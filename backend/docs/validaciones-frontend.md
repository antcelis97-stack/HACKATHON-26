# Validaciones Frontend - SIEst

## Reglas Generales

### Caracteres permitidos
- **Nombres propios**: Solo letras, espacios, acentos y Ñ (ej: "Juan María José")
- **Usuario/Login**: Solo letras, números y guiones bajos (ej: "admin_2024")
- **Contraseñas**: Mínimo 8 caracteres, al menos 1 mayúscula, 1 número
- **Códigos (barras, QR, serie)**: Letras, números y guiones (ej: "MCEQLAP001", "SN-98765")

### Caracteres NO permitidos en ningún campo
- `< > & " ' ; = / \ | `
- Emojis
- Saltos de línea en campos de texto corto

---

## Validaciones por Campo

### 1. Login / Autenticación

| Campo | Reglas |
|-------|--------|
| usuario | Requerido, 3-50 caracteres, solo letras/números/guiones bajos |
| contraseña | Requerido, mínimo 8 caracteres |

**Errores:**
- "El usuario es requerido"
- "La contraseña es requerida"
- "Usuario o contraseña incorrectos"

---

### 2. Registro de Bien

| Campo | Reglas |
|-------|--------|
| nombre | Requerido, 2-100 caracteres, solo letras/números/espacios |
| codigo_barras | Requerido, único, 3-50 caracteres, formato: letras-números-guiones |
| no_serie | Opcional, 3-50 caracteres, formato: letras-números-guiones |
| no_factura | Opcional, 3-50 caracteres |
| descripcion | Opcional, máximo 500 caracteres |
| costo_unitario | Opcional, número positivo, máximo 2 decimales |
| cve_aula | Requerido, debe existir |
| estado_fisico | Requerido, opciones válidas |
| nombre_marca | Opcional, 2-50 caracteres |
| nombre_modelo | Opcional, 2-100 caracteres |
| foto | Opcional, imagen JPG/PNG/WEBP, máx 5MB |
| nfc | Opcional, código hexadecimal único, 8-20 caracteres |

**Errores:**
- "El nombre del bien es requerido"
- "El código de barras ya existe"
- "El número de serie ya existe"
- "El costo debe ser un número positivo"
- "La imagen debe estar en formato JPG, PNG o WEBP"
- "La imagen no puede exceder 5MB"
- "El código NFC ya está asignado a otro bien"

---

### 3. Registro de Aula

| Campo | Reglas |
|-------|--------|
| nombre | Requerido, 2-30 caracteres, solo letras/números/espacios/guiones |
| cve_edificio | Requerido, debe existir |
| cve_tipo_aula | Requerido, debe existir |
| capacidad | Opcional, número entero positivo, 1-999 |

**Errores:**
- "El nombre del aula es requerido"
- "El nombre no puede exceder 30 caracteres"
- "La capacidad debe ser un número entre 1 y 999"

---

### 4. Solicitud de Préstamo

| Campo | Reglas |
|-------|--------|
| cve_bien | Requerido, debe existir y estar disponible |
| cve_persona_solicita | Requerido |
| fecha_devolucion_pactada | Requerido, debe ser fecha futura (mínimo 1 día después) |

**Errores:**
- "Selecciona un bien"
- "El bien seleccionado no está disponible"
- "La fecha de devolución debe ser futura"
- "La fecha de devolución debe ser al menos mañana"

---

### 5. Registrar Auditoría

| Campo | Reglas |
|-------|--------|
| cve_auditor | Requerido |
| observaciones_generales | Opcional, máximo 1000 caracteres |
| detalles | Array con bienes auditados |

**Detalle de bien auditado:**
| Campo | Reglas |
|-------|--------|
| cve_bien | Requerido |
| encontrado | Requerido (0 o 1) |
| estado_encontrado | Requerido si encontrado = 0 |

---

### 6. Personas (Usuario, Profesor, Alumno)

| Campo | Reglas |
|-------|--------|
| nombre | Requerido, 2-50 caracteres, solo letras/espacios |
| apellido_paterno | Requerido, 2-35 caracteres, solo letras |
| apellido_materno | Opcional, 2-35 caracteres, solo letras |
| email | Opcional, formato email válido |
| matricula | Opcional (alumnos), 5-15 caracteres, solo letras/números |

**Errores:**
- "El nombre es requerido"
- "El apellido paterno es requerido"
- "El correo no tiene un formato válido"
- "La matrícula solo puede contener letras y números"

---

## Estados Válidos

### Estado Físico del Bien
```
bueno | regular | malo | mantenimiento | baja
```

### Estado de Préstamo
```
pendiente | aprobado | devuelto | rechazado | vencido
```

### Estado de Aula
```
activo | inactivo
```

---

## Formatos de Fecha

- **Input date**: `YYYY-MM-DD` (ej: 2026-04-25)
- **Input datetime**: `YYYY-MM-DDTHH:mm` (ej: 2026-04-25T14:00)
- **API response**: `YYYY-MM-DD HH:mm:ss` o ISO 8601
- **Display**: `DD/MM/YYYY` para usuario (formato MX)

---

## Longitudes Máximas

| Campo | Máximo |
|-------|--------|
| nombre (bien) | 100 |
| nombre (aula) | 30 |
| nombre (usuario) | 100 |
| nombre (persona) | 50 |
| descripción | 500 |
| observación | 1000 |
| código barras | 50 |
| número serie | 50 |
| email | 255 |
| teléfono | 20 |
| costo | 10,2 decimales |

---

## Validaciones de Formato

### Código de Barras
```regex
^[A-Z0-9\-]{3,50}$
```
- Solo mayúsculas
- Letras A-Z, números 0-9
- Guiones (-) permitidos
- Sin espacios

**Ejemplos válidos:** `MCEQLAP001`, `PROY-001`, `750123456789`

### Número de Serie
```regex
^[A-Za-z0-9\-]{3,50}$
```
- Letras mayúsculas o minúsculas
- Números
- Guiones (-)
- Sin espacios

**Ejemplos válidos:** `SN-98765`, `MX-9988`, `ABC123XYZ`

### Correo Electrónico
```regex
^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$
```

### Matrícula (Alumno)
```regex
^[A-Za-z0-9]{5,15}$
```

---

## Tabla de Validaciones Rápida

| Campo | Requerido | Pattern | Longitud | Extra |
|-------|-----------|---------|----------|-------|
| nombre_bien | ✅ | `^[A-Za-z0-9áéíóúÁÉÍÓÚÑñ\s]{2,100}$` | 2-100 | |
| codigo_barras | ✅ | `^[A-Z0-9\-]{3,50}$` | 3-50 | único |
| no_serie | ❌ | `^[A-Za-z0-9\-]{3,50}$` | 3-50 | único |
| nombre_aula | ✅ | `^[A-Za-z0-9áéíóúÁÉÍÓÚÑñ\s\-]{2,30}$` | 2-30 | |
| nombre_persona | ✅ | `^[A-Za-záéíóúÁÉÍÓÚÑñ]{2,50}$` | 2-50 | solo letras |
| email | ❌ | email válido | máx 255 | |
| costo | ❌ | número positivo | máx 10,2 | |
| fecha | ✅ | YYYY-MM-DD | | futura |

---

## Validaciones de Foto

| Propiedad | Requisito |
|-----------|-----------|
| formatos | JPG, PNG, WEBP |
| tamaño | Máximo 5MB |
| dimensiones | Máximo 1920x1080 |
| compresión | 80% calidad |

**Errores:**
- "La imagen debe estar en formato JPG, PNG o WEBP"
- "La imagen no puede exceder 5MB"
- "La imagen es demasiado grande. Reduce las dimensiones."

---

## Validaciones de NFC

| Propiedad | Requisito |
|-----------|-----------|
| formato | Código hexadecimal (ej: 04A3B2C1D5) |
| longitud | 8-20 caracteres |
| único | No puede repetirse en otro bien |

**Errores:**
- "El código NFC ya está asignado a otro bien"
- "Código NFC no válido"

---

## Mensajes de Error en Español

```typescript
const mensajesError = {
  requerido: 'Este campo es requerido',
  minLength: (n: number) => `Debe tener al menos ${n} caracteres`,
  maxLength: (n: number) => `No puede exceder ${n} caracteres`,
  email: 'El correo no tiene un formato válido',
  pattern: 'El formato no es válido',
  unique: 'Este valor ya existe en el sistema',
  numeroPositivo: 'Debe ser un número mayor a 0',
  fechaFutura: 'La fecha debe ser futura',
  fechaMinima: (fecha: string) => `Debe ser al menos el ${fecha}`,
  soloLetras: 'Solo se permiten letras',
  soloNumeros: 'Solo se permiten números',
  sinEspacios: 'No se permiten espacios',
  contrasenaDebil: 'La contraseña debe tener mínimo 8 caracteres, 1 mayúscula y 1 número'
};
```

---

## Placeholders y Ayuda para Formularios

### Login

| Campo | Placeholder | Descripción |
|-------|-------------|-------------|
| usuario | "Ej: admin" | Nombre de usuario del sistema |
| contraseña | "Tu contraseña" | Contraseña de acceso |

---

### Registro de Bien

| Campo | Placeholder | Descripción | Ejemplo |
|-------|-------------|-------------|---------|
| nombre | "Ej: Laptop Dell Latitude" | Nombre descriptivo del bien | Laptop Dell Latitude 3420 |
| codigo_barras | "Ej: MCEQLAP001" | Código único de identificación | MCEQLAP001 |
| no_serie | "Ej: SN-98765" | Número de serie del fabricante | SN-98765 |
| no_factura | "Ej: FACT-2024-001" | Número de factura de compra | FACT-2024-001 |
| descripcion | "Describe las características..." | Detalles adicionales | Color negro, 8GB RAM |
| costo_unitario | "Ej: 15000.00" | Precio en pesos MXN | 15000.00 |
| cve_aula | "Selecciona el aula" | Ubicación física del bien | Aula A-101 |
| estado_fisico | "Estado del bien" | Condición actual | Bueno |
| nombre_marca | "Ej: Dell" | Marca del fabricante | Dell |
| nombre_modelo | "Ej: Latitude 3420" | Modelo específico | Latitude 3420 |
| foto | "Subir foto del bien" | Imagen JPG/PNG/WebP, máx 5MB | 📷 laptop.jpg |
| nfc | "Código NFC" | UID de la etiqueta NFC | 04A3B2C1D5 |

---

### Registro de Aula

| Campo | Placeholder | Descripción | Ejemplo |
|-------|-------------|-------------|---------|
| nombre | "Ej: Aula A-101" | Nombre o número del aula | Aula A-105 |
| cve_edificio | "Selecciona el edificio" | Edificio donde se ubica | Edificio A |
| cve_tipo_aula | "Selecciona el tipo" | Tipo de aula (normal, lab, etc.) | Laboratorio |
| capacidad | "Ej: 30" | Número máximo de personas | 30 |

---

### Solicitud de Préstamo

| Campo | Placeholder | Descripción | Ejemplo |
|-------|-------------|-------------|---------|
| cve_bien | "Buscar por nombre o código..." | Bien que necesitas | Laptop Dell |
| cve_persona_solicita | "Selecciona solicitante" | Quién solicita (auto) | Juan Pérez |
| fecha_devolucion_pactada | "Selecciona fecha" | Fecha límite de devolución | 25/04/2026 |

---

### Registro de Auditoría

| Campo | Placeholder | Descripción | Ejemplo |
|-------|-------------|-------------|---------|
| cve_auditor | "Selecciona quien audita" | Persona responsable de la auditoría | María García |
| observaciones_generales | "Observaciones generales de la auditoría..." | Notas generales | Auditoría de cierre de semestre |

**Detalle de bien auditado:**
| Campo | Placeholder | Descripción |
|-------|-------------|-------------|
| buscar_bien | "Escanea o escribe código..." | Buscar bien por QR/código |
| encontrado | Sí / No | ¿Se encontró el bien? |
| estado_encontrado | "Ej: Excelente" | Estado en que se encontró |

---

### Formularios de Persona

| Campo | Placeholder | Descripción | Ejemplo |
|-------|-------------|-------------|---------|
| nombre | "Ej: Juan" | Nombre(s) de la persona | María del Carmen |
| apellido_paterno | "Ej: López" | Apellido paterno | Hernández |
| apellido_materno | "Ej: García" | Apellido materno (opcional) | Rodríguez |
| email | "Ej: correo@university.edu" | Correo institucional | juan.perez@universidad.edu |
| matricula | "Ej: 202200123" | Número de control (alumnos) | 202200123 |

---

### Búsquedas y Filtros

| Campo | Placeholder | Descripción |
|-------|-------------|-------------|
| buscar | "Buscar por nombre, serie o código..." | Búsqueda general |
| filtro_edificio | "Todos los edificios" | Filtrar por edificio |
| filtro_estado | "Todos los estados" | Filtrar por estado físico |
| filtro_aula | "Todas las aulas" | Filtrar por aula |

---

### Tooltips de Ayuda

```typescript
const tooltips = {
  codigo_barras: 'Código único que identifica el bien. Se imprime en la etiqueta.',
  no_serie: 'Número de serie del fabricante. Si no tiene, deja vacío.',
  costo_unitario: 'Precio en pesos mexicanos (MXN). Ej: 15000.00',
  estado_fisico: 'Bueno: funciona perfectamente | Regular: tiene detalles | Malo: no funciona bien',
  cve_aula: 'Ubicación física donde se encuentra el bien actualmente.',
  fecha_devolucion: 'Fecha en que el solicitante se compromete a devolver el bien.'
};
```

---

## Ejemplo de Formulario Completo (Registro de Bien)

```html
<!-- Nombre del bien -->
<label>Nombre del Bien *</label>
<input type="text" placeholder="Ej: Laptop Dell Latitude" />
<small>Nombre descriptivo del equipo o mueble</small>

<!-- Código de barras -->
<label>Código de Barras *</label>
<input type="text" placeholder="Ej: MCEQLAP001" />
<small>Código único de identificación (3-50 caracteres, solo letras, números y guiones)</small>

<!-- Número de serie -->
<label>Número de Serie</label>
<input type="text" placeholder="Ej: SN-98765" />
<small>Número de serie del fabricante (opcional)</small>

<!-- Aula -->
<label>Aula / Ubicación *</label>
<select>
  <option value="">Selecciona el aula donde se ubicará el bien</option>
  <option value="1">Aula A-101 - Edificio A</option>
  <option value="2">Laboratorio L-201 - Edificio A</option>
</select>
<small>Ubicación física donde se encuentra el bien</small>

<!-- Estado físico -->
<label>Estado Físico *</label>
<select>
  <option value="">Selecciona el estado actual</option>
  <option value="bueno">Bueno - En óptimas condiciones</option>
  <option value="regular">Regular - Necesita atención</option>
  <option value="malo">Malo - Deterioro significativo</option>
</select>
```