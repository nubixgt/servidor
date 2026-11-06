@echo off
REM Apaga el servidor de la app, este donde este (aunque hayas cerrado la
REM ventana donde lo arrancaste). Doble clic desde la carpeta.
title Triatlon 2.0 - Detener servidor

echo ============================================================
echo   Triatlon 2.0 - deteniendo servidor
echo ============================================================
echo.

set "FOUND="
for /f "tokens=5" %%P in ('netstat -ano ^| findstr /r /c:"127.0.0.1:8000 .*LISTENING"') do (
    set "FOUND=1"
    echo Deteniendo el proceso %%P ...
    taskkill /PID %%P /F >nul 2>&1
    if errorlevel 1 (
        echo [ERROR] No se pudo detener el proceso %%P.
        echo Prueba a ejecutar este archivo como administrador.
    ) else (
        echo Listo.
    )
)

if not defined FOUND (
    echo No habia ningun servidor corriendo en el puerto 8000.
)

echo.
timeout /t 3 >nul
