<?php

namespace app\Lib;

/**
 * ResponseFormatter - Formateador de respuestas JSON estándar
 * 
 * SIEMPRE usa esta clase para retornar respuestas de API.
 * Asegura consistencia en todos los endpoints.
 */
class ResponseFormatter
{
    /**
     * Respuesta de éxito
     */
    public static function success(mixed $data = null, int $httpCode = 200): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => date('c')
            ]
        ];
    }

    /**
     * Respuesta con paginación
     */
    public static function paginated(array $data, int $page, int $limit, int $total): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => date('c'),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $limit)
                ]
            ]
        ];
    }

    /**
     * Respuesta de creación (201)
     */
    public static function created(mixed $data = null): array
    {
        return [
            'success' => true,
            'data' => $data,
            'meta' => [
                'timestamp' => date('c'),
                'message' => 'Recurso creado exitosamente'
            ]
        ];
    }

    /**
     * Respuesta sin contenido (204)
     */
    public static function noContent(): array
    {
        return [
            'success' => true,
            'meta' => [
                'timestamp' => date('c'),
                'message' => 'Operación completada'
            ]
        ];
    }

    /**
     * Respuesta de error
     */
    public static function error(string $message, string $code = 'ERROR', ?string $ticket = null): array
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ],
            'meta' => [
                'timestamp' => date('c')
            ]
        ];

        if ($ticket) {
            $response['error']['ticket'] = $ticket;
        }

        return $response;
    }

    /**
     * Error de validación (400)
     */
    public static function validationError(array $errors): array
    {
        return [
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Datos inválidos',
                'details' => $errors
            ],
            'meta' => [
                'timestamp' => date('c')
            ]
        ];
    }

    /**
     * No encontrado (404)
     */
    public static function notFound(string $resource = 'Recurso'): array
    {
        return self::error("$resource no encontrado", 'NOT_FOUND');
    }

    /**
     * No autorizado (401)
     */
    public static function unauthorized(string $message = 'No autorizado'): array
    {
        return self::error($message, 'UNAUTHORIZED');
    }

    /**
     * Prohibido (403)
     */
    public static function forbidden(string $message = 'Acceso denegado'): array
    {
        return self::error($message, 'FORBIDDEN');
    }

    /**
     * Servicio no disponible (503)
     */
    public static function serviceUnavailable(string $message = 'Servicio temporalmente no disponible'): array
    {
        return self::error($message, 'SERVICE_UNAVAILABLE');
    }
}
