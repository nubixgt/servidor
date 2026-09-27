import { useState } from 'react';
import {
  Check,
  ChevronLeft,
  ChevronRight,
  Compass,
  ArrowRight,
  Trees,
  Zap,
  Sparkles,
  Home,
  TrendingUp,
  Play,
} from 'lucide-react';

interface ProjectSectionProps {
  onSelectLot: (lotId: string) => void;
  onOpenVideoTour: () => void;
  onMoreInfo: () => void;
}

export function ProjectSection({ onSelectLot, onOpenVideoTour, onMoreInfo }: ProjectSectionProps) {
  const [activeThumbIndex, setActiveThumbIndex] = useState(0);

  const galleryThumbs = [
    {
      title: 'Plano Aéreo Fase 1',
      url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB94Y7dtdtagYRkl3k2SRaUjEIOLcKtFG4A_rnzD6os-_m0u5MivUBtUIZxsxQ9I_pfqeiKfQaPRKH7YxzVP3ltBbIo4MwR1Mjsv-tsbUIVn0qXpsMmP_zVSoobhVVNopsYTXExg8rOTVr9whd-5s0YMJyXujA2gFG5Lm3F9ObtZBlNGxKzrVue9S5uHucCJuyBnLi7a5QkXoeJVP59Dk67dYfb-tRT5x5NZfDSQmDnlzzh0lZnXQ',
      isMasterplan: true,
    },
    {
      title: 'Entrada Monumental',
      url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA1LHcZwlIWINnWp_Qy9Hbfnbv5QOiFNlUVmBhYGJzcy7eAA4txkPPEek41dfhQU60Kt_KdHrlvLPwHbU_Dyn5EI4aFeRAs_0lUEXI5yEzwlBn9ZLoaVlspctmL7iQINa49IXny5owFpBflK_EzYZC4tTFg9KMifCQHvpr7JenED65gX04A1sSPFG_dah5jAJMjWgowvguwB22bsww9pNG8_0svZBtzq59xH55erGCz8oghobLnCg',
      isMasterplan: false,
    },
    {
      title: 'Áreas verdes y bulevar',
      url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA8xKPRr1gDdhvOa-9Mo5O9RE9T9t8raftSAfhHh4qP4vExV5lfS7_eBc-g22XIRZe2rGNAyslT4GX_RCy_OerFvQxZKoO9bSm--f8-O8CEvo6oN47lcC00KFaBQ_MxL38aG3Dmb57QK0VvD8g7YqGfYmzz2SVBODd93tFnky-RGe1wnX-Urao8nqXX4yJ_-sfufPRMe16EeXlNQxFEVm1Q8OJnlyMBhb7tYaFffKmEgP8gcf8fBA',
      isMasterplan: false,
    },
    {
      title: 'Vistas hacia montañas',
      url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAlW3OUNjRvcXL7YO702dNlwBxOsnYgTwF-lUekf4tmHSTfnoY1-vqiHufHJHrdYbyCg3AQoO5DUb3Ew4TQoGUTUn8LQnaG6SgqHKm8dYlTHflPVqr2136UZ4NlbnhcIzneaIR4BhbAkHsehqVn8tn3wYIhnCSej-XkFDoosoN3Zu3bA2uCS4uysD5oq9LZ9j6EquO0xCBIcCNUsTnkJKfqwdC1EcSXLHSoE0tiH9z4MhvaFuSARg',
      isMasterplan: false,
    },
    {
      title: 'Casa club y amenidades',
      url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDy4vudjlYYCO2h6N2_FRLRd1G5sgRYU_9fuwdEOlEr-GQC44fF48Ra455wWaqjh7h9H75Acn_GQdZkO5hWQIYu91fi8jUiNUW_D_BIRTkenc0HmP6jxfptHrWePtcOX9-lkIWmawMIWjVvAOoIOzq8nMZZVeI2TiUsia5Pi-THTLcevCJJeGc_kBIt75Q7nC68oCo_abKMQEvduuoQKvL4T-Psh6zpnh4Z5GYdIXppazM5ZyiIhg',
      isMasterplan: false,
    },
  ];

  const handlePrevThumb = () => {
    setActiveThumbIndex((prev) => (prev > 0 ? prev - 1 : galleryThumbs.length - 1));
  };

  const handleNextThumb = () => {
    setActiveThumbIndex((prev) => (prev < galleryThumbs.length - 1 ? prev + 1 : 0));
  };

  return (
    <section className="py-16 relative z-10" id="proyecto">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Column 1: Info & Pitch Left (4 cols) */}
          <div className="lg:col-span-4 flex flex-col justify-between h-full pt-2">
            <div>
              {/* Project Tagline */}
              <div className="flex items-center gap-2 mb-3">
                <div className="w-6 h-[2px] bg-brand-forest" />
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">
                  EL PROYECTO
                </span>
              </div>

              {/* Main Title */}
              <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight mb-5">
                Un lugar pensado para crecer
              </h2>

              {/* Paragraph Description */}
              <p className="text-slate-600 text-sm sm:text-base leading-relaxed mb-6 font-normal">
                Las Orquídeas combina ubicación estratégica, infraestructura subterránea moderna y
                un entorno natural inigualable para que tú y tu familia disfruten de una mejor calidad
                de vida con total tranquilidad.
              </p>

              <div className="space-y-3 mb-8">
                <div className="flex items-start gap-3">
                  <div className="w-5 h-5 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center shrink-0 mt-0.5">
                    <Check className="w-3 h-3 stroke-[3]" />
                  </div>
                  <span className="text-xs sm:text-sm text-slate-700 font-medium">
                    Garita de seguridad con control de acceso 24/7.
                  </span>
                </div>

                <div className="flex items-start gap-3">
                  <div className="w-5 h-5 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center shrink-0 mt-0.5">
                    <Check className="w-3 h-3 stroke-[3]" />
                  </div>
                  <span className="text-xs sm:text-sm text-slate-700 font-medium">
                    Red de agua potable propia y cableado subterráneo.
                  </span>
                </div>

                <div className="flex items-start gap-3">
                  <div className="w-5 h-5 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center shrink-0 mt-0.5">
                    <Check className="w-3 h-3 stroke-[3]" />
                  </div>
                  <span className="text-xs sm:text-sm text-slate-700 font-medium">
                    Calles amplias pavimentadas y aceras peatonales.
                  </span>
                </div>
              </div>
            </div>

            <div className="pt-2">
              <button
                onClick={onMoreInfo}
                className="inline-flex items-center gap-3 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200/90 shadow-xs px-6 py-3 rounded-full text-sm font-semibold transition hover:shadow-md cursor-pointer"
              >
                <span>Conoce más del proyecto</span>
                <ArrowRight className="w-4 h-4 text-slate-500" />
              </button>
            </div>
          </div>

          {/* Column 2: 3D Aerial Lot Masterplan & Interactive Card (5 cols) */}
          <div className="lg:col-span-5 relative">
            <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-900 group">
              {/* Main Preview */}
              <div className="relative h-[340px] sm:h-[400px] w-full overflow-hidden">
                <img
                  src={galleryThumbs[activeThumbIndex].url}
                  alt={galleryThumbs[activeThumbIndex].title}
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover object-bottom filter saturate-125 transform group-hover:scale-105 transition-transform duration-700"
                />
                <div className="absolute inset-0 bg-emerald-950/20 mix-blend-multiply pointer-events-none" />

                {/* Simulated Parcel Grid lines overlay when viewing masterplan */}
                {galleryThumbs[activeThumbIndex].isMasterplan && (
                  <>
                    <svg
                      className="absolute inset-0 w-full h-full opacity-40 pointer-events-none"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <line
                        stroke="#FFFFFF"
                        strokeDasharray="4 3"
                        strokeWidth="1.5"
                        x1="20%"
                        x2="80%"
                        y1="30%"
                        y2="85%"
                      />
                      <line
                        stroke="#FFFFFF"
                        strokeDasharray="4 3"
                        strokeWidth="1.5"
                        x1="30%"
                        x2="90%"
                        y1="20%"
                        y2="75%"
                      />
                      <line
                        stroke="#FFFFFF"
                        strokeDasharray="4 3"
                        strokeWidth="1.5"
                        x1="10%"
                        x2="80%"
                        y1="60%"
                        y2="20%"
                      />
                      <line
                        stroke="#FFFFFF"
                        strokeDasharray="4 3"
                        strokeWidth="1.5"
                        x1="25%"
                        x2="95%"
                        y1="80%"
                        y2="40%"
                      />
                    </svg>

                    {/* Floating Pin 1: Available (Lote 12) */}
                    <div className="absolute top-[48%] left-[46%] z-20 transform -translate-x-1/2 -translate-y-1/2">
                      <div className="relative flex items-center justify-center">
                        <span className="w-8 h-8 rounded-full bg-purple-500/40 pulse-ring absolute" />
                        <button
                          onClick={() => onSelectLot('l-12')}
                          className="w-7 h-7 rounded-full bg-purple-600 text-white flex items-center justify-center shadow-lg border-2 border-white cursor-pointer hover:scale-110 transition active:scale-95"
                          title="Lote 12 Disponible"
                        >
                          <span className="text-[10px] font-bold">12</span>
                        </button>
                      </div>
                    </div>

                    {/* Floating Pin 2: Secondary point */}
                    <div className="absolute top-[32%] left-[42%] z-20">
                      <button
                        onClick={() => onSelectLot('l-05')}
                        className="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center shadow-md border-2 border-white hover:scale-110 transition cursor-pointer"
                        title="Lote 5"
                      >
                        <div className="w-1.5 h-1.5 rounded-full bg-white" />
                      </button>
                    </div>

                    {/* Floating Pin 3: Right point */}
                    <div className="absolute top-[38%] right-[22%] z-20">
                      <button
                        onClick={() => onSelectLot('l-09')}
                        className="w-5 h-5 rounded-full bg-sky-500 text-white flex items-center justify-center shadow-md border-2 border-white hover:scale-110 transition cursor-pointer"
                        title="Lote 9"
                      >
                        <div className="w-1.5 h-1.5 rounded-full bg-white" />
                      </button>
                    </div>

                    {/* Floating Tooltip Card (Active Lot Details - Matching Reference Image) */}
                    <div className="absolute top-[28%] left-[48%] z-30 transform -translate-x-2 -translate-y-full w-48 sm:w-56 glass-card rounded-2xl p-3 shadow-xl border border-white">
                      <div className="flex items-center justify-between mb-1">
                        <div className="flex items-center gap-1.5">
                          <span className="w-2.5 h-2.5 rounded-full bg-purple-500" />
                          <span className="font-extrabold text-xs sm:text-sm text-slate-800">
                            Lote 12
                          </span>
                        </div>
                        <span className="text-[10px] text-emerald-700 bg-emerald-100 font-semibold px-2 py-0.5 rounded-full">
                          Disponible
                        </span>
                      </div>
                      <div className="text-[11px] text-slate-500 space-y-0.5">
                        <p className="flex items-center gap-1">
                          <span>📐</span> <span>260 m² (10m x 26m)</span>
                        </p>
                        <p className="text-brand-forest font-bold text-xs mt-1">Q 185,000</p>
                      </div>
                      <button
                        onClick={() => onSelectLot('l-12')}
                        className="mt-2 text-[10px] font-bold text-slate-700 hover:text-brand-forest flex items-center justify-end gap-1 w-full text-right cursor-pointer"
                      >
                        <span>Ver detalle</span> <span>→</span>
                      </button>
                    </div>

                    {/* Compass icon overlay */}
                    <div className="absolute bottom-16 left-4 z-20 w-8 h-8 rounded-full glass-card flex items-center justify-center text-slate-700 shadow-md">
                      <Compass className="w-4 h-4 text-brand-forest" />
                    </div>
                  </>
                )}
              </div>

              {/* Bottom Mini Thumbnails Strip */}
              <div className="bg-white/95 p-3 flex items-center justify-between gap-2 border-t border-slate-100">
                <button
                  onClick={handlePrevThumb}
                  aria-label="Anterior"
                  className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold transition cursor-pointer"
                >
                  <ChevronLeft className="w-4 h-4" />
                </button>

                <div className="grid grid-cols-4 gap-2 flex-1">
                  {galleryThumbs.slice(1, 5).map((thumb, idx) => {
                    const actualIdx = idx + 1;
                    const isSelected = activeThumbIndex === actualIdx;
                    return (
                      <button
                        key={thumb.title}
                        onClick={() => setActiveThumbIndex(actualIdx)}
                        className={`h-12 rounded-lg overflow-hidden border relative group/thumb cursor-pointer transition ${
                          isSelected ? 'border-brand-forest ring-2 ring-brand-forest/40' : 'border-slate-200'
                        }`}
                      >
                        <img
                          src={thumb.url}
                          alt={thumb.title}
                          referrerPolicy="no-referrer"
                          className="w-full h-full object-cover"
                        />
                        <div
                          className={`absolute inset-0 bg-brand-forest/20 transition ${
                            isSelected ? 'opacity-0' : 'group-hover/thumb:opacity-0'
                          }`}
                        />
                      </button>
                    );
                  })}
                </div>

                <button
                  onClick={handleNextThumb}
                  aria-label="Siguiente"
                  className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold transition cursor-pointer"
                >
                  <ChevronRight className="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          {/* Column 3: Amenities Grid & Video Card (3 cols) */}
          <div className="lg:col-span-3 flex flex-col justify-between space-y-6">
            <div>
              {/* Section Tagline */}
              <div className="flex items-center gap-2 mb-4">
                <div className="w-6 h-[2px] bg-brand-forest" />
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">
                  AMENIDADES
                </span>
              </div>

              {/* 6 Square Amenity Badges (Matching Image 2 layout) */}
              <div className="grid grid-cols-3 gap-2.5 mb-6">
                {/* Amenity 1 */}
                <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                    <svg className="w-4 h-4 text-brand-forest" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" />
                    </svg>
                  </div>
                  <span className="text-[10px] font-bold text-slate-800 leading-tight">
                    Calles amplias
                  </span>
                </div>

                {/* Amenity 2 */}
                <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                    <Trees className="w-4 h-4 text-brand-forest" />
                  </div>
                  <span className="text-[10px] font-bold text-slate-800 leading-tight">
                    Áreas verdes
                  </span>
                </div>

                {/* Amenity 3 */}
                <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                    <Zap className="w-4 h-4 text-brand-forest" />
                  </div>
                  <span className="text-[10px] font-bold text-slate-800 leading-tight">
                    Servicios básicos
                  </span>
                </div>

                {/* Amenity 4 */}
                <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                    <Sparkles className="w-4 h-4 text-brand-forest" />
                  </div>
                  <span className="text-[10px] font-bold text-slate-800 leading-tight">
                    Entorno natural
                  </span>
                </div>

                {/* Amenity 5 */}
                <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                    <Home className="w-4 h-4 text-brand-forest" />
                  </div>
                  <span className="text-[10px] font-bold text-slate-800 leading-tight">
                    Diseño moderno
                  </span>
                </div>

                {/* Amenity 6 */}
                <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                  <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                    <TrendingUp className="w-4 h-4 text-brand-forest" />
                  </div>
                  <span className="text-[10px] font-bold text-slate-800 leading-tight">
                    Alta plusvalía
                  </span>
                </div>
              </div>
            </div>

            {/* Panoramic Sunset Video Card (Matching bottom right of Image 2) */}
            <div
              onClick={onOpenVideoTour}
              className="relative rounded-2xl overflow-hidden shadow-lg group cursor-pointer border border-white active:scale-98 transition"
            >
              <img
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjQ4T8cH4OErX3z8O8lBggXHva1VH79T0Xsn3hoZkd3-6slkwAeK1qav1t0gPYUJqBlebNxd25tHVQIMKbV9vmekiIBqx0VYxwZhmBzCR2dx9Pga0mZdk1ZwGFhDPIpsLdZxcRDiygcISByaOx5ZnVudcdulntJZQqGuGGmmAZenNzPxm3BXpgVDiRlgNnghLiA1x82rZAZs6HVd5sCfW5d8GYLV56D-BW28WzcHPZHZm_49b37g"
                alt="Vista del entorno natural al atardecer"
                referrerPolicy="no-referrer"
                className="w-full h-36 object-cover filter brightness-[0.75] group-hover:scale-105 transition-transform duration-500"
              />
              <div className="absolute inset-0 bg-linear-to-t from-slate-950/80 via-slate-950/20 to-transparent flex flex-col justify-end p-4 text-white">
                <div className="flex items-center gap-3">
                  <div className="w-9 h-9 rounded-full bg-white/90 text-slate-900 flex items-center justify-center pl-0.5 shadow-lg group-hover:scale-110 transition">
                    <Play className="w-3.5 h-3.5 fill-current" />
                  </div>
                  <div>
                    <p className="text-[11px] font-medium text-slate-200 leading-snug">
                      Un entorno que te conecta con lo esencial
                    </p>
                    <p className="text-xs font-bold text-white flex items-center gap-1 mt-0.5">
                      <span>Ver video del proyecto</span>
                      <span>▷</span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
