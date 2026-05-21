import { useState, useRef, FormEvent, ChangeEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  Landmark, 
  CreditCard, 
  CloudUpload, 
  Sparkles, 
  Search, 
  CheckCircle2, 
  AlertTriangle, 
  Plus, 
  ChevronLeft, 
  ChevronRight, 
  FileDown, 
  Check, 
  HelpCircle,
  TrendingDown,
  X,
  FileSpreadsheet,
  DollarSign
} from "lucide-react";

interface Transaction {
  id: string;
  date: string;
  bankDesc: string;
  detail: string;
  refSystem: string;
  isLinked: boolean;
  amount: number;
  type: "in" | "out";
  status: "pending" | "matched" | "manual";
}

export default function BankConciliation() {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedPeriod, setSelectedPeriod] = useState("Últimos 30 días");
  const [selectedStatus, setSelectedStatus] = useState<"all" | "pending" | "matched" | "manual">("pending");
  const [showAddModal, setShowAddModal] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Initial transactions
  const [transactions, setTransactions] = useState<Transaction[]>([
    { 
      id: "982130021", 
      date: "12 Oct 2026", 
      bankDesc: "CONCRETOS DEL NORTE SA", 
      detail: "ID: 982130021", 
      refSystem: "Factura #F-4421", 
      isLinked: true, 
      amount: 2450.00, 
      type: "out", 
      status: "pending" 
    },
    { 
      id: "SUC-055", 
      date: "11 Oct 2026", 
      bankDesc: "TRANSFERENCIA DEPÓSITO", 
      detail: "SUCURSAL 055", 
      refSystem: "No se encontró coincidencia", 
      isLinked: false, 
      amount: 15000.00, 
      type: "in", 
      status: "pending" 
    },
    { 
      id: "PAY-41", 
      date: "10 Oct 2026", 
      bankDesc: "PAGO NOMINA SEM 41", 
      detail: "OPERACIONES CAMPO", 
      refSystem: "Planilla de Sueldos", 
      isLinked: true, 
      amount: 8230.10, 
      type: "out", 
      status: "pending" 
    },
    { 
      id: "OC-2023-99", 
      date: "09 Oct 2026", 
      bankDesc: "FERRETERIA INDUSTRIAL", 
      detail: "COMPRA HERRAMIENTAS", 
      refSystem: "OC #2023-99", 
      isLinked: true, 
      amount: 125.50, 
      type: "out", 
      status: "pending" 
    },
    { 
      id: "SUP-108", 
      date: "05 Oct 2026", 
      bankDesc: "ACEROS DE GUATE", 
      detail: "SUMINISTRO VARILLAS", 
      refSystem: "Factura #F-9021", 
      isLinked: true, 
      amount: 15400.00, 
      type: "out", 
      status: "matched" 
    },
    { 
      id: "CLI-992", 
      date: "02 Oct 2026", 
      bankDesc: "ANTICIPO SKYLINE TOWER", 
      detail: "TRANSF. CLIENTE", 
      refSystem: "Contrato #H-12", 
      isLinked: true, 
      amount: 75000.00, 
      type: "in", 
      status: "matched" 
    }
  ]);

  // New Transaction Form State
  const [newDesc, setNewDesc] = useState("");
  const [newDetail, setNewDetail] = useState("MANUAL SUITE");
  const [newAmount, setNewAmount] = useState("");
  const [newType, setNewType] = useState<"in" | "out">("out");

  // Filter logic
  const filteredTransactions = transactions.filter(tx => {
    const matchesSearch = 
      tx.bankDesc.toLowerCase().includes(searchTerm.toLowerCase()) ||
      tx.refSystem.toLowerCase().includes(searchTerm.toLowerCase()) ||
      tx.detail.toLowerCase().includes(searchTerm.toLowerCase());
    
    if (selectedStatus === "all") return matchesSearch;
    return tx.status === selectedStatus && matchesSearch;
  });

  // Action methods
  const handleConfirmMatch = (id: string) => {
    setTransactions(prev => prev.map(tx => {
      if (tx.id === id) {
        return { ...tx, status: "matched" };
      }
      return tx;
    }));
  };

  const handleManualAction = (id: string) => {
    setTransactions(prev => prev.map(tx => {
      if (tx.id === id) {
        return { ...tx, status: "manual", refSystem: "Asignación Manual OK" };
      }
      return tx;
    }));
  };

  const handleAutoConciliation = () => {
    // Audit Match AI triggers status shift for all pending with links
    setTransactions(prev => prev.map(tx => {
      if (tx.status === "pending" && tx.isLinked) {
        return { ...tx, status: "matched" };
      }
      return tx;
    }));
  };

  const handleCreateTransaction = (e: FormEvent) => {
    e.preventDefault();
    if (!newDesc || !newAmount) return;

    const newTx: Transaction = {
      id: Math.random().toString(36).substring(2, 9).toUpperCase(),
      date: "Hoy, " + new Date().toLocaleDateString("es-GT", { day: "numeric", month: "short" }),
      bankDesc: newDesc,
      detail: newDetail,
      refSystem: "Registro Manual",
      isLinked: false,
      amount: parseFloat(newAmount),
      type: newType,
      status: "manual"
    };

    setTransactions([newTx, ...transactions]);
    setShowAddModal(false);
    // Reset form
    setNewDesc("");
    setNewDetail("MANUAL SUITE");
    setNewAmount("");
    setNewType("out");
  };

  // Mock file loading
  const handleFileUploadClick = () => {
    fileInputRef.current?.click();
  };

  const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      // Create artificial new transaction
      const uploadedTx: Transaction = {
        id: "EXT-" + Math.floor(Math.random() * 90000 + 10000),
        date: "Hoy, " + new Date().toLocaleDateString("es-GT", { day: "numeric", month: "short" }),
        bankDesc: "CEMENTERA SUR CO-LTD",
        detail: "EXTRACTO EXPORTE BANCO",
        refSystem: "Factura #F-5582",
        isLinked: true,
        amount: 18450.00,
        type: "out",
        status: "pending"
      };

      setTransactions([uploadedTx, ...transactions]);
      alert("Extracto de banco cargado exitósamente! Se agregó 'CEMENTERA SUR CO-LTD' para confirmación.");
    }
  };

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      {/* Search & Intro */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Bancos y Conciliación</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión inteligente de saldos y extractos contables</p>
        </div>
        <div className="flex items-center gap-4 relative z-20">
          <div className="relative">
            <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
            <input 
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="glass-input pl-12 pr-6 py-4 rounded-2xl text-xs w-64 uppercase tracking-widest placeholder:text-white/20 text-white font-bold"
              placeholder="Buscar transacción..."
              type="text"
            />
          </div>
        </div>
      </div>

      {/* Summary Row */}
      <section className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {/* Account Card 1 */}
        <motion.div
          whileHover={{ y: -6, scale: 1.02 }}
          className="glass-card p-10 rounded-[40px] flex flex-col justify-between h-52 cursor-pointer group relative overflow-hidden"
        >
          <div className="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
          <div className="flex justify-between items-start">
            <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">Banco Santander</span>
            <div className="bg-primary/20 p-4 rounded-2xl text-primary border border-white/10 shadow-lg shadow-primary/20">
              <Landmark className="w-6 h-6" />
            </div>
          </div>
          <div className="mt-4">
            <p className="text-white/40 text-[10px] font-bold uppercase tracking-widest">Cuenta Corriente Operativa</p>
            <h3 className="text-4xl font-black tracking-tighter italic text-white mt-2">Q450,230.00</h3>
            <div className="mt-4 flex items-center bg-primary/15 text-primary border border-primary/20 px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex gap-2">
              <Check className="w-3.5 h-3.5" />
              Conciliado hace 2h
            </div>
          </div>
        </motion.div>

        {/* Account Card 2 */}
        <motion.div
          whileHover={{ y: -6, scale: 1.02 }}
          className="glass-card p-10 rounded-[40px] flex flex-col justify-between h-52 cursor-pointer group relative overflow-hidden"
        >
          <div className="absolute top-0 right-0 w-32 h-32 bg-orange-500/5 rounded-full blur-2xl pointer-events-none"></div>
          <div className="flex justify-between items-start">
            <span className="text-[10px] font-black text-white/30 uppercase tracking-[0.3em]">BBVA Corporate</span>
            <div className="bg-orange-500/20 p-4 rounded-2xl text-orange-400 border border-white/10 shadow-lg shadow-orange-500/10">
              <CreditCard className="w-6 h-6" />
            </div>
          </div>
          <div className="mt-4">
            <p className="text-white/40 text-[10px] font-bold uppercase tracking-widest">Tarjeta de Compras Materiales</p>
            <h3 className="text-4xl font-black tracking-tighter italic text-white mt-2">Q12,450.15</h3>
            <div className="mt-4 flex items-center bg-orange-500/15 text-orange-400 border border-orange-500/20 px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest inline-flex gap-2">
              <AlertTriangle className="w-3.5 h-3.5" />
              {transactions.filter(t => t.status === "pending").length} Pendientes
            </div>
          </div>
        </motion.div>

        {/* Bento Upload */}
        <motion.div
          whileHover={{ scale: 1.02 }}
          onClick={handleFileUploadClick}
          className="glass-card p-10 rounded-[40px] flex flex-col items-center justify-center text-center cursor-pointer border-2 border-dashed border-white/10 hover:border-primary/40 hover:bg-white/5 transition-all group"
        >
          <input 
            type="file" 
            ref={fileInputRef} 
            onChange={handleFileChange} 
            className="hidden" 
            accept=".csv,.pdf"
          />
          <div className="bg-primary/20 text-primary p-5 rounded-full mb-4 shadow-lg shadow-primary/10 group-hover:scale-110 transition-transform duration-500">
            <CloudUpload className="w-8 h-8" />
          </div>
          <h4 className="text-lg font-black italic uppercase tracking-wider text-white">Cargar Extracto</h4>
          <p className="text-xs text-white/40 mt-2 max-w-xs font-medium">
            Sube o arrastra archivos PDF o CSV para iniciar la conciliación automatizada.
          </p>
        </motion.div>
      </section>

      {/* Main Reconciliation Engine */}
      <section className="grid grid-cols-1 lg:grid-cols-4 gap-10 items-start">
        {/* Sidebar Controls */}
        <div className="space-y-8 lg:col-span-1">
          {/* Filters Card */}
          <div className="glass-card p-8 rounded-[36px] border border-white/5 space-y-6">
            <h5 className="text-xs font-black uppercase tracking-widest text-white/50 border-b border-white/5 pb-3">Filtros</h5>
            
            <div className="space-y-2">
              <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Periodo Evaluado</label>
              <select 
                value={selectedPeriod}
                onChange={(e) => setSelectedPeriod(e.target.value)}
                className="w-full bg-white/5 border border-white/10 rounded-2xl text-xs font-bold py-3.5 px-4 text-white focus:outline-none focus:ring-2 focus:ring-primary/40 focus:border-transparent cursor-pointer"
              >
                <option value="Últimos 30 días" className="bg-slate-900 text-white">Últimos 30 días</option>
                <option value="Mes actual" className="bg-slate-900 text-white">Mes actual</option>
                <option value="Trimestre anterior" className="bg-slate-900 text-white">Trimestre anterior</option>
              </select>
            </div>

            <div className="space-y-4 pt-2">
              <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 block">Estado del Flujo</label>
              <div className="flex flex-col gap-2">
                {[
                  { id: "pending", label: "Pendientes", count: transactions.filter(t => t.status === "pending").length },
                  { id: "matched", label: "Sugeridos (Match)", count: transactions.filter(t => t.status === "matched").length },
                  { id: "manual", label: "Manuales", count: transactions.filter(t => t.status === "manual").length },
                  { id: "all", label: "Ver Todos", count: transactions.length },
                ].map((st) => (
                  <button
                    key={st.id}
                    onClick={() => setSelectedStatus(st.id as any)}
                    className={`w-full flex items-center justify-between px-5 py-3.5 rounded-2xl text-xs font-black uppercase tracking-wider transition-all duration-300 ${
                      selectedStatus === st.id 
                        ? "bg-primary text-white shadow-lg shadow-primary/20" 
                        : "bg-white/5 text-white/40 hover:text-white hover:bg-white/10"
                    }`}
                  >
                    <span>{st.label}</span>
                    <span className="text-[10px] bg-white/10 px-2 py-0.5 rounded-lg">{st.count}</span>
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* AI Feature Component */}
          <div className="glass-card p-10 rounded-[40px] border border-primary/20 relative overflow-hidden bg-gradient-to-br from-primary/10 via-transparent to-transparent flex flex-col justify-between">
            <div className="flex justify-between items-start mb-6">
              <div className="bg-primary/20 p-3 rounded-2xl text-primary border border-white/15">
                <Sparkles className="w-5 h-5 animate-pulse" />
              </div>
              <span className="text-[9px] font-black text-primary uppercase tracking-[0.25em] bg-primary/10 border border-primary/20 px-2.5 py-1 rounded-full">Inteligente</span>
            </div>
            <div>
              <h5 className="text-xl font-black italic uppercase tracking-tighter text-white">Contabilidad Smart Match</h5>
              <p className="text-xs font-medium text-white/50 mt-3 leading-relaxed">
                Nuestra IA analizó su cartola e indexó <strong>{transactions.filter(t => t.status === "pending" && t.isLinked).length} coincidencias precisas</strong> pendientes según montos e instructivo.
              </p>
              <button 
                onClick={handleAutoConciliation}
                disabled={transactions.filter(t => t.status === "pending" && t.isLinked).length === 0}
                className="mt-8 w-full glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl disabled:opacity-30 disabled:pointer-events-none hover:shadow-primary/30 hover:-translate-y-0.5 active:translate-y-0 transition-all text-center"
              >
                Conciliar Automáticamente
              </button>
            </div>
          </div>
        </div>

        {/* Transactions Table Column */}
        <div className="lg:col-span-3 space-y-8">
          <div className="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
            {/* Table Header Wrapper */}
            <div className="p-10 border-b border-white/5 bg-white/5 flex flex-col md:flex-row md:items-center justify-between gap-6">
              <div>
                <h4 className="text-2xl font-black text-white italic uppercase tracking-tighter">Transacciones</h4>
                <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">
                  Mostrando registros según el filtro: {selectedStatus.toUpperCase()}
                </p>
              </div>
              <button className="px-6 py-3.5 border border-white/15 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center gap-3 text-white/60 hover:text-white hover:bg-white/5 transition-all">
                <FileDown className="w-4 h-4" /> Exportar
              </button>
            </div>

            {/* Content Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5">
                    <th className="px-10 py-8">Fecha</th>
                    <th className="px-10 py-8">Descripción de Banco</th>
                    <th className="px-10 py-8">Vínculo al Sistema</th>
                    <th className="px-10 py-8 text-right">Monto</th>
                    <th className="px-10 py-8 text-center">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/5">
                  <AnimatePresence initial={false}>
                    {filteredTransactions.length === 0 ? (
                      <tr>
                        <td colSpan={5} className="px-10 py-20 text-center text-white/30 font-bold uppercase tracking-widest text-xs">
                          Ninguna transacción pendiente coincide con el filtro.
                        </td>
                      </tr>
                    ) : (
                      filteredTransactions.map((tx, idx) => (
                        <motion.tr 
                          key={tx.id}
                          initial={{ opacity: 0, y: 10 }}
                          animate={{ opacity: 1, y: 0 }}
                          exit={{ opacity: 0, height: 0 }}
                          className="hover:bg-white/5 transition-all duration-300 group"
                        >
                          {/* Date */}
                          <td className="px-10 py-8 font-semibold text-white/50 text-xs">
                            {tx.date}
                          </td>

                          {/* Bank Description */}
                          <td className="px-10 py-8">
                            <h5 className="font-extrabold text-base text-white tracking-tight uppercase italic">{tx.bankDesc}</h5>
                            <p className="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">ID: {tx.id} • {tx.detail}</p>
                          </td>

                          {/* Linked System Ref */}
                          <td className="px-10 py-8">
                            {tx.isLinked ? (
                              <div className="inline-flex items-center gap-2 bg-primary/10 border border-primary/20 px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-primary">
                                <CheckCircle2 className="w-3.5 h-3.5" />
                                {tx.refSystem}
                              </div>
                            ) : (
                              <div className="inline-flex items-center gap-2 bg-rose-500/10 border border-rose-500/25 px-3.5 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest text-rose-400">
                                <AlertTriangle className="w-3.5 h-3.5" />
                                Sin sugerencia
                              </div>
                            )}
                          </td>

                          {/* Amount */}
                          <td className={`px-10 py-8 text-right font-black italic text-xl ${tx.type === 'in' ? 'text-primary' : 'text-white'}`}>
                            {tx.type === 'in' ? '+' : '-'}Q{tx.amount.toLocaleString("es-GT", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                          </td>

                          {/* Action Buttons */}
                          <td className="px-10 py-8 text-center">
                            {tx.status === "pending" ? (
                              <div className="flex items-center justify-center gap-3">
                                {tx.isLinked ? (
                                  <button 
                                    onClick={() => handleConfirmMatch(tx.id)}
                                    className="bg-primary hover:bg-primary-container text-white text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-white/5 transition-all hover:scale-102 hover:shadow-lg hover:shadow-primary/20"
                                  >
                                    Confirmar Match
                                  </button>
                                ) : (
                                  <button 
                                    onClick={() => handleManualAction(tx.id)}
                                    className="bg-white/5 hover:bg-white/10 text-white/60 hover:text-white text-[9px] font-black uppercase tracking-widest px-4 py-2 rounded-xl border border-white/10 transition-all hover:scale-102"
                                  >
                                    Asignar Manual
                                  </button>
                                )}
                              </div>
                            ) : (
                              <div className="inline-flex items-center gap-2 text-primary bg-primary/10 px-3.5 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border border-primary/25">
                                <Check className="w-3.5 h-3.5" />
                                {tx.status === "matched" ? "Match Exitoso" : "Conciliado Manual"}
                              </div>
                            )}
                          </td>
                        </motion.tr>
                      ))
                    )}
                  </AnimatePresence>
                </tbody>
              </table>
            </div>

            {/* Table Footer Pagination */}
            <div className="p-10 border-t border-white/5 bg-black/20 flex flex-col md:flex-row justify-between items-center gap-6">
              <p className="text-[10px] font-black text-white/20 uppercase tracking-widest">
                Mostrando 1-{filteredTransactions.length} de {filteredTransactions.length} registros cargados
              </p>
              <div className="flex items-center gap-3">
                <button className="w-10 h-10 rounded-xl border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all">
                  <ChevronLeft className="w-5 h-5 text-white/40" />
                </button>
                <button className="w-10 h-10 rounded-xl bg-primary text-white font-black italic text-sm">1</button>
                <button className="w-10 h-10 rounded-xl border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all">
                  <ChevronRight className="w-5 h-5 text-white/40" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Manual Entry FAB Modal */}
      <button 
        onClick={() => setShowAddModal(true)}
        className="fixed bottom-12 right-12 w-20 h-20 rounded-[32px] glass-button-primary bg-primary border border-primary text-white shadow-2xl shadow-primary/40 flex items-center justify-center hover:scale-110 active:scale-95 transition-all z-40 group"
      >
        <Plus className="w-10 h-10 group-hover:rotate-90 transition-transform duration-500" />
      </button>

      {/* Manual Transaction Modal */}
      <AnimatePresence>
        {showAddModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/80 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowAddModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 10 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.2)]"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Nuevo Registro Manual</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Introducir transacción al flujo de conciliación</p>

              <form onSubmit={handleCreateTransaction} className="space-y-6">
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Descripción del Movimiento</label>
                  <input 
                    type="text"
                    required
                    value={newDesc}
                    onChange={(e) => setNewDesc(e.target.value)}
                    placeholder="E.g. COMPRA CEMENTOS GUATEMALA"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase"
                  />
                </div>

                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Monto en Quetzales (Q)</label>
                    <input 
                      type="number"
                      required
                      step="0.01"
                      value={newAmount}
                      onChange={(e) => setNewAmount(e.target.value)}
                      placeholder="0.00"
                      className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white placeholder:text-white/20"
                    />
                  </div>

                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Tipo de Operación</label>
                    <div className="flex bg-white/5 border border-white/10 rounded-2xl p-1">
                      <button 
                        type="button"
                        onClick={() => setNewType("out")}
                        className={`flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all ${newType === 'out' ? 'bg-white/10 text-white' : 'text-white/40'}`}
                      >
                        Salida (EGRESO)
                      </button>
                      <button 
                        type="button"
                        onClick={() => setNewType("in")}
                        className={`flex-1 py-3 text-[10px] font-black uppercase tracking-wider rounded-xl transition-all ${newType === 'in' ? 'bg-primary text-white shadow-md' : 'text-white/40'}`}
                      >
                        Entrada (INGRESO)
                      </button>
                    </div>
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Detalle / ID de Referencia</label>
                  <input 
                    type="text"
                    required
                    value={newDetail}
                    onChange={(e) => setNewDetail(e.target.value)}
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold text-white uppercase"
                  />
                </div>

                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                  >
                    Guardar Registro
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowAddModal(false)}
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
