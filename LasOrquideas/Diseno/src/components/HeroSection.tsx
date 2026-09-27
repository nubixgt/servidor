import { useState } from 'react';
import { Play, TrendingUp, ShieldCheck, Trees, MapPin, ArrowRight, Grid3X3 } from 'lucide-react';

interface HeroSectionProps {
  onExploreLots: () => void;
  onOpenVideoTour: () => void;
}

export function HeroSection({ onExploreLots, onOpenVideoTour }: HeroSectionProps) {
  const [imageError, setImageError] = useState(false);

  const heroImageUrl =
    'https://lh3.googleusercontent.com/aida-public/AB6AXuDN7JRSqVoP6NZ8b175shgdlEzxNUKt7dMvkoHpkYJgQMpPmZ4zBubP7qVOudS5APG3-0AHBmYYZJ1Ok18D1yC0BU_GNurw7gvtI0n9MSHxp9fuAGu2o4vpS6XN2T1XQA4yMVVShCCMR4KIVKf3bQKZTbQ-UHFDhUTB3MNY-J8W1dQ6l-q6UDnsNfHF1BkQZye2uGzCHTi1av11NhOD6vi1akGPR3c4WZELgSx5GurECxdn4_eUOw';

  return (
    <section className="relative pt-24 pb-12 md:pt-32 md:pb-20 overflow-hidden" id="inicio">
      {/* Background Image with Soft Vignette */}
      <div className="absolute inset-0 z-0">
        {!imageError ? (
          <img
            src={heroImageUrl}
            alt="Vista panorámica de entrada residencial Las Orquídeas al atardecer"
            referrerPolicy="no-referrer"
            onError={() => setImageError(true)}
            className="w-full h-full object-cover object-center filter brightness-[0.98]"
          />
        ) : (
          <div className="w-full h-full bg-linear-to-br from-emerald-900/30 via-slate-800/20 to-teal-950/40" />
        )}
        <div className="absolute inset-0 bg-linear-to-r from-white/95 via-white/50 to-transparent" />
        <div className="absolute inset-0 bg-linear-to-t from-[#F2F6F4] via-transparent to-transparent" />
      </div>

      <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center min-h-[460px] md:min-h-[500px]">
          {/* Hero Copy Left Side */}
          <div className="lg:col-span-7 pt-4">
            {/* Tagline with line */}
            <div className="flex items-center gap-2 mb-4">
              <span className="text-xs md:text-sm font-bold uppercase tracking-tagline text-slate-800">
                TU PRÓXIMA HISTORIA COMIENZA AQUÍ
              </span>
              <div className="w-8 h-[2px] bg-slate-800/60" />
            </div>

            {/* Big Headline */}
            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.08] mb-6">
              Terrenos para <br />
              <span className="text-slate-900">un mejor futuro</span>
            </h1>

            {/* Subtitle */}
            <p className="text-slate-700 text-base md:text-lg max-w-xl mb-8 leading-relaxed font-normal">
              Las Orquídeas es un proyecto de lotificación diseñado para brindarte un entorno
              seguro, moderno y con gran proyección de plusvalía para ti y tu familia.
            </p>

            {/* Buttons */}
            <div className="flex flex-wrap items-center gap-3 sm:gap-4">
              <button
                onClick={onExploreLots}
                className="inline-flex items-center gap-3 bg-brand-forest hover:bg-brand-forest-dark text-white px-6 sm:px-7 py-3.5 rounded-full font-semibold text-sm md:text-base shadow-lg transition-transform hover:-translate-y-0.5 active:scale-95 cursor-pointer"
              >
                <Grid3X3 className="w-5 h-5 text-emerald-300" />
                <span>Ver lotes disponibles</span>
                <ArrowRight className="w-4 h-4 ml-1" />
              </button>

              <button
                onClick={onOpenVideoTour}
                className="inline-flex items-center gap-3 glass-card hover:bg-white/90 text-slate-800 px-5 sm:px-6 py-3.5 rounded-full font-semibold text-sm md:text-base shadow-xs transition hover:shadow-md cursor-pointer"
              >
                <span className="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs pl-0.5 shadow-sm">
                  <Play className="w-3 h-3 fill-current" />
                </span>
                <span>Conoce el proyecto</span>
              </button>
            </div>
          </div>

          {/* Hero Right Side: Monumental Entry Marker */}
          <div className="lg:col-span-5 hidden lg:flex justify-end pr-4">
            <div className="relative">
              {/* Circular emblem mirroring gate badge from mockup */}
              <div className="w-44 h-44 rounded-full border-4 border-white/80 shadow-2xl overflow-hidden glass-card p-2 flex flex-col items-center justify-center transform hover:scale-105 transition-all duration-300 backdrop-blur-xl">
                <div className="w-20 h-20 rounded-full border border-slate-200 bg-white/90 p-2 shadow-inner">
                  <svg className="w-full h-full" fill="none" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" fill="#F8FAFC" r="46" stroke="#E2E8F0" strokeWidth="2" />
                    <path d="M50 14L22 42V82H78V42L50 14Z" fill="#FFF" stroke="#1E293B" strokeWidth="3" />
                    <path d="M50 22L30 44V76H70V44L50 22Z" fill="#F0FDF4" />
                    <path d="M50 28L34 46L50 64L66 46L50 28Z" fill="#FDA4AF" />
                    <path d="M50 64L34 46L42 76H50V64Z" fill="#BE185D" />
                    <path d="M50 64L66 46L58 76H50V64Z" fill="#047857" />
                    <rect fill="#1E293B" height="5" width="5" x="44" y="40" />
                    <rect fill="#1E293B" height="5" width="5" x="51" y="40" />
                    <rect fill="#1E293B" height="5" width="5" x="44" y="47" />
                    <rect fill="#1E293B" height="5" width="5" x="51" y="47" />
                  </svg>
                </div>
                <span className="text-xs font-black tracking-widest text-slate-800 mt-2">
                  LAS ORQUÍDEAS
                </span>
                <span className="text-[8px] tracking-[0.3em] font-semibold text-slate-500 uppercase">
                  Lotificadora
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* 4 Hero Highlight Glass Cards */}
        <div className="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Card 1: Plusvalia */}
          <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
            <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
              <TrendingUp className="w-6 h-6 text-brand-emerald" />
            </div>
            <div>
              <h3 className="font-bold text-slate-900 text-sm leading-snug">
                Plusvalía garantizada
              </h3>
              <p className="text-xs text-slate-500">Inversión con futuro</p>
            </div>
          </div>

          {/* Card 2: Entorno seguro */}
          <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
            <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
              <ShieldCheck className="w-6 h-6 text-brand-emerald" />
            </div>
            <div>
              <h3 className="font-bold text-slate-900 text-sm leading-snug">
                Entorno seguro
              </h3>
              <p className="text-xs text-slate-500">Para tu familia</p>
            </div>
          </div>

          {/* Card 3: Areas verdes */}
          <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
            <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
              <Trees className="w-6 h-6 text-brand-emerald" />
            </div>
            <div>
              <h3 className="font-bold text-slate-900 text-sm leading-snug">
                Áreas verdes
              </h3>
              <p className="text-xs text-slate-500">Naturaleza cerca de ti</p>
            </div>
          </div>

          {/* Card 4: Acceso estrategico */}
          <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
            <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
              <MapPin className="w-6 h-6 text-brand-emerald" />
            </div>
            <div>
              <h3 className="font-bold text-slate-900 text-sm leading-snug">
                Acceso estratégico
              </h3>
              <p className="text-xs text-slate-500">Conecta con lo importante</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
