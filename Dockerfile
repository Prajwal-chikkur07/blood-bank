FROM php:8.3-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /app
COPY . .

EXPOSE 10000
CMD ["php", "-S", "0.0.0.0:10000", "-t", "."]
