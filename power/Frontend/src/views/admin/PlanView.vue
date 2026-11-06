<template>
  <div id="plan-view-container" class="flex flex-col gap-7 pb-20 select-none animate-fade-in-up">
    <!-- Header -->
    <header class="flex flex-col xl:flex-row justify-between items-start xl:items-center gap-5 pb-5 border-b border-white/10">
      <div>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Tu Plan de Entrenamiento</h2>
        <p class="text-sm text-[#c5c9ac] mt-1 font-medium">
          Tu semana, escrita con tus datos de Garmin y lo que le cuentas a tu coach.
        </p>
      </div>
      <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
        <button
          v-if="hasWeek"
          @click="handleGarminUpload"
          :disabled="uploading"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-black text-[#121700] bg-[#a5eeff] hover:bg-[#8fe6ff] rounded-xl uppercase tracking-wider transition-all shadow-[0_0_15px_rgba(165,238,255,0.3)] disabled:opacity-50"
        >
          <span class="material-symbols-outlined text-[16px]">{{ uploading ? 'sync' : 'watch' }}</span>
          <span>{{ uploading ? 'Subiendo...' : 'Subir a Garmin' }}</span>
        </button>
      </div>
    </header>

    <!-- El macrociclo se acaba, o ya se acabó.
         Antes no se avisaba de ninguna de las dos cosas: el plan de temporada
         llegaba hasta la semana N y en la N+1 el planificador seguía
         escribiendo semanas sin fase ni objetivo de horas, en silencio. -->
    <section
      v-if="avisoMacro && !generating"
      class="glass-card rounded-3xl p-5 sm:p-6 shadow-2xl flex flex-col sm:flex-row sm:items-center gap-4"
      :class="avisoMacro.tono === 'vencido' ? 'border border-[#ffb4ab]/40' : 'border border-[#caf300]/35'"
    >
      <span
        class="material-symbols-outlined text-3xl shrink-0"
        :class="avisoMacro.tono === 'vencido' ? 'text-[#ffb4ab]' : 'text-[#caf300]'"
      >{{ avisoMacro.tono === 'vencido' ? 'event_busy' : 'hourglass_bottom' }}</span>
      <div class="flex-1 min-w-0">
        <h3 class="text-base font-black text-white">{{ avisoMacro.titulo }}</h3>
        <p class="text-sm text-[#c5c9ac] mt-1 leading-relaxed">{{ avisoMacro.texto }}</p>
      </div>
      <button
        @click="promptAndGenerate('season')"
        :disabled="generating"
        class="shrink-0 flex items-center gap-2 px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_15px_rgba(202,243,0,0.3)] disabled:opacity-50 transition-all"
      >
        <span class="material-symbols-outlined text-[18px]">timeline</span>
        <span>{{ avisoMacro.accion }}</span>
      </button>
    </section>

    <!-- La SEMANA se acaba. Va encima del progreso y debajo del aviso del
         macrociclo: los dos avisan de lo mismo a escalas distintas, y el de la
         semana es el que pasa cada siete dias. -->
    <section
      v-if="avisoSemana && !generating && perfilSuficiente && !(acceso && acceso.puede_generar === false)"
      class="glass-card rounded-3xl p-5 sm:p-6 shadow-2xl flex flex-col sm:flex-row sm:items-center gap-4"
      :class="avisoSemana.tono === 'vencido' ? 'border border-[#ffb4ab]/40' : 'border border-[#a5eeff]/35'"
    >
      <span
        class="material-symbols-outlined text-3xl shrink-0"
        :class="avisoSemana.tono === 'vencido' ? 'text-[#ffb4ab]' : 'text-[#a5eeff]'"
      >{{ avisoSemana.tono === 'vencido' ? 'event_busy' : 'event_upcoming' }}</span>
      <div class="flex-1 min-w-0">
        <h3 class="text-base font-black text-white">{{ avisoSemana.titulo }}</h3>
        <p class="text-sm text-[#c5c9ac] mt-1 leading-relaxed">{{ avisoSemana.texto }}</p>
      </div>
      <!-- Sin boton cuando todavia no se puede generar (a dos dias del final):
           un boton que lleva a "todavia no toca" es peor que no tenerlo. -->
      <button
        v-if="avisoSemana.accion"
        @click="promptAndGenerate('week')"
        :disabled="generating || !hasSeason"
        class="shrink-0 flex items-center gap-2 px-5 py-2.5 bg-[#a5eeff] hover:bg-[#8fe6ff] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_15px_rgba(165,238,255,0.3)] disabled:opacity-50 transition-all"
      >
        <span class="material-symbols-outlined text-[18px]">auto_fix_high</span>
        <span>{{ avisoSemana.accion }}</span>
      </button>
    </section>

    <!-- Generation progress -->
    <section v-if="generating" class="glass-card rounded-3xl p-6 sm:p-7 border border-[#caf300]/30 shadow-2xl space-y-3">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-[#caf300] animate-pulse text-2xl">neurology</span>
        <h3 class="text-base font-black text-white">{{ statusText || 'Generando...' }}</h3>
      </div>
      <div class="w-full h-2.5 bg-white/10 rounded-full overflow-hidden p-0.5">
        <div class="h-full bg-[#caf300] rounded-full shadow-[0_0_12px_#caf300] transition-all duration-700" :style="{ width: `${progress}%` }"></div>
      </div>
      <!-- CUANDO SE PUEDE CERRAR. "Puedes cerrar la pestaña" era verdad antes
           de que el coach preguntara a mitad de generacion: quien cerraba no
           veia las preguntas, y la generacion esperaba diez minutos y seguia
           sin sus respuestas. Hasta que el worker dice `questions_done`, se
           pide quedarse. -->
      <p v-if="!preguntasCoach" class="text-[11px] font-mono-data text-[#8f9378]">
        <template v-if="preguntasResueltas">
          Esto tarda varios minutos. Ya puedes cerrar la pestaña; el plan se guarda al terminar. ({{ progress }}%)
        </template>
        <template v-else>
          Esto tarda varios minutos. Quédate en esta pantalla hasta que tu coach decida si tiene algo que
          preguntarte (suele ser en los primeros minutos); después podrás cerrarla. ({{ progress }}%)
        </template>
      </p>

      <!-- EL COACH PREGUNTA. Ha mirado tus datos resumidos y hay algo que solo
           tu le puedes decir. La generacion esta en pausa esperando esto; si no
           contestas en unos minutos, sigue con tus datos. Ver
           PaginaLocal/services/ai/preguntas_del_coach.py. -->
      <div v-if="preguntasCoach" class="pt-3 space-y-4 border-t border-white/10">
        <div>
          <p class="text-sm font-black text-white">Antes de planificar, tu coach quiere saber:</p>
          <p class="text-[11px] text-[#8f9378] mt-1">
            Contesta lo que quieras; lo que dejes en blanco lo decide con tus datos. Si no contestas en unos
            minutos, sigue sin esperar.
          </p>
        </div>
        <div v-for="(q, i) in preguntasCoach" :key="i" class="space-y-1.5">
          <label :for="`pregunta-coach-${i}`" class="block text-sm text-white font-bold leading-snug">{{ q.pregunta }}</label>
          <p v-if="q.por_que" class="text-[11px] text-[#8f9378] leading-relaxed">{{ q.por_que }}</p>
          <textarea
            :id="`pregunta-coach-${i}`"
            v-model="respuestasCoach[i]"
            rows="2"
            class="w-full glass-input rounded-xl px-3.5 py-2.5 text-white text-sm focus:outline-none resize-none select-text"
            placeholder="Tu respuesta"
          ></textarea>
        </div>
        <p v-if="errorRespuestas" class="text-[11px] font-bold text-[#ffb4ab]">{{ errorRespuestas }}</p>
        <div class="flex flex-wrap items-center justify-end gap-2">
          <button type="button" @click="enviarRespuestas(true)" :disabled="enviandoRespuestas"
                  class="px-4 py-2.5 glass-pill text-[#8f9378] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all disabled:opacity-40">
            Prefiero no contestar
          </button>
          <button type="button" @click="enviarRespuestas(false)" :disabled="enviandoRespuestas"
                  class="flex items-center gap-2 px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-95 disabled:opacity-40">
            <span class="material-symbols-outlined text-[18px]">{{ enviandoRespuestas ? 'sync' : 'send' }}</span>
            <span>{{ enviandoRespuestas ? 'Enviando...' : 'Enviar y seguir' }}</span>
          </button>
        </div>
      </div>
    </section>

    <p v-if="genError" class="glass-card rounded-2xl p-4 border border-[#ffb4ab]/40 text-sm text-[#ffb4ab] font-bold">
      {{ genError }}
    </p>

    <!-- Las semanas gratis y el precio (decisión del responsable, 2026-10-20: «que seamos claros que el
         precio del producto es Q60 (8 USD) al mes»). Terminadas, sólo se cierra generar: el plan, el
         reloj y el chat siguen. Ver Backend AccessService. -->
    <section v-if="acceso && acceso.estado === 'vencido'" class="glass-card rounded-2xl p-5 border border-[#ffd166]/45 space-y-2">
      <h3 class="text-base font-black text-white flex items-center gap-2">
        <span class="material-symbols-outlined text-[#ffd166]">lock_clock</span>
        {{ acceso.pagado_hasta ? 'Tu mes pagado terminó' : 'Tus semanas gratis terminaron' }} el {{ diaLargo(acceso.acceso_hasta) }}
      </h3>
      <p class="text-sm text-[#c5c9ac] leading-relaxed">
        Power Coach AI cuesta <b class="text-white">{{ acceso.precio.texto }}</b><template v-if="acceso.precio.anual">
        o {{ acceso.precio.anual.texto }}</template>. Tu plan, tu reloj, tu progreso y el chat
        siguen; para generar semanas nuevas hace falta la suscripción.
      </p>
      <button type="button" @click="quieroSuscribirme"
              class="px-4 py-2 rounded-xl bg-[#caf300] text-[#121700] text-xs font-black uppercase tracking-wider">
        Quiero suscribirme
      </button>
    </section>
    <!-- Garmin conectado y sin semanas gratis: pedirle «conecta tu Garmin» lo dejaba dando vueltas (revisión del
         2026-10-22). Son dos casos que la base no distingue (auditoría del 2026-10-23): lo conectó antes de que
         hubiera semanas gratis -- todos los de la beta, y volver a leerlo se las da -- o esa cuenta ya las usó. -->
    <section v-else-if="acceso && acceso.estado === 'garmin_usado'" class="glass-card rounded-2xl p-5 border border-[#ffd166]/45 space-y-2">
      <h3 class="text-base font-black text-white flex items-center gap-2">
        <span class="material-symbols-outlined text-[#ffd166]">watch</span>
        No tienes semanas gratis activas
      </h3>
      <p class="text-sm text-[#c5c9ac] leading-relaxed">
        Si conectaste tu Garmin antes de que hubiera semanas gratis, pulsa «Volver a leer mi Garmin» en tu perfil y
        se activan. Si esa cuenta de Garmin ya las usó, no trae más: van una vez por cuenta. Power Coach AI cuesta
        <b class="text-white">{{ acceso.precio.texto }}</b><template v-if="acceso.precio.anual">
        o {{ acceso.precio.anual.texto }}</template>. Tu plan, tu reloj, tu progreso y el chat siguen.
      </p>
      <div class="flex flex-wrap gap-2">
        <router-link to="/admin/perfil" class="px-4 py-2 rounded-xl border border-[#a5eeff]/50 text-[#a5eeff] text-xs font-black uppercase tracking-wider">
          Ir a mi perfil
        </router-link>
        <button type="button" @click="quieroSuscribirme"
                class="px-4 py-2 rounded-xl bg-[#caf300] text-[#121700] text-xs font-black uppercase tracking-wider">
          Quiero suscribirme
        </button>
      </div>
    </section>
    <section v-else-if="acceso && acceso.estado === 'sin_empezar'" class="glass-card rounded-2xl p-4 border border-[#a5eeff]/35 flex flex-wrap items-center gap-3">
      <span class="material-symbols-outlined text-[#a5eeff]">watch</span>
      <p class="flex-1 min-w-[200px] text-sm text-[#f1f0ee]">
        Conecta tu Garmin en tu perfil para empezar tus semanas gratis. Después, {{ acceso.precio.texto }}.
        <button type="button" @click="store.invitarAbierto = true" class="block mt-1 text-[11px] font-bold text-[#a5eeff] hover:underline">
          ¿Te invitó alguien? Escribe su código antes de conectarlo
        </button>
      </p>
      <router-link to="/admin/perfil" class="px-4 py-2 rounded-xl bg-[#a5eeff] text-[#121700] text-xs font-black uppercase tracking-wider">
        Ir a mi perfil
      </router-link>
    </section>
    <p v-else-if="acceso && acceso.estado === 'prueba'" class="text-[11px] text-[#8f9378]">
      Semanas gratis: te {{ acceso.dias_restantes === 1 ? 'queda 1 día' : `quedan ${acceso.dias_restantes} días` }}
      (hasta el {{ diaLargo(acceso.acceso_hasta) }}). Después, Power Coach AI cuesta {{ acceso.precio.texto }}.
      <button type="button" @click="quieroSuscribirme" class="font-bold text-[#caf300] hover:underline">Ver precios</button>
    </p>
    <p v-else-if="acceso && acceso.estado === 'pagado' && acceso.dias_restantes <= 7" class="text-[11px] text-[#8f9378]">
      {{ acceso.cortesia ? 'Tu acceso sin costo termina' : 'Tu mes pagado termina' }} el {{ diaLargo(acceso.acceso_hasta) }}
      (después, {{ acceso.precio.texto }}).
    </p>

    <!-- Cambios que el reloj todavía no tiene (Beta 3.0, F5-F6): una sesión movida o regenerada. -->
    <section v-if="sinEnviar && !uploading && !hasWeek" class="glass-card rounded-2xl p-4 border border-[#ffd166]/40 flex flex-wrap items-center gap-3">
      <span class="material-symbols-outlined text-[#ffd166]">sync_problem</span>
      <p class="flex-1 min-w-[200px] text-sm text-[#f1f0ee]">
        {{ sinEnviar === 1 ? 'Una sesión de tu plan no está igual en tu reloj.' : `${sinEnviar} sesiones de tu plan no están igual en tu reloj.` }}
      </p>
      <button type="button" @click="handleGarminUpload(fechasSinEnviar)"
              class="px-4 py-2 rounded-xl bg-[#ffd166] text-[#121700] text-xs font-black uppercase tracking-wider">
        Enviar al reloj
      </button>
    </section>

    <p v-if="uploadMsg" class="glass-card rounded-2xl p-4 border text-sm font-bold"
       :class="uploadError ? 'border-[#ffb4ab]/40 text-[#ffb4ab]' : 'border-[#a5eeff]/40 text-[#a5eeff]'">
      {{ uploadMsg }}
    </p>

    <!-- El perfil no da todavia. Se dice ANTES de que pulse, no despues:
         hasta la v1.1 no habia ninguna comprobacion en ninguna ruta y se podia
         generar un plan con el perfil al 8% -- salia un plan verosimil
         construido sobre valores por defecto, y ni el atleta ni nosotros
         sabiamos cuanto de el era suyo. El backend lo comprueba igual
         (PlanService::perfilSuficiente); esto es para que se entienda. -->
    <section
      v-if="!generating && !perfilSuficiente"
      class="glass-card rounded-3xl p-5 sm:p-6 border border-[#ffb4ab]/40 shadow-2xl flex flex-col sm:flex-row sm:items-center gap-4"
    >
      <span class="material-symbols-outlined text-3xl shrink-0 text-[#ffb4ab]">person_alert</span>
      <div class="flex-1 min-w-0">
        <h3 class="text-base font-black text-white">Termina tu perfil primero</h3>
        <p class="text-sm text-[#c5c9ac] mt-1 leading-relaxed">
          Va por el <span class="font-black text-white font-mono-data">{{ completion }}%</span>.
          Tu entrenador necesita al menos el {{ MINIMO_PERFIL }}% para escribirte un plan que sea tuyo
          y no una plantilla.
        </p>
      </div>
      <router-link
        to="/admin/perfil"
        class="shrink-0 flex items-center gap-2 px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all"
      >
        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        <span>Ir a mi perfil</span>
      </router-link>
    </section>

    <!-- Generate actions -->
    <!-- Sólo con el plan cargado: mientras carga decía «Primero genera el plan de temporada» a quien ya lo tiene. -->
    <section v-if="!generating && perfilSuficiente && loaded" class="grid grid-cols-1 md:grid-cols-2 gap-6" :class="hasWeek ? 'order-2' : ''">
      <button
        @click="promptAndGenerate('season')"
        class="glass-card rounded-3xl p-6 sm:p-7 flex items-center justify-between text-left cursor-pointer hover:border-[#caf300] hover:scale-[1.01] transition-all group shadow-2xl border border-white/10"
      >
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/50 flex items-center justify-center text-[#caf300] group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-[30px]">calendar_month</span>
          </div>
          <div>
            <h3 class="text-lg font-black text-white group-hover:text-[#caf300] transition-colors">
              {{ hasSeason ? 'Rehacer tu plan de temporada' : 'Generar tu plan de temporada' }}
            </h3>
            <p class="text-xs text-[#c5c9ac] mt-1 font-medium">
              {{ macroDisponibleDesde
                ? `Ya generaste uno esta semana: podrás volver a hacerlo desde el ${fechaDelCupo(macroDisponibleDesde)}.`
                : (hasSeason ? 'Sólo si cambió tu carrera o tu situación: escribe tu temporada otra vez.' : 'Tus fases hasta tu carrera: el mapa que siguen tus semanas.') }}
            </p>
          </div>
        </div>
        <span class="material-symbols-outlined text-[#caf300] group-hover:translate-x-1 transition-transform text-2xl">arrow_forward</span>
      </button>

      <button
        @click="hasSeason && promptAndGenerate('week')"
        :disabled="!hasSeason"
        class="glass-card rounded-3xl p-6 sm:p-7 flex items-center justify-between text-left transition-all group shadow-2xl border border-white/10"
        :class="hasSeason ? 'cursor-pointer hover:border-[#a5eeff] hover:scale-[1.01]' : 'opacity-50 cursor-not-allowed'"
      >
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-[#a5eeff]/15 border border-[#a5eeff]/50 flex items-center justify-center text-[#a5eeff] group-hover:scale-110 transition-transform">
            <span class="material-symbols-outlined text-[30px]">auto_fix_high</span>
          </div>
          <div>
            <h3 class="text-lg font-black text-white group-hover:text-[#a5eeff] transition-colors">Generar Semana</h3>
            <p class="text-xs text-[#c5c9ac] mt-1 font-medium">
              {{ !hasSeason ? 'Primero genera el plan de temporada.'
                : (tope ? `Ya tienes esta semana y la siguiente; la próxima empieza el ${fechaCorta(tope.siguiente)}.`
                  : (proximaDisponible ? `La de ${fechaCorta(proximaDisponible.siguiente)} se puede generar desde el ${fechaCorta(proximaDisponible.desde)}.`
                    : 'Tu siguiente semana, con tu cansancio y tu recuperación de hoy.')) }}
            </p>
          </div>
        </div>
        <span class="material-symbols-outlined text-[#a5eeff] group-hover:translate-x-1 transition-transform text-2xl">arrow_forward</span>
      </button>
    </section>

    <!-- «Generar mi semana automáticamente» (Database/027). Discreto a propósito (el responsable,
         2026-09-25): una fila pequeña con su interruptor; lo que pasó la última vez, debajo y sólo si
         hay algo que decir. La lógica vive en Backend/src/Services/AutoWeekService.php. -->
    <section v-if="auto?.available && hasSeason" class="px-1 space-y-1" :class="hasWeek ? 'order-2' : ''">
      <div class="flex items-center justify-end gap-2.5">
        <span id="auto-semana-titulo" class="text-[13px] text-white/85"
              :title="`La víspera de empezar tu semana, a las ${auto.hour}:00, tu coach escribe la siguiente y la sube a tu reloj. Sin preguntas ni check-in.`">
          Generar mi semana automáticamente
        </span>
        <button type="button" role="switch" :aria-checked="auto.enabled ? 'true' : 'false'"
                aria-labelledby="auto-semana-titulo" :disabled="autoGuardando" @click="alternarAuto"
                class="flex items-center gap-2 min-h-[44px] px-1 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#caf300] rounded-xl">
          <span class="relative shrink-0 w-10 h-6 rounded-full" :class="auto.enabled ? 'bg-[#caf300]' : 'bg-white/25'">
            <span class="absolute top-1 left-1 w-4 h-4 rounded-full transition-transform"
                  :class="auto.enabled ? 'translate-x-4 bg-[#121700]' : 'bg-white'"></span>
          </span>
          <span class="text-[12px] font-bold w-[4.5rem] text-left" :class="auto.enabled ? 'text-[#caf300]' : 'text-white/70'">{{ auto.enabled ? 'Activada' : 'Apagada' }}</span>
        </button>
      </div>
      <p v-if="autoError" class="text-[11px] text-right font-bold text-[#ffb4ab]">{{ autoError }}</p>
      <p v-else-if="auto.enabled && !hasWeek" class="text-[12px] text-right text-white/70">
        Genera tu primera semana; desde la siguiente se generan solas.
      </p>
      <p v-else-if="autoCuando && !['generando', 'subiendo'].includes(auto.stage)" class="text-[12px] text-right text-white/70">
        Próxima: {{ autoCuando }}.
      </p>
      <!-- Lo que pasó la última vez, sólo con la automática encendida (o si falló): apagada, «se generó sola y está
           en tu reloj» de hace semanas contradecía el estado de hoy del reloj (revisión del 2026-10-22). -->
      <p v-if="auto.message && (auto.enabled || auto.stage === 'error')" class="text-[12px] text-right leading-relaxed"
         :class="auto.stage === 'error' ? 'text-[#ffb4ab]' : 'text-white/70'">{{ auto.message }}</p>
      <p v-if="!auto.enabled && !auto.garmin_password_saved" class="text-[12px] text-right text-white/70">
        Necesita tu contraseña de Garmin guardada en tu perfil.
      </p>
    </section>

    <!-- Loading -->
    <div v-if="!loaded" class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">
      Cargando plan...
    </div>

    <template v-else>

      <!-- Weekly plan -->
      <section v-if="hasWeek" class="space-y-8 order-1">
        <!-- La tabla de zonas y ritmos ya no va aquí (el responsable, 2026-09-25: en la pantalla no
             se usaba). Sigue en la hoja imprimible / PDF, que la escribe de sus propios datos. -->
        <!-- SOLO LA SEMANA VIGENTE. `weeks` acumula hasta 12 (MAX_WEEKS_KEPT en
             server.py), y pintarlas todas seguidas convertia el movil en un
             scroll de ochenta tarjetas donde la de hoy quedaba enterrada. Las
             anteriores siguen enteras en Historial. -->
        <!-- Por qué la primera semana es así (idea del responsable, 2026-10-20: «atletas avanzados
             podrían tener ansiedad al ver los entrenos iniciales, porque rompe su rutina, y creer que
             necesitan más intensidad»). Sólo con UNA semana generada y con su número real de Garmin:
             no se le promete nada ni se le dice que es suave; se le dan sus horas y qué hacer si
             le queda corta. Se cierra y no vuelve para esa semana. -->
        <div v-if="primeraSemana" class="glass-card rounded-2xl border border-[#a5eeff]/30 p-4 sm:p-5 space-y-2">
          <div class="flex items-start justify-between gap-3">
            <h3 class="text-sm font-black text-white flex items-center gap-2">
              <span class="material-symbols-outlined text-[#a5eeff] text-[18px]">info</span>
              Por qué tu primera semana es así
            </h3>
            <button type="button" @click="cerrarPrimeraSemana" aria-label="Cerrar"
                    class="text-[#8f9378] hover:text-white"><span class="material-symbols-outlined text-[18px]">close</span></button>
          </div>
          <p class="text-xs text-[#c5c9ac] leading-relaxed">
            Tu coach la escribió con lo que tu Garmin dice que entrenaste las últimas 4 semanas:
            <b class="text-white">unas {{ coma(primeraSemana.cronica) }} h por semana</b>. Esta semana tiene
            {{ coma(primeraSemana.estaSemana) }} h<template v-if="primeraSemana.calidad">, con
            {{ primeraSemana.calidad }} {{ primeraSemana.calidad === 1 ? 'sesión' : 'sesiones' }} de calidad</template>.
            La primera semana sirve para que el plan te conozca: lo que hagas y cómo respondas es lo que mueve
            las siguientes.
          </p>
          <p class="text-xs text-[#c5c9ac] leading-relaxed">
            ¿Se te queda corta? Díselo al coach o en el check-in de la próxima semana, o cambia una sesión con
            «Ajustar».
          </p>
          <button type="button" @click="store.isAiCoachOpen = true"
                  class="text-[11px] font-black uppercase tracking-wider text-[#a5eeff] hover:text-white">
            Hablar con el coach
          </button>
        </div>

        <!-- Las semanas como un calendario que se desliza a los lados (decisión del responsable,
             2026-10-21). Tocar un día lo abre debajo; «Mover» marca en el calendario a dónde puede ir. -->
        <div ref="calendarioRef" class="rounded-3xl border border-white/10 bg-[#0e1013]/85 backdrop-blur-xl shadow-2xl p-3 sm:p-5 scroll-mt-24">
          <div v-if="moviendo" class="mb-3 rounded-2xl border border-[#a5eeff]/50 bg-[#a5eeff]/10 px-3 sm:px-4 py-3 flex items-center gap-3" role="status">
            <span class="material-symbols-outlined text-[#a5eeff]">open_with</span>
            <p class="flex-1 min-w-0 text-sm text-white leading-snug">
              Toca el día nuevo para <b>«{{ moviendo.titulo }}»</b>
              <span class="block text-[11px] text-[#c5c9ac]">Los marcados en <span class="text-[#caf300] font-black">verde</span>{{ semanasActivas.length > 1 ? ', de esta semana o de la siguiente ›' : ', de esta semana' }}.</span>
            </p>
            <button type="button" @click="cancelarMover"
                    class="px-3 py-2 rounded-xl glass-pill text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white">Cancelar</button>
          </div>
          <CalendarioDeSemanas
            ref="calendarioCmp"
            :weeks="weeks"
            :hoy="hoyISO"
            :dia-elegido="diaElegido"
            :mover="moviendo"
            :destinos="destinosValidos"
            :pendientes="fechasSinEnviar"
            :estados="store.estadoSesiones?.sesiones || {}"
            :movible="movibleEl"
            :titulo="tituloDelDia"
            @elegir="elegirDia"
            @destino="alElegirDestino"
            @arrastrar="empezarAMoverClave"
            @semana="(w) => { semanaMostradaNum = w ? w.week_number : null; }"
          />
          <!-- De dónde salen los ✓/✗ (su Garmin, sin IA) y si se puede fiar de ellos ahora. -->
          <EstadoDelReloj v-if="!moviendo" class="mt-2 justify-center" :estado="store.estadoSesiones"
                          :cargando="store.estadoSesionesCargando" @actualizar="store.cargarEstadoSesiones(true)" />
          <!-- Lo movido o regenerado que el reloj todavía no tiene: el paso siguiente, aquí mismo
               (revisión de UX del 2026-10-21: «el atleta cree que ya está en su reloj»). -->
          <div v-if="sinEnviar && !uploading && !moviendo"
               class="mt-3 rounded-xl border border-[#a5eeff]/30 bg-[#a5eeff]/10 px-3 py-2 flex items-center gap-3">
            <span class="material-symbols-outlined text-[#a5eeff] text-[20px]">watch</span>
            <p class="flex-1 min-w-0 text-[13px] text-white leading-snug">
              {{ sinEnviar === 1 ? '1 cambio sin enviar a tu reloj' : `${sinEnviar} cambios sin enviar a tu reloj` }}
            </p>
            <button type="button" @click="handleGarminUpload(fechasSinEnviar)"
                    class="h-9 px-4 rounded-xl bg-[#caf300] text-[#121700] text-xs font-black uppercase tracking-wider active:scale-95">
              Enviar al reloj
            </button>
          </div>
        </div>
        <Teleport to="body">
          <!-- Contenedor que coloca y barra que anima: el `transform` de la animación pisaba el centrado. -->
          <div v-if="deshacer && !moviendo"
               class="fixed z-50 left-4 right-24 bottom-[calc(16px+env(safe-area-inset-bottom))] md:left-0 md:right-0 md:bottom-24 md:flex md:justify-center pointer-events-none">
            <div role="status"
                 class="pointer-events-auto md:max-w-md rounded-2xl border border-[#caf300]/40 bg-[#14171b] shadow-2xl px-4 py-3 flex items-center gap-3 animate-fade-in-up">
              <span class="material-symbols-outlined text-[#caf300]">check_circle</span>
              <p class="flex-1 min-w-0 text-sm text-white leading-snug">
                <span class="sm:hidden block whitespace-nowrap truncate">{{ deshacer.corto }}</span><span class="hidden sm:inline">{{ deshacer.texto }}</span>
              </p>
              <button type="button" @click="deshacerMovimiento"
                      class="h-11 sm:h-9 px-3 shrink-0 rounded-lg flex items-center gap-1 text-[12px] font-black uppercase tracking-wider text-[#caf300] hover:bg-[#caf300]/10">
                <span class="material-symbols-outlined text-[18px]">undo</span>Deshacer
              </button>
            </div>
          </div>
        </Teleport>

        <div v-for="week in (semanaMostrada ? [semanaMostrada] : [])" :key="week.week_number" class="space-y-4">
          <!-- El razonamiento va por markdown, como el macrociclo de abajo.
               Salia con `{{ }}` y el atleta leia los asteriscos crudos:
               "objetivo de **6.8 h** totales (≈**408 min**)". Y no es un
               adorno: el modelo pone en negrita exactamente los NUMEROS que
               justifican la semana -- su HRV, su ACWR, el volumen objetivo --
               asi que sin renderizar se pierde justo lo que hay que mirar, y
               ademas es el primer parrafo que el atleta ve de su semana.
               `renderMarkdown` escapa el HTML antes, asi que `v-html` es
               seguro. -->
          <!-- El día elegido en el calendario: a lo ancho si es una sesión, en dos columnas si son dos. -->
          <div ref="detalleRef" class="grid grid-cols-1 gap-5 scroll-mt-24" :class="sesionesEse(week, diaElegido) > 1 ? 'md:grid-cols-2' : ''">
            <template v-for="(day, di) in (week.days || [])" :key="di">
            <article
              v-if="day.date === diaElegido"
              class="glass-card rounded-3xl p-5 sm:p-6 border border-white/10 flex flex-col"
              :class="[day.is_rest ? 'opacity-70' : '', esHoy(day.date) ? 'border-[#caf300]/60 shadow-[0_0_25px_rgba(202,243,0,0.15)]' : '']"
            >
              <!-- La CABECERA siempre se ve; el detalle se despliega. En el
                   telefono eran siete tarjetas altas de una columna con pasos,
                   proposito y adaptacion, todo abierto: para ver el jueves
                   habia que pasar el lunes entero. -->
              <button
                type="button"
                @click="alternarDia(day.date, di)"
                class="text-left w-full"
                :aria-expanded="String(diaAbierto(day.date, di))"
              >
                <div class="flex flex-wrap justify-between items-start mb-2 gap-2">
                  <div class="min-w-0">
                    <p class="font-mono-data text-xs font-black text-white">
                      <span class="whitespace-nowrap">{{ day.day_name_es }} · {{ fechaCorta(day.date) }}</span>
                      <span v-if="esHoy(day.date)" class="ml-1.5 px-1.5 py-px rounded bg-[#caf300] text-[#121700] text-[10px] whitespace-nowrap">HOY</span>
                    </p>
                    <p class="text-[10px] font-black uppercase tracking-wider text-[#caf300] mt-1">
                      {{ etiquetaDeLaSesion(day) }}
                      <!-- Dos sesiones el mismo dia se leian como huecos. Ahora
                           cada una dice cual es y cuantas hay: es tambien la
                           cara visible de que ya no emitimos bricks. -->
                      <span v-if="sesionesEse(week, day.date) > 1" class="text-[#a5eeff]">
                        · sesión {{ indiceDeSesion(week, day, di) }} de {{ sesionesEse(week, day.date) }}
                      </span>
                    </p>
                  </div>
                  <div class="flex items-center gap-1.5 shrink-0">
                    <!-- Qué lleva el reloj de esta sesión (Beta 3.0, F5), sólo en las que vienen. -->
                    <span v-if="!day.is_rest && enElReloj[claveDeSesion(day)] && day.date >= hoyISO"
                          class="px-2 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider"
                          :class="RELOJ[enElReloj[claveDeSesion(day)]].clase"
                          :title="RELOJ[enElReloj[claveDeSesion(day)]].ayuda">
                      {{ RELOJ[enElReloj[claveDeSesion(day)]].texto }}
                    </span>
                    <span v-if="!day.is_rest && day.total_duration_minutes" class="px-2.5 py-1 glass-pill rounded-lg text-[10px] font-black text-[#caf300] font-mono-data">
                      {{ Math.round(day.total_duration_minutes) }} min
                    </span>
                    <span class="material-symbols-outlined text-[18px] text-[#8f9378] md:hidden">
                      {{ diaAbierto(day.date, di) ? 'expand_less' : 'expand_more' }}
                    </span>
                  </div>
                </div>

                <h4 class="text-base font-black text-white leading-snug mb-3">{{ tituloDelDia(day) }}</h4>
              </button>
              <!-- Lo que dice su reloj de esta sesión: ✓ hecha, ✗ no está (utils/hecho.js; 2026-10-21). -->
<!-- Lo que dice su reloj de esta sesión (2026-10-21): ✓ hecha con sus números, ✗ no está. -->
              <div v-if="!day.is_rest && estadoDe(day) && MARCAS[estadoDe(day).estado]"
                   class="mb-3 -mt-1 rounded-2xl border px-3 py-2.5"
                   :class="['hecha', 'sin_intensidad'].includes(estadoDe(day).estado) ? 'border-[#caf300]/30 bg-[#caf300]/10' : 'border-white/10 bg-white/5'">
                <p class="flex items-center gap-2 text-sm font-bold text-white">
                  <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0" :class="MARCAS[estadoDe(day).estado].clase">
                    <span class="material-symbols-outlined text-[16px] font-black">{{ MARCAS[estadoDe(day).estado].icono }}</span>
                  </span>
                  {{ ['hecha', 'sin_intensidad'].includes(estadoDe(day).estado) ? (estadoDe(day).estado === 'hecha' ? 'Hecha' : 'Hecha, sin llegar a la intensidad') : MARCAS[estadoDe(day).estado].texto }}
                </p>
                <div v-if="['hecha', 'sin_intensidad'].includes(estadoDe(day).estado)" class="mt-2 grid grid-cols-3 gap-2 text-center">
                  <span class="rounded-xl bg-black/20 py-1.5"><span class="block font-mono-data text-sm font-black text-white">{{ estadoDe(day).minutos ?? '—' }}<span class="text-xs text-white/60">{{ estadoDe(day).minutos ? ` de ${Math.round(day.total_duration_minutes)}'` : '' }}</span></span><span class="block text-[11px] text-white/60">minutos</span></span>
                  <span class="rounded-xl bg-black/20 py-1.5"><span class="block font-mono-data text-sm font-black text-white">{{ estadoDe(day).km ? String(estadoDe(day).km).replace('.', ',') : '—' }}</span><span class="block text-[11px] text-white/60">km</span></span>
                  <span class="rounded-xl bg-black/20 py-1.5"><span class="block font-mono-data text-sm font-black text-white">{{ estadoDe(day).fc_media ?? '—' }}</span><span class="block text-[11px] text-white/60">FC media</span></span>
                </div>
                <p v-if="estadoDe(day).otro_dia || estadoDe(day).estado !== 'hecha'" class="mt-1.5 text-xs text-white/70 leading-snug">{{ textoDeLaSesion(estadoDe(day), day.total_duration_minutes) }}</p>
              </div>

              <!-- Fuerza: lo que cambia esta semana, en etiquetas. El porqué, a un toque.
                   Hasta el 2026-09-23 era un párrafo de ~20 líneas bajo los ejercicios. -->
              <div v-if="day.strength_block && (day.strength_block.dose || day.strength_block.placement_note)" class="mb-3 space-y-1.5">
                <button
                  v-if="day.strength_block.dose"
                  type="button"
                  @click="alternarDetalle(claveDeSesion(day) + '|dosis')"
                  class="flex items-center gap-1 px-2 py-0.5 rounded-md bg-[#ffd166]/10 border border-[#ffd166]/30 text-[10px] font-black uppercase tracking-wider text-[#ffd166]"
                  :aria-expanded="String(!!detalleAbierto[claveDeSesion(day) + '|dosis'])"
                >
                  {{ day.strength_block.dose === 'minima' ? 'Dosis mínima' : 'Dosis reducida' }}
                  <span v-if="day.strength_block.dose_reason" class="material-symbols-outlined text-[13px]">info</span>
                </button>
                <p v-if="detalleAbierto[claveDeSesion(day) + '|dosis'] && day.strength_block.dose_reason" class="text-[11px] text-[#c5c9ac] leading-snug">{{ day.strength_block.dose_reason }}</p>
                <p v-if="day.strength_block.placement_note" class="flex items-start gap-1 text-[11px] text-[#ffd166] leading-snug">
                  <span class="material-symbols-outlined text-[14px] shrink-0">warning</span>{{ day.strength_block.placement_note }}
                </p>
              </div>

              <!-- En pantalla ancha no hay nada que plegar: cabe. -->
              <div :class="diaAbierto(day.date, di) ? 'contents' : 'hidden md:contents'">

              <div v-if="!day.is_rest && day.garmin_exportable === false && (day.manual_instructions || day.purpose)" class="mb-3 glass-card rounded-xl p-3 border border-[#a5eeff]/30">
                <span class="text-[9px] font-black uppercase tracking-wider text-[#a5eeff]">Fuera del reloj</span>
                <p class="text-xs text-[#e5e2e1] leading-relaxed mt-1">{{ day.manual_instructions || day.purpose }}</p>
              </div>

              <!-- Un día de fuerza con sus ejercicios no necesita su único paso ("Fuerza — 49 min"):
                   los minutos ya van arriba. -->
              <PlanSteps v-if="!day.is_rest && day.steps && day.steps.length && !tieneEjercicios(day)" :steps="day.steps" />

              <div v-if="day.strength_block && (day.strength_block.exercises || []).length" class="mt-3 space-y-2">
                <p v-if="day.strength_block.warmup" class="text-[12px] text-white/75"><span class="font-bold text-white">Calentamiento:</span> {{ day.strength_block.warmup }}</p>
                <p v-if="usaRirORpe(day.strength_block)" class="text-[12px] text-white/70 leading-snug">
                  <b class="text-white">RIR 2</b>: acaba la serie sabiendo que podrías hacer 2 repeticiones más.
                  <b class="text-white">RPE 7</b>: esfuerzo 7 sobre 10.
                </p>
                <!-- Beta 2.1: la sesion por GRUPOS. Una superserie (A1, A2, A3) va
                     junta, con sus rondas y el descanso entre rondas; un plan sin
                     grupos se lee como un grupo por ejercicio y se ve como antes. -->
                <div class="space-y-2">
                  <div
                    v-for="grupo in gruposDeFuerza(day.strength_block)"
                    :key="grupo.clave"
                    :class="grupo.encabezado ? 'rounded-xl border border-white/10 bg-white/[0.02] p-2.5 space-y-2' : ''"
                  >
                    <p v-if="grupo.encabezado" class="text-[12px] font-bold text-[#caf300]">{{ grupo.encabezado }}</p>
                    <div v-for="item in grupo.items" :key="item.ei" class="text-xs text-[#e5e2e1]">
                      <div class="flex items-start gap-2">
                        <span class="shrink-0 min-w-[1.5rem] font-mono-data text-[11px] font-black text-[#8f9378]">{{ item.etiqueta }}</span>
                        <div class="min-w-0 flex-1">
                          <span class="font-bold text-white">{{ item.ex.name }}</span>
                          <span class="text-[#8f9378]"> · {{ item.ex.sets }}×{{ item.ex.reps }}<template v-if="item.ex.load"> · {{ item.ex.load }}</template><template v-if="item.ex.rest_seconds && !grupo.encabezado"> · descanso {{ duracionCorta(item.ex.rest_seconds) }}</template></span>
                          <button
                            v-if="item.ex.change"
                            type="button"
                            @click="alternarDetalle(claveDeSesion(day) + '|' + item.ei)"
                            class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-px rounded bg-[#a5eeff]/10 border border-[#a5eeff]/30 text-[10px] font-bold text-[#a5eeff] align-middle"
                            :aria-expanded="String(!!detalleAbierto[claveDeSesion(day) + '|' + item.ei])"
                          >{{ item.ex.change }}<span v-if="item.ex.change_reason" class="material-symbols-outlined text-[12px]">info</span></button>
                          <span v-if="detalleAbierto[claveDeSesion(day) + '|' + item.ei] && item.ex.change_reason" class="block text-[11px] text-[#c5c9ac] mt-0.5">{{ item.ex.change_reason }}</span>
                          <span v-if="item.ex.cues" class="block text-[#8f9378]">{{ item.ex.cues }}</span>
                          <!-- Lo que levantó la última vez (Database/026). Sin esto el
                               atleta tiene que acordarse de memoria de sus kilos. -->
                          <span v-if="comoLoHizo(ultimaDe(ultimaVez, item.ex))" class="block text-[#a5eeff]">
                            La última vez: {{ comoLoHizo(ultimaDe(ultimaVez, item.ex)) }}
                          </span>
                        </div>
                        <!-- Solo aparece si el ejercicio esta en el catalogo. Sin
                             imagen no hay boton: un boton que no ensena nada es
                             peor que ninguno. -->
                        <button
                          v-if="imagenDeEjercicio(item.ex)"
                          type="button"
                          @click="alternarImagen(day.date, item.ei)"
                          class="shrink-0 flex items-center gap-1 px-2 py-1 glass-pill rounded-lg text-[10px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#caf300] transition-colors"
                          :aria-expanded="String(!!imagenesAbiertas[day.date + '-' + item.ei])"
                        >
                          <span class="material-symbols-outlined text-[14px]">
                            {{ imagenesAbiertas[day.date + '-' + item.ei] ? 'expand_less' : 'image' }}
                          </span>
                          <span>{{ imagenesAbiertas[day.date + '-' + item.ei] ? 'Ocultar' : 'Ver' }}</span>
                        </button>
                      </div>
                      <img
                        v-if="imagenesAbiertas[day.date + '-' + item.ei] && imagenDeEjercicio(item.ex)"
                        :src="imagenDeEjercicio(item.ex)"
                        :alt="'Como se hace: ' + item.ex.name"
                        loading="lazy"
                        class="mt-2 w-full rounded-xl border border-white/10 bg-black/20"
                      />
                    </div>
                  </div>
                </div>
                <div v-if="(day.strength_block.skipped || []).length" class="text-[11px] text-[#8f9378]">
                  <button type="button" @click="alternarDetalle(claveDeSesion(day) + '|sin')" class="text-left"
                          :aria-expanded="String(!!detalleAbierto[claveDeSesion(day) + '|sin'])">
                    Esta semana sin: <span class="text-[#c5c9ac]">{{ day.strength_block.skipped.map((q) => q.name).join(', ') }}</span>
                    <span class="material-symbols-outlined text-[12px] align-middle">info</span>
                  </button>
                  <ul v-if="detalleAbierto[claveDeSesion(day) + '|sin']" class="mt-1 space-y-0.5 text-[#c5c9ac]">
                    <li v-for="q in day.strength_block.skipped" :key="q.name">{{ q.name }}: {{ q.reason }}</li>
                  </ul>
                </div>
                <!-- Planes guardados antes del 2026-09-23: sus notas eran un párrafo largo. Plegadas. -->
                <details v-if="day.strength_block.notes" class="text-[11px] text-[#8f9378]">
                  <summary class="cursor-pointer font-black uppercase tracking-wider text-[10px]">Notas del coach</summary>
                  <p class="mt-1 leading-relaxed">{{ day.strength_block.notes }}</p>
                </details>

                <!-- REGISTRAR lo que hizo. Es la unica parte de la app donde los kilos
                     son un dato y no una suposicion: los escribe el. De aqui sale la
                     progresion de carga (Database/026). -->
                <div class="pt-1">
                  <button
                    type="button"
                    @click="alternarRegistro(day)"
                    class="flex items-center gap-1.5 px-3 py-1.5 glass-pill rounded-lg text-[10px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#caf300] transition-colors"
                    :aria-expanded="String(!!registroAbierto[claveDeSesion(day)])"
                  >
                    <span class="material-symbols-outlined text-[14px]">edit_note</span>
                    {{ registroAbierto[claveDeSesion(day)] ? 'Cerrar' : (hayRegistro(day) ? 'Editar lo que hiciste' : 'Registrar lo que hiciste') }}
                  </button>
                  <!-- Con el panel ya cerrado, la confirmación tiene que verse fuera de él. -->
                  <span v-if="!registroAbierto[claveDeSesion(day)] && hechoDelRegistro[claveDeSesion(day)]"
                        class="ml-2 text-[11px] text-[#caf300]">{{ hechoDelRegistro[claveDeSesion(day)] }}</span>

                  <div v-if="registroAbierto[claveDeSesion(day)] && borradorDelRegistro[claveDeSesion(day)]" class="mt-2 rounded-xl border border-white/10 bg-white/[0.02] p-3 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                      <span class="text-[10px] font-black uppercase tracking-wider text-[#8f9378]">Series · reps · kg</span>
                      <div class="flex flex-wrap justify-end gap-1.5">
                        <button type="button" @click="hiceTalCual(day)"
                                class="px-2.5 py-1 glass-pill rounded-lg text-[10px] font-black uppercase tracking-wider text-[#caf300]">
                          Lo hice tal cual
                        </button>
                      </div>
                    </div>
                    <div
                      v-for="ex in (day.strength_block.exercises || [])"
                      :key="'reg-' + claveDeSesion(day) + '-' + (ex.id || ex.name)"
                      class="grid grid-cols-[1fr_3.5rem_4rem_4rem] gap-2 items-center"
                    >
                      <span class="text-[12px] text-[#e5e2e1] truncate" :title="ex.name">{{ ex.name }}</span>
                      <!-- Lo prescrito y los kilos de la última vez, como PISTA: sólo cuenta lo que escribe. -->
                      <input type="number" min="0" max="30" inputmode="numeric" :placeholder="String(ex.sets ?? 'ser')"
                             :aria-label="'Series hechas de ' + ex.name"
                             v-model.number="borradorDelRegistro[claveDeSesion(day)][ex.id || ex.name].sets"
                             class="w-full glass-input rounded-lg px-2 py-1.5 text-white text-[12px] font-mono-data placeholder:text-white/25" />
                      <input type="text" :placeholder="ex.reps || 'reps'"
                             :aria-label="'Repeticiones de ' + ex.name"
                             v-model="borradorDelRegistro[claveDeSesion(day)][ex.id || ex.name].reps"
                             class="w-full glass-input rounded-lg px-2 py-1.5 text-white text-[12px] font-mono-data placeholder:text-white/25" />
                      <input type="number" min="0" max="500" step="0.5" inputmode="decimal"
                             :placeholder="ultimaDe(ultimaVez, ex)?.weight_kg ? String(ultimaDe(ultimaVez, ex).weight_kg) : 'kg'"
                             :aria-label="'Kilos de ' + ex.name"
                             v-model.number="borradorDelRegistro[claveDeSesion(day)][ex.id || ex.name].weight_kg"
                             class="w-full glass-input rounded-lg px-2 py-1.5 text-white text-[12px] font-mono-data placeholder:text-white/25" />
                    </div>
                    <textarea
                      v-model="borradorDelRegistro[claveDeSesion(day)].__nota" rows="2"
                      placeholder="¿Algo que decir de esta sesión? (opcional)"
                      aria-label="Nota de la sesión"
                      class="w-full glass-input rounded-lg px-2.5 py-2 text-white text-[12px]"
                    ></textarea>
                    <div class="flex flex-wrap items-center gap-2">
                      <button type="button" @click="guardarRegistro(day)" :disabled="guardandoRegistro"
                              class="flex items-center gap-1.5 px-3 py-2 rounded-lg bg-[#caf300] text-[#17191e] text-[11px] font-black uppercase tracking-wider disabled:opacity-40">
                        <span class="material-symbols-outlined text-[14px]">check</span>
                        {{ guardandoRegistro ? 'Guardando…' : 'Guardar' }}
                      </button>
                      <button v-if="hayRegistro(day)" type="button" @click="borrarRegistro(day)"
                              class="px-3 py-2 glass-pill rounded-lg text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#ffb4ab]">
                        Borrar
                      </button>
                      <span v-if="mensajeDelRegistro" class="text-[11px] text-[#c5c9ac]">{{ mensajeDelRegistro }}</span>
                    </div>
                  </div>
                </div>
                <p v-if="day.strength_block.cooldown" class="text-[11px] text-[#8f9378]"><span class="font-black uppercase">Vuelta a la calma:</span> {{ day.strength_block.cooldown }}</p>
                <!-- PUERTA FALSA · video_ejercicio. La pidio la beta; hoy hay
                     un dibujo por ejercicio. La vista se cuenta una vez por
                     sesion aunque haya tres dias de fuerza. -->
                <PuertaFalsa
                  puerta="video_ejercicio"
                  etiqueta="Ver en vídeo"
                  icono="play_circle"
                  titulo="Todavía no existe"
                  texto="Estamos pensando en añadir un <b>vídeo corto de cada ejercicio</b>, con la técnica y los errores típicos. ¿Te serviría?"
                  clase="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-wider text-[#8f9378] hover:text-[#caf300] transition-colors"
                />
              </div>

              <!-- El porqué y el plan B, plegados: se leen cuando hacen falta, no en cada día.
                   Antes eran dos párrafos abiertos en todas las tarjetas (el responsable,
                   2026-09-23: "texto pesado que nadie lee"). -->
              <details v-if="day.purpose || day.adaptation" class="mt-auto pt-4 group">
                <summary class="cursor-pointer list-none flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-[#8f9378] hover:text-[#caf300]">
                  <span class="material-symbols-outlined text-[14px] transition-transform group-open:rotate-90">chevron_right</span>
                  {{ day.adaptation ? 'Por qué y si no es tu día' : 'Por qué' }}
                </summary>
                <div class="mt-1.5 space-y-1.5">
                  <p v-if="day.purpose" class="text-xs text-[#c5c9ac] leading-relaxed">{{ day.purpose }}</p>
                  <p v-if="day.adaptation" class="text-xs text-[#c5c9ac] leading-relaxed"><span class="font-black text-[#8f9378] uppercase text-[10px]">Si no es tu día:</span> {{ day.adaptation }}</p>
                </div>
              </details>
              <p v-if="day.edited?.moved_from" class="pt-3 text-[10px] font-black uppercase tracking-wider text-[#a5eeff] flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">edit_calendar</span>
                Movida por ti desde el {{ diaCorto(day.edited.moved_from) }}
              </p>
              <p v-if="day.edited?.added" class="pt-2 text-[10px] font-black uppercase tracking-wider text-[#d7b8ff] flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">add_circle</span>
                La añadiste tú
              </p>
              <p v-else-if="day.edited?.adjusted" class="pt-2 text-[10px] font-black uppercase tracking-wider text-[#ffd166] flex items-center gap-1">
                <span class="material-symbols-outlined text-[14px]">tune</span>
                Ajustada por ti
              </p>
              <!-- Cambiar la sesión, de hoy en adelante y nunca la carrera: ajustar sus pasos (Beta 3.0, F7;
                   carrera, bici, caminata y natación) y moverla dentro de su semana (F6). Botones y no
                   texto gris: José no los encontró (2026-10-19). -->
              <div v-if="movibleDia(day)"
                   class="pt-3 flex flex-wrap gap-2" :class="day.purpose || day.adaptation || day.edited?.moved_from ? '' : 'mt-auto'">
                <button v-if="day.date >= hoyISO && SE_AJUSTAN.includes(day.sport) && day.garmin_exportable !== false"
                        type="button" @click="abrirEditor(day)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#caf300]/40 bg-[#caf300]/5 text-[11px] font-black uppercase tracking-wider text-[#caf300] hover:bg-[#caf300]/15 active:scale-95 transition-all">
                  <span class="material-symbols-outlined text-[16px]">tune</span>
                  Ajustar
                </button>
                <button type="button" @click="empezarAMover(day)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#caf300]/40 bg-[#caf300]/5 text-[11px] font-black uppercase tracking-wider text-[#caf300] hover:bg-[#caf300]/15 active:scale-95 transition-all">
                  <span class="material-symbols-outlined text-[16px]">swap_horiz</span>
                  Mover de día
                </button>
                <button v-if="day.date >= hoyISO && day.edited?.adjusted && !day.edited?.added && SE_AJUSTAN.includes(day.sport)" type="button" @click="volverALaDelCoach(day)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-white/15 text-[11px] font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white hover:bg-white/5 active:scale-95 transition-all">
                  <span class="material-symbols-outlined text-[16px]">undo</span>
                  Volver a la del coach
                </button>
                <!-- Sólo lo que añadió él se quita: lo del coach se mueve o se ajusta (2026-10-23). -->
                <button v-if="day.date >= hoyISO && day.edited?.added" type="button" @click="quitarEntreno(day)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#ffb4ab]/40 text-[11px] font-black uppercase tracking-wider text-[#ffb4ab] hover:bg-[#ffb4ab]/10 active:scale-95 transition-all">
                  <span class="material-symbols-outlined text-[16px]">delete</span>
                  Quitar
                </button>
              </div>
              <!-- «Pedir ayuda» desde ESTA sesión (Beta 3.0, F3): la consulta llega con su
                   clave, y el diagnóstico compara lo decidido, lo codificado y el reloj. -->
              <button
                v-if="!day.is_rest"
                type="button"
                @click="store.abrirAyuda({ categoria: 'plan', sessionKey: claveDeSesion(day), sesion: `${tituloDelDia(day) || etiquetaDeLaSesion(day)} · ${fechaCorta(day.date)}` })"
                class="pt-2 self-start flex items-center gap-1 text-[10px] font-black uppercase tracking-wider text-[#8f9378] hover:text-[#a5eeff]"
              >
                <span class="material-symbols-outlined text-[14px]">support_agent</span>
                ¿Algo raro con esta sesión?
              </button>
              </div>
            </article>
            </template>
          </div>
          <!-- Un entreno suyo (2026-10-23): «es lo mismo que salir a correr sin que lo planificara el coach». -->
          <button v-if="puedeAnadir(week, diaElegido)" type="button" @click="anadirEntreno(diaElegido)"
                  class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-2xl border border-dashed border-[#d7b8ff]/50 text-[12px] font-black uppercase tracking-wider text-[#d7b8ff] hover:bg-[#d7b8ff]/5 active:scale-[0.99] transition-all">
            <span class="material-symbols-outlined text-[18px]">add_circle</span>
            Añadir un entreno el {{ diaCorto(diaElegido) }}
          </button>
          <details v-if="week.week_rationale" class="glass-card rounded-2xl border border-white/10 group">
            <summary class="cursor-pointer list-none flex items-center gap-2 px-4 py-3 text-xs font-black uppercase tracking-wider text-[#c5c9ac] hover:text-white">
              <span class="material-symbols-outlined text-[18px] text-[#caf300] transition-transform group-open:rotate-90">chevron_right</span>
              Por qué esta semana es así
            </summary>
            <div class="px-4 pb-4 text-sm text-[#c5c9ac] leading-relaxed coach-md" v-html="renderMarkdown(week.week_rationale)"></div>
          </details>

          <!-- Carga alta (o muy alta) contra su propio historial: por qué, con los números, y si
               es por lo que pidió. Lo escribe el código (services/ai/carga_segura.py). -->
          <p v-if="week.load_warning" class="flex items-start gap-1.5 text-[12px] text-[#ffd166] rounded-xl border border-[#ffd166]/30 bg-[#ffd166]/5 px-3 py-2">
            <span class="material-symbols-outlined text-[16px] shrink-0">monitor_heart</span>
            <span>{{ week.load_warning }}</span>
          </p>

          <!-- PUERTA FALSA · nutricion.
               ============================================================
               La funcion que se esta midiendo: una llamada DESPUES de escribir
               la semana que, con la carga de cada dia, diga que dias priorizar
               carbohidratos y cuales proteina. Recomendaciones generales, no
               una dieta: no tenemos sus datos para mas.
               Va aqui, debajo del razonamiento de la semana, porque es donde
               estaria si existiera. Las respuestas miden CUANTO la usaria, que
               es lo que decide si se construye: "cada semana" es un producto,
               "solo antes de una carrera" es otro mucho mas pequeño. -->
          <!-- Su fuerza no se está usando y no lo sabría: una rutina pegada sin confirmar,
               o el modo coach sin programa. Una línea con adónde ir. -->
          <p v-if="avisoDeFuerza" class="flex items-start gap-1.5 text-[12px] text-[#ffd166] rounded-xl border border-[#ffd166]/30 bg-[#ffd166]/5 px-3 py-2">
            <span class="material-symbols-outlined text-[16px] shrink-0">fitness_center</span>
            <span>{{ avisoDeFuerza }} <router-link to="/admin/perfil" class="underline font-bold">Perfil → Tu fuerza</router-link></span>
          </p>
          <!-- Fase nueva, programa de fuerza de antes (2026-10-21): toca revisarlo, sin imponerlo. -->
          <div v-else-if="renovarFuerza" class="flex items-start gap-2 text-[12px] text-[#e5e2e1] rounded-xl border border-[#a5eeff]/30 bg-[#a5eeff]/5 px-3 py-2.5">
            <span class="material-symbols-outlined text-[16px] text-[#a5eeff] shrink-0">autorenew</span>
            <div class="flex-1 space-y-1.5">
              <p class="leading-snug">
                Empezaste <b>{{ renovarFuerza.fase ? `«${renovarFuerza.fase}»` : 'una fase nueva' }}</b> el {{ diaCorto(renovarFuerza.desde) }}
                y tu programa de fuerza es de antes. Al cambiar de fase cambia lo que busca la fuerza: es buen momento para
                {{ store.profile?.strength_profile?.mode === 'coach' ? 'que el coach lo escriba otra vez' : 'actualizar tu rutina' }}.
                Si te funciona, mantenlo: dentro de una fase repetir los ejercicios es lo que deja subir la carga.
              </p>
              <div class="flex flex-wrap gap-2">
                <router-link to="/admin/perfil?tab=objetivos" class="px-2.5 py-1 rounded-lg border border-[#a5eeff]/40 text-[11px] font-black uppercase tracking-wider text-[#a5eeff] hover:bg-[#a5eeff]/10">Revisar mi fuerza</router-link>
                <button type="button" @click="mantenerFuerza" class="px-2.5 py-1 rounded-lg text-[11px] font-black uppercase tracking-wider text-[#8f9378] hover:text-white">Mantenerlo</button>
              </div>
            </div>
          </div>

          <!-- Sus «sí o sí» que casi no hace (2026-10-23, sin IA; utils/fijos.js): cada uno va al plan en su día, y
               una tabla más ambiciosa que su rutina le da semanas que no hace. Decide él. -->
          <div v-if="fijosQueCasiNoHace.length" class="flex items-start gap-2 text-[12px] text-[#e5e2e1] rounded-xl border border-[#ffd166]/35 bg-[#ffd166]/5 px-3 py-2.5">
            <span class="material-symbols-outlined text-[16px] text-[#ffd166] shrink-0">push_pin</span>
            <div class="flex-1 space-y-1.5">
              <p class="leading-snug">
                En tu tabla marcaste <b>«Sí o sí»</b> algo que casi no haces:
                <b>{{ fijosQueCasiNoHace.map(fraseDelFijo).join(', ') }}</b>.
                Lo que va «Sí o sí» tu plan lo pone siempre ese día. Si no suele encajarte, pásalo a <b>«Puedo»</b>: tu
                coach lo pondrá cuando convenga y tu semana se parecerá más a la que de verdad haces.
              </p>
              <div class="flex flex-wrap gap-2">
                <router-link to="/admin/perfil?tab=objetivos" class="px-2.5 py-1 rounded-lg border border-[#ffd166]/50 text-[11px] font-black uppercase tracking-wider text-[#ffd166] hover:bg-[#ffd166]/10">Revisar mi tabla</router-link>
                <button type="button" @click="mantenerFijos" class="px-2.5 py-1 rounded-lg text-[11px] font-black uppercase tracking-wider text-[#8f9378] hover:text-white">Mantenerlo así</button>
              </div>
            </div>
          </div>

          <PuertaFalsa
            puerta="nutricion"
            etiqueta="Qué comer esta semana"
            icono="restaurant"
            titulo="Todavía no existe"
            texto="Estamos pensando en añadir a tu semana <b>qué días priorizar carbohidratos y cuáles proteína</b>, según la carga de cada sesión. Serían recomendaciones generales, no una dieta. ¿Te serviría?"
            :opciones="[
              { valor: 'cada_semana', etiqueta: 'Sí, lo miraría cada semana' },
              { valor: 'carreras', etiqueta: 'Sólo en semanas de carrera' },
              { valor: 'no', etiqueta: 'No lo usaría' },
            ]"
            clase="self-start flex items-center gap-2 px-3 min-h-[40px] glass-pill rounded-xl text-[12px] font-bold text-white/85 hover:text-[#caf300] transition-colors"
          />

        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="text-[12px] text-white/60">Semana escrita el {{ fmtDate(plan.week_generated_at) }}</p>
            <!--
              Desactivado mientras se genera: borrar en ese momento deja el plan
              borrado aquí y una generación en marcha que, al terminar, lo vuelve a
              escribir. El atleta veía reaparecer lo que acababa de eliminar.
            -->
            <button
              v-if="hasSeason || hasWeek"
              @click="handleReset"
              :disabled="generating"
              :title="generating ? 'Espera a que termine la generación en curso' : ''"
              class="flex items-center gap-1.5 px-3 min-h-[40px] text-[12px] font-bold text-[#ffb4ab] hover:bg-[#93000a]/30 border border-[#ffb4ab]/30 rounded-xl transition-colors disabled:opacity-40 disabled:hover:bg-transparent"
            >
              <span class="material-symbols-outlined text-[16px]">delete</span>Eliminar plan
            </button>

          <!-- Aquí iba «¿Cuánto pagarías?» (PuertaDePago.vue), la disposición a pagar preguntada sin
               cifra. Se quitó el 2026-10-20: el responsable fijó el precio (Q60 / 8 USD al mes) y
               preguntarlo junto a ese precio confunde y ya no mide nada limpio. Sus respuestas
               siguen en Métricas. -->
        </div>
      </section>

      <!-- Tu mes: la semana en curso y las tres siguientes como las prevé el macrociclo.
           Es una VISTA, sin IA (el responsable, 2026-09-24): da la forma del mes sin
           generar ni subir al reloj semanas escritas con el retrato de hoy. -->
      <section v-if="mes.length" class="glass-card rounded-3xl border border-white/10 shadow-2xl p-5 sm:p-7 space-y-4" :class="hasWeek ? 'order-3' : ''">
        <header class="flex flex-wrap items-baseline justify-between gap-2">
          <h3 class="text-lg font-black text-white flex items-center gap-2.5">
            <span class="material-symbols-outlined text-[#caf300]">calendar_view_month</span>
            <span>Tu mes</span>
          </h3>
          <p class="text-[11px] text-[#8f9378]">Lo que prevé tu macrociclo. Las sesiones se escriben cada semana.</p>
        </header>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          <article v-for="w in mes" :key="w.numero"
                   class="rounded-2xl border p-4 space-y-2.5"
                   :class="w.actual ? 'border-[#caf300]/50 bg-[#caf300]/5' : 'border-white/10 bg-white/[0.03]'">
            <div class="flex items-center justify-between gap-2">
              <p class="text-[11px] font-black uppercase tracking-wider"
                 :class="w.actual ? 'text-[#caf300]' : 'text-[#c5c9ac]'">
                {{ w.actual ? `Semana ${w.numero} · esta semana` : `Semana ${w.numero}` }}
              </p>
              <span class="text-[10px] font-mono-data text-[#8f9378]">{{ fechaCorta(w.desde) }} – {{ fechaCorta(w.hasta) }}</span>
            </div>
            <p v-if="w.fase" class="text-[13px] font-bold text-white leading-snug">{{ w.fase }}</p>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="c in w.carreras" :key="c.nombre + c.fecha"
                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-[#ffd479]/15 border border-[#ffd479]/40 text-[#ffd479]">
                {{ c.prioridad ? `Carrera ${c.prioridad}` : 'Carrera' }} · {{ fechaCorta(c.fecha) }}
              </span>
              <span v-if="w.descarga"
                    class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-[#a5eeff]/15 border border-[#a5eeff]/40 text-[#a5eeff]">
                Descarga
              </span>
            </div>
            <p v-for="c in w.carreras" :key="'n' + c.nombre" class="text-[12px] text-[#f1f0ee]">{{ c.nombre }}</p>
            <p v-if="w.foco.length" class="text-[12px] text-[#c5c9ac] leading-snug">
              <span class="text-[#8f9378]">Se centra en</span> {{ w.foco.join(' y ') }}
            </p>
            <p v-if="w.horas" class="text-[11px] text-[#8f9378]">Referencia de la fase: unas {{ num(w.horas) }} h</p>
            <p class="text-[11px] font-bold pt-1 border-t border-white/10"
               :class="w.generada ? 'text-[#caf300]' : 'text-[#8f9378]'">
              {{ w.generada ? 'Ya generada'
                : (auto?.enabled ? 'Se generará sola la víspera' : `Se puede generar desde el ${fechaCorta(w.generableDesde)}`) }}
            </p>
          </article>
        </div>
      </section>

      <!-- Season plan. VA DEBAJO DE LA SEMANA y cerrado por defecto: es el mapa
           de meses, y lo que el atleta abre la app para ver son los proximos
           siete dias. Estaba arriba y abierto, asi que en un telefono habia que
           pasar el macrociclo entero -- tabla de fases y markdown incluidos --
           antes de llegar al lunes. -->
      <!-- Sin semana, «Eliminar plan» vive aquí: el de la semana está en su pie. -->
      <div v-if="hasSeason && !hasWeek" class="flex justify-end">
        <button @click="handleReset" :disabled="generating"
                class="flex items-center gap-1.5 px-3 min-h-[40px] text-[12px] font-bold text-[#ffb4ab] hover:bg-[#93000a]/30 border border-[#ffb4ab]/30 rounded-xl transition-colors disabled:opacity-40">
          <span class="material-symbols-outlined text-[16px]">delete</span>Eliminar plan
        </button>
      </div>
      <details v-if="hasSeason" class="glass-card rounded-3xl border border-white/10 shadow-2xl overflow-hidden" :class="hasWeek ? 'order-3' : ''">
        <summary class="cursor-pointer p-6 sm:p-7 text-lg font-black text-white flex items-center gap-2.5 select-none">
          <span class="material-symbols-outlined text-[#caf300]">timeline</span>
          <span>Tu plan de temporada</span>
          <span class="ml-auto material-symbols-outlined text-white/60">expand_more</span>
        </summary>
        <div class="px-6 sm:px-7 pb-7 space-y-5">
          <!-- Dónde está HOY el atleta dentro del macrociclo. Es el mismo
               número que recibe el planificador en su prompt, y hasta ahora
               sólo estaba ahí: el atleta veía una tabla de fases sin saber en
               cuál de ellas iba. -->
          <div v-if="posicionMacro" class="flex flex-wrap items-center gap-2">
            <span class="px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-wider bg-[#caf300]/15 border border-[#caf300]/40 text-[#caf300]">
              Semana {{ posicionMacro.semana }} de {{ posicionMacro.ultimaSemana }}
            </span>
            <span v-if="posicionMacro.faseNombre" class="px-3 py-1.5 rounded-lg text-[11px] font-bold text-[#c5c9ac] glass-pill">
              {{ posicionMacro.faseNombre }}
            </span>
            <span v-if="posicionMacro.esDescarga" class="px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-wider bg-[#a5eeff]/15 border border-[#a5eeff]/40 text-[#a5eeff]">
              Semana de descarga
            </span>
          </div>
          <!-- Qué busca la fuerza en esta fase (S7). El macrociclo lo escribía y ninguna
               pantalla lo enseñaba (auditoría del 2026-09-18). -->
          <p v-if="posicionMacro?.fase?.strength_quality" class="text-[12px] text-[#c5c9ac] -mt-2">
            <span class="font-black uppercase tracking-wider text-[10px] text-[#8f9378]">Fuerza en esta fase:</span>
            {{ posicionMacro.fase.strength_quality }}
          </p>

          <!-- El calendario de semanas. Cada cuadrito es una semana del
               macrociclo; las de descarga van marcadas y la actual resaltada.
               De un vistazo se ve la forma entera de la temporada, que una
               tabla de rangos no enseña. -->
          <div v-if="tiraDeSemanas.length" class="space-y-2">
            <div class="flex flex-wrap gap-1">
              <span
                v-for="w in tiraDeSemanas"
                :key="w.numero"
                :title="`Semana ${w.numero}${w.fase ? ' · ' + w.fase : ''}${w.descarga ? ' · descarga' : ''}`"
                class="w-7 h-7 rounded-md flex items-center justify-center text-[10px] font-mono-data border transition-colors"
                :class="[
                  w.actual ? 'ring-2 ring-[#caf300] ring-offset-1 ring-offset-transparent text-[#121700] bg-[#caf300] border-[#caf300] font-black'
                  : w.descarga ? 'bg-[#a5eeff]/15 border-[#a5eeff]/45 text-[#a5eeff]'
                  : w.pasada ? 'bg-white/10 border-white/15 text-[#8f9378]'
                  : 'bg-white/5 border-white/10 text-[#c5c9ac]',
                ]"
              >{{ w.numero }}</span>
            </div>
            <div class="flex flex-wrap gap-4 text-[10px] font-mono-data text-[#8f9378]">
              <span class="text-[#caf300]">▪ esta semana</span>
              <span class="text-[#a5eeff]">▪ descarga (volumen reducido)</span>
              <span>▪ ya pasó</span>
            </div>
          </div>

          <!-- `overflow-x-auto` sin `min-w` no scrollea: comprime. Las cuatro
               columnas se estrujaban y "Enfasis" se partia en cinco lineas. -->
          <div v-if="phases.length" class="overflow-x-auto -mx-1 px-1">
            <table class="w-full min-w-[520px] text-xs font-mono-data text-[#c5c9ac] border-collapse">
              <thead>
                <tr class="text-[#8f9378] uppercase tracking-widest text-[10px]">
                  <th class="text-left py-2 pr-4">Fase</th>
                  <th class="text-left py-2 pr-4">Semanas</th>
                  <th class="text-left py-2 pr-4" title="Carga semanal de Garmin (la de tu reloj) de una semana normal de la fase">Carga/sem</th>
                  <th class="text-left py-2">Énfasis</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(ph, i) in phases" :key="i" class="border-t border-white/10">
                  <td class="py-2 pr-4 text-white font-bold">{{ ph.name }}</td>
                  <td class="py-2 pr-4">{{ ph.start_week }}–{{ ph.end_week }}</td>
                  <!-- La carga manda; las horas quedan debajo como referencia de agenda.
                       Un macrociclo guardado antes de la v1.2 no trae carga y muestra
                       sus horas como siempre. -->
                  <td class="py-2 pr-4">
                    <span v-if="ph.weekly_load_target" class="text-white font-bold">{{ Math.round(ph.weekly_load_target) }}</span>
                    <span v-if="ph.weekly_hours_target" class="block text-[10px] text-[#8f9378]">~{{ ph.weekly_hours_target }} h</span>
                    <span v-if="!ph.weekly_load_target && !ph.weekly_hours_target">—</span>
                  </td>
                  <td class="py-2">
                    {{ ph.intensity_emphasis || '—' }}
                    <span v-if="ph.strength_quality" class="block text-[10px] text-[#8f9378]">Fuerza: {{ ph.strength_quality }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="text-sm text-[#e5e2e1] leading-relaxed coach-md" v-html="renderMarkdown(plan.season_plan_md)"></div>
          <p class="text-[11px] font-mono-data text-[#8f9378]">Generado: {{ fmtDate(plan.season_generated_at) }}</p>
        </div>
      </details>

      <!-- Cost -->
      <details v-if="store.isAdmin && summary && summary.total_cost_usd != null" class="glass-card rounded-2xl border border-white/10 order-3">
        <summary class="cursor-pointer p-5 text-sm font-black text-white select-none">Costo de esta generación</summary>
        <div class="px-5 pb-5 text-xs font-mono-data text-[#c5c9ac] space-y-1">
          <p>Total: <span class="text-[#caf300] font-black">${{ Number(summary.total_cost_usd).toFixed(4) }}</span> · {{ summary.total_tokens || 0 }} tokens</p>
          <div v-for="(a, i) in (summary.cost_breakdown || [])" :key="i" class="flex justify-between border-t border-white/10 pt-1">
            <span>{{ a.agent || a.role || a.name }}</span>
            <span>${{ Number(a.cost_usd || a.cost || 0).toFixed(4) }}</span>
          </div>
        </div>
      </details>

      <div v-if="!hasSeason && !hasWeek && !generating" class="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10 leading-relaxed">
        Todavía no hay ningún plan. Pulsa <strong class="text-white">"Generar Plan de Temporada"</strong> para empezar.
        <br />Necesitas tu correo y contraseña de Garmin Connect.
      </div>
    </template>
    <MoverSesionHoja :hoja="hoja" @modo="cambiarModo" @confirmar="confirmarMovimiento" @cancelar="cerrarHoja" />

    <EditorDeSesion
      v-if="editando && !editando.fuerza"
      :session-key="editando.sessionKey"
      :titulo="editando.titulo"
      :subtitulo="editando.subtitulo"
      :nuevo="editando.nuevo || null"
      :plan-version="plan.plan_version || ''"
      @cerrar="editando = null"
      @guardado="alGuardarElAjuste"
    />
    <EditorDeFuerza
      v-if="editando && editando.fuerza"
      :session-key="editando.sessionKey"
      :titulo="editando.titulo"
      :subtitulo="editando.subtitulo"
      :nuevo="editando.nuevo || null"
      :plan-version="plan.plan_version || ''"
      @cerrar="editando = null"
      @guardado="alGuardarElAjuste"
    />
  </div>
</template>

<script setup>
import { deporteVisible, etiquetaDeLaSesion, fechaCorta } from '../../utils/deportes';
import { ref, reactive, computed, onMounted, onUnmounted, h, watch, nextTick } from 'vue';
import { useAppStore } from '../../stores/appStore';
import swal, { textoDe } from '../../utils/swal';
import { posicionEnLaTemporada, avisoDeMacrociclo, avisoDeSemana, semanasDelMes, topeDeSemanas, antelacionDeLaSemana, fuerzaPorRevisar } from '../../utils/macrociclo';
import { fijosPocoHechos, fraseDelFijo, firmaDeFijos } from '../../utils/fijos';
import { num } from '../../utils/garmin';
import { renderMarkdown } from '../../utils/markdown';
import { MINIMO_PERFIL } from '../../utils/profile';
import { cargarCatalogo, imagenDe } from '../../utils/ejercicios';
import PuertaFalsa from '../../components/layout/PuertaFalsa.vue';
import { getAcceso, startSeason, startWeek, getStatus, resetPlan, startGarminUpload, getGarminUploadStatus, sendCoachAnswers,
  getAutoWeek, setAutoWeek, getGenerationQuota, getGarminSessions, moverSesion, editarSesion } from '../../services/plan';
import EditorDeSesion from '../../components/forms/EditorDeSesion.vue';
import EditorDeFuerza from '../../components/forms/EditorDeFuerza.vue';
import CalendarioDeSemanas from '../../components/plan/CalendarioDeSemanas.vue';
import { MARCAS, textoDeLaSesion } from '../../utils/hecho';
import EstadoDelReloj from '../../components/plan/EstadoDelReloj.vue';
import MoverSesionHoja from '../../components/plan/MoverSesionHoja.vue';
import { useRoute, useRouter } from 'vue-router';
import { fechasDeLaProximaSemana } from '../../utils/semana';
import { getGarminPasswordStatus } from '../../services/profile';
import { enviarFeedback } from '../../services/feedback';
import { registroDeFuerza, guardarRegistroDeFuerza, borrarRegistroDeFuerza,
         ultimaVezPorEjercicio, ultimaDe, comoLoHizo } from '../../services/fuerza';

const store = useAppStore();

/**
 * Por qué un atleta borra su plan. Las cinco del plan Beta 1.1, y no más.
 *
 * Cada una apunta a una parte distinta del sistema, que es lo que las hace
 * accionables: los días son la rejilla (2.A), el volumen es la prescripción,
 * las sesiones son la calidad del output, y lesionarse no es un fallo del
 * producto. "Otra cosa" existe para que nadie se vea obligado a mentir.
 */
const MOTIVOS_DE_BORRADO = {
  dias_deportes: 'No son los días o deportes que quería',
  // «Demasiado o muy poco» eran UN motivo y no se sabía hacia dónde. Partido el 2026-10-20 para medir
  // la hipótesis del responsable: un atleta avanzado ve suave su primera semana y cree que le falta.
  poco: 'Se me queda corta: quiero más volumen o intensidad',
  mucho: 'Es demasiado para mí',
  sesiones: 'Las sesiones no me convencen',
  salud: 'Me lesioné o me sentí mal',
  otra: 'Otra cosa',
};

// Las imagenes de los ejercicios de fuerza.
//
// El catalogo se pide una vez y en segundo plano: son 29 KB que solo hacen
// falta si el atleta tiene una sesion de fuerza delante, y mientras no ha
// llegado `imagenDe` devuelve null, asi que no se pinta ningun boton roto.
// `catalogoListo` existe solo para que Vue vuelva a evaluar cuando llegue.
const catalogoListo = ref(0);
cargarCatalogo().then(() => { catalogoListo.value += 1; });

const imagenesAbiertas = reactive({});
const alternarImagen = (dia, i) => {
  const clave = dia + '-' + i;
  imagenesAbiertas[clave] = !imagenesAbiertas[clave];
};

// El nombre visible es el respaldo de `video_key`: los planes generados antes
// de que existiera el catalogo no traen clave, y su nombre sigue resolviendo
// por los alias.
/** 120 -> "2'", 90 -> "90\"". */
// «30 s», «2 min», «2 min 30 s»: el 150" y el 2' de antes no los lee quien no es entrenador (revisión del 2026-10-22).
const duracionCorta = (segundos) => {
  const t = Math.round(Number(segundos) || 0);
  if (t < 60) return `${t} s`;
  return t % 60 ? `${Math.floor(t / 60)} min ${t % 60} s` : `${t / 60} min`;
};
// RIR y RPE, dichos una vez bajo la sesión que los usa.
const usaRirORpe = (bloque) => ((bloque && bloque.exercises) || []).some((ex) => /RIR|RPE/.test(`${ex.load || ''} ${ex.reps || ''} ${ex.cues || ''}`));

/**
 * La sesion de fuerza como grupos, venga como venga (Beta 2.1). El mismo
 * criterio que `plan_outputs.grupos_de_fuerza`: con `groups`, esos; sin ellos
 * (todo plan anterior), un grupo por ejercicio, que se pinta sin encabezado.
 * `ei` es la posicion en la lista plana, que es la clave de las imagenes.
 */
const gruposDeFuerza = (bloque) => {
  const ejercicios = (bloque && bloque.exercises) || [];
  const grupos = (bloque && bloque.groups) || [];
  if (!grupos.length) {
    return ejercicios.map((ex, ei) => ({ clave: `e${ei}`, encabezado: '', items: [{ ex, ei, etiqueta: `${ei + 1}.` }] }));
  }
  let ei = 0;
  return grupos.map((g, gi) => {
    const letra = g.label || String.fromCharCode(65 + gi);
    const lista = g.exercises || [];
    const series = [...new Set(lista.map((x) => x.sets))];
    // «Bloque A · 3 ejercicios seguidos · 2 rondas · 1 min 30 s de descanso entre rondas». Salía «1 RONDAS» y
    // un descanso «entre rondas» con una sola ronda (revisión del 2026-10-22).
    const rondas = lista.length > 1 && series.length === 1 ? Number(series[0]) || 0 : 0;
    const partes = [`Bloque ${letra}`];
    if (lista.length > 1) partes.push(`${lista.length} ejercicios seguidos`);
    if (rondas) partes.push(rondas === 1 ? '1 ronda' : `${rondas} rondas`);
    if (g.rest_between_rounds_s && rondas !== 1) partes.push(`${duracionCorta(g.rest_between_rounds_s)} de descanso entre rondas`);
    return {
      clave: `g${gi}`,
      encabezado: lista.length > 1 || g.rest_between_rounds_s ? partes.join(' · ') : '',
      items: lista.map((ex, i) => ({ ex, ei: ei++, etiqueta: `${letra}${i + 1}` })),
    };
  });
};

const imagenDeEjercicio = (ex) => {
  void catalogoListo.value;
  return imagenDe(ex && ex.name, ex && ex.video_key);
};

// El minimo de perfil. La regla vive en el backend
// (PlanService::MINIMO_PERFIL); esto sirve para decirlo antes de que el atleta
// pulse y gaste una llamada que va a rebotar.
// null mientras no se sabe: no se avisa de un perfil incompleto antes de haberlo leído.
const completion = computed(() => store.athlete.completionRate ?? store.profile?.completionRate ?? null);

// --- Registrar lo que levantó (Beta 2.1, Database/026) --------------------
//
// La app prescribía fuerza y no sabía qué se hacía con ella, así que su programa
// no podía progresar: es lo primero que tienen Hevy, Strong o Fitbod. Garmin no
// lo resuelve (devuelve repeticiones y descansos, pero el ejercicio llega como
// desconocido y el peso a 0, medido el 2026-09-17).
const registroAbierto = reactive({});
const borradorDelRegistro = reactive({});
const registroPorSesion = reactive({});
const guardandoRegistro = ref(false);
const mensajeDelRegistro = ref('');
const hechoDelRegistro = reactive({});
const ultimaVez = ref({});

const claveDeSesion = (day) => `${day.date}|${day.session_slot || 'unica'}`;
const hayRegistro = (day) => Boolean(registroPorSesion[claveDeSesion(day)]);

// Lo que se despliega a un toque: el porqué de una dosis, de un cambio o de lo quitado.
const detalleAbierto = reactive({});
const alternarDetalle = (clave) => { detalleAbierto[clave] = !detalleAbierto[clave]; };

// Si su fuerza elegida no se está usando, se dice (y dónde arreglarlo).
const avisoDeFuerza = computed(() => {
  const modo = store.profile?.strength_profile?.mode;
  const rutina = store.profile?.strength_routine;
  const confirmada = Boolean(rutina?.confirmed_at && (rutina?.sessions || []).length);
  if (modo === 'rutina' && !confirmada) return 'Tu rutina está sin confirmar: tu semana no la usa todavía.';
  if (modo === 'coach' && !confirmada) return 'Pediste que el coach te arme la fuerza, pero aún no tienes programa confirmado.';
  return '';
});

// Fase nueva con el programa de fuerza de antes (utils/macrociclo.fuerzaPorRevisar). «Mantenerlo» se
// recuerda en este navegador para esa fase.
const fuerzaMantenida = ref('');
try { fuerzaMantenida.value = localStorage.getItem('pc.fuerza_mantenida') || ''; } catch (e) { /* sin almacenamiento */ }
const renovarFuerza = computed(() => {
  const rutina = store.profile?.strength_routine;
  const modo = store.profile?.strength_profile?.mode;
  if (!['rutina', 'coach'].includes(modo) || !(rutina?.sessions || []).length) return null;
  const r = fuerzaPorRevisar({ confirmadoEl: rutina?.confirmed_at, seasonPhases: plan.value?.season_phases,
    weekStartDay: store.profile?.week_start_day });
  return r && fuerzaMantenida.value !== r.desde ? r : null;
});
const mantenerFuerza = () => {
  fuerzaMantenida.value = renovarFuerza.value?.desde || '';
  try { localStorage.setItem('pc.fuerza_mantenida', fuerzaMantenida.value); } catch (e) { /* sin almacenamiento */ }
};

// Sus «sí o sí» que hizo como mucho 1 de 4 semanas (utils/fijos.js). «Mantenerlo así» se recuerda en este
// navegador hasta la siguiente cuenta (cambia con cada semana generada).
const fijosMantenidos = ref('');
try { fijosMantenidos.value = localStorage.getItem('pc.fijos_mantenidos') || ''; } catch (e) { /* sin almacenamiento */ }
const fijosQueCasiNoHace = computed(() => {
  const lista = fijosPocoHechos(plan.value?.summary, store.profile?.sport_days);
  return lista.length && fijosMantenidos.value !== firmaDeFijos(plan.value?.summary, lista) ? lista : [];
});
const mantenerFijos = () => {
  fijosMantenidos.value = firmaDeFijos(plan.value?.summary, fijosPocoHechos(plan.value?.summary, store.profile?.sport_days));
  try { localStorage.setItem('pc.fijos_mantenidos', fijosMantenidos.value); } catch (e) { /* sin almacenamiento */ }
};

const tieneEjercicios = (day) => Boolean(day.strength_block && (day.strength_block.exercises || []).length);

/**
 * El título del día. En fuerza, SU sesión ("Upper A"), que es lo que busca en su
 * rutina: la decisión escribía "Fuerza" o, peor, "Pierna + core" en un día de torso,
 * porque decide los días antes de saber qué sesión cae en cada uno. Los planes
 * guardados antes del 2026-09-23 no traen `session_name`: se busca en su rutina.
 */
const tituloDelDia = (day) => {
  if (day.is_rest) return day.focus || 'Descanso';
  const bloque = day.strength_block;
  if (bloque?.session_name) return bloque.session_name;
  if (bloque?.routine_session_id) {
    const sesion = (store.profile?.strength_routine?.sessions || []).find((s) => s.id === bloque.routine_session_id);
    if (sesion?.name) return sesion.name;
  }
  return day.focus || '';
};

async function cargarRegistroDeFuerza() {
  try {
    const datos = await registroDeFuerza({});
    (datos?.logs || []).forEach((log) => {
      registroPorSesion[`${log.fecha}|${log.session_slot || 'unica'}`] = log;
    });
    ultimaVez.value = ultimaVezPorEjercicio(datos?.logs);
  } catch (e) {
    // Que no se pueda leer el registro no puede tumbar la pantalla del plan.
    ultimaVez.value = {};
  }
}

function alternarRegistro(day) {
  const clave = claveDeSesion(day);
  mensajeDelRegistro.value = '';
  hechoDelRegistro[clave] = '';
  if (!borradorDelRegistro[clave]) {
    const guardado = registroPorSesion[clave];
    const previos = {};
    (guardado?.exercises || []).forEach((e) => { previos[e.exercise_id || e.name] = e; });
    const borrador = { __nota: guardado?.note || '' };
    (day.strength_block?.exercises || []).forEach((ex) => {
      const id = ex.id || ex.name;
      // Un registro de antes del 2026-09-23 no tiene id: se encuentra por el nombre.
      const hecho = previos[id] || previos[ex.name] || {};
      // Sólo lo que ÉL escribió. Lo prescrito y los kilos de la última vez van como
      // pista gris (placeholder), no como valor: hasta el 2026-09-23 se rellenaban y,
      // al guardar, lo que nadie tocó quedaba registrado como hecho.
      borrador[id] = {
        sets: hecho.sets ?? null,
        reps: hecho.reps ?? '',
        weight_kg: hecho.weight_kg ?? null,
      };
    });
    borradorDelRegistro[clave] = borrador;
  }
  registroAbierto[clave] = !registroAbierto[clave];
}

async function guardarRegistro(day) {
  const clave = claveDeSesion(day);
  const borrador = borradorDelRegistro[clave] || {};
  const vacio = (v) => v === null || v === undefined || v === '';
  const ejercicios = (day.strength_block?.exercises || []).map((ex) => {
    const id = ex.id || ex.name;
    const fila = borrador[id] || {};
    return {
      exercise_id: ex.id || '',
      name: ex.name,
      sets: vacio(fila.sets) ? null : fila.sets,
      reps: String(fila.reps ?? ''),
      weight_kg: vacio(fila.weight_kg) ? null : fila.weight_kg,
      done: true,
    };
    // Una fila en blanco no es "lo hice": no se registra.
  }).filter((e) => e.sets !== null || e.reps.trim() !== '' || e.weight_kg !== null);
  if (!ejercicios.length) {
    mensajeDelRegistro.value = 'Escribe al menos un ejercicio, o pulsa «Lo hice tal cual».';
    return;
  }
  guardandoRegistro.value = true;
  mensajeDelRegistro.value = '';
  try {
    await guardarRegistroDeFuerza({
      fecha: day.date,
      session_slot: day.session_slot || 'unica',
      routine_session_id: day.strength_block?.routine_session_id || '',
      note: borrador.__nota || '',
      exercises: ejercicios,
    });
    await cargarRegistroDeFuerza();
    mensajeDelRegistro.value = '';
    hechoDelRegistro[clave] = 'Guardado.';
    registroAbierto[clave] = false;
  } catch (e) {
    mensajeDelRegistro.value = e?.response?.data?.message || 'No se pudo guardar.';
  } finally {
    guardandoRegistro.value = false;
  }
}

/** Rellena lo que falte con lo prescrito y los kilos de la última vez: "lo hice tal cual". */
function hiceTalCual(day) {
  const borrador = borradorDelRegistro[claveDeSesion(day)];
  if (!borrador) return;
  (day.strength_block?.exercises || []).forEach((ex) => {
    const id = ex.id || ex.name;
    const fila = borrador[id] || (borrador[id] = { sets: null, reps: '', weight_kg: null });
    if (fila.sets === null || fila.sets === '') fila.sets = ex.sets ?? null;
    if (!fila.reps) fila.reps = ex.reps ?? '';
    if (fila.weight_kg === null || fila.weight_kg === '') fila.weight_kg = ultimaDe(ultimaVez.value, ex)?.weight_kg ?? null;
  });
}

async function borrarRegistro(day) {
  const clave = claveDeSesion(day);
  try {
    await borrarRegistroDeFuerza({ fecha: day.date, session_slot: day.session_slot || 'unica' });
    // Primero se cierra el panel: borrar el borrador con el panel abierto dejaba sus
    // campos leyendo de un objeto que ya no existía ("Cannot read properties of
    // undefined (reading 'Sentadilla dividida')", auditoría del 2026-09-18).
    registroAbierto[clave] = false;
    delete registroPorSesion[clave];
    delete borradorDelRegistro[clave];
    await cargarRegistroDeFuerza();
    mensajeDelRegistro.value = '';
    hechoDelRegistro[clave] = 'Borrado.';
  } catch (e) {
    mensajeDelRegistro.value = 'No se pudo borrar.';
  }
}


const perfilSuficiente = computed(() => completion.value === null || completion.value >= MINIMO_PERFIL);

const plan = computed(() => store.plan || {});
const loaded = computed(() => store.planLoaded);
const generating = ref(false);

// Si el atleta guardó su contraseña de Garmin (cifrada, en su perfil). Nunca
// llega aquí la contraseña: sólo si existe. Con ella no se le pide nada, que
// es exactamente para lo que la guardó.
const claveGuardada = ref(false);
getGarminPasswordStatus()
  .then((d) => { claveGuardada.value = !!d?.saved; })
  .catch(() => { claveGuardada.value = false; });
const progress = ref(0);
const statusText = ref('');
// Las preguntas del coach a mitad de generacion. `jobPreguntado` evita que
// el siguiente sondeo las vuelva a abrir mientras el worker todavia no ha
// leido las respuestas que ya se enviaron.
const preguntasCoach = ref(null);
const respuestasCoach = ref([]);
const enviandoRespuestas = ref(false);
const errorRespuestas = ref('');
const jobPreguntado = ref('');
const preguntasResueltas = ref(false);
let jobEnCurso = '';
const genError = ref('');
let pollTimer = null;
let pollCount = 0;

const weeklyPlan = computed(() => plan.value?.weekly_plan || {});
const weeks = computed(() => weeklyPlan.value.weeks || []);
const phases = computed(() => plan.value?.season_phases?.phases || plan.value?.season_phases || []);
const router = useRouter();
const summary = computed(() => plan.value?.summary || null);

// Hasta cuándo puede generar y el precio (Database/035-036). Sin respuesta, no se enseña nada.
const acceso = ref(null);
const cargarAcceso = () => getAcceso().then((a) => { acceso.value = a; }).catch(() => { acceso.value = null; });
cargarAcceso();
// Lleva a «Precios», en la tuerca (2026-10-21): ahí elige mensual o anual y desde ahí escribe a Ayuda.
const quieroSuscribirme = () => store.abrirPrecios();

// «Por qué tu primera semana es así»: sólo con una semana generada, su número real de Garmin y sin
// haberla cerrado ya (se recuerda en el navegador, por semana).
const CLASES_DE_CALIDAD = ['vo2max', 'tempo_threshold', 'repeticiones', 'hill_repeats'];
const primeraCerrada = ref(new Set());
try {
  primeraCerrada.value = new Set(JSON.parse(localStorage.getItem('primera_semana_cerrada') || '[]'));
} catch (e) { /* sin almacenamiento: se enseña */ }
const primeraSemana = computed(() => {
  const semanas = weeklyPlan.value.weeks || [];
  const cronica = Number(summary.value?.volume_baseline?.chronic_weekly_hours);
  if (semanas.length !== 1 || !cronica) return null;
  const dias = (semanas[0].days || []).filter((d) => !d.is_rest);
  const inicio = dias[0]?.date || '';
  if (!inicio || primeraCerrada.value.has(inicio)) return null;
  return {
    inicio,
    cronica,
    estaSemana: dias.reduce((t, d) => t + (Number(d.total_duration_minutes) || 0), 0) / 60,
    calidad: dias.filter((d) => CLASES_DE_CALIDAD.includes(d.intensity_class)).length,
  };
});
const cerrarPrimeraSemana = () => {
  const s = new Set(primeraCerrada.value);
  s.add(primeraSemana.value?.inicio);
  primeraCerrada.value = s;
  try { localStorage.setItem('primera_semana_cerrada', JSON.stringify([...s])); } catch (e) { /* nada */ }
};
const coma = (n) => (Math.round(Number(n) * 10) / 10).toLocaleString('es');
const hasSeason = computed(() => !!plan.value?.has_season);
const mes = computed(() => (hasSeason.value ? semanasDelMes(plan.value?.season_phases, {
  weekStartDay: store.profile?.week_start_day,
  competiciones: store.profile?.competitions || [],
  diasGenerados: (plan.value?.weekly_plan?.weeks || []).flatMap((w) => (w.days || []).map((d) => d.date)),
}) : []));

// Dónde va el atleta dentro de su macrociclo, y si toca renovarlo. Ver
// utils/macrociclo.js: la semana que manda para el prompt se calcula en
// Python; esto sólo decide si se enseña el aviso.
const posicionMacro = computed(() =>
  (hasSeason.value ? posicionEnLaTemporada(plan.value?.season_phases, undefined, store.profile?.week_start_day) : null));
const avisoMacro = computed(() => avisoDeMacrociclo(posicionMacro.value));

// Una casilla por semana del macrociclo, para el calendario. Se construye de
// las fases, que son la única fuente: nadie declara "24 semanas" en ningún
// sitio -- la duración es la última semana de la última fase.
const tiraDeSemanas = computed(() => {
  const fases = phases.value || [];
  if (!fases.length) return [];
  const ultima = Math.max(...fases.map((f) => Number(f.end_week) || 0));
  if (!ultima || ultima > 104) return [];   // un macrociclo de dos años no es un macrociclo
  const actual = posicionMacro.value?.semana ?? null;
  return Array.from({ length: ultima }, (_, i) => {
    const numero = i + 1;
    const fase = fases.find((f) => (Number(f.start_week) || 0) <= numero && numero <= (Number(f.end_week) || 0));
    return {
      numero,
      fase: fase?.name || '',
      descarga: (fase?.deload_weeks || []).includes(numero),
      actual: numero === actual,
      pasada: actual != null && numero < actual,
    };
  });
});
const hasWeek = computed(() => !!plan.value?.has_week);

// ---- La semana en el telefono ----------------------------------------------

const fechasDe = (week) => (week.days || []).map((d) => d.date).filter(Boolean).sort();

// ---- El calendario y el día elegido (2026-10-21) ------------------------------------------
// Las semanas se ven en `CalendarioDeSemanas`; debajo, sólo el día que se toca. Las ACTIVAS (la de
// hoy y las que vienen) son las que se pueden cambiar: las terminadas se cerraron en el expediente.
// Se guarda el NÚMERO y la semana se busca en el plan de ahora: guardando el objeto, tras mover una
// sesión el detalle seguía enseñando la copia de antes (el plan se reemplaza entero).
const semanaMostradaNum = ref(null);
const semanaMostrada = computed(() => weeks.value.find((w) => w.week_number === semanaMostradaNum.value) || null);
const diaElegido = ref('');
const calendarioRef = ref(null);
const calendarioCmp = ref(null);
const semanasActivas = computed(() => weeks.value.filter((w) => (w.days || []).some((d) => d.date && d.date >= hoyISO)));
const fechaActiva = (f) => semanasActivas.value.some((w) => (w.days || []).some((d) => d.date === f));
const sesionesDeLaFecha = (f) => weeks.value.flatMap((w) => (w.days || []).filter((d) => d.date === f));
// Mover: cualquier sesión de una semana activa, también la de un día que ya pasó («ayer no pude»).
const movibleDia = (day) => !day.is_rest && day.intensity_class !== 'race_day' && fechaActiva(day.date);
// Lo que se puede arrastrar de una casilla: su única sesión movible.
const movibleEl = (fecha) => {
  const s = sesionesDeLaFecha(fecha).filter(movibleDia);
  return s.length === 1 ? claveDeSesion(s[0]) : null;
};
const detalleRef = ref(null);
const estadoDe = (day) => store.estadoSesiones?.sesiones?.[claveDeSesion(day)] || null;
// `?dia=AAAA-MM-DD` (desde Inicio): abre el plan en ese día, una vez, cuando el calendario ya está.
const route = useRoute();
let diaPedido = /^\d{4}-\d{2}-\d{2}$/.test(String(route.query.dia || '')) ? String(route.query.dia) : '';
watch(() => [calendarioCmp.value, weeks.value.length], () => {
  if (!diaPedido || !calendarioCmp.value || !weeks.value.length) return;
  const dia = diaPedido;
  diaPedido = '';
  setTimeout(() => {
    irASemanaDe(dia);
    diaElegido.value = dia;
    nextTick(() => detalleRef.value?.scrollIntoView?.({ behavior: 'smooth', block: 'nearest' }));
  }, 150);
}, { immediate: true });
const elegirDia = (f) => {
  diaElegido.value = f;
  // En el teléfono el detalle puede quedar fuera de la pantalla: se acerca lo justo.
  nextTick(() => {
    const r = detalleRef.value?.getBoundingClientRect?.();
    if (r && r.top > window.innerHeight - 140) detalleRef.value.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });
};
// Al cambiar de semana, el día elegido pasa a uno de ella: hoy si está, si no el primero que viene.
watch(semanaMostrada, (w) => {
  const fechas = w ? fechasDe(w) : [];
  if (!fechas.length || fechas.includes(diaElegido.value)) return;
  const conSesion = (f) => (w.days || []).some((d) => d.date === f && !d.is_rest);
  diaElegido.value = fechas.includes(hoyISO) ? hoyISO
    : (fechas.find((f) => f >= hoyISO && conSesion(f)) || fechas.find(conSesion) || fechas[0]);
});
// Ir a la semana de una fecha (el mismo orden que el calendario: por su primer día).
const irASemanaDe = (fecha) => {
  const orden = weeks.value.map(fechasDe).filter((f) => f.length).sort((a, b) => a[0].localeCompare(b[0]));
  const i = orden.findIndex((f) => f.includes(fecha));
  if (i >= 0) calendarioCmp.value?.irA(i);
};

/**
 * Mover una sesión (Beta 3.0, F6; 2026-10-21: de cualquier semana activa y a la otra semana).
 * «Mover» pone el calendario en modo mover: se marcan los días a los que puede ir y, al tocar
 * uno, la hoja enseña el cambio calculado en seco por el servidor, con sus avisos. Nada viaja al
 * reloj aquí: la sesión queda «cambios sin enviar» hasta «Enviar al reloj».
 */
const moviendo = ref(null);   // {clave, fecha, titulo, pasado}
const hoja = ref(null);
const deshacer = ref(null);
let temporizadorDeshacer = null;
const empezarAMover = (day) => {
  moviendo.value = { clave: claveDeSesion(day), fecha: day.date, titulo: tituloDelDia(day) || etiquetaDeLaSesion(day),
    pasado: day.date < hoyISO };
  deshacer.value = null;
  // Si la semana de hoy no tiene adónde ir (el domingo), se enseña la siguiente.
  setTimeout(() => {
    calendarioRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    const aqui = destinosValidos.value.filter((f) => (semanaMostrada.value?.days || []).some((d) => d.date === f));
    if (!aqui.length && destinosValidos.value.length) irASemanaDe(destinosValidos.value[0]);
  }, 0);
};
const empezarAMoverClave = ({ clave, fecha }) => {
  const d = sesionesDeLaFecha(fecha).find((x) => claveDeSesion(x) === clave);
  if (d) empezarAMover(d);
};
const cancelarMover = () => { moviendo.value = null; hoja.value = null; };
const cerrarHoja = () => { hoja.value = null; };
// Hoy o después, de una semana activa, sin la carrera y con sitio (como mucho dos sesiones por día).
const destinosValidos = computed(() => {
  if (!moviendo.value) return [];
  const fechas = new Set(semanasActivas.value.flatMap(fechasDe));
  return [...fechas].sort().filter((f) => {
    if (f < hoyISO || f === moviendo.value.fecha) return false;
    const s = sesionesDeLaFecha(f).filter((d) => !d.is_rest);
    return !s.some((d) => d.intensity_class === 'race_day') && s.length <= 1;
  });
});
const pedidoDeLaHoja = () => ({ sessionKey: hoja.value.origen.clave, toDate: hoja.value.destino.fecha,
  modo: hoja.value.modo, planVersion: plan.value?.plan_version });
const calcularHoja = async () => {
  const h = hoja.value;
  if (!h) return;
  Object.assign(h, { cargando: true, error: '', vista: null });
  try {
    h.vista = (await moverSesion({ ...pedidoDeLaHoja(), dryRun: true })).preview;
  } catch (e) {
    h.error = e?.response?.data?.message || 'No se pudo calcular el cambio.';
  } finally {
    h.cargando = false;
  }
};
// Lo que la hoja enseña de una sesión: su deporte, su nombre y sus minutos.
const fichaDe = (d) => ({ titulo: tituloDelDia(d) || etiquetaDeLaSesion(d), sport: deporteVisible(d),
  minutos: Math.round(Number(d.total_duration_minutes) || 0) });
const alElegirDestino = async (fecha) => {
  const ocupadas = sesionesDeLaFecha(fecha).filter((d) => !d.is_rest);
  const origen = sesionesDeLaFecha(moviendo.value.fecha).find((d) => claveDeSesion(d) === moviendo.value.clave);
  hoja.value = reactive({
    origen: { ...moviendo.value, ...(origen ? fichaDe(origen) : {}) },
    destino: { fecha, sesiones: ocupadas.map((d) => tituloDelDia(d) || etiquetaDeLaSesion(d)), fichas: ocupadas.map(fichaDe) },
    // La de un día pasado no se intercambia: mandaría la otra al pasado.
    modo: ocupadas.length && moviendo.value.pasado ? 'doble' : 'intercambiar',
    vista: null, cargando: true, error: '', guardando: false,
  });
  await calcularHoja();
};
const cambiarModo = (m) => { if (hoja.value) { hoja.value.modo = m; calcularHoja(); } };
const confirmarMovimiento = async () => {
  const h = hoja.value;
  if (!h) return;
  h.guardando = true;
  // Dónde estaban los iconos antes de cambiar el plan, para verlos ir a su día nuevo.
  const intercambio = h.modo === 'intercambiar' && h.destino.sesiones.length;
  const volar = calendarioCmp.value?.prepararVuelo([[h.origen.fecha, h.destino.fecha],
    ...(intercambio ? [[h.destino.fecha, h.origen.fecha]] : [])]);
  try {
    const r = await moverSesion(pedidoDeLaHoja());
    if (r?.plan) store.plan = r.plan;
    else await load();
    volar?.();
    await cargarEnElReloj();
    const { origen } = h;
    const destino = h.destino.fecha;
    moviendo.value = null;
    hoja.value = null;
    diaElegido.value = destino;
    // Deshacer = volver a moverla a su día; no se ofrece si su día ya pasó (no se puede volver ahí).
    const movida = sesionesDeLaFecha(destino).find((d) => !d.is_rest
      && (tituloDelDia(d) || etiquetaDeLaSesion(d)) === origen.titulo) || null;
    clearTimeout(temporizadorDeshacer);
    const corta = (t) => String(t || '').split(' (')[0];
    const texto = intercambio
      ? `Intercambiadas: ${corta(origen.titulo)} al ${diaCorto(destino)} y ${corta(h.destino.sesiones[0])} al ${diaCorto(origen.fecha)}.`
      : `${corta(origen.titulo)}: movida al ${diaCorto(destino)}.`;
    // En el teléfono, la versión corta: «Intercambiadas (mié 21 ⇄ jue 22)».
    const dc = (f) => { const d = new Date(`${f}T12:00:00`); return `${d.toLocaleDateString('es', { weekday: 'short' }).replace('.', '')} ${d.getDate()}`; };
    const corto = intercambio ? `${dc(origen.fecha)} ⇄ ${dc(destino)}` : `Movida al ${dc(destino)}`;
    deshacer.value = movida && !origen.pasado
      ? { clave: claveDeSesion(movida), fecha: origen.fecha, destino, modo: 'intercambiar', texto, corto } : null;
    if (deshacer.value) temporizadorDeshacer = setTimeout(() => { deshacer.value = null; }, 15000);
    store.showToast(`Movida al ${diaCorto(destino)}. Envíala a tu reloj cuando quieras.`);
  } catch (e) {
    h.guardando = false;
    h.error = e?.response?.data?.message || 'No se pudo guardar el cambio.';
    if (e?.response?.data?.code === 'stale') await load();
  }
};
const deshacerMovimiento = async () => {
  const d = deshacer.value;
  if (!d) return;
  deshacer.value = null;
  const volar = calendarioCmp.value?.prepararVuelo([[d.destino, d.fecha]]);
  try {
    const r = await moverSesion({ sessionKey: d.clave, toDate: d.fecha, modo: d.modo, planVersion: plan.value?.plan_version });
    if (r?.plan) store.plan = r.plan;
    else await load();
    volar?.();
    await cargarEnElReloj();
    diaElegido.value = d.fecha;
    store.showToast('Deshecho: la sesión vuelve a su día.');
  } catch (e) {
    await swal.fire({ icon: 'warning', title: 'No se deshizo', text: e?.response?.data?.message || 'No se pudo deshacer.' });
  }
};
// Escape cancela el modo mover.
const alPulsarTecla = (e) => { if (e.key === 'Escape' && moviendo.value && !hoja.value) cancelarMover(); };
window.addEventListener('keydown', alPulsarTecla);
onUnmounted(() => window.removeEventListener('keydown', alPulsarTecla));

// La semana vigente se acaba. El macrociclo ya avisaba de su final; la semana
// no avisaba de nada, y el atleta tenia que acordarse solo de volver a generar
// -- con 0 semanas cerradas de 37 generaciones en la beta, esa es la primera
// sospecha. Ver `avisoDeSemana`.
const ultimoDiaDelPlan = computed(() => {
  const fechas = weeks.value.flatMap(fechasDe).sort();
  return fechas.length ? fechas[fechas.length - 1] : '';
});
// Si ya tiene la semana en curso y la siguiente (el tope de ventana_de_generacion).
const tope = computed(() => (hasWeek.value ? topeDeSemanas(ultimoDiaDelPlan.value, hoyISO) : null));
// Si la siguiente semana todavía no se puede generar, desde cuándo (lo decía sólo al pulsar, 2026-10-22).
const proximaDisponible = computed(() => (hasWeek.value && !tope.value ? antelacionDeLaSemana(ultimoDiaDelPlan.value, hoyISO) : null));
// «Generar mi semana automáticamente» (Database/027; ver Backend AutoWeekService). La
// víspera de su siguiente semana, a las `hour` de su zona, se genera sin preguntas ni
// check-in y se sube a su reloj.
// El tope de generaciones (D2, Beta 3.0 F2): versiones que le quedan de su semana
// actual y desde cuándo otro macrociclo. Se dice antes de pulsar, nunca en dinero.
// null = el servidor no lo sabe (sin la migración 028) y no se enseña nada.
// Qué lleva el reloj de cada sesión (Beta 3.0, F5). Vacío = no se sabe y no se dice nada.
const enElReloj = ref({});
const RELOJ = {
  en_el_reloj: { texto: 'En tu reloj', clase: 'bg-[#caf300]/15 text-[#caf300]', ayuda: 'Tu reloj tiene esta misma versión.' },
  cambios_sin_enviar: { texto: 'Cambios sin enviar', clase: 'bg-[#ffd166]/15 text-[#ffd166]',
    ayuda: 'Tu reloj tiene otra versión o la tiene en otro día: vuelve a subir la semana.' },
  sin_subir: { texto: 'Sin enviar', clase: 'bg-white/10 text-[#8f9378]', ayuda: 'Esta sesión no está en tu reloj.' },
};
const cargarEnElReloj = async () => { enElReloj.value = await getGarminSessions(); };
// Las sesiones que vienen y el reloj no tiene igual (movidas o regeneradas).
// Lo que el reloj tiene distinto del plan, de hoy en adelante: lo cambiado, lo que sobra (un entreno suyo que
// quitó después de enviarlo) y lo que añadió él y nunca se subió (2026-10-23: sin esto «Enviar al reloj» no lo
// mandaba, porque una sesión «sin subir» no contaba como cambio).
const clavesAnadidas = computed(() => new Set(semanasActivas.value.flatMap((w) => (w.days || [])
  .filter((d) => d.edited?.added && !d.is_rest).map((d) => claveDeSesion(d)))));
const porEnviar = (k, v) => k.slice(0, 10) >= hoyISO
  && (v === 'cambios_sin_enviar' || v === 'sobra' || (v === 'sin_subir' && clavesAnadidas.value.has(k)));
const sinEnviar = computed(() => Object.entries(enElReloj.value).filter(([k, v]) => porEnviar(k, v)).length);
// Las fechas que hay que volver a enviar (Beta 3.0, F10). «Enviar al reloj» manda SÓLO éstas: subía la
// semana entera y, en el reloj real del atleta 2 (2026-10-11), cambiar una sesión recreaba las 7.
// Al mover, la entrada del registro ya viajó con la sesión a su día nuevo: subir ese día retira la vieja.
const fechasSinEnviar = computed(() => [...new Set(Object.entries(enElReloj.value)
  .filter(([k, v]) => porEnviar(k, v))
  .map(([k]) => k.slice(0, 10)))].sort());

// «martes 13», para la marca de una sesión movida.
const diaCorto = (iso) => {
  const f = new Date(`${iso}T12:00:00`);
  return `${f.toLocaleDateString('es', { weekday: 'long' })} ${f.getDate()}`;
};

// Las sesiones que se pueden mover o ajustar: las mismas condiciones que sus botones (de una
// semana activa, nunca el descanso ni la carrera).
const sesionesQueSePuedenCambiar = computed(() => semanasActivas.value
  .flatMap((w) => (w.days || []).filter(movibleDia).map((d) => ({ week: w, day: d })))
  .sort((a, b) => a.day.date.localeCompare(b.day.date)));
// Desde «¿Qué no encaja?»: qué sesión y qué hacer con ella.
const elegirSesionQueCambiar = async (editables) => {
  const opciones = Object.fromEntries(editables.map((e, i) => [
    String(i), `${diaCorto(e.day.date)} · ${tituloDelDia(e.day) || etiquetaDeLaSesion(e.day)}`,
  ]));
  const s = await swal.fire({
    title: '¿Qué sesión?', input: 'select', inputOptions: opciones, inputValue: '0',
    showCancelButton: true, confirmButtonText: 'Seguir', cancelButtonText: 'Cancelar',
  });
  if (!s.isConfirmed) return;
  const elegida = editables[Number(s.value)];
  if (!elegida) return;
  const ajustable = elegida.day.date >= hoyISO && SE_AJUSTAN.includes(elegida.day.sport)
    && elegida.day.garmin_exportable !== false;
  if (!ajustable) {
    empezarAMover(elegida.day);
    return;
  }
  const q = await swal.fire({
    title: '¿Qué quieres cambiar?',
    showDenyButton: true, showCancelButton: true,
    confirmButtonText: 'Moverla de día', denyButtonText: 'Ajustar sus pasos', cancelButtonText: 'Cancelar',
    // Ninguna de las dos borra nada: el rojo de «deny» se queda para lo que sí.
    denyButtonColor: '#3a4030',
  });
  if (q.isConfirmed) empezarAMover(elegida.day);
  else if (q.isDenied) abrirEditor(elegida.day);
};

// Ajustar los pasos de una sesión (Beta 3.0, F7), y la fuerza de esta semana (2026-10-23).
const SE_AJUSTAN = ['run', 'bike', 'swim', 'walk', 'strength'];
const editando = ref(null);
const abrirEditor = (day) => {
  editando.value = {
    sessionKey: claveDeSesion(day),
    titulo: tituloDelDia(day) || etiquetaDeLaSesion(day),
    subtitulo: `${diaCorto(day.date)} · ${etiquetaDeLaSesion(day)}`,
    fuerza: day.sport === 'strength',
  };
};

// Un entreno que añade el atleta (2026-10-23): hoy o un día que viene de una semana activa, nunca el día de
// una carrera y como mucho tres sesiones por día (lo mismo que comprueba el servidor).
const DEPORTES_PARA_ANADIR = { run: 'Carrera', bike: 'Bici', swim: 'Natación', walk: 'Caminata', strength: 'Fuerza' };
const puedeAnadir = (week, fecha) => {
  if (!fecha || fecha < hoyISO || !semanasActivas.value.some((w) => w === week)) return false;
  const delDia = (week.days || []).filter((d) => d.date === fecha && !d.is_rest);
  return delDia.length < 3 && !delDia.some((d) => d.intensity_class === 'race_day');
};
const anadirEntreno = async (fecha) => {
  const r = await swal.fire({
    title: `Añadir un entreno el ${diaCorto(fecha)}`,
    text: 'Es tuyo: lo decides tú, como salir a entrenar sin que lo haya planificado el coach.',
    input: 'select', inputOptions: DEPORTES_PARA_ANADIR, inputValue: 'run',
    showCancelButton: true, confirmButtonText: 'Seguir', cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed || !DEPORTES_PARA_ANADIR[r.value]) return;
  editando.value = {
    sessionKey: '', titulo: DEPORTES_PARA_ANADIR[r.value], subtitulo: `${diaCorto(fecha)} · lo añades tú`,
    fuerza: r.value === 'strength', nuevo: { fecha, deporte: r.value },
  };
};
const quitarEntreno = async (day) => {
  const c = await swal.fire({
    title: 'Quitar este entreno',
    text: `«${tituloDelDia(day) || etiquetaDeLaSesion(day)}» sale de tu plan. Si ya estaba en tu reloj, se retira al pulsar «Enviar al reloj».`,
    showCancelButton: true, confirmButtonText: 'Quitar', cancelButtonText: 'Cancelar', confirmButtonColor: '#b3261e',
  });
  if (!c.isConfirmed) return;
  try {
    const r = await editarSesion({ op: 'quitar', sessionKey: claveDeSesion(day), planVersion: plan.value?.plan_version });
    if (r?.plan) store.plan = r.plan;
    else await load();
    await cargarEnElReloj();
    store.showToast('Quitado de tu plan.');
  } catch (e) {
    await swal.fire({ icon: 'warning', title: 'No se pudo', text: e?.response?.data?.message || 'No se pudo quitar.' });
  }
};
const alGuardarElAjuste = async (r) => {
  editando.value = null;
  if (r?.plan) store.plan = r.plan;
  else await load();
  await cargarEnElReloj();
  const avisos = r?.preview?.avisos || [];
  store.showToast(avisos.length ? `Guardada. ${avisos[0]}` : 'Guardada. Envíala a tu reloj cuando quieras.');
};
const volverALaDelCoach = async (day) => {
  const c = await swal.fire({
    title: 'Volver a la del coach',
    text: 'La sesión vuelve a los pasos y textos que te escribió el coach. El día se queda donde está.',
    showCancelButton: true, confirmButtonText: 'Volver', cancelButtonText: 'Cancelar',
  });
  if (!c.isConfirmed) return;
  try {
    const r = await editarSesion({ op: 'revertir', sessionKey: claveDeSesion(day), planVersion: plan.value?.plan_version });
    if (r?.plan) store.plan = r.plan;
    else await load();
    await cargarEnElReloj();
    store.showToast('Vuelve a ser la del coach. Envíala a tu reloj cuando quieras.');
  } catch (e) {
    await swal.fire({ icon: 'warning', title: 'No se pudo', text: e?.response?.data?.message || 'No se pudo volver a la del coach.' });
  }
};

const cupo = ref(null);
const cargarCupo = async () => {
  try {
    const c = await getGenerationQuota();
    cupo.value = c?.available ? c : null;
  } catch (e) {
    cupo.value = null;
  }
};
const quedanDeLaSemana = computed(() => cupo.value?.week?.quedan ?? null);
const macroDisponibleDesde = computed(() => cupo.value?.season?.available_from || '');
const fechaDelCupo = (valor) => diaLargo(String(valor).slice(0, 10));

const auto = ref(null);
const autoGuardando = ref(false);
const autoError = ref('');
// "domingo 11 de octubre": el formato largo de es pone una coma tras el día de la semana.
const diaLargo = (iso) => {
  const f = new Date(`${iso}T12:00:00`);
  return `${f.toLocaleDateString('es', { weekday: 'long' })} ${f.toLocaleDateString('es', { day: 'numeric', month: 'long' })}`;
};
const zonaDelNavegador = () => {
  try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { return ''; }
};
const cargarAuto = async () => {
  try {
    auto.value = await getAutoWeek();
    // De viaje, la víspera es la de donde está: se actualiza la zona al abrir el plan.
    const tz = zonaDelNavegador();
    if (auto.value?.enabled && tz && auto.value.timezone !== tz) auto.value = await setAutoWeek(true);
  } catch (e) { /* sin la migración o sin servicio: el interruptor no se enseña */ }
};
const alternarAuto = async () => {
  if (!auto.value || autoGuardando.value) return;
  autoGuardando.value = true;
  autoError.value = '';
  try {
    auto.value = await setAutoWeek(!auto.value.enabled);
    store.showToast(auto.value.enabled ? 'Tu semana se escribirá sola.' : 'Semana automática apagada.');
  } catch (e) {
    autoError.value = e?.response?.data?.message || 'No se pudo cambiar. Inténtalo de nuevo.';
  } finally {
    autoGuardando.value = false;
  }
};
// La víspera a las 21:00, o -- si eso ya pasó y su semana todavía no ha terminado el primer día
// (AutoWeekService::cuandoToca, 'ahora') -- «en la próxima hora». Decía «el domingo 18 a las 21:00»
// el lunes 19 por la tarde (prueba del 2026-10-19), una hora que ya había pasado.
const autoCuando = computed(() => {
  const a = auto.value;
  if (!a?.enabled || !a?.launch_date) return '';
  const lanzamiento = new Date(`${a.launch_date}T${String(a.hour).padStart(2, '0')}:00:00`);
  const ahora = new Date();
  if (lanzamiento > ahora) return `el ${diaLargo(a.launch_date)} a las ${a.hour}:00`;
  const finDelPrimerDia = new Date(`${a.launch_date}T00:00:00`);
  finDelPrimerDia.setDate(finDelPrimerDia.getDate() + 2);
  return ahora < finDelPrimerDia ? 'en la próxima hora' : '';
});

const avisoSemana = computed(() => {
  const aviso = hasWeek.value && ultimoDiaDelPlan.value ? avisoDeSemana(ultimoDiaDelPlan.value) : null;
  // Con la semana automática, "genera la siguiente" no es lo que va a pasar: se genera sola.
  // Si su semana ya terminó sin plan, el aviso de siempre (eso se retoma a mano).
  if (aviso && aviso.tono !== 'vencido' && autoCuando.value) {
    return {
      ...aviso,
      titulo: 'Tu siguiente semana se generará sola',
      texto: `${autoCuando.value.charAt(0).toUpperCase()}${autoCuando.value.slice(1)} tu coach la escribe y la sube a tu reloj. Si prefieres generarla tú antes, puedes hacerlo.`,
    };
  }
  return aviso;
});
// Cuando cambia la semana escrita (generó, borró), cambia también la próxima víspera.
watch(ultimoDiaDelPlan, () => { if (auto.value) cargarAuto(); });

const hoyISO = new Date().toLocaleDateString('sv');   // YYYY-MM-DD local
const esHoy = (fecha) => fecha === hoyISO;

/**
 * Que dias estan desplegados. En movil arrancan CERRADOS salvo el de hoy, que
 * es el unico que el atleta viene a ver; en pantalla ancha no se pliega nada.
 */
const diasAbiertos = reactive({});
const alternarDia = (fecha, i) => {
  const clave = `${fecha}-${i}`;
  diasAbiertos[clave] = !diaAbierto(fecha, i);
};
const diaAbierto = (fecha, i) => {
  const clave = `${fecha}-${i}`;
  return clave in diasAbiertos ? diasAbiertos[clave] : true;
};

/** Cuantas sesiones tiene ese dia, contando solo las que son entrenamiento. */
const sesionesEse = (week, fecha) =>
  (week.days || []).filter((d) => d.date === fecha && !d.is_rest).length;

/** Cual de ellas es esta, en el orden en que vienen. */
const indiceDeSesion = (week, day, di) =>
  (week.days || []).slice(0, di + 1).filter((d) => d.date === day.date && !d.is_rest).length;


const fmtDate = (s) => {
  if (!s) return '—';
  try { return new Date(s.replace(' ', 'T')).toLocaleString('es', { dateStyle: 'medium', timeStyle: 'short' }); }
  catch (e) { return s; }
};

const load = () => store.loadPlan();

/**
 * Que un botón que falla lo diga.
 *
 * El fallo que costó esto no fue el error en sí: fue que no se veía. Un
 * `await` que revienta dentro de un manejador sin `try/catch` cierra el
 * diálogo, deja el rechazo en la consola y no cambia un solo píxel de la
 * pantalla. Para el atleta eso es «el botón no funciona», que es justo lo que
 * no se puede diagnosticar ni contar por teléfono.
 */
const avisarDelFallo = (e, porDefecto) => {
  console.error(porDefecto, e);
  swal.fire({
    icon: 'error',
    title: 'No se pudo completar',
    text: e?.response?.data?.message || e?.message || porDefecto,
  });
};

const stopPolling = () => { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } };

const poll = async () => {
  pollCount += 1;
  // ~45 min. Eran 25, y el AiService no deja volver a generar hasta 45: el
  // atleta veía "tardando demasiado", pulsaba otra vez y le decían que ya
  // tenía una en curso. El worker se corta solo a los 40 y lo dice (ver
  // TOPE_DE_LA_GENERACION_S en AiService/worker.py), así que esto casi nunca
  // debería llegar a verse.
  if (pollCount > 675) {
    stopPolling();
    generating.value = false;
    genError.value = 'La generación está tardando demasiado. Vuelve a intentarlo en unos minutos; si se repite, escríbenos desde Ayuda.';
    return;
  }
  try {
    const s = await getStatus();
    // El límite cuenta desde que la generación ARRANCA: el tiempo en cola no
    // es suyo. Ver 'queued' en PlanService::pollStatus.
    if (s.queued) pollCount = 0;
    progress.value = s.progress || 0;
    statusText.value = s.status_text || '';
    jobEnCurso = s.job_id || jobEnCurso;
    preguntasResueltas.value = !!s.questions_done;
    if (s.awaiting_answers && s.questions?.length && jobPreguntado.value !== s.job_id) {
      if (!preguntasCoach.value) respuestasCoach.value = s.questions.map(() => '');
      preguntasCoach.value = s.questions;
    } else if (!s.awaiting_answers) {
      preguntasCoach.value = null;
    }
    // Un error SIN `done` es el puente caido o sin responder: hasta aqui se
    // ignoraba y la pantalla decia "Generando..." durante 25 minutos.
    if (!s.done && !s.running && s.error) {
      if (!puedeReintentar()) genError.value = `No se pudo consultar la generación: ${s.error}`;
      return;
    }
    fallosSeguidos = 0;
    if (s.done) {
      stopPolling();
      generating.value = false;
      cargarCupo();
      cargarEnElReloj();
      if (s.error) {
        genError.value = `La generación falló: ${s.error}`;
      } else {
        if (s.plan) store.plan = s.plan; else await load();
        store.showToast('Plan generado con éxito.');
      }
    }
  } catch (e) {
    if (!puedeReintentar()) genError.value = 'Se perdió la conexión con el servicio de generación.';
  }
};

// Un sondeo fallido no es una generación fallida. El despliegue reinicia el
// AiService (`pkill` + serve-guard) y un solo fallo paraba el sondeo para
// siempre: las preguntas del coach desaparecían y la generación esperaba diez
// minutos a alguien que ya no las veía. Se aguantan ~1 minuto de fallos
// seguidos (15 sondeos de 4 s) antes de rendirse.
let fallosSeguidos = 0;
const puedeReintentar = () => {
  fallosSeguidos += 1;
  if (fallosSeguidos < 15) return true;
  fallosSeguidos = 0;
  stopPolling();
  generating.value = false;
  return false;
};

const enviarRespuestas = async (enBlanco) => {
  enviandoRespuestas.value = true;
  errorRespuestas.value = '';
  try {
    const respuestas = enBlanco ? preguntasCoach.value.map(() => '') : respuestasCoach.value.map((r) => r || '');
    await sendCoachAnswers(jobEnCurso, respuestas, preguntasCoach.value.map((q) => q.pregunta || ''));
    jobPreguntado.value = jobEnCurso;
    preguntasCoach.value = null;
  } catch (e) {
    errorRespuestas.value = e?.response?.data?.message || 'No se pudieron enviar tus respuestas. Inténtalo otra vez.';
  } finally {
    enviandoRespuestas.value = false;
  }
};

const startPolling = () => {
  generating.value = true;
  genError.value = '';
  progress.value = 5;
  statusText.value = 'Iniciando...';
  preguntasResueltas.value = false;
  pollCount = 0;
  fallosSeguidos = 0;
  stopPolling();
  pollTimer = setInterval(poll, 4000);
  poll();
};

// "Rechazar / Ajustar" se retiro en la v1.1. Hacia dos cosas y las dos estaban
// mal:
//
//   1. Rehacer una semana o una temporada NO es mas barato que generarla de
//      cero -- $0.25 contra $0.25 -- asi que no compraba nada.
//   2. Rehacer arrastra el plan que se rechaza. La generacion nueva recibe la
//      semana anterior (_format_previous_plan) y el macrociclo anterior
//      (services/ai/previous_season.py), asi que el modelo escribe la
//      correccion mirando justo aquello de lo que el atleta se queria alejar.
//      En la beta se vio en el gasto: 20 macrociclos para 5 atletas en cinco
//      dias, y el sesgo seguia ahi despues de cada intento.
//
// Queda "Eliminar plan". Borrar el macrociclo deja `season_plan_md` en NULL, y
// entonces el bloque de plan anterior sale VACIO por construccion: el analisis
// empieza limpio sin ninguna bandera nueva que mantener. El contexto se
// conserva solo cuando de verdad hay continuidad -- la semana siguiente de una
// temporada viva.

const avisarDelCupo = (mensaje, titulo = 'Ya no quedan versiones') => swal.fire({
  icon: 'info',
  title: titulo,
  text: mensaje,
  showCancelButton: !!store.abrirAyuda,
  confirmButtonText: 'Entendido',
  cancelButtonText: 'Pedir ayuda',
}).then((r) => {
  if (r.dismiss === 'cancel') store.abrirAyuda?.({ categoria: 'plan' });
});

const promptAndGenerate = async (kind, { redo = false, adjustment = '' } = {}) => {
  // El macrociclo, uno cada 7 días (D2): se dice antes de pedirle la contraseña.
  if (kind === 'season' && macroDisponibleDesde.value) {
    await avisarDelCupo(`Ya generaste un macrociclo esta semana. Podrás volver a generarlo desde el `
      + `${fechaDelCupo(macroDisponibleDesde.value)}.`, 'Todavía no toca');
    return;
  }
  // ¿Desde hoy o desde mañana? Sólo tiene sentido en la PRIMERA semana: a
  // partir de ahí la siguiente empieza donde acabó la anterior, y dejar elegir
  // abriría huecos o solapes en el calendario.
  //
  // La pregunta existe porque "hoy" no es la respuesta correcta a todas horas.
  // A las ocho de la tarde, escribirle entrenos para hoy es escribirle un día
  // que ya se le fue; a las once de la mañana, después de su sesión, puede ser
  // exactamente lo que quiere. Sólo el atleta sabe cuál es su caso.
  let startDate = '';
  if (kind === 'week' && !redo && !hasWeek.value) {
    const hoy = new Date();
    const manana = new Date(hoy.getTime() + 86400000);
    const dd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const dia = (d) => d.toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' });
    const q = await swal.fire({
      icon: 'question',
      title: '¿Desde cuándo?',
      text: 'Tu primera semana puede arrancar hoy mismo o mañana. Después, cada semana empezará donde acabe la anterior.',
      input: 'radio',
      inputOptions: {
        [dd(hoy)]: `Hoy — ${dia(hoy)}`,
        [dd(manana)]: `Mañana — ${dia(manana)}`,
      },
      inputValue: hoy.getHours() >= 17 ? dd(manana) : dd(hoy),
      showCancelButton: true,
      confirmButtonText: 'Continuar',
      cancelButtonText: 'Cancelar',
    });
    if (!q.isConfirmed || !q.value) return;
    startDate = q.value;
  }

  // El check-in va antes de la semana y sólo antes de la semana: el
  // macrociclo mira meses, no cómo dormiste anoche. Es opcional; cerrar con la
  // X cancela. Lo que el atleta escriba aquí llega al planificador como
  // molestia declarada y contexto de la semana, y de ahí salen las
  // adaptaciones de cada sesión.
  //
  // Va DESPUÉS del "¿desde cuándo?" porque enseña las fechas de la semana que
  // se va a generar. "Saltar" viaja hasta el servidor: esa semana no usa ningún
  // check-in, tampoco el que se contestó hace un rato para otra generación.
  //
  // Rehacer NO lo vuelve a preguntar: es la misma semana y el mismo día, y
  // el check-in que ya respondió sigue siendo el de hoy.
  let sinCheckin = false;
  // El muro de pago, lo PRIMERO: sin acceso, rellenar el check-in no lleva a nada (2026-10-20).
  if (acceso.value && acceso.value.puede_generar === false) {
    const r = await swal.fire({
      icon: 'info',
      title: acceso.value.estado === 'sin_empezar' ? 'Conecta tu Garmin'
        : (acceso.value.estado === 'garmin_usado' ? 'No tienes semanas gratis activas' : 'Tu acceso terminó'),
      text: acceso.value.estado === 'sin_empezar'
        ? `Conecta tu Garmin en tu perfil para empezar tus semanas gratis. Después, Power Coach AI cuesta ${acceso.value.precio.texto}.`
        : (acceso.value.estado === 'garmin_usado'
          ? `Si conectaste tu Garmin antes de que hubiera semanas gratis, pulsa «Volver a leer mi Garmin» en tu perfil y se activan. Si esa cuenta ya las usó, Power Coach AI cuesta ${acceso.value.precio.texto}.`
          : `Power Coach AI cuesta ${acceso.value.precio.texto}. Tu plan, tu reloj y el chat siguen; para generar semanas nuevas hace falta la suscripción.`),
      showCancelButton: true,
      confirmButtonText: acceso.value.estado === 'sin_empezar' ? 'Ir a mi perfil' : 'Quiero suscribirme',
      cancelButtonText: 'Ahora no',
    });
    if (r.isConfirmed) {
      if (acceso.value.estado === 'sin_empezar') router.push('/admin/perfil');
      else quieroSuscribirme();
    }
    return;
  }
  // El tope de dos semanas, ANTES del check-in: preguntarle cómo llega y contestarle después
  // "ya tienes las dos semanas" era hacerle rellenarlo para nada (auditoría del 2026-09-24).
  if (kind === 'week' && !redo && tope.value) {
    await swal.fire({
      icon: 'info',
      title: 'Ya tienes las dos semanas',
      html: `Tienes escrita tu semana en curso y la siguiente. La próxima empieza el ${fechaCorta(tope.value.siguiente)}`
        + (autoCuando.value ? ` y se generará sola ${autoCuando.value}.` : '; lo ideal es generarla la víspera.'),
      confirmButtonText: 'Entendido',
    });
    return;
  }
  // Y la ventana de antelación, también ANTES del check-in: quien prefiere esperar no tiene que
  // contestarlo para nada. Si decide generar igual, va forzado desde el principio.
  let forzar = false;
  const antes = kind === 'week' && !redo && hasWeek.value ? antelacionDeLaSemana(ultimoDiaDelPlan.value, hoyISO) : null;
  if (antes) {
    // Lo recomendado es el botón principal (revisión del 2026-10-22): destacar «Generar de todas formas» empujaba
    // al camino que el propio texto desaconseja.
    const vispera = new Date(`${antes.siguiente}T12:00:00`);
    vispera.setDate(vispera.getDate() - 1);
    const visperaIso = `${vispera.getFullYear()}-${String(vispera.getMonth() + 1).padStart(2, '0')}-${String(vispera.getDate()).padStart(2, '0')}`;
    const r = await swal.fire({
      icon: 'info',
      title: 'Mejor un poco más tarde',
      html: `Tu siguiente semana empieza el ${diaLargo(antes.siguiente)}. Lo ideal es generarla la víspera, el `
        + `${diaLargo(visperaIso)}: así tiene en cuenta cómo llegas. Puedes hacerlo desde el ${diaLargo(antes.desde)}.`,
      showCancelButton: true,
      confirmButtonText: `Esperar al ${fechaCorta(antes.desde)}`,
      cancelButtonText: 'Generar ahora',
    });
    if (r.isConfirmed || r.dismiss !== 'cancel') return;
    forzar = true;
  }
  if (kind === 'week' && !redo) {
    if (!store.profile) await store.loadProfile();
    const fechas = fechasDeLaProximaSemana(weeklyPlan.value, store.profile?.week_start_day, { arranque: startDate });
    const respuesta = await store.askCheckin(fechas);
    if (!respuesta) return;
    sinCheckin = respuesta === 'saltado';
  }

  const email = store.profile?.garmin_email || '';
  const titulo = adjustment
    ? (kind === 'season' ? 'Rehacer el plan completo' : 'Rehacer la semana')
    : (kind === 'season' ? 'Generar Plan de Temporada' : 'Generar Semana');
  // Regenerar el macrociclo lo REEMPLAZA, y la confirmación sólo decía que se
  // conectaría a Garmin: no había forma de saber qué pasaba con el plan actual.
  const reemplaza = kind === 'season' && hasSeason.value
    ? '<br><br>Tu macrociclo actual se reemplaza por uno nuevo, escrito con tus datos de hoy y desde donde vas. El anterior queda en Historial y tus semanas ya generadas no se tocan.'
    : '';
  const cuerpo = `Se conectará a Garmin Connect${email ? ` con <b>${email}</b>` : ''} para leer tu historial de entrenamiento.${reemplaza}<br><br>Tarda varios minutos.`;

  // Sin `icon` a proposito, aqui y en "Subir a Garmin": el circulo grande de
  // SweetAlert en una confirmacion rutinaria se leia como un aviso de error
  // (lo reporto el atleta). Los iconos se quedan donde hay algo que perder.
  // Con la contraseña guardada no se pide: es justo lo que se guardó para
  // evitar. Sin ella, se escribe aquí y no se almacena en ningún sitio.
  // Tras el check-in no se vuelve a preguntar (revisión del 2026-10-22): «Guardar y generar mi semana» ya era el sí,
  // y una tercera ventana seguida sobraba. Sin contraseña guardada sí: hay que escribirla.
  const vieneDelCheckin = kind === 'week' && !redo;
  const r = claveGuardada.value
    ? (vieneDelCheckin ? { isConfirmed: true } : await swal.fire({
        title: titulo, html: cuerpo,
        showCancelButton: true, confirmButtonText: 'Generar', cancelButtonText: 'Cancelar',
      }))
    : await swal.fire({
        title: titulo, html: cuerpo,
        input: 'password',
        inputPlaceholder: 'Contraseña de Garmin Connect',
        inputAttributes: { autocomplete: 'off' },
        showCancelButton: true, confirmButtonText: 'Generar', cancelButtonText: 'Cancelar',
      });
  if (!r.isConfirmed) return;
  const pw = textoDe(r);
  if (!claveGuardada.value && !pw) return;

  try {
    if (kind === 'season') await startSeason(pw, adjustment);
    else await startWeek(pw, redo, forzar, adjustment, startDate, sinCheckin);
    startPolling();
  } catch (e) {
    // El muro de pago (2026-10-20): se dice el precio y cómo seguir, sin perder nada de lo que tiene.
    if (e?.response?.data?.code === 'paywall') {
      cargarAcceso();
      const r = await swal.fire({
        icon: 'info', title: 'Tu acceso terminó',
        text: e.response.data.message || '',
        showCancelButton: true, confirmButtonText: 'Quiero suscribirme', cancelButtonText: 'Ahora no',
      });
      if (r.isConfirmed) quieroSuscribirme();
      return;
    }
    // El tope de versiones (D2): no se salta, se explica.
    if (e?.response?.data?.code === 'cupo') {
      await avisarDelCupo(e.response.data.message || 'Ya generaste todas las versiones de esta semana.');
      cargarCupo();
      return;
    }
    if (e?.response?.data?.code === 'busy') {
      genError.value = e.response.data.message || 'Ya hay una generación en curso. Espera a que termine.';
      return;
    }
    // 409 = la ventana de antelación: todavía no toca, pero es una decisión
    // del atleta, no un error. El plan se escribe con los datos de HOY, así
    // que generarlo con días de antelación lo construye sobre un retrato que
    // ya habrá cambiado; se le dice por qué y decide.
    if (e?.response?.status === 409 && kind === 'week') {
      const v = e.response.data?.data || {};
      // El tope de semanas por delante no se salta: sólo se explica.
      if (v.limit) {
        await swal.fire({
          icon: 'info',
          title: 'Ya tienes las dos semanas',
          html: `${e.response.data?.message || ''}`,
          confirmButtonText: 'Entendido',
        });
        return;
      }
      const r = await swal.fire({
        icon: 'warning',
        title: 'Todavía no toca',
        html: `${e.response.data?.message || ''}`,
        showCancelButton: true,
        confirmButtonText: 'Generar de todas formas',
        cancelButtonText: v.allowed_from ? `Esperar al ${fechaCorta(v.allowed_from)}` : 'Esperar',
      });
      if (!r.isConfirmed) return;
      try {
        await startWeek(pw, redo, true, adjustment, startDate, sinCheckin);
        startPolling();
      } catch (e2) {
        genError.value = e2?.response?.data?.message || 'No se pudo iniciar la generación.';
      }
      return;
    }
    genError.value = e?.response?.data?.message || 'No se pudo iniciar la generación.';
  }
};

const uploading = ref(false);
const uploadMsg = ref('');
const uploadError = ref(false);
let uploadTimer = null;

const pollUpload = async () => {
  try {
    const s = await getGarminUploadStatus();
    if (s.running) {
      uploadMsg.value = s.status || 'Subiendo a Garmin...';
      return;
    }
    clearInterval(uploadTimer);
    uploadTimer = null;
    uploading.value = false;
    if (s.error || s.status === 'failed') {
      uploadError.value = true;
      uploadMsg.value = `La subida falló: ${s.error || 'error desconocido'}`;
    } else {
      const r = s.result || {};
      uploadError.value = false;
      uploadMsg.value = `Subido a Garmin: ${r.uploaded ?? 0}/${r.total ?? 0} sesiones`
        + (r.replaced ? ` · ${r.replaced} reemplazadas` : '')
        + (r.pruned ? ` · ${r.pruned} retiradas` : '');
      store.showToast('Plan subido a tu Garmin.');
      cargarEnElReloj();
    }
  } catch (e) {
    clearInterval(uploadTimer);
    uploadTimer = null;
    uploading.value = false;
    uploadError.value = true;
    uploadMsg.value = 'Se perdió la conexión con el servicio de subida.';
  }
};

// `fechas`: sólo esas (lo que cambió); sin ellas, la semana entera, como «Subir a Garmin».
const handleGarminUpload = async (fechas = null) => {
  const soloCambios = Array.isArray(fechas) && fechas.length > 0;
  const email = store.profile?.garmin_email || '';
  const cuerpo = soloCambios
    ? `Se enviarán a Garmin Connect${email ? ` (${email})` : ''} sólo las sesiones que cambiaste (${fechas.length === 1 ? '1 día' : `${fechas.length} días`}). Las demás de tu reloj no se tocan.`
    : `Se subirán las sesiones de tu semana al calendario de Garmin Connect${email ? ` (${email})` : ''}.<br>Las que ya estuvieran se reemplazan, no se duplican.`;
  const r = claveGuardada.value
    ? await swal.fire({
        title: 'Subir a Garmin', html: cuerpo,
        showCancelButton: true, confirmButtonText: 'Subir', cancelButtonText: 'Cancelar',
      })
    : await swal.fire({
        title: 'Subir a Garmin', html: cuerpo,
        input: 'password',
        inputPlaceholder: 'Contraseña de Garmin Connect',
        showCancelButton: true, confirmButtonText: 'Subir', cancelButtonText: 'Cancelar',
      });
  if (!r.isConfirmed) return;
  const pw = textoDe(r);
  if (!claveGuardada.value && !pw) return;

  uploading.value = true;
  uploadMsg.value = 'Conectando con Garmin...';
  uploadError.value = false;
  try {
    await startGarminUpload(pw, soloCambios ? fechas : null);
    clearInterval(uploadTimer);
    uploadTimer = setInterval(pollUpload, 3000);
    pollUpload();
  } catch (e) {
    uploading.value = false;
    uploadError.value = true;
    uploadMsg.value = e?.response?.data?.message || 'No se pudo iniciar la subida.';
  }
};

// Borrar el plan aquí y dejarlo en el reloj es el desajuste que hay que
// evitar: la app dice que ese plan ya no existe y la muñeca sigue pitando para
// hacerlo. Por eso se pide la contraseña — es opcional, y saltarla deja las
// sesiones en Garmin, que es lo que pasaba siempre hasta ahora.
// Qué se borra se elige. Antes lo único posible era borrarlo TODO, así que
// deshacerse de siete días que no gustaron obligaba a regenerar el macrociclo
// entero — 24 semanas, la llamada más cara del producto, y con búsqueda web.
// Son dos decisiones de escalas distintas y se borran por separado.
const handleReset = async () => {
  const email = store.profile?.garmin_email || '';

  const q = await swal.fire({
    icon: 'question',
    title: '¿Qué quieres eliminar?',
    input: 'radio',
    inputOptions: {
      week: quedanDeLaSemana.value === null
        ? 'Eliminar esta semana — el macrociclo se queda y la vuelves a generar'
        : quedanDeLaSemana.value > 0
          ? `Eliminar esta semana — el macrociclo se queda y la vuelves a generar (te quedan ${quedanDeLaSemana.value} ${quedanDeLaSemana.value === 1 ? 'versión' : 'versiones'})`
          : 'Eliminar esta semana — ya usaste sus versiones: no podrás volver a generarla',
      season: 'Sólo el macrociclo — la semana se queda',
      all: 'Las dos cosas',
    },
    inputValue: 'week',
    showCancelButton: true,
    confirmButtonText: 'Continuar',
    cancelButtonText: 'Cancelar',
  });
  if (!q.isConfirmed || !q.value) return;
  const scope = q.value;

  // El macrociclo nunca estuvo en el reloj, así que ahí no hay nada que pedir.
  if (scope === 'season') {
    const c = await swal.fire({
      icon: 'warning',
      title: '¿Eliminar el macrociclo?',
      text: 'Se borra el plan de temporada y su informe de análisis. Tu semana actual, tu perfil y tu cuaderno no se tocan.',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
    });
    if (!c.isConfirmed) return;
    try {
      await resetPlan('', 'season');
    } catch (e) {
      return avisarDelFallo(e, 'No se pudo eliminar el macrociclo.');
    }
    store.plan = {};
    await load();
    store.showToast('Macrociclo eliminado.');
    return;
  }

  // «¿Qué no encaja?» (D2, Beta 3.0 F2): borrar la semana es el camino a otra versión
  // pagada, y muchas veces lo que no encaja se arregla sin escribirla de nuevo: moviendo o
  // ajustando una sesión (F6-F7, sin IA y sin gastar versiones) o pidiendo ayuda.
  if (scope === 'week' && store.abrirAyuda) {
    const quedan = quedanDeLaSemana.value;
    const editables = sesionesQueSePuedenCambiar.value;
    let cambiarUna = false;
    const n = await swal.fire({
      icon: 'question',
      title: '¿Qué no encaja?',
      html: (editables.length
        ? 'Si es una sesión, puedes <b>moverla de día o ajustar sus pasos</b> sin generar la semana otra vez.'
          + '<br><button type="button" id="cambiar-una-sesion" class="swal2-styled" '
          + 'style="margin-top:12px;background:#caf300;color:#11140b;font-weight:800">Mover o ajustar una sesión</button>'
          + '<br><br>Si es otra cosa, cuéntanos qué no te cuadra y lo revisamos contigo, sin borrar nada.'
        : 'Cuéntanos qué no te cuadra de esta semana y lo revisamos contigo, sin borrar nada.')
        + (quedan === null ? '' : quedan > 0
          ? `<br><br>Si prefieres generarla otra vez, te quedan <b>${quedan} ${quedan === 1 ? 'versión' : 'versiones'}</b> de esta semana.`
          : '<br><br><b>Ya usaste las versiones de esta semana:</b> si la eliminas, no podrás volver a generarla.'),
      didOpen: () => {
        document.getElementById('cambiar-una-sesion')?.addEventListener('click', () => {
          cambiarUna = true;
          swal.close();
        });
      },
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: 'Pedir ayuda',
      denyButtonText: quedan === 0 ? 'Eliminarla igual' : 'Eliminar y generar otra',
      cancelButtonText: 'Cancelar',
    });
    if (cambiarUna) {
      await elegirSesionQueCambiar(editables);
      return;
    }
    if (n.isConfirmed) {
      store.abrirAyuda({ categoria: 'plan' });
      return;
    }
    if (!n.isDenied) return;
  }

  const queSeVa = scope === 'week'
    ? 'Se borra la semana generada. Tu macrociclo, tu perfil y tu cuaderno no se tocan.'
    : 'Se borra el macrociclo, la semana generada y tu historial de planes. Tu perfil y tu cuaderno no se tocan.';
  const r = await swal.fire({
    icon: 'warning',
    title: '¿Seguro?',
    html: claveGuardada.value
      ? `${queSeVa}<br><br>Se quitarán también del reloj las sesiones que subió esta app. <b>Tus actividades ya entrenadas no se tocan.</b>`
      : `${queSeVa}<br><br>Escribe tu contraseña de Garmin${email ? ` (${email})` : ''} y quitamos también `
        + 'del reloj las sesiones que subió esta app. <b>Tus actividades ya entrenadas no se tocan.</b>'
        + '<br><small>Si lo dejas en blanco, se borra aquí y las sesiones se quedan en tu Garmin.</small>',
    ...(claveGuardada.value ? {} : {
      input: 'password',
      inputPlaceholder: 'Contraseña de Garmin (opcional)',
    }),
    showCancelButton: true,
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;

  // `retirarDeGarmin` es explícito y no se deduce de si la contraseña viene
  // vacía: con una guardada, vacía significa "usa la mía"; sin ella, significa
  // "no toques mi Garmin". Son dos cosas distintas y confundirlas borraría (o
  // dejaría) entrenos del reloj sin que el atleta lo hubiera pedido.
  const clave = textoDe(r);
  const retirarDeGarmin = claveGuardada.value || !!clave;

  // POR QUÉ lo borra. La pregunta más valiosa del plan Beta 1.1: hubo 37
  // generaciones pagadas para 5 atletas -- Hugo solo hizo 15 -- y ni una fila
  // en la base de datos que dijera por qué se repetían. Cada respuesta lleva a
  // un arreglo distinto: los días no son los suyos (rejilla), el volumen no
  // cuadra (prescripción), las sesiones no convencen (calidad del output).
  //
  // Va DESPUÉS de confirmar y se puede saltar: el plan ya se va a borrar,
  // conteste o no. Una encuesta que bloquea es una encuesta que se odia.
  //
  // Y un cuadro para escribir, opcional. El motivo cerrado suma; el texto
  // explica -- "las sesiones no me convencen" no dice cuales ni por que.
  // Va a athlete_feedback (Database/020), entero, no recortado a 191
  // caracteres dentro de un evento como iba la nota antes.
  const radios = Object.entries(MOTIVOS_DE_BORRADO).map(([valor, etiqueta]) => `
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;text-align:left;margin:4px 0">
      <input type="radio" name="motivo-borrado" value="${valor}"> ${etiqueta}
    </label>`).join('');
  const motivo = await swal.fire({
    icon: 'question',
    title: '¿Por qué lo borras?',
    html: `<p style="font-size:13px;margin-bottom:10px">Una respuesta rápida y nos ayuda a que el siguiente te salga bien.</p>
      <div style="display:inline-block;text-align:left">${radios}</div>
      <textarea id="motivo-texto" rows="3" maxlength="4000" placeholder="Cuéntanos más, si quieres (opcional)"
        style="width:100%;box-sizing:border-box;margin-top:10px;border-radius:14px;padding:10px 12px;font-size:14px;
               background:#1b1d22;color:#f1f0ee;border:1px solid rgba(255,255,255,.18);resize:vertical"></textarea>`,
    showCancelButton: true,
    confirmButtonText: 'Enviar y eliminar',
    cancelButtonText: 'Prefiero no decir',
    focusConfirm: false,
    preConfirm: () => ({
      razon: document.querySelector('input[name="motivo-borrado"]:checked')?.value || '',
      nota: (document.getElementById('motivo-texto')?.value || '').trim(),
    }),
  });
  const razon = motivo.isConfirmed ? (motivo.value?.razon || '') : '';
  const nota = motivo.isConfirmed ? (motivo.value?.nota || '') : '';

  let res;
  try {
    res = await resetPlan(clave, scope, retirarDeGarmin, razon);
  } catch (e) {
    return avisarDelFallo(e, 'No se pudo eliminar el plan.');
  }
  if (nota) {
    // Despues del borrado y sin bloquearlo: si no llega, el plan ya se borro
    // igual, que es lo que el atleta pidio.
    enviarFeedback({
      message: nota, reason: razon, context: scope === 'week' ? 'borrar_semana' : 'borrar_plan',
    });
  }
  store.plan = {};
  await load();
  cargarCupo();
  cargarEnElReloj();

  // Qué pasó con el reloj, dicho en voz alta. Un borrado a medias en silencio
  // es peor que uno que falla de frente.
  const que = scope === 'week' ? 'Semana eliminada' : 'Plan eliminado';
  const g = res?.data?.garmin || {};
  if (g.retirados) {
    store.showToast(`${que} · ${g.retirados} sesión(es) retiradas de tu Garmin.`);
  } else if (g.pendientes) {
    store.showToast(`${que}, pero ${g.pendientes} sesión(es) siguen en tu Garmin.`);
  } else {
    store.showToast(`${que}.`);
  }
};

onMounted(async () => {
  if (!store.planLoaded) await load();
  cargarAuto();
  cargarCupo();
  cargarEnElReloj();
  // Los ✓/✗ de lo que ya hizo, de su Garmin (sin esperar: tarda unos segundos la primera vez).
  if (hasWeek.value) store.cargarEstadoSesiones();
  // Lo que levantó en el gimnasio: se lee una vez al abrir el plan y se enseña al
  // lado de cada ejercicio ("la última vez: 60 kg × 8").
  cargarRegistroDeFuerza();
  // Resume the progress view if a generation is still running.
  try {
    const s = await getStatus();
    if (s.running) {
      startPolling();
    } else if (s.done && s.plan && !s.error) {
      // El atleta cerro la pestaña mientras se generaba: el backend acaba de
      // GUARDAR ese resultado al consultarlo, pero la pantalla seguia
      // enseñando el plan viejo y el atleta volvia a generar (pagando otra
      // vez). Auditoria previa al merge.
      store.plan = s.plan;
    }
  } catch (e) { /* bridge not reachable — ignore */ }
});
onUnmounted(() => { stopPolling(); if (uploadTimer) clearInterval(uploadTimer); });

// Lo que la frase de una serie dice que NO dicen ya sus pasos ("" si nada).
// El codificador guarda "4x(20'' alta cadencia en Z2)" en el bloque Y reparte
// su trabajo y su recuperacion en los pasos de dentro, asi que pintar las dos
// cosas decia la misma serie dos veces seguidas. Espejo de `repeat_extra` en
// PaginaLocal/services/ai/rendering/printable_renderer.py (la hoja impresa).
const REPETICION = /^\s*\d+\s*[x×]\s*\(?\s*|\s*\)?\s*$/gi;
const plano = (t) => String(t || '').replace(/''/g, '"').replace(/”/g, '"').toLowerCase().replace(/\s+/g, ' ').trim();
const extraDeSerie = (step) => {
  const cabecera = plano(String(step.description || '').replace(REPETICION, ''));
  if (!cabecera) return '';
  const repetida = (step.substeps || []).some((s) => {
    const t = plano(String(s.description || '').replace(REPETICION, ''));
    return t && (cabecera.includes(t) || t.includes(cabecera));
  });
  return repetida ? '' : step.description;
};

// Recursive workout-steps renderer (repeat blocks nest via substeps).
const PlanSteps = {
  name: 'PlanSteps',
  props: { steps: Array, depth: { type: Number, default: 0 } },
  setup(props) {
    return () => h('ul', { class: `space-y-1.5 text-xs text-[#f1f0ee] ${props.depth ? 'pl-4 mt-1 border-l border-white/10' : 'my-2'}` },
      (props.steps || []).map((step, i) => {
        if (step.substeps && step.substeps.length) {
          const extra = extraDeSerie(step);
          return h('li', { key: i, class: 'space-y-1' }, [
            h('span', { class: 'inline-flex items-baseline gap-2' }, [
              h('span', { class: 'px-1.5 py-0.5 rounded-md bg-[#caf300] text-[#121700] text-[10px] font-black font-mono-data' },
                `${step.repeat_count || ''}×`),
              h('span', { class: 'font-bold text-[#caf300]' }, extra || 'Repite:'),
            ]),
            h(PlanSteps, { steps: step.substeps, depth: props.depth + 1 }),
          ]);
        }
        return h('li', { key: i, class: 'flex items-start gap-2' }, [
          h('span', { class: 'w-1.5 h-1.5 rounded-full bg-[#8f9378] mt-1.5 shrink-0' }),
          h('span', {}, step.description),
        ]);
      }),
    );
  },
};
</script>
