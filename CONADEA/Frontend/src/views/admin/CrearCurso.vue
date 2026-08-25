<template>
  <div>
    <div class="fila-seccion">
      <div>
        <h2>Crear curso</h2>
        <p class="subtitulo-admin">Solo para Administrador</p>
      </div>
    </div>

    <!-- Datos generales -->
    <div class="vidrio">
      <h3 class="titulo-tarjeta">Datos generales</h3>

      <div class="fila-icono-titulo">
        <div class="campo campo-icono">
          <label>Ícono</label>
          <input v-model="icono" type="text" placeholder="📘" maxlength="4" />
        </div>
        <div class="campo campo-crece">
          <label>Título</label>
          <input v-model="titulo" type="text" placeholder="Ej. Ganadería Sostenible" />
        </div>
      </div>

      <div class="campo">
        <label>Descripción</label>
        <textarea v-model="descripcion" rows="3" placeholder="Breve resumen del curso"></textarea>
      </div>

      <div class="campo">
        <label>Imagen del curso</label>
        <div class="selector-imagen" :class="{ 'tiene-imagen': previewImagen }" @click="inputImagen?.click()">
          <img v-if="previewImagen" :src="previewImagen" alt="Vista previa" class="preview-imagen" />
          <template v-else>
            <span class="selector-imagen-icono">🖼️</span>
            <span>Toca para elegir una foto</span>
          </template>
        </div>
        <input ref="inputImagen" type="file" accept="image/*" class="input-oculto" @change="onElegirImagen" />
      </div>
    </div>

    <!-- Lecciones -->
    <div class="fila-seccion fila-seccion-sub">
      <h3>Lecciones</h3>
      <button type="button" class="btn-agregar" @click="agregarLeccion">+ Agregar</button>
    </div>

    <div v-for="(leccion, i) in lecciones" :key="leccion._id" class="vidrio tarjeta-item">
      <div class="cab-item">
        <b>Lección {{ i + 1 }}</b>
        <button v-if="lecciones.length > 1" type="button" class="btn-quitar" @click="quitarLeccion(i)">🗑️</button>
      </div>
      <div class="campo">
        <label>Título</label>
        <input v-model="leccion.titulo" type="text" placeholder="Ej. Cómo enviar consultas" />
      </div>
      <div class="campo">
        <label>Contenido</label>
        <textarea v-model="leccion.contenido" rows="4" placeholder="Explica el paso a paso..."></textarea>
      </div>
      <div class="campo">
        <label>Video (opcional)</label>
        <div v-if="!leccion.video" class="selector-video" @click="leccion._inputVideo?.click()">
          <span>🎬 Toca para elegir un video</span>
        </div>
        <div v-else class="selector-video con-video">
          <span>✅ {{ leccion.video.name }}</span>
          <button type="button" class="btn-quitar-chico" @click="leccion.video = null">✕</button>
        </div>
        <input
          :ref="(el) => (leccion._inputVideo = el)"
          type="file"
          accept="video/mp4,video/quicktime"
          class="input-oculto"
          @change="(e) => onElegirVideo(e, leccion)"
        />
      </div>
      <div class="fila-seccion fila-seccion-sub">
        <h4>Quiz de la lección</h4>
        <button type="button" class="btn-agregar" @click="agregarPregunta(leccion)">+ Agregar pregunta</button>
      </div>

      <div v-for="(pregunta, j) in leccion.preguntas" :key="pregunta._id" class="vidrio tarjeta-item" style="margin-left: 20px; border-color: rgba(74,222,128,0.3);">
        <div class="cab-item">
          <b>Pregunta {{ j + 1 }}</b>
          <button type="button" class="btn-quitar" @click="quitarPregunta(leccion, j)">🗑️</button>
        </div>
        <div class="campo">
          <label>Pregunta</label>
          <input v-model="pregunta.pregunta" type="text" placeholder="¿Cuál es...?" />
        </div>

        <label class="etiqueta-opciones">Opciones (toca ✓ para marcar la correcta)</label>
        <div v-for="(opcion, k) in pregunta.opciones" :key="opcion._id" class="fila-opcion">
          <button
            type="button"
            class="marca-correcta"
            :class="{ activa: opcion.esCorrecta }"
            @click="marcarCorrecta(pregunta, k)"
          >
            <span v-if="opcion.esCorrecta">✓</span>
          </button>
          <input v-model="opcion.texto" type="text" :placeholder="`Opción ${k + 1}`" />
          <button v-if="pregunta.opciones.length > 2" type="button" class="btn-quitar-chico" @click="quitarOpcion(pregunta, k)">✕</button>
        </div>
        <button type="button" class="btn-agregar-opcion" @click="agregarOpcion(pregunta)">+ Agregar opción</button>
      </div>
    </div>

    <button class="btn btn-verde btn-ancho" :disabled="guardando" @click="guardar">
      {{ guardando ? 'Guardando...' : 'Guardar curso' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { alertaExito, alertaError } from '../../utils/alertas.js';
import cursoService from '../../services/cursoService.js';

const router = useRouter();

let contadorIds = 0;
const idUnico = () => contadorIds++;

function nuevaLeccion() {
  return { _id: idUnico(), titulo: '', contenido: '', video: null, _inputVideo: null, preguntas: [] };
}
function nuevaOpcion(esCorrecta = false) {
  return { _id: idUnico(), texto: '', esCorrecta };
}
function nuevaPregunta() {
  return { _id: idUnico(), pregunta: '', opciones: [nuevaOpcion(true), nuevaOpcion()] };
}

const icono = ref('📘');
const titulo = ref('');
const descripcion = ref('');
const imagenFile = ref(null);
const previewImagen = ref('');
const inputImagen = ref(null);

const lecciones = ref([nuevaLeccion()]);
const guardando = ref(false);

function onElegirImagen(evento) {
  const archivo = evento.target.files?.[0];
  if (!archivo) return;
  imagenFile.value = archivo;
  previewImagen.value = URL.createObjectURL(archivo);
}

function onElegirVideo(evento, leccion) {
  const archivo = evento.target.files?.[0];
  if (archivo) leccion.video = archivo;
}

function agregarLeccion() {
  lecciones.value.push(nuevaLeccion());
}
function quitarLeccion(i) {
  if (lecciones.value.length <= 1) return;
  lecciones.value.splice(i, 1);
}

function agregarPregunta(leccion) {
  leccion.preguntas.push(nuevaPregunta());
}
function quitarPregunta(leccion, i) {
  leccion.preguntas.splice(i, 1);
}
function agregarOpcion(pregunta) {
  pregunta.opciones.push(nuevaOpcion());
}
function quitarOpcion(pregunta, i) {
  if (pregunta.opciones.length <= 2) return;
  pregunta.opciones.splice(i, 1);
}
function marcarCorrecta(pregunta, i) {
  pregunta.opciones.forEach((o, j) => (o.esCorrecta = j === i));
}

function validar() {
  if (!icono.value.trim()) return 'Elige un ícono (emoji) para el curso.';
  if (titulo.value.trim().length < 3) return 'El título del curso es obligatorio.';
  if (!descripcion.value.trim()) return 'La descripción del curso es obligatoria.';
  if (!imagenFile.value) return 'Selecciona una foto para el curso.';
  for (const l of lecciones.value) {
    if (!l.titulo.trim() || !l.contenido.trim()) {
      return 'Completa el título y el contenido de todas las lecciones.';
    }
    for (const p of l.preguntas) {
      if (!p.pregunta.trim()) return 'Completa el texto de todas las preguntas del quiz.';
      for (const o of p.opciones) {
        if (!o.texto.trim()) return 'Completa el texto de todas las opciones del quiz.';
      }
      if (!p.opciones.some((o) => o.esCorrecta)) {
        return 'Marca cuál opción es la correcta en cada pregunta del quiz.';
      }
    }
  }
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
    lecciones: lecciones.value.map((l) => ({ 
      titulo: l.titulo.trim(), 
      contenido: l.contenido.trim(),
      quiz: l.preguntas.map((p) => ({
        pregunta: p.pregunta.trim(),
        opciones: p.opciones.map((o) => ({ texto: o.texto.trim(), es_correcta: o.esCorrecta }))
      }))
    })),
  };

  const videosLecciones = {};
  lecciones.value.forEach((l, i) => {
    if (l.video) videosLecciones[i] = l.video;
  });

  guardando.value = true;
  try {
    await cursoService.crear(datos, imagenFile.value, videosLecciones);
    await alertaExito('¡Curso publicado!', 'El curso ya está disponible.');
    router.push('/catalogo');
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo conectar con el servidor. Intenta de nuevo.', 'No se pudo guardar');
  } finally {
    guardando.value = false;
  }
}
</script>

<style scoped>
.subtitulo-admin {
  font-size: 0.8rem;
  color: var(--texto-suave);
  margin-top: 2px;
}

.fila-seccion-sub {
  margin-top: 24px;
}

.fila-seccion-sub h3 {
  font-size: 1.1rem;
  font-family: 'Outfit', sans-serif;
}

.titulo-tarjeta {
  font-size: 1rem;
  margin-bottom: 4px;
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

.campo textarea::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.campo textarea:focus {
  border-color: var(--verde);
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.25);
  background: rgba(255, 255, 255, 0.12);
}

.input-oculto {
  display: none;
}

.selector-imagen {
  height: 160px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.15);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.82rem;
  cursor: pointer;
  overflow: hidden;
  transition: border-color 0.2s ease;
}

.selector-imagen:hover {
  border-color: rgba(74, 222, 128, 0.4);
}

.selector-imagen-icono {
  font-size: 1.6rem;
}

.selector-imagen.tiene-imagen {
  padding: 0;
}

.preview-imagen {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.btn-agregar {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--verde);
  padding: 6px 12px;
  border-radius: 10px;
  transition: background 0.15s ease;
}

.btn-agregar:hover {
  background: rgba(74, 222, 128, 0.12);
}

.tarjeta-item {
  margin-top: 12px;
}

.cab-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.cab-item b {
  font-size: 0.92rem;
  font-family: 'Outfit', sans-serif;
}

.btn-quitar {
  color: var(--rojo);
  font-size: 0.95rem;
  padding: 4px 8px;
  border-radius: 8px;
}

.btn-quitar:hover {
  background: rgba(248, 113, 113, 0.12);
}

.selector-video {
  padding: 12px 14px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.15);
  font-size: 0.82rem;
  color: rgba(255, 255, 255, 0.55);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.selector-video:hover {
  border-color: rgba(74, 222, 128, 0.4);
}

.selector-video.con-video {
  background: rgba(74, 222, 128, 0.1);
  border-color: rgba(74, 222, 128, 0.4);
  color: var(--texto);
  cursor: default;
}

.selector-video.con-video span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.btn-quitar-chico {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.8rem;
  padding: 2px 6px;
  border-radius: 6px;
  flex: none;
}

.btn-quitar-chico:hover {
  color: #FFFFFF;
  background: rgba(255, 255, 255, 0.1);
}

.etiqueta-opciones {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--texto-suave);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 10px 0 8px;
}

.fila-opcion {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.fila-opcion input {
  flex: 1;
  padding: 9px 12px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.07);
  color: var(--texto);
  outline: none;
  font-size: 0.85rem;
  transition: all 0.3s ease;
}

.fila-opcion input:focus {
  border-color: var(--verde);
  box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.25);
}

.marca-correcta {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  flex: none;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: var(--verde);
  font-size: 0.8rem;
  font-weight: 800;
  transition: all 0.15s ease;
}

.marca-correcta.activa {
  background: rgba(74, 222, 128, 0.2);
  border-color: var(--verde);
}

.btn-agregar-opcion {
  font-size: 0.76rem;
  font-weight: 700;
  color: var(--verde);
  padding: 4px 4px;
  margin-top: 2px;
}

.btn-agregar-opcion:hover {
  text-decoration: underline;
}

.btn-ancho {
  margin-top: 24px;
}
</style>
