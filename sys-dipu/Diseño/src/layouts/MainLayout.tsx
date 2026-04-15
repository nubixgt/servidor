import React from 'react';
import { Role, Category } from '../types';

const SidebarItem = ({ icon, label, active = false, onClick }: { icon: string, label: string, active?: boolean, onClick: () => void }) => (
  <button
    onClick={onClick}
    className={`w-full flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-300 group ${
      active 
        ? 'bg-primary-container text-on-primary-container shadow-sm' 
        : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'
    }`}
  >
    <span className={`material-symbols-outlined text-xl transition-transform duration-300 ${active ? 'scale-110' : 'group-hover:scale-110'}`}>
      {icon}
    </span>
    <span className={`text-sm font-bold tracking-wide ${active ? '' : 'opacity-80 group-hover:opacity-100'}`}>
      {label}
    </span>
  </button>
);

interface MainLayoutProps {
  children: React.ReactNode;
  role: Role;
  assignedCategory: Category | null;
  currentView: string;
  setCurrentView: (view: string) => void;
  onLogout: () => void;
}

const CATEGORIES_DATA: { id: Category, icon: string, label: string }[] = [
  { id: 'iniciativas', icon: 'gavel', label: 'Iniciativas de Ley' },
  { id: 'citaciones', icon: 'record_voice_over', label: 'Citaciones' },
  { id: 'comisiones', icon: 'groups', label: 'Comisiones' },
  { id: 'fiscalizacion', icon: 'policy', label: 'Fiscalización' },
  { id: 'compromisos', icon: 'handshake', label: 'Compromisos Distritales' },
  { id: 'actividades', icon: 'event_available', label: 'Actividades' },
  { id: 'redes', icon: 'public', label: 'Redes Sociales' },
  { id: 'afiliaciones', icon: 'how_to_reg', label: 'Afiliaciones Políticas' },
];

export default function MainLayout({ children, role, assignedCategory, currentView, setCurrentView, onLogout }: MainLayoutProps) {
  const visibleCategories = role === 'administrador' 
    ? CATEGORIES_DATA 
    : CATEGORIES_DATA.filter(c => c.id === assignedCategory);

  // Determine the theme class based on the current view
  const themeClass = CATEGORIES_DATA.some(c => c.id === currentView) 
    ? `theme-${currentView}` 
    : '';

  return (
    <div className={`bg-surface text-on-surface min-h-screen font-body flex transition-colors duration-500 ${themeClass}`}>
      {/* Sidebar */}
      <aside className="w-72 fixed left-0 top-0 h-full bg-surface-container-lowest flex flex-col p-6 z-40 border-r border-surface-container-low">
        <div className="mb-8 px-4">
          <div className="flex items-center gap-3 mb-6">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-primary-dim flex items-center justify-center text-on-primary shadow-sm">
              <span className="material-symbols-outlined">account_balance</span>
            </div>
            <div>
              <h2 className="font-bold text-on-surface leading-tight font-headline">Ethereal Bureau</h2>
              <p className="text-[10px] uppercase tracking-widest text-outline">Gestión Operativa</p>
            </div>
          </div>
        </div>
        
        <nav className="flex-1 space-y-1 overflow-y-auto pr-2 custom-scrollbar">
          <SidebarItem icon="dashboard" label="Dashboard" active={currentView === 'dashboard'} onClick={() => setCurrentView('dashboard')} />
          
          <div className="pt-4 pb-2">
            <p className="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">Categorías</p>
            {visibleCategories.map(cat => (
              <SidebarItem 
                key={cat.id} 
                icon={cat.icon} 
                label={cat.label} 
                active={currentView === cat.id} 
                onClick={() => setCurrentView(cat.id)} 
              />
            ))}
          </div>

          <div className="pt-4 pb-2">
            <p className="text-[10px] font-bold uppercase tracking-widest text-outline-variant px-4 mb-2">Herramientas</p>
            <SidebarItem icon="event" label="Calendario Global" active={currentView === 'calendario'} onClick={() => setCurrentView('calendario')} />
            <SidebarItem icon="view_kanban" label="Gestor de Tareas" active={currentView === 'kanban'} onClick={() => setCurrentView('kanban')} />
            <SidebarItem icon="inventory_2" label="Archivo Central" active={currentView === 'archivo'} onClick={() => setCurrentView('archivo')} />
            {role === 'administrador' && (
              <SidebarItem icon="manage_accounts" label="Gestión de Usuarios" active={currentView === 'usuarios'} onClick={() => setCurrentView('usuarios')} />
            )}
            {role === 'administrador' && (
              <SidebarItem icon="tune" label="Configuración" active={currentView === 'configuracion'} onClick={() => setCurrentView('configuracion')} />
            )}
          </div>
        </nav>

        <div className="mt-auto pt-6 border-t border-surface-container-low">
          <button className="w-full py-3 px-4 bg-gradient-to-br from-primary to-primary-dim text-on-primary rounded-xl font-bold flex items-center justify-center gap-2 mb-4 shadow-ambient hover:scale-[1.02] transition-transform">
            <span className="material-symbols-outlined">add</span> Nuevo Registro
          </button>
          <div className="space-y-1">
            <button onClick={onLogout} className="w-full flex items-center gap-3 px-4 py-2 text-on-surface-variant hover:bg-surface-container-low rounded-lg transition-all text-xs font-medium">
              <span className="material-symbols-outlined text-lg">logout</span> Cerrar Sesión
            </button>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <div className="ml-72 flex-1 flex flex-col min-h-screen">
        {/* Top Navbar */}
        <header className="sticky top-0 z-30 glass-header px-10 py-4 flex justify-between items-center border-b border-surface-container-low/50">
          <div className="flex items-center gap-8">
             <span className="text-xl font-bold text-on-surface uppercase tracking-widest font-headline opacity-0">Ethereal Bureau</span> {/* Spacer */}
          </div>
          <div className="flex items-center gap-4">
            <div className="relative group hidden md:block">
              <span className="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline scale-90">search</span>
              <input type="text" placeholder="Buscar..." className="bg-surface-container-low border-none rounded-full pl-10 pr-4 py-2 text-sm w-64 focus:ring-2 focus:ring-primary/20 transition-all outline-none" />
            </div>
            <button className="w-10 h-10 flex items-center justify-center rounded-full hover:bg-surface-container-low transition-all duration-300 text-on-surface-variant">
              <span className="material-symbols-outlined">notifications</span>
            </button>
            <div className="h-8 w-[1px] bg-surface-container-high mx-2"></div>
            <div className="flex items-center gap-3">
              <div className="text-right hidden md:block">
                <p className="text-xs font-bold text-on-surface capitalize">{role}</p>
                <p className="text-[10px] text-on-surface-variant uppercase tracking-widest">Usuario Activo</p>
              </div>
              <div className="w-9 h-9 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold text-xs border-2 border-surface-container-highest uppercase">
                {role.substring(0, 2)}
              </div>
            </div>
          </div>
        </header>

        {/* View Container */}
        <main className="p-10 flex-1">
          {children}
        </main>
      </div>
    </div>
  );
}
