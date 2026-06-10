import React, { useState } from "react";
import { 
  ClipboardSignature, 
  Wifi, 
  Database, 
  Loader2, 
  CheckCircle, 
  XSquare,
  ShieldAlert
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

interface TecnicoPilotoProps {
  currentUserFullName: string;
  onLogout: () => void;
}

interface PilotRegistration {
  pilot_name: string;
  pilot_phone: string;
  machinery: string[];
  registeredAt: string;
}

export default function TecnicoPiloto({ currentUserFullName, onLogout }: TecnicoPilotoProps) {
  const [pilotName, setPilotName] = useState("");
  const [pilotPhone, setPilotPhone] = useState("");
  const [selectedMachinery, setSelectedMachinery] = useState<string[]>([]);
  
  // Status states
  const [statusText, setStatusText] = useState("Listo para Transmitir");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [registrationSuccess, setRegistrationSuccess] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");

  const machineryOptions = [
    { id: "excavator-x1", label: "Excavadora Hidráulica X1-B" },
    { id: "crane-tower", label: "Grúa Torre Automatizada" },
    { id: "loader-industrial", label: "Cargador Frontal Industrial" },
    { id: "telemetry-node", label: "Nodo de Telemetría Móvil" }
  ];

  const handleCheckboxChange = (id: string) => {
    setSelectedMachinery(prev => 
      prev.includes(id) ? prev.filter(item => item !== id) : [...prev, id]
    );
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage("");

    if (!pilotName.trim()) {
      setErrorMessage("Por favor ingrese el nombre completo del piloto.");
      return;
    }
    if (!pilotPhone.trim()) {
      setErrorMessage("Por favor ingrese un teléfono de enlace válido.");
      return;
    }
    if (selectedMachinery.length === 0) {
      setErrorMessage("Debe seleccionar al menos una maquinaria autorizada.");
      return;
    }

    setIsSubmitting(true);
    setStatusText("Procesando Enlace...");

    setTimeout(() => {
      // Create new pilot record
      const newRegistration: PilotRegistration = {
        pilot_name: pilotName.toUpperCase().trim(),
        pilot_phone: pilotPhone.trim(),
        machinery: selectedMachinery,
        registeredAt: new Date().toLocaleString("es-GT")
      };

      // Retrieve existing pilots from localStorage and add new one
      try {
        const savedPilots = localStorage.getItem("cooitza_machinery_pilots");
        const currentPilots: PilotRegistration[] = savedPilots ? JSON.parse(savedPilots) : [];
        currentPilots.unshift(newRegistration);
        localStorage.setItem("cooitza_machinery_pilots", JSON.stringify(currentPilots));
      } catch (err) {
        console.error("Error saving pilot registration", err);
      }

      setIsSubmitting(false);
      setRegistrationSuccess(true);
      setStatusText("Transmitido de Forma Estable");

      setTimeout(() => {
        // Reset states
        setPilotName("");
        setPilotPhone("");
        setSelectedMachinery([]);
        setRegistrationSuccess(false);
        setStatusText("Listo para Transmitir");
      }, 3000);

    }, 1500);
  };

  return (
    <div 
      className="min-h-screen w-full flex flex-col items-center justify-center p-6 text-[#191c1d] relative font-sans"
      style={{
        backgroundImage: "radial-gradient(circle, #cbd5e1 1px, transparent 1px)",
        backgroundSize: "24px 24px",
        backgroundColor: "#f8f9fa"
      }}
    >
      {/* Visual Identity Title Anchor */}
      <div className="mb-8 text-center select-none">
        <h1 className="font-display text-3xl font-extrabold text-[#0054A3] uppercase tracking-wider mb-1">
          IndustrialMS
        </h1>
        <p className="font-display text-[10px] font-bold tracking-[0.12em] text-[#004586]/75 uppercase">
          Protocolo de Eficiencia Clínica v2.4
        </p>
      </div>

      <main className="w-full max-w-[500px] relative z-10">
        
        {/* Form Container */}
        <div className="bg-white border border-[#cbd5e1] p-8 relative shadow-sm flex flex-col gap-6">
          {/* Header Accent Line */}
          <div className="absolute top-0 left-0 w-full h-[3px] bg-[#FFD200]" />

          {/* Session state header matching Cooitzá exact design */}
          <div className="flex justify-between items-center bg-slate-100 -mx-8 -mt-8 px-8 py-3 border-b border-[#cbd5e1] select-none">
            <div className="flex items-center gap-2">
              <span className={`w-2.5 h-2.5 rounded-full ${registrationSuccess ? "bg-green-500 animate-pulse" : "bg-emerald-500 animate-pulse"}`} />
              <span className="font-display text-xs font-black text-[#0054A3] uppercase">
                Rol: Técnico Piloto
              </span>
            </div>
            <div className="flex items-center gap-4">
              <span className="font-sans text-xs text-[#004586] max-w-[150px] truncate text-right font-medium">
                {currentUserFullName}
              </span>
              <button 
                type="button"
                onClick={onLogout}
                className="font-display text-[11px] font-black uppercase text-red-650 hover:underline cursor-pointer"
              >
                Cerrar Sesión
              </button>
            </div>
          </div>

          <header className="mb-2 select-none border-b border-[#cbd5e1]/40 pb-4">
            <div className="flex items-center gap-2 mb-1.5">
              <span className="w-2 h-2 rounded-full bg-[#FFD200]" />
              <span className="font-display text-[11px] font-bold uppercase text-[#0054A3] tracking-wider">
                Estado Enlace: {statusText}
              </span>
            </div>
            
            <h2 className="font-display text-2xl font-black tracking-tight text-[#191c1d] uppercase">
              Registro de Piloto
            </h2>
            <p className="text-xs leading-relaxed text-[#004586] mt-2 font-medium">
              Gestione e inscriba la autorización de pilotos que despliegan operaciones técnico-industriales en campo.
            </p>
          </header>

          <form className="space-y-5" onSubmit={handleFormSubmit}>
            
            {/* Error alerts inside the form layout */}
            {errorMessage && (
              <div className="bg-red-50 border-l-4 border-red-500 p-3.5 text-xs text-red-800 font-sans flex items-center gap-2">
                <XSquare className="w-4 h-4 text-red-600 flex-shrink-0" />
                <span className="font-medium">{errorMessage}</span>
              </div>
            )}

            {/* Field: Name */}
            <div className="flex flex-col gap-1.5">
              <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block" htmlFor="pilot_name">
                Nombre Completo del Operador
              </label>
              <div className="relative group">
                <input 
                  id="pilot_name" 
                  name="pilot_name" 
                  type="text" 
                  required
                  placeholder="EJ. MARCO A. SANDOVAL"
                  value={pilotName}
                  onChange={(e) => setPilotName(e.target.value)}
                  className="w-full bg-slate-50 border border-[#cbd5e1] p-3 font-sans text-sm text-on-surface focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none appearance-none transition-all uppercase font-medium placeholder:text-slate-400"
                />
              </div>
            </div>

            {/* Field: Phone */}
            <div className="flex flex-col gap-1.5">
              <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block" htmlFor="pilot_phone">
                Teléfono de Enlace Directo
              </label>
              <div className="relative">
                <input 
                  id="pilot_phone" 
                  name="pilot_phone" 
                  type="tel" 
                  required
                  placeholder="+502 0000-0000"
                  value={pilotPhone}
                  onChange={(e) => setPilotPhone(e.target.value)}
                  className="w-full bg-slate-50 border border-[#cbd5e1] p-3 font-sans text-sm text-on-surface focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none appearance-none transition-all font-medium placeholder:text-slate-400"
                />
              </div>
            </div>

            {/* Field: Machinery List */}
            <div className="flex flex-col gap-1.5">
              <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">
                Listado de Maquinarias Autorizadas
              </label>
              <div className="border border-[#cbd5e1] divide-y divide-[#cbd5e1] bg-slate-50 shadow-inner">
                {machineryOptions.map((machinery) => {
                  const isChecked = selectedMachinery.includes(machinery.id);
                  return (
                    <label 
                      key={machinery.id} 
                      onClick={() => handleCheckboxChange(machinery.id)}
                      className={`flex items-center px-4 py-3 cursor-pointer transition-colors group select-none ${
                        isChecked ? "bg-[#0054A3]/5" : "hover:bg-slate-100"
                      }`}
                    >
                      {/* Custom Simulated checkbox with premium Cooitzá blue & yellow style */}
                      <div className={`w-5 h-5 border flex items-center justify-center mr-3.5 transition-all duration-150 ${
                        isChecked 
                          ? "border-[#0054A3] bg-[#0054A3] text-white" 
                          : "border-[#cbd5e1] bg-white group-hover:border-[#FFD200]"
                      }`}>
                        {isChecked && (
                          <svg className="w-3.5 h-3.5 text-white stroke-2 fill-none stroke-current" viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12" />
                          </svg>
                        )}
                      </div>

                      <span className={`font-display text-xs font-bold uppercase tracking-tight transition-colors ${
                        isChecked ? "text-[#0054A3]" : "text-on-surface-variant group-hover:text-[#191c1d]"
                      }`}>
                        {machinery.label}
                      </span>
                    </label>
                  );
                })}
              </div>
            </div>

            {/* Technical Footer / CTA */}
            <div className="pt-2 mt-6 flex flex-col gap-3">
              {registrationSuccess ? (
                <div className="w-full bg-green-600 text-white font-display text-xs font-bold tracking-[0.1em] py-3.5 px-4 flex items-center justify-center gap-2 uppercase shadow-sm">
                  <CheckCircle size={18} />
                  <span>REGISTRO EXPORTADO CORRECTAMENTE</span>
                </div>
              ) : (
                <button 
                  type="submit"
                  disabled={isSubmitting}
                  className="w-full bg-[#FFD200] text-[#002d58] font-display text-xs font-black tracking-[0.12em] py-3.5 px-4 flex items-center justify-center gap-2 hover:opacity-95 active:scale-[0.99] transition-all cursor-pointer disabled:opacity-75 disabled:cursor-wait shadow-sm uppercase"
                >
                  {isSubmitting ? (
                    <>
                      <Loader2 size={18} className="animate-spin text-[#0054A3]" />
                      <span>PROCESANDO ENLACE...</span>
                    </>
                  ) : (
                    <>
                      <ClipboardSignature size={18} className="text-[#0054A3]" />
                      <span>REGISTRAR OPERADOR TÉCNICO</span>
                    </>
                  )}
                </button>
              )}

              <button 
                type="button"
                onClick={onLogout}
                className="w-full border border-[#cbd5e1] text-[#004586] bg-white hover:bg-slate-50 font-display text-xs font-black tracking-[0.12em] py-3.5 px-4 flex items-center justify-center gap-2 transition-all cursor-pointer uppercase shadow-xs"
              >
                CANCELAR OPERACIÓN
              </button>
            </div>

          </form>

          {/* Registration Success feedback sub-alert */}
          <AnimatePresence>
            {registrationSuccess && (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                exit={{ opacity: 0, scale: 0.95 }}
                className="mt-2 bg-emerald-50 border border-emerald-200 p-4 shadow-xs"
              >
                <div className="flex gap-2.5">
                  <CheckCircle className="text-emerald-700 shrink-0 mt-0.5" size={16} />
                  <div className="text-xs font-sans text-emerald-800">
                    <p className="font-bold">Formulario técnico transmitido con éxito.</p>
                    <p className="mt-1">Piloto registrado permanentemente en el sistema central de bitácoras de Cooitzá R.L.</p>
                  </div>
                </div>
              </motion.div>
            )}
          </AnimatePresence>

        </div>

        {/* System Metadata */}
        <div className="mt-6 flex justify-between items-center px-1 text-on-surface-variant/60 select-none">
          <span className="font-display text-[10px] font-bold uppercase tracking-[0.1em]">
            Auth Token: 992-PX-77
          </span>
          <div className="flex gap-4">
            <span className="font-display text-[10px] font-bold uppercase tracking-[0.1em] flex items-center gap-1.5">
              <Wifi size={12} className="text-[#004586]/70" /> Online
            </span>
            <span className="font-display text-[10px] font-bold uppercase tracking-[0.1em] flex items-center gap-1.5">
              <Database size={12} className="text-[#004586]/70" /> Sync
            </span>
          </div>
        </div>

      </main>
    </div>
  );
}
