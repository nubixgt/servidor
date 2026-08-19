import React from 'react';
import { 
  X, 
  MapPin, 
  Compass, 
  Ruler, 
  Layers, 
  Mountain, 
  Droplets, 
  Trees, 
  Calendar, 
  CheckCircle2, 
  User, 
  Download, 
  Printer
} from 'lucide-react';
import { Parcela } from '../types';

interface PlotDetailModalProps {
  parcel: Parcela;
  onClose: () => void;
  onApprove?: (id: string) => void;
}

export const PlotDetailModal: React.FC<PlotDetailModalProps> = ({
  parcel,
  onClose,
  onApprove
}) => {
  const isValidated = parcel.status === 'Aprobado' || parcel.validationTag === 'Validado V2';

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
      <div className="glass-panel border border-white/20 rounded-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-2xl animate-fadeIn text-[#f1f5f9]">
        {/* Modal Header */}
        <div className="sticky top-0 bg-black/85 backdrop-blur-2xl border-b border-white/10 p-4 sm:p-6 flex justify-between items-start z-20">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center text-[#4ade80]">
              <Trees className="w-6 h-6" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <span className="text-xs font-mono bg-[#06b6d4]/15 text-[#38bdf8] px-2 py-0.5 rounded border border-[#38bdf8]/30">
                  {parcel.code}
                </span>
                <span className={`text-xs px-2.5 py-0.5 rounded-full font-semibold border ${
                  isValidated ? 'bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30' : 'bg-[#f59e0b]/20 text-[#fbbf24] border-[#f59e0b]/30'
                }`}>
                  {parcel.validationTag || parcel.status}
                </span>
              </div>
              <h2 className="text-xl sm:text-2xl font-bold text-white mt-1">{parcel.name}</h2>
              <p className="text-xs text-[#cbd5e1] flex items-center gap-1 mt-0.5">
                <MapPin className="w-3.5 h-3.5 text-[#38bdf8]" />
                <span>{parcel.community}, {parcel.municipality}, {parcel.department}</span>
              </p>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button
              onClick={handlePrint}
              className="p-2 text-[#cbd5e1] hover:text-white bg-white/10 hover:bg-white/15 rounded-lg transition-colors"
              title="Imprimir Ficha Técnica"
            >
              <Printer className="w-4 h-4" />
            </button>
            <button
              onClick={onClose}
              className="p-2 text-[#cbd5e1] hover:text-white bg-white/10 hover:bg-white/15 rounded-lg transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        <div className="p-4 sm:p-6 space-y-6">
          {/* Key Metrics Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div className="bg-black/30 p-3.5 rounded-xl border border-white/10">
              <span className="text-[10px] uppercase font-bold text-[#94a3b8] block">Área Total</span>
              <span className="text-xl font-bold text-white font-mono">{parcel.areaHa} ha</span>
            </div>
            <div className="bg-black/30 p-3.5 rounded-xl border border-white/10">
              <span className="text-[10px] uppercase font-bold text-[#94a3b8] block">Pendiente Media</span>
              <span className="text-xl font-bold text-white font-mono">{parcel.slopeDegrees}° ({parcel.slopeClassification})</span>
            </div>
            <div className="bg-black/30 p-3.5 rounded-xl border border-white/10">
              <span className="text-[10px] uppercase font-bold text-[#94a3b8] block">Familias Beneficiadas</span>
              <span className="text-xl font-bold text-[#4ade80] font-mono">{parcel.benefitedFamilies}</span>
            </div>
            <div className="bg-black/30 p-3.5 rounded-xl border border-white/10">
              <span className="text-[10px] uppercase font-bold text-[#94a3b8] block">Volumen Hídrico Retenido</span>
              <span className="text-xl font-bold text-[#38bdf8] font-mono">{(parcel.areaHa * 420).toFixed(0)} m³</span>
            </div>
          </div>

          {/* Keyline & Soil Parameters */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div className="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
              <h3 className="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                <Compass className="w-4 h-4 text-[#4ade80]" />
                <span>Diseño Hidrológico Keyline</span>
              </h3>
              <div className="space-y-2 text-xs">
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Práctica Implementada:</span>
                  <span className="text-white font-medium">{parcel.keylinePractice}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Espaciamiento entre Líneas:</span>
                  <span className="text-white font-mono">{parcel.lineSpacingMeters} m</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Longitud Total Trazada:</span>
                  <span className="text-white font-mono">{parcel.totalLengthMeters} m lineales</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Orientación / Azimut Maestro:</span>
                  <span className="text-white font-mono">N 42° E</span>
                </div>
              </div>
            </div>

            <div className="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
              <h3 className="text-sm font-bold text-white flex items-center gap-2 border-b border-white/10 pb-2">
                <Mountain className="w-4 h-4 text-[#facc15]" />
                <span>Diagnóstico de Suelo y Terreno</span>
              </h3>
              <div className="space-y-2 text-xs">
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Textura del Suelo:</span>
                  <span className="text-white font-medium">{parcel.soilTexture}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Nivel de Erosión Previo:</span>
                  <span className="text-[#fca5a5] font-medium">{parcel.erosionLevel}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Profundidad Efectiva:</span>
                  <span className="text-white font-mono">{parcel.soilDepthCm} cm</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-[#94a3b8]">Riesgo de Encharcamiento:</span>
                  <span className="text-[#38bdf8] font-medium">{parcel.hasWaterlogging ? 'Sí' : 'No'}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Species & Producer Details */}
          <div className="bg-black/30 p-5 rounded-xl border border-white/10 space-y-3">
            <h3 className="text-sm font-bold text-white flex items-center gap-2">
              <Trees className="w-4 h-4 text-[#4ade80]" />
              <span>Especies Forestales y Coberturas Asociadas</span>
            </h3>
            <div className="flex flex-wrap gap-2 pt-1">
              {parcel.associatedSpecies.map((sp, idx) => (
                <span key={idx} className="bg-white/10 border border-white/15 text-white px-3 py-1 rounded-lg text-xs font-medium">
                  {sp}
                </span>
              ))}
            </div>
          </div>

          {/* Field Photos Gallery */}
          {parcel.photos && parcel.photos.length > 0 && (
            <div>
              <h3 className="text-sm font-bold text-white mb-3">Evidencia Fotográfica de Campo</h3>
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {parcel.photos.map((photo, idx) => (
                  <div key={idx} className="h-32 rounded-xl overflow-hidden border border-white/15 group relative bg-black/40">
                    <img src={photo} alt={`Campo ${idx + 1}`} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Footer Metadata & Action Buttons */}
          <div className="pt-4 border-t border-white/10 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 text-xs">
            <div className="space-y-0.5 text-[#94a3b8]">
              <p>Productor: <strong className="text-white">{parcel.producer}</strong></p>
              <p>Técnico Responsable: <strong className="text-white">{parcel.technicianName}</strong> · Registrado el {parcel.registrationDate}</p>
            </div>

            <div className="flex items-center gap-2 w-full sm:w-auto">
              {!isValidated && onApprove && (
                <button
                  onClick={() => {
                    onApprove(parcel.id);
                    onClose();
                  }}
                  className="flex-1 sm:flex-initial px-4 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-white font-bold rounded-xl flex items-center justify-center gap-1.5 shadow-lg transition-all"
                >
                  <CheckCircle2 className="w-4 h-4" />
                  <span>Validar Parcela</span>
                </button>
              )}
              <button
                onClick={onClose}
                className="flex-1 sm:flex-initial px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-all"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
