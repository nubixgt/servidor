<script setup>
defineProps({ texto: String, extra: String, busy: Boolean, error: String, boton: { type: String, default: 'Eliminar' } })
defineEmits(['confirmar', 'cancelar'])
</script>

<template>
  <p v-if="error" class="error-text" role="alert">{{ error }}</p>
  <p class="confirm-copy">{{ texto }}</p>
  <p v-if="extra" class="error-text">{{ extra }}</p>
  <div class="form-actions">
    <button class="btn btn-secondary" :disabled="busy" @click="$emit('cancelar')">Cancelar</button>
    <button class="btn btn-danger" :disabled="busy" @click="$emit('confirmar')">{{ busy ? 'Procesando…' : boton }}</button>
  </div>
</template>
