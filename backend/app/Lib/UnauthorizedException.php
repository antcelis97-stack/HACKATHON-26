<?php

namespace App\Lib;

/**
 * Excepción para errores de autenticación (401)
 */
class UnauthorizedException extends \Exception
{
    protected $code = 401;
    protected $message = 'No autorizado';
}
