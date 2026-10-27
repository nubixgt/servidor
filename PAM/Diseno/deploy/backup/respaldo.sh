#!/bin/sh
# Respaldo de la base PAM con pg_dump (formato custom, comprimido).
#   respaldo.sh ahora        → un respaldo inmediato
#   respaldo.sh programado   → un respaldo diario a la HORA_RESPALDO, con retención de RETENCION_DIAS
set -eu
DESTINO=/respaldos
mkdir -p "$DESTINO"
respaldar() {
  f="$DESTINO/pam_$(date +%Y%m%d_%H%M).dump"
  echo "$(date) · respaldando en $f"
  pg_dump -Fc -Z 6 --no-owner -f "$f.tmp" && mv "$f.tmp" "$f"
  sha256sum "$f" > "$f.sha256"
  find "$DESTINO" -name 'pam_*.dump*' -mtime +"${RETENCION_DIAS:-30}" -delete
  echo "$(date) · respaldo terminado ($(du -h "$f" | cut -f1))"
}
if [ "${1:-ahora}" = "ahora" ]; then respaldar; exit 0; fi
while true; do
  if [ "$(date +%H)" = "${HORA_RESPALDO:-01}" ] && [ ! -f "$DESTINO/.hecho_$(date +%Y%m%d)" ]; then
    respaldar && rm -f "$DESTINO"/.hecho_* && touch "$DESTINO/.hecho_$(date +%Y%m%d)"
  fi
  sleep 600
done
