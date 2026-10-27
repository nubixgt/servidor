#!/bin/sh
# Arranque de los contenedores: espera la base, aplica migraciones y levanta el servicio pedido.
set -e
echo "Esperando PostgreSQL…"
python - <<'PY'
import os, time, psycopg
for i in range(60):
    try:
        psycopg.connect(os.environ["DATABASE_URL"]).close(); break
    except Exception:
        time.sleep(2)
else:
    raise SystemExit("PostgreSQL no responde")
PY
case "$1" in
  api)
    python -m pam.cli migrar
    exec uvicorn pam.api.main:app --host 0.0.0.0 --port 8000 --workers "${API_WORKERS:-4}" --proxy-headers --forwarded-allow-ips="*" ;;
  worker)
    exec python -m pam.cli worker ;;
  *)
    exec python -m pam.cli "$@" ;;
esac
