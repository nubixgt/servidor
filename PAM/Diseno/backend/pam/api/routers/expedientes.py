"""Beneficiarios / expedientes: búsqueda paginada en servidor, Expediente 360° y registro de gestiones."""
from __future__ import annotations

import base64
import json
from datetime import date

from fastapi import APIRouter, Depends, HTTPException, Query, Request
from pydantic import BaseModel, Field

from ...config import settings
from ...db import lectura, transaccion
from ...seguridad import enmascarar_dpi
from ..deps import Usuario, ambito_sql, auditar, limitar, marcar_refresco_pendiente, requiere
from ..filtros import REGLAS, Filtros, where

router = APIRouter(prefix="/expedientes", tags=["Expedientes"])

ORDENES = {
    "fsol": ("v.fecha_solicitud", "date"),
    "nombre": ("v.nombre_norm", "text"),
    "numero": ("v.numero", "text"),
    "actualizado": ("v.actualizado_en", "timestamptz"),
}


def filtros_query(
    q: str | None = None, dep: int | None = None, mun: int | None = None, anio: int | None = None, e: int | None = None,
    r: int | None = None, n: int | None = None, v: int | None = None, s: int | None = None, f: int | None = None,
    a: int | None = None, am: int | None = None, t: int | None = None, regla: str | None = None,
) -> Filtros:
    if regla and regla not in REGLAS:
        raise HTTPException(400, "Regla desconocida")
    return Filtros(q=q, dep=dep, mun=mun, anio=anio, e=e, r=r, n=n, v=v, s=s, f=f, a=a, am=am, t=t, regla=regla)


def _cursor_enc(vals: list) -> str:
    return base64.urlsafe_b64encode(json.dumps(vals, default=str).encode()).decode()


def _cursor_dec(c: str) -> list:
    try:
        return json.loads(base64.urlsafe_b64decode(c.encode()))
    except Exception:
        raise HTTPException(400, "Cursor inválido")


def _fila(r: dict) -> dict:
    return {"id": r["id"], "numero": r["numero"], "nombre": r["nombre"], "dpi": enmascarar_dpi(r["documento_numero"]),
            "documento_tipo": r["documento_tipo"], "departamento": r["departamento"], "municipio": r["municipio"],
            "municipio_id": r["municipio_id"], "edad": r["edad"], "fecha_solicitud": r["fecha_solicitud"],
            "estado_orden": r["estado_orden"], "estado": r["estado"], "situacion_nomina": r["situacion_nomina"],
            "fecha_estado": r["fecha_estado"], "actualizado_en": r["actualizado_en"]}


@router.get("")
def listar(request: Request, fl: Filtros = Depends(filtros_query), orden: str = "fsol", dir: str = "desc",
           cursor: str | None = None, limite: int = Query(25, ge=5, le=100),
           u: Usuario = Depends(requiere("beneficiarios.ver"))):
    limitar(f"busq:{u.id}", settings.busqueda_rpm)
    texto = fl.q and any(ch.isalpha() for ch in fl.q)
    if orden == "relevancia" and not texto:
        orden = "fsol"
    if orden == "relevancia":
        from psycopg import sql as _sql
        from ..filtros import norm
        lit = _sql.Literal(norm(fl.q)).as_string(None)
        col, tipo = f"similarity(v.nombre_norm, {lit})::float8", "float8"
    elif orden in ORDENES:
        col, tipo = ORDENES[orden]
    else:
        raise HTTPException(400, "Orden no permitido")
    desc = dir.lower() != "asc"
    w, p = where(fl)
    amb, ap = ambito_sql(u)
    base = f"FROM core.v_expediente v WHERE true {w} {amb}"
    params = p + ap
    pag, pp = "", []
    if cursor:
        last_val, last_id = _cursor_dec(cursor)
        op = "<" if desc else ">"
        if last_val is None:
            pag = f" AND ({col} IS NULL AND v.id {op} %s)"
            pp = [last_id]
        else:
            pag = f" AND (({col}, v.id) {op} (%s::{tipo}, %s) OR {col} IS NULL)"
            pp = [last_val, last_id]
    nulls = "NULLS LAST"
    sentido = "DESC" if desc else "ASC"
    with lectura() as cur:
        cur.execute(f"SELECT v.*, {col} AS _rel {base} {pag} ORDER BY {col} {sentido} {nulls}, v.id {sentido} LIMIT %s", params + pp + [limite + 1])
        filas = cur.fetchall()
        total = None
        if not cursor:
            cur.execute(f"SELECT count(*) AS n FROM (SELECT 1 {base} LIMIT 10001) x", params)
            total = cur.fetchone()["n"]
    hay_mas = len(filas) > limite
    filas = filas[:limite]
    sig = None
    if hay_mas and filas:
        ult = filas[-1]
        if orden == "relevancia":
            sig = _cursor_enc([ult["_rel"], ult["id"]])
        else:
            key = {"fsol": "fecha_solicitud", "nombre": "nombre_norm", "numero": "numero", "actualizado": "actualizado_en"}[orden]
            sig = _cursor_enc([ult[key], ult["id"]])
    return {"items": [_fila(r) for r in filas], "siguiente": sig, "total": total, "total_es_minimo": total == 10001}


def _obtener(cur, eid: int, u: Usuario) -> dict:
    amb, ap = ambito_sql(u)
    cur.execute(f"SELECT v.* FROM core.v_expediente v WHERE v.id = %s {amb}", [eid] + ap)
    r = cur.fetchone()
    if not r:
        raise HTTPException(404, "Expediente no encontrado o fuera de su ámbito")
    return r


@router.get("/{eid}")
def detalle(eid: int, request: Request, u: Usuario = Depends(requiere("beneficiarios.ver"))):
    with lectura() as cur:
        r = _obtener(cur, eid, u)
        cur.execute("SELECT v.id, v.fecha, c.codigo AS resultado, v.observacion, v.tecnico, v.origen, v.creado_en FROM core.visita v JOIN ref.cat_resultado_visita c ON c.id=v.resultado_id WHERE v.expediente_id=%s ORDER BY v.fecha DESC, v.id DESC", (eid,))
        visitas = cur.fetchall()
        cur.execute("SELECT s.id, s.fecha, t.codigo, t.nombre AS tipo, f.nombre AS familia, s.numero, s.descripcion, s.origen FROM core.resolucion s JOIN ref.cat_tipo_resolucion t ON t.id=s.tipo_id JOIN ref.cat_familia_resolucion f ON f.id=t.familia_id WHERE s.expediente_id=%s ORDER BY s.fecha DESC NULLS LAST, s.id DESC", (eid,))
        resol = cur.fetchall()
        cur.execute("SELECT o.id, o.fecha, o.texto, o.origen, u.usuario FROM core.observacion o LEFT JOIN seg.usuario u ON u.id=o.usuario_id WHERE o.expediente_id=%s ORDER BY o.fecha DESC, o.id DESC", (eid,))
        obs = cur.fetchall()
        cur.execute("SELECT m.id, m.tipo, m.fecha, m.motivo, m.origen FROM core.movimiento_nomina m WHERE m.expediente_id=%s ORDER BY m.fecha, m.id", (eid,))
        nomina = cur.fetchall()
        cur.execute("SELECT x.id, c.nombre AS estado, c.orden, x.fecha, x.vigente, x.motivo, x.origen, x.creado_en, u.usuario FROM core.estado_expediente x JOIN ref.cat_estado c ON c.id=x.estado_id LEFT JOIN seg.usuario u ON u.id=x.usuario_id WHERE x.expediente_id=%s ORDER BY x.creado_en DESC, x.id DESC", (eid,))
        estados = cur.fetchall()
        cur.execute("SELECT * FROM core.v_evento WHERE expediente_id=%s ORDER BY fecha NULLS LAST, creado_en", (eid,))
        eventos = cur.fetchall()
        ap = None
        if r["apoderado_id"]:
            cur.execute("SELECT tipo_id, numero_id, nombre FROM core.apoderado WHERE id=%s", (r["apoderado_id"],))
            ap = cur.fetchone()
            if ap:
                ap["numero_id"] = enmascarar_dpi(ap["numero_id"]) if not u.puede("beneficiarios.ver_dpi") else ap["numero_id"]
        cur.execute("SELECT id, numero, fecha_solicitud, estado_id FROM core.expediente WHERE persona_id=%s AND id<>%s ORDER BY fecha_solicitud", (r["persona_id"], eid))
        otros = cur.fetchall()
        cur.execute("SELECT regla, estado, comentario, actualizado_en FROM core.caso_revision WHERE expediente_id=%s", (eid,))
        casos = cur.fetchall()
        # reglas que aplican a este expediente
        conds = ", ".join(f"({c}) AS {k}" for k, (c, _, _) in REGLAS.items())
        cur.execute(f"SELECT {conds} FROM core.v_expediente v WHERE v.id=%s", (eid,))
        flags = cur.fetchone()
    alertas = [{"regla": k, "titulo": REGLAS[k][1], "prioridad": REGLAS[k][2]} for k, val in flags.items() if val and k != "qm"]
    auditar(u, "VER_EXPEDIENTE", "beneficiarios", "core.expediente", str(eid), None, request)
    per = {k: r[k] for k in ("persona_id", "nombre", "documento_tipo", "cui_valido", "fecha_nacimiento", "edad", "telefono", "direccion")}
    per["dpi"] = enmascarar_dpi(r["documento_numero"])
    exp = {k: r[k] for k in ("id", "numero", "fecha_solicitud", "estado", "estado_orden", "estado_id", "fecha_estado", "departamento", "municipio",
                             "municipio_id", "departamento_id", "situacion_nomina", "fecha_nomina_desde", "fecha_nomina_hasta",
                             "fecha_fallecimiento", "fecha_suspension", "ult_observacion", "ult_observacion_fecha", "actualizado_en")}
    return {"persona": per, "expediente": exp, "visitas": visitas, "resoluciones": resol, "observaciones": obs, "nomina": nomina,
            "estados": estados, "eventos": eventos, "apoderado": ap, "otros_expedientes": otros, "alertas": alertas, "casos": casos,
            "puede_ver_dpi": u.puede("beneficiarios.ver_dpi")}


@router.get("/{eid}/dpi")
def ver_dpi(eid: int, request: Request, u: Usuario = Depends(requiere("beneficiarios.ver_dpi"))):
    with lectura() as cur:
        r = _obtener(cur, eid, u)
    auditar(u, "VER_DPI", "beneficiarios", "core.expediente", str(eid), None, request)
    return {"dpi": r["documento_numero"]}


# ---------------------------------------------------------------- escritura
class NuevaObservacion(BaseModel):
    texto: str = Field(min_length=3, max_length=4000)
    fecha: date | None = None


class NuevaVisita(BaseModel):
    fecha: date
    resultado: str = Field(pattern="^(POSITIVA|NEGATIVA|NO_LOCALIZADO)$")
    observacion: str | None = Field(None, max_length=4000)
    tecnico: str | None = Field(None, max_length=120)


class NuevaResolucion(BaseModel):
    fecha: date
    tipo_codigo: str
    numero: str | None = Field(None, max_length=60)
    descripcion: str | None = Field(None, max_length=4000)


class CambioEstado(BaseModel):
    estado_codigo: str
    fecha: date
    motivo: str = Field(min_length=5, max_length=1000)


class MovimientoNomina(BaseModel):
    tipo: str = Field(pattern="^(ALTA|BAJA)$")
    fecha: date
    motivo: str | None = Field(None, max_length=1000)
    estado_codigo: str | None = None  # para BAJA: estado resultante (SUSPENDIDO, FALLECIDO, ...)


class Contacto(BaseModel):
    telefono: str | None = Field(None, max_length=40)
    direccion: str | None = Field(None, max_length=400)


def _validar_fecha(f: date) -> None:
    if f > date.today():
        raise HTTPException(400, "La fecha no puede ser futura")


def _post_escritura(cur, eid: int) -> None:
    cur.execute("SELECT core.recalcular_resumen(ARRAY[%s]::bigint[])", (eid,))
    marcar_refresco_pendiente(cur)


def _check(cur, eid: int, u: Usuario) -> dict:
    return _obtener(cur, eid, u)


@router.post("/{eid}/observaciones", status_code=201)
def agregar_observacion(eid: int, datos: NuevaObservacion, u: Usuario = Depends(requiere("observacion.registrar"))):
    f = datos.fecha or date.today()
    _validar_fecha(f)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        _check(cur, eid, u)
        cur.execute("INSERT INTO core.observacion(expediente_id, fecha, texto, usuario_id) VALUES (%s,%s,%s,%s) RETURNING id",
                    (eid, f, datos.texto.strip(), u.id))
        nid = cur.fetchone()["id"]
        _post_escritura(cur, eid)
    return {"id": nid}


@router.post("/{eid}/visitas", status_code=201)
def agregar_visita(eid: int, datos: NuevaVisita, u: Usuario = Depends(requiere("visita.registrar"))):
    _validar_fecha(datos.fecha)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        r = _check(cur, eid, u)
        if r["fecha_solicitud"] and datos.fecha < r["fecha_solicitud"]:
            raise HTTPException(400, "La visita no puede ser anterior a la solicitud")
        cur.execute("""INSERT INTO core.visita(expediente_id, fecha, resultado_id, observacion, tecnico, usuario_id)
                       SELECT %s, %s, id, %s, %s, %s FROM ref.cat_resultado_visita WHERE codigo = %s RETURNING id""",
                    (eid, datos.fecha, datos.observacion, datos.tecnico or u.nombre, u.id, datos.resultado))
        nid = cur.fetchone()["id"]
        _post_escritura(cur, eid)
    return {"id": nid}


@router.post("/{eid}/resoluciones", status_code=201)
def agregar_resolucion(eid: int, datos: NuevaResolucion, u: Usuario = Depends(requiere("resolucion.emitir"))):
    _validar_fecha(datos.fecha)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        _check(cur, eid, u)
        cur.execute("SELECT id FROM ref.cat_tipo_resolucion WHERE codigo = %s AND activo", (datos.tipo_codigo,))
        t = cur.fetchone()
        if not t:
            raise HTTPException(400, "Tipo de resolución no válido")
        cur.execute("""INSERT INTO core.resolucion(expediente_id, fecha, tipo_id, numero, descripcion, usuario_id)
                       VALUES (%s,%s,%s,%s,%s,%s) RETURNING id""", (eid, datos.fecha, t["id"], datos.numero, datos.descripcion, u.id))
        nid = cur.fetchone()["id"]
        _post_escritura(cur, eid)
    return {"id": nid}


def _cambiar_estado(cur, eid: int, codigo: str, fecha: date, motivo: str, u: Usuario) -> None:
    cur.execute("SELECT id FROM ref.cat_estado WHERE codigo = %s", (codigo,))
    est = cur.fetchone()
    if not est:
        raise HTTPException(400, "Estado no válido")
    cur.execute("UPDATE core.estado_expediente SET vigente = false WHERE expediente_id = %s AND vigente", (eid,))
    cur.execute("""INSERT INTO core.estado_expediente(expediente_id, estado_id, fecha, vigente, motivo, usuario_id)
                   VALUES (%s,%s,%s,true,%s,%s)""", (eid, est["id"], fecha, motivo, u.id))
    if codigo == "FALLECIDO":
        cur.execute("UPDATE core.expediente SET fecha_fallecimiento = coalesce(fecha_fallecimiento, %s) WHERE id = %s", (fecha, eid))
    if codigo == "SUSPENDIDO":
        cur.execute("UPDATE core.expediente SET fecha_suspension = %s WHERE id = %s", (fecha, eid))


@router.post("/{eid}/estado", status_code=201)
def cambiar_estado(eid: int, datos: CambioEstado, u: Usuario = Depends(requiere("estado.cambiar"))):
    _validar_fecha(datos.fecha)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        r = _check(cur, eid, u)
        if datos.estado_codigo == "EN_NOMINA_ACTIVA":
            raise HTTPException(400, "El ingreso a nómina se registra como movimiento de nómina (ALTA)")
        _cambiar_estado(cur, eid, datos.estado_codigo, datos.fecha, datos.motivo, u)
        if r["estado_id"] == 1:  # sale de nómina
            cur.execute("INSERT INTO core.movimiento_nomina(expediente_id, tipo, fecha, motivo, usuario_id) VALUES (%s,'BAJA',%s,%s,%s)",
                        (eid, datos.fecha, datos.motivo, u.id))
        _post_escritura(cur, eid)
    return {"ok": True}


@router.post("/{eid}/nomina", status_code=201)
def movimiento_nomina(eid: int, datos: MovimientoNomina, u: Usuario = Depends(requiere("nomina.gestionar"))):
    _validar_fecha(datos.fecha)
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        r = _check(cur, eid, u)
        if datos.tipo == "ALTA":
            if r["estado_id"] == 1:
                raise HTTPException(400, "El expediente ya está en nómina activa")
            if r["fecha_fallecimiento"]:
                raise HTTPException(400, "No se puede dar de alta a una persona con fallecimiento registrado")
            _cambiar_estado(cur, eid, "EN_NOMINA_ACTIVA", datos.fecha, datos.motivo or "Alta en nómina", u)
        else:
            if r["estado_id"] != 1:
                raise HTTPException(400, "Solo se puede dar de baja un expediente en nómina activa")
            if not datos.estado_codigo or datos.estado_codigo == "EN_NOMINA_ACTIVA":
                raise HTTPException(400, "Indique el estado resultante de la baja")
            _cambiar_estado(cur, eid, datos.estado_codigo, datos.fecha, datos.motivo or "Baja de nómina", u)
        cur.execute("INSERT INTO core.movimiento_nomina(expediente_id, tipo, fecha, motivo, usuario_id) VALUES (%s,%s,%s,%s,%s)",
                    (eid, datos.tipo, datos.fecha, datos.motivo, u.id))
        _post_escritura(cur, eid)
    return {"ok": True}


@router.patch("/{eid}/contacto")
def actualizar_contacto(eid: int, datos: Contacto, u: Usuario = Depends(requiere("beneficiarios.editar"))):
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        r = _check(cur, eid, u)
        cur.execute("UPDATE core.persona SET telefono = %s, direccion = %s, actualizado_en = now() WHERE id = %s",
                    (datos.telefono or None, datos.direccion or None, r["persona_id"]))
        marcar_refresco_pendiente(cur)
    return {"ok": True}


class NuevaSolicitud(BaseModel):
    nombre: str = Field(min_length=5, max_length=200)
    dpi: str | None = Field(None, max_length=20)
    fecha_nacimiento: date
    telefono: str | None = None
    direccion: str | None = None
    municipio_id: int
    fecha_solicitud: date
    numero: str | None = None


@router.post("", status_code=201)
def nueva_solicitud(datos: NuevaSolicitud, u: Usuario = Depends(requiere("solicitud.registrar"))):
    _validar_fecha(datos.fecha_solicitud)
    if u.municipio_id and datos.municipio_id != u.municipio_id or (u.departamento_id and datos.municipio_id // 100 != u.departamento_id):
        raise HTTPException(403, "El municipio está fuera de su ámbito")
    dpi = "".join(ch for ch in (datos.dpi or "") if ch.isdigit()) or None
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        cur.execute("SELECT ref.f_cui_valido(%s) AS ok, ref.f_norm(%s) AS nn", (dpi, datos.nombre))
        chk = cur.fetchone()
        if dpi and len(dpi) == 13 and not chk["ok"]:
            raise HTTPException(400, "El CUI no es válido (dígito verificador o código de departamento/municipio)")
        clave = f"CUI:{dpi}" if dpi and len(dpi) == 13 else f"NF:{chk['nn']}|{datos.fecha_nacimiento.isoformat()}"
        tipo_doc = "CUI" if dpi and len(dpi) == 13 else ("CEDULA" if dpi else "SIN_DOCUMENTO")
        cur.execute("""INSERT INTO core.persona(clave, nombre, nombre_norm, documento_tipo, documento_numero, cui_valido, fecha_nacimiento, telefono, direccion, municipio_id)
                       VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                       ON CONFLICT (clave) DO UPDATE SET telefono = coalesce(EXCLUDED.telefono, core.persona.telefono),
                           direccion = coalesce(EXCLUDED.direccion, core.persona.direccion), actualizado_en = now()
                       RETURNING id""",
                    (clave, datos.nombre.strip().upper(), chk["nn"], tipo_doc, dpi, bool(chk["ok"]), datos.fecha_nacimiento,
                     datos.telefono, datos.direccion, datos.municipio_id))
        pid = cur.fetchone()["id"]
        cur.execute("SELECT count(*) AS n FROM core.expediente WHERE persona_id = %s AND estado_id IN (1,2)", (pid,))
        if cur.fetchone()["n"]:
            raise HTTPException(409, "La persona ya tiene un expediente en trámite o en nómina activa")
        cur.execute("""INSERT INTO core.expediente(numero, persona_id, municipio_id, fecha_solicitud, estado_id, fecha_estado, origen)
                       VALUES (%s,%s,%s,%s,2,%s,'SISTEMA') RETURNING id""",
                    (datos.numero or dpi, pid, datos.municipio_id, datos.fecha_solicitud, datos.fecha_solicitud))
        eid = cur.fetchone()["id"]
        if not (datos.numero or dpi):
            cur.execute("UPDATE core.expediente SET numero = %s WHERE id = %s", (f"PAM-{datos.fecha_solicitud.year}-{eid}", eid))
        cur.execute("INSERT INTO core.estado_expediente(expediente_id, estado_id, fecha, vigente, motivo, usuario_id) VALUES (%s,2,%s,true,'Solicitud registrada',%s)",
                    (eid, datos.fecha_solicitud, u.id))
        _post_escritura(cur, eid)
    return {"id": eid}


class Caso(BaseModel):
    regla: str
    estado: str = Field(pattern="^(EN_REVISION|CONFIRMADO|DESCARTADO|CORREGIDO)$")
    comentario: str | None = Field(None, max_length=2000)


@router.post("/{eid}/casos")
def gestionar_caso(eid: int, datos: Caso, u: Usuario = Depends(requiere("alertas.gestionar", "calidad.gestionar"))):
    if datos.regla not in REGLAS:
        raise HTTPException(400, "Regla desconocida")
    with transaccion(u.ctx(), u.ip, u.request_id) as cur:
        _check(cur, eid, u)
        cur.execute("""INSERT INTO core.caso_revision(regla, expediente_id, estado, comentario, usuario_id) VALUES (%s,%s,%s,%s,%s)
                       ON CONFLICT (regla, expediente_id) DO UPDATE SET estado = EXCLUDED.estado, comentario = EXCLUDED.comentario,
                       usuario_id = EXCLUDED.usuario_id, actualizado_en = now()""",
                    (datos.regla, eid, datos.estado, datos.comentario, u.id))
    return {"ok": True}
