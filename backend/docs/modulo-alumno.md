# Módulo Alumno - Documentación Técnica

## Pantallas del Módulo

1. **Solicitar Préstamo** - Formulario para pedir equipo
2. **Historial de Préstamo** - Mis préstamos realizados

---

## Endpoints del Módulo

### Préstamos (Alumno)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/v1/prestamos/solicitar` | Solicitar préstamo |
| GET | `/api/v1/prestamos/no-devueltos/@cve_persona` | Préstamos no devueltos |
| GET | `/api/v1/prestamos/aceptados/persona/@id` | Préstamos aceptados |

### Bienes Disponibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/informacion/bienes/disponibles` | Lista de bienes disponibles |

---

## Interfaces TypeScript

### SolicitarPrestamoPayload
```typescript
interface SolicitarPrestamoPayload {
  cve_bien: number;
  cve_persona_solicita: number;
  fecha_devolucion_pactada: string; // formato: "2026-05-15 14:00:00"
}
```

### MiPrestamo
```typescript
interface MiPrestamo {
  cve_prestamo: number;
  cve_bien: number;
  nombre_bien: string;
  fecha_solicitud: string;
  fecha_devolucion_pactada: string;
  fecha_devolucion_real: string | null;
  estado_prestamo: string;
  nombre_aula: string;
  nombre_edificio: string;
}
```

### BienDisponible
```typescript
interface BienDisponible {
  cve_bien: number;
  no_serie: string | null;
  nombre: string;
  nombre_modelo?: string;
  nombre_marca?: string;
}
```

---

## Flujo: Solicitar Préstamo (Alumno)

```
1. Alumno ve lista de bienes disponibles
2. Selecciona el bien que necesita
3. Indica fecha de devolución deseada
4. POST /api/v1/prestamos/solicitar
5. Backend:
   - Verifica que bien existe y está disponible
   - Inserta en prestamos (estado: pendiente)
   - Notifica al profesor encargado del aula
6. Espera aprobación del profesor
```

---

## Validaciones Backend

### Solicitar Préstamo
- `cve_bien` es requerido
- `cve_persona_solicita` es requerido (del token)
- `fecha_devolucion_pactada` es requerido
- Bien debe existir y tener estado_prestamo = 'disponible'
- Bien debe tener activo = true
- Alumno no puede pedir más de X préstamos simultáneos (opcional)

---

## Componentes Angular Necesarios

```
pages/alumno/
├── solicitar-prestamo/
│   ├── solicitar-prestamo.component.ts
│   ├── solicitar-prestamo.component.html
│   └── solicitar-prestamo.component.css
└── historial-prestamo/
    └── ...
```

---

## Notas Importantes

- El alumno **NO puede** prestar directamente (no tiene el endpoint de administrador)
- Debe esperar a que un profesor/admin approve el préstamo
- No tiene acceso a bienes dados de baja o en mantenimiento