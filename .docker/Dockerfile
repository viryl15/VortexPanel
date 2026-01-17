# VortexPanel dev image: PHP + Composer + Node (for Vite)
# Goal: run the demo app and develop the packages anywhere (Windows/Docker-friendly)

FROM php:8.2-cli-bookworm

ARG NODE_MAJOR=20

# System dependencies
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl ca-certificates unzip zip \
    sqlite3 libsqlite3-dev \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# PHP extensions (keep it lean)
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_sqlite \
    zip \
    mbstring \
    bcmath

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.js (for Vite)
RUN curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - \
  && apt-get update \
  && apt-get install -y --no-install-recommends nodejs \
  && npm install -g npm@latest \
  && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/vortex

CMD ["bash"]
