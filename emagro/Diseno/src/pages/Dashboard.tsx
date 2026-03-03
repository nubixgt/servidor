import PageLayout from '../components/PageLayout';
import { TrendingUp, Users, AlertTriangle, Minus, ArrowRight } from 'lucide-react';

export default function Dashboard() {
  return (
    <PageLayout title="Estadísticas de Ventas" subtitle="Resumen general del rendimiento comercial">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <KpiCard
          title="Ventas Totales"
          value="Q 48,250.00"
          trend="+12.5% vs mes ant."
          trendUp={true}
          icon={<span className="font-bold text-xl">Q</span>}
          color="green"
        />
        <KpiCard
          title="Clientes Activos"
          value="1,245"
          trend="+3.2% nuevos"
          trendUp={true}
          icon={<Users size={24} />}
          color="blue"
        />
        <KpiCard
          title="Stock Bajo"
          value="8 Items"
          trend="Requiere atención"
          trendUp={false}
          icon={<AlertTriangle size={24} />}
          color="red"
        />
        <KpiCard
          title="Promedio Venta"
          value="Q 850.00"
          trend="Estable"
          trendUp={null}
          icon={<TrendingUp size={24} />}
          color="orange"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div className="flex items-center justify-between mb-8">
            <div>
              <h3 className="text-lg font-bold text-gray-800">Tendencia de Ventas</h3>
              <p className="text-sm text-gray-500">Enero - Junio 2026</p>
            </div>
            <select className="bg-gray-50 border border-gray-200 text-sm rounded-lg py-2 px-3 focus:ring-1 focus:ring-[#2E7D32] focus:border-[#2E7D32] text-gray-600 shadow-sm cursor-pointer hover:bg-gray-100 transition-colors outline-none">
              <option>Últimos 6 meses</option>
              <option>Este año</option>
              <option>Todo el tiempo</option>
            </select>
          </div>
          {/* Chart Placeholder */}
          <div className="h-72 flex items-end justify-between gap-2">
              {[40, 55, 45, 70, 60, 85].map((h, i) => (
                  <div key={i} className="flex-1 flex flex-col items-center gap-2 group">
                      <div className={`w-full max-w-[3rem] rounded-t-lg transition-all relative ${i === 5 ? 'bg-[#2E7D32]' : 'bg-gray-100 group-hover:bg-[#2E7D32]/60'}`} style={{ height: `${h}%` }}>
                          {i === 5 && <div className="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-xs font-bold py-1 px-2 rounded-full">Q32k</div>}
                      </div>
                      <span className={`text-xs font-medium ${i === 5 ? 'text-[#2E7D32] font-bold' : 'text-gray-500'}`}>
                          {['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'][i]}
                      </span>
                  </div>
              ))}
          </div>
        </div>

        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col">
          <h3 className="text-lg font-bold text-gray-800 mb-6">Ventas por Categoría</h3>
          <div className="flex-1 flex items-center justify-center relative py-4">
            <div className="w-48 h-48 rounded-full border-[1.5rem] border-[#2E7D32] relative flex items-center justify-center shadow-sm bg-white" style={{ borderRightColor: '#81C784', borderBottomColor: '#FFB74D', borderLeftColor: '#EEEEEE', transform: 'rotate(-45deg)' }}>
            </div>
            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <span className="text-3xl font-bold text-gray-800">1,204</span>
              <span className="text-xs text-gray-500 font-medium uppercase tracking-wide">Items Vendidos</span>
            </div>
          </div>
          <div className="mt-6 space-y-4">
            <CategoryStat name="Fertilizantes" percentage="45%" color="bg-[#2E7D32]" />
            <CategoryStat name="Semillas" percentage="25%" color="bg-[#81C784]" />
            <CategoryStat name="Herramientas" percentage="20%" color="bg-[#FFB74D]" />
            <CategoryStat name="Otros" percentage="10%" color="bg-[#EEEEEE]" />
          </div>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
          <h3 className="text-lg font-bold text-gray-800">Ventas Recientes</h3>
          <button className="text-[#2E7D32] text-sm font-semibold hover:text-[#1B5E20] transition-colors flex items-center gap-1 hover:underline">
            Ver todas <ArrowRight size={16} />
          </button>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-white text-gray-500 border-b border-gray-100">
              <tr>
                <th className="px-6 py-4 font-semibold w-32 bg-gray-50/50">ID</th>
                <th className="px-6 py-4 font-semibold bg-gray-50/50">Cliente</th>
                <th className="px-6 py-4 font-semibold bg-gray-50/50">Fecha</th>
                <th className="px-6 py-4 font-semibold bg-gray-50/50">Monto</th>
                <th className="px-6 py-4 font-semibold bg-gray-50/50">Estado</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              <RecentSaleRow id="#00043" name="Héctor Carrascoza" location="Joyabaj, Quiché" date="20 Feb 2026" amount="Q 640.00" status="Completado" initials="HC" color="green" />
              <RecentSaleRow id="#00042" name="Octavio Turuy" location="Sumpango" date="19 Feb 2026" amount="Q 1,250.00" status="Pendiente" initials="OT" color="blue" />
              <RecentSaleRow id="#00041" name="Agroservicio Caleb" location="Sumpango" date="18 Feb 2026" amount="Q 3,420.00" status="Completado" initials="AC" color="purple" />
            </tbody>
          </table>
        </div>
      </div>
    </PageLayout>
  );
}

function KpiCard({ title, value, trend, trendUp, icon, color }: any) {
  const colorMap: any = {
    green: { bg: 'bg-green-50', text: 'text-[#2E7D32]', border: 'border-green-100', trendText: 'text-green-700', trendBg: 'bg-green-50' },
    blue: { bg: 'bg-blue-50', text: 'text-blue-600', border: 'border-blue-100', trendText: 'text-blue-700', trendBg: 'bg-blue-50' },
    red: { bg: 'bg-red-50', text: 'text-red-500', border: 'border-red-100', trendText: 'text-red-700', trendBg: 'bg-red-50' },
    orange: { bg: 'bg-orange-50', text: 'text-orange-500', border: 'border-orange-100', trendText: 'text-gray-500', trendBg: 'bg-gray-100' },
  };
  const c = colorMap[color];

  return (
    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between hover:shadow-md transition-all duration-300">
      <div>
        <p className="text-sm font-medium text-gray-500 mb-1">{title}</p>
        <h3 className="text-2xl font-bold text-gray-800">{value}</h3>
        <div className="flex items-center gap-1 mt-2">
          {trendUp === true && <TrendingUp size={14} className="text-green-600" />}
          {trendUp === false && <AlertTriangle size={14} className="text-red-600" />}
          {trendUp === null && <Minus size={14} className="text-gray-400" />}
          <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${c.trendBg} ${c.trendText}`}>
            {trend}
          </span>
        </div>
      </div>
      <div className={`w-12 h-12 rounded-full ${c.bg} flex items-center justify-center ${c.text} shadow-sm border ${c.border}`}>
        {icon}
      </div>
    </div>
  );
}

function CategoryStat({ name, percentage, color }: any) {
  return (
    <div className="flex items-center justify-between text-sm group p-2 hover:bg-gray-50 rounded-lg transition-colors">
      <div className="flex items-center gap-3">
        <span className={`w-3 h-3 rounded-full ${color} shadow-sm`}></span>
        <span className="text-gray-600 font-medium">{name}</span>
      </div>
      <span className="font-bold text-gray-800">{percentage}</span>
    </div>
  );
}

function RecentSaleRow({ id, name, location, date, amount, status, initials, color }: any) {
  const colorMap: any = {
    green: 'bg-green-100 text-[#2E7D32] border-green-200',
    blue: 'bg-blue-100 text-blue-600 border-blue-200',
    purple: 'bg-purple-100 text-purple-600 border-purple-200',
  };
  const statusColor = status === 'Completado' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-yellow-50 text-yellow-700 border-yellow-200';
  const statusDot = status === 'Completado' ? 'bg-green-500' : 'bg-yellow-500';

  return (
    <tr className="group hover:bg-gray-50 transition-colors">
      <td className="px-6 py-4 font-medium text-gray-900">{id}</td>
      <td className="px-6 py-4">
        <div className="flex items-center gap-3">
          <div className={`w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold border shadow-sm ${colorMap[color]}`}>
            {initials}
          </div>
          <div>
            <div className="font-semibold text-gray-900">{name}</div>
            <div className="text-xs text-gray-500">{location}</div>
          </div>
        </div>
      </td>
      <td className="px-6 py-4 text-gray-500">{date}</td>
      <td className="px-6 py-4 font-bold text-gray-900">{amount}</td>
      <td className="px-6 py-4">
        <span className={`px-2.5 py-1 rounded-full text-xs font-semibold border flex w-fit items-center gap-1 ${statusColor}`}>
          <span className={`w-1.5 h-1.5 rounded-full ${statusDot}`}></span> {status}
        </span>
      </td>
    </tr>
  );
}
