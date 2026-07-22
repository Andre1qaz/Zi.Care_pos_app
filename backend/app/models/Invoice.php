<?php

declare(strict_types=1);

namespace App\Models;

use Phalcon\Mvc\Model;

class Invoice extends Model
{
    public ?int $id = null;
    public string $invoice_number;
    public ?int $customer_id = null;
    public int $cashier_id;
    public string $payment_method;
    public float $total_amount;
    public float $paid_amount;
    public float $outstanding_balance;
    public float $change_amount;
    public string $payment_status;
    public string $sync_status = 'pending';
    public ?string $odoo_move_id = null;
    public ?string $due_date = null;

    public function initialize(): void
    {
        $this->setSource('invoices');
        $this->belongsTo('customer_id', Customer::class, 'id', ['alias' => 'customer']);
        $this->belongsTo('cashier_id', User::class, 'id', ['alias' => 'cashier']);
        $this->hasMany('id', InvoiceDetail::class, 'invoice_id', ['alias' => 'details']);
        $this->hasMany('id', Payment::class, 'invoice_id', ['alias' => 'payments']);
    }

    public function beforeUpdate(): void
    {
        // Auto-calculate outstanding balance
        $this->outstanding_balance = $this->total_amount - $this->paid_amount;
    }

    public function beforeCreate(): void
    {
        // Auto-calculate outstanding balance
        $this->outstanding_balance = $this->total_amount - $this->paid_amount;
        
        // Set default due date to 7 days from now if not set
        if ($this->due_date === null) {
            $this->due_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        }
    }
}
