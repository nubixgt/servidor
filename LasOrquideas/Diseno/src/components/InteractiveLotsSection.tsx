import { useState, useId } from 'react';
import { LOTS_DATA, Lot } from '../data/lotData';
import { Calculator, MessageCircle, CheckCircle2 } from 'lucide-react';

interface InteractiveLotsSectionProps {
  selectedLotId: string;
  onSelectLot: (lotId: string) => void;
  onOpenReservation: (lot: Lot) => void;
}

export function InteractiveLotsSection({
  selectedLotId,
  onSelectLot,
  onOpenReservation,
}: InteractiveLotsSectionProps) {
  const metrajeSelectId = useId();
  const [statusFilter, setStatusFilter] = useState<'all' | 'disponible' | 'reservado' | 'vendido'>('all');
  const [metrajeFilter, setMetrajeFilter] = useState<string>('220-300');
  const [showFinancingCalc, setShowFinancingCalc] = useState<boolean>(false);
  const [downPaymentPercent, setDownPaymentPercent] = useState<number>(10);
  const [termYears, setTermYears] = useState<number>(5);

  const selectedLot = LOTS_DATA.find((l) => l.id === selectedLotId) || LOTS_DATA[11]; // default Lote 12

  // Filter lots based on status & metraje
  const filteredLots = LOTS_DATA.filter((lot) => {
    if (statusFilter !== 'all' && lot.status !== statusFilter) return false;
    if (metrajeFilter === '180-220' && (lot.areaM2 < 180 || lot.areaM2 > 220)) return false;
    if (metrajeFilter === '220-300' && (lot.areaM2 < 220 || lot.areaM2 > 300)) return false;
    if (metrajeFilter === '300+' && lot.areaM2 <= 300) return false;
    return true;
  });

  // Financial calculations
  const downPaymentAmount = (selectedLot.priceQ * downPaymentPercent) / 100;
  const financedAmount = selectedLot.priceQ - downPaymentAmount;
  const annualInterestRate = 0.085; // 8.5% annual rate
  const monthlyRate = annualInterestRate / 12;
  const totalMonths = termYears * 12;
  const monthlyPaymentQ = Math.round(
    (financedAmount * (monthlyRate * Math.pow(1 + monthlyRate, totalMonths))) /
      (Math.pow(1 + monthlyRate, totalMonths) - 1)
  );
  const monthlyPaymentUSD = Math.round(monthlyPaymentQ / 7.8);

  const row1 = LOTS_DATA.slice(0, 6);
  const row2 = LOTS_DATA.slice(6, 12);

  const getLotStatusStyle = (lot: Lot, isSelected: boolean) => {
    if (isSelected) {
      return 'bg-brand-forest ring-4 ring-brand-forest/30 text-white font-extrabold shadow-md transform scale-105 border-white';
    }
    if (lot.status === 'vendido') {
      return 'bg-slate-300 text-slate-500 font-bold border-white cursor-not-allowed opacity-80';
    }
    if (lot.status === 'reservado') {
      return 'bg-amber-400 hover:bg-amber-500 text-amber-950 font-bold border-white shadow-xs cursor-pointer';
    }
    // disponible
    return 'bg-emerald-500 hover:bg-emerald-600 text-white font-bold border-white shadow-xs cursor-pointer hover:scale-102';
  };

  return (
    <section className="py-16 bg-white/60 relative backdrop-blur-md border-y border-slate-200/60" id="lotes">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="text-center max-w-3xl mx-auto mb-10">
          <div className="inline-flex items-center gap-2 mb-2">
            <span className="w-5 h-[2px] bg-brand-forest" />
            <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">
              PLANO MASTER Y DISPONIBILIDAD
            </span>
            <span className="w-5 h-[2px] bg-brand-forest" />
          </div>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Encuentra el lote perfecto para tu hogar
          </h2>
          <p className="text-slate-600 text-sm mt-3">
            Elige la ubicación preferida dentro de nuestra primera fase de desarrollo. Todos los
            lotes cuentan con escrituración inmediata y facilidades de financiamiento directo.
          </p>
        </div>

        {/* Filter Bar Controls */}
        <div className="glass-card rounded-2xl p-4 shadow-xs mb-8 flex flex-wrap items-center justify-between gap-4">
          {/* Status Toggles */}
          <div className="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 text-xs font-semibold">
            <span className="text-slate-500 mr-2 text-xs uppercase tracking-wider">Estado:</span>
            <button
              onClick={() => setStatusFilter('all')}
              className={`px-3.5 py-1.5 rounded-full transition cursor-pointer ${
                statusFilter === 'all'
                  ? 'bg-brand-forest text-white shadow-xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
              }`}
            >
              Todos (48)
            </button>
            <button
              onClick={() => setStatusFilter('disponible')}
              className={`px-3.5 py-1.5 rounded-full transition cursor-pointer ${
                statusFilter === 'disponible'
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
              }`}
            >
              Disponibles (28)
            </button>
            <button
              onClick={() => setStatusFilter('reservado')}
              className={`px-3.5 py-1.5 rounded-full transition cursor-pointer ${
                statusFilter === 'reservado'
                  ? 'bg-amber-500 text-white shadow-xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
              }`}
            >
              Reservados (12)
            </button>
            <button
              onClick={() => setStatusFilter('vendido')}
              className={`px-3.5 py-1.5 rounded-full transition cursor-pointer ${
                statusFilter === 'vendido'
                  ? 'bg-slate-600 text-white shadow-xs'
                  : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
              }`}
            >
              Vendidos (8)
            </button>
          </div>

          {/* Metraje selector */}
          <div className="flex items-center gap-3 text-xs">
            <label htmlFor={metrajeSelectId} className="text-slate-500 uppercase tracking-wider font-semibold">Filtro metraje:</label>
            <select
              id={metrajeSelectId}
              value={metrajeFilter}
              onChange={(e) => setMetrajeFilter(e.target.value)}
              className="rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 py-1.5 px-3 bg-white focus:ring-brand-forest focus:border-brand-forest cursor-pointer"
            >
              <option value="all">Todos los tamaños</option>
              <option value="180-220">180 m² - 220 m²</option>
              <option value="220-300">220 m² - 300 m²</option>
              <option value="300+">Más de 300 m² (Esquina)</option>
            </select>
          </div>
        </div>

        {/* Interactive Map Layout & Detail Card */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          {/* Masterplan Visual Interactive Canvas (7 cols) */}
          <div className="lg:col-span-7 bg-emerald-950/5 rounded-3xl p-6 border border-emerald-900/10 shadow-inner relative">
            <div className="flex items-center justify-between text-xs font-bold text-slate-600 mb-4">
              <span>MANZANA B - FASE 1</span>
              <div className="flex items-center gap-3 sm:gap-4">
                <span className="flex items-center gap-1.5">
                  <span className="w-3 h-3 rounded-md bg-emerald-500" /> Disponible
                </span>
                <span className="flex items-center gap-1.5">
                  <span className="w-3 h-3 rounded-md bg-amber-400" /> Reservado
                </span>
                <span className="flex items-center gap-1.5">
                  <span className="w-3 h-3 rounded-md bg-slate-400" /> Vendido
                </span>
              </div>
            </div>

            {/* Lots Grid Map Representation */}
            <div className="grid grid-cols-6 gap-2 sm:gap-3 py-2">
              {/* Row 1: L-01 to L-06 */}
              {row1.map((lot) => {
                const isSelected = selectedLot.id === lot.id;
                const isMatch = filteredLots.some((f) => f.id === lot.id);
                return (
                  <div
                    key={lot.id}
                    onClick={() => onSelectLot(lot.id)}
                    className={`h-16 rounded-xl text-xs flex flex-col items-center justify-center border-2 transition-all ${getLotStatusStyle(
                      lot,
                      isSelected
                    )} ${!isMatch ? 'opacity-30' : ''}`}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') onSelectLot(lot.id);
                    }}
                  >
                    <span>{lot.number}</span>
                    <span className="text-[9px]">
                      {lot.status === 'vendido'
                        ? 'Vendido'
                        : lot.status === 'reservado'
                        ? 'Reservado'
                        : `${lot.areaM2} m²`}
                    </span>
                  </div>
                );
              })}

              {/* Main Boulevard (Road) */}
              <div className="col-span-6 py-2">
                <div className="h-6 rounded-lg bg-slate-200 border-dashed border-y border-slate-300 flex items-center justify-center text-[10px] uppercase tracking-widest text-slate-500 font-bold">
                  Bulevar Central de las Acacias (14.00 Metros)
                </div>
              </div>

              {/* Row 2: L-07 to L-12 */}
              {row2.map((lot) => {
                const isSelected = selectedLot.id === lot.id;
                const isMatch = filteredLots.some((f) => f.id === lot.id);
                return (
                  <div
                    key={lot.id}
                    onClick={() => onSelectLot(lot.id)}
                    className={`h-16 rounded-xl text-xs flex flex-col items-center justify-center border-2 transition-all ${getLotStatusStyle(
                      lot,
                      isSelected
                    )} ${!isMatch ? 'opacity-30' : ''}`}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') onSelectLot(lot.id);
                    }}
                  >
                    <span>{lot.number} {lot.featured ? '★' : ''}</span>
                    <span className={`text-[9px] ${isSelected ? 'text-emerald-300' : ''}`}>
                      {lot.status === 'vendido'
                        ? 'Vendido'
                        : lot.status === 'reservado'
                        ? 'Reservado'
                        : `${lot.areaM2} m²`}
                    </span>
                  </div>
                );
              })}
            </div>

            <p className="text-[11px] text-slate-500 text-center mt-3">
              Haz clic sobre cualquier lote verde para ver especificaciones técnicas y cuotas mensuales sugeridas.
            </p>
          </div>

          {/* Lot Details Card (Right 5 cols) */}
          <div className="lg:col-span-5">
            <div className="glass-card rounded-3xl p-6 sm:p-8 shadow-xl border border-white relative overflow-hidden">
              {/* Top Badge */}
              <div className="flex items-center justify-between mb-4">
                <span
                  className={`px-3 py-1 rounded-full text-xs font-bold border ${
                    selectedLot.status === 'disponible'
                      ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
                      : selectedLot.status === 'reservado'
                      ? 'bg-amber-100 text-amber-800 border-amber-200'
                      : 'bg-slate-100 text-slate-700 border-slate-200'
                  }`}
                >
                  ● Lote Seleccionado: {selectedLot.number}
                </span>
                <span className="text-xs text-slate-500 font-semibold">{selectedLot.manzana}</span>
              </div>

              <h3 className="text-2xl font-extrabold text-slate-900 mb-1">
                Terreno Residencial Premium
              </h3>
              <p className="text-xs text-slate-500 mb-6">
                Ubicación preferencial frente a área verde y sin vecino posterior directo.
              </p>

              {/* Technical Specs List */}
              <div className="space-y-3.5 border-y border-slate-200/80 py-4 mb-6 text-sm">
                <div className="flex justify-between items-center">
                  <span className="text-slate-500">Área total:</span>
                  <span className="font-bold text-slate-800">
                    {selectedLot.areaM2}.00 m² ({selectedLot.dimensions})
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-500">Topografía:</span>
                  <span className="font-bold text-slate-800">{selectedLot.topography}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-500">Servicios incluidos:</span>
                  <span className="font-bold text-slate-800">{selectedLot.services}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-500">Precio especial de preventa:</span>
                  <span className="font-extrabold text-brand-forest text-lg">
                    Q {selectedLot.priceQ.toLocaleString()}
                    <span className="text-xs text-slate-500 font-normal ml-1">
                      (${selectedLot.priceUSD.toLocaleString()} USD)
                    </span>
                  </span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-slate-500">Enganche fraccionado desde:</span>
                  <span className="font-bold text-slate-800">
                    Q {selectedLot.minDownPaymentQ.toLocaleString()} (10%)
                  </span>
                </div>
              </div>

              {/* Toggle Financing Calculator */}
              <div className="mb-6">
                <button
                  onClick={() => setShowFinancingCalc(!showFinancingCalc)}
                  className="w-full flex items-center justify-between p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100/70 text-brand-forest text-xs font-bold transition cursor-pointer border border-emerald-200/60"
                >
                  <div className="flex items-center gap-2">
                    <Calculator className="w-4 h-4" />
                    <span>Simulador de Cuotas Mensuales</span>
                  </div>
                  <span>{showFinancingCalc ? 'Ocultar ▲' : 'Calcular ▼'}</span>
                </button>

                {showFinancingCalc && (
                  <div className="mt-3 p-4 bg-white/90 rounded-2xl border border-slate-200/80 space-y-3 text-xs animate-fadeIn">
                    <div className="flex items-center justify-between">
                      <span className="text-slate-600 font-medium">Enganche:</span>
                      <div className="flex gap-2">
                        {[10, 20, 30].map((pct) => (
                          <button
                            key={pct}
                            onClick={() => setDownPaymentPercent(pct)}
                            className={`px-2.5 py-1 rounded-lg font-bold transition cursor-pointer ${
                              downPaymentPercent === pct
                                ? 'bg-brand-forest text-white'
                                : 'bg-slate-100 text-slate-600'
                            }`}
                          >
                            {pct}%
                          </button>
                        ))}
                      </div>
                    </div>

                    <div className="flex items-center justify-between">
                      <span className="text-slate-600 font-medium">Plazo financiamiento:</span>
                      <div className="flex gap-2">
                        {[3, 5, 8, 10].map((yrs) => (
                          <button
                            key={yrs}
                            onClick={() => setTermYears(yrs)}
                            className={`px-2.5 py-1 rounded-lg font-bold transition cursor-pointer ${
                              termYears === yrs
                                ? 'bg-brand-forest text-white'
                                : 'bg-slate-100 text-slate-600'
                            }`}
                          >
                            {yrs} años
                          </button>
                        ))}
                      </div>
                    </div>

                    <div className="pt-2 border-t border-slate-100 flex items-center justify-between bg-emerald-50/70 p-2.5 rounded-xl">
                      <div>
                        <p className="text-[10px] text-slate-500 uppercase tracking-wider">
                          Cuota mensual estimada
                        </p>
                        <p className="text-sm font-extrabold text-brand-forest">
                          Q {monthlyPaymentQ.toLocaleString()} / mes
                        </p>
                      </div>
                      <span className="text-xs font-semibold text-slate-500">
                        ~${monthlyPaymentUSD} USD
                      </span>
                    </div>
                  </div>
                )}
              </div>

              {/* CTA for this lot */}
              <div className="space-y-3">
                {selectedLot.status === 'disponible' ? (
                  <button
                    onClick={() => onOpenReservation(selectedLot)}
                    className="w-full bg-brand-forest hover:bg-brand-forest-dark text-white py-3.5 px-6 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg transition active:scale-95 cursor-pointer"
                  >
                    <MessageCircle className="w-5 h-5 fill-current" />
                    <span>Me interesa reservar este lote</span>
                  </button>
                ) : (
                  <button
                    disabled
                    className="w-full bg-slate-300 text-slate-500 py-3.5 px-6 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 cursor-not-allowed"
                  >
                    <CheckCircle2 className="w-5 h-5" />
                    <span>
                      {selectedLot.status === 'reservado'
                        ? 'Lote en proceso de reserva'
                        : 'Lote no disponible'}
                    </span>
                  </button>
                )}
                <p className="text-[11px] text-slate-500 text-center">
                  Reserva en línea hoy mismo con solo Q 1,000 reembolsables.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
