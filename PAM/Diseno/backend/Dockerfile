# API + worker del Sistema PAM
FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 PIP_NO_CACHE_DIR=1 \
    MIGRATIONS_DIR=/app/db/migrations IMPORT_DIR=/data/importaciones EXPORT_DIR=/data/exportaciones

RUN apt-get update && apt-get install -y --no-install-recommends postgresql-client tzdata && rm -rf /var/lib/apt/lists/* \
 && useradd --create-home --uid 10001 pam

WORKDIR /app
COPY backend/requirements.txt .
RUN pip install -r requirements.txt
COPY backend/pam ./pam
COPY db/migrations ./db/migrations
COPY deploy/docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh && mkdir -p /data/importaciones /data/exportaciones && chown -R pam:pam /data

USER pam
EXPOSE 8000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD python -c "import urllib.request;urllib.request.urlopen('http://127.0.0.1:8000/api/salud',timeout=4)" || exit 1
ENTRYPOINT ["/entrypoint.sh"]
CMD ["api"]
