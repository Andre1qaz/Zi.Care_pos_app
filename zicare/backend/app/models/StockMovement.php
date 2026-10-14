<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class StockMovement extends Model
{
    public ?int $id = null;
    public int $product_id;
    public string $movement_type;
    public int $quantity = 0;
    public int $stock_before = 0;
    public int $stock_after = 0;
    public ?string $reason = null;
    public ?string $reference = null;
    public ?int $invoice_id = null;
    public ?int $user_id = null;

    public function initialize(): void
    {
        $this->setSource('stock_movements');
        $this->belongsTo('product_id', Product::class, 'id', ['alias' => 'product']);
        $this->belongsTo('invoice_id', Invoice::class, 'id', ['alias' => 'invoice']);
        $this->belongsTo('user_id', User::class, 'id', ['alias' => 'user']);
    }
}
