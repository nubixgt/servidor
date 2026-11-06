<template>
  <div class="space-y-6 animate-fade-in-up">
    <header class="flex items-center gap-3.5 flex-wrap">
      <div class="w-12 h-12 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300] shadow-[0_0_15px_rgba(202,243,0,0.25)] shrink-0">
        <span class="material-symbols-outlined text-[26px]">monitoring</span>
      </div>
      <div class="flex-1 min-w-[220px]">
        <h2 class="text-2xl font-black text-white tracking-tight">Métricas</h2>
        <p class="text-xs text-[#c5c9ac] font-medium">Cómo se usa la app y en qué se va el dinero</p>
      </div>
      <div class="flex items-center gap-1.5 glass-pill rounded-xl p-1">
        <button
          v-for="d in VENTANAS" :key="d"
          @click="cambiarVentana(d)"
          class="px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-wider transition-all"
          :class="dias === d ? 'bg-[#caf300] text-[#121700]' : 'text-[#c5c9ac] hover:text-white'"
        >{{ d }} d</button>
      </div>
    </header>

    <!-- Va aparte del resto: carga y se refresca sola, y se ve aunque MySQL falle. -->
    <SaludDeLaApp :atletas="nombresDeAtletas" />
    <!-- Las consultas de «Pedir ayuda» (Beta 3.0, F3), también aparte del resto. -->
    <ColaDeAyuda />

    <div v-if="cargando" class="glass-panel border border-white/15 rounded-3xl p-10 text-center">
      <span class="material-symbols-outlined text-[#caf300] text-3xl animate-spin">progress_activity</span>
      <p class="text-sm text-[#c5c9ac] mt-2">Leyendo…</p>
    </div>

    <div v-else-if="error" class="glass-panel border border-red-500/40 rounded-3xl p-6">
      <p class="text-sm text-red-300 font-bold">{{ error }}</p>
      <p class="text-xs text-[#8f9378] mt-1">
        Si la tabla no existe todavía, corre <code class="font-mono-data">Database/012_create_app_events.sql</code>.
      </p>
    </div>

    <template v-else-if="m">
      <!-- ── Los cinco números ─────────────────────────────────────────── -->
      <section class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="t in tarjetas" :key="t.label"
             class="glass-panel border border-white/15 rounded-2xl p-4">
          <p class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">{{ t.label }}</p>
          <p class="text-3xl font-black text-white font-mono-data mt-1 leading-none">{{ t.valor }}</p>
          <p class="text-[11px] text-[#c5c9ac] mt-1.5 leading-snug">{{ t.pie }}</p>
        </div>
      </section>

      <!-- ── Embudo ────────────────────────────────────────────────────── -->
      <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">filter_alt</span>
            <span>Hasta dónde llegan</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1">
            Personas distintas, no veces, y <span class="text-[#c5c9ac] font-bold">desde siempre</span> —
            no de la ventana elegida. Un embudo mide progreso acumulado; lo que pasa estos
            {{ dias }} días está más abajo.
          </p>
          <p v-if="m.measuring_since" class="text-[11px] text-amber-300/80 mt-2 leading-relaxed">
            La medición empezó el <span class="font-mono-data">{{ fecha(m.measuring_since) }}</span>.
            Lo que ocurrió antes no está aquí: un escalón en cero junto a un atleta que sí tiene ese plan
            no es un fallo, es que se generó antes de que esto existiera.
          </p>
        </div>
        <div class="space-y-2.5">
          <div v-for="paso in m.funnel" :key="paso.key" class="flex items-center gap-3">
            <span class="w-40 sm:w-52 shrink-0 text-xs text-[#c5c9ac] font-medium">{{ paso.label }}</span>
            <div class="flex-1 h-7 bg-black/40 rounded-lg overflow-hidden border border-white/10 relative">
              <div
                class="h-full bg-gradient-to-r from-[#caf300]/80 to-[#caf300] rounded-lg transition-all duration-700"
                :style="{ width: anchoEmbudo(paso.users) }"
              ></div>
              <span class="absolute inset-y-0 left-2.5 flex items-center text-[11px] font-black font-mono-data text-white mix-blend-difference">
                {{ paso.users }}
              </span>
            </div>
            <span class="w-12 shrink-0 text-right text-[11px] font-mono-data text-[#8f9378]">
              {{ pctEmbudo(paso.users) }}
            </span>
          </div>
        </div>
      </section>

      <!-- ── Lo que el producto ENTREGA ────────────────────────────────── -->
      <!--
        La pregunta que decide si el MVP se sostiene. Todo lo de arriba mide si
        la gente PRUEBA la app: se registra, conecta el reloj, genera un plan.
        Esto mide si le SIRVE. Un plan generado es curiosidad; un plan
        entrenado, y otra semana después, es el producto funcionando. Si el
        embudo va bien y esto va mal, el problema no está en cómo se entra.
      -->
      <section class="glass-panel border rounded-3xl p-5 sm:p-6 space-y-4"
               :class="entrega ? 'border-[#caf300]/35' : 'border-white/15'">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">verified</span>
            <span>Lo que se entrena, no lo que se genera</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1 leading-relaxed">
            Del expediente: semanas ya cerradas contra las que el atleta entrenó de verdad. Es lo único
            del panel que dice si la app <span class="text-[#c5c9ac] font-bold">sirve</span> en vez de si
            se prueba.
          </p>
        </div>

        <p v-if="entregado && !entregado.available"
           class="text-xs text-amber-300/90 bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-3">
          El AiService no respondió, así que el expediente no se puede leer.
          <span v-if="entregado.error" class="block text-[10px] font-mono-data text-[#8f9378] mt-1">{{ entregado.error }}</span>
        </p>

        <p v-else-if="!entrega" class="text-xs text-[#c5c9ac] bg-white/5 border border-white/10 rounded-xl px-4 py-3 leading-relaxed">
          Todavía no hay ninguna semana cerrada. Una semana se cierra cuando se genera la
          <span class="text-white font-bold">siguiente</span>: es entonces cuando se compara lo prescrito
          con lo que Garmin registró. Hasta que un atleta llegue a su segunda semana, este número no
          existe — y es el más importante del panel.
        </p>

        <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div class="glass-panel border border-white/15 rounded-2xl p-4">
            <p class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Semanas entrenadas</p>
            <p class="text-3xl font-black text-[#caf300] font-mono-data mt-1 leading-none">{{ entrega.pct }}%</p>
            <p class="text-[11px] text-[#c5c9ac] mt-1.5">{{ entrega.entrenadas }} de {{ entrega.cerradas }} cerradas</p>
          </div>
          <div class="glass-panel border border-white/15 rounded-2xl p-4">
            <p class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Horas hechas</p>
            <p class="text-3xl font-black text-white font-mono-data mt-1 leading-none">{{ entrega.horasPct }}%</p>
            <p class="text-[11px] text-[#c5c9ac] mt-1.5">
              {{ num(entrega.ejecutadas) }} h de {{ num(entrega.prescritas) }} h prescritas
            </p>
          </div>
          <div class="glass-panel border border-white/15 rounded-2xl p-4">
            <p class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Sesiones de calidad</p>
            <p class="text-3xl font-black text-white font-mono-data mt-1 leading-none">
              {{ entrega.calidadHecha }}<span class="text-[#8f9378] text-xl">/{{ entrega.calidadPrescrita }}</span>
            </p>
            <p class="text-[11px] text-[#c5c9ac] mt-1.5">
              <span v-if="entrega.flojas" class="text-[#ffd479]">{{ entrega.flojas }} entrenadas sin intensidad</span>
              <span v-else>las que llevan el estímulo</span>
            </p>
          </div>
        </div>

        <p v-if="entrega" class="text-[11px] text-[#8f9378] leading-relaxed">
          Las tres dicen cosas distintas: la primera si el atleta vuelve a entrenar, la segunda si la
          dosis le cabe, y la tercera si el estímulo duro le está entrando. Un 90% de horas con la
          calidad cayéndose es un plan que se cumple y no entrena.
        </p>
      </section>

      <!-- ── Retención por cohorte ─────────────────────────────────────── -->
      <!--
        Cada cohorte se compara CONSIGO MISMA, y sólo se pinta la columna que
        esa cohorte ya ha tenido tiempo de alcanzar. Lo anterior contaba, sobre
        todas las cuentas, cuántas habían vuelto alguna vez -- con la que se
        registró ayer dentro del denominador.
      -->
      <!-- EL BUCLE.
           ====================================================================
           Va ANTES que todo lo demas a proposito. El embudo de arriba dice
           cuanta gente llega; este dice si el producto CIERRA su ciclo, que es
           la pregunta que decide el MVP. La beta dejo 37 generaciones y 0
           semanas cerradas, y el panel medía el primer paso y el ultimo -- nada
           de los tres del medio, que es donde estaba la explicacion.

           "Rehechas" es la clave: dos semanas cuyas ventanas se SOLAPAN son la
           misma semana generada dos veces. Ahi se ve el bucle de reintento. -->
      <section v-if="escalones.length" class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">refresh</span>
            <span>El bucle: dónde se cae</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1 leading-relaxed">
            Por semana generada, no por atleta. Sale del expediente y del registro de subidas.
          </p>
        </div>

        <div class="space-y-2">
          <div v-for="e in escalones" :key="e.k" class="flex items-center gap-3">
            <span class="w-44 shrink-0 text-xs text-[#c5c9ac]">{{ e.etiqueta }}</span>
            <div class="flex-1 h-2.5 bg-white/10 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all"
                   :class="e.v === 0 ? 'bg-[#ffb4ab]' : 'bg-[#caf300]'"
                   :style="{ width: `${(e.v / baseBucle) * 100}%` }"></div>
            </div>
            <span class="w-8 text-right text-xs font-mono-data font-black text-white">{{ e.v }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
          <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-[#8f9378]">Rehechas</p>
            <p class="text-xl font-mono-data font-black" :class="bucle.rehechas ? 'text-[#ffb4ab]' : 'text-white'">
              {{ bucle.rehechas }}
            </p>
            <p class="text-[10px] text-[#8f9378]">mismos días, otra vez</p>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-[#8f9378]">Racha máxima</p>
            <p class="text-xl font-mono-data font-black text-white">{{ bucle.racha_maxima }}</p>
            <p class="text-[10px] text-[#8f9378]">semanas seguidas</p>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-[#8f9378]">Con racha ≥2</p>
            <p class="text-xl font-mono-data font-black"
               :class="bucle.cuentas_con_racha >= 3 ? 'text-[#caf300]' : 'text-white'">
              {{ bucle.cuentas_con_racha }}/{{ bucle.cuentas }}
            </p>
            <p class="text-[10px] text-[#8f9378]">el umbral es 3 de 5</p>
          </div>
          <div>
            <p class="text-[10px] font-black uppercase tracking-wider text-[#8f9378]">En curso</p>
            <p class="text-xl font-mono-data font-black text-[#a5eeff]">{{ bucle.en_curso }}</p>
            <p class="text-[10px] text-[#8f9378]">aún no toca juzgarlas</p>
          </div>
        </div>
      </section>

      <!-- INVITACIONES (Beta 3.0, Database/033): el motor de crecimiento que pidió el responsable.
           «Cuentan» = el invitado ya generó su primera semana. -->
      <section v-if="invitaciones?.disponible" class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
          <span class="material-symbols-outlined text-[#caf300] text-[20px]">person_add</span>
          <span>Invitaciones</span>
        </h3>
        <div class="grid grid-cols-3 gap-3">
          <div class="glass-card rounded-2xl border border-white/10 p-3">
            <p class="text-[10px] uppercase font-black text-[#8f9378]">Entraron invitados</p>
            <p class="text-2xl font-black text-white tabular-nums">{{ invitaciones.invitados }}</p>
          </div>
          <div class="glass-card rounded-2xl border border-white/10 p-3">
            <p class="text-[10px] uppercase font-black text-[#8f9378]">Ya entrenan</p>
            <p class="text-2xl font-black text-[#caf300] tabular-nums">{{ invitaciones.activos }}</p>
          </div>
          <div class="glass-card rounded-2xl border border-white/10 p-3">
            <p class="text-[10px] uppercase font-black text-[#8f9378]">Semanas regaladas</p>
            <p class="text-2xl font-black text-white tabular-nums">{{ invitaciones.semanas_regaladas }}</p>
          </div>
        </div>
        <ul v-if="invitaciones.quien_invita?.length" class="space-y-1">
          <li v-for="q in invitaciones.quien_invita" :key="q.nombre" class="flex justify-between text-xs text-[#c5c9ac]">
            <span class="text-white">{{ q.nombre }}</span>
            <span class="tabular-nums">{{ q.activos }} de {{ q.invitados }} entrenan</span>
          </li>
        </ul>
        <p v-else class="text-xs text-[#8f9378]">Nadie ha entrado invitado todavía. Abrir «Invitar» se cuenta como <code>invitar_abierto</code>.</p>
      </section>

      <!-- QUÉ PANTALLAS ABREN (Beta 3.0, F0). Para decidir qué estadísticas
           quitar o cambiar con datos, no con suposiciones. Sin administradores. -->
      <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#a5eeff] text-[20px]">visibility</span>
            <span>Qué pantallas abren</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1 leading-relaxed">
            Vistas y personas distintas por pantalla en la ventana elegida. Se mide desde la Beta 3.0.
          </p>
        </div>
        <p v-if="!pantallas.length" class="text-xs text-[#8f9378]">
          Todavía no hay vistas registradas: aparecen en cuanto los atletas naveguen por la app.
        </p>
        <div v-else class="space-y-2">
          <div v-for="pa in pantallas" :key="pa.screen" class="space-y-1">
            <div class="flex items-baseline justify-between gap-3 text-xs">
              <span class="font-bold text-white">{{ pa.etiqueta }}</span>
              <span class="font-mono-data text-[#c5c9ac]">{{ pa.vistas }} {{ pa.vistas === 1 ? 'vista' : 'vistas' }} · {{ pa.personas }} {{ pa.personas === 1 ? 'persona' : 'personas' }}</span>
            </div>
            <div class="h-2 rounded-full bg-white/5 overflow-hidden">
              <div class="h-full rounded-full bg-[#a5eeff]/70" :style="{ width: `${Math.round(100 * pa.vistas / maxPantalla)}%` }"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- PUERTAS FALSAS.
           ====================================================================
           Lo unico del panel que mide DEMANDA en vez de uso: todo lo demas
           cuenta lo que la gente hizo con lo que ya hay. Los toques y las
           personas dicen cosas distintas y por eso van los dos -- tres toques
           de una persona es un interesado insistente; tres de tres es demanda.

           Regla de retirada: a los 30 dias o a los 3 toques, lo que llegue
           antes. Una puerta falsa permanente es una mentira permanente. -->
      <section v-if="puertas.length || embudoPuertas.length" class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#a5eeff] text-[20px]">door_front</span>
            <span>Puertas falsas · qué piden</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1 leading-relaxed">
            Cosas que todavía no existen. Retira la puerta a los 30 días o a los 3 toques.
          </p>
        </div>
        <!-- Por PERSONAS y sin administradores: la vieron -> la abrieron ->
             dijeron que si. La conversion es la ultima entre la primera; sin
             la primera, "3 toques" podia ser tres de tres o tres de cuarenta. -->
        <div v-if="embudoPuertas.length" class="overflow-x-auto">
          <table class="w-full text-[11px] font-mono-data text-[#8f9378]">
            <thead>
              <tr class="text-[10px] uppercase tracking-wider">
                <th class="text-left font-black py-1 pr-3">Puerta</th>
                <th class="text-right font-black py-1 px-2">La vieron</th>
                <th class="text-right font-black py-1 px-2">La abrieron</th>
                <th class="text-right font-black py-1 px-2">Dijeron sí</th>
                <th class="text-right font-black py-1 pl-2">Conversión</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="p in embudoPuertas" :key="p.door">
                <tr class="border-t border-white/10">
                  <td class="py-1.5 pr-3 text-xs text-[#c5c9ac] font-sans">{{ p.etiqueta }}</td>
                  <td class="text-right px-2 tabular-nums">{{ p.vieron }}</td>
                  <td class="text-right px-2 tabular-nums">{{ p.tocaron }}</td>
                  <td class="text-right px-2 tabular-nums font-black text-white">{{ p.confirmaron }}</td>
                  <td class="text-right pl-2 tabular-nums">{{ p.conversion }}</td>
                </tr>
                <tr v-if="p.door === 'pago_maximo' && resumenDePago(p.respuestas).length">
                  <td colspan="5" class="pb-1 pl-3 text-[11px] text-[#caf300]">
                    <span v-for="m in resumenDePago(p.respuestas)" :key="m.moneda" class="mr-4">
                      {{ m.moneda }}: mediana <b>{{ m.mediana }}</b>/mes · {{ m.personas }}p · de {{ m.minimo }} a {{ m.maximo }}
                    </span>
                  </td>
                </tr>
                <tr v-if="Object.keys(p.respuestas).length">
                  <td colspan="5" class="pb-1.5 pl-3 text-[10px]">
                    <span v-for="(n, r) in p.respuestas" :key="r" class="mr-3">{{ respuestaLegible(r) }}: {{ n }}p</span>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <div v-else class="space-y-3">
          <div v-for="p in puertas" :key="p.clave" class="space-y-1">
            <div class="flex items-center gap-3">
              <span class="w-44 shrink-0 text-xs text-[#c5c9ac]">{{ p.etiqueta }}</span>
              <div class="flex-1 h-2.5 bg-white/10 rounded-full overflow-hidden">
                <div class="h-full bg-[#a5eeff] rounded-full" :style="{ width: `${(p.n / maxPuerta) * 100}%` }"></div>
              </div>
              <span class="w-20 text-right text-[11px] font-mono-data text-[#8f9378]">
                <span class="font-black text-white">{{ p.n }}</span> · {{ p.personas }}p
              </span>
            </div>
            <p v-if="Object.keys(p.respuestas).length" class="pl-3 text-[10px] font-mono-data text-[#8f9378]">
              <span v-for="(n, r) in p.respuestas" :key="r" class="mr-3">{{ respuestaLegible(r) }}: {{ n }}</span>
            </p>
          </div>
        </div>
      </section>

      <!-- LO QUE NOS ESCRIBEN.
           ====================================================================
           El motivo cerrado de borrado suma; esto explica. Cada mensaje con
           quien lo escribio, desde donde y, si fue al borrar, el motivo que
           eligio -- para cruzar "las sesiones no me convencen" con el porque. -->
      <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">rate_review</span>
            <span>Lo que nos escriben</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1">Desde «Ayuda → Opinar» y desde el diálogo de borrado (las consultas de ayuda van en su cola). Los más recientes primero.</p>
        </div>
        <p v-if="!mensajes.length" class="text-xs text-[#c5c9ac] bg-white/5 border border-white/10 rounded-xl px-4 py-3">
          Todavía nadie ha escrito. Si la tabla no existe, corre <code class="font-mono-data">Database/020_feedback_escrito.sql</code>.
        </p>
        <ul v-else class="space-y-2.5 max-h-[520px] overflow-y-auto pr-1">
          <li v-for="f in mensajes" :key="f.id" class="bg-white/5 border border-white/10 rounded-2xl px-4 py-3">
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-black uppercase tracking-wider text-[#8f9378]">
              <span class="text-white">{{ f.user_name }}</span>
              <span>·</span><span class="font-mono-data normal-case">{{ fecha(f.created_at) }}</span>
              <span class="px-2 py-0.5 rounded-md bg-white/10 text-[#c5c9ac]">{{ CONTEXTO_DE_MENSAJE[f.context] || f.context }}</span>
              <span v-if="f.kind" class="px-2 py-0.5 rounded-md bg-[#caf300]/15 text-[#caf300]">{{ TIPO_DE_MENSAJE[f.kind] || f.kind }}</span>
              <span v-if="f.reason" class="px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-300">{{ f.reason }}</span>
              <span v-if="f.page" class="font-mono-data normal-case">{{ f.page }}</span>
            </div>
            <p class="text-sm text-[#f1f0ee] mt-1.5 whitespace-pre-line leading-relaxed">{{ f.message }}</p>
          </li>
        </ul>
      </section>

      <!-- POR QUE BORRAN.
           ====================================================================
           El embudo dice cuantos llegan. Esto dice por que se vuelven atras, y
           es lo unico accionable de los dos: la beta dejo 37 generaciones
           pagadas para 5 atletas -- uno solo hizo 15 -- sin una sola fila que
           dijera el motivo. Cada respuesta apunta a una parte distinta del
           sistema. Ver Database/016. -->
      <section v-if="motivosDeBorrado.length" class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#ffb4ab] text-[20px]">help</span>
            <span>Por qué borran su plan</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1 leading-relaxed">
            Se pregunta al eliminar, y contestar es opcional. Sólo aparecen los que contestaron.
          </p>
        </div>
        <div class="space-y-2">
          <div v-for="r in motivosDeBorrado" :key="r.clave" class="flex items-center gap-3">
            <span class="w-48 shrink-0 text-xs text-[#c5c9ac]">{{ r.etiqueta }}</span>
            <div class="flex-1 h-2.5 bg-white/10 rounded-full overflow-hidden">
              <div class="h-full bg-[#ffb4ab] rounded-full" :style="{ width: `${(r.n / maxMotivo) * 100}%` }"></div>
            </div>
            <span class="w-8 text-right text-xs font-mono-data font-black text-white">{{ r.n }}</span>
          </div>
        </div>
        <details v-if="notasDeBorrado.length" class="text-[11px]">
          <summary class="cursor-pointer text-[#8f9378] hover:text-[#caf300]">
            Lo que escribieron ({{ notasDeBorrado.length }})
          </summary>
          <ul class="mt-2 space-y-1 text-[#c5c9ac]">
            <li v-for="(n, i) in notasDeBorrado" :key="i">— {{ n }}</li>
          </ul>
        </details>
      </section>

      <section v-if="m.cohorts?.length" class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">group</span>
            <span>Quién vuelve</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1 leading-relaxed">
            Por semana de primera entrada. Un guion no es un cero: es que esa cohorte todavía no ha
            llegado a esa semana.
          </p>
        </div>
        <div class="overflow-x-auto -mx-1 px-1">
          <table class="w-full text-xs min-w-[420px]">
            <thead>
              <tr class="text-[10px] uppercase text-[#8f9378] font-black tracking-wider border-b border-white/10">
                <th class="text-left py-2 pr-3">Entraron la semana del</th>
                <th class="text-right py-2 px-2">Personas</th>
                <th class="text-right py-2 px-2">+1 sem</th>
                <th class="text-right py-2 px-2">+2 sem</th>
                <th class="text-right py-2 pl-2">+3 sem</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="c in m.cohorts" :key="c.cohort" class="border-b border-white/5">
                <td class="py-2 pr-3 font-mono-data text-[#c5c9ac]">{{ c.cohort }}</td>
                <td class="py-2 px-2 text-right font-mono-data text-white font-bold">{{ c.size }}</td>
                <td v-for="n in 3" :key="n" class="py-2 px-2 text-right font-mono-data"
                    :class="c.mature_weeks >= n ? 'text-white' : 'text-[#8f9378]'">
                  <template v-if="c.mature_weeks >= n">
                    {{ c[`w${n}`] }}
                    <span class="text-[#8f9378] text-[10px]">({{ Math.round((c[`w${n}`] / c.size) * 100) }}%)</span>
                  </template>
                  <template v-else>—</template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ── Gasto por herramienta ─────────────────────────────────────── -->
      <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
              <span class="material-symbols-outlined text-[#caf300] text-[20px]">payments</span>
              <span>En qué se va el dinero</span>
            </h3>
            <p class="text-[11px] text-[#8f9378] mt-1">
              Costo de proveedor desde el principio, no del periodo elegido. No es una factura.
            </p>
          </div>
          <p v-if="gasto?.available" class="text-2xl font-black text-white font-mono-data">
            {{ usd(gasto.total_cost_usd) }}
          </p>
        </div>

        <p v-if="!gasto?.available" class="text-xs text-amber-300/90 bg-amber-500/10 border border-amber-500/30 rounded-xl px-4 py-3">
          El AiService no respondió, así que el gasto no se puede leer. El resto del panel sale de la base
          de datos y sigue siendo válido.
          <span v-if="gasto?.error" class="block text-[10px] font-mono-data text-[#8f9378] mt-1">{{ gasto.error }}</span>
        </p>

        <div v-else class="space-y-2.5">
          <div v-for="h in herramientas" :key="h.key" class="flex items-center gap-3">
            <span class="w-40 sm:w-52 shrink-0 text-xs text-[#c5c9ac] font-medium truncate">
              {{ h.label }}
              <span v-if="h.automatic_runs" class="block text-[10px] text-[#8f9378] font-mono-data"
                    :title="`${usd(h.automatic_cost_usd)} en semanas que generó el cron, sin nadie delante`">
                {{ h.automatic_runs }} {{ h.automatic_runs === 1 ? 'automática' : 'automáticas' }} · {{ usd(h.automatic_cost_usd) }}
              </span>
            </span>
            <div class="flex-1 h-7 bg-black/40 rounded-lg overflow-hidden border border-white/10 relative">
              <div class="h-full rounded-lg transition-all duration-700"
                   :style="{ width: anchoGasto(h.cost_usd), background: h.color }"></div>
              <span class="absolute inset-y-0 left-2.5 flex items-center text-[11px] font-black font-mono-data text-white mix-blend-difference">
                {{ usd(h.cost_usd) }}
              </span>
            </div>
            <span class="w-28 sm:w-36 shrink-0 text-right text-[10px] font-mono-data text-[#8f9378] leading-tight">
              {{ h.runs }} usos<br class="sm:hidden" />
              <span class="hidden sm:inline"> · </span>{{ usdUnidad(h.avg_cost_usd) }} c/u
            </span>
          </div>
          <p class="text-[11px] text-[#8f9378] pt-1 leading-relaxed">
            La columna de la derecha es la que decide qué es sostenible: un total alto repartido entre
            muchos usos baratos no es el mismo problema que uno concentrado en pocos caros. El chat es
            el único sin límite de frecuencia.
          </p>
          <p v-if="ahorroCache" class="text-[11px] text-[#caf300]/90 bg-[#caf300]/10 border border-[#caf300]/25 rounded-xl px-3.5 py-2.5 leading-relaxed">
            <span class="font-black">Caché del chat activa.</span>
            {{ fmtInt(ahorroCache.leidos) }} tokens servidos desde caché
            (a la décima parte) y {{ fmtInt(ahorroCache.escritos) }} escritos.
            Si estos números dejan de crecer, la caché dejó de acertar y la factura sube sin avisar.
          </p>
        </div>
      </section>

      <!-- ── Actividad diaria ──────────────────────────────────────────── -->
      <section v-if="m.daily?.length" class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
          <span class="material-symbols-outlined text-[#caf300] text-[20px]">show_chart</span>
          <span>Actividad por día</span>
        </h3>
        <div class="flex items-end gap-1 h-32">
          <div v-for="d in m.daily" :key="d.day" class="flex-1 flex flex-col items-center gap-1 group min-w-0">
            <div class="w-full bg-[#caf300]/70 group-hover:bg-[#caf300] rounded-t transition-colors"
                 :style="{ height: altoBarra(d.events) }"
                 :title="`${d.day} · ${d.events} acciones · ${d.users} atletas`"></div>
            <span class="text-[8px] font-mono-data text-[#8f9378] truncate w-full text-center">{{ diaCorto(d.day) }}</span>
          </div>
        </div>
      </section>

      <!-- ── Por atleta ────────────────────────────────────────────────── -->
      <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div>
          <h3 class="flex items-center gap-2 text-sm font-black text-white uppercase tracking-wider">
            <span class="material-symbols-outlined text-[#caf300] text-[20px]">group</span>
            <span>Atleta por atleta</span>
          </h3>
          <p class="text-[11px] text-[#8f9378] mt-1">
            Salen todos, también quien no ha hecho nada — que en un MVP es la fila más informativa.
          </p>
        </div>
        <div class="overflow-x-auto -mx-1 px-1">
          <table class="w-full text-xs min-w-[720px]">
            <thead>
              <tr class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider border-b border-white/10">
                <th class="text-left py-2 pr-3">Atleta</th>
                <th class="text-right py-2 px-2">Perfil</th>
                <th class="text-right py-2 px-2">Acciones</th>
                <th class="text-right py-2 px-2">Chats</th>
                <th class="text-right py-2 px-2" title="Macrociclos y semanas, también las que generó el cron">Planes</th>
                <th class="text-right py-2 px-2">Costo</th>
                <th class="text-left py-2 pl-3">Última vez</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in usuarios" :key="u.user_id"
                  class="border-b border-white/5 hover:bg-white/5 transition-colors">
                <td class="py-2.5 pr-3">
                  <span class="text-white font-bold">{{ u.name }}</span>
                  <span v-if="u.role === 'admin'" class="ml-1.5 text-[9px] font-black uppercase text-[#caf300]">admin</span>
                  <span v-if="u.status !== 'approved'"
                        class="ml-1.5 text-[9px] font-black uppercase text-amber-400">{{ u.status }}</span>
                  <span class="block text-[10px] text-[#8f9378] truncate max-w-[220px]">{{ u.email }}</span>
                </td>
                <td class="text-right px-2 font-mono-data" :class="u.completion_rate ? 'text-[#c5c9ac]' : 'text-[#8f9378]/50'">
                  {{ u.completion_rate }}%
                </td>
                <td class="text-right px-2 font-mono-data text-[#c5c9ac]">{{ u.events }}</td>
                <td class="text-right px-2 font-mono-data text-[#c5c9ac]">{{ u.chats }}</td>
                <td class="text-right px-2 font-mono-data text-[#c5c9ac]">
                  {{ u.plans }}<span v-if="u.auto_plans" class="text-[10px] text-[#8f9378]"
                    :title="`${u.auto_plans} generada(s) sola(s)`"> ({{ u.auto_plans }} auto)</span>
                </td>
                <td class="text-right px-2 font-mono-data" :class="costeDe(u) ? 'text-white font-bold' : 'text-[#8f9378]/50'">
                  {{ costeDe(u) ? usd(costeDe(u)) : '—' }}
                </td>
                <td class="pl-3 font-mono-data text-[10px]"
                    :class="u.last_login_at ? 'text-[#c5c9ac]' : 'text-[#8f9378]/50'">
                  {{ u.last_login_at ? fecha(u.last_login_at) : 'nunca entró' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="gastoSinCuenta" class="text-[11px] text-[#8f9378] leading-relaxed">
          Hay {{ usd(gastoSinCuenta) }} de gasto anterior a que existieran las cuentas, sin atribuir a
          nadie. Se deja aparte a propósito: cargárselo a alguien sería peor que no atribuirlo.
        </p>
      </section>
    </template>
  </div>
</template>

<script setup>
import { num } from '../../utils/garmin';
import { ref, computed, onMounted } from 'vue';
import { getDelivery, getLoopFunnel, getMetrics, getReferrals, getSpending } from '../../services/metrics';
import { getFeedback } from '../../services/feedback';
import SaludDeLaApp from '../../components/admin/SaludDeLaApp.vue';
import ColaDeAyuda from '../../components/admin/ColaDeAyuda.vue';

const VENTANAS = [7, 30, 90];
const dias = ref(30);
const m = ref(null);
const gasto = ref(null);
const invitaciones = ref(null);
const entregado = ref(null);
const mensajes = ref([]);
const cargando = ref(true);
const error = ref('');

// Un color por herramienta, estable. El chat va en ámbar porque es la única
// sin límite de frecuencia: si algún día se come la barra, se tiene que ver
// que es ESA la que creció.
const COLORES = {
  week: '#caf300',
  season: '#8fd14f',
  chat: '#f0a830',
  health_intake: '#5fa8d3',
};

// Dos formatos y no uno: en una columna de totales, "$0.1331" junto a "$5.32"
// se lee como si fueran magnitudes distintas. Los totales van a dos decimales
// (cuatro solo cuando redondear los dejaria en "$0.00", que el lector
// interpreta como gratis) y el coste POR USO siempre a cuatro, porque ahi la
// cuarta cifra es justo la que decide si algo es sostenible.
const usd = (n) => {
  const v = Number(n || 0);
  return `$${v > 0 && v < 0.01 ? v.toFixed(4) : v.toFixed(2)}`;
};
const usdUnidad = (n) => `$${Number(n || 0).toFixed(4)}`;
const fmtInt = (n) => Number(n || 0).toLocaleString('es');
const fecha = (s) => (s ? String(s).slice(0, 16).replace('T', ' ') : '');
const diaCorto = (s) => String(s || '').slice(5);
const CONTEXTO_DE_MENSAJE = {
  general: 'Ayuda → Opinar',
  borrar_semana: 'Al borrar la semana',
  borrar_plan: 'Al borrar el plan',
  pago: 'Por qué pagaría eso',
};
const TIPO_DE_MENSAJE = { fallo: 'Algo falla', sugerencia: 'Idea', me_gusta: 'Le gusta' };

const cargar = async () => {
  cargando.value = true;
  error.value = '';
  try {
    // El gasto se pide en paralelo y su fallo NO tumba el panel: por eso va en
    // su propio catch en vez de en el Promise.all de arriba.
    const [uso, spend, entrega, bucleRes, escritos] = await Promise.all([
      getMetrics(dias.value),
      getSpending().catch((e) => ({ available: false, error: e?.message || 'sin respuesta' })),
      getDelivery().catch((e) => ({ available: false, error: e?.message || 'sin respuesta' })),
      getLoopFunnel().catch((e) => ({ available: false, error: e?.message || 'sin respuesta' })),
      getFeedback().catch(() => []),
    ]);
    invitaciones.value = await getReferrals().catch(() => ({ disponible: false }));
    mensajes.value = escritos;
    m.value = uso;
    gasto.value = spend;
    entregado.value = entrega;
    bucle.value = bucleRes;
  } catch (e) {
    error.value = e?.response?.data?.message || e?.message || 'No se pudieron leer las métricas';
  } finally {
    cargando.value = false;
  }
};

const cambiarVentana = (d) => { dias.value = d; cargar(); };
onMounted(cargar);

// Las cuatro cifras de cabecera, elegidas por lo que hacen decidir.
//
// La de "Volvieron" que habia aqui contaba, sobre TODAS las cuentas aprobadas,
// cuantas habian entrado otro dia -- incluida la que se registro ayer, que no
// ha tenido tiempo material de volver y entraba contada como que no volvio. Ese
// numero bajaba al ganar usuarios, que es lo contrario de lo que hace una
// retencion. La retencion de verdad esta abajo, por cohortes; aqui va la
// ACTIVACION, que es la pregunta anterior: cuantos llegan al momento en que la
// app vale algo.
const tarjetas = computed(() => {
  if (!m.value) return [];
  const s = m.value.signups || {};
  const a = m.value.activation || {};
  const activos = m.value.active?.users ?? 0;
  const mediana = a.median_days_to_value;
  return [
    { label: 'Cuentas', valor: s.approved ?? 0,
      pie: s.pending ? `${s.pending} esperando aprobación` : 'ninguna pendiente' },
    { label: `Activos (${dias.value} d)`, valor: activos,
      pie: 'hicieron algo en la ventana' },
    { label: 'Activados', valor: `${a.activated ?? 0}/${a.approved ?? 0}`,
      pie: 'han generado su primera semana' },
    { label: 'Días hasta la 1ª semana',
      valor: mediana === null || mediana === undefined ? '—' : mediana,
      pie: mediana === null || mediana === undefined
        ? 'todavía nadie ha llegado'
        : `mediana · ${a.same_day} el mismo día` },
  ];
});

// Lo que el producto ENTREGA. Todo lo de arriba dice si la gente PRUEBA la
// app; esto dice si le sirve. Sale del expediente del AiService, no de MySQL.
const entrega = computed(() => {
  const e = entregado.value;
  if (!e?.available || !e.weeks_closed) return null;
  return {
    cerradas: e.weeks_closed,
    entrenadas: e.weeks_trained,
    pct: Math.round((e.weeks_trained / e.weeks_closed) * 100),
    prescritas: e.prescribed_hours,
    ejecutadas: e.executed_hours,
    horasPct: e.prescribed_hours
      ? Math.round((e.executed_hours / e.prescribed_hours) * 100) : 0,
    calidadPrescrita: e.quality_prescribed,
    calidadHecha: e.quality_done,
    flojas: e.under_intensity,
  };
});

const bucle = ref(null);

// Los cinco escalones, en el orden del bucle. `en_curso` no es un escalon: es
// la semana que todavia no ha ocurrido, y se dice aparte para que nadie lea un
// cero como un fallo cuando es sencillamente que aun no toca.
const escalones = computed(() => {
  const b = bucle.value;
  if (!b || b.available === false) return [];
  return [
    { k: 'generadas', etiqueta: 'Generó la semana', v: b.generadas },
    { k: 'subidas', etiqueta: 'La subió al reloj', v: b.subidas },
    { k: 'entrenadas', etiqueta: 'Entrenó algo de ella', v: b.entrenadas },
    { k: 'continuadas', etiqueta: 'Encadenó la siguiente', v: b.continuadas },
  ];
});
const baseBucle = computed(() => Math.max(1, ...escalones.value.map((e) => e.v)));

const ETIQUETAS_DE_PUERTA = {
  invitar: 'Invitar a un atleta',
  pago_maximo: '¿Cuánto pagarías? (máximo al mes)',
  // Retirada el 2026-09-13: enseñaba nuestros costes y anclaba la respuesta.
  // Se sigue etiquetando para leer las filas que ya existen.
  precio: '¿Qué costaría esto? (retirada)',
  app_movil: 'App en el teléfono',
  otros_relojes: 'Polar · Coros · Suunto',
  video_ejercicio: 'Vídeo del ejercicio',
  aguas_abiertas: 'Aguas abiertas',
  nutricion: 'Qué comer esta semana',
  plan_carrera: 'Estrategia para el día de carrera',
};

const embudoPuertas = computed(() => (m.value?.door_funnel || [])
  .map((p) => ({
    ...p,
    etiqueta: ETIQUETAS_DE_PUERTA[p.door] || p.door,
    conversion: p.vieron ? `${Math.round((p.confirmaron / p.vieron) * 100)}%` : '—',
  }))
  .sort((a, b) => b.confirmaron - a.confirmaron || b.vieron - a.vieron));

// Un monto de `pago_maximo` llega como "25_usd"; se enseña como "25 USD/mes".
// Cualquier otra respuesta sale tal cual.
const respuestaLegible = (r) => {
  const m = /^(\d+)_([a-z]{3})$/.exec(String(r));
  return m ? `${m[1]} ${m[2].toUpperCase()}/mes` : r;
};

// La disposición a pagar, por moneda: mediana, rango y personas. `respuestas`
// trae cuántas PERSONAS dieron cada monto exacto, así que la mediana se pondera
// por personas — si cuatro dicen 10 y una dice 100, la respuesta es 10, que es
// justo lo que una media escondería. No se convierte entre monedas: un tipo de
// cambio metido aquí sería un número nuestro, no del atleta.
const resumenDePago = (respuestas) => {
  const porMoneda = {};
  for (const [r, n] of Object.entries(respuestas || {})) {
    const m = /^(\d+)_([a-z]{3})$/.exec(r);
    if (!m) continue;
    const lista = porMoneda[m[2]] || (porMoneda[m[2]] = []);
    for (let i = 0; i < Number(n || 0); i += 1) lista.push(Number(m[1]));
  }
  return Object.entries(porMoneda).map(([moneda, montos]) => {
    const orden = [...montos].sort((a, b) => a - b);
    const mitad = Math.floor(orden.length / 2);
    const mediana = orden.length % 2 ? orden[mitad] : (orden[mitad - 1] + orden[mitad]) / 2;
    return {
      moneda: moneda.toUpperCase(),
      mediana,
      personas: orden.length,
      minimo: orden[0],
      maximo: orden[orden.length - 1],
    };
  });
};

// `detail` llega como "puerta" o "puerta:respuesta". Se agrupa por PUERTA y se
// guardan las respuestas aparte: saber cuántos dijeron cada cosa es justo el
// dato que se compró.
const puertas = computed(() => {
  const suma = {};
  for (const r of (m.value?.door_knocks || [])) {
    const [clave, respuesta] = String(r.detail || '').split(':');
    if (!clave) continue;
    const fila = suma[clave] || (suma[clave] = { clave, n: 0, personas: 0, respuestas: {} });
    fila.n += Number(r.n || 0);
    fila.personas = Math.max(fila.personas, Number(r.personas || 0));
    if (respuesta) fila.respuestas[respuesta] = (fila.respuestas[respuesta] || 0) + Number(r.n || 0);
  }
  return Object.values(suma)
    .map((f) => ({ ...f, etiqueta: ETIQUETAS_DE_PUERTA[f.clave] || f.clave }))
    .sort((a, b) => b.n - a.n);
});
const maxPuerta = computed(() => Math.max(1, ...puertas.value.map((p) => p.n)));

// Qué pantallas abren (Beta 3.0, F0). Los nombres son los del router.
const ETIQUETAS_DE_PANTALLA = {
  AdminDashboard: 'Inicio',
  AdminPlan: 'Plan',
  AdminProfile: 'Perfil',
  AdminCuaderno: 'Cuaderno',
  AdminAnalytics: 'Progreso',
  AdminReports: 'Informes',
  AdminHistorial: 'Historial',
  AdminWelcome: 'Bienvenida',
};
const pantallas = computed(() => (m.value?.screen_views || [])
  .map((f) => ({ ...f, etiqueta: ETIQUETAS_DE_PANTALLA[f.screen] || f.screen })));
const maxPantalla = computed(() => Math.max(1, ...pantallas.value.map((pa) => pa.vistas)));

const baseEmbudo = computed(() => Math.max(1, ...(m.value?.funnel || []).map((f) => f.users)));

// Los motivos llegan como "scope:clave — nota" (una fila por texto distinto),
// asi que hay que agrupar por CLAVE: "week:volumen" y "all:volumen" son el
// mismo motivo con distinto alcance, y contarlos aparte partiria el dato justo
// donde deja de ser util.
const ETIQUETAS_DE_MOTIVO = {
  dias_deportes: 'No son sus días o deportes',
  volumen: 'Volumen: mucho o poco (hasta el 2026-10-20)',
  poco: 'Se le queda corta',
  mucho: 'Es demasiado',
  sesiones: 'Las sesiones no convencen',
  salud: 'Lesión o se sintió mal',
  otra: 'Otra cosa',
};

const motivosCrudos = computed(() => m.value?.delete_reasons || []);

const motivosDeBorrado = computed(() => {
  const suma = {};
  for (const r of motivosCrudos.value) {
    const clave = String(r.detail || '').split('—')[0].trim().split(':').pop().trim();
    if (!clave) continue;
    suma[clave] = (suma[clave] || 0) + Number(r.n || 0);
  }
  return Object.entries(suma)
    .map(([clave, n]) => ({ clave, n, etiqueta: ETIQUETAS_DE_MOTIVO[clave] || clave }))
    .sort((a, b) => b.n - a.n);
});

const maxMotivo = computed(() => Math.max(1, ...motivosDeBorrado.value.map((r) => r.n)));

/** Lo que escribieron a mano, que es donde estan los motivos que no previmos. */
const notasDeBorrado = computed(() => motivosCrudos.value
  .map((r) => String(r.detail || '').split('—').slice(1).join('—').trim())
  .filter(Boolean));
const anchoEmbudo = (n) => `${Math.max(n ? 4 : 0, (n / baseEmbudo.value) * 100)}%`;
const pctEmbudo = (n) => (baseEmbudo.value ? `${Math.round((n / baseEmbudo.value) * 100)}%` : '—');

const herramientas = computed(() => {
  const bk = gasto.value?.by_kind || {};
  return Object.entries(bk)
    .map(([key, v]) => ({ key, ...v, color: COLORES[key] || '#8f9378' }))
    .sort((a, b) => b.cost_usd - a.cost_usd);
});
const maxGasto = computed(() => Math.max(1e-9, ...herramientas.value.map((h) => h.cost_usd)));
const anchoGasto = (n) => `${Math.max(n ? 6 : 0, (n / maxGasto.value) * 100)}%`;

// Se enseña sólo si de verdad hubo caché: en cero, la frase sobra.
const ahorroCache = computed(() => {
  const leidos = herramientas.value.reduce((a, h) => a + (h.cache_read_tokens || 0), 0);
  const escritos = herramientas.value.reduce((a, h) => a + (h.cache_creation_tokens || 0), 0);
  return leidos || escritos ? { leidos, escritos } : null;
});

// El libro identifica al atleta como "u<id>"; la tabla de usuarios, por id.
const costePorUsuario = computed(() => {
  const mapa = {};
  for (const f of gasto.value?.by_user || []) {
    const id = /^u(\d+)$/.exec(f.user_id || '')?.[1];
    if (id) mapa[Number(id)] = f.cost_usd;
  }
  return mapa;
});
// {user_id: nombre} para que el panel de salud diga quién, no "#2".
const nombresDeAtletas = computed(() =>
  Object.fromEntries((m.value?.users || []).map((u) => [String(u.user_id), u.name])),
);
const costeDe = (u) => costePorUsuario.value[u.user_id] || 0;
const gastoSinCuenta = computed(
  () => (gasto.value?.by_user || []).find((f) => f.user_id === 'sin_cuenta')?.cost_usd || 0,
);

// Ordenados por lo que más dice de un MVP: primero quien más usa la app.
const usuarios = computed(() =>
  [...(m.value?.users || [])].sort((a, b) => b.events - a.events || costeDe(b) - costeDe(a)),
);

// Altura en PIXELES y no en porcentaje. Un `height: X%` dentro de un flex con
// `items-end` se resuelve contra un padre sin altura definida y sale cero --
// ya pasó en las gráficas del atleta.
const ALTO_BARRA = 112;
const maxDia = computed(() => Math.max(1, ...(m.value?.daily || []).map((d) => d.events)));
const altoBarra = (n) => `${Math.max(n ? 3 : 2, Math.round((n / maxDia.value) * ALTO_BARRA))}px`;
</script>
