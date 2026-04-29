# Base de Datos SIEst - Esquema Completo

## Arquitectura

```
PostgreSQL (siest) ←→ PHP Flight ←→ Angular Frontend
     │
     ├── Catálogos base
     ├── Inventario (bienes)
     ├── Operaciones (préstamos, auditorías)
     └── Auth (usuarios, roles)
```

---

## 1. TABLAS DE AUTENTICACIÓN

### roles
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_rol | INT | PK, IDENTITY | Identificador único |
| nombre | VARCHAR(50) | NOT NULL, UNIQUE | Nombre del rol |
| descripcion | TEXT | | Descripción del rol |
| fecha_alta | TIMESTAMP | DEFAULT NOW() | Fecha de creación |

**Roles definidos:**
| nombre | descripcion |
|--------|-------------|
| `Administrador` | Acceso total al sistema (Admin + Director) |
| `Profesor` | Acceso a módulos de profesor |
| `Estudiante` | Acceso básico a módulos de estudiante |

**Nota:** El rol `Administrador` es usado tanto por Administrador como por Director, ya que comparten los mismos permisos.

### usuarios
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_usuario | INT | PK, IDENTITY | Identificador único |
| usuario | VARCHAR(50) | UNIQUE, NOT NULL | Nombre de usuario |
| contrasena_hash | VARCHAR(255) | NOT NULL | Hash bcrypt |
| cve_rol | INT | FK → roles | Rol del usuario |
| nombre | VARCHAR(100) | | Nombre completo |
| email | VARCHAR(255) | | Correo electrónico |
| activo | BOOLEAN | DEFAULT true | Estado del usuario |
| cve_persona | INT | FK → persona | Persona asociada |
| fecha_registro | TIMESTAMP | DEFAULT NOW() | Fecha de registro |
| fecha_actualizacion | TIMESTAMP | DEFAULT NOW() | Última actualización |

**Usuarios de prueba:**
| usuario | contraseña | rol | descripción |
|---------|------------|-----|-------------|
| admin | Admin123! | Administrador | Administrador del sistema |
| profesor | Profesor123! | Profesor | Profesor de ejemplo |
| alumno | Alumno123! | Estudiante | Estudiante de ejemplo |
| director | Director123! | Administrador | Director (comparte rol Admin) |

**Nota:** El Director usa el mismo rol `Administrador` que el admin, diferenciándose solo por configuración en `cve_persona` o un campo adicional.

---

## 2. CATÁLOGOS BASE (Nivel 0)

### area
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_area | INT | PK, IDENTITY | Identificador |
| cve_area_padre | INT | FK → area | Área padre (jerarquía) |
| abreviatura | VARCHAR(5) | NOT NULL | Abreviatura |
| nombre | VARCHAR(60) | NOT NULL | Nombre completo |
| activo | BOOLEAN | DEFAULT TRUE | Estado |

### tipo_profesor
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_tipo_profesor | INT | PK, IDENTITY | Identificador |
| nombre | VARCHAR(30) | NOT NULL | Tipo (TC, AS, MT, INV, VIS) |
| descripcion | TEXT | | Descripción |

### persona
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_persona | INT | PK, IDENTITY | Identificador |
| nombre | VARCHAR(50) | NOT NULL | Nombre |
| apellido_paterno | VARCHAR(35) | | Apellido paterno |
| apellido_materno | VARCHAR(35) | | Apellido materno |
| cve_carrera | INT | | Carrera |
| cve_nivel_estudio | INT | | Nivel de estudios |
| fecha_alta | TIMESTAMP | DEFAULT NOW() | Fecha de registro |

### edificio
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_edificio | INT | PK, IDENTITY | Identificador |
| nombre | VARCHAR(80) | | Nombre del edificio |
| abreviatura | VARCHAR(5) | | Abreviatura |
| activo | BOOLEAN | DEFAULT TRUE | Estado |

### tipo_aula
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_tipo_aula | INT | PK, IDENTITY | Identificador |
| nombre | VARCHAR(45) | NOT NULL | Tipo (Aula Normal, Laboratorio, etc.) |

### tipos_bien
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_tipo | INT | PK, IDENTITY | Identificador |
| clave | VARCHAR(2) | NOT NULL | Clave (MC, MA, EQ, VE, HE) |
| nombre | VARCHAR(100) | NOT NULL | Nombre del tipo |

### marcas
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_marca | INT | PK, IDENTITY | Identificador |
| nombre_marca | VARCHAR(50) | NOT NULL, UNIQUE | Nombre de marca |

### motivo_de_movimiento
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_motivo | INT | PK, IDENTITY | Identificador |
| nombre_motivo | VARCHAR(50) | NOT NULL | Motivo (Cambio, Préstamo, etc.) |
| requiere_aprobacion | BOOLEAN | DEFAULT FALSE | Requiere aprobación |

---

## 3. TABLAS CON REFERENCIAS (Nivel 1)

### profesor
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_profesor | INT | PK, IDENTITY | Identificador |
| cve_persona | INT | FK → persona | Persona |
| cve_tipo_profesor | INT | FK → tipo_profesor | Tipo de profesor |
| cve_area | INT | FK → area | Área |
| activo | BOOLEAN | DEFAULT TRUE | Estado |

### alumnos
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_alumno | INT | PK, IDENTITY | Identificador |
| cve_persona | INT | FK → persona | Persona |
| matricula | VARCHAR(15) | NOT NULL | Número de matrícula |
| fecha_inscrito | DATE | NOT NULL | Fecha de inscripción |
| inscrito | BOOLEAN | NOT NULL | Estado de inscripción |
| activo | BOOLEAN | DEFAULT TRUE | Estado |

### aula
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_aula | INT | PK, IDENTITY | Identificador |
| cve_edificio | INT | FK → edificio | Edificio |
| cve_tipo_aula | INT | FK → tipo_aula | Tipo de aula |
| nombre | VARCHAR(30) | NOT NULL | Nombre |
| capacidad | SMALLINT | | Capacidad |
| cve_profesor | INT | FK → profesor | Encargado |

### familias_articulos
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_familia | INT | PK, IDENTITY | Identificador |
| clave | VARCHAR(2) | NOT NULL | Clave |
| nombre | VARCHAR(100) | NOT NULL | Nombre |
| cve_tipo | INT | FK → tipos_bien | Tipo de bien |

### modelos
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_modelo | INT | PK, IDENTITY | Identificador |
| nombre_modelo | VARCHAR(100) | NOT NULL | Nombre del modelo |
| cve_marca | INT | FK → marcas | Marca |
| descripcion | TEXT | | Descripción |

### adscripcion
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_adscripcion | INT | PK, IDENTITY | Identificador |
| clave_adscripcion | VARCHAR(20) | NOT NULL, UNIQUE | Clave (ADS-001, etc.) |
| nombre_adscripcion | VARCHAR(50) | NOT NULL | Nombre |
| estado | BOOLEAN | DEFAULT TRUE | Estado |
| fecha_registro | DATE | DEFAULT NOW() | Fecha |

---

## 4. TABLAS CON REFERENCIAS (Nivel 2)

### articulos
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_articulo | INT | PK, IDENTITY | Identificador |
| clave | VARCHAR(4) | NOT NULL | Clave |
| nombre | VARCHAR(100) | NOT NULL | Nombre |
| cve_familia | INT | FK → familias_articulos | Familia |

### adscripcion_persona
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_adscripcion_persona | INT | PK, IDENTITY | Identificador |
| cve_adscripcion | INT | FK → adscripcion | Adscripción |
| cve_persona | INT | FK → persona | Persona |
| activo | BOOLEAN | DEFAULT TRUE | Estado |
| fecha_registro | TIMESTAMP | DEFAULT NOW() | Fecha |

---

## 5. TABLA DE INVENTARIO (Central)

### bienes
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_bien | INT | PK, IDENTITY | Identificador único |
| nombre | VARCHAR(100) | NOT NULL | Nombre del bien |
| codigo_barras | VARCHAR(50) | UNIQUE | Código de barras |
| nfc | VARCHAR(50) | UNIQUE | Código NFC (para auditorías) |
| codigo_qr | VARCHAR(255) | UNIQUE | Código QR |
| no_serie | VARCHAR(50) | UNIQUE | Número de serie |
| no_factura | VARCHAR(50) | UNIQUE | Número de factura |
| descripcion | TEXT | | Descripción |
| cve_modelo | INT | FK → modelos | Modelo |
| cve_articulo | INT | FK → articulos | Artículo |
| costo_unitario | DECIMAL(10,2) | | Precio unitario |
| cve_aula | INT | FK → aula | Aula donde está |
| cve_encargado | INT | FK → persona | Persona responsable |
| foto_url | VARCHAR(500) | | URL de la foto en Google Drive |
| foto_drive_id | VARCHAR(255) | | ID del archivo en Drive |
| estado_fisico | VARCHAR(20) | | bueno, regular, malo, mantenimiento, baja |
| estado_prestamo | VARCHAR(20) | DEFAULT 'disponible' | disponible, prestado, vencido |
| activo | BOOLEAN | DEFAULT TRUE | Estado |
| fecha_registro | DATE | DEFAULT NOW() | Fecha de registro |

---

## 6. OPERACIONES (Movimientos)

### auditorias
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_auditoria | INT | PK, IDENTITY | Identificador |
| fecha_auditoria | DATE | DEFAULT NOW() | Fecha |
| cve_auditor | INT | FK → persona | Auditor |
| observaciones_generales | TEXT | | Observaciones |

### auditoria_detalle
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_auditoria_det | INT | PK, IDENTITY | Identificador |
| cve_auditoria | INT | FK → auditorias | Auditoría |
| cve_bien | INT | FK → bienes | Bien auditado |
| encontrado | BOOLEAN | | Encontrado |
| estado_encontrado | VARCHAR(50) | | Estado (Excelente, Dañado, etc.) |
| observaciones_evidencia | TEXT | | Evidencia y observaciones |
| observaciones_evidencia | TEXT | | Evidencia |

### prestamos
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_prestamo | INT | PK, IDENTITY | Identificador |
| cve_bien | INT | FK → bienes | Bien prestado |
| cve_persona_solicita | INT | FK → persona | Solicitante |
| fecha_solicitud | TIMESTAMP | DEFAULT NOW() | Fecha solicitud |
| fecha_devolucion_pactada | TIMESTAMP | | Fecha pactada |
| fecha_devolucion_real | TIMESTAMP | NULL | Fecha devolución real |
| estado_prestamo | VARCHAR(20) | DEFAULT 'pendiente' | pendiente, aprobado, devuelto, rechazado |
| observaciones | TEXT | | Observaciones del préstamo |

### bitacora_movimientos
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_movimiento | INT | PK, IDENTITY | Identificador |
| cve_bien | INT | FK → bienes | Bien |
| cve_motivo | INT | FK → motivo_de_movimiento | Motivo |
| cve_persona_accion | INT | FK → persona | Persona que actiona |
| fecha_movimiento | TIMESTAMP | DEFAULT NOW() | Fecha |
| observaciones | TEXT | | Observaciones |

---

## 7. RESGUARDO Y FOLIOS

### folio_siia
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_folio | INT | PK, IDENTITY | Identificador |
| clave_siia | VARCHAR(50) | NOT NULL | Clave SIIA |
| cve_persona | INT | FK → persona | Persona |
| fecha | DATE | DEFAULT NOW() | Fecha |
| hora | TIME | DEFAULT NOW() | Hora |
| estado | BOOLEAN | DEFAULT TRUE | Estado |

### numero_resguardo
| Columna | Tipo | Constraints | Descripción |
|---------|------|-------------|-------------|
| cve_resguardo_pk | INT | PK, IDENTITY | Identificador |
| no_resguardo | VARCHAR(50) | NOT NULL | Número |
| cve_persona | INT | FK → persona | Persona |
| fecha | DATE | DEFAULT NOW() | Fecha |
| hora | TIME | DEFAULT NOW() | Hora |
| estado | BOOLEAN | DEFAULT TRUE | Estado |
| fecha_registro | DATE | DEFAULT NOW() | Fecha registro |

---

## 8. RELACIONES IMPORTANTES

```
persona
  ├── profesor (1:1)
  ├── alumnos (1:1)
  ├── usuarios (1:1)
  └── adscripcion_persona (1:N)

aula
  ├── bienes (1:N)
  ├── edificio (N:1)
  └── profesor (N:1) - cve_profesor

bienes
  ├── aula (N:1)
  ├── modelos (N:1)
  ├── articulos (N:1)
  └── persona (N:1) - cve_encargado

bienes ──prestamos──> persona (muchos a uno)
```

---

## 9. TRIGGERS ACTUALES

### trg_actualizar_estado_prestamo
- Cuando se actualiza `estado_prestamo` en `prestamos`:
  - Si es 'aprobado' → actualiza bien a 'prestado'
  - Si es 'devuelto' → actualiza bien a 'disponible'

### trg_log_estado_fisico
- Cuando cambia `estado_fisico` en `bienes`:
  - Inserta automáticamente en `bitacora_movimientos`

---

## 10. CONSULTAS ÚTILES

### Bienes con ubicación
```sql
SELECT b.cve_bien, b.nombre, a.nombre as aula, e.nombre as edificio
FROM bienes b
JOIN aula a ON b.cve_aula = a.cve_aula
JOIN edificio e ON a.cve_edificio = e.cve_edificio;
```

### Préstamos activos con datos
```sql
SELECT p.*, b.nombre as bien, pe.nombre as solicitante
FROM prestamos p
JOIN bienes b ON p.cve_bien = b.cve_bien
JOIN persona pe ON p.cve_persona_solicita = pe.cve_persona
WHERE p.fecha_devolucion_real IS NULL;
```

### Inventario por aula
```sql
SELECT a.nombre, COUNT(b.cve_bien) as total, SUM(b.costo_unitario) as valor
FROM aula a
LEFT JOIN bienes b ON a.cve_aula = b.cve_aula
GROUP BY a.cve_aula, a.nombre;
```