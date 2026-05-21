import { LayoutDashboard, Users, Construction, FolderKanban, Banknote, LifeBuoy, LogOut, Plus, Package, Building2, ShoppingBag, Landmark, PiggyBank, Coins, ClipboardList, FolderOpen, Bell } from "lucide-react";
import { motion } from "motion/react";
import { Role, View } from "../App";

interface SidebarProps {
  currentView: View;
  onViewChange: (view: View) => void;
  onLogout: () => void;
  role: Role;
}

export default function Sidebar({ currentView, onViewChange, onLogout, role }: SidebarProps) {
  const allNavItemsArr = [
    { id: "dashboard", label: "Panel Principal", icon: LayoutDashboard, roles: ["admin", "supervisor", "tecnico"] },
    { id: "personnel", label: "Personal", icon: Users, roles: ["admin"] },
    { id: "machinery", label: "Maquinaria", icon: Construction, roles: ["admin", "supervisor"] },
    { id: "tech-machinery", label: "Estado Maquinaria", icon: Construction, roles: ["tecnico"] },
    { id: "projects", label: "Proyectos", icon: FolderKanban, roles: ["admin", "supervisor"] },
    { id: "tech-projects", label: "Mis Proyectos", icon: FolderKanban, roles: ["tecnico"] },
    { id: "inventory", label: "Inventario", icon: Package, roles: ["admin", "supervisor", "tecnico"] },
    { id: "suppliers", label: "Proveedores", icon: Building2, roles: ["admin"] },
    { id: "purchases", label: "Compras", icon: ShoppingBag, roles: ["admin"] },
    { id: "finance", label: "Finanzas", icon: Banknote, roles: ["admin"] },
    { id: "bank-conciliation", label: "Bancos y Conciliación", icon: Landmark, roles: ["admin"] },
    { id: "credits-accounts-payable", label: "Créditos y Cuentas por Pagar", icon: Landmark, roles: ["admin"] },
    { id: "budgets-estimations", label: "Presupuestos y Estimaciones", icon: PiggyBank, roles: ["admin"] },
    { id: "payroll-expenses", label: "Planillas y Gastos", icon: Coins, roles: ["admin"] },
    { id: "bitacora-mantenimiento", label: "Bitácora y Mantenimiento", icon: ClipboardList, roles: ["admin", "supervisor", "tecnico"] },
    { id: "digital-documents", label: "Documentos Digitales", icon: FolderOpen, roles: ["admin", "supervisor", "tecnico"] },
    { id: "notifications-alerts", label: "Notificaciones y Alertas", icon: Bell, roles: ["admin", "supervisor", "tecnico"] },
  ];

  const filteredItems = allNavItemsArr.filter(item => item.roles.includes(role || ""));

  return (
    <aside className="w-[280px] h-screen fixed left-0 top-0 glass-sidebar flex flex-col py-8 z-50 hidden md:flex">
      <div className="px-10 mb-12">
        <h1 className="text-2xl font-bold bg-gradient-to-r from-white to-white/60 bg-clip-text text-transparent tracking-tight">ConstructPro</h1>
        <p className="text-[10px] font-bold text-primary uppercase tracking-[0.2em] mt-1">Suite de Gestión</p>
        
        {role && (
          <div className="mt-6 px-4 py-1.5 bg-primary/10 border border-primary/20 rounded-xl inline-flex items-center gap-2">
            <div className="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shadow-[0_0_8px_#6366f1]"></div>
            <p className="text-[10px] font-black text-primary uppercase tracking-[0.2em] italic">{role}</p>
          </div>
        )}
      </div>

      <nav className="flex-grow px-4 overflow-y-auto custom-scrollbar">
        <ul className="space-y-1.5">
          {filteredItems.map((item) => {
            const Icon = item.icon;
            const isActive = currentView === item.id;
            return (
              <li key={item.id} className="relative px-2">
                <button
                  onClick={() => onViewChange(item.id as View)}
                  className={`w-full flex items-center px-6 py-4 rounded-2xl transition-all duration-300 group ${
                    isActive
                      ? "text-white bg-white/10 shadow-[0_0_20px_rgba(255,255,255,0.05)]"
                      : "text-white/60 hover:text-white hover:bg-white/5"
                  }`}
                >
                  <Icon className={`w-5 h-5 mr-4 transition-transform duration-300 group-hover:scale-110 ${isActive ? "text-primary" : "text-white/40"}`} />
                  <span className="text-xs font-black uppercase tracking-widest italic">{item.label}</span>
                  {isActive && (
                    <motion.div
                      layoutId="active-nav-glow"
                      className="absolute right-6 w-1.5 h-1.5 rounded-full bg-primary shadow-[0_0_10px_#6366f1]"
                    />
                  )}
                </button>
              </li>
            );
          })}
        </ul>
      </nav>

      {role === "admin" && (
        <div className="px-6 mb-8 mt-auto">
          <button className="w-full glass-button-primary text-white py-4 px-6 rounded-2xl text-[10px] font-black uppercase tracking-widest flex items-center justify-center gap-2 shadow-2xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-0.5 active:translate-y-0 transition-all">
            <Plus className="w-4 h-4" />
            Nuevo Proyecto
          </button>
        </div>
      )}

      <div className="pt-4 px-6 space-y-1 border-t border-white/5 mt-auto">
        <button className="w-full flex items-center px-6 py-3 rounded-xl text-white/60 hover:text-white hover:bg-white/5 transition-all text-sm font-medium">
          <LifeBuoy className="w-5 h-5 mr-4" />
          Soporte
        </button>
        <button 
          onClick={onLogout}
          className="w-full flex items-center px-6 py-3 rounded-xl text-white/60 hover:text-tertiary transition-all text-sm font-medium"
        >
          <LogOut className="w-5 h-5 mr-4" />
          Cerrar Sesión
        </button>
      </div>
    </aside>
  );
}
