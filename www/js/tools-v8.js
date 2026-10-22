
(()=>{const q=id=>document.getElementById(id), n=id=>parseFloat(q(id)?.value), out=(id,s)=>q(id).innerHTML=s;
const C=273.15,g=9.80665,R=287.05;
q('atmCalc')?.addEventListener('click',()=>{let h=n('atmH'),t=n('atmT')+C;let p=101325*Math.pow(1-2.25577e-5*h,5.25588),rho=p/(R*t),o2=p*.2095/1000;out('atmOut',`Pressure: ${(p/100).toFixed(1)} hPa<br>Density: ${rho.toFixed(3)} kg/m³<br>O₂ partial pressure: ${o2.toFixed(1)} kPa`)});
q('windCalc')?.addEventListener('click',()=>{let t=n('windT'),v=Math.max(4.8,n('windV'));let wc=13.12+.6215*t-11.37*Math.pow(v,.16)+.3965*t*Math.pow(v,.16);out('windOut',`Wind chill: ${wc.toFixed(1)} °C`)});
q('dewCalc')?.addEventListener('click',()=>{let t=n('dewT'),rh=n('dewRH'),a=17.62,b=243.12,gam=Math.log(rh/100)+a*t/(b+t),td=b*gam/(a-gam),base=Math.max(0,(t-td)*125);out('dewOut',`Dew point: ${td.toFixed(1)} °C<br>Estimated cloud base: ${Math.round(base)} m AGL`)});
q('slopeCalc')?.addEventListener('click',()=>{let rise=n('slopeRise'),run=n('slopeRun'),pct=100*rise/run,ang=Math.atan2(rise,run)*180/Math.PI;out('slopeOut',`Gradient: ${pct.toFixed(1)} %<br>Angle: ${ang.toFixed(1)}°`)});
q('etaCalc')?.addEventListener('click',()=>{let d=n('etaD'),speed=n('etaS'),up=n('etaUp'),vr=n('etaVR');let h=d/speed+up/vr;out('etaOut',`Estimated moving time: ${Math.floor(h)} h ${Math.round((h%1)*60)} min`)});
q('coordCalc')?.addEventListener('click',()=>{let deg=n('coordD'),min=n('coordM'),sec=n('coordS'),sign=q('coordDir').value==='-'?-1:1;out('coordOut',`Decimal: ${(sign*(Math.abs(deg)+min/60+sec/3600)).toFixed(6)}°`)});
q('waveCalc')?.addEventListener('click',()=>{let f=n('waveF');out('waveOut',`Wavelength: ${(299.792458/f).toFixed(3)} m<br>Quarter-wave: ${(74.9481145/f).toFixed(3)} m`)});
q('fsplCalc')?.addEventListener('click',()=>{let f=n('fsplF'),d=n('fsplD'),fspl=32.44+20*Math.log10(f)+20*Math.log10(d);out('fsplOut',`Free-space path loss: ${fspl.toFixed(1)} dB`)});
q('horizonCalc')?.addEventListener('click',()=>{let h1=n('horH1'),h2=n('horH2');let d=3.57*(Math.sqrt(h1)+Math.sqrt(h2));out('horOut',`Approx. radio/visual horizon: ${d.toFixed(1)} km`)});
q('doseCalc')?.addEventListener('click',()=>{let r=n('doseR'),m=n('doseM');out('doseOut',`Accumulated dose: ${(r*m/60).toFixed(3)} µSv`)});
q('inverseCalc')?.addEventListener('click',()=>{let r1=n('invR1'),d1=n('invD1'),d2=n('invD2');out('invOut',`Idealized rate at ${d2} m: ${(r1*Math.pow(d1/d2,2)).toFixed(3)} µSv/h`)});
q('batteryCalc')?.addEventListener('click',()=>{let wh=n('batWh'),w=n('batW'),eff=n('batEff')/100;out('batOut',`Idealized runtime: ${(wh*eff/w).toFixed(2)} h`)});
q('rkCalc')?.addEventListener('click',()=>{let m=n('rkM'),cda=n('rkCdA'),rho=n('rkRho'),tmax=n('rkT'),dt=n('rkDt'),v=0,x=0;const a=v=>g-(.5*rho*cda*v*Math.abs(v))/m;for(let t=0;t<tmax;t+=dt){let k1v=a(v),k1x=v,k2v=a(v+k1v*dt/2),k2x=v+k1v*dt/2,k3v=a(v+k2v*dt/2),k3x=v+k2v*dt/2,k4v=a(v+k3v*dt),k4x=v+k3v*dt;v+=dt*(k1v+2*k2v+2*k3v+k4v)/6;x+=dt*(k1x+2*k2x+2*k3x+k4x)/6}out('rkOut',`RK4 after ${tmax}s:<br>velocity ${v.toFixed(2)} m/s<br>distance ${x.toFixed(1)} m`)});
})();
