#!/bin/sh
# Genera un certificado autofirmado SOLO para pruebas internas. En producción use el certificado institucional.
cd "$(dirname "$0")/certs" && openssl req -x509 -nodes -newkey rsa:2048 -days 365 \
  -keyout pam.key -out pam.crt -subj "/C=GT/O=MINTRAB/CN=${1:-pam.local}" && echo "Certificado de prueba creado en $(pwd)"
