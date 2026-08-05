<template>
  <div>
    <div class="fila-seccion">
      <h2>Catálogo de cursos</h2>
      <span style="font-size:.78rem;color:var(--texto-suave);">{{ listaFiltrada.length }} de {{ MODULOS.length }} cursos</span>
    </div>
    <p v-if="terminoBusqueda" style="font-size:.8rem;color:var(--texto-suave);margin-bottom:12px;">
      Resultados para «{{ terminoBusqueda }}»
    </p>
    <div class="grid-catalogo">
      <button
        v-for="m in listaFiltrada"
        :key="m.id"
        class="vidrio carta-curso"
        @click="router.push(`/catalogo/${m.id}`)"
      >
        <div class="portada" :style="portadaStyle(m)">
          <span class="num">Módulo {{ m.id }}</span>
          <span v-if="store.progDe(m.id).ok" class="listo">✓ Completado</span>
        </div>
        <div class="cuerpo-carta">
          <h4>{{ m.t }}</h4>
          <p class="desc">{{ m.d }}</p>
          <div class="pista" style="margin-bottom:6px;"><div class="pista-fill" :style="{ width: store.pctModulo(m) + '%' }"></div></div>
          <div class="meta-curso">
            <span>📖 {{ m.lecciones.length }} lecciones</span>
            <span>📝 Evaluación</span>
            <span>🎓 Certificado</span>
          </div>
        </div>
      </button>
      <p v-if="listaFiltrada.length === 0" style="color:var(--texto-suave);">
        No se encontraron cursos para esa búsqueda.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { MODULOS } from '../../data/local.js';

const props = defineProps({ terminoBusqueda: { type: String, default: '' } });
const router = useRouter();
const store = useAppStore();

const listaFiltrada = computed(() => {
  const f = props.terminoBusqueda.toLowerCase().trim();
  if (!f) return MODULOS;
  return MODULOS.filter(m =>
    m.t.toLowerCase().includes(f) ||
    m.d.toLowerCase().includes(f) ||
    m.lecciones.some(l => l.t.toLowerCase().includes(f))
  );
});

function portadaStyle(m) {
  if (store.config.datos) return `background: linear-gradient(135deg, #34D399, #059669);`;
  return `background-image: url('${m.img}'); background-size: cover; background-position: center;`;
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
