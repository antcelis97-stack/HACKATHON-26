<?php

namespace app\Lib;

use PDO;

/**
 * AuditLog - Servicio para registrar acciones de auditoría
 * 
 * Registrar operaciones de escritura críticas (CREATE, UPDATE, DELETE).
 */
class AuditLog
{
    /**
     * Registrar acción de auditoría
     */
    public static function log(
        string $accion,
        ?string $tabla = null,
        ?int $registroId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?int $userId = null
    ): void {
        try {
            $pdo = getPgConnection();

            $stmt = $pdo->prepare("
                INSERT INTO public.audit_log 
                (user_id, accion, tabla, registro_id, datos_anteriores, datos_nuevos)
                VALUES (:user_id, :accion, :tabla, :registro_id, :anteriores, :nuevos)
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':accion' => $accion,
                ':tabla' => $tabla,
                ':registro_id' => $registroId,
                ':anteriores' => $datosAnteriores ? json_encode($datosAnteriores) : null,
                ':nuevos' => $datosNuevos ? json_encode($datosNuevos) : null
            ]);

        } catch (\Exception $e) {
            // No fallar la operación principal si el audit log falla
            error_log("AuditLog: " . $e->getMessage());
        }
    }

    public static function create(string $tabla, int $registroId, array $datos, ?int $userId = null): void
    {
        self::log('CREATE', $tabla, $registroId, null, $datos, $userId);
    }

    public static function update(string $tabla, int $registroId, array $antes, array $despues, ?int $userId = null): void
    {
        self::log('UPDATE', $tabla, $registroId, $antes, $despues, $userId);
    }

    public static function delete(string $tabla, int $registroId, array $datos, ?int $userId = null): void
    {
        self::log('DELETE', $tabla, $registroId, $datos, null, $userId);
    }
}
