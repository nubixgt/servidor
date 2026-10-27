const PRIMARY=['dep','mun','y','e'];
function renderFilters(){
  const fb=$('#fbar');const nAct=Object.values(F).filter(v=>v!=null).length;
  const secAct=FDEF.filter(d=>!PRIMARY.includes(d[0])&&F[d[0]]!=null).length;
  const showSec=UI.moreF||secAct>0;
  fb.innerHTML=`<button class="btn ftog" id="ftog" aria-expanded="${fb.classList.contains('open')}">${ic('sliders',16)} Filtros${nAct?' ('+nAct+')':''}</button>`+
   FDEF.filter(d=>showSec||PRIMARY.includes(d[0])).map(([k,l,opts])=>{const o=opts();const dis=k==='mun'&&F.dep==null;return `<div class="fsel ${F[k]!=null?'on':''}"><label for="f_${k}">${l}</label><select id="f_${k}" data-f="${k}" ${dis?'disabled title="Seleccione primero un departamento"':''}><option value="">${l}${dis?' (elija depto.)':''}</option>${o.map(([v,t])=>`<option value="${v}" ${F[k]===v?'selected':''}>${esc(t)}</option>`).join('')}</select></div>`}).join('')+
   `<button class="btn more" id="moreF" aria-expanded="${showSec}">${ic('sliders',15)} ${showSec?'Menos filtros':'Más filtros (6)'}</button><button class="btn" id="clearF" ${nAct?'':'disabled'}>Limpiar filtros</button>`;
  document.querySelectorAll('[data-f]').forEach(s=>s.onchange=e=>{const k=s.dataset.f;setF(k,e.target.value===''?null:+e.target.value,{toggle:false})});
  $('#clearF').onclick=clearF;$('#ftog').onclick=()=>{fb.classList.toggle('open');renderFilters()};
  $('#moreF').onclick=()=>{UI.moreF=!showSec;if(!UI.moreF&&secAct){FDEF.forEach(d=>{if(!PRIMARY.includes(d[0]))F[d[0]]=null});render();return}renderFilters()};
  const act=Object.entries(F).filter(([k,v])=>v!=null);
  $('#chips').innerHTML=act.map(([k,v])=>`<span class="chip">${esc(flabel(k,v))}<button data-rm="${k}" aria-label="Quitar filtro">${ic('close',12)}</button></span>`).join('');
  document.querySelectorAll('[data-rm]').forEach(b=>b.onclick=()=>{F[b.dataset.rm]=null;if(b.dataset.rm==='dep')F.mun=null;render()});
}

/* ---------- transiciones de vista ---------- */
function go(v){UI.view=v;UI.enter=true;try{history.replaceState(null,'','#'+v)}catch(e){}render();window.scrollTo({top:0,behavior:RM.matches?'auto':'smooth'})}
function renderView(){HOOKS.length=0;const el=$('#view');el.classList.toggle('enter',!!UI.enter);UI.enter=false;el.innerHTML=(VIEWS[UI.view]||VIEWS.dashboard)();HOOKS.forEach(f=>f())}

/* ---------- reflejo de luz que sigue al cursor ---------- */
let spotRaf=0;
document.addEventListener('pointermove',e=>{if(spotRaf)return;spotRaf=requestAnimationFrame(()=>{spotRaf=0;const el=e.target.closest&&e.target.closest('.card,.kpi,.hero');if(!el)return;const r=el.getBoundingClientRect();el.style.setProperty('--mx',(e.clientX-r.left)+'px');el.style.setProperty('--my',(e.clientY-r.top)+'px')})},{passive:true});

/* ---------- consulta en lenguaje natural ---------- */
const DEP_ALIAS={'xela':8,'quetzaltenango':8,'peten':16,'quiche':13,'el quiche':13,'guate':0};
function parseQ(text){
  let t=' '+norm(text).replace(/[¿?¡!.,;:()]/g,' ').replace(/\s+/g,' ')+' ';const f={};const tags=[];
  const has=re=>re.test(t);const cut=s=>{t=t.replace(' '+s+' ',' ')};
  // municipio explícito
  const MUNS=MN.map((n,i)=>[norm(n),i]).sort((a,b)=>b[0].length-a[0].length);
  for(const [nm,i] of MUNS){if(t.includes(' municipio de '+nm+' ')||t.includes(' municipio '+nm+' ')){f.mun=i;f.dep=MDEP[i];cut('municipio de '+nm);cut('municipio '+nm);break}}
  if(f.mun==null){
    const DN=DEPS.map((d,i)=>[norm(d),i]).concat(Object.entries(DEP_ALIAS)).sort((a,b)=>b[0].length-a[0].length);
    for(const [dn,i] of DN){if(t.includes(' '+dn+' ')){f.dep=i;cut(dn);break}}
    for(const [nm,i] of MUNS){if(nm.length>3&&t.includes(' '+nm+' ')&&(f.dep==null||MDEP[i]===f.dep)){f.mun=i;f.dep=MDEP[i];break}}
  }
  if(f.dep!=null)tags.push(f.mun!=null?`${MN[f.mun]}, ${DEPS[f.dep]}`:DEPS[f.dep]);
  if(has(/ visitas? positivas? /)){f.v=1;tags.push('Visita positiva')}
  else if(has(/ visitas? negativas? /)){f.v=2;tags.push('Visita negativa')}
  else if(has(/ sin visitas? /)){f.v=0;tags.push('Sin visita')}
  if(has(/ resolucion(es)? positivas? /)){f.r=0;tags.push('Resolución positiva')}
  else if(has(/ sin resolucion /)){f.r=8;tags.push('Sin resolución')}
  if(has(/ (egresad|salieron|dados de baja|salida)/)){f.n=2;tags.push('Egresados de nómina')}
  else if(has(/ nunca ingres/)){f.n=0;tags.push('Nunca ingresó a nómina')}
  else if(has(/ (nomina|activos?|reciben|cobran|pagados?) /)){f.n=1;tags.push('En nómina activa')}
  if(has(/ (fallecid|murieron|difunt|muertos?)/)){f.f=1;tags.push('Con fallecimiento')}
  if(has(/ (tramite|pendientes?|en proceso) /)){f.e=1;tags.push('En trámite')}
  else if(has(/ suspendid/)){f.e=4;tags.push('Suspendidos')}
  else if(has(/ bloquead/)){f.e=3;tags.push('Bloqueados')}
  else if(has(/ no localizad/)){f.e=5;tags.push('No localizados')}
  else if(has(/ anulad/)){f.e=6;tags.push('Anulados')}
  else if(has(/ (expedientes?|resolucion(es)?|casos?) negativ/)){f.e=2;tags.push('Negativos')}
  let m=t.match(/ (mayores|mas) de (\d{2,3})/)||t.match(/ (\d{2,3}) anos? (o|y) mas /)||t.match(/ (de )?(\d{2,3}) (anos? )?(para arriba|en adelante)/);
  if(m){const age=+(m.slice(1).find(x=>x&&/^\d+$/.test(x)));const lows=[65,70,75,80,85,90];let idx=lows.findIndex(l=>l>=age);if(age<=65)idx=0;if(idx<0)idx=5;f.am=idx;tags.push(`${lows[idx]} años o más`)}
  const mb=t.match(/ entre (\d{2}) y (\d{2,3})/);if(mb&&f.am==null){const a=+mb[1];const idx=[65,70,75,80,85,90].findIndex((l,i,arr)=>a>=l&&(i===5||a<arr[i+1]));if(idx>=0){f.a=idx;tags.push(AB[idx]+' años')}}
  const my=t.match(/ (20[0-2]\d) /);if(my&&YS.includes(my[1])){f.y=YS.indexOf(my[1]);tags.push('Solicitud '+my[1])}
  if(has(/ (mas de un ano|mas de 365|mas de 12 meses|antiguos?|atrasad)/)){f.t=6;f.e=1;if(!tags.includes('En trámite'))tags.push('En trámite');tags.push('Más de 365 días')}
  return {f,tags};
}
function countWith(ff){const save={...F};for(const k in F)F[k]=null;Object.assign(F,ff);let c=0,p=0;for(let i=0;i<C.len;i++){if(ok(i,'')){c+=C.c[i];p+=C.p[i]}}Object.assign(F,save);return {c,p}}
const EXAMPLES=['Beneficiarios en nómina mayores de 80 años en Alta Verapaz','Expedientes en trámite con más de un año en Huehuetenango','Fallecidos que siguen en nómina','Solicitudes de 2025 sin visita en Quiché','Suspendidos en el municipio de Cobán','Visitas negativas en Petén'];
function openAsk(seed){
  closeAsk();const w=document.createElement('div');w.className='cmdk';w.setAttribute('role','dialog');w.setAttribute('aria-modal','true');w.setAttribute('aria-label','Consulta en lenguaje natural');
  w.innerHTML=`<div class="panel gl"><div class="inrow">${ICON?`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/></svg>`:''}<input id="askIn" autocomplete="off" placeholder="Pregunte en español: “cuántos en nómina mayores de 80 en Alta Verapaz”" aria-label="Pregunta"><button class="ibtn" id="askX" aria-label="Cerrar" style="width:34px;height:34px">${ic('close',16)}</button></div><div class="res" id="askRes"></div><div class="foot"><span>Enter aplica los filtros al tablero · Esc cierra</span><span>Reglas locales: departamento, municipio, estado, nómina, visita, edad, año y antigüedad</span></div></div>`;
  document.body.appendChild(w);const inp=$('#askIn');let tmr=0,cur=null,seq=0;
  const draw=()=>{const q=inp.value.trim();const box=$('#askRes');
    if(!q){box.innerHTML=`<div class="lbl">Pruebe con</div><div class="ex">${EXAMPLES.map(e=>`<button data-ex="${esc(e)}">${esc(e)}</button>`).join('')}</div>`;box.querySelectorAll('[data-ex]').forEach(b=>b.onclick=()=>{inp.value=b.dataset.ex;draw();inp.focus()});cur=null;return}
    const {f,tags}=parseQ(q);
    if(tags.length){const r=countWith(f);cur=f;
      box.innerHTML=`<div class="answer"><div class="an">${fmt(r.c)}</div><div class="at"><b>registros</b> (${fmt(r.p)} personas únicas) cumplen la consulta.<br><span class="note">${pc(r.c,TOTAL_NAC,2)}% del total de su ámbito</span></div><button class="btn pri" id="askGo" style="margin-left:auto">Aplicar al tablero</button></div><div><div class="lbl" style="margin-bottom:6px">Interpretación</div><div class="parsed">${tags.map(t=>`<span>${esc(t)}</span>`).join('')}</div></div>`;
      $('#askGo').onclick=apply}
    else{cur=null;box.innerHTML=`<div class="note">No reconocí filtros en la pregunta. Mencione un departamento, un estado (en nómina, en trámite, suspendidos, fallecidos), una edad o un año.</div>`}
    if(!tags.length&&q.length>=3&&PUEDE('beneficiarios.ver')){const token=++seq;
      api.get('/expedientes',{q,limite:6,orden:'relevancia'}).then(res=>{if(token!==seq||!document.getElementById('askRes'))return;
        const ppl=res.items||[];if(!ppl.length)return;
        box.innerHTML=`<div><div class="lbl" style="margin-bottom:4px">Expedientes encontrados (${res.total_es_minimo?'más de 10,000':fmt(res.total)})</div>${ppl.map(p=>`<button class="pp" data-pp="${p.id}"><b>${esc(p.nombre)}</b><span>${badge(p.estado_orden)}</span><small>Exp. ${esc(p.numero||'s/n')} · ${esc(p.dpi||'—')} · ${esc(p.municipio)}, ${esc(p.departamento)}</small></button>`).join('')}</div>`;
        box.querySelectorAll('[data-pp]').forEach(b=>b.onclick=()=>{closeAsk();openExp(+b.dataset.pp)});
      }).catch(()=>{})}
  };
  const apply=()=>{if(!cur)return;for(const k in F)F[k]=null;Object.assign(F,cur);closeAsk();if(!['dashboard','territorio','beneficiarios'].includes(UI.view))UI.view='dashboard';UI.enter=true;render();toast('Consulta aplicada al tablero')};
  inp.oninput=()=>{clearTimeout(tmr);tmr=setTimeout(draw,220)};
  inp.onkeydown=e=>{if(e.key==='Enter'){e.preventDefault();draw();apply()}else if(e.key==='Escape')closeAsk()};
  w.onclick=e=>{if(e.target===w)closeAsk()};$('#askX').onclick=closeAsk;
  if(seed)inp.value=seed;draw();inp.focus();
}
function closeAsk(){document.querySelectorAll('.cmdk').forEach(e=>e.remove())}
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();openAsk()}if(e.key==='Escape')closeAsk()});
setTimeout(()=>{const b=document.getElementById('askBtn');if(b)b.onclick=()=>openAsk()},0);

