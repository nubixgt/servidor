import React, { useState, useEffect, useRef } from "react";
import { 
  Truck, 
  CloudUpload, 
  Search, 
  Edit, 
  Trash2, 
  ArrowRight, 
  X,
  FileSpreadsheet
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

interface Vehicle {
  id: string;
  code: string; // Used for plural views or Placa
  model: string; // Marca / Modelo
  plate: string;
  type: "camion" | "pickup" | "pipa" | "otros";
  status: "Activo" | "Mantenimiento" | "Standby";
  year: string;
  mileage: number;
  photoUrl?: string;
}

interface VehiculosModuleProps {
  onVehiclesChange?: (count: number, activeCount: number, maintenanceCount: number) => void;
}

const DEFAULT_VEHICLE_PHOTOS = [
  "https://lh3.googleusercontent.com/aida-public/AB6AXuBfCQxHMoSqylJtj62363vrKS4Ai0aSb8qAVt7Vxe7OrqjIXMky93gYA8fkKJ5NI234BDTazq23zLhJnD2FS5s7l6F6n53lXwZt9ykMZ1mHgocxXB85X1OimLy6_6zeYidMZPGnl51KC3KG2QK0v-25MkkEOFHoTzq3XSaYsi8wqQQ4E9FhsapVEDRzsLqlWWh_bSjIN7hgooh7Eno7Co11U4_AFWZ5F1x6PV8KiOhzF9aAvednwsyE0P7Pmgnvfo9FIu6x7CJDYFv4",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuAOS7o83jWBjyOCa7Sy-JMKLT1aChKEr5Vfl6WYCfW3UNvOpY14pVAAVDMBCCBQmGz7d0keMVoNrSENiIZJqRP0DkPySV3YzA-Y9P68wI37H_hd4KSJ9bkmbO7HGVYCbbd8Ozn6DdGkM0Ocp-Ql4RrIJhdWNG7n54xYb6NZya35YtSfcbOQTUWLG-vnUJZuNdHA76wg9lCXDzMYtlzKLfrsSh5x8l4E8BMp4NhRqRKTHDXlis4zJwNr3W9wZXpyu99CYSnDb8QVh5O-",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuBtxq1_ce_lH6LS17f9tAAI4LUKez8StI7z_g8Oj1bLpZF6CKykb_8CtlBzi0Q2mvFFm-UiluRsxEeaiuy7GuU7M-0qISKI70sFsyV2IFNnPGKAcZxNObUP2lAw2kSl2jYd8ugip5qTfhkPs-UUjo_DDlJEXPas4XhJCWnNYnqvH672FKRQXY1dFDNuBjWybUR-S2f6Iqnc8CJd26B4ZLnAMInrBbXfsH0ySl88b54eftYeBhdo_bhcPM9TjAZs0ZcB0UmtJFX47wqX",
  "https://lh3.googleusercontent.com/aida-public/AB6AXuDIO4iixhRl8ks22wCvo8FVysSOADgubwEgVWVRvCXJN3VSP4BwqaBT9hBoD6obwKzk8zYlU609d8UPAgY0TvKo-sYR0eH9C9emvqKpK8CWFz8d7kpRnMPsUG-SI14qGGQ5GDvgOrNqGWbnfaBlYmeDHxMwys23tvSNVyDNzjlwfz52UVjsTYQK8FOuhL022AkGVNHKhnhc6Dv2LIJZoxJvEs3quHXMMCy2L09rqOKoq031Q0ptFawvTDHWaqTZdAgCRuqTexXOzph7"
];

export default function VehiculosModule({ onVehiclesChange }: VehiculosModuleProps) {
  const [vehicles, setVehicles] = useState<Vehicle[]>([]);
  const [editVehicleId, setEditVehicleId] = useState<string | null>(null);
  
  // Form fields
  const [marca, setMarca] = useState("");
  const [placa, setPlaca] = useState("");
  const [tipo, setTipo] = useState<"camion" | "pickup" | "pipa" | "otros">("camion");
  const [yearModel, setYearModel] = useState("");
  const [mileage, setMileage] = useState("");
  const [status, setStatus] = useState<"Activo" | "Mantenimiento" | "Standby">("Activo");
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);

  // Drag over state
  const [isDragOver, setIsDragOver] = useState(false);
  const [showInvoiceModal, setShowInvoiceModal] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");

  const fileInputRef = useRef<HTMLInputElement>(null);

  // Initialize from LocalStorage
  useEffect(() => {
    const saved = localStorage.getItem("cooitza_vehiculos");
    if (saved) {
      const data = JSON.parse(saved);
      setVehicles(data);
      triggerSync(data);
    } else {
      const defaultVehicles: Vehicle[] = [
        { 
          id: "v1", 
          code: "VEH-104", 
          model: "Volvo FH16", 
          plate: "PLQ-9902", 
          type: "camion", 
          status: "Activo",
          year: "2023",
          mileage: 48900,
          photoUrl: DEFAULT_VEHICLE_PHOTOS[0]
        },
        { 
          id: "v2", 
          code: "VEH-88", 
          model: "Ford Ranger", 
          plate: "GST-5512", 
          type: "pickup", 
          status: "Mantenimiento",
          year: "2022",
          mileage: 12400,
          photoUrl: DEFAULT_VEHICLE_PHOTOS[1]
        },
        { 
          id: "v3", 
          code: "VEH-05", 
          model: "Isuzu NPR", 
          plate: "MXX-2281", 
          type: "camion", 
          status: "Activo",
          year: "2021",
          mileage: 81300,
          photoUrl: DEFAULT_VEHICLE_PHOTOS[2]
        },
        { 
          id: "v4", 
          code: "VEH-92", 
          model: "Toyota Hilux", 
          plate: "RDZ-0092", 
          type: "pickup", 
          status: "Activo",
          year: "2023",
          mileage: 37900,
          photoUrl: DEFAULT_VEHICLE_PHOTOS[3]
        }
      ];
      setVehicles(defaultVehicles);
      localStorage.setItem("cooitza_vehiculos", JSON.stringify(defaultVehicles));
      triggerSync(defaultVehicles);
    }
  }, []);

  const triggerSync = (updated: Vehicle[]) => {
    if (onVehiclesChange) {
      const active = updated.filter(v => v.status === "Activo").length;
      const maint = updated.filter(v => v.status === "Mantenimiento").length;
      onVehiclesChange(updated.length, active, maint);
    }
  };

  const persistVehicles = (updated: Vehicle[]) => {
    setVehicles(updated);
    localStorage.setItem("cooitza_vehiculos", JSON.stringify(updated));
    triggerSync(updated);
  };

  // Convert uploaded file to base64
  const processFile = (file: File) => {
    if (file && file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        if (e.target?.result) {
          setPhotoPreview(e.target.result as string);
        }
      };
      reader.readAsDataURL(file);
    }
  };

  const handleDragOver = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(true);
  };

  const handleDragLeave = () => {
    setIsDragOver(false);
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    setIsDragOver(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      processFile(e.dataTransfer.files[0]);
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      processFile(e.target.files[0]);
    }
  };

  const handleSaveVehicle = (e: React.FormEvent) => {
    e.preventDefault();
    if (!marca.trim() || !placa.trim()) return;

    // Pick a default photo if none was uploaded
    const chosenPhoto = photoPreview || DEFAULT_VEHICLE_PHOTOS[Math.floor(Math.random() * DEFAULT_VEHICLE_PHOTOS.length)];

    if (editVehicleId) {
      const updated = vehicles.map(v => v.id === editVehicleId ? {
        ...v,
        model: marca,
        plate: placa,
        type: tipo,
        year: yearModel,
        mileage: parseFloat(mileage) || 0,
        status,
        photoUrl: chosenPhoto
      } : v);
      persistVehicles(updated);
      setEditVehicleId(null);
    } else {
      const newVehicle: Vehicle = {
        id: "veh_" + Date.now(),
        code: "VEH-" + (100 + vehicles.length),
        model: marca,
        plate: placa,
        type: tipo,
        status,
        year: yearModel || "2024",
        mileage: parseFloat(mileage) || 0,
        photoUrl: chosenPhoto
      };
      persistVehicles([newVehicle, ...vehicles]);
    }

    // Reset Form
    setMarca("");
    setPlaca("");
    setTipo("camion");
    setYearModel("");
    setMileage("");
    setStatus("Activo");
    setPhotoPreview(null);
  };

  const handleEditVehicle = (v: Vehicle) => {
    setEditVehicleId(v.id);
    setMarca(v.model);
    setPlaca(v.plate);
    setTipo(v.type);
    setYearModel(v.year);
    setMileage(v.mileage.toString());
    setStatus(v.status);
    setPhotoPreview(v.photoUrl || null);
  };

  const handleDeleteVehicle = (id: string) => {
    if (window.confirm("¿Está seguro de remover este vehículo de la flota?")) {
      const updated = vehicles.filter(v => v.id !== id);
      persistVehicles(updated);
    }
  };

  // Only show the 4 most recently registered on the right sidebar
  const recentVehicles = vehicles.slice(0, 4);

  // Filter full list for search
  const filteredVehicles = vehicles.filter(v => 
    v.model.toLowerCase().includes(searchTerm.toLowerCase()) ||
    v.plate.toLowerCase().includes(searchTerm.toLowerCase()) ||
    v.code.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <motion.div 
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex flex-col gap-6"
    >
      {/* Dynamic top heading & status */}
      <div className="flex md:flex-row flex-col justify-between md:items-center gap-2 border-b border-slate-200 pb-3">
        <div className="border-l-4 border-[#0054A3] pl-3">
          <span className="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">LOGÍSTICA INTEGRAL</span>
          <h2 className="font-display text-3xl font-black text-on-surface mt-0.5">Control de Flota Operativa</h2>
          <p className="text-xs text-on-surface-variant font-medium mt-1">
            Registro, control de kilometraje y taller de vehículos de transporte Cooitzá.
          </p>
        </div>
        <div className="flex items-center gap-2 bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 self-start">
          <div className="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
          <span className="font-display text-[10px] font-bold uppercase tracking-widest">Módulo de Flotas Activo</span>
        </div>
      </div>

      <div className="flex flex-col lg:flex-row gap-8 items-start">
        
        {/* Left Form Column (Registro de Vehículos) */}
        <section className="flex-1 space-y-4 w-full">
          <div className="bg-white border border-[#cbd5e1] p-6 relative overflow-hidden">
            <div className="absolute top-0 left-0 w-full h-[3px] bg-[#0054A3]" />
            
            <h3 className="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider mb-6 border-b pb-2">
              {editVehicleId ? "Modificar Vehículo Registrado" : "Registro de Vehículos"}
            </h3>

            <form onSubmit={handleSaveVehicle} className="grid grid-cols-1 md:grid-cols-2 gap-4">
              
              <div className="flex flex-col gap-1">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Marca / Modelo
                </label>
                <input 
                  type="text"
                  value={marca}
                  onChange={(e) => setMarca(e.target.value)}
                  placeholder="Ej. Freightliner o Volvo FH16"
                  className="w-full border border-[#cbd5e1] focus:border-[#0054A3] font-body-md text-xs py-2 px-3 outline-none"
                  required
                />
              </div>

              <div className="flex flex-col gap-1">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Placa / Matrícula
                </label>
                <input 
                  type="text"
                  value={placa}
                  onChange={(e) => setPlaca(e.target.value)}
                  placeholder="Ej. ABC-1234"
                  className="w-full border border-[#cbd5e1] focus:border-[#0054A3] font-body-md text-xs py-2 px-3 outline-none"
                  required
                />
              </div>

              <div className="flex flex-col gap-1">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Tipo de Vehículo
                </label>
                <select 
                  value={tipo}
                  onChange={(e) => setTipo(e.target.value as any)}
                  className="w-full border border-[#cbd5e1] focus:border-[#0054A3] font-body-md text-xs py-2 px-3 bg-white outline-none cursor-pointer"
                >
                  <option value="camion">Camión</option>
                  <option value="pickup">Pickup</option>
                  <option value="pipa">Pipa Cisterna</option>
                  <option value="otros">Otros Equipos</option>
                </select>
              </div>

              <div className="flex flex-col gap-1">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Modelo / Año
                </label>
                <input 
                  type="text"
                  value={yearModel}
                  onChange={(e) => setYearModel(e.target.value)}
                  placeholder="Ej. 2024"
                  className="w-full border border-[#cbd5e1] focus:border-[#0054A3] font-body-md text-xs py-2 px-3 outline-none"
                />
              </div>

              <div className="flex flex-col gap-1 md:col-span-2">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Kilometraje de Registro
                </label>
                <div className="relative">
                  <input 
                    type="number"
                    value={mileage}
                    onChange={(e) => setMileage(e.target.value)}
                    placeholder="000,000"
                    className="w-full border border-[#cbd5e1] focus:border-[#0054A3] font-body-md text-xs py-2 pl-3 pr-12 outline-none"
                    required
                  />
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 font-display text-[10px] font-black text-on-surface-variant">
                    KM
                  </span>
                </div>
              </div>

              <div className="flex flex-col gap-1 md:col-span-2">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Estado de Flota
                </label>
                <select
                  value={status}
                  onChange={(e) => setStatus(e.target.value as any)}
                  className="w-full border border-[#cbd5e1] focus:border-[#0054A3] font-body-md text-xs py-2 px-3 bg-white outline-none cursor-pointer"
                >
                  <option value="Activo">Operativo</option>
                  <option value="Mantenimiento">Taller / Mantenimiento</option>
                  <option value="Standby">En Reserva (Standby)</option>
                </select>
              </div>

              {/* Upload Area styled exactly according to requested HTML */}
              <div className="md:col-span-2 flex flex-col gap-1">
                <label className="font-display text-[11px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                  Fotografía del Vehículo
                </label>
                
                <input 
                  type="file" 
                  ref={fileInputRef}
                  onChange={handleFileChange}
                  accept="image/*"
                  className="hidden" 
                />

                <div 
                  onClick={() => fileInputRef.current?.click()}
                  onDragOver={handleDragOver}
                  onDragLeave={handleDragLeave}
                  onDrop={handleDrop}
                  className={`border-2 border-dashed p-8 flex flex-col items-center justify-center gap-3 transition-colors cursor-pointer group rounded-sm ${
                    isDragOver 
                      ? "border-[#0054A3] bg-[#0054A3]/5" 
                      : "border-[#cbd5e1] bg-slate-50 hover:bg-slate-100"
                  }`}
                >
                  {photoPreview ? (
                    <div className="relative w-full max-w-[200px] h-24 overflow-hidden border border-slate-200">
                      <img 
                        src={photoPreview} 
                        alt="Preview" 
                        typeof="image"
                        className="w-full h-full object-cover" 
                      />
                      <button 
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          setPhotoPreview(null);
                        }}
                        className="absolute top-1 right-1 bg-red-650 text-white p-0.5 rounded-sm hover:bg-red-700"
                        title="Quitar foto"
                      >
                        <X size={12} />
                      </button>
                    </div>
                  ) : (
                    <>
                      <CloudUpload className="w-12 h-12 text-[#94a3b8] group-hover:text-[#0054A3] transition-colors" />
                      <div className="text-center">
                        <p className="font-body-md text-xs text-on-surface-variant font-medium">
                          Arrastre la imagen o <span className="text-[#0054A3] font-bold underline">examine</span>
                        </p>
                        <p className="font-display text-[10px] text-slate-400 uppercase mt-1 tracking-wider">
                          Formatos aceptados: PNG, JPG (Max 5MB)
                        </p>
                      </div>
                    </>
                  )}
                </div>
              </div>

              <div className="md:col-span-2 pt-2">
                <button 
                  type="submit"
                  className="w-full md:w-auto px-6 py-2.5 bg-[#FFD200] text-[#0054A3] font-display text-xs font-black uppercase tracking-widest hover:brightness-105 transition-all cursor-pointer shadow-sm rounded-none"
                >
                  {editVehicleId ? "Aplicar Cambios" : "Registrar Vehículo"}
                </button>

                {editVehicleId && (
                  <button 
                    type="button"
                    onClick={() => {
                      setEditVehicleId(null);
                      setMarca("");
                      setPlaca("");
                      setTipo("camion");
                      setYearModel("");
                      setMileage("");
                      setStatus("Activo");
                      setPhotoPreview(null);
                    }}
                    className="w-full md:w-auto md:ml-4 text-xs font-bold text-red-600 hover:underline mt-2 md:mt-0"
                  >
                    Cancelar Edición
                  </button>
                )}
              </div>

            </form>
          </div>
        </section>

        {/* Right Column (Registrados Recientemente sidebar list with full hover states) */}
        <section className="w-full lg:w-80 flex flex-col gap-4">
          <div className="flex items-center justify-between border-b border-slate-300 pb-2">
            <h3 className="font-display text-[11px] font-black text-on-surface-variant uppercase tracking-widest">
              Registrados Recientemente
            </h3>
            <span className="font-display text-[11px] font-extrabold text-[#0054A3]">
              {vehicles.length < 10 ? `0${vehicles.length}` : vehicles.length} TOTAL
            </span>
          </div>

          <div className="flex flex-col gap-2.5 max-h-[440px] overflow-y-auto pr-1">
            {recentVehicles.map((v) => (
              <div 
                key={v.id} 
                className="bg-white border border-[#cbd5e1] p-3 flex items-center gap-3 hover:border-[#0054A3] transition-all cursor-pointer group shadow-sm select-none"
                onClick={() => handleEditVehicle(v)}
                title="Haga clic para editar los datos de este vehículo"
              >
                {/* Photo container with Grayscale/Color transition and premium industrial frame */}
                <div className="w-16 h-16 bg-slate-100 flex-shrink-0 overflow-hidden relative border border-slate-100">
                  <img 
                    src={v.photoUrl || DEFAULT_VEHICLE_PHOTOS[0]} 
                    alt={v.model} 
                    className="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-300"
                  />
                </div>

                <div className="flex-grow min-w-0">
                  <p className="font-display text-[10px] font-bold text-slate-400 uppercase truncate">
                    {v.model} ({v.year || "2023"})
                  </p>
                  <p className="font-display text-sm font-black text-on-surface tracking-tight mt-0.5 truncate">
                    {v.plate}
                  </p>
                  <div className="flex items-center gap-1.5 mt-1.5">
                    <span className={`w-2 h-2 rounded-full ${
                      v.status === "Activo" ? "bg-emerald-500" :
                      v.status === "Mantenimiento" ? "bg-amber-500" : "bg-slate-400"
                    }`} />
                    <span className="font-display text-[9px] font-extrabold text-on-surface-variant uppercase tracking-wider">
                      {v.status === "Activo" ? "Operativo" : v.status === "Mantenimiento" ? "Mantenimiento" : "Standby"}
                    </span>
                  </div>
                </div>

                <div className="flex flex-col gap-1 shrink-0 opacity-40 group-hover:opacity-100 transition-opacity">
                  <button 
                    type="button" 
                    onClick={(e) => {
                      e.stopPropagation();
                      handleDeleteVehicle(v.id);
                    }}
                    className="text-red-650 hover:bg-red-50 p-1 rounded"
                    title="Eliminar unidad"
                  >
                    <Trash2 size={13} />
                  </button>
                </div>
              </div>
            ))}
          </div>

          <button 
            type="button"
            onClick={() => setShowInvoiceModal(true)}
            className="w-full mt-2 py-3 border border-[#cbd5e1] hover:border-[#0054A3] font-display text-[10px] font-black uppercase text-on-surface-variant hover:text-[#0054A3] transition-all flex items-center justify-center gap-2 cursor-pointer bg-white"
          >
            <span>Ver Inventario Completo</span>
            <ArrowRight size={13} />
          </button>
        </section>

      </div>

      {/* FULL FLEET SEARCH / CRUD MODAL GRID */}
      <AnimatePresence>
        {showInvoiceModal && (
          <div className="fixed inset-0 bg-slate-900/80 z-50 flex items-center justify-center p-4">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-white max-w-4xl w-full p-6 flex flex-col gap-4 border-t-4 border-[#0054A3]"
            >
              <div className="flex justify-between items-center border-b pb-3 border-slate-200">
                <div className="flex items-center gap-2">
                  <Truck className="text-[#0054A3]" size={20} />
                  <h3 className="font-display text-base font-black text-[#0054A3] uppercase">
                    Inventario Completo de Vehículos Cooitzá
                  </h3>
                </div>
                <button 
                  type="button"
                  onClick={() => setShowInvoiceModal(false)}
                  className="text-slate-400 hover:text-slate-600 transition-colors"
                >
                  <X size={20} />
                </button>
              </div>

              {/* Filter and Search box in Modal */}
              <div className="flex flex-col sm:flex-row gap-3 items-center bg-slate-50 p-3 border border-slate-200">
                <div className="relative flex-grow w-full">
                  <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                  <input 
                    type="text" 
                    placeholder="Filtrar por placa, marca, modelo o código..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-9 pr-3 py-1.5 bg-white border border-[#cbd5e1] text-xs font-semibold outline-none focus:border-[#0054A3] w-full"
                  />
                </div>
                <div className="text-[11px] font-bold text-slate-500 shrink-0 uppercase tracking-widest">
                  Resultados: {filteredVehicles.length} de {vehicles.length}
                </div>
              </div>

              {/* Data Table */}
              <div className="overflow-y-auto max-h-[350px] border border-slate-200">
                <table className="w-full text-left text-xs font-sans">
                  <thead className="sticky top-0 bg-slate-100 font-display font-black text-[10px] text-on-surface-variant uppercase tracking-wider border-b border-slate-200 z-10 select-none">
                    <tr>
                      <th className="py-2 px-3">Código</th>
                      <th className="py-2 px-3">Descripción / Marca</th>
                      <th className="py-2 px-3">Placa</th>
                      <th className="py-2 px-3">Año / Tipo</th>
                      <th className="py-2 px-3">Kilometraje</th>
                      <th className="py-2 px-3">Estado</th>
                      <th className="py-2.5 px-3 text-center">Acción</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 bg-white">
                    {filteredVehicles.length === 0 ? (
                      <tr>
                        <td colSpan={7} className="py-8 text-center text-slate-400 italic">
                          No se encontraron unidades con los términos especificados
                        </td>
                      </tr>
                    ) : (
                      filteredVehicles.map((v) => (
                        <tr key={v.id} className="hover:bg-slate-50 transition-colors">
                          <td className="py-3 px-3 font-mono font-bold text-[#0054A3]">{v.code}</td>
                          <td className="py-3 px-3">
                            <div className="flex items-center gap-2">
                              {v.photoUrl && (
                                <img src={v.photoUrl} alt="" className="w-6 h-6 object-cover bg-slate-100" />
                              )}
                              <span className="font-semibold text-slate-800">{v.model}</span>
                            </div>
                          </td>
                          <td className="py-3 px-3 font-mono font-bold text-slate-700">{v.plate}</td>
                          <td className="py-3 px-3">
                            <span className="uppercase text-slate-500 font-bold">{v.year}</span>
                            <span className="ml-1 text-[10px] bg-slate-100 px-1 py-0.5 uppercase border rounded-sm font-semibold text-slate-600">
                              {v.type}
                            </span>
                          </td>
                          <td className="py-3 px-3 font-mono font-bold">
                            {v.mileage.toLocaleString()} <span className="text-[10px] text-slate-400">KM</span>
                          </td>
                          <td className="py-3 px-3">
                            <span className={`px-2 py-0.5 rounded text-[9px] font-extrabold uppercase ${
                              v.status === "Activo" ? "bg-emerald-100 text-emerald-800" :
                              v.status === "Mantenimiento" ? "bg-amber-100 text-amber-800" :
                              "bg-slate-100 text-slate-800"
                            }`}>
                              {v.status === "Activo" ? "Operativo" : v.status === "Mantenimiento" ? "Taller" : "Standby"}
                            </span>
                          </td>
                          <td className="py-3 px-3">
                            <div className="flex justify-center items-center gap-1">
                              <button 
                                type="button" 
                                onClick={() => {
                                  handleEditVehicle(v);
                                  setShowInvoiceModal(false);
                                }}
                                className="p-1 hover:bg-slate-100 text-[#0054A3] rounded"
                                title="Editar datos"
                              >
                                <Edit size={13} />
                              </button>
                              <button 
                                type="button" 
                                onClick={() => handleDeleteVehicle(v.id)}
                                className="p-1 hover:bg-red-50 text-red-650 rounded"
                                title="Remover de la flota"
                              >
                                <Trash2 size={13} />
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>

              <div className="flex justify-end pt-2 border-t border-slate-200">
                <button 
                  type="button"
                  onClick={() => setShowInvoiceModal(false)}
                  className="px-5 py-2 border border-slate-300 font-display text-[10px] font-bold uppercase tracking-wider hover:bg-slate-50 cursor-pointer"
                >
                  Cerrar Vista de Inventario
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </motion.div>
  );
}
