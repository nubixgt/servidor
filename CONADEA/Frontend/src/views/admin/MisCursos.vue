<template>
  <div>
    <div class="fila-seccion">
      <h2>Mis cursos</h2>
      <button class="ver-todas" @click="router.push('/catalogo')">Ir al catálogo →</button>
    </div>

    <div class="toggle-pills">
      <button class="pill" :class="{ activa: !completados }" @click="completados = false">En progreso</button>
      <button class="pill" :class="{ activa: completados }" @click="completados = true">Completados</button>
    </div>

    <div v-if="cargando" class="vidrio">
      <p style="font-size:.85rem;color:var(--texto-suave);">Cargando cursos...</p>
    </div>

    <div v-else-if="error" class="vidrio">
      <p style="font-size:.85rem;color:var(--rojo);margin-bottom:12px;">{{ error }}</p>
      <button class="btn btn-verde" @click="cursosStore.cargar()">Reintentar</button>
    </div>

    <div v-else class="vidrio">
      <div v-if="lista.length">
        <div v-for="c in lista" :key="c.id" class="curso-fila" @click="abrirCurso(c)">
          <div class="miniatura" :style="miniaturaStyle(c)"></div>
          <div class="datos">
            <b>{{ c.titulo }}</b>
            <div class="pista"><div class="pista-fill" :style="{ width: pctCurso(c) + '%' }"></div></div>
            <span class="pct-txt">{{ pctCurso(c) }}% completado</span>
          </div>
          <button class="btn-continuar" :class="{ hecho: aprobado(c.id) }" @click.stop="abrirCurso(c)">
            {{ aprobado(c.id) ? 'Repasar' : 'Continuar' }}
          </button>
        </div>
      </div>
      <p v-else style="font-size:.85rem;color:var(--texto-suave);">
        {{ completados
          ? 'Todavía no has completado ningún curso.'
          : 'Todavía no has iniciado ningún curso. Explora el catálogo y comienza tu primera lección.' }}
      </p>
    </div>

    <div class="banner-catalogo">
      <h3>Explora más cursos</h3>
      <p>Accede a todo nuestro catálogo de cursos disponibles.</p>
      <button class="btn btn-verde" @click="router.push('/catalogo')">Ver catálogo →</button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { storeToRefs } from 'pinia';
import { useCursosStore } from '../../stores/cursos.js';

const router = useRouter();
const store = useAppStore();
const cursosStore = useCursosStore();
const { cursos, cargando, error } = storeToRefs(cursosStore);
const { pctCurso, aprobado, enProgreso } = cursosStore;

onMounted(() => cursosStore.cargar());

const completados = ref(false);

const lista = computed(() =>
  cursos.value.filter((c) => (completados.value ? aprobado(c.id) : enProgreso(c)))
);

function miniaturaStyle(c) {
  if (store.config.datos) return `background: linear-gradient(135deg, #34D399, #059669);`;
  return `background-image: url('${c.imagen_url}'); background-size: cover; background-position: center;`;
}

function abrirCurso(c) {
  router.push(`/curso/${c.id}`);
}
</script>

<style scoped>
.toggle-pills { display: flex; gap: 10px; margin-bottom: 16px; }
.pill { padding: 9px 18px; border-radius: 20px; font-size: 0.8rem; font-weight: 800; color: var(--texto-suave); background: var(--vidrio-2); border: 1px solid var(--borde); transition: all 0.2s ease; }
.pill.activa { background: linear-gradient(135deg, var(--verde), var(--verde-fuerte)); color: #06281A; border-color: transparent; }

.curso-fila { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); transition: all 0.2s; cursor: pointer; }
.curso-fila:hover { padding-left: 4px; }
.curso-fila:last-of-type { border-bottom: none; }
.miniatura { width: 64px; height: 48px; border-radius: 12px; flex: none; border: 1px solid var(--borde-claro); }
.datos { flex: 1; min-width: 0; }
.datos b { font-size: 0.92rem; display: block; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-family: 'Outfit', sans-serif; font-weight: 700; }
.pct-txt { font-size: 0.78rem; color: var(--texto-suave); margin-top: 4px; display: block; }
.btn-continuar { border: 1.5px solid var(--verde); border-bottom: 3.5px solid var(--verde-oscuro); color: var(--verde); font-size: 0.78rem; font-weight: 700; padding: 7px 16px 9px; border-radius: 12px; flex: none; transition: all 0.1s ease; background: transparent; cursor: pointer; }
.btn-continuar:hover { background: var(--verde); color: #06281A; transform: translateY(-1.5px); border-bottom-width: 5px; }
.btn-continuar.hecho { border-color: var(--oro); border-bottom-color: #B45309; color: var(--oro); }
.btn-continuar.hecho:hover { background: var(--oro); color: #3A2A00; }

.banner-catalogo { margin-top: 20px; padding: 20px; border-radius: 20px; background: linear-gradient(135deg, #15803D, #0369A1); }
.banner-catalogo h3 { font-size: 1.05rem; font-family: 'Outfit', sans-serif; margin-bottom: 4px; }
.banner-catalogo p { font-size: 0.85rem; color: rgba(255,255,255,0.85); margin-bottom: 14px; }
</style>
