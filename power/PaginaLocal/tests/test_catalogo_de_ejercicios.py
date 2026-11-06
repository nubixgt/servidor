"""La imagen que ve el atleta es la del ejercicio que le recetaron.

## De dónde salió esto

Beta de septiembre de 2026, cuatro atletas. Dos quejas sobre la misma pantalla:
"la rutina de gym es una mierda" y "aveces dice 'bicho muerto', cuando el
nombre conocido es dead bug, así también algunos ejercicios no saben como
hacerlos". La segunda es la que arregla este módulo: el plan describía el
movimiento con palabras y nada más, y el atleta lo leía en el gimnasio entre
serie y serie.

## Qué se fija aquí

1. **Nunca la imagen equivocada.** Es la única garantía que importa de verdad:
   un dibujo que no es el del ejercicio recetado hace que el atleta entrene
   otro movimiento creyendo que hace el suyo. Ante la duda, sin imagen.
2. **Las malas traducciones resuelven bien.** "bicho muerto" tiene que llevar
   a la imagen de dead bug, porque es lo que el modelo escribe.
3. **Las dos copias del catálogo no divergen.** La de Python (la que alimenta
   el prompt) y la de `Frontend/public/ejercicios/catalogo.json` (la que pinta
   la imagen) salen de la misma fuente, y hay un script que lo garantiza. Si
   alguien edita el JSON a mano, esta prueba lo dice.
4. **Cada imagen declarada existe en el disco.** Un 404 en el móvil del atleta
   es un hueco roto donde debía haber una instrucción.

Correr con:  python -m unittest discover -s tests
"""

import json
import sys
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(RAIZ))

from services.ai.exercise_catalog import (  # noqa: E402
    ALIAS,
    EJERCICIOS,
    SIN_IMAGEN,
    imagen_de,
    normalizar,
    numero_de,
    render_catalogo_para_prompt,
    resolver,
)

IMAGENES = RAIZ.parent / "Frontend" / "public" / "ejercicios"
CATALOGO_JSON = IMAGENES / "catalogo.json"


class NuncaLaImagenEquivocada(unittest.TestCase):
    """Abstenerse antes que acertar por suerte."""

    def test_un_nombre_desconocido_no_devuelve_nada(self):
        for inventado in (
            "press de banca con mancuernas raro",
            "ejercicio que no existe",
            "sentadilla con salto lateral en bosu invertido",
            "",
            None,
        ):
            with self.subTest(nombre=inventado):
                self.assertIsNone(resolver(inventado))

    def test_no_hay_coincidencia_parcial(self):
        """"peso muerto rumano" y "…a una pierna" son ejercicios distintos.

        Un emparejamiento por prefijo mandaría el segundo a la imagen del
        primero: misma familia, carga y riesgo distintos.
        """
        self.assertEqual(resolver("Peso muerto rumano"), "peso_muerto_rumano")
        self.assertEqual(
            resolver("Peso muerto rumano a una pierna"), "peso_muerto_rumano_a_una_pierna"
        )
        self.assertNotEqual(numero_de("peso_muerto_rumano"),
                            numero_de("peso_muerto_rumano_a_una_pierna"))

    def test_ningun_alias_apunta_a_dos_ejercicios(self):
        """Un alias ambiguo es una imagen equivocada esperando su turno."""
        for clave, destino in ALIAS.items():
            with self.subTest(alias=clave):
                self.assertIn(destino, EJERCICIOS)
                self.assertNotIn(clave, EJERCICIOS,
                                 f"'{clave}' es a la vez ejercicio y alias de otro")


class LasMalasTraduccionesResuelven(unittest.TestCase):
    """Las que el atleta reportó, y las que el prompt ya nombra como prohibidas."""

    CASOS = {
        "bicho muerto": "dead_bug",
        "insecto muerto": "dead_bug",
        "escarabajo muerto": "dead_bug",
        "Dead bug": "dead_bug",
        "perro pajaro": "bird_dog",
        "pájaro perro": "bird_dog",
        "escultura": "sculling",
        "Sculling": "sculling",
    }

    def test_resuelven_al_ejercicio_correcto(self):
        for escrito, esperado in self.CASOS.items():
            with self.subTest(escrito=escrito):
                self.assertEqual(resolver(escrito), esperado)

    def test_da_igual_como_venga_escrito(self):
        """El modelo escribe el mismo ejercicio de cinco formas distintas."""
        for variante in (
            "Sentadilla búlgara", "sentadilla bulgara", "SENTADILLA_BULGARA",
            "sentadilla-búlgara", "Bulgarian split squat", "zancada búlgara",
        ):
            with self.subTest(variante=variante):
                self.assertEqual(resolver(variante), "sentadilla_bulgara")

    def test_el_apostrofo_no_separa(self):
        """"farmer's carry" y "farmers carry" son el mismo ejercicio.

        Convirtiendo el apóstrofo en separador salían `farmer_s_carry` y
        `farmers_carry`, y el segundo no encontraba nada.
        """
        for variante in ("farmer's carry", "farmers carry", "FARMER'S CARRY",
                         "Paseo del granjero"):
            with self.subTest(variante=variante):
                self.assertEqual(resolver(variante), "paseo_del_granjero")

    def test_video_key_manda_sobre_el_nombre(self):
        """La clave la eligió el modelo de la lista cerrada; el nombre es libre."""
        self.assertEqual(
            resolver("un nombre cualquiera que se invento", "sentadilla_bulgara"),
            "sentadilla_bulgara",
        )

    def test_el_nombre_rescata_a_los_planes_viejos(self):
        """Los planes generados antes del catálogo no traen `video_key`."""
        self.assertEqual(resolver("Remo renegado", None), "remo_renegado")
        self.assertEqual(resolver("Almeja", ""), "almeja")


class LasImagenesExisten(unittest.TestCase):
    def test_cada_imagen_declarada_esta_en_el_disco(self):
        faltan = [
            imagen_de(slug) for slug in EJERCICIOS
            if imagen_de(slug) and not (IMAGENES / imagen_de(slug)).is_file()
        ]
        self.assertEqual(faltan, [], f"declaradas pero ausentes: {faltan}")

    def test_las_que_faltan_devuelven_none_y_no_una_ruta_rota(self):
        """El 086 (estiramiento de cadera 90/90) no se generó: el nombre del
        fichero habría llevado una barra, que no vale en un nombre de fichero.

        Lo importante no es que falte, es que se sepa: `imagen_de` devuelve
        None y la UI no pinta el botón, en vez de pintar un hueco roto.
        """
        for n in SIN_IMAGEN:
            slug = next(s for s, datos in EJERCICIOS.items() if datos[0] == n)
            with self.subTest(n=n):
                self.assertIsNone(imagen_de(slug))
                self.assertIsNotNone(resolver(EJERCICIOS[slug][1]))  # se conoce igual

    def test_no_sobran_imagenes_sin_ejercicio(self):
        """Un JPG que ningún ejercicio reclama es peso muerto en el despliegue."""
        numeros = {datos[0] for datos in EJERCICIOS.values()}
        sueltas = [
            f.name for f in IMAGENES.glob("*.jpg")
            if not (f.stem.isdigit() and int(f.stem) in numeros)
        ]
        self.assertEqual(sueltas, [])


class LasDosCopiasNoDivergen(unittest.TestCase):
    """La de Python alimenta el prompt; la del frontend pinta la imagen."""

    def setUp(self):
        if not CATALOGO_JSON.is_file():
            self.skipTest("catalogo.json no generado todavía")
        self.json = json.loads(CATALOGO_JSON.read_text(encoding="utf-8"))

    def test_mismos_ejercicios_en_las_dos(self):
        self.assertEqual(set(self.json["ejercicios"]), set(EJERCICIOS))

    def test_mismos_alias_en_las_dos(self):
        self.assertEqual(self.json["alias"], dict(ALIAS))

    def test_mismos_numeros_y_mismas_imagenes(self):
        for slug, (n, es, _en, _g, _s) in EJERCICIOS.items():
            with self.subTest(slug=slug):
                self.assertEqual(self.json["ejercicios"][slug]["n"], n)
                self.assertEqual(self.json["ejercicios"][slug]["es"], es)
                self.assertEqual(self.json["ejercicios"][slug]["img"], imagen_de(slug))


class ElBloqueDelPrompt(unittest.TestCase):
    def test_ofrece_una_lista_cerrada_y_dice_que_no_la_invente(self):
        bloque = render_catalogo_para_prompt()
        self.assertIn("video_key", bloque)
        self.assertIn("sentadilla_bulgara", bloque)
        self.assertIn("deja `video_key` vacio", bloque)

    def test_solo_lleva_los_grupos_que_caben_en_un_strength_block(self):
        """Los drills de natación y carrera no van en cada sesión de fuerza."""
        bloque = render_catalogo_para_prompt()
        self.assertNotIn("sculling", bloque)
        self.assertNotIn("a_skip", bloque)
        self.assertIn("dead_bug", bloque)

    def test_cada_clave_ofrecida_resuelve_a_si_misma(self):
        """Si el modelo copia una clave del bloque, tiene que encontrarla."""
        bloque = render_catalogo_para_prompt(("fuerza", "movilidad", "natacion", "carrera"))
        for slug in EJERCICIOS:
            with self.subTest(slug=slug):
                self.assertIn(slug, bloque)
                self.assertEqual(resolver(None, slug), slug)


class LaNormalizacionEsLaMisma(unittest.TestCase):
    """Si Python y JavaScript dejan de normalizar igual, un ejercicio resuelve
    en el servidor y no en el navegador sin que nadie se entere. El gemelo de
    esta prueba vive en `Frontend/src/utils/ejercicios.js` como comentario: no
    hay suite de frontend, así que al menos se fija aquí qué se espera.
    """

    def test_casos_frontera(self):
        self.assertEqual(normalizar("Sentadilla búlgara"), "sentadilla_bulgara")
        self.assertEqual(normalizar("  Peso   muerto--rumano  "), "peso_muerto_rumano")
        self.assertEqual(normalizar("Farmer's Carry"), "farmers_carry")
        self.assertEqual(normalizar("90/90"), "90_90")
        self.assertEqual(normalizar(None), "")
        self.assertEqual(normalizar("   "), "")


if __name__ == "__main__":
    unittest.main()


class ElPluralSeLeeIgualEnLasDos(unittest.TestCase):
    """Lectura real del 2026-09-23: "Flexiones" y "Zancadas atrás" no resolvían porque los
    alias están en singular. Python y el navegador tienen que leerlo igual."""

    def test_python_resuelve_los_plurales(self):
        from services.ai.exercise_catalog import resolver
        self.assertEqual(resolver("Flexiones"), "flexion")
        self.assertEqual(resolver("Zancadas atrás"), "zancada_inversa")
        self.assertEqual(resolver("Sentadillas búlgaras"), "sentadilla_bulgara")
        self.assertIsNone(resolver("Press"))

    def test_js_da_lo_mismo(self):
        import json
        import shutil
        import subprocess
        import tempfile

        from services.ai.exercise_catalog import _en_singular, normalizar
        if not shutil.which("node"):
            self.skipTest("sin node")
        js = (Path(__file__).resolve().parents[2] / "Frontend/src/utils/ejercicios.js").as_uri()
        nombres = ["Flexiones", "Zancadas atrás", "Sentadillas búlgaras", "Dominadas", "Nueces", "Press",
                   "Abdominales", "Bíceps", "Remo con barra", "Extensiones de cuádriceps"]
        claves = [normalizar(n) for n in nombres]
        script = (f"import {{ enSingular }} from '{js}';" + chr(10)
                  + f"const c = {json.dumps(claves)};" + chr(10)
                  + "console.log(JSON.stringify([c.map(x => enSingular(x)), c.map(x => enSingular(x, true))]));"
                  + chr(10))
        with tempfile.NamedTemporaryFile("w", suffix=".mjs", delete=False, encoding="utf-8") as f:
            f.write(script)
        try:
            salida = subprocess.run(["node", f.name], capture_output=True, text=True, check=True, timeout=60)
        finally:
            Path(f.name).unlink(missing_ok=True)
        todas, primera = json.loads(salida.stdout)
        self.assertEqual(todas, [_en_singular(c) for c in claves])
        self.assertEqual(primera, [_en_singular(c, solo_la_primera=True) for c in claves])
