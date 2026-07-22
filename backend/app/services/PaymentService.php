<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AppException;

class PaymentService
{
    private const CASH = 'cash';
    private const NON_CASH = ['qris', 'transfer', 'ewallet'];

    public function calculate(
        string $method,
        float $totalAmount,
        float $paidAmount,
        array $data
    ): array {
        $method = strtolower($method);

        if ($method === self::CASH || $method === 'tunai') {
            return $this->processCash($totalAmount, $paidAmount);
        }

        if (in_array($method, self::NON_CASH, true)) {
            return $this->processNonCash($method, $totalAmount, $paidAmount, $data);
        }

        throw new AppException("Invalid payment method: {$method}");
    }

    private function processCash(float $totalAmount, float $paidAmount): array
    {
        // Allow partial payments - paid_amount can be less than total_amount
        $changeAmount = max(0, $paidAmount - $totalAmount);
        
        // Determine payment status based on amount paid
        if ($paidAmount <= 0) {
            $paymentStatus = 'unpaid';
        } elseif ($paidAmount >= $totalAmount) {
            $paymentStatus = 'paid';
        } else {
            $paymentStatus = 'partial';
        }

        return [
            'paid_amount'    => $paidAmount,
            'change_amount'  => $changeAmount,
            'payment_status' => $paymentStatus,
        ];
    }

    private function processNonCash(string $method, float $totalAmount, float $paidAmount, array $data): array
    {
        if (empty($data['provider'])) {
            throw new AppException('Payment provider is required for non-cash payment');
        }

        if (empty($data['payment_reference'])) {
            throw new AppException('Payment reference is required for non-cash payment');
        }

        // Perbaikan: Mendukung pembayaran non-tunai parsial (Split Payment)
        // Sebelumnya selalu memaksa paid_amount menjadi $totalAmount
        $status = $data['payment_status'] ?? null;
        
        if (!$status) {
            if ($paidAmount <= 0) {
                $status = 'unpaid';
            } elseif ($paidAmount >= $totalAmount) {
                $status = 'paid';
            } else {
                $status = 'partial';
            }
        } elseif (!in_array($status, ['pending', 'paid', 'failed', 'cancelled', 'partial', 'unpaid'], true)) {
            $status = 'paid';
        }

        return [
            'paid_amount'    => $paidAmount,
            'change_amount'  => 0, // Non-tunai biasanya pas tanpa kembalian
            'payment_status' => $status,
        ];
    }
}