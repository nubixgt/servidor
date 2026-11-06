<template>
  <div id="dashboard-canvas" class="flex flex-col gap-5 sm:gap-6 pb-24 select-none animate-fade-in-up">
    <!-- Saludo: quién, qué día y, en una línea, qué toca hoy. -->
    <section class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-bold text-white/60">{{ hoyLargo }}</p>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight leading-tight drop-shadow-md">
          ¡Hola, <span class="text-[#caf300]">{{ firstName }}</span>!
        </h2>
        <p class="text-sm text-white/75 mt-1">{{ resumenDeHoy }}</p>
      </div>
      <button v-if="store.athlete.garminConnected" type="button" @click="store.isHealthOpen = true"
              class="self-start sm:self-auto flex items-center gap-2 h-10 px-4 glass-pill hover:bg-white/15 text-[#f1f0ee] hover:text-[#caf300] rounded-xl text-xs font-extrabold uppercase tracking-wider border border-white/15">
        <span class="material-symbols-outlined text-[18px]">favorite</span>
        <span>Tu forma</span>
      </button>
    </section>

    <!-- ============ SIN SEMANA: los pasos para empezar (la primera experiencia) ============ -->
    <section v-if="planListo && !hasWeek" class="order-1 rounded-3xl border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl shadow-2xl p-5 sm:p-7 space-y-5">
      <div>
        <p class="text-[11px] font-black text-[#caf300] uppercase tracking-widest">Empieza aquí · paso {{ pasos.filter((x) => x.hecho).length + 1 }} de {{ pasos.length }}</p>
        <h3 class="text-xl sm:text-2xl font-black text-white mt-1">Tu primera semana, en {{ pasos.length }} pasos</h3>
        <p class="text-sm text-white/70 mt-1">Tu coach necesita conocerte y leer tu reloj antes de escribirte el plan.</p>
      </div>
      <ol class="space-y-2.5">
        <li v-for="(p, i) in pasos" :key="p.id"
            class="flex flex-wrap sm:flex-nowrap items-center gap-3 rounded-2xl border p-3 sm:p-4"
            :class="p.hecho ? 'border-[#caf300]/30 bg-[#caf300]/5' : (p.id === siguientePaso?.id ? 'border-[#caf300] bg-[#caf300]/10' : 'border-white/10 bg-white/[0.03]')">
          <span class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 font-black text-sm"
                :class="p.hecho ? 'bg-[#caf300] text-[#121700]' : 'bg-white/10 text-white'">
            <span v-if="p.hecho" class="material-symbols-outlined text-[18px]">check</span>
            <span v-else>{{ i + 1 }}</span>
          </span>
          <span class="flex-1 min-w-0">
            <span class="flex flex-wrap items-center gap-2">
              <span class="text-sm font-black" :class="p.hecho ? 'text-white/70 line-through decoration-white/30' : 'text-white'">{{ p.titulo }}</span>
              <span v-if="p.premio && !p.hecho" class="px-1.5 py-0.5 rounded-md bg-[#caf300]/15 text-[#caf300] text-[10px] font-black uppercase tracking-wider">{{ p.premio }}</span>
            </span>
            <span class="block text-xs text-white/70 leading-snug mt-0.5">{{ p.detalle }}</span>
            <span v-if="p.id === 'perfil' && !p.hecho && completitud !== null" class="mt-2 block h-1 rounded-full bg-white/10 overflow-hidden">
              <span class="block h-full bg-[#caf300] rounded-full" :style="{ width: `${Math.min(100, completitud)}%` }"></span>
            </span>
            <span v-if="!p.hecho && p.id !== siguientePaso?.id" class="block text-[11px] text-white/50 mt-1 flex items-center gap-1">
              <span class="material-symbols-outlined text-[13px]">lock</span>Después de «{{ siguientePaso?.titulo }}»
            </span>
          </span>
          <router-link v-if="!p.hecho && p.id === siguientePaso?.id" :to="p.ruta"
                       class="w-full sm:w-auto shrink-0 h-11 px-5 rounded-xl bg-[#caf300] text-[#121700] text-xs font-black uppercase tracking-wider flex items-center justify-center gap-1.5">
            {{ p.accion }}<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </router-link>
        </li>
      </ol>
      <p class="text-xs text-white/70">¿Dudas en algún paso?
        <button type="button" @click="store.abrirAyuda()" class="font-bold text-[#a5eeff] hover:text-white">Pídenos ayuda</button>: te contesta una persona.</p>
    </section>

    <!-- ============ HOY ============ -->
    <section v-if="hasWeek" class="order-1 rounded-3xl border bg-[#0e1013]/85 backdrop-blur-xl shadow-2xl p-5 sm:p-7"
             :class="hoyHecho ? 'border-[#caf300]/50' : 'border-white/10'">
      <header class="flex items-center justify-between gap-3 mb-4">
        <p class="text-[11px] font-black uppercase tracking-widest" :class="hoyHecho ? 'text-[#caf300]' : 'text-white/60'">
          {{ sesionesDeHoy.length ? (hoyHecho ? 'Hoy · hecho' : 'Hoy') : 'Hoy descansas' }}
        </p>
        <router-link :to="`/admin/plan?dia=${hoyISO}`" class="text-[11px] font-black text-[#a5eeff] uppercase tracking-wider hover:text-white">
          Ver en el plan
        </router-link>
      </header>

      <div v-if="sesionesDeHoy.length" class="space-y-4">
        <article v-for="s in sesionesDeHoy" :key="clave(s)" class="flex gap-4">
          <!-- El icono del deporte, o el ✓ si su reloj ya la tiene. -->
          <span class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center shrink-0"
                :class="estadoDe(s) && MARCAS[estadoDe(s).estado] && estadoDe(s).estado !== 'sin_sincronizar' ? MARCAS[estadoDe(s).estado].clase : 'bg-white/5 border border-white/10'">
            <span class="material-symbols-outlined text-[30px] sm:text-[34px]"
                  :style="estadoDe(s) && ['hecha','sin_intensidad'].includes(estadoDe(s).estado) ? {} : { color: COLOR_DEL_DEPORTE[deporteVisible(s)] }">
              {{ estadoDe(s) && ['hecha','sin_intensidad'].includes(estadoDe(s).estado) ? 'check' : (ICONO_DEL_DEPORTE[deporteVisible(s)] || 'exercise') }}
            </span>
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-black uppercase tracking-wider" :style="{ color: COLOR_DEL_DEPORTE[deporteVisible(s)] }">
              {{ etiquetaDeLaSesion(s) }}<template v-if="s.total_duration_minutes"> · {{ Math.round(s.total_duration_minutes) }} min</template>
            </p>
            <h3 class="text-xl sm:text-2xl font-black text-white leading-tight">{{ tituloDe(s) }}</h3>
            <p v-if="estadoDe(s) && textoDeLaSesion(estadoDe(s), s.total_duration_minutes)" class="text-[13px] mt-1"
               :class="['hecha','sin_intensidad'].includes(estadoDe(s).estado) ? 'text-[#caf300]' : 'text-white/60'">
              {{ textoDeLaSesion(estadoDe(s), s.total_duration_minutes) }}
            </p>
            <!-- Lo esencial para salir de casa: los primeros pasos. El resto, en el plan. -->
            <ul v-if="!(estadoDe(s) && ['hecha','sin_intensidad'].includes(estadoDe(s).estado)) && pasosQueDicenAlgo(s).length"
                class="mt-3 space-y-1.5 text-[13px] text-white/85">
              <li v-for="(paso, i) in pasosQueDicenAlgo(s).slice(0, 3)" :key="i" class="flex items-start gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-white/40 mt-1.5 shrink-0"></span>
                <span>{{ paso.description }}</span>
              </li>
              <li v-if="(s.steps || []).length > 3" class="pl-3.5">
                <router-link :to="`/admin/plan?dia=${hoyISO}`" class="text-xs font-bold text-[#a5eeff] hover:text-white">Ver los {{ s.steps.length }} pasos ›</router-link>
              </li>
            </ul>
            <p v-else-if="!(estadoDe(s) && ['hecha','sin_intensidad'].includes(estadoDe(s).estado)) && s.purpose" class="mt-2 text-[13px] text-white/70 leading-relaxed">{{ s.purpose }}</p>
          </div>
        </article>
      </div>
      <div v-else class="flex items-center gap-4">
        <span class="w-14 h-14 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
          <span class="material-symbols-outlined text-[30px] text-white/60">bedtime</span>
        </span>
        <p class="text-sm text-white/75 leading-relaxed">
          Recuperar también es entrenar.<template v-if="proxima"> Lo siguiente: <b class="text-white">{{ tituloDe(proxima) }}</b>, {{ cuandoEs(proxima.date) }}.</template>
        </p>
      </div>

      <div v-if="avisoEnHoy" role="status"
           class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-white/15 bg-white/[0.04] px-3 py-2 text-[13px] text-white/80">
        <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-white/60">sync</span>¿Ya la hiciste? Sincroniza tu reloj y pulsa «Leer mi reloj» para marcarla.</span>
        <button type="button" :disabled="store.estadoSesionesCargando" @click="store.cargarEstadoSesiones(true)"
                class="min-h-[40px] px-3 rounded-lg font-bold text-[#121700] bg-[#caf300] disabled:opacity-50">{{ store.estadoSesionesCargando ? 'Leyendo…' : 'Leer mi reloj' }}</button>
      </div>

      <p v-if="sesionesDeHoy.length && proxima" class="mt-5 pt-4 border-t border-white/10 text-[13px] text-white/70 flex items-center gap-2">
        <span class="material-symbols-outlined text-[18px]" :style="{ color: COLOR_DEL_DEPORTE[deporteVisible(proxima)] }">{{ ICONO_DEL_DEPORTE[deporteVisible(proxima)] || 'exercise' }}</span>
        <span>Después: <b class="text-white">{{ tituloDe(proxima) }}</b>, {{ cuandoEs(proxima.date) }}</span>
      </p>
    </section>

    <!-- ============ ESTA SEMANA: lo hecho, día a día, con su ✓ ============ -->
    <section v-if="hasWeek && semanaActual" class="order-2 rounded-3xl border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl shadow-2xl p-5 sm:p-6 space-y-4">
      <header class="flex items-end justify-between gap-3">
        <div>
          <p class="text-[11px] font-black text-white/60 uppercase tracking-widest">Esta semana</p>
          <p class="text-lg font-black text-white mt-0.5">
            <span class="text-[#caf300] tabular-nums">{{ progresoSemana.hechas }}</span> de {{ progresoSemana.total }} sesiones hechas
          </p>
        </div>
        <router-link to="/admin/plan" class="text-[11px] font-black text-[#a5eeff] uppercase tracking-wider hover:text-white shrink-0">Ver plan</router-link>
      </header>
      <div class="h-2 rounded-full bg-white/10 overflow-hidden" role="progressbar" :aria-valuenow="progresoSemana.hechas" :aria-valuemax="progresoSemana.total">
        <div class="h-full bg-[#caf300] rounded-full transition-all duration-700" :style="{ width: `${progresoSemana.pct}%` }"></div>
      </div>
      <div class="grid grid-cols-7 gap-1 sm:gap-2">
        <router-link v-for="c in diasDeLaSemana" :key="c.fecha" :to="`/admin/plan?dia=${c.fecha}`"
                     class="relative rounded-xl border p-1.5 sm:p-2 min-h-[76px] flex flex-col items-center gap-1 transition-colors"
                     :class="[c.fecha === hoyISO ? 'border-[#caf300] bg-[#caf300]/10' : 'border-white/10 bg-white/[0.04] hover:border-white/30', c.fecha < hoyISO ? 'opacity-80' : '']"
                     :aria-label="c.etiqueta" :aria-current="c.fecha === hoyISO ? 'date' : undefined">
          <span class="text-[11px] font-black uppercase text-white/70">{{ c.inicial }}</span>
          <span class="font-mono-data text-sm font-black" :class="c.fecha === hoyISO ? 'text-[#caf300]' : 'text-white'">{{ c.dia }}</span>
          <span v-if="c.marca" class="w-6 h-6 rounded-full flex items-center justify-center" :class="MARCAS[c.marca].clase">
            <span class="material-symbols-outlined text-[16px] font-black">{{ MARCAS[c.marca].icono }}</span>
          </span>
          <span v-else class="material-symbols-outlined text-[20px]" :style="{ color: c.color }">{{ c.icono }}</span>
        </router-link>
      </div>
      <EstadoDelReloj :estado="store.estadoSesiones" :cargando="store.estadoSesionesCargando" :sin-aviso="avisoEnHoy"
                      @actualizar="store.cargarEstadoSesiones(true)" />
      <!-- La racha vive de esta barra: aquí y no en una tarjeta aparte (revisión de UX, 2026-10-21). -->
      <router-link v-if="racha" to="/admin/analytics" class="flex items-center justify-between gap-3 pt-3 border-t border-white/10 text-sm text-white/80 hover:text-white">
        <span class="flex items-center gap-2">
          <span class="material-symbols-outlined text-[20px] text-[#caf300]">local_fire_department</span>
          <template v-if="racha.actual">Racha: <b class="text-white">{{ racha.actual }} {{ racha.actual === 1 ? 'semana cumplida' : 'semanas cumplidas' }}</b></template>
          <template v-else>Cumple esta semana y empieza tu racha</template>
        </span>
        <span class="text-xs text-white/60 shrink-0">Nivel {{ racha.nivel }} · {{ medallasConseguidas }} {{ medallasConseguidas === 1 ? 'medalla' : 'medallas' }} ›</span>
      </router-link>
    </section>

    <!-- ============ Tu progreso (racha, última semana, VO2max) ============ -->
    <section v-if="semana || forma" class="order-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
      <article v-if="semana" class="rounded-3xl p-5 border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl">
        <p class="text-[11px] font-black text-white/60 uppercase tracking-widest">Tu última semana</p>
        <div class="flex items-end gap-2 mt-1">
          <span class="text-4xl font-black text-white tabular-nums">{{ num(semana.hours) }} h</span>
          <span v-if="semana.prescribed_hours" class="text-xs font-bold text-white/60 pb-1.5">de {{ num(semana.prescribed_hours) }} h previstas</span>
        </div>
        <div v-if="kmPorDeporte.length" class="flex flex-wrap gap-1.5 mt-3">
          <span v-for="[deporte, km] in kmPorDeporte" :key="deporte" class="px-2.5 py-1 glass-pill rounded-lg text-[11px] font-bold text-white/75">
            {{ deporte }} <span class="font-mono-data text-white">{{ num(km) }} km</span>
          </span>
        </div>
        <p class="text-xs text-white/70 mt-3">Del {{ fechaCorta(semana.from) }} al {{ fechaCorta(semana.to) }}</p>
      </article>
      <article v-if="forma" class="rounded-3xl p-5 border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl">
        <!-- «VO₂max» en mayúsculas salía «VO:MAX» (la letra de los rótulos no tiene el ₂). -->
        <p class="text-[13px] font-bold text-white/80">Tu forma aeróbica <span class="text-white/60">(VO2máx)</span></p>
        <div class="flex items-end gap-2 mt-1">
          <span class="text-4xl font-black text-white tabular-nums">{{ forma.actual }}</span>
          <span class="text-xs font-black pb-1.5" :class="forma.delta >= 0 ? 'text-[#caf300]' : 'text-[#ffb4ab]'">
            {{ forma.delta >= 0 ? '+' : '' }}{{ forma.delta }} desde {{ forma.desde }}
          </span>
        </div>
        <svg viewBox="0 0 300 60" class="w-full h-14 mt-3" preserveAspectRatio="none" aria-hidden="true">
          <polyline :points="forma.linea" fill="none" stroke="#caf300" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
        </svg>
        <p class="text-xs text-white/70">De {{ forma.desde }} a hoy · más alto es mejor</p>
      </article>
    </section>

    <!-- Vista técnica: sólo el administrador (ACWR y VFC). -->
    <div v-if="store.isAdmin && (acwr || recuperacion)" class="order-7 flex items-center gap-2">
      <button type="button" @click="alternarAvanzadas"
              class="flex items-center gap-1.5 px-3.5 py-2 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider transition-colors"
              :class="avanzadasVisibles ? 'text-[#caf300]' : 'text-[#8f9378] hover:text-white'" :aria-expanded="String(avanzadasVisibles)">
        <span class="material-symbols-outlined text-[16px]">science</span>
        <span>Vista técnica</span>
        <span class="material-symbols-outlined text-[16px]">{{ avanzadasVisibles ? 'expand_less' : 'expand_more' }}</span>
      </button>
      <span v-if="!avanzadasVisibles" class="text-[11px] text-[#8f9378]">ACWR y VFC (sólo administrador)</span>
    </div>
    <section v-if="store.isAdmin && avanzadasVisibles && (acwr || recuperacion)" class="order-7 grid grid-cols-1 md:grid-cols-2 gap-5">
      <article v-if="acwr" class="glass-card rounded-3xl p-5 sm:p-6 border border-white/15 shadow-2xl">
        <p class="text-[11px] font-black text-[#8f9378] uppercase tracking-widest">Carga aguda:crónica</p>
        <div class="flex items-end gap-2 mt-1">
          <span class="font-mono-data text-4xl font-black" :class="acwr.color">{{ acwr.valor }}</span>
          <span class="text-xs font-bold pb-1.5" :class="acwr.color">{{ acwr.etiqueta }}</span>
        </div>
        <div class="relative h-3 mt-4 rounded-full bg-white/10 overflow-hidden">
          <div class="absolute inset-y-0 bg-[#caf300]/25" :style="{ left: acwr.bandaIzq, width: acwr.bandaAncho }"></div>
          <div class="absolute inset-y-0 w-1 rounded-full" :class="acwr.fondo" :style="{ left: acwr.marca }"></div>
        </div>
        <div class="flex justify-between text-[10px] font-mono-data text-[#8f9378] mt-1.5">
          <span>0</span><span>zona habitual 0.8–1.3</span><span>2.0</span>
        </div>
      </article>
      <article v-if="recuperacion" class="glass-card rounded-3xl p-5 sm:p-6 border border-white/15 shadow-2xl">
        <p class="text-[11px] font-black text-[#8f9378] uppercase tracking-widest">Variabilidad cardiaca</p>
        <div class="flex items-end gap-2 mt-1">
          <span class="font-mono-data text-4xl font-black text-white">{{ recuperacion.actual }}</span>
          <span class="text-xs font-bold text-[#8f9378] pb-1.5">ms</span>
        </div>
        <svg viewBox="0 0 300 60" class="w-full h-14 mt-3" preserveAspectRatio="none">
          <rect v-if="recuperacion.banda" x="0" :y="recuperacion.banda.y" width="300" :height="recuperacion.banda.alto" fill="rgba(202,243,0,0.10)" />
          <polyline :points="recuperacion.linea" fill="none" stroke="#a5eeff" stroke-width="2" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
        </svg>
        <p class="text-[10px] font-mono-data text-[#8f9378]">{{ recuperacion.banda ? 'La franja es TU rango normal' : 'Últimas noches' }}</p>
      </article>
    </section>

    <!-- ============ La carrera y el macrociclo ============ -->
    <section class="contents">
      <article class="rounded-3xl p-5 sm:p-6 border flex flex-col bg-[#0e1013]/85 backdrop-blur-xl"
               :class="carreraCerca ? 'order-3 border-[#ffd166]/50' : 'order-5 border-white/10'">
        <header class="flex justify-between items-start gap-3">
          <div class="min-w-0">
            <p class="text-[11px] font-black text-white/60 uppercase tracking-widest">
              {{ carreraElegida === 0 ? 'Tu próxima carrera' : 'Carrera agendada' }}
              <span v-if="nextRace?.priority" class="ml-1 px-1.5 py-0.5 rounded text-[9px]"
                    :class="nextRace.priority === 'A' ? 'bg-[#caf300] text-[#121700]' : 'bg-white/10 text-white/80'">{{ nextRace.priority }}</span>
            </p>
            <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight line-clamp-2 break-words">{{ nextRace ? nextRace.name : 'Sin carrera todavía' }}</h3>
            <p v-if="nextRace && raceSubtitle(nextRace)" class="text-sm font-bold text-[#caf300]">{{ raceSubtitle(nextRace) }}</p>
            <p v-if="nextRace" class="text-xs text-white/60 mt-0.5">{{ fechaDeCarrera(nextRace.date) }}</p>
          </div>
          <span class="material-symbols-outlined text-[26px] text-[#ffd166] shrink-0">emoji_events</span>
        </header>
        <div v-if="nextRace" class="mt-4 flex items-end gap-3">
          <span class="font-mono-data text-5xl font-black leading-none tabular-nums" :class="carreraCerca ? 'text-[#ffd166]' : 'text-white'">{{ raceDays }}</span>
          <span class="text-[11px] font-black text-white/60 pb-1 uppercase tracking-widest">{{ raceDays === 1 ? 'día' : 'días' }}</span>
          <span v-if="nextRace.target_time" class="ml-auto text-xs text-white/60 pb-1">Objetivo <b class="text-white font-mono-data">{{ nextRace.target_time }}</b></span>
        </div>
        <p v-else class="mt-3 text-sm text-white/70 leading-relaxed">Con una carrera en el calendario tu coach sabe hacia dónde construir.</p>
        <div v-if="carreras.length > 1" class="mt-4 pt-3 border-t border-white/10">
          <div class="flex gap-2 overflow-x-auto snap-x snap-mandatory pb-1 -mx-1 px-1 hide-scrollbar">
            <button v-for="(c, i) in carreras" :key="c.id || `${c.date}-${c.name}`" type="button" @click="carreraElegida = i"
                    :aria-pressed="carreraElegida === i" :title="c.name"
                    class="snap-start shrink-0 w-36 sm:w-auto sm:max-w-[16rem] text-left rounded-xl p-2.5 border transition-colors"
                    :class="carreraElegida === i ? 'border-[#caf300]/60 bg-[#caf300]/10' : 'border-white/10 bg-white/5 hover:border-white/25'">
              <span class="block text-[11px] font-bold text-white/65">{{ fechaCorta(c.date) }} · {{ diasHasta(c.date) === 0 ? 'hoy' : `en ${diasHasta(c.date)} ${diasHasta(c.date) === 1 ? 'día' : 'días'}` }}</span>
              <span class="block mt-0.5 text-sm font-bold text-white truncate sm:whitespace-normal">{{ c.name }}</span>
            </button>
          </div>
        </div>
        <div class="mt-auto pt-4 flex flex-wrap items-center gap-2">
          <router-link to="/admin/perfil?tab=objetivos"
                       class="h-9 px-3 rounded-xl glass-pill text-[11px] font-black uppercase tracking-wider text-white/80 hover:text-[#caf300] flex items-center gap-1">
            <span class="material-symbols-outlined text-[16px]">{{ nextRace ? 'edit_calendar' : 'add' }}</span>{{ nextRace ? 'Mis carreras' : 'Añadir carrera' }}
          </router-link>
          <PuertaFalsa v-if="nextRace" puerta="plan_carrera" etiqueta="Estrategia para el día de carrera" icono="flag"
            titulo="Todavía no existe"
            texto="Estamos pensando en darte, para cada competición, <b>a qué ritmo ir en cada tramo y qué comer y beber durante la carrera</b>, calculado con tus datos. ¿Lo usarías?"
            :opciones="[
              { valor: 'todas', etiqueta: 'Sí, en todas mis carreras' },
              { valor: 'importantes', etiqueta: 'Sólo en las importantes' },
              { valor: 'no', etiqueta: 'No lo necesito' },
            ]"
            clase="h-9 flex items-center gap-1.5 px-3 glass-pill rounded-xl text-[11px] font-black uppercase tracking-wider text-white/60 hover:text-[#caf300] transition-colors" />
        </div>
      </article>

      <!-- El macrociclo: en qué fase va y cuánto queda. Antes, «Semanas / Sesiones / Volumen» sumaban todas
           las semanas guardadas (hasta 12) y no decían nada de esta. -->
      <article v-if="posicion" class="order-6 rounded-3xl p-5 sm:p-6 border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl flex flex-col">
        <p class="text-[11px] font-black text-white/60 uppercase tracking-widest">Tu temporada</p>
        <h3 class="text-xl sm:text-2xl font-black text-white tracking-tight mt-0.5">{{ posicion.faseNombre || 'Tu macrociclo' }}</h3>
        <p class="text-sm text-white/80 mt-0.5">Semana <b class="text-white tabular-nums">{{ posicion.semana }}</b> de {{ posicion.ultimaSemana }}<template v-if="posicion.esDescarga"> · semana de descarga</template></p>
        <div class="mt-4 h-2 rounded-full bg-white/10 overflow-hidden">
          <div class="h-full bg-[#a5eeff] rounded-full" :style="{ width: `${Math.min(100, (posicion.semana / posicion.ultimaSemana) * 100)}%` }"></div>
        </div>
        <router-link to="/admin/plan" class="mt-auto pt-4 text-[11px] font-black text-[#a5eeff] uppercase tracking-wider hover:text-white self-start">Ver el macrociclo</router-link>
      </article>
      <article v-else-if="planListo && !hasSeason && hasWeek" class="order-6 rounded-3xl p-5 sm:p-6 border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl">
        <p class="text-[11px] font-black text-white/60 uppercase tracking-widest">Tu temporada</p>
        <p class="mt-2 text-sm text-white/70 leading-relaxed">Todavía no tienes macrociclo: es el mapa de fases hasta tu carrera. Se crea en Plan.</p>
      </article>
    </section>
  </div>
</template>

<script setup>
import { COLOR_DEL_DEPORTE, ICONO_DEL_DEPORTE, deporteVisible, etiquetaDeLaSesion } from '../../utils/deportes';
import { num } from '../../utils/garmin';
import { MARCAS, marcaDelDia, textoDeLaSesion } from '../../utils/hecho';
import EstadoDelReloj from '../../components/plan/EstadoDelReloj.vue';
import { MARGEN_HORAS } from '../../utils/sincronizacion';
import { posicionEnLaTemporada } from '../../utils/macrociclo';
import { MINIMO_PERFIL } from '../../utils/profile';
import { computed, onMounted, ref, watch } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { getCharts } from '../../services/plan';
import PuertaFalsa from '../../components/layout/PuertaFalsa.vue';

const store = useAppStore();

// El nombre que el ATLETA escribió en su perfil manda sobre el de la cuenta.
//
// Estaba al revés y se veían los dos nombres a la vez en la misma pantalla: el
// saludo decía "¡Hola, Atleta!" (de `users.name`, que en local es "Atleta 2" y
// en producción es lo que devuelva Google) mientras la barra lateral, dos
// centímetros a la izquierda, decía "Jose Ines Castillo" (de
// `athlete_profiles.full_name`). Ganaba la cuenta porque iba primera en la
// cadena y nunca está vacía.
const firstName = computed(
  () => (store.athlete.name || store.authUser?.name || 'Atleta').trim().split(' ')[0] || 'Atleta',
);

const weeklyPlan = computed(() => store.plan?.weekly_plan || {});
const hasSeason = computed(() => !!store.plan?.has_season);
const hasWeek = computed(() => !!store.plan?.has_week);
const planListo = computed(() => !!store.planLoaded);
const allDays = computed(() => (weeklyPlan.value.weeks || []).flatMap((w) => w.days || []));

const todayISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

/* ---------------------------------------------------------------------------
   Inicio = HOY (rediseño del 2026-10-21). Lo que el atleta viene a ver: qué toca hoy y si ya lo hizo
   (✓ de su Garmin, sin IA: utils/hecho.js), cómo va la semana, su carrera y su temporada. Sin semana,
   los pasos para llegar a la primera.
--------------------------------------------------------------------------- */
const hoyISO = todayISO();
// Sólo la primera letra en mayúscula: con `capitalize` salía «21 De Octubre».
const mayuscula = (t) => t.charAt(0).toUpperCase() + t.slice(1);
const hoyLargo = mayuscula(new Date().toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' }));
const FRANJAS = { am: 0, unica: 1, pm: 2, s3: 3, s4: 4 };
const orden = (a, b) => (a.date + (FRANJAS[a.session_slot || 'unica'] ?? 9)).localeCompare(b.date + (FRANJAS[b.session_slot || 'unica'] ?? 9));
const clave = (d) => `${d.date}|${d.session_slot || 'unica'}`;
const estadoDe = (d) => store.estadoSesiones?.sesiones?.[clave(d)] || null;
const hecha = (d) => ['hecha', 'sin_intensidad'].includes(estadoDe(d)?.estado);
// En fuerza, SU sesión («Upper A»), como en Plan.
const tituloDe = (d) => d?.strength_block?.session_name || d?.focus || etiquetaDeLaSesion(d);

const sesionesDeHoy = computed(() => allDays.value.filter((d) => d.date === hoyISO && !d.is_rest).sort(orden));
const hoyHecho = computed(() => sesionesDeHoy.value.length > 0 && sesionesDeHoy.value.every(hecha));
const proxima = computed(() => allDays.value.filter((d) => !d.is_rest && d.date > hoyISO).sort(orden)[0] || null);
const cuandoEs = (fecha) => {
  const dias = Math.round((new Date(`${fecha}T12:00:00`) - new Date(`${hoyISO}T12:00:00`)) / 86400000);
  if (dias === 1) return 'mañana';
  const d = new Date(`${fecha}T12:00:00`);
  return `el ${d.toLocaleDateString('es', { weekday: 'long' })} ${d.getDate()}`;
};
// Los pasos que no repiten el título (una fuerza trae un único paso que se llama como la sesión).
const pasosQueDicenAlgo = (s) => (s.steps || []).filter((p) => !String(p.description || '').startsWith(tituloDe(s)));

const resumenDeHoy = computed(() => {
  if (!planListo.value) return 'Cargando tu plan…';
  if (!hasWeek.value) return 'Vamos a preparar tu primera semana.';
  const hoy = sesionesDeHoy.value;
  if (!hoy.length) return 'Hoy descansas.';
  if (hoyHecho.value) return '¡Hoy ya entrenaste! Buen trabajo.';
  const s = hoy[0];
  const min = s.total_duration_minutes ? `, ${Math.round(s.total_duration_minutes)} min` : '';
  return `Hoy toca ${etiquetaDeLaSesion(s).toLowerCase()}${min}${hoy.length > 1 ? ` y ${hoy.length - 1} sesión más` : ''}.`;
});

// La semana en curso (o, si no hay, la que viene), día a día con su ✓.
const semanaActual = computed(() => {
  const semanas = weeklyPlan.value.weeks || [];
  const fechas = (w) => (w.days || []).map((d) => d.date).filter(Boolean).sort();
  return semanas.find((w) => { const f = fechas(w); return f.length && f[0] <= hoyISO && hoyISO <= f[f.length - 1]; })
    || semanas.filter((w) => (fechas(w)[0] || '') > hoyISO).sort((a, b) => fechas(a)[0].localeCompare(fechas(b)[0]))[0]
    || null;
});
const INICIALES = ['D', 'L', 'M', 'X', 'J', 'V', 'S'];
const diasDeLaSemana = computed(() => {
  const dias = semanaActual.value?.days || [];
  return [...new Set(dias.map((d) => d.date).filter(Boolean))].sort().map((fecha) => {
    const sesiones = dias.filter((d) => d.date === fecha && !d.is_rest).sort(orden);
    const f = new Date(`${fecha}T12:00:00`);
    const marca = marcaDelDia(store.estadoSesiones?.sesiones, sesiones.map(clave));
    return {
      fecha, dia: f.getDate(), inicial: INICIALES[f.getDay()], marca,
      icono: sesiones.length ? (ICONO_DEL_DEPORTE[deporteVisible(sesiones[0])] || 'exercise') : 'bedtime',
      color: sesiones.length ? (COLOR_DEL_DEPORTE[deporteVisible(sesiones[0])] || '#c5c9ac') : '#8f9378',
      etiqueta: `${f.toLocaleDateString('es', { weekday: 'long', day: 'numeric' })}: `
        + `${sesiones.length ? sesiones.map(tituloDe).join(' y ') : 'descanso'}${marca ? `, ${MARCAS[marca].texto}` : ''}`,
    };
  });
});
// Su reloj lleva 3 h o más sin subir nada (el mismo margen que el aviso del check-in).
const relojViejo = computed(() => {
  const iso = store.estadoSesiones?.ultima_sincronizacion;
  return Boolean(iso) && (Date.now() - new Date(iso).getTime()) / 3600000 >= MARGEN_HORAS;
});
// El aviso del reloj va en «Hoy» cuando hay una sesión de hoy en duda; en «Esta semana» queda sólo la hora.
const avisoEnHoy = computed(() => sesionesDeHoy.value.length > 0 && !hoyHecho.value && relojViejo.value);
const progresoSemana = computed(() => {
  const sesiones = (semanaActual.value?.days || []).filter((d) => !d.is_rest);
  const hechas = sesiones.filter(hecha).length;
  return { total: sesiones.length, hechas, pct: sesiones.length ? Math.round((hechas / sesiones.length) * 100) : 0 };
});

const posicion = computed(() => (hasSeason.value
  ? posicionEnLaTemporada(store.plan?.season_phases, undefined, store.profile?.week_start_day) : null));

// Sin semana: los pasos para llegar a la primera, con el que toca resaltado (la primera experiencia).
const completitud = computed(() => store.athlete.completionRate ?? store.profile?.completionRate ?? null);
const pasos = computed(() => [
  { id: 'perfil', hecho: completitud.value !== null && completitud.value >= MINIMO_PERFIL, ruta: '/admin/perfil',
    titulo: 'Completa tu perfil', accion: 'Completar',
    detalle: completitud.value !== null ? `Vas al ${completitud.value} %. Tu coach necesita al menos el ${MINIMO_PERFIL} %.` : 'Tus deportes, tus días y tu objetivo.' },
  { id: 'garmin', hecho: Boolean(store.athlete.garminConnected), ruta: '/admin/perfil?tab=identidad',
    titulo: 'Conecta tu Garmin', accion: 'Conectar', premio: '+2 semanas gratis',
    detalle: 'Para leer lo que entrenas, enviar los entrenos a tu reloj y empezar tus semanas gratis.' },
  { id: 'temporada', hecho: hasSeason.value, ruta: '/admin/plan', titulo: 'Crea tu temporada', accion: 'Crear',
    detalle: 'El mapa de fases hasta tu carrera. Tarda unos minutos.' },
  { id: 'semana', hecho: hasWeek.value, ruta: '/admin/plan', titulo: 'Genera tu primera semana', accion: 'Generar',
    detalle: 'Tus próximos días de entreno, que se envían a tu reloj.' },
]);
const siguientePaso = computed(() => pasos.value.find((x) => !x.hecho) || null);

// Los ✓ de su Garmin, en cuanto hay una semana (no bloquea: tarda unos segundos la primera vez).
watch(hasWeek, (si) => { if (si && !store.estadoSesiones) store.cargarEstadoSesiones(); }, { immediate: true });

// Las competiciones que quedan, de la mas cercana a la mas lejana.
//
// Antes se enseñaba la primera de prioridad A aunque hubiera otra antes: el
// atleta 2 veia su 10K a 221 dias mientras tenia un 5K dentro de 9. La que
// importa esta semana es la mas cercana, y las demas se ven en la tira.
const carreras = computed(() => {
  const t = todayISO();
  return (store.profile?.competitions || [])
    .filter((c) => c.date && c.date >= t)
    .sort((a, b) => a.date.localeCompare(b.date));
});
const carreraElegida = ref(0);
watch(carreras, (lista) => {
  if (carreraElegida.value >= lista.length) carreraElegida.value = 0;
});
const nextRace = computed(() => carreras.value[carreraElegida.value] || null);
const diasHasta = (fecha) => Math.max(
  0,
  Math.round((new Date(`${fecha}T00:00:00`) - new Date(`${todayISO()}T00:00:00`)) / 86400000),
);
const raceDays = computed(() => (nextRace.value ? diasHasta(nextRace.value.date) : null));
// A 14 días o menos, la carrera sube a después de «Esta semana» y se pinta en amarillo.
const carreraCerca = computed(() => carreraElegida.value === 0 && raceDays.value !== null && raceDays.value <= 14);
const fechaDeCarrera = (fecha) => mayuscula(new Date(`${fecha}T00:00:00`)
  .toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }));
const fechaCorta = (fecha) => {
  const d = new Date(`${fecha}T00:00:00`);
  const mismoAno = d.getFullYear() === new Date().getFullYear();
  return d.toLocaleDateString('es', { day: 'numeric', month: 'short', ...(mismoAno ? {} : { year: '2-digit' }) });
};

// Las etiquetas del catalogo de distancias (el mismo /carreras.json que usa el
// perfil). DISTANCE_LABELS queda para las carreras guardadas con la clave vieja.
const etiquetasDeDistancia = ref({});
fetch(`${import.meta.env.BASE_URL}carreras.json`)
  .then((r) => (r.ok ? r.json() : null))
  .then((d) => {
    const mapa = {};
    for (const g of (d && d.grupos) || []) for (const o of g.options || []) mapa[o.value] = o.label;
    etiquetasDeDistancia.value = mapa;
  })
  .catch(() => {});
const DISTANCE_LABELS = {
  '5K': '5K', '10K': '10K', half_marathon: 'Media Maratón', marathon: 'Maratón',
  Sprint: 'Triatlón Sprint', Olympic: 'Triatlón Olímpico', half_ironman: 'Ironman 70.3',
  ironman: 'Ironman', gran_fondo: 'Gran Fondo',
};
// Sin repetir el nombre: una carrera llamada "10K" salia como "10K / 10K".
const raceSubtitle = (r) => {
  // Sin caer a `race_type`: es la etiqueta de un desplegable retirado y el
  // alta viejo la rellenaba con "Olympic" por defecto (ver race_distances.py).
  const s = etiquetasDeDistancia.value[r.distance] || DISTANCE_LABELS[r.distance] || '';
  return s && s.trim().toLowerCase() !== String(r.name || '').trim().toLowerCase() ? s : '';
};

/* ---------------------------------------------------------------------------
   Tu estado HOY

   Los tres numeros que contestan "¿puedo apretar hoy?". Salen de
   `/plan/charts`, la misma tuberia que Analytics -- no se recalcula nada aqui,
   sólo se presenta más corto: en Analytics se va a estudiar la tendencia, y en
   Inicio se mira de paso.

   Fallar aqui no puede costar el panel: si las gráficas no cargan, la sección
   simplemente no aparece y el resto del tablero sigue en pie.
--------------------------------------------------------------------------- */
const graficas = ref({});
onMounted(async () => {
  try { graficas.value = (await getCharts())?.charts || {}; }
  catch (e) { console.warn('No se pudieron cargar las gráficas del inicio:', e?.message || e); }
});

const semana = computed(() => graficas.value?.last_week || null);
const racha = computed(() => graficas.value?.progreso?.racha || null);
const medallasConseguidas = computed(() => (graficas.value?.progreso?.medallas || []).filter((m) => m.conseguida).length);
// De más a menos km: el orden de las claves no sobrevive al viaje por PHP.
const kmPorDeporte = computed(() => Object.entries(semana.value?.km_by_sport || {}).sort((x, y) => y[1] - x[1]));

/**
 * Si las metricas avanzadas se ven. Cerradas por defecto, y la eleccion se
 * recuerda en ESTE navegador.
 *
 * `localStorage` y no el perfil a proposito: es una preferencia de vista, no un
 * dato del atleta, y no merece un viaje al servidor ni una columna. Puede venir
 * vacio o lanzar (ventana privada, datos borrados), asi que se lee y se escribe
 * entre `try`.
 */
const CLAVE_AVANZADAS = 'pc.dashboard.avanzadas';
const avanzadasVisibles = ref(false);
try {
  avanzadasVisibles.value = localStorage.getItem(CLAVE_AVANZADAS) === '1';
} catch (e) { /* sin almacenamiento: se queda cerrado, que es el defecto */ }

const alternarAvanzadas = () => {
  avanzadasVisibles.value = !avanzadasVisibles.value;
  try {
    localStorage.setItem(CLAVE_AVANZADAS, avanzadasVisibles.value ? '1' : '0');
  } catch (e) { /* no poder recordarlo no puede impedir verlo */ }
};

/** Una polilínea de 300x60 a partir de una serie, con margen para el trazo. */
const polilinea = (puntos, clave = 'value') => {
  const vals = (puntos || []).map((p) => Number(p[clave])).filter(Number.isFinite);
  if (vals.length < 2) return null;
  let min = Math.min(...vals);
  let max = Math.max(...vals);
  if (max - min < 1e-9) { const d = Math.abs(max) * 0.1 || 0.5; min -= d; max += d; }
  const y = (v) => 4 + 52 * (1 - (v - min) / (max - min));
  return {
    linea: vals.map((v, i) => `${((i / (vals.length - 1)) * 300).toFixed(1)},${y(v).toFixed(1)}`).join(' '),
    y, min, max,
  };
};

const acwr = computed(() => {
  const c = graficas.value?.load;
  const v = Number(c?.current);
  if (!c || !Number.isFinite(v)) return null;
  // El eje va de 0 a 2: por debajo de 0.8 se esta desentrenando y por encima
  // de 1.5 esta la zona que la literatura asocia a mas riesgo. Fuera de ese
  // rango el numero deja de ser interpretable, asi que se recorta la MARCA
  // pero nunca el valor que se lee.
  const pct = (x) => `${Math.min(100, Math.max(0, (x / 2) * 100)).toFixed(1)}%`;
  const [bajo, alto] = c.sweet_spot || [0.8, 1.3];
  const dentro = v >= bajo && v <= alto;
  const arriba = v > (c.high ?? 1.5);
  return {
    valor: Math.round(v * 100) / 100,
    etiqueta: arriba ? 'carga alta' : dentro ? 'en tu zona habitual' : v < bajo ? 'por debajo' : 'por encima',
    color: arriba ? 'text-[#ffb4ab]' : dentro ? 'text-[#caf300]' : 'text-[#a5eeff]',
    fondo: arriba ? 'bg-[#ffb4ab]' : dentro ? 'bg-[#caf300]' : 'bg-[#a5eeff]',
    marca: pct(v),
    bandaIzq: pct(bajo),
    bandaAncho: `${((alto - bajo) / 2) * 100}%`,
  };
});

const forma = computed(() => {
  const c = graficas.value?.fitness;
  const linea = polilinea(c?.points);
  if (!c || !linea) return null;
  return {
    actual: Math.round(Number(c.current)),
    delta: Math.round(Number(c.change) || 0),
    linea: linea.linea,
    desde: c.points?.[0]?.label || '',
  };
});

const recuperacion = computed(() => {
  const c = graficas.value?.recovery?.hrv;
  const linea = polilinea(c?.points);
  if (!c || !linea) return null;
  // La banda es el rango base PROPIO del atleta, el que Garmin calcula contra
  // sus ultimas semanas. Nunca un rango general: fuera de el no significa nada.
  let banda = null;
  const b = (c.band || []).map(Number).filter(Number.isFinite);
  if (b.length === 2) {
    const arriba = linea.y(Math.max(...b));
    const abajo = linea.y(Math.min(...b));
    banda = { y: arriba.toFixed(1), alto: Math.max(1, abajo - arriba).toFixed(1) };
  }
  return { actual: Math.round(Number(c.current)), linea: linea.linea, banda };
});
</script>
