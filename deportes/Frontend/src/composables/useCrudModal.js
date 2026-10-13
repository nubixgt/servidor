import { ref } from 'vue'

// Estado común de las pantallas de administración: modal (formulario / eliminar / eventos),
// aviso de éxito y errores. `guardar`/`eliminar` reciben la función que llama a useAdminData.
export function useCrudModal() {
  const modo = ref(null) // null | 'form' | 'delete' | 'events'
  const item = ref(null)
  const ocupado = ref(false)
  const error = ref('')
  const campos = ref({})
  const aviso = ref('')

  function abrir(nuevoModo, registro = null) {
    item.value = registro ? { ...registro } : null
    modo.value = nuevoModo
    error.value = ''
    campos.value = {}
  }

  function cerrar() {
    if (ocupado.value) return
    modo.value = null
    item.value = null
    error.value = ''
  }

  async function ejecutar(accion, mensaje) {
    ocupado.value = true
    error.value = ''
    campos.value = {}
    try {
      await accion()
      modo.value = null
      item.value = null
      aviso.value = mensaje
    } catch (e) {
      error.value = e.message
      campos.value = e.fields || {}
    } finally {
      ocupado.value = false
    }
  }

  return { modo, item, ocupado, error, campos, aviso, abrir, cerrar, ejecutar }
}
