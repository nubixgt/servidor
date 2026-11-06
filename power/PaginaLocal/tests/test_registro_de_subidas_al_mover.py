"""El registro de subidas cuando una sesión se mueve (Beta 3.0, F5, base de F6).

`garmin_uploads.json` es lo único que dice qué entrenos del reloj son nuestros. Mover una
sesión en la app (F6) no toca el reloj al momento: Garmin no reprograma sin cambiar el
nombre (lleva la fecha), así que la siguiente subida BORRA y vuelve a CREAR. Hasta
entonces el registro tiene que:

- conservar el `workout_id` en la clave nueva (para que la subida lo reemplace y no deje
  dos entrenos), aunque la subida falle;
- saber en qué fecha está de verdad en el reloj (`scheduled_date`), porque la huella no
  mira la fecha: una sesión movida tiene la MISMA huella y saldría «en tu reloj» estando el
  entreno en el día viejo. Ese es el falso «Subido» que esto evita.

Las fechas son la semana real del atleta 2 del 12 al 18 de octubre de 2026.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.langgraph.schemas.plan_outputs import WorkoutDay  # noqa: E402
from services.garmin import upload_ledger as ul  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]


def _dia(fecha, foco="Rodaje", minutos=40):
    return WorkoutDay(date=fecha, day_name_es="x", session_slot="unica", is_rest=False, sport="run",
                      focus=foco, purpose="-", adaptation="-", total_duration_minutes=minutos, steps=[])


def _registro():
    r = []
    r = ul.anotar(r, fecha="2026-10-13", franja="unica", workout_id=111, nombre="Rodaje", deporte="run",
                  huella_sesion=ul.huella(_dia("2026-10-13", "Rodaje")))
    r = ul.anotar(r, fecha="2026-10-15", franja="unica", workout_id=222, nombre="Series", deporte="run",
                  huella_sesion=ul.huella(_dia("2026-10-15", "Series", 38)))
    return r


class MoverSinPerderIds(unittest.TestCase):

    def test_intercambio_martes_jueves(self):
        r = ul.mover(_registro(), {"2026-10-13|unica": "2026-10-15|unica", "2026-10-15|unica": "2026-10-13|unica"})
        por = ul.por_sesion(r)
        self.assertEqual(por["2026-10-15|unica"]["workout_id"], 111)
        self.assertEqual(por["2026-10-13|unica"]["workout_id"], 222)
        # En el reloj siguen donde estaban hasta la siguiente subida.
        self.assertEqual(ul.fecha_programada(por["2026-10-15|unica"]), "2026-10-13")
        self.assertEqual(len(r), 2, "ni uno de más ni uno de menos")

    def test_el_falso_subido(self):
        """La huella no mira la fecha: sin la fecha programada, la sesión movida al jueves
        salía «en tu reloj» con el entreno todavía el martes."""
        r = ul.mover(_registro(), {"2026-10-13|unica": "2026-10-16|unica"})
        movida = _dia("2026-10-16", "Rodaje")
        entrada = ul.por_sesion(r)["2026-10-16|unica"]
        self.assertEqual(entrada["fingerprint"], ul.huella(movida), "misma huella")
        self.assertEqual(ul.estado_de_la_sesion(entrada, movida), "cambios_sin_enviar")

    def test_la_subida_fallida_no_pierde_el_id(self):
        """Si la subida que tenía que reemplazarlo falla, el id sigue en el registro (en la
        clave nueva) y por tanto se puede retirar: nada se queda en el reloj sin dueño."""
        r = ul.mover(_registro(), {"2026-10-13|unica": "2026-10-16|unica"})
        ids = {e["workout_id"] for e in r}
        self.assertEqual(ids, {111, 222})
        self.assertIn(111, {e["workout_id"] for e in ul.para_fechas(r, ["2026-10-16"])})

    def test_tras_subir_queda_en_su_dia(self):
        r = ul.mover(_registro(), {"2026-10-13|unica": "2026-10-16|unica"})
        movida = _dia("2026-10-16", "Rodaje")
        r = ul.anotar(r, fecha="2026-10-16", franja="unica", workout_id=333, nombre="Rodaje", deporte="run",
                      huella_sesion=ul.huella(movida))
        entrada = ul.por_sesion(r)["2026-10-16|unica"]
        self.assertEqual((entrada["workout_id"], ul.fecha_programada(entrada)), (333, "2026-10-16"))
        self.assertEqual(ul.estado_de_la_sesion(entrada, movida), "en_el_reloj")

    def test_no_se_pisa_una_sesion_que_se_queda(self):
        with self.assertRaises(ValueError):
            ul.mover(_registro(), {"2026-10-13|unica": "2026-10-15|unica"})

    def test_el_test_de_umbral_no_se_mueve(self):
        r = ul.anotar([], fecha="2026-10-14", franja="test-run", workout_id=9, nombre="Test", deporte="run", tipo="test")
        self.assertEqual(ul.mover(r, {"2026-10-14|test-run": "2026-10-17|test-run"})[0]["date"], "2026-10-14")


class ElEstadoDeCadaSesion(unittest.TestCase):

    def test_los_tres_estados(self):
        r = ul.por_sesion(_registro())
        self.assertEqual(ul.estado_de_la_sesion(r["2026-10-13|unica"], _dia("2026-10-13", "Rodaje")), "en_el_reloj")
        self.assertEqual(ul.estado_de_la_sesion(r["2026-10-15|unica"], _dia("2026-10-15", "Series", 45)),
                         "cambios_sin_enviar", "regenerada con otros minutos")
        self.assertEqual(ul.estado_de_la_sesion(None, _dia("2026-10-17")), "sin_subir")

    def test_una_entrada_vieja_sin_fecha_programada(self):
        """Las anteriores a la F5 no guardaban `scheduled_date`: era `date`."""
        vieja = {"date": "2026-10-13", "session_slot": "unica", "workout_id": 1,
                 "fingerprint": ul.huella(_dia("2026-10-13", "Rodaje"))}
        self.assertEqual(ul.estado_de_la_sesion(vieja, _dia("2026-10-13", "Rodaje")), "en_el_reloj")


class LaCopiaEnMySQL(unittest.TestCase):

    def test_se_copia_al_terminar_una_subida_y_al_borrar(self):
        """En cada sitio donde el registro cambia: una subida que termina, retirar del reloj al
        borrar y, desde F6, mover una sesión (sus ids pasan a la clave nueva). Contaba «2» y F6
        añadió la tercera con razón: se mira cada función, no cuántas llamadas hay."""
        php = (RAIZ / "Backend/src/Services/PlanService.php").read_text(encoding="utf-8")
        llamada = "$this->copiarRegistroDeSubidas($userId);"
        for funcion in ("function resetForUser", "function garminUploadStatus", "function editSession"):
            cuerpo = php[php.index(funcion):].split("\n    public function ", 1)[0]
            self.assertIn(llamada, cuerpo, f"{funcion} cambia el registro y no lo copia")
        self.assertIn("'030' => ['athlete_garmin_uploads']",
                      (RAIZ / "Backend/src/Support/Migraciones.php").read_text(encoding="utf-8"))

    def test_sabe_que_la_subida_termino_por_su_resultado(self):
        """Prueba en el reloj real del atleta 2 (2026-10-11): cuatro subidas y la copia en MySQL
        seguía con los ids de la primera (1726695666…; la última dejó 1726699155…). El puente
        devuelve al terminar `status: "Subiendo entrenamientos..."` con `result`; la condición
        miraba `status in (success, failed)` y la copia no se escribía nunca al subir."""
        php = (RAIZ / "Backend/src/Services/PlanService.php").read_text(encoding="utf-8")
        cuerpo = php[php.index("function garminUploadStatus"):].split("\n    public function ", 1)[0]
        self.assertIn("!empty($estado['result']) || !empty($estado['error'])", cuerpo)
        self.assertNotIn("['success', 'failed']", cuerpo)

    def test_enviar_al_reloj_manda_solo_lo_que_cambio(self):
        """Reloj real del atleta 2 (2026-10-11): «Enviar al reloj» subía la semana entera y cambiar una sesión
        recreaba las 7 («replaced: 7»), con ids nuevos para lo que estaba bien. Ahora manda sólo las fechas
        con «cambios sin enviar»; probado en su reloj: mover el Ritmo 10K del 14 al 15 y enviar dio
        «uploaded: 1, replaced: 1» y las otras 6 conservaron su id."""
        vista = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn('@click="handleGarminUpload(fechasSinEnviar)"', vista)
        self.assertIn("await startGarminUpload(pw, soloCambios ? fechas : null);", vista)

    def test_la_copia_nunca_borra_del_reloj(self):
        repo = (RAIZ / "Backend/src/Repositories/GarminUploadRepository.php").read_text(encoding="utf-8")
        self.assertNotIn("garminRetire", repo)
        self.assertNotIn("delete_workout", repo)


if __name__ == "__main__":
    unittest.main()
