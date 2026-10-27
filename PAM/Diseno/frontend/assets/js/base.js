const EMBLEM_C='assets/img/escudo-color.png';
/* ================== PAM · prototipo ================== */
const $=s=>document.querySelector(s);
const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const nf=new Intl.NumberFormat('es-GT');
const fmt=n=>nf.format(Math.round(n||0));
const pc=(a,b,d=1)=>b?(a/b*100).toFixed(d):'0.0';
let CORTE='';                       // fecha de corte de los datos (la entrega la API)
const HOY=new Date(), ANIO=HOY.getFullYear(), MES=HOY.getMonth();  // MES: 0-11
const MESC=Math.max(1,MES);         // meses completos del año en curso (para comparar contra el año anterior)
const MESES=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
const PERIODO=`ene–${MESES[MESC-1]}`;
const DEPS=['Guatemala','El Progreso','Sacatepéquez','Chimaltenango','Escuintla','Santa Rosa','Sololá','Totonicapán','Quetzaltenango','Suchitepéquez','Retalhuleu','San Marcos','Huehuetenango','Quiché','Baja Verapaz','Alta Verapaz','Petén','Izabal','Zacapa','Chiquimula','Jalapa','Jutiapa'];
let EST=['En nómina activa','En trámite','Negativo','Bloqueado','Suspendido','No localizado','Anulado','Reactivado','Fallecido'];
const EST_ICON=['check','clock','x','lock','pause','pin','slash','refresh','minus'];
const AB=['65–69','70–74','75–79','80–84','85–89','90+'];
let RG=['Positiva','Negativa','Suspensión por fallecimiento (RENAP)','Suspensión administrativa','Reactivación / reanudación','No identifica','Improcedente / desistimiento','Modificación de datos','Sin resolución'];
const NOM=['Nunca ingresó','Activo en nómina','Egresado de nómina'];
const VIS=['Sin visita','Visita positiva','Visita negativa'];
const ANT=['','0–30 días','31–60 días','61–90 días','91–180 días','181–365 días','Más de 365 días'];
const ICON={
 grid:'<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
 users:'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
 file:'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/>',
 pin:'<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
 stamp:'<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
 wallet:'<path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>',
 pulse:'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
 map:'<path d="M9 3 3 6v15l6-3 6 3 6-3V3l-6 3-6-3z"/><path d="M9 3v15"/><path d="M15 6v15"/>',
 alert:'<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
 chart:'<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
 shield:'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
 sliders:'<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
 db:'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/>',
 check:'<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
 x:'<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
 pause:'<circle cx="12" cy="12" r="9"/><path d="M10 15V9M14 15V9"/>',
 minus:'<circle cx="12" cy="12" r="9"/><path d="M8 12h8"/>',
 clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 lock:'<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
 slash:'<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
 refresh:'<path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>',
 close:'<path d="M18 6 6 18M6 6l12 12"/>',
 chev:'<path d="m9 18 6-6-6-6"/>',
 eye:'<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
 info:'<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
 download:'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>',
 copy:'<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
 upload:'<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m17 8-5-5-5 5"/><path d="M12 3v12"/>'
};
const ic=(k,w=18)=>`<svg viewBox="0 0 24 24" width="${w}" height="${w}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICON[k]}</svg>`;
const stc=i=>`var(--st${i})`;
const badge=i=>`<span class="badge" style="color:${stc(i)};background:color-mix(in srgb,${stc(i)} 13%,transparent);border-color:color-mix(in srgb,${stc(i)} 30%,transparent)"><i style="background:${stc(i)}"></i>${EST[i]}</span>`;
function nomBadge(n){const c=n===1?'var(--ok)':n===2?'var(--off)':'var(--ink3)';return `<span class="badge" style="color:${c};background:color-mix(in srgb,${c} 12%,transparent)">${n===1?'En nómina':n===2?'Egresado':'Sin nómina'}</span>`}

/* ---------- data ---------- */
let C,S,G,MU,MN,MDEP,YS,MUNI_BY_DEP,RSIN=9,TOTAL_NAC=0;
function prepCube(j){
  const L=j.cols.c.length; C={len:L};
  for(const k of j.dims) C[k]=(k==='m'?Uint16Array:Uint8Array).from(j.cols[k]);
  for(const k of j.meas) C[k]=Uint32Array.from(j.cols[k]);
  MU=j.MU; MN=j.MN; YS=j.YS; if(j.RG){RG=j.RG;RSIN=RG.length-1} if(j.ES)EST=j.ES;
  MDEP=Uint8Array.from(MU.map(m=>parseInt(m.slice(0,2),10)-1));
  MUNI_BY_DEP=DEPS.map(()=>[]); MU.forEach((m,i)=>MUNI_BY_DEP[MDEP[i]].push(i));
  TOTAL_NAC=0; for(let i=0;i<L;i++)TOTAL_NAC+=C.c[i];
}

/* ---------- filters ---------- */
const F={dep:null,mun:null,y:null,e:null,r:null,n:null,v:null,s:null,f:null,a:null,am:null,t:null};
const FDEF=[
 ['dep','Departamento',()=>DEPS.map((d,i)=>[i,d]).sort((a,b)=>a[1].localeCompare(b[1]))],
 ['mun','Municipio',()=>F.dep==null?[]:MUNI_BY_DEP[F.dep].map(i=>[i,MN[i]]).sort((a,b)=>a[1].localeCompare(b[1]))],
 ['y','Año de solicitud',()=>YS.map((y,i)=>[i,y]).reverse()],
 ['e','Estado actual',()=>EST.map((e,i)=>[i,e])],
 ['r','Tipo de resolución',()=>RG.map((e,i)=>[i,e])],
 ['n','Situación de nómina',()=>NOM.map((e,i)=>[i,e])],
 ['v','Resultado de visita',()=>VIS.map((e,i)=>[i,e])],
 ['s','Suspendido',()=>[[1,'Con suspensión registrada'],[0,'Sin suspensión']]],
 ['f','Fallecido',()=>[[1,'Con fallecimiento registrado'],[0,'Sin fallecimiento']]],
 ['a','Rango de edad',()=>AB.map((e,i)=>[i,e+' años'])]
];
const FLABEL={t:v=>'Antigüedad: '+ANT[v],am:v=>'Edad: '+[65,70,75,80,85,90][v]+' años o más'};
function flabel(k,v){ if(FLABEL[k])return FLABEL[k](v); const d=FDEF.find(x=>x[0]===k); const o=d[2]().find(x=>x[0]===v); return d[1]+': '+(o?o[1]:v)}
function setF(k,v,{toggle=true}={}){
  if(toggle&&F[k]===v) v=null;
  F[k]=v; if(k==='dep'&&F.mun!=null&&(v==null||MDEP[F.mun]!==v)) F.mun=null;
  if(k==='mun'&&v!=null) F.dep=MDEP[v];
  render();
}
function clearF(){for(const k in F)F[k]=null;render()}
function ok(i,ex){
  if(ex!=='geo'){ if(F.dep!=null&&MDEP[C.m[i]]!==F.dep)return false; if(F.mun!=null&&C.m[i]!==F.mun)return false; }
  if(F.y!=null&&C.y[i]!==F.y)return false;
  if(F.e!=null&&ex!=='e'&&C.e[i]!==F.e)return false;
  if(F.r!=null&&ex!=='r'&&C.r[i]!==F.r)return false;
  if(F.n!=null&&C.n[i]!==F.n)return false;
  if(F.v!=null&&C.v[i]!==F.v)return false;
  if(F.s!=null&&C.s[i]!==F.s)return false;
  if(F.f!=null&&C.f[i]!==F.f)return false;
  if(F.a!=null&&ex!=='a'&&C.a[i]!==F.a)return false;
  if(F.am!=null&&ex!=='a'&&C.a[i]<F.am)return false;
  if(F.t!=null&&ex!=='t'&&C.t[i]!==F.t)return false;
  return true;
}
const acc=()=>({c:0,p:0,nom:0,tram:0,vp:0,vn:0,susp:0,fall:0});
function add(a,i){const c=C.c[i];a.c+=c;a.p+=C.p[i];if(C.n[i]===1)a.nom+=c;const e=C.e[i];if(e===1)a.tram+=c;else if(e===4)a.susp+=c;const v=C.v[i];if(v===1)a.vp+=c;else if(v===2)a.vn+=c;if(C.f[i])a.fall+=c;}
let AG=null;
function compute(){
  if(!C)return;
  const L=C.len, T=acc(), byE=new Array(9).fill(0), byR=new Array(RG.length).fill(0), byA=AB.map(()=>({c:0,nom:0})), byT=new Array(7).fill(0);
  const byDep=DEPS.map(acc), byMun=new Map(), geoDep=DEPS.map(acc), geoMun=new Map();
  const fl={c:0,vis:0,vp:0,rp:0,res:0,ing:0,act:0};const wait={vis:0,res:0,nom:0};
  const al={qa:0,qs:0,old:0,nov:0,rpn:0,vpr:0,qu:0,qx:0,qd:0,qf:0,qe:0,qm:0,susp:0};
  for(let i=0;i<L;i++){
    const c=C.c[i];
    if(ok(i,'geo')){const d=MDEP[C.m[i]];add(geoDep[d],i);let g=geoMun.get(C.m[i]);if(!g){g=acc();geoMun.set(C.m[i],g)}add(g,i)}
    if(ok(i,'e'))byE[C.e[i]]+=c;
    if(ok(i,'r'))byR[C.r[i]]+=c;
    if(ok(i,'a')){const b=byA[C.a[i]];b.c+=c;if(C.n[i]===1)b.nom+=c}
    if(ok(i,'t')&&C.t[i])byT[C.t[i]]+=c;
    if(!ok(i,''))continue;
    add(T,i);const d=MDEP[C.m[i]];add(byDep[d],i);let g=byMun.get(C.m[i]);if(!g){g=acc();byMun.set(C.m[i],g)}add(g,i);
    const v=C.v[i],r=C.r[i],n=C.n[i],e=C.e[i];
    fl.c+=c;if(v)fl.vis+=c;if(r!==RSIN)fl.res+=c;if(e===1){if(v===0)wait.vis+=c;else if(r===RSIN)wait.res+=c;if(r===0&&n===0)wait.nom+=c}if(v===1)fl.vp+=c;if(r===0)fl.rp+=c;if(n)fl.ing+=c;if(n===1)fl.act+=c;
    al.qa+=C.qa[i];al.qs+=C.qs[i];al.qu+=C.qu[i];al.qx+=C.qx[i];al.qd+=C.qd[i];al.qf+=C.qf[i];al.qe+=C.qe[i];al.qm+=C.qm[i];
    if(C.t[i]===6)al.old+=c; if(e===1&&v===0)al.nov+=c; if(r===0&&n===0)al.rpn+=c; if(v===1&&r===RSIN)al.vpr+=c; if(e===4)al.susp+=c;
  }
  AG={wait,T,byE,byR,byA,byT,byDep,byMun,geoDep,geoMun,fl,al};
}

/* ---------- series ---------- */
const SER=[['sol','Solicitudes recibidas'],['vpos','Visitas positivas'],['vneg','Visitas negativas'],['res','Resoluciones emitidas'],['ing','Ingresos a nómina'],['sal','Salidas de nómina'],['susp','Suspensiones'],['fall','Fallecimientos registrados']];
function seriesYear(keys){
  const out={};const ny=ANIO-S.Y0+1;
  for(const k of keys){const a=new Array(ny).fill(0);for(const [m,e,y,v] of S.yr[k]){if(F.dep!=null&&MDEP[m]!==F.dep)continue;if(F.mun!=null&&m!==F.mun)continue;if(F.e!=null&&e!==F.e)continue;a[y]+=v}out[k]=a}
  return out;
}
function seriesMonth(keys){
  const out={};const nm=(ANIO-2018)*12+MES+1;
  for(const k of keys){const a=new Array(nm).fill(0);for(const [d,e,mi,v] of S.mo[k]){if(F.dep!=null&&d!==F.dep)continue;if(F.e!=null&&e!==F.e)continue;a[mi]+=v}out[k]=a}
  return out;
}
function ytd(k,year,months=MESC){const base=(year-2018)*12;let s=0;for(const [d,e,mi,v] of S.mo[k]){if(F.dep!=null&&d!==F.dep)continue;if(F.e!=null&&e!==F.e)continue;if(mi>=base&&mi<base+months)s+=v}return s}
/* ---------- alert rules ---------- */
const RULES=[
 {k:'qa',t:'Fallecidos que continúan activos en nómina',d:'Fecha de fallecimiento registrada y estado EN NÓMINA ACTIVA',p:'c',icon:'alert',pred:p=>p.q.qa},
 {k:'qs',t:'Suspendidos que continúan activos en nómina',d:'Fecha de suspensión registrada y estado EN NÓMINA ACTIVA',p:'h',icon:'pause',pred:p=>p.q.qs},
 {k:'old',t:'Solicitudes en trámite con más de 365 días',d:'Sin resolución final un año después de la solicitud',p:'h',icon:'clock',pred:p=>p.t===6},
 {k:'nov',t:'Solicitudes en trámite sin visita',d:'Expediente en trámite sin visita positiva ni negativa',p:'h',icon:'pin',pred:p=>p.e===1&&p.v===0},
 {k:'rpn',t:'Resolución positiva sin ingreso a nómina',d:'La resolución más reciente es positiva y nunca ingresó a nómina',p:'m',icon:'wallet',pred:p=>p.r===0&&p.n===0},
 {k:'vpr',t:'Visita positiva sin resolución',d:'Visita positiva registrada y ninguna resolución emitida',p:'m',icon:'stamp',pred:p=>p.v===1&&p.r===8},
 {k:'qu',t:'DPI registrado en más de un expediente',d:'El mismo CUI aparece en 2 a 4 registros',p:'m',icon:'copy',pred:p=>p.q.qu},
 {k:'qx',t:'Número de expediente duplicado',d:'El mismo número de expediente en registros distintos',p:'m',icon:'file',pred:p=>p.q.qx},
 {k:'qf',t:'Fechas inconsistentes',d:'Visita, resolución, nómina o fallecimiento antes de la solicitud, o fechas futuras',p:'m',icon:'clock',pred:p=>p.q.qf},
 {k:'qd',t:'Documento sin CUI válido',d:'Cédula antigua (número corto) o CUI con dígito verificador incorrecto',p:'l',icon:'shield',pred:p=>p.q.qd},
 {k:'qe',t:'Edad fuera de rango',d:'Más de 110 años, menos de 60 al solicitar o sin fecha de nacimiento',p:'l',icon:'users',pred:p=>p.q.qe},
 {k:'qm',t:'Sin teléfono de contacto',d:'Dificulta programar visitas y notificar resoluciones',p:'l',icon:'info',pred:p=>p.q.qm}
];
const PRN={c:'Crítica',h:'Alta',m:'Media',l:'Baja'};
const PRC={c:'var(--crit)',h:'var(--warnf)',m:'var(--blue5)',l:'var(--off)'};
/* ---------- UI state ---------- */
const UI={view:'dashboard',mapMetric:'c',evoMode:'y',evoOn:new Set(['sol','vpos','ing','fall']),cohort:'rec',terrSort:['c',-1],terrOpen:new Set(),bq:'',bRule:null,page:0,bSort:['fsol',-1],role:'SUP',rep:0,exports:[]};
const METRICS=[['c','Registros'],['p','Personas'],['nom','En nómina'],['tram','Pendientes (en trámite)'],['susp','Suspendidos'],['fall','Fallecidos'],['vp','Visitas positivas'],['vn','Visitas negativas']];
const tt=$('#tt');
function showTT(h,x,y){tt.innerHTML=h;tt.hidden=false;const w=tt.offsetWidth,hh=tt.offsetHeight;let L=x+14,T=y+14;if(L+w>innerWidth-8)L=x-w-14;if(T+hh>innerHeight-8)T=y-hh-14;tt.style.left=Math.max(8,L)+'px';tt.style.top=Math.max(8,T)+'px'}
function hideTT(){tt.hidden=true}
function toast(m){const t=document.createElement('div');t.className='toast';t.textContent=m;document.body.appendChild(t);setTimeout(()=>t.remove(),2600)}
const HOOKS=[];const hook=f=>HOOKS.push(f);
const scopeName=()=>{if(F.mun!=null)return `${MN[F.mun]}, ${DEPS[F.dep]}`;if(F.dep!=null)return DEPS[F.dep];
  const u=typeof api!=='undefined'&&api.usuario();if(u&&u.municipio_id&&MU&&MU.length===1)return `${MN[0]}, ${DEPS[MDEP[0]]}`;if(u&&u.departamento_id)return DEPS[u.departamento_id-1];return 'Nacional'};
const norm=s=>String(s).normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase().replace(/\s+/g,' ').trim();
