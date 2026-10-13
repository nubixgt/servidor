import { reactive } from 'vue'
import { league, teams, players, matches, events } from '../data/pulseMock'

// Fuente de datos (etapas 1-2): datos de ejemplo + cambios del admin guardados en localStorage.
// Cuando exista el backend, solo cambia esta sección; las vistas leen siempre `state`.
const CLAVE = 'pulse-datos-v1'

const copiar = (o) => JSON.parse(JSON.stringify(o))
const originales = () => copiar({ league, teams, players, matches, events })

function leerGuardado() {
  try {
    return JSON.parse(localStorage.getItem(CLAVE)) || originales()
  } catch {
    return originales()
  }
}

// Datos "crudos" editables (los modifica useAdminData).
export const raw = reactive(leerGuardado())

export function guardarRaw() {
  localStorage.setItem(CLAVE, JSON.stringify(raw))
}

export function restablecerRaw() {
  Object.assign(raw, originales())
  guardarRaw()
}

function cargarFuente() {
  return raw
}

const edad = (fechaNacimiento, hoy = new Date()) => {
  const [y, m, d] = fechaNacimiento.split('-').map(Number)
  const mes = hoy.getUTCMonth() + 1
  const cumplio = mes > m || (mes === m && hoy.getUTCDate() >= d)
  return hoy.getUTCFullYear() - y - (cumplio ? 0 : 1)
}

// Construye partidos con marcador, jugadores con estadísticas y tabla de posiciones.
export function construirOverview(raw) {
  const equipo = (id) => raw.teams.find((t) => +t.id === +id)
  const esGol = (e) => e.type === 'goal' || e.type === 'own_goal'

  const partidos = raw.matches
    .map((p) => {
      const local = equipo(p.home_team_id)
      const visita = equipo(p.away_team_id)
      let home_score = 0
      let away_score = 0
      for (const e of raw.events.filter((x) => +x.match_id === +p.id && esGol(x))) {
        const anotaLocal = (+e.team_id === +p.home_team_id) !== (e.type === 'own_goal')
        if (anotaLocal) home_score++
        else away_score++
      }
      return {
        ...p,
        home_score,
        away_score,
        home_name: local.name,
        home_short: local.short_name,
        home_color: local.primary_color,
        home_logo: local.logo_url,
        away_name: visita.name,
        away_short: visita.short_name,
        away_color: visita.primary_color,
        away_logo: visita.logo_url,
        stadium: local.stadium,
      }
    })
    .sort((a, b) => b.kickoff_at.localeCompare(a.kickoff_at) || b.id - a.id)

  const finalizados = new Set(partidos.filter((p) => p.status === 'finished').map((p) => +p.id))
  const eventosValidos = raw.events.filter((e) => finalizados.has(+e.match_id))

  const jugadores = raw.players
    .map((j) => {
      const eq = equipo(j.team_id)
      return {
        ...j,
        team_name: eq.name,
        short_name: eq.short_name,
        primary_color: eq.primary_color,
        age: edad(j.birth_date),
        goals: eventosValidos.filter((e) => +e.player_id === +j.id && e.type === 'goal').length,
        assists: eventosValidos.filter((e) => +e.assist_player_id === +j.id && e.type === 'goal').length,
        yellow_cards: eventosValidos.filter((e) => +e.player_id === +j.id && e.type === 'yellow').length,
        red_cards: eventosValidos.filter((e) => +e.player_id === +j.id && e.type === 'red').length,
      }
    })
    .sort((a, b) => b.goals - a.goals || b.assists - a.assists || a.full_name.localeCompare(b.full_name))

  const posiciones = raw.teams
    .map((t) => {
      const fila = {
        team_id: t.id, name: t.name, short_name: t.short_name, primary_color: t.primary_color, logo_url: t.logo_url,
        played: 0, won: 0, drawn: 0, lost: 0, goals_for: 0, goals_against: 0, goal_difference: 0, points: 0,
      }
      partidos
        .filter((p) => p.status === 'finished' && [+p.home_team_id, +p.away_team_id].includes(+t.id))
        .forEach((p) => {
          const esLocal = +p.home_team_id === +t.id
          const gf = esLocal ? p.home_score : p.away_score
          const gc = esLocal ? p.away_score : p.home_score
          fila.played++
          fila.goals_for += gf
          fila.goals_against += gc
          if (gf > gc) { fila.won++; fila.points += 3 }
          else if (gf === gc) { fila.drawn++; fila.points++ }
          else fila.lost++
        })
      fila.goal_difference = fila.goals_for - fila.goals_against
      return fila
    })
    .sort((a, b) => b.points - a.points || b.goal_difference - a.goal_difference || b.goals_for - a.goals_for || a.name.localeCompare(b.name))

  return { league: raw.league, teams: raw.teams, players: jugadores, matches: partidos, standings: posiciones }
}

// Eventos de un partido con nombres resueltos, ordenados por minuto.
export function eventosDePartido(matchId) {
  const raw = cargarFuente()
  const nombre = (id) => raw.players.find((j) => +j.id === +id)?.full_name
  return raw.events
    .filter((e) => +e.match_id === +matchId)
    .map((e) => ({
      ...e,
      player_name: nombre(e.player_id),
      assist_name: nombre(e.assist_player_id),
      team_name: raw.teams.find((t) => +t.id === +e.team_id)?.name,
    }))
    .sort((a, b) => a.minute - b.minute)
}

const state = reactive({
  league: { name: 'LIGA PULSE', season: '' },
  teams: [], players: [], matches: [], standings: [],
  loading: false, error: '', loaded: false,
})

async function load() {
  state.loading = true
  state.error = ''
  try {
    Object.assign(state, construirOverview(cargarFuente()), { loaded: true })
  } catch (e) {
    state.error = e.message
  } finally {
    state.loading = false
  }
}

export function usePulse() {
  return { state, load, eventosDePartido }
}

