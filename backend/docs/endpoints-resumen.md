# Endpoints Completos - SIEst API v1

## Base URL
```
Desarrollo: http://localhost:8080
```

---

## Authentication

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/v1/login` | No | Iniciar sesión |
| POST | `/api/v1/refresh-token` | No | Renovar token |

---

## Alumno

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/v1/prestamos/solicitar` | Sí | Solicitar préstamo |
| GET | `/api/v1/prestamos/no-devueltos/@cve_persona` | Sí | Mis préstamos no devueltos |
| GET | `/api/v1/prestamos/aceptados/persona/@id` | Sí | Préstamos aceptados |

---

## Profesor

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/v1/profesor/inventario` | Sí | Registrar bien |
| GET | `/api/v1/profesor/aulas` | Sí | Aulas encargadas |
| GET | `/api/v1/profesor/aulas/@id` | Sí | Detalle aula |
| POST | `/api/v1/prestamos/solicitar` | Sí | Solicitar préstamo |
| GET | `/api/v1/profesor/inventario` | Sí | Bienes de mis aulas |

---

## Administrador

### Inventario
| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/administrador/inventario` | No* | Lista bienes |
| POST | `/api/v1/administrador/inventario` | No* | Registrar bien |
| GET | `/api/v1/administrador/inventario/detalle/@id` | No* | Detalle bien |
| GET | `/api/v1/administrador/inventario/por-aula` | No* | Bienes por aula |

*\* Actualmente sin authMiddleware activo*

### Préstamos
| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/administrador/prestamos` | No* | Lista préstamos |
| GET | `/api/v1/administrador/prestamos/no-devueltos` | No* | Préstamos vencidos |
| POST | `/api/v1/administrador/prestamos/solicitar` | No* | Préstamo directo |

### Aulas
| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| POST | `/api/v1/administrador/aulas` | No* | Registrar aula |

### Bitácora
| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/administrador/bitacora` | No* | Ver movimientos |
| POST | `/api/v1/administrador/bitacora` | No* | Registrar movimiento |

### Auditorías
| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/administrador/auditorias` | No* | Lista auditorías |
| POST | `/api/v1/administrador/auditorias` | No* | Registrar auditoría |

### Estadísticas
| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/administrador/estadisticas/estado-fisico` | No* | Conteo por estado |

---

## Auditorías

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/auditorias/buscar-nfc/@nfc` | No* | Buscar por NFC |
| GET | `/api/v1/auditorias/buscar-qr/@qr` | No* | Buscar por QR |
| GET | `/api/v1/barcodes/buscar/@codigo` | No* | Buscar por código barras |

---

## Reportes

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/reportes/resguardo-individual/@id_persona` | No* | Resguardo personal |
| GET | `/api/v1/reportes/encabezado-direccion/@id_persona` | No* | Encabezado dirección |
| GET | `/api/v1/reportes/movimiento-interno/@id_prestamo` | No* | Movimiento interno |
| GET | `/api/v1/reportes/prestamos/no-devueltos` | No* | Préstamos no devueltos |
| GET | `/api/v1/reportes/inventario/bajas` | No* | Bienes dados de baja |
| GET | `/api/v1/reportes/inventario/mantenimiento` | No* | Bienes en mantenimiento |
| GET | `/api/v1/reportes/trazabilidad/@id_bien` | No* | Trazabilidad del bien |
| GET | `/api/v1/reportes/estadisticas/valor-por-aula` | No* | Valor monetario por aula |
| GET | `/api/v1/reportes/estadisticas/estado-fisico` | No* | Bienes por estado físico |
| GET | `/api/v1/reportes/estadisticas/estado-prestamo` | No* | Préstamos por estado |

---

## Etiquetas

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/aulas` | No* | Todas las aulas |
| GET | `/api/v1/barcodes/etiquetas/datos/aula/@cve_aula` | No* | Barcodes por aula |
| GET | `/api/v1/barcodes/etiquetas/qr/datos/aula/@cve_aula` | No* | QR por aula |

---

## Papelera

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/papelera/bienes` | No* | Bienes inactivos |
| PUT | `/api/v1/papelera/desactivar/@id` | No* | Mover a papelera |
| PUT | `/api/v1/papelera/restaurar/@id` | No* | Restaurar bien |
| DELETE | `/api/v1/papelera/eliminar/@id` | No* | Eliminar permanentemente |
| GET | `/api/v1/papelera/bienes/persona/@cve_persona` | No* | Por persona |
| GET | `/api/v1/papelera/bienes/@id` | No* | Detalle bien |

---

## Información (Catálogos)

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/aulas/edificio/@cve_edificio` | No* | Aulas por edificio |
| GET | `/api/v1/informacion/edificios` | No* | Lista de edificios |
| GET | `/api/v1/informacion/personal/carrera/@cve_carrera` | No* | Personal por carrera |
| GET | `/api/v1/informacion/bienes` | No* | Catálogo de bienes |
| GET | `/api/v1/informacion/bienes/disponibles` | No* | Bienes disponibles |
| GET | `/api/v1/informacion/tipos-bien` | No* | Tipos de bien |
| GET | `/api/v1/informacion/familias/tipo/@cve_tipo` | No* | Familias por tipo |
| GET | `/api/v1/informacion/articulos/familia/@cve_familia` | No* | Artículos por familia |
| GET | `/api/v1/informacion/tipos-aula` | No* | Tipos de aula |
| GET | `/api/v1/informacion/marcas` | No* | Catálogo de marcas |
| GET | `/api/v1/informacion/modelos` | No* | Catálogo de modelos |
| GET | `/api/v1/informacion/bien/qr/@id` | No* | QR por ID de bien |
| GET | `/api/v1/informacion/bien/formato/@id` | No* | Formato para bien |
| GET | `/api/v1/informacion/adscripciones` | No* | Adscripciones |
| GET | `/api/v1/informacion/folios-siia` | No* | Folios SIIA |
| GET | `/api/v1/informacion/numeros-resguardo` | No* | Números de resguardo |

---

## Préstamos (Compartido)

| Método | Endpoint | Auth | Descripción |
|--------|----------|------|-------------|
| GET | `/api/v1/prestamos` | No* | Ver préstamos |
| GET | `/api/v1/prestamos/pendientes` | No* | Préstamos pendientes |
| PUT | `/api/v1/prestamos/aceptar/@id` | No* | Aceptar préstamo |
| PUT | `/api/v1/prestamos/rechazar/@id` | No* | Rechazar préstamo |
| PUT | `/api/v1/prestamos/devolver/@id` | No* | Devolver préstamo |
| GET | `/api/v1/prestamos/pendientes/encargado/@id` | No* | Pendientes por profesor |
| GET | `/api/v1/prestamos/detalle/@id` | No* | Detalle del préstamo |

---

## Formatos de Response

### Success
```json
{
  "success": true,
  "data": [...]
}
```

### Error
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje de error"
  }
}
```

### Created
```json
{
  "success": true,
  "data": { "id": 1 },
  "meta": {
    "message": "Recurso creado exitosamente"
  }
}
```

---

## Códigos de Error

| Código | HTTP | Descripción |
|--------|------|-------------|
| VALIDATION_ERROR | 400 | Datos inválidos |
| UNAUTHORIZED | 401 | Token inválido |
| NOT_FOUND | 404 | Recurso no encontrado |
| ERROR | 500 | Error interno |