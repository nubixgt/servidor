import { reactive } from 'vue'
import { panelApi } from '../services/panelApi'

// Datos del panel (admin y encargado) leídos del API. El servidor ya limita lo que ve cada rol.
const state = reactive({ teams: [], players: [], matches: [], resumen: null, loaded: false, loading: false, error: '' })

async function load() {
  state.loading = true
  state.error = ''
  try {
    const [teams, players, matches, resumen] = await Promise.all([panelApi.equipos(), panelApi.jugadores(), panelApi.partidos(), panelApi.resumen()])
    Object.assign(state, { teams, players, matches, resumen, loaded: true })
  } catch (e) {
    state.error = e.message
  } finally {
    state.loading = false
  }
}

function reset() {
  Object.assign(state, { teams: [], players: [], matches: [], resumen: null, loaded: false, error: '' })
}

export function usePanel() {
  return { state, load, reset }
}
