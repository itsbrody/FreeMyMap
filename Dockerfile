FROM php:8.3-apache

# mod_rewrite für .htaccess
RUN a2enmod rewrite headers expires

# allow_url_fopen sicherstellen (für Short-URL-Auflösung)
RUN echo "allow_url_fopen = On" > /usr/local/etc/php/conf.d/freemymap.ini

# Rate-Limiting-Verzeichnis vorbereiten
RUN mkdir -p /tmp/freemymap_ratelimit && \
    chown www-data:www-data /tmp/freemymap_ratelimit

# App-Dateien kopieren
COPY --chown=www-data:www-data . /var/www/html/

# Sicherheit: includes/ nicht direkt erreichbar (zusätzlich zu .htaccess)
RUN chmod 750 /var/www/html/includes/

EXPOSE 80