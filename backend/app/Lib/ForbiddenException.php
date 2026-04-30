<?php

namespace App\Lib;

/**
 * Excepción para errores de autorización (403)
 */
class ForbiddenException extends \Exception
{
    protected $code = 403;
    protected $message = 'Acceso denegado';
}
