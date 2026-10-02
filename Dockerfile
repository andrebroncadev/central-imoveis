FROM php:8.3-apache
RUN docker-php-ext-install opcache && mkdir -p /tmp/php-sessions && chown www-data:www-data /tmp/php-sessions && printf '%s\n' 'upload_max_filesize=100M' 'post_max_size=110M' 'max_file_uploads=1' 'display_errors=0' 'log_errors=1' 'max_execution_time=600' 'max_input_time=600' > /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/
RUN a2enmod rewrite headers expires
RUN printf '%s\n' '<IfModule mod_headers.c>' 'Header always set X-Content-Type-Options "nosniff"' 'Header always set Referrer-Policy "strict-origin-when-cross-origin"' 'Header always set X-Frame-Options "SAMEORIGIN"' '</IfModule>' > /etc/apache2/conf-available/security-headers.conf && a2enconf security-headers
EXPOSE 80