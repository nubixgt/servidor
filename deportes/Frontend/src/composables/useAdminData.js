import { panelApi } from '../services/panelApi'
import { usePanel } from './usePanel'

// Acciones del panel: llaman al API y recargan los datos. Los errores llegan como Error(message) con `fields`.
export function useAdminData() {
  const { load } = usePanel()
  const seguido = (accion) => async (...args) => {
    const r = await accion(...args)
    await load()
    return r
  }

  return {
    guardarEquipo: seguido(panelApi.guardarEquipo),
    eliminarEquipo: seguido(panelApi.eliminarEquipo),
    guardarJugador: seguido(panelApi.guardarJugador),
    eliminarJugador: seguido(panelApi.eliminarJugador),
    bajaJugador: seguido(panelApi.bajaJugador),
    altaJugador: seguido(panelApi.altaJugador),
    guardarPartido: seguido(panelApi.guardarPartido),
    eliminarPartido: seguido(panelApi.eliminarPartido),
    guardarEvento: seguido(panelApi.guardarEvento),
    eliminarEvento: seguido(panelApi.eliminarEvento),
  }
}
