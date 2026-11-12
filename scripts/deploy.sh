#!/usr/bin/env bash
# Uso: ./deploy.sh [versión]
# Ejemplos:
#   ./deploy.sh            -> usa la versión definida en .env (o latest)
#   ./deploy.sh v1.0.1     -> despliega esa versión

set -euo pipefail

COMPOSE_FILE="docker-compose.release.yml"
ENV_FILE=".env"
VERSION="${1:-}"

# Colores para los mensajes
VERDE='\033[0;32m'
AMARILLO='\033[1;33m'
ROJO='\033[0;31m'
NC='\033[0m'

info()  { echo -e "${VERDE}[INFO]${NC} $1"; }
aviso() { echo -e "${AMARILLO}[AVISO]${NC} $1"; }
error() { echo -e "${ROJO}[ERROR]${NC} $1" >&2; exit 1; }

# 1. Verificar requisitos
command -v docker >/dev/null 2>&1 || error "Docker no está instalado."
docker compose version >/dev/null 2>&1 || error "Docker Compose (plugin v2) no está disponible."

[ -f "$COMPOSE_FILE" ] || error "No se encontró $COMPOSE_FILE en $(pwd)."
[ -f "$ENV_FILE" ] || error "No se encontró $ENV_FILE. Crea uno con tus variables."

# 2. Si se indicó versión, actualizar APP_VERSION en .env
if [ -n "$VERSION" ]; then
  info "Versión solicitada: $VERSION"
  if grep -q '^APP_VERSION=' "$ENV_FILE"; then
    sed -i.bak "s|^APP_VERSION=.*|APP_VERSION=${VERSION}|" "$ENV_FILE" && rm -f "${ENV_FILE}.bak"
  else
    echo "APP_VERSION=${VERSION}" >> "$ENV_FILE"
  fi
fi

# 3. Respaldo de la base de datos (si el contenedor ya está corriendo)
if docker ps --format '{{.Names}}' | grep -q '^sistema-almacen-db$'; then
  mkdir -p backups
  RESPALDO="backups/respaldo_$(date +%Y%m%d_%H%M%S).sql"
  info "Creando respaldo de la base de datos en $RESPALDO"

  set -a; source "$ENV_FILE"; set +a
  if docker exec sistema-almacen-db \
      mysqldump -u root -p"${DB_ROOT_PASS}" "${DB_NAME}" > "$RESPALDO" 2>/dev/null; then
    info "Respaldo creado correctamente."
  else
    rm -f "$RESPALDO"
    aviso "No se pudo crear el respaldo. Continuando de todos modos."
  fi
else
  aviso "La base de datos no está corriendo, se omite el respaldo."
fi

# 4. Descargar imágenes nuevas
info "Descargando imágenes..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" pull

# 5. Levantar los servicios
info "Levantando servicios..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --remove-orphans

# 6. Verificar que la app responda
set -a; source "$ENV_FILE"; set +a
PUERTO="${APP_PORT:-8080}"
info "Esperando a que la aplicación responda en el puerto $PUERTO..."

for intento in $(seq 1 15); do
  if curl -fsS -o /dev/null "http://localhost:${PUERTO}/"; then
    info "La aplicación está funcionando en http://localhost:${PUERTO}"
    break
  fi
  if [ "$intento" -eq 15 ]; then
    aviso "La app no respondió a tiempo. Revisa los logs con:"
    echo "  docker compose -f $COMPOSE_FILE logs app"
    exit 1
  fi
  sleep 3
done

# 7. Limpiar imágenes antiguas sin uso
info "Limpiando imágenes antiguas..."
docker image prune -f >/dev/null

info "Despliegue completado."