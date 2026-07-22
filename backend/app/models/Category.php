<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class Category extends Model
{
    public ?int $id = null;
    public string $category_name;
    public ?string $description = null;

    public function initialize(): void
    {
        $this->setSource('categories');
        $this->hasMany('id', Product::class, 'category_id', ['alias' => 'products']);
    }
}
