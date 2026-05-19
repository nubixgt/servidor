import { useState } from "react";
import { AnimatePresence, motion } from "motion/react";
import Sidebar from "./components/Sidebar";
import TopBar from "./components/TopBar";
import Login from "./views/Login";
import Dashboard from "./views/admin/Dashboard";
import Personnel from "./views/admin/Personnel";
import Machinery from "./views/admin/Machinery";
import Projects from "./views/admin/Projects";
import Finance from "./views/admin/Finance";
import Inventory from "./views/admin/Inventory";
import Suppliers from "./views/admin/Suppliers";
import Purchases from "./views/admin/Purchases";
import MachineryStatus from "./views/tecnico/MachineryStatus";
import TechProjects from "./views/tecnico/TechProjects";

export type Role = "admin" | "supervisor" | "tecnico" | null;
export type View = "login" | "dashboard" | "personnel" | "machinery" | "projects" | "finance" | "inventory" | "suppliers" | "purchases" | "tech-machinery" | "tech-projects";

export default function App() {
  const [currentView, setCurrentView] = useState<View>("login");
  const [userRole, setUserRole] = useState<Role>(null);

  const getPageTitle = (view: View) => {
    switch (view) {
      case "dashboard": return "Vista General del Panel";
      case "personnel": return "Gestión de Personal";
      case "machinery": return "Gestión de Maquinaria";
      case "projects": return "Portafolio de Proyectos";
      case "finance": return "Vista General de Finanzas";
      case "inventory": return "Gestión de Inventario";
      case "suppliers": return "Directorio de Proveedores";
      case "purchases": return "Módulo de Compras";
      case "tech-machinery": return "Estatus de Maquinaria";
      case "tech-projects": return "Mis Asignaciones";
      default: return "ConstructPro";
    }
  };

  const handleLogin = (role: Role) => {
    setUserRole(role);
    if (role === "tecnico") {
      setCurrentView("tech-machinery");
    } else {
      setCurrentView("dashboard");
    }
  };

  const handleLogout = () => {
    setUserRole(null);
    setCurrentView("login");
  };

  if (currentView === "login") {
    return (
      <AnimatePresence mode="wait">
        <motion.div
          key="login-view"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="w-full h-full"
        >
          <Login onLogin={handleLogin} />
        </motion.div>
      </AnimatePresence>
    );
  }

  const renderView = () => {
    switch (currentView) {
      case "dashboard": return <Dashboard />;
      case "personnel": return <Personnel />;
      case "machinery": return <Machinery />;
      case "projects": return <Projects />;
      case "finance": return <Finance />;
      case "inventory": return <Inventory />;
      case "suppliers": return <Suppliers />;
      case "purchases": return <Purchases />;
      case "tech-machinery": return <MachineryStatus />;
      case "tech-projects": return <TechProjects />;
      default: return <Dashboard />;
    }
  };

  return (
    <div className="min-h-screen bg-app-scenic relative selection:bg-primary/30 selection:text-white">
      <Sidebar 
        currentView={currentView} 
        onViewChange={(view) => setCurrentView(view as View)} 
        onLogout={handleLogout}
        role={userRole}
      />
      
      <div className="flex flex-col min-h-screen relative z-10">
        <TopBar title={getPageTitle(currentView)} />
        
        <main className="flex-grow md:ml-[280px]">
          <AnimatePresence mode="wait">
            <motion.div
              key={currentView}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -10 }}
              transition={{ duration: 0.4, ease: "easeOut" }}
            >
              {renderView()}
            </motion.div>
          </AnimatePresence>
        </main>
        
        <footer className="md:ml-[280px] p-10 text-center text-xs font-bold text-white/40 uppercase tracking-[0.2em]">
          ConstructPro © 2024 Suite de Gestión • Versión Premium 4.2.0
        </footer>
      </div>

      <div className="fixed inset-0 pointer-events-none z-0 opacity-40">
        <div className="absolute top-[10%] left-[5%] w-[500px] h-[500px] bg-primary/10 blur-[150px] rounded-full"></div>
        <div className="absolute bottom-[10%] right-[5%] w-[600px] h-[600px] bg-tertiary/5 blur-[180px] rounded-full"></div>
      </div>
    </div>
  );
}
