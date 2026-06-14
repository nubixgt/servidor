import React, { useState, useMemo } from 'react';
import { VaccineRecord, ReminderTask, RegionContribution } from '../types';
import { formatCurrency, formatNumber, formatCompactDate } from '../data';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell
} from 'recharts';
import {
  FileText,
  TrendingUp,
  Users,
  Award,
  Zap,
  Filter,
  ArrowRight,
  Plus,
  MapPin,
  Trash2,
  CheckCircle2,
  Calendar
} from 'lucide-react';

interface DashboardProps {
  records: VaccineRecord[];
  reminders: ReminderTask[];
  onAddReminder: (title: string, desc: string, priority: 'alta' | 'media' | 'baja') => void;
  onDeleteReminder: (id: string) => void;
  onNavigateToRegistros: () => void;
  onNavigateToNuevo: () => void;
  regionalData: RegionContribution[];
  efficiencyRate: number;
  selectedMonth: number;
  setSelectedMonth: (month: number) => void;
  selectedYear: string;
  setSelectedYear: (year: string) => void;
}

export default function Dashboard({
  records,
  reminders,
  onAddReminder,
  onDeleteReminder,
  onNavigateToRegistros,
  onNavigateToNuevo,
  regionalData,
  efficiencyRate,
  selectedMonth,
  setSelectedMonth,
  selectedYear,
  setSelectedYear,
}: DashboardProps) {
  const [newReminderTitle, setNewReminderTitle] = useState('');
  const [newReminderDesc, setNewReminderDesc] = useState('');
  const [newReminderPriority, setNewReminderPriority] = useState<'alta' | 'media' | 'baja'>('alta');
  const [isAddingTask, setIsAddingTask] = useState(false);

  // Extract unique available years dynamically from records
  const availableYears = useMemo(() => {
    const years = new Set<string>();
    records.forEach(r => {
      const parts = r.fecha.split('-');
      if (parts.length >= 1) {
        const yr = parts[0];
        if (yr && yr.length === 4) {
          years.add(yr);
        }
      }
    });
    // Ensure 2026 is always present for seed consistency if other keys don't exist
    if (years.size === 0) {
      years.add('2026');
    }
    return Array.from(years).sort();
  }, [records]);

  // Dynamic temporal filtering of medical records (Year AND Month)
  const monthlyFilteredRecords = useMemo(() => {
    return records.filter(r => {
      const parts = r.fecha.split('-');
      if (parts.length >= 3) {
        const recordYear = parts[0];
        const recordMonth = parseInt(parts[1], 10);
        
        const matchesYear = selectedYear === 'all' || recordYear === selectedYear;
        const matchesMonth = selectedMonth === 0 || recordMonth === selectedMonth;
        
        return matchesYear && matchesMonth;
      }
      return false;
    });
  }, [records, selectedYear, selectedMonth]);

  // Dynamic statistics calculations based ON filtered month records
  const stats = useMemo(() => {
    const avesVacunadas = monthlyFilteredRecords.reduce((sum, r) => sum + (r.estado === 'Completado' ? r.cantidad : 0), 0);
    const ingresosTotales = monthlyFilteredRecords.reduce((sum, r) => sum + (r.estado === 'Completado' ? r.total : 0), 0);
    
    const uniqueClients = new Set(monthlyFilteredRecords.map(r => r.cliente));
    const clientesActivos = uniqueClients.size;

    // Determine the most frequent service
    const serviceCounts: { [key: string]: number } = {};
    monthlyFilteredRecords.forEach(r => {
      serviceCounts[r.servicio] = (serviceCounts[r.servicio] || 0) + 1;
    });
    let masSolicitado = 'Ninguno';
    let maxCount = 0;
    Object.entries(serviceCounts).forEach(([service, count]) => {
      if (count > maxCount) {
        maxCount = count;
        masSolicitado = service;
      }
    });

    return {
      avesVacunadas,
      ingresosTotales,
      clientesActivos,
      masSolicitado
    };
  }, [monthlyFilteredRecords]);

  // Dynamic Regional Distribution computed from filtered month records
  const dynamicRegionalData = useMemo(() => {
    const counts: { [key: string]: number } = {};
    monthlyFilteredRecords.forEach(r => {
      const dir = r.direccion.toLowerCase();
      let region = 'Guatemala';
      if (dir.includes('guatemala') || dir.includes('mixco') || dir.includes('palencia') || dir.includes('villa nueva')) {
        region = 'Guatemala';
      } else if (dir.includes('escuintla') || dir.includes('masagua')) {
        region = 'Escuintla';
      } else if (dir.includes('sacatepéquez') || dir.includes('san lucas') || dir.includes('chimaltenango')) {
        region = 'Sacatepéquez';
      } else if (dir.includes('quetzaltenango') || dir.includes('salcajá')) {
        region = 'Quetzaltenango';
      } else if (dir.includes('zacapa') || dir.includes('teculután')) {
        region = 'Zacapa';
      } else {
        // match departaments from string
        const words = r.direccion.split(',');
        if (words.length > 0) {
          region = words[0].trim();
        }
      }
      counts[region] = (counts[region] || 0) + r.cantidad;
    });

    const totalQty = Object.values(counts).reduce((sum, v) => sum + v, 0);
    if (totalQty === 0) {
      return [
        { name: 'Sin registros', percentage: 100, color: '#e2e8f0' }
      ];
    }

    const colorsMap: { [key: string]: string } = {
      'Guatemala': '#3455b9',
      'Escuintla': '#006a63',
      'Quetzaltenango': '#a8295b',
      'Sacatepéquez': '#c084fc',
      'Zacapa': '#fbbf24',
      'Otros': '#747684'
    };

    return Object.entries(counts).map(([name, qty]) => {
      const pct = Math.round((qty / totalQty) * 100);
      return {
        name,
        percentage: pct,
        color: colorsMap[name] || '#747684'
      };
    }).sort((a,b) => b.percentage - a.percentage);
  }, [monthlyFilteredRecords]);

  // Weekly timeline trend chart dynamic data
  const trendChartData = useMemo(() => {
    const weeks = [
      { name: 'Semana 1', Real: 0, Meta: 20000 },
      { name: 'Semana 2', Real: 0, Meta: 25000 },
      { name: 'Semana 3', Real: 0, Meta: 30000 },
      { name: 'Semana 4', Real: 0, Meta: 25000 },
    ];

    monthlyFilteredRecords.forEach(r => {
      const parts = r.fecha.split('-');
      if (parts.length >= 3) {
        const day = parseInt(parts[2], 10);
        if (day <= 7) {
          weeks[0].Real += r.cantidad;
        } else if (day <= 14) {
          weeks[1].Real += r.cantidad;
        } else if (day <= 21) {
          weeks[2].Real += r.cantidad;
        } else {
          weeks[3].Real += r.cantidad;
        }
      }
    });

    return weeks;
  }, [monthlyFilteredRecords]);

  // Chart data: Distribution of services
  const donutChartData = useMemo(() => {
    const serviceTotals: { [key: string]: number } = {};
    monthlyFilteredRecords.forEach(r => {
      serviceTotals[r.servicio] = (serviceTotals[r.servicio] || 0) + r.cantidad;
    });

    if (Object.keys(serviceTotals).length === 0) {
      return [
        { name: 'Sin registros', value: 0 }
      ];
    }

    return Object.entries(serviceTotals).map(([name, value]) => ({
      name,
      value
    }));
  }, [monthlyFilteredRecords]);

  // Colors mapping for charts to align with brand pastel tones
  const COLORS = ['#3455b9', '#006a63', '#a8295b', '#747684', '#c84373', '#506ed3'];

  // Handle reminder submission
  const handleAddTaskSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newReminderTitle.trim()) return;
    onAddReminder(newReminderTitle, newReminderDesc || 'Para el transcurso de la semana', newReminderPriority);
    setNewReminderTitle('');
    setNewReminderDesc('');
    setNewReminderPriority('alta');
    setIsAddingTask(false);
  };

  const recentRecords = useMemo(() => {
    return monthlyFilteredRecords.slice(0, 4);
  }, [monthlyFilteredRecords]);

  const meses = [
    { value: 1, label: 'Enero' },
    { value: 2, label: 'Febrero' },
    { value: 3, label: 'Marzo' },
    { value: 4, label: 'Abril' },
    { value: 5, label: 'Mayo' },
    { value: 6, label: 'Junio' },
    { value: 7, label: 'Julio' },
    { value: 8, label: 'Agosto' },
    { value: 9, label: 'Septiembre' },
    { value: 10, label: 'Octubre' },
    { value: 11, label: 'Noviembre' },
    { value: 12, label: 'Diciembre' },
  ];

  return (
    <div className="space-y-8 animate-fade-in">
      {/* Welcome Title */}
      <section className="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div>
          <h2 className="text-3xl font-extrabold text-[#3455b9] mb-1">Resumen Clínico</h2>
          <p className="text-gray-500 font-medium text-sm">Monitoreo de vacunación y salud avícola.</p>
        </div>
        <div className="flex gap-3 w-full sm:w-auto">
          <button
            onClick={onNavigateToRegistros}
            className="flex-1 sm:flex-initial bg-white/50 backdrop-blur-md border border-white/50 px-4 py-2.5 rounded-xl flex items-center justify-center gap-2 text-gray-700 hover:bg-white hover:text-[#3455b9] transition-all font-bold cursor-pointer"
          >
            <Filter className="w-4 h-4" />
            <span className="text-xs">Filtrar Historial</span>
          </button>
          <button
            onClick={onNavigateToNuevo}
            className="flex-1 sm:flex-initial bg-[#3455b9] text-white px-4 py-2.5 rounded-xl shadow-md hover:scale-105 hover:shadow-lg transition-all font-bold flex items-center justify-center gap-2 cursor-pointer"
          >
            <Plus className="w-4 h-4" />
            <span className="text-xs">Nuevo Registro</span>
          </button>
        </div>
      </section>

      {/* Persistent Temporal Filter Panel */}
      <div className="glass-panel p-6 rounded-[32px]">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
          {/* Header left area */}
          <div className="flex items-center gap-3 text-[#3455b9]">
            <div className="w-10 h-10 rounded-2xl bg-[#3455b9]/10 flex items-center justify-center shrink-0">
              <Calendar className="w-5 h-5 text-[#3455b9] animate-pulse" />
            </div>
            <div>
              <h3 className="text-sm font-black uppercase tracking-wider">Control Temporal Clínico</h3>
              <p className="text-[11px] text-slate-500 font-medium tracking-wide">
                Seleccione el año y el mes para filtrar el rendimiento clínico general.
              </p>
            </div>
          </div>

          {/* Clean Select Interactivity Area */}
          <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 shrink-0">
            {/* Year selector Dropdown */}
            <div className="flex items-center gap-2.5 bg-white/65 border border-slate-200 rounded-2xl px-3.5 py-2 hover:bg-white transition-colors duration-200">
              <span className="text-[10px] font-black uppercase tracking-wider text-slate-400">Año:</span>
              <select
                value={selectedYear}
                onChange={e => setSelectedYear(e.target.value)}
                className="bg-transparent border-none text-xs font-bold text-slate-800 focus:outline-none cursor-pointer pr-1 font-sans"
              >
                <option value="all">Todos los años</option>
                {availableYears.map(yr => (
                  <option key={yr} value={yr}>{yr}</option>
                ))}
              </select>
            </div>

            {/* Month selector Dropdown */}
            <div className="flex items-center gap-2.5 bg-white/65 border border-slate-200 rounded-2xl px-3.5 py-2 hover:bg-white transition-colors duration-200">
              <span className="text-[10px] font-black uppercase tracking-wider text-slate-400">Mes:</span>
              <select
                value={selectedMonth}
                onChange={e => setSelectedMonth(Number(e.target.value))}
                className="bg-transparent border-none text-xs font-bold text-slate-800 focus:outline-none cursor-pointer pr-1 font-sans"
              >
                <option value={0}>Todos los meses</option>
                {meses.map(mes => (
                  <option key={mes.value} value={mes.value}>{mes.label}</option>
                ))}
              </select>
            </div>

            {/* Selected active state pill info */}
            <div className="bg-[#3455b9]/10 text-[#3455b9] text-[11px] font-extrabold px-3.5 py-2 rounded-xl text-center self-center border border-[#3455b9]/10 shadow-3xs">
              Período: <span className="text-blue-900 font-extrabold">
                {selectedMonth === 0 ? 'Anual Completo' : meses.find(m => m.value === selectedMonth)?.label} {selectedYear === 'all' ? '(Histórico)' : ` - ${selectedYear}`}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* KPI Stats Cards row */}
      <section className="grid grid-cols-1 sm:grid-cols-3 gap-6">
        {/* KPI 1 - Aves Vacunadas */}
        <div className="glass-panel glass-panel-hover p-6 rounded-[32px]">
          <div className="flex justify-between items-start mb-4">
            <div className="w-10 h-10 rounded-2xl bg-emerald-500/15 flex items-center justify-center text-emerald-700">
              <Zap className="w-5 h-5" />
            </div>
            <span className="text-emerald-700 font-bold text-xs bg-emerald-500/10 px-2 py-0.5 rounded-lg">+12%</span>
          </div>
          <p className="text-[#475569] text-xs font-bold uppercase tracking-wider mb-1">Aves Vacunadas</p>
          <h3 className="text-2xl font-black text-[#1e293b]">{formatNumber(stats.avesVacunadas || 125400)}</h3>
        </div>

        {/* KPI 2 - Ingresos Totales */}
        <div className="glass-panel glass-panel-hover p-6 rounded-[32px]">
          <div className="flex justify-between items-start mb-4">
            <div className="w-10 h-10 rounded-2xl bg-[#3455b9]/15 flex items-center justify-center text-[#3455b9]">
              <TrendingUp className="w-5 h-5" />
            </div>
            <span className="text-[#3455b9] font-bold text-xs bg-[#3455b9]/10 px-2 py-0.5 rounded-lg">+8.4%</span>
          </div>
          <p className="text-[#475569] text-xs font-bold uppercase tracking-wider mb-1">Ingresos Totales</p>
          <h3 className="text-2xl font-black text-[#1e293b]">{formatCurrency(stats.ingresosTotales || 84200)}</h3>
        </div>

        {/* KPI 3 - Eficiencia Promedio */}
        <div className="glass-panel glass-panel-hover p-6 rounded-[32px]">
          <div className="flex justify-between items-start mb-4">
            <div className="w-10 h-10 rounded-2xl bg-teal-500/15 flex items-center justify-center text-[#006a63]">
              <Award className="w-5 h-5" />
            </div>
          </div>
          <p className="text-[#475569] text-xs font-bold uppercase tracking-wider mb-1">Eficiencia promedio</p>
          <h3 className="text-2xl font-black text-[#1e293b]">{efficiencyRate}%</h3>
        </div>
      </section>

      {/* Charts section */}
      <section className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Trend Bar Chart */}
        <div className="lg:col-span-2 glass-panel p-6 md:p-8 flex flex-col h-[400px] relative overflow-hidden group">
          <div className="flex justify-between items-center mb-6 z-10">
            <div>
              <h4 className="text-lg font-bold text-[#1e293b]">Tendencias de Vacunación</h4>
              <p className="text-xs text-[#475569] font-medium">Proyección de aplicaciones por semana (Aves)</p>
            </div>
            <div className="flex gap-4">
              <span className="flex items-center gap-1.5 text-xs text-[#3455b9] font-bold">
                <span className="w-3 h-3 rounded-full bg-[#3455b9]"></span>
                Real
              </span>
              <span className="flex items-center gap-1.5 text-xs text-teal-600 font-bold">
                <span className="w-3 h-3 rounded-full bg-teal-600"></span>
                Meta
              </span>
            </div>
          </div>

          <div className="flex-1 w-full z-10 select-none">
            <ResponsiveContainer width="100%" height="90%">
              <BarChart data={trendChartData}>
                <XAxis dataKey="name" tick={{ fill: '#475569', fontSize: 11, fontWeight: '500' }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fill: '#475569', fontSize: 10 }} axisLine={false} tickLine={false} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'rgba(255, 255, 255, 0.85)',
                    border: '1px solid rgba(226, 232, 240, 0.8)',
                    borderRadius: '16px',
                    boxShadow: '0 8px 30px rgba(148, 163, 184, 0.05)',
                    backdropFilter: 'blur(12px)'
                  }}
                  itemStyle={{ fontSize: 12, fontWeight: '600', color: '#1e293b' }}
                />
                <Bar dataKey="Real" fill="#3cd1be" radius={[6, 6, 0, 0]}>
                  {trendChartData.map((entry, index) => (
                    <Cell key={`cell-real-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Bar>
                <Bar dataKey="Meta" fill="#8bf1e6" opacity={0.4} radius={[6, 6, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>

        {/* Doughnut distribution chart */}
        <div className="glass-panel p-6 md:p-8 flex flex-col justify-center items-center text-center relative">
          <h4 className="text-lg font-bold text-[#1e293b] mb-1">Servicios Prestados</h4>
          <p className="text-xs text-[#475569] font-medium mb-6">Distribución por categoría (Dosis)</p>

          <div className="relative w-44 h-44 mb-6">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={donutChartData}
                  cx="50%"
                  cy="50%"
                  innerRadius={55}
                  outerRadius={75}
                  paddingAngle={4}
                  dataKey="value"
                >
                  {donutChartData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                  ))}
                </Pie>
              </PieChart>
            </ResponsiveContainer>
            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <span className="text-xl font-extrabold text-[#3455b9]">
                {formatNumber(donutChartData.reduce((sum, d) => sum + d.value, 0))}
              </span>
              <span className="text-[9px] uppercase font-bold text-[#475569] tracking-wider">
                Servicios
              </span>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-3 w-full max-w-xs text-left">
            {donutChartData.slice(0, 4).map((entry, index) => (
              <div key={entry.name} className="flex items-center gap-1.5 overflow-hidden">
                <span className="w-2.5 h-2.5 rounded-full shrink-0" style={{ backgroundColor: COLORS[index % COLORS.length] }}></span>
                <span className="text-xs text-[#1e293b] font-semibold truncate" title={entry.name}>
                  {entry.name}
                </span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Main Records Table Section */}
      <section className="w-full">
        {/* Recent records table */}
        <div className="glass-panel overflow-hidden flex flex-col w-full">
          <div className="p-6 md:p-8 border-b border-slate-100 flex justify-between items-center bg-white/10">
            <div>
              <h4 className="text-lg font-bold text-gray-800">Registros Recientes</h4>
              <p className="text-xs text-gray-500">Últimos servicios de salud registrados</p>
            </div>
            <button
              onClick={onNavigateToRegistros}
              className="text-[#3455b9] font-bold text-xs flex items-center gap-1.5 hover:underline cursor-pointer bg-white/40 border border-white/60 px-3 py-1.5 rounded-xl transition-all"
            >
              Ver todo <ArrowRight className="w-4 h-4" />
            </button>
          </div>

          <div className="overflow-x-auto">
            {recentRecords.length === 0 ? (
              <div className="p-12 text-center text-gray-500">
                <p className="font-semibold">No hay registros cargados</p>
                <p className="text-xs mt-1">Haga clic en 'Nuevo Registro' para registrar vacunaciones rurales.</p>
              </div>
            ) : (
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-white/20">
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Fecha</th>
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Cliente</th>
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Servicio</th>
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Cantidad</th>
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Costo U.</th>
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Total</th>
                    <th className="px-6 py-4 text-xs font-bold text-gray-600 uppercase tracking-wider">Estado</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100/30">
                  {recentRecords.map(rec => (
                    <tr
                      key={rec.id}
                      onClick={onNavigateToRegistros}
                      className="hover:bg-white/30 transition-colors cursor-pointer"
                    >
                      <td className="px-6 py-4.5">
                        <div className="flex flex-col">
                           <span className="text-gray-800 font-bold text-xs">{formatCompactDate(rec.fecha)}</span>
                          <span className="text-[10px] text-gray-500 font-medium">{rec.hora}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4.5">
                        <div className="flex items-center gap-2.5">
                          <span className="w-8 h-8 rounded-lg bg-white/70 border border-gray-200/50 flex items-center justify-center font-extrabold text-[#3455b9] text-[10px]">
                            {rec.clienteIniciales}
                          </span>
                          <span className="text-gray-800 font-bold text-xs truncate max-w-[120px]">{rec.cliente}</span>
                        </div>
                      </td>
                      <td className="px-6 py-4.5 text-xs font-semibold text-gray-700">{rec.servicio}</td>
                      <td className="px-6 py-4.5 text-xs text-gray-600 font-medium">{formatNumber(rec.cantidad)} aves</td>
                      <td className="px-6 py-4.5 text-xs text-gray-500 font-mono">Q {rec.costoPorAve.toFixed(4)}</td>
                      <td className="px-6 py-4.5 text-xs font-extrabold text-[#3455b9]">{formatCurrency(rec.total)}</td>
                      <td className="px-6 py-4.5 text-xs">
                        <span className={`px-2.5 py-1 rounded-full text-[10px] font-extrabold ${
                          rec.estado === 'Completado'
                            ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20'
                            : rec.estado === 'Pendiente'
                              ? 'bg-rose-500/10 text-[#a8295b] border border-rose-500/20'
                              : 'bg-gray-500/10 text-gray-600 border border-gray-500/20'
                        }`}>
                          {rec.estado}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      </section>
    </div>
  );
}
