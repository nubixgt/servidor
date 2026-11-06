# Despliegue en cPanel — Runbook

Guía operativa de **Power Coach AI** en producción (cPanel de StableHost, cuenta
`visionwe`). Léela antes de tocar infraestructura.

> **Cambio de casa (2026-11):** la app vive ahora en `servidor/power/` y se publica en **https://m.nubix.gt/power/**
> (workflow `servidor/.github/workflows/deploy-power.yml`, que reemplaza al `deploy.yml` de `Web-App-Tricoach`).
> `powercoachai.pro` pasó a ser solo la landing pública (repo `Web-App-Tricoach`). El AiService NO se movió:
> sigue en `~/aiservice/` tras `ai.powercoachai.pro`. Donde este documento diga `powercoachai.pro/backend`
> léase `m.nubix.gt/power/backend`. Lo manual de la mudanza: secretos `VITE_*` en el repo `servidor`, origen
> `https://m.nubix.gt` en Google OAuth, copiar `backend/.env` y mover el cron de semanas automáticas.

- **Frontend** (Vue build, `base /power/`) → `/home/visionwe/m.nubix.gt/power/`
- **Backend** (PHP API) → `/home/visionwe/m.nubix.gt/power/backend/`
- **AiService** (puente FastAPI a `PaginaLocal/`) → `https://ai.powercoachai.pro`
- **PaginaLocal** → importado como librería por el AiService. **Nunca se modifica.**

Todo se despliega con **GitHub Actions** al hacer `git push` a `main` del repo `servidor`
(`.github/workflows/deploy-power.yml`).

---

## 1. Qué es automático y qué es manual

### Automático en cada `git push` a `main`

| Paso | Acción |
|---|---|
| Build frontend | `npm ci && npm run build` con `VITE_API_BASE_URL` y `VITE_GOOGLE_CLIENT_ID` |
| Deploy frontend | rsync `power/Frontend/dist/` → `m.nubix.gt/power/` (excluye `/backend/`) |
| Deploy backend | rsync `power/Backend/` → `m.nubix.gt/power/backend/` (excluye `.env`) |
| Deploy AiService | rsync `AiService/` → `aiservice/app/` |
| Deploy PaginaLocal | rsync `PaginaLocal/` → `aiservice/PaginaLocal/` |
| Reinicio AiService | `pkill uvicorn` + `serve-guard.sh` (relanza con el código nuevo) |

### Manual — sólo en estos casos

| Situación | Qué hacer |
|---|---|
| Nueva dependencia en `AiService/requirements.txt` | SSH → activar venv → `pip install -r requirements.txt` → `pkill -f 'uvicorn main:app'` (ver §4) |
| Nueva dependencia PHP / composer | según corresponda en `Backend/` |
| Migración de base de datos | ejecutar el `.sql` numerado de `Database/` a mano (phpMyAdmin o `mysql`) |
| Semanas automáticas (una vez, desde la 2.1) | el cron de §4 «Semanas automáticas» |
| Cambiar una API key o el `BRIDGE_SERVICE_TOKEN` | ver §5 |
| Cambiar variables del frontend (`VITE_*`) | son *secrets* de GitHub Actions, no del servidor |
| Cambiar `Backend/.env` | editar a mano en el servidor (está excluido de los deploys) |

---

## 2. Arquitectura del AiService en este host (IMPORTANTE)

**El "Setup Python App" (Passenger/LSAPI) de este host NO puede servir el AiService.**
Motivo: su LSAPI sólo habla **WSGI**, y el AiService es **ASGI** (FastAPI).
Se probó `a2wsgi` (shim ASGI→WSGI) y **cuelga el worker** (timeout, incluso con un
ASGI mínimo). Además CageFS de CloudLinux no tiene `/dev/fd` (rompe la sustitución
de procesos en bash).

### Solución en producción

```
Navegador → m.nubix.gt/power (PHP)
                │  PYTHON_SERVICE_URL = https://ai.powercoachai.pro
                ▼
        LiteSpeed  (subdominio ai.powercoachai.pro)
                │  .htaccess:  RewriteRule ^(.*)$ http://127.0.0.1:8600/$1 [P,L]
                ▼
        uvicorn  127.0.0.1:8600           ← AiService/serve.sh
                │                            ← AiService/serve-guard.sh  (cron cada minuto)
                ▼
        PaginaLocal (librería)  →  Gemini / OpenAI / Anthropic
```

### Piezas en el servidor

| Ruta | Qué es |
|---|---|
| `/home/visionwe/aiservice/app/` | Código del AiService (= `AiService/` del repo). Deploy automático. |
| `/home/visionwe/aiservice/PaginaLocal/` | Copia de `PaginaLocal/`. Deploy automático. |
| `/home/visionwe/aiservice/app/_runtime/` | **Estado persistente. NUNCA se despliega ni se borra.** Planes por usuario, `track_record.jsonl` (Analytics), `upload_ledger` (Garmin), `secure/profiles.json` (creds Garmin cifradas con Fernet), jobs en vuelo, `uvicorn.log`. |
| `/home/visionwe/virtualenv/aiservice/app/3.12/` | venv (la creó "Setup Python App"). Python 3.12.14. |
| `/home/visionwe/.cl.selector/python-selector.json` | Donde CloudLinux guarda los 8 secretos del AiService (clave `env_vars`). `serve.sh` los lee de aquí. |
| `/home/visionwe/ai.powercoachai.pro/.htaccess` | El reverse-proxy a uvicorn. `serve-guard.sh` lo re-escribe si cPanel lo pisa. |
| `/home/visionwe/m.nubix.gt/power/backend/.env` | Config del backend PHP. Contiene `PYTHON_SERVICE_URL=https://ai.powercoachai.pro` y `PYTHON_SERVICE_TOKEN`. |
| Cron `* * * * *` | `/bin/bash /home/visionwe/aiservice/app/serve-guard.sh` — keepalive. |

### La "Python App" de cPanel: creada pero fuera del circuito

Se mantiene **sólo** para conservar la venv y los secretos. Su config (startup file,
entry point) es basura de las pruebas y **no importa** porque LiteSpeed va directo a
uvicorn.

> ⚠️ **NUNCA le des a GUARDAR / REINICIAR / DETENER / DESTRUIR** en
> cPanel → *Setup Python App* → `ai.powercoachai.pro`.
> Cualquiera de esos botones regenera `/home/visionwe/ai.powercoachai.pro/.htaccess`
> con el bloque de Passenger → el AiService da error hasta que el cron restaure el
> proxy (≤ 1 min). Si pasa sin querer: `bash /home/visionwe/aiservice/app/serve-guard.sh`.
>
> Si la **DESTRUYES**, se borran la venv y los secretos → hay que rehacer §7.

---

## 3. Los scripts (`AiService/`)

- **`serve.sh`** — activa la venv, exporta los secretos leyéndolos de
  `python-selector.json` con `eval "$(python3 -c ...)"` (no `< <(...)`: CageFS no
  tiene `/dev/fd`), y `exec uvicorn main:app --host 127.0.0.1 --port 8600`.
- **`serve-guard.sh`** — lo corre el cron cada minuto:
  1. si el `.htaccess` no apunta a `127.0.0.1:8600`, lo reescribe con el proxy;
  2. si `GET /health` no responde, `pkill` + relanza `serve.sh` con `setsid nohup`.
- **`worker.py`** — runner **desacoplado** para las tareas largas (generar plan de
  temporada / semana, subir a Garmin). Lo lanza `main.py` con
  `subprocess.Popen(start_new_session=True)`; escribe progreso a
  `_runtime/{jobs,upload_jobs}/<id>/status.json`. Sobrevive a que uvicorn reinicie.
- **`run.sh` / `tunnel.sh`** — sólo desarrollo local. Excluidos del deploy.

---

## 4. Operación diaria

### Reiniciar el AiService
```bash
pkill -f 'uvicorn main:app --host 127.0.0.1 --port 8600'
# el cron lo levanta en ≤ 1 min, o forzarlo ya:
bash /home/visionwe/aiservice/app/serve-guard.sh
```

### Ver logs
```bash
tail -f /home/visionwe/aiservice/app/_runtime/uvicorn.log
# por generación de plan / subida a Garmin:
ls -lt /home/visionwe/aiservice/app/_runtime/jobs/
tail -50 /home/visionwe/aiservice/app/_runtime/jobs/<id>/worker.log
```

### Re-instalar dependencias (tras cambiar `requirements.txt`)
```bash
source /home/visionwe/virtualenv/aiservice/app/3.12/bin/activate
cd /home/visionwe/aiservice/app
pip install -r requirements.txt
pkill -f 'uvicorn main:app --host 127.0.0.1 --port 8600'
```
Si `pip` muere por memoria: `pip install --no-cache-dir -r requirements.txt`.

### Semanas automáticas (cron cada hora) — Beta 2.1

«Generar mi semana automáticamente» (Database/027) no corre sola: la dispara un cron que
lanza `backend/scripts/semanas_automaticas.php` cada hora. Sin el cron, el interruptor se
enciende en la app y **nunca pasa nada**. Se configura UNA vez, después de desplegar la 2.1 y
de correr `Database/027_semana_automatica.sql`:

1. Por SSH, averigua qué PHP usa la cuenta en la línea de comandos y que el script arranca:
   ```bash
   which php                     # normalmente /usr/local/bin/php (ea-php de cPanel)
   php /home/visionwe/m.nubix.gt/power/backend/scripts/semanas_automaticas.php --estado
   #   sin salida = nadie lo tiene encendido; "falta correr Database/027" = falta la migración
   ```
2. En cPanel → **Cron Jobs** → «Add New Cron Job», una vez por hora en el minuto 5
   (`5 * * * *`), con este comando (cambia la ruta de `php` si `which php` dijo otra):
   ```bash
   /usr/local/bin/php /home/visionwe/m.nubix.gt/power/backend/scripts/semanas_automaticas.php >> /home/visionwe/logs/semanas_automaticas.log 2>&1
   ```
   (Crea antes la carpeta: `mkdir -p /home/visionwe/logs`.)
3. Para comprobarlo al día siguiente: `tail -50 /home/visionwe/logs/semanas_automaticas.log`.
   Cada vuelta escribe una línea por cosa que pasa ("lanzada la semana del…", "subida
   (7/7…)") y nada si no tocaba a nadie.

Qué hace cada vuelta: a quien tenga el interruptor encendido y sea la víspera de su semana
desde las 21:00 de SU hora, le genera la semana sin preguntas ni check-in, la guarda y la sube a
su reloj (reemplaza lo que había y retira lo que sobra). Se queda hasta 50 min esperando a que
terminen, con un candado para que dos vueltas no se pisen, y como mucho 3 intentos por semana.
Cada semana cuesta lo de una normal (~0,30 USD) y va al libro de gastos. Para apagarlo para
todos basta con quitar el cron; cada atleta lo apaga desde su pantalla de Plan.

### Capacidad de las semanas automáticas (evaluado el 2026-09-24)

No hacen falta más servidores: todo pasa por la MISMA cola que las generaciones a mano
(`MAX_CONCURRENT_GENERATIONS`, 3 por defecto; 2 si se decidió D3). Una semana automática tarda ~5-6
min (no espera preguntas), así que salen ~30 por hora con 3 huecos (~20 con 2). Si todos los atletas
tienen la semana en lunes y la misma zona, se encolan juntos el domingo a las 21:05: 30 atletas
terminan hacia las 22:05 y 100 hacia las 00:30, antes del lunes por la mañana. La cola avanza sola
(cada worker la drena al terminar) y el script espera a lo que está en cola hasta 6 h sin relanzarlo.
Lo que sí conviene vigilar las primeras semanas: los 429 de Garmin (muchos inicios de sesión desde la
misma IP en la misma hora); si aparecen en el log, el reintento de la hora siguiente los absorbe.

### Antes de fusionar la 2.1 en `main` (auditoría del 2026-09-24)

- `git merge-tree` da 4 conflictos, todos triviales: los tres expertos (`activity/metrics/physiology_
  expert_node.py`: quedarse con la línea de pruebas-locales, que añade `nota_de_receptores`) y
  `tests/test_semanas_del_macrociclo.py` (la de pruebas-locales contiene todas las de main y más).
- El commit de producción `0bb40c0` se conserva: la fusión deja idénticos a `main` la configuración,
  `database.php`, `.env.example`, `api.js`, `Login.vue` y el router, y no trae el `dev-login`.
- Sin dependencias nuevas ni variables de entorno nuevas. La puerta legal y `JWT_SECRET` ya son como en
  `main` (misma versión de condiciones: nadie tendrá que volver a aceptarlas).
- Migraciones a correr a mano: **025, 026 y 027**. El código aguanta que falten (probado escondiendo las
  tablas: el interruptor automático y el registro de fuerza se ocultan, nada da error).
- Modelos `gpt-6-luna` y `gpt-6-sol`: la clave de OpenAI de producción es la misma que la local (el
  responsable, 2026-09-24), y en local generaron macrociclos y semanas reales ese día. Nada que hacer.
- Los planes guardados con la Beta 2.0 abren con el código nuevo (15 semanas y 4 macrociclos locales
  validados; ningún archivo se borra en la fusión).

### Fusión de la Beta 3.0 en `main` (auditoría del 2026-10-23)

Lo que trae y cómo se comprobó, en `contexto/04_ESTADO_Y_PENDIENTES.md`. Lo que hay que saber para
desplegarla:

- **Un solo conflicto**, `Frontend/src/views/auth/Login.vue`: se queda el código de invitación y se quita el
  `dev-login` (el commit de producción `0bb40c0` se conserva; `router.php` y el `dev-login` no vuelven).
- **Sin dependencias ni variables de entorno nuevas.** El despliegue de GitHub Actions reinicia el AiService.
- **Las condiciones de uso suben de versión** (2026-09-15 → 2026-10-21): todos los atletas las vuelven a
  aceptar al abrir la app, y **no se puede generar sin aceptarlas**. Si alguien tiene la semana automática
  encendida y no ha abierto la app, el script ya no gasta sus intentos: le deja «Tu semana del … está
  esperando: acepta las nuevas condiciones…» y la genera en la hora siguiente a que las acepte (hasta el final
  de su primer día). Conviene avisarles antes del domingo.
- **Migraciones a mano, 028 a 037**, en orden. Ensayadas dos veces sobre una base en el estado de
  producción (hasta la 027) con los datos de tres atletas: sin errores, sólo añaden tablas y columnas y los
  planes, opiniones y avisos quedaron idénticos byte a byte. La 037 necesita la 036 (sin ella da error).

**Lo que tiene que hacer Miguel a mano, en este orden**, después de que GitHub Actions despliegue la fusión
(sin las migraciones el código nuevo funciona como antes: no hay muro, ni tope, ni «Ayuda»; y la cortesía sólo
se puede registrar con el código nuevo, por eso van después):

1. Respaldo de la base (phpMyAdmin → Exportar).
2. **Primero ver cuáles faltan**: nada en el repo confirma hasta dónde llega producción (se supuso la 027; que
   la 025-027 de la 2.1 se corrieran nunca se comprobó). El script, ya desplegado, revisa de la 001 a la 037:
   ```bash
   php /home/visionwe/m.nubix.gt/power/backend/scripts/verificar_migraciones.php
   ```
   Correr **sólo las que marque como faltantes**, una por una y en orden (phpMyAdmin → SQL), y volver a
   lanzar el script hasta que diga que están todas. Ensayado el 2026-10-23 corriendo la 001-037 dos veces sobre
   una base vacía: de la 015 a la 037 la segunda vez no da error; la 002, 007, 008, 010, 013 y 014 sí
   («Duplicate entry/column»), por eso no se corren a ciegas.
3. **Justo después de la 035 y la 036, la cortesía de cada atleta de la beta**: desde ese momento el muro de
   pago está activo, y los atletas de la beta (su Garmin se conectó antes de que hubiera semanas gratis) no
   pueden generar hasta tenerla. En la app, como administrador: **Usuarios → «Registrar pago» → «Cortesía de
   la beta (12 meses) · Q0, sin cobro»**, con una nota («Atleta de la beta»). Queda en Q0 en la contabilidad y
   el atleta recibe «Tienes acceso sin costo… hasta el …». Quiénes son:
   ```sql
   SELECT id, name, email FROM users WHERE role = 'user' AND status = 'approved' ORDER BY id;
   ```
   (Si uno no la tiene todavía, ve «No tienes semanas gratis activas» con la salida de volver a leer su Garmin,
   que le da 2 semanas de prueba.)
4. Una vez, para que la copia del expediente en MySQL empiece completa:
   ```bash
   php /home/visionwe/m.nubix.gt/power/backend/scripts/comparar_expediente.php --copiar
   ```
   La copia del registro de subidas (030) se escribe sola en la siguiente subida de cada atleta.
5. El cron horario de `semanas_automaticas.php` (arriba) sigue igual; ahora además borra los diagnósticos de
   «Ayuda» a los 90 días y avisa al administrador si un atleta pasa de 1 USD en un día.

### Comprobar que está vivo
```bash
curl -s https://ai.powercoachai.pro/health | python3 -m json.tool
#   ok:true · ai_mode:"standard" · providers todos true · token_configured:true

curl -s -o /dev/null -w '%{http_code}\n' -H 'X-Service-Token: mal' \
     https://ai.powercoachai.pro/bridge/generate-status        # → 401
```

---

## 5. Cambiar un secreto (API key, token…)

Los secretos NO están en ningún `.env` del servidor: viven en
`~/.cl.selector/python-selector.json` (los puso "Setup Python App" en su día).

Para cambiar uno **sin tocar la UI de la Python App** (que regeneraría el `.htaccess`):

```bash
python3 - <<'PY'
import json
p = "/home/visionwe/.cl.selector/python-selector.json"
d = json.load(open(p))
d["aiservice/app"]["env_vars"]["OPENAI_API_KEY"] = "sk-nuevo-valor"
json.dump(d, open(p, "w"), indent=1)
print("ok")
PY
pkill -f 'uvicorn main:app --host 127.0.0.1 --port 8600'
```

Si cambias `BRIDGE_SERVICE_TOKEN`, actualiza también
`/home/visionwe/m.nubix.gt/power/backend/.env` → `PYTHON_SERVICE_TOKEN` (debe
coincidir) y el `.env` de la raíz del repo en tu PC.

Secretos actuales: `OPENAI_API_KEY`, `GEMINI_API_KEY`, `ANTHROPIC_API_KEY`,
`GARMIN_CREDENTIALS_KEY`, `BRIDGE_SERVICE_TOKEN`, `AI_MODE=standard`,
`LOG_LEVEL=INFO`, `PYTHONUNBUFFERED=1`.

Y en `backend/.env` (no en el selector de Python): `JWT_SECRET` — **sin él el
backend se niega a firmar sesiones y nadie puede entrar** —, `PYTHON_SERVICE_TOKEN`
(igual a `BRIDGE_SERVICE_TOKEN`) y las credenciales de la base. Desde el
2026-09-15 el código no trae ni token del puente ni contraseña de la base por
defecto: el que estuvo escrito quedó en el historial de git y hay que rotarlo.

Opcionales, con un valor por defecto que vale para este host (no hace falta ponerlas):
`MAX_CONCURRENT_GENERATIONS` (3), `LLM_TIMEOUT_S` (480: segundos que una llamada a
un modelo puede tardar antes de cortarse) y `WORKER_TOPE_S` (2400: tope de una
generación entera; pasado, el worker marca el error y libera la cola). Si subes
`WORKER_TOPE_S` por encima de 2700, sube también el límite de espera de
`Frontend/src/views/admin/PlanView.vue` y `_MAX_JOB_TOTAL_S` en `AiService/main.py`.

> **`AI_MODE=standard` ya NO es "sólo Gemini".** Desde v8 es un tier mixto, elegido
> replicando cada nodo contra prompts reales: los tres expertos y la decisión semanal
> corren `gpt-5-mid`, el codificador de entrenos `claude-haiku`, y siguen en Gemini los
> resumidores, la síntesis, el chat y el planificador de temporada. La tabla que manda
> es `PaginaLocal/services/ai/ai_settings.py`; lo que corre de verdad se comprueba con
> `GET /health`.
>
> `AI_MODE=pro` dobla RAM y duración → riesgo en los 2 GB del host. **Quedarse en
> `standard`.**
>
> Ojo: `athlete_profiles.ai_mode` tiene default `'pro'` en la base de datos y **no lo
> usa nadie** — `AiService/main.py` lo pisa siempre con esta variable de entorno. El
> tier es una decisión del operador, no del atleta.

---

## 6. GitHub Actions — secrets necesarios

`SSH_PRIVATE_KEY`, `SSH_HOST`, `SSH_USER`, `SSH_PORT`, `VITE_GOOGLE_CLIENT_ID`,
`VITE_FEEDBACK_WHATSAPP` (el número del botón Opinar; sin él el botón de WhatsApp no aparece).
El workflow usa `easingthemes/ssh-deploy@v6.0.3` con `rsync -avzr --delete`.

**`--delete` + `EXCLUDE`:** `_runtime/` está gitignoreado → ausente del checkout →
**sin el `EXCLUDE`, `--delete` borraría todo el estado persistente.** Si tocas el
workflow, verifica que `EXCLUDE` del paso "Deploy AiService" siga conteniendo
`/_runtime/`.

---

## 7. Recuperación ante desastre (rehacer el AiService desde cero)

Si se pierde la venv / la Python App / el servidor:

1. **SSH:** `mkdir -p /home/visionwe/aiservice/app /home/visionwe/aiservice/PaginaLocal`
2. **Subdominio** `ai.powercoachai.pro` (cPanel → Dominios → Crear). Esperar DNS +
   AutoSSL. Verificar `curl -sI https://ai.powercoachai.pro`.
3. **`git push`** a `main` (o rsync manual la primera vez) para poblar el código.
4. **Setup Python App:** Python `3.12.14`, App root `aiservice/app`, URL
   `ai.powercoachai.pro` sin path. (Startup file / entry point: da igual lo que
   pongas, no se usa — pon `main.py` / `app`.)
5. **Environment variables** en esa UI: las 8 de §5. Guardar.
6. **Instalar deps (SSH):**
   ```bash
   source /home/visionwe/virtualenv/aiservice/app/3.12/bin/activate
   cd /home/visionwe/aiservice/app
   python -m pip install --upgrade pip
   pip install -r requirements.txt
   ```
7. **`.htaccess` del subdominio** `/home/visionwe/ai.powercoachai.pro/.htaccess`:
   ```
   RewriteEngine On
   RewriteRule ^(.*)$ http://127.0.0.1:8600/$1 [P,L]
   ```
8. **Cron** (cPanel → Cron Jobs, cada minuto):
   ```
   /bin/bash /home/visionwe/aiservice/app/serve-guard.sh >/dev/null 2>&1
   ```
9. **Arrancar:** `bash /home/visionwe/aiservice/app/serve-guard.sh`
10. **`Backend/.env`:** `PYTHON_SERVICE_URL=https://ai.powercoachai.pro` +
    `PYTHON_SERVICE_TOKEN=<BRIDGE_SERVICE_TOKEN>`
11. **Verificar** con los `curl` de §4.

Plan original y decisiones: `~/.claude/plans/resilient-soaring-tulip.md` (en la
máquina del dev original).

---

## 8. Problemas conocidos

| Síntoma | Causa / arreglo |
|---|---|
| `https://ai.powercoachai.pro` da 500 / 503 / página de Passenger | Alguien tocó la Python App en cPanel y regeneró el `.htaccess`. `bash serve-guard.sh` (o esperar 1 min al cron). |
| `/health` responde pero `providers` todos `false` | `serve.sh` no exportó los secretos. Revisar que `python-selector.json` tenga `aiservice/app.env_vars` con 8 claves. `pkill uvicorn` y reintentar. |
| Tareas largas (plan/Garmin) se quedan en `progress:5` | El `worker.py` no arrancó. `cat _runtime/jobs/<id>/worker.log`. Posible bloqueo de `subprocess` por CageFS. |
| Deploy borró `_runtime/` | `EXCLUDE` del workflow perdió `/_runtime/`. Restaurar de backup y arreglar el YAML. |
| El chat del Coach no responde desde el sitio | Backend no llega al AiService. Verificar `Backend/.env` (`PYTHON_SERVICE_URL`, `PYTHON_SERVICE_TOKEN`) y que uvicorn esté vivo. |
