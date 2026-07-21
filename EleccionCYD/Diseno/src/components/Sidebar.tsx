import { Screen } from '../types';
import { Grid, Gavel, BarChart2, Settings, LogOut } from 'lucide-react';

interface SidebarProps {
  currentScreen: Screen;
  onNavigate: (screen: Screen) => void;
  onLogout: () => void;
  onSubmitScores?: () => void;
}

export default function Sidebar({ currentScreen, onNavigate, onLogout, onSubmitScores }: SidebarProps) {
  return (
    <aside className="hidden lg:flex flex-col py-8 gap-8 h-[calc(100vh-80px)] w-64 bg-white border-r border-gray-200 sticky top-20 select-none shrink-0 overflow-y-auto">
      {/* Judge Profile Header */}
      <div className="px-6 flex items-center gap-3">
        <div className="w-10 h-10 rounded-full bg-neutral-100 overflow-hidden border border-gray-100">
          <img 
            className="w-full h-full object-cover" 
            alt="Official Judge portrait" 
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAwGbxslq_rbCzMPL3FlMpsyeZ_SFYvR5KNwEy9riyV-1mGz0OVEVv6hN_ct2oDFSj3xKcP1kVXlllLXZfiFK54JXU5HvokGBZ1w-ZGfHZm3gzo4P4XvqHiY2VUD7AVTOBYok9BIi3papekiMf4Q-xx46mtjSOJaBVNGAm7TxrtSzEUv2WibH4tB--cyvqi0xAXMHmRhHMovdLoK38w-usJzeR8Bux85fymdttJbrG1XIcmmtLYZSyz"
          />
        </div>
        <div>
          <p className="text-[11px] font-semibold tracking-wider text-black uppercase">Official Judge</p>
          <p className="text-xs text-gray-500">Paris Collection 2024</p>
        </div>
      </div>

      {/* Nav Menu */}
      <nav className="flex flex-col gap-1 flex-grow">
        <button 
          onClick={() => onNavigate('directory')}
          className={`flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200 ${
            currentScreen === 'directory'
              ? 'text-black font-semibold bg-gray-50 border-l-2 border-black'
              : 'text-gray-400 hover:text-black hover:bg-gray-50/50'
          }`}
        >
          <Grid size={18} strokeWidth={1.5} />
          <span className="text-xs uppercase tracking-widest font-semibold">Models</span>
        </button>

        <button 
          onClick={() => onNavigate('judging')}
          className={`flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200 ${
            currentScreen === 'judging'
              ? 'text-black font-semibold bg-gray-50 border-l-2 border-black'
              : 'text-gray-400 hover:text-black hover:bg-gray-50/50'
          }`}
        >
          <Gavel size={18} strokeWidth={1.5} />
          <span className="text-xs uppercase tracking-widest font-semibold">Judging</span>
        </button>

        <button 
          onClick={() => onNavigate('leaderboard')}
          className={`flex items-center gap-4 pl-6 py-3 cursor-pointer text-left transition-all duration-200 ${
            currentScreen === 'leaderboard'
              ? 'text-black font-semibold bg-gray-50 border-l-2 border-black'
              : 'text-gray-400 hover:text-black hover:bg-gray-50/50'
          }`}
        >
          <BarChart2 size={18} strokeWidth={1.5} />
          <span className="text-xs uppercase tracking-widest font-semibold">Results</span>
        </button>
      </nav>

      {/* Bottom Actions */}
      <div className="px-4 mt-auto border-t border-gray-100 pt-6 flex flex-col gap-4">
        <button 
          onClick={() => alert('Judiciary environment settings loaded.')}
          className="flex items-center gap-4 text-gray-400 hover:text-black transition-colors pl-4 text-left cursor-pointer"
        >
          <Settings size={16} strokeWidth={1.5} />
          <span className="text-[10px] uppercase tracking-widest font-semibold">Settings</span>
        </button>

        <button 
          onClick={onLogout}
          className="flex items-center gap-4 text-gray-400 hover:text-black transition-colors pl-4 text-left cursor-pointer"
        >
          <LogOut size={16} strokeWidth={1.5} />
          <span className="text-[10px] uppercase tracking-widest font-semibold">Logout</span>
        </button>

        {onSubmitScores && (
          <button 
            onClick={onSubmitScores}
            className="mt-4 w-full bg-black text-white py-4 text-[10px] font-semibold tracking-widest uppercase hover:bg-neutral-800 transition-all duration-200 rounded-none cursor-pointer"
          >
            Submit Final Scores
          </button>
        )}
      </div>
    </aside>
  );
}
