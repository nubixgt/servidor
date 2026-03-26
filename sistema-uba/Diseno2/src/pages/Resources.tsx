import { ArrowRight, BriefcaseMedical, Droplet, Map, MapPin, Phone, Siren } from 'lucide-react';

export default function Resources() {
  return (
    <div className="space-y-12">
      {/* Hero Section / Context */}
      <section className="mb-12">
        <h1 className="text-4xl md:text-5xl font-extrabold text-primary mb-4 tracking-tight leading-tight">Recursos y Servicios</h1>
        <p className="text-on-surface-variant text-lg max-w-2xl leading-relaxed">
          Información esencial y herramientas de contacto inmediato para proteger la vida silvestre y garantizar el bienestar animal en cualquier situación.
        </p>
      </section>

      {/* Bento Grid: Main Services */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-6 mb-12">
        {/* Veterinarios 24h (Priority Card) */}
        <div className="md:col-span-8 group relative overflow-hidden rounded-xl glass-panel p-8 shadow-[0_12px_32px_rgba(0,105,112,0.05)] border border-white/20 transition-all hover:scale-[1.01]">
          <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
              <div className="flex items-center gap-2 mb-2">
                <span className="flex items-center justify-center w-10 h-10 rounded-full bg-secondary-container/20 text-secondary">
                  <Siren className="w-5 h-5" />
                </span>
                <span className="text-sm font-bold tracking-widest uppercase text-secondary">Urgencias</span>
              </div>
              <h2 className="text-3xl font-bold text-on-surface">Veterinarios 24h</h2>
            </div>
            <button className="primary-gradient text-white px-6 py-3 rounded-xl font-bold shadow-lg flex items-center gap-2">
              <Phone className="w-5 h-5" />
              Llamar ahora
            </button>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div className="p-4 rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors">
              <h3 className="font-bold text-primary mb-1">Clínica Central Fauna</h3>
              <p className="text-sm text-on-surface-variant mb-3">Especialistas en especies exóticas y silvestres.</p>
              <div className="flex items-center text-xs font-semibold text-secondary">
                <MapPin className="w-4 h-4 mr-1" />
                A 2.4 km de ti
              </div>
            </div>
            <div className="p-4 rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors">
              <h3 className="font-bold text-primary mb-1">Hospital Vet-H24</h3>
              <p className="text-sm text-on-surface-variant mb-3">Atención inmediata para atropellos y traumas.</p>
              <div className="flex items-center text-xs font-semibold text-secondary">
                <MapPin className="w-4 h-4 mr-1" />
                A 5.1 km de ti
              </div>
            </div>
          </div>
        </div>

        {/* Centros de Rescate (Vertical Card) */}
        <div className="md:col-span-4 rounded-xl glass-panel overflow-hidden shadow-[0_12px_32px_rgba(0,105,112,0.05)] border border-white/20">
          <div className="h-48 relative">
            <img
              alt="Rescue Center"
              className="w-full h-full object-cover"
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuCA8VSuJJxeVrYOH4rySAh-yD-VfCCX3xMqUU5_wUfGiySkA4BZy04Or4PAwBVl_C7qyFfprTITf_xeWUvdjd927XrPlFr2KFRnPaIQgYEY832mVjz8rGLXJVXaE7DIXKJ7yTfvTPb5eKRaSdw_E0TLKZmUNXvelQPg60RQ6IJlEFR145XnnFccqVb32UF-l0rVW4haDXIsWUY2PMUBkN95vZaHFSjOaJYTItSOLgS07YNKzUcWbLfRyt1Fof7zxxUfqCieweek6pY"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-primary/80 to-transparent"></div>
            <div className="absolute bottom-4 left-4">
              <h2 className="text-xl font-bold text-white">Centros de Rescate</h2>
            </div>
          </div>
          <div className="p-6">
            <p className="text-sm text-on-surface-variant mb-4">Directorio de santuarios y centros de recuperación oficiales por región.</p>
            <ul className="space-y-3">
              <li className="flex items-center justify-between p-2 rounded-lg hover:bg-surface-container-low transition-colors group cursor-pointer">
                <span className="text-sm font-medium">Santuario del Norte</span>
                <ArrowRight className="text-primary w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" />
              </li>
              <li className="flex items-center justify-between p-2 rounded-lg hover:bg-surface-container-low transition-colors group border-t border-outline-variant/10 cursor-pointer">
                <span className="text-sm font-medium">Refugio Tierralibre</span>
                <ArrowRight className="text-primary w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity" />
              </li>
            </ul>
          </div>
        </div>

        {/* Mapas de Zonas Protegidas (Wide/Asymmetric) */}
        <div className="md:col-span-12 lg:col-span-7 bg-surface-container-low rounded-xl p-8 flex flex-col md:flex-row gap-8 items-center overflow-hidden">
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-4">
              <Map className="text-primary w-6 h-6" />
              <h2 className="text-2xl font-bold">Zonas Protegidas</h2>
            </div>
            <p className="text-on-surface-variant mb-6">Consulta los límites de parques nacionales, reservas biológicas y corredores ecológicos activos.</p>
            <div className="flex flex-wrap gap-2">
              <span className="px-4 py-2 bg-white rounded-full text-xs font-bold text-primary shadow-sm">GPS Tiempo Real</span>
              <span className="px-4 py-2 bg-white rounded-full text-xs font-bold text-primary shadow-sm">Offline Disponible</span>
            </div>
          </div>
          <div className="w-full md:w-64 h-48 rounded-xl overflow-hidden shadow-xl border-4 border-white rotate-2">
            <img
              alt="Protected zones map"
              className="w-full h-full object-cover"
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuBY9tQGcOaiIJgx7zJ8XiKxW3MDE-YB86Di0Faf58qnhA6fGvJEF5SmRaBDgSevHPVULhQJcfQ09wOFpyayLtbu1a3pw9qmmhH4VndQ9noxp-f6gTl4s9rDmrRwyFdFkjsv73CKaBERBlyYMud7D1TrTstvuzkQnFpi67BmM-XWWWACFm5_bXmo3DPIad97c-Hx6P9y4ew2Lv8W7eU_s3cKp3p_H3Zv4pRQanAwJ-KQKjw9WDOMx0L84F1jRSfFwCSTg7qFng-jPb8"
            />
          </div>
        </div>

        {/* Guías de primeros auxilios (Compact/Focus) */}
        <div className="md:col-span-12 lg:col-span-5 primary-gradient rounded-xl p-8 text-white relative overflow-hidden">
          <div className="relative z-10">
            <h2 className="text-2xl font-bold mb-4">Primeros Auxilios</h2>
            <p className="text-white/80 mb-6 text-sm leading-relaxed">Guías interactivas paso a paso para estabilizar animales heridos antes de que llegue la ayuda profesional.</p>
            <div className="space-y-3">
              <div className="bg-white/10 backdrop-blur-md p-4 rounded-xl flex items-center gap-4 hover:bg-white/20 transition-colors cursor-pointer">
                <BriefcaseMedical className="w-6 h-6" />
                <span className="font-semibold">Protocolo de transporte</span>
              </div>
              <div className="bg-white/10 backdrop-blur-md p-4 rounded-xl flex items-center gap-4 hover:bg-white/20 transition-colors cursor-pointer">
                <Droplet className="w-6 h-6" />
                <span className="font-semibold">Hidratación de emergencia</span>
              </div>
            </div>
          </div>
          {/* Abstract Glass decoration */}
          <div className="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
        </div>
      </div>

      {/* Featured Resource / Editorial Section */}
      <section className="mt-16">
        <div className="flex items-end justify-between mb-8">
          <div>
            <span className="text-primary font-bold uppercase tracking-widest text-xs mb-2 block">Biblioteca</span>
            <h2 className="text-3xl font-extrabold">Recursos Destacados</h2>
          </div>
          <span className="text-primary font-bold text-sm cursor-pointer hover:underline">Ver todo</span>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
          {/* Resource 1 */}
          <div className="group">
            <div className="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative shadow-lg">
              <img
                alt="Tortoise rescue"
                className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuD3TbHlbe3JFj47JnLbj--iJXtKzHIUqUg9t9MPjBWCsqJRZCZwZMXOmkWW3htIILcpxYQ8ve1_rysrYuZtWwvGpMfVBBlwnmeuz9_SWlBzLlm9IBDMzX6j1AGpuj1_IUwuh-tysi0j7B8oOfdZEb8VitkXE3E4eoMBnBZk7WxIhFemG8A1UyePiTULqa3TdXSuYkRE9Q7RhWrsI7YM0nxzkrtn6NYEP0SWytpRQ7txYNUNsPEj4K2mu0Ba5xwwcOBc1yJaZi7t2mA"
              />
              <div className="absolute top-4 left-4">
                <span className="bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-bold text-primary uppercase">Guía PDF</span>
              </div>
            </div>
            <h3 className="font-bold text-xl mb-2 group-hover:text-primary transition-colors">Protección de Quelonios</h3>
            <p className="text-sm text-on-surface-variant line-clamp-2">Aprende a identificar nidos y cómo actuar ante el avistamiento de tortugas marinas en zonas urbanas.</p>
          </div>
          {/* Resource 2 */}
          <div className="group">
            <div className="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative shadow-lg">
              <img
                alt="Bird care"
                className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBHyttRB0X25EPVlFUWmGfpSYA6N_d7icgUdOh9z0iFelakS2NWgaSVfE7qWD4hrWtTjr0RxIIpZum3CgsvO2EAswYDxielZkNjq76LsQReo9DFxorHYOtnWMxSX0pwgmMZ_kh_i7e6942-NkksHPklrSgtMK9jTOYw0JR5ckPeJTPt0i5YsbQ7l945PZZkkO7bdqB6ZXjGA6AcuhFSJSIz1RXaf12IuHaNXFmbeHaHUNA_y4GJOjdckPKLshBArtD1Bnew4bZhb1s"
              />
              <div className="absolute top-4 left-4">
                <span className="bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-bold text-primary uppercase">Video Tutorial</span>
              </div>
            </div>
            <h3 className="font-bold text-xl mb-2 group-hover:text-primary transition-colors">Aves Caídas del Nido</h3>
            <p className="text-sm text-on-surface-variant line-clamp-2">¿Cuándo intervenir y cuándo dejar que la naturaleza siga su curso? Guía visual para rescate de polluelos.</p>
          </div>
          {/* Resource 3 */}
          <div className="group">
            <div className="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative shadow-lg">
              <img
                alt="Map landscape"
                className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBOD4Czb12cmCBkprvhJ7IrSJQUvfe4DK1igwDwcSLVdUzWVHKMHzzWZ5ZRQytUpJdWb5tNAwaTExtzevPPq4p06JFPZnVjtc-aOwOVW7Fsf7G6TkrSpQG4bamRSeytehCVVmP1Wb_SRFdF4AZlpdoRgME43IkkFGkfk4_Ys-Ia6UjV1WQMVVJBVsnCUTAjybj9FqmlgkdVVISDNYv9tGer6E0IcunSLGQCj1edGeo4efvMf0QCcWZErHvohdqkzLE7Kfb_tS946ac"
              />
              <div className="absolute top-4 left-4">
                <span className="bg-white/80 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-bold text-primary uppercase">Directorio</span>
              </div>
            </div>
            <h3 className="font-bold text-xl mb-2 group-hover:text-primary transition-colors">Mapa de Corredores</h3>
            <p className="text-sm text-on-surface-variant line-clamp-2">Base de datos colaborativa sobre los principales corredores biológicos y puntos de colisión frecuentes.</p>
          </div>
        </div>
      </section>
    </div>
  );
}
