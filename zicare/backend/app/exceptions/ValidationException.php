<?php

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    protected int $statusCode = 422;

    private array $errors;

    /**
     * @param array|string $errors A map/list of field errors, or a single message string.
     * @param string|null  $message Optional message when $errors is an array.
     */
    public function __construct(
        array|string $errors = [],
        ?string $message = null
    ) {
        if (is_string($errors)) {
            $message = $message ?? $errors;
            $errors = [$errors];
        }

        $this->errors = $errors;
        parent::__construct($message ?? 'Validation failed');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
