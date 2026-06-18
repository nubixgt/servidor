<template>
  <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-8">

    <!-- Header -->
    <div data-aos="fade-right" data-aos-duration="1000">
      <h1 class="text-4xl font-black text-white italic uppercase tracking-tighter leading-none mb-2">
        Calendario <span class="text-primary">· {{ monthName }} {{ currentYear }}</span>
      </h1>
      <p class="text-white/60 font-bold uppercase tracking-widest text-sm">Pagos recurrentes programados</p>
    </div>

    <!-- Main layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Calendar grid -->
      <div class="lg:col-span-2 glass-card rounded-[32px] p-6 md:p-8 border border-white/5" data-aos="zoom-in-up" data-aos-duration="1000">

        <!-- Month nav -->
        <div class="flex items-center justify-between mb-8">
          <button @click="prevMonth" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-primary/20 hover:text-primary text-white/50 flex items-center justify-center transition-all border border-white/5">
            <ChevronLeftIcon class="w-5 h-5" />
          </button>
          <span class="text-base font-black text-white uppercase tracking-widest">{{ monthName }} {{ currentYear }}</span>
          <button @click="nextMonth" class="w-10 h-10 rounded-xl bg-white/5 hover:bg-primary/20 hover:text-primary text-white/50 flex items-center justify-center transition-all border border-white/5">
            <ChevronRightIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Day headers -->
        <div class="grid grid-cols-7 mb-3">
          <div v-for="d in ['L','M','M','J','V','S','D']" :key="d" class="text-center text-[10px] font-black uppercase tracking-widest text-white/30 py-2">{{ d }}</div>
        </div>

        <!-- Day cells -->
        <div class="grid grid-cols-7 gap-1.5">
          <!-- Empty offset cells -->
          <div v-for="n in startOffset" :key="'e' + n"></div>

          <!-- Day cells -->
          <button
            v-for="day in daysInMonth"
            :key="day"
            @click="selectDay(day)"
            :class="[
              'aspect-square flex flex-col items-center justify-center rounded-2xl text-sm font-black transition-all relative',
              isToday(day) ? 'border-2 border-primary text-white' : 'border border-white/5 text-white/70',
              hasPayments(day) ? 'bg-primary/15 hover:bg-primary/25' : 'bg-white/3 hover:bg-white/8',
              selectedDay === day ? 'ring-2 ring-primary ring-offset-1 ring-offset-black/50' : ''
            ]"
          >
            <span>{{ day }}</span>
            <div v-if="hasPayments(day)" class="flex gap-0.5 mt-0.5">
              <span
                v-for="n in Math.min(getPaymentsForDay(day).length, 3)"
                :key="n"
                class="w-1 h-1 rounded-full bg-primary"
              ></span>
            </div>
          </button>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-6 mt-6 pt-4 border-t border-white/5">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-primary/50"></div>
            <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Días con pagos</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-xl border-2 border-primary"></div>
            <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Hoy</span>
          </div>
        </div>
      </div>

      <!-- Sidebar: payments list -->
      <div class="glass-card rounded-[32px] p-6 border border-white/5 flex flex-col" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="100">
        <h2 class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-4 flex items-center gap-2">
          <CalendarDaysIcon class="w-4 h-4" />
          {{ selectedDay ? `Pagos del día ${selectedDay}` : 'Pagos del mes' }}
        </h2>

        <div v-if="visiblePayments.length > 0" class="space-y-3 overflow-y-auto flex-1">
          <div
            v-for="item in visiblePayments"
            :key="item.id"
            class="bg-black/30 rounded-2xl p-4 border border-white/5 hover:border-primary/20 transition-all"
          >
            <div class="flex items-start gap-3">
              <div class="min-w-[44px] text-center bg-primary/10 rounded-xl px-2 py-2 border border-primary/20">
                <p class="text-lg font-black text-primary leading-none">{{ item.dia_pago }}</p>
                <p class="text-[9px] font-black text-primary/60 uppercase tracking-widest">día</p>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-black text-white truncate">{{ item.concepto }}</p>
                <p v-if="item.descripcion" class="text-[10px] text-white/40 mt-0.5 line-clamp-1">{{ item.descripcion }}</p>
                <p class="text-xs font-black text-primary mt-1">Q {{ Number(item.monto).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="flex-1 flex items-center justify-center">
          <p class="text-white/30 text-sm font-bold text-center">
            {{ selectedDay ? `Sin pagos el día ${selectedDay}` : 'Sin pagos registrados este mes' }}
          </p>
        </div>

        <!-- Total -->
        <div v-if="visiblePayments.length > 0" class="mt-4 pt-4 border-t border-white/5">
          <div class="flex justify-between items-center">
            <span class="text-[10px] font-black uppercase tracking-widest text-white/30">Total</span>
            <span class="text-base font-black text-primary">
              Q {{ visiblePayments.reduce((s, i) => s + Number(i.monto || 0), 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
            </span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import api from '../../services/api';

const recurrents = ref([]);
const today = new Date();
const currentMonth = ref(today.getMonth());
const currentYear = ref(today.getFullYear());
const selectedDay = ref(null);

const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const monthName = computed(() => MONTHS[currentMonth.value]);

const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());

// Monday-based offset (0=Mon ... 6=Sun)
const startOffset = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1).getDay();
  return (firstDay + 6) % 7;
});

const isToday = (day) =>
  day === today.getDate() &&
  currentMonth.value === today.getMonth() &&
  currentYear.value === today.getFullYear();

const getPaymentsForDay = (day) => recurrents.value.filter(r => Number(r.dia_pago) === day);
const hasPayments = (day) => getPaymentsForDay(day).length > 0;

const visiblePayments = computed(() => {
  if (selectedDay.value !== null) return getPaymentsForDay(selectedDay.value);
  return [...recurrents.value].sort((a, b) => Number(a.dia_pago) - Number(b.dia_pago));
});

const selectDay = (day) => {
  selectedDay.value = selectedDay.value === day ? null : day;
};

const prevMonth = () => {
  selectedDay.value = null;
  if (currentMonth.value === 0) { currentMonth.value = 11; currentYear.value--; }
  else currentMonth.value--;
};

const nextMonth = () => {
  selectedDay.value = null;
  if (currentMonth.value === 11) { currentMonth.value = 0; currentYear.value++; }
  else currentMonth.value++;
};

onMounted(async () => {
  try {
    const res = await api.get('/recurrents');
    if (res.data.status === 'success') recurrents.value = res.data.data;
  } catch (e) { console.error(e); }
});
</script>
