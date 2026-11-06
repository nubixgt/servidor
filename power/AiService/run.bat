@echo off
REM Arranca el AiService (el puente a PaginaLocal) en Windows.
REM
REM El equivalente de run.sh, que es de Linux y exige un `.venv/bin/uvicorn`
REM que en Windows no existe. Aqui se usa el Python del sistema, donde las
REM dependencias del cerebro ya estan instaladas.
REM
REM Necesita BRIDGE_SERVICE_TOKEN en el .env de la RAIZ del repo, con el mismo
REM valor que PYTHON_SERVICE_TOKEN del Backend. Sin el, el servicio arranca
REM pero responde 500 a todo.
REM
REM Se ejecuta DESDE DENTRO de AiService: `main.py` importa `bridge.paginalocal`
REM por ruta relativa, y desde la raiz del repo no lo encuentra.

cd /d "%~dp0"

if "%AISERVICE_PORT%"=="" set AISERVICE_PORT=8600

echo.
echo   AiService  -^>  http://127.0.0.1:%AISERVICE_PORT%
echo   Salud      -^>  http://127.0.0.1:%AISERVICE_PORT%/health
echo   Detener    -^>  Ctrl+C
echo.

python -m uvicorn main:app --host 127.0.0.1 --port %AISERVICE_PORT%

pause
