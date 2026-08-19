import React, { useState } from 'react';
import { 
  Search, 
  Bell, 
  Menu, 
  Sun,
  User,
  LogOut,
  AlertTriangle
} from 'lucide-react';
import { UserProfile, SystemAlert } from '../types';

interface TopAppBarProps {
  currentUser: UserProfile;
  alerts: SystemAlert[];
  onOpenMobileMenu: () => void;
  onSearch: (query: string) => void;
  selectedRegion: string;
  onChangeRegion: (region: string) => void;
  onLogout: () => void;
  onSelectTab: (tab: any) => void;
}

export const TopAppBar: React.FC<TopAppBarProps> = ({
  currentUser,
  alerts,
  onOpenMobileMenu,
  onSearch,
  onLogout,
  onSelectTab
}) => {
  const [showNotifications, setShowNotifications] = useState(false);
  const [showProfileMenu, setShowProfileMenu] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value;
    setSearchQuery(val);
    onSearch(val);
  };

  return (
    <header className="bg-[#081611]/95 fixed top-0 right-0 w-full md:w-[calc(100%-260px)] z-40 border-b border-[#142f24] flex justify-between items-center px-4 sm:px-6 py-2.5 h-[64px] font-[Arial,Helvetica,sans-serif]">
      {/* Left: Mobile Toggle & Global Search Bar */}
      <div className="flex-1 flex items-center gap-3">
        <button
          onClick={onOpenMobileMenu}
          className="md:hidden p-2 rounded-lg text-[#94a3b8] hover:text-white hover:bg-[#0c1e17] transition-colors"
        >
          <Menu className="w-5 h-5" />
        </button>

        {/* Search Bar */}
        <div className="relative w-full max-w-md group">
          <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#64748b] group-focus-within:text-white transition-colors" />
          <input
            type="text"
            value={searchQuery}
            onChange={handleSearchChange}
            placeholder="Buscar parcelas, técnicos, departamentos..."
            className="w-full bg-[#0c1e17] border border-[#17382b] rounded-full py-1.5 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-[#22c55e]/60 transition-all placeholder:text-[#64748b]"
          />
        </div>
      </div>

      {/* Right Side: Weather indicator + Notifications + User Avatar */}
      <div className="flex items-center space-x-3 sm:space-x-4">
        {/* Weather Indicator */}
        <div className="hidden sm:flex items-center gap-1.5 text-xs text-[#cbd5e1] bg-[#0c1e17] border border-[#17382b] px-3 py-1.5 rounded-full">
          <Sun className="w-3.5 h-3.5 text-[#eab308]" />
          <span className="font-medium text-white">24°</span>
          <span className="text-[#94a3b8]">Guatemala</span>
        </div>

        {/* Notifications */}
        <div className="relative">
          <button
            onClick={() => {
              setShowNotifications(!showNotifications);
              setShowProfileMenu(false);
            }}
            className="p-2 text-[#94a3b8] hover:text-white transition-colors relative rounded-full hover:bg-[#0c1e17]"
            title="Alertas del Sistema"
          >
            <Bell className="w-4 h-4" />
            <span className="absolute top-1 right-1 w-4 h-4 bg-[#ef4444] text-white text-[9px] font-bold rounded-full flex items-center justify-center">
              3
            </span>
          </button>

          {showNotifications && (
            <div className="absolute right-0 mt-2 w-80 bg-[#0c1e17] border border-[#17382b] rounded-2xl shadow-2xl p-4 z-50 animate-fadeIn">
              <div className="flex justify-between items-center pb-2 mb-2 border-b border-[#17382b]">
                <h4 className="text-xs font-bold text-white uppercase tracking-wider">Alertas Activas</h4>
                <span className="text-[10px] bg-[#22c55e]/20 text-[#22c55e] px-2 py-0.5 rounded-full font-bold">
                  3 nuevas
                </span>
              </div>

              <div className="space-y-2 max-h-72 overflow-y-auto pr-1">
                {alerts.slice(0, 3).map((al) => (
                  <div 
                    key={al.id} 
                    className="p-2.5 rounded-xl border border-[#17382b] bg-[#081611] text-xs"
                  >
                    <div className="flex items-start gap-2">
                      <AlertTriangle className="w-4 h-4 text-[#eab308] flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="font-semibold text-white">{al.title}</p>
                        <p className="text-[11px] text-[#94a3b8] mt-0.5 leading-snug">{al.description}</p>
                        <span className="text-[9px] text-[#64748b] mt-1 block">{al.location} · {al.date}</span>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* User Profile Avatar */}
        <div className="relative">
          <button
            onClick={() => {
              setShowProfileMenu(!showProfileMenu);
              setShowNotifications(false);
            }}
            className="flex items-center"
          >
            <div className="w-8 h-8 rounded-full border border-[#22c55e]/50 overflow-hidden cursor-pointer hover:border-[#22c55e] transition-colors bg-[#133225] flex items-center justify-center">
              {currentUser.avatarUrl ? (
                <img src={currentUser.avatarUrl} alt={currentUser.name} className="w-full h-full object-cover" />
              ) : (
                <span className="text-xs font-bold text-[#22c55e]">{currentUser.initials || 'AM'}</span>
              )}
            </div>
          </button>

          {showProfileMenu && (
            <div className="absolute right-0 mt-2 w-56 bg-[#0c1e17] border border-[#17382b] rounded-2xl shadow-2xl p-2 z-50 animate-fadeIn">
              <div className="p-2.5 border-b border-[#17382b] mb-1">
                <p className="text-xs font-bold text-white truncate">{currentUser.name}</p>
                <p className="text-[11px] text-[#22c55e]">{currentUser.role} · {currentUser.region}</p>
                <span className="text-[10px] text-[#64748b]">{currentUser.email}</span>
              </div>

              <button
                onClick={() => {
                  onSelectTab('settings');
                  setShowProfileMenu(false);
                }}
                className="w-full text-left px-3 py-2 rounded-xl text-xs text-[#cbd5e1] hover:bg-[#133225] flex items-center gap-2 transition-colors"
              >
                <User className="w-4 h-4 text-[#94a3b8]" />
                <span>Perfil y Cuenta</span>
              </button>

              <button
                onClick={() => {
                  onLogout();
                  setShowProfileMenu(false);
                }}
                className="w-full text-left px-3 py-2 rounded-xl text-xs text-[#f87171] hover:bg-[#ef4444]/20 flex items-center gap-2 transition-colors mt-1"
              >
                <LogOut className="w-4 h-4" />
                <span>Cerrar Sesión</span>
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
};
