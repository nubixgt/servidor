import { useState, FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  ClipboardList, 
  Plus, 
  Search, 
  CheckCircle2, 
  AlertTriangle, 
  Clock, 
  Sliders, 
  TrendingUp, 
  Eye, 
  UserPlus, 
  Users,
  Wrench, 
  ShieldAlert, 
  Zap, 
  Image as ImageIcon,
  ChevronLeft,
  ChevronRight,
  Sparkles,
  Info
} from "lucide-react";

interface TimelineItem {
  id: string;
  title: string;
  time: string;
  description: string;
  type: "success" | "warning" | "info";
  tags: string[];
  images?: string[];
}

interface MaintenanceAlert {
  id: string;
  machineName: string;
  task: string;
  status: "vencido" | "proximo";
  currentHours: number;
  targetHours: number;
  daysRemainingOrOverdue: string;
  dept: string;
}

interface CrewSite {
  id: string;
  crewName: string;
  assignedTask: string;
  status: "En Progreso" | "Iniciado" | "Detenido";
  workersCount: number;
  efficiency: number;
}

export default function BitacoraMantenimiento() {
  const [timelineItems, setTimelineItems] = useState<TimelineItem[]>([
    {
      id: "TL-1",
      title: "Vaciado de Losa Nivel 4",
      time: "08:30 AM",
      description: "Se completó con éxito el vaciado de 120m3 de concreto premezclado de alta resistencia. Sin incidencias reportadas en la bomba ni vibradores.",
      type: "success",
      tags: ["Obras Civiles", "Concreto"],
      images: [
        "https://images.unsplash.com/photo-1541888946425-d81bb19240f5?auto=format&fit=crop&w=400&q=80",
        "https://images.unsplash.com/photo-1581094288338-2314dddb7ecc?auto=format&fit=crop&w=400&q=80"
      ]
    },
    {
      id: "TL-2",
      title: "Falla en Grúa Torre G-02",
      time: "11:15 AM",
      description: "Sobrecalentamiento en el sistema hidráulico principal. Se detienen operaciones preventivamente para revisión técnica de mangueras e intercambiadores.",
      type: "warning",
      tags: ["EquiposPesados", "Incidencia"],
    },
    {
      id: "TL-3",
      title: "Inspección de Seguridad Semanal",
      time: "02:45 PM",
      description: "Revisión de arneses de seguridad, líneas de vida y señalética protectora en zona de excavación fase 2. Cumplimiento general del 98%.",
      type: "info",
      tags: ["Seguridad", "Auditoría"],
    }
  ]);

  const [maintenanceAlerts, setMaintenanceAlerts] = useState<MaintenanceAlert[]>([
    {
      id: "MA-1",
      machineName: "Excavadora CAT-320",
      task: "Cambio de aceite hidráulico y filtros generales",
      status: "vencido",
      currentHours: 512,
      targetHours: 500,
      daysRemainingOrOverdue: "Excedido por 12 hrs",
      dept: "Equipos Obra"
    },
    {
      id: "MA-2",
      machineName: "Cargador Frontal L-12",
      task: "Revisión del sistema de frenos servo-asistidos y articulaciones de pala",
      status: "proximo",
      currentHours: 800,
      targetHours: 1000,
      daysRemainingOrOverdue: "Faltan 4 días",
      dept: "Equipos Obra"
    }
  ]);

  const [crews, setCrews] = useState<CrewSite[]>([
    {
      id: "CRW-1",
      crewName: "Cuadrilla A - Cimentación",
      assignedTask: "Armado de refuerzo zapata estructural Z-04",
      status: "En Progreso",
      workersCount: 12,
      efficiency: 92
    },
    {
      id: "CRW-2",
      crewName: "Cuadrilla B - Eléctricos",
      assignedTask: "Canalización de ductos soterrados principales de acometida",
      status: "Iniciado",
      workersCount: 6,
      efficiency: 45
    }
  ]);

  const [showAddLogModal, setShowAddLogModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [notification, setNotification] = useState("");

  // New Log form states
  const [newTitle, setNewTitle] = useState("");
  const [newTime, setNewTime] = useState("");
  const [newDesc, setNewDesc] = useState("");
  const [newType, setNewType] = useState<"success" | "warning" | "info">("info");
  const [newTagStr, setNewTagStr] = useState("");

  const triggerToast = (msg: string) => {
    setNotification(msg);
    setTimeout(() => {
      setNotification("");
    }, 4500);
  };

  const handleCreateLog = (e: FormEvent) => {
    e.preventDefault();
    if (!newTitle || !newDesc) return;

    const finalTags = newTagStr ? newTagStr.split(",").map(t => t.trim()) : ["General"];
    const finalTime = newTime || new Date().toLocaleTimeString("es-GT", { hour: "2-digit", minute: "2-digit" });

    const newLogItem: TimelineItem = {
      id: "TL-" + Math.floor(Math.random() * 900 + 100),
      title: newTitle,
      time: finalTime,
      description: newDesc,
      type: newType,
      tags: finalTags
    };

    setTimelineItems([newLogItem, ...timelineItems]);
    setShowAddLogModal(false);
    
    // Clear Form
    setNewTitle("");
    setNewTime("");
    setNewDesc("");
    setNewType("info");
    setNewTagStr("");

    triggerToast("¡Entrada agregada con éxito a la bitácora de obra!");
  };

  const handleScheduleMaintenance = (id: string, name: string) => {
    // Simulate scheduling maintenance
    setMaintenanceAlerts(maintenanceAlerts.filter(item => item.id !== id));
    triggerToast(`Mantenimiento para ${name} programado de manera exitosa.`);
  };

  const filteredLogs = timelineItems.filter(item => 
    item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    item.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
    item.tags.some(t => t.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      {/* Toast Alert */}
      <AnimatePresence>
        {notification && (
          <motion.div
            initial={{ opacity: 0, y: -20, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, scale: 0.95 }}
            className="fixed top-24 right-10 z-50 bg-primary/25 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl"
          >
            <CheckCircle2 className="w-5 h-5 text-primary" />
            <span className="text-xs font-black uppercase tracking-wider">{notification}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Page Header */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Bitácoras y Mantenimiento</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Control diario de operaciones y salud de activos críticos</p>
        </div>
        <button 
          onClick={() => setShowAddLogModal(true)}
          className="glass-button-primary bg-primary border-primary border text-white px-8 py-5 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
        >
          <Plus className="w-4 h-4" /> Nuevo Registro Bitácora
        </button>
      </div>

      {/* Main Grid Content Layout */}
      <div className="grid grid-cols-12 gap-10">
        
        {/* Left Column: Log Timeline (Col-Span 7) */}
        <section className="col-span-12 lg:col-span-7 space-y-8">
          <div className="flex justify-between items-center border-b border-white/5 pb-4">
            <h3 className="text-xl font-black italic uppercase tracking-wider flex items-center gap-3">
              <ClipboardList className="w-5 h-5 text-primary" />
              Línea de Tiempo de Obra
            </h3>
            
            {/* Search within Timeline */}
            <div className="relative">
              <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-white/40" />
              <input 
                type="text"
                placeholder="Buscar en bitácora..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="glass-input pl-10 pr-4 py-2.5 rounded-xl text-2xs uppercase tracking-widest font-bold placeholder:text-white/25 w-48 text-white focus:outline-none focus:border-primary/50"
              />
            </div>
          </div>

          <div className="glass-card rounded-[48px] p-10 border border-white/5 shadow-2xl relative overflow-hidden">
            
            {/* Horizontal Grid lines or timeline tracks */}
            <div className="absolute left-16 top-10 bottom-10 w-[2px] bg-white/5 pointer-events-none"></div>

            <div className="space-y-12 relative z-10">
              {filteredLogs.map((item) => (
                <div key={item.id} className="flex gap-6 relative group">
                  {/* Timeline bullet/icon indicator */}
                  <div className={`w-12 h-12 rounded-2xl flex items-center justify-center z-10 transition-transform duration-300 group-hover:scale-110 shadow-lg ${
                    item.type === "success" ? "bg-emerald-500/15 border border-emerald-500/30 text-emerald-400" :
                    item.type === "warning" ? "bg-rose-500/15 border border-rose-500/30 text-rose-400 animate-pulse" :
                    "bg-primary/15 border border-primary/20 text-primary"
                  }`}>
                    {item.type === "success" ? (
                      <CheckCircle2 className="w-5 h-5" />
                    ) : item.type === "warning" ? (
                      <AlertTriangle className="w-5 h-5" />
                    ) : (
                      <Info className="w-5 h-5" />
                    )}
                  </div>

                  {/* Body Content */}
                  <div className="flex-1 space-y-3 bg-white/[0.01] hover:bg-white/[0.03] p-6 rounded-3xl border border-transparent hover:border-white/5 transition-all">
                    <div className="flex justify-between items-start gap-4">
                      <div>
                        <span className="text-[10px] font-black tracking-widest text-[#004ced] dark:text-primary bg-primary/20 px-2.5 py-0.5 rounded uppercase">
                          {item.id}
                        </span>
                        <h4 className="text-lg font-black italic tracking-tight text-white uppercase mt-2 group-hover:text-primary transition-colors">
                          {item.title}
                        </h4>
                      </div>
                      <span className="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-1.5 bg-white/5 px-2 py-1 rounded">
                        <Clock className="w-3.5 h-3.5" />
                        {item.time}
                      </span>
                    </div>

                    <p className="text-xs text-white/50 leading-relaxed font-bold">
                      {item.description}
                    </p>

                    {/* Tags */}
                    <div className="flex flex-wrap gap-2 pt-2">
                      {item.tags.map((tag, tIdx) => (
                        <span key={tIdx} className="text-[9px] font-black uppercase tracking-wider text-white/40 bg-white/5 border border-white/5 px-2.5 py-1 rounded-lg">
                          #{tag}
                        </span>
                      ))}
                    </div>

                    {/* Render attachment photostream if present */}
                    {item.images && item.images.length > 0 && (
                      <div className="grid grid-cols-2 md:grid-cols-3 gap-4 pt-3.5">
                        {item.images.map((imgUrl, iIdx) => (
                          <div key={iIdx} className="relative h-24 rounded-2xl overflow-hidden border border-white/10 group/img cursor-pointer">
                            <img 
                              src={imgUrl} 
                              alt="Log preview attachment"
                              className="w-full h-full object-cover grayscale hover:grayscale-0 transition-all duration-300"
                            />
                            <div className="absolute inset-0 bg-black/40 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center">
                              <ImageIcon className="w-5 h-5 text-white" />
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* Right Column: Maintenance & State (Col-Span 5) */}
        <section className="col-span-12 lg:col-span-5 space-y-8">
          
          {/* Section Indicator */}
          <div className="border-b border-white/5 pb-4">
            <h3 className="text-xl font-black italic uppercase tracking-wider flex items-center gap-3">
              <Wrench className="w-5 h-5 text-primary" />
              Mantenimiento Preventivo
            </h3>
          </div>

          <div className="space-y-6">
            {maintenanceAlerts.map((alert) => (
              <motion.div 
                whileHover={{ x: 6 }}
                key={alert.id}
                className={`glass-card p-8 rounded-[36px] border-l-4 shadow-xl flex flex-col justify-between ${
                  alert.status === "vencido" ? "border-rose-500/70" : "border-primary/70"
                }`}
              >
                <div className="flex items-start gap-4">
                  <div className={`p-3 rounded-2xl ${
                    alert.status === 'vencido' 
                      ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' 
                      : 'bg-primary/10 text-primary border border-primary/20'
                  }`}>
                    <Zap className="w-6 h-6" />
                  </div>

                  <div className="flex-grow space-y-3">
                    <div className="flex justify-between items-start">
                      <h4 className="font-extrabold text-white text-base tracking-tight uppercase italic">{alert.machineName}</h4>
                      <span className={`text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg ${
                        alert.status === "vencido" 
                          ? "bg-rose-500/20 text-rose-400 animate-pulse border border-rose-500/10" 
                          : "bg-primary/20 text-primary border border-primary/10"
                      }`}>
                        {alert.status}
                      </span>
                    </div>

                    <p className="text-xs text-white/50 leading-relaxed font-bold uppercase">{alert.task}</p>

                    {/* Progress tracking */}
                    <div className="pt-2">
                      <div className="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                        <div 
                          className={`h-full rounded-full ${alert.status === 'vencido' ? 'bg-rose-500' : 'bg-primary'}`} 
                          style={{ width: `${Math.min(100, (alert.currentHours / alert.targetHours) * 100)}%` }}
                        ></div>
                      </div>
                      
                      <div className="flex justify-between items-center mt-2.5 text-[10px] font-bold text-white/30 uppercase tracking-widest">
                        <span>{alert.currentHours} / {alert.targetHours} Horas De Trabajo</span>
                        <span className={alert.status === 'vencido' ? 'text-rose-400' : 'text-white/60'}>
                          {alert.daysRemainingOrOverdue}
                        </span>
                      </div>
                    </div>

                    {/* Program Option Button */}
                    <div className="text-right pt-2">
                      <button 
                        onClick={() => handleScheduleMaintenance(alert.id, alert.machineName)}
                        className="text-[10px] font-black uppercase tracking-widest text-primary hover:text-white transition-colors"
                      >
                        Programar Servicio Técnico
                      </button>
                    </div>
                  </div>
                </div>
              </motion.div>
            ))}

            {/* General Fleet Metrics Card */}
            <div className="glass-card rounded-[40px] p-8 border border-white/5 space-y-6 bg-gradient-to-br from-[#003ec7]/10 to-transparent">
              <h5 className="text-sm font-black uppercase tracking-widest text-white/60">Estado General de la Flota</h5>
              
              <div className="grid grid-cols-2 gap-4">
                <div className="bg-white/5 p-5 rounded-3xl text-center border border-white/5">
                  <p className="text-4xl font-black italic text-primary">24</p>
                  <p className="text-[10px] font-black text-white/40 uppercase tracking-widest mt-2">Maquinarias Activas</p>
                </div>

                <div className="bg-white/5 p-5 rounded-3xl text-center border border-white/5">
                  <p className="text-4xl font-black italic text-rose-400">3</p>
                  <p className="text-[10px] font-black text-white/40 uppercase tracking-widest mt-2">En Mantenimiento</p>
                </div>
              </div>

              <button 
                onClick={() => triggerToast("Mostrando inventario total de activos pesados de ConstructPro")}
                className="w-full py-4 text-[10px] font-black uppercase tracking-widest text-[#003ec7] dark:text-primary hover:bg-white/5 rounded-2xl border border-primary/20 transition-all text-center"
              >
                Ver Reporte de Activos Completo
              </button>
            </div>
          </div>
        </section>
      </div>

      {/* Site Worker Grid list (Full Width Bottom) */}
      <section className="space-y-6 pt-10">
        <div className="flex flex-col md:flex-row justify-between md:items-center gap-4 border-b border-white/5 pb-4">
          <div>
            <h3 className="text-xl font-black italic uppercase tracking-wider flex items-center gap-3">
              <Users className="w-5 h-5 text-primary" />
              Ingeniería & Personal en Sitio
            </h3>
            <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Monitoreo de cuadrillas operando hoy en obra</p>
          </div>

          <div className="flex -space-x-3 items-center bg-white/5 px-4 py-2 rounded-2xl border border-white/5">
            <span className="text-[10px] font-black text-white/40 uppercase tracking-widest mr-4">Responsables directos</span>
            {[
              "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80",
              "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=100&q=80",
              "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&q=80"
            ].map((usr, uIdx) => (
              <img 
                key={uIdx}
                src={usr} 
                alt="Representative Worker" 
                className="w-8 h-8 rounded-full border-2 border-[#0b1c30] object-cover"
              />
            ))}
            <div className="w-8 h-8 rounded-full border-2 border-[#0b1c30] bg-primary flex items-center justify-center text-[9px] font-black text-white">
              +42
            </div>
          </div>
        </div>

        {/* Crew Table Grid Representation */}
        <div className="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead>
                <tr className="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                  <th className="px-10 py-6">Cuadrilla / Equipo</th>
                  <th className="px-10 py-6">Fase Asignada</th>
                  <th className="px-10 py-6 text-center">Estado de Operación</th>
                  <th className="px-10 py-6 text-right">Personal Total</th>
                  <th className="px-10 py-6">Rendimiento Estimado</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {crews.map((crew) => (
                  <tr key={crew.id} className="hover:bg-white/5 transition-all duration-300">
                    <td className="px-10 py-6 font-extrabold text-white tracking-tight text-base uppercase italic">
                      {crew.crewName}
                    </td>
                    <td className="px-10 py-6 text-xs text-white/50 font-bold uppercase">
                      {crew.assignedTask}
                    </td>
                    <td className="px-10 py-6 text-center">
                      <span className={`px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border ${
                        crew.status === "En Progreso" ? "bg-primary/20 text-primary border-primary/25" :
                        "bg-white/5 text-white/40 border-white/10"
                      }`}>
                        {crew.status}
                      </span>
                    </td>
                    <td className="px-10 py-6 text-right font-black italic text-base text-white">
                      {crew.workersCount} Operarios
                    </td>
                    <td className="px-10 py-6">
                      <div className="flex items-center gap-3">
                        <div className="w-24 bg-white/5 h-2 rounded-full overflow-hidden">
                          <div className="bg-primary h-full rounded-full" style={{ width: `${crew.efficiency}%` }}></div>
                        </div>
                        <span className="text-xs font-black italic text-primary">{crew.efficiency}%</span>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {/* Floating Sparkles Analysis Insights */}
      <div className="glass-card p-10 rounded-[48px] border border-dashed border-primary/30 bg-gradient-to-br from-primary/5 to-transparent relative overflow-hidden">
        <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl"></div>
        <div className="flex gap-4 items-start">
          <div className="bg-primary/20 p-4 rounded-full text-primary border border-white/5 shadow-lg">
            <Sparkles className="w-6 h-6 animate-pulse" />
          </div>
          <div>
            <h5 className="text-lg font-black italic uppercase tracking-wider text-white">Optimización del Cronograma y Desgaste</h5>
            <p className="text-xs text-white/50 font-medium leading-relaxed mt-3">
              Nuestro simulador AI ha identificado que alternar el uso de la Grúa Torre G-02 con la G-01 durante la Fase de Acabados reducirá los picos de temperatura en un <strong>20% general</strong>, aumentando el intervalo operacional recomendado en hasta 80 horas de trabajo sin incurrir en mantenimiento preventivo anticipado.
            </p>
          </div>
        </div>
      </div>

      {/* Create timeline log Modal Sheet */}
      <AnimatePresence>
        {showAddLogModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowAddLogModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Nuevo Registro</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Registrar observaciones, incidencias y eventos diarios en obra</p>

              <form onSubmit={handleCreateLog} className="space-y-6">
                
                {/* Title */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Título de Evento</label>
                  <input 
                    type="text"
                    required
                    value={newTitle}
                    onChange={(e) => setNewTitle(e.target.value)}
                    placeholder="E.g. Inspección estructural nivel 5"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                <div className="grid grid-cols-2 gap-6">
                  {/* Time */}
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Hora (Opcional)</label>
                    <input 
                      type="text"
                      value={newTime}
                      onChange={(e) => setNewTime(e.target.value)}
                      placeholder="E.g. 03:00 PM"
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                    />
                  </div>

                  {/* Type */}
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Criticidad / Categoría</label>
                    <select 
                      value={newType}
                      onChange={(e) => setNewType(e.target.value as any)}
                      className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
                    >
                      <option value="info" className="bg-slate-950 text-white">Informativo</option>
                      <option value="success" className="bg-slate-950 text-white">Completado</option>
                      <option value="warning" className="bg-slate-950 text-white">Incidencia / Advertencia</option>
                    </select>
                  </div>
                </div>

                {/* Description */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Detalle / Notas de campo</label>
                  <textarea 
                    rows={3}
                    required
                    value={newDesc}
                    onChange={(e) => setNewDesc(e.target.value)}
                    placeholder="Escriba aquí los detalles correspondientes..."
                    className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 text-white focus:outline-none focus:border-primary/50"
                  ></textarea>
                </div>

                {/* Tags input */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Etiquetas (Separadas por comas)</label>
                  <input 
                    type="text"
                    value={newTagStr}
                    onChange={(e) => setNewTagStr(e.target.value)}
                    placeholder="E.g. Concreto, Excavaciones, Supervisor"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                {/* Submit buttons */}
                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border border-primary text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                  >
                    Confirmar Registro
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowAddLogModal(false)}
                    className="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-black text-white/50"
                  >
                    Cancelar
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
