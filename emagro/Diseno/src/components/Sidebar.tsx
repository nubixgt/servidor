import { NavLink, useNavigate } from 'react-router-dom';
import { LayoutDashboard, Users, Store, Receipt, Package, CreditCard, ChevronDown, Menu, LogOut } from 'lucide-react';
import { useState } from 'react';

export default function Sidebar() {
  const [isMobileOpen, setIsMobileOpen] = useState(false);
  const navigate = useNavigate();

  const navItems = [
    { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
    { name: 'Usuarios', path: '/usuarios', icon: Users },
    { name: 'Clientes', path: '/clientes', icon: Store },
    { name: 'Ventas', path: '/ventas', icon: Receipt },
    { name: 'Catálogo', path: '/catalogo', icon: Package },
    { name: 'Pagos', path: '/pagos', icon: CreditCard },
  ];

  const handleLogout = () => {
    navigate('/login');
  };

  return (
    <>
      {/* Mobile Header */}
      <div className="md:hidden fixed top-0 w-full h-16 bg-[#2E7D32] border-b border-green-600 flex items-center justify-between px-4 z-50 shadow-md">
        <div className="flex items-center gap-2">
          <div className="w-8 h-8 rounded-full bg-white flex items-center justify-center text-[#2E7D32] font-bold text-sm">EM</div>
          <span className="text-lg font-bold text-white">EMAGRO</span>
        </div>
        <button className="text-white" onClick={() => setIsMobileOpen(!isMobileOpen)}>
          <Menu size={24} />
        </button>
      </div>

      {/* Sidebar */}
      <aside className={`w-64 bg-white border-r border-gray-200 flex flex-col z-40 shadow-[4px_0_24px_rgba(0,0,0,0.02)] transition-transform duration-300 ${isMobileOpen ? 'translate-x-0 fixed inset-y-0 left-0 pt-16' : '-translate-x-full hidden'} md:translate-x-0 md:flex md:relative md:pt-0`}>
        <div className="h-20 flex items-center px-6 border-b border-gray-100 hidden md:flex">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 rounded-full bg-[#2E7D32] flex items-center justify-center text-white font-bold text-sm shadow-sm">
              EM
            </div>
            <span className="text-lg font-bold text-gray-800 tracking-tight">EMAGRO</span>
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto py-6 px-4 space-y-1">
          {navItems.map((item) => (
            <NavLink
              key={item.name}
              to={item.path}
              onClick={() => setIsMobileOpen(false)}
              className={({ isActive }) =>
                `flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium ${
                  isActive
                    ? 'bg-green-50 text-[#2E7D32]'
                    : 'text-gray-600 hover:bg-green-50 hover:text-[#2E7D32]'
                }`
              }
            >
              <item.icon size={20} />
              {item.name}
            </NavLink>
          ))}
        </nav>

        <div className="p-4 border-t border-gray-100 flex flex-col gap-2">
          <div className="flex items-center gap-3 px-2 py-2 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer group">
            <div className="w-10 h-10 rounded-full bg-gray-200 overflow-hidden ring-2 ring-white shadow-sm">
              <img
                alt="Profile"
                className="w-full h-full object-cover"
                src="https://picsum.photos/seed/user/100/100"
              />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-gray-900 group-hover:text-[#2E7D32] transition-colors truncate">
                Admin Agro
              </p>
              <p className="text-xs text-gray-500 truncate">Administrador</p>
            </div>
            <ChevronDown size={16} className="text-gray-400" />
          </div>
          
          <button 
            onClick={handleLogout}
            className="flex items-center gap-3 w-full px-4 py-3 text-gray-600 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors mt-2"
          >
            <LogOut size={20} />
            <span className="text-sm font-medium">Cerrar Sesión</span>
          </button>
        </div>
      </aside>
      
      {/* Overlay for mobile */}
      {isMobileOpen && (
        <div 
          className="fixed inset-0 bg-black/50 z-30 md:hidden" 
          onClick={() => setIsMobileOpen(false)}
        />
      )}
    </>
  );
}
