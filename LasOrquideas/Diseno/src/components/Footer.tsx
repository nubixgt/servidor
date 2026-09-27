import { MessageCircle } from 'lucide-react';

interface FooterProps {
  onNavigate: (sectionId: string) => void;
}

export function Footer({ onNavigate }: FooterProps) {
  return (
    <>
      <footer className="bg-white border-t border-slate-200/70 pt-16 pb-12 relative z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
            {/* Brand Column */}
            <div className="lg:col-span-2 space-y-4">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-full border border-slate-300 bg-white p-1 flex items-center justify-center">
                  <svg className="w-full h-full" fill="none" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" fill="#F8FAFC" r="46" stroke="#E2E8F0" strokeWidth="2" />
                    <path d="M50 14L22 42V82H78V42L50 14Z" fill="#FFF" stroke="#1E293B" strokeWidth="3" />
                    <path d="M50 22L30 44V76H70V44L50 22Z" fill="#F0FDF4" />
                    <path d="M50 28L34 46L50 64L66 46L50 28Z" fill="#FDA4AF" />
                    <path d="M50 64L34 46L42 76H50V64Z" fill="#BE185D" />
                    <path d="M50 64L66 46L58 76H50V64Z" fill="#047857" />
                  </svg>
                </div>
                <div>
                  <span className="font-extrabold text-slate-900 tracking-wider text-sm">
                    LAS ORQUÍDEAS
                  </span>
                  <span className="block text-[8px] tracking-[0.25em] font-semibold text-slate-500 uppercase">
                    Lotificadora
                  </span>
                </div>
              </div>
              <p className="text-xs text-slate-500 leading-relaxed max-w-sm">
                Desarrollo residencial campestre y lotificación de alta plusvalía en Guatemala.
                Garantizamos seguridad, áreas verdes y certeza jurídica para construir el hogar de
                tus sueños.
              </p>
            </div>

            {/* Links Column */}
            <div>
              <h4 className="font-bold text-xs uppercase tracking-wider text-slate-900 mb-3">
                Navegación
              </h4>
              <ul className="space-y-2 text-xs text-slate-600">
                <li>
                  <button
                    onClick={() => onNavigate('inicio')}
                    className="hover:text-brand-forest transition cursor-pointer text-left"
                  >
                    Inicio
                  </button>
                </li>
                <li>
                  <button
                    onClick={() => onNavigate('proyecto')}
                    className="hover:text-brand-forest transition cursor-pointer text-left"
                  >
                    El Proyecto
                  </button>
                </li>
                <li>
                  <button
                    onClick={() => onNavigate('lotes')}
                    className="hover:text-brand-forest transition cursor-pointer text-left"
                  >
                    Lotes Disponibles
                  </button>
                </li>
                <li>
                  <button
                    onClick={() => onNavigate('ubicacion')}
                    className="hover:text-brand-forest transition cursor-pointer text-left"
                  >
                    Ubicación y Rutas
                  </button>
                </li>
                <li>
                  <button
                    onClick={() => onNavigate('galeria')}
                    className="hover:text-brand-forest transition cursor-pointer text-left"
                  >
                    Galería de Fotos
                  </button>
                </li>
              </ul>
            </div>

            {/* Amenities Column */}
            <div>
              <h4 className="font-bold text-xs uppercase tracking-wider text-slate-900 mb-3">
                Beneficios
              </h4>
              <ul className="space-y-2 text-xs text-slate-600">
                <li>Garita de Seguridad 24/7</li>
                <li>Pozo Propio de Agua</li>
                <li>Cableado Subterráneo</li>
                <li>Calles Pavimentadas</li>
                <li>Financiamiento Propio</li>
              </ul>
            </div>

            {/* Office Column */}
            <div>
              <h4 className="font-bold text-xs uppercase tracking-wider text-slate-900 mb-3">
                Oficina de Ventas
              </h4>
              <p className="text-xs text-slate-600 leading-relaxed">
                Km. 24.5 Carretera Residencial,
                <br />
                Guatemala, C.A.
                <br />
                <br />
                <strong className="text-slate-800">Horarios de atención:</strong>
                <br />
                Lunes a Domingo: 8:00 AM - 5:30 PM
              </p>
            </div>
          </div>

          {/* Copyright bottom */}
          <div className="border-t border-slate-100 pt-6 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-4">
            <p>© 2026 Las Orquídeas Lotificadora. Todos los derechos reservados.</p>
            <div className="flex items-center gap-6">
              <span className="hover:text-slate-600 transition cursor-pointer">
                Políticas de Privacidad
              </span>
              <span className="hover:text-slate-600 transition cursor-pointer">
                Términos del Servicio
              </span>
              <span className="hover:text-slate-600 transition cursor-pointer">
                Certeza Jurídica
              </span>
            </div>
          </div>
        </div>
      </footer>

      {/* Floating WhatsApp Button */}
      <aside className="fixed bottom-6 right-6 z-50">
        <a
          href="https://wa.me/50255555555?text=Hola,%20quisiera%20más%20información%20sobre%20Las%20Orquídeas"
          target="_blank"
          rel="noopener noreferrer"
          aria-label="Contactar por WhatsApp"
          className="group relative flex items-center justify-center w-14 h-14 bg-[#25D366] hover:bg-[#20ba59] text-white rounded-full shadow-2xl transition-all duration-300 hover:scale-110 active:scale-95"
        >
          {/* Pulsing circle */}
          <span className="absolute w-full h-full rounded-full bg-[#25D366] opacity-75 pulse-ring -z-10" />

          {/* WhatsApp Icon */}
          <MessageCircle className="w-7 h-7 fill-current" />

          {/* Tooltip label */}
          <span className="absolute right-16 bg-slate-900 text-white text-xs font-semibold py-1.5 px-3 rounded-xl shadow-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
            ¿Dudas? Chatea con nosotros
          </span>
        </a>
      </aside>
    </>
  );
}
