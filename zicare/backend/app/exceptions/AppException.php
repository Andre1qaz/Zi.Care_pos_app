<?php

declare(strict_types=1);

namespace App\Exceptions;

class AppException extends \Exception
{
    protected int $statusCode = 400;

    public function __construct(string $message = '', ?int $statusCode = null)
    {
        parent::__construct($message);

        // Only override the class default when an explicit code is given,
        // otherwise subclasses (e.g. NotFoundException => 404) keep theirs.
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
