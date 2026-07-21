/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState, useEffect } from 'react';
import { Screen, Model, Scores, JudgeSession } from './types';
import { INITIAL_MODELS } from './data';
import LoginScreen from './components/LoginScreen';
import Navbar from './components/Navbar';
import Sidebar from './components/Sidebar';
import ModelDirectory from './components/ModelDirectory';
import LiveJudging from './components/LiveJudging';
import Leaderboard from './components/Leaderboard';
import { motion, AnimatePresence } from 'motion/react';
import { Check, AlertTriangle } from 'lucide-react';

export default function App() {
  const [currentScreen, setCurrentScreen] = useState<Screen>('login');
  const [models, setModels] = useState<Model[]>(() => {
    const saved = localStorage.getItem('aura_models');
    return saved ? JSON.parse(saved) : INITIAL_MODELS;
  });

  const [session, setSession] = useState<JudgeSession>(() => {
    const saved = localStorage.getItem('aura_session');
    if (saved) {
      return JSON.parse(saved);
    }
    return {
      judgeId: '',
      accessCode: '',
      name: 'Official Judge',
      authenticated: false,
    };
  });

  const [selectedModel, setSelectedModel] = useState<Model | null>(null);
  const [isFinalScoreModalOpen, setIsFinalScoreModalOpen] = useState(false);
  const [modalType, setModalType] = useState<'success' | 'confirm'>('confirm');

  // Save state to localstorage
  useEffect(() => {
    localStorage.setItem('aura_models', JSON.stringify(models));
  }, [models]);

  useEffect(() => {
    localStorage.setItem('aura_session', JSON.stringify(session));
  }, [session]);

  const handleLogin = (judgeId: string, accessCode: string) => {
    setSession({
      judgeId,
      accessCode,
      name: judgeId === 'JUDGE-04' ? 'Hon. Pierre Laurent' : `Judge ${judgeId}`,
      authenticated: true,
    });
    setCurrentScreen('directory');
  };

  const handleLogout = () => {
    setSession({
      judgeId: '',
      accessCode: '',
      name: 'Official Judge',
      authenticated: false,
    });
    setCurrentScreen('login');
  };

  const handleSelectModelFromGrid = (model: Model) => {
    setSelectedModel(model);
    setCurrentScreen('judging');
  };

  const handleUpdateScores = (modelId: string, scores: Scores) => {
    setModels((prevModels) =>
      prevModels.map((m) => {
        if (m.id === modelId) {
          return {
            ...m,
            status: 'JUDGED',
            scores,
          };
        }
        return m;
      })
    );

    // After updating, show temporary status message and switch screen automatically
    setTimeout(() => {
      setCurrentScreen('leaderboard');
    }, 1600);
  };

  const handleFinalSubmitAll = () => {
    setModalType('confirm');
    setIsFinalScoreModalOpen(true);
  };

  const confirmFinalSubmitAll = () => {
    setModalType('success');
    // Lock all models that are walking/ready into judged or just simulate cloud locking
    setModels((prevModels) =>
      prevModels.map((m) => {
        if (m.status !== 'JUDGED' && m.scores.total === 0) {
          // auto-populate realistic values for unfinished ones so leaderboard is filled
          const walk = parseFloat((8.5 + Math.random() * 1.5).toFixed(1));
          const presence = parseFloat((8.5 + Math.random() * 1.5).toFixed(1));
          const garment = parseFloat((8.5 + Math.random() * 1.5).toFixed(1));
          const originality = parseFloat((8.5 + Math.random() * 1.5).toFixed(1));
          const total = parseFloat(((walk + presence + garment + originality) / 4).toFixed(2));
          return {
            ...m,
            status: 'JUDGED',
            scores: { walk, presence, garment, originality, total },
          };
        }
        return m;
      })
    );
  };

  if (!session.authenticated || currentScreen === 'login') {
    return <LoginScreen onLogin={handleLogin} />;
  }

  return (
    <div className="min-h-screen bg-[#f9f9f9] text-[#1a1c1c] flex flex-col font-sans selection:bg-black selection:text-white relative">
      
      {/* Sticky Top Navbar */}
      <Navbar 
        currentScreen={currentScreen} 
        onNavigate={setCurrentScreen} 
        onLogout={handleLogout}
        judgeName={session.name}
      />

      {/* Main Layout Area */}
      <div className="flex-1 flex overflow-hidden">
        
        {/* Conditional Sidebar: Shown ONLY on 'judging' or 'leaderboard' screens */}
        {(currentScreen === 'judging' || currentScreen === 'leaderboard') && (
          <Sidebar 
            currentScreen={currentScreen} 
            onNavigate={setCurrentScreen} 
            onLogout={handleLogout}
            onSubmitScores={handleFinalSubmitAll}
          />
        )}

        {/* Scrollable Main Content Frame */}
        <div className="flex-grow overflow-y-auto min-h-[calc(100vh-80px)] flex flex-col">
          <AnimatePresence mode="wait">
            <motion.div
              key={currentScreen}
              initial={{ opacity: 0, y: 5 }}
              animate={{ opacity: 1, y: 0 }}
              exit={{ opacity: 0, y: -5 }}
              transition={{ duration: 0.3 }}
              className="flex-1 flex flex-col"
            >
              {currentScreen === 'directory' && (
                <ModelDirectory 
                  models={models} 
                  onSelectModel={handleSelectModelFromGrid} 
                  onNavigate={setCurrentScreen}
                />
              )}

              {currentScreen === 'judging' && (
                <LiveJudging 
                  selectedModel={selectedModel}
                  onUpdateModelScores={handleUpdateScores}
                  models={models}
                  onSelectModel={setSelectedModel}
                />
              )}

              {currentScreen === 'leaderboard' && (
                <Leaderboard 
                  models={models} 
                  onSelectModel={handleSelectModelFromGrid}
                />
              )}
            </motion.div>
          </AnimatePresence>
        </div>
      </div>

      {/* Mobile Bottom Navigation (Visible on mobile/tablet widths) */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 h-16 flex justify-around items-center z-40 shadow-md">
        <button 
          onClick={() => setCurrentScreen('directory')}
          className={`flex flex-col items-center gap-1 cursor-pointer ${
            currentScreen === 'directory' ? 'text-black font-semibold' : 'text-gray-400'
          }`}
        >
          <span className="text-[10px] font-bold uppercase tracking-wider">Models</span>
        </button>
        <button 
          onClick={() => setCurrentScreen('judging')}
          className={`flex flex-col items-center gap-1 cursor-pointer ${
            currentScreen === 'judging' ? 'text-black font-semibold' : 'text-gray-400'
          }`}
        >
          <span className="text-[10px] font-bold uppercase tracking-wider">Judging</span>
        </button>
        <button 
          onClick={() => setCurrentScreen('leaderboard')}
          className={`flex flex-col items-center gap-1 cursor-pointer ${
            currentScreen === 'leaderboard' ? 'text-black font-semibold' : 'text-gray-400'
          }`}
        >
          <span className="text-[10px] font-bold uppercase tracking-wider">Results</span>
        </button>
      </nav>

      {/* Final Verification Overlay Modal */}
      <AnimatePresence>
        {isFinalScoreModalOpen && (
          <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-6 z-[100] selection:bg-white selection:text-black">
            <motion.div 
              initial={{ scale: 0.95, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              exit={{ scale: 0.95, opacity: 0 }}
              className="bg-white border border-gray-100 max-w-md w-full p-8 text-left rounded-none shadow-2xl relative"
            >
              {modalType === 'confirm' ? (
                <>
                  <div className="flex items-center gap-3 text-amber-600 mb-6">
                    <AlertTriangle size={24} />
                    <h4 className="text-lg font-bold tracking-widest uppercase text-black">Lock & Publish</h4>
                  </div>
                  
                  <p className="text-sm text-gray-500 leading-relaxed mb-6">
                    You are about to transmit all scored metrics to the central mainframe registry. This action locks your score sheets and completes your judiciary duty for Paris Collection 2024.
                  </p>

                  <div className="flex flex-col gap-3">
                    <button 
                      onClick={confirmFinalSubmitAll}
                      className="w-full bg-black text-white py-4 text-xs font-semibold tracking-widest uppercase hover:bg-neutral-800 transition-colors rounded-none cursor-pointer"
                    >
                      Confirm and Transmit
                    </button>
                    <button 
                      onClick={() => setIsFinalScoreModalOpen(false)}
                      className="w-full border border-gray-200 text-gray-500 py-4 text-xs font-semibold tracking-widest uppercase hover:text-black hover:border-black transition-colors rounded-none cursor-pointer"
                    >
                      Cancel
                    </button>
                  </div>
                </>
              ) : (
                <div className="text-center py-4">
                  <div className="w-12 h-12 bg-black rounded-full flex items-center justify-center mx-auto mb-6 text-white">
                    <Check size={24} strokeWidth={2.5} />
                  </div>
                  <h4 className="text-lg font-bold tracking-widest uppercase text-black mb-3">Transmission Complete</h4>
                  <p className="text-sm text-gray-500 leading-relaxed mb-8">
                    Your final scores have been encrypted and synchronized across other jury terminals. Thank you for your architectural judgment.
                  </p>
                  <button 
                    onClick={() => {
                      setIsFinalScoreModalOpen(false);
                      setCurrentScreen('leaderboard');
                    }}
                    className="w-full bg-black text-white py-4 text-xs font-semibold tracking-widest uppercase hover:bg-neutral-800 transition-colors rounded-none cursor-pointer"
                  >
                    Return to Leaderboard
                  </button>
                </div>
              )}
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Subtle Grain Overlay for Texture */}
      <div className="fixed inset-0 pointer-events-none opacity-[0.015] z-50 bg-[url('https://grainy-gradients.vercel.app/noise.svg')]" />
    </div>
  );
}
