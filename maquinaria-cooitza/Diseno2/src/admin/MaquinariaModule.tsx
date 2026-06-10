import React, { useState, useEffect, useRef } from "react";
import { 
  Tractor, 
  Construction, 
  Wrench, 
  CircleSlash, 
  Plus, 
  Check, 
  Edit, 
  Trash2, 
  X, 
  CloudUpload, 
  Search, 
  MoreVertical, 
  Filter, 
  Forklift
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

interface Machinery {
  id: string;
  name: string; // e.g., "Excavator 320 GC"
  category: "Tractor" | "Excavadora" | "Cargadora" | "Bulldozer" | "Grúa";
  brand: string; // e.g., "CAT | Caterpillar"
  serialId: string; // e.g., "ID-7742-XP"
  accumulatedHours: number;
  nextService: string; // e.g., "15 OCT 24"
  status: "Operativo" | "Mantenimiento" | "Fuera de Servicio";
  photoUrl?: string;
}

interface MaquinariaModuleProps {
  onMachineryChange?: (count: number) => void;
}

const DEFAULT_MACHINERY_PHOTOS: Record<string, string> = {
  "Excavadora": "https://lh3.googleusercontent.com/aida-public/AB6AXuCW4-n0Uts09omCrK9hJAsromc1Y7GCJOZdeCvW2-u5sfFJXqom9XcKgbiq51rcx0mQNuEyHdpIGs8r9yViHSIpGwQ-Z7-RPkZ35ItK-6bQfr3kdlj5PT9e5KXQB3gtC7eSS279VcUjQS7-RNR9MPbwf5ypPuTg4CgEMIhyaVTzA00eWFBzQ74Pu3JtOSHdgJcFGtuDXY8l6dlcOrHUitHLowY1QP3UIFOg92Wkg41214T-JmcBsgoF8wyXoCRHv3C6SVyFE96IGl5b",
  "Tractor": "https://lh3.googleusercontent.com/aida-public/AB6AXuCtWEyh1Z-AVKb8m7r1Xd4QhYcVyxqNgGs7-QDVKHd0JvWlxT5MCJ0EPmyeydptGOjpmTw3CVlGGHGm53HGi_fza4tXXmiVp3tTR6S2n7gc02D3GN7Ko5Lc8Gv-BkHjm2F9kcmNC5ezQd7YofIuYhuYnHs-50gaNnQv7Livvi7M1RvyouOyT0-aegn6hvLevJh28ZSBMI76QCDIx27OkhuzjNPbxMQu8-cl0ANrBMiXuPsIX7-OsUgTo7TgPkIZQCwhWHSIMCSQg7PB",
  "Cargadora": "https://lh3.googleusercontent.com/aida-public/AB6AXuAPH_LQU15grhFazspqrpDAmtqij-Yi5u8pti4HGaVVVa49RVExnL40aoKKWYuBPOrziyt5g1P6pGgUiFYDzWpjjdg10n6G293Fahquh4OpO2eXBRu_Yl-uUBULkorbqp9oN16B5Gt4PG24HIbpbL7Z1pnrdEmXpME2Gbn2PFyNe7t7rIkkdfW_i-cqfovF0AtH9eU5NaBycx-bhwYG18aVN7t9ZCam-M94lOheq8vxN54bn5Q1FiBKhPTQXuinSWwvEfzWYeJx3D4U",
  "Bulldozer": "https://lh3.googleusercontent.com/aida-public/AB6AXuCtWEyh1Z-AVKb8m7r1Xd4QhYcVyxqNgGs7-QDVKHd0JvWlxT5MCJ0EPmyeydptGOjpmTw3CVlGGHGm53HGi_fza4tXXmiVp3tTR6S2n7gc02D3GN7Ko5Lc8Gv-BkHjm2F9kcmNC5ezQd7YofIuYhuYnHs-50gaNnQv7Livvi7M1RvyouOyT0-aegn6hvLevJh28ZSBMI76QCDIx27OkhuzjNPbxMQu8-cl0ANrBMiXuPsIX7-OsUgTo7TgPkIZQCwhWHSIMCSQg7PB",
  "Grúa": "https://lh3.googleusercontent.com/aida-public/AB6AXuCW4-n0Uts09omCrK9hJAsromc1Y7GCJOZdeCvW2-u5sfFJXqom9XcKgbiq51rcx0mQNuEyHdpIGs8r9yViHSIpGwQ-Z7-RPkZ35ItK-6bQfr3kdlj5PT9e5KXQB3gtC7eSS279VcUjQS7-RNR9MPbwf5ypPuTg4CgEMIhyaVTzA00eWFBzQ74Pu3JtOSHdgJcFGtuDXY8l6dlcOrHUitHLowY1QP3UIFOg92Wkg41214T-JmcBsgoF8wyXoCRHv3C6SVyFE96IGl5b"
};

export default function MaquinariaModule({ onMachineryChange }: MaquinariaModuleProps) {
  const [machinery, setMachinery] = useState<Machinery[]>([]);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editId, setEditId] = useState<string | null>(null);
  
  // Search and Filter States
  const [showFilters, setShowFilters] = useState(false);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterCategory, setFilterCategory] = useState("todos");
  const [filterStatus, setFilterStatus] = useState("todos");

  // Floating Dropdown State for more_vert
  const [activeDropdownId, setActiveDropdownId] = useState<string | null>(null);

  // Form Fields
  const [brand, setBrand] = useState("");
  const [name, setName] = useState("");
  const [category, setCategory] = useState<"Tractor" | "Excavadora" | "Cargadora" | "Bulldozer" | "Grúa">("Excavadora");
  const [serialId, setSerialId] = useState("");
  const [accumulatedHours, setAccumulatedHours] = useState("");
  const [nextService, setNextService] = useState("");
  const [status, setStatus] = useState<"Operativo" | "Mantenimiento" | "Fuera de Servicio">("Operativo");
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);

  // Drag and drop photo state
  const [isDragOver, setIsDragOver] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Load from localStorage
  useEffect(() => {
    const saved = localStorage.getItem("cooitza_maquinaria");
    if (saved) {
      try {
        const data = JSON.parse(saved);
        // Map old data format to new structure
        const mappedData = data.map((item: any) => ({
          id: item.id,
          brand: item.brand || (item.name?.split(" ")[1] ? item.name.split(" ")[0] : "Cooitzá Brand"),
          name: item.name ? (item.name.includes(item.brand) ? item.name.replace(item.brand, "").trim() : item.name) : "Equipo No Definido",
          category: mapOldCategoryToNew(item.category || "excavadora"),
          serialId: item.serialId || "ID-" + item.id.toUpperCase(),
          accumulatedHours: item.accumulatedHours || 0,
          nextService: item.nextService || "25 DIC 26",
          status: item.status || "Operativo",
          photoUrl: item.photoUrl || DEFAULT_MACHINERY_PHOTOS[mapOldCategoryToNew(item.category || "excavadora")]
        }));
        setMachinery(mappedData);
        triggerSync(mappedData);
      } catch (err) {
        console.error("Failed to parse machinery data", err);
      }
    } else {
      const defaultMachinery: Machinery[] = [
        { 
          id: "m1", 
          brand: "Caterpillar", 
          name: "Excavator 320 GC", 
          category: "Excavadora", 
          serialId: "ID-7742-XP", 
          accumulatedHours: 1240, 
          nextService: "15 OCT 24", 
          status: "Operativo", 
          photoUrl: DEFAULT_MACHINERY_PHOTOS["Excavadora"]
        },
        { 
          id: "m2", 
          brand: "John Deere", 
          name: "8R 370 Series", 
          category: "Tractor", 
          serialId: "ID-9921-JD", 
          accumulatedHours: 850, 
          nextService: "22 NOV 24", 
          status: "Operativo", 
          photoUrl: DEFAULT_MACHINERY_PHOTOS["Tractor"]
        },
        { 
          id: "m3", 
          brand: "Komatsu", 
          name: "WA200-8", 
          category: "Cargadora", 
          serialId: "ID-4432-KM", 
          accumulatedHours: 3120, 
          nextService: "01 DEC 24", 
          status: "Mantenimiento", 
          photoUrl: DEFAULT_MACHINERY_PHOTOS["Cargadora"]
        }
      ];
      setMachinery(defaultMachinery);
      localStorage.setItem("cooitza_maquinaria", JSON.stringify(defaultMachinery));
      triggerSync(defaultMachinery);
    }
  }, []);

  const mapOldCategoryToNew = (oldCat: string): "Tractor" | "Excavadora" | "Cargadora" | "Bulldozer" | "Grúa" => {
    const norm = oldCat.toLowerCase();
    if (norm === "tractor") return "Tractor";
    if (norm === "excavadora") return "Excavadora";
    if (norm === "retro") return "Grúa";
    if (norm === "rodo") return "Bulldozer";
    if (norm === "pipa" || norm === "cargadora") return "Cargadora";
    return "Excavadora";
  };

  const triggerSync = (updated: Machinery[]) => {
    if (onMachineryChange) {
      onMachineryChange(updated.length);
    }
  };

  const persistMachinery = (updated: Machinery[]) => {
    setMachinery(updated);
    localStorage.setItem("cooitza_maquinaria", JSON.stringify(updated));
    triggerSync(updated);
  };

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

  const openAddModal = () => {
    setEditId(null);
    setBrand("");
    setName("");
    setCategory("Excavadora");
    setSerialId("");
    setAccumulatedHours("");
    setNextService("");
    setStatus("Operativo");
    setPhotoPreview(null);
    setIsModalOpen(true);
  };

  const openEditModal = (item: Machinery) => {
    setEditId(item.id);
    setBrand(item.brand);
    setName(item.name);
    setCategory(item.category);
    setSerialId(item.serialId);
    setAccumulatedHours(item.accumulatedHours.toString());
    setNextService(item.nextService);
    setStatus(item.status);
    setPhotoPreview(item.photoUrl || null);
    setIsModalOpen(true);
    setActiveDropdownId(null);
  };

  const handleDelete = (id: string) => {
    if (window.confirm("¿Está seguro de remover permanentemente este activo de maquinaria pesada?")) {
      const updated = machinery.filter(m => m.id !== id);
      persistMachinery(updated);
      setActiveDropdownId(null);
    }
  };

  const handleSaveMachinery = (e: React.FormEvent) => {
    e.preventDefault();
    if (!name.trim() || !brand.trim() || !serialId.trim()) return;

    const chosenPhoto = photoPreview || DEFAULT_MACHINERY_PHOTOS[category] || DEFAULT_MACHINERY_PHOTOS["Excavadora"];

    if (editId) {
      const updated = machinery.map(m => m.id === editId ? {
        ...m,
        brand,
        name,
        category,
        serialId,
        accumulatedHours: parseFloat(accumulatedHours) || 0,
        nextService: nextService || "Sin Programar",
        status,
        photoUrl: chosenPhoto
      } : m);
      persistMachinery(updated);
    } else {
      const newM: Machinery = {
        id: "m_" + Date.now(),
        brand,
        name,
        category,
        serialId,
        accumulatedHours: parseFloat(accumulatedHours) || 0,
        nextService: nextService || "Sin Programar",
        status,
        photoUrl: chosenPhoto
      };
      persistHydrate([newM, ...machinery]);
    }

    setIsModalOpen(false);
  };

  // Helper workaround to avoid variable namespace conflicts
  const persistHydrate = (data: Machinery[]) => {
    persistMachinery(data);
  };

  const filteredMachinery = machinery.filter(m => {
    const matchesSearch = 
      m.brand.toLowerCase().includes(searchTerm.toLowerCase()) ||
      m.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      m.serialId.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesCategory = filterCategory === "todos" || m.category === filterCategory;
    const matchesStatus = filterStatus === "todos" || m.status === filterStatus;

    return matchesSearch && matchesCategory && matchesStatus;
  });

  return (
    <motion.div 
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      className="flex flex-col gap-6 w-full text-[#191c1d]"
    >
      {/* Header and Add Button Section */}
      <div className="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 border-b border-[#cbd5e1] pb-6">
        <div>
          <h2 className="font-display text-[32px] font-bold text-[#191c1d] tracking-tight">Gestión de Maquinaria</h2>
          <p className="font-body-lg text-sm text-[#524534] mt-2">Control centralizado de activos industriales y equipos pesados.</p>
        </div>
        
        <button 
          type="button"
          onClick={openAddModal}
          className="bg-[#f5a623] hover:bg-[#e09212] text-[#291800] font-display text-xs font-bold uppercase tracking-wider px-6 py-3 flex items-center gap-2 transition-all cursor-pointer border border-[#d7c3ae]"
        >
          <Plus size={16} />
          <span>Nueva Maquinaria</span>
        </button>
      </div>

      {/* Filters and Stats Bento Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-stretch">
        
        {/* KPI: Total Activos */}
        <div className="bg-white p-5 border border-[#cbd5e1] flex flex-col justify-between shadow-sm">
          <span className="font-display text-[11px] font-bold text-[#524534] uppercase tracking-wider">Total Activos</span>
          <span className="font-display text-4xl font-extrabold text-[#835500] mt-4">{machinery.length}</span>
        </div>

        {/* KPI: En Operación */}
        <div className="bg-white p-5 border border-[#cbd5e1] flex flex-col justify-between shadow-sm">
          <span className="font-display text-[11px] font-bold text-[#524534] uppercase tracking-wider">En Operación</span>
          <div className="flex items-center gap-2 mt-4">
            <div className="w-2.5 h-2.5 rounded-full bg-[#f5a623] animate-pulse"></div>
            <span className="font-display text-4xl font-extrabold text-[#191c1d]">
              {machinery.filter(m => m.status === "Operativo").length}
            </span>
          </div>
        </div>

        {/* KPI: En Taller / Mantenimiento */}
        <div className="bg-white p-5 border border-[#cbd5e1] flex flex-col justify-between shadow-sm">
          <span className="font-display text-[11px] font-bold text-[#524534] uppercase tracking-wider">En Taller / Mando</span>
          <div className="flex items-center gap-2 mt-4">
            <div className="w-2.5 h-2.5 rounded-full bg-[#ba1a1a]"></div>
            <span className="font-display text-4xl font-extrabold text-[#ba1a1a]">
              {machinery.filter(m => m.status === "Mantenimiento").length}
            </span>
          </div>
        </div>

        {/* Action Toggle Filter Box */}
        <button 
          type="button"
          onClick={() => setShowFilters(!showFilters)}
          className={`p-5 flex flex-col justify-center items-center border-2 border-dashed transition-all cursor-pointer text-center ${
            showFilters 
              ? "bg-[#835500]/5 border-[#835500] text-[#835500]" 
              : "border-[#cbd5e1] bg-slate-50 hover:bg-slate-100 text-[#524534]"
          }`}
        >
          <Filter size={20} className={showFilters ? "text-[#835500]" : "text-[#524534]"} />
          <p className="font-display text-xs font-bold uppercase tracking-wider mt-2">Filtrar Listado</p>
        </button>

      </div>

      {/* FILTER DRAWER SLIDE DOWN PANEL */}
      <AnimatePresence>
        {showFilters && (
          <motion.div 
            initial={{ height: 0, opacity: 0 }}
            animate={{ height: "auto", opacity: 1 }}
            exit={{ height: 0, opacity: 0 }}
            className="overflow-hidden bg-white border border-[#cbd5e1] shadow-sm p-5 grid grid-cols-1 md:grid-cols-3 gap-4"
          >
            {/* Search Input */}
            <div className="flex flex-col gap-1">
              <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Buscar por Texto</label>
              <div className="relative">
                <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                <input 
                  type="text"
                  placeholder="Ej. Caterpillar, WA200..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-9 pr-3 py-2 border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none bg-white transition-colors"
                />
              </div>
            </div>

            {/* Category Filter */}
            <div className="flex flex-col gap-1">
              <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Filtrar por Tipo</label>
              <select 
                value={filterCategory}
                onChange={(e) => setFilterCategory(e.target.value)}
                className="w-full border border-[#cbd5e1] px-3 py-2 text-xs bg-white outline-none cursor-pointer focus:border-[#835500]"
              >
                <option value="todos">Todos los Equipos</option>
                <option value="Excavadora">Excavadora</option>
                <option value="Tractor">Tractor</option>
                <option value="Cargadora">Cargadora</option>
                <option value="Bulldozer">Bulldozer</option>
                <option value="Grúa">Grúa</option>
              </select>
            </div>

            {/* Status Filter */}
            <div className="flex flex-col gap-1">
              <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Estado Técnico</label>
              <select 
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full border border-[#cbd5e1] px-3 py-2 text-xs bg-white outline-none cursor-pointer focus:border-[#835500]"
              >
                <option value="todos">Cualquier Estado</option>
                <option value="Operativo">Operativo (En Campo)</option>
                <option value="Mantenimiento">Taller de Mantenimiento</option>
                <option value="Fuera de Servicio">Fuera de Servicio</option>
              </select>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Machinery Cards Grid */}
      {filteredMachinery.length === 0 ? (
        <div className="bg-white border border-[#cbd5e1] p-12 text-center text-slate-400 italic">
          No se encontraron activos industriales según los criterios especificados.
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {filteredMachinery.map((m) => (
            <div 
              key={m.id} 
              className="bg-white border border-[#cbd5e1] group hover:border-[#835500] transition-colors overflow-hidden flex flex-col md:flex-row h-full shadow-sm relative"
            >
              
              {/* Photo Box container with gray -> color hover frame */}
              <div className="w-full md:w-48 h-48 md:h-auto bg-slate-100 relative overflow-hidden shrink-0">
                <img 
                  src={m.photoUrl || DEFAULT_MACHINERY_PHOTOS[m.category]} 
                  alt={m.name} 
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-all duration-500" 
                />
                <div className="absolute top-3 left-3 bg-[#f5a623] text-[#291800] border border-[#d7c3ae] px-2 py-0.5 font-display text-[10px] font-bold uppercase tracking-wider">
                  {m.category}
                </div>
              </div>

              {/* Machinery Core Details Panel */}
              <div className="p-5 flex-1 flex flex-col justify-between relative">
                
                <div>
                  <div className="flex justify-between items-start gap-4">
                    <div>
                      <span className="font-display text-[10px] font-bold text-[#835500] uppercase tracking-wider">
                        {m.brand}
                      </span>
                      <h3 className="font-display text-lg font-black text-[#191c1d] mt-1 line-clamp-2">
                        {m.name}
                      </h3>
                    </div>
                    <span className="font-display text-[10px] font-bold text-[#835500] bg-[#f5a623]/10 border border-[#f5a623]/25 px-2.5 py-0.5">
                      {m.serialId}
                    </span>
                  </div>

                  {/* Status Badge */}
                  <div className="mt-3">
                    <span className={`inline-block px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-widest ${
                      m.status === "Operativo" ? "bg-emerald-50 text-emerald-800 border border-emerald-200" :
                      m.status === "Mantenimiento" ? "bg-amber-50 text-amber-800 border border-amber-200" :
                      "bg-red-50 text-red-800 border border-red-200"
                    }`}>
                      ● {m.status === "Operativo" ? "Operativo" : m.status === "Mantenimiento" ? "Taller" : "Fuera de Servicio"}
                    </span>
                  </div>
                </div>

                {/* Counter & Actions Bottom Row */}
                <div className="mt-5 pt-4 flex items-end justify-between border-t border-[#cbd5e1]">
                  <div className="flex gap-6">
                    <div className="flex flex-col">
                      <span className="text-[9px] font-bold text-[#524534] uppercase tracking-wider">Horas Uso</span>
                      <span className="font-display text-xs font-black text-[#191c1d] mt-0.5">
                        {m.accumulatedHours?.toLocaleString() || "0"} h
                      </span>
                    </div>

                    <div className="flex flex-col">
                      <span className="text-[9px] font-bold text-[#524534] uppercase tracking-wider">Próx. Service</span>
                      <span className="font-display text-xs font-black text-[#191c1d] mt-0.5">
                        {m.nextService || "Sin Programar"}
                      </span>
                    </div>
                  </div>

                  {/* dropdown action button */}
                  <div className="relative">
                    <button 
                      type="button"
                      onClick={() => setActiveDropdownId(activeDropdownId === m.id ? null : m.id)}
                      className="p-1.5 hover:bg-slate-100 border border-transparent hover:border-slate-200 transition-colors cursor-pointer text-[#524534]"
                      title="Opciones de activo"
                    >
                      <MoreVertical size={16} />
                    </button>

                    {/* Popover floating action panel */}
                    {activeDropdownId === m.id && (
                      <div className="absolute right-0 bottom-full mb-1 w-44 bg-white border border-[#cbd5e1] shadow-xl z-20 font-sans text-xs">
                        <button 
                          type="button"
                          onClick={() => openEditModal(m)}
                          className="w-full px-3 py-2.5 hover:bg-slate-50 text-left flex items-center gap-2 cursor-pointer border-b border-slate-100"
                        >
                          <Edit size={12} className="text-[#835500]" />
                          <span>Editar Maquinaria</span>
                        </button>
                        <button 
                          type="button"
                          onClick={() => handleDelete(m.id)}
                          className="w-full px-3 py-2.5 hover:bg-red-50 text-left flex items-center gap-2 text-red-600 cursor-pointer"
                        >
                          <Trash2 size={12} className="text-red-600" />
                          <span>Remover de Flota</span>
                        </button>
                      </div>
                    )}
                  </div>
                </div>

              </div>

            </div>
          ))}
        </div>
      )}

      {/* MODAL: REGISTRAR O EDITAR MAQUINARIA */}
      <AnimatePresence>
        {isModalOpen && (
          <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <motion.div 
              initial={{ scale: 0.96, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.96, opacity: 0 }}
              className="bg-white border border-[#cbd5e1] w-full max-w-2xl p-6 shadow-2xl relative overflow-y-auto max-h-[90vh]"
            >
              
              {/* Modal Header */}
              <div className="flex justify-between items-start mb-6 border-b border-[#cbd5e1] pb-4">
                <div>
                  <h3 className="font-display text-xl font-bold text-[#191c1d]">
                    {editId ? "Modificar Activo de Maquinaria" : "Registro de Maquinaria"}
                  </h3>
                  <p className="font-body-md text-xs text-[#524534] mt-1">Complete los datos técnicos oficiales para el activo.</p>
                </div>
                
                <button 
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="p-1 hover:bg-slate-100 rounded-full cursor-pointer text-[#524534]"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Form Input fields */}
              <form onSubmit={handleSaveMachinery} className="space-y-4">
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {/* Brand of fabricator */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Marca del Fabricante</label>
                    <input 
                      type="text"
                      value={brand}
                      onChange={(e) => setBrand(e.target.value)}
                      placeholder="Ej. Caterpillar, John Deere or Komatsu"
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-body-md transition-colors"
                      required
                    />
                  </div>

                  {/* Type/Category */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Tipo de Equipo</label>
                    <select 
                      value={category}
                      onChange={(e) => setCategory(e.target.value as any)}
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none cursor-pointer font-body-md transition-colors"
                    >
                      <option value="Excavadora">Excavadora</option>
                      <option value="Tractor">Tractor</option>
                      <option value="Cargadora">Cargadora</option>
                      <option value="Bulldozer">Bulldozer</option>
                      <option value="Grúa">Grúa</option>
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {/* Model / Name */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Modelo / Nombre</label>
                    <input 
                      type="text"
                      value={name}
                      onChange={(e) => setName(e.target.value)}
                      placeholder="Ej. Excavator 320 GC o 8R 370"
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-body-md transition-colors"
                      required
                    />
                  </div>

                  {/* Serial/ID */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Identificador Único (Serial/ID)</label>
                    <input 
                      type="text"
                      value={serialId}
                      onChange={(e) => setSerialId(e.target.value)}
                      placeholder="Ej. ID-7742-XP"
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-body-md transition-colors"
                      required
                    />
                  </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* Current Hours */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Horas Acumuladas</label>
                    <input 
                      type="number"
                      value={accumulatedHours}
                      onChange={(e) => setAccumulatedHours(e.target.value)}
                      placeholder="Ej. 1240"
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-body-md transition-colors"
                      min="0"
                      step="0.1"
                      required
                    />
                  </div>

                  {/* Next Service */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Próximo Service Fija</label>
                    <input 
                      type="text"
                      value={nextService}
                      onChange={(e) => setNextService(e.target.value)}
                      placeholder="Ej: 15 OCT 24"
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none font-body-md transition-colors"
                    />
                  </div>

                  {/* Operational status */}
                  <div className="flex flex-col gap-1">
                    <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Condición del Activo</label>
                    <select 
                      value={status}
                      onChange={(e) => setStatus(e.target.value as any)}
                      className="p-2.5 bg-white border border-[#cbd5e1] focus:border-[#835500] text-xs outline-none cursor-pointer font-body-md transition-colors"
                    >
                      <option value="Operativo">Operativo (En Campo)</option>
                      <option value="Mantenimiento">Mantenimiento / Taller</option>
                      <option value="Fuera de Servicio">Fuera de Servicio</option>
                    </select>
                  </div>
                </div>

                {/* Photo Drag drop upload area */}
                <div className="flex flex-col gap-1">
                  <label className="text-[10px] font-bold text-[#524534] uppercase tracking-wider">Fotografía del Activo</label>
                  
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
                    className={`border-2 border-dashed p-10 flex flex-col items-center justify-center gap-3 transition-colors cursor-pointer group rounded-sm ${
                      isDragOver 
                        ? "border-[#835500] bg-[#835500]/5" 
                        : "border-[#cbd5e1] bg-slate-50 hover:bg-slate-100"
                    }`}
                  >
                    {photoPreview ? (
                      <div className="relative w-full max-w-[200px] h-28 overflow-hidden border border-slate-200">
                        <img 
                          src={photoPreview} 
                          alt="Machinery Preview" 
                          referrerPolicy="no-referrer"
                          className="w-full h-full object-cover" 
                        />
                        <button 
                          type="button"
                          onClick={(e) => {
                            e.stopPropagation();
                            setPhotoPreview(null);
                          }}
                          className="absolute top-1.5 right-1.5 bg-[#ba1a1a] text-white p-1 rounded hover:bg-red-700 transition-colors"
                          title="Remover foto"
                        >
                          <X size={12} />
                        </button>
                      </div>
                    ) : (
                      <>
                        <CloudUpload className="w-12 h-12 text-[#857462] group-hover:text-[#835500] transition-colors" />
                        <div className="text-center">
                          <p className="font-body-md text-xs text-[#524534] font-medium">
                            Arrastre la imagen del activo o <span className="text-[#835500] font-bold underline">examine</span>
                          </p>
                          <p className="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">
                            Formatos: JPG, PNG (Max 5MB)
                          </p>
                        </div>
                      </>
                    )}
                  </div>
                </div>

                {/* Modal actions */}
                <div className="pt-4 flex justify-end gap-3 border-t border-[#cbd5e1]">
                  <button 
                    type="button"
                    onClick={() => setIsModalOpen(false)}
                    className="px-6 py-2.5 border border-[#cbd5e1] hover:bg-slate-50 font-display text-[10px] font-bold uppercase tracking-wider transition-colors cursor-pointer"
                  >
                    Cancelar
                  </button>
                  <button 
                    type="submit"
                    className="px-6 py-2.5 bg-[#f5a623] text-[#291800] border border-[#d7c3ae] font-display text-[10px] font-extrabold uppercase tracking-widest hover:brightness-105 transition-all cursor-pointer"
                  >
                    Confirmar Registro
                  </button>
                </div>

              </form>

            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </motion.div>
  );
}
