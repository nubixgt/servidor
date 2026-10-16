# Resultado de las pruebas de aceptación

Fecha: 2026-10-16T15:05:19.468Z

**66 de 66 pruebas aprobadas.**


## Configuración institucional

- ✅ El administrador inicia sesión y crea un ciclo con cuatro bimestres
- ✅ Fechas de los bimestres configurables (y se rechazan bimestres superpuestos)
- ✅ Grados con secciones propias

## Registro y permisos

- ✅ Registro completo: cuenta + curso nuevo + grado + secciones en una sola operación
- ✅ Cada fila genera una relación por sección (sin combinar cursos con todos los grados)
- ✅ Registro sin asignación completa o con errores: no se crea nada y se señalan los campos
- ✅ Un nombre de usuario duplicado se rechaza (sin distinguir mayúsculas)
- ✅ El formulario de registro obtiene ciclo, grados con secciones y catálogo sin iniciar sesión
- ✅ Importación del listado del colegio: grados, cursos, docentes y carga académica (sin duplicar al repetir)
- ✅ Al elegir su nombre se precargan sus cursos agrupados por curso y grado
- ✅ Registro vinculado al listado con cualquier contraseña; el nombre no puede usarse dos veces
- ✅ Las contraseñas se guardan con hash, nunca en texto legible
- ✅ Rutas privadas protegidas: sin sesión 401; sin encabezado CSRF 403
- ✅ Un docente no puede usar rutas de administración

## Cursos y asignaciones

- ✅ “Matemáticas”, “ MATEMÁTICAS ” y “Matematicas” no crean cursos duplicados
- ✅ “Matemáticas financieras” se crea como curso diferente
- ✅ Creación simultánea del mismo curso por varios docentes produce un único registro
- ✅ Un docente puede tener varios cursos, grados y secciones (una asignación por sección)
- ✅ Las secciones dependen del grado (sección de otro grado rechazada)
- ✅ No se duplican asignaciones
- ✅ Un docente no puede renombrar un curso compartido que usan otras cuentas
- ✅ Desde «Mis cursos» se agregan varias filas a la vez (curso existente y curso nuevo)
- ✅ Quitar una asignación sin planes la elimina sin borrar el curso del catálogo

## Planificación

- ✅ Crear plan; un segundo intento abre el existente (sin duplicar)
- ✅ Un borrador incompleto puede guardarse
- ✅ Bloques flexibles (1 y 3 semanas), bloque que cruza de enero a febrero, 35 contenidos en una semana
- ✅ La numeración de semanas continúa dentro del bimestre (no reinicia por bloque)
- ✅ Un contenido admite varias actividades de aprendizaje y los períodos se guardan por contenido
- ✅ Cada bloque tiene exactamente una evaluación; varios criterios no crean varias evaluaciones
- ✅ Suma de criterios sin doble conteo (criterios 6+5+4 = 15; modo total = 20)
- ✅ Los planes de las secciones A y B permanecen separados
- ✅ Fechas inválidas se detectan antes de marcar como listo
- ✅ Una pestaña antigua no sobrescribe cambios más recientes (conflicto de versión 409)
- ✅ Cada cuenta recupera sus planes después de cerrar sesión
- ✅ Un docente no puede acceder a planes ajenos modificando URL o petición

## Estado “Listo” y hoja de actividades en PDF

- ✅ No se genera la hoja final mientras el plan es borrador; la vista previa no lleva marcas de borrador
- ✅ Marcar como listo registra la fecha
- ✅ Editar un plan listo requiere acción explícita
- ✅ Cambiar sólo una actividad de aprendizaje (no aparece en el PDF) conserva el estado Listo
- ✅ Generar la hoja final (plan listo) y descargarla
- ✅ Formato de la plantilla: papel oficio vertical (612 × 1008 pt) y texto seleccionable
- ✅ Recupera docente, curso, grado, sección, ciclo y bimestre correctos (con tildes y ñ)
- ✅ Columnas de la plantilla, criterios, punteos y fechas conservados
- ✅ Incluye apartados adicionales registrados (“No aplica” explícito) y textos institucionales
- ✅ No confunde aprendizaje con evaluación: las actividades de aprendizaje no aparecen en la hoja
- ✅ Agrupa los contenidos con la evaluación de su bloque
- ✅ No agrega valores que nadie registró (sin undefined/null/NaN ni datos de ejemplo de la plantilla)
- ✅ La vista previa y el PDF descargado corresponden a la misma información guardada
- ✅ Documento extenso: varios meses y textos largos se distribuyen en varias páginas, sin recortes ni letra diminuta
- ✅ Consistencia histórica: renombrar un curso o el perfil no cambia hojas ya generadas
- ✅ Cambiar un tema (información del PDF) devuelve el plan a borrador
- ✅ Se conserva el registro de creación, modificaciones y cambios de estado

## Plan bimestral en PDF

- ✅ Plan corto: papel legal, título y datos del plan seleccionado, cinco secciones en orden
- ✅ Contenidos, páginas, actividades por contenido y períodos guardados (no numeración automática)
- ✅ Una sola evaluación por bloque: se imprime su descripción y no los criterios de la hoja
- ✅ No aparecen #REF!, undefined, null ni datos del ejemplo; sin entrega ni revisión inventadas
- ✅ Entrega y revisión reales: entregar requiere plan listo; la revisión la registra coordinación
- ✅ Plan extenso: varias páginas legibles, encabezados repetidos, continuaciones y todos los contenidos
- ✅ Otro docente no puede obtener el plan bimestral ajeno

## Administración

- ✅ El administrador consulta planes (registrado) pero no modifica contenido académico
- ✅ Devolver a borrador por la administración queda registrado con motivo
- ✅ Seguimiento: estados por docente y bimestre
- ✅ Quitar una asignación con planes la desactiva y conserva el historial

## Recuperación y restablecimiento de acceso

- ✅ Sin correo configurado no se simula ningún envío de recuperación
- ✅ Restablecimiento administrativo: contraseña temporal de un solo uso y cambio obligatorio
- ✅ Cuenta desactivada no puede ingresar
