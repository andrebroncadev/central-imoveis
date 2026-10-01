FROM php:8.3-apache
RUN docker-php-ext-install opcache
COPY . /var/www/html/
RUN a2enmod rewrite headers expires
RUN printf '%s\n' '<IfModule mod_headers.c>' 'Header always set X-Content-Type-Options "nosniff"' 'Header always set Referrer-Policy "strict-origin-when-cross-origin"' 'Header always set X-Frame-Options "SAMEORIGIN"' '</IfModule>' > /etc/apache2/conf-available/security-headers.conf && a2enconf security-headers
EXPOSE 80