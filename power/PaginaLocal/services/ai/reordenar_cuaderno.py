"""«Revisa mi cuaderno»: el coach lee el cuaderno ENTERO junto al perfil, pregunta lo que no
sabe y propone un cuaderno ordenado, que el atleta acepta cambio a cambio (Beta 3.0, F9).

El cuaderno crece con el chat y se llena de anotaciones repetidas, viejas, que en realidad son
del perfil o que lo CONTRADICEN (el cuaderno dice «los martes me gusta correr» y su tabla no
tiene correr el martes). Decisión del responsable (2026-10-11): la IA lee todos los puntos y
el perfil, hace las preguntas que hagan falta y devuelve:

- el cuaderno ordenado: para cada anotación mantener, reescribir (también para cambiarla de
  categoría), fusionar, pasar al perfil o retirar, cada una con su motivo;
- **avisos del perfil**: lo que el ATLETA tiene que cambiar en su perfil («ve a tu perfil y
  marca el martes como correr», «añade la competición», «declara la lesión en Avanzado»).
  No van al cuaderno ni se guardan: la app no escribe el perfil por él.

Una o dos llamadas al modelo del cuaderno (`AgentRole.NOTEBOOK`, barato): si no tiene dudas,
ordena en la primera; si pregunta (4 como mucho), ordena con las respuestas en la segunda,
donde ya no puede volver a preguntar.

**El código no se fía de la propuesta** antes de enseñarla:

- cada anotación aparece exactamente una vez, y la que el modelo olvidó se queda como está;
- un texto nuevo no puede traer números ni fechas que no estuvieran en las anotaciones que
  sustituye o en lo que el atleta respondió (un «4 km» que nadie dijo acabaría en el plan);
- «pasar al perfil» y los avisos, sólo a un campo de `CAMPOS_DEL_PERFIL` (con el sitio REAL
  de la pantalla del perfil);
- una categoría fuera de las del cuaderno no se acepta, y una preferencia de entrenamiento
  sigue siéndolo (alimenta su plan).

Nada se guarda aquí: PHP retira las originales con su motivo y `replaced_by` (nada se borra).
"""

from __future__ import annotations

import re
from collections.abc import Awaitable, Callable
from datetime import date
from typing import Any, Literal

from pydantic import BaseModel, Field

ACCIONES = ("mantener", "reescribir", "fusionar", "al_perfil", "retirar")
CATEGORIAS = ("medicacion", "sueno", "logistica", "preferencia", "molestia", "nutricion", "vida", "otro")
MAX_ENTRADAS = 80
MAX_PREGUNTAS = 4
MAX_AVISOS = 6

# Dónde está cada dato en la pantalla del perfil (Frontend/src/views/admin/ProfileView.vue:
# pestaña → sección → campo). La pantalla lo dice y lleva a la pestaña; NO escribe el perfil.
# Hasta el 2026-10-11 aquí había un «planning_context» que la pantalla no tiene.
CAMPOS_DEL_PERFIL = {
    "sport_days": "Perfil → Objetivos y días → Tus días de entrenamiento",
    "pool_access": "Perfil → Objetivos y días → Tus días de entrenamiento → Piscina",
    "competitions": "Perfil → Objetivos y días → Tus carreras",
    "strength_profile": "Perfil → Objetivos y días → Fuerza",
    "preferred_rest_day": "Perfil → Objetivos y días → Tus días de entrenamiento",
    "current_injuries": "Perfil → Avanzado → Salud y antecedentes → Lesiones",
    "past_injuries_surgeries": "Perfil → Avanzado → Salud y antecedentes → Lesiones y cirugías pasadas",
    "chronic_conditions": "Perfil → Avanzado → Salud y antecedentes → Condiciones crónicas",
    "medications": "Perfil → Avanzado → Salud y antecedentes → Medicamentos",
    "treadmill_access": "Perfil → Avanzado → Equipo y entorno",
    "nutrition_notes": "Perfil → Avanzado → Estilo de vida y recuperación → Notas de alimentación",
    "avg_sleep_hours": "Perfil → Avanzado → Estilo de vida y recuperación → Horas de sueño",
}
# La pestaña a la que lleva el botón de la pantalla (ProfileView acepta ?tab=).
PESTANA_DEL_CAMPO = {c: d.split(" → ")[1] for c, d in CAMPOS_DEL_PERFIL.items()}

_DIAS = ("Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo")


class AccionDeReorden(BaseModel):
    ids: list[str] = Field(..., description="Los id de las anotaciones a las que se refiere (una o varias).")
    accion: Literal["mantener", "reescribir", "fusionar", "al_perfil", "retirar"]
    texto: str = Field("", description="Sólo en reescribir y fusionar: la anotación nueva, en una frase.")
    categoria: str = Field("", description="Sólo en reescribir y fusionar: la categoría de la anotación nueva.")
    campo_del_perfil: str = Field("", description="Sólo en al_perfil: uno de los campos de la lista.")
    motivo: str = Field(..., description="Por qué, en una frase, para el atleta.")


class Pregunta(BaseModel):
    texto: str = Field(..., description="La pregunta, corta, en segunda persona.")
    ids: list[str] = Field(default_factory=list, description="Las anotaciones a las que se refiere.")
    opciones: list[str] = Field(default_factory=list, description="Hasta 4 respuestas cortas posibles, si las hay.")


class AvisoDelPerfil(BaseModel):
    campo: str = Field(..., description="Uno de los campos del perfil de la lista.")
    que_hacer: str = Field(..., description="Lo que el atleta tiene que cambiar en su perfil, en una frase.")
    ids: list[str] = Field(default_factory=list, description="Las anotaciones que lo muestran.")


class Revision(BaseModel):
    preguntas: list[Pregunta] = Field(default_factory=list)
    acciones: list[AccionDeReorden] = Field(default_factory=list)
    avisos_del_perfil: list[AvisoDelPerfil] = Field(default_factory=list)


# Compatibilidad con el nombre de la primera versión (F9) y sus pruebas.
PropuestaDeReorden = Revision


SISTEMA = """Eres el entrenador de este atleta y estás revisando su cuaderno: las anotaciones que
has ido tomando de vuestras conversaciones y las que escribió él. Tienes también un resumen de su
PERFIL, que es lo que la app usa para planificar.

Haces tres cosas:

1. PREGUNTAS (opcional, como mucho 4): sólo si una anotación es ambigua, parece vieja o choca con
   otra o con el perfil y no puedes saber cuál vale. Una pregunta por duda, corta, con opciones si
   las hay. Nunca preguntes por algo que el cuaderno no menciona. Si preguntas, deja "acciones" y
   "avisos_del_perfil" vacíos: los harás con sus respuestas.
   Una molestia, un dolor o una lesión que el perfil no tiene: PREGUNTA si sigue antes de mandarla
   al perfil o retirarla. Una molestia que ya pasó no es una lesión actual, y una que sigue no se
   retira.

2. ACCIONES, para CADA anotación una, con su motivo en una frase:
   - mantener: está bien como está. Es lo normal.
   - reescribir: dice lo mismo, pero mejor (más claro, una sola frase), o va en otra categoría.
     No añadas nada que no diga.
   - fusionar: varias dicen lo mismo o se completan; escribe UNA que las sustituya a todas.
   - al_perfil: no es una anotación sino un dato del perfil (medicación, una condición, una lesión,
     su nutrición, sus días de entreno). Di en qué campo va.
   - retirar: ya no aplica (una molestia que se curó, un viaje cuyas fechas ya pasaron respecto a
     HOY), o el atleta lo dijo. Sólo si queda claro: ante la duda, mantener o preguntar.

3. AVISOS DEL PERFIL: cuando el cuaderno y el perfil se contradicen, o el cuaderno dice algo que el
   perfil debería tener y no tiene (una competición, una lesión actual, un día de entreno), di al
   atleta qué cambiar en su perfil y en qué campo. Ejemplo: el cuaderno dice que los martes le
   gusta correr y su tabla no tiene correr el martes → «Marca Correr el martes en tu tabla de
   días». Esto no va al cuaderno: lo cambia él. No repitas como aviso lo que ya propones con
   al_perfil.

Reglas:
- Cada anotación aparece en UNA sola acción.
- Nunca escribas un número, una distancia, un ritmo o una fecha que no esté en las anotaciones o
  en lo que el atleta respondió.
- Las preferencias de entrenamiento se quedan como preferencias: alimentan su plan.
- Categorías del cuaderno: """ + ", ".join(CATEGORIAS) + """.
- No inventes problemas: si el cuaderno está bien y cuadra con el perfil, mantener todas, sin
  preguntas ni avisos, es la respuesta correcta."""


def _txt(v: Any) -> str:
    return str(v).strip() if v not in (None, "", [], {}) else ""


def resumen_del_perfil(perfil: dict | None) -> str:
    """Lo del perfil que un cuaderno puede contradecir, escrito para el modelo. Sin el perfil,
    lo dice: el modelo no puede suponer que el perfil está vacío."""
    p = perfil or {}
    if not p:
        return "Perfil: no disponible en esta revisión (no des avisos del perfil)."
    lineas = ["Perfil del atleta:"]
    tabla = p.get("sport_days") if isinstance(p.get("sport_days"), dict) else {}
    if tabla:
        dias = []
        for dia in _DIAS:
            hay = [f"{dep} ({estado})" for dep, por_dia in tabla.items()
                   if isinstance(por_dia, dict) and por_dia.get(dia) in ("fijo", "posible")
                   for estado in [por_dia.get(dia)]]
            dias.append(f"  - {dia}: {', '.join(hay) if hay else 'descanso'}")
        lineas.append("- Tabla de días (deporte y si es fijo o posible):")
        lineas.extend(dias)
    else:
        lineas.append("- Tabla de días: sin rellenar.")
    if _txt(p.get("preferred_rest_day")):
        lineas.append(f"- Día de descanso preferido: {p['preferred_rest_day']}")
    comps = [c for c in (p.get("competitions") or []) if isinstance(c, dict)]
    lineas.append("- Competiciones: " + ("; ".join(
        f"{c.get('name') or 'sin nombre'} ({c.get('date') or 'sin fecha'}, prioridad {c.get('priority') or '?'}"
        f"{', ' + str(c['distance']) if c.get('distance') else ''})" for c in comps) if comps else "ninguna"))
    lesiones = [i for i in (p.get("current_injuries") or []) if isinstance(i, dict)]
    lineas.append("- Lesiones en la ficha: " + ("; ".join(
        f"{i.get('location') or 'lesión'}{' ' + str(i['laterality']) if i.get('laterality') else ''} "
        f"({i.get('status') or 'activa'}"
        f"{', dolor ' + str(i['pain_scale']) + '/10' if i.get('pain_scale') not in (None, '') else ''})"
        for i in lesiones) if lesiones else "ninguna declarada"))
    for campo, nombre in (("past_injuries_surgeries", "Lesiones y cirugías pasadas"),
                          ("chronic_conditions", "Condiciones crónicas"), ("medications", "Medicamentos"),
                          ("nutrition_notes", "Notas de nutrición")):
        if _txt(p.get(campo)):
            lineas.append(f"- {nombre}: {p[campo]}")
    if _txt(p.get("pool_access")):
        lineas.append(f"- Acceso a piscina: {p['pool_access']}")
    if p.get("treadmill_access") is not None:
        lineas.append(f"- Cinta de correr: {'sí' if p.get('treadmill_access') else 'no'}")
    fuerza = p.get("strength_profile") if isinstance(p.get("strength_profile"), dict) else {}
    if fuerza:
        lineas.append(f"- Fuerza: {fuerza.get('mode') or '?'}, objetivo {fuerza.get('goal') or '?'}, "
                      f"{fuerza.get('equipment') or '?'}")
    return "\n".join(lineas)


_DIAS_DE_LA_SEMANA = ("lunes", "martes", "miércoles", "jueves", "viernes", "sábado", "domingo")


def mensaje(entradas: list[dict], perfil: dict | None = None, respuestas: list[dict] | None = None,
            hoy: date | None = None) -> str:
    # Sin la fecha de hoy, «un viaje del 1 al 5 de octubre» se retiraba adivinando (prueba real
    # del 2026-10-11): el modelo no puede saber qué ya pasó.
    hoy = hoy or date.today()
    lineas = [f"Hoy es {_DIAS_DE_LA_SEMANA[hoy.weekday()]} {hoy.strftime('%d/%m/%Y')}.",
              "Campos del perfil (para al_perfil y para los avisos): " + ", ".join(CAMPOS_DEL_PERFIL), ""]
    if perfil is not None:
        lineas += [resumen_del_perfil(perfil), ""]
    lineas.append("Anotaciones:")
    for e in entradas:
        fecha = str(e.get("created_at") or "")[:10]
        cita = f' (dijo: «{e["quote"]}»)' if e.get("quote") else ""
        lineas.append(f'- id {e["id"]} · {e.get("category") or "otro"} · {fecha}: {e.get("text", "")}{cita}')
    if respuestas:
        lineas += ["", "Ya preguntaste y el atleta respondió (no vuelvas a preguntar; ordena ahora):"]
        for r in respuestas:
            lineas.append(f'- {r["pregunta"]} → {r["respuesta"]}')
    return "\n".join(lineas)


_NUMERO = re.compile(r"\d+(?:[.,]\d+)?")


def _numeros(texto: str) -> set[str]:
    return {n.replace(",", ".") for n in _NUMERO.findall(texto or "")}


def limpiar_respuestas(respuestas: Any) -> list[dict]:
    """Lo que manda la pantalla: como mucho 4 preguntas con su respuesta, en texto corto."""
    salida = []
    for r in respuestas or []:
        if not isinstance(r, dict):
            continue
        pregunta, respuesta = str(r.get("pregunta") or "").strip()[:300], str(r.get("respuesta") or "").strip()[:500]
        if pregunta and respuesta:
            salida.append({"pregunta": pregunta, "respuesta": respuesta})
    # Se recorta DESPUÉS de quitar las vacías: una sin contestar no le quita el sitio a otra.
    return salida[:MAX_PREGUNTAS]


def validar(propuesta: Revision | dict, entradas: list[dict], respuestas: list[dict] | None = None,
            ) -> tuple[list[dict], list[str]]:
    """Las acciones que se pueden enseñar, y lo que se descartó (para el registro)."""
    if isinstance(propuesta, dict):
        propuesta = Revision.model_validate(propuesta)
    por_id = {str(e["id"]): e for e in entradas}
    respondido = " ".join(r["respuesta"] for r in respuestas or [])
    usadas: set[str] = set()
    acciones, descartes = [], []
    for a in propuesta.acciones:
        ids = [str(i) for i in a.ids]
        if not ids or any(i not in por_id for i in ids) or any(i in usadas for i in ids) or len(set(ids)) != len(ids):
            descartes.append(f"{a.accion} {ids}: ids que no existen o repetidos")
            continue
        motivo = None
        if a.accion == "reescribir" and (len(ids) != 1 or not a.texto.strip()):
            motivo = "reescribir es una anotación con su texto nuevo"
        elif a.accion == "fusionar" and (len(ids) < 2 or not a.texto.strip()):
            motivo = "fusionar son dos o más anotaciones con su texto nuevo"
        elif a.accion == "al_perfil" and a.campo_del_perfil not in CAMPOS_DEL_PERFIL:
            motivo = f"campo del perfil desconocido: {a.campo_del_perfil!r}"
        elif a.accion in ("reescribir", "fusionar"):
            origen = " ".join(str(por_id[i].get("text", "")) + " " + str(por_id[i].get("quote") or "") for i in ids)
            inventados = _numeros(a.texto) - _numeros(origen + " " + respondido)
            if inventados:
                motivo = f"el texto nuevo trae números que no estaban: {sorted(inventados)}"
        if motivo:
            descartes.append(f"{a.accion} {ids}: {motivo}")
            continue
        categoria = a.categoria if a.categoria in CATEGORIAS else (por_id[ids[0]].get("category") or "otro")
        # Una preferencia de entrenamiento alimenta su plan: no deja de serlo al reescribirse.
        if any(por_id[i].get("category") == "preferencia" for i in ids) and a.accion in ("reescribir", "fusionar"):
            categoria = "preferencia"
        usadas.update(ids)
        if a.accion == "mantener":
            continue  # no hay nada que aceptar
        acciones.append({
            "ids": ids, "accion": a.accion, "texto": a.texto.strip(), "categoria": categoria,
            "campo_del_perfil": a.campo_del_perfil, "donde": CAMPOS_DEL_PERFIL.get(a.campo_del_perfil, ""),
            "pestana": PESTANA_DEL_CAMPO.get(a.campo_del_perfil, ""),
            "motivo": a.motivo.strip(),
            "originales": [{"id": i, "text": por_id[i].get("text", ""), "category": por_id[i].get("category")} for i in ids],
        })
    return acciones, descartes


def validar_avisos(propuesta: Revision, entradas: list[dict], acciones: list[dict] | None = None,
                   ) -> tuple[list[dict], list[str]]:
    ids_validos = {str(e["id"]) for e in entradas}
    # Lo que ya va «al perfil» con una acción no se repite como aviso (prueba real del
    # 2026-10-11: «los martes me gusta correr» salía dos veces).
    cubiertos = {(a["campo_del_perfil"], i) for a in acciones or [] if a["accion"] == "al_perfil" for i in a["ids"]}
    avisos, descartes, vistos = [], [], set()
    for a in propuesta.avisos_del_perfil:
        if a.campo not in CAMPOS_DEL_PERFIL or not a.que_hacer.strip():
            descartes.append(f"aviso {a.campo!r}: campo desconocido o sin texto")
            continue
        if a.ids and all((a.campo, str(i)) in cubiertos for i in a.ids):
            continue
        clave = (a.campo, a.que_hacer.strip().lower())
        if clave in vistos or len(avisos) >= MAX_AVISOS:
            continue
        vistos.add(clave)
        avisos.append({"campo": a.campo, "que_hacer": a.que_hacer.strip(), "donde": CAMPOS_DEL_PERFIL[a.campo],
                       "pestana": PESTANA_DEL_CAMPO[a.campo], "ids": [str(i) for i in a.ids if str(i) in ids_validos]})
    return avisos, descartes


def validar_preguntas(propuesta: Revision, entradas: list[dict]) -> list[dict]:
    ids_validos = {str(e["id"]) for e in entradas}
    preguntas = []
    for p in propuesta.preguntas:
        texto = p.texto.strip()
        if not texto:
            continue
        preguntas.append({"texto": texto[:300], "ids": [str(i) for i in p.ids if str(i) in ids_validos],
                          "opciones": [o.strip()[:80] for o in p.opciones if o.strip()][:4]})
        if len(preguntas) >= MAX_PREGUNTAS:
            break
    return preguntas


LeerConModelo = Callable[[str, str], Awaitable[tuple[Revision, dict]]]


async def _con_el_modelo(sistema: str, usuario: str) -> tuple[Revision, dict]:
    from langchain_core.callbacks.usage import get_usage_metadata_callback
    from langchain_core.messages import HumanMessage, SystemMessage

    from services.ai.ai_settings import AgentRole
    from services.ai.model_config import ModelSelector

    runnable = ModelSelector.get_llm(AgentRole.NOTEBOOK).with_structured_output(Revision)
    with get_usage_metadata_callback() as uso:
        propuesta = await runnable.ainvoke([SystemMessage(content=sistema), HumanMessage(content=usuario)])
        return propuesta, dict(uso.usage_metadata or {})


async def revisar(entradas: list[dict], perfil: dict | None = None, respuestas: Any = None,
                  leer: LeerConModelo | None = None) -> tuple[dict[str, Any], dict]:
    """(lo que se enseña, uso del modelo). Sólo las vigentes; no guarda nada.

    Devuelve `preguntas` (si el coach necesita saber algo antes: entonces nada más) o
    `acciones` y `avisos` (el cuaderno ordenado y lo que hay que cambiar en el perfil).
    """
    vigentes = [e for e in entradas or [] if (e.get("status") or "vigente") == "vigente" and e.get("id") is not None]
    vacio = {"preguntas": [], "acciones": [], "avisos": [], "descartadas": []}
    if len(vigentes) < 2:
        return {**vacio, "aviso": "Tu cuaderno tiene muy pocas anotaciones para revisarlo."}, {}
    aviso = ""
    if len(vigentes) > MAX_ENTRADAS:
        aviso = f"Se revisan las {MAX_ENTRADAS} más recientes de tus {len(vigentes)} anotaciones."
        vigentes = sorted(vigentes, key=lambda e: str(e.get("created_at") or ""))[-MAX_ENTRADAS:]
    respuestas = limpiar_respuestas(respuestas)
    propuesta, uso = await (leer or _con_el_modelo)(SISTEMA, mensaje(vigentes, perfil, respuestas))
    if isinstance(propuesta, dict):
        propuesta = Revision.model_validate(propuesta)
    # Con respuestas ya no se pregunta otra vez: lo que vuelva a preguntar se ignora.
    preguntas = [] if respuestas else validar_preguntas(propuesta, vigentes)
    if preguntas:
        return {**vacio, "preguntas": preguntas, "aviso": aviso}, uso
    acciones, descartes = validar(propuesta, vigentes, respuestas)
    avisos, descartes_avisos = validar_avisos(propuesta, vigentes, acciones) if perfil is not None else ([], [])
    return {"preguntas": [], "acciones": acciones, "avisos": avisos,
            "descartadas": descartes + descartes_avisos, "aviso": aviso}, uso


async def proponer(entradas: list[dict], leer: LeerConModelo | None = None) -> tuple[dict[str, Any], dict]:
    """La primera versión (F9, sin perfil ni preguntas). Se queda por las pruebas que la usan."""
    return await revisar(entradas, None, None, leer)
