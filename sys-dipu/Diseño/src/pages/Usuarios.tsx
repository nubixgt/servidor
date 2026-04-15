import React from 'react';

export default function Usuarios() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header & Action Row */}
      <header className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div className="max-w-2xl">
          <h1 className="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Gestión de Usuarios</h1>
          <p className="text-on-surface-variant text-lg leading-relaxed">Administración de accesos, roles y permisos para el personal del despacho y equipo técnico.</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
            <span className="material-symbols-outlined text-xl">ios_share</span> Exportar
          </button>
          <button className="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
            <span className="material-symbols-outlined text-xl">person_add</span> Nuevo Usuario
          </button>
        </div>
      </header>

      {/* Stats Bento Grid */}
      <div className="grid grid-cols-12 gap-6 mb-10">
        <div className="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
          <span className="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Usuarios</span>
          <div className="flex items-baseline gap-2">
            <span className="text-5xl font-extrabold text-on-surface font-headline">24</span>
            <span className="text-primary font-bold text-sm">Activos</span>
          </div>
        </div>
        <div className="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Administradores</span>
              <span className="w-2 h-2 rounded-full bg-tertiary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">2</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Técnicos</span>
              <span className="w-2 h-2 rounded-full bg-primary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">18</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Inactivos</span>
              <span className="w-2 h-2 rounded-full bg-error"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">4</span>
          </div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="flex flex-wrap items-center gap-4 mb-8">
        <div className="relative flex-1 min-w-[300px]">
          <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
          <input className="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por nombre, correo o rol..." type="text"/>
        </div>
        <div className="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
          <button className="px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm">Todos</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Administradores</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Técnicos</button>
        </div>
        <button className="p-3 bg-surface-container-low text-on-surface-variant rounded-xl hover:bg-surface-container-high transition-colors">
          <span className="material-symbols-outlined">filter_list</span>
        </button>
      </div>

      {/* List / Table */}
      <div className="bg-surface-container-lowest rounded-2xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                <th className="px-8 py-5">Usuario</th>
                <th className="px-8 py-5">Rol</th>
                <th className="px-8 py-5">Categoría Asignada</th>
                <th className="px-8 py-5">Último Acceso</th>
                <th className="px-8 py-5">Estado</th>
                <th className="px-8 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y-0">
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <div className="flex items-center gap-3">
                    <img alt="Avatar" className="w-10 h-10 rounded-full object-cover" src="https://i.pravatar.cc/150?img=68" />
                    <div>
                      <p className="font-bold text-on-surface">Dip. Carlos Mendoza</p>
                      <p className="text-xs text-on-surface-variant">carlos.mendoza@congreso.gob</p>
                    </div>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">Administrador</span>
                </td>
                <td className="px-8 py-6">
                  <span className="text-sm text-on-surface-variant">Todas</span>
                </td>
                <td className="px-8 py-6">
                  <p className="text-sm text-on-surface">Hoy, 09:45 AM</p>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-primary"></span>
                    <span className="text-sm font-medium text-on-surface">Activo</span>
                  </div>
                </td>
                <td className="px-8 py-6 text-right">
                  <button className="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                    <span className="material-symbols-outlined">edit</span>
                  </button>
                </td>
              </tr>
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-sm">ER</div>
                    <div>
                      <p className="font-bold text-on-surface">Elena Rivas</p>
                      <p className="text-xs text-on-surface-variant">elena.rivas@equipo.gob</p>
                    </div>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-surface-container-highest text-on-surface-variant text-xs font-bold rounded-full">Técnico</span>
                </td>
                <td className="px-8 py-6">
                  <div className="flex flex-wrap gap-1">
                    <span className="px-2 py-0.5 bg-surface-container text-[10px] rounded">Iniciativas</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <p className="text-sm text-on-surface">Ayer, 16:30 PM</p>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-primary"></span>
                    <span className="text-sm font-medium text-on-surface">Activo</span>
                  </div>
                </td>
                <td className="px-8 py-6 text-right">
                  <button className="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                    <span className="material-symbols-outlined">edit</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
