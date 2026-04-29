# Módulo Administrador - Documentación Técnica

## Pantallas del Módulo

1. **Dashboard** - Vista general con estadísticas
2. **Inventario General** - Tabla de todos los bienes (**con foto**)
3. **Registrar Bien** - Formulario para agregar bienes (**con foto**)
4. **Registro de Aulas** - Formulario para crear aulas
5. **Historial de Préstamos** - Lista de préstamos
6. **Solicitar Préstamo (Directo)** - Préstamo sin aprobación
7. **Bitácora de Movimientos** - Log de operaciones
8. **Auditorías** - Gestión de auditorías (**con NFC**)
9. **Reportes** - Generar reportes varios
10. **Gráficas** - Visualización de estadísticas
11. **Impresión de Etiquetas** - Generar etiquetas por aula
12. **Papelera** - Bienes eliminados

---

## Endpoints del Módulo

### Inventario

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/inventario` | Lista todos los bienes |
| POST | `/api/v1/administrador/inventario` | Registra nuevo bien |
| GET | `/api/v1/administrador/inventario/detalle/@id` | Detalle de un bien |
| GET | `/api/v1/administrador/inventario/por-aula` | Bienes agrupados por aula |

### Préstamos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/prestamos` | Lista todos los préstamos |
| GET | `/api/v1/administrador/prestamos/no-devueltos` | Préstamos vencidos |
| POST | `/api/v1/administrador/prestamos/solicitar` | Crear préstamo directo |

### Aulas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/administrador/aulas` | Registrar nueva aula |

### Bitácora

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/bitacora` | Lista movimientos |
| POST | `/api/v1/administrador/bitacora` | Registrar movimiento |

### Auditorías

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/auditorias` | Lista auditorías |
| POST | `/api/v1/administrador/auditorias` | Registrar auditoría |

### Estadísticas

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/estadisticas/estado-fisico` | Conteo por estado |

---

## Esquema de Datos

### bienes
```sql
CREATE TABLE bienes (
    cve_bien INT PRIMARY KEY,
    nombre VARCHAR(100),
    codigo_barras VARCHAR(50),
    codigo_qr VARCHAR(255),
    no_serie VARCHAR(50),
    no_factura VARCHAR(50),
    descripcion TEXT,
    cve_modelo INT,
    cve_articulo INT,
    costo_unitario DECIMAL(10,2),
    cve_aula INT,
    cve_encargado INT,
    estado_fisico VARCHAR(20),  -- bueno, malo, reparacion
    estado_prestamo VARCHAR(20), -- disponible, prestado, vencido
    activo BOOLEAN,
    fecha_registro DATE
);
```

### aula
```sql
CREATE TABLE aula (
    cve_aula INT PRIMARY KEY,
    cve_edificio INT,
    cve_tipo_aula INT,
    nombre VARCHAR(30),
    capacidad SMALLINT,
    cve_profesor INT
);
```

### prestamos
```sql
CREATE TABLE prestamos (
    cve_prestamo INT PRIMARY KEY,
    cve_bien INT,
    cve_persona_solicita INT,
    fecha_solicitud TIMESTAMP,
    fecha_devolucion_pactada TIMESTAMP,
    fecha_devolucion_real TIMESTAMP,
    estado_prestamo VARCHAR(20) -- pendiente, aprobado, devuelto, rechazado
);
```

---

## Interfaces TypeScript

### InventarioBien
```typescript
interface InventarioBien {
  cve_bien: number;
  nombre: string;
  codigo_barras: string | null;
  codigo_qr: string | null;
  no_serie: string | null;
  no_factura: string | null;
  descripcion: string | null;
  cve_modelo: number | null;
  cve_articulo: number | null;
  costo_unitario: number | null;
  cve_aula: number | null;
  estado_fisico: string;
  estado_prestamo: string;
  activo: boolean;
  fecha_registro: string;
  nombre_modelo?: string;
  nombre_marca?: string;
  nombre_aula?: string;
  nombre_edificio?: string;
}
```

### RegistrarBienPayload
```typescript
interface RegistrarBienPayload {
  nombre: string;
  codigo_barras: string;
  nfc?: string;
  codigo_qr?: string;
  no_serie?: string;
  no_factura?: string;
  descripcion?: string;
  cve_modelo?: number;
  cve_articulo?: number;
  costo_unitario?: number;
  cve_aula?: number;
  estado_fisico?: string;
  nombre_marca?: string;
  nombre_modelo?: string;
}
```

### PrestamoItem
```typescript
interface PrestamoItem {
  cve_prestamo: number;
  cve_bien: number;
  nombre_bien: string;
  cve_persona_solicita: number;
  fecha_solicitud: string;
  fecha_devolucion_pactada: string;
  fecha_devolucion_real: string | null;
  estado_prestamo: string;
}
```

### BitacoraItem
```typescript
interface BitacoraItem {
  cve_movimiento: number;
  fecha_movimiento: string;
  observaciones: string | null;
  nombre_bien: string;
  no_serie: string | null;
  nombre_motivo: string;
  persona_nombre: string;
  apellido_paterno: string;
}
```

---

## Flujo: Registrar Bien

```
1. Usuario llena formulario
2. Frontend valida campos requeridos
3. POST /api/v1/administrador/inventario
4. Backend:
   - Valida datos
   - Inserta en tabla bienes
   - Genera codigo_qr si no existe
   - AuditLog
5. Response 201 con cve_bien
6. Frontend muestra éxito
```

## Flujo: Solicitar Préstamo Directo

```
1. Usuario selecciona bien y persona
2. POST /api/v1/administrador/prestamos/solicitar
3. Backend:
   - Verifica que bien existe y está disponible
   - Inserta en prestamos (estado: pendiente)
   - AuditLog
4. Response 201 con cve_prestamo
5. Listo para imprimir etiqueta
```

---

## Validaciones Backend

### Registrar Bien
- `nombre` es requerido
- `codigo_barras` es requerido y único

### Solicitar Préstamo
- `cve_bien` es requerido
- `cve_persona_solicita` es requerido
- `fecha_devolucion_pactada` es requerido
- Bien debe existir y estar disponible

---

## Componentes Angular Necesarios

```
pages/administrador/
├── inventario-general/
│   ├── inventario-general.component.ts
│   ├── inventario-general.component.html
│   └── inventario-general.component.css
├── registrar-bien/
│   └── ...
├── registro-aulas/
│   └── ...
├── historial-prestamos/
│   └── ...
├── solicitar-prestamo-sin-permiso/
│   └── ...
├── bitacora-movimientos/
│   └── ...
├── auditorias/
│   └── ...
├── generar-reportes/
│   └── ...
├── valor-patrimonial/
│   └── ...
├── impresion-etiquetas/
│   └── ...
├── registro-inmobiliaria/
│   └── ...
├── registros-salas/
│   └── ...
├── prestamo/
│   └── ...
├── tabla-inventario/
│   └── ...
└── notifications-prestamos/
    └── ...
```

---

## Estados del Bien

| estado_fisico | Descripción |
|---------------|-------------|
| `bueno` | En óptimas condiciones |
| `regular` | Necesita atención |
| `malo` | Deterioro significativo |
| `mantenimiento` | En reparación |
| `baja` | Dado de baja |

| estado_prestamo | Descripción |
|-----------------|-------------|
| `disponible` | Puede prestarse |
| `prestado` | Actualmente prestado |
| `vencido` | Pasó fecha de devolución |
| `reservado` | Apartado para alguien |