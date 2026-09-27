import { ArrowRight, Rocket } from 'lucide-react';

interface CtaBannerProps {
  onContactClick: () => void;
}

export function CtaBanner({ onContactClick }: CtaBannerProps) {
  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
      <div className="bg-brand-forest-dark text-white rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl relative overflow-hidden">
        {/* Background leaf accent */}
        <div className="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
          <svg className="w-60 h-60 fill-current" viewBox="0 0 100 100">
            <path d="M50 0 C70 30 100 50 100 100 C50 100 30 70 0 50 C30 50 50 30 50 0 Z" />
          </svg>
        </div>

        <div className="flex items-center gap-4 sm:gap-5 z-10">
          <div className="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl shrink-0 border border-white/20">
            <Rocket className="w-7 h-7 text-emerald-300" />
          </div>
          <div>
            <h3 className="text-xl sm:text-2xl font-extrabold text-white leading-tight">
              Impulsa tu inversión hoy con asesoría personalizada
            </h3>
            <p className="text-xs sm:text-sm text-emerald-200/90 mt-1">
              Conoce nuestras promociones de enganche y facilidades de pago.
            </p>
          </div>
        </div>

        <button
          onClick={onContactClick}
          aria-label="Ir a contacto"
          className="z-10 w-12 h-12 rounded-full bg-white text-brand-forest flex items-center justify-center text-xl font-bold shadow-lg hover:scale-110 active:scale-95 transition shrink-0 cursor-pointer"
        >
          <ArrowRight className="w-5 h-5 text-brand-forest" />
        </button>
      </div>
    </div>
  );
}
