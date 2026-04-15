import React from 'react';

export default function SystemConfig() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header Section */}
      <div className="mb-12 flex justify-between items-start">
        <div>
          <nav aria-label="Breadcrumb" className="flex text-[10px] font-bold text-outline uppercase tracking-widest mb-3">
            <ol className="flex items-center space-x-2">
              <li><a className="hover:text-primary transition-colors" href="#">Sistema</a></li>
              <li><span className="material-symbols-outlined text-[12px]">chevron_right</span></li>
              <li className="text-primary">Configuración</li>
            </ol>
          </nav>
          <h2 className="text-4xl font-extrabold text-on-surface tracking-tighter leading-tight font-headline">Configuración del Sistema</h2>
          <p className="text-on-surface-variant text-base mt-2 max-w-2xl font-medium opacity-80">Control de acceso institucional y parámetros globales de catálogo.</p>
        </div>
        <div className="flex gap-4">
          <button className="px-8 py-3 bg-primary text-white font-bold rounded-lg flex items-center gap-2 hover:bg-on-surface transition-all shadow-sm">
            <span className="material-symbols-outlined text-lg">person_add</span>
            Añadir Usuario
          </button>
        </div>
      </div>

      {/* Dashboard Bento Grid */}
      <div className="grid grid-cols-12 gap-8">
        {/* Main Users Table (Large Span) */}
        <div className="col-span-12 lg:col-span-8 space-y-8">
          <div className="bg-surface rounded-xl border border-outline-variant/30 overflow-hidden shadow-sm">
            <div className="px-8 py-5 border-b border-surface-container-high flex justify-between items-center bg-surface-container-lowest">
              <span className="text-[11px] font-extrabold text-outline uppercase tracking-[0.15em]">Directorio de Usuarios</span>
              <div className="flex gap-4">
                <button className="text-outline hover:text-primary transition-colors"><span className="material-symbols-outlined text-xl">tune</span></button>
                <button className="text-outline hover:text-primary transition-colors"><span className="material-symbols-outlined text-xl">cloud_download</span></button>
              </div>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-surface-container-lowest border-b border-surface-container-high">
                    <th className="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Identidad</th>
                    <th className="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Credencial</th>
                    <th className="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Perfil RBAC</th>
                    <th className="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider">Estado</th>
                    <th className="px-8 py-5 text-[10px] font-bold text-outline uppercase tracking-wider"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-surface-container-low">
                  <tr className="hover:bg-surface-container-low transition-colors group">
                    <td className="px-8 py-5">
                      <div className="flex items-center gap-4">
                        <div className="w-9 h-9 rounded-lg bg-primary-container text-primary flex items-center justify-center font-bold text-xs">MV</div>
                        <span className="font-bold text-on-surface">Mariana Villanueva</span>
                      </div>
                    </td>
                    <td className="px-8 py-5 text-on-surface-variant text-sm font-medium">m.villanueva@legis.gob</td>
                    <td className="px-8 py-5">
                      <span className="text-[10px] px-2.5 py-1 bg-surface-container text-on-surface-variant font-extrabold rounded uppercase">Superadmin</span>
                    </td>
                    <td className="px-8 py-5">
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-container text-primary text-[10px] font-extrabold">
                        <span className="w-1.5 h-1.5 rounded-full bg-primary"></span> ACTIVO
                      </span>
                    </td>
                    <td className="px-8 py-5">
                      <div className="flex items-center justify-end gap-3 text-outline-variant group-hover:text-outline opacity-40 group-hover:opacity-100 transition-all">
                        <span className="material-symbols-outlined text-lg cursor-pointer hover:text-primary">edit_note</span>
                        <span className="material-symbols-outlined text-lg cursor-pointer hover:text-error">do_not_disturb_on</span>
                      </div>
                    </td>
                  </tr>
                  <tr className="hover:bg-surface-container-low transition-colors group">
                    <td className="px-8 py-5">
                      <div className="flex items-center gap-4">
                        <div className="w-9 h-9 rounded-lg bg-surface-container text-outline flex items-center justify-center font-bold text-xs">RC</div>
                        <span className="font-bold text-on-surface">Roberto Castillo</span>
                      </div>
                    </td>
                    <td className="px-8 py-5 text-on-surface-variant text-sm font-medium">r.castillo@legis.gob</td>
                    <td className="px-8 py-5">
                      <span className="text-[10px] px-2.5 py-1 bg-surface-container text-on-surface-variant font-extrabold rounded uppercase">Diputado</span>
                    </td>
                    <td className="px-8 py-5">
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary-container text-primary text-[10px] font-extrabold">
                        <span className="w-1.5 h-1.5 rounded-full bg-primary"></span> ACTIVO
                      </span>
                    </td>
                    <td className="px-8 py-5">
                      <div className="flex items-center justify-end gap-3 text-outline-variant group-hover:text-outline opacity-40 group-hover:opacity-100 transition-all">
                        <span className="material-symbols-outlined text-lg cursor-pointer hover:text-primary">edit_note</span>
                        <span className="material-symbols-outlined text-lg cursor-pointer hover:text-error">do_not_disturb_on</span>
                      </div>
                    </td>
                  </tr>
                  <tr className="hover:bg-surface-container-low transition-colors group">
                    <td className="px-8 py-5">
                      <div className="flex items-center gap-4">
                        <div className="w-9 h-9 rounded-lg bg-surface-container text-outline flex items-center justify-center font-bold text-xs">EL</div>
                        <span className="font-bold text-on-surface">Elena Ledesma</span>
                      </div>
                    </td>
                    <td className="px-8 py-5 text-on-surface-variant text-sm font-medium">e.ledesma@legis.gob</td>
                    <td className="px-8 py-5">
                      <span className="text-[10px] px-2.5 py-1 bg-surface-container text-on-surface-variant font-extrabold rounded uppercase">Analista</span>
                    </td>
                    <td className="px-8 py-5">
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-error-container text-error text-[10px] font-extrabold">
                        <span className="w-1.5 h-1.5 rounded-full bg-error"></span> INACTIVO
                      </span>
                    </td>
                    <td className="px-8 py-5">
                      <div className="flex items-center justify-end gap-3 text-outline-variant group-hover:text-outline opacity-40 group-hover:opacity-100 transition-all">
                        <span className="material-symbols-outlined text-lg cursor-pointer hover:text-primary">edit_note</span>
                        <span className="material-symbols-outlined text-lg cursor-pointer hover:text-secondary">check_circle</span>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div className="px-8 py-5 border-t border-surface-container-low flex items-center justify-between">
              <span className="text-xs text-outline font-medium italic">142 registros encontrados</span>
              <div className="flex gap-2">
                <button className="w-10 h-10 flex items-center justify-center rounded-lg border border-surface-container-high text-outline hover:bg-surface-container-low transition-colors"><span className="material-symbols-outlined text-lg">chevron_left</span></button>
                <button className="w-10 h-10 flex items-center justify-center rounded-lg bg-primary text-white text-xs font-bold">1</button>
                <button className="w-10 h-10 flex items-center justify-center rounded-lg border border-surface-container-high text-xs font-bold hover:bg-surface-container-low">2</button>
                <button className="w-10 h-10 flex items-center justify-center rounded-lg border border-surface-container-high text-outline hover:bg-surface-container-low transition-colors"><span className="material-symbols-outlined text-lg">chevron_right</span></button>
              </div>
            </div>
          </div>

          {/* Audit Log Section */}
          <div className="bg-surface-container-lowest rounded-xl p-8 border border-outline-variant/30 relative overflow-hidden shadow-sm">
            <div className="absolute top-0 right-0 p-8 opacity-5">
              <span className="material-symbols-outlined text-[120px]">verified_user</span>
            </div>
            <div className="flex items-center gap-3 mb-8">
              <span className="material-symbols-outlined text-primary text-2xl">history_edu</span>
              <h3 className="text-lg font-extrabold text-on-surface">Bitácora de Auditoría</h3>
            </div>
            <div className="space-y-6 relative z-10 border-l border-surface-container-high ml-2.5 pl-8">
              <div className="relative">
                <div className="absolute -left-[37px] top-1 w-4 h-4 rounded-full border-4 border-white bg-primary"></div>
                <p className="text-sm text-on-surface font-bold">Cambio de Rol: Usuario Roberto Castillo</p>
                <p className="text-xs text-outline mt-1">Ejecutado por Mariana Villanueva • Hace 12 minutos</p>
              </div>
              <div className="relative">
                <div className="absolute -left-[37px] top-1 w-4 h-4 rounded-full border-4 border-white bg-error"></div>
                <p className="text-sm text-on-surface font-bold">Intento de acceso fallido: IP 192.168.1.45</p>
                <p className="text-xs text-outline mt-1">Módulo de Seguridad • Hace 45 minutos</p>
              </div>
              <div className="relative">
                <div className="absolute -left-[37px] top-1 w-4 h-4 rounded-full border-4 border-white bg-secondary"></div>
                <p className="text-sm text-on-surface font-bold">Actualización de Parámetros: Umbral de Alertas</p>
                <p className="text-xs text-outline mt-1">Configuración Global • Hace 2 horas</p>
              </div>
            </div>
            <button className="mt-10 inline-flex items-center gap-2 text-primary font-extrabold text-xs uppercase tracking-widest hover:opacity-70 transition-all border-b-2 border-primary/20 pb-1">
              Ver bitácora completa
              <span className="material-symbols-outlined text-sm">arrow_forward</span>
            </button>
          </div>
        </div>

        {/* Sidebar Modules */}
        <div className="col-span-12 lg:col-span-4 space-y-8">
          {/* RBAC Form */}
          <div className="bg-surface rounded-xl p-8 border border-outline-variant/30 shadow-sm">
            <h3 className="text-xs font-extrabold text-outline uppercase tracking-[0.2em] mb-8">Nuevo Perfil (RBAC)</h3>
            <form className="space-y-6">
              <div className="space-y-2">
                <label className="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Nombre Completo</label>
                <input className="w-full border border-surface-container-high bg-surface-container-low rounded-lg px-4 py-3 text-sm focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="p. ej. Luis Méndez" type="text"/>
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Email Institucional</label>
                <input className="w-full border border-surface-container-high bg-surface-container-low rounded-lg px-4 py-3 text-sm focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all outline-none" placeholder="l.mendez@legis.gob" type="email"/>
              </div>
              <div className="space-y-2">
                <label className="text-[10px] font-extrabold text-on-surface-variant uppercase tracking-wider">Rol de Acceso</label>
                <div className="relative">
                  <select className="w-full border border-surface-container-high bg-surface-container-low rounded-lg px-4 py-3 text-sm focus:bg-surface focus:border-primary focus:ring-1 focus:ring-primary transition-all appearance-none outline-none cursor-pointer">
                    <option>Seleccione un rol...</option>
                    <option>Superadmin</option>
                    <option>Diputado</option>
                    <option>Coordinador</option>
                    <option>Responsable</option>
                    <option>Operativo</option>
                    <option>Consulta</option>
                  </select>
                  <span className="material-symbols-outlined absolute right-3 top-3.5 text-outline pointer-events-none">expand_more</span>
                </div>
              </div>
              <div className="flex items-start gap-3 pt-2">
                <div className="flex items-center h-5">
                  <input className="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary bg-surface-container-low transition-all" type="checkbox"/>
                </div>
                <span className="text-xs text-outline font-medium leading-none">Notificar credenciales de acceso vía email institucional</span>
              </div>
              <button className="w-full py-3.5 bg-secondary text-white font-bold rounded-lg hover:bg-on-surface transition-all shadow-sm mt-4">Procesar Registro</button>
            </form>
          </div>

          {/* Parameters Section */}
          <div className="bg-primary text-white rounded-xl p-8 relative overflow-hidden shadow-sm">
            <div className="absolute -right-8 -top-8 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
            <h3 className="text-[10px] font-extrabold uppercase tracking-[0.25em] mb-8 text-primary-container">Estructura Crítica</h3>
            <div className="space-y-8 relative z-10">
              <div>
                <div className="flex justify-between items-center mb-3">
                  <span className="text-xs font-bold tracking-tight">Umbral Alertas Legislativas</span>
                  <span className="text-[10px] font-black bg-white/10 px-2 py-0.5 rounded">85%</span>
                </div>
                <div className="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                  <div className="bg-white h-full" style={{ width: '85%' }}></div>
                </div>
              </div>
              <div className="space-y-3">
                <div className="flex justify-between items-center p-4 bg-white/5 border border-white/10 rounded-lg group cursor-pointer hover:bg-white/10 transition-all">
                  <div className="flex items-center gap-3">
                    <span className="material-symbols-outlined text-white/70">folder_open</span>
                    <span className="text-xs font-bold">Catálogos Maestros</span>
                  </div>
                  <span className="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
                </div>
                <div className="flex justify-between items-center p-4 bg-white/5 border border-white/10 rounded-lg group cursor-pointer hover:bg-white/10 transition-all">
                  <div className="flex items-center gap-3">
                    <span className="material-symbols-outlined text-white/70">security</span>
                    <span className="text-xs font-bold">Seguridad y Cifrado</span>
                  </div>
                  <span className="material-symbols-outlined text-white/40 group-hover:translate-x-1 transition-transform">chevron_right</span>
                </div>
              </div>
            </div>
          </div>

          {/* Visual Analytics */}
          <div className="bg-surface-container-low rounded-xl p-8 border border-outline-variant/30 shadow-sm">
            <p className="text-[10px] font-extrabold text-outline uppercase tracking-[0.2em] mb-10 text-center">Distribución Institucional</p>
            <div className="flex items-center justify-center mb-10">
              <div className="relative w-36 h-36 flex items-center justify-center">
                <svg className="w-full h-full transform -rotate-90">
                  <circle className="text-surface-container-high" cx="72" cy="72" fill="transparent" r="64" stroke="currentColor" strokeWidth="10"></circle>
                  <circle className="text-primary" cx="72" cy="72" fill="transparent" r="64" stroke="currentColor" strokeDasharray="402" strokeDashoffset="120" strokeLinecap="round" strokeWidth="10"></circle>
                  <circle className="text-secondary" cx="72" cy="72" fill="transparent" r="64" stroke="currentColor" strokeDasharray="402" strokeDashoffset="310" strokeLinecap="round" strokeWidth="10"></circle>
                </svg>
                <div className="absolute flex flex-col items-center">
                  <span className="text-3xl font-extrabold text-on-surface tracking-tighter">142</span>
                  <span className="text-[9px] uppercase tracking-widest font-bold opacity-50">Total</span>
                </div>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="flex items-center gap-3 px-3 py-2 bg-surface rounded-lg border border-surface-container-high">
                <span className="w-2 h-2 rounded-full bg-primary"></span>
                <span className="text-[10px] font-bold text-on-surface">Admin (12%)</span>
              </div>
              <div className="flex items-center gap-3 px-3 py-2 bg-surface rounded-lg border border-surface-container-high">
                <span className="w-2 h-2 rounded-full bg-secondary"></span>
                <span className="text-[10px] font-bold text-on-surface">Diput. (48%)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
