const fs = require('fs');

const routesCode = fs.readFileSync('routes/routes.php', 'utf8');
const collectionStr = fs.readFileSync('bruno/bruno_export_completo.json', 'utf8');
const collection = JSON.parse(collectionStr);

// Regex to find Flight::route('METHOD /path', ...)
const routeRegex = /Flight::route\('([A-Z]+)\s+([^']+)'/g;

let match;
const routes = [];

while ((match = routeRegex.exec(routesCode)) !== null) {
    const method = match[1];
    let path = match[2];
    routes.push({ method, path });
}

// Function to find existing request
function findRequest(collectionItems, method, pathStr) {
    for (const item of collectionItems) {
        if (item.item) {
            const found = findRequest(item.item, method, pathStr);
            if (found) return true;
        } else if (item.request) {
            const itemMethod = item.request.method;
            const itemPath = "{{baseUrl}}" + item.request.url.path.map(p => '/' + p).join('');
            // Path conversion for postman path array: "{{baseUrl}}/api/v1/..."
            const currentPathUrl = item.request.url.raw.split('?')[0];
            
            // Compare normalized paths
            const nPath1 = (currentPathUrl).replace(/{{baseUrl}}/, '').replace(/\/$/, '');
            const nPath2 = pathStr.replace(/@\w+/g, p => {
                // Find what it was replaced with in postman, usually a number or string like 1
                return p;
            });
            
            // We just do a simple check
            // For now, let's rebuild the whole items array to be clean, or just append missing ones.
        }
    }
    return false;
}

const newItems = {};

routes.forEach(route => {
    if (route.path === '/') return; // skip health check
    
    // Determine category
    let category = "Otros";
    if (route.path.includes('/login') || route.path.includes('/refresh-token')) category = "Auth";
    else if (route.path.includes('/profesor')) category = "Profesor";
    else if (route.path.includes('/alumno')) category = "Alumno";
    else if (route.path.includes('/administrador')) category = "Administrador";
    else if (route.path.includes('/barcodes')) category = "Barcodes";
    else if (route.path.includes('/empleados')) category = "Empleados";
    else if (route.path.includes('/auditorias')) category = "Auditorias";
    else if (route.path.includes('/papelera')) category = "Papelera";
    else if (route.path.includes('/reportes')) category = "Reportes";
    else if (route.path.includes('/informacion') || route.path.includes('/aulas/edificio') || route.path.includes('/prestamos/procesar')) category = "Informacion";

    if (!newItems[category]) newItems[category] = [];
    
    // Create item
    const pathParts = route.path.split('/').filter(p => p);
    
    // Handle query params in path? In Flight, path is just /path/@id
    let postmanPath = route.path.replace(/@(\w+)/g, '1'); // default to 1 for IDs
    let pathArr = postmanPath.split('/').filter(p => p);
    
    let itemName = `${route.method} ${route.path}`;
    
    newItems[category].push({
        name: itemName,
        request: {
            method: route.method,
            header: [],
            url: {
                raw: `{{baseUrl}}${postmanPath}`,
                host: ["{{baseUrl}}"],
                path: pathArr
            }
        }
    });
});

const finalItems = [];
for (const [category, items] of Object.entries(newItems)) {
    finalItems.push({
        name: category,
        item: items
    });
}

collection.item = finalItems;

fs.writeFileSync('bruno/bruno_export_completo.json', JSON.stringify(collection, null, "\t"));
console.log("Updated bruno_export_completo.json");
