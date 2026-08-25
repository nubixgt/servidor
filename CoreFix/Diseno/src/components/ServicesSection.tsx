import React from 'react';
import { 
  Laptop, 
  Smartphone, 
  Tv, 
  Gamepad2, 
  Terminal, 
  LayoutGrid, 
  Wrench,
  ArrowRight
} from 'lucide-react';
import { SERVICES_DATA } from '../data/mockData';
import { ServiceItem } from '../types';

interface ServicesSectionProps {
  onSelectService: (service: ServiceItem) => void;
  onOpenQuoteWithService: (serviceTitle: string) => void;
}

export const ServicesSection: React.FC<ServicesSectionProps> = ({
  onSelectService,
  onOpenQuoteWithService,
}) => {
  const getIcon = (iconName: string) => {
    const iconClass = "w-7 h-7 text-blue-600";
    switch (iconName) {
      case 'laptop':
        return <Laptop className={iconClass} strokeWidth={2} />;
      case 'smartphone':
        return <Smartphone className={iconClass} strokeWidth={2} />;
      case 'tv':
        return <Tv className={iconClass} strokeWidth={2} />;
      case 'gamepad':
        return <Gamepad2 className={iconClass} strokeWidth={2} />;
      case 'terminal':
        return <Terminal className={iconClass} strokeWidth={2} />;
      case 'grid':
        return <LayoutGrid className={iconClass} strokeWidth={2} />;
      case 'wrench':
        return <Wrench className={iconClass} strokeWidth={2} />;
      default:
        return <Wrench className={iconClass} strokeWidth={2} />;
    }
  };

  // Divide services into top standard 6 cards (4-col / 2-row layout) and bottom wide maintenance card
  const topServices = SERVICES_DATA.slice(0, 6);
  const wideService = SERVICES_DATA[6]; // Mantenimiento Preventivo

  return (
    <section id="servicios-section" className="py-16 sm:py-24 bg-slate-50/60 border-b border-slate-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {/* Section Header matching the reference screenshot */}
        <div className="text-center max-w-3xl mx-auto mb-14">
          <h2 className="text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight">
            Nuestros Servicios
          </h2>
          <p className="mt-3.5 text-base sm:text-lg text-slate-600">
            Soluciones integrales de hardware y software para mantener tu ecosistema digital funcionando sin interrupciones.
          </p>
        </div>

        {/* Services Grid matching exact cards layout */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          
          {/* Top 6 Standard Cards */}
          {topServices.map((service) => (
            <div
              key={service.id}
              id={`service-card-${service.id}`}
              className="bg-white rounded-2xl p-7 border border-slate-200 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
            >
              <div className="space-y-4">
                <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-105 transition-transform">
                  {getIcon(service.iconName)}
                </div>

                <h3 className="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                  {service.title}
                </h3>

                <p className="text-slate-600 text-sm leading-relaxed">
                  {service.shortDescription}
                </p>
              </div>

              <div className="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
                <button
                  onClick={() => onSelectService(service)}
                  className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer"
                >
                  <span>Ver detalles</span>
                  <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                </button>
                <button
                  onClick={() => onOpenQuoteWithService(service.title)}
                  className="text-xs font-medium text-slate-500 hover:text-slate-900 hover:underline cursor-pointer"
                >
                  Cotizar
                </button>
              </div>
            </div>
          ))}

          {/* Bottom row: Mantenimiento Preventivo wide card spanning 2 columns on lg */}
          {wideService && (
            <div
              id={`service-card-${wideService.id}`}
              className="sm:col-span-2 lg:col-span-2 bg-white rounded-2xl p-7 border border-slate-200 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
            >
              <div className="space-y-4">
                <div className="flex items-center gap-3">
                  <div className="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center group-hover:scale-105 transition-transform shrink-0">
                    {getIcon(wideService.iconName)}
                  </div>
                  <div>
                    <h3 className="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                      {wideService.title}
                    </h3>
                    <span className="text-xs text-blue-600 font-medium">Recomendado cada 6 a 12 meses</span>
                  </div>
                </div>

                <p className="text-slate-600 text-sm leading-relaxed">
                  {wideService.shortDescription}
                </p>
              </div>

              <div className="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
                <button
                  onClick={() => onSelectService(wideService)}
                  className="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1 cursor-pointer"
                >
                  <span>Ver detalles y protocolo térmico</span>
                  <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                </button>
                <button
                  onClick={() => onOpenQuoteWithService(wideService.title)}
                  className="bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium px-3.5 py-1.5 rounded-lg text-xs transition-colors cursor-pointer"
                >
                  Solicitar Mantenimiento
                </button>
              </div>
            </div>
          )}

          {/* Complementary Quick Diagnostic card to fill grid balance gracefully */}
          <div className="sm:col-span-2 lg:col-span-2 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-7 text-white shadow-md flex flex-col justify-between">
            <div className="space-y-3">
              <div className="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/20 rounded-full text-xs font-medium text-blue-50">
                <span>¿No sabes exactamente qué tiene tu equipo?</span>
              </div>
              <h3 className="text-xl font-bold text-white">
                Diagnóstico y Presupuesto Sin Cargo
              </h3>
              <p className="text-blue-100 text-sm leading-relaxed">
                Trae tu dispositivo a nuestro laboratorio o solicita retiro. Desarmamos, testeamos voltajes con instrumental de precisión y te damos un presupuesto exacto antes de realizar cualquier trabajo.
              </p>
            </div>
            <div className="pt-5 mt-4 border-t border-white/20 flex items-center justify-between">
              <span className="text-xs font-medium text-blue-100">Sin compromiso de reparación</span>
              <button
                onClick={() => onOpenQuoteWithService('Diagnóstico General')}
                className="bg-white text-blue-700 hover:bg-blue-50 font-semibold px-4 py-2 rounded-xl text-xs shadow-sm transition-all cursor-pointer"
              >
                Traer a Diagnóstico
              </button>
            </div>
          </div>

        </div>

      </div>
    </section>
  );
};
