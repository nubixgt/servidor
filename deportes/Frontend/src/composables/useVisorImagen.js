import { reactive } from 'vue'
import { imgUrl } from '../services/api'

// Estado compartido del visor de fotos (un solo <VisorImagen> montado en cada layout del panel).
const visor = reactive({ abierta: false, url: '', titulo: '' })

export function useVisorImagen() {
  const verFoto = (ruta, titulo = '') => {
    if (!ruta) return
    Object.assign(visor, { abierta: true, url: imgUrl(ruta), titulo })
  }
  const cerrarVisor = () => (visor.abierta = false)
  return { visor, verFoto, cerrarVisor }
}
