"""«Generar mi semana automáticamente» (decisión del responsable, 2026-09-24).

Con el interruptor encendido, la VÍSPERA del primer día de su siguiente semana, a las 21:00
de SU hora, se genera la semana sin preguntas del coach ni check-in y se sube a su reloj
(Backend/src/Services/AutoWeekService.php, lanzado cada hora por
Backend/scripts/semanas_automaticas.php).

Con el calendario real del atleta 2: su plan llega hasta el domingo 2026-10-11, su semana
empieza el lunes y vive en Lima (UTC-5). Su siguiente semana es la del lunes 12, y se genera
desde el domingo 11 a las 21:00 de Lima, que en el servidor (UTC) ya es el lunes 12 a las
02:00. Si el servidor usara su propia hora, la lanzaría cinco horas antes.

Lo que se fija aquí:
- cuándo toca, en su zona (PHP, ejecutado de verdad si hay intérprete);
- que la semana automática no pregunta (sin proveedor, el paso de las preguntas no gasta)
  y que el cerebro sabe que el atleta no dijo nada de esa semana;
- que ningún texto le manda a un botón que no existe («Rehacer semana» se retiró en la v1.1).
"""

import json
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / "PaginaLocal"))

_XAMPP = Path("C:/xampp/php/php.exe")
PHP = shutil.which("php") or (str(_XAMPP) if _XAMPP.is_file() else None)


def _php(cuerpo: str):
    script = (
        # Con el autoload: `fechaLarga` lee la zona de AccessService (2026-10-21).
        "<?php\nrequire " + json.dumps(str(RAIZ / "Backend/autoload.php")) + ";\n"
        "use App\\Services\\AutoWeekService;\n" + cuerpo
    )
    with tempfile.NamedTemporaryFile("w", suffix=".php", delete=False, encoding="utf-8") as f:
        f.write(script)
    try:
        salida = subprocess.run([PHP, f.name], capture_output=True, text=True, check=True, timeout=60)
    finally:
        Path(f.name).unlink(missing_ok=True)
    return json.loads(salida.stdout)


@unittest.skipUnless(PHP, "sin PHP no se puede ejecutar AutoWeekService")
class CuandoToca(unittest.TestCase):
    def test_la_vispera_a_las_21_de_su_hora(self):
        casos = {
            "2026-10-11T20:59:00": "antes",
            "2026-10-11T21:00:00": "ahora",
            "2026-10-12T23:00:00": "ahora",   # el servidor estuvo caído: se genera igual el primer día
            "2026-10-13T08:00:00": "tarde",   # su semana ya empezó ayer sin plan: se retoma a mano
        }
        datos = _php(
            "$lima = new DateTimeZone('America/Lima');\n$out = [];\n"
            f"foreach ({json.dumps(list(casos))} as $c) "
            "$out[$c] = AutoWeekService::cuandoToca('2026-10-12', new DateTimeImmutable($c, $lima));\n"
            "echo json_encode($out);\n"
        )
        self.assertEqual(datos, casos)

    def test_la_hora_es_la_suya_no_la_del_servidor(self):
        """02:00 UTC del lunes 12 son las 21:00 del domingo 11 en Lima: toca. Las 21:00 UTC del
        domingo son las 16:00 en Lima: todavía no."""
        datos = _php(
            "$lima = new DateTimeZone('America/Lima');\n"
            "$a = (new DateTimeImmutable('2026-10-12T02:00:00Z'))->setTimezone($lima);\n"
            "$b = (new DateTimeImmutable('2026-10-11T21:00:00Z'))->setTimezone($lima);\n"
            "echo json_encode([AutoWeekService::cuandoToca('2026-10-12', $a), "
            "AutoWeekService::cuandoToca('2026-10-12', $b)]);\n"
        )
        self.assertEqual(datos, ["ahora", "antes"])

    def test_la_siguiente_semana_empieza_el_dia_despues_del_ultimo(self):
        plan = {"weeks": [
            {"days": [{"date": "2026-09-23"}, {"date": "2026-09-27"}]},
            {"days": [{"date": "2026-10-05"}, {"date": "2026-10-11"}, {"date": "2026-10-11"}]},
        ]}
        datos = _php(
            f"echo json_encode([AutoWeekService::siguienteInicio(json_decode({json.dumps(json.dumps(plan))}, true)), "
            "AutoWeekService::siguienteInicio(['weeks' => []]), AutoWeekService::fechaLarga('2026-10-12', '2026-10-11')]);\n"
        )
        self.assertEqual(datos, ["2026-10-12", None, "lunes 12 de octubre"])


class LaSemanaAutomaticaNoPreguntaNiSupone(unittest.TestCase):
    def test_el_worker_no_registra_quien_pregunte(self):
        """Sin proveedor, `preguntas_del_coach_node` no pregunta ni gasta (su docstring)."""
        worker = (RAIZ / "AiService/worker.py").read_text("utf-8")
        self.assertIn('automatica = kind == "week" and bool(SPEC.get("automatic", False))', worker)
        self.assertIn("usar_proveedor(None)", worker)
        self.assertIn("automatica=automatica", worker)

    def test_la_nota_dice_lo_que_no_se_sabe(self):
        from web_app import server

        nota = server.NOTA_DE_SEMANA_AUTOMATICA
        self.assertIn("el atleta no ha dicho nada de cómo llega a esta semana", nota)
        self.assertIn("No le atribuyas sensaciones, cansancio, molestias ni peticiones", nota)
        self.assertNotIn("Rehacer", nota)
        import inspect
        self.assertIn("automatica", inspect.signature(server.run_week_generation_task).parameters)


sys.path.insert(0, str(RAIZ / "AiService"))


class ElPuenteLoDeclara(unittest.TestCase):
    def test_el_esquema_y_el_spec(self):
        from bridge.schemas import GenerateWeekRequest

        self.assertIn("automatic", GenerateWeekRequest.model_fields)
        self.assertFalse(GenerateWeekRequest.model_fields["automatic"].default)
        main = (RAIZ / "AiService/main.py").read_text("utf-8")
        self.assertIn('"automatic": req.automatic', main)


class SinCondicionesNiAccesoEspera(unittest.TestCase):
    """Auditoría del 2026-10-23, antes de fusionar con main: la versión del Legal sube (2026-09-15 →
    2026-10-21) y generar exige haberla aceptado. Quien tenía la semana automática y no abría la app antes
    del domingo a las 21:00 gastaba sus 3 intentos (21:00, 22:00, 23:00) y se quedaba sin semana; con el muro,
    igual quien todavía no tenía su cortesía. Ensayado en local con el atleta 2 (domingo 1-nov 21:30, sin
    aceptar): cuatro vueltas con 0 intentos y «Tu semana del lunes 2 de noviembre está esperando: acepta las
    nuevas condiciones de uso al abrir la app…»; el martes 3, «no se generó a tiempo. Genérala a mano»."""

    def setUp(self):
        self.servicio = (RAIZ / "Backend/src/Services/AutoWeekService.php").read_text("utf-8")

    def test_no_gasta_intentos_y_dice_que_falta(self):
        self.assertIn("in_array($res['code'] ?? '', ['terms_required', 'paywall'], true)", self.servicio)
        rama = self.servicio[self.servicio.index("['terms_required', 'paywall']"):]
        rama = rama[:rama.index("return false;")]
        self.assertNotIn(", true)", rama.split("setStage(", 1)[1], "no cuenta como intento")
        self.assertIn("está esperando", rama)

    def test_pasado_su_primer_dia_no_promete_lo_que_ya_no_hara(self):
        self.assertIn("$esperaba = str_contains((string) ($fila['message'] ?? ''), 'está esperando');", self.servicio)
        self.assertIn("if (!$mismaSemana || $stage !== 'error' || $esperaba) {", self.servicio)


if __name__ == "__main__":
    unittest.main()
