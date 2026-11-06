"""Beta 2.1, paso S6: el coach escribe el programa de gimnasio y el atleta lo confirma.

Jessie dijo que los ejercicios de gimnasio le salían "muy básicos": cada semana se
inventaban de cero, sin saber su equipo, su objetivo ni cómo quería repartirlos. S6 le
da lo mismo que S4 le da a Fernando, pero escrito por el coach: se escribe UNA vez por
bloque, se revisa en la MISMA pantalla y a partir de ahí la semana lo usa como una
rutina propia (S5).

Lo que fija:

- el prompt sale de lo que el atleta DECLARÓ, y dice lo que no se sabe;
- cuántas sesiones se piden sale de sus días de gimnasio (par si eligió torso/pierna);
- el borrador tiene la misma forma que una rutina pegada, con ids, imagen resuelta por
  alias exacto y nombre de Garmin sólo si está verificado;
- lo que NO cuadra con lo que declaró (más ejercicios seguidos de los que pidió, un
  levantamiento con barra que no marcó) se convierte en pregunta de la revisión;
- la semana usa el programa del coach por el mismo camino que una rutina propia.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import programa_del_coach as pc  # noqa: E402
from services.ai.perfil_de_fuerza import leer_perfil_de_fuerza  # noqa: E402


def perfil(**extra):
    datos = {"mode": "coach", "goal": "rendimiento", "equipment": "mancuernas_y_bandas",
             "structure": "full_body", "exercises_per_group": 2, "session_minutes": 45}
    datos.update(extra)
    return leer_perfil_de_fuerza({"strength_profile": datos})


def _ej(nombre, **extra):
    datos = {"name": nombre, "sets": 3, "reps": "8-10", "load": "RIR 2", "cues": "", "video_key": "",
             "garmin_category": "", "garmin_exercise": ""}
    datos.update(extra)
    return pc.EjercicioPropuesto(**datos)


def programa(*grupos, nombre="Cuerpo completo A", minutos=45, notas="", enfasis="completo"):
    return pc.ProgramaPropuesto(notes=notas, sessions=[pc.SesionPropuesta(
        name=nombre, minutes=minutos, emphasis=enfasis,
        why="Acompaña tus días de carrera sin quitarles frescura.",
        groups=[pc.GrupoPropuesto(exercises=list(g), rest_between_rounds_s=90) for g in grupos])])


class CuantasSesionesPide(unittest.TestCase):
    def test_las_que_su_tabla_le_da(self):
        self.assertEqual(pc.cuantas_sesiones(3, perfil()), 3)
        self.assertEqual(pc.cuantas_sesiones(1, perfil()), 1)

    def test_sin_dias_marcados_al_menos_una(self):
        self.assertEqual(pc.cuantas_sesiones(0, perfil()), 1)

    def test_con_torso_pierna_YA_NO_se_fuerza_un_numero_par(self):
        """Jessie, 2026-09-17: con tres días fijos y torso/pierna recibía DOS sesiones
        alternándose, y por turno le tocaba pierna un lunes y torso el siguiente, sin
        mirar que el lunes lleva calidad. Con tres días, tres sesiones."""
        self.assertEqual(pc.cuantas_sesiones(3, perfil(structure="upper_lower")), 3)
        self.assertEqual(pc.cuantas_sesiones(4, perfil(structure="upper_lower")), 4)

    def test_un_tope(self):
        self.assertEqual(pc.cuantas_sesiones(9, perfil()), pc.MAX_SESIONES)


class ElPromptSaleDeLoQueDeclaro(unittest.TestCase):
    def setUp(self):
        self.usuario = pc.mensajes(perfil(barbell_lifts=[]), 3, "run", "")[1]["content"]
        self.sistema = pc.mensajes(perfil(), 3, "run", "")[0]["content"]

    def test_lleva_sus_respuestas(self):
        self.assertIn("rendimiento", self.usuario)
        self.assertIn("mancuernas y bandas", self.usuario)
        self.assertIn("full body", self.usuario)
        self.assertIn("3 a la semana", self.usuario)

    def test_dice_lo_que_no_se_sabe(self):
        self.assertIn("No declaró ningún levantamiento con barra", self.usuario)

    def test_lleva_las_listas_cerradas(self):
        self.assertIn("GOBLET_SQUAT", self.usuario)       # Garmin
        self.assertIn("sentadilla_goblet", self.usuario)   # imágenes

    def test_no_le_pone_numeros_de_criterio_nuestros(self):
        for prohibido in ("RIR 2", "series por grupo", "3 sesiones a la semana", "8-12"):
            with self.subTest(prohibido=prohibido):
                self.assertNotIn(prohibido, self.sistema)

    def test_la_molestia_declarada_viaja(self):
        usuario = pc.mensajes(perfil(), 2, "triathlon", "Rodilla derecha, 3/10 al bajar escaleras")[1]["content"]
        self.assertIn("Rodilla derecha, 3/10", usuario)


class ElBorradorEsComoUnaRutinaPegada(unittest.TestCase):
    def test_misma_forma_con_ids(self):
        borrador = pc.borrador_desde_el_programa(programa([_ej("Sentadilla goblet"), _ej("Remo con mancuerna")]),
                                                 perfil())
        self.assertEqual(borrador["source"], "coach")
        self.assertEqual(borrador["original_text"], "")
        self.assertIsNone(borrador["confirmed_at"])
        sesion = borrador["sessions"][0]
        self.assertEqual(sesion["id"], "cuerpo_completo_a")
        self.assertEqual([e["id"] for e in sesion["groups"][0]["exercises"]],
                         ["cuerpo_completo_a_g1_e1", "cuerpo_completo_a_g1_e2"])
        self.assertEqual(sesion["groups"][0]["rest_between_rounds_s"], 90)
        self.assertEqual(sesion["why"][:8], "Acompaña")

    def test_la_imagen_se_resuelve_por_alias_exacto(self):
        borrador = pc.borrador_desde_el_programa(programa([_ej("Sentadilla goblet")]), perfil())
        self.assertEqual(borrador["sessions"][0]["groups"][0]["exercises"][0]["video_key"], "sentadilla_goblet")

    def test_un_nombre_de_garmin_sin_verificar_no_llega_al_reloj(self):
        borrador = pc.borrador_desde_el_programa(programa([
            _ej("Sentadilla goblet", garmin_category="SQUAT", garmin_exercise="GOBLET_SQUAT"),
            _ej("Sentadilla en silla", garmin_category="SQUAT", garmin_exercise="CHAIR_SQUAT_INVENTADA"),
        ]), perfil())
        ejercicios = borrador["sessions"][0]["groups"][0]["exercises"]
        self.assertEqual(ejercicios[0]["garmin_exercise"], "GOBLET_SQUAT")
        self.assertIsNone(ejercicios[1]["garmin_exercise"])
        self.assertEqual(ejercicios[1]["garmin_category"], "SQUAT")

    def test_mas_ejercicios_seguidos_de_los_que_pidio_se_pregunta(self):
        borrador = pc.borrador_desde_el_programa(
            programa([_ej("Sentadilla goblet"), _ej("Remo con mancuerna"), _ej("Plancha frontal")]), perfil())
        self.assertTrue(any("2 ejercicio(s) seguidos" in d for d in borrador["doubts"]))

    def test_una_barra_que_no_declaro_se_pregunta_en_su_ejercicio(self):
        borrador = pc.borrador_desde_el_programa(programa([_ej("Sentadilla trasera con barra")]),
                                                 perfil(barbell_lifts=["peso_muerto"]))
        ejercicio = borrador["sessions"][0]["groups"][0]["exercises"][0]
        self.assertTrue(any("no marcaste como tuyo" in d for d in ejercicio["doubts"]))

    def test_la_barra_que_SI_declaro_no_molesta(self):
        borrador = pc.borrador_desde_el_programa(programa([_ej("Sentadilla trasera con barra")]),
                                                 perfil(barbell_lifts=["sentadilla"]))
        self.assertEqual(borrador["sessions"][0]["groups"][0]["exercises"][0]["doubts"], [])

    def test_lo_que_el_coach_quiera_decir_del_programa_entero_se_lee_Y_SE_GUARDA(self):
        """En la primera prueba real (José, 2026-09-17) el coach explicó la rotación y cómo
        progresar las cargas; se leía en la revisión y se perdía al confirmar."""
        borrador = pc.borrador_desde_el_programa(
            programa([_ej("Sentadilla goblet")], notas="Empieza ligero la primera semana."), perfil())
        # Se lee en la revisión por `notes` ("Cómo progresarlo") y se guarda con el programa.
        # No es una duda: hasta el 2026-09-23 salía en "Échale un ojo a esto".
        self.assertEqual(borrador["notes"], "Empieza ligero la primera semana.")
        self.assertNotIn("Empieza ligero la primera semana.", borrador["doubts"])

    def test_el_porque_de_cada_sesion_viaja_en_el_borrador(self):
        borrador = pc.borrador_desde_el_programa(programa([_ej("Sentadilla goblet")]), perfil())
        self.assertIn("frescura", borrador["sessions"][0]["why"])

    def test_cada_sesion_dice_QUE_CARGA(self):
        """De ahí sale en qué día de su semana cae (paso_de_fuerza.colocar_las_sesiones)."""
        borrador = pc.borrador_desde_el_programa(
            programa([_ej("Sentadilla goblet")], enfasis="pierna"), perfil())
        self.assertEqual(borrador["sessions"][0]["emphasis"], "pierna")

    def test_al_coach_se_le_pide_que_reparta_lo_que_carga(self):
        sistema = pc.mensajes(perfil(), 3, "run", "")[0]["content"]
        self.assertIn("qué carga", sistema)
        self.assertIn("pierna / torso / completo", sistema)


class LaLlamada(unittest.TestCase):
    def test_devuelve_el_borrador_listo_para_revisar(self):
        visto = {}

        async def llamar(mensajes):
            visto["mensajes"] = mensajes
            return programa([_ej("Sentadilla goblet"), _ej("Remo con mancuerna")])

        borrador, escrito = asyncio.run(pc.escribir_el_programa(perfil(), 3, "run", "", llamar))
        self.assertEqual(len(borrador["sessions"]), 1)
        self.assertEqual(len(escrito.sessions), 1)
        self.assertIn("Escribe 3 sesión(es)", visto["mensajes"][1]["content"])


if __name__ == "__main__":
    unittest.main()
