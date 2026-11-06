"""Las semanas gratis van atadas a una cuenta Garmin NUEVA (decisión del responsable, 2026-10-20).

«2 semanas gratis, pero hay que garantizar que no haya free riders: 2 semanas por cada cuenta nueva de
Garmin vinculada (éstas son limitadas, no como las de Google), 2 semanas para el referido y 2 extra para el
que refiere, acumulables por cada referido nuevo pero que no se repita con otro.»

`FreeWeeksService::decidir` es la regla; `alConectarGarmin` la aplica al conectar Garmin, contra la huella de
esa cuenta (Database/035, sin FK: sobrevive al borrado de la cuenta de la app).

Correr con:  python -m unittest discover -s tests -v
"""

import json
import shutil
import subprocess
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
PHP = shutil.which("php") or ("C:/xampp/php/php.exe" if Path("C:/xampp/php/php.exe").exists() else None)


def _php(codigo: str):
    salida = subprocess.run([PHP, "-r", "require 'Backend/autoload.php';" + codigo], cwd=RAIZ,
                            capture_output=True, text=True, timeout=60, check=True)
    return json.loads(salida.stdout)


@unittest.skipUnless(PHP, "sin PHP en esta máquina")
class LaRegla(unittest.TestCase):

    def _decidir(self, de_quien, invitacion):
        inv = "null" if invitacion is None else "['id' => 7, 'referrer_id' => 2]"
        return _php(f"echo json_encode(App\\Services\\FreeWeeksService::decidir('{de_quien}', {inv}));")

    def test_garmin_nuevo_sin_invitacion_son_2_de_prueba(self):
        d = self._decidir("nueva", None)
        self.assertEqual(d["conceder"], [{"user": "atleta", "semanas": 2, "motivo": "prueba"}])
        self.assertIsNone(d["invitacion"])

    def test_garmin_nuevo_invitado_2_y_2_mas_2_para_quien_invita(self):
        d = self._decidir("nueva", {"id": 7})
        self.assertEqual(sorted((c["user"], c["motivo"], c["semanas"]) for c in d["conceder"]),
                         [("atleta", "invitado", 2), ("atleta", "prueba", 2), ("invitador", "invitador", 2)])
        self.assertEqual(d["invitacion"], "activar")

    def test_volver_a_sincronizar_no_regala(self):
        self.assertEqual(self._decidir("suya", None), {"conceder": [], "invitacion": None})

    def test_un_garmin_de_otra_cuenta_no_regala_y_descarta_la_invitacion(self):
        """Una persona con dos correos de Google y un solo reloj no se invita a sí misma."""
        self.assertEqual(self._decidir("de_otro", None), {"conceder": [], "invitacion": None})
        self.assertEqual(self._decidir("de_otro", {"id": 7}), {"conceder": [], "invitacion": "descartar"})

    def test_la_huella_es_del_profile_id_y_si_no_del_correo(self):
        h = _php("echo json_encode([App\\Services\\FreeWeeksService::huella('126999363', 'A@b.com'),"
                 " App\\Services\\FreeWeeksService::huella(null, ' a@B.com '),"
                 " App\\Services\\FreeWeeksService::huella(null, 'a@b.com'), App\\Services\\FreeWeeksService::huella('', '')]);")
        self.assertEqual(len(h[0]), 64)
        self.assertNotEqual(h[0], h[1], "con profileId no se usa el correo")
        self.assertEqual(h[1], h[2], "el correo se normaliza")
        self.assertIsNone(h[3])


class LosCables(unittest.TestCase):

    def test_se_decide_al_conectar_y_el_id_no_sale_a_la_pantalla(self):
        perfil = (RAIZ / "Backend/src/Controllers/ProfileController.php").read_text(encoding="utf-8")
        self.assertIn("FreeWeeksService())->alConectarGarmin(", perfil)
        self.assertIn("unset($data['garmin_cuenta']);", perfil)
        server = (RAIZ / "PaginaLocal/web_app/server.py").read_text(encoding="utf-8")
        self.assertIn('"garmin_cuenta": _cuenta_garmin(extractor)', server)

    def test_la_huella_sobrevive_al_borrado_de_la_cuenta(self):
        sql = (RAIZ / "Database/035_semanas_gratis.sql").read_text(encoding="utf-8")
        tabla = sql[sql.index("CREATE TABLE IF NOT EXISTS garmin_accounts_seen"):sql.index("CREATE TABLE IF NOT EXISTS athlete_free_weeks")]
        self.assertNotIn("FOREIGN KEY", tabla)
        self.assertIn("'035' => ['garmin_accounts_seen', 'athlete_free_weeks', 'athlete_referrals.garmin_repeated']",
                      (RAIZ / "Backend/src/Support/Migraciones.php").read_text(encoding="utf-8"))

    def test_el_legal_lo_dice(self):
        legal = (RAIZ / "Backend/src/Support/Legal.php").read_text(encoding="utf-8")
        self.assertIn("huella de tu cuenta de Garmin", legal)
        self.assertIn("una sola vez", legal)

    def test_la_fecha_del_texto_es_la_de_la_version(self):
        """Al subir la versión al 2026-10-20 el texto siguió diciendo «Versión del 11 de octubre»: la
        pantalla de aceptación enseñaba una fecha que no era la que se aceptaba."""
        import re
        legal = (RAIZ / "Backend/src/Support/Legal.php").read_text(encoding="utf-8")
        anio, mes, dia = re.search(r"public const VERSION = '(\d{4})-(\d{2})-(\d{2})';", legal).groups()
        meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre",
                 "octubre", "noviembre", "diciembre"]
        self.assertIn(f"Versión del {int(dia)} de {meses[int(mes) - 1]} de {anio}", legal)

    def test_el_codigo_se_puede_escribir_hasta_conectar_garmin(self):
        """Decisión del responsable del 2026-10-21: «deja escribir el código hasta que conecte su Garmin».
        Es al conectarlo cuando se decide la invitación (`alConectarGarmin`); después ya no cambiaría nada.
        Probado contra la base local: con Garmin conectado, «El código se escribe antes de conectar tu
        Garmin»; un código que no existe, «Ese código no es de ningún atleta»; el de José, apuntado; otra
        vez, «Tu cuenta ya tiene a quien te invitó». Datos de prueba borrados."""
        ref = (RAIZ / "Backend/src/Services/ReferralService.php").read_text(encoding="utf-8")
        cuerpo = ref[ref.index("public function reclamar("):]
        self.assertLess(cuerpo.index("conectoGarmin($userId)"), cuerpo.index("->registrar("),
                        "se mira el Garmin ANTES de apuntar la invitación")
        self.assertIn("'puede_escribir_codigo' => !$porQuien && !$this->repo->conectoGarmin($userId)", ref)
        ctrl = (RAIZ / "Backend/src/Controllers/ReferralController.php").read_text(encoding="utf-8")
        self.assertIn("#[Route('/referrals/claim', 'POST')]", ctrl)
        modal = (RAIZ / "Frontend/src/components/modals/InvitarModal.vue").read_text(encoding="utf-8")
        self.assertIn('v-else-if="datos.puede_escribir_codigo"', modal)

    def test_la_entrada_dice_la_regla_de_hoy_y_deja_escribir_el_codigo(self):
        """2026-10-21: la pantalla de entrada seguía diciendo «Cuando generes tu primera semana, los dos
        ganáis» (la regla de antes del 2026-10-20), y quien veía el código en una tarjeta compartida no
        tenía dónde escribirlo: sólo contaba el enlace `?ref=`."""
        login = (RAIZ / "Frontend/src/views/auth/Login.vue").read_text(encoding="utf-8")
        self.assertNotIn("Cuando generes tu primera semana", login)
        self.assertIn("Cuando conectes tu Garmin", login)
        self.assertIn("guardarCodigo(codigoEscrito.value)", login)
        for ruta in ("Frontend/src/views/auth/Login.vue", "Backend/src/Support/Legal.php"):
            self.assertNotIn("ganáis", (RAIZ / ruta).read_text(encoding="utf-8"), ruta)


if __name__ == "__main__":
    unittest.main()
