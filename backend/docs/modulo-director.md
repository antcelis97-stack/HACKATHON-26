# Módulo Director - Documentación Técnica

## Descripción

El **Director** comparte muchas funcionalidades con el Administrador, pero con **restricciones de solo lectura**. Su propósito es supervisar que todo funcione correctamente sin intervenir en operaciones.

**Credenciales:**
- Usuario: `director`
- Contraseña: `Director123!`

---

## Pantallas del Módulo

### ✅ VISIBLES PARA DIRECTOR

1. **Dashboard** - Vista general con estadísticas
2. **Historial de Préstamos** - Ver todos los préstamos (solo vista)
3. **Ver Reportes** - Generar reportes (solo lectura)
4. **Ver Gráficas** - Visualización de estadísticas
5. **Ver Bitácora** - Ver movimientos (solo lectura)
6. **Ver Inventario** - Ver bienes (solo vista, sin registrar/editar)

### ❌ NO VISIBLES PARA DIRECTOR

- Registrar Bien
- Registrar Aula
- Solicitar Préstamo Directo
- Registrar Auditoría
- Gestionar Bajas/Mantenimiento
- Papelera

---

## Endpoints del Módulo

El Director accede a los **mismos endpoints** que el Administrador, pero el frontend **no muestra las opciones de modificar**.

### Acceso permitido (solo lectura)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/administrador/inventario` | Ver inventario |
| GET | `/api/v1/administrador/prestamos` | Ver préstamos |
| GET | `/api/v1/administrador/prestamos/no-devueltos` | Préstamos vencidos |
| GET | `/api/v1/administrador/bitacora` | Ver movimientos |
| GET | `/api/v1/administrador/auditorias` | Ver auditorías |
| GET | `/api/v1/administrador/estadisticas/estado-fisico` | Gráficas |

### Reportes accesibles

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/reportes/resguardo-individual/@id` | Resguardo personal |
| GET | `/api/v1/reportes/inventario/bajas` | Ver bajas |
| GET | `/api/v1/reportes/inventario/mantenimiento` | Ver mantenimiento |
| GET | `/api/v1/reportes/trazabilidad/@id` | Trazabilidad |
| GET | `/api/v1/reportes/estadisticas/estado-fisico` | Gráfica estado físico |
| GET | `/api/v1/reportes/estadisticas/estado-prestamo` | Gráfica préstamos |
| GET | `/api/v1/reportes/estadisticas/valor-por-aula` | Valor por aula |

---

## Diferencias con Administrador

| Funcionalidad | Administrador | Director |
|---------------|---------------|----------|
| Ver inventario | ✅ | ✅ |
| Registrar bien | ✅ | ❌ |
| Editar bien | ✅ | ❌ |
| Eliminar bien | ✅ | ❌ |
| Ver préstamos | ✅ | ✅ |
| Aceptar/rechazar préstamo | ✅ | ❌ |
| Ver reportes | ✅ | ✅ |
| Generar reportes | ✅ | ✅ |
| Ver bitácora | ✅ | ✅ |
| Registrar bitácora | ✅ | ❌ |
| Ver gráficas | ✅ | ✅ |
| Registrar aulas | ✅ | ❌ |
| Gestionar papelera | ✅ | ❌ |

---

## Componentes Angular

El Director usa una **copia del módulo Administrador** pero con los botones de acción ocultos/deshabilitados.

```
pages/director/
├── dashboard-director/
├── ver-inventario-solo-lectura/
├── historial-prestamos/
├── ver-reportes/
├── ver-estadisticas/
└── ver-bitacora/
```

**Nota:** El contenido de los componentes es casi identical al Administrador, solo se quitan los formularios de registro y los botones de acción.