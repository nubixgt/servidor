"""Pruebas de integración contra una base PostgreSQL temporal.

Requiere TEST_DATABASE_URL apuntando a un servidor donde el usuario pueda crear bases
(por defecto postgresql://postgres@localhost/pam_test). La base se recrea en cada corrida.
Los datos de prueba son ficticios y se generan aquí mismo.
"""
from __future__ import annotations

import csv
import os
import random
from pathlib import Path

import psycopg
import pytest

ADMIN_URL = os.getenv("TEST_ADMIN_URL", "postgresql://postgres@localhost/postgres")
TEST_URL = os.getenv("TEST_DATABASE_URL", "postgresql://postgres@localhost/pam_test")
os.environ["DATABASE_URL"] = TEST_URL
os.environ.setdefault("JWT_SECRET", "secreto-de-pruebas-con-longitud-suficiente-123456")
os.environ["COOKIE_SECURE"] = "false"
os.environ["ENTORNO"] = "pruebas"

ENCABEZADOS = ["Departamento", "Municipio", "No. Expediente", "Nombre", "DPI", "Fecha Nacimiento", "Fecha Solicitud", "AÑO DE SOLICITUD",
               "Telefono", "Direccion", "Fecha Observacion", "Ultima Observación General", "ID Apoderado", "No. ID Apoderado", "Apoderado",
               "Visita Positiva", "AÑO DE VISITA POSITIVA ", "MES DE VISITA", "Visita Negativa", "AÑO DE VISITA NEGATIVA", "MES DE VISITA NEGATIVA",
               "En Nomina Desde", "año de ingreso a nomina", "En Nomina Hasta", "Fallecio", "AÑO DE FALLECIDO ", "Suspendido",
               "Fecha Ultima Resolucion", "AÑO DE RESOLUCIÓN ", "Tipo Resolucion", "Resolucion", "Fecha Estado Actual", "AÑO DE ESTADO ACTUAL", "Estado Actual"]


def cui(rnd: random.Random, mun: str) -> str:
    while True:
        base = "".join(str(rnd.randint(0, 9)) for _ in range(8))
        v = sum(int(base[i]) * (i + 2) for i in range(8)) % 11
        if v < 10:
            return base + str(v) + mun


def fila(**k) -> list[str]:
    d = {h: "" for h in ENCABEZADOS}
    d.update({"AÑO DE VISITA POSITIVA ": "1900", "AÑO DE FALLECIDO ": "1900", "año de ingreso a nomina": "#VALOR!", "Tipo Resolucion": "0"})
    for h, v in k.items():
        d[h] = v
    return [d[h] for h in ENCABEZADOS]


def generar_matriz(ruta: Path, n: int = 400, semilla: int = 7, cambios: dict | None = None) -> dict:
    """Matriz ficticia con casos borde. Devuelve conteos esperados."""
    rnd = random.Random(semilla)
    muns = [("Alta Verapaz", "1601 - Cobán, Alta Verapaz", "1601"), ("Guatemala", "0101 - Guatemala, Guatemala", "0101"),
            ("Jutiapa", "2201 - Jutiapa, Jutiapa", "2201")]
    nombres = ["MARÍA", "JOSÉ", "PEDRO", "ROSA", "JUANA", "TOMÁS"]
    apellidos = ["LÓPEZ", "PÉREZ", "CAAL", "GARCÍA", "XOL", "MORALES"]
    estados = ["EN NOMINA ACTIVA", "EXPEDIENTE EN TRAMITE", "FALLECIDO", "EXPEDIENTE NEGATIVO", "EXPEDIENTE SUSPENDIDO"]
    filas = []
    for i in range(n):
        dep, mun, cod = muns[i % 3]
        est = estados[i % 5]
        dpi = cui(rnd, cod)
        r = dict(Departamento=dep, Municipio=mun, **{"No. Expediente": dpi, "Nombre": f"{nombres[i % 6]} {apellidos[(i // 6) % 6]} PRUEBA{i}",
                 "DPI": dpi, "Fecha Nacimiento": f"{rnd.randint(1, 28):02d}/{rnd.randint(1, 12):02d}/{rnd.randint(1930, 1958)}",
                 "Fecha Solicitud": f"{rnd.randint(1, 28):02d}/{rnd.randint(1, 12):02d}/{rnd.randint(2015, 2024)}",
                 "Telefono": "5555" + str(1000 + i) if i % 2 else "", "Direccion": "ALDEA DE PRUEBA", "Estado Actual": est,
                 "Fecha Estado Actual": "18/08/2026"})
        if est in ("EN NOMINA ACTIVA", "FALLECIDO", "EXPEDIENTE SUSPENDIDO"):
            r.update({"Visita Positiva": "10/03/2025", "Fecha Ultima Resolucion": "15/04/2025", "Tipo Resolucion": "A",
                      "Resolucion": "Resolucion Positiva", "En Nomina Desde": "30/04/2025", "En Nomina Hasta": "31/07/2026"})
        if est == "FALLECIDO":
            r.update({"Fallecio": "02/02/2026", "Tipo Resolucion": "Ñ", "Resolucion": "Resolución Suspensión (Fallecidos/RENAP)", "Fecha Ultima Resolucion": "01/03/2026"})
        if est == "EXPEDIENTE NEGATIVO":
            r.update({"Visita Negativa": "01/06/2025", "Tipo Resolucion": "B", "Resolucion": "Resolucion Negativa", "Fecha Ultima Resolucion": "01/07/2025"})
        if i % 7 == 0:
            r.update({"Fecha Observacion": "05/05/2025", "Ultima Observación General": "ADULTO MAYOR CARECE DE INGRESOS"})
        filas.append(fila(**r))
    # casos borde
    filas.append(fila(Departamento="GUATEMALA", Municipio="0101 - Guatemala, Guatemala", **{"No. Expediente": "143413", "Nombre": "JOSÉ LÓPEZ CASOBORDE",
               "DPI": "8297", "Fecha Nacimiento": "13/11/1934", "Fecha Solicitud": "25/09/2008", "Estado Actual": "EN NOMINA ACTIVA",
               "En Nomina Desde": "31/12/2008", "Fallecio": "01/01/2020", "Fecha Estado Actual": "18/08/2026",
               "ID Apoderado": "DPI", "No. ID Apoderado": "1234567", "Apoderado": "ANA APODERADA PRUEBA"}))   # cédula + fallecido activo
    filas.append(fila(Departamento="Jutiapa", Municipio="9999 - Inexistente, X", **{"No. Expediente": "1", "Nombre": "FILA RECHAZADA",
               "DPI": "1", "Estado Actual": "EN NOMINA ACTIVA"}))                                              # municipio inválido
    filas.append(fila(Departamento="Jutiapa", Municipio="2201 - Jutiapa, Jutiapa", **{"No. Expediente": "2", "Nombre": "ESTADO RARO",
               "DPI": "2", "Estado Actual": "ESTADO QUE NO EXISTE"}))                                          # estado inválido
    dup = list(filas[0])
    filas.append(dup)                                                                                          # fila repetida exacta
    if cambios:
        for idx, est in cambios.items():
            filas[idx][ENCABEZADOS.index("Estado Actual")] = est
    with open(ruta, "w", encoding="mac_roman", newline="") as f:
        w = csv.writer(f, delimiter=";")
        w.writerow(ENCABEZADOS)
        w.writerows(filas)
    return {"total": len(filas), "rechazadas": 2, "validas": len(filas) - 2}


@pytest.fixture(scope="session")
def base(tmp_path_factory):
    with psycopg.connect(ADMIN_URL, autocommit=True) as c:
        c.execute("DROP DATABASE IF EXISTS pam_test WITH (FORCE)")
        c.execute("CREATE DATABASE pam_test")
    from pam.db import migrar
    migrar(TEST_URL)
    d = tmp_path_factory.mktemp("datos")
    os.environ["IMPORT_DIR"] = str(d)
    from pam.config import settings
    settings.import_dir = d
    ruta = d / "matriz.csv"
    esperado = generar_matriz(ruta)
    from pam.etl.importador import crear_lote, ejecutar_lote
    from psycopg.rows import dict_row
    with psycopg.connect(TEST_URL, autocommit=True, row_factory=dict_row) as c:
        lote = crear_lote(c, ruta, None)
    reporte = ejecutar_lote(lote, TEST_URL)
    return {"dir": d, "ruta": ruta, "esperado": esperado, "reporte": reporte, "lote": lote}


@pytest.fixture(scope="session")
def cliente(base):
    from fastapi.testclient import TestClient
    from pam.api.main import app
    with TestClient(app) as c:
        yield c


def crear_usuario(usuario: str, rol: str, clave: str = "ClavePrueba123", dep: int | None = None, mun: int | None = None):
    from pam.seguridad import hash_clave
    with psycopg.connect(TEST_URL, autocommit=True) as c:
        c.execute("""INSERT INTO seg.usuario(usuario, nombre, hash, rol_id, departamento_id, municipio_id, debe_cambiar_clave)
                     SELECT %s, %s, %s, id, %s, %s, false FROM seg.rol WHERE codigo = %s
                     ON CONFLICT (usuario) DO UPDATE SET hash = EXCLUDED.hash, intentos_fallidos = 0, bloqueado_hasta = NULL""",
                  (usuario, usuario.title(), hash_clave(clave), dep, mun, rol))


def token(cliente, usuario: str, clave: str = "ClavePrueba123") -> dict:
    r = cliente.post("/api/v1/auth/login", json={"usuario": usuario, "clave": clave})
    assert r.status_code == 200, r.text
    return {"Authorization": "Bearer " + r.json()["access_token"]}
