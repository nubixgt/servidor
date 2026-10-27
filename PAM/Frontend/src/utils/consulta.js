// Consulta en lenguaje natural: reglas locales que traducen una frase a filtros del tablero
import { AB, DEPS, norm } from './formato';

const DEP_ALIAS = { xela: 8, quetzaltenango: 8, peten: 16, quiche: 13, 'el quiche': 13, guate: 0 };

export const EJEMPLOS = [
    'Beneficiarios en nómina mayores de 80 años en Alta Verapaz',
    'Expedientes en trámite con más de un año en Huehuetenango',
    'Fallecidos que siguen en nómina',
    'Solicitudes de 2025 sin visita en Quiché',
    'Suspendidos en el municipio de Cobán',
    'Visitas negativas en Petén',
];

/** @returns {{f: object, tags: string[]}} filtros reconocidos y su descripción */
export function parseQ(text, cube) {
    const { MN, MDEP, YS } = cube;
    let t = ' ' + norm(text).replace(/[¿?¡!.,;:()]/g, ' ').replace(/\s+/g, ' ') + ' ';
    const f = {};
    const tags = [];
    const has = (re) => re.test(t);
    const cut = (s) => { t = t.replace(' ' + s + ' ', ' '); };

    // municipio explícito
    const MUNS = MN.map((n, i) => [norm(n), i]).sort((a, b) => b[0].length - a[0].length);
    for (const [nm, i] of MUNS) {
        if (t.includes(' municipio de ' + nm + ' ') || t.includes(' municipio ' + nm + ' ')) {
            f.mun = i; f.dep = MDEP[i];
            cut('municipio de ' + nm); cut('municipio ' + nm);
            break;
        }
    }
    if (f.mun == null) {
        const DN = DEPS.map((d, i) => [norm(d), i]).concat(Object.entries(DEP_ALIAS)).sort((a, b) => b[0].length - a[0].length);
        for (const [dn, i] of DN) {
            if (t.includes(' ' + dn + ' ')) { f.dep = i; cut(dn); break; }
        }
        for (const [nm, i] of MUNS) {
            if (nm.length > 3 && t.includes(' ' + nm + ' ') && (f.dep == null || MDEP[i] === f.dep)) { f.mun = i; f.dep = MDEP[i]; break; }
        }
    }
    if (f.dep != null) tags.push(f.mun != null ? `${MN[f.mun]}, ${DEPS[f.dep]}` : DEPS[f.dep]);

    if (has(/ visitas? positivas? /)) { f.v = 1; tags.push('Visita positiva'); }
    else if (has(/ visitas? negativas? /)) { f.v = 2; tags.push('Visita negativa'); }
    else if (has(/ sin visitas? /)) { f.v = 0; tags.push('Sin visita'); }

    if (has(/ resolucion(es)? positivas? /)) { f.r = 0; tags.push('Resolución positiva'); }
    else if (has(/ sin resolucion /)) { f.r = 8; tags.push('Sin resolución'); }

    if (has(/ (egresad|salieron|dados de baja|salida)/)) { f.n = 2; tags.push('Egresados de nómina'); }
    else if (has(/ nunca ingres/)) { f.n = 0; tags.push('Nunca ingresó a nómina'); }
    else if (has(/ (nomina|activos?|reciben|cobran|pagados?) /)) { f.n = 1; tags.push('En nómina activa'); }

    if (has(/ (fallecid|murieron|difunt|muertos?)/)) { f.f = 1; tags.push('Con fallecimiento'); }

    if (has(/ (tramite|pendientes?|en proceso) /)) { f.e = 1; tags.push('En trámite'); }
    else if (has(/ suspendid/)) { f.e = 4; tags.push('Suspendidos'); }
    else if (has(/ bloquead/)) { f.e = 3; tags.push('Bloqueados'); }
    else if (has(/ no localizad/)) { f.e = 5; tags.push('No localizados'); }
    else if (has(/ anulad/)) { f.e = 6; tags.push('Anulados'); }
    else if (has(/ (expedientes?|resolucion(es)?|casos?) negativ/)) { f.e = 2; tags.push('Negativos'); }

    const m = t.match(/ (mayores|mas) de (\d{2,3})/) || t.match(/ (\d{2,3}) anos? (o|y) mas /) || t.match(/ (de )?(\d{2,3}) (anos? )?(para arriba|en adelante)/);
    if (m) {
        const age = +(m.slice(1).find((x) => x && /^\d+$/.test(x)));
        const lows = [65, 70, 75, 80, 85, 90];
        let idx = lows.findIndex((l) => l >= age);
        if (age <= 65) idx = 0;
        if (idx < 0) idx = 5;
        f.am = idx;
        tags.push(`${lows[idx]} años o más`);
    }
    const mb = t.match(/ entre (\d{2}) y (\d{2,3})/);
    if (mb && f.am == null) {
        const a = +mb[1];
        const idx = [65, 70, 75, 80, 85, 90].findIndex((l, i, arr) => a >= l && (i === 5 || a < arr[i + 1]));
        if (idx >= 0) { f.a = idx; tags.push(AB[idx] + ' años'); }
    }
    const my = t.match(/ (20[0-2]\d) /);
    if (my && YS.includes(my[1])) { f.y = YS.indexOf(my[1]); tags.push('Solicitud ' + my[1]); }
    if (has(/ (mas de un ano|mas de 365|mas de 12 meses|antiguos?|atrasad)/)) {
        f.t = 6; f.e = 1;
        if (!tags.includes('En trámite')) tags.push('En trámite');
        tags.push('Más de 365 días');
    }
    return { f, tags };
}
