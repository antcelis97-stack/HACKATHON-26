<?php

namespace app\Lib;

use PDO;

/**
 * Logger - Sistema de logging estructurado
 * 
 * Usar para registrar eventos importantes del sistema.
 * Los logs se guardan en la tabla public.logs de PostgreSQL.
 */
class Logger
{
    public const DEBUG = 'DEBUG';
    public const INFO = 'INFO';
    public const WARNING = 'WARNING';
    public const ERROR = 'ERROR';
    public const CRITICAL = 'CRITICAL';

    /**
     * Loguear un mensaje
     */
    public static function log(string $nivel, string $mensaje, array $contexto = []): void
    {
        try {
            $pdo = getPgConnection();
            
            $stmt = $pdo->prepare("
                INSERT INTO public.logs (nivel, mensaje, contexto)
                VALUES (:nivel, :mensaje, :contexto)
            ");

            $stmt->execute([
                ':nivel' => $nivel,
                ':mensaje' => $mensaje,
                ':contexto' => json_encode($contexto)
            ]);

        } catch (\Exception $e) {
            // Fallback: si falla el guardado en BD, usar error_log
            error_log("[$nivel] $mensaje");
        }
    }

    public static function debug(string $mensaje, array $contexto = []): void
    {
        self::log(self::DEBUG, $mensaje, $contexto);
    }

    public static function info(string $mensaje, array $contexto = []): void
    {
        self::log(self::INFO, $mensaje, $contexto);
    }

    public static function warning(string $mensaje, array $contexto = []): void
    {
        self::log(self::WARNING, $mensaje, $contexto);
    }

    public static function error(string $mensaje, array $contexto = []): void
    {
        self::log(self::ERROR, $mensaje, $contexto);
    }

    public static function critical(string $mensaje, array $contexto = []): void
    {
        self::log(self::CRITICAL, $mensaje, $contexto);
    }
}
