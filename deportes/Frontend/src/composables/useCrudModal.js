import { ref } from 'vue'
import { alertaError, confirmarEliminar, toastExito } from '../utils/alertas'

// Estado común de las pantallas del panel: modal (formulario / baja / eventos) y avisos con SweetAlert.
// Los errores de validación por campo (`campos`) se siguen mostrando bajo cada input del formulario.
export function useCrudModal() {
  const modo = ref(null) // null | 'form' | 'baja' | 'events'
  const item = ref(null)
  const ocupado = ref(false)
  const campos = ref({})

  function abrir(nuevoModo, registro = null) {
    item.value = registro ? { ...registro } : null
    modo.value = nuevoModo
    campos.value = {}
  }

  function cerrar() {
    if (ocupado.value) return
    modo.value = null
    item.value = null
  }

  // Ejecuta una acción del API: éxito -> aviso y se cierra el modal; error -> alerta con el motivo.
  async function ejecutar(accion, mensaje, tituloError = 'No se pudo guardar') {
    ocupado.value = true
    campos.value = {}
    try {
      await accion()
      modo.value = null
      item.value = null
      toastExito(mensaje)
    } catch (e) {
      campos.value = e.fields || {}
      await alertaError(tituloError, e.message)
    } finally {
      ocupado.value = false
    }
  }

  // Pregunta con SweetAlert antes de eliminar y avisa el resultado.
  async function eliminar({ title, text, accion, mensaje }) {
    const { isConfirmed } = await confirmarEliminar({ title, text })
    if (!isConfirmed) return
    ocupado.value = true
    try {
      await accion()
      toastExito(mensaje)
    } catch (e) {
      await alertaError('No se pudo eliminar', e.message)
    } finally {
      ocupado.value = false
    }
  }

  return { modo, item, ocupado, campos, abrir, cerrar, ejecutar, eliminar }
}
