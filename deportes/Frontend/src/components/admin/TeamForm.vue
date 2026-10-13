<script setup>
import { reactive, ref } from 'vue'
import ImageUpload from './ImageUpload.vue'

const props = defineProps({ team: Object, busy: Boolean, errors: { type: Object, default: () => ({}) } })
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  name: '', short_name: '', city: '', stadium: '', coach: '',
  primary_color: '#c6ff3b', secondary_color: '#101518', founded_year: new Date().getFullYear(), logo_url: null,
  ...props.team,
})
const subiendo = ref(false)
const anioMax = new Date().getFullYear()
</script>

<template>
  <form @submit.prevent="emit('save', { ...form })">
    <div class="form-grid">
      <div class="full"><ImageUpload v-model="form.logo_url" label="Escudo del equipo" @busy="subiendo = $event" /></div>
      <label>Nombre<input v-model.trim="form.name" maxlength="80" required /><small class="field-error">{{ errors.name }}</small></label>
      <label>Abreviatura<input v-model.trim="form.short_name" maxlength="4" required placeholder="AUR" /></label>
      <label>Ciudad<input v-model.trim="form.city" maxlength="100" required /></label>
      <label>Estadio<input v-model.trim="form.stadium" maxlength="100" required /></label>
      <label>Entrenador<input v-model.trim="form.coach" maxlength="100" required /></label>
      <label>Año de fundación<input v-model.number="form.founded_year" type="number" min="1800" :max="anioMax" required /></label>
      <label>Color principal<input v-model="form.primary_color" type="color" /></label>
      <label>Color secundario<input v-model="form.secondary_color" type="color" /></label>
    </div>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" :disabled="busy || subiendo" @click="emit('cancel')">Cancelar</button>
      <button class="btn btn-primary" :disabled="busy || subiendo">{{ busy ? 'Guardando…' : 'Guardar equipo' }}</button>
    </div>
  </form>
</template>
