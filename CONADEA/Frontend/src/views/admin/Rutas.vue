<template>
  <div>
    <div class="fila-seccion">
      <h2>Rutas de aprendizaje</h2>
      <button v-if="esAdmin" class="btn btn-verde" @click="abrirCrear">+ Nueva ruta</button>
    </div>
    <p style="font-size:.84rem;color:var(--texto-suave);margin-bottom:14px;">
      Las rutas agrupan cursos reales según tu actividad productiva.
    </p>

    <div v-if="cargando" class="vidrio">
      <p style="font-size:.85rem;color:var(--texto-suave);">Cargando rutas...</p>
    </div>

    <div v-else-if="error" class="vidrio">
      <p style="font-size:.85rem;color:var(--rojo);margin-bottom:12px;">{{ error }}</p>
      <button class="btn btn-verde" @click="cargarRutas">Reintentar</button>
    </div>

    <div v-else-if="rutas.length === 0" class="vidrio">
      <p style="font-size:.85rem;color:var(--texto-suave);">
        {{ esAdmin
          ? 'Todavía no hay rutas de aprendizaje. Crea la primera con "+ Nueva ruta".'
          : 'Todavía no hay rutas de aprendizaje publicadas.' }}
      </p>
    </div>

    <div v-else v-for="r in rutas" :key="r.id" class="vidrio ruta-card">
      <div class="ruta-header" :class="r.color !== 'esmeralda' ? r.color : ''">
        <div class="ruta-icono">{{ r.icono }}</div>
        <div class="ruta-info">
          <b>{{ r.titulo }}</b>
          <p>{{ r.descripcion }}</p>
          <span class="estado-ruta">{{ pctRuta(r) }}% completado · {{ r.cursos.length }} cursos</span>
          <div class="pista" style="margin-top:6px;">
            <div class="pista-fill" :style="{ width: pctRuta(r) + '%' }"></div>
          </div>
        </div>
        <div v-if="esAdmin" class="acciones-admin">
          <button class="btn-icono" title="Editar ruta" @click="abrirEditar(r)">✏️</button>
          <button class="btn-icono btn-icono-rojo" title="Eliminar ruta" @click="eliminarRuta(r)">🗑️</button>
        </div>
      </div>

      <!-- Cursos de la ruta -->
      <div style="margin-top:12px;">
        <div v-for="c in r.cursos" :key="c.id" class="curso-fila-mini">
          <div class="mini-ic">{{ c.icono }}</div>
          <div class="datos">
            <b>{{ c.titulo }}</b>
            <div class="pista"><div class="pista-fill" :style="{ width: cursosStore.pctCurso(c) + '%' }"></div></div>
          </div>
          <button class="btn-continuar-sm" :class="{ hecho: cursosStore.aprobado(c.id) }" @click="router.push(`/curso/${c.id}`)">
            {{ cursosStore.aprobado(c.id) ? 'Repasar' : 'Ir al curso' }}
          </button>
        </div>
      </div>
    </div>

    <RutaFormModal
      v-if="modalAbierto"
      :ruta="rutaEditando"
      @cerrar="modalAbierto = false"
      @guardado="onGuardado"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { useCursosStore } from '../../stores/cursos.js';
import { alertaConfirmar, alertaExito, alertaError } from '../../utils/alertas.js';
import rutaService from '../../services/rutaService.js';
import RutaFormModal from '../../components/admin/RutaFormModal.vue';

const router = useRouter();
const store = useAppStore();
const cursosStore = useCursosStore();

const esAdmin = computed(() => store.usuario?.rol === 'Administrador');

const rutas = ref([]);
const cargando = ref(true);
const error = ref('');

const modalAbierto = ref(false);
const rutaEditando = ref(null);

onMounted(() => {
  cursosStore.cargar();
  cargarRutas();
});

async function cargarRutas() {
  cargando.value = true;
  error.value = '';
  try {
    const { data } = await rutaService.listar();
    rutas.value = data.data;
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo conectar con el servidor.';
  } finally {
    cargando.value = false;
  }
}

function pctRuta(ruta) {
  if (!ruta.cursos.length) return 0;
  const suma = ruta.cursos.reduce((s, c) => s + cursosStore.pctCurso(c), 0);
  return Math.round(suma / ruta.cursos.length);
}

function abrirCrear() {
  rutaEditando.value = null;
  modalAbierto.value = true;
}

function abrirEditar(ruta) {
  rutaEditando.value = ruta;
  modalAbierto.value = true;
}

function onGuardado() {
  modalAbierto.value = false;
  cargarRutas();
}

async function eliminarRuta(ruta) {
  const confirmar = await alertaConfirmar(
    '¿Eliminar ruta?',
    `Se eliminará "${ruta.titulo}". Los cursos que agrupa no se borran, solo dejan de estar en esta ruta.`,
    'Sí, eliminar',
    'Cancelar'
  );
  if (!confirmar) return;

  try {
    await rutaService.eliminar(ruta.id);
    await alertaExito('Ruta eliminada', 'La ruta se eliminó correctamente.');
    cargarRutas();
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo eliminar la ruta. Intenta de nuevo.');
  }
}
</script>

<style scoped>
.ruta-card { margin-bottom: 14px; }
.ruta-header { display: flex; gap: 16px; align-items: flex-start; }
.ruta-icono { width: 56px; height: 56px; border-radius: 14px; flex: none; font-size: 1.7rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #34D399, #059669); }
.ruta-header.azul .ruta-icono { background: linear-gradient(135deg, #60A5FA, #1D4ED8); }
.ruta-header.oro .ruta-icono  { background: linear-gradient(135deg, #FCD34D, #D97706); }
.ruta-header.verde .ruta-icono{ background: linear-gradient(135deg, #4ADE80, #15803D); }
.ruta-info { flex: 1; min-width: 0; }
.ruta-info b { font-size: 0.95rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
.ruta-info p { font-size: 0.78rem; color: var(--texto-suave); margin: 4px 0 6px; }
.estado-ruta { font-size: 0.76rem; color: var(--lima); font-weight: 700; }
.acciones-admin { display: flex; flex-direction: column; gap: 6px; flex: none; }
.btn-icono { width: 32px; height: 32px; border-radius: 8px; background: var(--vidrio-2); border: 1px solid var(--borde); font-size: 0.85rem; }
.btn-icono:hover { background: var(--vidrio-3); }
.btn-icono-rojo:hover { border-color: rgba(248,113,113,0.5); }
.curso-fila-mini { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--borde); }
.curso-fila-mini:last-child { border-bottom: none; }
.mini-ic { width: 36px; height: 36px; border-radius: 10px; flex: none; display: flex; align-items: center; justify-content: center; font-size: 1rem; background: var(--vidrio-2); border: 1px solid var(--borde); }
.datos { flex: 1; min-width: 0; }
.datos b { font-size: 0.88rem; display: block; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Outfit', sans-serif; font-weight: 700; }
.btn-continuar-sm { border: 1.5px solid var(--verde); border-bottom: 3px solid var(--verde-oscuro); color: var(--verde); font-size: 0.74rem; font-weight: 700; padding: 5px 12px 7px; border-radius: 10px; flex: none; background: transparent; cursor: pointer; transition: all 0.1s; }
.btn-continuar-sm:hover { background: var(--verde); color: #06281A; transform: translateY(-1px); }
.btn-continuar-sm.hecho { border-color: var(--oro); border-bottom-color: #B45309; color: var(--oro); }
.btn-continuar-sm.hecho:hover { background: var(--oro); color: #3A2A00; }
</style>
