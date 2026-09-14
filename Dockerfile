# Development image for mister-quiz.
# PHP 8.0.30 matches the starter's locked Laravel 8.33 (Laravel 8.33 fails on PHP 8.1+).
# Versions match the team standard in _docs/Installation-guide.md.
# The application code is not copied in: docker-compose.yml bind-mounts it.
FROM php:8.0.30-cli-bullseye

# Debian 11 (bullseye) is end-of-life: its security repository lists packages that no longer
# download (404), so only the main and updates repositories are used.
# mbstring, pdo_sqlite, dom, xml, curl and tokenizer are already bundled in the base image.
# No git or mysql client needed: Composer installs zip packages with unzip, and MySQL
# commands run in the db container (`docker compose exec db mysql ...`).
RUN sed -i '/bullseye-security/d' /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.9.5 /usr/bin/composer /usr/bin/composer

# Copied into the image so the executable bit does not depend on the host filesystem.
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint

# No fixed USER: the entrypoint switches to the owner of the project folder (any host UID).
WORKDIR /var/www/html

EXPOSE 8000

ENTRYPOINT ["entrypoint"]
# --no-reload: without it, Laravel 8's server removes DB_HOST/DB_PORT from the web process,
# which then reads 127.0.0.1 from .env and cannot reach the db container.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
