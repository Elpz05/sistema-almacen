# Sistema de Almacén

Aplicación web en **PHP + MySQL** para controlar el inventario de un almacén: productos, empresas, empleados, entradas, salidas y reportes. Se ejecuta con **Docker** y cuenta con integración continua, liberación automática de versiones y *scripts* de despliegue y pruebas.

**Materia:** Gestión del Proceso de Desarrollo de Software
**Integrantes:** Vega Lugo Josué Alejandro · López Sánchez Eden

---

## Contenido

1. [Requisitos](#requisitos)
2. [Inicio rápido](#inicio-rápido)
3. [Comandos manuales con Docker](#comandos-manuales-con-docker)
4. [Variables de entorno](#variables-de-entorno)
5. [Base de datos](#base-de-datos)
6. [Pruebas](#pruebas)
7. [CI/CD y versiones](#cicd-y-versiones)
8. [Estructura del proyecto](#estructura-del-proyecto)
9. [Seguridad](#seguridad)
10. [Solución de problemas](#solución-de-problemas)

---

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) abierto y en estado *Engine running*.
- **Git Bash** (incluido con Git for Windows) para ejecutar los *scripts* `.sh`. En Linux o macOS sirve cualquier terminal.
- `curl` (viene con Git Bash).

No necesitas instalar PHP ni MySQL: todo corre en contenedores.

## Inicio rápido

Desde la raíz del proyecto, en **Git Bash**:

```bash
# 1. Dar permisos y limpiar saltos de línea (solo la primera vez)
sed -i 's/\r$//' scripts/*.sh
chmod +x scripts/*.sh

# 2. Generar el archivo .env (pregunta versión, puerto, base y usuario)
./scripts/setup-release-env.sh

# 3. Descargar la imagen, levantar los servicios y esperar a que responda
./scripts/deploy.sh

# 4. (Opcional) Verificar el despliegue
./scripts/test-release.sh
```

Después abre **http://localhost:8080** (o el puerto que hayas indicado en `APP_PORT`).

Para desplegar una versión específica:

```bash
./scripts/deploy.sh v1.0.0
```

Para apagar sin borrar los datos:

```bash
docker compose -f docker-compose.release.yml down
```

> Con `down -v` se borra también el volumen de la base de datos. Al volver a levantar, la base se recarga desde `database/init.sql`.

## Comandos manuales con Docker

Si prefieres no usar los *scripts*:

```bash
# Crear .env a partir de la plantilla y editarlo
cp .env.example .env

# Levantar (descarga la imagen publicada en ghcr.io)
docker compose -f docker-compose.release.yml up -d

# Estado y registros
docker compose -f docker-compose.release.yml ps
docker compose -f docker-compose.release.yml logs app --tail 20
docker compose -f docker-compose.release.yml logs db --tail 20
```

**Construir la imagen localmente** (por ejemplo, para probar cambios sin publicar):

```bash
docker build -f docker/Dockerfile -t ghcr.io/elpz05/sistema-almacen:latest .
docker compose -f docker-compose.release.yml up -d --pull never
```

Si tu `.env` usa otra versión (`APP_VERSION=v1.0.0`), ponle esa etiqueta a la imagen:

```bash
docker tag ghcr.io/elpz05/sistema-almacen:latest ghcr.io/elpz05/sistema-almacen:v1.0.0
```

## Variables de entorno

Se definen en el archivo `.env` (plantilla en `.env.example`). **No se sube al repositorio.**

| Variable | Descripción | Ejemplo |
|---|---|---|
| `APP_VERSION` | Etiqueta de la imagen a desplegar | `latest`, `v1.0.0` |
| `APP_PORT` | Puerto local de la aplicación | `8080` |
| `DB_NAME` | Nombre de la base de datos | `almacen` |
| `DB_USER` | Usuario de la aplicación en MySQL | `almacen_user` |
| `DB_PASS` | Contraseña de ese usuario | *(secreta)* |
| `DB_ROOT_PASS` | Contraseña de `root` de MySQL | *(secreta)* |

`db.php` lee `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS` del entorno del contenedor, por lo que no hay credenciales escritas en el código.

> Las contraseñas se guardan en el volumen al crearlo. Si cambias `DB_PASS` o `DB_ROOT_PASS` en un `.env` existente, ejecuta `down -v` antes de volver a levantar; de lo contrario verás errores `Access denied`.

## Base de datos

- Motor: **MySQL 8.0** en el contenedor `db`, con *healthcheck* y volumen persistente `db-data`.
- La estructura y los datos iniciales están en `database/init.sql` y se cargan **automáticamente solo cuando el volumen es nuevo**.
- Tablas: `empleados`, `empresas`, `productos` y `movimientos`.

Revisar las tablas:

```bash
set -a; source .env; set +a
docker compose -f docker-compose.release.yml exec db mysql -uroot -p"$DB_ROOT_PASS" "$DB_NAME" -e "SHOW TABLES;"
```

Restablecer la contraseña de un usuario de la aplicación (las contraseñas se guardan con *bcrypt*):

```bash
HASH=$(docker compose -f docker-compose.release.yml exec -T app php -r "echo password_hash('nueva_clave', PASSWORD_BCRYPT);")
docker compose -f docker-compose.release.yml exec -T db mysql -uroot -p"$DB_ROOT_PASS" "$DB_NAME" -e "UPDATE empleados SET password='$HASH' WHERE id=1;"
```

## Pruebas

### Pruebas unitarias (PHPUnit)

La regla de negocio de entradas y salidas está en `funciones_stock.php` (función `calcular_stock`) y se prueba en `tests/StockTest.php`: 8 casos entre entradas, salidas y datos inválidos.

Sin instalar PHP (con Docker), desde PowerShell:

```powershell
docker run --rm -v "${PWD}:/app" -w /app composer:2 sh -c "composer install && vendor/bin/phpunit tests"
```

Resultado esperado: `OK (8 tests, 8 assertions)`.

### Pruebas del despliegue

```bash
./scripts/test-release.sh
```

Verifica requisitos, variables de entorno, contenedores, salud de la base de datos, tablas, PHP y sus extensiones, sintaxis de todos los archivos PHP, respuestas HTTP (páginas públicas y protegidas) y errores en los registros. Devuelve código `0` si todo pasa y `1` si alguna prueba falla.

> En Windows puede aparecer el aviso `Permisos de .env son 644`: el sistema de archivos no aplica los permisos de Unix. No es un fallo.

## CI/CD y versiones

Los flujos están en `.github/workflows/`:

| Flujo | Se dispara con | Qué hace |
|---|---|---|
| `ci.yml` | `push` y `pull request` a `Main` | Revisa la sintaxis PHP (8.1, 8.2 y 8.3), revisa `bot.js` (Node 20) y ejecuta PHPUnit |
| `release.yml` | *tag* `vX.Y.Z` | Revisa la sintaxis, empaqueta un `.zip`, construye y publica la imagen en `ghcr.io/elpz05/sistema-almacen` (con la versión y `latest`) y crea un *Release* en GitHub |

**Publicar una versión nueva:**

```bash
git tag v1.0.1
git push origin v1.0.1
```

**Rollback:** despliega la versión estable anterior.

```bash
./scripts/deploy.sh v1.0.0
```

> Para descargar la imagen sin iniciar sesión, el paquete en GitHub (*Packages → sistema-almacen → Package settings*) debe tener visibilidad **Public**. Si es privado, usa `docker login ghcr.io`.

## Estructura del proyecto

```
sistema-almacen/
├── .github/workflows/        # ci.yml y release.yml
├── css/                      # Hojas de estilo
├── database/
│   └── init.sql              # Estructura y datos iniciales de MySQL
├── docker/
│   └── Dockerfile            # Imagen PHP 8.2 + Apache (mysqli, pdo_mysql, mbstring, zip)
├── scripts/
│   ├── setup-release-env.sh  # Genera el .env
│   ├── deploy.sh             # Respaldo, descarga, despliegue y verificación
│   └── test-release.sh       # Pruebas del despliegue
├── tests/
│   └── StockTest.php         # Pruebas unitarias (PHPUnit)
├── acciones_*.php            # Procesan las operaciones de cada módulo
├── dashboard.php, productos.php, empleados.php,
│   empresas.php, entradas.php, salidas.php, reportes.php
├── auth.php                  # Control de sesión
├── db.php                    # Conexión a MySQL (lee variables de entorno)
├── funciones_stock.php       # Lógica de entradas y salidas
├── bot.js                    # Componente en JavaScript
├── docker-compose.release.yml
├── composer.json             # PHPUnit como dependencia de desarrollo
└── .env.example              # Plantilla de variables de entorno
```

## Seguridad

- **Nunca subas** el `.env`, los respaldos (`backups/`) ni exportaciones SQL con datos reales. Ya están en `.gitignore`.
- Si una contraseña real llegó al historial de Git o a un chat, **cámbiala** en el servicio donde se usaba; borrar el archivo después no la elimina del historial.
- `.dockerignore` evita que `.env`, la base de datos, los *scripts* y las pruebas entren a la imagen.
- `database/init.sql` es solo para desarrollo y demostración: no lo uses con datos reales en un repositorio público.

## Solución de problemas

| Síntoma | Causa probable | Solución |
|---|---|---|
| `error from registry: denied` | La imagen no existe en `ghcr.io` o el paquete es privado | Publica un *tag*, haz público el paquete o construye la imagen localmente |
| `No such image: ...:v1.0.0` | `APP_VERSION` apunta a una etiqueta que no existe localmente | Usa `docker tag` o cambia `APP_VERSION` |
| `db` queda `unhealthy` con *password option is not specified* | Falta `DB_ROOT_PASS` en el `.env` | Complétalo y ejecuta `down -v` |
| `Access denied for user` | Contraseña del `.env` distinta a la del volumen existente | `down -v` y volver a levantar |
| `SHOW TABLES` vacío | `init.sql` no se montó o el volumen ya existía | Verifica el volumen en el compose y ejecuta `down -v` |
| `port is already allocated` | El puerto está ocupado (por ejemplo, otro contenedor) | Cambia `APP_PORT` en el `.env` |
| `\r: command not found` en un `.sh` | Saltos de línea de Windows | `sed -i 's/\r$//' scripts/*.sh` |
| Los `.sh` se abren con otro programa en Windows | Se ejecutaron desde PowerShell o el Explorador | Usa **Git Bash** |
