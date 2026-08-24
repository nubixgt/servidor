<template>
  <div class="fondo-modal" @click.self="cerrar">
    <div class="modal-ruta vidrio">
      <div class="modal-header">
        <h3>{{ esEdicion ? 'Editar ruta' : 'Nueva ruta de aprendizaje' }}</h3>
        <button class="btn-cerrar" @click="cerrar">✕</button>
      </div>

      <div class="fila-icono-titulo">
        <div class="campo campo-icono">
          <label>Ícono</label>
          <input v-model="icono" type="text" placeholder="🐄" maxlength="4" :disabled="guardando" />
        </div>
        <div class="campo campo-crece">
          <label>Título</label>
          <input v-model="titulo" type="text" placeholder="Ej. Ganadería Sostenible" :disabled="guardando" />
        </div>
      </div>

      <div class="campo">
        <label>Descripción</label>
        <textarea v-model="descripcion" rows="3" placeholder="Breve resumen de la ruta" :disabled="guardando"></textarea>
      </div>

      <div class="campo">
        <label>Color</label>
        <div class="selector-color">
          <button
            v-for="c in colores"
            :key="c.valor"
            type="button"
            class="chip-color"
            :class="{ activo: color === c.valor }"
            :style="{ background: c.gradiente }"
            :disabled="guardando"
            @click="color = c.valor"
          >
            <span v-if="color === c.valor">✓</span>
          </button>
        </div>
      </div>

      <div class="campo">
        <label>Cursos de la ruta</label>
        <div class="lista-cursos-check">
          <p v-if="cursosStore.cargando" class="sin-cursos">Cargando cursos...</p>
          <p v-else-if="cursosStore.cursos.length === 0" class="sin-cursos">
            Todavía no hay cursos publicados — crea uno primero en "Crear curso".
          </p>
          <label v-for="c in cursosStore.cursos" :key="c.id" class="opcion-curso">
            <input type="checkbox" :value="c.id" v-model="cursoIdsSeleccionados" :disabled="guardando" />
            <span>{{ c.icono }} {{ c.titulo }}</span>
          </label>
        </div>
      </div>

      <button class="btn btn-verde btn-ancho" :disabled="guardando" @click="guardar">
        {{ guardando ? 'Guardando...' : (esEdicion ? 'Guardar cambios' : 'Crear ruta') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCursosStore } from '../../stores/cursos.js';
import { alertaExito, alertaError } from '../../utils/alertas.js';
import rutaService from '../../services/rutaService.js';

const props = defineProps({
  ruta: { type: Object, default: null } // null = crear; objeto = editar
});
const emit = defineEmits(['cerrar', 'guardado']);

const cursosStore = useCursosStore();
onMounted(() => cursosStore.cargar());

const esEdicion = computed(() => !!props.ruta);

const colores = [
  { valor: 'esmeralda', gradiente: 'linear-gradient(135deg, #34D399, #059669)' },
  { valor: 'verde', gradiente: 'linear-gradient(135deg, #4ADE80, #15803D)' },
  { valor: 'azul', gradiente: 'linear-gradient(135deg, #60A5FA, #1D4ED8)' },
  { valor: 'oro', gradiente: 'linear-gradient(135deg, #FCD34D, #D97706)' }
];

const icono = ref(props.ruta?.icono || '🌱');
const titulo = ref(props.ruta?.titulo || '');
const descripcion = ref(props.ruta?.descripcion || '');
const color = ref(props.ruta?.color || 'esmeralda');
const cursoIdsSeleccionados = ref(props.ruta?.cursos?.map((c) => c.id) || []);
const guardando = ref(false);

function cerrar() {
  if (!guardando.value) emit('cerrar');
}

function validar() {
  if (!icono.value.trim()) return 'Elige un ícono (emoji) para la ruta.';
  if (titulo.value.trim().length < 3) return 'El título de la ruta es obligatorio.';
  if (!descripcion.value.trim()) return 'La descripción de la ruta es obligatoria.';
  if (cursoIdsSeleccionados.value.length < 1) return 'Asigna al menos un curso a la ruta.';
  return null;
}

async function guardar() {
  if (guardando.value) return;

  const error = validar();
  if (error) {
    alertaError(error, 'Falta información');
    return;
  }

  const datos = {
    icono: icono.value.trim(),
    titulo: titulo.value.trim(),
    descripcion: descripcion.value.trim(),
    color: color.value,
    curso_ids: cursoIdsSeleccionados.value
  };

  guardando.value = true;
  try {
    if (esEdicion.value) {
      await rutaService.actualizar(props.ruta.id, datos);
      await alertaExito('¡Listo!', 'La ruta se actualizó correctamente.');
    } else {
      await rutaService.crear(datos);
      await alertaExito('¡Ruta creada!', 'La ruta ya está disponible.');
    }
    emit('guardado');
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo conectar con el servidor. Intenta de nuevo.');
  } finally {
    guardando.value = false;
  }
}
</script>

<style scoped>
.fondo-modal {
  position: fixed;
  inset: 0;
  background: rgba(5, 20, 26, 0.85);
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  backdrop-filter: blur(8px);
}

.modal-ruta {
  width: 100%;
  max-width: 520px;
  max-height: 88vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.modal-header h3 {
  font-size: 1.1rem;
  font-family: 'Outfit', sans-serif;
}

.btn-cerrar {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  color: var(--texto-suave);
  font-size: 0.9rem;
}

.btn-cerrar:hover {
  background: var(--vidrio-2);
  color: #FFFFFF;
}

.fila-icono-titulo {
  display: flex;
  gap: 12px;
}

.campo-icono {
  width: 84px;
  flex: none;
}

.campo-icono input {
  text-align: center;
  font-size: 1.3rem;
}

.campo-crece {
  flex: 1;
}

.campo textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.07);
  color: var(--texto);
  outline: none;
  resize: vertical;
  font-family: inherit;
  transition: all 0.3s ease;
}

.campo textarea:focus {
  border-color: var(--verde);
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.25);
  background: rgba(255, 255, 255, 0.12);
}

.selector-color {
  display: flex;
  gap: 10px;
}

.chip-color {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 2px solid transparent;
  color: #06281A;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.chip-color.activo {
  border-color: #FFFFFF;
  transform: scale(1.08);
}

.lista-cursos-check {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.05);
  padding: 6px;
}

.opcion-curso {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
}

.opcion-curso:hover {
  background: rgba(255, 255, 255, 0.06);
}

.opcion-curso input {
  accent-color: var(--verde);
  width: 16px;
  height: 16px;
}

.sin-cursos {
  font-size: 0.8rem;
  color: var(--texto-suave);
  padding: 10px;
}

.btn-ancho {
  margin-top: 20px;
}
</style>
