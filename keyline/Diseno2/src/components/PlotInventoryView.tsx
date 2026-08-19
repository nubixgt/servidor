import React, { useState, useMemo } from 'react';
import { 
  Plus, 
  Search, 
  Download, 
  Eye, 
  MapPin, 
  Calendar, 
  Trash2,
  Sprout
} from 'lucide-react';
import { Parcela } from '../types';

interface PlotInventoryViewProps {
  parcelas: Parcela[];
  onSelectParcel: (parcel: Parcela) => void;
  onOpenNewRegistration: () => void;
  onDeleteParcel?: (id: string) => void;
}

export const PlotInventoryView: React.FC<PlotInventoryViewProps> = ({
  parcelas,
  onSelectParcel,
  onOpenNewRegistration,
  onDeleteParcel
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedDept, setSelectedDept] = useState('Todos');
  const [selectedStatus, setSelectedStatus] = useState('Cualquiera');

  const departments = ['Todos', 'Alta Verapaz', 'Huehuetenango', 'Quiché', 'Chimaltenango', 'Petén', 'San Marcos'];
  const statusOptions = ['Cualquiera', 'Aprobado', 'En Revisión', 'Levantamiento', 'Pendiente'];

  const filteredParcelas = useMemo(() => {
    return parcelas.filter((p) => {
      const matchesSearch = 
        p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.producer.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.municipality.toLowerCase().includes(searchTerm.toLowerCase()) ||
        p.technicianName.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesDept = selectedDept === 'Todos' || p.department === selectedDept;
      const matchesStatus = selectedStatus === 'Cualquiera' || p.status === selectedStatus;

      return matchesSearch && matchesDept && matchesStatus;
    });
  }, [parcelas, searchTerm, selectedDept, selectedStatus]);

  const handleExportCSV = () => {
    const headers = ["ID", "Nombre", "Productor", "Departamento", "Municipio", "Comunidad", "Area_ha", "Familias", "Estado", "Tecnico", "Keyline", "Pendiente_deg"];
    const rows = filteredParcelas.map(p => [
      p.code,
      `"${p.name}"`,
      `"${p.producer}"`,
      `"${p.department}"`,
      `"${p.municipality}"`,
      `"${p.community}"`,
      p.areaHa,
      p.benefitedFamilies,
      p.status,
      `"${p.technicianName}"`,
      `"${p.keylinePractice}"`,
      p.slopeDegrees
    ]);

    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `keylinegt_parcelas_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="space-y-5 max-w-[1600px] mx-auto animate-fadeIn pb-12 font-[Arial,Helvetica,sans-serif]">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">Todas las Parcelas</h2>
          <p className="text-xs sm:text-sm text-[#94a3b8] mt-0.5">
            Gestión centralizada de polígonos, curvas a nivel y beneficiarios.
          </p>
        </div>

        <button
          onClick={onOpenNewRegistration}
          className="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-black rounded-xl text-xs font-bold transition-all shadow-md"
        >
          <Plus className="w-4 h-4" />
          <span>Registrar Parcela</span>
        </button>
      </div>

      {/* Filter and Search Bar */}
      <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div className="flex-1 relative group">
          <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#64748b] group-focus-within:text-white transition-colors" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Buscar por código, nombre, productor, municipio..."
            className="w-full bg-[#081611] border border-[#17382b] rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-[#22c55e]/60 transition-all placeholder:text-[#64748b]"
          />
        </div>

        <div className="flex flex-wrap gap-2.5 items-center">
          {/* Dept Dropdown */}
          <div className="flex items-center gap-2 bg-[#081611] border border-[#17382b] rounded-xl px-3 py-1.5">
            <span className="text-[10px] text-[#94a3b8] font-bold uppercase tracking-wider">Depto:</span>
            <select
              value={selectedDept}
              onChange={(e) => setSelectedDept(e.target.value)}
              className="bg-transparent text-xs text-white focus:outline-none cursor-pointer"
            >
              {departments.map((d) => (
                <option key={d} value={d} className="bg-[#0c1e17] text-white">
                  {d}
                </option>
              ))}
            </select>
          </div>

          {/* Status Dropdown */}
          <div className="flex items-center gap-2 bg-[#081611] border border-[#17382b] rounded-xl px-3 py-1.5">
            <span className="text-[10px] text-[#94a3b8] font-bold uppercase tracking-wider">Estado:</span>
            <select
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value)}
              className="bg-transparent text-xs text-white focus:outline-none cursor-pointer"
            >
              {statusOptions.map((s) => (
                <option key={s} value={s} className="bg-[#0c1e17] text-white">
                  {s}
                </option>
              ))}
            </select>
          </div>

          {/* Export Button */}
          <button
            onClick={handleExportCSV}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-[#081611] hover:bg-[#133225] border border-[#17382b] hover:border-[#22c55e]/50 text-white rounded-xl text-xs font-medium transition-all"
            title="Exportar archivo CSV"
          >
            <Download className="w-3.5 h-3.5 text-[#22c55e]" />
            <span className="hidden sm:inline">CSV</span>
          </button>
        </div>
      </div>

      {/* Grid of Plot Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredParcelas.map((p) => {
          const statusBadge = 
            p.status === 'Aprobado' ? 'bg-[#22c55e]/15 border-[#22c55e]/30 text-[#22c55e]' :
            p.status === 'En Revisión' ? 'bg-[#eab308]/15 border-[#eab308]/30 text-[#eab308]' :
            p.status === 'Levantamiento' ? 'bg-[#38bdf8]/15 border-[#38bdf8]/30 text-[#38bdf8]' :
            'bg-[#ef4444]/15 border-[#ef4444]/30 text-[#ef4444]';

          return (
            <div
              key={p.id}
              className="bg-[#0c1e17] border border-[#17382b] hover:border-[#22c55e]/40 rounded-2xl overflow-hidden transition-all duration-200 flex flex-col justify-between group"
            >
              <div>
                {/* Image Header */}
                <div className="relative h-40 w-full overflow-hidden bg-[#081611]">
                  <img
                    src={p.photos[0] || 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=500&auto=format&fit=crop'}
                    alt={p.name}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-[#0c1e17] via-transparent to-transparent" />
                  
                  {/* Status & Code Badges */}
                  <div className="absolute top-3 left-3 right-3 flex justify-between items-center">
                    <span className="text-[10px] font-mono px-2 py-0.5 rounded-full bg-black/70 border border-white/20 text-white backdrop-blur-md">
                      {p.code}
                    </span>
                    <span className={`text-[10px] px-2 py-0.5 rounded-full border font-semibold ${statusBadge}`}>
                      {p.status}
                    </span>
                  </div>

                  {/* Area Badge */}
                  <div className="absolute bottom-2 right-3 bg-black/70 border border-white/15 px-2.5 py-1 rounded-lg text-xs font-bold text-white backdrop-blur-md">
                    {p.areaHa} ha
                  </div>
                </div>

                {/* Body Content */}
                <div className="p-4 space-y-3">
                  <div>
                    <h3 className="text-base font-bold text-white group-hover:text-[#22c55e] transition-colors truncate">
                      {p.name}
                    </h3>
                    <p className="text-xs text-[#94a3b8] truncate mt-0.5">
                      Productor: <span className="text-[#cbd5e1]">{p.producer}</span>
                    </p>
                  </div>

                  {/* Location and Info */}
                  <div className="grid grid-cols-2 gap-2 text-[11px] text-[#cbd5e1]">
                    <div className="flex items-center gap-1.5 truncate">
                      <MapPin className="w-3.5 h-3.5 text-[#22c55e] flex-shrink-0" />
                      <span className="truncate">{p.municipality}, {p.department}</span>
                    </div>
                    <div className="flex items-center gap-1.5 truncate">
                      <Calendar className="w-3.5 h-3.5 text-[#64748b] flex-shrink-0" />
                      <span>{p.registrationDate}</span>
                    </div>
                  </div>

                  {/* Keyline specs */}
                  <div className="bg-[#081611] rounded-xl p-2.5 border border-[#17382b] text-[11px] text-[#cbd5e1] space-y-1">
                    <div className="flex justify-between">
                      <span className="text-[#94a3b8]">Práctica:</span>
                      <span className="font-semibold text-white truncate max-w-[170px]">{p.keylinePractice}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-[#94a3b8]">Pendiente / Suelo:</span>
                      <span className="text-white">{p.slopeDegrees}° · {p.soilTexture}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Card Footer Actions */}
              <div className="p-4 pt-0 border-t border-[#17382b]/60 flex items-center justify-between mt-2">
                <span className="text-[11px] text-[#94a3b8] truncate">
                  Técnico: <span className="text-[#cbd5e1]">{p.technicianName.split(' ')[0]}</span>
                </span>

                <div className="flex items-center gap-1.5">
                  {onDeleteParcel && (
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        if (confirm(`¿Eliminar parcela ${p.code}?`)) {
                          onDeleteParcel(p.id);
                        }
                      }}
                      className="p-1.5 rounded-lg bg-[#081611] hover:bg-[#ef4444]/20 border border-[#17382b] text-[#94a3b8] hover:text-[#ef4444] transition-colors"
                      title="Eliminar"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  )}
                  <button
                    onClick={() => onSelectParcel(p)}
                    className="flex items-center gap-1.5 px-3 py-1.5 bg-[#153e2d] hover:bg-[#1a4f3a] text-[#22c55e] border border-[#22c55e]/30 rounded-xl text-xs font-semibold transition-colors"
                  >
                    <Eye className="w-3.5 h-3.5" />
                    <span>Ver detalle</span>
                  </button>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
