FROM php:8.2-cli

WORKDIR /app

RUN docker-php-ext-install json >/dev/null 2>&1 || true

CMD ["php", "-v"]
