"""v7 -- el coach ve la semana ENTERA, o no puede responder por ella.

El fallo, reportado por el atleta: preguntó por qué tenía una sesión de VO2max
el miércoles y el coach le contestó que ese día era natación. No era una
alucinación gratuita — **no tenía el miércoles delante**.

La semana se le mandaba como `json.dumps(week, indent=2)` recortado a 9.000
caracteres. Una semana real ocupa 17.581: el coach veía seis días de nueve, y
los tres que se perdían eran los del FINAL, que es donde suele estar la sesión
de calidad. Al no verlo, rellenó el hueco con lo único que sí tenía a la vista
(el plan de temporada y el informe de salud, donde sí se habla de natación).

La causa era el formato, no el tamaño. La mitad de esos 17.581 caracteres eran
llaves, comillas, `null` y sangría — la misma enfermedad que
`services/garmin/payload_pruning.py` documenta para los summarizers, en el
único sitio del pipeline donde nadie la había mirado. La misma semana en prosa
compacta ocupa 8.119 y cabe entera.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.coach_chat import _lean, render_week_for_coach  # noqa: E402


# Una semana con la forma de la real: nueve entradas para siete días (dos
# dobles sesiones), y la sesión de calidad el último día -- que es exactamente
# la que el recorte se llevaba.
def _dia(fecha, deporte, foco, minutos, **extra):
    base = dict(
        date=fecha, day_name_es="Día", session_slot="unica", is_rest=False,
        sport=deporte, focus=foco, discipline=deporte,
        total_duration_minutes=minutos, intensity_class="easy",
        purpose=f"Propósito de {foco}.", adaptation=f"Si algo va mal en {foco}, para.",
        steps=[], garmin_exportable=True,
    )
    base.update(extra)
    return base


VO2MAX = _dia("2026-09-02", "run", "VO2max", 45, intensity_class="vo2max", day_name_es="Miércoles", steps=[
    {"step_order": 1, "step_type": "warmup", "duration_type": "time", "duration_value": 720,
     "target_type": "hr_zone", "target_zone": 2, "description": "12min calentamiento", "substeps": []},
    {"step_order": 2, "step_type": "repeat", "repeat_count": 5,
     "description": "5x(3min a 5:12-5:20/km, 2min rec)", "substeps": [
         {"step_order": 1, "step_type": "interval", "duration_type": "time", "duration_value": 180,
          "target_type": "pace", "target_low": 312, "target_high": 320,
          "description": "3min a 5:12-5:20/km", "substeps": []},
         {"step_order": 2, "step_type": "recovery", "duration_type": "time", "duration_value": 120,
          "target_type": "hr_zone", "target_zone": 1, "description": "2min trote suave", "substeps": []},
     ]},
])

SEMANA = {
    "week_number": 1,
    "week_rationale": "Semana 1 de la fase de puesta a punto, ~5.0h en total.",
    "days": [
        _dia("2026-08-27", "strength", "Fuerza", 35,
             intensity_class="strength_freeform", strength_block={
                 "warmup": "5 min de movilidad de cadera y tobillo",
                 "exercises": [
                     {"name": "Sentadilla bulgara", "sets": 3, "reps": "8-10 por pierna",
                      "load": "12 kg por mancuerna", "rest_seconds": 90,
                      "cues": "rodilla alineada con el pie", "targets": "gluteo, cuadriceps",
                      "video_key": "sentadilla_bulgara"},
                     {"name": "Peso muerto rumano unipodal", "sets": 3, "reps": "10 por lado",
                      "load": "RPE 7", "rest_seconds": 60,
                      "cues": "columna neutra, la cadera va atras", "targets": "isquiotibiales",
                      "video_key": "peso_muerto_rumano_unipodal"},
                 ],
                 "cooldown": "Estiramiento de flexores de cadera",
                 "notes": "Si molesta la ingle, sustituye la bulgara por sentadilla a dos piernas.",
             }),
        _dia("2026-08-28", "run", "Aeróbico + técnica", 30),
        _dia("2026-08-29", "rest", "", None, is_rest=True),
        _dia("2026-08-30", "run", "Tirada larga suave", 60),
        _dia("2026-08-31", "strength", "Fuerza", 35, session_slot="am"),
        _dia("2026-08-31", "run", "Recuperación", 30, session_slot="pm"),
        _dia("2026-09-01", "strength", "Fuerza", 35, session_slot="am"),
        _dia("2026-09-01", "bike", "Aeróbico bajo", 30, session_slot="pm"),
        VO2MAX,
    ],
}


class ElUltimoDiaDeLaSemanaTambienExiste(unittest.TestCase):
    """El test que reproduce el fallo exacto que reportó el atleta."""

    def test_el_miercoles_con_el_vo2max_esta_en_el_bloque(self):
        bloque = render_week_for_coach(SEMANA)
        self.assertIn("2026-09-02", bloque)
        self.assertIn("VO2max", bloque)

    def test_estan_los_nueve_dias(self):
        bloque = render_week_for_coach(SEMANA)
        for dia in SEMANA["days"]:
            with self.subTest(fecha=dia["date"], franja=dia["session_slot"]):
                self.assertIn(dia["date"], bloque)

    def test_las_dobles_sesiones_se_distinguen(self):
        """Dos entradas para el 31 de agosto. Sin una marca que las distinga,
        el coach responde por una cuando le preguntan por la otra.

        La marca dice el ORDEN, no una hora. Decia "(AM)" y "(PM)" y eso era
        falso justo en el caso que mas importa: un atleta que corre Y nada la
        misma manana veia su segunda sesion marcada como PM. `session_slot`
        siempre fue una clave de posicion; la hora sale de la franja declarada.
        """
        bloque = render_week_for_coach(SEMANA)
        self.assertIn("1ª sesión del día", bloque)
        self.assertIn("2ª sesión del día", bloque)
        self.assertNotIn("(AM)", bloque)

    def test_la_marca_dice_a_que_franja_pertenece_cuando_se_sabe(self):
        """Dos sesiones en la MISMA franja son el caso que el modelo viejo no
        podia expresar: correr y nadar de 05:30 a 07:00 son dos sesiones con el
        mismo `block_index`."""
        semana = {"days": [
            _dia("2026-09-04", "run", "Rodaje", 45, session_slot="am", block_index=0),
            _dia("2026-09-04", "swim", "Tecnica", 40, session_slot="pm", block_index=0),
        ]}
        bloque = render_week_for_coach(semana)
        self.assertIn("franja 0", bloque)
        self.assertEqual(bloque.count("franja 0"), 2)

    def test_cabe_entera_donde_antes_no_cabia(self):
        crudo = len(json.dumps(SEMANA, indent=2, ensure_ascii=False))
        compacto = len(render_week_for_coach(SEMANA))
        self.assertLess(compacto, crudo / 1.5)
        self.assertLess(compacto, 9000)  # el presupuesto que antes la partía


class LaFUERZATambienEsUnaSesion(unittest.TestCase):
    """v7. `strength_block` vive FUERA de `steps` a proposito, porque no viaja
    a Garmin -- se lee en el movil, en el gimnasio. Y por vivir fuera se le
    escapaba al renderizador del coach: un jueves de fuerza le llegaba como un
    unico paso de "35 min", asi que a "que ejercicios hago el jueves?" solo
    podia contestar inventandoselos o admitiendo que no lo sabia.

    Es exactamente el fallo del miercoles de VO2max que este archivo ya
    documenta, en otro campo del mismo dia."""

    BLOQUE = None

    def setUp(self):
        self.bloque = render_week_for_coach(SEMANA)

    def test_los_ejercicios_estan(self):
        self.assertIn("Sentadilla bulgara", self.bloque)
        self.assertIn("Peso muerto rumano unipodal", self.bloque)

    def test_las_series_las_repeticiones_y_la_carga_estan(self):
        """Son la pregunta literal del atleta: cuantas y con cuanto."""
        self.assertIn("3x8-10 por pierna", self.bloque)
        self.assertIn("12 kg por mancuerna", self.bloque)
        self.assertIn("descanso 90s", self.bloque)

    def test_la_indicacion_de_ejecucion_esta(self):
        """Es la frase que evita que el atleta se haga dano haciendolo mal."""
        self.assertIn("columna neutra", self.bloque)

    def test_la_sustitucion_por_una_molestia_esta(self):
        """Si el atleta escribe "me molesta la ingle", el coach ya tiene
        escrito el cambio que el propio plan preveia."""
        self.assertIn("sustituye la bulgara", self.bloque)

    def test_el_calentamiento_y_la_vuelta_a_la_calma_estan(self):
        self.assertIn("movilidad de cadera", self.bloque)
        self.assertIn("flexores de cadera", self.bloque)

    def test_un_dia_SIN_bloque_de_fuerza_no_se_rompe(self):
        """La mayoria de los dias no lo llevan, y una carrera nunca."""
        self.assertIn("Tirada larga suave", self.bloque)


class NoSePierdeUnSoloNumero(unittest.TestCase):
    """Compactar no puede costar precisión: el atleta pregunta por los
    números, y son lo primero que un recorte se llevaba."""

    def test_el_ritmo_de_las_series_esta(self):
        bloque = render_week_for_coach(SEMANA)
        self.assertIn("5:12-5:20/km", bloque)

    def test_las_repeticiones_y_su_recuperacion_estan(self):
        bloque = render_week_for_coach(SEMANA)
        self.assertIn("5x", bloque)
        self.assertIn("2min", bloque)

    def test_las_zonas_de_frecuencia_cardiaca_estan(self):
        self.assertIn("Z2", render_week_for_coach(SEMANA))

    def test_el_proposito_y_la_adaptacion_sobreviven(self):
        """Es la mitad del producto: por qué esta sesión y qué hacer si algo
        va mal."""
        bloque = render_week_for_coach(SEMANA)
        self.assertIn("Propósito de VO2max", bloque)
        self.assertIn("Si algo va mal en VO2max", bloque)

    def test_el_porque_de_la_semana_va_arriba(self):
        self.assertIn("puesta a punto", render_week_for_coach(SEMANA))

    def test_una_sesion_que_no_va_al_reloj_se_marca_y_se_escribe_entera(self):
        """v5 Track 5: lo que Garmin no puede llevar se entrega escrito. Si el
        coach no lo tiene, el atleta pregunta y no hay respuesta."""
        semana = {"days": [_dia("2026-09-03", "strength", "Rehab", 30,
                                garmin_exportable=False,
                                manual_instructions="3x12 puente de glúteo")]}
        bloque = render_week_for_coach(semana)
        self.assertIn("puente de glúteo", bloque)
        self.assertIn("No va al reloj", bloque)

    def test_un_dia_de_descanso_se_dice_descanso(self):
        self.assertIn("DESCANSO", render_week_for_coach(SEMANA))


class LosArtefactosDelArchivoSePodanAntesDeRecortarse(unittest.TestCase):
    """Los informes de los expertos llegaban recortados entre el 77% y el 80%,
    y lo que se perdía era el final -- donde están las implicaciones. Se
    guardan indentados para que una persona pueda leerlos; mandárselos así al
    modelo era pagar sangría y perder contenido."""

    CRUDO = json.dumps({
        "for_weekly_planner": {
            "signals": ["deriva cardíaca 144->161 bpm", "carga alta"],
            "evidence": ["21k del 2026-08-23"],
            "uncertainty": None,
            "vacio": [],
        }
    }, indent=2, ensure_ascii=False)

    def test_encoge_de_verdad(self):
        self.assertLess(len(_lean(self.CRUDO)), len(self.CRUDO) / 1.5)

    def test_no_pierde_ni_un_dato(self):
        for valor in ("deriva cardíaca 144->161 bpm", "carga alta", "21k del 2026-08-23"):
            with self.subTest(valor=valor):
                self.assertIn(valor, _lean(self.CRUDO))

    def test_un_texto_que_no_es_json_se_devuelve_intacto(self):
        """synthesis.md es markdown. Pasarlo por aquí no puede romperlo."""
        markdown = "# Informe\n\nEsto no es JSON.\n"
        self.assertEqual(_lean(markdown), markdown)


class LaSemanaActualNoSeRecortaNunca(unittest.TestCase):
    """La regla, escrita donde se pueda romper. Todo lo demás del contexto
    admite recorte; esto no, porque es la pregunta que el atleta viene a
    hacer."""

    FUENTE = (Path(__file__).resolve().parents[1]
              / "services/ai/coach_chat.py").read_text(encoding="utf-8")

    def test_el_bloque_de_la_semana_no_pasa_por_truncate(self):
        trozo = self.FUENTE[self.FUENTE.index("Semana actual"):]
        trozo = trozo[:trozo.index("Semanas anteriores")]
        self.assertIn("render_week_for_coach", trozo)
        self.assertNotIn("_truncate", trozo)

    def test_y_ya_no_se_serializa_en_json_indentado(self):
        trozo = self.FUENTE[self.FUENTE.index("Semana actual"):]
        trozo = trozo[:trozo.index("Semanas anteriores")]
        self.assertNotIn("indent=2", trozo)


if __name__ == "__main__":
    unittest.main(verbosity=2)
