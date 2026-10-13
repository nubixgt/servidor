export const STATUS_LABEL = { scheduled: 'Programado', live: 'En juego', finished: 'Finalizado' }
export const EVENT_LABEL = { goal: 'Gol', own_goal: 'Autogol', yellow: 'Amarilla', red: 'Roja' }

// Fecha en horario de Guatemala (los kickoff_at se guardan en UTC "YYYY-MM-DD HH:mm:ss").
export const formatFecha = (valor, conHora = false) =>
  new Intl.DateTimeFormat('es-GT', {
    timeZone: 'America/Guatemala',
    day: '2-digit',
    month: 'short',
    ...(conHora ? { hour: '2-digit', minute: '2-digit' } : {}),
  }).format(new Date(valor.replace(' ', 'T') + 'Z'))
