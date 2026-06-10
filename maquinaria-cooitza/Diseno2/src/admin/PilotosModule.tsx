import React, { useState, useEffect } from "react";
import { 
  Users, 
  UserPlus, 
  Check, 
  Edit, 
  Trash2 
} from "lucide-react";
import { motion } from "motion/react";

interface Pilot {
  id: string;
  name: string;
  phone: string;
  assignedMachines: string[];
  status: "En Turno" | "Descanso";
}

interface PilotosModuleProps {
  onPilotsChange?: (count: number, activeCount: number, restingCount: number) => void;
}

export default function PilotosModule({ onPilotsChange }: PilotosModuleProps) {
  const [pilots, setPilots] = useState<Pilot[]>([]);
  const [editPilotId, setEditPilotId] = useState<string | null>(null);
  const [pilotForm, setPilotForm] = useState({
    name: "",
    phone: "",
    assignedMachines: [] as string[],
    status: "En Turno" as "En Turno" | "Descanso"
  });

  // Load from localStorage
  useEffect(() => {
    const saved = localStorage.getItem("cooitza_pilotos");
    if (saved) {
      const data = JSON.parse(saved);
      setPilots(data);
      triggerSync(data);
    } else {
      const defaultPilots: Pilot[] = [
        { id: "p1", name: "Ricardo Valdivia", phone: "+502 5901 2234", assignedMachines: ["Excavadora CAT 320", "Grúa RT765E-2"], status: "En Turno" },
        { id: "p2", name: "Elena Soto", phone: "+502 4120 8899", assignedMachines: ["Bulldozer D6K2"], status: "Descanso" },
        { id: "p3", name: "Marcos Peña", phone: "+502 3341 7766", assignedMachines: ["Motoniveladora 140K", "Cargador Frontal 950K"], status: "En Turno" },
      ];
      setPilots(defaultPilots);
      localStorage.setItem("cooitza_pilotos", JSON.stringify(defaultPilots));
      triggerSync(defaultPilots);
    }
  }, []);

  const triggerSync = (updated: Pilot[]) => {
    if (onPilotsChange) {
      const active = updated.filter(p => p.status === "En Turno").length;
      const resting = updated.filter(p => p.status === "Descanso").length;
      onPilotsChange(updated.length, active, resting);
    }
  };

  const persistPilots = (updated: Pilot[]) => {
    setPilots(updated);
    localStorage.setItem("cooitza_pilotos", JSON.stringify(updated));
    triggerSync(updated);
  };

  const handleSavePilot = (e: React.FormEvent) => {
    e.preventDefault();
    if (!pilotForm.name.trim()) return;

    if (editPilotId) {
      const updated = pilots.map(p => p.id === editPilotId ? { ...p, ...pilotForm } : p);
      persistPilots(updated);
      setEditPilotId(null);
    } else {
      const newPilot: Pilot = {
        id: "p_" + Date.now(),
        ...pilotForm
      };
      persistPilots([newPilot, ...pilots]);
    }
    setPilotForm({ name: "", phone: "", assignedMachines: [], status: "En Turno" });
  };

  const handleEditPilot = (p: Pilot) => {
    setEditPilotId(p.id);
    setPilotForm({ name: p.name, phone: p.phone, assignedMachines: p.assignedMachines, status: p.status });
  };

  const handleDeletePilot = (id: string) => {
    if (window.confirm("¿Está seguro de remover a este piloto asignado?")) {
      const updated = pilots.filter(p => p.id !== id);
      persistPilots(updated);
    }
  };

  const toggleMachineAssignment = (machineName: string) => {
    const isAlreadyAssigned = pilotForm.assignedMachines.includes(machineName);
    if (isAlreadyAssigned) {
      setPilotForm({ ...pilotForm, assignedMachines: pilotForm.assignedMachines.filter(m => m !== machineName) });
    } else {
      setPilotForm({ ...pilotForm, assignedMachines: [...pilotForm.assignedMachines, machineName] });
    }
  };

  return (
    <motion.div 
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex flex-col gap-6"
    >
      {/* Tab identity Header */}
      <div className="mb-2 border-l-4 border-[#0054A3] pl-3">
        <span className="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">RECURSOS HUMANOS</span>
        <h2 className="font-display text-3xl font-black text-on-surface mt-0.5">Administración de Pilotos</h2>
        <p className="text-xs text-on-surface-variant font-medium mt-1">Gestión de personal operativo y asignación de maquinaria pesada.</p>
      </div>

      {/* Bento Grid Layout layout with registration forms and registered list side-by-side */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        {/* Registration form block */}
        <section className="lg:col-span-5 bg-white border border-[#cbd5e1] p-6 shadow-sm">
          <div className="flex items-center gap-2 mb-4 border-b pb-2">
            <UserPlus className="text-[#0054A3] w-5 h-5" />
            <h3 className="font-display text-xs font-bold uppercase text-on-surface">
              {editPilotId ? "Modificar Datos de Piloto" : "Registro de Nuevo Piloto"}
            </h3>
          </div>

          <form className="space-y-4" onSubmit={handleSavePilot}>
            <div>
              <label className="text-on-surface-variant text-[11px] font-bold block mb-1 uppercase tracking-wider">Nombre Completo</label>
              <input 
                type="text"
                value={pilotForm.name}
                onChange={(e) => setPilotForm({ ...pilotForm, name: e.target.value })}
                placeholder="Ej. Ricardo Valdivia"
                className="w-full px-3 py-2 border border-[#cbd5e1] text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] transition-colors bg-white outline-none"
                required
              />
            </div>

            <div>
              <label className="text-on-surface-variant text-[11px] font-bold block mb-1 uppercase tracking-wider">Teléfono de Contacto</label>
              <input 
                type="tel"
                value={pilotForm.phone}
                onChange={(e) => setPilotForm({ ...pilotForm, phone: e.target.value })}
                placeholder="Ej: +502 5901 2234"
                className="w-full px-3 py-2 border border-[#cbd5e1] text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] transition-colors bg-white outline-none"
              />
            </div>

            <div>
              <label className="text-on-surface-variant text-[11px] font-bold block mb-1 uppercase tracking-wider">Maquinarias Autorizadas / Asignadas</label>
              <div className="grid grid-cols-1 gap-1.5 p-3 bg-slate-50 border border-[#cbd5e1] max-h-44 overflow-y-auto">
                {[
                  "Excavadora CAT 320",
                  "Bulldozer D6K2",
                  "Grúa RT765E-2",
                  "Cargador Frontal 950K",
                  "Motoniveladora 140K",
                  "Retroexcavadora John Deere",
                  "Camión Cisterna Hino"
                ].map((machineItemName) => {
                  const isChecked = pilotForm.assignedMachines.includes(machineItemName);
                  return (
                    <label key={machineItemName} className="flex items-center gap-3 cursor-pointer group text-xs text-on-surface-variant select-none">
                      <input 
                        type="checkbox"
                        checked={isChecked}
                        onChange={() => toggleMachineAssignment(machineItemName)}
                        className="w-4 h-4 rounded-sm border-[#cbd5e1] text-[#0054A3] focus:ring-0"
                      />
                      <span className="group-hover:text-[#0054A3] transition-colors">{machineItemName}</span>
                    </label>
                  );
                })}
              </div>
            </div>

            <div className="pt-2">
              <button 
                type="submit"
                className="w-full bg-[#0054A3] hover:bg-[#004586] text-white font-display text-xs font-bold py-3 uppercase tracking-wider flex items-center justify-center gap-2 cursor-pointer transition-colors"
              >
                <Check size={14} />
                <span>{editPilotId ? "Confirmar Edición" : "Guardar Piloto"}</span>
              </button>

              {editPilotId && (
                <button 
                  type="button"
                  onClick={() => {
                    setEditPilotId(null);
                    setPilotForm({ name: "", phone: "", assignedMachines: [], status: "En Turno" });
                  }}
                  className="w-full text-center text-xs text-red-650 hover:underline mt-2 cursor-pointer"
                >
                  Cancelar Edición
                </button>
              )}
            </div>
          </form>
        </section>

        {/* Registered pilot list */}
        <section className="lg:col-span-7 flex flex-col gap-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2 text-on-surface">
              <Users className="text-[#0054A3] w-5 h-5" />
              <h3 className="font-display text-xs font-bold uppercase">Pilotos Registrados</h3>
            </div>
            <span className="bg-[#0054A3]/10 text-[#0054A3] border border-[#0054A3]/10 px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest">
              Total Cooitzá: {pilots.length}
            </span>
          </div>

          <div className="space-y-2">
            {pilots.length === 0 ? (
              <div className="bg-white border border-[#cbd5e1] p-8 text-center text-slate-400 italic">
                Ningún piloto registrado actualmente
              </div>
            ) : (
              pilots.map((p) => (
                <div key={p.id} className="bg-white border border-[#cbd5e1] p-4 flex items-center gap-4 hover:border-[#0054A3] transition-colors shadow-sm">
                  
                  {/* Avatar */}
                  <div className="w-10 h-10 bg-[#0054A3]/5 text-[#0054A3] rounded-full flex items-center justify-center font-display font-bold">
                    {p.name.substring(0, 2).toUpperCase()}
                  </div>

                  {/* Middle details */}
                  <div className="flex-grow min-w-0">
                    <h4 className="font-display font-bold text-sm text-[#0054A3] mb-1">{p.name}</h4>
                    <div className="flex flex-wrap gap-1">
                      {p.assignedMachines && p.assignedMachines.length > 0 ? (
                        p.assignedMachines.map((mItem) => (
                          <span key={mItem} className="text-[9px] bg-slate-100 text-slate-700 px-1.5 py-0.5 border border-slate-200">
                            {mItem}
                          </span>
                        ))
                      ) : (
                        <span className="text-[10px] text-slate-400 italic">Ninguna asignación</span>
                      )}
                    </div>
                  </div>

                  {/* Status toggle actions */}
                  <div className="text-right hidden sm:block shrink-0">
                    <p className="font-mono text-xs text-on-surface-variant font-semibold">{p.phone || "Sin Teléfono"}</p>
                    <button 
                      type="button"
                      onClick={() => {
                        const updated = pilots.map(item => item.id === p.id ? { ...item, status: (item.status === "En Turno" ? "Descanso" : "En Turno") as any } : item);
                        persistPilots(updated);
                      }}
                      className={`inline-block mt-1 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-sm hover:scale-95 transition-all text-left ${
                        p.status === "En Turno" ? "bg-emerald-100 text-emerald-800" : "bg-amber-100 text-amber-800"
                      }`}
                    >
                      ● {p.status}
                    </button>
                  </div>

                  {/* Operational CRUD tools */}
                  <div className="flex flex-col gap-1 shrink-0">
                    <button 
                      type="button"
                      onClick={() => handleEditPilot(p)}
                      className="text-[#0054A3] hover:bg-slate-100 p-1.5 rounded transition-colors"
                      title="Editar"
                    >
                      <Edit size={14} />
                    </button>
                    <button 
                      type="button"
                      onClick={() => handleDeletePilot(p.id)}
                      className="text-red-650 hover:bg-red-50 p-1.5 rounded transition-colors"
                      title="Eliminar piloto"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>

                </div>
              ))
            )}
          </div>
        </section>

      </div>
    </motion.div>
  );
}
