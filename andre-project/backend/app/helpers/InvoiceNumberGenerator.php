<?php

declare(strict_types=1);

namespace App\Helpers;

use Phalcon\Di\Di;

class InvoiceNumberGenerator
{
    public static function generate(): string
    {
        $db = Di::getDefault()->getShared('db');
        $date = date('Y-m-d');
        $prefix = 'INV-' . date('Ymd') . '-';

        $db->execute(
            'INSERT INTO invoice_sequences (sequence_date, last_number) VALUES (?, 1)
             ON DUPLICATE KEY UPDATE last_number = last_number + 1',
            [$date]
        );

        $result = $db->query(
            'SELECT last_number FROM invoice_sequences WHERE sequence_date = ?',
            [$date]
        )->fetch();

        $number = str_pad((string) $result['last_number'], 4, '0', STR_PAD_LEFT);

        return $prefix . $number;
    }
}
