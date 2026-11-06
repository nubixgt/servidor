#!/bin/bash
# Run the AiService under uvicorn.
#
# LiteSpeed's "Setup Python App" (Passenger) on this host is WSGI-only and cannot
# host a FastAPI (ASGI) app -- native ASGI gives `__call__() missing 'send'`, and
# a2wsgi hangs the worker. So uvicorn runs standalone on 127.0.0.1:8600 and
# LiteSpeed reverse-proxies to it (docroot .htaccess: RewriteRule ... [P]).
# Kept alive by serve-guard.sh from cron. Local dev still uses ./run.sh.

APP_DIR="/home/visionwe/aiservice/app"
VENV_ACTIVATE="/home/visionwe/virtualenv/aiservice/app/3.12/bin/activate"
PORT="${AISERVICE_PORT:-8600}"

cd "$APP_DIR" || exit 1
# shellcheck disable=SC1090
source "$VENV_ACTIVATE"

# The API keys / BRIDGE_SERVICE_TOKEN were entered in cPanel's "Setup Python App"
# UI. Passenger would inject them into the process; uvicorn does not, so pull them
# out of the CloudLinux selector config and export them here.
# `eval "$(...)"` (not `< <(...)`): CloudLinux CageFS has no /dev/fd, so process
# substitution fails with "/dev/fd/63: No such file or directory".
eval "$(python3 -c '
import json, shlex, sys
try:
    data = json.load(open("/home/visionwe/.cl.selector/python-selector.json"))
    env = (data.get("aiservice/app", {}) or {}).get("env_vars") or {}
    for k, v in env.items():
        if isinstance(v, dict):
            v = v.get("value", "")
        print("export %s=%s" % (k, shlex.quote(str(v))))
except Exception as exc:
    sys.stderr.write("serve.sh: could not read env vars: %s\n" % exc)
')"

exec uvicorn main:app --host 127.0.0.1 --port "$PORT" --workers 1 \
    --no-access-log --log-level info
