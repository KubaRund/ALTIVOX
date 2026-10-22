window.altivoxDuties = async ({$, on, notice, download, shuffle}) => {
  $('tool-content').innerHTML='<div class="panel">Připravuji ručně psanou šablonu…</div>';
  try {
    if (!window.altivoxDutyFont) window.altivoxDutyFont = new FontFace('AltivoxHand', 'url(./vendor/caveat/Caveat.ttf)', {weight:'400'}).load().then(font=>{document.fonts.add(font);return font;});
    await window.altivoxDutyFont;
  } catch { window.altivoxDutyFont=null; if(location.hash==='#duties') $('tool-content').innerHTML='<div class="panel">Písmo šablony nelze načíst. Obnovte stránku a zkuste to znovu.</div>';return; }
  if(location.hash!=='#duties')return;
  const roles=['Tabule','Květiny','Úklid','Pomůcky','Světla a technika','Okno a větrání'];
  $('tool-content').innerHTML=`<div class="split"><div class="panel"><h2>Rozpis třídních služeb</h2><label class="field">Jména, každé na nový řádek<textarea id="duty-names" maxlength="12000" placeholder="Anna Nováková&#10;David Černý"></textarea></label><div class="fields"><label class="field">Třída<input id="duty-class" maxlength="30" placeholder="6. B"></label><label class="field">Školní rok<input id="duty-year" maxlength="30" value="2026/27"></label></div><label class="field">Období (nepovinné)<input id="duty-period" maxlength="60" placeholder="12.–16. října"></label><label class="field">Počet žáků u každé služby<select id="duty-count"><option value="1">1</option><option value="2" selected>2</option><option value="3">3</option></select></label><div class="actions"><button id="duty-generate" class="btn">Vygenerovat / přelosovat</button><button id="duty-next" class="btn secondary" disabled>Další rozpis v pořadí</button><button id="duty-pdf" class="btn secondary" disabled>Stáhnout PDF</button></div><p class="hint">Jména zůstávají jen v paměti této stránky. Každý žák je v jednom rozpisu nejvýše jednou. Když jmen nestačí, zbylá místa zůstanou prázdná. Další rozpis pokračuje v pořadí, aby se vystřídala celá třída.</p><p id="duty-status" class="hint" role="status"></p></div><div class="panel"><h2>Náhled PDF · A4</h2><canvas id="duty-preview" width="1240" height="1754" aria-label="Náhled rozpisu třídních služeb" style="width:100%;height:auto;background:white;border-radius:8px"></canvas></div></div>`;
  const canvas=$('duty-preview'), ctx=canvas.getContext('2d');
  let queue=[],offset=0,assigned=null,signature='';
  const inputSignature=()=>$('duty-names').value+'|'+$('duty-count').value;
  function fit(text,x,y,max,size=34){ctx.font=`${size}px AltivoxHand`;while(ctx.measureText(text).width>max&&size>16){size--;ctx.font=`${size}px AltivoxHand`;}ctx.fillText(text,x,y);}
  // Original line drawings matching the six symbols in the reference photo.
  const symbols=[
    'M3 5L23 6L22 19L2 18Z M5 4L25 5L24 19 M10 19L10 22L17 22',
    'M10 17L19 17L17 24L12 24Z M14 17L14 6 M14 12Q4 7 6 4Q12 1 14 8 M14 8Q17 1 22 4Q24 9 15 11',
    'M5 1L14 17 M14 17L22 19L25 27L10 25L14 17 M14 20L12 24 M18 21L18 25 M21 22L23 26',
    'M3 3L8 3L8 24L3 24Z M3 7L6 7 M3 11L6 11 M3 15L6 15 M3 19L6 19 M12 3L15 3L15 22L12 24Z M12 3L13 1L15 3 M22 11L22 25 M22 11C15 10 16 1 22 2C28 3 28 10 22 11',
    'M10 17C1 11 5 1 14 2C23 3 24 12 17 17L17 22L11 22Z M11 25L17 25 M13 16L10 8L18 11L14 17',
    'M3 3L24 4L23 25L2 24Z M13 4L12 24 M3 14L23 15 M4 4L3 23 M14 5L23 5L22 13L14 13Z'
  ];
  function symbol(i,x,y){ctx.save();ctx.translate(x,y);ctx.scale(1.9,1.9);ctx.lineWidth=.85;ctx.lineCap='round';ctx.lineJoin='round';ctx.strokeStyle='#292723';ctx.stroke(new Path2D(symbols[i]));ctx.restore();}
  function line(x,y,width,i,j){ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x+width*.3,y+(i%2?1:-1));ctx.lineTo(x+width*.7,y+(j?2:0));ctx.lineTo(x+width,y-1);ctx.lineWidth=1.5;ctx.strokeStyle='#292723';ctx.stroke();}
  function draw(){
    ctx.fillStyle='#fff';ctx.fillRect(0,0,1240,1754);ctx.fillStyle='#292723';ctx.textAlign='center';
    fit('Rozpis Třídních služeb',710,140,930,73);
    fit($('duty-period').value,710,190,860,34);ctx.textAlign='left';
    const positions=[250,420,640,820,945,1135];
    roles.forEach((role,i)=>{const y=positions[i];fit(role,65,y,325,i===2?75:i>3?49:58);
      const labelWidth=ctx.measureText(role).width;symbol(i,Math.min(345,75+labelWidth),y-45);
      const count=Number($('duty-count').value),width=750/count;
      for(let j=0;j<count;j++){const x=440+j*width;fit(assigned?.[i]?.[j]||'',x+5,y+19,width-32,45);line(x,y+34,width-32,i,j);}
    });
    ctx.textAlign='center';ctx.save();ctx.translate(760,1640);ctx.rotate(.028);
    fit([$('duty-class').value,$('duty-year').value&&'Školní rok '+$('duty-year').value].filter(Boolean).join(' · '),0,0,950,51);ctx.restore();ctx.textAlign='left';
  }
  function generate(fresh){
    const people=$('duty-names').value.split(/\r?\n/).map(x=>x.trim()).filter(Boolean);
    if(!people.length||people.length>300||people.some(x=>x.length>100)){notice('Zadejte 1–300 jmen, každé nejvýše 100 znaků.');return;}
    if(new Set(people).size!==people.length){notice('V seznamu se opakují stejná jména. Rozlište je například příjmením nebo iniciálou.');return;}
    if(fresh||signature!==inputSignature()){queue=shuffle(people);offset=0;signature=inputSignature();}
    const slots=6*Number($('duty-count').value),used=Math.min(slots,queue.length),selected=Array.from({length:used},(_,i)=>queue[(offset+i)%queue.length]);offset=(offset+(used===queue.length?1:used))%queue.length;
    assigned=roles.map((_,i)=>selected.slice(i*Number($('duty-count').value),(i+1)*Number($('duty-count').value)));
    $('duty-status').textContent=`Rozděleno ${used} z ${queue.length} žáků.${used<slots?' '+(slots-used)+' míst zůstává prázdných.':''}`;
    $('duty-pdf').disabled=false;$('duty-next').disabled=false;draw();
  }
  function invalidate(){assigned=null;queue=[];signature='';$('duty-next').disabled=true;$('duty-pdf').disabled=true;$('duty-status').textContent='Seznam se změnil. Vygenerujte nový rozpis.';draw();}
  on('duty-names','input',invalidate);on('duty-count','change',invalidate);
  ['duty-class','duty-year','duty-period'].forEach(id=>on(id,'input',draw));
  on('duty-generate','click',()=>generate(true));on('duty-next','click',()=>generate(false));
  on('duty-pdf','click',()=>{
    if(!assigned)return;draw();
    // A self-contained, one-page A4 PDF. Rasterized text preserves Czech characters without external fonts.
    const jpg=Uint8Array.from(atob(canvas.toDataURL('image/jpeg',.96).split(',')[1]),c=>c.charCodeAt(0)),enc=new TextEncoder(),chunks=[],offsets=[0];let length=0;
    const add=v=>{const bytes=typeof v==='string'?enc.encode(v):v;chunks.push(bytes);length+=bytes.length;};
    const object=(id,body)=>{offsets[id]=length;add(`${id} 0 obj\n${body}\nendobj\n`);};
    add('%PDF-1.4\n');object(1,'<< /Type /Catalog /Pages 2 0 R >>');object(2,'<< /Type /Pages /Kids [3 0 R] /Count 1 >>');
    object(3,'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>');
    offsets[4]=length;add(`4 0 obj\n<< /Type /XObject /Subtype /Image /Width 1240 /Height 1754 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpg.length} >>\nstream\n`);add(jpg);add('\nendstream\nendobj\n');
    const content='q\n595.28 0 0 841.89 0 0 cm\n/Im0 Do\nQ\n';object(5,`<< /Length ${enc.encode(content).length} >>\nstream\n${content}endstream`);
    const start=length;add('xref\n0 6\n0000000000 65535 f \n');for(let i=1;i<=5;i++)add(String(offsets[i]).padStart(10,'0')+' 00000 n \n');add(`trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n${start}\n%%EOF\n`);
    download('altivox-tridni-sluzby.pdf',new Blob(chunks,{type:'application/pdf'}));
  });draw();
};

