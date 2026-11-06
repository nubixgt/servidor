"""Cómo entrena la fuerza el atleta y su programa (Beta 2.1, Database/025).

Fernando (2026-09-17) hace una rutina Upper/Lower A/B con superseries y quiere
hipertrofia; Jessie dice que los ejercicios le salen "muy básicos"; a Hugo le
gusta como está. En las 8 semanas archivadas de José y Jessie ninguna ficha
decía gimnasio, equipo, objetivo ni rutina, así que el modelo elegía lo seguro.

Tres modos, y el tercero es no contestar:

- ``rutina``: tiene la suya; se usa de base y el coach puede mejorarla diciendo
  por qué.
- ``coach``: el coach le arma el programa con su objetivo, su equipo y su tiempo.
- sin perfil (``None``): el plan sigue EXACTAMENTE como hasta hoy.

Este módulo es la única lectura de las dos columnas. Lo que no reconoce lo
descarta en vez de adivinar: un modo desconocido es "no contestó", un objetivo
desconocido es "sin objetivo". Los valores permitidos tienen su espejo en
``Frontend/src/utils/fuerza.js`` y ``tests/test_perfil_de_fuerza.py`` fija que
coinciden.

Lo que NO hace: decidir cuánta fuerza toca en una semana. La dosis la pone la
decisión semanal (ve el afinamiento, la carrera y la recuperación) y el objetivo
cambia el contenido, nunca la frecuencia (plan de la Beta 2.1, S5).
"""

from __future__ import annotations

from dataclasses import dataclass

MODOS = ("rutina", "coach")
OBJETIVOS = ("hipertrofia", "rendimiento", "prevencion")
EQUIPOS = ("gimnasio", "mancuernas_y_bandas", "peso_corporal")
ESTRUCTURAS = ("full_body", "upper_lower")
# Experiencia en concreto, no autoevaluada: qué levantamientos con barra ya hace
# con buena técnica. "Nivel avanzado" no le dice al coach si puede recetar un
# peso muerto.
LEVANTAMIENTOS_CON_BARRA = ("sentadilla", "peso_muerto", "press_banca", "press_militar",
                            "remo_con_barra", "hip_thrust")
EJERCICIOS_POR_GRUPO = (1, 3)
MINUTOS_POR_SESION = (15, 180)
ORIGENES_DE_RUTINA = ("atleta", "coach")


@dataclass(frozen=True)
class PerfilDeFuerza:
    modo: str
    objetivo: str | None = None
    equipo: str | None = None
    estructura: str | None = None
    ejercicios_por_grupo: int | None = None
    minutos_por_sesion: int | None = None
    levantamientos_con_barra: tuple[str, ...] = ()


@dataclass(frozen=True)
class RutinaDeFuerza:
    origen: str
    texto_original: str
    confirmada_el: str | None
    sesiones: tuple[dict, ...]

    @property
    def confirmada(self) -> bool:
        """Sólo una rutina confirmada entra en una semana: el atleta revisó cómo se ordenó."""
        return bool(self.confirmada_el) and bool(self.sesiones)


def _entero_en(valor, limites: tuple[int, int]) -> int | None:
    if isinstance(valor, bool):
        return None
    try:
        numero = float(valor)
    except (TypeError, ValueError):
        return None
    if numero != int(numero):
        return None
    return int(numero) if limites[0] <= numero <= limites[1] else None


def _uno_de(valor, permitidos) -> str | None:
    return valor if isinstance(valor, str) and valor in permitidos else None


def leer_perfil_de_fuerza(perfil: dict | None) -> PerfilDeFuerza | None:
    """El perfil de fuerza del atleta, o None si no contestó (o lo guardado no es un modo)."""
    bruto = (perfil or {}).get("strength_profile")
    if not isinstance(bruto, dict):
        return None
    modo = _uno_de(bruto.get("mode"), MODOS)
    if modo is None:
        return None
    levantamientos = bruto.get("barbell_lifts")
    levantamientos = levantamientos if isinstance(levantamientos, list) else []
    return PerfilDeFuerza(
        modo=modo,
        objetivo=_uno_de(bruto.get("goal"), OBJETIVOS),
        equipo=_uno_de(bruto.get("equipment"), EQUIPOS),
        estructura=_uno_de(bruto.get("structure"), ESTRUCTURAS),
        ejercicios_por_grupo=_entero_en(bruto.get("exercises_per_group"), EJERCICIOS_POR_GRUPO),
        minutos_por_sesion=_entero_en(bruto.get("session_minutes"), MINUTOS_POR_SESION),
        levantamientos_con_barra=tuple(x for x in LEVANTAMIENTOS_CON_BARRA if x in levantamientos),
    )


def render_para_el_macrociclo(perfil: PerfilDeFuerza | None, rutina: RutinaDeFuerza | None) -> str:
    """Lo que el atleta PIDIÓ de su fuerza, para el planificador de temporada (S7).

    Es una PETICIÓN suya, como su tabla de disponibilidad, no una regla nuestra: el
    macrociclo decide qué cualidad tiene sentido en cada fase y lo escribe en
    `SeasonPhase.strength_quality`. Sin perfil devuelve "" y el prompt no cambia ni un
    carácter, que es el caso de todo el que no ha contestado.
    """
    if perfil is None:
        return ""
    lineas = []
    if perfil.objetivo:
        como_lo_dice = {"hipertrofia": "ganar músculo", "rendimiento": "rendir más en su deporte",
                      "prevencion": "mantenerse sano y compensar"}
        lineas.append(f"- Para qué hace gimnasio, en sus palabras: **{como_lo_dice[perfil.objetivo]}**.")
    if perfil.equipo:
        lineas.append(f"- Con qué entrena: {perfil.equipo.replace('_', ' ')}.")
    if perfil.levantamientos_con_barra:
        lineas.append("- Levantamientos con barra que ya hace con buena técnica: "
                      + ", ".join(x.replace("_", " ") for x in perfil.levantamientos_con_barra) + ".")
    else:
        lineas.append("- No declaró ningún levantamiento con barra que ya haga con buena técnica.")
    if rutina is not None and rutina.confirmada:
        de_quien = "que le escribió su coach" if rutina.origen == "coach" else "suya"
        lineas.append(f"- Tiene un programa de gimnasio confirmado ({de_quien}) con "
                      f"{len(rutina.sesiones)} sesión(es): las semanas parten de ahí.")
    if not lineas:
        return ""
    return "\n### Su fuerza, como la pidió\n" + "\n".join(lineas) + "\n" + COMO_SE_PERIODIZA


# Lo que sostiene la evidencia sobre periodizar la fuerza en un atleta de
# resistencia, SIN fijar semanas ni números: igual que con los repartos de
# intensidad, se da la evidencia y decide el modelo para ESE atleta.
#
# La fuerza pesada mejora la economía de carrera (Blagrove 2018) y entrenarla
# junto a la resistencia apenas resta fuerza máxima o hipertrofia (Schumann 2022),
# pero suma fatiga -- y la economía de carrera cae durante unas horas después de
# fuerza de tren inferior (revisión de entrenamiento concurrente, 2024). Dónde cae
# cada sesión NO es cosa del macrociclo: lo decide la semana con sus días clave
# (langgraph/nodes/paso_de_fuerza.py::colocar_las_sesiones).
COMO_SE_PERIODIZA = (
    "Es una petición suya, igual que su tabla de días. **La fuerza también se periodiza**: en un atleta de "
    "resistencia lo habitual es empezar por adaptación y técnica, subir a cargas altas cuando la temporada "
    "lo permite y dejarla en mantenimiento cerca de las competiciones. La fuerza pesada mejora la economía "
    "de carrera (Blagrove 2018) y entrenarla junto a la resistencia apenas resta fuerza o hipertrofia "
    "(Schumann 2022), pero suma fatiga. Escribe en `strength_quality` qué busca la fuerza en CADA fase, con "
    "las palabras de este atleta y lo que declaró, o déjala vacía si no tienes nada que decir. **No fijes "
    "cuántas sesiones ni cuántas semanas**: los días son suyos, y en qué día cae cada sesión lo decide la "
    "semana con su calidad y su tirada larga delante.\n"
)


def leer_rutina_de_fuerza(perfil: dict | None) -> RutinaDeFuerza | None:
    """El programa guardado, confirmado o no. None si no hay nada que leer."""
    bruto = (perfil or {}).get("strength_routine")
    if not isinstance(bruto, dict):
        return None
    texto = bruto.get("original_text") if isinstance(bruto.get("original_text"), str) else ""
    sesiones = bruto.get("sessions") if isinstance(bruto.get("sessions"), list) else []
    if not texto.strip() and not sesiones:
        return None
    confirmada = bruto.get("confirmed_at")
    return RutinaDeFuerza(
        origen=_uno_de(bruto.get("source"), ORIGENES_DE_RUTINA) or "atleta",
        texto_original=texto,
        confirmada_el=confirmada if isinstance(confirmada, str) and confirmada else None,
        sesiones=tuple(s for s in sesiones if isinstance(s, dict)),
    )
