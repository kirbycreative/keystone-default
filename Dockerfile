FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY laravel-toolkit laravel-toolkit
COPY resources resources
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS php-dependencies
WORKDIR /app
COPY composer.json composer.lock ./
COPY laravel-toolkit laravel-toolkit
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.4-fpm-alpine
RUN apk add --no-cache nginx supervisor libzip-dev icu-dev oniguruma-dev \
    && docker-php-ext-install bcmath intl mbstring opcache pcntl pdo_mysql pdo_sqlite zip
WORKDIR /var/www/html
COPY . .
COPY --from=php-dependencies /app/vendor vendor
COPY --from=frontend /app/public/build public/build
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD wget -qO- http://127.0.0.1:8080/health || exit 1
ENTRYPOINT ["entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
