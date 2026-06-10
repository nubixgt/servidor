import React, { useState } from 'react';
import { CheckCircle, ArrowRight, CheckCircle2, Landmark, Music } from 'lucide-react';
import { VoteOption } from '../types';

// ==========================================
// INTERNAL SUB-COMPONENT: Header
// ==========================================
interface HeaderProps {
  title: string;
  subtitle: string;
}

function Header({ title, subtitle }: HeaderProps) {
  return (
    <header className="text-center mb-10 md:mb-16 space-y-4">
      <div className="inline-flex items-center gap-2 px-3 py-1 bg-surface-container rounded-full text-xs text-primary-base font-semibold tracking-wider uppercase mb-2">
        <CheckCircle className="w-3.5 h-3.5 text-secondary-base" />
        Sondeo Ciudadano Oficial
      </div>
      <h1 className="text-3xl md:text-5xl font-extrabold text-primary-base tracking-tight select-none">
        {title}
      </h1>
      <p className="text-base md:text-lg text-on-surface-variant max-w-2xl mx-auto leading-relaxed">
        {subtitle}
      </p>
    </header>
  );
}

// ==========================================
// INTERNAL SUB-COMPONENT: SurveyCard
// ==========================================
interface SurveyCardProps {
  key?: string | number;
  option: VoteOption;
  isSelected: boolean;
  onSelect: () => void;
}

function SurveyCard({ option, isSelected, onSelect }: SurveyCardProps) {
  const [imgFailed, setImgFailed] = useState(false);

  return (
    <button
      id={`option-${option.id}`}
      onClick={onSelect}
      className={`group relative text-left bg-surface-container-lowest rounded-xl p-6 md:p-8 flex flex-col justify-between overflow-hidden cursor-pointer transition-all duration-300 border-2 w-full
        ${isSelected 
          ? 'border-secondary-base shadow-lg ring-2 ring-secondary-base/20' 
          : 'border-slate-100 hover:border-secondary-base hover:-translate-y-1 hover:shadow-lg'
        }
      `}
    >
      <div className="w-full">
        {/* Image Frame */}
        <div className="aspect-video w-full mb-6 rounded-lg overflow-hidden bg-slate-900 relative">
          {!imgFailed ? (
            <img
              src={option.image}
              alt={option.imageAlt}
              referrerPolicy="no-referrer"
              onError={() => setImgFailed(true)}
              className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-103"
            />
          ) : (
            /* Custom gorgeous SVG graphic gradient fallback per choice */
            <div className="w-full h-full flex flex-col items-center justify-center p-6 bg-gradient-to-br from-[#091426] to-[#1e293b] text-white relative">
              <div className="absolute inset-0 opacity-10 bg-[radial-gradient(#ffffff_1px,transparent_1px)] [background-size:16px_16px]" />
              
              {option.id === 'option-a' ? (
                <>
                  <div className="flex gap-1.5 mb-2">
                    <span className="w-1.5 h-6 bg-secondary-base animate-pulse rounded-full" />
                    <span className="w-1.5 h-8 bg-indigo-400 animate-pulse delay-75 rounded-full" />
                    <span className="w-1.5 h-6 bg-secondary-base animate-pulse delay-150 rounded-full" />
                  </div>
                  <Landmark className="w-12 h-12 text-indigo-200 mb-2 opacity-90 stroke-[1.5]" />
                  <span className="text-[11px] font-black tracking-widest uppercase text-slate-300">ESTADIO ARENA</span>
                </>
              ) : (
                <>
                  <Music className="w-12 h-12 text-secondary-fixed mb-2 stroke-[1.5]" />
                  <span className="text-[11px] font-black tracking-widest uppercase text-indigo-300">SALÓN DE CONCIERTOS</span>
                </>
              )}
              
              <span className="absolute bottom-3 right-3 text-[9px] font-mono text-slate-500 bg-slate-950/40 px-2 py-0.5 rounded">
                Ilustración Digital Activa
              </span>
            </div>
          )}

          {isSelected && (
            <div className="absolute inset-0 bg-secondary-base/5 flex items-center justify-center transition-opacity" />
          )}
        </div>

        {/* Badge & Check Indicator */}
        <div className="flex items-center justify-between mb-4">
          <span className={`text-xs font-bold px-3 py-1.5 rounded-full select-none ${option.badgeColor}`}>
            {option.badge}
          </span>
          <div className="flex items-center gap-1.5">
            {isSelected ? (
              <span className="flex items-center gap-1 text-secondary-base text-xs font-semibold animate-fade-in">
                <CheckCircle2 className="w-5 h-5 text-secondary-base fill-secondary-base/10" />
              </span>
            ) : (
              <div className="w-5 h-5 rounded-full border-2 border-slate-200 group-hover:border-slate-300 transition-colors" />
            )}
          </div>
        </div>

        {/* Text Details */}
        <h2 className="text-xl md:text-2xl font-bold text-primary-base mb-2 group-hover:text-secondary-base transition-colors">
          {option.title}
        </h2>
        <p className="text-sm md:text-base text-on-surface-variant leading-relaxed">
          {option.description}
        </p>
      </div>

      {/* Footer selector simulation */}
      <div className="mt-8 pt-4 border-t border-slate-50 flex items-center font-semibold text-secondary-base text-xs md:text-sm tracking-wider w-full">
        <span className="group-hover:mr-1 transition-all uppercase">
          {isSelected ? 'PROPUESTA SELECCIONADA' : 'SELECCIONAR ESTA PROPUESTA'}
        </span>
        <ArrowRight className={`w-4 h-4 ml-1.5 transition-transform ${isSelected ? 'translate-x-1' : 'group-hover:translate-x-1'}`} />
      </div>
    </button>
  );
}

// ==========================================
// MAIN EXPORTED SCREEN: SondeoFormScreen
// ==========================================
interface SondeoFormScreenProps {
  options: VoteOption[];
  selectedOptionId: string | null;
  isSubmitting: boolean;
  submitText: string;
  submitColor: string;
  onSelectOption: (id: string) => void;
  onSubmitVote: () => void;
}

export default function SondeoFormScreen({
  options,
  selectedOptionId,
  isSubmitting,
  submitText,
  submitColor,
  onSelectOption,
  onSubmitVote
}: SondeoFormScreenProps) {
  return (
    <div className="space-y-6">
      <Header 
        title="¿CÓMO PREFERÍS EL EVENTO?"
        subtitle="Marcá tu preferencia para ayudarnos a organizar la mejor experiencia democrática y participativa."
      />

      {/* Sub-grid options */}
      <div id="survey-options-grid" className="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 mb-12">
        {options.map((opt) => (
          <SurveyCard
            key={opt.id}
            option={opt}
            isSelected={selectedOptionId === opt.id}
            onSelect={() => onSelectOption(opt.id)}
          />
        ))}
      </div>

      {/* Dynamic Confirm trigger button */}
      <div className="flex flex-col items-center justify-center space-y-3">
        <button
          id="submit-btn"
          onClick={onSubmitVote}
          disabled={!selectedOptionId || isSubmitting}
          className={`px-12 py-4 rounded-lg font-bold text-sm tracking-wide shadow-md transition-all duration-300 transform select-none cursor-pointer
            ${selectedOptionId 
              ? `${submitColor} hover:bg-slate-800 text-white active:scale-95` 
              : 'bg-slate-200 text-slate-400 cursor-not-allowed border border-slate-300'
            }
            relative overflow-hidden min-w-[320px] text-center
          `}
        >
          {isSubmitting ? (
            <span className="flex items-center justify-center gap-2">
              <span className="w-4 h-4 border-2 border-white/65 border-t-transparent rounded-full animate-spin" />
              {submitText}
            </span>
          ) : (
            <span>{submitText}</span>
          )}
        </button>
        <p className="text-[11px] text-on-surface-variant text-center max-w-sm">
          Al confirmar, tu preferencia se integrará con firmas criptográficas al registro oficial de Democratic Pulse.
        </p>
      </div>
    </div>
  );
}
