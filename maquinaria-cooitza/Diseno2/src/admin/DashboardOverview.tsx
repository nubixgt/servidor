import React, { useState } from "react";
import { 
  Activity, 
  Users, 
  Truck, 
  TrendingUp, 
  History, 
  Search, 
  MapPin, 
  Eye, 
  Trash2,
  Tractor,
  Construction,
  Wrench,
  CircleSlash,
  Droplets
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";
import { OperationalLog } from "../types";

interface DashboardOverviewProps {
  logs: OperationalLog[];
  onDeleteLog: (id: string) => void;
  onAddMockLog: () => void;
  pilotsCount: number;
  pilotsActiveCount: number;
  pilotsRestingCount: number;
  vehiclesCount: number;
  vehiclesActiveCount: number;
  vehiclesMaintenanceCount: number;
  onOpenPhotoModal: (url: string) => void;
  onTriggerExport: (format: "Excel" | "PDF", filteredCount: number) => void;
}

export default function DashboardOverview({
  logs,
  onDeleteLog,
  onAddMockLog,
  pilotsCount,
  pilotsActiveCount,
  pilotsRestingCount,
  vehiclesCount,
  vehiclesActiveCount,
  vehiclesMaintenanceCount,
  onOpenPhotoModal,
  onTriggerExport
}: DashboardOverviewProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedMachineFilter, setSelectedMachineFilter] = useState("all");
  const [selectedRegTypeFilter, setSelectedRegTypeFilter] = useState("all");

  const machineIconsMap: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
    tractor: Tractor,
    excavadora: Construction,
    retro: Wrench,
    rodo: CircleSlash,
    pipa: Droplets,
    volteo: Truck,
  };

  const getMachineLabel = (type: string) => {
    switch (type) {
      case "tractor": return "Tractor";
      case "excavadora": return "Excavadora";
      case "retro": return "Retro Excavadora";
      case "rodo": return "Rodo de Presión";
      case "pipa": return "Pipa Cisterna";
      case "volteo": return "Camión Volteo";
      default: return type;
    }
  };

  // Filter logs
  const filteredLogs = logs.filter(log => {
    const operatorMatches = log.operatorName.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            log.location.formattedAddress.toLowerCase().includes(searchTerm.toLowerCase());
    const machineMatches = selectedMachineFilter === "all" || log.machineType === selectedMachineFilter;
    const regMatches = selectedRegTypeFilter === "all" || log.regType === selectedRegTypeFilter;
    return operatorMatches && machineMatches && regMatches;
  });

  // KPI calculations
  const totalHoursLogged = logs.reduce((acc, log) => acc + log.horometroValue, 0);
  const avgHours = logs.length > 0 ? (totalHoursLogged / logs.length).toFixed(1) : "0.0";
  const activeLogsCounter = logs.length;

  return (
    <motion.div 
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex flex-col gap-6"
    >
      {/* Command Center Title */}
      <div className="mb-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <span className="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">RESUMEN GENERAL</span>
          <h2 className="font-display text-3xl font-black text-on-surface tracking-tight mt-0.5">Control de Telemetría</h2>
        </div>
        <button 
          type="button"
          onClick={onAddMockLog}
          className="font-display text-[11px] font-black bg-[#FFD200] text-[#0054A3] hover:brightness-95 transition-all px-4 py-2 self-start sm:self-center uppercase tracking-wide border-2 border-transparent hover:border-[#0054A3]/20"
        >
          + SIMULAR TELEMETRÍA DE OBRA
        </button>
      </div>

      {/* Bento Quick statistics Row */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        {/* Pilots Count */}
        <div className="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div className="flex justify-between items-start mb-4">
            <Users className="text-[#0054A3] w-6 h-6" />
            <span className="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">PILOTOS</span>
          </div>
          <div className="font-display text-4xl font-extrabold text-[#0054A3]">{pilotsCount}</div>
          <p className="font-sans text-xs text-on-surface-variant font-medium mt-1">
            {pilotsActiveCount} En Turno Activo • {pilotsRestingCount} En Descanso
          </p>
        </div>

        {/* Vehicles Flota Count */}
        <div className="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div className="flex justify-between items-start mb-4">
            <Truck className="text-[#0054A3] w-6 h-6" />
            <span className="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">VEHÍCULOS</span>
          </div>
          <div className="font-display text-4xl font-extrabold text-[#0054A3]">{vehiclesCount}</div>
          <p className="font-sans text-xs text-on-surface-variant font-medium mt-1">
            {vehiclesActiveCount} Rutas Asignadas / {vehiclesMaintenanceCount} En Taller
          </p>
        </div>

        {/* Logged hours accumulators */}
        <div className="bg-white border border-[#cbd5e1] p-6 shadow-sm hover:border-[#0054A3] transition-all">
          <div className="flex justify-between items-start mb-4">
            <TrendingUp className="text-[#0054A3] w-6 h-6" />
            <span className="font-display text-[10px] font-black text-[#0054A3] bg-[#0054A3]/5 px-2 py-0.5 rounded">HORÓMETRO TOTAL</span>
          </div>
          <div className="font-display text-4xl font-extrabold text-[#0054A3]">{totalHoursLogged.toFixed(1)}</div>
          <p className="font-sans text-xs text-on-surface-variant font-medium mt-1">
            Promedio: {avgHours} HRS por bitácora ({activeLogsCounter} registros guardados)
          </p>
        </div>

      </div>

      {/* Graphic charts & core interactive telemetry lists */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {/* Horizontal CSS Chart */}
        <div className="lg:col-span-8 bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between">
          <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-6">
            <div>
              <h3 className="font-display text-base font-bold text-on-surface">Uso Operativo por Equipo</h3>
              <p className="text-xs text-on-surface-variant font-medium">Baches promedio acumulados en el último mes</p>
            </div>
            <div className="flex gap-4">
              <div className="flex items-center gap-1.5 text-xs font-semibold text-on-surface">
                <span className="w-3 h-3 bg-[#0054A3]" />
                <span>Flota Cooitzá</span>
              </div>
              <div className="flex items-center gap-1.5 text-xs font-semibold text-on-surface">
                <span className="w-3 h-3 bg-[#FFD200]" />
                <span>Sello Operador</span>
              </div>
            </div>
          </div>

          {/* Top-Tier Custom Dynamic Graph Grid */}
          <div className="flex-1 flex items-end gap-3 h-48 pb-2 border-b border-[#cbd5e1] relative">
            {/* Gridlines */}
            <div className="absolute inset-x-0 top-1/4 border-t border-slate-100" />
            <div className="absolute inset-x-0 top-2/4 border-t border-slate-100" />
            <div className="absolute inset-x-0 top-3/4 border-t border-slate-100" />

            {/* Bar 1: Tractor */}
            <div className="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div className="w-full bg-[#cbd5e1] h-1/2 relative">
                <div className="absolute bottom-0 w-full bg-[#0054A3] h-[75%] group-hover:bg-[#004586] transition-all" />
              </div>
              <span className="font-sans text-[10px] font-bold text-on-surface-variant mt-2 text-center truncate w-full">Tractores</span>
            </div>

            {/* Bar 2: Excavadoras */}
            <div className="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div className="w-full bg-[#cbd5e1] h-1/2 relative">
                <div className="absolute bottom-0 w-full bg-[#FFD200] h-[95%] group-hover:brightness-95 transition-all" />
              </div>
              <span className="font-sans text-[10px] font-bold text-on-surface-variant mt-2 text-center truncate w-full">Excavadoras</span>
            </div>

            {/* Bar 3: Retro */}
            <div className="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div className="w-full bg-[#cbd5e1] h-1/2 relative">
                <div className="absolute bottom-0 w-full bg-[#0054A3] h-[60%] group-hover:bg-[#004586] transition-all" />
              </div>
              <span className="font-sans text-[10px] font-bold text-on-surface-variant mt-2 text-center truncate w-full">Retros</span>
            </div>

            {/* Bar 4: Volquetes */}
            <div className="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div className="w-full bg-[#cbd5e1] h-1/2 relative">
                <div className="absolute bottom-0 w-full bg-[#FFD200] h-[85%] group-hover:brightness-95 transition-all" />
              </div>
              <span className="font-sans text-[10px] font-bold text-on-surface-variant mt-2 text-center truncate w-full">Volteos</span>
            </div>

            {/* Bar 5: Pipa */}
            <div className="flex-1 flex flex-col items-center justify-end h-full group cursor-pointer z-10">
              <div className="w-full bg-[#cbd5e1] h-1/2 relative">
                <div className="absolute bottom-0 w-full bg-[#0054A3] h-[40%] group-hover:bg-[#004586] transition-all" />
              </div>
              <span className="font-sans text-[10px] font-bold text-on-surface-variant mt-2 text-center truncate w-full">Pipas</span>
            </div>
          </div>
          
          <div className="mt-4 text-[10px] font-sans font-medium text-on-surface-variant text-center uppercase tracking-wide">
            Las unidades se autoajustan de acuerdo a las últimas lecturas enviadas por el personal de obra.
          </div>
        </div>

        {/* Health diagnostics overview inside Dashboard */}
        <div className="lg:col-span-4 flex flex-col gap-6">
          
          {/* System Health checklist */}
          <div className="bg-white border border-[#cbd5e1] p-5 shadow-sm">
            <h3 className="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-4">ESTADO DE ENLACES</h3>
            <div className="space-y-3 font-sans text-xs font-medium">
              
              <div className="flex justify-between items-center text-on-surface-variant">
                <span>Servidor Principal Cooitzá</span>
                <span className="text-emerald-600 bg-emerald-50 border border-emerald-200 px-1.5 font-bold uppercase text-[9px]">ONLINE</span>
              </div>
              <div className="w-full bg-slate-100 h-1.5">
                <div className="bg-[#4CAF50] h-full w-[100%]" />
              </div>

              <div className="flex justify-between items-center text-on-surface-variant">
                <span>Criptografía de Sesión SSL</span>
                <span className="text-[#0054A3] font-bold font-mono">256-BIT</span>
              </div>
              <div className="w-full bg-slate-100 h-1.5">
                <div className="bg-[#0054A3] h-full w-[80%]" />
              </div>

              <div className="flex justify-between items-center text-on-surface-variant">
                <span>Servicio de Geolocalización</span>
                <span className="text-emerald-600 font-bold font-mono text-[10px]">ACTIVO (99.8%)</span>
              </div>

            </div>
          </div>

          {/* Short recent logs preview */}
          <div className="bg-white border border-[#cbd5e1] shadow-sm flex flex-col">
            <div className="px-4 py-2.5 bg-slate-100 border-b border-[#cbd5e1] font-display text-xs font-bold text-[#0054A3]">
              ÚLTIMAS PUBLICACIONES DE OBRA
            </div>
            <div className="p-4 space-y-3 max-h-[160px] overflow-y-auto">
              {logs.slice(0, 3).map((l, i) => (
                <div key={l.id || i} className="flex gap-2 items-start text-xs border-b pb-2 last:border-0 last:pb-0">
                  <History className="text-[#0054A3] shrink-0 mt-0.5" size={13} />
                  <div className="truncate">
                    <p className="font-bold text-on-surface truncate">
                      {getMachineLabel(l.machineType)} ({l.horometroValue.toFixed(1)} hrs)
                    </p>
                    <p className="text-[10px] text-on-surface-variant truncate">
                      Por {l.operatorName.replace(" (Técnico)", "")} • {l.location.formattedAddress}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

        </div>

      </div>

      {/* High resolution detailed bitácora list database below */}
      <div className="bg-white border border-[#cbd5e1] p-4 shadow-sm flex flex-col gap-4">
        <div className="flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b pb-3 border-slate-100">
          <div>
            <h3 className="font-display text-base font-bold text-[#0054A3]">Bitácora Completa de Horómetros</h3>
            <p className="text-xs text-on-surface-variant font-medium">Buscador y exportador de lecturas certificadas de Guatemala.</p>
          </div>

          {/* Search box and exports */}
          <div className="flex flex-wrap items-center gap-3">
            <div className="relative">
              <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-3.5 h-3.5" />
              <input 
                type="text" 
                placeholder="Buscar por operador o lote..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-8 pr-3 py-1 bg-slate-50 border border-[#cbd5e1] text-xs font-medium outline-none focus:border-[#0054A3]"
              />
            </div>
            <button 
              type="button"
              onClick={() => onTriggerExport("Excel", filteredLogs.length)}
              className="text-xs font-bold bg-[#FFD200] text-[#0054A3] border border-[#FFD200] px-3 py-1 cursor-pointer hover:bg-[#ffe040] transition-colors"
            >
              EXCEL
            </button>
            <button 
              type="button"
              onClick={() => onTriggerExport("PDF", filteredLogs.length)}
              className="text-xs font-bold bg-slate-800 text-white border border-slate-800 px-3 py-1 cursor-pointer hover:bg-slate-700 transition-colors"
            >
              PDF
            </button>
          </div>
        </div>

        {/* Filters selection row */}
        <div className="flex flex-wrap gap-4 items-center bg-slate-50 p-2.5 text-xs text-on-surface-variant border border-slate-100">
          <div className="flex items-center gap-1.5">
            <span className="font-bold">Máquina:</span>
            <select 
              value={selectedMachineFilter}
              onChange={(e) => setSelectedMachineFilter(e.target.value)}
              className="bg-white border border-slate-200 px-2 py-0.5 outline-none focus:border-[#0054A3]"
            >
              <option value="all">Todas</option>
              <option value="tractor">Tractor</option>
              <option value="excavadora">Excavadora</option>
              <option value="retro">Retro Excavadora</option>
              <option value="rodo">Rodo</option>
              <option value="pipa">Pipa</option>
              <option value="volteo">Camión Volteo</option>
            </select>
          </div>

          <div className="flex items-center gap-1.5">
            <span className="font-bold">Registro:</span>
            <select 
              value={selectedRegTypeFilter}
              onChange={(e) => setSelectedRegTypeFilter(e.target.value)}
              className="bg-white border border-slate-200 px-2 py-0.5 outline-none focus:border-[#0054A3]"
            >
              <option value="all">Todos</option>
              <option value="inicial">Inicial</option>
              <option value="final">Final</option>
            </select>
          </div>
        </div>

        {/* Table implementation */}
        <div className="overflow-x-auto w-full">
          <table className="w-full text-left font-sans text-xs divide-y divide-slate-200">
            <thead className="bg-slate-100 font-display font-bold text-[10px] text-on-surface uppercase tracking-wider">
              <tr>
                <th className="py-2.5 px-3">Técnico / Fecha</th>
                <th className="py-2.5 px-3">Equipo</th>
                <th className="py-2.5 px-3">Clase</th>
                <th className="py-2.5 px-3">Horómetro</th>
                <th className="py-2.5 px-3">Ubicación GPS</th>
                <th className="py-2.5 px-3">Captura</th>
                <th className="py-2.5 px-3 text-center">Gestionar</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredLogs.length === 0 ? (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-slate-400 italic">
                    Ninguna bitácora coincide con los filtros establecidos
                  </td>
                </tr>
              ) : (
                filteredLogs.map((log) => {
                  const Icon = machineIconsMap[log.machineType] || Tractor;
                  return (
                    <tr key={log.id} className="hover:bg-slate-50 transition-colors">
                      <td className="py-2.5 px-3">
                        <p className="font-bold text-on-surface">{log.operatorName}</p>
                        <p className="text-[10px] text-on-surface-variant font-mono">{log.dateTime}</p>
                      </td>

                      <td className="py-2.5 px-3">
                        <span className="inline-flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                          <Icon size={12} className="text-[#0054A3]" />
                          <span className="font-semibold text-[10px]">{getMachineLabel(log.machineType)}</span>
                        </span>
                      </td>

                      <td className="py-2.5 px-3">
                        <span className={`px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider ${
                          log.regType === "inicial" ? "bg-emerald-100 text-emerald-800" : "bg-amber-100 text-amber-800"
                        }`}>
                          {log.regType}
                        </span>
                      </td>

                      <td className="py-2.5 px-3 font-mono font-bold text-sm text-[#0054A3]">
                        {log.horometroValue.toFixed(1)} <span className="text-[9px] text-[#475569]">HRS</span>
                      </td>

                      <td className="py-2.5 px-3 max-w-[150px] truncate" title={log.location.formattedAddress}>
                        <div className="flex items-center gap-0.5 text-[10px] text-red-600 font-mono">
                          <MapPin size={10} className="text-red-500" />
                          <span>{log.location.lat.toFixed(2)}°, {log.location.lng.toFixed(2)}°</span>
                        </div>
                        <p className="text-[9px] text-on-surface-variant truncate mt-0.5">{log.location.formattedAddress}</p>
                      </td>

                      <td className="py-2.5 px-3">
                        {log.photoUrl ? (
                          <button 
                            type="button"
                            onClick={() => onOpenPhotoModal(log.photoUrl as string)}
                            className="text-[10px] font-bold bg-[#0054A3]/10 text-[#0054A3] hover:bg-[#0054A3]/25 px-2 py-0.5 rounded border border-[#0054A3]/20 flex items-center gap-1 cursor-pointer transition-colors"
                          >
                            <Eye size={10} />
                            <span>VER DIAL</span>
                          </button>
                        ) : (
                          <span className="opacity-45 italic">N/A</span>
                        )}
                      </td>

                      <td className="py-2.5 px-3 text-center">
                        <button 
                          type="button"
                          onClick={() => onDeleteLog(log.id)}
                          className="p-1.5 hover:bg-red-50 text-red-600 rounded transition-colors"
                          title="Eliminar registro"
                        >
                          <Trash2 size={13} />
                        </button>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </motion.div>
  );
}
