// Resuelve la ruta relativa que guarda el backend (p. ej. "uploads/equipos/3/logo.png")
// a una URL servida por Apache bajo /BasquetSanarate/Backend/.
// En desarrollo se puede apuntar a otro host con VITE_ASSET_BASE.
const base =
    import.meta.env.VITE_ASSET_BASE ||
    `${import.meta.env.BASE_URL}Backend/`;

export function assetUrl(ruta) {
    if (!ruta) return null;
    if (/^https?:\/\//i.test(ruta)) return ruta;
    return base + String(ruta).replace(/^\/+/, '');
}
