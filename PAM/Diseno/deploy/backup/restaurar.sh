#!/bin/sh
# Restaura un respaldo sobre la base PAM (¡reemplaza los datos actuales!).
#   docker compose run --rm -v "$PWD/respaldos:/respaldos" respaldo sh /restaurar.sh /respaldos/pam_YYYYMMDD_HHMM.dump
set -eu
ARCHIVO="${1:?Indique el archivo .dump}"
sha256sum -c "$ARCHIVO.sha256"
echo "Se restaurará $ARCHIVO sobre la base $PGDATABASE en 10 segundos (Ctrl+C para cancelar)…"
sleep 10
pg_restore --clean --if-exists --no-owner -d "$PGDATABASE" "$ARCHIVO"
echo "Restauración terminada. Ejecute: docker compose exec api python -m pam.cli refrescar"
