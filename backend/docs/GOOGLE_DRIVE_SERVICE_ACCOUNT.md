# Guía de Integración: Google Drive con Cuenta de Servicio

Esta guía explica cómo implementar la subida de archivos a Google Drive utilizando una **Cuenta de Servicio**, ideal para procesos automáticos de servidor a servidor sin intervención del usuario.

## 1. Cuenta de Servicio

Ya contamos con la siguiente cuenta de servicio para este módulo:
`bolsa-de-trabajo@modulo-de-egresaods.iam.gserviceaccount.com`

### ¿Por qué usar una Cuenta de Servicio?
A diferencia de OAuth 2.0, no requiere que un usuario inicie sesión y autorice en el navegador cada vez que el token expire. La cuenta de servicio actúa como un "usuario bot" o entidad de servicio con su propia identidad.

---

## 2. Requisitos y Librerías

### Librería PHP
Es obligatorio tener instalada la librería oficial de Google. En tu archivo `composer.json` ya está incluida:

```bash
composer require google/apiclient
```

### Configuración en Google Cloud
1.  **Habilitar API**: Asegúrate de que la "Google Drive API" esté habilitada en el proyecto de Google Cloud.
2.  **Archivo de Llave (JSON)**: Debes descargar el archivo JSON de credenciales de la cuenta de servicio desde la consola de Google Cloud (Sección *Credenciales* -> *Cuentas de servicio* -> *Claves* -> *Añadir clave*).
3.  **Seguridad**: Guarda este archivo en una carpeta segura (fuera de la carpeta pública `public/` o `web/`) y **NUNCA** lo subas al repositorio. Agrégalo al archivo `.gitignore`.

---

## 3. PASO CRUCIAL: Permisos en Drive

La cuenta de servicio **no tiene acceso a tus carpetas personales** de forma predeterminada.
1.  Crea una carpeta en tu Google Drive personal (ej. "Fotos Egresados").
2.  Haz clic derecho en la carpeta -> **Compartir**.
3.  Agrega el correo: `bolsa-de-trabajo@modulo-de-egresaods.iam.gserviceaccount.com`
4.  Otórgale permisos de **Editor**.
5.  Copia el ID de la carpeta desde la URL (es la cadena de letras y números al final).

---

## 4. Implementación en PHP (Flight)

### Inicialización del Servicio
```php
use Google\Client;
use Google\Service\Drive;

function obtenerServicioDrive() {
    $cliente = new Client();
    // Ruta al archivo JSON descargado de Google Cloud
    $cliente->setAuthConfig(__DIR__ . '/../../config/google-key.json');
    $cliente->addScope(Drive::DRIVE_FILE);
    
    return new Drive($cliente);
}
```

### Ejemplo de Subida de Archivo
```php
public static function subirArchivo($nombreArchivo, $rutaTemporal, $tipoMime) {
    $servicio = obtenerServicioDrive();
    
    $metadatosArchivo = new Drive\DriveFile([
        'name' => $nombreArchivo,
        'parents' => ['ID_DE_LA_CARPETA_COMPARTIDA'] // ID de la carpeta compartida arriba
    ]);

    $contenido = file_get_contents($rutaTemporal);

    $archivo = $servicio->files->create($metadatosArchivo, [
        'data' => $contenido,
        'mimeType' => $tipoMime,
        'uploadType' => 'multipart',
        'fields' => 'id, webViewLink'
    ]);

    return [
        'id' => $archivo->id,
        'url' => $archivo->webViewLink
    ];
}
```

---

## 5. Integración con la Base de Datos

### Cambios necesarios
La base de datos actual ya cuenta con las columnas necesarias en las tablas clave. **No necesitas cambiar nada en el esquema (schema)**, solo asegurarte de guardar los datos correctamente.

#### Tabla `egresados`
Columnas actuales:
- `url_foto_drive`: Para guardar el enlace (`webViewLink`) de la foto.
- `url_cv_drive`: Para guardar el enlace del PDF del currículum (CV).

#### Tabla `empresas`
Columna actual:
- `url_convenio_drive`: Para el documento legal o convenio de la empresa.

### Lógica de Persistencia (SQL)
Cuando recibas la respuesta de Google Drive, debes actualizar el registro correspondiente:

```sql
-- Ejemplo para actualizar el CV de un egresado
UPDATE egresados 
SET url_cv_drive = 'https://drive.google.com/file/d/ID_ARCHIVO/view'
WHERE cve_alumno = 'MATRICULA_AQUI';
```

---

## 6. Recomendación para Visualización Directa

Google Drive por defecto muestra los archivos en su propio visor. Si quieres que las imágenes se carguen directamente en una etiqueta `<img>` en el frontend, puedes usar este formato de URL:

**Enlace de Drive:** `https://drive.google.com/file/d/ID_ARCHIVO/view`
**Enlace de Visualización Directa:** `https://lh3.googleusercontent.com/u/0/d/ID_ARCHIVO`

> [!TIP]
> Es recomendable guardar también el **ID de Drive** en una columna adicional. Esto te permitirá **eliminar** o **reemplazar** el archivo en la nube desde el sistema si el usuario decide actualizar su documento.
