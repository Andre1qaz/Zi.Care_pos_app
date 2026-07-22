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
            $rowClass = $index % 2 === 0 ? 'bg-white' : 'bg-gray-50';
            $rows .= sprintf(
                '<tr class="%s">
                    <td class="px-4 py-3 text-left font-medium">%s</td>
                    <td class="px-4 py-3 text-center text-sm text-gray-500">%s</td>
                    <td class="px-4 py-3 text-center">%d</td>
                    <td class="px-4 py-3 text-right">%s</td>
                    <td class="px-4 py-3 text-right text-gray-400">-</td>
                    <td class="px-4 py-3 text-right text-gray-400">-</td>
                    <td class="px-4 py-3 text-right font-semibold">%s</td>
                </tr>',
                $rowClass,
                htmlspecialchars($item['product_name']),
                htmlspecialchars($item['product_code'] ?? '-'),
                $item['quantity'],
                $this->formatMoney((float) $item['price']),
                $this->formatMoney((float) $item['subtotal'])
            );
        }

        $statusBadge = $this->getStatusBadge($invoice['payment_status']);

        $paymentHistorySection = '';
        if (!empty($payments) && $invoice['payment_status'] !== 'paid') {
            $paymentRows = '';
            foreach ($payments as $payment) {
                $paymentRows .= sprintf(
                    '<tr class="border-b border-gray-100">
                        <td class="px-4 py-2 text-sm">%s</td>
                        <td class="px-4 py-2 text-sm">%s</td>
                        <td class="px-4 py-2 text-right font-medium">%s</td>
                        <td class="px-4 py-2 text-sm">%s</td>
                    </tr>',
                    date('d M Y, H:i', strtotime($payment['payment_time'] ?? $payment['created_at'])),
                    ucfirst($payment['payment_method']),
                    $this->formatMoney((float) $payment['amount']),
                    $payment['cashier_name'] ?? $cashier
                );
            }

            $outstandingBalance = (float) ($invoice['outstanding_balance'] ?? 0);
            $paymentPercentage = $invoice['total_amount'] > 0 
                ? round((($invoice['paid_amount'] / $invoice['total_amount']) * 100), 1) 
                : 0;

            $paymentHistorySection = <<<HTML
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Riwayat Pembayaran</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 text-xs uppercase">
                            <th class="pb-2">Tanggal</th>
                            <th class="pb-2">Metode</th>
                            <th class="pb-2 text-right">Jumlah</th>
                            <th class="pb-2">Kasir</th>
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
        <body>
            <div class="container">
                {$paymentHistorySection}
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
            'paid' => '<span class="status-badge status-paid">LUNAS</span>',
            'partial' => '<span class="status-badge status-partial">SEBAGIAN</span>',
            'unpaid' => '<span class="status-badge status-unpaid">BELUM BAYAR</span>',
            'overdue' => '<span class="status-badge status-overdue">TERLAMBAT</span>'
        ];
        return $badges[$status] ?? '<span class="status-badge status-unpaid">' . strtoupper($status) . '</span>';
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