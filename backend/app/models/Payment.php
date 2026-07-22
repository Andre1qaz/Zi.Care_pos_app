<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class Payment extends Model
{
    public ?int $id = null;
    public int $invoice_id;
    public ?int $cashier_id = null;
    public string $payment_method;
    public ?string $provider = null;
    public ?string $payment_reference = null;
    public float $amount;
    public string $payment_status;
    public ?string $payment_time = null;
    public ?string $notes = null;

    public function initialize(): void
    {
        $this->setSource('payments');
        $this->belongsTo('invoice_id', Invoice::class, 'id', ['alias' => 'invoice']);
        $this->belongsTo('cashier_id', User::class, 'id', ['alias' => 'cashier']);
    }
}
