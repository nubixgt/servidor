import React from 'react';

export default function ResponsableDashboard() {
  return (
    <div className="space-y-10 animate-in fade-in duration-500">
      {/* Header Section */}
      <header className="mb-10 flex flex-col md:flex-row md:justify-between md:items-end gap-6">
        <div className="max-w-2xl">
          <h1 className="text-4xl font-extrabold text-on-surface tracking-tight mb-2 font-headline">Dashboard Técnico</h1>
          <p className="text-on-surface-variant leading-relaxed">Bienvenido, Técnico. Aquí tienes el estado actual de tu categoría asignada.</p>
        </div>
        <div className="flex space-x-4">
          <div className="bg-surface-container-low px-4 py-2 rounded-xl flex items-center space-x-3">
            <span className="material-symbols-outlined text-primary">calendar_today</span>
            <span className="text-sm font-semibold">Martes, 24 Oct 2023</span>
          </div>
        </div>
      </header>

      {/* Bento Grid Layout */}
      <div className="grid grid-cols-12 gap-6">
        
        {/* Personal KPIs - Column 1-4 */}
        <section className="col-span-12 lg:col-span-4 space-y-6">
          <div className="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_12px_40px_rgba(43,52,55,0.06)] flex flex-col justify-between h-full min-h-[400px]">
            <div>
              <h3 className="text-lg font-bold mb-6 flex items-center space-x-2 font-headline">
                <span className="material-symbols-outlined text-primary">assignment_ind</span>
                <span>Mis Tareas del Día</span>
              </h3>
              <div className="space-y-4">
                <div className="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                  <div className="flex items-center space-x-3">
                    <span className="h-2 w-2 rounded-full bg-error"></span>
                    <span className="text-sm font-medium">Revisión de Auditoría #42</span>
                  </div>
                  <span className="text-xs font-bold text-on-surface-variant">09:00</span>
                </div>
                <div className="flex items-center justify-between p-4 bg-surface-container-low rounded-xl">
                  <div className="flex items-center space-x-3">
                    <span className="h-2 w-2 rounded-full bg-primary"></span>
                    <span className="text-sm font-medium">Firma de Certificados</span>
                  </div>
                  <span className="text-xs font-bold text-on-surface-variant">11:30</span>
                </div>
                <div className="flex items-center justify-between p-4 bg-surface-container-low rounded-xl opacity-60 line-through">
                  <div className="flex items-center space-x-3">
                    <span className="h-2 w-2 rounded-full bg-outline"></span>
                    <span className="text-sm font-medium">Sincronización Equipo</span>
                  </div>
                  <span className="material-symbols-outlined text-sm">check_circle</span>
                </div>
              </div>
            </div>
            <div className="mt-8 pt-8 border-t border-surface-container">
              <div className="flex justify-between items-end mb-2">
                <span className="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Avance Semanal</span>
                <span className="text-2xl font-black text-primary font-headline">72%</span>
              </div>
              <div className="w-full bg-primary-container h-3 rounded-full overflow-hidden">
                <div className="bg-primary h-full rounded-full" style={{ width: '72%' }}></div>
              </div>
            </div>
          </div>
        </section>

        {/* Module Status: Fiscalización - Column 5-12 */}
        <section className="col-span-12 lg:col-span-8 bg-surface-container p-8 rounded-3xl space-y-8">
          <div className="flex justify-between items-start">
            <div>
              <h2 className="text-2xl font-bold mb-1 font-headline">Módulo: Fiscalización</h2>
              <p className="text-sm text-on-surface-variant">Indicadores de rendimiento de área en tiempo real</p>
            </div>
            <span className="bg-primary-container text-on-primary-container px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">Estado: Operativo</span>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Metric Card 1 */}
            <div className="bg-surface-container-lowest p-6 rounded-2xl flex flex-col">
              <span className="text-xs font-semibold text-on-surface-variant mb-4">Inspecciones Activas</span>
              <div className="flex items-end space-x-2">
                <span className="text-4xl font-black font-headline">128</span>
                <span className="text-error text-xs font-bold flex items-center mb-1">
                  <span className="material-symbols-outlined text-sm">arrow_upward</span> 12%
                </span>
              </div>
            </div>
            {/* Metric Card 2 */}
            <div className="bg-surface-container-lowest p-6 rounded-2xl flex flex-col">
              <span className="text-xs font-semibold text-on-surface-variant mb-4">Eficiencia de Cierre</span>
              <div className="flex items-end space-x-2">
                <span className="text-4xl font-black font-headline">94.2%</span>
                <span className="text-primary text-xs font-bold flex items-center mb-1">
                  <span className="material-symbols-outlined text-sm">trending_up</span> 0.5
                </span>
              </div>
            </div>
            {/* Metric Card 3 */}
            <div className="bg-surface-container-lowest p-6 rounded-2xl flex flex-col">
              <span className="text-xs font-semibold text-on-surface-variant mb-4">Alertas de Módulo</span>
              <div className="flex items-end space-x-2">
                <span className="text-4xl font-black font-headline">03</span>
                <span className="bg-error-container text-on-error-container px-2 py-0.5 rounded text-[10px] font-bold mb-1 ml-2">URGENTE</span>
              </div>
            </div>
          </div>

          {/* Featured Chart Mockup */}
          <div className="bg-surface-container-lowest rounded-2xl p-6 h-64 relative overflow-hidden flex items-end justify-between space-x-2">
            <div className="absolute top-6 left-6 flex space-x-4 items-center">
              <span className="text-sm font-bold">Distribución de Recursos</span>
              <div className="flex items-center space-x-2">
                <span className="w-3 h-3 rounded-full bg-primary"></span>
                <span className="text-[10px] text-on-surface-variant font-medium">Terreno</span>
              </div>
              <div className="flex items-center space-x-2">
                <span className="w-3 h-3 rounded-full bg-secondary"></span>
                <span className="text-[10px] text-on-surface-variant font-medium">Gabinete</span>
              </div>
            </div>
            {/* Visual bar placeholder */}
            <div className="w-full bg-primary/10 h-32 rounded-t-lg"></div>
            <div className="w-full bg-primary/30 h-48 rounded-t-lg"></div>
            <div className="w-full bg-primary h-24 rounded-t-lg"></div>
            <div className="w-full bg-primary/20 h-56 rounded-t-lg"></div>
            <div className="w-full bg-primary/60 h-40 rounded-t-lg"></div>
            <div className="w-full bg-primary/40 h-32 rounded-t-lg"></div>
            <div className="w-full bg-primary h-44 rounded-t-lg"></div>
            <div className="w-full bg-primary/10 h-28 rounded-t-lg"></div>
            <div className="w-full bg-primary/70 h-52 rounded-t-lg"></div>
          </div>
        </section>

        {/* Pending Tasks requiring immediate action - Column 1-8 */}
        <section className="col-span-12 lg:col-span-8 bg-surface-container-lowest p-8 rounded-3xl shadow-[0_12px_40px_rgba(43,52,55,0.06)]">
          <div className="flex justify-between items-center mb-8">
            <h3 className="text-xl font-bold flex items-center space-x-2 font-headline">
              <span className="material-symbols-outlined text-error">priority_high</span>
              <span>Acción Inmediata (Módulo)</span>
            </h3>
            <button className="text-primary text-sm font-bold hover:underline">Ver todas</button>
          </div>
          <div className="space-y-1">
            {/* Task Item */}
            <div className="group flex items-center p-4 hover:bg-surface-container-low rounded-2xl transition-all cursor-pointer">
              <div className="w-12 h-12 bg-error/10 text-error rounded-xl flex items-center justify-center mr-4">
                <span className="material-symbols-outlined">gavel</span>
              </div>
              <div className="flex-1">
                <h4 className="font-bold text-sm">Denuncia Ciudadana #8819</h4>
                <p className="text-xs text-on-surface-variant">Obstrucción de vía pública - Zona Norte</p>
              </div>
              <div className="text-right flex items-center space-x-6">
                <div className="hidden md:block">
                  <p className="text-xs font-bold text-on-surface">Vence en 2h</p>
                  <p className="text-[10px] text-error font-medium italic">Prioridad Crítica</p>
                </div>
                <span className="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
              </div>
            </div>
            {/* Task Item */}
            <div className="group flex items-center p-4 hover:bg-surface-container-low rounded-2xl transition-all cursor-pointer">
              <div className="w-12 h-12 bg-tertiary-container text-on-tertiary-container rounded-xl flex items-center justify-center mr-4">
                <span className="material-symbols-outlined">contract</span>
              </div>
              <div className="flex-1">
                <h4 className="font-bold text-sm">Validación de Licencia Comercial</h4>
                <p className="text-xs text-on-surface-variant">Expediente 2023-LC-451 - Pendiente firma Resp.</p>
              </div>
              <div className="text-right flex items-center space-x-6">
                <div className="hidden md:block">
                  <p className="text-xs font-bold text-on-surface">Vence hoy</p>
                  <p className="text-[10px] text-on-surface-variant font-medium">Pendiente Firma</p>
                </div>
                <span className="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
              </div>
            </div>
            {/* Task Item */}
            <div className="group flex items-center p-4 hover:bg-surface-container-low rounded-2xl transition-all cursor-pointer">
              <div className="w-12 h-12 bg-secondary-container text-on-secondary-container rounded-xl flex items-center justify-center mr-4">
                <span className="material-symbols-outlined">feedback</span>
              </div>
              <div className="flex-1">
                <h4 className="font-bold text-sm">Descargo de Infracción #221</h4>
                <p className="text-xs text-on-surface-variant">Revisión de pruebas presentadas por el titular</p>
              </div>
              <div className="text-right flex items-center space-x-6">
                <div className="hidden md:block">
                  <p className="text-xs font-bold text-on-surface">Vence mañana</p>
                  <p className="text-[10px] text-on-surface-variant font-medium">En cola de revisión</p>
                </div>
                <span className="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
              </div>
            </div>
          </div>
        </section>

        {/* Calendar & Events - Column 9-12 */}
        <section className="col-span-12 lg:col-span-4 space-y-6">
          <div className="bg-surface-container-low p-8 rounded-3xl flex flex-col h-full">
            <h3 className="text-lg font-bold mb-6 flex items-center space-x-2 font-headline">
              <span className="material-symbols-outlined text-primary">event</span>
              <span>Próximas Citaciones</span>
            </h3>
            <div className="space-y-6">
              <div className="relative pl-6 border-l-2 border-primary">
                <span className="absolute -left-[5px] top-0 w-2 h-2 rounded-full bg-primary ring-4 ring-surface-container-low"></span>
                <p className="text-[10px] font-bold text-primary uppercase tracking-tighter">Mañana • 10:00 AM</p>
                <h5 className="text-sm font-bold mt-1">Audiencia de Conciliación</h5>
                <p className="text-xs text-on-surface-variant">Sala 4 - Caso #299-A</p>
              </div>
              <div className="relative pl-6 border-l-2 border-outline-variant/30">
                <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-tighter">Jueves • 09:30 AM</p>
                <h5 className="text-sm font-bold mt-1">Reunión de Gabinete</h5>
                <p className="text-xs text-on-surface-variant">Virtual - Reporte Mensual</p>
              </div>
              <div className="relative pl-6 border-l-2 border-outline-variant/30">
                <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-tighter">Viernes • 15:00 PM</p>
                <h5 className="text-sm font-bold mt-1">Inspección In Situ</h5>
                <p className="text-xs text-on-surface-variant">Complejo Industrial Norte</p>
              </div>
            </div>
            <div className="mt-auto pt-10">
              <div className="rounded-2xl overflow-hidden relative h-32 group cursor-pointer">
                <img alt="Bureau Workspace" className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=400&h=200" referrerPolicy="no-referrer" />
                <div className="absolute inset-0 bg-primary/40 flex flex-col items-center justify-center text-white">
                  <p className="text-[10px] font-bold uppercase tracking-widest">Calendario Completo</p>
                  <span className="material-symbols-outlined text-2xl mt-1">open_in_new</span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      {/* Floating Action Component for focused screens */}
      <div className="fixed bottom-10 right-10 flex flex-col space-y-4 z-50">
        <button className="w-14 h-14 bg-surface-container-lowest shadow-[0_12px_40px_rgba(43,52,55,0.06)] rounded-full flex items-center justify-center text-primary hover:bg-primary hover:text-white transition-all duration-300">
          <span className="material-symbols-outlined">chat_bubble</span>
        </button>
        <button className="w-14 h-14 bg-primary shadow-[0_12px_40px_rgba(43,52,55,0.06)] rounded-full flex items-center justify-center text-white hover:scale-110 transition-all duration-300">
          <span className="material-symbols-outlined">add</span>
        </button>
      </div>
    </div>
  );
}
