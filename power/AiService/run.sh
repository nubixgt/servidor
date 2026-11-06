#!/usr/bin/env bash
# Start the AiService bridge (dev). Reads config from ../.env
set -euo pipefail
cd "$(dirname "$0")"

PORT="${AISERVICE_PORT:-8600}"

if [ ! -x .venv/bin/uvicorn ]; then
  echo "No venv. Créalo con:" >&2
  echo "  uv venv --python 3.12 .venv && uv pip install --python .venv/bin/python -r requirements.txt langchain-google-genai" >&2
  exit 1
fi

exec .venv/bin/uvicorn main:app --host 127.0.0.1 --port "$PORT" "$@"
