export default function Denuncias() {
  const techAreas = [
    { name: 'Área Legal', icon: 'gavel', count: 45, color: 'primary' },
    { name: 'Área Técnica', icon: 'biotech', count: 32, color: 'secondary' },
    { name: 'Emitir Dictamen', icon: 'assignment', count: 18, color: 'tertiary' },
    { name: 'Opinión Legal', icon: 'balance', count: 12, color: 'error' },
    { name: 'Resolución Final', icon: 'task_alt', count: 8, color: 'outline' },
  ];

  return (
    <div className="animate-in fade-in duration-500">
      <div className="flex justify-between items-end mb-8">
        <div>
          <h2 className="font-headline text-3xl font-extrabold text-on-surface tracking-tight">Gestión de Denuncias</h2>
          <p className="text-on-surface-variant mt-1">Administración centralizada y seguimiento por áreas técnicas.</p>
        </div>
        <button className="primary-gradient text-white px-4 py-2 rounded-xl font-bold text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2">
          <span className="material-symbols-outlined text-[18px]">add</span>
          Nueva Denuncia Manual
        </button>
      </div>

      {/* 5 Technician Areas */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        {techAreas.map((area, index) => (
          <div key={index} className="glass-card p-4 rounded-2xl shadow-sm hover:shadow-md transition-all cursor-pointer border border-outline-variant/20 hover:border-primary/30 group">
            <div className="flex justify-between items-start mb-3">
              <div className={`w-10 h-10 rounded-lg bg-${area.color}-container/30 flex items-center justify-center text-${area.color} group-hover:scale-110 transition-transform`}>
                <span className="material-symbols-outlined">{area.icon}</span>
              </div>
              <span className="text-xs font-bold text-on-surface-variant bg-surface-container-low px-2 py-1 rounded-full">{area.count} casos</span>
            </div>
            <h3 className="font-bold text-sm text-on-surface leading-tight">{area.name}</h3>
            <p className="text-[10px] text-on-surface-variant uppercase tracking-widest mt-1">Fase {index + 1}</p>
          </div>
        ))}
      </div>

      <div className="glass-card rounded-2xl shadow-ambient overflow-hidden">
        <div className="p-4 border-b border-outline-variant/20 flex gap-4 bg-surface-container-lowest/50">
          <div className="relative flex-1">
            <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">search</span>
            <input type="text" placeholder="Buscar por ID, denunciante o ubicación..." className="w-full bg-surface border border-outline-variant/30 rounded-lg py-2 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all" />
          </div>
          <select className="bg-surface border border-outline-variant/30 rounded-lg px-4 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
            <option>Todas las fases</option>
            <option>Fase 1: Legal</option>
            <option>Fase 2: Inspección</option>
          </select>
          <button className="px-4 py-2 bg-surface border border-outline-variant/30 rounded-lg text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">filter_list</span>
            Filtros
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-surface-container-lowest border-b border-outline-variant/20 text-xs uppercase tracking-widest text-outline">
                <th className="p-4 font-bold">ID Expediente</th>
                <th className="p-4 font-bold">Fecha</th>
                <th className="p-4 font-bold">Tipo</th>
                <th className="p-4 font-bold">Fase Actual</th>
                <th className="p-4 font-bold">Prioridad</th>
                <th className="p-4 font-bold text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="text-sm divide-y divide-outline-variant/10">
              {[
                { id: 'EXP-2024-089', date: '24 Mar 2024', type: 'Canino', phase: 'Fase 2: Inspección', priority: 'Alta', color: 'error' },
                { id: 'EXP-2024-090', date: '23 Mar 2024', type: 'Felino', phase: 'Fase 1: Legal', priority: 'Media', color: 'primary' },
                { id: 'EXP-2024-091', date: '22 Mar 2024', type: 'Equino', phase: 'Fase 3: Evaluación', priority: 'Baja', color: 'outline' },
                { id: 'EXP-2024-092', date: '21 Mar 2024', type: 'Exótico', phase: 'Fase 4: Resolución', priority: 'Alta', color: 'error' },
              ].map((row) => (
                <tr key={row.id} className="hover:bg-surface-container-lowest/50 transition-colors">
                  <td className="p-4 font-bold text-on-surface">{row.id}</td>
                  <td className="p-4 text-on-surface-variant">{row.date}</td>
                  <td className="p-4 text-on-surface-variant">{row.type}</td>
                  <td className="p-4">
                    <span className="px-2 py-1 bg-surface-container-low rounded text-xs font-semibold text-on-surface-variant">{row.phase}</span>
                  </td>
                  <td className="p-4">
                    <span className={`px-2 py-1 bg-${row.color}-container/20 text-${row.color} rounded text-xs font-bold uppercase`}>{row.priority}</span>
                  </td>
                  <td className="p-4 text-right">
                    <button className="text-primary hover:bg-primary-fixed/50 p-2 rounded-lg transition-colors">
                      <span className="material-symbols-outlined text-[20px]">visibility</span>
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="p-4 border-t border-outline-variant/20 flex justify-between items-center bg-surface-container-lowest/50 text-sm text-on-surface-variant">
          <span>Mostrando 1 a 4 de 1,284 registros</span>
          <div className="flex gap-2">
            <button className="px-3 py-1 border border-outline-variant/30 rounded hover:bg-surface-container-low">Anterior</button>
            <button className="px-3 py-1 border border-outline-variant/30 rounded hover:bg-surface-container-low">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  );
}
