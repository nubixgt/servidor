"""Lo que el atleta paga en CADA mensaje no puede crecer con el tiempo.

## Qué se auditó

El chat del coach reenvía su contexto entero en cada pregunta. Medido sobre el
atleta real: 50.964 caracteres (~12.700 tokens) por mensaje. Casi todo está
acotado a propósito -- 16 turnos de conversación, 40 anotaciones de cuaderno,
UNA generación archivada con presupuesto por artefacto, 5.000 caracteres de
plan de temporada.

Quedaban tres cosas que crecían solas, ninguna urgente y las tres inevitables:

1. **El listado de semanas anteriores.** Una línea por semana pasada (~250
   caracteres), y el plan las acumula todas. A los seis meses son 26 líneas; al
   año, 52. Ahora: `MAX_PAST_WEEKS_LISTED`.

2. **El informe del asistente de salud**, que entraba ENTERO en el contexto del
   coach (`report_budget=None`). No es un dato quieto: cada consulta con el
   «Doctor» lo reescribe partiendo del anterior, así que su tendencia natural
   es crecer. Ahora: `_REPORT_BUDGET_CHAT`, el doble del informe real medido,
   así que hoy no corta nada -- pone un techo, no un recorte.

3. **`weekly_plan.json`**, que guardaba todas las semanas para siempre. Una
   pesa 38 KB, viaja entera PHP -> puente en cada mensaje del chat y vive en
   una columna JSON de MySQL. Ahora: `MAX_WEEKS_KEPT`.

El historial del atleta NO se toca: vive en `track_record.jsonl` (cada semana
cerrada con su ejecución real, que es de donde salen las gráficas) y en las
trazas archivadas. Lo que se acota es una copia de trabajo.

## Y dos campos del perfil que no llegaban

`travel_frequency` y `cross_training_open`: el atleta los rellena en su ficha,
viajaban hasta el planificador y se tiraban. Sólo los leía el asistente de
salud. Misma clase de fallo que la cinta, el plan, el informe y la ficha de
números -- un dato que existe, que otro consumidor sí recibe, y que a éste
nadie le pasaba.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import re
import sys
import tempfile
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import (  # noqa: E402
    MAX_PAST_WEEKS_LISTED,
    build_coach_context,
)


def _dia(fecha: str, *, descanso: bool) -> dict:
    """Un día válido para WorkoutDay. Los campos obligatorios van todos: el
    esquema real los exige y una semana a medias no probaría nada."""
    return {
        "date": fecha,
        "day_name_es": "Lunes",
        "is_rest": descanso,
        "sport": "rest" if descanso else "run",
        "focus": "Descanso" if descanso else "Z2",
        "purpose": "Una frase.",
        "adaptation": "Si viene cansado: parar.",
        "discipline": "descanso" if descanso else "carrera",
    }


def _semana(numero: int) -> dict:
    """Una semana mínima pero completa: el resumen cuenta sesiones y cita el
    porqué, así que las dos cosas tienen que existir."""
    return {
        "week_number": numero,
        "days": [
            _dia(f"2026-01-{numero:02d}", descanso=False),
            _dia(f"2026-02-{numero:02d}", descanso=True),
        ],
        "week_rationale": f"Motivo de la semana {numero}",
    }


class ElListadoDeSemanasAnterioresNoCreceSinFin(unittest.TestCase):
    def setUp(self):
        self.dir = Path(tempfile.mkdtemp())
        (self.dir / "weekly_plan.json").write_text(
            json.dumps({"weeks": [_semana(n) for n in range(1, 21)]}),
            encoding="utf-8",
        )

    def test_EL_TOPE_solo_se_listan_las_ultimas(self):
        texto = build_coach_context(self.dir, {})["context"]
        listadas = [n for n in range(1, 20) if f"- Semana {n}:" in texto]
        self.assertEqual(len(listadas), MAX_PAST_WEEKS_LISTED)
        # Las que se conservan son las RECIENTES: son de las que un entrenador
        # habla. La 19 es la anterior a la actual; la 1 es de hace cinco meses.
        self.assertIn("- Semana 19:", texto)
        self.assertNotIn("- Semana 1:", texto)

    def test_la_semana_ACTUAL_va_entera_y_no_cuenta_para_el_tope(self):
        """El tope es del resumen histórico. La semana que el atleta tiene
        ahora es la pregunta que viene a hacer y va completa, siempre."""
        texto = build_coach_context(self.dir, {})["context"]
        self.assertIn("Semana actual (la que el atleta tiene ahora) — COMPLETA", texto)
        self.assertIn("Motivo de la semana 20", texto)

    def test_lo_que_se_deja_fuera_se_DICE(self):
        """Recortar en silencio es cómo un modelo completa con seguridad lo que
        no vio. Si el atleta pregunta por una semana de hace meses, el
        entrenador tiene que poder decir que no la tiene delante."""
        texto = build_coach_context(self.dir, {})["context"]
        self.assertIn("11 semana(s) anterior(es) que no caben", texto)

    def test_sin_semanas_pasadas_no_se_emite_el_bloque(self):
        """Un atleta con una sola semana no debe pagar un encabezado vacío."""
        (self.dir / "weekly_plan.json").write_text(
            json.dumps({"weeks": [_semana(1)]}), encoding="utf-8")
        texto = build_coach_context(self.dir, {})["context"]
        self.assertNotIn("Semanas anteriores", texto)


class CadaCosaTIENE_UN_SOLO_NOMBRE(unittest.TestCase):
    """Cuatro bloques del contexto salían con el título dos veces.

    `render_health_block`, `render_notebook_block`, `render_checkin_block` y
    `render_constraints_block` escriben su propio encabezado dentro del texto,
    porque en el prompt de los planificadores son la única cabecera que hay.
    Aquí se les ponía otra encima:

        ## Antecedentes de salud declarados
        ```
        ## Antecedentes de salud declarados por el atleta

    Dos nombres para una misma cosa, en el prompt de un modelo al que además se
    le advierte de que un encabezado puede no llamarse como el atleta lo llama.
    """

    def _contexto(self):
        directorio = Path(tempfile.mkdtemp())
        # Una lesion declarada, que es lo que SIGUE alimentando el bloque
        # despues de la v1.2. El informe del asistente ya no existe.
        perfil = {"current_injuries": [{"location": "Rodilla", "status": "activa"}]}
        return build_coach_context(directorio, perfil)["context"]

    def test_EL_FALLO_el_bloque_de_salud_no_se_titula_dos_veces(self):
        cabeceras = re.findall(r"^## (.+)$", self._contexto(), re.M)
        salud = [h for h in cabeceras if h.startswith("Antecedentes de salud")]
        self.assertEqual(len(salud), 1, f"encabezados de salud: {salud}")

    def test_se_queda_el_nombre_DEL_BLOQUE_no_el_del_envoltorio(self):
        """El del bloque es su nombre canónico: el mismo que ven los
        planificadores, y al que apunta el inventario del system prompt."""
        self.assertIn("## Antecedentes de salud declarados por el atleta", self._contexto())

    def test_ningun_encabezado_del_contexto_se_repite(self):
        cabeceras = re.findall(r"^## (.+)$", self._contexto(), re.M)
        repetidos = [h for h in set(cabeceras) if cabeceras.count(h) > 1]
        self.assertEqual(repetidos, [])

    def test_un_bloque_que_era_SOLO_su_encabezado_no_emite_seccion(self):
        from services.ai.coach_chat import _section
        self.assertEqual(_section("Lo que sea", "## Un titulo y nada mas"), "")

    def test_un_bloque_sin_encabezado_propio_conserva_el_nuestro(self):
        from services.ai.coach_chat import _section
        self.assertTrue(_section("Quién es el atleta", "Jose, 34").startswith("## Quién es el atleta"))


class ElInformeDelDoctorYaNoExiste(unittest.TestCase):
    """Aqui habia tres pruebas fijando el TOPE del informe del asistente de
    salud dentro del chat. La v1.2 lo retiro entero (Database/017), asi que lo
    que hay que fijar ya no es su techo sino su AUSENCIA: un perfil viejo que
    todavia lo trajera no puede volver a colarlo en el prompt."""

    def test_un_informe_heredado_no_entra_en_el_contexto(self):
        largo = "DATO MEDICO. " * 2000
        texto = build_coach_context(Path(tempfile.mkdtemp()),
                                    {"doctor_performance_report": largo})["context"]
        self.assertNotIn("DATO MEDICO", texto)

    def test_ni_el_tamizaje_ni_la_casilla_de_propenso(self):
        texto = build_coach_context(Path(tempfile.mkdtemp()), {
            "par_q_flags": {"q2": True},
            "injury_prone": True,
        })["context"]
        self.assertNotIn("propenso", texto.lower())
        self.assertNotIn("tamizaje", texto.lower())


class ElPlanSemanalNoGuardaSemanasParaSiempre(unittest.TestCase):
    def _payload(self, numero: int):
        from web_app.server import WeeklyPlanPayload
        return WeeklyPlanPayload(
            athlete_name="Test", start_date="2026-01-01", zones_table="",
            weeks=[_semana(numero)],
        )

    def test_EL_TOPE_veinte_generaciones_dejan_MAX_WEEKS_KEPT(self):
        from web_app.server import MAX_WEEKS_KEPT, _append_week
        plan = None
        for n in range(1, 21):
            plan = _append_week(plan, self._payload(n))
        self.assertEqual(len(plan.weeks), MAX_WEEKS_KEPT)

    def test_la_NUMERACION_del_macrociclo_no_se_desplaza(self):
        """El fallo que tirar semanas por delante podría causar: si el número
        siguiente se calculara contando las que quedan, la semana 20 del
        macrociclo pasaría a llamarse semana 13 y todo lo que cuelga de ese
        número dejaría de cuadrar."""
        from web_app.server import _append_week
        plan = None
        for n in range(1, 21):
            plan = _append_week(plan, self._payload(n))
        self.assertEqual(plan.weeks[-1].week_number, 20)

    def test_la_semana_recien_generada_conserva_su_porque(self):
        """`week_rationale` es lo que el atleta lee para saber por qué su
        semana es así, y es justo lo que un model_copy mal hecho se lleva."""
        from web_app.server import _append_week
        plan = _append_week(self._payload(1), self._payload(2))
        self.assertEqual(plan.weeks[-1].week_rationale, "Motivo de la semana 2")


class LosDosCamposDelPerfilQueNoLlegaban(unittest.TestCase):
    def _contexto(self, **campos) -> str:
        from web_app.server import build_custom_planning_context
        base = {"sports_preference": "run", "available_days": ["Lunes"]}
        return build_custom_planning_context({**base, **campos})

    def test_EL_FALLO_viajar_con_frecuencia_llega_al_planificador(self):
        texto = self._contexto(travel_frequency="frecuente")
        self.assertIn("Travels FREQUENTLY", texto)

    def test_viajar_de_vez_en_cuando_no_se_lee_igual_que_viajar_siempre(self):
        ocasional = self._contexto(travel_frequency="ocasional")
        self.assertIn("Travels occasionally", ocasional)
        self.assertNotIn("Travels FREQUENTLY", ocasional)

    def test_no_declarar_viajes_no_inventa_una_restriccion(self):
        texto = self._contexto(travel_frequency="")
        self.assertNotIn("Travel", texto)

    def test_EL_FALLO_negarse_al_cruzado_es_una_PROHIBICION(self):
        """El caso que de verdad informa: `cross_training_open` nace en True en
        el formulario, así que el False es una negativa explícita del atleta. Sin
        ella el planificador le cambia carrera por bici creyendo que ayuda."""
        texto = self._contexto(cross_training_open=False)
        self.assertIn("NOT open to cross-training", texto)
        self.assertIn("do not swap the sport", texto)

    def test_aceptarlo_es_un_permiso_SUBORDINADO_no_una_invitacion(self):
        """Decirle "es abierto al cruzado" a secas contradiría dos reglas que
        ya están en este mismo prompt: la disciplina objetivo y los deportes
        que cada día permite (por franja en los perfiles viejos, por la
        rejilla en los de la v1.1 -- de ahí que hable del día y no del bloque)."""
        texto = self._contexto(cross_training_open=True)
        self.assertIn("Open to cross-training", texto)
        self.assertIn("the sports allowed that day permit", texto)


if __name__ == "__main__":
    unittest.main()
