<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class InvoiceDetail extends Model
{
    public ?int $id = null;
    public int $invoice_id;
    public int $product_id;
    public int $quantity;
    public float $price;
    public float $subtotal;

    public function initialize(): void
    {
        $this->setSource('invoice_details');
        $this->belongsTo('invoice_id', Invoice::class, 'id', ['alias' => 'invoice']);
        $this->belongsTo('product_id', Product::class, 'id', ['alias' => 'product']);
    }
}
