import React from 'react';
import { Model } from '../types';
import { motion } from 'motion/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface LeaderboardProps {
  models: Model[];
  onSelectModel: (model: Model) => void;
}

export default function Leaderboard({ models, onSelectModel }: LeaderboardProps) {
  // Filter scored models or sort all models by total score descending
  const rankedModels = [...models].sort((a, b) => b.scores.total - a.scores.total);

  // Take top 3 for podium.
  const podium1 = rankedModels[0]; // Winner
  const podium2 = rankedModels[1]; // Rank 2
  const podium3 = rankedModels[2]; // Rank 3

  return (
    <div className="flex-grow px-6 md:px-16 py-12 max-w-5xl mx-auto w-full bg-[#f9f9f9]">
      
      {/* Page Title */}
      <section className="mb-16">
        <h1 className="text-3xl font-light tracking-tight text-black mb-2 uppercase">Live Ranking</h1>
        <p className="text-sm text-gray-400 leading-relaxed max-w-2xl">
          Official live leaderboard for the Paris Collection 2024. Rankings are updated in real-time as judging panels submit their final evaluations.
        </p>
      </section>

      {/* Top 3 Featured Podium Section */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16 items-end">
        {/* Silver - Rank 2 */}
        {podium2 && (
          <motion.div 
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            onClick={() => onSelectModel(podium2)}
            className="order-2 md:order-1 bg-white p-6 border-b-2 border-gray-300 relative flex flex-col items-center text-center cursor-pointer hover:shadow-md transition-all rounded-none"
          >
            <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-gray-100 text-black px-3 py-1 text-[9px] font-bold tracking-widest uppercase">
              RANK 02
            </div>
            <div className="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-100 mb-4 bg-neutral-50 shadow-sm">
              <img 
                className="w-full h-full object-cover" 
                alt={podium2.name} 
                src={podium2.podiumUrl || podium2.imageUrl}
              />
            </div>
            <h3 className="text-xs font-bold tracking-widest text-black mb-1 uppercase">{podium2.name}</h3>
            <p className="text-gray-400 text-xs italic font-light">{podium2.look}</p>
            <p className="text-black font-semibold text-xs mt-2 uppercase tracking-wide">Score: {podium2.scores.total.toFixed(2)}</p>
          </motion.div>
        )}

        {/* Gold - Rank 1 (Winner) */}
        {podium1 && (
          <motion.div 
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.2 }}
            onClick={() => onSelectModel(podium1)}
            className="order-1 md:order-2 bg-white p-8 border-b-4 border-black relative flex flex-col items-center text-center shadow-lg transform md:-translate-y-2 scale-105 cursor-pointer rounded-none z-10"
          >
            <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-black text-white px-4 py-1 text-[9px] font-bold tracking-widest uppercase">
              WINNER
            </div>
            <div className="w-28 h-28 rounded-full overflow-hidden border-4 border-black mb-4 bg-neutral-50 shadow-sm">
              <img 
                className="w-full h-full object-cover" 
                alt={podium1.name} 
                src={podium1.podiumUrl || podium1.imageUrl}
              />
            </div>
            <h3 className="text-sm font-extrabold tracking-widest text-black mb-1 uppercase">{podium1.name}</h3>
            <p className="text-gray-400 text-xs italic font-light">{podium1.look}</p>
            <p className="text-black font-extrabold text-sm mt-3 uppercase tracking-wider">Score: {podium1.scores.total.toFixed(2)}</p>
          </motion.div>
        )}

        {/* Bronze - Rank 3 */}
        {podium3 && (
          <motion.div 
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.3 }}
            onClick={() => onSelectModel(podium3)}
            className="order-3 md:order-3 bg-white p-6 border-b-2 border-gray-200 relative flex flex-col items-center text-center cursor-pointer hover:shadow-md transition-all rounded-none"
          >
            <div className="absolute -top-4 left-1/2 -translate-x-1/2 bg-gray-50 text-gray-500 px-3 py-1 text-[9px] font-bold tracking-widest uppercase">
              RANK 03
            </div>
            <div className="w-24 h-24 rounded-full overflow-hidden border-2 border-gray-100 mb-4 bg-neutral-50 shadow-sm">
              <img 
                className="w-full h-full object-cover" 
                alt={podium3.name} 
                src={podium3.podiumUrl || podium3.imageUrl}
              />
            </div>
            <h3 className="text-xs font-bold tracking-widest text-black mb-1 uppercase">{podium3.name}</h3>
            <p className="text-gray-400 text-xs italic font-light">{podium3.look}</p>
            <p className="text-black font-semibold text-xs mt-2 uppercase tracking-wide">Score: {podium3.scores.total.toFixed(2)}</p>
          </motion.div>
        )}
      </div>

      {/* Detailed Table */}
      <div className="bg-white overflow-x-auto border border-gray-100 shadow-[0_0_30px_rgba(0,0,0,0.015)] rounded-none mb-12">
        <table className="w-full border-collapse text-left">
          <thead>
            <tr className="border-b border-gray-200 bg-gray-50/50">
              <th className="py-5 px-6 text-[10px] font-bold tracking-widest text-gray-400 uppercase w-20 text-center">Rank</th>
              <th className="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase">Model</th>
              <th className="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-center">Walk</th>
              <th className="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-center">Presence</th>
              <th className="py-5 px-4 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-center">Garment</th>
              <th className="py-5 px-6 text-[10px] font-bold tracking-widest text-gray-400 uppercase text-right w-28">Total</th>
            </tr>
          </thead>
          <tbody>
            {rankedModels.map((model, idx) => {
              const rankStr = String(idx + 1).padStart(2, '0');
              const hasScore = model.scores.total > 0;
              const isFirst = idx === 0;
              const isSecond = idx === 1;
              const isThird = idx === 2;

              let accentClass = '';
              if (isFirst && hasScore) accentClass = 'border-l-[3px] border-black';
              else if (isSecond && hasScore) accentClass = 'border-l-[3px] border-neutral-400';
              else if (isThird && hasScore) accentClass = 'border-l-[3px] border-neutral-200';

              return (
                <tr 
                  key={model.id}
                  onClick={() => onSelectModel(model)}
                  className={`border-b border-gray-100 transition-all duration-300 hover:bg-neutral-50/50 hover:translate-x-1 cursor-pointer ${accentClass}`}
                >
                  {/* Rank */}
                  <td className="py-5 px-6 text-center">
                    <span className={`text-[11px] font-bold tracking-widest ${hasScore ? 'text-black' : 'text-gray-300'}`}>
                      {rankStr}
                    </span>
                  </td>

                  {/* Model */}
                  <td className="py-4 px-4">
                    <div className="flex items-center gap-4">
                      <div className="w-11 h-11 bg-neutral-100 overflow-hidden shrink-0">
                        <img 
                          className="w-full h-full object-cover" 
                          alt="" 
                          src={model.avatarUrl || model.imageUrl} 
                        />
                      </div>
                      <div>
                        <span className="text-[11px] font-bold tracking-widest text-black uppercase block">
                          {model.name}
                        </span>
                        <span className="text-[9px] text-gray-400 block truncate max-w-[150px] italic font-light">
                          {model.look}
                        </span>
                      </div>
                    </div>
                  </td>

                  {/* Scores columns */}
                  <td className="py-5 px-4 text-center text-xs text-gray-500 font-medium">
                    {hasScore ? model.scores.walk.toFixed(1) : '—'}
                  </td>
                  <td className="py-5 px-4 text-center text-xs text-gray-500 font-medium">
                    {hasScore ? model.scores.presence.toFixed(1) : '—'}
                  </td>
                  <td className="py-5 px-4 text-center text-xs text-gray-500 font-medium">
                    {hasScore ? model.scores.garment.toFixed(1) : '—'}
                  </td>

                  {/* Total */}
                  <td className="py-5 px-6 text-right">
                    <span className={`text-xs font-bold tracking-widest ${hasScore ? 'text-black' : 'text-gray-300'}`}>
                      {hasScore ? model.scores.total.toFixed(2) : 'PENDING'}
                    </span>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      {/* Footer Pagination Status */}
      <div className="flex justify-between items-center text-gray-400 select-none">
        <div className="flex items-center gap-2">
          <span className="w-2 h-2 rounded-full bg-black animate-pulse" />
          <span className="text-[9px] font-bold tracking-widest uppercase">Live Data Stream Active</span>
        </div>
        <div className="flex gap-3">
          <button className="p-2 border border-gray-200 hover:border-black hover:text-black transition-colors rounded-none cursor-pointer">
            <ChevronLeft size={16} strokeWidth={1.5} />
          </button>
          <button className="p-2 border border-gray-200 hover:border-black hover:text-black transition-colors rounded-none cursor-pointer">
            <ChevronRight size={16} strokeWidth={1.5} />
          </button>
        </div>
      </div>

    </div>
  );
}
