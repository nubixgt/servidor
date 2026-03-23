import React from 'react';
import { Outlet, NavLink, useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { 
  LayoutDashboard, 
  MapPin, 
  PlusCircle, 
  BarChart3, 
  Users, 
  LogOut, 
  History, 
  Bell, 
  Search,
  Menu
} from 'lucide-react';
import { cn } from '../lib/utils';

export default function Layout() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = React.useState(false);

  if (!user) {
    navigate('/login');
    return null;
  }

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const adminLinks = [
    { to: '/admin', icon: LayoutDashboard, label: 'Dashboard' },
    { to: '/admin/locations', icon: MapPin, label: 'Locaciones' },
    { to: '/admin/new', icon: PlusCircle, label: 'Nueva Transacción' },
    { to: '/admin/reports', icon: BarChart3, label: 'Reportes' },
    { to: '/admin/users', icon: Users, label: 'Usuarios' },
  ];

  const techLinks = [
    { to: '/tech', icon: LayoutDashboard, label: 'Dashboard' },
    { to: '/tech/history', icon: History, label: 'Historial' },
  ];

  const links = user.role === 'admin' ? adminLinks : techLinks;

  return (
    <div className="flex h-screen bg-surface overflow-hidden">
      {/* Sidebar */}
      <aside className={cn(
        "fixed inset-y-0 left-0 z-50 w-64 bg-white/80 backdrop-blur-xl border-r border-outline-variant/30 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:flex lg:flex-col",
        isMobileMenuOpen ? "translate-x-0" : "-translate-x-full"
      )}>
        <div className="flex items-center justify-center h-20 border-b border-outline-variant/20">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary rounded-xl flex items-center justify-center shadow-sm">
              <span className="text-white font-bold text-xl">AL</span>
            </div>
            <span className="font-sans font-bold text-xl text-primary tracking-tight">Ledger</span>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto py-6 px-4 space-y-2">
          <div className="text-xs font-semibold text-outline uppercase tracking-wider mb-4 px-3">
            Menú Principal
          </div>
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              end={link.to === '/admin' || link.to === '/tech'}
              onClick={() => setIsMobileMenuOpen(false)}
              className={({ isActive }) => cn(
                "flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 group",
                isActive 
                  ? "bg-primary-fixed text-on-primary-fixed font-medium shadow-sm" 
                  : "text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
              )}
            >
              <link.icon className={cn(
                "w-5 h-5 transition-colors",
                location.pathname === link.to || (link.to !== '/admin' && link.to !== '/tech' && location.pathname.startsWith(link.to)) ? "text-primary" : "text-outline group-hover:text-on-surface"
              )} />
              <span>{link.label}</span>
            </NavLink>
          ))}
        </div>

        <div className="p-4 border-t border-outline-variant/20">
          <button
            onClick={handleLogout}
            className="flex items-center gap-3 px-3 py-3 w-full rounded-xl text-error hover:bg-error-container/50 transition-colors duration-200"
          >
            <LogOut className="w-5 h-5" />
            <span className="font-medium">Cerrar Sesión</span>
          </button>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        {/* Topbar */}
        <header className="h-20 bg-white/60 backdrop-blur-md border-b border-outline-variant/20 flex items-center justify-between px-6 z-40 sticky top-0">
          <div className="flex items-center gap-4">
            <button 
              className="lg:hidden p-2 text-on-surface-variant hover:bg-surface-container rounded-lg transition-colors"
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
            >
              <Menu className="w-6 h-6" />
            </button>
            <div className="hidden md:flex items-center bg-surface-container-low rounded-full px-4 py-2 border border-outline-variant/30 focus-within:border-primary/50 focus-within:bg-white transition-all w-64 lg:w-96">
              <Search className="w-4 h-4 text-outline mr-2" />
              <input 
                type="text" 
                placeholder="Buscar..." 
                className="bg-transparent border-none outline-none text-sm w-full text-on-surface placeholder:text-outline"
              />
            </div>
          </div>

          <div className="flex items-center gap-4">
            <button className="p-2 text-on-surface-variant hover:bg-surface-container rounded-full transition-colors relative">
              <Bell className="w-5 h-5" />
              <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-error rounded-full border-2 border-white"></span>
            </button>
            <div className="flex items-center gap-3 pl-4 border-l border-outline-variant/30">
              <div className="text-right hidden sm:block">
                <p className="text-sm font-semibold text-on-surface">{user.name}</p>
                <p className="text-xs text-outline capitalize">{user.role}</p>
              </div>
              <div className="w-10 h-10 rounded-full bg-primary-container text-on-primary-container flex items-center justify-center font-bold shadow-sm border border-primary-fixed">
                {user.name.charAt(0)}
              </div>
            </div>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
          <div className="max-w-7xl mx-auto">
            <Outlet />
          </div>
        </main>
      </div>
      
      {/* Mobile Overlay */}
      {isMobileMenuOpen && (
        <div 
          className="fixed inset-0 bg-on-surface/20 backdrop-blur-sm z-40 lg:hidden"
          onClick={() => setIsMobileMenuOpen(false)}
        />
      )}
    </div>
  );
}
