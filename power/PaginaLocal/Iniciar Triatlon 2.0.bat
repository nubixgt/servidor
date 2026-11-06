@echo off
REM Arranca la app y abre el navegador. Doble clic desde la carpeta.
title Triatlon 2.0 - Servidor
cd /d "%~dp0"

echo ============================================================
echo   Triatlon 2.0 - iniciando servidor
echo ============================================================
echo.

REM Buscar un Python utilizable: primero el lanzador "py", luego "python".
where py >nul 2>&1
if %errorlevel%==0 (
    set "PYEXE=py"
) else (
    where python >nul 2>&1
    if %errorlevel%==0 (
        set "PYEXE=python"
    ) else (
        echo [ERROR] No se encontro Python en este equipo.
        echo Instalalo desde https://www.python.org/downloads/ y vuelve a intentarlo.
        echo.
        pause
        exit /b 1
    )
)

REM Si el puerto 8000 ya esta ocupado, la app anterior sigue viva.
netstat -ano | findstr /r /c:"127.0.0.1:8000 .*LISTENING" >nul 2>&1
if %errorlevel%==0 (
    echo [AVISO] Ya hay algo corriendo en el puerto 8000.
    echo Cierra la app anterior con "Detener Triatlon 2.0.bat" y vuelve a intentarlo.
    echo.
    pause
    exit /b 1
)

echo Servidor arrancando en http://127.0.0.1:8000
echo El navegador se abrira solo en unos segundos.
echo.
echo Para apagarlo: el boton "Apagar servidor" en la app,
echo o cierra esta ventana, o pulsa Ctrl+C aqui.
echo.

%PYEXE% run_app.py

echo.
echo El servidor se detuvo.
pause
