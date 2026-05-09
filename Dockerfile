FROM php:8.3-apache

# mod_rewrite für .htaccess
RUN a2enmod rewrite headers expires

# allow_url_fopen sicherstellen (für Short-URL-Auflösung)
RUN echo "allow_url_fopen = On" > /usr/local/etc/php/conf.d/freemymap.ini

# AllowOverride aktivieren, damit .htaccess greift
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Rate-Limiting-Verzeichnis vorbereiten
RUN mkdir -p /tmp/freemymap_ratelimit && \
    chown www-data:www-data /tmp/freemymap_ratelimit

# App-Dateien kopieren
COPY --chown=www-data:www-data . /var/www/html/

# Sicherheit: includes/ nicht direkt erreichbar (zusätzlich zu .htaccess)
RUN chmod 750 /var/www/html/includes/

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -sf http://localhost/ || exit 1

EXPOSE 80