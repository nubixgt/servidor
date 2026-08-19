import React from 'react';
import { 
  LayoutDashboard, 
  Layers, 
  FileEdit, 
  Sliders, 
  BarChart2, 
  Bug, 
  Users, 
  Settings, 
  LogOut, 
  Sprout, 
  ChevronDown,
  X
} from 'lucide-react';
import { NavigationTab, UserProfile } from '../types';

interface SideNavBarProps {
  activeTab: NavigationTab;
  onSelectTab: (tab: NavigationTab) => void;
  onOpenNewRegistration: () => void;
  onOpenSupport?: () => void;
  onLogout?: () => void;
  currentUser?: UserProfile;
  isOpenMobile?: boolean;
  onCloseMobile?: () => void;
}

export const SideNavBar: React.FC<SideNavBarProps> = ({
  activeTab,
  onSelectTab,
  onOpenNewRegistration,
  onLogout,
  currentUser,
  isOpenMobile = false,
  onCloseMobile
}) => {
  const navItems: { id: NavigationTab; label: string; icon: React.ReactNode; action?: () => void }[] = [
    { id: 'dashboard', label: 'Panel ejecutivo', icon: <LayoutDashboard className="w-4 h-4" /> },
    { id: 'plot-inventory', label: 'Todas las parcelas', icon: <Layers className="w-4 h-4" /> },
    { 
      id: 'registration-wizard', 
      label: 'Registrar parcela', 
      icon: <FileEdit className="w-4 h-4" />,
      action: onOpenNewRegistration
    },
    { id: 'technical-variables', label: 'Variables técnicas', icon: <Sliders className="w-4 h-4" /> },
    { id: 'supervisor-reviews', label: 'Reportes y análisis', icon: <BarChart2 className="w-4 h-4" /> },
    { id: 'field-surveys', label: 'Bioindicadores', icon: <Bug className="w-4 h-4" /> },
    { id: 'user-management', label: 'Usuarios y equipo', icon: <Users className="w-4 h-4" /> },
    { id: 'settings', label: 'Configuración', icon: <Settings className="w-4 h-4" /> },
  ];

  return (
    <>
      {/* Mobile Backdrop */}
      {isOpenMobile && (
        <div 
          onClick={onCloseMobile}
          className="fixed inset-0 bg-black/70 backdrop-blur-md z-40 md:hidden animate-fadeIn"
        />
      )}

      <aside className={`
        w-[260px] h-screen fixed left-0 top-0 
        bg-[#081611] border-r border-[#142f24]
        flex flex-col p-4 z-50 transition-transform duration-300 ease-in-out font-[Arial,Helvetica,sans-serif]
        ${isOpenMobile ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}
      `}>
        {/* Header / Brand */}
        <div className="mb-5 flex items-center justify-between px-2 pt-1">
          <div className="flex items-center space-x-2.5 cursor-pointer" onClick={() => onSelectTab('dashboard')}>
            <div className="w-8 h-8 rounded-lg bg-[#153e2d] border border-[#22c55e]/40 flex items-center justify-center shadow-[0_0_12px_rgba(34,197,94,0.25)]">
              <Sprout className="w-5 h-5 text-[#22c55e]" />
            </div>
            <div>
              <h1 className="text-lg font-bold text-white tracking-tight">
                Keyline<span className="text-[#22c55e]">GT</span>
              </h1>
            </div>
          </div>
          
          {isOpenMobile && (
            <button 
              onClick={onCloseMobile}
              className="p-1 rounded-lg text-[#94a3b8] hover:text-white md:hidden"
            >
              <X className="w-5 h-5" />
            </button>
          )}
        </div>

        {/* Navigation Tabs */}
        <nav className="flex-1 space-y-1 overflow-y-auto pr-1">
          {navItems.map((item, idx) => {
            const isActive = activeTab === item.id;
            return (
              <button
                key={`${item.id}-${idx}`}
                onClick={() => {
                  if (item.action) {
                    item.action();
                  } else {
                    onSelectTab(item.id);
                  }
                  if (onCloseMobile) onCloseMobile();
                }}
                className={`w-full flex items-center space-x-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all duration-150 text-left ${
                  isActive
                    ? 'bg-[#133225] border border-[#1b4334] text-white font-semibold'
                    : 'text-[#94a3b8] hover:text-white hover:bg-[#0c1e17]'
                }`}
              >
                <span className={isActive ? 'text-[#22c55e]' : 'text-[#64748b] group-hover:text-white'}>
                  {item.icon}
                </span>
                <span className="truncate">{item.label}</span>
              </button>
            );
          })}
        </nav>

        {/* Scenic Agroforestry Hill Art Banner at Bottom of Sidebar */}
        <div className="my-3 rounded-xl overflow-hidden relative h-20 bg-gradient-to-t from-[#0c2419] to-[#081611] border border-[#17382b] flex items-end justify-center">
          <svg className="w-full h-full opacity-60" viewBox="0 0 200 80" preserveAspectRatio="none">
            <path d="M0 60 Q 40 20 80 50 T 160 30 T 200 65 L 200 80 L 0 80 Z" fill="#133d2a" />
            <path d="M0 70 Q 60 40 120 60 T 200 50 L 200 80 L 0 80 Z" fill="#1e543b" />
            <path d="M0 75 Q 70 55 140 70 T 200 65 L 200 80 L 0 80 Z" fill="#22c55e" opacity="0.4" />
            {/* Keyline Contours */}
            <path d="M10 55 Q 50 25 90 52 T 180 35" stroke="#4ade80" strokeWidth="0.75" fill="none" strokeDasharray="3,2" opacity="0.7" />
            <path d="M5 68 Q 65 44 125 63 T 195 53" stroke="#4ade80" strokeWidth="0.75" fill="none" strokeDasharray="3,2" opacity="0.7" />
          </svg>
        </div>

        {/* Bottom Profile Card & Logout Button */}
        <div className="pt-2 space-y-2 border-t border-[#142f24]">
          <div 
            onClick={() => onSelectTab('settings')}
            className="p-2 rounded-xl bg-[#0c1e17] border border-[#17382b] flex items-center justify-between gap-2 cursor-pointer hover:border-[#22c55e]/40 transition-colors"
          >
            <div className="flex items-center gap-2.5 overflow-hidden">
              <div className="w-8 h-8 rounded-full bg-[#1e4635] border border-[#22c55e]/40 flex items-center justify-center text-xs font-bold text-[#22c55e] flex-shrink-0">
                {currentUser?.avatarUrl ? (
                  <img src={currentUser.avatarUrl} alt={currentUser.name} className="w-full h-full rounded-full object-cover" />
                ) : (
                  currentUser?.initials || 'AM'
                )}
              </div>
              <div className="overflow-hidden">
                <p className="text-[12px] font-semibold text-white truncate">
                  {currentUser?.name || 'Ana Martínez Valdés'}
                </p>
                <p className="text-[11px] text-[#64748b] truncate">
                  {currentUser?.role || 'Supervisora'}
                </p>
              </div>
            </div>
            <ChevronDown className="w-3.5 h-3.5 text-[#64748b] flex-shrink-0" />
          </div>

          <button
            onClick={onLogout}
            className="w-full py-2 px-3 bg-[#0c1e17] hover:bg-[#ef4444]/15 hover:border-[#ef4444]/40 border border-[#17382b] rounded-xl text-[12px] text-[#94a3b8] hover:text-[#f87171] transition-all flex items-center justify-center gap-2 font-medium"
          >
            <LogOut className="w-3.5 h-3.5" />
            <span>Cerrar sesión</span>
          </button>
        </div>
      </aside>
    </>
  );
};
