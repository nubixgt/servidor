<template>
  <div class="topbar">
    <!-- Hamburguesa (móvil) -->
    <button class="hamburguesa" @click="emit('abrirMenu')" aria-label="Abrir menú">☰</button>

    <!-- Saludo -->
    <div class="saludo">
      <h1>¡Hola, {{ nombreCorto }}! 👋</h1>
      <p>Sigue aprendiendo y avanza en tu crecimiento profesional.</p>
    </div>

    <!-- Derecha: campana + avatar -->
    <div class="derecha">
      <button class="campana" @click="emit('irA', 'novedades')" aria-label="Novedades">
        🔔
        <span class="punto" :class="{ oculto: store.novVisto }" id="punto-novedades">
          {{ novedadesCount }}
        </span>
      </button>

      <button class="chip-usuario" @click="emit('irA', 'perfil')">
        <span class="avatar" :style="avatarStyle">
          <img v-if="!store.config.datos" :src="avatarUrl" :alt="store.usuario?.nombre" class="avatar-img" />
          <span v-else>{{ iniciales }}</span>
        </span>
        <span>
          <b>{{ store.usuario?.nombre?.split(' ').slice(0, 2).join(' ') }}</b>
          <small>Ver perfil ▾</small>
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useAppStore } from '../../stores/app.js';
import { NOVEDADES } from '../../data/local.js';

const emit = defineEmits(['abrirMenu', 'irA']);
const store = useAppStore();

const novedadesCount = computed(() => Math.min(NOVEDADES.length, 9));

const nombreCorto = computed(() =>
  store.usuario?.nombre?.split(' ')[0] || 'Usuario'
);

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

const avatarStyle = computed(() => {
  if (store.config.datos) return '';
  return 'background: none;';
});
</script>

<style scoped>
.topbar {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.saludo h1 {
  font-size: 2rem;
  font-weight: 800;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

.saludo p {
  font-size: 0.9rem;
  color: var(--texto-suave);
  margin-top: 4px;
}

.derecha {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 18px;
}

.campana {
  position: relative;
  font-size: 1.35rem;
  padding: 6px;
  transition: transform 0.2s ease;
}

.campana:hover {
  transform: scale(1.08);
}

.punto {
  position: absolute;
  top: 2px;
  right: -1px;
  min-width: 18px;
  height: 18px;
  border-radius: 10px;
  background: var(--verde);
  color: #06281A;
  font-size: 0.68rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  box-shadow: 0 0 8px rgba(74, 222, 128, 0.5);
  transition: opacity 0.2s;
}

.punto.oculto {
  display: none;
}

.chip-usuario {
  display: flex;
  align-items: center;
  gap: 12px;
  text-align: left;
  transition: transform 0.2s ease;
}

.chip-usuario:hover {
  transform: translateY(-1px);
}

.avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  flex: none;
  background: linear-gradient(135deg, var(--verde), #16A34A);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  color: #06281A;
  border: 2px solid var(--borde-claro);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}

.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  display: block;
}

.chip-usuario b {
  display: block;
  font-size: 0.9rem;
  font-family: 'Outfit', sans-serif;
  font-weight: 700;
}

.chip-usuario small {
  display: block;
  font-size: 0.74rem;
  color: var(--texto-suave);
  margin-top: 1px;
}

.hamburguesa {
  display: none;
  font-size: 1.6rem;
  padding: 6px 12px;
  border: 1px solid var(--borde);
  border-radius: 12px;
  background: var(--vidrio);
  backdrop-filter: blur(10px);
}

@media (max-width: 980px) {
  .hamburguesa {
    display: block;
  }
}
</style>
