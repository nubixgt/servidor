import { Package, AlertTriangle, ArrowUpRight, Search, Filter, MoreVertical, Plus, History, Truck, Boxes, Thermometer } from "lucide-react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

export default function Inventory() {
  const [selectedItem, setSelectedItem] = useState<any>(null);

  const metrics = [
    { label: "Valor Total Stock", value: "Q4.8M", change: "+5.2%", icon: Boxes, color: "primary" },
    { label: "Artículos Críticos", value: "12", change: "-2 esta semana", icon: AlertTriangle, color: "tertiary" },
    { label: "Órdenes Pendientes", value: "08", change: "6 en tránsito", icon: Truck, color: "primary" },
  ];

  const inventoryItems = [
    { id: "MAT-001", name: "Cemento Portland Tipo I", cat: "Materiales Base", stock: 1250, unit: "Sacos", price: "Q85.00", total: "Q106,250", status: "Óptimo", color: "green", supplier: "Cementos Progreso" },
    { id: "MAT-082", name: "Varilla Corrugada 3/8", cat: "Acero", stock: 84, unit: "Quintales", price: "Q380.00", total: "Q31,920", status: "Bajo Stock", color: "orange", supplier: "Aceros de Guate" },
    { id: "MAT-154", name: "Pintura Acrílica Blanca", cat: "Acabados", stock: 12, unit: "Galones", price: "Q125.00", total: "Q1,500", status: "Crítico", color: "red", supplier: "Pinturas Volcán" },
    { id: "MAT-201", name: "Tubería PVC 2' - 160PSI", cat: "Fontanería", stock: 450, unit: "Tubos", price: "Q45.00", total: "Q20,250", status: "Óptimo", color: "green", supplier: "Amanco Guatemala" },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Control de Inventario</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión centralizada de suministros y materiales</p>
        </div>
        <button className="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
          <Plus className="w-5 h-5" />
          Registrar Entrada
        </button>
      </div>

      {/* Metrics Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
        {metrics.map((m, i) => (
          <motion.div 
            key={i}
            whileHover={{ y: -5 }}
            className="glass-card p-10 rounded-[40px] border border-white/5 group relative overflow-hidden"
          >
            <div className={`absolute top-0 right-0 w-32 h-32 bg-${m.color}/10 blur-[60px] rounded-full translate-x-10 -translate-y-10 group-hover:bg-${m.color}/20 transition-all`}></div>
            <div className="relative z-10 flex items-start justify-between">
              <div>
                <p className="text-[10px] font-black text-white/30 uppercase tracking-[0.3em] mb-4">{m.label}</p>
                <h3 className="text-4xl font-black text-white italic tracking-tighter">{m.value}</h3>
                <div className="flex items-center gap-2 mt-4">
                  <span className={`text-[10px] font-black px-2 py-1 rounded-lg ${m.color === 'primary' ? 'bg-primary/20 text-primary' : 'bg-tertiary/20 text-tertiary'}`}>
                    {m.change}
                  </span>
                </div>
              </div>
              <div className={`w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-${m.color === 'primary' ? 'primary' : 'tertiary'} shadow-2xl`}>
                <m.icon className="w-7 h-7" />
              </div>
            </div>
          </motion.div>
        ))}
      </div>

      {/* Main Inventory Section */}
      <section className="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
        <div className="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
          <div className="relative flex-1 max-w-lg">
            <Search className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
            <input 
              type="text" 
              placeholder="Buscar por código, nombre o categoría..." 
              className="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
            />
          </div>
          <div className="flex items-center gap-4">
            <button className="h-14 px-8 rounded-2xl border border-white/10 flex items-center gap-3 text-xs font-black text-white/40 uppercase tracking-widest hover:bg-white/5 transition-all">
              <Filter className="w-5 h-5" /> Filtros
            </button>
            <button className="h-14 px-8 rounded-2xl bg-white/5 border border-white/10 flex items-center gap-3 text-xs font-black text-white uppercase tracking-widest hover:bg-white/10 transition-all">
              Reporte de Stock
            </button>
          </div>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">
                <th className="px-12 py-8">Recurso / Código</th>
                <th className="px-12 py-8">Categoría</th>
                <th className="px-12 py-8">Disponibilidad</th>
                <th className="px-12 py-8 text-right">Valor en Libros</th>
                <th className="px-12 py-8">Proveedor</th>
                <th className="px-12 py-8"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {inventoryItems.map((item, i) => (
                <tr 
                  key={i} 
                  onClick={() => setSelectedItem(item)}
                  className="hover:bg-white/5 transition-all cursor-pointer group"
                >
                  <td className="px-12 py-10">
                    <div className="flex items-center gap-5">
                      <div className="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                        <Package className="w-6 h-6" />
                      </div>
                      <div>
                        <p className="font-black text-lg text-white italic tracking-tighter uppercase">{item.name}</p>
                        <p className="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mt-1">ID: {item.id}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-12 py-10">
                    <span className="text-xs font-bold text-white/40 uppercase tracking-widest">{item.cat}</span>
                  </td>
                  <td className="px-12 py-10">
                    <div className="space-y-3">
                      <div className="flex items-center justify-between gap-10">
                        <span className="text-sm font-black text-white italic">{item.stock} {item.unit}</span>
                        <span className={`text-[10px] font-black uppercase tracking-widest ${
                          item.color === 'green' ? 'text-primary' : 
                          item.color === 'orange' ? 'text-orange-400' : 'text-tertiary'
                        }`}>
                          {item.status}
                        </span>
                      </div>
                      <div className="w-32 h-1.5 bg-white/5 rounded-full overflow-hidden">
                        <div 
                          className={`h-full ${
                            item.color === 'green' ? 'bg-primary' : 
                            item.color === 'orange' ? 'bg-orange-500' : 'bg-tertiary'
                          } shadow-[0_0_10px_currentColor]`}
                          style={{ width: `${item.color === 'green' ? 85 : item.color === 'orange' ? 25 : 10}%` }}
                        ></div>
                      </div>
                    </div>
                  </td>
                  <td className="px-12 py-10 text-right">
                    <p className="font-black text-white italic text-lg">{item.total}</p>
                    <p className="text-[10px] font-bold text-white/30 uppercase tracking-widest mt-1">P/U: {item.price}</p>
                  </td>
                  <td className="px-12 py-10">
                    <p className="text-sm font-bold text-white uppercase italic">{item.supplier}</p>
                  </td>
                  <td className="px-12 py-10 text-right">
                    <button className="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white/20 hover:text-white hover:bg-white/10 transition-all">
                      <MoreVertical className="w-5 h-5" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      {/* Item Detail Modal */}
      <AnimatePresence>
        {selectedItem && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setSelectedItem(null)}
              className="absolute inset-0 bg-black/80 backdrop-blur-sm"
            ></motion.div>
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 20 }}
              className="relative w-full max-w-4xl glass-card rounded-[56px] overflow-hidden border border-white/10 shadow-[0_0_100px_rgba(0,0,0,0.5)] bg-slate-950"
            >
              <div className="flex h-full max-h-[85vh]">
                <div className="flex-1 p-12 overflow-y-auto custom-scrollbar">
                  <div className="flex justify-between items-start mb-12">
                    <div>
                      <span className="text-[10px] font-black uppercase tracking-[0.4em] text-primary mb-3 block">Detalle de Recurso</span>
                      <h2 className="text-5xl font-black text-white italic uppercase tracking-tighter">{selectedItem.name}</h2>
                      <p className="text-white/40 font-bold uppercase tracking-widest mt-2">{selectedItem.id} • {selectedItem.cat}</p>
                    </div>
                    <button onClick={() => setSelectedItem(null)} className="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-white/40 hover:text-white">
                      <Plus className="rotate-45 w-8 h-8" />
                    </button>
                  </div>

                  <div className="grid grid-cols-2 gap-8 mb-12">
                    <div className="glass-card p-8 rounded-[32px] border border-white/5">
                      <p className="text-[10px] font-black uppercase tracking-widest text-white/20 mb-4 flex items-center gap-2">
                        <Boxes className="w-4 h-4" /> Existencia Actual
                      </p>
                      <h4 className="text-4xl font-black text-white italic">{selectedItem.stock} {selectedItem.unit}</h4>
                    </div>
                    <div className="glass-card p-8 rounded-[32px] border border-white/5">
                      <p className="text-[10px] font-black uppercase tracking-widest text-white/20 mb-4 flex items-center gap-2">
                        <Thermometer className="w-4 h-4" /> Salud de Stock
                      </p>
                      <div className="flex items-center gap-3">
                        <div className={`w-4 h-4 rounded-full ${
                          selectedItem.color === 'green' ? 'bg-primary shadow-[0_0_10px_#6366f1]' : 
                          selectedItem.color === 'orange' ? 'bg-orange-500 shadow-[0_0_10px_#f97316]' : 
                          'bg-tertiary shadow-[0_0_10px_#f43f5e]'
                        }`}></div>
                        <span className="text-2xl font-black text-white italic uppercase tracking-tight">{selectedItem.status}</span>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-10">
                    <div>
                      <h5 className="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 flex items-center gap-3">
                        <div className="w-8 h-[1px] bg-white/10"></div> Historial de Movimientos
                      </h5>
                      <div className="space-y-4">
                        {[
                          { event: "Ingreso de Lote", qty: "+500", date: "May 12, 2024", user: "Admin", icon: ArrowUpRight },
                          { event: "Salida a Proyecto Skyline", qty: "-120", date: "May 10, 2024", user: "Supervisor X", icon: History },
                          { event: "Ajuste de Auditoría", qty: "-4", date: "May 08, 2024", user: "Auditor", icon: History },
                        ].map((log, i) => (
                          <div key={i} className="flex items-center justify-between py-5 border-b border-white/5 group">
                            <div className="flex items-center gap-5">
                              <div className={`w-10 h-10 rounded-xl flex items-center justify-center ${log.qty.startsWith('+') ? 'bg-primary/10 text-primary' : 'bg-white/5 text-white/30'}`}>
                                <log.icon className="w-5 h-5" />
                              </div>
                              <div>
                                <p className="text-sm font-bold text-white uppercase italic">{log.event}</p>
                                <p className="text-[10px] font-black text-white/20 uppercase tracking-widest">{log.date} • {log.user}</p>
                              </div>
                            </div>
                            <span className={`text-lg font-black italic tracking-tighter ${log.qty.startsWith('+') ? 'text-primary' : 'text-white'}`}>
                              {log.qty} {selectedItem.unit}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>

                <div className="w-1/3 bg-white/[0.02] border-l border-white/5 p-12">
                  <div className="space-y-12">
                    <div>
                      <h5 className="text-[10px] font-black uppercase tracking-[0.3em] text-white/20 mb-6 font-black">Información de Suministro</h5>
                      <div className="space-y-6">
                        <div className="space-y-2">
                          <p className="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Proveedor Principal</p>
                          <p className="text-sm font-black text-white uppercase italic">{selectedItem.supplier}</p>
                        </div>
                        <div className="space-y-2">
                          <p className="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Tiempo de Reposición</p>
                          <p className="text-sm font-black text-white uppercase italic">3 a 5 días hábiles</p>
                        </div>
                        <div className="space-y-2">
                          <p className="text-[9px] font-black text-white/30 uppercase tracking-[0.2em]">Ubicación Almacén</p>
                          <p className="text-sm font-black text-white uppercase italic">Bodega Central - Pasillo B2</p>
                        </div>
                      </div>
                    </div>

                    <div className="flex flex-col gap-4">
                      <button className="w-full glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl shadow-primary/20">
                        Generar Orden de Compra
                      </button>
                      <button className="w-full py-5 rounded-2xl bg-white/5 border border-white/10 text-white/40 font-black text-sm uppercase tracking-widest hover:text-white transition-all">
                        Editar Información
                      </button>
                    </div>
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
