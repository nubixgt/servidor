import { Screen } from '../types';
import { User, Menu } from 'lucide-react';

interface NavbarProps {
  currentScreen: Screen;
  onNavigate: (screen: Screen) => void;
  onLogout: () => void;
  judgeName: string;
}

export default function Navbar({ currentScreen, onNavigate, onLogout, judgeName }: NavbarProps) {
  return (
    <header className="bg-[#f9f9f9] border-b border-black sticky top-0 z-50 select-none">
      <div className="flex justify-between items-center w-full px-6 md:px-16 h-20 max-w-[1440px] mx-auto">
        {/* Logo */}
        <button 
          onClick={() => onNavigate('directory')} 
          className="font-serif text-lg md:text-xl tracking-[0.25em] text-black uppercase cursor-pointer text-left font-normal"
        >
          AURA FASHION WEEK
        </button>

        {/* Center Navigation Links */}
        <nav className="hidden md:flex items-center gap-12 h-full">
          <button
            onClick={() => onNavigate('directory')}
            className={`font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300 ${
              currentScreen === 'directory'
                ? 'text-black border-b border-black'
                : 'text-gray-400 hover:text-black'
            }`}
          >
            Directory
          </button>
          <button
            onClick={() => onNavigate('judging')}
            className={`font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300 ${
              currentScreen === 'judging'
                ? 'text-black border-b border-black'
                : 'text-gray-400 hover:text-black'
            }`}
          >
            Live Judging
          </button>
          <button
            onClick={() => onNavigate('leaderboard')}
            className={`font-semibold text-xs uppercase tracking-[0.15em] pb-1 cursor-pointer transition-all duration-300 ${
              currentScreen === 'leaderboard'
                ? 'text-black border-b border-black'
                : 'text-gray-400 hover:text-black'
            }`}
          >
            Leaderboard
          </button>
        </nav>

        {/* User Actions */}
        <div className="flex items-center gap-4">
          <div className="hidden sm:flex flex-col items-end text-right">
            <span className="text-[10px] font-semibold tracking-wider text-black">{judgeName}</span>
            <button 
              onClick={onLogout} 
              className="text-[9px] uppercase tracking-widest text-gray-400 hover:text-black transition-colors"
            >
              Sign out
            </button>
          </div>
          <button 
            className="text-black hover:opacity-80 transition-opacity p-1 cursor-pointer"
            onClick={() => onNavigate('leaderboard')}
            title="Profile / Results"
          >
            <User size={22} strokeWidth={1.5} />
          </button>
          <button className="md:hidden text-black p-1">
            <Menu size={22} strokeWidth={1.5} />
          </button>
        </div>
      </div>
    </header>
  );
}
