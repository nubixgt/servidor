import React, { useState } from 'react';
import { 
  Calendar,
  Download,
  MapPin, 
  Users, 
  Layers, 
  Sprout, 
  ChevronDown, 
  ArrowUpRight,
  ExternalLink,
  Plus,
  Minus,
  Layers as LayersIcon,
  FileText,
  PenTool,
  CheckCircle2,
  Clock,
  Droplets,
  AlertOctagon,
  ArrowRight
} from 'lucide-react';
import { Parcela } from '../types';

interface DashboardViewProps {
  parcelas: Parcela[];
  onSelectParcel: (parcel: Parcela) => void;
  onNavigateToTab: (tab: any) => void;
  onOpenExportReport?: () => void;
}

export const DashboardView: React.FC<DashboardViewProps> = ({
  parcelas,
  onSelectParcel,
  onNavigateToTab,
  onOpenExportReport
}) => {
  const [zoomLevel, setZoomLevel] = useState(1);
  const [dateRange, setDateRange] = useState('Este mes');
  const [showDateDropdown, setShowDateDropdown] = useState(false);
  const [selectedMapPoint, setSelectedMapPoint] = useState<any | null>(null);

  // Departments ranking data matching reference
  const deptsRanking = [
    { rank: 1, name: 'Alta Verapaz', ha: '12.5 ha', pct: 90 },
    { rank: 2, name: 'Quiché', ha: '10.2 ha', pct: 75 },
    { rank: 3, name: 'Huehuetenango', ha: '8.7 ha', pct: 60 },
    { rank: 4, name: 'San Marcos', ha: '6.1 ha', pct: 45 },
    { rank: 5, name: 'Guatemala', ha: '4.3 ha', pct: 30 },
  ];

  // Map Points in Guatemala
  const mapPoints = [
    { id: 'p1', name: 'Finca El Pinar', code: 'KL-2026-00001', dept: 'Alta Verapaz', x: 54, y: 48, status: 'ejecutada' },
    { id: 'p2', name: 'Parcela Nebaj Keyline', code: 'KL-2026-00084', dept: 'Quiché', x: 44, y: 50, status: 'ejecutada' },
    { id: 'p3', name: 'Finca Los Cuchumatanes', code: 'KL-2026-00042', dept: 'Huehuetenango', x: 34, y: 44, status: 'en_proceso' },
    { id: 'p4', name: 'Parcela San Marcos', code: 'KL-2026-00105', dept: 'San Marcos', x: 25, y: 62, status: 'pendiente' },
    { id: 'p5', name: 'Terracerías Tecpán', code: 'KL-2026-00018', dept: 'Chimaltenango', x: 46, y: 66, status: 'ejecutada' },
    { id: 'p6', name: 'Agroforestería Cobán', code: 'KL-2026-00099', dept: 'Alta Verapaz', x: 58, y: 42, status: 'en_proceso' },
    { id: 'p7', name: 'Microcuenca Chixoy', code: 'KL-2026-00112', dept: 'Quiché', x: 48, y: 44, status: 'ejecutada' },
    { id: 'p8', name: 'Finca Santiago Sacatepéquez', code: 'KL-2026-00055', dept: 'Sacatepéquez', x: 50, y: 70, status: 'en_proceso' },
    { id: 'p9', name: 'Bosque Nuboso Purulhá', code: 'KL-2026-00073', dept: 'Baja Verapaz', x: 58, y: 54, status: 'ejecutada' },
    { id: 'p10', name: 'Laderas Tajumulco', code: 'KL-2026-00120', dept: 'San Marcos', x: 22, y: 58, status: 'pendiente' },
    { id: 'p11', name: 'Reserva Sierra de las Minas', code: 'KL-2026-00130', dept: 'El Progreso', x: 65, y: 60, status: 'ejecutada' },
  ];

  return (
    <div className="space-y-5 max-w-[1600px] mx-auto animate-fadeIn pb-12 font-[Arial,Helvetica,sans-serif]">
      {/* 1. Header Section: Greeting + Controls */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-bold text-white tracking-tight flex items-center gap-2">
            ¡Hola, Ana! <span className="text-xl">🌱</span>
          </h1>
          <p className="text-xs sm:text-sm text-[#94a3b8] mt-1 font-normal">
            Aquí tienes el resumen del avance nacional de parcelas keyline.
          </p>
        </div>

        {/* Action Controls */}
        <div className="flex items-center gap-3">
          {/* Date Range Selector */}
          <div className="relative">
            <button
              onClick={() => setShowDateDropdown(!showDateDropdown)}
              className="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#0c1e17] border border-[#17382b] text-xs font-medium text-white hover:border-[#22c55e]/50 transition-colors shadow-sm"
            >
              <Calendar className="w-3.5 h-3.5 text-[#94a3b8]" />
              <span>{dateRange}</span>
              <ChevronDown className="w-3 h-3 text-[#94a3b8]" />
            </button>

            {showDateDropdown && (
              <div className="absolute right-0 mt-1.5 w-40 bg-[#0c1e17] border border-[#17382b] rounded-xl shadow-2xl p-1.5 z-30 animate-fadeIn">
                {['Este mes', 'Último trimestre', 'Año 2026', 'Todo el histórico'].map((range) => (
                  <button
                    key={range}
                    onClick={() => {
                      setDateRange(range);
                      setShowDateDropdown(false);
                    }}
                    className={`w-full text-left px-2.5 py-1.5 rounded-lg text-xs transition-colors ${
                      dateRange === range ? 'bg-[#153e2d] text-[#22c55e] font-semibold' : 'text-[#cbd5e1] hover:bg-[#133225]'
                    }`}
                  >
                    {range}
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Export Button */}
          <button
            onClick={() => {
              if (onOpenExportReport) onOpenExportReport();
              else onNavigateToTab('supervisor-reviews');
            }}
            className="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0c1e17] hover:bg-[#133225] border border-[#17382b] hover:border-[#22c55e]/50 text-xs font-medium text-white transition-all shadow-sm"
          >
            <Download className="w-3.5 h-3.5 text-[#22c55e]" />
            <span>Exportar reporte</span>
          </button>
        </div>
      </div>

      {/* 2. Top 4 KPI Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Card 1: Parcelas registradas */}
        <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex items-center justify-between">
          <div>
            <span className="text-[13px] text-[#94a3b8] font-normal">
              Parcelas registradas
            </span>
            <div className="text-2xl sm:text-3xl font-bold text-white mt-1">
              1,248
            </div>
            <p className="text-[11px] text-[#22c55e] font-medium mt-1 flex items-center gap-1">
              <span>▲ 18% vs mes anterior</span>
            </p>
          </div>
          <div className="w-12 h-12 rounded-xl bg-[#081611] border border-[#17382b] flex items-center justify-center">
            <Layers className="w-6 h-6 text-[#22c55e]" />
          </div>
        </div>

        {/* Card 2: Área acumulada */}
        <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex items-center justify-between">
          <div>
            <span className="text-[13px] text-[#94a3b8] font-normal">
              Área acumulada
            </span>
            <div className="text-2xl sm:text-3xl font-bold text-white mt-1">
              12,584 <span className="text-lg font-normal text-[#94a3b8]">ha</span>
            </div>
            <p className="text-[11px] text-[#22c55e] font-medium mt-1 flex items-center gap-1">
              <span>▲ 24% vs mes anterior</span>
            </p>
          </div>
          <div className="w-12 h-12 rounded-xl bg-[#081611] border border-[#17382b] flex items-center justify-center">
            <Sprout className="w-6 h-6 text-[#22c55e]" />
          </div>
        </div>

        {/* Card 3: Cobertura territorial */}
        <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex items-center justify-between">
          <div>
            <span className="text-[13px] text-[#94a3b8] font-normal">
              Cobertura territorial
            </span>
            <div className="text-2xl sm:text-3xl font-bold text-white mt-1">
              5%
            </div>
            <p className="text-[11px] text-[#94a3b8] font-normal mt-1">
              1 de 22 departamentos
            </p>
          </div>
          <div className="w-12 h-12 rounded-xl bg-[#081611] border border-[#17382b] flex items-center justify-center">
            <MapPin className="w-6 h-6 text-[#22c55e]" />
          </div>
        </div>

        {/* Card 4: Familias beneficiadas */}
        <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex items-center justify-between">
          <div>
            <span className="text-[13px] text-[#94a3b8] font-normal">
              Familias beneficiadas
            </span>
            <div className="text-2xl sm:text-3xl font-bold text-white mt-1">
              860
            </div>
            <p className="text-[11px] text-[#94a3b8] font-normal mt-1">
              23 municipios alcanzados
            </p>
          </div>
          <div className="w-12 h-12 rounded-xl bg-[#081611] border border-[#17382b] flex items-center justify-center">
            <Users className="w-6 h-6 text-[#22c55e]" />
          </div>
        </div>
      </div>

      {/* 3. Middle Row: Semáforo de seguimiento & Validación técnica */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {/* Left: Semáforo de seguimiento */}
        <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex flex-col justify-between">
          <div>
            <h3 className="text-[15px] font-bold text-white">Semáforo de seguimiento</h3>
            <p className="text-[11px] text-[#94a3b8]">Visión rápida del estado del portafolio.</p>

            {/* 3 Status Columns */}
            <div className="grid grid-cols-3 gap-3 mt-4">
              {/* Column 1: Ejecutadas */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3.5">
                <div className="flex items-center gap-1.5">
                  <div className="w-2.5 h-2.5 rounded-full bg-[#22c55e]" />
                  <span className="text-xs text-white font-medium">Ejecutadas</span>
                </div>
                <div className="text-2xl font-bold text-white mt-2">820</div>
                <p className="text-[10px] text-[#94a3b8] mt-0.5 leading-snug">Parcelas implementadas</p>
              </div>

              {/* Column 2: En proceso */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3.5">
                <div className="flex items-center gap-1.5">
                  <div className="w-2.5 h-2.5 rounded-full bg-[#eab308]" />
                  <span className="text-xs text-white font-medium">En proceso</span>
                </div>
                <div className="text-2xl font-bold text-white mt-2">295</div>
                <p className="text-[10px] text-[#94a3b8] mt-0.5 leading-snug">Diseño y levantamiento</p>
              </div>

              {/* Column 3: Pendientes */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3.5">
                <div className="flex items-center gap-1.5">
                  <div className="w-2.5 h-2.5 rounded-full bg-[#ef4444]" />
                  <span className="text-xs text-white font-medium">Pendientes</span>
                </div>
                <div className="text-2xl font-bold text-white mt-2">133</div>
                <p className="text-[10px] text-[#94a3b8] mt-0.5 leading-snug">Requieren seguimiento</p>
              </div>
            </div>
          </div>

          <div className="mt-4 pt-3 border-t border-[#17382b]/60">
            <p className="text-[11px] text-[#94a3b8] leading-relaxed">
              El proyecto cubre <span className="text-white font-semibold">1 de 22 departamentos</span> (5% de cobertura nacional) con <span className="text-white font-semibold">0 parcelas validadas</span> y <span className="text-[#eab308] font-semibold">1 pendientes de revisión</span>.
            </p>
          </div>
        </div>

        {/* Right: Validación técnica (Donut chart & stats) */}
        <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex flex-col justify-between">
          <div>
            <h3 className="text-[15px] font-bold text-white">Validación técnica</h3>
            <p className="text-[11px] text-[#94a3b8]">Revisión de calidad de la información cargada.</p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 items-center gap-4 my-2">
            {/* SVG Donut Chart */}
            <div className="relative flex items-center justify-center">
              <svg className="w-36 h-36 transform -rotate-90" viewBox="0 0 100 100">
                {/* Background Ring */}
                <circle cx="50" cy="50" r="38" stroke="#17382b" strokeWidth="12" fill="none" />
                {/* Gray/Teal Segment (8%) */}
                <circle 
                  cx="50" cy="50" r="38" 
                  stroke="#64748b" strokeWidth="12" 
                  strokeDasharray="238.76" strokeDashoffset="0"
                  fill="none" 
                />
                {/* Yellow Segment (19%) */}
                <circle 
                  cx="50" cy="50" r="38" 
                  stroke="#eab308" strokeWidth="12" 
                  strokeDasharray="238.76" strokeDashoffset="19.1"
                  fill="none" 
                />
                {/* Green Segment (73%) */}
                <circle 
                  cx="50" cy="50" r="38" 
                  stroke="#22c55e" strokeWidth="12" 
                  strokeDasharray="238.76" strokeDashoffset="64.4"
                  fill="none" 
                  strokeLinecap="round"
                />
              </svg>
              {/* Donut Center */}
              <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span className="text-xl font-bold text-white">1,115</span>
                <span className="text-[10px] text-[#94a3b8] uppercase font-semibold">Total</span>
              </div>
            </div>

            {/* Legend / Metrics List */}
            <div className="space-y-3">
              <div className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <div className="w-2.5 h-2.5 rounded-sm bg-[#22c55e]" />
                  <span className="text-[#cbd5e1]">Validadas</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="font-bold text-white">820</span>
                  <span className="text-[#94a3b8] w-8 text-right">73%</span>
                </div>
              </div>

              <div className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <div className="w-2.5 h-2.5 rounded-sm bg-[#eab308]" />
                  <span className="text-[#cbd5e1]">Pendientes de revisión</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="font-bold text-white">215</span>
                  <span className="text-[#94a3b8] w-8 text-right">19%</span>
                </div>
              </div>

              <div className="flex items-center justify-between text-xs">
                <div className="flex items-center gap-2">
                  <div className="w-2.5 h-2.5 rounded-sm bg-[#64748b]" />
                  <span className="text-[#cbd5e1]">Otras</span>
                </div>
                <div className="flex items-center gap-3">
                  <span className="font-bold text-white">80</span>
                  <span className="text-[#94a3b8] w-8 text-right">8%</span>
                </div>
              </div>
            </div>
          </div>

          <div className="pt-2"></div>
        </div>
      </div>

      {/* 4. Map & Department Ranking + Process Status */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
        {/* Left (7 cols): Mapa nacional de parcelas */}
        <div className="lg:col-span-7 bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex flex-col justify-between">
          <div className="flex items-center justify-between mb-3">
            <div>
              <h3 className="text-[15px] font-bold text-white">Mapa nacional de parcelas</h3>
              <p className="text-[11px] text-[#94a3b8]">Ubicación georreferenciada de las parcelas con coordenadas registradas.</p>
            </div>
            <button 
              onClick={() => onNavigateToTab('plot-inventory')}
              className="text-xs text-[#22c55e] hover:text-[#4ade80] flex items-center gap-1 font-medium transition-colors"
            >
              <span>Ver mapa completo</span>
              <ExternalLink className="w-3 h-3" />
            </button>
          </div>

          {/* Interactive Map Visual */}
          <div className="relative w-full h-[280px] bg-[#081611] rounded-xl overflow-hidden border border-[#17382b] flex items-center justify-center">
            {/* Topographic Background Contour Grid */}
            <div 
              className="absolute inset-0 opacity-40 transition-transform duration-300"
              style={{
                transform: `scale(${zoomLevel})`,
                backgroundImage: `radial-gradient(#153e2d 1px, transparent 1px), radial-gradient(#17382b 1px, #081611 1px)`,
                backgroundSize: '24px 24px',
                backgroundPosition: '0 0, 12px 12px'
              }}
            />

            {/* Stylized Guatemala Map Borders & Mountain Relief Path */}
            <svg 
              className="absolute inset-0 w-full h-full p-4 opacity-75"
              viewBox="0 0 100 100" 
              preserveAspectRatio="xMidYMid meet"
            >
              {/* Shaded Guatemala territory polygon */}
              <polygon 
                points="18,52 28,32 50,22 75,20 85,38 78,55 70,72 52,82 30,82 15,68" 
                fill="#0e281e" 
                stroke="#1e543b" 
                strokeWidth="0.8"
              />
              {/* Sierra Madre & Cuchumatanes Keyline contour ribbons */}
              <path d="M22 62 Q 35 48 55 58 T 76 50" fill="none" stroke="#22c55e" strokeWidth="0.6" strokeDasharray="2,2" opacity="0.6" />
              <path d="M26 50 Q 42 38 62 45 T 80 40" fill="none" stroke="#22c55e" strokeWidth="0.6" strokeDasharray="2,2" opacity="0.6" />
              <path d="M30 72 Q 48 64 68 70" fill="none" stroke="#22c55e" strokeWidth="0.5" opacity="0.4" />
            </svg>

            {/* Plotted Plot Location Pins */}
            {mapPoints.map((pt) => {
              const isExec = pt.status === 'ejecutada';
              const isProc = pt.status === 'en_proceso';
              return (
                <div
                  key={pt.id}
                  onClick={() => setSelectedMapPoint(pt)}
                  style={{ left: `${pt.x}%`, top: `${pt.y}%` }}
                  className="absolute -translate-x-1/2 -translate-y-1/2 cursor-pointer group z-20"
                >
                  <div className={`w-3 h-3 rounded-full border border-black flex items-center justify-center transition-transform duration-200 group-hover:scale-150 ${
                    isExec ? 'bg-[#22c55e] shadow-[0_0_8px_#22c55e]' : isProc ? 'bg-[#eab308] shadow-[0_0_8px_#eab308]' : 'bg-[#ef4444] shadow-[0_0_8px_#ef4444]'
                  }`} />
                  
                  {/* Tooltip on hover */}
                  <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1.5 hidden group-hover:block bg-[#081611] text-white text-[10px] px-2 py-1 rounded-md border border-[#17382b] whitespace-nowrap shadow-xl z-30">
                    <p className="font-bold">{pt.name}</p>
                    <p className="text-[9px] text-[#94a3b8]">{pt.dept} · {pt.code}</p>
                  </div>
                </div>
              );
            })}

            {/* Map Controls */}
            <div className="absolute top-3 left-3 flex flex-col gap-1 z-20">
              <button 
                onClick={() => setZoomLevel(prev => Math.min(prev + 0.2, 1.8))}
                className="w-7 h-7 bg-[#0c1e17] border border-[#17382b] rounded-lg text-white flex items-center justify-center hover:bg-[#133225] transition-colors text-xs font-bold"
                title="Acercar"
              >
                <Plus className="w-3.5 h-3.5" />
              </button>
              <button 
                onClick={() => setZoomLevel(prev => Math.max(prev - 0.2, 0.8))}
                className="w-7 h-7 bg-[#0c1e17] border border-[#17382b] rounded-lg text-white flex items-center justify-center hover:bg-[#133225] transition-colors text-xs font-bold"
                title="Alejar"
              >
                <Minus className="w-3.5 h-3.5" />
              </button>
              <button 
                onClick={() => setZoomLevel(1)}
                className="w-7 h-7 bg-[#0c1e17] border border-[#17382b] rounded-lg text-white flex items-center justify-center hover:bg-[#133225] transition-colors text-xs font-bold"
                title="Centrar"
              >
                <LayersIcon className="w-3.5 h-3.5 text-[#22c55e]" />
              </button>
            </div>

            {/* Selected Map Pin Detail Box */}
            {selectedMapPoint && (
              <div className="absolute bottom-3 left-3 right-3 bg-[#0c1e17]/95 border border-[#22c55e]/50 p-2.5 rounded-xl flex items-center justify-between z-30 shadow-2xl backdrop-blur-md animate-fadeIn">
                <div>
                  <p className="text-xs font-bold text-white">{selectedMapPoint.name}</p>
                  <p className="text-[10px] text-[#94a3b8]">{selectedMapPoint.dept} · Código: {selectedMapPoint.code}</p>
                </div>
                <div className="flex items-center gap-2">
                  <span className={`text-[10px] px-2 py-0.5 rounded-full font-semibold ${
                    selectedMapPoint.status === 'ejecutada' ? 'bg-[#22c55e]/20 text-[#22c55e]' : 'bg-[#eab308]/20 text-[#eab308]'
                  }`}>
                    {selectedMapPoint.status}
                  </span>
                  <button 
                    onClick={() => setSelectedMapPoint(null)}
                    className="text-xs text-[#94a3b8] hover:text-white px-1.5"
                  >
                    ✕
                  </button>
                </div>
              </div>
            )}
          </div>

          {/* Map Legend */}
          <div className="flex items-center gap-4 mt-3 text-xs text-[#cbd5e1]">
            <div className="flex items-center gap-1.5">
              <div className="w-2.5 h-2.5 rounded-full bg-[#22c55e]" />
              <span>Ejecutadas</span>
            </div>
            <div className="flex items-center gap-1.5">
              <div className="w-2.5 h-2.5 rounded-full bg-[#eab308]" />
              <span>En proceso</span>
            </div>
            <div className="flex items-center gap-1.5">
              <div className="w-2.5 h-2.5 rounded-full bg-[#ef4444]" />
              <span>Pendientes</span>
            </div>
          </div>
        </div>

        {/* Right (5 cols): Stacked Cards (Parcelas por departamento + Estado del proceso) */}
        <div className="lg:col-span-5 space-y-4 flex flex-col justify-between">
          {/* Card A: Parcelas por departamento */}
          <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5">
            <div className="flex items-center justify-between mb-3">
              <div>
                <h3 className="text-[15px] font-bold text-white">Parcelas por departamento</h3>
                <p className="text-[11px] text-[#94a3b8]">Ranking según registros ingresados.</p>
              </div>
              <button 
                onClick={() => onNavigateToTab('plot-inventory')}
                className="text-xs text-[#22c55e] hover:text-[#4ade80] font-medium transition-colors"
              >
                Ver ranking
              </button>
            </div>

            {/* Department List with Green Progress Bars */}
            <div className="space-y-2.5">
              {deptsRanking.map((dept) => (
                <div key={dept.rank} className="flex items-center gap-3 text-xs">
                  <span className="w-4 text-[#94a3b8] font-bold text-[11px]">{dept.rank}</span>
                  <span className="w-28 text-white truncate font-medium">{dept.name}</span>
                  <div className="flex-1 h-2 bg-[#081611] rounded-full overflow-hidden border border-[#17382b]">
                    <div 
                      className="h-full bg-[#22c55e] rounded-full transition-all duration-500" 
                      style={{ width: `${dept.pct}%` }} 
                    />
                  </div>
                  <span className="w-14 text-right text-white font-medium text-[11px]">{dept.ha}</span>
                </div>
              ))}
            </div>
          </div>

          {/* Card B: Estado del proceso */}
          <div className="bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5">
            <h3 className="text-[15px] font-bold text-white">Estado del proceso</h3>
            <p className="text-[11px] text-[#94a3b8] mb-3">Distribución por fase técnica.</p>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
              {/* Levantamiento */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-2.5 text-center">
                <div className="w-6 h-6 rounded-lg bg-[#22c55e]/15 text-[#22c55e] flex items-center justify-center mx-auto mb-1">
                  <FileText className="w-3.5 h-3.5" />
                </div>
                <div className="text-base font-bold text-white">820</div>
                <p className="text-[10px] text-[#94a3b8]">Levantamiento</p>
              </div>

              {/* Diseño */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-2.5 text-center">
                <div className="w-6 h-6 rounded-lg bg-[#eab308]/15 text-[#eab308] flex items-center justify-center mx-auto mb-1">
                  <PenTool className="w-3.5 h-3.5" />
                </div>
                <div className="text-base font-bold text-white">295</div>
                <p className="text-[10px] text-[#94a3b8]">Diseño</p>
              </div>

              {/* Implementado */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-2.5 text-center">
                <div className="w-6 h-6 rounded-lg bg-[#38bdf8]/15 text-[#38bdf8] flex items-center justify-center mx-auto mb-1">
                  <CheckCircle2 className="w-3.5 h-3.5" />
                </div>
                <div className="text-base font-bold text-white">95</div>
                <p className="text-[10px] text-[#94a3b8]">Implementado</p>
              </div>

              {/* Pendiente */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-2.5 text-center">
                <div className="w-6 h-6 rounded-lg bg-[#ef4444]/15 text-[#ef4444] flex items-center justify-center mx-auto mb-1">
                  <Clock className="w-3.5 h-3.5" />
                </div>
                <div className="text-base font-bold text-white">133</div>
                <p className="text-[10px] text-[#94a3b8]">Pendiente</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* 5. Bottom Row: Diagnóstico físico del suelo & Registros recientes */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
        {/* Left (7 cols): Diagnóstico físico del suelo */}
        <div className="lg:col-span-7 bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex flex-col justify-between">
          <div>
            <h3 className="text-[15px] font-bold text-white">Diagnóstico físico del suelo</h3>
            <p className="text-[11px] text-[#94a3b8] mb-4">Talpetate, encharcamiento, profundidad y bioindicadores.</p>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              {/* Sub-item 1: Talpetate */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3 flex flex-col justify-between">
                <div>
                  <div className="flex items-center gap-1.5 text-xs text-[#94a3b8]">
                    <AlertOctagon className="w-3.5 h-3.5 text-[#ef4444]" />
                    <span className="font-semibold text-white">Talpetate</span>
                  </div>
                  <div className="text-xl font-bold text-white mt-1">154</div>
                  <p className="text-[10px] text-[#94a3b8] mt-0.5">de 1,248 parcelas (12%)</p>
                </div>
                <div className="mt-3 pt-1 border-t-2 border-[#ef4444]">
                  <span className="text-[9px] text-[#ef4444] font-medium">Con talpetate: 154</span>
                </div>
              </div>

              {/* Sub-item 2: Encharcamiento */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3 flex flex-col justify-between">
                <div>
                  <div className="flex items-center gap-1.5 text-xs text-[#94a3b8]">
                    <Droplets className="w-3.5 h-3.5 text-[#38bdf8]" />
                    <span className="font-semibold text-white">Encharcamiento</span>
                  </div>
                  <div className="text-xl font-bold text-white mt-1">86</div>
                  <p className="text-[10px] text-[#94a3b8] mt-0.5">parcelas con encharcamiento (6.8%)</p>
                </div>
                <div className="mt-3 pt-1 border-t-2 border-[#38bdf8]">
                  <span className="text-[9px] text-[#38bdf8] font-medium">Sin encharcamiento: 1,162</span>
                </div>
              </div>

              {/* Sub-item 3: Profundidad de suelo */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3 flex flex-col justify-between">
                <div>
                  <div className="flex items-center gap-1.5 text-xs text-[#94a3b8]">
                    <Sprout className="w-3.5 h-3.5 text-[#22c55e]" />
                    <span className="font-semibold text-white">Profundidad suelo</span>
                  </div>
                  <div className="text-xl font-bold text-white mt-1">0.65 m</div>
                  <p className="text-[10px] text-[#94a3b8] mt-0.5">Promedio (56 mediciones).</p>
                </div>
                <div className="mt-3 pt-1 border-t-2 border-[#22c55e]">
                  <span className="text-[9px] text-[#22c55e] font-medium">Estrato óptimo</span>
                </div>
              </div>

              {/* Sub-item 4: Bioindicadores de suelo */}
              <div className="bg-[#081611] border border-[#17382b] rounded-xl p-3 flex flex-col justify-between">
                <div>
                  <div className="flex items-center gap-1.5 text-xs text-[#94a3b8]">
                    <Sprout className="w-3.5 h-3.5 text-[#22c55e]" />
                    <span className="font-semibold text-white">Bioindicadores</span>
                  </div>
                  <p className="text-[10px] text-[#94a3b8] mt-2 leading-tight">
                    Aún no hay bioindicadores capturados.
                  </p>
                </div>
                <div className="mt-3">
                  <button 
                    onClick={() => onNavigateToTab('field-surveys')}
                    className="w-full py-1.5 px-2 bg-[#153e2d] hover:bg-[#1a4f3a] text-[#22c55e] border border-[#22c55e]/30 rounded-lg text-[10px] font-semibold transition-colors"
                  >
                    Registrar bioindicadores
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Right (5 cols): Registros recientes */}
        <div className="lg:col-span-5 bg-[#0c1e17] border border-[#17382b] rounded-2xl p-5 flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-3">
              <div>
                <h3 className="text-[15px] font-bold text-white">Registros recientes</h3>
                <p className="text-[11px] text-[#94a3b8]">Últimas parcelas cargadas por el equipo técnico.</p>
              </div>
              <button 
                onClick={() => onNavigateToTab('plot-inventory')}
                className="text-xs text-[#22c55e] hover:text-[#4ade80] font-medium transition-colors"
              >
                Ver todas
              </button>
            </div>

            {/* Table */}
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead>
                  <tr className="text-[10px] text-[#94a3b8] border-b border-[#17382b] uppercase tracking-wider font-semibold">
                    <th className="pb-2 font-semibold">Parcela</th>
                    <th className="pb-2 font-semibold">Técnico</th>
                    <th className="pb-2 font-semibold">Departamento</th>
                    <th className="pb-2 font-semibold">Estado</th>
                    <th className="pb-2 font-semibold">Fecha</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-[#17382b]">
                  {parcelas.slice(0, 3).map((p) => (
                    <tr 
                      key={p.id}
                      onClick={() => onSelectParcel(p)}
                      className="hover:bg-[#133225]/40 transition-colors cursor-pointer group"
                    >
                      <td className="py-2.5 pr-2">
                        <div className="flex items-center gap-2">
                          <img 
                            src={p.photos[0] || 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=100&auto=format&fit=crop'} 
                            alt={p.name}
                            className="w-7 h-7 rounded-md object-cover border border-[#17382b] flex-shrink-0"
                          />
                          <div className="overflow-hidden">
                            <p className="font-semibold text-white group-hover:text-[#22c55e] transition-colors truncate text-[11px]">
                              {p.name}
                            </p>
                            <p className="text-[9px] font-mono text-[#94a3b8] truncate">{p.code}</p>
                          </div>
                        </div>
                      </td>
                      <td className="py-2.5 px-2 text-[#cbd5e1] text-[11px] truncate">
                        {p.technicianName}
                      </td>
                      <td className="py-2.5 px-2 text-[#cbd5e1] text-[11px]">
                        {p.department}
                      </td>
                      <td className="py-2.5 px-2">
                        <span className="text-[10px] px-2 py-0.5 rounded-full font-medium bg-[#22c55e]/15 text-[#22c55e] border border-[#22c55e]/30">
                          {p.status}
                        </span>
                      </td>
                      <td className="py-2.5 pl-2 text-[#94a3b8] text-[10px] whitespace-nowrap">
                        {p.registrationDate}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
