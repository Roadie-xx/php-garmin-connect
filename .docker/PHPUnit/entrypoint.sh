#!/bin/sh
set -e

if [ "$1" = 'zsh' ] || [ "$1" = 'sh' ] || [ "$1" = 'shell' ] || [ "$1" = 'bash' ]; then
    exec /bin/zsh
    exit
fi

if [ "$1" = 'init' ]; then
    cp /data/phpunit/bootstrap.php /app/tests/
    cp /data/phpunit/phpunit.xml /app/
    echo Done.
    exit
fi

exec phpunit "$@"