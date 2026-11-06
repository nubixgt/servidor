"""Que el reloj ejecute lo mismo que el plan dice que dura.

## El fallo, con sus numeros reales

La sesion de bici del martes 2026-09-01 del atleta 2. El plan, en el movil:

    Aerobico - 45 min
    10' calentamiento Z1 muy suave
    5x(1' a 95-105 rpm Z2, 1' rec)
    10' Z2 estable
    10' enfriamiento Z1

En el reloj, esa misma sesion decia **5 repeticiones de 25 minutos**. Y era
verdad: el paso guardado era

    description     "1' a 95-105 rpm Z2"
    duration_value  1500

Mil quinientos segundos son veinticinco minutos. La sesion entera sumaba 160
minutos donde el dia declaraba 45.

## Por que no lo vio nadie

Las dos mitades del paso se leen en sitios distintos. La app imprime
`description`, asi que en el movil la sesion seguia siendo de 1'; Garmin
ejecuta `duration_value`, asi que en el reloj era de 25'. Nadie compara las
dos, y el atleta se entero encima de la bici.

El validador tampoco: `_day_duration_minutes` prefiere `total_duration_minutes`
cuando existe, asi que las reglas de horas juzgaron el presupuesto contra los
45 minutos que el modelo dijo y jamas contra los 160 que escribio. Un plan
puede cumplir todas las reglas de este fichero y aun asi mandar al atleta a
pedalear casi tres horas.

Es exactamente el hueco que describe CLAUDE.md: "pasos con la forma correcta
pero unidades equivocadas".

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import (  # noqa: E402
    TargetType,
    WeekBlock,
    WeeklyPlanPayload,
    WorkoutDay,
    WorkoutStep,
)
from services.ai.langgraph.validation import plan_validator as pv  # noqa: E402


def _paso(descripcion, segundos, tipo="interval", zona=2):
    return WorkoutStep(
        step_order=1, step_type=tipo, duration_type="time", duration_value=segundos,
        target_type=TargetType.HR_ZONE, target_zone=zona, description=descripcion,
    )


def _bici_del_martes(segundos_del_trabajo=1500.0):
    """La sesion real, con el numero que rompio como unico parametro.

    Con 1500 es la que llego al reloj; con 60 es la que el plan decia.
    """
    return WorkoutDay(
        date="2026-09-01", day_name_es="Martes", is_rest=False, sport="bike",
        session_slot="pm", focus="Aerobico",
        purpose="Sumar estimulo cardiovascular de bajo impacto.",
        adaptation="Si las piernas estan pesadas: todo en Z1-Z2 continua.",
        total_duration_minutes=45,
        steps=[
            _paso("10' calentamiento Z1 muy suave", 600, tipo="warmup", zona=1),
            WorkoutStep(
                step_order=2, step_type="repeat", repeat_count=5,
                description="5x(1' a 95-105 rpm Z2, 1' rec)",
                substeps=[
                    _paso("1' a 95-105 rpm Z2", segundos_del_trabajo, tipo="main"),
                    _paso("1' suave Z2, bajando cadencia", 60, tipo="recovery"),
                ],
            ),
            _paso("10' Z2 estable", 600, tipo="recovery"),
            _paso("10' enfriamiento Z1 muy suave", 600, tipo="cooldown", zona=1),
        ],
    )


def _natacion_del_viernes():
    """La sesion que NO debe avisar, y es la razon de que la regla sea de un
    solo sentido.

    35 minutos declarados cuyos pasos por tiempo suman 3.5: todo lo demas se
    mide en metros. Una regla que exigiera cuadrar en los dos sentidos
    protestaria en cada sesion de piscina que existe.
    """
    def metros(descripcion, m):
        return WorkoutStep(step_order=1, step_type="interval", duration_type="distance",
                           duration_value=m, target_type=TargetType.NONE, description=descripcion)

    return WorkoutDay(
        date="2026-09-04", day_name_es="Viernes", is_rest=False, sport="swim",
        session_slot="unica", focus="Tecnica", purpose="Tecnica de brazada.",
        adaptation="Si el hombro molesta, quita los 100m.", total_duration_minutes=35,
        steps=[
            metros("300m tecnica suave", 300),
            WorkoutStep(
                step_order=2, step_type="repeat", repeat_count=6,
                description="6x50m tecnica",
                substeps=[metros("6x50m tecnica", 50),
                          _paso('20" de descanso en el borde', 20, tipo="rest", zona=1)],
            ),
            metros("250m muy suave", 250),
        ],
    )


def _avisos(day):
    return [v["type"] for v in pv._coaching_violations(day, {})]


class ElRelojYElPlanTienenQueDecirLoMismo(unittest.TestCase):
    def test_EL_FALLO_una_sesion_de_45_cuyos_pasos_suman_160_se_detecta(self):
        avisos = _avisos(_bici_del_martes(1500.0))
        self.assertIn(
            "duration_mismatch", avisos,
            "La sesion que llego al reloj como 5x25' paso el validador sin una queja",
        )

    def test_el_aviso_senala_el_paso_culpable_y_no_solo_el_dia(self):
        """Un aviso que dice "algo no cuadra" obliga a releer la sesion entera."""
        detalle = next(v["detail"] for v in pv._coaching_violations(_bici_del_martes(1500.0), {})
                       if v["type"] == "duration_mismatch")
        self.assertIn("1500s", detalle)
        self.assertIn("1' a 95-105 rpm Z2", detalle)
        self.assertIn("160", detalle)

    def test_la_misma_sesion_bien_escrita_no_avisa(self):
        self.assertNotIn("duration_mismatch", _avisos(_bici_del_martes(60.0)))

    def test_una_sesion_de_piscina_medida_en_metros_no_avisa(self):
        self.assertNotIn("duration_mismatch", _avisos(_natacion_del_viernes()))

    def test_un_dia_sin_total_declarado_no_avisa(self):
        """No hay contra que comparar; inventar un total seria peor que callar."""
        dia = _bici_del_martes(1500.0)
        dia.total_duration_minutes = None
        self.assertNotIn("duration_mismatch", _avisos(dia))

    def test_un_redondeo_no_es_un_error_de_unidades(self):
        """55 minutos de pasos contra 45 declarados: ancho, pero no un 25x."""
        dia = _bici_del_martes(60.0)
        dia.steps[0].duration_value = 1500  # el calentamiento sube a 25'
        dia.total_duration_minutes = 60
        self.assertNotIn("duration_mismatch", _avisos(dia))


class LeerLaDuracionQueElPropioPasoAnuncia(unittest.TestCase):
    def test_minutos_con_prima(self):
        self.assertEqual(pv._segundos_que_dice_la_descripcion("10' calentamiento Z1"), 600)

    def test_segundos_con_comillas(self):
        self.assertEqual(pv._segundos_que_dice_la_descripcion('20" de descanso en el borde'), 20)

    def test_minutos_escritos_con_letras(self):
        self.assertEqual(pv._segundos_que_dice_la_descripcion("3 min a ritmo umbral"), 180)

    def test_un_ritmo_no_es_una_duracion(self):
        """'1:52-1:59/100m' no anuncia 59 minutos de nada."""
        self.assertIsNone(
            pv._segundos_que_dice_la_descripcion("6x100m crol a 1:52-1:59/100m"))

    def test_una_cadencia_no_es_una_duracion(self):
        self.assertIsNone(pv._segundos_que_dice_la_descripcion("a 95-105 rpm en Z2"))

    def test_un_texto_sin_tiempo_devuelve_nada(self):
        self.assertIsNone(pv._segundos_que_dice_la_descripcion("Fuerza y estabilidad"))


class ElArregloDeterministaCuandoLaIANoLoCuadra(unittest.TestCase):
    def _reparar(self, dia):
        payload = WeeklyPlanPayload(
            athlete_name="Jose", start_date=dia.date, zones_table="",
            weeks=[WeekBlock(week_number=1, days=[dia])],
        )
        violaciones = pv._coaching_violations(dia, {})
        return pv._apply_duration_fallback(payload, violaciones)

    def test_devuelve_la_sesion_que_el_plan_decia(self):
        """El paso vuelve a 60s porque su propia descripcion dice 1'."""
        payload, cambiado = self._reparar(_bici_del_martes(1500.0))
        self.assertTrue(cambiado)
        dia = payload.weeks[0].days[0]
        trabajo = dia.steps[1].substeps[0]
        self.assertEqual(trabajo.duration_value, 60)
        self.assertEqual(pv._steps_duration_seconds(dia.steps) / 60, 40)
        self.assertNotIn("duration_mismatch", _avisos(dia))

    def test_sin_nada_legible_no_toca_los_pasos_pero_deja_de_mentir(self):
        """La otra salida: el total pasa a decir la verdad.

        Asi las reglas de horas, que hasta ahora creian el total declarado,
        pueden hacer su trabajo sobre el numero real.
        """
        dia = _bici_del_martes(1500.0)
        for paso in pv._walk_steps(dia.steps):
            paso.description = "pedalear"
        payload, cambiado = self._reparar(dia)
        self.assertTrue(cambiado)
        reparado = payload.weeks[0].days[0]
        self.assertEqual(reparado.steps[1].substeps[0].duration_value, 1500,
                         "No hay evidencia para cambiar el paso: no se toca")
        self.assertEqual(reparado.total_duration_minutes, 160)
        self.assertNotIn("duration_mismatch", _avisos(reparado))

    def test_no_toca_los_dias_que_no_estaban_marcados(self):
        payload, cambiado = pv._apply_duration_fallback(
            WeeklyPlanPayload(athlete_name="Jose", start_date="2026-09-01", zones_table="",
                              weeks=[WeekBlock(week_number=1, days=[_bici_del_martes(1500.0)])]),
            [],
        )
        self.assertFalse(cambiado)
        self.assertEqual(payload.weeks[0].days[0].steps[1].substeps[0].duration_value, 1500)

    def test_una_correccion_que_no_cuadra_se_deshace_entera(self):
        """El arbitro: si tras leer las descripciones la suma sigue sin cuadrar,
        el cambio no vale y no se queda a medias."""
        dia = _bici_del_martes(1500.0)
        # La descripcion del trabajo miente tanto como el valor.
        dia.steps[1].substeps[0].description = "40' a 95-105 rpm Z2"
        payload, _ = self._reparar(dia)
        reparado = payload.weeks[0].days[0]
        self.assertEqual(reparado.steps[1].substeps[0].duration_value, 1500,
                         "Se quedo una correccion que no arreglaba nada")
        self.assertEqual(reparado.total_duration_minutes, 160)


if __name__ == "__main__":
    unittest.main()
