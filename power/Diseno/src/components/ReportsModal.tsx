import React from 'react';
import { AthleteProfile, CurrentMacrocycle } from '../types';

interface ReportsModalProps {
  isOpen: boolean;
  onClose: () => void;
  athlete: AthleteProfile;
  macrocycle: CurrentMacrocycle;
}

export const ReportsModal: React.FC<ReportsModalProps> = ({
  isOpen,
  onClose,
  athlete,
  macrocycle,
}) => {
  if (!isOpen) return null;

  const handlePrint = () => {
    window.print();
  };

  return (
    <div
      id="reports-modal-backdrop"
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fadeIn select-none"
    >
      <div className="glass-panel border border-white/15 rounded-3xl max-w-3xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative max-h-[90vh] overflow-y-auto">
        {/* Modal Header */}
        <div className="flex justify-between items-start border-b border-white/10 pb-4">
          <div>
            <div className="flex items-center gap-2">
              <span className="px-2.5 py-0.5 bg-[#caf300] text-[#121700] font-mono-data text-[10px] font-black rounded-full uppercase">
                Informe Clínico
              </span>
              <span className="text-xs font-mono-data text-[#8f9378] font-bold">
                ID: LAB-703-2024-X9
              </span>
            </div>
            <h3 className="text-2xl font-black text-white mt-1">
              Informe de Laboratorio & Macrociclo
            </h3>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 transition-colors"
          >
            <span className="material-symbols-outlined text-2xl">close</span>
          </button>
        </div>

        {/* Report Content Document */}
        <div className="glass-card border border-white/10 rounded-3xl p-6 sm:p-7 space-y-6 font-sans">
          {/* Athlete banner in report */}
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-4 border-b border-white/10">
            <div>
              <h4 className="text-lg font-black text-white">{athlete.name} Triathlete</h4>
              <p className="text-xs text-[#caf300] font-mono-data uppercase font-bold">
                {athlete.primarySport} • Objetivo: {athlete.nextTargetRace.name} ({athlete.nextTargetRace.daysRemaining} días)
              </p>
            </div>
            <div className="text-left sm:text-right font-mono-data text-xs text-[#c5c9ac]">
              <p>Fecha: {new Date().toLocaleDateString('es-ES')}</p>
              <p className="text-[#caf300] font-black">Estado: Apto / Competición Élite</p>
            </div>
          </div>

          {/* Biological Markers Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3.5 font-mono-data text-xs">
            <div className="p-3.5 glass-panel rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">FTP Ciclista</span>
              <span className="text-base font-black text-[#caf300]">{athlete.ftpWatts} W</span>
            </div>
            <div className="p-3.5 glass-panel rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">Vo2Max</span>
              <span className="text-base font-black text-[#a5eeff]">{athlete.vo2Max} ml/kg</span>
            </div>
            <div className="p-3.5 glass-panel rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">FC Reposo</span>
              <span className="text-base font-black text-white">{athlete.restingHeartRate} bpm</span>
            </div>
            <div className="p-3.5 glass-panel rounded-2xl border border-white/10">
              <span className="text-[#8f9378] block text-[10px] uppercase font-black">VFC Baseline</span>
              <span className="text-base font-black text-[#caf300]">{athlete.hrv} ms</span>
            </div>
          </div>

          {/* Prescription Evaluation */}
          <div className="space-y-2">
            <h5 className="text-xs font-black text-[#8f9378] uppercase tracking-wider">
              Diagnóstico Fisiológico & Prescripción AI
            </h5>
            <p className="text-sm text-[#e5e2e1] leading-relaxed glass-panel p-4.5 rounded-2xl border border-white/10 font-medium">
              El atleta se encuentra en el <strong className="text-white">{macrocycle.phase}</strong> con una adherencia del 85%. Los biomarcadores de variabilidad de frecuencia cardíaca (VFC) confirman una óptima asimilación mitocondrial en Zona 2 sin sobrecarga simpática. Se recomienda mantener el incremento de potencia sub-umbral en ciclismo y vigilancia pasiva del tendón de Aquiles derecho en carrera a pie.
            </p>
          </div>
        </div>

        {/* Modal Actions */}
        <div className="flex justify-end gap-3 pt-3 border-t border-white/10">
          <button
            onClick={onClose}
            className="px-5 py-2.5 glass-pill hover:bg-white/15 text-[#c5c9ac] hover:text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all"
          >
            Cerrar
          </button>
          <button
            id="btn-print-report"
            onClick={handlePrint}
            className="flex items-center gap-2 px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(202,243,0,0.3)] active:scale-95 hover:scale-105"
          >
            <span className="material-symbols-outlined text-[18px]">download</span>
            <span>Descargar / Imprimir PDF</span>
          </button>
        </div>
      </div>
    </div>
  );
};
