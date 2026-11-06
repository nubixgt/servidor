# AGENTS.md — Power Coach AI

Instrucciones permanentes para cualquier agente que trabaje en este repo (Antigravity, Claude Code u otro).
Todo el contexto vive en **`contexto/`**. Antes de tocar código, lee en este orden:

1. `contexto/01_COMO_TRABAJAMOS.md` — cómo se trabaja aquí: sesgos, causa raíz contra parche, verificación.
   **Obligatorio.** Es lo que más se ha pagado aprender.
2. `contexto/04_ESTADO_Y_PENDIENTES.md` — dónde está el proyecto hoy, qué falta y qué espera una decisión.
3. `contexto/02_ARQUITECTURA_Y_REGLAS.md` — la app entera y cada regla de producto con su porqué.
4. `contexto/03_CEREBRO.md` — lo de dentro de `PaginaLocal/` (esquemas, unidades, validador, modelos).
5. `contexto/planes/PLAN_BETA_3_0.md` — **el plan que manda ahora** (Beta 3.0, fases F0-F10). La historia de la
   Beta 2.x, con sus casillas, sigue en `contexto/planes/PLAN_DE_ACCION_BETA_2_0.md`.

`contexto/README.md` tiene el mapa completo de la carpeta.

## Qué es

**Power Coach AI**: un entrenador de triatlón y carrera que lee los datos reales del atleta en Garmin Connect,
los pasa por un pipeline multiagente (LangGraph; GPT, Claude y Gemini según el rol), escribe un macrociclo y
semanas estructuradas, y las sube al reloj del atleta como entrenos de verdad. Cuatro piezas:
`Frontend/` (Vue 3, :5173) → `Backend/` (PHP sin framework, :8080, dueño de MySQL) → `AiService/` (FastAPI,
:8600, el puente) → `PaginaLocal/` (el cerebro, importado directamente).

El hilo conductor: **un plan que parece escrito por un entrenador no es lo mismo que un plan seguro de
correr.** Casi todos los fallos caros han sido pasos con la forma correcta y las unidades, las zonas o el ritmo
equivocados. Se verifica contra los números reales de un atleta, no contra la validez del esquema.

## Reglas que no se negocian

- **Nada se sube a `main`.** Se trabaja en la rama `pruebas-locales` (push a esa rama sí) hasta que el
  responsable acuerde el merge, que se hace con él y en el orden de la Fase B.
- **Los costos de IA son SÓLO del administrador.** Nunca se le enseñan a un atleta (`#[Authorize(['admin'])]`,
  `store.isAdmin`).
- **Nada se borra en silencio** (retirar marca con fecha de baja y conserva el texto). **Recortar sin decirlo
  está prohibido**: todo bloque truncado lo anuncia.
- **Una aserción no se parchea para que pase en verde**: se vuelve a apuntar a la garantía que hay debajo.
- **Las migraciones (`Database/NNN_*.sql`) se corren A MANO**, también en producción. Todo código que use una
  columna nueva tiene que aguantar que la migración todavía no esté (`COLS_OPCIONALES`, `supportsTerms()`).
  Cada migración nueva lleva su comprobación en `Backend/scripts/verificar_migraciones.php`.
- **Nunca escribas contraseñas en campos**, ni subas o retires entrenos de relojes Garmin reales, ni corras
  scripts contra producción, sin permiso explícito del responsable para ESA acción.
- **No cambies las preguntas del coach** (`preguntas_del_coach.py`) ni la **puerta falsa del día de carrera**
  sin que el responsable lo pida.
- **No metas sesgos que nadie pidió**: ni reglas de población donde manda el historial del atleta, ni
  prevención de lesiones que el atleta no declaró, ni números en negrita que parezcan objetivos. Ver 01.
- **Una generación real cuesta dinero** (~0,20-0,30 USD). Antes de lanzar una, o una réplica de un nodo, dilo.
- El relanzamiento de la Beta 2.0 es **desde cero**: se borran todos los perfiles, con el orden de la Fase B
  (primero retirar de Garmin, después la base).

## Cómo se trabaja, en corto (el detalle en 01)

1. **Causa raíz, no parche.** Si el modelo hace mal algo, mira primero el prompt archivado real
   (`AiService/_runtime/storage/u<id>/reasoning/<ISO>/prompts/*.json`): casi siempre le faltaba un dato, el
   esquema no podía expresarlo o dos textos se contradecían. Una regla determinista que corrige la salida es
   una red, con fecha para quitarla, no un arreglo.
2. **Lo que el atleta PIDE manda: el deporte, el día y lo que pide de una sesión (cuántas, un mínimo, la
   larga del lunes); el total de la semana y la intensidad son nuestros.**
3. **Abstenerse antes que adivinar.** Un dato que no se sabe se dice que no se sabe.
4. **Verifica con datos reales**: el `worker.log` del job, el prompt archivado, la base, el navegador y, para
   cambios de prompt, una réplica del nodo contra el prompt real (`PaginaLocal/scripts/replay_node.py`).
5. **Una sola fuente de verdad.** Si una constante vive en Python y en JS, una prueba fija que valgan lo mismo.
6. **Cada fallo encontrado deja una prueba** con los números reales en el docstring.
7. **Informa con honestidad**: lo que se verificó, lo que no, y lo que queda para decidir.

## Comandos (Windows; los servidores nunca con `&`)

```bash
# Backend (en Backend/)
C:/xampp/php/php.exe -S localhost:8080 router.php
# Frontend (en Frontend/)
npm.cmd run dev
# AiService (en AiService/) — necesita MySQL de XAMPP arriba
python -m uvicorn main:app --host 127.0.0.1 --port 8600
# Pruebas del cerebro (unittest) y del puente (pytest)
cd PaginaLocal && python -m unittest discover -s tests
cd AiService && python -m pytest tests -q
# Análisis estático desde la raíz: se revisa el DELTA sobre la línea base (ruff 19, vulture 204)
python -m ruff check .
python -m vulture
# Frontend compila / PHP sin errores / migraciones aplicadas
cd Frontend && npm.cmd run build
C:/xampp/php/php.exe -l <archivo.php>
C:/xampp/php/php.exe Backend/scripts/verificar_migraciones.php
```

MySQL local: `C:\xampp\mysql\bin\mysql.exe -u root tricoach`. En local existe `POST /auth/dev-login` con
`{"slot": 1|2|3}` (sólo desde 127.0.0.1, no existe en `main`): 1 Miguel (admin), 2 José, 3 Jessie. El token va
a `localStorage` como `token` y el usuario como `authUser`.

**El log de una generación está en `AiService/_runtime/jobs/<job_id>/worker.log` y en ningún otro sitio**
(la generación corre en un proceso worker aparte; la consola del AiService no la ve).

**Un cambio en `AiService/main.py` o `AiService/bridge/` exige reiniciar el AiService** (uvicorn va sin
recarga automática); el cerebro de `PaginaLocal/` se relee en cada generación.

Estado verificado al 2026-10-23: 2.756 pruebas en PaginaLocal, 110 en AiService, ruff 19, frontend compila.
