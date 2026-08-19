import React, { useState, useMemo } from 'react';
import { 
  Plus, 
  Search, 
  Filter, 
  Sprout, 
  Droplet, 
  Activity, 
  CheckCircle2, 
  AlertTriangle, 
  Clock, 
  X, 
  Eye,
  Trash2
} from 'lucide-react';
import { BioindicatorSample, BioindicatorType } from '../types';

interface BioindicatorsViewProps {
  samples: BioindicatorSample[];
  onAddSample: (sample: BioindicatorSample) => void;
}

export const BioindicatorsView: React.FC<BioindicatorsViewProps> = ({
  samples,
  onAddSample
}) => {
  const [selectedBioFilters, setSelectedBioFilters] = useState<BioindicatorType[]>([]);
  const [searchTerm, setSearchTerm] = useState('');
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  const [inspectingSample, setInspectingSample] = useState<BioindicatorSample | null>(null);

  // New Sample Form State
  const [newSampleData, setNewSampleData] = useState({
    parcelName: 'Finca El Recuerdo',
    technicianName: 'Miguel Ramos',
    technicianInitials: 'MR',
    depthCm: 45,
    ph: 6.5,
    organicMatterPct: 5.0,
    status: 'Óptimo' as const,
    keyIndicators: ['Lombrices', 'Estructura granular'] as BioindicatorType[],
    notes: 'Presencia alta de lombriz de tierra en horizonte A.'
  });

  const availableBioindicators: BioindicatorType[] = [
    'Lombrices', 'Hongos', 'Hormigas', 'Hojarasca', 'Micelio', 'Estructura granular', 'Ciempiés', 'Escarabajos'
  ];

  const toggleFilter = (bio: BioindicatorType) => {
    setSelectedBioFilters(prev => 
      prev.includes(bio) ? prev.filter(b => b !== bio) : [...prev, bio]
    );
  };

  const filteredSamples = useMemo(() => {
    return samples.filter(s => {
      const matchesSearch = 
        s.parcelName.toLowerCase().includes(searchTerm.toLowerCase()) ||
        s.code.toLowerCase().includes(searchTerm.toLowerCase()) ||
        s.technicianName.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesBio = 
        selectedBioFilters.length === 0 ||
        selectedBioFilters.every(b => s.keyIndicators.includes(b));

      return matchesSearch && matchesBio;
    });
  }, [samples, searchTerm, selectedBioFilters]);

  const handleCreateSample = (e: React.FormEvent) => {
    e.preventDefault();
    const newSample: BioindicatorSample = {
      id: `BIO-${new Date().getFullYear()}-${String(samples.length + 1).padStart(3, '0')}`,
      parcelId: 'p-new',
      parcelName: newSampleData.parcelName,
      sampleDate: new Date().toISOString().split('T')[0],
      technicianName: newSampleData.technicianName,
      technicianInitials: newSampleData.technicianInitials,
      depthCm: Number(newSampleData.depthCm),
      ph: Number(newSampleData.ph),
      organicMatterPct: Number(newSampleData.organicMatterPct),
      status: newSampleData.status,
      keyIndicators: newSampleData.keyIndicators,
      notes: newSampleData.notes
    };

    onAddSample(newSample);
    setIsAddModalOpen(false);
  };

  return (
    <div className="space-y-6 max-w-[1600px] mx-auto animate-fadeIn pb-12">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">Bioindicadores y Salud del Suelo</h2>
          <p className="text-xs sm:text-sm text-[#cbd5e1] mt-0.5">
            Registro biológico de macroorganismos, pH, materia orgánica y microbiología edáfica.
          </p>
        </div>

        <button
          onClick={() => setIsAddModalOpen(true)}
          className="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-xl text-xs font-semibold tracking-wider uppercase transition-all shadow-[0_0_20px_rgba(34,197,94,0.4)]"
        >
          <Plus className="w-4 h-4" />
          <span>Nuevo Muestreo</span>
        </button>
      </div>

      {/* KPI Overview Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Muestras Totales</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#4ade80]">
              <Activity className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">{samples.length}</div>
            <p className="text-xs text-[#4ade80] font-semibold mt-1">100% georreferenciadas</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">pH Promedio</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#38bdf8]">
              <Droplet className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">6.4</div>
            <p className="text-xs text-[#cbd5e1] mt-1">Rango óptimo para café / agroforestería</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Materia Orgánica</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#facc15]">
              <Sprout className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">5.2%</div>
            <p className="text-xs text-[#4ade80] font-semibold mt-1">+1.4% tras zanjas de infiltración</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Suelos Óptimos</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#4ade80]">
              <CheckCircle2 className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">
              {samples.filter(s => s.status === 'Óptimo').length}
            </div>
            <p className="text-xs text-[#cbd5e1] mt-1">Alta actividad biológica observada</p>
          </div>
        </div>
      </div>

      {/* Filter and Search Bar */}
      <div className="glass-panel rounded-2xl p-4 space-y-3">
        <div className="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
          <div className="flex-1 relative group">
            <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#94a3b8] group-focus-within:text-white transition-colors" />
            <input
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Buscar muestra por código, parcela o técnico..."
              className="w-full bg-black/30 border border-white/15 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/40 transition-all placeholder:text-[#94a3b8]/60"
            />
          </div>
        </div>

        {/* Bioindicator Tags Quick Filter */}
        <div className="flex items-center gap-2 flex-wrap pt-1">
          <span className="text-[10px] text-[#94a3b8] font-bold uppercase tracking-wider flex items-center gap-1">
            <Filter className="w-3 h-3" />
            <span>Filtrar organismos:</span>
          </span>
          {availableBioindicators.map(bio => {
            const isSelected = selectedBioFilters.includes(bio);
            return (
              <button
                key={bio}
                onClick={() => toggleFilter(bio)}
                className={`px-3 py-1 rounded-full text-xs font-medium transition-all ${
                  isSelected 
                    ? 'bg-[#22c55e] text-white shadow-sm' 
                    : 'bg-black/30 text-[#cbd5e1] hover:bg-white/10 border border-white/10'
                }`}
              >
                {bio}
              </button>
            );
          })}
          {selectedBioFilters.length > 0 && (
            <button
              onClick={() => setSelectedBioFilters([])}
              className="text-[11px] text-[#f87171] hover:underline ml-2"
            >
              Limpiar filtros
            </button>
          )}
        </div>
      </div>

      {/* Samples Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        {filteredSamples.map((sample) => {
          const isOptimal = sample.status === 'Óptimo';
          const isModerate = sample.status === 'Moderado';

          return (
            <div
              key={sample.id}
              className="glass-panel glass-card-hover rounded-2xl p-5 flex flex-col justify-between relative group border border-white/15"
            >
              <div>
                <div className="flex justify-between items-start mb-2.5">
                  <span className="text-xs font-mono text-[#38bdf8] bg-[#06b6d4]/15 px-2.5 py-0.5 rounded-lg border border-[#38bdf8]/30">
                    {sample.code}
                  </span>
                  <span
                    className={`text-[10px] font-bold px-2.5 py-0.5 rounded-full border ${
                      isOptimal
                        ? 'bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30'
                        : isModerate
                        ? 'bg-[#f59e0b]/20 text-[#fbbf24] border-[#f59e0b]/30'
                        : 'bg-[#ef4444]/20 text-[#fca5a5] border-[#ef4444]/30'
                    }`}
                  >
                    {sample.status}
                  </span>
                </div>

                <h3 className="text-base font-bold text-white group-hover:text-[#4ade80] transition-colors">
                  {sample.parcelName}
                </h3>
                <p className="text-xs text-[#94a3b8] mt-0.5">Técnico: <strong className="text-[#cbd5e1]">{sample.technicianName}</strong></p>

                {/* Metrics */}
                <div className="grid grid-cols-3 gap-2 mt-4 py-2.5 px-3 bg-black/30 rounded-xl border border-white/5">
                  <div className="text-center">
                    <span className="text-[10px] text-[#94a3b8] uppercase font-bold block">Profundidad</span>
                    <span className="text-xs font-bold text-white font-mono">{sample.depthCm} cm</span>
                  </div>
                  <div className="text-center border-x border-white/10">
                    <span className="text-[10px] text-[#94a3b8] uppercase font-bold block">pH Suelo</span>
                    <span className="text-xs font-bold text-[#38bdf8] font-mono">{sample.ph}</span>
                  </div>
                  <div className="text-center">
                    <span className="text-[10px] text-[#94a3b8] uppercase font-bold block">Mat. Orgánica</span>
                    <span className="text-xs font-bold text-[#4ade80] font-mono">{sample.organicMatterPct}%</span>
                  </div>
                </div>

                {/* Organisms tags */}
                <div className="mt-3.5">
                  <span className="text-[10px] text-[#94a3b8] font-bold uppercase tracking-wider block mb-1.5">
                    Bioindicadores Observados:
                  </span>
                  <div className="flex flex-wrap gap-1.5">
                    {sample.keyIndicators.map((bio, i) => (
                      <span
                        key={i}
                        className="text-[10px] bg-white/10 border border-white/15 text-white px-2 py-0.5 rounded-md font-medium"
                      >
                        {bio}
                      </span>
                    ))}
                  </div>
                </div>

                {/* Notes preview */}
                {sample.notes && (
                  <p className="text-[11px] text-[#cbd5e1] mt-3 italic line-clamp-2 bg-black/20 p-2 rounded-lg border border-white/5">
                    "{sample.notes}"
                  </p>
                )}
              </div>

              {/* Footer */}
              <div className="mt-5 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                <span className="text-[11px] text-[#94a3b8] flex items-center gap-1 font-mono">
                  <Clock className="w-3 h-3" />
                  {sample.samplingDate}
                </span>

                <button
                  onClick={() => setInspectingSample(sample)}
                  className="flex items-center space-x-1 px-3 py-1.5 bg-white/10 hover:bg-[#22c55e] text-white rounded-lg font-semibold text-xs transition-all border border-white/15"
                >
                  <Eye className="w-3.5 h-3.5" />
                  <span>Ver Detalle</span>
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* Add Sample Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn">
          <div className="glass-panel max-w-lg w-full rounded-2xl p-6 border border-white/20 shadow-2xl space-y-4">
            <div className="flex justify-between items-center pb-2 border-b border-white/10">
              <h3 className="text-base font-bold text-white">Nuevo Muestreo Biológico de Suelo</h3>
              <button onClick={() => setIsAddModalOpen(false)} className="text-[#94a3b8] hover:text-white">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleCreateSample} className="space-y-4">
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">Nombre de la Parcela</label>
                  <input
                    type="text"
                    required
                    value={newSampleData.parcelName}
                    onChange={(e) => setNewSampleData({ ...newSampleData, parcelName: e.target.value })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white"
                  />
                </div>
                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">Técnico Evaluador</label>
                  <input
                    type="text"
                    required
                    value={newSampleData.technicianName}
                    onChange={(e) => setNewSampleData({ ...newSampleData, technicianName: e.target.value })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-3 gap-3">
                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">Profundidad (cm)</label>
                  <input
                    type="number"
                    required
                    value={newSampleData.depthCm}
                    onChange={(e) => setNewSampleData({ ...newSampleData, depthCm: Number(e.target.value) })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white"
                  />
                </div>
                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">pH Suelo</label>
                  <input
                    type="number"
                    step="0.1"
                    required
                    value={newSampleData.ph}
                    onChange={(e) => setNewSampleData({ ...newSampleData, ph: Number(e.target.value) })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white"
                  />
                </div>
                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">Mat. Orgánica (%)</label>
                  <input
                    type="number"
                    step="0.1"
                    required
                    value={newSampleData.organicMatterPct}
                    onChange={(e) => setNewSampleData({ ...newSampleData, organicMatterPct: Number(e.target.value) })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white"
                  />
                </div>
              </div>

              <div>
                <label className="text-xs text-[#cbd5e1] block mb-1.5">Organismos y Estructuras Detectadas</label>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  {availableBioindicators.map(bio => {
                    const isChecked = newSampleData.keyIndicators.includes(bio);
                    return (
                      <button
                        type="button"
                        key={bio}
                        onClick={() => {
                          const updated = isChecked
                            ? newSampleData.keyIndicators.filter(b => b !== bio)
                            : [...newSampleData.keyIndicators, bio];
                          setNewSampleData({ ...newSampleData, keyIndicators: updated });
                        }}
                        className={`p-2 rounded-xl text-xs font-medium text-left transition-all ${
                          isChecked 
                            ? 'bg-[#22c55e] text-white shadow-sm' 
                            : 'bg-black/30 text-[#cbd5e1] border border-white/10 hover:bg-white/5'
                        }`}
                      >
                        {bio}
                      </button>
                    );
                  })}
                </div>
              </div>

              <div>
                <label className="text-xs text-[#cbd5e1] block mb-1">Observaciones de Campo</label>
                <textarea
                  rows={2}
                  value={newSampleData.notes}
                  onChange={(e) => setNewSampleData({ ...newSampleData, notes: e.target.value })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white resize-none"
                  placeholder="Describa la humedad, textura y coloración del perfil..."
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-white/10">
                <button
                  type="button"
                  onClick={() => setIsAddModalOpen(false)}
                  className="px-4 py-2 bg-white/10 hover:bg-white/15 text-xs text-[#cbd5e1] rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-xs font-bold text-white rounded-xl shadow-lg"
                >
                  Guardar Muestra
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Inspect Sample Detail Modal */}
      {inspectingSample && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn">
          <div className="glass-panel max-w-md w-full rounded-2xl p-6 border border-white/20 shadow-2xl space-y-4">
            <div className="flex justify-between items-center pb-2 border-b border-white/10">
              <div>
                <span className="text-xs font-mono text-[#38bdf8]">{inspectingSample.code}</span>
                <h3 className="text-base font-bold text-white mt-0.5">{inspectingSample.parcelName}</h3>
              </div>
              <button onClick={() => setInspectingSample(null)} className="text-[#94a3b8] hover:text-white">
                <X className="w-5 h-5" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div className="grid grid-cols-2 gap-2 bg-black/30 p-3 rounded-xl border border-white/5">
                <div>
                  <span className="text-[#94a3b8] block text-[10px] uppercase font-bold">Evaluador:</span>
                  <span className="text-white font-medium">{inspectingSample.technicianName}</span>
                </div>
                <div>
                  <span className="text-[#94a3b8] block text-[10px] uppercase font-bold">Fecha:</span>
                  <span className="text-white font-mono">{inspectingSample.samplingDate}</span>
                </div>
                <div>
                  <span className="text-[#94a3b8] block text-[10px] uppercase font-bold">Coordenadas:</span>
                  <span className="text-[#4ade80] font-mono">{inspectingSample.locationCoords}</span>
                </div>
                <div>
                  <span className="text-[#94a3b8] block text-[10px] uppercase font-bold">Estado:</span>
                  <span className="text-white font-medium">{inspectingSample.status}</span>
                </div>
              </div>

              <div>
                <span className="text-[#94a3b8] block text-[10px] uppercase font-bold mb-1.5">Bioindicadores Identificados:</span>
                <div className="flex flex-wrap gap-1.5">
                  {inspectingSample.keyIndicators.map((bio, idx) => (
                    <span key={idx} className="bg-[#22c55e]/20 text-[#4ade80] border border-[#4ade80]/30 px-2.5 py-1 rounded-lg text-xs font-medium">
                      {bio}
                    </span>
                  ))}
                </div>
              </div>

              {inspectingSample.notes && (
                <div>
                  <span className="text-[#94a3b8] block text-[10px] uppercase font-bold mb-1">Notas Técnicas:</span>
                  <p className="bg-black/30 p-3 rounded-xl text-[#cbd5e1] border border-white/5 italic">
                    "{inspectingSample.notes}"
                  </p>
                </div>
              )}
            </div>

            <div className="flex justify-end pt-3 border-t border-white/10">
              <button
                onClick={() => setInspectingSample(null)}
                className="px-4 py-2 bg-white/10 hover:bg-white/20 text-xs text-white rounded-xl"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
