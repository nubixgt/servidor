import React from 'react';
import { ViewType, AthleteProfile } from '../types';

interface SidebarProps {
  currentView: ViewType;
  onNavigate: (view: ViewType) => void;
  athlete: AthleteProfile;
  onOpenAiCoach: () => void;
  onLogout: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({
  currentView,
  onNavigate,
  athlete,
  onOpenAiCoach,
  onLogout,
}) => {
  const navItems: { id: ViewType; label: string; icon: string }[] = [
    { id: 'inicio', label: 'Inicio', icon: 'home' },
    { id: 'perfil', label: 'Perfil', icon: 'person' },
    { id: 'plan', label: 'Plan', icon: 'calendar_today' },
    { id: 'historial', label: 'Historial', icon: 'history' },
    { id: 'cuaderno', label: 'Cuaderno', icon: 'edit_note' },
  ];

  return (
    <nav
      id="main-sidebar"
      className="hidden md:flex flex-col fixed left-0 top-0 h-full w-64 bg-[#0d0f12]/80 backdrop-blur-2xl border-r border-white/10 p-5 z-50 select-none shadow-2xl"
    >
      {/* Brand Header */}
      <div className="mb-6 px-1">
        <div
          onClick={() => onNavigate('inicio')}
          className="cursor-pointer transition-transform hover:scale-[1.01]"
        >
          <h1 className="text-2xl font-black text-white tracking-tight leading-none">
            Power Coach <span className="text-[#caf300]">AI</span>
          </h1>
          <p className="text-[11px] text-[#8f9378] font-bold tracking-widest uppercase mt-1">
            ELITE PERFORMANCE LAB
          </p>
        </div>
      </div>

      {/* User Mini Card (Pill style matching reference) */}
      <div
        onClick={() => onNavigate('perfil')}
        className="flex items-center gap-3 mb-6 p-2.5 bg-[#17191e]/80 border border-white/10 hover:border-[#caf300]/50 rounded-2xl cursor-pointer transition-all duration-200 group shadow-md"
      >
        <div className="relative w-10 h-10 rounded-full overflow-hidden border-2 border-[#caf300] shrink-0 shadow-[0_0_10px_rgba(202,243,0,0.3)]">
          <img
            src={athlete.avatarUrl}
            alt="Alex Triathlete"
            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
          />
          <div className="absolute bottom-0 right-0 w-2.5 h-2.5 bg-[#caf300] rounded-full ring-2 ring-black"></div>
        </div>
        <div className="flex-1 min-w-0">
          <p className="text-sm font-bold text-white truncate group-hover:text-[#caf300] transition-colors">
            {athlete.name} Triathlete
          </p>
          <p className="text-[10px] font-extrabold text-[#caf300] tracking-wider uppercase truncate">
            {athlete.roleTitle}
          </p>
        </div>
        <span className="material-symbols-outlined text-[#8f9378] text-lg group-hover:translate-x-0.5 transition-transform">
          chevron_right
        </span>
      </div>

      {/* Navigation Links */}
      <div className="flex-1 space-y-2 overflow-y-auto pr-1">
        {navItems.map((item) => {
          const isActive = currentView === item.id;
          return (
            <button
              key={item.id}
              id={`nav-link-${item.id}`}
              onClick={() => onNavigate(item.id)}
              className={`w-full flex items-center gap-3.5 px-4 py-3 rounded-xl text-left transition-all duration-200 ${
                isActive
                  ? 'bg-[#caf300] text-[#121700] font-extrabold shadow-[0_0_20px_rgba(202,243,0,0.35)] scale-[1.02]'
                  : 'text-[#c5c9ac] hover:text-white hover:bg-white/5 font-medium'
              }`}
            >
              <span
                className={`material-symbols-outlined text-[22px] ${
                  isActive ? 'text-[#121700]' : 'text-[#c5c9ac]'
                }`}
                style={isActive ? { fontVariationSettings: "'FILL' 1" } : undefined}
              >
                {item.icon}
              </span>
              <span className="text-sm tracking-tight">
                {item.label}
              </span>
            </button>
          );
        })}
      </div>

      {/* AI Agent CTA & Footer */}
      <div className="mt-auto pt-4 space-y-3 border-t border-white/10">
        <button
          id="btn-agente-ia-sidebar"
          onClick={onOpenAiCoach}
          className="w-full flex items-center justify-center gap-2.5 px-4 py-3.5 bg-[#caf300] hover:bg-[#b0d500] text-[#121700] rounded-xl font-extrabold text-sm tracking-wide transition-all shadow-[0_0_25px_rgba(202,243,0,0.35)] hover:scale-[1.02] active:scale-95"
        >
          <span className="material-symbols-outlined text-[22px]">smart_toy</span>
          <span>Agente IA</span>
        </button>

        <button
          id="btn-logout"
          onClick={onLogout}
          className="w-full flex items-center gap-3 px-3 py-2 text-[#ff8f83] hover:text-[#ffb4ab] hover:bg-[#93000a]/15 rounded-xl transition-colors text-xs font-semibold"
        >
          <span className="material-symbols-outlined text-[18px]">power_settings_new</span>
          <span>Cerrar Sesión</span>
        </button>
      </div>
    </nav>
  );
};
