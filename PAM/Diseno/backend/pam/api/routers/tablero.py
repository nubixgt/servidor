"""Datos agregados del tablero (lee solo mart.*, nunca el detalle)."""
from __future__ import annotations

import gzip
import json
import threading

from fastapi import APIRouter, Depends, Request, Response

from ...db import lectura
from ..deps import Usuario, requiere

router = APIRouter(prefix="/tablero", tags=["Tablero"])
AB = ["65–69", "70–74", "75–79", "80–84", "85–89", "90+"]
DIMS = ["m", "e", "a", "y", "r", "v", "s", "f", "n", "t"]
MEAS = ["c", "p", "qd", "qu", "qx", "qf", "qe", "qa", "qs", "qm"]
SERIES = ["sol", "vpos", "vneg", "res", "ing", "sal", "susp", "fall"]
Y0 = 2006
_cache: dict[tuple, dict] = {}
_lock = threading.Lock()


def _version(cur) -> str:
    cur.execute("SELECT coalesce((SELECT valor FROM mart.meta WHERE clave='cache_version'),'0') AS v, (SELECT valor FROM mart.meta WHERE clave='refrescado_en') AS r")
    r = cur.fetchone()
    return r["v"], r["r"]


def _ambito(u: Usuario) -> tuple[str, list, str]:
    if u.municipio_id:
        return " AND x.municipio_id = %s", [u.municipio_id], f"m{u.municipio_id}"
    if u.departamento_id:
        return " AND x.municipio_id / 100 = %s", [u.departamento_id], f"d{u.departamento_id}"
    return "", [], "nac"


def _catalogos(cur, wsql: str, wp: list) -> dict:
    cur.execute(f"SELECT DISTINCT x.municipio_id FROM mart.mv_cubo x WHERE true {wsql} ORDER BY 1", wp)
    mids = [r["municipio_id"] for r in cur.fetchall()]
    cur.execute("SELECT id, codigo, nombre FROM ref.municipio WHERE id = ANY(%s) ORDER BY id", (mids,))
    mun = cur.fetchall()
    cur.execute("SELECT nombre FROM ref.cat_estado ORDER BY orden")
    es = [r["nombre"] for r in cur.fetchall()]
    cur.execute("SELECT nombre FROM ref.cat_familia_resolucion ORDER BY orden")
    rg = [r["nombre"] for r in cur.fetchall()] + ["Sin resolución"]
    cur.execute(f"SELECT DISTINCT x.anio FROM mart.mv_cubo x WHERE true {wsql} ORDER BY 1", wp)
    ys = [r["anio"] for r in cur.fetchall()]
    return {"mun": mun, "es": es, "rg": rg, "ys": ys}


def construir(u: Usuario) -> dict:
    wsql, wp, clave = _ambito(u)
    with lectura() as cur:
        ver, refrescado = _version(cur)
        k = (clave, ver)
        with _lock:
            if k in _cache:
                return _cache[k]
        cat = _catalogos(cur, wsql, wp)
        midx = {m["id"]: i for i, m in enumerate(cat["mun"])}
        yidx = {y: i for i, y in enumerate(cat["ys"])}
        cur.execute(f"SELECT x.* FROM mart.mv_cubo x WHERE true {wsql}", wp)
        cols = {d: [] for d in DIMS + MEAS}
        for r in cur:
            cols["m"].append(midx[r["municipio_id"]]); cols["e"].append(r["e"]); cols["a"].append(r["a"])
            cols["y"].append(yidx[r["anio"]]); cols["r"].append(r["r"]); cols["v"].append(r["v"]); cols["s"].append(r["s"])
            cols["f"].append(r["f"]); cols["n"].append(r["n"]); cols["t"].append(r["t"])
            for mm in MEAS:
                cols[mm].append(r[mm])
        cubo = {"dims": DIMS, "meas": MEAS, "cols": cols, "MU": [m["codigo"] for m in cat["mun"]], "MN": [m["nombre"] for m in cat["mun"]],
                "ES": cat["es"], "AB": AB, "YS": [str(y) if y else "s/f" for y in cat["ys"]], "RG": cat["rg"]}
        # series
        yr = {k: [] for k in SERIES}
        mo = {k: [] for k in SERIES}
        cur.execute(f"""SELECT x.municipio_id, x.e, x.tipo, extract(year FROM x.mes)::int AS anio, sum(x.n)::int AS n
                        FROM mart.mv_serie x WHERE true {wsql} GROUP BY 1,2,3,4""", wp)
        for r in cur:
            if r["municipio_id"] in midx:
                yr[r["tipo"]].append([midx[r["municipio_id"]], r["e"], r["anio"] - Y0, r["n"]])
        cur.execute(f"""SELECT x.municipio_id / 100 - 1 AS dg, x.e, x.tipo,
                               (extract(year FROM x.mes)::int - 2018) * 12 + extract(month FROM x.mes)::int - 1 AS mi, sum(x.n)::int AS n
                        FROM mart.mv_serie x WHERE x.mes >= DATE '2018-01-01' {wsql} GROUP BY 1,2,3,4""", wp)
        for r in cur:
            mo[r["tipo"]].append([r["dg"], r["e"], r["mi"], r["n"]])
        cur.execute("SELECT dep, cohorte, tramo, n, media, mediana, p90 FROM mart.mv_tiempos")
        tm: dict = {"all": {}, "rec": {}}
        for r in cur:
            clave_dep = "N" if r["dep"] == 0 else str(r["dep"] - 1)
            tm[r["cohorte"]].setdefault(r["tramo"], {})[clave_dep] = [r["n"], r["media"], r["mediana"], r["p90"]]
        cur.execute("SELECT (valor #>> '{}') AS v FROM ref.parametro WHERE clave='cohorte_reciente_desde'")
        cohorte = cur.fetchone()["v"]
        cur.execute("SELECT max(fecha_estado) AS corte FROM core.expediente WHERE fecha_estado <= current_date")
        corte = cur.fetchone()["corte"]
        datos_obj = {"cubo": cubo, "series": {"Y0": Y0, "yr": yr, "mo": mo, "tm": tm, "cohorte_desde": cohorte},
                 "meta": {"refrescado_en": refrescado, "fecha_corte": corte.isoformat() if corte else None, "ambito": clave}}
        crudo = json.dumps(datos_obj, separators=(",", ":"), ensure_ascii=False, default=str).encode()
        datos = {"etag": f'"{clave}-{ver}"', "json": crudo, "gz": gzip.compress(crudo, 6)}
        with _lock:
            if len(_cache) > 64:
                _cache.clear()
            _cache[k] = datos
        return datos


@router.get("/datos")
def datos(request: Request, u: Usuario = Depends(requiere("dashboard.ver"))):
    d = construir(u)
    h = {"ETag": d["etag"], "Cache-Control": "private, no-cache", "Vary": "Authorization, Accept-Encoding"}
    if request.headers.get("if-none-match") == d["etag"]:
        return Response(status_code=304, headers=h)
    if "gzip" in request.headers.get("accept-encoding", ""):
        return Response(d["gz"], media_type="application/json", headers={**h, "Content-Encoding": "gzip"})
    return Response(d["json"], media_type="application/json", headers=h)
