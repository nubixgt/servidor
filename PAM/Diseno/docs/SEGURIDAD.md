# Seguridad y privacidad

| Control | Implementación |
|---|---|
| Contraseñas | Argon2id; política 10+ caracteres con mayúsculas, minúsculas y números; cambio obligatorio en el primer ingreso |
| Bloqueo por intentos | 5 intentos fallidos → 15 minutos (configurable) |
| Sesiones | Token de acceso de 30 min en memoria del navegador + renovación con cookie `httpOnly`, `Secure`, `SameSite=Strict`, rotada en cada uso y revocable; cambiar la clave cierra las demás sesiones |
| Autorización | 21 permisos por módulo y acción asignados a roles configurables; ámbito territorial por usuario aplicado en la base |
| Datos personales | DPI enmascarado por defecto (`•••• ••••• 1601`); ver el completo exige permiso y queda auditado; tableros sin datos personales |
| Consultas masivas | Límite de búsquedas por usuario por minuto; conteos acotados; exportación con motivo, límite de filas por rol y marca de uso oficial |
| Auditoría | Bitácora de solo inserción: inicios de sesión, fallidos, consultas de expediente y DPI, exportaciones, importaciones y cada cambio de datos con valores antes/después, IP y request id |
| Transporte | HTTPS (TLS 1.2/1.3) con HSTS; HTTP redirige a HTTPS |
| Navegador | CSP estricta sin orígenes externos, `X-Frame-Options DENY`, `nosniff`, sin librerías ni fuentes de terceros |
| Base de datos | No expuesta fuera de la red interna de Docker; respaldos diarios con suma SHA-256 y retención configurable |
| Importaciones | Validación de extensión y tamaño, nombres de archivo saneados, procesamiento fuera del proceso web |

## Recomendaciones de operación

1. Use el certificado institucional y un nombre DNS interno; no publique el sistema a internet sin VPN.
2. Genere `JWT_SECRET` y `POSTGRES_PASSWORD` aleatorios y guárdelos en el gestor de secretos del Ministerio.
3. Copie los respaldos a otro servidor y pruebe una restauración mensual.
4. Revise la bitácora semanalmente (filtro `VER_DPI`, `EXPORTAR`, `LOGIN_FALLIDO`).
5. Desactive usuarios en cuanto dejen el puesto (cierra sus sesiones al instante).
6. Aplique actualizaciones del sistema operativo y de las imágenes Docker mensualmente.
