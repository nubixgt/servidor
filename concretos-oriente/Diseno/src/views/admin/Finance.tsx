import { Wallet, TrendingUp, Clock, Construction, Banknote, HardDrive, User, Plus, Search, Bell, Settings, MoreVertical, ChevronLeft, ChevronRight, FileDown } from "lucide-react";
import { motion } from "motion/react";

export default function Finance() {
  const kpis = [
    { label: "Liquidez Total de Activos", value: "Q2,845k", sub: "+12.4% rendimiento", icon: Wallet, color: "primary" },
    { label: "Utilidad Operativa Neta", value: "Q412.8k", sub: "+3.1% sobre meta", icon: TrendingUp, color: "primary" },
    { label: "Pasivos no Resueltos", value: "Q84.3k", sub: "14 unidades pendientes", icon: Clock, color: "tertiary" },
  ];

  const transactions = [
    { desc: "Suministro de Concreto a Granel", date: "Mar 24, 2024", id: "INV-8821", cat: "Materiales", proj: "Azure Towers Fase 2", amount: "-Q12,450.00", status: "Completado", icon: Construction },
    { desc: "Pago de Hito de Proyecto", date: "Mar 22, 2024", id: "REC-4410", cat: "Ingresos", proj: "Riverside Plaza", amount: "+Q85,000.00", status: "Completado", icon: Banknote, isPositive: true },
    { desc: "Arrendamiento de Excavadora (Mensual)", date: "Mar 20, 2024", id: "EXP-9901", cat: "Equipo", proj: "Expansión de Carretera", amount: "-Q4,200.00", status: "Pendiente", icon: HardDrive },
    { desc: "Nómina de Personal", date: "Mar 15, 2024", id: "PAY-5522", cat: "Mano de Obra", proj: "Múltiples Proyectos", amount: "-Q28,900.00", status: "Completado", icon: User },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      {/* KPIs Section */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {kpis.map((kpi, i) => (
          <motion.div
            key={i}
            whileHover={{ y: -6, scale: 1.02 }}
            className="glass-card p-10 rounded-[40px] flex flex-col justify-between h-48 cursor-pointer group"
          >
            <div className="flex justify-between items-start">
              <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">{kpi.label}</span>
              <div className={`bg-${kpi.color}/20 p-4 rounded-2xl text-${kpi.color} shadow-lg shadow-${kpi.color}/20 border border-white/10`}>
                <kpi.icon className="w-6 h-6" />
              </div>
            </div>
            <div className="mt-auto">
              <h2 className={`text-4xl font-black tracking-tighter italic ${kpi.label.includes('Liabilities') ? 'text-tertiary' : 'text-white'}`}>{kpi.value}</h2>
              <div className={`flex items-center gap-2.5 mt-3 text-[10px] font-black uppercase tracking-widest ${kpi.label.includes('Liabilities') ? 'text-tertiary shadow-[0_0_10px_#f43f5e30]' : 'text-primary shadow-[0_0_10px_#6366f130]'}`}>
                {kpi.label.includes('Liquidity') && <TrendingUp className="w-4 h-4" />}
                {kpi.sub}
              </div>
            </div>
          </motion.div>
        ))}
      </section>

      {/* Charts Row */}
      <section className="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div className="lg:col-span-2 glass-card p-12 rounded-[56px] relative overflow-hidden">
          <div className="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-primary/5 to-transparent pointer-events-none"></div>
          <div className="flex justify-between items-center mb-12 relative z-10">
            <div>
              <h3 className="text-3xl font-black text-white uppercase italic tracking-tighter">Dinámica Fiscal</h3>
              <p className="text-sm font-bold text-white/30 mt-2 uppercase tracking-widest">Análisis de entrada vs salida YTD</p>
            </div>
            <div className="flex gap-10">
              <div className="flex items-center gap-3">
                <span className="w-2.5 h-2.5 rounded-full bg-primary shadow-[0_0_12px_#6366f1]"></span>
                <span className="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Activos Líquidos</span>
              </div>
              <div className="flex items-center gap-3">
                <span className="w-2.5 h-2.5 rounded-full bg-white/20"></span>
                <span className="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Pasivos</span>
              </div>
            </div>
          </div>
          <div className="h-72 relative flex items-end justify-between px-6 z-10">
             <div className="absolute inset-0 flex flex-col justify-between text-[9px] font-black text-white/10 uppercase tracking-widest pointer-events-none pb-12">
              {[500, 400, 300, 200, 100, 0].map(val => (
                <div key={val} className="border-t border-white/5 w-full pt-2 flex justify-between items-center">
                  <span>{val}K Val</span>
                  <div className="w-[85%] border-t border-dashed border-white/5"></div>
                </div>
              ))}
            </div>
            <div className="flex-1 flex items-end justify-around gap-10 h-full z-10 pb-6 ml-10">
              {["Ene", "Feb", "Mar", "Abr", "May", "Jun"].map((month, i) => (
                <div key={month} className="flex flex-col items-center gap-4 w-full group h-full justify-end">
                  <div className="flex gap-3 w-full items-end justify-center h-full">
                    <motion.div 
                      whileHover={{ scaleX: 1.1 }}
                      initial={{ height: 0 }}
                      animate={{ height: `${[60, 75, 85, 70, 90, 80][i]}%` }}
                      className={`w-5 rounded-t-xl transition-all duration-700 ${i === 2 ? 'bg-primary shadow-[0_0_25px_#6366f160]' : 'bg-primary/20 group-hover:bg-primary/40'}`}
                    ></motion.div>
                    <motion.div 
                      whileHover={{ scaleX: 1.1 }}
                      initial={{ height: 0 }}
                      animate={{ height: `${[40, 45, 50, 60, 35, 40][i]}%` }}
                      className={`w-5 rounded-t-xl transition-all duration-700 ${i === 2 ? 'bg-white/40 shadow-[0_0_20px_rgba(255,255,255,0.1)]' : 'bg-white/10 group-hover:bg-white/20'}`}
                    ></motion.div>
                  </div>
                  <span className={`text-[10px] font-black tracking-widest uppercase ${i === 2 ? 'text-primary shadow-[0_0_5px_#6366f150]' : 'text-white/20'}`}>{month}</span>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="glass-card p-12 rounded-[56px] flex flex-col border border-white/5">
          <h3 className="text-3xl font-black text-white uppercase italic tracking-tighter">Vector de Presupuesto</h3>
          <p className="text-[10px] font-bold text-white/30 mt-2 mb-12 uppercase tracking-[0.2em]">Perfil de distribución de recursos</p>
          <div className="relative w-60 h-60 mx-auto mb-12 flex items-center justify-center">
            <svg className="w-full h-full transform -rotate-90 drop-shadow-2xl" viewBox="0 0 36 36">
              <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="rgba(255,255,255,0.05)" strokeWidth="4.5" />
              <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#6366f1" strokeWidth="4.5" strokeDasharray="55 100" strokeLinecap="round" className="drop-shadow-[0_0_8px_#6366f1]" />
              <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="#f43f5e" strokeWidth="4.5" strokeDasharray="25 100" strokeDashoffset="-55" strokeLinecap="round" className="drop-shadow-[0_0_8px_#f43f5e]" />
              <circle cx="18" cy="18" r="15.9" fill="transparent" stroke="rgba(255,255,255,0.3)" strokeWidth="4.5" strokeDasharray="20 100" strokeDashoffset="-80" strokeLinecap="round" />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center text-center translate-y-1">
              <span className="text-5xl font-black text-white italic tracking-tighter">Q152k</span>
              <span className="text-[8px] text-white/30 uppercase font-black tracking-[0.4em] mt-2">Capital Activo</span>
            </div>
          </div>
          <div className="space-y-6 mt-auto">
            {[
              { label: "Infraestructura", val: 55, color: "bg-primary shadow-[0_0_10px_#6366f1]" },
              { label: "Operaciones de Talento", val: 25, color: "bg-tertiary shadow-[0_0_10px_#f43f5e]" },
              { label: "Subsidiarios Auxiliares", val: 20, color: "bg-white/20" },
            ].map((item) => (
              <div key={item.label} className="flex items-center justify-between group cursor-pointer border-b border-white/5 pb-4">
                <div className="flex items-center gap-4">
                  <span className={`w-3 h-3 rounded-full ${item.color} group-hover:scale-150 transition-transform duration-500`}></span>
                  <span className="text-xs font-bold text-white/60 tracking-wider uppercase">{item.label}</span>
                </div>
                <span className="text-sm font-black text-white italic">{item.val}%</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Transactions Section */}
      <section className="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
        <div className="p-12 flex flex-col md:flex-row md:items-center justify-between gap-8 border-b border-white/5 bg-white/5 backdrop-blur-3xl">
          <div>
            <h3 className="text-4xl font-black text-white italic uppercase tracking-tighter">Protocolo de Transacciones</h3>
            <p className="text-[10px] font-bold text-white/30 mt-3 uppercase tracking-[0.3em]">Libro mayor financiero en tiempo real</p>
          </div>
          <button className="glass-button-primary text-white border border-white/20 px-10 py-5 rounded-[28px] font-black text-xs uppercase tracking-[0.2em] flex items-center gap-4 shadow-2xl shadow-primary/20 hover:shadow-primary/40 transition-all">
            <FileDown className="w-6 h-6" />
            Exportación Segura de Auditoría
          </button>
        </div>

        <div className="overflow-x-auto px-6">
          <table className="w-full text-left">
            <thead>
              <tr className="text-[11px] font-bold text-white/20 uppercase tracking-[0.3em]">
                <th className="px-10 py-10">Descripción de Entidad</th>
                <th className="px-10 py-10">Vector</th>
                <th className="px-10 py-10">Proyecto Vinculado</th>
                <th className="px-10 py-10 text-right">Valor Neto</th>
                <th className="px-10 py-10">Estado de Auditoría</th>
                <th className="px-10 py-10"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {transactions.map((tx, i) => (
                <tr key={i} className="hover:bg-white/5 group transition-all duration-300 cursor-pointer">
                  <td className="px-10 py-10">
                    <div className="flex items-center gap-6">
                      <div className="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-xl group-hover:shadow-primary/30">
                        <tx.icon className="w-8 h-8" />
                      </div>
                      <div>
                        <p className="font-black text-xl text-white tracking-tight italic uppercase">{tx.desc}</p>
                        <p className="text-[10px] font-bold text-white/30 mt-1 uppercase tracking-widest">{tx.date} • {tx.id}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-10 py-10">
                    <span className="text-[10px] font-black text-white/60 uppercase tracking-[0.3em] bg-white/5 px-4 py-2 rounded-xl border border-white/5">{tx.cat}</span>
                  </td>
                  <td className="px-10 py-10">
                    <span className="text-sm font-bold text-white/40 uppercase tracking-widest">{tx.proj}</span>
                  </td>
                  <td className={`px-10 py-10 text-right font-black italic text-2xl ${tx.isPositive ? 'text-primary shadow-[0_0_15px_#6366f130]' : 'text-white'}`}>
                    {tx.amount}
                  </td>
                  <td className="px-10 py-10">
                    <div className={`flex items-center gap-3 font-black text-[10px] uppercase tracking-[0.2em] ${tx.status === 'Completed' ? 'text-primary' : 'text-tertiary'}`}>
                      <span className={`w-2.5 h-2.5 rounded-full ${tx.status === 'Completed' ? 'bg-primary shadow-[0_0_10px_#6366f1]' : 'bg-tertiary animate-pulse shadow-[0_0_10px_#f43f5e]'}`}></span>
                      {tx.status}
                    </div>
                  </td>
                  <td className="px-10 py-10 text-right">
                    <button className="w-12 h-12 rounded-2xl bg-white/5 hover:bg-white/10 transition-all flex items-center justify-center text-white/10 hover:text-white">
                      <MoreVertical className="w-6 h-6" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="p-10 flex flex-col md:flex-row justify-between items-center gap-8 border-t border-white/5 bg-black/20">
          <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">Mostrando 1-10 de 480 Entradas Totales del Libro Mayor</p>
          <div className="flex items-center gap-4">
            <button className="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/20 hover:bg-white/5 transition-all">
              <ChevronLeft className="w-7 h-7" />
            </button>
            <button className="w-12 h-12 flex items-center justify-center rounded-2xl bg-primary text-white font-black italic text-lg shadow-[0_0_20px_rgba(99,102,241,0.4)]">1</button>
            <button className="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/40 hover:bg-white/5 transition-all font-black italic">2</button>
            <button className="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/40 hover:bg-white/5 transition-all font-black italic">3</button>
            <button className="w-12 h-12 flex items-center justify-center rounded-2xl border border-white/10 text-white/20 hover:bg-white/5 transition-all">
              <ChevronRight className="w-7 h-7" />
            </button>
          </div>
        </div>
      </section>

      {/* FAB */}
      <button className="fixed bottom-12 right-12 w-20 h-20 rounded-[32px] glass-button-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-50 group">
        <Plus className="w-10 h-10 group-hover:rotate-90 transition-transform duration-500" />
      </button>
    </div>
  );
}
