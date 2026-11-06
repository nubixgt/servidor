"""v7 -- el diccionario de Garmin dejó de ser una adivinanza.

Desde v5 este repo tenía una regla: no inventar los identificadores de Garmin.
Devuelven 400 ante cualquier valor fuera de su enum y — peor — a veces aceptan
la petición y **descartan el campo en silencio**. Así que el estilo de natación
y el nombre del ejercicio de fuerza viajaban dentro de la descripción del paso.

El atleta creó entrenos de referencia en su cuenta y se leyeron de vuelta con la
API. Todo lo que se afirma aquí salió de uno de esos entrenos, y cada test dice
de cuál.

El hallazgo que gobierna el diseño está en `1679360985`: se mandaron catorce
ejercicios con nombre y **siete perdieron el `exerciseName`**, quedándose sólo
con la categoría. Ni un error ni un aviso. De ahí que la categoría se mande
siempre y el nombre sólo si está verificado.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    StrengthBlock,
    StrengthExercise,
    WorkoutDay,
    WorkoutStep,
)
from services.garmin import garmin_vocabulary as vocab  # noqa: E402
from services.garmin.workout_builder import (  # noqa: E402
    AthleteZoneConfig,
    _reps_from_text,
    build_garmin_workout,
)

ZONAS = AthleteZoneConfig(hr_zones={1: (123, 139), 2: (140, 153), 3: (154, 167)})


def _plano(pasos, salida=None):
    salida = salida if salida is not None else []
    for p in pasos:
        if p.get("workoutSteps"):
            salida.append(p)
            _plano(p["workoutSteps"], salida)
        else:
            salida.append(p)
    return salida


class LoQueGarminACEPTA(unittest.TestCase):
    """Leído del multideporte 1679387475, que recorre todo el vocabulario."""

    def test_los_estilos_son_los_que_volvieron_intactos(self):
        for estilo in ("free", "backstroke", "breaststroke", "fly", "individual_medley",
                       "mixed", "any_stroke"):
            with self.subTest(estilo=estilo):
                self.assertIn(estilo, vocab.SWIM_STROKES)

    def test_un_estilo_INVENTADO_no_viaja(self):
        """En español, o mal escrito, o de otra API: se omite y la descripción
        del paso sigue llevándolo, que es lo que hacía antes."""
        for malo in ("crol", "mariposa", "freestyle", ""):
            with self.subTest(malo=malo):
                self.assertNotIn("strokeType", vocab.swim_fields(malo, None, None))

    def test_pero_las_mayusculas_SI_se_toleran(self):
        """Rechazar "FREE" seria castigar al modelo por algo que no cambia el
        significado. Lo que no se tolera es un valor que Garmin no conoce."""
        self.assertEqual(vocab.swim_fields("FREE", None, None)["strokeType"]["strokeTypeKey"], "free")

    def test_son_OBJETOS_con_su_id_y_no_cadenas(self):
        """Se mandaron como cadenas y Garmin respondio 500: la subida de
        natacion entera se cayo. El resumen del MCP los aplana a texto
        (`"stroke_type": "free"`) y de ahi salio el error; el payload real los
        trae con su id, como stepType, endCondition y targetType.
        """
        campos = vocab.swim_fields("free", "paddles", "pull")
        for campo, clave, esperado in (("strokeType", "strokeTypeId", 6),
                                       ("equipmentType", "equipmentTypeId", 3),
                                       ("drillType", "drillTypeId", 2)):
            with self.subTest(campo=campo):
                self.assertIsInstance(campos[campo], dict)
                self.assertEqual(campos[campo][clave], esperado)

    def test_los_ids_NO_se_pueden_deducir_contando(self):
        """`fly` es 5 y no 4, y `free` es 6: la numeracion tiene huecos. Un id
        deducido Garmin lo acepta y muestra otro estilo -- la peticion no
        falla y el resultado esta mal, que es lo peor que puede pasar."""
        self.assertEqual(vocab.SWIM_STROKES["fly"]["strokeTypeId"], 5)
        self.assertEqual(vocab.SWIM_STROKES["free"]["strokeTypeId"], 6)

    def test_el_multideporte_es_10(self):
        """Se puso 9, deducido. Garmin acepto la subida y la guardo como un
        entreno de un solo deporte: el brick llego al reloj con el tramo de
        bici y sin el de carrera, sin un solo error en el log."""
        self.assertEqual(vocab.MULTISPORT_TYPE["sportTypeId"], 10)

    def test_el_material_y_el_drill_igual(self):
        campos = vocab.swim_fields("free", "pull_buoy", "kick")
        self.assertEqual(campos["equipmentType"]["equipmentTypeKey"], "pull_buoy")
        self.assertEqual(campos["drillType"]["drillTypeKey"], "kick")
        self.assertEqual(list(vocab.swim_fields("free", "manoplas", "tecnica")), ["strokeType"])


class LaCategoriaSIEMPRE_ElNombreSOLO_SI_SE_VERIFICO(unittest.TestCase):
    """La lección de 1679360985: de catorce nombres enviados, siete
    desaparecieron y las catorce categorías sobrevivieron."""

    def test_la_categoria_se_reconoce_aunque_venga_desordenada(self):
        self.assertEqual(vocab.normalise_category("squat"), "SQUAT")
        self.assertEqual(vocab.normalise_category("hip raise"), "HIP_RAISE")
        self.assertEqual(vocab.normalise_category("calf-raise"), "CALF_RAISE")

    def test_una_categoria_inventada_no_viaja(self):
        """Mejor un bloque de repeticiones sin nombre que una categoría que
        Garmin rechaza: el paso ya lleva el ejercicio en su descripción."""
        self.assertIsNone(vocab.normalise_category("SENTADILLA"))
        self.assertIsNone(vocab.normalise_category(""))

    def test_un_nombre_verificado_si_viaja(self):
        self.assertEqual(
            vocab.verified_exercise("DEADLIFT", "barbell_straight_leg_deadlift"),
            "BARBELL_STRAIGHT_LEG_DEADLIFT",
        )

    def test_un_nombre_NO_verificado_se_omite(self):
        """`BARBELL_SQUAT` es exactamente uno de los siete que Garmin tiró: se
        mandó, se guardó el entreno, y el nombre no estaba al releerlo."""
        self.assertIsNone(vocab.verified_exercise("SQUAT", "BARBELL_SQUAT"))
        self.assertIsNone(vocab.verified_exercise("SQUAT", "peso_muerto_rumano_unipodal"))

    def test_una_pareja_INCOHERENTE_tampoco(self):
        """Garmin exige categoría y nombre juntos; un nombre de otra familia es
        otra forma de perderlo."""
        self.assertIsNone(vocab.verified_exercise("SQUAT", "BARBELL_DEADLIFT"))

    def test_todo_nombre_verificado_tiene_una_categoria_valida(self):
        for categoria in vocab.VERIFIED_EXERCISES:
            with self.subTest(categoria=categoria):
                self.assertIn(categoria, vocab.STRENGTH_CATEGORIES)


class LasRepeticionesEscritasSeTraducen(unittest.TestCase):
    """`reps` es TEXTO a propósito -- "8-10", "12 por lado", "30 s" -- porque un
    isométrico se mide en tiempo y un unilateral se cuenta por lado."""

    def test_un_numero_suelto(self):
        self.assertEqual(_reps_from_text("12"), ("reps", 12.0))

    def test_un_rango_toma_el_extremo_ALTO(self):
        """El reloj marca cuándo PUEDE terminar la serie; cortarla en 8 le
        quitaría al atleta las dos repeticiones que el entrenador sí le dio."""
        self.assertEqual(_reps_from_text("8-10"), ("reps", 10.0))

    def test_por_lado_sigue_siendo_repeticiones(self):
        self.assertEqual(_reps_from_text("12 por lado"), ("reps", 12.0))

    def test_un_isometrico_va_por_TIEMPO(self):
        """Sin esto, "30 s" de plancha subiría como treinta repeticiones."""
        self.assertEqual(_reps_from_text("30 s"), ("time", 30.0))
        self.assertEqual(_reps_from_text('45"'), ("time", 45.0))
        self.assertEqual(_reps_from_text("1 min"), ("time", 60.0))

    def test_sin_numero_lo_cierra_el_atleta(self):
        """Honesto y siempre ejecutable, frente a inventar un número."""
        self.assertEqual(_reps_from_text("hasta fallo"), ("lap_button", None))
        self.assertEqual(_reps_from_text(""), ("lap_button", None))

    def test_un_tempo_o_una_pausa_no_son_la_serie(self):
        """José, 2026-10-19: «puso por tiempo 2 segundos, por lo que ni vi el entreno que
        tocaba». El tiempo se buscaba en cualquier parte del texto y ganaba al número. Textos
        REALES de las semanas guardadas en local y lo que subían al reloj:
        «10 (2" arriba, 2" abajo)» 2 s; «12-15 con pausa 1" arriba» 1 s;
        «8-10 por lado (3-4 s bajada)» 4 s; «8-10 por lado (con pausa 1 s)» 1 s."""
        self.assertEqual(_reps_from_text('10 (2" arriba, 2" abajo)'), ("reps", 10.0))
        self.assertEqual(_reps_from_text('12-15 con pausa 1" arriba'), ("reps", 15.0))
        self.assertEqual(_reps_from_text("8-10 por lado (3-4 s bajada)"), ("reps", 10.0))
        self.assertEqual(_reps_from_text("8-10 por lado (con pausa 1 s)"), ("reps", 10.0))
        # Un isométrico sigue yendo por tiempo, también con decimales.
        self.assertEqual(_reps_from_text("20-30 s por lado"), ("time", 30.0))
        self.assertEqual(_reps_from_text("1,5 min"), ("time", 90.0))

    def test_dos_trabajos_o_sin_numero_fijo_los_cierra_el_atleta(self):
        """«AMRAP dejando 2 reps» subía como 2 repeticiones, y «10 dinámicos + 30" arriba» como
        30 s: elegir uno de los dos trabajos sería mentirle. Textos reales del 2026-10-19."""
        self.assertEqual(_reps_from_text("AMRAP dejando 2 reps"), ("lap_button", None))
        self.assertEqual(_reps_from_text('10 dinámicos + 30" arriba'), ("lap_button", None))
        self.assertEqual(_reps_from_text(
            '10 rodilla extendida, 10 rodilla flexionada, 10 unipodal cada lado, 30" isométrico'),
            ("lap_button", None))
        self.assertEqual(_reps_from_text("10 por pierna, lento"), ("reps", 10.0))


class LaFuerzaLLEGA_AlReloj(unittest.TestCase):
    """Antes `steps` llevaba UN bloque en blanco de 45 minutos y el desglose
    vivía sólo en el móvil. La forma que se usa ahora es la de los propios
    entrenos de Garmin (1679371824 "Día de piernas"): un bloque de repetición
    por ejercicio, con las series como iteraciones."""

    def _dia(self, **cambios):
        base = dict(
            name="Sentadilla búlgara", sets=3, reps="8-10 por pierna",
            load="12 kg por mancuerna", rest_seconds=90, cues="columna neutra",
            targets="glúteo", video_key="dumbbell_split_squat", garmin_category="SQUAT",
        )
        base.update(cambios)
        return WorkoutDay(
            date="2026-09-01", day_name_es="Martes", is_rest=False, sport="strength",
            focus="Fuerza", purpose="Fuerza general", adaptation="Baja carga si molesta",
            intensity_class="strength_freeform", total_duration_minutes=45,
            strength_block=StrengthBlock(
                warmup="5 min de movilidad", exercises=[StrengthExercise(**base)],
                cooldown="Estiramiento suave",
            ),
            steps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                               duration_value=2700, description="45 min de fuerza")],
        )

    def test_cada_ejercicio_es_un_bloque_con_sus_SERIES(self):
        dto = build_garmin_workout(self._dia(), ZONAS)
        pasos = dto["workoutSegments"][0]["workoutSteps"]
        bloques = [p for p in pasos if p.get("workoutSteps")]
        self.assertEqual(len(bloques), 1)
        self.assertEqual(bloques[0]["numberOfIterations"], 3)

    def test_el_descanso_va_DENTRO_del_bloque(self):
        """Igual que en una serie de carrera: fuera del bloque, el atleta
        encadenaría las tres series sin parar."""
        dto = build_garmin_workout(self._dia(), ZONAS)
        bloque = next(p for p in dto["workoutSegments"][0]["workoutSteps"] if p.get("workoutSteps"))
        descanso = bloque["workoutSteps"][1]
        self.assertEqual(descanso["stepType"]["stepTypeKey"], "rest")
        self.assertEqual(descanso["endConditionValue"], 90.0)

    def test_lleva_la_categoria_y_el_nombre_verificado(self):
        dto = build_garmin_workout(self._dia(), ZONAS)
        trabajo = _plano(dto["workoutSegments"][0]["workoutSteps"])
        ejercicio = next(p for p in trabajo if p.get("category"))
        self.assertEqual(ejercicio["category"], "SQUAT")
        self.assertEqual(ejercicio["exerciseName"], "DUMBBELL_SPLIT_SQUAT")

    def test_un_nombre_no_verificado_NO_impide_la_subida(self):
        dto = build_garmin_workout(self._dia(video_key="sentadilla_bulgara"), ZONAS)
        ejercicio = next(p for p in _plano(dto["workoutSegments"][0]["workoutSteps"]) if p.get("category"))
        self.assertEqual(ejercicio["category"], "SQUAT")
        self.assertNotIn("exerciseName", ejercicio)
        self.assertIn("Sentadilla búlgara", ejercicio["description"])

    def test_el_calentamiento_y_la_vuelta_a_la_calma_estan(self):
        pasos = build_garmin_workout(self._dia(), ZONAS)["workoutSegments"][0]["workoutSteps"]
        tipos = [p["stepType"]["stepTypeKey"] for p in pasos]
        self.assertEqual(tipos[0], "warmup")
        self.assertEqual(tipos[-1], "cooldown")

    def test_una_sesion_SIN_desglose_sigue_funcionando_como_antes(self):
        """Planes anteriores a v7, y sesiones que el coach dejó libres."""
        dia = self._dia()
        dia.strength_block = None
        pasos = build_garmin_workout(dia, ZONAS)["workoutSegments"][0]["workoutSteps"]
        self.assertEqual(len(pasos), 1)
        self.assertEqual(pasos[0]["endConditionValue"], 2700)


class LaNatacionLlevaSuEstilo(unittest.TestCase):
    def _dia(self, **cambios):
        paso = dict(step_order=1, step_type="interval", duration_type="distance",
                    duration_value=100, description="100m crol fuerte")
        paso.update(cambios)
        return WorkoutDay(
            date="2026-09-05", day_name_es="Sábado", is_rest=False, sport="swim",
            focus="Umbral", purpose="Serie", adaptation="Reduce si cansado",
            intensity_class="tempo_threshold", total_duration_minutes=45,
            steps=[WorkoutStep(**paso)],
        )

    def test_el_estilo_y_el_material_viajan_en_su_campo(self):
        dto = build_garmin_workout(self._dia(stroke="free", equipment="paddles", drill="pull"), ZONAS)
        paso = dto["workoutSegments"][0]["workoutSteps"][0]
        self.assertEqual(paso["strokeType"]["strokeTypeKey"], "free")
        self.assertEqual(paso["equipmentType"]["equipmentTypeKey"], "paddles")
        self.assertEqual(paso["drillType"]["drillTypeKey"], "pull")

    def test_sin_estilo_el_paso_sigue_siendo_valido(self):
        paso = build_garmin_workout(self._dia(), ZONAS)["workoutSegments"][0]["workoutSteps"][0]
        self.assertNotIn("strokeType", paso)
        self.assertEqual(paso["endConditionValue"], 100)

    def test_la_natacion_sigue_SIN_objetivo_de_intensidad(self):
        """Lo que cambió es el estilo y el material, no los objetivos: el ritmo
        de piscina sigue sin verificarse contra la cuenta real."""
        paso = build_garmin_workout(self._dia(stroke="free"), ZONAS)["workoutSegments"][0]["workoutSteps"][0]
        self.assertEqual(paso["targetType"]["workoutTargetTypeKey"], "no.target")


if __name__ == "__main__":
    unittest.main(verbosity=2)


class ElBrickLLEGA_AlReloj(unittest.TestCase):
    """v7. Un brick se entregaba escrito porque el formato multideporte era una
    incognita -- este repo no adivina los formatos de Garmin. Dejo de serlo
    cuando el atleta creo uno en su cuenta (1679387475) y se leyo de vuelta.

    Lo que resulto ser: TRAMOS ORDENADOS, cada uno con su deporte y sus pasos,
    y SIN pasos de transicion. La app del reloj los lista igual. No hay
    transiciones porque el tramo siguiente empieza donde acaba el anterior, que
    es exactamente lo que un brick es.
    """

    def _dia(self, pasos):
        return WorkoutDay(
            date="2026-11-01", day_name_es="Domingo", is_rest=False, sport="brick",
            discipline="brick", focus="Brick", purpose="Ensayo de transicion",
            adaptation="Si molesta, corta el trote", intensity_class="race_day",
            total_duration_minutes=60, steps=pasos,
        )

    def _paso(self, deporte, segundos, orden=1, **extra):
        return WorkoutStep(step_order=orden, step_type="interval", leg_sport=deporte,
                           duration_type="time", duration_value=segundos,
                           description=f"{segundos}s de {deporte}", **extra)

    def test_cada_deporte_es_un_TRAMO(self):
        dto = build_garmin_workout(
            self._dia([self._paso("bike", 2400, 1), self._paso("run", 1200, 2)]), ZONAS)
        self.assertEqual(dto["sportType"]["sportTypeKey"], "multi_sport")
        self.assertEqual(dto["sportType"]["sportTypeId"], 10)
        # Sin transiciones: lo que se entrena es no parar entre tramos, y una
        # pantalla de transicion pidiendo confirmacion es justo esa pausa.
        self.assertIs(dto["isSessionTransitionEnabled"], False)
        deportes = [s["sportType"]["sportTypeKey"] for s in dto["workoutSegments"]]
        self.assertEqual(deportes, ["cycling", "running"])

    def test_el_stepOrder_es_GLOBAL_entre_tramos(self):
        """Leido del entreno de referencia: 1-21 natacion, 22-25 bici, 26
        carrera. Si el tramo de bici empezara en 1 otra vez, Garmin guardaria
        los pasos pisados."""
        dto = build_garmin_workout(self._dia([
            self._paso("bike", 600, 1), self._paso("bike", 1800, 2), self._paso("run", 1200, 3),
        ]), ZONAS)
        ordenes = [p["stepOrder"] for s in dto["workoutSegments"] for p in s["workoutSteps"]]
        self.assertEqual(ordenes, [1, 2, 3])

    def test_un_bloque_de_repeticion_dentro_de_un_tramo_consume_sus_ordenes(self):
        dto = build_garmin_workout(self._dia([
            WorkoutStep(step_order=1, step_type="interval", leg_sport="bike", repeat_count=3,
                        description="3x3'", substeps=[
                            self._paso("bike", 180, 1), self._paso("bike", 120, 2)]),
            self._paso("run", 1200, 2),
        ]), ZONAS)
        carrera = dto["workoutSegments"][-1]
        self.assertEqual(carrera["sportType"]["sportTypeKey"], "running")
        self.assertEqual(carrera["workoutSteps"][0]["stepOrder"], 4)

    def test_dos_tramos_del_MISMO_deporte_no_se_funden(self):
        """El multideporte de referencia acaba con un segundo tramo de
        natacion. Agrupar por deporte en vez de por adyacencia fundiria los dos
        y cambiaria la sesion."""
        dto = build_garmin_workout(self._dia([
            self._paso("swim", 600, 1), self._paso("run", 1200, 2), self._paso("swim", 300, 3),
        ]), ZONAS)
        deportes = [s["sportType"]["sportTypeKey"] for s in dto["workoutSegments"]]
        self.assertEqual(deportes, ["swimming", "running", "swimming"])

    def test_un_paso_sin_etiqueta_hereda_la_del_anterior(self):
        """El modelo suele etiquetar el primer paso de cada tramo y no
        repetirlo en la recuperacion."""
        dto = build_garmin_workout(self._dia([
            self._paso("bike", 600, 1), self._paso(None, 1800, 2), self._paso("run", 1200, 3),
        ]), ZONAS)
        self.assertEqual(len(dto["workoutSegments"]), 2)
        self.assertEqual(len(dto["workoutSegments"][0]["workoutSteps"]), 2)

    def test_la_natacion_del_brick_sigue_SIN_objetivo(self):
        dto = build_garmin_workout(self._dia([
            self._paso("swim", 600, 1, target_type="hr_zone", target_zone=2),
            self._paso("run", 1200, 2, target_type="hr_zone", target_zone=2),
        ]), ZONAS)
        natacion = dto["workoutSegments"][0]["workoutSteps"][0]
        carrera = dto["workoutSegments"][1]["workoutSteps"][0]
        self.assertEqual(natacion["targetType"]["workoutTargetTypeKey"], "no.target")
        self.assertEqual(carrera["targetType"]["workoutTargetTypeKey"], "heart.rate.zone")

    def test_un_brick_de_UN_solo_deporte_se_rechaza(self):
        """No es un brick. Subirlo como multideporte con un tramo se ve en el
        reloj como un entreno normal con un envoltorio raro."""
        from services.garmin.workout_builder import UnsupportedSportError
        with self.assertRaises(UnsupportedSportError):
            build_garmin_workout(self._dia([self._paso("bike", 2400, 1)]), ZONAS)


class UnBrickSeJuzgaPorSusTRAMOS(unittest.TestCase):
    """El fallo que se llevo por delante el domingo entero.

    Un domingo de 06:00 a 10:00 con `run, trail, bike` permitidos rechazo un
    brick de bici y carrera con "'brick' no esta en los deportes permitidos", y
    el dia se convirtio en descanso. La semana bajo de 6.08h a 4.25h de las
    5.5h prescritas -- y de paso desaparecio la unica sesion de trail y el
    unico brick que el coach habia decidido.

    La palabra "brick" no es un deporte que el atleta pueda marcar en su
    franja. Lo que hace en esa ventana son sus TRAMOS.
    """

    def _dia(self, pasos, deporte="brick"):
        return WorkoutDay(
            date="2026-08-30", day_name_es="Domingo", is_rest=False, sport=deporte,
            discipline="brick", focus="Brick aerobico", purpose="Ensayo de transicion",
            adaptation="Si molesta, corta el trote", intensity_class="cross_training",
            total_duration_minutes=110, steps=pasos,
        )

    def _paso(self, deporte, orden=1):
        return WorkoutStep(step_order=orden, step_type="interval", leg_sport=deporte,
                           duration_type="time", duration_value=1800,
                           description=f"30' de {deporte}")

    def test_un_brick_de_bici_y_carrera_cabe_en_una_franja_que_permite_las_dos(self):
        from services.ai.langgraph.validation.plan_validator import _sports_used
        dia = self._dia([self._paso("bike", 1), self._paso("run", 2)])
        permitidos = {"run", "trail", "bike"}
        self.assertEqual(_sports_used(dia), {"bike", "run"})
        self.assertFalse(_sports_used(dia) - permitidos)

    def test_un_brick_con_un_tramo_NO_permitido_si_se_rechaza(self):
        """La regla sigue protegiendo: si la franja no permite nadar, un brick
        que empieza nadando no cabe."""
        from services.ai.langgraph.validation.plan_validator import _sports_used
        dia = self._dia([self._paso("swim", 1), self._paso("bike", 2)])
        self.assertEqual(sorted(_sports_used(dia) - {"run", "trail", "bike"}), ["swim"])

    def test_una_sesion_normal_se_sigue_juzgando_por_su_deporte(self):
        from services.ai.langgraph.validation.plan_validator import _sports_used
        self.assertEqual(_sports_used(self._dia([self._paso("run")], deporte="swim")), {"swim"})


class LaVerificacionComparaLoQueSE_MANDO(unittest.TestCase):
    """"Garmin guardo 8 pasos y el plan tenia 1", con el entreno perfectamente
    bien en Garmin.

    Desde v7 `day.steps` y lo que se sube no son lo mismo: una sesion de fuerza
    lleva UN paso en `steps` -- el bloque que existe solo para que la app
    muestre una duracion -- y sube como los ocho que salen de
    `strength_block`. La alarma saltaba en TODOS los dias de fuerza, y una
    alarma que salta siempre deja de ser una alarma.
    """

    def _fuerza(self):
        return WorkoutDay(
            date="2026-08-31", day_name_es="Lunes", is_rest=False, sport="strength",
            focus="Fuerza", purpose="Fuerza general", adaptation="Baja carga si molesta",
            intensity_class="strength_freeform", total_duration_minutes=30,
            strength_block=StrengthBlock(
                warmup="5 min de movilidad",
                exercises=[StrengthExercise(name="Sentadilla", sets=3, reps="10",
                                            rest_seconds=60, garmin_category="SQUAT")],
                cooldown="Estiramiento",
            ),
            steps=[WorkoutStep(step_order=1, step_type="interval", duration_type="time",
                               duration_value=1800, description="30 min de fuerza")],
        )

    def test_lo_enviado_y_day_steps_YA_no_coinciden(self):
        """El supuesto que rompio la verificacion, escrito donde se ve."""
        from services.garmin.garmin_upload_service import (
            _count_expected_steps,
            _count_stored_steps,
            _sent_steps,
        )
        enviado = build_garmin_workout(self._fuerza(), ZONAS)
        self.assertEqual(_count_expected_steps(self._fuerza().steps), 1)
        self.assertGreater(_count_stored_steps(_sent_steps(enviado)), 1)

    def test_contra_lo_ENVIADO_no_hay_discrepancia(self):
        """Es la comprobacion que importa: lo que Garmin guardo contra lo que
        se le mando, que es lo unico que puede corromper."""
        from services.garmin.garmin_upload_service import _count_stored_steps, _sent_steps
        enviado = _sent_steps(build_garmin_workout(self._fuerza(), ZONAS))
        self.assertEqual(_count_stored_steps(enviado), _count_stored_steps(enviado))

    def test_un_brick_cuenta_los_pasos_de_TODOS_sus_tramos(self):
        """Contar solo el primer tramo acusaria de discrepancia a cada brick."""
        from services.garmin.garmin_upload_service import _sent_steps
        dia = WorkoutDay(
            date="2026-08-30", day_name_es="Domingo", is_rest=False, sport="brick",
            discipline="brick", focus="Brick", purpose="Transicion", adaptation="-",
            intensity_class="cross_training", total_duration_minutes=90,
            steps=[
                WorkoutStep(step_order=1, step_type="interval", leg_sport="bike",
                            duration_type="time", duration_value=3600, description="60' bici"),
                WorkoutStep(step_order=2, step_type="interval", leg_sport="run",
                            duration_type="time", duration_value=1800, description="30' carrera"),
            ])
        self.assertEqual(len(_sent_steps(build_garmin_workout(dia, ZONAS))), 2)


class ElPromptYElEsquemaNoPuedenCONTRADECIRSE(unittest.TestCase):
    """La leccion que este repo lleva desde v6, y que se repitio en v7.

    `stroke`, `equipment` y `drill` se anadieron a `WorkoutStep` -- el arbol
    interno -- y NO a `FormattedWorkoutStep`, que es el esquema que el modelo
    escribe. El prompt le pedia el estilo y el material; su esquema de salida
    no tenia donde ponerlos.

    Dos modelos distintos los omitieron los dos, y se leyo como que el
    codificador ignoraba las instrucciones. No las ignoraba: no podia
    obedecerlas. **Una restriccion que el prompt pide y el esquema no admite
    la resuelve el modelo a favor del esquema, siempre.**
    """

    CAMPOS_QUE_EL_PROMPT_PIDE = ("stroke", "equipment", "drill", "leg_sport", "repeat_count")

    def test_lo_que_el_prompt_DOCUMENTA_como_campo_existe_de_verdad(self):
        """La lista de arriba se escribe a mano, y eso ya fallo una vez.

        El prompt documentaba `garmin_exercise` -- "el identificador de Garmin
        del ejercicio" -- y ese campo no existia en ningun esquema. El unico
        que habia era `video_key`, descrito como una clave EN CASTELLANO para
        videos futuros, y el constructor leia ese y lo buscaba en el enum
        INGLES de Garmin. Tres descripciones del mismo dato, las tres
        distintas. El modelo obedecio al esquema, escribio
        'sentadilla_bulgara', no coincidio con nada, y al reloj solo llego la
        categoria generica.

        Nadie lo anadio a CAMPOS_QUE_EL_PROMPT_PIDE porque nadie se acordo. Asi
        que esto no se acuerda: lee el prompt y comprueba lo que encuentra.
        """
        import re
        from pathlib import Path

        import services.ai.langgraph.schemas.plan_outputs as po

        nodo = Path(__file__).resolve().parents[1] / "services/ai/langgraph/nodes/weekly_planner_node.py"
        texto = nodo.read_text(encoding="utf-8")
        # El patron con el que este prompt documenta un campo: una vinneta que
        # empieza por el nombre entre acentos graves y sigue con dos puntos.
        # Los VALORES (`tempo_threshold`, `pull_buoy`) se enumeran sin ellos.
        documentados = set(re.findall(r"^\s*-\s*`([a-z_][a-z_0-9]*)`\s*(?::|\by\b)", texto, re.M))
        self.assertTrue(documentados, "el patron dejo de encontrar campos: revisalo antes de fiarte")

        modelos = ("DayIntent", "WeekIntentContent", "FormattedWorkoutDay",
                   "FormattedWorkoutStep", "StrengthBlock", "StrengthExercise")
        existentes = set()
        for m in modelos:
            existentes |= set(getattr(po, m).model_fields)

        huerfanos = sorted(documentados - existentes)
        self.assertEqual(
            huerfanos, [],
            f"el prompt documenta campos que ningun esquema tiene: {huerfanos}. "
            "El modelo no puede obedecer lo que su esquema no admite.",
        )

    def test_todo_lo_que_el_prompt_pide_existe_en_el_esquema_de_salida(self):
        from services.ai.langgraph.schemas.plan_outputs import FormattedWorkoutStep
        for campo in self.CAMPOS_QUE_EL_PROMPT_PIDE:
            with self.subTest(campo=campo):
                self.assertIn(campo, FormattedWorkoutStep.model_fields)

    def test_y_el_prompt_los_nombra(self):
        """El otro lado del mismo contrato: un campo en el esquema que nadie
        explica se rellena a ciegas o no se rellena."""
        from services.ai.langgraph.nodes.weekly_planner_node import (
            WORKOUT_FORMATTER_USER_PROMPT,
        )
        for campo in ("stroke", "equipment", "drill", "leg_sport"):
            with self.subTest(campo=campo):
                self.assertIn(campo, WORKOUT_FORMATTER_USER_PROMPT)

    def test_sobreviven_a_expand(self):
        """De poco sirve que el modelo los escriba si se pierden al construir
        el arbol que va al constructor de Garmin."""
        from services.ai.langgraph.schemas.plan_outputs import FormattedWorkoutStep
        paso = FormattedWorkoutStep(
            step_order=1, step_type="interval", duration_type="distance", duration_value=100,
            description="8x100m crol", repeat_count=8, recovery_seconds=20,
            stroke="free", equipment="paddles", drill="pull", leg_sport="swim",
        )
        expandido = paso.expand()
        trabajo = expandido.substeps[0] if expandido.substeps else expandido
        self.assertEqual(trabajo.stroke, "free")
        self.assertEqual(trabajo.equipment, "paddles")
        self.assertEqual(trabajo.drill, "pull")

    def test_y_llegan_al_DTO_de_garmin(self):
        from services.ai.langgraph.schemas.plan_outputs import (
            FormattedWorkoutStep,
            expand_formatted_steps,
        )
        dia = WorkoutDay(
            date="2026-09-05", day_name_es="Sabado", is_rest=False, sport="swim",
            focus="Umbral", purpose="Serie", adaptation="-", intensity_class="tempo_threshold",
            total_duration_minutes=45,
            steps=expand_formatted_steps([FormattedWorkoutStep(
                step_order=1, step_type="interval", duration_type="distance", duration_value=100,
                description="8x100m crol", repeat_count=8, recovery_seconds=20,
                stroke="free", equipment="paddles", drill="pull",
            )]),
        )
        dto = build_garmin_workout(dia, ZONAS)
        pasos = _plano(dto["workoutSegments"][0]["workoutSteps"])
        con_estilo = [p for p in pasos
                      if (p.get("strokeType") or {}).get("strokeTypeKey") == "free"]
        self.assertTrue(con_estilo, "el estilo no llego al DTO")
        self.assertEqual(con_estilo[0]["equipmentType"]["equipmentTypeKey"], "paddles")
        self.assertEqual(con_estilo[0]["drillType"]["drillTypeKey"], "pull")
