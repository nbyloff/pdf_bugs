# Set master image
FROM php:8.2-fpm

# Arguments defined in docker-compose.yml
ARG UID

ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Set working directory
WORKDIR /var/www/html

COPY ./start.sh /usr/local/bin/start

# Install Additional dependencies
RUN apt-get update && apt-get -y upgrade && apt-get install -y libzip-dev supervisor zip fontconfig wget && \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y \
      chromium \
      procps \
      build-essential \
      libpng-dev \
      libjpeg62-turbo-dev \
      libfreetype6-dev \
      locales \
      zip \
      jpegoptim optipng pngquant gifsicle \
      vim \
      unzip \
      git \
      curl \
      nodejs gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 \
      libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 \
      libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libnss3 lsb-release xdg-utils libappindicator1 \
      libgbm-dev --no-install-recommends && \
    npm install --global --unsafe-perm puppeteer && \
    #npx puppeteer browsers install chrome && \
    docker-php-ext-configure zip && \
    docker-php-ext-install zip && \
    pecl install redis && docker-php-ext-enable redis && docker-php-ext-install pdo pdo_mysql pcntl exif && \
        docker-php-ext-configure gd \
          --with-freetype=/usr/include/ \
          --with-jpeg=/usr/include/ \
        && docker-php-ext-install -j$(nproc) gd \
        && docker-php-source delete && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    useradd -u $UID -g www-data -m web && \
    mkdir -p /home/web/.composer && \
    apt-get clean && rm -rf /var/lib/apt/lists/* && \
    chmod +x /usr/local/bin/start

USER web

ENTRYPOINT ["/usr/local/bin/start"]
