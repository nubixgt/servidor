import React, { useState } from 'react';
import { X, Send, CheckCircle2, Zap, Shield } from 'lucide-react';

interface QuoteModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialServiceTitle?: string;
  onSuccess: (ticketCode: string) => void;
}

export const QuoteModal: React.FC<QuoteModalProps> = ({
  isOpen,
  onClose,
  initialServiceTitle = '',
  onSuccess,
}) => {
  const [device, setDevice] = useState(initialServiceTitle || 'PC & Laptops');
  const [issue, setIssue] = useState('');
  const [name, setName] = useState('');
  const [phone, setPhone] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    setTimeout(() => {
      const code = `TF-${Math.floor(1000 + Math.random() * 9000)}`;
      setIsSubmitting(false);
      onSuccess(code);
      onClose();
    }, 600);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-blue-600 to-blue-700 p-5 text-white flex items-center justify-between">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center">
              <Zap className="w-4 h-4 text-white" />
            </div>
            <div>
              <h3 className="text-base font-bold">Solicitar Presupuesto Rápido</h3>
              <p className="text-xs text-blue-100">Respuesta en menos de 30 minutos</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-4">
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Dispositivo o Servicio
            </label>
            <select
              value={device}
              onChange={(e) => setDevice(e.target.value)}
              className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
            >
              <option value="PC & Laptops">PC & Laptops (Notebook / Torre)</option>
              <option value="Smartphones">Smartphones (iPhone / Samsung / Otros)</option>
              <option value="Televisores">Televisores & Smart TVs</option>
              <option value="Consolas">Consolas (PS5 / PS4 / Switch / Xbox)</option>
              <option value="Sistemas OS">Sistemas OS (Formateo / Optimización)</option>
              <option value="Software & Office">Software & Office (Virus / Licencias)</option>
              <option value="Mantenimiento Preventivo">Mantenimiento Preventivo Térmico</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Falla o síntoma que presenta
            </label>
            <textarea
              rows={2}
              required
              placeholder="Ej: Pantalla con rayas verdes, no prende tras tormenta, etc."
              value={issue}
              onChange={(e) => setIssue(e.target.value)}
              className="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Tu Nombre
              </label>
              <input
                type="text"
                required
                placeholder="Nombre y apellido"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Teléfono / WhatsApp
              </label>
              <input
                type="tel"
                required
                placeholder="+1 234 567 8900"
                value={phone}
                onChange={(e) => setPhone(e.target.value)}
                className="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-600"
              />
            </div>
          </div>

          <div className="p-3 bg-blue-50/70 border border-blue-100 rounded-xl text-[11px] text-blue-900 flex items-center gap-2">
            <Shield className="w-4 h-4 text-blue-600 shrink-0" />
            <span>Presupuesto sin cargo ni compromiso de reparación.</span>
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={isSubmitting}
              className="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-xs sm:text-sm transition-all shadow-md cursor-pointer flex items-center justify-center gap-2 disabled:opacity-50"
            >
              {isSubmitting ? (
                <span>Procesando...</span>
              ) : (
                <>
                  <span>Enviar y Recibir Cotización</span>
                  <Send className="w-4 h-4" />
                </>
              )}
            </button>
          </div>
        </form>

      </div>
    </div>
  );
};
