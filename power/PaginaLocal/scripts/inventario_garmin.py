"""Qué hay de verdad en el Garmin del atleta. SÓLO MIRA: no borra nada.

## Para qué sirve

Cuando el reloj avisa de que "hay demasiadas sesiones de entrenamiento", la
pregunta que hay que contestar antes de tocar una línea de código es: **de
quién son**. Este guion la contesta con datos de la cuenta, separando lo que
puso esta app de lo que puso el atleta.

Existe porque esa pregunta se contestó primero por deducción y la deducción
era falsa. El síntoma -- entrenos viejos en el reloj que ya no se ven en la
app del móvil -- parecía un fallo de la limpieza: entrenos nuestros
descalendarizados a medias, o una copia que el reloj se quedaba. Medido contra
la cuenta real de un atleta el 2026-09-01:

    Biblioteca de Garmin ....... 59 entrenos
      de esta app .............. 7   (la semana vigente, correcta)
      del atleta ............... 52  (Garmin Coach: "Run Walk Run(R)",
                                      "Magic Half-Mile", "Entrenamiento:
                                      ejercicio", de enero a junio)
    Calendario ................. 7 entradas, las 7 nuestras

Ni un huérfano nuestro. El límite del reloj lo llenaban los entrenos del propio
atleta, que Garmin Connect **sí** sigue teniendo -- en Entrenamientos > Mis
entrenamientos, que no es la pantalla del calendario donde se había mirado.

## Por qué no borra

Borrarlos es decisión del atleta y sólo suya. La regla de esta app no cambia:
no se borra un id que no esté en nuestro registro de subidas
(services/garmin/upload_ledger.py), y ninguno de esos 52 lo está. Para lo
nuestro ya existe scripts/limpiar_garmin.py, que además pide confirmación.

    python scripts/inventario_garmin.py
    python scripts/inventario_garmin.py "Otro Atleta"
"""

from __future__ import annotations

import sys
from collections import Counter
from datetime import date
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

# La consola de Windows llega en cp1252 y revienta con cualquier carácter fuera
# de ella (mismo motivo que en limpiar_garmin.py).
for _flujo in (sys.stdout, sys.stderr):
    try:
        _flujo.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, ValueError):
        pass

from services.garmin import upload_ledger  # noqa: E402
from services.garmin.client import GarminConnectClient  # noqa: E402

# El techo de entrenos que suelen aceptar los relojes de Garmin. Es
# ORIENTATIVO y varía por modelo y versión de firmware; se enseña para poder
# decir "vas por 59 de unos 50" en vez de un número suelto que no significa
# nada. No se usa para decidir nada.
TECHO_ORIENTATIVO_DEL_RELOJ = 50


def _biblioteca_entera(cliente) -> list[dict]:
    """Todos los entrenos, no los primeros cien.

    `get_workouts()` trae 100 por defecto, y una cuenta real devolvió
    EXACTAMENTE 100 -- justo la pinta de una lista cortada. Dar eso por toda la
    biblioteca dejaría sin mirar precisamente los más antiguos.
    """
    todos, desde, pagina = [], 0, 100
    while True:
        lote = cliente.client.get_workouts(start=desde, end=pagina) or []
        todos.extend(lote)
        if len(lote) < pagina:
            return todos
        desde += pagina


def _calendario(cliente, meses: int = 6) -> list[dict]:
    """Los entrenos PROGRAMADOS de los últimos meses.

    Van aparte de la biblioteca a propósito: son las dos capas que se estaban
    confundiendo. La entrada del calendario es un puntero a la biblioteca, y
    borrar el entreno se lleva las dos -- pero verlas por separado es lo que
    permite comprobarlo en vez de suponerlo.

    El mes de Garmin es 0-indexado (enero = 0).
    """
    import garth

    hoy = date.today()
    salida, vistos = [], set()
    for atras in range(meses):
        total = hoy.year * 12 + (hoy.month - 1) - atras
        anio, mes = divmod(total, 12)
        try:
            r = garth.client.get("connectapi", f"/calendar-service/year/{anio}/month/{mes}", api=True)
            datos = r.json() if hasattr(r, "json") else r
        except Exception as exc:  # noqa: BLE001
            print(f"  (no se pudo leer {anio}-{mes + 1:02d}: {exc})")
            continue
        for item in datos.get("calendarItems") or []:
            if item.get("itemType") != "workout":
                continue
            # Garmin devuelve el mismo entreno en el mes que se pide y en el
            # siguiente cuando cae en la frontera; sin esto saldría dos veces.
            if item.get("id") in vistos:
                continue
            vistos.add(item.get("id"))
            salida.append(item)
    return salida


def _perfil_y_storage(nombre: str | None, directorio: str | None):
    """De dónde salen las credenciales y el registro de subidas.

    Dos sitios, porque hay dos despliegues. La PaginaLocal de un solo atleta
    los guarda en `data/storage/<perfil>/`; la versión multi-atleta le da a
    cada uno su carpeta (`AiService/_runtime/storage/u<id>/`) con su propio
    `profiles.json` dentro. Sin `--dir` este guion sólo veía la primera, que en
    una máquina de desarrollo suele estar vacía -- y fallaba con un 401 de
    Garmin que no explicaba nada de lo que estaba pasando.
    """
    import web_app.server as srv

    if directorio:
        storage = Path(directorio).expanduser().resolve()
        fichero = storage / "profiles.json"
        if not fichero.exists():
            print(f"No hay profiles.json en {storage}")
            return None, None
        import json

        perfiles = json.loads(fichero.read_text(encoding="utf-8"))
        if nombre and nombre in perfiles:
            return perfiles[nombre], storage
        if len(perfiles) != 1:
            print(f"{fichero} tiene {len(perfiles)} perfiles; di cuál: {sorted(perfiles)}")
            return None, None
        return next(iter(perfiles.values())), storage

    perfil = srv.load_profiles().get(nombre)
    if not perfil:
        print(f"No existe el perfil {nombre!r}.")
        return None, None
    return perfil, srv._profile_storage_dir(nombre)


def inventariar(nombre: str | None, directorio: str | None = None) -> int:
    import web_app.server as srv

    perfil, storage = _perfil_y_storage(nombre, directorio)
    if perfil is None:
        return 1
    if not perfil.get("garmin_email"):
        print("Ese perfil no tiene cuenta de Garmin guardada.")
        return 1

    registro = upload_ledger.cargar(storage)
    nuestros = {int(e["workout_id"]): e for e in registro}

    cliente = GarminConnectClient()
    cliente.connect(perfil["garmin_email"], srv.decrypt_secret(perfil["garmin_password"]))

    biblioteca = _biblioteca_entera(cliente)
    mios = [w for w in biblioteca if int(w.get("workoutId", 0)) in nuestros]
    suyos = [w for w in biblioteca if int(w.get("workoutId", 0)) not in nuestros]

    print(f"\n=== BIBLIOTECA DE ENTRENOS: {len(biblioteca)} ===")
    print(f"  de esta app ... {len(mios):>3}")
    print(f"  del atleta .... {len(suyos):>3}")
    if len(biblioteca) > TECHO_ORIENTATIVO_DEL_RELOJ:
        print(f"\n  El reloj suele aceptar ~{TECHO_ORIENTATIVO_DEL_RELOJ}. Con {len(biblioteca)} es"
              " normal que avise de que\n  ha alcanzado su límite -- y los que sobran no son"
              f" nuestros, son {len(suyos)}\n  del atleta. Se borran desde Garmin Connect >"
              " Entrenamiento > Entrenamientos.")

    print(f"\n--- De esta app ({len(mios)}) ---")
    for w in sorted(mios, key=lambda w: w.get("workoutName") or ""):
        e = nuestros[int(w["workoutId"])]
        print(f"  {w['workoutId']}  {e.get('date')}  {w.get('workoutName')}")

    print(f"\n--- Del atleta, INTOCABLES ({len(suyos)}) ---")
    por_nombre = Counter((w.get("workoutName") or "") for w in suyos)
    for nom, veces in por_nombre.most_common():
        print(f"  {veces:>3}x  {nom}")

    # Anotado en el registro pero ya no en Garmin: el atleta lo borró a mano.
    # No es un fallo, pero conviene verlo -- explica un "Subido" en la app que
    # el reloj no tiene.
    en_garmin = {int(w.get("workoutId", 0)) for w in biblioteca}
    fantasmas = [e for wid, e in nuestros.items() if wid not in en_garmin]
    if fantasmas:
        print(f"\n--- Anotados por la app pero YA NO en Garmin ({len(fantasmas)}) ---")
        print("    (los borró el atleta a mano; la próxima subida los repone)")
        for e in sorted(fantasmas, key=lambda e: e.get("date", "")):
            print(f"  {e['workout_id']}  {e.get('date')}  {e.get('name')}")

    programados = _calendario(cliente)
    nuestros_prog = [i for i in programados if int(i.get("workoutId") or 0) in nuestros]
    print(f"\n=== CALENDARIO (últimos 6 meses): {len(programados)} programados ===")
    print(f"  de esta app ... {len(nuestros_prog)}")
    for i in sorted(programados, key=lambda i: i.get("date") or ""):
        marca = "app  " if int(i.get("workoutId") or 0) in nuestros else "atleta"
        print(f"  {i.get('date')}  [{marca}]  {i.get('title')}")

    # Una entrada de calendario cuyo entreno ya no existe en la biblioteca
    # sería la prueba de que hace falta descalendarizar aparte de borrar. En la
    # cuenta real no apareció ninguna; si algún día aparece, esta línea es la
    # que lo dice.
    huerfanas = [i for i in programados if int(i.get("workoutId") or 0) not in en_garmin]
    if huerfanas:
        print(f"\n  ATENCIÓN: {len(huerfanas)} entrada(s) del calendario apuntan a un entreno")
        print("  que ya no está en la biblioteca. Eso SÍ sería un huérfano de programación.")
        for i in huerfanas:
            print(f"    {i.get('date')}  wid={i.get('workoutId')}  schedId={i.get('id')}  {i.get('title')}")

    print()
    return 0


if __name__ == "__main__":
    import web_app.server as _srv

    argv = sys.argv[1:]
    carpeta = None
    if "--dir" in argv:
        i = argv.index("--dir")
        carpeta = argv[i + 1] if i + 1 < len(argv) else None
        del argv[i:i + 2]
        if not carpeta:
            print("--dir necesita una ruta, p.ej. --dir AiService/_runtime/storage/u2")
            sys.exit(1)

    argumentos = [a for a in argv if not a.startswith("--")]
    objetivo = argumentos[0] if argumentos else None
    if objetivo is None and not carpeta:
        perfiles = list(_srv.load_profiles())
        if not perfiles:
            print("No hay perfiles guardados. Con la versión multi-atleta, apunta a su carpeta:"
                  "\n  python scripts/inventario_garmin.py --dir ../AiService/_runtime/storage/u2")
            sys.exit(1)
        objetivo = perfiles[0]

    print(f"Inventario de Garmin para {objetivo or carpeta!r} (sólo lectura, no borra nada).")
    sys.exit(inventariar(objetivo, carpeta))
