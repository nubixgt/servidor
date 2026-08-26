import re

input_file = "c:/xampp/htdocs/unificacion_maga/Databases/dump-RegistroClimatologico-202606091625.sql"
output_file = "c:/xampp/htdocs/unificacion_maga/Databases/clima_migracion_final.sql"

with open(input_file, 'r', encoding='utf-8') as f:
    content = f.read()

new_sql = """-- Migración de Climatología con Prefijos (clima_)
-- Limpiado de directivas BINLOG y GTID_PURGED
SET FOREIGN_KEY_CHECKS=0;

-- DROPS en orden inverso para evitar conflictos de claves foraneas
DROP TABLE IF EXISTS `clima_fotos`;
DROP TABLE IF EXISTS `clima_registros`;
DROP TABLE IF EXISTS `clima_alertas`;
DROP TABLE IF EXISTS `clima_usuarios`;

"""

# 1. Extraer tabla usuarios -> clima_usuarios
m_us_create = re.search(r"CREATE TABLE `usuarios` \((.*?)\) ENGINE=InnoDB.*?;", content, re.DOTALL)
if m_us_create:
    new_sql += "-- 1. Tabla de Usuarios (clima_usuarios)\n"
    create_str = m_us_create.group(1)
    new_sql += f"CREATE TABLE `clima_usuarios` ({create_str}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n"

m_us_insert = re.search(r"INSERT INTO `usuarios` VALUES\s*(.*?);", content, re.DOTALL)
if m_us_insert:
    new_sql += "INSERT INTO `clima_usuarios` VALUES\n"
    new_sql += m_us_insert.group(1) + ";\n\n"

# 2. Extraer tabla alertas -> clima_alertas
m_al_create = re.search(r"CREATE TABLE `alertas` \((.*?)\) ENGINE=InnoDB.*?;", content, re.DOTALL)
if m_al_create:
    new_sql += "-- 2. Tabla de Alertas (clima_alertas)\n"
    create_str = m_al_create.group(1)
    # Reemplazar fk
    create_str = create_str.replace("REFERENCES `usuarios` (`id`)", "REFERENCES `clima_usuarios` (`id`)")
    new_sql += f"CREATE TABLE `clima_alertas` ({create_str}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n"

m_al_insert = re.search(r"INSERT INTO `alertas` VALUES\s*(.*?);", content, re.DOTALL)
if m_al_insert:
    new_sql += "INSERT INTO `clima_alertas` VALUES\n"
    new_sql += m_al_insert.group(1) + ";\n\n"

# 3. Extraer tabla registros_climaticos -> clima_registros
m_reg_create = re.search(r"CREATE TABLE `registros_climaticos` \((.*?)\) ENGINE=InnoDB.*?;", content, re.DOTALL)
if m_reg_create:
    new_sql += "-- 3. Tabla de Registros Climaticos (clima_registros)\n"
    create_str = m_reg_create.group(1)
    # Reemplazar fk
    create_str = create_str.replace("REFERENCES `usuarios` (`id`)", "REFERENCES `clima_usuarios` (`id`)")
    new_sql += f"CREATE TABLE `clima_registros` ({create_str}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n"

m_reg_insert = re.search(r"INSERT INTO `registros_climaticos` VALUES\s*(.*?);", content, re.DOTALL)
if m_reg_insert:
    new_sql += "INSERT INTO `clima_registros` VALUES\n"
    new_sql += m_reg_insert.group(1) + ";\n\n"

# 4. Extraer tabla registros_fotografias -> clima_fotos
m_fot_create = re.search(r"CREATE TABLE `registros_fotografias` \((.*?)\) ENGINE=InnoDB.*?;", content, re.DOTALL)
if m_fot_create:
    new_sql += "-- 4. Tabla de Fotografias (clima_fotos)\n"
    create_str = m_fot_create.group(1)
    # Reemplazar fk a registros_climaticos por clima_registros
    create_str = create_str.replace("REFERENCES `registros_climaticos` (`id`)", "REFERENCES `clima_registros` (`id`)")
    new_sql += f"CREATE TABLE `clima_fotos` ({create_str}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n"

m_fot_insert = re.search(r"INSERT INTO `registros_fotografias` VALUES\s*(.*?);", content, re.DOTALL)
if m_fot_insert:
    new_sql += "INSERT INTO `clima_fotos` VALUES\n"
    new_sql += m_fot_insert.group(1) + ";\n\n"

new_sql += "SET FOREIGN_KEY_CHECKS=1;\n"

with open(output_file, 'w', encoding='utf-8') as f:
    f.write(new_sql)

print(f"Migración completada. Archivo generado en: {output_file}")
