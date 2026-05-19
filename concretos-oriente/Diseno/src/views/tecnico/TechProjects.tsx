import { FolderKanban, Activity, CheckCircle2, Clock, MapPin, Plus, FileText, ChevronRight, MessageSquare } from "lucide-react";
import { motion } from "motion/react";

export default function TechProjects() {
  const myProjects = [
    { 
      name: "Skyline Tower - Fase 2", 
      id: "PRJ-001", 
      role: "Técnico Especialista", 
      location: "Zona 10, Ciudad de Guatemala",
      progress: 78,
      status: "Activo",
      tasks: [
        { name: "Verificación de Cimentación", status: "Done" },
        { name: "Instalación de Vigas Nivel 14", status: "Pending" }
      ]
    },
    { 
      name: "Marina Wharf Pavimentación", 
      id: "PRJ-015", 
      role: "Logística Técnica", 
      location: "San José, Escuintla",
      progress: 34,
      status: "Activo",
      tasks: [
        { name: "Drenajes Pluviales", status: "In Progress" }
      ]
    }
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      <div className="space-y-3">
        <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Mis Asignaciones</h2>
        <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Reportes de campo y seguimiento de obra</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {myProjects.map((prj, i) => (
          <motion.div 
            key={i}
            whileHover={{ y: -5 }}
            className="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl relative"
          >
            <div className="absolute top-0 right-0 p-8">
               <span className="px-4 py-2 bg-primary/20 text-primary border border-primary/20 rounded-full text-[10px] font-black uppercase tracking-widest">{prj.status}</span>
            </div>

            <div className="p-12">
              <div className="flex items-center gap-4 mb-8">
                <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-primary">
                  <FolderKanban className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="text-2xl font-black italic uppercase tracking-tighter leading-none">{prj.name}</h3>
                  <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-2">ID: {prj.id} • {prj.role}</p>
                </div>
              </div>

              <div className="flex items-center gap-4 mb-10 text-white/40">
                <MapPin className="w-4 h-4 text-primary" />
                <span className="text-xs font-bold uppercase tracking-tight">{prj.location}</span>
              </div>

              <div className="space-y-4 mb-12">
                <div className="flex justify-between items-center text-[10px] font-black uppercase tracking-[0.2em] text-white/30">
                  <span>Progreso de Obra</span>
                  <span>{prj.progress}%</span>
                </div>
                <div className="w-full h-2 bg-white/5 rounded-full overflow-hidden">
                  <motion.div 
                    initial={{ width: 0 }}
                    animate={{ width: `${prj.progress}%` }}
                    className="h-full bg-primary shadow-[0_0_15px_#6366f1]"
                  />
                </div>
              </div>

              <div className="space-y-4">
                <h4 className="text-[10px] font-black uppercase tracking-widest text-primary italic mb-6">Mis Tareas Actuales</h4>
                {prj.tasks.map((task, ti) => (
                  <div key={ti} className="flex items-center justify-between p-5 bg-white/5 rounded-2xl border border-white/5 group hover:border-primary/30 transition-all">
                    <div className="flex items-center gap-4">
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center ${task.status === 'Done' ? 'bg-primary/20 text-primary' : 'bg-white/5 text-white/20'}`}>
                        {task.status === 'Done' ? <CheckCircle2 className="w-4 h-4" /> : <Clock className="w-4 h-4" />}
                      </div>
                      <span className={`text-sm font-bold ${task.status === 'Done' ? 'text-white/40 line-through' : 'text-white'}`}>{task.name}</span>
                    </div>
                    <ChevronRight className="w-4 h-4 text-white/20 group-hover:translate-x-1 transition-transform" />
                  </div>
                ))}
              </div>

              <div className="grid grid-cols-2 gap-4 mt-8">
                <button className="py-4 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                  <FileText className="w-4 h-4" /> Subir Bitácora
                </button>
                <button className="py-4 rounded-2xl bg-white/5 border border-white/10 text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                  <MessageSquare className="w-4 h-4" /> Chat Site
                </button>
              </div>
            </div>
          </motion.div>
        ))}
      </div>
    </div>
  );
}
