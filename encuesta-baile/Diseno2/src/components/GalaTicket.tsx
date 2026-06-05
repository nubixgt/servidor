import React, { useState } from "react";
import LucideIcon from "./LucideIcon";
import { UserSelections } from "../types";

interface GalaTicketProps {
  selections: UserSelections;
}

export default function GalaTicket({ selections }: GalaTicketProps) {
  const [userName, setUserName] = useState<string>("");
  const [downloaded, setDownloaded] = useState<boolean>(false);

  const getFormatLabel = () => {
    if (selections.step1 === "stadium") return "Baile Social en Estadio";
    if (selections.step1 === "ballroom") return "Baile en Salón + Concierto";
    return "Baile en Salón + Concierto";
  };

  const getGastronomyLabel = () => {
    if (selections.step2 === "cocktails") return "Cócteles & Finger Foods";
    if (selections.step2 === "dinner") return "Cena de Gala (3 Tiempos)";
    return "Cena de Gala (3 Tiempos)";
  };

  const getDressCodeLabel = () => {
    if (selections.step3 === "blackTie") return "Rigurosa Etiqueta / Black Tie";
    if (selections.step3 === "contemporary") return "Elegancia Contemporánea";
    return "Elegancia Contemporánea";
  };

  const handleDownload = () => {
    setDownloaded(true);
    setTimeout(() => {
      setDownloaded(false);
      alert(
        `¡Su Pase Vip Digital para "${
          userName.trim() || "Invitado Distinguido"
        }" se ha generado y guardado exitosamente en su dispositivo!`
      );
    }, 1500);
  };

  return (
    <div className="w-full flex flex-col items-center gap-8 animate-fade-in">
      {/* Name Input Box */}
      <div className="w-full max-w-xl bg-on-secondary/10 border border-outline-variant/30 rounded-xl p-6 mb-2">
        <label className="block text-primary-gold font-sans font-medium text-sm tracking-widest uppercase mb-3">
          Personalizá tu Invitación Premium
        </label>
        <p className="text-secondary/70 text-xs mb-4">
          Ingresá tu nombre completo para grabar tu Pase Digital de Gala numerado de Nubix.
        </p>
        <div className="relative">
          <input
            type="text"
            value={userName}
            onChange={(e) => setUserName(e.target.value)}
            placeholder="Ej. Miguel Fuentes"
            maxLength={36}
            className="w-full bg-brand-midnight/60 border border-outline/30 focus:border-primary-gold rounded-lg py-3 px-4 text-white placeholder-white/30 focus:outline-none focus:ring-1 focus:ring-primary-gold transition-all font-sans"
          />
          {userName.trim() && (
            <div className="absolute right-3 top-3 text-primary-gold">
              <LucideIcon name="Check" size={18} />
            </div>
          )}
        </div>
      </div>

      {/* Ticket Wrapper */}
      <div className="relative w-full max-w-xl bg-gradient-to-br from-[#101424] to-[#1a233d] border-2 border-primary-gold/40 rounded-2xl overflow-hidden shadow-2xl gold-glow">
        
        {/* Ticket Header background pattern */}
        <div className="absolute inset-0 opacity-5 pointer-events-none bg-[radial-gradient(#f2ca50_1px,transparent_1px)] [background-size:16px_16px]"></div>
        
        {/* Fine gold lines / corners */}
        <div className="absolute top-2 left-2 right-2 bottom-2 border border-primary-gold/20 rounded-xl pointer-events-none"></div>
        
        {/* Main Badge Body */}
        <div className="relative p-6 md:p-8 flex flex-col gap-6">
          {/* Top Row: Brand & Pass No. */}
          <div className="flex justify-between items-center border-b border-primary-gold/15 pb-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-full border border-primary-gold flex items-center justify-center bg-brand-midnight">
                <span className="text-primary-gold font-serif font-bold text-sm tracking-tighter">N</span>
              </div>
              <div>
                <span className="font-serif font-bold text-lg tracking-wider text-primary-gold">Nubix</span>
                <span className="text-[9px] block text-on-surface-variant font-sans tracking-widest uppercase">Gala de Fin de Año</span>
              </div>
            </div>
            <div className="text-right">
              <span className="text-[10px] text-on-surface-variant uppercase tracking-widest block">Pase VIP Numerado</span>
              <span className="font-mono text-xs text-primary-gold font-bold">NUX-2026-{(userName.length * 47 + 512).toString().padStart(4, '0')}</span>
            </div>
          </div>

          {/* Guest Name & Welcome */}
          <div className="py-2 text-center">
            <span className="text-[10px] text-primary-gold/70 tracking-widest uppercase block mb-1">Invitado de Honor</span>
            <h2 className="font-serif text-3xl font-semibold tracking-wide text-white italic capitalize min-h-[40px]">
              {userName.trim() || "Invitado Distinguido"}
            </h2>
          </div>

          {/* Selections / RSVP Specs */}
          <div className="bg-brand-midnight/55 rounded-xl p-4 border border-outline-variant/20 flex flex-col gap-3">
            <div className="flex items-center justify-between text-xs pb-2 border-b border-white/5">
              <span className="text-on-surface-variant uppercase tracking-widest font-sans font-medium text-[10px]">Acontecimiento:</span>
              <span className="text-white hover:text-primary-gold transition-colors font-semibold">{getFormatLabel()}</span>
            </div>
            
            <div className="flex items-center justify-between text-xs pb-2 border-b border-white/5">
              <span className="text-on-surface-variant uppercase tracking-widest font-sans font-medium text-[10px]">Catering Elegido:</span>
              <span className="text-white font-semibold">{getGastronomyLabel()}</span>
            </div>

            <div className="flex items-center justify-between text-xs">
              <span className="text-on-surface-variant uppercase tracking-widest font-sans font-medium text-[10px]">Código de Etiqueta:</span>
              <span className="text-primary-gold font-semibold">{getDressCodeLabel()}</span>
            </div>
          </div>

          {/* Ticket Footer / Footer Info & Barcode/QR Row */}
          <div className="flex items-center justify-between border-t border-primary-gold/15 pt-5 mt-2">
            <div className="flex flex-col gap-1 text-[11px] text-on-surface-variant">
              <div className="flex items-center gap-1.5">
                <LucideIcon name="Calendar" size={12} className="text-primary-gold" />
                <span className="font-semibold text-white">Viernes, 11 de Dic, 2026</span>
              </div>
              <div className="flex items-center gap-1.5">
                <LucideIcon name="MapPin" size={12} className="text-primary-gold" />
                <span>21:00 hs — Centro de Eventos</span>
              </div>
            </div>

            {/* Simulated QR Code SVG */}
            <div className="flex items-center justify-center p-2 bg-white rounded-lg w-16 h-16 border border-primary-gold/30">
              <LucideIcon name="QrCode" className="text-brand-midnight" size={54} />
            </div>
          </div>
        </div>

        {/* Dynamic Ticket Rip edges */}
        <div className="absolute left-0 top-1/2 -translate-y-1/2 w-4 h-8 bg-brand-midnight rounded-r-full border-r border-t border-b border-primary-gold/40 z-10"></div>
        <div className="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-8 bg-brand-midnight rounded-l-full border-l border-t border-b border-primary-gold/40 z-10"></div>
      </div>

      {/* Download button */}
      <button
        onClick={handleDownload}
        disabled={downloaded}
        className={`w-full max-w-xl py-4 flex items-center justify-center gap-3 rounded-lg font-sans font-bold uppercase tracking-wider text-sm transition-all shadow-lg active:scale-98 ${
          downloaded
            ? "bg-slate-700 text-slate-300"
            : "gold-gradient-bg text-brand-midnight hover:shadow-primary-gold/20"
        }`}
      >
        {downloaded ? (
          <>
            <span className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full" />
            <span>Generando ticket personalizado...</span>
          </>
        ) : (
          <>
            <LucideIcon name="Check" size={18} />
            <span>Confirmar Propuesta y Descargar Pase Digital</span>
          </>
        )}
      </button>
    </div>
  );
}
