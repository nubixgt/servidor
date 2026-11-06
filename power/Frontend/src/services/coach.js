import api from './api';

/**
 * AI Coach chat. Read-only conversational coach — answers from the athlete's
 * own data, never modifies the plan. Backed by the AiService bridge.
 *
 * `coachId` es el entrenador elegido en Configuración (juan | fernanda | ben |
 * natasha). Viaja como un identificador y NO como texto de instrucciones: el
 * catálogo que decide qué añade cada uno al system prompt vive en Python
 * (PaginaLocal/services/ai/coach_chat.py), así que desde aquí no se puede
 * escribir nada dentro del prompt. Un id desconocido cae al estilo por defecto.
 */
export async function sendCoachMessage({ message, history = [], coachId = null }) {
  const { data } = await api.post('/coach/chat', { message, history, coach_id: coachId });
  return data.data; // { reply, usage }
}
