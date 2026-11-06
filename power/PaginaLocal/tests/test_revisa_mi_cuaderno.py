"""«Revisa mi cuaderno» con el modelo simulado (decisión del responsable, 2026-10-11).

Lo que pidió: la IA lee todos los puntos del cuaderno, HACE PREGUNTAS relevantes y devuelve un
cuaderno ordenado, resolviendo las contradicciones entre el cuaderno y el perfil. Su ejemplo:
«si el cuaderno dice los martes me gusta correr y en el perfil tiene el martes sin correr, el
agente le dirá (esto no va al cuaderno) que vaya a su perfil y marque el martes como correr».

La tabla de días de las pruebas es la REAL del atleta 2 (spec.json del 2026-10-09): el martes
tiene Bici (posible) y Fuerza (fijo), no Correr. Ni una llamada real.

Correr con:  python -m unittest discover -s tests -v
"""

import asyncio
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai import reordenar_cuaderno as rc  # noqa: E402

PERFIL_DEL_ATLETA_2 = {
    "sport_days": {
        "Correr": {"Lunes": "posible", "Miércoles": "posible", "Viernes": "posible", "Domingo": "posible"},
        "Trail": {"Domingo": "posible"},
        "Bici": {"Martes": "posible", "Domingo": "posible"},
        "Nadar": {"Miércoles": "fijo", "Viernes": "fijo"},
        "Fuerza": {"Lunes": "fijo", "Martes": "fijo", "Jueves": "fijo"},
    },
    "preferred_rest_day": "Sábado",
    "competitions": [{"name": "10K", "date": "2027-04-20", "priority": "A", "distance": "10K"}],
    "current_injuries": [],
    "past_injuries_surgeries": "Tendón peroneo, psoas y colateral medial. Sin dolor.",
    "pool_access": "completo",
    "treadmill_access": True,
}

CUADERNO = [
    {"id": 1, "status": "vigente", "category": "preferencia", "text": "Los martes le gusta correr.", "created_at": "2026-09-20"},
    {"id": 2, "status": "vigente", "category": "molestia", "text": "Molestia en el aquiles izquierdo.", "created_at": "2026-08-02"},
    {"id": 3, "status": "vigente", "category": "logistica", "text": "Hará el medio maratón de Lima en noviembre.", "created_at": "2026-09-28"},
    {"id": 4, "status": "vigente", "category": "otro", "text": "Duerme mal cuando entrena de noche.", "created_at": "2026-09-30"},
]


def _leer(revision, guardar=None):
    async def leer(sistema, usuario):
        if guardar is not None:
            guardar.append(usuario)
        return rc.Revision.model_validate(revision), {"input_tokens": 1500}
    return leer


def _revisar(revision, respuestas=None, perfil=PERFIL_DEL_ATLETA_2, guardar=None):
    return asyncio.run(rc.revisar(CUADERNO, perfil, respuestas, _leer(revision, guardar)))[0]


class PrimeroPregunta(unittest.TestCase):

    def test_si_pregunta_no_ordena_todavia(self):
        r = _revisar({
            "preguntas": [{"texto": "¿Te sigue molestando el aquiles?", "ids": ["2"], "opciones": ["Sí", "No, ya pasó"]}],
            "acciones": [{"ids": ["4"], "accion": "reescribir", "texto": "x", "categoria": "sueno", "motivo": "m"}],
        })
        self.assertEqual([p["texto"] for p in r["preguntas"]], ["¿Te sigue molestando el aquiles?"])
        self.assertEqual(r["preguntas"][0]["opciones"], ["Sí", "No, ya pasó"])
        self.assertEqual((r["acciones"], r["avisos"]), ([], []), "las acciones se escriben con sus respuestas")

    def test_como_mucho_cuatro_y_sin_ids_inventados(self):
        r = _revisar({"preguntas": [{"texto": f"¿{n}?", "ids": ["2", "99"]} for n in range(6)]})
        self.assertEqual(len(r["preguntas"]), 4)
        self.assertEqual(r["preguntas"][0]["ids"], ["2"])

    def test_con_respuestas_ya_no_vuelve_a_preguntar(self):
        guardado = []
        r = _revisar({
            "preguntas": [{"texto": "¿Otra cosa?"}],
            "acciones": [{"ids": ["2"], "accion": "retirar", "motivo": "Dijo que ya pasó."}],
        }, respuestas=[{"pregunta": "¿Te sigue molestando el aquiles?", "respuesta": "No, ya pasó"}], guardar=guardado)
        self.assertEqual(r["preguntas"], [])
        self.assertEqual([a["accion"] for a in r["acciones"]], ["retirar"])
        self.assertIn("¿Te sigue molestando el aquiles? → No, ya pasó", guardado[0])

    def test_un_numero_de_su_respuesta_si_vale(self):
        respuestas = [{"pregunta": "¿Cuánto quieres correr los martes?", "respuesta": "unos 8 km suaves"}]
        r = _revisar({"acciones": [{"ids": ["1"], "accion": "reescribir", "texto": "Los martes quiere correr unos 8 km suaves.",
                                    "motivo": "Lo concretó."}]}, respuestas=respuestas)
        self.assertEqual(len(r["acciones"]), 1)
        self.assertEqual(r["acciones"][0]["categoria"], "preferencia")

    def test_las_respuestas_se_limpian(self):
        sucias = [{"pregunta": "a", "respuesta": ""}, "x", *[{"pregunta": f"p{n}", "respuesta": "r"} for n in range(6)]]
        self.assertEqual(len(rc.limpiar_respuestas(sucias)), 4, "como mucho 4, y una vacía no le quita el sitio a otra")


class LosAvisosDelPerfil(unittest.TestCase):

    def test_el_ejemplo_del_responsable(self):
        r = _revisar({
            "acciones": [{"ids": ["1"], "accion": "mantener", "motivo": "Es su preferencia."}],
            "avisos_del_perfil": [
                {"campo": "sport_days", "que_hacer": "Marca Correr el martes en tu tabla de días.", "ids": ["1"]},
                {"campo": "competitions", "que_hacer": "Añade el medio maratón de Lima a tus competiciones.", "ids": ["3"]},
                {"campo": "garmin_password", "que_hacer": "x"},
                {"campo": "sport_days", "que_hacer": "Marca Correr el martes en tu tabla de días."},
            ],
        })
        self.assertEqual([a["campo"] for a in r["avisos"]], ["sport_days", "competitions"],
                         "un campo fuera de la lista y un aviso repetido no se enseñan")
        self.assertEqual(r["avisos"][0]["donde"], "Perfil → Objetivos y días → Tus días de entrenamiento")
        self.assertEqual(r["avisos"][0]["pestana"], "Objetivos y días")
        self.assertEqual(r["acciones"], [], "los avisos no tocan el cuaderno")

    def test_sin_perfil_no_hay_avisos(self):
        r = _revisar({"avisos_del_perfil": [{"campo": "sport_days", "que_hacer": "x"}]}, perfil=None)
        self.assertEqual(r["avisos"], [])

    def test_una_categoria_que_no_existe_no_se_acepta(self):
        r = _revisar({"acciones": [{"ids": ["4"], "accion": "reescribir", "texto": "Duerme mal si entrena de noche.",
                                    "categoria": "salud_mental", "motivo": "Más claro."}]})
        self.assertEqual(r["acciones"][0]["categoria"], "otro")


class LoQueEnseñoLaPruebaReal(unittest.TestCase):
    """Prueba real del 2026-10-11 (gemini-3.1-flash-lite, ~0,0013 USD por vuelta, 1.440 tokens de
    entrada y ~600 de salida) con 10 anotaciones al estilo del cuaderno de Fernando en el perfil del
    atleta 2. Acertó el martes, la natación repetida, la medicación y la competición; y:

    - retiró «Viaja a Arequipa del 1 al 5 de octubre» sin saber qué día era: le faltaba la fecha;
    - mandó «Molestia leve en el aquiles izquierdo desde agosto» a Lesiones actuales sin preguntar
      si sigue (lesiones declaradas, no supuestas);
    - «Los martes me gusta correr» salió dos veces: como al_perfil y como aviso.
    """

    def test_el_mensaje_dice_que_dia_es_hoy(self):
        from datetime import date
        self.assertIn("Hoy es domingo 11/10/2026.", rc.mensaje(CUADERNO, PERFIL_DEL_ATLETA_2, hoy=date(2026, 10, 11)))

    def test_ante_una_molestia_pregunta_si_sigue(self):
        self.assertIn("PREGUNTA si sigue", rc.SISTEMA)

    def test_lo_que_va_al_perfil_no_se_repite_como_aviso(self):
        r = _revisar({
            "acciones": [{"ids": ["1"], "accion": "al_perfil", "campo_del_perfil": "sport_days",
                          "motivo": "Añadir correr los martes."}],
            "avisos_del_perfil": [{"campo": "sport_days", "que_hacer": "Marca Correr el martes.", "ids": ["1"]}],
        })
        self.assertEqual([a["accion"] for a in r["acciones"]], ["al_perfil"])
        self.assertEqual(r["avisos"], [])


class ElResumenDelPerfil(unittest.TestCase):

    def test_la_tabla_dice_que_el_martes_no_corre(self):
        texto = rc.resumen_del_perfil(PERFIL_DEL_ATLETA_2)
        self.assertIn("- Martes: Bici (posible), Fuerza (fijo)", texto)
        self.assertIn("- Sábado: descanso", texto)
        self.assertIn("Lesiones en la ficha: ninguna declarada", texto)
        self.assertIn("10K (2027-04-20, prioridad A, 10K)", texto)

    def test_sin_perfil_lo_dice(self):
        self.assertIn("no disponible", rc.resumen_del_perfil(None))
        self.assertIn("no des avisos del perfil", rc.resumen_del_perfil({}))

    def test_el_mensaje_lleva_perfil_y_anotaciones(self):
        texto = rc.mensaje(CUADERNO, PERFIL_DEL_ATLETA_2)
        self.assertIn("Perfil del atleta:", texto)
        self.assertIn("- id 1 · preferencia · 2026-09-20: Los martes le gusta correr.", texto)


if __name__ == "__main__":
    unittest.main()
