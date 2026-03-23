import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Lock, User, ArrowRight } from 'lucide-react';

export default function Login() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Simulate login logic based on username
    if (username.toLowerCase().includes('admin')) {
      login('admin');
      navigate('/admin');
    } else {
      login('tech');
      navigate('/tech');
    }
  };

  return (
    <div className="min-h-screen flex bg-surface">
      {/* Left Side - Form */}
      <div className="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-20 xl:px-24 bg-white">
        <div className="w-full max-w-sm">
          <div className="mb-10 text-center lg:text-left">
            <div className="w-12 h-12 bg-primary rounded-xl flex items-center justify-center shadow-sm mb-6 mx-auto lg:mx-0">
              <span className="text-white font-bold text-2xl">AL</span>
            </div>
            <h2 className="text-3xl font-sans font-bold text-on-surface tracking-tight">Bienvenido de nuevo</h2>
            <p className="mt-2 text-sm text-on-surface-variant">
              Ingresa tus credenciales para acceder al sistema.
            </p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-sm font-medium text-on-surface-variant mb-2">
                Usuario
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <User className="h-5 w-5 text-outline" />
                </div>
                <input
                  type="text"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="block w-full pl-10 pr-3 py-3 border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all bg-surface-container-lowest"
                  placeholder="nombre.apellido"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-on-surface-variant mb-2">
                Contraseña
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock className="h-5 w-5 text-outline" />
                </div>
                <input
                  type="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="block w-full pl-10 pr-3 py-3 border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm transition-all bg-surface-container-lowest"
                  placeholder="••••••••"
                />
              </div>
            </div>

            <div className="flex items-center justify-between">
              <div className="flex items-center">
                <input
                  id="remember-me"
                  name="remember-me"
                  type="checkbox"
                  className="h-4 w-4 text-primary focus:ring-primary border-outline-variant rounded"
                />
                <label htmlFor="remember-me" className="ml-2 block text-sm text-on-surface-variant">
                  Recordarme
                </label>
              </div>

              <div className="text-sm">
                <a href="#" className="font-medium text-primary hover:text-primary-container transition-colors">
                  ¿Olvidaste tu contraseña?
                </a>
              </div>
            </div>

            <div>
              <button
                type="submit"
                className="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-container focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-200"
              >
                Iniciar Sesión
                <ArrowRight className="w-4 h-4" />
              </button>
            </div>
          </form>

          <div className="mt-8 text-center text-xs text-outline">
            <p>Tip: Usa 'admin' para Administrador, otro para Técnico</p>
          </div>
        </div>
      </div>

      {/* Right Side - Image/Branding */}
      <div className="hidden lg:flex flex-1 bg-primary relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-primary to-primary-container opacity-90"></div>
        {/* Decorative elements */}
        <div className="absolute -top-24 -right-24 w-96 h-96 bg-secondary rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div className="absolute -bottom-24 -left-24 w-96 h-96 bg-primary-fixed rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        
        <div className="relative z-10 flex flex-col justify-center items-start p-20 text-white max-w-2xl">
          <h1 className="text-5xl font-sans font-bold tracking-tight mb-6 leading-tight">
            Gestión de Activos <br/>
            <span className="text-secondary-container">Inteligente y Segura</span>
          </h1>
          <p className="text-lg text-primary-fixed-dim font-body leading-relaxed mb-12">
            Control total sobre el inventario, locaciones y movimientos. 
            Diseñado para la eficiencia operativa y la trazabilidad absoluta.
          </p>
          
          <div className="grid grid-cols-2 gap-8 w-full">
            <div className="glass-panel bg-white/10 border-white/10 p-6 rounded-2xl">
              <div className="text-3xl font-bold text-secondary-container mb-2">99.9%</div>
              <div className="text-sm text-primary-fixed-dim">Precisión de Inventario</div>
            </div>
            <div className="glass-panel bg-white/10 border-white/10 p-6 rounded-2xl">
              <div className="text-3xl font-bold text-secondary-container mb-2">+5k</div>
              <div className="text-sm text-primary-fixed-dim">Activos Gestionados</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
