@echo off
REM Script de instalacion de TCPDF para Windows
REM Sistema de Vales de Caja Chica - VISAR

echo ===========================================
echo Instalador de TCPDF para Windows
echo Sistema de Vales de Caja Chica - VISAR
echo ===========================================
echo.

REM Verificar si existe Composer
where composer >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Se detecta Composer instalado.
    echo Instalando TCPDF via Composer...
    composer require tecnickcom/tcpdf
    
    if %ERRORLEVEL% EQU 0 (
        echo.
        echo ===========================================
        echo TCPDF instalado correctamente via Composer
        echo ===========================================
        echo.
    ) else (
        echo Error al instalar TCPDF con Composer
        goto MANUAL
    )
) else (
    goto MANUAL
)

goto END

:MANUAL
echo.
echo Composer no esta instalado o la instalacion fallo.
echo.
echo Por favor, descargue TCPDF manualmente:
echo.
echo 1. Visite: https://github.com/tecnickcom/TCPDF/releases
echo 2. Descargue la ultima version (tcpdf_X_X_X.zip)
echo 3. Extraiga el contenido en una carpeta llamada "tcpdf"
echo 4. Coloque la carpeta "tcpdf" en el mismo directorio que este script
echo.
pause

:END
echo.
echo Instalacion completada.
echo Consulte README.md para continuar con la configuracion.
echo.
pause