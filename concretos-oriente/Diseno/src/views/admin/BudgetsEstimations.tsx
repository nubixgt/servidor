import { useState, FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  PiggyBank, 
  Plus, 
  Search, 
  TrendingUp, 
  Calendar, 
  ChevronLeft, 
  ChevronRight, 
  Wrench, 
  Users, 
  Award, 
  CheckCircle2, 
  Flame, 
  Sparkles, 
  AlertCircle,
  FileText,
  DollarSign
} from "lucide-react";

interface BudgetEstimate {
  id: string;
  project: string;
  phase: string;
  date: string;
  cost: number;
  utility: number; // Percentage, e.g. 22
  status: "En Revisión" | "Aprobado" | "Borrador";
}

export default function BudgetsEstimations() {
  const [searchTerm, setSearchTerm] = useState("");
  const [showCreateModal, setShowCreateModal] = useState(false);

  // Form states
  const [projectName, setProjectName] = useState("");
  const [phaseName, setPhaseName] = useState("");
  const [costValue, setCostValue] = useState("");
  const [utilityValue, setUtilityValue] = useState("22");
  const [statusValue, setStatusValue] = useState<"En Revisión" | "Aprobado" | "Borrador">("En Revisión");

  // Initial Budgets Data
  const [budgets, setBudgets] = useState<BudgetEstimate[]>([
    {
      id: "BDG-01",
      project: "Residencial 'La Sierra'",
      phase: "Estructura",
      date: "24 May, 2026",
      cost: 450200.00,
      utility: 22,
      status: "En Revisión"
    },
    {
      id: "BDG-02",
      project: "Torre Corporativa Delta",
      phase: "Acabados",
      date: "20 May, 2026",
      cost: 890500.00,
      utility: 28,
      status: "Aprobado"
    },
    {
      id: "BDG-03",
      project: "Complejo Logístico Norte",
      phase: "Excavación",
      date: "15 May, 2026",
      cost: 1120000.00,
      utility: 19,
      status: "Borrador"
    }
  ]);

  // Bar Graph monthly stats (Ene to Abr)
  const [graphData, setGraphData] = useState([
    { month: "Ene", budget: 60, real: 65 },
    { month: "Feb", budget: 75, real: 70 },
    { month: "Mar", budget: 55, real: 52 },
    { month: "Abr", budget: 85, real: 92 },
  ]);

  // Add notification state
  const [notification, setNotification] = useState("");

  const handleCreateEstimate = (e: FormEvent) => {
    e.preventDefault();
    if (!projectName || !costValue) return;

    const newEst: BudgetEstimate = {
      id: "BDG-" + Math.floor(Math.random() * 90 + 10),
      project: projectName,
      phase: phaseName || "General",
      date: new Date().toLocaleDateString("es-GT", { day: "numeric", month: "short", year: "numeric" }),
      cost: parseFloat(costValue),
      utility: parseInt(utilityValue),
      status: statusValue
    };

    setBudgets([newEst, ...budgets]);
    setShowCreateModal(false);

    // Reset Form
    setProjectName("");
    setPhaseName("");
    setCostValue("");
    setUtilityValue("22");
    setStatusValue("En Revisión");

    // Success toast
    triggerNotification("¡Estimación creada exitosamente!");
  };

  const triggerNotification = (text: string) => {
    setNotification(text);
    setTimeout(() => {
      setNotification("");
    }, 4000);
  };

  const calculateTotalQuoted = () => {
    return budgets.reduce((acc, curr) => acc + curr.cost, 0);
  };

  const calculateAverageMargin = () => {
    if (budgets.length === 0) return 0;
    const sum = budgets.reduce((acc, curr) => acc + curr.utility, 0);
    return (sum / budgets.length).toFixed(1);
  };

  const filteredBudgets = budgets.filter(b => 
    b.project.toLowerCase().includes(searchTerm.toLowerCase()) ||
    b.phase.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      {/* Toast Notification */}
      <AnimatePresence>
        {notification && (
          <motion.div
            initial={{ opacity: 0, y: -20, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, scale: 0.9 }}
            className="fixed top-24 right-10 z-50 bg-primary/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-lg"
          >
            <CheckCircle2 className="w-5 h-5 text-primary" />
            <span className="text-xs font-black uppercase tracking-wider">{notification}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Header Section */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Presupuestos y Estimaciones</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestione sus costos operativos y márgenes de utilidad en tiempo real</p>
        </div>
        <button 
          onClick={() => setShowCreateModal(true)}
          className="glass-button-primary bg-primary border border-primary text-white px-8 py-5 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
        >
          <Plus className="w-4 h-4" /> Crear Estimación
        </button>
      </div>

      {/* Bento Grid Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {/* Stat Card 1 */}
        <motion.div 
          whileHover={{ y: -5 }}
          className="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52 relative overflow-hidden group"
        >
          <div className="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-full blur-xl"></div>
          <div>
            <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Total Cotizado</span>
            <div className="flex items-baseline gap-3 mt-4">
              <h3 className="text-3xl font-black italic tracking-tighter">Q{calculateTotalQuoted().toLocaleString("es-GT")}</h3>
              <span className="text-[10px] font-black text-primary bg-primary/20 px-2 py-0.5 rounded-lg">+12%</span>
            </div>
          </div>
          <div>
            <div className="w-full bg-white/5 h-2 rounded-full overflow-hidden mb-2">
              <div className="bg-primary h-full rounded-full" style={{ width: "70%" }}></div>
            </div>
            <p className="text-[9px] font-bold text-white/30 uppercase tracking-wider">Cumplimiento del pipeline</p>
          </div>
        </motion.div>

        {/* Stat Card 2 (Highlight Margin) */}
        <motion.div 
          whileHover={{ y: -10 }}
          className="bg-gradient-to-br from-primary to-indigo-950 p-8 rounded-[40px] flex flex-col justify-between h-52 relative overflow-hidden shadow-2xl shadow-primary/20"
        >
          <div className="absolute -right-10 -top-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
          <div>
            <span className="text-[10px] font-black text-white/80 uppercase tracking-[0.25em]">Margen Promedio</span>
            <div className="flex items-baseline gap-3 mt-4">
              <h3 className="text-4xl font-black italic tracking-tighter">{calculateAverageMargin()}%</h3>
              <TrendingUp className="w-5 h-5 text-white/80" />
            </div>
          </div>
          <div>
            <p className="text-xs font-black uppercase tracking-wider text-white/90">Objetivo General: 22%</p>
            <p className="text-[10px] font-bold text-white/50 tracking-wide mt-2">Por encima del objetivo fijado por dirección</p>
          </div>
        </motion.div>

        {/* Stat Card 3 */}
        <motion.div 
          whileHover={{ y: -5 }}
          className="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52 relative overflow-hidden"
        >
          <div className="absolute top-0 right-0 w-24 h-24 bg-red-500/5 rounded-full blur-xl"></div>
          <div>
            <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Pendientes de Aprobación</span>
            <div className="flex items-baseline gap-3 mt-4">
              <h3 className="text-3xl font-black italic tracking-tighter">18</h3>
              <span className="text-[9px] font-black bg-rose-500/20 text-rose-400 px-2.5 py-0.5 rounded-lg uppercase tracking-wider">Urgente</span>
            </div>
          </div>
          <div className="flex items-center justify-between">
            <div className="flex -space-x-2.5">
              <div className="w-8 h-8 rounded-full border border-slate-900 bg-indigo-500 flex items-center justify-center text-[10px] font-extrabold">A1</div>
              <div className="w-8 h-8 rounded-full border border-slate-900 bg-pink-500 flex items-center justify-center text-[10px] font-extrabold">F2</div>
              <div className="w-8 h-8 rounded-full border border-slate-900 bg-amber-500 flex items-center justify-center text-[10px] font-extrabold">JV</div>
            </div>
            <p className="text-[9px] font-extrabold text-white/30 uppercase tracking-widest">En espera</p>
          </div>
        </motion.div>

        {/* Stat Card 4 */}
        <motion.div 
          whileHover={{ y: -5 }}
          className="glass-card p-8 rounded-[40px] border border-white/5 flex flex-col justify-between h-52"
        >
          <div>
            <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Materiales Indexados</span>
            <div className="flex items-baseline gap-3 mt-4">
              <h3 className="text-3xl font-black italic tracking-tighter">94%</h3>
            </div>
          </div>
          <div>
            <p className="text-[10px] text-white/50 leading-relaxed font-bold">
              Tipos actualizados automáticamente hoy a las <strong>08:00 AM</strong>.
            </p>
          </div>
        </motion.div>
      </div>

      {/* Main Sections (Table & Breakdown) */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-10 items-start">
        {/* Main Table Section (Col-Span 2) */}
        <div className="lg:col-span-2 space-y-6">
          <div className="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
            <div className="p-10 border-b border-white/5 bg-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
              <div>
                <h3 className="text-2xl font-black text-white italic uppercase tracking-tighter">Presupuestos Activos</h3>
                <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Estimaciones vigentes por proyecto y fase</p>
              </div>
              <div className="relative">
                <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
                <input 
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder="Filtrar por proyecto..."
                  className="glass-input pl-11 pr-5 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-56 text-white placeholder:text-white/20"
                />
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                    <th className="px-10 py-6">Proyecto</th>
                    <th className="px-10 py-6">Fecha</th>
                    <th className="px-10 py-6 text-right">Costo Total</th>
                    <th className="px-10 py-6 text-center">Beneficio (Utilidad)</th>
                    <th className="px-10 py-6 text-right">Estado</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/5">
                  <AnimatePresence initial={false}>
                    {filteredBudgets.length === 0 ? (
                      <tr>
                        <td colSpan={5} className="px-10 py-16 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                          No hay presupuestos activos que coincidan con la búsqueda.
                        </td>
                      </tr>
                    ) : (
                      filteredBudgets.map((b) => (
                        <motion.tr 
                          key={b.id}
                          initial={{ opacity: 0 }}
                          animate={{ opacity: 1 }}
                          exit={{ opacity: 0 }}
                          className="hover:bg-white/5 transition-all duration-300 cursor-pointer group"
                        >
                          <td className="px-10 py-6">
                            <h5 className="font-extrabold text-base text-white tracking-tight uppercase italic">{b.project}</h5>
                            <p className="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">ID: {b.id} • Fase: {b.phase}</p>
                          </td>
                          <td className="px-10 py-6 text-xs text-white/50 font-bold">
                            {b.date}
                          </td>
                          <td className="px-10 py-6 text-right font-black italic text-base text-white">
                            Q{b.cost.toLocaleString("es-GT", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                          </td>
                          <td className="px-10 py-6 text-center">
                            <span className="bg-primary/20 text-primary border border-primary/25 px-3.5 py-1.5 rounded-xl text-xs font-black italic">
                              {b.utility}%
                            </span>
                          </td>
                          <td className="px-10 py-6 text-right">
                            <span className={`px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border ${
                              b.status === 'Aprobado' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                              b.status === 'En Revisión' ? 'bg-primary/10 text-primary border-primary/20' :
                              'bg-white/5 text-white/40 border-white/10'
                            }`}>
                              {b.status}
                            </span>
                          </td>
                        </motion.tr>
                      ))
                    )}
                  </AnimatePresence>
                </tbody>
              </table>
            </div>

            <div className="p-8 border-t border-white/5 bg-black/10 flex justify-between items-center text-[10px] font-black text-white/20 uppercase tracking-widest">
              <span>Mostrando {filteredBudgets.length} de {budgets.length} elementos</span>
              <button 
                onClick={() => triggerNotification("Filtrado avanzado próximamente disponible")}
                className="hover:text-primary transition-colors cursor-pointer"
              >
                Ver todos
              </button>
            </div>
          </div>
        </div>

        {/* Detailed Cost Breakdown (Right Column) */}
        <div className="lg:col-span-1 space-y-8">
          
          {/* Breakdown Card */}
          <div className="glass-card p-10 rounded-[44px] border-l-4 border-primary space-y-8">
            <div>
              <h3 className="text-xl font-black italic uppercase tracking-tighter">Desglose de Costos</h3>
              <p className="text-[10px] font-black text-white/35 uppercase tracking-widest mt-1">Gastos presupuestales globales</p>
            </div>

            {/* Categories */}
            <div className="space-y-6">
              {/* Category 1 */}
              <div className="space-y-3">
                <div className="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                  <span className="flex items-center gap-2 text-white/60">
                    <Wrench className="w-3.5 h-3.5 text-primary" />
                    Materiales
                  </span>
                  <span className="font-black text-white">Q680,400.00</span>
                </div>
                <div className="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                  <div className="bg-primary h-full rounded-full" style={{ width: "55%" }}></div>
                </div>
                <p className="text-[9px] font-bold text-white/30 uppercase tracking-widest">Alerta: +4.2% inflación acero estructural</p>
              </div>

              {/* Category 2 */}
              <div className="space-y-3">
                <div className="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                  <span className="flex items-center gap-2 text-white/60">
                    <Users className="w-3.5 h-3.5 text-primary" />
                    Mano de Obra
                  </span>
                  <span className="font-black text-white">Q340,200.00</span>
                </div>
                <div className="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                  <div className="bg-primary h-full rounded-full" style={{ width: "30%" }}></div>
                </div>
                <p className="text-[9px] font-bold text-white/30 uppercase tracking-widest">Certificación y seguridad técnica</p>
              </div>

              {/* Category 3 */}
              <div className="space-y-3">
                <div className="flex justify-between items-center text-xs font-extrabold uppercase tracking-wide">
                  <span className="flex items-center gap-2 text-white/60">
                    <Award className="w-3.5 h-3.5 text-primary" />
                    Equipos y Maquinaria
                  </span>
                  <span className="font-black text-white">Q120,150.00</span>
                </div>
                <div className="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                  <div className="bg-primary h-full rounded-full" style={{ width: "10%" }}></div>
                </div>
                <p className="text-[9px] font-bold text-white/30 uppercase tracking-widest">Ahorro detectado: Eficiencia renting</p>
              </div>

              {/* Operating Margin Total */}
              <div className="space-y-3 pt-6 border-t border-white/5">
                <div className="flex justify-between items-center">
                  <span className="text-xs font-black uppercase tracking-wider text-primary italic">Margen de Caja</span>
                  <span className="text-lg font-black italic text-primary">Q99,250.00</span>
                </div>
                <div className="w-full bg-primary/20 h-2 rounded-full">
                  <div className="bg-primary h-full rounded-full" style={{ width: "100%" }}></div>
                </div>
              </div>
            </div>
          </div>

          {/* Quick Action Analysis */}
          <div className="glass-card p-10 rounded-[44px] border border-white/5 bg-gradient-to-br from-indigo-950/20 to-transparent relative overflow-hidden group">
            <div className="relative z-10 space-y-3">
              <h4 className="text-lg font-black italic uppercase tracking-wider text-white">Análisis de Mercado</h4>
              <p className="text-xs text-white/40 leading-relaxed font-bold">
                Tenemos nuevos precios verificados en cementeras y aceros para actualizar rápidamente sus márgenes de ganancia.
              </p>
              <button 
                onClick={() => triggerNotification("Base de datos de proveedores actualizada")}
                className="mt-6 text-[10px] font-black uppercase tracking-[0.2em] text-primary flex items-center gap-2 group-hover:translate-x-1.5 transition-transform cursor-pointer"
              >
                Actualizar Base de Precios <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Visual Chart Trends & IA Suggestions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
        
        {/* Trend Graph Visualization */}
        <div className="lg:col-span-2 glass-card p-10 rounded-[48px] border border-white/5 space-y-8">
          <div className="flex flex-col md:flex-row justify-between md:items-center gap-4">
            <div>
              <h3 className="text-2xl font-black italic uppercase tracking-tighter text-white">Costos Totales vs. Presupuestado</h3>
              <p className="text-[10px] font-black text-white/30 uppercase tracking-widest mt-1">Comparación periódica Enero - Abril</p>
            </div>
            <div className="flex items-center gap-6">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-primary"></div>
                <span className="text-[10px] font-black uppercase tracking-widest text-white/40">Presupuestado</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded-full bg-rose-500"></div>
                <span className="text-[10px] font-black uppercase tracking-widest text-white/40">Real</span>
              </div>
            </div>
          </div>

          {/* Render custom styled vertical bars */}
          <div className="pt-8 pb-4 h-64 flex items-end gap-12 md:gap-16 border-l border-b border-white/5 pl-6 relative">
            
            {/* Grid guides */}
            <div className="absolute left-0 right-0 top-1/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>
            <div className="absolute left-0 right-0 top-2/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>
            <div className="absolute left-0 right-0 top-3/4 h-[1px] bg-white/[0.02] border-dashed pointer-events-none"></div>

            {graphData.map((data, idx) => (
              <div key={idx} className="flex-grow flex flex-col items-center h-full justify-end gap-3 group0">
                <div className="w-full flex items-end h-full gap-2 px-1 max-w-[80px]">
                  
                  {/* Budget bar */}
                  <div className="flex-1 bg-primary/20 hover:bg-primary border border-primary/20 rounded-t-xl group-hover:scale-105 transition-all text-center relative" style={{ height: `${data.budget}%` }}>
                    <span className="absolute -top-7 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-[10px] font-black bg-slate-900 border border-white/10 px-1 py-0.5 rounded text-white">{data.budget}%</span>
                  </div>

                  {/* Real bar */}
                  <div className="flex-1 bg-rose-500/20 hover:bg-rose-500 border border-rose-500/20 rounded-t-xl group-hover:scale-102 transition-all relative" style={{ height: `${data.real}%` }}>
                    <span className="absolute -top-7 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 text-[10px] font-black bg-slate-900 border border-white/10 px-1 py-0.5 rounded text-rose-400">{data.real}%</span>
                  </div>
                </div>
                <span className="text-[10px] font-black uppercase tracking-widest text-white/40 mt-1">{data.month}</span>
              </div>
            ))}
          </div>
        </div>

        {/* AI Suggestion Box */}
        <div className="lg:col-span-1 bg-gradient-to-br from-indigo-950 to-slate-950 border border-primary/30 p-10 rounded-[48px] flex flex-col justify-between h-full shadow-2xl relative overflow-hidden text-center">
          <div className="absolute -top-10 -right-10 w-28 h-28 bg-primary/10 rounded-full blur-2xl"></div>
          <div className="flex pt-4 flex-col items-center gap-4">
            <div className="bg-primary/20 p-5 rounded-full text-primary border border-white/10 shadow-xl shadow-primary/20">
              <Sparkles className="w-8 h-8 animate-pulse" />
            </div>
            <h3 className="text-xl font-black italic uppercase tracking-tighter text-white">Sugerencia IA</h3>
            <p className="text-xs text-white/50 leading-relaxed font-semibold">
              Identificamos un potencial ahorro de hasta un <strong>8.5% en la logística global</strong> de la Fase Residencial "La Sierra" ajustando y programando las rutas de acarreo.
            </p>
          </div>
          <button 
            onClick={() => triggerNotification("Sugerencias aplicadas, simulando optimización.")}
            className="mt-8 w-full glass-button-primary bg-primary border border-primary py-4 rounded-3xl text-xs font-black uppercase tracking-widest text-white shadow-2xl"
          >
            Aplicar Sugerencias
          </button>
        </div>
      </div>

      {/* Create Estimate Modal */}
      <AnimatePresence>
        {showCreateModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowCreateModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Crear Estimación</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Diseñar nueva cotización de costos de proyecto</p>

              <form onSubmit={handleCreateEstimate} className="space-y-6">
                
                {/* Project Name */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre del Proyecto</label>
                  <input 
                    type="text"
                    required
                    value={projectName}
                    onChange={(e) => setProjectName(e.target.value)}
                    placeholder="E.g. Proyecto Altiplano - Lote 5"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                {/* Phase */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Fase / Categoría</label>
                  <input 
                    type="text"
                    value={phaseName}
                    onChange={(e) => setPhaseName(e.target.value)}
                    placeholder="E.g. Cimentación / Estructura"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                <div className="grid grid-cols-2 gap-6">
                  {/* Cost total */}
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Costo Estimado (Q)</label>
                    <input 
                      type="number"
                      required
                      step="0.01"
                      value={costValue}
                      onChange={(e) => setCostValue(e.target.value)}
                      placeholder="0.00"
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
                    />
                  </div>

                  {/* Profit margin */}
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Margen de Utilidad (%)</label>
                    <input 
                      type="number"
                      value={utilityValue}
                      onChange={(e) => setUtilityValue(e.target.value)}
                      placeholder="22"
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
                    />
                  </div>
                </div>

                {/* Initial Status */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Estado del Presupuesto</label>
                  <div className="flex bg-white/5 border border-white/10 rounded-2xl p-1">
                    {["Borrador", "En Revisión"].map((st) => (
                      <button 
                        key={st}
                        type="button"
                        onClick={() => setStatusValue(st as any)}
                        className={`flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all ${statusValue === st ? 'bg-primary text-white shadow-md' : 'text-white/40'}`}
                      >
                        {st}
                      </button>
                    ))}
                  </div>
                </div>

                {/* Action CTA */}
                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                  >
                    Confirmar Estimación
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowCreateModal(false)}
                    className="px-8 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-sm font-bold text-white/50"
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
