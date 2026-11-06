"""Las semanas como calendario y mover desde él (decisión del responsable, 2026-10-21).

«Idealmente una corrediza hacia los lados, donde las semanas se apilen como un calendario; recalendarizar
una sesión de cualquier semana activa, no importa si fue ayer, y que se vea muy bien.» La regla de qué se
puede mover está en el cerebro (`edicion_de_sesion.mover`, test_mover_sesion.py); esto fija que la pantalla
enseña lo mismo y los fallos que salieron al probarla como José en local (semana del 19 al 25 de octubre):

- tras mover, el detalle enseñaba la copia VIEJA de la semana (guardaba el objeto, y el plan se reemplaza
  entero): el jueves 22 seguía diciendo «Cuerpo completo» con «técnica + ritmo» ya en él;
- la hoja de confirmación no salía en el teléfono (`fixed` dentro de una vista animada con `transform`);
- los títulos de las casillas no se recortaban (`sm:block` pisaba el `display` de `line-clamp`).

Tres rondas de revisión de UX/UI con un subagente: 77, 89 y la última en el informe de la sesión.

Correr con:  python -m unittest discover -s tests -v
"""

import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
PLAN = (RAIZ / "Frontend/src/views/admin/PlanView.vue").read_text(encoding="utf-8")
CAL = (RAIZ / "Frontend/src/components/plan/CalendarioDeSemanas.vue").read_text(encoding="utf-8")
HOJA = (RAIZ / "Frontend/src/components/plan/MoverSesionHoja.vue").read_text(encoding="utf-8")


class LaPantallaEnseñaLaRegla(unittest.TestCase):

    def test_mover_vale_para_lo_de_ayer_de_una_semana_activa(self):
        self.assertIn("const movibleDia = (day) => !day.is_rest && day.intensity_class !== 'race_day' && fechaActiva(day.date);",
                      PLAN)

    def test_a_un_dia_que_viene_sin_la_carrera_y_con_sitio(self):
        cuerpo = PLAN[PLAN.index("const destinosValidos = computed("):]
        cuerpo = cuerpo[:cuerpo.index("});") + 3]
        for trozo in ("f < hoyISO", "race_day", "s.length <= 1"):
            self.assertIn(trozo, cuerpo)

    def test_ajustar_sigue_siendo_de_hoy_en_adelante(self):
        self.assertIn('<button v-if="day.date >= hoyISO && SE_AJUSTAN.includes(day.sport)', PLAN)

    def test_la_de_ayer_no_se_intercambia_tampoco_en_la_hoja(self):
        self.assertIn("modo: ocupadas.length && moviendo.value.pasado ? 'doble' : 'intercambiar'", PLAN)
        self.assertIn("deshabilitada: x.origen.pasado", HOJA)


class LosFallosQueSalieronAlProbar(unittest.TestCase):

    def test_el_detalle_lee_la_semana_del_plan_de_ahora(self):
        self.assertIn("const semanaMostrada = computed(() => weeks.value.find((w) => w.week_number === semanaMostradaNum.value)",
                      PLAN)

    def test_la_hoja_va_a_body(self):
        self.assertIn('<Teleport to="body">', HOJA)

    def test_el_recorte_del_titulo_no_lo_pisa_un_display(self):
        self.assertNotIn("sm:block text-[11px] text-white/90 leading-tight line-clamp-2", CAL)

    def test_el_reloj_se_nombra_igual(self):
        self.assertIn("Enviar al reloj", PLAN)
        self.assertIn("«Enviar al reloj»", HOJA)
        self.assertNotIn("'Sin subir'", PLAN)


if __name__ == "__main__":
    unittest.main()
