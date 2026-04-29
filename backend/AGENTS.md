# Code Review Rules

## PHP Standards

### General
- Use strict typing (`declare(strict_types=1)`)
- Use namespaced classes (PSR-4 autoloading)
- Use strict comparison (`===` not `==`)
- Never use `var`, use `public`, `private`, `protected`

### Functions
- Use return type hints
- Use type hints for parameters
- Keep functionsunder 50 lines

### Variables
- Use `const` for constants
- Use `private const` for class constants
- Declare property types when possible

### Code Style
- Use 4 spaces for indentation
- Opening brace on same line
- Use meaningful variable names
- Los comentarios DocBlock no cuentan para el límite de líneas

### Security
- Never expose sensitive data in responses
- Validate all input
- Use prepared statements for SQL
- Sanitize output (htmlspecialchars)

## Error Handling
- Throw exceptions, never suppress errors
- Usar ValidationException cuando aplica, o \Exception genéricos están bien
- Log errors with context
- Return proper HTTP status codes

## Documentation
- Document public methods with DocBlock
- Document parameters and return types (puede ser formato simple /** @param type $name */)
- Keep comments meaningful and current
- Los DocBlocks pueden ser mínimos - no se requiere formato exacto @param/@return
- Para métodos privados con nombres claros, pueden omitirse los Docs