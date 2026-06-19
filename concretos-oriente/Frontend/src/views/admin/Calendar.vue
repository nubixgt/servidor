<template>
  <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-8">

    <!-- Header -->
    <div data-aos="fade-right" data-aos-duration="1000">
      <h1 class="text-4xl font-black text-white italic uppercase tracking-tighter leading-none mb-2">
        Calendario <span class="text-primary">· {{ monthName }} {{ currentYear }}</span>
      </h1>
      <p class="text-white/60 font-bold uppercase tracking-widest text-sm">
        {{ activeTab === 'pagos' ? 'Pagos recurrentes programados' : 'Cumpleaños de empleados' }}
      </p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-black/30 border border-white/10 rounded-2xl p-1 w-fit" data-aos="fade-right" data-aos-duration="800">
      <button
        @click="activeTab = 'pagos'"
        :class="[
          'px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
          activeTab === 'pagos'
            ? 'bg-primary text-white shadow-lg shadow-primary/30'
            : 'text-white/50 hover:text-white'
        ]"
      >
        Pagos
      </button>
      <button
        @click="activeTab = 'cumpleanos'"
        :class="[
          'px-8 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all duration-300',
          activeTab === 'cumpleanos'
            ? 'bg-rose-500 text-white shadow-lg shadow-rose-500/30'
            : 'text-white/50 hover:text-white'
        ]"
      >
        Cumpleaños
      </button>
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
          <div v-for="n in startOffset" :key="'e' + n"></div>

          <button
            v-for="day in daysInMonth"
            :key="day"
            @click="selectDay(day)"
            :class="[
              'aspect-square flex flex-col items-center justify-center rounded-2xl text-sm font-black transition-all relative',
              isToday(day) ? 'border-2 border-primary text-white' : 'border border-white/5 text-white/70',
              activeTab === 'pagos'
                ? (hasPayments(day) ? 'bg-primary/15 hover:bg-primary/25' : 'bg-white/3 hover:bg-white/8')
                : (hasBirthdays(day) ? 'bg-rose-500/15 hover:bg-rose-500/25' : 'bg-white/3 hover:bg-white/8'),
              selectedDay === day ? (activeTab === 'pagos' ? 'ring-2 ring-primary ring-offset-1 ring-offset-black/50' : 'ring-2 ring-rose-400 ring-offset-1 ring-offset-black/50') : ''
            ]"
          >
            <span>{{ day }}</span>
            <!-- Dots pagos -->
            <div v-if="activeTab === 'pagos' && hasPayments(day)" class="flex gap-0.5 mt-0.5">
              <span
                v-for="n in Math.min(getPaymentsForDay(day).length, 3)"
                :key="n"
                class="w-1 h-1 rounded-full bg-primary"
              ></span>
            </div>
            <!-- Dots cumpleaños -->
            <div v-if="activeTab === 'cumpleanos' && hasBirthdays(day)" class="flex gap-0.5 mt-0.5">
              <span
                v-for="n in Math.min(getBirthdaysForDay(day).length, 3)"
                :key="n"
                class="w-1 h-1 rounded-full bg-rose-400"
              ></span>
            </div>
          </button>
        </div>

        <!-- Legend -->
        <div class="flex items-center gap-6 mt-6 pt-4 border-t border-white/5">
          <template v-if="activeTab === 'pagos'">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-primary/50"></div>
              <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Días con pagos</span>
            </div>
          </template>
          <template v-else>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-rose-400/50"></div>
              <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Cumpleaños</span>
            </div>
          </template>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-xl border-2 border-primary"></div>
            <span class="text-[10px] font-bold text-white/40 uppercase tracking-widest">Hoy</span>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="glass-card rounded-[32px] p-6 border border-white/5 flex flex-col" data-aos="zoom-in-up" data-aos-duration="1000" data-aos-delay="100">

        <!-- ── PAGOS sidebar ── -->
        <template v-if="activeTab === 'pagos'">
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

          <div v-if="visiblePayments.length > 0" class="mt-4 pt-4 border-t border-white/5">
            <div class="flex justify-between items-center">
              <span class="text-[10px] font-black uppercase tracking-widest text-white/30">Total</span>
              <span class="text-base font-black text-primary">
                Q {{ visiblePayments.reduce((s, i) => s + Number(i.monto || 0), 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
              </span>
            </div>
          </div>
        </template>

        <!-- ── CUMPLEAÑOS sidebar ── -->
        <template v-else>
          <h2 class="text-[10px] font-black uppercase tracking-widest text-white/40 mb-4 flex items-center gap-2">
            <CakeIcon class="w-4 h-4" />
            {{ selectedDay ? `Cumpleaños del día ${selectedDay}` : `Cumpleaños de ${monthName}` }}
          </h2>

          <div v-if="visibleBirthdays.length > 0" class="space-y-3 overflow-y-auto flex-1">
            <div
              v-for="emp in visibleBirthdays"
              :key="emp.id"
              class="bg-black/30 rounded-2xl p-4 border border-white/5 hover:border-rose-400/20 transition-all"
            >
              <div class="flex items-start gap-3">
                <div class="min-w-[44px] text-center bg-rose-500/10 rounded-xl px-2 py-2 border border-rose-500/20">
                  <p class="text-lg font-black text-rose-400 leading-none">{{ emp.dia }}</p>
                  <p class="text-[9px] font-black text-rose-400/60 uppercase tracking-widest">día</p>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-black text-white truncate">{{ emp.nombres }} {{ emp.apellidos }}</p>
                  <p class="text-[10px] text-white/40 mt-0.5">{{ emp.puesto }}</p>
                  <p class="text-xs font-black text-rose-400 mt-1">🎂 {{ emp.edad }} años</p>
                </div>
              </div>
            </div>
          </div>

          <div v-else class="flex-1 flex items-center justify-center">
            <p class="text-white/30 text-sm font-bold text-center">
              {{ selectedDay ? `Sin cumpleaños el día ${selectedDay}` : `Sin cumpleaños en ${monthName}` }}
            </p>
          </div>

          <div v-if="visibleBirthdays.length > 0" class="mt-4 pt-4 border-t border-white/5">
            <div class="flex justify-between items-center">
              <span class="text-[10px] font-black uppercase tracking-widest text-white/30">Total del mes</span>
              <span class="text-base font-black text-rose-400">{{ birthdaysThisMonth.length }} cumpleaño{{ birthdaysThisMonth.length !== 1 ? 's' : '' }}</span>
            </div>
          </div>
        </template>

      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { CalendarDaysIcon, ChevronLeftIcon, ChevronRightIcon, CakeIcon } from '@heroicons/vue/24/outline';
import api from '../../services/api';

const BASE_URL = '/concretos-oriente/Backend/api/v1';

const recurrents = ref([]);
const employees  = ref([]);
const activeTab  = ref('pagos');

const today = new Date();
const currentMonth = ref(today.getMonth());
const currentYear  = ref(today.getFullYear());
const selectedDay  = ref(null);

const MONTHS = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const monthName = computed(() => MONTHS[currentMonth.value]);

const daysInMonth = computed(() => new Date(currentYear.value, currentMonth.value + 1, 0).getDate());

const startOffset = computed(() => {
  const firstDay = new Date(currentYear.value, currentMonth.value, 1).getDay();
  return (firstDay + 6) % 7;
});

const isToday = (day) =>
  day === today.getDate() &&
  currentMonth.value === today.getMonth() &&
  currentYear.value === today.getFullYear();

// ----------------------------------------------------------------
// PAGOS
// ----------------------------------------------------------------
const getPaymentsForDay = (day) => recurrents.value.filter(r => Number(r.dia_pago) === day);
const hasPayments = (day) => getPaymentsForDay(day).length > 0;

const visiblePayments = computed(() => {
  if (selectedDay.value !== null) return getPaymentsForDay(selectedDay.value);
  return [...recurrents.value].sort((a, b) => Number(a.dia_pago) - Number(b.dia_pago));
});

// ----------------------------------------------------------------
// CUMPLEAÑOS
// ----------------------------------------------------------------
const birthdaysThisMonth = computed(() => {
  return employees.value
    .filter(emp => {
      if (!emp.fecha_nacimiento) return false;
      const [, mes] = emp.fecha_nacimiento.split('-');
      return parseInt(mes, 10) === currentMonth.value + 1;
    })
    .map(emp => {
      const [anio, mes, dia] = emp.fecha_nacimiento.split('-');
      const edad = currentYear.value - parseInt(anio, 10);
      return { ...emp, dia: parseInt(dia, 10), mes: parseInt(mes, 10), edad };
    })
    .sort((a, b) => a.dia - b.dia);
});

const getBirthdaysForDay = (day) => birthdaysThisMonth.value.filter(e => e.dia === day);
const hasBirthdays = (day) => getBirthdaysForDay(day).length > 0;

const visibleBirthdays = computed(() => {
  if (selectedDay.value !== null) return getBirthdaysForDay(selectedDay.value);
  return birthdaysThisMonth.value;
});

// ----------------------------------------------------------------
// Navegación
// ----------------------------------------------------------------
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

// ----------------------------------------------------------------
// Fetch
// ----------------------------------------------------------------
onMounted(async () => {
  try {
    const res = await api.get('/recurrents');
    if (res.data.status === 'success') recurrents.value = res.data.data;
  } catch (e) { console.error(e); }

  try {
    const res = await fetch(`${BASE_URL}/personnel`);
    const result = await res.json();
    if (result.status === 'success') employees.value = result.data;
  } catch (e) { console.error(e); }
});
</script>
