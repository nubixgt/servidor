import React, { useState } from "react";
import { User, Lock, ArrowRight, Shield, Loader2 } from "lucide-react";
import { motion } from "motion/react";
import { UserRole } from "../types";

interface LoginProps {
  onLoginSuccess: (username: string, role: UserRole, fullName: string) => void;
}

export default function Login({ onLoginSuccess }: LoginProps) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState("");
  const [rememberMe, setRememberMe] = useState(true);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMessage("");

    if (!username.trim() || !password.trim()) {
      setErrorMessage("Por favor, complete todos los campos.");
      return;
    }

    setIsLoading(true);

    // Simulate reliable industrial authentication
    setTimeout(() => {
      setIsLoading(false);
      const userLower = username.toLowerCase().trim();
      
      if (userLower === "admin" && password === "admin123") {
        onLoginSuccess("admin", "admin", "Administrador Principal Cooitzá");
      } else if ((userLower === "tecnico" || userLower === "tecnico_piloto" || userLower === "piloto") && password === "tecnico123") {
        onLoginSuccess("tecnico", "tecnico_piloto", "Robert Andersson (Técnico Piloto)");
      } else if ((userLower === "tecnico_dashboard" || userLower === "dashboard") && password === "tecnico123") {
        onLoginSuccess("tecnico_dashboard", "tecnico_dashboard", "Elena Rodriguez (Técnico Analista)");
      } else if (password === "123") {
        // Simple universal bypass for streamlined testing by the user
        let determinedRole: UserRole = "tecnico_piloto";
        if (userLower.includes("admin")) {
          determinedRole = "admin";
        } else if (userLower.includes("dash") || userLower.includes("board")) {
          determinedRole = "tecnico_dashboard";
        }
        const fullName = determinedRole === "admin" ? "Admin Especial" : `${username} (Operador Técnico)`;
        onLoginSuccess(username, determinedRole, fullName);
      } else {
        setErrorMessage("Credenciales incorrectas. Pruebe 'admin', 'tecnico_dashboard', o 'tecnico' con contraseña 'tecnico123'.");
      }
    }, 1000);
  };

  return (
    <motion.div 
      initial={{ opacity: 0, y: 15 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
      className="w-full max-w-[420px] bg-white border border-[#cbd5e1] p-8 shadow-sm flex flex-col gap-6"
    >
      <header className="text-center flex flex-col items-center">
        {/* Minimalist custom-drawn Cooitzá logo inside the card */}
        <div className="relative w-24 h-24 mb-3">
          <div className="absolute inset-0 bg-[#FFD200] rounded-full" />
          <div className="absolute inset-0 flex items-center justify-center">
            <span className="font-display text-3xl font-black text-[#0054A3] tracking-tighter italic">
              C
            </span>
          </div>
        </div>
        <h1 className="font-display text-2xl font-bold text-[#0054A3] tracking-tight">
          Cooitzá Control
        </h1>
        <p className="font-mono-label text-xs text-on-surface-variant font-semibold tracking-wider mt-1 uppercase">
          Sistemas de Control Industrial
        </p>
      </header>

      {errorMessage && (
        <div className="bg-red-50 border-l-4 border-red-500 p-3 text-xs text-red-700 font-medium font-sans">
          {errorMessage}
        </div>
      )}

      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <div className="flex flex-col gap-1">
          <label className="text-on-surface-variant block text-xs font-bold uppercase tracking-wider" htmlFor="username">
            Usuario
          </label>
          <div className="relative">
            <User className="absolute left-3 top-1/2 -translate-y-1/2 text-[#0054A3] w-5 h-5 pointer-events-none" />
            <input
              id="username"
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              className="w-full pl-10 pr-3 py-2.5 border border-[#cbd5e1] focus:border-[#0054A3] outline-none font-sans text-sm focus:ring-1 focus:ring-[#FFD200]"
              placeholder="Ej: tecnico o admin"
              disabled={isLoading}
            />
          </div>
        </div>

        <div className="flex flex-col gap-1">
          <div className="flex justify-between items-end">
            <label className="text-on-surface-variant block text-xs font-bold uppercase tracking-wider" htmlFor="password">
              Contraseña
            </label>
            <span className="font-display text-[10px] text-[#0054A3] hover:underline cursor-pointer">
              ¿Olvidaste tu clave?
            </span>
          </div>
          <div className="relative">
            <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-[#0054A3] w-5 h-5 pointer-events-none" />
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full pl-10 pr-3 py-2.5 border border-[#cbd5e1] focus:border-[#0054A3] outline-none font-sans text-sm focus:ring-1 focus:ring-[#FFD200]"
              placeholder="••••••••"
              disabled={isLoading}
            />
          </div>
        </div>

        <div className="flex items-center gap-2 py-1">
          <input
            id="remember"
            type="checkbox"
            checked={rememberMe}
            onChange={(e) => setRememberMe(e.target.checked)}
            className="w-4 h-4 rounded-sm border-[#cbd5e1] text-[#0054A3] focus:ring-0 cursor-pointer"
          />
          <label className="font-display text-xs text-on-surface-variant cursor-pointer select-none font-medium" htmlFor="remember">
            Mantener sesión iniciada
          </label>
        </div>

        <button
          type="submit"
          disabled={isLoading}
          className="w-full bg-[#0054A3] hover:bg-[#004586] disabled:bg-[#cbd5e1] text-white py-3 font-display text-xs font-bold uppercase tracking-widest flex items-center justify-center gap-2 cursor-pointer transition-all active:scale-[0.98]"
        >
          {isLoading ? (
            <>
              <Loader2 className="w-4 h-4 animate-spin" />
              <span>Autenticando...</span>
            </>
          ) : (
            <>
              <span>Ingresar</span>
              <ArrowRight className="w-4 h-4" />
            </>
          )}
        </button>
      </form>

      {/* Access Help Card */}
      <div className="bg-slate-50 p-4 border border-[#cbd5e1] text-[11px] font-mono flex flex-col gap-1.5 text-on-surface-variant">
        <span className="font-sans font-bold uppercase tracking-wider text-xs text-[#0054A3]">Autenticación de Prueba</span>
        <div><strong className="text-[#0054A3]">Admin:</strong> <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">admin</code> / <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">admin123</code></div>
        <div><strong className="text-[#0054A3]">Técnico Piloto (Formulario):</strong> <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">tecnico</code> o <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">piloto</code> / <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">tecnico123</code></div>
        <div><strong className="text-[#0054A3]">Técnico Dashboard (Logs):</strong> <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">dashboard</code> o <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">tecnico_dashboard</code> / <code className="bg-white px-1.5 py-0.5 rounded border border-slate-200">tecnico123</code></div>
      </div>

      <footer className="pt-4 border-t border-[#cbd5e1] text-center flex flex-col gap-2">
        <p className="font-display text-[11px] text-on-surface-variant font-semibold tracking-wider uppercase">
          Protocolo de Acceso Seguro v4.2.0
        </p>
        <div className="flex justify-center gap-4">
          <span className="flex items-center gap-1 font-display text-[10px] text-on-surface-variant font-bold uppercase">
            <span className="w-2 h-2 rounded-full bg-[#4CAF50] animate-pulse" />
            Sistemas Online
          </span>
          <span className="flex items-center gap-1 font-display text-[10px] text-on-surface-variant font-bold uppercase">
            <Shield className="w-3.5 h-3.5 text-[#0054A3]" /> Cifrado SSL
          </span>
        </div>
      </footer>
    </motion.div>
  );
}
