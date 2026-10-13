#!/usr/bin/env bash
# Aplica en orden las migraciones pendientes de Database/migrations/.
# Uso:  DB_HOST=127.0.0.1 DB_PORT=3306 DB_USER=root DB_PASS=... DB_NAME=deportes ./migrate.sh
# Haz un respaldo antes:  mysqldump -u USER -p DB_NAME > respaldo.sql
set -euo pipefail
cd "$(dirname "$0")/migrations"
M=(mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USER:-root}" "-p${DB_PASS:-}" "${DB_NAME:-deportes}")
"${M[@]}" -e "CREATE TABLE IF NOT EXISTS schema_migrations (archivo VARCHAR(100) PRIMARY KEY, aplicada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP)"
for f in [0-9]*.sql; do
  if [ -n "$("${M[@]}" -N -e "SELECT 1 FROM schema_migrations WHERE archivo='$f'")" ]; then echo "= $f (ya aplicada)"; continue; fi
  echo "+ aplicando $f"; "${M[@]}" < "$f"
  "${M[@]}" -e "INSERT INTO schema_migrations (archivo) VALUES ('$f')"
done
echo "Migraciones al día."
