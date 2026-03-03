import PageLayout from '../components/PageLayout';
import { Search, Filter, Plus, MoreVertical } from 'lucide-react';

export default function Clientes() {
  return (
    <PageLayout title="Directorio de Clientes" subtitle="Administra la información de tus clientes agrícolas">
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <div className="flex flex-col md:flex-row gap-4 justify-between items-center">
            <div className="relative w-full md:w-96">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input className="block w-full pl-10 pr-3 py-2.5 border border-gray-200 bg-gray-50 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] transition-all sm:text-sm" placeholder="Buscar cliente por nombre o NIT..." type="text" />
            </div>
            <div className="flex gap-3 w-full md:w-auto">
                <button className="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-green-50 text-[#2E7D32] hover:bg-green-100 rounded-lg text-sm font-semibold transition-colors border border-green-200">
                    <Filter size={18} />
                    Filtros
                </button>
                <button className="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-2.5 bg-[#2E7D32] hover:bg-[#1B5E20] text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                    <Plus size={18} />
                    Nuevo Cliente
                </button>
            </div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 text-gray-500 border-b border-gray-100">
              <tr>
                <th className="px-6 py-4 font-semibold w-24">ID</th>
                <th className="px-6 py-4 font-semibold">Cliente</th>
                <th className="px-6 py-4 font-semibold">Teléfono</th>
                <th className="px-6 py-4 font-semibold">Dirección</th>
                <th className="px-6 py-4 font-semibold text-right">Límite de Crédito</th>
                <th className="px-6 py-4 font-semibold text-center">Estado</th>
                <th className="px-6 py-4"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {[1, 2, 3, 4, 5].map((i) => (
                <tr key={i} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 font-mono text-gray-500">#C00{i}</td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-gray-200 overflow-hidden">
                        <img src={`https://picsum.photos/seed/client${i}/100/100`} alt="Client" className="w-full h-full object-cover" />
                      </div>
                      <div>
                        <div className="font-semibold text-gray-900">Cliente {i}</div>
                        <div className="text-xs text-gray-500">Agropecuaria {i}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-gray-600">+52 55 1234 567{i}</td>
                  <td className="px-6 py-4 text-gray-600">Dirección {i}, Zona Centro</td>
                  <td className="px-6 py-4 font-medium text-right text-gray-900">${(i * 5000).toFixed(2)}</td>
                  <td className="px-6 py-4 text-center">
                    <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                      Activo
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button className="text-gray-400 hover:text-[#2E7D32] p-1 rounded-full hover:bg-green-50 transition-all">
                      <MoreVertical size={18} />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </PageLayout>
  );
}
