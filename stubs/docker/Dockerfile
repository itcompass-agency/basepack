FROM php:8.3-fpm

# set main params
ARG BUILD_ARGUMENT_ENV=prod
ENV ENV=$BUILD_ARGUMENT_ENV
ENV APP_HOME=/var/www/html
ARG HOST_UID=1000
ARG HOST_GID=1000
ENV USERNAME=www-data
ARG INSIDE_DOCKER_CONTAINER=1
ENV INSIDE_DOCKER_CONTAINER=$INSIDE_DOCKER_CONTAINER
ARG XDEBUG_CONFIG=main
ENV XDEBUG_CONFIG=$XDEBUG_CONFIG

# check environment
RUN if [ "$BUILD_ARGUMENT_ENV" = "default" ]; then echo "Set BUILD_ARGUMENT_ENV in docker build-args like --build-arg BUILD_ARGUMENT_ENV=dev" && exit 2; \
    elif [ "$BUILD_ARGUMENT_ENV" = "dev" ]; then echo "Building development environment."; \
    elif [ "$BUILD_ARGUMENT_ENV" = "prod" ]; then echo "Building production environment."; \
    else echo "Set correct BUILD_ARGUMENT_ENV in docker build-args like --build-arg BUILD_ARGUMENT_ENV=dev. Available choices are dev,test,prod." && exit 2; \
    fi

# node install
RUN set -uex; \
    apt-get update; \
    apt-get install -y ca-certificates curl gnupg; \
    mkdir -p /etc/apt/keyrings; \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
     | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg; \
    NODE_MAJOR=20; \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" \
     > /etc/apt/sources.list.d/nodesource.list; \
    apt-get -qy update; \
    apt-get -qy install nodejs;

# Create .npm directory and fix permissions
RUN mkdir -p /var/www/.npm && chown -R www-data:www-data /var/www/.npm

# install all the dependencies and enable PHP modules
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
      procps \
      nano \
      git \
      unzip \
      libicu-dev \
      zlib1g-dev \
      libxml2 \
      libxml2-dev \
      libreadline-dev \
      supervisor \
      cron \
      sudo \
      libzip-dev \
      libgmp-dev \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libwebp-dev \
      libpq-dev \
      imagemagick \
      libmagickwand-dev \
      ghostscript \
    && docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
      pdo_mysql \
      -j$(nproc) gd \
      sockets \
      intl \
      opcache \
      zip \
      gmp \
      bcmath \
    && rm -rf /tmp/* \
    && rm -rf /var/list/apt/* \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# redis installation
RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/5.3.4.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

# create document root, fix permissions for www-data user and change owner to www-data
# RUN mkdir -p $APP_HOME/public && \
#     mkdir -p /home/$USERNAME && chown $USERNAME:$USERNAME /home/$USERNAME \
#     && usermod -o -u $HOST_UID $USERNAME -d /home/$USERNAME \
#     && groupmod -o -g $HOST_GID $USERNAME \
#     && chown -R ${USERNAME}:${USERNAME} $APP_HOME

# put php config for Laravel
COPY ./.docker/$BUILD_ARGUMENT_ENV/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./.docker/$BUILD_ARGUMENT_ENV/php.ini /usr/local/etc/php/php.ini

# install Xdebug in case dev/test environment
COPY ./.docker/general/do_we_need_xdebug.sh /tmp/
COPY ./.docker/dev/xdebug-${XDEBUG_CONFIG}.ini /tmp/xdebug.ini
RUN chmod u+x /tmp/do_we_need_xdebug.sh && /tmp/do_we_need_xdebug.sh

# install imagick manually
RUN git clone https://github.com/Imagick/imagick.git /usr/src/imagick \
    && cd /usr/src/imagick \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && docker-php-ext-enable imagick \
    && rm -rf /usr/src/imagick

RUN sed -i 's@<policy domain="coder" rights="none" pattern="PDF" />@<policy domain="coder" rights="read|write" pattern="PDF" />@' /etc/ImageMagick-6/policy.xml || true
RUN sed -i 's@<policy domain="coder" rights="none" pattern="PS" />@<policy domain="coder" rights="read|write" pattern="PS" />@' /etc/ImageMagick-6/policy.xml || true

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN chmod +x /usr/bin/composer
RUN mkdir -p /var/www/.composer/cache && \
    chown -R www-data:www-data /var/www/.composer && \
    chmod -R 775 /var/www/.composer/cache
ENV COMPOSER_ALLOW_SUPERUSER=1

# add supervisor
RUN mkdir -p /var/log/supervisor
COPY --chown=root:root ./.docker/general/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=root:crontab ./.docker/general/cron /var/spool/cron/crontabs/root
RUN chmod 0600 /var/spool/cron/crontabs/root

# set working directory
WORKDIR $APP_HOME

USER ${USERNAME}

# copy source files and config file
COPY --chown=${USERNAME}:${USERNAME} . $APP_HOME/
COPY --chown=${USERNAME}:${USERNAME} .env $APP_HOME/.env

# install all PHP dependencies
RUN if [ "$BUILD_ARGUMENT_ENV" = "dev" ]; then COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-interaction --no-progress; \
    else COMPOSER_MEMORY_LIMIT=-1 composer install --optimize-autoloader --no-interaction --no-progress --no-dev; \
    fi

USER root