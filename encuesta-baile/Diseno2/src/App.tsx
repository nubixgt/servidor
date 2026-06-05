import React, { useState } from "react";
import { POLL_STEPS } from "./data/pollData";
import { UserSelections } from "./types";
import BackgroundDecoration from "./components/BackgroundDecoration";
import LucideIcon from "./components/LucideIcon";
import GalaTicket from "./components/GalaTicket";
import LiveStatistics from "./components/LiveStatistics";

export default function App() {
  const [currentStep, setCurrentStep] = useState<number>(1);
  const [selections, setSelections] = useState<UserSelections>({
    step1: "ballroom", // Pre-selected Card B from screenshot by default
    step2: "dinner",
    step3: "blackTie"
  });

  const stepIndex = currentStep - 1;
  const currentStepData = POLL_STEPS[stepIndex];

  const handleSelectOption = (optionId: string) => {
    setSelections((prev) => {
      if (currentStep === 1) return { ...prev, step1: optionId };
      if (currentStep === 2) return { ...prev, step2: optionId };
      if (currentStep === 3) return { ...prev, step3: optionId };
      return prev;
    });
  };

  const getSelectionForStep = (stepNum: number) => {
    if (stepNum === 1) return selections.step1;
    if (stepNum === 2) return selections.step2;
    if (stepNum === 3) return selections.step3;
    return null;
  };

  const handleNext = () => {
    if (currentStep < 4) {
      setCurrentStep((prev) => prev + 1);
    }
  };

  const handleBack = () => {
    if (currentStep > 1) {
      setCurrentStep((prev) => prev - 1);
    }
  };

  const handleReset = () => {
    setSelections({
      step1: "ballroom",
      step2: "dinner",
      step3: "blackTie"
    });
    setCurrentStep(1);
  };

  const isStepCompleted = (stepNum: number) => {
    const val = getSelectionForStep(stepNum);
    return val !== null;
  };

  return (
    <div className="relative min-h-screen flex flex-col justify-between overflow-x-hidden pt-6 pb-12 font-sans selection:bg-primary-gold/30 selection:text-white">
      {/* Cinematic Background Illustration */}
      {currentStep <= 3 && <BackgroundDecoration />}

      {/* Content wrapper */}
      <main className="relative z-10 w-full max-w-6xl mx-auto px-6 md:px-8 mt-6 flex-grow flex flex-col justify-start">
        
        {/* Step-by-Step wizard container */}
        {currentStep <= 3 ? (
          <div className="flex flex-col gap-8 md:gap-11 animate-fade-in">
            
            {/* Header / Title Area with step progress */}
            <div className="max-w-2xl">
              {/* Progress Bar Indicator */}
              <div className="flex gap-2.5 mb-8">
                {POLL_STEPS.map((step) => {
                  const isActive = step.id <= currentStep;
                  return (
                    <div
                      key={step.id}
                      className={`h-1 w-12 rounded-full transition-all duration-500 ${
                        isActive ? "bg-primary-gold" : "bg-[#273047] opacity-40"
                      }`}
                      title={`Paso ${step.id}`}
                    />
                  );
                })}
              </div>

              {/* Title Rendering */}
              <h1 className="font-serif text-4xl md:text-5xl font-bold tracking-tight text-white mb-4 leading-tight">
                {currentStepData.titlePrerender}{" "}
                <span className="text-primary-gold italic font-normal font-serif">
                  {currentStepData.titleItalic}
                </span>
              </h1>
              
              {/* Subtitle description */}
              <p className="text-sm md:text-base text-on-surface-variant font-sans max-w-xl leading-relaxed">
                {currentStepData.subtitle}
              </p>
            </div>

            {/* Options Interactive Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
              {currentStepData.options.map((option) => {
                const isSelected = getSelectionForStep(currentStep) === option.id;
                
                return (
                  <div
                    key={option.id}
                    onClick={() => handleSelectOption(option.id)}
                    className={`group cursor-pointer relative rounded-2xl p-6 md:p-8 flex flex-col justify-between gap-6 transition-all duration-300 overflow-hidden card-glow border-2 ${
                      isSelected
                        ? "bg-slate-900/45 border-primary-gold gold-glow"
                        : "bg-slate-905/30 border-border-card hover:border-primary-gold/40"
                    }`}
                  >
                    {/* Top checked action circle bar */}
                    <div className="flex justify-between items-start">
                      <div
                        className={`w-5 h-5 rounded-full border-2 transition-all flex items-center justify-center ${
                          isSelected
                            ? "border-primary-gold bg-brand-midnightScale"
                            : "border-[#99907c]/60 group-hover:border-primary-gold"
                        }`}
                      >
                        {isSelected && (
                          <div className="w-2.5 h-2.5 bg-primary-gold rounded-full" />
                        )}
                      </div>
                    </div>

                    {/* Card main contents layout */}
                    <div className="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                      
                      {/* Circle Thumbnail Image frame with Lucide back-glow icon mask */}
                      <div className="relative w-32 h-32 flex-shrink-0 rounded-full overflow-hidden border border-primary-gold/40 shadow-inner bg-brand-midnight flex items-center justify-center">
                        <div className="absolute inset-x-0 inset-y-0 opacity-15 flex items-center justify-center">
                          <LucideIcon name={option.decorIcon === "stadium" ? "Building" : "Award"} size={72} className="text-primary-gold" />
                        </div>
                        {option.imageUrl && (
                          <img
                            alt={option.title}
                            className="w-full h-full object-cover rounded-full group-hover:scale-105 transition-transform duration-500"
                            src={option.imageUrl}
                            referrerPolicy="no-referrer"
                          />
                        )}
                      </div>

                      {/* Content column details */}
                      <div className="flex flex-col gap-3 text-center sm:text-left flex-grow">
                        <h3 className="font-serif text-xl md:text-2xl font-bold text-white tracking-wide leading-snug">
                          {option.title}
                        </h3>
                        <div className="h-[1px] w-8 bg-primary-gold opacity-50 mx-auto sm:mx-0"></div>
                        
                        {/* Highlights list */}
                        <ul className="flex flex-col gap-3 mt-1.5">
                          {option.highlights.map((hlt, idx) => (
                            <li key={idx} className="flex gap-3 text-left">
                              <div className="text-primary-gold flex-shrink-0 mt-0.5">
                                <LucideIcon name={hlt.iconName} size={15} />
                              </div>
                              <div className="font-sans text-xs text-on-surface-variant flex flex-col">
                                <span className="font-medium text-white/90">{hlt.label}</span>
                                {hlt.subLabel && <span className="text-[10px] opacity-70 mt-0.5">{hlt.subLabel}</span>}
                              </div>
                            </li>
                          ))}
                        </ul>
                      </div>
                    </div>

                    {/* Golden call-to-action bar button */}
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        handleSelectOption(option.id);
                      }}
                      className={`w-full py-3.5 mt-4 text-xs font-bold rounded-lg uppercase tracking-widest transition-all active:scale-98 shadow-md ${
                        isSelected
                          ? "gold-gradient-bg text-brand-midnight shadow-primary-gold/15"
                          : "bg-white/5 text-primary-gold border border-primary-gold/30 hover:bg-primary-gold hover:text-brand-midnight"
                      }`}
                    >
                      {isSelected ? "Propuesta seleccionada" : "Seleccionar esta propuesta"}
                    </button>
                  </div>
                );
              })}
            </div>

            {/* Shield Security Information Banner */}
            <div className="w-full bg-[#191c1f]/40 border border-border-card/30 rounded-xl p-4 flex items-center justify-center gap-3">
              <div className="text-primary-gold">
                <LucideIcon name="ShieldCheck" size={18} />
              </div>
              <p className="text-on-surface-variant text-xs md:text-sm text-center">
                Tu respuesta es anónima y nos ayuda a crear{" "}
                <span className="text-primary-gold font-semibold">el mejor evento para todos.</span>
              </p>
            </div>

            {/* Step navigation buttons bar */}
            <div className="w-full flex justify-between items-center mt-2 pb-6">
              {currentStep > 1 ? (
                <button
                  onClick={handleBack}
                  className="flex items-center gap-2 py-3 px-6 hover:bg-white/5 border border-white/10 text-white hover:text-primary-gold font-sans font-medium rounded-full cursor-pointer transition-all active:scale-95"
                >
                  <LucideIcon name="ChevronLeft" size={14} />
                  <span>Atrás</span>
                </button>
              ) : (
                <div />
              )}

              <button
                onClick={handleNext}
                disabled={!isStepCompleted(currentStep)}
                className={`group flex items-center gap-3 py-3.5 px-8 gold-gradient-bg text-brand-midnight hover:shadow-lg hover:shadow-primary-gold/10 font-sans font-bold uppercase tracking-widest text-xs rounded-full transition-all active:scale-95 cursor-pointer ${
                  !isStepCompleted(currentStep) ? "opacity-40 cursor-not-allowed" : ""
                }`}
              >
                <span>{currentStep === 3 ? "Ver mi ticket" : "Siguiente"}</span>
                <LucideIcon
                  name="ChevronRight"
                  size={14}
                  className="transition-transform group-hover:translate-x-1"
                />
              </button>
            </div>

          </div>
        ) : (
          /* Step 4: Highly polished premium success summary & interactive feedback widget */
          <div className="flex flex-col items-center gap-10 animate-fade-in max-w-4xl mx-auto pb-12">
            
            {/* Header Title Success Banner */}
            <div className="text-center max-w-xl">
              <div className="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary-gold/15 text-primary-gold mb-5">
                <LucideIcon name="Check" size={24} />
              </div>
              <h1 className="font-serif text-4xl md:text-5xl font-bold tracking-tight text-white mb-3">
                ¡Tu propuesta ha sido{" "}
                <span className="text-primary-gold italic font-normal">registrada!</span>
              </h1>
              <p className="text-sm md:text-base text-on-surface-variant font-sans px-4">
                Muchas gracias por participar. Tu opinión nos ayuda a moldear este acontecimiento exclusivo.
                Hemos generado una invitación VIP interactiva con tus elecciones.
              </p>
            </div>

            {/* Split Grid for layout: Ticket generator left, Poll statistics right */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 w-full items-start">
              {/* Left Column: Personalized Interactive Ticket */}
              <div className="flex flex-col items-center gap-4">
                <div className="w-full flex items-center justify-between px-2 text-xs text-on-surface-variant tracking-wider uppercase font-sans font-bold">
                  <span>Pase de Invitación VIP</span>
                  <span className="text-primary-gold flex items-center gap-1">
                    <LucideIcon name="Sparkle" size={10} className="animate-spin" />
                    Exclusivo Nubix
                  </span>
                </div>
                <GalaTicket selections={selections} />
              </div>

              {/* Right Column: Dynamic Live Stats Panel */}
              <div className="flex flex-col gap-4">
                <div className="w-full flex items-center justify-between px-2 text-xs text-on-surface-variant tracking-wider uppercase font-sans font-bold">
                  <span>Auditoría de Respuestas</span>
                  <span className="text-emerald-400 flex items-center gap-1.5 font-bold">
                    <span className="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" />
                    Encuesta Activa
                  </span>
                </div>
                <LiveStatistics selections={selections} onReset={handleReset} />
              </div>
            </div>

          </div>
        )}

      </main>

      {/* Styled Brand Footer */}
      <footer className="w-full max-w-6xl mx-auto border-t border-white/5 mt-12 pt-6 px-6 md:px-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs text-on-surface-variant opacity-75">
        <div className="flex items-center gap-3">
          <span className="font-serif font-black text-lg tracking-wider text-primary-gold">Nubix</span>
          <span className="h-3 w-[1px] bg-white/10 hidden sm:inline" />
          <span className="font-sans text-[11px] uppercase tracking-wider hidden sm:inline">Eventos de Prestigio</span>
        </div>

        <div className="flex flex-wrap gap-x-4 gap-y-2 justify-center font-sans">
          <a className="hover:text-primary-gold transition-colors cursor-pointer" href="#privacy">Privacy Policy</a>
          <span>•</span>
          <a className="hover:text-primary-gold transition-colors cursor-pointer" href="#terms">Terms of Service</a>
          <span>•</span>
          <a className="hover:text-primary-gold transition-colors cursor-pointer" href="#cookies">Cookie Settings</a>
        </div>

        <div className="text-center sm:text-right font-sans text-[11px]">
          © 2026 Premium Events / Nubix. Todos los derechos reservados.
        </div>
      </footer>
    </div>
  );
}
