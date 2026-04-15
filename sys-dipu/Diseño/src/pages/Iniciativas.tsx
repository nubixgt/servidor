import React from 'react';

export default function Iniciativas() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header & Action Row */}
      <header className="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div className="max-w-2xl">
          <h1 className="text-[2.75rem] leading-[1.2] font-extrabold text-on-surface tracking-tight mb-2 font-headline">Iniciativas de Ley</h1>
          <p className="text-on-surface-variant text-lg leading-relaxed">Gestión integral de propuestas legislativas y seguimiento de estados parlamentarios.</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="px-6 py-2.5 bg-surface-container-high text-on-surface font-semibold rounded-lg flex items-center gap-2 transition-all hover:bg-surface-container-highest active:scale-95">
            <span className="material-symbols-outlined text-xl">ios_share</span> Exportar
          </button>
          <button className="px-6 py-2.5 bg-gradient-to-br from-primary to-primary-dim text-on-primary font-semibold rounded-lg flex items-center gap-2 shadow-lg shadow-primary/10 transition-all hover:shadow-xl active:scale-95">
            <span className="material-symbols-outlined text-xl">add</span> Nueva Iniciativa
          </button>
        </div>
      </header>

      {/* Stats Bento Grid (Asymmetric) */}
      <div className="grid grid-cols-12 gap-6 mb-10">
        <div className="col-span-12 lg:col-span-4 bg-surface-container-low rounded-2xl p-6 flex flex-col justify-between min-h-[160px]">
          <span className="text-on-surface-variant font-medium text-sm uppercase tracking-wider">Total Iniciativas</span>
          <div className="flex items-baseline gap-2">
            <span className="text-5xl font-extrabold text-on-surface font-headline">142</span>
            <span className="text-primary font-bold text-sm">+12% este mes</span>
          </div>
        </div>
        <div className="col-span-12 lg:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">En Revisión</span>
              <span className="w-2 h-2 rounded-full bg-tertiary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">24</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Presentadas</span>
              <span className="w-2 h-2 rounded-full bg-primary"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">88</span>
          </div>
          <div className="bg-surface-container rounded-2xl p-6 flex flex-col justify-between">
            <div className="flex justify-between items-start">
              <span className="text-on-surface-variant font-medium text-sm">Borradores</span>
              <span className="w-2 h-2 rounded-full bg-outline-variant"></span>
            </div>
            <span className="text-3xl font-bold text-on-surface font-headline">30</span>
          </div>
        </div>
      </div>

      {/* Filter Bar */}
      <div className="flex flex-wrap items-center gap-4 mb-8">
        <div className="relative flex-1 min-w-[300px]">
          <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
          <input className="w-full pl-12 pr-4 py-3 bg-surface-container-low border-none rounded-xl text-on-surface placeholder:text-on-surface-variant focus:ring-2 focus:ring-primary/20 transition-all outline-none" placeholder="Buscar por título, folio o autor..." type="text"/>
        </div>
        <div className="flex items-center bg-surface-container-low p-1.5 rounded-xl gap-1">
          <button className="px-4 py-2 bg-surface-container-lowest text-on-surface text-sm font-semibold rounded-lg shadow-sm">Todas</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Borrador</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">En Trámite</button>
          <button className="px-4 py-2 text-on-surface-variant text-sm font-medium hover:text-on-surface transition-colors">Aprobadas</button>
        </div>
        <button className="p-3 bg-surface-container-low text-on-surface-variant rounded-xl hover:bg-surface-container-high transition-colors">
          <span className="material-symbols-outlined">filter_list</span>
        </button>
      </div>

      {/* Editorial List / Table */}
      <div className="bg-surface-container-lowest rounded-2xl overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-surface-container text-on-surface-variant text-xs uppercase tracking-widest font-bold">
                <th className="px-8 py-5">Referencia</th>
                <th className="px-8 py-5">Título de la Iniciativa</th>
                <th className="px-8 py-5">Estado</th>
                <th className="px-8 py-5">Fecha</th>
                <th className="px-8 py-5">Autor</th>
                <th className="px-8 py-5 text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y-0">
              {/* Row 1 */}
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <span className="font-mono text-xs text-on-surface-variant">L-2024-089</span>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-md">
                    <p className="font-bold text-on-surface mb-1">Ley de Transparencia Algorítmica</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Reforma al artículo 45 en materia de inteligencia artificial y ética pública.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-full">En Comisión</span>
                </td>
                <td className="px-8 py-6">
                  <p className="text-sm text-on-surface">12 Oct 2024</p>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <div className="w-6 h-6 rounded-full bg-primary-container flex items-center justify-center text-[10px] font-bold text-on-primary-container">MS</div>
                    <span className="text-sm">M. Sánchez</span>
                  </div>
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
              {/* Row 2 */}
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <span className="font-mono text-xs text-on-surface-variant">L-2024-092</span>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-md">
                    <p className="font-bold text-on-surface mb-1">Programa de Movilidad Sustentable 2030</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Incentivos fiscales para la transición a vehículos eléctricos en transporte público.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-xs font-bold rounded-full">Borrador</span>
                </td>
                <td className="px-8 py-6">
                  <p className="text-sm text-on-surface">15 Oct 2024</p>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <div className="w-6 h-6 rounded-full bg-secondary-container flex items-center justify-center text-[10px] font-bold text-on-secondary-container">LR</div>
                    <span className="text-sm">L. Rivera</span>
                  </div>
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
              {/* Row 3 */}
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <span className="font-mono text-xs text-on-surface-variant">L-2024-075</span>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-md">
                    <p className="font-bold text-on-surface mb-1">Protección de Datos en Entornos Virtuales</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Regulación de la privacidad en el metaverso y plataformas de realidad aumentada.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-primary-container text-on-primary-container text-xs font-bold rounded-full">Aprobada</span>
                </td>
                <td className="px-8 py-6">
                  <p className="text-sm text-on-surface">05 Oct 2024</p>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <div className="w-6 h-6 rounded-full bg-tertiary-container flex items-center justify-center text-[10px] font-bold text-on-tertiary-container">JC</div>
                    <span className="text-sm">J. Castillo</span>
                  </div>
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
              {/* Row 4 */}
              <tr className="group hover:bg-surface-container-low transition-colors">
                <td className="px-8 py-6">
                  <span className="font-mono text-xs text-on-surface-variant">L-2024-101</span>
                </td>
                <td className="px-8 py-6">
                  <div className="max-w-md">
                    <p className="font-bold text-on-surface mb-1">Ley de Fomento a la Ciberseguridad Nacional</p>
                    <p className="text-xs text-on-surface-variant line-clamp-1">Creación de la agencia nacional de respuesta ante incidentes críticos.</p>
                  </div>
                </td>
                <td className="px-8 py-6">
                  <span className="px-3 py-1 bg-error-container text-on-error-container text-xs font-bold rounded-full">Observada</span>
                </td>
                <td className="px-8 py-6">
                  <p className="text-sm text-on-surface">20 Oct 2024</p>
                </td>
                <td className="px-8 py-6">
                  <div className="flex items-center gap-2">
                    <div className="w-6 h-6 rounded-full bg-surface-container-highest flex items-center justify-center text-[10px] font-bold text-on-surface-variant">AM</div>
                    <span className="text-sm">A. Mendoza</span>
                  </div>
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
        {/* Pagination */}
        <div className="px-8 py-5 border-t border-surface-container flex items-center justify-between">
          <span className="text-xs text-on-surface-variant font-medium">Mostrando 1-4 de 142 iniciativas</span>
          <div className="flex items-center gap-2">
            <button className="w-8 h-8 flex items-center justify-center text-on-surface-variant hover:bg-surface-container rounded-lg transition-colors">
              <span className="material-symbols-outlined text-xl">chevron_left</span>
            </button>
            <button className="w-8 h-8 flex items-center justify-center bg-primary text-white text-xs font-bold rounded-lg shadow-sm">1</button>
            <button className="w-8 h-8 flex items-center justify-center text-on-surface-variant text-xs font-bold hover:bg-surface-container rounded-lg transition-colors">2</button>
            <button className="w-8 h-8 flex items-center justify-center text-on-surface-variant text-xs font-bold hover:bg-surface-container rounded-lg transition-colors">3</button>
            <button className="w-8 h-8 flex items-center justify-center text-on-surface-variant hover:bg-surface-container rounded-lg transition-colors">
              <span className="material-symbols-outlined text-xl">chevron_right</span>
            </button>
          </div>
        </div>
      </div>

      {/* Contextual FAB */}
      <button className="fixed bottom-10 right-10 w-16 h-16 bg-gradient-to-br from-primary to-primary-dim text-white rounded-full shadow-2xl flex items-center justify-center transition-transform hover:scale-110 active:scale-95 z-50">
        <span className="material-symbols-outlined text-3xl">add</span>
      </button>
    </div>
  );
}
