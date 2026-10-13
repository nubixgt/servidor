import { raw, guardarRaw, restablecerRaw, usePulse } from './usePulse'

// CRUD de la administración sobre los datos locales. Mismas reglas de negocio que el demo.
// Cada función lanza Error(mensaje) con `fields` opcional para marcar campos del formulario.
const falla = (mensaje, fields = {}) => {
  const e = new Error(mensaje)
  e.fields = fields
  throw e
}
const mismo = (a, b) => +a === +b
const siguienteId = (lista) => Math.max(0, ...lista.map((x) => +x.id)) + 1

function persistir() {
  try {
    guardarRaw()
  } catch {
    falla('No hay espacio local. Prueba con una imagen más pequeña.')
  }
}

export function useAdminData() {
  const { load } = usePulse()

  // Aplica un cambio y revierte si no cabe en localStorage.
  async function transaccion(cambiar) {
    const respaldo = JSON.stringify(raw)
    try {
      cambiar()
      persistir()
    } catch (e) {
      Object.assign(raw, JSON.parse(respaldo))
      throw e
    }
    await load()
  }

  function guardarEquipo(datos) {
    return transaccion(() => {
      const id = datos.id
      if (raw.teams.some((t) => !mismo(t.id, id) && t.name.toLowerCase() === datos.name.toLowerCase()))
        falla('Ya existe un equipo con ese nombre.', { name: 'Ya existe un equipo con ese nombre.' })
      upsert(raw.teams, datos)
    })
  }

  function eliminarEquipo(id) {
    return transaccion(() => {
      if (raw.players.some((j) => mismo(j.team_id, id)) || raw.matches.some((p) => mismo(p.home_team_id, id) || mismo(p.away_team_id, id)))
        falla('El equipo tiene jugadores o partidos relacionados.')
      raw.teams = raw.teams.filter((t) => !mismo(t.id, id))
    })
  }

  function guardarJugador(datos) {
    return transaccion(() => {
      const actual = raw.players.find((j) => mismo(j.id, datos.id))
      if (raw.players.some((j) => !mismo(j.id, datos.id) && mismo(j.team_id, datos.team_id) && mismo(j.jersey_number, datos.jersey_number)))
        falla('Este dorsal ya existe en el equipo.', { jersey_number: 'Este dorsal ya existe en el equipo.' })
      if (actual && !mismo(actual.team_id, datos.team_id) && raw.events.some((e) => mismo(e.player_id, datos.id) || mismo(e.assist_player_id, datos.id)))
        falla('No se puede transferir un jugador con eventos.', { team_id: 'Tiene eventos registrados.' })
      upsert(raw.players, datos)
    })
  }

  function eliminarJugador(id) {
    return transaccion(() => {
      if (raw.events.some((e) => mismo(e.player_id, id) || mismo(e.assist_player_id, id)))
        falla('El jugador tiene eventos relacionados.')
      raw.players = raw.players.filter((j) => !mismo(j.id, id))
    })
  }

  function guardarPartido(datos) {
    return transaccion(() => {
      const actual = raw.matches.find((p) => mismo(p.id, datos.id))
      if (mismo(datos.home_team_id, datos.away_team_id)) falla('Local y visitante deben ser distintos.')
      const tieneEventos = actual && raw.events.some((e) => mismo(e.match_id, actual.id))
      if (tieneEventos && (datos.status === 'scheduled' || !mismo(datos.home_team_id, actual.home_team_id) || !mismo(datos.away_team_id, actual.away_team_id)))
        falla('El partido tiene eventos: no se pueden cambiar equipos ni volver a Programado.')
      upsert(raw.matches, datos)
    })
  }

  function eliminarPartido(id) {
    return transaccion(() => {
      raw.matches = raw.matches.filter((p) => !mismo(p.id, id))
      raw.events = raw.events.filter((e) => !mismo(e.match_id, id))
    })
  }

  function guardarEvento(matchId, datos) {
    return transaccion(() => {
      const partido = raw.matches.find((p) => mismo(p.id, matchId))
      const jugador = raw.players.find((j) => mismo(j.id, datos.player_id))
      if (!partido || partido.status === 'scheduled') falla('Cambia el partido a En juego antes de registrar eventos.')
      if (!jugador || ![+partido.home_team_id, +partido.away_team_id].includes(+jugador.team_id)) falla('Jugador no válido para este partido.')
      if (datos.assist_player_id) {
        const asistente = raw.players.find((j) => mismo(j.id, datos.assist_player_id))
        if (datos.type !== 'goal' || !asistente || !mismo(asistente.team_id, jugador.team_id) || mismo(asistente.id, jugador.id)) falla('Asistencia inválida.')
      }
      upsert(raw.events, { ...datos, match_id: +matchId, team_id: +jugador.team_id })
    })
  }

  function eliminarEvento(id) {
    return transaccion(() => {
      raw.events = raw.events.filter((e) => !mismo(e.id, id))
    })
  }

  async function restablecer() {
    restablecerRaw()
    await load()
  }

  return { guardarEquipo, eliminarEquipo, guardarJugador, eliminarJugador, guardarPartido, eliminarPartido, guardarEvento, eliminarEvento, restablecer }
}

function upsert(lista, datos) {
  const i = lista.findIndex((x) => mismo(x.id, datos.id))
  if (i >= 0 && datos.id) lista[i] = { ...datos }
  else lista.push({ ...datos, id: siguienteId(lista) })
}
