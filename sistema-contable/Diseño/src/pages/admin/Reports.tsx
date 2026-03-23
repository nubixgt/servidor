import React, { useState } from 'react';
import { 
  FileText, 
  Download, 
  Filter, 
  Calendar,
  MapPin,
  TrendingUp,
  TrendingDown,
  MoreVertical,
  CheckCircle2,
  Clock
} from 'lucide-react';
import { cn } from '../../lib/utils';

const mockReports = [
  { id: 'REP-2026-03-01', date: '2026-03-22', location: 'Heladería CC Pradera', type: 'Ingreso', category: 'Venta Diaria', amount: 'GTQ 4,500.00', status: 'Aprobado', user: 'Ana Gómez' },
  { id: 'REP-2026-03-02', date: '2026-03-22', location: 'Casa en Arrendamiento 1', type: 'Ingreso', category: 'Pago de Renta', amount: 'GTQ 5,000.00', status: 'Aprobado', user: 'Admin' },
  { id: 'REP-2026-03-03', date: '2026-03-21', location: 'Heladería Tecpán', type: 'Egreso', category: 'Insumos', amount: 'GTQ 1,250.00', status: 'Pendiente', user: 'María López' },
  { id: 'REP-2026-03-04', date: '2026-03-21', location: 'Local L-01', type: 'Ingreso', category: 'Pago de Renta', amount: 'GTQ 3,500.00', status: 'Aprobado', user: 'Admin' },
  { id: 'REP-2026-03-05', date: '2026-03-20', location: 'Heladería Gasolinera Texaco', type: 'Egreso', category: 'Mantenimiento', amount: 'GTQ 800.00', status: 'Rechazado', user: 'Carlos Ruiz' },
];

export default function AdminReports() {
  const [dateRange, setDateRange] = useState('Este Mes');

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-sans font-bold text-on-surface tracking-tight">Reportes y Consolidación</h1>
          <p className="text-sm text-on-surface-variant mt-1">Genera reportes financieros por locación o portafolio completo.</p>
        </div>
        <div className="flex items-center gap-3">
          <button className="flex items-center gap-2 px-4 py-2 bg-surface-container-low text-on-surface-variant rounded-xl text-sm font-medium hover:bg-surface-container transition-colors border border-outline-variant/30">
            <Download className="w-4 h-4" />
            Exportar Excel
          </button>
          <button className="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-container transition-colors shadow-sm">
            <FileText className="w-4 h-4" />
            Generar PDF
          </button>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="glass-card p-4 flex flex-wrap gap-4 items-end">
        <div className="flex-1 min-w-[200px] space-y-1.5">
          <label className="text-xs font-semibold text-outline uppercase tracking-wider">Rango de Fechas</label>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
            <select 
              className="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all appearance-none text-on-surface"
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
            >
              <option>Hoy</option>
              <option>Esta Semana</option>
              <option>Este Mes</option>
              <option>Mes Anterior</option>
              <option>Este Año</option>
              <option>Personalizado...</option>
            </select>
          </div>
        </div>

        <div className="flex-1 min-w-[200px] space-y-1.5">
          <label className="text-xs font-semibold text-outline uppercase tracking-wider">Locación</label>
          <div className="relative">
            <MapPin className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
            <select className="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all appearance-none text-on-surface">
              <option>Todas las locaciones</option>
              <option>Heladerías (Todas)</option>
              <option>Casas (Todas)</option>
              <option>Propiedad Comercial (Todas)</option>
            </select>
          </div>
        </div>

        <div className="flex-1 min-w-[200px] space-y-1.5">
          <label className="text-xs font-semibold text-outline uppercase tracking-wider">Tipo de Transacción</label>
          <div className="relative">
            <Filter className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
            <select className="w-full pl-10 pr-4 py-2.5 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all appearance-none text-on-surface">
              <option>Todos</option>
              <option>Solo Ingresos</option>
              <option>Solo Egresos</option>
            </select>
          </div>
        </div>

        <button className="px-6 py-2.5 bg-surface-container-high text-on-surface rounded-xl text-sm font-medium hover:bg-surface-container-highest transition-colors h-[42px]">
          Aplicar Filtros
        </button>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="glass-panel p-5 border-l-4 border-l-secondary">
          <div className="flex items-center gap-2 text-on-surface-variant mb-2">
            <TrendingUp className="w-4 h-4 text-secondary" />
            <span className="text-sm font-medium">Total Ingresos</span>
          </div>
          <div className="text-2xl font-bold text-on-surface font-mono">GTQ 13,000.00</div>
        </div>
        <div className="glass-panel p-5 border-l-4 border-l-error">
          <div className="flex items-center gap-2 text-on-surface-variant mb-2">
            <TrendingDown className="w-4 h-4 text-error" />
            <span className="text-sm font-medium">Total Egresos</span>
          </div>
          <div className="text-2xl font-bold text-on-surface font-mono">GTQ 2,050.00</div>
        </div>
        <div className="glass-panel p-5 border-l-4 border-l-primary">
          <div className="flex items-center gap-2 text-on-surface-variant mb-2">
            <FileText className="w-4 h-4 text-primary" />
            <span className="text-sm font-medium">Balance Neto</span>
          </div>
          <div className="text-2xl font-bold text-on-surface font-mono">GTQ 10,950.00</div>
        </div>
      </div>

      {/* Data Table */}
      <div className="glass-card overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-surface-container-low text-on-surface-variant font-medium">
              <tr>
                <th className="px-6 py-4 font-medium">Fecha</th>
                <th className="px-6 py-4 font-medium">Locación</th>
                <th className="px-6 py-4 font-medium">Categoría</th>
                <th className="px-6 py-4 font-medium">Monto</th>
                <th className="px-6 py-4 font-medium">Estado</th>
                <th className="px-6 py-4 font-medium">Usuario</th>
                <th className="px-6 py-4 font-medium text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-outline-variant/20">
              {mockReports.map((report) => (
                <tr key={report.id} className="hover:bg-surface-container-lowest transition-colors group">
                  <td className="px-6 py-4 text-on-surface-variant whitespace-nowrap">{report.date}</td>
                  <td className="px-6 py-4">
                    <div className="font-semibold text-on-surface">{report.location}</div>
                    <div className="text-xs text-outline font-mono">{report.id}</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <span className={cn(
                        "w-2 h-2 rounded-full",
                        report.type === 'Ingreso' ? "bg-secondary" : "bg-error"
                      )} />
                      <span className="text-on-surface-variant">{report.category}</span>
                    </div>
                  </td>
                  <td className={cn(
                    "px-6 py-4 font-mono font-medium",
                    report.type === 'Ingreso' ? "text-secondary" : "text-error"
                  )}>
                    {report.type === 'Ingreso' ? '+' : '-'}{report.amount}
                  </td>
                  <td className="px-6 py-4">
                    <span className={cn(
                      "px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 w-fit",
                      report.status === 'Aprobado' ? "bg-secondary-container text-on-secondary-container" : 
                      report.status === 'Pendiente' ? "bg-tertiary-fixed-dim/20 text-tertiary" : 
                      "bg-error-container text-on-error-container"
                    )}>
                      {report.status === 'Aprobado' && <CheckCircle2 className="w-3 h-3" />}
                      {report.status === 'Pendiente' && <Clock className="w-3 h-3" />}
                      {report.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 text-on-surface-variant">{report.user}</td>
                  <td className="px-6 py-4 text-right">
                    <button className="p-2 text-outline hover:text-on-surface hover:bg-surface-container rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                      <MoreVertical className="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        
        {/* Pagination */}
        <div className="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-surface-container-lowest text-sm text-on-surface-variant">
          <div>Mostrando 5 de 128 registros</div>
          <div className="flex gap-1">
            <button className="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-surface-container disabled:opacity-50">Anterior</button>
            <button className="px-3 py-1 bg-primary text-white rounded-lg">1</button>
            <button className="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-surface-container">2</button>
            <button className="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-surface-container">3</button>
            <button className="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-surface-container">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  );
}
