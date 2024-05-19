FROM debian

# Actualizamos el sistema e instalamos herramientas necesarias

RUN apt-get update && \
apt-get install -y \
curl \
git \
unzip \
gnupg \
lsb-release \
&& apt-get clean

# Agregamos el repositorio de paquetes de PHP

RUN curl -fsSL https://packages.sury.org/php/apt.gpg | apt-key add - && \
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee /etc/apt/sources.list.d/php.list && \
apt-get update

# Instalamos PHP y las extensiones necesarias

RUN apt-get install -y \
php7.4 \
php7.4-cli \
php7.4-common \
php7.4-json \
php7.4-opcache \
php7.4-pgsql \
libapache2-mod-php7.4 \
&& a2enmod rewrite

# Limpiamos el sistema de paquetes

RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copiamos el código fuente de la aplicación al directorio /var/www/html del contenedor

COPY . /var/www/html/
WORKDIR /var/www/html/

# Instalamos Composer

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiamos el archivo de configuración del virtual host de Apache

COPY vh.conf /etc/apache2/sites-available/000-default.conf

# Habilitamos el sitio de Apache para nuestra aplicación

RUN a2ensite 000-default.conf


# Instalamos las dependencias de la aplicación usando Composer

RUN composer update --ignore-platform-req=ext-dom --ignore-platform-req=ext-xml --ignore-platform-req=ext-xmlwriter --ignore-platform-req=ext-curl

# Cambiamos los permisos del directorio de la aplicación

RUN chown -R www-data:www-data /var/www/html

# Exponemos el puerto 80 para que la aplicación sea accesible desde el exterior

EXPOSE 80

# Comando para iniciar el servidor web Apache al iniciar el contenedor

CMD ["apache2ctl","-DFOREGROUND"]
