import React from 'react';
import { AthleteProfile } from '../types';

interface HealthModalProps {
  isOpen: boolean;
  onClose: () => void;
  athlete: AthleteProfile;
  onSync: () => void;
  isSyncing?: boolean;
}

export const HealthModal: React.FC<HealthModalProps> = ({
  isOpen,
  onClose,
  athlete,
  onSync,
  isSyncing = false,
}) => {
  if (!isOpen) return null;

  return (
    <div
      id="health-modal-backdrop"
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fadeIn select-none"
    >
      <div className="glass-panel border border-white/15 rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative">
        <div className="flex justify-between items-start border-b border-white/10 pb-4">
          <div className="flex items-center gap-3.5">
            <div className="w-12 h-12 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.2)]">
              <span className="material-symbols-outlined text-[26px]">favorite</span>
            </div>
            <div>
              <h3 className="text-xl font-black text-white">
                Telemetría & Salud
              </h3>
              <p className="text-xs text-[#c5c9ac] font-medium">
                Sincronización de biometría en tiempo real
              </p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 transition-colors"
          >
            <span className="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>

        <div className="space-y-4 font-mono-data text-xs">
          <div className="p-4.5 glass-card rounded-2xl border border-white/10 flex justify-between items-center">
            <div>
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">Estado VFC (HRV)</span>
              <span className="text-lg font-black text-[#caf300]">{athlete.hrv} ms • Equilibrado</span>
            </div>
            <span className="material-symbols-outlined text-[#caf300] text-3xl">ecg_heart</span>
          </div>

          <div className="grid grid-cols-2 gap-3.5">
            <div className="p-4 glass-card rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">FC Reposo</span>
              <span className="text-base font-black text-white mt-1">{athlete.restingHeartRate} bpm</span>
            </div>
            <div className="p-4 glass-card rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">FC Máxima</span>
              <span className="text-base font-black text-white mt-1">{athlete.maxHeartRate} bpm</span>
            </div>
            <div className="p-4 glass-card rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">SpO2 Sueño</span>
              <span className="text-base font-black text-[#a5eeff] mt-1">97.8%</span>
            </div>
            <div className="p-4 glass-card rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">Horas Sueño Profundo</span>
              <span className="text-base font-black text-[#a5eeff] mt-1">1h 48m (8h 12m tot)</span>
            </div>
          </div>
        </div>

        <div className="pt-3 flex justify-between items-center border-t border-white/10">
          <span className="text-[11px] font-mono-data text-[#8f9378]">
            Garmin Connect API v3.4 Conectado
          </span>
          <button
            onClick={onSync}
            disabled={isSyncing}
            className="flex items-center gap-2 px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(202,243,0,0.3)] disabled:opacity-50 hover:scale-105 active:scale-95"
          >
            <span className="material-symbols-outlined text-[18px]">
              {isSyncing ? 'sync' : 'refresh'}
            </span>
            <span>{isSyncing ? 'Sincronizando...' : 'Actualizar Sensores'}</span>
          </button>
        </div>
      </div>
    </div>
  );
};
