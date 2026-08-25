import React from 'react';
import { 
  Wrench, 
  MapPin, 
  Mail, 
  Phone, 
  MessageSquare
} from 'lucide-react';
import { NavTab } from '../types';

interface FooterProps {
  setActiveTab: (tab: NavTab) => void;
  onOpenWhatsApp: () => void;
}

export const Footer: React.FC<FooterProps> = ({
  setActiveTab,
  onOpenWhatsApp,
}) => {
  return (
    <footer className="bg-slate-50 border-t border-slate-200/80 text-slate-700">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-16">
        <div className="grid grid-cols-1 md:grid-cols-12 gap-10 lg:gap-12">
          
          {/* Column 1: Brand & Slogan */}
          <div className="md:col-span-5 lg:col-span-5 space-y-4">
            <div 
              onClick={() => { setActiveTab('inicio'); window.scrollTo({ top: 0, behavior: 'smooth' }); }}
              className="flex items-center gap-2.5 cursor-pointer inline-flex"
            >
              <div className="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-xs">
                <Wrench className="w-4 h-4 -rotate-45" />
              </div>
              <span className="text-xl font-bold tracking-tight text-slate-900">
                TechFix <span className="text-blue-600">Services</span>
              </span>
            </div>

            <p className="text-slate-600 text-sm leading-relaxed max-w-sm">
              Expertos en soluciones tecnológicas rápidas, transparentes y profesionales.
            </p>

            <div className="pt-2 text-xs text-slate-500">
              <p>Taller equipado con instrumental de laboratorio de última generación.</p>
            </div>
          </div>

          {/* Column 2: Conectar (Social / Channels) */}
          <div className="md:col-span-3 lg:col-span-3 space-y-3">
            <h4 className="text-sm font-bold text-slate-900 uppercase tracking-wider">
              Conectar
            </h4>
            <ul className="space-y-2.5 text-sm">
              <li>
                <a 
                  href="https://facebook.com" 
                  target="_blank" 
                  rel="noreferrer" 
                  className="text-slate-600 hover:text-blue-600 transition-colors"
                >
                  Facebook
                </a>
              </li>
              <li>
                <a 
                  href="https://instagram.com" 
                  target="_blank" 
                  rel="noreferrer" 
                  className="text-slate-600 hover:text-blue-600 transition-colors"
                >
                  Instagram
                </a>
              </li>
              <li>
                <button 
                  onClick={onOpenWhatsApp} 
                  className="text-slate-600 hover:text-emerald-600 transition-colors text-left cursor-pointer"
                >
                  WhatsApp
                </button>
              </li>
              <li>
                <a 
                  href="https://linkedin.com" 
                  target="_blank" 
                  rel="noreferrer" 
                  className="text-slate-600 hover:text-blue-600 transition-colors"
                >
                  LinkedIn
                </a>
              </li>
            </ul>
          </div>

          {/* Column 3: Atención al Cliente */}
          <div className="md:col-span-4 lg:col-span-4 space-y-3">
            <h4 className="text-sm font-bold text-slate-900 uppercase tracking-wider">
              Atención al Cliente
            </h4>
            
            <div className="space-y-3 text-sm text-slate-600">
              <div className="flex items-start gap-2.5">
                <MapPin className="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
                <span>Av. Tecnológica 1024, Distrito Centro</span>
              </div>

              <div className="flex items-center gap-2.5">
                <Mail className="w-4 h-4 text-slate-500 shrink-0" />
                <a href="mailto:soporte@techfix.com" className="hover:text-blue-600 transition-colors">
                  soporte@techfix.com
                </a>
              </div>

              <div className="flex items-center gap-2.5">
                <Phone className="w-4 h-4 text-slate-500 shrink-0" />
                <a href="tel:+12345678900" className="hover:text-blue-600 transition-colors font-medium text-slate-800">
                  +1 234 567 8900
                </a>
              </div>
            </div>

            <div className="pt-2">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-md text-xs font-medium border border-emerald-200">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                Guardia técnica para emergencias activa
              </span>
            </div>
          </div>

        </div>
      </div>

      {/* Bottom Sub-footer */}
      <div className="border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-3">
          <p>© 2024 TechFix Services. Todos los derechos reservados.</p>
          <div className="flex gap-4 text-slate-500">
            <span className="hover:text-slate-700 cursor-pointer">Términos del Servicio</span>
            <span>•</span>
            <span className="hover:text-slate-700 cursor-pointer">Políticas de Garantía</span>
            <span>•</span>
            <span className="hover:text-slate-700 cursor-pointer">Privacidad de Datos</span>
          </div>
        </div>
      </div>
    </footer>
  );
};
