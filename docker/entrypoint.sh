#!/bin/sh
# Prepares the Laravel app before starting the development server.
# Every command runs as the owner of the project folder, so files created in the
# bind mount belong to the host user whatever their UID is.
set -e

if [ "$(id -u)" = "0" ]; then
    uid="$(stat -c %u /var/www/html)"
    gid="$(stat -c %g /var/www/html)"
    if [ "$uid" != "0" ]; then
        export HOME=/tmp
        exec setpriv --reuid="$uid" --regid="$gid" --clear-groups "$0" "$@"
    fi
fi

if [ "$1" = "php" ] && [ "$2" = "artisan" ] && [ "$3" = "serve" ]; then
    if [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist
    fi

    if [ ! -f .env ]; then
        cp .env.example .env
    fi

    if grep -q '^APP_KEY=$' .env; then
        php artisan key:generate --ansi
    fi

    # No automatic seeding: restarting the container must not duplicate questions.
    php artisan migrate --force
fi

exec "$@"
