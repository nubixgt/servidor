import React from "react";
import { UserSelections } from "../types";
import { INITIAL_STATS } from "../data/pollData";
import LucideIcon from "./LucideIcon";

interface LiveStatisticsProps {
  selections: UserSelections;
  onReset: () => void;
}

export default function LiveStatistics({ selections, onReset }: LiveStatisticsProps) {
  // Compute votes dynamically by adding user selections
  const stadiumVotes = INITIAL_STATS.format.stadium + (selections.step1 === "stadium" ? 1 : 0);
  const ballroomVotes = INITIAL_STATS.format.ballroom + (selections.step1 === "ballroom" ? 1 : 0);
  const totalFormat = stadiumVotes + ballroomVotes;

  const stadiumPerc = Math.round((stadiumVotes / totalFormat) * 100);
  const ballroomPerc = Math.round((ballroomVotes / totalFormat) * 100);

  const cocktailVotes = INITIAL_STATS.gastronomy.cocktails + (selections.step2 === "cocktails" ? 1 : 0);
  const dinnerVotes = INITIAL_STATS.gastronomy.dinner + (selections.step2 === "dinner" ? 1 : 0);
  const totalGastronomy = cocktailVotes + dinnerVotes;

  const cocktailPerc = Math.round((cocktailVotes / totalGastronomy) * 100);
  const dinnerPerc = Math.round((dinnerVotes / totalGastronomy) * 100);

  const blackTieVotes = INITIAL_STATS.dressCode.blackTie + (selections.step3 === "blackTie" ? 1 : 0);
  const contemporaryVotes = INITIAL_STATS.dressCode.contemporary + (selections.step3 === "contemporary" ? 1 : 0);
  const totalDressCode = blackTieVotes + contemporaryVotes;

  const blackTiePerc = Math.round((blackTieVotes / totalDressCode) * 100);
  const contemporaryPerc = Math.round((contemporaryVotes / totalDressCode) * 100);

  return (
    <div className="w-full max-w-xl bg-on-secondary/10 border border-outline-variant/20 rounded-2xl p-6 md:p-8 animate-fade-in flex flex-col gap-6">
      <div className="flex items-center gap-3 border-b border-white/5 pb-4">
        <div className="p-2 bg-primary-gold/10 rounded-lg text-primary-gold">
          <LucideIcon name="Users2" size={22} />
        </div>
        <div>
          <h3 className="font-serif text-xl font-bold text-white tracking-wide">Resultados de la Comunidad</h3>
          <p className="text-secondary/70 text-xs font-sans">Votos acumulados en tiempo real • {totalFormat} participantes</p>
        </div>
      </div>

      <div className="flex flex-col gap-5">
        {/* Category 1: Formato del Evento */}
        <div>
          <div className="flex justify-between items-center text-xs text-on-surface-variant uppercase tracking-wider font-sans font-semibold mb-2">
            <span>1. Formato de Evento Preferido</span>
            <span className="text-[10px] text-white/50">{totalFormat} votos</span>
          </div>
          <div className="flex flex-col gap-3 bg-brand-midnight/40 rounded-lg p-3 border border-white/5">
            {/* Item 1 */}
            <div>
              <div className="flex justify-between items-center text-xs mb-1">
                <span className={`font-medium ${selections.step1 === "stadium" ? "text-primary-gold" : "text-white/80"}`}>
                  Baile en Estadio {selections.step1 === "stadium" && " (Tu Voto)"}
                </span>
                <span className="font-mono">{stadiumPerc}%</span>
              </div>
              <div className="w-full h-2 bg-brand-midnight rounded-full overflow-hidden">
                <div
                  className="h-full bg-slate-500 rounded-full transition-all duration-1000 ease-out"
                  style={{ width: `${stadiumPerc}%` }}
                ></div>
              </div>
            </div>
            {/* Item 2 */}
            <div>
              <div className="flex justify-between items-center text-xs mb-1">
                <span className={`font-medium ${selections.step1 === "ballroom" ? "text-primary-gold" : "text-white/80"}`}>
                  Salón + Concierto {selections.step1 === "ballroom" && " (Tu Voto)"}
                </span>
                <span className="font-mono text-primary-gold font-bold">{ballroomPerc}%</span>
              </div>
              <div className="w-full h-2 bg-brand-midnight rounded-full overflow-hidden">
                <div
                  className="h-full bg-primary-gold rounded-full transition-all duration-1000 ease-out"
                  style={{ width: `${ballroomPerc}%` }}
                ></div>
              </div>
            </div>
          </div>
        </div>

        {/* Category 2: Catering */}
        <div>
          <div className="flex justify-between items-center text-xs text-on-surface-variant uppercase tracking-wider font-sans font-semibold mb-2">
            <span>2. Propuesta Gastronómica</span>
            <span className="text-[10px] text-white/50">{totalGastronomy} votos</span>
          </div>
          <div className="flex flex-col gap-3 bg-brand-midnight/40 rounded-lg p-3 border border-white/5">
            {/* Item 1 */}
            <div>
              <div className="flex justify-between items-center text-xs mb-1">
                <span className={`font-medium ${selections.step2 === "cocktails" ? "text-primary-gold" : "text-white/80"}`}>
                  Cócteles & Finger Foods {selections.step2 === "cocktails" && " (Tu Voto)"}
                </span>
                <span className="font-mono">{cocktailPerc}%</span>
              </div>
              <div className="w-full h-2 bg-brand-midnight rounded-full overflow-hidden">
                <div
                  className="h-full bg-slate-500 rounded-full transition-all duration-1000 ease-out"
                  style={{ width: `${cocktailPerc}%` }}
                ></div>
              </div>
            </div>
            {/* Item 2 */}
            <div>
              <div className="flex justify-between items-center text-xs mb-1">
                <span className={`font-medium ${selections.step2 === "dinner" ? "text-primary-gold" : "text-white/80"}`}>
                  Cena de Gala (3 tempos) {selections.step2 === "dinner" && " (Tu Voto)"}
                </span>
                <span className="font-mono text-primary-gold font-bold">{dinnerPerc}%</span>
              </div>
              <div className="w-full h-2 bg-brand-midnight rounded-full overflow-hidden">
                <div
                  className="h-full bg-primary-gold rounded-full transition-all duration-1000 ease-out"
                  style={{ width: `${dinnerPerc}%` }}
                ></div>
              </div>
            </div>
          </div>
        </div>

        {/* Category 3: Dress Code */}
        <div>
          <div className="flex justify-between items-center text-xs text-on-surface-variant uppercase tracking-wider font-sans font-semibold mb-2">
            <span>3. Preferencia de Vestimenta</span>
            <span className="text-[10px] text-white/50">{totalDressCode} votos</span>
          </div>
          <div className="flex flex-col gap-3 bg-brand-midnight/40 rounded-lg p-3 border border-white/5">
            {/* Item 1 */}
            <div>
              <div className="flex justify-between items-center text-xs mb-1">
                <span className={`font-medium ${selections.step3 === "blackTie" ? "text-primary-gold" : "text-white/80"}`}>
                  Black Tie / Alta Gala {selections.step3 === "blackTie" && " (Tu Voto)"}
                </span>
                <span className="font-mono text-primary-gold font-bold">{blackTiePerc}%</span>
              </div>
              <div className="w-full h-2 bg-brand-midnight rounded-full overflow-hidden">
                <div
                  className="h-full bg-primary-gold rounded-full transition-all duration-1000 ease-out"
                  style={{ width: `${blackTiePerc}%` }}
                ></div>
              </div>
            </div>
            {/* Item 2 */}
            <div>
              <div className="flex justify-between items-center text-xs mb-1">
                <span className={`font-medium ${selections.step3 === "contemporary" ? "text-primary-gold" : "text-white/80"}`}>
                  Elegancia Contemporánea {selections.step3 === "contemporary" && " (Tu Voto)"}
                </span>
                <span className="font-mono">{contemporaryPerc}%</span>
              </div>
              <div className="w-full h-2 bg-brand-midnight rounded-full overflow-hidden">
                <div
                  className="h-full bg-slate-500 rounded-full transition-all duration-1000 ease-out"
                  style={{ width: `${contemporaryPerc}%` }}
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="pt-2 border-t border-white/5 flex justify-center">
        <button
          onClick={onReset}
          className="flex items-center gap-2 text-xs text-slate-400 hover:text-primary-gold active:scale-95 transition-all py-1.5 px-4 rounded-full border border-white/10 hover:border-primary-gold/40 cursor-pointer"
        >
          <LucideIcon name="RotateCcw" size={12} />
          <span>Restablecer y Votar de Nuevo</span>
        </button>
      </div>
    </div>
  );
}
