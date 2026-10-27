import psycopg
import pytest
from psycopg.rows import dict_row

from conftest import TEST_URL, crear_usuario, generar_matriz, token


def q(sql, *p):
    with psycopg.connect(TEST_URL, row_factory=dict_row) as c:
        return c.execute(sql, p).fetchall()


# ---------------------------------------------------------------- importación
def test_importacion_concilia(base):
    rep, esp = base["reporte"], base["esperado"]
    assert rep["total_matriz"] == esp["total"]
    assert rep["total_base_lote"] == esp["validas"]
    assert rep["errores"] == {"MUNICIPIO_DESCONOCIDO": 1, "ESTADO_DESCONOCIDO": 1}
    assert rep["advertencias"]["FALLECIDO_ACTIVO"] == 1
    assert rep["advertencias"]["DOCUMENTO_NO_CUI"] == 1
    # cada expediente tiene exactamente un estado vigente
    assert q("SELECT count(*) AS n FROM core.estado_expediente WHERE vigente")[0]["n"] == esp["validas"]
    # fila repetida exacta: dos expedientes, una persona
    r = q("""SELECT count(*) AS exp, count(DISTINCT persona_id) AS per FROM core.expediente
             WHERE split_part(clave_origen, '#', 1) = (SELECT split_part(clave_origen, '#', 1) FROM core.expediente WHERE clave_origen LIKE '%%#2')""")[0]
    assert r == {"exp": 2, "per": 1}
    # años y relleno '1900' / '#VALOR!' no generan fechas
    assert q("SELECT count(*) AS n FROM core.visita WHERE fecha < '1901-01-01'")[0]["n"] == 0
    # agregados del tablero cuadran con el detalle
    assert q("SELECT sum(c) AS n FROM mart.mv_cubo")[0]["n"] == esp["validas"]


def test_reimportar_es_idempotente_y_guarda_historial(base):
    from pam.etl.importador import crear_lote, ejecutar_lote
    antes = q("SELECT count(*) AS n FROM core.expediente")[0]["n"]
    estados_antes = q("SELECT count(*) AS n FROM core.estado_expediente")[0]["n"]
    ruta = base["dir"] / "matriz_v2.csv"
    generar_matriz(ruta, cambios={0: "FALLECIDO", 1: "EXPEDIENTE ANULADO"})   # dos expedientes cambian de estado
    with psycopg.connect(TEST_URL, autocommit=True, row_factory=dict_row) as c:
        lote = crear_lote(c, ruta, None)
    ejecutar_lote(lote, TEST_URL)
    l = q("SELECT filas_insertadas, filas_actualizadas FROM stg.import_lote WHERE id=%s", lote)[0]
    assert l["filas_insertadas"] == 0
    assert q("SELECT count(*) AS n FROM core.expediente")[0]["n"] == antes
    # dos expedientes cambiaron de estado: el historial crece, nunca se sobrescribe
    assert q("SELECT count(*) AS n FROM core.estado_expediente")[0]["n"] == estados_antes + 2
    assert q("SELECT count(*) AS n FROM core.estado_expediente WHERE vigente")[0]["n"] == antes


def test_lector_excel(base, tmp_path):
    from openpyxl import Workbook
    from pam.etl.lector import Lectura
    import csv
    wb = Workbook()
    ws = wb.active
    ws.append(["Reporte PAM"])  # fila de título antes de los encabezados
    with open(base["ruta"], encoding="mac_roman") as f:
        for i, row in enumerate(csv.reader(f, delimiter=";")):
            ws.append(row)
            if i > 20:
                break
    p = tmp_path / "m.xlsx"
    wb.save(p)
    filas = list(Lectura(p))
    assert len(filas) == 21
    assert filas[0][1][3].startswith("MARÍA")


# ---------------------------------------------------------------- seguridad
def test_login_bloqueo_y_cambio_obligatorio(cliente):
    crear_usuario("bloqueo", "CONSULTA")
    for _ in range(5):
        assert cliente.post("/api/v1/auth/login", json={"usuario": "bloqueo", "clave": "mala"}).status_code == 401
    assert cliente.post("/api/v1/auth/login", json={"usuario": "bloqueo", "clave": "ClavePrueba123"}).status_code == 423
    with psycopg.connect(TEST_URL, autocommit=True) as c:
        c.execute("UPDATE seg.usuario SET debe_cambiar_clave = true, bloqueado_hasta = NULL, intentos_fallidos = 0 WHERE usuario = 'bloqueo'")
    h = token(cliente, "bloqueo")
    assert cliente.get("/api/v1/tablero/datos", headers=h).status_code == 403
    assert cliente.post("/api/v1/auth/cambiar-clave", headers=h, json={"actual": "ClavePrueba123", "nueva": "corta"}).status_code == 400
    assert cliente.post("/api/v1/auth/cambiar-clave", headers=h, json={"actual": "ClavePrueba123", "nueva": "OtraClave2026x"}).status_code == 200
    h = token(cliente, "bloqueo", "OtraClave2026x")
    assert cliente.get("/api/v1/tablero/datos", headers=h).status_code == 200


def test_refresh_y_logout(cliente):
    crear_usuario("sesion", "CONSULTA")
    token(cliente, "sesion")
    r = cliente.post("/api/v1/auth/refresh")
    assert r.status_code == 200 and r.json()["access_token"]
    cliente.post("/api/v1/auth/logout")
    assert cliente.post("/api/v1/auth/refresh").status_code == 401


def test_permisos_por_rol(cliente):
    crear_usuario("consulta1", "CONSULTA")
    h = token(cliente, "consulta1")
    eid = q("SELECT id FROM core.expediente ORDER BY id LIMIT 1")[0]["id"]
    d = cliente.get(f"/api/v1/expedientes/{eid}", headers=h).json()
    assert "•" in d["persona"]["dpi"] and d["puede_ver_dpi"] is False
    assert cliente.get(f"/api/v1/expedientes/{eid}/dpi", headers=h).status_code == 403
    assert cliente.post(f"/api/v1/expedientes/{eid}/visitas", headers=h, json={"fecha": "2026-01-10", "resultado": "POSITIVA"}).status_code == 403
    assert cliente.post("/api/v1/reportes/exportar", headers=h, json={"tipo": "listado", "motivo": "prueba de permisos"}).status_code == 403
    assert cliente.get("/api/v1/admin/usuarios", headers=h).status_code == 403
    assert cliente.get("/api/v1/tablero/datos").status_code == 401


def test_ambito_territorial(cliente):
    crear_usuario("cobanero", "SUPERVISOR", mun=1601, dep=16)
    h = token(cliente, "cobanero")
    cubo = cliente.get("/api/v1/tablero/datos", headers=h).json()["cubo"]
    assert cubo["MU"] == ["1601"]
    fuera = q("SELECT id FROM core.expediente WHERE municipio_id <> 1601 LIMIT 1")[0]["id"]
    assert cliente.get(f"/api/v1/expedientes/{fuera}", headers=h).status_code == 404
    lista = cliente.get("/api/v1/expedientes", headers=h, params={"limite": 100}).json()["items"]
    assert lista and all(x["municipio_id"] == 1601 for x in lista)


# ---------------------------------------------------------------- consulta
def test_busqueda_sin_tildes(cliente):
    crear_usuario("operador1", "OPERADOR")
    h = token(cliente, "operador1")
    r = cliente.get("/api/v1/expedientes", headers=h, params={"q": "jose  lopez casoborde"}).json()
    assert r["total"] == 1 and r["items"][0]["nombre"] == "JOSÉ LÓPEZ CASOBORDE"
    r = cliente.get("/api/v1/expedientes", headers=h, params={"q": "1234567"}).json()   # No. ID del apoderado
    assert r["total"] == 1
    r = cliente.get("/api/v1/expedientes", headers=h, params={"regla": "qa"}).json()
    assert r["total"] == 1


def test_paginacion_por_cursor(cliente):
    h = token(cliente, "operador1")
    vistos, cursor = set(), None
    while True:
        r = cliente.get("/api/v1/expedientes", headers=h, params={"limite": 50, "cursor": cursor, "orden": "nombre", "dir": "asc"}).json()
        vistos |= {x["id"] for x in r["items"]}
        cursor = r["siguiente"]
        if not cursor:
            break
    assert len(vistos) == q("SELECT count(*) AS n FROM core.expediente")[0]["n"]


def test_tablero_etag(cliente):
    h = token(cliente, "operador1")
    r = cliente.get("/api/v1/tablero/datos", headers=h)
    assert r.status_code == 200 and sum(r.json()["cubo"]["cols"]["c"]) == q("SELECT count(*) AS n FROM core.expediente")[0]["n"]
    r2 = cliente.get("/api/v1/tablero/datos", headers={**h, "If-None-Match": r.headers["etag"]})
    assert r2.status_code == 304


# ---------------------------------------------------------------- gestión y auditoría
def test_flujo_de_gestion_con_auditoria(cliente):
    crear_usuario("super1", "SUPERVISOR")
    h = token(cliente, "super1")
    r = cliente.post("/api/v1/expedientes", headers=h, json={"nombre": "Persona Nueva Prueba", "dpi": None, "fecha_nacimiento": "1950-05-05",
                                                             "municipio_id": 1601, "fecha_solicitud": "2026-01-15"})
    assert r.status_code == 201, r.text
    eid = r.json()["id"]
    assert cliente.post(f"/api/v1/expedientes/{eid}/visitas", headers=h, json={"fecha": "2026-01-20", "resultado": "POSITIVA"}).status_code == 201
    assert cliente.post(f"/api/v1/expedientes/{eid}/resoluciones", headers=h, json={"fecha": "2026-02-01", "tipo_codigo": "A", "numero": "RES-1"}).status_code == 201
    assert cliente.post(f"/api/v1/expedientes/{eid}/nomina", headers=h, json={"tipo": "ALTA", "fecha": "2026-02-28"}).status_code == 201
    d = cliente.get(f"/api/v1/expedientes/{eid}", headers=h).json()
    assert d["expediente"]["estado_orden"] == 0 and d["expediente"]["fecha_nomina_desde"] == "2026-02-28"
    assert cliente.post(f"/api/v1/expedientes/{eid}/nomina", headers=h, json={"tipo": "BAJA", "fecha": "2026-03-10", "estado_codigo": "FALLECIDO", "motivo": "RENAP"}).status_code == 201
    d = cliente.get(f"/api/v1/expedientes/{eid}", headers=h).json()
    assert d["expediente"]["estado"] == "Fallecido" and d["expediente"]["fecha_fallecimiento"] == "2026-03-10"
    assert [e["vigente"] for e in d["estados"]].count(True) == 1 and len(d["estados"]) == 3
    tipos = {e["tipo"] for e in d["eventos"]}
    assert {"SOLICITUD", "VISITA_POSITIVA", "RESOLUCION", "NOMINA_ALTA", "NOMINA_BAJA", "FALLECIMIENTO", "ESTADO"} <= tipos
    # no se puede volver a dar de alta a un fallecido
    assert cliente.post(f"/api/v1/expedientes/{eid}/nomina", headers=h, json={"tipo": "ALTA", "fecha": "2026-03-20"}).status_code == 400
    aud = q("SELECT accion, entidad FROM audit.bitacora WHERE usuario = 'super1'")
    assert ("INSERT", "core.visita") in {(a["accion"], a["entidad"]) for a in aud}
    assert ("VER_EXPEDIENTE", "core.expediente") in {(a["accion"], a["entidad"]) for a in aud}


def test_bitacora_inmutable():
    with pytest.raises(psycopg.errors.RaiseException):
        with psycopg.connect(TEST_URL) as c:
            c.execute("DELETE FROM audit.bitacora")


def test_exportacion_queda_auditada(cliente):
    h = token(cliente, "super1")
    r = cliente.post("/api/v1/reportes/exportar", headers=h, json={"tipo": "resumen_municipal", "formato": "xlsx", "motivo": "Prueba de reporte"})
    assert r.status_code == 200 and r.content[:2] == b"PK"
    r = cliente.post("/api/v1/reportes/exportar", headers=h, json={"tipo": "listado", "formato": "csv", "motivo": "Prueba listado", "filtros": {"dep": 16}})
    assert r.status_code == 200 and "•••• •••••" not in r.text   # el supervisor tiene permiso de ver DPI completo
    assert q("SELECT count(*) AS n FROM audit.bitacora WHERE accion='EXPORTAR' AND usuario='super1'")[0]["n"] == 2


def test_administracion_usuarios_y_permisos(cliente):
    crear_usuario("admin1", "ADMINISTRADOR")
    h = token(cliente, "admin1")
    roles = cliente.get("/api/v1/admin/roles", headers=h).json()["roles"]
    consulta = next(r for r in roles if r["codigo"] == "CONSULTA")
    r = cliente.post("/api/v1/admin/usuarios", headers=h, json={"usuario": "nuevo.usuario", "nombre": "Nuevo Usuario", "rol_id": consulta["id"]})
    assert r.status_code == 201 and len(r.json()["clave_temporal"]) >= 10
    nuevo = cliente.post("/api/v1/auth/login", json={"usuario": "nuevo.usuario", "clave": r.json()["clave_temporal"]}).json()
    assert nuevo["usuario"]["debe_cambiar_clave"] is True
    # quitar un permiso al rol y verificar que aplica
    perms = [p for p in consulta["permisos"] if p != "calidad.ver"]
    assert cliente.put(f"/api/v1/admin/roles/{consulta['id']}/permisos", headers=h, json=perms).status_code == 200
    assert "calidad.ver" not in cliente.get("/api/v1/admin/roles", headers=h).json()["roles"][[r["codigo"] for r in roles].index("CONSULTA")]["permisos"]


def test_orden_por_relevancia_pagina_completo(cliente):
    h = token(cliente, "operador1")
    vistos, cursor, total = [], None, None
    while True:
        r = cliente.get("/api/v1/expedientes", headers=h, params={"q": "lopez", "orden": "relevancia", "limite": 10, "cursor": cursor}).json()
        total = total if total is not None else r["total"]
        vistos += [x["id"] for x in r["items"]]
        cursor = r["siguiente"]
        if not cursor:
            break
    assert len(vistos) == len(set(vistos)) == total
