import React, { useState } from 'react';
import { Sprout, Lock, Mail, Eye, EyeOff, ArrowRight, ShieldCheck, CheckCircle2 } from 'lucide-react';
import { UserProfile } from '../types';
import { INITIAL_USERS } from '../data/initialData';

interface LoginViewProps {
  onLogin: (user: UserProfile) => void;
}

export const LoginView: React.FC<LoginViewProps> = ({ onLogin }) => {
  const [email, setEmail] = useState('a.martinez@keylinegt.com');
  const [password, setPassword] = useState('••••••••••••');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [showForgotModal, setShowForgotModal] = useState(false);
  const [forgotEmail, setForgotEmail] = useState('');
  const [forgotSuccess, setForgotSuccess] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setTimeout(() => {
      const found = INITIAL_USERS.find(u => u.email.toLowerCase() === email.toLowerCase()) || INITIAL_USERS[1];
      setIsLoading(false);
      onLogin(found);
    }, 400);
  };

  const handleQuickLogin = (user: UserProfile) => {
    setEmail(user.email);
    setIsLoading(true);
    setTimeout(() => {
      setIsLoading(false);
      onLogin(user);
    }, 300);
  };

  const handleForgotSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setForgotSuccess(true);
    setTimeout(() => {
      setForgotSuccess(false);
      setShowForgotModal(false);
    }, 1500);
  };

  return (
    <div className="min-h-screen w-full relative flex items-center justify-center p-4 bg-[#071510] font-[Arial,Helvetica,sans-serif]">
      {/* Main Dark Login Card */}
      <div className="relative z-10 w-full max-w-md bg-[#0c1e17] rounded-3xl p-8 sm:p-10 border border-[#17382b] shadow-2xl animate-fadeIn">
        {/* Brand Icon & Heading */}
        <div className="text-center mb-8">
          <div className="w-16 h-16 rounded-2xl bg-[#153e2d] border border-[#22c55e]/40 flex items-center justify-center mx-auto mb-3 shadow-[0_0_20px_rgba(34,197,94,0.25)]">
            <Sprout className="w-9 h-9 text-[#22c55e]" />
          </div>
          <h1 className="text-2xl font-bold text-white tracking-tight">
            Keyline<span className="text-[#22c55e]">GT</span>
          </h1>
          <p className="text-xs text-[#22c55e] font-semibold tracking-wide uppercase mt-0.5">Avance Nacional de Parcelas</p>
          <p className="text-xs text-[#94a3b8] mt-2">
            Plataforma geoespacial de diseño hidrológico y conservación de suelos
          </p>
        </div>

        {/* Login Form */}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="text-xs font-medium text-[#cbd5e1] block mb-1.5">Usuario o Correo Institucional</label>
            <div className="relative">
              <Mail className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#64748b]" />
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="a.martinez@keylinegt.com"
                className="w-full bg-[#081611] border border-[#17382b] focus:border-[#22c55e]/60 rounded-xl py-2.5 pl-10 pr-4 text-xs text-white placeholder:text-[#64748b] focus:outline-none transition-colors"
              />
            </div>
          </div>

          <div>
            <div className="flex justify-between items-center mb-1.5">
              <label className="text-xs font-medium text-[#cbd5e1]">Contraseña de Acceso</label>
              <button
                type="button"
                onClick={() => setShowForgotModal(true)}
                className="text-[11px] text-[#22c55e] hover:underline"
              >
                ¿Olvidaste tu clave?
              </button>
            </div>
            <div className="relative">
              <Lock className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#64748b]" />
              <input
                type={showPassword ? 'text' : 'password'}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••••••"
                className="w-full bg-[#081611] border border-[#17382b] focus:border-[#22c55e]/60 rounded-xl py-2.5 pl-10 pr-10 text-xs text-white placeholder:text-[#64748b] focus:outline-none transition-colors"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-[#64748b] hover:text-white"
              >
                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
              </button>
            </div>
          </div>

          <div className="flex items-center justify-between text-xs text-[#94a3b8] pt-1">
            <label className="flex items-center space-x-2 cursor-pointer select-none">
              <input
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                className="rounded border-[#17382b] text-[#22c55e] focus:ring-0 bg-[#081611]"
              />
              <span>Recordar sesión</span>
            </label>
            <span className="text-[11px] text-[#64748b]">v2.4 - Guatemala</span>
          </div>

          <button
            type="submit"
            disabled={isLoading}
            className="w-full py-3 bg-[#22c55e] hover:bg-[#16a34a] text-black font-bold text-xs rounded-xl shadow-lg shadow-[#22c55e]/20 transition-all flex items-center justify-center gap-2 mt-2"
          >
            {isLoading ? (
              <div className="w-4 h-4 border-2 border-black border-t-transparent rounded-full animate-spin" />
            ) : (
              <>
                <span>Ingresar al Sistema</span>
                <ArrowRight className="w-4 h-4" />
              </>
            )}
          </button>
        </form>

        {/* Demo Fast Logins for Testing Roles */}
        <div className="mt-8 pt-6 border-t border-[#17382b]">
          <p className="text-[11px] text-[#94a3b8] text-center mb-3">
            Acceso Rápido para Demostración:
          </p>
          <div className="grid grid-cols-3 gap-2">
            {INITIAL_USERS.map((usr) => (
              <button
                key={usr.id}
                type="button"
                onClick={() => handleQuickLogin(usr)}
                className="p-2 rounded-xl bg-[#081611] hover:bg-[#133225] border border-[#17382b] hover:border-[#22c55e]/40 transition-all text-center group"
              >
                <span className="text-[11px] font-bold text-white block group-hover:text-[#22c55e] transition-colors truncate">
                  {usr.name.split(' ')[0]}
                </span>
                <span className="text-[9px] text-[#64748b] block">{usr.role}</span>
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Forgot Password Modal */}
      {showForgotModal && (
        <div className="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4 animate-fadeIn">
          <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl max-w-sm w-full p-6 text-center">
            <ShieldCheck className="w-10 h-10 text-[#22c55e] mx-auto mb-3" />
            <h3 className="text-base font-bold text-white mb-1">Recuperación de Acceso</h3>
            <p className="text-xs text-[#94a3b8] mb-4">
              Ingresa tu correo institucional para enviarte un enlace de restablecimiento.
            </p>

            {forgotSuccess ? (
              <div className="p-3 bg-[#22c55e]/15 border border-[#22c55e]/30 rounded-xl text-xs text-[#22c55e] flex items-center justify-center gap-2 mb-4">
                <CheckCircle2 className="w-4 h-4" />
                <span>Enlace enviado a tu bandeja de entrada</span>
              </div>
            ) : (
              <form onSubmit={handleForgotSubmit} className="space-y-3 mb-4">
                <input
                  type="email"
                  required
                  value={forgotEmail}
                  onChange={(e) => setForgotEmail(e.target.value)}
                  placeholder="tu.correo@keylinegt.com"
                  className="w-full bg-[#081611] border border-[#17382b] rounded-xl py-2 px-3 text-xs text-white placeholder:text-[#64748b] focus:outline-none focus:border-[#22c55e]"
                />
                <button
                  type="submit"
                  className="w-full py-2 bg-[#22c55e] hover:bg-[#16a34a] text-black font-bold text-xs rounded-xl transition-all"
                >
                  Enviar enlace
                </button>
              </form>
            )}

            <button
              onClick={() => setShowForgotModal(false)}
              className="text-xs text-[#94a3b8] hover:text-white"
            >
              Regresar al inicio de sesión
            </button>
          </div>
        </div>
      )}
    </div>
  );
};
