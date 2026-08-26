const DIAS_VALIDOS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

// Acepta tanto la inicial (L,M,X,J,V,S,D) como el nombre completo del día,
// sin depender de tildes (quita acentos antes de comparar).
const NOMBRE_A_LETRA = {
    lunes: 'L',
    martes: 'M',
    miercoles: 'X',
    jueves: 'J',
    viernes: 'V',
    sabado: 'S',
    domingo: 'D',
};

function sinTildes(texto) {
    return texto.normalize('NFD').replace(/[̀-ͯ]/g, '');
}

/** @returns {number|null} */
function parsearMinutos(texto) {
    const n = parseInt(texto.trim(), 10);
    if (Number.isNaN(n) || n < 5 || n > 180) return null;
    return n;
}

/** @returns {string|null} "HH:MM" en 24 horas, o null si no se pudo interpretar */
function parsearHora(texto) {
    const m = texto
        .trim()
        .toLowerCase()
        .match(/^(\d{1,2})(?::(\d{2}))?\s*(a\.?m\.?|p\.?m\.?)?$/);
    if (!m) return null;

    let horas = parseInt(m[1], 10);
    const minutos = m[2] ? parseInt(m[2], 10) : 0;
    const sufijo = m[3]?.replace(/\./g, '');

    if (horas > 23 || minutos > 59) return null;

    if (sufijo === 'am') {
        if (horas < 1 || horas > 12) return null;
        horas = horas === 12 ? 0 : horas;
    } else if (sufijo === 'pm') {
        if (horas < 1 || horas > 12) return null;
        horas = horas === 12 ? 12 : horas + 12;
    }
    // Sin am/pm: se toma tal cual en formato 24h (ej. "19:00" o "7:00").

    return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}`;
}

/** @returns {string|null} CSV de letras de día (ej. "L,M,X,J,V"), o null si no se pudo interpretar ninguno */
function parsearDias(texto) {
    const limpio = sinTildes(texto.trim().toLowerCase());
    if (limpio === 'todos' || limpio === 'todos los dias') {
        return DIAS_VALIDOS.join(',');
    }

    const tokens = limpio.split(/[,\s]+/).filter(Boolean);
    const letras = [];
    for (const token of tokens) {
        const letra = token.length === 1 ? token.toUpperCase() : NOMBRE_A_LETRA[token];
        if (!letra || !DIAS_VALIDOS.includes(letra)) return null;
        if (!letras.includes(letra)) letras.push(letra);
    }

    return letras.length ? letras.join(',') : null;
}

module.exports = { parsearMinutos, parsearHora, parsearDias };
