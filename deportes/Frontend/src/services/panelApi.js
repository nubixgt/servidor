import api from './api'

// Capa entre el panel (claves tipo Pulse en inglés) y el API (columnas de la BD en español).

const POSICION = { POR: 'Portero', DEF: 'Defensa', MED: 'Mediocampo', DEL: 'Delantero' }
const ESTADO = { scheduled: 'programado', live: 'en_juego', finished: 'finalizado' }

// Ejecuta una petición y convierte los errores del API en Error(message) con `fields`.
export async function llamar(peticion) {
  try {
    return (await peticion).data
  } catch (e) {
    const err = new Error(e.response?.data?.message || (e.response ? 'Ocurrió un error inesperado.' : 'No se pudo conectar con el servidor.'))
    err.fields = e.response?.data?.fields || {}
    err.status = e.response?.status
    throw err
  }
}

function formData(campos, fotos = {}) {
  const fd = new FormData()
  Object.entries(campos).forEach(([k, v]) => fd.append(k, v === null || v === undefined ? '' : v))
  Object.entries(fotos).forEach(([campo, f]) => {
    if (f?.file) fd.append(campo, f.file)
    else if (f?.remove) fd.append('eliminar_' + campo, '1')
  })
  return fd
}

const EQUIPO = {
  name: 'nombre', short_name: 'abreviatura', city: 'ciudad', stadium: 'estadio', coach: 'entrenador',
  primary_color: 'color_principal', secondary_color: 'color_secundario', founded_year: 'anio_fundacion',
  representante: 'representante', telefono: 'telefono', dpi: 'dpi',
  sub_representante_nombre: 'sub_representante_nombre', sub_representante_dpi: 'sub_representante_dpi', sub_representante_telefono: 'sub_representante_telefono',
}

export const panelApi = {
  resumen: () => llamar(api.get('/panel/resumen')),
  equipos: () => llamar(api.get('/panel/equipos')),
  jugadores: () => llamar(api.get('/panel/jugadores')),
  partidos: () => llamar(api.get('/panel/partidos')),

  guardarEquipo(d, fotos) {
    const campos = {}
    Object.entries(EQUIPO).forEach(([ui, bd]) => { if (ui in d) campos[bd] = d[ui] })
    const fd = formData(campos, fotos)
    return llamar(d.id ? api.post(`/panel/equipos/${d.id}`, fd) : api.post('/panel/equipos', fd))
  },
  eliminarEquipo: (id) => llamar(api.delete(`/panel/equipos/${id}`)),

  guardarJugador(d, fotos) {
    const campos = {
      nombre: d.full_name, dpi: d.dpi, telefono: d.telefono, posicion: POSICION[d.position] || '', dorsal: d.jersey_number,
      fecha_nacimiento: d.birth_date, nacionalidad: d.nationality, estatura_cm: d.height_cm, peso_kg: d.weight_kg, pie_habil: d.preferred_foot,
    }
    if (d.team_id) campos.equipo_id = d.team_id
    const fd = formData(campos, { foto: fotos?.foto })
    return llamar(d.id ? api.post(`/panel/jugadores/${d.id}`, fd) : api.post('/panel/jugadores', fd))
  },
  eliminarJugador: (id) => llamar(api.delete(`/panel/jugadores/${id}`)),
  bajaJugador: (id, razon) => llamar(api.patch(`/panel/jugadores/${id}/baja`, { razon_baja: razon })),
  altaJugador: (id) => llamar(api.patch(`/panel/jugadores/${id}/alta`)),

  guardarPartido(d) {
    const cuerpo = {
      equipo_local_id: d.home_team_id, equipo_visitante_id: d.away_team_id, fecha: d.kickoff_at,
      jornada: d.round_number, estado: ESTADO[d.status],
    }
    return llamar(d.id ? api.put(`/panel/partidos/${d.id}`, cuerpo) : api.post('/panel/partidos', cuerpo))
  },
  eliminarPartido: (id) => llamar(api.delete(`/panel/partidos/${id}`)),

  eventos: (partidoId) => llamar(api.get(`/panel/partidos/${partidoId}/eventos`)),
  jugadoresDePartido: (partidoId) => llamar(api.get(`/panel/partidos/${partidoId}/jugadores`)),
  guardarEvento(partidoId, d) {
    const cuerpo = { tipo: { goal: 'gol', own_goal: 'autogol', yellow: 'amarilla', red: 'roja' }[d.type], minuto: d.minute, jugador_id: d.player_id, asistente_id: d.assist_player_id }
    return llamar(d.id ? api.put(`/panel/partidos/${partidoId}/eventos/${d.id}`, cuerpo) : api.post(`/panel/partidos/${partidoId}/eventos`, cuerpo))
  },
  eliminarEvento: (partidoId, id) => llamar(api.delete(`/panel/partidos/${partidoId}/eventos/${id}`)),
}
