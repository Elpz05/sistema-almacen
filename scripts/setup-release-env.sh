#!/usr/bin/env bash
# Uso: ./setup-release-env.sh
# Genera el archivo .env para el despliegue con docker-compose.release.yml

set -euo pipefail

ENV_FILE=".env"

# Colores para los mensajes
VERDE='\033[0;32m'
AMARILLO='\033[1;33m'
ROJO='\033[0;31m'
NC='\033[0m'

info()  { echo -e "${VERDE}[INFO]${NC} $1"; }
aviso() { echo -e "${AMARILLO}[AVISO]${NC} $1"; }
error() { echo -e "${ROJO}[ERROR]${NC} $1" >&2; exit 1; }

# Genera una contraseña aleatoria de 24 caracteres (solo letras y números)
generar_password() {
  if command -v openssl >/dev/null 2>&1; then
    openssl rand -base64 48 | tr -dc 'A-Za-z0-9' | head -c 24
  else
    tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24
  fi
}

# Pregunta un valor con una opción por defecto
preguntar() {
  local mensaje="$1"
  local defecto="$2"
  local respuesta
  read -r -p "$mensaje [$defecto]: " respuesta
  echo "${respuesta:-$defecto}"
}

echo "=== Configuración del entorno de Sistema de Almacén ==="
echo

# 1. Si ya existe un .env, preguntar antes de sobrescribirlo
if [ -f "$ENV_FILE" ]; then
  aviso "Ya existe un archivo $ENV_FILE."
  read -r -p "¿Quieres sobrescribirlo? Se guardará una copia de respaldo (s/N): " confirmar
  case "$confirmar" in
    s|S|si|SI|sí|Sí)
      COPIA="${ENV_FILE}.bak.$(date +%Y%m%d_%H%M%S)"
      cp "$ENV_FILE" "$COPIA"
      info "Copia de respaldo guardada en $COPIA"
      ;;
    *)
      info "No se hicieron cambios."
      exit 0
      ;;
  esac
fi

# 2. Pedir los valores
APP_VERSION=$(preguntar "Versión de la aplicación" "latest")
APP_PORT=$(preguntar "Puerto de la aplicación" "8080")
DB_NAME=$(preguntar "Nombre de la base de datos" "almacen")
DB_USER=$(preguntar "Usuario de la base de datos" "almacen_user")

# Validar que el puerto sea un número válido
if ! [[ "$APP_PORT" =~ ^[0-9]+$ ]] || [ "$APP_PORT" -lt 1 ] || [ "$APP_PORT" -gt 65535 ]; then
  error "El puerto '$APP_PORT' no es válido (debe estar entre 1 y 65535)."
fi

# 3. Contraseñas
read -r -p "¿Generar contraseñas aleatorias seguras? (S/n): " auto
case "$auto" in
  n|N|no|NO)
    read -r -s -p "Contraseña del usuario $DB_USER: " DB_PASS; echo
    read -r -s -p "Contraseña de root de MySQL: " DB_ROOT_PASS; echo
    [ -n "$DB_PASS" ] && [ -n "$DB_ROOT_PASS" ] || error "Las contraseñas no pueden estar vacías."
    ;;
  *)
    DB_PASS=$(generar_password)
    DB_ROOT_PASS=$(generar_password)
    info "Contraseñas generadas automáticamente."
    ;;
esac

# 4. Escribir el archivo .env
cat > "$ENV_FILE" <<EOF
# Generado por setup-release-env.sh el $(date '+%Y-%m-%d %H:%M:%S')
# NO subas este archivo a GitHub

APP_VERSION=${APP_VERSION}
APP_PORT=${APP_PORT}

DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_ROOT_PASS=${DB_ROOT_PASS}
EOF

# Solo el dueño puede leer y escribir el archivo
chmod 600 "$ENV_FILE"
info "Archivo $ENV_FILE creado con permisos 600."

# 5. Asegurar que .env y backups estén en .gitignore
if [ -d ".git" ] || [ -f ".gitignore" ]; then
  touch .gitignore
  for entrada in ".env" ".env.bak.*" "backups/"; do
    if ! grep -qxF "$entrada" .gitignore; then
      echo "$entrada" >> .gitignore
      info "Se agregó '$entrada' a .gitignore"
    fi
  done
fi

echo
info "Listo. Guarda estas contraseñas en un lugar seguro."
echo "  Siguiente paso: ./deploy.sh"