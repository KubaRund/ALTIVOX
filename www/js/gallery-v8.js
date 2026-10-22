
(async()=>{
 const grid=document.querySelector('#v8Gallery'), controls=document.querySelector('#v8GalleryControls'), count=document.querySelector('#galleryCount');
 if(!grid)return;
 const esc=s=>String(s).replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[c]));
 let images=[];
 try{const r=await fetch('/api/gallery.php',{cache:'no-store'});const d=await r.json();images=d.images||[];if(count)count.textContent=images.length+' IMAGES';}
 catch(e){grid.innerHTML='<div class="v8-gallery-empty">Gallery API se nepodařilo načíst.</div>';return;}
 const categories=['all',...new Set(images.map(x=>x.category))];
 const labels={all:'ALL',field:'FIELD',gear:'GEAR',lab:'LAB',signal:'SIGNAL',water:'WATER',uploads:'UPLOADS',other:'OTHER'};
 controls.innerHTML=categories.map((c,i)=>`<button class="${i===0?'active':''}" data-cat="${c}">${labels[c]||c.toUpperCase()}</button>`).join('');
 function render(cat='all'){
   const arr=cat==='all'?images:images.filter(x=>x.category===cat);
   grid.innerHTML=arr.length?arr.map((x,i)=>`<a href="${esc(x.url)}" class="v8-gallery-item" data-full="${esc(x.url)}"><img loading="lazy" src="${esc(x.url)}" alt="${esc(x.label)}"><span>${esc(x.category.toUpperCase())} / ${esc(x.label.toUpperCase())}</span></a>`).join(''):'<div class="v8-gallery-empty">V této kategorii zatím nejsou fotografie.</div>';
 }
 controls.addEventListener('click',e=>{const b=e.target.closest('button');if(!b)return;controls.querySelectorAll('button').forEach(x=>x.classList.remove('active'));b.classList.add('active');render(b.dataset.cat)});
 render();
 const lb=document.querySelector('#v8Lightbox'), lbImg=lb.querySelector('img');
 grid.addEventListener('click',e=>{const a=e.target.closest('[data-full]');if(!a)return;e.preventDefault();lbImg.src=a.dataset.full;lb.classList.add('open')});
 lb.addEventListener('click',e=>{if(e.target===lb||e.target.closest('button')){lb.classList.remove('open');lbImg.src='';}});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){lb.classList.remove('open');lbImg.src='';}});
})();
