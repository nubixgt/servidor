import React from 'react';
import { ViewType, AthleteProfile } from '../types';

interface TopHeaderProps {
  currentView: ViewType;
  onNavigate: (view: ViewType) => void;
  athlete: AthleteProfile;
  onOpenNotifications: () => void;
  onOpenSettings: () => void;
  onToggleMobileMenu: () => void;
}

export const TopHeader: React.FC<TopHeaderProps> = ({
  currentView,
  onNavigate,
  athlete,
  onOpenNotifications,
  onOpenSettings,
  onToggleMobileMenu,
}) => {
  return (
    <header
      id="top-header"
      className="fixed top-0 right-0 w-full md:w-[calc(100%-16rem)] z-40 backdrop-blur-2xl border-b border-white/10 bg-[#0d0f12]/75 flex justify-between items-center px-6 lg:px-10 py-3.5 h-[72px] transition-all"
    >
      {/* Left side: Mobile trigger & Navigation tabs */}
      <div className="flex items-center gap-6">
        <button
          id="btn-mobile-menu"
          onClick={onToggleMobileMenu}
          className="md:hidden text-[#f1f0ee] p-1.5 rounded-lg hover:bg-white/10"
          aria-label="Abrir menú de navegación"
        >
          <span className="material-symbols-outlined text-2xl">menu</span>
        </button>

        <nav className="hidden lg:flex items-center gap-8">
          <button
            onClick={() => onNavigate('inicio')}
            className={`text-xs font-extrabold uppercase tracking-widest transition-all relative py-2 ${
              currentView === 'inicio'
                ? 'text-[#caf300]'
                : 'text-[#8f9378] hover:text-white'
            }`}
          >
            DASHBOARD
            {currentView === 'inicio' && (
              <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#caf300] shadow-[0_0_10px_#caf300] rounded-full"></span>
            )}
          </button>
          <button
            onClick={() => onNavigate('analytics')}
            className={`text-xs font-extrabold uppercase tracking-widest transition-all relative py-2 ${
              currentView === 'analytics'
                ? 'text-[#caf300]'
                : 'text-[#8f9378] hover:text-white'
            }`}
          >
            ANALYTICS
            {currentView === 'analytics' && (
              <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#caf300] shadow-[0_0_10px_#caf300] rounded-full"></span>
            )}
          </button>
          <button
            onClick={() => onNavigate('reports')}
            className={`text-xs font-extrabold uppercase tracking-widest transition-all relative py-2 ${
              currentView === 'reports'
                ? 'text-[#caf300]'
                : 'text-[#8f9378] hover:text-white'
            }`}
          >
            REPORTS
            {currentView === 'reports' && (
              <span className="absolute bottom-0 left-0 w-full h-0.5 bg-[#caf300] shadow-[0_0_10px_#caf300] rounded-full"></span>
            )}
          </button>
        </nav>
      </div>

      {/* Right side: Actions & Athlete card */}
      <div className="flex items-center gap-4 sm:gap-5">
        <button
          id="btn-top-notifications"
          onClick={onOpenNotifications}
          className="relative p-2 text-[#c5c9ac] hover:text-[#caf300] transition-colors rounded-xl hover:bg-white/5"
          title="Notificaciones"
        >
          <span className="material-symbols-outlined text-[22px]">notifications</span>
          <span className="absolute top-2 right-2 w-2 h-2 bg-[#caf300] rounded-full shadow-[0_0_8px_#caf300]"></span>
        </button>

        <button
          id="btn-top-settings"
          onClick={onOpenSettings}
          className="p-2 text-[#c5c9ac] hover:text-[#caf300] transition-colors rounded-xl hover:bg-white/5"
          title="Configuración"
        >
          <span className="material-symbols-outlined text-[22px]">settings</span>
        </button>

        {/* User Pill Matching Reference Image */}
        <div
          onClick={() => onNavigate('perfil')}
          className="flex items-center gap-3 pl-2 sm:pl-3 cursor-pointer group"
        >
          <div className="text-right hidden sm:block">
            <p className="text-xs font-bold text-white group-hover:text-[#caf300] transition-colors">
              Hola, {athlete.name}
            </p>
            <p className="text-[10px] font-extrabold text-[#caf300] tracking-wider uppercase">
              {athlete.roleTitle}
            </p>
          </div>
          <div className="w-9 h-9 rounded-full overflow-hidden border border-[#caf300]/60 shadow-[0_0_10px_rgba(202,243,0,0.25)] group-hover:border-[#caf300] transition-all shrink-0">
            <img
              src={athlete.avatarUrl}
              alt={athlete.name}
              className="w-full h-full object-cover group-hover:scale-105 transition-transform"
            />
          </div>
          <span className="material-symbols-outlined text-[#8f9378] text-lg group-hover:text-[#caf300] transition-colors hidden sm:inline-block">
            keyboard_arrow_down
          </span>
        </div>
      </div>
    </header>
  );
};
