# Módulos del Sistema SIEst - Documentación Técnica

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                        FRONTEND (Angular 17)                │
├─────────────────────────────────────────────────────────────┤
│  Alumno  │  Profesor  │  Administrador  │  Director         │
└────┬────────────────────────────────────────────────────────┘
     │
     │ HTTP JSON API
     │
┌────▼────────────────────────────────────────────────────────┐
│                    BACKEND (PHP Flight)                      │
├─────────────────────────────────────────────────────────────┤
│  Controllers: Ejemplo, Administrador, Informacion,          │
│              Reportes, Auditorias, Papelera, Barcodes       │
└────┬────────────────────────────────────────────────────────┘
     │
     │ getPgConnection()
     │
┌────▼────────────────────────────────────────────────────────┐
│              POSTGRESQL (Datos nuevos)                      │
│  bienes, aula, edificio, prestamos, auditorias, etc.      │
└──────────────────────────────────────��──────────────────────┘
```

---

## Roles y Permisos

| Rol | Módulo | Descripción |
|-----|--------|-------------|
| **Alumno** | Alumno | Solicitar préstamos, ver historial |
| **Profesor** | Profesor | Préstamos, aulas encargadas, auditoría, reportes |
| **Administrador** | Administrador | Control total del inventario |
| **Director** | Administrador | Comparte módulo con Admin (mismo rol) |

**Nota:** El Director accede con `director / Director123!` pero usa el mismo módulo de Administrador. |

---

## Endpoints por Módulo

### Authentication
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/login` | Iniciar sesión |
| POST | `/api/v1/refresh-token` | Renovar token |

### Información (Catálogos)
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/informacion/edificios` | Lista de edificios |
| GET | `/api/v1/informacion/tipos-bien` | Tipos de bien |
| GET | `/api/v1/informacion/marcas` | Catálogo de marcas |
| GET | `/api/v1/informacion/modelos` | Catálogo de modelos |
| GET | `/api/v1/informacion/adscripciones` | Adscripciones |

### Alumno
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/prestamos/solicitar` | Solicitar préstamo |
| GET | `/api/v1/prestamos` | Ver mis préstamos |

### Profesor
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/profesor/inventario` | Registrar bien |
| GET | `/api/v1/profesor/aulas` | Aulas encargadas |
| GET | `/api/v1/profesor/aulas/@id` | Detalle aula |

### Administrador
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/inventario` | Inventario general |
| POST | `/api/v1/administrador/inventario` | Registrar bien |
| GET | `/api/v1/administrador/inventario/detalle/@id` | Detalle bien |
| GET | `/api/v1/administrador/prestamos` | Todos los préstamos |
| GET | `/api/v1/administrador/estadisticas/estado-fisico` | Por estado físico |
| POST | `/api/v1/administrador/prestamos/solicitar` | Préstamo directo |
| GET | `/api/v1/administrador/bitacora` | Movimientos |
| POST | `/api/v1/administrador/auditorias` | Registrar auditoría |
| GET | `/api/v1/administrador/auditorias` | Listar auditorías |
| POST | `/api/v1/administrador/aulas` | Registrar aula |

### Auditorías
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/auditorias/buscar-nfc/@nfc` | Buscar por NFC |
| GET | `/api/v1/auditorias/buscar-qr` | Buscar por QR |

### Reportes
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/reportes/resguardo-individual/@id` | Resguardo personal |
| GET | `/api/v1/reportes/movimiento-interno/@id` | Movimiento interno |
| GET | `/api/v1/reportes/inventario/bajas` | Bienes dados de baja |
| GET | `/api/v1/reportes/inventario/mantenimiento` | Bienes en mantenimiento |
| GET | `/api/v1/reportes/trazabilidad/@id` | Trazabilidad del bien |
| GET | `/api/v1/reportes/estadisticas/estado-fisico` | Gráfica estado físico |
| GET | `/api/v1/reportes/estadisticas/estado-prestamo` | Gráfica préstamos |
| GET | `/api/v1/reportes/estadisticas/valor-por-aula` | Valor monetario |

### Etiquetas
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/aulas` | Todas las aulas |
| GET | `/api/v1/barcodes/etiquetas/datos/aula/@id` | Barcodes por aula |
| GET | `/api/v1/barcodes/etiquetas/qr/datos/aula/@id` | QR por aula |

### Papelera
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/papelera/bienes` | Bienes inactivos |
| PUT | `/api/v1/papelera/desactivar/@id` | Mover a papelera |
| PUT | `/api/v1/papelera/restaurar/@id` | Restaurar |
| DELETE | `/api/v1/papelera/eliminar/@id` | Eliminar permanentemente |

---

## Archivos de Documentación por Módulo

| Archivo | Descripción |
|---------|-------------|
| `modulo-alumno.md` | Funcionalidades del alumno |
| `modulo-profesor.md` | Funcionalidades del profesor |
| `modulo-administrador.md` | Funcionalidades del administrador |
| `modulo-director.md` | Funcionalidades del director |
| `endpoints-impresion-etiquetas.md` | Módulo de impresión de etiquetas |
| `frontend/pantallas.md` | UI por módulo |
| `frontend/componentes.md` | Componentes Angular necesarios |

---

## Flujo de Datos Típico

```
1. Login → Obtener token JWT
2. Guardar token en localStorage
3. Incluir token en headers: Authorization: Bearer {token}
4. Llamar endpoints protegidos
5. Manejar errores 401 (redirigir a login)
```

---

## Estados de Response

```json
// Éxito
{ "success": true, "data": {...} }

// Error
{ "success": false, "error": { "code": "ERROR", "message": "..." } }

// Validación
{ "success": false, "error": { "code": "VALIDATION_ERROR", "details": [...] } }
```