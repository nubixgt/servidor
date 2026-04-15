import React from 'react';

export default function CoordinatorDashboard() {
  return (
    <div className="space-y-10 animate-in fade-in duration-500">
      {/* Header & Breadcrumbs */}
      <div className="mb-10 max-w-7xl mx-auto">
        <nav className="flex text-[10px] font-bold uppercase tracking-widest text-outline mb-2 gap-2 items-center">
          <span>Ecosistema</span>
          <span className="material-symbols-outlined text-[10px]">chevron_right</span>
          <span className="text-primary">Dashboard Central</span>
        </nav>
        <h2 className="text-[2.75rem] font-black text-on-surface leading-tight tracking-tighter font-headline">
          Estado del Centro de <span className="text-primary italic">Comando</span>.
        </h2>
      </div>

      {/* Bento Grid Section */}
      <div className="grid grid-cols-12 gap-8 max-w-7xl mx-auto">
        
        {/* KPI Section (Asymmetric) */}
        <div className="col-span-12 lg:col-span-8 grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
          <div className="bg-surface-container-lowest p-6 rounded-xl border border-transparent shadow-[0_12px_40px_rgba(43,52,55,0.04)]">
            <p className="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Tareas Equipo</p>
            <h3 className="text-3xl font-black text-on-surface font-headline">1,284</h3>
            <div className="mt-2 flex items-center text-primary text-[10px] font-bold">
              <span className="material-symbols-outlined text-xs mr-1">trending_up</span> +12% vs ayer
            </div>
          </div>
          <div className="bg-surface-container-lowest p-6 rounded-xl border border-transparent shadow-[0_12px_40px_rgba(43,52,55,0.04)]">
            <p className="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Cumplimiento</p>
            <h3 className="text-3xl font-black text-on-surface font-headline">94.2%</h3>
            <div className="mt-2 flex items-center text-primary text-[10px] font-bold">
              <span className="material-symbols-outlined text-xs mr-1">check_circle</span> Meta superada
            </div>
          </div>
          <div className="bg-surface-container-lowest p-6 rounded-xl border border-transparent shadow-[0_12px_40px_rgba(43,52,55,0.04)]">
            <p className="text-[10px] font-bold text-outline uppercase tracking-widest mb-1">Fiscalizaciones</p>
            <h3 className="text-3xl font-black text-on-surface font-headline">42</h3>
            <div className="mt-2 flex items-center text-on-surface-variant text-[10px] font-bold">
              <span className="material-symbols-outlined text-xs mr-1">sensors</span> En tiempo real
            </div>
          </div>
          <div className="bg-surface-container-low p-6 rounded-xl border-b-2 border-error/30">
            <p className="text-[10px] font-bold text-error uppercase tracking-widest mb-1">Alertas Inactividad</p>
            <h3 className="text-3xl font-black text-error font-headline">07</h3>
            <div className="mt-2 flex items-center text-error text-[10px] font-bold">
              <span className="material-symbols-outlined text-xs mr-1">warning</span> Acción inmediata
            </div>
          </div>
        </div>

        {/* Performance Chart (Bento Large) */}
        <div className="col-span-12 lg:col-span-8 bg-surface-container-lowest p-8 rounded-2xl shadow-[0_12px_40px_rgba(43,52,55,0.06)]">
          <div className="flex justify-between items-end mb-10">
            <div>
              <h4 className="text-lg font-bold text-on-surface font-headline">Rendimiento por Miembro</h4>
              <p className="text-sm text-outline">Comparativa de carga vs efectividad semanal</p>
            </div>
            <div className="flex gap-4">
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-primary"></span>
                <span className="text-[10px] font-bold uppercase text-outline">Completadas</span>
              </div>
              <div className="flex items-center gap-2">
                <span className="w-3 h-3 rounded-full bg-surface-container-highest"></span>
                <span className="text-[10px] font-bold uppercase text-outline">Asignadas</span>
              </div>
            </div>
          </div>
          
          {/* Mock Chart with Tonal Layers */}
          <div className="space-y-6">
            <div className="space-y-2">
              <div className="flex justify-between text-xs font-bold text-on-surface-variant">
                <span>Elena Valdés (Coord. Sr)</span>
                <span>45/50</span>
              </div>
              <div className="h-3 w-full bg-surface-container-high rounded-full overflow-hidden flex">
                <div className="h-full bg-primary" style={{ width: '90%' }}></div>
              </div>
            </div>
            <div className="space-y-2">
              <div className="flex justify-between text-xs font-bold text-on-surface-variant">
                <span>Julian Ricci (Auditor)</span>
                <span>28/48</span>
              </div>
              <div className="h-3 w-full bg-surface-container-high rounded-full overflow-hidden flex">
                <div className="h-full bg-primary" style={{ width: '58%' }}></div>
              </div>
            </div>
            <div className="space-y-2">
              <div className="flex justify-between text-xs font-bold text-on-surface-variant">
                <span>Sofía Chen (Operaciones)</span>
                <span>52/52</span>
              </div>
              <div className="h-3 w-full bg-surface-container-high rounded-full overflow-hidden flex">
                <div className="h-full bg-primary" style={{ width: '100%' }}></div>
              </div>
            </div>
            <div className="space-y-2">
              <div className="flex justify-between text-xs font-bold text-on-surface-variant">
                <span>Marcos Díaz (Analista)</span>
                <span>12/40</span>
              </div>
              <div className="h-3 w-full bg-surface-container-high rounded-full overflow-hidden flex">
                <div className="h-full bg-primary" style={{ width: '30%' }}></div>
              </div>
            </div>
          </div>
        </div>

        {/* Quick Assignment (Bento Side) */}
        <div className="col-span-12 lg:col-span-4 flex flex-col gap-8">
          <div className="bg-surface-container-low p-6 rounded-2xl">
            <h4 className="text-sm font-black text-on-surface font-headline uppercase tracking-widest mb-4">Asignación Rápida</h4>
            <div className="space-y-3">
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm border-l-4 border-primary group cursor-grab active:cursor-grabbing hover:bg-surface-bright transition-colors">
                <p className="text-xs font-bold mb-1">Fiscalización Sector Delta</p>
                <p className="text-[10px] text-outline">Prioridad: Alta • Expira en 4h</p>
              </div>
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm border-l-4 border-primary-container group cursor-grab active:cursor-grabbing hover:bg-surface-bright transition-colors">
                <p className="text-xs font-bold mb-1">Revisión Documental v2</p>
                <p className="text-[10px] text-outline">Prioridad: Media • Pendiente</p>
              </div>
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm border-l-4 border-outline-variant group cursor-grab active:cursor-grabbing hover:bg-surface-bright transition-colors">
                <p className="text-xs font-bold mb-1">Actualización de Protocolo</p>
                <p className="text-[10px] text-outline">Prioridad: Baja • Sin asignar</p>
              </div>
            </div>
            <button className="w-full mt-4 py-3 text-xs font-bold text-primary hover:bg-primary/5 rounded-lg transition-colors border border-dashed border-primary/30">
              Ver todo el backlog
            </button>
          </div>

          {/* Blockage Notifications */}
          <div className="bg-error-container/20 p-6 rounded-2xl">
            <div className="flex items-center gap-2 mb-4">
              <span className="material-symbols-outlined text-error">dangerous</span>
              <h4 className="text-sm font-black text-on-error-container font-headline uppercase tracking-widest">Bloqueos Críticos</h4>
            </div>
            <div className="space-y-4">
              <div className="flex gap-3">
                <div className="w-2 h-2 rounded-full bg-error mt-1"></div>
                <div>
                  <p className="text-xs font-bold text-on-error-container">Servidor de Integración (SI-9) caído</p>
                  <p className="text-[10px] text-on-error-container/70">Afecta a 14 tareas de fiscalización activa.</p>
                </div>
              </div>
              <div className="flex gap-3">
                <div className="w-2 h-2 rounded-full bg-error mt-1"></div>
                <div>
                  <p className="text-xs font-bold text-on-error-container">Retraso en Aprobación Jurídica</p>
                  <p className="text-[10px] text-on-error-container/70">Proyecto "Etereal Alpha" bloqueado (48h).</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Team Activity Feed (Bento Wide) */}
        <div className="col-span-12 bg-surface-container-high/30 p-8 rounded-3xl">
          <div className="flex justify-between items-center mb-8">
            <h4 className="text-lg font-bold text-on-surface font-headline">Flujo de Actividad Reciente</h4>
            <div className="flex bg-surface-container-lowest rounded-full p-1 shadow-sm">
              <button className="px-4 py-1.5 text-[10px] font-bold rounded-full bg-primary text-white">Global</button>
              <button className="px-4 py-1.5 text-[10px] font-bold rounded-full text-outline hover:text-primary">Mío</button>
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {/* Activity Item 1 */}
            <div className="flex gap-4 items-start p-4 bg-surface-container-lowest rounded-2xl hover:translate-y-[-4px] transition-transform duration-300">
              <div className="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-xs">JR</div>
              <div>
                <p className="text-[11px] font-bold text-on-surface">Julián Ricci <span className="text-outline font-normal">completó</span> Auditoría Sector Sur</p>
                <p className="text-[10px] text-primary mt-1">Hace 12 minutos</p>
              </div>
            </div>
            {/* Activity Item 2 */}
            <div className="flex gap-4 items-start p-4 bg-surface-container-lowest rounded-2xl hover:translate-y-[-4px] transition-transform duration-300">
              <div className="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-bold text-xs">SC</div>
              <div>
                <p className="text-[11px] font-bold text-on-surface">Sofía Chen <span className="text-outline font-normal">adjuntó archivo</span> Plan Maestro 2024</p>
                <p className="text-[10px] text-primary mt-1">Hace 45 minutos</p>
              </div>
            </div>
            {/* Activity Item 3 */}
            <div className="flex gap-4 items-start p-4 bg-surface-container-lowest rounded-2xl hover:translate-y-[-4px] transition-transform duration-300">
              <div className="w-10 h-10 rounded-full bg-tertiary-container flex items-center justify-center">
                <span className="material-symbols-outlined text-on-tertiary-container text-sm">system_update_alt</span>
              </div>
              <div>
                <p className="text-[11px] font-bold text-on-surface">SISTEMA <span className="text-outline font-normal">generó reporte</span> Mensual de Operaciones</p>
                <p className="text-[10px] text-primary mt-1">Hace 2 horas</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Floating Tooltip / Status */}
      <div className="fixed bottom-8 right-8 bg-on-surface text-surface-container-lowest px-6 py-4 rounded-2xl shadow-2xl backdrop-blur-xl bg-opacity-90 flex items-center gap-4 z-50">
        <div className="flex -space-x-2">
          <div className="w-6 h-6 rounded-full border-2 border-on-surface bg-primary-container text-on-primary-container flex items-center justify-center text-[8px] font-bold">A</div>
          <div className="w-6 h-6 rounded-full border-2 border-on-surface bg-secondary-container text-on-secondary-container flex items-center justify-center text-[8px] font-bold">B</div>
          <div className="w-6 h-6 rounded-full border-2 border-on-surface bg-primary text-white text-[8px] flex items-center justify-center">+12</div>
        </div>
        <div className="h-8 w-[1px] bg-outline/20"></div>
        <p className="text-[10px] font-bold uppercase tracking-widest">14 Activos en Línea</p>
      </div>
    </div>
  );
}
