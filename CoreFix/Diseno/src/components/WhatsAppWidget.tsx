import React, { useState } from 'react';
import { MessageCircle, X, Send, CheckCheck, Clock, Shield } from 'lucide-react';

interface WhatsAppWidgetProps {
  isOpen: boolean;
  setIsOpen: (open: boolean) => void;
}

export const WhatsAppWidget: React.FC<WhatsAppWidgetProps> = ({
  isOpen,
  setIsOpen,
}) => {
  const [inputMsg, setInputMsg] = useState('');
  const [messages, setMessages] = useState<Array<{ sender: 'bot' | 'user'; text: string; time: string }>>([
    {
      sender: 'bot',
      text: '¡Hola! 👋 Bienvenido a TechFix Services. ¿En qué podemos ayudarte hoy?',
      time: 'Justo ahora'
    }
  ]);

  const quickOptions = [
    '📱 Presupuesto cambio de pantalla',
    '💻 Limpieza y pasta térmica de PC',
    '🎮 Reparación de puerto HDMI PS5',
    '🔍 Consultar estado de mi ticket'
  ];

  const handleSend = (textToSend?: string) => {
    const text = textToSend || inputMsg;
    if (!text.trim()) return;

    const userTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    setMessages(prev => [...prev, { sender: 'user', text, time: userTime }]);
    setInputMsg('');

    // Automated fast response
    setTimeout(() => {
      let reply = '¡Excelente! Uno de nuestros técnicos especializados está disponible. Si deseas, podemos continuar la conversación directa o agendar tu ingreso técnico.';
      if (text.toLowerCase().includes('ticket') || text.toLowerCase().includes('estado')) {
        reply = 'Para consultar tu orden al instante, también puedes usar nuestro buscador de tickets en el menú superior o facilitarnos tu número de orden aquí.';
      } else if (text.toLowerCase().includes('pantalla') || text.toLowerCase().includes('celular')) {
        reply = 'Para cambio de pantallas trabajamos con repuestos originales y demoramos entre 45 a 60 minutos con 6 meses de garantía. ¿Qué modelo de celular tienes?';
      } else if (text.toLowerCase().includes('pasta') || text.toLowerCase().includes('pc') || text.toLowerCase().includes('limpieza')) {
        reply = 'El mantenimiento térmico incluye desarmado integral, limpieza por ultrasonido, cambio de pasta Arctic/Thermal Grizzly y test de estrés con reporte.';
      }

      setMessages(prev => [
        ...prev,
        {
          sender: 'bot',
          text: reply,
          time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        }
      ]);
    }, 650);
  };

  const handleOpenDirectWhatsApp = () => {
    const defaultText = encodeURIComponent('Hola TechFix Services, quisiera consultar por una reparación de mi equipo.');
    window.open(`https://wa.me/12345678900?text=${defaultText}`, '_blank');
  };

  return (
    <>
      {/* Floating WhatsApp Action Button */}
      <div className="fixed bottom-6 right-6 z-50 flex flex-col items-end">
        
        {/* Chat Popover Window */}
        {isOpen && (
          <div className="mb-3 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in slide-in-from-bottom-5 duration-200">
            {/* Header */}
            <div className="bg-[#075e54] text-white p-4 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="relative">
                  <div className="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center font-bold text-white shadow-inner">
                    TF
                  </div>
                  <span className="absolute bottom-0 right-0 w-3 h-3 bg-emerald-400 border-2 border-[#075e54] rounded-full" />
                </div>
                <div>
                  <h4 className="font-semibold text-sm">TechFix Soporte</h4>
                  <p className="text-[11px] text-emerald-200 flex items-center gap-1">
                    <span>En línea</span> • Respuesta inmediata
                  </p>
                </div>
              </div>
              <button
                onClick={() => setIsOpen(false)}
                className="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors"
                aria-label="Cerrar chat"
              >
                <X className="w-5 h-5" />
              </button>
            </div>

            {/* Chat Body */}
            <div className="p-4 bg-[#efeae2] h-72 overflow-y-auto space-y-3 text-xs">
              <div className="bg-amber-50 text-amber-900 border border-amber-200 p-2 rounded-lg text-[11px] flex items-start gap-1.5 shadow-xs">
                <Shield className="w-3.5 h-3.5 text-amber-700 shrink-0 mt-0.5" />
                <span>Atención técnica oficial y presupuestos en el acto.</span>
              </div>

              {messages.map((m, idx) => (
                <div
                  key={idx}
                  className={`flex ${m.sender === 'user' ? 'justify-end' : 'justify-start'}`}
                >
                  <div
                    className={`max-w-[82%] rounded-xl p-2.5 shadow-xs ${
                      m.sender === 'user'
                        ? 'bg-[#d9fdd3] text-slate-800 rounded-tr-none'
                        : 'bg-white text-slate-800 rounded-tl-none'
                    }`}
                  >
                    <p className="leading-relaxed">{m.text}</p>
                    <div className="flex items-center justify-end gap-1 mt-1 text-[10px] text-slate-400">
                      <span>{m.time}</span>
                      {m.sender === 'user' && <CheckCheck className="w-3 h-3 text-blue-500" />}
                    </div>
                  </div>
                </div>
              ))}

              {/* Quick suggestions */}
              {messages.length <= 2 && (
                <div className="pt-2 space-y-1.5">
                  <p className="text-[10px] text-slate-500 font-medium">Consultas frecuentes:</p>
                  {quickOptions.map((opt, i) => (
                    <button
                      key={i}
                      onClick={() => handleSend(opt)}
                      className="block w-full text-left bg-white/90 hover:bg-white text-slate-700 p-2 rounded-lg border border-slate-200/80 text-[11px] transition-colors shadow-2xs cursor-pointer"
                    >
                      {opt}
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* Chat Input & WhatsApp external link */}
            <div className="p-3 bg-white border-t border-slate-100 space-y-2">
              <form
                onSubmit={(e) => {
                  e.preventDefault();
                  handleSend();
                }}
                className="flex items-center gap-2"
              >
                <input
                  type="text"
                  value={inputMsg}
                  onChange={(e) => setInputMsg(e.target.value)}
                  placeholder="Escribe tu consulta técnica..."
                  className="flex-1 text-xs bg-slate-100 rounded-xl px-3 py-2 border-none focus:outline-none focus:ring-2 focus:ring-emerald-500 text-slate-800"
                />
                <button
                  type="submit"
                  className="bg-[#25D366] hover:bg-[#20bd5a] text-white p-2 rounded-xl transition-colors cursor-pointer"
                  aria-label="Enviar mensaje"
                >
                  <Send className="w-4 h-4" />
                </button>
              </form>

              <button
                onClick={handleOpenDirectWhatsApp}
                className="w-full text-center py-1.5 text-[11px] font-semibold text-emerald-700 hover:text-emerald-800 hover:underline flex items-center justify-center gap-1 cursor-pointer"
              >
                <span>Abrir chat directo en la app de WhatsApp</span>
              </button>
            </div>
          </div>
        )}

        {/* Floating Green WhatsApp Button matching the image */}
        <button
          onClick={() => setIsOpen(!isOpen)}
          id="btn-whatsapp-floating"
          className="w-14 h-14 rounded-full bg-[#25D366] hover:bg-[#20bd5a] text-white flex items-center justify-center shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all duration-200 cursor-pointer group relative"
          aria-label="Contactar por WhatsApp"
          title="Chatear con un técnico por WhatsApp"
        >
          {isOpen ? (
            <X className="w-7 h-7" />
          ) : (
            <svg 
              className="w-7 h-7 fill-current" 
              viewBox="0 0 24 24"
            >
              <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>
          )}

          {/* Pulse notification dot */}
          {!isOpen && (
            <span className="absolute -top-1 -right-1 flex h-4 w-4">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-white"></span>
            </span>
          )}
        </button>

      </div>
    </>
  );
};
