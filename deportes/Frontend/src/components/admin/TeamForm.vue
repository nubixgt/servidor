<script setup>
import { reactive } from 'vue'
import ImageUpload from './ImageUpload.vue'

// `restringido`: el encargado no puede cambiar nombre, representante ni DPI.
const props = defineProps({ team: Object, busy: Boolean, restringido: Boolean, errors: { type: Object, default: () => ({}) } })
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  name: '', representante: '', telefono: '', dpi: '', sub_representante_nombre: '', sub_representante_dpi: '', sub_representante_telefono: '',
  short_name: '', city: '', stadium: '', coach: '', primary_color: '#c6ff3b', secondary_color: '#101518', founded_year: null,
  ...props.team,
})
const fotos = reactive({
  foto: { file: null, remove: false },
  foto_representante: { file: null, remove: false },
  foto_sub_representante: { file: null, remove: false },
})
const anioMax = new Date().getFullYear()

// Los campos vacíos opcionales llegan como null desde la BD; los normalizamos al guardar.
const guardar = () => emit('save', { ...form }, { ...fotos })
</script>

<template>
  <form @submit.prevent="guardar">
    <div class="form-grid">
      <div class="full"><ImageUpload v-model="fotos.foto" :current="team?.logo_url" label="Escudo del equipo" /></div>
      <label>Nombre<input v-model.trim="form.name" maxlength="100" required :disabled="restringido" /><small class="field-error">{{ errors.nombre }}</small></label>
      <label>Abreviatura<input v-model.trim="form.short_name" maxlength="4" placeholder="AUR" /></label>

      <label>Representante<input v-model.trim="form.representante" maxlength="100" required :disabled="restringido" /></label>
      <label>Teléfono<input v-model.trim="form.telefono" maxlength="20" required /></label>
      <label>DPI del representante<input v-model.trim="form.dpi" maxlength="20" required :disabled="restringido" /><small class="field-error">{{ errors.dpi }}</small></label>
      <div><ImageUpload v-model="fotos.foto_representante" :current="team?.foto_representante_url" label="Foto del representante" /></div>

      <label>Sub‑representante<input v-model.trim="form.sub_representante_nombre" maxlength="150" /></label>
      <label>DPI del sub‑representante<input v-model.trim="form.sub_representante_dpi" maxlength="13" /></label>
      <label>Teléfono del sub‑representante<input v-model.trim="form.sub_representante_telefono" maxlength="8" /></label>
      <div><ImageUpload v-model="fotos.foto_sub_representante" :current="team?.sub_representante_foto_url" label="Foto del sub‑representante" /></div>

      <label>Ciudad<input v-model.trim="form.city" maxlength="100" /></label>
      <label>Estadio<input v-model.trim="form.stadium" maxlength="100" /></label>
      <label>Entrenador<input v-model.trim="form.coach" maxlength="100" /></label>
      <label>Año de fundación<input v-model.number="form.founded_year" type="number" min="1800" :max="anioMax" /></label>
      <label>Color principal<input v-model="form.primary_color" type="color" /></label>
      <label>Color secundario<input v-model="form.secondary_color" type="color" /></label>
    </div>
    <p v-if="!team" class="form-help">El acceso del encargado se crea automáticamente: usuario = DPI y contraseña = teléfono (sin espacios).</p>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" :disabled="busy" @click="emit('cancel')">Cancelar</button>
      <button class="btn btn-primary" :disabled="busy">{{ busy ? 'Guardando…' : 'Guardar equipo' }}</button>
    </div>
  </form>
</template>
