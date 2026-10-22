// Semana ISO (1-53) de una fecha 'YYYY-MM-DD'. El servidor calcula el mismo dato al guardar.
export const semanaIso = (fecha) => {
    const d = new Date((fecha || new Date().toISOString().slice(0, 10)) + 'T00:00:00');
    if (isNaN(d)) return '—';
    d.setDate(d.getDate() + 4 - (d.getDay() || 7));
    const inicio = new Date(d.getFullYear(), 0, 1);
    return Math.ceil(((d - inicio) / 86400000 + 1) / 7);
};

// Descarga un CSV (separador ';', con BOM para Excel). `filas` es un arreglo de arreglos.
export const descargarCSV = (nombre, filas) => {
    const csv = '﻿' + filas.map((f) => f.map((c) => `"${String(c ?? '').replace(/"/g, '""')}"`).join(';')).join('\r\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    a.download = nombre;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
};
