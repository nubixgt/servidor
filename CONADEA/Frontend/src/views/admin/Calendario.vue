<template>
  <div>
    <div class="fila-seccion">
      <div>
        <h2>Calendario · {{ tituloMes }}</h2>
        <p class="subtitulo-admin">Tus horarios de estudio por curso</p>
      </div>
      <div class="nav-mes">
        <button class="btn-icono" title="Mes anterior" @click="cambiarMes(-1)">‹</button>
        <button class="btn-hoy" @click="irAHoy">Hoy</button>
        <button class="btn-icono" title="Mes siguiente" @click="cambiarMes(1)">›</button>
      </div>
    </div>

    <p v-if="error" class="aviso-error">{{ error }}</p>

    <div class="cal-grid-layout">
      <!-- Calendario -->
      <div class="vidrio">
        <div class="cal-grid">
          <div v-for="(d, i) in diasSemana" :key="i" class="enc">{{ d }}</div>
          <div v-for="n in desfase" :key="`v${n}`"></div>
          <button
            v-for="dia in diasEnMes"
            :key="dia"
            class="cal-dia mes"
            :class="{
              evento: sesionesDelDia(dia).length > 0,
              hoy: esHoy(dia),
              elegido: dia === diaElegido
            }"
            @click="diaElegido = dia === diaElegido ? null : dia"
          >
            {{ dia }}
            <span v-if="sesionesDelDia(dia).length" class="puntos">
              <i v-for="s in sesionesDelDia(dia).slice(0, 3)" :key="s.curso_id" :class="{ pausado: !s.activo }"></i>
            </span>
          </button>
        </div>
        <p class="leyenda">
          🟩 Días con estudio programado · Borde azul: hoy · Toca un día para ver el detalle
        </p>

        <!-- Detalle del día elegido -->
        <div v-if="diaElegido" class="detalle-dia">
          <b>{{ textoDiaElegido }}</b>
          <p v-if="!sesionesDelDia(diaElegido).length" class="vacio">No tienes estudio programado este día.</p>
          <div v-for="s in sesionesDelDia(diaElegido)" :key="s.curso_id" class="evento-linea">
            <div class="fecha-caja"><b>{{ horaCorta(s.hora) }}</b></div>
            <div>
              <b class="titulo-sesion">{{ iconoCurso(s.curso_id) }} {{ s.curso_titulo }}</b>
              <br><small class="suave">{{ s.duracion_minutos }} min<template v-if="!s.activo"> · avisos pausados</template></small>
            </div>
          </div>
        </div>
      </div>

      <!-- Mis horarios -->
      <div class="vidrio">
        <div class="cab-tarjeta">
          <h3>Mis horarios</h3>
          <button v-if="!editando" class="btn-continuar-sm" @click="nuevoHorario">＋ Agregar</button>
        </div>

        <!-- Formulario -->
        <form v-if="editando" class="form-horario" @submit.prevent="guardar">
          <div class="campo">
            <label>Curso</label>
            <select v-model="form.cursoId" :disabled="formEsEdicion" required>
              <option value="" disabled>Elige un curso</option>
              <option v-for="c in cursosDisponibles" :key="c.id" :value="c.id">{{ c.icono }} {{ c.titulo }}</option>
            </select>
          </div>

          <div class="campo">
            <label>Días</label>
            <div class="chips-dias">
              <button
                v-for="d in DIAS"
                :key="d.k"
                type="button"
                class="chip-dia"
                :class="{ activo: form.dias.includes(d.k) }"
                @click="alternarDia(d.k)"
              >{{ d.n }}</button>
            </div>
          </div>

          <div class="fila-2">
            <div class="campo">
              <label>Hora</label>
              <input v-model="form.hora" type="time" required />
            </div>
            <div class="campo">
              <label>Duración (min)</label>
              <input v-model.number="form.duracion" type="number" min="5" max="180" step="5" required />
            </div>
          </div>

          <div class="acciones-form">
            <button type="button" class="btn-outline" @click="cancelar">Cancelar</button>
            <button type="submit" class="btn-verde" :disabled="guardando">
              {{ guardando ? 'Guardando…' : 'Guardar' }}
            </button>
          </div>
        </form>

        <!-- Lista -->
        <template v-else>
          <p v-if="cargando" class="vacio">Cargando…</p>
          <p v-else-if="!horarios.length" class="vacio">
            Aún no tienes horarios. Agrega uno para recibir tu recordatorio de estudio por WhatsApp
            10 minutos antes.
          </p>
          <div v-for="h in horarios" :key="h.curso_id" class="evento-linea" :class="{ pausado: !h.activo }">
            <div class="fecha-caja"><b>{{ horaCorta(h.hora) }}</b></div>
            <div class="info-horario">
              <b class="titulo-sesion">{{ iconoCurso(h.curso_id) }} {{ h.curso_titulo }}</b>
              <br><small class="suave">{{ nombresDias(h.dias) }} · {{ h.duracion_minutos }} min</small>
            </div>
            <div class="acciones-horario">
              <button class="btn-icono" title="Editar" @click="editar(h)">✏️</button>
              <button class="btn-icono" :title="h.activo ? 'Pausar avisos' : 'Reactivar avisos'" @click="alternarActivo(h)">
                {{ h.activo ? '🔔' : '🔕' }}
              </button>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import horarioService from '../../services/horarioService.js';
import { useCursosStore } from '../../stores/cursos.js';
import { alertaError, alertaExito } from '../../utils/alertas.js';

const cursosStore = useCursosStore();

// k = letra que guarda el Backend (L M X J V S D) · n = nombre corto
const DIAS = [
  { k: 'L', n: 'Lun' }, { k: 'M', n: 'Mar' }, { k: 'X', n: 'Mié' }, { k: 'J', n: 'Jue' },
  { k: 'V', n: 'Vie' }, { k: 'S', n: 'Sáb' }, { k: 'D', n: 'Dom' }
];
// Date.getDay(): 0 = domingo … 6 = sábado
const LETRA_POR_GETDAY = ['D', 'L', 'M', 'X', 'J', 'V', 'S'];
const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const hoy = new Date();
const anio = ref(hoy.getFullYear());
const mes = ref(hoy.getMonth()); // 0-11
const diaElegido = ref(null);

const horarios = ref([]);
const cargando = ref(false);
const error = ref('');

const editando = ref(false);
const formEsEdicion = ref(false);
const guardando = ref(false);
const form = ref({ cursoId: '', dias: [], hora: '19:00', duracion: 15 });

const tituloMes = computed(() => {
  const t = new Date(anio.value, mes.value, 1).toLocaleDateString('es-GT', { month: 'long', year: 'numeric' });
  return t.charAt(0).toUpperCase() + t.slice(1);
});
const diasEnMes = computed(() => new Date(anio.value, mes.value + 1, 0).getDate());
// Casillas vacías antes del día 1 (la semana empieza en lunes).
const desfase = computed(() => (new Date(anio.value, mes.value, 1).getDay() + 6) % 7);

const textoDiaElegido = computed(() => {
  const t = new Date(anio.value, mes.value, diaElegido.value)
    .toLocaleDateString('es-GT', { weekday: 'long', day: 'numeric', month: 'long' });
  return t.charAt(0).toUpperCase() + t.slice(1);
});

// Cursos que todavía no tienen horario (al editar, el propio curso sigue disponible).
const cursosDisponibles = computed(() =>
  formEsEdicion.value
    ? cursosStore.cursos
    : cursosStore.cursos.filter((c) => !horarios.value.some((h) => h.curso_id === c.id))
);

function esHoy(dia) {
  return dia === hoy.getDate() && mes.value === hoy.getMonth() && anio.value === hoy.getFullYear();
}

function sesionesDelDia(dia) {
  const letra = LETRA_POR_GETDAY[new Date(anio.value, mes.value, dia).getDay()];
  return horarios.value.filter((h) => h.dias.split(',').includes(letra));
}

function horaCorta(hora) {
  const [h, m] = String(hora).split(':').map(Number);
  const sufijo = h >= 12 ? 'p.m.' : 'a.m.';
  return `${h % 12 || 12}:${String(m).padStart(2, '0')} ${sufijo}`;
}

function nombresDias(dias) {
  return DIAS.filter((d) => dias.split(',').includes(d.k)).map((d) => d.n).join(', ');
}

function iconoCurso(cursoId) {
  return cursosStore.cursos.find((c) => c.id === cursoId)?.icono || '📘';
}

function cambiarMes(delta) {
  const f = new Date(anio.value, mes.value + delta, 1);
  anio.value = f.getFullYear();
  mes.value = f.getMonth();
  diaElegido.value = null;
}

function irAHoy() {
  anio.value = hoy.getFullYear();
  mes.value = hoy.getMonth();
  diaElegido.value = hoy.getDate();
}

async function cargarHorarios() {
  cargando.value = true;
  error.value = '';
  try {
    const res = await horarioService.listar();
    horarios.value = res.data.data.map((h) => ({
      ...h,
      curso_id: Number(h.curso_id),
      activo: Number(h.activo) === 1
    }));
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudieron cargar tus horarios.';
  } finally {
    cargando.value = false;
  }
}

function nuevoHorario() {
  form.value = { cursoId: '', dias: [], hora: '19:00', duracion: 15 };
  formEsEdicion.value = false;
  editando.value = true;
}

function editar(h) {
  form.value = {
    cursoId: h.curso_id,
    dias: h.dias.split(','),
    hora: String(h.hora).slice(0, 5),
    duracion: Number(h.duracion_minutos)
  };
  formEsEdicion.value = true;
  editando.value = true;
}

function cancelar() {
  editando.value = false;
}

function alternarDia(k) {
  const i = form.value.dias.indexOf(k);
  if (i >= 0) form.value.dias.splice(i, 1);
  else form.value.dias.push(k);
}

async function guardar() {
  guardando.value = true;
  try {
    await horarioService.guardar({
      cursoId: form.value.cursoId,
      dias: form.value.dias.join(','),
      hora: form.value.hora,
      duracionMinutos: form.value.duracion
    });
    editando.value = false;
    await cargarHorarios();
    alertaExito('Horario guardado', 'Te avisaremos por WhatsApp 10 minutos antes de tu hora de estudio.', 2500);
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo guardar el horario.');
  } finally {
    guardando.value = false;
  }
}

async function alternarActivo(h) {
  try {
    await horarioService.actualizarActivo(h.curso_id, !h.activo);
    h.activo = !h.activo;
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo actualizar el horario.');
  }
}

onMounted(() => {
  cursosStore.cargar();
  cargarHorarios();
});
</script>

<style scoped>
.subtitulo-admin { font-size: 0.78rem; color: var(--texto-suave); margin-top: 2px; }
.nav-mes { display: flex; gap: 8px; align-items: center; }
.btn-hoy { font-size: 0.78rem; font-weight: 700; padding: 6px 14px; border-radius: 10px; background: var(--vidrio-2); border: 1px solid var(--borde); cursor: pointer; color: inherit; }
.btn-hoy:hover { background: var(--vidrio-3); }
.aviso-error { color: #f87171; font-size: 0.85rem; margin-bottom: 12px; }

.cal-grid-layout { display: grid; grid-template-columns: 1.1fr 1fr; gap: 20px; align-items: start; }
@media (max-width: 768px) { .cal-grid-layout { grid-template-columns: 1fr; } }
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center; font-size: 0.85rem; }
.enc { font-weight: 800; color: var(--texto-suave); font-size: 0.72rem; text-transform: uppercase; padding: 6px 0; }
.cal-dia { padding: 12px 0 16px; border-radius: 12px; border: 1px solid transparent; position: relative; transition: all 0.2s; color: inherit; font: inherit; cursor: pointer; }
.cal-dia.mes { border-color: var(--borde); background: var(--vidrio); }
.cal-dia.mes:hover { border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.05); }
.cal-dia.evento { background: rgba(74,222,128,0.15); border-color: var(--verde); font-weight: 800; }
.cal-dia.hoy { outline: 2.5px solid var(--azul); font-weight: 800; box-shadow: 0 0 10px rgba(56,189,248,0.4); }
.cal-dia.elegido { background: rgba(74,222,128,0.3); }
.puntos { position: absolute; bottom: 5px; left: 0; right: 0; display: flex; justify-content: center; gap: 3px; }
.puntos i { width: 5px; height: 5px; border-radius: 50%; background: var(--verde); }
.puntos i.pausado { background: var(--texto-suave); }
.leyenda { font-size: 0.72rem; color: var(--texto-suave); margin-top: 12px; }

.detalle-dia { margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--borde); }
.vacio { font-size: 0.84rem; color: var(--texto-suave); padding: 8px 0; }
.suave { color: var(--texto-suave); }
.titulo-sesion { font-size: 0.84rem; }

.evento-linea { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); font-size: 0.9rem; align-items: center; }
.evento-linea:last-child { border-bottom: none; }
.evento-linea.pausado { opacity: 0.55; }
.info-horario { flex: 1; min-width: 0; }
.fecha-caja { min-width: 64px; flex: none; text-align: center; background: var(--vidrio-2); border: 1px solid var(--borde); border-radius: 12px; padding: 8px 4px; }
.fecha-caja b { display: block; font-size: 0.8rem; font-family: 'Outfit', sans-serif; font-weight: 800; }
.acciones-horario { display: flex; gap: 6px; flex: none; }
.btn-icono { width: 32px; height: 32px; border-radius: 8px; background: var(--vidrio-2); border: 1px solid var(--borde); font-size: 0.85rem; cursor: pointer; color: inherit; }
.btn-icono:hover { background: var(--vidrio-3); }
.btn-continuar-sm { border: 1.5px solid var(--verde); border-bottom: 3px solid var(--verde-oscuro); color: var(--verde); font-size: 0.74rem; font-weight: 700; padding: 5px 12px 7px; border-radius: 10px; background: transparent; cursor: pointer; transition: all 0.1s; }
.btn-continuar-sm:hover { background: var(--verde); color: #06281A; transform: translateY(-1px); }

.form-horario { display: flex; flex-direction: column; gap: 4px; }
.fila-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.chips-dias { display: flex; flex-wrap: wrap; gap: 6px; }
.chip-dia { padding: 7px 12px; border-radius: 10px; border: 1px solid var(--borde); background: var(--vidrio); font-size: 0.78rem; font-weight: 700; cursor: pointer; color: inherit; transition: all 0.15s; }
.chip-dia.activo { background: rgba(74,222,128,0.2); border-color: var(--verde); color: var(--verde); }
.acciones-form { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; }
.btn-outline { padding: 9px 18px; border-radius: 12px; border: 1px solid var(--borde); background: transparent; color: inherit; font-weight: 700; cursor: pointer; }
.btn-outline:hover { background: var(--vidrio-2); }
</style>
