#!/bin/bash
# Obtener el directorio donde está este script .command
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$DIR"

echo "========================================"
echo "            Iniciando pScan"
echo "========================================"

# Verificar si el servidor ya está corriendo en el puerto 8088
if lsof -Pi :8088 -sTCP:LISTEN -t >/dev/null ; then
    echo "El servidor ya se encuentra ejecutándose."
else
    echo "Iniciando servidor de base de datos en segundo plano..."
    # Lanzar el servidor en segundo plano
    python3 server.py > /dev/null 2>&1 &
    # Darle un momento al servidor para inicializar
    sleep 1.5
fi

# Abrir el buscador en el navegador predeterminado
echo "Abriendo pScan en tu navegador..."
open "http://localhost:8088"

echo "¡Listo! Ya puedes cerrar esta ventana de la Terminal."
sleep 2
exit 0
