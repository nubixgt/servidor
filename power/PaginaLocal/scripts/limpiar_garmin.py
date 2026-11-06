"""Recoge los entrenos que esta app dejó sueltos en la biblioteca de Garmin.

Se necesita UNA vez, para la deuda anterior a v7. Hasta ahora la app subía y se
olvidaba del `workoutId`, así que cada generación dejaba una tanda nueva de
entrenos y ninguna se retiraba jamás. Seis generaciones de prueba en una tarde
dejaron ~40, y el reloj del atleta avisó de que había alcanzado su límite de
sesiones de entrenamiento. A partir de v7 eso ya no vuelve a pasar: cada subida
queda anotada (services/garmin/upload_ledger.py) y la app retira lo que caduca.

Hace dos cosas, y la primera importa tanto como la segunda:

  1. ADOPTA los entrenos del plan VIGENTE. Los que ya están en el reloj del
     atleta ahora mismo no se tocan, pero se anotan en el registro, para que la
     próxima vez que suba esa sesión REEMPLACE la que hay en vez de duplicarla.
  2. RETIRA los de generaciones anteriores, que ya no corresponden a ningún
     plan y sólo ocupan sitio.

Cómo decide que un entreno es nuestro -- y por qué se puede confiar en eso:

    Sólo se propone borrar un entreno cuyo nombre coincida EXACTAMENTE con el
    que este código habría generado para una sesión concreta de un plan
    archivado de este atleta: "{foco} - {fecha}" con el sufijo de franja. No se
    borra por parecido, ni por fecha, ni porque el nombre "suene" a nuestro.
    Un entreno del atleta -- una plantilla de Garmin Coach, algo que se creó a
    mano, una referencia -- no puede coincidir con esa cadena por accidente,
    porque incluye una fecha que sólo sale de su propio historial de planes.

    Aun así, no borra nada sin que se lo pidan: enseña la lista y para. Hay que
    volver a llamarlo con --borrar.

    python scripts/limpiar_garmin.py                      # sólo mira
    python scripts/limpiar_garmin.py --borrar             # retira
    python scripts/limpiar_garmin.py "Otro Atleta" --borrar

Hay un segundo modo, --por-patron, para la deuda que el emparejamiento estricto
no alcanza: los entrenos de generaciones cuyo plan ya no está archivado. Ese
modo se fía sólo de la FORMA del nombre ("algo - 2026-09-03"), que es evidencia
más débil, así que enseña la lista entera y hay que mirarla antes de confirmar.
"""

from __future__ import annotations

import json
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

# La consola de Windows llega en cp1252 y revienta con cualquier carácter fuera
# de ella. Una herramienta de mantenimiento que se cae por cómo imprime no
# mantiene nada.
for _flujo in (sys.stdout, sys.stderr):
    try:
        _flujo.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, ValueError):
        pass

from services.ai.langgraph.schemas.plan_outputs import (
    WeeklyPlanPayload,  # noqa: E402
    slot_suffix,  # noqa: E402
)
from services.garmin import upload_ledger  # noqa: E402
from services.garmin.client import GarminConnectClient  # noqa: E402
from services.garmin.garmin_upload_service import GarminUploadService  # noqa: E402

# Los entrenos subidos ANTES de que la etiqueta cambiara llevan "(AM)"/"(PM)"
# en el nombre; los de ahora llevan "(1ª)"/"(2ª)". El limpiador tiene que
# reconocer los dos o dejaria huerfano justo lo que vino a recoger.
_SUFIJOS_ANTIGUOS = {"am": " (AM)", "pm": " (PM)"}

# "Lo que sea - 2026-09-03" y "Lo que sea - 2026-09-03 (AM)": la forma exacta
# que produce workout_builder. Es una PISTA, no una prueba -- de ahí que sólo
# se use con --por-patron y siempre enseñando la lista.
_PATRON_NUESTRO = re.compile(r"^.+ - \d{4}-\d{2}-\d{2}(?: \((?:AM|PM)\))?$")


def _nombre_garmin(dia) -> str:
    """El mismo nombre que construye workout_builder para esta sesión.

    Si el constructor cambia el formato, esto deja de encontrar coincidencias y
    el script no propone nada -- que es el fallo correcto: no encontrar de
    menos, nunca borrar de más."""
    base = dia.focus or ("Brick" if dia.sport == "brick" else dia.sport.title())
    return f"{base} - {dia.date}{slot_suffix(dia.session_slot)}"[:100]


def _nombres_garmin(dia) -> set[str]:
    """Todos los nombres con los que ESTA sesion pudo haberse subido."""
    base = dia.focus or ("Brick" if dia.sport == "brick" else dia.sport.title())
    nombres = {_nombre_garmin(dia)}
    antiguo = _SUFIJOS_ANTIGUOS.get(dia.session_slot)
    if antiguo:
        nombres.add(f"{base} - {dia.date}{antiguo}"[:100])
    return nombres


def _plan(ruta: Path) -> WeeklyPlanPayload | None:
    if not ruta.exists():
        return None
    try:
        crudo = json.loads(ruta.read_text(encoding="utf-8"))
        return None if "error" in crudo else WeeklyPlanPayload.model_validate(crudo)
    except Exception:
        return None


def _sesiones(payload: WeeklyPlanPayload | None):
    return [d for w in (payload.weeks if payload else []) for d in w.days if not d.is_rest]


def _biblioteca_entera(cliente) -> list[dict]:
    """Todos los entrenos, no los primeros cien.

    `get_workouts()` acepta un desplazamiento y un limite, y por defecto trae
    100. La cuenta real devolvia EXACTAMENTE 100, que es justo la pinta de una
    lista cortada: dar eso por "toda la biblioteca" habria dejado sin mirar
    precisamente los mas antiguos, que son los que sobran."""
    todos, desde, pagina = [], 0, 100
    while True:
        lote = cliente.client.get_workouts(start=desde, end=pagina) or []
        todos.extend(lote)
        if len(lote) < pagina:
            return todos
        desde += pagina


def resolver_adopciones(candidatos):
    """Reparte los candidatos entre los que se adoptan y los que se retiran.

    Una sesión puede tener varias copias en la biblioteca: cada clic en "Subir"
    creaba una. **Cuando hay más de una no se adopta ninguna** -- se retiran
    todas y la sesión vuelve a contar como no subida.

    La tentación es quedarse con la más reciente (el id mayor). No sirve: el id
    mayor sólo dice cuál se creó después, no cuál corresponde al plan de ahora.
    Pasó de verdad -- al borrar a mano el entreno bueno de una sesión, esto
    adoptó una copia de una generación anterior y le grabó la huella del plan
    ACTUAL. El registro habría dicho "al día" con otro entreno en el reloj, que
    es exactamente el fallo que esta app existe para no cometer.

    Ante la duda, retirarlas todas y que el atleta la vuelva a subir: un clic
    de más contra un entreno equivocado en la muñeca.

    Devuelve (adoptar, retirar, copias_por_nombre).
    """
    copias: dict[str, list[int]] = {}
    for wid, n, _ in candidatos:
        copias.setdefault(n, []).append(wid)
    ambiguas = {n for n, ids in copias.items() if len(ids) > 1}
    return (
        [t for t in candidatos if t[1] not in ambiguas],
        [(wid, n) for wid, n, _ in candidatos if n in ambiguas],
        copias,
    )


def limpiar(nombre: str, borrar: bool, por_patron: bool = False) -> int:
    import web_app.server as srv

    perfil = srv.load_profiles().get(nombre)
    if not perfil:
        print(f"No existe el perfil {nombre!r}.")
        return 1
    storage = srv._profile_storage_dir(nombre)

    vigente = _plan(storage / "weekly_plan.json")
    if vigente is None:
        print("Este perfil no tiene un plan vigente; nada que adoptar.")
    nombres_vigentes = {n: d for d in _sesiones(vigente) for n in _nombres_garmin(d)}

    # Todos los planes que este atleta ha tenido alguna vez.
    nombres_historicos: set[str] = set()
    for hist in sorted((storage / "history").glob("*/weekly_plan.json")):
        nombres_historicos |= {n for d in _sesiones(_plan(hist)) for n in _nombres_garmin(d)}
    nombres_historicos -= set(nombres_vigentes)

    print(f"\nPlan vigente: {len(nombres_vigentes)} sesión(es).")
    print(f"Planes archivados: {len(nombres_historicos)} nombre(s) que ya no corresponden a nada.\n")

    cliente = GarminConnectClient()
    cliente.connect(perfil["garmin_email"], srv.decrypt_secret(perfil["garmin_password"]))
    servicio = GarminUploadService(cliente)
    biblioteca = _biblioteca_entera(cliente)
    print(f"Biblioteca de Garmin: {len(biblioteca)} entreno(s).\n")

    registro = upload_ledger.cargar(storage)
    ya_anotados = {int(e["workout_id"]) for e in registro}

    adoptar, retirar, sospechosos = [], [], []
    for w in biblioteca:
        wid, wnombre = int(w.get("workoutId")), (w.get("workoutName") or "")
        if wid in ya_anotados:
            continue
        if wnombre in nombres_vigentes:
            adoptar.append((wid, wnombre, nombres_vigentes[wnombre]))
        elif wnombre in nombres_historicos:
            retirar.append((wid, wnombre))
        elif por_patron and _PATRON_NUESTRO.match(wnombre):
            sospechosos.append((wid, wnombre))

    adoptar, dudosas, copias = resolver_adopciones(adoptar)
    retirar += dudosas
    ambiguas = {n for _, n in dudosas}
    if ambiguas:
        print(f"\n--- Con varias copias, no se adopta ninguna: {len(ambiguas)} sesión(es) ---")
        for n in sorted(ambiguas):
            print(f"  {n}  ({len(copias[n])} copias) -> vuelve a subirla desde la app")

    print(f"--- Adoptar (se QUEDAN en tu Garmin, sólo se anotan): {len(adoptar)} ---")
    for wid, n, _ in sorted(adoptar, key=lambda t: t[1]):
        print(f"  {wid}  {n}")
    print(f"\n--- Retirar (de planes anteriores o duplicados): {len(retirar)} ---")
    for wid, n in sorted(retirar, key=lambda t: t[1]):
        print(f"  {wid}  {n}")

    if por_patron:
        print(f"\n--- Por la FORMA del nombre, no por coincidencia con un plan: {len(sospechosos)} ---")
        print("    Su plan ya no está archivado, así que esto es una pista y no una prueba.")
        print("    MÍRALA ENTERA: si reconoces alguno como tuyo, no uses este modo.")
        for wid, n in sorted(sospechosos, key=lambda t: t[1]):
            print(f"  {wid}  {n}")
        retirar += sospechosos

    # Los ya anotados no son "intactos": son nuestros y estan controlados. Un
    # numero que los mezcla con los entrenos del atleta hace pensar que hay mas
    # cosas suyas de las que hay, en la unica pantalla donde eso importa.
    controlados = sum(1 for w in biblioteca if int(w.get("workoutId")) in ya_anotados)
    intactos = len(biblioteca) - len(adoptar) - len(retirar) - controlados
    if controlados:
        print(f"\n--- Ya anotados de antes (nuestros, bajo control): {controlados} ---")
    print(f"\n--- Ni se tocan ni se miran: {intactos} ---")
    print("    (todo lo que no coincide EXACTAMENTE con una sesión de un plan tuyo:")
    print("     tus plantillas, lo de Garmin Coach, tus referencias, lo que te creaste a mano)")

    if not borrar:
        extra = " --por-patron" if por_patron else ""
        print("\nEsto ha sido sólo una mirada. Para que actúe:")
        print(f'    python scripts/limpiar_garmin.py "{nombre}"{extra} --borrar\n')
        return 0

    for wid, n, dia in adoptar:
        registro = upload_ledger.anotar(
            registro, fecha=dia.date, franja=dia.session_slot, workout_id=wid,
            nombre=dia.focus or dia.sport, deporte=dia.sport,
            huella_sesion=upload_ledger.huella(dia),
            # Emparejado por el nombre, no devuelto por Garmin al crearlo.
            # Queda marcado para que el registro diga siempre de donde salio.
            adoptada=True,
        )
    upload_ledger.guardar(storage, registro)
    print(f"\nAnotados {len(adoptar)} entrenos del plan vigente en el registro.")

    fallos = 0
    for wid, n in retirar:
        if not servicio.delete_workout(wid):
            fallos += 1
            print(f"  no se pudo retirar {wid} ({n})")
    print(f"Retirados {len(retirar) - fallos} de {len(retirar)}.")
    print("\nA partir de aquí la app se encarga sola: cada subida reemplaza la anterior")
    print(f"y retira lo que lleva más de {upload_ledger.DIAS_QUE_SE_CONSERVAN} días pasado.\n")
    return 1 if fallos else 0


if __name__ == "__main__":
    banderas = {"--borrar", "--por-patron"}
    args = [a for a in sys.argv[1:] if a not in banderas]
    sys.exit(limpiar(args[0] if args else "Jose Ines Castillo",
                     "--borrar" in sys.argv, "--por-patron" in sys.argv))
