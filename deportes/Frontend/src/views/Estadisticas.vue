<template>
  <div class="min-h-screen flex flex-col md:flex-row bg-background text-on-background">
    <!-- Desktop SideNav -->
    <aside class="hidden md:flex flex-col h-screen w-64 bg-surface-container-lowest border-r border-outline-variant/30 shadow-xl p-gutter sticky top-0 shrink-0 z-40">
      <div class="mb-stack-lg">
        <router-link to="/" class="text-headline-lg font-headline-lg text-primary-fixed uppercase tracking-tighter">DEPORTES</router-link>
      </div>
      <nav class="flex-1 space-y-2">
        <router-link to="/" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface transition-all duration-200 rounded-lg">
          <span class="material-symbols-outlined font-light">dashboard</span>
          <span class="text-label-sm font-label-sm uppercase">Inicio</span>
        </router-link>
        <span class="flex items-center gap-3 px-4 py-3 bg-primary-container text-on-primary-container rounded-lg ring-1 ring-primary-fixed/50 scale-[0.98]">
          <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">bar_chart</span>
          <span class="text-label-sm font-label-sm uppercase font-bold">Estadísticas</span>
        </span>
        <router-link to="/historial-partidos" class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-variant/50 hover:text-on-surface transition-all duration-200 rounded-lg">
          <span class="material-symbols-outlined font-light">sports_soccer</span>
          <span class="text-label-sm font-label-sm uppercase">Historial</span>
        </router-link>
      </nav>
      <div class="mt-auto pt-stack-md border-t border-outline-variant/30">
        <router-link to="/login" class="w-full flex items-center justify-center gap-2 py-3 bg-transparent border border-outline-variant text-on-surface hover:border-primary-fixed hover:text-primary-fixed transition-colors rounded-lg">
          <span class="material-symbols-outlined font-light text-sm">login</span>
          <span class="text-label-sm font-label-sm uppercase">Login</span>
        </router-link>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col relative bg-background overflow-hidden">
      <!-- Mobile Top Nav -->
      <header class="md:hidden flex justify-between items-center px-container-margin py-4 w-full bg-background/80 backdrop-blur-lg border-b border-white/10 sticky top-0 z-50">
        <button @click="$router.go(-1)" class="text-on-surface-variant hover:text-primary-fixed transition-colors">
          <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <h1 class="text-headline-lg-mobile font-headline-lg-mobile text-primary-fixed uppercase tracking-tighter">DEPORTES</h1>
      </header>

      <div class="flex-1 overflow-y-auto">
        <!-- Page Header -->
        <div class="px-container-margin py-stack-lg relative overflow-hidden">
          <div class="absolute inset-0 opacity-20 pointer-events-none bg-[radial-gradient(circle_at_top_right,_var(--color-primary-fixed)_0%,_transparent_50%)]"></div>
          <div class="relative z-10 flex items-center justify-between gap-4">
            <div>
              <h2 class="text-display-lg font-display-lg text-white uppercase">Estadísticas</h2>
              <p class="text-body-md font-body-md text-on-surface-variant max-w-2xl mt-4">Líderes y rankings del torneo en tiempo real.</p>
            </div>
            <button @click="$router.go(-1)" class="hidden md:flex items-center gap-2 px-4 py-2 rounded-full border border-white/10 hover:border-primary-fixed/50 text-label-sm font-label-sm transition-colors text-on-surface bg-[#252525] shrink-0">
              <span class="material-symbols-outlined text-[16px]">arrow_back</span>
              Volver
            </button>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="px-container-margin mb-stack-lg overflow-x-auto pb-4 hide-scrollbar">
          <div class="flex gap-4 inline-flex">
            <button @click="activeTab = 'goleadoresGlobal'" :class="['px-6 py-3 rounded-full font-bold text-label-sm transition-all flex items-center gap-2 shrink-0', activeTab === 'goleadoresGlobal' ? 'bg-primary-fixed text-on-primary-fixed shadow-[0_0_15px_rgba(185,246,63,0.3)]' : 'glass-panel text-on-surface font-semibold hover:bg-surface-variant/50']">
              <span class="material-symbols-outlined text-[18px]">emoji_events</span> Goleadores Global
            </button>
            <button @click="activeTab = 'goleadoresEquipo'" :class="['px-6 py-3 rounded-full font-bold text-label-sm transition-all flex items-center gap-2 shrink-0', activeTab === 'goleadoresEquipo' ? 'bg-primary-fixed text-on-primary-fixed shadow-[0_0_15px_rgba(185,246,63,0.3)]' : 'glass-panel text-on-surface font-semibold hover:bg-surface-variant/50']">
              <span class="material-symbols-outlined text-[18px]">sports_soccer</span> Goleadores por Equipo
            </button>
            <button @click="activeTab = 'porterosGlobal'" :class="['px-6 py-3 rounded-full font-bold text-label-sm transition-all flex items-center gap-2 shrink-0', activeTab === 'porterosGlobal' ? 'bg-white text-black shadow-[0_0_15px_rgba(255,255,255,0.3)]' : 'glass-panel text-on-surface font-semibold hover:bg-surface-variant/50']">
              <span class="material-symbols-outlined text-[18px]">sports_mma</span> Porteros Global
            </button>
            <button @click="activeTab = 'porterosEquipo'" :class="['px-6 py-3 rounded-full font-bold text-label-sm transition-all flex items-center gap-2 shrink-0', activeTab === 'porterosEquipo' ? 'bg-white text-black shadow-[0_0_15px_rgba(255,255,255,0.3)]' : 'glass-panel text-on-surface font-semibold hover:bg-surface-variant/50']">
              <span class="material-symbols-outlined text-[18px]">shield</span> Porteros por Equipo
            </button>
            <button @click="activeTab = 'tarjetas'" :class="['px-6 py-3 rounded-full font-bold text-label-sm transition-all flex items-center gap-2 shrink-0', activeTab === 'tarjetas' ? 'bg-error text-on-error shadow-[0_0_15px_rgba(239,68,68,0.3)]' : 'glass-panel text-on-surface font-semibold hover:bg-surface-variant/50']">
              <span class="material-symbols-outlined text-[18px]">style</span> Equipos con Más Tarjetas
            </button>
          </div>
        </div>

        <div v-if="loading" class="flex flex-col items-center justify-center py-20">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary-fixed mb-4"></div>
          <p class="text-on-surface-variant animate-pulse">Cargando estadísticas...</p>
        </div>

        <div v-else class="relative min-h-[400px] px-container-margin pb-stack-lg">
          <transition name="fade" mode="out-in">
            <div :key="activeTab">

              <!-- TAB 1: GOLEADORES GLOBAL -->
              <section v-if="activeTab === 'goleadoresGlobal'">
                <!-- Podium Top 3 -->
                <div v-if="goleadoresGlobal.length >= 3" class="flex flex-col md:flex-row items-end justify-center gap-6 md:gap-4 mt-12 mb-12">
                  <!-- Rank 2 -->
                  <div class="w-full md:w-1/3 max-w-[220px] flex flex-col items-center relative order-2 md:order-1">
                    <div class="relative w-20 h-20 mb-4 z-10">
                      <img :src="getFotoUrl(goleadoresGlobal[1].foto_ruta)" class="w-full h-full object-cover rounded-full border-4 border-surface-container" @error="handleImageError" />
                      <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-surface-container-high border border-white/20 flex items-center justify-center font-headline-lg-mobile text-white text-sm">2</div>
                    </div>
                    <div class="glass-panel w-full rounded-t-xl rounded-b-lg flex flex-col items-center pt-8 pb-6 px-4 border-t-4 border-t-[#C0C0C0]">
                      <h3 class="text-title-md font-title-md text-white text-center truncate w-full">{{ goleadoresGlobal[1].nombre }}</h3>
                      <p class="text-label-sm font-label-sm text-on-surface-variant mt-1">{{ goleadoresGlobal[1].equipo_nombre }}</p>
                      <div class="mt-4 text-center">
                        <span class="text-headline-lg font-headline-lg text-primary-fixed block">{{ goleadoresGlobal[1].total_goles }}</span>
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">Goles</span>
                      </div>
                    </div>
                  </div>
                  <!-- Rank 1 -->
                  <div class="w-full md:w-1/3 max-w-[240px] flex flex-col items-center relative order-1 md:order-2">
                    <span class="material-symbols-outlined absolute -top-10 text-[56px] text-primary-fixed opacity-40 z-0" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                    <div class="relative w-28 h-28 mb-4 z-10">
                      <img :src="getFotoUrl(goleadoresGlobal[0].foto_ruta)" class="w-full h-full object-cover rounded-full border-4 border-primary-fixed shadow-[0_0_20px_rgba(185,246,63,0.4)]" @error="handleImageError" />
                      <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-10 h-10 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-headline-lg text-lg font-bold">1</div>
                    </div>
                    <div class="glass-panel-active w-full rounded-t-2xl rounded-b-lg flex flex-col items-center pt-10 pb-6 px-4">
                      <h3 class="text-headline-lg-mobile font-headline-lg-mobile text-white text-center truncate w-full">{{ goleadoresGlobal[0].nombre }}</h3>
                      <p class="text-label-sm font-label-sm text-primary-fixed mt-1 font-bold">{{ goleadoresGlobal[0].equipo_nombre }}</p>
                      <div class="mt-4 text-center">
                        <span class="text-display-lg font-display-lg text-white block leading-none">{{ goleadoresGlobal[0].total_goles }}</span>
                        <span class="text-[12px] text-on-surface-variant uppercase tracking-widest font-bold mt-2 block">Goles</span>
                      </div>
                    </div>
                  </div>
                  <!-- Rank 3 -->
                  <div class="w-full md:w-1/3 max-w-[220px] flex flex-col items-center relative order-3">
                    <div class="relative w-16 h-16 mb-4 z-10">
                      <img :src="getFotoUrl(goleadoresGlobal[2].foto_ruta)" class="w-full h-full object-cover rounded-full border-4 border-surface-container" @error="handleImageError" />
                      <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-surface-container-high border border-white/20 flex items-center justify-center font-headline-lg-mobile text-white text-sm">3</div>
                    </div>
                    <div class="glass-panel w-full rounded-t-xl rounded-b-lg flex flex-col items-center pt-8 pb-6 px-4 border-t-4 border-t-[#CD7F32]">
                      <h3 class="text-title-md font-title-md text-white text-center truncate w-full">{{ goleadoresGlobal[2].nombre }}</h3>
                      <p class="text-label-sm font-label-sm text-on-surface-variant mt-1">{{ goleadoresGlobal[2].equipo_nombre }}</p>
                      <div class="mt-4 text-center">
                        <span class="text-headline-lg font-headline-lg text-primary-fixed block">{{ goleadoresGlobal[2].total_goles }}</span>
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">Goles</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Resto del Top 5 -->
                <div v-if="goleadoresGlobal.length > 3" class="bg-gradient-card rounded-xl overflow-hidden p-2 max-w-4xl mx-auto">
                  <div class="flex items-center px-4 py-3 border-b border-white/5 text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">
                    <div class="w-12 text-center">#</div>
                    <div class="flex-1 pl-4">Jugador</div>
                    <div class="w-32 hidden sm:block">Equipo</div>
                    <div class="w-20 text-right pr-4">Goles</div>
                  </div>
                  <div class="flex flex-col gap-1 mt-2">
                    <div v-for="(jugador, index) in goleadoresGlobal.slice(3, 5)" :key="jugador.id" class="flex items-center px-4 py-3 rounded-lg hover:bg-surface-variant/30 transition-colors group">
                      <div class="w-12 text-center font-headline-lg-mobile text-on-surface-variant text-lg">{{ index + 4 }}</div>
                      <div class="flex-1 pl-4 flex items-center gap-4 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-surface-container-high overflow-hidden border border-white/10 shrink-0">
                          <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                        </div>
                        <div class="min-w-0">
                          <div class="text-body-md font-body-md text-white font-semibold truncate">{{ jugador.nombre }}</div>
                          <div class="text-label-sm font-label-sm text-on-surface-variant sm:hidden truncate">{{ jugador.equipo_nombre }}</div>
                        </div>
                      </div>
                      <div class="w-32 hidden sm:block text-body-md text-on-surface-variant truncate">{{ jugador.equipo_nombre }}</div>
                      <div class="w-20 text-right pr-4 font-headline-lg-mobile text-primary-fixed text-xl group-hover:text-white transition-colors">{{ jugador.total_goles }}</div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- TAB 2: GOLEADORES POR EQUIPO -->
              <section v-if="activeTab === 'goleadoresEquipo'">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-gutter">
                  <div v-for="jugador in goleadoresPorEquipo" :key="jugador.id" class="card-gradient border border-outline-variant/30 rounded-xl overflow-hidden hover:border-primary-fixed/50 transition-all hover:-translate-y-1 group">
                    <div class="h-16 bg-surface-container relative">
                      <div class="absolute inset-0 opacity-30 bg-center bg-cover blur-sm" :style="{ backgroundImage: `url(${getFotoUrl(jugador.equipo_foto)})` }"></div>
                      <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-20 h-20 rounded-full border-4 border-surface-container-lowest overflow-hidden z-10 bg-surface-container-high shadow-lg group-hover:border-primary-fixed transition-colors">
                        <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                      </div>
                    </div>
                    <div class="pt-10 pb-4 px-4 text-center">
                      <h4 class="text-title-md font-title-md text-on-surface truncate">{{ jugador.nombre }}</h4>
                      <div class="flex items-center justify-center gap-1.5 mt-1 mb-4">
                        <img :src="getFotoUrl(jugador.equipo_foto)" class="w-3 h-3 rounded-full" @error="handleImageError" />
                        <p class="text-label-sm font-label-sm text-on-surface-variant truncate">{{ jugador.equipo_nombre }}</p>
                      </div>
                      <div class="inline-flex items-center gap-2 bg-primary-fixed/10 px-4 py-1.5 rounded-full border border-primary-fixed/20">
                        <span class="material-symbols-outlined text-[16px] text-primary-fixed">sports_soccer</span>
                        <span class="text-primary-fixed font-black text-xl">{{ jugador.total_goles }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- TAB 3: PORTEROS GLOBAL -->
              <section v-if="activeTab === 'porterosGlobal'">
                <div v-if="porterosGlobal.length >= 3" class="flex flex-col md:flex-row items-end justify-center gap-6 md:gap-4 mt-12 mb-12">
                  <!-- Rank 2 -->
                  <div class="w-full md:w-1/3 max-w-[220px] flex flex-col items-center relative order-2 md:order-1">
                    <div class="relative w-20 h-20 mb-4 z-10">
                      <img :src="getFotoUrl(porterosGlobal[1].foto_ruta)" class="w-full h-full object-cover rounded-full border-4 border-surface-container" @error="handleImageError" />
                      <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-surface-container-high border border-white/20 flex items-center justify-center font-headline-lg-mobile text-white text-sm">2</div>
                    </div>
                    <div class="glass-panel w-full rounded-t-xl rounded-b-lg flex flex-col items-center pt-8 pb-6 px-4 border-t-4 border-t-[#C0C0C0]">
                      <h3 class="text-title-md font-title-md text-white text-center truncate w-full">{{ porterosGlobal[1].nombre }}</h3>
                      <p class="text-label-sm font-label-sm text-on-surface-variant mt-1">{{ porterosGlobal[1].equipo_nombre }}</p>
                      <div class="mt-4 text-center">
                        <span class="text-headline-lg font-headline-lg text-white block">{{ porterosGlobal[1].total_goles_recibidos }}</span>
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">En Contra</span>
                      </div>
                    </div>
                  </div>
                  <!-- Rank 1 -->
                  <div class="w-full md:w-1/3 max-w-[240px] flex flex-col items-center relative order-1 md:order-2">
                    <span class="material-symbols-outlined absolute -top-10 text-[56px] text-white opacity-40 z-0" style="font-variation-settings: 'FILL' 1;">shield</span>
                    <div class="relative w-28 h-28 mb-4 z-10">
                      <img :src="getFotoUrl(porterosGlobal[0].foto_ruta)" class="w-full h-full object-cover rounded-full border-4 border-white shadow-[0_0_20px_rgba(255,255,255,0.4)]" @error="handleImageError" />
                      <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-10 h-10 rounded-full bg-white text-black flex items-center justify-center font-headline-lg text-lg font-bold">1</div>
                    </div>
                    <div class="glass-panel w-full rounded-t-2xl rounded-b-lg flex flex-col items-center pt-10 pb-6 px-4 border border-white/30">
                      <h3 class="text-headline-lg-mobile font-headline-lg-mobile text-white text-center truncate w-full">{{ porterosGlobal[0].nombre }}</h3>
                      <p class="text-label-sm font-label-sm text-white mt-1 font-bold">{{ porterosGlobal[0].equipo_nombre }}</p>
                      <div class="mt-4 text-center">
                        <span class="text-display-lg font-display-lg text-white block leading-none">{{ porterosGlobal[0].total_goles_recibidos }}</span>
                        <span class="text-[12px] text-on-surface-variant uppercase tracking-widest font-bold mt-2 block">En Contra</span>
                      </div>
                    </div>
                  </div>
                  <!-- Rank 3 -->
                  <div class="w-full md:w-1/3 max-w-[220px] flex flex-col items-center relative order-3">
                    <div class="relative w-16 h-16 mb-4 z-10">
                      <img :src="getFotoUrl(porterosGlobal[2].foto_ruta)" class="w-full h-full object-cover rounded-full border-4 border-surface-container" @error="handleImageError" />
                      <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-surface-container-high border border-white/20 flex items-center justify-center font-headline-lg-mobile text-white text-sm">3</div>
                    </div>
                    <div class="glass-panel w-full rounded-t-xl rounded-b-lg flex flex-col items-center pt-8 pb-6 px-4 border-t-4 border-t-[#CD7F32]">
                      <h3 class="text-title-md font-title-md text-white text-center truncate w-full">{{ porterosGlobal[2].nombre }}</h3>
                      <p class="text-label-sm font-label-sm text-on-surface-variant mt-1">{{ porterosGlobal[2].equipo_nombre }}</p>
                      <div class="mt-4 text-center">
                        <span class="text-headline-lg font-headline-lg text-white block">{{ porterosGlobal[2].total_goles_recibidos }}</span>
                        <span class="text-[10px] text-on-surface-variant uppercase tracking-widest font-bold">En Contra</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="porterosGlobal.length > 3" class="bg-gradient-card rounded-xl overflow-hidden p-2 max-w-4xl mx-auto">
                  <div class="flex items-center px-4 py-3 border-b border-white/5 text-label-sm font-label-sm text-on-surface-variant uppercase tracking-wider">
                    <div class="w-12 text-center">#</div>
                    <div class="flex-1 pl-4">Portero</div>
                    <div class="w-32 hidden sm:block">Equipo</div>
                    <div class="w-24 text-right pr-4">En Contra</div>
                  </div>
                  <div class="flex flex-col gap-1 mt-2">
                    <div v-for="(jugador, index) in porterosGlobal.slice(3, 5)" :key="jugador.id" class="flex items-center px-4 py-3 rounded-lg hover:bg-surface-variant/30 transition-colors group">
                      <div class="w-12 text-center font-headline-lg-mobile text-on-surface-variant text-lg">{{ index + 4 }}</div>
                      <div class="flex-1 pl-4 flex items-center gap-4 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-surface-container-high overflow-hidden border border-white/10 shrink-0">
                          <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                        </div>
                        <div class="min-w-0">
                          <div class="text-body-md font-body-md text-white font-semibold truncate">{{ jugador.nombre }}</div>
                          <div class="text-label-sm font-label-sm text-on-surface-variant sm:hidden truncate">{{ jugador.equipo_nombre }}</div>
                        </div>
                      </div>
                      <div class="w-32 hidden sm:block text-body-md text-on-surface-variant truncate">{{ jugador.equipo_nombre }}</div>
                      <div class="w-24 text-right pr-4 font-headline-lg-mobile text-white text-xl group-hover:text-primary-fixed transition-colors">{{ jugador.total_goles_recibidos }}</div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- TAB 4: PORTEROS POR EQUIPO -->
              <section v-if="activeTab === 'porterosEquipo'">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-gutter">
                  <div v-for="jugador in porterosPorEquipo" :key="jugador.id" class="card-gradient border border-outline-variant/30 rounded-xl overflow-hidden hover:border-white/50 transition-all hover:-translate-y-1 group">
                    <div class="h-16 bg-surface-container relative">
                      <div class="absolute inset-0 opacity-30 bg-center bg-cover blur-sm" :style="{ backgroundImage: `url(${getFotoUrl(jugador.equipo_foto)})` }"></div>
                      <div class="absolute -bottom-8 left-1/2 -translate-x-1/2 w-20 h-20 rounded-full border-4 border-surface-container-lowest overflow-hidden z-10 bg-surface-container-high shadow-lg group-hover:border-white transition-colors">
                        <img :src="getFotoUrl(jugador.foto_ruta)" class="w-full h-full object-cover" @error="handleImageError" />
                      </div>
                    </div>
                    <div class="pt-10 pb-4 px-4 text-center">
                      <h4 class="text-title-md font-title-md text-on-surface truncate">{{ jugador.nombre }}</h4>
                      <div class="flex items-center justify-center gap-1.5 mt-1 mb-4">
                        <img :src="getFotoUrl(jugador.equipo_foto)" class="w-3 h-3 rounded-full" @error="handleImageError" />
                        <p class="text-label-sm font-label-sm text-on-surface-variant truncate">{{ jugador.equipo_nombre }}</p>
                      </div>
                      <div class="inline-flex items-center gap-2 bg-surface-container-high px-4 py-1.5 rounded-full border border-outline-variant/50">
                        <span class="material-symbols-outlined text-[16px] text-white">shield</span>
                        <span class="text-white font-black text-xl">{{ jugador.total_goles_recibidos }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <!-- TAB 5: TARJETAS (FAIR PLAY) -->
              <section v-if="activeTab === 'tarjetas'">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
                  <div v-for="(equipo, index) in tarjetasEquipos" :key="equipo.id" class="card-gradient border border-outline-variant/30 rounded-xl p-5 flex flex-col relative overflow-hidden group hover:border-error/40 transition-colors">
                    <div class="absolute -right-4 -bottom-6 text-8xl font-black text-white/5 z-0 select-none">{{ index + 1 }}</div>
                    <div class="relative z-10 flex items-center gap-4 mb-5">
                      <div class="w-14 h-14 rounded-full overflow-hidden border-2 border-outline-variant/50 bg-surface-container-lowest shrink-0">
                        <img :src="getFotoUrl(equipo.foto_ruta)" class="w-full h-full object-cover p-1" @error="handleImageError" />
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-title-md font-title-md text-on-surface leading-tight truncate">{{ equipo.nombre }}</h4>
                        <span class="text-label-sm font-label-sm text-on-surface-variant">{{ equipo.total_tarjetas }} amonestaciones</span>
                      </div>
                    </div>
                    <div class="relative z-10 flex gap-2 w-full mt-auto">
                      <div class="flex-1 bg-surface-container-lowest rounded-lg p-2 flex flex-col items-center justify-center border border-outline-variant/30">
                        <div class="w-4 h-5 bg-yellow-400 rounded-sm mb-1 shadow-sm shadow-yellow-400/20"></div>
                        <span class="text-body-md font-bold text-on-surface">{{ equipo.total_amarillas }}</span>
                      </div>
                      <div class="flex-1 bg-surface-container-lowest rounded-lg p-2 flex flex-col items-center justify-center border border-outline-variant/30">
                        <div class="w-4 h-5 bg-error rounded-sm mb-1 shadow-sm shadow-error/20"></div>
                        <span class="text-body-md font-bold text-on-surface">{{ equipo.total_rojas }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

            </div>
          </transition>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api, { IMAGE_BASE_URL } from '../services/api';

const loading = ref(true);
const activeTab = ref('goleadoresGlobal'); // Default tab

const goleadoresGlobal = ref([]);
const porterosGlobal = ref([]);
const tarjetasEquipos = ref([]);
const goleadoresPorEquipo = ref([]);
const porterosPorEquipo = ref([]);

const defaultAvatar = 'https://ui-avatars.com/api/?name=Jugador&background=1f2937&color=fff';

const getFotoUrl = (ruta) => {
  if (!ruta) return defaultAvatar;
  // Si la ruta ya tiene http, retornarla, sino concatenar el baseUrl
  if (ruta.startsWith('http')) return ruta;
  return `${IMAGE_BASE_URL}${ruta}`;
};

const handleImageError = (e) => {
  e.target.src = defaultAvatar;
};

const fetchEstadisticas = async () => {
  loading.value = true;
  try {
    const [resGoles, resPorteros, resTarjetas, resGolesEq, resPorterosEq] = await Promise.all([
      api.get(`/rankings/goleadores?limit=5`),
      api.get(`/rankings/porteros?limit=5`),
      api.get(`/rankings/tarjetas-equipos?limit=10`),
      api.get(`/rankings/goleadores-por-equipo`),
      api.get(`/rankings/porteros-por-equipo`)
    ]);

    goleadoresGlobal.value = resGoles.data;
    porterosGlobal.value = resPorteros.data;
    tarjetasEquipos.value = resTarjetas.data;
    goleadoresPorEquipo.value = resGolesEq.data;
    porterosPorEquipo.value = resPorterosEq.data;

  } catch (error) {
    console.error('Error fetching stats:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchEstadisticas();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}
.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
