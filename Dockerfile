FROM php:7.4-cli

COPY sources.list /etc/apt/sources.list

RUN set -eux \
    && apt-get update \ 
    && apt-get install -y --no-install-recommends \
    curl zip unzip wget \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev

RUN docker-php-ext-install -j$(nproc) iconv bcmath pdo_mysql

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN pecl install redis swoole \
    && docker-php-ext-enable redis swoole

RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/bin/composer

RUN composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/

#remove cache
RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

WORKDIR /easyswoole

CMD ["php", "easyswoole", "start"]

EXPOSE 9501
