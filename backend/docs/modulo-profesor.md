# Módulo Profesor - Documentación Técnica

## Pantallas del Módulo

1. **Solicitar Préstamo** - Formulario para pedir equipo
2. **Historial de Préstamos** - Mis préstamos solicitados
3. **Aulas Encargadas** - Lista de aulas a mi cargo
4. **Auditorías** - Generar reporte de auditoría por aula (**con NFC**)
5. **Tabla Inventario (Profesor)** - Bienes de mis aulas
6. **Registro Inmobiliaria** - Registrar bienes propios (**con foto**)
7. **Notificaciones** - Campanita de préstamos pendientes

---

## Endpoints del Módulo

### Préstamos (Profesor)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/profesor/prestamos` | Mis préstamos |
| POST | `/api/v1/prestamos/solicitar` | Solicitar préstamo |

### Aulas Encargadas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/profesor/aulas` | Lista de aulas a mi cargo |
| GET | `/api/v1/profesor/aulas/@id` | Detalle del aula |

### Inventario (Profesor)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/profesor/inventario` | Registrar bien en mis aulas |
| GET | `/api/v1/profesor/inventario` | Bienes de mis aulas |

### Auditorías

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/administrador/auditorias` | Registrar auditoría |

---

## Esquema de Datos

### aula (relación con profesor)
```sql
-- Relación: aula tiene cve_profesor que indica el encargado
ALTER TABLE aula ADD COLUMN cve_profesor INT REFERENCES profesor(cve_profesor);
```

### profesor
```sql
CREATE TABLE profesor (
    cve_profesor INT PRIMARY KEY,
    cve_persona INT,
    cve_tipo_profesor INT,
    cve_area INT,
    activo BOOLEAN
);
```

---

## Interfaces TypeScript

### MisPrestamos (Profesor)
```typescript
interface MisPrestamos {
  cve_prestamo: number;
  cve_bien: number;
  nombre_bien: string;
  fecha_solicitud: string;
  fecha_devolucion_pactada: string;
  estado_prestamo: string; // pendiente, aprobado, devuelto, rechazado
}
```

### AulaEncargada
```typescript
interface AulaEncargada {
  cve_aula: number;
  nombre: string;
  capacidad: number;
  nombre_edificio: string;
  tipo_aula: string;
}
```

### BienDeMiAula
```typescript
interface BienDeMiAula {
  cve_bien: number;
  nombre: string;
  no_serie: string | null;
  estado_fisico: string;
  nombre_modelo: string;
  nombre_aula: string;
}
```

---

## Flujo: Solicitar Préstamo

```
1. Profesor busca bien por nombre/código
2. Selecciona fecha de devolución
3. POST /api/v1/prestamos/solicitar
4. Backend:
   - Verifica que bien existe y está disponible
   - Inserta en prestamos (estado: pendiente)
   - Notifica al administrador
5. Esperar aprobación
```

## Flujo: Aulas Encargadas

```
1. Obtener cve_profesor del token JWT
2. GET /api/v1/profesor/aulas
3. Backend filtra: WHERE cve_profesor = :id
4. Muestra lista de aulas
5. Al seleccionar aula, muestra bienes de esa aula
```

## Flujo: Registrar Bien (Profesor)

```
1. Profesor llena formulario
2. POST /api/v1/profesor/inventario
3. Backend:
   - Valida que el aula pertenece al profesor
   - Inserta en bienes
   - Genera codigo_qr
4. Response 201
```

---

## Validaciones Backend

### Solicitar Préstamo
- `cve_bien` es requerido
- `fecha_devolucion_pactada` es requerido
- Bien debe existir y estar disponible
- Fecha de devolución debe ser futura

### Registrar Bien (Profesor)
- `nombre` es requerido
- Aula debe pertenecer al profesor
- `cve_aula` es requerido

---

## Componentes Angular Necesarios

```
pages/profesor/
├── solicitar-prestamo/
│   ├── solicitar-prestamo.component.ts
│   ├── solicitar-prestamo.component.html
│   └── solicitar-prestamo.component.css
├── historial-prestamos-profesor/
│   └── ...
├── aulas-encargadas/
│   └── ...
├── generar-auditoria-profesor/
│   └── ...
├── tabla-inventario-profesor/
│   └── ...
├── registro-inmobiliaria-profesor/
│   └── ...
└── notificaciones-prestamos-profesor/
    └── ...
```

---

## Estados de Préstamo (Profesor)

| estado_prestamo | Descripción |
|-----------------|-------------|
| `pendiente` | Esperando aprobación del admin |
| `aprobado` | Aceptado, en uso |
| `devuelto` | Devuelto al aula |
| `rechazado` | Denegado por admin |
| `vencido` | Pasó fecha de devolución |