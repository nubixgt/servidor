"""Un triatleta sin distancia declarada no recibe una inventada.

## De dónde salió

De una generación REAL contra la cuenta del atleta 2 el 2026-09-10. Su perfil
tiene `sports_preference = 'triathlon'` y `triathlon_distance = NULL` en MySQL,
porque **ninguna vista del frontend escribe ese campo**. El prompt archivado
(`reasoning/2026-09-10T08-18-06/prompts/007__season_planner__*.json`) decía,
palabra por palabra:

    CRITICAL: The athlete is training for a None Triathlon.
    Target triathlon distance: None. Ensure swim/bike/run volumes match the
    demands of this race format.

La causa es de una sutileza que este repo ya conoce: `dict.get(clave, defecto)`
**sólo usa el defecto si la clave FALTA**. Aquí la clave estaba, con valor
`None`, así que el defecto `"Olympic"` nunca llegó a correr y el `None` se
imprimió tal cual dentro de una línea marcada CRITICAL.

## Por qué no se arregla poniendo "Olympic"

Porque sería el mismo fallo con mejor cara. Las proporciones de nado, bici y
carrera de un supersprint y de un Ironman no se parecen en nada, y elegir por
el atleta le da al planificador una certeza que nadie le dio. La regla de la
casa es abstenerse antes que acertar por suerte: se dice que NO está declarada
y de dónde deducirla, que es el calendario de competiciones — ese sí es suyo.

Correr con:  python -m unittest discover -s tests -v
"""

import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))

from web_app.server import build_custom_planning_context  # noqa: E402

BASE = {
    "name": "T",
    "sports_preference": "triathlon",
    "available_days": ["Martes", "Domingo"],
    "available_hours": 8,
}


class DistanciaDeTriatlon(unittest.TestCase):
    def _contexto(self, **extra):
        return build_custom_planning_context({**BASE, **extra})

    def test_la_clave_presente_con_none_no_imprime_none(self):
        """El caso exacto del atleta 2: la clave existe y vale None."""
        texto = self._contexto(triathlon_distance=None)
        self.assertNotIn("None Triathlon", texto)
        self.assertNotIn("Target triathlon distance: None", texto)

    def test_sin_distancia_se_dice_que_no_esta_declarada(self):
        for valor in (None, "", "   "):
            with self.subTest(valor=repr(valor)):
                texto = self._contexto(triathlon_distance=valor)
                self.assertIn("not set in the profile", texto)
                # Ya no se le pide suponer un formato y "confirmarlo": sin triatlón en el
                # calendario, el macrociclo del atleta 2 supuso "un triatlón sprint" y el
                # chat le propuso bajar la natación que había pedido (2026-09-24). La
                # garantía de debajo es la misma: no se rellena una distancia en silencio.
                self.assertIn("do NOT assume a triathlon format", texto)
                self.assertNotIn("infer it from the competition calendar", texto)

    def test_pero_si_ya_lo_contesto_no_se_le_vuelve_a_preguntar(self):
        """2026-09-11: contestó "olímpico" al coach, quedó en su cuaderno, y
        la semana siguiente le volvía a pedir que lo confirmara."""
        texto = self._contexto(triathlon_distance=None)
        self.assertIn("notebook or their answers to your questions name it, that is the answer", texto)

    def test_sin_distancia_no_se_inventa_olympic(self):
        """El defecto viejo. Inventarlo es el mismo fallo con mejor cara."""
        texto = self._contexto(triathlon_distance=None)
        self.assertNotIn("Olympic Triathlon", texto)

    def test_la_clave_ausente_tampoco_inventa(self):
        """Un perfil viejo sin el campo se comporta igual que uno con None."""
        texto = self._contexto()
        self.assertIn("not set in the profile", texto)

    def test_target_distance_de_carrera_no_se_cuela_como_triatlon(self):
        """El atleta 2 tiene `target_distance = '10k'`. Un '10k Triathlon' no
        existe, y el defecto viejo (`target_distance or "Olympic"`) lo habria
        escrito en cuanto la clave faltara."""
        texto = self._contexto(target_distance="10k")
        self.assertNotIn("10k Triathlon", texto)

    def test_con_distancia_escrita_a_mano_se_respeta_tal_cual(self):
        """Lo que el atleta escribio no se traduce ni se corrige."""
        texto = self._contexto(triathlon_distance="Ironman 70.3")
        self.assertIn("Ironman 70.3 Triathlon", texto)
        self.assertIn("Target triathlon distance: Ironman 70.3", texto)
        self.assertNotIn("NOT DECLARED", texto)

    def test_una_clave_del_catalogo_llega_como_TRAMOS(self):
        """`tri_olympic` no le dice nada a un modelo; los tramos si, y son lo
        que decide las proporciones de nado, bici y carrera."""
        texto = self._contexto(triathlon_distance="tri_olympic")
        self.assertNotIn("tri_olympic", texto)
        self.assertIn("1.5 km de natación", texto)
        self.assertIn("40 km de bici", texto)
        self.assertIn("10 km de carrera", texto)

    def test_el_vocabulario_de_soporte_sigue_completo_en_los_dos_caminos(self):
        """La rama nueva no puede perder lo que la v7 arreglo: la lista de
        disciplinas de soporte no es solo 'strength'."""
        for valor in (None, "Olympic"):
            with self.subTest(valor=valor):
                texto = self._contexto(triathlon_distance=valor)
                for disciplina in ("plyometrics", "prehab", "rehab", "mobility", "technique"):
                    self.assertIn(disciplina, texto)


if __name__ == "__main__":
    unittest.main()


class UnaCarreraCorridaNoEsUnaProxima(unittest.TestCase):
    """Semana del atleta 2 del 2026-10-05: su 5K del 2026-09-20 salía bajo [UPCOMING COMPETITIONS]
    y "The training plan MUST periodize correctly towards these" (auditoría del 2026-09-24)."""

    def test_la_corrida_sale_aparte(self):
        from datetime import date, timedelta
        pasada = (date.today() - timedelta(days=15)).isoformat()
        futura = (date.today() + timedelta(days=200)).isoformat()
        texto = build_custom_planning_context({**BASE, "competitions": [
            {"name": "5k Monterrico", "date": pasada, "priority": "B"},
            {"name": "10K", "date": futura, "priority": "A"}]})
        proximas = texto[texto.index("[UPCOMING COMPETITIONS]"):]
        self.assertNotIn("5k Monterrico", proximas.split("MUST periodize")[0])
        self.assertIn("[CARRERAS YA CORRIDAS", texto)
        self.assertIn(f"- 5k Monterrico | {pasada}", texto)
        self.assertIn(f"- 10K | {futura}", proximas)

    def test_la_de_antes_de_la_semana_no_es_ya_corrida(self):
        """José, 2026-10-22: la semana del lunes 26 generada el jueves 22 recibió el gravel del sábado 24 bajo
        «CARRERAS YA CORRIDAS» y además como próxima carrera; el coach preguntó si ya la había corrido."""
        from datetime import date, timedelta
        sabado = (date.today() + timedelta(days=2)).isoformat()
        texto = build_custom_planning_context({**BASE, "competitions": [
            {"name": "50 K Gravel Maniac", "date": sabado, "priority": "B"}]},
            referencia=date.today() + timedelta(days=4))
        self.assertNotIn("[CARRERAS YA CORRIDAS", texto)
        self.assertIn("[CARRERAS ANTES DE ESTA SEMANA — todavía por correr", texto)
        self.assertIn(f"- 50 K Gravel Maniac | {sabado}", texto)


if __name__ == "__main__":
    unittest.main()
