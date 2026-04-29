<?php

namespace App\Lib;

/**
 * Excepción para recursos no encontrados (404)
 */
class NotFoundException extends \Exception
{
    protected $code = 404;
    protected $message = 'Recurso no encontrado';
}
