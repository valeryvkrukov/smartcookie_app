FROM php:8.4-apache

# ── System: install PHP extensions, Node.js, cron, and supervisor
RUN apt-get update && apt-get install -y --no-install-recommends \
        default-mysql-client \
        nodejs \
        npm \
        cron \
        supervisor \
    && docker-php-ext-install pdo_mysql \
    && echo 'upload_max_filesize=10M\npost_max_size=12M' > /usr/local/etc/php/conf.d/uploads.ini \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite \
    && printf '<Directory "${APACHE_DOCUMENT_ROOT}">\n\tOptions +FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' \
       >> /etc/apache2/sites-available/000-default.conf

# ── Cron: run Laravel scheduler every minute
RUN echo '* * * * * www-data cd /var/www/html && php artisan schedule:run >> /var/log/laravel-scheduler.log 2>&1' \
    > /etc/cron.d/laravel-scheduler \
    && chmod 0644 /etc/cron.d/laravel-scheduler

# ── Supervisor: run Apache and cron as managed processes
RUN mkdir -p /var/log/supervisor
COPY supervisord.conf /etc/supervisor/conf.d/app.conf

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]