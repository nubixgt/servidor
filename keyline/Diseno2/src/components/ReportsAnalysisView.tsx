import React, { useState } from 'react';
import { 
  Download, 
  FileText, 
  CheckCircle2, 
  TrendingUp, 
  Layers, 
  ShieldCheck, 
  Printer
} from 'lucide-react';
import { Parcela } from '../types';

interface ReportsAnalysisViewProps {
  parcelas: Parcela[];
  onApproveParcel: (parcelId: string) => void;
  onOpenExportModal: () => void;
}

export const ReportsAnalysisView: React.FC<ReportsAnalysisViewProps> = ({
  parcelas,
  onApproveParcel,
  onOpenExportModal
}) => {
  const [selectedDeptFilter, setSelectedDeptFilter] = useState('Alta Verapaz');

  const pendingApprovals = parcelas.filter(p => p.status === 'En Revisión' || p.status === 'Pendiente');

  const deptData: { [key: string]: { imp: number; val: number } } = {
    'Alta Verapaz': { imp: 120, val: 95 },
    'Quiché': { imp: 85, val: 68 },
    'Huehuetenango': { imp: 65, val: 52 },
    'Chimaltenango': { imp: 45, val: 38 },
    'Petén': { imp: 90, val: 72 }
  };

  return (
    <div className="space-y-6 max-w-[1600px] mx-auto animate-fadeIn pb-12">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">
            Reportes y Análisis de Supervisión
          </h2>
          <p className="text-xs sm:text-sm text-[#cbd5e1] mt-0.5">
            Métricas consolidadas, validación técnica de polígonos y centro de exportación oficial.
          </p>
        </div>

        <button
          onClick={onOpenExportModal}
          className="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-xl text-xs font-semibold tracking-wider uppercase transition-all shadow-[0_0_20px_rgba(34,197,94,0.4)]"
        >
          <Download className="w-4 h-4" />
          <span>Generar Reporte PDF/CSV</span>
        </button>
      </div>

      {/* KPI Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Tasa de Aprobación</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#4ade80]">
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">88.4%</div>
            <p className="text-xs text-[#4ade80] font-semibold mt-1">Conforme a norma técnica Keyline</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Retención Hídrica Est.</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#38bdf8]">
              <TrendingUp className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">+38%</div>
            <p className="text-xs text-[#cbd5e1] mt-1">Estimación hídrica en ladera</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Erosión Evitada</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#facc15]">
              <Layers className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">14.2 <span className="text-sm font-normal">t/ha/año</span></div>
            <p className="text-xs text-[#4ade80] font-semibold mt-1">Cálculo RUSLE calibrado</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex justify-between items-start">
            <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Por Revisar</span>
            <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#f59e0b]">
              <FileText className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-3xl font-bold text-white font-mono">{pendingApprovals.length}</div>
            <p className="text-xs text-[#cbd5e1] mt-1">En cola de supervisión</p>
          </div>
        </div>
      </div>

      {/* Pending Validation Queue */}
      <div className="glass-panel rounded-2xl p-6">
        <div className="flex justify-between items-center pb-4 border-b border-white/10">
          <div>
            <h3 className="text-base font-bold text-white tracking-tight">
              Cola de Validación Técnica de Parcelas
            </h3>
            <p className="text-xs text-[#cbd5e1] mt-0.5">
              Polígonos cargados recientemente que requieren visto bueno de supervisor.
            </p>
          </div>
          <span className="text-xs px-3 py-1 bg-[#f59e0b]/20 text-[#fbbf24] border border-[#f59e0b]/30 rounded-full font-mono">
            {pendingApprovals.length} pendientes
          </span>
        </div>

        <div className="divide-y divide-white/10 mt-2">
          {pendingApprovals.map((p) => (
            <div key={p.id} className="py-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 hover:bg-white/5 px-2 rounded-xl transition-colors">
              <div className="space-y-1">
                <div className="flex items-center gap-2">
                  <span className="text-xs font-mono text-[#38bdf8] bg-black/40 px-2 py-0.5 rounded border border-white/10">
                    {p.code}
                  </span>
                  <h4 className="text-xs font-bold text-white">{p.name}</h4>
                </div>
                <p className="text-xs text-[#cbd5e1]">
                  Técnico: <strong className="text-white">{p.technicianName}</strong> · {p.municipality}, {p.department} ({p.areaHa} ha, {p.slopeDegrees}° pend.)
                </p>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => onApproveParcel(p.id)}
                  className="px-3.5 py-1.5 bg-[#22c55e] hover:bg-[#16a34a] text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all shadow-sm"
                >
                  <CheckCircle2 className="w-3.5 h-3.5" />
                  <span>Aprobar Parcela</span>
                </button>
              </div>
            </div>
          ))}

          {pendingApprovals.length === 0 && (
            <div className="py-8 text-center text-xs text-[#cbd5e1]">
              <CheckCircle2 className="w-8 h-8 text-[#4ade80] mx-auto mb-2 opacity-80" />
              <span>Todas las parcelas se encuentran validadas y al día.</span>
            </div>
          )}
        </div>
      </div>

      {/* Official Analysis Charts & Comparison */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div className="glass-panel rounded-2xl p-6 space-y-4">
          <div className="flex justify-between items-center">
            <h3 className="text-base font-bold text-white tracking-tight">Avance por Departamento</h3>
            <select
              value={selectedDeptFilter}
              onChange={(e) => setSelectedDeptFilter(e.target.value)}
              className="bg-black/40 border border-white/15 text-xs text-white rounded-xl px-3 py-1.5"
            >
              {Object.keys(deptData).map(d => (
                <option key={d} value={d} className="bg-black/90 text-white">{d}</option>
              ))}
            </select>
          </div>

          <div className="space-y-4 pt-2">
            <div>
              <div className="flex justify-between text-xs text-[#cbd5e1] mb-1">
                <span>Parcelas Levantadas en Campo</span>
                <span className="font-mono text-white font-bold">{deptData[selectedDeptFilter]?.imp || 100}</span>
              </div>
              <div className="w-full bg-black/40 h-2.5 rounded-full overflow-hidden border border-white/10">
                <div className="bg-[#38bdf8] h-full w-[80%]" />
              </div>
            </div>

            <div>
              <div className="flex justify-between text-xs text-[#cbd5e1] mb-1">
                <span>Validaciones Supervisor</span>
                <span className="font-mono text-[#4ade80] font-bold">{deptData[selectedDeptFilter]?.val || 80}</span>
              </div>
              <div className="w-full bg-black/40 h-2.5 rounded-full overflow-hidden border border-white/10">
                <div className="bg-[#22c55e] h-full w-[65%]" />
              </div>
            </div>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-6 flex flex-col justify-between">
          <div>
            <h3 className="text-base font-bold text-white tracking-tight">Exportación de Certificados</h3>
            <p className="text-xs text-[#cbd5e1] mt-0.5">
              Genere el dossier técnico con firma digital para auditorías del MAGA y cooperación internacional.
            </p>
          </div>

          <div className="p-4 bg-black/30 border border-white/10 rounded-xl my-4 text-xs space-y-1.5">
            <p className="text-white font-medium flex items-center gap-1.5">
              <Printer className="w-4 h-4 text-[#4ade80]" />
              <span>Plantilla oficial Keyline v2.4 (Guatemala)</span>
            </p>
            <p className="text-[#94a3b8]">Incluye: Planimetría, Curvas de Nivel, Cálculo de Volumen Hídrico y Ficha de Productor.</p>
          </div>

          <button
            onClick={onOpenExportModal}
            className="w-full py-2.5 bg-white/10 hover:bg-[#22c55e] text-white rounded-xl text-xs font-bold transition-all border border-white/15"
          >
            Configurar y Descargar Reporte
          </button>
        </div>
      </div>
    </div>
  );
};
