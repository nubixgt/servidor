import React, { useState } from 'react';

interface LoginScreenProps {
  onLoginSuccess: (athleteName: string) => void;
}

export const LoginScreen: React.FC<LoginScreenProps> = ({ onLoginSuccess }) => {
  const [username, setUsername] = useState('alex.triathlete');
  const [password, setPassword] = useState('••••••••••••');
  const [errorMsg, setErrorMsg] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (username.trim()) {
      const cleanName = username.includes('.') ? username.split('.')[0] : username;
      const formatted = cleanName.charAt(0).toUpperCase() + cleanName.slice(1);
      onLoginSuccess(formatted || 'Alex');
    } else {
      setErrorMsg('Por favor introduce tu ID o correo de atleta.');
    }
  };

  return (
    <div
      id="login-screen"
      className="min-h-screen w-full flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden select-none bg-[#07080a]"
    >
      {/* Cinematic Sports Background */}
      <div className="absolute inset-0 pointer-events-none z-0 overflow-hidden select-none">
        <img
          src="https://images.unsplash.com/photo-1517649763962-0c623266ddc0?auto=format&fit=crop&w=2560&q=85"
          alt="Cyclist"
          referrerPolicy="no-referrer"
          className="absolute inset-0 w-full h-full object-cover object-right md:object-center opacity-45 scale-105"
        />
        <img
          src="https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=2000&q=85"
          alt="Swimmer"
          referrerPolicy="no-referrer"
          className="absolute inset-y-0 left-0 w-full md:w-2/3 h-full object-cover object-left opacity-25 mix-blend-screen"
        />
        <div className="absolute inset-0 bg-gradient-to-r from-[#07080a]/85 via-[#07080a]/65 to-[#07080a]/50" />
        <div className="absolute inset-0 bg-gradient-to-t from-[#07080a]/90 via-transparent to-[#07080a]/40" />
        <div className="absolute top-1/4 -left-32 w-96 h-96 bg-[#caf300]/10 rounded-full blur-[120px]" />
      </div>

      {/* Main Container */}
      <div className="w-full max-w-md relative z-10 space-y-6">
        {/* Brand Header */}
        <div className="text-center space-y-3">
          <div className="w-16 h-16 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/50 mx-auto flex items-center justify-center text-[#caf300] shadow-[0_0_30px_rgba(202,243,0,0.35)]">
            <span className="material-symbols-outlined text-[36px]">bolt</span>
          </div>

          <div>
            <h1 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
              Power Coach <span className="text-[#caf300]">AI</span>
            </h1>
            <p className="text-xs sm:text-sm font-bold text-[#c5c9ac] tracking-widest uppercase mt-1">
              ELITE PERFORMANCE LAB • ACCESO ATLETA
            </p>
          </div>
        </div>

        {/* Central Glass Card */}
        <div
          id="login-main-card"
          className="glass-card rounded-3xl p-6 sm:p-8 space-y-6 shadow-2xl border border-white/10"
        >
          {errorMsg && (
            <div className="p-3 bg-[#ffb4ab]/10 border border-[#ffb4ab]/30 rounded-xl flex items-center gap-2 text-[#ffb4ab] text-xs">
              <span className="material-symbols-outlined text-base">error</span>
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Form */}
          <form onSubmit={handleSubmit} className="space-y-4 font-sans">
            <div>
              <label className="block text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-2">
                Atleta ID / Email
              </label>
              <div className="relative">
                <span className="material-symbols-outlined absolute left-3.5 top-3.5 text-[#8f9378] text-[20px]">
                  person
                </span>
                <input
                  id="login-username-input"
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full bg-[#17191e]/80 border border-white/10 focus:border-[#caf300] rounded-xl pl-11 pr-4 py-3 text-white text-sm focus:outline-none transition-colors font-mono-data"
                  placeholder="alex.triathlete@garmin.com"
                  required
                />
              </div>
            </div>

            <div>
              <label className="block text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-2">
                Contraseña
              </label>
              <div className="relative">
                <span className="material-symbols-outlined absolute left-3.5 top-3.5 text-[#8f9378] text-[20px]">
                  lock
                </span>
                <input
                  id="login-password-input"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full bg-[#17191e]/80 border border-white/10 focus:border-[#caf300] rounded-xl pl-11 pr-4 py-3 text-white text-sm focus:outline-none transition-colors"
                  placeholder="••••••••••••"
                  required
                />
              </div>
            </div>

            <button
              id="btn-login-submit"
              type="submit"
              className="w-full py-3.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl font-black text-xs sm:text-sm uppercase tracking-wider transition-all shadow-[0_0_25px_rgba(202,243,0,0.35)] active:scale-95 flex items-center justify-center gap-2 mt-2"
            >
              <span>Entrar al Laboratorio</span>
              <span className="material-symbols-outlined text-[18px]">arrow_forward</span>
            </button>
          </form>

          {/* Footer Contact */}
          <div className="text-center pt-3 border-t border-white/10">
            <a
              href="mailto:coach@powercoach.ai?subject=Solicitud%20de%20acceso%20Power%20Coach%20AI"
              className="text-xs text-[#8f9378] hover:text-[#caf300] transition-colors underline underline-offset-4 font-medium"
            >
              ¿Necesitas credenciales? Solicita acceso al entrenador
            </a>
          </div>
        </div>
      </div>
    </div>
  );
};
