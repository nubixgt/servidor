import PageLayout from '../components/PageLayout';
import { Search, Filter, Plus, FileText, Eye } from 'lucide-react';

export default function Ventas() {
  return (
    <PageLayout title="Historial de Ventas" subtitle="Registro de transacciones y facturación">
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div className="md:col-span-1">
            <label className="block text-sm font-medium text-gray-700 mb-1">Buscar venta</label>
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
              <input className="block w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none" placeholder="ID, Cliente o NIT..." type="text" />
            </div>
          </div>
          <div className="md:col-span-1">
            <label className="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
            <input className="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none" type="date" />
          </div>
          <div className="md:col-span-1">
            <label className="block text-sm font-medium text-gray-700 mb-1">Vendedor</label>
            <select className="block w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-900 focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] sm:text-sm outline-none">
              <option>Todos los vendedores</option>
            </select>
          </div>
          <div className="md:col-span-1 flex justify-end gap-2">
            <button className="flex items-center justify-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors w-full md:w-auto">
              <Filter size={16} className="mr-2" /> Filtros
            </button>
            <button className="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#2E7D32] hover:bg-[#1B5E20] shadow-sm transition-colors w-full md:w-auto">
              <Plus size={16} className="mr-2" /> Nueva Venta
            </button>
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-gray-50 text-gray-500 border-b border-gray-100 uppercase text-xs">
              <tr>
                <th className="px-6 py-4 font-semibold">ID Nota</th>
                <th className="px-6 py-4 font-semibold">Fecha</th>
                <th className="px-6 py-4 font-semibold">Cliente</th>
                <th className="px-6 py-4 font-semibold">Vendedor</th>
                <th className="px-6 py-4 font-semibold">Total</th>
                <th className="px-6 py-4 font-semibold text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {[1, 2, 3, 4, 5].map((i) => (
                <tr key={i} className="hover:bg-gray-50 transition-colors">
                  <td className="px-6 py-4 font-bold text-gray-900">#0004{i}</td>
                  <td className="px-6 py-4">
                    <div className="text-gray-900">20/02/2026</div>
                    <div className="text-xs text-gray-500">14:30</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="font-medium text-gray-900">Cliente {i}</div>
                    <div className="text-xs text-gray-500">NIT: CF</div>
                  </td>
                  <td className="px-6 py-4 text-gray-600">Vendedor {i}</td>
                  <td className="px-6 py-4">
                    <span className="px-3 py-1 inline-flex text-sm font-semibold rounded-full bg-green-50 text-green-700 border border-green-200">
                      Q {(i * 320).toFixed(2)}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button className="text-[#2E7D32] hover:text-[#1B5E20] mr-3" title="Ver PDF">
                      <FileText size={20} />
                    </button>
                    <button className="text-gray-400 hover:text-gray-600" title="Detalles">
                      <Eye size={20} />
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
