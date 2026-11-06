import React from 'react';
import { AthleteProfile, WorkoutSession, AdherenceStats, CurrentMacrocycle } from '../types';

interface DashboardViewProps {
  athlete: AthleteProfile;
  adherence: AdherenceStats;
  macrocycle: CurrentMacrocycle;
  weeklyWorkouts: WorkoutSession[];
  onOpenWorkout: (workout: WorkoutSession) => void;
  onOpenGeneratePlan: () => void;
  onOpenPdfModal: () => void;
  onOpenAnalytics: () => void;
  onOpenHealth: () => void;
  onOpenMacrocycleDetails: () => void;
  onSendToGarmin: (workout: WorkoutSession) => void;
  onOpenAiCoach: () => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({
  athlete,
  adherence,
  macrocycle,
  weeklyWorkouts,
  onOpenWorkout,
  onOpenGeneratePlan,
  onOpenPdfModal,
  onOpenAnalytics,
  onOpenHealth,
  onOpenMacrocycleDetails,
  onSendToGarmin,
  onOpenAiCoach,
}) => {
  // Find current session (or fallback to Tuesday/cycling)
  const currentWorkout = weeklyWorkouts.find((w) => w.dayLabel === 'MAR') || weeklyWorkouts[1];

  // Calculate SVG stroke offset for the 85% adherence donut
  const radius = 42;
  const circumference = 2 * Math.PI * radius; // ~263.89
  const strokeDashoffset = circumference - (adherence.percentage / 100) * circumference;

  return (
    <div id="dashboard-canvas" className="space-y-7 pb-20 select-none">
      {/* Welcome & Quick Actions Header */}
      <section className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-5">
        <div>
          <h2 className="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight">
            ¡Bienvenido, <span className="text-[#caf300]">{athlete.name}!</span>
          </h2>
          <p className="text-[#c5c9ac] text-sm sm:text-base mt-1.5 font-normal">
            Tu mejor versión comienza hoy. Tus AI Coaches están listos.
          </p>
        </div>

        {/* Quick Action Buttons (Matching Screenshot) */}
        <div className="flex flex-wrap items-center gap-2.5 sm:gap-3">
          <button
            id="btn-generar-plan-nuevo"
            onClick={onOpenGeneratePlan}
            className="flex items-center gap-2 px-5 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)] hover:scale-[1.02] active:scale-95"
          >
            <span className="material-symbols-outlined text-[18px]">add</span>
            <span>Generar plan nuevo</span>
          </button>

          <button
            id="btn-plan-pdf"
            onClick={onOpenPdfModal}
            className="flex items-center gap-2 px-4 py-3 glass-card hover:bg-white/10 text-[#f1f0ee] hover:text-[#caf300] rounded-xl text-xs font-extrabold uppercase tracking-wider transition-all border border-white/10"
          >
            <span className="material-symbols-outlined text-[18px]">picture_as_pdf</span>
            <span>Plan PDF</span>
          </button>

          <button
            id="btn-analisis"
            onClick={onOpenAnalytics}
            className="flex items-center gap-2 px-4 py-3 glass-card hover:bg-white/10 text-[#f1f0ee] hover:text-[#caf300] rounded-xl text-xs font-extrabold uppercase tracking-wider transition-all border border-white/10"
          >
            <span className="material-symbols-outlined text-[18px]">analytics</span>
            <span>Análisis</span>
          </button>

          <button
            id="btn-salud"
            onClick={onOpenHealth}
            className="flex items-center gap-2 px-4 py-3 glass-card hover:bg-white/10 text-[#f1f0ee] hover:text-[#caf300] rounded-xl text-xs font-extrabold uppercase tracking-wider transition-all border border-white/10"
          >
            <span className="material-symbols-outlined text-[18px]">favorite</span>
            <span>Salud</span>
          </button>
        </div>
      </section>

      {/* Week Days Selector Strip (LUN, MAR, MIE, JUE, VIE, SAB, DOM) */}
      <section className="grid grid-cols-7 gap-2.5 sm:gap-4 overflow-x-auto hide-scrollbar py-1">
        {weeklyWorkouts.map((workout) => {
          const isToday = workout.dayLabel === 'MAR';
          const getSportIcon = (sport: string) => {
            switch (sport) {
              case 'swim': return 'pool';
              case 'bike': return 'directions_bike';
              case 'run': return 'directions_run';
              case 'gym': return 'fitness_center';
              default: return 'self_improvement';
            }
          };

          const getIconColor = (sport: string, today: boolean) => {
            if (today) return 'text-[#caf300]';
            if (sport === 'run') return 'text-[#a5eeff]';
            if (sport === 'bike') return 'text-[#caf300]';
            if (sport === 'swim') return 'text-[#a5eeff]';
            return 'text-[#c5c9ac]';
          };

          return (
            <div
              key={workout.id}
              onClick={() => onOpenWorkout(workout)}
              className={`flex flex-col items-center justify-center py-4 px-2 sm:py-5 rounded-2xl transition-all cursor-pointer select-none relative ${
                isToday
                  ? 'glass-card border-2 border-[#caf300] shadow-[0_0_25px_rgba(202,243,0,0.35)] scale-[1.03] bg-[#1a2210]/60'
                  : 'glass-card hover:border-[#caf300]/40 hover:bg-white/5'
              }`}
            >
              {isToday && (
                <div className="absolute top-2 right-2 w-2 h-2 bg-[#caf300] rounded-full shadow-[0_0_8px_#caf300]"></div>
              )}
              <span
                className={`text-xs font-black tracking-widest mb-2 ${
                  isToday ? 'text-[#caf300]' : 'text-[#c5c9ac]'
                }`}
              >
                {workout.dayLabel}
              </span>
              <span
                className={`material-symbols-outlined text-[26px] ${getIconColor(
                  workout.sport,
                  isToday
                )}`}
              >
                {getSportIcon(workout.sport)}
              </span>
            </div>
          );
        })}
      </section>

      {/* Bento Grid Layout */}
      <section className="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {/* Próxima sesión (Wide Card - 8 Cols) */}
        <article
          id="card-proxima-sesion"
          className="lg:col-span-8 glass-card rounded-3xl p-6 sm:p-8 flex flex-col relative overflow-hidden group shadow-2xl hover:border-[#caf300]/30 transition-all border border-white/10"
        >
          {/* Ambient Glow */}
          <div className="absolute top-0 right-0 w-80 h-80 bg-[#caf300]/10 blur-[100px] -mr-20 -mt-20 rounded-full pointer-events-none"></div>

          <header className="flex justify-between items-start mb-6 z-10">
            <div className="flex items-center gap-3.5">
              <div className="w-12 h-12 rounded-2xl bg-[#1d2214]/80 flex items-center justify-center text-[#caf300] border border-[#caf300]/40 shadow-[0_0_15px_rgba(202,243,0,0.2)]">
                <span className="material-symbols-outlined text-[28px]">directions_bike</span>
              </div>
              <div>
                <h3 className="text-lg sm:text-xl font-black text-white tracking-tight">Próxima sesión</h3>
                <p className="text-[11px] text-[#8f9378] font-bold uppercase tracking-widest mt-0.5">
                  HOY, 18:00H
                </p>
              </div>
            </div>
            <span className="px-3.5 py-1.5 bg-[#17191e]/90 rounded-xl font-mono-data text-xs font-extrabold text-[#caf300] border border-[#caf300]/30 shadow-inner">
              60 MIN
            </span>
          </header>

          <div className="z-10 mt-auto">
            <h4 className="text-2xl sm:text-3xl font-black text-[#caf300] tracking-tight mb-5">
              Ciclismo - Rodaje Z2
            </h4>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-5 border-t border-white/10">
              {/* Pasos */}
              <div className="space-y-2.5">
                <p className="text-[11px] font-black text-[#8f9378] tracking-widest uppercase">
                  PASOS
                </p>
                <ul className="text-xs sm:text-sm text-[#f1f0ee] space-y-2 font-medium">
                  <li className="flex items-center gap-2.5">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#8f9378]"></span>
                    <span>Calentamiento 15' (Z1)</span>
                  </li>
                  <li className="flex items-center gap-2.5 text-white font-bold">
                    <span className="w-2 h-2 rounded-full bg-[#caf300] shadow-[0_0_8px_#caf300]"></span>
                    <span>Bloque Principal 30' (Z2)</span>
                  </li>
                  <li className="flex items-center gap-2.5">
                    <span className="w-1.5 h-1.5 rounded-full bg-[#8f9378]"></span>
                    <span>Enfriamiento 15' (Z1)</span>
                  </li>
                </ul>
              </div>

              {/* Propósito */}
              <div className="space-y-2 sm:border-l sm:border-white/10 sm:pl-6">
                <p className="text-[11px] font-black text-[#8f9378] tracking-widest uppercase">
                  PROPÓSITO
                </p>
                <p className="text-xs sm:text-sm text-[#c5c9ac] leading-relaxed">
                  Desarrollo aeróbico base. Mantener cadencia alta (90+ rpm) y pulso estrictamente bajo el umbral aeróbico.
                </p>
                <button
                  id="btn-enviar-garmin-dashboard"
                  onClick={() => onSendToGarmin(currentWorkout)}
                  className="mt-3 text-[#caf300] hover:text-[#bbf400] text-xs sm:text-sm font-bold inline-flex items-center gap-1.5 group-hover:translate-x-1 transition-transform"
                >
                  <span>Enviar a Garmin</span>
                  <span className="material-symbols-outlined text-[18px]">watch</span>
                </button>
              </div>
            </div>
          </div>
        </article>

        {/* Adherencia (Square Card - 4 Cols) */}
        <article
          id="card-adherencia"
          className="lg:col-span-4 glass-card rounded-3xl p-6 sm:p-7 flex flex-col items-center justify-between text-center shadow-2xl hover:border-[#caf300]/30 transition-all border border-white/10"
        >
          <h3 className="text-lg font-bold text-white w-full text-left mb-2">Adherencia</h3>

          {/* Radial Donut Progress with Neon Glow */}
          <div className="relative w-44 h-44 flex items-center justify-center my-3">
            <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
              {/* Background track */}
              <circle
                cx="50"
                cy="50"
                r={radius}
                fill="transparent"
                stroke="rgba(255, 255, 255, 0.08)"
                strokeWidth="7"
              />
              {/* Progress stroke */}
              <circle
                cx="50"
                cy="50"
                r={radius}
                fill="transparent"
                stroke="#caf300"
                strokeWidth="7"
                strokeDasharray={circumference}
                strokeDashoffset={strokeDashoffset}
                strokeLinecap="round"
                className="drop-shadow-[0_0_12px_rgba(202,243,0,0.8)] transition-all duration-1000 ease-out"
              />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center">
              <span className="font-mono-data text-4xl sm:text-5xl font-black text-white leading-none tracking-tight">
                {adherence.percentage}%
              </span>
              <span className="text-[11px] font-black text-[#caf300] tracking-widest uppercase mt-1">
                {adherence.statusLabel}
              </span>
            </div>
          </div>

          {/* Bottom Breakdown stats */}
          <div className="flex justify-between w-full pt-4 border-t border-white/10 px-2">
            <div className="flex flex-col items-center">
              <span className="text-white font-mono-data text-lg font-black">
                {adherence.completedCount}
              </span>
              <span className="text-[#8f9378] text-[10px] uppercase font-bold tracking-widest mt-0.5">
                HECHAS
              </span>
            </div>
            <div className="w-px h-8 bg-white/10"></div>
            <div className="flex flex-col items-center">
              <span className="text-[#a5eeff] font-mono-data text-lg font-black">
                {adherence.modifiedCount}
              </span>
              <span className="text-[#8f9378] text-[10px] uppercase font-bold tracking-widest mt-0.5">
                MOD.
              </span>
            </div>
            <div className="w-px h-8 bg-white/10"></div>
            <div className="flex flex-col items-center">
              <span className="text-[#ffb4ab] font-mono-data text-lg font-black">
                {adherence.missedCount}
              </span>
              <span className="text-[#8f9378] text-[10px] uppercase font-bold tracking-widest mt-0.5">
                FALLO
              </span>
            </div>
          </div>
        </article>

        {/* Próximo Objetivo A (Col-span 6) */}
        <article
          id="card-proximo-objetivo"
          className="col-span-1 lg:col-span-6 glass-card rounded-3xl p-6 sm:p-8 flex flex-col justify-between group cursor-pointer hover:border-[#caf300]/40 transition-all relative overflow-hidden shadow-2xl border border-white/10"
          onClick={onOpenMacrocycleDetails}
        >
          <div className="flex justify-between items-start">
            <div>
              <p className="text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-1.5">
                PRÓXIMO OBJETIVO A
              </p>
              <h3 className="text-2xl sm:text-3xl font-black text-white tracking-tight">
                {athlete.nextTargetRace.name}
              </h3>
              <p className="text-lg sm:text-xl font-bold text-[#caf300] mt-0.5">
                {athlete.nextTargetRace.location}
              </p>
            </div>
            <div className="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-[#8f9378] group-hover:text-[#caf300] transition-colors">
              <span className="material-symbols-outlined text-[22px]">emoji_events</span>
            </div>
          </div>

          <div className="mt-8">
            <div className="flex items-end gap-3 mb-3">
              <span className="font-mono-data text-4xl sm:text-5xl font-black text-white leading-none tracking-tighter">
                {athlete.nextTargetRace.daysRemaining}
              </span>
              <span className="text-[11px] font-black text-[#8f9378] pb-1 uppercase tracking-widest">
                DÍAS RESTANTES
              </span>
            </div>

            {/* Race progress bar with glowing lime */}
            <div className="w-full h-2.5 bg-white/10 rounded-full overflow-hidden p-0.5">
              <div
                className="h-full bg-[#caf300] rounded-full shadow-[0_0_12px_#caf300] transition-all duration-1000"
                style={{ width: `${athlete.nextTargetRace.progressPercent}%` }}
              ></div>
            </div>
          </div>
        </article>

        {/* Macrociclo Actual Resumen (Col-span 6) */}
        <article
          id="card-macrociclo-resumen"
          className="col-span-1 lg:col-span-6 glass-card rounded-3xl p-6 sm:p-8 flex flex-col justify-between shadow-2xl hover:border-[#caf300]/40 transition-all border border-white/10"
        >
          <header className="flex justify-between items-center mb-6">
            <p className="text-[11px] font-black text-[#8f9378] uppercase tracking-widest">
              MACROCICLO ACTUAL
            </p>
            <span className="px-3 py-1 bg-white/5 border border-white/10 rounded-lg font-mono-data text-[10px] font-bold text-white uppercase tracking-wider">
              {macrocycle.phase}
            </span>
          </header>

          <div className="grid grid-cols-3 gap-3 my-auto">
            <div className="flex flex-col gap-1 border-r border-white/10 pr-2">
              <span className="font-mono-data text-3xl sm:text-4xl font-black text-white">
                {macrocycle.weeksCount}
              </span>
              <span className="text-[10px] font-black text-[#8f9378] tracking-widest uppercase">
                SEMANAS
              </span>
            </div>

            <div className="flex flex-col gap-1 border-r border-white/10 px-2">
              <span className="font-mono-data text-3xl sm:text-4xl font-black text-white">
                {macrocycle.sessionsCount}
              </span>
              <span className="text-[10px] font-black text-[#8f9378] tracking-widest uppercase">
                SESIONES
              </span>
            </div>

            <div className="flex flex-col gap-1 pl-2">
              <span className="font-mono-data text-3xl sm:text-4xl font-black text-[#caf300]">
                {macrocycle.volumeHours}h
              </span>
              <span className="text-[10px] font-black text-[#8f9378] tracking-widest uppercase">
                VOLUMEN
              </span>
            </div>
          </div>

          <button
            id="btn-ver-detalles-plan"
            onClick={onOpenMacrocycleDetails}
            className="mt-6 w-full py-3 glass-pill hover:bg-white/10 rounded-xl text-xs font-black tracking-wider uppercase text-[#f1f0ee] hover:text-[#caf300] transition-all"
          >
            VER DETALLES DEL PLAN
          </button>
        </article>
      </section>

      {/* Floating Action Button for AI Coach (Matching Reference) */}
      <button
        id="fab-ai-coach"
        onClick={onOpenAiCoach}
        className="fixed bottom-8 right-8 z-50 flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-full shadow-[0_0_30px_rgba(202,243,0,0.5)] hover:scale-110 active:scale-95 transition-all group"
        title="Consultar al Agente IA"
      >
        <span className="material-symbols-outlined text-[28px] sm:text-[32px] group-hover:rotate-12 transition-transform">
          smart_toy
        </span>
        <span className="absolute top-0 right-0 flex h-4 w-4">
          <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-80"></span>
          <span className="relative inline-flex rounded-full h-3.5 w-3.5 bg-white border-2 border-[#caf300]"></span>
        </span>
      </button>
    </div>
  );
};
