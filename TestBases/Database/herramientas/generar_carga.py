#!/usr/bin/env python3
"""
Genera Database/03_carga_beneficiarios.sql a partir de "Base Entrega_.xlsx".

Uso (desde TestBases/):
    python3 Database/herramientas/generar_carga.py
    python3 Database/herramientas/generar_carga.py ruta/al/archivo.xlsx

Solo usa la librería estándar de Python (no requiere openpyxl).

Reglas de limpieza (todas se reportan al terminar):
  · Textos: se recortan espacios y se colapsan los espacios dobles.
  · CUI: debe tener 13 dígitos y ser único; si no, se aborta la carga.
  · Teléfono: "0" o cualquier valor que no sean 7-8 dígitos pasa a NULL.
  · Comunidad base vacía pasa a NULL.
  · Color de relleno de la celda "Nombre": rojo -> 'rojo', ámbar -> 'ambar'.
"""
import collections
import re
import sys
import xml.etree.ElementTree as ET
import zipfile
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
ENTRADA = Path(sys.argv[1]) if len(sys.argv) > 1 else RAIZ / "Base Entrega_.xlsx"
SALIDA = RAIZ / "Database" / "03_carga_beneficiarios.sql"
LOTE = 500

NS = {"m": "http://schemas.openxmlformats.org/spreadsheetml/2006/main"}
COLORES = {"FFC00000": "rojo", "FFFFC000": "ambar"}
COLUMNAS = {"A": "comunidad", "B": "numero", "C": "nombre", "D": "cui", "E": "telefono", "F": "base"}


def limpiar(texto):
    return re.sub(r"\s+", " ", texto or "").strip()


def sql(valor):
    if valor is None:
        return "NULL"
    if isinstance(valor, int):
        return str(valor)
    return "'" + valor.replace("\\", "\\\\").replace("'", "''") + "'"


def leer_filas(ruta):
    z = zipfile.ZipFile(ruta)
    textos = []
    if "xl/sharedStrings.xml" in z.namelist():
        for si in ET.fromstring(z.read("xl/sharedStrings.xml")).findall("m:si", NS):
            textos.append("".join(t.text or "" for t in si.iter("{%s}t" % NS["m"])))

    estilos = ET.fromstring(z.read("xl/styles.xml"))
    rellenos = []
    for f in estilos.find("m:fills", NS).findall("m:fill", NS):
        fg = f.find("m:patternFill/m:fgColor", NS)
        rellenos.append(fg.get("rgb") if fg is not None else None)
    color_de_estilo = [rellenos[int(xf.get("fillId", 0))] for xf in estilos.find("m:cellXfs", NS).findall("m:xf", NS)]

    hoja = ET.fromstring(z.read("xl/worksheets/sheet1.xml"))
    for fila in hoja.find("m:sheetData", NS).findall("m:row", NS):
        datos = {"_fila": int(fila.get("r")), "_color": None}
        for c in fila.findall("m:c", NS):
            letra = re.match(r"[A-Z]+", c.get("r")).group()
            v = c.find("m:v", NS)
            if v is None:
                continue
            valor = textos[int(v.text)] if c.get("t") == "s" else v.text
            if letra in COLUMNAS:
                datos[COLUMNAS[letra]] = valor
            if letra == "C":
                datos["_color"] = COLORES.get(color_de_estilo[int(c.get("s", 0))])
        yield datos


def main():
    if not ENTRADA.exists():
        sys.exit(f"No existe {ENTRADA}")

    filas = list(leer_filas(ENTRADA))
    encabezado, filas = filas[0], filas[1:]
    esperado = ["COMUNIDAD", "No.", "NOMBRE DEL O LOS BENEFICIARIOS", "CUI", "TELEFONO", "COMUNIDAD BASE"]
    leido = [limpiar(encabezado.get(k)) for k in ("comunidad", "numero", "nombre", "cui", "telefono", "base")]
    if leido != esperado:
        sys.exit(f"Encabezados inesperados: {leido}")

    comunidades, bases = {}, {}
    registros = []
    cuis = set()
    telefonos_descartados = 0
    sin_base = 0
    resaltes = collections.Counter()

    for f in filas:
        nombre = limpiar(f.get("nombre"))
        comunidad = limpiar(f.get("comunidad"))
        cui = limpiar(f.get("cui"))
        numero = limpiar(f.get("numero"))
        if not (nombre and comunidad):
            sys.exit(f"Fila {f['_fila']}: falta comunidad o nombre")
        if not re.fullmatch(r"\d{13}", cui):
            sys.exit(f"Fila {f['_fila']}: CUI inválido")
        if cui in cuis:
            sys.exit(f"Fila {f['_fila']}: CUI duplicado")
        if not numero.isdigit():
            sys.exit(f"Fila {f['_fila']}: No. inválido")
        cuis.add(cui)

        telefono = limpiar(f.get("telefono"))
        if not re.fullmatch(r"\d{7,8}", telefono):
            telefono = None
            telefonos_descartados += 1

        base = limpiar(f.get("base")) or None
        if base is None:
            sin_base += 1

        comunidad_id = comunidades.setdefault(comunidad, len(comunidades) + 1)
        base_id = bases.setdefault(base, len(bases) + 1) if base else None
        resaltado = f["_color"] or "ninguno"
        resaltes[resaltado] += 1
        registros.append((comunidad_id, base_id, int(numero), nombre, cui, telefono, resaltado, f["_fila"]))

    with open(SALIDA, "w", encoding="utf-8") as out:
        out.write(
            "-- =====================================================================\n"
            "--  TestBases · 03 · CARGA DE BENEFICIARIOS (generado automáticamente)\n"
            "--  Origen: Base Entrega_.xlsx · NO editar a mano: regenerar con\n"
            "--    python3 Database/herramientas/generar_carga.py\n"
            "--  Ejecutar UNA sola vez, después de 01 y 02. CONTIENE DATOS PERSONALES:\n"
            "--  está en .gitignore y no debe subirse al repositorio.\n"
            "--  Es transaccional: si algo falla, no queda nada a medias.\n"
            "-- =====================================================================\n\n"
            "USE testbases;\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n\n"
        )
        out.write("INSERT INTO comunidades (id, nombre) VALUES\n")
        out.write(",\n".join(f"    ({i}, {sql(n)})" for n, i in comunidades.items()) + ";\n\n")
        out.write("INSERT INTO comunidades_base (id, nombre) VALUES\n")
        out.write(",\n".join(f"    ({i}, {sql(n)})" for n, i in bases.items()) + ";\n\n")
        for ini in range(0, len(registros), LOTE):
            out.write(
                "INSERT INTO beneficiarios (comunidad_id, comunidad_base_id, numero, nombre, cui, telefono, "
                "resaltado_origen, fila_origen) VALUES\n"
            )
            out.write(
                ",\n".join(
                    "    (" + ", ".join(sql(x) for x in r) + ")" for r in registros[ini : ini + LOTE]
                )
                + ";\n\n"
            )
        out.write(
            f"-- Verificación: debe devolver {len(registros)}\n"
            "SELECT COUNT(*) AS beneficiarios_cargados FROM beneficiarios;\n\n"
            "COMMIT;\n"
        )

    print(f"Archivo: {SALIDA}")
    print(f"Beneficiarios: {len(registros)}")
    print(f"Comunidades: {len(comunidades)} · Comunidades base: {len(bases)}")
    print(f"Sin comunidad base (NULL): {sin_base}")
    print(f"Teléfonos descartados (0 o inválidos): {telefonos_descartados}")
    print(f"Resaltado de origen: {dict(resaltes)}")


if __name__ == "__main__":
    main()
