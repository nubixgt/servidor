"""Traduce los filtros del tablero a SQL sobre core.v_expediente (alias v).

Mismos códigos que el cubo del tablero:
  dep  departamento (1-22)            mun  municipio (código INE, p. ej. 1601)
  anio año de solicitud               e    estado (orden 0-8)
  r    familia de la última resolución (orden 0-8; 9 = sin resolución)
  n    situación de nómina (0 nunca, 1 activo, 2 egresado)
  v    última visita (0 sin, 1 positiva, 2 negativa)
  s    con suspensión (0/1)           f    con fallecimiento (0/1)
  a    banda de edad (0 = 65-69 … 5 = 90+)   am  edad mínima (banda)
  t    antigüedad del trámite (1-6)   regla  regla de alerta / calidad
"""
from __future__ import annotations

import re
import unicodedata

from pydantic import BaseModel


def norm(t: str) -> str:
    t = unicodedata.normalize("NFKD", t or "")
    t = "".join(ch for ch in t if not unicodedata.combining(ch)).lower()
    return re.sub(r"\s+", " ", t).strip()

REGLAS: dict[str, tuple[str, str, str]] = {
    # clave: (condición SQL, título, prioridad)
    "qa": ("v.q_fall_activo", "Fallecidos que continúan activos en nómina", "critica"),
    "qs": ("v.q_susp_activo", "Suspendidos que continúan activos en nómina", "alta"),
    "old": ("v.antiguedad = 6", "Solicitudes en trámite con más de 365 días", "alta"),
    "nov": ("v.estado_id = 2 AND v.visita_cod = 0", "Solicitudes en trámite sin visita", "alta"),
    "rpn": ("v.ult_resolucion_familia_id = 1 AND v.situacion_nomina = 0", "Resolución positiva sin ingreso a nómina", "media"),
    "vpr": ("v.visita_cod = 1 AND v.ult_resolucion_tipo_id IS NULL", "Visita positiva sin resolución", "media"),
    "qu": ("v.flag_dpi_duplicado", "DPI registrado en más de un expediente", "media"),
    "qx": ("v.flag_exp_duplicado", "Número de expediente duplicado", "media"),
    "qf": ("v.q_fechas", "Fechas inconsistentes", "media"),
    "qd": ("v.q_doc", "Documento sin CUI válido", "baja"),
    "qe": ("v.q_edad", "Edad fuera de rango", "baja"),
    "qm": ("v.q_sin_tel", "Sin teléfono de contacto", "baja"),
}


class Filtros(BaseModel):
    dep: int | None = None
    mun: int | None = None
    anio: int | None = None
    e: int | None = None
    r: int | None = None
    n: int | None = None
    v: int | None = None
    s: int | None = None
    f: int | None = None
    a: int | None = None
    am: int | None = None
    t: int | None = None
    regla: str | None = None
    q: str | None = None


def where(fl: Filtros) -> tuple[str, list]:
    sql, p = [], []
    if fl.dep is not None:
        sql.append("v.departamento_id = %s"); p.append(fl.dep)
    if fl.mun is not None:
        sql.append("v.municipio_id = %s"); p.append(fl.mun)
    if fl.anio is not None:
        if fl.anio == 0:
            sql.append("v.fecha_solicitud IS NULL")
        else:
            sql.append("v.fecha_solicitud >= make_date(%s,1,1) AND v.fecha_solicitud < make_date(%s,1,1)"); p += [fl.anio, fl.anio + 1]
    if fl.e is not None:
        sql.append("v.estado_orden = %s"); p.append(fl.e)
    if fl.r is not None:
        if fl.r == 9:
            sql.append("v.ult_resolucion_tipo_id IS NULL")
        else:
            sql.append("v.ult_resolucion_familia_id = (SELECT id FROM ref.cat_familia_resolucion WHERE orden = %s)"); p.append(fl.r)
    if fl.n is not None:
        sql.append("v.situacion_nomina = %s"); p.append(fl.n)
    if fl.v is not None:
        sql.append("v.visita_cod = %s"); p.append(fl.v)
    if fl.s is not None:
        sql.append("v.fecha_suspension IS NOT NULL" if fl.s else "v.fecha_suspension IS NULL")
    if fl.f is not None:
        sql.append("v.fecha_fallecimiento IS NOT NULL" if fl.f else "v.fecha_fallecimiento IS NULL")
    if fl.a is not None:
        sql.append("v.banda_edad = %s"); p.append(fl.a)
    if fl.am is not None:
        sql.append("v.banda_edad >= %s"); p.append(fl.am)
    if fl.t is not None:
        sql.append("v.antiguedad = %s"); p.append(fl.t)
    if fl.regla:
        if fl.regla not in REGLAS:
            raise ValueError("Regla desconocida")
        sql.append(REGLAS[fl.regla][0])
    if fl.q and fl.q.strip():
        q = fl.q.strip()
        dig = "".join(ch for ch in q if ch.isdigit())
        if dig and len(dig) >= 3 and not any(ch.isalpha() for ch in q):
            pat = dig + "%"
            sql.append("""v.id IN (
                SELECT e.id FROM core.expediente e WHERE e.persona_id IN
                    (SELECT id FROM core.persona WHERE documento_numero LIKE %s OR telefono LIKE %s)
                UNION SELECT e.id FROM core.expediente e WHERE e.numero LIKE %s
                UNION SELECT e.id FROM core.expediente e WHERE e.apoderado_id IN (SELECT id FROM core.apoderado WHERE numero_id LIKE %s))""")
            p += [pat, pat, pat, pat]
        else:
            toks = [norm(t) for t in q.split() if norm(t)][:6]
            if toks:
                pats = ["%" + t.replace("%", "").replace("_", "") + "%" for t in toks]
                nombre = " AND ".join("pp.nombre_norm LIKE %s" for _ in toks)
                ap = " AND ".join("a.nombre_norm LIKE %s" for _ in toks)
                sql.append(f"""v.id IN (
                    SELECT e.id FROM core.expediente e WHERE e.persona_id IN (SELECT pp.id FROM core.persona pp WHERE {nombre})
                    UNION SELECT e.id FROM core.expediente e WHERE e.apoderado_id IN (SELECT a.id FROM core.apoderado a WHERE {ap}))""")
                p += pats + pats
    return (" AND " + " AND ".join(sql)) if sql else "", p
