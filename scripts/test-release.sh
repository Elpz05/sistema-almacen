#!/usr/bin/env bash
# Uso: ./test-release.sh
# Verifica que el despliegue con docker-compose.release.yml funcione correctamente.
# Devuelve código 0 si todo pasa y 1 si alguna prueba falla.

set -uo pipefail

COMPOSE_FILE="docker-compose.release.yml"
ENV_FILE=".env"
APP_CONTAINER="sistema-almacen-app"
DB_CONTAINER="sistema-almacen-db"

# Colores para los mensajes
VERDE='\033[0;32m'
AMARILLO='\033[1;33m'
ROJO='\033[0;31m'
NC='\033[0m'

PASARON=0
FALLARON=0
AVISOS=0

ok()    { echo -e "  ${VERDE}[OK]${NC}    $1"; PASARON=$((PASARON + 1)); }
fallo() { echo -e "  ${ROJO}[FALLO]${NC} $1"; FALLARON=$((FALLARON + 1)); }
aviso() { echo -e "  ${AMARILLO}[AVISO]${NC} $1"; AVISOS=$((AVISOS + 1)); }
titulo() { echo; echo "== $1 =="; }

# Devuelve el código HTTP de una URL (000 si no hay respuesta)
codigo_http() {
  curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$1" || echo "000"
}

echo "=== Pruebas del despliegue de Sistema de Almacén ==="

# 1. Requisitos
titulo "Requisitos"

if command -v docker >/dev/null 2>&1; then
  ok "Docker está instalado"
else
  fallo "Docker no está instalado"
  echo; echo "No se puede continuar sin Docker."; exit 1
fi

if docker compose version >/dev/null 2>&1; then
  ok "Docker Compose está disponible"
else
  fallo "Docker Compose (plugin v2) no está disponible"
  exit 1
fi

if command -v curl >/dev/null 2>&1; then
  ok "curl está instalado"
else
  fallo "curl no está instalado"
  exit 1
fi

# 2. Archivos de configuración
titulo "Archivos de configuración"

[ -f "$COMPOSE_FILE" ] && ok "Existe $COMPOSE_FILE" || { fallo "Falta $COMPOSE_FILE"; exit 1; }
[ -f "$ENV_FILE" ] && ok "Existe $ENV_FILE" || { fallo "Falta $ENV_FILE (ejecuta ./setup-release-env.sh)"; exit 1; }

set -a; source "$ENV_FILE"; set +a

for variable in APP_VERSION APP_PORT DB_NAME DB_USER DB_PASS DB_ROOT_PASS; do
  if [ -n "${!variable:-}" ]; then
    ok "Variable $variable definida"
  else
    fallo "Variable $variable vacía o ausente en $ENV_FILE"
  fi
done

PERMISOS=$(stat -c '%a' "$ENV_FILE" 2>/dev/null || stat -f '%Lp' "$ENV_FILE" 2>/dev/null || echo "?")
if [ "$PERMISOS" = "600" ]; then
  ok "Permisos de $ENV_FILE correctos (600)"
else
  aviso "Permisos de $ENV_FILE son $PERMISOS (se recomienda 600)"
fi

if docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" config -q 2>/dev/null; then
  ok "El archivo $COMPOSE_FILE es válido"
else
  fallo "El archivo $COMPOSE_FILE tiene errores de configuración"
fi

PUERTO="${APP_PORT:-8080}"
URL="http://localhost:${PUERTO}"

# 3. Contenedores
titulo "Contenedores"

for contenedor in "$APP_CONTAINER" "$DB_CONTAINER"; do
  ESTADO=$(docker inspect -f '{{.State.Status}}' "$contenedor" 2>/dev/null || echo "no existe")
  if [ "$ESTADO" = "running" ]; then
    ok "Contenedor $contenedor está corriendo"
  else
    fallo "Contenedor $contenedor está en estado: $ESTADO"
  fi
done

SALUD=$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}sin-healthcheck{{end}}' "$DB_CONTAINER" 2>/dev/null || echo "desconocido")
if [ "$SALUD" = "healthy" ]; then
  ok "La base de datos reporta estado healthy"
else
  fallo "La base de datos reporta estado: $SALUD"
fi

REINICIOS=$(docker inspect -f '{{.RestartCount}}' "$APP_CONTAINER" 2>/dev/null || echo "0")
if [ "$REINICIOS" -eq 0 ] 2>/dev/null; then
  ok "La aplicación no se ha reiniciado"
else
  aviso "La aplicación se ha reiniciado $REINICIOS veces (revisa los logs)"
fi

# 4. Base de datos
titulo "Base de datos"

if docker exec "$DB_CONTAINER" mysqladmin ping -h localhost -u root -p"${DB_ROOT_PASS}" --silent >/dev/null 2>&1; then
  ok "MySQL responde al ping"
else
  fallo "MySQL no responde al ping"
fi

if docker exec "$DB_CONTAINER" mysql -u "${DB_USER}" -p"${DB_PASS}" -e "USE \`${DB_NAME}\`;" >/dev/null 2>&1; then
  ok "El usuario ${DB_USER} puede acceder a la base ${DB_NAME}"
else
  fallo "El usuario ${DB_USER} no puede acceder a la base ${DB_NAME}"
fi

TABLAS=$(docker exec "$DB_CONTAINER" mysql -N -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null || echo "0")
if [ "${TABLAS:-0}" -gt 0 ] 2>/dev/null; then
  ok "La base de datos tiene $TABLAS tablas"
else
  aviso "La base de datos no tiene tablas (¿cargaste database.sql?)"
fi

# 5. PHP dentro del contenedor
titulo "PHP en la aplicación"

VERSION_PHP=$(docker exec "$APP_CONTAINER" php -r 'echo PHP_VERSION;' 2>/dev/null || echo "")
if [ -n "$VERSION_PHP" ]; then
  ok "PHP $VERSION_PHP funcionando"
else
  fallo "No se pudo ejecutar PHP en el contenedor"
fi

for extension in mysqli pdo_mysql mbstring zip; do
  if docker exec "$APP_CONTAINER" php -m 2>/dev/null | grep -qix "$extension"; then
    ok "Extensión $extension cargada"
  else
    fallo "Falta la extensión $extension"
  fi
done

if docker exec "$APP_CONTAINER" sh -c \
  'find /var/www/html -name "*.php" -print0 | xargs -0 -n1 php -l >/dev/null 2>/tmp/php_errores.txt'; then
  ok "Todos los archivos PHP tienen sintaxis válida"
else
  fallo "Hay archivos PHP con errores de sintaxis"
fi

# 6. Respuesta HTTP
titulo "Respuesta HTTP ($URL)"

CODIGO=$(codigo_http "$URL/")
case "$CODIGO" in
  200|301|302) ok "Página principal responde ($CODIGO)" ;;
  *)           fallo "Página principal devolvió el código $CODIGO" ;;
esac

# Páginas públicas que deben existir (200 o redirección)
for pagina in index.html login_process.php recover_method.php; do
  CODIGO=$(codigo_http "$URL/$pagina")
  case "$CODIGO" in
    200|301|302|405) ok "/$pagina responde ($CODIGO)" ;;
    *)               fallo "/$pagina devolvió el código $CODIGO" ;;
  esac
done

# Páginas protegidas: sin sesión deben redirigir al login (no mostrar contenido)
for pagina in dashboard.php productos.php empleados.php; do
  CODIGO=$(codigo_http "$URL/$pagina")
  case "$CODIGO" in
    301|302|401|403) ok "/$pagina está protegida ($CODIGO)" ;;
    200)             aviso "/$pagina responde 200 sin sesión (verifica que auth.php la proteja)" ;;
    *)               fallo "/$pagina devolvió el código $CODIGO" ;;
  esac
done

CODIGO=$(codigo_http "$URL/pagina-que-no-existe-12345.php")
if [ "$CODIGO" = "404" ]; then
  ok "Las páginas inexistentes devuelven 404"
else
  aviso "Una página inexistente devolvió $CODIGO (se esperaba 404)"
fi

# 7. Errores en los logs
titulo "Logs"

ERRORES_APP=$(docker logs --tail 200 "$APP_CONTAINER" 2>&1 | grep -ciE 'fatal|segfault|cannot connect' || true)
if [ "${ERRORES_APP:-0}" -eq 0 ]; then
  ok "Sin errores graves en los logs de la aplicación"
else
  aviso "Hay $ERRORES_APP líneas con errores en los logs (docker logs $APP_CONTAINER)"
fi

# Resumen
echo
echo "=============================================="
echo -e " Resultado: ${VERDE}${PASARON} correctas${NC}, ${ROJO}${FALLARON} fallidas${NC}, ${AMARILLO}${AVISOS} avisos${NC}"
echo "=============================================="

if [ "$FALLARON" -gt 0 ]; then
  exit 1
fi
exit 0