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

        <!-- DIPUTADOS -->
        <div class="field-group">
          <div class="field-label"><i class="fas fa-user-shield"></i> Diputados Asignados</div>
          
          <div v-if="currentDeptData.diputado_asignado" style="margin-bottom: 10px; display: flex; flex-direction: column; gap: 6px;">
            <div v-for="(dip, idx) in currentDeptData.diputado_asignado.split('\\n')" :key="idx" class="muni-chip" style="padding: 8px 10px;">
              <div style="font-weight:600; font-size: 13px;">{{ dip }}</div>
            </div>
          </div>
          
          <div style="display:flex; gap:8px;">
            <input type="text" class="field-input" v-model="newDiputado" placeholder="Escribe un nuevo diputado..." @keyup.enter="addDiputado">
            <button class="map-btn" style="background:var(--blue); color:white; border:none; width:auto; padding: 0 15px;" @click="addDiputado" :disabled="isSaving || !newDiputado.trim()">
              <i class="fas fa-plus"></i> Añadir
            </button>
          </div>
        </div>

        <!-- GPC -->
        <div class="info-card" v-if="currentDeptData.gpc && !isEditingGpc" style="margin-top:20px;">
          <div class="ic-label" style="display:flex; justify-content:space-between;">
            <span><i class="fas fa-users-cog"></i> GPC</span>
            <span style="cursor:pointer; color:var(--blue2);" @click="isEditingGpc = true"><i class="fas fa-edit"></i></span>
          </div>
          <div class="ic-value">{{ currentDeptData.gpc }}</div>
        </div>
        
        <div class="field-group" v-else style="margin-top:20px;">
          <div class="field-label"><i class="fas fa-users-cog"></i> GPC (Grupo de Coordinación)</div>
          <div style="display:flex; gap:8px;">
            <input type="text" class="field-input" v-model="formData.gpc" placeholder="Nombre o código GPC…" @keyup.enter="saveGpc">
            <button class="map-btn" style="background:var(--blue); color:white; border:none; width:auto; padding: 0 15px;" @click="saveGpc" :disabled="isSaving">
              <i class="fas fa-save"></i>
            </button>
          </div>
        </div>

        <!-- Notas ocultas temporalmente -->
        <!--
        <div class="field-group">
          <div class="field-label"><i class="fas fa-sticky-note"></i> Notas del Departamento</div>
          <textarea class="field-input" v-model="formData.notas" rows="3" placeholder="Observaciones, seguimiento…"></textarea>
        </div>
        -->

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
const isEditingGpc = ref(false);
const newDiputado = ref('');

function norm(s) { return (s||'').toUpperCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim(); }

const formData = reactive({
  diputado_asignado: '',
  gpc: '',
  notas: ''
});

const currentDeptData = computed(() => {
  if (!store.selectedDept) return null;
  return store.departamentos.find(d => norm(d.departamento) === norm(store.selectedDept));
});

const deptMunis = computed(() => {
  if (!store.selectedDept) return [];
  return store.municipios.filter(m => norm(m.departamento) === norm(store.selectedDept));
});

watch(() => store.selectedDept, (newDept) => {
  isEditingGpc.value = false;
  newDiputado.value = '';
  if (currentDeptData.value) {
    formData.diputado_asignado = currentDeptData.value.diputado_asignado || '';
    formData.gpc = currentDeptData.value.gpc || '';
    formData.notas = currentDeptData.value.notas || '';
  }
});

const saveGpc = async () => {
  isSaving.value = true;
  const success = await store.saveDepartamento({
    departamento: store.selectedDept,
    diputado_asignado: formData.diputado_asignado,
    gpc: formData.gpc,
    notas: formData.notas
  });
  if (success) {
    showToast('✓ GPC guardado');
    isEditingGpc.value = false;
  }
  isSaving.value = false;
};

const addDiputado = async () => {
  if (!newDiputado.value.trim()) return;
  isSaving.value = true;
  
  const existing = currentDeptData.value.diputado_asignado || '';
  const updatedList = existing ? existing + '\n' + newDiputado.value.trim() : newDiputado.value.trim();
  formData.diputado_asignado = updatedList;

  const success = await store.saveDepartamento({
    departamento: store.selectedDept,
    diputado_asignado: updatedList,
    gpc: formData.gpc,
    notas: formData.notas
  });
  
  if (success) {
    showToast('✓ Diputado añadido');
    newDiputado.value = ''; // Borrar el espacio
  }
  isSaving.value = false;
};
</script>
