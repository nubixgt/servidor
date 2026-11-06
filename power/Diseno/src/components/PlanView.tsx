import React from 'react';
import { WorkoutSession } from '../types';

interface PlanViewProps {
  weeklyWorkouts: WorkoutSession[];
  onOpenWorkout: (workout: WorkoutSession) => void;
  onGenerateSeasonPlan: () => void;
  onGenerateWeekPlan: () => void;
  onRejectAdjust: () => void;
  onUploadAllToGarmin: () => void;
  onOpenPdf: () => void;
  onOpenMacrocycle: () => void;
  onOpenAnalytics: () => void;
  onOpenHealth: () => void;
}

export const PlanView: React.FC<PlanViewProps> = ({
  weeklyWorkouts,
  onOpenWorkout,
  onGenerateSeasonPlan,
  onGenerateWeekPlan,
  onRejectAdjust,
  onUploadAllToGarmin,
  onOpenPdf,
  onOpenMacrocycle,
  onOpenAnalytics,
  onOpenHealth,
}) => {
  return (
    <div id="plan-view-container" className="space-y-7 pb-20 select-none">
      {/* Plan Header */}
      <header className="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-5 pb-5 border-b border-white/10">
        <div>
          <h2 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Tu Plan de Entrenamiento
          </h2>
          <p className="text-sm text-[#c5c9ac] mt-1 font-medium">
            Prescripción periodizada y adaptativa sincronizada con tu reloj de pulso Garmin.
          </p>
        </div>

        {/* Action Controls */}
        <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
          {/* Quick tools */}
          <div className="flex items-center gap-1.5 glass-pill p-1 rounded-xl">
            <button
              onClick={onOpenPdf}
              className="px-3 py-1.5 text-xs font-bold text-[#c5c9ac] hover:text-[#caf300] hover:bg-white/10 rounded-lg transition-colors uppercase tracking-wider flex items-center gap-1"
            >
              <span className="material-symbols-outlined text-[16px]">picture_as_pdf</span>
              <span>PDF</span>
            </button>
            <button
              onClick={onOpenMacrocycle}
              className="px-3 py-1.5 text-xs font-bold text-[#c5c9ac] hover:text-[#caf300] hover:bg-white/10 rounded-lg transition-colors uppercase tracking-wider flex items-center gap-1"
            >
              <span className="material-symbols-outlined text-[16px]">timeline</span>
              <span>Macrociclo</span>
            </button>
            <button
              onClick={onOpenAnalytics}
              className="px-3 py-1.5 text-xs font-bold text-[#c5c9ac] hover:text-[#caf300] hover:bg-white/10 rounded-lg transition-colors uppercase tracking-wider flex items-center gap-1"
            >
              <span className="material-symbols-outlined text-[16px]">bar_chart</span>
              <span>Análisis</span>
            </button>
            <button
              onClick={onOpenHealth}
              className="px-3 py-1.5 text-xs font-bold text-[#c5c9ac] hover:text-[#caf300] hover:bg-white/10 rounded-lg transition-colors uppercase tracking-wider flex items-center gap-1"
            >
              <span className="material-symbols-outlined text-[16px]">favorite</span>
              <span>Salud</span>
            </button>
          </div>

          {/* Core Action Buttons */}
          <button
            id="btn-reject-adjust"
            onClick={onRejectAdjust}
            className="px-4 py-2.5 text-xs font-black text-[#ffb4ab] hover:bg-[#93000a]/30 border border-[#ffb4ab]/30 rounded-xl uppercase tracking-wider transition-colors glass-pill"
          >
            Rechazar / Ajustar
          </button>

          <button
            id="btn-upload-all-garmin"
            onClick={onUploadAllToGarmin}
            className="flex items-center gap-2 px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)] active:scale-95 hover:scale-[1.02]"
          >
            <span className="material-symbols-outlined text-[18px]">cloud_upload</span>
            <span>Subir Todo a Garmin</span>
          </button>
        </div>
      </header>

      {/* Plan Generation Actions Cards */}
      <section className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Generar Plan de Temporada */}
        <div
          onClick={onGenerateSeasonPlan}
          className="glass-card rounded-3xl p-6 sm:p-7 flex items-center justify-between cursor-pointer hover:border-[#caf300] hover:scale-[1.01] transition-all group shadow-2xl border border-white/10"
        >
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/50 flex items-center justify-center text-[#caf300] group-hover:scale-110 transition-transform shadow-[0_0_15px_rgba(202,243,0,0.25)]">
              <span className="material-symbols-outlined text-[30px]">calendar_month</span>
            </div>
            <div>
              <h3 className="text-lg font-black text-white group-hover:text-[#caf300] transition-colors">
                Generar Plan de Temporada
              </h3>
              <p className="text-xs text-[#c5c9ac] mt-1 font-medium">
                Estructura tu macrociclo completo hasta Ironman 70.3 Marbella.
              </p>
            </div>
          </div>
          <span className="material-symbols-outlined text-[#caf300] group-hover:translate-x-1 transition-transform text-2xl">
            arrow_forward
          </span>
        </div>

        {/* Generar Semana */}
        <div
          onClick={onGenerateWeekPlan}
          className="glass-card rounded-3xl p-6 sm:p-7 flex items-center justify-between cursor-pointer hover:border-[#a5eeff] hover:scale-[1.01] transition-all group shadow-2xl border border-white/10"
        >
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-[#a5eeff]/15 border border-[#a5eeff]/50 flex items-center justify-center text-[#a5eeff] group-hover:scale-110 transition-transform shadow-[0_0_15px_rgba(165,238,255,0.25)]">
              <span className="material-symbols-outlined text-[30px]">auto_fix_high</span>
            </div>
            <div>
              <h3 className="text-lg font-black text-white group-hover:text-[#a5eeff] transition-colors">
                Generar Semana
              </h3>
              <p className="text-xs text-[#c5c9ac] mt-1 font-medium">
                Calcula microciclo de 7 días basado en tu fatiga y VFC actual.
              </p>
            </div>
          </div>
          <span className="material-symbols-outlined text-[#a5eeff] group-hover:translate-x-1 transition-transform text-2xl">
            arrow_forward
          </span>
        </div>
      </section>

      {/* Week Header & Overview Banner */}
      <section className="glass-card rounded-3xl p-6 sm:p-7 border border-white/10 border-l-4 border-l-[#caf300] shadow-2xl">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div>
            <div className="flex items-center gap-2.5">
              <span className="px-3 py-1 bg-[#caf300] text-[#121700] font-black rounded-lg text-[10px] uppercase tracking-wider shadow-[0_0_10px_rgba(202,243,0,0.3)]">
                Activa
              </span>
              <h3 className="text-xl sm:text-2xl font-black text-white">
                Semana 1: Bloque Base Aeróbica
              </h3>
            </div>
            <p className="text-xs font-mono-data text-[#c5c9ac] mt-1.5">
              05 JUN - 11 JUN • 7 Sesiones programadas • 10h 30m volumen total
            </p>
          </div>

          <span className="px-4 py-2 glass-pill rounded-xl text-xs font-black text-[#caf300] border border-[#caf300]/30 shadow-sm font-mono-data">
            TSS Estimado: 485
          </span>
        </div>
      </section>

      {/* 7-Day Workout Grid */}
      <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        {weeklyWorkouts.map((session) => {
          const isCurrent = session.dayLabel === 'MAR';
          const getSportIcon = (sport: string) => {
            switch (sport) {
              case 'swim': return 'pool';
              case 'bike': return 'directions_bike';
              case 'run': return 'directions_run';
              case 'gym': return 'fitness_center';
              default: return 'self_improvement';
            }
          };

          return (
            <div
              key={session.id}
              onClick={() => onOpenWorkout(session)}
              className={`glass-card rounded-3xl p-5 sm:p-6 flex flex-col justify-between cursor-pointer hover:scale-[1.02] hover:border-[#caf300] transition-all group border border-white/10 ${
                isCurrent
                  ? 'border-[#caf300] bg-[#caf300]/10 shadow-[0_0_25px_rgba(202,243,0,0.2)]'
                  : ''
              }`}
            >
              <div>
                {/* Card Top: Day & Sport Icon */}
                <div className="flex justify-between items-center mb-3">
                  <div className="flex items-center gap-2">
                    <span className="font-mono-data text-xs font-black text-white">
                      {session.dayLabel} {session.dayNumber}
                    </span>
                    <span
                      className={`text-[10px] font-black px-2.5 py-0.5 rounded-md border uppercase ${
                        session.tag === 'TECH' || session.tag === 'OPEN-WATER'
                          ? 'text-[#a5eeff] bg-[#a5eeff]/15 border-[#a5eeff]/40'
                          : session.tag === 'TEMPO'
                          ? 'text-[#ff5252] bg-[#ff5252]/15 border-[#ff5252]/40'
                          : 'text-[#caf300] bg-[#caf300]/15 border-[#caf300]/40'
                      }`}
                    >
                      {session.tag}
                    </span>
                  </div>

                  <div className="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-[#caf300] group-hover:bg-[#caf300] group-hover:text-[#121700] transition-colors">
                    <span className="material-symbols-outlined text-[20px]">
                      {getSportIcon(session.sport)}
                    </span>
                  </div>
                </div>

                {/* Workout Title */}
                <h4 className="text-base font-black text-white group-hover:text-[#caf300] transition-colors leading-snug mb-2">
                  {session.title}
                </h4>

                {/* Purpose preview */}
                <p className="text-xs text-[#c5c9ac] line-clamp-2 leading-relaxed">
                  {session.purpose}
                </p>
              </div>

              {/* Card Footer: Duration & Garmin Status */}
              <div className="mt-5 pt-3 border-t border-white/10 flex justify-between items-center text-xs font-mono-data">
                <span className="text-white font-black">{session.durationStr}</span>
                {session.garminSynced ? (
                  <span className="text-[#caf300] flex items-center gap-1 text-[11px] font-bold">
                    <span className="material-symbols-outlined text-[15px]">done_all</span>
                    <span>Sincronizado</span>
                  </span>
                ) : (
                  <span className="text-[#8f9378] flex items-center gap-1 text-[11px] font-bold">
                    <span className="material-symbols-outlined text-[15px]">watch</span>
                    <span>Pendiente</span>
                  </span>
                )}
              </div>
            </div>
          );
        })}
      </section>
    </div>
  );
};
