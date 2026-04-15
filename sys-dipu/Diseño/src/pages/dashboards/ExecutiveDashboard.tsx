import React from 'react';

export default function ExecutiveDashboard() {
  return (
    <div className="space-y-10 animate-in fade-in duration-500">
      {/* Header Section */}
      <div className="flex justify-between items-end">
        <div>
          <h2 className="text-2xl font-extrabold text-on-surface tracking-tight font-headline">Dashboard Administrador</h2>
          <p className="text-on-surface-variant/60 text-sm font-medium mt-1">Vista global de la gestión legislativa</p>
        </div>
        <div className="flex gap-3">
          <button className="px-5 py-2 bg-surface text-on-surface-variant border border-outline-variant/50 rounded-lg font-bold text-[11px] flex items-center gap-2 hover:bg-background transition-all shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] uppercase tracking-wider">
            <span className="material-symbols-outlined text-base">download</span> Exportar Reporte
          </button>
          <button className="px-5 py-2 bg-primary text-white rounded-lg font-bold text-[11px] flex items-center gap-2 hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 uppercase tracking-wider">
            <span className="material-symbols-outlined text-base">add</span> Nueva Tarea
          </button>
        </div>
      </div>

      {/* KPI Cards - Minimal Style */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div className="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
          <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Tareas Pendientes</p>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold text-on-surface">24</span>
            <span className="text-[10px] text-primary font-bold">+12%</span>
          </div>
          <div className="mt-6 w-full bg-background h-1 rounded-full overflow-hidden">
            <div className="bg-primary h-full w-[65%] rounded-full"></div>
          </div>
        </div>
        
        <div className="bg-error-container/20 p-6 rounded-xl border border-error/10 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
          <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-error/60 mb-4">Tareas Vencidas</p>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold text-error">08</span>
            <span className="text-[10px] text-error/60 font-semibold">Crítico</span>
          </div>
          <div className="mt-6 w-full bg-error/10 h-1 rounded-full overflow-hidden">
            <div className="bg-error h-full w-[40%] rounded-full"></div>
          </div>
        </div>

        <div className="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
          <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Próximos 7 Días</p>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold text-on-surface">15</span>
            <span className="text-[10px] text-on-surface-variant/40 font-medium">Eventos</span>
          </div>
          <div className="mt-6 flex -space-x-1.5">
            <img alt="Team" className="w-6 h-6 rounded-full border-2 border-surface ring-1 ring-outline-variant/10" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjoHbE1Pp0ojaeoZnnLDLtnozj4Y044uhjON2KaMyK6fypdfJqZb2P53rGghkjnMUca1MhZ4Au4DqQ04RSrolpWK8UT7e5pc2WCD2dwYGCeoCjtyAjaQL6vOu7ePD910d8MKfMPvPg9GDz7SASzu62KntSyLF5W_fgPlOTFsMmJwRVFjyuFNFgjOtBizvyf-wXsjPPtp_IUStcR-AlMKKgFMz2RpL9QG5Jf-JGjZUpq8yRjzYolPRmDnVizTi8xfqeosGbCbR1f63w"/>
            <img alt="Team" className="w-6 h-6 rounded-full border-2 border-surface ring-1 ring-outline-variant/10" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCFALZhzUcIV6ir1DL2rDdxupNp20SvvTvuQsjxo0N9vuYm-SggwyJ6-aw1jtONt6A0icXs-NYHWJZQJdpWDzwuNy9B5IT0CscNgu6w8hY4YfcJ-vtHBgmTvNFleDXimBC2QjkgJVdDMvSKz5XyBfhzQCSht8A0L9l66tWiA1rXKhqzdr3oqw3H2GbNxyjbdA-Z4JmsILl4oOeV18IUqc6APRnyi0NEwGL5k55npdL_ytYtfQiOpHAkwwyyvbYb7ZxoaD0uULR42_gL"/>
            <div className="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center text-[8px] font-bold border-2 border-surface ring-1 ring-outline-variant/10 text-on-primary-container">+3</div>
          </div>
        </div>

        <div className="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
          <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Fiscalizaciones</p>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold text-on-surface">06</span>
            <span className="text-[10px] text-primary/70 font-bold">Activas</span>
          </div>
          <div className="mt-6 text-[10px] text-on-surface-variant/50 flex items-center gap-1.5">
            <span className="material-symbols-outlined text-sm text-primary">check_circle</span> 2 cerradas esta semana
          </div>
        </div>

        <div className="bg-surface p-6 rounded-xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] border-b-primary border-b-2">
          <p className="text-[10px] font-bold uppercase tracking-[0.15em] text-on-surface-variant/50 mb-4">Atrasos de Prensa</p>
          <div className="flex items-baseline gap-2">
            <span className="text-3xl font-bold text-primary">03</span>
            <span className="text-[10px] text-on-surface-variant/40 font-medium">Publicaciones</span>
          </div>
          <div className="mt-6 flex gap-1">
            <span className="px-2 py-0.5 bg-background border border-outline-variant/30 text-on-surface-variant/60 text-[8px] font-bold rounded uppercase">Revisar</span>
          </div>
        </div>
      </div>

      {/* Middle Section: Main Charts & Alerts */}
      <div className="grid grid-cols-12 gap-10">
        {/* Left Column: Activity & Performance */}
        <div className="col-span-12 lg:col-span-8 space-y-10">
          {/* Activity Trend */}
          <div className="bg-surface p-10 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
            <div className="flex justify-between items-center mb-10">
              <h3 className="text-lg font-bold text-on-surface tracking-tight font-headline">Tendencia de Actividad Semanal</h3>
              <div className="flex gap-4">
                <span className="flex items-center gap-2 text-[10px] font-bold text-on-surface-variant/70"><span className="w-2 h-2 rounded-full bg-primary"></span> Legislativo</span>
                <span className="flex items-center gap-2 text-[10px] font-bold text-on-surface-variant/40"><span className="w-2 h-2 rounded-full bg-outline-variant"></span> Territorial</span>
              </div>
            </div>
            <div className="h-48 flex items-end justify-between gap-6 px-4">
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full relative h-32 flex items-end">
                  <div className="w-full bg-background rounded-t-lg h-[20%]"></div>
                  <div className="absolute bottom-0 w-full bg-primary/10 h-[45%] rounded-t-lg border-x border-t border-primary/20"></div>
                </div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Lun</span>
              </div>
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full h-32 bg-primary/10 rounded-t-lg border-x border-t border-primary/20 h-[60%]"></div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Mar</span>
              </div>
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full h-32 bg-primary/10 rounded-t-lg border-x border-t border-primary/20 h-[85%]"></div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Mie</span>
              </div>
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full h-32 bg-primary/10 rounded-t-lg border-x border-t border-primary/20 h-[55%]"></div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Jue</span>
              </div>
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full h-32 bg-primary rounded-t-lg h-full"></div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Vie</span>
              </div>
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full h-32 bg-background rounded-t-lg h-[10%]"></div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Sab</span>
              </div>
              <div className="flex-1 flex flex-col items-center gap-3">
                <div className="w-full h-32 bg-background rounded-t-lg h-[5%]"></div>
                <span className="text-[10px] font-bold text-on-surface-variant/40 uppercase">Dom</span>
              </div>
            </div>
          </div>

          {/* Performance & Compromisos */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-10">
            <div className="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
              <h3 className="text-sm font-bold text-on-surface mb-8 uppercase tracking-[0.1em] font-headline">Tareas por Miembro</h3>
              <div className="space-y-8">
                <div className="space-y-2">
                  <div className="flex justify-between text-[10px] font-bold uppercase tracking-tight text-on-surface-variant/60">
                    <span>Dra. Elena Rivas</span>
                    <span className="text-primary">80% EFECTIVIDAD</span>
                  </div>
                  <div className="h-1.5 w-full bg-background rounded-full overflow-hidden">
                    <div className="bg-primary w-[80%] h-full rounded-full"></div>
                  </div>
                </div>
                <div className="space-y-2">
                  <div className="flex justify-between text-[10px] font-bold uppercase tracking-tight text-on-surface-variant/60">
                    <span>Carlos Méndez</span>
                    <span className="text-primary">92% EFECTIVIDAD</span>
                  </div>
                  <div className="h-1.5 w-full bg-background rounded-full overflow-hidden">
                    <div className="bg-primary w-[92%] h-full rounded-full"></div>
                  </div>
                </div>
                <div className="space-y-2">
                  <div className="flex justify-between text-[10px] font-bold uppercase tracking-tight text-on-surface-variant/60">
                    <span>Sofía Castro</span>
                    <span className="text-primary">40% EFECTIVIDAD</span>
                  </div>
                  <div className="h-1.5 w-full bg-background rounded-full overflow-hidden">
                    <div className="bg-primary/30 w-[40%] h-full rounded-full"></div>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
              <h3 className="text-sm font-bold text-on-surface mb-8 uppercase tracking-[0.1em] font-headline">Compromisos</h3>
              <div className="flex items-center gap-8">
                <div className="relative w-28 h-28">
                  <svg className="w-full h-full rotate-[-90deg]" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#F1F3F4" strokeWidth="2.5"></circle>
                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#506670" strokeDasharray="70 100" strokeLinecap="round" strokeWidth="2.5"></circle>
                    <circle cx="18" cy="18" fill="transparent" r="16" stroke="#D3E5F0" strokeDasharray="20 100" strokeDashoffset="-70" strokeLinecap="round" strokeWidth="2.5"></circle>
                  </svg>
                  <div className="absolute inset-0 flex items-center justify-center flex-col leading-none">
                    <span className="text-2xl font-black text-on-surface font-headline">82</span>
                    <span className="text-[7px] font-bold text-on-surface-variant/50 uppercase tracking-widest mt-1">Total</span>
                  </div>
                </div>
                <div className="space-y-3">
                  <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-primary"></span>
                    <span className="text-[10px] font-bold text-on-surface-variant/60">En Proceso</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-primary-container"></span>
                    <span className="text-[10px] font-bold text-on-surface-variant/60">Completados</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-background border border-outline-variant/30"></span>
                    <span className="text-[10px] font-bold text-on-surface-variant/60">Pendientes</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right Column: Sidebar Panels */}
        <div className="col-span-12 lg:col-span-4 space-y-10">
          {/* Alerts Panel */}
          <div className="bg-surface rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)] overflow-hidden">
            <div className="bg-error-container/10 p-5 flex items-center justify-between border-b border-error/5">
              <h3 className="text-xs font-bold text-error/80 flex items-center gap-2 uppercase tracking-widest font-headline">
                <span className="material-symbols-outlined text-base">warning</span> Alertas Críticas
              </h3>
              <span className="text-[9px] font-black bg-error text-white px-2 py-0.5 rounded-full">4</span>
            </div>
            <div className="p-6 divide-y divide-background">
              <div className="pb-4 flex gap-4">
                <div className="w-1.5 h-auto bg-error/20 rounded-full"></div>
                <div>
                  <p className="text-[11px] font-bold text-on-surface leading-tight">Proyecto de Ley: Reforma Educativa</p>
                  <p className="text-[9px] text-on-surface-variant/50 font-medium mt-1">Venció hace 2 días • Elena R.</p>
                </div>
              </div>
              <div className="pt-4 flex gap-4">
                <div className="w-1.5 h-auto bg-error/20 rounded-full"></div>
                <div>
                  <p className="text-[11px] font-bold text-on-surface leading-tight">Informe de Fiscalización - Vialidad</p>
                  <p className="text-[9px] text-on-surface-variant/50 font-medium mt-1">Vence HOY 18:00 • Carlos M.</p>
                </div>
              </div>
            </div>
          </div>

          {/* Mini Calendar */}
          <div className="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
            <div className="flex justify-between items-center mb-6">
              <h3 className="text-[10px] font-bold text-on-surface-variant/70 uppercase tracking-[0.2em] font-headline">Agenda Mensual</h3>
              <span className="text-[10px] font-bold text-primary">Octubre 2023</span>
            </div>
            <div className="grid grid-cols-7 gap-1 text-center mb-8">
              <div className="text-[7px] font-bold text-on-surface-variant/30">DO</div>
              <div className="text-[7px] font-bold text-on-surface-variant/30">LU</div>
              <div className="text-[7px] font-bold text-on-surface-variant/30">MA</div>
              <div className="text-[7px] font-bold text-on-surface-variant/30">MI</div>
              <div className="text-[7px] font-bold text-on-surface-variant/30">JU</div>
              <div className="text-[7px] font-bold text-on-surface-variant/30">VI</div>
              <div className="text-[7px] font-bold text-on-surface-variant/30">SA</div>
              {/* Row 1 */}
              <div className="p-1.5 text-[10px] font-medium text-on-surface-variant/20">28</div>
              <div className="p-1.5 text-[10px] font-medium text-on-surface-variant/20">29</div>
              <div className="p-1.5 text-[10px] font-medium text-on-surface-variant/20">30</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">1</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">2</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">3</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">4</div>
              {/* Selection */}
              <div className="p-1.5 text-[10px] font-bold text-on-surface">5</div>
              <div className="p-1.5 text-[10px] font-bold bg-primary text-white rounded-lg">6</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">7</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">8</div>
              <div className="p-1.5 text-[10px] font-bold bg-primary-container text-on-primary-container rounded-lg">9</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">10</div>
              <div className="p-1.5 text-[10px] font-bold text-on-surface">11</div>
            </div>
            <div className="space-y-4">
              <div className="flex items-center gap-4 group cursor-pointer">
                <div className="w-9 h-9 bg-background rounded-lg flex items-center justify-center flex-col leading-none border border-outline-variant/20 transition-colors group-hover:border-primary/30">
                  <span className="text-xs font-bold text-on-surface">12</span>
                  <span className="text-[6px] font-bold uppercase text-on-surface-variant/40">Oct</span>
                </div>
                <div className="text-[11px]">
                  <p className="font-bold text-on-surface group-hover:text-primary transition-colors">Comisión de Hacienda</p>
                  <p className="text-on-surface-variant/50 text-[10px]">09:00 AM • Sala Principal</p>
                </div>
              </div>
            </div>
          </div>

          {/* Activity Feed */}
          <div className="bg-surface p-8 rounded-2xl border border-outline-variant/20 shadow-[0_1px_3px_rgba(0,0,0,0.02),0_1px_2px_rgba(0,0,0,0.04)]">
            <h3 className="text-[10px] font-bold text-on-surface-variant/70 uppercase tracking-[0.2em] mb-8 font-headline">Feed Reciente</h3>
            <div className="space-y-6 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-[1px] before:bg-background">
              <div className="flex gap-4 relative z-10">
                <div className="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center shadow-sm">
                  <span className="material-symbols-outlined text-[10px] text-on-primary-container" style={{ fontVariationSettings: "'FILL' 1" }}>check</span>
                </div>
                <div className="text-[10px]">
                  <p className="font-bold text-on-surface">Tarea completada</p>
                  <p className="text-on-surface-variant/50">E. Rivas finalizó 'Minuta de Sesión'.</p>
                  <p className="text-[8px] text-on-surface-variant/30 mt-1 uppercase font-bold tracking-tighter">Hace 12 min</p>
                </div>
              </div>
              <div className="flex gap-4 relative z-10">
                <div className="w-6 h-6 rounded-full bg-background border border-outline-variant/20 flex items-center justify-center shadow-sm">
                  <span className="material-symbols-outlined text-[10px] text-on-surface-variant">edit</span>
                </div>
                <div className="text-[10px]">
                  <p className="font-bold text-on-surface">Expediente Actualizado</p>
                  <p className="text-on-surface-variant/50">C. Méndez adjuntó informe técnico.</p>
                  <p className="text-[8px] text-on-surface-variant/30 mt-1 uppercase font-bold tracking-tighter">Hace 1 hora</p>
                </div>
              </div>
              <div className="flex gap-4 relative z-10">
                <div className="w-6 h-6 rounded-full bg-secondary-container/30 flex items-center justify-center shadow-sm">
                  <span className="material-symbols-outlined text-[10px] text-primary">mail</span>
                </div>
                <div className="text-[10px]">
                  <p className="font-bold text-on-surface">Correspondencia</p>
                  <p className="text-on-surface-variant/50">Ingresó oficio del Ministerio de Salud.</p>
                  <p className="text-[8px] text-on-surface-variant/30 mt-1 uppercase font-bold tracking-tighter">Hace 2 horas</p>
                </div>
              </div>
            </div>
            <button className="w-full mt-10 py-3 bg-background border border-outline-variant/20 rounded-lg text-[9px] font-bold text-on-surface-variant/60 hover:bg-primary-container hover:text-on-primary-container hover:border-transparent transition-all uppercase tracking-widest">
              Ver historial completo
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
