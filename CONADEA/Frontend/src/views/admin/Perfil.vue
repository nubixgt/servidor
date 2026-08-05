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
          <p>🏢 {{ store.usuario?.org }}<br>📍 {{ store.usuario?.muni }} · {{ store.usuario?.act }}<br>📅 En el programa desde el {{ store.usuario?.desde }}</p>
        </div>
      </div>
      <div class="estadisticas">
        <div class="stat-caja"><b>{{ store.pctGlobal }}%</b><small>Avance</small></div>
        <div class="stat-caja"><b>{{ store.leccionesHechas }}/{{ store.totalLecciones }}</b><small>Lecciones</small></div>
        <div class="stat-caja"><b>{{ store.modulosCompletos }}</b><small>Certificados</small></div>
        <div class="stat-caja"><b>{{ store.racha }}</b><small>Días de racha</small></div>
      </div>
    </div>

    <!-- Avance por curso -->
    <div class="vidrio">
      <div class="cab-tarjeta"><h3>Avance por curso</h3></div>
      <table class="tabla-avance">
        <tbody>
          <tr v-for="m in MODULOS" :key="m.id">
            <td class="mod-ic">{{ m.ic }}</td>
            <td>
              <b style="font-size:.85rem;">{{ m.t }}</b>
              <div class="pista" style="margin-top:5px;">
                <div class="pista-fill" :style="{ width: store.pctModulo(m) + '%' }"></div>
              </div>
            </td>
            <td class="td-right">
              <button v-if="store.progDe(m.id).ok" class="btn-cert" @click="router.push('/certificados')">🎓 Certificado</button>
              <span v-else style="font-size:.78rem;color:var(--texto-suave);">{{ store.pctModulo(m) }}%</span>
            </td>
          </tr>
        </tbody>
      </table>
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
.btn-cert { border: 1.5px solid var(--oro); border-bottom: 3px solid #B45309; color: var(--oro); font-size: 0.74rem; font-weight: 700; padding: 5px 10px 7px; border-radius: 10px; background: transparent; cursor: pointer; transition: all 0.1s; }
.btn-cert:hover { background: var(--oro); color: #3A2A00; }
</style>
