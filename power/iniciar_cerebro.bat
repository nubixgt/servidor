@echo off
REM Arranca SOLO el cerebro (PaginaLocal) en local, contra tus datos reales.
REM
REM No necesita MySQL, ni PHP, ni Node: nada de eso toca la IA. Sirve para
REM afinar prompts, modelos, el validador y el constructor de entrenos.
REM
REM Se ejecuta DESDE DENTRO de PaginaLocal a proposito: con `--app-dir` desde
REM la raiz, uvicorn importa la app pero no llega a escuchar en el puerto.
REM
REM El vigilante de --reload mira solo el codigo. Apuntandolo a la raiz se
REM queda colgado indefinidamente recorriendo Frontend/node_modules (8651
REM ficheros).

cd /d "%~dp0PaginaLocal"

echo.
echo   Cerebro local  ->  http://127.0.0.1:8010
echo   Datos          ->  PaginaLocal\data\storage
echo   Para detenerlo ->  Ctrl+C
echo.

python -m uvicorn web_app.server:app ^
    --host 127.0.0.1 --port 8010 ^
    --reload --reload-dir web_app --reload-dir services --reload-dir core

pause
