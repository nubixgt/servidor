import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

export default function Login() {
  const navigate = useNavigate();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Guardamos el rol en localStorage para simular la sesión
    const role = username.toLowerCase().trim();
    localStorage.setItem('userRole', role);

    // Redirección basada en el rol
    if (role === 'admin') {
      navigate('/admin/dashboard');
    } else if (role === 'tecnico') {
      navigate('/tech/dashboard');
    } else {
      // Por defecto, si escriben otra cosa, los mandamos como admin para la demo
      localStorage.setItem('userRole', 'admin');
      navigate('/admin/dashboard');
    }
  };

  return (
    <div className="min-h-screen bg-surface flex items-center justify-center p-4 relative overflow-hidden font-body">
      {/* Ethereal Background Elements */}
      <div className="absolute top-[-20%] left-[-10%] w-[60%] h-[60%] rounded-full bg-primary-fixed/30 blur-[120px] -z-10"></div>
      <div className="absolute bottom-[-20%] right-[-10%] w-[50%] h-[50%] rounded-full bg-secondary-fixed/20 blur-[100px] -z-10"></div>

      <div className="w-full max-w-[1000px] h-[600px] glass-card rounded-3xl shadow-ambient flex overflow-hidden relative z-10">
        
        {/* Left Side: Branding & Visual */}
        <div className="hidden md:flex w-1/2 primary-gradient p-12 flex-col justify-between relative overflow-hidden text-white">
          <div className="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1548199973-03cce0bbc87b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80')] opacity-20 mix-blend-overlay object-cover"></div>
          <div className="relative z-10">
            <div className="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center mb-6 shadow-sm border border-white/30">
              <span className="material-symbols-outlined text-4xl">pets</span>
            </div>
            <h1 className="font-headline font-extrabold text-4xl tracking-tight leading-tight mb-4">
              Protección y Bienestar Animal
            </h1>
            <p className="text-primary-fixed text-lg font-medium">
              Plataforma de gestión integral para el Ministerio de Agricultura, Ganadería y Alimentación.
            </p>
          </div>
          <div className="relative z-10">
            <p className="text-sm font-bold tracking-widest uppercase opacity-80">AppUBA v2.0</p>
          </div>
        </div>

        {/* Right Side: Login Form */}
        <div className="w-full md:w-1/2 p-8 sm:p-12 flex flex-col justify-center bg-white/50">
          <div className="max-w-sm mx-auto w-full">
            <div className="mb-8 text-center md:text-left">
              <h2 className="font-headline font-extrabold text-3xl text-on-surface mb-2">Bienvenido</h2>
              <p className="text-on-surface-variant">Ingresa tus credenciales para acceder al sistema.</p>
            </div>

            <form onSubmit={handleLogin} className="space-y-5">
              <div>
                <label className="block text-xs font-bold text-outline uppercase tracking-widest mb-2">Usuario</label>
                <div className="relative group">
                  <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">person</span>
                  <input 
                    type="text" 
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    placeholder="Ej: admin o tecnico" 
                    className="w-full bg-surface-container-lowest border border-outline-variant/50 rounded-xl py-3 pl-12 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50 transition-all"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-bold text-outline uppercase tracking-widest mb-2">Contraseña</label>
                <div className="relative group">
                  <span className="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
                  <input 
                    type="password" 
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="••••••••" 
                    className="w-full bg-surface-container-lowest border border-outline-variant/50 rounded-xl py-3 pl-12 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary/50 transition-all"
                    required
                  />
                  <button type="button" className="absolute right-4 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors">
                    <span className="material-symbols-outlined text-[20px]">visibility_off</span>
                  </button>
                </div>
              </div>

              <button type="submit" className="w-full primary-gradient text-white font-bold py-3.5 rounded-xl shadow-md hover:shadow-lg hover:opacity-95 transition-all mt-8 flex items-center justify-center gap-2">
                Ingresar al Sistema
                <span className="material-symbols-outlined text-[20px]">arrow_forward</span>
              </button>
            </form>

            <div className="mt-8 text-center">
              <p className="text-xs text-outline font-medium">
                Uso exclusivo del personal autorizado del MAGA.<br/>
                Todos los accesos son monitoreados.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
