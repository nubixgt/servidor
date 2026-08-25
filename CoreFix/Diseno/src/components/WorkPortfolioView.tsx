import React, { useState } from 'react';
import { PORTFOLIO_ITEMS } from '../data/mockData';
import { PortfolioItem } from '../types';
import { 
  CheckCircle2, 
  Clock, 
  ShieldCheck, 
  Sparkles, 
  Wrench, 
  Split, 
  SlidersHorizontal,
  ArrowRight,
  Zap,
  Activity
} from 'lucide-react';

interface WorkPortfolioViewProps {
  onOpenQuoteWithDevice: (device: string) => void;
}

export const WorkPortfolioView: React.FC<WorkPortfolioViewProps> = ({
  onOpenQuoteWithDevice,
}) => {
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [activeItem, setActiveItem] = useState<PortfolioItem>(PORTFOLIO_ITEMS[0]);
  const [sliderPosition, setSliderPosition] = useState<number>(50);
  const [compareMode, setCompareMode] = useState<'slider' | 'side-by-side'>('slider');

  const categories = [
    { id: 'all', label: 'Todos los Trabajos' },
    { id: 'Consolas', label: 'Consolas' },
    { id: 'PC & Laptops', label: 'PC & Laptops' },
    { id: 'Smartphones', label: 'Smartphones' },
    { id: 'Televisores', label: 'Televisores' },
  ];

  const filteredItems = selectedCategory === 'all'
    ? PORTFOLIO_ITEMS
    : PORTFOLIO_ITEMS.filter(item => item.category === selectedCategory);

  return (
    <div className="py-12 bg-slate-50/50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto space-y-3">
          <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold">
            <Sparkles className="w-3.5 h-3.5 text-blue-600" />
            <span>Casos Reales de Éxito en Laboratorio</span>
          </div>
          <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 tracking-tight">
            Trabajos y Reparaciones Realizadas
          </h1>
          <p className="text-slate-600 text-base sm:text-lg">
            Conoce el antes y el después de nuestros procedimientos técnicos de alta precisión con instrumental de grado industrial.
          </p>
        </div>

        {/* Categories */}
        <div className="flex items-center justify-center gap-2 overflow-x-auto pb-2">
          {categories.map((cat) => (
            <button
              key={cat.id}
              onClick={() => {
                setSelectedCategory(cat.id);
                const first = cat.id === 'all' 
                  ? PORTFOLIO_ITEMS[0] 
                  : PORTFOLIO_ITEMS.find(i => i.category === cat.id) || PORTFOLIO_ITEMS[0];
                setActiveItem(first);
              }}
              className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer ${
                selectedCategory === cat.id
                  ? 'bg-blue-600 text-white shadow-xs'
                  : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'
              }`}
            >
              {cat.label}
            </button>
          ))}
        </div>

        {/* Featured Interactive Before & After Showcase */}
        <div className="bg-white rounded-3xl p-6 sm:p-8 lg:p-10 border border-slate-200 shadow-xl space-y-8">
          
          <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 pb-6 border-b border-slate-100">
            <div>
              <span className="text-xs font-bold text-blue-600 uppercase tracking-wider">
                {activeItem.category} • {activeItem.device}
              </span>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                {activeItem.title}
              </h2>
            </div>

            <div className="flex items-center gap-2">
              <button
                onClick={() => setCompareMode('slider')}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer transition-colors ${
                  compareMode === 'slider'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                <SlidersHorizontal className="w-3.5 h-3.5" />
                Deslizador Interactivo
              </button>
              <button
                onClick={() => setCompareMode('side-by-side')}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold flex items-center gap-1.5 cursor-pointer transition-colors ${
                  compareMode === 'side-by-side'
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                <Split className="w-3.5 h-3.5" />
                Lado a Lado
              </button>
            </div>
          </div>

          {/* Before / After Visual Box */}
          {compareMode === 'slider' ? (
            <div className="space-y-3">
              <div className="relative h-[320px] sm:h-[420px] lg:h-[480px] rounded-2xl overflow-hidden select-none bg-slate-900 border border-slate-200 shadow-inner">
                {/* AFTER IMAGE (Background) */}
                <img
                  src={activeItem.afterImage}
                  alt={activeItem.afterLabel || "Después de la reparación"}
                  className="absolute inset-0 w-full h-full object-cover"
                  referrerPolicy="no-referrer"
                />
                <div className="absolute top-4 right-4 bg-emerald-600/90 text-white text-xs font-bold px-3 py-1 rounded-full backdrop-blur-xs shadow-md z-10">
                  {activeItem.afterLabel || "DESPUÉS (Reparado)"}
                </div>

                {/* BEFORE IMAGE (Clipped with slider width) */}
                <div
                  className="absolute inset-0 overflow-hidden"
                  style={{ width: `${sliderPosition}%` }}
                >
                  <img
                    src={activeItem.beforeImage}
                    alt={activeItem.beforeLabel || "Antes de la reparación"}
                    className="absolute inset-0 w-full h-full object-cover max-w-none"
                    style={{ width: '100%', height: '100%', minWidth: '100%' }}
                    referrerPolicy="no-referrer"
                  />
                  <div className="absolute top-4 left-4 bg-rose-600/90 text-white text-xs font-bold px-3 py-1 rounded-full backdrop-blur-xs shadow-md z-10">
                    {activeItem.beforeLabel || "ANTES (Dañado)"}
                  </div>
                </div>

                {/* Vertical Divider line */}
                <div
                  className="absolute top-0 bottom-0 w-1 bg-white shadow-2xl z-20 pointer-events-none"
                  style={{ left: `calc(${sliderPosition}% - 2px)` }}
                >
                  <div className="absolute top-1/2 -translate-y-1/2 -left-3.5 w-8 h-8 rounded-full bg-white text-slate-800 shadow-xl flex items-center justify-center border-2 border-blue-600 font-bold text-xs">
                    ↔
                  </div>
                </div>

                {/* Hidden range input for natural drag control */}
                <input
                  type="range"
                  min="0"
                  max="100"
                  value={sliderPosition}
                  onChange={(e) => setSliderPosition(Number(e.target.value))}
                  className="absolute inset-0 opacity-0 cursor-ew-resize w-full h-full z-30"
                  aria-label="Deslizar para comparar antes y después"
                />
              </div>

              <p className="text-center text-xs text-slate-500 italic">
                ← Desliza el cursor o pulsa para comparar el resultado antes y después de la intervención →
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <div className="relative h-[260px] sm:h-[340px] rounded-2xl overflow-hidden border border-slate-200">
                  <img
                    src={activeItem.beforeImage}
                    alt={activeItem.beforeLabel || "Antes"}
                    className="w-full h-full object-cover"
                    referrerPolicy="no-referrer"
                  />
                  <div className="absolute top-3 left-3 bg-rose-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                    ANTES
                  </div>
                </div>
                <p className="text-xs font-semibold text-rose-700">{activeItem.beforeLabel}</p>
              </div>

              <div className="space-y-2">
                <div className="relative h-[260px] sm:h-[340px] rounded-2xl overflow-hidden border border-slate-200">
                  <img
                    src={activeItem.afterImage}
                    alt={activeItem.afterLabel || "Después"}
                    className="w-full h-full object-cover"
                    referrerPolicy="no-referrer"
                  />
                  <div className="absolute top-3 right-3 bg-emerald-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                    DESPUÉS
                  </div>
                </div>
                <p className="text-xs font-semibold text-emerald-700">{activeItem.afterLabel}</p>
              </div>
            </div>
          )}

          {/* Technical Case Study Breakdown */}
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 pt-4">
            
            <div className="lg:col-span-8 space-y-5">
              <div>
                <h4 className="text-sm font-bold text-slate-900 uppercase tracking-wider text-rose-700">
                  Diagnóstico Inicial
                </h4>
                <p className="mt-1 text-sm text-slate-700 leading-relaxed">
                  {activeItem.problem}
                </p>
              </div>

              <div>
                <h4 className="text-sm font-bold text-slate-900 uppercase tracking-wider text-blue-700">
                  Procedimiento Técnico Aplicado
                </h4>
                <p className="mt-1 text-sm text-slate-700 leading-relaxed">
                  {activeItem.solution}
                </p>
              </div>

              <div className="space-y-2">
                <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">
                  Detalles y Controles Realizados
                </h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  {activeItem.details.map((detail, idx) => (
                    <div key={idx} className="flex items-start gap-2 text-xs text-slate-600">
                      <CheckCircle2 className="w-4 h-4 text-blue-600 shrink-0 mt-0.5" />
                      <span>{detail}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Right Meta Box */}
            <div className="lg:col-span-4 bg-slate-50 rounded-2xl p-5 border border-slate-200 space-y-4 flex flex-col justify-between">
              <div className="space-y-3">
                <h4 className="text-xs font-bold text-slate-900 uppercase tracking-wider">
                  Ficha Técnica de Orden
                </h4>

                <div className="space-y-2.5 text-xs">
                  <div className="flex justify-between items-center py-1 border-b border-slate-200/70">
                    <span className="text-slate-500">Tiempo de Reparación:</span>
                    <span className="font-bold text-slate-900 flex items-center gap-1">
                      <Clock className="w-3.5 h-3.5 text-blue-600" />
                      {activeItem.turnaround}
                    </span>
                  </div>

                  <div className="flex justify-between items-center py-1 border-b border-slate-200/70">
                    <span className="text-slate-500">Garantía Otorgada:</span>
                    <span className="font-bold text-emerald-700 flex items-center gap-1">
                      <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
                      {activeItem.warranty}
                    </span>
                  </div>

                  <div className="flex justify-between items-center py-1 border-b border-slate-200/70">
                    <span className="text-slate-500">Control de Calidad:</span>
                    <span className="font-bold text-blue-700 flex items-center gap-1">
                      <Activity className="w-3.5 h-3.5 text-blue-600" />
                      Aprobado 100%
                    </span>
                  </div>
                </div>
              </div>

              <div className="pt-2">
                <button
                  onClick={() => onOpenQuoteWithDevice(activeItem.device)}
                  className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-xs shadow-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                >
                  <span>Tengo una falla similar en mi equipo</span>
                  <ArrowRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>

          </div>

        </div>

        {/* Gallery of other case studies */}
        <div className="space-y-4">
          <h3 className="text-xl font-bold text-slate-900">
            Explora otros casos resueltos en nuestro laboratorio
          </h3>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {filteredItems.map((item) => {
              const isSelected = activeItem.id === item.id;
              return (
                <div
                  key={item.id}
                  onClick={() => {
                    setActiveItem(item);
                    window.scrollTo({ top: 400, behavior: 'smooth' });
                  }}
                  className={`bg-white rounded-2xl p-4 border transition-all cursor-pointer group flex flex-col justify-between ${
                    isSelected
                      ? 'border-blue-600 ring-2 ring-blue-600/20 shadow-md'
                      : 'border-slate-200 hover:border-blue-300 hover:shadow-md'
                  }`}
                >
                  <div className="space-y-3">
                    <div className="relative h-36 rounded-xl overflow-hidden bg-slate-100">
                      <img
                        src={item.afterImage}
                        alt={item.title}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        referrerPolicy="no-referrer"
                      />
                      <span className="absolute bottom-2 left-2 px-2 py-0.5 rounded-md bg-slate-900/80 text-white text-[10px] font-bold">
                        {item.category}
                      </span>
                    </div>

                    <h4 className="text-sm font-bold text-slate-900 group-hover:text-blue-600 transition-colors line-clamp-2">
                      {item.title}
                    </h4>

                    <p className="text-xs text-slate-500 line-clamp-2">
                      {item.problem}
                    </p>
                  </div>

                  <div className="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                    <span>Ver caso completo</span>
                    <ArrowRight className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

      </div>
    </div>
  );
};
