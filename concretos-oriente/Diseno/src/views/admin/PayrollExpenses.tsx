import { useState, FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  Users, 
  Plus, 
  Search, 
  TrendingUp, 
  Calendar, 
  ChevronLeft, 
  ChevronRight, 
  Filter, 
  Download, 
  MoreVertical, 
  CheckCircle2, 
  Clock, 
  Lightbulb,
  Coins,
  Activity,
  X,
  CreditCard,
  Eye,
  CheckCircle,
  HelpCircle,
  AlertTriangle,
  Flame,
  FileSpreadsheet
} from "lucide-react";

interface EmployeePayroll {
  id: string;
  name: string;
  role: string;
  hours: number;
  baseSalary: number;
  bonuses: number;
  deductions: number;
  department: "Obra Civil" | "Maquinaria y Logística" | "Arquitectura & Diseño" | "Administración";
  status: "Procesado" | "Pendiente";
  initials: string;
}

export default function PayrollExpenses() {
  const [searchTerm, setSearchTerm] = useState("");
  const [deptFilter, setDeptFilter] = useState<string>("Todos los departamentos");
  const [showPayModal, setShowPayModal] = useState(false);
  const [showAddEmployeeModal, setShowAddEmployeeModal] = useState(false);
  const [notification, setNotification] = useState("");

  // New Employee state
  const [newName, setNewName] = useState("");
  const [newRole, setNewRole] = useState("");
  const [newDept, setNewDept] = useState<any>("Obra Civil");
  const [newHours, setNewHours] = useState("160");
  const [newBase, setNewBase] = useState("");
  const [newBonuses, setNewBonuses] = useState("0");
  const [newDeductions, setNewDeductions] = useState("0");

  const [employees, setEmployees] = useState<EmployeePayroll[]>([
    {
      id: "1",
      name: "Roberto Mendoza",
      role: "Ingeniero de Obra",
      hours: 160,
      baseSalary: 4200.00,
      bonuses: 350.00,
      deductions: 210.00,
      department: "Obra Civil",
      status: "Procesado",
      initials: "RM"
    },
    {
      id: "2",
      name: "Lucia Castillo",
      role: "Arquitecta Senior",
      hours: 152,
      baseSalary: 3800.00,
      bonuses: 120.00,
      deductions: 190.00,
      department: "Arquitectura & Diseño",
      status: "Pendiente",
      initials: "LC"
    },
    {
      id: "3",
      name: "Jorge Salazar",
      role: "Operador de Grúa",
      hours: 168,
      baseSalary: 2500.00,
      bonuses: 450.00,
      deductions: 125.00,
      department: "Maquinaria y Logística",
      status: "Procesado",
      initials: "JS"
    },
    {
      id: "4",
      name: "Mariana Paredes",
      role: "Asistente Administrativa",
      hours: 160,
      baseSalary: 1800.00,
      bonuses: 0.00,
      deductions: 90.00,
      department: "Administración",
      status: "Pendiente",
      initials: "MP"
    }
  ]);

  const [paymentHistory, setPaymentHistory] = useState([
    {
      id: "H1",
      title: "Cierre Septiembre - 2da Quincena",
      date: "Pagado el 30 Sep, 2026",
      count: 142,
      amount: 138400.00
    },
    {
      id: "H2",
      title: "Cierre Septiembre - 1ra Quincena",
      date: "Pagado el 15 Sep, 2026",
      count: 138,
      amount: 135210.00
    }
  ]);

  const showToast = (msg: string) => {
    setNotification(msg);
    setTimeout(() => {
      setNotification("");
    }, 4000);
  };

  const handleCreateEmployee = (e: FormEvent) => {
    e.preventDefault();
    if (!newName || !newRole || !newBase) return;

    const initials = newName.split(" ").map(w => w[0]).join("").substring(0, 2).toUpperCase();
    const newEmp: EmployeePayroll = {
      id: Math.random().toString(36).substring(2, 9),
      name: newName,
      role: newRole,
      hours: parseFloat(newHours) || 0,
      baseSalary: parseFloat(newBase) || 0,
      bonuses: parseFloat(newBonuses) || 0,
      deductions: parseFloat(newDeductions) || 0,
      department: newDept,
      status: "Pendiente",
      initials: initials || "EE"
    };

    setEmployees([...employees, newEmp]);
    setShowAddEmployeeModal(false);
    showToast(`Empleado ${newName} agregado a la planilla.`);

    // Reset Form
    setNewName("");
    setNewRole("");
    setNewDept("Obra Civil");
    setNewHours("160");
    setNewBase("");
    setNewBonuses("0");
    setNewDeductions("0");
  };

  // Mass Payment execution
  const executeMassPayments = () => {
    const updated = employees.map(emp => ({ ...emp, status: "Procesado" as const }));
    setEmployees(updated);
    
    // Add current run to history
    const totalAmount = updated.reduce((s, e) => s + (e.baseSalary + e.bonuses - e.deductions), 0);
    const newHistory = {
      id: Math.random().toString(),
      title: "Cierre Octubre - 1ra Quincena",
      date: "Pagado Hoy",
      count: updated.length,
      amount: totalAmount
    };

    setPaymentHistory([newHistory, ...paymentHistory]);
    setShowPayModal(false);
    showToast("¡Pagos masivos emitidos y procesados correctamente!");
  };

  // Calculations
  const totalBase = employees.reduce((s, e) => s + e.baseSalary, 0);
  const totalBonuses = employees.reduce((s, e) => s + e.bonuses, 0);
  const totalDeductions = employees.reduce((s, e) => s + e.deductions, 0);
  const totalPayrollGross = totalBase + totalBonuses - totalDeductions;

  const filteredEmployees = employees.filter(emp => {
    const matchesSearch = emp.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          emp.role.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesDept = deptFilter === "Todos los departamentos" || emp.department === deptFilter;
    return matchesSearch && matchesDept;
  });

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
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Planillas y Pagos</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Periodo Actual: 01 Oct — 15 Oct, 2026</p>
        </div>
        <div className="flex flex-wrap gap-4">
          <button 
            onClick={() => showToast("Planilla exportada a Excel")}
            className="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
          >
            <FileSpreadsheet className="w-4 h-4 text-primary" /> Exportar Reporte
          </button>
          
          <button 
            onClick={() => setShowAddEmployeeModal(true)}
            className="flex items-center gap-2 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
          >
            <Plus className="w-4 h-4 text-primary" /> Agregar Empleado
          </button>

          <button 
            onClick={() => setShowPayModal(true)}
            className="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
          >
            <CheckCircle className="w-4 h-4" /> Emitir Pagos Masivos
          </button>
        </div>
      </div>

      {/* Bento Grid Summary Card */}
      <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8">
        
        {/* Total Payroll Box */}
        <div className="col-span-1 md:col-span-2 bg-gradient-to-br from-primary via-primary/80 to-indigo-950 text-white p-10 rounded-[48px] shadow-2xl flex flex-col justify-between relative overflow-hidden h-52">
          <div className="absolute -right-10 -top-10 w-44 h-44 bg-white/10 rounded-full blur-3xl"></div>
          <div className="flex justify-between items-start">
            <span className="text-[10px] font-black opacity-80 uppercase tracking-[0.25em]">TOTAL NÓMINA NETO</span>
            <div className="bg-white/10 p-2.5 rounded-xl text-white">
              <Coins className="w-5 h-5" />
            </div>
          </div>
          <div>
            <h3 className="text-4xl font-black italic tracking-tighter">
              Q{totalPayrollGross.toLocaleString("es-GT", { minimumFractionDigits: 2 })}
            </h3>
            <div className="flex items-center gap-1.5 text-white/70 font-black text-[10px] tracking-wider uppercase mt-2">
              <TrendingUp className="w-3.5 h-3.5" />
              <span>+4.2% frente a quincena anterior</span>
            </div>
          </div>
        </div>

        {/* Bonuses and Extras */}
        <div className="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-52">
          <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Bonos y Extras</span>
          <div>
            <h3 className="text-3xl font-black italic tracking-tighter">Q{totalBonuses.toLocaleString("es-GT", { minimumFractionDigits: 2 })}</h3>
            <p className="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-2">Total bonos indexados</p>
          </div>
        </div>

        {/* Deductions Card */}
        <div className="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between h-52">
          <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.25em]">Deducciones Totales</span>
          <div>
            <h3 className="text-3xl font-black italic tracking-tighter text-rose-400">Q{totalDeductions.toLocaleString("es-GT", { minimumFractionDigits: 2 })}</h3>
            <p className="text-[10px] font-bold text-white/40 tracking-wider uppercase mt-2">Seguros, impuestos, préstamos</p>
          </div>
        </div>
      </div>

      {/* Employed Table list */}
      <div className="glass-card rounded-[48px] overflow-hidden border border-white/5 shadow-2xl">
        {/* Table Filters header */}
        <div className="p-10 border-b border-white/5 bg-white/5 flex flex-col sm:flex-row justify-between items-center gap-6">
          <div>
            <h3 className="text-2xl font-black text-white italic uppercase tracking-tighter">Listado de Empleados</h3>
            <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">Nómina individual habilitada para este periodo</p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            
            <div className="relative">
              <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
              <input 
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Buscar por rol o nombre..."
                className="glass-input pl-11 pr-5 py-3 rounded-xl text-xs uppercase font-extrabold tracking-wider w-56 text-white placeholder:text-white/20"
              />
            </div>

            <select 
              value={deptFilter}
              onChange={(e) => setDeptFilter(e.target.value)}
              className="bg-white/5 border border-white/10 p-3 text-xs rounded-xl text-white font-bold cursor-pointer uppercase focus:outline-none"
            >
              <option className="bg-slate-950 text-white">Todos los departamentos</option>
              <option className="bg-slate-950 text-white">Obra Civil</option>
              <option className="bg-slate-950 text-white">Maquinaria y Logística</option>
              <option className="bg-slate-950 text-white">Arquitectura & Diseño</option>
              <option className="bg-slate-950 text-white">Administración</option>
            </select>
          </div>
        </div>

        {/* Table representation */}
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                <th className="px-10 py-6">Empleado</th>
                <th className="px-10 py-6">Horas</th>
                <th className="px-10 py-6 text-right">Sueldo Base</th>
                <th className="px-10 py-6 text-right">Bonos</th>
                <th className="px-10 py-6 text-right text-rose-400">Deducciones</th>
                <th className="px-10 py-6 text-right">Total Neto</th>
                <th className="px-10 py-6 text-center">Estado</th>
                <th className="px-10 py-6"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              <AnimatePresence initial={false}>
                {filteredEmployees.length === 0 ? (
                  <tr>
                    <td colSpan={8} className="px-10 py-16 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                      No se encontraron empleados registrados bajo este criterio.
                    </td>
                  </tr>
                ) : (
                  filteredEmployees.map((emp) => (
                    <motion.tr 
                      key={emp.id}
                      initial={{ opacity: 0 }}
                      animate={{ opacity: 1 }}
                      exit={{ opacity: 0 }}
                      className="hover:bg-white/5 transition-all duration-300"
                    >
                      {/* Name Card */}
                      <td className="px-10 py-6">
                        <div className="flex items-center gap-4">
                          <div className="w-10 h-10 rounded-full bg-primary/20 text-primary font-black italic border border-white/10 shadow flex items-center justify-center text-sm">
                            {emp.initials}
                          </div>
                          <div>
                            <h5 className="font-extrabold text-base text-white tracking-tight uppercase italic">{emp.name}</h5>
                            <p className="text-[10px] font-bold text-white/35 tracking-wider uppercase mt-1">
                              {emp.role} • <span className="text-primary">{emp.department}</span>
                            </p>
                          </div>
                        </div>
                      </td>

                      {/* Hours */}
                      <td className="px-10 py-6 text-xs text-white/60 font-extrabold">
                        {emp.hours}h
                      </td>

                      {/* Base Salary */}
                      <td className="px-10 py-6 text-right font-black italic text-sm text-white/80">
                        Q{emp.baseSalary.toLocaleString("es-GT")}
                      </td>

                      {/* Bonuses */}
                      <td className="px-10 py-6 text-right font-black italic text-sm text-emerald-400">
                        +Q{emp.bonuses.toLocaleString("es-GT")}
                      </td>

                      {/* Deductions */}
                      <td className="px-10 py-6 text-right font-black italic text-sm text-rose-400">
                        -Q{emp.deductions.toLocaleString("es-GT")}
                      </td>

                      {/* Global Net */}
                      <td className="px-10 py-6 text-right font-black italic text-base text-white">
                        Q{(emp.baseSalary + emp.bonuses - emp.deductions).toLocaleString("es-GT", { minimumFractionDigits: 2 })}
                      </td>

                      {/* State status */}
                      <td className="px-10 py-6 text-center">
                        <span className={`px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border ${
                          emp.status === 'Procesado' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                          'bg-amber-500/10 text-amber-400 border-amber-500/20'
                        }`}>
                          {emp.status}
                        </span>
                      </td>

                      <td className="px-10 py-6 text-right">
                        <button 
                          onClick={() => showToast(`Detalle de planilla para: ${emp.name}`)}
                          className="p-2 text-white/30 hover:text-white transition-colors"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                      </td>
                    </motion.tr>
                  ))
                )}
              </AnimatePresence>
            </tbody>
          </table>
        </div>

        {/* Bottom index and pagination controls */}
        <div className="p-8 border-t border-white/5 bg-black/10 flex justify-between items-center text-[10px] font-black text-white/20 uppercase tracking-widest">
          <span>Mostrando {filteredEmployees.length} de {employees.length} empleados</span>
          <div className="flex gap-2">
            <button className="w-10 h-10 rounded-xl border border-white/5 bg-white/5 flex items-center justify-center">
              <ChevronLeft className="w-4 h-4" />
            </button>
            <button className="w-10 h-10 rounded-xl bg-primary text-white font-black italic text-xs">1</button>
            <button className="w-10 h-10 rounded-xl border border-white/5 bg-white/5 flex items-center justify-center">
              <ChevronRight className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Dept Distribution & History */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-10">
        
        {/* Department Progress charts */}
        <div className="glass-card p-10 rounded-[44px] border border-white/5 space-y-6">
          <h4 className="text-sm font-black uppercase tracking-widest text-white/60">Distribución por Departamento</h4>
          <div className="space-y-6">
            
            {/* Dept 1 */}
            <div className="space-y-2">
              <div className="flex justify-between font-bold text-xs uppercase tracking-wide">
                <span className="text-white/50">Obra Civil</span>
                <span className="font-extrabold text-white">Q82,400.00</span>
              </div>
              <div className="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
                <div className="bg-primary h-full rounded-full" style={{ width: "65%" }}></div>
              </div>
            </div>

            {/* Dept 2 */}
            <div className="space-y-2">
              <div className="flex justify-between font-bold text-xs uppercase tracking-wide">
                <span className="text-white/50">Maquinaria y Logística</span>
                <span className="font-extrabold text-white">Q34,120.00</span>
              </div>
              <div className="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
                <div className="bg-primary/60 h-full rounded-full" style={{ width: "35%" }}></div>
              </div>
            </div>

            {/* Dept 3 */}
            <div className="space-y-2">
              <div className="flex justify-between font-bold text-xs uppercase tracking-wide">
                <span className="text-white/50">Arquitectura & Diseño</span>
                <span className="font-extrabold text-white">Q18,060.00</span>
              </div>
              <div className="w-full bg-white/5 h-2.5 rounded-full overflow-hidden">
                <div className="bg-primary/30 h-full rounded-full" style={{ width: "20%" }}></div>
              </div>
            </div>
          </div>
        </div>

        {/* Historic logs list */}
        <div className="glass-card p-10 rounded-[44px] border border-white/5 flex flex-col justify-between">
          <div>
            <div className="flex justify-between items-center mb-6">
              <h4 className="text-sm font-black uppercase tracking-widest text-white/60">Historial de Pagos</h4>
              <Clock className="w-4 h-4 text-white/40" />
            </div>

            <div className="space-y-6">
              {paymentHistory.map((item) => (
                <div key={item.id} className="flex items-center gap-4">
                  <div className="w-10 h-10 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                    <CheckCircle className="w-5 h-5" />
                  </div>
                  <div className="flex-grow">
                    <p className="font-extrabold text-sm uppercase tracking-tight text-white">{item.title}</p>
                    <p className="text-[10px] text-white/30 font-bold tracking-wider mt-1 uppercase">{item.date} • {item.count} empleados</p>
                  </div>
                  <div className="text-right">
                    <p className="font-black italic text-sm text-white">Q{item.amount.toLocaleString("es-GT")}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <button 
            onClick={() => showToast("Cargando historial completo de planillas y egresos de ConstructPro.")}
            className="w-full mt-8 py-4 border border-white/10 rounded-2xl text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary/10 transition-colors"
          >
            Ver Historial Completo
          </button>
        </div>
      </div>

      {/* Massive Payments modal confirmation sheet */}
      <AnimatePresence>
        {showPayModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowPayModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-md glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Confirmar Pagos</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Estás por emitir pagos a {employees.length} empleados correspondientes al periodo actual</p>

              <div className="bg-white/5 border border-white/10 rounded-3xl p-6 mb-8 space-y-4 font-bold text-xs uppercase tracking-wide">
                <div className="flex justify-between">
                  <span className="text-white/40">Subtotal Nómina Básica</span>
                  <span className="text-white font-black">Q{totalBase.toLocaleString("es-GT")}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-white/40">Bonificaciones</span>
                  <span className="text-emerald-400 font-black">+Q{totalBonuses.toLocaleString("es-GT")}</span>
                </div>
                <div className="flex justify-between text-rose-400">
                  <span>Deducciones Aplicadas</span>
                  <span className="font-white font-black">-Q{totalDeductions.toLocaleString("es-GT")}</span>
                </div>
                <div className="pt-4 border-t border-white/10 flex justify-between items-baseline">
                  <span className="text-white/80 font-black">TOTAL A TRANSFERIR:</span>
                  <span className="text-xl font-black italic text-primary">Q{totalPayrollGross.toLocaleString("es-GT", { minimumFractionDigits: 2 })}</span>
                </div>
              </div>

              <div className="flex gap-4">
                <button 
                  onClick={executeMassPayments}
                  className="flex-grow glass-button-primary bg-primary border border-primary text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                >
                  Procesar Ahora
                </button>
                <button 
                  type="button"
                  onClick={() => setShowPayModal(false)}
                  className="px-6 py-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-extrabold text-white/50"
                >
                  Cancelar
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Add Employee modal view */}
      <AnimatePresence>
        {showAddEmployeeModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowAddEmployeeModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Agregar Empleado</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Ingresar nuevo colaborador a la planilla de ConstructPro</p>

              <form onSubmit={handleCreateEmployee} className="space-y-6">
                {/* Full name */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Nombre Completo</label>
                  <input 
                    type="text"
                    required
                    value={newName}
                    onChange={(e) => setNewName(e.target.value)}
                    placeholder="E.g. Juan Carlos López"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                {/* Role and Dept */}
                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Puesto / Rol administrativo</label>
                    <input 
                      type="text"
                      required
                      value={newRole}
                      onChange={(e) => setNewRole(e.target.value)}
                      placeholder="E.g. Supervisor de Obra"
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                    />
                  </div>

                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Departamento</label>
                    <select 
                      value={newDept}
                      onChange={(e) => setNewDept(e.target.value)}
                      className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
                    >
                      <option className="bg-slate-950 text-white">Obra Civil</option>
                      <option className="bg-slate-950 text-white">Maquinaria y Logística</option>
                      <option className="bg-slate-950 text-white">Arquitectura & Diseño</option>
                      <option className="bg-slate-950 text-white">Administración</option>
                    </select>
                  </div>
                </div>

                {/* Hours & Base Salary */}
                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Horas laboradas</label>
                    <input 
                      type="number"
                      required
                      value={newHours}
                      onChange={(e) => setNewHours(e.target.value)}
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
                    />
                  </div>

                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Sueldo Base (Q)</label>
                    <input 
                      type="number"
                      required
                      step="0.01"
                      value={newBase}
                      onChange={(e) => setNewBase(e.target.value)}
                      placeholder="0.00"
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
                    />
                  </div>
                </div>

                {/* Bonuses and Deductions */}
                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Bonos extraordinarios</label>
                    <input 
                      type="number"
                      step="0.01"
                      value={newBonuses}
                      onChange={(e) => setNewBonuses(e.target.value)}
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
                    />
                  </div>

                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Deducciones (Anticipos, ISR, IHSS)</label>
                    <input 
                      type="number"
                      step="0.01"
                      value={newDeductions}
                      onChange={(e) => setNewDeductions(e.target.value)}
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white"
                    />
                  </div>
                </div>

                {/* Action CTA */}
                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                  >
                    Confirmar Empleado
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowAddEmployeeModal(false)}
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
