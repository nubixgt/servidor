import React, { useState } from 'react';
import { MacrocycleHistoryItem } from '../types';

interface HistorialViewProps {
  historyItems: MacrocycleHistoryItem[];
  onOpenMacrocycleDetail: (item: MacrocycleHistoryItem) => void;
}

export const HistorialView: React.FC<HistorialViewProps> = ({
  historyItems,
  onOpenMacrocycleDetail,
}) => {
  const [selectedItem, setSelectedItem] = useState<MacrocycleHistoryItem | null>(null);

  const handleOpen = (item: MacrocycleHistoryItem) => {
    setSelectedItem(item);
    onOpenMacrocycleDetail(item);
  };

  return (
    <div id="historial-view-container" className="space-y-7 pb-20 select-none">
      {/* Header */}
      <header className="pb-5 border-b border-white/10">
        <h2 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
          Historial de Planes
        </h2>
        <p className="text-sm text-[#c5c9ac] mt-1 font-medium">
          Registro analítico de macrociclos anteriores y balance de optimización del Agente IA.
        </p>
      </header>

      {/* History List of Macrocycle Cards */}
      <section className="space-y-4">
        {historyItems.map((item) => (
          <div
            key={item.id}
            id={`macrocycle-item-${item.id}`}
            className="glass-card rounded-3xl p-5 sm:p-6 flex flex-col md:flex-row md:items-center justify-between gap-5 hover:border-[#caf300]/50 transition-all group border border-white/10 shadow-2xl"
          >
            {/* Left side: Icon & Title */}
            <div className="flex items-center gap-4">
              <div className="w-14 h-14 rounded-2xl bg-white/10 border border-white/15 flex items-center justify-center text-[#caf300] shrink-0 group-hover:scale-105 group-hover:bg-[#caf300]/15 group-hover:border-[#caf300]/50 transition-all shadow-[0_0_15px_rgba(202,243,0,0.15)]">
                <span className="material-symbols-outlined text-[28px]">calendar_today</span>
              </div>

              <div>
                <h3 className="text-lg sm:text-xl font-black text-white group-hover:text-[#caf300] transition-colors">
                  {item.period}
                </h3>
                <div className="flex flex-wrap items-center gap-3 text-xs text-[#c5c9ac] mt-1 font-mono-data">
                  <span>{item.weeks} semanas</span>
                  <span>•</span>
                  <span>{item.sessionsCount} sesiones</span>
                  <span>•</span>
                  <span className="text-[#a5eeff] font-bold">{item.totalVolumeHours}h volumen</span>
                </div>
              </div>
            </div>

            {/* Right side: AI Investment & Open Button */}
            <div className="flex items-center justify-between md:justify-end gap-6 pt-3 md:pt-0 border-t md:border-t-0 border-white/10">
              <div className="text-left md:text-right font-mono-data">
                <p className="text-[11px] text-[#8f9378] uppercase font-black tracking-wider">
                  Inversión IA
                </p>
                <p className="text-base font-black text-[#caf300]">
                  ${item.aiInvestment.toFixed(2)}
                </p>
              </div>

              <button
                id={`btn-open-macrocycle-${item.id}`}
                onClick={() => handleOpen(item)}
                className="px-6 py-2.5 glass-pill hover:bg-[#caf300] text-[#e5e2e1] hover:text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-sm active:scale-95 hover:scale-105"
              >
                Abrir
              </button>
            </div>
          </div>
        ))}
      </section>

      {/* Retrospective Detail Modal */}
      {selectedItem && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fadeIn">
          <div className="glass-panel border border-white/15 rounded-3xl max-w-xl w-full p-6 sm:p-8 space-y-6 shadow-2xl">
            <div className="flex justify-between items-start border-b border-white/10 pb-4">
              <div>
                <span className="text-xs font-black text-[#caf300] uppercase font-mono-data">
                  {selectedItem.phase}
                </span>
                <h3 className="text-2xl font-black text-white mt-1">
                  {selectedItem.period}
                </h3>
              </div>
              <button
                onClick={() => setSelectedItem(null)}
                className="p-1.5 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10"
              >
                <span className="material-symbols-outlined text-2xl">close</span>
              </button>
            </div>

            <div className="grid grid-cols-3 gap-3 p-4 glass-card rounded-2xl border border-white/10 text-center font-mono-data">
              <div>
                <p className="text-[11px] text-[#8f9378] uppercase font-black">Semanas</p>
                <p className="text-xl font-black text-white mt-1">{selectedItem.weeks}</p>
              </div>
              <div>
                <p className="text-[11px] text-[#8f9378] uppercase font-black">Adherencia</p>
                <p className="text-xl font-black text-[#caf300] mt-1">{selectedItem.adherencePercent}%</p>
              </div>
              <div>
                <p className="text-[11px] text-[#8f9378] uppercase font-black">Inversión IA</p>
                <p className="text-xl font-black text-[#a5eeff] mt-1">${selectedItem.aiInvestment.toFixed(2)}</p>
              </div>
            </div>

            <div className="space-y-2">
              <h4 className="text-xs font-black text-[#8f9378] uppercase tracking-wider">
                Enfoque Fisiológico
              </h4>
              <p className="text-sm text-[#e5e2e1] leading-relaxed glass-card p-4 rounded-2xl border border-white/10">
                {selectedItem.focus}
              </p>
            </div>

            <div className="space-y-2">
              <h4 className="text-xs font-black text-[#8f9378] uppercase tracking-wider">
                Conclusiones del Entrenador & Telemetría
              </h4>
              <p className="text-sm text-[#e5e2e1] leading-relaxed glass-card p-4 rounded-2xl border border-white/10">
                {selectedItem.summaryNotes}
              </p>
            </div>

            <div className="flex justify-end gap-3 pt-3 border-t border-white/10">
              <button
                onClick={() => setSelectedItem(null)}
                className="px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-xs font-black uppercase tracking-wider rounded-xl transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)]"
              >
                Entendido
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
