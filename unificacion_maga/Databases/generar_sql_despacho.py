import re

input_file = "c:/xampp/htdocs/unificacion_maga/Databases/dump-actividades_despacho-202606091544.sql"
output_file = "c:/xampp/htdocs/unificacion_maga/Databases/despacho_migracion_final.sql"

with open(input_file, 'r', encoding='utf-8') as f:
    content = f.read()

new_sql = """-- Migración de Actividades Despacho con Prefijos (despacho_)
-- Limpiado de directivas BINLOG y GTID_PURGED
SET FOREIGN_KEY_CHECKS=0;

-- DROPS en orden inverso para evitar conflictos de claves foraneas en phpMyAdmin
DROP TABLE IF EXISTS `despacho_actividades_adjuntos`;
DROP TABLE IF EXISTS `despacho_actividades_seguimiento`;
DROP TABLE IF EXISTS `despacho_actividades`;
DROP TABLE IF EXISTS `despacho_tecnicos`;

-- 1. Tabla de Tecnicos
CREATE TABLE `despacho_tecnicos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `cargo` varchar(100),
  `area` varchar(100),
  `rol` varchar(100),
  `email` varchar(150),
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Actividades
CREATE TABLE `despacho_actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tecnico_id` int NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `categoria` varchar(100),
  `estado` varchar(50) DEFAULT 'PENDIENTE',
  `prioridad` varchar(50) DEFAULT 'MEDIA',
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tecnico_id` (`tecnico_id`),
  CONSTRAINT `despacho_act_fk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `despacho_tecnicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Seguimiento
CREATE TABLE `despacho_actividades_seguimiento` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actividad_id` INT NOT NULL,
    `comentario` TEXT NOT NULL,
    `fecha_registro` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `despacho_seg_fk` FOREIGN KEY (`actividad_id`) REFERENCES `despacho_actividades`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Adjuntos
CREATE TABLE `despacho_actividades_adjuntos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `actividad_id` INT NOT NULL,
    `nombre_archivo` VARCHAR(255) NOT NULL,
    `url_archivo` VARCHAR(255) NOT NULL,
    `tipo_archivo` VARCHAR(50),
    `fecha_carga` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `despacho_adj_fk` FOREIGN KEY (`actividad_id`) REFERENCES `despacho_actividades`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

"""

# Extract usuarios
m_users = re.search(r'INSERT INTO `usuarios` VALUES\s*(.*?);', content, re.DOTALL)
if m_users:
    new_sql += "\n-- Insertando Técnicos (Migrados de la antigua tabla usuarios)\n"
    new_sql += "INSERT INTO `despacho_tecnicos` (`id`, `nombre`, `rol`, `activo`) VALUES\n"
    
    # We carefully split using regex because values might contain commas
    groups = re.findall(r"\(\d+,\s*'.*?',\s*'.*?',\s*'.*?',\s*'.*?',\s*'.*?'.*?\)", m_users.group(1), re.DOTALL)
    
    rows = []
    for group in groups:
        mg = re.search(r"\((\d+),\s*'(.*?)',\s*'.*?',\s*'.*?',\s*'(.*?)',\s*'(.*?)'", group)
        if mg:
            id_val = mg.group(1)
            nombre = mg.group(2)
            rol = mg.group(3)
            estado = mg.group(4)
            activo = "1" if estado == "activo" else "0"
            rows.append(f"({id_val}, '{nombre}', '{rol}', {activo})")
    
    new_sql += ",\n".join(rows) + ";\n"

# Extract actividades
m_act = re.search(r'INSERT INTO `actividades` VALUES\s*(.*?);', content, re.DOTALL)
if m_act:
    act_sql = m_act.group(1)
    
    # Fix enum values
    act_sql = act_sql.replace("'activa'", "'PENDIENTE'")
    act_sql = act_sql.replace("'en_progreso'", "'EN PROGRESO'")
    act_sql = act_sql.replace("'completada'", "'COMPLETADA'")
    act_sql = act_sql.replace("'critica'", "'CRITICA'")
    act_sql = act_sql.replace("'baja'", "'BAJA'")
    act_sql = act_sql.replace("'media'", "'MEDIA'")
    act_sql = act_sql.replace("'alta'", "'ALTA'")
    
    new_sql += "\n-- Insertando Actividades\n"
    new_sql += "INSERT INTO `despacho_actividades` (`id`, `tecnico_id`, `titulo`, `descripcion`, `estado`, `prioridad`, `categoria`, `fecha_inicio`, `fecha_fin`, `fecha_creacion`, `fecha_modificacion`) VALUES\n"
    new_sql += act_sql + ";\n"

new_sql += "\nSET FOREIGN_KEY_CHECKS=1;\n"

with open(output_file, 'w', encoding='utf-8') as f:
    f.write(new_sql)
print("Fix Evaluacion Generado con Exito en", output_file)
