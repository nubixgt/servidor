<template>
  <div class="pt-20 pb-16 px-4 md:px-8 max-w-[1600px] mx-auto space-y-8 text-white min-h-screen">
    
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
      <div class="space-y-3">
        <h2 class="text-4xl font-black text-white italic uppercase tracking-tighter">Estado de Cuenta</h2>
        <p class="text-white/40 font-bold uppercase tracking-[0.2em] text-xs">Libro mayor de movimientos bancarios unificados</p>
      </div>
      <button
        @click="exportToExcel"
        class="glass-button-primary bg-emerald-600/20 border-emerald-500/50 text-emerald-400 px-8 py-5 rounded-3xl text-xs font-black uppercase tracking-widest shadow-2xl flex items-center gap-3 hover:scale-105 active:scale-95 transition-all"
      >
        <ArrowDownTrayIcon class="w-4 h-4" /> Exportar a Excel
      </button>
    </div>

    <!-- Filtros -->
    <div class="glass-card p-6 rounded-[32px] border border-white/10 flex flex-wrap gap-4 items-center">
      <div class="flex-1 min-w-[250px]">
        <label class="text-[10px] font-black uppercase tracking-wider text-white/50 mb-2 block">Búsqueda</label>
        <div class="relative">
          <MagnifyingGlassIcon class="w-4 h-4 text-white/40 absolute left-4 top-1/2 -translate-y-1/2" />
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Buscar por correlativo, nombre o proyecto..."
            class="w-full bg-black/20 border border-white/10 rounded-2xl pl-10 pr-4 py-3 text-sm text-white focus:outline-none focus:border-primary/50 transition-colors"
          />
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="glass-card rounded-[32px] border border-white/10 overflow-hidden relative">
      <div v-if="loading" class="p-12 text-center text-white/40 font-black uppercase tracking-widest text-xs flex flex-col items-center gap-4">
        <div class="w-8 h-8 border-4 border-primary/30 border-t-primary rounded-full animate-spin"></div>
        Cargando estado de cuenta...
      </div>
      <div v-else-if="filteredData.length === 0" class="p-12 text-center text-white/40 font-black uppercase tracking-widest text-xs">
        No se encontraron movimientos registrados.
      </div>
      <div v-else class="overflow-x-auto custom-scrollbar">
        <table class="w-full text-left min-w-[1200px]" id="estado-cuenta-table">
          <thead>
            <tr class="bg-black/40 border-b border-white/10">
              <th class="px-5 py-4 text-[10px] font-black text-white/50 uppercase tracking-[0.2em] whitespace-nowrap">Fecha</th>
              <th class="px-5 py-4 text-[10px] font-black text-white/50 uppercase tracking-[0.2em] whitespace-nowrap">Correlativo</th>
              <th class="px-5 py-4 text-[10px] font-black text-white/50 uppercase tracking-[0.2em] whitespace-nowrap">Nombre</th>
              <th class="px-5 py-4 text-[10px] font-black text-white/50 uppercase tracking-[0.2em] min-w-[200px]">Descripción</th>
              <th
                v-for="banco in banks"
                :key="banco"
                class="px-5 py-4 text-[10px] font-black text-primary uppercase tracking-[0.2em] whitespace-nowrap text-right bg-primary/5"
              >
                {{ banco }}
              </th>
              <th class="px-5 py-4 text-[10px] font-black text-white/50 uppercase tracking-[0.2em] whitespace-nowrap">Proyectos</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-white/5">
            <tr
              v-for="(row, idx) in filteredData"
              :key="idx"
              class="hover:bg-white/[0.02] transition-colors"
            >
              <td class="px-5 py-4 text-xs font-bold text-white/70 whitespace-nowrap">{{ row.fecha }}</td>
              <td class="px-5 py-4">
                <span class="text-[10px] font-black tracking-widest px-2 py-1 rounded bg-white/5 border border-white/10">
                  {{ row.prefix }}{{ row.id }}
                </span>
              </td>
              <td class="px-5 py-4 text-xs font-bold text-white uppercase">{{ row.nombre || '-' }}</td>
              <td class="px-5 py-4 text-xs text-white/60">{{ row.descripcion || '-' }}</td>
              
              <!-- Columnas dinámicas de bancos -->
              <td
                v-for="banco in banks"
                :key="banco"
                class="px-5 py-4 text-xs font-black text-right border-l border-white/5 bg-white/[0.01]"
                :class="getAmountColorClass(row, banco)"
              >
                {{ getAmountFormatted(row, banco) }}
              </td>

              <td class="px-5 py-4 text-xs font-black text-cyan-400 uppercase">{{ row.proyecto || '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { ArrowDownTrayIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

import api from '../../services/api';

const loading = ref(true);
const data = ref([]);
const searchTerm = ref('');
const banks = ref([]);

const fetchData = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/statement`);
    if (res.data.status === 'success') {
      data.value = res.data.data || [];
      extractBanks();
    }
  } catch (error) {
    console.error(error);
  }
  loading.value = false;
};

const extractBanks = () => {
  const bankSet = new Set();
  data.value.forEach(row => {
    if (row.banco) bankSet.add(row.banco);
  });
  // Sort alphabetically but put "Otros" at the end if it exists
  const sorted = Array.from(bankSet).sort();
  const otrosIdx = sorted.findIndex(b => b.toLowerCase() === 'otros');
  if (otrosIdx !== -1) {
    const otros = sorted.splice(otrosIdx, 1)[0];
    sorted.push(otros);
  }
  banks.value = sorted;
};

const filteredData = computed(() => {
  if (!searchTerm.value) return data.value;
  const term = searchTerm.value.toLowerCase();
  return data.value.filter(r => 
    (r.nombre || '').toLowerCase().includes(term) ||
    (r.proyecto || '').toLowerCase().includes(term) ||
    (`${r.prefix}${r.id}`).toLowerCase().includes(term) ||
    (r.descripcion || '').toLowerCase().includes(term)
  );
});

const getAmountFormatted = (row, banco) => {
  if (row.banco !== banco) return '';
  const val = Number(row.monto) || 0;
  return `Q ${val.toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
};

const getAmountColorClass = (row, banco) => {
  if (row.banco !== banco) return 'text-white/20';
  return row.tipo === 'Ingreso' ? 'text-emerald-400' : 'text-rose-400';
};

const exportToExcel = () => {
  const header = ['FECHA', 'CORRELATIVO', 'NOMBRE', 'DESCRIPCION', ...banks.value.map(b => b.toUpperCase()), 'PROYECTOS'];
  let csvContent = "data:text/csv;charset=utf-8," + header.join(",") + "\n";

  filteredData.value.forEach(r => {
    const rowArray = [
      r.fecha,
      `${r.prefix}${r.id}`,
      `"${r.nombre || ''}"`,
      `"${r.descripcion || ''}"`
    ];

    banks.value.forEach(banco => {
      if (r.banco === banco) {
        const val = Number(r.monto) || 0;
        rowArray.push(r.tipo === 'Ingreso' ? val : -val);
      } else {
        rowArray.push('');
      }
    });

    rowArray.push(`"${r.proyecto || ''}"`);
    csvContent += rowArray.join(",") + "\n";
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `Estado_De_Cuenta_${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

onMounted(() => {
  fetchData();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}
</style>
