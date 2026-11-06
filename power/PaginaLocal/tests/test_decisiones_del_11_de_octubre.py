"""Las decisiones del responsable del 2026-10-11, fijadas donde viven (PHP y frontend).

- «¿Borrar una nota del cuaderno debe pasar a retirarla?» → sí. Borrar RETIRA; sólo una ya
  retirada se borra para siempre, y sólo si el atleta lo pide (sus datos son suyos).
- «¿Subir la versión de Legal y cuánto se guardan los diagnósticos de Ayuda?» → sí, y 90 días.
- «¿Se quedan el pulso en reposo, el sueño y el peso?» → no (ver test_progreso_sin_jerga).
- «¿La tarjeta lleva el nombre, y el código de invitación va ya?» → sí y sí: «crecimiento
  exponencial a través de meses o semanas gratis por referrals; planta las bases».

Correr con:  python -m unittest discover -s tests -v
"""

import re
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]


def _leer(rel):
    return (RAIZ / rel).read_text(encoding="utf-8")


class BorrarRetira(unittest.TestCase):

    def test_el_servidor_retira_y_solo_borra_una_retirada_si_se_pide(self):
        servicio = _leer("Backend/src/Services/NotebookService.php")
        cuerpo = servicio[servicio.index("function deleteForUser"):]
        cuerpo = cuerpo[:cuerpo.index("\n    }\n")]
        self.assertIn("retireFor", cuerpo)
        self.assertIn("$paraSiempre", cuerpo)
        self.assertLess(cuerpo.index("'retirada'"), cuerpo.index("->delete("),
                        "sólo una ya retirada llega a borrarse")
        self.assertIn("$_GET['para_siempre']", _leer("Backend/src/Controllers/NotebookController.php"))

    def test_la_pantalla_no_ofrece_borrar_una_nota_en_uso(self):
        panel = _leer("Frontend/src/components/chat/CuadernoPanel.vue")
        vigente = panel[panel.index("} else if (s === 'vigente') {"):panel.index("} else {", panel.index("} else if (s === 'vigente') {"))]
        self.assertNotIn("'delete'", vigente)
        self.assertIn("Borrar para siempre", panel)


class LegalYDiagnosticos(unittest.TestCase):

    def test_la_version_sube_y_dice_lo_que_cambio(self):
        legal = _leer("Backend/src/Support/Legal.php")
        # La del 2026-10-11 o una posterior (el 2026-10-20 subió por las semanas gratis con Garmin).
        version = re.search(r"public const VERSION = '(\d{4}-\d{2}-\d{2})';", legal).group(1)
        self.assertGreaterEqual(version, "2026-10-11")
        texto = legal[legal.index("<<<'MD'"):]
        for frase in ("se borra a los 90 días", "invitar", "botón **Ayuda**", "borrarlas para siempre",
                      "Nunca tu contraseña de Garmin"):
            self.assertIn(frase, texto)
        self.assertNotIn("**Opinar**", texto, "el botón se llama «Ayuda» desde F3")

    def test_los_90_dias_son_los_mismos_en_el_texto_y_en_el_codigo(self):
        repo = _leer("Backend/src/Repositories/FeedbackRepository.php")
        dias = int(re.search(r"DIAS_DEL_DIAGNOSTICO = (\d+);", repo).group(1))
        self.assertEqual(dias, 90)
        self.assertIn(f"se borra a los {dias} días", _leer("Backend/src/Support/Legal.php"))
        self.assertIn("purgarDiagnosticos()", _leer("Backend/scripts/semanas_automaticas.php"),
                      "lo borra el cron horario; sin llamarlo, el texto promete algo que no pasa")


class Invitaciones(unittest.TestCase):

    def test_el_codigo_tiene_la_misma_forma_en_el_navegador_y_en_php(self):
        js = re.search(r"const FORMA = /\^\[A-Za-z0-9\]\{(\d+),(\d+)\}\$/;", _leer("Frontend/src/utils/invitacion.js"))
        php = re.search(r"preg_match\('/\^\[A-Z0-9\]\{(\d+),(\d+)\}\$/'", _leer("Backend/src/Services/ReferralService.php"))
        self.assertIsNotNone(js)
        self.assertIsNotNone(php)
        self.assertEqual(js.groups(), php.groups())

    def test_el_codigo_viaja_con_el_login_y_solo_cuenta_al_crear_la_cuenta(self):
        self.assertIn("referral_code", _leer("Frontend/src/services/auth.js"))
        self.assertIn("capturarCodigo()", _leer("Frontend/src/main.js"))
        auth = _leer("Backend/src/Services/AuthService.php")
        crear = auth.index("createPending(")
        self.assertIn("alCrearLaCuenta", auth[crear:crear + 400], "sólo en la rama que CREA la cuenta")

    def test_cuenta_cuando_conecta_un_garmin_nuevo(self):
        """Hasta el 2026-10-20 contaba al generar su primera semana; el responsable lo cambió ese día
        (ver test_semanas_gratis.py): cuenta al conectar una cuenta Garmin que nadie había conectado."""
        repo = _leer("Backend/src/Repositories/FreeWeeksRepository.php")
        self.assertIn("WHERE id = :id AND activated_at IS NULL", repo, "lo ganado no se reescribe")
        self.assertNotIn("activarLasQueGeneraron", _leer("Backend/src/Services/ReferralService.php"))

    def test_ya_no_es_una_puerta_falsa(self):
        barra = _leer("Frontend/src/components/layout/Sidebar.vue")
        self.assertNotIn('puerta="invitar"', barra)
        self.assertIn("store.invitarAbierto = true", barra)

    def test_la_tarjeta_lleva_nombre_y_codigo_y_nada_de_salud(self):
        tarjeta = _leer("Frontend/src/utils/compartir.js")
        self.assertIn("La racha de ${nombre}", tarjeta)
        self.assertIn("código ${codigo}", tarjeta)
        dibujo = tarjeta[tarjeta.index("export async function tarjetaDeProgreso"):tarjeta.index("export async function compartirTarjeta")]
        for salud in ("vo2", "peso", "weight", "hrv", "pulso", "sleep"):
            self.assertNotIn(salud, dibujo.lower())

    def test_la_migracion_esta_en_la_lista(self):
        self.assertIn("'033' => ['users.referral_code', 'athlete_referrals']", _leer("Backend/src/Support/Migraciones.php"))


if __name__ == "__main__":
    unittest.main()
