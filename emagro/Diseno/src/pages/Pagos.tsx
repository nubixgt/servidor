import PageLayout from '../components/PageLayout';
import { Search, Filter, Plus, MoreVertical, TrendingUp, AlertCircle, Calendar } from 'lucide-react';

export default function Pagos() {
  return (
    <PageLayout title="Gestión de Pagos" subtitle="Seguimiento de cobros y deudas">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div className="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-gray-500 text-sm font-medium mb-1">Total Recaudado</p>
                    <h3 className="text-2xl font-bold text-gray-900 tracking-tight mb-2">$45,200.00</h3>
                </div>
                <div className="p-2 bg-green-50 rounded-lg text-green-600">
                    <TrendingUp size={24} />
                </div>
            </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-gray-500 text-sm font-medium mb-1">Deuda Total</p>
                    <h3 className="text-2xl font-bold text-gray-900 tracking-tight mb-2">$12,350.50</h3>
                </div>
                <div className="p-2 bg-orange-50 rounded-lg text-orange-600">
                    <AlertCircle size={24} />
                </div>
            </div>
        </div>
        <div className="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-gray-500 text-sm font-medium mb-1">Pagos Próximos</p>
                    <h3 className="text-2xl font-bold text-gray-900 tracking-tight mb-2">5 Clientes</h3>
                </div>
                <div className="p-2 bg-red-50 rounded-lg text-red-600">
                    <Calendar size={24} />
                </div>
            </div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-5 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
            <div className="relative w-full md:w-96">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
                <input className="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-[#2E7D32]/20 focus:border-[#2E7D32] outline-none text-gray-900 placeholder-gray-400" placeholder="Buscar pago o cliente..." type="text" />
            </div>
            <div className="flex gap-3 w-full md:w-auto">
                <button className="flex-1 md:flex-none px-4 py-2 bg-green-50 text-[#2E7D32] border border-green-200 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors flex items-center justify-center gap-2">
                    <Filter size={18} /> Filtros
                </button>
                <button className="flex-1 md:flex-none px-4 py-2 bg-[#2E7D32] text-white rounded-lg text-sm font-medium hover:bg-[#1B5E20] transition-colors flex items-center justify-center gap-2 shadow-sm">
                    <Plus size={18} /> Registrar Pago
                </button>
            </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50 text-gray-500 text-xs font-semibold uppercase tracking-wider border-b border-gray-100">
                <th className="px-6 py-4">ID</th>
                <th className="px-6 py-4">Cliente</th>
                <th className="px-6 py-4">Concepto</th>
                <th className="px-6 py-4">Vencimiento</th>
                <th className="px-6 py-4">Monto</th>
                <th className="px-6 py-4">Estado</th>
                <th className="px-6 py-4 text-right"></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {[1, 2, 3, 4, 5].map((i) => (
                <tr key={i} className="hover:bg-gray-50 transition-colors group">
                  <td className="px-6 py-4 text-sm text-gray-500 font-mono">#P-2026-00{i}</td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-bold text-xs">C{i}</div>
                      <span className="font-medium text-gray-900">Cliente {i}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-600">Fertilizantes NPK</td>
                  <td className="px-6 py-4 text-sm text-gray-600">15 Oct 2026</td>
                  <td className="px-6 py-4 font-medium text-gray-900">${(i * 450).toFixed(2)}</td>
                  <td className="px-6 py-4">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ${i % 3 === 0 ? 'bg-red-50 text-red-700 border-red-200' : i % 2 === 0 ? 'bg-yellow-50 text-yellow-700 border-yellow-200' : 'bg-green-50 text-green-700 border-green-200'}`}>
                      {i % 3 === 0 ? 'Vencido' : i % 2 === 0 ? 'Pendiente' : 'Pagado'}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    <button className="text-gray-400 hover:text-[#2E7D32] transition-colors">
                      <MoreVertical size={20} />
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
