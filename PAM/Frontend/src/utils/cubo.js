// Cubo del tablero: se descarga una vez y todo el filtrado cruzado se calcula en el navegador.
import { ANIO, MES, DEPS, AB, EST_DEF, RG_DEF } from './formato';

export const acc = () => ({ c: 0, p: 0, nom: 0, tram: 0, vp: 0, vn: 0, susp: 0, fall: 0 });

/** Convierte las columnas del API en arreglos tipados y calcula los índices territoriales. */
export function prepCube(j) {
    const L = j.cols.c.length;
    const C = { len: L };
    for (const k of j.dims) C[k] = (k === 'm' ? Uint16Array : Uint8Array).from(j.cols[k]);
    for (const k of j.meas) C[k] = Uint32Array.from(j.cols[k]);
    const MU = j.MU;
    const RG = j.RG || RG_DEF;
    const MDEP = Uint8Array.from(MU.map((m) => parseInt(m.slice(0, 2), 10) - 1));
    const MUNI_BY_DEP = DEPS.map(() => []);
    MU.forEach((m, i) => MUNI_BY_DEP[MDEP[i]].push(i));
    let TOTAL_NAC = 0;
    for (let i = 0; i < L; i++) TOTAL_NAC += C.c[i];
    return { C, MU, MN: j.MN, YS: j.YS, MDEP, MUNI_BY_DEP, TOTAL_NAC, RG, RSIN: RG.length - 1, EST: j.ES || EST_DEF };
}

/** ¿La fila i del cubo pasa los filtros F? `ex` excluye el filtro de esa dimensión (para sus propios conteos). */
export function ok(cube, F, i, ex) {
    const { C, MDEP } = cube;
    if (ex !== 'geo') {
        if (F.dep != null && MDEP[C.m[i]] !== F.dep) return false;
        if (F.mun != null && C.m[i] !== F.mun) return false;
    }
    if (F.y != null && C.y[i] !== F.y) return false;
    if (F.e != null && ex !== 'e' && C.e[i] !== F.e) return false;
    if (F.r != null && ex !== 'r' && C.r[i] !== F.r) return false;
    if (F.n != null && C.n[i] !== F.n) return false;
    if (F.v != null && C.v[i] !== F.v) return false;
    if (F.s != null && C.s[i] !== F.s) return false;
    if (F.f != null && C.f[i] !== F.f) return false;
    if (F.a != null && ex !== 'a' && C.a[i] !== F.a) return false;
    if (F.am != null && ex !== 'a' && C.a[i] < F.am) return false;
    if (F.t != null && ex !== 't' && C.t[i] !== F.t) return false;
    return true;
}

function add(C, a, i) {
    const c = C.c[i];
    a.c += c;
    a.p += C.p[i];
    if (C.n[i] === 1) a.nom += c;
    const e = C.e[i];
    if (e === 1) a.tram += c;
    else if (e === 4) a.susp += c;
    const v = C.v[i];
    if (v === 1) a.vp += c;
    else if (v === 2) a.vn += c;
    if (C.f[i]) a.fall += c;
}

/** Agregados que alimentan todas las tarjetas del tablero para los filtros F. */
export function compute(cube, F) {
    const { C, MDEP, RG, RSIN, EST } = cube;
    const L = C.len;
    const T = acc();
    const byE = new Array(EST.length).fill(0);
    const byR = new Array(RG.length).fill(0);
    const byA = AB.map(() => ({ c: 0, nom: 0 }));
    const byT = new Array(7).fill(0);
    const byDep = DEPS.map(acc);
    const byMun = new Map();
    const geoDep = DEPS.map(acc);
    const geoMun = new Map();
    const fl = { c: 0, vis: 0, vp: 0, rp: 0, res: 0, ing: 0, act: 0 };
    const wait = { vis: 0, res: 0, nom: 0 };
    const al = { qa: 0, qs: 0, old: 0, nov: 0, rpn: 0, vpr: 0, qu: 0, qx: 0, qd: 0, qf: 0, qe: 0, qm: 0, susp: 0 };

    for (let i = 0; i < L; i++) {
        const c = C.c[i];
        if (ok(cube, F, i, 'geo')) {
            add(C, geoDep[MDEP[C.m[i]]], i);
            let g = geoMun.get(C.m[i]);
            if (!g) { g = acc(); geoMun.set(C.m[i], g); }
            add(C, g, i);
        }
        if (ok(cube, F, i, 'e')) byE[C.e[i]] += c;
        if (ok(cube, F, i, 'r')) byR[C.r[i]] += c;
        if (ok(cube, F, i, 'a')) {
            const b = byA[C.a[i]];
            b.c += c;
            if (C.n[i] === 1) b.nom += c;
        }
        if (ok(cube, F, i, 't') && C.t[i]) byT[C.t[i]] += c;
        if (!ok(cube, F, i, '')) continue;

        add(C, T, i);
        add(C, byDep[MDEP[C.m[i]]], i);
        let g = byMun.get(C.m[i]);
        if (!g) { g = acc(); byMun.set(C.m[i], g); }
        add(C, g, i);

        const v = C.v[i], r = C.r[i], n = C.n[i], e = C.e[i];
        fl.c += c;
        if (v) fl.vis += c;
        if (r !== RSIN) fl.res += c;
        if (e === 1) {
            if (v === 0) wait.vis += c;
            else if (r === RSIN) wait.res += c;
            if (r === 0 && n === 0) wait.nom += c;
        }
        if (v === 1) fl.vp += c;
        if (r === 0) fl.rp += c;
        if (n) fl.ing += c;
        if (n === 1) fl.act += c;
        al.qa += C.qa[i]; al.qs += C.qs[i]; al.qu += C.qu[i]; al.qx += C.qx[i];
        al.qd += C.qd[i]; al.qf += C.qf[i]; al.qe += C.qe[i]; al.qm += C.qm[i];
        if (C.t[i] === 6) al.old += c;
        if (e === 1 && v === 0) al.nov += c;
        if (r === 0 && n === 0) al.rpn += c;
        if (v === 1 && r === RSIN) al.vpr += c;
        if (e === 4) al.susp += c;
    }
    return { wait, T, byE, byR, byA, byT, byDep, byMun, geoDep, geoMun, fl, al };
}

/** Cuenta registros/personas para un conjunto de filtros sin tocar los filtros activos. */
export function countWith(cube, ff) {
    let c = 0, p = 0;
    for (let i = 0; i < cube.C.len; i++) {
        if (ok(cube, ff, i, '')) { c += cube.C.c[i]; p += cube.C.p[i]; }
    }
    return { c, p };
}

// ---------------------------------------------------------------- series
export function seriesYear(S, F, MDEP, keys) {
    const out = {};
    const ny = ANIO - S.Y0 + 1;
    for (const k of keys) {
        const a = new Array(ny).fill(0);
        for (const [m, e, y, v] of S.yr[k]) {
            if (F.dep != null && MDEP[m] !== F.dep) continue;
            if (F.mun != null && m !== F.mun) continue;
            if (F.e != null && e !== F.e) continue;
            a[y] += v;
        }
        out[k] = a;
    }
    return out;
}

export function seriesMonth(S, F, keys) {
    const out = {};
    const nm = (ANIO - 2018) * 12 + MES + 1;
    for (const k of keys) {
        const a = new Array(nm).fill(0);
        for (const [d, e, mi, v] of S.mo[k]) {
            if (F.dep != null && d !== F.dep) continue;
            if (F.e != null && e !== F.e) continue;
            a[mi] += v;
        }
        out[k] = a;
    }
    return out;
}

/** Acumulado de los primeros `months` meses del año `year` (para comparar contra el año anterior). */
export function ytd(S, F, k, year, months) {
    const base = (year - 2018) * 12;
    let s = 0;
    for (const [d, e, mi, v] of S.mo[k]) {
        if (F.dep != null && d !== F.dep) continue;
        if (F.e != null && e !== F.e) continue;
        if (mi >= base && mi < base + months) s += v;
    }
    return s;
}

// ---------------------------------------------------------------- mapa
export function bboxOf(p) {
    let x0 = 1e9, y0 = 1e9, x1 = -1e9, y1 = -1e9;
    const nums = p.match(/-?\d+\.?\d*/g).map(Number);
    for (let i = 0; i < nums.length; i += 2) {
        const x = nums[i], y = nums[i + 1];
        if (x < x0) x0 = x;
        if (x > x1) x1 = x;
        if (y < y0) y0 = y;
        if (y > y1) y1 = y;
    }
    return [x0, y0, x1, y1];
}

export function quantBreaks(vals) {
    const s = [...vals].sort((a, b) => a - b);
    return [0.2, 0.4, 0.6, 0.8].map((q) => s[Math.floor(q * (s.length - 1))]);
}
