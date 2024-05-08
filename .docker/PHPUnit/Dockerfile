# BUILD from project root:
# docker build --force-rm --tag "roadie/phpunit" --file .docker/phpunit/Dockerfile .

# RUN:
# docker run -it --rm --volume ${pwd}:/app --name="phpunit" "roadie/phpunit" run
# Microsoft.PowerShell.Core/FileSystem:://192.168.0.111/web/timesavers/docker/phpunit:/app

# RUN zsh:
# docker run -it --rm --volume ${pwd}:/app --name="phpunit" "roadie/phpunit" zsh

# docker exec -it "roadie/phpunit" bash

FROM php:8.1-fpm-alpine

LABEL maintainer="Robby van Zweeden"

# ZSH wit Oh my zsh
RUN apk update \
    && apk add --no-cache zsh zsh-autosuggestions zsh-syntax-highlighting git\
    && rm -rf /var/cache/apk/*

RUN sh -c "$(wget https://raw.github.com/robbyrussell/oh-my-zsh/master/tools/install.sh -O -)"
RUN echo "source /usr/share/zsh/plugins/zsh-syntax-highlighting/zsh-syntax-highlighting.zsh" >> ~/.zshrc && \
    echo "source /usr/share/zsh/plugins/zsh-autosuggestions/zsh-autosuggestions.zsh" >> ~/.zshrc

COPY .docker/PHPUnit/entrypoint.sh /bin/
RUN chmod +x /bin/entrypoint.sh

# Set up the application directory.
WORKDIR /app

# Install and enable apcu
RUN apk add --update --no-cache --virtual .build-dependencies $PHPIZE_DEPS \
        && pecl install apcu \
        && docker-php-ext-enable apcu \
        && pecl clear-cache \
        && apk del .build-dependencies

# Add xDebug: Needed for coverage
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug-3.1.5 \
    && docker-php-ext-enable xdebug \
    && apk del -f .build-deps

# Configure Xdebug
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/xdebug.ini

# Additional tools
ADD https://getcomposer.org/composer.phar /usr/local/bin/composer

# Add path to composed tools
ENV PATH /root/.composer/vendor/bin:$PATH

# Make the tools executable and install the tools
RUN chmod +x /usr/local/bin/composer \
    && /usr/local/bin/composer global require 'symfony/test-pack' \
    && /usr/local/bin/composer global require 'phpunit/php-code-coverage'

# Copy helper files
RUN mkdir -p /data/phpunit
COPY .docker/PHPUnit/bootstrap.php /data/phpunit/
COPY .docker/PHPUnit/phpunit.xml /data/phpunit/

ENTRYPOINT ["entrypoint.sh"]
# CMD [""]

# docker run -it --rm --volume ${pwd}:/app --name="phpunit" "roadie/phpunit" init
# docker run -it --rm --volume ${pwd}:/app --name="phpunit" "roadie/phpunit" zsh
#   > composer init
#        Package name: roadie/php-garmin-connect
#        Description []: Client connector to the Garmin Connect API in php
#        Author [n to skip]: Roadie
#   > phpunit
# docker run -it --rm --volume ${pwd}:/app --name="phpunit" "roadie/phpunit" tests/RamerDouglasPeuckerTest.php
