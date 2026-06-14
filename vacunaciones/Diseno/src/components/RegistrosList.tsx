import React, { useState, useMemo } from 'react';
import { VaccineRecord } from '../types';
import { formatCurrency, formatNumber, formatCompactDate, SERVICIOS_PRESTADOS } from '../data';
import { Search, Filter, Download, ArrowUpDown, Trash2, CheckCircle, Clock, XCircle, Plus } from 'lucide-react';

interface RegistrosListProps {
  records: VaccineRecord[];
  onDeleteRecord: (id: string) => void;
  onUpdateStatus: (id: string, newStatus: 'Completado' | 'Pendiente' | 'Cancelado') => void;
  onNavigateToNuevo: () => void;
}

export default function RegistrosList({
  records,
  onDeleteRecord,
  onUpdateStatus,
  onNavigateToNuevo,
}: RegistrosListProps) {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedService, setSelectedService] = useState('Todos');
  const [selectedStatus, setSelectedStatus] = useState('Todos');
  const [sortBy, setSortBy] = useState<'fecha' | 'cantidad' | 'total'>('fecha');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');

  // Multi-criteria filtering logic
  const filteredRecords = useMemo(() => {
    return records
      .filter(rec => {
        const matchesSearch =
          rec.cliente.toLowerCase().includes(searchTerm.toLowerCase()) ||
          rec.vacunador.toLowerCase().includes(searchTerm.toLowerCase()) ||
          rec.direccion.toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesService = selectedService === 'Todos' || rec.servicio === selectedService;
        const matchesStatus = selectedStatus === 'Todos' || rec.estado === selectedStatus;

        return matchesSearch && matchesService && matchesStatus;
      })
      .sort((a, b) => {
        let valA: string | number = a.fecha;
        let valB: string | number = b.fecha;

        if (sortBy === 'cantidad') {
          valA = a.cantidad;
          valB = b.cantidad;
        } else if (sortBy === 'total') {
          valA = a.total;
          valB = b.total;
        }

        if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
        if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
        return 0;
      });
  }, [records, searchTerm, selectedService, selectedStatus, sortBy, sortOrder]);

  const toggleSort = (field: 'fecha' | 'cantidad' | 'total') => {
    if (sortBy === field) {
      setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setSortBy(field);
      setSortOrder('desc');
    }
  };

  // Mock excel csv export download
  const handleExportCSV = () => {
    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += 'ID,Fecha,Cliente,Servicio,Cantidad,Costo por Ave (Q),Total (Q),Estado,Vacunador,Direccion\n';

    filteredRecords.forEach(r => {
      csvContent += `"${r.id}","${r.fecha}","${r.cliente}","${r.servicio}",${r.cantidad},${r.costoPorAve},${r.total},"${r.estado}","${r.vacunador}","${r.direccion}"\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'VaxPoultry_Historial_Vacunacion.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="space-y-6 animate-fade-in relative pb-10">
      
      {/* Title block */}
      <section className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-3xl font-extrabold text-[#3455b9] mb-1">Historial Clínico de Servicios</h2>
          <p className="text-[#475569] text-sm font-bold">Consulte, filtre y modifique los registros guardados en campo.</p>
        </div>
        <div className="flex gap-2 w-full sm:w-auto shrink-0">
          <button
            onClick={handleExportCSV}
            className="flex-1 sm:flex-initial bg-white/60 hover:bg-white text-[#1e293b] font-bold px-4 py-2.5 rounded-xl border border-slate-200/60 shadow-3xs flex items-center justify-center gap-1.5 transition-all text-xs cursor-pointer"
          >
            <Download className="w-4 h-4 text-[#3455b9]" />
            <span>Descargar CSV</span>
          </button>
          <button
            onClick={onNavigateToNuevo}
            className="flex-1 sm:flex-initial bg-[#3455b9] hover:opacity-95 text-white font-bold px-4 py-2.5 rounded-xl shadow-xs flex items-center justify-center gap-1.5 transition-all text-xs cursor-pointer"
          >
            <Plus className="w-4 h-4" />
            <span>Nuevo Registro</span>
          </button>
        </div>
      </section>

      {/* Filter and search utilities bar */}
      <div className="glass-panel p-4 md:p-6 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          
          {/* Real-time search term bar */}
          <div className="relative md:col-span-2">
            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-[#3455b9]" />
            <input
              type="text"
              placeholder="Buscar por cliente, veterinario, dirección..."
              value={searchTerm}
              onChange={e => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm text-[#1e293b] placeholder-gray-500 font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
            />
          </div>

          {/* Service Dropdown category Filter */}
          <div>
            <select
              value={selectedService}
              onChange={e => setSelectedService(e.target.value)}
              className="w-full px-3 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all cursor-pointer font-bold text-[#1e293b]"
            >
              <option value="Todos" className="text-gray-900 font-semibold">Todos los Servicios</option>
              {SERVICIOS_PRESTADOS.map(srv => (
                <option key={srv} value={srv} className="text-gray-900 font-semibold">{srv}</option>
              ))}
            </select>
          </div>

          {/* Status Dropdown category filter */}
          <div>
            <select
              value={selectedStatus}
              onChange={e => setSelectedStatus(e.target.value)}
              className="w-full px-3 py-2.5 bg-white/40 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all cursor-pointer font-bold text-[#1e293b]"
            >
              <option value="Todos" className="text-gray-900 font-semibold">Todos los Estados</option>
              <option value="Completado" className="text-gray-900 font-semibold">Completado</option>
              <option value="Pendiente" className="text-gray-900 font-semibold">Pendiente</option>
              <option value="Cancelado" className="text-gray-900 font-semibold">Cancelado</option>
            </select>
          </div>

        </div>

        {/* Quick info summaries inside records list */}
        <div className="flex gap-6 text-xs text-[#475569] font-bold px-1 bg-white/20 p-2.5 rounded-lg border border-slate-200/50">
          <span>Registros Coincidentes: <strong className="text-[#3455b9] font-extrabold">{filteredRecords.length}</strong></span>
          <span>Aves Totales: <strong className="text-teal-700 font-extrabold">{formatNumber(filteredRecords.reduce((sum, r) => sum + r.cantidad, 0))}</strong></span>
          <span>Suma Facturación: <strong className="text-pink-700 font-extrabold">{formatCurrency(filteredRecords.reduce((sum, r) => sum + r.total, 0))}</strong></span>
        </div>
      </div>

      {/* Main glass spreadsheet list */}
      <div className="glass-panel overflow-hidden">
        <div className="overflow-x-auto">
          {filteredRecords.length === 0 ? (
            <div className="p-16 text-center text-gray-500 bg-white/10">
              <Search className="w-10 h-10 text-gray-300 mx-auto mb-2" />
              <p className="font-bold text-base">Sin resultados coincidentes</p>
              <p className="text-xs mt-1">Pruebe modificando los términos de búsqueda o filtros generales.</p>
            </div>
          ) : (
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-white/30 border-b border-gray-100/35">
                  <th
                    onClick={() => toggleSort('fecha')}
                    className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-white/40 transition-colors"
                  >
                    <div className="flex items-center gap-1">
                      <span>Fecha</span>
                      <ArrowUpDown className="w-3.5 h-3.5 text-gray-400" />
                    </div>
                  </th>
                  <th className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Cliente / Granja</th>
                  <th className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Servicio Prestado</th>
                  <th
                    onClick={() => toggleSort('cantidad')}
                    className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-white/40 transition-colors"
                  >
                    <div className="flex items-center gap-1">
                      <span>Cantidad</span>
                      <ArrowUpDown className="w-3.5 h-3.5 text-gray-400" />
                    </div>
                  </th>
                  <th className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Unitario (Q)</th>
                  <th
                    onClick={() => toggleSort('total')}
                    className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider cursor-pointer hover:bg-white/40 transition-colors"
                  >
                    <div className="flex items-center gap-1">
                      <span>Total</span>
                      <ArrowUpDown className="w-3.5 h-3.5 text-gray-400" />
                    </div>
                  </th>
                  <th className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Veterinario</th>
                  <th className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider">Estado</th>
                  <th className="px-6 py-4.5 text-xs font-bold text-gray-600 uppercase tracking-wider text-right">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100/30">
                {filteredRecords.map(rec => (
                  <tr key={rec.id} className="hover:bg-white/20 transition-all group">
                    
                    {/* Date cell */}
                    <td className="px-6 py-4">
                      <div className="flex flex-col">
                        <strong className="text-gray-800 text-xs">{formatCompactDate(rec.fecha)}</strong>
                        <span className="text-[10px] text-gray-500 font-semibold">{rec.hora}</span>
                      </div>
                    </td>

                    {/* Client cell with initials */}
                    <td className="px-6 py-4">
                      <div className="flex items-start gap-2 max-w-[200px]">
                        <span className="w-7 h-7 rounded-md bg-white/70 border border-gray-200/50 flex items-center justify-center font-black text-[#3455b9] text-[9px] shrink-0">
                          {rec.clienteIniciales}
                        </span>
                        <div>
                          <p className="text-xs font-bold text-gray-800 line-clamp-1">{rec.cliente}</p>
                          <p className="text-[9px] text-gray-400 font-semibold line-clamp-1" title={rec.direccion}>{rec.direccion}</p>
                        </div>
                      </div>
                    </td>

                    {/* Service Name */}
                    <td className="px-6 py-4 text-xs font-semibold text-gray-700">{rec.servicio}</td>

                    {/* Bird Quantity */}
                    <td className="px-6 py-4 text-xs font-bold text-gray-800">{formatNumber(rec.cantidad)} aves</td>

                    {/* Cost per unit */}
                    <td className="px-6 py-4 text-xs text-gray-500 font-mono">Q {rec.costoPorAve.toFixed(4)}</td>

                    {/* Estimated Total */}
                    <td className="px-6 py-4 text-xs font-black text-[#3455b9]">{formatCurrency(rec.total)}</td>

                    {/* Veterinarian */}
                    <td className="px-6 py-4 text-xs text-gray-500 font-medium truncate max-w-[110px]">{rec.vacunador}</td>

                    {/* Dynamic status selectors inline */}
                    <td className="px-6 py-4">
                      <div className="relative inline-block">
                        <select
                          value={rec.estado}
                          onChange={e => onUpdateStatus(rec.id, e.target.value as 'Completado' | 'Pendiente' | 'Cancelado')}
                          className={`text-[10px] font-bold px-2 py-1 rounded-full cursor-pointer border-0 ring-1 focus:ring-1 focus:outline-none ${
                            rec.estado === 'Completado'
                              ? 'bg-emerald-50 text-emerald-700 ring-emerald-500/20'
                              : rec.estado === 'Pendiente'
                                ? 'bg-amber-50 text-amber-700 ring-amber-500/20'
                                : 'bg-red-50 text-red-700 ring-red-500/20'
                          }`}
                        >
                          <option value="Completado">Completado</option>
                          <option value="Pendiente">Pendiente</option>
                          <option value="Cancelado">Cancelado</option>
                        </select>
                      </div>
                    </td>

                    {/* Delete actions */}
                    <td className="px-6 py-4 text-right">
                      <button
                        onClick={() => {
                          if (confirm(`¿Está seguro de eliminar el registro de ${rec.cliente}?`)) {
                            onDeleteRecord(rec.id);
                          }
                        }}
                        className="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg opacity-0 group-hover:opacity-100 transition-all cursor-pointer"
                        title="Eliminar este registro permanentemente"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </td>

                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}
