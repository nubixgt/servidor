import React, { useState } from 'react';
import { Model, ModelStatus, Screen } from '../types';
import { motion, AnimatePresence } from 'motion/react';
import { SortAsc, ArrowRight, Check, Globe, Share2 } from 'lucide-react';

interface ModelDirectoryProps {
  models: Model[];
  onSelectModel: (model: Model) => void;
  onNavigate: (screen: Screen) => void;
}

export default function ModelDirectory({ models, onSelectModel, onNavigate }: ModelDirectoryProps) {
  const [activeFilter, setActiveFilter] = useState<ModelStatus | 'ALL'>('ALL');
  const [sortBy, setSortBy] = useState<'name' | 'status'>('name');

  // Filter models
  const filteredModels = models.filter((model) => {
    if (activeFilter === 'ALL') return true;
    return model.status === activeFilter;
  });

  // Sort models
  const sortedModels = [...filteredModels].sort((a, b) => {
    if (sortBy === 'name') return a.name.localeCompare(b.name);
    return a.status.localeCompare(b.status);
  });

  const toggleSort = () => {
    setSortBy((prev) => (prev === 'name' ? 'status' : 'name'));
  };

  const getStatusBadge = (status: ModelStatus) => {
    switch (status) {
      case 'WALKING':
        return (
          <div className="absolute top-4 left-4 flex items-center gap-2 bg-white/95 backdrop-blur px-3 py-1.5 shadow-sm border border-neutral-100">
            <span className="w-1.5 h-1.5 rounded-full bg-black animate-pulse" />
            <span className="text-[9px] font-bold tracking-widest uppercase text-black">WALKING</span>
          </div>
        );
      case 'READY':
        return (
          <div className="absolute top-4 left-4 flex items-center gap-2 bg-white/95 backdrop-blur px-3 py-1.5 shadow-sm border border-neutral-100">
            <span className="w-1.5 h-1.5 rounded-full bg-gray-400" />
            <span className="text-[9px] font-bold tracking-widest uppercase text-gray-500">READY</span>
          </div>
        );
      case 'JUDGED':
        return (
          <div className="absolute top-4 left-4 flex items-center gap-1 bg-white/95 backdrop-blur px-2.5 py-1.5 shadow-sm border border-neutral-100">
            <Check size={10} className="text-black" strokeWidth={3} />
            <span className="text-[9px] font-bold tracking-widest uppercase text-black">JUDGED</span>
          </div>
        );
    }
  };

  return (
    <div className="bg-[#f9f9f9] text-[#1a1c1c] min-h-screen">
      <main className="max-w-[1440px] mx-auto px-6 md:px-16 py-12">
        
        {/* Header Section */}
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-8">
          <div className="space-y-3">
            <h1 className="text-3xl md:text-4xl font-light tracking-tight text-black">Model Directory</h1>
            <p className="text-gray-500 text-sm max-w-md leading-relaxed">
              Official lineup for the Paris Collection 2024. Live status tracking and runway assignments.
            </p>
          </div>

          {/* Filters & Sorting */}
          <div className="flex items-center gap-2 overflow-x-auto pb-2 md:pb-0 scrollbar-none shrink-0">
            {(['ALL', 'READY', 'WALKING', 'JUDGED'] as const).map((filter) => (
              <button
                key={filter}
                onClick={() => setActiveFilter(filter)}
                className={`px-5 py-2.5 text-[10px] font-bold tracking-widest border transition-all duration-300 cursor-pointer ${
                  activeFilter === filter
                    ? 'bg-black text-white border-black'
                    : 'bg-transparent text-gray-400 border-gray-200 hover:border-black hover:text-black'
                }`}
              >
                {filter}
              </button>
            ))}
            <div className="w-[1px] h-6 bg-gray-200 mx-2 hidden sm:block" />
            <button 
              onClick={toggleSort}
              className="flex items-center gap-2 text-[10px] font-bold tracking-widest text-gray-500 hover:text-black cursor-pointer uppercase py-2 px-3"
            >
              <SortAsc size={14} />
              Sort: {sortBy}
            </button>
          </div>
        </div>

        {/* Bento/Grid of Model Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
          <AnimatePresence mode="popLayout">
            {sortedModels.map((model, index) => (
              <motion.div
                key={model.id}
                layout
                initial={{ opacity: 0, y: 15 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95 }}
                transition={{ duration: 0.5, delay: index * 0.05 }}
                onClick={() => onSelectModel(model)}
                className="group relative flex flex-col gap-4 cursor-pointer select-none"
              >
                {/* Image Container */}
                <div className="aspect-[3/4] relative overflow-hidden bg-neutral-100">
                  <img 
                    src={model.imageUrl} 
                    alt={model.name}
                    loading="lazy"
                    className="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                  />
                  {/* Hover visual highlight border */}
                  <div className="absolute inset-0 border border-black opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none" />
                  
                  {/* Status Indicator */}
                  {getStatusBadge(model.status)}
                </div>

                {/* Meta details */}
                <div className="space-y-1">
                  <div className="flex justify-between items-start">
                    <h3 className="text-[11px] font-bold tracking-widest uppercase text-black">{model.name}</h3>
                    {model.scores.total > 0 && (
                      <span className="text-[10px] font-bold text-gray-500">★ {model.scores.total.toFixed(2)}</span>
                    )}
                  </div>
                  <p className="text-xs text-gray-400 italic font-light">{model.look}</p>
                </div>
              </motion.div>
            ))}
          </AnimatePresence>
        </div>

        {/* Empty State */}
        {sortedModels.length === 0 && (
          <div className="py-24 text-center border border-dashed border-gray-200">
            <p className="text-sm text-gray-400">No models found matching the active status filter.</p>
          </div>
        )}

        {/* Load More Section */}
        <div className="mt-20 flex justify-center">
          <button 
            onClick={() => alert('Full roster is active. Custom judge assignments can be configured in your system settings.')}
            className="flex items-center gap-4 px-12 py-4 border border-black text-[10px] font-bold tracking-widest text-black hover:bg-black hover:text-white transition-all duration-300 rounded-none cursor-pointer"
          >
            VIEW FULL ROSTER
            <ArrowRight size={14} />
          </button>
        </div>
      </main>

      {/* Footer Decoration / Secondary Info */}
      <footer className="mt-24 border-t border-gray-200 py-16 bg-white">
        <div className="max-w-[1440px] mx-auto px-6 md:px-16 flex flex-col md:flex-row justify-between items-start gap-12">
          <div className="space-y-4">
            <div className="font-serif text-lg tracking-[0.2em] text-black font-normal uppercase">AURA</div>
            <p className="text-[10px] text-gray-400 tracking-[0.25em] uppercase">Paris Collection 2024</p>
          </div>
          
          <div className="grid grid-cols-2 gap-16">
            <div className="flex flex-col gap-2.5">
              <span className="text-[10px] font-bold tracking-widest text-gray-400 mb-2 uppercase">PAGES</span>
              <button onClick={() => onNavigate('directory')} className="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Directory</button>
              <button onClick={() => onNavigate('judging')} className="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Live Show</button>
              <button onClick={() => alert('Press kit downloads will open shortly.')} className="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Press Kit</button>
            </div>
            
            <div className="flex flex-col gap-2.5">
              <span className="text-[10px] font-bold tracking-widest text-gray-400 mb-2 uppercase">SYSTEM</span>
              <button onClick={() => alert('Legal guidelines for judges.')} className="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Legal</button>
              <button onClick={() => alert('Privacy declaration.')} className="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">Privacy</button>
              <button onClick={() => alert('Judiciary endpoint APIs.')} className="text-xs text-black font-semibold text-left hover:opacity-75 cursor-pointer">API</button>
            </div>
          </div>
        </div>

        <div className="max-w-[1440px] mx-auto px-6 md:px-16 mt-12 pt-8 border-t border-gray-100 flex justify-between items-center text-gray-400">
          <span className="text-[9px] tracking-widest uppercase font-semibold">© 2024 AURA FASHION GROUP</span>
          <div className="flex gap-4">
            <button className="hover:text-black transition-colors" title="Language"><Globe size={16} strokeWidth={1.5} /></button>
            <button className="hover:text-black transition-colors" title="Share portal"><Share2 size={16} strokeWidth={1.5} /></button>
          </div>
        </div>
      </footer>
    </div>
  );
}
