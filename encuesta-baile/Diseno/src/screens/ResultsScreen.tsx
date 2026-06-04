import React from 'react';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, Cell, PieChart, Pie, Legend } from 'recharts';
import { VOTE_OPTIONS } from '../data';
import { BarChart3, Users, Clock, Award, ShieldAlert } from 'lucide-react';

interface ResultsScreenProps {
  votesA: number;
  votesB: number;
  userVote: string | null;
  onResetVote: () => void;
}

export default function ResultsScreen({
  votesA,
  votesB,
  userVote,
  onResetVote
}: ResultsScreenProps) {
  const total = votesA + votesB;
  const pctA = total > 0 ? Math.round((votesA / total) * 100) : 0;
  const pctB = total > 0 ? Math.round((votesB / total) * 100) : 0;

  // Chart data
  const chartData = [
    {
      name: 'Estadio (Opción A)',
      votos: votesA,
      porcentaje: pctA,
      color: '#091426',
    },
    {
      name: 'Salón + Concierto (B)',
      votos: votesB,
      porcentaje: pctB,
      color: '#4648d4',
    }
  ];

  // Gender/Age breakdowns
  const demographicData = [
    { name: 'Jóvenes (18-30)', 'Opción A (Estadio)': 65, 'Opción B (Salón)': 35 },
    { name: 'Adultos (31-50)', 'Opción A (Estadio)': 48, 'Opción B (Salón)': 52 },
    { name: 'Mayores (51+)', 'Opción A (Estadio)': 25, 'Opción B (Salón)': 75 },
  ];

  return (
    <div className="space-y-10 animate-fade-in">
      {/* Custom page title heading */}
      <div className="text-center space-y-2 mb-6">
        <h2 className="text-3xl font-extrabold text-[#091426] tracking-tight">
          Tablero de Escrutinio Abierto
        </h2>
        <p className="text-slate-500 text-sm max-w-xl mx-auto">
          Analiza la deliberación de la comunidad en tiempo real. Todas las respuestas son públicas y transparentes.
        </p>
      </div>

      <div className="bg-surface-container-lowest rounded-xl p-6 md:p-8 border border-slate-100 shadow-md">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 pb-6 border-b border-slate-100">
          <div>
            <h2 className="text-2xl font-bold text-primary-base flex items-center gap-2">
              <BarChart3 className="w-6 h-6 text-secondary-base" />
              Resultados del Sondeo en Tiempo Real
            </h2>
            <p className="text-xs md:text-sm text-on-surface-variant mt-1">
              Respuestas recopiladas y verificadas mediante nuestro portal seguro.
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <button
              onClick={onResetVote}
              className="px-4 py-2 bg-slate-100 text-primary-base rounded-lg text-xs font-semibold hover:bg-slate-200 transition-colors"
            >
              Modificar mi preferencia
            </button>
          </div>
        </div>

        {/* Metrics Summary Rows */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
          <div className="bg-slate-50 rounded-lg p-4 flex items-center gap-4 border border-slate-100">
            <div className="w-10 h-10 rounded-full bg-slate-900/5 flex items-center justify-center text-primary-base">
              <Users className="w-5 h-5 col" />
            </div>
            <div>
              <div className="text-2xl font-bold text-primary-base">{total}</div>
              <div className="text-xs text-on-surface-variant font-medium">Participaciones Totales</div>
            </div>
          </div>

          <div className="bg-slate-50 rounded-lg p-4 flex items-center gap-4 border border-slate-100">
            <div className="w-10 h-10 rounded-full bg-secondary-base/5 flex items-center justify-center text-secondary-base">
              <Award className="w-5 h-5 col" />
            </div>
            <div>
              <div className="text-2xl font-bold text-secondary-base">
                {votesA > votesB ? 'Opción A' : votesA < votesB ? 'Opción B' : 'Empate'}
              </div>
              <div className="text-xs text-on-surface-variant font-medium">Preferencia Líder</div>
            </div>
          </div>

          <div className="bg-slate-50 rounded-lg p-4 flex items-center gap-4 border border-slate-100">
            <div className="w-10 h-10 rounded-full bg-emerald-500/5 flex items-center justify-center text-emerald-600">
              <Clock className="w-5 h-5 col" />
            </div>
            <div>
              <div className="text-2xl font-bold text-emerald-600">Activo</div>
              <div className="text-xs text-on-surface-variant font-medium">Cierre: En 3 días</div>
            </div>
          </div>
        </div>

        {/* Core Votes Layout Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start mb-8">
          {/* Visual Percent Bar Meters */}
          <div className="space-y-6">
            <h3 className="text-base font-bold text-primary-base uppercase tracking-wider">
              Detalle por Propuestas
            </h3>

            {/* Option A Progress */}
            <div className={`p-4 rounded-lg border transition-all ${userVote === 'option-a' ? 'bg-indigo-50/20 border-indigo-100' : 'bg-transparent border-slate-100'}`}>
              <div className="flex justify-between items-center mb-2">
                <div className="flex items-center gap-1.5">
                  <span className="w-3 h-3 rounded-full bg-[#091426]" />
                  <span className="font-semibold text-primary-base text-sm md:text-base">Gran Baile en el Estadio</span>
                  {userVote === 'option-a' && (
                    <span className="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded">Tu Voto</span>
                  )}
                </div>
                <span className="font-black text-primary-base text-sm">{votesA} votos ({pctA}%)</span>
              </div>
              <div className="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
                <div
                  className="bg-[#091426] h-full rounded-full transition-all duration-1000"
                  style={{ width: `${pctA}%` }}
                />
              </div>
              <p className="text-xs text-on-surface-variant mt-2 italic">
                Convocatoria expansiva, ideal para toda la comunidad unificada.
              </p>
            </div>

            {/* Option B Progress */}
            <div className={`p-4 rounded-lg border transition-all ${userVote === 'option-b' ? 'bg-indigo-50/20 border-indigo-100' : 'bg-transparent border-slate-100'}`}>
              <div className="flex justify-between items-center mb-2">
                <div className="flex items-center gap-1.5">
                  <span className="w-3 h-3 rounded-full bg-[#4648d4]" />
                  <span className="font-semibold text-primary-base text-sm md:text-base">Baile en Salón + Concierto</span>
                  {userVote === 'option-b' && (
                    <span className="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-bold rounded">Tu Voto</span>
                  )}
                </div>
                <span className="font-black text-[#4648d4] text-sm">{votesB} votos ({pctB}%)</span>
              </div>
              <div className="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
                <div
                  className="bg-[#4648d4] h-full rounded-full transition-all duration-1000"
                  style={{ width: `${pctB}%` }}
                />
              </div>
              <p className="text-xs text-on-surface-variant mt-2 italic">
                Ambiente íntimo con acústica distinguida y mayor interacción.
              </p>
            </div>

            {/* Institutional note */}
            <div className="p-3 bg-slate-50 rounded-lg text-xs text-on-surface-variant flex items-start gap-2.5 border border-slate-100">
              <ShieldAlert className="w-4 h-4 text-slate-500 shrink-0 mt-0.5" />
              <span>
                Un ciudadano equivale a una preferencia registrada. El sistema valida las identificaciones civiles cifradas antes de contabilizar cada aporte democrático.
              </span>
            </div>
          </div>

          {/* Visual Recharts Bar Graph */}
          <div className="bg-slate-50 rounded-lg p-4 border border-slate-100 flex flex-col justify-between">
            <div className="mb-4">
              <h4 className="text-xs font-bold text-on-surface-variant uppercase tracking-wider">
                Distribución Gráfica de Preferencias
              </h4>
            </div>
            <div className="h-60 w-full">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart
                  data={chartData}
                  margin={{ top: 10, right: 10, left: -20, bottom: 5 }}
                >
                  <XAxis dataKey="name" tick={{ fontSize: 11, fill: '#45474c' }} />
                  <YAxis allowDecimals={false} tick={{ fontSize: 11, fill: '#45474c' }} />
                  <Tooltip
                    contentStyle={{ backgroundColor: '#ffffff', borderRadius: '8px', border: '1px solid #ECEEF0' }}
                    labelStyle={{ fontWeight: 'bold', color: '#091426' }}
                  />
                  <Bar dataKey="votos" radius={[4, 4, 0, 0]} maxBarSize={50}>
                    {chartData.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            </div>
            <div className="text-center pt-2 text-xs text-slate-400 font-mono">
              Actualizado hace unos segundos
            </div>
          </div>
        </div>

        {/* Advanced insights section */}
        <div className="bg-slate-50/50 rounded-lg p-5 border border-slate-100">
          <h3 className="text-sm font-bold text-primary-base tracking-wider uppercase mb-3">
            Tendencia Demográfica Estimada (por rangos etarios)
          </h3>
          <p className="text-xs text-on-surface-variant mb-4">
            Visualiza qué segmentos de la comunidad se inclinan por cada alternativa según los datos censados.
          </p>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {demographicData.map((data, i) => (
              <div key={i} className="bg-white rounded-lg p-3 border border-slate-100 shadow-sm">
                <span className="text-xs font-bold text-primary-base block mb-2">{data.name}</span>
                <div className="space-y-1.5">
                  <div className="flex justify-between text-[11px] font-medium">
                    <span className="text-slate-500">Estadio (Opción A):</span>
                    <span className="text-primary-base font-bold">{data['Opción A (Estadio)']}%</span>
                  </div>
                  <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div className="bg-[#091426] h-full" style={{ width: `${data['Opción A (Estadio)']}%` }} />
                  </div>
                  <div className="flex justify-between text-[11px] font-medium">
                    <span className="text-slate-500">Salón (Opción B):</span>
                    <span className="text-[#4648d4] font-bold">{data['Opción B (Salón)']}%</span>
                  </div>
                  <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div className="bg-[#4648d4] h-full" style={{ width: `${data['Opción B (Salón)']}%` }} />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
