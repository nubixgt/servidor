<template>
  <!-- pb-28: el botón flotante del coach tapaba el final de cada pestaña. -->
  <div id="profile-view-container" class="space-y-6 sm:space-y-7 pb-28 select-none animate-fade-in-up">
    <!-- Más compacta en el teléfono, y en el alta sin el porcentaje: ya hay «Paso 1 de 3» y dos barras de
         progreso a la vez eran dos maneras de contar lo mismo (revisión del 2026-10-21). -->
    <header class="flex flex-row justify-between items-start sm:items-center gap-3 pb-4 sm:pb-5 border-b border-white/10">
      <div class="min-w-0">
        <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight">
          {{ guiado ? 'Tu perfil' : 'Perfil del Atleta' }}
        </h2>
        <div v-if="!guiado" class="flex items-center gap-3 mt-2">
          <!-- «Lo básico»: el porcentaje no cuenta Avanzado, que es opcional, y «Completado 100 %» con
               Avanzado sin abrir no cuadraba (revisión del 2026-10-21). -->
          <span class="text-xs font-bold text-[#caf300] uppercase tracking-wider whitespace-nowrap">
            Lo básico: {{ completion }}%
          </span>
          <div class="w-20 sm:w-40 h-2 bg-white/10 rounded-full overflow-hidden p-0.5 shrink-0">
            <div
              class="h-full rounded-full transition-all"
              :class="completion >= MINIMO_PERFIL
                ? 'bg-[#caf300] shadow-[0_0_10px_#caf300]'
                : 'bg-[#ffb4ab] shadow-[0_0_10px_#ffb4ab]'"
              :style="{ width: `${completion}%` }"
            ></div>
          </div>
        </div>

        <!-- QUE falta, no solo cuanto. En la beta los cinco atletas se quedaron
             entre el 95% y el 96% -- a cada uno le faltaba UN campo -- y ninguno
             sabia cual. Cada campo es un boton que lleva a su pestaña. -->
        <div v-if="camposQueFaltan.length && !guiado" class="mt-2.5 flex flex-wrap items-center gap-1.5">
          <span class="text-[11px] font-black uppercase tracking-wider text-[#8f9378]">Te falta:</span>
          <button
            v-for="c in camposQueFaltan"
            :key="c.campo"
            type="button"
            @click="irACampo(c)"
            class="px-2.5 py-1 rounded-lg text-[11px] font-bold glass-pill text-[#c5c9ac] hover:text-[#caf300] transition-colors"
          >
            {{ c.etiqueta }}
          </button>
        </div>
      </div>

      <!-- Header Actions -->
      <!-- Lima sólo con algo que guardar; sin cambios, «Guardado» y apagado: siempre lima no decía nada. -->
      <div v-if="!guiado" class="flex items-center gap-3 shrink-0">
        <button
          id="btn-save-profile"
          @click="handleSave"
          :disabled="store.isProfileSaving || !hayCambios"
          class="flex items-center justify-center gap-1.5 w-[8.25rem] sm:w-auto px-4 sm:px-6 py-2.5 min-h-[44px] rounded-xl text-xs font-black uppercase tracking-wider transition-transform active:scale-95"
          :class="hayCambios
            ? 'bg-[#caf300] hover:bg-[#bbf400] text-[#121700] shadow-[0_0_20px_rgba(202,243,0,0.35)]'
            : 'border border-white/15 text-white/60 cursor-default'"
        >
          <span class="material-symbols-outlined text-[18px]">
            {{ store.isProfileSaving ? 'sync' : (hayCambios ? 'save' : 'cloud_done') }}
          </span>
          <span>{{ store.isProfileSaving ? 'Guardando…' : (hayCambios ? 'Guardar' : 'Guardado') }}</span>
        </button>
      </div>
    </header>

    <!-- Save feedback toast -->
    <div
      v-if="saveToast"
      role="status"
      class="sticky top-3 z-30 p-4 glass-panel border border-[#caf300]/60 text-[#caf300] rounded-2xl text-sm font-bold flex items-center gap-3 shadow-2xl"
    >
      <span class="material-symbols-outlined text-xl">check_circle</span>
      <span>Perfil guardado.</span>
    </div>

    <!-- Bienvenida: sólo la primera vez, y sólo mientras dura -->
    <section
      v-if="guiado"
      class="glass-card rounded-3xl p-6 sm:p-7 border border-[#caf300]/30 shadow-2xl space-y-2"
    >
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-[#caf300] text-2xl">waving_hand</span>
        <h3 class="text-lg font-black text-white">
          Bienvenido{{ store.authUser?.name ? `, ${String(store.authUser.name).split(' ')[0]}` : '' }}
        </h3>
      </div>
      <p v-if="pasoActual === 0" class="text-sm text-white/80 leading-relaxed">
        Vamos paso a paso. Empezamos conectando tu Garmin —de ahí sale casi todo solo— y luego
        repasamos {{ tabs.length }} apartados cortos. Puedes saltarte cualquiera, pero cuantos menos
        datos tenga tu entrenador, más tendrá que suponer.
      </p>
      <p class="text-[12px] font-bold text-white/75">
        Paso {{ pasoActual + 1 }} de {{ tabs.length }} · {{ tabs[pasoActual].label }}
      </p>
      <div class="w-full h-1.5 bg-white/10 rounded-full overflow-hidden mt-1">
        <div
          class="h-full bg-[#caf300] rounded-full transition-all duration-500"
          :style="{ width: `${((pasoActual + 1) / tabs.length) * 100}%` }"
        ></div>
      </div>
    </section>

    <!-- Tabs: lo BASICO delante, lo avanzado detras de un boton.
         ==================================================================
         Eran ocho pestañas seguidas y todas pesaban igual. El atleta llegaba
         al alta y se encontraba fisiologia de laboratorio, entorno, estilo de
         vida y una ficha medica completa antes de poder generar nada -- y la
         ULTIMA, la octava, era Salud: la ultima impresion del registro era un
         cuestionario clinico. De ahi salio "el sistema de inicio debe ser mas
         intuitivo, preguntar menos cosas que solo hacen ruido".

         Lo basico es lo que un entrenador necesita para escribir la primera
         semana. Lo demas mejora el plan pero no lo bloquea, y vive detras de
         "Opciones avanzadas".

         Envuelve en vez de desplazarse de lado: en un teléfono de 375 px la
         barra se cortaba en "HISTOR" y Avanzado no se veía ni estando dentro
         (auditoría del 2026-09-23). -->
    <!-- En el teléfono, una rejilla de 2×2: envolviendo, la tercera pestaña se quedaba sola en una segunda fila. -->
    <!-- En el alta no hay pestañas: el paso a paso las recorre en orden (con las dos, eran dos navegaciones). -->
    <nav v-if="!guiado" class="grid grid-cols-2 sm:flex sm:flex-wrap items-stretch sm:items-center gap-2 pb-1">
      <button
        v-for="(tab, i) in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="px-3 sm:px-4 py-3 sm:py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider flex items-center justify-center gap-1.5"
        :class="activeTab === tab.id
          ? 'bg-[#caf300] text-[#121700] shadow-[0_0_20px_rgba(202,243,0,0.35)] scale-[1.02]'
          : 'bg-[#1d2025] border border-white/10 text-white/90 hover:text-white'"
      >
        <span v-if="guiado && i < pasoActual" class="material-symbols-outlined text-[14px]">check</span>
        <span>{{ tab.label }}</span>
      </button>

      <span v-if="!guiado" class="hidden sm:block w-px h-6 bg-white/15 shrink-0 mx-1"></span>

      <button
        v-if="!guiado"
        @click="activeTab = 'avanzado'"
        class="px-3 sm:px-4 py-3 sm:py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider flex items-center justify-center gap-1.5"
        :class="activeTab === 'avanzado'
          ? 'bg-[#caf300] text-[#121700] shadow-[0_0_20px_rgba(202,243,0,0.35)] scale-[1.02]'
          : 'bg-[#1d2025] border border-white/10 text-white/90 hover:text-white'"
      >
        <!-- En el teléfono sin icono: con él, «· opcional» quedaba cortado contra el borde. -->
        <span class="material-symbols-outlined text-[14px] hidden sm:inline">tune</span>
        <span class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-tight">
          <span>Avanzado</span>
          <span class="normal-case tracking-normal font-bold text-[11px] opacity-80"><span class="hidden sm:inline">· </span>opcional</span>
        </span>
      </button>
    </nav>

    <!-- ============ Tab 1: Identidad ============ -->
    <div v-if="activeTab === 'identidad'" class="grid grid-cols-1 lg:grid-cols-12 gap-7">
      <section class="lg:col-span-8 glass-card rounded-3xl p-6 sm:p-8 space-y-6 border border-white/10">
        <h3 class="text-xl font-black text-white tracking-tight border-b border-white/10 pb-4 flex items-center gap-2.5">
          <span class="material-symbols-outlined text-[#caf300]">badge</span>
          <span>Tus datos y tu Garmin</span>
        </h3>

        <div class="space-y-5">
          <div>
            <label :class="labelClass">Tu nombre</label>
            <input type="text" v-model="formData.name" :class="inputClass" placeholder="Ej. Juan Pérez" />
          </div>

          <div>
            <label :class="labelClass">Tu deporte principal</label>
            <select v-model="formData.sports_preference" :class="inputClass">
              <option value="triathlon" :class="optClass">Triatlón</option>
              <option value="run" :class="optClass">Solo Correr</option>
              <option value="bike" :class="optClass">Solo Ciclismo</option>
              <option value="swim" :class="optClass">Solo Natación</option>
            </select>
          </div>

          <div>
            <label :class="labelClass">Correo de tu cuenta Garmin</label>
            <input type="email" v-model="formData.garmin_email" :class="[inputClass, 'font-mono-data']" placeholder="tu_email@garmin.com" />
          </div>

          <div>
            <label :class="labelClass">Contraseña de Garmin</label>
            <!-- Con el ojo para verla: en el teléfono se escribe a ciegas y un error se nota sólo al conectar. -->
            <div class="relative">
              <input
                :type="verClave ? 'text' : 'password'"
                v-model="formData.garmin_password"
                :class="inputClass" class="pr-12"
                autocomplete="current-password"
                :placeholder="claveGuardada ? 'Guardada' : 'Tu contraseña de Garmin'"
              />
              <button v-if="formData.garmin_password" type="button" @click="verClave = !verClave" :aria-label="verClave ? 'Ocultar contraseña' : 'Ver contraseña'"
                      class="absolute right-1 top-1/2 -translate-y-1/2 w-11 h-11 flex items-center justify-center text-white/70 hover:text-white">
                <span class="material-symbols-outlined text-[20px]">{{ verClave ? 'visibility_off' : 'visibility' }}</span>
              </button>
            </div>
            <p v-if="claveGuardada" class="text-[12px] text-white/70 mt-1.5">Escribe aquí sólo si quieres cambiarla.</p>

            <!-- Guardarla es opcional y se dice qué protege y qué no. Una
                 promesa de seguridad más grande de la real es peor que no
                 hacerla. -->
            <!-- Lo que hace falta saber, en una frase; el detalle técnico sigue entero y a un toque. Siete
                 líneas de 11 px en gris eran lo menos legible de la primera pantalla (revisión del 2026-10-21). -->
            <label v-if="!claveGuardada || formData.garmin_password" class="flex items-start gap-3 mt-3 cursor-pointer min-h-[44px]">
              <input type="checkbox" v-model="recordarClave" class="mt-0.5 accent-[#caf300] w-5 h-5 shrink-0" />
              <span class="text-[13px] text-white/80 leading-snug">
                <span class="font-black text-white">Recordar mi contraseña</span>
                — se guarda cifrada. Así tu semana se sube sola a tu reloj y ves qué entrenos hiciste sin
                volver a escribirla.
              </span>
            </label>
            <details class="group mt-1 ml-8">
              <summary class="cursor-pointer list-none inline-flex items-center gap-1 text-[13px] font-bold text-white/80 hover:text-[#caf300] min-h-[44px]">
                <span class="material-symbols-outlined text-[16px] transition-transform group-open:rotate-90">chevron_right</span>
                Cómo la guardamos
              </summary>
              <p class="text-[12px] text-white/70 leading-relaxed mt-1">
                Se cifra en el servidor de IA con una clave que la base de datos no tiene, así que
                no aparece legible en una copia de la base. No la protege de alguien con acceso
                completo al servidor. Puedes borrarla cuando quieras. Si tu cuenta de Garmin pide
                verificación en dos pasos, todavía no podemos conectarla.
              </p>
            </details>

            <div v-if="claveGuardada" class="flex flex-wrap items-center gap-x-3 gap-y-2 mt-2">
              <span class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[#caf300] text-[16px]">lock</span>
                <span class="text-[13px] font-bold text-[#caf300]">Garmin conectado · contraseña guardada</span>
              </span>
              <button
                type="button"
                @click="olvidarClave"
                class="px-3 py-2.5 min-h-[44px] rounded-lg border border-[#ffb4ab]/40 text-[12px] font-bold text-[#ffb4ab] hover:bg-[#ffb4ab]/10"
              >
                Dejar de recordarla
              </button>
            </div>
            <p v-if="claveMsg" class="text-[11px] font-bold mt-2" :class="claveError ? 'text-[#ffb4ab]' : 'text-[#caf300]'">
              {{ claveMsg }}
            </p>
          </div>

          <div class="pt-2">
            <button
              type="button"
              @click="handleGarminImport"
              :disabled="garminImporting || !formData.garmin_email || (!formData.garmin_password && !claveGuardada)"
              class="w-full flex items-center justify-center gap-2.5 py-3.5 rounded-xl text-xs font-black tracking-wider uppercase transition-all active:scale-[0.98] disabled:bg-white/10 disabled:text-white/60 disabled:shadow-none disabled:border-transparent disabled:cursor-not-allowed"
              :class="garminYaConectado
                ? 'border border-white/25 text-white hover:border-white/50'
                : 'bg-[#caf300] hover:bg-[#bbf400] text-[#121700] shadow-[0_0_20px_rgba(202,243,0,0.35)]'"
            >
              <!-- Ya conectado deja de ser la acción principal: en lima seguía pareciendo que faltaba algo. -->
              <span class="material-symbols-outlined text-[22px]">{{ garminImporting ? 'sync' : (garminYaConectado ? 'refresh' : 'watch') }}</span>
              <span>{{ garminImporting ? 'Conectando con Garmin…' : (garminYaConectado ? 'Volver a leer mi Garmin' : 'Conectar mi Garmin') }}</span>
            </button>
            <!-- Desactivado con su motivo: sin él, el botón apagado parecía roto. -->
            <p v-if="!garminImporting && (!formData.garmin_email || (!formData.garmin_password && !claveGuardada))"
               class="text-[12px] text-white/70 mt-2">
              Escribe tu correo y tu contraseña de Garmin para conectar.
            </p>
            <p v-else-if="!garminMsg && !garminYaConectado" class="text-[12px] text-white/70 mt-2">
              Trae tu pulso, tus zonas, tu VO₂max y tus últimas semanas de entreno (tarda unos segundos).
            </p>
            <p v-if="garminMsg" class="text-[12px] font-bold mt-2" :class="garminError ? 'text-[#ffb4ab]' : 'text-[#caf300]'">
              {{ garminMsg }}
            </p>
            <!-- PUERTA FALSA · otros_relojes. Humango sincroniza con Polar,
                 Suunto, Coros y Apple Watch; nosotros solo con Garmin. -->
            <div class="mt-3">
              <PuertaFalsa
                puerta="otros_relojes"
                etiqueta="Uso otro reloj"
                icono="watch"
                titulo="Todavía no existe"
                texto="Hoy sólo conectamos con Garmin. ¿Qué reloj usas? Si llega a haber otro, te avisamos."
                :opciones="[
                  { valor: 'coros', etiqueta: 'Coros' },
                  { valor: 'polar', etiqueta: 'Polar' },
                  { valor: 'suunto', etiqueta: 'Suunto' },
                  { valor: 'apple', etiqueta: 'Apple Watch' },
                ]"
                clase="flex items-center gap-1.5 px-3 py-2.5 rounded-xl border border-white/15 text-[13px] font-bold text-white/80 hover:text-[#caf300] hover:border-[#caf300]/40 transition-colors"
              />
            </div>
          </div>
        </div>
      </section>

      <!-- Right Card: Telemetría Rápida -->
      <section v-if="store.athlete.garminConnected || !guiado" class="lg:col-span-4 space-y-5">
        <div class="glass-card rounded-3xl p-6 sm:p-7 space-y-4 border border-white/10 shadow-2xl">
          <h4 class="text-xs font-black text-[#caf300] uppercase tracking-widest flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">ecg_heart</span>
            <span>Lo que dice tu reloj</span>
          </h4>

          <!-- En palabras del atleta, con una línea de qué es; sin dato se dice, no «— W». -->
          <dl class="divide-y divide-white/10">
            <div v-for="d in datosDelReloj" :key="d.etiqueta" class="flex justify-between items-start gap-3 py-2.5">
              <dt class="min-w-0">
                <span class="block text-[13px] font-bold text-white">{{ d.etiqueta }}</span>
                <span class="block text-[11px] text-white/60 leading-snug">{{ d.ayuda }}</span>
              </dt>
              <dd class="shrink-0 text-right">
                <span v-if="d.valor" class="font-mono-data font-black text-sm" :class="d.color">{{ d.valor }}
                  <span class="text-[11px] font-bold text-white/60">{{ d.unidad }}</span></span>
                <span v-else class="text-[12px] text-white/50">aún sin dato</span>
              </dd>
            </div>
          </dl>
        </div>

        <div class="glass-card rounded-3xl p-5 sm:p-6 flex items-center gap-3.5 border border-white/10">
          <!-- Lo que HAY guardado, no sólo si importó en esta visita: con correo y contraseña
               guardados decía "Conecta con tu correo y contraseña" (auditoría del 2026-09-24). -->
          <div class="w-3.5 h-3.5 rounded-full" :class="(garminConnected || claveGuardada) ? 'bg-[#caf300] shadow-[0_0_10px_#caf300]' : 'bg-[#8f9378]'"></div>
          <div>
            <p class="text-xs font-black text-white uppercase tracking-wider">Garmin Connect</p>
            <p class="text-[11px] text-[#8f9378] font-bold mt-0.5">
              {{ garminConnected ? 'Datos importados desde Garmin'
                : claveGuardada ? 'Conectado: tu coach lee tus datos al generar y sube tus sesiones al reloj'
                : formData.garmin_email ? 'Escribe tu contraseña al generar, o guárdala arriba para no tener que hacerlo'
                : 'Conecta con tu correo y contraseña de Garmin arriba' }}
            </p>
          </div>
        </div>
      </section>
    </div>

    <!-- ============ Tab 2: Objetivos ============ -->
    <div v-if="activeTab === 'objetivos'" class="space-y-6">
      <section :class="cardClass">
        <h3 :class="sectionHeadClass">
          <span class="material-symbols-outlined text-[#caf300]">flag</span>
          <span>Tus carreras</span>
        </h3>

        <!-- Una fila por carrera, por fecha, con las ya corridas aparte (revisión del 2026-10-21): abiertas y
             en el orden en que se escribieron eran ~2.400 px en el teléfono, y una carrera de septiembre salía
             entre las que vienen como «Competición 1». -->
        <div class="space-y-2.5">
          <p v-if="carrerasQueVienen.length" class="text-[12px] text-white/70">
            <b class="text-white">A</b> la principal · <b class="text-white">B</b> importante · <b class="text-white">C</b> de preparación
          </p>
          <p v-if="!carrerasQueVienen.length" class="text-sm text-white/70">
            Todavía no tienes ninguna carrera por delante. Añádela y tu plan se construye hacia ella.
          </p>
          <FilaPlegable
            v-for="comp in carrerasQueVienen" :key="claveDeCarrera(comp)"
            :titulo="tituloDeCarrera(comp)" :detalle="detalleDeCarrera(comp)"
            :abierta="carrerasAbiertas.has(comp)" @update:abierta="alternarCarrera(comp, $event)"
            texto-quitar="Quitar esta carrera" @quitar="quitarCarrera(comp)"
          >
            <template #insignia>
              <span class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shrink-0"
                    :class="claseDePrioridad(comp.priority)">{{ comp.priority || 'B' }}</span>
            </template>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label :class="labelClass">Nombre</label>
                <input type="text" v-model="comp.name" :class="inputClass" placeholder="Ej. Ironman 70.3 Marbella" />
              </div>
              <div>
                <label :class="labelClass">Fecha</label>
                <input type="date" v-model="comp.date" :class="inputClass" />
              </div>
              <div class="md:col-span-2">
                <label :class="labelClass">Qué tan importante es</label>
                <div role="radiogroup" aria-label="Prioridad de la carrera" class="grid grid-cols-3 gap-2">
                  <button v-for="pr in PRIORIDADES" :key="pr.valor" type="button" role="radio"
                          :aria-checked="String(comp.priority === pr.valor)" @click="comp.priority = pr.valor"
                          class="rounded-xl border px-2 py-2.5 text-left transition-colors min-h-[44px] min-w-0"
                          :class="comp.priority === pr.valor ? 'border-[#caf300] bg-[#caf300]/10' : 'border-white/15 hover:border-white/30'">
                    <span class="block text-[13px] font-black leading-tight" :class="comp.priority === pr.valor ? 'text-[#caf300]' : 'text-white'">{{ pr.valor }}<span class="block text-[12px]">{{ pr.titulo }}</span></span>
                    <span class="block text-[11px] text-white/70 leading-snug mt-0.5">{{ pr.texto }}</span>
                  </button>
                </div>
              </div>
              <div>
                <label :class="labelClass">Distancia</label>
                <select v-model="comp.distance" :class="inputClass">
                  <option value="" :class="optClass">Otra / Sin especificar</option>
                  <!-- Sale del catalogo del cerebro (race_distances.py), no de
                       una lista escrita aqui. Ver utils/profile.js. -->
                  <optgroup v-for="g in gruposDeCarrera" :key="g.group" :label="g.group">
                    <option v-for="o in g.options" :key="o.value" :value="o.value" :class="optClass">
                      {{ o.label }}
                    </option>
                  </optgroup>
                </select>
              </div>
              <!-- Sólo donde el desnivel cambia la carrera (montaña, bici, «otra»), o si ya tiene uno. -->
              <div v-if="pideDesnivel(comp)">
                <label :class="labelClass">Desnivel positivo (m)<span :class="marcaOpcional">opcional</span></label>
                <input type="number" min="0" inputmode="numeric" v-model="comp.elevation_gain_m" :class="inputClass" placeholder="Ej. 600" />
              </div>
              <div>
                <label :class="labelClass">Tiempo objetivo<span :class="marcaOpcional">opcional</span></label>
                <!-- Tres campos y no un texto: "3:30" para una maratón se leía
                     como 3 min 30 s (atleta Fernando, 2026-09-13). -->
                <TiempoHMS v-model="comp.target_time" etiqueta="Tiempo objetivo" :clase-input="inputClass" />
              </div>
            </div>
          </FilaPlegable>

          <button type="button" @click="addCompetition" :class="addBtnClass">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Añadir una carrera</span>
          </button>
        </div>

        <!-- Las ya corridas siguen en el perfil: el cerebro las usa para la semana de después. -->
        <div v-if="carrerasCorridas.length" class="space-y-2.5">
          <button type="button" @click="verCorridas = !verCorridas" :aria-expanded="String(verCorridas)"
                  class="flex items-center gap-1.5 text-[13px] font-bold text-white/80 hover:text-white min-h-[44px]">
            <span class="material-symbols-outlined text-[18px] transition-transform" :class="verCorridas ? 'rotate-90' : ''">chevron_right</span>
            Ya corridas ({{ carrerasCorridas.length }})
          </button>
          <template v-if="verCorridas">
            <FilaPlegable
              v-for="comp in carrerasCorridas" :key="claveDeCarrera(comp)"
              :titulo="tituloDeCarrera(comp)" :detalle="detalleDeCarrera(comp)"
              :abierta="carrerasAbiertas.has(comp)" @update:abierta="alternarCarrera(comp, $event)"
              texto-quitar="Quitar esta carrera" @quitar="quitarCarrera(comp)"
            >
              <template #insignia>
                <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-white/5 text-white/60">
                  <span class="material-symbols-outlined text-[18px]">done</span>
                </span>
              </template>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label :class="labelClass">Nombre</label>
                  <input type="text" v-model="comp.name" :class="inputClass" />
                </div>
                <div>
                  <label :class="labelClass">Fecha</label>
                  <input type="date" v-model="comp.date" :class="inputClass" />
                </div>
              </div>
              <p class="text-[12px] text-white/70">Si quieres guardar tu tiempo, añádelo en Mi experiencia → Marcas personales.</p>
            </FilaPlegable>
          </template>
        </div>

        <!-- La meta principal sale del MISMO catalogo de 38 que las
             competiciones. Era un `input` de texto libre, y por eso el perfil
             de un triatleta podia decir "10k" -- lo que dejaba
             `triathlon_distance` sin poner y el prompt diciendo, en una linea
             marcada CRITICAL, "training for a None Triathlon". Verificado
             contra la cuenta real el 2026-09-10.
             Va DESPUÉS de las carreras y dice cuándo no cuadra con la A: arriba y sin más se leía como pedir
             dos veces lo mismo, y nada impedía que se contradijeran (revisión del 2026-10-21). -->
        <div v-if="distanciaDeLaA" class="border-t border-white/10 pt-4 flex items-start gap-2.5">
          <span class="material-symbols-outlined text-[#caf300] text-[20px] mt-px">flag</span>
          <p class="text-[13px] text-white/80 leading-snug">
            Tu plan se construye hacia tu carrera A: <b class="text-white">{{ distanciaDeLaA.etiqueta }}</b>.
            <span class="block text-[12px] text-white/60">Si cambias tu carrera A, cambia con ella.</span>
          </p>
        </div>
        <div v-else class="border-t border-white/10 pt-5">
          <label :class="labelClass">Tu distancia principal</label>
          <select v-model="formData.target_distance" :class="inputClass">
            <option value="" :class="optClass">Sin especificar</option>
            <optgroup v-for="g in gruposDeCarrera" :key="g.group" :label="g.group">
              <option v-for="o in g.options" :key="o.value" :value="o.value" :class="optClass">
                {{ o.label }}
              </option>
            </optgroup>
            <!-- Lo que el atleta escribio antes y no casa con el catalogo se
                 conserva como su propia opcion: nadie pierde lo que puso, ni
                 aunque el catalogo no cargue. -->
            <option v-if="metaFueraDelCatalogo" :value="formData.target_distance" :class="optClass">
              {{ formData.target_distance }} (escrito a mano)
            </option>
          </select>
          <p class="text-[12px] text-white/70 mt-1.5 leading-relaxed">
            La distancia hacia la que entrenas. Si añades una carrera A, se toma de ella.
            <template v-if="esMetaDeTriatlon">Con ella tu entrenador sabe los tramos exactos y reparte nado, bici y carrera en esa proporción.</template>
          </p>
        </div>
      </section>

      <!-- Disponibilidad: QUE quiere entrenar cada dia, no a que hora.
           ==================================================================
           Aqui habia una rejilla de franjas de reloj: por dia, hasta tres
           bloques con hora de inicio, hora de fin y los deportes que ese bloque
           permitia. Hasta 21 campos de hora en un telefono.

           Dos problemas, y el segundo es el grave:

           1. Ninguna app de referencia pregunta horas de reloj. Garmin Coach y
              Runna preguntan cuantos dias por semana y cuales.
           2. Una franja sólo sabe decir "PUEDO". No habia ningun sitio donde el
              atleta dijera "QUIERO". El prompt del planificador lo dice con
              todas las letras -- "un bloque es un permiso, no una obligacion" --
              asi que cuando un atleta queria nadar el martes y no le salia
              natacion, no era un fallo del modelo: nadie se lo habia pedido.

           Los datos viejos no se pierden: `day_time_blocks` se sigue guardando
           tal cual y de ahi se deriva esta rejilla la primera vez (todo como
           "posible", que es lo que un bloque significaba). Ver utils/profile.js. -->
      <section :class="cardClass">
        <h3 :class="sectionHeadClass">
          <span class="material-symbols-outlined text-[#caf300]">calendar_month</span>
          <span>Tus días de entrenamiento</span>
        </h3>
        <p class="text-sm text-white/80 leading-relaxed">
          Marca qué quieres entrenar cada día. <span class="text-white font-bold">Cuánto tiempo y a qué
          intensidad lo decide tu entrenador</span> según tu carga y tu fase.
        </p>

        <!-- Se marca TOCANDO (decisión del responsable, 2026-10-21, tras probar el «pincel»): un toque «Puedo», dos
             «Sí o sí», tres vuelve a descanso. Elegir antes qué marcar y luego tocar era un paso más. La leyenda va
             arriba y con las MISMAS casillas que la tabla: el estado del medio no se adivina de un color. -->
        <div class="space-y-3">
          <!-- El ciclo, como secuencia: «3 toques = descanso» no se entendía con la casilla vacía ya siendo descanso
               (revisión del 2026-10-22). -->
          <p class="text-[13px] text-white/85">Toca una casilla para cambiarla; cada toque pasa a la siguiente:</p>
          <ol class="flex flex-wrap items-center gap-x-2 gap-y-2 text-[13px] text-white/85" aria-label="Qué hace cada toque">
            <li v-for="(pz, i) in LEYENDA" :key="pz.titulo" class="flex items-center gap-2">
              <span v-if="i" class="material-symbols-outlined text-[18px] text-white/50" aria-hidden="true">arrow_forward</span>
              <span class="w-7 h-7 rounded-md border flex items-center justify-center shrink-0" :class="pz.clase" aria-hidden="true">
                <span v-if="pz.icono" class="material-symbols-outlined text-[15px]">{{ pz.icono }}</span>
              </span>
              <span><b class="text-white">{{ pz.titulo }}</b><span class="text-white/65"> ({{ pz.texto }})</span></span>
            </li>
          </ol>

          <!-- Teléfono: el deporte ENCIMA de su fila y los siete días a todo el ancho (en una columna a la izquierda
               las casillas medían 33 px). Pantalla ancha: una tabla de verdad, deporte a la izquierda y los días con
               su cabecera, casillas de 52×48 (antes salía todo pegado a la izquierda, a tamaño de teléfono). -->
          <div role="grid" aria-label="Qué quieres entrenar cada día" class="space-y-3 sm:space-y-2">
            <div role="row" class="hidden sm:grid sm:grid-cols-[9rem_repeat(7,minmax(3.25rem,1fr))] sm:max-w-3xl sm:gap-2" aria-hidden="true">
              <span></span>
              <span v-for="day in weekDays" :key="day" class="text-center text-[12px] font-bold text-white/70">{{ day.slice(0, 3) }}</span>
            </div>
            <div v-for="dep in gridSports" :key="dep" role="row"
                 class="sm:grid sm:grid-cols-[9rem_repeat(7,minmax(3.25rem,1fr))] sm:max-w-3xl sm:gap-2 sm:items-center">
              <p role="rowheader" class="flex items-center gap-1.5 text-[13px] sm:text-sm font-bold text-white mb-1.5 sm:mb-0">
                <span class="material-symbols-outlined text-[18px] text-white/80">{{ ICONO_DEL_DEPORTE[dep] }}</span>{{ dep }}
              </p>
              <div class="grid grid-cols-7 gap-1 sm:contents">
                <button
                  v-for="day in weekDays" :key="day"
                  type="button" role="gridcell"
                  @click="rotarCasilla(dep, day)"
                  :title="tituloCasilla(dep, day) + (pocoHecho(dep, day) ? ` · lo hiciste ${pocoHecho(dep, day).veces} de ${pocoHecho(dep, day).semanas} semanas` : '')"
                  :aria-label="dep + ' el ' + day.toLowerCase() + ': ' + tituloCasilla(dep, day) + (pocoHecho(dep, day) ? `, y lo hiciste ${pocoHecho(dep, day).veces} de las últimas ${pocoHecho(dep, day).semanas} semanas` : '') + '. Toca para cambiar.'"
                  class="relative h-11 sm:h-12 w-full rounded-lg border transition-transform active:scale-90 flex flex-col items-center justify-center leading-none"
                  :class="claseCasilla(dep, day)"
                >
                  <span v-if="pocoHecho(dep, day)" class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-[#ffd166] ring-2 ring-[#0d0f12]" aria-hidden="true"></span>
                  <span v-if="estadoCasilla(dep, day)" class="material-symbols-outlined text-[15px] sm:text-[18px]">
                    {{ estadoCasilla(dep, day) === 'fijo' ? 'push_pin' : 'check' }}
                  </span>
                  <span class="text-[11px] font-bold sm:hidden" :class="estadoCasilla(dep, day) ? 'mt-0.5' : 'text-white/75'">{{ inicialDelDia(day) }}</span>
                  <span v-if="estadoCasilla(dep, day)" class="hidden sm:block text-[11px] font-bold mt-0.5">{{ estadoCasilla(dep, day) === 'fijo' ? 'Sí o sí' : 'Puedo' }}</span>
                </button>
              </div>
            </div>
          </div>
          <p class="text-[12px] text-white/70 leading-relaxed">
            <b class="text-white">Puedo</b>: tu coach decide si ese día lo usa. <b class="text-white">Sí o sí</b>: ese
            día lo entrenas. Un día sin nada es descanso. Si dudas, marca «Puedo».
            Tus carreras cuentan aunque su día no esté marcado.
          </p>
          <!-- Sus «sí o sí» que casi no hace, leídos de su Garmin al generar la semana (2026-10-23, sin IA). -->
          <div v-if="fijosQueCasiNoHace.length" class="rounded-xl border border-[#ffd166]/40 bg-[#ffd166]/5 px-3 py-2.5 space-y-2 sm:max-w-3xl">
            <p class="text-[13px] text-white/90 leading-relaxed">
              <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#ffd166] mr-1 align-middle" aria-hidden="true"></span>
              Marcaste <b class="text-white">«Sí o sí»</b> y en tu Garmin casi no aparece:
              <b class="text-white">{{ fijosQueCasiNoHace.map(fraseDelFijo).join(', ') }}</b>.
              Lo que va «Sí o sí» tu plan lo pone siempre ese día. Si no suele encajarte, «Puedo» deja que tu coach lo
              ponga cuando convenga.
            </p>
            <button type="button" @click="pasarAPuedo"
                    class="px-3 py-2 min-h-[40px] rounded-lg border border-[#ffd166]/60 text-[12px] font-black uppercase tracking-wider text-[#ffd166] hover:bg-[#ffd166]/10">
              Pasar {{ fijosQueCasiNoHace.length === 1 ? 'esa' : 'esas' }} a «Puedo»
            </button>
          </div>
        </div>

        <!-- Los mínimos, fuera de la tabla (en una columna de 52 px no caben) y como sugerencia. -->
        <ul v-if="avisosMinimos.length" class="space-y-1 text-[12px] text-[#ffd166]">
          <li v-for="a in avisosMinimos" :key="a" class="flex items-start gap-1.5">
            <span class="material-symbols-outlined text-[15px] mt-px">lightbulb</span><span>{{ a }}</span>
          </li>
          <li class="text-white/70 pl-5">Es una recomendación, no un límite: si quieres correr dos días, el plan se hace con eso.</li>
        </ul>

        <!-- Un aviso, nunca un bloqueo: la app no sabe mas que el atleta sobre
             su vida. Solo pone el numero al lado de lo que ha pedido. Informativo, no en el rojo de un
             error: no hay nada mal hecho (revisión del 2026-10-21). -->
        <p v-if="avisoDeCarga" class="text-[13px] text-white/85 rounded-2xl p-3.5 border border-white/15 bg-white/[0.03] leading-relaxed">
          <span class="material-symbols-outlined text-[16px] align-middle text-[#a5eeff]">info</span>
          {{ avisoDeCarga }}
        </p>

        <!-- Aqui hubo un campo "Horas a la semana (como maximo)", y ya no lo
             hay: no es una pregunta que el atleta pueda contestar bien. Sabe
             cuantas horas cree que le sobran, no cuantas necesita su plan, y
             su respuesta llegaba al prompt como un TECHO. Cuanto entrena lo
             decide el entrenador, con la carga cronica medida en Garmin, la
             fase del macrociclo y el margen de progresion. -->

        <!-- CON QUE cuenta para esos dias. Estaban en "Equipo y Entorno",
             detras de Avanzado, y cuentan para el 100%; su sitio logico es
             este: sin piscina no se puede recetar natacion por mucho que
             marque Nadar el martes, y una bici de rodillo cambia la sesion
             entera. Debajo de la tabla y con su título: delante de ella la
             separaban 400 px de su leyenda (revisión del 2026-10-21). -->
        <div class="border-t border-white/10 pt-5 space-y-4">
          <h4 class="text-base font-black text-white flex items-center gap-2">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">location_on</span>Dónde entrenas
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Piscina, sólo a quien nada (revisión del 2026-10-22: se lo preguntaba a una corredora). -->
            <div v-if="nadaAlgunDia">
              <label :class="labelClass">¿Cuándo tienes piscina?</label>
              <select v-model="formData.pool_access" :class="inputClass">
                <option value="" :class="optClass">— Elige —</option>
                <option value="ninguno" :class="optClass">No tengo piscina</option>
                <option value="limitado" :class="optClass">Algunos días u horarios</option>
                <option value="completo" :class="optClass">Cuando quiera</option>
              </select>
            </div>
            <!-- 8x100m son cuatro largos en una piscina de 25 m y dos en una de
                 50, y hasta la v1.1 el sistema no lo sabia: el atleta los contaba
                 de cabeza dentro del agua. Ver Database/014. -->
            <div v-if="nadaAlgunDia && formData.pool_access !== 'ninguno'">
              <label :class="labelClass">Largo de la piscina<span :class="marcaOpcional">opcional</span></label>
              <select v-model="formData.pool_length_m" :class="inputClass">
                <option value="" :class="optClass">— No lo sé —</option>
                <option :value="25" :class="optClass">25 m</option>
                <option :value="50" :class="optClass">50 m</option>
                <option :value="33" :class="optClass">33 m</option>
                <option :value="20" :class="optClass">20 m</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">Bici</label>
              <select v-model="formData.bike_setup" :class="inputClass">
                <option value="" :class="optClass">— Elige —</option>
                <option value="outdoor" :class="optClass">Sólo en la calle</option>
                <option value="rodillo" :class="optClass">Sólo en rodillo</option>
                <option value="ambos" :class="optClass">En la calle y en rodillo</option>
              </select>
            </div>
          </div>
          <!-- PUERTA FALSA · aguas_abiertas. Existe como META (ow_1500...) pero
               no como entreno: open_water_swimming se colapsa a `swim` al leer de
               Garmin y no hay disciplina propia. Junto a la piscina, que es donde
               el atleta piensa en ello. -->
          <PuertaFalsa
            v-if="nadaAlgunDia && formData.pool_access !== 'ninguno'"
            puerta="aguas_abiertas"
            etiqueta="Quiero entrenar en aguas abiertas"
            icono="waves"
            titulo="Todavía no existe"
            texto="Hoy tus sesiones de natación son de piscina. Estamos pensando en añadir <b>sesiones de aguas abiertas</b> (orientación, salidas, nadar en grupo). ¿Te interesaría?"
            clase="self-start flex items-center gap-1.5 px-3 py-2.5 rounded-xl border border-white/15 text-[13px] font-bold text-white/80 hover:text-[#caf300] hover:border-[#caf300]/40 transition-colors"
          />

        </div>
      </section>

      <!-- Beta 2.1 (Database/025): cómo entrena la fuerza. Junto a la tabla,
           porque decide qué lleva un día de Fuerza que acaba de marcar. -->
      <PerfilDeFuerza
        :perfil="formData.strength_profile"
        :rutina="formData.strength_routine"
        v-model:texto="formData.strength_routine_texto"
        :guardar-rutina="guardarRutinaConfirmada"
        :dias-de-fuerza="diasPorDeporte(formData.sportDays, 'Fuerza')"
      />
    </div>

    <!-- ============ Tab 3: Historial ============ -->
    <div v-if="activeTab === 'historial'" class="space-y-6">
      <section :class="cardClass">
        <h3 :class="sectionHeadClass">
          <span class="material-symbols-outlined text-[#caf300]">history</span>
          <span>Tu experiencia</span>
        </h3>
        <!-- El volumen semanal NO se pregunta: lo MIDE Garmin.
             ==============================================================
             Era un campo de texto que el atleta rellenaba a ojo, y su numero
             competia dentro del prompt con las actividades reales. Un atleta
             declaraba 5 h/semana cuando sus ultimas doce semanas de Garmin
             daban 6,6 -- y el planificador tenia que decidir a cual creerle.

             `get_weekly_training_volume()` lo saca de las ultimas 12 semanas
             en UNA sola llamada y sin ningun modelo, y la importacion lo
             escribe. Preguntarselo ademas era pedirle que adivinara un dato
             que ya teniamos medido. Se ENSEÑA, con su procedencia, y sin
             aspecto de campo: parecía que se podía escribir (revisión del 2026-10-21). -->
        <div class="flex items-start gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-3.5">
          <span class="material-symbols-outlined text-[#caf300] text-[22px] mt-0.5">watch</span>
          <div class="min-w-0">
            <p class="text-[13px] font-bold text-white">Cuánto entrenas</p>
            <p v-if="horasMediasGarmin" class="text-sm text-white/80">
              <span class="font-mono-data font-bold text-white">{{ num(horasMediasGarmin) }} h por semana</span>
              <span class="text-white/60 text-[12px]"> · {{ origenDelVolumen }}</span>
            </p>
            <p v-else class="text-sm text-white/70">Lo medimos de tu Garmin al conectarlo.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
          <div>
            <label :class="labelClass">Tu nivel</label>
            <select v-model="formData.training_age_level" :class="inputClass">
              <option value="" :class="optClass">— Elige —</option>
              <option value="principiante" :class="optClass">Principiante</option>
              <option value="intermedio" :class="optClass">Intermedio</option>
              <option value="avanzado" :class="optClass">Avanzado</option>
              <option value="elite" :class="optClass">Élite</option>
            </select>
            <p v-if="definicionDelNivel" class="text-[12px] text-white/70 mt-1.5 leading-snug">{{ definicionDelNivel }}</p>
          </div>
          <div>
            <label :class="labelClass">Años entrenando</label>
            <input type="number" min="0" step="0.5" inputmode="decimal" v-model="formData.years_training" :class="inputClass" placeholder="Ej. 3" />
            <p class="text-[12px] text-white/70 mt-1.5 leading-snug">Con regularidad, en cualquier deporte.</p>
          </div>
          <div>
            <label :class="labelClass">Tu sesión más larga reciente<span :class="marcaOpcional">opcional</span></label>
            <!-- En horas y minutos, como los tiempos de las marcas: «(horas)» con decimales no se sabía si era 1,5 o
                 1:30 (revisión del 2026-10-22). Se guarda igual que antes, en horas. -->
            <div class="grid grid-cols-[minmax(0,5rem)_auto_minmax(0,5rem)_auto] items-center gap-2 max-w-[15rem]">
              <input type="number" min="0" max="24" inputmode="numeric" v-model="largaHoras" :class="inputClass" class="!px-2 text-center" placeholder="1" aria-label="Horas" />
              <span class="text-[12px] text-white/70">h</span>
              <input type="number" min="0" max="59" step="5" inputmode="numeric" v-model="largaMinutos" :class="inputClass" class="!px-2 text-center" placeholder="30" aria-label="Minutos" />
              <span class="text-[12px] text-white/70">min</span>
            </div>
            <p class="text-[12px] text-white/70 mt-1.5 leading-snug">En cualquier deporte, de los últimos dos meses.</p>
          </div>
        </div>
      </section>

      <!-- Marcas: una LISTA abierta, no seis campos fijos.
           ==================================================================
           Eran 5K, 10K, media, maraton, FTP y 1500m: cuatro de asfalto, una
           de bici y una de piscina. Un triatleta con un olimpico hecho o un
           nadador con una 5K de aguas abiertas no tenia donde ponerlo -- y
           esas son justo las marcas que dicen de que es capaz.

           El formato sale del mismo catalogo de 38 que las competiciones
           objetivo (public/carreras.json), con la puerta abierta a escribir
           uno que no este. Lo ya escrito se traduce al leer, sin tocar la
           base de datos: ver `leerMarcas` en utils/profile.js.
           Cada marca es una fila que se abre, como las carreras (revisión del 2026-10-21). -->
      <section :class="cardClass">
        <h3 :class="sectionHeadClass">
          <span class="material-symbols-outlined text-[#caf300]">emoji_events</span>
          <span>Marcas personales</span>
          <span class="ml-auto text-[12px] font-bold text-white/60">{{ cuantasMarcas }}</span>
        </h3>
        <p class="text-sm text-white/80">Las que quieras, del deporte que sea: le dicen a tu entrenador de qué eres capaz.</p>

        <div class="space-y-2.5">
          <FilaPlegable
            v-for="m in formData.recent_prs.marks" :key="claveDeCarrera(m)"
            :titulo="tituloDeMarca(m)" :detalle="detalleDeMarca(m)"
            :abierta="marcasAbiertas.has(m)" @update:abierta="(v) => (v ? marcasAbiertas.add(m) : marcasAbiertas.delete(m))"
            texto-quitar="Quitar esta marca" @quitar="quitarMarca(m)"
          >
            <template #insignia>
              <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 bg-[#caf300]/10 text-[#caf300]">
                <span class="material-symbols-outlined text-[18px]">{{ iconoDeMarca(m) }}</span>
              </span>
            </template>
            <div>
              <label :class="labelClass">Prueba</label>
              <select
                v-if="!m.libre"
                :value="m.key"
                @change="elegirFormato(m, $event.target.value)"
                :class="inputClass"
              >
                <option value="" :class="optClass">— Elige la prueba —</option>
                <optgroup v-for="g in gruposDeCarrera" :key="g.group" :label="g.group">
                  <option v-for="o in g.options" :key="o.value" :value="o.value" :class="optClass">
                    {{ o.label }}
                  </option>
                </optgroup>
                <option value="__otra__" :class="optClass">Otra (la escribo yo)</option>
              </select>
              <input
                v-else
                type="text"
                v-model="m.label"
                :class="inputClass"
                placeholder="Ej. Vertical de Acatenango · FTP test 20 min"
              />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label :class="labelClass">Tu tiempo</label>
                <TiempoHMS v-model="m.time" etiqueta="Marca" :clase-input="inputClass" />
              </div>
              <div>
                <label :class="labelClass">Cuándo<span :class="marcaOpcional">opcional</span></label>
                <MesAnio v-model="m.date" etiqueta="Cuándo" :clase-input="inputClass" />
              </div>
            </div>
          </FilaPlegable>

          <button type="button" @click="anadirMarca" :class="addBtnClass">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Añadir una marca</span>
          </button>
        </div>
        <div>
          <label :class="labelClass">Otras marcas o notas<span :class="marcaOpcional">opcional</span></label>
          <textarea rows="2" v-model="formData.recent_prs.notes" :class="inputClass" placeholder="Cualquier otra marca que no encaje arriba"></textarea>
        </div>
      </section>
    </div>

    <!-- ============ Tab 4: Fisiología ============ -->
    <div v-if="activeTab === 'avanzado'" class="space-y-6">
      <!-- Qué es esta pestaña, en una línea (revisión del 2026-10-21). Antes abría con tres tarjetas de umbral que
           repetían lo de Zonas, con un «—» que parecía un dato que faltaba. -->
      <p class="text-sm text-white/80 leading-relaxed">
        Todo es opcional. Sirve para afinar tus ritmos y tus zonas; si no sabes un dato, déjalo vacío: tu
        entrenador usa lo que mide tu Garmin.
        <span class="block text-[13px] text-white/75 mt-1"><span class="material-symbols-outlined text-[16px] align-middle text-[#a5eeff]">watch</span>
          = dato que trajo tu Garmin. Puedes corregirlo.</span>
      </p>

      <SeccionPlegable icono="monitoring" titulo="Fisiología"
        descripcion="Peso, altura, pulso en reposo y VO₂max. Casi todo llega de tu Garmin."
        :resumen="resumenAvanzado.fisiologia" v-model:abierta="plegables.fisiologia">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelClass">Peso (kg)<span v-if="esDeGarmin('weight_kg')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" step="0.1" v-model="formData.weight_kg" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">Altura (cm)<span v-if="esDeGarmin('height_cm')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" step="0.5" v-model="formData.height_cm" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">Género<span v-if="esDeGarmin('gender')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <select v-model="formData.gender" :class="inputClass">
              <option value="" :class="optClass">— —</option>
              <option value="female" :class="optClass">Femenino</option>
              <option value="male" :class="optClass">Masculino</option>
              <option value="other" :class="optClass">Otro</option>
            </select>
          </div>
          <div>
            <label :class="labelClass">Fecha de nacimiento<span v-if="esDeGarmin('birth_date')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="date" v-model="formData.birth_date" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">Pulso en reposo (ppm)<span v-if="esDeGarmin('resting_heart_rate')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" v-model="formData.resting_heart_rate" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">VO₂max corriendo<span v-if="esDeGarmin('vo2max_running')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" step="0.1" v-model="formData.vo2max_running" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">VO₂max en bici<span v-if="esDeGarmin('vo2max_cycling')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" step="0.1" v-model="formData.vo2max_cycling" :class="inputClass" />
            <p class="text-[12px] text-white/70 mt-1.5 leading-snug">Sólo si entrenas con potenciómetro; si no, déjalo vacío.</p>
          </div>
        </div>
      </SeccionPlegable>

      <SeccionPlegable icono="tune" titulo="Zonas de entrenamiento"
        descripcion="Tu potencia umbral, tu pulso umbral y las zonas que usa tu reloj."
        :resumen="resumenAvanzado.zonas" v-model:abierta="plegables.zonas">
        <!-- Por deporte (revisión del 2026-10-21): seis campos seguidos obligaban a leer cada etiqueta para saber
             de qué deporte era. Y una frase arriba: el plan ya usa lo de tu Garmin. -->
        <p class="text-sm text-white/80 leading-relaxed">Tu plan usa lo que mide tu Garmin, afinado con tus carreras.
          Cámbialo sólo si sabes que no es lo tuyo.</p>
        <!-- En PC, cada deporte a lo ancho (revisión del 2026-10-21: bici y natación ocupaban media fila y dejaban la
             otra mitad vacía, y las cajas de min:seg medían distinto en cada campo). Las de min:seg, iguales en todos. -->
        <p class="flex items-center gap-1.5 text-[13px] font-black text-white uppercase tracking-wider">
          <span class="material-symbols-outlined text-[18px] text-white/80">directions_run</span>Carrera</p>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 -mt-2">
          <div>
            <label :class="labelClass">Pulso que aguantas ~1 hora corriendo (ppm)<span v-if="esDeGarmin('lactate_threshold_hr')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" inputmode="numeric" v-model="formData.lactate_threshold_hr" :class="inputClass" class="max-w-[14rem]" placeholder="Ej. 172" />
          </div>
          <div>
            <label :class="labelClass">Ritmo que aguantas ~1 hora (min/km)<span v-if="esDeGarmin('lactate_threshold_pace_sec_per_km')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <div class="grid grid-cols-[minmax(0,4.5rem)_auto_minmax(0,4.5rem)_auto] items-center gap-2 max-w-[14rem]">
              <input type="number" min="2" max="15" inputmode="numeric" v-model="carreraMin"
                     :class="inputClass" class="!px-2 text-center" placeholder="5" aria-label="Minutos por km" />
              <span class="text-white/60 font-black">:</span>
              <input type="number" min="0" max="59" inputmode="numeric" v-model="carreraSeg"
                     :class="inputClass" class="!px-2 text-center" placeholder="30" aria-label="Segundos por km" />
              <span class="text-[12px] text-white/70 whitespace-nowrap">/km</span>
            </div>
            <!-- El que usa el plan, si no es el de aquí (Beta 3.0, F10): el perfil decía 5:53 de
                 Garmin y el plan usaba 5:43 ajustado sin decirlo (auditoría del 2026-09-23). Como pareja de datos
                 y no como una línea pequeña debajo: «5:53» arriba y «5:40» en letra chica se leían como dos
                 verdades (revisión del 2026-10-21). -->
            <div v-if="umbralDelPlan" class="mt-2 rounded-xl border border-[#a5eeff]/30 bg-[#a5eeff]/5 px-3 py-2.5">
              <p class="text-[13px] text-white">Tu plan usa <b class="text-[#a5eeff]">{{ umbralDelPlan.ritmo }}/km</b></p>
              <p class="text-[12px] text-white/75 leading-snug mt-0.5">{{ umbralDelPlan.porque }}</p>
            </div>
          </div>
          <!-- Database/022. Lo pidió un atleta: "mi zona 2 es realmente a
               6-6:20", y su hoja decía "Fácil 5:03-5:40" sacado de una
               predicción de maratón. Lo que el atleta sabe gana. -->
          <div>
            <label :class="labelClass">Tu ritmo de rodaje suave (min/km)</label>
            <!-- En dos filas (desde / hasta): en una, las cuatro cajas cortaban las cifras («6 : 0(»). -->
            <div class="space-y-2 max-w-[17rem]">
              <div class="grid grid-cols-[3.5rem_minmax(0,1fr)_auto_minmax(0,1fr)_auto] items-center gap-2">
                <span class="text-[12px] text-white/70">desde</span>
                <input type="number" min="3" max="15" inputmode="numeric" v-model="suaveRapidoMin"
                       :class="inputClass" class="!px-2 text-center placeholder:text-white/30" placeholder="–" aria-label="Rodaje suave, ritmo rápido: minutos" />
                <span class="text-white/60 font-black">:</span>
                <input type="number" min="0" max="59" inputmode="numeric" v-model="suaveRapidoSeg"
                       :class="inputClass" class="!px-2 text-center placeholder:text-white/30" placeholder="–" aria-label="Rodaje suave, ritmo rápido: segundos" />
                <span class="text-[12px] text-white/70">/km</span>
              </div>
              <div class="grid grid-cols-[3.5rem_minmax(0,1fr)_auto_minmax(0,1fr)_auto] items-center gap-2">
                <span class="text-[12px] text-white/70">hasta</span>
                <input type="number" min="3" max="15" inputmode="numeric" v-model="suaveLentoMin"
                       :class="inputClass" class="!px-2 text-center placeholder:text-white/30" placeholder="–" aria-label="Rodaje suave, ritmo lento: minutos" />
                <span class="text-white/60 font-black">:</span>
                <input type="number" min="0" max="59" inputmode="numeric" v-model="suaveLentoSeg"
                       :class="inputClass" class="!px-2 text-center placeholder:text-white/30" placeholder="–" aria-label="Rodaje suave, ritmo lento: segundos" />
                <span class="text-[12px] text-white/70">/km</span>
              </div>
            </div>
            <p class="text-[12px] text-white/70 mt-1.5 leading-snug">
              El de un rodaje en el que puedes hablar en frases enteras. Si lo sabes, tu plan lo usa en vez de deducirlo.
            </p>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2">
          <div class="space-y-2">
            <p class="flex items-center gap-1.5 text-[13px] font-black text-white uppercase tracking-wider">
              <span class="material-symbols-outlined text-[18px] text-white/80">directions_bike</span>Bici</p>
            <label :class="labelClass">Potencia que aguantas 1 hora en bici, FTP (vatios)</label>
            <input type="number" min="0" inputmode="numeric" v-model="formData.ftp_watts" :class="inputClass" class="max-w-[14rem]" placeholder="Ej. 220" />
            <p class="text-[12px] text-white/70 leading-snug">
              Sólo si entrenas con potenciómetro: los vatios que aguantas una hora. Sin potenciómetro, déjalo vacío.
            </p>
          </div>
          <div class="space-y-2">
            <p class="flex items-center gap-1.5 text-[13px] font-black text-white uppercase tracking-wider">
              <span class="material-symbols-outlined text-[18px] text-white/80">pool</span>Natación</p>
            <label :class="labelClass">Ritmo nadando fuerte y sin parar (min/100 m)</label>
            <div class="grid grid-cols-[minmax(0,4.5rem)_auto_minmax(0,4.5rem)_auto] items-center gap-2 max-w-[14rem]">
              <input type="number" min="0" max="4" inputmode="numeric" v-model="nadoMin"
                     :class="inputClass" class="!px-2 text-center" placeholder="1" aria-label="Minutos por 100 m" />
              <span class="text-white/60 font-black">:</span>
              <input type="number" min="0" max="59" inputmode="numeric" v-model="nadoSeg"
                     :class="inputClass" class="!px-2 text-center" placeholder="50" aria-label="Segundos por 100 m" />
              <span class="text-[12px] text-white/70 whitespace-nowrap">/100 m</span>
            </div>
            <p class="text-[12px] text-white/70 leading-snug">
              El que aguantarías unos 20 minutos. Si no lo sabes, déjalo vacío: tu coach lo deduce de tus nados.
            </p>
            <button v-if="ritmoDe1500 && !formData.swim_threshold_pace_sec_per_100m" type="button"
                    @click="formData.swim_threshold_pace_sec_per_100m = ritmoDe1500.seg"
                    class="mt-1.5 text-[11px] font-black text-[#caf300] hover:underline text-left">
              Tu 1500 m en {{ ritmoDe1500.marca }} son {{ Math.floor(ritmoDe1500.seg / 60) }}:{{ String(ritmoDe1500.seg % 60).padStart(2, '0') }}/100 m — usarlo
            </button>
          </div>
        </div>
      <details class="group rounded-2xl border border-white/10 bg-white/[0.03] px-4 sm:px-5">
        <summary class="cursor-pointer list-none flex items-center justify-between gap-2 min-h-[48px] text-[14px] font-bold text-white">
          Tus zonas de pulso
          <span class="material-symbols-outlined text-white/60 transition-transform group-open:rotate-180">expand_more</span>
        </summary>
        <div class="space-y-3 pb-4">
        <!-- Dos líneas y no cuatro párrafos (revisión del 2026-10-21). Y la verdad de cuándo se ven los cambios:
             José cambió sus zonas de bici en Garmin Connect y la app seguía con las de antes, porque las lee de
             la última actividad de cada deporte. -->
        <p class="text-[13px] text-white/80 leading-relaxed">
          Son las de <b class="text-white">tu reloj</b>. Si las cambias en Garmin Connect, tu plan las ve desde tu
          siguiente actividad de ese deporte.
        </p>
        <router-link to="/admin/plan"
                     class="inline-flex items-center gap-2 min-h-[44px] text-[13px] font-bold text-[#caf300] hover:text-white transition-colors">
          <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
          <span>Ver mis zonas en el plan</span>
        </router-link>

        <!-- SOLO si sus zonas estan mal, y solo el sabe si lo estan.
             ==================================================================
             Esto NO se le dice a todo el mundo por defecto, y por eso esta
             detras de un desplegable con la condicion escrita en el titulo.
             Un atleta con las zonas bien que resetea sus zonas se queda peor,
             y "toca esta configuracion de tu reloj" dicho a la ligera es la
             clase de consejo que rompe algo que funcionaba.

             La prueba de que estan mal no es un numero nuestro: es que su Z2
             no le deje hablar o su Z4 no aguante una hora. Eso lo sabe el, y
             nadie mas. -->
        <details class="group pt-2">
          <summary class="cursor-pointer list-none flex items-start gap-2.5 text-sm text-white/80 min-h-[44px] py-2
                          hover:text-white transition-colors">
            <span class="material-symbols-outlined text-[18px] text-[#caf300] shrink-0 mt-px
                         group-open:rotate-90 transition-transform">chevron_right</span>
            <span class="flex-1">¿Tu zona 2 no te deja hablar, o tu zona 4 no la aguantas una hora?
              <span class="block text-white/60 text-[12px]">Puede que tus zonas estén mal medidas.</span></span>
          </summary>
          <div class="mt-3 ml-7 space-y-3 text-sm text-white/80 leading-relaxed">
            <p>
              Son las dos señales de que tus zonas no son tuyas:
              <span class="text-white font-bold">la Z2 tiene que dejarte hablar en frases enteras</span> y
              <span class="text-white font-bold">la Z4 tiene que ser un ritmo que aguantarías una hora</span>
              apretando. Si no cuadra, casi siempre es porque están calculadas desde una frecuencia
              cardíaca máxima estimada por fórmula — la de «220 menos tu edad» y sus parientes — y esa
              fórmula falla con facilidad en 10 o 15 pulsaciones por persona.
            </p>
            <p>
              Lo que recomendamos entonces, y es gratis:
            </p>
            <ol class="list-decimal ml-5 space-y-1.5 marker:text-[#caf300] marker:font-bold">
              <li>Activa en tu Garmin la <span class="text-white font-bold">detección automática de umbral
                de lactato</span>.</li>
              <li>Cambia el cálculo de tus zonas de frecuencia cardíaca a
                <span class="text-white font-bold">% del umbral de lactato</span> en vez de % de la máxima.</li>
              <li>Pulsa <span class="text-white font-bold">restablecer</span> en las zonas, para que los
                porcentajes se recoloquen sobre el umbral nuevo.</li>
            </ol>
            <p>
              El umbral es un punto fisiológico real y medible; tu máxima, salvo que te la hayas medido en
              una prueba a tope, es una suposición. Anclar las zonas al umbral es la forma más precisa que
              existe sin pasar por un laboratorio.
            </p>
            <p class="text-[13px] text-[#8f9378]">
              Tres avisos honestos: en la mayoría de relojes la detección
              <span class="text-[#c5c9ac]">necesita banda pectoral</span> y una carrera con un tramo
              exigente para dispararse; el umbral que detecta es
              <span class="text-[#c5c9ac]">el de correr</span>, y en bici suele estar unas pulsaciones más
              abajo; y cuando lo tengas, vuelve a hacer la prueba de las frases enteras en Z2 antes de
              darlo por bueno. Nosotros no tocamos tus zonas: leemos las que tengas puestas.
            </p>
          </div>
        </details>
        </div>
      </details>
      </SeccionPlegable>

      <!-- El test de umbral (Database/023). Los informes del atleta Hugo
           (2026-09-07) tenian un umbral sacado de una prediccion y unas zonas de
           fabrica, y el propio informe decia que lo resolveria un test. Aqui
           esta el protocolo, y la cuenta sobre las vueltas que guardo el reloj.
           Nada se guarda hasta que el atleta pulsa "Usar" y luego Guardar. -->
      <SeccionPlegable icono="science" titulo="Test de umbral"
        descripcion="Una prueba de 30 minutos a tope que mide tu ritmo límite. Para quien ya entrena con constancia."
        :resumen="resumenAvanzado.test" v-model:abierta="plegables.test">
        <!-- Tres pasos visibles y el protocolo detrás de «Cómo es el test» (revisión del 2026-10-21): eran ~25
             líneas antes de llegar a la fecha y los botones. -->
        <p class="text-sm text-white/80 leading-relaxed">
          Te lo enviamos a tu reloj con su calentamiento; lo haces ese día y aquí leemos el resultado. No lo
          necesitas para empezar.
        </p>
        <p class="text-[13px] font-bold text-white/80 -mb-2">Deporte del test</p>
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Deporte del test">
          <button v-for="d in DEPORTES_DEL_TEST" :key="d.id" type="button" role="tab"
                  :aria-selected="testDeporte === d.id" @click="elegirDeporteDelTest(d.id)"
                  class="px-4 min-h-[44px] rounded-xl text-xs font-black uppercase tracking-wider"
                  :class="testDeporte === d.id ? 'bg-[#caf300] text-[#121700]' : 'bg-[#1d2025] border border-white/10 text-white/90 hover:text-white'">
            {{ d.label }}
          </button>
        </div>

        <p v-if="testGuardado" class="text-[13px] text-[#a5eeff]">Tu último test: {{ testGuardado }}</p>

        <details class="group rounded-xl border border-white/10 px-3">
          <summary class="cursor-pointer list-none flex items-center gap-1.5 text-[13px] font-bold text-white/85 hover:text-[#caf300] min-h-[44px]">
            <span class="material-symbols-outlined text-[18px] transition-transform group-open:rotate-90">chevron_right</span>
            Cómo es el test
          </summary>
          <div class="space-y-2 pb-3">
            <p class="text-white font-black text-sm">{{ protocoloDelTest.titulo }}</p>
            <p class="text-[12px] text-white/70 leading-relaxed">{{ protocoloDelTest.cuando }}</p>
            <ol class="list-decimal ml-5 space-y-1 text-[13px] text-white/85 leading-relaxed">
              <li v-for="(paso, i) in protocoloDelTest.pasos" :key="i">{{ paso }}</li>
            </ol>
            <p class="text-[12px] text-white/70 leading-relaxed">{{ protocoloDelTest.calculo }}</p>
            <p v-if="protocoloDelTest.alternativa" class="text-[12px] text-white/70 leading-relaxed">{{ protocoloDelTest.alternativa }}</p>
          </div>
        </details>

        <div v-if="testDeporte !== 'swim'" class="space-y-3">
          <div class="grid grid-cols-1 sm:flex sm:flex-wrap sm:items-end gap-3">
            <div>
              <label :class="labelClass">Día del test</label>
              <input type="date" v-model="fechaDelTest" :min="hoyLocal()" :class="inputClass" aria-label="Día del test" />
            </div>
            <button type="button" @click="enviarTest" :disabled="trabajandoTest || !fechaDelTest"
                    class="flex items-center justify-center gap-2 px-4 min-h-[44px] rounded-xl text-xs font-black uppercase tracking-wider bg-[#caf300] text-[#121700] disabled:bg-white/10 disabled:text-white/60">
              <span class="material-symbols-outlined text-[18px]">watch</span>
              <span>Enviar el test a mi reloj</span>
            </button>
            <p v-if="!fechaDelTest" class="text-[12px] text-white/75 sm:basis-full -mt-1">Elige el día del test para enviarlo.</p>
            <button type="button" @click="analizarTest" :disabled="trabajandoTest"
                    class="flex items-center justify-center gap-2 px-3 min-h-[44px] rounded-xl text-[13px] font-bold text-white/85 underline underline-offset-2 hover:text-white disabled:opacity-50">
              <span class="material-symbols-outlined text-[18px]">{{ trabajandoTest ? 'sync' : 'monitoring' }}</span>
              <span>{{ trabajandoTest ? 'Leyendo tu Garmin…' : '¿Ya lo hiciste? Leer mi test de Garmin' }}</span>
            </button>
          </div>
          <div v-if="resultadoDelTest && resultadoDelTest.encontrada"
               class="rounded-2xl border p-4 space-y-1.5" :class="resultadoDelTest.ok ? 'border-[#caf300]/40' : 'border-white/10'">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-sm text-white font-bold">{{ resultadoDelTest.nombre || 'Actividad' }}
                <span class="text-[#8f9378] font-normal">· {{ resultadoDelTest.fecha }} · {{ resultadoDelTest.minutos }} min</span></p>
              <button v-if="resultadoDelTest.ok" type="button" @click="usarTest(resultadoDelTest)"
                      class="px-3 py-1.5 rounded-lg bg-[#caf300] text-[#121700] text-[11px] font-black uppercase tracking-wider">
                Usar estos valores
              </button>
            </div>
            <p v-if="resultadoDelTest.ok" class="text-sm text-[#caf300] font-black font-mono-data">
              FC umbral {{ resultadoDelTest.lthr }} ppm<span v-if="resultadoDelTest.ritmo"> · ritmo umbral {{ resultadoDelTest.ritmo }}/km</span><span v-if="resultadoDelTest.ftp_w"> · FTP ~{{ resultadoDelTest.ftp_w }} W</span>
              <span class="block text-[11px] text-[#8f9378] font-normal">Test de {{ resultadoDelTest.minutos_test }} min; pulso de los últimos {{ resultadoDelTest.minutos_ultimos }} min.</span>
            </p>
            <p v-else class="text-xs text-[#c5c9ac] leading-relaxed">No se puede leer como test: {{ resultadoDelTest.motivo }}</p>
            <p v-for="(aviso, i) in (resultadoDelTest.avisos || [])" :key="i" class="text-[11px] text-[#8f9378] leading-relaxed">{{ aviso }}</p>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-for="t in TIEMPOS_DE_NATACION" :key="t.id">
              <label :class="labelClass">{{ t.label }}</label>
              <div class="flex items-center gap-2">
                <input type="number" min="0" max="30" inputmode="numeric" v-model="tiemposDeNado[t.id].min"
                       :class="inputClass" class="text-center" placeholder="0" :aria-label="`${t.label}: minutos`" />
                <span class="text-[#8f9378] font-black">:</span>
                <input type="number" min="0" max="59" inputmode="numeric" v-model="tiemposDeNado[t.id].seg"
                       :class="inputClass" class="text-center" placeholder="00" :aria-label="`${t.label}: segundos`" />
              </div>
            </div>
          </div>
          <p v-if="cssDelTest && cssDelTest.ok" class="text-sm text-[#caf300] font-black font-mono-data">
            CSS {{ cssDelTest.css }}/100 m
            <button type="button" @click="usarCss"
                    class="ml-2 px-3 py-1.5 rounded-lg bg-[#caf300] text-[#121700] text-[11px] font-black uppercase tracking-wider align-middle">
              Usar este ritmo
            </button>
          </p>
          <p v-else-if="cssDelTest" class="text-xs text-[#c5c9ac]">{{ cssDelTest.motivo }}</p>
        </div>

        <p v-if="testMsg" class="text-xs leading-relaxed" :class="testError ? 'text-[#ffb4ab]' : 'text-[#a5eeff]'">{{ testMsg }}</p>
      </SeccionPlegable>

      <!-- Aqui habia una tabla titulada "Zonas de Entrenamiento Calculadas"
           con los cinco rangos ESCRITOS A MANO en el HTML: "163 - 221 W" para
           todo el mundo, independientemente de su FTP. No calculaba nada. Era
           maqueta que se quedo a la vista de nueve atletas reales bajo un
           titulo que decia "Calculadas".

           No se sustituye por un calculo desde el FTP a proposito. Las zonas
           que manda el plan son las del RELOJ del atleta -- leidas de Garmin
           por services/ai/prescription.py cada vez que se genera -- y una
           segunda tabla derivada aqui de una formula diria numeros distintos
           de los que va a ver en la muneca. Ese desacuerdo ya costo una "Zona
           2" empezando en 143 cuando su reloj empieza en 139. -->

    </div>

    <!-- ============ Tab 5: Equipo y Entorno ============ -->
    <div v-if="activeTab === 'avanzado'" class="space-y-6">
      <SeccionPlegable icono="fitness_center" titulo="Equipo y entorno"
        descripcion="Dónde entrenas: terreno, clima, altitud, cinta."
        :resumen="resumenAvanzado.equipo" v-model:abierta="plegables.equipo">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelClass">Altitud donde entrenas (m)</label>
            <input type="number" min="0" v-model="formData.altitude_m" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">Clima habitual</label>
            <select v-model="formData.climate" :class="inputClass">
              <option value="" :class="optClass">— Seleccionar —</option>
              <option value="templado" :class="optClass">Templado</option>
              <option value="calido_humedo" :class="optClass">Cálido / Húmedo</option>
              <option value="frio" :class="optClass">Frío</option>
              <option value="variable" :class="optClass">Variable</option>
            </select>
          </div>
          <div>
            <label :class="labelClass">Terreno</label>
            <select v-model="formData.terrain" :class="inputClass">
              <option value="" :class="optClass">— Seleccionar —</option>
              <option value="llano" :class="optClass">Llano</option>
              <option value="montanoso" :class="optClass">Montañoso</option>
              <option value="mixto" :class="optClass">Mixto</option>
            </select>
          </div>
          <!-- "Entrenos brick" ya no se pregunta. Desde la v1.1 un brick no
               es un tipo de sesión que se pueda elegir: son DOS sesiones el
               mismo día, y lo que las encadena va escrito en el texto de la
               primera, porque un multideporte no le llega bien a varios
               relojes. Preguntar por una preferencia sobre algo que ya no
               existe como objeto sólo podía confundir. -->
        </div>
        <label class="flex items-center gap-3 cursor-pointer min-h-[44px]">
          <input type="checkbox" v-model="formData.treadmill_access" class="w-5 h-5 accent-[#caf300]" />
          <span class="text-sm text-white">Tengo cinta de correr (trotadora)</span>
        </label>
      </SeccionPlegable>
    </div>

    <!-- ============ Tab 6: Estilo de Vida ============ -->
    <div v-if="activeTab === 'avanzado'" class="space-y-6">
      <SeccionPlegable icono="bedtime" titulo="Estilo de vida y recuperación"
        descripcion="Sueño, trabajo, estrés, viajes y alimentación."
        :resumen="resumenAvanzado.estilo" v-model:abierta="plegables.estilo">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label :class="labelClass">Tu trabajo</label>
            <select v-model="formData.occupation_type" :class="inputClass">
              <option value="" :class="optClass">— Seleccionar —</option>
              <option value="sedentario" :class="optClass">Sedentario (oficina)</option>
              <option value="activo_fisico" :class="optClass">Físicamente Activo</option>
              <option value="mixto" :class="optClass">Mixto</option>
            </select>
          </div>
          <div>
            <label :class="labelClass">Horas de sueño (media)<span v-if="esDeGarmin('avg_sleep_hours')" :class="badgeGarmin" :title="tituloGarmin"><span class="material-symbols-outlined text-[11px] leading-none">watch</span>GARMIN</span></label>
            <input type="number" min="0" max="16" step="0.1" v-model="formData.avg_sleep_hours" :class="inputClass" />
          </div>
          <div>
            <label :class="labelClass">Tu nivel de estrés</label>
            <select v-model="formData.stress_level_self_report" :class="inputClass">
              <option value="" :class="optClass">— Seleccionar —</option>
              <option value="bajo" :class="optClass">Bajo</option>
              <option value="medio" :class="optClass">Medio</option>
              <option value="alto" :class="optClass">Alto</option>
            </select>
          </div>
          <div>
            <label :class="labelClass">Cuánto viajas</label>
            <select v-model="formData.travel_frequency" :class="inputClass">
              <option value="" :class="optClass">— Seleccionar —</option>
              <option value="nunca" :class="optClass">Nunca</option>
              <option value="ocasional" :class="optClass">Ocasional</option>
              <option value="frecuente" :class="optClass">Frecuente</option>
            </select>
          </div>
        </div>
        <div>
          <label :class="labelClass">Alimentación</label>
          <p class="text-[12px] text-white/70 -mt-1 mb-1.5 leading-snug">Horarios, restricciones, lo que te sienta mal. Lo que
            quieras pedir de tu entrenamiento, mejor en
            <router-link to="/admin/cuaderno" class="text-[#caf300] font-bold underline underline-offset-2">tu cuaderno</router-link>.</p>
          <textarea rows="3" v-model="formData.nutrition_notes" :class="inputClass" class="[field-sizing:content] min-h-[5.5rem] resize-none" placeholder="Ej. Vegetariano, restricciones, horarios de comida..."></textarea>
        </div>
        <!-- No se le ofrece a quien marcó Masculino (auditoría del 2026-09-24); si ya lo tenía
             marcado se sigue enseñando, para poder quitarlo. -->
        <label v-if="formData.gender !== 'male' || formData.menstrual_tracking_opt_in" class="flex items-center gap-3 cursor-pointer">
          <input type="checkbox" v-model="formData.menstrual_tracking_opt_in" class="w-4 h-4 accent-[#caf300]" />
          <span class="text-sm text-white">Quiero que el ciclo menstrual se considere en mi plan (opcional)</span>
        </label>
        <div v-if="formData.menstrual_tracking_opt_in">
          <label :class="labelClass">Notas sobre tu ciclo menstrual</label>
          <textarea rows="3" v-model="formData.menstrual_cycle_notes" :class="inputClass" class="[field-sizing:content] min-h-[5.5rem] resize-none" placeholder="Ej. Fase actual, cómo suele afectar tu energía/recuperación..."></textarea>
        </div>
      </SeccionPlegable>
    </div>

    <!-- ============ Tab 7: Salud ============ -->
    <div v-if="activeTab === 'avanzado'" class="space-y-6">
      <SeccionPlegable id="seccion-salud" icono="health_and_safety" titulo="Salud y antecedentes"
        descripcion="Lesiones, condiciones y medicación. Sólo lo que quieras declarar."
        :resumen="resumenAvanzado.salud" v-model:abierta="plegables.salud">

        <!-- Qué va en cada sitio (revisión del 2026-10-21): el botón y los textos libres parecían tres formas de
             declarar lo mismo. Y para qué se usa: es lo más sensible del perfil. -->
        <p class="text-[13px] text-white/80 leading-relaxed rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2.5">
          <span class="material-symbols-outlined text-[16px] align-middle text-[#a5eeff]">lock</span>
          Sólo se usa para escribir tu plan: lo leen los modelos de IA que lo escriben, como dicen las condiciones de
          uso. Declara sólo lo que quieras.
        </p>
        <div class="space-y-4">
          <div>
            <p class="text-sm font-bold text-white">Lesiones o molestias de ahora</p>
            <p class="text-[12px] text-white/70 leading-snug">Las que te molestan hoy. Tu plan las tiene en cuenta en
              proporción al dolor que digas.</p>
          </div>
          <div
            v-for="(inj, i) in formData.current_injuries"
            :key="`inj-${i}`"
            class="glass-card rounded-2xl p-5 border border-white/10 space-y-4"
          >
            <div class="flex justify-between items-center">
              <span class="text-[11px] font-black uppercase tracking-widest"
                    :class="inj.status === 'resuelta' ? 'text-[#8f9378]' : 'text-[#ffb4ab]'">
                {{ inj.status === 'resuelta' ? 'Lesión resuelta' : 'Lesión activa' }} {{ i + 1 }}
              </span>
              <button type="button" @click="formData.current_injuries.splice(i, 1)" class="text-[#ffb4ab] hover:text-white">
                <span class="material-symbols-outlined text-[18px]">close</span>
              </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label :class="labelClass">Ubicación</label>
                <input type="text" v-model="inj.location" :class="inputClass" placeholder="Ej. Rodilla, tendón de Aquiles" />
              </div>
              <div>
                <label :class="labelClass">Lateralidad</label>
                <select v-model="inj.laterality" :class="inputClass">
                  <option value="" :class="optClass">— —</option>
                  <option value="izquierda" :class="optClass">Izquierda</option>
                  <option value="derecha" :class="optClass">Derecha</option>
                  <option value="bilateral" :class="optClass">Bilateral</option>
                </select>
              </div>
              <div>
                <label :class="labelClass">¿Cuándo empezó?</label>
                <input type="text" v-model="inj.onset" :class="inputClass" placeholder="Ej. hace 3 semanas" />
              </div>
              <div>
                <label :class="labelClass">Dolor (0-10)</label>
                <input type="number" min="0" max="10" v-model="inj.pain_scale" :class="inputClass"
                       :disabled="inj.status === 'resuelta'" />
              </div>
              <!-- Una lesion curada CAMBIA DE ESTADO, no se borra. El historial
                   empieza a valer justo cuando deja de doler: dice por donde se
                   le ha roto antes a este cuerpo. Y deja de ser una restriccion,
                   asi que el plan no la esquiva -- ver health_context.py. -->
              <div>
                <label :class="labelClass">Estado</label>
                <select v-model="inj.status" :class="inputClass">
                  <option value="activa" :class="optClass">Activa — me molesta ahora</option>
                  <option value="resuelta" :class="optClass">Resuelta — ya no me molesta</option>
                </select>
              </div>
              <div>
                <label :class="labelClass">Qué lo empeora</label>
                <input type="text" v-model="inj.aggravating_factors" :class="inputClass" placeholder="Ej. bajadas, correr >10km" />
              </div>
              <div>
                <label :class="labelClass">Tratamiento</label>
                <input type="text" v-model="inj.treatment" :class="inputClass" placeholder="Ej. fisioterapia" />
              </div>
              <div class="md:col-span-2">
                <label :class="labelClass">Observación</label>
                <input type="text" v-model="inj.notes" :class="inputClass"
                       placeholder="Ej. sólo me pasa en bajada; el fisio dice que viene de la cadera" />
              </div>
            </div>
          </div>

          <button type="button" @click="addInjury" :class="addBtnClass">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Agregar lesión</span>
          </button>
        </div>

        <div class="grid grid-cols-1 gap-5 border-t border-white/10 pt-5">
          <p class="text-sm font-bold text-white -mb-2">Tus antecedentes, en tus palabras</p>
          <div>
            <label :class="labelClass">Lesiones o cirugías de antes (ya curadas)</label>
            <textarea rows="3" v-model="formData.past_injuries_surgeries" :class="inputClass" class="[field-sizing:content] min-h-[5.5rem] resize-none"></textarea>
          </div>
          <div>
            <label :class="labelClass">Condiciones crónicas</label>
            <textarea rows="3" v-model="formData.chronic_conditions" :class="inputClass" class="[field-sizing:content] min-h-[5.5rem] resize-none" placeholder="Ej. asma, hipotiroidismo"></textarea>
          </div>
          <div>
            <label :class="labelClass">Algo más que deba saber tu entrenador</label>
            <textarea rows="3" v-model="formData.limitations" :class="inputClass" class="[field-sizing:content] min-h-[5.5rem] resize-none" placeholder="Ej. el médico me pidió no pasar de 160 ppm"></textarea>
          </div>
          <div>
            <label :class="labelClass">Medicamentos</label>
            <textarea rows="3" v-model="formData.medications" :class="inputClass" class="[field-sizing:content] min-h-[5.5rem] resize-none" placeholder="Ej. betabloqueantes, inhalador"></textarea>
          </div>
        </div>
      </SeccionPlegable>
    </div>

    <!-- ============ Preferencias de Coaching ============
         En IDENTIDAD, debajo de los datos, y no en Avanzado: el dia de
         descanso y el dia en que empieza la semana deciden la forma de cada
         semana, y escondidos en la ultima pestaña nadie los encontraba. -->
    <!-- En el alta va plegada: sus tres valores tienen un defecto que vale, y abierta alargaba el primer
         paso con preferencias que no hacen falta para empezar (revisión del 2026-10-21). -->
    <div v-if="activeTab === 'identidad'" class="space-y-6">
      <SeccionPlegable
        v-model:abierta="semanaAbierta"
        icono="settings_suggest"
        titulo="Tu semana"
        descripcion="Qué día empieza y si se puede cambiar una sesión por otro deporte."
        :resumen="resumenDeTuSemana"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <!-- El "Tono del Coach" ya no se elige aquí. Ahora se elige
               ELIGIENDO ENTRENADOR (engranaje del topbar → Apariencia): cada
               uno tiene su forma de hablar, y así lo que se escoge es una
               persona y no un ajuste suelto. Sólo afecta al chat. -->
          <!-- "¿Incluir Entrenamiento de Fuerza?" ya no se pregunta aquí: la
               rejilla de días lo contesta mejor y sin ambigüedad. Marcar
               Fuerza un martes ES pedir fuerza; no marcarla ningún día ES no
               quererla. Tener las dos cosas permitía contradecirse — un
               "No" aquí y tres días de Fuerza en la rejilla — y entonces
               alguien tenía que decidir cuál de las dos ganaba. -->
          <!-- «Día de descanso preferido» ya no se pregunta (decisión del responsable, 2026-10-21): repetía la
               tabla de días y los dos podían contradecirse. Un día sin nada marcado en la tabla ES descanso, y así
               llega al prompt; ver `assembleProfilePayload`. -->
          <div>
            <label :class="labelClass">Tu semana empieza el</label>
            <select v-model="formData.week_start_day" :class="inputClass">
              <option value="" :class="optClass">— El día en que generes —</option>
              <option v-for="day in weekDays" :key="day" :value="day" :class="optClass">{{ day }}</option>
            </select>
            <p class="text-[12px] text-white/70 mt-1.5 leading-snug">{{ ejemploDeInicioDeSemana }}</p>
          </div>
        </div>
        <label class="flex items-start gap-3 cursor-pointer min-h-[44px]">
          <input type="checkbox" v-model="formData.cross_training_open" class="mt-0.5 w-5 h-5 accent-[#caf300] shrink-0" />
          <span class="text-sm text-white leading-snug">
            Mi entrenador puede cambiar una sesión por otro deporte
            <span class="block text-[12px] text-white/70 mt-0.5">
              Si un día no conviene la sesión prevista (mucha carga, una molestia, el clima), la cambia por bici o
              natación suave y te dice por qué. Sólo con los deportes que marques ese día en tu tabla.
            </span>
          </span>
        </label>
        <!-- Aqui estaba "Reducir automaticamente el volumen de la semana 1 si mi
             ACWR esta en zona de riesgo". Se retiro el 2026-09-14 junto con la
             regla del validador: era un corte de poblacion (>1,5, "alto riesgo
             de lesion") que el ACWR no puede sostener (Impellizzeri 2020), y
             venia marcado por defecto. La columna sigue en la base de datos. -->
      </SeccionPlegable>

      <!-- Aqui habia una seccion "Tu Entrenador de IA" que explicaba que
           modelo corria cada rol y que ventanas de historial se leen.
           Retirada: es arquitectura nuestra, no una decision ni un dato del
           atleta, y ocupaba una pantalla entera de su perfil sin que hubiera
           nada que hacer en ella. -->
    </div>

    <!-- «Restablecer mi perfil» ya no está aquí: borraba TODO el perfil desde el final de una pestaña opcional
         (revisión del 2026-10-21). Vive en la tuerca → Cuenta, con BORRAR escrito (components/cuenta/CuentaPanel.vue). -->


    <!-- Guardar a la vista cuando hay algo que guardar (revisión del 2026-10-21): sólo estaba arriba, y al final de
         una pestaña de siete pantallas no se veía ni se sabía si había cambios pendientes. Deja sitio a la
         derecha para el botón flotante del coach. -->
    <div v-if="!guiado && hayCambios"
         class="sticky bottom-3 z-30 mr-[4.5rem] glass-panel rounded-2xl border border-[#caf300]/35 shadow-2xl p-2.5 pl-4 flex items-center justify-between gap-3">
      <span class="flex items-center gap-2 text-[13px] font-bold text-white">
        <span class="w-2 h-2 rounded-full bg-[#ffd166] shrink-0"></span>Cambios sin guardar
      </span>
      <button type="button" @click="handleSave" :disabled="store.isProfileSaving"
              class="flex items-center gap-1.5 px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider disabled:opacity-60">
        <span class="material-symbols-outlined text-[18px]">{{ store.isProfileSaving ? 'sync' : 'save' }}</span>
        {{ store.isProfileSaving ? 'Guardando…' : 'Guardar' }}
      </button>
    </div>

    <!-- Pie del paso a paso. Guardar existe siempre arriba; esto es el hilo
         que lleva de un apartado al siguiente sin que el atleta tenga que
         adivinar que las pestañas se recorren en orden. -->
    <section
      v-if="guiado"
      class="sticky bottom-4 z-30 glass-panel rounded-2xl border border-white/15 shadow-2xl p-4 flex flex-wrap items-center justify-between gap-3"
    >
      <button
        v-if="pasoActual > 0"
        @click="pasoAnterior"
        class="px-4 py-2.5 glass-pill text-[#c5c9ac] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all"
      >
        Atrás
      </button>
      <span v-else></span>

      <div class="flex items-center gap-2.5 ml-auto">
        <button
          @click="saltarPaso"
          class="px-4 py-2.5 glass-pill text-white/85 hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all"
        >
          Saltar por ahora
        </button>
        <button
          @click="siguientePaso"
          :disabled="store.isProfileSaving || garminImporting"
          class="flex items-center gap-2 px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-transform shadow-[0_0_20px_rgba(202,243,0,0.35)] active:scale-95 disabled:opacity-50"
        >
          <span>{{ garminImporting ? 'Conectando…' : esUltimoPaso ? 'Terminar' : (conectaAlContinuar ? 'Conectar y continuar' : 'Guardar y continuar') }}</span>
          <span class="material-symbols-outlined text-[18px]">{{ esUltimoPaso ? 'check' : 'arrow_forward' }}</span>
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount, toRaw } from 'vue';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import swal from '../../utils/swal';
import { garminImport, saveGarminPassword, getGarminPasswordStatus, testDeUmbral } from '../../services/profile';
import { getPlan } from '../../services/plan';
import { renderMarkdown } from '../../utils/markdown';
import PuertaFalsa from '../../components/layout/PuertaFalsa.vue';
import TiempoHMS from '../../components/forms/TiempoHMS.vue';
import PerfilDeFuerza from '../../components/forms/PerfilDeFuerza.vue';
import SeccionPlegable from '../../components/forms/SeccionPlegable.vue';
import FilaPlegable from '../../components/forms/FilaPlegable.vue';
import MesAnio from '../../components/forms/MesAnio.vue';
import { tiempoASegundos, leerTiempo } from '../../utils/tiempo';
import { num } from '../../utils/garmin';
import {
  WEEK_DAYS,
  GRID_SPORTS,
  ETIQUETAS_DE_CAMPO,
  PESTANA_DE_CAMPO,
  MINIMO_PERFIL,
  MINIMOS_POR_DEPORTE,
  ESTADO_FIJO,
  ESTADO_POSIBLE,
  diasPorDeporte,
  marcaVacia,
  emptyFormData,
  buildFormData,
  assembleProfilePayload,
  secToPace,
  paceToSec,
} from '../../utils/profile';
import { fijosPocoHechos, fraseDelFijo } from '../../utils/fijos';

const store = useAppStore();
const route = useRoute();
const router = useRouter();
const activeTab = ref('identidad');

// --- Paso a paso de la primera vez ---------------------------------------
//
// No es una pantalla aparte: son las MISMAS pestañas recorridas en orden. Un
// asistente separado seria una segunda copia del formulario, y las dos
// divergirian en cuanto se añadiera un campo.
//
// El orden ya empieza por Identidad, que es donde esta el boton de conectar
// Garmin: de ahi salen peso, altura, VO2max, FC de reposo y umbral sin que el
// atleta escriba nada, y todo lo que venga despues llega ya medio relleno.
const guiado = computed(() => route.query.onboarding === '1');
const pasoActual = computed(() => Math.max(0, tabs.findIndex((t) => t.id === activeTab.value)));
const esUltimoPaso = computed(() => pasoActual.value === tabs.length - 1);

const irAPaso = (i) => {
  activeTab.value = tabs[Math.min(Math.max(i, 0), tabs.length - 1)].id;
  if (typeof window !== 'undefined') window.scrollTo({ top: 0, behavior: 'smooth' });
};
const pasoAnterior = () => irAPaso(pasoActual.value - 1);

// Saltar es legitimo y esta a un clic, pero se dice QUE cuesta -- y en
// concreto, no con un "sera menos preciso" que no significa nada.
const saltarPaso = async () => {
  const r = await swal.fire({
    icon: 'warning',
    title: `¿Saltar ${tabs[pasoActual.value].label}?`,
    html: 'Tu entrenador escribe el plan con lo que le des. Lo que falte aquí lo tendrá que '
        + '<b>suponer</b>, y una suposición sobre tu carga, tus horarios o tu salud es justo lo que hace '
        + 'que un plan no encaje.<br><br>Puedes volver cuando quieras desde tu menú, arriba a la derecha.',
    showCancelButton: true,
    confirmButtonText: 'Saltar de todas formas',
    cancelButtonText: 'Lo relleno ahora',
  });
  if (!r.isConfirmed) return;
  if (esUltimoPaso.value) await terminarGuiado({ guardar: false });
  else irAPaso(pasoActual.value + 1);
};

const siguientePaso = async () => {
  // Con correo y contraseña escritos y sin conectar, «Guardar y continuar» conecta (decisión del responsable,
  // 2026-10-21): quien escribía sus datos y pulsaba el botón lima creía haber conectado y seguía sin Garmin.
  // Si Garmin la rechaza, se queda en el paso con el error a la vista.
  if (pasoActual.value === 0 && formData.garmin_email && formData.garmin_password && !garminYaConectado.value) {
    await handleGarminImport();
    if (garminError.value) return;
  }
  if (!(await handleSave({ silencioso: true }))) return;  // no avanzamos sobre un guardado fallido
  if (esUltimoPaso.value) await terminarGuiado({ guardar: false });
  else irAPaso(pasoActual.value + 1);
};

// Al terminar, el asistente de salud. Es el unico que puede leer lo que el
// atleta acaba de escribir y repreguntar; llega con la ficha delante en vez de
// partir de cero. Salud es el ultimo paso, asi que aqui ya estamos en su
// pestaña: el boton ARRANCA la consulta en vez de solo cerrar el aviso, que es
// lo que hacia antes -- prometia una acción y no ejecutaba ninguna.
const terminarGuiado = async ({ guardar = true } = {}) => {
  if (guardar) await handleSave({ silencioso: true });
  // A la pantalla de bienvenida: ahi elige entrenador -- lo unico que le queda
  // por decidir y que hasta ahora vivia escondido en Configuracion -- y ve de
  // una vez para que sirve cada seccion.
  //
  // Antes esto era un aviso que ofrecia arrancar el asistente de salud. La
  // oferta no se pierde: es un boton de esa pantalla ("Antes, el asistente de
  // salud"), que trae aqui con ?tab=salud. Cambia de un modal que hay que
  // contestar en el momento a algo que sigue estando cuando vuelva.
  router.replace({ path: '/admin/bienvenida' });
};
const saveToast = ref(false);

const garminImporting = ref(false);
const verClave = ref(false);
const volumenGarmin = ref(null);
const garminMsg = ref('');
const garminError = ref(false);
const garminConnected = ref(false);

// El informe que dejo el asistente de salud, si el atleta llego a usarlo.
// Se sigue enseñando -- es suyo -- aunque ya no se pueda pedir uno nuevo.

const weekDays = WEEK_DAYS;
const gridSports = GRID_SPORTS;

/**
 * La rejilla de disponibilidad: tres estados por casilla, y cada toque pasa al siguiente:
 * (nada) -> posible -> fijo -> (nada). Se probó un «pincel» (elegir qué marcar y luego tocar) y el responsable
 * lo descartó el 2026-10-21: «tocar una vez da un cheque, dos el pin y la tercera regresa a descanso» es más
 * intuitivo. «Posible» va primero porque es lo corriente: «sí o sí» son dos o tres casillas de treinta y cinco.
 */
const estadoCasilla = (dep, dia) => (formData.sportDays[dep] || {})[dia] || null;

// Las mismas clases en la tabla y en el selector.
const CLASE_FIJO = 'bg-[#a5eeff] border-[#a5eeff] text-[#00363f]';
const CLASE_POSIBLE = 'bg-[#caf300]/25 border-[#caf300] text-[#caf300]';
const CLASE_NO = 'border-white/40 text-transparent hover:border-white/70';
const LEYENDA = [
  { titulo: 'Descanso', texto: 'vacía', icono: '', clase: 'border-white/40' },
  { titulo: 'Puedo', texto: 'el coach decide', icono: 'check', clase: CLASE_POSIBLE },
  { titulo: 'Sí o sí', texto: 'lo quiero', icono: 'push_pin', clase: CLASE_FIJO },
  { titulo: 'Descanso', texto: 'otra vez vacía', icono: '', clase: 'border-white/40' },
];
const SIGUIENTE = { '': ESTADO_POSIBLE, [ESTADO_POSIBLE]: ESTADO_FIJO, [ESTADO_FIJO]: '' };
// La sesión más larga en horas y minutos; se guarda en horas, como siempre.
const largaHoras = computed({
  get: () => { const h = Number(formData.longest_recent_session_hours); return Number.isFinite(h) && h > 0 ? Math.floor(h) : ''; },
  set: (v) => { formData.longest_recent_session_hours = ((Number(v) || 0) + (Number(largaMinutos.value) || 0) / 60) || ''; },
});
const largaMinutos = computed({
  get: () => { const h = Number(formData.longest_recent_session_hours); return Number.isFinite(h) && h > 0 ? Math.round((h - Math.floor(h)) * 60) : ''; },
  set: (v) => { formData.longest_recent_session_hours = ((Number(largaHoras.value) || 0) + (Number(v) || 0) / 60) || ''; },
});

// Si marcó Nadar algún día (o aún no tiene tabla: entonces no lo sabemos y se pregunta igual).
const nadaAlgunDia = computed(() => {
  const dias = formData.sportDays || {};
  if (!Object.values(dias).some((fila) => Object.keys(fila || {}).length)) return true;
  return Object.keys(dias.Nadar || {}).length > 0;
});
const rotarCasilla = (dep, dia) => {
  const fila = { ...(formData.sportDays[dep] || {}) };
  const siguiente = SIGUIENTE[estadoCasilla(dep, dia) || ''];
  if (siguiente) fila[dia] = siguiente;
  else delete fila[dia];
  formData.sportDays = { ...formData.sportDays, [dep]: fila };
};
const claseCasilla = (dep, dia) => {
  const e = estadoCasilla(dep, dia);
  if (e === ESTADO_FIJO) return CLASE_FIJO;
  if (e === ESTADO_POSIBLE) return CLASE_POSIBLE;
  return CLASE_NO;
};
const ICONO_DEL_DEPORTE = {
  Correr: 'directions_run', Trail: 'landscape', Bici: 'directions_bike', Nadar: 'pool', Fuerza: 'fitness_center',
};
// Dos letras: con una, «X» para el miércoles no se entendía y «M» eran dos días.
const inicialDelDia = (dia) => dia.slice(0, 2);

const tituloCasilla = (dep, dia) => {
  const e = estadoCasilla(dep, dia);
  if (e === ESTADO_FIJO) return 'lo quiero sí o sí';
  if (e === ESTADO_POSIBLE) return 'puedo, que decida el coach';
  return 'ese día, no';
};

/** El minimo sugerido de ese deporte, si se queda corto. Nunca bloquea. */
const avisoMinimo = (dep) => {
  const m = MINIMOS_POR_DEPORTE[dep];
  if (!m) return '';
  const n = diasPorDeporte(formData.sportDays, dep);
  if (n === 0 || n >= m.min) return '';
  return `sugerido ${m.min} dias (recomendado ${m.rec})`;
};
const avisosMinimos = computed(() => GRID_SPORTS.flatMap((dep) => {
  const m = MINIMOS_POR_DEPORTE[dep];
  return avisoMinimo(dep) ? [`${dep}: te sugerimos ${m.min} días (lo ideal, ${m.rec}).`] : [];
}));

/**
 * Lo que el atleta declara que entrena hoy, para poner al lado de lo que pide.
 *
 * Desde la v1.2 ya NO se pregunta: lo mide `get_weekly_training_volume()` de
 * las ultimas 12 semanas de Garmin y lo escribe la importacion. El campo sigue
 * existiendo en el perfil -- un atleta sin Garmin conectado no tiene otra
 * fuente -- pero deja de ser una casilla que rellenar a ojo, y siempre se
 * enseña con su procedencia al lado.
 */
const horasMediasGarmin = computed(() => {
  const h = Number(formData.current_weekly_volume_hours);
  return Number.isFinite(h) && h > 0 ? h : null;
});

/**
 * De donde sale ese numero, dicho al lado del numero.
 *
 * Una media de 4 h/semana no significa lo mismo si entreno las doce semanas que
 * si entreno seis, asi que cuando Garmin acaba de darlo se dice cuantas semanas
 * tuvieron actividad. Si el atleta lo escribio a mano (o viene de un perfil
 * viejo), se dice eso en vez de atribuirselo a Garmin.
 */
const origenDelVolumen = computed(() => {
  const v = volumenGarmin.value;
  if (v && v.hours_per_week) {
    const activas = v.active_weeks && v.active_weeks < v.weeks
      ? `, con actividad en ${v.active_weeks} de ${v.weeks}`
      : '';
    return `medido en tus últimas ${v.weeks} semanas de Garmin${activas}`;
  }
  // "según lo que declaraste" sólo si de verdad lo escribió él. Este texto
  // salía debajo de un 6.5 que Garmin había medido sobre 12 semanas, porque
  // la procedencia se buscaba únicamente en localStorage y no sobrevivía a
  // recargar la página. Ver `esDeGarmin`.
  return esDeGarmin('current_weekly_volume_hours')
    ? 'medido por Garmin en tus últimas semanas de entreno'
    : 'según lo que declaraste';
});

/**
 * El aviso de carga. Compara SESIONES pedidas contra las que caben en lo que el
 * atleta dice que entrena, a una hora por sesion como regla gruesa.
 *
 * No bloquea y no recorta: pone el numero al lado de la peticion y sigue. Quien
 * decide cuanto puede entrenar es el atleta, y la IA ya regula la duracion.
 */
const avisoDeCarga = computed(() => {
  const pedidas = GRID_SPORTS.reduce((n, dep) => n + diasPorDeporte(formData.sportDays, dep), 0);
  const h = horasMediasGarmin.value;
  if (!pedidas || !h || pedidas <= h + 1) return '';
  return `Marcaste ${pedidas} casillas. Según tu Garmin entrenas unas ${num(h)} h por semana, así que tu `
    + 'entrenador ajustará la duración de cada sesión para que la carga sea sostenible. Si prefieres menos '
    + 'sesiones y más largas, desmarca alguna.';
});


// SALUD VA LA ÚLTIMA, y no es una cuestión de gusto.
//
// El asistente de salud vive dentro de esa pestaña, y el recorrido guiado
// termina diciendo "ahora habla con el asistente". Mientras Salud fue el paso
// 7 de 8, el atleta se topaba con el asistente a mitad de camino y al final se
// le pedía que hablara con alguien con quien ya había hablado.
//
// Además es el orden que hace útil al asistente: es el único que puede leer lo
// que el atleta acaba de escribir y repreguntar, así que cuanto más tarde
// llegue, más ficha tiene delante.
/**
 * El recorrido guiado: lo que hace falta para escribir la primera semana.
 *
 * Tres apartados, no ocho. Salud SALE del alta a proposito -- era la octava y
 * ultima, asi que lo ultimo que veia un atleta al registrarse era un
 * cuestionario clinico de siete preguntas, y en la beta eso se leyo como que la
 * app va de lesiones. Sigue estando entera, en Avanzado, para quien la quiera.
 */
const tabs = [
  { id: 'identidad', label: 'Identidad' },
  { id: 'objetivos', label: 'Objetivos y días' },
  // «Mi experiencia» y no «Historial» (decisión del responsable, 2026-10-21): el menú ya tiene un «Historial» con
  // los planes anteriores, y aquí se esperaban ver los entrenos pasados. El id sigue siendo 'historial' (enlaces).
  { id: 'historial', label: 'Mi experiencia' },
];



const SECCIONES_DE_AVANZADO = { salud: 'seccion-salud' };

// Avanzado, por secciones que se despliegan (2026-10-21). Cerradas de entrada: el atleta ve qué hay y
// cuánto lleva, y abre lo que quiera rellenar. Un enlace a una sección (`?tab=salud`) la abre.
const plegables = reactive({ fisiologia: false, zonas: false, test: false, equipo: false, estilo: false, salud: false });
const lleno = (v) => v !== null && v !== undefined && String(v).trim() !== '' && !(Array.isArray(v) && !v.length);
// «6 de 7» no decía qué faltaba (revisión del 2026-10-21): con uno o dos sin rellenar, se nombran.
const NOMBRE_CORTO = {
  weight_kg: 'peso', height_cm: 'altura', birth_date: 'fecha de nacimiento', gender: 'género',
  resting_heart_rate: 'pulso en reposo', vo2max_running: 'VO₂max corriendo', vo2max_cycling: 'VO₂max en bici',
  lactate_threshold_hr: 'pulso umbral', lactate_threshold_pace_sec_per_km: 'ritmo umbral', ftp_watts: 'FTP',
  swim_threshold_pace_sec_per_100m: 'ritmo nadando', easy_run_pace_fast_sec_per_km: 'rodaje suave',
  terrain: 'terreno', climate: 'clima', altitude_m: 'altitud', treadmill_access: 'cinta',
  avg_sleep_hours: 'sueño', occupation_type: 'trabajo', stress_level_self_report: 'estrés',
  travel_frequency: 'viajes', nutrition_notes: 'alimentación',
};
const deTantos = (campos) => {
  const faltan = campos.filter((c) => !lleno(formData[c]));
  if (!faltan.length) return 'Completo';
  if (faltan.length === campos.length) return 'Sin rellenar';
  if (faltan.length <= 2) return `Puedes añadir: ${faltan.map((c) => NOMBRE_CORTO[c] || c).join(' y ')}`;
  return `${campos.length - faltan.length} de ${campos.length}`;
};
const resumenAvanzado = computed(() => {
  const activas = (formData.current_injuries || []).filter((x) => x.status !== 'resuelta').length;
  return {
    // VO₂max en bici y FTP sólo los tiene quien entrena con potenciómetro: no se cuentan como pendientes.
    fisiologia: deTantos(['weight_kg', 'height_cm', 'birth_date', 'gender', 'resting_heart_rate', 'vo2max_running']),
    // Los cinco campos que enseña la sección, no dos.
    zonas: deTantos(['lactate_threshold_hr', 'lactate_threshold_pace_sec_per_km', 'easy_run_pace_fast_sec_per_km',
      'swim_threshold_pace_sec_per_100m']),
    test: formData.threshold_tests && Object.keys(formData.threshold_tests).length ? 'Hecho' : 'Sin hacer',
    equipo: deTantos(['terrain', 'climate', 'altitude_m', 'treadmill_access']),
    estilo: deTantos(['avg_sleep_hours', 'occupation_type', 'stress_level_self_report', 'travel_frequency', 'nutrition_notes']),
    salud: activas ? `${activas} ${activas === 1 ? 'lesión activa' : 'lesiones activas'}`
      // También lo escrito a mano: con sus lesiones pasadas escritas decía «Nada declarado».
      : (lleno(formData.chronic_conditions) || lleno(formData.medications) || lleno(formData.past_injuries_surgeries)
        || lleno(formData.limitations) || (formData.current_injuries || []).length
        ? 'Con información' : 'Nada declarado'),
  };
});
// Se baja cuando el perfil ya cargó: la sección existe desde el primer pintado,
// pero lo de encima crece al llegar los datos y la dejaba 5.000 px más abajo de
// donde se había saltado (comprobado en el navegador, 375 px de ancho).
const llevarASeccion = async (id) => {
  if (typeof document === 'undefined') return;
  for (let i = 0; i < 60 && !store.profileLoaded; i += 1) {
    await new Promise((r) => setTimeout(r, 150));
  }
  await nextTick();
  await new Promise((r) => setTimeout(r, 250));
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

// `?tab=salud` abre esa pestaña directamente. Existe para poder enlazar una
// seccion concreta desde fuera -- la pantalla de bienvenida lo usa para el
// asistente de salud -- en vez de decirle al atleta "y ahora busca la pestaña
// Salud", que es la instruccion que nadie sigue.
//
// Va DESPUES de `tabs` y no arriba con el resto del paso a paso: con
// `immediate: true` el callback corre durante el setup, y ahi `tabs` todavia
// estaria en su zona muerta temporal. Un `computed` aguanta esa colocacion
// porque es perezoso; un watch inmediato no.
watch(
  () => route.query.tab,
  (pedida) => {
    // `?tab=avanzado` (o cualquiera de las basicas) abre esa pantalla. Las
    // cinco secciones avanzadas ya no son pestañas: viven apiladas en UNA
    // pantalla, asi que el unico destino avanzado posible es 'avanzado'.
    const destinos = [...tabs.map((x) => x.id), 'avanzado'];
    // Una sección de Avanzado se pide por su nombre: la Bienvenida enlaza
    // "Declarar una lesión" a `?tab=salud`, y 'salud' no es una pestaña desde
    // que Avanzado es una sola pantalla -- el enlace dejaba al atleta en
    // Identidad (auditoría del 2026-09-23).
    const seccion = SECCIONES_DE_AVANZADO[pedida];
    const destino = seccion ? 'avanzado' : pedida;
    if (destino && destinos.includes(destino)) {
      activeTab.value = destino;
    }
    if (seccion) {
      plegables[pedida] = true;
      llevarASeccion(seccion);
    }
  },
  { immediate: true },
);

// Los formatos de carrera, del catalogo del cerebro.
//
// 38 formatos con sus tramos en metros -- trail, aguas abiertas, duatlon,
// acuatlon, MTB, contrarreloj -- que ya existian en
// services/ai/race_distances.py y que este desplegable no ofrecia. Se sirve
// como JSON estatico (public/carreras.json, generado) en vez de por una ruta
// del backend: son datos que no cambian por atleta y no merecen un salto mas.
//
// Si la peticion falla se queda vacio y el atleta puede seguir con "Otra / Sin
// especificar" -- un catalogo que no carga no puede impedir guardar el perfil.
const gruposDeCarrera = ref([]);
fetch(`${import.meta.env.BASE_URL}carreras.json`)
  .then((r) => (r.ok ? r.json() : null))
  .then((d) => { gruposDeCarrera.value = (d && d.grupos) || []; })
  .catch(() => { gruposDeCarrera.value = []; });

/** Todas las claves del catalogo, planas. */
const clavesDeCarrera = computed(
  () => new Set(gruposDeCarrera.value.flatMap((g) => g.options.map((o) => o.value))),
);

/**
 * La meta guardada no esta en el catalogo.
 *
 * Pasa con lo que se escribio cuando esto era texto libre ("Olimpico",
 * "Medio Maraton"), y tambien mientras `carreras.json` todavia no ha cargado.
 * En los dos casos se conserva como opcion propia: un desplegable que no puede
 * representar lo que hay guardado lo borraria al primer Guardar.
 */
const metaFueraDelCatalogo = computed(() => {
  const v = (formData.target_distance || '').trim();
  return !!v && !clavesDeCarrera.value.has(v);
});

/** La meta elegida es un formato de triatlon (de ahi sale `triathlon_distance`). */
const esMetaDeTriatlon = computed(() => (formData.target_distance || '').startsWith('tri_'));


// shared style classes (keep the app's look)
// En minúscula y legibles (13 px, contraste alto): las mayúsculas de 11 px en gris costaban leerse en el
// teléfono (revisión del perfil, 2026-10-21).
const labelClass = 'block text-[13px] font-bold text-white/80 mb-1.5';
const inputClass = 'w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors';
const optClass = 'bg-[#17191e] text-white';
const cardClass = 'glass-card rounded-3xl p-4 sm:p-8 space-y-6 border border-white/10';
const sectionHeadClass = 'text-xl font-black text-white tracking-tight border-b border-white/10 pb-4 flex items-center gap-2.5';
const addBtnClass = 'flex items-center gap-1.5 px-4 py-2.5 glass-pill text-[#caf300] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-colors';

const formData = reactive(emptyFormData());

const seed = (p) => {
  Object.assign(formData, buildFormData(p));
  formData.competitions.sort(porFecha);
  nextTick(() => { instantanea.value = fotoDelFormulario(); });
};
seed(store.profile);
watch(() => store.profile, (p) => seed(p));


const completion = computed(() => store.athlete.completionRate ?? store.profile?.completionRate ?? 0);

const semanaAbierta = ref(!guiado.value);
const resumenDeTuSemana = computed(() => {
  const partes = [];
  if (formData.week_start_day) partes.push(`empieza el ${formData.week_start_day.toLowerCase()}`);
  return partes.length ? partes.join(' · ') : 'Opcional';
});
// Con el día de HOY: «la víspera de ese día» en abstracto no se entendía.
const ejemploDeInicioDeSemana = computed(() => {
  const dia = formData.week_start_day;
  if (!dia) return 'Cada semana empieza el día en que la generas.';
  const hoy = WEEK_DAYS[(new Date().getDay() + 6) % 7];
  const i = WEEK_DAYS.indexOf(dia);
  const vispera = WEEK_DAYS[(i + 6) % 7];
  if (hoy === dia) return `Hoy es ${hoy.toLowerCase()}: tus semanas irán de ${dia.toLowerCase()} a ${vispera.toLowerCase()}.`;
  return `Hoy es ${hoy.toLowerCase()}: tu primera semana irá de hoy al ${vispera.toLowerCase()}, y las siguientes de `
    + `${dia.toLowerCase()} a ${vispera.toLowerCase()}.`;
});

const conectaAlContinuar = computed(() => guiado.value && pasoActual.value === 0 && !!formData.garmin_email
  && !!formData.garmin_password && !garminYaConectado.value);
const garminYaConectado = computed(() => garminConnected.value || claveGuardada.value || !!store.athlete?.garminConnected);

const datosDelReloj = computed(() => [
  { etiqueta: 'Pulso en reposo', ayuda: 'al despertar', valor: formData.resting_heart_rate, unidad: 'ppm', color: 'text-white' },
  { etiqueta: 'Pulso en tu umbral corriendo', ayuda: 'el esfuerzo que aguantas ~1 hora', valor: formData.lactate_threshold_hr, unidad: 'ppm', color: 'text-white' },
  { etiqueta: 'Potencia umbral en bici', ayuda: 'FTP; sólo si entrenas con potenciómetro', valor: formData.ftp_watts, unidad: 'W', color: 'text-[#caf300]' },
  { etiqueta: 'VO₂max corriendo', ayuda: 'tu forma aeróbica según Garmin', valor: formData.vo2max_running, unidad: '', color: 'text-[#a5eeff]' },
].filter((d) => d.valor || d.etiqueta !== 'Potencia umbral en bici'));

/**
 * Los campos que faltan, con su etiqueta y su pestaña.
 *
 * La lista la calcula el backend (`ProfileService::missingFields`), que es
 * donde vive la regla de que campos cuentan segun el deporte. Aqui solo se
 * traducen a lo que el atleta ve escrito en el formulario.
 */
const camposQueFaltan = computed(() => {
  const faltan = store.athlete.missingFields || store.profile?.missing_fields || [];
  return faltan
    .map((campo) => ({
      campo,
      etiqueta: ETIQUETAS_DE_CAMPO[campo] || campo,
      tab: PESTANA_DE_CAMPO[campo] || 'identidad',
    }))
    .filter((c) => ETIQUETAS_DE_CAMPO[c.campo]);
});

const irACampo = (c) => {
  activeTab.value = c.tab;
  // «Tu semana» va plegada en el alta: si lo que falta está dentro, se abre.
  if (c.campo === 'week_start_day') semanaAbierta.value = true;
};
/**
 * El atleta escribe y lee el ritmo en min/km ("4:30"), que es como lo dice su
 * reloj y como lo dice cualquier entrenador. Por dentro se sigue guardando en
 * SEGUNDOS: es lo que esperan la base de datos, el esquema del plan y el
 * constructor de entrenos de Garmin.
 *
 * El campo pedia y mostraba los segundos crudos -- "347.2" -- que no se parece
 * a nada de lo que el atleta ve en ningun otro sitio. Los prompts, en cambio,
 * ya recibian min/km desde siempre (ver prescription.py): el unico sitio donde
 * asomaba la unidad interna era este formulario.
 *
 * Escribir se hace en el `set`, asi que el boton de rellenar desde Garmin --
 * que escribe los segundos directamente en formData -- sigue funcionando y se
 * ve formateado sin tocarlo.
 */
/**
 * Un ritmo se escribe en DOS casillas, minutos y segundos.
 *
 * Antes era una sola casilla de texto atada a un `computed` que convertia en
 * cada tecla, y no se podia usar: escribir "1" guardaba 1 SEGUNDO (un numero
 * suelto se leia como segundos) y el campo se reescribia solo como "0:01"
 * antes de que el atleta llegara a la segunda tecla. Y en el movil,
 * `inputmode="numeric"` saca un teclado sin dos puntos, asi que "1:45" era
 * imposible de teclear. Dos casillas numericas funcionan con cualquier
 * teclado y no hay nada que interpretar.
 *
 * Se sigue guardando en SEGUNDOS, que es lo que lee el cerebro, y el boton de
 * Garmin -- que escribe los segundos directamente -- se ve formateado solo.
 */
const partesDeRitmo = (campo) => ({
  min: computed({
    get: () => (Number(formData[campo]) > 0 ? Math.floor(Number(formData[campo]) / 60) : ''),
    set: (v) => {
      const s = Number(formData[campo]) > 0 ? Math.round(Number(formData[campo])) % 60 : 0;
      const m = Number(v);
      formData[campo] = v === '' || !Number.isFinite(m) || m < 0 ? (s || '') : (Math.round(m) * 60 + s) || '';
    },
  }),
  seg: computed({
    get: () => (Number(formData[campo]) > 0 ? Math.round(Number(formData[campo])) % 60 : ''),
    set: (v) => {
      const m = Number(formData[campo]) > 0 ? Math.floor(Number(formData[campo]) / 60) : 0;
      const s = Math.min(59, Math.max(0, Math.round(Number(v) || 0)));
      formData[campo] = (m * 60 + s) || '';
    },
  }),
});
const { min: carreraMin, seg: carreraSeg } = partesDeRitmo('lactate_threshold_pace_sec_per_km');
const { min: nadoMin, seg: nadoSeg } = partesDeRitmo('swim_threshold_pace_sec_per_100m');
const { min: suaveRapidoMin, seg: suaveRapidoSeg } = partesDeRitmo('easy_run_pace_fast_sec_per_km');
const { min: suaveLentoMin, seg: suaveLentoSeg } = partesDeRitmo('easy_run_pace_slow_sec_per_km');

/**
 * Si el atleta ya escribio su marca de 1500 m, el ritmo por 100 m sale de
 * ahi sin preguntarle nada: es la referencia que casi todos tienen y el
 * "ritmo umbral de natacion" una que casi nadie sabe decir. Se OFRECE, no se
 * rellena: una marca de hace dos anos no es su ritmo de hoy.
 */
const ritmoDe1500 = computed(() => {
  const marca = (formData.recent_prs?.marks || []).find((m) => m?.key === 'swim_1500' && m?.time);
  // Con la marca en horas, minutos y segundos ("00:27:30") y el lector único de
  // tiempos: `paceToSec` lee "min:seg" y con tres partes daba 27 SEGUNDOS.
  const seg = marca ? tiempoASegundos(marca.time) : '';
  if (!seg) return null;
  const por100 = Math.round(seg / 15);
  return por100 >= 50 && por100 <= 240 ? { marca: marca.time, seg: por100 } : null;
});

const paceKm = computed(() => secToPace(formData.lactate_threshold_pace_sec_per_km, '').trim());

// El umbral con el que se escribió su plan, y de dónde salió (summary.prescription). Sólo se dice
// cuando NO es el que está escrito aquí: si coincide, no hay nada que aclarar.
const prescripcionDelPlan = ref(null);
const resumenDelPlan = ref(null);
onMounted(async () => {
  try {
    const p = await getPlan();
    const s = typeof p?.summary === 'string' ? JSON.parse(p.summary) : p?.summary;
    resumenDelPlan.value = s || null;
    prescripcionDelPlan.value = s?.prescription || null;
  } catch (e) { prescripcionDelPlan.value = null; }
});

// Sus «sí o sí» que hizo como mucho 1 de 4 semanas (2026-10-23, utils/fijos.js): se marcan en la tabla y se
// ofrece pasarlos a «Puedo» de una vez (se guarda con «Guardar», como cualquier cambio de la tabla).
const fijosQueCasiNoHace = computed(() => fijosPocoHechos(resumenDelPlan.value, formData.sportDays));
const pocoHecho = (dep, dia) => fijosQueCasiNoHace.value.find((f) => f.deporte === dep && f.dia === dia) || null;
const pasarAPuedo = () => {
  const tabla = { ...formData.sportDays };
  fijosQueCasiNoHace.value.forEach((f) => {
    tabla[f.deporte] = { ...(tabla[f.deporte] || {}), [f.dia]: ESTADO_POSIBLE };
  });
  formData.sportDays = tabla;
};
const umbralDelPlan = computed(() => {
  const p = prescripcionDelPlan.value;
  const delPlan = Number(p?.threshold_pace_sec_per_km);
  if (!p || !delPlan) return '';
  const aqui = Number(formData.lactate_threshold_pace_sec_per_km) || 0;
  if (aqui && Math.abs(aqui - delPlan) < 3) return '';
  const ritmo = secToPace(delPlan, '').trim();
  const desde = Number(p.umbral_ajustado_desde) || aqui;
  if (p.threshold_pace_source === 'garmin_ajustado') {
    return { ritmo, porque: `Un poco más rápido que el ${desde ? secToPace(desde, '').trim() : ''} de tu Garmin, porque tus tiempos de carrera dicen que puedes más. No tienes que hacer nada; si lo cambias, usamos el tuyo.` };
  }
  if (p.threshold_pace_source === 'race_prediction') {
    return { ritmo, porque: 'Sale de los tiempos de carrera que estima tu Garmin. No tienes que hacer nada; si lo cambias, usamos el tuyo.' };
  }
  if (p.threshold_pace_source === 'test') return { ritmo, porque: 'De tu test de umbral.' };
  return '';
});
const paceSwim = computed(() => secToPace(formData.swim_threshold_pace_sec_per_100m, '').trim());

const formatDate = (iso) => {
  try {
    return new Date(iso).toLocaleDateString('es', { day: '2-digit', month: 'long', year: 'numeric' });
  } catch (e) {
    return iso;
  }
};

const addCompetition = () => {
  formData.competitions.push({
    // Sin race_type 'Olympic' por defecto: convertia cada 5K en un triatlon para el cerebro.
    name: '', date: '', race_type: '', priority: 'B', distance: '', elevation_gain_m: '', target_time: '',
  });
  carrerasAbiertas.add(formData.competitions[formData.competitions.length - 1]);
};

// --- Las carreras como filas (revisión del 2026-10-21) ------------------------
const PRIORIDADES = [
  { valor: 'A', titulo: 'Principal', texto: 'La que marca tu temporada.' },
  { valor: 'B', titulo: 'Importante', texto: 'Entrenas algo menos la semana antes.' },
  { valor: 'C', titulo: 'De preparación', texto: 'Casi sin bajar el entreno.' },
];
const claseDePrioridad = (p) => ({
  A: 'bg-[#caf300] text-[#121700]',
  B: 'bg-[#a5eeff]/15 text-[#a5eeff] border border-[#a5eeff]/40',
  C: 'bg-white/10 text-white/80',
}[p] || 'bg-white/10 text-white/80');

const hoyIso = (() => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
})();
const yaCorrida = (c) => !!c.date && c.date < hoyIso;
// Por fecha y sin reordenar mientras se escribe: el orden es el de la lista, que se ordena al cargar.
const carrerasQueVienen = computed(() => formData.competitions.filter((c) => !yaCorrida(c)));
const carrerasCorridas = computed(() => formData.competitions.filter(yaCorrida).reverse());
// Declarada como función: `seed` la usa al montar, antes de llegar a esta línea.
function porFecha(a, b) { return (a.date || '9999').localeCompare(b.date || '9999'); }

const carrerasAbiertas = reactive(new Set());
const alternarCarrera = (c, abrir) => (abrir ? carrerasAbiertas.add(c) : carrerasAbiertas.delete(c));
const verCorridas = ref(false);

const idsDeFila = new WeakMap();
let siguienteId = 0;
const claveDeCarrera = (c) => {
  const crudo = toRaw(c);
  if (!idsDeFila.has(crudo)) idsDeFila.set(crudo, `fila-${siguienteId += 1}`);
  return idsDeFila.get(crudo);
};

const etiquetaDeDistancia = (valor) => {
  for (const g of gruposDeCarrera.value) {
    const o = g.options.find((x) => x.value === valor);
    if (o) return { etiqueta: o.label, grupo: g.group };
  }
  return null;
};
const fechaDeFila = (iso) => {
  if (!iso) return 'sin fecha';
  const d = new Date(`${iso}T12:00:00`);
  return Number.isNaN(d.getTime()) ? iso : d.toLocaleDateString('es', { day: 'numeric', month: 'short', year: 'numeric' });
};
// «0:53:00» y no «00:53:00»; «25:40» si no llega a la hora.
const tiempoCorto = (t) => {
  const p = leerTiempo(t);
  if (!p || p.h === '') return '';
  const dos = (x) => String(x).padStart(2, '0');
  return Number(p.h) ? `${Number(p.h)}:${dos(p.m)}:${dos(p.s)}` : `${Number(p.m)}:${dos(p.s)}`;
};
// El deporte delante cuando la distancia sola no lo dice («400 m» puede ser corriendo o nadando).
const distanciaLegible = (valor) => {
  const d = etiquetaDeDistancia(valor);
  if (!d) return '';
  return ['Natación', 'Aguas abiertas'].includes(d.grupo) ? `${d.grupo} ${d.etiqueta}` : d.etiqueta;
};
const tituloDeCarrera = (c) => (c.name || '').trim() || etiquetaDeDistancia(c.distance)?.etiqueta || 'Carrera sin nombre';
const detalleDeCarrera = (c) => [
  fechaDeFila(c.date),
  // La distancia, salvo que ya sea el título (una carrera llamada «10K»).
  etiquetaDeDistancia(c.distance)?.etiqueta !== tituloDeCarrera(c) ? distanciaLegible(c.distance) : '',
  tiempoCorto(c.target_time) ? `objetivo ${tiempoCorto(c.target_time)}` : '',
].filter(Boolean).join(' · ');
const pideDesnivel = (c) => {
  if (lleno(c.elevation_gain_m)) return true;
  const g = etiquetaDeDistancia(c.distance)?.grupo;
  return !c.distance || ['Trail', 'Ciclismo', 'Otros'].includes(g);
};

const quitarCarrera = async (c) => {
  const r = await swal.fire({
    icon: 'question',
    title: `¿Quitar «${tituloDeCarrera(c)}»?`,
    text: 'Se quita al guardar el perfil. Tu plan dejará de prepararla.',
    showCancelButton: true,
    confirmButtonText: 'Quitar',
    cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;
  const i = formData.competitions.indexOf(c);
  if (i !== -1) formData.competitions.splice(i, 1);
  carrerasAbiertas.delete(c);
};

// La distancia de la carrera A que viene antes, para decir si la «principal» no cuadra con ella.
const distanciaDeLaA = computed(() => {
  const a = carrerasQueVienen.value.find((c) => c.priority === 'A' && c.distance);
  const d = a && etiquetaDeDistancia(a.distance);
  return d ? { valor: a.distance, etiqueta: d.etiqueta } : null;
});
/**
 * Al elegir del catalogo se guarda la CLAVE y tambien la etiqueta.
 *
 * La clave es lo que el cerebro puede convertir en metros (race_distances.py);
 * la etiqueta es lo que el atleta reconoce si algun dia esa clave cambia. Con
 * "Otra" no hay clave: el nombre lo escribe el, y sigue valiendo para el prompt
 * aunque no se pueda medir.
 */
const elegirFormato = (m, valor) => {
  if (valor === '__otra__') {
    m.libre = true;
    m.key = '';
    m.label = '';
    return;
  }
  m.libre = false;
  m.key = valor;
  const grupo = gruposDeCarrera.value.find((g) => g.options.some((o) => o.value === valor));
  m.label = grupo ? grupo.options.find((o) => o.value === valor).label : '';
};

// Qué es cada nivel (decisión del responsable, 2026-10-21): sin definición, quien corre 10K y empieza en el
// triatlón no sabía si era principiante o intermedia. Se describe la experiencia, no el ritmo.
const NIVELES_DEFINIDOS = {
  principiante: 'Principiante: llevas menos de un año entrenando con regularidad, o vuelves tras mucho tiempo parado.',
  intermedio: 'Intermedio: entrenas con constancia desde hace uno o más años y ya has hecho alguna carrera.',
  avanzado: 'Avanzado: varios años entrenando estructurado y compites buscando un tiempo.',
  elite: 'Élite: compites a nivel nacional o internacional, o estás cerca de los mejores de tu categoría.',
};
const definicionDelNivel = computed(() => NIVELES_DEFINIDOS[formData.training_age_level]
  || 'Elige el que más se parezca a ti; tu entrenador lo contrasta con lo que dice tu Garmin.');

const marcasAbiertas = reactive(new Set());
const cuantasMarcas = computed(() => {
  const n = formData.recent_prs.marks.length;
  return n === 1 ? '1 marca' : `${n} marcas`;
});
const anadirMarca = () => {
  formData.recent_prs.marks.push(marcaVacia());
  marcasAbiertas.add(formData.recent_prs.marks[formData.recent_prs.marks.length - 1]);
};
const tituloDeMarca = (m) => {
  const formato = m.key ? etiquetaDeDistancia(m.key) : null;
  return (m.label || formato?.etiqueta || '').trim() || 'Marca sin prueba';
};
// El grupo del catálogo dice el deporte: «1500 m» a secas no decía si era corriendo o nadando.
const detalleDeMarca = (m) => [
  m.key ? etiquetaDeDistancia(m.key)?.grupo : '',
  tiempoCorto(m.time),
  (m.date || '').trim(),
].filter(Boolean).join(' · ') || 'sin tiempo todavía';
const iconoDeMarca = (m) => ({
  Carrera: 'directions_run', Trail: 'landscape', 'Triatlón': 'sports_score', 'Natación': 'pool',
  'Aguas abiertas': 'waves', Ciclismo: 'directions_bike',
}[m.key ? etiquetaDeDistancia(m.key)?.grupo : ''] || 'emoji_events');
const quitarMarca = async (m) => {
  const r = await swal.fire({
    icon: 'question',
    title: `¿Quitar «${tituloDeMarca(m)}»?`,
    text: 'Se quita al guardar el perfil.',
    showCancelButton: true,
    confirmButtonText: 'Quitar',
    cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;
  const i = formData.recent_prs.marks.indexOf(m);
  if (i !== -1) formData.recent_prs.marks.splice(i, 1);
  marcasAbiertas.delete(m);
};

const addInjury = () => formData.current_injuries.push({
  location: '', laterality: '', onset: '', pain_scale: '', aggravating_factors: '', treatment: '',
  status: 'activa', notes: '',
});

// Devuelve SI se guardo. El paso a paso lo necesita: antes esto se tragaba el
// error y devolvia undefined, asi que avanzar de apartado sobre un guardado
// fallido le habria hecho perder lo que acababa de escribir.
// --- La contraseña de Garmin, guardada cifrada ---------------------------
//
// Nunca vuelve del servidor: sólo se sabe SI hay una. Escribir en la casilla y
// guardar la reemplaza; "Olvidarla" la borra y el atleta vuelve a escribirla
// cada vez, que es como funcionaba hasta ahora.
const claveGuardada = ref(false);
// Marcada de salida. El atleta pidió expresamente no tener que escribirla cada
// vez, y una casilla que nace apagada significa que nunca se guarda salvo que
// alguien la descubra. Desmarcarla o pulsar "Olvidarla" está a un clic, y
// justo debajo se dice qué protege el cifrado y qué no.
const recordarClave = ref(true);
const claveMsg = ref('');
const claveError = ref(false);

const refrescarEstadoClave = async () => {
  try {
    claveGuardada.value = !!(await getGarminPasswordStatus())?.saved;
    recordarClave.value = recordarClave.value || claveGuardada.value;
  } catch (e) {
    claveGuardada.value = false;
  }
};
refrescarEstadoClave();

const olvidarClave = async () => {
  const r = await swal.fire({
    icon: 'question',
    title: '¿Olvidar tu contraseña de Garmin?',
    text: 'Tendrás que escribirla cada vez que generes un plan o subas al reloj, y sin ella no funcionan la semana automática ni las marcas de «hecha» de tus sesiones.',
    showCancelButton: true,
    confirmButtonText: 'Sí, olvidarla',
    cancelButtonText: 'Cancelar',
  });
  if (!r.isConfirmed) return;
  try {
    await saveGarminPassword('');
    claveGuardada.value = false;
    recordarClave.value = false;
    claveError.value = false;
    claveMsg.value = 'Contraseña olvidada.';
  } catch (e) {
    claveError.value = true;
    claveMsg.value = e?.response?.data?.message || 'No se pudo borrar.';
  }
};

// El guardado de la contraseña va APARTE del perfil y su fallo no puede tumbar
// el guardado del perfil: son dos cosas distintas y sólo una necesita que el
// servicio de IA esté arriba (es quien tiene la clave de cifrado).
const guardarClaveSiToca = async () => {
  const escrita = (formData.garmin_password || '').trim();
  if (!recordarClave.value || !escrita) return;
  try {
    claveGuardada.value = !!(await saveGarminPassword(escrita))?.saved;
    // Lo que diga el servidor, no lo que se esperaba: decía "guardada" con `saved: false`.
    claveError.value = !claveGuardada.value;
    claveMsg.value = claveGuardada.value
      ? 'Contraseña guardada cifrada.'
      : 'No se pudo guardar la contraseña. Pulsa Guardar e inténtalo otra vez; mientras, tendrás que escribirla al generar.';
  } catch (e) {
    claveError.value = true;
    claveMsg.value = e?.response?.data?.message
      || 'No se pudo guardar la contraseña de forma segura. Tendrás que escribirla cada vez.';
  }
};

// La rutina confirmada se guarda EN el momento (Beta 2.1, S4): confirmar y tener
// que acordarse de pulsar Guardar después es perderla. Guarda el perfil entero,
// como el botón de siempre.
const guardarRutinaConfirmada = async (rutina) => {
  formData.strength_routine = rutina;
  formData.strength_routine_texto = rutina.original_text;
  return handleSave();
};

// ¿Hay algo sin guardar? Lo que se mandaría al guardar, contra lo que había al cargar o al último guardado.
const instantanea = ref('');
function fotoDelFormulario() {
  return JSON.stringify(assembleProfilePayload(formData)) + (formData.garmin_password || '');
}
const hayCambios = computed(() => !!instantanea.value && fotoDelFormulario() !== instantanea.value);
const avisarAlCerrar = (e) => { if (hayCambios.value) { e.preventDefault(); e.returnValue = ''; } };
onMounted(() => window.addEventListener('beforeunload', avisarAlCerrar));
onBeforeUnmount(() => window.removeEventListener('beforeunload', avisarAlCerrar));
onBeforeRouteLeave(async () => {
  if (!hayCambios.value) return true;
  const r = await swal.fire({
    icon: 'warning',
    title: 'Tienes cambios sin guardar',
    text: 'Si sales ahora, se pierden.',
    showCancelButton: true,
    showDenyButton: true,
    confirmButtonText: 'Guardar y salir',
    denyButtonText: 'Salir sin guardar',
    cancelButtonText: 'Seguir aquí',
  });
  if (r.isConfirmed) return handleSave({ silencioso: true });
  return r.isDenied;
});

const handleSave = async (opciones = {}) => {
  const silencioso = opciones?.silencioso === true;
  try {
    await store.persistProfile(assembleProfilePayload(formData));
    await guardarClaveSiToca();
    instantanea.value = fotoDelFormulario();
    if (!silencioso) {
      saveToast.value = true;
      setTimeout(() => { saveToast.value = false; }, 3000);
    }
    return true;
  } catch (e) {
    return false;  // el aviso ya lo mostro el store
  }
};

// --- "Lo trajo Garmin" ---------------------------------------------------
//
// El atleta no puede distinguir lo que escribio el de lo que le rellenaron, y
// esa diferencia importa: un peso que trajo el reloj es un dato medido, y uno
// que escribio hace ocho meses puede estar viejo. El distintivo marca la
// procedencia, no bloquea nada.
//
// Se guarda el VALOR importado, no una lista de campos: asi el distintivo
// desaparece solo en cuanto el atleta cambia el numero -- si lo edito, ya no
// es de Garmin -- sin tener que vigilar cada input.
//
// Vive en localStorage y no en la base de datos a proposito: es informativo,
// no cambia el plan, y una columna nueva significa migracion en produccion. Si
// algun dia hace falta que sobreviva al cambio de navegador, ahi se movera.
const claveGarmin = () => `garmin_filled_u${store.authUser?.id ?? 'anon'}`;
const garminValues = ref({});
try {
  garminValues.value = JSON.parse(localStorage.getItem(claveGarmin()) || '{}');
} catch (e) {
  garminValues.value = {};
}
const recordarGarmin = (vals) => {
  garminValues.value = { ...garminValues.value, ...vals };
  try {
    localStorage.setItem(claveGarmin(), JSON.stringify(garminValues.value));
  } catch (e) { /* modo privado: el distintivo simplemente no sobrevive al recargar */ }

  // Y TAMBIEN en el perfil, no solo en este navegador.
  //
  // `localStorage` basta para pintar la insignia GARMIN aqui, pero la
  // PROCEDENCIA la necesita el cerebro: desde la v1.1 el prompt dice
  // "MEASURED" o "self-reported" segun si el volumen semanal lo midio el reloj
  // o lo escribio el atleta, y esa decision se toma leyendo este campo. Sin
  // escribirlo, la rama nueva no se activaba nunca -- un no-op silencioso, que
  // es justo la clase de fallo que este repo persigue.
  //
  // Se acumula en vez de reemplazar: una importacion que trae menos campos que
  // la anterior no convierte en "declarados" los que ya vinieron del reloj.
  const previos = formData._carry.garmin_autofilled_fields || [];
  formData._carry.garmin_autofilled_fields = [...new Set([...previos, ...Object.keys(vals)])];
};
/**
 * Si este campo lo trajo el reloj y el atleta no lo ha cambiado desde.
 *
 * Hay DOS fuentes y hacen falta las dos. `localStorage` guarda los VALORES
 * que trajo la ultima importacion, y sirve para saber si el atleta los ha
 * editado a mano despues. `garmin_autofilled_fields` vive en el perfil, en
 * MySQL, y es la unica que sobrevive a recargar, a cambiar de navegador y a
 * entrar desde el telefono.
 *
 * Mirando solo la primera, el distintivo y la procedencia desaparecian en
 * cuanto se recargaba la pagina: el atleta veia "6.5 h/semana segun lo que
 * declaraste" debajo de un numero que Garmin habia MEDIDO sobre sus ultimas
 * 12 semanas. Se vio en pantalla.
 */
const camposDeGarmin = computed(() => new Set(formData._carry?.garmin_autofilled_fields || []));
const esDeGarmin = (campo) => {
  if (!camposDeGarmin.value.has(campo)) return false;
  const v = garminValues.value[campo];
  // Sin valor recordado en ESTE navegador no se puede comprobar si lo edito,
  // y la constancia del perfil es la que manda: se cree al perfil.
  if (v === undefined || v === null || v === '') return true;
  return String(formData[campo] ?? '') === String(v);
};
const marcaOpcional = 'ml-1.5 inline-block px-1.5 py-0.5 rounded-md align-middle '
  + 'bg-white/5 border border-white/15 text-[#8f9378] text-[9px] font-black tracking-wide uppercase';
// Sólo el reloj, sin la palabra: la insignia de 9 px se repetía 8 veces. La leyenda está arriba de Avanzado.
const badgeGarmin = 'ml-1.5 inline-flex items-center align-middle text-[#a5eeff] [&>*]:!text-[15px] text-[0px]';
const tituloGarmin = 'Lo trajo Garmin al conectar tu cuenta. Puedes corregirlo: '
  + 'en cuanto lo cambies deja de estar marcado.';

const GARMIN_FIELDS = [
  'gender', 'weight_kg', 'height_cm', 'birth_date', 'vo2max_running', 'vo2max_cycling',
  'lactate_threshold_hr', 'lactate_threshold_pace_sec_per_km', 'resting_heart_rate', 'avg_sleep_hours',
  // Deja de ser una estimacion del atleta: sale de sumar la duracion de sus
  // actividades de las ultimas 12 semanas. Ver
  // data_extractor.get_weekly_training_volume -- una sola llamada, sin IA.
  'current_weekly_volume_hours',
];

const handleGarminImport = async () => {
  if (garminImporting.value) return;
  garminImporting.value = true;
  garminMsg.value = '';
  garminError.value = false;
  try {
    const res = await garminImport({
      garmin_email: formData.garmin_email,
      garmin_password: formData.garmin_password,
    });
    const imported = res?.imported || {};
    let n = 0;
    const traidos = {};
    GARMIN_FIELDS.forEach((k) => {
      if (imported[k] !== undefined && imported[k] !== null && imported[k] !== '') {
        formData[k] = imported[k];
        traidos[k] = imported[k];
        n += 1;
      }
    });
    recordarGarmin(traidos);
    const suggested = [
      ...(res?.suggested_available_days || []),
      ...(res?.suggested_long_days || []),
    ];
    // El detalle del volumen, para que la rejilla pueda decir de donde sale el
    // numero y sobre cuantas semanas. No es un campo del perfil.
    volumenGarmin.value = res?.volumen_semanal || null;
    garminError.value = false;
    garminConnected.value = true;
    // Aquí y en ningún sitio mejor: Garmin acaba de aceptarla, así que es la
    // única vez que sabemos con certeza que la contraseña es correcta. Guardar
    // una que no funciona sería peor que no guardar nada — fallaría luego, en
    // mitad de una generación, sin que el atleta entienda por qué.
    await guardarClaveSiToca();
    garminMsg.value = n
      ? `${n} campo(s) auto-completados desde Garmin. Revísalos y pulsa Guardar.`
      : 'Conexión con Garmin correcta, pero no había datos nuevos para completar.';
    if (suggested.length) {
      garminMsg.value += ` Días sugeridos por Garmin: ${[...new Set(suggested)].join(', ')} (aplícalos en Objetivos si quieres).`;
    }
  } catch (e) {
    garminError.value = true;
    garminMsg.value = e?.response?.data?.message || 'No se pudo conectar con Garmin. Revisa el correo y la contraseña.';
  } finally {
    garminImporting.value = false;
  }
};

// --- El test de umbral (Database/023) ------------------------------------
//
// El protocolo NO se escribe aqui: sale de PaginaLocal/services/ai/test_de_umbral.py
// por scripts/generar_catalogo_frontend.py (public/test_de_umbral.json), para que
// lo que el atleta sigue y la cuenta que hace el cerebro digan lo mismo.
//
// Mismo contrato que la importacion de Garmin: se ponen los numeros en el
// formulario y se guardan al pulsar Guardar. Y dejan de estar marcados como "de
// Garmin": un umbral medido en un test es del atleta, y el cerebro lo reconoce
// como tal (prescription.py).
const DEPORTES_DEL_TEST = [
  { id: 'run', label: 'Carrera' },
  { id: 'bike', label: 'Bici' },
  { id: 'swim', label: 'Natación' },
];
const TIEMPOS_DE_NATACION = [
  { id: 't400', label: 'Tu 400 m a tope' },
  { id: 't200', label: 'Tu 200 m a tope' },
];
const protocolosDelTest = ref({});
fetch(`${import.meta.env.BASE_URL}test_de_umbral.json`)
  .then((r) => (r.ok ? r.json() : null))
  .then((d) => { protocolosDelTest.value = d?.protocolos || {}; })
  .catch(() => { protocolosDelTest.value = {}; });

const testDeporte = ref('run');
const protocoloDelTest = computed(() => protocolosDelTest.value[testDeporte.value]
  || { titulo: '', cuando: '', pasos: [], calculo: '' });
const trabajandoTest = ref(false);
const resultadoDelTest = ref(null);
const fechaDelTest = ref('');
const testMsg = ref('');
const testError = ref(false);
const tiemposDeNado = reactive({ t400: { min: '', seg: '' }, t200: { min: '', seg: '' } });

const elegirDeporteDelTest = (id) => {
  testDeporte.value = id;
  resultadoDelTest.value = null;
  testMsg.value = '';
  testError.value = false;
};

const hoyLocal = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

const testGuardado = computed(() => {
  const t = (formData.threshold_tests || {})[testDeporte.value];
  if (!t || !t.fecha) return '';
  const partes = [];
  if (t.lthr) partes.push(`FC umbral ${t.lthr} ppm`);
  if (t.ritmo_s_km) partes.push(`ritmo umbral ${secToPace(t.ritmo_s_km, '').trim()}/km`);
  if (t.ftp_w) partes.push(`FTP ~${t.ftp_w} W`);
  if (t.css_s_100m) partes.push(`CSS ${secToPace(t.css_s_100m, '').trim()}/100 m`);
  return `${t.fecha} · ${partes.join(' · ')}`;
});

const hablarConGarmin = async (accion) => {
  if (trabajandoTest.value) return null;
  trabajandoTest.value = true;
  testMsg.value = '';
  testError.value = false;
  try {
    return await testDeUmbral({
      sport: testDeporte.value,
      action: accion,
      fecha: fechaDelTest.value,
      garmin_password: formData.garmin_password || '',
    });
  } catch (e) {
    testError.value = true;
    testMsg.value = e?.response?.data?.message || 'No se pudo hablar con tu Garmin. Revisa que esté conectado en Identidad.';
    return null;
  } finally {
    trabajandoTest.value = false;
  }
};

const enviarTest = async () => {
  const r = await hablarConGarmin('subir');
  if (!r) return;
  resultadoDelTest.value = null;
  testMsg.value = `Listo: «${r.nombre}» está en tu reloj para el ${r.programado}. Hazlo desde ese entreno del reloj `
    + '(así la app sabe cuál es) y, cuando se sincronice, pulsa «Ya lo hice: leer mi test».';
};

const analizarTest = async () => {
  const r = await hablarConGarmin('analizar');
  if (!r) return;
  resultadoDelTest.value = r.encontrada ? r : null;
  if (!r.encontrada) testMsg.value = r.motivo;
};

/**
 * Pone un test en el perfil respetando que manda el MAS RECIENTE.
 *
 * El test se repite cada pocos meses. El nuevo pasa a ser el vigente si es mas
 * reciente, y el que habia baja a `anteriores`; si es mas viejo (un test de
 * hace meses leido tarde), entra en `anteriores` sin tocar el vigente. Nada se
 * tira. El cerebro vuelve a ordenar por fecha al leer
 * (test_de_umbral.tests_por_fecha), asi que no depende de que esto acierte.
 */
const conElTestNuevo = (deporte, nuevo) => {
  const tests = { ...(formData.threshold_tests || {}) };
  const actual = tests[deporte];
  if (!actual || !actual.fecha) {
    tests[deporte] = { ...nuevo, anteriores: [] };
    return { tests, manda: true };
  }
  const { anteriores = [], ...vigente } = actual;
  if (String(nuevo.fecha) >= String(vigente.fecha)) {
    const mismo = vigente.fecha === nuevo.fecha && vigente.activity_id === nuevo.activity_id;
    tests[deporte] = { ...nuevo, anteriores: mismo ? anteriores : [...anteriores, vigente] };
    return { tests, manda: true };
  }
  tests[deporte] = { ...vigente, anteriores: [...anteriores, nuevo] };
  return { tests, manda: false, vigente };
};

const dejaDeSerDeGarmin = (campos) => {
  formData._carry.garmin_autofilled_fields = (formData._carry.garmin_autofilled_fields || [])
    .filter((c) => !campos.includes(c));
};

const usarTest = (c) => {
  testError.value = false;
  const deporte = testDeporte.value;
  const nuevo = deporte === 'run'
    ? { fecha: c.fecha, activity_id: c.activity_id, lthr: c.lthr, ritmo_s_km: c.ritmo_s_km || null }
    : { fecha: c.fecha, activity_id: c.activity_id, lthr: c.lthr, ftp_w: c.ftp_w || null };
  const { tests, manda, vigente } = conElTestNuevo(deporte, nuevo);
  formData.threshold_tests = tests;
  if (!manda) {
    testMsg.value = `Guardado en tu historial. Tu test del ${vigente.fecha} es más reciente y sigue mandando. `
      + 'Pulsa Guardar para quedártelo.';
    return;
  }
  if (deporte === 'run') {
    formData.lactate_threshold_hr = c.lthr;
    const campos = ['lactate_threshold_hr'];
    if (c.ritmo_s_km) {
      formData.lactate_threshold_pace_sec_per_km = c.ritmo_s_km;
      campos.push('lactate_threshold_pace_sec_per_km');
    }
    dejaDeSerDeGarmin(campos);
    testMsg.value = `Puesto en tu perfil: FC umbral ${c.lthr} ppm${c.ritmo ? ` y ritmo umbral ${c.ritmo}/km` : ''}. `
      + 'Pulsa Guardar para quedártelo. Para que tu reloj también lo use, en Garmin Connect configura tus zonas '
      + `de pulso de carrera basadas en tu FC de umbral de lactato (${c.lthr}).`;
    return;
  }
  if (c.ftp_w) {
    formData.ftp_watts = c.ftp_w;
    dejaDeSerDeGarmin(['ftp_watts']);
  }
  // Como en carrera: el reloj marca las zonas con SU tabla, y un test no la cambia (José, 2026-10-21: test de
  // 156 y las zonas de bici del reloj seguían en las de antes).
  testMsg.value = `Puesto en tu perfil: FC umbral en bici ${c.lthr} ppm${c.ftp_w ? ` y FTP ~${c.ftp_w} W` : ''}. `
    + 'Pulsa Guardar para quedártelo. Para que tu reloj también lo use, en Garmin Connect configura tus zonas '
    + `de pulso de ciclismo basadas en tu FC de umbral de lactato (${c.lthr}).`;
};

/**
 * CSS = (T400 - T200) / 2. Se calcula aqui y en ningun otro sitio: el cerebro
 * solo lee el resultado guardado. Los tiempos los escribe el atleta porque el
 * reloj cuenta los largos por acelerometro (ver prescription._SWIM_CLASS_FRACTIONS).
 */
const segundosDe = (t) => (Number(t.min) || 0) * 60 + (Number(t.seg) || 0);
const cssDelTest = computed(() => {
  const t400 = segundosDe(tiemposDeNado.t400);
  const t200 = segundosDe(tiemposDeNado.t200);
  if (!t400 || !t200) return null;
  if (t400 < 220 || t200 < 100 || t200 * 2 >= t400) {
    return { ok: false, motivo: 'Los tiempos no cuadran: el 400 m tiene que durar más del doble que el 200 m.' };
  }
  const css = (t400 - t200) / 2;
  if (css < 60 || css > 300) return { ok: false, motivo: 'El ritmo que sale no es plausible; revisa los tiempos.' };
  return { ok: true, css_s_100m: Math.round(css * 10) / 10, css: secToPace(css, '').trim(), t400, t200 };
});

const usarCss = () => {
  const c = cssDelTest.value;
  if (!c || !c.ok) return;
  formData.swim_threshold_pace_sec_per_100m = c.css_s_100m;
  // Hoy es siempre el más reciente: el anterior baja al historial.
  formData.threshold_tests = conElTestNuevo(
    'swim', { fecha: hoyLocal(), t400_s: c.t400, t200_s: c.t200, css_s_100m: c.css_s_100m },
  ).tests;
  testError.value = false;
  testMsg.value = `Puesto en tu perfil: CSS ${c.css}/100 m. Pulsa Guardar para quedártelo.`;
};

// El chat del asistente de salud ("el Doctor") se retiró de la pantalla en la v1.1 y
// del cerebro en la Beta 3.0: la pestaña Salud son sólo los campos que el atleta rellena.

</script>
