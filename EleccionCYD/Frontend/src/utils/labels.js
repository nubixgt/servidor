export const STATUS_LABELS = {
  READY: 'LISTO',
  WALKING: 'EN PASARELA',
  JUDGED: 'CALIFICADO',
};

export function statusLabel(status) {
  return STATUS_LABELS[status] || status;
}

export const FILTER_LABELS = {
  ALL: 'TODOS',
  ...STATUS_LABELS,
};

export const SORT_LABELS = {
  name: 'nombre',
  status: 'estado',
};
