import React from 'react';

export default function ConsultaDashboard() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Page Header (Editorial Authority) */}
      <div className="mb-12 flex justify-between items-end">
        <div>
          <div className="flex items-center gap-3 mb-2">
            <span className="px-2 py-1 bg-primary-container text-on-primary-container text-[10px] font-bold rounded uppercase tracking-wider">Módulo Consulta</span>
            <span className="text-outline-variant text-xs font-medium">Actualizado hace 12 min</span>
          </div>
          <h1 className="text-[2.75rem] font-headline font-extrabold text-on-surface leading-tight tracking-tight">Repositorio de Iniciativas</h1>
          <p className="text-on-surface-variant font-body max-w-2xl mt-2 leading-relaxed">
            Acceso centralizado al historial y estado actual de propuestas legislativas. En modo consulta, el acceso se limita a la visualización de documentos y trazas de auditoría.
          </p>
        </div>
        <div className="flex gap-3">
          <button className="px-5 py-2.5 bg-surface-container-high text-on-surface rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-highest transition-all">
            <span className="material-symbols-outlined text-lg">filter_list</span> Filtrar
          </button>
          <button className="px-5 py-2.5 bg-surface-container-high text-on-surface rounded-xl font-semibold flex items-center gap-2 hover:bg-surface-container-highest transition-all">
            <span className="material-symbols-outlined text-lg">download</span> Exportar Reporte
          </button>
        </div>
      </div>

      {/* Bento Grid Layout */}
      <div className="grid grid-cols-12 gap-6">
        {/* Main Activity Column */}
        <div className="col-span-12 lg:col-span-8 space-y-6">
          {/* Initiative Item 1 */}
          <div className="bg-surface-container-lowest rounded-xl p-6 transition-all hover:bg-surface-container-low group relative shadow-sm border border-outline-variant/20">
            <div className="absolute top-6 right-6 flex items-center gap-2">
              <span className="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-[11px] font-bold rounded-full">EN REVISIÓN</span>
              <span className="material-symbols-outlined text-outline-variant opacity-30 group-hover:opacity-100 transition-opacity">info</span>
            </div>
            <div className="flex gap-6">
              <div className="w-14 h-14 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-primary text-3xl">policy</span>
              </div>
              <div className="flex-1">
                <h3 className="text-xl font-bold text-on-surface mb-2 font-headline">Reforma a la Ley de Aguas Nacionales (2024-B)</h3>
                <p className="text-on-surface-variant text-sm mb-4 leading-relaxed line-clamp-2">
                  Propuesta para la modernización de los sistemas de captación pluvial en zonas metropolitanas de alta densidad poblacional y regulación de pozos profundos.
                </p>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">person_outline</span> Dip. Alejandro Ruiz
                  </div>
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">calendar_today</span> 14 Oct, 2023
                  </div>
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">attachment</span> 3 Documentos
                  </div>
                </div>
              </div>
            </div>
            <div className="mt-6 flex gap-2 border-t border-surface-container-high pt-4 opacity-50">
              <button className="flex-1 py-2 bg-surface-container-low text-on-surface-variant text-xs font-bold rounded-lg cursor-not-allowed" disabled>
                <span className="material-symbols-outlined text-sm align-middle mr-1">visibility</span> Ver Detalles
              </button>
              <button className="flex-1 py-2 bg-surface-container-low text-on-surface-variant text-xs font-bold rounded-lg cursor-not-allowed" disabled>
                <span className="material-symbols-outlined text-sm align-middle mr-1">history</span> Historial
              </button>
            </div>
          </div>

          {/* Initiative Item 2 */}
          <div className="bg-surface-container-lowest rounded-xl p-6 transition-all hover:bg-surface-container-low group relative shadow-sm border border-outline-variant/20">
            <div className="absolute top-6 right-6 flex items-center gap-2">
              <span className="px-3 py-1 bg-surface-container-highest text-on-surface-variant text-[11px] font-bold rounded-full">APROBADO</span>
              <span className="material-symbols-outlined text-outline-variant opacity-30 group-hover:opacity-100 transition-opacity">info</span>
            </div>
            <div className="flex gap-6">
              <div className="w-14 h-14 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-primary text-3xl">verified_user</span>
              </div>
              <div className="flex-1">
                <h3 className="text-xl font-bold text-on-surface mb-2 font-headline">Ley General de Ciberseguridad Institucional</h3>
                <p className="text-on-surface-variant text-sm mb-4 leading-relaxed line-clamp-2">
                  Marco regulatorio para la protección de infraestructura crítica nacional y protocolos de respuesta ante incidentes digitales en dependencias de gobierno.
                </p>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">person_outline</span> Comisión de Justicia
                  </div>
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">calendar_today</span> 02 Sep, 2023
                  </div>
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">attachment</span> 1 Documento
                  </div>
                </div>
              </div>
            </div>
            <div className="mt-6 flex gap-2 border-t border-surface-container-high pt-4 opacity-50">
              <button className="flex-1 py-2 bg-surface-container-low text-on-surface-variant text-xs font-bold rounded-lg cursor-not-allowed" disabled>
                <span className="material-symbols-outlined text-sm align-middle mr-1">visibility</span> Ver Detalles
              </button>
              <button className="flex-1 py-2 bg-surface-container-low text-on-surface-variant text-xs font-bold rounded-lg cursor-not-allowed" disabled>
                <span className="material-symbols-outlined text-sm align-middle mr-1">history</span> Historial
              </button>
            </div>
          </div>

          {/* Initiative Item 3 */}
          <div className="bg-surface-container-lowest rounded-xl p-6 transition-all hover:bg-surface-container-low group relative shadow-sm border border-outline-variant/20">
            <div className="absolute top-6 right-6 flex items-center gap-2">
              <span className="px-3 py-1 bg-error-container/20 text-error text-[11px] font-bold rounded-full">EN PAUSA</span>
              <span className="material-symbols-outlined text-outline-variant opacity-30 group-hover:opacity-100 transition-opacity">info</span>
            </div>
            <div className="flex gap-6">
              <div className="w-14 h-14 rounded-xl bg-surface-container flex items-center justify-center shrink-0">
                <span className="material-symbols-outlined text-primary text-3xl">payments</span>
              </div>
              <div className="flex-1">
                <h3 className="text-xl font-bold text-on-surface mb-2 font-headline">Incentivos Fiscales para Energías Limpias</h3>
                <p className="text-on-surface-variant text-sm mb-4 leading-relaxed line-clamp-2">
                  Régimen de deducibilidad para empresas que implementen sistemas de autogeneración fotovoltaica y eólica en parques industriales.
                </p>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">person_outline</span> Dip. Claudia Mora
                  </div>
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">calendar_today</span> 28 Ago, 2023
                  </div>
                  <div className="flex items-center gap-2 text-xs text-on-surface-variant font-medium">
                    <span className="material-symbols-outlined text-sm">attachment</span> 5 Documentos
                  </div>
                </div>
              </div>
            </div>
            <div className="mt-6 flex gap-2 border-t border-surface-container-high pt-4 opacity-50">
              <button className="flex-1 py-2 bg-surface-container-low text-on-surface-variant text-xs font-bold rounded-lg cursor-not-allowed" disabled>
                <span className="material-symbols-outlined text-sm align-middle mr-1">visibility</span> Ver Detalles
              </button>
              <button className="flex-1 py-2 bg-surface-container-low text-on-surface-variant text-xs font-bold rounded-lg cursor-not-allowed" disabled>
                <span className="material-symbols-outlined text-sm align-middle mr-1">history</span> Historial
              </button>
            </div>
          </div>
        </div>

        {/* Secondary Metrics Column */}
        <div className="col-span-12 lg:col-span-4 space-y-6">
          {/* Stats Card */}
          <div className="bg-primary bg-gradient-to-br from-primary to-primary-dim rounded-2xl p-8 text-on-primary shadow-ambient">
            <div className="flex justify-between items-start mb-10">
              <div>
                <p className="text-[10px] uppercase tracking-[0.2em] font-bold opacity-70">Balance Trimestral</p>
                <h4 className="text-3xl font-headline font-bold mt-1">128</h4>
              </div>
              <span className="material-symbols-outlined text-4xl opacity-40">equalizer</span>
            </div>
            <div className="space-y-4">
              <div className="flex justify-between text-xs items-center">
                <span>Aprobadas</span>
                <span className="font-bold">64%</span>
              </div>
              <div className="h-1.5 w-full bg-on-primary/20 rounded-full overflow-hidden">
                <div className="h-full bg-white" style={{ width: '64%' }}></div>
              </div>
              <div className="flex justify-between text-xs items-center mt-4">
                <span>En Revisión</span>
                <span className="font-bold">22%</span>
              </div>
              <div className="h-1.5 w-full bg-on-primary/20 rounded-full overflow-hidden">
                <div className="h-full bg-white/60" style={{ width: '22%' }}></div>
              </div>
            </div>
          </div>

          {/* Quick Insights (Tonal Layering) */}
          <div className="bg-surface-container rounded-2xl p-6 border border-outline-variant/20">
            <h4 className="font-headline font-bold text-on-surface mb-6 flex items-center gap-2">
              <span className="material-symbols-outlined text-primary">analytics</span> Tendencias
            </h4>
            <div className="space-y-4">
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm">
                <p className="text-[10px] text-on-surface-variant font-bold uppercase mb-1">Tópico más consultado</p>
                <p className="text-sm font-bold text-on-surface">Medio Ambiente y Agua</p>
              </div>
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm">
                <p className="text-[10px] text-on-surface-variant font-bold uppercase mb-1">Actividad Reciente</p>
                <p className="text-sm font-bold text-on-surface">14 nuevas consultas hoy</p>
              </div>
              <div className="bg-surface-container-lowest p-4 rounded-xl shadow-sm">
                <p className="text-[10px] text-on-surface-variant font-bold uppercase mb-1">Sesión Actual</p>
                <p className="text-sm font-bold text-on-surface">Rol: Invitado (Consulta)</p>
              </div>
            </div>
          </div>

          {/* Mode Restriction Notice (Glassmorphism inspired) */}
          <div className="bg-error-container/10 border border-error/20 rounded-2xl p-6 backdrop-blur-sm">
            <div className="flex gap-4">
              <span className="material-symbols-outlined text-error">lock</span>
              <div>
                <h5 className="text-sm font-bold text-error mb-1">Restricciones de Edición</h5>
                <p className="text-xs text-on-surface-variant leading-relaxed">
                  Su cuenta no posee privilegios para modificar, crear o eliminar iniciativas. Contacte al administrador si requiere permisos de escritura.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
