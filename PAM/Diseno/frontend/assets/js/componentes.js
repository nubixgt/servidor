/* ---------- KPI ---------- */
function delta(cur,prev,good='up'){if(!prev)return '';const d=(cur-prev)/prev*100;const up=d>=0;const cls=(good==='nt'||Math.abs(d)<.5)?'nt':(up===(good==='up')?'up':(good==='warn'?'warnup':'dn'));return `<span class="delta ${cls}">${up?'▲':'▼'} ${Math.abs(d).toFixed(1)}%</span>`}
function estadoCard(){
  const tot=AG.byE.reduce((a,b)=>a+b,0);const order=EST.map((e,i)=>i).sort((a,b)=>AG.byE[b]-AG.byE[a]);const max=Math.max(...AG.byE,1);
  hook(()=>document.querySelectorAll('[data-est]').forEach(r=>{r.onclick=()=>setF('e',+r.dataset.est);r.onkeydown=e=>{if(e.key==='Enter')setF('e',+r.dataset.est)}}));
  return `<div class="card"><div class="ch"><div><h2>Estado actual de los expedientes</h2><p>${fmt(tot)} registros · clic en un estado para filtrar todo el tablero</p></div></div>
  <div class="hb" role="list">${order.map(i=>`<div class="row ${F.e!=null&&F.e!==i?'dim':''}" role="listitem" tabindex="0" data-est="${i}"><span class="lab" style="color:var(--ink)">${ic(EST_ICON[i],15).replace('<svg','<svg style="color:'+stc(i)+'"')} ${EST[i]}</span><span class="trk"><span class="fill" style="display:block;width:${AG.byE[i]/max*100}%;background:${stc(i)}"></span></span><span class="v">${fmt(AG.byE[i])}</span><span class="p">${pc(AG.byE[i],tot)}%</span></div>`).join('')}</div></div>`;
}

/* ---------- Mapa ---------- */
let BBOX={};
function bboxOf(p){let x0=1e9,y0=1e9,x1=-1e9,y1=-1e9;const nums=p.match(/-?\d+\.?\d*/g).map(Number);for(let i=0;i<nums.length;i+=2){const x=nums[i],y=nums[i+1];if(x<x0)x0=x;if(x>x1)x1=x;if(y<y0)y0=y;if(y>y1)y1=y}return [x0,y0,x1,y1]}
function quantBreaks(vals){const s=[...vals].sort((a,b)=>a-b);return [.2,.4,.6,.8].map(q=>s[Math.floor(q*(s.length-1))])}
function mapCard(big){
  const met=UI.mapMetric, mlab=METRICS.find(m=>m[0]===met)[1];
  const vals=AG.geoDep.map(a=>a[met]);const br=quantBreaks(vals);const cls=v=>v<=br[0]?1:v<=br[1]?2:v<=br[2]?3:v<=br[3]?4:5;
  let vb=`0 0 ${G.W} ${G.H}`;
  if(F.dep!=null){const b=BBOX[F.dep+1];const pad=Math.max(b[2]-b[0],b[3]-b[1])*.35;vb=`${b[0]-pad} ${b[1]-pad} ${b[2]-b[0]+2*pad} ${b[3]-b[1]+2*pad}`}
  const paths=Object.entries(G.paths).map(([code,p])=>{const d=+code-1;return `<path d="${p}" data-dep="${d}" fill="var(--seq${cls(vals[d])})" class="${F.dep===d?'sel':F.dep!=null?'fade':''}" tabindex="0" aria-label="${DEPS[d]}: ${fmt(vals[d])} ${mlab}"></path>`}).join('');
  let rankItems;
  if(F.dep==null){rankItems=DEPS.map((d,i)=>({i,n:d,v:vals[i],kind:'dep'}))}
  else{rankItems=MUNI_BY_DEP[F.dep].map(m=>({i:m,n:MN[m],v:(AG.geoMun.get(m)||acc())[met],kind:'mun'}))}
  if(MU.length<340)rankItems=rankItems.filter(r=>r.v>0||(r.kind==='dep'&&MUNI_BY_DEP[r.i].length));
  rankItems.sort((a,b)=>b.v-a.v);const rmax=Math.max(1,...rankItems.map(r=>r.v));
  hook(()=>{
    document.querySelectorAll('.mapsvg path').forEach(p=>{const d=+p.dataset.dep;
      p.onmousemove=e=>{const a=AG.geoDep[d];showTT(`<h4>${DEPS[d]}</h4>${[['Personas',a.p],['Solicitudes (registros)',a.c],['En nómina',a.nom],['Pendientes',a.tram],['Suspendidos',a.susp],['Fallecidos',a.fall],['Visitas positivas',a.vp]].map(([l,v])=>`<div class="r"><span>${l}</span><b>${fmt(v)}</b></div>`).join('')}<div class="r" style="margin-top:4px"><span>Clic para ver municipios</span></div>`,e.clientX,e.clientY)};
      p.onmouseleave=hideTT;p.onclick=()=>{hideTT();setF('dep',d)};p.onkeydown=e=>{if(e.key==='Enter')setF('dep',d)}});
    document.querySelectorAll('.rank button').forEach(b=>b.onclick=()=>b.dataset.kind==='dep'?setF('dep',+b.dataset.i):setF('mun',+b.dataset.i,{toggle:true}));
    const s=document.getElementById('mapMetric');if(s)s.onchange=e=>{UI.mapMetric=e.target.value;render()};
    document.querySelectorAll('[data-crumb]').forEach(b=>b.onclick=()=>{const c=b.dataset.crumb;if(c==='gt'){F.dep=null;F.mun=null;render()}else{F.mun=null;render()}});
  });
  const crumbs=`<div class="crumbs"><button data-crumb="gt">Guatemala</button>${F.dep!=null?`› ${F.mun!=null?`<button data-crumb="dep">${DEPS[F.dep]}</button> › <span class="cur">${MN[F.mun]}</span>`:`<span class="cur">${DEPS[F.dep]}</span>`}`:''}</div>`;
  return `<div class="card"><div class="ch"><div><h2>Distribución territorial</h2>${crumbs}</div><div class="tools"><label class="note" for="mapMetric">Visualizar por</label><select class="mini-sel" id="mapMetric">${METRICS.map(([k,l])=>`<option value="${k}" ${k===met?'selected':''}>${l}</option>`).join('')}</select></div></div>
  <div class="mapwrap"><div><svg class="mapsvg" viewBox="${vb}" role="img" aria-label="Mapa de Guatemala por departamento, coloreado por ${mlab}" style="${big?'max-height:560px':''}">${paths}</svg>
  <div class="legend"><span>${mlab}:</span>${[1,2,3,4,5].map((c,i)=>`<i style="background:var(--seq${c})" title="${i===0?'≤ '+fmt(br[0]):i===4?'> '+fmt(br[3]):fmt(br[i-1])+' – '+fmt(br[i])}"></i>`).join('')}<span>${fmt(Math.min(...vals))} – ${fmt(Math.max(...vals))}</span></div></div>
  <div><div class="note" style="margin-bottom:8px"><b>${F.dep==null?'Departamentos':'Municipios de '+DEPS[F.dep]}</b> por ${mlab.toLowerCase()} · clic para ${F.dep==null?'ver municipios':'filtrar el municipio'}</div>
  <div class="rank">${rankItems.map((r,k)=>`<button data-kind="${r.kind}" data-i="${r.i}" style="${r.kind==='mun'&&F.mun===r.i?'background:var(--blue1)':''}"><span class="rn">${k+1}</span><span class="rb"><span>${esc(r.n)}</span><i style="width:${r.v/rmax*100}%"></i></span><span class="rv">${fmt(r.v)}</span></button>`).join('')}</div></div></div></div>`;
}

/* ---------- Flujo ---------- */
function antigCard(){
  const v=AG.byT.slice(1);const tot=v.reduce((a,b)=>a+b,0);const max=Math.max(1,...v);const col=['var(--seq2)','var(--seq3)','var(--seq4)','var(--warnf)','var(--warn)','var(--crit)'];
  hook(()=>{document.querySelectorAll('[data-ant]').forEach(b=>b.onclick=()=>setF('t',+b.dataset.ant));const g=document.getElementById('antGo');if(g)g.onclick=()=>{UI.bRule=null;go('beneficiarios')}});
  return `<div class="card"><div class="ch"><div><h2>Antigüedad de solicitudes pendientes</h2><p>${fmt(tot)} expedientes EN TRÁMITE · días desde la solicitud al corte ${CORTE}</p></div><button class="btn" id="antGo">Ver expedientes</button></div>
  <div class="cols">${v.map((x,i)=>`<button class="col ${F.t!=null&&F.t!==i+1?'dim':''}" data-ant="${i+1}" aria-label="${ANT[i+1]}: ${fmt(x)}"><span class="cv">${fmt(x)}</span><span class="cb" style="height:${x/max*120}px;background:${col[i]}"></span><span class="cl">${ANT[i+1].replace(' días','').replace('Más de ','> ')}<br><span style="color:var(--ink3)">${pc(x,tot,0)}%</span></span></button>`).join('')}</div>
  <p class="note" style="margin:10px 0 0">Días: 0–30 a más de 365. ${pc(v[5],tot,0)}% de los pendientes supera un año.</p></div>`;
}

/* ---------- Evolución ---------- */
const CH={};
function evoCard(keys=SER.map(s=>s[0]),title='Evolución histórica'){
  const mode=UI.evoMode;const ks=keys;let labels,data;
  if(mode==='y'){data=seriesYear(ks);labels=[];for(let y=S.Y0;y<=ANIO;y++)labels.push(String(y))}
  else{data=seriesMonth(ks);labels=[];const M=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];for(let y=2018;y<=ANIO;y++)for(let m=0;m<12;m++){if(y===ANIO&&m>MES)break;labels.push(M[m]+' '+String(y).slice(2))}}
  const series=ks.map(k=>{const si=SER.findIndex(s=>s[0]===k);return {k,l:SER[si][1],c:`var(--s${si+1})`,data:data[k],on:ks.length<=3||UI.evoOn.has(k)}});
  const id='evo'+Math.random().toString(36).slice(2,7);
  hook(()=>{document.querySelectorAll('[data-evm]').forEach(b=>b.onclick=()=>{UI.evoMode=b.dataset.evm;render()});document.querySelectorAll('[data-ser]').forEach(b=>b.onclick=()=>{const k=b.dataset.ser;UI.evoOn.has(k)?UI.evoOn.delete(k):UI.evoOn.add(k);if(!UI.evoOn.size)UI.evoOn.add(k);render()})});
  return `<div class="card"><div class="ch"><div><h2>${title}</h2><p>${scopeName()}${F.e!=null?' · '+EST[F.e]:''} · ${mode==='y'?`por año (${ANIO} parcial)`:`por mes, 2018–${ANIO}`}${mode==='m'&&F.mun!=null?' · mensual a nivel departamental':''}</p></div><div class="seg"><button data-evm="y" class="${mode==='y'?'on':''}">Año</button><button data-evm="m" class="${mode==='m'?'on':''}">Mes</button></div></div>
  ${ks.length>3?`<div class="leg" style="margin-bottom:8px">${series.map(s=>`<button data-ser="${s.k}" class="${s.on?'':'off'}" aria-pressed="${s.on}"><i style="background:${s.c}"></i>${s.l}</button>`).join('')}</div>`:`<div class="leg" style="margin-bottom:8px">${series.map(s=>`<span class="note" style="display:inline-flex;align-items:center;gap:6px;margin-right:10px"><i style="width:14px;height:3px;border-radius:2px;background:${s.c};display:block"></i>${s.l}</span>`).join('')}</div>`}
  <div class="chartbox">${lineChart(id,labels,series,{partial:true,aria:title})}</div></div>`;
}

/* ---------- Edad ---------- */
function ageCard(){
  const tot=AG.byA.reduce((a,b)=>a+b.c,0);const max=Math.max(1,...AG.byA.map(b=>b.c));
  hook(()=>document.querySelectorAll('[data-age]').forEach(r=>{r.onclick=()=>setF('a',+r.dataset.age);r.onkeydown=e=>{if(e.key==='Enter')setF('a',+r.dataset.age)}}));
  return `<div class="card"><div class="ch"><div><h2>Distribución por edad</h2><p>Edad calculada desde la fecha de nacimiento al corte · verde: en nómina activa</p></div></div>
  <div class="hb">${AG.byA.map((b,i)=>`<div class="row ${F.a!=null&&F.a!==i?'dim':''}" tabindex="0" data-age="${i}"><span class="lab">${AB[i]} años</span><span class="trk" style="display:flex;gap:2px;background:none"><span class="fill" style="display:block;width:${b.nom/max*100}%;background:var(--ok);border-radius:0"></span><span class="fill" style="display:block;width:${(b.c-b.nom)/max*100}%;background:var(--seq2)"></span></span><span class="v">${fmt(b.c)}</span><span class="p">${pc(b.c,tot)}%</span></div>`).join('')}</div>
  <div class="legend" style="margin-top:12px"><i style="background:var(--ok)"></i>En nómina activa <i style="background:var(--seq2);margin-left:10px"></i>Otros estados</div></div>`;
}

/* ---------- Alertas ---------- */
function alertRows(limit){
  const rows=RULES.map(r=>({...r,n:AG.al[r.k]})).filter(r=>r.n>0);
  hook(()=>document.querySelectorAll('[data-rule]').forEach(b=>b.onclick=()=>{UI.bRule=b.dataset.rule;UI.page=0;go('beneficiarios')}));
  return `<div class="alist">${rows.slice(0,limit||99).map(r=>`<div class="al"><span class="band" style="background:${PRC[r.p]}"></span><span class="ai" style="color:${PRC[r.p]};background:color-mix(in srgb,${PRC[r.p]} 14%,transparent)">${ic(r.icon)}</span><div><div class="at">${r.t}<span class="pr ${r.p}">${PRN[r.p]}</span></div><div class="ad">${r.d} · actualizado ${CORTE}</div></div><div class="ac">${fmt(r.n)}</div><button class="btn" data-rule="${r.k}">Ver casos</button></div>`).join('')}</div>`;
}
function alertsCard(){return `<div class="card"><div class="ch"><div><h2>Requieren atención</h2><p>Reglas automáticas sobre los datos filtrados · ${scopeName()}</p></div><button class="btn" data-go="alertas">Todas las alertas</button></div>${alertRows(6)}</div>`}

/* ---------- Tabla territorial ---------- */
function terrCard(){
  const cols=[['p','Personas'],['c','Solicitudes'],['nom','En nómina'],['tram','Pendientes'],['vp','Visitas +'],['vn','Visitas −'],['susp','Suspendidos'],['fall','Fallecidos']];
  const [sk,sd]=UI.terrSort;let deps=DEPS.map((n,i)=>({i,n,a:AG.byDep[i]})).filter(d=>d.a.c>0);
  deps.sort((a,b)=>sk==='n'?a.n.localeCompare(b.n)*sd:(a.a[sk]-b.a[sk])*sd);
  if(F.dep!=null)UI.terrOpen.add(F.dep);
  hook(()=>{document.querySelectorAll('[data-ts]').forEach(b=>b.onclick=()=>{const k=b.dataset.ts;UI.terrSort=[k,UI.terrSort[0]===k?-UI.terrSort[1]:(k==='n'?1:-1)];render()});
    document.querySelectorAll('[data-tog]').forEach(b=>b.onclick=()=>{const d=+b.dataset.tog;UI.terrOpen.has(d)?UI.terrOpen.delete(d):UI.terrOpen.add(d);render()});
    document.querySelectorAll('[data-tmun]').forEach(b=>b.onclick=()=>setF('mun',+b.dataset.tmun))});
  const arrow=k=>UI.terrSort[0]===k?`<span class="so">${UI.terrSort[1]<0?'▼':'▲'}</span>`:'';
  const T=AG.T;
  let body='';for(const d of deps){const open=UI.terrOpen.has(d.i);body+=`<tr><td><button class="tog ${open?'open':''}" data-tog="${d.i}" aria-expanded="${open}">${ic('chev',14)}${d.n}</button></td>${cols.map(([k])=>`<td class="n">${fmt(d.a[k])}</td>`).join('')}</tr>`;
    if(open){const ms=MUNI_BY_DEP[d.i].map(m=>({m,a:AG.byMun.get(m)})).filter(x=>x.a);ms.sort((a,b)=>sk==='n'?MN[a.m].localeCompare(MN[b.m])*sd:(a.a[sk]-b.a[sk])*sd);
      body+=ms.map(x=>`<tr class="sub"><td><button class="link" data-tmun="${x.m}">${esc(MN[x.m])}</button></td>${cols.map(([k])=>`<td class="n">${fmt(x.a[k])}</td>`).join('')}</tr>`).join('')}}
  return `<div class="card"><div class="ch"><div><h2>Análisis territorial</h2><p>Departamentos y municipios · clic en el encabezado para ordenar, en el departamento para expandir</p></div></div>
  <div class="tw" style="max-height:520px"><table><thead><tr><th><button data-ts="n">Departamento ${arrow('n')}</button></th>${cols.map(([k,l])=>`<th class="n"><button data-ts="${k}">${l} ${arrow(k)}</button></th>`).join('')}</tr></thead><tbody>${body}</tbody>
  <tfoot><tr><td><b>Total ${scopeName()}</b></td>${cols.map(([k])=>`<td class="n"><b>${fmt(T[k])}</b></td>`).join('')}</tr></tfoot></table></div></div>`;
}

/* ---------- Resoluciones ---------- */
function resCard(){
  const tot=AG.byR.reduce((a,b)=>a+b,0);const order=RG.map((r,i)=>i).sort((a,b)=>AG.byR[b]-AG.byR[a]);const max=Math.max(1,...AG.byR);
  hook(()=>document.querySelectorAll('[data-res]').forEach(r=>r.onclick=()=>setF('r',+r.dataset.res)));
  return `<div class="card"><div class="ch"><div><h2>Última resolución por familia</h2><p>47 códigos originales agrupados en 9 familias</p></div></div>
  <div class="hb">${order.map(i=>`<div class="row ${F.r!=null&&F.r!==i?'dim':''}" tabindex="0" data-res="${i}"><span class="lab" title="${RG[i]}">${RG[i]}</span><span class="trk"><span class="fill" style="display:block;width:${AG.byR[i]/max*100}%;background:${i===RSIN?'var(--off)':'var(--seq4)'}"></span></span><span class="v">${fmt(AG.byR[i])}</span><span class="p">${pc(AG.byR[i],tot)}%</span></div>`).join('')}</div></div>`;
}
/* =====================================================================
   v2 · componentes rediseñados (sobrescriben a los de la versión 1)
   ===================================================================== */
const PREV={};
const RM=matchMedia('(prefers-reduced-motion: reduce)');
function countUps(){
  document.querySelectorAll('[data-count]').forEach(el=>{
    const to=+el.dataset.count,key=el.dataset.ck,from=PREV[key]??(key==='hero'?0:to*.6);PREV[key]=to;
    if(RM.matches||from===to){el.textContent=fmt(to);return}
    const t0=performance.now(),d=750;
    const step=t=>{const k=Math.min(1,(t-t0)/d),e=1-Math.pow(1-k,3);el.textContent=fmt(from+(to-from)*e);if(k<1)requestAnimationFrame(step)};
    requestAnimationFrame(step);
  });
}
function spark(data,tone,id){
  const W=160,H=34,n=data.length,mx=Math.max(1,...data);
  const x=i=>i/(n-1)*W,y=v=>H-2-(v/mx)*(H-6);
  const d=data.map((v,i)=>`${i?'L':'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join('');
  return `<svg class="spark" viewBox="0 0 ${W} ${H}" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="${id}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${tone}" stop-opacity=".45"/><stop offset="1" stop-color="${tone}" stop-opacity="0"/></linearGradient></defs><path d="${d}L${W},${H}L0,${H}Z" fill="url(#${id})"/><path d="${d}" fill="none" stroke="${tone}" stroke-width="1.6" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg>`;
}
function ring(p,tone){const r=26,c=2*Math.PI*r;return `<svg width="68" height="68" viewBox="0 0 68 68" aria-hidden="true"><circle cx="34" cy="34" r="${r}" fill="none" stroke="color-mix(in srgb,var(--ink3) 16%,transparent)" stroke-width="8"/><circle cx="34" cy="34" r="${r}" fill="none" stroke="${tone}" stroke-width="8" stroke-linecap="round" stroke-dasharray="${c}" stroke-dashoffset="${c*(1-p)}" transform="rotate(-90 34 34)" style="transition:stroke-dashoffset .8s cubic-bezier(.2,.7,.2,1)"/></svg>`}

function kpiRow(){
  const T=AG.T;const Y=seriesYear(['sol','ing','vpos','vneg','susp','fall']);
  let acc=0;const cum=Y.sol.map(v=>acc+=v);
  const s26=ytd('sol',ANIO),s25=ytd('sol',ANIO-1),i26=ytd('ing',ANIO),i25=ytd('ing',ANIO-1),vp26=ytd('vpos',ANIO),vp25=ytd('vpos',ANIO-1),vn26=ytd('vneg',ANIO),vn25=ytd('vneg',ANIO-1),f26=ytd('fall',ANIO),f25=ytd('fall',ANIO-1);
  const nDep=AG.byDep.filter(a=>a.c).length,nMun=AG.byMun.size;
  const K=[
   {k:'nom',t:'En nómina',i:'wallet',tone:'var(--ok)',v:T.nom,d:`Ingresos ${PERIODO} ${ANIO}: ${fmt(i26)} ${delta(i26,i25)}`,sp:Y.ing,click:()=>setF('n',1)},
   {k:'sol',t:'Solicitudes',i:'file',tone:'var(--s1)',v:T.c,d:`${ANIO} ${PERIODO}: ${fmt(s26)} ${delta(s26,s25,'nt')}`,sp:Y.sol,click:()=>setF('e',1)},
   {k:'vp',t:'Visitas positivas',i:'check',tone:'var(--s3)',v:T.vp,d:`${ANIO} ${PERIODO}: ${fmt(vp26)} ${delta(vp26,vp25)}`,sp:Y.vpos,click:()=>setF('v',1)},
   {k:'vn',t:'Visitas negativas',i:'x',tone:'var(--s2)',v:T.vn,d:`${ANIO} ${PERIODO}: ${fmt(vn26)} ${delta(vn26,vn25,'dn')}`,sp:Y.vneg,click:()=>setF('v',2)},
   {k:'c',t:'Total de registros',i:'db',tone:'var(--s7)',v:T.c,d:`${fmt(T.c-T.p)} de personas repetidas`,sp:cum,click:null},
   {k:'susp',t:'Suspendidos',i:'pause',tone:'var(--st4)',v:T.susp,d:`${fmt(AG.byE[3])} bloqueados además`,sp:Y.susp,click:()=>setF('e',4)},
   {k:'fall',t:'Fallecidos',i:'minus',tone:'var(--dead)',v:T.fall,d:`${ANIO} ${PERIODO}: ${fmt(f26)} ${delta(f26,f25,'dn')}`,sp:Y.fall,click:()=>setF('f',1)}
  ];
  const on={nom:F.n===1,sol:F.e===1,vp:F.v===1,vn:F.v===2,susp:F.e===4,fall:F.f===1};
  const tot=AG.byE.reduce((a,b)=>a+b,0)||1;const order=EST.map((e,i)=>i).sort((a,b)=>AG.byE[b]-AG.byE[a]);
  hook(()=>{document.querySelectorAll('.kpi[data-k]').forEach(b=>{const k=K.find(x=>x.k===b.dataset.k);if(k&&k.click)b.onclick=k.click;else b.style.cursor='default'});
    document.querySelectorAll('[data-hst]').forEach(b=>{b.onclick=()=>setF('e',+b.dataset.hst);b.onmousemove=e=>{const i=+b.dataset.hst;showTT(`<h4>${EST[i]}</h4><div class="r"><span>Registros</span><b>${fmt(AG.byE[i])}</b></div><div class="r"><span>Participación</span><b>${pc(AG.byE[i],tot)}%</b></div>`,e.clientX,e.clientY)};b.onmouseleave=hideTT});countUps()});
  const act=pc(T.nom,T.c,1);
  return `<section class="kpis" aria-label="Indicadores principales">
   <div class="hero"><span class="orb"></span><img class="wm" src="${EMBLEM_C}" alt=""><div class="kt">Personas únicas<span class="ki" style="width:32px;height:32px;border-radius:10px;display:grid;place-items:center;background:var(--blue1);color:var(--blue7)">${ic('users')}</span></div>
    <div class="big" data-count="${T.p}" data-ck="hero">${fmt(T.p)}</div>
    <div class="sub">Identificadas en <b>${fmt(T.c)}</b> registros · <b>${nDep}</b> departamentos · <b>${fmt(nMun)}</b> municipios · ${scopeName()}</div>
    <div><div class="note" style="margin-bottom:6px">Composición por estado actual · clic para filtrar</div>
    <div class="stackbar">${order.filter(i=>AG.byE[i]).map(i=>`<button data-hst="${i}" aria-label="${EST[i]}: ${fmt(AG.byE[i])}" style="flex-grow:${AG.byE[i]};background:${stc(i)};${F.e!=null&&F.e!==i?'opacity:.3':''}"></button>`).join('')}</div></div>
    <div class="stackleg">${order.slice(0,6).map(i=>`<span><i style="background:${stc(i)}"></i>${EST[i]}<b>${pc(AG.byE[i],tot,0)}%</b></span>`).join('')}</div></div>
   ${K.map(k=>`<button class="kpi ${on[k.k]?'on':''}" data-k="${k.k}" style="--tone:${k.tone}" ${k.click?'':'tabindex="-1"'}><span class="kt">${k.t}<span class="ki">${ic(k.i,15)}</span></span><span class="kv" data-count="${k.v}" data-ck="${k.k}">${fmt(k.v)}</span><span class="kd">${k.d}</span>${spark(k.sp,k.tone,'sp'+k.k)}</button>`).join('')}
   <div class="kpi" style="cursor:default"><span class="kt">Cobertura activa<span class="ki" style="--tone:var(--ok)">${ic('pulse',15)}</span></span><div class="gauge">${ring(T.nom/Math.max(1,T.c),'var(--ok)')}<div><div class="gv">${act}%</div><div class="kd">de las solicitudes reciben hoy el aporte</div></div></div></div>
  </section>`;
}

/* flujo como corriente animada */
function flowCard(){
  const f=AG.fl,w=AG.wait,base=Math.max(1,f.c);
  const st=[['Solicitud',f.c,w.vis,'esperan visita'],['Visita',f.vis,w.res,'esperan resolución'],['Resolución',f.res,w.nom,'esperan ingreso a nómina'],['Ingreso a nómina',f.ing,null,''],['Activo hoy',f.act,null,'']];
  let neck=-1,mx=0;st.forEach((s,i)=>{if(s[2]!=null&&s[2]>mx){mx=s[2];neck=i}});
  const W=1000,H=230,mid=96,xs=st.map((_,i)=>(i+.5)*W/5),hs=st.map(s=>Math.max(8,s[1]/base*170));
  let bands='';for(let i=0;i<4;i++){const x0=xs[i]+6,x1=xs[i+1]-6,h0=hs[i]/2,h1=hs[i+1]/2,cx=(x0+x1)/2;
    bands+=`<path class="band" d="M${x0},${mid-h0} C${cx},${mid-h0} ${cx},${mid-h1} ${x1},${mid-h1} L${x1},${mid+h1} C${cx},${mid+h1} ${cx},${mid+h0} ${x0},${mid+h0}Z" fill="url(#pipeG)"/>`}
  const drips=st.map((s,i)=>s[2]?`<path d="M${xs[i]},${mid+hs[i]/2} C${xs[i]},${mid+hs[i]/2+20} ${xs[i]+40},${H-30} ${xs[i]+80},${H-10}" stroke="${i===neck?'var(--crit)':'var(--warnf)'}" stroke-width="${Math.max(3,s[2]/base*170)}" stroke-linecap="round" fill="none" opacity=".45"/><path class="loss" d="M${xs[i]},${mid+hs[i]/2} C${xs[i]},${mid+hs[i]/2+20} ${xs[i]+40},${H-30} ${xs[i]+80},${H-10}" ${i===neck?'style="stroke:var(--crit)"':''}/>`:'').join('');
  const nodes=xs.map((x,i)=>`<rect x="${x-6}" y="${mid-hs[i]/2}" width="12" height="${hs[i]}" rx="4" fill="var(--pipe2)"/>`).join('');
  return `<div class="card"><div class="ch"><div><h2>Flujo del programa</h2><p>Grosor = expedientes que alcanzaron la etapa · en ámbar, expedientes en trámite detenidos · ${scopeName()}</p></div></div>
  <div class="stream"><svg viewBox="0 0 ${W} ${H}" role="img" aria-label="Flujo del programa por etapa"><defs><linearGradient id="pipeG" x1="0" x2="1"><stop offset="0" stop-color="var(--pipe1)" stop-opacity=".55"/><stop offset="1" stop-color="var(--pipe2)" stop-opacity=".3"/></linearGradient></defs>${drips}${bands}${nodes}<path class="flowline" d="M${xs[0]},${mid} L${xs[4]},${mid}"/></svg></div>
  <div class="snodes">${st.map(([l,v,wt,wl],i)=>`<div class="snode ${i===neck?'neck':''}"><span class="sl">${l}</span>${i===neck?'<span class="tag">Cuello de botella</span>':''}<span class="sv">${fmt(v)}</span><span class="conv">${pc(v,base)}% de las solicitudes</span>${wt!=null?`<div class="wait"><b>${fmt(wt)}</b>${wl}</div>`:''}</div>`).join('')}</div>
  <p class="note" style="margin:12px 0 0">La matriz guarda solo la última visita y la última resolución; con el historial de eventos el flujo será exacto. ${fmt(f.ing-f.act)} expedientes salieron de la nómina.</p></div>`;
}

/* series con área y trazo animado */
function lineChart(id,labels,series,opts={}){
  const W=680,H=250,pl=52,pr=14,pt=14,pb=28;const n=labels.length;
  const on=series.filter(s=>s.on);const mx=Math.max(1,...on.flatMap(s=>s.data));
  const step=Math.pow(10,Math.floor(Math.log10(mx)));const nice=[1,2,2.5,5,10].map(k=>k*step).find(t=>mx/t<=5)||step*10;const top=Math.ceil(mx/nice)*nice;
  const x=i=>pl+(W-pl-pr)*(n===1?0:i/(n-1)),y=v=>pt+(H-pt-pb)*(1-v/top);
  let g='';for(let t=0;t<=top+1e-9;t+=nice){g+=`<line class="gl" x1="${pl}" x2="${W-pr}" y1="${y(t)}" y2="${y(t)}"/><text class="ax" x="${pl-8}" y="${y(t)+4}" text-anchor="end">${t>=1000?fmt(t/1000)+' k':fmt(t)}</text>`}
  const every=Math.ceil(n/12);labels.forEach((l,i)=>{if(i%every===0||i===n-1)g+=`<text class="ax" x="${x(i)}" y="${H-8}" text-anchor="middle">${l}</text>`});
  const defs=on.map((s,k)=>`<linearGradient id="${id}g${k}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="${s.c}" stop-opacity="${on.length>2?.14:.28}"/><stop offset="1" stop-color="${s.c}" stop-opacity="0"/></linearGradient>`).join('');
  const lines=on.map((s,k)=>{const d=s.data.map((v,i)=>`${i?'L':'M'}${x(i).toFixed(1)},${y(v).toFixed(1)}`).join('');const li=n-1;
    return `<path class="area" d="${d}L${x(li)},${y(0)}L${x(0)},${y(0)}Z" fill="url(#${id}g${k})"/><path class="ln ${RM.matches?'':'draw'}" pathLength="1" d="${d}" stroke="${s.c}" style="animation-delay:${k*.08}s"/><circle cx="${x(li)}" cy="${y(s.data[li])}" r="4.5" fill="${s.c}" stroke="var(--bg)" stroke-width="2"/>`}).join('');
  CH[id]={labels,series:on,x,y,W,H,pl,pr,pt,pb,n};
  hook(()=>{const svg=document.getElementById(id);if(!svg)return;const xh=svg.querySelector('.xh');const dots=svg.querySelector('.hd');
    svg.onmousemove=e=>{const r=svg.getBoundingClientRect();const px=(e.clientX-r.left)/r.width*W;const c=CH[id];let i=Math.round((px-pl)/((W-pl-pr)/(Math.max(1,n-1))));i=Math.max(0,Math.min(n-1,i));xh.setAttribute('x1',c.x(i));xh.setAttribute('x2',c.x(i));xh.style.display='';
      dots.innerHTML=c.series.map(s=>`<circle cx="${c.x(i)}" cy="${c.y(s.data[i])}" r="5" fill="${s.c}" stroke="var(--bg)" stroke-width="2"/>`).join('');
      showTT(`<h4>${labels[i]}${opts.partial&&i===n-1?' (parcial)':''}</h4>${c.series.map(s=>`<div class="r"><span><i style="background:${s.c}"></i>${s.l}</span><b>${fmt(s.data[i])}</b></div>`).join('')}`,e.clientX,e.clientY)};
    svg.onmouseleave=()=>{xh.style.display='none';dots.innerHTML='';hideTT()}});
  return `<svg id="${id}" class="lc" viewBox="0 0 ${W} ${H}" role="img" aria-label="${opts.aria||'Serie temporal'}"><defs>${defs}</defs>${g}<line class="xh" x1="0" x2="0" y1="${pt}" y2="${H-pb}" style="display:none"/>${lines}<g class="hd"></g></svg>`;
}

/* tiempos con barra de rango mediana → P90 */
function timesCard(){
  const sc=F.dep!=null?String(F.dep):'N';const tm=S.tm[UI.cohort];
  const tr=[['sv','Solicitud → Visita'],['vr','Visita → Resolución'],['rn','Resolución → Nómina'],['sn','Solicitud → Nómina']];
  hook(()=>document.querySelectorAll('[data-coh]').forEach(b=>b.onclick=()=>{UI.cohort=b.dataset.coh;render()}));
  const other=['y','e','r','n','v','s','f','a','am','t','mun'].some(k=>F[k]!=null);
  return `<div class="card"><div class="ch"><div><h2>Tiempos del proceso</h2><p>Días calendario · ${F.dep!=null?DEPS[F.dep]:'Nacional'} · la barra va de 0 al percentil 90; la marca es la mediana</p></div><div class="seg" role="group" aria-label="Cohorte"><button data-coh="rec" class="${UI.cohort==='rec'?'on':''}">Desde 2023</button><button data-coh="all" class="${UI.cohort==='all'?'on':''}">Todas</button></div></div>
  <div class="times">${tr.map(([k,l])=>{const v=tm[k][sc];return `<div class="tm"><div class="tml">${l}</div>${v?`<div class="tv">${fmt(v[2])}<small>días · mediana</small></div><div class="rng"><i style="left:0;width:100%"></i><b style="left:calc(${Math.min(100,v[2]/Math.max(1,v[3])*100)}% - 1px)"></b></div><div class="ts"><span>Media <b>${fmt(v[1])}</b></span><span>P90 <b>${fmt(v[3])}</b></span><span>${fmt(v[0])} exp.</span></div>`:'<div class="note">Sin datos</div>'}</div>`}).join('')}</div>
  ${other?`<p class="note" style="margin:10px 0 0">En el prototipo los tiempos responden al filtro de departamento. En producción responden a todos los filtros.</p>`:''}</div>`;
}

/* mapa: etiqueta, pulso y zoom animado */
let LASTVB=null;
const _mapCard=mapCard;
function mapCardV2(big){
  let h=_mapCard(big);
  if(F.dep!=null){const b=BBOX[F.dep+1];const cx=(b[0]+b[2])/2,cy=(b[1]+b[3])/2;
    h=h.replace('</svg>',`<circle class="ping" cx="${cx}" cy="${cy}" r="3"/><circle cx="${cx}" cy="${cy}" r="3.5" fill="#fff"/><text class="mlabel" x="${cx}" y="${cy-10}" text-anchor="middle">${DEPS[F.dep]}</text></svg>`)}
  hook(()=>{const svg=document.querySelector('.mapsvg');if(!svg)return;const to=svg.getAttribute('viewBox').split(' ').map(Number);
    if(LASTVB&&!RM.matches&&LASTVB.join()!==to.join()){const from=LASTVB.slice();svg.setAttribute('viewBox',from.join(' '));const t0=performance.now();
      const st=t=>{const k=Math.min(1,(t-t0)/550),e=k<.5?4*k*k*k:1-Math.pow(-2*k+2,3)/2;svg.setAttribute('viewBox',from.map((f,j)=>f+(to[j]-f)*e).join(' '));if(k<1)requestAnimationFrame(st)};requestAnimationFrame(st)}
    LASTVB=to;});
  return h;
}
/* ---------- filtros: principales + "Más filtros" ---------- */