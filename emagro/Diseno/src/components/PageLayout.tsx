import { Search, Bell, Settings } from 'lucide-react';
import { ReactNode } from 'react';

interface PageLayoutProps {
  title: string;
  subtitle: string;
  children: ReactNode;
  actions?: ReactNode;
}

export default function PageLayout({ title, subtitle, children, actions }: PageLayoutProps) {
  return (
    <main className="flex-1 overflow-y-auto pt-16 md:pt-0 bg-[#F8F9FA] relative">
      <div className="bg-[#2E7D32] pb-32 pt-8 px-8 rounded-b-[2.5rem] shadow-lg relative z-0">
        <div className="max-w-7xl mx-auto">
          <header className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
              <h1 className="text-2xl font-bold text-white mb-1">{title}</h1>
              <p className="text-green-100 text-sm">{subtitle}</p>
            </div>
            <div className="flex items-center gap-3">
              <div className="relative hidden md:block">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-white/70" size={18} />
                <input
                  className="pl-10 pr-4 py-2 rounded-lg border-none bg-white/10 text-white placeholder-white/70 focus:ring-2 focus:ring-white/50 focus:bg-white/20 transition-all text-sm w-64 shadow-inner outline-none"
                  placeholder="Buscar..."
                  type="text"
                />
              </div>
              <button className="p-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-colors relative shadow-sm">
                <Bell size={20} />
                <span className="absolute top-2 right-2 w-2 h-2 rounded-full bg-red-400 border border-[#2E7D32]"></span>
              </button>
              <button className="p-2 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-colors shadow-sm">
                <Settings size={20} />
              </button>
            </div>
          </header>
          {actions && <div className="mt-4">{actions}</div>}
        </div>
      </div>

      <div className="px-6 md:px-8 max-w-7xl mx-auto -mt-24 pb-8 relative z-10 space-y-6">
        {children}
      </div>
    </main>
  );
}
