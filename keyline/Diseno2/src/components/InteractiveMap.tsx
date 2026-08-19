import React, { useState } from 'react';
import { MapPin, AlertTriangle, CheckCircle, ZoomIn, ZoomOut, Layers, Eye } from 'lucide-react';
import { Parcela } from '../types';

interface InteractiveMapProps {
  parcelas: Parcela[];
  onSelectParcel: (parcel: Parcela) => void;
}

export const InteractiveMap: React.FC<InteractiveMapProps> = ({ parcelas, onSelectParcel }) => {
  const [filter, setFilter] = useState<'all' | 'active' | 'alert'>('all');
  const [selectedPin, setSelectedPin] = useState<Parcela | null>(null);
  const [zoom, setZoom] = useState<number>(1);
  const [showContours, setShowContours] = useState<boolean>(true);

  // Map coordinate conversion to container percentage relative to Guatemala bounds
  // Guatemala aprox lat 13.7 to 17.8, lon -92.2 to -88.2
  const minLat = 14.2;
  const maxLat = 16.0;
  const minLon = -92.0;
  const maxLon = -89.8;

  const getPinPosition = (lat: number, lon: number) => {
    // Relative positioning
    const xPct = ((lon - minLon) / (maxLon - minLon)) * 80 + 10;
    const yPct = 100 - (((lat - minLat) / (maxLat - minLat)) * 80 + 10);
    return {
      left: `${Math.min(Math.max(xPct, 8), 92)}%`,
      top: `${Math.min(Math.max(yPct, 15), 85)}%`
    };
  };

  const filteredParcelas = parcelas.filter(p => {
    if (filter === 'active') return p.status === 'Aprobado' || p.status === 'Validado V2';
    if (filter === 'alert') return p.erosionLevel === 'Severa' || p.status === 'En Revisión';
    return true;
  });

  return (
    <div className="glass-panel rounded-xl flex flex-col h-[420px] overflow-hidden relative border border-white/10 group/map select-none">
      {/* Map Header Overlay */}
      <div className="p-4 border-b border-white/10 flex flex-wrap justify-between items-center bg-[#122131]/75 backdrop-blur-md z-20 absolute top-0 w-full">
        <h3 className="text-base sm:text-lg font-semibold text-[#d4e4fa] flex items-center gap-2">
          <span className="p-1 rounded-md bg-[#10b981]/20 text-[#4edea3] flex items-center justify-center">
            <MapPin className="w-4 h-4" />
          </span>
          Mapa de Intervenciones
        </h3>

        {/* Filter Pills & View toggles */}
        <div className="flex items-center gap-2 mt-2 sm:mt-0">
          <button
            onClick={() => setFilter(filter === 'active' ? 'all' : 'active')}
            className={`flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full transition-all border ${
              filter === 'active' 
                ? 'bg-[#10b981]/30 border-[#4edea3] text-[#4edea3] shadow-[0_0_10px_rgba(78,222,163,0.3)]' 
                : 'bg-[#273647]/60 border-white/10 text-[#bbcabf] hover:text-white'
            }`}
          >
            <div className="w-2 h-2 rounded-full bg-[#4edea3] animate-pulse" />
            Activas ({parcelas.filter(p => p.status === 'Aprobado' || p.status === 'Validado V2').length})
          </button>
          
          <button
            onClick={() => setFilter(filter === 'alert' ? 'all' : 'alert')}
            className={`flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full transition-all border ${
              filter === 'alert' 
                ? 'bg-[#EF4444]/30 border-[#ffb4ab] text-[#ffb4ab] shadow-[0_0_10px_rgba(239,68,68,0.3)]' 
                : 'bg-[#273647]/60 border-white/10 text-[#bbcabf] hover:text-white'
            }`}
          >
            <div className="w-2 h-2 rounded-full bg-[#EF4444]" />
            Alertas ({parcelas.filter(p => p.erosionLevel === 'Severa' || p.status === 'En Revisión').length})
          </button>

          <button
            onClick={() => setShowContours(!showContours)}
            title="Alternar curvas de nivel Keyline"
            className={`p-1 rounded-md text-xs border ${
              showContours ? 'bg-[#4cd7f6]/20 border-[#4cd7f6]/50 text-[#4cd7f6]' : 'bg-white/5 border-white/10 text-[#bbcabf]'
            }`}
          >
            <Layers className="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      {/* Map Canvas Background */}
      <div 
        className="flex-1 relative w-full h-full bg-cover bg-center overflow-hidden transition-transform duration-300"
        style={{
          backgroundImage: `url('https://lh3.googleusercontent.com/aida-public/AB6AXuC84bskMprqUt7X_mfTnG4Cu7eio0wax9Gp0BHs-rCqxOQmt_W1QrnpvW3AjMUMGFulehBWMWBQ_l9DMVuvohWZuAwkKjGKFSpY9c3FPn3nTe3xVoNT7WyTPHp5QzKJlzoE4ay16XZwdyWiaY7xfLyrQ92im6fcFFIaQi0yh3W6ON3uaiqpPbYQckgrBJWh8B6_4ZkXlBh0eiUlCQf1QltP7fCNZZA9eI2_6amlP-yF8LBqlXDWU3H8')`,
          transform: `scale(${zoom})`,
          transformOrigin: 'center center'
        }}
      >
        {/* Dark Vignette & Grid */}
        <div className="absolute inset-0 bg-gradient-to-t from-[#051424]/90 via-[#051424]/40 to-[#051424]/70" />
        
        {/* Animated Keyline Topographic Contour Vectors */}
        {showContours && (
          <svg className="absolute inset-0 w-full h-full pointer-events-none opacity-40" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id="contourGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#4edea3" stopOpacity="0.8" />
                <stop offset="50%" stopColor="#4cd7f6" stopOpacity="0.4" />
                <stop offset="100%" stopColor="#10b981" stopOpacity="0.7" />
              </linearGradient>
            </defs>
            {/* Topo lines simulation for Guatemala highlands */}
            <path d="M -50 180 Q 200 120 450 190 T 950 160 T 1400 220" fill="none" stroke="url(#contourGrad)" strokeWidth="1" strokeDasharray="4 3" />
            <path d="M -50 220 Q 250 160 500 230 T 1000 200 T 1400 270" fill="none" stroke="url(#contourGrad)" strokeWidth="1.2" />
            <path d="M -50 260 Q 300 200 550 270 T 1050 240 T 1400 310" fill="none" stroke="url(#contourGrad)" strokeWidth="1" strokeDasharray="6 4" />
            <path d="M -50 310 Q 350 240 600 310 T 1100 290 T 1400 360" fill="none" stroke="url(#contourGrad)" strokeWidth="1.5" />
            <path d="M 200 280 Q 380 230 520 290 Q 650 340 500 380 Q 320 370 200 280 Z" fill="none" stroke="#4cd7f6" strokeWidth="0.8" strokeOpacity="0.5" />
          </svg>
        )}

        {/* Pins on the Map */}
        {filteredParcelas.map((parcel) => {
          const pos = getPinPosition(parcel.latitude, parcel.longitude);
          const isAlert = parcel.erosionLevel === 'Severa' || parcel.status === 'En Revisión';
          const isSelected = selectedPin?.id === parcel.id;

          return (
            <div
              key={parcel.id}
              style={{ left: pos.left, top: pos.top }}
              onClick={() => {
                setSelectedPin(parcel);
              }}
              className="absolute -translate-x-1/2 -translate-y-1/2 cursor-pointer z-10 group"
            >
              {/* Outer radar pulse */}
              <div 
                className={`w-6 h-6 rounded-full absolute -inset-1 animate-ping opacity-75 ${
                  isAlert ? 'bg-[#EF4444]/40' : 'bg-[#4edea3]/40'
                }`} 
              />
              
              {/* Core Pin */}
              <div 
                className={`w-4 h-4 rounded-full border-2 border-[#051424] shadow-lg flex items-center justify-center transition-transform duration-200 group-hover:scale-125 ${
                  isAlert 
                    ? 'bg-[#EF4444] shadow-[0_0_12px_rgba(239,68,68,0.9)]' 
                    : 'bg-[#4edea3] shadow-[0_0_12px_rgba(78,222,163,0.9)]'
                } ${isSelected ? 'ring-4 ring-white' : ''}`}
              />

              {/* Hover / Active Tooltip */}
              <div className="absolute hidden group-hover:block bottom-full left-1/2 -translate-x-1/2 mb-2 w-56 bg-[#122131]/95 p-3 rounded-lg text-xs border border-white/15 backdrop-blur-xl shadow-2xl z-30 pointer-events-auto">
                <div className="flex items-start justify-between gap-1 mb-1">
                  <p className="font-bold text-[#4edea3] truncate">{parcel.name}</p>
                  <span className={`text-[9px] px-1.5 py-0.5 rounded font-mono ${
                    isAlert ? 'bg-[#EF4444]/20 text-[#ffb4ab]' : 'bg-[#10b981]/20 text-[#4edea3]'
                  }`}>
                    {parcel.areaHa} ha
                  </span>
                </div>
                <p className="text-[#bbcabf] text-[11px] mb-1.5">{parcel.municipality}, {parcel.department}</p>
                <div className="flex items-center justify-between pt-1.5 border-t border-white/10 text-[10px]">
                  <span className="text-white/70">Técnico: {parcel.technicianName}</span>
                  <button 
                    onClick={(e) => {
                      e.stopPropagation();
                      onSelectParcel(parcel);
                    }}
                    className="text-[#4cd7f6] hover:underline flex items-center gap-0.5"
                  >
                    Ver <Eye className="w-2.5 h-2.5" />
                  </button>
                </div>
              </div>
            </div>
          );
        })}

        {/* Selected Pin Card Modal (Floating bottom info) */}
        {selectedPin && (
          <div className="absolute bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-80 bg-[#122131]/95 border border-[#4edea3]/40 rounded-xl p-3.5 backdrop-blur-xl shadow-2xl z-20 animate-fadeIn">
            <div className="flex justify-between items-start mb-2">
              <div>
                <span className="text-[10px] font-mono bg-[#4cd7f6]/15 text-[#4cd7f6] px-1.5 py-0.5 rounded border border-[#4cd7f6]/30">
                  {selectedPin.code}
                </span>
                <h4 className="font-semibold text-sm text-white mt-1">{selectedPin.name}</h4>
              </div>
              <button 
                onClick={() => setSelectedPin(null)}
                className="text-[#bbcabf] hover:text-white text-xs px-1"
              >
                ✕
              </button>
            </div>
            
            <p className="text-xs text-[#bbcabf] mb-3">
              {selectedPin.community}, {selectedPin.municipality}, {selectedPin.department}
            </p>

            <div className="grid grid-cols-2 gap-2 text-xs mb-3 bg-black/20 p-2 rounded-lg border border-white/5">
              <div>
                <span className="text-[10px] text-white/50 block">Área Total</span>
                <span className="font-semibold text-[#4edea3]">{selectedPin.areaHa} ha</span>
              </div>
              <div>
                <span className="text-[10px] text-white/50 block">Keyline</span>
                <span className="font-semibold text-white truncate block">{selectedPin.keylinePractice.split(' ')[0]}</span>
              </div>
            </div>

            <div className="flex items-center justify-between gap-2">
              <span className={`text-[10px] px-2 py-0.5 rounded-full ${
                selectedPin.status === 'Aprobado' 
                  ? 'bg-[#10b981]/20 text-[#4edea3] border border-[#10b981]/30' 
                  : 'bg-[#f59e0b]/20 text-[#fcd34d] border border-[#f59e0b]/30'
              }`}>
                {selectedPin.status}
              </span>
              
              <button
                onClick={() => onSelectParcel(selectedPin)}
                className="px-3 py-1 bg-[#10b981] hover:bg-[#10b981]/90 text-white rounded-lg text-xs font-semibold flex items-center gap-1 transition-colors"
              >
                <Eye className="w-3.5 h-3.5" /> Ficha Técnica
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Map Control Buttons (Bottom Left) */}
      <div className="absolute bottom-3 left-3 flex flex-col gap-1 z-20">
        <button
          onClick={() => setZoom(prev => Math.min(prev + 0.2, 1.8))}
          className="w-7 h-7 bg-[#122131]/80 hover:bg-[#273647] border border-white/10 text-white rounded-md flex items-center justify-center backdrop-blur-md transition-colors"
          title="Acercar"
        >
          <ZoomIn className="w-3.5 h-3.5" />
        </button>
        <button
          onClick={() => setZoom(prev => Math.max(prev - 0.2, 0.8))}
          className="w-7 h-7 bg-[#122131]/80 hover:bg-[#273647] border border-white/10 text-white rounded-md flex items-center justify-center backdrop-blur-md transition-colors"
          title="Alejar"
        >
          <ZoomOut className="w-3.5 h-3.5" />
        </button>
      </div>

      {/* Coordinates / Region Indicator */}
      <div className="absolute bottom-3 right-3 z-10 pointer-events-none hidden sm:block">
        <span className="text-[10px] font-mono bg-black/40 text-[#bbcabf] px-2.5 py-1 rounded-full border border-white/10 backdrop-blur-sm">
          Tierras Altas de Guatemala · Geo-Datum WGS84
        </span>
      </div>
    </div>
  );
};
