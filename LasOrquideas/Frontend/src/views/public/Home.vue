<template>
  <div className="min-h-screen bg-[#F2F6F4] text-slate-800 antialiased selection:bg-brand-forest selection:text-white relative overflow-x-hidden">
    <!-- Foliage overlay decorative elements matching mockup -->
    <div className="foliage-blur-left hidden lg:block pointer-events-none fixed left-0 top-[15%] w-72 h-96 opacity-60 z-0">
      <svg className="w-full h-full" fill="none" viewBox="0 0 200 300" xmlns="http://www.w3.org/2000/svg">
        <path d="M-40 200C20 180 80 120 100 40C70 80 30 110 -40 200Z" fill="#2d6a4f" />
        <path d="M-20 280C50 250 140 180 160 80C110 130 50 180 -20 280Z" fill="#1b4332" />
        <path d="M-10 120C40 100 110 70 140 -20C90 20 40 60 -10 120Z" fill="#40916c" />
      </svg>
    </div>

    <div className="foliage-blur-right hidden lg:block pointer-events-none fixed right-0 top-[45%] w-64 h-96 opacity-50 z-0">
      <svg className="w-full h-full" fill="none" viewBox="0 0 200 300" xmlns="http://www.w3.org/2000/svg">
        <path d="M220 20C160 40 100 100 80 180C110 140 150 110 220 20Z" fill="#2d6a4f" />
        <path d="M240 100C170 130 80 200 60 300C110 250 170 200 240 100Z" fill="#1b4332" />
      </svg>
    </div>

    <!-- Floating Capsule Navbar -->
    <Navbar
      v-model:activeScreen="activeScreen"
      v-model:viewMode="viewMode"
    />

    <!-- Main Content Area -->
    <main className="relative z-10">
      <!-- MODO 1: CONTINUOUS LANDING PAGE -->
      <template v-if="viewMode === 'landing'">
        <!-- SECTION 1: HERO -->
        <section className="relative pt-24 pb-12 md:pt-32 md:pb-20 overflow-hidden" id="inicio">
          <div className="absolute inset-0 z-0">
            <img
              v-if="!heroImageError"
              src="https://lh3.googleusercontent.com/aida-public/AB6AXuDN7JRSqVoP6NZ8b175shgdlEzxNUKt7dMvkoHpkYJgQMpPmZ4zBubP7qVOudS5APG3-0AHBmYYZJ1Ok18D1yC0BU_GNurw7gvtI0n9MSHxp9fuAGu2o4vpS6XN2T1XQA4yMVVShCCMR4KIVKf3bQKZTbQ-UHFDhUTB3MNY-J8W1dQ6l-q6UDnsNfHF1BkQZye2uGzCHTi1av11NhOD6vi1akGPR3c4WZELgSx5GurECxdn4_eUOw"
              alt="Vista panorámica de entrada residencial Las Orquídeas al atardecer"
              referrerPolicy="no-referrer"
              @error="heroImageError = true"
              className="w-full h-full object-cover object-center filter brightness-[0.98]"
            />
            <div v-else className="w-full h-full bg-gradient-to-br from-emerald-900/30 via-slate-800/20 to-teal-950/40" />
            <div className="absolute inset-0 bg-gradient-to-r from-[#F2F6F4] via-[#F2F6F4]/80 sm:via-[#F2F6F4]/65 to-transparent" />
            <div className="absolute inset-0 bg-gradient-to-t from-[#F2F6F4] via-[#F2F6F4]/40 to-transparent" />
          </div>

          <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center min-h-[460px] md:min-h-[500px]">
              <div className="lg:col-span-7 pt-4">
                <div className="flex items-center gap-2 mb-4">
                  <span className="text-xs md:text-sm font-bold uppercase tracking-tagline text-slate-800">
                    TU PRÓXIMA HISTORIA COMIENZA AQUÍ
                  </span>
                  <div className="w-8 h-[2px] bg-slate-800/60" />
                </div>

                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.08] mb-6">
                  Terrenos para <br />
                  <span className="text-slate-900">un mejor futuro</span>
                </h1>

                <p className="text-slate-700 text-base md:text-lg max-w-xl mb-8 leading-relaxed font-normal">
                  Las Orquídeas es un proyecto de lotificación diseñado para brindarte un entorno
                  seguro, moderno y con gran proyección de plusvalía para ti y tu familia.
                </p>

                <div className="flex flex-wrap items-center gap-3 sm:gap-4">
                  <button
                    @click="handleSelectLot('l-12')"
                    className="inline-flex items-center gap-3 bg-brand-forest hover:bg-brand-forest-dark text-white px-6 sm:px-7 py-3.5 rounded-full font-semibold text-sm md:text-base shadow-lg transition-transform hover:-translate-y-0.5 active:scale-95 cursor-pointer"
                  >
                    <Grid3X3 className="w-5 h-5 text-emerald-300" />
                    <span>Ver lotes disponibles</span>
                    <ArrowRight className="w-4 h-4 ml-1" />
                  </button>

                  <button
                    @click="isVideoModalOpen = true"
                    className="inline-flex items-center gap-3 glass-card hover:bg-white/90 text-slate-800 px-5 sm:px-6 py-3.5 rounded-full font-semibold text-sm md:text-base shadow-xs transition hover:shadow-md cursor-pointer"
                  >
                    <span className="w-7 h-7 rounded-full bg-slate-900 text-white flex items-center justify-center text-xs pl-0.5 shadow-sm">
                      <Play className="w-3 h-3 fill-current" />
                    </span>
                    <span>Conoce el proyecto</span>
                  </button>
                </div>
              </div>

              <!-- Monumental Logo Emblem right side -->
              <div className="lg:col-span-5 hidden lg:flex justify-end pr-4">
                <div className="relative">
                  <div className="w-44 h-44 rounded-full border-4 border-white/80 shadow-2xl overflow-hidden glass-card p-2 flex flex-col items-center justify-center transform hover:scale-105 transition-all duration-300 backdrop-blur-xl">
                    <div className="w-20 h-20 rounded-full border border-slate-200 bg-white/90 p-0.5 shadow-inner flex items-center justify-center overflow-hidden">
                      <img :src="logoUrl" alt="Las Orquídeas Emblem" className="w-full h-full object-cover object-center rounded-full" />
                    </div>
                    <span className="text-xs font-black tracking-widest text-slate-800 mt-2">LAS ORQUÍDEAS</span>
                    <span className="text-[8px] tracking-[0.3em] font-semibold text-slate-500 uppercase">Lotificadora</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- 4 Hero Glass Cards -->
            <div className="mt-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
                <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
                  <TrendingUp className="w-6 h-6 text-brand-emerald" />
                </div>
                <div>
                  <h3 className="font-bold text-slate-900 text-sm leading-snug">Plusvalía garantizada</h3>
                  <p className="text-xs text-slate-500">Inversión con futuro</p>
                </div>
              </div>

              <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
                <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
                  <ShieldCheck className="w-6 h-6 text-brand-emerald" />
                </div>
                <div>
                  <h3 className="font-bold text-slate-900 text-sm leading-snug">Entorno seguro</h3>
                  <p className="text-xs text-slate-500">Para tu familia</p>
                </div>
              </div>

              <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
                <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
                  <Trees className="w-6 h-6 text-brand-emerald" />
                </div>
                <div>
                  <h3 className="font-bold text-slate-900 text-sm leading-snug">Áreas verdes</h3>
                  <p className="text-xs text-slate-500">Naturaleza cerca de ti</p>
                </div>
              </div>

              <div className="glass-hero-badge rounded-2xl p-4 flex items-center gap-4 transition hover:-translate-y-1 hover:shadow-xl">
                <div className="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100/80 flex items-center justify-center shrink-0">
                  <MapPin className="w-6 h-6 text-brand-emerald" />
                </div>
                <div>
                  <h3 className="font-bold text-slate-900 text-sm leading-snug">Acceso estratégico</h3>
                  <p className="text-xs text-slate-500">Conecta con lo importante</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 2: PROYECTO -->
        <section className="py-16 relative z-10" id="proyecto">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
              <div className="lg:col-span-4 flex flex-col justify-between h-full pt-2">
                <div>
                  <div className="flex items-center gap-2 mb-3">
                    <div className="w-6 h-[2px] bg-brand-forest" />
                    <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">EL PROYECTO</span>
                  </div>
                  <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight mb-5">
                    Un lugar pensado para crecer
                  </h2>
                  <p className="text-slate-600 text-sm sm:text-base leading-relaxed mb-6 font-normal">
                    Las Orquídeas combina ubicación estratégica, infraestructura subterránea moderna y
                    un entorno natural inigualable para que tú y tu familia disfruten de una mejor calidad
                    de vida con total tranquilidad.
                  </p>

                  <div className="space-y-3 mb-8">
                    <div className="flex items-start gap-3">
                      <div className="w-5 h-5 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center shrink-0 mt-0.5">
                        <Check className="w-3 h-3 stroke-[3]" />
                      </div>
                      <span className="text-xs sm:text-sm text-slate-700 font-medium">Garita de seguridad con control de acceso 24/7.</span>
                    </div>
                    <div className="flex items-start gap-3">
                      <div className="w-5 h-5 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center shrink-0 mt-0.5">
                        <Check className="w-3 h-3 stroke-[3]" />
                      </div>
                      <span className="text-xs sm:text-sm text-slate-700 font-medium">Red de agua potable propia y cableado subterráneo.</span>
                    </div>
                    <div className="flex items-start gap-3">
                      <div className="w-5 h-5 rounded-full bg-emerald-100 text-brand-forest flex items-center justify-center shrink-0 mt-0.5">
                        <Check className="w-3 h-3 stroke-[3]" />
                      </div>
                      <span className="text-xs sm:text-sm text-slate-700 font-medium">Calles amplias pavimentadas y aceras peatonales.</span>
                    </div>
                  </div>
                </div>

                <div className="pt-2">
                  <button
                    @click="handleNavigate('contacto')"
                    className="inline-flex items-center gap-3 bg-white hover:bg-slate-50 text-slate-800 border border-slate-200/90 shadow-xs px-6 py-3 rounded-full text-sm font-semibold transition hover:shadow-md cursor-pointer"
                  >
                    <span>Conoce más del proyecto</span>
                    <ArrowRight className="w-4 h-4 text-slate-500" />
                  </button>
                </div>
              </div>

              <!-- Project Masterplan Preview -->
              <div className="lg:col-span-5 relative">
                <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-slate-900 group">
                  <div className="relative h-[340px] sm:h-[400px] w-full overflow-hidden">
                    <img
                      :src="galleryThumbs[activeThumbIndex].url"
                      :alt="galleryThumbs[activeThumbIndex].title"
                      referrerPolicy="no-referrer"
                      className="w-full h-full object-cover object-bottom filter saturate-125 transform group-hover:scale-105 transition-transform duration-700"
                    />
                    <div className="absolute inset-0 bg-emerald-950/20 mix-blend-multiply pointer-events-none" />

                    <template v-if="galleryThumbs[activeThumbIndex].isMasterplan">
                      <div className="absolute top-[48%] left-[46%] z-20 transform -translate-x-1/2 -translate-y-1/2">
                        <div className="relative flex items-center justify-center">
                          <span className="w-8 h-8 rounded-full bg-purple-500/40 pulse-ring absolute" />
                          <button
                            @click="handleSelectLot('l-12')"
                            className="w-7 h-7 rounded-full bg-purple-600 text-white flex items-center justify-center shadow-lg border-2 border-white cursor-pointer hover:scale-110 transition active:scale-95"
                            title="Lote 12 Disponible"
                          >
                            <span className="text-[10px] font-bold">12</span>
                          </button>
                        </div>
                      </div>

                      <div className="absolute top-[28%] left-[48%] z-30 transform -translate-x-2 -translate-y-full w-48 sm:w-56 glass-card rounded-2xl p-3 shadow-xl border border-white">
                        <div className="flex items-center justify-between mb-1">
                          <div className="flex items-center gap-1.5">
                            <span className="w-2.5 h-2.5 rounded-full bg-purple-500" />
                            <span className="font-extrabold text-xs sm:text-sm text-slate-800">Lote 12</span>
                          </div>
                          <span className="text-[10px] text-emerald-700 bg-emerald-100 font-semibold px-2 py-0.5 rounded-full">Disponible</span>
                        </div>
                        <div className="text-[11px] text-slate-500 space-y-0.5">
                          <p className="flex items-center gap-1"><span>📐</span> <span>260 m² (10m x 26m)</span></p>
                          <p className="text-brand-forest font-bold text-xs mt-1">Q 185,000</p>
                        </div>
                        <button
                          @click="handleSelectLot('l-12')"
                          className="mt-2 text-[10px] font-bold text-slate-700 hover:text-brand-forest flex items-center justify-end gap-1 w-full text-right cursor-pointer"
                        >
                          <span>Ver detalle</span> <span>→</span>
                        </button>
                      </div>
                    </template>
                  </div>

                  <!-- Bottom Thumbs Strip -->
                  <div className="bg-white/95 p-3 flex items-center justify-between gap-2 border-t border-slate-100">
                    <button
                      @click="activeThumbIndex = activeThumbIndex > 0 ? activeThumbIndex - 1 : galleryThumbs.length - 1"
                      className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold transition cursor-pointer"
                    >
                      <ChevronLeft className="w-4 h-4" />
                    </button>
                    <div className="grid grid-cols-4 gap-2 flex-1">
                      <button
                        v-for="(thumb, idx) in galleryThumbs.slice(1, 5)"
                        :key="thumb.title"
                        @click="activeThumbIndex = idx + 1"
                        :class="[
                          'h-12 rounded-lg overflow-hidden border relative group/thumb cursor-pointer transition',
                          activeThumbIndex === idx + 1 ? 'border-brand-forest ring-2 ring-brand-forest/40' : 'border-slate-200'
                        ]"
                      >
                        <img :src="thumb.url" :alt="thumb.title" referrerPolicy="no-referrer" className="w-full h-full object-cover" />
                      </button>
                    </div>
                    <button
                      @click="activeThumbIndex = activeThumbIndex < galleryThumbs.length - 1 ? activeThumbIndex + 1 : 0"
                      className="w-7 h-7 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center text-xs font-bold transition cursor-pointer"
                    >
                      <ChevronRight className="w-4 h-4" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Amenities Grid & Video Card -->
              <div className="lg:col-span-3 flex flex-col justify-between space-y-6">
                <div>
                  <div className="flex items-center gap-2 mb-4">
                    <div className="w-6 h-[2px] bg-brand-forest" />
                    <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">AMENIDADES</span>
                  </div>
                  <div className="grid grid-cols-3 gap-2.5 mb-6">
                    <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                      <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                        <Compass className="w-4 h-4 text-brand-forest" />
                      </div>
                      <span className="text-[10px] font-bold text-slate-800 leading-tight">Calles amplias</span>
                    </div>
                    <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                      <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                        <Trees className="w-4 h-4 text-brand-forest" />
                      </div>
                      <span className="text-[10px] font-bold text-slate-800 leading-tight">Áreas verdes</span>
                    </div>
                    <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                      <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                        <Zap className="w-4 h-4 text-brand-forest" />
                      </div>
                      <span className="text-[10px] font-bold text-slate-800 leading-tight">Servicios básicos</span>
                    </div>
                    <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                      <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                        <Sparkles className="w-4 h-4 text-brand-forest" />
                      </div>
                      <span className="text-[10px] font-bold text-slate-800 leading-tight">Entorno natural</span>
                    </div>
                    <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                      <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                        <HomeIcon className="w-4 h-4 text-brand-forest" />
                      </div>
                      <span className="text-[10px] font-bold text-slate-800 leading-tight">Diseño moderno</span>
                    </div>
                    <div className="glass-card rounded-2xl p-2.5 flex flex-col items-center text-center shadow-xs hover:shadow-md transition hover:-translate-y-0.5">
                      <div className="w-8 h-8 rounded-lg bg-emerald-50 text-brand-forest flex items-center justify-center mb-1.5">
                        <TrendingUp className="w-4 h-4 text-brand-forest" />
                      </div>
                      <span className="text-[10px] font-bold text-slate-800 leading-tight">Alta plusvalía</span>
                    </div>
                  </div>
                </div>

                <div
                  @click="isVideoModalOpen = true"
                  className="relative rounded-2xl overflow-hidden shadow-lg group cursor-pointer border border-white active:scale-98 transition"
                >
                  <img
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCjQ4T8cH4OErX3z8O8lBggXHva1VH79T0Xsn3hoZkd3-6slkwAeK1qav1t0gPYUJqBlebNxd25tHVQIMKbV9vmekiIBqx0VYxwZhmBzCR2dx9Pga0mZdk1ZwGFhDPIpsLdZxcRDiygcISByaOx5ZnVudcdulntJZQqGuGGmmAZenNzPxm3BXpgVDiRlgNnghLiA1x82rZAZs6HVd5sCfW5d8GYLV56D-BW28WzcHPZHZm_49b37g"
                    alt="Vista del entorno natural al atardecer"
                    referrerPolicy="no-referrer"
                    className="w-full h-36 object-cover filter brightness-[0.75] group-hover:scale-105 transition-transform duration-500"
                  />
                  <div className="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent flex flex-col justify-end p-4 text-white">
                    <div className="flex items-center gap-3">
                      <div className="w-9 h-9 rounded-full bg-white/90 text-slate-900 flex items-center justify-center pl-0.5 shadow-lg group-hover:scale-110 transition">
                        <Play className="w-3.5 h-3.5 fill-current" />
                      </div>
                      <div>
                        <p className="text-[11px] font-medium text-slate-200 leading-snug">Un entorno que te conecta con lo esencial</p>
                        <p className="text-xs font-bold text-white flex items-center gap-1 mt-0.5">
                          <span>Ver video del proyecto</span>
                          <span>▷</span>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 3: INTERACTIVE LOTS -->
        <section className="py-16 bg-white/60 relative backdrop-blur-md border-y border-slate-200/60" id="lotes">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="text-center max-w-3xl mx-auto mb-10">
              <div className="inline-flex items-center gap-2 mb-2">
                <span className="w-5 h-[2px] bg-brand-forest" />
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">PLANO MASTER Y DISPONIBILIDAD</span>
                <span className="w-5 h-[2px] bg-brand-forest" />
              </div>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Encuentra el lote perfecto para tu hogar
              </h2>
              <p className="text-slate-600 text-sm mt-3">
                Elige la ubicación preferida dentro de nuestra primera fase de desarrollo. Todos los
                lotes cuentan con escrituración inmediata y facilidades de financiamiento directo.
              </p>
            </div>

            <!-- Filter Controls -->
            <div className="glass-card rounded-2xl p-4 shadow-xs mb-8 flex flex-wrap items-center justify-between gap-4">
              <div className="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0 text-xs font-semibold">
                <span className="text-slate-500 mr-2 text-xs uppercase tracking-wider">Estado:</span>
                <button
                  @click="statusFilter = 'all'"
                  :class="['px-3.5 py-1.5 rounded-full transition cursor-pointer', statusFilter === 'all' ? 'bg-brand-forest text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700']"
                >Todos (48)</button>
                <button
                  @click="statusFilter = 'disponible'"
                  :class="['px-3.5 py-1.5 rounded-full transition cursor-pointer', statusFilter === 'disponible' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700']"
                >Disponibles (28)</button>
                <button
                  @click="statusFilter = 'reservado'"
                  :class="['px-3.5 py-1.5 rounded-full transition cursor-pointer', statusFilter === 'reservado' ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700']"
                >Reservados (12)</button>
                <button
                  @click="statusFilter = 'vendido'"
                  :class="['px-3.5 py-1.5 rounded-full transition cursor-pointer', statusFilter === 'vendido' ? 'bg-slate-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700']"
                >Vendidos (8)</button>
              </div>

              <div className="flex items-center gap-3 text-xs">
                <label htmlFor="metrajeSelect" className="text-slate-500 uppercase tracking-wider font-semibold">Metraje:</label>
                <select
                  id="metrajeSelect"
                  v-model="metrajeFilter"
                  className="rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 py-1.5 px-3 bg-white focus:ring-brand-forest focus:border-brand-forest cursor-pointer"
                >
                  <option value="all">Todos los tamaños</option>
                  <option value="180-220">180 m² - 220 m²</option>
                  <option value="220-300">220 m² - 300 m²</option>
                  <option value="300+">Más de 300 m² (Esquina)</option>
                </select>
              </div>
            </div>

            <!-- Masterplan interactive grid & Lot Details -->
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
              <div className="lg:col-span-7 bg-emerald-950/5 rounded-3xl p-6 border border-emerald-900/10 shadow-inner relative">
                <div className="flex items-center justify-between text-xs font-bold text-slate-600 mb-4">
                  <span>MANZANA B - FASE 1</span>
                  <div className="flex items-center gap-3 sm:gap-4">
                    <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-md bg-emerald-500" /> Disponible</span>
                    <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-md bg-amber-400" /> Reservado</span>
                    <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-md bg-slate-400" /> Vendido</span>
                  </div>
                </div>

                <div className="grid grid-cols-6 gap-2 sm:gap-3 py-2">
                  <div
                    v-for="lot in LOTS_DATA.slice(0, 6)"
                    :key="lot.id"
                    @click="selectedLotId = lot.id"
                    :class="[
                      'h-16 rounded-xl text-xs flex flex-col items-center justify-center border-2 transition-all cursor-pointer',
                      getLotStatusStyle(lot, selectedLotId === lot.id),
                      !isLotMatching(lot) ? 'opacity-30' : ''
                    ]"
                  >
                    <span>{{ lot.number }}</span>
                    <span className="text-[9px]">{{ lot.status === 'vendido' ? 'Vendido' : lot.status === 'reservado' ? 'Reservado' : `${lot.areaM2} m²` }}</span>
                  </div>

                  <div className="col-span-6 py-2">
                    <div className="h-6 rounded-lg bg-slate-200 border-dashed border-y border-slate-300 flex items-center justify-center text-[10px] uppercase tracking-widest text-slate-500 font-bold">
                      Bulevar Central de las Acacias (14.00 Metros)
                    </div>
                  </div>

                  <div
                    v-for="lot in LOTS_DATA.slice(6, 12)"
                    :key="lot.id"
                    @click="selectedLotId = lot.id"
                    :class="[
                      'h-16 rounded-xl text-xs flex flex-col items-center justify-center border-2 transition-all cursor-pointer',
                      getLotStatusStyle(lot, selectedLotId === lot.id),
                      !isLotMatching(lot) ? 'opacity-30' : ''
                    ]"
                  >
                    <span>{{ lot.number }} {{ lot.featured ? '★' : '' }}</span>
                    <span className="text-[9px]">{{ lot.status === 'vendido' ? 'Vendido' : lot.status === 'reservado' ? 'Reservado' : `${lot.areaM2} m²` }}</span>
                  </div>
                </div>
              </div>

              <!-- Selected Lot Detail Card -->
              <div className="lg:col-span-5">
                <div className="glass-card rounded-3xl p-6 sm:p-8 shadow-xl border border-white relative overflow-hidden">
                  <div className="flex items-center justify-between mb-4">
                    <span
                      :class="[
                        'px-3 py-1 rounded-full text-xs font-bold border',
                        selectedLot.status === 'disponible' ? 'bg-emerald-100 text-emerald-800 border-emerald-200' :
                        selectedLot.status === 'reservado' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-slate-100 text-slate-700 border-slate-200'
                      ]"
                    >
                      ● Lote Seleccionado: {{ selectedLot.number }}
                    </span>
                    <span className="text-xs text-slate-500 font-semibold">{{ selectedLot.manzana }}</span>
                  </div>

                  <h3 className="text-2xl font-extrabold text-slate-900 mb-1">Terreno Residencial Premium</h3>
                  <p className="text-xs text-slate-500 mb-6">Ubicación preferencial frente a área verde.</p>

                  <div className="space-y-3.5 border-y border-slate-200/80 py-4 mb-6 text-sm">
                    <div className="flex justify-between items-center">
                      <span className="text-slate-500">Área total:</span>
                      <span className="font-bold text-slate-800">{{ selectedLot.areaM2 }}.00 m² ({{ selectedLot.dimensions }})</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-slate-500">Topografía:</span>
                      <span className="font-bold text-slate-800">{{ selectedLot.topography }}</span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-slate-500">Precio especial de preventa:</span>
                      <span className="font-extrabold text-brand-forest text-lg">
                        Q {{ selectedLot.priceQ.toLocaleString() }}
                        <span className="text-xs text-slate-500 font-normal ml-1">(${{ selectedLot.priceUSD.toLocaleString() }} USD)</span>
                      </span>
                    </div>
                    <div className="flex justify-between items-center">
                      <span className="text-slate-500">Enganche 10%:</span>
                      <span className="font-bold text-slate-800">Q {{ selectedLot.minDownPaymentQ.toLocaleString() }}</span>
                    </div>
                  </div>

                  <div className="space-y-3">
                    <button
                      v-if="selectedLot.status === 'disponible'"
                      @click="reservationLot = selectedLot"
                      className="w-full bg-brand-forest hover:bg-brand-forest-dark text-white py-3.5 px-6 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 shadow-lg transition active:scale-95 cursor-pointer"
                    >
                      <MessageCircle className="w-5 h-5 fill-current" />
                      <span>Me interesa reservar este lote</span>
                    </button>
                    <button
                      v-else
                      disabled
                      className="w-full bg-slate-300 text-slate-500 py-3.5 px-6 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 cursor-not-allowed"
                    >
                      <CheckCircle2 className="w-5 h-5" />
                      <span>{{ selectedLot.status === 'reservado' ? 'Lote en proceso de reserva' : 'Lote no disponible' }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 4: UBICACION -->
        <section className="py-16 relative" id="ubicacion">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
              <div className="lg:col-span-5">
                <div className="flex items-center gap-2 mb-3">
                  <div className="w-6 h-[2px] bg-brand-forest" />
                  <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">UBICACIÓN ESTRATÉGICA</span>
                </div>
                <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-tight mb-4">
                  Conexión y tranquilidad en un solo lugar
                </h2>
                <p className="text-slate-600 text-sm leading-relaxed mb-6">
                  Ubicado en una zona de alta expansión residencial en Guatemala, protegido del ruido de
                  la ciudad pero a pocos minutos de los principales centros comerciales, colegios y servicios médicos.
                </p>

                <div className="space-y-4 mb-8">
                  <div
                    @click="activeLocationTab = 'colegios'"
                    :class="['flex items-start gap-4 p-3.5 rounded-2xl glass-card cursor-pointer transition', activeLocationTab === 'colegios' ? 'ring-2 ring-brand-forest/30 bg-white/90 shadow-md' : 'hover:bg-white/80']"
                  >
                    <div className="w-10 h-10 rounded-xl bg-emerald-100/70 text-brand-forest flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">5 min</div>
                    <div className="flex-1">
                      <h4 className="font-bold text-slate-800 text-sm flex items-center gap-1.5"><School className="w-4 h-4 text-brand-forest" /><span>Colegios y Centros Educativos</span></h4>
                      <p className="text-xs text-slate-500 mt-0.5">Acceso directo por bulevares principales.</p>
                    </div>
                  </div>

                  <div
                    @click="activeLocationTab = 'comercio'"
                    :class="['flex items-start gap-4 p-3.5 rounded-2xl glass-card cursor-pointer transition', activeLocationTab === 'comercio' ? 'ring-2 ring-brand-forest/30 bg-white/90 shadow-md' : 'hover:bg-white/80']"
                  >
                    <div className="w-10 h-10 rounded-xl bg-emerald-100/70 text-brand-forest flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">10 min</div>
                    <div className="flex-1">
                      <h4 className="font-bold text-slate-800 text-sm flex items-center gap-1.5"><ShoppingBag className="w-4 h-4 text-brand-forest" /><span>Supermercados y Plazas Comerciales</span></h4>
                      <p className="text-xs text-slate-500 mt-0.5">Bancos, farmacias, cines y restaurantes de prestigio.</p>
                    </div>
                  </div>
                </div>

                <a
                  href="https://maps.google.com/?q=Las+Orquideas+Guatemala"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-3 bg-brand-forest text-white px-6 py-3 rounded-full text-sm font-semibold hover:bg-brand-forest-dark transition shadow-md active:scale-95 cursor-pointer"
                >
                  <Navigation className="w-4 h-4 text-emerald-300" />
                  <span>Ver ruta en Waze / Google Maps</span>
                  <ArrowRight className="w-4 h-4" />
                </a>
              </div>

              <div className="lg:col-span-7">
                <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white h-[380px] sm:h-[440px] group">
                  <img
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuAFQB1o4vdkcwzHfd5bED3ZafhEWFEGNp8YZdC7aK7hF7jDDtbj94c5h5OJbwQ8RQUZsZamDB60qyWMjX44YpH1rs8V48icf5DSuxhSCTUky6UdYrXUS0vr2qtQ-Gv7UBZ1n3Qnk2LrLLpRsItTanABz_3XdK38ZoV24-9PwwSFtxJyRs4xASteQTRxoqqSXATopsBS5T6V6QxoOL6x7IDvQTWm9WKqqcVKf86FhYd9sjvphU1xyQ"
                    alt="Ubicación panorámica Las Orquídeas"
                    referrerPolicy="no-referrer"
                    className="w-full h-full object-cover filter contrast-[1.05] group-hover:scale-102 transition duration-700"
                  />
                  <div className="absolute bottom-6 left-4 right-4 sm:left-6 sm:right-6 glass-card rounded-2xl p-4 flex items-center justify-between shadow-xl border border-white">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-xl bg-brand-forest text-white flex items-center justify-center shrink-0">
                        <MapPin className="w-5 h-5 text-emerald-300" />
                      </div>
                      <div>
                        <p className="text-xs font-semibold text-brand-forest uppercase tracking-wider">Garita Principal</p>
                        <p className="text-xs sm:text-sm font-extrabold text-slate-900">Km. 24.5 Carretera Residencial, Las Orquídeas</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 5: GALERIA -->
        <section className="py-16 bg-[#eef4f1]" id="galeria">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex flex-col md:flex-row md:items-end justify-between mb-8">
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <div className="w-6 h-[2px] bg-brand-forest" />
                  <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">GALERÍA VISUAL</span>
                </div>
                <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Recorre cada espacio pensado para ti</h2>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
              <div
                v-for="(photo, idx) in GALLERY_ITEMS"
                :key="photo.id"
                @click="lightboxIndex = idx"
                className="group relative rounded-3xl overflow-hidden shadow-md h-72 border-2 border-white cursor-pointer active:scale-98 transition-all"
              >
                <img :src="photo.imageUrl" :alt="photo.title" referrerPolicy="no-referrer" className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                <div className="absolute inset-0 bg-gradient-to-t from-slate-900/85 via-slate-900/20 to-transparent opacity-80 group-hover:opacity-90 transition" />
                <div className="absolute bottom-4 left-4 right-4 text-white">
                  <span className="text-[10px] font-bold uppercase tracking-wider bg-brand-forest text-emerald-300 px-2.5 py-1 rounded-full">{{ photo.tag }}</span>
                  <h4 className="font-extrabold text-base mt-2">{{ photo.title }}</h4>
                  <p className="text-xs text-slate-200 line-clamp-1">{{ photo.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 6: BENEFICIOS RIBBON -->
        <section className="py-12 bg-white border-y border-slate-100">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
              <div className="flex flex-col items-center p-4 rounded-2xl hover:bg-slate-50/80 transition">
                <div className="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-brand-forest mb-3">
                  <Clock className="w-6 h-6 text-brand-forest" />
                </div>
                <h4 className="font-bold text-slate-800 text-sm">Escrituración Rápida</h4>
                <p className="text-xs text-slate-500 mt-1 max-w-xs">Trámite ágil y certeza jurídica 100% comprobable.</p>
              </div>

              <div className="flex flex-col items-center p-4 rounded-2xl hover:bg-slate-50/80 transition">
                <div className="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-brand-forest mb-3">
                  <CheckCircle2 className="w-6 h-6 text-brand-forest" />
                </div>
                <h4 className="font-bold text-slate-800 text-sm">Financiamiento Propio</h4>
                <p className="text-xs text-slate-500 mt-1 max-w-xs">Sin complicaciones bancarias ni trámites excesivos.</p>
              </div>

              <div className="flex flex-col items-center p-4 rounded-2xl hover:bg-slate-50/80 transition">
                <div className="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-brand-forest mb-3">
                  <Droplets className="w-6 h-6 text-brand-forest" />
                </div>
                <h4 className="font-bold text-slate-800 text-sm">Agua Propia Abundante</h4>
                <p className="text-xs text-slate-500 mt-1 max-w-xs">Pozo propio de gran capacidad con tanque elevado.</p>
              </div>

              <div className="flex flex-col items-center p-4 rounded-2xl hover:bg-slate-50/80 transition">
                <div className="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-brand-forest mb-3">
                  <TrendingUp className="w-6 h-6 text-brand-forest" />
                </div>
                <h4 className="font-bold text-slate-800 text-sm">Plusvalía Acelerada</h4>
                <p className="text-xs text-slate-500 mt-1 max-w-xs">Crecimiento del valor de la tierra garantizado por contrato.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 7: CONTACTO -->
        <section className="py-20 relative overflow-hidden" id="contacto">
          <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div className="glass-card rounded-3xl p-6 sm:p-10 md:p-12 shadow-2xl border border-white">
              <div className="text-center max-w-xl mx-auto mb-8">
                <div className="inline-flex items-center gap-2 mb-2">
                  <span className="w-5 h-[2px] bg-brand-forest" />
                  <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">ASESORÍA INMEDIATA</span>
                  <span className="w-5 h-[2px] bg-brand-forest" />
                </div>
                <h2 className="text-3xl font-extrabold text-slate-900 tracking-tight">Agenda tu visita a Las Orquídeas</h2>
                <p className="text-slate-600 text-sm mt-2">Completa el formulario y un asesor inmobiliario se pondrá en contacto contigo.</p>
              </div>

              <form @submit.prevent="contactSubmitted = true" className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nombre Completo</label>
                    <input type="text" required placeholder="Ej. Carlos Mendoza" v-model="contactForm.nombre" className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none" />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Teléfono / WhatsApp</label>
                    <input type="tel" required placeholder="+502 5555-5555" v-model="contactForm.telefono" className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none" />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Correo Electrónico</label>
                  <input type="email" required placeholder="tuemail@ejemplo.com" v-model="contactForm.email" className="w-full rounded-2xl border border-slate-200/90 bg-white/90 text-sm p-3 focus:ring-2 focus:ring-brand-forest focus:outline-none" />
                </div>

                <div className="pt-2">
                  <button type="submit" className="w-full bg-brand-forest hover:bg-brand-forest-dark text-white py-4 px-6 rounded-2xl font-extrabold text-sm md:text-base flex items-center justify-center gap-3 shadow-lg hover:shadow-xl transition-all transform active:scale-95 cursor-pointer">
                    <span>Enviar mensaje y solicitar asesoría personalizada</span>
                    <ArrowRight className="w-5 h-5 text-emerald-300" />
                  </button>
                </div>
              </form>
            </div>
          </div>
        </section>

        <!-- CTA BANNER -->
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
          <div className="bg-brand-forest-dark text-white rounded-3xl p-6 sm:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl relative overflow-hidden">
            <div className="flex items-center gap-4 sm:gap-5 z-10">
              <div className="w-14 h-14 rounded-2xl bg-white/10 flex items-center justify-center text-2xl shrink-0 border border-white/20">
                <Rocket className="w-7 h-7 text-emerald-300" />
              </div>
              <div>
                <h3 className="text-xl sm:text-2xl font-extrabold text-white leading-tight">Impulsa tu inversión hoy con asesoría personalizada</h3>
                <p className="text-xs sm:text-sm text-emerald-200/90 mt-1">Conoce nuestras promociones de enganche y facilidades de pago.</p>
              </div>
            </div>
            <button @click="handleNavigate('contacto')" className="z-10 w-12 h-12 rounded-full bg-white text-brand-forest flex items-center justify-center text-xl font-bold shadow-lg hover:scale-110 active:scale-95 transition shrink-0 cursor-pointer">
              <ArrowRight className="w-5 h-5 text-brand-forest" />
            </button>
          </div>
        </div>
      </template>

      <!-- MODO 2: PANTALLAS POR SECCIÓN (DEDICATED PANTALLAS VIEW) -->
      <template v-else>
        <div className="pt-24 min-h-[80vh]">
          <!-- Pantalla 1: INICIO -->
          <div v-if="activeScreen === 'inicio'">
            <section className="relative pb-12 overflow-hidden">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center min-h-[400px]">
                  <div className="lg:col-span-7 pt-4">
                    <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest block mb-2">PANTALLA: INICIO</span>
                    <h1 className="text-4xl sm:text-5xl font-extrabold text-slate-900 tracking-tight leading-tight mb-4">
                      Terrenos para un mejor futuro
                    </h1>
                    <p className="text-slate-700 text-base max-w-xl mb-6">
                      Las Orquídeas es un proyecto de lotificación diseñado para brindarte un entorno seguro y moderno.
                    </p>
                    <div className="flex gap-4">
                      <button @click="activeScreen = 'lotes'" className="bg-brand-forest text-white px-6 py-3 rounded-full font-bold text-sm shadow-md">Ver Lotes Disponibles →</button>
                      <button @click="isVideoModalOpen = true" className="glass-card text-slate-800 px-5 py-3 rounded-full font-bold text-sm">Ver Video Tour</button>
                    </div>
                  </div>
                  <div className="lg:col-span-5 flex justify-center">
                    <div className="w-48 h-48 rounded-full border-4 border-white shadow-2xl glass-card p-2 flex flex-col items-center justify-center overflow-hidden">
                      <img :src="logoUrl" alt="Las Orquídeas Logo" className="w-24 h-24 object-cover rounded-full mb-1" />
                      <span className="text-xs font-black tracking-widest text-slate-800">LAS ORQUÍDEAS</span>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <section className="py-12 bg-white border-y border-slate-100">
              <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                  <div className="p-4 rounded-2xl bg-slate-50">
                    <h4 className="font-bold text-slate-800 text-sm">Escrituración Rápida</h4>
                    <p className="text-xs text-slate-500 mt-1">Trámite ágil y certeza jurídica 100% comprobable.</p>
                  </div>
                  <div className="p-4 rounded-2xl bg-slate-50">
                    <h4 className="font-bold text-slate-800 text-sm">Financiamiento Propio</h4>
                    <p className="text-xs text-slate-500 mt-1">Sin complicaciones bancarias.</p>
                  </div>
                  <div className="p-4 rounded-2xl bg-slate-50">
                    <h4 className="font-bold text-slate-800 text-sm">Agua Propia Abundante</h4>
                    <p className="text-xs text-slate-500 mt-1">Pozo propio de gran capacidad.</p>
                  </div>
                  <div className="p-4 rounded-2xl bg-slate-50">
                    <h4 className="font-bold text-slate-800 text-sm">Plusvalía Acelerada</h4>
                    <p className="text-xs text-slate-500 mt-1">Crecimiento de valor garantizado.</p>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Pantalla 2: PROYECTO -->
          <div v-else-if="activeScreen === 'proyecto'" className="py-6">
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div className="mb-6">
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest block mb-1">PANTALLA: EL PROYECTO</span>
                <h2 className="text-3xl font-extrabold text-slate-900">Un lugar pensado para crecer</h2>
              </div>
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div className="lg:col-span-6 space-y-4 text-slate-600 text-sm">
                  <p>Las Orquídeas combina ubicación estratégica, infraestructura subterránea moderna y un entorno natural inigualable.</p>
                  <ul className="space-y-2">
                    <li className="flex items-center gap-2"><Check className="w-4 h-4 text-brand-forest" /> Garita de seguridad 24/7.</li>
                    <li className="flex items-center gap-2"><Check className="w-4 h-4 text-brand-forest" /> Pozo propio de agua potable y cableado subterráneo.</li>
                    <li className="flex items-center gap-2"><Check className="w-4 h-4 text-brand-forest" /> Bulevar principal de 14 metros con aceras de concreto.</li>
                  </ul>
                  <button @click="activeScreen = 'contacto'" className="bg-brand-forest text-white px-6 py-2.5 rounded-full font-bold text-xs mt-4">Solicitar Asesoría →</button>
                </div>
                <div className="lg:col-span-6 rounded-3xl overflow-hidden shadow-xl border-4 border-white h-[320px]">
                  <img :src="galleryThumbs[0].url" alt="Masterplan" className="w-full h-full object-cover" />
                </div>
              </div>
            </section>
          </div>

          <!-- Pantalla 3: LOTES -->
          <div v-else-if="activeScreen === 'lotes'" className="py-6">
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div className="mb-6">
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest block mb-1">PANTALLA: MAPA INTERACTIVO DE LOTES</span>
                <h2 className="text-3xl font-extrabold text-slate-900">Disponibilidad en Fase 1</h2>
              </div>

              <!-- Interactive Lots Component -->
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <div className="lg:col-span-7 bg-emerald-950/5 rounded-3xl p-6 border border-emerald-900/10 shadow-inner">
                  <div className="flex items-center justify-between text-xs font-bold text-slate-600 mb-4">
                    <span>MANZANA B</span>
                    <div className="flex gap-3">
                      <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-md bg-emerald-500" /> Disponible</span>
                      <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-md bg-amber-400" /> Reservado</span>
                      <span className="flex items-center gap-1.5"><span className="w-3 h-3 rounded-md bg-slate-400" /> Vendido</span>
                    </div>
                  </div>

                  <div className="grid grid-cols-6 gap-2 py-2">
                    <div
                      v-for="lot in LOTS_DATA"
                      :key="lot.id"
                      @click="selectedLotId = lot.id"
                      :class="[
                        'h-16 rounded-xl text-xs flex flex-col items-center justify-center border-2 transition-all cursor-pointer',
                        getLotStatusStyle(lot, selectedLotId === lot.id)
                      ]"
                    >
                      <span>{{ lot.number }}</span>
                      <span className="text-[9px]">{{ lot.status === 'vendido' ? 'Vendido' : lot.status === 'reservado' ? 'Reservado' : `${lot.areaM2} m²` }}</span>
                    </div>
                  </div>
                </div>

                <div className="lg:col-span-5 glass-card rounded-3xl p-6 shadow-xl border border-white">
                  <span className="text-xs font-bold text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full inline-block mb-3">Lote Seleccionado: {{ selectedLot.number }}</span>
                  <h3 className="text-2xl font-extrabold text-slate-900 mb-2">Terreno Residencial {{ selectedLot.manzana }}</h3>
                  <div className="space-y-2 text-xs border-y py-3 my-3">
                    <div className="flex justify-between"><span>Área:</span> <span className="font-bold">{{ selectedLot.areaM2 }} m² ({{ selectedLot.dimensions }})</span></div>
                    <div className="flex justify-between"><span>Precio:</span> <span className="font-extrabold text-brand-forest">Q {{ selectedLot.priceQ.toLocaleString() }}</span></div>
                    <div className="flex justify-between"><span>Enganche 10%:</span> <span className="font-bold">Q {{ selectedLot.minDownPaymentQ.toLocaleString() }}</span></div>
                  </div>
                  <button
                    v-if="selectedLot.status === 'disponible'"
                    @click="reservationLot = selectedLot"
                    className="w-full bg-brand-forest text-white py-3 rounded-2xl font-bold text-sm shadow-md"
                  >
                    Me interesa reservar este lote
                  </button>
                </div>
              </div>
            </section>
          </div>

          <!-- Pantalla 4: UBICACION -->
          <div v-else-if="activeScreen === 'ubicacion'" className="py-6">
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div className="mb-6">
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest block mb-1">PANTALLA: UBICACIÓN Y RUTAS</span>
                <h2 className="text-3xl font-extrabold text-slate-900">Ubicación Estratégica</h2>
              </div>
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <div className="lg:col-span-6 space-y-4">
                  <div className="glass-card p-4 rounded-2xl">
                    <h4 className="font-bold text-slate-800 text-sm">Colegios y Centros Educativos (5 min)</h4>
                    <p className="text-xs text-slate-500 mt-1">Colegio Montessori, El Roble y accesos rápidos.</p>
                  </div>
                  <div className="glass-card p-4 rounded-2xl">
                    <h4 className="font-bold text-slate-800 text-sm">Supermercados y Comercio (10 min)</h4>
                    <p className="text-xs text-slate-500 mt-1">La Torre, Walmart y centros comerciales.</p>
                  </div>
                </div>
                <div className="lg:col-span-6 rounded-3xl overflow-hidden shadow-xl border-4 border-white h-[320px]">
                  <img src="https://lh3.googleusercontent.com/aida-public/AB6AXuAFQB1o4vdkcwzHfd5bED3ZafhEWFEGNp8YZdC7aK7hF7jDDtbj94c5h5OJbwQ8RQUZsZamDB60qyWMjX44YpH1rs8V48icf5DSuxhSCTUky6UdYrXUS0vr2qtQ-Gv7UBZ1n3Qnk2LrLLpRsItTanABz_3XdK38ZoV24-9PwwSFtxJyRs4xASteQTRxoqqSXATopsBS5T6V6QxoOL6x7IDvQTWm9WKqqcVKf86FhYd9sjvphU1xyQ" alt="Ubicacion" className="w-full h-full object-cover" />
                </div>
              </div>
            </section>
          </div>

          <!-- Pantalla 5: GALERIA -->
          <div v-else-if="activeScreen === 'galeria'" className="py-6">
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
              <div className="mb-6">
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest block mb-1">PANTALLA: GALERÍA DE FOTOS</span>
                <h2 className="text-3xl font-extrabold text-slate-900">Fotos e Infraestructura</h2>
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                  v-for="(photo, idx) in GALLERY_ITEMS"
                  :key="photo.id"
                  @click="lightboxIndex = idx"
                  className="group relative rounded-3xl overflow-hidden shadow-md h-64 border-2 border-white cursor-pointer"
                >
                  <img :src="photo.imageUrl" :alt="photo.title" className="w-full h-full object-cover group-hover:scale-105 transition" />
                  <div className="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent p-4 flex flex-col justify-end text-white">
                    <span className="text-[10px] font-bold uppercase bg-brand-forest px-2 py-0.5 rounded-full w-fit mb-1">{{ photo.tag }}</span>
                    <h4 className="font-extrabold text-sm">{{ photo.title }}</h4>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Pantalla 6: CONTACTO -->
          <div v-else-if="activeScreen === 'contacto'" className="py-6">
            <section className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
              <div className="glass-card rounded-3xl p-8 shadow-xl border border-white">
                <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest block mb-1 text-center">PANTALLA: CONTACTO Y COTIZACIÓN</span>
                <h2 className="text-2xl font-extrabold text-slate-900 text-center mb-6">Agenda tu visita</h2>
                <form @submit.prevent="contactSubmitted = true" className="space-y-4 text-xs">
                  <div>
                    <label className="block font-bold text-slate-700 uppercase mb-1">Nombre Completo</label>
                    <input type="text" required v-model="contactForm.nombre" className="w-full p-3 rounded-xl border bg-white" placeholder="Ej. Carlos Mendoza" />
                  </div>
                  <div>
                    <label className="block font-bold text-slate-700 uppercase mb-1">Teléfono / WhatsApp</label>
                    <input type="tel" required v-model="contactForm.telefono" className="w-full p-3 rounded-xl border bg-white" placeholder="+502 5555-5555" />
                  </div>
                  <button type="submit" className="w-full bg-brand-forest text-white py-3 rounded-xl font-bold text-sm shadow-md">Enviar Consulta →</button>
                </form>
              </div>
            </section>
          </div>

          <!-- Banner CTA Bottom -->
          <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 my-10">
            <div className="bg-brand-forest-dark text-white rounded-3xl p-6 flex items-center justify-between shadow-xl">
              <div>
                <h3 className="text-lg font-extrabold">¿Tienes dudas o quieres cotizar tu terreno?</h3>
                <p className="text-xs text-emerald-200">Asesoría directa en oficinas o en terreno.</p>
              </div>
              <button @click="activeScreen = 'contacto'" className="bg-white text-brand-forest px-5 py-2.5 rounded-full font-bold text-xs shadow-md">Contáctanos →</button>
            </div>
          </div>
        </div>
      </template>
    </main>

    <!-- Main Footer -->
    <Footer @navigate="handleNavigate" />

    <!-- Modals -->
    <ReservationModal
      :lot="reservationLot"
      @close="reservationLot = null"
    />

    <VideoModal
      :isOpen="isVideoModalOpen"
      @close="isVideoModalOpen = false"
      @selectLot="handleSelectLot('l-12')"
    />

    <LightboxModal
      :currentIndex="lightboxIndex"
      :items="GALLERY_ITEMS"
      @close="lightboxIndex = null"
      @prev="lightboxIndex = lightboxIndex !== null ? (lightboxIndex > 0 ? lightboxIndex - 1 : GALLERY_ITEMS.length - 1) : null"
      @next="lightboxIndex = lightboxIndex !== null ? (lightboxIndex < GALLERY_ITEMS.length - 1 ? lightboxIndex + 1 : 0) : null"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import Navbar from '../../components/layout/Navbar.vue';
import Footer from '../../components/layout/Footer.vue';
import ReservationModal from '../../components/modals/ReservationModal.vue';
import VideoModal from '../../components/modals/VideoModal.vue';
import LightboxModal from '../../components/modals/LightboxModal.vue';

import { LOTS_DATA, GALLERY_ITEMS } from '../../data/lotData.js';
import logoUrl from '../../assets/images/Logo.jpg';

import {
  Grid3X3,
  ArrowRight,
  Play,
  TrendingUp,
  ShieldCheck,
  Trees,
  MapPin,
  Check,
  ChevronLeft,
  ChevronRight,
  Compass,
  Zap,
  Sparkles,
  Home as HomeIcon,
  MessageCircle,
  CheckCircle2,
  School,
  ShoppingBag,
  Navigation,
  Clock,
  Droplets,
  Rocket
} from 'lucide-vue-next';

const activeScreen = ref('inicio');
const viewMode = ref('landing');
const selectedLotId = ref('l-12');
const reservationLot = ref(null);
const isVideoModalOpen = ref(false);
const lightboxIndex = ref(null);
const heroImageError = ref(false);

const activeThumbIndex = ref(0);
const statusFilter = ref('all');
const metrajeFilter = ref('220-300');
const activeLocationTab = ref('colegios');

const contactSubmitted = ref(false);
const contactForm = ref({
  nombre: '',
  telefono: '',
  email: '',
  mensaje: ''
});

const galleryThumbs = [
  {
    title: 'Plano Aéreo Fase 1',
    url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB94Y7dtdtagYRkl3k2SRaUjEIOLcKtFG4A_rnzD6os-_m0u5MivUBtUIZxsxQ9I_pfqeiKfQaPRKH7YxzVP3ltBbIo4MwR1Mjsv-tsbUIVn0qXpsMmP_zVSoobhVVNopsYTXExg8rOTVr9whd-5s0YMJyXujA2gFG5Lm3F9ObtZBlNGxKzrVue9S5uHucCJuyBnLi7a5QkXoeJVP59Dk67dYfb-tRT5x5NZfDSQmDnlzzh0lZnXQ',
    isMasterplan: true,
  },
  {
    title: 'Entrada Monumental',
    url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA1LHcZwlIWINnWp_Qy9Hbfnbv5QOiFNlUVmBhYGJzcy7eAA4txkPPEek41dfhQU60Kt_KdHrlvLPwHbU_Dyn5EI4aFeRAs_0lUEXI5yEzwlBn9ZLoaVlspctmL7iQINa49IXny5owFpBflK_EzYZC4tTFg9KMifCQHvpr7JenED65gX04A1sSPFG_dah5jAJMjWgowvguwB22bsww9pNG8_0svZBtzq59xH55erGCz8oghobLnCg',
    isMasterplan: false,
  },
  {
    title: 'Áreas verdes y bulevar',
    url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuA8xKPRr1gDdhvOa-9Mo5O9RE9T9t8raftSAfhHh4qP4vExV5lfS7_eBc-g22XIRZe2rGNAyslT4GX_RCy_OerFvQxZKoO9bSm--f8-O8CEvo6oN47lcC00KFaBQ_MxL38aG3Dmb57QK0VvD8g7YqGfYmzz2SVBODd93tFnky-RGe1wnX-Urao8nqXX4yJ_-sfufPRMe16EeXlNQxFEVm1Q8OJnlyMBhb7tYaFffKmEgP8gcf8fBA',
    isMasterplan: false,
  },
  {
    title: 'Vistas hacia montañas',
    url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAlW3OUNjRvcXL7YO702dNlwBxOsnYgTwF-lUekf4tmHSTfnoY1-vqiHufHJHrdYbyCg3AQoO5DUb3Ew4TQoGUTUn8LQnaG6SgqHKm8dYlTHflPVqr2136UZ4NlbnhcIzneaIR4BhbAkHsehqVn8tn3wYIhnCSej-XkFDoosoN3Zu3bA2uCS4uysD5oq9LZ9j6EquO0xCBIcCNUsTnkJKfqwdC1EcSXLHSoE0tiH9z4MhvaFuSARg',
    isMasterplan: false,
  },
  {
    title: 'Casa club y amenidades',
    url: 'https://lh3.googleusercontent.com/aida-public/AB6AXuDy4vudjlYYCO2h6N2_FRLRd1G5sgRYU_9fuwdEOlEr-GQC44fF48Ra455wWaqjh7h9H75Acn_GQdZkO5hWQIYu91fi8jUiNUW_D_BIRTkenc0HmP6jxfptHrWePtcOX9-lkIWmawMIWjVvAOoIOzq8nMZZVeI2TiUsia5Pi-THTLcevCJJeGc_kBIt75Q7nC68oCo_abKMQEvduuoQKvL4T-Psh6zpnh4Z5GYdIXppazM5ZyiIhg',
    isMasterplan: false,
  },
];

const selectedLot = computed(() => {
  return LOTS_DATA.find((l) => l.id === selectedLotId.value) || LOTS_DATA[11];
});

const handleSelectLot = (lotId) => {
  selectedLotId.value = lotId;
  if (viewMode.value === 'screens') {
    activeScreen.value = 'lotes';
  } else {
    const el = document.getElementById('lotes');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  }
};

const handleNavigate = (sectionId) => {
  activeScreen.value = sectionId;
  if (viewMode.value === 'landing') {
    const el = document.getElementById(sectionId);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  }
};

const isLotMatching = (lot) => {
  if (statusFilter.value !== 'all' && lot.status !== statusFilter.value) return false;
  if (metrajeFilter.value === '180-220' && (lot.areaM2 < 180 || lot.areaM2 > 220)) return false;
  if (metrajeFilter.value === '220-300' && (lot.areaM2 < 220 || lot.areaM2 > 300)) return false;
  if (metrajeFilter.value === '300+' && lot.areaM2 <= 300) return false;
  return true;
};

const getLotStatusStyle = (lot, isSelected) => {
  if (isSelected) {
    return 'bg-brand-forest ring-4 ring-brand-forest/30 text-white font-extrabold shadow-md transform scale-105 border-white';
  }
  if (lot.status === 'vendido') {
    return 'bg-slate-300 text-slate-500 font-bold border-white cursor-not-allowed opacity-80';
  }
  if (lot.status === 'reservado') {
    return 'bg-amber-400 hover:bg-amber-500 text-amber-950 font-bold border-white shadow-xs cursor-pointer';
  }
  return 'bg-emerald-500 hover:bg-emerald-600 text-white font-bold border-white shadow-xs cursor-pointer hover:scale-102';
};
</script>
