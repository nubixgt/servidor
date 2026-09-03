<template>
  <div class="flex flex-col w-full">
    <AdminPageHeader
      eyebrow="Datos oficiales"
      title="Gestión y edición de estadísticas"
      subtitle="Ajusta manualmente las marcas de cada jugador y la tabla de posiciones federada."
    >
      <template #actions>
        <button
          type="button"
          class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-high text-on-surface hover:bg-surface-variant transition-colors font-label-pill text-label-pill uppercase shadow-sm"
          :disabled="recalculando"
          @click="recalcular"
        >
          <span class="material-symbols-outlined text-[18px] text-secondary" :class="{ 'animate-spin': recalculando }">sync</span>
          <span>Recalcular tabla</span>
        </button>
      </template>
    </AdminPageHeader>

    <!-- Tabs -->
    <div class="flex items-center gap-space-xs bg-surface-container-lowest p-1 rounded-full shadow-sm w-fit mb-space-lg">
      <button
        v-for="t in tabs"
        :key="t.value"
        type="button"
        class="px-space-lg py-space-2xs rounded-full font-label-pill text-label-pill uppercase transition-colors"
        :class="tab === t.value ? 'bg-primary-container text-on-primary-fixed' : 'text-secondary hover:text-on-surface'"
        @click="tab = t.value"
      >{{ t.label }}</button>
    </div>

    <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-sm">Cargando…</div>

    <!-- Jugadores -->
    <div v-else-if="tab === 'jugadores'" class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
      <table class="w-full min-w-[900px] text-left">
        <thead>
          <tr class="font-label-meta text-label-meta uppercase text-secondary border-b border-surface-container-high">
            <th class="p-space-sm">Atleta</th>
            <th class="p-space-sm">Equipo</th>
            <th class="p-space-sm text-center w-16">PJ</th>
            <th class="p-space-sm text-center w-20">PPG</th>
            <th class="p-space-sm text-center w-20">RPG</th>
            <th class="p-space-sm text-center w-20">APG</th>
            <th class="p-space-sm text-center w-20">3P%</th>
            <th class="p-space-sm text-center w-20">TL%</th>
            <th class="p-space-sm text-center w-20">EFF</th>
            <th class="p-space-sm text-center w-24"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="j in jugadores" :key="j.id" class="border-b border-surface-container last:border-0">
            <td class="p-space-sm font-body-sm text-body-sm font-semibold text-on-surface">
              {{ j.nombre_completo }} <span v-if="j.dorsal != null" class="text-secondary">#{{ j.dorsal }}</span>
            </td>
            <td class="p-space-sm font-body-sm text-body-sm text-secondary">{{ j.equipo_nombre || '—' }}</td>
            <td class="p-space-sm"><input v-model.number="j.pj" type="number" min="0" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm"><input v-model.number="j.ppg" type="number" step="0.1" min="0" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm"><input v-model.number="j.rpg" type="number" step="0.1" min="0" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm"><input v-model.number="j.apg" type="number" step="0.1" min="0" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm"><input v-model.number="j.tres_pct" type="number" step="0.1" min="0" max="100" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm"><input v-model.number="j.tl_pct" type="number" step="0.1" min="0" max="100" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm"><input v-model.number="j.eff" type="number" step="0.1" class="cell" @input="mark(j.id)" /></td>
            <td class="p-space-sm text-center">
              <button
                type="button"
                class="row-save"
                :class="{ 'row-save-dirty': dirty.has(j.id) }"
                :disabled="!dirty.has(j.id) || savingId === j.id"
                @click="saveJugador(j)"
              >
                <span class="material-symbols-outlined text-[16px]">{{ savingId === j.id ? 'hourglass_empty' : 'check' }}</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!jugadores.length" class="p-space-lg text-center text-secondary font-body-sm">No hay jugadores registrados.</p>
    </div>

    <!-- Posiciones -->
    <div v-else class="bg-surface-container-lowest rounded-xl shadow-sm overflow-x-auto">
      <p class="px-space-md pt-space-md font-label-meta text-label-meta text-secondary uppercase">
        Ganado +2 · Perdido +1 · Forfeit 0 — la sanción es negativa.
      </p>
      <table class="w-full min-w-[900px] text-left">
        <thead>
          <tr class="font-label-meta text-label-meta uppercase text-secondary border-b border-surface-container-high">
            <th class="p-space-sm">Club</th>
            <th class="p-space-sm text-center w-16">PJ</th>
            <th class="p-space-sm text-center w-16">G</th>
            <th class="p-space-sm text-center w-16">P</th>
            <th class="p-space-sm text-center w-20">PF</th>
            <th class="p-space-sm text-center w-20">PC</th>
            <th class="p-space-sm text-center w-16">DIF</th>
            <th class="p-space-sm text-center w-24">Racha</th>
            <th class="p-space-sm text-center w-20">PTS</th>
            <th class="p-space-sm text-center w-28">Sanción</th>
            <th class="p-space-sm text-center w-24"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in clasificacion" :key="c.equipo_id" class="border-b border-surface-container last:border-0">
            <td class="p-space-sm font-body-sm text-body-sm font-semibold text-on-surface">{{ c.equipo_nombre }}</td>
            <td class="p-space-sm"><input v-model.number="c.pj" type="number" min="0" class="cell" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm"><input v-model.number="c.pg" type="number" min="0" class="cell" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm"><input v-model.number="c.pp" type="number" min="0" class="cell" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm"><input v-model.number="c.pf" type="number" min="0" class="cell" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm"><input v-model.number="c.pc" type="number" min="0" class="cell" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm text-center font-headline-md text-headline-md" :class="(c.pf - c.pc) > 0 ? 'text-primary' : (c.pf - c.pc) < 0 ? 'text-error' : ''">
              {{ (c.pf - c.pc) > 0 ? '+' : '' }}{{ c.pf - c.pc }}
            </td>
            <td class="p-space-sm"><input v-model.trim="c.racha" type="text" maxlength="10" class="cell" placeholder="G3" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm"><input v-model.number="c.puntos_liga" type="number" class="cell" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm"><input v-model.trim="c.sancion" type="text" class="cell" placeholder="—" @input="markC(c.equipo_id)" /></td>
            <td class="p-space-sm text-center">
              <button
                type="button"
                class="row-save"
                :class="{ 'row-save-dirty': dirtyC.has(c.equipo_id) }"
                :disabled="!dirtyC.has(c.equipo_id) || savingC === c.equipo_id"
                @click="saveClasif(c)"
              >
                <span class="material-symbols-outlined text-[16px]">{{ savingC === c.equipo_id ? 'hourglass_empty' : 'check' }}</span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <p v-if="!clasificacion.length" class="p-space-lg text-center text-secondary font-body-sm">No hay equipos registrados.</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue';
import { useToast } from '../../composables/useToast';
import { useAlert } from '../../composables/useAlert';
import estadisticasService from '../../services/estadisticasService';

const toast = useToast();
const alert = useAlert();

const tabs = [
  { value: 'jugadores', label: 'Estadísticas por jugador' },
  { value: 'posiciones', label: 'Posiciones & franquicias' }
];
const tab = ref('jugadores');

const loading = ref(true);
const recalculando = ref(false);
const jugadores = ref([]);
const clasificacion = ref([]);

const dirty = ref(new Set());
const dirtyC = ref(new Set());
const savingId = ref(null);
const savingC = ref(null);

const mark = (id) => dirty.value.add(id) && (dirty.value = new Set(dirty.value));
const markC = (id) => dirtyC.value.add(id) && (dirtyC.value = new Set(dirtyC.value));

async function load() {
  loading.value = true;
  try {
    const [j, c] = await Promise.all([
      estadisticasService.jugadores(),
      estadisticasService.clasificacion()
    ]);
    jugadores.value = j;
    clasificacion.value = c;
    dirty.value = new Set();
    dirtyC.value = new Set();
  } catch {
    toast.error('No se pudieron cargar las estadísticas');
  } finally {
    loading.value = false;
  }
}

async function saveJugador(j) {
  savingId.value = j.id;
  try {
    await estadisticasService.updateJugador(j.id, {
      pj: j.pj, ppg: j.ppg, rpg: j.rpg, apg: j.apg,
      tres_pct: j.tres_pct, tl_pct: j.tl_pct, eff: j.eff
    });
    dirty.value.delete(j.id);
    dirty.value = new Set(dirty.value);
    toast.success('Estadísticas actualizadas');
  } catch (err) {
    alert.error('No se pudo guardar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    savingId.value = null;
  }
}

async function saveClasif(c) {
  savingC.value = c.equipo_id;
  try {
    await estadisticasService.updateClasificacion(c.equipo_id, {
      pj: c.pj, pg: c.pg, pp: c.pp, pf: c.pf, pc: c.pc,
      racha: c.racha ?? '', puntos_liga: c.puntos_liga, sancion: c.sancion ?? ''
    });
    dirtyC.value.delete(c.equipo_id);
    dirtyC.value = new Set(dirtyC.value);
    toast.success('Tabla actualizada');
  } catch (err) {
    alert.error('No se pudo guardar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    savingC.value = null;
  }
}

async function recalcular() {
  recalculando.value = true;
  try {
    const res = await estadisticasService.recalcular();
    clasificacion.value = res.clasificacion;
    dirtyC.value = new Set();
    toast.success(`Tabla recalculada (${res.equipos_actualizados} equipos)`);
  } catch (err) {
    alert.error('No se pudo recalcular', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    recalculando.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.cell {
  width: 100%;
  text-align: center;
  padding: 0.3rem 0.4rem;
  border-radius: 0.5rem;
  background-color: #f3f4f5;
  color: #191c1d;
  font-size: 13px;
  font-weight: 600;
  outline: none;
}
.cell:focus {
  background-color: #ffffff;
  box-shadow: 0 0 0 2px #ccff00;
}
.row-save {
  width: 2rem;
  height: 2rem;
  border-radius: 9999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #e7e8e9;
  color: #747a60;
}
.row-save-dirty {
  background-color: #ccff00;
  color: #161e00;
}
.row-save:disabled {
  opacity: 0.5;
}
</style>
