import React, { useState } from 'react';

export default function Archivo() {
  const [searchQuery, setSearchQuery] = useState('');

  const archiveData = [
    { id: 'EXP-2022-0891', title: 'Ley de Presupuesto General 2023', type: 'Ley', date: '15 Dic 2022', module: 'Finanzas', status: 'Aprobado' },
    { id: 'RES-2023-0142', title: 'Resolución de Nombramientos Comité B', type: 'Resolución', date: '04 Mar 2023', module: 'Administración', status: 'Histórico' },
    { id: 'DEC-2021-0055', title: 'Decreto de Emergencia Sanitaria (Cierre)', type: 'Decreto', date: '30 Nov 2021', module: 'Salud', status: 'Abrogado' },
    { id: 'ACT-2023-0992', title: 'Acta de Sesión Plenaria Ordinaria #45', type: 'Acta', date: '12 Oct 2023', module: 'Pleno', status: 'Histórico' },
    { id: 'EXP-2020-1102', title: 'Reforma al Código de Comercio', type: 'Ley', date: '22 Ene 2021', module: 'Economía', status: 'Aprobado' },
    { id: 'RES-2022-0881', title: 'Aprobación de Plan de Desarrollo Urbano', type: 'Resolución', date: '18 Ago 2022', module: 'Infraestructura', status: 'Histórico' },
    { id: 'EXP-2019-0334', title: 'Iniciativa de Ley de Protección Animal', type: 'Iniciativa', date: '05 Sep 2019', module: 'Medio Ambiente', status: 'Rechazado' },
  ];

  return (
    <div className="space-y-8 animate-in fade-in duration-500">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:justify-between md:items-end gap-4 mb-8">
        <div>
          <nav className="flex text-[10px] font-bold uppercase tracking-widest text-outline mb-2 gap-2 items-center">
            <span>Ecosistema</span>
            <span className="material-symbols-outlined text-[10px]">chevron_right</span>
            <span className="text-primary">Archivo Central</span>
          </nav>
          <h1 className="text-4xl font-extrabold text-on-surface tracking-tight font-headline">Repositorio Documental</h1>
          <p className="text-on-surface-variant mt-2 max-w-2xl text-sm">
            Acceso al acervo histórico, expedientes cerrados y resoluciones pasadas. 
            Todos los roles tienen acceso de lectura, pero las acciones avanzadas dependen de su nivel de autorización.
          </p>
        </div>
        <div className="flex gap-3">
          <button className="px-4 py-2 bg-surface-container-high text-on-surface rounded-lg font-bold text-xs flex items-center gap-2 hover:bg-surface-container-highest transition-colors">
            <span className="material-symbols-outlined text-sm">download</span> Exportar Índice
          </button>
        </div>
      </div>

      {/* Stats Bento */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
          <div className="flex justify-between items-start mb-4">
            <div className="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center">
              <span className="material-symbols-outlined">folder_open</span>
            </div>
            <span className="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Total</span>
          </div>
          <h3 className="text-3xl font-black text-on-surface font-headline">45,231</h3>
          <p className="text-xs text-outline mt-1">Expedientes digitalizados</p>
        </div>
        
        <div className="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
          <div className="flex justify-between items-start mb-4">
            <div className="w-10 h-10 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
              <span className="material-symbols-outlined">history</span>
            </div>
            <span className="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Este Mes</span>
          </div>
          <h3 className="text-3xl font-black text-on-surface font-headline">+124</h3>
          <p className="text-xs text-outline mt-1">Nuevos ingresos al archivo</p>
        </div>

        <div className="bg-surface-container-lowest p-6 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10">
          <div className="flex justify-between items-start mb-4">
            <div className="w-10 h-10 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center">
              <span className="material-symbols-outlined">search_insights</span>
            </div>
            <span className="text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Consultas</span>
          </div>
          <h3 className="text-3xl font-black text-on-surface font-headline">892</h3>
          <p className="text-xs text-outline mt-1">Búsquedas en los últimos 7 días</p>
        </div>
      </div>

      {/* Search and Filter Bar */}
      <div className="bg-surface-container-lowest p-4 rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10 flex flex-col md:flex-row gap-4 items-center">
        <div className="relative flex-1 w-full">
          <span className="absolute inset-y-0 left-0 pl-4 flex items-center text-outline">
            <span className="material-symbols-outlined">search</span>
          </span>
          <input 
            type="text" 
            placeholder="Buscar por ID de expediente, título o palabras clave..." 
            className="w-full bg-surface-container-low border-none rounded-xl py-3 pl-12 pr-4 text-sm focus:ring-2 focus:ring-primary/20 transition-all"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
        <div className="flex gap-3 w-full md:w-auto overflow-x-auto pb-2 md:pb-0">
          <select className="bg-surface-container-low border-none rounded-xl py-3 px-4 text-sm text-on-surface font-medium focus:ring-2 focus:ring-primary/20 cursor-pointer outline-none">
            <option>Todos los Tipos</option>
            <option>Leyes</option>
            <option>Decretos</option>
            <option>Resoluciones</option>
            <option>Actas</option>
          </select>
          <select className="bg-surface-container-low border-none rounded-xl py-3 px-4 text-sm text-on-surface font-medium focus:ring-2 focus:ring-primary/20 cursor-pointer outline-none">
            <option>Cualquier Año</option>
            <option>2023</option>
            <option>2022</option>
            <option>2021</option>
            <option>Anteriores</option>
          </select>
          <button className="px-4 py-3 bg-surface-container-high text-on-surface rounded-xl flex items-center justify-center hover:bg-surface-container-highest transition-colors">
            <span className="material-symbols-outlined text-lg">tune</span>
          </button>
        </div>
      </div>

      {/* Data Table */}
      <div className="bg-surface-container-lowest rounded-2xl shadow-[0_8px_30px_rgba(43,52,55,0.04)] border border-outline-variant/10 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-surface-container-low/50 border-b border-outline-variant/20">
                <th className="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Expediente</th>
                <th className="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Título / Asunto</th>
                <th className="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Tipo</th>
                <th className="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Fecha Archivo</th>
                <th className="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant">Estado</th>
                <th className="p-4 text-[10px] font-bold uppercase tracking-widest text-on-surface-variant text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-outline-variant/10">
              {archiveData.map((item, index) => (
                <tr key={index} className="hover:bg-surface-container-low/30 transition-colors group">
                  <td className="p-4">
                    <span className="text-xs font-bold font-mono text-primary bg-primary/5 px-2 py-1 rounded">{item.id}</span>
                  </td>
                  <td className="p-4">
                    <p className="text-sm font-bold text-on-surface">{item.title}</p>
                    <p className="text-[10px] text-outline mt-0.5">Módulo: {item.module}</p>
                  </td>
                  <td className="p-4">
                    <span className="text-xs font-medium text-on-surface-variant flex items-center gap-1">
                      <span className="material-symbols-outlined text-[14px]">
                        {item.type === 'Ley' ? 'gavel' : item.type === 'Resolución' ? 'contract' : item.type === 'Acta' ? 'history_edu' : 'description'}
                      </span>
                      {item.type}
                    </span>
                  </td>
                  <td className="p-4 text-xs text-on-surface-variant">{item.date}</td>
                  <td className="p-4">
                    <span className={`text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-full ${
                      item.status === 'Aprobado' ? 'bg-primary-container text-on-primary-container' :
                      item.status === 'Rechazado' ? 'bg-error-container text-on-error-container' :
                      item.status === 'Abrogado' ? 'bg-surface-container-highest text-on-surface-variant' :
                      'bg-secondary-container text-on-secondary-container'
                    }`}>
                      {item.status}
                    </span>
                  </td>
                  <td className="p-4 text-right">
                    <div className="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button className="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-white transition-colors" title="Ver Detalles">
                        <span className="material-symbols-outlined text-sm">visibility</span>
                      </button>
                      <button className="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-on-surface-variant hover:bg-primary hover:text-white transition-colors" title="Descargar PDF">
                        <span className="material-symbols-outlined text-sm">picture_as_pdf</span>
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        {/* Pagination */}
        <div className="p-4 border-t border-outline-variant/10 flex items-center justify-between bg-surface-container-lowest">
          <span className="text-xs text-outline font-medium">Mostrando 1 a 7 de 45,231 registros</span>
          <div className="flex gap-1">
            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-outline hover:bg-surface-container transition-colors disabled:opacity-50" disabled>
              <span className="material-symbols-outlined text-sm">chevron_left</span>
            </button>
            <button className="w-8 h-8 rounded-lg flex items-center justify-center bg-primary text-white font-bold text-xs">1</button>
            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors font-bold text-xs">2</button>
            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors font-bold text-xs">3</button>
            <span className="w-8 h-8 flex items-center justify-center text-outline text-xs">...</span>
            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors font-bold text-xs">89</button>
            <button className="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors">
              <span className="material-symbols-outlined text-sm">chevron_right</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
