import api from './api';

/**
 * Lo que esta cuenta ha costado en APIs de IA.
 *
 * Es costo de PROVEEDOR — lo que la app le pagó a Anthropic, OpenAI y Google —
 * no una factura. La fuente es un libro de sólo-añadir que vive fuera del árbol
 * de datos del atleta: **borrar el plan no borra lo que ya costó**.
 */
export async function getUsage() {
  const { data } = await api.get('/usage');
  // { total_cost_usd, total_tokens, runs, by_kind, by_month, stale, source }
  return data.data;
}
