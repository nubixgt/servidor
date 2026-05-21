import { useState, FormEvent } from "react";
import { motion, AnimatePresence } from "motion/react";
import { 
  Folder, 
  FolderPlus, 
  File, 
  FileText, 
  Plus, 
  Search, 
  Grid, 
  List, 
  ChevronRight, 
  Download, 
  Share2, 
  MoreVertical, 
  Layers, 
  ShieldCheck, 
  Camera, 
  FileSpreadsheet, 
  BookOpen, 
  Upload, 
  X, 
  CheckCircle2, 
  Calendar,
  AlertCircle,
  HardDrive
} from "lucide-react";

interface ProjectFolder {
  id: string;
  name: string;
  filesCount: number;
  size: string;
  colorClass: string;
}

interface DigitalFile {
  id: string;
  name: string;
  category: "all" | "planos" | "contratos" | "licencias" | "evidencia";
  folderId: string;
  size: string;
  modifiedAt: string;
  modifiedBy: string;
  ext: "pdf" | "dwg" | "docx" | "xlsx" | "png";
  thumbnail?: string;
}

interface Permit {
  id: string;
  name: string;
  project: string;
  status: "vence_pronto" | "vigente";
  expiresAt: string;
}

export default function DigitalDocuments() {
  const [viewMode, setViewMode] = useState<"grid" | "list">("grid");
  const [selectedCategory, setSelectedCategory] = useState<string>("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedFolderId, setSelectedFolderId] = useState<string | null>(null);
  
  // Modals status
  const [showUploadModal, setShowUploadModal] = useState(false);
  const [showNewFolderModal, setShowNewFolderModal] = useState(false);
  
  // Notification Toast
  const [notification, setNotification] = useState("");

  // Create Folder form state
  const [folderName, setFolderName] = useState("");

  // Create File form state
  const [fileName, setFileName] = useState("");
  const [fileCat, setFileCat] = useState<"planos" | "contratos" | "licencias" | "evidencia">("planos");
  const [fileExtension, setFileExtension] = useState<"pdf" | "dwg" | "docx" | "xlsx" | "png">("pdf");
  const [fileFolder, setFileFolder] = useState<string>("Alpha");

  const [folders, setFolders] = useState<ProjectFolder[]>([
    { id: "Alpha", name: "Torre Residencial Alpha", filesCount: 2450, size: "1.2 GB", colorClass: "text-primary" },
    { id: "Sur", name: "Puente Interconector Sur", filesCount: 890, size: "450 MB", colorClass: "text-indigo-400" },
    { id: "SkyPort", name: "Centro Logístico SkyPort", filesCount: 156, size: "89 MB", colorClass: "text-emerald-400" }
  ]);

  const [files, setFiles] = useState<DigitalFile[]>([
    {
      id: "DOC-1",
      name: "Plano_Estructural_V2.pdf",
      category: "planos",
      folderId: "Alpha",
      size: "18.4 MB",
      modifiedAt: "hace 2h",
      modifiedBy: "Ing. Carlos Ruiz",
      ext: "pdf",
      thumbnail: "https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=400&q=80"
    },
    {
      id: "DOC-2",
      name: "Instalaciones_Hidrosanitarias.dwg",
      category: "planos",
      folderId: "Alpha",
      size: "45.2 MB",
      modifiedAt: "ayer",
      modifiedBy: "Arq. M. Lopez",
      ext: "dwg"
    },
    {
      id: "DOC-3",
      name: "Contrato_Suministros_Aceros.docx",
      category: "contratos",
      folderId: "Sur",
      size: "1.4 MB",
      modifiedAt: "hace 3d",
      modifiedBy: "Jurídico",
      ext: "docx",
      thumbnail: "https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=400&q=80"
    },
    {
      id: "DOC-4",
      name: "Presupuesto_General_Estructuras.xlsx",
      category: "contratos",
      folderId: "SkyPort",
      size: "4.8 MB",
      modifiedAt: "hace 1 semana",
      modifiedBy: "Aux. Contable",
      ext: "xlsx"
    },
    {
      id: "DOC-5",
      name: "Permiso_Ambiental_Zona_Sur.pdf",
      category: "licencias",
      folderId: "Sur",
      size: "2.1 MB",
      modifiedAt: "hace 5 días",
      modifiedBy: "Ing. Carlos Ruiz",
      ext: "pdf"
    }
  ]);

  const [permits, setPermits] = useState<Permit[]>([
    { id: "P-1", name: "Licencia de Construcción #4502", project: "Torre Alpha", status: "vence_pronto", expiresAt: "15 Dic 2026" },
    { id: "P-2", name: "Permiso Ambiental Zona Sur", project: "Puente Interconector", status: "vigente", expiresAt: "02 Feb 2027" }
  ]);

  const showToast = (msg: string) => {
    setNotification(msg);
    setTimeout(() => {
      setNotification("");
    }, 4500);
  };

  const handleCreateFolder = (e: FormEvent) => {
    e.preventDefault();
    if (!folderName.trim()) return;

    // Avoid duplicate folders for same ID
    const generatedId = folderName.replace(/\s+/g, "").substring(0, 10);
    const newFold: ProjectFolder = {
      id: generatedId,
      name: folderName,
      filesCount: 0,
      size: "0 Bytes",
      colorClass: "text-amber-400"
    };

    setFolders([...folders, newFold]);
    setFolderName("");
    setShowNewFolderModal(false);
    showToast(`Carpeta "${folderName}" creada exitosamente.`);
  };

  const handleUploadFile = (e: FormEvent) => {
    e.preventDefault();
    if (!fileName.trim()) return;

    const newFileObj: DigitalFile = {
      id: "DOC-" + Math.floor(Math.random() * 900 + 100),
      name: fileName.includes(".") ? fileName : `${fileName}.${fileExtension}`,
      category: fileCat,
      folderId: fileFolder,
      size: `${(Math.random() * 10 + 1).toFixed(1)} MB`,
      modifiedAt: "ahora mismo",
      modifiedBy: "Admin Principal",
      ext: fileExtension
    };

    // Update parent folder counters
    setFolders(prev => prev.map(fold => {
      if (fold.id === fileFolder) {
        return {
          ...fold,
          filesCount: fold.filesCount + 1,
          size: fold.size === "0 Bytes" ? "5.4 MB" : fold.size
        };
      }
      return fold;
    }));

    setFiles([newFileObj, ...files]);
    setFileName("");
    setShowUploadModal(false);
    showToast(`Archivo "${newFileObj.name}" subido a la carpeta.`);
  };

  const handleShare = (fileName: string) => {
    showToast(`Enlace de descarga compartido para "${fileName}"`);
  };

  const handleDownload = (fileName: string) => {
    showToast(`Iniciando descarga de "${fileName}"`);
  };

  // Filters logic
  const filteredFiles = files.filter(f => {
    const matchesSearch = f.name.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          f.modifiedBy.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = selectedCategory === "all" || f.category === selectedCategory;
    const matchesFolder = selectedFolderId === null || f.folderId === selectedFolderId;
    return matchesSearch && matchesCategory && matchesFolder;
  });

  return (
    <div className="pt-20 pb-20 px-10 max-w-7xl mx-auto space-y-12 text-white">
      {/* Toast message wrapper */}
      <AnimatePresence>
        {notification && (
          <motion.div
            initial={{ opacity: 0, y: -20, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, scale: 0.95 }}
            className="fixed top-24 right-10 z-50 bg-[#0052ff]/20 border border-primary text-white backdrop-blur-xl px-6 py-4 rounded-2xl flex items-center gap-3 shadow-2xl shadow-primary/20"
          >
            <CheckCircle2 className="w-5 h-5 text-primary" />
            <span className="text-xs font-black uppercase tracking-wider">{notification}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Header and top actionable items */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div className="space-y-3">
          <h2 className="text-4xl font-black text-white italic uppercase tracking-tighter">Documentos Digitales</h2>
          <div className="flex items-center gap-2 text-white/40 text-xs font-extrabold uppercase tracking-widest">
            <span className="cursor-pointer hover:text-white" onClick={() => setSelectedFolderId(null)}>Mi Unidad</span>
            {selectedFolderId && (
              <>
                <ChevronRight className="w-3.5 h-3.5" />
                <span className="text-primary tracking-wider">{folders.find(fd => fd.id === selectedFolderId)?.name}</span>
              </>
            )}
          </div>
        </div>

        {/* Global CTA buttons stack */}
        <div className="flex flex-wrap gap-4">
          <button 
            onClick={() => setShowNewFolderModal(true)}
            className="flex items-center gap-2.5 px-6 py-4 rounded-2xl border border-white/5 bg-white/5 text-white/80 font-black text-xs uppercase tracking-widest hover:bg-white/10 hover:scale-105 active:scale-95 transition-all"
          >
            <FolderPlus className="w-4 h-4 text-primary" /> Nueva Carpeta
          </button>

          <button 
            onClick={() => setShowUploadModal(true)}
            className="glass-button-primary bg-primary border-primary border text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-2.5 hover:scale-105 active:scale-95 transition-all shadow-primary/20"
          >
            <Upload className="w-4 h-4" /> Subir Archivo
          </button>
        </div>
      </div>

      {/* Primary filters Quick bar control & view toggles */}
      <div className="flex flex-col md:flex-row justify-between items-center gap-6 border-b border-white/5 pb-6">
        {/* Category buttons */}
        <div className="flex gap-2 bg-white/5 p-1.5 rounded-2xl border border-white/5 overflow-x-auto w-full md:w-auto scrollbar-hide">
          <button 
            onClick={() => { setSelectedCategory("all"); setSelectedFolderId(null); }}
            className={`px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap ${
              selectedCategory === "all" ? "bg-primary text-white shadow-lg shadow-primary/25" : "text-white/40 hover:text-white"
            }`}
          >
            Todos
          </button>

          <button 
            onClick={() => setSelectedCategory("planos")}
            className={`px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap ${
              selectedCategory === "planos" ? "bg-primary text-white shadow-lg" : "text-white/40 hover:text-white"
            }`}
          >
            Planos (PDF/DWG)
          </button>

          <button 
            onClick={() => setSelectedCategory("contratos")}
            className={`px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap ${
              selectedCategory === "contratos" ? "bg-primary text-white shadow-lg" : "text-white/40 hover:text-white"
            }`}
          >
            Contratos
          </button>

          <button 
            onClick={() => setSelectedCategory("licencias")}
            className={`px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap ${
              selectedCategory === "licencias" ? "bg-primary text-white shadow-lg" : "text-white/40 hover:text-white"
            }`}
          >
            Licencias
          </button>

          <button 
            onClick={() => setSelectedCategory("evidencia")}
            className={`px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-wider transition-all whitespace-nowrap ${
              selectedCategory === "evidencia" ? "bg-primary text-white shadow-lg" : "text-white/40 hover:text-white"
            }`}
          >
            Evidencia Obra
          </button>
        </div>

        {/* Search Input and View toggles */}
        <div className="flex items-center gap-4 w-full md:w-auto justify-end">
          <div className="relative w-full md:w-64">
            <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
            <input 
              type="text"
              placeholder="Buscar documentos..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="glass-input pl-10 pr-4 py-3 rounded-xl text-xs uppercase tracking-wider font-extrabold w-full text-white placeholder:text-white/20"
            />
          </div>

          <div className="flex gap-1.5 bg-white/5 p-1 rounded-xl">
            <button 
              onClick={() => setViewMode("grid")}
              className={`p-2 rounded-lg transition-transform ${viewMode === "grid" ? "bg-primary text-white" : "text-white/30"}`}
            >
              <Grid className="w-4 h-4" />
            </button>
            <button 
              onClick={() => setViewMode("list")}
              className={`p-2 rounded-lg transition-transform ${viewMode === "list" ? "bg-primary text-white" : "text-white/30"}`}
            >
              <List className="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      {/* Folder representation grid */}
      <section className="space-y-6">
        <h3 className="text-xl font-black italic uppercase tracking-wider flex items-center gap-2">
          <Folder className="w-5 h-5 text-primary" /> Carpetas de Proyecto
        </h3>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
          {folders.map((fold) => (
            <div 
              key={fold.id}
              onClick={() => setSelectedFolderId(selectedFolderId === fold.id ? null : fold.id)}
              className={`glass-card p-8 rounded-3xl border transition-all cursor-pointer relative group flex flex-col justify-between h-44 ${
                selectedFolderId === fold.id ? "bg-primary/10 border-primary" : "border-white/5 hover:border-white/10 hover:bg-white/5"
              }`}
            >
              <div className="flex justify-between items-start">
                <Folder className={`w-10 h-10 ${fold.colorClass}`} />
                <button className="text-white/35 group-hover:text-white transition-colors">
                  <MoreVertical className="w-4 h-4" />
                </button>
              </div>

              <div>
                <h4 className="font-extrabold text-white text-base tracking-tight uppercase italic group-hover:text-primary transition-colors">{fold.name}</h4>
                <p className="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1">
                  {fold.filesCount.toLocaleString()} archivos • {fold.size}
                </p>
              </div>
            </div>
          ))}

          {/* Special create folder item card */}
          <div 
            onClick={() => setShowNewFolderModal(true)}
            className="bg-white/5 border-2 border-dashed border-white/5 p-8 rounded-3xl hover:bg-white/10 transition-all cursor-pointer flex flex-col items-center justify-center text-center h-44"
          >
            <FolderPlus className="w-8 h-8 text-white/30 mb-2" />
            <p className="text-[10px] font-black uppercase tracking-widest text-white/40">Nueva Carpeta de Proyecto</p>
          </div>
        </div>
      </section>

      {/* Document grid listing */}
      <section className="space-y-6">
        <div className="flex justify-between items-center pb-2">
          <h3 className="text-xl font-black italic uppercase tracking-wider flex items-center gap-2">
            <BookOpen className="w-5 h-5 text-primary" /> Archivos Registrados
          </h3>
          {selectedFolderId && (
            <button 
              onClick={() => setSelectedFolderId(null)}
              className="text-primary font-black uppercase tracking-widest text-[9.5px] hover:text-white transition-colors"
            >
              Cerrar Filtro de Carpeta
            </button>
          )}
        </div>

        {viewMode === "grid" ? (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <AnimatePresence initial={false}>
              {filteredFiles.length === 0 ? (
                <div className="col-span-full py-16 text-center text-white/30 font-black uppercase tracking-widest text-xs">
                  No se han encontrado archivos en este directorio con los filtros provistos.
                </div>
              ) : (
                filteredFiles.map((file) => (
                  <motion.div 
                    layout
                    key={file.id}
                    className="glass-card rounded-3xl overflow-hidden border border-white/5 group hover:border-white/10 transition-all shadow-xl flex flex-col justify-between"
                  >
                    {/* Visual wrapper */}
                    <div className="h-44 bg-white/5 relative overflow-hidden flex items-center justify-center">
                      {file.thumbnail ? (
                        <img 
                          src={file.thumbnail}
                          alt={file.name}
                          className="w-full h-full object-cover grayscale group-hover:grayscale-0 transition-transform duration-500 group-hover:scale-105"
                        />
                      ) : (
                        <div className="flex flex-col items-center gap-2 text-white/30">
                          <FileText className="w-12 h-12 text-primary/60" />
                          <span className="text-[10px] font-black uppercase tracking-[0.2em]">{file.ext}</span>
                        </div>
                      )}

                      {/* Pill indicator badge */}
                      <span className="absolute top-4 left-4 bg-slate-950/80 border border-white/10 text-white font-black text-[9px] px-3 py-1 rounded-xl uppercase tracking-widest">
                        {file.ext}
                      </span>
                    </div>

                    {/* Metadata summary grid */}
                    <div className="p-6 space-y-4">
                      <div className="min-w-0">
                        <h4 className="font-extrabold text-white text-sm tracking-tight truncate uppercase italic">{file.name}</h4>
                        <p className="text-[10px] font-bold text-white/30 tracking-wider uppercase mt-1 balance">
                          Modificado {file.modifiedAt} • {file.modifiedBy}
                        </p>
                      </div>

                      <div className="flex justify-between items-center text-[10px] font-black uppercase tracking-widest border-t border-white/5 pt-4">
                        <span className="text-white/40">{file.size}</span>
                        <div className="flex gap-2">
                          <button 
                            onClick={() => handleDownload(file.name)}
                            className="p-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition-colors border border-white/5"
                          >
                            <Download className="w-3.5 h-3.5" />
                          </button>
                          <button 
                            onClick={() => handleShare(file.name)}
                            className="p-2 bg-white/5 hover:bg-white/10 text-white/70 hover:text-white rounded-lg transition-colors border border-white/5"
                          >
                            <Share2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </motion.div>
                ))
              )}
            </AnimatePresence>
          </div>
        ) : (
          /* List mode layout table */
          <div className="glass-card rounded-[32px] overflow-hidden border border-white/5 shadow-2xl">
            <div className="overflow-x-auto">
              <table className="w-full text-left">
                <thead>
                  <tr className="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                    <th className="px-10 py-5">Nombre del archivo</th>
                    <th className="px-10 py-5">Modificado</th>
                    <th className="px-10 py-5">Ubicación</th>
                    <th className="px-10 py-5 text-right">Tamaño</th>
                    <th className="px-10 py-5 text-right">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-white/5">
                  {filteredFiles.map((file) => (
                    <tr key={file.id} className="hover:bg-white/5 transition-all">
                      <td className="px-10 py-5">
                        <div className="flex items-center gap-3">
                          <FileText className="w-4.5 h-4.5 text-primary" />
                          <span className="font-extrabold uppercase italic tracking-tight text-white">{file.name}</span>
                        </div>
                      </td>
                      <td className="px-10 py-5 text-xs font-bold text-white/50 lowercase">
                        {file.modifiedAt} por {file.modifiedBy}
                      </td>
                      <td className="px-10 py-5">
                        <span className="text-[10px] font-black tracking-wider uppercase text-primary bg-primary/20 px-2.5 py-1 rounded">
                          {file.folderId}
                        </span>
                      </td>
                      <td className="px-10 py-5 text-right font-black italic text-sm text-white/70">
                        {file.size}
                      </td>
                      <td className="px-10 py-5 text-right space-x-1">
                        <button 
                          onClick={() => handleDownload(file.name)}
                          className="p-2 hover:bg-white/5 text-white/40 hover:text-white rounded-xl transition-colors inline-block"
                        >
                          <Download className="w-4 h-4" />
                        </button>
                        <button 
                          onClick={() => handleShare(file.name)}
                          className="p-2 hover:bg-white/5 text-white/40 hover:text-white rounded-xl transition-colors inline-block"
                        >
                          <Share2 className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </section>

      {/* Licenses and Permits near to expiry Section */}
      <section className="space-y-6">
        <h3 className="text-xl font-black italic uppercase tracking-wider flex items-center gap-2">
          <ShieldCheck className="w-5 h-5 text-primary" /> Licencias y Permisos Próximos a Vencer
        </h3>

        <div className="glass-card rounded-[40px] overflow-hidden border border-white/5 shadow-2xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left">
              <thead>
                <tr className="text-[10px] font-extrabold text-white/30 uppercase tracking-widest border-b border-white/5 bg-white/5">
                  <th className="px-10 py-5">Documento</th>
                  <th className="px-10 py-5">Proyecto Asociado</th>
                  <th className="px-10 py-5 text-center">Estado</th>
                  <th className="px-10 py-5 text-center">Vencimiento oficial</th>
                  <th className="px-10 py-5 text-right">Acción</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-white/5">
                {permits.map((p) => (
                  <tr key={p.id} className="hover:bg-white/5 transition-all">
                    <td className="px-10 py-6">
                      <div className="flex items-center gap-3">
                        <ShieldCheck className="w-5 h-5 text-emerald-400" />
                        <span className="font-extrabold text-base text-white tracking-tight uppercase italic">{p.name}</span>
                      </div>
                    </td>
                    <td className="px-10 py-6 text-sm text-white/60 font-black uppercase">
                      {p.project}
                    </td>
                    <td className="px-10 py-6 text-center">
                      <span className={`px-3 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-widest border ${
                        p.status === "vence_pronto" 
                          ? "bg-amber-500/10 text-amber-400 border-amber-500/20" 
                          : "bg-emerald-500/10 text-emerald-400 border-emerald-500/20"
                      }`}>
                        {p.status === "vence_pronto" ? "Vence Pronto" : "Vigente"}
                      </span>
                    </td>
                    <td className="px-10 py-6 text-center text-xs font-black text-rose-400">
                      {p.expiresAt}
                    </td>
                    <td className="px-10 py-6 text-right space-x-2">
                      <button 
                        onClick={() => handleDownload(p.name)}
                        className="p-2.5 bg-white/5 border border-white/5 rounded-xl text-white/60 hover:text-white transition-all hover:scale-105 active:scale-95 inline-block"
                      >
                        <Download className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>

      {/* Floating Action control for Drag-and-drop simulated widget */}
      <div 
        onClick={() => setShowUploadModal(true)}
        className="fixed bottom-10 right-10 w-16 h-16 bg-primary rounded-full shadow-[0_4px_32px_rgba(0,82,255,0.4)] hover:shadow-[0_4px_32px_rgba(0,82,255,0.6)] flex items-center justify-center cursor-pointer hover:scale-110 active:scale-95 transition-all group z-40 border border-primary-container"
      >
        <Upload className="w-6 h-6 text-white group-hover:rotate-12 transition-transform" />
        <span className="absolute right-20 bg-slate-900 border border-white/10 text-white font-black text-[9px] px-3 py-1.5 rounded-xl uppercase tracking-widest pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
          Cargar Nuevo Archivo
        </span>
      </div>

      {/* Upload document Modal layout */}
      <AnimatePresence>
        {showUploadModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowUploadModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-xl glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Cargar Archivo</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Arrastre o seleccione el archivo digital para su almacenamiento</p>

              <form onSubmit={handleUploadFile} className="space-y-6">
                
                {/* Drag zone representation */}
                <div className="border-2 border-dashed border-white/10 rounded-3xl p-8 flex flex-col items-center justify-center text-center bg-white/[0.01] hover:bg-white/[0.03] transition-colors cursor-pointer">
                  <Upload className="w-10 h-10 text-primary/60 mb-3" />
                  <p className="text-xs font-black uppercase tracking-widest text-white/50">Arrastra tus archivos aquí</p>
                  <p className="text-[10px] font-bold text-white/20 uppercase tracking-wider mt-1">O haz clic para explorar en el disco local</p>
                </div>

                {/* Name */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 animate-pulse">Nombre De Referencia o Archivo</label>
                  <input 
                    type="text"
                    required
                    value={fileName}
                    onChange={(e) => setFileName(e.target.value)}
                    placeholder="E.g. Plano de Planta Baja"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                {/* Categories & Extension grids */}
                <div className="grid grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Categoría General</label>
                    <select 
                      value={fileCat}
                      onChange={(e) => setFileCat(e.target.value as any)}
                      className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
                    >
                      <option value="planos" className="bg-slate-950 text-white">Planos</option>
                      <option value="contratos" className="bg-slate-950 text-white">Contratos</option>
                      <option value="licencias" className="bg-slate-950 text-white">Licencias</option>
                      <option value="evidencia" className="bg-slate-950 text-white">Evidencia</option>
                    </select>
                  </div>

                  <div className="space-y-2">
                    <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Extensión / Tipo</label>
                    <select 
                      value={fileExtension}
                      onChange={(e) => setFileExtension(e.target.value as any)}
                      className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
                    >
                      <option value="pdf" className="bg-slate-950 text-white">PDF Document</option>
                      <option value="dwg" className="bg-slate-950 text-white">DWG Blueprint</option>
                      <option value="docx" className="bg-slate-950 text-white">Microsoft Word</option>
                      <option value="xlsx" className="bg-slate-950 text-white">Excel Spreadsheet</option>
                      <option value="png" className="bg-slate-950 text-white">PNG Image</option>
                    </select>
                  </div>
                </div>

                {/* Folder target destination */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1">Asignar a Carpeta de Destino</label>
                  <select 
                    value={fileFolder}
                    onChange={(e) => setFileFolder(e.target.value)}
                    className="w-full bg-white/5 border border-white/10 rounded-2xl p-4 text-sm font-bold uppercase text-white focus:outline-none"
                  >
                    {folders.map(f => (
                      <option key={f.id} value={f.id} className="bg-slate-950 text-white">{f.name}</option>
                    ))}
                  </select>
                </div>

                {/* CTA Form Actions */}
                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border-primary border text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                  >
                    Confirmar Guardado
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowUploadModal(false)}
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

      {/* Create project directory new Folder Modal layout */}
      <AnimatePresence>
        {showNewFolderModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-950/85 backdrop-blur-md">
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setShowNewFolderModal(false)}
              className="absolute inset-0 cursor-pointer"
            ></motion.div>

            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="relative w-full max-w-md glass-card rounded-[56px] p-12 border border-white/10 bg-slate-950 shadow-[0_0_120px_rgba(99,102,241,0.25)] text-white"
            >
              <h3 className="text-3xl font-black text-white italic uppercase tracking-tighter mb-2">Nueva Carpeta</h3>
              <p className="text-white/40 font-bold uppercase tracking-widest text-[10px] mb-8">Ingrese el nombre identificador para el nuevo directorio lógico de obra</p>

              <form onSubmit={handleCreateFolder} className="space-y-6">
                
                {/* Name */}
                <div className="space-y-2">
                  <label className="text-[10px] font-black text-white/30 uppercase tracking-widest ml-1 animate-pulse">Nombre de la Carpeta</label>
                  <input 
                    type="text"
                    required
                    value={folderName}
                    onChange={(e) => setFolderName(e.target.value)}
                    placeholder="E.g. Condominio El Prado - Fase Final"
                    className="w-full glass-input rounded-2xl p-4 text-sm font-bold placeholder:text-white/20 uppercase text-white"
                  />
                </div>

                {/* CTA Buttons */}
                <div className="flex gap-4 pt-4">
                  <button 
                    type="submit" 
                    className="flex-grow glass-button-primary bg-primary border border-primary text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-2xl hover:shadow-primary/30 transition-all"
                  >
                    Crear Directorio
                  </button>
                  <button 
                    type="button"
                    onClick={() => setShowNewFolderModal(false)}
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
