<template>
  <div class="info-panel">
    <div class="panel-header">
      <h3><i class="fas fa-info-circle"></i> Información</h3>
    </div>
    <div class="panel-body">
      
      <!-- Estado Nacional -->
      <div v-if="!store.selectedDept">
        <div class="selected-name">Guatemala</div>
        <div class="selected-sub">Selecciona un departamento o municipio en el mapa</div>
        <div class="info-card">
          <div class="ic-label"><i class="fas fa-map"></i> País</div>
          <div class="ic-value">República de Guatemala</div>
        </div>
        <div class="info-card">
          <div class="ic-label"><i class="fas fa-city"></i> Departamentos</div>
          <div class="ic-value">{{ store.departamentos.length }} departamentos</div>
        </div>
        <div class="info-card">
          <div class="ic-label"><i class="fas fa-map-pin"></i> Municipios</div>
          <div class="ic-value">{{ store.totalMunicipios }} municipios</div>
        </div>
      </div>

      <!-- Estado Departamento -->
      <div v-else-if="!store.selectedMuni && currentDeptData">
        <div class="selected-name">{{ store.selectedDept }}</div>
        <div class="selected-sub">Departamento</div>
        <div class="info-card">
          <div class="ic-label"><i class="fas fa-map-pin"></i> Municipios</div>
          <div class="ic-value">{{ currentDeptData.total_municipios }} municipios</div>
        </div>

        <div class="divider"></div>
        <div style="font-size:11px;color:var(--muted);font-weight:700;margin-bottom:12px;text-transform:uppercase;letter-spacing:.06em;">Datos del Departamento</div>

        <div class="field-group">
          <div class="field-label"><i class="fas fa-user-shield"></i> Diputado Asignado</div>
          <input type="text" class="field-input" v-model="formData.diputado_asignado" placeholder="Nombre del diputado…">
        </div>

        <div class="field-group">
          <div class="field-label"><i class="fas fa-users-cog"></i> GPC (Grupo de Coordinación)</div>
          <input type="text" class="field-input" v-model="formData.gpc" placeholder="Nombre o código GPC…">
        </div>

        <div class="field-group">
          <div class="field-label"><i class="fas fa-sticky-note"></i> Notas del Departamento</div>
          <textarea class="field-input" v-model="formData.notas" rows="3" placeholder="Observaciones, seguimiento…"></textarea>
        </div>

        <button class="save-btn" @click="save" :disabled="isSaving">
          <i class="fas fa-save"></i> {{ isSaving ? 'Guardando...' : 'Guardar cambios del departamento' }}
        </button>

        <div class="divider"></div>
        <div style="font-size:12px;color:var(--muted);margin-bottom:10px;font-weight:600;">MUNICIPIOS (Alcaldes y Partidos)</div>
        <div class="muni-grid">
          <div v-for="m in deptMunis" :key="m.id" class="muni-chip" @click="store.selectMuni(m)">
            <div>
              <div style="font-weight:600;margin-bottom:2px">{{ m.municipio }}</div>
              <div class="chip-alcalde">{{ m.alcalde || 'Sin datos' }}</div>
            </div>
            <span v-if="m.partido_alcalde" style="font-size:10px;padding:2px 7px;border-radius:6px;background:rgba(47,129,247,.15);color:var(--blue2);font-weight:700">
              {{ m.partido_alcalde }}
            </span>
          </div>
        </div>
      </div>

      <!-- Estado Municipio -->
      <div v-else-if="store.selectedMuni">
        <div class="selected-name">{{ store.selectedMuni.municipio }}</div>
        <div class="selected-sub">{{ store.selectedMuni.departamento }}</div>

        <div class="info-card">
          <div class="ic-label"><i class="fas fa-user-tie"></i> Alcalde</div>
          <div class="ic-value">{{ store.selectedMuni.alcalde || 'Sin datos' }}</div>
          <div class="ic-party" v-if="store.selectedMuni.partido_alcalde">{{ store.selectedMuni.partido_alcalde }}</div>
        </div>

        <div class="divider"></div>
        <button class="map-btn back-btn" style="width:100%;justify-content:center;padding:10px;" @click="store.selectMuni(null)">
          <i class="fas fa-arrow-left"></i> Volver al departamento
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, watch, reactive, ref, inject } from 'vue';
import { useMunicipiosStore } from '../stores/municipios';

const store = useMunicipiosStore();
const showToast = inject('showToast');
const isSaving = ref(false);

const formData = reactive({
  diputado_asignado: '',
  gpc: '',
  notas: ''
});

const currentDeptData = computed(() => {
  return store.departamentos.find(d => d.departamento === store.selectedDept);
});

const deptMunis = computed(() => {
  return store.municipios.filter(m => m.departamento === store.selectedDept);
});

watch(() => store.selectedDept, (newDept) => {
  if (currentDeptData.value) {
    formData.diputado_asignado = currentDeptData.value.diputado_asignado || '';
    formData.gpc = currentDeptData.value.gpc || '';
    formData.notas = currentDeptData.value.notas || '';
  }
});

const save = async () => {
  isSaving.value = true;
  const success = await store.saveDepartamento({
    departamento: store.selectedDept,
    diputado_asignado: formData.diputado_asignado,
    gpc: formData.gpc,
    notas: formData.notas
  });
  if (success) {
    showToast('✓ Datos guardados correctamente');
  }
  isSaving.value = false;
};
</script>
