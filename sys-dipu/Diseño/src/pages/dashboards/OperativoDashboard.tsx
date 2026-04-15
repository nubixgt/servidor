import React from 'react';

export default function OperativoDashboard() {
  return (
    <div className="space-y-10 animate-in fade-in duration-500">
      <header className="mb-10">
        <h1 className="text-[2.75rem] font-extrabold text-on-surface leading-tight tracking-tight mb-2 font-headline">Escritorio Operativo</h1>
        <p className="text-on-surface-variant max-w-2xl leading-relaxed">Bienvenido de nuevo. Aquí tienes un resumen de tus responsabilidades y la agenda legislativa para el día de hoy.</p>
      </header>

      <div className="grid grid-cols-12 gap-8 items-start">
        {/* Main Flow: Bento Grid Layout */}
        <div className="col-span-12 lg:col-span-8 space-y-8">
          
          {/* Section: Mis Tareas del Día */}
          <section>
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-xl font-bold text-on-surface flex items-center gap-2 font-headline">
                <span className="material-symbols-outlined text-primary">task_alt</span>
                Mis Tareas del Día
              </h2>
              <span className="text-xs font-semibold text-primary bg-primary-container px-3 py-1 rounded-full">4 PENDIENTES</span>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-surface-container-lowest p-6 rounded-xl shadow-[0_12px_40px_rgba(43,52,55,0.06)] group hover:bg-surface-container-low transition-all border-l-4 border-primary">
                <h3 className="font-bold text-on-surface mb-2">Revisión de Iniciativa 2024-B</h3>
                <p className="text-sm text-on-surface-variant mb-4">Analizar el impacto presupuestario para la nueva ley de infraestructura urbana.</p>
                <div className="flex items-center justify-between">
                  <span className="text-xs text-on-surface-variant flex items-center gap-1">
                    <span className="material-symbols-outlined text-sm">schedule</span> 09:00 AM
                  </span>
                  <button className="text-primary text-xs font-bold hover:underline">REVISAR</button>
                </div>
              </div>
              <div className="bg-surface-container-lowest p-6 rounded-xl shadow-[0_12px_40px_rgba(43,52,55,0.06)] group hover:bg-surface-container-low transition-all border-l-4 border-tertiary">
                <h3 className="font-bold text-on-surface mb-2">Firma de Dictamen</h3>
                <p className="text-sm text-on-surface-variant mb-4">Validar digitalmente el dictamen de la comisión de hacienda.</p>
                <div className="flex items-center justify-between">
                  <span className="text-xs text-on-surface-variant flex items-center gap-1">
                    <span className="material-symbols-outlined text-sm">schedule</span> 11:30 AM
                  </span>
                  <button className="text-primary text-xs font-bold hover:underline">FIRMAR</button>
                </div>
              </div>
            </div>
          </section>

          {/* Section: Mis Compromisos (Asymmetric Card Layout) */}
          <section>
            <h2 className="text-xl font-bold text-on-surface flex items-center gap-2 mb-6 font-headline">
              <span className="material-symbols-outlined text-primary">handshake</span>
              Mis Compromisos
            </h2>
            <div className="bg-surface-container rounded-2xl p-1 overflow-hidden">
              <div className="bg-surface-container-lowest rounded-xl p-8 flex flex-col md:flex-row gap-8 items-center">
                <div className="w-32 h-32 rounded-2xl overflow-hidden flex-shrink-0">
                  <img alt="Reunión institucional" className="w-full h-full object-cover" src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=400&h=400" referrerPolicy="no-referrer" />
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-2">
                    <span className="bg-tertiary-container text-on-tertiary-container text-[10px] font-bold px-2 py-0.5 rounded uppercase">Alta Prioridad</span>
                    <span className="text-on-surface-variant text-sm font-medium">Reunión de Gabinete</span>
                  </div>
                  <h3 className="text-2xl font-bold text-on-surface mb-3 font-headline">Presentación de Lineamientos 2025</h3>
                  <p className="text-on-surface-variant leading-relaxed text-sm mb-4">Exposición ante la comisión de planeación sobre los nuevos protocolos de transparencia y gestión digital del archivo administrativo.</p>
                  <div className="flex flex-wrap gap-4">
                    <div className="flex items-center gap-2 text-on-surface-variant text-sm">
                      <span className="material-symbols-outlined text-primary text-lg">calendar_today</span>
                      <span>Mañana, 10 de Oct</span>
                    </div>
                    <div className="flex items-center gap-2 text-on-surface-variant text-sm">
                      <span className="material-symbols-outlined text-primary text-lg">location_on</span>
                      <span>Sala B, Piso 4</span>
                    </div>
                  </div>
                </div>
                <div className="flex md:flex-col gap-2">
                  <button className="bg-primary text-on-primary px-6 py-2 rounded-lg font-bold text-sm shadow-sm hover:bg-primary/90 transition-colors">Confirmar</button>
                  <button className="bg-surface-container-high text-on-surface px-6 py-2 rounded-lg font-bold text-sm hover:bg-surface-container-highest transition-colors">Detalles</button>
                </div>
              </div>
            </div>
          </section>
        </div>

        {/* Side Flow: Notifications & Activities */}
        <div className="col-span-12 lg:col-span-4 space-y-8">
          
          {/* Section: Notificaciones Personales */}
          <section className="bg-surface-container-low p-6 rounded-2xl">
            <h2 className="text-lg font-bold text-on-surface flex items-center gap-2 mb-6 font-headline">
              <span className="material-symbols-outlined text-primary">notifications_active</span>
              Notificaciones
            </h2>
            <div className="space-y-6">
              <div className="flex gap-4 group">
                <div className="w-2 h-2 mt-2 rounded-full bg-primary flex-shrink-0"></div>
                <div>
                  <p className="text-sm font-bold text-on-surface">Actualización de Expediente</p>
                  <p className="text-xs text-on-surface-variant mt-1">El folio L-452 ha sido movido a "Revisión Técnica" por el Responsable.</p>
                  <span className="text-[10px] text-outline-variant mt-2 block">Hace 15 min</span>
                </div>
              </div>
              <div className="flex gap-4 group">
                <div className="w-2 h-2 mt-2 rounded-full bg-outline-variant flex-shrink-0"></div>
                <div>
                  <p className="text-sm font-bold text-on-surface">Nuevo Comentario</p>
                  <p className="text-xs text-on-surface-variant mt-1">Lucía M. comentó en tu borrador de Ley de Aguas.</p>
                  <span className="text-[10px] text-outline-variant mt-2 block">Hace 2 horas</span>
                </div>
              </div>
              <div className="flex gap-4 group">
                <div className="w-2 h-2 mt-2 rounded-full bg-outline-variant flex-shrink-0"></div>
                <div>
                  <p className="text-sm font-bold text-on-surface">Tarea Completada</p>
                  <p className="text-xs text-on-surface-variant mt-1">Has finalizado el "Reporte Semanal de Productividad".</p>
                  <span className="text-[10px] text-outline-variant mt-2 block">Ayer</span>
                </div>
              </div>
            </div>
            <button className="w-full mt-6 text-sm font-bold text-primary hover:text-primary-dim transition-colors py-2 border-t border-outline-variant/15">VER TODO EL FEED</button>
          </section>

          {/* Section: Próximas Actividades */}
          <section>
            <h2 className="text-lg font-bold text-on-surface flex items-center gap-2 mb-4 font-headline">
              <span className="material-symbols-outlined text-primary">event_upcoming</span>
              Próximas Actividades
            </h2>
            <div className="space-y-4">
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm flex items-center gap-4 border border-outline-variant/10">
                <div className="flex flex-col items-center justify-center bg-primary-container text-on-primary-container w-12 h-12 rounded-lg font-bold">
                  <span className="text-[10px] uppercase">Oct</span>
                  <span className="text-lg leading-none">12</span>
                </div>
                <div className="flex-1">
                  <p className="text-sm font-bold text-on-surface">Sesión Ordinaria</p>
                  <p className="text-xs text-on-surface-variant">Salón de Plenos, 10:00</p>
                </div>
                <span className="material-symbols-outlined text-on-surface-variant">chevron_right</span>
              </div>
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm flex items-center gap-4 border border-outline-variant/10">
                <div className="flex flex-col items-center justify-center bg-surface-container text-on-surface-variant w-12 h-12 rounded-lg font-bold">
                  <span className="text-[10px] uppercase">Oct</span>
                  <span className="text-lg leading-none">15</span>
                </div>
                <div className="flex-1">
                  <p className="text-sm font-bold text-on-surface">Taller de Capacitación</p>
                  <p className="text-xs text-on-surface-variant">Auditorio Digital, 14:00</p>
                </div>
                <span className="material-symbols-outlined text-on-surface-variant">chevron_right</span>
              </div>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}
