import { Construction, User, Lock, Eye, ArrowRight, ShieldCheck, UserCog, HardHat, Plus } from "lucide-react";
import { useState, FormEvent } from "react";
import { motion } from "motion/react";
import { Role } from "../App";

interface LoginProps {
  onLogin: (role: Role) => void;
}

export default function Login({ onLogin }: LoginProps) {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    
    if (username.toLowerCase().includes("admin")) {
      onLogin("admin");
    } else if (username.toLowerCase().includes("supervisor")) {
      onLogin("supervisor");
    } else if (username.toLowerCase().includes("tecnico")) {
      onLogin("tecnico");
    } else {
      onLogin("admin");
    }
  };

  return (
    <div className="min-h-screen bg-app-scenic flex flex-col items-center justify-center p-6 relative overflow-hidden">
      {/* Decorative Glows */}
      <div className="absolute top-1/4 left-1/4 w-[600px] h-[600px] bg-primary/20 blur-[150px] rounded-full animate-pulse"></div>
      <div className="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-tertiary/10 blur-[130px] rounded-full"></div>

      <motion.div 
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className="w-full max-w-[520px] glass-card rounded-[48px] p-12 relative z-10 border border-white/20 shadow-[0_32px_64px_-12px_rgba(0,0,0,0.5)] bg-slate-950/40 backdrop-blur-3xl"
      >
        <div className="text-center mb-12">
          <div className="w-20 h-20 bg-primary/20 rounded-[28px] flex items-center justify-center mx-auto mb-6 shadow-xl border border-white/20">
            <Construction className="w-10 h-10 text-primary shadow-[0_0_15px_rgba(99,102,241,0.4)]" />
          </div>
          <h1 className="text-4xl font-black text-white tracking-tighter mb-1 uppercase italic">CONSTRUCTPRO</h1>
          <p className="text-white/40 text-[10px] font-bold tracking-[0.4em] uppercase">Gestión Empresarial</p>
        </div>

        <form className="space-y-8" onSubmit={handleSubmit}>
          <div className="space-y-6">
            <div className="space-y-2">
              <label className="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 block ml-3">Usuario</label>
              <div className="relative group">
                <User className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 group-focus-within:text-primary transition-colors" />
                <input 
                  type="text" 
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  placeholder="admin, supervisor o tecnico"
                  className="w-full glass-input rounded-2xl py-5 pl-14 pr-6 focus:ring-2 focus:ring-primary/40 transition-all outline-none placeholder:text-white/10 font-medium"
                />
              </div>
            </div>

            <div className="space-y-2">
              <label className="text-[10px] font-black uppercase tracking-[0.2em] text-white/30 block ml-3">Contraseña</label>
              <div className="relative group">
                <Lock className="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5 text-white/30 group-focus-within:text-primary transition-colors" />
                <input 
                  type="password" 
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full glass-input rounded-2xl py-5 pl-14 pr-12 focus:ring-2 focus:ring-primary/40 transition-all outline-none placeholder:text-white/10 font-medium"
                />
                <button type="button" className="absolute right-5 top-1/2 -translate-y-1/2 text-white/20 hover:text-white transition-colors">
                  <Eye className="w-5 h-5" />
                </button>
              </div>
            </div>
          </div>

          <button 
            type="submit"
            className="w-full glass-button-primary text-white py-5 rounded-2xl font-black text-lg flex items-center justify-center gap-3 shadow-[0_20px_40px_-5px_rgba(99,102,241,0.3)] hover:shadow-[0_20px_40px_-5px_rgba(99,102,241,0.5)] hover:-translate-y-1 active:translate-y-0 transition-all uppercase tracking-widest"
          >
            Sincronizar Acceso
            <ArrowRight className="w-6 h-6" />
          </button>
        </form>

        <div className="mt-12 pt-8 border-t border-white/5">
          <p className="text-[10px] font-black text-white/20 uppercase tracking-[0.2em] mb-6 text-center italic">Credenciales por Rol</p>
          
          <div className="grid grid-cols-1 gap-4">
            <button 
              onClick={() => { setUsername("admin_pro"); setPassword("admin123"); }}
              className="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 hover:border-primary/30 transition-all group"
            >
              <div className="flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-primary/20 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <div className="text-left">
                  <p className="text-[10px] font-black text-white italic uppercase tracking-widest">Administrador</p>
                  <p className="text-xs text-white/40 font-bold">admin_pro • PWD: admin123</p>
                </div>
              </div>
              <Plus className="w-4 h-4 text-white/20" />
            </button>

            <button 
              onClick={() => { setUsername("supervisor_site"); setPassword("super456"); }}
              className="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 hover:border-orange-500/30 transition-all group"
            >
              <div className="flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-orange-500/20 flex items-center justify-center text-orange-500 group-hover:scale-110 transition-transform">
                  <UserCog className="w-5 h-5" />
                </div>
                <div className="text-left">
                  <p className="text-[10px] font-black text-white italic uppercase tracking-widest">Supervisor</p>
                  <p className="text-xs text-white/40 font-bold">supervisor_site • PWD: super456</p>
                </div>
              </div>
              <Plus className="w-4 h-4 text-white/20" />
            </button>

            <button 
              onClick={() => { setUsername("tecnico_base"); setPassword("tech789"); }}
              className="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 hover:border-white/30 transition-all group"
            >
              <div className="flex items-center gap-4">
                <div className="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white/60 group-hover:scale-110 transition-transform">
                  <HardHat className="w-5 h-5" />
                </div>
                <div className="text-left">
                  <p className="text-[10px] font-black text-white italic uppercase tracking-widest">Técnico</p>
                  <p className="text-xs text-white/40 font-bold">tecnico_base • PWD: tech789</p>
                </div>
              </div>
              <Plus className="w-4 h-4 text-white/20" />
            </button>
          </div>
        </div>
      </motion.div>

      <motion.p 
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.5 }}
        className="mt-12 text-[10px] font-bold text-white/20 uppercase tracking-[0.3em] relative z-10"
      >
        ConstructPro © 2024 • Asegurado por Enterprise Shield™
      </motion.p>
    </div>
  );
}
