import React, { useState, useEffect } from "react";
import { 
  Users, 
  Shield, 
  Wrench, 
  TrendingUp, 
  Search, 
  Plus, 
  Filter, 
  Download, 
  ChevronLeft, 
  ChevronRight, 
  Lock, 
  History, 
  Edit2, 
  Trash2, 
  UserX,
  X,
  Check,
  ShieldCheck,
  Info
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

interface UserConfig {
  id: string;
  fullName: string;
  username: string;
  email: string;
  role: "admin" | "tecnico" | "tecnico_dashboard" | "tecnico_piloto";
  site: string;
  status: "Active" | "Inactive";
  lastAccess: string;
}

interface UsuariosModuleProps {
  onUsersListChange?: (count: number) => void;
}

export default function UsuariosModule({ onUsersListChange }: UsuariosModuleProps) {
  const [usersList, setUsersList] = useState<UserConfig[]>([]);
  const [searchQuery, setSearchQuery] = useState("");
  const [roleFilter, setRoleFilter] = useState<string>("all");
  const [statusFilter, setStatusFilter] = useState<string>("all");
  
  // Pagination
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 4;

  // Modal / Form state for Creating and Editing users
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editUserId, setEditUserId] = useState<string | null>(null);

  // Form Field States
  const [fullName, setFullName] = useState("");
  const [username, setUsername] = useState("");
  const [email, setEmail] = useState("");
  const [role, setRole] = useState<"admin" | "tecnico" | "tecnico_dashboard" | "tecnico_piloto">("tecnico");
  const [site, setSite] = useState("");
  const [status, setStatus] = useState<"Active" | "Inactive">("Active");

  // Notifications or toast messages
  const [toastMessage, setToastMessage] = useState("");

  useEffect(() => {
    const saved = localStorage.getItem("cooitza_usuarios");
    if (saved) {
      try {
        const data = JSON.parse(saved);
        setUsersList(data);
        triggerSync(data);
      } catch (e) {
        initializeDefaults();
      }
    } else {
      initializeDefaults();
    }
  }, []);

  const initializeDefaults = () => {
    const defaultUsers: UserConfig[] = [
      { 
        id: "u1", 
        fullName: "Alejandro Rivera", 
        username: "admin", 
        email: "a.rivera@machinerylink.com", 
        role: "admin", 
        site: "Sede Central Chimaltenango", 
        status: "Active", 
        lastAccess: "2026-05-30 08:42" 
      },
      { 
        id: "u2", 
        fullName: "Beatriz Mendoza", 
        username: "tecnico_dashboard", 
        email: "b.mendoza@machinerylink.com", 
        role: "tecnico_dashboard", 
        site: "Planta El Rodeo", 
        status: "Active", 
        lastAccess: "2026-05-29 14:15" 
      },
      { 
        id: "u3", 
        fullName: "Carlos Salazar", 
        username: "tecnico_piloto_carlos", 
        email: "c.salazar@machinerylink.com", 
        role: "tecnico_piloto", 
        site: "Cantera Cooitzá", 
        status: "Inactive", 
        lastAccess: "2026-05-25 09:00" 
      },
      { 
        id: "u4", 
        fullName: "Diana López", 
        username: "dlopez_cooitza", 
        email: "d.lopez@machinerylink.com", 
        role: "tecnico", 
        site: "Terminal Sur Obra", 
        status: "Active", 
        lastAccess: "2026-05-30 07:30" 
      },
    ];
    setUsersList(defaultUsers);
    localStorage.setItem("cooitza_usuarios", JSON.stringify(defaultUsers));
    triggerSync(defaultUsers);
  };

  const triggerSync = (updated: UserConfig[]) => {
    if (onUsersListChange) {
      onUsersListChange(updated.length);
    }
  };

  const persistUsers = (updated: UserConfig[]) => {
    setUsersList(updated);
    localStorage.setItem("cooitza_usuarios", JSON.stringify(updated));
    triggerSync(updated);
  };

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => setToastMessage(""), 3000);
  };

  const handleOpenCreateForm = () => {
    setEditUserId(null);
    setFullName("");
    setUsername("");
    setEmail("");
    setRole("tecnico");
    setSite("");
    setStatus("Active");
    setIsFormOpen(true);
  };

  const handleOpenEditForm = (u: UserConfig) => {
    setEditUserId(u.id);
    setFullName(u.fullName);
    setUsername(u.username);
    setEmail(u.email);
    setRole(u.role);
    setSite(u.site);
    setStatus(u.status);
    setIsFormOpen(true);
  };

  const handleSaveUserSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!fullName.trim() || !username.trim() || !email.trim()) return;

    if (editUserId) {
      const updated = usersList.map(u => u.id === editUserId ? {
        ...u,
        fullName: fullName.trim(),
        username: username.toLowerCase().trim(),
        email: email.trim(),
        role,
        site: site.trim() || "Sede General",
        status
      } : u);
      persistUsers(updated);
      showToast(`Usuario "${fullName}" actualizado.`);
    } else {
      const newUser: UserConfig = {
        id: "u_" + Date.now(),
        fullName: fullName.trim(),
        username: username.toLowerCase().trim(),
        email: email.trim(),
        role,
        site: site.trim() || "Sede General",
        status,
        lastAccess: new Date().toISOString().replace('T', ' ').substring(0, 16)
      };
      persistUsers([newUser, ...usersList]);
      showToast(`Usuario "${fullName}" registrado.`);
    }

    setIsFormOpen(false);
  };

  const handleDeleteUser = (id: string, name: string) => {
    if (window.confirm(`¿Está seguro de revocar permanentemente la autorización de seguridad para "${name}"?`)) {
      const updated = usersList.filter(u => u.id !== id);
      persistUsers(updated);
      showToast(`Autorización de "${name}" revocada.`);
    }
  };

  const handleToggleStatus = (id: string, currentStatus: "Active" | "Inactive", name: string) => {
    const nextStatus = currentStatus === "Active" ? "Inactive" : "Active";
    const updated = usersList.map(u => u.id === id ? { ...u, status: nextStatus } : u);
    persistUsers(updated);
    showToast(`Estado de "${name}" cambiado a ${nextStatus === 'Active' ? 'Activo' : 'Inactivo'}.`);
  };

  const handleExportData = () => {
    showToast("Exportando informe de personal de Cooitzá a CSV...");
    const headers = "ID,Nombre,Email,Rol,Sede,Estado,UltimoAcceso\n";
    const rows = usersList.map(u => `${u.id},${u.fullName},${u.email},${u.role},${u.site},${u.status},${u.lastAccess}`).join("\n");
    const blob = new Blob([headers + rows], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', `Cooitza_Usuarios_Report.csv`);
    a.click();
  };

  // Filter Logic
  const filteredUsers = usersList.filter(u => {
    const query = searchQuery.toLowerCase();
    const matchesKeyword = 
      u.fullName.toLowerCase().includes(query) || 
      u.username.toLowerCase().includes(query) || 
      u.email.toLowerCase().includes(query) || 
      u.site.toLowerCase().includes(query);

    const matchesRole = roleFilter === "all" || u.role === roleFilter;
    const matchesStatus = statusFilter === "all" || u.status === statusFilter;

    return matchesKeyword && matchesRole && matchesStatus;
  });

  // Pagination calculations
  const totalItems = filteredUsers.length;
  const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;
  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentItems = filteredUsers.slice(indexOfFirstItem, indexOfLastItem);

  // Stats Counters
  const totalGlobalDatabaseCount = 1284 + (usersList.length - 4); // Bento grid scale metrics
  const adminsCount = usersList.filter(u => u.role === "admin").length;
  const totalGlobalAdminsCount = 39 + adminsCount;
  const totalFieldOperatorsCount = totalGlobalDatabaseCount - totalGlobalAdminsCount;

  // Helper for Role badges
  const renderRoleBadge = (roleStr: string) => {
    switch (roleStr) {
      case "admin":
        return <span className="inline-block px-2 py-0.5 bg-red-100 text-red-800 text-[10px] font-bold uppercase rounded-sm border border-red-200">Admin</span>;
      case "tecnico_dashboard":
        return <span className="inline-block px-2 py-0.5 bg-indigo-100 text-[#0054A3] text-[10px] font-bold uppercase rounded-sm border border-indigo-200">Panel Técnico</span>;
      case "tecnico_piloto":
        return <span className="inline-block px-2 py-0.5 bg-amber-100 text-amber-800 text-[10px] font-bold uppercase rounded-sm border border-amber-200">Técnico Piloto</span>;
      default:
        return <span className="inline-block px-2 py-0.5 bg-slate-100 text-slate-700 text-[10px] font-bold uppercase rounded-sm border border-slate-200">Técnico</span>;
    }
  };

  const getInitials = (nameStr: string) => {
    return nameStr.split(" ").map(n => n[0]).join("").substring(0, 2).toUpperCase();
  };

  return (
    <motion.div 
      initial={{ opacity: 0, y: 10 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0 }}
      className="flex flex-col gap-6 w-full font-sans pb-12"
    >
      
      {/* Title block with Cooitzá theme */}
      <div className="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-[#cbd5e1] gap-4">
        <div className="border-l-4 border-[#0054A3] pl-3">
          <span className="font-display text-[10px] font-black text-[#0054A3] tracking-widest uppercase">
            Protocolo de Eficiencia Q4
          </span>
          <h2 className="font-display text-3xl font-black text-[#191c1d] uppercase mt-0.5">
            Gestión de Usuarios
          </h2>
          <p className="text-xs text-on-surface-variant font-medium mt-1">
            Gestión y concesión de credenciales para el personal técnico e industrial de la estación central.
          </p>
        </div>

        <button 
          onClick={handleOpenCreateForm}
          className="bg-[#0054A3] hover:bg-[#004586] text-white font-display text-xs font-black tracking-wider px-5 py-3 rounded-none uppercase flex items-center justify-center gap-2 self-start md:self-center cursor-pointer transition-all active:scale-[0.98] shadow-sm select-none"
        >
          <Plus size={16} className="text-[#FFD200]" />
          <span>Nuevo Usuario</span>
        </button>
      </div>

      {/* Bento Grid layout stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 select-none">
        
        {/* Dynamic Card 1 */}
        <div className="bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between relative overflow-hidden group shadow-xs">
          <div className="absolute top-0 left-0 w-1.5 h-full bg-[#0054A3]" />
          <div>
            <span className="font-display text-[10px] font-black text-on-surface-variant uppercase tracking-wider">
              Total base de datos
            </span>
            <div className="font-display text-4xl font-black mt-2 text-[#191c1d]">
              {totalGlobalDatabaseCount.toLocaleString()}
            </div>
          </div>
          <div className="mt-4 flex items-center gap-1.5 text-xs font-bold text-slate-500">
            <TrendingUp className="text-[#0054A3]" size={16} />
            <span>+12% este mes vs histórico</span>
          </div>
        </div>

        {/* Dynamic Card 2 */}
        <div className="bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between relative overflow-hidden group shadow-xs">
          <div className="absolute top-0 left-0 w-1.5 h-full bg-[#FFD200]" />
          <div>
            <span className="font-display text-[10px] font-black text-on-surface-variant uppercase tracking-wider">
              Administradores Generales
            </span>
            <div className="font-display text-4xl font-black mt-2 text-[#0054A3]">
              {totalGlobalAdminsCount}
            </div>
          </div>
          <p className="mt-4 text-xs font-mono font-bold text-neutral-400">
            System Core Team & Log Audit
          </p>
        </div>

        {/* Dynamic Card 3 */}
        <div className="bg-white border border-[#cbd5e1] p-6 flex flex-col justify-between relative overflow-hidden group shadow-xs">
          <div className="absolute top-0 left-0 w-1.5 h-full bg-[#10b981]" />
          <div>
            <span className="font-display text-[10px] font-black text-on-surface-variant uppercase tracking-wider">
              Técnicos Autorizados
            </span>
            <div className="font-display text-4xl font-black mt-2 text-emerald-600">
              {totalFieldOperatorsCount.toLocaleString()}
            </div>
          </div>
          <p className="mt-4 text-xs font-mono font-bold text-neutral-400">
            Operaciones Activas en Cantera
          </p>
        </div>

      </div>

      {/* Main Directory Table section */}
      <div className="bg-white border border-[#cbd5e1] overflow-hidden shadow-sm flex flex-col">
        
        {/* Table Filter Topbar Controls */}
        <div className="px-6 py-4 bg-slate-50 border-b border-[#cbd5e1] flex flex-col lg:flex-row lg:items-center justify-between gap-4 select-none">
          <span className="font-display text-xs font-black uppercase tracking-widest text-[#0054A3]">
            Directorio del Personal Técnico
          </span>
          
          <div className="flex flex-wrap items-center gap-3">
            {/* Search option */}
            <div className="relative min-w-[240px] flex-1 lg:flex-none">
              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant/60">
                <Search size={15} />
              </span>
              <input 
                type="text"
                placeholder="Buscar por nombre, ID o email..."
                value={searchQuery}
                onChange={(e) => {
                  setSearchQuery(e.target.value);
                  setCurrentPage(1);
                }}
                className="bg-white border border-[#cbd5e1] pl-9 pr-4 py-2 text-xs outline-none focus:border-[#0054A3] transition-all w-full placeholder:text-slate-400"
              />
            </div>

            {/* Role filter */}
            <select
              value={roleFilter}
              onChange={(e) => {
                setRoleFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="bg-white border border-[#cbd5e1] px-3 py-2 text-xs outline-none focus:border-[#0054A3] cursor-pointer"
            >
              <option value="all">Todos los Roles</option>
              <option value="admin">Administrador</option>
              <option value="tecnico_dashboard">Panel Técnico</option>
              <option value="tecnico_piloto">Técnico Piloto</option>
              <option value="tecnico">Técnico Común</option>
            </select>

            {/* Status filter */}
            <select
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
                setCurrentPage(1);
              }}
              className="bg-white border border-[#cbd5e1] px-3 py-2 text-xs outline-none focus:border-[#0054A3] cursor-pointer"
            >
              <option value="all">Cualquier Estado</option>
              <option value="Active">Activo</option>
              <option value="Inactive">Inactivo</option>
            </select>

            <button 
              onClick={handleExportData}
              className="bg-white border border-[#cbd5e1] hover:bg-slate-50 text-slate-700 font-display text-[10px] font-bold uppercase tracking-wider px-4 py-2.5 flex items-center gap-1.5 cursor-pointer select-none"
              title="Exportar base de datos a CSV"
            >
              <Download size={14} className="text-[#0054A3]" />
              <span>Exportar</span>
            </button>
          </div>
        </div>

        {/* Database table view */}
        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead>
              <tr className="text-left border-b border-[#cbd5e1] bg-slate-50 select-none">
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Nombre de Operador</th>
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Email de Enlace</th>
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Rol Asignado</th>
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Sede Operación</th>
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Último Acceso</th>
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Estado</th>
                <th className="p-4 font-display text-[10px] font-bold uppercase tracking-wider text-on-surface-variant text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[#cbd5e1]">
              {currentItems.length === 0 ? (
                <tr>
                  <td colSpan={7} className="p-12 text-center text-slate-400 font-medium italic">
                    Sin resultados coincidentes con los filtros seleccionados.
                  </td>
                </tr>
              ) : (
                currentItems.map((u) => (
                  <tr key={u.id} className="hover:bg-slate-50/50 transition-all group">
                    <td className="p-4 flex items-center gap-3">
                      <div className="w-9 h-9 rounded-full bg-[#0054A3]/10 text-[#0054A3] flex items-center justify-center font-display text-xs font-black">
                        {getInitials(u.fullName)}
                      </div>
                      <div>
                        <span className="font-sans text-[#191c1d] font-bold hover:text-[#0054A3] transition-colors">{u.fullName}</span>
                        <span className="block text-[10px] font-mono text-[#004586] tracking-tight">{`@${u.username}`}</span>
                      </div>
                    </td>
                    <td className="p-4 text-xs font-medium text-slate-600">{u.email}</td>
                    <td className="p-4 text-xs font-medium">{renderRoleBadge(u.role)}</td>
                    <td className="p-4 text-xs font-medium text-slate-500">{u.site}</td>
                    <td className="p-4 text-xs font-mono font-medium text-[#004586]">{u.lastAccess || "No disponible"}</td>
                    <td className="p-4">
                      <button
                        onClick={() => handleToggleStatus(u.id, u.status, u.fullName)}
                        className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase border cursor-pointer select-none ${
                          u.status === "Active" 
                            ? "bg-emerald-50 text-emerald-700 border-emerald-200" 
                            : "bg-red-50 text-red-700 border-red-200"
                        }`}
                      >
                        <span className={`w-1.5 h-1.5 rounded-full ${u.status === "Active" ? "bg-emerald-500 animate-pulse" : "bg-red-500"}`} />
                        <span>{u.status === "Active" ? "Activo" : "Inactivo"}</span>
                      </button>
                    </td>
                    <td className="p-4 text-right">
                      <div className="flex justify-end gap-1.5 opacity-60 group-hover:opacity-100 transition-opacity">
                        <button 
                          onClick={() => handleOpenEditForm(u)}
                          className="p-1.5 text-[#0054A3] hover:bg-[#0054A3]/10 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                          title="Editar Autorización"
                        >
                          <Edit2 size={13} />
                        </button>
                        <button 
                          onClick={() => handleToggleStatus(u.id, u.status, u.fullName)}
                          className={`p-1.5 border border-[#cbd5e1] bg-white transition-all cursor-pointer ${
                            u.status === "Active" ? "text-amber-600 hover:bg-amber-50" : "text-emerald-600 hover:bg-emerald-50"
                          }`}
                          title={u.status === "Active" ? "Suspender acceso" : "Re-habilitar acceso"}
                        >
                          <UserX size={13} />
                        </button>
                        <button 
                          onClick={() => handleDeleteUser(u.id, u.fullName)}
                          className="p-1.5 text-red-650 hover:bg-red-50 border border-[#cbd5e1] bg-white transition-all cursor-pointer"
                          title="Eliminar permanentemente"
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

        {/* Pagination bar matches Cooitzá style */}
        <div className="px-6 py-4 bg-slate-50 border-t border-[#cbd5e1] flex flex-col sm:flex-row justify-between items-center gap-3 select-none">
          <span className="font-display text-[10px] font-bold uppercase tracking-wider text-slate-500">
            Mostrando {indexOfFirstItem + 1}-{Math.min(indexOfLastItem, totalItems)} de {totalItems} usuarios ({totalGlobalDatabaseCount} registros totales en red)
          </span>

          <div className="flex gap-1.5">
            <button 
              disabled={currentPage === 1}
              onClick={() => setCurrentPage(prev => Math.max(prev - 1, 1))}
              className="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors"
            >
              <ChevronLeft size={16} />
            </button>
            
            {Array.from({ length: totalPages }, (_, i) => i + 1).map((pageNum) => (
              <button
                key={pageNum}
                onClick={() => setCurrentPage(pageNum)}
                className={`w-8 h-8 flex items-center justify-center border font-display text-xs font-black transition-colors ${
                  currentPage === pageNum 
                    ? "bg-[#0054A3] text-white border-[#0054A3]" 
                    : "border-[#cbd5e1] bg-white hover:bg-slate-50 cursor-pointer"
                }`}
              >
                {pageNum}
              </button>
            ))}

            <button 
              disabled={currentPage === totalPages}
              onClick={() => setCurrentPage(prev => Math.min(prev + 1, totalPages))}
              className="w-8 h-8 flex items-center justify-center border border-[#cbd5e1] bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer transition-colors"
            >
              <ChevronRight size={16} />
            </button>
          </div>
        </div>

      </div>

      {/* Additional Asymmetric Info Grid cards matching HTML */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6 select-none">
        
        {/* Compliance Card */}
        <div className="p-6 bg-white border border-[#cbd5e1] flex gap-4 items-start shadow-xs">
          <div className="p-3 bg-red-50 text-red-700 border border-red-150">
            <Lock size={26} />
          </div>
          <div>
            <h3 className="font-display text-sm font-black text-[#191c1d] uppercase tracking-wide">
              Protocolo de Seguridad Corporativa
            </h3>
            <p className="text-xs text-slate-500 leading-relaxed mt-2">
              Todos los técnicos con acceso a la telemetría deben completar el ciclo anual de certificación Q4. Renovación de firma requerida para 12 usuarios este mes.
            </p>
            <button className="mt-4 font-display text-[10px] font-black text-[#0054A3] uppercase hover:underline flex items-center gap-1 cursor-pointer">
              <span>Auditar reporte de cumplimiento</span>
              <Info size={12} />
            </button>
          </div>
        </div>

        {/* Logs Audit Card */}
        <div className="p-6 bg-white border border-[#cbd5e1] flex gap-4 items-start shadow-xs">
          <div className="p-3 bg-indigo-50 text-[#0054A3] border border-indigo-150">
            <History size={26} />
          </div>
          <div>
            <h3 className="font-display text-sm font-black text-[#191c1d] uppercase tracking-wide">
              Auditoría de Acceso TLS
            </h3>
            <p className="text-xs text-slate-500 leading-relaxed mt-2">
              Sede central Guatemala auditó los logs hace 4 horas automáticamente. No se detectaron anomalías ni re-intentos fallidos en la estación satelital 04-B.
            </p>
            <button className="mt-4 font-display text-[10px] font-black text-[#0054A3] uppercase hover:underline flex items-center gap-1 cursor-pointer">
              <span>Descargar logs criptográficos (.csv)</span>
              <Download size={12} />
            </button>
          </div>
        </div>

      </div>

      {/* Slide-over Overlay Dialog for Adding/Editing credentials */}
      <AnimatePresence>
        {isFormOpen && (
          <div className="fixed inset-0 bg-slate-900/40 z-50 flex items-center justify-center p-4">
            
            <motion.div 
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="bg-white border-2 border-[#cbd5e1] w-full max-w-lg shadow-2xl relative flex flex-col overflow-hidden"
            >
              
              {/* Gold Indicator Head accent */}
              <div className="w-full h-1 bg-[#FFD200]" />

              {/* Header Box */}
              <div className="flex justify-between items-center bg-slate-50 px-6 py-4 border-b border-[#cbd5e1]">
                <div className="flex items-center gap-2">
                  <ShieldCheck className="text-[#0054A3]" size={18} />
                  <span className="font-display text-xs font-black text-[#0054A3] uppercase tracking-wider">
                    {editUserId ? "Modificar Autorización Cooitzá" : "Conceder Nueva Autorización"}
                  </span>
                </div>
                <button 
                  onClick={() => setIsFormOpen(false)}
                  className="p-1 hover:bg-slate-200 text-slate-500 transition-colors cursor-pointer"
                >
                  <X size={18} />
                </button>
              </div>

              {/* Form container */}
              <form onSubmit={handleSaveUserSubmit} className="p-6 space-y-4">
                
                {/* Full name description field */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                    Nombre Completo del Operador
                  </label>
                  <input 
                    type="text"
                    required
                    placeholder="Ej: Alejandro Rivera de León"
                    value={fullName}
                    onChange={(e) => setFullName(e.target.value)}
                    className="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none active:bg-white"
                  />
                </div>

                {/* Grid wrap for dynamic elements */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  {/* Username */}
                  <div className="flex flex-col gap-1.5">
                    <label className="text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                      Usuario (ID Log-in)
                    </label>
                    <input 
                      type="text"
                      required
                      placeholder="Ej: ariveral"
                      value={username}
                      onChange={(e) => setUsername(e.target.value)}
                      className="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none"
                    />
                  </div>

                  {/* Email */}
                  <div className="flex flex-col gap-1.5">
                    <label className="text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                      Correo Electrónico
                    </label>
                    <input 
                      type="email"
                      required
                      placeholder="Ej: a.rivera@cooitza.com"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      className="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none"
                    />
                  </div>
                </div>

                {/* Role select matching the system's exact internal logins */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                    Rol Operativo & de Control
                  </label>
                  <select
                    value={role}
                    onChange={(e) => setRole(e.target.value as any)}
                    className="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none cursor-pointer"
                  >
                    <option value="tecnico_dashboard">Técnico Horómetros (Válido para enviar lecturas)</option>
                    <option value="tecnico_piloto">Técnico Piloto (Formulario de Piloto)</option>
                    <option value="tecnico">Técnico Estándar</option>
                    <option value="admin">Administrador General de Sistemas Cooitzá</option>
                  </select>
                </div>

                {/* Site location */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                    Sede o Cantera Asignada
                  </label>
                  <input 
                    type="text"
                    placeholder="Ej: Sede Central / Cantera Chimaltenango"
                    value={site}
                    onChange={(e) => setSite(e.target.value)}
                    className="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none"
                  />
                </div>

                {/* Access validation status */}
                <div className="flex flex-col gap-1.5">
                  <label className="text-on-surface-variant text-[10px] font-bold uppercase tracking-wider">
                    Estado de Permiso Inicial
                  </label>
                  <select
                    value={status}
                    onChange={(e) => setStatus(e.target.value as any)}
                    className="w-full bg-slate-50 border border-[#cbd5e1] p-2.5 font-sans text-xs focus:ring-1 focus:ring-[#FFD200] focus:border-[#0054A3] focus:bg-white outline-none cursor-pointer"
                  >
                    <option value="Active">Permitido (Autorización Concedida)</option>
                    <option value="Inactive">Suspendido (Remover Permisos de Acceso)</option>
                  </select>
                </div>

                {/* Modal footer CTA */}
                <div className="pt-4 flex items-center justify-end gap-3 border-t border-[#cbd5e1]/60 mt-6 font-display">
                  <button 
                    type="button"
                    onClick={() => setIsFormOpen(false)}
                    className="px-4 py-2 text-xs font-black uppercase text-slate-500 hover:bg-slate-100 transition-colors cursor-pointer"
                  >
                    Cancelar
                  </button>

                  <button 
                    type="submit"
                    className="bg-[#0054A3] hover:bg-[#004586] text-white px-5 py-2.5 text-xs font-black uppercase tracking-wider flex items-center gap-1.5 cursor-pointer shadow-xs active:scale-[0.98]"
                  >
                    <Check size={14} className="text-[#FFD200]" />
                    <span>{editUserId ? "Actualizar Permisos" : "Conceder Autorización"}</span>
                  </button>
                </div>

              </form>

            </motion.div>

          </div>
        )}
      </AnimatePresence>

      {/* Dynamic Action Notification Toast alert */}
      <AnimatePresence>
        {toastMessage && (
          <motion.div 
            initial={{ opacity: 0, y: 30, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 30, scale: 0.95 }}
            className="fixed bottom-6 left-6 bg-[#191c1d] text-white border-l-4 border-[#FFD200] p-4 shadow-xl z-50 flex items-center gap-3 font-sans text-xs max-w-sm rounded-none"
          >
            <div className="w-5 h-5 bg-[#0054A3]/30 rounded-full flex items-center justify-center text-[#FFD200] shrink-0 font-display">i</div>
            <span className="font-bold">{toastMessage}</span>
          </motion.div>
        )}
      </AnimatePresence>

    </motion.div>
  );
}
