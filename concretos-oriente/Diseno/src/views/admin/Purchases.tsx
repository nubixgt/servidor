import { ShoppingBag, CreditCard, ShoppingCart, Calendar, ArrowUpRight, Search, FileText, ChevronRight, MoreVertical, Plus, Truck, CheckCircle2 } from "lucide-react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

export default function Purchases() {
  const [activeTab, setActiveTab] = useState("orders");

  const orders = [
    { id: "PO-2024-001", supplier: "Cementos Pro", date: "May 15, 2024", total: "Q12,450.00", status: "Enviado", items: 3, priority: "Alta" },
    { id: "PO-2024-002", supplier: "Aceros de Guate", date: "May 12, 2024", total: "Q45,200.00", status: "Pendiente", items: 5, priority: "Crítica" },
    { id: "PO-2023-998", supplier: "Eléctricos Fuentes", date: "May 08, 2024", total: "Q3,100.00", status: "Completado", items: 2, priority: "Media" },
    { id: "PO-2023-997", supplier: "Amanco Guatemala", date: "May 05, 2024", total: "Q8,600.00", status: "Completado", items: 8, priority: "Baja" },
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Módulo de Compras</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Aprovisionamiento y órdenes de suministro</p>
        </div>
        <button className="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
          <Plus className="w-5 h-5" />
          Nueva Órden (PO)
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        {[
          { label: "Presupuesto Mensual", val: "Q250k", sub: "Disponibilidad: Q42k", icon: CreditCard },
          { label: "Órdenes Activas", val: "14", sub: "4 Urgentes", icon: ShoppingBag },
          { label: "En Tránsito", val: "06", sub: "Llegan hoy: 2", icon: Truck },
          { label: "Ahorro YTD", val: "12.4%", sub: "Q18,200 proyectado", icon: CheckCircle2 },
        ].map((stat, i) => (
          <div key={i} className="glass-card p-8 rounded-[32px] border border-white/5">
            <div className="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-primary mb-6">
              <stat.icon className="w-5 h-5" />
            </div>
            <p className="text-[10px] font-black text-white/30 uppercase tracking-widest mb-1">{stat.label}</p>
            <h4 className="text-2xl font-black text-white italic uppercase">{stat.val}</h4>
            <p className="text-[10px] font-bold text-white/20 uppercase tracking-widest mt-2">{stat.sub}</p>
          </div>
        ))}
      </div>

      <section className="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
        <div className="flex border-b border-white/5 bg-white/5 backdrop-blur-3xl px-12 pt-8">
          {["orders", "requisitions", "returns"].map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`relative px-8 py-6 text-xs font-black uppercase tracking-widest transition-all ${
                activeTab === tab ? "text-white" : "text-white/30 hover:text-white/60"
              }`}
            >
              {tab === "orders" ? "Órdenes de Compra" : tab === "requisitions" ? "Requisiciones" : "Devoluciones"}
              {activeTab === tab && (
                <motion.div layoutId="purchase-underline" className="absolute bottom-0 left-0 w-full h-1 bg-primary rounded-t-full shadow-[0_0_15px_#6366f1]" />
              )}
            </button>
          ))}
        </div>

        <div className="p-12 overflow-x-auto">
          <table className="w-full text-left">
            <thead>
              <tr className="text-[10px] font-black text-white/20 uppercase tracking-[0.3em]">
                <th className="pb-8 px-4">Referencia PO</th>
                <th className="pb-8 px-4">Proveedor</th>
                <th className="pb-8 px-4">Fecha</th>
                <th className="pb-8 px-4">Items</th>
                <th className="pb-8 px-4 text-right">Valor Total</th>
                <th className="pb-8 px-4">Prioridad</th>
                <th className="pb-8 px-4">Estado</th>
                <th className="pb-8 px-4"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-white/5">
              {orders.map((po, i) => (
                <tr key={i} className="hover:bg-white/5 transition-all group cursor-pointer">
                  <td className="py-10 px-4">
                    <p className="font-black text-white italic text-lg uppercase tracking-tight">{po.id}</p>
                  </td>
                  <td className="py-10 px-4">
                    <p className="text-sm font-bold text-white uppercase italic">{po.supplier}</p>
                  </td>
                  <td className="py-10 px-4">
                    <div className="flex items-center gap-3 text-white/40">
                      <Calendar className="w-4 h-4" />
                      <span className="text-xs font-bold">{po.date}</span>
                    </div>
                  </td>
                  <td className="py-10 px-4 text-center">
                    <span className="text-xs font-black text-white italic">{po.items} Unidades</span>
                  </td>
                  <td className="py-10 px-4 text-right">
                    <p className="font-black text-white italic text-lg">{po.total}</p>
                  </td>
                  <td className="py-10 px-4">
                    <span className={`px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border ${
                      po.priority === 'Crítica' ? 'border-tertiary/20 text-tertiary bg-tertiary/10' : 
                      po.priority === 'Alta' ? 'border-orange-500/20 text-orange-400 bg-orange-500/10' :
                      'border-white/10 text-white/40 bg-white/5'
                    }`}>
                      {po.priority}
                    </span>
                  </td>
                  <td className="py-10 px-4">
                    <div className="flex items-center gap-3">
                      <div className={`w-2 h-2 rounded-full ${po.status === 'Completado' ? 'bg-primary shadow-[0_0_8px_#6366f1]' : po.status === 'Enviado' ? 'bg-orange-500' : 'bg-white/20'}`}></div>
                      <span className="text-xs font-black text-white italic uppercase">{po.status}</span>
                    </div>
                  </td>
                  <td className="py-10 px-4 text-right">
                    <button className="w-12 h-12 rounded-xl border border-white/10 flex items-center justify-center text-white/20 hover:text-white hover:bg-white/10 transition-all">
                      <ChevronRight className="w-6 h-6" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}
