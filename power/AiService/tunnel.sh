#!/usr/bin/env bash
# Exposes the local AiService (port 8600) to the internet with a free Cloudflare
# quick tunnel, so the cPanel backend can reach it while you test the AI features.
#
# The printed https://<random>.trycloudflare.com URL changes every run — paste it
# into cPanel's Backend/.env as PYTHON_SERVICE_URL each time.
set -euo pipefail

PORT="${AISERVICE_PORT:-8600}"
CF="$(command -v cloudflared || echo "$HOME/.local/bin/cloudflared")"

if [ ! -x "$CF" ]; then
  echo "cloudflared no encontrado. Instálalo:" >&2
  echo "  curl -L -o ~/.local/bin/cloudflared https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64 && chmod +x ~/.local/bin/cloudflared" >&2
  exit 1
fi

echo "Asegúrate de que ./run.sh ya está corriendo en el puerto $PORT."
echo "Copia la URL https://<...>.trycloudflare.com y ponla en cPanel: Backend/.env -> PYTHON_SERVICE_URL"
echo
exec "$CF" tunnel --url "http://localhost:${PORT}"
