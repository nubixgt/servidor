import React, { useState } from 'react';
import { 
  X, 
  Download, 
  FileText, 
  FileSpreadsheet, 
  Map, 
  CheckCircle2, 
  Trees
} from 'lucide-react';
import { Parcela } from '../types';

interface ExportModalProps {
  parcelas: Parcela[];
  onClose: () => void;
}

export const ExportModal: React.FC<ExportModalProps> = ({ parcelas, onClose }) => {
  const [format, setFormat] = useState<'pdf' | 'csv' | 'geojson'>('pdf');
  const [selectedDept, setSelectedDept] = useState('Todos');
  const [isExporting, setIsExporting] = useState(false);
  const [downloadSuccess, setDownloadSuccess] = useState(false);

  const handleDownload = () => {
    setIsExporting(true);

    setTimeout(() => {
      if (format === 'csv') {
        const headers = ["ID", "Nombre", "Productor", "Departamento", "Municipio", "Comunidad", "Area_ha", "Familias", "Estado", "Tecnico", "Keyline", "Pendiente_deg", "Lat", "Lon"];
        const rows = parcelas.map(p => [
          p.code,
          `"${p.name}"`,
          `"${p.producer}"`,
          `"${p.department}"`,
          `"${p.municipality}"`,
          `"${p.community}"`,
          p.areaHa,
          p.benefitedFamilies,
          p.status,
          `"${p.technicianName}"`,
          `"${p.keylinePractice}"`,
          p.slopeDegrees,
          p.latitude,
          p.longitude
        ]);

        const csvContent = "data:text/csv;charset=utf-8," + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `keylinegt_reporte_consolidado_${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } else if (format === 'geojson') {
        const geojson = {
          type: "FeatureCollection",
          features: parcelas.map(p => ({
            type: "Feature",
            geometry: {
              type: "Point",
              coordinates: [p.longitude, p.latitude]
            },
            properties: {
              code: p.code,
              name: p.name,
              producer: p.producer,
              areaHa: p.areaHa,
              department: p.department,
              status: p.status
            }
          }))
        };
        const blob = new Blob([JSON.stringify(geojson, null, 2)], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `keylinegt_poligonos_${new Date().toISOString().split('T')[0]}.geojson`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } else {
        window.print();
      }

      setIsExporting(false);
      setDownloadSuccess(true);
      setTimeout(() => setDownloadSuccess(false), 3000);
    }, 800);
  };

  const departments = ['Todos', 'Alta Verapaz', 'Huehuetenango', 'Quiché', 'Chimaltenango', 'Petén', 'San Marcos'];

  return (
    <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn">
      <div className="glass-panel max-w-lg w-full rounded-2xl p-6 border border-white/20 shadow-2xl space-y-5">
        <div className="flex justify-between items-center pb-3 border-b border-white/10">
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 rounded-lg bg-[#22c55e]/20 flex items-center justify-center border border-[#4ade80]/30 text-[#4ade80]">
              <Trees className="w-4 h-4" />
            </div>
            <div>
              <h3 className="text-base font-bold text-white">Generar Reporte Oficial</h3>
              <p className="text-xs text-[#cbd5e1]">Exportación de métricas y polígonos geoespaciales</p>
            </div>
          </div>
          <button onClick={onClose} className="text-[#94a3b8] hover:text-white">
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Format Selection */}
        <div>
          <label className="text-xs font-semibold text-white block mb-2">Formato de Exportación</label>
          <div className="grid grid-cols-3 gap-2.5">
            <button
              type="button"
              onClick={() => setFormat('pdf')}
              className={`p-3 rounded-xl text-left border transition-all ${
                format === 'pdf'
                  ? 'bg-[#22c55e]/20 border-[#4ade80] text-white shadow-sm'
                  : 'bg-black/30 border-white/10 text-[#cbd5e1] hover:bg-white/5'
              }`}
            >
              <FileText className={`w-5 h-5 mb-1.5 ${format === 'pdf' ? 'text-[#4ade80]' : 'text-[#94a3b8]'}`} />
              <p className="text-xs font-bold">PDF Oficial</p>
              <p className="text-[10px] text-[#94a3b8]">Dossier con mapas</p>
            </button>

            <button
              type="button"
              onClick={() => setFormat('csv')}
              className={`p-3 rounded-xl text-left border transition-all ${
                format === 'csv'
                  ? 'bg-[#22c55e]/20 border-[#4ade80] text-white shadow-sm'
                  : 'bg-black/30 border-white/10 text-[#cbd5e1] hover:bg-white/5'
              }`}
            >
              <FileSpreadsheet className={`w-5 h-5 mb-1.5 ${format === 'csv' ? 'text-[#4ade80]' : 'text-[#94a3b8]'}`} />
              <p className="text-xs font-bold">CSV / Excel</p>
              <p className="text-[10px] text-[#94a3b8]">Datos tabulados</p>
            </button>

            <button
              type="button"
              onClick={() => setFormat('geojson')}
              className={`p-3 rounded-xl text-left border transition-all ${
                format === 'geojson'
                  ? 'bg-[#22c55e]/20 border-[#4ade80] text-white shadow-sm'
                  : 'bg-black/30 border-white/10 text-[#cbd5e1] hover:bg-white/5'
              }`}
            >
              <Map className={`w-5 h-5 mb-1.5 ${format === 'geojson' ? 'text-[#4ade80]' : 'text-[#94a3b8]'}`} />
              <p className="text-xs font-bold">GeoJSON (GIS)</p>
              <p className="text-[10px] text-[#94a3b8]">QGIS / ArcGIS</p>
            </button>
          </div>
        </div>

        {/* Filter Selection */}
        <div>
          <label className="text-xs font-semibold text-white block mb-1">Filtrar por Departamento</label>
          <select
            value={selectedDept}
            onChange={(e) => setSelectedDept(e.target.value)}
            className="w-full glass-input rounded-xl p-2.5 text-xs text-white bg-black/80"
          >
            {departments.map((d) => (
              <option key={d} value={d}>{d}</option>
            ))}
          </select>
        </div>

        {/* Summary note */}
        <div className="bg-black/30 p-3 rounded-xl border border-white/10 text-xs text-[#cbd5e1]">
          <p className="text-white font-semibold">Resumen de Exportación:</p>
          <p className="text-[11px] text-[#94a3b8] mt-0.5">
            Se incluirán {parcelas.length} parcelas registradas con coordenadas GPS, prácticas Keyline y beneficiarios.
          </p>
        </div>

        {downloadSuccess && (
          <div className="p-3 bg-[#22c55e]/20 border border-[#4ade80]/40 rounded-xl text-xs text-[#4ade80] flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4" />
            <span>Descarga generada con éxito</span>
          </div>
        )}

        <div className="flex justify-end gap-2 pt-3 border-t border-white/10">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 bg-white/10 hover:bg-white/20 text-xs text-white rounded-xl"
          >
            Cancelar
          </button>
          <button
            type="button"
            onClick={handleDownload}
            disabled={isExporting}
            className="px-5 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-xs font-bold text-white rounded-xl shadow-lg flex items-center gap-2"
          >
            <Download className="w-4 h-4" />
            <span>{isExporting ? 'Procesando...' : 'Descargar Archivo'}</span>
          </button>
        </div>
      </div>
    </div>
  );
};
