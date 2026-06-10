<template>
  <div
    class="relative min-h-screen flex flex-col overflow-x-hidden font-sans bg-[#0a0f1e] text-white -mt-10 md:-mt-14 -mb-[3rem]"
    style="width: 100vw; margin-left: calc(-50vw + 50%);"
  >
    <!-- Cinematic Background -->
    <div class="absolute top-0 right-0 w-full h-[614px] md:w-1/2 md:h-screen pointer-events-none opacity-30 md:opacity-50 overflow-hidden z-0">
      <img
        alt="Premium Event Scene"
        class="w-full h-full object-cover object-center transition-all duration-1000 ease-out scale-105"
        src="https://lh3.googleusercontent.com/aida-public/AB6AXuBf-te7Jx8lFHnda-WiD_84QAIaiT4YyIPWEzclFHKDavGE0bI6_UaUAwYumD_jhr3rPCxBwymYe2VHHKFb_y0jgg1k2ZTSx_QDNtcXOUJQxIAdKpIO0U4ww7Y4SRTb-IpJCYmvlnMDths50SbIivZg16kaqcKoQ4L1s8LOEgyrmVdHlHjp1Fn6c9rAvYHzQbHpsVVMO4rQxWRpygU-_abcl4FXnhOTyHsKRaPnQb1Ifn0gXjnCkSVeE123-BKHw3tvtnhxxMjfsO3R"
        referrerpolicy="no-referrer"
      />
      <div class="absolute inset-0 bg-gradient-to-b from-[#0a0f1e]/10 via-[#0a0f1e]/80 to-[#0a0f1e]"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#0a0f1e] via-[#0a0f1e]/30 to-transparent hidden md:block"></div>
      <div class="absolute top-1/4 right-1/4 w-64 h-64 bg-[#f2ca50]/5 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Content -->
    <main class="relative z-10 w-full max-w-6xl mx-auto px-6 md:px-8 pt-10 pb-16 flex flex-col gap-10 animate-fade-in">

      <!-- Header -->
      <div class="max-w-2xl">
        <h1 class="font-serif text-4xl md:text-5xl font-bold tracking-tight text-white mb-3 leading-tight">
          Resultados en
          <span class="text-[#f2ca50] italic font-normal font-serif"> tiempo real</span>
        </h1>
        <p class="text-sm md:text-base text-[#d0c5af] max-w-xl leading-relaxed">
          Respuestas recopiladas y verificadas mediante nuestro portal seguro.
        </p>
      </div>

      <!-- Total Participation Card -->
      <div class="inline-flex items-center gap-4 bg-white/5 border border-[rgba(153,144,124,0.2)] rounded-xl px-6 py-4 w-fit">
        <div class="w-10 h-10 rounded-full bg-[#f2ca50]/10 flex items-center justify-center">
          <span class="material-symbols-outlined text-[#f2ca50]" style="font-size:20px; font-variation-settings: 'FILL' 1;">groups</span>
        </div>
        <div>
          <div class="text-3xl font-bold text-[#f2ca50]">{{ total }}</div>
          <div class="text-xs text-[#d0c5af] font-medium tracking-wide uppercase">Participaciones Totales</div>
        </div>
      </div>

      <!-- Main Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

        <!-- Progress Bars -->
        <div class="flex flex-col gap-6">
          <h3 class="text-sm font-bold text-[#f2ca50] uppercase tracking-widest">Detalle por Propuestas</h3>

          <!-- Option A -->
          <div :class="['p-5 rounded-2xl border-2 transition-all duration-300', userVote === 'option-a' ? 'border-[#f2ca50] bg-[#f2ca50]/5 gold-glow' : 'border-[rgba(153,144,124,0.2)] bg-white/5']">
            <div class="flex justify-between items-center mb-3">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#f2ca50]"></span>
                <span class="font-semibold text-white text-sm md:text-base">Baile Social en el Estadio</span>
                <span v-if="userVote === 'option-a'" class="px-2 py-0.5 bg-[#f2ca50]/20 text-[#f2ca50] text-[10px] font-bold rounded-full">Tu Voto</span>
              </div>
              <span class="font-black text-[#f2ca50] text-sm">{{ votesA }} votos ({{ pctA }}%)</span>
            </div>
            <div class="w-full bg-white/10 h-3 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-1000"
                style="background: linear-gradient(90deg, #d4af37 0%, #f2ca50 100%);"
                :style="{ width: `${pctA}%` }"
              ></div>
            </div>
            <p class="text-xs text-[#d0c5af] mt-2 italic">Convocatoria expansiva, ideal para toda la comunidad unificada.</p>
            
            <!-- Singer Breakdown -->
            <div class="mt-4 pt-4 border-t border-[rgba(153,144,124,0.2)]">
              <h4 class="text-[10px] font-bold text-[#f2ca50] uppercase tracking-widest mb-3">Preferencia de Cantante</h4>
              <div class="flex flex-col gap-3">
                <div class="w-full">
                  <div class="flex justify-between text-xs mb-1">
                    <span class="text-white">Eddy Herrera</span>
                    <span class="font-bold text-[#f2ca50]">{{ votesEddy }} votos ({{ pctEddy }}%)</span>
                  </div>
                  <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-[#f2ca50] h-full rounded-full transition-all duration-1000" :style="{ width: `${pctEddy}%` }"></div>
                  </div>
                </div>
                <div class="w-full">
                  <div class="flex justify-between text-xs mb-1">
                    <span class="text-white">Wilfredo Vargas</span>
                    <span class="font-bold text-[#f2ca50]">{{ votesWilfredo }} votos ({{ pctWilfredo }}%)</span>
                  </div>
                  <div class="w-full bg-white/10 h-1.5 rounded-full overflow-hidden">
                    <div class="bg-[#f2ca50] h-full rounded-full transition-all duration-1000 opacity-70" :style="{ width: `${pctWilfredo}%` }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Option B -->
          <div :class="['p-5 rounded-2xl border-2 transition-all duration-300', userVote === 'option-b' ? 'border-[#f2ca50] bg-[#f2ca50]/5 gold-glow' : 'border-[rgba(153,144,124,0.2)] bg-white/5']">
            <div class="flex justify-between items-center mb-3">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-[#bdc6e4]"></span>
                <span class="font-semibold text-white text-sm md:text-base">Baile Social en Salón + Concierto</span>
                <span v-if="userVote === 'option-b'" class="px-2 py-0.5 bg-[#f2ca50]/20 text-[#f2ca50] text-[10px] font-bold rounded-full">Tu Voto</span>
              </div>
              <span class="font-black text-[#bdc6e4] text-sm">{{ votesB }} votos ({{ pctB }}%)</span>
            </div>
            <div class="w-full bg-white/10 h-3 rounded-full overflow-hidden">
              <div
                class="bg-[#bdc6e4] h-full rounded-full transition-all duration-1000"
                :style="{ width: `${pctB}%` }"
              ></div>
            </div>
            <p class="text-xs text-[#d0c5af] mt-2 italic">Ambiente íntimo con acústica distinguida y mayor interacción.</p>
          </div>
        </div>

        <!-- Bar Chart -->
        <div class="bg-white/5 border border-[rgba(153,144,124,0.2)] rounded-2xl p-6 flex flex-col justify-between">
          <div class="mb-4">
            <h4 class="text-xs font-bold text-[#d0c5af] uppercase tracking-widest">
              Distribución Gráfica de Preferencias
            </h4>
          </div>
          <div class="h-60 w-full pb-6 relative flex text-[11px] text-[#d0c5af]">
            <!-- Y Axis -->
            <div class="flex flex-col justify-between items-end pr-2 border-r border-white/10 w-8 flex-shrink-0 relative z-10 pb-5">
              <span>{{ maxY }}</span>
              <span>{{ Math.round(maxY / 2) }}</span>
              <span>0</span>
            </div>

            <!-- Chart Area -->
            <div class="flex-grow flex justify-around items-end pl-2 relative">
              <div
                v-for="(entry, index) in chartData"
                :key="index"
                class="flex flex-col items-center group relative h-full w-full max-w-[80px]"
              >
                <div class="w-full flex-grow flex items-end relative h-full pb-6 z-10">
                  <div
                    class="w-full rounded-t-lg transition-all duration-1000 relative"
                    :style="{ backgroundColor: entry.color, height: `${(entry.votos / maxY) * 100}%` }"
                  >
                    <!-- Tooltip -->
                    <div class="opacity-0 group-hover:opacity-100 pointer-events-none absolute -top-14 left-1/2 -translate-x-1/2 bg-[#1d2023] border border-[rgba(153,144,124,0.3)] rounded-lg px-3 py-2 text-xs font-bold text-white shadow-lg whitespace-nowrap z-50 transition-opacity duration-200">
                      {{ entry.name }}<br/>
                      <span class="font-normal text-[#d0c5af]">Votos: </span>
                      <span :style="{ color: entry.color }">{{ entry.votos }}</span>
                    </div>
                  </div>
                </div>
                <span class="absolute bottom-0 text-[10px] text-center w-24 left-1/2 -translate-x-1/2 truncate text-[#d0c5af]">{{ entry.name }}</span>
              </div>

              <!-- Horizontal Grid Lines -->
              <div class="absolute bottom-6 left-0 right-0 h-px bg-white/10 z-0"></div>
              <div class="absolute bottom-1/2 left-0 right-0 h-px bg-white/5 z-0"></div>
            </div>
          </div>
          <div class="text-center pt-2 text-xs text-[#d0c5af]/50 font-mono">
            Actualizado hace unos segundos
          </div>
        </div>
      </div>

    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const votesA = ref(0);
const votesB = ref(0);
const votesEddy = ref(0);
const votesWilfredo = ref(0);
const userVote = ref(null);

const fetchResults = async () => {
  try {
    const response = await axios.get('https://m.nubix.gt/encuesta-baile/Backend/api/v1/votes');
    if (response.data && response.data.status === 'success') {
      votesA.value = response.data.data['option-a'] || 0;
      votesB.value = response.data.data['option-b'] || 0;
      
      if (response.data.data.singers) {
        votesEddy.value = response.data.data.singers['Eddy Herrera'] || 0;
        votesWilfredo.value = response.data.data.singers['Wilfredo Vargas'] || 0;
      }
    }
  } catch (error) {
    console.error('Error fetching votes:', error);
  }
};

onMounted(() => {
  fetchResults();
  setInterval(fetchResults, 5000);
});

defineEmits(['resetVote']);

const total = computed(() => votesA.value + votesB.value);
const pctA = computed(() => total.value > 0 ? Math.round((votesA.value / total.value) * 100) : 0);
const pctB = computed(() => total.value > 0 ? Math.round((votesB.value / total.value) * 100) : 0);

const totalSingers = computed(() => votesEddy.value + votesWilfredo.value);
const pctEddy = computed(() => totalSingers.value > 0 ? Math.round((votesEddy.value / totalSingers.value) * 100) : 0);
const pctWilfredo = computed(() => totalSingers.value > 0 ? Math.round((votesWilfredo.value / totalSingers.value) * 100) : 0);

const maxY = computed(() => {
  const max = Math.max(votesA.value, votesB.value, 10);
  return Math.ceil(max * 1.2);
});

const chartData = computed(() => [
  {
    name: 'Estadio (A)',
    votos: votesA.value,
    porcentaje: pctA.value,
    color: '#f2ca50',
  },
  {
    name: 'Salón + Concierto (B)',
    votos: votesB.value,
    porcentaje: pctB.value,
    color: '#bdc6e4',
  }
]);
</script>

<style scoped>
.gold-glow {
  box-shadow: 0 0 35px rgba(242, 202, 80, 0.18);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  vertical-align: middle;
}
</style>
