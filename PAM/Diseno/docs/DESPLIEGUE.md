# Manual de despliegue

Este manual instala el Sistema PAM en un servidor Linux del Ministerio. Hay dos caminos:
**A. Docker Compose** (recomendado) y **B. instalación directa** en Ubuntu sin contenedores.

## 1. Requisitos del servidor

| Recurso | Mínimo | Recomendado (800 mil – 3 millones de expedientes) |
|---|---|---|
| CPU | 4 vCPU | 8 vCPU |
| RAM | 8 GB | 16 GB |
| Disco | 60 GB SSD | 200 GB SSD (base ≈ 2 GB por cada 800 mil expedientes + respaldos) |
| Sistema | Ubuntu Server 22.04/24.04 LTS, Debian 12 o RHEL 9 | |
| Software | Docker Engine 24+ con Docker Compose v2 | |
| Red | Puertos 80 y 443 abiertos hacia la red institucional | Nombre DNS interno (p. ej. `pam.mintrab.gob.gt`) |

El sistema **no necesita salida a internet** una vez construidas las imágenes: tipografías, logotipos y librerías van incluidos.

## 2. Instalación con Docker Compose

```bash
# 1. Copiar el proyecto al servidor (por ejemplo en /opt/pam)
sudo mkdir -p /opt/pam && sudo chown $USER /opt/pam
cd /opt/pam && unzip pam-sistema.zip && cd pam-sistema

# 2. Configuración
cp .env.example .env
openssl rand -hex 32                  # pegue el resultado en JWT_SECRET
openssl rand -base64 24               # pegue el resultado en POSTGRES_PASSWORD
nano .env                             # ajuste PG_SHARED_BUFFERS (25 % RAM) y PG_CACHE (75 % RAM)

# 3. Certificado HTTPS
#    Producción: copie el certificado institucional como deploy/nginx/certs/pam.crt y pam.key
#    Pruebas:    ./deploy/nginx/certificado-prueba.sh pam.mintrab.local

# 4. Construir y levantar
docker compose up -d --build
docker compose ps                     # db, api, worker, web y respaldo deben estar "running/healthy"

# 5. Primer administrador
docker compose exec api python -m pam.cli crear-admin --usuario admin --nombre "Administrador PAM" --correo admin@mintrab.gob.gt
```

El último comando muestra una **contraseña temporal**; al ingresar, el sistema obliga a cambiarla.

> Si el servidor no tiene acceso a Docker Hub, construya las imágenes en otro equipo y transfiéralas:
> `docker save pam-backend:1.0 postgres:16 nginx:1.27-alpine | gzip > imagenes.tar.gz` y en el servidor
> `docker load < imagenes.tar.gz`.

## 3. Primera carga de la matriz

```bash
docker compose cp "/ruta/BD PAM.csv" api:/data/importaciones/
docker compose exec api python -m pam.cli importar "/data/importaciones/BD PAM.csv" --validar   # opcional: solo revisa
docker compose exec api python -m pam.cli importar "/data/importaciones/BD PAM.csv" --usuario admin
```

Tiempo de referencia: 802,556 filas → ≈ 4,5 minutos en 2 vCPU. Al terminar imprime el reporte de conciliación
(debe decir `"diferencias": []`). Las cargas siguientes pueden hacerse desde **Control → Importaciones** en la web.

## 4. Operación diaria

| Tarea | Comando |
|---|---|
| Ver estado de los servicios | `docker compose ps` |
| Ver registros de la API / importaciones | `docker compose logs -f api` · `docker compose logs -f worker` |
| Respaldo inmediato | `docker compose exec respaldo sh /respaldo.sh ahora` |
| Restaurar un respaldo | `docker compose exec respaldo sh /restaurar.sh /respaldos/pam_AAAAMMDD_HHMM.dump` |
| Recalcular el tablero | `docker compose exec api python -m pam.cli refrescar` |
| Restablecer clave de un administrador | `docker compose exec api python -m pam.cli crear-admin --usuario admin` |
| Detener / iniciar | `docker compose stop` · `docker compose start` |

**Respaldos:** el servicio `respaldo` genera cada día (a la `HORA_RESPALDO`) un archivo `pam_AAAAMMDD_HHMM.dump`
con su suma SHA-256 en `BACKUP_DIR`, y borra los mayores a `RETENCION_DIAS`. Copie esa carpeta a otro servidor o
almacenamiento institucional. Pruebe una restauración al menos una vez al mes en un ambiente de pruebas.

## 5. Actualizar a una nueva versión

```bash
docker compose exec respaldo sh /respaldo.sh ahora   # siempre respaldar antes
git pull   # o descomprimir la nueva versión encima
docker compose up -d --build                         # las migraciones nuevas se aplican solas al arrancar la API
```

## 6. Instalación directa en Ubuntu (sin Docker)

```bash
sudo apt install -y postgresql-16 postgresql-contrib nginx python3.12-venv
sudo -u postgres createuser -P pam && sudo -u postgres createdb -O pam pam
sudo -u postgres psql -d pam -c "CREATE EXTENSION pg_trgm; CREATE EXTENSION unaccent; CREATE EXTENSION pgcrypto;"
sudo useradd -r -m -d /opt/pam pam && sudo cp -r . /opt/pam/app && sudo chown -R pam /opt/pam
sudo -u pam python3.12 -m venv /opt/pam/venv && sudo -u pam /opt/pam/venv/bin/pip install -r /opt/pam/app/backend/requirements.txt
sudo cp deploy/systemd/pam.env.example /etc/pam.env && sudo nano /etc/pam.env     # DATABASE_URL, JWT_SECRET…
sudo cp deploy/systemd/pam-api.service deploy/systemd/pam-worker.service /etc/systemd/system/
sudo systemctl daemon-reload && sudo systemctl enable --now pam-api pam-worker
# Nginx: copie deploy/nginx/pam.conf a /etc/nginx/sites-enabled/, cambie "proxy_pass http://api:8000" por
# "proxy_pass http://127.0.0.1:8000" y "root" por /opt/pam/app/frontend; luego: sudo nginx -t && sudo systemctl reload nginx
sudo -u pam bash -c 'set -a; . /etc/pam.env; cd /opt/pam/app/backend && /opt/pam/venv/bin/python -m pam.cli crear-admin'
```

## 7. Solución de problemas

| Síntoma | Causa probable | Acción |
|---|---|---|
| La web muestra "No se pudieron cargar los datos" | La API no responde | `docker compose logs api` |
| Importación queda en PENDIENTE | El worker no está corriendo | `docker compose up -d worker` |
| Importación en ERROR "No se encontró la fila de encabezados" | El archivo no tiene MUNICIPIO, NOMBRE, DPI o ESTADO ACTUAL | Revise la primera fila; ver docs/IMPORTACION.md |
| Usuario bloqueado | 5 intentos fallidos | Esperar 15 min o "Restablecer clave" en Administración |
| El tablero no refleja cambios recientes | Los agregados se refrescan cada 15 min | `python -m pam.cli refrescar` |
| Error 413 al subir | Archivo mayor a 600 MB | Subir CSV (pesa menos que Excel) o ajustar `MAX_UPLOAD_MB` y `client_max_body_size` |
