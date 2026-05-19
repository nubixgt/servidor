import { Construction, Truck, Settings, History, MapPin, CheckCircle2, AlertTriangle, ArrowRightLeft, PackageCheck, Wrench } from "lucide-react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

export default function MachineryStatus() {
  const [selectedMachine, setSelectedMachine] = useState<any>(null);

  const machinery = [
    { id: "MQ-882", name: "Excavadora Volvo EC220D", status: "En Bodega", lastLog: "Ayer, 4:00 PM", code: "EXT-01", location: "Bodega Central", health: 95 },
    { id: "MQ-124", name: "Grúa Torre Potain MCT 205", status: "En Sitio", lastLog: "Hoy, 8:15 AM", code: "CRN-05", location: "Skyline Tower", health: 88 },
    { id: "MQ-451", name: "Cargador Frontal CAT 950K", status: "En Servicio", lastLog: "Hace 2 días", code: "LDR-02", location: "Taller Externo", health: 65 },
    { id: "MQ-302", name: "Camión Volteo Hino 500", status: "Transbordo", lastLog: "Hoy, 10:30 AM", code: "TRK-12", location: "Ruta CR-10", health: 92 },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
      <div className="space-y-3">
        <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Control de Estatus</h2>
        <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Monitoreo operativo de flota y herramientas</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {[
          { label: "En Bodega", count: 42, icon: PackageCheck, color: "primary" },
          { label: "En Operación", count: 86, icon: Construction, color: "primary" },
          { label: "En Mantenimiento", count: 8, icon: Wrench, color: "orange-500" },
          { label: "Fuera de Servicio", count: 3, icon: AlertTriangle, color: "tertiary" },
        ].map((stat, i) => (
          <div key={i} className="glass-card p-8 rounded-[32px] border border-white/5">
            <div className={`w-10 h-10 rounded-xl bg-${stat.color === 'primary' ? 'primary' : stat.color}/20 flex items-center justify-center text-${stat.color === 'primary' ? 'primary' : stat.color} mb-6`}>
              <stat.icon className="w-5 h-5" />
            </div>
            <p className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-1">{stat.label}</p>
            <h4 className="text-3xl font-black text-white italic transition-all group-hover:scale-105">{stat.count}</h4>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-6">
        {machinery.map((mq, i) => (
          <motion.div 
            key={i}
            initial={{ opacity: 0, x: -20 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: i * 0.1 }}
            className="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-8 group hover:border-primary/30 transition-all cursor-pointer"
            onClick={() => setSelectedMachine(mq)}
          >
            <div className="flex items-center gap-8">
              <div className="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                <Truck className="w-8 h-8" />
              </div>
              <div>
                <div className="flex items-center gap-3">
                  <h3 className="text-2xl font-black text-white italic uppercase tracking-tighter">{mq.name}</h3>
                  <span className="px-3 py-1 bg-white/5 rounded-lg text-[9px] font-black text-white/40 uppercase tracking-widest">{mq.code}</span>
                </div>
                <div className="flex items-center gap-6 mt-2">
                  <div className="flex items-center gap-2 text-white/30">
                    <MapPin className="w-4 h-4 text-primary" />
                    <span className="text-xs font-bold">{mq.location}</span>
                  </div>
                  <div className="flex items-center gap-2 text-white/30">
                    <History className="w-4 h-4 text-primary" />
                    <span className="text-xs font-bold">{mq.lastLog}</span>
                  </div>
                </div>
              </div>
            </div>

            <div className="flex items-center gap-12">
              <div className="text-right">
                <p className="text-[10px] font-black text-white/20 uppercase tracking-widest mb-2 text-center md:text-right">Salud de Equipo</p>
                <div className="flex items-center gap-3">
                  <div className="w-24 h-1.5 bg-white/5 rounded-full overflow-hidden">
                    <div className="h-full bg-primary" style={{ width: `${mq.health}%` }}></div>
                  </div>
                  <span className="text-sm font-black text-white italic">{mq.health}%</span>
                </div>
              </div>

              <div className="flex flex-col items-end">
                <span className={`px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest border ${
                  mq.status === 'En Bodega' ? 'bg-primary/20 text-primary border-primary/20' : 
                  mq.status === 'En Servicio' ? 'bg-orange-500/20 text-orange-400 border-orange-500/20' :
                  'bg-white/5 text-white/40 border-white/10'
                }`}>
                  {mq.status}
                </span>
                <button className="mt-4 text-[10px] font-black text-primary hover:underline uppercase tracking-[0.2em] flex items-center gap-2 group/btn">
                  Actualizar Registro <ArrowRightLeft className="w-3 h-3 group-hover/btn:translate-x-1 transition-transform" />
                </button>
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      <AnimatePresence>
        {selectedMachine && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setSelectedMachine(null)}
              className="absolute inset-0 bg-black/80 backdrop-blur-md"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.9 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.9 }}
              className="relative w-full max-w-2xl glass-card rounded-[56px] p-12 border border-white/10 shadow-[0_0_100px_rgba(99,102,241,0.15)] bg-slate-950"
            >
              <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter mb-2">Reportar Cambio de Estado</h2>
              <p className="text-white/40 font-bold uppercase tracking-widest text-xs mb-10">{selectedMachine.name} • {selectedMachine.code}</p>
              
              <div className="grid grid-cols-2 gap-4 mb-8">
                {[
                  { id: 'bodega', label: 'Retorno a Bodega', icon: PackageCheck },
                  { id: 'servicio', label: 'Envío a Servicio', icon: Wrench },
                  { id: 'sitio', label: 'Asignar a Sitio', icon: MapPin },
                  { id: 'baja', label: 'Reportar Avería', icon: AlertTriangle },
                ].map((action) => (
                  <button 
                    key={action.id}
                    className="p-6 bg-white/5 border border-white/5 rounded-3xl flex flex-col items-center gap-4 hover:border-primary/50 hover:bg-primary/10 transition-all group"
                  >
                    <action.icon className="w-8 h-8 text-white/40 group-hover:text-primary transition-colors" />
                    <span className="text-[10px] font-black text-white uppercase tracking-widest">{action.label}</span>
                  </button>
                ))}
              </div>

              <div className="space-y-6">
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/20 uppercase tracking-widest ml-4">Observaciones Técnicas</label>
                  <textarea 
                    className="w-full glass-input rounded-2xl p-6 text-sm outline-none focus:ring-2 focus:ring-primary/40 min-h-[120px]"
                    placeholder="Describe el estado técnico o motivos del movimiento..."
                  ></textarea>
                </div>
                
                <div className="flex gap-4">
                  <button className="flex-1 glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl">Confirmar Movimiento</button>
                  <button 
                    onClick={() => setSelectedMachine(null)}
                    className="px-8 py-5 rounded-2xl bg-white/5 border border-white/10 text-white/40 font-black text-sm uppercase tracking-widest"
                  >
                    Cancelar
                  </button>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
