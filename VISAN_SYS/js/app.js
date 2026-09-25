// ── HELPERS ──
const vcolor = c => c==='FFFF0000'?'var(--danger)':c==='FFFF8001'?'var(--warn-h)':'var(--warn-m)';
const vname  = c => c==='FFFF0000'?'Muy Alta':c==='FFFF8001'?'Alta':'Media';
const vbadge = c => c==='FFFF0000'?'risk-r':c==='FFFF8001'?'risk-o':'risk-y';
function fmtN(n){ if(!n&&n!==0)return '—'; return Number(n).toLocaleString('es-GT'); }
function fmtQ(n){ if(!n&&n!==0)return '—'; return 'Q ' + Number(n).toLocaleString('es-GT',{minimumFractionDigits:2,maximumFractionDigits:2}); }

// Presupuesto financiero por rubro (fuente: matriz de ejecución presupuestaria UDAFA).
const RX_PRESUPUESTO = {
  aa:  { presupuesto: 140000000,    ejecutado: 82771650 },
  apa: { presupuesto: 84654305,     ejecutado: 48641165.70 },
  res: { presupuesto: 31130243,     ejecutado: 24309000 },
  tot: { presupuesto: 395784548,    ejecutado: 238493465.70 }
};
function fmtD(s){
  if(!s)return '—';
  if(/^\d{4}-\d{2}-\d{2}$/.test(s)){const[y,m,d]=s.split('-');return `${d}/${m}/${y}`;}
  return s;
}
function sortD(s){
  if(!s)return '9999-99';
  if(/^\d{4}-\d{2}-\d{2}$/.test(s))return s;
  const mo={ENERO:'01',FEBRERO:'02',MARZO:'03',ABRIL:'04',MAYO:'05',JUNIO:'06',
    JULIO:'07',AGOSTO:'08',SEPTIEMBRE:'09',OCTUBRE:'10',NOVIEMBRE:'11',DICIEMBRE:'12'};
  const m=s.match(/(\w+) DE (\d{4})/);
  if(m&&mo[m[1]])return `${m[2]}-${mo[m[1]]}`;
  return '9998-00';
}

// Los datos (DATA) llegan del servidor ya actualizados: ver js/boot.js

// Lo programado solo cuenta si tiene fecha de programación (un texto como «SOLICITUD NUEVA» no es fecha).
const PROG_KEYS=['nda','mc','judicial','apa','reserva','insan'];
const tieneFechaProg=(r,k)=>/\d/.test(String(r['prog_'+k+'_fecha']||''));
const progOf=(r,k)=>tieneFechaProg(r,k)?(Number(r['prog_'+k])||0):0;

// ── AGGREGATIONS ──
const DEPTS=[...new Set(DATA.map(r=>r.departamento))].sort();
let T={
  ej_nda:DATA.reduce((s,r)=>s+(r.ej_nda||0),0),
  ej_mc:DATA.reduce((s,r)=>s+(r.ej_mc||0),0),
  ej_judicial:DATA.reduce((s,r)=>s+(r.ej_judicial||0),0),
  ej_apa:DATA.reduce((s,r)=>s+(r.ej_apa||0),0),
  ej_reserva:DATA.reduce((s,r)=>s+(r.ej_reserva||0),0),
  ej_insan:DATA.reduce((s,r)=>s+(r.ej_insan||0),0),
  prog_nda:DATA.reduce((s,r)=>s+progOf(r,'nda'),0),
  prog_mc:DATA.reduce((s,r)=>s+progOf(r,'mc'),0),
  prog_judicial:DATA.reduce((s,r)=>s+progOf(r,'judicial'),0),
  prog_apa:DATA.reduce((s,r)=>s+progOf(r,'apa'),0),
  prog_reserva:DATA.reduce((s,r)=>s+progOf(r,'reserva'),0),
  prog_insan:DATA.reduce((s,r)=>s+progOf(r,'insan'),0),
};
let TOT_EJ=Object.values({a:T.ej_nda,b:T.ej_mc,c:T.ej_judicial,d:T.ej_apa,e:T.ej_reserva,f:T.ej_insan}).reduce((s,v)=>s+v,0);
let TOT_PROG=Object.values({a:T.prog_nda,b:T.prog_mc,c:T.prog_judicial,d:T.prog_apa,e:T.prog_reserva,f:T.prog_insan}).reduce((s,v)=>s+v,0);
let TOTAL=TOT_EJ+TOT_PROG;
let ROJO=DATA.filter(r=>r.color==='FFFF0000').length;
let NARANJA=DATA.filter(r=>r.color==='FFFF8001').length;
let AMARILLO=DATA.filter(r=>r.color==='FFFFFF00').length;

function recalculateAll() {
  T.ej_nda = DATA.reduce((s, r) => s + (r.ej_nda || 0), 0);
  T.ej_mc = DATA.reduce((s, r) => s + (r.ej_mc || 0), 0);
  T.ej_judicial = DATA.reduce((s, r) => s + (r.ej_judicial || 0), 0);
  T.ej_apa = DATA.reduce((s, r) => s + (r.ej_apa || 0), 0);
  T.ej_reserva = DATA.reduce((s, r) => s + (r.ej_reserva || 0), 0);
  T.ej_insan = DATA.reduce((s, r) => s + (r.ej_insan || 0), 0);
  T.prog_nda = DATA.reduce((s,r)=>s+progOf(r,'nda'),0);
  T.prog_mc = DATA.reduce((s,r)=>s+progOf(r,'mc'),0);
  T.prog_judicial = DATA.reduce((s,r)=>s+progOf(r,'judicial'),0);
  T.prog_apa = DATA.reduce((s,r)=>s+progOf(r,'apa'),0);
  T.prog_reserva = DATA.reduce((s,r)=>s+progOf(r,'reserva'),0);
  T.prog_insan = DATA.reduce((s,r)=>s+progOf(r,'insan'),0);

  TOT_EJ = T.ej_nda + T.ej_mc + T.ej_judicial + T.ej_apa + T.ej_reserva + T.ej_insan;
  TOT_PROG = T.prog_nda + T.prog_mc + T.prog_judicial + T.prog_apa + T.prog_reserva + T.prog_insan;
  TOTAL = TOT_EJ + TOT_PROG;
  ROJO = DATA.filter(r => r.color === 'FFFF0000').length;
  NARANJA = DATA.filter(r => r.color === 'FFFF8001').length;
  AMARILLO = DATA.filter(r => r.color === 'FFFFFF00').length;

  renderDashboard();
  renderDeptCards();
  renderDeptCards2();
  renderEjMetrics();
  renderEjTable();
  renderProgMetrics();
  renderProgTable();
  renderConredMetrics();
  renderConredTable();
}
window.recalculateAll = recalculateAll;
const ALERTS=[
  {type:'critical',title:'INSAN programada sin ejecutar',body:`${DATA.filter(r=>progOf(r,'insan')&&!r.ej_insan).length} municipios con INSAN programada pendiente de ejecución.`,count:DATA.filter(r=>progOf(r,'insan')&&!r.ej_insan).length},
  {type:'critical',title:'Municipios Muy Alta INSAN sin intervención',body:`${DATA.filter(r=>r.color==='FFFF0000'&&!r.atendido_prog).length} municipios en rojo sin intervención principal programada.`,count:DATA.filter(r=>r.color==='FFFF0000'&&!r.atendido_prog).length},
  {type:'high',title:'APA programada sin ejecutar',body:`${DATA.filter(r=>progOf(r,'apa')&&!r.ej_apa).length} municipios con APA programada pero sin ejecución.`,count:DATA.filter(r=>progOf(r,'apa')&&!r.ej_apa).length},
  {type:'medium',title:'NDA programado sin fecha definida',body:`${DATA.filter(r=>r.prog_nda&&!r.prog_nda_fecha).length} registros de NDA programado sin fecha de programación confirmada.`,count:DATA.filter(r=>r.prog_nda&&!r.prog_nda_fecha).length},
  {type:'info',title:'CONRED sin datos registrados',body:'La columna CONRED no tiene intervenciones activas en la matriz actual.',count:0},
];

// ── DATE & TOPBAR ──
const now=new Date();
document.getElementById('sb-alert-badge').textContent=ALERTS.filter(a=>a.type==='critical'||a.type==='high').length;
document.getElementById('sb-bodegas-badge').textContent=Object.keys(BODEGAS_METADATA).length;

// ── NAVIGATION ──
function navigate(page){
  const actualPage = (page === 'raciones') ? 'bodegas' : page;
  document.querySelectorAll('.sb-item').forEach(b=>b.classList.toggle('active',b.dataset.page===page));
  document.querySelectorAll('.page').forEach(p=>p.classList.toggle('active',p.id==='page-'+actualPage));
  const titles={inicio:'Panel principal',resumen:'Resumen Departamental',ejecucion:'Ejecución',programacion:'Programación',conred:'CONRED',bodegas:'Bodegas y Almacenes',raciones:'Contenido de la Ración',departamentos:'Departamentos',alertas:'Alertas',importar:'Importar datos',usuarios:'Usuarios y permisos'};
  document.getElementById('top-title').textContent=titles[page]||page;
  if(actualPage === 'bodegas') initBodegas();
  if(page === 'bodegas') switchBodegasView('resumen');
  if(page === 'raciones') switchBodegasView('ficha');
  if(page === 'importar') initImportPage();
  if(page === 'usuarios') initUsersPage();
  document.querySelector('.page-content').scrollTo(0,0);
  closeMobileSidebar();
}
document.querySelectorAll('.sb-item[data-page]').forEach(b=>b.addEventListener('click',()=>navigate(b.dataset.page)));

// ── SIDEBAR COLLAPSE ──
let sbCollapsed=false;
function toggleSidebar(){
  sbCollapsed=!sbCollapsed;
  document.getElementById('sidebar').classList.toggle('collapsed',sbCollapsed);
  document.getElementById('sb-collapse-btn').textContent=sbCollapsed?'▶':'◀';
}
function openMobileSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sb-overlay').classList.add('show');
}
function closeMobileSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sb-overlay').classList.remove('show');
}

// ── GLOBAL SEARCH ──
function onGlobalSearch(q){
  const drop=document.getElementById('search-drop');
  if(!q||q.length<2){drop.hidden=true;return;}
  const hits=DATA.filter(r=>r.municipio.toLowerCase().includes(q.toLowerCase())||r.departamento.toLowerCase().includes(q.toLowerCase())).slice(0,10);
  if(!hits.length){drop.hidden=true;return;}
  drop.innerHTML=hits.map(r=>`<div class="search-item" onclick="goToMuni('${r.municipio.replace(/'/g,"\\'")}')">
    <span class="vuln-pip" style="background:${vcolor(r.color)};width:8px;height:8px;border-radius:50%;flex-shrink:0"></span>
    <div><div class="search-item-muni">${r.municipio}</div><div class="search-item-dept">${r.departamento}</div></div></div>`).join('');
  drop.hidden=false;
}
function hideSearchDrop(){document.getElementById('search-drop').hidden=true;}
function goToMuni(muni){
  document.getElementById('global-search').value=muni;
  document.getElementById('ej-search').value=muni;
  navigate('ejecucion'); renderEjTable();
  hideSearchDrop();
}

// ── PANEL PRINCIPAL: RACIONES DE ALIMENTOS ──
// AA (Asistencia Alimentaria) = NDA + MC + Judicial + INSAN. APA y Reserva se reportan aparte.
// Meta = ejecutado + programado pendiente; % de avance = ejecutado ÷ meta.
const AA_KEYS=['nda','mc','judicial','insan'];
function sumRows(rows,pre,keys){return rows.reduce((s,r)=>s+keys.reduce((a,k)=>a+(pre==='prog'?progOf(r,k):(Number(r[pre+'_'+k])||0)),0),0);}
function racionesDe(rows){
  const g=keys=>({ej:sumRows(rows,'ej',keys),prog:sumRows(rows,'prog',keys)});
  const aa=g(AA_KEYS),apa=g(['apa']),res=g(['reserva']);
  return {aa,apa,res,tot:{ej:aa.ej+apa.ej+res.ej,prog:aa.prog+apa.prog+res.prog}};
}
function pctAvance(o){const m=o.ej+o.prog;return m?o.ej/m*100:0;}
function fmtZ(n){return n?fmtN(n):'—';}
function fmtPct(p){return p.toLocaleString('es-GT',{minimumFractionDigits:2,maximumFractionDigits:2})+' %';}
function escHTML(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function fechaLarga(iso){
  if(!iso)return '—';
  const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  const[y,m,d]=iso.split('-').map(Number);return `${d} de ${meses[m-1]} de ${y}`;
}

const RX_ICONS={
  aa:'<svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="8" r="2.4"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M14.5 14.2c.8-.3 1.6-.5 2.5-.5 2.8 0 5 2.2 5 5"/></svg>',
  apa:'<svg viewBox="0 0 24 24"><path d="M4 9h16l-1.6 10.2a2 2 0 01-2 1.8H7.6a2 2 0 01-2-1.8L4 9zM8 9l4-6 4 6M9 13v4M15 13v4"/></svg>',
  res:'<svg viewBox="0 0 24 24"><path d="M3 11l9-7 9 7M5 9.5V20h14V9.5M9 20v-6h6v6"/></svg>',
  tot:'<svg viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2.8h6V4M8.5 11l2 2 4-4M8.5 17h7"/></svg>',
  insan:'<svg viewBox="0 0 24 24"><circle cx="7" cy="8" r="2.2"/><circle cx="12" cy="6.5" r="2.5"/><circle cx="17" cy="8" r="2.2"/><path d="M3 19c0-2.4 1.8-4.4 4-4.4M21 19c0-2.4-1.8-4.4-4-4.4M7.5 20c0-2.8 2-5 4.5-5s4.5 2.2 4.5 5"/></svg>',
  nda:'<svg viewBox="0 0 24 24"><circle cx="9" cy="7.5" r="3"/><circle cx="16.5" cy="10" r="2.2"/><path d="M3.5 20c0-3 2.5-5.5 5.5-5.5s5.5 2.5 5.5 5.5M14 20c0-2 1.2-3.8 2.5-3.8S19.5 18 19.5 20"/></svg>',
  judicial:'<svg viewBox="0 0 24 24"><path d="M12 3v18M7 21h10M5 7h14M5 7l-3 7a3.5 3.5 0 006 0L5 7zM19 7l-3 7a3.5 3.5 0 006 0l-3-7"/></svg>',
  mc:'<svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>'
};

function ringCard(o){
  const p=pctAvance(o.v),C=2*Math.PI*52,off=C*(1-Math.min(p,100)/100);
  const b=RX_PRESUPUESTO[o.tone];
  const bp=b?(b.presupuesto?b.ejecutado/b.presupuesto*100:0):0,bC=2*Math.PI*17,bOff=bC*(1-Math.min(bp,100)/100);
  return `<button class="rx-ring-card tone-${o.tone}" onclick="${o.action}" aria-label="${o.label}: ${fmtN(o.v.ej)} raciones ejecutadas, ${fmtPct(p)} de avance. Ver detalle">
    <div class="rx-ring-top">
      <div class="rx-ring">
        <svg viewBox="0 0 120 120" aria-hidden="true">
          <defs><linearGradient id="rxg-${o.tone}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--rx-${o.tone}-a)"/><stop offset="1" stop-color="var(--rx-${o.tone}-b)"/></linearGradient></defs>
          <circle class="rx-ring-bg" cx="60" cy="60" r="52"/>
          <circle class="rx-ring-fg" cx="60" cy="60" r="52" stroke="url(#rxg-${o.tone})" style="--c:${C.toFixed(2)};stroke-dasharray:${C.toFixed(2)};stroke-dashoffset:${off.toFixed(2)}" transform="rotate(-90 60 60)"/>
        </svg>
        <div class="rx-ring-in">
          <span class="rx-ring-ico">${RX_ICONS[o.icon]}</span>
          <span class="rx-ring-lbl">${o.label}</span>
          <strong class="rx-ring-val">${fmtN(o.v.ej)}</strong>
          <span class="rx-ring-pct">${fmtPct(p)}</span>
        </div>
      </div>
      <div class="rx-ring-side">
        <div><span>Ejecutado</span><strong>${fmtN(o.v.ej)}</strong></div>
        <div><span>Programado</span><strong>${fmtN(o.v.prog)}</strong></div>
        <div class="rx-ring-meta"><span>Total</span><strong>${fmtN(o.v.ej+o.v.prog)}</strong></div>
      </div>
    </div>
    ${b?`<div class="rx-ring-budget">
      <div class="rx-ring-budget-nums">
        <div><span>Presupuesto</span><strong>${fmtQ(b.presupuesto)}</strong></div>
        <div><span>Ejecutado</span><strong>${fmtQ(b.ejecutado)}</strong></div>
      </div>
      <div class="rx-ring-budget-mini">
        <svg viewBox="0 0 40 40" aria-hidden="true">
          <defs><linearGradient id="rxgm-${o.tone}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--rx-${o.tone}-a)"/><stop offset="1" stop-color="var(--rx-${o.tone}-b)"/></linearGradient></defs>
          <circle class="rx-ring-bg" cx="20" cy="20" r="17"/>
          <circle class="rx-ring-fg" cx="20" cy="20" r="17" stroke="url(#rxgm-${o.tone})" stroke-width="5" style="stroke-dasharray:${bC.toFixed(2)};stroke-dashoffset:${bOff.toFixed(2)}" transform="rotate(-90 20 20)"/>
        </svg>
        <span>${Math.round(bp)}%</span>
      </div>
    </div>`:''}
  </button>`;
}

function partCard(o){
  return `<button class="rx-part tone-${o.tone}" onclick="navigate('ejecucion');setEjInt('${o.key}')" aria-label="${o.title}: ${fmtN(o.ej)} raciones ejecutadas. Ver detalle">
    <span class="rx-part-ico">${RX_ICONS[o.key]}</span>
    <span class="rx-part-title">${o.title}</span>
    <span class="rx-part-nums">
      <span><small>Ejecutado</small><b>${fmtN(o.ej)}</b></span>
      <span><small>Programado</small><b>${fmtN(o.prog)}</b></span>
    </span>
    <span class="rx-part-share"><i style="width:${o.share.toFixed(1)}%"></i></span>
    <span class="rx-part-foot">${o.share.toLocaleString('es-GT',{maximumFractionDigits:1})}% de lo ejecutado en AA</span>
  </button>`;
}

// 22 filas: los 22 departamentos caben sin cambiar de página; municipios usa la misma altura.
const RX_PAGE=22;
const rxState={dep:{q:'',sort:'nombre',dir:1,page:1},mun:{q:'',dept:'',sort:'nombre',dir:1,page:1}};

function rxRowsDept(){
  return DEPTS.map(d=>{const rows=DATA.filter(r=>r.departamento===d);return {nombre:d,sub:`${rows.length} municipios`,...rxAgg(rows)};});
}
function rxRowsMuni(){
  return DATA.map(r=>({nombre:r.municipio,sub:r.departamento,dept:r.departamento,cod:r.cod_mun,color:r.color,...rxAgg([r])}));
}
function rxAgg(rows){
  const x=racionesDe(rows);
  return {aa:x.aa.ej,apa:x.apa.ej,res:x.res.ej,tot:x.tot.ej,pct:pctAvance(x.tot),hasMeta:(x.tot.ej+x.tot.prog)>0};
}
function rxSort(kind,key){
  const s=rxState[kind];
  if(s.sort===key)s.dir*=-1;else{s.sort=key;s.dir=key==='nombre'?1:-1;}
  s.page=1;renderRxTable(kind);
}
function rxPage(kind,p){rxState[kind].page=p;renderRxTable(kind);}
function rxFilter(kind){
  const s=rxState[kind];
  s.q=(document.getElementById(`rx-${kind}-q`).value||'').trim().toLowerCase();
  if(kind==='mun')s.dept=document.getElementById('rx-mun-dept').value;
  s.page=1;renderRxTable(kind);
}
function rxPickDept(d){
  const sel=document.getElementById('rx-mun-dept');sel.value=d;rxFilter('mun');
  document.getElementById('rx-mun-card').scrollIntoView({behavior:'smooth',block:'nearest'});
}
function rxOpenMuni(cod){
  const r=DATA.find(x=>x.cod_mun===cod);if(!r)return;
  document.getElementById('ej-search').value=r.municipio;
  navigate('ejecucion');renderEjTable();
}
function avanceTone(p){return p>=75?'hi':p>=50?'mid':'lo';}

function renderRxTable(kind){
  const s=rxState[kind];
  let rows=kind==='dep'?rxRowsDept():rxRowsMuni();
  if(kind==='mun'&&s.dept)rows=rows.filter(r=>r.dept===s.dept);
  if(s.q)rows=rows.filter(r=>r.nombre.toLowerCase().includes(s.q)||r.sub.toLowerCase().includes(s.q));
  rows.sort((a,b)=>s.sort==='nombre'?a.nombre.localeCompare(b.nombre,'es')*s.dir:((a[s.sort]-b[s.sort])*s.dir||a.nombre.localeCompare(b.nombre,'es')));
  const pages=Math.max(1,Math.ceil(rows.length/RX_PAGE));if(s.page>pages)s.page=pages;
  const start=(s.page-1)*RX_PAGE,view=rows.slice(start,start+RX_PAGE);
  const th=(key,label,cls='')=>`<th class="${cls}${s.sort===key?' sorted':''}"><button onclick="rxSort('${kind}','${key}')">${label}<span aria-hidden="true">${s.sort===key?(s.dir>0?'▲':'▼'):'↕'}</span></button></th>`;
  const head=`<thead><tr><th class="rx-num">#</th>${th('nombre',kind==='dep'?'Departamento':'Municipio')}${th('aa','AA','rx-c-aa')}${th('apa','APA','rx-c-apa')}${th('res','Reserva','rx-c-res')}${th('tot','Total','rx-c-tot')}${th('pct','% Avance')}</tr></thead>`;
  const body=view.length?view.map((r,i)=>{
    const click=kind==='dep'?`rxPickDept('${r.nombre.replace(/'/g,"\\'")}')`:`rxOpenMuni(${r.cod})`;
    const pip=kind==='mun'?`<i class="rx-pip" style="background:${vcolor(r.color)}" title="Inseguridad ${vname(r.color)}"></i>`:'';
    const pct=r.hasMeta?`<span class="rx-av av-${avanceTone(r.pct)}"><i style="width:${Math.min(r.pct,100).toFixed(1)}%"></i></span><b>${Math.round(r.pct)}%</b>`:'<span class="rx-muted">—</span>';
    return `<tr tabindex="0" onclick="${click}" onkeydown="if(event.key==='Enter')${click}">
      <td class="rx-num">${start+i+1}</td>
      <td class="rx-name">${pip}<span><strong>${escHTML(r.nombre)}</strong><small>${escHTML(r.sub)}</small></span></td>
      <td class="rx-c-aa">${fmtZ(r.aa)}</td><td class="rx-c-apa">${fmtZ(r.apa)}</td><td class="rx-c-res">${fmtZ(r.res)}</td><td class="rx-c-tot">${fmtZ(r.tot)}</td>
      <td class="rx-pct">${pct}</td></tr>`;
  }).join(''):`<tr><td colspan="7" class="rx-empty">Sin resultados para este filtro.</td></tr>`;
  document.getElementById(`rx-${kind}-tbl`).innerHTML=head+`<tbody>${body}</tbody>`;
  // Paginación compacta: 1 … p-1 p p+1 … N
  const nums=[...new Set([1,s.page-1,s.page,s.page+1,pages])].filter(n=>n>=1&&n<=pages).sort((a,b)=>a-b);
  let pg='',prev=0;nums.forEach(n=>{if(n-prev>1)pg+='<span class="rx-gap">…</span>';pg+=`<button class="${n===s.page?'on':''}" onclick="rxPage('${kind}',${n})">${n}</button>`;prev=n;});
  document.getElementById(`rx-${kind}-pg`).innerHTML=`<span>Mostrando ${rows.length?start+1:0}–${start+view.length} de ${rows.length} ${kind==='dep'?'departamentos':'municipios'}</span><div><button ${s.page===1?'disabled':''} onclick="rxPage('${kind}',${s.page-1})" aria-label="Página anterior">‹</button>${pg}<button ${s.page===pages?'disabled':''} onclick="rxPage('${kind}',${s.page+1})" aria-label="Página siguiente">›</button></div>`;
}

function renderDashboard(){
  const x=racionesDe(DATA);
  document.getElementById('rx-rings').innerHTML=[
    {label:'Asistencia Alimentaria',tone:'aa',icon:'aa',v:x.aa,action:"navigate('ejecucion');setEjInt('all')"},
    {label:'Alimentos por Acciones',tone:'apa',icon:'apa',v:x.apa,action:"navigate('ejecucion');setEjInt('apa')"},
    {label:'Reserva Estratégica',tone:'res',icon:'res',v:x.res,action:"navigate('ejecucion');setEjInt('reserva')"},
    {label:'Total Ejecutado',tone:'tot',icon:'tot',v:x.tot,action:"navigate('ejecucion');setEjInt('all')"}
  ].map(ringCard).join('');
  const aaEj=x.aa.ej||1;
  document.getElementById('rx-parts').innerHTML=[
    {key:'insan',title:'INSAN',tone:'insan'},
    {key:'nda',title:'NDA Nacional',tone:'nda'},
    {key:'judicial',title:'Medida Transitoria / Judicial',tone:'judicial'},
    {key:'mc',title:'Medida Cautelar / Política Pública',tone:'mc'}
  ].map(p=>({...p,ej:T['ej_'+p.key],prog:T['prog_'+p.key],share:T['ej_'+p.key]/aaEj*100})).map(partCard).join('');
  renderRxTable('dep');
  renderRxTable('mun');
}
function initDashboard(){
  const sel=document.getElementById('rx-mun-dept');
  DEPTS.forEach(d=>{const o=document.createElement('option');o.value=d;o.textContent=d;sel.appendChild(o);});
  const m=window.VISAN_META||{},imp=m.ultimo_import;
  document.getElementById('rx-update').innerHTML=`<span class="rx-upd-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg></span><div><small>Datos al</small><strong>${fechaLarga(m.fecha_corte)}</strong>${imp?`<small class="rx-upd-src" title="${escHTML(imp.archivo)}">Importado ${fmtD((imp.fecha||'').slice(0,10))} por ${escHTML(imp.usuario)}</small>`:''}</div>`;
  renderDashboard();
}
function goToRisk(color){navigate('ejecucion');document.getElementById('ej-vuln').value=color;renderEjTable();}

// ── TERRITORY NAVIGATION ──
function goToDept(dept){
  document.getElementById('dept-search2').value=dept;
  navigate('departamentos'); renderDeptCards2();
}

// ── DEPT CARDS (shared logic) ──
function deptCardHTML(dept){
  const rows=DATA.filter(r=>r.departamento===dept);
  const r=rows.filter(r=>r.color==='FFFF0000').length;
  const o=rows.filter(r=>r.color==='FFFF8001').length;
  const y=rows.filter(r=>r.color==='FFFFFF00').length;
  const tot=r+o+y||1;
  const ej=rows.reduce((s,r)=>s+(r.ej_nda||0)+(r.ej_mc||0)+(r.ej_judicial||0)+(r.ej_apa||0)+(r.ej_reserva||0)+(r.ej_insan||0),0);
  const prog=rows.reduce((s,r)=>s+PROG_KEYS.reduce((a,k)=>a+progOf(r,k),0),0);
  return `<div class="dept-card" onclick="goToDept('${dept}')">
    <div class="dept-card-hd"><div class="dept-card-name">${dept}</div><div class="dept-card-cnt">${rows.length} municipios</div></div>
    <div class="dept-risk-strip">
      <div style="width:${(r/tot*100).toFixed(1)}%;background:var(--danger)"></div>
      <div style="width:${(o/tot*100).toFixed(1)}%;background:var(--warn-h)"></div>
      <div style="width:${(y/tot*100).toFixed(1)}%;background:var(--warn-m)"></div>
    </div>
    <div class="dept-card-body">
      <div class="dept-stat-row"><span class="dsr-label" style="color:var(--danger)">● Muy Alta</span><span class="dsr-val">${r}</span></div>
      <div class="dept-stat-row"><span class="dsr-label" style="color:var(--warn-h)">● Alta</span><span class="dsr-val">${o}</span></div>
      <div class="dept-stat-row"><span class="dsr-label" style="color:var(--warn-m)">● Media</span><span class="dsr-val">${y}</span></div>
      <div class="dept-stat-row" style="margin-top:6px;border-top:1px solid var(--border-2);padding-top:6px"><span class="dsr-label">Ejecutado</span><span class="dsr-val">${fmtN(ej)}</span></div>
      <div class="dept-stat-row"><span class="dsr-label">Programado</span><span class="dsr-val">${fmtN(prog)}</span></div>
    </div>
  </div>`;
}
function renderDeptCards(){
  const q=(document.getElementById('res-search').value||'').toLowerCase();
  document.getElementById('dept-grid').innerHTML=DEPTS.filter(d=>d.toLowerCase().includes(q)).map(deptCardHTML).join('');
}
function renderDeptCards2(){
  const q=(document.getElementById('dept-search2').value||'').toLowerCase();
  document.getElementById('dept-grid2').innerHTML=DEPTS.filter(d=>d.toLowerCase().includes(q)).map(deptCardHTML).join('');
}

// ── POPULATE DEPT SELECTS ──
['ej-dept','prog-dept','conred-dept'].forEach(id=>{
  const sel=document.getElementById(id);
  if(sel){
    DEPTS.forEach(d=>{const o=document.createElement('option');o.value=d;o.textContent=d;sel.appendChild(o);});
  }
});

// ── SESIÓN Y PERMISOS ──
// El usuario viene de la sesión del servidor (js/boot.js). El servidor vuelve a validar cada edición.
const ROL_INFO={
  admin:{label:'Administrador',badge:'🛡️ Administrador',cls:'admin',desc:'Control total: usuarios, permisos, importación y edición'},
  editor:{label:'Editor',badge:'✏️ Editor',cls:'editor',desc:'Edición según los permisos delegados'},
  visualizador:{label:'Visualizador',badge:'👁️ Solo lectura',cls:'viewer',desc:'Consulta de tableros e indicadores'}
};
let currentUser={
  id:CURRENT_USER.id,
  username:CURRENT_USER.usuario,
  name:CURRENT_USER.nombre,
  rol:CURRENT_USER.rol,
  // Compatibilidad con el código existente: 'editor' = puede editar algo
  role:CURRENT_USER.rol==='visualizador'?'visualizador':'editor',
  permisos:CURRENT_USER.permisos
};
const MOD_OF={ej:'ejecucion',prog:'programacion',conred:'conred'};
function canEditModule(mod){return currentUser.permisos.modulos.includes(mod);}
function deptAllowed(dept){const d=currentUser.permisos.departamentos;return !d.length||d.includes(dept);}
function fieldRule(key){
  let m;
  if((m=key.match(/^ej_(nda|mc|judicial|apa|reserva|insan)(_fecha)?$/)))return ['ejecucion',m[1]];
  if((m=key.match(/^prog_(nda|mc|judicial|apa|reserva|insan)(_fecha|_carga|_solicitud)?$/)))return ['programacion',m[1]];
  if(key==='prog_anio')return ['programacion',null];
  if(['conred','conred_solicitud','conred_fecha'].includes(key))return ['conred',null];
  return null;
}
function canEditField(key,row){
  const rule=fieldRule(key);if(!rule)return false;
  const [mod,modal]=rule,p=currentUser.permisos;
  return p.modulos.includes(mod)&&(!modal||p.modalidades.includes(modal))&&deptAllowed(row.departamento);
}
function canEditRow(module,row){
  return canEditModule(MOD_OF[module])&&deptAllowed(row.departamento)&&(module==='conred'||currentUser.permisos.modalidades.length>0);
}
window.canEditField=canEditField;window.canEditRow=canEditRow;window.canEditModule=canEditModule;

function initAuth(){
  const info=ROL_INFO[currentUser.rol];
  const initials=(currentUser.name||currentUser.username).split(/\s+/).map(w=>w[0]).join('').slice(0,2).toUpperCase();
  document.getElementById('top-user-name').textContent=currentUser.name;
  const badge=document.getElementById('top-role-badge');
  badge.className='top-role-badge '+info.cls;badge.textContent=info.badge;
  document.getElementById('top-avatar-initials').textContent=initials;
  document.getElementById('ud-name').textContent=currentUser.name;
  document.getElementById('ud-role-label').textContent=`@${currentUser.username} · ${info.desc}`;
  // Menú: módulos de sistema según el rol
  document.querySelectorAll('[data-requires]').forEach(el=>{
    const req=el.dataset.requires;
    el.hidden=!(req==='admin'?currentUser.rol==='admin':req==='importar'?currentUser.permisos.importar:true);
  });
}

function toggleUserDropdown(e){
  if(e)e.stopPropagation();
  const dd=document.getElementById('user-dropdown');
  if(dd)dd.hidden=!dd.hidden;
}
window.toggleUserDropdown=toggleUserDropdown;
document.addEventListener('click',e=>{
  const dd=document.getElementById('user-dropdown');
  if(dd&&!dd.contains(e.target))dd.hidden=true;
});

async function logout(){
  try{await api('api/auth.php?a=logout',{json:{}});}catch(e){}
  location.replace('login.html');
}
window.logout=logout;

function openPasswordModal(){
  document.getElementById('user-dropdown').hidden=true;
  ['pw-actual','pw-nueva','pw-confirma'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('pw-error').textContent='';
  document.getElementById('password-modal').hidden=false;
  document.getElementById('pw-actual').focus();
}
function closePasswordModal(){document.getElementById('password-modal').hidden=true;}
async function submitPassword(){
  const actual=document.getElementById('pw-actual').value,nueva=document.getElementById('pw-nueva').value,conf=document.getElementById('pw-confirma').value;
  const err=document.getElementById('pw-error');
  if(nueva.length<8){err.textContent='La nueva contraseña debe tener al menos 8 caracteres.';return;}
  if(nueva!==conf){err.textContent='La confirmación no coincide.';return;}
  try{
    await api('api/auth.php?a=password',{json:{actual,nueva}});
    closePasswordModal();showToast('✓ Contraseña actualizada.');
  }catch(e){err.textContent=e.message;}
}
window.openPasswordModal=openPasswordModal;window.closePasswordModal=closePasswordModal;window.submitPassword=submitPassword;

function showToast(msg,type){
  const wrap=document.getElementById('toast-wrap');
  if(!wrap)return;
  const t=document.createElement('div');
  t.className='toast-msg'+(type==='error'?' toast-error':'');
  t.setAttribute('role',type==='error'?'alert':'status');
  const span=document.createElement('span');span.textContent=msg;t.appendChild(span);
  wrap.appendChild(t);
  setTimeout(()=>{
    t.style.opacity='0';
    t.style.transform='translateY(10px)';
    t.style.transition='all .3s ease';
    setTimeout(()=>t.remove(),300);
  },type==='error'?6000:3500);
}
window.showToast=showToast;

// ── EDIT MUNICIPALITY MODAL & PROMPT ──
// Las ediciones se guardan en el servidor; solo se habilitan los campos que el usuario tiene delegados.
let currentEditMuni = null;
let currentEditModule = 'ej';

async function saveMuniChanges(row, cambios) {
  const res = await api('api/data.php?a=editar', { json: { cod_mun: row.cod_mun, cambios } });
  Object.assign(row, res.fila);
  recalculateAll();
}

function editFieldHTML(row, key, label, type, placeholder, fullWidth) {
  const allowed = canEditField(key, row);
  const val = row[key] !== null && row[key] !== undefined ? row[key] : '';
  return `
    <div class="edit-field-group"${fullWidth ? ' style="grid-column: 1 / -1"' : ''}>
      <div class="edit-field-label"><span>${label}</span>${allowed ? '' : '<span class="edit-lock" title="Sin permiso delegado para este campo">🔒</span>'}</div>
      <input type="${type}" class="edit-input" id="field-${key}" data-key="${key}" value="${escHTML(val)}" placeholder="${placeholder}"${type === 'number' ? ' min="0"' : ''}${allowed ? '' : ' disabled'}>
    </div>`;
}

function openEditMuniModal(cod_mun, module) {
  const row = DATA.find(r => r.cod_mun === cod_mun);
  if (!row) return;
  if (!canEditRow(module || 'ej', row)) {
    showToast('⚠️ No tienes permiso para editar este registro.', 'error');
    return;
  }
  currentEditMuni = row;
  currentEditModule = module || 'ej';

  document.getElementById('edit-modal-title').textContent = `Editar ${currentEditModule === 'ej' ? 'Ejecución' : currentEditModule === 'prog' ? 'Programación' : 'CONRED'}`;
  document.getElementById('edit-modal-sub').textContent = `${row.municipio} · ${row.departamento} (Cód. Municipal: ${row.cod_mun})`;

  const labels = { nda: 'NDA', mc: 'MC', judicial: 'Judicial', apa: 'APA', reserva: 'Reserva', insan: 'INSAN' };
  let fieldsHTML = '';
  if (currentEditModule === 'ej') {
    Object.entries(labels).forEach(([k, l]) => {
      fieldsHTML += editFieldHTML(row, `ej_${k}`, `${l} Cantidad`, 'number', '0');
      fieldsHTML += editFieldHTML(row, `ej_${k}_fecha`, `${l} Fecha`, 'text', 'AAAA-MM-DD o texto');
    });
  } else if (currentEditModule === 'prog') {
    fieldsHTML += editFieldHTML(row, 'prog_anio', 'Año de Solicitud / Programación', 'number', '2025 o 2026', true);
    Object.entries(labels).forEach(([k, l]) => {
      fieldsHTML += editFieldHTML(row, `prog_${k}`, `${l} Prog. Cantidad`, 'number', '0');
      fieldsHTML += editFieldHTML(row, `prog_${k}_fecha`, `${l} Prog. Fecha`, 'text', 'AAAA-MM-DD o texto');
    });
  } else if (currentEditModule === 'conred') {
    fieldsHTML += editFieldHTML(row, 'conred', 'CONRED Cantidad', 'number', '0', true);
    fieldsHTML += editFieldHTML(row, 'conred_solicitud', 'Solicitud (Fecha)', 'text', 'AAAA-MM-DD o texto');
    fieldsHTML += editFieldHTML(row, 'conred_fecha', 'Fecha Programada', 'text', 'AAAA-MM-DD o texto');
  }

  document.getElementById('edit-modal-fields').innerHTML = fieldsHTML;
  document.getElementById('edit-modal').hidden = false;
}
window.openEditMuniModal = openEditMuniModal;

function closeEditModal() {
  document.getElementById('edit-modal').hidden = true;
  currentEditMuni = null;
}
window.closeEditModal = closeEditModal;

async function submitModalEdit() {
  if (!currentEditMuni) return;
  const row = currentEditMuni;
  const cambios = {};
  for (const input of document.querySelectorAll('#edit-modal-fields input:not([disabled])')) {
    const key = input.dataset.key;
    const raw = input.value.trim();
    let val = raw === '' ? null : raw;
    if (input.type === 'number' && val !== null) {
      val = Number(raw);
      if (isNaN(val) || val < 0) { showToast(`⚠️ Valor no válido en ${key}.`, 'error'); input.focus(); return; }
    }
    if ((row[key] ?? null) !== val) cambios[key] = val;
  }
  if (!Object.keys(cambios).length) { closeEditModal(); return; }

  const btn = document.getElementById('edit-save-btn');
  btn.disabled = true;
  try {
    await saveMuniChanges(row, cambios);
    closeEditModal();
    showToast(`✓ Cambios guardados para ${row.municipio}. Totales recalculados.`);
  } catch (e) {
    showToast(`No se guardó: ${e.message}`, 'error');
  } finally {
    btn.disabled = false;
  }
}
window.submitModalEdit = submitModalEdit;

async function promptEditCell(cod_mun, fieldKey, fieldLabel, isNumber) {
  const row = DATA.find(r => r.cod_mun === cod_mun);
  if (!row) return;
  if (!canEditField(fieldKey, row)) {
    showToast('⚠️ No tienes permiso para editar este campo.', 'error');
    return;
  }

  const currentVal = row[fieldKey] !== null && row[fieldKey] !== undefined ? row[fieldKey] : '';
  const newVal = prompt(`Editar ${fieldLabel} para ${row.municipio}:`, currentVal);
  if (newVal === null) return;

  let parsedVal = newVal.trim();
  if (isNumber) {
    parsedVal = parsedVal === '' ? null : Number(parsedVal.replace(/[,\s]/g, ''));
    if (parsedVal !== null && (isNaN(parsedVal) || parsedVal < 0)) {
      showToast('⚠️ Ingresa un número válido (mayor o igual a 0).', 'error');
      return;
    }
  } else {
    parsedVal = parsedVal === '' ? null : parsedVal;
  }
  if ((row[fieldKey] ?? null) === parsedVal) return;

  try {
    await saveMuniChanges(row, { [fieldKey]: parsedVal });
    showToast(`✓ ${fieldLabel} actualizado en ${row.municipio}. Totales recalculados.`);
  } catch (e) {
    showToast(`No se guardó: ${e.message}`, 'error');
  }
}
window.promptEditCell = promptEditCell;

// ── MUNI CELL & VULNERABILITY HELPERS ──
function muniCell(r){
  return `<div class="muni-wrap">
    <span class="vuln-pip" style="background:${vcolor(r.color)};width:9px;height:9px;border-radius:50%"></span>
    <div>
      <div class="muni-name">${r.municipio}</div>
      <div class="muni-dept">${r.departamento}</div>
    </div>
  </div>`;
}
function vulnBadge(r){
  return `<span class="risk-badge ${vbadge(r.color)}">${vname(r.color)}</span>`;
}

// ── SHARED ACCORDION & EXPANSION STATE ──
let ejSelectedDept = null, ejExpandedDepts = new Set();
let progSelectedDept = null, progExpandedDepts = new Set();
let conredSelectedDept = null, conredExpandedDepts = new Set();

function toggleDeptExpand(mod, dept){
  if(mod === 'ej'){
    if(ejExpandedDepts.has(dept)) ejExpandedDepts.delete(dept);
    else ejExpandedDepts.add(dept);
    renderEjTable();
  } else if(mod === 'prog'){
    if(progExpandedDepts.has(dept)) progExpandedDepts.delete(dept);
    else progExpandedDepts.add(dept);
    renderProgTable();
  } else if(mod === 'conred'){
    if(conredExpandedDepts.has(dept)) conredExpandedDepts.delete(dept);
    else conredExpandedDepts.add(dept);
    renderConredTable();
  }
}
window.toggleDeptExpand = toggleDeptExpand;

function toggleAllDepts(mod){
  if(mod === 'ej'){
    if(ejExpandedDepts.size >= DEPTS.length){
      ejExpandedDepts.clear();
      const el = document.getElementById('ej-expand-all-text');
      if(el) el.textContent = '⊞ Expandir Todo';
    } else {
      DEPTS.forEach(d => ejExpandedDepts.add(d));
      const el = document.getElementById('ej-expand-all-text');
      if(el) el.textContent = '⊟ Colapsar Todo';
    }
    renderEjTable();
  } else if(mod === 'prog'){
    if(progExpandedDepts.size >= DEPTS.length){
      progExpandedDepts.clear();
      const el = document.getElementById('prog-expand-all-text');
      if(el) el.textContent = '⊞ Expandir Todo';
    } else {
      DEPTS.forEach(d => progExpandedDepts.add(d));
      const el = document.getElementById('prog-expand-all-text');
      if(el) el.textContent = '⊟ Colapsar Todo';
    }
    renderProgTable();
  } else if(mod === 'conred'){
    if(conredExpandedDepts.size >= DEPTS.length){
      conredExpandedDepts.clear();
      const el = document.getElementById('conred-expand-all-text');
      if(el) el.textContent = '⊞ Expandir Todo';
    } else {
      DEPTS.forEach(d => conredExpandedDepts.add(d));
      const el = document.getElementById('conred-expand-all-text');
      if(el) el.textContent = '⊟ Colapsar Todo';
    }
    renderConredTable();
  }
}
window.toggleAllDepts = toggleAllDepts;

// ── EJECUCIÓN MODULE ──
const EJ_INTS=[
  {id:'all',l:'Todas'},
  {id:'nda',l:'NDA',vk:'ej_nda',dk:'ej_nda_fecha',desc:'NDA Nacional'},
  {id:'mc',l:'MC',vk:'ej_mc',dk:'ej_mc_fecha',desc:'Medida Cautelar'},
  {id:'judicial',l:'Judicial',vk:'ej_judicial',dk:'ej_judicial_fecha',desc:'Medida Transitoria / Judicial'},
  {id:'apa',l:'APA',vk:'ej_apa',dk:'ej_apa_fecha',desc:'Alimentos por Acciones'},
  {id:'reserva',l:'Reserva',vk:'ej_reserva',dk:'ej_reserva_fecha',desc:'Reserva Estratégica'},
  {id:'insan',l:'INSAN',vk:'ej_insan',dk:'ej_insan_fecha',desc:'Inseguridad Alimentaria'}
];
let ejInt='all';

function buildEjChips(){
  document.getElementById('ej-chips').innerHTML=EJ_INTS.map(d=>`
    <button class="chip ${d.id===ejInt?'active':''}" onclick="setEjInt('${d.id}')">${d.l}</button>
  `).join('');
}
function setEjInt(id){
  ejInt=id;
  buildEjChips();
  renderEjMetrics();
  renderEjTable();
}
window.setEjInt=setEjInt;

function selectEjDept(dept){
  ejSelectedDept = dept;
  const sel = document.getElementById('ej-dept');
  if(sel) sel.value = dept || '';
  if(dept) ejExpandedDepts.add(dept);
  renderEjMetrics();
  renderEjTable();
}
window.selectEjDept = selectEjDept;

function onEjDeptSelectChange(){
  const val = document.getElementById('ej-dept').value || null;
  selectEjDept(val);
}
window.onEjDeptSelectChange = onEjDeptSelectChange;

function renderEjMetrics(){
  const dept = ejSelectedDept;
  const rows = dept ? DATA.filter(r => r.departamento === dept) : DATA;
  
  const nda = rows.reduce((s, r) => s + (r.ej_nda || 0), 0);
  const mc = rows.reduce((s, r) => s + (r.ej_mc || 0), 0);
  const jud = rows.reduce((s, r) => s + (r.ej_judicial || 0), 0);
  const apa = rows.reduce((s, r) => s + (r.ej_apa || 0), 0);
  const res = rows.reduce((s, r) => s + (r.ej_reserva || 0), 0);
  const ins = rows.reduce((s, r) => s + (r.ej_insan || 0), 0);
  const tot = nda + mc + jud + apa + res + ins;
  const munisAtendidos = rows.filter(r => (r.ej_nda || r.ej_mc || r.ej_judicial || r.ej_apa || r.ej_reserva || r.ej_insan)).length;

  const wrap = document.getElementById('ej-metrics-wrap');
  if(!wrap) return;
  wrap.innerHTML = `
    <div class="active-scope-badge">
      <span class="scope-tag">
        <span style="font-size:15px">📊</span>
        <span>Totales Dinámicos:</span>
        <strong style="color:var(--blue);font-size:13px">${dept ? 'Departamento de ' + dept : 'Nivel Nacional (22 Departamentos)'}</strong>
        <span class="dept-muni-count">${rows.length} municipios</span>
      </span>
      ${dept ? `<button class="scope-reset-btn" onclick="selectEjDept(null)">✕ Ver Totales Nacionales</button>` : `<span style="font-size:11px;color:var(--text-2);font-weight:500">Haz clic en cualquier departamento para ver sus métricas</span>`}
    </div>
    <div class="metric-cards-grid">
      <div class="metric-glass-card ${ejInt==='all'?'active-metric':''}" onclick="setEjInt('all')" title="Ver todas las intervenciones ejecutadas">
        <div class="m-label"><span>Total Ejecutado</span><span>📦</span></div>
        <div class="m-value">${fmtN(tot)}</div>
        <div class="m-sub">${munisAtendidos} de ${rows.length} munis atendidos</div>
      </div>
      <div class="metric-glass-card ${ejInt==='nda'?'active-metric':''}" onclick="setEjInt('nda')" title="Filtrar por modalidad NDA">
        <div class="m-label"><span>NDA</span><span>🏷️</span></div>
        <div class="m-value">${fmtN(nda)}</div>
        <div class="m-sub">${rows.filter(r=>r.ej_nda).length} munis con entrega</div>
      </div>
      <div class="metric-glass-card ${ejInt==='mc'?'active-metric':''}" onclick="setEjInt('mc')" title="Filtrar por modalidad MC">
        <div class="m-label"><span>MC</span><span>🛒</span></div>
        <div class="m-value">${fmtN(mc)}</div>
        <div class="m-sub">${rows.filter(r=>r.ej_mc).length} munis modalidad compra</div>
      </div>
      <div class="metric-glass-card ${ejInt==='judicial'?'active-metric':''}" onclick="setEjInt('judicial')" title="Filtrar por Judicial">
        <div class="m-label"><span>Judicial</span><span>⚖️</span></div>
        <div class="m-value">${fmtN(jud)}</div>
        <div class="m-sub">${rows.filter(r=>r.ej_judicial).length} munis vía judicial</div>
      </div>
      <div class="metric-glass-card ${ejInt==='apa'?'active-metric':''}" onclick="setEjInt('apa')" title="Filtrar por APA">
        <div class="m-label"><span>APA</span><span>🌾</span></div>
        <div class="m-value">${fmtN(apa)}</div>
        <div class="m-sub">${rows.filter(r=>r.ej_apa).length} munis atendidos</div>
      </div>
      <div class="metric-glass-card ${ejInt==='reserva'?'active-metric':''}" onclick="setEjInt('reserva')" title="Filtrar por Reserva">
        <div class="m-label"><span>Reserva</span><span>🏛️</span></div>
        <div class="m-value">${fmtN(res)}</div>
        <div class="m-sub">${rows.filter(r=>r.ej_reserva).length} munis con reserva</div>
      </div>
      <div class="metric-glass-card ${ejInt==='insan'?'active-metric':''}" onclick="setEjInt('insan')" title="Filtrar por INSAN">
        <div class="m-label"><span>INSAN</span><span>⚠️</span></div>
        <div class="m-value">${fmtN(ins)}</div>
        <div class="m-sub">${rows.filter(r=>r.ej_insan).length} munis INSAN</div>
      </div>
    </div>
  `;
}

function renderEjTable(){
  const q=(document.getElementById('ej-search').value||'').toLowerCase();
  const dept=document.getElementById('ej-dept').value;
  const vuln=document.getElementById('ej-vuln').value;

  let filtered = DATA.filter(r=>{
    if(dept && r.departamento !== dept) return false;
    if(vuln && r.color !== vuln) return false;
    if(q && !r.municipio.toLowerCase().includes(q) && !r.departamento.toLowerCase().includes(q)) return false;
    if(ejInt !== 'all'){
      const d = EJ_INTS.find(x => x.id === ejInt);
      if(d && !r[d.vk]) return false;
    }
    return true;
  });

  const activeInts = ejInt === 'all' ? EJ_INTS.slice(1) : EJ_INTS.filter(d => d.id === ejInt);
  const tbl = document.getElementById('ej-table');

  // Build Table Header
  let theadHTML = `<tr>
    <th style="min-width:230px">Departamento / Municipio</th>
    <th style="min-width:130px">Vulnerabilidad</th>
    <th style="min-width:110px">Tipo</th>`;
  activeInts.forEach(m => {
    theadHTML += `<th style="text-align:right">${m.l} Cant.</th><th style="min-width:95px">${m.l} Fecha</th>`;
  });
  theadHTML += `</tr>`;
  tbl.querySelector('thead').innerHTML = theadHTML;

  if(!filtered.length){
    tbl.querySelector('tbody').innerHTML = `<tr><td colspan="${3 + activeInts.length * 2}" style="text-align:center;padding:40px;color:var(--text-2)">Sin registros para los filtros aplicados.</td></tr>`;
    document.getElementById('ej-pg').innerHTML = `<span>0 registros encontrados</span>`;
    return;
  }

  // Group by Departamento
  const deptsInView = [...new Set(filtered.map(r => r.departamento))].sort();
  const isSearching = q.length > 0;

  let tbodyHTML = '';
  deptsInView.forEach(d => {
    const dRows = filtered.filter(r => r.departamento === d);
    const rCount = dRows.filter(r => r.color === 'FFFF0000').length;
    const oCount = dRows.filter(r => r.color === 'FFFF8001').length;
    const yCount = dRows.filter(r => r.color === 'FFFFFF00').length;
    const modSums = activeInts.map(m => dRows.reduce((s, r) => s + (r[m.vk] || 0), 0));

    const isExp = isSearching || ejExpandedDepts.has(d);
    const isSel = (ejSelectedDept === d);

    // Parent Department Summary Row (Amounts only, no dates)
    tbodyHTML += `<tr class="dept-pivot-row ${isExp ? 'is-expanded' : ''} ${isSel ? 'is-selected' : ''}" onclick="toggleDeptExpand('ej', '${d.replace(/'/g, "\\'")}')">
      <td>
        <div class="dept-pivot-title">
          <span class="dept-toggle-icon">${isExp ? '▼' : '▶'}</span>
          <span class="dept-name-text">${d}</span>
          <span class="dept-muni-count">${dRows.length} munis</span>
          <button class="dept-select-action ${isSel ? 'selected' : ''}" onclick="event.stopPropagation(); selectEjDept('${d.replace(/'/g, "\\'")}')">
            ${isSel ? '✓ Activo' : 'Seleccionar'}
          </button>
        </div>
      </td>
      <td>
        <div class="dept-vuln-summary-pills">
          ${rCount ? `<span class="dept-v-pill r" title="${rCount} municipios Muy Alta">● ${rCount}</span>` : ''}
          ${oCount ? `<span class="dept-v-pill o" title="${oCount} municipios Alta">● ${oCount}</span>` : ''}
          ${yCount ? `<span class="dept-v-pill y" title="${yCount} municipios Media">● ${yCount}</span>` : ''}
        </div>
      </td>
      <td><span style="font-size:11px;font-weight:700;color:var(--text-2)">Totales Depto.</span></td>`;

    modSums.forEach(sumVal => {
      tbodyHTML += `<td style="text-align:right"><span class="dept-total-val">${fmtN(sumVal)}</span></td>
                    <td><span class="dept-blank-col">—</span></td>`;
    });
    tbodyHTML += `</tr>`;

    // Municipality Child Rows (when expanded)
    if(isExp){
      dRows.forEach(r => {
        const vulnCls = r.color === 'FFFF0000' ? 'vuln-muy-alta' : r.color === 'FFFF8001' ? 'vuln-alta' : 'vuln-media';
        const badgeCls = r.color === 'FFFF0000' ? 'r' : r.color === 'FFFF8001' ? 'o' : 'y';

        tbodyHTML += `<tr class="muni-child-row ${vulnCls}">
          <td>
            <div class="muni-wrap muni-child-indent">
              <span class="muni-tree-line"></span>
              <span class="vuln-pip" style="background:${vcolor(r.color)};width:10px;height:10px;border-radius:50%;box-shadow:0 0 0 2px #fff"></span>
              <div>
                <div class="muni-name" style="font-weight:700">
                  ${r.municipio}
                  ${canEditRow('ej', r) ? `<button class="muni-edit-action-btn" onclick="event.stopPropagation(); openEditMuniModal(${r.cod_mun}, 'ej')" title="Editar datos de ${r.municipio}">✏️ Editar</button>` : ''}
                </div>
                <div class="muni-dept" style="font-size:10px;opacity:0.75">${r.departamento}</div>
              </div>
            </div>
          </td>
          <td><span class="muni-badge-highlight ${badgeCls}">● ${vname(r.color)}</span></td>
          <td><span style="font-size:11px;color:var(--text-2);font-weight:600">${r.atendido_prog || '—'}</span></td>`;

        activeInts.forEach(m => {
          const cellQtyAttr = canEditField(m.vk, r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, '${m.vk}', '${m.l} Cant.', true)" title="Clic para editar ${m.l} Cant."` : '';
          const cellDateAttr = canEditField(m.dk, r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, '${m.dk}', '${m.l} Fecha', false)" title="Clic para editar ${m.l} Fecha"` : '';

          tbodyHTML += `<td style="text-align:right" ${cellQtyAttr}><span class="n-val">${fmtN(r[m.vk])}</span></td>
                        <td ${cellDateAttr}><span class="d-val">${fmtD(r[m.dk])}</span></td>`;
        });
        tbodyHTML += `</tr>`;
      });
    }
  });

  tbl.querySelector('tbody').innerHTML = tbodyHTML;

  // Footer summary
  const expCount = ejExpandedDepts.size;
  document.getElementById('ej-pg').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:10px">
      <span>Mostrando <strong>${deptsInView.length}</strong> departamentos · <strong>${filtered.length}</strong> municipios (${expCount} departamentos desplegados)</span>
      <div style="display:flex;gap:6px">
        <button class="glass-btn" onclick="toggleAllDepts('ej')">⊞ Alternar Despliegue</button>
        ${ejSelectedDept ? `<button class="glass-btn" style="color:var(--blue);border-color:var(--blue)" onclick="selectEjDept(null)">✕ Ver Nacional</button>` : ''}
      </div>
    </div>
  `;
}
window.renderEjTable = renderEjTable;

// ── PROGRAMACIÓN MODULE ──
const PROG_INTS=[
  {id:'all',l:'Todas'},
  {id:'nda',l:'NDA',vk:'prog_nda',dk:'prog_nda_fecha',desc:'NDA Nacional'},
  {id:'mc',l:'MC',vk:'prog_mc',dk:'prog_mc_fecha',desc:'Medida Cautelar'},
  {id:'judicial',l:'Judicial',vk:'prog_judicial',dk:'prog_judicial_fecha',desc:'Medida Transitoria / Judicial'},
  {id:'apa',l:'APA',vk:'prog_apa',dk:'prog_apa_fecha',desc:'Alimentos por Acciones'},
  {id:'reserva',l:'Reserva',vk:'prog_reserva',dk:'prog_reserva_fecha',desc:'Reserva Estratégica'},
  {id:'insan',l:'INSAN',vk:'prog_insan',dk:'prog_insan_fecha',desc:'Inseguridad Alimentaria'}
];
let progInt='all';

function buildProgChips(){
  document.getElementById('prog-chips').innerHTML=PROG_INTS.map(d=>`
    <button class="chip ${d.id===progInt?'active':''}" onclick="setProgInt('${d.id}')">${d.l}</button>
  `).join('');
}
function setProgInt(id){
  progInt=id;
  buildProgChips();
  renderProgMetrics();
  renderProgTable();
}
window.setProgInt=setProgInt;

function selectProgDept(dept){
  progSelectedDept = dept;
  const sel = document.getElementById('prog-dept');
  if(sel) sel.value = dept || '';
  if(dept) progExpandedDepts.add(dept);
  renderProgMetrics();
  renderProgTable();
}
window.selectProgDept = selectProgDept;

function onProgDeptSelectChange(){
  const val = document.getElementById('prog-dept').value || null;
  selectProgDept(val);
}
window.onProgDeptSelectChange = onProgDeptSelectChange;

function renderProgMetrics(){
  const dept = progSelectedDept;
  const rows = dept ? DATA.filter(r => r.departamento === dept) : DATA;
  
  const nda = rows.reduce((s, r) => s + progOf(r, 'nda'), 0);
  const mc = rows.reduce((s, r) => s + progOf(r, 'mc'), 0);
  const jud = rows.reduce((s, r) => s + progOf(r, 'judicial'), 0);
  const apa = rows.reduce((s, r) => s + progOf(r, 'apa'), 0);
  const res = rows.reduce((s, r) => s + progOf(r, 'reserva'), 0);
  const ins = rows.reduce((s, r) => s + progOf(r, 'insan'), 0);
  const tot = nda + mc + jud + apa + res + ins;
  const munisProg = rows.filter(r => PROG_KEYS.some(k => progOf(r, k))).length;

  const wrap = document.getElementById('prog-metrics-wrap');
  if(!wrap) return;
  wrap.innerHTML = `
    <div class="active-scope-badge">
      <span class="scope-tag">
        <span style="font-size:15px">📋</span>
        <span>Totales Dinámicos:</span>
        <strong style="color:var(--blue);font-size:13px">${dept ? 'Departamento de ' + dept : 'Nivel Nacional (22 Departamentos)'}</strong>
        <span class="dept-muni-count">${rows.length} municipios</span>
      </span>
      ${dept ? `<button class="scope-reset-btn" onclick="selectProgDept(null)">✕ Ver Totales Nacionales</button>` : `<span style="font-size:11px;color:var(--text-2);font-weight:500">Haz clic en cualquier departamento para ver sus métricas</span>`}
    </div>
    <div class="metric-cards-grid">
      <div class="metric-glass-card ${progInt==='all'?'active-metric':''}" onclick="setProgInt('all')" title="Ver todas las intervenciones programadas">
        <div class="m-label"><span>Total Programado</span><span>📋</span></div>
        <div class="m-value">${fmtN(tot)}</div>
        <div class="m-sub">${munisProg} de ${rows.length} munis con meta</div>
      </div>
      <div class="metric-glass-card ${progInt==='nda'?'active-metric':''}" onclick="setProgInt('nda')" title="Filtrar por NDA Programado">
        <div class="m-label"><span>NDA Prog.</span><span>🏷️</span></div>
        <div class="m-value">${fmtN(nda)}</div>
        <div class="m-sub">${rows.filter(r=>progOf(r,'nda')).length} munis programados</div>
      </div>
      <div class="metric-glass-card ${progInt==='mc'?'active-metric':''}" onclick="setProgInt('mc')" title="Filtrar por MC Programado">
        <div class="m-label"><span>MC Prog.</span><span>🛒</span></div>
        <div class="m-value">${fmtN(mc)}</div>
        <div class="m-sub">${rows.filter(r=>progOf(r,'mc')).length} munis programados</div>
      </div>
      <div class="metric-glass-card ${progInt==='judicial'?'active-metric':''}" onclick="setProgInt('judicial')" title="Filtrar por Judicial Programado">
        <div class="m-label"><span>Judicial Prog.</span><span>⚖️</span></div>
        <div class="m-value">${fmtN(jud)}</div>
        <div class="m-sub">${rows.filter(r=>progOf(r,'judicial')).length} munis programados</div>
      </div>
      <div class="metric-glass-card ${progInt==='apa'?'active-metric':''}" onclick="setProgInt('apa')" title="Filtrar por APA Programado">
        <div class="m-label"><span>APA Prog.</span><span>🌾</span></div>
        <div class="m-value">${fmtN(apa)}</div>
        <div class="m-sub">${rows.filter(r=>progOf(r,'apa')).length} munis programados</div>
      </div>
      <div class="metric-glass-card ${progInt==='reserva'?'active-metric':''}" onclick="setProgInt('reserva')" title="Filtrar por Reserva Programada">
        <div class="m-label"><span>Reserva Prog.</span><span>🏛️</span></div>
        <div class="m-value">${fmtN(res)}</div>
        <div class="m-sub">${rows.filter(r=>progOf(r,'reserva')).length} munis programados</div>
      </div>
      <div class="metric-glass-card ${progInt==='insan'?'active-metric':''}" onclick="setProgInt('insan')" title="Filtrar por INSAN Programada">
        <div class="m-label"><span>INSAN Prog.</span><span>⚠️</span></div>
        <div class="m-value">${fmtN(ins)}</div>
        <div class="m-sub">${rows.filter(r=>progOf(r,'insan')).length} munis programados</div>
      </div>
    </div>
  `;
}

function renderProgTable(){
  const q=(document.getElementById('prog-search').value||'').toLowerCase();
  const dept=document.getElementById('prog-dept').value;
  const vuln=document.getElementById('prog-vuln').value;

  let filtered = DATA.filter(r=>{
    if(dept && r.departamento !== dept) return false;
    if(vuln && r.color !== vuln) return false;
    if(q && !r.municipio.toLowerCase().includes(q) && !r.departamento.toLowerCase().includes(q)) return false;
    if(progInt !== 'all'){
      if(!progOf(r, progInt)) return false;
    }
    return true;
  });

  const activeInts = progInt === 'all' ? PROG_INTS.slice(1) : PROG_INTS.filter(d => d.id === progInt);
  const tbl = document.getElementById('prog-table');

  // Build Table Header
  let theadHTML = `<tr>
    <th style="min-width:230px">Departamento / Municipio</th>
    <th style="min-width:130px">Vulnerabilidad</th>
    <th style="min-width:85px">Año Sol.</th>
    <th style="min-width:110px">Tipo</th>`;
  activeInts.forEach(m => {
    theadHTML += `<th style="text-align:right">${m.l} Cant.</th><th style="min-width:105px">${m.l} Fecha</th>`;
  });
  theadHTML += `</tr>`;
  tbl.querySelector('thead').innerHTML = theadHTML;

  if(!filtered.length){
    tbl.querySelector('tbody').innerHTML = `<tr><td colspan="${4 + activeInts.length * 2}" style="text-align:center;padding:40px;color:var(--text-2)">Sin registros para los filtros aplicados.</td></tr>`;
    document.getElementById('prog-pg').innerHTML = `<span>0 registros encontrados</span>`;
    return;
  }

  // Group by Departamento
  const deptsInView = [...new Set(filtered.map(r => r.departamento))].sort();
  const isSearching = q.length > 0;

  let tbodyHTML = '';
  deptsInView.forEach(d => {
    const dRows = filtered.filter(r => r.departamento === d);
    const rCount = dRows.filter(r => r.color === 'FFFF0000').length;
    const oCount = dRows.filter(r => r.color === 'FFFF8001').length;
    const yCount = dRows.filter(r => r.color === 'FFFFFF00').length;
    const modSums = activeInts.map(m => dRows.reduce((s, r) => s + progOf(r, m.id), 0));

    const isExp = isSearching || progExpandedDepts.has(d);
    const isSel = (progSelectedDept === d);

    // Parent Department Summary Row
    tbodyHTML += `<tr class="dept-pivot-row ${isExp ? 'is-expanded' : ''} ${isSel ? 'is-selected' : ''}" onclick="toggleDeptExpand('prog', '${d.replace(/'/g, "\\'")}')">
      <td>
        <div class="dept-pivot-title">
          <span class="dept-toggle-icon">${isExp ? '▼' : '▶'}</span>
          <span class="dept-name-text">${d}</span>
          <span class="dept-muni-count">${dRows.length} munis</span>
          <button class="dept-select-action ${isSel ? 'selected' : ''}" onclick="event.stopPropagation(); selectProgDept('${d.replace(/'/g, "\\'")}')">
            ${isSel ? '✓ Activo' : 'Seleccionar'}
          </button>
        </div>
      </td>
      <td>
        <div class="dept-vuln-summary-pills">
          ${rCount ? `<span class="dept-v-pill r" title="${rCount} municipios Muy Alta">● ${rCount}</span>` : ''}
          ${oCount ? `<span class="dept-v-pill o" title="${oCount} municipios Alta">● ${oCount}</span>` : ''}
          ${yCount ? `<span class="dept-v-pill y" title="${yCount} municipios Media">● ${yCount}</span>` : ''}
        </div>
      </td>
      <td><span class="dept-blank-col">—</span></td>
      <td><span style="font-size:11px;font-weight:700;color:var(--text-2)">Totales Depto.</span></td>`;

    modSums.forEach(sumVal => {
      tbodyHTML += `<td style="text-align:right"><span class="dept-total-val">${fmtN(sumVal)}</span></td>
                    <td><span class="dept-blank-col">—</span></td>`;
    });
    tbodyHTML += `</tr>`;

    // Municipality Child Rows
    if(isExp){
      dRows.forEach(r => {
        const vulnCls = r.color === 'FFFF0000' ? 'vuln-muy-alta' : r.color === 'FFFF8001' ? 'vuln-alta' : 'vuln-media';
        const badgeCls = r.color === 'FFFF0000' ? 'r' : r.color === 'FFFF8001' ? 'o' : 'y';

        const cellAnioAttr = canEditField('prog_anio', r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, 'prog_anio', 'Año Sol.', true)" title="Clic para editar Año Sol."` : '';

        tbodyHTML += `<tr class="muni-child-row ${vulnCls}">
          <td>
            <div class="muni-wrap muni-child-indent">
              <span class="muni-tree-line"></span>
              <span class="vuln-pip" style="background:${vcolor(r.color)};width:10px;height:10px;border-radius:50%;box-shadow:0 0 0 2px #fff"></span>
              <div>
                <div class="muni-name" style="font-weight:700">
                  ${r.municipio}
                  ${canEditRow('prog', r) ? `<button class="muni-edit-action-btn" onclick="event.stopPropagation(); openEditMuniModal(${r.cod_mun}, 'prog')" title="Editar datos de ${r.municipio}">✏️ Editar</button>` : ''}
                </div>
                <div class="muni-dept" style="font-size:10px;opacity:0.75">${r.departamento}</div>
              </div>
            </div>
          </td>
          <td><span class="muni-badge-highlight ${badgeCls}">● ${vname(r.color)}</span></td>
          <td ${cellAnioAttr}><span class="d-val">${r.prog_anio || '—'}</span></td>
          <td><span style="font-size:11px;color:var(--text-2);font-weight:600">${r.atendido_prog || '—'}</span></td>`;

        activeInts.forEach(m => {
          const v = r[m.dk];
          const dateHTML = v && v.includes('SOLICITUD')
            ? `<span style="font-size:10px;background:#dbeafe;color:var(--blue);padding:2px 6px;border-radius:4px;font-weight:700">SOLICITUD NUEVA</span>`
            : `<span class="d-val">${fmtD(v)}</span>`;

          const cellQtyAttr = canEditField(m.vk, r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, '${m.vk}', '${m.l} Prog. Cant.', true)" title="Clic para editar ${m.l} Prog. Cant."` : '';
          const cellDateAttr = canEditField(m.dk, r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, '${m.dk}', '${m.l} Prog. Fecha', false)" title="Clic para editar ${m.l} Prog. Fecha"` : '';

          const sinFecha = r[m.vk] && !tieneFechaProg(r, m.id);
          tbodyHTML += `<td style="text-align:right" ${cellQtyAttr}><span class="n-val${sinFecha ? ' n-sin-fecha' : ''}"${sinFecha ? ' title="Sin fecha de programación: no se suma en los totales"' : ''}>${fmtN(r[m.vk])}</span></td>
                        <td ${cellDateAttr}>${dateHTML}</td>`;
        });
        tbodyHTML += `</tr>`;
      });
    }
  });

  tbl.querySelector('tbody').innerHTML = tbodyHTML;

  const expCount = progExpandedDepts.size;
  document.getElementById('prog-pg').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:10px">
      <span>Mostrando <strong>${deptsInView.length}</strong> departamentos · <strong>${filtered.length}</strong> municipios (${expCount} departamentos desplegados)</span>
      <div style="display:flex;gap:6px">
        <button class="glass-btn" onclick="toggleAllDepts('prog')">⊞ Alternar Despliegue</button>
        ${progSelectedDept ? `<button class="glass-btn" style="color:var(--blue);border-color:var(--blue)" onclick="selectProgDept(null)">✕ Ver Nacional</button>` : ''}
      </div>
    </div>
  `;
}
window.renderProgTable = renderProgTable;

// ── CONRED MODULE ──
function selectConredDept(dept){
  conredSelectedDept = dept;
  const sel = document.getElementById('conred-dept');
  if(sel) sel.value = dept || '';
  if(dept) conredExpandedDepts.add(dept);
  renderConredMetrics();
  renderConredTable();
}
window.selectConredDept = selectConredDept;

function onConredDeptSelectChange(){
  const val = document.getElementById('conred-dept').value || null;
  selectConredDept(val);
}
window.onConredDeptSelectChange = onConredDeptSelectChange;

function renderConredMetrics(){
  const dept = conredSelectedDept;
  const rows = dept ? DATA.filter(r => r.departamento === dept) : DATA;
  const totConred = rows.reduce((s, r) => s + (r.conred || 0), 0);
  const munisConred = rows.filter(r => r.conred).length;
  const munisSol = rows.filter(r => r.conred_solicitud).length;

  const wrap = document.getElementById('conred-metrics-wrap');
  if(!wrap) return;
  wrap.innerHTML = `
    <div class="active-scope-badge">
      <span class="scope-tag">
        <span style="font-size:15px">🛡️</span>
        <span>Totales Dinámicos CONRED:</span>
        <strong style="color:var(--blue);font-size:13px">${dept ? 'Departamento de ' + dept : 'Nivel Nacional (22 Departamentos)'}</strong>
        <span class="dept-muni-count">${rows.length} municipios</span>
      </span>
      ${dept ? `<button class="scope-reset-btn" onclick="selectConredDept(null)">✕ Ver Totales Nacionales</button>` : `<span style="font-size:11px;color:var(--text-2);font-weight:500">Haz clic en cualquier departamento para ver sus métricas</span>`}
    </div>
    <div class="metric-cards-grid">
      <div class="metric-glass-card active-metric">
        <div class="m-label"><span>Total CONRED</span><span>📦</span></div>
        <div class="m-value">${fmtN(totConred)}</div>
        <div class="m-sub">${munisConred} municipios con volumen</div>
      </div>
      <div class="metric-glass-card">
        <div class="m-label"><span>Solicitudes Activas</span><span>📑</span></div>
        <div class="m-value">${fmtN(munisSol)}</div>
        <div class="m-sub">Solicitudes registradas</div>
      </div>
      <div class="metric-glass-card">
        <div class="m-label"><span>Municipios Ámbito</span><span>📍</span></div>
        <div class="m-value">${fmtN(rows.length)}</div>
        <div class="m-sub">En evaluación institucional</div>
      </div>
      <div class="metric-glass-card">
        <div class="m-label"><span>Estado Matriz</span><span>⏳</span></div>
        <div class="m-value" style="font-size:15px;color:var(--warn-m)">Pendiente Carga</div>
        <div class="m-sub">Corte al ${fmtD((window.VISAN_META||{}).fecha_corte)}</div>
      </div>
    </div>
  `;
}

function renderConredTable(){
  const q=(document.getElementById('conred-search').value||'').toLowerCase();
  const dept=document.getElementById('conred-dept') ? document.getElementById('conred-dept').value : '';
  const vuln=document.getElementById('conred-vuln').value;

  let filtered = DATA.filter(r=>{
    if(dept && r.departamento !== dept) return false;
    if(vuln && r.color !== vuln) return false;
    if(q && !r.municipio.toLowerCase().includes(q) && !r.departamento.toLowerCase().includes(q)) return false;
    return true;
  });

  const tbl = document.getElementById('conred-tbl');
  tbl.querySelector('thead').innerHTML = `<tr>
    <th style="min-width:230px">Departamento / Municipio</th>
    <th style="min-width:130px">Vulnerabilidad</th>
    <th style="min-width:110px">Tipo</th>
    <th style="text-align:right">CONRED Cantidad</th>
    <th style="min-width:110px">Solicitud</th>
    <th style="min-width:110px">Fecha Prog.</th>
  </tr>`;

  if(!filtered.length){
    document.getElementById('conred-tbody').innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-2)">Sin registros para los filtros aplicados.</td></tr>`;
    document.getElementById('conred-pg').innerHTML = `<span>0 registros encontrados</span>`;
    return;
  }

  const deptsInView = [...new Set(filtered.map(r => r.departamento))].sort();
  const isSearching = q.length > 0;

  let tbodyHTML = '';
  deptsInView.forEach(d => {
    const dRows = filtered.filter(r => r.departamento === d);
    const rCount = dRows.filter(r => r.color === 'FFFF0000').length;
    const oCount = dRows.filter(r => r.color === 'FFFF8001').length;
    const yCount = dRows.filter(r => r.color === 'FFFFFF00').length;
    const conredSum = dRows.reduce((s, r) => s + (r.conred || 0), 0);

    const isExp = isSearching || conredExpandedDepts.has(d);
    const isSel = (conredSelectedDept === d);

    // Parent Department Summary Row
    tbodyHTML += `<tr class="dept-pivot-row ${isExp ? 'is-expanded' : ''} ${isSel ? 'is-selected' : ''}" onclick="toggleDeptExpand('conred', '${d.replace(/'/g, "\\'")}')">
      <td>
        <div class="dept-pivot-title">
          <span class="dept-toggle-icon">${isExp ? '▼' : '▶'}</span>
          <span class="dept-name-text">${d}</span>
          <span class="dept-muni-count">${dRows.length} munis</span>
          <button class="dept-select-action ${isSel ? 'selected' : ''}" onclick="event.stopPropagation(); selectConredDept('${d.replace(/'/g, "\\'")}')">
            ${isSel ? '✓ Activo' : 'Seleccionar'}
          </button>
        </div>
      </td>
      <td>
        <div class="dept-vuln-summary-pills">
          ${rCount ? `<span class="dept-v-pill r" title="${rCount} municipios Muy Alta">● ${rCount}</span>` : ''}
          ${oCount ? `<span class="dept-v-pill o" title="${oCount} municipios Alta">● ${oCount}</span>` : ''}
          ${yCount ? `<span class="dept-v-pill y" title="${yCount} municipios Media">● ${yCount}</span>` : ''}
        </div>
      </td>
      <td><span style="font-size:11px;font-weight:700;color:var(--text-2)">Totales Depto.</span></td>
      <td style="text-align:right"><span class="dept-total-val">${fmtN(conredSum)}</span></td>
      <td><span class="dept-blank-col">—</span></td>
      <td><span class="dept-blank-col">—</span></td>
    </tr>`;

    // Municipality Child Rows
    if(isExp){
      dRows.forEach(r => {
        const vulnCls = r.color === 'FFFF0000' ? 'vuln-muy-alta' : r.color === 'FFFF8001' ? 'vuln-alta' : 'vuln-media';
        const badgeCls = r.color === 'FFFF0000' ? 'r' : r.color === 'FFFF8001' ? 'o' : 'y';

        const cellQtyAttr = canEditField('conred', r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, 'conred', 'CONRED Cantidad', true)" title="Clic para editar CONRED Cantidad"` : '';
        const cellSolAttr = canEditField('conred_solicitud', r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, 'conred_solicitud', 'CONRED Solicitud', false)" title="Clic para editar Solicitud"` : '';
        const cellDateAttr = canEditField('conred_fecha', r) ? `class="editable-cell" onclick="event.stopPropagation(); promptEditCell(${r.cod_mun}, 'conred_fecha', 'CONRED Fecha Prog.', false)" title="Clic para editar Fecha Prog."` : '';

        tbodyHTML += `<tr class="muni-child-row ${vulnCls}">
          <td>
            <div class="muni-wrap muni-child-indent">
              <span class="muni-tree-line"></span>
              <span class="vuln-pip" style="background:${vcolor(r.color)};width:10px;height:10px;border-radius:50%;box-shadow:0 0 0 2px #fff"></span>
              <div>
                <div class="muni-name" style="font-weight:700">
                  ${r.municipio}
                  ${canEditRow('conred', r) ? `<button class="muni-edit-action-btn" onclick="event.stopPropagation(); openEditMuniModal(${r.cod_mun}, 'conred')" title="Editar datos de ${r.municipio}">✏️ Editar</button>` : ''}
                </div>
                <div class="muni-dept" style="font-size:10px;opacity:0.75">${r.departamento}</div>
              </div>
            </div>
          </td>
          <td><span class="muni-badge-highlight ${badgeCls}">● ${vname(r.color)}</span></td>
          <td><span style="font-size:11px;color:var(--text-2);font-weight:600">${r.atendido_prog || '—'}</span></td>
          <td style="text-align:right" ${cellQtyAttr}><span class="n-val">${fmtN(r.conred)}</span></td>
          <td ${cellSolAttr}><span class="d-val">${fmtD(r.conred_solicitud)}</span></td>
          <td ${cellDateAttr}><span class="d-val">${fmtD(r.conred_fecha)}</span></td>
        </tr>`;
      });
    }
  });

  document.getElementById('conred-tbody').innerHTML = tbodyHTML;

  const expCount = conredExpandedDepts.size;
  document.getElementById('conred-pg').innerHTML = `
    <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:10px">
      <span>Mostrando <strong>${deptsInView.length}</strong> departamentos · <strong>${filtered.length}</strong> municipios (${expCount} departamentos desplegados)</span>
      <div style="display:flex;gap:6px">
        <button class="glass-btn" onclick="toggleAllDepts('conred')">⊞ Alternar Despliegue</button>
        ${conredSelectedDept ? `<button class="glass-btn" style="color:var(--blue);border-color:var(--blue)" onclick="selectConredDept(null)">✕ Ver Nacional</button>` : ''}
      </div>
    </div>
  `;
}
window.renderConredTable = renderConredTable;

// ── ALERTAS FULL PAGE ──
function renderAlertasPage(){
  document.getElementById('alertas-full').innerHTML='<div class="alert-list">'+ALERTS.map(a=>`
    <div class="alert-item ${a.type}" style="padding:14px 16px">
      <div class="alert-dot ${a.type}"></div>
      <div class="alert-content">
        <div class="alert-title" style="font-size:14px">${a.title}</div>
        <div class="alert-body" style="font-size:12px;margin-top:3px">${a.body}</div>
      </div>
      ${a.count?`<div class="alert-count" style="font-size:20px">${fmtN(a.count)}</div>`:''}
    </div>`).join('')+'</div>';
}

// ══════════════════════════════════════════════
// ── MÓDULO BODEGAS Y ALMACENES (Bodegas.xlsx) ──
// ══════════════════════════════════════════════

let currentBodegasView = 'resumen'; // 'resumen', 'cards', 'table', 'balance', 'ficha'
let bodegasFilterBodega = '';
let bodegasFilterConv = '';
let bodegasSearchTerm = '';
let currentEditBodegaRow = null;
let bodegasSortColumn = null;
let bodegasSortDirection = 'asc';

function toggleBodegasSort(column) {
  if (bodegasSortColumn === column) {
    bodegasSortDirection = bodegasSortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    bodegasSortColumn = column;
    bodegasSortDirection = 'asc';
  }
  renderBodegasContent();
}
window.toggleBodegasSort = toggleBodegasSort;

function bodegasSortIconHtml(column) {
  if (bodegasSortColumn !== column) {
    return '<span style="opacity:0.45;font-size:10px;margin-left:5px">⇅</span>';
  }
  return bodegasSortDirection === 'asc'
    ? '<span style="font-size:10px;margin-left:5px">▲</span>'
    : '<span style="font-size:10px;margin-left:5px">▼</span>';
}

let bodegasInvSortColumn = null;
let bodegasInvSortDirection = 'asc';

function toggleBodegasInvSort(column) {
  if (bodegasInvSortColumn === column) {
    bodegasInvSortDirection = bodegasInvSortDirection === 'asc' ? 'desc' : 'asc';
  } else {
    bodegasInvSortColumn = column;
    bodegasInvSortDirection = 'asc';
  }
  renderBodegasContent();
}
window.toggleBodegasInvSort = toggleBodegasInvSort;

function bodegasInvSortIconHtml(column) {
  if (bodegasInvSortColumn !== column) {
    return '<span style="opacity:0.45;font-size:10px;margin-left:5px">⇅</span>';
  }
  return bodegasInvSortDirection === 'asc'
    ? '<span style="font-size:10px;margin-left:5px">▲</span>'
    : '<span style="font-size:10px;margin-left:5px">▼</span>';
}

function recalculateResumenTotales() {
  if (typeof BODEGAS_RESUMEN_CONVENIOS === 'undefined' || !BODEGAS_RESUMEN_CONVENIOS.filas) return;
  let c02 = 0, c03 = 0, c04 = 0, c05 = 0;
  BODEGAS_RESUMEN_CONVENIOS.filas.forEach(r => {
    const v02 = parseInt(r.c02_2026) || 0;
    const v03 = parseInt(r.c03_2026) || 0;
    const v04 = parseInt(r.c04_2026) || 0;
    const v05 = parseInt(r.c05_2026) || 0;
    r.c02_2026 = v02;
    r.c03_2026 = v03;
    r.c04_2026 = v04;
    r.c05_2026 = v05;
    r.total = v02 + v03 + v04 + v05;
    c02 += v02;
    c03 += v03;
    c04 += v04;
    c05 += v05;
  });
  BODEGAS_RESUMEN_CONVENIOS.totales = {
    c02_2026: c02,
    c03_2026: c03,
    c04_2026: c04,
    c05_2026: c05,
    total_general: c02 + c03 + c04 + c05
  };
}
window.recalculateResumenTotales = recalculateResumenTotales;

function loadBodegasEdits() {
  try {
    const server = window.VISAN_BODEGAS || {};
    if (server.inventario) {
      const edits = server.inventario;
      BODEGAS_INVENTARIO.forEach(row => {
        if (edits[row.id]) {
          Object.assign(row, edits[row.id]);
        }
      });
    }
    if (server.resumen) {
      const resEdits = server.resumen;
      if (typeof BODEGAS_RESUMEN_CONVENIOS !== 'undefined' && BODEGAS_RESUMEN_CONVENIOS.filas) {
        BODEGAS_RESUMEN_CONVENIOS.filas.forEach(row => {
          const editKey = resEdits[row.bodega] || (row.bodega_id && resEdits[row.bodega_id]);
          if (editKey) {
            Object.assign(row, editKey);
          }
        });
        recalculateResumenTotales();
      }
    }
  } catch(e) {
    console.error('Error cargando ediciones de bodegas:', e);
  }
}
loadBodegasEdits();

function initBodegas() {
  const totalBodegas = Object.keys(BODEGAS_METADATA).length;
  const cardsCount = document.getElementById('b-tab-cards-count');
  if (cardsCount) cardsCount.textContent = totalBodegas;

  const bSelect = document.getElementById('bodegas-filter-bodega');
  if (bSelect) {
    bSelect.innerHTML = `<option value="">Todas las Bodegas (${totalBodegas})</option>`;
    Object.keys(BODEGAS_METADATA).forEach(bKey => {
      const meta = BODEGAS_METADATA[bKey] || {};
      const opt = document.createElement('option');
      opt.value = bKey;
      opt.textContent = `${meta.icono || '🏢'} ${meta.nombre || bKey} (${meta.entidad || ''} · ${meta.departamento || ''})`;
      bSelect.appendChild(opt);
    });
  }

  const cSelect = document.getElementById('bodegas-filter-conv');
  if (cSelect && cSelect.options.length <= 1) {
    const uniqueConvs = [...new Set(BODEGAS_INVENTARIO.map(r => r.convenio))].filter(Boolean).sort();
    uniqueConvs.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c;
      const prog = BODEGAS_INVENTARIO.find(r => r.convenio === c)?.programa || '';
      opt.textContent = `${c} ${prog ? '· ' + prog : ''}`;
      cSelect.appendChild(opt);
    });
  }

  renderBodegasMetrics();
  renderBodegasContent();
}
window.initBodegas = initBodegas;

function bodegasUltimaActualizacionTexto() {
  const fecha = (window.VISAN_BODEGAS || {}).ultima_actualizacion;
  if (!fecha) return 'Sin ediciones registradas en Bodegas';
  return `Última actualización de Bodegas: ${fmtD(fecha.slice(0, 10))}`;
}

function renderBodegasMetrics() {
  const wrap = document.getElementById('bodegas-kpi-wrap');
  if (!wrap) return;

  let invFiltered = BODEGAS_INVENTARIO;
  if (bodegasFilterBodega) invFiltered = invFiltered.filter(r => r.bodega === bodegasFilterBodega);
  if (bodegasFilterConv) invFiltered = invFiltered.filter(r => r.convenio === bodegasFilterConv);

  const totalDisponibles = invFiltered.reduce((s, r) => s + (r.disponible_raciones || 0), 0);

  const totConvenio = BODEGAS_BALANCE.reduce((s, r) => s + (r.total_convenio || 0), 0);
  const totRecibidas = BODEGAS_BALANCE.reduce((s, r) => s + (r.recibidas || 0), 0);
  const totDespachadas = BODEGAS_BALANCE.reduce((s, r) => s + (r.despachadas || 0), 0);
  const totPendientePMA = BODEGAS_BALANCE.reduce((s, r) => s + (r.pendiente_pma || 0), 0);
  const totalBodegasRed = Object.keys(BODEGAS_METADATA).length;

  const pctRecibidas = totConvenio > 0 ? ((totRecibidas / totConvenio) * 100).toFixed(1) : 0;
  const pctDespachadas = totRecibidas > 0 ? ((totDespachadas / totRecibidas) * 100).toFixed(1) : 0;

  const currentMeta = bodegasFilterBodega ? BODEGAS_METADATA[bodegasFilterBodega] : null;
  const scopeText = currentMeta ? `Almacén: ${currentMeta.nombre} (${currentMeta.entidad || ''})` : `Red Nacional (${totalBodegasRed} Almacenes)`;
  const convText = bodegasFilterConv ? ` · Convenio ${bodegasFilterConv}` : '';

  wrap.innerHTML = `
    <div class="active-scope-badge">
      <div class="scope-tag">
        <span style="font-size:14px">🏬</span>
        <span>Ámbito: <strong>${scopeText}${convText}</strong></span>
        ${bodegasFilterBodega || bodegasFilterConv ? `<span class="scope-count">(${invFiltered.length} registros)</span>` : ''}
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        <span style="font-size:11px;color:var(--text-2)">${bodegasUltimaActualizacionTexto()}</span>
        ${bodegasFilterBodega || bodegasFilterConv ? `<button class="scope-reset-btn" onclick="resetBodegasFilter()">✕ Ver Toda la Red</button>` : ''}
      </div>
    </div>

    <div class="kpi-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); margin-bottom: 0;">
      <div class="kpi-card tone-green" onclick="switchBodegasView('resumen')">
        <div class="kpi-icon">📦</div>
        <div class="kpi-label">Raciones Disponibles</div>
        <div class="kpi-value">${fmtN(totalDisponibles)}</div>
        <div class="kpi-detail">En stock en almacenes</div>
        <div class="kpi-arrow">↗</div>
      </div>
      <div class="kpi-card" onclick="switchBodegasView('resumen')">
        <div class="kpi-icon">📑</div>
        <div class="kpi-label">Total Convenios</div>
        <div class="kpi-value">${fmtN(totConvenio)}</div>
        <div class="kpi-detail">Meta programada PMA</div>
        <div class="kpi-arrow">↗</div>
      </div>
      <div class="kpi-card" onclick="switchBodegasView('balance')">
        <div class="kpi-icon">📥</div>
        <div class="kpi-label">Recibidas en Bodegas</div>
        <div class="kpi-value">${fmtN(totRecibidas)}</div>
        <div class="kpi-detail">${pctRecibidas}% de meta convenios</div>
        <div class="kpi-arrow">↗</div>
      </div>
      <div class="kpi-card tone-orange" onclick="switchBodegasView('balance')">
        <div class="kpi-icon">🚚</div>
        <div class="kpi-label">Raciones Despachadas</div>
        <div class="kpi-value">${fmtN(totDespachadas)}</div>
        <div class="kpi-detail">${pctDespachadas}% de lo recibido</div>
        <div class="kpi-arrow">↗</div>
      </div>
      <div class="kpi-card tone-yellow" onclick="switchBodegasView('balance')">
        <div class="kpi-icon">⏳</div>
        <div class="kpi-label">Pendiente Entrega PMA</div>
        <div class="kpi-value">${fmtN(totPendientePMA)}</div>
        <div class="kpi-detail">Por ingresar a bodegas</div>
        <div class="kpi-arrow">↗</div>
      </div>
      <div class="kpi-card" onclick="switchBodegasView('cards')">
        <div class="kpi-icon">🏢</div>
        <div class="kpi-label">Almacenes en Red</div>
        <div class="kpi-value">${totalBodegasRed}</div>
        <div class="kpi-detail">En 7 departamentos (PMA e INDECA)</div>
        <div class="kpi-arrow">↗</div>
      </div>
    </div>
  `;
}
window.renderBodegasMetrics = renderBodegasMetrics;

function switchBodegasView(view) {
  currentBodegasView = view;
  ['resumen', 'cards', 'table', 'balance', 'ficha'].forEach(v => {
    const btn = document.getElementById(`b-tab-${v}`);
    if (btn) btn.classList.toggle('active', v === view);
  });
  renderBodegasContent();
}
window.switchBodegasView = switchBodegasView;

function onBodegasSearch() {
  bodegasSearchTerm = document.getElementById('bodegas-search').value.trim().toLowerCase();
  renderBodegasContent();
}
window.onBodegasSearch = onBodegasSearch;

function onBodegasFilterChange() {
  bodegasFilterBodega = document.getElementById('bodegas-filter-bodega').value;
  bodegasFilterConv = document.getElementById('bodegas-filter-conv').value;
  const resetBtn = document.getElementById('bodegas-reset-filter-btn');
  if (resetBtn) resetBtn.style.display = (bodegasFilterBodega || bodegasFilterConv) ? 'inline-flex' : 'none';
  renderBodegasMetrics();
  renderBodegasContent();
}
window.onBodegasFilterChange = onBodegasFilterChange;

function resetBodegasFilter() {
  bodegasFilterBodega = '';
  bodegasFilterConv = '';
  bodegasSearchTerm = '';
  const bSelect = document.getElementById('bodegas-filter-bodega');
  const cSelect = document.getElementById('bodegas-filter-conv');
  const sInput = document.getElementById('bodegas-search');
  if (bSelect) bSelect.value = '';
  if (cSelect) cSelect.value = '';
  if (sInput) sInput.value = '';
  const resetBtn = document.getElementById('bodegas-reset-filter-btn');
  if (resetBtn) resetBtn.style.display = 'none';
  renderBodegasMetrics();
  renderBodegasContent();
}
window.resetBodegasFilter = resetBodegasFilter;

function selectBodegaCard(bodegaKey) {
  if (bodegasFilterBodega === bodegaKey) {
    bodegasFilterBodega = '';
  } else {
    bodegasFilterBodega = bodegaKey;
  }
  const bSelect = document.getElementById('bodegas-filter-bodega');
  if (bSelect) bSelect.value = bodegasFilterBodega;
  const resetBtn = document.getElementById('bodegas-reset-filter-btn');
  if (resetBtn) resetBtn.style.display = bodegasFilterBodega ? 'inline-flex' : 'none';
  renderBodegasMetrics();
  renderBodegasContent();
}
window.selectBodegaCard = selectBodegaCard;

function viewBodegaInCards(bodegaKey) {
  bodegasFilterBodega = bodegaKey;
  const bSelect = document.getElementById('bodegas-filter-bodega');
  if (bSelect) bSelect.value = bodegaKey;
  const resetBtn = document.getElementById('bodegas-reset-filter-btn');
  if (resetBtn) resetBtn.style.display = 'inline-flex';
  renderBodegasMetrics();
  switchBodegasView('cards');
}
window.viewBodegaInCards = viewBodegaInCards;

function viewBodegaInTable(bodegaKey) {
  bodegasFilterBodega = bodegaKey;
  const bSelect = document.getElementById('bodegas-filter-bodega');
  if (bSelect) bSelect.value = bodegaKey;
  const resetBtn = document.getElementById('bodegas-reset-filter-btn');
  if (resetBtn) resetBtn.style.display = 'inline-flex';
  switchBodegasView('table');
}
window.viewBodegaInTable = viewBodegaInTable;

async function promptEditResumenCell(bodegaName, fieldKey, fieldLabel) {
  if (!canEditModule('bodegas')) {
    showToast('⚠️ Se requiere rol de Editor para modificar datos.');
    return;
  }
  const row = BODEGAS_RESUMEN_CONVENIOS.filas.find(r => r.bodega === bodegaName || r.bodega_id === bodegaName);
  if (!row) return;

  const currentVal = row[fieldKey] || 0;
  const input = prompt(`Editar raciones para ${row.nombre || bodegaName}\nConvenio: ${fieldLabel}\nValor actual: ${fmtN(currentVal)}`, currentVal);
  if (input === null) return;

  const newVal = parseInt(input.replace(/[,\s]/g, ''), 10);
  if (isNaN(newVal) || newVal < 0) {
    showToast('⚠️ Ingresa una cantidad numérica válida.', 'error');
    return;
  }

  try {
    await api('api/data.php?a=bodegas', { json: { tipo: 'resumen', id: row.bodega, cambios: { [fieldKey]: newVal } } });
  } catch (e) {
    showToast(`No se guardó: ${e.message}`, 'error');
    return;
  }
  row[fieldKey] = newVal;
  recalculateResumenTotales();

  showToast(`✓ Actualizado ${fieldLabel} en ${row.nombre || bodegaName}: ${fmtN(newVal)} raciones.`);
  renderBodegasMetrics();
  renderBodegasContent();
}
window.promptEditResumenCell = promptEditResumenCell;

function renderBodegasResumenConvenios(container) {
  const isEditor = canEditModule('bodegas');
  const convenios = BODEGAS_RESUMEN_CONVENIOS.convenios;
  const totales = BODEGAS_RESUMEN_CONVENIOS.totales;
  const totalGeneral = totales.total_general || 90025;

  let filas = BODEGAS_RESUMEN_CONVENIOS.filas;
  if (bodegasFilterBodega) {
    filas = filas.filter(r => r.bodega === bodegasFilterBodega || r.bodega_id === bodegasFilterBodega);
  }
  if (bodegasSearchTerm) {
    filas = filas.filter(r =>
      r.bodega.toLowerCase().includes(bodegasSearchTerm) ||
      (r.nombre && r.nombre.toLowerCase().includes(bodegasSearchTerm)) ||
      (r.entidad && r.entidad.toLowerCase().includes(bodegasSearchTerm)) ||
      (r.departamento && r.departamento.toLowerCase().includes(bodegasSearchTerm))
    );
  }

  if (bodegasSortColumn) {
    const col = bodegasSortColumn;
    const dir = bodegasSortDirection === 'asc' ? 1 : -1;
    filas = [...filas].sort((a, b) => {
      const metaA = BODEGAS_METADATA[a.bodega_id] || BODEGAS_METADATA[a.bodega] || {};
      const metaB = BODEGAS_METADATA[b.bodega_id] || BODEGAS_METADATA[b.bodega] || {};
      let va, vb;
      switch (col) {
        case 'bodega':
          va = metaA.nombre || a.nombre || a.bodega || '';
          vb = metaB.nombre || b.nombre || b.bodega || '';
          return va.localeCompare(vb, 'es') * dir;
        case 'entidad':
          va = a.entidad || metaA.entidad || '';
          vb = b.entidad || metaB.entidad || '';
          return va.localeCompare(vb, 'es') * dir;
        case 'departamento':
          va = a.departamento || metaA.departamento || '';
          vb = b.departamento || metaB.departamento || '';
          return va.localeCompare(vb, 'es') * dir;
        case 'part':
          va = a.total || 0;
          vb = b.total || 0;
          return (va - vb) * dir;
        default:
          va = a[col] || 0;
          vb = b[col] || 0;
          return (va - vb) * dir;
      }
    });
  }

  let html = `
    <!-- Fila de Tarjetas Ejecutivas por Convenio -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:16px;margin-bottom:20px">
  `;

  convenios.forEach(conv => {
    const cant = totales[conv.key] || 0;
    const pct = totalGeneral > 0 ? ((cant / totalGeneral) * 100).toFixed(1) : 0;
    const bodegasConStock = BODEGAS_RESUMEN_CONVENIOS.filas.filter(r => (r[conv.key] || 0) > 0).length;

    html += `
      <div class="card glass-panel" style="padding:18px 20px;border-left:4px solid ${conv.color};background:linear-gradient(145deg, rgba(255,255,255,0.96), rgba(248,251,255,0.85));box-shadow:0 6px 20px rgba(10,37,84,0.06);position:relative;overflow:hidden">
        <div style="display:flex;align-items:flex-start;margin-bottom:8px">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:22px">${conv.icono || '📦'}</span>
            <div>
              <span style="display:inline-block;background:${conv.badgeBg || '#e0f2fe'};color:${conv.badgeColor || '#0369a1'};font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px;letter-spacing:0.5px">
                CONVENIO ${conv.codigo}
              </span>
              <h4 style="font-size:14px;font-weight:700;color:var(--navy);margin-top:2px;font-family:'Sora',sans-serif">${conv.nombre}</h4>
            </div>
          </div>
        </div>

        <div style="margin:12px 0 8px">
          <div style="font-size:11px;color:var(--text-2);text-transform:uppercase;letter-spacing:0.5px;font-weight:600">Raciones Disponibles</div>
          <div style="font-size:26px;font-weight:800;color:var(--navy);font-family:'Sora',sans-serif;line-height:1.1">
            ${fmtN(cant)}
          </div>
        </div>

        <div style="height:6px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:10px">
          <div style="height:100%;width:${pct}%;background:${conv.color};border-radius:10px;transition:width .5s ease"></div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--text-2)">
          <span>🏬 <strong>${bodegasConStock}</strong> almacenes con stock</span>
          <span style="font-weight:600;color:${conv.color}">Meta Convenio</span>
        </div>
      </div>
    `;
  });

  html += `
    </div>

    <!-- Matriz Territorial de Convenios 2026 -->
    <div class="card glass-panel" style="padding:22px;margin-bottom:24px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:12px">
        <div>
          <div style="display:flex;align-items:center;gap:10px">
            <h3 style="font-size:18px;font-family:'Sora',sans-serif;color:var(--navy)">
              📑 Resumen Territorial de Convenios 2026
            </h3>
            <span style="background:linear-gradient(135deg,#0284c7,#2563eb);color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.5px">
              DISPONIBLE PARA PROGRAMAR
            </span>
          </div>
          <p style="font-size:12px;color:var(--text-2);margin-top:4px">
            Distribución estratégica de raciones disponibles por convenio en la red de ${Object.keys(BODEGAS_METADATA).length} bodegas y almacenes VISAN (PMA e INDECA).
          </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:12px;color:var(--text-2)">Total Raciones en Red:</span>
          <span style="font-size:16px;font-weight:800;background:#e0f2fe;color:#0369a1;padding:4px 12px;border-radius:8px">
            ${fmtN(totalGeneral)} raciones
          </span>
        </div>
      </div>

      <div class="tbl-wrap" style="border-radius:12px;border:1px solid rgba(21,94,239,0.14);overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12.5px;min-width:980px">
          <thead>
            <tr style="background:#133d79;color:#fff">
              <th style="padding:12px 14px;text-align:left;min-width:180px;cursor:pointer;user-select:none" onclick="toggleBodegasSort('bodega')" title="Ordenar por Bodega / Almacén">Bodega / Almacén${bodegasSortIconHtml('bodega')}</th>
              <th style="padding:12px 10px;text-align:center;width:80px;cursor:pointer;user-select:none" onclick="toggleBodegasSort('entidad')" title="Ordenar por Entidad">Entidad${bodegasSortIconHtml('entidad')}</th>
              <th style="padding:12px 12px;text-align:left;min-width:120px;cursor:pointer;user-select:none" onclick="toggleBodegasSort('departamento')" title="Ordenar por Departamento">Departamento${bodegasSortIconHtml('departamento')}</th>
              <th style="padding:12px 12px;text-align:right;background:#1e40af;color:#dbeafe;cursor:pointer;user-select:none" onclick="toggleBodegasSort('c02_2026')" title="Ordenar por Convenio 02-2026 (INSAN)">
                02-2026<br><span style="font-size:10px;font-weight:400;opacity:0.9">INSAN</span>${bodegasSortIconHtml('c02_2026')}
              </th>
              <th style="padding:12px 12px;text-align:right;background:#065f46;color:#d1fae5;cursor:pointer;user-select:none" onclick="toggleBodegasSort('c03_2026')" title="Ordenar por Convenio 03-2026 (Reserva Estratégica)">
                03-2026<br><span style="font-size:10px;font-weight:400;opacity:0.9">Res. Estratégica</span>${bodegasSortIconHtml('c03_2026')}
              </th>
              <th style="padding:12px 12px;text-align:right;background:#92400e;color:#fef3c7;cursor:pointer;user-select:none" onclick="toggleBodegasSort('c04_2026')" title="Ordenar por Convenio 04-2026 (Alimentos por Acciones)">
                04-2026<br><span style="font-size:10px;font-weight:400;opacity:0.9">Alim. Acciones</span>${bodegasSortIconHtml('c04_2026')}
              </th>
              <th style="padding:12px 12px;text-align:right;background:#5b21b6;color:#ede9fe;cursor:pointer;user-select:none" onclick="toggleBodegasSort('c05_2026')" title="Ordenar por Convenio 05-2026 (NDA - MJ - MT - MC)">
                05-2026<br><span style="font-size:10px;font-weight:400;opacity:0.9">NDA·MJ·MT·MC</span>${bodegasSortIconHtml('c05_2026')}
              </th>
              <th style="padding:12px 14px;text-align:right;background:#0f274a;color:#fff;font-weight:800;cursor:pointer;user-select:none" onclick="toggleBodegasSort('total')" title="Ordenar por Total para Programar">
                TOTAL PARA<br>PROGRAMAR${bodegasSortIconHtml('total')}
              </th>
              <th style="padding:12px 12px;text-align:right;cursor:pointer;user-select:none" onclick="toggleBodegasSort('part')" title="Ordenar por Participación %">Part. %${bodegasSortIconHtml('part')}</th>
              <th style="padding:12px 14px;text-align:center;min-width:130px">Composición</th>
              <th style="padding:12px 14px;text-align:center;min-width:100px">Acciones</th>
            </tr>
          </thead>
          <tbody>
  `;

  if (filas.length === 0) {
    html += `
      <tr>
        <td colspan="11" style="padding:30px;text-align:center;color:var(--text-2);background:#fff">
          🔍 No se encontraron bodegas que coincidan con los filtros seleccionados.
        </td>
      </tr>
    `;
  } else {
    filas.forEach(row => {
      const meta = BODEGAS_METADATA[row.bodega_id] || BODEGAS_METADATA[row.bodega] || {};
      const partPct = totalGeneral > 0 ? ((row.total / totalGeneral) * 100).toFixed(1) : 0;
      const isIndeca = (row.entidad || meta.entidad) === 'INDECA';

      // Porcentajes de segmentos para la barra apilada
      const p02 = row.total > 0 ? ((row.c02_2026 / row.total) * 100).toFixed(1) : 0;
      const p03 = row.total > 0 ? ((row.c03_2026 / row.total) * 100).toFixed(1) : 0;
      const p04 = row.total > 0 ? ((row.c04_2026 / row.total) * 100).toFixed(1) : 0;
      const p05 = row.total > 0 ? ((row.c05_2026 / row.total) * 100).toFixed(1) : 0;

      const editCellCursor = isEditor ? 'cursor:pointer;' : '';
      const editCellClass = isEditor ? 'hover-editable' : '';

      html += `
        <tr style="border-bottom:1px solid rgba(226,238,255,0.7);background:rgba(255,255,255,0.75);transition:background .15s" onmouseover="this.style.background='rgba(240,246,255,0.9)'" onmouseout="this.style.background='rgba(255,255,255,0.75)'">
          <td style="padding:12px 14px">
            <div style="display:flex;align-items:center;gap:8px">
              <span style="font-size:18px">${meta.icono || '🏢'}</span>
              <div>
                <strong style="color:var(--navy);font-size:13px">${meta.nombre || row.nombre || row.bodega}</strong>
                <div style="font-size:11px;color:var(--text-2);margin-top:2px;display:flex;align-items:flex-start;gap:4px">
                  <span style="font-size:11px">📍</span>
                  <span>${meta.direccion || meta.tipo || 'Almacén VISAN'}</span>
                </div>
              </div>
            </div>
          </td>
          <td style="padding:12px 10px;text-align:center">
            <span style="background:${isIndeca ? '#ecfdf5' : '#eff6ff'};color:${isIndeca ? '#047857' : '#1d4ed8'};border:1px solid ${isIndeca ? '#a7f3d0' : '#bfdbfe'};font-size:10px;font-weight:800;padding:2px 8px;border-radius:6px;letter-spacing:0.5px">
              ${row.entidad || meta.entidad || 'PMA'}
            </span>
          </td>
          <td style="padding:12px 12px">
            <span style="background:#eaf2fc;color:#1e40af;font-size:11px;font-weight:700;padding:3px 9px;border-radius:12px">
              ${row.departamento || meta.departamento || '-'}
            </span>
          </td>

          <!-- Convenio 02-2026 -->
          <td style="padding:12px 12px;text-align:right;${editCellCursor}" class="${editCellClass}" onclick="${isEditor ? `promptEditResumenCell('${row.bodega_id || row.bodega}', 'c02_2026', 'Convenio 02-2026 / INSAN')` : ''}" title="${isEditor ? 'Clic para editar raciones 02-2026' : ''}">
            ${row.c02_2026 > 0 
              ? `<span style="font-weight:700;color:#1d4ed8;background:#eff6ff;padding:3px 8px;border-radius:6px">${fmtN(row.c02_2026)}</span>` 
              : `<span style="color:#cbd5e1">-</span>`}
          </td>

          <!-- Convenio 03-2026 -->
          <td style="padding:12px 12px;text-align:right;${editCellCursor}" class="${editCellClass}" onclick="${isEditor ? `promptEditResumenCell('${row.bodega_id || row.bodega}', 'c03_2026', 'Convenio 03-2026 / Reserva Estratégica')` : ''}" title="${isEditor ? 'Clic para editar raciones 03-2026' : ''}">
            ${row.c03_2026 > 0 
              ? `<span style="font-weight:700;color:#047857;background:#ecfdf5;padding:3px 8px;border-radius:6px">${fmtN(row.c03_2026)}</span>` 
              : `<span style="color:#cbd5e1">-</span>`}
          </td>

          <!-- Convenio 04-2026 -->
          <td style="padding:12px 12px;text-align:right;${editCellCursor}" class="${editCellClass}" onclick="${isEditor ? `promptEditResumenCell('${row.bodega_id || row.bodega}', 'c04_2026', 'Convenio 04-2026 / Alimentos Por Acciones')` : ''}" title="${isEditor ? 'Clic para editar raciones 04-2026' : ''}">
            ${row.c04_2026 > 0 
              ? `<span style="font-weight:700;color:#b45309;background:#fffbeb;padding:3px 8px;border-radius:6px">${fmtN(row.c04_2026)}</span>` 
              : `<span style="color:#cbd5e1">-</span>`}
          </td>

          <!-- Convenio 05-2026 -->
          <td style="padding:12px 12px;text-align:right;${editCellCursor}" class="${editCellClass}" onclick="${isEditor ? `promptEditResumenCell('${row.bodega_id || row.bodega}', 'c05_2026', 'Convenio 05-2026 / NDA - MJ - MT - MC')` : ''}" title="${isEditor ? 'Clic para editar raciones 05-2026' : ''}">
            ${row.c05_2026 > 0 
              ? `<span style="font-weight:700;color:#6d28d9;background:#f5f3ff;padding:3px 8px;border-radius:6px">${fmtN(row.c05_2026)}</span>` 
              : `<span style="color:#cbd5e1">-</span>`}
          </td>

          <!-- Total Disponible -->
          <td style="padding:12px 14px;text-align:right;font-weight:800;color:var(--navy);font-size:14px;background:#f8fbff">
            ${fmtN(row.total)}
          </td>

          <!-- Participación % -->
          <td style="padding:12px 12px;text-align:right;font-weight:700;color:var(--navy)">
            ${partPct}%
          </td>

          <!-- Barra de Composición Multi-convenio -->
          <td style="padding:12px 14px">
            <div style="height:9px;background:#e2e8f0;border-radius:10px;overflow:hidden;display:flex" title="INSAN: ${p02}% | Reserva: ${p03}% | Acciones: ${p04}% | NDA: ${p05}%">
              ${p02 > 0 ? `<div style="height:100%;width:${p02}%;background:#2563eb" title="02-2026: ${p02}%"></div>` : ''}
              ${p03 > 0 ? `<div style="height:100%;width:${p03}%;background:#059669" title="03-2026: ${p03}%"></div>` : ''}
              ${p04 > 0 ? `<div style="height:100%;width:${p04}%;background:#d97706" title="04-2026: ${p04}%"></div>` : ''}
              ${p05 > 0 ? `<div style="height:100%;width:${p05}%;background:#7c3aed" title="05-2026: ${p05}%"></div>` : ''}
            </div>
          </td>

          <!-- Acciones -->
          <td style="padding:12px 14px;text-align:center">
            <div style="display:flex;justify-content:center;gap:6px">
              <button class="b-card-action-btn" onclick="viewBodegaInCards('${row.bodega_id || row.bodega}')" title="Ver detalles y stock en Almacenes Territoriales">
                🏢 Ver
              </button>
              ${isEditor ? `
                <button class="b-card-action-btn edit-btn" onclick="promptEditResumenCell('${row.bodega_id || row.bodega}', 'c04_2026', 'Convenios')" title="Modificar cantidades">
                  ✏️
                </button>
              ` : ''}
            </div>
          </td>
        </tr>
      `;
    });
  }

  // Totales footer
  const sub02 = filas.reduce((s, r) => s + (r.c02_2026 || 0), 0);
  const sub03 = filas.reduce((s, r) => s + (r.c03_2026 || 0), 0);
  const sub04 = filas.reduce((s, r) => s + (r.c04_2026 || 0), 0);
  const sub05 = filas.reduce((s, r) => s + (r.c05_2026 || 0), 0);
  const subTot = filas.reduce((s, r) => s + (r.total || 0), 0);
  const subPct = totalGeneral > 0 ? ((subTot / totalGeneral) * 100).toFixed(1) : 0;

  html += `
          </tbody>
          <tfoot>
            <tr style="background:#eaf2fc;font-weight:800;color:var(--navy);border-top:2px solid #bcd7fa;font-size:13px">
              <td style="padding:14px">TOTAL NACIONAL</td>
              <td style="padding:14px 10px;text-align:center"><span style="font-size:10px;font-weight:800;color:var(--navy);background:#fff;padding:2px 8px;border-radius:6px;border:1px solid #cbd5e1">${Object.keys(BODEGAS_METADATA).length} RED</span></td>
              <td style="padding:14px 12px">${filas.length} Almacenes (7 Deptos.)</td>
              <td style="padding:14px 12px;text-align:right;color:#1d4ed8">${fmtN(sub02)}</td>
              <td style="padding:14px 12px;text-align:right;color:#047857">${fmtN(sub03)}</td>
              <td style="padding:14px 12px;text-align:right;color:#b45309">${fmtN(sub04)}</td>
              <td style="padding:14px 12px;text-align:right;color:#6d28d9">${fmtN(sub05)}</td>
              <td style="padding:14px 14px;text-align:right;font-size:15px;color:var(--navy);background:#dbeafe">${fmtN(subTot)}</td>
              <td style="padding:14px 12px;text-align:right">${subPct}%</td>
              <td style="padding:14px 14px;text-align:center">
                <span style="font-size:11px;color:var(--text-2);font-weight:600">Red Nacional VISAN</span>
              </td>
              <td style="padding:14px 14px;text-align:center">
                <span style="font-size:11px;color:#0284c7;font-weight:700">100% Activo</span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Leyenda de Convenios al pie -->
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:14px;padding-top:12px;border-top:1px dashed #cbd5e1;font-size:11px;color:var(--text-2)">
        <div style="display:flex;gap:16px;flex-wrap:wrap">
          <span style="display:inline-flex;align-items:center;gap:5px">
            <span style="width:10px;height:10px;background:#2563eb;border-radius:2px;display:inline-block"></span>
            <strong>02-2026:</strong> INSAN (Asistencia Alimentaria)
          </span>
          <span style="display:inline-flex;align-items:center;gap:5px">
            <span style="width:10px;height:10px;background:#059669;border-radius:2px;display:inline-block"></span>
            <strong>03-2026:</strong> Reserva Estratégica
          </span>
          <span style="display:inline-flex;align-items:center;gap:5px">
            <span style="width:10px;height:10px;background:#d97706;border-radius:2px;display:inline-block"></span>
            <strong>04-2026:</strong> Alimentos Por Acciones
          </span>
          <span style="display:inline-flex;align-items:center;gap:5px">
            <span style="width:10px;height:10px;background:#7c3aed;border-radius:2px;display:inline-block"></span>
            <strong>05-2026:</strong> Niñez y Grupos Vulnerables (NDA·MJ·MT·MC)
          </span>
        </div>
        ${isEditor ? `<span style="color:#059669;font-weight:700">✏️ Modo Editor Activo: Haz clic en cualquier celda para editar el valor.</span>` : ''}
      </div>
    </div>
  `;

  container.innerHTML = html;
}
window.renderBodegasResumenConvenios = renderBodegasResumenConvenios;

function renderBodegasContent() {
  const container = document.getElementById('bodegas-content-area');
  if (!container) return;

  if (currentBodegasView === 'resumen') {
    renderBodegasResumenConvenios(container);
  } else if (currentBodegasView === 'cards') {
    renderBodegasCards(container);
  } else if (currentBodegasView === 'table') {
    renderBodegasTable(container);
  } else if (currentBodegasView === 'balance') {
    renderBodegasBalance(container);
  } else if (currentBodegasView === 'ficha') {
    renderBodegasContenido(container);
  }
}
window.renderBodegasContent = renderBodegasContent;

function renderBodegasCards(container) {
  const isEditor = canEditModule('bodegas');
  const totalNacionalStock = BODEGAS_INVENTARIO.reduce((s, r) => s + (r.disponible_raciones || 0), 0);

  const bodegasMap = {};
  Object.keys(BODEGAS_METADATA).forEach(bKey => {
    bodegasMap[bKey] = {
      key: bKey,
      meta: BODEGAS_METADATA[bKey],
      raciones_disponibles: 0,
      arroz: 0,
      frijol: 0,
      azucar: 0,
      aceite: 0,
      maiz: 0,
      avena: 0,
      mezcla: 0,
      convenios: new Set(),
      rows: []
    };
  });

  BODEGAS_INVENTARIO.forEach(row => {
    const k = row.bodega;
    if (!bodegasMap[k]) {
      bodegasMap[k] = {
        key: k,
        meta: BODEGAS_METADATA[k] || { nombre: k, departamento: '', tipo: 'Almacén', icono: '🏢', entidad: 'PMA' },
        raciones_disponibles: 0,
        arroz: 0,
        frijol: 0,
        azucar: 0,
        aceite: 0,
        maiz: 0,
        avena: 0,
        mezcla: 0,
        convenios: new Set(),
        rows: []
      };
    }
    const b = bodegasMap[k];
    b.raciones_disponibles += (row.disponible_raciones || 0);
    b.arroz += (row.arroz_5lb || 0);
    b.frijol += (row.frijol_5lb || 0) + (row.frijol_10lb || 0);
    b.azucar += (row.azucar_500g || 0);
    b.aceite += (row.aceite_800ml || 0);
    b.maiz += (row.maiz_blanco_25lb || 0);
    b.avena += (row.avena_1kg || 0);
    b.mezcla += (row.mezcla_900g || 0);
    if (row.convenio) b.convenios.add(row.convenio);
    b.rows.push(row);
  });

  let bodegasList = Object.values(bodegasMap);
  if (bodegasFilterBodega) {
    bodegasList = bodegasList.filter(b => b.key === bodegasFilterBodega);
  }
  if (bodegasSearchTerm) {
    bodegasList = bodegasList.filter(b => 
      b.meta.nombre.toLowerCase().includes(bodegasSearchTerm) ||
      b.meta.departamento.toLowerCase().includes(bodegasSearchTerm) ||
      (b.meta.entidad && b.meta.entidad.toLowerCase().includes(bodegasSearchTerm)) ||
      b.meta.tipo.toLowerCase().includes(bodegasSearchTerm)
    );
  }

  let html = `<div class="bodegas-cards-grid">`;
  bodegasList.forEach(b => {
    const pct = totalNacionalStock > 0 ? ((b.raciones_disponibles / totalNacionalStock) * 100).toFixed(1) : 0;
    const isSelected = (bodegasFilterBodega === b.key);
    const isIndeca = (b.meta.entidad === 'INDECA');

    html += `
      <div class="bodega-card ${isSelected ? 'active-selected' : ''}">
        <div class="bodega-card-hd">
          <div style="display:flex;align-items:flex-start;gap:12px;width:100%">
            <div class="bodega-icon-box">${b.meta.icono || '🏢'}</div>
            <div class="bodega-title-group" style="flex:1;min-width:0">
              <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:2px">
                <h3 style="margin:0">${b.meta.nombre}</h3>
                <span style="background:${isIndeca ? '#ecfdf5' : '#eff6ff'};color:${isIndeca ? '#047857' : '#1d4ed8'};border:1px solid ${isIndeca ? '#a7f3d0' : '#bfdbfe'};font-size:9.5px;font-weight:800;padding:1px 6px;border-radius:4px">
                  ${b.meta.entidad || 'PMA'}
                </span>
                <span class="bodega-loc-tag" style="margin-left:auto">${b.meta.departamento}</span>
              </div>
              <div class="bodega-meta" style="margin-top:4px;font-size:11px;color:var(--text-2);line-height:1.35;display:flex;align-items:flex-start;gap:4px">
                <span style="flex-shrink:0;font-size:12px">📍</span>
                <span style="word-break:break-word">${b.meta.direccion || b.meta.tipo || ''}</span>
              </div>
            </div>
          </div>
          ${isSelected ? `<span style="background:var(--blue);color:#fff;font-size:10px;padding:3px 8px;border-radius:6px;font-weight:700;margin-top:2px;flex-shrink:0">Filtro Activo</span>` : ''}
        </div>

        <div class="bodega-stock-highlight" style="margin:12px 0 14px 0">
          <div>
            <div class="b-stock-lbl">Raciones Disponibles</div>
            <div class="b-stock-val">${fmtN(b.raciones_disponibles)}</div>
          </div>
        </div>

        <div class="bodega-card-foot">
          <span style="font-size:10px;color:var(--text-3)">Convenios: ${Array.from(b.convenios).join(', ') || (isIndeca ? 'Reserva Estratégica INDECA' : 'N/A')}</span>
          <div style="display:flex;gap:6px">
            <button class="glass-btn" style="font-size:11px;padding:4px 10px" onclick="selectBodegaCard('${b.key}')">
              ${isSelected ? '✕ Quitar' : '🔍 Filtrar'}
            </button>
            <button class="glass-btn primary" style="font-size:11px;padding:4px 10px" onclick="viewBodegaInTable('${b.key}')">
              📋 Insumos
            </button>
            ${isEditor && b.rows.length > 0 ? `<button class="muni-edit-action-btn" onclick="openEditBodegaModal('${b.rows[0]?.id}')">✏️ Editar</button>` : ''}
          </div>
        </div>
      </div>
    `;
  });
  html += `</div>`;
  container.innerHTML = html;
}

function renderBodegasTable(container) {
  const isEditor = canEditModule('bodegas');
  let rows = BODEGAS_INVENTARIO;

  if (bodegasFilterBodega) rows = rows.filter(r => r.bodega === bodegasFilterBodega);
  if (bodegasFilterConv) rows = rows.filter(r => r.convenio === bodegasFilterConv);
  if (bodegasSearchTerm) {
    rows = rows.filter(r =>
      r.bodega.toLowerCase().includes(bodegasSearchTerm) ||
      (r.convenio && r.convenio.toLowerCase().includes(bodegasSearchTerm)) ||
      (r.programa && r.programa.toLowerCase().includes(bodegasSearchTerm))
    );
  }

  if (bodegasInvSortColumn) {
    const col = bodegasInvSortColumn;
    const dir = bodegasInvSortDirection === 'asc' ? 1 : -1;
    rows = [...rows].sort((a, b) => {
      let va, vb;
      switch (col) {
        case 'bodega':
          va = (BODEGAS_METADATA[a.bodega] || {}).nombre || a.bodega || '';
          vb = (BODEGAS_METADATA[b.bodega] || {}).nombre || b.bodega || '';
          return va.localeCompare(vb, 'es') * dir;
        case 'convenio':
          va = a.convenio || '';
          vb = b.convenio || '';
          return va.localeCompare(vb, 'es') * dir;
        case 'programa':
          va = a.programa || '';
          vb = b.programa || '';
          return va.localeCompare(vb, 'es') * dir;
        case 'mezcla_900g':
          va = a.mezcla_900g || a.harina_soya_900g || 0;
          vb = b.mezcla_900g || b.harina_soya_900g || 0;
          return (va - vb) * dir;
        case 'maiz_blanco_25lb':
          va = a.maiz_blanco_25lb || a.maiz_25lb || 0;
          vb = b.maiz_blanco_25lb || b.maiz_25lb || 0;
          return (va - vb) * dir;
        default:
          va = a[col] || 0;
          vb = b[col] || 0;
          return (va - vb) * dir;
      }
    });
  }

  // Totales
  const totArroz = rows.reduce((s, r) => s + (r.arroz_5lb || 0), 0);
  const totFrijol5 = rows.reduce((s, r) => s + (r.frijol_5lb || 0), 0);
  const totFrijol10 = rows.reduce((s, r) => s + (r.frijol_10lb || 0), 0);
  const totAzucar = rows.reduce((s, r) => s + (r.azucar_500g || 0), 0);
  const totMezcla = rows.reduce((s, r) => s + (r.mezcla_900g || r.harina_soya_900g || 0), 0);
  const totSal460 = rows.reduce((s, r) => s + (r.sal_460g || 0), 0);
  const totSal500 = rows.reduce((s, r) => s + (r.sal_500g || 0), 0);
  const totAceite = rows.reduce((s, r) => s + (r.aceite_800ml || 0), 0);
  const totAvena = rows.reduce((s, r) => s + (r.avena_1kg || 0), 0);
  const totHarina = rows.reduce((s, r) => s + (r.harina_maiz_5lb || 0), 0);
  const totMaiz = rows.reduce((s, r) => s + (r.maiz_blanco_25lb || r.maiz_25lb || 0), 0);
  const totDisp = rows.reduce((s, r) => s + (r.disponible_raciones || 0), 0);

  const cellVal = (v) => {
    if (v === null || v === undefined || v === 0) return '<span style="color:#94a3b8;font-weight:500">-</span>';
    return `<span class="n-val" style="font-weight:600">${fmtN(v)}</span>`;
  };

  let html = `
    <div class="card glass-panel" style="padding:22px;margin-bottom:20px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px">
        <div>
          <div style="display:flex;align-items:center;gap:10px">
            <h3 style="font-size:18px;font-family:'Sora',sans-serif;color:var(--navy)">
              📋 Distribución de Ración por Bodega (Bodegas.xlsx)
            </h3>
            <span style="background:linear-gradient(135deg,#0284c7,#2563eb);color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.5px">
              MATRIZ OFICIAL DE INSUMOS
            </span>
          </div>
          <p style="font-size:12px;color:var(--text-2);margin-top:4px">
            Distribución física consolidada por insumo, peso y tipo de presentación en raciones disponibles.
          </p>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <span style="font-size:12px;color:var(--text-2)">Total Raciones en Almacenes:</span>
          <span style="font-size:16px;font-weight:800;background:#ffedd5;color:#9a3412;border:1px solid #fdba74;padding:4px 12px;border-radius:8px">
            ${fmtN(totDisp)} raciones
          </span>
        </div>
      </div>

      <div class="tbl-wrap" style="border-radius:12px;border:1px solid rgba(21,94,239,0.14);overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:1380px">
          <thead>
            <tr style="background:#133d79;color:#fff">
              <th style="padding:12px 14px;text-align:left;position:sticky;left:0;background:#133d79;z-index:10;min-width:180px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('bodega')" title="Ordenar por Bodega">
                Bodega${bodegasInvSortIconHtml('bodega')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:85px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('arroz_5lb')" title="Ordenar por Arroz">
                Arroz<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 5 lb</span>${bodegasInvSortIconHtml('arroz_5lb')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:90px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('frijol_5lb')" title="Ordenar por Frijol Negro 5lb">
                Frijol Negro<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 05 lb</span>${bodegasInvSortIconHtml('frijol_5lb')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:90px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('frijol_10lb')" title="Ordenar por Frijol Negro 10lb">
                Frijol Negro<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 10 lb</span>${bodegasInvSortIconHtml('frijol_10lb')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:85px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('azucar_500g')" title="Ordenar por Azúcar">
                Azúcar<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 500 g</span>${bodegasInvSortIconHtml('azucar_500g')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:115px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('mezcla_900g')" title="Ordenar por Mezcla Harina">
                Mezcla Harina<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Maíz/Soya 900g</span>${bodegasInvSortIconHtml('mezcla_900g')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:85px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('sal_460g')" title="Ordenar por Sal Yodada 460g">
                Sal Yodada<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 460 g</span>${bodegasInvSortIconHtml('sal_460g')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:85px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('sal_500g')" title="Ordenar por Sal Yodada 500g">
                Sal Yodada<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 500 g</span>${bodegasInvSortIconHtml('sal_500g')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:95px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('aceite_800ml')" title="Ordenar por Aceite Vegetal">
                Aceite Vegetal<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Botella de 800 ml</span>${bodegasInvSortIconHtml('aceite_800ml')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:95px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('avena_1kg')" title="Ordenar por Hojuela Avena">
                Hojuela Avena<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 1 kg</span>${bodegasInvSortIconHtml('avena_1kg')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:115px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('harina_maiz_5lb')" title="Ordenar por Harina de Maíz">
                Harina de Maíz<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Nixtamalizada 5lb</span>${bodegasInvSortIconHtml('harina_maiz_5lb')}
              </th>
              <th style="padding:10px 8px;text-align:right;min-width:95px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('maiz_blanco_25lb')" title="Ordenar por Maíz Blanco">
                Maíz Blanco<br><span style="font-size:9.5px;font-weight:400;opacity:0.85">Bolsa de 25 lb</span>${bodegasInvSortIconHtml('maiz_blanco_25lb')}
              </th>
              <th style="padding:10px 10px;text-align:right;min-width:110px;background:#fed7aa;color:#7c2d12;font-weight:800;border-left:1px solid #fdba74;border-right:1px solid #fdba74;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('disponible_raciones')" title="Ordenar por Disponible en Raciones">
                DISPONIBLE<br>EN RACIONES${bodegasInvSortIconHtml('disponible_raciones')}
              </th>
              <th style="padding:10px 10px;text-align:center;min-width:85px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('convenio')" title="Ordenar por Convenio">CONVENIO${bodegasInvSortIconHtml('convenio')}</th>
              <th style="padding:10px 12px;text-align:left;min-width:140px;cursor:pointer;user-select:none" onclick="toggleBodegasInvSort('programa')" title="Ordenar por Programa / DAAN">PROGRAMA / DAAN${bodegasInvSortIconHtml('programa')}</th>
              ${isEditor ? `<th style="padding:10px 8px;text-align:center;min-width:60px">Acción</th>` : ''}
            </tr>
          </thead>
          <tbody>
  `;

  rows.forEach(r => {
    const meta = BODEGAS_METADATA[r.bodega] || {};
    const cellClass = isEditor ? 'class="editable-cell"' : '';
    const cellClick = (field, label) => isEditor ? `onclick="promptEditBodegaCell('${r.id}', '${field}', '${label}')"` : '';

    html += `
      <tr style="border-bottom:1px solid rgba(21,94,239,0.08);background:#fff;transition:background .15s" onmouseover="this.style.background='#f8fbff'" onmouseout="this.style.background='#fff'">
        <td style="padding:10px 14px;position:sticky;left:0;background:#fff;z-index:2;box-shadow:2px 0 5px rgba(0,0,0,0.03)">
          <div style="display:flex;align-items:center;gap:8px">
            <span style="font-size:16px">${meta.icono || '🏢'}</span>
            <div>
              <strong style="color:var(--navy);font-size:12.5px">${meta.nombre || r.bodega}</strong>
              <div style="font-size:10px;color:var(--text-2)">${r.bodega} · ${meta.departamento || ''}</div>
            </div>
          </div>
        </td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('arroz_5lb', 'Arroz 5lb')}>${cellVal(r.arroz_5lb)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('frijol_5lb', 'Frijol 5lb')}>${cellVal(r.frijol_5lb)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('frijol_10lb', 'Frijol 10lb')}>${cellVal(r.frijol_10lb)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('azucar_500g', 'Azúcar 500g')}>${cellVal(r.azucar_500g)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('mezcla_900g', 'Mezcla Maíz/Soya 900g')}>${cellVal(r.mezcla_900g || r.harina_soya_900g)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('sal_460g', 'Sal Yodada 460g')}>${cellVal(r.sal_460g)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('sal_500g', 'Sal Yodada 500g')}>${cellVal(r.sal_500g)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('aceite_800ml', 'Aceite 800ml')}>${cellVal(r.aceite_800ml)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('avena_1kg', 'Avena 1kg')}>${cellVal(r.avena_1kg)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('harina_maiz_5lb', 'Harina Maíz 5lb')}>${cellVal(r.harina_maiz_5lb)}</td>
        <td style="padding:10px 8px;text-align:right" ${cellClass} ${cellClick('maiz_blanco_25lb', 'Maíz Blanco 25lb')}>${cellVal(r.maiz_blanco_25lb || r.maiz_25lb)}</td>
        <td style="padding:10px 10px;text-align:right;background:#fff7ed;color:#9a3412;font-weight:800;border-left:1px solid #fed7aa;border-right:1px solid #fed7aa" ${cellClass} ${cellClick('disponible_raciones', 'Raciones Disponibles')}>
          ${r.disponible_raciones > 0 ? fmtN(r.disponible_raciones) : '-'}
        </td>
        <td style="padding:10px 10px;text-align:center">
          <span style="background:#eef4ff;color:#1e40af;padding:2px 7px;border-radius:6px;font-weight:700;font-size:11px">${r.convenio || '—'}</span>
        </td>
        <td style="padding:10px 12px">
          <span style="font-size:11px;color:var(--text-1);font-weight:600">${r.programa || '—'}</span>
        </td>
        ${isEditor ? `
          <td style="padding:10px 8px;text-align:center">
            <button class="muni-edit-action-btn" onclick="openEditBodegaModal('${r.id}')" title="Editar fila completa">✏️</button>
          </td>
        ` : ''}
      </tr>
    `;
  });

  html += `
          </tbody>
          <tfoot>
            <tr style="background:#eaf2fc;font-weight:800;color:var(--navy);border-top:2px solid #bcd7fa;font-size:12.5px">
              <td style="padding:12px 14px;position:sticky;left:0;background:#eaf2fc;z-index:2">TOTAL</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totArroz)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totFrijol5)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totFrijol10)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totAzucar)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totMezcla)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totSal460)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totSal500)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totAceite)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totAvena)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totHarina)}</td>
              <td style="padding:12px 8px;text-align:right">${fmtN(totMaiz)}</td>
              <td style="padding:12px 10px;text-align:right;background:#fed7aa;color:#7c2d12;font-size:13px;border-left:1px solid #fdba74;border-right:1px solid #fdba74">${fmtN(totDisp)}</td>
              <td style="padding:12px 10px;text-align:center">—</td>
              <td style="padding:12px 12px">${rows.length} Registros</td>
              ${isEditor ? '<td></td>' : ''}
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  `;
  container.innerHTML = html;
}

function renderBodegasBalance(container) {
  let html = `
    <div style="display:grid;grid-template-columns:1fr;gap:20px;margin-bottom:24px">
      <div class="card glass-panel" style="padding:22px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
          <div>
            <h3 style="font-size:18px;font-family:'Sora',sans-serif;color:var(--navy)">Balance de Raciones por Convenio (UDAFA)</h3>
            <p style="font-size:12px;color:var(--text-2)">Relación entre meta programada de convenios, ingresos a bodegas y raciones despachadas.</p>
          </div>
          <span style="background:#e0f2fe;color:#0284c7;font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px">Fuente Oficial UDAFA</span>
        </div>

        <div class="tbl-wrap" style="margin-bottom:16px">
          <table style="width:100%;border-collapse:collapse;font-size:12px">
            <thead>
              <tr style="background:#133d79;color:#fff">
                <th style="padding:10px 14px;text-align:left">Programa</th>
                <th style="padding:10px 12px;text-align:left">Convenio</th>
                <th style="padding:10px 12px;text-align:right">Total Convenio</th>
                <th style="padding:10px 12px;text-align:right">Recibidas VISAN</th>
                <th style="padding:10px 12px;text-align:right">Despachadas</th>
                <th style="padding:10px 12px;text-align:right">Disponibles</th>
                <th style="padding:10px 12px;text-align:right">Pendiente PMA</th>
                <th style="padding:10px 14px;text-align:center">% Avance Despacho</th>
              </tr>
            </thead>
            <tbody>
  `;

  BODEGAS_BALANCE.forEach(b => {
    const pct = b.recibidas > 0 ? ((b.despachadas / b.recibidas) * 100).toFixed(1) : 0;
    html += `
      <tr style="border-bottom:1px solid #e2eeff;background:#fff">
        <td style="padding:12px 14px"><strong style="color:var(--navy)">${b.programa}</strong></td>
        <td style="padding:12px 12px"><span style="background:#f1f5f9;color:#334155;padding:2px 7px;border-radius:6px;font-weight:600">${b.convenio}</span></td>
        <td style="padding:12px 12px;text-align:right;font-weight:700">${fmtN(b.total_convenio)}</td>
        <td style="padding:12px 12px;text-align:right;color:#0284c7;font-weight:600">${fmtN(b.recibidas)}</td>
        <td style="padding:12px 12px;text-align:right;color:#ea580c;font-weight:600">${fmtN(b.despachadas)}</td>
        <td style="padding:12px 12px;text-align:right;color:#16a34a;font-weight:700">${fmtN(b.disponibles)}</td>
        <td style="padding:12px 12px;text-align:right;color:#ca8a04">${fmtN(b.pendiente_pma)}</td>
        <td style="padding:12px 14px">
          <div style="display:flex;align-items:center;gap:8px">
            <div style="flex:1;height:7px;background:#e2e8f0;border-radius:10px;overflow:hidden">
              <div style="height:100%;width:${Math.min(100, pct)}%;background:linear-gradient(90deg,#0284c7,#10b981);border-radius:10px"></div>
            </div>
            <span style="font-size:11px;font-weight:700;color:var(--navy);width:42px;text-align:right">${pct}%</span>
          </div>
        </td>
      </tr>
    `;
  });

  const totConv = BODEGAS_BALANCE.reduce((s, r) => s + r.total_convenio, 0);
  const totRec = BODEGAS_BALANCE.reduce((s, r) => s + r.recibidas, 0);
  const totDes = BODEGAS_BALANCE.reduce((s, r) => s + r.despachadas, 0);
  const totDisp = BODEGAS_BALANCE.reduce((s, r) => s + r.disponibles, 0);
  const totPma = BODEGAS_BALANCE.reduce((s, r) => s + r.pendiente_pma, 0);
  const globalPct = totRec > 0 ? ((totDes / totRec) * 100).toFixed(1) : 0;

  html += `
            </tbody>
            <tfoot>
              <tr style="background:#eaf2fc;font-weight:700;color:var(--navy);border-top:2px solid #bcd7fa">
                <td style="padding:12px 14px">TOTAL GENERAL</td>
                <td style="padding:12px 12px">${BODEGAS_BALANCE.length} Convenios</td>
                <td style="padding:12px 12px;text-align:right">${fmtN(totConv)}</td>
                <td style="padding:12px 12px;text-align:right;color:#0284c7">${fmtN(totRec)}</td>
                <td style="padding:12px 12px;text-align:right;color:#ea580c">${fmtN(totDes)}</td>
                <td style="padding:12px 12px;text-align:right;color:#16a34a">${fmtN(totDisp)}</td>
                <td style="padding:12px 12px;text-align:right;color:#ca8a04">${fmtN(totPma)}</td>
                <td style="padding:12px 14px;text-align:center;color:var(--blue)">${globalPct}% Despachado</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  `;
  container.innerHTML = html;
}

function renderBodegasContenido(container) {
  let html = `
    <div style="margin-bottom:16px">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <h3 style="font-size:18px;font-family:'Sora',sans-serif;color:var(--navy)">Fichas Técnicas del Contenido de la Ración</h3>
        <span style="background:linear-gradient(135deg,#0284c7,#2563eb);color:#fff;font-size:10px;font-weight:800;padding:3px 10px;border-radius:20px;letter-spacing:0.5px">
          MATRIZ OFICIAL DE RACIONES
        </span>
      </div>
      <p style="font-size:12px;color:var(--text-2);margin-top:4px">Composición oficial de productos, presentaciones, peso, costo y valor nutricional por cada tipo de ración entregada.</p>
    </div>
    <div class="ficha-grid">
  `;

  BODEGAS_RACIONES_FICHA.forEach(f => {
    html += `
      <div class="ficha-card">
        <div class="ficha-hd">
          <h3>${f.titulo}</h3>
          <div style="font-size:11px;color:var(--text-2);margin-top:2px">${f.grupo}</div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:8px;margin-bottom:14px">
          <div style="background:#f8fbff;border:1px solid #e1effe;border-radius:9px;padding:8px 10px;text-align:center">
            <div style="font-size:9.5px;color:var(--text-2);text-transform:uppercase;letter-spacing:0.4px;font-weight:600">Peso</div>
            <div style="font-size:12px;font-weight:700;color:var(--navy);margin-top:2px">${f.peso}</div>
          </div>
          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;padding:8px 10px;text-align:center">
            <div style="font-size:9.5px;color:var(--text-2);text-transform:uppercase;letter-spacing:0.4px;font-weight:600">Costo Ración</div>
            <div style="font-size:12px;font-weight:700;color:#15803d;margin-top:2px">Q${fmtN(f.costo)}</div>
          </div>
          <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:9px;padding:8px 10px;text-align:center">
            <div style="font-size:9.5px;color:var(--text-2);text-transform:uppercase;letter-spacing:0.4px;font-weight:600">Kilocalorías</div>
            <div style="font-size:12px;font-weight:700;color:#c2410c;margin-top:2px">${f.kcal} kcal</div>
          </div>
        </div>

        <div class="ficha-items-list">
    `;

    f.productos.forEach(it => {
      html += `
        <div class="ficha-item-row">
          <div class="ficha-prod-title">${it.producto}</div>
          <div class="ficha-prod-qty">${it.presentacion}</div>
        </div>
      `;
    });

    html += `
        </div>
      </div>
    `;
  });

  html += `</div>`;
  container.innerHTML = html;
}

function openEditBodegaModal(id) {
  if (!canEditModule('bodegas')) {
    showToast('⚠️ Solo los usuarios con rol de Editor pueden modificar inventarios.');
    return;
  }
  const row = BODEGAS_INVENTARIO.find(r => r.id === id);
  if (!row) return;
  currentEditBodegaRow = row;

  const meta = BODEGAS_METADATA[row.bodega] || {};
  document.getElementById('bodega-edit-title').textContent = `Editar Inventario: ${meta.nombre || row.bodega}`;
  document.getElementById('bodega-edit-sub').textContent = `Convenio: ${row.convenio} · Programa: ${row.programa} (${meta.departamento || ''})`;

  const fieldsWrap = document.getElementById('bodega-edit-fields');
  const fields = [
    { key: 'disponible_raciones', label: 'DISPONIBLE EN RACIONES' },
    { key: 'arroz_5lb', label: 'Arroz (Bolsa de 5 lb)' },
    { key: 'frijol_5lb', label: 'Frijol Negro (Bolsa de 05 lb)' },
    { key: 'frijol_10lb', label: 'Frijol Negro (Bolsa de 10 lb)' },
    { key: 'azucar_500g', label: 'Azúcar (Bolsa de 500 g)' },
    { key: 'mezcla_900g', label: 'Mezcla Maíz y Soya (900 g)' },
    { key: 'sal_460g', label: 'Sal Yodada (Bolsa de 460 g)' },
    { key: 'sal_500g', label: 'Sal Yodada (Bolsa de 500 g)' },
    { key: 'aceite_800ml', label: 'Aceite Vegetal (Botella de 800 ml)' },
    { key: 'avena_1kg', label: 'Hojuela de Avena (Bolsa de 1 kg)' },
    { key: 'harina_maiz_5lb', label: 'Harina de Maíz Nixtamalizada (5 lb)' },
    { key: 'maiz_blanco_25lb', label: 'Maíz Blanco (Bolsa de 25 lb)' }
  ];

  let fieldsHTML = '';
  fields.forEach(f => {
    fieldsHTML += `
      <div class="edit-field-group">
        <div class="edit-field-label"><span>${f.label}</span></div>
        <input type="number" class="edit-input" id="field-bodega-${f.key}" value="${row[f.key] !== null && row[f.key] !== undefined ? row[f.key] : 0}" placeholder="0" min="0">
      </div>
    `;
  });

  fieldsWrap.innerHTML = fieldsHTML;
  document.getElementById('bodega-edit-modal').hidden = false;
}
window.openEditBodegaModal = openEditBodegaModal;

function closeBodegaEditModal() {
  document.getElementById('bodega-edit-modal').hidden = true;
  currentEditBodegaRow = null;
}
window.closeBodegaEditModal = closeBodegaEditModal;

async function submitBodegaEdit() {
  if (!currentEditBodegaRow) return;
  const row = currentEditBodegaRow;
  const cambios = {};
  for (const input of document.querySelectorAll('#bodega-edit-fields input')) {
    const key = input.id.replace('field-bodega-', '');
    const val = input.value.trim() === '' ? 0 : Number(input.value.trim());
    if (isNaN(val) || val < 0) { showToast('⚠️ Ingresa cantidades válidas (mayores o iguales a 0).', 'error'); input.focus(); return; }
    cambios[key] = val;
    if (key === 'mezcla_900g') cambios.harina_soya_900g = val;
    if (key === 'maiz_blanco_25lb') cambios.maiz_25lb = val;
  }
  try {
    await api('api/data.php?a=bodegas', { json: { tipo: 'inventario', id: row.id, cambios } });
  } catch (e) {
    showToast(`No se guardó: ${e.message}`, 'error');
    return;
  }
  Object.assign(row, cambios);
  closeBodegaEditModal();
  renderBodegasMetrics();
  renderBodegasContent();
  showToast(`✓ Inventario de ${row.bodega} actualizado y recalculado.`);
}
window.submitBodegaEdit = submitBodegaEdit;

async function promptEditBodegaCell(id, fieldKey, fieldLabel) {
  if (!canEditModule('bodegas')) {
    showToast('⚠️ Cambia a Modo Editor para modificar valores de inventario.');
    return;
  }
  const row = BODEGAS_INVENTARIO.find(r => r.id === id);
  if (!row) return;

  const currentVal = row[fieldKey] !== null && row[fieldKey] !== undefined ? row[fieldKey] : 0;
  const newVal = prompt(`Editar ${fieldLabel} para ${row.bodega} (${row.convenio}):`, currentVal);
  if (newVal === null) return;

  const parsedVal = newVal.trim() === '' ? 0 : Number(newVal.trim().replace(/[,\s]/g, ''));
  if (isNaN(parsedVal) || parsedVal < 0) {
    showToast('⚠️ Ingresa un número válido (mayor o igual a 0).', 'error');
    return;
  }
  const cambios = { [fieldKey]: parsedVal };
  if (fieldKey === 'mezcla_900g') cambios.harina_soya_900g = parsedVal;
  if (fieldKey === 'maiz_blanco_25lb') cambios.maiz_25lb = parsedVal;
  try {
    await api('api/data.php?a=bodegas', { json: { tipo: 'inventario', id, cambios } });
  } catch (e) {
    showToast(`No se guardó: ${e.message}`, 'error');
    return;
  }
  Object.assign(row, cambios);

  renderBodegasMetrics();
  renderBodegasContent();
  showToast(`✓ ${fieldLabel} actualizado en ${row.bodega}. Totales recalculados.`);
}
window.promptEditBodegaCell = promptEditBodegaCell;

// ── INIT ──
initAuth();
initDashboard();
renderDeptCards();
renderDeptCards2();
buildEjChips();
renderEjMetrics();
renderEjTable();
buildProgChips();
renderProgMetrics();
renderProgTable();
renderConredMetrics();
renderConredTable();
renderAlertasPage();
initBodegas();
