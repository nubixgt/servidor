import { TrendingUp, TrendingDown, Construction, AlertTriangle, MoreVertical, MapPin, Calendar, Layers, List, Boxes, Mountain, X, User, Activity, Gauge, ShieldCheck, History } from "lucide-react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

export default function Machinery() {
  const [activeTab, setActiveTab] = useState("machinery");
  const [selectedMachine, setSelectedMachine] = useState<any>(null);

  const metrics = [
    { label: "Valuación de Inventario", value: "Q2.4M", trend: "+12% vs mes anterior", icon: TrendingUp, color: "text-primary" },
    { label: "Tasa de Consumo", value: "840 kg/día", trend: "-4% brecha eficiencia", icon: TrendingDown, color: "text-tertiary" },
    { label: "Maquinaria Activa", value: "42 / 48", percentage: 88, color: "text-primary" },
    { label: "Alertas Críticas", value: "03", trend: "Acción urgente requerida", icon: AlertTriangle, color: "text-error" },
  ];

  const machinery = [
    { 
      name: "CAT 320 GC", 
      type: "Excavadora • EX-042", 
      fuel: 78, 
      location: "Sector 7", 
      maint: "en 12d", 
      status: "Operativa", 
      statusColor: "green", 
      img: "https://lh3.googleusercontent.com/aida-public/AB6AXuCUyVqpheG5Xp0ccizgbsaN-FRTvOR-DITSCeVTA7EoZRxKtN8BaGvLEm4K2bZdWrJ5tOAeWz5F_bGpsfg_a9tnlAH1qrMMIDYM7e9QvAyIYcQ76WYoG6awOnUrgmbJNZkq4RFbBpxXhR2FjlbvHeUwUddgZEsXV5LWdlxVH4WEC-N6Em-53KeC_gtEo8RjRFSgI_NA0ZN3iG1QW9rmfy6i-SVhDrHc1FuZUq_0tUkg6xbNHYHbNFqVtFWMDwDNbOqOo1Ltim2__WE",
      specs: { hp: "145 HP", peso: "21,900 kg", profundidad: "6.72 m" },
      operator: "Roberto Jimenez",
      lastService: "Oct 12, 2023",
      history: [
        { date: "Oct 12", event: "Servicio Estándar", status: "Aprobado" },
        { date: "Ago 05", event: "Ajuste de Oruga", status: "Aprobado" },
        { date: "Jun 14", event: "Fuga Hidráulica", status: "Crítico" },
      ]
    },
    { 
      name: "Volvo FMX", 
      type: "Mixer de Concreto • MX-109", 
      fuel: 42, 
      location: "Depósito Base", 
      maint: "vencido", 
      status: "En Espera", 
      statusColor: "orange", 
      img: "https://lh3.googleusercontent.com/aida-public/AB6AXuCZkiAcuAATincPGGXI-CGRLBqB-HRPzN88_PZBKIi1keO0kHx-invtkIlWZnzGIAKaXVDxfqVrn7Tek4VguF8jzvyeu9vG_XK3yF8xWmVSYU3jWmkhp4lZ17esd5DrFgZGwEpAYapjCeGZVNRx1A6LSSqe4yqCNvD6wHfyfyad9KHzHTqnlADRU2t9bJqq8aEMmKYKJ4B3uSlV7d3G-Wfhdv_90cTFZaYBuVxIwHqQ5bASyAjcOTMFteOTIYqgZLcMz5BsGHrEwpM",
      specs: { hp: "420 HP", carga: "32,000 kg", capacidad: "12 m³" },
      operator: "Carla Martinez",
      lastService: "Sep 28, 2023",
      history: [
        { date: "Sep 28", event: "Inspección de Tambor", status: "Aprobado" },
        { date: "Jul 10", event: "Cambio de Aceite", status: "Aprobado" },
      ]
    },
    { 
      name: "Liebherr 280", 
      type: "Grúa Torre • TC-001", 
      fuel: 65, 
      location: "Sector 1", 
      maint: "en 45d", 
      status: "Activa", 
      statusColor: "green", 
      img: "https://lh3.googleusercontent.com/aida-public/AB6AXuDp9kl31qCayEdKXTEX0_x6wXDhGwwFK5wFB0QhP-zAOPD52lUXNxjBMMlVOkf6haDAzp8R9CUlrsXzck7cymtrCrHHYO40SPRWnN36XkWOL0ZiFeN4cGxpHlszuQXboVVhhn5Sm3qBxXAqZ4R0mTtDUd1yaMMF7zydLdDlUXZIqozMpJ3jHQEdiZLTLy4ku1j-2WQQNXoHTthDwWrkxoPtN8T8H_ORG9RneM_x0GRcfB9nUkLOfx7Lre-cuD0VNxAyLafYI6CPIKg",
      specs: { hp: "Eléctrico (90kW)", altura: "84 m", cargaMax: "12,000 kg" },
      operator: "David Chen",
      lastService: "Ene 15, 2024",
      history: [
        { date: "Ene 15", event: "Certificación Anual", status: "Aprobado" },
        { date: "Nov 02", event: "Tensado de Cables", status: "Aprobado" },
      ]
    },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12">
      {/* Metrics Section */}
      <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {metrics.map((metric, i) => (
          <motion.div
            key={i}
            whileHover={{ y: -6, scale: 1.02 }}
            className="glass-card p-8 rounded-3xl flex flex-col justify-between h-44 border border-white/5 transition-all cursor-pointer group"
          >
            <div>
              <p className="text-white/40 text-[11px] font-bold uppercase tracking-[0.2em] mb-4">{metric.label}</p>
              <h3 className={`text-3xl font-bold ${metric.color === "text-error" ? "text-tertiary" : "text-white"}`}>{metric.value}</h3>
            </div>
            {metric.percentage ? (
              <div className="w-full bg-white/5 h-2 rounded-full mt-6 overflow-hidden p-[1px]">
                <motion.div 
                  initial={{ width: 0 }}
                  animate={{ width: `${metric.percentage}%` }}
                  className="bg-primary h-full rounded-full shadow-[0_0_10px_#6366f1]"
                ></motion.div>
              </div>
            ) : (
              <div className={`flex items-center gap-2 mt-6 ${metric.color} bg-white/5 px-3 py-1.5 rounded-xl w-fit border border-white/5`}>
                {metric.icon && <metric.icon className="w-4 h-4" />}
                <span className="text-[10px] font-bold uppercase tracking-wider">{metric.trend}</span>
              </div>
            )}
          </motion.div>
        ))}
      </section>

      {/* Tabs */}
      <section>
        <div className="flex gap-12 border-b border-white/10 relative">
          {["machinery", "inventory"].map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`pb-6 text-xl font-bold transition-all relative ${
                activeTab === tab ? "text-white" : "text-white/40 hover:text-white/60"
              }`}
            >
              {tab === "machinery" ? "Maquinaria Pesada" : "Inventario de Materiales"}
              {activeTab === tab && (
                <motion.div layoutId="tab-underline" className="absolute bottom-0 left-0 w-full h-1.5 bg-primary rounded-t-full shadow-[0_0_15px_#6366f1]" />
              )}
            </button>
          ))}
        </div>
      </section>

      {/* Machinery Content */}
      <AnimatePresence mode="wait">
        {activeTab === "machinery" ? (
          <motion.section
            key="machinery"
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.98 }}
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"
          >
            {machinery.map((m, i) => (
              <motion.div 
                key={i} 
                onClick={() => setSelectedMachine(m)}
                className="glass-card rounded-[40px] overflow-hidden group hover:-translate-y-2 transition-all duration-500 border border-white/10 cursor-pointer"
              >
                <div className="h-56 relative overflow-hidden">
                  <img src={m.img} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" alt={m.name} referrerPolicy="no-referrer" />
                  <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                  <div className="absolute top-6 right-6 px-4 py-2 backdrop-blur-xl bg-black/40 rounded-2xl text-[10px] font-bold uppercase tracking-[0.2em] flex items-center gap-2.5 border border-white/20 shadow-xl">
                    <span className={`w-2.5 h-2.5 rounded-full ${m.statusColor === "green" ? "bg-primary shadow-[0_0_10px_#6366f1]" : "bg-orange-500 shadow-[0_0_10px_#f97316]"}`}></span>
                    {m.status}
                  </div>
                </div>
                <div className="p-10 relative">
                  <div className="flex justify-between items-start mb-8">
                    <div>
                      <h4 className="text-2xl font-bold text-white tracking-tight">{m.name}</h4>
                      <p className="text-sm font-semibold text-white/40 mt-1 uppercase tracking-widest">{m.type}</p>
                    </div>
                    <button className="p-3 bg-white/5 hover:bg-white/10 rounded-2xl transition-all text-white/40 hover:text-white border border-white/10">
                      <MoreVertical className="w-5 h-5" />
                    </button>
                  </div>
                  <div className="space-y-8">
                    <div>
                      <div className="flex items-center justify-between mb-3">
                        <span className="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em]">Uso y Carga</span>
                        <span className="text-sm font-bold text-white tracking-widest">{m.fuel}%</span>
                      </div>
                      <div className="w-full bg-white/5 h-3 rounded-full overflow-hidden shadow-inner p-[2px] border border-white/5">
                        <motion.div 
                          initial={{ width: 0 }}
                          animate={{ width: `${m.fuel}%` }}
                          className={`h-full rounded-full transition-all duration-1000 ${m.fuel < 50 ? 'bg-tertiary shadow-[0_0_10px_#f43f5e]' : 'bg-primary shadow-[0_0_10px_#6366f1]'}`}
                        ></motion.div>
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-10 pt-2 border-t border-white/5">
                      <div className="space-y-2">
                        <p className="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em]">Ubicación</p>
                        <p className="text-sm font-bold text-white flex items-center gap-2.5 tracking-wide">
                          <MapPin className="w-5 h-5 text-primary" /> {m.location}
                        </p>
                      </div>
                      <div className="space-y-2">
                        <p className="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em]">Servicio</p>
                        <p className="text-sm font-bold text-white flex items-center gap-2.5 tracking-wide text-nowrap">
                          <Calendar className="w-5 h-5 text-primary" /> {m.maint}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </motion.div>
            ))}
          </motion.section>
        ) : (
          <motion.section
            key="inventory"
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.98 }}
            className="glass-card rounded-[40px] overflow-hidden border border-white/10"
          >
            <div className="overflow-x-auto px-4">
              <table className="w-full text-left">
                <thead>
                  <tr className="border-b border-white/5">
                    <th className="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Nombre del Material</th>
                    <th className="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Stock Actual</th>
                    <th className="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Punto de Reorden</th>
                    <th className="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Tiempo de Entrega</th>
                    <th className="px-8 py-8 text-[11px] font-bold text-white/30 uppercase tracking-[0.2em]">Estado</th>
                    <th className="px-8 py-8 text-right"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/5">
                  {[
                    { name: "Cemento (Grado A)", sub: "Sacos (50kg)", stock: "1,240 Unidades", point: "300 Unidades", time: "3 Días", status: "Óptimo", icon: Layers },
                    { name: "Acero de Refuerzo", sub: "Toneladas", stock: "12.5 Tons", point: "15 Tons", time: "7 Días", status: "Stock Bajo", icon: List },
                    { name: "Arena de Río", sub: "Metros Cúbicos", stock: "450 m³", point: "100 m³", time: "2 Días", status: "Óptimo", icon: Boxes },
                    { name: "Grava Triturada", sub: "Metros Cúbicos", stock: "85 m³", point: "100 m³", time: "4 Días", status: "Advertencia", icon: Mountain },
                  ].map((inv, i) => (
                    <tr key={i} className="hover:bg-white/5 transition-all group">
                      <td className="px-8 py-8">
                        <div className="flex items-center gap-5">
                          <div className="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center text-white/40 group-hover:bg-primary/20 group-hover:text-primary transition-all border border-white/5 shadow-lg">
                            <inv.icon className="w-7 h-7" />
                          </div>
                          <div>
                            <p className="font-bold text-white text-lg tracking-tight">{inv.name}</p>
                            <p className="text-xs font-semibold text-white/40 tracking-widest uppercase mt-1">{inv.sub}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-8 py-8 font-bold text-white text-base">{inv.stock}</td>
                      <td className="px-8 py-8 font-semibold text-white/40">{inv.point}</td>
                      <td className="px-8 py-8 font-semibold text-white/70">{inv.time}</td>
                      <td className="px-8 py-8">
                        <span className={`px-5 py-2 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all ${
                          inv.status === "Óptimo" ? "bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]" :
                          inv.status === "Advertencia" ? "bg-orange-500/20 text-orange-400 border-orange-500/20 shadow-[0_0_15px_rgba(249,115,22,0.1)]" :
                          "bg-tertiary/20 text-tertiary border-tertiary/20 shadow-[0_0_15px_rgba(244,63,94,0.1)]"
                        }`}>
                          {inv.status}
                        </span>
                      </td>
                      <td className="px-8 py-8 text-right">
                        <button className={`font-bold text-[10px] uppercase tracking-[0.2em] shadow-xl transition-all hover:scale-105 active:scale-95 ${inv.status === "Stock Bajo" ? "bg-primary text-white px-8 py-3.5 rounded-2xl shadow-primary/20" : "text-primary hover:text-white px-8 py-3.5 border border-white/10 rounded-2xl"}`}>
                          {inv.status === "Stock Bajo" ? "Pedir Ahora" : "Reabastecer"}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </motion.section>
        )}
      </AnimatePresence>

      {/* Machinery Details Modal */}
      <AnimatePresence>
        {selectedMachine && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setSelectedMachine(null)}
              className="absolute inset-0 bg-black/80 backdrop-blur-sm"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 20 }}
              className="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)]"
            >
              <button 
                onClick={() => setSelectedMachine(null)}
                className="absolute top-8 right-8 z-10 w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/10 text-white/40 hover:text-white"
              >
                <X className="w-6 h-6" />
              </button>

              <div className="flex flex-col lg:flex-row h-full max-h-[85vh] overflow-y-auto">
                {/* Left: Media */}
                <div className="lg:w-1/2 relative bg-black/40">
                  <img src={selectedMachine.img} className="w-full h-full object-cover" alt={selectedMachine.name} />
                  <div className="absolute inset-0 bg-gradient-to-t from-black via-transparent to-transparent"></div>
                  <div className="absolute bottom-10 left-10">
                    <span className="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2 block">Activo de Empresa</span>
                    <h2 className="text-5xl font-black text-white italic uppercase tracking-tighter">{selectedMachine.name}</h2>
                    <p className="text-white/40 font-bold uppercase tracking-widest mt-2">{selectedMachine.type}</p>
                  </div>
                </div>

                {/* Right: Info */}
                <div className="lg:w-1/2 p-12 bg-black/20 overflow-y-auto">
                  <div className="space-y-10">
                    {/* Status & Health */}
                    <div className="flex gap-4">
                      <div className="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                          <Activity className="w-4 h-4" /> Estado Operativo
                        </p>
                        <div className="flex items-center gap-3">
                          <div className={`w-3 h-3 rounded-full ${selectedMachine.statusColor === 'green' ? 'bg-primary' : 'bg-orange-500'} shadow-[0_0_10px_currentColor]`}></div>
                          <span className="text-xl font-black italic uppercase text-white">{selectedMachine.status}</span>
                        </div>
                      </div>
                      <div className="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                          <Gauge className="w-4 h-4" /> Eficiencia de Ciclo
                        </p>
                        <span className="text-xl font-black italic uppercase text-white">{selectedMachine.fuel}% de Carga</span>
                      </div>
                    </div>

                    {/* Technical Specs */}
                    <div>
                      <h5 className="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                        <div className="w-8 h-[1px] bg-white/10"></div> Especificaciones Técnicas
                      </h5>
                      <div className="grid grid-cols-3 gap-6">
                        {Object.entries(selectedMachine.specs).map(([key, val]: any) => (
                          <div key={key} className="space-y-1">
                            <p className="text-[9px] font-black text-white/30 uppercase tracking-widest leading-tight">{key}</p>
                            <p className="text-sm font-bold text-white">{val}</p>
                          </div>
                        ))}
                      </div>
                    </div>

                    {/* Personnel */}
                    <div className="p-8 rounded-[32px] bg-white/5 border border-white/5 flex items-center justify-between">
                      <div className="flex items-center gap-5">
                        <div className="w-16 h-16 rounded-2xl bg-primary flex items-center justify-center text-white shadow-2xl shadow-primary/40">
                          <User className="w-8 h-8" />
                        </div>
                        <div>
                          <p className="text-[10px] font-black text-white/20 uppercase tracking-widest">Especialista Asignado</p>
                          <p className="text-xl font-black italic uppercase text-white tracking-tight">{selectedMachine.operator}</p>
                        </div>
                      </div>
                      <ShieldCheck className="w-8 h-8 text-primary/40" />
                    </div>

                    {/* History */}
                    <div>
                      <h5 className="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                        <div className="w-8 h-[1px] bg-white/10"></div> Libro de Mantenimiento
                      </h5>
                      <div className="space-y-4">
                        {selectedMachine.history.map((h: any, i: number) => (
                          <div key={i} className="flex items-center justify-between py-4 border-b border-white/5">
                            <div className="flex items-center gap-4">
                              <History className="w-5 h-5 text-white/20" />
                              <div>
                                <p className="text-sm font-bold text-white uppercase italic">{h.event}</p>
                                <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest">{h.date}</p>
                              </div>
                            </div>
                            <span className={`text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-lg border ${h.status === 'Aprobado' ? 'border-primary/20 text-primary' : 'border-tertiary/20 text-tertiary shadow-[0_0_10px_#f43f5e30]'}`}>
                              {h.status}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>

                    <button className="w-full glass-button-primary py-6 rounded-3xl font-black text-lg uppercase tracking-widest shadow-2xl shadow-primary/20 flex items-center justify-center gap-4 group">
                      <Calendar className="w-6 h-6 group-hover:scale-110 transition-transform" />
                      Planificar Mantenimiento
                    </button>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
