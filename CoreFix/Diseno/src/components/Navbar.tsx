import React, { useState } from 'react';
import { NavTab } from '../types';
import { 
  Wrench, 
  Menu, 
  X, 
  Search, 
  PhoneCall, 
  ShieldCheck, 
  Clock 
} from 'lucide-react';

interface NavbarProps {
  activeTab: NavTab;
  setActiveTab: (tab: NavTab) => void;
  onOpenTracker: () => void;
  onOpenQuoteModal: () => void;
}

export const Navbar: React.FC<NavbarProps> = ({
  activeTab,
  setActiveTab,
  onOpenTracker,
  onOpenQuoteModal,
}) => {
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const navItems: { id: NavTab; label: string }[] = [
    { id: 'inicio', label: 'Inicio' },
    { id: 'servicios', label: 'Servicios' },
    { id: 'trabajos', label: 'Trabajos' },
    { id: 'contacto', label: 'Contacto' },
  ];

  return (
    <header className="sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-slate-100 shadow-xs">
      {/* Top micro banner for quick info / trust */}
      <div className="bg-slate-900 text-slate-300 text-xs py-1.5 px-4 hidden md:block">
        <div className="max-w-7xl mx-auto flex justify-between items-center">
          <div className="flex items-center space-x-6">
            <span className="flex items-center gap-1.5">
              <Clock className="w-3.5 h-3.5 text-blue-400" />
              Lun a Sáb: 9:00 a 19:00 hs
            </span>
            <span className="flex items-center gap-1.5">
              <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
              Garantía escrita de hasta 6 meses en todas las reparaciones
            </span>
          </div>
          <div className="flex items-center space-x-4 text-slate-300">
            <button 
              onClick={onOpenTracker}
              className="hover:text-white flex items-center gap-1 text-blue-400 hover:underline transition-colors cursor-pointer"
            >
              <Search className="w-3.5 h-3.5" />
              Rastrear mi Equipo (Ticket)
            </button>
            <span className="text-slate-600">|</span>
            <a href="tel:+12345678900" className="hover:text-white flex items-center gap-1 transition-colors">
              <PhoneCall className="w-3.5 h-3.5 text-blue-400" />
              +1 234 567 8900
            </a>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-20">
          {/* Brand Logo */}
          <div 
            onClick={() => { setActiveTab('inicio'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}
            className="flex items-center gap-2.5 cursor-pointer group select-none"
            id="navbar-brand-logo"
          >
            <div className="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-all duration-300 shadow-xs border border-blue-100">
              <div className="relative">
                <Wrench className="w-5 h-5 -rotate-45" />
              </div>
            </div>
            <div className="flex flex-col">
              <span className="text-xl sm:text-2xl font-bold tracking-tight text-blue-600 flex items-center">
                TechFix <span className="text-slate-900 font-bold ml-1.5">Services</span>
              </span>
            </div>
          </div>

          {/* Desktop Navigation Links */}
          <nav className="hidden md:flex items-center space-x-1 lg:space-x-2">
            {navItems.map((item) => {
              const isActive = activeTab === item.id;
              return (
                <button
                  key={item.id}
                  id={`nav-item-${item.id}`}
                  onClick={() => {
                    setActiveTab(item.id);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                  }}
                  className={`relative px-4 py-2 text-sm font-medium transition-colors cursor-pointer ${
                    isActive 
                      ? 'text-blue-600' 
                      : 'text-slate-600 hover:text-slate-900'
                  }`}
                >
                  {item.label}
                  {isActive && (
                    <span className="absolute bottom-0 left-4 right-4 h-0.5 bg-blue-600 rounded-full animate-in fade-in duration-200" />
                  )}
                </button>
              );
            })}
          </nav>

          {/* Right Action Buttons */}
          <div className="hidden md:flex items-center space-x-3">
            <button
              onClick={onOpenTracker}
              id="btn-nav-track-order"
              className="px-3.5 py-2 text-sm font-medium text-slate-700 hover:text-blue-600 hover:bg-slate-50 rounded-lg border border-slate-200 transition-all flex items-center gap-1.5 cursor-pointer"
              title="Consultar estado de reparación con tu código de ticket"
            >
              <Search className="w-4 h-4 text-slate-500" />
              <span>Estado de Orden</span>
            </button>

            <button
              onClick={() => {
                setActiveTab('contacto');
                window.scrollTo({ top: 0, behavior: 'smooth' });
              }}
              id="btn-nav-contact"
              className="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-all duration-200 shadow-sm hover:shadow-md cursor-pointer active:scale-98"
            >
              Contáctanos
            </button>
          </div>

          {/* Mobile Menu Toggle */}
          <div className="flex items-center space-x-2 md:hidden">
            <button
              onClick={onOpenTracker}
              className="p-2 text-slate-600 hover:text-blue-600 rounded-lg border border-slate-200"
              aria-label="Buscar ticket"
            >
              <Search className="w-5 h-5" />
            </button>
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="p-2 text-slate-700 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition-colors cursor-pointer"
              aria-label="Abrir menú"
              id="btn-mobile-menu-toggle"
            >
              {isMobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </div>

      {/* Mobile Drawer Menu */}
      {isMobileMenuOpen && (
        <div className="md:hidden border-t border-slate-100 bg-white px-4 pt-3 pb-6 space-y-3 shadow-lg">
          <div className="space-y-1">
            {navItems.map((item) => {
              const isActive = activeTab === item.id;
              return (
                <button
                  key={item.id}
                  onClick={() => {
                    setActiveTab(item.id);
                    setIsMobileMenuOpen(false);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                  }}
                  className={`w-full text-left px-3 py-2.5 rounded-lg text-base font-medium transition-colors ${
                    isActive
                      ? 'bg-blue-50 text-blue-600 font-semibold'
                      : 'text-slate-700 hover:bg-slate-50'
                  }`}
                >
                  {item.label}
                </button>
              );
            })}
          </div>

          <div className="pt-2 space-y-2 border-t border-slate-100">
            <button
              onClick={() => {
                onOpenTracker();
                setIsMobileMenuOpen(false);
              }}
              className="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 text-slate-700 font-medium text-sm hover:bg-slate-50"
            >
              <Search className="w-4 h-4 text-slate-500" />
              Consultar Estado de Orden
            </button>
            <button
              onClick={() => {
                onOpenQuoteModal();
                setIsMobileMenuOpen(false);
              }}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 rounded-lg text-sm transition-colors text-center shadow-xs"
            >
              Solicitar Presupuesto
            </button>
          </div>
        </div>
      )}
    </header>
  );
};
