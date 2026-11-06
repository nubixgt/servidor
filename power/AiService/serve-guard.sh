#!/bin/bash
# Keepalive for the AiService uvicorn process. Install in cron:
#   */1 * * * * /bin/bash /home/visionwe/aiservice/app/serve-guard.sh >/dev/null 2>&1
#   @reboot     /bin/bash /home/visionwe/aiservice/app/serve-guard.sh >/dev/null 2>&1
#
# Every run:
#   1. re-asserts the reverse-proxy .htaccess (cPanel may restore the Passenger
#      block if the stopped Python App is touched);
#   2. restarts uvicorn if /health does not answer.

APP_DIR="/home/visionwe/aiservice/app"
LOG="$APP_DIR/_runtime/uvicorn.log"
HTACCESS="/home/visionwe/ai.powercoachai.pro/.htaccess"
PORT=8600
PROC_MATCH="uvicorn main:app --host 127.0.0.1 --port $PORT"

mkdir -p "$APP_DIR/_runtime"

# Only one guard at a time.
exec 9>"$APP_DIR/_runtime/.guard.lock"
flock -n 9 || exit 0

# 1. keep LiteSpeed pointed at uvicorn, not at the (stopped) Passenger app.
if ! grep -qF "127.0.0.1:$PORT" "$HTACCESS" 2>/dev/null; then
    printf 'RewriteEngine On\nRewriteRule ^(.*)$ http://127.0.0.1:%s/$1 [P,L]\n' "$PORT" > "$HTACCESS"
fi

# 2. keep uvicorn alive.
if curl -sf -m 10 "http://127.0.0.1:$PORT/health" >/dev/null 2>&1; then
    exit 0
fi

pkill -f "$PROC_MATCH" 2>/dev/null
sleep 2
cd "$APP_DIR" || exit 1
setsid nohup bash serve.sh >> "$LOG" 2>&1 &
