import React from 'react';
import { LayoutDashboard, FileSpreadsheet, BookOpen } from 'lucide-react';

interface SidebarProps {
  activeTab: 'dashboard' | 'registros' | 'nuevo-registro' | 'ajustes' | 'catalogo';
  setActiveTab: (tab: 'dashboard' | 'registros' | 'nuevo-registro' | 'ajustes' | 'catalogo') => void;
}

export default function Sidebar({ activeTab, setActiveTab }: SidebarProps) {
  return (
    <aside className="fixed left-0 top-0 bottom-0 flex flex-col p-6 z-40 bg-white/60 backdrop-blur-[20px] border-r border-slate-200/60 w-64 hidden md:flex transition-all duration-300">
      <div className="mb-10 flex items-center gap-3">
        <div className="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#3455b9] to-[#506ed3] flex items-center justify-center text-white shadow-md overflow-hidden transition-transform hover:rotate-12 duration-300">
          <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
          </svg>
        </div>
        <div>
          <h1 className="text-xl font-extrabold text-[#3455b9] leading-tight tracking-tight">VaxPoultry</h1>
          <p className="text-[10px] text-slate-500 uppercase tracking-widest font-extrabold">Clínica Avícola</p>
        </div>
      </div>

      <nav className="flex-1 space-y-2">
        <button
          onClick={() => setActiveTab('dashboard')}
          className={`w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer ${
            activeTab === 'dashboard'
              ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40'
              : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'
          }`}
        >
          <LayoutDashboard className="w-5 h-5" />
          <span className="text-sm">Dashboard</span>
        </button>

        <button
          onClick={() => setActiveTab('registros')}
          className={`w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer ${
            activeTab === 'registros'
              ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40'
              : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'
          }`}
        >
          <FileSpreadsheet className="w-5 h-5" />
          <span className="text-sm">Historial</span>
        </button>

        <button
          onClick={() => setActiveTab('nuevo-registro')}
          className={`w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer ${
            activeTab === 'nuevo-registro'
              ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40'
              : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'
          }`}
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
          </svg>
          <span className="text-sm">Nuevo Registro</span>
        </button>

        <button
          onClick={() => setActiveTab('catalogo')}
          className={`w-full flex items-center gap-4 px-4 py-3 rounded-2xl font-bold transition-all duration-200 text-left cursor-pointer ${
            activeTab === 'catalogo'
              ? 'text-[#3455b9] bg-white/60 shadow-sm translate-x-1 border border-white/40'
              : 'text-gray-700 hover:text-[#3455b9] hover:bg-white/30'
          }`}
        >
          <BookOpen className="w-5 h-5" />
          <span className="text-sm">Catálogo</span>
        </button>
      </nav>
    </aside>
  );
}
