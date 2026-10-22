
(()=>{
 const $=s=>document.querySelector(s), lat=$('#lat'), lon=$('#lon'), status=$('#wxstatus');
 const presets={sokolov:[50.1813,12.6401],fuscher:[47.0745,12.6947],glockner:[47.0746,12.6939],schwaiger:[47.1628,12.7389],breithorn:[45.941,7.748]};
 async function load(){
  const la=parseFloat(lat.value),lo=parseFloat(lon.value);if(!Number.isFinite(la)||!Number.isFinite(lo))return;
  status.textContent='Načítám Open-Meteo…';
  const u=`https://api.open-meteo.com/v1/forecast?latitude=${la}&longitude=${lo}&current=temperature_2m,apparent_temperature,precipitation,rain,weather_code,cloud_cover,pressure_msl,wind_speed_10m,wind_gusts_10m,wind_direction_10m&hourly=temperature_2m,precipitation_probability,precipitation,cloud_cover,wind_speed_10m,wind_gusts_10m,freezing_level_height&forecast_days=2&timezone=auto`;
  try{
   const d=await fetch(u).then(r=>r.json()),c=d.current||{};
   $('#temp').textContent=(c.temperature_2m??'—')+' °C';$('#feel').textContent=(c.apparent_temperature??'—')+' °C';
   $('#wind').textContent=(c.wind_speed_10m??'—')+' / '+(c.wind_gusts_10m??'—')+' km/h';$('#rain').textContent=(c.precipitation??'—')+' mm';
   $('#pressure').textContent=(c.pressure_msl??'—')+' hPa';$('#cloud').textContent=(c.cloud_cover??'—')+' %';$('#place').textContent=`${la.toFixed(4)} N / ${lo.toFixed(4)} E`;
   const h=d.hourly||{}, now=Date.now(), rows=[];
   for(let i=0;i<(h.time||[]).length&&rows.length<12;i++){const t=new Date(h.time[i]);if(t.getTime()<now-3600000)continue;rows.push(`<div><b>${t.toLocaleTimeString('cs-CZ',{hour:'2-digit',minute:'2-digit'})}</b><span>${h.temperature_2m[i]} °C</span><span>${h.wind_speed_10m[i]} / ${h.wind_gusts_10m[i]} km/h</span><span>${h.precipitation_probability[i]} %</span><span>0°C: ${Math.round(h.freezing_level_height[i]||0)} m</span></div>`)}
   $('#hourly').innerHTML=rows.join('');
   status.textContent='Open-Meteo / aktualizováno '+new Date().toLocaleTimeString('cs-CZ')+'. Pro horská rozhodnutí vždy ověř oficiální výstrahy a lokální předpověď.';
  }catch(e){status.textContent='Live data se nepodařilo načíst. Zkontroluj připojení.'}
 }
 document.querySelectorAll('[data-preset]').forEach(b=>b.addEventListener('click',()=>{const p=presets[b.dataset.preset];lat.value=p[0];lon.value=p[1];load()}));
 $('#load')?.addEventListener('click',load);load();
})();
