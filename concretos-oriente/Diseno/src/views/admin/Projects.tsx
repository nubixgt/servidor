import { Building2, Factory, HardHat, User, ChevronRight, CheckCircle2, TrendingUp, PiggyBank, MoreVertical, Plus, X, Calendar, MapPin, Activity, ShieldCheck, Briefcase } from "lucide-react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

export default function Projects() {
  const [view, setView] = useState("projects");
  const [selectedProject, setSelectedProject] = useState<any>(null);

  const projects = [
    { 
      name: "Torre Skyline Heights A", 
      lead: "Marcus Sterling", 
      progress: 68, 
      status: "Ruta Crítica", 
      statusColor: "tertiary", 
      icon: Building2, 
      budget: "+4.2% Sobre", 
      budgetColor: "text-tertiary", 
      img: "https://images.unsplash.com/photo-1541888946425-d81bb19480c5?auto=format&fit=crop&q=80&w=2070",
      location: "Zona 10, Ciudad de Guatemala",
      startDate: "Ene 2024",
      milestones: [
        { name: "Cimentación", status: "Completado" },
        { name: "Estructura Base", status: "Completado" },
        { name: "Instalaciones Eléctricas", status: "En Progreso" }
      ]
    },
    { 
      name: "Centro Logístico Oak Creek", 
      lead: "Sarah Chen", 
      progress: 12, 
      status: "En Planificación", 
      statusColor: "white/20", 
      icon: Factory, 
      budget: "En Tiempo", 
      budgetColor: "text-primary", 
      img: "https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&q=80&w=2070",
      location: "San José Pinula",
      startDate: "Mar 2024",
      milestones: [
        { name: "Permisos Ambientales", status: "Completado" },
        { name: "Preparación de Terreno", status: "En Progreso" }
      ]
    },
    { 
      name: "Muelle Harborview", 
      lead: "James Wilson", 
      progress: 89, 
      status: "Obra Activa", 
      statusColor: "primary", 
      icon: HardHat, 
      budget: "-2.1% Ahorro", 
      budgetColor: "text-primary", 
      highlightedBudget: true, 
      img: "https://images.unsplash.com/photo-1541123437800-1bb1317badc2?auto=format&fit=crop&q=80&w=2070",
      location: "Puerto Barrios, Izabal",
      startDate: "Oct 2023",
      milestones: [
        { name: "Estructura de Muelles", status: "Completado" },
        { name: "Acabados de Exterior", status: "En Progreso" }
      ]
    },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 min-h-screen text-white">
      <header className="flex flex-col md:flex-row md:items-end justify-between gap-10 bg-white/5 p-10 rounded-[48px] border border-white/10 backdrop-blur-xl">
        <div className="space-y-3">
          <h1 className="text-5xl font-black tracking-tighter uppercase italic">Portafolio de Proyectos</h1>
          <p className="text-white/60 text-lg font-medium leading-relaxed max-w-xl">Supervisión de ciclos de vida de construcción y desempeño de socios en todos los sitios activos con analítica en tiempo real.</p>
        </div>

        <div className="flex p-2 bg-black/20 rounded-[28px] shadow-inner border border-white/10 backdrop-blur-xl">
          <button 
            onClick={() => setView("projects")}
            className={`px-10 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all ${
              view === "projects" ? "bg-primary text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]" : "text-white/40 hover:text-white"
            }`}
          >
            Proyectos Activos
          </button>
          <button 
            onClick={() => setView("providers")}
            className={`px-10 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all ${
              view === "providers" ? "bg-primary text-white shadow-[0_0_20px_rgba(99,102,241,0.4)]" : "text-white/40 hover:text-white"
            }`}
          >
            Proveedores de Servicios
          </button>
        </div>
      </header>

      <AnimatePresence mode="wait">
        {view === "projects" ? (
          <motion.section 
            key="projects"
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.98 }}
            className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10"
          >
            {projects.map((proj, i) => {
              const Icon = proj.icon;
              return (
                <motion.div
                  key={i}
                  whileHover={{ y: -8 }}
                  onClick={() => setSelectedProject(proj)}
                  className="glass-card rounded-[48px] overflow-hidden group cursor-pointer border border-white/10 shadow-[0_24px_48px_-12px_rgba(0,0,0,0.5)] flex flex-col h-full"
                >
                  <div className="h-56 relative overflow-hidden shrink-0">
                    <img src={proj.img} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000" alt={proj.name} referrerPolicy="no-referrer" />
                    <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    <div className="absolute top-6 right-6 px-4 py-2 backdrop-blur-2xl bg-white/10 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border border-white/20 shadow-xl">
                      <div className="flex items-center gap-2.5">
                        <span className={`w-2 h-2 rounded-full ${proj.statusColor.includes('primary') ? 'bg-primary' : proj.statusColor.includes('tertiary') ? 'bg-tertiary' : 'bg-white/40'}`}></span>
                        {proj.status}
                      </div>
                    </div>
                  </div>

                  <div className="p-10 flex flex-col flex-1">
                    <div className="flex justify-between items-start mb-6">
                      <div className="w-14 h-14 bg-primary/20 rounded-2xl flex items-center justify-center text-primary border border-white/10 shadow-lg group-hover:shadow-primary/20 transition-all">
                        <Icon className="w-7 h-7" />
                      </div>
                      <button className="p-3 bg-white/5 hover:bg-white/10 rounded-xl transition-all">
                        <MoreVertical className="w-5 h-5 text-white/20" />
                      </button>
                    </div>

                    <h3 className="text-2xl font-black text-white mb-2 leading-tight uppercase italic">{proj.name}</h3>
                    <p className="text-xs font-bold text-white/40 mb-10 flex items-center gap-2.5 uppercase tracking-widest">
                      <User className="w-4 h-4 text-primary" /> Lider: {proj.lead}
                    </p>

                    <div className="space-y-4 mb-10 mt-auto">
                      <div className="flex justify-between items-center text-[10px] font-black uppercase tracking-[0.3em] text-white/30">
                        <span>Progreso del Proyecto</span>
                        <span className="text-white font-black italic text-sm">{proj.progress}%</span>
                      </div>
                      <div className="w-full h-3 bg-white/5 rounded-full overflow-hidden shadow-inner p-[2px] border border-white/5">
                        <motion.div 
                          initial={{ width: 0 }}
                          animate={{ width: `${proj.progress}%` }}
                          className={`h-full rounded-full ${proj.progress > 80 ? 'bg-tertiary shadow-[0_0_10px_#f43f5e]' : 'bg-primary shadow-[0_0_10px_#6366f1]'}`}
                        ></motion.div>
                      </div>
                    </div>

                    <div className="flex items-center justify-between pt-8 border-t border-white/5">
                      <div>
                        <p className="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em] mb-2">Salud Fiscal</p>
                        <div className={`flex items-center gap-2 font-black text-sm uppercase tracking-tighter italic ${proj.budgetColor}`}>
                          {proj.budget.includes("Sobre") ? <TrendingUp className="w-5 h-5" /> : <PiggyBank className="w-5 h-5" />}
                          <span className={proj.highlightedBudget ? "bg-primary text-white px-3 py-1 rounded-lg text-xs" : ""}>{proj.budget}</span>
                        </div>
                      </div>
                      <button className="w-12 h-12 rounded-2xl bg-white/5 hover:bg-primary transition-all flex items-center justify-center group-hover:shadow-[0_0_20px_rgba(99,102,241,0.2)]">
                        <ChevronRight className="w-6 h-6 text-white/40 group-hover:text-white" />
                      </button>
                    </div>
                  </div>
                </motion.div>
              );
            })}
          </motion.section>
        ) : (
          <motion.section
            key="providers"
            initial={{ opacity: 0, y: 30 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 30 }}
            className="glass-card rounded-[48px] overflow-hidden border border-white/10"
          >
            <div className="overflow-x-auto px-6">
              <table className="w-full text-left">
                <thead>
                  <tr className="border-b border-white/5">
                    <th className="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Entidad Socia</th>
                    <th className="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Unidad Operativa</th>
                    <th className="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Contacto Seguro</th>
                    <th className="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Valuación Pendiente</th>
                    <th className="px-10 py-10 text-[11px] font-black text-white/30 uppercase tracking-[0.3em]">Verificación</th>
                    <th className="px-10 py-10"></th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/5">
                  {[
                    { name: "Elite Concrete Co.", id: "PR-092", service: "Estructuras y Cimientos", email: "ops@elite.com", tel: "+1 (555) 012-3456", amount: "Q12,450.00", count: "2 Pendientes", status: "Preferido", initials: "EC" },
                    { name: "Vantage Steel Corp", id: "PR-114", service: "Sistemas Reforzados", email: "intel@vantage.io", tel: "+1 (555) 987-6543", amount: "Q45,200.00", count: "Atrasado", status: "Crítico", initials: "VS", isCritical: true },
                    { name: "Apex Wiring Ltd.", id: "PR-042", service: "Infraestructura Eléctrica", email: "support@apex.systems", tel: "+1 (555) 444-3210", amount: "Q3,100.00", count: "1 En Proceso", status: "Verificado", initials: "AW" },
                  ].map((p, i) => (
                    <tr key={i} className="hover:bg-white/5 transition-all group cursor-pointer">
                      <td className="px-10 py-10">
                        <div className="flex items-center gap-6">
                          <div className={`w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center font-black text-lg group-hover:bg-primary group-hover:text-white transition-all shadow-xl ${p.isCritical ? "text-tertiary" : "text-primary"}`}>
                            {p.initials}
                          </div>
                          <div>
                            <p className="font-black text-xl text-white tracking-tight italic uppercase">{p.name}</p>
                            <p className="text-[10px] text-white/30 uppercase font-bold tracking-[0.2em] mt-1.5">ID Reg: {p.id}</p>
                          </div>
                        </div>
                      </td>
                      <td className="px-10 py-10">
                        <p className="text-sm font-bold text-white/70 uppercase tracking-widest">{p.service}</p>
                      </td>
                      <td className="px-10 py-10">
                        <p className="text-sm font-bold text-white truncate max-w-[150px]">{p.email}</p>
                        <p className="text-xs font-bold text-white/30 uppercase tracking-widest mt-1.5">{p.tel}</p>
                      </td>
                      <td className="px-10 py-10">
                        <p className={`text-xl font-black italic ${p.isCritical ? "text-tertiary" : "text-white"}`}>{p.amount}</p>
                        <p className={`text-[10px] font-black uppercase tracking-[0.2em] mt-2 ${p.isCritical ? "text-tertiary/60" : "text-white/30"}`}>{p.count}</p>
                      </td>
                      <td className="px-10 py-10">
                        <span className={`px-6 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest border transition-all ${
                          p.status === "Preferido" ? "bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.2)]" : 
                          p.status === "Crítico" ? "bg-tertiary/20 text-tertiary border-tertiary/20 shadow-[0_0_15px_rgba(244,63,94,0.2)]" : 
                          "bg-white/10 text-white/60 border-white/10"
                        }`}>
                          {p.status}
                        </span>
                      </td>
                      <td className="px-10 py-10 text-right">
                        <button className="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-white/20 hover:text-white transition-all hover:bg-white/10">
                          <MoreVertical className="w-6 h-6" />
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

      {/* Project Details Modal */}
      <AnimatePresence>
        {selectedProject && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setSelectedProject(null)}
              className="absolute inset-0 bg-black/80 backdrop-blur-sm"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 20 }}
              className="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)]"
            >
              <button 
                onClick={() => setSelectedProject(null)}
                className="absolute top-8 right-8 z-10 w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/10 text-white/40 hover:text-white"
              >
                <X className="w-6 h-6" />
              </button>

              <div className="flex flex-col lg:flex-row h-full max-h-[85vh] overflow-y-auto">
                {/* Left: Media */}
                <div className="lg:w-1/2 relative bg-black/40">
                  <img src={selectedProject.img} className="w-full h-full object-cover" alt={selectedProject.name} />
                  <div className="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
                  <div className="absolute bottom-10 left-10">
                    <span className="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-2 block">Activo de Empresa</span>
                    <h2 className="text-5xl font-black text-white italic uppercase tracking-tighter">{selectedProject.name}</h2>
                    <p className="text-white/40 font-bold uppercase tracking-widest mt-2">LIDERADO POR {selectedProject.lead}</p>
                  </div>
                </div>

                {/* Right: Info */}
                <div className="lg:w-1/2 p-12 bg-black/20 overflow-y-auto">
                  <div className="space-y-10">
                    {/* Status & Velocity */}
                    <div className="flex gap-4">
                      <div className="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                          <Activity className="w-4 h-4" /> Estado Actual
                        </p>
                        <div className="flex items-center gap-3">
                          <div className={`w-3 h-3 rounded-full ${selectedProject.statusColor === 'primary' ? 'bg-primary' : 'bg-tertiary'} shadow-[0_0_10px_currentColor]`}></div>
                          <span className="text-xl font-black italic uppercase text-white">{selectedProject.status}</span>
                        </div>
                      </div>
                      <div className="flex-1 glass-card p-6 rounded-3xl border border-white/5">
                        <p className="text-[10px] font-black uppercase tracking-[0.2em] text-white/20 mb-4 flex items-center gap-2">
                          <TrendingUp className="w-4 h-4" /> Progreso
                        </p>
                        <span className="text-xl font-black italic uppercase text-white">{selectedProject.progress}%</span>
                      </div>
                    </div>

                    {/* Quick Info */}
                    <div className="grid grid-cols-2 gap-6">
                      <div className="space-y-2">
                        <p className="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-2"><MapPin className="w-3 h-3" /> Ubicación</p>
                        <p className="text-sm font-bold text-white">{selectedProject.location}</p>
                      </div>
                      <div className="space-y-2">
                        <p className="text-[10px] font-black text-white/30 uppercase tracking-widest flex items-center gap-2"><Calendar className="w-3 h-3" /> Inicio de Obra</p>
                        <p className="text-sm font-bold text-white">{selectedProject.startDate}</p>
                      </div>
                    </div>

                    {/* Milestones */}
                    <div>
                      <h5 className="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                        <div className="w-8 h-[1px] bg-white/10"></div> Hitos del Proyecto
                      </h5>
                      <div className="space-y-4">
                        {selectedProject.milestones.map((m: any, i: number) => (
                          <div key={i} className="flex items-center justify-between py-4 border-b border-white/5">
                            <div className="flex items-center gap-4">
                              <CheckCircle2 className={`w-5 h-5 ${m.status === 'Completado' ? 'text-primary' : 'text-white/20'}`} />
                              <p className="text-sm font-bold text-white uppercase italic">{m.name}</p>
                            </div>
                            <span className={`text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-lg ${m.status === 'Completado' ? 'bg-primary/10 text-primary' : 'bg-white/5 text-white/30'}`}>
                              {m.status}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>

                    <div className="p-8 rounded-[32px] bg-white/5 border border-white/5 flex items-center justify-between">
                      <div className="flex items-center gap-5">
                        <div className="w-16 h-16 rounded-2xl bg-primary/20 border border-white/10 flex items-center justify-center text-primary">
                          <Briefcase className="w-8 h-8" />
                        </div>
                        <div>
                          <p className="text-[10px] font-black text-white/30 uppercase tracking-widest">Presupuesto de Obra</p>
                          <p className={`text-xl font-black italic uppercase italic tracking-tight ${selectedProject.budgetColor}`}>{selectedProject.budget}</p>
                        </div>
                      </div>
                      <ShieldCheck className="w-8 h-8 text-primary/40" />
                    </div>

                    <button className="w-full glass-button-primary py-6 rounded-3xl font-black text-lg uppercase tracking-widest shadow-2xl shadow-primary/20 flex items-center justify-center gap-4">
                      Ver Reporte Detallado
                    </button>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      <button className="fixed bottom-12 right-12 h-20 w-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 group">
        <Plus className="w-10 h-10 group-hover:rotate-90 transition-transform duration-500 shadow-[0_0_20px_rgba(99,102,241,0.5)]" />
      </button>
    </div>
  );
}
