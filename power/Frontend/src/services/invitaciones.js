import api from './api';

/** Su código, a quién invitó y las semanas gratis que lleva (Beta 3.0, Database/033). */
export async function misInvitaciones() {
  const { data } = await api.get('/referrals/mine');
  return data.data; // { disponible, codigo, invitados, semanas_ganadas, semanas_por_invitado, ... }
}

/** Escribir el código de quien le invitó, hasta que conecta su Garmin (2026-10-21). Devuelve `mio`. */
export async function escribirCodigo(codigo) {
  const { data } = await api.post('/referrals/claim', { code: codigo });
  return data.data;
}
