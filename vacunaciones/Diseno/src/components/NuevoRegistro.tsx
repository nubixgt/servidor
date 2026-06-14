import React, { useState, useEffect } from 'react';
import { VaccineRecord } from '../types';
import { getServiceUnitCost, SERVICIOS_PRESTADOS, formatCurrency, formatNumber } from '../data';
import { Save, Calendar, User, Building, MapPin, Sparkles, CheckCircle2 } from 'lucide-react';

interface NuevoRegistroProps {
  onAddRecord: (recordData: Omit<VaccineRecord, 'id' | 'hora' | 'clienteIniciales'>) => void;
  records: VaccineRecord[];
  efficiencyRate: number;
}

export default function NuevoRegistro({ onAddRecord, records, efficiencyRate }: NuevoRegistroProps) {
  const [fecha, setFecha] = useState(new Date().toISOString().split('T')[0]);
  const [vacunador, setVacunador] = useState('');
  const [cliente, setCliente] = useState('');
  const [direccion, setDireccion] = useState('');
  const [servicio, setServicio] = useState('');
  const [cantidad, setCantidad] = useState<number | ''>('');
  
  const [costoPorAve, setCostoPorAve] = useState(0);
  const [totalEstimado, setTotalEstimado] = useState(0);

  // Animation States for Submit button
  const [submitStatus, setSubmitStatus] = useState<'idle' | 'processing' | 'success'>('idle');

  // Trigger unit cost calculation on change
  useEffect(() => {
    if (!servicio) {
      setCostoPorAve(0);
      setTotalEstimado(0);
      return;
    }
    const qty = Number(cantidad) || 0;
    const unitCost = getServiceUnitCost(servicio, qty);
    setCostoPorAve(unitCost);
    setTotalEstimado(qty * unitCost);
  }, [servicio, cantidad]);

  // Submit Handler
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!servicio || !cantidad || !cliente || !vacunador || !direccion) {
      alert('Por favor complete todos los datos del servicio.');
      return;
    }

    setSubmitStatus('processing');

    setTimeout(() => {
      setSubmitStatus('success');

      // Actual state addition
      onAddRecord({
        fecha,
        vacunador,
        cliente,
        direccion,
        servicio,
        cantidad: Number(cantidad),
        costoPorAve,
        total: totalEstimado,
        estado: 'Completado'
      });

      // Turn off green indicator and reset
      setTimeout(() => {
        setSubmitStatus('idle');
        // Reset form fields
        setCliente('');
        setDireccion('');
        setServicio('');
        setCantidad('');
      }, 2000);

    }, 1200);
  };

  return (
    <div className="space-y-8 animate-fade-in relative max-w-4xl mx-auto pb-10">
      {/* Glow ambient items */}
      <div className="absolute -top-[10%] -right-[10%] w-[320px] h-[320px] bg-[#3455b9]/5 blur-[100px] rounded-full pointer-events-none"></div>
      
      {/* Form Title Heading */}
      <div>
        <h2 className="text-3xl font-extrabold text-[#3455b9] mb-1">Nueva Entrada de Vacunación</h2>
        <p className="text-[#475569] font-bold text-sm">Complete los detalles para registrar un nuevo servicio en el sistema.</p>
      </div>

      {/* Main Glassmorphic Form Card */}
      <div className="glass-panel p-6 md:p-10 shadow-xl">
        <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
          
          {/* Fecha input */}
          <div className="space-y-2">
            <label className="block text-xs font-black text-[#475569] uppercase tracking-wider">Fecha</label>
            <div className="relative group">
              <Calendar className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input
                type="date"
                required
                value={fecha}
                onChange={e => setFecha(e.target.value)}
                className="w-full bg-white/40 border border-white/50 rounded-2xl py-4 pl-12 pr-4 text-[#1e293b] font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
              />
            </div>
          </div>

          {/* Vacunador input */}
          <div className="space-y-2">
            <label className="block text-xs font-black text-[#475569] uppercase tracking-wider">Vacunador</label>
            <div className="relative group">
              <User className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input
                type="text"
                required
                placeholder="Nombre completo del veterinario"
                value={vacunador}
                onChange={e => setVacunador(e.target.value)}
                className="w-full bg-white/40 border border-white/50 rounded-2xl py-4 pl-12 pr-4 text-[#1e293b] placeholder-gray-500 font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
              />
            </div>
          </div>

          {/* Cliente input */}
          <div className="space-y-2 md:col-span-2">
            <label className="block text-xs font-black text-[#475569] uppercase tracking-wider">Cliente Visitado</label>
            <div className="relative group">
              <Building className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input
                type="text"
                required
                placeholder="Nombre de la granja avícola o propietario"
                value={cliente}
                onChange={e => setCliente(e.target.value)}
                className="w-full bg-white/40 border border-white/50 rounded-2xl py-4 pl-12 pr-4 text-[#1e293b] placeholder-gray-500 font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
              />
            </div>
          </div>

          {/* Direccion input */}
          <div className="space-y-2 md:col-span-2">
            <label className="block text-xs font-black text-[#475569] uppercase tracking-wider">Dirección de la Granja</label>
            <div className="relative group">
              <MapPin className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80 transition-colors group-focus-within:text-[#3455b9]" />
              <input
                type="text"
                required
                placeholder="Ubicación exacta del servicio o Departamento"
                value={direccion}
                onChange={e => setDireccion(e.target.value)}
                className="w-full bg-white/40 border border-white/50 rounded-2xl py-4 pl-12 pr-4 text-[#1e293b] placeholder-gray-500 font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
              />
            </div>
          </div>

          {/* Servicio prestado selection */}
          <div className="space-y-2">
            <label className="block text-xs font-black text-[#475569] uppercase tracking-wider">Servicio Prestado</label>
            <div className="relative group">
              <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
              </svg>
              <select
                required
                value={servicio}
                onChange={e => setServicio(e.target.value)}
                className="w-full bg-white/40 border border-white/50 rounded-2xl py-4 pl-12 pr-10 text-[#1e293b] font-bold focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all appearance-none cursor-pointer"
              >
                <option value="" disabled className="text-gray-700 font-bold">Seleccione un servicio</option>
                {SERVICIOS_PRESTADOS.map(serv => (
                  <option key={serv} value={serv} className="text-gray-900 font-semibold">{serv}</option>
                ))}
              </select>
              <div className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-700 pointer-events-none">
                <ChevronDown />
              </div>
            </div>
          </div>

          {/* Cantidad Ave */}
          <div className="space-y-2">
            <label className="block text-xs font-black text-[#475569] uppercase tracking-wider">Cantidad Ave (Unidades)</label>
            <div className="relative group">
              <svg className="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-[#3455b9]/80" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              <input
                type="number"
                required
                min="1"
                placeholder="Cantidad de aves a vacunar"
                value={cantidad}
                onChange={e => setCantidad(e.target.value === '' ? '' : Number(e.target.value))}
                className="w-full bg-white/40 border border-white/50 rounded-2xl py-4 pl-12 pr-4 text-[#1e293b] placeholder-gray-500 font-medium focus:outline-none focus:ring-4 focus:ring-[#3455b9]/10 focus:bg-white/60 transition-all"
              />
            </div>
          </div>

          {/* Calculated Wells */}
          <div className="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 p-6 mt-4 rounded-3xl bg-white/20 backdrop-blur-md border border-white/35 shadow-xs">
            <div className="space-y-2">
              <span className="block text-xs font-extrabold text-[#475569] uppercase tracking-wider">Costo por Ave (Q)</span>
              <div className="bg-[#3455b9]/15 text-[#3455b9] border border-[#3455b9]/20 px-6 py-4.5 rounded-2xl font-black text-center text-lg shadow-2xs">
                Q {costoPorAve.toFixed(4)}
              </div>
            </div>

            <div className="space-y-2">
              <span className="block text-xs font-extrabold text-[#475569] uppercase tracking-wider">Total Estimado del Servicio</span>
              <div className="bg-emerald-500/15 text-emerald-700 border border-emerald-500/20 px-6 py-4.5 rounded-2xl font-black text-center text-2xl shadow-2xs transition-all">
                {formatCurrency(totalEstimado)}
              </div>
            </div>
          </div>

          {/* Form Submit buttons */}
          <div className="md:col-span-2 mt-4">
            <button
              type="submit"
              disabled={submitStatus !== 'idle'}
              className={`w-full py-4.5 rounded-2xl font-bold text-lg flex items-center justify-center gap-2.5 transition-all shadow-md cursor-pointer ${
                submitStatus === 'idle'
                  ? 'bg-[#3455b9] text-white hover:opacity-95 hover:scale-[1.01]'
                  : submitStatus === 'processing'
                    ? 'bg-amber-500 text-white cursor-wait'
                    : 'bg-emerald-500 text-white cursor-default animate-bounce'
              }`}
            >
              {submitStatus === 'idle' && (
                <>
                  <Save className="w-5 h-5" />
                  <span>Guardar Registro</span>
                </>
              )}
              {submitStatus === 'processing' && (
                <>
                  <svg className="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  <span>Procesando...</span>
                </>
              )}
              {submitStatus === 'success' && (
                <>
                  <CheckCircle2 className="w-6 h-6" />
                  <span>Registro Guardado</span>
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

// Simple Helper Component
function ChevronDown() {
  return (
    <svg className="w-5 h-5 text-[#3455b9]" fill="none" stroke="currentColor" strokeWidth="2.5" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
    </svg>
  );
}
