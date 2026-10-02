<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    protected int $statusCode = 422;

    public function __construct(
        private array $errors,
        string $message = 'Validation failed'
    ) {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
