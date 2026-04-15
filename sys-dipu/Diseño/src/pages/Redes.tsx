import React from 'react';

export default function Redes() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header & Action Row */}
      <header className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div className="max-w-2xl">
          <h1 className="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Redes Sociales y Prensa</h1>
          <p className="text-on-surface-variant text-lg leading-relaxed">Gestión de comunicación digital, comunicados de prensa, métricas de impacto y monitoreo de medios.</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
            <span className="material-symbols-outlined text-xl">insights</span> Ver Métricas
          </button>
          <button className="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
            <span className="material-symbols-outlined text-xl">add</span> Nueva Publicación
          </button>
        </div>
      </header>

      {/* Stats Bento Grid */}
      <div className="grid grid-cols-12 gap-6 mb-10">
        <div className="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
          <span className="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Alcance Total (Mes)</span>
          <div className="flex items-baseline gap-2">
            <span className="text-5xl font-extrabold text-on-surface font-headline">1.2M</span>
            <span className="text-primary font-bold text-sm">+15% vs mes anterior</span>
          </div>
        </div>
        <div className="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Publicaciones (X/Twitter)</span>
              <span className="w-2 h-2 rounded-full bg-tertiary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">45</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Comunicados de Prensa</span>
              <span className="w-2 h-2 rounded-full bg-primary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">8</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Menciones Positivas</span>
              <span className="w-2 h-2 rounded-full bg-secondary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">68%</span>
          </div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="flex flex-wrap items-center gap-4 mb-8">
        <div className="relative flex-1 min-w-[300px]">
          <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
          <input className="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por título, plataforma o fecha..." type="text"/>
        </div>
        <div className="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
          <button className="px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm">Todas</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Redes</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Prensa</button>
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
                <th className="px-8 py-5">Fecha</th>
                <th className="px-8 py-5">Contenido</th>
                <th className="px-8 py-5">Plataforma/Medio</th>
                <th className="px-8 py-5">Estado</th>
                <th className="px-8 py-5">Interacciones</th>
                <th className="px-8 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y-0">
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <p className="text-sm font-bold text-on-surface">24 Oct 2024</p>
                  <p className="text-xs text-on-surface-variant">10:30 AM</p>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-xs">
                    <p className="font-bold text-on-surface">Post: Aprobación Ley de Movilidad</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">"Hoy dimos un paso importante para el futuro del transporte..."</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-sm text-on-surface-variant">tag</span>
                    <span className="text-sm text-on-surface">X / Twitter</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">Publicado</span>
                </td>
                <td className="px-8 py-6">
                  <span className="text-sm font-bold text-on-surface">12.5K</span>
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
                  <p className="text-sm font-bold text-on-surface">26 Oct 2024</p>
                  <p className="text-xs text-on-surface-variant">08:00 AM</p>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-xs">
                    <p className="font-bold text-on-surface">Comunicado: Postura Presupuesto 2025</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Boletín de prensa sobre la asignación a salud y educación.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <span className="material-symbols-outlined text-sm text-on-surface-variant">newspaper</span>
                    <span className="text-sm text-on-surface">Medios Nacionales</span>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full">Programado</span>
                </td>
                <td className="px-8 py-6">
                  <span className="text-sm font-bold text-on-surface-variant">-</span>
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
