import { Users, UserCheck, CalendarOff, Search, Plus, Filter, Download, MoreVertical, ChevronLeft, ChevronRight } from "lucide-react";
import { motion } from "motion/react";

export default function Personnel() {
  const stats = [
    { label: "Total de Empleados", value: "1,248", change: "+4 esta semana", icon: Users, color: "text-primary", bgColor: "bg-primary/20" },
    { label: "Presentes Hoy", value: "1,176", change: "94.2% Tasa", icon: UserCheck, color: "text-primary", bgColor: "bg-white/10" },
    { label: "De Licencia", value: "72", change: "12 Pendientes", icon: CalendarOff, color: "text-tertiary", bgColor: "bg-tertiary/20" },
  ];

  const employees = [
    { name: "Michael Scott", role: "Ingeniero Principal", project: "Torre Skyline", status: "Activo", attendance: "98.5%", id: "44291", initials: "MS" },
    { name: "Jim Beasley", role: "Cataz", project: "Puente Oakridge", status: "De Licencia", attendance: "82.0%", id: "44292", initials: "JB" },
    { name: "Dwight Walker", role: "Obrero (Grado I)", project: "Torre Skyline", status: "Activo", attendance: "94.8%", id: "44293", initials: "DW" },
    { name: "Pam Halpert", role: "Inspector de Seguridad", project: "Muelle Marina", status: "Activo", attendance: "100%", id: "44294", initials: "PH" },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <h2 className="text-4xl font-bold tracking-tight text-white mb-2">Gestión de Personal</h2>
          <p className="text-white/60">Gestiona tu fuerza laboral, rastrea la asistencia y asigna cuadrillas.</p>
        </div>
        <button className="glass-button-primary text-white py-4 px-10 rounded-2xl font-bold flex items-center justify-center gap-2 shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all">
          <Plus className="w-5 h-5" />
          Añadir Personal
        </button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {stats.map((stat, i) => (
          <motion.div
            key={i}
            whileHover={{ y: -6, scale: 1.02 }}
            className="glass-card p-10 rounded-[32px] flex flex-col justify-between h-52 cursor-pointer group"
          >
            <div className="flex items-center justify-between mb-4">
              <div className={`p-4 rounded-2xl ${stat.bgColor} ${stat.color} border border-white/10 shadow-lg`}>
                <stat.icon className="w-8 h-8" />
              </div>
              <span className={`text-[11px] font-bold px-3.5 py-1.5 rounded-full ${stat.color} ${stat.bgColor} border border-white/5 tracking-wider uppercase`}>
                {stat.change}
              </span>
            </div>
            <div>
              <p className="text-white/40 text-[11px] font-bold uppercase tracking-[0.2em]">{stat.label}</p>
              <h3 className="text-4xl font-bold text-white mt-2 group-hover:text-primary transition-colors">{stat.value}</h3>
            </div>
          </motion.div>
        ))}
      </div>

      {/* Table Section */}
      <div className="glass-card rounded-[40px] overflow-hidden border border-white/10">
        <div className="p-10 flex flex-wrap items-center justify-between gap-6 border-b border-white/5">
          <div className="flex flex-wrap items-center gap-4">
            <div className="glass-input px-6 py-3 rounded-2xl flex items-center gap-3 text-white/60 font-bold text-xs uppercase tracking-widest cursor-pointer">
              <Filter className="w-4 h-4" />
              Filtros
            </div>
            <select className="bg-white/5 border border-white/10 rounded-2xl px-6 py-3 text-sm font-semibold text-white outline-none focus:ring-2 focus:ring-primary/40 cursor-pointer transition-all">
              <option className="bg-slate-900">Todos los Roles</option>
              <option className="bg-slate-900">Ingeniero Principal</option>
              <option className="bg-slate-900">Mayordomo</option>
              <option className="bg-slate-900">Inspector de Seguridad</option>
            </select>
          </div>
          <button className="flex items-center gap-2 text-primary text-sm font-bold hover:bg-white/5 px-8 py-3 rounded-2xl transition-all border border-white/10">
            <Download className="w-5 h-5" />
            Exportar CSV
          </button>
        </div>

        <div className="overflow-x-auto px-4">
          <table className="w-full text-left">
            <thead>
              <tr className="text-[11px] font-bold text-white/40 uppercase tracking-[0.2em]">
                <th className="px-8 py-8">Nombre del Empleado</th>
                <th className="px-8 py-8">Rol</th>
                <th className="px-8 py-8">Proyecto Asignado</th>
                <th className="px-8 py-8">Estado</th>
                <th className="px-8 py-8 text-right">Asistencia</th>
                <th className="px-8 py-8"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {employees.map((emp) => (
                <tr key={emp.id} className="hover:bg-white/5 group cursor-pointer transition-colors duration-200">
                  <td className="px-8 py-8">
                    <div className="flex items-center gap-5">
                      <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center font-bold text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300 border border-white/10 shadow-lg">
                        {emp.initials}
                      </div>
                      <div>
                        <p className="font-bold text-white text-lg">{emp.name}</p>
                        <p className="text-xs text-white/40 font-medium tracking-widest mt-1">ID: {emp.id}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-8 py-8">
                    <span className="text-sm font-semibold text-white/70">{emp.role}</span>
                  </td>
                  <td className="px-8 py-8">
                    <div className="flex items-center gap-3">
                      <div className={`w-2.5 h-2.5 rounded-full ${emp.id === '44292' ? 'bg-white/20' : emp.id === '44294' ? 'bg-tertiary shadow-[0_0_8px_#f43f5e]' : 'bg-primary shadow-[0_0_8px_#6366f1]'}`}></div>
                      <span className="text-sm font-bold text-white/90">{emp.project}</span>
                    </div>
                  </td>
                  <td className="px-8 py-8">
                    <span className={`px-4 py-2 rounded-full text-[10px] font-extrabold uppercase tracking-widest border transition-all ${
                      emp.status === 'Activo' 
                        ? 'bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]' 
                        : 'bg-tertiary/20 text-tertiary border-tertiary/20 shadow-[0_0_15px_rgba(244,63,94,0.1)] shadow-inner'
                    }`}>
                      {emp.status}
                    </span>
                  </td>
                  <td className="px-8 py-8 text-right font-bold text-white text-base">
                    {emp.attendance}
                  </td>
                  <td className="px-8 py-8 text-right">
                    <button className="p-3 text-white/20 hover:text-primary transition-all opacity-0 group-hover:opacity-100 bg-white/5 rounded-xl">
                      <MoreVertical className="w-5 h-5" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="px-8 py-8 flex items-center justify-between border-t border-white/5">
          <p className="text-xs font-bold text-white/30 tracking-widest uppercase">Mostrando 1-10 de 1,248 empleados</p>
          <div className="flex items-center gap-3">
            <button className="p-3 rounded-2xl bg-white/5 hover:bg-white/10 transition-all text-white/40 disabled:opacity-10" disabled>
              <ChevronLeft className="w-6 h-6" />
            </button>
            {[1, 2, 3].map((page) => (
              <button
                key={page}
                className={`w-12 h-12 rounded-2xl font-bold text-sm transition-all border border-white/5 ${
                  page === 1 ? "glass-button-primary text-white shadow-xl shadow-primary/20" : "hover:bg-white/10 text-white/50"
                }`}
              >
                {page}
              </button>
            ))}
            <button className="p-3 rounded-2xl bg-white/5 hover:bg-white/10 transition-all text-white/40">
              <ChevronRight className="w-6 h-6" />
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
