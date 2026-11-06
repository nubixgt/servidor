import React, { useState, useRef, useEffect } from 'react';
import { AthleteProfile, ChatMessage } from '../types';

interface AiCoachModalProps {
  isOpen: boolean;
  onClose: () => void;
  athlete: AthleteProfile;
}

export const AiCoachModal: React.FC<AiCoachModalProps> = ({
  isOpen,
  onClose,
  athlete,
}) => {
  const [messages, setMessages] = useState<ChatMessage[]>([
    {
      id: 'msg-1',
      sender: 'ai',
      text: `¡Hola Alex! Soy tu **Agente IA de Power Coach AI**.
He revisado tu telemetría reciente: tu VFC está equilibrada en **${athlete.hrv} ms** y tu FTP se sitúa en **${athlete.ftpWatts} W**.
Tenemos **${athlete.nextTargetRace.daysRemaining} días** para el **${athlete.nextTargetRace.name} ${athlete.nextTargetRace.location}**.
¿Deseas evaluar la fatiga acumulada, ajustar la sesión de hoy o simular un microciclo de carga?`,
      timestamp: 'Ahora',
      badge: 'Coach Jefe',
    },
  ]);
  const [inputText, setInputText] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (isOpen) {
      messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages, isOpen]);

  if (!isOpen) return null;

  const handleSend = async (customMessage?: string) => {
    const textToSend = customMessage || inputText.trim();
    if (!textToSend || isLoading) return;

    const userMsg: ChatMessage = {
      id: `usr-${Date.now()}`,
      sender: 'athlete',
      text: textToSend,
      timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    };

    setMessages((prev) => [...prev, userMsg]);
    if (!customMessage) setInputText('');
    setIsLoading(true);

    try {
      const response = await fetch('/api/ai/coach', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: textToSend,
          athleteContext: {
            name: athlete.name,
            primarySport: athlete.primarySport,
            hrv: athlete.hrv,
            restingHR: athlete.restingHeartRate,
            maxHR: athlete.maxHeartRate,
            ftpWatts: athlete.ftpWatts,
            targetRace: athlete.nextTargetRace,
          },
        }),
      });

      const data = await response.json();
      const aiReply = data.reply || 'No se pudo obtener respuesta del Agente IA.';

      const aiMsg: ChatMessage = {
        id: `ai-${Date.now()}`,
        sender: 'ai',
        text: aiReply,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
        badge: 'Agente IA',
      };
      setMessages((prev) => [...prev, aiMsg]);
    } catch (error) {
      console.error('Error fetching AI coach reply:', error);
      const errorMsg: ChatMessage = {
        id: `ai-err-${Date.now()}`,
        sender: 'ai',
        text: '⚠️ Ocurrió una interrupción en el canal con el Agente IA. Reintenta tu consulta.',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
      };
      setMessages((prev) => [...prev, errorMsg]);
    } finally {
      setIsLoading(false);
    }
  };

  const quickPrompts = [
    'Ajustar por dolor en tendón de Aquiles',
    'Revisar SWOLF y técnica de natación',
    'Plan de nutrición e hidratación para 70.3',
    '¿Cómo optimizar mi Z2 en ciclismo?',
  ];

  return (
    <div
      id="ai-coach-backdrop"
      className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-md animate-fadeIn select-none"
    >
      <div className="glass-panel border border-white/15 rounded-3xl max-w-2xl w-full h-[85vh] flex flex-col shadow-[0_0_50px_rgba(202,243,0,0.15)] relative overflow-hidden">
        {/* Header */}
        <header className="p-4 sm:p-5 border-b border-white/10 flex justify-between items-center bg-white/5">
          <div className="flex items-center gap-3.5">
            <div className="w-11 h-11 rounded-2xl bg-[#caf300] text-[#121700] flex items-center justify-center shadow-[0_0_15px_rgba(202,243,0,0.4)]">
              <span className="material-symbols-outlined text-[26px]">smart_toy</span>
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-lg font-black text-white tracking-tight">
                  Agente IA
                </h3>
                <span className="px-2.5 py-0.5 bg-[#caf300]/20 border border-[#caf300]/40 text-[#caf300] font-mono-data text-[10px] font-bold rounded-full uppercase shadow-sm">
                  Online
                </span>
              </div>
              <p className="text-xs text-[#c5c9ac] font-medium">
                Consultoría de rendimiento & prescripción de carga en tiempo real
              </p>
            </div>
          </div>

          <button
            onClick={onClose}
            className="p-1.5 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10 transition-colors"
          >
            <span className="material-symbols-outlined text-2xl">close</span>
          </button>
        </header>

        {/* Message Feed */}
        <div className="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
          {messages.map((msg) => {
            const isUser = msg.sender === 'athlete';
            return (
              <div
                key={msg.id}
                className={`flex gap-3 ${isUser ? 'justify-end' : 'justify-start'}`}
              >
                {!isUser && (
                  <div className="w-8 h-8 rounded-xl bg-white/10 border border-white/15 text-[#caf300] flex items-center justify-center shrink-0 mt-1">
                    <span className="material-symbols-outlined text-[18px]">smart_toy</span>
                  </div>
                )}

                <div
                  className={`max-w-[82%] rounded-3xl p-4.5 text-sm leading-relaxed ${
                    isUser
                      ? 'bg-[#caf300] text-[#121700] font-bold rounded-tr-none shadow-[0_0_20px_rgba(202,243,0,0.25)]'
                      : 'glass-card text-[#e5e2e1] border border-white/10 rounded-tl-none font-medium'
                  }`}
                >
                  <div className="whitespace-pre-wrap">{msg.text}</div>
                  <div
                    className={`text-[10px] font-mono-data mt-2 text-right ${
                      isUser ? 'text-[#121700]/70' : 'text-[#8f9378]'
                    }`}
                  >
                    {msg.timestamp}
                  </div>
                </div>
              </div>
            );
          })}

          {isLoading && (
            <div className="flex gap-3 items-center text-xs text-[#caf300] font-mono-data">
              <div className="w-8 h-8 rounded-xl bg-[#caf300]/20 text-[#caf300] flex items-center justify-center animate-pulse">
                <span className="material-symbols-outlined text-[18px]">neurology</span>
              </div>
              <span>Agente IA analizando telemetría y formulando prescripción...</span>
            </div>
          )}
          <div ref={messagesEndRef} />
        </div>

        {/* Quick Chips */}
        <div className="px-4 py-2.5 border-t border-white/10 flex items-center gap-2 overflow-x-auto hide-scrollbar bg-white/5">
          {quickPrompts.map((prompt, idx) => (
            <button
              key={idx}
              onClick={() => handleSend(prompt)}
              className="px-3.5 py-1.5 glass-pill hover:bg-[#caf300] text-[#c5c9ac] hover:text-[#121700] rounded-full text-xs whitespace-nowrap transition-all font-mono-data font-bold"
            >
              {prompt}
            </button>
          ))}
        </div>

        {/* Input Bar */}
        <div className="p-4 border-t border-white/10 bg-white/5">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              handleSend();
            }}
            className="flex items-center gap-2.5"
          >
            <input
              type="text"
              value={inputText}
              onChange={(e) => setInputText(e.target.value)}
              placeholder="Pregunta sobre ritmos, zonas, fatiga o solicita cambios..."
              className="flex-1 glass-input rounded-2xl px-4 py-3 text-white text-sm focus:outline-none transition-all"
            />
            <button
              type="submit"
              disabled={!inputText.trim() || isLoading}
              className="p-3.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-2xl transition-all shadow-[0_0_15px_rgba(202,243,0,0.35)] disabled:opacity-40 hover:scale-105 active:scale-95"
            >
              <span className="material-symbols-outlined text-[20px] leading-none">
                send
              </span>
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};
