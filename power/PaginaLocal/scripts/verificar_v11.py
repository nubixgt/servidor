"""La lista de lanzamiento de la v1.1, comprobada contra el codigo.

No sustituye ni a la suite ni a `verificar_generacion.py`. Cada uno mira una
cosa distinta y las tres hacen falta:

    suite                    -> las funciones puras se comportan
    verificar_v11.py         -> los ENTREGABLES del plan estan en el codigo
    verificar_generacion.py  -> una generacion REAL cumple las garantias

Este script existe porque un plan de 38 puntos repartido en cuatro carpetas se
da por terminado de memoria, y de memoria se dan por hechas cosas que no lo
estan. Cada comprobacion es falsable: apunta a un fichero y a una cadena que
tiene (o que ya NO puede tener).

    python PaginaLocal/scripts/verificar_v11.py

Sale con codigo 1 si algo falta.
"""

from __future__ import annotations

from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]

fallos: list[str] = []
_cache: dict[Path, str] = {}


def leer(rel: str) -> str:
    ruta = RAIZ / rel
    if ruta not in _cache:
        _cache[ruta] = ruta.read_text(encoding="utf-8", errors="ignore") if ruta.is_file() else ""
    return _cache[ruta]


def check(punto: str, que: str, ok: bool, detalle: str = "") -> None:
    marca = "OK  " if ok else "FALTA"
    print(f"  {marca} {punto:5} {que}" + (f"  -- {detalle}" if detalle and not ok else ""))
    if not ok:
        fallos.append(f"{punto} {que}")


def tiene(punto: str, que: str, rel: str, aguja: str) -> None:
    check(punto, que, aguja in leer(rel), f"no encontre {aguja!r} en {rel}")


def no_tiene(punto: str, que: str, rel: str, aguja: str) -> None:
    check(punto, que, aguja not in leer(rel), f"todavia esta {aguja!r} en {rel}")


def existe(punto: str, que: str, rel: str) -> None:
    check(punto, que, (RAIZ / rel).exists(), f"no existe {rel}")


PROFILE = "Frontend/src/views/admin/ProfileView.vue"
PLANVIEW = "Frontend/src/views/admin/PlanView.vue"
WEEKLY = "PaginaLocal/services/ai/langgraph/nodes/weekly_planner_node.py"
SERVER = "PaginaLocal/web_app/server.py"
OUTPUTS = "PaginaLocal/services/ai/langgraph/schemas/plan_outputs.py"
CHECKIN = "Frontend/src/components/modals/CheckinModal.vue"
DASHBOARD = "Frontend/src/views/admin/DashboardView.vue"


def main() -> int:
    print("\n=== BLOQUE 1 — lo que se arreglo sin tocar a nadie ===")
    # La cadena sigue en el fichero, dentro del comentario que explica por que
    # se borro. Lo que no puede haber es la TABLA: se busca su titulo.
    no_tiene("1.1", "las zonas de vatios inventadas ya no se pintan", PROFILE,
             ">Zonas de Entrenamiento Calculadas<")
    tiene("1.2", "hay prueba de que un paso de FC sube la ZONA",
          "PaginaLocal/tests/test_workout_builder.py", "zoneNumber")
    tiene("1.3", "la meta sale del catalogo, no de texto libre", PROFILE, "gruposDeCarrera")
    no_tiene("1.4", "la contradiccion FREEFORM de fuerza ya no esta", WEEKLY, "FREEFORM")
    tiene("1.5", "los nombres consagrados estan protegidos",
          "PaginaLocal/services/ai/langgraph/nodes/prompt_components.py", "dead bug")
    tiene("1.6", "un paso lleva UNA zona o ninguna", WEEKLY, "UNA zona")
    no_tiene("1.7", "'Rechazar / Ajustar' ya no es un boton", PLANVIEW, '>Rechazar')
    # `<details>` nativo, no un ref: se pliega solo y funciona sin JS.
    tiene("1.8", "el macrociclo va plegado y DEBAJO", PLANVIEW,
          '<details v-if="hasSeason"')
    no_tiene("1.9", "nadie es 'Atleta Elite' por defecto",
             "Frontend/src/data/mockData.js", "roleTitle: 'Atleta")
    tiene("1.10", "cada escala del check-in dice sus polos", CHECKIN, "item.uno")
    tiene("1.11", "las tablas anchas tienen su propio scroll", PLANVIEW, "overflow-x-auto")
    tiene("1.12", "la altimetria se marca opcional", PROFILE, "marcaOpcional")
    no_tiene("1.13", "DEPLOYMENT ya no dice 'standard = Gemini'",
             "contexto/operacion/DEPLOYMENT.md", "standard` = Gemini (barato)")
    tiene("1.13", "el docstring de Garmin ya no promete la animacion",
          "PaginaLocal/services/garmin/garmin_vocabulary.py", "Nunca se verific")
    tiene("1.14", "el planning_context del atleta se concatena, no se pisa",
          SERVER, "custom_planning_context +=")

    print("\n=== 2.A — el atleta manda sobre su semana ===")
    existe("2.A", "la migracion de la rejilla", "Database/013_add_sport_days.sql")
    tiene("2.A", "la rejilla tiene TRES estados", SERVER, "ESTADO_POSIBLE")
    tiene("2.A", "el prompt pide, no permite", SERVER, "[LO QUE EL ATLETA PIDE PARA SU SEMANA]")
    tiene("2.A", "distingue FIJO de POSIBLE", SERVER, "FIJO (lo ha pedido)")
    # OJO: la frase SIGUE en server.py y tiene que seguir. Es la tabla de
    # respaldo de los perfiles que aun no han contestado la rejilla, y quitarla
    # les cambiaria el comportamiento sin que ellos tocaran nada. Lo que se
    # comprueba es que el camino de la rejilla NO la emite -- y eso solo se
    # puede ver en un prompt real: lo hace verificar_generacion.py.
    tiene("2.A", "la tabla de franjas sigue viva para perfiles viejos", SERVER,
          "UN BLOQUE ES UN PERMISO")
    tiene("2.A", "y la rejilla usa OTRA tabla", SERVER, "render_sport_days_table")
    tiene("2.A", "el validador exige los `fijo`",
          "PaginaLocal/services/ai/langgraph/validation/plan_validator.py",
          "sport_requested_missing")
    tiene("2.A", "la rejilla MANDA sobre las franjas viejas", SERVER, "LA REJILLA MANDA CUANDO EXISTE")

    print("\n=== 2.B — brick se lee, no se escribe ===")
    check("2.B", "`DayIntent` ya no admite brick",
          '"walk", "rest"]' in leer(OUTPUTS) and leer(OUTPUTS).count('"brick", "rest"]') == 1)
    tiene("2.B", "`WorkoutDay` SI lo sigue admitiendo", OUTPUTS, '"walk", "brick", "rest"]')
    existe("2.B", "la asimetria esta fijada por una prueba",
           "PaginaLocal/tests/test_brick_se_lee_no_se_escribe.py")

    print("\n=== 2.C — las lesiones dejan de ser el centro ===")
    existe("2.C", "la migracion de estado de lesion", "Database/014_injuries_status_and_pool.sql")
    tiene("2.C", "una lesion resuelta NO es una restriccion",
          "PaginaLocal/services/ai/health_context.py", "resolved_injuries")
    # La v1.1 quito el BOTON; la v1.2 quito el agente entero (Database/017),
    # asi que ya no hay ni comentario que buscar: lo que se comprueba es que
    # ningun rastro del informe llegue al perfil.
    no_tiene("2.C", "el asistente de salud no existe en la UI", PROFILE, "doctorReport")
    no_tiene("2.C", "ni el tamizaje de aptitud fisica", PROFILE, "par_q")
    # Desde la v1.2 «Avanzado» ya no son cinco pestañas dentro de un segundo
    # nivel de navegacion: son cinco secciones apiladas en UNA pantalla.
    tiene("2.C", "Salud vive en la pantalla avanzada", PROFILE,
          "activeTab === 'avanzado'")

    print("\n=== 2.D — el alta deja de hacer ruido ===")
    tiene("2.D", "el alta se parte en basico / avanzado", PROFILE,
          "@click=\"activeTab = 'avanzado'\"")
    tiene("2.D", "se enseña QUE falta, por nombre", PROFILE, "camposQueFaltan")
    tiene("2.D", "el backend exige el 80% para generar",
          "Backend/src/Services/PlanService.php", "MINIMO_PERFIL = 80")
    tiene("2.D", "la longitud de piscina se pregunta", PROFILE, "pool_length_m")
    tiene("2.D", "el volumen se MIDE de Garmin, sin IA",
          "PaginaLocal/services/garmin/data_extractor.py", "get_weekly_training_volume")
    tiene("2.D", "las marcas son una lista abierta", "Frontend/src/utils/profile.js", "leerMarcas")

    print("\n=== 2.E — el telefono ===")
    tiene("2.E", "el dia se pliega y despliega", PLANVIEW, "diasAbiertos")
    tiene("2.E", "dos sesiones el mismo dia se marcan", PLANVIEW, "sesion")
    # Viven en el DASHBOARD, no en la vista del plan: es la pantalla que el
    # atleta abre primero. Y la eleccion se recuerda en localStorage, para no
    # obligarle a plegarlas cada vez.
    tiene("2.E", "las metricas avanzadas se pliegan", DASHBOARD, "alternarAvanzadas")
    tiene("2.E", "y recuerda si las quiere abiertas", DASHBOARD, "CLAVE_AVANZADAS")

    print("\n=== 2.F — la puerta legal ===")
    existe("2.F", "la migracion de la aceptacion", "Database/015_terms_acceptance.sql")
    existe("2.F", "el texto y su version viven juntos", "Backend/src/Support/Legal.php")
    tiene("2.F", "dice que los datos van a OpenAI, Anthropic y Google",
          "Backend/src/Support/Legal.php", "Anthropic")
    tiene("2.F", "la puerta no se enseña sin la migracion",
          "Frontend/src/components/layout/LegalGate.vue", "termsVersionRequired")
    tiene("2.F", "el backend tolera que falte la columna",
          "Backend/src/Repositories/UserRepository.php", "supportsTerms")
    tiene("2.F", "el controlador esta REGISTRADO en el router",
          "Backend/api/v1/index.php", "LegalController")

    print("\n=== Catalogo de ejercicios ===")
    existe("cat", "la fuente unica de los 126 movimientos",
           "PaginaLocal/services/ai/exercise_catalog.py")
    existe("cat", "el JSON que lee el frontend", "Frontend/public/ejercicios/catalogo.json")
    check("cat", "las 126 imagenes estan en disco",
          len(list((RAIZ / "Frontend/public/ejercicios").glob("*.jpg"))) >= 126,
          f"solo hay {len(list((RAIZ / 'Frontend/public/ejercicios').glob('*.jpg')))}")

    print("\n=== Costos ===")
    tiene("cost", "el gasto se anota aunque la corrida falle", SERVER, "anotar_gasto(profile_dict")
    tiene("cost", "los costos son solo del administrador",
          "Backend/src/Controllers/UsageController.php", "Authorize(['admin'])")

    print()
    if fallos:
        print(f"{len(fallos)} punto(s) SIN cumplir:")
        for f in fallos:
            print(f"   - {f}")
        return 1
    print("Los 38 entregables del plan estan en el codigo.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
