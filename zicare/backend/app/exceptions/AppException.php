<?php

declare(strict_types=1);

namespace App\Exceptions;

class AppException extends \Exception
{
    protected int $statusCode = 400;
    protected array $errors = [];

    public function __construct(string $message = '', ?int $statusCode = null, array $errors = [])
    {
        parent::__construct($message);

        // Only override the class default when an explicit code is given,
        // otherwise subclasses (e.g. NotFoundException => 404) keep theirs.
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }

        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Detail tambahan (mis. attempts_remaining) yang ikut dikirim ke klien.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
