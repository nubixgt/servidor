# Base de datos · TestBases

MySQL 5.7+ / MariaDB 10.3+. Todo en `utf8mb4` / `InnoDB`. Sin seeders ni lógica en código: la base se arma **solo con estos archivos SQL**, ejecutados a mano.

## Orden de ejecución

| # | Archivo | Qué hace |
|---|---------|----------|
| 1 | `01_esquema.sql` | Crea la base `testbases` y las 6 tablas. |
| 2 | `02_catalogos_y_usuarios.sql` | Inserta los 2 roles y los usuarios `admin` y `tecnico`. |
| 3 | `03_carga_beneficiarios.sql` | Carga las 13,980 filas del Excel. **No está en git** (datos personales); se genera con la herramienta. |
| 4 | `04_telefono_agregado.sql` | Agrega a `beneficiarios` quién y cuándo agregó un teléfono desde la app. Ejecutar antes de desplegar esa versión. |

En cPanel/phpMyAdmin: crea la base y el usuario desde cPanel, comenta las líneas `CREATE DATABASE`/`USE` de `01_esquema.sql` si la base ya existe, selecciónala e importa los archivos en orden.

## Modelo

```
roles ──< usuarios ──< seguimientos >──1:1── beneficiarios >── comunidades
                                                   └────────── comunidades_base
```

| Tabla | Contenido |
|-------|-----------|
| `roles` | `admin` y `tecnico`. |
| `usuarios` | Cuentas de acceso (clave en bcrypt, contador de intentos y bloqueo temporal). |
| `comunidades` | Columna COMUNIDAD del Excel. |
| `comunidades_base` | Columna COMUNIDAD BASE del Excel (dato distinto; no se mezcla con el anterior). |
| `beneficiarios` | Una fila por fila del Excel. Datos de origen: **no se editan desde la app**, salvo el teléfono cuando venía vacío (`telefono_agregado_por/en` registran quién y cuándo). |
| `seguimientos` | Lo que la app agrega por beneficiario: 1 comentario + 1 imagen. Tabla aparte a propósito. |

Decisiones de limpieza al cargar el Excel: CUI como `CHAR(13)` único (Excel lo mostraba en notación científica); teléfono `0` → `NULL`; comunidad base vacía → `NULL`; el color rojo/ámbar de las filas se guarda en `resaltado_origen` (significado por confirmar); `fila_origen` guarda el número de fila del Excel para auditar.

## Regenerar la carga (03)

Desde `TestBases/`, con el Excel en la raíz del proyecto:

```sh
python3 Database/herramientas/generar_carga.py
```

Solo usa la librería estándar de Python. Aborta si encuentra un CUI inválido o repetido.

## Cambiar contraseñas

```sh
php -r "echo password_hash('NUEVA_CLAVE', PASSWORD_BCRYPT), PHP_EOL;"
```
```sql
UPDATE usuarios SET clave_hash = '<hash>' WHERE usuario = 'admin';
```

## Cambios futuros

No se edita `01_esquema.sql` cuando la base ya está en producción: se agrega un archivo nuevo (`04_...sql`, `05_...sql`) con los `ALTER TABLE`, para que el historial quede ordenado.
