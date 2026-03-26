import { Link, Outlet, useLocation } from 'react-router-dom';
import { Shield, Search, LayoutDashboard, Megaphone, Newspaper, Library, Menu } from 'lucide-react';
import { cn } from '../lib/utils';

export default function Layout() {
  const location = useLocation();

  const navItems = [
    { name: 'Inicio', path: '/', icon: LayoutDashboard },
    { name: 'Reportar', path: '/report', icon: Megaphone },
    { name: 'Noticias', path: '/news', icon: Newspaper },
    { name: 'Recursos', path: '/resources', icon: Library },
  ];

  return (
    <div className="min-h-screen bg-surface font-body text-on-surface antialiased pb-32">
      {/* Top Navigation Shell */}
      <header className="fixed top-0 w-full z-50 bg-white/60 backdrop-blur-xl shadow-[0_12px_32px_rgba(15,42,74,0.08)]">
        <div className="flex justify-between items-center w-full px-6 py-4 max-w-7xl mx-auto">
          <div className="flex items-center gap-3">
            <button className="md:hidden p-2 hover:bg-blue-50/50 rounded-full transition-colors active:scale-95 duration-200">
              <Menu className="text-blue-800 w-6 h-6" />
            </button>
            <Shield className="hidden md:block text-primary w-8 h-8" />
            <span className="text-2xl font-extrabold tracking-tighter text-blue-900 font-headline">AppUBA</span>
          </div>

          {/* Desktop Nav */}
          <nav className="hidden md:flex items-center space-x-8">
            {navItems.map((item) => (
              <Link
                key={item.path}
                to={item.path}
                className={cn(
                  "font-headline transition-colors px-3 py-1 rounded-full",
                  location.pathname === item.path
                    ? "text-blue-900 font-bold border-b-2 border-blue-900 rounded-none pb-1"
                    : "text-slate-500 hover:bg-blue-50/50"
                )}
              >
                {item.name}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-4">
            <button className="hidden md:block p-2 text-primary hover:bg-blue-50/50 rounded-full transition-colors">
              <Search className="w-6 h-6" />
            </button>
            <div className="w-10 h-10 rounded-full bg-primary-container flex items-center justify-center text-white font-bold border-2 border-primary-container shadow-sm overflow-hidden">
              <img
                alt="Avatar"
                className="w-full h-full object-cover"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuDJeUFsjdxbKL-yUXpFFE4PaArOBrzESrMkLh6m4ivmJPabNoSR8a6ujpvpUqwzj421YaDOn-39raSzUgOeJLb3faMmk4lTxLw65Fy12LukmsjefibgOEYZ9RygP1ZfayaMMmWpFiWHOuVPoYPqpzDHIhRWyzuzo-5H4VYaHSLp8Nk_cCy6Uou59I1CzlFcWr21s7tm5wCTnZblmKUaJrCF-gEl7oQSu6vP3pJUZKm1y3zauLsvC-QQdxkeNgx9qHszJCvnYSslzpI"
              />
            </div>
          </div>
        </div>
      </header>

      <main className="pt-24 px-4 sm:px-6 max-w-7xl mx-auto">
        <Outlet />
      </main>

      {/* Bottom Navigation Shell (Mobile) */}
      <nav className="md:hidden fixed bottom-0 left-0 w-full flex justify-around items-center px-4 pb-6 pt-3 bg-white/70 backdrop-blur-2xl z-50 shadow-[0_-8px_32px_rgba(0,0,0,0.05)] rounded-t-[2rem]">
        {navItems.map((item) => {
          const isActive = location.pathname === item.path;
          const Icon = item.icon;
          return (
            <Link
              key={item.path}
              to={item.path}
              className={cn(
                "flex flex-col items-center justify-center p-2 transition-all duration-300 ease-out",
                isActive
                  ? "bg-blue-800 text-white rounded-2xl px-5 py-2 scale-110 shadow-lg shadow-blue-200"
                  : "text-slate-400 hover:text-blue-600"
              )}
            >
              <Icon className={cn("w-6 h-6", isActive ? "mb-1" : "")} />
              <span className="font-headline text-[11px] font-semibold uppercase tracking-wider mt-1">
                {item.name}
              </span>
            </Link>
          );
        })}
      </nav>
    </div>
  );
}
