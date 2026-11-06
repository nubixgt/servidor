"""El precio y el muro de pago (decisiones del responsable del 2026-10-20).

«El precio es Q60 (8 USD) al mes; las semanas gratis terminan en un muro de pago, y quiero que seamos claros
con el precio.» Y en su elección: las semanas gratis se gastan por calendario (7 días cada una), el muro sólo
cierra generar (semana, macrociclo, semana automática), el pago va fuera de la app y lo registra el
administrador, y los atletas de la beta empiezan como todos al relanzar.

Correr con:  python -m unittest discover -s tests -v
"""

import json
import re
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


def _calcular(concesiones, pagos=()):
    c = ",".join(f"['created_at' => '{f}', 'weeks' => {w}]" for f, w in concesiones)
    p = ",".join(f"['paid_until' => '{h}']" for h in pagos)
    return _php(f"echo json_encode(App\\Services\\AccessService::calcular([{c}], [{p}]));")


@unittest.skipUnless(PHP, "sin PHP en esta máquina")
class LasSemanasPorCalendario(unittest.TestCase):

    def test_dos_semanas_son_catorce_dias(self):
        self.assertEqual(_calcular([("2026-10-20 18:00:00", 2)])["acceso_hasta"], "2026-11-02")

    def test_el_invitado_suma_sus_dos_y_las_de_invitacion(self):
        r = _calcular([("2026-10-20 18:00:00", 2), ("2026-10-20 18:00:00", 2)])
        self.assertEqual(r["gratis_hasta"], "2026-11-16")

    def test_una_que_llega_con_dias_por_delante_se_suma_al_final(self):
        r = _calcular([("2026-10-01 09:00:00", 2), ("2026-10-10 09:00:00", 2)])
        self.assertEqual(r["gratis_hasta"], "2026-10-28")

    def test_una_que_llega_ya_vencida_empieza_ese_dia(self):
        """Quien invita cuando su prueba terminó hace semanas no gana días en el pasado."""
        r = _calcular([("2026-10-01 09:00:00", 2), ("2026-10-20 09:00:00", 2)])
        self.assertEqual(r["gratis_hasta"], "2026-11-02")

    def test_el_pago_manda_si_llega_mas_lejos(self):
        r = _calcular([("2026-10-01 09:00:00", 2)], ["2026-12-31"])
        self.assertEqual((r["gratis_hasta"], r["pagado_hasta"], r["acceso_hasta"]),
                         ("2026-10-14", "2026-12-31", "2026-12-31"))

    def test_sin_nada_no_hay_acceso(self):
        self.assertIsNone(_calcular([])["acceso_hasta"])

    def test_un_pago_alarga_desde_donde_terminaba(self):
        p = _php("echo json_encode([App\\Services\\AccessService::periodoDelPago('2026-11-02', 1, '2026-10-25'),"
                 " App\\Services\\AccessService::periodoDelPago('2026-10-01', 1, '2026-10-25'),"
                 " App\\Services\\AccessService::periodoDelPago(null, 3, '2026-10-25')]);")
        self.assertEqual(p[0], ["2026-11-03", "2026-12-02"], "con días por delante, se suma al final")
        self.assertEqual(p[1], ["2026-10-25", "2026-11-24"], "vencido, empieza hoy")
        self.assertEqual(p[2], ["2026-10-25", "2027-01-24"])


class ElPrecioDichoIgual(unittest.TestCase):

    def test_q60_y_8_usd_en_el_codigo_y_en_el_legal(self):
        acceso = (RAIZ / "Backend/src/Services/AccessService.php").read_text(encoding="utf-8")
        gtq = re.search(r"PRECIO_GTQ = (\d+);", acceso).group(1)
        usd = re.search(r"PRECIO_USD = (\d+);", acceso).group(1)
        self.assertEqual((gtq, usd), ("60", "8"))
        legal = (RAIZ / "Backend/src/Support/Legal.php").read_text(encoding="utf-8")
        texto = legal[legal.index("<<<'MD'"):]   # lo que lee el atleta, no la historia del código
        self.assertIn(f"Power Coach AI cuesta Q{gtq} ({usd} USD) al mes", texto)
        self.assertNotIn("costo real", texto)

    def test_las_pantallas_lo_leen_del_servidor(self):
        plan = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("acceso.precio.texto", plan)
        self.assertNotIn("<PuertaDePago", plan, "«¿Cuánto pagarías?» junto al precio ya no mide nada")
        invitar = (RAIZ / "Frontend/src/components/modals/InvitarModal.vue").read_text(encoding="utf-8")
        self.assertIn("datos.precio?.texto", invitar)


@unittest.skipUnless(PHP, "sin PHP en esta máquina")
class ElPlanAnual(unittest.TestCase):
    """«Anual 80 USD, o Q600, y colocas el ahorro» (el responsable, 2026-10-21). El ahorro se calcula en
    `AccessService::precio()`; ninguna pantalla lo escribe a mano."""

    def test_el_ahorro_sale_de_las_constantes(self):
        a = _php("echo json_encode(App\\Services\\AccessService::precio()['anual']);")
        self.assertEqual((a["gtq"], a["usd"], a["texto"]), (600, 80, "Q600 (80 USD) al año"))
        self.assertEqual((a["sin_descuento_gtq"], a["ahorro_gtq"], a["ahorro_usd"], a["meses_gratis"]),
                         (720, 120, 16, 2))

    def test_el_legal_lo_dice_igual(self):
        legal = (RAIZ / "Backend/src/Support/Legal.php").read_text(encoding="utf-8")
        texto = legal[legal.index("<<<'MD'"):]
        self.assertIn("o Q600 (80 USD) al año", texto)
        self.assertIn("renovación automática", texto)

    def test_la_pantalla_de_precios_no_dice_otro_precio_mientras_carga(self):
        """`PRECIO_DE_RESPALDO` se enseña un instante antes de que conteste el servidor."""
        panel = (RAIZ / "Frontend/src/components/precios/PreciosPanel.vue").read_text(encoding="utf-8")
        respaldo = panel[panel.index("const PRECIO_DE_RESPALDO"):].split("};", 1)[0]
        a = _php("echo json_encode(App\\Services\\AccessService::precio());")
        for clave, valor in (("gtq", a["gtq"]), ("usd", a["usd"])):
            self.assertIn(f"{clave}: {valor},", respaldo)
        for clave in ("gtq", "usd", "sin_descuento_gtq", "ahorro_gtq", "ahorro_usd", "meses_gratis"):
            self.assertIn(f"{clave}: {a['anual'][clave]}", respaldo, clave)
        # Lo que ve el atleta: sin los comentarios del código, que explican justo por qué no va.
        visible = re.sub(r"<!--.*?-->", "", panel.split("<script setup>")[0], flags=re.S).lower()
        self.assertNotIn("más elegido", visible, "prueba social sin el dato: no se inventa")
        self.assertIn("Tu pago quedó registrado", (RAIZ / "Backend/src/Services/AccessService.php")
                      .read_text(encoding="utf-8"), "«Precios» promete un aviso con la fecha")

    def test_una_fecha_del_ano_que_viene_lleva_su_ano(self):
        """Prueba del 2026-10-21: el pago anual de Jessie llegaba hasta el 2027-10-28 y el aviso decía
        «hasta el jueves 28 de octubre», que se lee como la semana siguiente."""
        f = _php("echo json_encode([App\\Services\\AutoWeekService::fechaLarga('2027-10-28', '2026-10-21'),"
                 " App\\Services\\AutoWeekService::fechaLarga('2026-10-28', '2026-10-21')]);")
        self.assertEqual(f, ["jueves 28 de octubre de 2027", "miércoles 28 de octubre"])

    def test_el_pago_anual_cobra_el_precio_anual(self):
        acceso = (RAIZ / "Backend/src/Services/AccessService.php").read_text(encoding="utf-8")
        # Desde el 2026-10-23 la cortesía va delante (Q0); el anual sigue cobrando su precio.
        self.assertIn("$importe = $cortesia ? 0 : ($anual ? self::PRECIO_ANUAL_GTQ : $meses * self::PRECIO_GTQ);", acceso)
        ctrl = (RAIZ / "Backend/src/Controllers/AdminUserController.php").read_text(encoding="utf-8")
        self.assertIn("$anual = ($body['plan'] ?? '') === 'anual';", ctrl)
        usuarios = (RAIZ / "Frontend/src/views/admin/UsersAdminView.vue").read_text(encoding="utf-8")
        self.assertIn("plan: 'anual'", usuarios)


class TodoQuieroSuscribirmeLlevaAPrecios(unittest.TestCase):

    def test_plan_abre_la_pestana_de_la_tuerca(self):
        plan = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("const quieroSuscribirme = () => store.abrirPrecios();", plan)
        self.assertNotIn("Quiero suscribirme a Power Coach AI", plan, "ya no va directo a Ayuda")
        ajustes = (RAIZ / "Frontend/src/components/modals/SettingsModal.vue").read_text(encoding="utf-8")
        self.assertIn("<PreciosPanel v-if=\"store.settingsTab === 'precios'\" />", ajustes)
        tienda = (RAIZ / "Frontend/src/stores/appStore.js").read_text(encoding="utf-8")
        self.assertIn("this.abrirAjustes('precios');", tienda)


class AnularUnPagoSinBorrarlo(unittest.TestCase):
    """«Un pago registrado por error no se puede corregir desde la pantalla, sólo en la base» (el
    responsable, 2026-10-21). Se ANULA (Database/037), nunca se borra. Probado en local: un mes (Q60, del 21-oct
    al 20-nov) y un anual (Q600, del 21-nov-2026 al 20-nov-2027) a Jessie; anular el primero → «Sólo se puede
    anular el último pago vigente»; sin motivo → rechazado; el anual → acceso hasta el 20-nov y su aviso
    «Corregimos un pago»."""

    def setUp(self):
        self.acceso = (RAIZ / "Backend/src/Services/AccessService.php").read_text(encoding="utf-8")

    def test_la_migracion_esta_registrada(self):
        self.assertIn("'037' => ['athlete_payments.voided_at']",
                      (RAIZ / "Backend/src/Support/Migraciones.php").read_text(encoding="utf-8"))
        sql = (RAIZ / "Database/037_anular_pagos.sql").read_text(encoding="utf-8")
        self.assertNotIn("DELETE", sql.upper())

    def test_un_anulado_no_da_acceso_y_sin_la_037_cuentan_todos(self):
        self.assertIn("(self::admiteAnular($pdo) ? ' AND voided_at IS NULL' : '')", self.acceso)

    def test_solo_el_ultimo_vigente_y_con_motivo(self):
        cuerpo = self.acceso[self.acceso.index("public function anularPago("):]
        cuerpo = cuerpo[:cuerpo.index("\n    }\n") + 6]
        self.assertIn("ORDER BY id DESC LIMIT 1", cuerpo)
        self.assertIn("Sólo se puede anular el último pago vigente", cuerpo)
        self.assertIn("Escribe por qué se anula", cuerpo)
        self.assertNotIn("DELETE", cuerpo.upper(), "es contabilidad: se anula, no se borra")

    def test_el_atleta_se_entera_de_su_pago(self):
        self.assertIn("'Tu pago quedó registrado'", self.acceso)
        self.assertIn("'Corregimos un pago'", self.acceso)


@unittest.skipUnless(PHP, "sin PHP en esta máquina")
class LaCortesiaDeLaBeta(unittest.TestCase):
    """Auditoría del 2026-10-23, antes de fusionar con main. Con la 035 y la 036, todos los atletas de la beta
    (su Garmin se conectó antes de que hubiera semanas gratis) quedaban sin poder generar y leían «Esta cuenta
    de Garmin ya usó sus semanas gratis», que era falso: en un ensayo de las migraciones 028-037 sobre una base
    en el estado de producción, José y Jessie salieron `garmin_usado`. El responsable decidió darles un año
    desde «Usuarios»; registrarlo como el plan anual habría puesto Q600 de ingreso que no existe y les habría
    avisado «Tu pago quedó registrado (Q600)»."""

    def setUp(self):
        self.acceso = (RAIZ / "Backend/src/Services/AccessService.php").read_text(encoding="utf-8")

    def test_una_cortesia_da_acceso_y_se_sabe_que_lo_es(self):
        r = _php("echo json_encode([App\\Services\\AccessService::calcular([],"
                 " [['paid_until' => '2027-10-22', 'amount_gtq' => '0.00']]),"
                 " App\\Services\\AccessService::calcular([], [['paid_until' => '2027-10-22', 'amount_gtq' => '600.00']]),"
                 " App\\Services\\AccessService::calcular([], [['paid_until' => '2026-11-20', 'amount_gtq' => '60.00'],"
                 " ['paid_until' => '2027-11-20', 'amount_gtq' => '0.00']])]);")
        self.assertEqual((r[0]["acceso_hasta"], r[0]["cortesia"]), ("2027-10-22", True))
        self.assertFalse(r[1]["cortesia"])
        self.assertTrue(r[2]["cortesia"], "manda el que llega más lejos")

    def test_la_contabilidad_y_el_aviso_dicen_que_no_pagó(self):
        self.assertIn("'Cortesía (sin cobro). '", self.acceso)
        self.assertIn("'Tienes acceso sin costo'", self.acceso)
        self.assertIn("SELECT paid_until, amount_gtq FROM athlete_payments", self.acceso)
        ctrl = (RAIZ / "Backend/src/Controllers/AdminUserController.php").read_text(encoding="utf-8")
        self.assertIn("$cortesia = ($body['plan'] ?? '') === 'cortesia';", ctrl)
        usuarios = (RAIZ / "Frontend/src/views/admin/UsersAdminView.vue").read_text(encoding="utf-8")
        self.assertIn("{ plan: 'cortesia', months: 12, note }", usuarios)

    def test_sin_semanas_gratis_no_se_le_dice_que_las_usó(self):
        """Los dos casos que la base no distingue: conectó Garmin antes de la 035 (volver a leerlo se las da)
        o esa cuenta ya pasó por la app."""
        plan = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
        precios = (RAIZ / "Frontend/src/components/precios/PreciosPanel.vue").read_text(encoding="utf-8")
        for nombre, texto in (("AccessService", self.acceso), ("PlanView", plan), ("PreciosPanel", precios)):
            self.assertNotIn("ya usó sus semanas gratis", texto, nombre)
            self.assertIn("Volver a leer mi Garmin", texto, nombre)


class ElMuroSoloCierraGenerar(unittest.TestCase):

    def test_las_dos_generaciones_y_nada_mas(self):
        plan = (RAIZ / "Backend/src/Services/PlanService.php").read_text(encoding="utf-8")
        self.assertEqual(plan.count("(new AccessService())->muro($userId)"), 2)
        for funcion in ("function startSeason(", "function startWeek("):
            cuerpo = plan[plan.index(funcion):].split("\n    public function ", 1)[0]
            self.assertIn("->muro($userId)", cuerpo, funcion)
        controlador = (RAIZ / "Backend/src/Controllers/PlanController.php").read_text(encoding="utf-8")
        self.assertIn("if ($code === 'paywall') {\n            return 402;", controlador)

    def test_la_migracion_esta_registrada_y_sin_fk(self):
        sql = (RAIZ / "Database/036_pagos.sql").read_text(encoding="utf-8")
        self.assertNotIn("FOREIGN KEY", sql, "es contabilidad: sobrevive al borrado de la cuenta")
        self.assertIn("'036' => ['athlete_payments']",
                      (RAIZ / "Backend/src/Support/Migraciones.php").read_text(encoding="utf-8"))


if __name__ == "__main__":
    unittest.main()
