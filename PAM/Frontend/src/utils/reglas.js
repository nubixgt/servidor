// Reglas automáticas de alerta y calidad (mismas claves que el backend)
export const RULES = [
    { k: 'qa', t: 'Fallecidos que continúan activos en nómina', d: 'Fecha de fallecimiento registrada y estado EN NÓMINA ACTIVA', p: 'c', icon: 'alert' },
    { k: 'qs', t: 'Suspendidos que continúan activos en nómina', d: 'Fecha de suspensión registrada y estado EN NÓMINA ACTIVA', p: 'h', icon: 'pause' },
    { k: 'old', t: 'Solicitudes en trámite con más de 365 días', d: 'Sin resolución final un año después de la solicitud', p: 'h', icon: 'clock' },
    { k: 'nov', t: 'Solicitudes en trámite sin visita', d: 'Expediente en trámite sin visita positiva ni negativa', p: 'h', icon: 'pin' },
    { k: 'rpn', t: 'Resolución positiva sin ingreso a nómina', d: 'La resolución más reciente es positiva y nunca ingresó a nómina', p: 'm', icon: 'wallet' },
    { k: 'vpr', t: 'Visita positiva sin resolución', d: 'Visita positiva registrada y ninguna resolución emitida', p: 'm', icon: 'stamp' },
    { k: 'qu', t: 'DPI registrado en más de un expediente', d: 'El mismo CUI aparece en 2 a 4 registros', p: 'm', icon: 'copy' },
    { k: 'qx', t: 'Número de expediente duplicado', d: 'El mismo número de expediente en registros distintos', p: 'm', icon: 'file' },
    { k: 'qf', t: 'Fechas inconsistentes', d: 'Visita, resolución, nómina o fallecimiento antes de la solicitud, o fechas futuras', p: 'm', icon: 'clock' },
    { k: 'qd', t: 'Documento sin CUI válido', d: 'Cédula antigua (número corto) o CUI con dígito verificador incorrecto', p: 'l', icon: 'shield' },
    { k: 'qe', t: 'Edad fuera de rango', d: 'Más de 110 años, menos de 60 al solicitar o sin fecha de nacimiento', p: 'l', icon: 'users' },
    { k: 'qm', t: 'Sin teléfono de contacto', d: 'Dificulta programar visitas y notificar resoluciones', p: 'l', icon: 'info' },
];
export const PRN = { c: 'Crítica', h: 'Alta', m: 'Media', l: 'Baja' };
export const PRC = { c: 'var(--crit)', h: 'var(--warnf)', m: 'var(--blue5)', l: 'var(--off)' };
