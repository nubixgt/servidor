"""El atleta manda sobre la forma de su semana.

## De dónde salió

Beta de septiembre de 2026, cuatro atletas. Cuatro comentarios distintos sobre
lo mismo, y el más claro: *"aveces quieren nadar y la IA no dejó natación para
ese día. cuando colocan ejercicio y horario, esperan que eso les ponga la IA"*.

No era un fallo del modelo. El perfil sólo sabía expresar PERMISO —
`day_time_blocks` guarda franjas de reloj— y el prompt se lo decía con todas
las letras: *"UN BLOQUE ES UN PERMISO, NO UNA OBLIGACIÓN"*. El modelo vio un
hueco y decidió, correctamente según sus reglas, no usarlo. Nadie se lo había
pedido.

## Lo que se fija aquí

1. **Un perfil viejo sigue funcionando exactamente igual.** Los cinco atletas
   de la beta no tienen rejilla, y hasta que la contesten el prompt y el
   validador tienen que comportarse como antes. Es la garantía que más importa
   porque su fallo es silencioso.
2. **Un `fijo` que no aparece se marca.** Es la contraparte de
   `day_not_available`: aquella caza entrenar donde no se puede; ésta caza no
   entrenar donde se ha pedido.
3. **Un `posible` que no se usa NO se marca.** No usarlo es una decisión de
   entrenador legítima, y convertirlo en obligación reintroduce el fallo de
   v7.2: un atleta con tres huecos el lunes recibió tres micro-sesiones.
4. **Trail es terreno, no deporte.** Un trail fijo se satisface con una sesión
   de carrera — la misma regla que ya aplica `_canonical_sports_for_day`.

Correr con:  python -m unittest discover -s tests
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from web_app.server import (  # noqa: E402
    _multi_block_note,
    _rejilla_por_dia,
    build_availability_constraints,
    render_availability_table,
    render_sport_days_table,
)

# El caso textual del atleta: "los domingos puedo hacer bici y correr, pero
# para mí, bici que lo decida el coach".
REJILLA = {
    "Correr": {"Lunes": "fijo", "Domingo": "fijo"},
    "Bici": {"Domingo": "posible", "Miércoles": "posible"},
    "Nadar": {"Martes": "fijo", "Jueves": "posible"},
    "Fuerza": {"Viernes": "posible"},
}

# Un perfil de los que ya existen: franjas de reloj, sin rejilla.
PERFIL_VIEJO = {
    "available_days": ["Lunes", "Miércoles"],
    "day_time_blocks": {
        "Lunes": [{"start": "05:30", "end": "07:00", "sports": ["Correr", "Nadar"]}],
        "Miércoles": [{"start": "18:00", "end": "20:00", "sports": ["Bici"]}],
    },
}


class UnPerfilViejoNoCambia(unittest.TestCase):
    """La garantía cuyo fallo sería silencioso."""

    def test_sin_rejilla_se_usa_la_tabla_de_franjas_de_siempre(self):
        tabla = render_availability_table(PERFIL_VIEJO)
        self.assertIn("ATHLETE DAILY AVAILABILITY", tabla)
        self.assertIn("05:30-07:00", tabla)
        self.assertNotIn("LO QUE EL ATLETA PIDE", tabla)

    def test_sin_rejilla_la_nota_sigue_hablando_de_permisos(self):
        nota = _multi_block_note(PERFIL_VIEJO)
        self.assertIn("UN BLOQUE ES UN PERMISO", nota)
        self.assertNotIn("COMO SE LEE ESTA TABLA", nota)

    def test_sin_rejilla_no_hay_peticiones_que_comprobar(self):
        """Un perfil que no ha pedido nada no puede tener nada sin cumplir."""
        limites = build_availability_constraints(PERFIL_VIEJO)
        self.assertEqual(limites["sport_days_fijos"], {})

    def test_una_rejilla_vacia_o_rota_no_activa_el_camino_nuevo(self):
        for basura in ({}, None, [], "Lunes", {"Correr": "Lunes"}, {"Correr": {"Lunes": "quizá"}}):
            with self.subTest(basura=basura):
                perfil = {**PERFIL_VIEJO, "sport_days": basura}
                self.assertEqual(_rejilla_por_dia(perfil), {})
                self.assertIn("ATHLETE DAILY AVAILABILITY", render_availability_table(perfil))


class LaTablaDicePeticionYNoPermiso(unittest.TestCase):
    def setUp(self):
        self.perfil = {"available_days": [], "sport_days": REJILLA}
        self.tabla = render_sport_days_table(self.perfil)

    def test_separa_lo_pedido_de_lo_permitido(self):
        self.assertIn("LO QUE EL ATLETA PIDE", self.tabla)
        self.assertIn("FIJO (lo ha pedido)", self.tabla)
        self.assertIn("POSIBLE (decides tu)", self.tabla)

    def test_el_domingo_lleva_correr_fijo_y_bici_posible(self):
        """El caso textual del atleta, en la fila que le corresponde."""
        fila = next(l for l in self.tabla.split("\n") if l.startswith("Domingo"))
        pedido, posible = fila.split("|")[1], fila.split("|")[2]
        self.assertIn("Correr", pedido)
        self.assertNotIn("Bici", pedido)
        self.assertIn("Bici", posible)

    def test_un_dia_sin_nada_se_dice_descanso(self):
        fila = next(l for l in self.tabla.split("\n") if l.startswith("Sábado"))
        self.assertIn("descanso", fila)

    def test_la_nota_deja_la_frontera_donde_estaba(self):
        """El deporte y el día son suyos; el volumen y la intensidad, nuestros."""
        nota = _multi_block_note(self.perfil)
        self.assertIn("COMO SE LEE ESTA TABLA", nota)
        self.assertIn("VOLUMEN Y LA INTENSIDAD", nota)
        self.assertNotIn("UN BLOQUE ES UN PERMISO", nota)

    def test_sin_ningun_fijo_se_le_dice_que_decide_el(self):
        perfil = {"sport_days": {"Bici": {"Lunes": "posible"}}}
        self.assertIn("no ha marcado ningun dia como fijo", _multi_block_note(perfil))


class SoloSeExigenLosFijos(unittest.TestCase):
    def setUp(self):
        self.limites = build_availability_constraints({"available_days": [], "sport_days": REJILLA})
        self.peticiones = self.limites["sport_days_fijos"]

    def test_los_fijos_viajan_en_claves_canonicas(self):
        """El validador compara contra `day.sport`, que es `run`/`bike`/`swim`."""
        self.assertEqual(self.peticiones["Lunes"], ["run"])
        self.assertEqual(self.peticiones["Martes"], ["swim"])
        self.assertEqual(self.peticiones["Domingo"], ["run"])

    def test_un_posible_nunca_entra_como_peticion(self):
        """El miércoles sólo tiene bici en `posible`: no se exige nada."""
        self.assertNotIn("Miércoles", self.peticiones)
        self.assertNotIn("Jueves", self.peticiones)
        self.assertNotIn("Viernes", self.peticiones)

    def test_trail_fijo_se_satisface_corriendo(self):
        """Trail es terreno, no deporte: un plan no emite `sport="trail"`."""
        limites = build_availability_constraints(
            {"sport_days": {"Trail": {"Sábado": "fijo"}}})
        self.assertEqual(limites["sport_days_fijos"]["Sábado"], ["run"])


class ElValidadorCazaElFijoQueFalta(unittest.TestCase):
    """La regla nueva, contra el validador de verdad."""

    def _semana(self, dias):
        from services.ai.langgraph.schemas.plan_outputs import (
            WeekBlock,
            WeeklyPlanPayload,
            WorkoutDay,
            WorkoutStep,
        )
        paso = WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                           duration_value=1800, description="30'")
        construidos = []
        for fecha, nombre, deporte in dias:
            construidos.append(WorkoutDay(
                date=fecha, day_name_es=nombre, is_rest=deporte is None,
                sport=deporte or "rest", focus="x", purpose="x", adaptation="x",
                total_duration_minutes=0.0 if deporte is None else 30.0,
                steps=[] if deporte is None else [paso],
            ))
        return WeeklyPlanPayload(
            athlete_name="Atleta", start_date="2026-09-14", zones_table="Z1 …",
            weeks=[WeekBlock(week_number=1, days=construidos)],
        )

    def setUp(self):
        # Lunes 2026-09-14 .. domingo 2026-09-20
        self.nombres = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"]
        self.fechas = [f"2026-09-{14 + i}" for i in range(7)]
        self.week_dates = [{"date": f, "day_name_es": n}
                           for f, n in zip(self.fechas, self.nombres, strict=True)]
        self.limites = {
            "days": {n: {"available": True, "hours": None, "sports": None, "blocks": []}
                     for n in self.nombres},
            "weekly_hours_budget": None,
            "sport_days_fijos": {"Martes": ["swim"]},
        }

    def _tipos(self, plan):
        from services.ai.langgraph.validation.plan_validator import validate_weekly_plan
        informe = validate_weekly_plan(plan, self.limites, self.week_dates)
        return [v["type"] for v in informe["violations"]]

    def test_se_marca_cuando_el_martes_no_lleva_natacion(self):
        plan = self._semana([(f, n, "run" if n == "Martes" else None)
                             for f, n in zip(self.fechas, self.nombres, strict=True)])
        self.assertIn("sport_requested_missing", self._tipos(plan))

    def test_no_se_marca_cuando_si_lo_lleva(self):
        plan = self._semana([(f, n, "swim" if n == "Martes" else None)
                             for f, n in zip(self.fechas, self.nombres, strict=True)])
        self.assertNotIn("sport_requested_missing", self._tipos(plan))

    def test_no_vale_ponerlo_otro_dia(self):
        """Pidió nadar el MARTES. Nadar el jueves no es lo que pidió."""
        plan = self._semana([(f, n, "swim" if n == "Jueves" else None)
                             for f, n in zip(self.fechas, self.nombres, strict=True)])
        self.assertIn("sport_requested_missing", self._tipos(plan))

    def test_un_dia_de_descanso_no_satisface_una_peticion(self):
        plan = self._semana([(f, n, None) for f, n in zip(self.fechas, self.nombres, strict=True)])
        self.assertIn("sport_requested_missing", self._tipos(plan))

    def test_sin_peticiones_la_regla_nunca_dispara(self):
        self.limites["sport_days_fijos"] = {}
        plan = self._semana([(f, n, None) for f, n in zip(self.fechas, self.nombres, strict=True)])
        self.assertNotIn("sport_requested_missing", self._tipos(plan))

    def test_no_se_exige_un_dia_que_no_esta_en_el_bloque(self):
        """Atleta 2, 2026-09-23: su primera semana iba de miércoles a domingo y se le
        exigió la fuerza FIJA del lunes y del martes. Dos reparaciones por IA (gpt-5.2,
        ~11.000 tokens de salida cada una) para algo imposible de cumplir."""
        self.limites["sport_days_fijos"] = {"Lunes": ["strength"], "Martes": ["strength"], "Jueves": ["swim"]}
        miercoles_a_domingo = list(zip(self.fechas[2:], self.nombres[2:], strict=True))
        self.week_dates = [{"date": f, "day_name_es": n} for f, n in miercoles_a_domingo]
        plan = self._semana([(f, n, "swim" if n == "Jueves" else None) for f, n in miercoles_a_domingo])
        self.assertNotIn("sport_requested_missing", self._tipos(plan))
        # El jueves sí está en el bloque: sin su natación, se sigue exigiendo.
        plan = self._semana([(f, n, None) for f, n in miercoles_a_domingo])
        self.assertEqual(self._tipos(plan).count("sport_requested_missing"), 1)

    def test_no_se_repara_en_tier_1(self):
        """Poner un día a descanso es determinista; añadir una sesión es criterio.

        Si entrara en `_TIER1_FIXABLE_TYPES`, el arreglo automático tendría que
        inventar duración e intensidad, que es justo lo que la capa 1 no hace.
        """
        from services.ai.langgraph.validation.plan_validator import _TIER1_FIXABLE_TYPES
        self.assertNotIn("sport_requested_missing", _TIER1_FIXABLE_TYPES)


if __name__ == "__main__":
    unittest.main()
