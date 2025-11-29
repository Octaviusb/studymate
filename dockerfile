# 1️⃣ Imagen base PHP + Apache
FROM php:8.2-apache

# 2️⃣ Instalar dependencias para SQL Server
RUN apt-get update && apt-get install -y \
    gnupg2 \
    unixodbc-dev \
    curl \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/11/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv
