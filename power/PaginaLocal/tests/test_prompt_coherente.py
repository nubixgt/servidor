"""El prompt no puede contradecirse a sí mismo, y no puede prohibir repetir.

Dos lecciones distintas, las dos de la misma noche de pruebas.

**La contradicción.** El bloque de disponibilidad decía que un bloque de
05:00-07:00 con `run, swim` admite las dos sesiones. Doce líneas más abajo,
bajo el encabezado `CRITICAL SCHEDULING RULES (MANDATORY — NEVER VIOLATE)`, la
regla 10 decía lo contrario: *"Only use TWO WorkoutDay entries for the same
date on the days explicitly listed as having 2 blocks. Every other day gets
exactly one entry"*. El modelo citó esa regla dos generaciones seguidas -- "la
regla operativa de una sola entrada en días de un bloque" -- y se leyó como
que se inventaba reglas. No se las inventaba: las leía.

**La prohibición de repetir.** El bloque de continuidad decía "no repitas la
misma semana". Eso es malo entrenando: un microciclo se repite dentro de su
mesociclo por diseño, y lo que progresa es la dosis, no la identidad de las
sesiones. Pedirle novedad al coach cada semana es pedirle que rompa la
adaptación, que necesita repetición para producirse.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.nodes.weekly_planner_node import (  # noqa: E402
    TRAINING_DECISION_USER_PROMPT,
)
from web_app.server import build_custom_planning_context  # noqa: E402

PERFIL = {
    "name": "T",
    "sports_preference": "triathlon",
    "available_days": ["Viernes", "Domingo"],
    "day_time_blocks": {
        # El viernes real del atleta: UN bloque de dos horas para dos deportes.
        "Viernes": [{"start": "05:00", "end": "07:00", "sports": ["Correr", "Nadar"]}],
        "Domingo": [{"start": "06:00", "end": "10:00", "sports": ["Correr", "Bici"]}],
    },
    "available_hours": 6,
}


class LasReglasCriticasNoContradicenLaTabla(unittest.TestCase):
    """Lo que va bajo "MANDATORY — NEVER VIOLATE" gana a todo lo demás.

    Si esa sección dice una cosa y el resto del prompt la contraria, el modelo
    obedece a la sección marcada como inviolable -- y con razón.
    """

    def setUp(self):
        self.contexto = build_custom_planning_context(PERFIL)

    def test_la_nota_de_bloques_sigue_diciendo_que_caben_varias(self):
        """Sigue permitiendo dos sesiones en un bloque -- eso no cambio."""
        self.assertIn("un bloque SI admite varias sesiones", self.contexto)
        self.assertIn("45' de carrera y 40' de natacion", self.contexto)

    def test_y_ahora_dice_que_un_bloque_PUEDE_QUEDARSE_VACIO(self):
        """El fallo de v7.2, con sus numeros: el atleta declaro tres bloques un
        lunes y uno los demas dias, y recibio tres micro-sesiones ese lunes.

        El texto solo decia cuando PUEDE anadir sesiones. Nunca dijo que no
        hiciera falta -- y una tabla de huecos sin permiso explicito para
        dejarlos vacios se lee como una rejilla que hay que rellenar."""
        self.assertIn("PERMISO, NO UNA OBLIGACION", self.contexto)
        self.assertIn("Dejar un bloque vacio es normal", self.contexto)
        # Y en la seccion que manda sobre todas las demas.
        criticas = self.contexto[self.contexto.index("CRITICAL SCHEDULING RULES"):]
        self.assertIn("PERMISSION, not a slot to fill", criticas)
        self.assertIn("never required", criticas)

    def test_ya_no_hay_ningunas_horas_declaradas_en_el_prompt(self):
        """La version anterior de esta prueba exigia que las horas declaradas
        se llamaran "CEILING, never a target". Era la mitad del arreglo.

        La otra mitad es que ese numero no deberia existir: lo escribia el
        atleta a mano, y no es una pregunta que pueda contestar bien -- no
        sabe cuantas horas necesita su plan, solo cuantas cree que le sobran.
        Peor, tenia un valor por defecto de 8.0, asi que quien no tocaba el
        campo mandaba al prompt "Total Available Weekly Hours: 8 hours net":
        un numero inventado con forma de medicion.

        El volumen sale de la carga cronica medida, la fase del macrociclo y
        el margen de progresion (`compute_weekly_volume_target`), y ese camino
        nunca paso por aqui."""
        self.assertNotIn("Total Available Weekly Hours", self.contexto)
        criticas = self.contexto[self.contexto.index("CRITICAL SCHEDULING RULES"):]
        self.assertIn("NOT asked how many hours", criticas)
        self.assertIn("what this athlete actually absorbs", criticas)

    def test_el_trabajo_de_apoyo_no_se_reduce_a_fuerza(self):
        """Enumerar "optional strength workouts" dentro de una linea marcada
        CRITICAL dejaba fuera pliometria, prehab, rehab y movilidad, que el
        esquema acepta desde v7."""
        for disciplina in ("plyometrics", "prehab", "rehab", "mobility"):
            with self.subTest(disciplina=disciplina):
                self.assertIn(disciplina, self.contexto)
        self.assertNotIn("and optional strength workouts", self.contexto)

    def test_y_las_reglas_criticas_NO_dicen_lo_contrario(self):
        """La regla 10 ataba las dobles al número de BLOQUES del día. El límite
        real son las HORAS del bloque."""
        criticas = self.contexto[self.contexto.index("CRITICAL SCHEDULING RULES"):]
        self.assertNotIn("Every other day gets exactly one entry", criticas)
        self.assertNotIn("on the days explicitly listed as having 2 blocks", criticas)

    def test_las_reglas_criticas_lo_dicen_igual_que_la_nota(self):
        criticas = self.contexto[self.contexto.index("CRITICAL SCHEDULING RULES"):]
        self.assertIn("BLOCK'S HOURS", criticas)
        self.assertIn("the number of blocks is not the limit", criticas)

    def test_lo_que_SI_sigue_siendo_inviolable(self):
        """Aflojar la regla equivocada no puede aflojar las que protegen al
        atleta: los días de descanso y los deportes permitidos por franja."""
        criticas = self.contexto[self.contexto.index("CRITICAL SCHEDULING RULES"):]
        self.assertIn('ONLY schedule training sessions on days marked as "Available: YES"', criticas)
        self.assertIn("absolutely NO training whatsoever", criticas)
        self.assertIn('ONLY use the sports listed in the "Allowed Sports"', criticas)
        self.assertIn("must fit in that block's hours", criticas)


class ConLaRejillaLasReglasHablanDeLaRejilla(unittest.TestCase):
    """El prompt archivado del atleta 2 (2026-09-11T09-22-29, decisión semanal).

    Su tabla era la de la v1.1 -- FIJO / POSIBLE --, y bajo "NEVER VIOLATE"
    seguían las reglas de las franjas: 'días marcados "Available: YES"', 'la
    columna "Allowed Sports"' y "A block is a PERMISSION". Ninguna de esas
    columnas estaba en su prompt, y la regla inviolable llamaba permiso a lo
    que la tabla de encima llamaba petición.
    """

    PERFIL_REJILLA = {
        "name": "T",
        "sports_preference": "triathlon",
        "available_days": ["Martes", "Domingo"],
        "sport_days": {"Nadar": {"Martes": "fijo"}, "Correr": {"Domingo": "posible"}},
    }

    def setUp(self):
        contexto = build_custom_planning_context(self.PERFIL_REJILLA)
        self.criticas = contexto[contexto.index("CRITICAL SCHEDULING RULES"):]

    def test_no_nombra_columnas_que_no_existen(self):
        self.assertNotIn("Available: YES", self.criticas)
        self.assertNotIn("Allowed Sports", self.criticas)
        self.assertNotIn("A block is a PERMISSION", self.criticas)

    def test_el_fijo_es_inviolable_y_el_posible_es_permiso(self):
        self.assertIn("Every FIJO appears on its day", self.criticas)
        self.assertIn("POSIBLE is a PERMISSION", self.criticas)
        self.assertIn("absolutely NO training whatsoever", self.criticas)


class RepetirSesionesEsEntrenar(unittest.TestCase):
    """Esto no es moda rápida: la adaptación necesita repetición.

    Un entrenador mantiene los anclajes de la semana -- el día de calidad, la
    tirada larga, la fuerza -- y progresa la dosis. Un prompt que le pide algo
    distinto cada semana le está pidiendo que rompa el mesociclo.
    """

    def test_ningun_prompt_prohibe_repetir(self):
        fuentes = {
            "decision": TRAINING_DECISION_USER_PROMPT,
            "contexto": build_custom_planning_context(PERFIL),
        }
        prohibiciones = (
            "no repitas", "nunca repitas", "do not repeat", "never repeat",
            "no uses la misma sesion", "evita repetir",
        )
        for nombre, texto in fuentes.items():
            bajo = texto.lower()
            for frase in prohibiciones:
                with self.subTest(fuente=nombre, frase=frase):
                    self.assertNotIn(frase, bajo)

    def test_y_el_bloque_de_continuidad_dice_que_repetir_es_correcto(self):
        from services.ai.langgraph.nodes.weekly_planner_node import _format_previous_plan
        from services.ai.langgraph.schemas.plan_outputs import (
            WeekBlock,
            WeeklyPlanPayload,
            WorkoutDay,
        )
        dia = WorkoutDay(date="2026-09-02", day_name_es="Miércoles", is_rest=False, sport="run",
                         focus="Tempo", purpose="p", adaptation="a",
                         total_duration_minutes=45, steps=[])
        payload = WeeklyPlanPayload(athlete_name="T", start_date="2026-09-02", zones_table="z",
                                    weeks=[WeekBlock(week_number=1, days=[dia])])
        texto = _format_previous_plan(payload, "2026-09-05")
        self.assertIn("es lo normal y lo correcto", texto)
        self.assertIn("DOSIS", texto)
        # Y lo que sí se sigue evitando, que es otra cosa.
        self.assertIn("reinventar la semana", texto)


class LaEtiquetaDeLaSesionNoMiente(unittest.TestCase):
    """"AM" y "PM" para dos sesiones de las cinco de la mañana.

    El atleta las veía así en su reloj: una "(AM)" y otra "(PM)", las dos en su
    bloque de 05:00-07:00. La segunda no es de la tarde: es la segunda.
    """

    def test_el_sufijo_es_un_ordinal_y_no_una_hora(self):
        from services.ai.langgraph.schemas.plan_outputs import slot_suffix
        self.assertEqual(slot_suffix("unica"), "")
        self.assertEqual(slot_suffix("am"), " (1ª)")
        self.assertEqual(slot_suffix("pm"), " (2ª)")
        self.assertEqual(slot_suffix("s3"), " (3ª)")
        self.assertEqual(slot_suffix("s4"), " (4ª)")

    def test_el_nombre_que_llega_al_reloj_lo_usa(self):
        from services.ai.langgraph.schemas.plan_outputs import TargetType, WorkoutDay, WorkoutStep
        from services.garmin.workout_builder import AthleteZoneConfig, build_garmin_workout
        dia = WorkoutDay(
            date="2026-08-28", day_name_es="Viernes", is_rest=False, sport="run",
            session_slot="pm", focus="Trote", purpose="p", adaptation="a",
            total_duration_minutes=30,
            steps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                               duration_value=1800, target_type=TargetType.HR_ZONE,
                               target_zone=2, description="rodaje")],
        )
        nombre = build_garmin_workout(dia, AthleteZoneConfig(hr_zones={2: (140, 153)}))["workoutName"]
        self.assertEqual(nombre, "Trote - 2026-08-28 (2ª)")
        self.assertNotIn("(PM)", nombre)

    def test_no_queda_ningun_AM_PM_suelto_en_el_codigo(self):
        """El fallo de esta familia es dejarse una copia.

        Se buscan las dos formas en que la etiqueta se producia: el diccionario
        que la mapeaba desde la franja, y la condicional de la plantilla. Los
        comentarios que EXPLICAN el fallo viejo no cuentan -- son justamente lo
        que hay que conservar."""
        raiz = Path(__file__).resolve().parents[1]
        produce_etiqueta = re.compile(
            r"""["']am["']\s*:\s*["']\s*\(AM\)"""      # {"am": " (AM)"}
            r"""|session_slot\s*==\s*['"]am['"]\s*%\}\s*\(AM\)"""  # jinja
        )
        sospechosos = []
        for ruta in [*(raiz / "services").rglob("*.py"),
                     *(raiz / "services").rglob("*.jinja2"),
                     raiz / "web_app/server.py",
                     raiz / "web_app/static/app.js",
                     *(raiz / "scripts").glob("*.py")]:
            for n, linea in enumerate(ruta.read_text(encoding="utf-8", errors="replace").splitlines(), 1):
                if produce_etiqueta.search(linea) and "_SUFIJOS_ANTIGUOS" not in linea:
                    sospechosos.append(f"{ruta.name}:{n}")
        self.assertEqual(sospechosos, [], f"algo sigue produciendo (AM)/(PM): {sospechosos}")

    def test_hay_UN_solo_orden_de_franjas(self):
        """Tres copias de la misma tupla son tres oportunidades de divergir."""
        raiz = Path(__file__).resolve().parents[1]
        definiciones = []
        for ruta in (raiz / "services").rglob("*.py"):
            if re.search(r'^_?SLOT_ORDER\s*(:[^=]+)?=\s*\(', ruta.read_text(encoding="utf-8"), re.M):
                definiciones.append(ruta.name)
        self.assertEqual(definiciones, ["plan_outputs.py"], f"redefinido en: {definiciones}")


if __name__ == "__main__":
    unittest.main()
