<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class Product extends Model
{
    public ?int $id = null;
    public string $product_code;
    public string $product_name;
    public ?string $description = null;
    public string $product_type = 'barang';
    public ?string $service_status = null;
    public float $price;
    public int $stock;
    public ?int $category_id = null;
    public int $is_active = 1;

    public function initialize(): void
    {
        $this->setSource('products');
        $this->belongsTo('category_id', Category::class, 'id', ['alias' => 'category']);
    }
}
