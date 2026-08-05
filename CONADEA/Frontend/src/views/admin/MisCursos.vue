<template>
  <div>
    <div class="fila-seccion">
      <h2>Mis cursos</h2>
      <button class="ver-todas" @click="router.push('/catalogo')">Ir al catálogo →</button>
    </div>
    <div class="vidrio">
      <div v-if="cursosIniciados.length">
        <div v-for="m in cursosIniciados" :key="m.id" class="curso-fila">
          <div class="miniatura" :style="miniaturaStyle(m)"></div>
          <div class="datos">
            <b>{{ m.t }}</b>
            <div class="pista"><div class="pista-fill" :style="{ width: store.pctModulo(m) + '%' }"></div></div>
            <span class="pct-txt">{{ store.pctModulo(m) }}% completado</span>
          </div>
          <button class="btn-continuar" :class="{ hecho: store.progDe(m.id).ok }" @click="router.push(`/catalogo/${m.id}`)">
            {{ store.progDe(m.id).ok ? 'Repasar' : 'Continuar' }}
          </button>
        </div>
      </div>
      <p v-else style="font-size:.85rem;color:var(--texto-suave);">
        Todavía no has iniciado ningún curso. Explora el <b>catálogo</b> y comienza tu primera lección.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { MODULOS } from '../../data/local.js';

const router = useRouter();
const store = useAppStore();

const cursosIniciados = computed(() =>
  MODULOS.filter(m => store.progDe(m.id).lec.length > 0 || store.progDe(m.id).ok)
);

function miniaturaStyle(m) {
  if (store.config.datos) return `background: linear-gradient(135deg, #34D399, #059669);`;
  return `background-image: url('${m.img}'); background-size: cover; background-position: center;`;
}
</script>

<style scoped>
.curso-fila { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid var(--borde); transition: all 0.2s; }
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
</style>
