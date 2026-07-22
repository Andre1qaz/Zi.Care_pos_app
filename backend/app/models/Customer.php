<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class Customer extends Model
{
    public ?int $id = null;
    public string $customer_name;
    public ?string $phone = null;
    public ?string $email = null;
    public ?string $address = null;

    public function initialize(): void
    {
        $this->setSource('customers');
        $this->hasMany('id', Invoice::class, 'customer_id', ['alias' => 'invoices']);
    }
}
