<script setup>
import { computed } from 'vue';
import { useModelStore } from '../../stores/modelStore';
import { ROUNDS } from '../../utils/rubrics';
import MyScoresTable from '../../components/dashboard/MyScoresTable.vue';
import { getUserPhoto } from '../../data/userPhotos';

const store = useModelStore();

const usuarioActual = computed(() => {
  try {
    return JSON.parse(localStorage.getItem('usuario'));
  } catch {
    return null;
  }
});

const usuarioNombre = computed(() => usuarioActual.value?.nombre || 'Jurado Oficial');
const usuarioFoto = computed(() => getUserPhoto(usuarioActual.value?.id));

// Cuántos participantes calificó ESTE jurado en cada ronda (de los que ya existen en el listado).
const roundProgress = computed(() => {
  return ROUNDS.map((round) => {
    const done = Object.keys(store.myScores[round.key] || {}).length;
    const total = store.participants.length;
    return { key: round.key, label: round.label, done, total };
  });
});

// Total de votos emitidos por este jurado, sumando las 3 rondas.
const votosEmitidos = computed(() => roundProgress.value.reduce((acc, r) => acc + r.done, 0));
const votosPosibles = computed(() => store.participants.length * ROUNDS.length);
</script>

<template>
  <div class="flex-grow px-6 md:px-16 py-12 max-w-5xl mx-auto w-full text-white">

    <section class="mb-12 flex items-center gap-5">
      <img
        :src="usuarioFoto"
        :alt="usuarioNombre"
        class="w-16 h-16 md:w-20 md:h-20 rounded-full object-cover border border-white/20 shrink-0"
      />
      <div>
        <h1 class="text-3xl font-light tracking-tight text-white mb-2 uppercase">Mi <span class="text-amber-400 font-normal">Panel</span></h1>
        <p class="text-sm text-white/50 leading-relaxed max-w-2xl">
          Este es tu propio avance como jurado: solo las calificaciones que TÚ has enviado. Para ver el
          ranking combinado de todos los jurados, entra a "Tabla de Posiciones".
        </p>
      </div>
    </section>

    <!-- Stat Tiles -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
      <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-5">
        <p class="text-[10px] font-bold tracking-widest text-white/40 uppercase mb-2">Votos Emitidos</p>
        <p class="text-3xl font-light text-amber-400">{{ votosEmitidos }}<span class="text-base text-white/30">/{{ votosPosibles }}</span></p>
      </div>
      <div v-for="round in roundProgress" :key="round.key" class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-2xl p-5">
        <p class="text-[10px] font-bold tracking-widest text-white/40 uppercase mb-2">{{ round.label }}</p>
        <p class="text-3xl font-light text-white mb-3">{{ round.done }}<span class="text-base text-white/30">/{{ round.total }}</span></p>
        <div class="h-1 bg-white/10 rounded-full overflow-hidden">
          <div
            class="h-full bg-amber-400 transition-all duration-500"
            :style="{ width: `${round.total > 0 ? (round.done / round.total) * 100 : 0}%` }"
          ></div>
        </div>
      </div>
    </section>

    <!-- Mis calificaciones por participante -->
    <MyScoresTable title="Señoritas" :models="store.senoritas" />
    <MyScoresTable title="Jóvenes" :models="store.jovenes" />

  </div>
</template>
