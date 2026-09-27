import { useState } from 'react';
import { MapPin, Navigation, School, ShoppingBag, Stethoscope, ArrowRight } from 'lucide-react';

export function LocationSection() {
  const [activeTab, setActiveTab] = useState<'colegios' | 'comercio' | 'salud'>('colegios');

  const locationPhotoUrl =
    'https://lh3.googleusercontent.com/aida-public/AB6AXuAFQB1o4vdkcwzHfd5bED3ZafhEWFEGNp8YZdC7aK7hF7jDDtbj94c5h5OJbwQ8RQUZsZamDB60qyWMjX44YpH1rs8V48icf5DSuxhSCTUky6UdYrXUS0vr2qtQ-Gv7UBZ1n3Qnk2LrLLpRsItTanABz_3XdK38ZoV24-9PwwSFtxJyRs4xASteQTRxoqqSXATopsBS5T6V6QxoOL6x7IDvQTWm9WKqqcVKf86FhYd9sjvphU1xyQ';

  const places = {
    colegios: [
      'Colegio Bilingüe Montessori (5 min)',
      'Colegio El Roble (8 min)',
      'Universidad del Valle Campus Central (15 min)',
    ],
    comercio: [
      'Centro Comercial Las Terrazas (10 min)',
      'Supermercado La Torre y Walmart (9 min)',
      'Plaza Financiera y Bancos (8 min)',
    ],
    salud: [
      'Hospital Herrera Llerandi Satélite (12 min)',
      'Centro de Urgencias y Clínicas Médicas (10 min)',
      'Farmacias 24 Horas (6 min)',
    ],
  };

  return (
    <section className="py-16 relative" id="ubicacion">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          {/* Text left */}
          <div className="lg:col-span-5">
            <div className="flex items-center gap-2 mb-3">
              <div className="w-6 h-[2px] bg-brand-forest" />
              <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">
                UBICACIÓN ESTRATÉGICA
              </span>
            </div>

            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight mb-4">
              Conexión y tranquilidad en un solo lugar
            </h2>

            <p className="text-slate-600 text-sm leading-relaxed mb-6">
              Ubicado en una zona de alta expansión residencial en Guatemala, protegido del ruido de
              la ciudad pero a pocos minutos de los principales centros comerciales, colegios y
              servicios médicos.
            </p>

            {/* Time distances list */}
            <div className="space-y-4 mb-8">
              <div
                onClick={() => setActiveTab('colegios')}
                className={`flex items-start gap-4 p-3.5 rounded-2xl glass-card cursor-pointer transition ${
                  activeTab === 'colegios'
                    ? 'ring-2 ring-brand-forest/30 bg-white/90 shadow-md'
                    : 'hover:bg-white/80'
                }`}
              >
                <div className="w-10 h-10 rounded-xl bg-emerald-100/70 text-brand-forest flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                  5 min
                </div>
                <div className="flex-1">
                  <div className="flex items-center justify-between">
                    <h4 className="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                      <School className="w-4 h-4 text-brand-forest" />
                      <span>Colegios y Centros Educativos</span>
                    </h4>
                  </div>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Acceso directo por bulevares principales.
                  </p>
                  {activeTab === 'colegios' && (
                    <ul className="mt-2 text-[11px] text-slate-600 space-y-1 list-disc list-inside">
                      {places.colegios.map((item) => (
                        <li key={item}>{item}</li>
                      ))}
                    </ul>
                  )}
                </div>
              </div>

              <div
                onClick={() => setActiveTab('comercio')}
                className={`flex items-start gap-4 p-3.5 rounded-2xl glass-card cursor-pointer transition ${
                  activeTab === 'comercio'
                    ? 'ring-2 ring-brand-forest/30 bg-white/90 shadow-md'
                    : 'hover:bg-white/80'
                }`}
              >
                <div className="w-10 h-10 rounded-xl bg-emerald-100/70 text-brand-forest flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                  10 min
                </div>
                <div className="flex-1">
                  <h4 className="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                    <ShoppingBag className="w-4 h-4 text-brand-forest" />
                    <span>Supermercados y Plazas Comerciales</span>
                  </h4>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Bancos, farmacias, cines y restaurantes de prestigio.
                  </p>
                  {activeTab === 'comercio' && (
                    <ul className="mt-2 text-[11px] text-slate-600 space-y-1 list-disc list-inside">
                      {places.comercio.map((item) => (
                        <li key={item}>{item}</li>
                      ))}
                    </ul>
                  )}
                </div>
              </div>

              <div
                onClick={() => setActiveTab('salud')}
                className={`flex items-start gap-4 p-3.5 rounded-2xl glass-card cursor-pointer transition ${
                  activeTab === 'salud'
                    ? 'ring-2 ring-brand-forest/30 bg-white/90 shadow-md'
                    : 'hover:bg-white/80'
                }`}
              >
                <div className="w-10 h-10 rounded-xl bg-emerald-100/70 text-brand-forest flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">
                  12 min
                </div>
                <div className="flex-1">
                  <h4 className="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                    <Stethoscope className="w-4 h-4 text-brand-forest" />
                    <span>Centros de Salud y Hospitales</span>
                  </h4>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Atención médica oportuna para toda la familia.
                  </p>
                  {activeTab === 'salud' && (
                    <ul className="mt-2 text-[11px] text-slate-600 space-y-1 list-disc list-inside">
                      {places.salud.map((item) => (
                        <li key={item}>{item}</li>
                      ))}
                    </ul>
                  )}
                </div>
              </div>
            </div>

            <a
              href="https://maps.google.com/?q=Las+Orquideas+Guatemala"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-3 bg-brand-forest text-white px-6 py-3 rounded-full text-sm font-semibold hover:bg-brand-forest-dark transition shadow-md active:scale-95 cursor-pointer"
            >
              <Navigation className="w-4 h-4 text-emerald-300" />
              <span>Ver ruta en Waze / Google Maps</span>
              <ArrowRight className="w-4 h-4" />
            </a>
          </div>

          {/* Map visual right side */}
          <div className="lg:col-span-7">
            <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white h-[380px] sm:h-[440px] group">
              <img
                src={locationPhotoUrl}
                alt="Ubicación panorámica y accesos a Las Orquídeas"
                referrerPolicy="no-referrer"
                className="w-full h-full object-cover filter contrast-[1.05] group-hover:scale-102 transition duration-700"
              />
              <div className="absolute inset-0 bg-linear-to-t from-slate-900/60 via-transparent to-transparent" />

              {/* Floating Location Badge */}
              <div className="absolute bottom-6 left-4 right-4 sm:left-6 sm:right-6 glass-card rounded-2xl p-4 flex items-center justify-between shadow-xl border border-white">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-xl bg-brand-forest text-white flex items-center justify-center shrink-0">
                    <MapPin className="w-5 h-5 text-emerald-300" />
                  </div>
                  <div>
                    <p className="text-xs font-semibold text-brand-forest uppercase tracking-wider">
                      Garita Principal
                    </p>
                    <p className="text-xs sm:text-sm font-extrabold text-slate-900">
                      Km. 24.5 Carretera Residencial, Las Orquídeas
                    </p>
                  </div>
                </div>
                <div className="hidden sm:block">
                  <span className="px-3 py-1.5 rounded-full bg-brand-forest text-white text-xs font-bold shadow-xs">
                    100% Asfaltado
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
