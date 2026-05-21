import React, { useState, FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  Bell, 
  AlertTriangle, 
  CheckCircle2, 
  Trash2, 
  Mail, 
  MessageSquare, 
  Info, 
  Coins, 
  Wrench, 
  UserPlus, 
  Building, 
  Search, 
  Settings2, 
  Check, 
  ChevronRight, 
  Calendar, 
  X,
  FileCheck,
  Smartphone,
  CloudLightning,
  Sparkles,
  Server
} from "lucide-react";

interface NotificationItem {
  id: string;
  title: string;
  category: "finanzas" | "personal" | "maquinaria" | "proyectos";
  description: string;
  isUrgent: boolean;
  timeAgo: string;
  projectOrMeta: string;
  valueOrPriority: string;
  isRead: boolean;
}

interface SourceStatus {
  id: string;
  name: string;
  status: "online" | "syncing" | "offline";
}

export default function NotificationsAlerts() {
  const [searchTerm, setSearchTerm] = useState("");
  const [activeTab, setActiveTab] = useState<"todas" | "finanzas" | "personal" | "maquinaria" | "proyectos">("todas");
  const [showConfigModal, setShowConfigModal] = useState(false);
  const [notification, setNotification] = useState("");

  // Alert channel configuration states
  const [emailAlerts, setEmailAlerts] = useState(true);
  const [pushAlerts, setPushAlerts] = useState(true);
  const [smsAlerts, setSmsAlerts] = useState(false);

  // Core simulated notification items
  const [notifications, setNotifications] = useState<NotificationItem[]>([
    {
      id: "NT-101",
      title: "Retraso Crítico en Cimentación",
      category: "proyectos",
      description: "El Proyecto SkyTower reporta un retraso de 48h debido a falla mecánica en la excavadora principal. Requiere reprogramación inmediata de turnos.",
      isUrgent: true,
      timeAgo: "Hace 15 min",
      projectOrMeta: "SkyTower Phase 1",
      valueOrPriority: "Urgente",
      isRead: false
    },
    {
      id: "NT-102",
      title: "Factura Aprobada",
      category: "finanzas",
      description: "La factura #F-2024-089 (Suministros de Acero Corrugado) ha sido validada y aprobada por el departamento contable principal.",
      isUrgent: false,
      timeAgo: "Hace 2 horas",
      projectOrMeta: "Compra de Aceros",
      valueOrPriority: "Q12,450.00",
      isRead: false
    },
    {
      id: "NT-103",
      title: "Mantenimiento Preventivo Requerido",
      category: "maquinaria",
      description: "La Grúa Torre GT-04 requiere cambio de aceite hidráulico de alta densidad y revisión de tensión de cables mecánicos en los próximos 3 días.",
      isUrgent: false,
      timeAgo: "Hace 5 horas",
      projectOrMeta: "GT-04",
      valueOrPriority: "Medio",
      isRead: false
    },
    {
      id: "NT-104",
      title: "Nuevo Ingreso a Equipo de Obra",
      category: "personal",
      description: "Roberto Gómez se ha unido oficialmente al proyecto residencial como Inspector de Seguridad Industrial certificado en normas ISO 4501.",
      isUrgent: false,
      timeAgo: "Ayer, 09:30 AM",
      projectOrMeta: "ID: #8900",
      valueOrPriority: "Informativo",
      isRead: true
    },
    {
      id: "NT-105",
      title: "Vencimiento Próximo de Póliza",
      category: "finanzas",
      description: "La póliza de vicios ocultos para el Puente Interconector Sur vencerá en 30 días calendario. Solicite renovación regulatoria.",
      isUrgent: false,
      timeAgo: "Hace 2 días",
      projectOrMeta: "Póliza de Fianzas",
      valueOrPriority: "Q45,000.00",
      isRead: true
    },
    {
      id: "NT-106",
      title: "Rendimiento semanal superado",
      category: "proyectos",
      description: "La cuadrilla de acabados arquitectónicos en Urbanización Las Colinas completó el doble de metrajes planificados para esta quincena.",
      isUrgent: false,
      timeAgo: "Hace 3 días",
      projectOrMeta: "Colinas",
      valueOrPriority: "Alta Eficiencia",
      isRead: true
    }
  ]);

  const [sources, setSources] = useState<SourceStatus[]>([
    { id: "SRC-1", name: "Sensores IOT Maquinaria contratista", status: "online" },
    { id: "SRC-2", name: "API Banco Central (Plataforma FX)", status: "online" },
    { id: "SRC-3", name: "Logs de Seguridad Física de Obra", status: "online" },
    { id: "SRC-4", name: "Sincronización de Costos ERP", status: "syncing" }
  ]);

  const showToast = (msg: string) => {
    setNotification(msg);
    setTimeout(() => {
      setNotification("");
    }, 4500);
  };

  const handleMarkAllRead = () => {
    setNotifications(prev => prev.map(nt => ({ ...nt, isRead: true })));
    showToast("Todas las notificaciones marcadas como leídas");
  };

  const handleMarkAsRead = (id: string) => {
    setNotifications(prev => prev.map(nt => {
      if (nt.id === id) {
        return { ...nt, isRead: true };
      }
      return nt;
    }));
    showToast("Notificación leída");
  };

  const handleDeleteNotification = (id: string, e: React.MouseEvent) => {
    e.stopPropagation();
    setNotifications(prev => prev.filter(nt => nt.id !== id));
    showToast("Notificación removida del buzón");
  };

  const handleSaveConfig = (e: FormEvent) => {
    e.preventDefault();
    setShowConfigModal(false);
    showToast("Canales de alertas actualizados correctamente");
  };

  // State stats computations
  const criticalCount = notifications.filter(n => n.isUrgent && !n.isRead).length;
  const unreadCount = notifications.filter(n => !n.isRead).length;
  const financeCount = notifications.filter(n => n.category === "finanzas").length;
  const totalCount = notifications.length;

  const filteredNotifications = notifications.filter(nt => {
    const matchesSearch = nt.title.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          nt.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          nt.projectOrMeta.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesTab = activeTab === "todas" || nt.category === activeTab;
    return matchesSearch && matchesTab;
  });

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      {/* Toast Notification helper */}
      <AnimatePresence>
        {notification && (
          <motion.div
            initial={{ opacity: 0, y: -20, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, scale: 0.95 }}
            className="fixed top-24 right-10 z-50 bg-primary/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl"
          >
            <CheckCircle2 className="w-5 h-5 text-primary" />
            <span className="text-xs font-black uppercase tracking-wider">{notification}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Header View Area */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Centro de Mensajes</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestiona las alertas críticas y actualizaciones de tus proyectos en tiempo real</p>
        </div>
        
        <div className="flex flex-wrap items-center gap-4">
          <button 
            onClick={handleMarkAllRead}
            className="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
          >
            <FileCheck className="w-4 h-4 text-primary" /> Marcar Todo Como Leído
          </button>

          <button 
            onClick={() => setShowConfigModal(true)}
            className="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
          >
            <Settings2 className="w-4 h-4" /> Configurar Alertas
          </button>
        </div>
      </div>

      {/* Bento Grid Analytics Metrics */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
        
        {/* Urgent Alerts card */}
        <div 
          onClick={() => { setActiveTab("todas"); setSearchTerm(""); }}
          className="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-rose-500/50 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
        >
          <div className="flex justify-between items-center">
            <AlertTriangle className="w-5 h-5 text-rose-400" />
            <span className="text-[10px] font-black text-white/30 uppercase tracking-widest">Críticas</span>
          </div>
          <div>
            <h3 className="text-4xl font-black italic text-white tracking-tighter">{criticalCount}</h3>
            <p className="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">Requieren acción inmediata</p>
          </div>
        </div>

        {/* Global state info card */}
        <div 
          onClick={() => { setActiveTab("todas"); setSearchTerm(""); }}
          className="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-primary/50 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
        >
          <div className="flex justify-between items-center">
            <Info className="w-5 h-5 text-primary" />
            <span className="text-[10px] font-black text-white/30 uppercase tracking-widest font-sans">Nuevos</span>
          </div>
          <div>
            <h3 className="text-4xl font-black italic text-white tracking-tighter">{unreadCount}</h3>
            <p className="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">No leídos actualmente</p>
          </div>
        </div>

        {/* Financial alert tracker */}
        <div 
          onClick={() => setActiveTab("finanzas")}
          className="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-emerald-500/50 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
        >
          <div className="flex justify-between items-center">
            <Coins className="w-5 h-5 text-emerald-400" />
            <span className="text-[10px] font-black text-white/30 uppercase tracking-widest">Finanzas</span>
          </div>
          <div>
            <h3 className="text-4xl font-black italic text-white tracking-tighter">{financeCount}</h3>
            <p className="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">Registros de cuentas y egresos</p>
          </div>
        </div>

        {/* Historical entries */}
        <div 
          onClick={() => { setActiveTab("todas"); setSearchTerm(""); }}
          className="glass-card p-6 rounded-3xl border border-white/5 border-l-4 border-white/20 cursor-pointer hover:bg-white/[0.03] transition-all flex flex-col justify-between h-40"
        >
          <div className="flex justify-between items-center">
            <Bell className="w-5 h-5 text-white/50" />
            <span className="text-[10px] font-black text-white/30 uppercase tracking-widest">Historial</span>
          </div>
          <div>
            <h3 className="text-4xl font-black italic text-white/75 tracking-tighter">{totalCount}</h3>
            <p className="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-1">Notificaciones registradas</p>
          </div>
        </div>

      </div>

      {/* Primary Notifications Container & filter tabs */}
      <div className="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
        
        {/* Tabs block */}
        <div className="flex items-center gap-8 px-10 py-6 border-b border-white/5 bg-white/5 overflow-x-auto scrollbar-hide">
          <button 
            onClick={() => setActiveTab("todas")}
            className={`text-xs font-black uppercase tracking-widest pb-3 transition-colors ${
              activeTab === "todas" ? "text-primary border-b-2 border-primary" : "text-white/40 hover:text-white"
            }`}
          >
            Todas las Notificaciones
          </button>

          <button 
            onClick={() => setActiveTab("finanzas")}
            className={`text-xs font-black uppercase tracking-widest pb-3 transition-colors ${
              activeTab === "finanzas" ? "text-primary border-b-2 border-primary" : "text-white/40 hover:text-white"
            }`}
          >
            Finanzas
          </button>

          <button 
            onClick={() => setActiveTab("personal")}
            className={`text-xs font-black uppercase tracking-widest pb-3 transition-colors ${
              activeTab === "personal" ? "text-primary border-b-2 border-primary" : "text-white/40 hover:text-white"
            }`}
          >
            Personal
          </button>

          <button 
            onClick={() => setActiveTab("maquinaria")}
            className={`text-xs font-black uppercase tracking-widest pb-3 transition-colors ${
              activeTab === "maquinaria" ? "text-primary border-b-2 border-primary" : "text-white/40 hover:text-white"
            }`}
          >
            Maquinaria
          </button>

          <button 
            onClick={() => setActiveTab("proyectos")}
            className={`text-xs font-black uppercase tracking-widest pb-3 transition-colors ${
              activeTab === "proyectos" ? "text-primary border-b-2 border-primary" : "text-white/40 hover:text-white"
            }`}
          >
            Proyectos
          </button>
        </div>

        {/* Live Filter Search bar */}
        <div className="p-8 border-b border-white/5 bg-black/10 flex items-center justify-between">
          <div className="relative w-full max-w-md">
            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/30" />
            <input 
              type="text"
              placeholder="Buscar alertas por palabra clave o proyecto..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="glass-input pl-11 pr-4 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-full text-white placeholder:text-white/20"
            />
          </div>
          <span className="text-[10px] font-black text-white/20 uppercase tracking-widest">
            {filteredNotifications.length} de {notifications.length} cargadas
          </span>
        </div>

        {/* Notifications list Rows split */}
        <div className="divide-y divide-white/5">
          <AnimatePresence initial={false}>
            {filteredNotifications.length === 0 ? (
              <div className="px-10 py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">
                No tienes notificaciones o alertas en este módulo.
              </div>
            ) : (
              filteredNotifications.map((nt) => {
                const getCategoryStyles = () => {
                  switch (nt.category) {
                    case "finanzas":
                      return { bg: "bg-emerald-500/10 text-emerald-400 border border-emerald-500/20", icon: Coins };
                    case "personal":
                      return { bg: "bg-amber-500/10 text-amber-400 border border-amber-500/20", icon: UserPlus };
                    case "maquinaria":
                      return { bg: "bg-sky-500/10 text-sky-400 border border-sky-500/20", icon: Wrench };
                    case "proyectos":
                      return { bg: "bg-indigo-500/10 text-indigo-400 border border-indigo-500/20", icon: Building };
                    default:
                      return { bg: "bg-white/5 text-white/50 border border-white/5", icon: Info };
                  }
                };

                const IconElement = getCategoryStyles().icon;

                return (
                  <motion.div
                    key={nt.id}
                    layoutId={`row-${nt.id}`}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    onClick={() => handleMarkAsRead(nt.id)}
                    className={`group flex flex-col md:flex-row items-start md:items-center gap-6 px-10 py-6 transition-colors cursor-pointer ${
                      nt.isUrgent && !nt.isRead ? "bg-rose-500/[0.04] hover:bg-rose-500/[0.08]" :
                      !nt.isRead ? "bg-primary/[0.02] hover:bg-primary/[0.05]" :
                      "hover:bg-white/[0.02]"
                    }`}
                  >
                    {/* Category logo icon */}
                    <div className={`flex-shrink-0 w-12 h-12 rounded-2xl flex items-center justify-center ${
                      nt.isUrgent && !nt.isRead ? "bg-rose-500/15 text-rose-400 border border-rose-500/20 animate-pulse" : getCategoryStyles().bg
                    }`}>
                      {nt.isUrgent && !nt.isRead ? (
                        <AlertTriangle className="w-5 h-5" />
                      ) : (
                        <IconElement className="w-5 h-5" />
                      )}
                    </div>

                    {/* Content information stack */}
                    <div className="flex-grow space-y-1.5 min-w-0">
                      <div className="flex items-center flex-wrap gap-2.5">
                        <h4 className={`font-extrabold text-white text-base tracking-tight uppercase italic ${nt.isRead ? "opacity-60" : ""}`}>
                          {nt.title}
                        </h4>
                        
                        {/* Status tag pills */}
                        {nt.isUrgent && !nt.isRead && (
                          <span className="px-3 py-0.5 bg-rose-500 text-white font-black text-[9px] uppercase tracking-widest rounded-lg">
                            Urgente
                          </span>
                        )}

                        <span className="text-[10px] font-black uppercase tracking-wider text-white/30">
                          {nt.id}
                        </span>
                      </div>

                      <p className={`text-xs text-white/50 leading-relaxed font-semibold transition-all ${nt.isRead ? "opacity-50" : ""}`}>
                        {nt.description}
                      </p>

                      {/* Bottom context specs */}
                      <div className="flex items-center flex-wrap gap-4 pt-1.5 text-[10px] font-black text-white/30 uppercase tracking-widest">
                        <span className="flex items-center gap-1.5">
                          <Calendar className="w-3.5 h-3.5" />
                          {nt.timeAgo}
                        </span>
                        
                        <span className="text-white/10">•</span>

                        <span className="text-primary italic">
                          {nt.projectOrMeta}
                        </span>

                        <span className="text-white/10">•</span>

                        <span className={nt.isUrgent && !nt.isRead ? "text-rose-400" : "text-emerald-400"}>
                          {nt.valueOrPriority}
                        </span>
                      </div>

                    </div>

                    {/* Quick Dismiss or action buttons */}
                    <div className="flex items-center gap-2 self-end md:self-center opacity-0 group-hover:opacity-100 transition-opacity">
                      {!nt.isRead && (
                        <button 
                          onClick={(e) => { e.stopPropagation(); handleMarkAsRead(nt.id); }}
                          title="Marcar como leído"
                          className="p-2.5 bg-white/5 hover:bg-white/10 text-white/50 hover:text-white rounded-xl border border-white/5 transition-all"
                        >
                          <Check className="w-4 h-4 text-emerald-400" />
                        </button>
                      )}
                      
                      <button 
                        onClick={(e) => handleDeleteNotification(nt.id, e)}
                        title="Eliminar registro"
                        className="p-2.5 bg-white/5 hover:bg-white/10 text-white/50 hover:text-rose-400 rounded-xl border border-white/5 transition-all"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </div>

                  </motion.div>
                );
              })
            )}
          </AnimatePresence>
        </div>

      </div>

      {/* Dashboard Insights Summary cards & Data Source Checklist */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        {/* Asymmetrical Weekly Analytics Card */}
        <div className="lg:col-span-2 bg-gradient-to-br from-primary/10 via-slate-950 to-transparent p-10 rounded-[48px] border border-white/5 flex flex-col md:flex-row items-center justify-between gap-8">
          
          <div className="space-y-6 flex-1">
            <div className="inline-flex bg-primary/20 p-3 rounded-2xl text-primary">
              <CloudLightning className="w-5 h-5 animate-bounce" />
            </div>

            <div className="space-y-2">
              <h3 className="text-2xl font-black text-white italic uppercase tracking-tighter">Resumen Semanal de Alertas</h3>
              <p className="text-xs text-white/50 font-bold leading-relaxed max-w-md uppercase">
                Has resuelto satisfactoriamente el 85% de las alertas críticas de esta semana. La eficiencia de cierre promedia 1.2 hrs.
              </p>
            </div>

            <div className="flex gap-4">
              <div className="bg-white/5 p-4 rounded-2xl flex-1 border border-white/5">
                <span className="text-[10px] font-black text-white/30 uppercase tracking-widest block mb-1">Promedio Cierre</span>
                <span className="text-base font-black italic text-white uppercase">1.2 Horas</span>
              </div>
              <div className="bg-white/5 p-4 rounded-2xl flex-1 border border-white/5">
                <span className="text-[10px] font-black text-white/30 uppercase tracking-widest block mb-1">Críticas Hoy</span>
                <span className="text-base font-black italic text-rose-400 uppercase">0 Alertas</span>
              </div>
            </div>
          </div>

          {/* Simple Visual Mini histogram preview graph */}
          <div className="w-[180px] h-[140px] flex items-end justify-between p-4 bg-white/[0.02] border border-white/5 rounded-3xl gap-2.5 relative">
            <div className="absolute top-3 left-4 text-[8px] uppercase tracking-widest font-black text-white/20">Cierres L-V</div>
            <div className="w-full bg-primary/20 h-1/2 rounded-lg"></div>
            <div className="w-full bg-primary/40 h-3/4 rounded-lg"></div>
            <div className="w-full bg-primary/60 h-2/3 rounded-lg"></div>
            <div className="w-full bg-primary h-full rounded-lg relative overflow-hidden">
              <div className="absolute bottom-0 inset-x-0 bg-white/20 h-1 w-full"></div>
            </div>
            <div className="w-full bg-primary/30 h-1/3 rounded-lg"></div>
          </div>

        </div>

        {/* Integration Live status channel sources list */}
        <div className="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between">
          <div>
            <h4 className="text-sm font-black uppercase tracking-widest text-white/50 mb-6 flex items-center gap-2">
              <Server className="w-4 h-4 text-primary" /> Fuentes de Datos Vinculadas
            </h4>

            <div className="space-y-4 font-bold text-xs uppercase tracking-wide">
              {sources.map((src) => (
                <div key={src.id} className="flex justify-between items-center bg-white/[0.01] p-3 rounded-xl border border-white/5">
                  <span className="text-white/75">{src.name}</span>
                  <div className="flex items-center gap-2">
                    <span className={`w-2.5 h-2.5 rounded-full ${
                      src.status === 'online' ? 'bg-emerald-500 animate-pulse' :
                      src.status === 'syncing' ? 'bg-amber-400 animate-spin border' :
                      'bg-rose-500'
                    }`}></span>
                    <span className="text-[9px] text-white/30 uppercase tracking-wider">{src.status}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <button 
            onClick={() => showToast("Diagnosticando salud del concentrador de transacciones de ConstructPro")}
            className="w-full mt-8 py-4 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest text-[#003ec7] dark:text-primary hover:bg-[#003ec7]/10 transition-colors"
          >
            Ver Estado del Sistema
          </button>
        </div>

      </div>

      {/* Config setup Modal representation overlay */}
      <AnimatePresence>
        {showConfigModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowConfigModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-md glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Canales de Alerta</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Elige cómo deseas enterarte de eventos financieros y de logística en ConstructPro</p>

              <form onSubmit={handleSaveConfig} className="space-y-6">
                
                {/* Email setup toggle */}
                <div className="flex justify-between items-center p-4 bg-white/5 rounded-2xl border border-white/5">
                  <div className="flex items-center gap-3">
                    <Mail className="w-5 h-5 text-primary" />
                    <div>
                      <p className="font-extrabold text-xs uppercase tracking-wider text-white">Alertas por Correo</p>
                      <p className="text-[10px] font-bold text-white/30 uppercase mt-0.5">Reportes diarios e hitos financieros</p>
                    </div>
                  </div>
                  <input 
                    type="checkbox"
                    checked={emailAlerts}
                    onChange={(e) => setEmailAlerts(e.target.checked)}
                    className="w-5 h-5 rounded border-white/10 bg-white/5 text-primary focus:ring-primary/20 focus:ring-offset-0"
                  />
                </div>

                {/* Push alerts setup toggle */}
                <div className="flex justify-between items-center p-4 bg-white/5 rounded-2xl border border-white/5">
                  <div className="flex items-center gap-3">
                    <Smartphone className="w-5 h-5 text-primary" />
                    <div>
                      <p className="font-extrabold text-xs uppercase tracking-wider text-white">Notificaciones Push</p>
                      <p className="text-[10px] font-bold text-white/30 uppercase mt-0.5">Incidentes críticos en obra pesada</p>
                    </div>
                  </div>
                  <input 
                    type="checkbox"
                    checked={pushAlerts}
                    onChange={(e) => setPushAlerts(e.target.checked)}
                    className="w-5 h-5 rounded border-white/10 bg-white/5 text-primary focus:ring-primary/20 focus:ring-offset-0"
                  />
                </div>

                {/* SMS setup toggle */}
                <div className="flex justify-between items-center p-4 bg-white/5 rounded-2xl border border-white/5">
                  <div className="flex items-center gap-3">
                    <MessageSquare className="w-5 h-5 text-primary" />
                    <div>
                      <p className="font-extrabold text-xs uppercase tracking-wider text-white">Servicios de Mensajería SMS</p>
                      <p className="text-[10px] font-bold text-white/30 uppercase mt-0.5">Sueldos y nóminas procesadas (Beta)</p>
                    </div>
                  </div>
                  <input 
                    type="checkbox"
                    checked={smsAlerts}
                    onChange={(e) => setSmsAlerts(e.target.checked)}
                    className="w-5 h-5 rounded border-white/10 bg-white/5 text-primary focus:ring-primary/20 focus:ring-offset-0"
                  />
                </div>

                {/* Submit action CTA */}
                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all font-sans"
                  >
                    Guardar Configuración
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowConfigModal(false)}
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
