<?php

declare(strict_types=1);

namespace App\Repositories;

use Phalcon\Di\Di;

abstract class BaseRepository
{
    protected function db()
    {
        return Di::getDefault()->getShared('db');
    }
}
