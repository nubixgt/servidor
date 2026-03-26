import { ArrowRight } from 'lucide-react';

export default function News() {
  return (
    <div className="space-y-12">
      {/* Editorial Header Section */}
      <section className="mb-12">
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div className="max-w-2xl">
            <span className="font-headline font-bold uppercase tracking-widest text-primary text-xs mb-3 block">Explora el Ecosistema</span>
            <h2 className="text-4xl md:text-6xl font-headline font-extrabold tracking-tight text-on-surface leading-tight">
              Crónicas de la <span className="text-gradient-primary">Vida Silvestre</span>
            </h2>
          </div>
          <div className="flex gap-2">
            <span className="bg-surface-container-high px-4 py-2 rounded-full text-sm font-semibold text-on-surface-variant">Mayo 2024</span>
          </div>
        </div>
      </section>

      {/* Featured News Bento Grid */}
      <div className="grid grid-cols-1 md:grid-cols-12 gap-6 mb-12">
        {/* Main Featured Article */}
        <article className="md:col-span-8 relative group overflow-hidden rounded-xl bg-surface-container-lowest">
          <div className="aspect-[16/10] overflow-hidden">
            <img
              className="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
              alt="Lion"
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuCHNfKqju7FrEltSB3vtAL59XzHqX_gFLzf5EpI_YtcknRZ4JRYm6UAXQ_F6Ovfr1viNer2InBhK3LDHGMlkwfQvRQtvYEILJCDkc8CIAOz7gb3YK2YwGsoLKhPtTaYPulEb1yi3GTr38gfGR544b-7RXaKRF8Z0jKueImO6qJVjzkrnqFxNJqY75uiRxZYesTy326FasE1rUjVbimsPoIphcORuWy5bAjL0Kddn0m4Ryxj4VwuC6Ji0KFw0jlOaE6xjPLCnShfd1E"
            />
          </div>
          <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
          <div className="absolute bottom-0 left-0 p-8 w-full">
            <div className="glass-panel inline-flex items-center px-3 py-1 rounded-full mb-4">
              <span className="w-2 h-2 rounded-full bg-secondary-container mr-2"></span>
              <span className="text-[10px] font-bold uppercase tracking-tighter text-on-surface">Legislación Urgente</span>
            </div>
            <h3 className="text-2xl md:text-4xl font-headline font-bold text-white mb-4 leading-tight">
              Nueva Ley de Protección de Corredores Biológicos: Un Hito para la Fauna Local
            </h3>
            <p className="text-white/80 max-w-xl text-lg mb-6 line-clamp-2">
              El parlamento aprueba por unanimidad la protección de rutas migratorias críticas, asegurando el futuro de más de 40 especies en peligro.
            </p>
            <button className="bg-gradient-to-br from-primary to-primary-container text-white px-8 py-3 rounded-xl font-bold transition-transform active:scale-95 shadow-lg shadow-primary/20">
              Leer artículo completo
            </button>
          </div>
        </article>

        {/* Side Feature 1 */}
        <article className="md:col-span-4 flex flex-col gap-6">
          <div className="flex-1 rounded-xl overflow-hidden relative group bg-surface-container-low min-h-[200px]">
            <img
              className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
              alt="Sea Turtle"
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuDL4RHYV2RV9zTxPVgKO8AJkGExz3JMDsok1aE_BGGutCzGE-1gvhcOnaK3ZlnN4nhLHCs-Jx-XAcOWDBHCZf1rbosppaisuSjJBiqYM5KnLNV9z86V20NovsDrI5wH5gMF74HHzWGF130Vex61-CLLVR2Ky7r9umttn3gkbMpod0ItD_Yk2gIEcDqEZeCeIlFqasLBhP0u7M7Grg9ggkXSJy34kSQik2c0rBoVdskc3Fk5HAPPOhZbUt-g-hICsw6JnrgiKc9oQOI"
            />
            <div className="absolute inset-0 bg-primary/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 backdrop-blur-sm flex items-center justify-center">
              <span className="text-white font-bold text-lg">Ver Evento</span>
            </div>
            <div className="absolute bottom-0 left-0 p-6 w-full glass-panel">
              <span className="text-xs font-bold text-primary mb-1 block uppercase">Voluntariado</span>
              <h4 className="text-lg font-headline font-bold text-on-surface leading-snug">Limpieza de Costas: Únete este Domingo en Playa Verde</h4>
            </div>
          </div>
          <div className="flex-1 rounded-xl overflow-hidden relative group bg-surface-container-low min-h-[200px]">
            <img
              className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
              alt="Dog"
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuAJByldComikbM38FnFBFAut-4C5mttVRWShGUDC8GGbaYlSnlm3p1Ejm5gITKmHtDWdcpLeMov3fxw4DSMVo2yThsA_baRuhuD3uEhErD4gehLfBMvWJdemn66D1tSLNuaUbY1UMZkwEbFF47BtL-atkm_UARhJh8n7IPIvLjSg9udRiVxm9zK-x2fb2M-a5mwkOTZ_q11B9rI3ktvaMn4YnWkMYB9bjwKkJ3a9Pac6c2aMiVsNgcxtHKxuSlZkIs9g7vCmuKY9wM"
            />
            <div className="absolute inset-0 bg-primary/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 backdrop-blur-sm flex items-center justify-center">
              <span className="text-white font-bold text-lg">Saber Más</span>
            </div>
            <div className="absolute bottom-0 left-0 p-6 w-full glass-panel">
              <span className="text-xs font-bold text-primary mb-1 block uppercase">Salud Animal</span>
              <h4 className="text-lg font-headline font-bold text-on-surface leading-snug">Campaña de Vacunación Gratuita para Mascotas Comunitarias</h4>
            </div>
          </div>
        </article>
      </div>

      {/* Section: Discovery Feed */}
      <section className="mb-12">
        <div className="flex items-center justify-between mb-8">
          <h3 className="text-2xl font-headline font-bold text-on-surface">Descubrimientos Recientes</h3>
          <div className="h-[2px] flex-grow mx-8 bg-surface-container-high rounded-full"></div>
          <button className="text-primary font-bold hover:underline flex items-center gap-2">
            Ver todos <ArrowRight className="w-4 h-4" />
          </button>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
          {/* Discovery Card 1 */}
          <div className="group">
            <div className="rounded-xl overflow-hidden mb-4 aspect-square bg-surface-container-low shadow-sm transition-all duration-300 group-hover:shadow-xl group-hover:-translate-y-1">
              <img
                className="w-full h-full object-cover"
                alt="Bird"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuAPWqp_CWB_fJprbJts-ppkGa5nwcVBZR4mP_CVIe1dwZeAiwA8O4OxAnq7BPxWlFNnx-4KblDrm6ESJZS4hcLpm5y5Lzn3YQvcni6n9Kke1Y7r4kMFJL5wrGnrmZXDenk0dECaWzbvNSZ1we6xyT6lZ3fCdzEw-yuabkIUftBcwu_k1s6L5tiFAP1F1_4oR4qA-CsD1h7plm4AguP4ypLvp9-VOKEKgPmgGCdUS7DYrtVicLcIO1jXok-0bJeZ8CNTfsgGQUxtWc8"
              />
            </div>
            <div className="flex items-center gap-2 mb-2">
              <span className="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Ornitología</span>
              <span className="text-outline text-[10px] font-medium uppercase tracking-widest">Hace 2 horas</span>
            </div>
            <h4 className="text-xl font-headline font-bold text-on-surface group-hover:text-primary transition-colors leading-tight mb-2">Avistamiento Raro: El regreso del Quetzal de Montaña</h4>
            <p className="text-on-surface-variant text-sm line-clamp-3">Científicos locales registran por primera vez en una década la anidación de esta especie emblemática en la reserva norte.</p>
          </div>
          {/* Discovery Card 2 */}
          <div className="group">
            <div className="rounded-xl overflow-hidden mb-4 aspect-square bg-surface-container-low shadow-sm transition-all duration-300 group-hover:shadow-xl group-hover:-translate-y-1">
              <img
                className="w-full h-full object-cover"
                alt="River"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuCMO-pflygLmfGuqGa4Z3n6iJBtjvAZpW1GFZJuJODdxLR_tD7mEbXbaOWVXcg1InuGJy-vny0F7zXM-Gmxx2K0itnmmDjuCCe4udDAaF4OOdwB8UvUMeVmv0vK5stZx_QTuEpE8o8xdNmUwX6bie6wj1alpaA_px6zSLopBDbB46FffTfMlYsiDG8VDHCSgyFwEtczd5OcQiifDWaaQ-08hqwIwiO0Vju427uZASsNoMxG_N6D3eo2l5PHQ0oWfNKfoKDxITZ44Zw"
              />
            </div>
            <div className="flex items-center gap-2 mb-2">
              <span className="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Ecología</span>
              <span className="text-outline text-[10px] font-medium uppercase tracking-widest">Hace 5 horas</span>
            </div>
            <h4 className="text-xl font-headline font-bold text-on-surface group-hover:text-primary transition-colors leading-tight mb-2">Restauración de Humedales: El Agua Vuelve a Fluir</h4>
            <p className="text-on-surface-variant text-sm line-clamp-3">El proyecto de recuperación hídrica muestra sus primeros resultados positivos atrayendo garzas y anfibios locales.</p>
          </div>
          {/* Discovery Card 3 */}
          <div className="group">
            <div className="rounded-xl overflow-hidden mb-4 aspect-square bg-surface-container-low shadow-sm transition-all duration-300 group-hover:shadow-xl group-hover:-translate-y-1">
              <img
                className="w-full h-full object-cover"
                alt="Fox"
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBh2_3H0oASrnIAWmcDEfFM669g5rqeGdsjZMNzzeaspSEihQNWPPAue3Bz0gzjqJXVEVoV3Qh_QGGBQSbIWnUfwvHJZ_9OBARSL6V_-FVGt4FdCCW-oAg_GL_wheZ5H2yI_5tPSlx0CzkxtH3ipoywvVbKBOsS-ulzUf86cH3HiyidR66ZmhN8-P0xCvLIsedcqBnbJUFB74KraQKfWV_0YzrdaqEQie7-vePcyucj25UoG8dBvOLcCrDsnapHpC0Utmmk4xKdvHk"
              />
            </div>
            <div className="flex items-center gap-2 mb-2">
              <span className="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full uppercase">Fauna Urbana</span>
              <span className="text-outline text-[10px] font-medium uppercase tracking-widest">Ayer</span>
            </div>
            <h4 className="text-xl font-headline font-bold text-on-surface group-hover:text-primary transition-colors leading-tight mb-2">Guía de Convivencia: Zorros en Perímetros Urbanos</h4>
            <p className="text-on-surface-variant text-sm line-clamp-3">Cómo actuar ante la presencia de fauna silvestre en zonas residenciales para garantizar la seguridad de todos.</p>
          </div>
        </div>
      </section>

      {/* Newsletter Glass Section */}
      <section className="mb-12 rounded-[2.5rem] overflow-hidden relative p-8 md:p-16">
        <div className="absolute inset-0 z-0">
          <img
            className="w-full h-full object-cover"
            alt="Landscape"
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAKM6QtdbFMqFxG3f1wTmcnt2zZHGFhslrubf07KwqhWxewhyzdKK5B1w509oZaNOQxYp8FJFC2nlJ61sqMxvajFdm251QgqZYhU3Xz5ZtIvV542wp101Hce0and-psGw-gFMznllrnp4st1RsddsYuWVnESnFJ1D-k6D_zJNGE1Qomy8Ve1G-6pinoHA38QvbgF873FEB9zpX8ug3mezR3xdcoFn3i4o6R7wXiyK9wrfCNWUpqWqbjWshWmEEQEum33cC0z1GNipg"
          />
          <div className="absolute inset-0 bg-primary/20 backdrop-blur-md"></div>
        </div>
        <div className="relative z-10 max-w-xl mx-auto text-center">
          <h3 className="text-3xl md:text-4xl font-headline font-extrabold text-white mb-4">Mantente Informado</h3>
          <p className="text-white/90 mb-8 font-medium">Recibe las actualizaciones más importantes sobre nuestra fauna directamente en tu correo.</p>
          <div className="flex flex-col md:flex-row gap-3">
            <input
              className="flex-grow bg-white/20 border-white/30 text-white placeholder:text-white/60 rounded-xl px-6 py-3 focus:ring-2 focus:ring-white outline-none backdrop-blur-md transition-all"
              placeholder="tu@email.com"
              type="email"
            />
            <button className="bg-white text-primary font-bold px-8 py-3 rounded-xl hover:bg-blue-50 transition-colors active:scale-95 shadow-xl">
              Suscribirme
            </button>
          </div>
        </div>
      </section>
    </div>
  );
}
