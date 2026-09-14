<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;
use App\Exceptions\ValidationException;
use App\Helpers\InvoiceNumberGenerator;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Payment;
use App\Repositories\ProductRepository;
use Phalcon\Di\Di;

class TransactionService
{
    private ProductRepository $productRepository;
    private PaymentService $paymentService;
    private OdooSyncService $odooSyncService;

    public function __construct()
    {
        $di = Di::getDefault();
        $this->productRepository = $di->getShared('productRepository');
        $this->paymentService = $di->getShared('paymentService');
        $this->odooSyncService = $di->getShared('odooSyncService');
    }

    public function create(array $data, int $cashierId): array
    {
        $this->validate($data);

        $items = $data['items'];
        $payments = $data['payments'];

        $totalAmount = 0;
        $lineItems = [];

        foreach ($items as $item) {
            $product = $this->productRepository->findById((int) $item['product_id']);
            if (!$product) {
                throw new ValidationException(['items' => "Product ID {$item['product_id']} not found"]);
            }

            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                throw new ValidationException(['items' => 'Quantity must be greater than 0']);
            }

            $productType = $product->product_type ?? 'barang';

            if ($productType === 'barang') {
                if ($product->stock < $qty) {
                    throw new AppException("Insufficient stock for {$product->product_name}");
                }
            } elseif ($productType === 'jasa') {
                if ($product->service_status !== 'tersedia') {
                    throw new AppException("Service {$product->product_name} is not available");
                }
            }

            $subtotal = $product->price * $qty;
            $totalAmount += $subtotal;

            $lineItems[] = [
                'product_id' => $product->id,
                'quantity'   => $qty,
                'price'      => $product->price,
                'subtotal'   => $subtotal,
                'product_type' => $productType,
                'product_name' => $product->product_name,
                'product_code' => $product->product_code ?? 'SKU-000',
            ];
        }

        $totalPaid = array_sum(array_column($payments, 'amount'));
        
        $db = Di::getDefault()->getShared('db');
        $db->begin();

        try {
            $invoice = new Invoice();
            $invoice->invoice_number = InvoiceNumberGenerator::generate();
            
            // Integrasi Data Pelanggan yang Terhubung ke OdooSyncService
            $invoice->customer_id = $data['customer_id'] ?? null;
            $invoice->customer_name = $data['customer_name'] ?? 'Walk-in Customer';
            $invoice->customer_email = $data['customer_email'] ?? 'walkin@pos.com';
            $invoice->customer_phone = $data['customer_phone'] ?? '';
            
            $invoice->cashier_id = $cashierId;
            // Menyimpan metode pembayaran utama untuk keperluan sinkronisasi sederhana
            $invoice->payment_method = $data['payments'][0]['payment_method'] ?? 'cash'; 
            $invoice->total_amount = $totalAmount;
            $invoice->paid_amount = $totalPaid;
            
            $invoice->change_amount = ($totalPaid > $totalAmount) ? ($totalPaid - $totalAmount) : 0;
            $invoice->outstanding_balance = ($totalAmount > $totalPaid) ? ($totalAmount - $totalPaid) : 0;
            
            $invoice->payment_status = ($totalPaid < $totalAmount) ? 'partial' : 'paid';
            $invoice->sync_status = 'pending';

            if (!$invoice->save()) {
                throw new AppException('Failed to create invoice');
            }

            foreach ($lineItems as $line) {
                $detail = new InvoiceDetail();
                $detail->invoice_id = $invoice->id;
                $detail->assign($line);
                $detail->save();

                if ($line['product_type'] === 'barang') {
                    if (!$this->productRepository->decrementStock($line['product_id'], $line['quantity'])) {
                        throw new AppException('Failed to update stock');
                    }
                }
            }

            foreach ($payments as $paymentData) {
                $payment = new Payment();
                $payment->invoice_id = $invoice->id;
                $payment->payment_method = $paymentData['payment_method'];
                $payment->provider = $paymentData['provider'] ?? null;
                $payment->payment_reference = $paymentData['payment_reference'] ?? null;
                $payment->amount = (float) $paymentData['amount'];
                $payment->payment_status = 'paid';
                $payment->payment_time = date('Y-m-d H:i:s');
                $payment->save();
            }

            $audit = new AuditLog();
            $audit->user_id = $cashierId;
            $audit->activity = "Created transaction {$invoice->invoice_number} with status {$invoice->payment_status}";
            $audit->entity_type = 'invoice';
            $audit->entity_id = $invoice->id;
            $audit->save();

            $db->commit();
        } catch (\Exception $e) {
            $db->rollback();
            throw $e;
        }

        // Picu integrasi pipa sinkronisasi penuh Odoo
        $this->odooSyncService->queueSync((int) $invoice->id);

        return Di::getDefault()->getShared('invoiceRepository')->findWithDetails((int) $invoice->id);
    }

    private function validate(array $data): void
    {
        $errors = [];
        if (!isset($data['items']) || empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = 'At least one item is required';
        }
        if (!isset($data['payments']) || empty($data['payments']) || !is_array($data['payments'])) {
            $errors['payments'] = 'At least one payment is required';
        }
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }
}