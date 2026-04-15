import React from 'react';

export default function Calendar() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header Section */}
      <div className="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <span className="text-primary font-bold text-[10px] tracking-[0.2em] uppercase mb-3 block">Social Media Suite</span>
          <h2 className="text-4xl font-extrabold text-on-surface font-headline tracking-tight">Calendario Editorial</h2>
          <p className="text-on-surface-variant mt-2 text-base">Gestiona mensajes institucionales multiplataforma y flujos de aprobación.</p>
        </div>
        <div className="flex items-center gap-4">
          <div className="flex bg-surface-container p-1 rounded-lg border border-outline-variant/50">
            <button className="px-5 py-1.5 bg-surface shadow-sm rounded-md text-sm font-bold text-on-surface">Mensual</button>
            <button className="px-5 py-1.5 text-sm font-medium text-on-surface-variant hover:text-on-surface transition-colors">Semanal</button>
            <button className="px-5 py-1.5 text-sm font-medium text-on-surface-variant hover:text-on-surface transition-colors">Lista</button>
          </div>
          <button className="flex items-center gap-2 bg-gradient-to-br from-primary to-primary-dim text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:opacity-90 transition-all shadow-md">
            <span className="material-symbols-outlined text-sm">add_box</span>
            Nuevo Contenido
          </button>
        </div>
      </div>

      {/* Dashboard Filters */}
      <div className="mb-8 flex flex-wrap items-center justify-between gap-4 py-4 border-b border-outline-variant/50">
        <div className="flex items-center gap-8">
          <div className="flex items-center gap-4">
            <span className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Plataforma:</span>
            <div className="flex gap-2">
              <button className="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors"><span className="material-symbols-outlined text-lg">public</span></button>
              <button className="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center shadow-sm"><span className="material-symbols-outlined text-lg">movie</span></button>
              <button className="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant transition-colors"><span className="material-symbols-outlined text-lg">photo_camera</span></button>
            </div>
          </div>
          <div className="h-6 w-px bg-outline-variant/50"></div>
          <div className="flex items-center gap-2">
            <span className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">Estado:</span>
            <select className="bg-transparent border-none text-sm font-bold text-on-surface focus:ring-0 cursor-pointer p-0 outline-none">
              <option>Todos los Estados</option>
              <option>Borrador</option>
              <option>Aprobado</option>
              <option>Publicado</option>
            </select>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <button className="w-8 h-8 flex items-center justify-center text-on-surface hover:bg-surface-container rounded-full transition-colors">
            <span className="material-symbols-outlined">chevron_left</span>
          </button>
          <span className="text-sm font-black text-on-surface font-headline uppercase tracking-widest">Octubre 2023</span>
          <button className="w-8 h-8 flex items-center justify-center text-on-surface hover:bg-surface-container rounded-full transition-colors">
            <span className="material-symbols-outlined">chevron_right</span>
          </button>
        </div>
      </div>

      {/* Calendar Grid */}
      <div className="bg-surface border border-outline-variant/50 rounded-lg overflow-hidden shadow-sm">
        {/* Days Header */}
        <div className="grid grid-cols-7 border-b border-outline-variant/50 bg-surface-container-lowest">
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Lun</div>
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Mar</div>
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Mie</div>
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Jue</div>
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Vie</div>
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Sab</div>
          <div className="py-3 text-center text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em]">Dom</div>
        </div>
        
        {/* Calendar Content */}
        <div className="grid grid-cols-7 gap-[1px] bg-outline-variant/50">
          {/* Week 1 - Partial */}
          <div className="bg-surface-container-lowest h-44 p-3 opacity-50">
            <span className="text-xs font-bold font-headline text-on-surface-variant">28</span>
          </div>
          <div className="bg-surface-container-lowest h-44 p-3 opacity-50">
            <span className="text-xs font-bold font-headline text-on-surface-variant">29</span>
          </div>
          <div className="bg-surface-container-lowest h-44 p-3 opacity-50">
            <span className="text-xs font-bold font-headline text-on-surface-variant">30</span>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer group">
            <span className="text-xs font-bold font-headline text-on-surface">01</span>
            {/* Post Card Draft */}
            <div className="mt-2 p-2 bg-surface border border-outline-variant/50 rounded shadow-sm group-hover:border-primary/30 transition-all">
              <div className="flex items-center justify-between mb-1">
                <span className="material-symbols-outlined text-[14px] text-on-surface-variant">public</span>
                <span className="text-[8px] font-black px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant uppercase">Borrador</span>
              </div>
              <p className="text-[10px] font-medium leading-tight line-clamp-2 text-on-surface">Anuncio de Asamblea Live stream</p>
            </div>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">02</span>
            {/* Post Card Video Required */}
            <div className="mt-2 p-2 bg-on-surface text-surface rounded shadow-sm">
              <div className="flex items-center justify-between mb-1">
                <span className="material-symbols-outlined text-[14px]">movie</span>
                <span className="text-[8px] font-black px-1.5 py-0.5 rounded bg-surface/20 uppercase">Requerido</span>
              </div>
              <p className="text-[10px] font-medium leading-tight line-clamp-2">Un Día en el Senado - Video BTS</p>
            </div>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">03</span>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">04</span>
          </div>

          {/* Week 2 */}
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">05</span>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">06</span>
            {/* Post Card Approved */}
            <div className="mt-2 p-2 bg-surface border-l-4 border-primary rounded shadow-sm">
              <div className="flex items-center justify-between mb-1">
                <span className="material-symbols-outlined text-[14px] text-primary">photo_camera</span>
                <span className="text-[8px] font-black px-1.5 py-0.5 rounded bg-primary-container text-on-primary-container uppercase">Aprobado</span>
              </div>
              <p className="text-[10px] font-medium leading-tight line-clamp-2 text-on-surface">Infografía Ley de Educación</p>
            </div>
          </div>
          <div className="bg-surface h-44 p-3 ring-1 ring-inset ring-primary relative">
            <span className="text-xs font-bold font-headline text-primary">07</span>
            <div className="mt-2 p-2 bg-primary text-white rounded shadow-md">
              <div className="flex items-center justify-between mb-1">
                <span className="material-symbols-outlined text-[14px]">public</span>
                <span className="text-[8px] font-black px-1.5 py-0.5 rounded bg-white/20 uppercase">Publicado</span>
              </div>
              <p className="text-[10px] font-medium leading-tight line-clamp-2">Declaración Política de Seguridad</p>
            </div>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">08</span>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">09</span>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">10</span>
          </div>
          <div className="bg-surface h-44 p-3 hover:bg-surface-container-lowest transition-colors cursor-pointer">
            <span className="text-xs font-bold font-headline text-on-surface">11</span>
          </div>
        </div>
      </div>

      {/* Side Workflow Panel */}
      <div className="mt-16 grid grid-cols-1 lg:grid-cols-3 gap-12">
        {/* Approval Queue */}
        <div className="lg:col-span-2 space-y-6">
          <h3 className="text-xl font-bold text-on-surface font-headline tracking-tight">Cola de Aprobación Pendiente</h3>
          
          {/* Approval Card 1 */}
          <div className="bg-surface p-5 rounded-lg border border-outline-variant/50 hover:border-primary/30 transition-all flex gap-6 shadow-sm">
            <div className="w-24 h-24 rounded-lg overflow-hidden flex-shrink-0 border border-outline-variant/50">
              <img className="w-full h-full object-cover" alt="Official graphic" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDy-8vyEGSodkO4j3--xOJ8h6_-cxBUp9Opvc79Bn-uS2XX0NdXlj3zmatLwtb1U1hFa4aNG-fC90yhP62v36dfT3JBxLOWz2-EPES5TB24jYG_TFQJvsNbf_gZ0OI9TGGft6iDa_99paY4rc2cPShOMSpYp0bMJvl0hUp3HCrWfpL-9pZPBfxwzaYbBsdGNceompnNS6wjtBqFPHwxiWnZRCjLGv3gNA6Ngp_32z2K9jPFX_Acg-xqdrrJcSwG0vHCB-vvtad-PV0U"/>
            </div>
            <div className="flex-1">
              <div className="flex justify-between items-start">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <span className="material-symbols-outlined text-primary text-lg">movie</span>
                    <span className="text-[10px] font-black text-on-surface-variant uppercase tracking-widest">TikTok / Instagram</span>
                  </div>
                  <h4 className="text-base font-bold text-on-surface">Resumen Sesión Legislativa - Sem 42</h4>
                  <p className="text-xs text-on-surface-variant mt-1">Mark J. (Equipo Comms)</p>
                </div>
                <div className="flex flex-col items-end gap-1">
                  <span className="text-[9px] font-black px-2 py-0.5 rounded bg-surface-container text-on-surface uppercase">Esperando Video</span>
                  <span className="text-[10px] text-on-surface-variant">Vence: 22 Oct</span>
                </div>
              </div>
              <div className="mt-6 flex gap-3">
                <button className="bg-primary text-white px-5 py-2 rounded text-xs font-bold hover:opacity-90 transition-all">Aprobar</button>
                <button className="bg-surface-container text-on-surface px-5 py-2 rounded text-xs font-bold hover:bg-surface-container-high transition-all">Rechazar</button>
                <div className="flex-1 flex items-center relative ml-2">
                  <input className="w-full bg-surface-container border-none rounded py-2 pl-3 pr-10 text-xs focus:ring-1 focus:ring-primary outline-none" placeholder="Añadir feedback..." type="text"/>
                  <button className="absolute right-2 text-on-surface-variant hover:text-on-surface transition-colors">
                    <span className="material-symbols-outlined text-sm">send</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Stats Sidebar */}
        <div className="space-y-8">
          <h3 className="text-xl font-bold text-on-surface font-headline tracking-tight">Velocidad de Plataforma</h3>
          
          <div className="bg-surface-container-lowest p-6 rounded-lg border border-outline-variant/50 shadow-sm">
            <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mb-6">Resumen de Engagement</p>
            <div className="space-y-6">
              <div className="flex justify-between items-end">
                <div>
                  <p className="text-3xl font-black text-on-surface font-headline">42.8k</p>
                  <p className="text-xs text-on-surface-variant">Impresiones Totales</p>
                </div>
                <div className="text-primary font-bold text-xs flex items-center gap-1">
                  <span className="material-symbols-outlined text-sm">trending_up</span>
                  +12.4%
                </div>
              </div>
              <div className="w-full bg-outline-variant h-1.5 rounded-full overflow-hidden">
                <div className="bg-primary h-full rounded-full w-[70%]"></div>
              </div>
              <div className="grid grid-cols-2 gap-4 pt-2">
                <div>
                  <p className="text-lg font-bold text-on-surface">12</p>
                  <p className="text-[10px] text-on-surface-variant uppercase font-bold">Programados</p>
                </div>
                <div>
                  <p className="text-lg font-bold text-primary">08</p>
                  <p className="text-[10px] text-on-surface-variant uppercase font-bold">En Vivo Hoy</p>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-surface p-6 rounded-lg border border-outline-variant/50 shadow-sm">
            <p className="text-[10px] font-black text-on-surface-variant uppercase tracking-[0.2em] mb-4">Mix de Contenido</p>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span className="w-2 h-2 bg-primary rounded-full"></span>
                  <span className="text-sm font-medium text-on-surface-variant">Informativo</span>
                </div>
                <span className="text-sm font-bold text-on-surface">45%</span>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span className="w-2 h-2 bg-secondary rounded-full"></span>
                  <span className="text-sm font-medium text-on-surface-variant">Comunidad</span>
                </div>
                <span class="text-sm font-bold text-on-surface">30%</span>
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <span className="w-2 h-2 bg-on-surface rounded-full"></span>
                  <span className="text-sm font-medium text-on-surface-variant">Actualizaciones Urgentes</span>
                </div>
                <span className="text-sm font-bold text-on-surface">25%</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
