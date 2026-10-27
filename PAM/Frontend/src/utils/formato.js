// Formato y constantes de presentación (idénticos al diseño original)
const nf = new Intl.NumberFormat('es-GT');

export const fmt = (n) => nf.format(Math.round(n || 0));
export const pc = (a, b, d = 1) => (b ? ((a / b) * 100).toFixed(d) : '0.0');

/** AAAA-MM-DD → DD/MM/AAAA */
export const fd = (s) => (s ? s.slice(0, 10).split('-').reverse().join('/') : '—');
export const fdl = (s) => (s ? new Date(s.slice(0, 10) + 'T12:00:00').toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' }) : '—');
export const fts = (s) => (s ? new Date(String(s).replace(' ', 'T')).toLocaleString('es-GT', { dateStyle: 'short', timeStyle: 'short' }) : '—');
export const hoyISO = () => new Date(Date.now() - new Date().getTimezoneOffset() * 6e4).toISOString().slice(0, 10);

export const norm = (s) => String(s).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();

export const HOY = new Date();
export const ANIO = HOY.getFullYear();
export const MES = HOY.getMonth(); // 0-11
export const MESC = Math.max(1, MES); // meses completos del año en curso
export const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
export const PERIODO = `ene–${MESES[MESC - 1]}`;

export const DEPS = ['Guatemala', 'El Progreso', 'Sacatepéquez', 'Chimaltenango', 'Escuintla', 'Santa Rosa', 'Sololá', 'Totonicapán', 'Quetzaltenango', 'Suchitepéquez', 'Retalhuleu', 'San Marcos', 'Huehuetenango', 'Quiché', 'Baja Verapaz', 'Alta Verapaz', 'Petén', 'Izabal', 'Zacapa', 'Chiquimula', 'Jalapa', 'Jutiapa'];
export const EST_DEF = ['En nómina activa', 'En trámite', 'Negativo', 'Bloqueado', 'Suspendido', 'No localizado', 'Anulado', 'Reactivado', 'Fallecido'];
export const EST_ICON = ['check', 'clock', 'x', 'lock', 'pause', 'pin', 'slash', 'refresh', 'minus'];
export const AB = ['65–69', '70–74', '75–79', '80–84', '85–89', '90+'];
export const RG_DEF = ['Positiva', 'Negativa', 'Suspensión por fallecimiento (RENAP)', 'Suspensión administrativa', 'Reactivación / reanudación', 'No identifica', 'Improcedente / desistimiento', 'Modificación de datos', 'Sin resolución'];
export const NOM = ['Nunca ingresó', 'Activo en nómina', 'Egresado de nómina'];
export const VIS = ['Sin visita', 'Visita positiva', 'Visita negativa'];
export const ANT = ['', '0–30 días', '31–60 días', '61–90 días', '91–180 días', '181–365 días', 'Más de 365 días'];

export const METRICS = [['c', 'Registros'], ['p', 'Personas'], ['nom', 'En nómina'], ['tram', 'Pendientes (en trámite)'], ['susp', 'Suspendidos'], ['fall', 'Fallecidos'], ['vp', 'Visitas positivas'], ['vn', 'Visitas negativas']];
export const SER = [['sol', 'Solicitudes recibidas'], ['vpos', 'Visitas positivas'], ['vneg', 'Visitas negativas'], ['res', 'Resoluciones emitidas'], ['ing', 'Ingresos a nómina'], ['sal', 'Salidas de nómina'], ['susp', 'Suspensiones'], ['fall', 'Fallecimientos registrados']];

export const stc = (i) => `var(--st${i})`;
