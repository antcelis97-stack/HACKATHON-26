# Documentación del Sistema de Inventario y Códigos QR

Este documento explica paso a paso la estructura y el funcionamiento del sistema de registro de bienes con generación dinámica de códigos QR.

---

## 📂 Estructura del Proyecto

```text
/Prueba QR
├── backend/
│   ├── db.php           # Conexión a PostgreSQL (PDO)
│   └── registrar.php    # Procesa la inserción en la BD
├── front/
│   ├── index.php        # Formulario de registro y visualización inmediata
│   ├── listar.php       # Inventario general con botones para ver QR
│   └── cleanup.php      # Utilidad para liberar espacio en disco
└── libreria/            # (Opcional) Librerías locales
```

---

## ⚙️ Funcionamiento Paso a Paso

### 1. Conexión a Base de Datos (`backend/db.php`)
Es el corazón del sistema. Utiliza **PDO (PHP Data Objects)** para conectarse de forma segura a PostgreSQL.
- **Configuración**: Define host, puerto (`5434`), nombre de DB (`inventario`) y credenciales.
- **Modo de error**: Configurado para lanzar excepciones, lo que facilita encontrar fallos.

### 2. Registro de un Nuevo Bien (`backend/registrar.php`)
Cuando el usuario envía el formulario desde el frontend:
1.  **Recepción**: Recibe `nombre`, `codigo_barras` y `descripcion`.
2.  **Inserción**: Ejecuta un `INSERT` en la tabla `bienes`.
3.  **Recuperación**: Usa `RETURNING cve_bien` para obtener el ID único generado por la base de datos.
4.  **Redirección**: Envía al usuario de vuelta a `front/index.php` enviando el `id` en la URL.

### 3. Visualización Dinámica de QR (`front/index.php`)
Después de registrar, esta página detecta el `id` en la URL y muestra el código QR:
1.  **Sin Almacenamiento**: No se guarda ninguna imagen en el servidor.
2.  **API Externa**: Utiliza `api.qrserver.com` para generar la imagen al vuelo.
3.  **Contenido del QR**: El código contiene la URL completa para ver ese producto en el servidor (ej: `http://localhost/.../index.php?id=123`).

### 4. Inventario de Bienes (`front/listar.php`)
Muestra una tabla con todos los registros:
1.  **Consulta**: Obtiene todos los bienes ordenados del más reciente al más antiguo.
2.  **Botón "Ver QR"**: Al presionarlo, se ejecuta una función JavaScript llamada `verQR()`.
3.  **Modal Moderno**: Se abre una ventana emergente que carga la imagen del QR desde la API externa usando la URL del producto.
4.  **Optimización**: Al no cargar archivos locales, la página es más rápida y no consume espacio en disco.

---

## 🚀 Optimización de Espacio (Ahorro de Disco)

El sistema ha sido migrado de un modelo de "Almacenamiento en Disco" a un modelo "Dinámico":
- **Antes**: Cada QR ocupaba ~20KB. Con 1000 registros, ocupabas 20MB.
- **Ahora**: Cada QR ocupa **0KB** en tu servidor. Se generan y muestran solo cuando alguien los necesita ver.

### Cómo liberar espacio:
Si tenías imágenes guardadas de versiones anteriores, el archivo `front/cleanup.php` las borra automáticamente para limpiar el servidor.

---

## 🛠️ Tecnologías Utilizadas
- **Backend**: PHP 8.x
- **Base de Datos**: PostgreSQL 14+
- **Frontend**: HTML5, CSS3 (Modal y Tablas Responsivas), JavaScript Vanilla.
- **QR Engine**: QRServer API (Vía HTTPS).

---
*Documentación generada para el Sistema de Inventario QR - 2026*
