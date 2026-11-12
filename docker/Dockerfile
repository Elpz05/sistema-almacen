FROM php:8.2-apache

# Extensiones de PHP para conectarse a MySQL y manejar texto
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
  libonig-dev \
  libzip-dev \
  unzip \
  && docker-php-ext-install \
  mysqli \
  pdo \
  pdo_mysql \
  mbstring \
  zip \
  && a2enmod rewrite headers \
  && rm -rf /var/lib/apt/lists/*

# Configuración recomendada de PHP para producción
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Directorio de trabajo (raíz web de Apache)
WORKDIR /var/www/html

# Copiar el sistema
COPY . /var/www/html/

# Permisos para Apache
RUN chown -R www-data:www-data /var/www/html \
  && find /var/www/html -type d -exec chmod 755 {} \; \
  && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80

CMD ["apache2-foreground"]