import { ArrowRight, Heart, Lightbulb, PawPrint, Quote } from 'lucide-react';

export default function Home() {
  return (
    <div className="space-y-12">
      {/* Hero Section: Historia destacada del día */}
          <section className="relative w-full aspect-[21/9] min-h-[400px] rounded-xl overflow-hidden group cursor-pointer shadow-lg">
            <img
              alt="Main Story"
              className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
              src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?q=80&w=1200&auto=format&fit=crop"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
            <div className="absolute bottom-0 left-0 p-8 md:p-12 w-full md:w-2/3">
              <div className="status-frost text-white px-4 py-1 rounded-full inline-block mb-4 font-headline text-sm font-semibold tracking-wide uppercase">
                Rescate destacado
              </div>
              <h1 className="font-headline text-4xl md:text-6xl font-extrabold text-white mb-4 tracking-tight leading-tight">
                Una Nueva Oportunidad para Max
              </h1>
              <p className="text-white/90 text-lg md:text-xl font-light mb-6 max-w-xl">
                Tras recibir una denuncia ciudadana, el equipo de AppUBA logró rescatar y rehabilitar a este perrito en situación de abandono.
              </p>
              <button className="bg-gradient-to-r from-primary to-primary-container text-white px-8 py-4 rounded-xl font-headline font-bold shadow-lg hover:scale-105 transition-transform">
                Leer Historia Completa
              </button>
            </div>
          </section>

      {/* Bento Grid: Convivencia & Galería */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
        {/* Consejos de convivencia */}
        <div className="md:col-span-8 flex flex-col gap-8">
          <div className="flex justify-between items-end">
            <div>
              <h2 className="font-headline text-3xl font-extrabold text-primary tracking-tight">Convivencia Urbana</h2>
              <p className="text-outline font-body">Guía práctica para proteger nuestra biodiversidad local.</p>
            </div>
            <button className="text-primary font-bold flex items-center gap-1 hover:underline">
              Ver todos <ArrowRight className="w-4 h-4" />
            </button>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6">
            {/* Tip Card 1 */}
            <div className="glass-card p-6 rounded-xl shadow-sm border border-outline-variant/10 hover:shadow-md transition-shadow">
              <div className="w-12 h-12 rounded-full bg-secondary-container/20 flex items-center justify-center text-secondary mb-4">
                <Lightbulb className="w-6 h-6" />
              </div>
              <h3 className="font-headline text-xl font-bold mb-2">Iluminación Responsable</h3>
              <p className="text-on-surface-variant font-body leading-relaxed">
                Reduce la contaminación lumínica en tu jardín para ayudar a las aves migratorias y polinizadores nocturnos.
              </p>
            </div>
            {/* Tip Card 2 */}
            <div className="glass-card p-6 rounded-xl shadow-sm border border-outline-variant/10 hover:shadow-md transition-shadow">
              <div className="w-12 h-12 rounded-full bg-primary-container/20 flex items-center justify-center text-primary mb-4">
                <PawPrint className="w-6 h-6" />
              </div>
              <h3 className="font-headline text-xl font-bold mb-2">Fauna en el Jardín</h3>
              <p className="text-on-surface-variant font-body leading-relaxed">
                Crea refugios naturales con troncos y rocas para pequeños reptiles que controlan plagas de forma natural.
              </p>
            </div>
          </div>

          {/* Rescate Destacado (Horizontal) */}
          <div className="relative rounded-xl overflow-hidden aspect-[16/6] min-h-[200px] shadow-xl group cursor-pointer">
            <img
              alt="Rescate"
              className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
              src="https://images.unsplash.com/photo-1543466835-00a7907e9de1?q=80&w=800&auto=format&fit=crop"
            />
            <div className="absolute inset-0 bg-black/30 backdrop-blur-[2px] group-hover:backdrop-blur-0 transition-all"></div>
            <div className="absolute inset-0 p-8 flex flex-col justify-end">
              <span className="bg-white/90 text-primary px-3 py-1 rounded-full text-xs font-bold w-fit mb-2 uppercase tracking-widest">Éxito en Rescate</span>
              <h3 className="text-white font-headline text-2xl font-bold">Un nuevo comienzo para nuestros amigos de cuatro patas</h3>
            </div>
          </div>
        </div>

        {/* Galería de Rescates (Vertical Sidebar) */}
        <div className="md:col-span-4 flex flex-col gap-6">
          <h2 className="font-headline text-2xl font-extrabold text-primary tracking-tight">Galería de Rescates</h2>
          <div className="space-y-6">
            {/* Gallery Item */}
            <div className="group relative rounded-xl overflow-hidden aspect-square shadow-md">
              <img
                alt="Rescue 1"
                className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                src="https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?q=80&w=800&auto=format&fit=crop"
              />
              <div className="absolute bottom-0 left-0 right-0 p-4 glass-card m-3 rounded-lg flex justify-between items-center translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all">
                <div>
                  <p className="text-[10px] font-bold text-primary uppercase tracking-tighter">Felinos</p>
                  <p className="text-sm font-bold">Recuperación Exitosa</p>
                </div>
                <Heart className="text-primary w-5 h-5" />
              </div>
            </div>
            {/* Gallery Item 2 */}
            <div className="group relative rounded-xl overflow-hidden aspect-square shadow-md">
              <img
                alt="Rescue 2"
                className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                src="https://images.unsplash.com/photo-1552728089-57168bb3e003?q=80&w=800&auto=format&fit=crop"
              />
              <div className="absolute bottom-0 left-0 right-0 p-4 glass-card m-3 rounded-lg flex justify-between items-center translate-y-2 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all">
                <div>
                  <p className="text-[10px] font-bold text-primary uppercase tracking-tighter">Aves</p>
                  <p className="text-sm font-bold">Vuelo de Libertad</p>
                </div>
                <Heart className="text-primary w-5 h-5" />
              </div>
            </div>
          </div>
          <button className="w-full py-4 bg-surface-container-low text-primary font-headline font-bold rounded-xl border border-primary/10 hover:bg-surface-container-high transition-colors">
            Explorar Galería Completa
          </button>
        </div>
      </div>

      {/* Featured Quote / Mission */}
      <section className="mt-20 py-16 px-8 rounded-xl bg-primary text-white text-center relative overflow-hidden shadow-2xl">
        <div className="absolute top-0 right-0 p-12 opacity-10">
          <Quote className="w-48 h-48" />
        </div>
        <div className="relative z-10 max-w-2xl mx-auto">
          <h2 className="font-headline text-3xl md:text-4xl font-extrabold mb-6 italic">"La naturaleza no es un lugar para visitar. Es nuestro hogar."</h2>
          <div className="w-16 h-1 bg-white/30 mx-auto mb-6 rounded-full"></div>
          <p className="text-primary-fixed-dim text-lg">AppUBA trabaja cada día para educar, proteger y restaurar el vínculo entre la humanidad y el reino animal en Guatemala.</p>
        </div>
      </section>
    </div>
  );
}
