<template>
  <div>
    <div class="fila-seccion">
      <h2>Catálogo de cursos</h2>
      <span v-if="!cargando" style="font-size:.78rem;color:var(--texto-suave);">
        {{ listaFiltrada.length }} de {{ cursos.length }} cursos
      </span>
    </div>
    <p v-if="terminoBusqueda" style="font-size:.8rem;color:var(--texto-suave);margin-bottom:12px;">
      Resultados para «{{ terminoBusqueda }}»
    </p>

    <div v-if="cargando" class="vidrio">
      <p style="font-size:.85rem;color:var(--texto-suave);">Cargando cursos...</p>
    </div>

    <div v-else-if="error" class="vidrio">
      <p style="font-size:.85rem;color:var(--rojo);margin-bottom:12px;">{{ error }}</p>
      <button class="btn btn-verde" @click="cursosStore.cargar()">Reintentar</button>
    </div>

    <div v-else class="grid-catalogo">
      <button
        v-for="c in listaFiltrada"
        :key="c.id"
        class="vidrio carta-curso"
        @click="router.push(`/curso/${c.id}`)"
      >
        <div class="portada" :style="portadaStyle(c)">
          <span class="num">{{ c.icono }} Curso</span>
          <span v-if="aprobado(c.id)" class="listo">✓ Completado</span>
        </div>
        <div class="cuerpo-carta">
          <h4>{{ c.titulo }}</h4>
          <p class="desc">{{ c.descripcion }}</p>
          <div class="pista" style="margin-bottom:6px;"><div class="pista-fill" :style="{ width: pctCurso(c) + '%' }"></div></div>
          <div class="meta-curso">
            <span>📖 {{ c.total_lecciones }} lecciones</span>
            <span>📝 Evaluación</span>
            <span>🎓 Certificado</span>
          </div>
        </div>
      </button>
      <p v-if="listaFiltrada.length === 0" style="color:var(--texto-suave);">
        {{ cursos.length === 0 ? 'Todavía no hay cursos publicados.' : 'No se encontraron cursos para esa búsqueda.' }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { storeToRefs } from 'pinia';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { useCursosStore } from '../../stores/cursos.js';

const props = defineProps({ terminoBusqueda: { type: String, default: '' } });
const router = useRouter();
const store = useAppStore();
const cursosStore = useCursosStore();
const { cursos, cargando, error } = storeToRefs(cursosStore);
const { pctCurso, aprobado } = cursosStore;

onMounted(() => cursosStore.cargar());

const listaFiltrada = computed(() => {
  const f = props.terminoBusqueda.toLowerCase().trim();
  if (!f) return cursos.value;
  return cursos.value.filter(
    (c) => c.titulo.toLowerCase().includes(f) || c.descripcion.toLowerCase().includes(f)
  );
});

function portadaStyle(c) {
  if (store.config.datos) return `background: linear-gradient(135deg, #34D399, #059669);`;
  return `background-image: url('${c.imagen_url}'); background-size: cover; background-position: center;`;
}
</script>

<style scoped>
.grid-catalogo { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
.carta-curso { display: flex; flex-direction: column; text-align: left; overflow: hidden; padding: 0; transition: all 0.3s ease; cursor: pointer; }
.carta-curso:hover { transform: translateY(-4px); border-color: var(--verde); }
.portada { height: 120px; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid var(--borde); }
.num { position: absolute; top: 10px; left: 12px; font-size: 0.68rem; font-weight: 800; letter-spacing: 0.08em; background: rgba(0,0,0,0.55); padding: 4px 10px; border-radius: 20px; text-transform: uppercase; }
.listo { position: absolute; top: 10px; right: 12px; font-size: 0.68rem; font-weight: 800; background: var(--oro); color: #3A2A00; padding: 4px 10px; border-radius: 20px; }
.cuerpo-carta { padding: 16px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
.cuerpo-carta h4 { font-size: 1rem; font-family: 'Outfit', sans-serif; font-weight: 700; }
.desc { font-size: 0.8rem; color: var(--texto-suave); line-height: 1.5; flex: 1; }
.meta-curso { display: flex; gap: 12px; font-size: 0.74rem; color: var(--texto-suave); border-top: 1px solid rgba(255,255,255,0.06); padding-top: 10px; margin-top: 4px; flex-wrap: wrap; }
</style>
