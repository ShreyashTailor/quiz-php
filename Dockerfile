# QuizMaster Dockerfile
# Uses PHP 8.3 with Apache

FROM php:8.3-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    zip \
    gd \
    mbstring \
    xml \
    curl \
    opcache

# Enable Apache modules
RUN a2enmod rewrite headers ssl

# Configure PHP for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/var/log/php_errors.log'; \
    echo 'session.cookie_httponly=1'; \
    echo 'session.cookie_secure=1'; \
    echo 'session.cookie_samesite=Strict'; \
    echo 'session.use_strict_mode=1'; \
    echo 'session.gc_maxlifetime=3600'; \
    echo 'upload_max_filesize=10M'; \
    echo 'post_max_size=10M'; \
    echo 'max_execution_time=30'; \
    echo 'memory_limit=256M'; \
} > /usr/local/php/conf.d/quizmaster.ini

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/logs \
    && chmod 755 /var/www/html/logs

# Create .htaccess for security
RUN { \
    echo '# Security Headers'; \
    echo 'Header always set X-Content-Type-Options nosniff'; \
    echo 'Header always set X-Frame-Options DENY'; \
    echo 'Header always set X-XSS-Protection "1; mode=block"'; \
    echo 'Header always set Referrer-Policy "strict-origin-when-cross-origin"'; \
    echo 'Header always set Content-Security-Policy "default-src '\''self'\''; script-src '\''self'\'' '\''unsafe-inline'\''; style-src '\''self'\'' '\''unsafe-inline'\''; img-src '\''self'\'' data: https:; font-src '\''self'\''; connect-src '\''self'\''"'; \
    echo ''; \
    echo '# Prevent access to sensitive files'; \
    echo '<FilesMatch "^\.(env|git|htaccess)">'; \
    echo '    Order allow,deny'; \
    echo '    Deny from all'; \
    echo '</FilesMatch>'; \
    echo ''; \
    echo '<FilesMatch "(config|security)\.php$">'; \
    echo '    Order allow,deny'; \
    echo '    Deny from all'; \
    echo '</FilesMatch>'; \
    echo ''; \
    echo '# Prevent directory listing'; \
    echo 'Options -Indexes'; \
    echo ''; \
    echo '# URL Rewriting'; \
    echo 'RewriteEngine On'; \
    echo 'RewriteCond %{REQUEST_FILENAME} !-f'; \
    echo 'RewriteCond %{REQUEST_FILENAME} !-d'; \
    echo 'RewriteRule ^(.*)$ index.php [QSA,L]'; \
} > /var/www/html/.htaccess

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]