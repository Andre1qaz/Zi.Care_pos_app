<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Invoice;
use App\Models\Payment;
use App\Repositories\InvoiceRepository;
use Phalcon\Di\Di;

class InvoiceService
{
    private InvoiceRepository $repository;

    public function __construct()
    {
        $this->repository = Di::getDefault()->getShared('invoiceRepository');
    }

    public function list(array $filters, int $page, int $limit): array
    {
        return $this->repository->findAll($filters, $page, $limit);
    }

    public function get(int $id): array
    {
        $invoice = $this->repository->findWithDetails($id);
        if (!$invoice) {
            throw new NotFoundException('Invoice not found');
        }
        return $invoice;
    }

    public function calculatePaymentStatus(float $totalAmount, float $paidAmount, ?string $dueDate = null): string
    {
        $outstanding = round($totalAmount - $paidAmount, 2);
        
        if ($paidAmount <= 0) {
            return 'unpaid';
        }
        
        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }
        
        if ($outstanding > 0 && $dueDate && strtotime($dueDate) < time()) {
            return 'overdue';
        }
        
        return 'partial';
    }

    public function addPayment(int $invoiceId, array $paymentData, int $cashierId): array
    {
        $invoice = Invoice::findFirst($invoiceId);
        if (!$invoice) {
            throw new NotFoundException('Invoice not found');
        }

        $paymentAmount = round((float) ($paymentData['amount'] ?? 0), 2);
        if ($paymentAmount <= 0) {
            throw new ValidationException(['Payment amount must be greater than 0']);
        }

        $outstandingBalance = round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2);
        if ($paymentAmount > $outstandingBalance) {
            throw new ValidationException(['Payment amount cannot exceed outstanding balance']);
        }

        $payment = new Payment();
        $payment->invoice_id = $invoiceId;
        $payment->cashier_id = $cashierId;
        $payment->payment_method = $paymentData['payment_method'];
        $payment->provider = $paymentData['provider'] ?? null;
        $payment->payment_reference = $paymentData['payment_reference'] ?? null;
        $payment->amount = $paymentAmount;
        $payment->payment_status = 'paid';
        $payment->payment_time = date('Y-m-d H:i:s');
        $payment->notes = $paymentData['notes'] ?? null;

        if (!$payment->save()) {
            throw new ValidationException(['Failed to create payment record']);
        }

        $invoice->paid_amount += $paymentAmount;
        $invoice->payment_status = $this->calculatePaymentStatus(
            (float) $invoice->total_amount,
            (float) $invoice->paid_amount,
            $invoice->due_date
        );

        if (!$invoice->save()) {
            throw new ValidationException(['Failed to update invoice']);
        }

        // --- SINKRONISASI ODOO: PEMBAYARAN LANJUTAN ---
        try {
            if (!empty($invoice->odoo_move_id)) {
                $odooSync = Di::getDefault()->getShared('odooSyncService');
                $odooSync->syncPayment(
                    (int) $invoice->odoo_move_id, 
                    $paymentAmount, 
                    $paymentData['payment_method'] ?? 'cash'
                );
            }
        } catch (\Exception $e) {
            // Abaikan agar gagal sync tidak mempengaruhi database lokal
        }

        return [
            'payment' => $payment->toArray(),
            'invoice' => $invoice->toArray()
        ];
    }

    public function getPaymentHistory(int $invoiceId): array
    {
        $invoice = Invoice::findFirst($invoiceId);
        if (!$invoice) {
            throw new NotFoundException('Invoice not found');
        }

        $payments = Payment::find([
            'conditions' => 'invoice_id = :invoice_id:',
            'bind' => ['invoice_id' => $invoiceId],
            'order' => 'created_at ASC'
        ]);

        $paymentsList = [];
        foreach ($payments as $p) {
            $paymentsList[] = $p->toArray();
        }

        return [
            'invoice' => $invoice->toArray(),
            'payments' => $paymentsList
        ];
    }

    public function getOutstandingInvoices(array $filters = [], int $page = 1, int $limit = 20): array
    {
        $filters['payment_status'] = ['unpaid', 'partial', 'overdue'];
        return $this->repository->findAll($filters, $page, $limit);
    }

    public function generatePdf(int $id): string
    {
        $data = $this->get($id);
        $invoice = $data['invoice'];
        $details = $data['details'];
        $payments = $data['payments'] ?? [];
        $cashier = $data['cashier']['name'] ?? 'N/A';
        $customer = $data['customer'] ?? ['customer_name' => 'Walk-in Customer', 'phone' => '-', 'email' => '-', 'address' => '-'];

        $rows = '';
        foreach ($details as $index => $item) {
            $rows .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td class="text-center">%s</td>
                    <td class="text-center">%d</td>
                    <td class="text-right">%s</td>
                    <td class="text-right">%s</td>
                </tr>',
                htmlspecialchars($item['product_name']),
                htmlspecialchars($item['product_code'] ?? '-'),
                $item['quantity'],
                $this->formatMoney((float) $item['price']),
                $this->formatMoney((float) $item['subtotal'])
            );
        }

        $statusBadge = $this->getStatusBadge($invoice['payment_status']);
        $outstandingBalance = (float) ($invoice['outstanding_balance'] ?? 0);
        $changeAmount = (float) ($invoice['change_amount'] ?? 0);

        $outstandingRow = $outstandingBalance > 0 
            ? '<div class="summary-row" style="color: #dc3545;">
                <span>Sisa Pembayaran:</span>
                <span>' . $this->formatMoney($outstandingBalance) . '</span>
            </div>' 
            : '';
        
        $changeRow = $changeAmount > 0 
            ? '<div class="summary-row" style="color: #28a745;">
                <span>Kembalian:</span>
                <span>' . $this->formatMoney($changeAmount) . '</span>
            </div>' 
            : '';

        $paymentHistorySection = '';
        if (!empty($payments)) {
            $paymentRows = '';
            foreach ($payments as $payment) {
                $paymentRows .= sprintf(
                    '<tr>
                        <td>%s</td>
                        <td>%s</td>
                        <td class="text-right">%s</td>
                        <td>%s</td>
                    </tr>',
                    date('d M Y, H:i', strtotime($payment['payment_time'] ?? $payment['created_at'])),
                    ucfirst($payment['payment_method']),
                    $this->formatMoney((float) $payment['amount']),
                    $payment['cashier_name'] ?? $cashier
                );
            }

            $paymentHistorySection = <<<HTML
            <div class="payment-history">
                <h3>Riwayat Pembayaran</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                            <th>Kasir</th>
                        </tr>
                    </thead>
                    <tbody>{$paymentRows}</tbody>
                </table>
            </div>
HTML;
        }

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {$invoice['invoice_number']}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; font-size: 12px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header-left h1 { margin: 0; color: #333; }
        .header-left p { margin: 5px 0; color: #666; }
        .header-right { text-align: right; }
        .invoice-number { font-size: 24px; font-weight: bold; color: #333; }
        .status { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-partial { background: #cce5ff; color: #004085; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .status-overdue { background: #f5c6cb; color: #721c24; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-section h3 { margin: 0 0 10px 0; font-size: 14px; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .info-row { display: flex; margin-bottom: 8px; }
        .info-label { width: 120px; color: #666; }
        .info-value { font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f8f9fa; border: 1px solid #ddd; padding: 10px; text-align: left; font-weight: bold; color: #333; }
        td { border: 1px solid #ddd; padding: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { background: #f8f9fa; padding: 15px; border-radius: 4px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .summary-row.total { font-size: 16px; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 10px; }
        .payment-history { margin-top: 30px; }
        .payment-history h3 { margin: 0 0 15px 0; font-size: 14px; color: #333; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1>INVOICE</h1>
                <p>Point of Sale System</p>
            </div>
            <div class="header-right">
                <div class="invoice-number">{$invoice['invoice_number']}</div>
                <div style="margin-top: 10px;">{$statusBadge}</div>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-section">
                <h3>Informasi Invoice</h3>
                <div class="info-row">
                    <span class="info-label">Tanggal:</span>
                    <span class="info-value">{$this->formatDateTime($invoice['created_at'])}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Kasir:</span>
                    <span class="info-value">{$cashier}</span>
                </div>
            </div>
            <div class="info-section">
                <h3>Informasi Pelanggan</h3>
                <div class="info-row">
                    <span class="info-label">Nama:</span>
                    <span class="info-value">{$customer['customer_name']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Telepon:</span>
                    <span class="info-value">{$customer['phone']}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{$customer['email']}</span>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th class="text-center">Kode</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <span>Total Tagihan:</span>
                <span>{$this->formatMoney((float)$invoice['total_amount'])}</span>
            </div>
            <div class="summary-row">
                <span>Total Dibayar:</span>
                <span>{$this->formatMoney((float)$invoice['paid_amount'])}</span>
            </div>
            {$outstandingRow}
            {$changeRow}
        </div>

        {$paymentHistorySection}

        <div class="footer">
            <p>Terima kasih atas transaksi Anda!</p>
            <p>Generated on {$this->formatDateTime(date('Y-m-d H:i:s'))}</p>
        </div>
    </div>
</body>
</html>
HTML;

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function getStatusBadge(string $status): string
    {
        $badges = [
            'paid' => '<span class="status status-paid">LUNAS</span>',
            'partial' => '<span class="status status-partial">SEBAGIAN</span>',
            'unpaid' => '<span class="status status-unpaid">BELUM BAYAR</span>',
            'overdue' => '<span class="status status-overdue">TERLAMBAT</span>'
        ];
        return $badges[$status] ?? '<span class="status status-unpaid">' . strtoupper($status) . '</span>';
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }

    private function formatDateTime(string $dateStr): string
    {
        return date('d M Y, H:i', strtotime($dateStr));
    }
}