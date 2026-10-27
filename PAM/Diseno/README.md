# PAM · Sistema Integral de Gestión y Monitoreo del Programa del Adulto Mayor

Ministerio de Trabajo y Previsión Social · Guatemala

Plataforma web para administrar, consultar, monitorear y auditar los expedientes del Programa del Adulto Mayor.
Convierte la matriz histórica (≈ 800 mil filas en Excel/CSV) en una base relacional **Persona → Expediente → Eventos**
con historial, tablero territorial, alertas, calidad de datos, reportes controlados y bitácora inmutable.

Probado con la matriz real `BD PAM.csv`: **802,556 filas importadas en ≈ 4,5 minutos, conciliación sin diferencias
por departamento × estado**.

## Contenido del repositorio

```
pam-sistema/
├── docker-compose.yml          # base de datos, API, worker, Nginx y respaldos
├── .env.example                # configuración (copiar como .env)
├── Makefile                    # atajos de operación
├── db/migrations/              # esquema SQL versionado (se aplica solo al arrancar)
│   ├── 001_base.sql            #   extensiones, esquemas, funciones (CUI, fechas, normalización)
│   ├── 002_catalogos.sql       #   22 departamentos, 340 municipios, estados, 46 tipos de resolución
│   ├── 003_seguridad.sql       #   usuarios, 7 roles, 21 permisos configurables, sesiones
│   ├── 004_core.sql            #   persona, expediente, visitas, resoluciones, nómina, estados, línea de tiempo
│   ├── 005_auditoria.sql       #   bitácora de solo inserción + triggers de auditoría
│   ├── 006_importacion.sql     #   área de carga, validaciones y transformación masiva (SQL)
│   └── 007_mart.sql            #   agregados del tablero (vistas materializadas)
├── backend/                    # API FastAPI + importador + worker (Python 3.12)
│   ├── pam/api/                #   rutas /api/v1 (auth, tablero, expedientes, reportes, importaciones, admin)
│   ├── pam/etl/                #   lector CSV/Excel en streaming e importador por lotes
│   ├── pam/cli.py              #   comandos: migrar, crear-admin, importar, refrescar, worker
│   └── tests/                  #   pruebas de integración (pytest)
├── frontend/                   # interfaz web (HTML/CSS/JS sin compilación, servida por Nginx)
├── deploy/                     # Nginx, Dockerfile entrypoint, respaldos y servicios systemd
└── docs/                       # manuales: despliegue, importación, operación, arquitectura, seguridad
```

## Puesta en marcha rápida (servidor con Docker)

```bash
cp .env.example .env                      # edite claves: POSTGRES_PASSWORD, JWT_SECRET
./deploy/nginx/certificado-prueba.sh      # o copie el certificado institucional a deploy/nginx/certs/
docker compose up -d --build              # crea la base, aplica migraciones y levanta todo
docker compose exec api python -m pam.cli crear-admin --usuario admin --nombre "Administrador PAM"
```

Ingrese a `https://<servidor>` con el usuario `admin` y la contraseña temporal que muestra el último comando.

### Cargar la matriz completa (800 mil+ registros)

Desde la web: **Control → Importaciones**, arrastre el archivo `.xlsx` o `.csv` (hasta 600 MB).
Desde la consola (recomendado para la primera carga):

```bash
docker compose cp "BD PAM.csv" api:/data/importaciones/
docker compose exec api python -m pam.cli importar "/data/importaciones/BD PAM.csv" --usuario admin
```

Detalles en [docs/IMPORTACION.md](docs/IMPORTACION.md). Guía completa de instalación en [docs/DESPLIEGUE.md](docs/DESPLIEGUE.md).

## Tecnología

| Capa | Tecnología | Por qué |
|---|---|---|
| Base de datos | PostgreSQL 16 + pg_trgm + unaccent | Búsqueda sin tildes con índices trigram, vistas materializadas, transacciones |
| API | Python 3.12 · FastAPI · psycopg 3 | Un solo lenguaje para API e importador; carga masiva con `COPY` |
| Importación | Lector en streaming (CSV y Excel) + transformación en SQL | 800 mil filas sin cargar el archivo en memoria |
| Interfaz | HTML + CSS + JavaScript sin dependencias externas | Sin compilación ni CDN: funciona en redes institucionales cerradas |
| Publicación | Nginx (HTTPS, cabeceras de seguridad, CSP estricta) | |
| Operación | Docker Compose, respaldos diarios `pg_dump` | |

La propuesta inicial contemplaba NestJS y Redis. Se eligió FastAPI porque el importador masivo y la API comparten
el mismo código Python, y Redis no fue necesario: los agregados se precalculan en PostgreSQL y la API los mantiene en
memoria con validación por versión (ETag). Puede incorporarse después sin cambiar la API.

## Pruebas

```bash
cd backend && pip install -r requirements-dev.txt
TEST_DATABASE_URL=postgresql://postgres@localhost/pam_test pytest -q
```
