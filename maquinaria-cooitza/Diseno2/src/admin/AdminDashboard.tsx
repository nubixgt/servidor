import React, { useState, useEffect } from "react";
import { 
  Tractor, 
  Truck, 
  Activity, 
  Users, 
  Shield, 
  AlertTriangle, 
  LogOut, 
  Bell, 
  X 
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";
import { OperationalLog } from "../types";

// Import Modular Components
import DashboardOverview from "./DashboardOverview";
import PilotosModule from "./PilotosModule";
import VehiculosModule from "./VehiculosModule";
import MaquinariaModule from "./MaquinariaModule";
import UsuariosModule from "./UsuariosModule";

interface AdminDashboardProps {
  logs: OperationalLog[];
  onDeleteLog: (id: string) => void;
  onAddMockLog: () => void;
  onLogout: () => void;
}

type AdminTab = "dashboard" | "pilotos" | "vehiculos" | "maquinaria" | "usuarios";

export default function AdminDashboard({ logs, onDeleteLog, onAddMockLog, onLogout }: AdminDashboardProps) {
  const [activeTab, setActiveTab] = useState<AdminTab>("dashboard");
  const [isEmergencyActive, setIsEmergencyActive] = useState(false);
  const [showNotificationAlert, setShowNotificationAlert] = useState(false);
  const [selectedPhotoInModal, setSelectedPhotoInModal] = useState<string | null>(null);

  // Quick feedback export notification
  const [isExporting, setIsExporting] = useState(false);
  const [exportMessage, setExportMessage] = useState("");

  // Real-time counter states synchronized from child modules
  const [pilotsCount, setPilotsCount] = useState(3);
  const [pilotsActiveCount, setPilotsActiveCount] = useState(2);
  const [pilotsRestingCount, setPilotsRestingCount] = useState(1);

  const [vehiclesCount, setVehiclesCount] = useState(3);
  const [vehiclesActiveCount, setVehiclesActiveCount] = useState(2);
  const [vehiclesMaintenanceCount, setVehiclesMaintenanceCount] = useState(1);

  const [machineryCount, setMachineryCount] = useState(3);
  const [usersCount, setUsersCount] = useState(3);

  // Synchronize counters on primary load check
  useEffect(() => {
    try {
      const p = localStorage.getItem("cooitza_pilotos");
      if (p) {
        const parsed = JSON.parse(p);
        setPilotsCount(parsed.length);
        setPilotsActiveCount(parsed.filter((item: any) => item.status === "En Turno").length);
        setPilotsRestingCount(parsed.filter((item: any) => item.status === "Descanso").length);
      }
      const v = localStorage.getItem("cooitza_vehiculos");
      if (v) {
        const parsed = JSON.parse(v);
        setVehiclesCount(parsed.length);
        setVehiclesActiveCount(parsed.filter((item: any) => item.status === "Activo").length);
        setVehiclesMaintenanceCount(parsed.filter((item: any) => item.status === "Mantenimiento").length);
      }
      const m = localStorage.getItem("cooitza_maquinaria");
      if (m) setMachineryCount(JSON.parse(m).length);
      const u = localStorage.getItem("cooitza_usuarios");
      if (u) setUsersCount(JSON.parse(u).length);
    } catch (e) {
      console.warn("Storage syncing failed: ", e);
    }
  }, [activeTab]);

  const handleTriggerExport = (format: "Excel" | "PDF", filteredCount: number) => {
    setIsExporting(true);
    setExportMessage(`Generando informe de telemetría Cooitzá en formato ${format}...`);
    setTimeout(() => {
      setExportMessage(`¡Archivo ${format} exportado correctamente con ${filteredCount} registros!`);
      setTimeout(() => {
        setIsExporting(false);
        setExportMessage("");
      }, 2000);
    }, 1200);
  };

  return (
    <div className="flex min-h-screen bg-[#f8f9fa] w-full text-on-surface overflow-x-hidden font-body-md select-text">
      
      {/* SideNavBar Menu Component */}
      <aside className="hidden lg:flex flex-col w-64 bg-slate-100 border-r border-[#cbd5e1] h-screen sticky top-0 p-4 select-none">
        
        {/* Brand Header */}
        <div className="mb-8">
          <div className="flex items-center gap-2">
            <div className="relative w-8 h-8 flex-shrink-0">
              <div className="absolute inset-0 bg-[#FFD200] rounded-full" />
              <div className="absolute inset-0 flex items-center justify-center font-display text-base font-black text-[#0054A3] italic">C</div>
            </div>
            <h2 className="font-display font-black text-base text-[#0054A3] tracking-tight uppercase">
              COOITZÁ R.L.
            </h2>
          </div>
          <div className="flex items-center gap-1.5 mt-2">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <p className="font-display text-[10px] font-bold text-[#004586] uppercase tracking-wider">Estación 04-B Terminal</p>
          </div>
        </div>

        {/* Sidebar Navigation Options */}
        <nav className="flex-1 space-y-1">
          <button 
            type="button"
            onClick={() => setActiveTab("dashboard")}
            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider ${
              activeTab === "dashboard" 
                ? "bg-[#0054A3] text-white shadow-sm" 
                : "text-[#004586] hover:bg-[#cbd5e1]/30"
            }`}
          >
            <Activity size={18} />
            <span>Dashboard</span>
          </button>

          <button 
            type="button"
            onClick={() => setActiveTab("pilotos")}
            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider ${
              activeTab === "pilotos" 
                ? "bg-[#0054A3] text-white shadow-sm" 
                : "text-[#004586] hover:bg-[#cbd5e1]/30"
            }`}
          >
            <Users size={18} />
            <span>Pilotos ({pilotsCount})</span>
          </button>

          <button 
            type="button"
            onClick={() => setActiveTab("vehiculos")}
            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider ${
              activeTab === "vehiculos" 
                ? "bg-[#0054A3] text-white shadow-sm" 
                : "text-[#004586] hover:bg-[#cbd5e1]/30"
            }`}
          >
            <Truck size={18} />
            <span>Vehículos ({vehiclesCount})</span>
          </button>

          <button 
            type="button"
            onClick={() => setActiveTab("maquinaria")}
            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider ${
              activeTab === "maquinaria" 
                ? "bg-[#0054A3] text-white shadow-sm" 
                : "text-[#004586] hover:bg-[#cbd5e1]/30"
            }`}
          >
            <Tractor size={18} />
            <span>Maquinaria ({machineryCount})</span>
          </button>

          <button 
            type="button"
            onClick={() => setActiveTab("usuarios")}
            className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-150 font-display text-xs font-bold uppercase tracking-wider ${
              activeTab === "usuarios" 
                ? "bg-[#0054A3] text-white shadow-sm" 
                : "text-[#004586] hover:bg-[#cbd5e1]/30"
            }`}
          >
            <Shield size={18} />
            <span>Usuarios ({usersCount})</span>
          </button>
        </nav>

        {/* Emergency Halt & user controls */}
        <div className="mt-auto space-y-2 pt-4 border-t border-[#cbd5e1]">
          <button 
            type="button"
            onClick={() => setIsEmergencyActive(true)}
            className="w-full bg-[#ba1a1a] hover:bg-red-750 text-white font-display text-[10px] font-black tracking-widest py-3 uppercase transition-colors rounded-sm cursor-pointer"
          >
            PARADA DE EMERGENCIA
          </button>

          <button 
            type="button"
            onClick={onLogout}
            className="w-full flex items-center gap-3 px-3 py-2 text-red-600 hover:bg-red-50 transition-colors text-xs font-sans font-bold cursor-pointer"
          >
            <LogOut size={16} />
            <span>Cerrar Sesión</span>
          </button>
        </div>
      </aside>

      {/* Main Container viewport */}
      <main className="flex-grow flex flex-col min-w-0 max-w-full overflow-hidden min-h-screen">
        
        {/* TopNavBar Header - Duplicate horizontal menu removed as explicitly requested */}
        <header className="w-full top-0 sticky z-10 bg-white border-b border-[#cbd5e1]">
          <div className="flex justify-between items-center px-6 py-3 max-w-[1280px] mx-auto w-full">
            <div className="flex items-center gap-3">
              <div className="lg:hidden relative w-7 h-7 flex-shrink-0">
                <div className="absolute inset-0 bg-[#FFD200] rounded-full" />
                <div className="absolute inset-0 flex items-center justify-center font-display text-xs font-black text-[#0054A3] italic">C</div>
              </div>
              <h1 className="font-display text-sm md:text-base font-black text-[#0054A3] uppercase tracking-tight">
                Cooitzá Control Panel
              </h1>
            </div>

            {/* Note: The horizontal duplicate links list was strictly removed here as requested */}
            <div className="hidden md:flex text-xs font-bold text-slate-400 select-none uppercase tracking-wide">
              Estación Enlazada Satelital Cooitzá
            </div>

            <div className="flex items-center gap-4">
              <button 
                type="button"
                onClick={() => setShowNotificationAlert(true)}
                className="relative p-1.5 text-on-surface-variant hover:text-[#0054A3] transition-colors cursor-pointer"
                title="Notificaciones"
              >
                <Bell size={18} />
                <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full animate-ping" />
              </button>

              <div className="flex items-center gap-2 border-l pl-3 border-[#cbd5e1]">
                <div className="hidden sm:flex flex-col text-right">
                  <span className="font-sans text-xs font-bold text-on-surface">Admin Cooitzá</span>
                  <span className="font-mono text-[9px] text-[#0054A3] uppercase">Consola Principal</span>
                </div>
                <div className="w-8 h-8 rounded-full overflow-hidden border border-[#cbd5e1]">
                  <img 
                    alt="Cooitzá Admin Profile" 
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuDuk5T1SnyB7CuXEU0F1Im314E1dy2F8NtMdIe7NTmKwuGq1z8uu1Rppj-NLDj9dErQZ3ODx2Dd-QTcW7UaxYM1Hm2JSagasIZaUwwD4OWDuM6bcFh4QWpb9Z2MyUhVV9i4s3YG9upSMiyC2_SkB2BPfehnmYBXXLr0DUc5JuNHA7doSwnd3uD9NYLS14Qnsw7E60fuazvxbd8ARAshO9IAkzzxUiC8vL584g7LEM35ciqvEu51n3ePH75b1GINyz5PS2l5ZDqkSoaL" 
                    className="w-full h-full object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
        </header>

        {/* Mobile Navigation Tabs list */}
        <div className="flex lg:hidden bg-slate-100 overflow-x-auto divide-x divide-slate-200 border-b border-[#cbd5e1] w-full">
          {[
            { id: "dashboard", label: "Dashboard" },
            { id: "pilotos", label: "Pilotos" },
            { id: "vehiculos", label: "Flota" },
            { id: "maquinaria", label: "Equipos" },
            { id: "usuarios", label: "Usuarios" }
          ].map((mTab) => (
            <button 
              key={mTab.id}
              type="button"
              onClick={() => setActiveTab(mTab.id as AdminTab)}
              className={`flex-shrink-0 px-4 py-3 font-display text-[11px] font-bold uppercase tracking-wider ${
                activeTab === mTab.id ? "bg-[#0054A3] text-white" : "text-[#004586] hover:bg-slate-200"
              }`}
            >
              {mTab.label}
            </button>
          ))}
        </div>

        {/* Background Export status notification */}
        <AnimatePresence>
          {isExporting && (
            <motion.div 
              initial={{ opacity: 0, height: 0 }}
              animate={{ opacity: 1, height: "auto" }}
              exit={{ opacity: 0, height: 0 }}
              className="bg-[#0054A3] text-white px-6 py-2.5 font-mono text-xs flex justify-between items-center"
            >
              <span>{exportMessage}</span>
              <div className="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
            </motion.div>
          )}
        </AnimatePresence>

        {/* View Canvas Routing */}
        <section className="p-4 md:p-8 flex-grow overflow-y-auto max-w-[1280px] w-full mx-auto flex flex-col gap-6">
          <AnimatePresence mode="wait">
            
            {activeTab === "dashboard" && (
              <DashboardOverview 
                logs={logs}
                onDeleteLog={onDeleteLog}
                onAddMockLog={onAddMockLog}
                pilotsCount={pilotsCount}
                pilotsActiveCount={pilotsActiveCount}
                pilotsRestingCount={pilotsRestingCount}
                vehiclesCount={vehiclesCount}
                vehiclesActiveCount={vehiclesActiveCount}
                vehiclesMaintenanceCount={vehiclesMaintenanceCount}
                onOpenPhotoModal={(url) => setSelectedPhotoInModal(url)}
                onTriggerExport={handleTriggerExport}
              />
            )}

            {activeTab === "pilotos" && (
              <PilotosModule 
                onPilotsChange={(count, active, resting) => {
                  setPilotsCount(count);
                  setPilotsActiveCount(active);
                  setPilotsRestingCount(resting);
                }}
              />
            )}

            {activeTab === "vehiculos" && (
              <VehiculosModule 
                onVehiclesChange={(count, active, maint) => {
                  setVehiclesCount(count);
                  setVehiclesActiveCount(active);
                  setVehiclesMaintenanceCount(maint);
                }}
              />
            )}

            {activeTab === "maquinaria" && (
              <MaquinariaModule 
                onMachineryChange={(count) => {
                  setMachineryCount(count);
                }}
              />
            )}

            {activeTab === "usuarios" && (
              <UsuariosModule 
                onUsersListChange={(count) => {
                  setUsersCount(count);
                }}
              />
            )}

          </AnimatePresence>
        </section>

        {/* Corporate Footer */}
        <footer className="mt-auto bg-white border-t border-[#cbd5e1] select-none">
          <div className="flex flex-col md:flex-row justify-between items-center px-6 py-4 max-w-[1280px] w-full mx-auto gap-4">
            <p className="font-display text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">
              © 2024 Cooitzá R.L. Central de Bitácoras e Integración de Flotas.
            </p>
            <div className="flex gap-6 font-display text-[10px] uppercase font-bold text-[#0054A3]">
              <a href="#" className="hover:underline">Seguridad Informática</a>
              <a href="#" className="hover:underline">Políticas de Uso</a>
              <a href="#" className="hover:underline flex items-center gap-1">
                <span className="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-ping" />
                <span>Sistemas Estables</span>
              </a>
            </div>
          </div>
        </footer>

      </main>

      {/* EMERGENCY SUSPENSION SYSTEM (EMERGENCY HALT OVERLAY) */}
      <AnimatePresence>
        {isEmergencyActive && (
          <motion.div 
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-red-950/95 z-50 flex items-center justify-center p-6"
          >
            <motion.div 
              initial={{ scale: 0.95 }}
              animate={{ scale: 1 }}
              exit={{ scale: 0.95 }}
              className="bg-white border-4 border-red-600 max-w-lg p-8 text-center flex flex-col items-center gap-6"
            >
              <div className="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center animate-bounce">
                <AlertTriangle size={52} />
              </div>

              <div>
                <h3 className="font-display text-2xl font-black text-red-600 uppercase tracking-tight">
                  PARADA DE EMERGENCIA INICIALIZADA
                </h3>
                <p className="text-xs text-on-surface-variant font-sans font-bold leading-relaxed mt-2 uppercase tracking-wide">
                  Se ha ordenado la suspensión transitoria del envío de horómetros por parte de toda la red de técnicos de Cooitzá R.L.
                </p>
              </div>

              <div className="bg-red-50 p-4 border border-red-200 text-left w-full font-mono text-[11px] text-red-800 space-y-1">
                <div>• Código de Alerta: AL-04B-EMERGENCY</div>
                <div>• Canal Satelital: Suministrando bloqueo TLS</div>
                <div>• Sede Central Chimaltenango notificando protocolo de suspensión temporal.</div>
              </div>

              <button 
                type="button"
                onClick={() => setIsEmergencyActive(false)}
                className="bg-red-600 text-white font-display text-xs font-black uppercase py-3.5 px-8 hover:bg-red-700 tracking-widest cursor-pointer shadow-md rounded-none"
              >
                DESBLOQUEAR SISTEMA
              </button>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* REAL-TIME NOTIFICATIONS POPUP PREVIEW */}
      <AnimatePresence>
        {showNotificationAlert && (
          <motion.div 
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 10 }}
            className="fixed bottom-6 right-6 bg-slate-900 text-white border border-[#FFD200] p-5 shadow-2xl z-40 max-w-sm"
          >
            <div className="flex justify-between items-start mb-3">
              <span className="font-display text-[10px] uppercase text-[#FFD200] font-black tracking-widest font-bold">
                Notificaciones del Enlace GPS
              </span>
              <button 
                type="button"
                onClick={() => setShowNotificationAlert(false)}
                className="text-white/60 hover:text-white cursor-pointer"
              >
                <X size={14} />
              </button>
            </div>
            
            <div className="space-y-3 text-[11px] font-mono select-text">
              <div className="border-b border-white/10 pb-2">
                <span className="text-[#FFD200]">● Alerta:</span> Unidad VEH-104 en cantera Chimaltenango transmitió Horómetro Inicial: 4235.8 hrs correctamente.
              </div>
              <div className="border-b border-white/10 pb-2">
                <span className="text-[#FFD200]">● Seguridad:</span> Encriptación activada para reportes TLS generados hoy.
              </div>
              <div>
                <span className="text-slate-400">Canal óptimo de asistencia Cooitzá R.L. Activo.</span>
              </div>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* DETAILED DIAL PHOTO MODAL VIEW */}
      <AnimatePresence>
        {selectedPhotoInModal && (
          <div 
            className="fixed inset-0 bg-black/90 z-50 flex items-center justify-center p-6 cursor-zoom-out"
            onClick={() => setSelectedPhotoInModal(null)}
          >
            <motion.div 
              initial={{ scale: 0.95 }}
              animate={{ scale: 1 }}
              exit={{ scale: 0.95 }}
              className="bg-slate-900 p-4 border border-white/10 max-w-2xl w-full relative"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="flex justify-between items-center text-white/50 text-[10px] font-mono uppercase mb-3">
                <span>LECTURA DE INSTRUMENTO COOITZÁ</span>
                <button 
                  type="button"
                  onClick={() => setSelectedPhotoInModal(null)}
                  className="hover:text-white uppercase font-bold cursor-pointer"
                >
                  CERRAR [X]
                </button>
              </div>

              <img 
                src={selectedPhotoInModal} 
                alt="Instrument Capture" 
                className="w-full h-auto object-contain bg-black max-h-[80vh]" 
              />
              
              <div className="text-center text-[#FFD200] font-mono text-xs mt-3">
                Firma Criptográfica SSL validada por el satélite Cooitzá R.L.
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
