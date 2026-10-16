# Sistema de Planificación Docente — Colegio CyD

Aplicación web para que cada docente registre su perfil, configure sus cursos/grados/secciones, elabore planificaciones bimestrales y genere la **Hoja de actividades en PDF** con el formato del Word institucional.

- **Backend:** Node.js 20+ y Express. **Base de datos:** SQLite (archivo único, con migraciones en `migrations/`).
- **Frontend:** Vue 3 servido localmente (sin CDN ni compilación). Interfaz en español, adaptable a computadora, tableta y teléfono.
- **PDF:** pdfmake en el servidor (no requiere Microsoft Word). Texto seleccionable, tildes y ñ, papel oficio vertical como la plantilla.

## Puesta en marcha

```bash
npm install
cp .env.example .env      # ajuste ADMIN_USERNAME / ADMIN_PASSWORD y demás valores
npm start                 # http://localhost:3000
```

Con Docker: `docker build -t cyd-planificacion . && docker run -p 3000:3000 --env-file .env -v cyd-data:/app/data -v cyd-backups:/app/backups cyd-planificacion`

En producción sírvala detrás de HTTPS (Nginx, Caddy o el proxy del hosting) con `SECURE_COOKIES=1` y `TRUST_PROXY=1`. Cualquier servicio que ejecute Node.js con disco persistente sirve (VPS, Railway, Render con disco, etc.).

Administrador adicional: `npm run create-admin -- usuario "Nombre completo"` (muestra una contraseña temporal).

Listado del colegio (grados, secciones, cursos, docentes y carga académica): `npm run import-catalog -- grados-cursos-docentes.md`. Se puede repetir sin duplicar; al registrarse, cada docente busca su nombre y se precargan sus cursos.

## Primeros pasos (administración)
1. Ingrese con el administrador inicial y cambie la contraseña temporal.
2. **Ciclos y bimestres:** cree el ciclo (p. ej. 2027) y configure fechas de los 4 bimestres.
3. **Grados y secciones:** cree los grados con su nivel y las secciones de cada uno.
4. **Institución:** revise textos del encabezado, observación, nota, firmas y logo (precargados desde la plantilla Word).

## Uso (docente)
1. **Registro:** nombre completo, usuario, contraseña (cuenta independiente de SICA).
2. **Asignaciones:** busque o cree el curso en el catálogo compartido, elija grado y una o varias secciones.
3. **Planificaciones:** abra o cree el plan del bimestre. El editor tiene secciones: Datos generales · Competencias e indicadores · Bloques y semanas (con la evaluación única de cada bloque) · Apartados adicionales · Revisión y PDF.
4. El trabajo se **guarda automáticamente** (indicador Guardando/Guardado/No se pudo guardar) y con el botón *Guardar borrador*.
5. **Marcar como listo** valida todo y lleva a cada campo faltante. Luego **Hoja de actividades → Generar** crea el PDF, se muestra y se descarga. Los PDF generados se conservan sin cambios.

## Correspondencia plantilla Word → sistema

| Elemento del Word | Origen en el sistema |
|---|---|
| Logo, “Colegio Particular Mixto / CIENCIA Y DESARROLLO”, líneas azul y amarilla, lema | Configuración institucional |
| “HOJA DE ACTIVIDADES ___ BIMESTRE” | Título institucional + número de bimestre del plan (se agrega la línea “Ciclo escolar”) |
| DOCENTE · GRADO · SECCIÓN · ASIGNATURA | Perfil del docente y asignación del plan |
| NOMBRE DEL ALUMNO / CLAVE | Líneas en blanco para llenar a mano |
| Franja “CONTENIDOS” | Texto institucional |
| Columna *Contenido* (“Temas:” + viñetas) | Contenidos de todas las semanas del bloque mensual (páginas del libro opcionales) |
| *Actividades de evaluación* | Descripción + indicaciones de la evaluación única del bloque |
| *Aspectos a evaluar* | Criterios: subtabla Aspectos/Calificación con Total (punteo por criterio) o lista + “TOTAL” (punteo total) |
| *Fecha de entrega* | Fecha de entrega de la evaluación del bloque |
| Actividad de mejoramiento, Afectivo, Asistencia, Examen final | Apartados adicionales del plan (con “No aplica”) |
| Observaciones, NOTA resaltada | Texto institucional + observaciones del plan |
| Firmas Catedrático, Padre de familia, Vo. Bo. Dirección | Textos institucionales |

Los valores del ejemplo del Word (nombres, temas, punteos como 20/5/30, fechas 00-00-2026) **no** se usan como datos ni reglas. Las actividades de aprendizaje y los períodos se registran en el plan pero no aparecen en la hoja (la plantilla no tiene columna para ellos). Si un bloque no cabe en una página, la tabla continúa con el encabezado repetido y una fila “Temas (continuación — mes)”.

## Respaldo y recuperación
- Respaldo en caliente verificado: `npm run backup` (o `node scripts/backup.js /ruta/destino`). Prográmelo a diario (cron) y copie los archivos fuera del servidor.
- Restaurar: detenga la app y ejecute `npm run restore -- respaldo.db --confirmar` (conserva la base actual con otro nombre).
- Todo (usuarios, planes, PDF generados, logo) está en el archivo `DB_FILE`.

## Seguridad
Contraseñas con scrypt; sesiones con cookie HttpOnly/SameSite; protección CSRF por encabezado; permisos verificados en el servidor (cada docente sólo ve sus planes; la administración consulta en modo lectura y todo queda en la bitácora); límite de intentos de ingreso; control de versiones para que una pestaña antigua no sobrescriba cambios.

## Pruebas
`npm test` ejecuta 50 pruebas de aceptación sobre una base temporal (requiere `pdftotext` de poppler-utils para revisar el texto del PDF). Resultado en `tests/RESULTADOS.md`.

## Pendientes y dependencias
- **Alojamiento con URL:** el sistema está listo para desplegarse, pero falta el servidor/hosting del colegio con HTTPS.
- **Correo (SMTP):** sin `SMTP_*` y `PUBLIC_URL`, la recuperación es sólo administrativa (no se simula ningún envío).
- **Imágenes de referencia de la planificación:** no llegaron adjuntas; la estructura del plan se basó en las reglas del requerimiento. Si las envía, se puede ajustar.
- La plantilla Word agrupa varias secciones (“A, B y C”) en una hoja; por regla del requerimiento, el sistema genera una hoja por sección.
