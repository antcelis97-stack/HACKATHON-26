# SIEstSandboxBackend

**Sandbox de desarrollo Backend para SIEst.**

Este proyecto contiene un ejemplo completo de módulo CRUD para que los alumnos aprendan los patrones de desarrollo del backend SIEst.

## Quick Start

### Requisitos
- PHP 8.2+
- Composer
- PostgreSQL 14+
- (Opcional) SQL Server - para consultar datos legacy

### Instalación

```bash
# 1. Clonar o copiar este directorio
cd SIEstSandboxBackend

# 2. Instalar dependencias
composer install

# 3. Copiar y configurar variables de entorno
cp .env.example .env
# Editar .env con tus credenciales de PostgreSQL

# 4. Ejecutar el schema de base de datos
# Opción A: psql directo
psql -h localhost -U postgres -d siest -f db/migrations/001_schema_sandbox.sql

# Opción B: Importar desde pgAdmin o DBeaver
# Importar el archivo: db/migrations/001_schema_sandbox.sql

# 5. Insertar usuarios de prueba
psql -h localhost -U postgres -d siest -f db/seeds/001_usuarios_prueba.sql

# 6. Iniciar servidor de desarrollo
php -S localhost:8080 -t .
```

### Credenciales de Prueba

| Usuario | Contraseña | Rol |
|--------|------------|-----|
| `admin` | `Admin123!` | Administrador |
| `profesor` | `Profesor123!` | Profesor |
| `alumno` | `Alumno123!` | Estudiante |

### Verificar Instalación

```bash
# Probar el endpoint de salud
curl http://localhost:8080/api/v1/empleados

# Probar login
curl -X POST http://localhost:8080/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{"usuario":"admin","contrasena":"Admin123!"}'
```

## Estructura del Proyecto

```
SIEstSandboxBackend/
├── app/
│   ├── controllers/        # Controladores de endpoints
│   └── Lib/               # Clases utilitarias
├── config/
│   └── database.php       # Conexiones a bases de datos
├── routes/
│   └── routes.php         # Definición de rutas
├── db/
│   ├── migrations/        # Scripts SQL de schema
│   └── seeds/             # Datos de prueba
├── docs/                  # Documentación adicional
├── .env.example          # Template de variables
└── composer.json          # Dependencias PHP
```

## Próximos Pasos

1. Lee [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) para entender la estructura del proyecto
2. Lee [docs/WORKFLOW.md](docs/WORKFLOW.md) para aprender el protocolo de desarrollo
3. Lee [docs/AGENTS.md](docs/AGENTS.md) para conocer los agentes especializados
4. Lee [STANDARDS.md](STANDARDS.md) para conocer los estándares de código
5. Revisa el ejemplo completo en `app/controllers/EjemploController.php`
6. Mira [docs/CODIGO_EJEMPLO.md](docs/CODIGO_EJEMPLO.md) para explicación detallada

## Documentación

| Documento | Descripción |
|-----------|-------------|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Arquitectura, setup, stack, naming |
| [docs/WORKFLOW.md](docs/WORKFLOW.md) | Protocolo de desarrollo |
| [docs/AGENTS.md](docs/AGENTS.md) | Agentes especializados |
| [STANDARDS.md](STANDARDS.md) | Estándares de código PHP |
| [API.md](API.md) | Referencia de endpoints |
| [docs/CODIGO_EJEMPLO.md](docs/CODIGO_EJEMPLO.md) | Explicación del código ejemplo |
