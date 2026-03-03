#!/bin/bash

# Script de instalación del Sistema de Votaciones del Congreso
# Autor: Sistema Automatizado
# Fecha: Octubre 2025

echo "=========================================="
echo "Sistema de Votaciones del Congreso GT"
echo "Script de Instalación"
echo "=========================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "ℹ $1"
}

# Verificar si se está ejecutando como root
if [[ $EUID -eq 0 ]]; then
   print_warning "Este script no debería ejecutarse como root"
fi

# Detectar sistema operativo
OS="unknown"
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    OS="mac"
else
    OS="windows"
fi

print_info "Sistema operativo detectado: $OS"
echo ""

# Paso 1: Verificar requisitos
echo "Paso 1: Verificando requisitos previos..."
echo "----------------------------------------"

# Verificar PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -c 1-3)
    print_success "PHP instalado (versión $PHP_VERSION)"
else
    print_error "PHP no está instalado"
    exit 1
fi

# Verificar MySQL/MariaDB
if command -v mysql &> /dev/null; then
    print_success "MySQL/MariaDB instalado"
else
    print_error "MySQL/MariaDB no está instalado"
    exit 1
fi

# Verificar Python
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version | cut -d " " -f 2)
    print_success "Python instalado (versión $PYTHON_VERSION)"
else
    print_warning "Python3 no está instalado (opcional pero recomendado)"
fi

# Verificar pdftotext
if command -v pdftotext &> /dev/null; then
    print_success "pdftotext (poppler-utils) instalado"
else
    print_warning "pdftotext no está instalado"
    echo "  Instalación en Linux: sudo apt-get install poppler-utils"
    echo "  Instalación en Mac: brew install poppler"
fi

echo ""

# Paso 2: Solicitar configuración de base de datos
echo "Paso 2: Configuración de base de datos"
echo "----------------------------------------"

read -p "Host de MySQL (por defecto: localhost): " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Nombre de la base de datos (por defecto: congreso_votaciones): " DB_NAME
DB_NAME=${DB_NAME:-congreso_votaciones}

read -p "Usuario de MySQL: " DB_USER

read -sp "Contraseña de MySQL: " DB_PASS
echo ""

echo ""
print_info "Configuración:"
echo "  Host: $DB_HOST"
echo "  Base de datos: $DB_NAME"
echo "  Usuario: $DB_USER"
echo ""

# Paso 3: Crear base de datos
echo "Paso 3: Creando base de datos..."
echo "----------------------------------------"

# Crear base de datos
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

if [ $? -eq 0 ]; then
    print_success "Base de datos creada"
else
    print_error "Error al crear la base de datos"
    print_info "Verifica tus credenciales e intenta crear la base de datos manualmente"
fi

# Importar estructura
print_info "Importando estructura de tablas..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database.sql 2>/dev/null

if [ $? -eq 0 ]; then
    print_success "Estructura de tablas importada"
else
    print_error "Error al importar la estructura"
fi

echo ""

# Paso 4: Configurar archivo config.php
echo "Paso 4: Configurando aplicación..."
echo "----------------------------------------"

# Backup del config.php original
if [ -f "config.php" ]; then
    cp config.php config.php.backup
    print_info "Backup creado: config.php.backup"
fi

# Actualizar configuración
sed -i.bak "s/define('DB_HOST', 'localhost');/define('DB_HOST', '$DB_HOST');/" config.php
sed -i.bak "s/define('DB_NAME', 'congreso_votaciones');/define('DB_NAME', '$DB_NAME');/" config.php
sed -i.bak "s/define('DB_USER', 'root');/define('DB_USER', '$DB_USER');/" config.php
sed -i.bak "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/" config.php

print_success "Archivo config.php actualizado"

# Crear directorio uploads si no existe
if [ ! -d "uploads" ]; then
    mkdir -p uploads
    chmod 755 uploads
    print_success "Directorio uploads/ creado"
else
    print_info "Directorio uploads/ ya existe"
fi

echo ""

# Paso 5: Verificar instalación
echo "Paso 5: Verificando instalación..."
echo "----------------------------------------"

# Probar conexión a la base de datos
php -r "
require 'config.php';
try {
    \$db = getDB();
    echo 'OK';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
"

if [ $? -eq 0 ]; then
    print_success "Conexión a la base de datos exitosa"
else
    print_error "Error al conectar con la base de datos"
fi

echo ""

# Instalar pdfplumber si Python está disponible
if command -v pip3 &> /dev/null; then
    echo "¿Deseas instalar pdfplumber para Python? (s/n)"
    read -p "> " INSTALL_PDFPLUMBER
    
    if [ "$INSTALL_PDFPLUMBER" = "s" ] || [ "$INSTALL_PDFPLUMBER" = "S" ]; then
        print_info "Instalando pdfplumber..."
        pip3 install pdfplumber --break-system-packages 2>/dev/null || pip3 install pdfplumber
        
        if [ $? -eq 0 ]; then
            print_success "pdfplumber instalado"
        else
            print_warning "No se pudo instalar pdfplumber automáticamente"
        fi
    fi
fi

echo ""

# Resumen final
echo "=========================================="
echo "¡Instalación completada!"
echo "=========================================="
echo ""
print_success "El sistema está listo para usar"
echo ""
echo "Próximos pasos:"
echo "1. Configura tu servidor web (Apache/Nginx) para servir los archivos"
echo "2. Accede al sistema en tu navegador"
echo "3. Ve a 'Cargar PDF' para subir tu primer documento"
echo ""
echo "URLs de acceso:"
echo "  Dashboard: http://localhost/congreso/"
echo "  Cargar PDF: http://localhost/congreso/cargar.php"
echo ""
echo "Documentación completa en: README.md"
echo ""
print_info "Si encuentras problemas, revisa el archivo error.log"
echo ""
