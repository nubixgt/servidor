import React, { useState } from 'react';
import { WorkoutSession } from '../types';

interface WorkoutDetailModalProps {
  workout: WorkoutSession | null;
  onClose: () => void;
  onSendToGarmin: (workout: WorkoutSession) => void;
  onToggleStatus: (workoutId: string) => void;
}

export const WorkoutDetailModal: React.FC<WorkoutDetailModalProps> = ({
  workout,
  onClose,
  onSendToGarmin,
  onToggleStatus,
}) => {
  const [syncing, setSyncing] = useState(false);
  const [syncedSuccess, setSyncedSuccess] = useState(false);

  if (!workout) return null;

  const handleGarminClick = () => {
    setSyncing(true);
    setTimeout(() => {
      setSyncing(false);
      setSyncedSuccess(true);
      onSendToGarmin(workout);
      setTimeout(() => setSyncedSuccess(false), 3000);
    }, 900);
  };

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
      id="workout-detail-backdrop"
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fadeIn"
    >
      <div className="glass-panel border border-white/15 rounded-3xl max-w-2xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex justify-between items-start border-b border-white/10 pb-4">
          <div className="flex items-center gap-4">
            <div className="w-14 h-14 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.2)]">
              <span className="material-symbols-outlined text-[30px]">
                {getSportIcon(workout.sport)}
              </span>
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="font-mono-data text-xs font-black text-[#caf300] uppercase">
                  {workout.dayLabel} {workout.dayNumber} • {workout.tag}
                </span>
                {workout.timeOfDay && (
                  <span className="text-[11px] font-mono-data text-[#8f9378]">
                    • {workout.timeOfDay}
                  </span>
                )}
              </div>
              <h3 className="text-2xl font-black text-white tracking-tight mt-0.5">
                {workout.title}
              </h3>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 transition-colors"
          >
            <span className="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>

        {/* Quick Metrics Strip */}
        <div className="grid grid-cols-3 gap-3 p-4 glass-card rounded-2xl border border-white/10 text-center font-mono-data">
          <div>
            <p className="text-[10px] text-[#8f9378] uppercase font-black tracking-wider">Duración</p>
            <p className="text-lg font-black text-white mt-0.5">{workout.durationStr}</p>
          </div>
          <div>
            <p className="text-[10px] text-[#8f9378] uppercase font-black tracking-wider">Zona Objetivo</p>
            <p className="text-lg font-black text-[#caf300] mt-0.5">{workout.targetZone || 'Z2 Base'}</p>
          </div>
          <div>
            <p className="text-[10px] text-[#8f9378] uppercase font-black tracking-wider">TSS Estimado</p>
            <p className="text-lg font-black text-[#a5eeff] mt-0.5">{workout.tss || 65}</p>
          </div>
        </div>

        {/* Pasos / Interval Breakdown */}
        <div className="space-y-3">
          <h4 className="text-xs font-black text-[#8f9378] uppercase tracking-wider flex items-center gap-2">
            <span className="material-symbols-outlined text-base text-[#caf300]">format_list_numbered</span>
            <span>Estructura de la Sesión</span>
          </h4>

          <div className="space-y-2.5">
            {workout.steps.map((step, idx) => (
              <div
                key={idx}
                className="flex items-center gap-3 p-3.5 glass-card rounded-2xl border border-white/10 text-sm text-[#e5e2e1] font-medium"
              >
                <span className="w-6 h-6 rounded-full bg-white/10 text-[#caf300] font-mono-data text-xs font-black flex items-center justify-center shrink-0">
                  {idx + 1}
                </span>
                <span className="leading-snug">{step}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Propósito Fisiológico */}
        <div className="space-y-2 bg-[#caf300]/10 border border-[#caf300]/30 rounded-2xl p-4.5">
          <h4 className="text-xs font-black text-[#caf300] uppercase tracking-wider flex items-center gap-1.5">
            <span className="material-symbols-outlined text-base">science</span>
            <span>Propósito Fisiológico</span>
          </h4>
          <p className="text-sm text-[#e5e2e1] leading-relaxed font-medium">
            {workout.purpose}
          </p>
        </div>

        {/* Garmin Sync Alert */}
        {syncedSuccess && (
          <div className="p-3.5 bg-[#caf300]/25 border border-[#caf300] text-[#caf300] rounded-2xl text-xs font-bold flex items-center gap-2 shadow-[0_0_15px_rgba(202,243,0,0.25)]">
            <span className="material-symbols-outlined text-base">check_circle</span>
            <span>¡Entrenamiento estructurado enviado con éxito a tu reloj Garmin!</span>
          </div>
        )}

        {/* Footer Actions */}
        <div className="flex flex-col sm:flex-row justify-between items-center gap-3 pt-4 border-t border-white/10">
          <button
            onClick={() => onToggleStatus(workout.id)}
            className="w-full sm:w-auto px-5 py-2.5 glass-pill hover:bg-white/15 text-white text-xs font-black uppercase tracking-wider rounded-xl transition-all flex items-center justify-center gap-2"
          >
            <span className="material-symbols-outlined text-base">
              {workout.status === 'completed' ? 'check_box' : 'check_box_outline_blank'}
            </span>
            <span>
              {workout.status === 'completed' ? 'Completado' : 'Marcar como Hecho'}
            </span>
          </button>

          <button
            id="btn-enviar-garmin-modal"
            onClick={handleGarminClick}
            disabled={syncing}
            className="w-full sm:w-auto px-7 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)] active:scale-95 hover:scale-105 flex items-center justify-center gap-2"
          >
            <span className="material-symbols-outlined text-base">
              {syncing ? 'sync' : 'watch'}
            </span>
            <span>
              {syncing ? 'Transmitiendo...' : 'Enviar a Garmin'}
            </span>
          </button>
        </div>
      </div>
    </div>
  );
};
