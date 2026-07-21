import React, { useState } from 'react';
import { Lock } from 'lucide-react';
import { motion } from 'motion/react';

interface LoginScreenProps {
  onLogin: (judgeId: string, accessCode: string) => void;
}

export default function LoginScreen({ onLogin }: LoginScreenProps) {
  const [judgeId, setJudgeId] = useState('JUDGE-04');
  const [accessCode, setAccessCode] = useState('PARIS2024');
  const [isAuthenticating, setIsAuthenticating] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!judgeId.trim() || !accessCode.trim()) {
      setError('Please fill in all fields.');
      return;
    }
    setError('');
    setIsAuthenticating(true);

    setTimeout(() => {
      setIsAuthenticating(false);
      onLogin(judgeId, accessCode);
    }, 1200);
  };

  return (
    <div className="relative min-h-screen w-full flex flex-col items-center justify-center overflow-hidden font-sans select-none selection:bg-black selection:text-white">
      {/* Background Wrapper */}
      <div className="absolute inset-0 w-full h-full z-0">
        <div 
          className="w-full h-full bg-cover bg-center brightness-90 scale-105"
          style={{ 
            backgroundImage: `url('https://lh3.googleusercontent.com/aida-public/AB6AXuCDJeQAA-guOjP4reFjlb6XiXKFZxhzk6rU-8FB2X1PBiO7WqVil1cB07rqW0k_ybTgsNfvsg8q1kPvL6FhTt41XVGj0tqVjd8sI5fSrE91oBrLI2ePtoo-rgD2UWF18V_Nt6STP5WZwiymABkEA3jbOYUCGm9Z_wFW9L40P05_B62HlnPhx5Zm2cLRis-kbXCTF9t3afjAIKGoAi3Qkit4FqqC-aixGiXPDnusQ3uznkYzDx3MSua9')` 
          }}
        />
        <div className="absolute inset-0 bg-white/40 backdrop-blur-[2px]" />
      </div>

      {/* Main Content */}
      <main className="relative z-10 flex flex-col items-center justify-center px-6 md:px-16 py-12 w-full max-w-lg">
        {/* Header Branding */}
        <motion.div 
          initial={{ opacity: 0, y: 10 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.1 }}
          className="mb-12 text-center"
        >
          <h1 className="font-serif text-3xl md:text-4xl tracking-[0.25em] text-black font-normal uppercase">
            AURA FASHION WEEK
          </h1>
          <p className="text-xs text-gray-500 mt-2 tracking-[0.3em] font-semibold uppercase">
            JUDICIARY PORTAL
          </p>
        </motion.div>

        {/* Central Login Card */}
        <motion.div 
          initial={{ opacity: 0, y: 15 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.8, delay: 0.3 }}
          className="w-full bg-white border border-black/5 p-8 md:p-12 shadow-[0_0_50px_rgba(0,0,0,0.04)] rounded-none"
        >
          <header className="mb-10">
            <h2 className="text-3xl text-black font-light tracking-tight mb-2">Login</h2>
            <p className="text-gray-500 text-sm">Please authenticate to access the judging dashboard.</p>
          </header>

          <form onSubmit={handleSubmit} className="space-y-8">
            {/* Judge ID Field */}
            <div className="relative group">
              <label 
                htmlFor="judge_id" 
                className="text-[10px] text-gray-400 font-semibold uppercase tracking-[0.15em] mb-1 block group-focus-within:text-black group-focus-within:tracking-[0.2em] transition-all duration-300"
              >
                Judge ID
              </label>
              <input 
                id="judge_id"
                name="judge_id"
                type="text"
                placeholder="Enter identification number"
                value={judgeId}
                onChange={(e) => setJudgeId(e.target.value)}
                required
                className="w-full bg-transparent border-t-0 border-x-0 border-b border-gray-300 py-3 px-0 text-sm text-black focus:ring-0 focus:border-black transition-all duration-300 rounded-none placeholder:text-gray-300"
              />
            </div>

            {/* Access Code Field */}
            <div className="relative group">
              <div className="flex justify-between items-end mb-1">
                <label 
                  htmlFor="access_code" 
                  className="text-[10px] text-gray-400 font-semibold uppercase tracking-[0.15em] group-focus-within:text-black group-focus-within:tracking-[0.2em] transition-all duration-300"
                >
                  Access Code
                </label>
                <button 
                  type="button"
                  onClick={() => alert('Access code has been sent to your official device. (Default is PARIS2024)')}
                  className="text-[9px] text-gray-400 hover:text-black transition-colors tracking-wider uppercase"
                >
                  Forgot Code?
                </button>
              </div>
              <input 
                id="access_code"
                name="access_code"
                type="password"
                placeholder="••••••••"
                value={accessCode}
                onChange={(e) => setAccessCode(e.target.value)}
                required
                className="w-full bg-transparent border-t-0 border-x-0 border-b border-gray-300 py-3 px-0 text-sm text-black focus:ring-0 focus:border-black transition-all duration-300 rounded-none placeholder:text-gray-300"
              />
            </div>

            {error && (
              <p className="text-red-600 text-xs tracking-wide">{error}</p>
            )}

            {/* Action Button */}
            <div className="pt-4">
              <button 
                type="submit"
                disabled={isAuthenticating}
                className="w-full bg-black text-white py-4 text-xs font-semibold tracking-[0.25em] uppercase hover:bg-neutral-800 active:scale-[0.99] transition-all duration-300 rounded-none cursor-pointer disabled:opacity-75"
              >
                {isAuthenticating ? 'Authenticating...' : 'Sign In'}
              </button>
            </div>
          </form>

          <footer className="mt-12 text-center">
            <div className="flex items-center justify-center gap-2 text-gray-400">
              <Lock size={13} className="opacity-80" />
              <span className="text-[10px] uppercase tracking-[0.2em]">Secure Judging Environment</span>
            </div>
          </footer>
        </motion.div>

        {/* Footer Decoration */}
        <motion.div 
          initial={{ opacity: 0 }}
          animate={{ opacity: 0.6 }}
          transition={{ duration: 1, delay: 0.5 }}
          className="mt-12 flex items-center gap-4"
        >
          <span className="w-8 h-[1px] bg-gray-300" />
          <span className="text-[10px] text-gray-500 uppercase tracking-[0.25em]">Paris Collection 2024</span>
          <span className="w-8 h-[1px] bg-gray-300" />
        </motion.div>
      </main>

      {/* Subtle Grain Overlay for Texture */}
      <div className="fixed inset-0 pointer-events-none opacity-[0.025] z-50 bg-[url('https://grainy-gradients.vercel.app/noise.svg')]" />
    </div>
  );
}
