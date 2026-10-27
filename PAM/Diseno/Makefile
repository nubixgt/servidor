# Atajos de operación (requieren Docker Compose v2)
.PHONY: arrancar detener estado logs admin importar validar refrescar respaldo pruebas

arrancar:            ## Construye y levanta todos los servicios
	docker compose up -d --build
detener:
	docker compose stop
estado:
	docker compose ps
logs:
	docker compose logs -f --tail=200 api worker
admin:               ## make admin USUARIO=admin
	docker compose exec api python -m pam.cli crear-admin --usuario $(or $(USUARIO),admin)
importar:            ## make importar ARCHIVO="BD PAM.csv"
	docker compose cp "$(ARCHIVO)" api:/data/importaciones/
	docker compose exec api python -m pam.cli importar "/data/importaciones/$(notdir $(ARCHIVO))"
validar:             ## make validar ARCHIVO="BD PAM.csv"  (no modifica la base)
	docker compose cp "$(ARCHIVO)" api:/data/importaciones/
	docker compose exec api python -m pam.cli importar "/data/importaciones/$(notdir $(ARCHIVO))" --validar
refrescar:
	docker compose exec api python -m pam.cli refrescar
respaldo:
	docker compose exec respaldo sh /respaldo.sh ahora
pruebas:             ## pruebas de integración contra PostgreSQL local
	cd backend && PYTHONPATH=.:tests python -m pytest -q tests
