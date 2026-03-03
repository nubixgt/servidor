import PageLayout from '../components/PageLayout';
import { Search, Plus, ArrowRight } from 'lucide-react';

export default function Catalogo() {
  return (
    <PageLayout title="Catálogo de Productos" subtitle="Gestiona tu inventario agrícola, precios y existencias">
      <div className="flex flex-col md:flex-row gap-4 justify-between items-center mb-6">
        <div className="relative w-full md:w-96">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input className="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl bg-white text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-[#2E7D32]/50 outline-none sm:text-sm shadow-sm" placeholder="Buscar por nombre, categoría o SKU" type="text" />
        </div>
        <div className="flex gap-2 overflow-x-auto w-full md:w-auto pb-2 md:pb-0 no-scrollbar">
            <button className="px-4 py-1.5 rounded-full bg-gray-900 text-white text-sm font-medium whitespace-nowrap">Todos</button>
            <button className="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium whitespace-nowrap">Fertilizantes</button>
            <button className="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium whitespace-nowrap">Semillas</button>
            <button className="px-4 py-1.5 rounded-full bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors text-sm font-medium whitespace-nowrap">Herramientas</button>
        </div>
        <button className="hidden md:flex bg-[#2E7D32] hover:bg-[#1B5E20] text-white font-medium py-2.5 px-5 rounded-full shadow-sm transition-all items-center gap-2">
            <Plus size={20} />
            <span>Nuevo Producto</span>
        </button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {[1, 2, 3, 4, 5, 6, 7, 8].map((i) => (
            <div key={i} className="bg-white rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200 group flex flex-col overflow-hidden border border-gray-100">
                <div className="relative h-48 bg-gray-100 overflow-hidden">
                    <span className={`absolute top-3 right-3 text-xs font-bold px-2.5 py-1 rounded-full z-10 ${i % 3 === 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>
                        {i % 3 === 0 ? 'Agotado' : 'En Stock'}
                    </span>
                    <img className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" src={`https://picsum.photos/seed/product${i}/400/300`} alt="Product" />
                </div>
                <div className="p-4 flex flex-col flex-1">
                    <div className="mb-1 text-xs text-gray-500 uppercase tracking-wider font-semibold">Categoría {i}</div>
                    <h3 className="text-lg font-bold text-gray-900 mb-1">Producto Agrícola {i}</h3>
                    <p className="text-sm text-gray-500 mb-4 line-clamp-2">Descripción corta del producto agrícola para mostrar en el catálogo.</p>
                    <div className="mt-auto flex items-center justify-between">
                        <span className="text-xl font-bold text-gray-900">${(i * 15.5).toFixed(2)}</span>
                        <button className="h-8 w-8 rounded-full bg-gray-100 hover:bg-[#2E7D32] hover:text-white flex items-center justify-center text-gray-700 transition-colors">
                            <ArrowRight size={18} />
                        </button>
                    </div>
                </div>
            </div>
        ))}
      </div>
    </PageLayout>
  );
}
