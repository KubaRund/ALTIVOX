
(async()=>{
 const parts=location.pathname.split('/').filter(Boolean);if(parts.length<2)return;
 const type=parts[0]==='gear'?'gear':parts[0]==='expeditions'?'field':null;if(!type)return;
 const slug=parts[1];let d;try{d=await fetch(`/api/record.php?type=${encodeURIComponent(type)}&slug=${encodeURIComponent(slug)}`,{cache:'no-store'}).then(r=>r.ok?r.json():null)}catch(e){return}
 if(!d?.ok)return;const r=d.record, esc=s=>String(s??'').replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[c]));
 const hero=document.querySelector('h1');if(hero&&r.title)hero.textContent=r.title;
 const desc=document.querySelector('.gear-detail-copyhero>p,.record-hero .deck');if(desc&&r.summary)desc.textContent=r.summary;
 const renderText=t=>String(t||'').split(/\r?\n/).map(x=>{x=x.trim();if(!x)return'';if(x.startsWith('## '))return`<h2>${esc(x.slice(3))}</h2>`;if(x.startsWith('### '))return`<h3>${esc(x.slice(4))}</h3>`;if(x.startsWith('- '))return`<li>${esc(x.slice(2))}</li>`;return`<p>${esc(x)}</p>`}).join('').replace(/(<li>.*?<\/li>)+/gs,m=>'<ul>'+m+'</ul>');
 const fields=[['BRAND',r.brand],['CATEGORY',r.category],['ROLE',r.role],['DATE',r.date],['LOCATION',r.location]];
 Object.entries(r.metrics||{}).forEach(([k,v])=>fields.push([k,v]));
 const extras=[['FIELD USE',r.field_use],['STRENGTHS',r.strengths],['LIMITATIONS',r.limitations],['TECH NOTES',r.tech_notes]].filter(x=>x[1]);
 const sec=document.createElement('section');sec.className='v8-managed-record';sec.innerHTML=`<div class="managed-kicker">CONTROL CENTER / LIVE RECORD</div><h2>${esc(r.title)}</h2><div class="v8-managed-meta"><span>${esc((r.type||'').toUpperCase())}</span><span>${esc((r.status||'').toUpperCase())}</span>${(r.tags||[]).map(x=>`<span>${esc(String(x).toUpperCase())}</span>`).join('')}</div><div class="v8-managed-columns"><div>${r.body?renderText(r.body):`<p>${esc(r.summary||'')}</p>`}${extras.map(x=>`<h3>${esc(x[0])}</h3>${renderText(x[1])}`).join('')}</div><div class="v8-managed-fields">${fields.filter(x=>x[1]).map(x=>`<div><small>${esc(x[0])}</small><b>${esc(x[1])}</b></div>`).join('')}</div></div>`;
 const main=document.querySelector('main');if(main)main.appendChild(sec);
})();
