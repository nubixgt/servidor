import React, { useState } from 'react';
import { 
  SERVICES_DATA, 
  FAQS 
} from '../data/mockData';
import { ServiceItem } from '../types';
import { 
  Laptop, 
  Smartphone, 
  Tv, 
  Gamepad2, 
  Terminal, 
  LayoutGrid, 
  Wrench, 
  ArrowRight,
  Calculator,
  ShieldCheck,
  CheckCircle,
  HelpCircle,
  ChevronDown,
  ChevronUp,
  Sparkles
} from 'lucide-react';

interface ServicesViewProps {
  onSelectService: (service: ServiceItem) => void;
  onOpenQuoteWithService: (serviceTitle: string) => void;
}

export const ServicesView: React.FC<ServicesViewProps> = ({
  onSelectService,
  onOpenQuoteWithService,
}) => {
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [openFaqIndex, setOpenFaqIndex] = useState<number | null>(0);

  // Interactive Quick Calculator state
  const [calcDevice, setCalcDevice] = useState('laptop');
  const [calcIssue, setCalcIssue] = useState('screen');
  const [calcUrgency, setCalcUrgency] = useState('normal');

  const categories = [
    { id: 'all', label: 'Todos los Servicios' },
    { id: 'hardware', label: 'PC & Notebooks' },
    { id: 'mobile', label: 'Smartphones & Tablets' },
    { id: 'displays', label: 'Televisores & Pantallas' },
    { id: 'gaming', label: 'Consolas & Mandos' },
    { id: 'software', label: 'Software & SO' },
    { id: 'maintenance', label: 'Mantenimiento Térmico' },
  ];

  const filteredServices = selectedCategory === 'all'
    ? SERVICES_DATA
    : SERVICES_DATA.filter(s => s.category === selectedCategory);

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

  // Calculator price computation
  const calculateEstimate = () => {
    let base = 25000;
    if (calcDevice === 'smartphone') {
      base = calcIssue === 'screen' ? 28000 : calcIssue === 'battery' ? 18000 : 32000;
    } else if (calcDevice === 'laptop') {
      base = calcIssue === 'thermal' ? 22000 : calcIssue === 'screen' ? 45000 : 35000;
    } else if (calcDevice === 'console') {
      base = calcIssue === 'hdmi' ? 32000 : calcIssue === 'thermal' ? 28000 : 25000;
    } else if (calcDevice === 'tv') {
      base = calcIssue === 'backlight' ? 38000 : 45000;
    }

    if (calcUrgency === 'express') {
      base += 8000;
    }

    return `$${base.toLocaleString('es-AR')}`;
  };

  return (
    <div className="py-12 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto space-y-4">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold">
            <Sparkles className="w-3.5 h-3.5 text-blue-600" />
            <span>Soluciones Especializadas con Instrumental de Precisión</span>
          </div>
          <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight">
            Catálogo Integral de Reparación
          </h1>
          <p className="text-slate-600 text-base sm:text-lg">
            Diagnóstico con osciloscopio, microsoldadura BGA, repuestos originales y protocolos de testeo industrial para que tu equipo rinda al máximo.
          </p>
        </div>

        {/* Category Filters */}
        <div className="flex items-center justify-start sm:justify-center gap-2 overflow-x-auto pb-2 scrollbar-none">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => setSelectedCategory(cat.id)}
              className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap cursor-pointer ${
                selectedCategory === cat.id
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              }`}
            >
              {cat.label}
            </button>
          ))}
        </div>

        {/* Services Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredServices.map((service) => (
            <div
              key={service.id}
              className="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200 shadow-xs hover:shadow-lg hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group"
            >
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center group-hover:scale-105 transition-transform">
                    {getIcon(service.iconName)}
                  </div>
                  <span className="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                    {service.warranty}
                  </span>
                </div>

                <div>
                  <h3 className="text-xl font-bold text-slate-900 group-hover:text-blue-600 transition-colors">
                    {service.title}
                  </h3>
                  <p className="text-xs text-blue-600 font-medium mt-0.5">
                    Tiempo estimado: {service.estimatedTime}
                  </p>
                </div>

                <p className="text-slate-600 text-sm leading-relaxed">
                  {service.shortDescription}
                </p>

                {/* Features list */}
                <div className="space-y-1.5 pt-2">
                  {service.features.slice(0, 3).map((feat, idx) => (
                    <div key={idx} className="flex items-start gap-2 text-xs text-slate-600">
                      <CheckCircle className="w-3.5 h-3.5 text-blue-600 shrink-0 mt-0.5" />
                      <span className="line-clamp-1">{feat}</span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="pt-6 mt-6 border-t border-slate-100 flex items-center justify-between">
                <div>
                  <span className="text-[11px] text-slate-400 block">Tarifa base desde</span>
                  <span className="text-base font-bold text-slate-900">{service.startingPrice}</span>
                </div>
                
                <div className="flex gap-2">
                  <button
                    onClick={() => onSelectService(service)}
                    className="text-xs font-semibold text-slate-700 hover:text-blue-600 px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer"
                  >
                    Detalles
                  </button>
                  <button
                    onClick={() => onOpenQuoteWithService(service.title)}
                    className="text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-2 rounded-lg shadow-xs cursor-pointer"
                  >
                    Cotizar
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>

        {/* Interactive Estimator / Calculator */}
        <div className="bg-slate-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <div className="lg:col-span-6 space-y-4">
              <div className="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-xs font-semibold border border-blue-400/30">
                <Calculator className="w-3.5 h-3.5 text-blue-400" />
                <span>Simulador de Presupuesto Estimado</span>
              </div>
              <h3 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Calcula el costo aproximado de tu reparación
              </h3>
              <p className="text-slate-300 text-sm leading-relaxed">
                Selecciona tu tipo de dispositivo y falla principal para obtener una referencia de costo con repuestos y mano de obra garantizada.
              </p>

              <div className="space-y-4 pt-2">
                <div>
                  <label className="block text-xs font-semibold text-slate-300 mb-1.5">1. Dispositivo</label>
                  <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    {[
                      { id: 'laptop', label: 'Notebook / PC' },
                      { id: 'smartphone', label: 'Smartphone' },
                      { id: 'console', label: 'Consola' },
                      { id: 'tv', label: 'Smart TV' },
                    ].map((item) => (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => setCalcDevice(item.id)}
                        className={`py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer text-center ${
                          calcDevice === item.id
                            ? 'bg-blue-600 border-blue-500 text-white font-bold'
                            : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                        }`}
                      >
                        {item.label}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-300 mb-1.5">2. Tipo de Falla / Trabajo</label>
                  <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    {[
                      { id: 'screen', label: 'Pantalla / Vidrio' },
                      { id: 'thermal', label: 'Mantenimiento Térmico' },
                      { id: 'battery', label: 'Batería / Carga' },
                      { id: 'hdmi', label: 'Puerto / Conector' },
                      { id: 'board', label: 'Placa / No Enciende' },
                    ].map((item) => (
                      <button
                        key={item.id}
                        type="button"
                        onClick={() => setCalcIssue(item.id)}
                        className={`py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer text-center ${
                          calcIssue === item.id
                            ? 'bg-blue-600 border-blue-500 text-white font-bold'
                            : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                        }`}
                      >
                        {item.label}
                      </button>
                    ))}
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-300 mb-1.5">3. Modalidad de Atención</label>
                  <div className="grid grid-cols-2 gap-2">
                    <button
                      type="button"
                      onClick={() => setCalcUrgency('normal')}
                      className={`py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer ${
                        calcUrgency === 'normal'
                          ? 'bg-blue-600 border-blue-500 text-white font-bold'
                          : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                      }`}
                    >
                      Estándar (24 a 48 hs)
                    </button>
                    <button
                      type="button"
                      onClick={() => setCalcUrgency('express')}
                      className={`py-2 px-3 rounded-xl text-xs font-medium border transition-colors cursor-pointer ${
                        calcUrgency === 'express'
                          ? 'bg-blue-600 border-blue-500 text-white font-bold'
                          : 'bg-slate-800/80 border-slate-700 text-slate-300 hover:bg-slate-800'
                      }`}
                    >
                      ⚡ Express en el Día (+ $8.000)
                    </button>
                  </div>
                </div>
              </div>
            </div>

            {/* Calculated Result Box */}
            <div className="lg:col-span-6 bg-slate-800/90 rounded-2xl p-6 sm:p-8 border border-slate-700 text-center space-y-4">
              <span className="text-xs font-semibold text-blue-400 uppercase tracking-widest block">
                Presupuesto Estimado
              </span>
              
              <div className="text-4xl sm:text-5xl font-black text-white tracking-tight">
                {calculateEstimate()}
              </div>

              <p className="text-xs text-slate-400 max-w-sm mx-auto">
                Incluye diagnóstico previo, repuestos testeados, mano de obra especializada y garantía escrita de 90 a 180 días.
              </p>

              <div className="pt-2">
                <button
                  onClick={() => onOpenQuoteWithService(`Cotización Calculador: ${calcDevice} - ${calcIssue}`)}
                  className="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 px-6 rounded-xl text-sm transition-all duration-200 shadow-md cursor-pointer flex items-center justify-center gap-2"
                >
                  <span>Reservar Turno con este Presupuesto</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              </div>
            </div>

          </div>
        </div>

        {/* FAQs Section */}
        <div className="max-w-3xl mx-auto space-y-6">
          <div className="text-center space-y-2">
            <h3 className="text-2xl sm:text-3xl font-bold text-slate-900">Preguntas Frecuentes</h3>
            <p className="text-slate-600 text-sm">Respuestas claras sobre nuestro proceso de trabajo y garantías</p>
          </div>

          <div className="space-y-3">
            {FAQS.map((faq, idx) => {
              const isOpen = openFaqIndex === idx;
              return (
                <div
                  key={idx}
                  className="border border-slate-200 rounded-xl overflow-hidden bg-white shadow-2xs"
                >
                  <button
                    onClick={() => setOpenFaqIndex(isOpen ? null : idx)}
                    className="w-full p-4 text-left font-semibold text-sm text-slate-900 flex justify-between items-center gap-4 hover:bg-slate-50 transition-colors cursor-pointer"
                  >
                    <span>{faq.question}</span>
                    {isOpen ? <ChevronUp className="w-4 h-4 text-blue-600 shrink-0" /> : <ChevronDown className="w-4 h-4 text-slate-400 shrink-0" />}
                  </button>
                  {isOpen && (
                    <div className="px-4 pb-4 pt-1 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 bg-slate-50/50">
                      {faq.answer}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

      </div>
    </div>
  );
};
