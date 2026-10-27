from __future__ import annotations

from fastapi import APIRouter, Depends

from ...db import lectura
from ..deps import Usuario, requiere, usuario_actual
from ..filtros import REGLAS

router = APIRouter(prefix="/catalogos", tags=["Catálogos"])


@router.get("")
def catalogos(u: Usuario = Depends(usuario_actual)):
    with lectura() as cur:
        out = {}
        for k, sql in {
            "departamentos": "SELECT id, codigo, nombre FROM ref.departamento ORDER BY id",
            "municipios": "SELECT id, codigo, departamento_id, nombre FROM ref.municipio ORDER BY id",
            "estados": "SELECT id, codigo, nombre, orden, color FROM ref.cat_estado ORDER BY orden",
            "familias_resolucion": "SELECT id, nombre, orden FROM ref.cat_familia_resolucion ORDER BY orden",
            "tipos_resolucion": "SELECT t.id, t.codigo, t.nombre, t.familia_id, t.activo FROM ref.cat_tipo_resolucion t ORDER BY t.codigo",
            "resultados_visita": "SELECT id, codigo, nombre FROM ref.cat_resultado_visita ORDER BY id",
        }.items():
            cur.execute(sql)
            out[k] = cur.fetchall()
    out["reglas"] = [{"clave": k, "titulo": t, "prioridad": p} for k, (_, t, p) in REGLAS.items()]
    return out


@router.get("/calidad")
def calidad_extra(u: Usuario = Depends(requiere("calidad.ver"))):
    """Indicadores que no salen del cubo: posibles personas duplicadas y último reporte de importación."""
    with lectura() as cur:
        cur.execute("""SELECT count(*) AS n FROM (SELECT nombre_norm, fecha_nacimiento FROM core.persona
                       WHERE fecha_nacimiento IS NOT NULL GROUP BY 1,2 HAVING count(*) > 1) x""")
        dup = cur.fetchone()["n"]
        cur.execute("SELECT id, archivo, reporte, finalizado_en FROM stg.import_lote WHERE estado='COMPLETADO' ORDER BY id DESC LIMIT 1")
        ult = cur.fetchone()
    return {"personas_posible_duplicado": dup, "ultima_importacion": ult}
