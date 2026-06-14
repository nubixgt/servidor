import React, { useState } from 'react';
import { BookOpen, ShieldCheck, HelpCircle, ArrowRight, Layers, Sparkles, Scale, Info } from 'lucide-react';
import { formatCurrency, formatNumber } from '../data';

interface ServiceItem {
  name: string;
  price: number;
  category: 'Vacunación' | 'Manejo Físico' | 'Tratamiento Grupal';
  description: string;
  technique: string;
  recommendedAge: string;
  guidelines: string;
  color: string;
}

export default function Catalogo() {
  const [selectedServiceForSimulation, setSelectedServiceForSimulation] = useState<string>('Doble Pechuga');
  const [simulationQty, setSimulationQty] = useState<number>(10000);

  const services: ServiceItem[] = [
    {
      name: 'Doble Pechuga',
      price: 0.0070,
      category: 'Vacunación',
      description: 'Inoculación pectoral avanzada orientada a la estimulación inmunitaria y protección del tejido muscular grueso.',
      technique: 'Intramuscular en músculo pectoral profundo con jeringa neumática calibrada.',
      recommendedAge: '4 a 6 semanas de vida.',
      guidelines: 'Inyección en un ángulo de 45° evitando impactar el esternón. Asegura óptima absorción biológica.',
      color: 'from-blue-500/20 to-indigo-500/10 border-blue-200'
    },
    {
      name: 'Vacuna Cuello',
      price: 0.0082,
      category: 'Vacunación',
      description: 'Inmunización profiláctica general subcutánea en la línea media posterior del cuello.',
      technique: 'Subcutánea en el tercio medio de la parte posterior del cuello dirigiendo la aguja hacia abajo.',
      recommendedAge: '1 a 10 días de vida.',
      guidelines: 'Evitar inyectar cerca de la cabeza o vasos sanguíneos de la médula. Limpieza aséptica continua.',
      color: 'from-emerald-500/20 to-teal-500/10 border-emerald-200'
    },
    {
      name: 'Despique',
      price: 0.0092,
      category: 'Manejo Físico',
      description: 'Recorte terapéutico del pico infantil para prevenir estrés caníbal, picoteo y desperdicio excesivo de concentrados.',
      technique: 'Cuchilla térmica de precisión a temperatura controlada de 650°C.',
      recommendedAge: '7 a 10 días.',
      guidelines: 'Asegurar cauterización completa durante 2 segundos para evitar hemorragias posteriores e infecciones.',
      color: 'from-pink-500/20 to-rose-500/10 border-pink-200'
    },
    {
      name: 'Re-Despique',
      price: 0.0101,
      category: 'Manejo Físico',
      description: 'Procedimiento secundario correctivo en pollonas ponedoras en crecimiento para homogeneidad terminal.',
      technique: 'Cauterización y rectificación angular guiada por placa metálica aperturada.',
      recommendedAge: '10 a 12 semanas.',
      guidelines: 'Únicamente necesario si se detecta sobrecrecimiento desigual o deformidades de pico pos-inicial.',
      color: 'from-amber-500/20 to-orange-500/10 border-amber-200'
    },
    {
      name: 'Vacuna Pechuga',
      price: 0.0070,
      category: 'Vacunación',
      description: 'Protección clásica contra Coriza Infecciosa y Síndrome de Baja Postura.',
      technique: 'Intramuscular superficial en el pecho izquierdo o derecho.',
      recommendedAge: '12 a 16 semanas.',
      guidelines: 'Alternar los lados de la pechuga en vacunaciones consecutivas. No administrar vacunas frías.',
      color: 'from-purple-500/20 to-fuchsia-500/10 border-purple-200'
    },
    {
      name: 'Aerosol',
      price: 0.0012,
      category: 'Tratamiento Grupal',
      description: 'Suministro masivo respiratorio por gota fina para la inmunidad rápida de mucosas naso-oculares.',
      technique: 'Nebulización ultrasónica controlada sobre los lotes de cría confinada.',
      recommendedAge: 'Cualquier edad (Principalmente pollitos de 1 día).',
      guidelines: 'Apagar extractores durante la aspersión y reactivarlos 15 minutos después. Luz tenue para calmar las aves.',
      color: 'from-sky-500/20 to-cyan-500/10 border-sky-200'
    }
  ];

  const currentSimulationPrice = services.find(s => s.name === selectedServiceForSimulation)?.price || 0.0070;
  const currentSimulationTotal = simulationQty * currentSimulationPrice;

  return (
    <div className="space-y-8 animate-fade-in pb-10">
      
      {/* Back glow background */}
      <div className="absolute top-[30%] left-[5%] w-[350px] h-[350px] bg-pink-100/30 blur-[130px] rounded-full pointer-events-none -z-10"></div>

      {/* Hero Header Section */}
      <section className="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
          <h2 className="text-3xl font-extrabold text-[#3455b9] mb-1">Catálogo de Procedimientos Clínicos</h2>
          <p className="text-[#5c4a3c] font-bold text-sm">Fichas técnicas de sanidad y costes normalizados establecidos por ave.</p>
        </div>
        <div className="flex items-center gap-1.5 bg-white/60 border border-white/50 px-4.5 py-2.5 rounded-2xl shadow-3xs text-xs font-bold text-[#2e2620]">
          <BookOpen className="w-4.5 h-4.5 text-[#3455b9]" />
          <span>6 Servicios Homologados</span>
        </div>
      </section>

      {/* Grid of service cards */}
      <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {services.map(srv => (
          <div
            key={srv.name}
            className={`glass-panel border-2 ${srv.color} p-6 flex flex-col justify-between hover:scale-[1.01] hover:shadow-lg transition-all duration-300 relative group overflow-hidden`}
          >
            {/* Sparkle decorative effect */}
            <div className="absolute right-3 top-3 opacity-0 group-hover:opacity-100 transition-opacity">
              <Sparkles className="w-5 h-5 text-[#3455b9]/40" />
            </div>

            <div>
              <div className="flex justify-between items-start mb-3">
                <span className="text-[10px] font-extrabold px-3 py-1 rounded-full bg-white border border-gray-200/50 text-[#5c4a3c] uppercase tracking-widest">
                  {srv.category}
                </span>
                <div className="text-right">
                  <p className="text-[10px] text-[#5c4a3c] font-extrabold uppercase tracking-wider">Tasa de Convenio</p>
                  <p className="text-base font-black text-[#3455b9]">Q {srv.price.toFixed(4)}</p>
                </div>
              </div>

              <h3 className="text-lg font-black text-[#2e2620] mb-2">{srv.name}</h3>
              <p className="text-xs text-gray-700 font-medium leading-relaxed mb-4">{srv.description}</p>

              <div className="space-y-2 border-t border-gray-100/50 pt-4 text-[11px]">
                <div className="flex gap-2">
                  <span className="font-extrabold text-[#5c4a3c] uppercase shrink-0 w-24">Técnica:</span>
                  <span className="text-[#2e2620] font-medium">{srv.technique}</span>
                </div>
                <div className="flex gap-2">
                  <span className="font-extrabold text-[#5c4a3c] uppercase shrink-0 w-24">Edad Ideal:</span>
                  <span className="text-[#2e2620] font-extrabold">{srv.recommendedAge}</span>
                </div>
                <div className="flex gap-2">
                  <span className="font-extrabold text-[#5c4a3c] uppercase shrink-0 w-24">Directriz:</span>
                  <span className="text-gray-700 font-medium italic">{srv.guidelines}</span>
                </div>
              </div>
            </div>

            <div className="mt-6 pt-3 border-t border-gray-100/30 flex justify-end">
              <button
                onClick={() => setSelectedServiceForSimulation(srv.name)}
                className="text-xs text-[#3455b9] bg-white hover:bg-[#3455b9]/5 border border-[#3455b9]/20 px-3 py-1.5 rounded-xl font-extrabold flex items-center gap-1 transition-all cursor-pointer"
              >
                Simular Costo <ArrowRight className="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        ))}
      </section>

      {/* Simulation Dynamic Sandbox Area */}
      <section className="glass-panel p-6 md:p-8 shadow-md">
        <div className="flex items-center gap-2 mb-4">
          <Scale className="w-5 h-5 text-[#3455b9]" />
          <h4 className="text-lg font-extrabold text-[#2e2620]">Simulador de Presupuestos Veterinarios</h4>
        </div>
        <p className="text-xs text-[#5c4a3c] mb-6 font-bold">Configure los parámetros a continuación para simular la inversión clínica basada en las tarifas del catálogo oficial.</p>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
          
          <div className="space-y-1.5">
            <label className="text-xs font-black text-[#5c4a3c] uppercase tracking-widest">Procedimiento</label>
            <select
              value={selectedServiceForSimulation}
              onChange={e => setSelectedServiceForSimulation(e.target.value)}
              className="w-full bg-white/40 border border-[#3455b9]/15 rounded-xl px-3.5 py-2.5 text-sm font-bold text-[#2e2620] cursor-pointer"
            >
              {services.map(s => (
                <option key={s.name} value={s.name} className="text-gray-950 font-semibold">{s.name} (Q {s.price.toFixed(4)}/ave)</option>
              ))}
            </select>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-black text-[#5c4a3c] uppercase tracking-widest">Población (Aves a tratar)</label>
            <input
              type="number"
              min="100"
              step="100"
              value={simulationQty}
              onChange={e => setSimulationQty(Math.max(0, parseInt(e.target.value) || 0))}
              className="w-full bg-white/40 border border-[#3455b9]/15 rounded-xl px-3.5 py-2.5 text-sm font-black text-[#2e2620] focus:outline-none focus:bg-white/70"
            />
          </div>

          {/* Simulated result widget badge */}
          <div className="bg-[#3455b9]/5 border border-[#3455b9]/10 p-4.5 rounded-2xl flex flex-col justify-center items-center text-center">
            <span className="text-[10px] text-[#5c4a3c] font-bold uppercase tracking-widest mb-1">Presupuesto Estimado</span>
            <span className="text-2xl font-black text-[#3455b9]">
              {formatCurrency(currentSimulationTotal)}
            </span>
            <span className="text-[9px] text-[#3455b9]/80 font-bold mt-1">
              {formatNumber(simulationQty)} aves a Q {currentSimulationPrice.toFixed(4)} c/u
            </span>
          </div>

        </div>

        <div className="mt-4 flex gap-2 items-center p-3.5 bg-amber-50/20 border border-amber-200/40 rounded-2xl">
          <Info className="w-4.5 h-4.5 text-amber-700 shrink-0" />
          <p className="text-[11px] text-[#5c4a3c] font-bold">Note: Las simulaciones se realizan sobre costes nominales de referencia. El precio real se consolida y registra ingresando una nueva entrada en el historial clínico.</p>
        </div>
      </section>

    </div>
  );
}
