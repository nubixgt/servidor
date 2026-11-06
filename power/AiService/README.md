# AiService — puente PHP ↔ PaginaLocal

Servicio FastAPI que **importa `PaginaLocal/` como librería** (sin modificar ni un
archivo) y expone sus agentes al Backend PHP. Los datos del atleta llegan desde
MySQL (vía el Backend), se materializan en `_runtime/` con el formato que
PaginaLocal espera, se corren los agentes, y el resultado vuelve al Backend para
guardarlo en MySQL. Nada se escribe dentro de `PaginaLocal/`.

## Endpoints (`X-Service-Token: <BRIDGE_SERVICE_TOKEN>` obligatorio salvo `/health`)

| Método | Ruta | Qué hace |
|---|---|---|
| GET  | `/health` | claves detectadas, `AI_MODE`, token configurado |
| POST | `/bridge/garmin-import` | `{garmin_email, garmin_password}` → campos para prellenar el perfil |
| POST | `/bridge/coach-chat` | chat del entrenador; devuelve `{reply, notebook_new, usage_metadata}` |
| POST | `/bridge/health-intake` | chat del "Doctor" (ficha de salud) |
| POST | `/bridge/charts` | series de Analytics (sin IA); `garmin_password` opcional refresca Garmin |
| POST | `/bridge/generate-season` · `/bridge/generate-week` | **tarea larga**: lanza `worker.py` desacoplado, devuelve `{started, job_id}` |
| GET  | `/bridge/generate-status` | progreso/resultado de la generación en curso (lee de disco) |
| POST | `/bridge/garmin-upload` | **tarea larga**: sube la semana al reloj Garmin |
| GET  | `/bridge/garmin-upload-status` | progreso/resultado de la subida |

Las 3 tareas largas corren en un **subproceso desacoplado** (`worker.py`) que sobrevive
a que el proceso web se recicle; el status se lee de `_runtime/{jobs,upload_jobs}/<id>/status.json`.

## Dev local

```bash
cd AiService
uv venv --python 3.12 .venv                                   # uv: https://astral.sh/uv
uv pip install --python .venv/bin/python -r requirements.txt
# ../.env (raíz del repo) debe tener OPENAI/GEMINI/ANTHROPIC keys,
# AI_MODE=standard, GARMIN_CREDENTIALS_KEY, BRIDGE_SERVICE_TOKEN
./run.sh          # http://127.0.0.1:8600   (./tunnel.sh solo si necesitas exponerlo)
```

## Despliegue en cPanel (producción)

`ai.powercoachai.pro`. **Passenger/LSAPI de este host es WSGI-only y no puede servir un
ASGI** (`__call__() missing 'send'`; `a2wsgi` cuelga el worker). Solución: **uvicorn
standalone en `127.0.0.1:8600` + LiteSpeed como reverse-proxy**.

- **App root**: `/home/visionwe/aiservice/app/` (= contenido de `AiService/`)
- **Librería**: `/home/visionwe/aiservice/PaginaLocal/` (hermano de `app/`)
- **Estado persistente**: `/home/visionwe/aiservice/app/_runtime/` — planes por usuario,
  `track_record.jsonl` (Analytics), `upload_ledger` (Garmin), `secure/profiles.json`
  (creds Garmin cifradas), jobs en vuelo, `uvicorn.log`. **El deploy NUNCA lo toca.**
- **venv**: `/home/visionwe/virtualenv/aiservice/app/3.12/` — la crea "Setup Python App".
  La Python App de cPanel se deja **creada pero DETENIDA**: solo se usa para conservar la
  venv y los 8 secretos, que cPanel guarda en `~/.cl.selector/python-selector.json`
  (`env_vars`). No se vuelve a tocar su UI.
- **Secretos** (`OPENAI_API_KEY`, `GEMINI_API_KEY`, `ANTHROPIC_API_KEY`,
  `GARMIN_CREDENTIALS_KEY`, `BRIDGE_SERVICE_TOKEN`, `AI_MODE=standard`, `LOG_LEVEL=INFO`,
  `PYTHONUNBUFFERED=1`): se metieron por la UI de "Setup Python App"; `serve.sh` los lee
  del `python-selector.json` y los exporta antes de lanzar uvicorn. **No** hay `.env` en el servidor.
- **`.htaccess`** de `/home/visionwe/ai.powercoachai.pro/`:
  `RewriteRule ^(.*)$ http://127.0.0.1:8600/$1 [P,L]`. `serve-guard.sh` lo re-escribe si
  cPanel restaura el bloque Passenger.
- **Arranque / keepalive**: `serve.sh` (activa venv, exporta secretos, `exec uvicorn`) lo
  mantiene vivo `serve-guard.sh` desde cron:
  ```
  */1 * * * * /bin/bash /home/visionwe/aiservice/app/serve-guard.sh >/dev/null 2>&1
  @reboot     /bin/bash /home/visionwe/aiservice/app/serve-guard.sh >/dev/null 2>&1
  ```

### Operación
- **Deploy**: `git push` a `main` → GitHub Actions rsyncea `AiService/` y `PaginaLocal/`.
  El siguiente tick del cron (≤1 min) no reinicia solo; para aplicar cambios de código:
  `pkill -f 'uvicorn main:app' ; ` y el guard lo relanza. O `touch _runtime/force-restart`
  (no implementado — de momento `pkill`).
- **Reinicio manual**: `pkill -f 'uvicorn main:app --host 127.0.0.1 --port 8600'`
  (el cron lo vuelve a levantar en ≤1 min), o `bash serve-guard.sh`.
- **Re-instalar deps** (tras cambiar `requirements.txt`):
  ```bash
  source /home/visionwe/virtualenv/aiservice/app/3.12/bin/activate
  cd /home/visionwe/aiservice/app && pip install -r requirements.txt
  pkill -f 'uvicorn main:app'
  ```
- **Logs**: `~/aiservice/app/_runtime/uvicorn.log` y
  `~/aiservice/app/_runtime/{jobs,upload_jobs}/<id>/worker.log` por generación.
- **Backend**: `~/m.nubix.gt/power/backend/.env` → `PYTHON_SERVICE_URL=https://ai.powercoachai.pro`,
  `PYTHON_SERVICE_TOKEN=<BRIDGE_SERVICE_TOKEN>`.

Pasos de alta inicial y detalle: ver `~/.claude/plans/resilient-soaring-tulip.md`.

## Cómo importa PaginaLocal sin tocarlo (`bridge/paginalocal.py`)

- `sys.path` incluye `PaginaLocal/`; `ENV_FILE` apunta a `../.env` (si no existe, se usan
  las env vars del proceso — el caso de cPanel).
- `web_app.server`, `services.ai.coach_chat`, `services.garmin.data_extractor` se importan tal cual.
- Monkeypatch en memoria: `pl_server._profile_storage_dir` / `STORAGE_DIR` / `DATA_DIR` /
  `SECURE_DATA_DIR` / `PROFILES_FILE` y `FilePlanStorage._get_user_dir` → `_runtime/…`.
