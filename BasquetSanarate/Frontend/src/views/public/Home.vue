<template>
  <div class="flex flex-col w-full">
    <!-- HERO SECTION -->
    <section class="relative w-full overflow-hidden bg-inverse-surface text-surface py-space-3xl md:py-space-3xl px-gutter-mobile md:px-gutter-desktop">
      <div class="absolute inset-0 pointer-events-none select-none opacity-5 flex items-center justify-center overflow-hidden">
        <span class="font-headline-xl text-[160px] md:text-[280px] leading-none uppercase font-bold text-primary-container tracking-tighter transform -rotate-6">SANARATE</span>
      </div>

      <div class="max-w-[1280px] mx-auto w-full relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-center">
        <div class="lg:col-span-7 flex flex-col items-start space-y-space-md">
          <div class="inline-flex items-center gap-space-xs bg-surface-variant/10 text-primary-container px-space-md py-space-2xs rounded-full">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-container animate-ping"></span>
            <span class="font-label-pill text-label-pill uppercase tracking-wider">Torneo Apertura 2025 • Temporada Oficial</span>
          </div>

          <div class="relative">
            <h1 class="font-display-hero text-display-hero-mobile md:text-display-hero uppercase tracking-tight text-surface font-extrabold leading-none">
              JUEGA EL <span class="text-primary-container inline-block drop-shadow-[0_0_25px_rgba(204,255,0,0.35)]">PARTIDO,</span><br/>
              VIVE EL <span class="text-primary-container inline-block">MOMENTO</span>
            </h1>
            <div class="mt-space-2xs font-headline-md text-headline-md uppercase tracking-wider text-surface-dim opacity-90">
              Liga de Baloncesto Sanarateca
            </div>
          </div>

          <p class="font-body-lg text-body-lg text-surface-dim max-w-xl leading-relaxed">
            La máxima pasión del deporte de las canastas en Sanarate. Destreza, ritmo frenético y comunidad unida en cada posesión sobre la duela. Únete a la élite del baloncesto departamental.
          </p>

          <div class="pt-space-sm flex flex-wrap items-center gap-space-md w-full sm:w-auto">
            <router-link 
              to="/partidos" 
              class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase px-space-xl py-space-md shadow-[0_8px_24px_-4px_rgba(204,255,0,0.45)] hover:shadow-[0_12px_32px_-2px_rgba(204,255,0,0.65)] hover:scale-105 transition-all duration-200"
            >
              <span class="material-symbols-outlined text-[20px] mr-space-xs">sports_basketball</span>
              Explorar Torneo
            </router-link>
            <router-link 
              to="/partidos" 
              class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-surface-variant/10 text-surface font-label-pill text-label-pill uppercase px-space-xl py-space-md hover:bg-surface-variant/20 transition-all duration-200"
            >
              <span class="material-symbols-outlined text-[20px] mr-space-xs">calendar_month</span>
              Ver Calendario
            </router-link>
          </div>

          <div class="pt-space-md flex items-center gap-space-lg text-surface-dim">
            <div class="flex items-center gap-space-xs">
              <span class="material-symbols-outlined text-primary-container text-[20px]">verified</span>
              <span class="font-label-meta text-label-meta uppercase tracking-wider">Acreditación Oficial CAB</span>
            </div>
            <div class="flex items-center gap-space-xs">
              <span class="material-symbols-outlined text-primary-container text-[20px]">stadium</span>
              <span class="font-label-meta text-label-meta uppercase tracking-wider">Gimnasio Municipal Central</span>
            </div>
          </div>
        </div>

        <div class="lg:col-span-5 relative flex items-center justify-center mt-space-lg lg:mt-0">
          <div class="absolute w-72 h-72 md:w-96 md:h-96 rounded-full bg-primary-container/15 blur-3xl -z-0"></div>
          <div class="relative z-10 w-full max-w-[420px] bg-surface-container-low/10 rounded-xl p-space-md backdrop-blur-md shadow-2xl">
            <div class="relative overflow-hidden rounded-lg aspect-[4/5] bg-surface-container-high">
              <img 
                class="w-full h-full object-cover object-top" 
                alt="Jugador destacado de la semana" 
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuBep_RvICFGdr6xGbFrYSGosLSsOi-yhJ9Rpzn-NklhrTkYHA_R0RikwC0-m5ULYbzlbb80vtFBUpxSfICfljFQmgMnFFOfAsNIrS4Hgl3KfELURHH5smRqryEW5bjLaXoCszzQ8r-Zi5zRmbNIyrwHbOFTafkHS-SVvdz_j7vCAE4F-QX0BodIpBdIKYIwau-pdXL0yIchdIQ-1eBjoEZNitqVRqhdjT1oiYwe8XEDzK_YDOgjD3SWWg"
              />
              <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface via-inverse-surface/30 to-transparent"></div>
              <div class="absolute top-space-md left-space-md bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase shadow-md flex items-center gap-space-2xs">
                <span class="w-2 h-2 rounded-full bg-inverse-surface"></span>
                Jugador de la Semana
              </div>
              <div class="absolute bottom-space-md left-space-md right-space-md p-space-md rounded bg-inverse-surface/85 backdrop-blur-md">
                <span class="font-label-meta text-label-meta text-primary-container uppercase tracking-widest">Alero Estrella • Halcones</span>
                <div class="flex items-baseline justify-between mt-space-2xs">
                  <h3 class="font-headline-md text-headline-md text-surface uppercase">Mateo Kiatipis</h3>
                  <span class="font-stat-display text-headline-md text-primary-container">#11</span>
                </div>
                <div class="grid grid-cols-2 gap-space-xs mt-space-sm pt-space-xs bg-surface-variant/10 rounded p-space-xs text-center">
                  <div>
                    <div class="font-headline-md text-headline-md text-surface leading-none">28.4</div>
                    <div class="font-label-meta text-label-meta text-surface-dim uppercase mt-space-2xs">PPG Promedio</div>
                  </div>
                  <div>
                    <div class="font-headline-md text-headline-md text-primary-container leading-none">84%</div>
                    <div class="font-label-meta text-label-meta text-surface-dim uppercase mt-space-2xs">Efectividad</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- METRICS BANNER -->
    <section class="w-full bg-surface-container-lowest py-space-lg shadow-sm">
      <div class="max-w-[1280px] mx-auto px-gutter-mobile md:px-gutter-desktop">
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-space-md">
          <div class="bg-surface-container-low p-space-md rounded-lg flex items-center gap-space-md transition-transform hover:-translate-y-1 duration-200">
            <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[24px]">groups</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-stat-display text-headline-xl text-on-surface leading-none">16</span>
              <span class="font-label-meta text-label-meta text-secondary uppercase truncate">Equipos Registrados</span>
            </div>
          </div>

          <div class="bg-surface-container-low p-space-md rounded-lg flex items-center gap-space-md transition-transform hover:-translate-y-1 duration-200">
            <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-fixed flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[24px]">person</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-stat-display text-headline-xl text-on-surface leading-none">180+</span>
              <span class="font-label-meta text-label-meta text-secondary uppercase truncate">Atletas Activos</span>
            </div>
          </div>

          <div class="bg-surface-container-low p-space-md rounded-lg flex items-center gap-space-md transition-transform hover:-translate-y-1 duration-200">
            <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[24px]">scoreboard</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-stat-display text-headline-xl text-on-surface leading-none">48</span>
              <span class="font-label-meta text-label-meta text-secondary uppercase truncate">Partidos Disputados</span>
            </div>
          </div>

          <div class="bg-surface-container-low p-space-md rounded-lg flex items-center gap-space-md transition-transform hover:-translate-y-1 duration-200">
            <div class="w-12 h-12 rounded-full bg-secondary-container text-on-secondary-fixed flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[24px]">stadium</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-stat-display text-headline-xl text-on-surface leading-none">3</span>
              <span class="font-label-meta text-label-meta text-secondary uppercase truncate">Sedes Municipales</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FIXTURE SECTION -->
    <section class="w-full py-space-2xl px-gutter-mobile md:px-gutter-desktop">
      <div class="max-w-[1280px] mx-auto flex flex-col gap-space-lg">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-space-sm">
          <div>
            <div class="inline-flex items-center gap-space-xs text-primary font-label-meta text-label-meta uppercase tracking-wider mb-space-2xs">
              <span class="w-2 h-2 rounded-full bg-primary"></span>
              Fixture • Jornada 9
            </div>
            <h2 class="font-headline-xl text-headline-xl uppercase text-on-surface leading-tight tracking-tight">
              Próximos Encuentros Destacados
            </h2>
          </div>
          <div class="flex items-center gap-space-xs overflow-x-auto pb-space-2xs">
            <button class="rounded-full bg-primary-container text-on-primary-fixed px-space-md py-space-xs font-label-pill text-label-pill uppercase shadow-sm">
              Destacados
            </button>
            <button class="rounded-full bg-surface-container text-secondary px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-surface-container-high transition-colors">
              Viernes
            </button>
            <button class="rounded-full bg-surface-container text-secondary px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-surface-container-high transition-colors">
              Sábado
            </button>
            <button class="rounded-full bg-surface-container text-secondary px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-surface-container-high transition-colors">
              Domingo
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <!-- Partido 1 (En Vivo) -->
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between gap-space-xs mb-space-md">
              <span class="bg-surface-container text-on-surface-variant font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase">
                Cancha Principal • Sanarate
              </span>
              <span class="bg-primary-container text-on-primary-fixed font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase font-bold animate-pulse">
                En Vivo • Q3
              </span>
            </div>
            <div class="bg-surface-container-low rounded-lg p-space-md my-space-xs">
              <div class="flex items-center justify-between">
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center font-headline-md text-headline-md uppercase shadow-sm mb-space-2xs">
                    TT
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight">Titanes</span>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">Local</span>
                </div>
                <div class="flex flex-col items-center justify-center w-2/12">
                  <div class="bg-inverse-surface text-primary-container px-space-sm py-space-2xs rounded-full font-headline-md text-headline-md leading-none tracking-widest">
                    64-58
                  </div>
                  <span class="font-label-meta text-label-meta text-error font-bold mt-space-2xs">04:12</span>
                </div>
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-surface-container-highest text-on-surface flex items-center justify-center font-headline-md text-headline-md uppercase shadow-sm mb-space-2xs">
                    HL
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight">Halcones</span>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">Visita</span>
                </div>
              </div>
            </div>
            <div class="mt-space-md pt-space-sm flex items-center justify-between">
              <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span class="material-symbols-outlined text-[16px]">schedule</span>
                <span>Hoy • 07:30 PM</span>
              </div>
              <router-link to="/partidos" class="rounded-full bg-surface-container text-on-surface px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-primary-container hover:text-on-primary-fixed transition-colors">
                Ficha Técnica
              </router-link>
            </div>
          </div>

          <!-- Partido 2 -->
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between gap-space-xs mb-space-md">
              <span class="bg-surface-container text-on-surface-variant font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase">
                Polideportivo Municipal
              </span>
              <span class="bg-surface-container-high text-secondary font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase font-semibold">
                Viernes 20
              </span>
            </div>
            <div class="bg-surface-container-low rounded-lg p-space-md my-space-xs">
              <div class="flex items-center justify-between">
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-surface-container-highest text-on-surface flex items-center justify-center font-headline-md text-headline-md uppercase shadow-sm mb-space-2xs">
                    JG
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight">Jaguares</span>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">10-5</span>
                </div>
                <div class="flex flex-col items-center justify-center w-2/12">
                  <div class="bg-primary-container text-on-primary-fixed px-space-sm py-space-2xs rounded-full font-label-pill text-label-pill uppercase font-bold">
                    VS
                  </div>
                  <span class="font-label-meta text-label-meta text-secondary mt-space-2xs">08:45 PM</span>
                </div>
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-surface-container-highest text-on-surface flex items-center justify-center font-headline-md text-headline-md uppercase shadow-sm mb-space-2xs">
                    TR
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight">Toros N.</span>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">14-1</span>
                </div>
              </div>
            </div>
            <div class="mt-space-md pt-space-sm flex items-center justify-between">
              <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span class="material-symbols-outlined text-[16px]">location_on</span>
                <span>Gimnasio Sanarate Norte</span>
              </div>
              <router-link to="/partidos" class="rounded-full bg-surface-container text-on-surface px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-primary-container hover:text-on-primary-fixed transition-colors">
                Previa
              </router-link>
            </div>
          </div>

          <!-- Partido 3 -->
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col justify-between relative overflow-hidden">
            <div class="flex items-center justify-between gap-space-xs mb-space-md">
              <span class="bg-surface-container text-on-surface-variant font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase">
                Cancha B • Barrio San Jerónimo
              </span>
              <span class="bg-surface-container-high text-secondary font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase font-semibold">
                Sábado 21
              </span>
            </div>
            <div class="bg-surface-container-low rounded-lg p-space-md my-space-xs">
              <div class="flex items-center justify-between">
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-surface-container-highest text-on-surface flex items-center justify-center font-headline-md text-headline-md uppercase shadow-sm mb-space-2xs">
                    LF
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight">Leones</span>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">8-7</span>
                </div>
                <div class="flex flex-col items-center justify-center w-2/12">
                  <div class="bg-primary-container text-on-primary-fixed px-space-sm py-space-2xs rounded-full font-label-pill text-label-pill uppercase font-bold">
                    VS
                  </div>
                  <span class="font-label-meta text-label-meta text-secondary mt-space-2xs">06:00 PM</span>
                </div>
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-surface-container-highest text-on-surface flex items-center justify-center font-headline-md text-headline-md uppercase shadow-sm mb-space-2xs">
                    CR
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight">Caimanes</span>
                  <span class="font-label-meta text-label-meta text-secondary uppercase">7-8</span>
                </div>
              </div>
            </div>
            <div class="mt-space-md pt-space-sm flex items-center justify-between">
              <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span class="material-symbols-outlined text-[16px]">sports</span>
                <span>Jornada 9 Regular</span>
              </div>
              <router-link to="/partidos" class="rounded-full bg-surface-container text-on-surface px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-primary-container hover:text-on-primary-fixed transition-colors">
                Previa
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DESTACADOS SECTION -->
    <section class="w-full bg-surface-container py-space-2xl px-gutter-mobile md:px-gutter-desktop">
      <div class="max-w-[1280px] mx-auto flex flex-col gap-space-xl">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm">
          <div>
            <span class="font-label-meta text-label-meta uppercase text-primary tracking-widest font-bold">Talento Departamental</span>
            <h2 class="font-headline-xl text-headline-xl uppercase text-on-surface leading-tight">
              Figuras & Equipos Destacados
            </h2>
          </div>
          <router-link to="/jugadores" class="inline-flex items-center gap-space-2xs font-label-pill text-label-pill uppercase text-on-surface hover:text-primary transition-colors">
            <span>Ver todos los atletas</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </router-link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <!-- Tarjeta Jugador 1 -->
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="relative overflow-hidden rounded-lg aspect-square bg-surface-container-high mb-space-md">
              <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Carlos Morales" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBIyvXZ_Fc1MvjhswePhohHbScVXd81uslD4SpwBjFjz2jFEiyyszMfwPrfF8QreLGBss5943YDyn0hu0KOdn6vty7KIi-1GpFTzAxHWY0NPlWM1y-ZquTW169t7iBS2bdppFgPuRhSd-XnistJqgapWy1xT2cPJAK4JdQ-_BNHLgHizddRkcvHoE7EN5MPshl5RBQ4jz1T2SHygDhbm61DqufqVOEWwlZmlQ1zkL2zaC77jyjLXJZtAA"/>
              <div class="absolute top-space-sm left-space-sm bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase font-bold">
                Top Scorer
              </div>
              <div class="absolute bottom-space-sm right-space-sm bg-inverse-surface/80 text-surface rounded-full px-space-sm py-space-2xs font-label-meta text-label-meta">
                #7 • Base
              </div>
            </div>
            <div>
              <span class="font-label-meta text-label-meta text-secondary uppercase">Titanes de Sanarate</span>
              <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface mt-space-2xs">Carlos Morales</h3>
              <p class="font-body-sm text-body-sm text-secondary mt-space-2xs">Líder indiscutible en triples convertidos y robos de balón en lo que va del torneo.</p>
            </div>
            <div class="grid grid-cols-2 gap-space-xs mt-space-md pt-space-md bg-surface-container-low p-space-sm rounded">
              <div>
                <div class="font-headline-md text-headline-md text-on-surface leading-none">31.2</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">PPG Anotación</div>
              </div>
              <div>
                <div class="font-headline-md text-headline-md text-primary leading-none">52%</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Tiros Triples</div>
              </div>
            </div>
          </div>

          <!-- Tarjeta Jugador 2 -->
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="relative overflow-hidden rounded-lg aspect-square bg-surface-container-high mb-space-md">
              <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="David Alvarado" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD5oqBzyoi5QCGtg9c5XKMBDh_H0H1EOC3jv4wvTt1vioXmr9thRgTfYoilKJeCmBoqHCZI8tML5v8rMGxFyatIMlNU5isWa6VFbju8MBxa2dTPQqkvoDtqcpIeOVgYaRmD7x1xSBAoivhi-wl-A1J6bVDN3jZ4gFRD5wIsnUOtd8efqyJp9iWU1vWmftmS-Lwi3eTnbk2UC4iagpfe6LWhVl5K0HUeOsoI9LfXkAKbJKV946Wsb7O1XQ"/>
              <div class="absolute top-space-sm left-space-sm bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase font-bold">
                MVP Defensivo
              </div>
              <div class="absolute bottom-space-sm right-space-sm bg-inverse-surface/80 text-surface rounded-full px-space-sm py-space-2xs font-label-meta text-label-meta">
                #15 • Pívot
              </div>
            </div>
            <div>
              <span class="font-label-meta text-label-meta text-secondary uppercase">Toros del Norte</span>
              <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface mt-space-2xs">David Alvarado</h3>
              <p class="font-body-sm text-body-sm text-secondary mt-space-2xs">Muralla en la pintura con récord de bloqueos defensivos en una sola temporada.</p>
            </div>
            <div class="grid grid-cols-2 gap-space-xs mt-space-md pt-space-md bg-surface-container-low p-space-sm rounded">
              <div>
                <div class="font-headline-md text-headline-md text-on-surface leading-none">14.8</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Rebotes/P</div>
              </div>
              <div>
                <div class="font-headline-md text-headline-md text-primary leading-none">3.4</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Bloqueos/P</div>
              </div>
            </div>
          </div>

          <!-- Tarjeta Equipo Destacado -->
          <div class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="relative overflow-hidden rounded-lg aspect-square bg-surface-container-high mb-space-md">
              <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="Toros del Norte" src="https://lh3.googleusercontent.com/aida-public/AB6AXuC1ZIzQt34Cy-D9yLeWpFAF7zEc3GdaIenQctB2GXBh_ZOIApVh6RlTF8jmjIsFFIL-YJiDzL5Mg7pwATkUu0-KdiIUfTT_-4uE7SauL3696aJ00u7f_e5AJdn1DjvvMXZ-qSKjr20MTbyEuTz75CsnGBmab-S6z4aKb4QhRNYUXtpJ854opmU_NItt24ZimXvOszTV6x5luLCRcMIalog1nFEUMARtehVtfzzfbSfrRzyykkd2Tg_iKA"/>
              <div class="absolute top-space-sm left-space-sm bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase font-bold">
                Líder del Torneo
              </div>
              <div class="absolute bottom-space-sm right-space-sm bg-inverse-surface/80 text-surface rounded-full px-space-sm py-space-2xs font-label-meta text-label-meta">
                1er Puesto
              </div>
            </div>
            <div>
              <span class="font-label-meta text-label-meta text-secondary uppercase">Plantel Destacado</span>
              <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface mt-space-2xs">Toros del Norte</h3>
              <p class="font-body-sm text-body-sm text-secondary mt-space-2xs">Racha invicta de 8 partidos consecutivos con la mejor ofensiva del campeonato.</p>
            </div>
            <div class="grid grid-cols-2 gap-space-xs mt-space-md pt-space-md bg-surface-container-low p-space-sm rounded">
              <div>
                <div class="font-headline-md text-headline-md text-on-surface leading-none">14 - 1</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Récord V-D</div>
              </div>
              <div>
                <div class="font-headline-md text-headline-md text-primary leading-none">+168</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Diferencial</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CALL TO ACTION / INSCRIPCIONES -->
    <section class="w-full py-space-2xl px-gutter-mobile md:px-gutter-desktop">
      <div class="max-w-[1280px] mx-auto bg-inverse-surface rounded-xl p-space-xl md:p-space-2xl text-surface relative overflow-hidden">
        <div class="absolute right-0 top-0 w-96 h-96 bg-primary-container/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 max-w-2xl flex flex-col gap-space-md">
          <div class="inline-flex items-center gap-space-xs text-primary-container font-label-meta text-label-meta uppercase tracking-widest">
            <span class="material-symbols-outlined text-[18px]">campaign</span>
            Inscripciones y Fanatics Club
          </div>
          <h2 class="font-display-hero text-headline-xl md:text-display-hero uppercase tracking-tight leading-none text-surface">
            ¿Listo para ser parte de la <span class="text-primary-container">Sanarateca</span>?
          </h2>
          <p class="font-body-lg text-body-lg text-surface-dim">
            Inscribe a tu equipo comunal o regístrate como aficionado para recibir alertas en vivo de los partidos, estadísticas detalladas de tus atletas favoritos y acceso exclusivo a las finales.
          </p>
          <div class="pt-space-sm flex flex-col sm:flex-row items-stretch sm:items-center gap-space-md">
            <div class="flex-1 bg-surface-variant/10 rounded-full px-space-md py-space-2xs flex items-center gap-space-xs">
              <span class="material-symbols-outlined text-surface-dim text-[20px]">mail</span>
              <input class="bg-transparent border-none outline-none text-surface placeholder:text-surface-dim text-body-sm w-full py-space-2xs" placeholder="Ingresa tu correo electrónico..." type="email"/>
            </div>
            <button class="rounded-full bg-primary-container text-on-primary-fixed px-space-xl py-space-md font-label-pill text-label-pill uppercase shadow-[0_4px_16px_rgba(204,255,0,0.35)] hover:scale-105 transition-all shrink-0">
              Unirse Ahora
            </button>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
</script>
