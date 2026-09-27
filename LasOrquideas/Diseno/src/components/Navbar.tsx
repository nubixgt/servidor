import { useState } from 'react';
import { Menu, X, MessageCircle, Eye, LayoutGrid } from 'lucide-react';

interface NavbarProps {
  activeScreen: string;
  setActiveScreen: (screen: string) => void;
  viewMode: 'landing' | 'screens';
  setViewMode: (mode: 'landing' | 'screens') => void;
}

export function Navbar({ activeScreen, setActiveScreen, viewMode, setViewMode }: NavbarProps) {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const navLinks = [
    { id: 'inicio', label: 'Inicio' },
    { id: 'proyecto', label: 'Proyecto' },
    { id: 'lotes', label: 'Lotes' },
    { id: 'ubicacion', label: 'Ubicación' },
    { id: 'galeria', label: 'Galería' },
    { id: 'contacto', label: 'Contacto' },
  ];

  const handleNavClick = (id: string) => {
    setActiveScreen(id);
    setMobileMenuOpen(false);

    if (viewMode === 'landing') {
      const element = document.getElementById(id);
      if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
      }
    }
  };

  return (
    <header className="fixed top-4 left-0 right-0 z-50 px-3 md:px-8 pointer-events-none">
      <div className="max-w-6xl mx-auto pointer-events-auto">
        <nav
          className="glass-nav rounded-full px-3 md:px-4 py-2 flex items-center justify-between shadow-lg border border-white/80"
          aria-label="Navegación principal"
        >
          {/* Logo Las Orquídeas */}
          <button
            onClick={() => handleNavClick('inicio')}
            className="flex items-center gap-2.5 md:gap-3 pl-1 md:pl-2 text-left group cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-forest rounded-full"
          >
            <div className="w-9 h-9 md:w-10 md:h-10 rounded-full border border-slate-300/80 bg-white shadow-sm flex items-center justify-center p-1.5 transition-transform duration-300 group-hover:scale-105">
              <svg className="w-full h-full" fill="none" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" fill="#FBF5F8" r="46" stroke="#E2D4DE" strokeWidth="2" />
                <path d="M50 14L22 42V82H78V42L50 14Z" fill="#FFF" stroke="#222" strokeWidth="3" />
                <path d="M50 20L28 44V76H46V54H54V76H72V44L50 20Z" fill="#E8F5E9" />
                <path d="M50 25L32 45L50 65L68 45L50 25Z" fill="#E5989B" />
                <path d="M50 65L32 45L40 76H50V65Z" fill="#B5337A" opacity="0.8" />
                <path d="M50 65L68 45L60 76H50V65Z" fill="#6B9080" opacity="0.8" />
                <rect fill="#2D3748" height="5" width="5" x="44" y="38" />
                <rect fill="#2D3748" height="5" width="5" x="51" y="38" />
                <rect fill="#2D3748" height="5" width="5" x="44" y="45" />
                <rect fill="#2D3748" height="5" width="5" x="51" y="45" />
              </svg>
            </div>
            <div className="flex flex-col">
              <span className="font-extrabold text-xs md:text-sm tracking-wider text-slate-900 leading-tight">
                LAS ORQUÍDEAS
              </span>
              <span className="text-[8px] md:text-[9px] font-semibold tracking-[0.22em] text-slate-500 uppercase">
                Lotificadora
              </span>
            </div>
          </button>

          {/* Desktop Navigation Items */}
          <div className="hidden md:flex items-center space-x-1 lg:space-x-1.5 text-xs lg:text-sm font-medium text-slate-600">
            {navLinks.map((link) => {
              const isActive = activeScreen === link.id;
              return (
                <button
                  key={link.id}
                  onClick={() => handleNavClick(link.id)}
                  className={`px-3.5 py-1.5 rounded-full transition-all duration-200 cursor-pointer ${
                    isActive
                      ? 'bg-[#E5EEF9] text-[#2B5282] font-semibold shadow-xs'
                      : 'hover:text-brand-forest hover:bg-slate-100/80'
                  }`}
                >
                  {link.label}
                </button>
              );
            })}
          </div>

          {/* Right Action buttons */}
          <div className="flex items-center gap-2">
            {/* View Mode Switcher (Vista Completa vs Pantallas dedicadas) */}
            <div className="hidden lg:flex items-center p-0.5 bg-slate-200/70 rounded-full text-[11px] font-medium text-slate-600">
              <button
                onClick={() => setViewMode('landing')}
                className={`flex items-center gap-1 px-2.5 py-1 rounded-full transition-all ${
                  viewMode === 'landing'
                    ? 'bg-white text-slate-900 shadow-xs font-semibold'
                    : 'hover:text-slate-900'
                }`}
                title="Ver página completa continua"
              >
                <Eye className="w-3 h-3" />
                <span>Página</span>
              </button>
              <button
                onClick={() => setViewMode('screens')}
                className={`flex items-center gap-1 px-2.5 py-1 rounded-full transition-all ${
                  viewMode === 'screens'
                    ? 'bg-white text-brand-forest shadow-xs font-semibold'
                    : 'hover:text-slate-900'
                }`}
                title="Navegar por pantallas individuales interactivas"
              >
                <LayoutGrid className="w-3 h-3" />
                <span>Pantallas</span>
              </button>
            </div>

            {/* Contact WhatsApp Button */}
            <a
              href="https://wa.me/50255555555?text=Hola,%20deseo%20información%20sobre%20los%20lotes%20disponibles%20en%20Las%20Orquídeas"
              target="_blank"
              rel="noopener noreferrer"
              className="bg-brand-forest hover:bg-brand-forest-dark text-white px-3.5 md:px-4 py-2 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-md transition-all hover:shadow-lg transform active:scale-95 whitespace-nowrap"
            >
              <MessageCircle className="w-3.5 h-3.5 fill-current" />
              <span>Contáctanos</span>
            </a>

            {/* Mobile Hamburger Button */}
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="md:hidden p-2 rounded-full hover:bg-slate-100 text-slate-700 transition"
              aria-label="Abrir menú"
            >
              {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
            </button>
          </div>
        </nav>

        {/* Mobile Dropdown Menu */}
        {mobileMenuOpen && (
          <div className="md:hidden mt-2 p-4 glass-card rounded-2xl shadow-xl border border-white/90 animate-fadeIn">
            <div className="flex flex-col space-y-1">
              {navLinks.map((link) => (
                <button
                  key={link.id}
                  onClick={() => handleNavClick(link.id)}
                  className={`text-left px-3 py-2 rounded-xl text-sm font-medium transition ${
                    activeScreen === link.id
                      ? 'bg-brand-forest text-white font-semibold'
                      : 'text-slate-700 hover:bg-slate-100'
                  }`}
                >
                  {link.label}
                </button>
              ))}
            </div>

            {/* Mobile View Mode switch */}
            <div className="mt-3 pt-3 border-t border-slate-200/80 flex items-center justify-between text-xs text-slate-600">
              <span className="font-semibold">Modo de Navegación:</span>
              <div className="flex gap-1 bg-slate-100 p-0.5 rounded-lg">
                <button
                  onClick={() => setViewMode('landing')}
                  className={`px-2 py-1 rounded-md text-[11px] ${
                    viewMode === 'landing' ? 'bg-white shadow-xs font-bold text-slate-900' : ''
                  }`}
                >
                  Página continua
                </button>
                <button
                  onClick={() => setViewMode('screens')}
                  className={`px-2 py-1 rounded-md text-[11px] ${
                    viewMode === 'screens' ? 'bg-white shadow-xs font-bold text-brand-forest' : ''
                  }`}
                >
                  Pantallas
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </header>
  );
}
