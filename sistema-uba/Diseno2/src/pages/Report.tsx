import { ArrowRight, ShieldCheck, HelpCircle, BookOpen } from 'lucide-react';

export default function Report() {
  return (
    <div className="space-y-12">
      {/* Hero Section & CTA */}
      <section className="relative rounded-xl overflow-hidden min-h-[400px] flex items-end p-8 md:p-12 mb-16">
        <div className="absolute inset-0 z-0">
          <img
            alt="Wildlife Conservation"
            className="w-full h-full object-cover"
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuBD5Yna2AYVSXCgmEgm2Y-2233xW4qgj2bFCrsOqaF2XY2iv4AaShAcfgPMVddtMoQjaw4L4ioeMN691SdHb2YTYY6qkCW7hpz3Qkxr9x6CR2_sqOeqOrhh2RQl4SjuM5F5DUxV1EpaXFTcyxk0GgsRnvRIXuSOw5yIUbMbpgdnEVx4dVKbNNbLshpmhbceLxoPlj9ACpyGMH47R6wiA7nTabwiLJWLwiC9yC3pNdyR1PMYv1bAilSfj8L1cj-t6mgbErPFxRm4FxA"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
        </div>
        <div className="relative z-10 w-full md:flex md:items-end md:justify-between gap-12">
          <div className="max-w-xl">
            <span className="inline-block px-4 py-1 rounded-full bg-secondary-container/80 backdrop-blur-md text-on-secondary-container text-xs font-bold uppercase tracking-widest mb-4">Acción Ciudadana</span>
            <h2 className="text-4xl md:text-5xl font-extrabold text-white font-headline leading-tight mb-4">Tu voz protege a los que no tienen una.</h2>
            <p className="text-white/80 text-lg font-light leading-relaxed mb-8">Cada reporte es una oportunidad de salvar una vida silvestre en peligro. Aprende a identificar riesgos y actúa hoy.</p>
          </div>
          <button className="primary-gradient text-white px-8 py-4 rounded-xl font-bold shadow-lg shadow-primary/20 flex items-center gap-3 active:scale-95 transition-all group shrink-0">
            <span>Iniciar Reporte Ciudadano</span>
            <ArrowRight className="w-5 h-5 group-hover:translate-x-1 transition-transform" />
          </button>
        </div>
      </section>

      {/* Section 1: Cómo identificar un animal en riesgo */}
      <section>
        <div className="mb-8">
          <h3 className="text-3xl font-bold font-headline text-blue-900 tracking-tight">Cómo identificar un animal en riesgo</h3>
          <p className="text-on-surface-variant mt-2 max-w-2xl">Observa estos patrones de comportamiento o entorno que podrían indicar que un animal necesita ayuda profesional.</p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Case: Aves */}
          <div className="glass-card rounded-xl p-6 border border-white/20 shadow-sm flex flex-col md:flex-row gap-6">
            <div className="w-full md:w-32 h-32 rounded-lg overflow-hidden shrink-0">
              <img
                alt="Bird in need"
                className="w-full h-full object-cover"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBc-gDZYAgxrlMAJYhm5xIfmUGQm8lUL4A5kNmPCCvTk_VxC1R_Bc0HbShk4pxem9xdQeXbhzx-Xrv64kbadZ7IgSQZIjmfggcydyVGfH9kH6CYbJoB7hQP-cYrnohgVrhtwMpg30NYi1dWEaCAFP7g4_6hPgOPNFYvOml2Anw80FYk2JMZ2erUJOJUJem44BtmrDEUSIvOgZiuf7MRNKE0TwvgnCRYhxBxTcLg7LmqQ-Y4YN0hwLP3K7Jn_jBl8h8wN3h5Q0wgFKY"
              />
            </div>
            <div className="space-y-3">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-secondary"></span>
                <span className="text-xs font-bold font-label uppercase tracking-widest text-secondary">Aves Silvestres</span>
              </div>
              <h4 className="text-xl font-bold font-headline text-on-surface leading-tight">Incapacidad de vuelo</h4>
              <p className="text-sm text-on-surface-variant leading-relaxed">Alas caídas, plumaje extremadamente erizado o si el ave no se inmuta ante la presencia humana cercana.</p>
            </div>
          </div>
          {/* Case: Mamíferos */}
          <div className="glass-card rounded-xl p-6 border border-white/20 shadow-sm flex flex-col md:flex-row gap-6">
            <div className="w-full md:w-32 h-32 rounded-lg overflow-hidden shrink-0">
              <img
                alt="Small mammal"
                className="w-full h-full object-cover"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuAVoqRxW18EdTZ-Crxlkv--8viOnA4Q_KILFRXsdfwVoogo6VltpkzXeY-DljO-kS60h3R3wii7XiydGruv_jV-vVHDF24fCtUFV6YluWRAaMY57Zy2H7vbYDGiWXcbVuSyaY1K8qxtwW1J3nOkmwYo9cD5xWV1CVLwkuiDD6Q1lgcbcm0lj15erfqPc3zVJkfcVentbZUOHXHXwdsaA7XeXSwUr3XFPoXSDn7Mah9zwk34LpYhJ-d-bRQEOJSBV5i2FsC7KgC8UoY"
              />
            </div>
            <div className="space-y-3">
              <div className="flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-secondary"></span>
                <span className="text-xs font-bold font-label uppercase tracking-widest text-secondary">Mamíferos Pequeños</span>
              </div>
              <h4 className="text-xl font-bold font-headline text-on-surface leading-tight">Desorientación diurna</h4>
              <p className="text-sm text-on-surface-variant leading-relaxed">Animales nocturnos (como zarigüeyas) vistos a plena luz del día mostrando confusión o movimientos erráticos.</p>
            </div>
          </div>
        </div>
      </section>

      {/* Section 2: Pasos para una denuncia efectiva */}
      <section className="bg-surface-container-low rounded-xl p-8 md:p-12 relative overflow-hidden">
        <div className="absolute top-0 right-0 p-8 opacity-10">
          <ShieldCheck className="w-32 h-32" />
        </div>
        <div className="mb-12 relative z-10">
          <h3 className="text-3xl font-bold font-headline text-blue-900 tracking-tight">Pasos para una denuncia efectiva</h3>
          <p className="text-on-surface-variant mt-2">Sigue este protocolo para asegurar que tu reporte sea procesado con rapidez y precisión.</p>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8 relative z-10">
          <div className="space-y-4">
            <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xl">1</div>
            <h5 className="font-bold text-lg font-headline">Registro Visual</h5>
            <p className="text-sm text-on-surface-variant leading-relaxed">Toma fotos o videos cortos desde una distancia segura. No intentes tocar al animal sin equipo de protección.</p>
          </div>
          <div className="space-y-4">
            <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xl">2</div>
            <h5 className="font-bold text-lg font-headline">Ubicación Precisa</h5>
            <p className="text-sm text-on-surface-variant leading-relaxed">Activa el GPS de tu dispositivo o identifica puntos de referencia cercanos para facilitar el rescate.</p>
          </div>
          <div className="space-y-4">
            <div className="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold text-xl">3</div>
            <h5 className="font-bold text-lg font-headline">Detalles del Caso</h5>
            <p className="text-sm text-on-surface-variant leading-relaxed">Describe brevemente lo que observas: heridas visibles, comportamiento o amenazas inmediatas (perros, tráfico).</p>
          </div>
        </div>
      </section>

      {/* Additional Resources: "Status Frost" Style */}
      <section className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="p-6 rounded-xl border border-outline-variant/15 flex items-start gap-4 hover:bg-surface-container-highest/50 transition-colors">
          <div className="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center shrink-0">
            <HelpCircle className="text-primary-container w-6 h-6" />
          </div>
          <div>
            <h6 className="font-bold font-headline mb-1">¿Necesitas ayuda inmediata?</h6>
            <p className="text-sm text-on-surface-variant">Llama a nuestra línea de emergencia 24/7 para casos críticos que requieren intervención instantánea.</p>
            <button className="mt-4 text-primary font-bold text-sm flex items-center gap-1 hover:underline">
              Contactar Centro de Rescate
            </button>
          </div>
        </div>
        <div className="p-6 rounded-xl border border-outline-variant/15 flex items-start gap-4 hover:bg-surface-container-highest/50 transition-colors">
          <div className="w-12 h-12 rounded-full bg-secondary-container/20 flex items-center justify-center shrink-0">
            <BookOpen className="text-secondary w-6 h-6" />
          </div>
          <div>
            <h6 className="font-bold font-headline mb-1">Guía de Primeros Auxilios</h6>
            <p className="text-sm text-on-surface-variant">Descarga nuestro manual para saber qué hacer mientras esperas a los especialistas en fauna silvestre.</p>
            <button className="mt-4 text-secondary font-bold text-sm flex items-center gap-1 hover:underline">
              Descargar Manual PDF
            </button>
          </div>
        </div>
      </section>
    </div>
  );
}
