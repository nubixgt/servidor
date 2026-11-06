"""Un campo declarado en el puente que PHP nunca rellena. Van CINCO.

## El patron

El esquema del puente declara un campo. PHP no lo envia. Pydantic le pone su
valor por defecto, nada falla, y el consumidor tira de lo que hubiera en el
espejo de disco -- o de nada. Ninguna prueba lo ve, porque las de PaginaLocal
no conocen PHP y PHP no tiene banco de pruebas.

Las cinco veces:

  1. `weekly_plan` en el chat del coach     -> respondia sobre una semana vieja
  2. `season_plan_md` en el chat del coach  -> idem con el macrociclo
  3. `season_phases` en el chat del coach   -> idem con las fases
  4. `summary` en el chat y en generacion   -> zonas y adherencia de un resto
  5. `season_plan_md` + `season_phases` en
     GENERAR TEMPORADA (2026-09-03)         -> ver abajo

El quinto es el peor porque ademas BORRA. El worker materializa el espejo antes
de generar, y `write_season_plan(dir, None, None)` elimina los dos ficheros: el
espejo refleja lo que MySQL manda y MySQL no mandaba nada. Cuando el servidor
iba a leer el plan viejo para el bloque "Macrociclo ANTERIOR", ya no existia.
Resultado: la funcion entera -- que el macrociclo nuevo conozca al anterior, su
adherencia y lo que recomendo -- estaba muerta en la app multiusuario, aunque
funcionara en la de un solo usuario, donde el fichero ya esta en disco.

## Que hace esta prueba

Lee el ESQUEMA de Python y el PHP que lo llama, y compara. Es la unica prueba
del repo que cruza los dos lenguajes, y existe porque el hueco esta justo ahi:
en el salto que ninguna de las dos suites mira.

No comprueba que el valor sea correcto -- eso no se puede desde aqui. Comprueba
que PHP MENCIONE cada campo que el esquema declara. Es un liston bajo, y aun
asi lo habria cazado las cinco veces.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from bridge.schemas import (  # noqa: E402
    CoachChatRequest,
    GenerateSeasonRequest,
    GenerateWeekRequest,
    StrengthRoutineRequest,
)

RAIZ = Path(__file__).resolve().parents[2]
PLAN_SERVICE = RAIZ / "Backend" / "src" / "Services" / "PlanService.php"
COACH_CTRL = RAIZ / "Backend" / "src" / "Controllers" / "CoachController.php"
PROFILE_CTRL = RAIZ / "Backend" / "src" / "Controllers" / "ProfileController.php"

# Campos que PHP NO manda a proposito, con su motivo. Cualquier otro que
# aparezca aqui sin explicacion es el fallo que esta prueba busca.
NO_LOS_MANDA_PHP = {
    # Los pone el propio puente o el worker, no el backend.
    "user_id", "profile", "garmin_password",
    # Opcionales de verdad: sólo viajan cuando el atleta los usa.
    "adjustment", "redo", "force", "start_date",
}


def _campos(modelo) -> set[str]:
    return set(modelo.model_fields) - NO_LOS_MANDA_PHP


def _php(fichero: Path, funcion: str) -> str:
    """El cuerpo de una funcion de PHP, hasta el siguiente `public function`."""
    texto = fichero.read_text(encoding="utf-8")
    ini = texto.index(f"function {funcion}")
    resto = texto[ini:]
    fin = resto.find("public function", 10)
    return resto[:fin] if fin > 0 else resto


class PhpRellenaTodoLoQueElPuenteDeclara(unittest.TestCase):
    def setUp(self):
        for f in (PLAN_SERVICE, COACH_CTRL):
            if not f.is_file():
                self.skipTest("Backend/ no esta presente en este arbol")

    def _comprobar(self, modelo, cuerpo: str, quien: str):
        faltan = sorted(c for c in _campos(modelo) if f"'{c}'" not in cuerpo)
        self.assertEqual(
            faltan, [],
            f"{quien} no envia estos campos que el puente declara: {faltan}. "
            "Pydantic les pondra su valor por defecto y nada fallara -- ese es "
            "exactamente el fallo.",
        )

    def test_EL_FALLO_generar_temporada_manda_el_macrociclo_anterior(self):
        cuerpo = _php(PLAN_SERVICE, "startSeason")
        self.assertIn("'season_plan_md'", cuerpo)
        self.assertIn("'season_phases'", cuerpo)

    def test_generar_temporada_rellena_el_esquema_entero(self):
        self._comprobar(GenerateSeasonRequest, _php(PLAN_SERVICE, "startSeason"),
                        "PlanService::startSeason")

    def test_EL_FALLO_la_semana_manda_lo_que_levanto_en_el_gimnasio(self):
        """Beta 2.1: sin `strength_logs`, la carga de su programa no puede progresar
        contra nada -- el campo estaría declarado y nadie lo rellenaría, que es
        exactamente el fallo que esta prueba persigue."""
        self.assertIn("'strength_logs'", _php(PLAN_SERVICE, "startWeek"))

    def test_generar_semana_rellena_el_esquema_entero(self):
        self._comprobar(GenerateWeekRequest, _php(PLAN_SERVICE, "startWeek"),
                        "PlanService::startWeek")

    def test_el_chat_del_coach_rellena_el_esquema_entero(self):
        cuerpo = _php(COACH_CTRL, "chat")
        # `message` y `chat_history` vienen del cuerpo de la peticion del
        # navegador, no de una consulta a MySQL: se nombran distinto en PHP.
        modelo = {c for c in _campos(CoachChatRequest) if c not in ("message", "chat_history")}
        faltan = sorted(c for c in modelo if f"'{c}'" not in cuerpo)
        self.assertEqual(faltan, [], f"CoachController::chat no envia: {faltan}")

    def test_ordenar_la_rutina_de_fuerza_rellena_el_esquema_entero(self):
        """Beta 2.1, S4: sin `grouping` ni `rest_between_rounds_s`, la rutina de Fernando
        se leería sin saber que cada línea es una superserie ni cuánto descansa."""
        cuerpo = _php(PROFILE_CTRL, "strengthRoutine")
        self._comprobar(StrengthRoutineRequest, cuerpo, "ProfileController::strengthRoutine")
        self.assertIn("'profile'", cuerpo)

    def test_el_macrociclo_anterior_sale_de_la_MISMA_columna_que_en_la_semana(self):
        """Si la temporada leyera el plan de otro sitio que la semana, las dos
        contarian macrociclos distintos y nadie lo notaria."""
        temporada = _php(PLAN_SERVICE, "startSeason")
        semana = _php(PLAN_SERVICE, "startWeek")
        for campo in ("seasonPlanMd", "seasonPhases"):
            with self.subTest(campo=campo):
                self.assertIn(campo, temporada)
                self.assertIn(campo, semana)


if __name__ == "__main__":
    unittest.main()
