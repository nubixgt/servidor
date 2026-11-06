"""v7 -- auditoría de interfaz. Lo que se rompió mirándolo, no leyéndolo.

Seis hallazgos de una pasada por la app corriendo, con el plan real del
atleta cargado. Dos de ellos eran regresiones introducidas por el propio
bloque de seguridad de v7, y el primero habría dejado a un beta mirando una
pantalla muerta después de pagar su generación.

Correr con:  python -m unittest discover -s tests -v
"""

import re
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from services.ai.prescription import render_zones_table  # noqa: E402

RAIZ = Path(__file__).resolve().parents[1]
APP_JS = (RAIZ / "web_app/static/app.js").read_text(encoding="utf-8")
CSS = (RAIZ / "web_app/static/styles.css").read_text(encoding="utf-8")

# Las zonas REALES de este atleta: Garmin devuelve una tabla por deporte y
# tres de las cuatro son idénticas.
ZONAS_REALES = {
    "hr_zones_by_sport": {
        "other": {"1": [123, 139], "2": [140, 153]},
        "bike": {"1": [123, 139], "2": [140, 153]},
        "swim": {"1": [93, 111], "2": [112, 129]},
        "run": {"1": [123, 139], "2": [140, 153]},
    }
}


class UnDiagnosticoNoPuedeTumbarLaGeneracion(unittest.TestCase):
    """El hallazgo grave, y una regresión de v7.

    `pollStatus()` pedía `/api/logs` -- que el bloque de seguridad restringió
    al operador -- ANTES de mirar el estado de la generación y sin protección
    propia. A un atleta normal le responde 403, `logs.logs` sale `undefined`,
    `.join` revienta, y el `catch` vacío se lo traga: la barra se congela, los
    botones quedan deshabilitados para siempre y el plan nunca aparece,
    aunque el servidor lo haya generado y cobrado bien.
    """

    def test_el_log_se_pide_en_su_propia_funcion_protegida(self):
        self.assertIn("async function refreshTechnicalLog()", APP_JS)
        cuerpo = APP_JS[APP_JS.index("async function refreshTechnicalLog()"):]
        cuerpo = cuerpo[:cuerpo.index("async function pollStatus()")]
        self.assertIn("try {", cuerpo)
        self.assertIn("catch", cuerpo)

    def test_pollstatus_ya_no_pide_el_log_directamente(self):
        cuerpo = APP_JS[APP_JS.index("async function pollStatus()"):]
        cuerpo = cuerpo[:cuerpo.index("\n}", cuerpo.index("if (st.status === 'failed')"))]
        self.assertNotIn("fetch('/api/logs')", cuerpo)

    def test_un_403_deja_de_pedirlo_en_vez_de_reintentar_cada_segundo(self):
        """El sondeo corre cada pocos segundos durante minutos. Insistir en
        una ruta que siempre va a devolver 403 llena el log del servidor y no
        arregla nada."""
        cuerpo = APP_JS[APP_JS.index("async function refreshTechnicalLog()"):]
        cuerpo = cuerpo[:cuerpo.index("async function pollStatus()")]
        self.assertIn("logTecnicoDisponible = false", cuerpo)

    def test_y_esconde_el_desplegable_que_nunca_se_abrira(self):
        cuerpo = APP_JS[APP_JS.index("async function refreshTechnicalLog()"):]
        cuerpo = cuerpo[:cuerpo.index("async function pollStatus()")]
        self.assertIn("classList.add('hidden')", cuerpo)


class LaVistaDelPlanSeReleeAlEntrar(unittest.TestCase):
    """Se pintaba con lo que hubiera en memoria desde la carga de la página.
    Reproducido: con la app abierta se generó un plan nuevo y la vista siguió
    mostrando el anterior -- fechas, sesiones y todo -- sin ninguna señal de
    estar desactualizada. En la pantalla donde el atleta lee qué entrena hoy,
    un dato viejo se lee como la verdad."""

    def test_navegar_a_plan_dispara_la_recarga(self):
        self.assertIn("if (target === 'plan') fetchCurrentPlan();", APP_JS)

    def test_las_demas_vistas_con_datos_ya_lo_hacian(self):
        for vista, funcion in (("historial", "loadHistory"), ("cuaderno", "loadNotebook")):
            with self.subTest(vista=vista):
                self.assertIn(f"if (target === '{vista}') {funcion}();", APP_JS)


class LaSerieNoSeLeeDosVeces(unittest.TestCase):
    """El origen quedó corregido en `expand()`, pero los planes YA GUARDADOS
    siguen trayendo la descripción duplicada en el paso hijo y no se van a
    regenerar solos. La pantalla tiene que sobrevivir a sus propios datos
    viejos."""

    def test_el_hijo_que_repite_la_cabecera_no_se_pinta(self):
        cuerpo = APP_JS[APP_JS.index("function renderPlanSteps("):]
        cuerpo = cuerpo[:cuerpo.index("\n}\n")]
        self.assertIn("s.description !== step.description", cuerpo)

    def test_pero_si_todos_los_hijos_coinciden_se_pintan_igual(self):
        """Filtrar hasta dejar el bloque vacío escondería la sesión entera.
        Ante la duda, se muestra de más."""
        cuerpo = APP_JS[APP_JS.index("function renderPlanSteps("):]
        cuerpo = cuerpo[:cuerpo.index("\n}\n")]
        self.assertIn("hijos.length ? hijos : step.substeps", cuerpo)


class LaBarraLateralCabeEnUnMovil(unittest.TestCase):
    """Regresión de v7: el pie pasó de 3 a 5 elementos al añadir Feedback y
    Salir. Medido en un viewport de 375 px, la página entera scrolleaba en
    horizontal (404 px)."""

    def test_el_pie_envuelve(self):
        bloque = CSS[CSS.index(".sidebar-footer {"):]
        bloque = bloque[:bloque.index("}")]
        self.assertIn("flex-wrap: wrap", bloque)


class LasZonasIgualesNoSeRepitenCuatroVeces(unittest.TestCase):
    """Garmin devuelve una tabla por deporte y muy a menudo varias son
    idénticas. En este atleta, carrera, bici y "otros" son las mismas y sólo
    natación difiere -- y listar las cuatro enterraba justamente la única
    diferencia que importa: que nadando entrena 28 ppm más abajo."""

    def test_las_identicas_se_agrupan_en_una_linea(self):
        tabla = render_zones_table(ZONAS_REALES)
        self.assertEqual(len(re.findall(r"FC [^:]+:", tabla)), 2)
        self.assertIn("FC Carrera, Bici y Otros:", tabla)
        self.assertIn("FC Natación:", tabla)

    def test_no_se_pierde_ni_un_numero(self):
        tabla = render_zones_table(ZONAS_REALES)
        for valor in ("123-139", "140-153", "93-111", "112-129"):
            with self.subTest(valor=valor):
                self.assertIn(valor, tabla)

    def test_los_deportes_reales_van_antes_que_el_cajon_de_sastre(self):
        """El atleta busca su deporte; "Otros" es la etiqueta que menos le
        dice."""
        tabla = render_zones_table(ZONAS_REALES)
        self.assertLess(tabla.index("Carrera"), tabla.index("Otros"))

    def test_si_todas_difieren_se_listan_todas(self):
        tabla = render_zones_table({"hr_zones_by_sport": {
            "swim": {"1": [93, 111]}, "run": {"1": [123, 139]}, "bike": {"1": [110, 130]},
        }})
        self.assertEqual(len(re.findall(r"FC [^:]+:", tabla)), 3)

    def test_sin_zonas_no_inventa_una_cabecera(self):
        self.assertEqual(render_zones_table({}), "")



class ElCableadoDeLaInterfazNoPuedeROMPERSE(unittest.TestCase):
    """Las tres formas en que un botón deja de hacer nada sin que nadie se
    entere, comprobadas leyendo los ficheros en vez de haciendo clic.

    Ninguna de las tres da error en la consola del atleta: `getElementById`
    devuelve `null` y el `addEventListener` que lo sigue es el que revienta,
    con lo que TODO lo que se enganchara después de esa línea se queda sin
    enganchar. Un `id` mal escrito en un sitio apaga media pantalla.

    Correr esta comprobación a mano encontró la app entera limpia; la
    diferencia entre eso y esto es que esto se corre solo la próxima vez.
    """

    HTML = (RAIZ / "web_app/static/index.html").read_text(encoding="utf-8")
    LOGIN = (RAIZ / "web_app/static/login.html").read_text(encoding="utf-8")
    SERVER = (RAIZ / "web_app/server.py").read_text(encoding="utf-8")

    def _ids_disponibles(self):
        # Los del HTML servido, más los que el propio JS crea con plantillas.
        return (set(re.findall(r'id="([A-Za-z0-9_\-]+)"', self.HTML))
                | set(re.findall(r'id="([A-Za-z0-9_\-]+)"', self.LOGIN))
                | set(re.findall(r'id="([A-Za-z0-9_\-]+)"', APP_JS)))

    def test_todo_getElementById_apunta_a_algo_que_existe(self):
        pedidos = set(re.findall(r"getElementById\(['\"]([A-Za-z0-9_\-]+)['\"]\)", APP_JS))
        faltan = sorted(pedidos - self._ids_disponibles())
        self.assertEqual(faltan, [], f"el JS busca ids que no existen: {faltan}")

    def test_ningun_boton_con_id_se_queda_sin_accion(self):
        """Un botón que no hace nada es peor que un botón ausente: el atleta
        lo pulsa, no pasa nada, y no sabe si falló él o la app."""
        huerfanos = []
        for tag in re.findall(r"<button[^>]*>", self.HTML):
            m = re.search(r'id="([A-Za-z0-9_\-]+)"', tag)
            if not m:
                continue
            bid = m.group(1)
            if "onclick" in tag:
                continue
            if f"getElementById('{bid}')" in APP_JS or f'getElementById("{bid}")' in APP_JS:
                continue
            huerfanos.append(bid)
        self.assertEqual(huerfanos, [], f"botones sin listener ni onclick: {huerfanos}")

    def test_todo_fetch_a_una_api_tiene_una_ruta_detras(self):
        rutas = set(re.findall(r'@app\.(?:get|post|put|delete)\("([^"]+)"', self.SERVER))
        patrones = [re.compile("^" + re.sub(r"\{[^}]+\}", "[^/]+", r) + "$") for r in rutas]

        def existe(p):
            # La ruta puede llevar el id pegado (`/api/notebook/${id}`): se
            # acepta también su carpeta padre.
            return any(x.match(p) or x.match(p.rstrip("/")) for x in patrones) or any(
                r.startswith(p.rstrip("/") + "/") for r in rutas)

        pedidas = set(re.findall(r"fetch\(\s*[`'\"](/api/[A-Za-z0-9_\-/]*)", APP_JS))
        rotas = sorted(p for p in pedidas if not existe(p))
        self.assertEqual(rotas, [], f"el JS llama a rutas que el servidor no tiene: {rotas}")

    def test_las_clases_que_el_JS_enciende_estan_definidas_en_el_CSS(self):
        """`classList.add('x')` con una `x` que el CSS no conoce no falla: no
        se ve. Es como se pierde un aviso que el código cree estar dando."""
        definidas = set(re.findall(r"\.([a-z][a-z0-9_\-]+)\s*[,{:]", CSS))
        usadas = set(re.findall(r"classList\.(?:add|remove|toggle)\(\s*['\"]([a-z][a-z0-9_\-]+)", APP_JS))
        # `hidden` la define el propio HTML/CSS base y se usa en todas partes.
        faltan = sorted(usadas - definidas - {"hidden", "active"})
        self.assertEqual(faltan, [], f"clases sin estilo: {faltan}")


if __name__ == "__main__":
    unittest.main(verbosity=2)
