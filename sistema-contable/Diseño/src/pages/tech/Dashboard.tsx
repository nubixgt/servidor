import React from 'react';
import { 
  ArrowDownToLine, 
  ArrowUpFromLine, 
  History, 
  Package, 
  MapPin, 
  Clock,
  ChevronRight
} from 'lucide-react';
import { cn } from '../../lib/utils';
import { useNavigate } from 'react-router-dom';

const recentActivity = [
  { id: 'TRX-101', type: 'Ingreso', asset: 'Lector de Código', location: 'Almacén Principal', time: '10:30 AM', status: 'Completado' },
  { id: 'TRX-102', type: 'Egreso', asset: 'Caja de Herramientas', location: 'Taller B', time: '09:15 AM', status: 'Completado' },
  { id: 'TRX-103', type: 'Ingreso', asset: 'Taladro Inalámbrico', location: 'Almacén Principal', time: 'Ayer', status: 'Pendiente Revisión' },
];

export default function TechDashboard() {
  const navigate = useNavigate();

  return (
    <div className="space-y-8 max-w-4xl mx-auto">
      <div className="text-center sm:text-left">
        <h1 className="text-3xl font-sans font-bold text-on-surface tracking-tight">Panel de Control</h1>
        <p className="text-base text-on-surface-variant mt-2">¿Qué acción deseas realizar hoy?</p>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <button 
          onClick={() => navigate('/tech/new-ingreso')}
          className="group relative overflow-hidden rounded-3xl bg-white border border-outline-variant/30 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 text-left p-8"
        >
          <div className="absolute top-0 right-0 w-32 h-32 bg-secondary-container rounded-bl-full opacity-20 group-hover:opacity-40 transition-opacity"></div>
          <div className="relative z-10">
            <div className="w-16 h-16 bg-secondary-container text-on-secondary-container rounded-2xl flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform">
              <ArrowDownToLine className="w-8 h-8" />
            </div>
            <h2 className="text-2xl font-bold text-on-surface mb-2">Registrar Ingreso</h2>
            <p className="text-on-surface-variant mb-6">Añade nuevos activos o registra devoluciones al inventario.</p>
            <div className="flex items-center text-secondary font-semibold group-hover:gap-2 transition-all">
              Comenzar <ChevronRight className="w-5 h-5 ml-1" />
            </div>
          </div>
        </button>

        <button 
          onClick={() => navigate('/tech/new-egreso')}
          className="group relative overflow-hidden rounded-3xl bg-white border border-outline-variant/30 shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 text-left p-8"
        >
          <div className="absolute top-0 right-0 w-32 h-32 bg-error-container rounded-bl-full opacity-20 group-hover:opacity-40 transition-opacity"></div>
          <div className="relative z-10">
            <div className="w-16 h-16 bg-error-container text-on-error-container rounded-2xl flex items-center justify-center mb-6 shadow-sm group-hover:scale-110 transition-transform">
              <ArrowUpFromLine className="w-8 h-8" />
            </div>
            <h2 className="text-2xl font-bold text-on-surface mb-2">Registrar Egreso</h2>
            <p className="text-on-surface-variant mb-6">Asigna activos a personal o registra salidas para mantenimiento.</p>
            <div className="flex items-center text-error font-semibold group-hover:gap-2 transition-all">
              Comenzar <ChevronRight className="w-5 h-5 ml-1" />
            </div>
          </div>
        </button>
      </div>

      {/* Recent Activity */}
      <div className="glass-card overflow-hidden mt-8">
        <div className="p-6 border-b border-outline-variant/20 flex items-center justify-between bg-surface-container-lowest">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-primary-fixed text-on-primary-fixed rounded-xl flex items-center justify-center">
              <History className="w-5 h-5" />
            </div>
            <div>
              <h2 className="text-lg font-bold text-on-surface">Mi Actividad Reciente</h2>
              <p className="text-xs text-on-surface-variant">Últimos movimientos registrados por ti</p>
            </div>
          </div>
          <button 
            onClick={() => navigate('/tech/history')}
            className="text-primary text-sm font-medium hover:underline hidden sm:block"
          >
            Ver historial completo
          </button>
        </div>
        
        <div className="divide-y divide-outline-variant/20">
          {recentActivity.map((activity) => (
            <div key={activity.id} className="p-4 sm:p-6 hover:bg-surface-container-lowest transition-colors flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div className="flex items-start sm:items-center gap-4">
                <div className={cn(
                  "w-12 h-12 rounded-full flex items-center justify-center shrink-0 shadow-sm",
                  activity.type === 'Ingreso' ? "bg-secondary-container text-on-secondary-container" : "bg-error-container text-on-error-container"
                )}>
                  <Package className="w-5 h-5" />
                </div>
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <span className="font-bold text-on-surface text-lg">{activity.asset}</span>
                    <span className={cn(
                      "px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider",
                      activity.type === 'Ingreso' ? "bg-secondary/10 text-secondary" : "bg-error/10 text-error"
                    )}>
                      {activity.type}
                    </span>
                  </div>
                  <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-on-surface-variant">
                    <span className="flex items-center gap-1"><MapPin className="w-4 h-4 text-outline" /> {activity.location}</span>
                    <span className="flex items-center gap-1"><Clock className="w-4 h-4 text-outline" /> {activity.time}</span>
                    <span className="text-outline font-mono text-xs">ID: {activity.id}</span>
                  </div>
                </div>
              </div>
              
              <div className="flex items-center justify-between sm:justify-end w-full sm:w-auto mt-2 sm:mt-0 pl-16 sm:pl-0">
                <div className="flex items-center gap-2">
                  <div className={cn(
                    "w-2 h-2 rounded-full",
                    activity.status === 'Completado' ? "bg-secondary" : "bg-tertiary-fixed-dim"
                  )} />
                  <span className="text-sm font-medium text-on-surface-variant">{activity.status}</span>
                </div>
                <button className="sm:hidden text-primary text-sm font-medium">Ver detalles</button>
              </div>
            </div>
          ))}
        </div>
        
        <div className="p-4 bg-surface-container-lowest border-t border-outline-variant/20 sm:hidden">
          <button 
            onClick={() => navigate('/tech/history')}
            className="w-full py-2 text-center text-primary text-sm font-medium hover:bg-primary-fixed/50 rounded-lg transition-colors"
          >
            Ver historial completo
          </button>
        </div>
      </div>
    </div>
  );
}
