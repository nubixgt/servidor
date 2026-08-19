import React from 'react';
import { 
  X, 
  HelpCircle, 
  Mountain, 
  Mail, 
  Phone
} from 'lucide-react';

interface SupportModalProps {
  onClose: () => void;
}

export const SupportModal: React.FC<SupportModalProps> = ({ onClose }) => {
  return (
    <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn">
      <div className="glass-panel border border-white/20 rounded-2xl w-full max-w-2xl p-6 sm:p-8 shadow-2xl text-xs text-[#f1f5f9]">
        {/* Header */}
        <div className="flex justify-between items-center pb-3 border-b border-white/10 mb-4">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-[#22c55e]/20 border border-[#4ade80]/30 flex items-center justify-center text-[#4ade80]">
              <HelpCircle className="w-4 h-4" />
            </div>
            <h3 className="text-base font-bold text-white">Centro de Ayuda & Metodología Keyline</h3>
          </div>
          <button onClick={onClose} className="text-[#94a3b8] hover:text-white">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
          {/* Keyline Summary Guide */}
          <div className="p-4 bg-black/30 rounded-xl border border-white/10 space-y-2">
            <h4 className="font-bold text-[#4ade80] text-sm flex items-center gap-1.5">
              <Mountain className="w-4 h-4" />
              ¿Qué es el Diseño Keyline (Línea Clave)?
            </h4>
            <p className="text-[#cbd5e1] leading-relaxed">
              El diseño Keyline es una técnica de ordenamiento hidrológico que aprovecha las curvas de nivel topográficas naturales para redistribuir el agua de lluvia desde las vaguadas hacia las crestas o laderas secas, evitando la erosión y maximizando la infiltración profunda en el suelo.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div className="p-3 bg-black/30 border border-white/10 rounded-xl space-y-1">
              <span className="font-bold text-white block">1. Zanjas de Infiltración</span>
              <p className="text-[11px] text-[#94a3b8]">
                Canales a nivel con caballón vegetado que capturan la escorrentía superficial.
              </p>
            </div>

            <div className="p-3 bg-black/30 border border-white/10 rounded-xl space-y-1">
              <span className="font-bold text-white block">2. Bioindicadores</span>
              <p className="text-[11px] text-[#94a3b8]">
                Evaluación cualitativa de lombrices, micelio y agregados de suelo fértil.
              </p>
            </div>

            <div className="p-3 bg-black/30 border border-white/10 rounded-xl space-y-1">
              <span className="font-bold text-white block">3. Validación V2</span>
              <p className="text-[11px] text-[#94a3b8]">
                Certificación técnica con coordenadas GPS georreferenciadas.
              </p>
            </div>
          </div>

          {/* Contact Support */}
          <div className="p-4 bg-white/10 border border-white/15 rounded-xl space-y-2">
            <h4 className="font-bold text-white text-xs">Mesa de Soporte Técnico para Supervisores</h4>
            <div className="flex flex-col sm:flex-row gap-4 text-xs">
              <div className="flex items-center gap-2 text-[#4ade80]">
                <Mail className="w-4 h-4" />
                <span>soporte@keyline.gt</span>
              </div>
              <div className="flex items-center gap-2 text-[#38bdf8]">
                <Phone className="w-4 h-4" />
                <span>+502 2300-8800 (Ext. 402)</span>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-6 pt-3 border-t border-white/10 flex justify-end">
          <button
            type="button"
            onClick={onClose}
            className="px-5 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-white font-semibold rounded-xl"
          >
            Entendido
          </button>
        </div>
      </div>
    </div>
  );
};
