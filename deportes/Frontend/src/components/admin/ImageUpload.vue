<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { imgUrl } from '../../services/api'

// v-model = { file: File|null, remove: boolean }. `current` es la ruta de la imagen ya guardada (si hay).
const props = defineProps({
  modelValue: { type: Object, default: () => ({ file: null, remove: false }) },
  current: String,
  label: { type: String, default: 'Imagen' },
})
const emit = defineEmits(['update:modelValue'])

const error = ref('')
const TIPOS = ['image/jpeg', 'image/png', 'image/webp']
const vista = ref(null)

watch(() => props.modelValue.file, (f) => {
  if (vista.value) URL.revokeObjectURL(vista.value)
  vista.value = f ? URL.createObjectURL(f) : null
}, { immediate: true })
onBeforeUnmount(() => vista.value && URL.revokeObjectURL(vista.value))

const previa = computed(() => vista.value || (!props.modelValue.remove && props.current ? imgUrl(props.current) : null))

function elegir(e) {
  const archivo = e.target.files?.[0]
  if (!archivo) return
  error.value = ''
  if (archivo.size > 5 * 1024 * 1024 || !TIPOS.includes(archivo.type)) {
    error.value = 'Selecciona un JPG, PNG o WebP de hasta 5 MB.'
    e.target.value = ''
    return
  }
  emit('update:modelValue', { file: archivo, remove: false })
}

const quitar = () => emit('update:modelValue', { file: null, remove: true })
</script>

<template>
  <div>
    <div class="upload-field">
      <img v-if="previa" :src="previa" alt="Vista previa" class="upload-preview" />
      <div>
        <label>{{ label }}<input type="file" accept="image/jpeg,image/png,image/webp" @change="elegir" /></label>
        <small>JPG, PNG o WebP · Máximo 5 MB</small>
        <button v-if="previa" type="button" class="text-link mt-2" @click="quitar">Quitar imagen</button>
      </div>
    </div>
    <p v-if="error" class="error-text" role="alert">{{ error }}</p>
  </div>
</template>
