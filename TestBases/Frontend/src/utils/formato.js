/** "2026-11-20 14:05:09" -> "20/11/2026 14:05" (la hora viene ya en hora de Guatemala). */
export const fechaHora = (valor) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(valor || '');
    return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : '';
};

/** 1234567 -> "1,234,567" */
export const numero = (n) => Number(n ?? 0).toLocaleString('en-US');

/** 3-4 dígitos: "1234 5678" */
export const telefono = (t) => (t && t.length === 8 ? `${t.slice(0, 4)} ${t.slice(4)}` : t || '');

/** CUI en bloques de 4-5-4, como se lee en el DPI */
export const cui = (c) => (c && c.length === 13 ? `${c.slice(0, 4)} ${c.slice(4, 9)} ${c.slice(9)}` : c || '');

export const kb = (bytes) => (bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);
