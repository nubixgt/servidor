import React from 'react';
import { 
  TrendingUp, 
  MapPin, 
  ArrowUpRight, 
  ArrowDownRight,
  MoreVertical,
  BarChart3,
  Building2,
  AlertCircle,
  CheckCircle2,
  XCircle,
  Clock
} from 'lucide-react';
import { cn } from '../../lib/utils';
import { useNavigate } from 'react-router-dom';

const kpis = [
  { title: 'Ingresos Totales (Mes)', value: 'GTQ 45,230', change: '+12%', isPositive: true, icon: TrendingUp },
  { title: 'Ocupación Comercial', value: '15/18', change: '83%', isPositive: true, icon: Building2 },
  { title: 'Alertas de Cobro', value: '3', change: '+1', isPositive: false, icon: AlertCircle },
  { title: 'Locaciones Activas', value: '7', change: '0', isPositive: true, icon: MapPin },
];

const recentTransactions = [
  { id: 'TRX-001', type: 'Ingreso', category: 'Venta Diaria', location: 'Heladería CC Pradera', user: 'Ana Gómez', date: 'Hace 2 horas', status: 'Pendiente' },
  { id: 'TRX-002', type: 'Egreso', category: 'Mantenimiento', location: 'Casa en Arrendamiento 1', user: 'Carlos Ruiz', date: 'Hace 5 horas', status: 'Aprobado' },
  { id: 'TRX-003', type: 'Ingreso', category: 'Pago de Renta', location: 'Local L-01', user: 'Admin', date: 'Ayer', status: 'Aprobado' },
  { id: 'TRX-004', type: 'Egreso', category: 'Insumos', location: 'Heladería Tecpán', user: 'María López', date: 'Ayer', status: 'Rechazado' },
];

const pendingLeases = [
  { id: 'L-02', tenant: 'Cafetería El Grano', amount: 'GTQ 3,500', daysOverdue: 5 },
  { id: 'B-04', tenant: 'Logística S.A.', amount: 'GTQ 2,000', daysOverdue: 2 },
  { id: 'A-01', tenant: 'Familia Pérez', amount: 'GTQ 5,000', daysOverdue: 0, dueToday: true },
];

export default function AdminDashboard() {
  const navigate = useNavigate();

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-sans font-bold text-on-surface tracking-tight">Dashboard General</h1>
          <p className="text-sm text-on-surface-variant mt-1">Resumen financiero y estado del portafolio inmobiliario.</p>
        </div>
        <div className="flex items-center gap-3">
          <button 
            onClick={() => navigate('/admin/reports')}
            className="px-4 py-2 bg-surface-container-low text-on-surface-variant rounded-xl text-sm font-medium hover:bg-surface-container transition-colors border border-outline-variant/30"
          >
            Descargar Reporte
          </button>
          <button 
            onClick={() => navigate('/admin/new')}
            className="px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-container transition-colors shadow-sm"
          >
            Nueva Transacción
          </button>
        </div>
      </div>

      {/* KPIs */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {kpis.map((kpi, index) => (
          <div key={index} className="glass-card p-5">
            <div className="flex items-center justify-between mb-4">
              <div className={cn(
                "w-10 h-10 rounded-xl flex items-center justify-center",
                kpi.title === 'Alertas de Cobro' ? "bg-error-container text-on-error-container" : "bg-primary-fixed text-on-primary-fixed"
              )}>
                <kpi.icon className="w-5 h-5" />
              </div>
              <div className={cn(
                "flex items-center text-xs font-semibold px-2 py-1 rounded-full",
                kpi.isPositive ? "bg-secondary-container text-on-secondary-container" : "bg-error-container text-on-error-container"
              )}>
                {kpi.isPositive ? <ArrowUpRight className="w-3 h-3 mr-1" /> : <ArrowDownRight className="w-3 h-3 mr-1" />}
                {kpi.change}
              </div>
            </div>
            <h3 className="text-2xl font-bold text-on-surface mb-1">{kpi.value}</h3>
            <p className="text-sm text-on-surface-variant">{kpi.title}</p>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Chart Placeholder */}
        <div className="lg:col-span-2 glass-card p-6 flex flex-col">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-lg font-bold text-on-surface">Ingresos vs Egresos</h2>
            <select className="bg-surface-container-low border-none text-sm rounded-lg px-3 py-1.5 text-on-surface-variant outline-none">
              <option>Este mes</option>
              <option>Mes anterior</option>
              <option>Este año</option>
            </select>
          </div>
          <div className="flex-1 bg-surface-container-lowest rounded-xl border border-outline-variant/30 flex items-center justify-center min-h-[300px]">
            <p className="text-outline text-sm flex items-center gap-2">
              <BarChart3 className="w-5 h-5" />
              Gráfico de tendencias de ingresos y egresos
            </p>
          </div>
        </div>

        {/* Alerts & Pending */}
        <div className="glass-card p-6 flex flex-col">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-lg font-bold text-on-surface flex items-center gap-2">
              <AlertCircle className="w-5 h-5 text-error" />
              Alertas de Arrendamiento
            </h2>
          </div>
          <div className="flex-1 space-y-4">
            {pendingLeases.map((lease) => (
              <div key={lease.id} className="flex items-start gap-4 p-3 rounded-xl bg-error-container/30 border border-error/20 transition-colors">
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between mb-1">
                    <p className="text-sm font-bold text-on-surface truncate">{lease.tenant}</p>
                    <span className="text-xs font-mono font-bold text-error">{lease.amount}</span>
                  </div>
                  <p className="text-xs text-on-surface-variant truncate">Unidad: {lease.id}</p>
                  <div className="mt-2 flex items-center gap-1.5 text-xs font-medium text-error">
                    <Clock className="w-3.5 h-3.5" />
                    {lease.dueToday ? 'Vence hoy' : `${lease.daysOverdue} días de atraso`}
                  </div>
                </div>
              </div>
            ))}
            <button 
              onClick={() => navigate('/admin/locations')}
              className="w-full py-2 mt-2 text-center text-primary text-sm font-medium hover:bg-primary-fixed/50 rounded-lg transition-colors border border-primary/20"
            >
              Ver todas las locaciones
            </button>
          </div>
        </div>
      </div>

      {/* Data Table */}
      <div className="glass-card overflow-hidden">
        <div className="p-6 border-b border-outline-variant/20 flex items-center justify-between">
          <div>
            <h2 className="text-lg font-bold text-on-surface">Transacciones Recientes</h2>
            <p className="text-xs text-on-surface-variant mt-1">Requieren revisión y aprobación del administrador.</p>
          </div>
          <div className="flex items-center gap-2">
            <button 
              onClick={() => navigate('/admin/reports')}
              className="text-primary text-sm font-medium hover:underline"
            >
              Ver todas
            </button>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-surface-container-low text-on-surface-variant font-medium">
              <tr>
                <th className="px-6 py-4 font-medium">ID</th>
                <th className="px-6 py-4 font-medium">Tipo</th>
                <th className="px-6 py-4 font-medium">Categoría</th>
                <th className="px-6 py-4 font-medium">Locación</th>
                <th className="px-6 py-4 font-medium">Técnico/Usuario</th>
                <th className="px-6 py-4 font-medium">Estado</th>
                <th className="px-6 py-4 font-medium text-right">Acción</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-outline-variant/20">
              {recentTransactions.map((trx) => (
                <tr key={trx.id} className="hover:bg-surface-container-lowest transition-colors group">
                  <td className="px-6 py-4 font-medium text-on-surface font-mono text-xs">{trx.id}</td>
                  <td className="px-6 py-4">
                    <span className={cn(
                      "px-2.5 py-1 rounded-full text-xs font-medium",
                      trx.type === 'Ingreso' ? "bg-secondary-container text-on-secondary-container" : "bg-error-container text-on-error-container"
                    )}>
                      {trx.type}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-on-surface-variant">{trx.category}</td>
                  <td className="px-6 py-4 text-on-surface-variant">{trx.location}</td>
                  <td className="px-6 py-4 text-on-surface-variant">
                    <div className="flex items-center gap-2">
                      <div className="w-6 h-6 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center text-[10px] font-bold">
                        {trx.user.charAt(0)}
                      </div>
                      {trx.user}
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={cn(
                      "px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 w-fit",
                      trx.status === 'Aprobado' ? "bg-secondary-container text-on-secondary-container" : 
                      trx.status === 'Pendiente' ? "bg-tertiary-fixed-dim/20 text-tertiary" : 
                      "bg-error-container text-on-error-container"
                    )}>
                      {trx.status === 'Aprobado' && <CheckCircle2 className="w-3 h-3" />}
                      {trx.status === 'Pendiente' && <Clock className="w-3 h-3" />}
                      {trx.status === 'Rechazado' && <XCircle className="w-3 h-3" />}
                      {trx.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-right">
                    {trx.status === 'Pendiente' ? (
                      <div className="flex items-center justify-end gap-2">
                        <button className="p-1.5 text-secondary hover:bg-secondary-container rounded-lg transition-colors" title="Aprobar">
                          <CheckCircle2 className="w-5 h-5" />
                        </button>
                        <button className="p-1.5 text-error hover:bg-error-container rounded-lg transition-colors" title="Rechazar">
                          <XCircle className="w-5 h-5" />
                        </button>
                      </div>
                    ) : (
                      <button className="p-2 text-outline hover:text-on-surface hover:bg-surface-container rounded-lg transition-colors">
                        <MoreVertical className="w-4 h-4" />
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
