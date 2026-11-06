"""Las lesiones dejan de ser el centro, y el volumen deja de preguntarse.

## De dónde sale

Beta de septiembre de 2026. Sobre lesiones, el comentario más duro fue: *"la
parte de lesiones es uno de los sesgos más graves que afectan la utilidad de la
app, actualmente es un foco principal, pero debería quedar como opción
avanzada"*.

Auditando el pipeline, el prompt del planificador NO empujaba a la prevención:
todo el aparato era condicional a que el atleta declarara algo, y un atleta sano
recibía cero tokens sobre lesiones. Lo que los atletas sentían era **lo que
ellos mismos habían declarado, volviéndoles amplificado** — y declaraban mucho
porque se les preguntaba en seis sitios.

De ahí que el arreglo sea preguntar menos y **dejar de tratar como restricción
lo que ya no lo es**, no reescribir el prompt.

## Qué se fija aquí

1. Una lesión **resuelta** no es una restricción: no entra como molestia y no
   genera contingencias en la semana.
2. Una lesión sin `status` sigue siendo **activa** — es lo que significaba la
   fila antes de que la columna existiera, y las de los cinco atletas de la beta
   no lo traen.
3. El volumen semanal, cuando lo midió Garmin, se le dice al modelo como MEDIDA
   y no como estimación del atleta.

Correr con:  python -m unittest discover -s tests
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.health_context import (  # noqa: E402
    active_injuries,
    render_health_block,
    resolved_injuries,
)
from services.ai.niggle_constraints import collect  # noqa: E402
from web_app.server import build_custom_planning_context  # noqa: E402

RODILLA_ACTIVA = {
    "location": "rodilla", "laterality": "izquierda", "onset": "hace 3 semanas",
    "pain_scale": 4, "status": "activa", "notes": "sólo en bajada",
}
HOMBRO_RESUELTO = {
    "location": "hombro", "laterality": "derecha", "onset": "hace 2 años",
    "pain_scale": 0, "status": "resuelta",
}
SIN_ESTADO = {"location": "tendón de Aquiles", "onset": "hace 1 mes", "pain_scale": 3}


class UnaLesionCuradaNoEsUnaRestriccion(unittest.TestCase):
    def test_se_separan_activas_de_resueltas(self):
        perfil = {"current_injuries": [RODILLA_ACTIVA, HOMBRO_RESUELTO]}
        self.assertEqual([i["location"] for i in active_injuries(perfil)], ["rodilla"])
        self.assertEqual([i["location"] for i in resolved_injuries(perfil)], ["hombro"])

    def test_sin_status_sigue_siendo_activa(self):
        """Las filas de los cinco atletas de la beta no traen la columna."""
        perfil = {"current_injuries": [SIN_ESTADO]}
        self.assertEqual(len(active_injuries(perfil)), 1)
        self.assertEqual(resolved_injuries(perfil), [])

    def test_una_resuelta_no_llega_como_molestia(self):
        """`collect` alimenta el bloque de contingencias por día.

        Si una lesión curada entrara aquí, cada semana llevaría una adaptación
        para una rodilla que lleva seis meses bien — que es literalmente la
        queja de la beta.
        """
        molestias = collect(None, {"current_injuries": [RODILLA_ACTIVA, HOMBRO_RESUELTO]})
        ubicaciones = " ".join(m["location"] for m in molestias)
        self.assertIn("rodilla", ubicaciones)
        self.assertNotIn("hombro", ubicaciones)

    def test_el_bloque_de_salud_la_enmarca_como_historial(self):
        bloque = render_health_block({"current_injuries": [HOMBRO_RESUELTO]})
        self.assertIn("Lesiones ya resueltas", bloque)
        self.assertIn("NO son una restriccion", bloque)

    def test_la_observacion_del_atleta_llega_con_sus_palabras(self):
        bloque = render_health_block({"current_injuries": [RODILLA_ACTIVA]})
        self.assertIn("sólo en bajada", bloque)

    def test_un_atleta_sin_nada_declarado_sigue_costando_cero(self):
        """La garantía que hace que todo esto sea seguro: quien no declara
        nada no recibe ni un token sobre lesiones."""
        self.assertEqual(render_health_block({}), "")
        self.assertEqual(render_health_block({"current_injuries": []}), "")


class ElVolumenSeMideYNoSePregunta(unittest.TestCase):
    """`current_weekly_volume_hours` dejó de ser una estimación a ojo.

    El propio prompt tenía que decirle al modelo que era *"a rough athlete
    estimate, not a measurement"*. Ahora el alta lo calcula sumando la duración
    de las actividades de las últimas 12 semanas — una llamada a Garmin y
    aritmética, sin ningún modelo.

    La procedencia viaja en `garmin_autofilled_fields`. **Ese campo existía y
    NADIE lo escribía**: se leía para saber si había Garmin conectado y se
    arrastraba de un guardado al siguiente, pero la insignia GARMIN del
    formulario sale de `localStorage`, no de ahí. Sin arreglarlo, esta rama no
    se habría activado nunca — un no-op silencioso. Ahora lo escribe
    `recordarGarmin` en ProfileView.
    """

    BASE = {"name": "Atleta", "sports_preference": "triathlon", "current_weekly_volume_hours": 6.4}

    def test_medido_se_dice_medido(self):
        contexto = build_custom_planning_context(
            {**self.BASE, "garmin_autofilled_fields": ["current_weekly_volume_hours"]})
        self.assertIn("MEASURED", contexto)
        self.assertIn("12 weeks", contexto)
        self.assertNotIn("Self-reported current weekly volume", contexto)

    def test_declarado_se_sigue_diciendo_declarado(self):
        contexto = build_custom_planning_context({**self.BASE, "garmin_autofilled_fields": []})
        self.assertIn("Self-reported current weekly volume", contexto)
        self.assertNotIn("MEASURED", contexto)

    def test_avisa_de_que_las_semanas_paradas_cuentan_como_cero(self):
        """Es la unica forma de leer bien la media: un parón la hunde."""
        contexto = build_custom_planning_context(
            {**self.BASE, "garmin_autofilled_fields": ["current_weekly_volume_hours"]})
        self.assertIn("count as zero", contexto)


class LaPiscinaTieneLargo(unittest.TestCase):
    def test_el_largo_llega_al_prompt_con_los_largos(self):
        contexto = build_custom_planning_context(
            {"name": "A", "pool_access": "completo", "pool_length_m": 25})
        self.assertIn("Pool length: 25 m", contexto)
        self.assertIn("largos", contexto)

    def test_sin_piscina_no_se_menciona_el_largo(self):
        contexto = build_custom_planning_context(
            {"name": "A", "pool_access": "ninguno", "pool_length_m": 25})
        self.assertNotIn("Pool length", contexto)

    def test_sin_declarar_no_se_inventa(self):
        contexto = build_custom_planning_context({"name": "A", "pool_access": "completo"})
        self.assertNotIn("Pool length", contexto)


if __name__ == "__main__":
    unittest.main()
