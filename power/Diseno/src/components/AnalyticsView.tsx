import React from 'react';
import { AthleteProfile, AdherenceStats } from '../types';

interface AnalyticsViewProps {
  athlete: AthleteProfile;
  adherence: AdherenceStats;
}

export const AnalyticsView: React.FC<AnalyticsViewProps> = ({
  athlete,
  adherence,
}) => {
  return (
    <div id="analytics-view-container" className="space-y-7 pb-20 select-none">
      {/* Header */}
      <header className="pb-5 border-b border-white/10">
        <h2 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
          Laboratorio de Rendimiento & Analítica
        </h2>
        <p className="text-sm text-[#c5c9ac] mt-1 font-medium">
          Monitorización continua de carga aguda (ATL), carga crónica (CTL) y estrés fisiológico (TSB).
        </p>
      </header>

      {/* Top 4 Metric KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div className="glass-card rounded-3xl p-6 border border-white/10 border-l-4 border-l-[#caf300] shadow-2xl">
          <p className="text-xs font-black text-[#8f9378] uppercase tracking-wider font-mono-data">
            Fitness (CTL)
          </p>
          <p className="text-4xl font-black text-white font-mono-data mt-2">78.4</p>
          <p className="text-xs text-[#caf300] mt-2 flex items-center gap-1 font-mono-data font-bold">
            <span className="material-symbols-outlined text-[16px]">trending_up</span>
            <span>+4.2 en 14 días</span>
          </p>
        </div>

        <div className="glass-card rounded-3xl p-6 border border-white/10 border-l-4 border-l-[#a5eeff] shadow-2xl">
          <p className="text-xs font-black text-[#8f9378] uppercase tracking-wider font-mono-data">
            Fatiga (ATL)
          </p>
          <p className="text-4xl font-black text-[#a5eeff] font-mono-data mt-2">86.1</p>
          <p className="text-xs text-[#c5c9ac] mt-2 font-mono-data font-medium">Carga aguda asimilable</p>
        </div>

        <div className="glass-card rounded-3xl p-6 border border-white/10 border-l-4 border-l-[#ffb4ab] shadow-2xl">
          <p className="text-xs font-black text-[#8f9378] uppercase tracking-wider font-mono-data">
            Forma (TSB)
          </p>
          <p className="text-4xl font-black text-[#caf300] font-mono-data mt-2">-7.7</p>
          <p className="text-xs text-[#caf300] mt-2 font-mono-data font-bold">Zona Óptima de Carga</p>
        </div>

        <div className="glass-card rounded-3xl p-6 border border-white/10 border-l-4 border-l-[#8f9378] shadow-2xl">
          <p className="text-xs font-black text-[#8f9378] uppercase tracking-wider font-mono-data">
            Vo2Max / Eficiencia
          </p>
          <p className="text-4xl font-black text-white font-mono-data mt-2">{athlete.vo2Max}</p>
          <p className="text-xs text-[#c5c9ac] mt-2 font-mono-data font-medium">ml/kg/min (Percentil 99)</p>
        </div>
      </div>

      {/* Charts Bento */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Weekly Volume Sport Distribution (7 cols) */}
        <div className="lg:col-span-7 glass-card rounded-3xl p-6 sm:p-7 space-y-5 border border-white/10 shadow-2xl">
          <h3 className="text-lg font-black text-white flex items-center justify-between">
            <span>Distribución de Volumen Semanal (18.5h)</span>
            <span className="text-xs font-mono-data text-[#caf300] font-black">Semana 1 / Fase 2</span>
          </h3>

          <div className="space-y-5">
            {/* Ciclismo */}
            <div>
              <div className="flex justify-between text-xs font-mono-data mb-2">
                <span className="text-white flex items-center gap-2 font-bold">
                  <span className="material-symbols-outlined text-[18px] text-[#caf300]">directions_bike</span>
                  <span>Ciclismo (9.5h • 51%)</span>
                </span>
                <span className="text-[#caf300] font-black">295 TSS</span>
              </div>
              <div className="h-3 bg-white/10 rounded-full overflow-hidden p-0.5 border border-white/10">
                <div className="h-full bg-[#caf300] rounded-full shadow-[0_0_12px_rgba(202,243,0,0.5)]" style={{ width: '51%' }}></div>
              </div>
            </div>

            {/* Carrera */}
            <div>
              <div className="flex justify-between text-xs font-mono-data mb-2">
                <span className="text-white flex items-center gap-2 font-bold">
                  <span className="material-symbols-outlined text-[18px] text-[#a5eeff]">directions_run</span>
                  <span>Carrera a pie (5.5h • 30%)</span>
                </span>
                <span className="text-[#a5eeff] font-black">203 TSS</span>
              </div>
              <div className="h-3 bg-white/10 rounded-full overflow-hidden p-0.5 border border-white/10">
                <div className="h-full bg-[#a5eeff] rounded-full shadow-[0_0_12px_rgba(165,238,255,0.5)]" style={{ width: '30%' }}></div>
              </div>
            </div>

            {/* Natación */}
            <div>
              <div className="flex justify-between text-xs font-mono-data mb-2">
                <span className="text-white flex items-center gap-2 font-bold">
                  <span className="material-symbols-outlined text-[18px] text-white">pool</span>
                  <span>Natación (3.5h • 19%)</span>
                </span>
                <span className="text-white font-black">115 TSS</span>
              </div>
              <div className="h-3 bg-white/10 rounded-full overflow-hidden p-0.5 border border-white/10">
                <div className="h-full bg-white rounded-full shadow-[0_0_12px_rgba(255,255,255,0.5)]" style={{ width: '19%' }}></div>
              </div>
            </div>
          </div>

          <div className="p-4.5 glass-panel rounded-2xl border border-white/10 text-xs text-[#c5c9ac] leading-relaxed">
            💡 <strong className="text-white">Diagnóstico del Algoritmo</strong>: Ratio de carga aeróbica / neuromuscular altamente equilibrado. El 80% del tiempo acumulado transcurre en Zona 2 (polarización estricta).
          </div>
        </div>

        {/* Zona Cardíaca & VFC Status (5 cols) */}
        <div className="lg:col-span-5 glass-card rounded-3xl p-6 sm:p-7 space-y-5 border border-white/10 shadow-2xl">
          <h3 className="text-lg font-black text-white flex items-center gap-2">
            <span className="material-symbols-outlined text-[#caf300]">ecg_heart</span>
            <span>Variabilidad Cardíaca (VFC)</span>
          </h3>

          <div className="p-5 glass-card rounded-2xl border border-white/10 space-y-3.5 font-mono-data">
            <div className="flex justify-between items-center text-sm">
              <span className="text-[#8f9378] font-bold">VFC Nocturna:</span>
              <span className="text-[#caf300] font-black">68 ms (Línea Base: 64-72 ms)</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-[#8f9378] font-bold">Estado Autonómico:</span>
              <span className="text-[#a5eeff] font-black">Óptimo / Asimilación</span>
            </div>
            <div className="flex justify-between items-center text-sm">
              <span className="text-[#8f9378] font-bold">FC Reposo Media:</span>
              <span className="text-white font-black">{athlete.restingHeartRate} bpm</span>
            </div>
          </div>

          <div className="p-4 bg-[#caf300]/15 border border-[#caf300]/40 rounded-2xl text-xs text-[#caf300] font-medium leading-relaxed shadow-[0_0_15px_rgba(202,243,0,0.15)]">
            ⚡ El sistema nervioso simpático no muestra signos de sobrentrenamiento. Autorizada la sesión de tempo planificada para el Jueves.
          </div>
        </div>
      </div>
    </div>
  );
};
