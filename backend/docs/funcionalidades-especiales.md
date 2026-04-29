# Funcionalidades Especiales

## 1. Fotos de Bienes (Google Drive)

### Descripción

Todos los bienes/inmuebles del sistema pueden tener una **fotografía** que se guarda en **Google Drive**. Esto permite:
- Vervisualizar el bien antes de préstamos
- Documentar el estado físico
- Evidencia en auditorías
-catálogo visual del inventario

### Flujo de Subida de Foto

```
1. Usuario toma/selecciona foto desde el formulario
2. Frontend comprime la imagen (máx 1MB, 1920x1080)
3. Se sube a Google Drive API
4. Se obtiene el link/ID del archivo
5. Se guarda en la base de datos (campo url_foto o file_id)
6. Se muestra la miniatura en la UI
```

### Campo en Base de Datos

```sql
ALTER TABLE bienes ADD COLUMN foto_url VARCHAR(500);
ALTER TABLE bienes ADD COLUMN foto_drive_id VARCHAR(255);
```

### Endpoint de Subida

```
POST /api/v1/bienes/foto
Content-Type: multipart/form-data

Body:
  - cve_bien: int
  - archivo: file (imagen)

Response:
{
  "success": true,
  "data": {
    "foto_url": "https://drive.google.com/uc?id=XXX",
    "foto_drive_id": "1ABC123..."
  }
}
```

### Endpoint de Eliminación

```
DELETE /api/v1/bienes/foto/@cve_bien

Response:
{
  "success": true,
  "message": "Foto eliminada correctamente"
}
```

### Validaciones de Imagen

| Propiedad | Requisito |
|-----------|-----------|
| Formatos | JPG, PNG, WEBP |
| Tamaño máx | 5MB |
| Dimensiones | Máx 1920x1080 |
| Compresión | 80% calidad JPEG |

### Placeholder de Foto

```
📷 "Subir foto del bien" o "Arrastra la imagen aquí"
```

### Herramienta Sugerida

Usar **Google Drive API v3** con:
- OAuth 2.0 para autenticación
- Carpeta específica por aula o por tipo
- Permisos de lectura pública

---

## 2. NFC en Auditorías

### Descripción

Las auditorías pueden usar **etiquetas NFC** para identificar bienes rápidamente. El profesor/admin escanea la etiqueta NFC del bien y el sistema muestra/marca automáticamente.

### Componentes Necesarios

| Componente | Descripción |
|------------|-------------|
| Lector NFC | Hardware (ej: PN532, ACR122U) |
| Etiquetas NFC | Tags adhesivos en cada bien |
| Endpoint de búsqueda | GET /api/v1/auditorias/buscar-nfc/@nfc |
| UI de escaneo | Panel de auditoría con botón "Escanear NFC" |

### Flujo de Auditoría NFC

```
1. Usuario abre panel de auditoría
2. Selecciona el aula a auditar
3. Activa modo "Escanear NFC"
4. Pega el lector al bien
5. Sistema lee el UID NFC
6. Busca el bien por NFC en BD
7. Muestra datos del bien encontrado
8. Marca como "encontrado" en la auditoría
9. Repite para siguiente bien
```

### Campo NFC en Base de Datos

```sql
-- Tabla bienes ya tiene: nfc VARCHAR(50) UNIQUE
-- Las etiquetas NFC guardan un código único (UID)
```

### Endpoint de Búsqueda NFC

```
GET /api/v1/auditorias/buscar-nfc/@nfc

Ejemplo: GET /api/v1/auditorias/buscar-nfc/04A3B2C1D5

Response (bien encontrado):
{
  "success": true,
  "data": {
    "cve_bien": 8,
    "nombre": "Proyector Nuevo",
    "codigo_barras": "PROY-001",
    "codigo_qr": "http://localhost:4200/bien/8",
    "no_serie": null,
    "estado_fisico": "bueno",
    "nombre_aula": "Aula A-101",
    "ubicacion_correcta": true,
    "encontrado_previamente": false
  }
}

Response (no encontrado):
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "No se encontró bien con esa etiqueta NFC"
  }
}
```

### UI de Auditoría NFC

```
┌─────────────────────────────────────────┐
│  AUDITORÍA DE AULA: Aula A-101         │
├─────────────────────────────────────────┤
│                                         │
│    ┌──────────────────────────────┐      │
│    │                              │      │
│    │     📱 ESCANEAR NFC         │      │
│    │                              │      │
│    │   [  Tocando lector...  ]   │      │
│    │                              │      │
│    └──────────────────────────────┘      │
│                                         │
│  Bien Detectado:                        │
│  ┌─────────────────────────────────────┐│
│  │ Proyector Nuevo                      ││
│  │ Serie: SN-98765                      ││
│  │ Estado: Bueno                        ││
│  │                                     ││
│  │ ¿Encontrado?                         ││
│  │  [Sí]  [No]                         ││
│  └─────────────────────────────────────┘│
│                                         │
│  Bienes Auditados: 3/10                 │
│                                         │
│  Lista de bienes esperados:              │
│  ☐ Laptop Dell (pendiente)              │
│  ☑ Proyector Nuevo (encontrado)         │
│  ☐ Escritorio  (pendiente)             │
└─────────────────────────────────────────┘
```

### Pasos de Implementación NFC

**Backend:**
1. ✅ Endpoint `buscar-nfc` ya existe en AuditoriasController

**Frontend:**
1. Componente de escaneo NFC
2. Integración con Web NFC API (navegador) o plugin
3. UI de auditoría mejorada

**Hardware:**
1. Adquirir lector NFC USB (ACR122U o similar)
2. Generar/grabar etiquetas NFC para cada bien
3. Pegar etiquetas en bienes

---

## 3. Combinar Foto + NFC en Bien

### Campos finales en tabla bienes

```sql
ALTER TABLE bienes ADD COLUMN IF NOT EXISTS foto_url VARCHAR(500);
ALTER TABLE bienes ADD COLUMN IF NOT EXISTS foto_drive_id VARCHAR(255);
-- nfc ya existe: nfc VARCHAR(50) UNIQUE
```

### Al registrar un bien, solicitar:

1. Datos básicos (nombre, código barras, etc.)
2. Ubicación (aula)
3. **Foto** (opcional pero recomendado)
4. **NFC** (opcional, se puede asignar después)

---

## Checklist de Implementación

### Fotos
- [ ] Crear endpoint POST /bienes/foto
- [ ] Crear endpoint DELETE /bienes/foto/@id
- [ ] Implementar Google Drive API en backend
- [ ] Frontend: input de imagen con preview
- [ ] Compresión de imágenes en frontend
- [ ] Mostrar foto en detalle de bien

### NFC
- [ ] Verificar endpoint buscar-nfc existe y funciona
- [ ] UI de auditoría con botón "Escanear"
- [ ] Integración con Web NFC API o lector USB
- [ ] Marcar automáticamente como encontrado
- [ ] Alerta si bien no está en lista de aula

---

## Ejemplo: Registro de Bien con Foto

```html
<form>
  <!-- Datos básicos -->
  <input type="text" placeholder="Nombre del bien" />
  <input type="text" placeholder="Código de barras" />
  
  <!-- Foto -->
  <div class="foto-upload">
    <input type="file" accept="image/*" />
    <div class="preview">
      <img src="https://drive.google.com/uc?id=XXX" alt="Foto del bien" />
      <button type="button">Eliminar</button>
    </div>
  </div>
  
  <!-- NFC (opcional) -->
  <div class="nfc-section">
    <label>Etiqueta NFC</label>
    <button type="button">📱 Escanear NFC</button>
    <small>Esta etiqueta NFC está asociada al bien</small>
  </div>
</form>
```