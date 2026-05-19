import { Search, Bell, Settings } from "lucide-react";

interface TopBarProps {
  title: string;
}

export default function TopBar({ title }: TopBarProps) {
  return (
    <header className="md:ml-[280px] h-20 sticky top-0 glass-topbar z-40 px-10 flex items-center justify-between">
      <div className="flex items-center gap-4">
        <h2 className="text-xl font-bold text-white tracking-tight">{title}</h2>
      </div>

      <div className="flex items-center gap-6 flex-grow max-w-xl mx-8">
        <div className="relative w-full">
          <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-white/40" />
          <input
            type="text"
            placeholder="Buscar proyectos, personal o datos..."
            className="w-full glass-input rounded-xl pl-11 pr-6 py-2.5 text-sm text-white focus:ring-2 focus:ring-primary/40 transition-all outline-none placeholder:text-white/20"
          />
        </div>
      </div>

      <div className="flex items-center gap-4">
        <button className="relative p-2.5 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-all cursor-pointer">
          <Bell className="w-5 h-5" />
          <span className="absolute top-2.5 right-2.5 w-2 h-2 bg-primary rounded-full border-2 border-black/20 shadow-[0_0_8px_#6366f1]"></span>
        </button>
        <button className="p-2.5 text-white/60 hover:text-white hover:bg-white/10 rounded-xl transition-all cursor-pointer">
          <Settings className="w-5 h-5" />
        </button>
        <div className="h-10 w-10 rounded-xl overflow-hidden border border-white/20 ml-2 shadow-lg">
          <img
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuArgdIOMUDf0C6kThHSW5VFABlcng177gJWGlDxkHuy8QmzbeWMS3kea5am-7C9uVfDhZAVPr_arrbRG2cWgE5E3Cuu8uB39zOFboMr1rzeCViTnoLCWBnhbjUOd_XKG24UbvAsD3Qk5Fz2Aaic7R-IM8YtHUliJgm5p0CftIpqvMbhTLNULlHzhk78AmxsWUmBH5Sxi4dTWeRFvGeSi2_FA1YK_rieEU5YHV0ecxUq8gPXqeBrVIir6mDTvjaHcv_WegQtRSMWmPQ"
            alt="User Profile"
            className="w-full h-full object-cover"
          />
        </div>
      </div>
    </header>
  );
}
