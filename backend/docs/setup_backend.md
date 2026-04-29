# Setup Backend — Módulo Inventario

## Requisitos

- **PHP >= 8.2**
- **PostgreSQL** (base de datos principal)
- Extensiones PHP habilitadas: `pdo_pgsql`, `mbstring`, `openssl`, `gd`

## Librerías (Composer)

| Librería | Versión | Propósito |
|---|---|---|
| `mikecao/flight` | ^3.15 | Micro-framework (routing, HTTP) |
| `vlucas/phpdotenv` | ^5.6 | Variables de entorno (`.env`) |
| `firebase/php-jwt` | ^7.0 | Autenticación JWT |
| `picqer/php-barcode-generator` | ^3.2 | Generación de códigos de barras |
| `endroid/qr-code` | ^6.0 | Generación de códigos QR |
| `google/apiclient` | ^2.19 | API de Google |

## Instalación

```bash
# 1. Clonar el repo
git clone <url-del-repo>
cd Modulo_inventarioBakend

# 2. Instalar dependencias (composer.phar ya viene incluido)
php composer.phar install

# 3. Configurar variables de entorno
copy .env.example .env
# Editar .env con los datos reales de tu máquina
```

### Variables de entorno a configurar (`.env`)

```env
# PostgreSQL
PG_HOST=localhost
PG_PORT=5432
PG_NAME=siest
PG_USER=postgres
PG_PASS=<tu_password>

# App
APP_ENV=development
API_KEY=<clave_secreta_minimo_32_caracteres>
FRONTEND_URL=http://localhost:4200
```

> Las variables `PG16_*` y `DB_*` (SQL Server) son opcionales — solo se usan si necesitás conectar a fuentes legacy.

## Levantar el servidor

```bash
php -S localhost:8082 -t .
```

El backend queda disponible en `http://localhost:8082`.
