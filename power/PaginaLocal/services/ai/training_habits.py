"""Lo que este atleta HACE, no lo que puede hacer.

## El hueco que llena

El reparto de los expertos dejaba esto sin dueño. El de actividad tiene
prohibido hablar de carga global ("es del experto de métricas") y prohibido
proponer horarios ("es del planificador"); el de métricas mira ACWR y CTL, que
son un número por día sin forma semanal. **La forma de una semana normal — qué
días entrena, de qué, cuántas calidades y en qué día caen — no la miraba
nadie.**

Se vio en un plan real: el atleta hace trabajo de calidad todos los miércoles
desde hace meses, y la primera semana generada fue Z1/Z2 de principio a fin. No
fue un fallo del modelo: nadie le dijo que existiera ese miércoles.

## Por qué esto y no `track_record.py`

`track_record` guarda **lo que la app recetó y cuánto se cumplió**. Es el
expediente de nuestra relación con el atleta, y para un atleta nuevo está
vacío. Esto otro es su vida anterior a nosotros, leída de Garmin: la única base
que hay el primer día, y la que dice si un bloque declarado es un hábito o un
deseo.

## Qué es y qué NO es

**Describe, no prescribe.** No dice "entrena los miércoles" ni "quita el martes
de bici". Dice cuántos martes de bici hubo de doce. Igual que `load_state.py`,
no fija ningún umbral propio y no bloquea nada: decidir qué significa es
trabajo del entrenador, y un atleta que quiere CAMBIAR su hábito es un caso
perfectamente legítimo que este módulo no debe estorbar.

Por eso el contraste con lo declarado se reporta como dos números uno al lado
del otro, nunca como un juicio. "Declaró bici los martes; entrenó bici 4 de 12
martes" deja las dos lecturas abiertas -- el bloque es una ficción, o es
justamente el hábito que quiere construir -- y sólo el atleta y su entrenador
saben cuál es.
"""

from __future__ import annotations

import statistics
from collections import defaultdict
from dataclasses import dataclass, field
from datetime import date, datetime, timedelta
from typing import Any

from services.ai.intensity_distribution import es_sesion_dura, referencias_de_prescripcion
from services.ai.load_state import _canonical_sport
from services.ai.prescription import build_prescription

# Doce semanas: suficiente para que un hábito semanal se repita muchas veces y
# corto para que un cambio de temporada no quede enterrado bajo lo viejo.
VENTANA_SEMANAS = 12

# Garmin ya clasifica la intensidad por sí mismo (`vigorous_intensity_minutes`,
# de sus zonas y su algoritmo). Usar eso en vez de inventar aquí un umbral de
# pulso propio es la misma regla que sigue `load_state.py`: cada palabra de
# estado es una que asignó Garmin, no una que nos inventamos.
MIN_MINUTOS_VIGOROSOS = 8

DIAS_ES = ["lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo"]

_NOMBRE_DEPORTE = {
    "run": "correr", "bike": "bici", "swim": "natación",
    "strength": "fuerza", "walk": "caminar", "other": "otro",
    "trail": "correr",   # trail es terreno, no deporte (ver SPORT_LABEL_TO_KEY)
}

# Las etiquetas que el atleta ve en su perfil ("Bici") NO son los tipos que
# devuelve Garmin ("cycling"), y `_canonical_sport` sólo entiende los segundos.
# Mezclarlos hacía que todo lo declarado cayera en "otro" y el contraste saliera
# vacío, en silencio -- exactamente la clase de fallo que este proyecto persigue.
#
# Es una copia de `SPORT_LABEL_TO_KEY` de web_app/server.py, que no se puede
# importar desde aquí: server importa services, y además su import crea
# directorios y corre una migración. `tests/test_training_habits.py` falla si
# las dos tablas divergen.
_ETIQUETA_PERFIL = {
    "correr": "run", "trail": "trail", "bici": "bike",
    "nadar": "swim", "fuerza": "strength", "cualquiera": None,
}


@dataclass
class HabitoDeporte:
    deporte: str
    sesiones_por_semana: float = 0.0
    horas_por_semana: float = 0.0
    duracion_tipica_min: int = 0
    duracion_maxima_min: int = 0
    semanas_con_actividad: int = 0
    # DISTANCIA. Hasta aquí este bloque medía sólo tiempo, y el atleta preguntó
    # al entrenador cuántos kilómetros había corrido el mes pasado: no podía
    # saberlo, porque el dato no le llegaba a nadie. Sale de
    # `ActivitySummary.distance`, que Garmin da en METROS.
    #
    # Cero en fuerza, movilidad y todo lo que no se mide en distancia; el
    # render omite el kilometraje cuando es cero en vez de escribir "0.0 km",
    # que se leería como que no entrenó.
    km_por_semana: float = 0.0
    km_ultimos_30d: float = 0.0
    km_total: float = 0.0


@dataclass
class TrainingHabits:
    desde: str = ""
    hasta: str = ""
    semanas: int = 0
    actividades: int = 0
    por_deporte: list[HabitoDeporte] = field(default_factory=list)
    # día -> {"semanas": n, "deportes": {deporte: veces}, "vigorosas": n}
    ritmo_semanal: dict[str, dict[str, Any]] = field(default_factory=dict)
    vigorosas_por_semana: float = 0.0
    dias_de_calidad: list[str] = field(default_factory=list)
    # Las sesiones cuya estructura se pudo leer de los tramos, de nueva a
    # vieja. Vacía no significa que el atleta no haga series: significa que no
    # las sabemos ver en su reloj, y el bloque lo dice con esas palabras.
    estructuradas: list[SesionEstructurada] = field(default_factory=list)
    contraste_declarado: list[str] = field(default_factory=list)
    # En cuántas de las semanas de la ventana hay alguna actividad: sin eso, una cuenta Garmin recién
    # estrenada o un mes de lesión saldrían como «no hace lo que marcó».
    semanas_con_actividad: int = 0
    avisos: list[str] = field(default_factory=list)


def _fecha_de(actividad: Any) -> date | None:
    crudo = getattr(actividad, "start_time", None)
    if not crudo:
        return None
    texto = str(crudo).strip().replace("T", " ")
    for formato in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%d %H:%M", "%Y-%m-%d"):
        try:
            return datetime.strptime(texto[:len(formato) + 2].strip(), formato).date()
        except ValueError:
            continue
    try:
        return datetime.fromisoformat(texto[:19]).date()
    except ValueError:
        return None


def _minutos(actividad: Any) -> float:
    resumen = getattr(actividad, "summary", None)
    segundos = getattr(resumen, "duration", None) if resumen else None
    try:
        return max(0.0, float(segundos or 0) / 60.0)
    except (TypeError, ValueError):
        return 0.0


def _km(actividad: Any) -> float:
    """Kilómetros de la actividad. Garmin la da en METROS.

    Misma conversión que `services/ai/adherence.py` (`distance / 1000.0`), a
    propósito: dos formas de contar los mismos kilómetros acabarían dando dos
    cifras distintas en dos sitios de la app.
    """
    resumen = getattr(actividad, "summary", None)
    metros = getattr(resumen, "distance", None) if resumen else None
    try:
        return max(0.0, float(metros or 0) / 1000.0)
    except (TypeError, ValueError):
        return 0.0


def _es_vigorosa(actividad: Any) -> bool:
    """Si Garmin le contó minutos de intensidad vigorosa.

    No se define un umbral de pulso propio a proposito: el atleta ve estos
    minutos en su reloj, y una calidad que la app cuenta y el reloj no crea
    exactamente la clase de discrepancia que ya costo una explicacion imposible
    con los dos ACWR.
    """
    resumen = getattr(actividad, "summary", None)
    if resumen is None:
        return False
    try:
        return float(getattr(resumen, "vigorous_intensity_minutes", 0) or 0) >= MIN_MINUTOS_VIGOROSOS
    except (TypeError, ValueError):
        return False


def _hm(minutos: float) -> str:
    total = int(round(minutos))
    return f"{total // 60}h{total % 60:02d}" if total >= 60 else f"{total}'"


# --- la estructura de una sesión, leída de sus tramos -----------------------
#
# ## Por qué esto es código y no una instrucción al resumidor
#
# El resumidor de actividad es `gemini-3.1-flash-lite` comprimiendo 40:1, y lo
# que se le pide es «deep dives ONLY for key sessions». Pedirle que además
# conserve siempre la estructura de las series es pedirle que no comprima justo
# donde más material hay. Una regla que el modelo puede desobedecer no es una
# garantía — la misma razón por la que `_soltar_zona_en_rangos` vive en el
# validador y no en el prompt.
#
# Y el coste de que se pierda está medido. El 2026-09-10 el planificador de
# temporada escribió sobre este atleta: *"el análisis de laps revela que en
# realidad son rodajes sostenidos sin estructura de intervalos"*, y aplazó todo
# el trabajo de VO2max a la semana 21 de 24. El día anterior el atleta había
# hecho **8 × 2' a 4:45-5:39/km con picos de 188 ppm**. El razonamiento era
# correcto; los datos que lo alimentaban, no.
#
# ## Qué cuenta como estructura, y qué no
#
# Garmin etiqueta cada tramo (`intensityType`): WARMUP, ACTIVE, INTERVAL,
# RECOVERY, REST, COOLDOWN. Una sesión es de SERIES cuando hay al menos dos
# tramos de trabajo separados por recuperación. Un rodaje con vuelta
# automática cada kilómetro tiene ocho tramos ACTIVE seguidos y ninguno de
# recuperación: no es una serie, y aquí no lo parece.
#
# **Se abstiene en cuanto duda.** Sin etiquetas de intensidad no se adivina la
# estructura por el ritmo: decirle al entrenador que el atleta hace series
# cuando no las hace es el mismo error que no verlas, en la otra dirección, y
# éste además sube la carga.
_TRAMOS_DE_TRABAJO = {"ACTIVE", "INTERVAL"}
_TRAMOS_DE_RECUPERACION = {"RECOVERY", "REST"}


@dataclass
class SesionEstructurada:
    fecha: str
    deporte: str
    nombre: str
    repeticiones: int
    trabajo_s: int
    recuperacion_s: int
    ritmo: str
    fc_pico: int


def _intensidad(tramo: Any) -> str:
    return str((tramo or {}).get("intensity") or "").strip().upper()


def _ritmo_de(deporte: str, tramos: list[dict]) -> str:
    """El rango de ritmo de los tramos de trabajo, en la unidad de su deporte.

    Corriendo se lee en min/km, nadando en min/100 m y en bici en km/h, que es
    como el atleta y el entrenador los dicen. `averageSpeed_ms` viene en metros
    por segundo -- ver `data_extractor.get_activity_laps`.
    """
    velocidades = [v for t in tramos if (v := t.get("averageSpeed_ms")) and float(v) > 0]
    if not velocidades:
        return ""
    rapida, lenta = max(velocidades), min(velocidades)

    def reloj(segundos: float) -> str:
        return f"{int(segundos) // 60}:{int(round(segundos)) % 60:02d}"

    if deporte == "bike":
        return (f"{lenta * 3.6:.1f}-{rapida * 3.6:.1f} km/h"
                if lenta != rapida else f"{rapida * 3.6:.1f} km/h")
    metros = 100 if deporte == "swim" else 1000
    unidad = "/100m" if deporte == "swim" else "/km"
    veloz, lento = metros / rapida, metros / lenta
    return (f"{reloj(veloz)}-{reloj(lento)}{unidad}"
            if int(veloz) != int(lento) else f"{reloj(veloz)}{unidad}")


def leer_estructura(actividad: Any, fecha: date, deporte: str) -> SesionEstructurada | None:
    """La forma de la sesión si de verdad la tiene; `None` en cuanto dude."""
    tramos = getattr(actividad, "laps", None)
    if not isinstance(tramos, list) or len(tramos) < 3:
        return None
    # Sin las pulsaciones del botón, con el MISMO criterio que el resumidor
    # (payload_pruning._lap_is_artifact). La sesión del 2026-09-09 de la atleta
    # 3 fueron 7 × 400 m, y este bloque decía "10 x 2'08" a 4:56-21:04/km":
    # contaba como repeticiones tres pulsaciones de <5 s. La semana dijo que
    # había hecho 10, y el 21:04/km ya avisaba de que algo no era una serie.
    from services.garmin.payload_pruning import _lap_is_artifact

    tramos = [t for t in tramos if isinstance(t, dict) and not _lap_is_artifact(t)]
    trabajo = [t for t in tramos if _intensidad(t) in _TRAMOS_DE_TRABAJO]
    descanso = [t for t in tramos if _intensidad(t) in _TRAMOS_DE_RECUPERACION]
    if len(trabajo) < 2 or not descanso:
        return None

    def segundos(lista: list[dict]) -> int:
        valores = [float(t.get("duration_s") or 0) for t in lista]
        valores = [v for v in valores if v > 0]
        return int(round(statistics.median(valores))) if valores else 0

    picos = [int(t["maxHR_bpm"]) for t in trabajo if t.get("maxHR_bpm")]
    return SesionEstructurada(
        fecha=fecha.isoformat(),
        deporte=_NOMBRE_DEPORTE.get(deporte, deporte),
        nombre=str(getattr(actividad, "activity_name", "") or "").strip(),
        repeticiones=len(trabajo),
        trabajo_s=segundos(trabajo),
        recuperacion_s=segundos(descanso),
        ritmo=_ritmo_de(deporte, trabajo),
        fc_pico=max(picos) if picos else 0,
    )


def _serie(s: SesionEstructurada) -> str:
    """«8 × 2' a 4:45-5:39/km, rec 2' · pico 188 ppm»."""
    def dur(segundos: int) -> str:
        if not segundos:
            return "?"
        return f"{segundos // 60}'{segundos % 60:02d}\"" if segundos % 60 else f"{segundos // 60}'"

    piezas = [f"{s.repeticiones} x {dur(s.trabajo_s)}"]
    if s.ritmo:
        piezas.append(f"a {s.ritmo}")
    if s.recuperacion_s:
        piezas.append(f"rec {dur(s.recuperacion_s)}")
    texto = " ".join(piezas)
    if s.fc_pico:
        texto += f" · pico {s.fc_pico} ppm"
    return texto


def build_training_habits(
    garmin_data: Any,
    perfil: dict[str, Any] | None = None,
    hoy: date | None = None,
    semanas: int = VENTANA_SEMANAS,
    dias_de_datos: int | None = None,
) -> TrainingHabits:
    """El retrato de la semana habitual, leído de las actividades reales.

    `dias_de_datos` es cuántos días de actividades se le PIDIERON a Garmin. Es
    obligatorio en la práctica aunque el parámetro sea opcional, y la razón es
    un fallo real:

    La generación de semana pide 30 días (`WEEK_WINDOW.activities_days`) y esto
    calculaba medianas sobre 12 semanas. Las siete semanas y media que nunca se
    bajaron entraban como ceros, así que un atleta que entrenó los 31 días de
    agosto recibió un bloque que decía "0.0 sesiones por semana" de todo y
    "entrenó en 5 de 12 semanas". No es que no entrenara: es que no miramos.

    Es EXACTAMENTE el fallo que `services/garmin/extraction_windows.py` existe
    para hacer imposible -- su propia docstring lo cuenta de
    `build_volume_baseline` -- y su test guardián no lo cazó porque sólo conoce
    las ventanas de carga. Ahora la ventana de cálculo se recorta a la de
    extracción, y el bloque dice cuántas semanas está mirando de verdad.
    """
    hoy = hoy or date.today()
    if dias_de_datos is not None:
        # Nunca se afirma sobre semanas que no se pidieron. Se redondea hacia
        # abajo: media semana de datos no es una semana de hábito.
        semanas = max(1, min(semanas, int(dias_de_datos) // 7))
    desde = hoy - timedelta(weeks=semanas)
    h = TrainingHabits(desde=desde.isoformat(), hasta=hoy.isoformat(), semanas=semanas)

    crudas = getattr(garmin_data, "all_activities", None) or getattr(
        garmin_data, "recent_activities", None) or []
    dentro = []
    for a in crudas:
        f = _fecha_de(a)
        if f and desde <= f <= hoy and _minutos(a) > 0:
            dentro.append((f, a))

    h.actividades = len(dentro)
    h.semanas_con_actividad = len({min((f - desde).days // 7, semanas - 1) for f, _ in dentro})
    if not dentro:
        h.avisos.append(
            "Garmin no devolvió actividades en esta ventana: no hay hábito que leer, "
            "y el plan se apoya sólo en lo que el atleta declaró."
        )
        return h

    # --- por deporte ---
    por_deporte_semana: dict[str, dict[int, list[float]]] = defaultdict(lambda: defaultdict(list))
    # Kilómetros aparte: total de la ventana y de los últimos 30 días. Los 30
    # días son los que contestan "¿cuánto corrí el mes pasado?", que es como el
    # atleta lo pregunta -- un promedio semanal no responde a eso.
    km_total: dict[str, float] = defaultdict(float)
    km_30d: dict[str, float] = defaultdict(float)
    hace_30 = hoy - timedelta(days=30)
    for f, a in dentro:
        deporte = _canonical_sport(getattr(a, "activity_type", None))
        semana = (f - desde).days // 7
        por_deporte_semana[deporte][semana].append(_minutos(a))
        km = _km(a)
        km_total[deporte] += km
        if f >= hace_30:
            km_30d[deporte] += km

    for deporte, semanas_dic in por_deporte_semana.items():
        # Las semanas SIN ese deporte cuentan como cero: si no, un atleta que
        # nada una vez al mes saldria como "1 sesion por semana de natacion".
        conteos = [len(semanas_dic.get(i, [])) for i in range(semanas)]
        minutos = [sum(semanas_dic.get(i, [])) for i in range(semanas)]
        todas = [m for lista in semanas_dic.values() for m in lista]
        # PROMEDIO, no mediana, para el ritmo semanal.
        #
        # La mediana con las semanas en blanco en cero da 0.0 en cuanto el
        # deporte se hace en menos de la mitad de las semanas -- que es el caso
        # normal de la natacion o la bici de un corredor. El prompt acababa
        # diciendo "natacion 0.0 ses/sem" cuatro lineas por encima de "carga
        # cronica 5.0 h/sem": dos numeros que se contradicen dentro del mismo
        # texto, y el modelo tiene que elegir uno sin saber cual mentimos.
        #
        # El promedio no puede ser cero cuando hubo sesiones, y la dispersion
        # -- que era lo que la mediana protegia -- se sigue diciendo, y mejor,
        # en `semanas_con_actividad`: "0.5 ses/sem, entreno en 2 de 4 semanas"
        # se lee sin ambiguedad. La duracion tipica SI se queda en mediana:
        # ahi solo entran sesiones reales, nunca ceros, y una tirada larga
        # suelta no debe mover lo que es una sesion normal.
        h.por_deporte.append(HabitoDeporte(
            deporte=_NOMBRE_DEPORTE.get(deporte, deporte),
            sesiones_por_semana=round(sum(conteos) / semanas, 1),
            horas_por_semana=round(sum(minutos) / semanas / 60.0, 1),
            duracion_tipica_min=int(round(statistics.median(todas))) if todas else 0,
            duracion_maxima_min=int(round(max(todas))) if todas else 0,
            semanas_con_actividad=sum(1 for c in conteos if c),
            km_por_semana=round(km_total[deporte] / semanas, 1),
            km_ultimos_30d=round(km_30d[deporte], 1),
            km_total=round(km_total[deporte], 1),
        ))
    h.por_deporte.sort(key=lambda d: -d.horas_por_semana)

    # --- ritmo semanal ---
    # Por deporte se guarda el conjunto de SEMANAS, no el numero de sesiones:
    # dos carreras el mismo lunes son un lunes de correr, no dos. Contando
    # sesiones salia "declaro correr los lunes: 24 de 12 semanas", que ademas
    # de falso es un numero imposible en mitad de un prompt.
    ritmo: dict[str, dict[str, Any]] = {
        d: {"semanas": set(), "deportes": defaultdict(set), "vigorosas": set()} for d in DIAS_ES
    }
    vigorosas_por_semana: dict[int, int] = defaultdict(int)
    # "Vigorosa" la decide la clasificación de la app -- pulso anclado a sus
    # umbrales y, en carrera, el ritmo en llano -- y sólo cuando no puede leer la
    # sesión, los minutos vigorosos de Garmin. Con sólo lo segundo, un atleta
    # cuyo pulso llega tarde no tenía "días de calidad" aunque hiciera series
    # cada semana. Ver intensity_distribution.es_sesion_dura.
    zonas = getattr(garmin_data, "garmin_hr_zones", None)
    try:
        referencias = referencias_de_prescripcion(build_prescription(garmin_data, perfil))
    except Exception:  # noqa: BLE001 - sin referencias vota sólo el pulso
        referencias = None

    def es_calidad(actividad: Any) -> bool:
        veredicto = es_sesion_dura(actividad, zonas, referencias)
        return _es_vigorosa(actividad) if veredicto is None else veredicto

    for f, a in dentro:
        dia = DIAS_ES[f.weekday()]
        semana = (f - desde).days // 7
        ritmo[dia]["semanas"].add(semana)
        ritmo[dia]["deportes"][_NOMBRE_DEPORTE.get(
            _canonical_sport(getattr(a, "activity_type", None)), "otro")].add(semana)
        if es_calidad(a):
            ritmo[dia]["vigorosas"].add(semana)
            vigorosas_por_semana[semana] += 1

    for dia, datos in ritmo.items():
        h.ritmo_semanal[dia] = {
            "semanas": len(datos["semanas"]),
            "deportes": {
                k: len(v) for k, v in sorted(datos["deportes"].items(), key=lambda kv: -len(kv[1]))
            },
            "vigorosas": len(datos["vigorosas"]),
        }

    h.vigorosas_por_semana = round(
        sum(vigorosas_por_semana.get(i, 0) for i in range(semanas)) / semanas, 1)
    # Un dia es "de calidad" si concentro trabajo vigoroso en al menos un tercio
    # de las semanas. No es un umbral fisiologico: es cuando deja de ser azar.
    #
    # Ordenados por cuantas semanas, y con el numero al lado cuando se
    # renderiza. Una lista pelada de nombres se llevo por delante un caso real:
    # el atleta hace calidad los miercoles y el bloque dijo "se concentra en:
    # viernes, domingo" porque esos dos tenian una semana mas. Con los numeros
    # delante, un 3 y un 4 se leen como lo que son -- casi lo mismo -- y quien
    # decide es el entrenador, que es toda la premisa de este modulo.
    h.dias_de_calidad = sorted(
        (d for d in DIAS_ES if h.ritmo_semanal[d]["vigorosas"] >= max(2, semanas // 3)),
        key=lambda d: -h.ritmo_semanal[d]["vigorosas"],
    )

    # --- la forma de las sesiones de calidad ---
    #
    # De nueva a vieja y acotada: lo que el entrenador necesita es reconocer
    # que este atleta SABE hacer series y a qué ritmos las hace, no un
    # inventario. Doce cubre de sobra un bloque de entrenamiento.
    estructuradas = []
    for f, a in sorted(dentro, key=lambda par: par[0], reverse=True):
        s = leer_estructura(a, f, _canonical_sport(getattr(a, "activity_type", None)))
        if s is not None:
            estructuradas.append(s)
    h.estructuradas = estructuradas[:12]

    # --- contraste con lo declarado ---
    #
    # LO DECLARADO ES LA REJILLA CUANDO EXISTE. Las franjas de reloj son lo que
    # el atleta dijo ANTES de la v1.1, y no se borran nunca -- pero contrastar
    # contra ellas cuando ya ha contestado la rejilla le pone en la boca lo que
    # ya no dice. El atleta 2 declaraba Fuerza los martes en sus franjas y hoy
    # pide Nadar: el bloque habria dicho "declaro Fuerza los martes" delante
    # del modelo, sobre una peticion que el mismo cambio.
    for dia, deportes_declarados in _declarado_por_dia(perfil).items():
        reales = h.ritmo_semanal.get(dia, {}).get("deportes", {})
        for deporte in sorted(deportes_declarados):
            veces = reales.get(deporte, 0)
            h.contraste_declarado.append(
                f"declaró {deporte} los {dia}: lo hizo en {veces} de {semanas} semanas"
            )

    return h


def _declarado_por_dia(perfil) -> dict[str, set]:
    """Qué deporte declaró el atleta para cada día, en nombres para leer.

    Prefiere la rejilla (`sport_days`, v1.1) y cae a las franjas de reloj sólo
    si todavía no la ha contestado. Los dos estados cuentan: un `posible` sigue
    siendo algo que el atleta dice que hace ese día, y el contraste existe para
    enseñar la distancia entre lo que dice y lo que Garmin registró.
    """
    perfil = perfil or {}

    def nombre(etiqueta) -> str:
        clave = _ETIQUETA_PERFIL.get(str(etiqueta).strip().lower(), "")
        return _NOMBRE_DEPORTE.get(clave, clave) if clave else ""

    salida: dict[str, set] = {}
    rejilla = perfil.get("sport_days") or {}
    if isinstance(rejilla, dict) and any(isinstance(f, dict) and f for f in rejilla.values()):
        for deporte, fila in rejilla.items():
            if not isinstance(fila, dict):
                continue
            for dia_perfil, estado in fila.items():
                dia = _normalizar_dia(dia_perfil)
                if not dia or estado not in ("fijo", "posible"):
                    continue
                if (n := nombre(deporte)):
                    salida.setdefault(dia, set()).add(n)
        return salida

    for dia_perfil, lista in (perfil.get("day_time_blocks") or {}).items():
        dia = _normalizar_dia(dia_perfil)
        if not dia or not lista:
            continue
        for bloque in lista:
            for etiqueta in (bloque.get("sports") or []):
                if (n := nombre(etiqueta)):
                    salida.setdefault(dia, set()).add(n)
    return salida


# Un «sí o sí» que el atleta hizo como mucho UNA vez en las últimas semanas (decisión del responsable,
# 2026-10-23: «que la app le alerte, sin costo de IA, cuando sea 1 de 4»). Fernando marcó 13 «sí o sí»
# sin día de descanso; su log decía «declaró natación los jueves: lo hizo en 0 de 4 semanas» y «correr los
# viernes: 0 de 4». Cada «sí o sí» va al plan en su día (regla 4), así que su semana salía con 14-17
# sesiones, la carga extrema y una segunda decisión pagada todas las semanas (~0,15 USD), y él cumplía
# 3,7-8,8 h de 8,5-13 prescritas. Esto no cambia su tabla: le enseña la distancia y decide él.
POCO_HECHO_MAX = 1
# Con menos semanas con actividad, la cuenta no dice nada de su rutina (Garmin recién conectado, una lesión).
SEMANAS_CON_ACTIVIDAD_MIN = 3


def fijos_poco_hechos(h: TrainingHabits, perfil) -> list[dict[str, Any]]:
    """Los «sí o sí» de su tabla que hizo como mucho `POCO_HECHO_MAX` veces en la ventana.

    Sólo la rejilla (`sport_days`) y sólo `fijo`: un `posible` ya es «cuando convenga». Devuelve la
    fila y el día COMO ESTÁN EN SU PERFIL («Nadar», «Jueves») para que la pantalla marque esa casilla,
    con cuántas veces lo hizo y de cuántas semanas. Vacía si no hay historial suficiente.
    """
    if h.semanas < 4 or h.semanas_con_actividad < min(SEMANAS_CON_ACTIVIDAD_MIN, h.semanas):
        return []
    rejilla = (perfil or {}).get("sport_days") or {}
    if not isinstance(rejilla, dict):
        return []
    salida = []
    for deporte, fila in rejilla.items():
        if not isinstance(fila, dict):
            continue
        clave = _ETIQUETA_PERFIL.get(str(deporte).strip().lower(), "")
        nombre = _NOMBRE_DEPORTE.get(clave, clave) if clave else ""
        if not nombre:
            continue
        for dia_perfil, estado in fila.items():
            dia = _normalizar_dia(dia_perfil)
            if estado != "fijo" or not dia:
                continue
            veces = h.ritmo_semanal.get(dia, {}).get("deportes", {}).get(nombre, 0)
            if veces <= POCO_HECHO_MAX:
                salida.append({"deporte": deporte, "dia": dia_perfil, "veces": min(veces, h.semanas),
                               "semanas": h.semanas})
    orden = {d: i for i, d in enumerate(DIAS_ES)}
    return sorted(salida, key=lambda x: (orden.get(_normalizar_dia(x["dia"]) or "", 9), str(x["deporte"])))


def _normalizar_dia(nombre: str) -> str | None:
    limpio = (nombre or "").strip().lower()
    equivalencias = {"miercoles": "miércoles", "sabado": "sábado"}
    limpio = equivalencias.get(limpio, limpio)
    return limpio if limpio in DIAS_ES else None


def render_training_habits(h: TrainingHabits) -> str:
    """El bloque compacto que se le entrega al agente."""
    if h.avisos and not h.actividades:
        return "[HÁBITOS DE ENTRENAMIENTO]\n" + "\n".join(f"- {a}" for a in h.avisos)

    lineas = [
        "[HÁBITOS DE ENTRENAMIENTO — lo que este atleta hace de verdad]",
        f"Ventana: {h.semanas} semanas ({h.desde} a {h.hasta}) · {h.actividades} actividades",
        "",
        "Por deporte (promedio semanal, contando las semanas en blanco):",
    ]
    for d in h.por_deporte:
        # El kilometraje sólo se escribe si lo hay. Un "0.0 km" en fuerza o en
        # movilidad se leería como que no entrenó, cuando es que ese deporte no
        # se mide en distancia.
        km = ""
        if d.km_total > 0:
            km = (f" · {d.km_por_semana} km/sem"
                  f" · {d.km_ultimos_30d} km en los últimos 30 días")
        lineas.append(
            f"  {d.deporte:<10} {d.sesiones_por_semana:>4} ses/sem · {d.horas_por_semana:>4}h/sem"
            f" · sesión típica {_hm(d.duracion_tipica_min)}"
            f" · más larga {_hm(d.duracion_maxima_min)}"
            f" · entrenó en {d.semanas_con_actividad}/{h.semanas} semanas"
            f"{km}"
        )

    lineas += ["", f"Ritmo semanal (en cuántas de las {h.semanas} semanas entrenó cada día):"]
    for dia in DIAS_ES:
        datos = h.ritmo_semanal.get(dia) or {}
        n = datos.get("semanas", 0)
        deportes = ", ".join(
            f"{k} {v}/{h.semanas}" for k, v in (datos.get("deportes") or {}).items())
        vig = datos.get("vigorosas", 0)
        marca = f" · {vig} con trabajo vigoroso" if vig else ""
        lineas.append(f"  {dia:<10} {n:>2}/{h.semanas}  {deportes or '—'}{marca}")

    lineas += [
        "",
        f"Trabajo vigoroso: {h.vigorosas_por_semana} sesiones por semana (promedio), de umbral o más "
        f"según su pulso anclado a sus umbrales y, en carrera, su ritmo en llano; donde eso no se puede "
        f"leer, según los minutos vigorosos que cuenta Garmin.",
    ]
    if h.dias_de_calidad:
        # Con el numero al lado, no como una lista de ganadores. Un 4 y un 3
        # son casi lo mismo, y una lista pelada hacia desaparecer el tercero.
        detalle = ", ".join(
            f"{d} ({h.ritmo_semanal[d]['vigorosas']}/{h.semanas} semanas)"
            for d in h.dias_de_calidad
        )
        lineas.append(f"Días donde cae el trabajo vigoroso: {detalle}.")

    # La estructura de las sesiones, leída de los tramos del reloj. Va DESPUÉS
    # del recuento de trabajo vigoroso porque lo concreta: "4.1 sesiones
    # vigorosas por semana" no dice si son series o cuestas o un rodaje fuerte.
    if h.estructuradas:
        lineas += [
            "",
            "Sesiones con estructura de series, leídas de los tramos del reloj",
            "(esto NO lo escribió el atleta: son las etiquetas que puso su Garmin):",
        ]
        for s in h.estructuradas:
            nombre = f" — «{s.nombre}»" if s.nombre else ""
            lineas.append(f"  {s.fecha} {s.deporte:<9} {_serie(s)}{nombre}")
        lineas.append(
            "Este atleta ejecuta series: tiene el hábito y los ritmos de arriba son los "
            "suyos, medidos. No hace falta introducirle la intensidad desde cero."
        )
    else:
        lineas += [
            "",
            "No se pudo leer estructura de series en ninguna sesión de la ventana. Eso "
            "significa que no la sabemos ver — su reloj puede no estar etiquetando los "
            "tramos — y NO que el atleta no haga intensidad. El recuento de trabajo "
            "vigoroso de arriba es la referencia buena para eso.",
        ]

    if h.contraste_declarado:
        lineas += ["", "Lo declarado, al lado de lo hecho:"]
        lineas += [f"  - {c}" for c in h.contraste_declarado]

    lineas += [
        "",
        "Esto DESCRIBE lo que el atleta hace; no dice lo que debe hacer. Un hábito puede",
        "mantenerse, romperse a propósito o construirse desde cero — eso lo decides tú, con",
        "el objetivo y la fase delante. Lo que no debería pasar es que un hábito de meses",
        "desaparezca del plan sin que haya una razón.",
    ]
    if h.avisos:
        lineas += [""] + [f"- {a}" for a in h.avisos]
    return "\n".join(lineas)


# --- guardar y reusar entre generaciones ------------------------------------
#
# El macrociclo baja 180 dias de actividades; la semana, 30. Doce semanas de
# habito solo se pueden leer del primero, asi que se calcula alli, se guarda, y
# cada semana lo reusa. El coste en Garmin es cero: son datos que ya se bajaron.
#
# La alternativa era que la semana pidiera 12 semanas a Garmin en cada
# generacion. Se descarto con el numero delante: la cuenta real tardo 4 minutos
# en devolver 173 actividades, y el summarizer de actividad es el nodo que mas
# tokens de entrada consume del pipeline.

FICHERO_HABITOS = "training_habits.json"

# Cuanto puede envejecer el retrato antes de dejar de valer.
#
# Seis semanas es la mitad de su propia ventana: pasado eso, lo que describe ya
# no es "como entrena este atleta ahora" sino como entrenaba. Entonces es mejor
# el retrato corto y fresco de la semana que el largo y viejo del macrociclo --
# un habito que cambio hace un mes importa mas que uno de hace tres.
DIAS_QUE_VALE = 42

# Con qué versión del lector se calculó el retrato guardado. Se sube cuando
# cambia CÓMO se lee la actividad, no sólo cuando cambia el texto.
#
# El 2026-09-14 se arregló que las pulsaciones del botón contaran como
# repeticiones, y la semana regenerada de la atleta 3 siguió diciendo
# "10 x 2'08"": leía el retrato que el macrociclo había guardado esa mañana con
# el lector viejo, y lo iba a seguir leyendo seis semanas. Un retrato de otra
# versión no se usa; la semana calcula el suyo con sus propios datos.
VERSION_DEL_RETRATO = 2


def guardar_habitos(storage_dir: Any, h: TrainingHabits) -> None:
    """Deja el retrato en el directorio del atleta. Nunca falla la generación."""
    import json
    from pathlib import Path

    try:
        ruta = Path(storage_dir) / FICHERO_HABITOS
        ruta.write_text(json.dumps({
            "version": VERSION_DEL_RETRATO,
            "hasta": h.hasta,
            "desde": h.desde,
            "semanas": h.semanas,
            "actividades": h.actividades,
            "bloque": render_training_habits(h),
        }, ensure_ascii=False, indent=2), encoding="utf-8")
    except (OSError, TypeError, ValueError):
        import logging
        logging.getLogger(__name__).warning(
            "No se pudo guardar el retrato de hábitos", exc_info=True)


def cargar_habitos(storage_dir: Any, hoy: date | None = None) -> str | None:
    """El retrato guardado, si sigue siendo suficientemente reciente.

    Devuelve el bloque ya montado, con una línea que dice CUÁNDO se calculó: un
    retrato de hace cinco semanas sigue valiendo, pero el entrenador tiene que
    saber que lo es. Devuelve None si no hay, si no se puede leer o si está
    viejo -- en cualquiera de esos casos quien llama calcula con lo que tenga.
    """
    import json
    from pathlib import Path

    try:
        datos = json.loads((Path(storage_dir) / FICHERO_HABITOS).read_text(encoding="utf-8"))
        bloque = str(datos.get("bloque") or "").strip()
        hasta = date.fromisoformat(str(datos["hasta"]))
    except (OSError, json.JSONDecodeError, KeyError, TypeError, ValueError):
        return None

    if not bloque or datos.get("version") != VERSION_DEL_RETRATO:
        return None
    dias = ((hoy or date.today()) - hasta).days
    if dias < 0 or dias > DIAS_QUE_VALE:
        return None
    if dias <= 7:
        return bloque
    return (
        f"{bloque}\n\nCalculado el {hasta.isoformat()}, hace {dias} días, sobre la ventana "
        "larga del macrociclo. Lo entrenado desde entonces no está en este retrato — "
        "para eso está el estado de carga, que sí es de esta semana."
    )
