import React from 'react';

export default function Actividades() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header & Action Row */}
      <header className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div className="max-w-2xl">
          <h1 className="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Actividades y Eventos</h1>
          <p className="text-on-surface-variant text-lg leading-relaxed">Registro y planificación de eventos públicos, reuniones, foros y actividades de representación.</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
            <span className="material-symbols-outlined text-xl">ios_share</span> Exportar
          </button>
          <button className="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
            <span className="material-symbols-outlined text-xl">add</span> Nueva Actividad
          </button>
        </div>
      </header>

      {/* Stats Bento Grid */}
      <div className="grid grid-cols-12 gap-6 mb-10">
        <div className="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
          <span className="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Eventos este Mes</span>
          <div className="flex items-baseline gap-2">
            <span className="text-5xl font-extrabold text-on-surface font-headline">24</span>
            <span className="text-primary font-bold text-sm">8 Próximos</span>
          </div>
        </div>
        <div className="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Foros / Congresos</span>
              <span className="w-2 h-2 rounded-full bg-tertiary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">5</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Reuniones</span>
              <span className="w-2 h-2 rounded-full bg-primary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">14</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Giras de Trabajo</span>
              <span className="w-2 h-2 rounded-full bg-secondary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">5</span>
          </div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="flex flex-wrap items-center gap-4 mb-8">
        <div className="relative flex-1 min-w-[300px]">
          <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
          <input className="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por nombre, lugar o fecha..." type="text"/>
        </div>
        <div className="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
          <button className="px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm">Todas</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Próximas</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Pasadas</button>
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
                <th className="px-8 py-5">Fecha y Hora</th>
                <th className="px-8 py-5">Actividad</th>
                <th className="px-8 py-5">Lugar</th>
                <th className="px-8 py-5">Tipo</th>
                <th className="px-8 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y-0">
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <div className="flex flex-col">
                    <span className="font-bold text-on-surface">25 Oct 2024</span>
                    <span className="text-xs text-on-surface-variant">09:00 AM - 13:00 PM</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-xs">
                    <p className="font-bold text-on-surface">Foro de Desarrollo Económico Local</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Participación como ponente principal.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-sm text-on-surface-variant">location_on</span>
                    <span className="text-sm text-on-surface">Hotel Camino Real</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full">Foro</span>
                </td>
                <td className="px-8 py-6 text-right">
                  <button className="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                    <span className="material-symbols-outlined">visibility</span>
                  </button>
                  <button className="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                    <span className="material-symbols-outlined">edit</span>
                  </button>
                </td>
              </tr>
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <div className="flex flex-col">
                    <span className="font-bold text-on-surface">28 Oct 2024</span>
                    <span className="text-xs text-on-surface-variant">15:00 PM - 17:00 PM</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-xs">
                    <p className="font-bold text-on-surface">Reunión con Gremial de Exportadores</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Mesa de trabajo sobre facilitación de comercio.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-sm text-on-surface-variant">location_on</span>
                    <span className="text-sm text-on-surface">Sede Agexport</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">Reunión</span>
                </td>
                <td className="px-8 py-6 text-right">
                  <button className="p-2 text-on-surface-variant hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                    <span className="material-symbols-outlined">visibility</span>
                  </button>
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
