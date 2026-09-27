import { useState } from 'react';
import { ArrowRight, CheckCircle2, MessageSquare, Calendar, Phone, Mail, User } from 'lucide-react';

interface ContactSectionProps {
  initialLotInterest?: string;
}

export function ContactSection({ initialLotInterest }: ContactSectionProps) {
  const [formData, setFormData] = useState({
    nombre: '',
    telefono: '',
    email: '',
    interes: initialLotInterest || 'Lote 12 (260 m²) - Manzana B',
    modalidad: 'presencial',
    fecha: '',
    mensaje: '',
  });

  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  const handleOpenWhatsApp = () => {
    const text = encodeURIComponent(
      `Hola Las Orquídeas, mi nombre es ${formData.nombre}. Estoy interesado en ${formData.interes}. Me gustaría agendar una visita (${formData.modalidad}) para la fecha ${formData.fecha || 'lo antes posible'}. Mensaje: ${formData.mensaje}`
    );
    window.open(`https://wa.me/50255555555?text=${text}`, '_blank');
  };

  return (
    <section className="py-20 relative overflow-hidden" id="contacto">
      {/* Background subtle floral glow */}
      <div className="absolute -bottom-24 -right-24 w-96 h-96 bg-brand-accent/10 rounded-full blur-3xl pointer-events-none" />
      <div className="absolute -top-24 -left-24 w-96 h-96 bg-brand-forest/10 rounded-full blur-3xl pointer-events-none" />

      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="glass-card rounded-3xl p-6 sm:p-10 md:p-12 shadow-2xl border border-white">
          <div className="text-center max-w-xl mx-auto mb-8">
            <div className="inline-flex items-center gap-2 mb-2">
              <span className="w-5 h-[2px] bg-brand-forest" />
              <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">
                ASESORÍA INMEDIATA
              </span>
              <span className="w-5 h-[2px] bg-brand-forest" />
            </div>
            <h2 className="text-3xl font-extrabold text-slate-900 tracking-tight">
              Agenda tu visita a Las Orquídeas
            </h2>
            <p className="text-slate-600 text-sm mt-2">
              Completa el formulario y un asesor inmobiliario se pondrá en contacto contigo para
              enviarte el plano de cotización y coordinar tu recorrido personalizado.
            </p>
          </div>

          {submitted ? (
            <div className="p-8 text-center bg-white/95 rounded-2xl border border-emerald-200 space-y-4 animate-fadeIn">
              <div className="w-16 h-16 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center mx-auto">
                <CheckCircle2 className="w-8 h-8 text-brand-emerald" />
              </div>
              <h3 className="text-2xl font-bold text-slate-900">¡Solicitud recibida con éxito!</h3>
              <p className="text-slate-600 text-sm max-w-md mx-auto">
                Muchas gracias, <strong>{formData.nombre}</strong>. Uno de nuestros asesores te
                contactará al <strong>{formData.telefono}</strong> con los detalles y planos de{' '}
                <strong>{formData.interes}</strong>.
              </p>

              <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                  onClick={handleOpenWhatsApp}
                  className="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20ba59] text-white px-6 py-3 rounded-full font-bold text-sm shadow-md transition cursor-pointer"
                >
                  <MessageSquare className="w-4 h-4 fill-current" />
                  <span>Chatear ahora por WhatsApp</span>
                </button>
                <button
                  onClick={() => setSubmitted(false)}
                  className="w-full sm:w-auto text-xs font-semibold text-slate-600 hover:text-slate-900 px-4 py-2 cursor-pointer"
                >
                  Enviar otra consulta
                </button>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Name */}
                <div>
                  <label
                    htmlFor="nombre"
                    className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5"
                  >
                    <User className="w-3.5 h-3.5 text-brand-forest" />
                    <span>Nombre Completo</span>
                  </label>
                  <input
                    id="nombre"
                    name="nombre"
                    type="text"
                    required
                    placeholder="Ej. Carlos Mendoza"
                    value={formData.nombre}
                    onChange={(e) => setFormData({ ...formData, nombre: e.target.value })}
                    className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none placeholder:text-slate-400"
                  />
                </div>

                {/* Phone / WhatsApp */}
                <div>
                  <label
                    htmlFor="telefono"
                    className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5"
                  >
                    <Phone className="w-3.5 h-3.5 text-brand-forest" />
                    <span>Teléfono / WhatsApp</span>
                  </label>
                  <input
                    id="telefono"
                    name="telefono"
                    type="tel"
                    required
                    placeholder="+502 5555-5555"
                    value={formData.telefono}
                    onChange={(e) => setFormData({ ...formData, telefono: e.target.value })}
                    className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none placeholder:text-slate-400"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {/* Email */}
                <div>
                  <label
                    htmlFor="email"
                    className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5"
                  >
                    <Mail className="w-3.5 h-3.5 text-brand-forest" />
                    <span>Correo Electrónico</span>
                  </label>
                  <input
                    id="email"
                    name="email"
                    type="email"
                    required
                    placeholder="tuemail@ejemplo.com"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none placeholder:text-slate-400"
                  />
                </div>

                {/* Interest Selection */}
                <div>
                  <label
                    htmlFor="interes"
                    className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                  >
                    Lote o Tamaño de Interés
                  </label>
                  <select
                    id="interes"
                    name="interes"
                    value={formData.interes}
                    onChange={(e) => setFormData({ ...formData, interes: e.target.value })}
                    className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none text-slate-700 cursor-pointer"
                  >
                    <option value="Lote 12 (260 m²) - Manzana B">
                      Lote 12 (260 m²) - Manzana B (Recomendado)
                    </option>
                    <option value="Lote 02 (210 m²) - Manzana B">
                      Lote 02 (210 m²) - Manzana B
                    </option>
                    <option value="Lote 03 (225 m²) - Manzana B">
                      Lote 03 (225 m²) - Manzana B
                    </option>
                    <option value="Lote 05 (250 m²) - Manzana B">
                      Lote 05 (250 m²) - Manzana B
                    </option>
                    <option value="Lotes entre 180 m² y 220 m²">
                      Lotes entre 180 m² y 220 m²
                    </option>
                    <option value="Lotes esquina (más de 300 m²)">
                      Lotes esquina (más de 300 m²)
                    </option>
                    <option value="Deseo cotizar financiamiento propio">
                      Deseo cotizar financiamiento propio
                    </option>
                  </select>
                </div>
              </div>

              {/* Visit Type & Preferred Date */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Modalidad de Visita
                  </label>
                  <div className="grid grid-cols-2 gap-2">
                    <button
                      type="button"
                      onClick={() => setFormData({ ...formData, modalidad: 'presencial' })}
                      className={`py-2.5 px-3 rounded-xl text-xs font-bold transition border cursor-pointer ${
                        formData.modalidad === 'presencial'
                          ? 'bg-brand-forest text-white border-brand-forest shadow-xs'
                          : 'bg-white/80 text-slate-700 border-slate-200 hover:bg-slate-100'
                      }`}
                    >
                      En Terreno (Presencial)
                    </button>
                    <button
                      type="button"
                      onClick={() => setFormData({ ...formData, modalidad: 'virtual' })}
                      className={`py-2.5 px-3 rounded-xl text-xs font-bold transition border cursor-pointer ${
                        formData.modalidad === 'virtual'
                          ? 'bg-brand-forest text-white border-brand-forest shadow-xs'
                          : 'bg-white/80 text-slate-700 border-slate-200 hover:bg-slate-100'
                      }`}
                    >
                      Asesoría Virtual (Zoom)
                    </button>
                  </div>
                </div>

                <div>
                  <label
                    htmlFor="fecha"
                    className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5"
                  >
                    <Calendar className="w-3.5 h-3.5 text-brand-forest" />
                    <span>Fecha sugerida (Opcional)</span>
                  </label>
                  <input
                    id="fecha"
                    name="fecha"
                    type="date"
                    value={formData.fecha}
                    onChange={(e) => setFormData({ ...formData, fecha: e.target.value })}
                    className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-2.5 focus:ring-2 focus:ring-brand-forest focus:outline-none text-slate-700 cursor-pointer"
                  />
                </div>
              </div>

              {/* Message */}
              <div>
                <label
                  htmlFor="mensaje"
                  className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5"
                >
                  Mensaje o Consulta Específica
                </label>
                <textarea
                  id="mensaje"
                  name="mensaje"
                  rows={3}
                  placeholder="¿Cuándo podría visitar el proyecto? ¿Tienen financiamiento a 5 años?"
                  value={formData.mensaje}
                  onChange={(e) => setFormData({ ...formData, mensaje: e.target.value })}
                  className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none placeholder:text-slate-400"
                />
              </div>

              {/* Submit Button */}
              <div className="pt-2">
                <button
                  type="submit"
                  className="w-full bg-brand-forest hover:bg-brand-forest-dark text-white py-4 px-6 rounded-2xl font-extrabold text-sm md:text-base flex items-center justify-center gap-3 shadow-lg hover:shadow-xl transition-all transform active:scale-95 cursor-pointer"
                >
                  <span>Enviar mensaje y solicitar asesoría personalizada</span>
                  <ArrowRight className="w-5 h-5 text-emerald-300" />
                </button>
              </div>

              <p className="text-center text-[11px] text-slate-500 pt-2">
                🔒 Tus datos están protegidos. No compartimos tu información con terceros.
              </p>
            </form>
          )}
        </div>
      </div>
    </section>
  );
}
