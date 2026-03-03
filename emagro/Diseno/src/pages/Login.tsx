import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Eye, EyeOff } from 'lucide-react';

export default function Login() {
  const [showPassword, setShowPassword] = useState(false);
  const navigate = useNavigate();

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    // Simulate login and redirect to dashboard
    navigate('/dashboard');
  };

  return (
    <div className="min-h-screen flex items-center justify-center relative overflow-hidden bg-[#f0f4f1]">
      {/* Background Image & Overlay */}
      <div className="absolute inset-0 z-0">
        <img
          alt="Campo agrícola verde difuminado"
          className="w-full h-full object-cover opacity-20"
          src="https://images.unsplash.com/photo-1625246333195-58f21a408738?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"
        />
        <div className="absolute inset-0 bg-gradient-to-br from-green-100/40 to-[#43a047]/10 backdrop-blur-[2px]"></div>
      </div>

      {/* Decorative Blurs */}
      <div className="absolute top-[-50px] right-[-50px] w-64 h-64 bg-[#43a047]/20 rounded-full blur-3xl z-0 pointer-events-none"></div>
      <div className="absolute bottom-[-50px] left-[-50px] w-64 h-64 bg-[#43a047]/20 rounded-full blur-3xl z-0 pointer-events-none"></div>

      {/* Login Card */}
      <div className="relative z-10 w-full max-w-md p-8 bg-white rounded-2xl shadow-2xl border border-gray-100 transition-all duration-300">
        <div className="flex flex-col items-center mb-8">
          <div className="flex items-center justify-center mb-4">
            <div className="relative flex items-center">
              <svg className="w-10 h-10 text-[#43a047] mr-2" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 7.03,11.5 8.5,13.5C10.45,11.5 13,9.5 17,8Z" />
              </svg>
              <div className="flex flex-col items-start leading-none">
                <h1 className="text-3xl font-bold text-gray-800 tracking-tight">EMAGRO</h1>
                <span className="text-[0.6rem] text-[#43a047] font-semibold tracking-widest uppercase">
                  Biotecnología para el Desarrollo
                </span>
              </div>
              <div className="absolute -top-2 -right-6 flex space-x-1">
                <div className="w-2 h-2 rounded-full bg-[#43a047] opacity-60"></div>
                <div className="w-3 h-3 rounded-full bg-[#43a047] opacity-80"></div>
                <div className="w-2 h-2 rounded-full bg-[#43a047] opacity-60"></div>
              </div>
            </div>
          </div>
          <h2 className="text-xl font-medium text-gray-600">Bienvenido de nuevo</h2>
          <p className="text-sm text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
        </div>

        <form onSubmit={handleLogin} className="space-y-6">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="username">
              Usuario
            </label>
            <div className="relative rounded-md shadow-sm">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <input
                className="focus:ring-[#43a047] focus:border-[#43a047] block w-full pl-10 pr-3 py-3 sm:text-sm border border-gray-300 rounded-xl bg-white text-gray-900 placeholder-gray-400 outline-none transition-colors"
                id="username"
                name="username"
                placeholder="nombre.apellido"
                required
                type="text"
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1" htmlFor="password">
              Contraseña
            </label>
            <div className="relative rounded-md shadow-sm">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </div>
              <input
                className="focus:ring-[#43a047] focus:border-[#43a047] block w-full pl-10 pr-10 py-3 sm:text-sm border border-gray-300 rounded-xl bg-white text-gray-900 placeholder-gray-400 outline-none transition-colors"
                id="password"
                name="password"
                placeholder="••••••••"
                required
                type={showPassword ? "text" : "password"}
              />
              <div 
                className="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer text-gray-400 hover:text-gray-600 transition-colors"
                onClick={() => setShowPassword(!showPassword)}
              >
                {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
              </div>
            </div>
          </div>

          <div className="flex items-center justify-between">
            <div className="flex items-center">
              <input
                className="h-4 w-4 text-[#43a047] focus:ring-[#43a047] border-gray-300 rounded cursor-pointer"
                id="remember-me"
                name="remember-me"
                type="checkbox"
              />
              <label className="ml-2 block text-sm text-gray-700 cursor-pointer" htmlFor="remember-me">
                Recordarme
              </label>
            </div>
            <div className="text-sm">
              <a className="font-medium text-[#43a047] hover:text-[#2e7d32] transition-colors" href="#">
                ¿Olvidaste tu contraseña?
              </a>
            </div>
          </div>

          <div>
            <button
              className="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-medium text-white bg-[#43a047] hover:bg-[#2e7d32] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#43a047] transition-all duration-200 transform hover:-translate-y-0.5"
              type="submit"
            >
              Ingresar
            </button>
          </div>
        </form>

        <div className="mt-8 text-center text-xs text-gray-500">
          <p>© 2026 EMAGRO Biotecnología. Todos los derechos reservados.</p>
          <div className="mt-2 flex justify-center space-x-4">
            <a className="hover:text-[#43a047] transition-colors" href="#">Privacidad</a>
            <span>•</span>
            <a className="hover:text-[#43a047] transition-colors" href="#">Términos</a>
            <span>•</span>
            <a className="hover:text-[#43a047] transition-colors" href="#">Soporte</a>
          </div>
        </div>
      </div>
    </div>
  );
}
