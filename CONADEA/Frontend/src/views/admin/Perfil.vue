<template>
  <div>
    <div class="fila-seccion"><h2>Mi perfil y avance</h2></div>

    <!-- Info del usuario -->
    <div class="vidrio" style="margin-bottom:16px;">
      <div class="cab-perfil">
        <div class="avatar-grande">
          <img v-if="!store.config.datos" :src="avatarUrl" :alt="store.usuario?.nombre" class="avatar-img" />
          <span v-else>{{ iniciales }}</span>
        </div>
        <div>
          <h3>{{ store.usuario?.nombre }}</h3>
          <p>📞 {{ store.usuario?.telefono || '—' }}<br>📍 {{ lugarTexto }}<br>🎖️ Rol: {{ store.usuario?.rol || '—' }}</p>
        </div>
      </div>
      <div class="estadisticas">
        <div class="stat-caja"><b>{{ pctGlobal }}%</b><small>Avance</small></div>
        <div class="stat-caja"><b>{{ leccionesHechas }}/{{ totalLecciones }}</b><small>Lecciones</small></div>
        <div class="stat-caja"><b>{{ certificados }}</b><small>Certificados</small></div>
        <div class="stat-caja"><b>{{ store.racha }}</b><small>Días de racha</small></div>
      </div>
    </div>

    <!-- Avance por curso -->
    <div class="vidrio">
      <div class="cab-tarjeta"><h3>Avance por curso</h3></div>

      <p v-if="cursosStore.cargando" style="font-size:.85rem;color:var(--texto-suave);">Cargando cursos...</p>
      <p v-else-if="cursos.length === 0" style="font-size:.85rem;color:var(--texto-suave);">
        Todavía no hay cursos publicados.
      </p>
      <table v-else class="tabla-avance">
        <tbody>
          <tr v-for="c in cursos" :key="c.id" class="fila-curso" @click="router.push(`/curso/${c.id}`)">
            <td class="mod-ic">{{ c.icono }}</td>
            <td>
              <b style="font-size:.85rem;">{{ c.titulo }}</b>
              <div class="pista" style="margin-top:5px;">
                <div class="pista-fill" :style="{ width: cursosStore.pctCurso(c) + '%' }"></div>
              </div>
            </td>
            <td class="td-right">
              <span v-if="cursosStore.aprobado(c.id)" class="chip-aprobado">✓ Aprobado</span>
              <span style="font-size:.78rem;color:var(--texto-suave);">{{ cursosStore.pctCurso(c) }}%</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useAppStore } from '../../stores/app.js';
import { useCursosStore } from '../../stores/cursos.js';
import locationService from '../../services/locationService.js';

const router = useRouter();
const store = useAppStore();
const cursosStore = useCursosStore();
const { cursos } = storeToRefs(cursosStore);

onMounted(() => {
  cursosStore.cargar();
  cargarUbicacion();
});

const iniciales = computed(() => {
  if (!store.usuario?.nombre) return '--';
  return store.usuario.nombre.split(' ').map(s => s[0]).slice(0, 2).join('').toUpperCase();
});

const avatarUrl = computed(() => {
  if (!store.usuario?.nombre) return '';
  const isFemale = /ana|maria|garcia|sofia|lucia|laura|elena|claudia|garcía|maría/i.test(store.usuario.nombre);
  return isFemale
    ? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&auto=format&fit=crop&q=80'
    : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&auto=format&fit=crop&q=80';
});

// Estadísticas reales — mismas fórmulas que
// app_conadea/lib/data/state/progreso_controller.dart. La racha sigue
// siendo local: ningún backend la registra todavía (ni en la app ni acá).
const leccionesHechas = computed(() =>
  cursos.value.reduce((s, c) => s + cursosStore.progresoDe(c.id).leccionesCompletadas.size, 0)
);
const totalLecciones = computed(() => cursos.value.reduce((s, c) => s + (c.total_lecciones ?? 0), 0));
const certificados = computed(() => cursos.value.filter((c) => cursosStore.aprobado(c.id)).length);
const pctGlobal = computed(() => {
  if (!cursos.value.length) return 0;
  const suma = cursos.value.reduce((s, c) => s + cursosStore.pctCurso(c), 0);
  return Math.round(suma / cursos.value.length);
});

// Nombre real del departamento/municipio a partir de los ids que guarda el
// login (Backend/src/Controllers/LocationController.php no tiene un
// endpoint por id, así que se busca en el listado).
const nombreDepartamento = ref('');
const nombreMunicipio = ref('');

const lugarTexto = computed(() => {
  if (nombreMunicipio.value && nombreDepartamento.value) return `${nombreMunicipio.value}, ${nombreDepartamento.value}`;
  return nombreDepartamento.value || 'Guatemala';
});

async function cargarUbicacion() {
  const depId = store.usuario?.departamentoId;
  if (!depId) return;
  try {
    const { data: depData } = await locationService.listarDepartamentos();
    nombreDepartamento.value = depData.data.find((d) => d.id === depId)?.nombre || '';

    const muniId = store.usuario?.municipioId;
    if (muniId) {
      const { data: muniData } = await locationService.listarMunicipios(depId);
      nombreMunicipio.value = muniData.data.find((m) => m.id === muniId)?.nombre || '';
    }
  } catch (e) {
    // Silencioso: no vale la pena bloquear el resto del perfil por esto.
  }
}
</script>

<style scoped>
.cab-perfil { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
.avatar-grande { width: 82px; height: 82px; border-radius: 50%; flex: none; font-size: 1.9rem; font-weight: 800; color: #06281A; background: linear-gradient(135deg, var(--verde), #16A34A); display: flex; align-items: center; justify-content: center; border: 3px solid var(--oro); box-shadow: 0 4px 16px rgba(244,197,66,0.35); overflow: hidden; }
.avatar-img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.cab-perfil h3 { font-size: 1.15rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
.cab-perfil p  { font-size: 0.82rem; color: var(--texto-suave); margin-top: 4px; line-height: 1.6; }
.estadisticas { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-top: 20px; }
@media (max-width: 700px) { .estadisticas { grid-template-columns: repeat(2, 1fr); } }
.stat-caja { background: var(--vidrio); border: 1px solid var(--borde); border-radius: 16px; padding: 16px 10px; text-align: center; transition: all 0.2s; }
.stat-caja:hover { border-color: rgba(255,255,255,0.25); transform: translateY(-2px); }
.stat-caja b { font-size: 1.65rem; display: block; color: var(--verde); font-family: 'Outfit', sans-serif; font-weight: 800; }
.stat-caja small { font-size: 0.7rem; color: var(--texto-suave); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; display: block; margin-top: 2px; }
.tabla-avance { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.tabla-avance td { padding: 12px 8px; border-bottom: 1px solid var(--borde); vertical-align: middle; }
.tabla-avance tr:last-child td { border-bottom: none; }
.mod-ic { width: 40px; font-size: 1.25rem; }
.td-right { text-align: right; white-space: nowrap; }
.fila-curso { cursor: pointer; transition: background 0.15s ease; }
.fila-curso:hover { background: rgba(255,255,255,0.04); }
.chip-aprobado { display: inline-block; font-size: 0.7rem; font-weight: 800; color: var(--oro); background: rgba(244,197,66,0.12); border: 1px solid rgba(244,197,66,0.4); padding: 3px 8px; border-radius: 10px; margin-right: 8px; }
</style>
