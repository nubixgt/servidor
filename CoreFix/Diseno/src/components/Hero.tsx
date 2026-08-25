import React from 'react';
import { Shield, Sparkles, CheckCircle2, Award, Zap } from 'lucide-react';

interface HeroProps {
  onSolicitarPresupuesto: () => void;
  onVerServicios: () => void;
}

export const Hero: React.FC<HeroProps> = ({
  onSolicitarPresupuesto,
  onVerServicios,
}) => {
  return (
    <section className="relative overflow-hidden bg-white pt-10 pb-16 lg:pt-16 lg:pb-24 border-b border-slate-100">
      {/* Subtle background ambient glow */}
      <div className="absolute top-0 right-1/4 w-96 h-96 bg-blue-50/50 rounded-full blur-3xl -z-10 pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
          
          {/* Left Column: Copy & Actions */}
          <div className="lg:col-span-6 xl:col-span-6 space-y-6">
            
            <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-semibold tracking-wide">
              <Sparkles className="w-3.5 h-3.5 text-blue-600" />
              <span>Laboratorio Técnico Especializado con Garantía</span>
            </div>

            <h1 className="text-4xl sm:text-5xl lg:text-[3.25rem] font-extrabold text-slate-900 tracking-tight leading-[1.15]">
              Reparación y mantenimiento de tu tecnología,{' '}
              <span className="text-blue-600">rápido y confiable</span>.
            </h1>

            <p className="text-slate-600 text-lg sm:text-xl font-normal leading-relaxed max-w-xl">
              Especialistas en computadoras, celulares, consolas y servicios de software. Devolvemos la vida a tus dispositivos con precisión técnica y componentes de alta calidad.
            </p>

            {/* Action CTA Buttons */}
            <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 pt-2">
              <button
                onClick={onSolicitarPresupuesto}
                id="hero-btn-solicitar-presupuesto"
                className="bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium text-base px-7 py-3.5 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 text-center cursor-pointer flex items-center justify-center gap-2 group"
              >
                <span>Solicitar Presupuesto</span>
                <Zap className="w-4 h-4 text-blue-200 group-hover:scale-110 transition-transform" />
              </button>

              <button
                onClick={onVerServicios}
                id="hero-btn-ver-servicios"
                className="bg-white hover:bg-blue-50/60 active:bg-blue-100/50 text-blue-600 border border-blue-400 font-medium text-base px-7 py-3.5 rounded-xl transition-all duration-200 text-center cursor-pointer shadow-xs"
              >
                Ver Servicios
              </button>
            </div>

            {/* Key Quality Highlights */}
            <div className="pt-6 border-t border-slate-100 grid grid-cols-3 gap-4">
              <div className="flex items-start gap-2">
                <CheckCircle2 className="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <div>
                  <h4 className="text-xs font-bold text-slate-900">Diagnóstico Ágil</h4>
                  <p className="text-[11px] text-slate-500">En 24hs o express</p>
                </div>
              </div>
              <div className="flex items-start gap-2">
                <Award className="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <div>
                  <h4 className="text-xs font-bold text-slate-900">Garantía Escrita</h4>
                  <p className="text-[11px] text-slate-500">Hasta 6 meses</p>
                </div>
              </div>
              <div className="flex items-start gap-2">
                <Shield className="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                <div>
                  <h4 className="text-xs font-bold text-slate-900">Repuestos OEM</h4>
                  <p className="text-[11px] text-slate-500">Calidad testeada</p>
                </div>
              </div>
            </div>

          </div>

          {/* Right Column: Hero Visual composition */}
          <div className="lg:col-span-6 xl:col-span-6 relative">
            <div className="relative mx-auto max-w-lg lg:max-w-none">
              
              {/* Main Image Frame matching reference screenshot */}
              <div className="relative overflow-hidden rounded-3xl bg-slate-100 shadow-xl border border-slate-200/60 group">
                <img
                  src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=1200&q=85"
                  alt="Mesa de trabajo de reparación técnica con laptop, herramientas y tapete antiestático azul"
                  className="w-full h-[380px] sm:h-[460px] lg:h-[490px] object-cover object-center group-hover:scale-103 transition-transform duration-700"
                  referrerPolicy="no-referrer"
                />

                {/* Ambient Workshop Overlay Accent */}
                <div className="absolute inset-0 bg-gradient-to-t from-slate-950/40 via-transparent to-transparent pointer-events-none" />

                {/* Floating pill badge */}
                <div className="absolute bottom-4 left-4 right-4 bg-white/95 backdrop-blur-md p-3.5 rounded-2xl border border-white/40 shadow-lg flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
                      <Shield className="w-5 h-5" />
                    </div>
                    <div>
                      <p className="text-xs font-bold text-slate-900">Laboratorio Certificado ESD</p>
                      <p className="text-[11px] text-slate-500">Microscopía, termografía y microsoldadura BGA</p>
                    </div>
                  </div>
                  <span className="hidden sm:inline-flex px-2.5 py-1 text-[11px] font-semibold bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200">
                    Taller Activo
                  </span>
                </div>
              </div>

              {/* Decorative accent element */}
              <div className="absolute -bottom-3 -right-3 w-28 h-28 bg-blue-600/10 rounded-full blur-xl -z-10" />
            </div>
          </div>

        </div>
      </div>
    </section>
  );
};
