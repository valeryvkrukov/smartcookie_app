FROM php:8.4-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
        cron \
        supervisor \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y nodejs npm

# ── Cron: run Laravel scheduler every minute
RUN echo '* * * * * www-data cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1' \
    > /etc/cron.d/laravel-scheduler \
    && chmod 0644 /etc/cron.d/laravel-scheduler

# ── Supervisord: keep Apache + cron running together
RUN mkdir -p /var/log/supervisor
COPY supervisord.conf /etc/supervisor/conf.d/app.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]