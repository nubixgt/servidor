"""Avanzado, Progreso, Informes, Cuaderno e Historial (revisión del 2026-10-21).

Revisión con dos evaluadores nuevos y ciegos por ronda (persona sin jerga y heurística), como pidió el
responsable. Lo que se arregló y aquí se fija:

- Progreso enseñaba «0 semanas seguidas» junto a «2 semanas cumplidas» sin decir por qué. Como José (atleta 2):
  su semana del 12 al 18 de octubre se quedó en 3 de 6 sesiones, y por eso la racha empezaba de nuevo.
- «Un día que se cae siempre… Quítalo de tu tabla» salía siempre, y suponía la causa.
- «Tu forma (VO2max) 51 (+7)»: el +7 no decía desde cuándo.
- Historial: nueve tarjetas «Semana» que sólo decían a qué hora se escribieron.
- «Restablecer mi perfil» borraba TODO desde el final de la pestaña opcional Avanzado.
- Salud decía «Nada declarado» con lesiones pasadas escritas.

Correr con:  python -m unittest tests.test_pantallas_del_atleta -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.charts import fitness_trend, plan_vs_reality, weekday_adherence  # noqa: E402
from services.ai.progreso import racha  # noqa: E402

RAIZ = Path(__file__).resolve().parents[2]
FRONT = RAIZ / "Frontend/src"


def _semana(desde, hasta, hechas, perdidas, horas=(5.0, 4.0)):
    return {"from": desde, "to": hasta, "week_closed": True, "completed_sessions": hechas, "missed_sessions": perdidas,
            "prescribed_hours": horas[0], "executed_hours": horas[1]}


class LaRachaDicePorQue(unittest.TestCase):

    def test_la_semana_de_jose_que_la_rompio(self):
        semanas = [_semana("2026-09-28", "2026-10-04", 5, 1), _semana("2026-10-05", "2026-10-11", 6, 0),
                   _semana("2026-10-12", "2026-10-18", 3, 3)]
        r = racha(semanas)
        self.assertEqual((r["actual"], r["mejor"], r["semanas_constantes"]), (0, 2, 2))
        u = r["ultima_semana"]
        self.assertEqual((u["desde"], u["hasta"], u["cuenta"], u["hechas"], u["evaluadas"]),
                         ("2026-10-12", "2026-10-18", "rompe", 3, 6))

    def test_la_pantalla_lo_explica(self):
        tarjeta = (FRONT / "components/progreso/TuProgreso.vue").read_text(encoding="utf-8")
        self.assertIn("hiciste ${u.hechas} de ${u.evaluadas} sesiones; para sumar hacían falta ${Math.ceil(u.evaluadas * 0.7)}", tarjeta)
        self.assertNotIn("una molestia declarada la congela", tarjeta)


class LasGraficas(unittest.TestCase):

    def test_el_vo2max_dice_desde_cuando(self):
        t = fitness_trend([{"date": "2025-09-07", "value": 44}, {"date": "2026-10-20", "value": 51}])
        self.assertEqual((t["change"], t["first_date"]), (7, "2025-09-07"))

    def test_el_aviso_de_dias_solo_con_un_dia_flojo_y_sin_suponer(self):
        # Sábado 1 de 3 (33 %) y martes 2 de 4 (50 %): sale, nombra el más flojo, en plural, y no supone la causa.
        flojo = weekday_adherence([{"by_weekday": {"martes": [4, 2], "sábado": [3, 1], "lunes": [4, 4]}}])
        self.assertEqual(flojo["weakest"], "sábado")
        # Y con su muestra: «1 de 3» dice cuánto pesa el consejo.
        self.assertTrue(flojo["note"].startswith("Los sábados cumples menos (1 de 3 sesiones)."), flojo["note"])
        self.assertNotIn("libre de verdad", flojo["note"])
        bien = weekday_adherence([{"by_weekday": {"lunes": [4, 4], "martes": [4, 3]}}])
        self.assertIsNone(bien["note"])

    def test_sin_jerga_de_por_dentro(self):
        vista = (FRONT / "views/admin/AnalyticsView.vue").read_text(encoding="utf-8")
        self.assertNotIn("de tus datos (sin IA)", vista)
        self.assertNotIn("la: 'Prescrito'", vista)


class LasBarrasSonSemanas(unittest.TestCase):

    def test_la_etiqueta_es_el_rango(self):
        # Las semanas reales de José: «15 SEP, 21 SEP, 26 SEP» no decía que cada barra era una semana.
        pvr = plan_vs_reality([_semana("2026-09-15", "2026-09-20", 4, 1), _semana("2026-09-26", "2026-10-04", 5, 1),
                               _semana("2026-10-09", "2026-10-11", 2, 0)])
        self.assertEqual([p["label"] for p in pvr["points"]], ["15-20 sep", "26 sep-4 oct", "9-11 oct"])


class ElHistorialDiceQueSemanas(unittest.TestCase):

    def test_el_servidor_manda_las_fechas(self):
        repo = (RAIZ / "Backend/src/Repositories/PlanRepository.php").read_text(encoding="utf-8")
        self.assertIn("'desde' => $fechas[0] ?? null,", repo)
        self.assertIn("'ultima_desde' => $ultima[0] ?? null,", repo)

    def test_la_pantalla_las_usa_y_borrar_va_dentro(self):
        vista = (FRONT / "views/admin/HistorialView.vue").read_text(encoding="utf-8")
        # La semana que AÑADIÓ cada versión (la última), no «del 26 sept al…» en tres tarjetas.
        self.assertIn("const desde = e.ultima_desde || e.desde;", vista)
        self.assertIn("Quitar del historial", vista)
        self.assertNotIn("Último archivado", vista)


class LaSemanaAbiertaEsLaQueSePulso(unittest.TestCase):
    """Tercera ronda: «Semana del 12 al 18 oct» abría «Semana 1» de septiembre, con «Sábado 09-26» y «(67m)»."""

    def test_primero_la_pulsada_y_con_fechas_y_minutos_legibles(self):
        vista = (FRONT / "views/admin/HistorialView.vue").read_text(encoding="utf-8")
        self.assertIn("const semanasEnOrden = (d) => [...(d?.weekly_plan?.weeks || [])].reverse();", vista)
        self.assertNotIn("(day.date || '').slice(5)", vista, "«09-26» se leía como mes-día imposible")
        self.assertNotIn("}}m)", vista, "«(67m)» se leía como metros")
        self.assertIn("Por qué esta semana es así", vista)
        # Y lo hecho (2026-10-22): «3 de 6 sesiones hechas» del expediente y el ✓/✗ de cada sesión reciente.
        self.assertIn("sesiones hechas", vista)
        self.assertIn("const marcaDe = (day) =>", vista)
        self.assertIn("ahora.sport !== day.sport", vista, "la marca es de la sesión del plan de ahora: mismo deporte")
        repo = (RAIZ / "Backend/src/Repositories/TrackRecordRepository.php").read_text(encoding="utf-8")
        self.assertIn("public function resultados(int $userId): array", repo)


class ElCuadernoNoEnsenaAGuardarlaMal(unittest.TestCase):

    def test_el_ejemplo_del_campo_es_del_tipo_por_defecto(self):
        panel = (FRONT / "components/chat/CuadernoPanel.vue").read_text(encoding="utf-8")
        self.assertIn("const newCategory = ref('preferencia');", panel)
        self.assertIn('placeholder="Ej. prefiero la tirada larga el domingo"', panel)


class RestablecerViveEnCuenta(unittest.TestCase):

    def test_fuera_de_avanzado_y_con_borrar_escrito(self):
        perfil = (FRONT / "views/admin/ProfileView.vue").read_text(encoding="utf-8")
        self.assertNotIn("btn-delete-profile", perfil)
        cuenta = (FRONT / "components/cuenta/CuentaPanel.vue").read_text(encoding="utf-8")
        self.assertIn("confirmacion.trim().toUpperCase() !== 'BORRAR'", cuenta)
        self.assertIn("Tendrás que volver a", cuenta, "borrar el perfil borra también la contraseña de Garmin")
        ajustes = (FRONT / "components/modals/SettingsModal.vue").read_text(encoding="utf-8")
        self.assertIn("{ id: 'cuenta', nombre: 'Cuenta'", ajustes)


class SaludCuentaLoEscrito(unittest.TestCase):

    def test_las_lesiones_pasadas_escritas_cuentan(self):
        perfil = (FRONT / "views/admin/ProfileView.vue").read_text(encoding="utf-8")
        self.assertIn("lleno(formData.past_injuries_surgeries)", perfil)


class LosCamposSeVen(unittest.TestCase):

    def test_el_borde_de_los_campos_llega_a_3_a_1(self):
        css = (FRONT / "style.css").read_text(encoding="utf-8")
        self.assertIn("border: 1px solid rgba(255, 255, 255, 0.40) !important;", css)


class RevisionCompletaDel22DeOctubre(unittest.TestCase):
    """La revisión completa de la app (PC y teléfono), ronda 1, y lo que vio el responsable."""

    def test_un_garmin_ya_visto_no_pide_conectar_garmin(self):
        """José, con su Garmin conectado desde antes de la 035, veía «Conecta tu Garmin para empezar tus semanas
        gratis» para siempre: el estado era `sin_empezar` aunque el Garmin estuviera conectado."""
        acceso = (RAIZ / "Backend/src/Services/AccessService.php").read_text(encoding="utf-8")
        self.assertIn("$this->garminConectado($userId) ? 'garmin_usado' : 'sin_empezar'", acceso)
        plan = (FRONT / "views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("acceso.estado === 'garmin_usado'", plan)

    def test_una_semana_en_curso_no_tiene_veredicto_en_el_historial(self):
        """José, jueves 2026-10-22: generar la semana del 26 midió a mitad la del 19 al 25 y el Historial decía
        «3 de 3 sesiones hechas · 1 de 2 clave · 2,2 h de 5,8 h» en verde. Esa línea se vuelve a medir al
        terminar la semana: hasta entonces es «en curso», sin color de bien o mal."""
        repo = (RAIZ / "Backend/src/Repositories/TrackRecordRepository.php").read_text(encoding="utf-8")
        self.assertIn("'en_curso' => !empty($w['to']) && (string) $w['to'] >= date('Y-m-d')", repo)
        historial = (FRONT / "views/admin/HistorialView.vue").read_text(encoding="utf-8")
        en_curso = historial.index('v-if="e.resultado?.en_curso"')
        self.assertLess(en_curso, historial.index('v-else-if="e.resultado"'))
        self.assertIn("En curso · hasta hoy", historial)

    def test_la_hoja_de_la_semana_reparte_el_ancho_antes_de_encoger(self):
        """Semana real de José del 26-oct (fuerza y bici el martes): con siete columnas iguales la hoja medía
        1.262 px para 726 de página, hacía falta reducirla al 56 % (el suelo es 72 %) y el pie salía en una
        tercera página en blanco. Con el ancho de cada día según su contenido, 82 %; la del 19-oct pasa del 73 %
        al 100 %. El descanso queda estrecho, pero con su fecha entera."""
        hoja = (RAIZ / "PaginaLocal/services/ai/rendering/templates/printable_plan.html.jinja2").read_text(encoding="utf-8")
        self.assertIn("p.querySelectorAll('.days').forEach(repartir);", hoja)
        self.assertLess(hoja.index("forEach(repartir)"), hoja.index("var z = Math.min(1, (ALTO * 0.97)"),
                        "primero se reparte el ancho y después se reduce lo que falte")
        self.assertIn("p.style.zoom = Math.max(0.72, z);", hoja)
        self.assertIn("white-space: nowrap; }", hoja)

    def test_los_bloques_de_fuerza_en_palabras(self):
        """«A · 3 SEGUIDOS · 1 RONDAS · 30" ENTRE RONDAS» (plural roto, comillas por segundos)."""
        plan = (FRONT / "views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("rondas === 1 ? '1 ronda'", plan)
        self.assertIn("if (t < 60) return `${t} s`;", plan)

    def test_el_checkin_dice_que_genera_y_ganas_va_como_las_demas(self):
        modal = (FRONT / "components/modals/CheckinModal.vue").read_text(encoding="utf-8")
        self.assertIn("'Guardar y generar mi semana'", modal)
        self.assertIn("Generar sin responder", modal)
        # Lo bueno a la izquierda en las cinco escalas; el valor guardado no cambia (7 = muchas ganas).
        self.assertIn('v-for="n in [7, 6, 5, 4, 3, 2, 1]"', modal)

    def test_lo_recomendado_es_el_boton_principal(self):
        plan = (FRONT / "views/admin/PlanView.vue").read_text(encoding="utf-8")
        self.assertIn("confirmButtonText: `Esperar al ${fechaCorta(antes.desde)}`", plan)
        self.assertIn("if (r.isConfirmed || r.dismiss !== 'cancel') return;", plan)

    def test_cuenta_dice_todo_lo_que_borra(self):
        """`deleteByUserId` borra también el plan, el historial, el cuaderno y los check-in; la lista lo callaba."""
        cuenta = (FRONT / "components/cuenta/CuentaPanel.vue").read_text(encoding="utf-8")
        self.assertIn("tu plan, tus planes anteriores, tu cuaderno y tus check-in", cuenta)
        repo = (RAIZ / "Backend/src/Repositories/ProfileRepository.php").read_text(encoding="utf-8")
        for tabla in ("athlete_plans", "athlete_plan_history", "athlete_notebook", "athlete_checkins"):
            self.assertIn(f"'{tabla}'", repo)

    def test_una_sola_navegacion_con_historial(self):
        cabecera = (FRONT / "components/layout/TopHeader.vue").read_text(encoding="utf-8")
        # La barra de arriba ya no repite el menú (INICIO · PROGRESO · INFORMES, sin Plan ni Cuaderno).
        self.assertNotIn('to="/admin/analytics"', cabecera)
        self.assertNotIn('to="/admin/reports"', cabecera)
        menu = (FRONT / "components/layout/Sidebar.vue").read_text(encoding="utf-8")
        self.assertIn("route: '/admin/historial'", menu)


if __name__ == "__main__":
    unittest.main()
