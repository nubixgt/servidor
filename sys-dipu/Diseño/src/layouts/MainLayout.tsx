import React from 'react';
import { Role, Category } from '../types';

const SidebarItem = ({ icon, label, active = false, onClick }: { icon: string, label: string, active?: boolean, onClick: () => void, key?: React.Key }) => (
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
            <div className="w-10 h-10 flex items-center justify-center">
              <svg width="38" height="38" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                  <radialGradient id="rsb-bg" cx="40" cy="28" r="44" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#1b3a6b"/>
                    <stop offset="100%" stopColor="#060e1c"/>
                  </radialGradient>
                  <linearGradient id="rsb-teal" x1="0" y1="0" x2="0" y2="80" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#7dd8f3"/>
                    <stop offset="100%" stopColor="#0891b2"/>
                  </linearGradient>
                  <linearGradient id="rsb-gold" x1="0" y1="0" x2="0" y2="80" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#fef08a"/>
                    <stop offset="100%" stopColor="#d97706"/>
                  </linearGradient>
                  <linearGradient id="rsb-border" x1="8" y1="4" x2="72" y2="76" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stopColor="#67e8f9"/>
                    <stop offset="55%" stopColor="#0891b2"/>
                    <stop offset="100%" stopColor="#164e63"/>
                  </linearGradient>
                  <filter id="rsb-glow">
                    <feGaussianBlur stdDeviation="2" result="b"/>
                    <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                  </filter>
                  <filter id="rsb-drop">
                    <feDropShadow dx="0" dy="3" stdDeviation="5" floodColor="#000a1f" floodOpacity="0.5"/>
                  </filter>
                </defs>
                <polygon points="40,4 73,22 73,58 40,76 7,58 7,22" fill="url(#rsb-bg)" filter="url(#rsb-drop)"/>
                <polygon points="40,4 73,22 73,58 40,76 7,58 7,22" fill="none" stroke="url(#rsb-border)" strokeWidth="1.5"/>
                <rect x="24" y="35" width="5.5" height="20" rx="2.75" fill="url(#rsb-teal)" filter="url(#rsb-glow)"/>
                <rect x="50.5" y="35" width="5.5" height="20" rx="2.75" fill="url(#rsb-teal)" filter="url(#rsb-glow)"/>
                <rect x="37.25" y="29" width="5.5" height="26" rx="2.75" fill="url(#rsb-gold)" filter="url(#rsb-glow)"/>
                <polygon points="40,19 58,32 22,32" fill="url(#rsb-gold)" opacity="0.9" filter="url(#rsb-glow)"/>
                <rect x="20" y="56" width="40" height="3.5" rx="1.75" fill="url(#rsb-teal)" opacity="0.8"/>
                <path d="M40,1.5 L41.5,3.5 L44,5 L41.5,6.5 L40,8.5 L38.5,6.5 L36,5 L38.5,3.5 Z" fill="url(#rsb-gold)" filter="url(#rsb-glow)"/>
              </svg>
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
          <button className="w-full py-3 px-4 bg-linear-to-br from-primary to-primary-dim text-on-primary rounded-xl font-bold flex items-center justify-center gap-2 mb-4 shadow-ambient hover:scale-[1.02] transition-transform">
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
            <div className="h-8 w-px bg-surface-container-high mx-2"></div>
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
