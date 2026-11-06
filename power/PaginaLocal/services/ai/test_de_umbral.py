"""El test de umbral: cómo se hace, y qué número sale de la actividad que lo registró.

## Por qué existe

Los informes del atleta Hugo (2026-09-07) muestran el hueco entero en tres
líneas. Su ritmo umbral de Garmin (6:23/km) era más lento que su ritmo de
maratón y se descartó; la app puso en su lugar su ritmo de 10K predicho (4:27),
que para un 10K de 44 minutos es más rápido que su umbral. Sus zonas de pulso
de carrera y de natación eran las de fábrica (50/60/70/80/90 % de una FC máxima
de 196), así que su "Z4" empezaba en 157: el experto leyó un 21K a 162 ppm de
media como "tempo/umbral" y le recetó rodar "por debajo de 140-145", y en la
misma frase dijo que sus zonas parecían mal calibradas y que lo resolvería un
test. Nadie en el sistema podía hacer ese test ni leerlo.

Una predicción describe una carrera que no se ha corrido; unas zonas de fábrica
describen a una persona media. El umbral de VERDAD sale de un esfuerzo medido
con un protocolo estándar, y eso es lo que hay aquí: el protocolo, escrito una
vez, y la cuenta sobre las vueltas que el reloj guardó. Sin modelo.

## Los protocolos (los de la literatura, no unos propios)

- **Carrera y bici: 30 minutos a tope, solo** (Joe Friel). Tras calentar, 30'
  al máximo esfuerzo que se pueda sostener de forma pareja. La FC media de los
  **últimos 20 minutos** es la FC umbral (LTHR); el ritmo medio de los 30' es
  el ritmo umbral en carrera, y la potencia media de los 30' aproxima el FTP
  en bici. El reloj parte los 30' en 10' + 20' SIN pausa entre medias: así los
  20' son una vuelta exacta y no hay nada que suponer. Dos pasos seguidos se
  pueden leer como dos series con descanso, y un descanso a los 10' tira el
  test; por eso los dos textos dicen "sin parar".
- **Natación: CSS** (critical swim speed). 400 m y 200 m a tope con descanso
  completo entre medias; CSS por 100 m = (T400 - T200) / 2. Los tiempos los
  escribe el atleta (el reloj cuenta los largos por acelerómetro) y la cuenta la
  hace la pantalla del perfil: el cerebro sólo lee el resultado guardado.

## Lo que la cuenta se niega a hacer

Abstenerse antes que acertar por suerte, como en `exercise_catalog.py`. Un
número de una sesión que no fue un test movería las zonas del atleta en la
dirección equivocada, así que la actividad tiene que tener la FORMA del
protocolo o se dice por qué no:

- **30 minutos seguidos y parejos.** Las vueltas reales de los atletas 2 y 3
  tienen tiradas de 2 h 35 con el pulso en 145-164 y sesiones de series. La
  primera versión, probada contra la cuenta del atleta 2 el 2026-09-14, aceptó
  como test sus series del 9 y del 2 de septiembre (2' fuertes con 2' de trote)
  y dos rodajes suaves de 31 y 36 minutos, y dio umbrales de 137-143 ppm a un
  atleta cuyo Garmin mide 176. Dentro de la ventana no puede haber vueltas mucho
  más lentas que el test (recuperaciones o paradas).
- **Con calentamiento, y destacando sobre él.** Sin nada antes con qué
  compararlo, 30 minutos de rodaje y 30 minutos a tope son indistinguibles.
- **Sin durar horas.** Los 30' más rápidos de una tirada larga no son un test.

Y aun con la forma correcta, lo que no se puede saber es si fue A TOPE. Por eso
el resultado se pone al lado de la FC umbral que ya tiene Garmin, y el atleta
decide con los dos números delante.
"""

from __future__ import annotations

from datetime import date
from typing import Any

# --- Protocolos --------------------------------------------------------------

#
# El test de carrera y el de bici van al RELOJ como un entreno de cuatro pasos
# (`sesion_del_test`): calentamiento, 10' de test, 20' de test y enfriamiento.
# Cada paso cierra su vuelta solo, así que los últimos 20' son una vuelta exacta,
# y la actividad que sale lleva el `workoutId` del entreno: la app lee ESA
# actividad y ninguna otra (ver `server.analizar_test_de_umbral`). La primera
# versión buscaba entre las actividades recientes la que "pareciera" un test, y
# eso es adivinar -- para adivinar, Garmin estima mejor.
PROTOCOLOS: dict[str, dict[str, Any]] = {
    "run": {
        "titulo": "Test de umbral corriendo (55 minutos con calentamiento)",
        "cuando": "Descansado, sin una sesión dura el día antes, en llano (pista, parque o carretera sin cuestas). "
                  "Mejor sin calor fuerte. El entreno va a tu reloj con los cuatro pasos y cada uno cambia solo: "
                  "no hace falta pulsar VUELTA.",
        "pasos": [
            "Calentamiento, 15': suave, subiendo poco a poco, con 3-4 aceleraciones cortas al final. Es parte del "
            "test: sin calentar, el pulso de los primeros minutos sale bajo.",
            "Test, 30' seguidos y SIN PARAR: al máximo esfuerzo que puedas sostener parejo de principio a fin, como "
            "una carrera en solitario. El reloj los parte en 10' + 20' sólo para medir los últimos 20: cuando cambie "
            "de paso en el minuto 10 no pares ni aflojes, sigue igual. No salgas a tope: el último minuto tiene que "
            "ser el más duro.",
            "Enfriamiento, 10': trote muy suave.",
        ],
        "calculo": "Tu FC umbral es tu pulso medio de los últimos 20 minutos, y tu ritmo umbral, tu ritmo medio de "
                   "los 30 minutos (Joe Friel). La app lee sólo la actividad que hagas con este entreno.",
        "alternativa": "Si tu reloj tiene la Prueba guiada de umbral de lactato de Garmin (necesita banda de pecho), "
                       "también sirve: tu plan lee el umbral que mide Garmin cada vez que se genera.",
    },
    "bike": {
        "titulo": "Test de umbral en bici (55 minutos con calentamiento)",
        "cuando": "Descansado, en rodillo o en un tramo llano o de subida constante sin cruces ni semáforos. El "
                  "entreno va a tu reloj con los cuatro pasos y cada uno cambia solo: no hace falta pulsar VUELTA.",
        "pasos": [
            "Calentamiento, 15': suave, subiendo poco a poco, con 3 aceleraciones de 30\" al final. Es parte del "
            "test: sin calentar, el pulso de los primeros minutos sale bajo.",
            "Test, 30' seguidos y SIN PARAR: al máximo esfuerzo que puedas sostener parejo de principio a fin. El "
            "reloj los parte en 10' + 20' sólo para medir los últimos 20: cuando cambie de paso en el minuto 10 no "
            "pares ni aflojes, sigue igual.",
            "Enfriamiento, 10': muy suave.",
        ],
        "calculo": "Tu FC umbral en bici es tu pulso medio de los últimos 20 minutos. Con potenciómetro, tu potencia "
                   "media de los 30 minutos es una buena aproximación de tu FTP (Joe Friel). La app lee sólo la "
                   "actividad que hagas con este entreno.",
    },
    "swim": {
        "titulo": "Test de CSS en natación (400 m + 200 m)",
        "cuando": "En piscina, descansado.",
        "pasos": [
            "400-600 m de calentamiento suave, con unos largos progresivos.",
            "400 m a tope, parejos. Apunta el tiempo.",
            "Descanso completo: 5-10 minutos nadando muy suave o parado.",
            "200 m a tope. Apunta el tiempo.",
            "200 m muy suaves.",
        ],
        "calculo": "Tu ritmo umbral por 100 m (CSS) es la diferencia entre tus dos tiempos, dividida entre 2. "
                   "Escribe los tiempos tú: el reloj cuenta los largos por acelerómetro y un viraje mal detectado "
                   "cambia el parcial.",
    },
}

# --- La forma de un test -------------------------------------------------------

VENTANA_MIN_S = 27 * 60
VENTANA_MAX_S = 36 * 60
# Los "últimos 20 minutos" con vueltas automáticas de 1 km (4-6 min cada una)
# no caen justos: se aceptan entre 15 y 24.
ULTIMOS_OBJETIVO_S = 20 * 60
ULTIMOS_MIN_S = 15 * 60
ULTIMOS_MAX_S = 24 * 60
# Calentamiento + enfriamiento + margen. Una tirada larga no es un test.
MARGEN_FUERA_DE_LA_VENTANA_S = 50 * 60
# El protocolo pide 15'; con menos de 8 no hay con qué comparar el test.
CALENTAMIENTO_MIN_S = 8 * 60
# Cuánto tiene que destacar el test sobre el calentamiento.
DESTACA_SOBRE_EL_CALENTAMIENTO = 1.08
DESTACA_SIN_POTENCIA_PPM = 10
# Un esfuerzo seguido: ninguna vuelta del test (de un minuto o más) por debajo de
# esta fracción de su velocidad o potencia media, ni de su pulso medio menos esto.
PAREJO_VELOCIDAD = 0.85
PAREJO_POTENCIA = 0.75
PAREJO_SIN_POTENCIA_PPM = 12
VUELTA_MINIMA_PARA_JUZGAR_S = 60
# Por encima de esto el ritmo depende del terreno, no del umbral.
DESNIVEL_MAX_M_POR_KM = 15.0
# Variación del ritmo entre vueltas por encima de la cual se avisa.
CV_RITMO_IRREGULAR = 0.08
# Distancia a la FC umbral de Garmin a partir de la que se pone el aviso.
LEJOS_DE_GARMIN_PPM = 10


def _n(valor: Any) -> float | None:
    try:
        v = float(valor)
    except (TypeError, ValueError):
        return None
    return v if v == v else None  # NaN fuera


def _vueltas_utiles(vueltas: list[dict] | None) -> list[dict]:
    return [v for v in (vueltas or []) if isinstance(v, dict) and (_n(v.get("duration_s")) or 0) > 0]


def _media_ponderada(vueltas: list[dict], clave: str) -> float | None:
    total = peso = 0.0
    for v in vueltas:
        valor, dur = _n(v.get(clave)), _n(v.get("duration_s")) or 0.0
        if valor is None or valor <= 0:
            continue
        total += valor * dur
        peso += dur
    return total / peso if peso else None


def _segundos(vueltas: list[dict]) -> float:
    return sum(_n(v.get("duration_s")) or 0.0 for v in vueltas)


def _metros(vueltas: list[dict]) -> float:
    return sum(_n(v.get("distance_m")) or 0.0 for v in vueltas)


def _medida(vueltas: list[dict], deporte: str) -> str:
    """Con qué se juzga lo duro que fue: velocidad a pie, potencia o, sin potenciómetro, pulso."""
    if deporte == "run":
        return "velocidad"
    return "potencia" if vueltas and all(_n(v.get("averagePower_w")) for v in vueltas) else "pulso"


def _intensidad(vueltas: list[dict], medida: str) -> float | None:
    if medida == "velocidad":
        s, m = _segundos(vueltas), _metros(vueltas)
        return m / s if s and m else None
    return _media_ponderada(vueltas, "averagePower_w" if medida == "potencia" else "averageHR_bpm")


def _es_parejo(ventana: list[dict], medida: str, media: float) -> bool:
    """Si es un esfuerzo SEGUIDO: sin recuperaciones ni paradas dentro."""
    for v in ventana:
        duracion = _n(v.get("duration_s")) or 0.0
        if duracion < VUELTA_MINIMA_PARA_JUZGAR_S:
            continue
        if medida == "velocidad":
            if (_n(v.get("distance_m")) or 0.0) / duracion < media * PAREJO_VELOCIDAD:
                return False
        elif medida == "potencia":
            if (_n(v.get("averagePower_w")) or 0.0) < media * PAREJO_POTENCIA:
                return False
        else:
            fc = _n(v.get("averageHR_bpm"))
            if fc is None or fc < media - PAREJO_SIN_POTENCIA_PPM:
                return False
    return True


def _ritmo(segundos_por_km: float | None) -> str:
    if not segundos_por_km:
        return "—"
    t = int(round(segundos_por_km))
    return f"{t // 60}:{t % 60:02d}"


def analizar_test(vueltas: list[dict] | None, deporte: str, en_cinta: bool = False,
                  lthr_de_garmin: float | None = None) -> dict[str, Any]:
    """Lo que dice una actividad como test de 30 minutos. `ok=False` con el motivo si no lo es.

    `vueltas` en el formato de `data_extractor.procesar_lap` (segundos, metros,
    m/s, ppm, vatios). Nunca devuelve un número de una actividad que no tenga
    forma de test. `lthr_de_garmin`, si se sabe, se pone al lado del resultado.
    """
    if deporte not in ("run", "bike"):
        return {"ok": False, "motivo": "El test por vueltas es de carrera o de bici; la natación se calcula con tus dos tiempos."}
    vueltas = _vueltas_utiles(vueltas)
    if not vueltas:
        return {"ok": False, "motivo": "La actividad no tiene vueltas con duración."}
    medida = _medida(vueltas, deporte)

    # La ventana contigua de 27-36 minutos más dura que sea un esfuerzo seguido.
    mejor: tuple[float, int, int] | None = None
    hubo_ventana = False
    for i in range(len(vueltas)):
        acumulado = 0.0
        for j in range(i, len(vueltas)):
            acumulado += _n(vueltas[j].get("duration_s")) or 0.0
            if acumulado > VENTANA_MAX_S:
                break
            if acumulado < VENTANA_MIN_S:
                continue
            hubo_ventana = True
            ventana = vueltas[i:j + 1]
            valor = _intensidad(ventana, medida)
            if valor and _es_parejo(ventana, medida, valor) and (mejor is None or valor > mejor[0]):
                mejor = (valor, i, j)
    if not hubo_ventana:
        return {"ok": False, "motivo": (
            "No hay 30 minutos seguidos que se puedan separar en vueltas: pulsa vuelta al empezar el test y a los "
            "10 minutos, como dice el protocolo.")}
    if mejor is None:
        return {"ok": False, "motivo": (
            "No hay 30 minutos de esfuerzo seguido y parejo: dentro hay vueltas mucho más lentas (recuperaciones, "
            "paradas o un rodaje irregular). Un test son 30 minutos a tope sin pausas.")}

    valor, i, j = mejor
    ventana = vueltas[i:j + 1]
    calentamiento = vueltas[:i]
    s_ventana, s_total = _segundos(ventana), _segundos(vueltas)

    if s_total - s_ventana > MARGEN_FUERA_DE_LA_VENTANA_S:
        return {"ok": False, "motivo": (
            f"La actividad dura {round(s_total / 60)} minutos: no tiene la forma del test (30 minutos a tope con su "
            "calentamiento y su enfriamiento). Los 30 minutos más rápidos de una sesión larga no son tu umbral.")}
    if _segundos(calentamiento) < CALENTAMIENTO_MIN_S:
        return {"ok": False, "motivo": (
            "No hay calentamiento antes de esos 30 minutos, y sin él no se distingue un test de un rodaje: el "
            "protocolo empieza con 15 minutos suaves.")}
    valor_calentamiento = _intensidad(calentamiento, medida)
    if valor_calentamiento and (
        valor < valor_calentamiento + DESTACA_SIN_POTENCIA_PPM if medida == "pulso"
        else valor < valor_calentamiento * DESTACA_SOBRE_EL_CALENTAMIENTO
    ):
        return {"ok": False, "motivo": (
            "Esos 30 minutos no destacan sobre el calentamiento: no parece un test a tope, sino un esfuerzo "
            "continuo.")}

    # Los últimos ~20 minutos: el sufijo de vueltas que más se acerca.
    ultimos: list[dict] = []
    for k in range(len(ventana) - 1, -1, -1):
        candidato = ventana[k:]
        if _segundos(candidato) > ULTIMOS_MAX_S:
            break
        if ultimos and abs(_segundos(candidato) - ULTIMOS_OBJETIVO_S) >= abs(_segundos(ultimos) - ULTIMOS_OBJETIVO_S):
            continue
        ultimos = candidato
    if _segundos(ultimos) < ULTIMOS_MIN_S:
        return {"ok": False, "motivo": (
            "Las vueltas son demasiado largas para separar los últimos 20 minutos del test: pulsa vuelta a los 10 "
            "minutos de empezar.")}
    lthr = _media_ponderada(ultimos, "averageHR_bpm")
    if not lthr:
        return {"ok": False, "motivo": "La actividad no tiene pulso en las vueltas del test."}

    avisos: list[str] = []
    resultado: dict[str, Any] = {
        "ok": True,
        "deporte": deporte,
        "lthr": round(lthr),
        "minutos_test": round(s_ventana / 60, 1),
        "minutos_ultimos": round(_segundos(ultimos) / 60, 1),
        "medida": medida,
        "avisos": avisos,
    }

    if deporte == "run":
        metros = _metros(ventana)
        ritmo = s_ventana / (metros / 1000.0) if metros else None
        desnivel = sum(_n(v.get("elevationGain_m")) or 0.0 for v in ventana)
        velocidades = [(_n(v.get("distance_m")) or 0.0) / (_n(v.get("duration_s")) or 1.0) for v in ventana
                       if (_n(v.get("duration_s")) or 0.0) >= VUELTA_MINIMA_PARA_JUZGAR_S]
        velocidades = [x for x in velocidades if x > 0]
        if len(velocidades) >= 3:
            media = sum(velocidades) / len(velocidades)
            cv = (sum((x - media) ** 2 for x in velocidades) / len(velocidades)) ** 0.5 / media
            if cv > CV_RITMO_IRREGULAR:
                avisos.append("El ritmo cambió bastante entre vueltas: un test parejo da un número más fiable.")
        if en_cinta:
            ritmo = None
            avisos.append("Fue en cinta: el ritmo que mide el reloj en cinta no es fiable, así que sólo se usa el pulso.")
        elif metros and desnivel / (metros / 1000.0) > DESNIVEL_MAX_M_POR_KM:
            ritmo = None
            avisos.append(f"El test tuvo {round(desnivel)} m de desnivel: con cuestas el ritmo no es tu umbral, así "
                          "que sólo se usa el pulso.")
        resultado["ritmo_s_km"] = round(ritmo, 1) if ritmo else None
        resultado["ritmo"] = _ritmo(ritmo) if ritmo else None
    else:
        potencia = _media_ponderada(ventana, "averagePower_w") if medida == "potencia" else None
        resultado["ftp_w"] = round(potencia) if potencia else None
        if not potencia:
            avisos.append("Sin potenciómetro: el test da tu FC umbral en bici, no tu FTP.")

    # Lo que no se puede saber por la forma: si fue A TOPE. Se pone al lado lo
    # que ya tiene Garmin y decide el atleta.
    referencia = _n(lthr_de_garmin)
    if deporte == "run" and referencia:
        if resultado["lthr"] < referencia - LEJOS_DE_GARMIN_PPM:
            avisos.append(f"Sale {resultado['lthr']} ppm y tu Garmin tiene {round(referencia)}: un test a tope suele "
                          "dar un umbral parecido o más alto. Si no fue a tope, no lo uses.")
        elif resultado["lthr"] > referencia + LEJOS_DE_GARMIN_PPM:
            avisos.append(f"Sale {resultado['lthr']} ppm y tu Garmin tiene {round(referencia)}: si fue a tope y "
                          "parejo, el de tu test es más fiable que la estimación del reloj.")
    return resultado


# --- Lo que ven los nodos ------------------------------------------------------

_DEPORTE_ES = {"run": "carrera", "bike": "bici", "swim": "natación"}
# Un umbral cambia con el entrenamiento: pasado este tiempo se le pone la edad.
SEMANAS_DE_VIGENCIA = 12


def _semanas(fecha: str | None, hoy: date) -> int | None:
    try:
        return max(0, (hoy - date.fromisoformat(str(fecha)[:10])).days // 7)
    except (TypeError, ValueError):
        return None


def _numeros_del_test(t: dict) -> list[str]:
    partes = []
    if t.get("lthr"):
        partes.append(f"FC umbral {round(float(t['lthr']))} ppm")
    if t.get("ritmo_s_km"):
        partes.append(f"ritmo umbral {_ritmo(float(t['ritmo_s_km']))}/km")
    if t.get("ftp_w"):
        partes.append(f"FTP ~{round(float(t['ftp_w']))} W")
    if t.get("css_s_100m"):
        partes.append(f"CSS {_ritmo(float(t['css_s_100m']))}/100 m")
    return partes


def tests_por_fecha(tests: dict | None, deporte: str) -> list[dict]:
    """Todos los tests guardados de un deporte, del MÁS RECIENTE al más viejo.

    El test se repite cada pocos meses. Lo que el perfil guarda es
    `{deporte: {...el que se usó..., "anteriores": [...]}}`, y quien lo escribe
    (la pantalla del perfil) pone arriba el más reciente. Pero quien LEE no se
    fía del orden: vuelve a ordenar por fecha, así que un test viejo analizado
    tarde nunca manda sobre uno más nuevo.
    """
    t = (tests or {}).get(deporte)
    if not isinstance(t, dict):
        return []
    todos = [{k: v for k, v in t.items() if k != "anteriores"}]
    todos += [a for a in (t.get("anteriores") or []) if isinstance(a, dict)]
    return sorted((x for x in todos if x.get("fecha")), key=lambda x: str(x["fecha"]), reverse=True)


def test_vigente(tests: dict | None, deporte: str) -> dict:
    """El test más reciente de ese deporte, o {}."""
    ordenados = tests_por_fecha(tests, deporte)
    return ordenados[0] if ordenados else {}


def describir_tests(tests: dict | None, hoy: date | None = None) -> list[str]:
    """Una línea por deporte con su test MÁS RECIENTE, su edad y el anterior. [] si no hay ninguno.

    Manda el último; el anterior se dice al lado porque la diferencia entre los
    dos es cómo respondió al bloque.
    """
    hoy = hoy or date.today()
    lineas = []
    for deporte in ("run", "bike", "swim"):
        ordenados = [x for x in tests_por_fecha(tests, deporte) if _numeros_del_test(x)]
        if not ordenados:
            continue
        t = ordenados[0]
        semanas = _semanas(t.get("fecha"), hoy)
        edad = ""
        if semanas is not None and semanas >= SEMANAS_DE_VIGENCIA:
            edad = f" — hace {semanas} semanas: puede haber cambiado, léelo contra lo que hace hoy"
        antes = ""
        if len(ordenados) > 1:
            previo = ordenados[1]
            antes = f" (el anterior, del {str(previo['fecha'])[:10]}: {', '.join(_numeros_del_test(previo))})"
        lineas.append(f"Test de {_DEPORTE_ES[deporte]} del {str(t['fecha'])[:10]}: "
                      f"{', '.join(_numeros_del_test(t))}{antes}{edad}.")
    return lineas


# --- La sesión que va al reloj ---------------------------------------------

NOMBRE_DEL_ENTRENO = "Test de umbral"
FRANJA_DEL_TEST = "test"


def sesion_del_test(deporte: str, fecha: str):
    """El test de carrera o de bici como una sesión del esquema, lista para subir al reloj.

    Determinista: cuatro pasos por tiempo y sin objetivo -- un objetivo de ritmo o
    de pulso haría que el atleta corriera al número del reloj en vez de a tope,
    que es justo lo que se mide. El 10' + 20' en dos pasos es lo que hace que la
    vuelta de los últimos 20 minutos exista sin que el atleta pulse nada.
    """
    from services.ai.langgraph.schemas.plan_outputs import TargetType, WorkoutDay, WorkoutStep

    if deporte not in ("run", "bike"):
        raise ValueError("El test al reloj es de carrera o de bici")
    a_pie = deporte == "run"
    que = "Corre" if a_pie else "Rueda"
    aceleraciones = "3-4 aceleraciones cortas" if a_pie else "3 aceleraciones de 30\""
    pasos = [
        WorkoutStep(step_order=1, step_type="warmup", duration_type="time", duration_value=900,
                    target_type=TargetType.NONE,
                    description=f"Calentamiento 15' suave, subiendo poco a poco, con {aceleraciones} al final"),
        WorkoutStep(step_order=2, step_type="interval", duration_type="time", duration_value=600,
                    target_type=TargetType.NONE,
                    description=f"TEST min 0-10: {que} al máximo que puedas sostener parejo 30' seguidos. "
                                "No salgas a tope. En el minuto 10 NO pares"),
        WorkoutStep(step_order=3, step_type="interval", duration_type="time", duration_value=1200,
                    target_type=TargetType.NONE,
                    description="TEST min 10-30: SIN PARAR ni aflojar, sigue igual; el último minuto es el más duro"),
        WorkoutStep(step_order=4, step_type="cooldown", duration_type="time", duration_value=600,
                    target_type=TargetType.NONE,
                    description="Enfriamiento 10' muy suave"),
    ]
    dia_es = ("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")[
        date.fromisoformat(fecha).weekday()]
    return WorkoutDay(
        date=fecha, day_name_es=dia_es, is_rest=False, sport=deporte, focus=NOMBRE_DEL_ENTRENO,
        purpose="Medir tu umbral de verdad: FC de los últimos 20 minutos y ritmo o potencia de los 30.",
        adaptation="Si no llegas descansado, muévelo: un test cansado da un umbral bajo.",
        steps=pasos, total_duration_minutes=55, discipline="test", intensity_class="other",
    )
