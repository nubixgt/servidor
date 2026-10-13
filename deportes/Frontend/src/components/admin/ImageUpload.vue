<script setup>
import { ref } from 'vue'

defineProps({ modelValue: String, label: { type: String, default: 'Imagen' } })
const emit = defineEmits(['update:modelValue', 'busy'])

const error = ref('')
const subiendo = ref(false)
const TIPOS = ['image/jpeg', 'image/png', 'image/webp']

const leer = (archivo) =>
  new Promise((ok, fallo) => {
    const lector = new FileReader()
    lector.onload = () => ok(lector.result)
    lector.onerror = fallo
    lector.readAsDataURL(archivo)
  })

async function elegir(e) {
  const archivo = e.target.files?.[0]
  if (!archivo) return
  error.value = ''
  if (archivo.size > 5 * 1024 * 1024 || !TIPOS.includes(archivo.type)) {
    error.value = 'Selecciona un JPG, PNG o WebP de hasta 5 MB.'
    return
  }
  subiendo.value = true
  emit('busy', true)
  try {
    emit('update:modelValue', await leer(archivo))
  } catch {
    error.value = 'No se pudo leer la imagen.'
  } finally {
    subiendo.value = false
    emit('busy', false)
  }
}
</script>

<template>
  <div>
    <div class="upload-field">
      <img v-if="modelValue" :src="modelValue" alt="Vista previa" class="upload-preview" />
      <div>
        <label>
          {{ label }}
          <input type="file" accept="image/jpeg,image/png,image/webp" :disabled="subiendo" @change="elegir" />
        </label>
        <small>{{ subiendo ? 'Subiendo imagen…' : 'JPG, PNG o WebP · Máximo 5 MB' }}</small>
        <button v-if="modelValue" type="button" class="text-link mt-2" :disabled="subiendo" @click="emit('update:modelValue', null)">Quitar imagen</button>
      </div>
    </div>
    <p v-if="error" class="error-text" role="alert">{{ error }}</p>
  </div>
</template>
