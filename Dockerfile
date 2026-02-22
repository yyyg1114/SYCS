# ベースイメージを指定
FROM php:8.2.12-apache

RUN apt update \
        && apt install -y \
            g++ \
            libicu-dev \
            libpq-dev \
            libzip-dev \
            zip \
            zlib1g-dev \
            npm \
            nodejs \
            vim \
        && docker-php-ext-install \
            intl \
            opcache \
            pdo \
            pdo_pgsql \
            pgsql \
            pdo_mysql
# Composerのインストール
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP拡張機能の有効化
RUN docker-php-ext-install pdo_mysql zip

# ソースをルートディレクトリにコピー（パスは適宜変えてください。）
COPY ./docker/shop/src /var/www/html

# apacheの設定ファイルをコピー（パスは適宜変えてください。）
COPY ./docker/shop/apache/default.conf /etc/apache2/sites-enabled/000-default.conf

# その他コピー
COPY ./php.ini-development /usr/local/etc/php/php.ini-development
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt update  
RUN apt install sudo  

# Composerで依存関係をインストール
RUN sudo composer install

RUN echo ServerName localhost >> /etc/apache2/apache2.conf

# アプリケーションキーの生成と設定
RUN php artisan key:generate

# アクセス権限は適宜変えてください。
RUN chmod 777 -R ./

RUN php artisan migrate

# ポートのエクスポート
EXPOSE 80

RUN a2enmod rewrite

# コンテナ起動時にApacheを実行
CMD ["apache2-foreground"]
