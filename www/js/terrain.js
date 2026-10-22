(()=>{'use strict';
const elevationSource=document.getElementById('elevationSource'),sourceBadge=document.getElementById('sourceBadge');
const $=s=>document.querySelector(s);const map=L.map('terrainMap',{zoomControl:true}).setView([50.181,12.64],12);L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap'}).addTo(map);
let rect=L.rectangle([[0,0],[0,0]],{color:'#2f7df6',weight:2,fillColor:'#2f7df6',fillOpacity:.14}).addTo(map);let zScale=1.5,lastSTL=null,lastName='terrain';let scene,camera,renderer,controls,modelGroup;
const areaKm=$('#areaKm'),aspect=$('#aspect'),modelWidth=$('#modelWidth'),baseHeight=$('#baseHeight'),status=$('#terrainStatus');
function bounds(){const c=map.getCenter(),w=+areaKm.value,h=w*(+aspect.value),latD=(h/2)/111.32,lonD=(w/2)/(111.32*Math.cos(c.lat*Math.PI/180));return L.latLngBounds([c.lat-latD,c.lng-lonD],[c.lat+latD,c.lng+lonD])}
function updateRect(){const b=bounds();rect.setBounds(b);$('#areaKmOut').value=(+areaKm.value).toFixed(1).replace('.0','')+' km';$('#modelWidthOut').value=modelWidth.value+' mm';$('#baseHeightOut').value=baseHeight.value+' mm';$('#areaInfo').textContent=(+areaKm.value).toFixed(1)+' × '+((+areaKm.value)*(+aspect.value)).toFixed(1)+' km';}
map.on('move zoom',updateRect);[areaKm,aspect].forEach(x=>x.addEventListener('input',updateRect));[modelWidth,baseHeight].forEach(x=>x.addEventListener('input',updateRect));updateRect();
$('#zButtons').addEventListener('click',e=>{if(!e.target.dataset.z)return;zScale=+e.target.dataset.z;document.querySelectorAll('#zButtons button').forEach(b=>b.classList.toggle('active',b===e.target));});
async function search(){const q=$('#placeSearch').value.trim();if(!q)return;status.textContent='Hledám místo…';try{const r=await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='+encodeURIComponent(q),{headers:{'Accept':'application/json'}});const j=await r.json();if(!j.length)throw Error('Místo nebylo nalezeno.');map.setView([+j[0].lat,+j[0].lon],12);$('#modelName').value=(j[0].display_name.split(',')[0]||q).toUpperCase().slice(0,32);status.textContent='Místo nalezeno. Můžeš generovat.';}catch(e){status.textContent=e.message;status.className='terrain-status error';}}
$('#searchBtn').onclick=search;$('#placeSearch').addEventListener('keydown',e=>{if(e.key==='Enter')search()});
function tileXY(lat,lon,z){const n=2**z,x=(lon+180)/360*n,latR=lat*Math.PI/180,y=(1-Math.asinh(Math.tan(latR))/Math.PI)/2*n;return{x,y}}
const tileCache=new Map();async function getTile(z,x,y){const n=2**z;x=((x%n)+n)%n;const key=`${z}/${x}/${y}`;if(tileCache.has(key))return tileCache.get(key);const p=new Promise((resolve,reject)=>{const im=new Image();im.crossOrigin='anonymous';im.onload=()=>{const c=document.createElement('canvas');c.width=c.height=256;const ctx=c.getContext('2d',{willReadFrequently:true});ctx.drawImage(im,0,0);resolve(ctx.getImageData(0,0,256,256).data)};im.onerror=()=>reject(Error('Výšková data se nepodařilo načíst.'));im.src=`https://s3.amazonaws.com/elevation-tiles-prod/terrarium/${z}/${x}/${y}.png`;});tileCache.set(key,p);return p}
async function elevation(lat,lon,z){const t=tileXY(lat,lon,z),tx=Math.floor(t.x),ty=Math.floor(t.y),px=Math.max(0,Math.min(255,Math.floor((t.x-tx)*256))),py=Math.max(0,Math.min(255,Math.floor((t.y-ty)*256))),d=await getTile(z,tx,ty),i=(py*256+px)*4;return d[i]*256+d[i+1]+d[i+2]/256-32768}

const CZ_BOUNDS={w:12.09,e:18.86,s:48.55,n:51.06};
function isInCzechia(lat,lon){return lon>=CZ_BOUNDS.w&&lon<=CZ_BOUNDS.e&&lat>=CZ_BOUNDS.s&&lat<=CZ_BOUNDS.n}
function updateElevationSource(){
  if(!elevationSource||!sourceBadge||!map)return;
  const c=map.getCenter(),auto=elevationSource.value==='auto';
  if(auto&&isInCzechia(c.lat,c.lng)){
    sourceBadge.textContent='ČR · ČÚZK DMR 5G · LiDAR · připraveno pro serverový import';
    sourceBadge.classList.add('is-dmr');
    sourceBadge.title='DMR 5G: oficiální výškopis ČÚZK. Přímé LAZ zpracování vyžaduje serverový převodník.';
  }else{
    sourceBadge.textContent='GLOBAL · terrain DEM';
    sourceBadge.classList.remove('is-dmr');
    sourceBadge.title='Globální výškový model.';
  }
}

function terrainZoom(b,n){const c=b.getCenter(),widthM=+areaKm.value*1000,spacing=Math.max(1,widthM/(n-1)),metersPerPixel0=156543.03392*Math.cos(c.lat*Math.PI/180),ideal=Math.ceil(Math.log2(metersPerPixel0/spacing));return Math.max(10,Math.min(15,ideal))}
async function sampleGrid(b,n){const s=b.getSouth(),north=b.getNorth(),w=b.getWest(),e=b.getEast(),z=terrainZoom(b,n);const out=Array.from({length:n},()=>Array(n));let done=0,total=n*n;const jobs=[];status.textContent=`Načítám reliéf ${n} × ${n} · terrain z${z}…`;for(let r=0;r<n;r++)for(let c=0;c<n;c++){jobs.push((async()=>{const lat=north-(north-s)*r/(n-1),lon=w+(e-w)*c/(n-1);out[r][c]=await elevation(lat,lon,z);done++;if(done%Math.max(80,Math.floor(total/100))===0)status.textContent=`Načítám reliéf ${n} × ${n} · ${Math.round(done/total*100)} %`;})())}await Promise.all(jobs);return out}
function tri(a,b,c,arr){arr.push([a,b,c])}function box(x0,x1,y0,y1,z0,z1,arr){const p=[[x0,y0,z0],[x1,y0,z0],[x1,y1,z0],[x0,y1,z0],[x0,y0,z1],[x1,y0,z1],[x1,y1,z1],[x0,y1,z1]],f=[[0,2,1],[0,3,2],[4,5,6],[4,6,7],[0,1,5],[0,5,4],[1,2,6],[1,6,5],[2,3,7],[2,7,6],[3,0,4],[3,4,7]];f.forEach(q=>tri(p[q[0]],p[q[1]],p[q[2]],arr))}
const FONT={A:['01110','10001','10001','11111','10001','10001','10001'],B:['11110','10001','10001','11110','10001','10001','11110'],C:['01111','10000','10000','10000','10000','10000','01111'],D:['11110','10001','10001','10001','10001','10001','11110'],E:['11111','10000','10000','11110','10000','10000','11111'],F:['11111','10000','10000','11110','10000','10000','10000'],G:['01111','10000','10000','10111','10001','10001','01111'],H:['10001','10001','10001','11111','10001','10001','10001'],I:['11111','00100','00100','00100','00100','00100','11111'],J:['00111','00010','00010','00010','10010','10010','01100'],K:['10001','10010','10100','11000','10100','10010','10001'],L:['10000','10000','10000','10000','10000','10000','11111'],M:['10001','11011','10101','10101','10001','10001','10001'],N:['10001','11001','10101','10011','10001','10001','10001'],O:['01110','10001','10001','10001','10001','10001','01110'],P:['11110','10001','10001','11110','10000','10000','10000'],Q:['01110','10001','10001','10001','10101','10010','01101'],R:['11110','10001','10001','11110','10100','10010','10001'],S:['01111','10000','10000','01110','00001','00001','11110'],T:['11111','00100','00100','00100','00100','00100','00100'],U:['10001','10001','10001','10001','10001','10001','01110'],V:['10001','10001','10001','10001','10001','01010','00100'],W:['10001','10001','10001','10101','10101','10101','01010'],X:['10001','10001','01010','00100','01010','10001','10001'],Y:['10001','10001','01010','00100','00100','00100','00100'],Z:['11111','00001','00010','00100','01000','10000','11111'],'0':['01110','10001','10011','10101','11001','10001','01110'],'1':['00100','01100','00100','00100','00100','00100','01110'],'2':['01110','10001','00001','00010','00100','01000','11111'],'3':['11110','00001','00001','01110','00001','00001','11110'],'4':['00010','00110','01010','10010','11111','00010','00010'],'5':['11111','10000','10000','11110','00001','00001','11110'],'6':['01110','10000','10000','11110','10001','10001','01110'],'7':['11111','00001','00010','00100','01000','01000','01000'],'8':['01110','10001','10001','01110','10001','10001','01110'],'9':['01110','10001','10001','01111','00001','00001','01110'],'-':['00000','00000','00000','11111','00000','00000','00000'],'.':['00000','00000','00000','00000','00000','00110','00110'],',':['00000','00000','00000','00000','00110','00100','01000'],' ':['00000','00000','00000','00000','00000','00000','00000']};
function cleanText(s){return s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase().replace(/[^A-Z0-9 .,\-]/g,' ').replace(/\s+/g,' ').trim()}
function textBoxes(text,yFront,zBase,maxWidth,height,depth,arr,centerX=0){text=cleanText(text);const unit=height/7,gap=unit,total=text.length*6*unit;if(total>maxWidth)unit*=maxWidth/total;let x=centerX-text.length*6*unit/2;for(const ch of text){const g=FONT[ch]||FONT[' '];for(let r=0;r<7;r++)for(let c=0;c<5;c++)if(g[r][c]==='1'){const x0=x+c*unit,x1=x+(c+.82)*unit,z1=zBase+(7-r)*unit,z0=z1-.82*unit;box(x0,x1,yFront-depth,yFront+.05,z0,z1,arr)}x+=6*unit}}
function buildTriangles(grid,b){const n=grid.length,W=+modelWidth.value,H=W*(+aspect.value),base=+baseHeight.value,min=Math.min(...grid.flat()),max=Math.max(...grid.flat()),realW=+areaKm.value*1000,mmPerM=W/realW,rel=(v)=>(v-min)*mmPerM*zScale+base;const tris=[];for(let r=0;r<n-1;r++)for(let c=0;c<n-1;c++){const x0=-W/2+W*c/(n-1),x1=-W/2+W*(c+1)/(n-1),y0=H/2-H*r/(n-1),y1=H/2-H*(r+1)/(n-1),a=[x0,y0,rel(grid[r][c])],bb=[x1,y0,rel(grid[r][c+1])],cc=[x0,y1,rel(grid[r+1][c])],d=[x1,y1,rel(grid[r+1][c+1])];tri(a,cc,bb,tris);tri(bb,cc,d,tris)}
tri([-W/2,-H/2,0],[W/2,H/2,0],[W/2,-H/2,0],tris);tri([-W/2,-H/2,0],[-W/2,H/2,0],[W/2,H/2,0],tris);
for(let i=0;i<n-1;i++){let x0=-W/2+W*i/(n-1),x1=-W/2+W*(i+1)/(n-1);let z0=rel(grid[n-1][i]),z1=rel(grid[n-1][i+1]);tri([x0,-H/2,0],[x1,-H/2,z1],[x1,-H/2,0],tris);tri([x0,-H/2,0],[x0,-H/2,z0],[x1,-H/2,z1],tris);z0=rel(grid[0][i]);z1=rel(grid[0][i+1]);tri([x0,H/2,0],[x1,H/2,0],[x1,H/2,z1],tris);tri([x0,H/2,0],[x1,H/2,z1],[x0,H/2,z0],tris)}
for(let i=0;i<n-1;i++){let y0=H/2-H*i/(n-1),y1=H/2-H*(i+1)/(n-1);let z0=rel(grid[i][0]),z1=rel(grid[i+1][0]);tri([-W/2,y0,0],[-W/2,y1,0],[-W/2,y1,z1],tris);tri([-W/2,y0,0],[-W/2,y1,z1],[-W/2,y0,z0],tris);z0=rel(grid[i][n-1]);z1=rel(grid[i+1][n-1]);tri([W/2,y0,0],[W/2,y1,z1],[W/2,y1,0],tris);tri([W/2,y0,0],[W/2,y0,z0],[W/2,y1,z1],tris)}
const name=cleanText($('#modelName').value)||'ALTIVOX TERRAIN';textBoxes(name,-H/2,base*.54,W*.86,Math.min(4.2,base*.40),.7,tris);if($('#coordsText').checked){const c=b.getCenter(),ct=`${c.lat.toFixed(4)} N, ${c.lng.toFixed(4)} E`;textBoxes(ct,-H/2,base*.08,W*.44,Math.min(1.8,base*.18),.55,tris,-W*.23)}if($('#altivoxMark').checked)textBoxes('MADE ON ALTIVOX',-H/2,base*.08,W*.36,Math.min(1.9,base*.19),.55,tris,W*.27);
return{tris,min,max,W,H,height:rel(max),name}}
function normal(a,b,c){const ux=b[0]-a[0],uy=b[1]-a[1],uz=b[2]-a[2],vx=c[0]-a[0],vy=c[1]-a[1],vz=c[2]-a[2],nx=uy*vz-uz*vy,ny=uz*vx-ux*vz,nz=ux*vy-uy*vx,l=Math.hypot(nx,ny,nz)||1;return[nx/l,ny/l,nz/l]}
function stlFrom(tris,name){let s=`solid ${name}\n`;for(const t of tris){const n=normal(...t);s+=` facet normal ${n.join(' ')}\n  outer loop\n`;for(const v of t)s+=`   vertex ${v.join(' ')}\n`;s+='  endloop\n endfacet\n'}return s+`endsolid ${name}\n`}
function init3D(){scene=new THREE.Scene();camera=new THREE.PerspectiveCamera(38,1,.1,2000);renderer=new THREE.WebGLRenderer({canvas:$('#terrainCanvas'),antialias:true,alpha:true});renderer.setPixelRatio(Math.min(devicePixelRatio,2));controls=new THREE.OrbitControls(camera,renderer.domElement);controls.enableDamping=true;scene.add(new THREE.HemisphereLight(0xffffff,0x263246,1.5));const d=new THREE.DirectionalLight(0xffffff,1.8);d.position.set(100,-120,180);scene.add(d);function resize(){const el=renderer.domElement,p=el.parentElement,w=p.clientWidth,h=p.clientHeight;renderer.setSize(w,h,false);camera.aspect=w/h;camera.updateProjectionMatrix()}addEventListener('resize',resize);resize();(function loop(){requestAnimationFrame(loop);controls.update();renderer.render(scene,camera)})()}

let previewLook='relief';
function elevationColors(g){
  const p=g.getAttribute('position'), cols=new Float32Array(p.count*3);
  let lo=Infinity,hi=-Infinity;
  for(let i=0;i<p.count;i++){const z=p.getZ(i);lo=Math.min(lo,z);hi=Math.max(hi,z)}
  const span=Math.max(.001,hi-lo), c=new THREE.Color();
  for(let i=0;i<p.count;i++){
    const t=(p.getZ(i)-lo)/span;
    if(t<.28)c.setRGB(.10+.10*t,.28+.85*t,.62-.45*t);
    else if(t<.62)c.setRGB(.16+.55*(t-.28),.48+.22*(t-.28),.22);
    else if(t<.84)c.setRGB(.40+.75*(t-.62),.42+.42*(t-.62),.22+.20*(t-.62));
    else c.setRGB(.72+1.5*(t-.84),.70+1.6*(t-.84),.67+1.8*(t-.84));
    cols[i*3]=Math.min(1,c.r);cols[i*3+1]=Math.min(1,c.g);cols[i*3+2]=Math.min(1,c.b);
  }
  g.setAttribute('color',new THREE.BufferAttribute(cols,3));
}
function makePreviewMaterial(g){
  elevationColors(g);
  return new THREE.MeshStandardMaterial({color:0xb9c5c9,roughness:.78,metalness:.03,side:THREE.DoubleSide,vertexColors:false,flatShading:false});
}
function applyPreviewLook(){
  if(!modelGroup)return;
  const mesh=modelGroup.children.find(x=>x.userData&&x.userData.terrainMesh); if(!mesh)return;
  const m=mesh.material, custom=document.querySelector('#previewColor');
  m.wireframe=false;m.vertexColors=false;m.flatShading=false;m.roughness=.78;m.metalness=.03;
  if(previewLook==='elevation'){m.vertexColors=true;m.color.set(0xffffff)}
  else if(previewLook==='dark'){m.color.set(0x4f6170);m.roughness=.62}
  else if(previewLook==='wire'){m.color.set(0x77aaff);m.wireframe=true;m.roughness=1}
  else {m.color.set(custom?custom.value:'#b9c5c9');m.roughness=.82}
  m.needsUpdate=true;
  const old=modelGroup.getObjectByName('terrainEdges');if(old){old.geometry.dispose();old.material.dispose();modelGroup.remove(old)}
  const edgeBox=document.querySelector('#previewEdges');
  if(edgeBox&&edgeBox.checked&&previewLook!=='wire'){
    const e=new THREE.LineSegments(new THREE.EdgesGeometry(mesh.geometry,18),new THREE.LineBasicMaterial({color:0x23364b,transparent:true,opacity:.45}));
    e.name='terrainEdges';modelGroup.add(e);
  }
}
function preview(tris,W,H,Z){if(modelGroup)scene.remove(modelGroup);modelGroup=new THREE.Group();const pos=[];tris.forEach(t=>t.forEach(v=>pos.push(...v)));const g=new THREE.BufferGeometry();g.setAttribute('position',new THREE.Float32BufferAttribute(pos,3));g.computeVertexNormals();const m=makePreviewMaterial(g);const mesh=new THREE.Mesh(g,m);mesh.userData.terrainMesh=true;modelGroup.add(mesh);applyPreviewLook();scene.add(modelGroup);camera.position.set(W*.9,-H*1.15,Math.max(Z*3,W*.65));controls.target.set(0,0,Z*.3);controls.update()}
init3D();
document.querySelectorAll('#visualModes button').forEach(b=>b.addEventListener('click',()=>{
  document.querySelectorAll('#visualModes button').forEach(x=>x.classList.remove('active'));
  b.classList.add('active');previewLook=b.dataset.look;applyPreviewLook();
}));
const previewColor=document.querySelector('#previewColor');
if(previewColor)previewColor.addEventListener('input',()=>{previewLook='relief';document.querySelectorAll('#visualModes button').forEach(x=>x.classList.toggle('active',x.dataset.look==='relief'));applyPreviewLook()});
const resetColor=document.querySelector('#resetColor');
if(resetColor)resetColor.addEventListener('click',()=>{previewColor.value='#b9c5c9';previewLook='relief';document.querySelectorAll('#visualModes button').forEach(x=>x.classList.toggle('active',x.dataset.look==='relief'));applyPreviewLook()});
const previewEdges=document.querySelector('#previewEdges');
if(previewEdges)previewEdges.addEventListener('change',applyPreviewLook);
$('#resetView').onclick=()=>{if(!modelGroup)return;camera.position.set(+modelWidth.value*.9,-(+modelWidth.value)*(+aspect.value)*1.15,+modelWidth.value*.65);controls.target.set(0,0,10);controls.update()};
$('#generateBtn').onclick=async()=>{const btn=$('#generateBtn');btn.disabled=true;$('#downloadStl').disabled=true;status.className='terrain-status';status.textContent='Připravuji výšková data…';try{const b=bounds(),n=+$('#quality').value,grid=await sampleGrid(b,n);status.textContent='Stavím 3D geometrii…';const o=buildTriangles(grid,b);lastName=(cleanText($('#modelName').value)||'ALTIVOX-TERRAIN').replace(/\s+/g,'-').toLowerCase();lastSTL=stlFrom(o.tris,lastName);preview(o.tris,o.W,o.H,o.height);$('#statSize').textContent=`${o.W.toFixed(0)} × ${o.H.toFixed(0)} × ${o.height.toFixed(1)} mm`;$('#statElevation').textContent=`${o.min.toFixed(0)}–${o.max.toFixed(0)} m n. m.`;$('#statArea').textContent=`${(+areaKm.value).toFixed(1)} × ${((+areaKm.value)*(+aspect.value)).toFixed(1)} km`;const c=b.getCenter();$('#statCenter').textContent=`${c.lat.toFixed(4)}, ${c.lng.toFixed(4)}`;$('#downloadStl').disabled=false;status.textContent=`Hotovo · ${n} × ${n} bodů · ${o.tris.length.toLocaleString('cs-CZ')} trojúhelníků. STL je připraveno.`;}catch(e){console.error(e);status.textContent='Generování selhalo: '+e.message;status.className='terrain-status error'}finally{btn.disabled=false}};
$('#downloadStl').onclick=()=>{if(!lastSTL)return;const blob=new Blob([lastSTL],{type:'model/stl'}),a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=lastName+'-made-on-altivox.stl';a.click();setTimeout(()=>URL.revokeObjectURL(a.href),1000)};
})();

if(elevationSource){elevationSource.addEventListener('change',updateElevationSource);}
if(typeof map!=='undefined'&&map){map.on('moveend',updateElevationSource);setTimeout(updateElevationSource,0);}
