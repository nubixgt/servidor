"""El perfil en el teléfono: Identidad, Objetivos y días e Historial (revisión del 2026-10-21).

El responsable pidió mejorar la UI y la UX de esas tres pestañas con una revisión que no se sesgara: dos
evaluadores nuevos y ciegos por ronda (una persona, Andrea, primera vez y sin jerga, y una evaluación
heurística), que no sabían que hubo una versión anterior. Lo que encontraron a 375 px, como José (atleta 2):

- la tabla de días medía 520 px y enseñaba lunes a miércoles, sin pista de que hubiera más (gravedad 4);
- los tiempos h/min/s cortaban las cifras («26» se veía «2(»);
- cada carrera abierta ocupaba ~600 px: Objetivos medía 7.358 px (después, 3.571);
- «Guardar» sólo arriba, siempre lima y sin decir si había cambios;
- la contraseña de Garmin con siete líneas técnicas de 11 px como primera impresión;
- cada toque de la tabla recorría vacío → posible → fijo y había que descubrirlo.

Correr con:  python -m unittest tests.test_perfil_en_el_telefono -v
"""

import re
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
FORMS = RAIZ / "Frontend/src/components/forms"
PERFIL = (RAIZ / "Frontend/src/views/admin/ProfileView.vue").read_text(encoding="utf-8")


class LaTablaDeDias(unittest.TestCase):

    def test_los_siete_dias_caben_sin_desplazarse(self):
        """Y el deporte va ENCIMA de su fila (decisión del responsable, 2026-10-21): en una columna a la izquierda
        las casillas medían 33 px de ancho; así, 39×43 medido a 375 px como José."""
        self.assertNotIn("min-w-[520px]", PERFIL)
        tabla = PERFIL[PERFIL.index('role="grid"'):]
        tabla = tabla[:tabla.index('<b class="text-white">Puedo</b>')]
        self.assertIn('role="rowheader"', tabla)
        self.assertIn("grid grid-cols-7", tabla, "las siete casillas de un deporte en su propia fila")
        self.assertIn("inicialDelDia(day)", tabla, "cada casilla dice su día")

    def test_se_marca_tocando_y_cada_toque_pasa_al_siguiente(self):
        """Decisión del responsable (2026-10-21, tras probar el «pincel» de la revisión): un toque «Puedo», dos
        «Sí o sí», tres vuelve a descanso. Elegir antes qué marcar era un paso más y peor experiencia."""
        self.assertIn("rotarCasilla(dep, day)", PERFIL)
        self.assertNotIn("pintarCasilla", PERFIL)
        self.assertIn("const SIGUIENTE = { '': ESTADO_POSIBLE, [ESTADO_POSIBLE]: ESTADO_FIJO, [ESTADO_FIJO]: '' };", PERFIL)

    def test_en_pantalla_ancha_es_una_tabla(self):
        """En PC salía todo pegado a la izquierda, a tamaño de teléfono (el responsable, 2026-10-21)."""
        self.assertIn("sm:grid-cols-[9rem_repeat(7,minmax(3.25rem,1fr))]", PERFIL)
        self.assertNotIn("max-w-[19rem]", PERFIL)


class GuardarSeVe(unittest.TestCase):

    def test_hay_estado_de_cambios_y_aviso_al_salir(self):
        self.assertIn("hayCambios", PERFIL)
        self.assertIn("Cambios sin guardar", PERFIL)
        self.assertIn("onBeforeRouteLeave", PERFIL)
        self.assertIn("beforeunload", PERFIL)

    def test_la_foto_se_toma_despues_de_cargar_y_de_guardar(self):
        """Sin la foto tras `seed`, el perfil recién abierto saldría «con cambios»."""
        self.assertRegex(PERFIL, r"const seed = \(p\) => \{[^}]*instantanea\.value = fotoDelFormulario\(\)")
        self.assertIn("instantanea.value = fotoDelFormulario();\n", PERFIL)


class ListasQueSePliegan(unittest.TestCase):

    def test_carreras_y_marcas_son_filas(self):
        self.assertTrue((FORMS / "FilaPlegable.vue").exists())
        self.assertEqual(len(re.findall(r"<FilaPlegable\b", PERFIL)), 3, "carreras que vienen, corridas y marcas")

    def test_quitar_pregunta_antes(self):
        for funcion in ("quitarCarrera", "quitarMarca"):
            cuerpo = PERFIL[PERFIL.index(f"const {funcion} = async"):]
            self.assertIn("swal.fire", cuerpo[:600], funcion)

    def test_las_carreras_se_ordenan_al_cargar(self):
        """La de septiembre salía entre las que vienen como «Competición 1»."""
        self.assertIn("formData.competitions.sort(porFecha)", PERFIL)
        self.assertIn("function porFecha", PERFIL, "declarada: `seed` la usa antes de su línea")


class ElProgramaDeFuerzaEnSuPantalla(unittest.TestCase):
    """Desplegado dentro del perfil eran ~3.160 px a 375 px (decisión del responsable, 2026-10-21)."""

    HOJA = (FORMS / "ProgramaDeFuerzaHoja.vue").read_text(encoding="utf-8")
    FUERZA = (FORMS / "PerfilDeFuerza.vue").read_text(encoding="utf-8")

    def test_ver_programa_abre_la_pantalla_y_no_despliega_aqui(self):
        self.assertIn("<ProgramaDeFuerzaHoja", self.FUERZA)
        self.assertNotIn('<template v-if="verPrograma">', self.FUERZA)

    def test_una_sesion_cada_vez_y_se_puede_salir(self):
        self.assertIn('role="tab"', self.HOJA)
        self.assertIn("<Teleport to=\"body\">", self.HOJA, "dentro de una vista con transform, fixed se descoloca")
        self.assertIn("e.key === 'Escape'", self.HOJA)
        self.assertIn("document.body.style.overflow = overflowAntes", self.HOJA, "la página vuelve a desplazarse")


class LosTiemposSeLeen(unittest.TestCase):

    def test_cada_campo_cabe_sus_cifras(self):
        tiempo = (FORMS / "TiempoHMS.vue").read_text(encoding="utf-8")
        self.assertIn("tiempo-campo", tiempo)
        self.assertRegex(tiempo, r"\.tiempo-campo \{[^}]*padding-left: 0\.25rem !important")


class CuandoEsMesYAnio(unittest.TestCase):

    def test_se_guarda_como_texto_y_no_se_pierde_lo_escrito(self):
        """Sigue siendo texto («septiembre 2026»): sin migración, y lo que no se entiende se enseña."""
        mes = (FORMS / "MesAnio.vue").read_text(encoding="utf-8")
        self.assertIn("[p.mes, p.anio].filter(Boolean).join(' ')", mes)
        self.assertIn("noSeEntiende", mes)
        self.assertIn('<MesAnio v-model="m.date"', PERFIL)


class LaContrasenaDeGarmin(unittest.TestCase):

    def test_el_detalle_tecnico_sigue_entero(self):
        """Se plegó, no se quitó: es lo que la protege y lo que no."""
        self.assertIn("No la protege de alguien con acceso", PERFIL)
        self.assertIn("Cómo la guardamos", PERFIL)

    def test_se_puede_ver_lo_escrito(self):
        self.assertIn(":type=\"verClave ? 'text' : 'password'\"", PERFIL)


class DecisionesDelResponsable(unittest.TestCase):
    """Decisiones del 2026-10-21 sobre lo que los evaluadores vieron duplicado o sin definir."""

    UTIL = (RAIZ / "Frontend/src/utils/profile.js").read_text(encoding="utf-8")

    def test_el_descanso_sale_de_la_tabla(self):
        """«Día de descanso preferido» repetía la tabla y podía contradecirla (un sábado de descanso con un
        «fijo» el sábado). Con tabla se guarda vacío: un día sin nada ES descanso para el prompt."""
        self.assertNotIn('v-model="formData.preferred_rest_day"', PERFIL)
        self.assertIn("preferred_rest_day: Object.keys(sport_days).length ? '' : (f.preferred_rest_day || '')", self.UTIL)

    def test_la_distancia_principal_es_la_de_la_carrera_a(self):
        """José: carrera A 10K y «Tu distancia principal: 10K» en un selector aparte."""
        self.assertIn("export function distanciaDeLaCarreraA", self.UTIL)
        self.assertIn("const meta = distanciaDeLaCarreraA(f.competitions) || (f.target_distance || '').trim();", self.UTIL)
        self.assertIn("target_distance: meta,", self.UTIL)
        self.assertIn("triathlon_distance: meta.startsWith('tri_')", self.UTIL, "la de triatlón sale de la misma")
        self.assertIn('<div v-else class="border-t border-white/10 pt-5">\n          <label :class="labelClass">Tu distancia principal</label>', PERFIL)

    def test_cada_nivel_tiene_su_definicion(self):
        for nivel in ("principiante", "intermedio", "avanzado", "elite"):
            self.assertRegex(PERFIL, rf"{nivel}: '[^']+'", nivel)

    def test_continuar_conecta_el_garmin_escrito(self):
        """Quien escribía correo y contraseña y pulsaba «Guardar y continuar» creía haber conectado. Ahora conecta,
        y si Garmin la rechaza no avanza."""
        cuerpo = PERFIL[PERFIL.index("const siguientePaso = async"):]
        cuerpo = cuerpo[:cuerpo.index("\n};")]
        self.assertLess(cuerpo.index("await handleGarminImport()"), cuerpo.index("handleSave"))
        self.assertIn("if (garminError.value) return;", cuerpo)
        self.assertIn("'Conectar y continuar'", PERFIL)

    def test_la_pestana_se_llama_mi_experiencia(self):
        """El menú ya tiene un «Historial» (los planes anteriores): dos cosas con el mismo nombre."""
        self.assertIn("{ id: 'historial', label: 'Mi experiencia' }", PERFIL)

    def test_recordar_la_contrasena_sigue_marcada(self):
        """El responsable la mantiene marcada: la semana automática y los ✓ del reloj la necesitan."""
        self.assertIn("const recordarClave = ref(true);", PERFIL)


if __name__ == "__main__":
    unittest.main()
