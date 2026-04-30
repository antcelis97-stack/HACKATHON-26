<?php

namespace App\Lib;

/**
 * Excepción para errores de validación (400)
 */
class ValidationException extends \Exception
{
    protected $code = 400;
    protected $message = 'Datos inválidos';
    
    private array $errors = [];

    public function __construct(string $message, array $errors = [], int $code = 400)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
