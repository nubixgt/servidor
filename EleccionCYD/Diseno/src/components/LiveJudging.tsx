import React, { useState, useEffect } from 'react';
import { Model, Scores } from '../types';
import { motion } from 'motion/react';
import { ArrowRight, Loader2, CheckCircle } from 'lucide-react';

interface LiveJudgingProps {
  selectedModel: Model | null;
  onUpdateModelScores: (modelId: string, scores: Scores) => void;
  models: Model[];
  onSelectModel: (model: Model) => void;
}

export default function LiveJudging({ 
  selectedModel, 
  onUpdateModelScores, 
  models, 
  onSelectModel 
}: LiveJudgingProps) {
  // Find a walking model by default if none selected
  const activeModel = selectedModel || 
    models.find(m => m.status === 'WALKING') || 
    models.find(m => m.status === 'READY') || 
    models[0];

  // Slider values
  const [vestimenta, setVestimenta] = useState(5);
  const [caminata, setCaminata] = useState(7);
  const [postura, setPostura] = useState(8);
  const [originalidad, setOriginalidad] = useState(6);
  
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);

  // Synchronize scores if model has been scored before
  useEffect(() => {
    if (activeModel && activeModel.scores && activeModel.scores.total > 0) {
      setVestimenta(Math.round(activeModel.scores.walk) || 5);
      setCaminata(Math.round(activeModel.scores.presence) || 7);
      setPostura(Math.round(activeModel.scores.garment) || 8);
      setOriginalidad(Math.round(activeModel.scores.originality) || 6);
    } else {
      // Default initial states matching scorecard
      setVestimenta(5);
      setCaminata(7);
      setPostura(8);
      setOriginalidad(6);
    }
  }, [activeModel]);

  if (!activeModel) {
    return (
      <div className="flex-1 flex items-center justify-center bg-gray-50 p-12">
        <p className="text-gray-400">No active models found in show roster.</p>
      </div>
    );
  }

  // Calculate Average Live
  const averageScore = parseFloat(((vestimenta + caminata + postura + originalidad) / 4).toFixed(1));

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    setTimeout(() => {
      setIsSubmitting(false);
      setSubmitSuccess(true);
      
      // Update state
      const finalScores: Scores = {
        walk: vestimenta,
        presence: caminata,
        garment: postura,
        originality: originalidad,
        total: averageScore
      };
      
      onUpdateModelScores(activeModel.id, finalScores);

      setTimeout(() => {
        setSubmitSuccess(false);
      }, 1500);
    }, 1200);
  };

  return (
    <div className="flex-1 flex flex-col md:flex-row overflow-hidden bg-white select-none">
      
      {/* Injecting Slider Range Custom Styles directly to support high-end minimalist design */}
      <style dangerouslySetInnerHTML={{__html: `
        input[type="range"] {
          -webkit-appearance: none;
          width: 100%;
          background: transparent;
        }
        input[type="range"]:focus {
          outline: none;
        }
        input[type="range"]::-webkit-slider-runnable-track {
          width: 100%;
          height: 1px;
          cursor: pointer;
          background: #000000;
        }
        input[type="range"]::-webkit-slider-thumb {
          height: 12px;
          width: 12px;
          border-radius: 0px;
          background: #000000;
          cursor: pointer;
          -webkit-appearance: none;
          margin-top: -6px;
          transition: transform 0.15s ease;
        }
        input[type="range"]::-webkit-slider-thumb:hover {
          transform: scale(1.2);
        }
        input[type="range"]::-moz-range-track {
          width: 100%;
          height: 1px;
          cursor: pointer;
          background: #000000;
        }
        input[type="range"]::-moz-range-thumb {
          height: 12px;
          width: 12px;
          border-radius: 0px;
          background: #000000;
          cursor: pointer;
        }
      `}} />

      {/* Left Column: Large Runway Photo */}
      <section className="w-full md:w-1/2 h-[40vh] md:h-full relative group overflow-hidden">
        <div 
          className="absolute inset-0 bg-cover bg-center transition-transform duration-[1.5s] ease-out group-hover:scale-105"
          style={{ backgroundImage: `url('${activeModel.imageUrl}')` }}
        />
        
        {/* Model Info Overlay */}
        <div className="absolute inset-x-0 bottom-0 p-8 md:p-12 bg-gradient-to-t from-black/80 via-black/45 to-transparent text-white flex flex-col justify-end h-1/2">
          <div className="flex items-center gap-2 mb-3">
            <span className="w-2 h-2 rounded-full bg-red-600 animate-pulse" />
            <span className="text-[10px] font-bold tracking-[0.25em] uppercase">Live on Runway</span>
          </div>
          <h2 className="font-serif text-3xl md:text-5xl tracking-tight mb-2 uppercase font-light">
            {activeModel.name}
          </h2>
          <div className="flex flex-wrap gap-x-6 gap-y-1 text-xs opacity-75 font-normal tracking-wide">
            <p className="uppercase">{activeModel.look}</p>
            <p className="text-gray-300">Designer: {activeModel.designer}</p>
          </div>
        </div>
        
        {/* Contrast Overlay Accent */}
        <div className="absolute inset-0 border border-white/5 pointer-events-none" />
      </section>

      {/* Right Column: Judging Scorecard */}
      <section className="w-full md:w-1/2 bg-[#f9f9f9] md:h-full p-8 md:p-16 overflow-y-auto flex flex-col border-t md:border-t-0 md:border-l border-gray-100">
        <div className="max-w-md mx-auto w-full flex-1 flex flex-col justify-center">
          
          <header className="mb-10">
            <p className="text-[10px] font-bold tracking-[0.25em] text-gray-400 mb-2 uppercase">Session 04</p>
            <h3 className="text-2xl font-light tracking-tight text-black">Judging Scorecard</h3>
            <div className="w-12 h-[1px] bg-black mt-4" />
          </header>

          <form onSubmit={handleSubmit} className="space-y-10">
            {/* Slider 1 */}
            <div className="space-y-3">
              <div className="flex justify-between items-end">
                <label className="text-[10px] font-bold tracking-widest text-black uppercase">Vestimenta</label>
                <span className="text-xs font-semibold text-gray-500">{vestimenta}/10</span>
              </div>
              <div className="relative pt-1">
                <input 
                  type="range"
                  min="1"
                  max="10"
                  step="1"
                  value={vestimenta}
                  onChange={(e) => setVestimenta(parseInt(e.target.value))}
                  className="w-full cursor-pointer"
                />
                <div className="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                  <span>1</span>
                  <span>10</span>
                </div>
              </div>
            </div>

            {/* Slider 2 */}
            <div className="space-y-3">
              <div className="flex justify-between items-end">
                <label className="text-[10px] font-bold tracking-widest text-black uppercase">Caminata</label>
                <span className="text-xs font-semibold text-gray-500">{caminata}/10</span>
              </div>
              <div className="relative pt-1">
                <input 
                  type="range"
                  min="1"
                  max="10"
                  step="1"
                  value={caminata}
                  onChange={(e) => setCaminata(parseInt(e.target.value))}
                  className="w-full cursor-pointer"
                />
                <div className="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                  <span>1</span>
                  <span>10</span>
                </div>
              </div>
            </div>

            {/* Slider 3 */}
            <div className="space-y-3">
              <div className="flex justify-between items-end">
                <label className="text-[10px] font-bold tracking-widest text-black uppercase">Postura/Actitud</label>
                <span className="text-xs font-semibold text-gray-500">{postura}/10</span>
              </div>
              <div className="relative pt-1">
                <input 
                  type="range"
                  min="1"
                  max="10"
                  step="1"
                  value={postura}
                  onChange={(e) => setPostura(parseInt(e.target.value))}
                  className="w-full cursor-pointer"
                />
                <div className="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                  <span>1</span>
                  <span>10</span>
                </div>
              </div>
            </div>

            {/* Slider 4 */}
            <div className="space-y-3">
              <div className="flex justify-between items-end">
                <label className="text-[10px] font-bold tracking-widest text-black uppercase">Originalidad</label>
                <span className="text-xs font-semibold text-gray-500">{originalidad}/10</span>
              </div>
              <div className="relative pt-1">
                <input 
                  type="range"
                  min="1"
                  max="10"
                  step="1"
                  value={originalidad}
                  onChange={(e) => setOriginalidad(parseInt(e.target.value))}
                  className="w-full cursor-pointer"
                />
                <div className="flex justify-between text-[9px] font-bold tracking-wider text-gray-400 mt-2">
                  <span>1</span>
                  <span>10</span>
                </div>
              </div>
            </div>

            {/* Summary Live Calculation */}
            <div className="pt-6 border-t border-gray-200">
              <div className="flex justify-between items-center">
                <p className="text-[10px] font-bold tracking-widest text-gray-400 uppercase">Average Score</p>
                <p className="text-4xl font-light text-black tracking-tight font-sans">
                  {averageScore.toFixed(1)}
                </p>
              </div>
            </div>

            {/* Buttons & Validation */}
            <div className="pt-4">
              <button 
                type="submit"
                disabled={isSubmitting || submitSuccess}
                className="w-full bg-black text-white py-5 text-xs font-semibold tracking-widest uppercase hover:bg-neutral-800 active:scale-[0.99] transition-all duration-300 rounded-none flex items-center justify-center gap-3 cursor-pointer disabled:bg-neutral-800"
              >
                {isSubmitting && <Loader2 className="animate-spin" size={14} />}
                {submitSuccess ? (
                  <>
                    <CheckCircle size={14} />
                    Scores Synchronized
                  </>
                ) : (
                  <>
                    Submit Final Scores
                    <ArrowRight size={14} />
                  </>
                )}
              </button>
              <p className="text-[9px] text-center text-gray-400 mt-4 uppercase tracking-wider leading-relaxed">
                Locking scores for look #{activeModel.id}. This action cannot be undone.
              </p>
            </div>
          </form>

          {/* Model Switcher Carousel */}
          <div className="mt-12 pt-8 border-t border-gray-200">
            <p className="text-[9px] font-bold tracking-widest text-gray-400 uppercase mb-4">Runway Queue</p>
            <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
              {models.map(m => (
                <button
                  key={m.id}
                  onClick={() => onSelectModel(m)}
                  className={`flex-shrink-0 flex items-center gap-2.5 px-3 py-2 border transition-all duration-300 cursor-pointer text-left ${
                    activeModel.id === m.id
                      ? 'border-black bg-white'
                      : 'border-gray-200 bg-transparent hover:border-black'
                  }`}
                >
                  <img src={m.imageUrl} className="w-6 h-8 object-cover" alt="" />
                  <div>
                    <p className="text-[9px] font-bold uppercase text-black truncate max-w-[80px]">{m.name}</p>
                    <p className="text-[8px] text-gray-400 uppercase">{m.status}</p>
                  </div>
                </button>
              ))}
            </div>
          </div>

        </div>
      </section>

    </div>
  );
}
