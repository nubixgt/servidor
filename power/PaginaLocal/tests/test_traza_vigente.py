"""El entrenador no explica un plan que el atleta ya borró.

## De dónde sale

Borrar el plan NO borra su traza de razonamiento, y eso es deliberado: la
traza explica por qué se escribió, y además guarda la serie de forma física
del atleta (`fitness_series.json`, `load_state.json`, `recovery.json`) que es
lo que dibuja el historial del panel. Borrarla con el plan destruiría datos
del ATLETA para deshacerse de datos del PLAN. Está fijado por
`ReasoningIsItsOwnAction` en tests/test_deletion_scope.py.

Lo que faltaba era lo otro: que quien la LEE compruebe que sigue valiendo.

`build_coach_context` tomaba la traza más reciente sin preguntar si su plan
seguía existiendo. Así que un atleta que borraba su semana y no generaba otra
abría el chat y su entrenador le explicaba, con detalle y con seguridad, la
semana recién borrada -- `week_intents.json` lleva las decisiones día a día y
`synthesis.md` el análisis que las justifica.

No afecta a la generación del plan siguiente: ningún nodo del grafo lee el
archivo de razonamiento (se comprueba abajo, para que siga siendo verdad).

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import tempfile
import unittest
from datetime import date, datetime
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import build_coach_context  # noqa: E402
from services.ai.reasoning_archive import save_reasoning  # noqa: E402

TODAY = date(2026, 9, 14)
PERFIL = {"name": "Jose Ines Castillo", "sports_preference": "run", "available_hours": 10}

# Lo bastante distintivo como para no aparecer por casualidad en el contexto.
DECISION_DE_LA_SEMANA = "VO2max el miercoles porque el hombro aguanta 45 minutos"
ANALISIS_DE_LA_TEMPORADA = "La base aerobica sostiene un medio maraton en marzo"


def _guardar_traza(storage: Path, kind: str, texto: str, cuando: datetime) -> None:
    """Una traza como la que deja una generación real, con su `kind`."""
    save_reasoning(
        storage,
        {
            "synthesis_result": texto,
            "week_intents": [{"date": "2026-09-16", "focus": texto}],
        },
        kind=kind,
        timestamp=cuando,
    )


def _con_perfil(poblar):
    """Un directorio de perfil temporal, ya poblado."""
    tmp = tempfile.TemporaryDirectory()
    storage = Path(tmp.name)
    poblar(storage)
    return tmp, storage


def _contexto(storage: Path) -> str:
    return build_coach_context(storage, PERFIL, today=TODAY)["context"]


class ElEntrenadorNoHablaDeUnPlanBorrado(unittest.TestCase):
    def setUp(self):
        self._tmp = tempfile.TemporaryDirectory()
        self.storage = Path(self._tmp.name)
        self.addCleanup(self._tmp.cleanup)
        # Primero la temporada, después la semana: es el orden real, y hace que
        # la semana sea la traza "más reciente".
        _guardar_traza(self.storage, "season", ANALISIS_DE_LA_TEMPORADA, datetime(2026, 9, 1, 8, 0, 0))
        _guardar_traza(self.storage, "week", DECISION_DE_LA_SEMANA, datetime(2026, 9, 14, 8, 0, 0))
        (self.storage / "season_plan.md").write_text("# Plan de Temporada", encoding="utf-8")
        (self.storage / "weekly_plan.json").write_text('{"weeks": []}', encoding="utf-8")

    def test_con_el_plan_en_pie_se_lee_la_traza_de_la_semana(self):
        """El comportamiento de siempre, que no debe cambiar."""
        self.assertIn(DECISION_DE_LA_SEMANA, _contexto(self.storage))

    def test_EL_FALLO_borrada_la_semana_su_razonamiento_deja_de_leerse(self):
        (self.storage / "weekly_plan.json").unlink()
        contexto = _contexto(self.storage)
        self.assertNotIn(
            DECISION_DE_LA_SEMANA, contexto,
            "El entrenador sigue explicando la semana que el atleta borro",
        )

    def test_y_se_cae_a_la_traza_de_temporada_que_si_sigue_vigente(self):
        """Borrar la semana no deja al entrenador sin contexto: el macrociclo
        sigue ahí y su razonamiento sigue describiendo algo que existe."""
        (self.storage / "weekly_plan.json").unlink()
        self.assertIn(ANALISIS_DE_LA_TEMPORADA, _contexto(self.storage))

    def test_borrado_todo_no_se_lee_ninguna(self):
        (self.storage / "weekly_plan.json").unlink()
        (self.storage / "season_plan.md").unlink()
        contexto = _contexto(self.storage)
        self.assertNotIn(DECISION_DE_LA_SEMANA, contexto)
        self.assertNotIn(ANALISIS_DE_LA_TEMPORADA, contexto)

    def test_borrada_solo_la_temporada_la_semana_se_sigue_leyendo(self):
        (self.storage / "season_plan.md").unlink()
        self.assertIn(DECISION_DE_LA_SEMANA, _contexto(self.storage))

    def test_una_traza_de_tipo_desconocido_no_se_descarta(self):
        """No saber si sigue vigente no es saber que no lo esta.

        Callar de mas dejaria al entrenador sin contexto que si tenia derecho
        a leer, y un `kind` nuevo no deberia romper el chat en silencio."""
        vacio = tempfile.TemporaryDirectory()
        self.addCleanup(vacio.cleanup)
        storage = Path(vacio.name)
        _guardar_traza(storage, "experimento-nuevo", DECISION_DE_LA_SEMANA, datetime(2026, 9, 14, 8, 0, 0))
        self.assertIn(DECISION_DE_LA_SEMANA, _contexto(storage))


class LaTrazaNoAlimentaLaGeneracion(unittest.TestCase):
    """La preocupación era «no sesgar planes nuevos con razonamiento
    incorrecto». Hoy eso no puede pasar porque NADA del grafo lee el archivo.

    Se comprueba en vez de confiar: es una propiedad fácil de perder sin querer
    -- basta un nodo que quiera «recordar lo que decidimos la vez pasada» --
    y el día que se pierda, el plan siguiente empezaría a heredar el
    razonamiento del anterior sin que nadie lo hubiera decidido.
    """

    def test_ningun_nodo_ni_flujo_importa_el_archivo_de_razonamiento(self):
        raiz = Path(__file__).resolve().parents[1] / "services" / "ai" / "langgraph"
        culpables = []
        for fichero in raiz.rglob("*.py"):
            texto = fichero.read_text(encoding="utf-8", errors="replace")
            for linea in texto.splitlines():
                despojada = linea.strip()
                if despojada.startswith("#"):
                    continue  # los comentarios SÍ la mencionan, y está bien
                if "reasoning_archive" in despojada or "load_reasoning_entry" in despojada:
                    culpables.append(f"{fichero.name}: {despojada}")
        self.assertEqual(
            culpables, [],
            "Un nodo de generacion empezo a leer el archivo de razonamiento: "
            "el plan nuevo heredaria el razonamiento del anterior",
        )


if __name__ == "__main__":
    unittest.main()
