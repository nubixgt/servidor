import React from 'react';
import { 
  X, 
  CheckCircle, 
  Clock, 
  ShieldCheck, 
  DollarSign, 
  AlertTriangle, 
  ArrowRight,
  Wrench,
  Laptop,
  Smartphone,
  Tv,
  Gamepad2,
  Terminal,
  LayoutGrid
} from 'lucide-react';
import { ServiceItem } from '../types';

interface ServiceDetailModalProps {
  service: ServiceItem | null;
  isOpen: boolean;
  onClose: () => void;
  onOpenQuote: (serviceTitle: string) => void;
}

export const ServiceDetailModal: React.FC<ServiceDetailModalProps> = ({
  service,
  isOpen,
  onClose,
  onOpenQuote,
}) => {
  if (!isOpen || !service) return null;

  const getIcon = (iconName: string) => {
    const iconClass = "w-6 h-6 text-blue-600";
    switch (iconName) {
      case 'laptop': return <Laptop className={iconClass} />;
      case 'smartphone': return <Smartphone className={iconClass} />;
      case 'tv': return <Tv className={iconClass} />;
      case 'gamepad': return <Gamepad2 className={iconClass} />;
      case 'terminal': return <Terminal className={iconClass} />;
      case 'grid': return <LayoutGrid className={iconClass} />;
      default: return <Wrench className={iconClass} />;
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 flex flex-col">
        
        {/* Header */}
        <div className="p-6 border-b border-slate-100 flex items-start justify-between bg-slate-50/80">
          <div className="flex items-center gap-3.5">
            <div className="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center">
              {getIcon(service.iconName)}
            </div>
            <div>
              <span className="text-xs font-semibold text-blue-600 uppercase tracking-wider">
                Servicio Especializado
              </span>
              <h3 className="text-xl font-bold text-slate-900">{service.title}</h3>
            </div>
          </div>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200/60 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Body */}
        <div className="p-6 space-y-6">
          {/* Detailed description */}
          <div>
            <h4 className="text-sm font-bold text-slate-900 mb-2">Descripción del Servicio</h4>
            <p className="text-slate-600 text-sm leading-relaxed">
              {service.fullDescription}
            </p>
          </div>

          {/* Quick Metrics */}
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div className="p-3.5 bg-blue-50/50 rounded-xl border border-blue-100">
              <span className="text-[11px] text-slate-500 font-medium flex items-center gap-1">
                <Clock className="w-3.5 h-3.5 text-blue-600" />
                Tiempo Estimado
              </span>
              <p className="text-xs font-bold text-slate-900 mt-1">{service.estimatedTime}</p>
            </div>

            <div className="p-3.5 bg-emerald-50/50 rounded-xl border border-emerald-100">
              <span className="text-[11px] text-slate-500 font-medium flex items-center gap-1">
                <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
                Garantía
              </span>
              <p className="text-xs font-bold text-slate-900 mt-1">{service.warranty}</p>
            </div>

            <div className="p-3.5 bg-purple-50/50 rounded-xl border border-purple-100">
              <span className="text-[11px] text-slate-500 font-medium flex items-center gap-1">
                <DollarSign className="w-3.5 h-3.5 text-purple-600" />
                Desde
              </span>
              <p className="text-xs font-bold text-slate-900 mt-1">{service.startingPrice}</p>
            </div>
          </div>

          {/* Included Features */}
          <div className="space-y-2.5">
            <h4 className="text-sm font-bold text-slate-900">¿Qué incluye este servicio?</h4>
            <div className="space-y-2">
              {service.features.map((feature, idx) => (
                <div key={idx} className="flex items-start gap-2.5 text-xs text-slate-700">
                  <CheckCircle className="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                  <span>{feature}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Common issues solved */}
          <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2">
            <h4 className="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wide">
              <AlertTriangle className="w-3.5 h-3.5 text-amber-500" />
              Problemas más comunes que resolvemos
            </h4>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600">
              {service.commonIssues.map((issue, idx) => (
                <div key={idx} className="flex items-center gap-2">
                  <span className="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0" />
                  <span>{issue}</span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 sm:p-6 border-t border-slate-100 bg-slate-50/80 flex items-center justify-between">
          <button
            onClick={onClose}
            className="px-4 py-2 text-xs font-medium text-slate-600 hover:text-slate-900 cursor-pointer"
          >
            Volver
          </button>
          <button
            onClick={() => {
              onClose();
              onOpenQuote(service.title);
            }}
            className="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-5 py-2.5 rounded-xl shadow-xs hover:shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span>Pedir presupuesto para {service.title}</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </button>
        </div>

      </div>
    </div>
  );
};
