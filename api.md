# Guía de Integración: API DENUE (INEGI) para Bolsa de Trabajo Universitaria

Esta documentación detalla cómo consumir la API del Directorio Estadístico Nacional de Unidades Económicas (DENUE) para filtrar empresas por geolocalización y afinidad académica (Carreras).

## 1. Requisitos Previos
1. **Token de Acceso:** Solicítalo gratuitamente en el [Portal de Desarrolladores del INEGI](https://www.inegi.org.mx/app/api/denue/v1/tokenVerify.aspx).
2. **Coordenadas de Referencia:** Latitud y longitud de tu localidad (ej. Nayarit: `21.50, -104.89`).

---

## 2. Endpoints Principales

La API utiliza peticiones `GET`. La estructura base para búsqueda por proximidad es:

`https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/[condicion]/[latitud,longitud]/[radio]/[token]`

### Parámetros:
* **condicion:** Palabra clave (ej. "software", "hospital", "despacho") o el código SCIAN.
* **latitud,longitud:** Coordenadas GPS.
* **radio:** Distancia en metros (máximo 5000 para búsquedas generales).
* **token:** Tu llave personal.

---

## 3. Lógica de Filtrado por Carreras (Mapeo SCIAN)

Para que el usuario filtre por carrera, debes mapear el nombre de la carrera a un código **SCIAN** (Sistema de Clasificación Industrial de América del Norte).

| Carrera | Palabra Clave | Código SCIAN Sugerido |
| :--- | :--- | :--- |
| **Ing. Sistemas / IT** | `computo` | `541510` (Desarrollo de software) |
| **Arquitectura** | `arquitectura` | `541310` (Servicios de arquitectura) |
| **Psicología** | `psicologia` | `621330` (Consultorios de psicología) |
| **Contaduría** | `contabilidad` | `541211` (Auditoría y contabilidad) |
| **Ing. Industrial** | `manufactura` | `31-33` (Sector manufacturero) |

---

## 4. Ejemplo de Implementación (JavaScript / Fetch)

```javascript
async function obtenerEmpresasPorCarrera(keyword, lat, lon) {
    const TOKEN = "TU_TOKEN_AQUI";
    const RADIO = "5000"; // 5 Kilómetros
    const URL = `https://www.inegi.org.mx/app/api/denue/v1/consulta/Buscar/${keyword}/${lat},${lon}/${RADIO}/${TOKEN}`;

    try {
        const response = await fetch(URL);
        const data = await response.json();
        
        // Filtrar solo empresas medianas y grandes para mejores oportunidades
        const empresasRelevantes = data.filter(empresa => 
            empresa.Estrato !== "0 a 5 personas"
        );

        console.log(empresasRelevantes);
    } catch (error) {
        console.error("Error al conectar con INEGI:", error);
    }
}