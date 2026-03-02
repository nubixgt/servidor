import PageLayout from '../components/PageLayout';
import { UserPlus } from 'lucide-react';

export default function Usuarios() {
  return (
    <PageLayout title="Gestión de Usuarios" subtitle="Administra los accesos y roles del sistema">
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <div className="flex flex-col md:flex-row gap-4 justify-between items-center">
            <div className="relative w-full md:w-96">
                <input className="block w-full pl-4 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar usuario..." type="text" />
            </div>
            <button className="flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm w-full md:w-auto">
                <UserPlus size={18} />
                Nuevo Usuario
            </button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {[1, 2, 3, 4, 5, 6].map((i) => (
            <div key={i} className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center hover:shadow-md transition-shadow">
                <div className="w-20 h-20 rounded-full bg-gray-200 mb-4 overflow-hidden">
                    <img src={`https://picsum.photos/seed/user${i}/200/200`} alt="User" className="w-full h-full object-cover" />
                </div>
                <h3 className="font-bold text-gray-900 text-lg">Usuario {i}</h3>
                <p className="text-sm text-gray-500 mb-4">usuario{i}@emagro.com</p>
                <span className="px-3 py-1 bg-green-50 text-green-700 text-xs font-semibold rounded-full border border-green-200 mb-4">
                    {i % 2 === 0 ? 'Administrador' : 'Vendedor'}
                </span>
                <div className="flex gap-2 w-full mt-auto pt-4 border-t border-gray-100">
                    <button className="flex-1 py-2 text-sm font-medium text-gray-600 hover:text-[#2E7D32] hover:bg-green-50 rounded-lg transition-colors">Editar</button>
                    <button className="flex-1 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">Bloquear</button>
                </div>
            </div>
        ))}
      </div>
    </PageLayout>
  );
}
