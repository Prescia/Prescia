FROM php:8.3-apache

# Install required PHP extensions and dependencies
RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install mysqli pdo pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers expires

# Keep framework internals and deployment artifacts outside the public surface.
RUN printf '%s\n' \
    '<FilesMatch "^(\\.env|composer\\.(json|lock)|Dockerfile|.*\\.(ini|log|sql|bak|dist))$">' \
    '    Require all denied' \
    '</FilesMatch>' \
    'Alias /pages/ /var/www/app/pages/' \
	    '<Directory "/var/www/app/pages/">' \
	    '    Require all granted' \
	    '</Directory>' \
	    '<DirectoryMatch "^/var/www/app/pages/(presciatester|_newProjectTemplate)(/|$)">' \
	    '    Require all denied' \
	    '</DirectoryMatch>' \
	    '<DirectoryMatch "^/var/www/app/(config|prescia|tests|tools|docs)(/|$)">' \
    '    Require all denied' \
    '</DirectoryMatch>' \
    > /etc/apache2/conf-available/prescia-hardening.conf \
    && a2enconf prescia-hardening

# Keep the application outside Apache's document root.
WORKDIR /var/www/app

# Copy application files as immutable application code.
COPY --chown=root:root . /var/www/app/

# Expose only the public wrapper and routing rules from the document root.
RUN mkdir -p /var/www/public \
    && cp /var/www/app/public/index.php /var/www/public/index.php \
    && cp /var/www/app/.htaccess /var/www/public/.htaccess

# Set up required directories with proper permissions
RUN mkdir -p _temp/_logs _temp/_cache _temp/_backups \
    && find /var/www/app /var/www/public -type d -exec chmod 0755 {} + \
    && find /var/www/app /var/www/public -type f -exec chmod 0644 {} + \
    && chown -R www-data:www-data _temp \
    && chmod -R 0770 _temp

# Copy configuration files if they don't exist
RUN if [ ! -f config/domains ]; then cp config/domains.original config/domains; fi \
    && if [ ! -f config/settings.php ]; then cp config/settings.php.original config/settings.php; fi

# Configure Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure PHP
COPY docker/php-production.ini /usr/local/etc/php/conf.d/production.ini
RUN echo "date.timezone = America/Sao_Paulo" >> /usr/local/etc/php/conf.d/timezone.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
