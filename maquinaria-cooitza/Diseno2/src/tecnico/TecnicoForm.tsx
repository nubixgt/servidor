import React, { useState } from "react";
import { 
  ChevronDown, 
  Tractor, 
  Construction, 
  Wrench, 
  CircleSlash, 
  Droplets, 
  Truck, 
  Camera, 
  MapPin,
  CheckCircle,
  AlertTriangle,
  RefreshCw
} from "lucide-react";
import { motion } from "motion/react";
import { OperationalLog } from "../types";

interface TecnicoFormProps {
  currentUserFullName: string;
  onAddLog: (log: Omit<OperationalLog, "id" | "dateTime">) => void;
  onLogout: () => void;
}

type MachineType = "tractor" | "excavadora" | "retro" | "rodo" | "pipa" | "volteo";
type RegType = "incinal" | "final";

export default function TecnicoForm({ currentUserFullName, onAddLog, onLogout }: TecnicoFormProps) {
  const [machineType, setMachineType] = useState<MachineType>("excavadora");
  const [regType, setRegType] = useState<RegType>("incinal");
  const [operatorName, setOperatorName] = useState(currentUserFullName);
  const [horometroValue, setHorometroValue] = useState("");
  const [selectedPhoto, setSelectedPhoto] = useState<string | null>(null);
  const [latitude, setLatitude] = useState(14.6349);
  const [longitude, setLongitude] = useState(-90.5069);
  const [address, setAddress] = useState("Km 42 Ruta Interamericana, Sector El Rodeo");
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);
  const [errorText, setErrorText] = useState("");

  const machines = [
    { id: "tractor", label: "Tractor", icon: Tractor },
    { id: "excavadora", label: "Excavadora", icon: Construction },
    { id: "retro", label: "Retro Excavadora", icon: Wrench },
    { id: "rodo", label: "Rodo", icon: CircleSlash },
    { id: "pipa", label: "Pipa", icon: Droplets },
    { id: "volteo", label: "Camión Volteo", icon: Truck },
  ];

  // Simulates capturing a dynamic photo of the hour meter dial 
  const handleSimulatePhoto = () => {
    const randomValue = horometroValue ? parseFloat(horometroValue).toFixed(1) : (Math.random() * 10000).toFixed(1);
    const canvas = document.createElement("canvas");
    canvas.width = 400;
    canvas.height = 200;
    const ctx = canvas.getContext("2d");
    if (ctx) {
      // Draw background
      ctx.fillStyle = "#1e293b";
      ctx.fillRect(0, 0, 400, 200);
      
      // Draw border frame
      ctx.strokeStyle = "#FFD200";
      ctx.lineWidth = 4;
      ctx.strokeRect(10, 10, 380, 180);
      
      // Draw camera crosshairs
      ctx.strokeStyle = "rgba(255,255,255,0.4)";
      ctx.lineWidth = 1;
      ctx.beginPath();
      ctx.moveTo(200, 20); ctx.lineTo(200, 180);
      ctx.moveTo(50, 100); ctx.lineTo(350, 100);
      ctx.stroke();

      // Draw horizontal scale meter
      ctx.fillStyle = "#475569";
      ctx.fillRect(80, 70, 240, 60);

      // Draw value text
      ctx.fillStyle = "#FFD200";
      ctx.font = "bold 28px 'Courier New', monospace";
      ctx.fillText(`${randomValue.padStart(7, "0")} HRS`, 105, 110);

      // Brand label on capture
      ctx.fillStyle = "#ffffff";
      ctx.font = "bold 10px sans-serif";
      ctx.fillText("COOITZÁ - REGISTRO TELEMETRÍA", 120, 160);
      
      setSelectedPhoto(canvas.toDataURL("image/png"));
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (uploadEvent) => {
        if (uploadEvent.target?.result) {
          setSelectedPhoto(uploadEvent.target.result as string);
        }
      };
      reader.readAsDataURL(file);
    }
  };

  // Allows testing coordinates trigger by tapping the map
  const handleRandomizeCoordinates = () => {
    const rLat = (14.5 + Math.random() * 0.3).toFixed(4);
    const rLng = (-90.6 + Math.random() * 0.3).toFixed(4);
    setLatitude(parseFloat(rLat));
    setLongitude(parseFloat(rLng));
    
    // Choose from some realistic Cooitzá operation sites in Guatemala
    const sites = [
      "Cantera Cooitzá - Chimaltenango, Guatemala",
      "Proyecto Vial CO-A4, San Carlos Alzatate",
      "Sector Norte de Construcción, Mixco",
      "Plantel Central Cooitzá - Ciudad de Guatemala"
    ];
    setAddress(sites[Math.floor(Math.random() * sites.length)]);
  };

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorText("");

    if (!operatorName) {
      setErrorText("Por favor, seleccione o ingrese un operador asignado.");
      return;
    }
    if (!horometroValue || parseFloat(horometroValue) <= 0) {
      setErrorText("Por favor, ingrese un valor de horómetro válido mayor que 0.");
      return;
    }

    setIsSubmitting(true);

    setTimeout(() => {
      setIsSubmitting(false);
      onAddLog({
        operatorName,
        machineType,
        regType: regType === "incinal" ? "inicial" : "final",
        horometroValue: parseFloat(horometroValue),
        photoUrl: selectedPhoto || undefined,
        location: {
          lat: latitude,
          lng: longitude,
          formattedAddress: address
        }
      });
      setSubmitSuccess(true);
    }, 1200);
  };

  if (submitSuccess) {
    return (
      <motion.div 
        initial={{ opacity: 0, scale: 0.98 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-[640px] bg-white border border-[#0054A3] p-8 text-center flex flex-col items-center gap-6 shadow-md"
      >
        <div className="w-16 h-16 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-600">
          <CheckCircle size={44} />
        </div>
        <div className="flex flex-col gap-2">
          <h2 className="font-display text-2xl font-bold text-[#0054A3]">
            Registro Guardado Exitosamente
          </h2>
          <p className="font-sans text-sm text-on-surface-variant max-w-sm mx-auto">
            La telemetría industrial del operador <strong className="text-on-surface">{operatorName}</strong> para la <strong className="text-on-surface">{(machines.find(m => m.id === machineType)?.label) || machineType}</strong> ha sido transmitida de manera segura.
          </p>
        </div>

        {/* Diagnostic details */}
        <div className="w-full bg-slate-50 p-4 border border-[#cbd5e1] rounded text-left font-mono text-xs flex flex-col gap-2 text-on-surface-variant">
          <div><span className="text-[#0054A3] font-bold">FECHA/HORA:</span> {new Date().toLocaleString("es-GT")}</div>
          <div><span className="text-[#0054A3] font-bold">HORÓMETRO:</span> {horometroValue} HRS ({regType === "incinal" ? "Inicial" : "Final"})</div>
          <div><span className="text-[#0054A3] font-bold">UBICACIÓN:</span> {latitude}° N, {longitude}° W</div>
          <div><span className="text-[#0054A3] font-bold">ESTADO:</span> TRANSMITIDO / OK</div>
        </div>

        <button
          type="button"
          onClick={() => {
            setSubmitSuccess(false);
            setHorometroValue("");
            setSelectedPhoto(null);
          }}
          className="bg-[#0054A3] hover:bg-[#004586] text-white py-3 px-6 font-display text-xs font-bold uppercase tracking-widest cursor-pointer transition-colors"
        >
          Nuevo Registro de Operación
        </button>
      </motion.div>
    );
  }

  return (
    <div className="w-full max-w-[640px] bg-white border border-[#cbd5e1] p-8 shadow-sm flex flex-col gap-6">
      
      {/* Session state header */}
      <div className="flex justify-between items-center bg-slate-100 -mx-8 -mt-8 px-8 py-3 border-b border-[#cbd5e1]">
        <div className="flex items-center gap-2">
          <span className="w-2.5 h-2.5 bg-emerald-500 rounded-full animate-pulse" />
          <span className="font-display text-xs font-black text-[#0054A3] uppercase">
            Rol: Técnico
          </span>
        </div>
        <div className="flex items-center gap-4">
          <span className="font-sans text-xs text-on-surface-variant max-w-[150px] truncate text-right">
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

      <form className="flex flex-col gap-6" onSubmit={handleFormSubmit}>
        
        {errorText && (
          <div className="bg-amber-50 border-l-4 border-amber-500 p-3 text-xs text-amber-800 font-medium font-sans flex items-center gap-2">
            <AlertTriangle className="w-4 h-4 text-amber-600 flex-shrink-0" />
            <span>{errorText}</span>
          </div>
        )}

        {/* Pilot Selection */}
        <div className="flex flex-col gap-1.5">
          <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">Operador Asignado</label>
          <div className="relative">
            <select 
              value={operatorName}
              onChange={(e) => setOperatorName(e.target.value)}
              className="w-full bg-slate-50 border border-[#cbd5e1] py-3 px-4 font-sans text-sm text-on-surface focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] outline-none appearance-none transition-all cursor-pointer"
            >
              <option value="">Seleccionar Operador...</option>
              <option value="Robert Andersson (Técnico)">Robert Andersson</option>
              <option value="Elena Rodriguez (Técnico)">Elena Rodriguez</option>
              <option value="Marcus Thorne (Técnico)">Marcus Thorne</option>
              <option value="M1gu3l Fu3nt3s (Técnico)">Miguel Fuentes</option>
            </select>
            <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[#0054A3]">
              <ChevronDown size={18} />
            </div>
          </div>
        </div>

        {/* Machine Type Grid */}
        <div className="flex flex-col gap-1.5">
          <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">Tipo de Maquinaria</label>
          <div className="grid grid-cols-2 md:grid-cols-3 gap-1">
            {machines.map((m) => {
              const Icon = m.icon;
              const isSelected = machineType === m.id;
              return (
                <button
                  key={m.id}
                  type="button"
                  onClick={() => setMachineType(m.id as MachineType)}
                  className={`border p-4 text-center flex flex-col items-center gap-1.5 transition-all ${
                    isSelected 
                      ? "border-[#0054A3] bg-[#0054A3]/5 ring-1 ring-[#0054A3]" 
                      : "border-[#cbd5e1] hover:border-[#FFD200] bg-white"
                  }`}
                >
                  <Icon size={22} className={isSelected ? "text-[#0054A3]" : "text-on-surface-variant"} />
                  <span className={`font-display text-xs font-bold uppercase tracking-tight ${isSelected ? "text-[#0054A3]" : "text-on-surface"}`}>
                    {m.label}
                  </span>
                </button>
              );
            })}
          </div>
        </div>

        {/* Registration Type Toggle */}
        <div className="flex flex-col gap-1.5">
          <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">Tipo de Registro</label>
          <div className="flex bg-slate-100 p-1 gap-1">
            <button
              type="button"
              onClick={() => setRegType("incinal")}
              className={`flex-1 py-2 text-center font-display text-xs font-bold uppercase tracking-wider transition-all ${
                regType === "incinal" 
                  ? "bg-white border border-[#cbd5e1] text-[#0054A3]" 
                  : "text-on-surface-variant opacity-60 hover:opacity-100"
              }`}
            >
              Horómetro Inicial
            </button>
            <button
              type="button"
              onClick={() => setRegType("final")}
              className={`flex-1 py-2 text-center font-display text-xs font-bold uppercase tracking-wider transition-all ${
                regType === "final" 
                  ? "bg-white border border-[#cbd5e1] text-[#0054A3]" 
                  : "text-on-surface-variant opacity-60 hover:opacity-100"
              }`}
            >
              Horómetro Final
            </button>
          </div>
        </div>

        {/* Value Inputs Group */}
        <div className="flex flex-col gap-4 p-5 bg-slate-50 border-l-4 border-[#0054A3]">
          <div className="flex flex-col gap-1.5">
            <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">
              {regType === "incinal" ? "HORÓMETRO INICIAL (VALOR EN HORAS)" : "HORÓMETRO FINAL (VALOR EN HORAS)"}
            </label>
            <div className="flex items-center">
              <input
                type="number"
                placeholder="00000.0"
                step="0.1"
                min="0"
                value={horometroValue}
                onChange={(e) => setHorometroValue(e.target.value)}
                className="w-full bg-white border border-[#cbd5e1] py-2.5 px-4 font-display font-medium text-sm text-on-surface outline-none focus:border-[#0054A3] focus:ring-1 focus:ring-[#FFD200]"
              />
              <div className="ml-3 bg-slate-100 px-4 py-2.5 border border-[#cbd5e1] font-display text-xs font-bold text-on-surface-variant">
                HRS
              </div>
            </div>
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">FOTO DEL REGISTRO HORÓMETRO</label>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              {/* File Upload Selector */}
              <label className="border-2 border-dashed border-[#cbd5e1] py-6 px-4 flex flex-col items-center justify-center gap-1.5 hover:border-[#0054A3] cursor-pointer transition-colors bg-white group select-none">
                <Camera size={26} className="text-on-surface-variant group-hover:text-[#0054A3] transition-colors" />
                <span className="font-sans text-xs text-on-surface-variant group-hover:text-on-surface">
                  Subir o capturar foto
                </span>
                <span className="font-display text-[9px] uppercase tracking-widest text-[#857462]">
                  JPG, PNG HASTA 10MB
                </span>
                <input 
                  type="file" 
                  accept="image/*" 
                  onChange={handleFileUpload} 
                  className="hidden" 
                />
              </label>

              {/* Live Dial Simulator */}
              <button
                type="button"
                onClick={handleSimulatePhoto}
                className="border border-[#0054A3] py-6 px-4 flex flex-col items-center justify-center gap-1.5 bg-[#0054A3]/5 hover:bg-[#0054A3]/10 text-[#0054A3] font-display text-xs font-bold uppercase tracking-wider transition-all"
              >
                <RefreshCw size={26} className="animate-spin-slow-less" />
                <span>Simular Lectura</span>
                <span className="text-[9px] font-sans font-medium text-on-surface-variant uppercase">Generar dial de prueba</span>
              </button>
            </div>

            {selectedPhoto && (
              <motion.div 
                initial={{ opacity: 0, scale: 0.95 }}
                animate={{ opacity: 1, scale: 1 }}
                className="mt-3 relative border border-[#cbd5e1] overflow-hidden"
              >
                <img 
                  src={selectedPhoto} 
                  alt="Vista previa" 
                  className="w-full h-auto max-h-[180px] object-contain bg-slate-900" 
                />
                <button
                  type="button"
                  onClick={() => setSelectedPhoto(null)}
                  className="absolute top-2 right-2 bg-red-600 text-white font-display text-[10px] font-black uppercase px-2.5 py-1 hover:bg-red-700 shadow"
                >
                  Eliminar Foto
                </button>
              </motion.div>
            )}
          </div>
        </div>

        {/* Current Location (Map) */}
        <div className="flex flex-col gap-1.5">
          <div className="flex justify-between items-end">
            <label className="text-on-surface-variant text-xs font-bold uppercase tracking-wider block">Ubicación Actual del Proyecto</label>
            <span className="font-display text-[10px] text-primary flex items-center gap-1 font-bold">
              <span className="w-2 h-2 rounded-full bg-primary animate-pulse" />
              GPS ACTIVO
            </span>
          </div>

          <div 
            onClick={handleRandomizeCoordinates}
            title="Haga clic para simular otra ubicación de obra"
            className="h-44 w-full border border-[#cbd5e1] bg-slate-50 relative overflow-hidden group cursor-pointer"
          >
            {/* Minimal grayscale topographical industrial styling construct */}
            <div className="absolute inset-0 bg-[#f1f5f9] flex items-center justify-center pointer-events-none opacity-40">
              <div className="w-full h-full bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:16px_16px]" />
            </div>
            
            <img
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuAhZvPhcwVmZ7QlGYcRR63FPRjn0fznOJewT9Vgz8qtQPnSidN-BRoEUsIf_WG1_bYH-9HmsWEF3qHEdIBKEK-F6TXjoAmEvYlxhcuhwuJGSR-FOmqD9Lp9dtpPzYWluYjv4_04AH-Eymk7FKKZinrAUTww4IbXAckEASbtl8AYHSmZrA4s0AUKn9npDtqISyaruKxUXGTRp_FqPtYXdTV9a9Y7Uo6_e_gVEk3XzEjagI5YPiS13XVGr3AgsLFFc0KkviiacPo7T4un"
              alt="Plano cartográfico de obra"
              className="w-full h-full object-cover grayscale opacity-45 group-hover:opacity-60 transition-opacity duration-500"
              referrerPolicy="no-referrer"
            />

            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div className="relative">
                <div className="absolute inset-0 bg-[#0054A3]/20 rounded-full animate-ping scale-150" />
                <div className="w-10 h-10 rounded-full bg-[#0054A3]/20 flex items-center justify-center border border-[#0054A3] relative">
                  <MapPin size={22} className="text-[#0054A3] fill-[#0054A3]" />
                </div>
              </div>
            </div>

            <div className="absolute top-2 left-2 bg-black/65 text-white text-[9px] px-2 py-0.5 font-mono select-none">
              ZONA OBRA INTERACTIVA • CLIC PARA CAMBIAR
            </div>

            <div className="absolute bottom-2 right-2 bg-white/90 backdrop-blur-sm px-2.5 py-1 border border-[#cbd5e1] max-w-[90%] truncate text-right">
              <p className="font-display text-[9px] font-bold text-on-surface tracking-tight">
                {latitude}° N, {longitude}° W
              </p>
              <p className="text-[8px] font-sans text-on-surface-variant font-medium truncate">
                {address}
              </p>
            </div>
          </div>
        </div>

        {/* Submit Operations */}
        <div className="mt-2">
          <button
            type="submit"
            disabled={isSubmitting}
            className="w-full bg-[#0054A3] hover:bg-[#004586] disabled:bg-[#cbd5e1] py-4 text-white font-display text-lg font-black uppercase tracking-wider transition-all active:scale-[0.98] cursor-pointer"
          >
            {isSubmitting ? "TRANSMITIENDO TELEMETRÍA..." : "REGISTRAR OPERACIÓN"}
          </button>
          <p className="text-center font-display text-[9px] text-on-surface-variant mt-3 font-semibold uppercase tracking-wider">
            Al realizar el envío, usted certifica la veracidad de la lectura del horómetro industrial.
          </p>
        </div>

      </form>
    </div>
  );
}
