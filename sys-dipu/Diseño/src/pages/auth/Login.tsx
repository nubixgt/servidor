import React, { useState } from 'react';
import { Role, Category } from '../../types';

export default function Login({ onLogin }: { onLogin: (role: Role, category?: Category) => void }) {
  const [tecnicoCategory, setTecnicoCategory] = useState<Category>('iniciativas');

  return (
    <div className="bg-surface text-on-surface min-h-screen flex flex-col items-center justify-center p-6 selection:bg-primary-container selection:text-on-primary-container relative overflow-hidden">
      {/* Decorative Element (Architectural Curator) */}
      <div className="absolute top-0 right-0 p-12 pointer-events-none opacity-20 hidden lg:block">
        <div className="w-64 h-64 border-40 border-surface-container-highest rounded-full"></div>
      </div>
      <div className="absolute bottom-0 left-0 p-12 pointer-events-none opacity-20 hidden lg:block">
        <div className="w-32 h-32 bg-surface-container-highest rounded-full"></div>
      </div>

      <main className="w-full max-w-md relative z-10">
        {/* Logo Section */}
        <div className="text-center mb-12">
          <div className="inline-flex items-center justify-center mb-6">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <radialGradient id="r-bg" cx="40" cy="28" r="44" gradientUnits="userSpaceOnUse">
                  <stop offset="0%" stopColor="#1b3a6b"/>
                  <stop offset="100%" stopColor="#060e1c"/>
                </radialGradient>
                <linearGradient id="r-teal" x1="0" y1="0" x2="0" y2="80" gradientUnits="userSpaceOnUse">
                  <stop offset="0%" stopColor="#7dd8f3"/>
                  <stop offset="100%" stopColor="#0891b2"/>
                </linearGradient>
                <linearGradient id="r-gold" x1="0" y1="0" x2="0" y2="80" gradientUnits="userSpaceOnUse">
                  <stop offset="0%" stopColor="#fef08a"/>
                  <stop offset="100%" stopColor="#d97706"/>
                </linearGradient>
                <linearGradient id="r-border" x1="8" y1="4" x2="72" y2="76" gradientUnits="userSpaceOnUse">
                  <stop offset="0%" stopColor="#67e8f9"/>
                  <stop offset="55%" stopColor="#0891b2"/>
                  <stop offset="100%" stopColor="#164e63"/>
                </linearGradient>
                <filter id="r-glow-sm">
                  <feGaussianBlur stdDeviation="1.5" result="b"/>
                  <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="r-glow-md">
                  <feGaussianBlur stdDeviation="2.5" result="b"/>
                  <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <filter id="r-drop">
                  <feDropShadow dx="0" dy="4" stdDeviation="8" floodColor="#000a1f" floodOpacity="0.6"/>
                </filter>
              </defs>
              <circle cx="40" cy="42" r="30" fill="#0ea5e9" opacity="0.05" filter="url(#r-glow-md)"/>
              <polygon points="40,4 73,22 73,58 40,76 7,58 7,22" fill="url(#r-bg)" filter="url(#r-drop)"/>
              <polygon points="40,4 73,22 73,58 40,76 7,58 7,22" fill="none" stroke="url(#r-border)" strokeWidth="1.5"/>
              <polygon points="40,11 67,27 67,53 40,69 13,53 13,27" fill="none" stroke="#38bdf8" strokeWidth="0.5" opacity="0.2"/>
              <rect x="24" y="35" width="5.5" height="20" rx="2.75" fill="url(#r-teal)" filter="url(#r-glow-sm)"/>
              <rect x="50.5" y="35" width="5.5" height="20" rx="2.75" fill="url(#r-teal)" filter="url(#r-glow-sm)"/>
              <rect x="37.25" y="29" width="5.5" height="26" rx="2.75" fill="url(#r-gold)" filter="url(#r-glow-md)"/>
              <polygon points="40,19 58,32 22,32" fill="url(#r-gold)" opacity="0.9" filter="url(#r-glow-sm)"/>
              <line x1="33" y1="19.5" x2="47" y2="19.5" stroke="#7dd8f3" strokeWidth="1" opacity="0.5"/>
              <rect x="20" y="56" width="40" height="3.5" rx="1.75" fill="url(#r-teal)" opacity="0.8" filter="url(#r-glow-sm)"/>
              <rect x="16" y="60.5" width="48" height="2" rx="1" fill="#38bdf8" opacity="0.25"/>
              <path d="M40,1.5 L41.5,3.5 L44,5 L41.5,6.5 L40,8.5 L38.5,6.5 L36,5 L38.5,3.5 Z" fill="url(#r-gold)" filter="url(#r-glow-md)"/>
              <circle cx="73" cy="22" r="1.8" fill="#38bdf8" opacity="0.45"/>
              <circle cx="73" cy="58" r="1.8" fill="#38bdf8" opacity="0.45"/>
              <circle cx="7" cy="22" r="1.8" fill="#38bdf8" opacity="0.45"/>
              <circle cx="7" cy="58" r="1.8" fill="#38bdf8" opacity="0.45"/>
            </svg>
          </div>
          <h1 className="text-3xl font-extrabold text-on-surface tracking-tight mb-2 font-headline">
            Civic Ethos Suite
          </h1>
          <p className="text-on-surface-variant font-medium tracking-wide text-sm uppercase">
            Portal de Gestión Institucional
          </p>
        </div>

        {/* Login Card */}
        <div className="bg-surface-container-lowest p-10 rounded-2xl shadow-[0_12px_40px_rgba(43,52,55,0.06)] border border-outline-variant/10">
          <div className="mb-8">
            <h2 className="text-xl font-bold text-on-surface mb-1 font-headline">Acceso Administrativo</h2>
            <p className="text-on-surface-variant text-sm">Ingrese sus credenciales para continuar.</p>
          </div>
          <form className="space-y-6" onSubmit={(e) => { e.preventDefault(); onLogin('superadmin'); }}>
            {/* Username Field */}
            <div className="space-y-2">
              <label className="block text-xs font-semibold text-on-surface-variant uppercase tracking-widest" htmlFor="username">
                Usuario
              </label>
              <div className="relative group">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline">
                  <span className="material-symbols-outlined text-sm">person</span>
                </div>
                <input
                  id="username"
                  name="username"
                  type="text"
                  required
                  placeholder="nombre.usuario"
                  className="block w-full pl-11 pr-4 py-3.5 bg-surface-container-low border-0 border-b-2 border-transparent focus:border-primary focus:ring-0 rounded-lg text-on-surface transition-all duration-300 placeholder:text-outline-variant/60"
                />
              </div>
            </div>

            {/* Password Field */}
            <div className="space-y-2">
              <div className="flex justify-between items-end">
                <label className="block text-xs font-semibold text-on-surface-variant uppercase tracking-widest" htmlFor="password">
                  Contraseña
                </label>
                <a href="#" className="text-xs font-medium text-primary hover:text-primary-dim transition-colors">¿Olvidó su clave?</a>
              </div>
              <div className="relative group">
                <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline">
                  <span className="material-symbols-outlined text-sm">lock</span>
                </div>
                <input
                  id="password"
                  name="password"
                  type="password"
                  required
                  placeholder="••••••••••••"
                  className="block w-full pl-11 pr-4 py-3.5 bg-surface-container-low border-0 border-b-2 border-transparent focus:border-primary focus:ring-0 rounded-lg text-on-surface transition-all duration-300 placeholder:text-outline-variant/60"
                />
              </div>
            </div>

            {/* Remember Me */}
            <div className="flex items-center space-x-2 px-1">
              <input type="checkbox" id="remember" className="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/20 bg-surface-container" />
              <label htmlFor="remember" className="text-sm text-on-surface-variant cursor-pointer select-none">Recordar mi sesión</label>
            </div>

            {/* Submit Button */}
            <button type="submit" className="w-full py-4 px-6 bg-linear-to-r from-primary to-primary-dim text-white font-bold rounded-xl shadow-md hover:shadow-xl hover:shadow-primary/20 transition-all duration-300 active:scale-[0.98] flex items-center justify-center space-x-2">
              <span>Entrar</span>
              <span className="material-symbols-outlined text-lg">arrow_forward</span>
            </button>
          </form>
        </div>

        {/* Test Buttons */}
        <div className="mt-8 bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/10 shadow-sm">
          <h3 className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-4 text-center">Ingreso Rápido (Pruebas)</h3>
          <div className="grid grid-cols-1 gap-3">
            <button onClick={() => onLogin('administrador')} className="py-3 px-4 bg-primary-container text-on-primary-container text-xs font-bold rounded-xl hover:bg-primary hover:text-white transition-colors flex items-center justify-center gap-2">
              <span className="material-symbols-outlined text-sm">admin_panel_settings</span>
              Ingresar como Administrador (Diputado)
            </button>
            <div className="flex gap-2">
              <button onClick={() => onLogin('tecnico', tecnicoCategory)} className="flex-1 py-3 px-4 bg-secondary-container text-on-secondary-container text-xs font-bold rounded-xl hover:bg-secondary hover:text-white transition-colors flex items-center justify-center gap-2">
                <span className="material-symbols-outlined text-sm">manage_accounts</span>
                Ingresar como Técnico
              </button>
              <select 
                value={tecnicoCategory} 
                onChange={(e) => setTecnicoCategory(e.target.value as Category)}
                className="bg-surface-container-low border-none rounded-xl text-xs font-bold text-on-surface px-3 focus:ring-2 focus:ring-primary/20"
              >
                <option value="iniciativas">Iniciativas</option>
                <option value="citaciones">Citaciones</option>
                <option value="comisiones">Comisiones</option>
                <option value="fiscalizacion">Fiscalización</option>
                <option value="compromisos">Compromisos</option>
                <option value="actividades">Actividades</option>
                <option value="redes">Redes</option>
                <option value="afiliaciones">Afiliaciones</option>
              </select>
            </div>
          </div>
        </div>

        {/* Footer / Support */}
        <div className="mt-10 text-center space-y-4">
          <p className="text-on-surface-variant text-xs font-medium">
            © 2024 Civic Ethos Suite. Sistema de Gestión Pública.
          </p>
          <div className="flex items-center justify-center space-x-6">
            <a href="#" className="text-xs text-outline hover:text-on-surface transition-colors">Soporte Técnico</a>
            <span className="w-1 h-1 bg-outline-variant rounded-full"></span>
            <a href="#" className="text-xs text-outline hover:text-on-surface transition-colors">Seguridad</a>
            <span className="w-1 h-1 bg-outline-variant rounded-full"></span>
            <a href="#" className="text-xs text-outline hover:text-on-surface transition-colors">Privacidad</a>
          </div>
        </div>
      </main>
    </div>
  );
}
