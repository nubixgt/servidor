import { Building2, Phone, Mail, MapPin, Globe, ShieldCheck, MoreVertical, Plus, Filter, Search, Star } from "lucide-react";
import { useState } from "react";
import { motion, AnimatePresence } from "motion/react";

export default function Suppliers() {
  const [selectedSupplier, setSelectedSupplier] = useState<any>(null);

  const suppliers = [
    { 
      name: "Cementos Pro", 
      id: "SUP-001", 
      cat: "Materiales Base", 
      rating: 4.8, 
      status: "Preferido", 
      contact: "Carlos Ruiz", 
      tel: "+502 2300-4400", 
      email: "ventas@cementospro.gt",
      address: "Carretera al Salvador, Km 14",
      activeProjects: 3,
      reliability: "98%"
    },
    { 
      name: "Aceros de Guate", 
      id: "SUP-042", 
      cat: "Acero y Estructuras", 
      rating: 4.5, 
      status: "Activo", 
      contact: "Ana Morales", 
      tel: "+502 2315-9000", 
      email: "industrial@acerosgt.com",
      address: "Zona 12, Avenida Petapa",
      activeProjects: 5,
      reliability: "94%"
    },
    { 
      name: "Eléctricos Fuentes", 
      id: "SUP-112", 
      cat: "Instalaciones", 
      rating: 4.9, 
      status: "Socio Oro", 
      contact: "Jorge Fuentes", 
      tel: "+502 2201-3344", 
      email: "jorge@elecfuentes.gt",
      address: "Zona 4, Edificio Tec",
      activeProjects: 2,
      reliability: "100%"
    }
  ];

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-10">
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Directorio de Proveedores</h2>
          <p className="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Gestión de alianzas estratégicas y suministros</p>
        </div>
        <button className="glass-button-primary text-white py-4 px-10 rounded-2xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-3 shadow-2xl shadow-primary/20 hover:scale-105 transition-all">
          <Plus className="w-5 h-5" />
          Añadir Proveedor
        </button>
      </div>

      <section className="glass-card rounded-[56px] overflow-hidden border border-white/5 shadow-2xl">
        <div className="p-12 border-b border-white/5 bg-white/5 backdrop-blur-3xl flex flex-col md:flex-row md:items-center justify-between gap-8">
          <div className="relative flex-1 max-w-lg">
            <Search className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30" />
            <input 
              type="text" 
              placeholder="Buscar proveedores por nombre o especialidad..." 
              className="w-full glass-input rounded-2xl pl-14 pr-6 py-4 text-sm font-medium text-white outline-none focus:ring-2 focus:ring-primary/40 transition-all"
            />
          </div>
          <div className="flex items-center gap-4">
            <button className="h-14 px-8 rounded-2xl border border-white/10 flex items-center gap-3 text-xs font-black text-white/40 uppercase tracking-widest hover:bg-white/5 transition-all">
              <Filter className="w-5 h-5" /> Categorías
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-0 divide-x divide-y divide-white/5">
          {suppliers.map((sup, i) => (
            <motion.div 
              key={i} 
              onClick={() => setSelectedSupplier(sup)}
              whileHover={{ backgroundColor: "rgba(255,255,255,0.02)" }}
              className="p-12 cursor-pointer transition-all group"
            >
              <div className="flex justify-between items-start mb-8">
                <div className="w-16 h-16 rounded-3xl bg-white/5 border border-white/10 flex items-center justify-center text-primary group-hover:scale-110 transition-transform shadow-2xl">
                  <Building2 className="w-8 h-8" />
                </div>
                <div className="flex items-center gap-1.5 px-3 py-1 bg-white/5 rounded-full border border-white/5">
                  <Star className="w-3 h-3 text-yellow-500 fill-yellow-500" />
                  <span className="text-[10px] font-black text-white italic">{sup.rating}</span>
                </div>
              </div>

              <h4 className="text-2xl font-black text-white italic uppercase tracking-tighter mb-2">{sup.name}</h4>
              <p className="text-[10px] font-bold text-white/30 uppercase tracking-[0.2em] mb-8">{sup.cat}</p>

              <div className="space-y-4 mb-10">
                <div className="flex items-center gap-4 text-white/60">
                  <Phone className="w-4 h-4 text-primary" />
                  <span className="text-sm font-medium">{sup.tel}</span>
                </div>
                <div className="flex items-center gap-4 text-white/60">
                  <Mail className="w-4 h-4 text-primary" />
                  <span className="text-sm font-medium truncate">{sup.email}</span>
                </div>
              </div>

              <div className="flex items-center justify-between pt-8 border-t border-white/5">
                <span className={`px-4 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest border ${
                  sup.status.includes("Socio") ? "bg-primary/20 text-primary border-primary/20 shadow-[0_0_15px_#6366f130]" : "bg-white/5 text-white/40 border-white/5"
                }`}>
                  {sup.status}
                </span>
                <span className="text-[10px] font-black text-white/20 uppercase tracking-widest">{sup.reliability} Confiabilidad</span>
              </div>
            </motion.div>
          ))}
        </div>
      </section>

      {/* Supplier Modal Placeholder (similar to others) */}
      <AnimatePresence>
        {selectedSupplier && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setSelectedSupplier(null)}
              className="absolute inset-0 bg-black/80 backdrop-blur-sm"
            ></motion.div>
            <motion.div 
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.9, y: 20 }}
              className="relative w-full max-w-4xl glass-card rounded-[56px] p-12 border border-white/10"
            >
              <div className="flex items-start justify-between">
                <div className="flex gap-8">
                  <div className="w-24 h-24 rounded-[32px] bg-primary/20 flex items-center justify-center text-primary border border-white/10">
                    <Building2 className="w-12 h-12" />
                  </div>
                  <div>
                    <h2 className="text-5xl font-black text-white italic uppercase tracking-tighter">{selectedSupplier.name}</h2>
                    <p className="text-xl font-bold text-primary mt-2 uppercase tracking-widest">{selectedSupplier.cat}</p>
                    <div className="flex items-center gap-6 mt-6">
                      <div className="flex items-center gap-2">
                        <MapPin className="w-4 h-4 text-white/40" />
                        <span className="text-sm font-bold text-white/60">{selectedSupplier.address}</span>
                      </div>
                      <div className="flex items-center gap-2">
                        <ShieldCheck className="w-4 h-4 text-primary" />
                        <span className="text-sm font-bold text-primary italic">Proveedor Verificado</span>
                      </div>
                    </div>
                  </div>
                </div>
                <button onClick={() => setSelectedSupplier(null)} className="w-14 h-14 rounded-2xl bg-white/5 hover:bg-white/10 flex items-center justify-center transition-all border border-white/5">
                  <Plus className="rotate-45 w-8 h-8 text-white/40" />
                </button>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
                <div className="glass-card p-8 rounded-[32px] border border-white/5">
                  <p className="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Proyectos Activos</p>
                  <p className="text-4xl font-black text-white italic">{selectedSupplier.activeProjects}</p>
                </div>
                <div className="glass-card p-8 rounded-[32px] border border-white/5">
                  <p className="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Cumplimiento</p>
                  <p className="text-4xl font-black text-white italic">{selectedSupplier.reliability}</p>
                </div>
                <div className="glass-card p-8 rounded-[32px] border border-white/5">
                  <p className="text-[10px] font-black text-white/20 uppercase tracking-widest mb-4">Calificación</p>
                  <div className="flex items-center gap-3">
                    <p className="text-4xl font-black text-white italic">{selectedSupplier.rating}</p>
                    <Star className="w-6 h-6 text-yellow-500 fill-yellow-500" />
                  </div>
                </div>
              </div>

              <div className="mt-12 flex gap-4">
                <button className="flex-1 glass-button-primary py-5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-2xl">Nueva Orden de Compra</button>
                <button className="flex-1 py-5 rounded-2xl bg-white/5 border border-white/10 text-white font-black text-sm uppercase tracking-widest">Ver Catálogo Completo</button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
