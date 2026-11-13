# 本番用のDockerfile（開発用は docker/php/Dockerfile）
# ビルドのコンテキストはリポジトリ直下なので、Laravelのファイルは src/ を付けてコピーする

# ===== 箱A：PHPの部品（vendor）を作る =====
FROM composer:latest AS composer-builder
WORKDIR /app
# 買い物リストだけ先に入れる（変わっていなければキャッシュが使われる）
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# ===== 箱C：画面のCSS・JS（public/build）を作る =====
FROM node:20 AS node-builder
WORKDIR /app
COPY src/package*.json ./
RUN npm ci
COPY src/resources ./resources
# Vite・Tailwind・PostCSS の設定ファイル
COPY src/vite.config.js src/tailwind.config.js src/postcss.config.js ./
RUN npm run build

# ===== Nginxの箱：public/ とビルド済みのCSS・JSを入れておく =====
FROM nginx:alpine AS web
COPY src/public /var/www/html/public
COPY --from=node-builder /app/public/build /var/www/html/public/build

# ===== 箱B：アプリを動かす（最終イメージ） =====
FROM php:8.2-fpm-alpine
WORKDIR /var/www/html

# 必要なものだけ入れる（DB接続 ＋ ヘルスチェック用の cgi-fcgi）
# php-fpm の ping 機能を有効にする（/ping に問い合わせると pong が返る）
RUN apk add --no-cache fcgi \
    && docker-php-ext-install pdo_mysql \
    && echo "ping.path = /ping" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# 箱A・箱Cで作った物と、自分のコードを入れる（持ち主は www-data）
COPY --chown=www-data:www-data --from=composer-builder /app/vendor ./vendor
COPY --chown=www-data:www-data src/ .
COPY --chown=www-data:www-data --from=node-builder /app/public/build ./public/build

# root ではなく、普通のユーザー（www-data）で動かす
USER www-data

# php-fpm（ポート9000）の /ping に問い合わせ、pong が返れば正常
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
      cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

CMD ["php-fpm"]
