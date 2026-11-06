"""¿Está todo conectado? Frontend → PHP → puente → PaginaLocal.

Esta app tiene cuatro piezas y tres saltos entre ellas, y ninguna prueba cruza
un salto: las de PaginaLocal no ven PHP, las de AiService no ven el frontend, y
PHP no tiene banco de pruebas. Un extremo suelto no da error al arrancar — da
un 404 el día que el atleta pulsa ese botón.

Ya pasó cuatro veces con campos de datos (`weekly_plan`, `season_plan_md`,
`season_phases`, `summary`: declarados en el esquema y nunca rellenados). Esto
comprueba lo mismo un nivel más arriba, con las RUTAS.

Qué mira:

  1. Cada llamada del frontend (`api.get/post/...`) tiene su `#[Route]` en PHP.
  2. Cada `#[Route]` de PHP lo llama alguien desde el frontend (una ruta que
     nadie usa no es un fallo, pero conviene verla).
  3. Cada llamada de PythonBridgeService tiene su `@app.<verbo>` en el puente.
  4. Cada endpoint `/bridge/*` del puente lo llama PHP.

Correr con:  python scripts/auditar_conexiones.py
"""

import pathlib
import re
import sys

RAIZ = pathlib.Path(__file__).resolve().parents[2]
FRONT = RAIZ / "Frontend" / "src"
CTRL = RAIZ / "Backend" / "src" / "Controllers"
BRIDGE_PHP = RAIZ / "Backend" / "src" / "Services" / "PythonBridgeService.php"
BRIDGE_PY = RAIZ / "AiService" / "main.py"

# Rutas de PHP que NO se llaman desde Frontend/src/services y está bien:
# el login de Google lo dispara el SDK, y dev-login sólo existe en local.
PHP_SIN_FRONT_OK = {"/auth/google", "/auth/dev-login", "/example"}


def _norm(ruta: str) -> str:
    """`/notebook/${id}`, `/notebook/{id}` y `/x?user_id=1` se comparan igual.

    El query string se quita ANTES de nada: PHP llama a
    `/bridge/generate-status?user_id=5` y el puente declara
    `/bridge/generate-status`. Sin quitarlo, tres conexiones sanas salian como
    rotas -- un auditor que da falsas alarmas se deja de mirar.
    """
    ruta = ruta.split("?")[0]
    ruta = re.sub(r"\$\{[^}]+\}", "{}", ruta)
    ruta = re.sub(r"\{[^}]+\}", "{}", ruta)
    return "/" + ruta.strip("/")


def llamadas_frontend() -> set[tuple[str, str]]:
    encontrado = set()
    if not FRONT.is_dir():
        return encontrado
    # Se barre TODO src/, no solo services/: las vistas de administracion
    # llaman a la API directamente y salian como "rutas PHP que nadie usa".
    patron = re.compile(r"api\.(get|post|put|patch|delete)\(\s*[`'\"]([^`'\"]+)[`'\"]")
    for f in list(FRONT.rglob("*.js")) + list(FRONT.rglob("*.vue")):
        for verbo, ruta in patron.findall(f.read_text(encoding="utf-8")):
            encontrado.add((verbo.upper(), _norm(ruta)))
    return encontrado


def rutas_php() -> set[tuple[str, str]]:
    encontrado = set()
    if not CTRL.is_dir():
        return encontrado
    patron = re.compile(r"#\[Route\(\s*'([^']+)'\s*,\s*'([A-Z]+)'")
    for f in CTRL.glob("*.php"):
        for ruta, verbo in patron.findall(f.read_text(encoding="utf-8")):
            encontrado.add((verbo, _norm(ruta)))
    return encontrado


def controladores_sin_registrar() -> list[str]:
    """Controladores con rutas que el router NUNCA llega a ver.

    ## El fallo que caza, y por que este script no lo cazaba

    `LegalController` tenia sus `#[Route]` bien puestos y este auditor lo daba
    por bueno -- porque lee los ATRIBUTOS, y el atributo estaba. Lo que faltaba
    era la linea `$router->registerController(...)` en `api/v1/index.php`, que
    se escribe A MANO. Resultado: `GET /legal` devolvia 404, la puerta legal
    salia con "No se pudieron cargar las condiciones" y **ningun atleta podia
    pasar de esa pantalla**. Se encontro levantando la app, no aqui.

    Comprobar que la ruta existe no es comprobar que esta enchufada.
    """
    indice = RAIZ / "Backend" / "api" / "v1" / "index.php"
    if not CTRL.is_dir() or not indice.is_file():
        return []
    registrados = set(re.findall(r"registerController\(\s*([A-Za-z_]\w*)::class",
                                 indice.read_text(encoding="utf-8")))
    patron = re.compile(r"#\[Route\(")
    sueltos = []
    for f in sorted(CTRL.glob("*.php")):
        if patron.search(f.read_text(encoding="utf-8")) and f.stem not in registrados:
            sueltos.append(f.stem)
    return sueltos


def llamadas_puente_desde_php() -> set[str]:
    if not BRIDGE_PHP.is_file():
        return set()
    texto = BRIDGE_PHP.read_text(encoding="utf-8")
    return {_norm(r) for r in re.findall(r"['\"](/bridge/[^'\"]+)['\"]", texto)}


def endpoints_puente() -> set[str]:
    if not BRIDGE_PY.is_file():
        return set()
    texto = BRIDGE_PY.read_text(encoding="utf-8")
    return {_norm(r) for r in re.findall(r'@app\.(?:get|post|put|patch|delete)\(\s*"([^"]+)"', texto)}


def _casa(llamada: tuple[str, str], ruta: tuple[str, str]) -> bool:
    """Si una llamada del frontend la sirve esta ruta de PHP.

    Un `{}` casa con CUALQUIER segmento, y por eso no basta comparar cadenas:
    PHP declara `/admin/users/{status}` y el frontend llama
    `/admin/users/approved`; el frontend arma `/admin/users/${id}/${accion}` y
    PHP declara `/admin/users/{id}/approve`. Comparando texto, las cuatro
    salían como rotas estando perfectamente conectadas — y un auditor que da
    falsas alarmas se deja de mirar.
    """
    if llamada[0] != ruta[0]:
        return False
    a, b = llamada[1].split("/"), ruta[1].split("/")
    if len(a) != len(b):
        return False
    return all(x == y or "{}" in (x, y) for x, y in zip(a, b, strict=True))


def main() -> int:
    front, php = llamadas_frontend(), rutas_php()
    php_llama, puente = llamadas_puente_desde_php(), endpoints_puente()

    huerfanas_front = sorted(l for l in front if not any(_casa(l, r) for r in php))
    php_sin_uso = sorted(
        r for r in php
        if r[1] not in PHP_SIN_FRONT_OK and not any(_casa(l, r) for l in front)
    )
    huerfanas_puente = sorted(php_llama - puente)
    puente_sin_uso = sorted(puente - php_llama)

    print(f"frontend -> PHP     : {len(front)} llamadas contra {len(php)} rutas")
    print(f"PHP -> puente       : {len(php_llama)} llamadas contra {len(puente)} endpoints")

    print(f"\nROTAS (frontend llama y PHP no sirve): {len(huerfanas_front)}")
    for v, r in huerfanas_front:
        print(f"   {v:6} {r}")

    print(f"\nROTAS (PHP llama y el puente no sirve): {len(huerfanas_puente)}")
    for r in huerfanas_puente:
        print(f"   {r}")

    print(f"\nsin usar (rutas PHP que nadie llama): {len(php_sin_uso)}")
    for v, r in php_sin_uso:
        print(f"   {v:6} {r}")

    print(f"\nsin usar (endpoints del puente que PHP no llama): {len(puente_sin_uso)}")
    for r in puente_sin_uso:
        print(f"   {r}")

    sueltos = controladores_sin_registrar()
    print(f"\nCONTROLADORES sin registrar en index.php: {len(sueltos)}")
    for c in sueltos:
        print(f"   {c}  <- sus rutas devuelven 404: falta $router->registerController({c}::class)")

    # Sólo las rotas son un fallo. Una ruta sin usar puede ser deliberada.
    # Un controlador sin registrar NUNCA lo es: son rutas muertas.
    return 1 if (huerfanas_front or huerfanas_puente or sueltos) else 0


if __name__ == "__main__":
    sys.exit(main())
