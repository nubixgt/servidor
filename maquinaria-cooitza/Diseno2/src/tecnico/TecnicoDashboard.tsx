import React, { useState, useEffect } from "react";
import { 
  Filter, 
  Download, 
  Search, 
  LogOut, 
  ChevronLeft, 
  ChevronRight, 
  Cpu, 
  Activity, 
  Clock, 
  AlertTriangle, 
  CheckCircle, 
  Info,
  Layers,
  Wrench,
  Construction,
  Compass
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";
import { OperationalLog } from "../types";

interface TecnicoDashboardProps {
  onLogout: () => void;
}

interface SystemEvent {
  id: string;
  timestamp: string;
  category: "Telemetría" | "Acceso" | "Mantenimiento" | "Crítico";
  message: string;
  level: "INFO" | "WARN" | "ERROR";
  operator: string;
}

interface AssetDetails {
  id: string;
  name: string;
  category: string;
  imageUrl: string;
  lastInspection: string;
  note: string;
}

export default function TecnicoDashboard({ onLogout }: TecnicoDashboardProps) {
  const [searchTerm, setSearchTerm] = useState("");
  const [filterLevel, setFilterLevel] = useState<"ALL" | "INFO" | "WARN" | "ERROR">("ALL");
  const [filterCategory, setFilterCategory] = useState<string>("todos");
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 5;

  // Selected asset for the preview component
  const [selectedAssetId, setSelectedAssetId] = useState("asset-1");

  // Telemetry items parsed from localStorage or fallback
  const [systemEvents, setSystemEvents] = useState<SystemEvent[]>([]);
  const [totalSystemEventsCount, setTotalSystemEventsCount] = useState(4281);

  // CPU and hardware simulation values
  const [cpuLoad, setCpuLoad] = useState(24);
  const [freeMemory, setFreeMemory] = useState(82);
  const [latency, setLatency] = useState(4);

  // Load logs and populate table
  useEffect(() => {
    // Hardware dynamic ticks
    const interval = setInterval(() => {
      setCpuLoad(prev => Math.max(12, Math.min(68, prev + Math.floor(Math.random() * 7) - 3)));
      setLatency(prev => Math.max(2, Math.min(15, prev + Math.floor(Math.random() * 3) - 1)));
    }, 5000);

    // Retrieve operator logs
    const savedLogs = localStorage.getItem("cooitza_machinery_logs");
    let mappedOperatorEvents: SystemEvent[] = [];
    if (savedLogs) {
      try {
        const parsed: OperationalLog[] = JSON.parse(savedLogs);
        mappedOperatorEvents = parsed.map((log, index) => {
          const isCritical = log.horometroValue > 9000;
          const isWarning = log.regType === "final";
          return {
            id: `#LOG-${log.id.substring(4, 10).toUpperCase() || (8800 + index)}`,
            timestamp: log.dateTime || new Date().toLocaleString("es-GT"),
            category: "Telemetría",
            message: `Reporte de horómetro ${log.regType === "inicial" ? "Inicial" : "Final"} para ${log.machineType.toUpperCase()}: ${log.horometroValue.toLocaleString()} HRS en ${log.location.formattedAddress}`,
            level: isCritical ? "ERROR" : isWarning ? "WARN" : "INFO",
            operator: log.operatorName.split(" ")[0] || "OPERADOR"
          };
        });
      } catch (err) {
        console.error("Error loading technician dashboard logs", err);
      }
    }

    // Default static events as described in the user design
    const defaultStaticEvents: SystemEvent[] = [
      {
        id: "#LOG-8821",
        timestamp: "2024-05-24 14:22:10",
        category: "Telemetría",
        message: "Calibración de sensor térmico completada con éxito.",
        level: "INFO",
        operator: "SYS_AUTO"
      },
      {
        id: "#LOG-8820",
        timestamp: "2024-05-24 14:15:02",
        category: "Acceso",
        message: "Inicio de sesión detectado desde Terminal 04.",
        level: "INFO",
        operator: "R. Sanchez"
      },
      {
        id: "#LOG-8819",
        timestamp: "2024-05-24 13:58:45",
        category: "Mantenimiento",
        message: "Ciclo de lubricación preventiva programado para 16:00.",
        level: "WARN",
        operator: "SCHEDULER"
      },
      {
        id: "#LOG-8818",
        timestamp: "2024-05-24 13:40:12",
        category: "Crítico",
        message: "Fluctuación de voltaje fuera de rango en Nodo-B.",
        level: "ERROR",
        operator: "MONITOR_01"
      },
      {
        id: "#LOG-8817",
        timestamp: "2024-05-24 13:12:33",
        category: "Telemetría",
        message: "Lectura de presión hidráulica estable a 450 PSI.",
        level: "INFO",
        operator: "SYS_AUTO"
      },
      {
        id: "#LOG-8816",
        timestamp: "2022-05-24 12:45:10",
        category: "Mantenimiento",
        message: "Revisión preventiva de motor térmico realizada.",
        level: "INFO",
        operator: "M. Thorne"
      },
      {
        id: "#LOG-8815",
        timestamp: "2024-05-24 11:32:00",
        category: "Telemetría",
        message: "Nivel de combustible de tanque auxiliar de reserva verificado.",
        level: "INFO",
        operator: "E. Rodriguez"
      }
    ];

    // Combine operator-submitted logs with predefined industrial logs for maximum fidelity
    setSystemEvents([...mappedOperatorEvents, ...defaultStaticEvents]);
    setTotalSystemEventsCount(4281 + mappedOperatorEvents.length);

    return () => clearInterval(interval);
  }, []);

  // Preset assets for the selector
  const assetsList: AssetDetails[] = [
    {
      id: "asset-1",
      name: "TURBINA T-04 (PRINCIPAL)",
      category: "Generador de Fuerza",
      imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuAS9Jhi3aHz6hvNHkZbsh4wSd8jUg4j7ENX9-hTRqUo_iSI3bYWUZtb26N9Y3Qhzoqkg6DNp0zDe4XgWc0RBeEgb6Teccq_HtPHYiAbhl16rD3LHMDTENTs4BxJR-bSQAufpm2osCcCCNWL9XHrIrcVTb1y2FMGekiztxtlRL7T0Xm3O_dgYi9If8_3rCPCgL468tvInm-FJs6nxjRB_g4wwbpYEt-mVyZPAvrWQ459IoIFHuVxkWQM4MAJ8ltaj4qUU9uTbzjuTdfO",
      lastInspection: "Mayo 20, 2024 - Operador Técnico J. Doe",
      note: "Rendimiento nominal estable. Se recomienda seguimiento de vibración en el próximo ciclo."
    },
    {
      id: "asset-2",
      name: "EXCAVADORA CAT 320-B",
      category: "Movimiento de Tierras",
      imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuCW4-n0Uts09omCrK9hJAsromc1Y7GCJOZdeCvW2-u5sfFJXqom9XcKgbiq51rcx0mQNuEyHdpIGs8r9yViHSIpGwQ-Z7-RPkZ35ItK-6bQfr3kdlj5PT9e5KXQB3gtC7eSS279VcUjQS7-RNR9MPbwf5ypPuTg4CgEMIhyaVTzA00eWFBzQ74Pu3JtOSHdgJcFGtuDXY8l6dlcOrHUitHLowY1QP3UIFOg92Wkg41214T-JmcBsgoF8wyXoCRHv3C6SVyFE96IGl5b",
      lastInspection: "Mayo 24, 2024 - Inspector Miguel Fuentes",
      note: "Presión hidráulica en rangos estándar de operación. Pérdida menor detectada en manguera de retorno, ya resuelta."
    },
    {
      id: "asset-3",
      name: "TRACTOR JOHN DEERE 8R",
      category: "Preparación de Campo",
      imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuCtWEyh1Z-AVKb8m7r1Xd4QhYcVyxqNgGs7-QDVKHd0JvWlxT5MCJ0EPmyeydptGOjpmTw3CVlGGHGm53HGi_fza4tXXmiVp3tTR6S2n7gc02D3GN7Ko5Lc8Gv-BkHjm2F9kcmNC5ezQd7YofIuYhuYnHs-50gaNnQv7Livvi7M1RvyouOyT0-aegn6hvLevJh28ZSBMI76QCDIx27OkhuzjNPbxMQu8-cl0ANrBMiXuPsIX7-OsUgTo7TgPkIZQCwhWHSIMCSQg7PB",
      lastInspection: "Mayo 18, 2024 - Operador R. Andersson",
      note: "Calibración del GPS de piloto automático certificada. Filtros de transmisión reemplazados."
    }
  ];

  const activeAsset = assetsList.find(a => a.id === selectedAssetId) || assetsList[0];

  // Filtering implementation
  const filteredEvents = systemEvents.filter(event => {
    const matchesSearch = 
      event.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
      event.message.toLowerCase().includes(searchTerm.toLowerCase()) ||
      event.operator.toLowerCase().includes(searchTerm.toLowerCase()) ||
      event.category.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesLevel = filterLevel === "ALL" || event.level === filterLevel;
    
    const matchesCategory = filterCategory === "todos" || event.category.toLowerCase() === filterCategory.toLowerCase();

    return matchesSearch && matchesLevel && matchesCategory;
  });

  // Pagination calculation
  const totalItems = filteredEvents.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
  const paginatedEvents = filteredEvents.slice(
    (currentPage - 1) * itemsPerPage,
    currentPage * itemsPerPage
  );

  const handleExportData = () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(filteredEvents, null, 2));
    const downloadAnchor = document.createElement("a");
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", `cooitza_industrial_logs_${new Date().toISOString().substring(0, 10)}.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
  };

  const criticalEventsCount = systemEvents.filter(e => e.level === "ERROR").length;

  return (
    <motion.div 
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      className="w-full max-w-[1280px] mx-auto px-6 py-8 flex flex-col gap-8 text-[#191c1d]"
    >
      
      {/* Dynamic Header Row WITHOUT standard platform navbar */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 border border-slate-200">
        <div className="flex items-center gap-3">
          {/* Logo element representing Cooitzá Industrial Core */}
          <div className="w-12 h-12 bg-primary flex items-center justify-center font-display font-black text-xl text-white italic">
            C
          </div>
          <div>
            <h2 className="font-display text-xl font-bold tracking-tight text-primary">Cooitzá IndustrialMS</h2>
            <p className="text-[10px] uppercase font-mono-label font-bold text-on-surface-variant tracking-wider">
              Estación de Telemetría v4.2.1 • Vista Exclusiva de Técnico
            </p>
          </div>
        </div>

        {/* User Badge & Logout Option */}
        <div className="flex items-center gap-4 self-stretch md:self-auto justify-between md:justify-end border-t md:border-t-0 pt-4 md:pt-0 border-slate-100">
          <div className="flex items-center gap-2">
            <div className="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse" />
            <span className="font-sans text-xs font-semibold text-on-surface">Técnico Analista</span>
          </div>
          
          <button
            type="button"
            onClick={onLogout}
            className="flex items-center gap-1.5 px-3.5 py-1.5 border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 font-display text-[10px] font-bold uppercase tracking-wider transition-colors cursor-pointer"
          >
            <LogOut size={12} />
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </div>

      {/* Main Title Section */}
      <div className="flex flex-col lg:flex-row lg:justify-between lg:items-end gap-6">
        <div>
          <div className="flex items-center gap-1.5 mb-2">
            <span className="w-2 h-2 rounded-full bg-[#f5a623] animate-pulse"></span>
            <span className="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest">
              SISTEMA OPERATIVO • MODO LECTURA
            </span>
          </div>
          <h1 className="font-display text-4xl font-extrabold text-[#191c1d] tracking-tight">
            Historial de Registros
          </h1>
          <p className="text-on-surface-variant font-sans text-sm max-w-2xl mt-1.5 leading-relaxed">
            Supervisión técnica de eventos del sistema, telemetría y logs de mantenimiento para la Estación 04-B de la división de Maquinaria Pesada Cooitzá.
          </p>
        </div>

        {/* Top Search and Export widgets */}
        <div className="flex flex-wrap items-center gap-3 w-full lg:w-auto">
          {/* Real-time search inside the view */}
          <div className="relative flex-1 lg:flex-initial min-w-[240px]">
            <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
            <input 
              type="text"
              placeholder="Buscar registros específicos..."
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setCurrentPage(1);
              }}
              className="w-full pl-9 pr-3 py-2 border border-slate-200 outline-none bg-white text-xs focus:border-primary transition-colors focus:ring-0"
            />
          </div>

          <button 
            type="button"
            onClick={handleExportData}
            style={{ cursor: "pointer" }}
            className="flex items-center gap-1.5 px-4 py-2 border border-slate-200 bg-white hover:bg-slate-50 transition-colors font-display text-[10px] font-bold uppercase tracking-wider"
          >
            <Download size={14} className="text-primary" />
            <span>EXPORTAR JSON</span>
          </button>
        </div>
      </div>

      {/* Bento Grid Layout (Row 1: Summary Statistics) */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        {/* Stat Box 1: Total Events */}
        <div className="col-span-12 md:col-span-3 bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden group">
          <div className="absolute right-0 top-0 p-4 opacity-5 translate-x-3 -translate-y-3">
            <Activity size={80} />
          </div>
          <div>
            <span className="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-4">
              EVENTOS TOTALES
            </span>
            <span className="font-display text-4xl font-extrabold text-[#191c1d]">
              {totalSystemEventsCount.toLocaleString()}
            </span>
          </div>
          <div className="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center text-xs">
            <span className="text-[#524534]">Últimas 24h</span>
            <span className="font-display font-semibold text-primary">+{filteredEvents.length * 4} activos</span>
          </div>
        </div>

        {/* Stat Box 2: Critical alerts */}
        <div className="col-span-12 md:col-span-3 bg-white border border-slate-200 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden">
          <div>
            <span className="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-4">
              CRÍTICOS
            </span>
            <span className="font-display text-4xl font-extrabold text-red-650">
              {criticalEventsCount < 10 ? `0${criticalEventsCount}` : criticalEventsCount}
            </span>
          </div>
          <div className="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center text-xs">
            <span className="text-[#524534]">Estado de alertas</span>
            <span className="font-display italic text-slate-500 font-bold">Monitoreado en vivo</span>
          </div>
        </div>

        {/* Stat Box 3: Bar Distribution Chart of Activity */}
        <div className="col-span-12 md:col-span-6 bg-white border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
          <div className="flex justify-between items-center mb-4">
            <span className="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest">
              DISTRIBUCIÓN DE ACTIVIDAD DE MAQUINARIA
            </span>
            <span className="text-[10px] font-mono bg-primary/10 text-primary px-2 py-0.5 font-bold">
              ESTADO SATISFACTORIO
            </span>
          </div>

          {/* Simple premium dynamic css grid bar chart representation */}
          <div className="h-24 flex items-end gap-1.5 pt-2">
            {[40, 60, 80, 50, 95, 45, 70, 85, 30, 55, 75, 100, 60, 40].map((h, i) => (
              <div 
                key={i} 
                className="bg-primary/25 group-hover:bg-[#f5a623] hover:bg-primary transition-all duration-300 w-full relative"
                style={{ height: `${h}%` }}
                title={`Hora ${12-i}h atrás: ${h}% carga`}
              >
                {/* Micro tooltip */}
                <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-black text-white text-[8px] p-1 rounded whitespace-nowrap">
                  {h}%
                </div>
              </div>
            ))}
          </div>

          <div className="flex justify-between items-center mt-3 text-[10px] text-slate-400 font-mono">
            <span>HACE 12 HORAS</span>
            <span>TIEMPO REAL</span>
          </div>
        </div>

      </div>

      {/* Main Table Panel Log detailed views */}
      <div className="bg-white border border-slate-200 overflow-hidden shadow-sm">
        
        {/* Table header control row */}
        <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex flex-wrap justify-between items-center gap-4">
          <div className="flex items-center gap-2">
            <span className="font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">
              REGISTRO DETALLADO DE EVENTOS
            </span>
          </div>

          {/* Active Level Color Filters */}
          <div className="flex flex-wrap items-center gap-3">
            {/* All */}
            <button 
              type="button"
              onClick={() => { setFilterLevel("ALL"); setCurrentPage(1); }}
              className={`px-2.5 py-1 text-[10px] font-bold font-display transition-all ${
                filterLevel === "ALL" 
                  ? "bg-primary text-white" 
                  : "bg-white border border-slate-200 text-slate-600 hover:bg-slate-50"
              }`}
            >
              TODOS
            </button>
            
            {/* Info Filter */}
            <button 
              type="button"
              onClick={() => { setFilterLevel("INFO"); setCurrentPage(1); }}
              className={`flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold font-display transition-all ${
                filterLevel === "INFO" 
                  ? "bg-emerald-600 text-white" 
                  : "bg-white border border-slate-200 text-slate-600 hover:bg-slate-100"
              }`}
            >
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
              INFO
            </button>

            {/* Warning Filter */}
            <button 
              type="button"
              onClick={() => { setFilterLevel("WARN"); setCurrentPage(1); }}
              className={`flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold font-display transition-all ${
                filterLevel === "WARN" 
                  ? "bg-amber-500 text-white" 
                  : "bg-white border border-slate-200 text-slate-600 hover:bg-slate-100"
              }`}
            >
              <span className="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block" />
              WARN
            </button>

            {/* Error Filter */}
            <button 
              type="button"
              onClick={() => { setFilterLevel("ERROR"); setCurrentPage(1); }}
              className={`flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold font-display transition-all ${
                filterLevel === "ERROR" 
                  ? "bg-red-650 text-white" 
                  : "bg-white border border-slate-200 text-slate-600 hover:bg-slate-100"
              }`}
            >
              <span className="w-1.5 h-1.5 rounded-full bg-red-650 inline-block" />
              ERROR
            </button>
          </div>
        </div>

        {/* Responsive Table Grid */}
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="border-b border-slate-200 bg-slate-50/50">
                <th className="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">ID EVENTO</th>
                <th className="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">TIMESTAMP</th>
                <th className="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">CATEGORÍA</th>
                <th className="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">MENSAJE DEL SISTEMA</th>
                <th className="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider text-center">NIVEL</th>
                <th className="p-4 font-display text-[10px] font-bold text-[#524534] uppercase tracking-wider">OPERADOR</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {paginatedEvents.length === 0 ? (
                <tr>
                  <td colSpan={6} className="p-8 text-center text-slate-400 italic font-sans text-xs">
                    No se encontraron registros activos con los filtros indicados.
                  </td>
                </tr>
              ) : (
                paginatedEvents.map((event) => (
                  <tr 
                    key={event.id} 
                    className="hover:bg-slate-50 transition-colors group cursor-pointer border-l-2 border-transparent hover:border-primary"
                  >
                    <td className="p-4 font-mono text-xs font-bold text-on-surface">
                      {event.id}
                    </td>
                    <td className="p-4 font-mono text-xs text-slate-500 whitespace-nowrap">
                      {event.timestamp}
                    </td>
                    <td className="p-4">
                      <span className="bg-slate-150 inline-block px-2.5 py-0.5 rounded text-[10px] font-display font-medium text-slate-700">
                        {event.category}
                      </span>
                    </td>
                    <td className="p-4 text-xs font-sans text-on-surface">
                      {event.message}
                    </td>
                    <td className="p-4 text-center">
                      <span className={`w-2.5 h-2.5 rounded-full inline-block ${
                        event.level === "INFO" ? "bg-emerald-500 shadow-sm" :
                        event.level === "WARN" ? "bg-amber-400 shadow-sm" : "bg-red-500 shadow-sm animate-pulse"
                      }`} title={event.level} />
                    </td>
                    <td className="p-4 font-mono text-xs text-slate-600 font-medium">
                      {event.operator}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Footer of log table including Pagination */}
        <div className="px-6 py-4 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4 bg-slate-50/50">
          <span className="text-xs font-sans text-[#524534]">
            Mostrando {paginatedEvents.length} de {filteredEvents.length} entradas ({totalSystemEventsCount.toLocaleString()} totales en sistema)
          </span>

          <div className="flex items-center gap-1">
            <button 
              type="button"
              disabled={currentPage === 1}
              onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
              className="w-8 h-8 flex items-center justify-center border border-slate-200 disabled:opacity-45 bg-white hover:bg-slate-50 transition-colors text-slate-500"
            >
              <ChevronLeft size={16} />
            </button>

            {Array.from({ length: totalPages }).map((_, i) => {
              const pNum = i + 1;
              const isActive = currentPage === pNum;
              return (
                <button
                  key={pNum}
                  type="button"
                  onClick={() => setCurrentPage(pNum)}
                  className={`w-8 h-8 flex items-center justify-center text-xs font-mono font-bold transition-all ${
                    isActive 
                      ? "bg-primary text-white border border-primary" 
                      : "bg-white border border-slate-200 text-slate-700 hover:bg-slate-100"
                  }`}
                >
                  {pNum}
                </button>
              );
            })}

            <button 
              type="button"
              disabled={currentPage === totalPages}
              onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
              className="w-8 h-8 flex items-center justify-center border border-slate-200 disabled:opacity-45 bg-white hover:bg-slate-50 transition-colors text-slate-500"
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>

      </div>

      {/* Row 3: Hardware Health & Asset snapshot (Side-by-Side widgets) */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {/* Hardware Status Widget */}
        <div className="col-span-12 lg:col-span-4 bg-white border border-slate-200 p-6 shadow-sm flex flex-col justify-between">
          <div>
            <span className="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block mb-5">
              ESTADO DE HARDWARE PRINCIPAL
            </span>
            <div className="divide-y divide-slate-100">
              
              {/* Proc monitor */}
              <div className="flex justify-between items-center py-3">
                <span className="font-sans text-xs text-on-surface flex items-center gap-2">
                  <Cpu size={14} className="text-primary" />
                  Procesador Central
                </span>
                <span className="font-mono text-xs font-bold text-emerald-600">
                  {cpuLoad}% (OPTIMAL)
                </span>
              </div>

              {/* Buffer memory */}
              <div className="flex justify-between items-center py-3">
                <span className="font-sans text-xs text-on-surface flex items-center gap-2">
                  <Layers size={14} className="text-primary" />
                  Memoria del Buffer
                </span>
                <span className="font-mono text-xs font-bold text-emerald-600">
                  {freeMemory}% LIBRE
                </span>
              </div>

              {/* Server Latency */}
              <div className="flex justify-between items-center py-3">
                <span className="font-sans text-xs text-on-surface flex items-center gap-2">
                  <Activity size={14} className="text-primary" />
                  Conectividad Uplink
                </span>
                <span className="font-mono text-xs font-bold text-primary">
                  LATENCIA {latency}ms
                </span>
              </div>

              {/* Node-B Sensors */}
              <div className="flex justify-between items-center py-3">
                <span className="font-sans text-xs text-on-surface flex items-center gap-2">
                  <AlertTriangle size={14} className="text-red-500" />
                  Sensores Nodo-B
                </span>
                <span className="font-mono text-xs font-bold text-[#ba1a1a] animate-pulse">
                  REVISIÓN REQ.
                </span>
              </div>

            </div>
          </div>
          <div className="mt-6 pt-4 border-t border-slate-100">
            <p className="text-[9px] font-mono text-slate-400 uppercase tracking-wider text-center">
              Frecuencia de telemetría de bus: 1 Hz
            </p>
          </div>
        </div>

        {/* Dynamic Asset Preview Widget */}
        <div className="col-span-12 lg:col-span-8 bg-white border border-slate-200 p-6 shadow-sm flex flex-col">
          <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-4">
            <span className="font-display text-[10px] font-bold text-slate-500 uppercase tracking-widest block">
              VISTA PREVIA DE ASSET OPERATIVO
            </span>

            {/* Selector list for assets */}
            <div className="flex gap-1 bg-slate-100 p-0.5 rounded">
              {assetsList.map((a, idx) => (
                <button
                  key={a.id}
                  type="button"
                  onClick={() => setSelectedAssetId(a.id)}
                  className={`px-3 py-1 text-[9px] font-bold font-display uppercase tracking-widest transition-all ${
                    selectedAssetId === a.id 
                      ? "bg-white text-primary shadow-sm" 
                      : "text-slate-500 hover:text-slate-800"
                  }`}
                >
                  ACTIVO {idx + 1}
                </button>
              ))}
            </div>
          </div>

          {/* Active selection info layout */}
          <div className="flex flex-col sm:flex-row gap-6 mt-2 flex-grow">
            <div className="w-full sm:w-1/3 bg-slate-100 aspect-square rounded overflow-hidden shadow-inner border border-slate-100 shrink-0">
              <img 
                src={activeAsset.imageUrl} 
                alt={activeAsset.name} 
                referrerPolicy="no-referrer"
                className="w-full h-full object-cover grayscale opacity-80 hover:grayscale-0 hover:opacity-100 transition-all duration-500" 
              />
            </div>
            
            <div className="w-full sm:w-2/3 flex flex-col gap-3 justify-center">
              <div className="bg-slate-50 p-4 border-l-4 border-primary">
                <span className="font-display text-[8px] font-black text-primary uppercase tracking-widest block mb-1">
                  IDENTIFICADOR DE ASSET ACTIVO
                </span>
                <span className="font-display text-sm font-bold text-on-surface">
                  {activeAsset.name} ({activeAsset.category})
                </span>
              </div>

              <div className="bg-slate-50 p-4 border-l-4 border-amber-300">
                <span className="font-display text-[8px] font-black text-slate-600 uppercase tracking-widest block mb-1">
                  ÚLTIMA INSPECCIÓN CERTIFICADA El {new Date().toLocaleDateString("es-GT")}
                </span>
                <p className="font-sans text-xs text-on-surface font-medium">
                  {activeAsset.lastInspection}
                </p>
              </div>

              <div className="bg-slate-50 p-4 border-l-4 border-slate-300 flex-grow">
                <span className="font-display text-[8px] font-black text-slate-600 uppercase tracking-widest block mb-1">
                  NOTA ADJUNTA DE BITÁCORA
                </span>
                <p className="font-sans text-xs text-slate-600 italic">
                  "{activeAsset.note}"
                </p>
              </div>
            </div>
          </div>

        </div>

      </div>

      {/* Styled Footer for verification */}
      <footer className="w-full mt-4 bg-white border border-slate-200">
        <div className="flex flex-col sm:flex-row justify-between items-center p-6 gap-4">
          <span className="font-display text-[9px] font-bold text-slate-400 uppercase tracking-widest text-center sm:text-left">
            © 2026 COOITZÁ R.L. CLINICAL EFFICIENCY PROTOCOL. SISTEMAS CENTRALIZADOS.
          </span>
          <div className="flex gap-4">
            <span className="flex items-center gap-1.5 font-display text-[9px] font-bold text-slate-500 uppercase tracking-wider">
              <span className="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
              SISTEMA INTEGRADO ONLINE
            </span>
          </div>
        </div>
      </footer>

    </motion.div>
  );
}
