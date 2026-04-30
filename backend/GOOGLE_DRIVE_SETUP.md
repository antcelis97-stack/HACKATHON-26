# Integración de Google Drive para Subida de Archivos

Este documento explica cómo funciona el módulo de subida de imágenes a Google Drive, qué dependencias utiliza y los pasos necesarios para instalarlo en otro proyecto.

## 1. ¿Cómo funciona?

El sistema utiliza la API de Google Drive para almacenar archivos de forma remota, evitando saturar el servidor local.

1.  **Recepción**: El backend recibe un archivo mediante una petición `POST` (multipart/form-data).
2.  **Procesamiento**: Se valida el tamaño y tipo de archivo. Por defecto está configurado para imágenes (JPG, PNG), pero se puede extender para aceptar PDFs o documentos de Word modificando la constante `ALLOWED_TYPES` en el controlador.
3.  **Subida**: La clase `GoogleDriveService` utiliza las credenciales de Google para enviar el archivo binario a una carpeta específica en Drive.
4.  **Respuesta**: Google Drive devuelve un `file_id` y un `web_view_link`.
5.  **Persistencia**: Estos datos se guardan en la base de datos (columnas `foto_url` y `foto_drive_id`) para poder visualizar la imagen después.

---

## 2. Dependencias y Requisitos

### Backend (PHP)
*   **PHP >= 8.2**
*   **Google API Client**: `google/apiclient`. Es la librería oficial de Google para PHP.
*   **vlucas/phpdotenv**: Para manejar las variables de entorno.

### Google Cloud (Infraestructura)
*   Un proyecto en **Google Cloud Console**.
*   **Google Drive API** habilitada.
*   **OAuth 2.0 Credentials** (ID de cliente y Secreto).

---

## 3. Guía de Instalación Paso a Paso

### Paso 1: Configuración en Google Cloud
1.  Ve a [Google Cloud Console](https://console.cloud.google.com/).
2.  Crea un nuevo proyecto.
3.  En el buscador, busca **"Google Drive API"** y haz clic en **Habilitar**.
4.  Configura la **Pantalla de consentimiento de OAuth** (OAuth Consent Screen) como "External" (o "Internal" si estás en una organización).
5.  Ve a **Credenciales** -> **Crear credenciales** -> **ID de cliente de OAuth**.
    *   Tipo de aplicación: **Aplicación Web**.
    *   URI de redireccionamiento autorizados: Añade la URL de tu backend (ej. `http://localhost:8082/oauth2callback`).
6.  Copia el **Client ID** y el **Client Secret**.

### Paso 2: Instalación de Librerías
En la terminal de tu nuevo proyecto, ejecuta:
```bash
composer require google/apiclient
```

### Paso 3: Variables de Entorno
Añade lo siguiente a tu archivo `.env`:
```env
GOOGLE_CLIENT_ID=tu_client_id_aqui
GOOGLE_CLIENT_SECRET=tu_client_secret_aqui
GOOGLE_REDIRECT_URI=http://localhost:8082/oauth2callback
GOOGLE_DRIVE_FOLDER_ID=id_de_la_carpeta_donde_se_guardaran_los_archivos
```
*Nota: Para obtener el `FOLDER_ID`, crea una carpeta en Drive y copia el ID que aparece al final de la URL.*

### Paso 4: Copiar Archivos del Código
Debes llevarte estos archivos a tu nuevo proyecto:
1.  `app/Lib/GoogleDriveService.php`: El motor que habla con Google.
2.  `app/Controllers/GoogleDriveController.php`: El controlador que maneja las peticiones.
3.  `token.json`: Este archivo se generará automáticamente después de la primera autorización (o debes crearlo inicialmente siguiendo el flujo de OAuth).

### Paso 5: Actualizar la Base de Datos
Asegúrate de que tu tabla de destino (ej. `bienes`) tenga estas columnas:
```sql
ALTER TABLE bienes ADD COLUMN foto_url TEXT;
ALTER TABLE bienes ADD COLUMN foto_drive_id VARCHAR(100);
```

---

## 4. Autorización Inicial (Generar token.json)

La primera vez que uses el servicio, necesitarás autorizar la aplicación para que Google genere un `refresh token`. 

1.  Crea un endpoint temporal que llame a `$driveService->getAuthUrl()`.
2.  Visita esa URL en tu navegador, inicia sesión con tu cuenta de Google y acepta los permisos.
3.  Google te redirigirá a tu `REDIRECT_URI` con un código en la URL (`?code=...`).
4.  Captura ese código y llama a `$driveService->saveToken($code)`. Esto creará el archivo `token.json` en la raíz de tu proyecto.

---

## 5. Endpoints de Ejemplo

### Subir Imagen
*   **Método**: `POST`
*   **URL**: `/api/v1/bienes/1/foto`
*   **Body**: `multipart/form-data` con campo `foto` (archivo).

### Eliminar Imagen
*   **Método**: `DELETE`
*   **URL**: `/api/v1/bienes/1/foto/ID_DE_DRIVE`
