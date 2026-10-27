"""Reportes y exportación controlada (CSV / Excel). Toda exportación queda en la bitácora."""
from __future__ import annotations

import csv
import io
import tempfile
from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, Request
from fastapi.responses import FileResponse
from pydantic import BaseModel, Field
from starlette.background import BackgroundTask

from ...db import lectura
from ..deps import Usuario, ambito_sql, auditar, requiere
from ..filtros import Filtros, where

router = APIRouter(prefix="/reportes", tags=["Reportes"])

# id: (título, descripción, requiere datos personales)
CATALOGO = {
    "resumen_departamental": ("Resumen departamental", "Indicadores por departamento", False),
    "resumen_municipal": ("Resumen municipal", "Indicadores por municipio", False),
    "estados": ("Estados actuales", "Expedientes por estado y departamento", False),
    "edad": ("Distribución por edad", "Rangos de edad por estado", False),
    "antiguedad": ("Antigüedad de expedientes", "Pendientes por rango de días y departamento", False),
    "resoluciones": ("Resoluciones", "Última resolución por familia y departamento", False),
    "serie_mensual": ("Movimientos por mes", "Solicitudes, visitas, resoluciones, ingresos, salidas, suspensiones y fallecimientos", False),
    "calidad": ("Calidad de datos", "Hallazgos por regla y departamento", False),
    "listado": ("Listado de expedientes", "Detalle con datos personales según los filtros (DPI enmascarado salvo permiso)", True),
}

EST = "(SELECT nombre FROM ref.cat_estado WHERE orden = x.e)"
COLS_RES = """sum(c) AS registros, sum(p) AS personas, sum(c) FILTER (WHERE n=1) AS en_nomina, sum(c) FILTER (WHERE e=1) AS en_tramite,
  sum(c) FILTER (WHERE v=1) AS visita_positiva, sum(c) FILTER (WHERE v=2) AS visita_negativa, sum(c) FILTER (WHERE e=4) AS suspendidos,
  sum(c) FILTER (WHERE f=1) AS fallecidos"""


class Exportacion(BaseModel):
    tipo: str
    formato: str = Field("xlsx", pattern="^(xlsx|csv)$")
    motivo: str = Field(min_length=5, max_length=300)
    filtros: Filtros = Filtros()


@router.get("")
def catalogo(u: Usuario = Depends(requiere("reportes.ver"))):
    return [{"id": k, "titulo": t, "descripcion": d, "datos_personales": p, "permitido": (not p) or u.puede("reportes.exportar")}
            for k, (t, d, p) in CATALOGO.items()]


def _amb_cubo(u: Usuario) -> tuple[str, list]:
    if u.municipio_id:
        return " AND x.municipio_id = %s", [u.municipio_id]
    if u.departamento_id:
        return " AND x.municipio_id / 100 = %s", [u.departamento_id]
    return "", []


def _cubo_where(fl: Filtros) -> tuple[str, list]:
    w, p = [], []
    if fl.dep is not None: w.append("x.municipio_id / 100 = %s"); p.append(fl.dep)
    if fl.mun is not None: w.append("x.municipio_id = %s"); p.append(fl.mun)
    if fl.anio is not None: w.append("x.anio = %s"); p.append(fl.anio)
    for k in ("e", "r", "n", "v", "s", "f", "a", "t"):
        val = getattr(fl, k)
        if val is not None: w.append(f"x.{k} = %s"); p.append(val)
    if fl.am is not None: w.append("x.a >= %s"); p.append(fl.am)
    return ("".join(" AND " + c for c in w), p)


def _consulta(tipo: str, fl: Filtros, u: Usuario, limite: int) -> tuple[list[str], list]:
    aw, ap = _amb_cubo(u)
    cw, cp = _cubo_where(fl)
    W, P = f"WHERE true {aw} {cw}", ap + cp
    dep = "JOIN ref.departamento d ON d.id = x.municipio_id / 100"
    q = {
        "resumen_departamental": f"SELECT d.nombre AS departamento, {COLS_RES} FROM mart.mv_cubo x {dep} {W} GROUP BY d.id, d.nombre ORDER BY d.id",
        "resumen_municipal": f"SELECT d.nombre AS departamento, m.codigo, m.nombre AS municipio, {COLS_RES} FROM mart.mv_cubo x {dep} JOIN ref.municipio m ON m.id = x.municipio_id {W} GROUP BY d.id, d.nombre, m.id ORDER BY m.id",
        "estados": f"SELECT d.nombre AS departamento, {EST} AS estado, sum(c) AS registros FROM mart.mv_cubo x {dep} {W} GROUP BY d.id, d.nombre, x.e ORDER BY d.id, x.e",
        "edad": f"SELECT (ARRAY['65-69','70-74','75-79','80-84','85-89','90+'])[x.a+1] AS rango_edad, {EST} AS estado, sum(c) AS registros FROM mart.mv_cubo x {W} GROUP BY x.a, x.e ORDER BY x.a, x.e",
        "antiguedad": f"SELECT d.nombre AS departamento, (ARRAY['0-30','31-60','61-90','91-180','181-365','Más de 365'])[x.t] AS dias_en_tramite, sum(c) AS expedientes FROM mart.mv_cubo x {dep} {W} AND x.t > 0 GROUP BY d.id, d.nombre, x.t ORDER BY d.id, x.t",
        "resoluciones": f"SELECT d.nombre AS departamento, coalesce((SELECT nombre FROM ref.cat_familia_resolucion WHERE orden = x.r), 'Sin resolución') AS familia, sum(c) AS registros FROM mart.mv_cubo x {dep} {W} GROUP BY d.id, d.nombre, x.r ORDER BY d.id, x.r",
        "calidad": f"""SELECT d.nombre AS departamento, sum(c) AS registros, sum(qd) AS documento_sin_cui_valido, sum(qu) AS dpi_repetido, sum(qx) AS expediente_repetido,
                       sum(qf) AS fechas_inconsistentes, sum(qe) AS edad_fuera_de_rango, sum(qa) AS fallecido_activo, sum(qs) AS suspendido_activo, sum(qm) AS sin_telefono
                       FROM mart.mv_cubo x {dep} {W} GROUP BY d.id, d.nombre ORDER BY d.id""",
    }
    if tipo == "serie_mensual":
        sw = aw.replace("x.municipio_id / 100", "x.municipio_id / 100")
        sp = list(ap)
        if fl.dep is not None: sw += " AND x.municipio_id / 100 = %s"; sp.append(fl.dep)
        if fl.mun is not None: sw += " AND x.municipio_id = %s"; sp.append(fl.mun)
        if fl.e is not None: sw += " AND x.e = %s"; sp.append(fl.e)
        sql = f"""SELECT to_char(x.mes, 'YYYY-MM') AS mes,
                    sum(n) FILTER (WHERE tipo='sol') AS solicitudes, sum(n) FILTER (WHERE tipo='vpos') AS visitas_positivas,
                    sum(n) FILTER (WHERE tipo='vneg') AS visitas_negativas, sum(n) FILTER (WHERE tipo='res') AS resoluciones,
                    sum(n) FILTER (WHERE tipo='ing') AS ingresos_nomina, sum(n) FILTER (WHERE tipo='sal') AS salidas_nomina,
                    sum(n) FILTER (WHERE tipo='susp') AS suspensiones, sum(n) FILTER (WHERE tipo='fall') AS fallecimientos
                  FROM mart.mv_serie x WHERE true {sw} GROUP BY x.mes ORDER BY x.mes"""
        params = sp
    elif tipo == "listado":
        w, p = where(fl)
        amb, apv = ambito_sql(u)
        dpi = "v.documento_numero" if u.puede("beneficiarios.ver_dpi") else \
              "CASE WHEN length(v.documento_numero)=13 THEN '•••• ••••• ' || right(v.documento_numero,4) ELSE '•••' || right(v.documento_numero,2) END"
        sql = f"""SELECT v.numero AS expediente, v.nombre, {dpi} AS dpi, v.fecha_nacimiento, v.edad, v.departamento, v.municipio,
                  v.telefono, v.direccion, v.fecha_solicitud, v.estado, v.fecha_estado,
                  CASE v.situacion_nomina WHEN 1 THEN 'Activo' WHEN 2 THEN 'Egresado' ELSE 'Nunca ingresó' END AS nomina,
                  v.fecha_nomina_desde, v.fecha_nomina_hasta, v.ult_visita_fecha, v.ult_resolucion_codigo, v.ult_resolucion_fecha,
                  v.fecha_suspension, v.fecha_fallecimiento, v.ult_observacion
                  FROM core.v_expediente v WHERE true {w} {amb} ORDER BY v.departamento_id, v.municipio_id, v.nombre_norm LIMIT %s"""
        params = p + apv + [limite + 1]
    elif tipo in q:
        sql, params = q[tipo], P
    else:
        raise HTTPException(400, "Reporte desconocido")
    with lectura() as cur:
        cur.execute(sql, params)
        cols = [d.name for d in cur.description]
        filas = [[r[c] for c in cols] for r in cur.fetchall()]
    return cols, filas


@router.post("/exportar")
def exportar(datos: Exportacion, request: Request, u: Usuario = Depends(requiere("reportes.ver"))):
    if datos.tipo not in CATALOGO:
        raise HTTPException(400, "Reporte desconocido")
    titulo, _, personal = CATALOGO[datos.tipo]
    limite = 10**7
    if personal:
        if not u.puede("reportes.exportar"):
            raise HTTPException(403, "Su rol no puede exportar datos personales")
        with lectura() as cur:
            cur.execute("SELECT valor FROM ref.parametro WHERE clave='export_max_filas_detalle'")
            lim = cur.fetchone()["valor"]
        limite = int(lim.get(u.rol, 10000))
    cols, filas = _consulta(datos.tipo, datos.filtros, u, limite)
    if personal and len(filas) > limite:
        raise HTTPException(413, f"El listado supera el máximo de {limite:,} filas para su rol. Agregue filtros (departamento, municipio, estado…).")
    sello = datetime.now().strftime("%Y%m%d_%H%M")
    nombre = f"PAM_{datos.tipo}_{sello}.{datos.formato}"
    tmp = tempfile.NamedTemporaryFile(delete=False, suffix="." + datos.formato)
    marca = f"Generado por {u.usuario} ({u.rol_nombre}) el {datetime.now():%d/%m/%Y %H:%M} · Motivo: {datos.motivo} · Uso oficial MINTRAB"
    if datos.formato == "csv":
        with open(tmp.name, "w", encoding="utf-8-sig", newline="") as f:
            w = csv.writer(f)
            w.writerow([marca])
            w.writerow(cols)
            w.writerows(filas)
    else:
        from openpyxl import Workbook
        from openpyxl.styles import Font
        wb = Workbook(write_only=True)
        ws = wb.create_sheet(titulo[:30])
        from openpyxl.cell import WriteOnlyCell
        c = WriteOnlyCell(ws, value=f"Ministerio de Trabajo y Previsión Social · Programa del Adulto Mayor · {titulo}")
        c.font = Font(bold=True, size=12)
        ws.append([c])
        ws.append([marca])
        ws.append([])
        hdr = []
        for col in cols:
            hc = WriteOnlyCell(ws, value=col.replace("_", " ").capitalize())
            hc.font = Font(bold=True)
            hdr.append(hc)
        ws.append(hdr)
        for fila in filas:
            ws.append(fila)
        wb.save(tmp.name)
    auditar(u, "EXPORTAR", "reportes", "reporte", datos.tipo,
            {"formato": datos.formato, "filas": len(filas), "motivo": datos.motivo, "filtros": datos.filtros.model_dump(exclude_none=True)}, request)
    import os
    return FileResponse(tmp.name, filename=nombre, background=BackgroundTask(os.unlink, tmp.name),
                        media_type="text/csv" if datos.formato == "csv" else "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
