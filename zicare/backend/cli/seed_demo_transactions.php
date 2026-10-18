#!/usr/bin/env php
<?php

/**
 * Seed Data Demo Transaksi Zi.Care POS
 * ------------------------------------
 * Membangun dataset demo yang realistis:
 *   1. Menambah pelanggan Indonesia.
 *   2. Menghapus invoice lama (yang memakai snapshot harga lama) beserta
 *      move Odoo-nya, dan mengembalikan stok yang dikonsumsinya.
 *   3. Membuat 120 transaksi selama 60 hari terakhir lewat TransactionService
 *      supaya stok, kartu stok, pembayaran, audit log, dan outstanding balance
 *      ikut konsisten secara otomatis.
 *   4. Men-backdate created_at / payment_time / kartu stok dan me-renumber
 *      nomor invoice mengikuti tanggalnya.
 *
 * Sinkronisasi Odoo TIDAK dijalankan di sini (dinonaktifkan lewat env) agar
 * tidak men-spawn 120 worker latar. Jalankan belakangan dengan:
 *     php cli/sync_odoo.php
 *
 * Pemakaian: php cli/seed_demo_transactions.php
 *              [--count=120] [--days=60] [--today=8]
 *
 *   --today=N  jumlah transaksi yang di-stamp dengan tanggal hari ini (tanggal
 *              sebenarnya) supaya panel "Hari Ini" di dashboard selalu terisi,
 *              berapa pun kapan skrip ini dijalankan.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Nonaktifkan sinkronisasi Odoo selama seeding. Nilai ini dibaca config.php
// saat services.php dimuat, jadi harus di-set sebelum require services.php.
$_ENV['ODOO_SYNC_ENABLED'] = 'false';

use Phalcon\Di\Di;

$di = new Di();
require BASE_PATH . '/app/config/services.php';

$db      = $di->getShared('db');
$tx      = $di->getShared('transactionService');
$odoo    = $di->getShared('odooSyncService');

// ---- Argumen CLI ----
$count = 120;
$days  = 60;
$todayCount = 8;
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (str_starts_with($arg, '--count=')) $count = max(1, (int) substr($arg, 8));
    if (str_starts_with($arg, '--days='))  $days  = max(1, (int) substr($arg, 7));
    if (str_starts_with($arg, '--today=')) $todayCount = max(0, (int) substr($arg, 8));
}

$rand = static fn (int $min, int $max): int => random_int($min, $max);

echo "=== Seed Demo Transaksi Zi.Care POS ===\n";
echo "Target: {$count} transaksi, {$days} hari terakhir\n\n";

// ============================================================
// 1. Hapus invoice lama + kembalikan stok + hapus move Odoo
// ============================================================
echo "[1/4] Membersihkan invoice lama...\n";
$oldInvoices = $db->query("SELECT id, invoice_number, odoo_move_id FROM invoices")->fetchAll();
if (!$oldInvoices) {
    echo "      (tidak ada invoice lama)\n";
}
foreach ($oldInvoices as $old) {
    $invId = (int) $old['id'];

    if (!empty($old['odoo_move_id'])) {
        try {
            $odoo->deleteMove((int) $old['odoo_move_id']);
            echo "      Odoo move {$old['odoo_move_id']} dihapus.\n";
        } catch (\Throwable $e) {
            echo "      Gagal hapus Odoo move {$old['odoo_move_id']}: {$e->getMessage()}\n";
        }
    }

    // Kembalikan stok barang yang terjual pada invoice lama.
    $details = $db->query(
        "SELECT d.product_id, d.quantity, p.product_type
         FROM invoice_details d
         JOIN products p ON p.id = d.product_id
         WHERE d.invoice_id = ?",
        [$invId]
    )->fetchAll();
    foreach ($details as $d) {
        if (($d['product_type'] ?? '') === 'barang') {
            $db->execute(
                "UPDATE products SET stock = stock + ? WHERE id = ?",
                [(int) $d['quantity'], (int) $d['product_id']]
            );
        }
    }

    $db->execute("DELETE FROM stock_movements WHERE invoice_id = ?", [$invId]);
    $db->execute("DELETE FROM audit_logs WHERE entity_type = 'invoice' AND entity_id = ?", [$invId]);
    // invoice_details & payments ikut terhapus via ON DELETE CASCADE.
    $db->execute("DELETE FROM invoices WHERE id = ?", [$invId]);
    echo "      Invoice {$old['invoice_number']} dihapus, stok dikembalikan.\n";
}

// ============================================================
// 2. Tambah pelanggan realistis
// ============================================================
echo "\n[2/4] Menambah pelanggan...\n";
$customers = [
    ['Rahmawati Putri',      '081321004455', 'rahmawati@email.com',       'Jl. Kemang Raya No. 21, Jakarta'],
    ['Dedi Kurniawan',       '081577889900', 'dedi.kurniawan@email.com',  'Jl. Diponegoro No. 8, Semarang'],
    ['Sari Indah Lestari',   '081298776655', 'sari.lestari@email.com',    'Jl. Gajah Mada No. 45, Surabaya'],
    ['Agus Setiawan',        '081388772211', 'agus.setiawan@email.com',   'Jl. Ahmad Yani No. 12, Bandung'],
    ['Maya Puspita',         '081612349876', 'maya.puspita@email.com',    'Jl. Sudirman No. 100, Yogyakarta'],
    ['Bambang Sutrisno',     '081234556677', 'bambang.sutrisno@email.com','Jl. Gatot Subroto No. 55, Jakarta'],
    ['Ratna Dewi',           '081776554433', 'ratna.dewi@email.com',      'Jl. Pemuda No. 33, Medan'],
    ['Hendra Wijaya',        '081345667788', 'hendra.wijaya@email.com',   'Jl. Panjang No. 9, Tangerang'],
    ['Lestari Ningsih',      '081899001122', 'lestari.ningsih@email.com', 'Jl. Khatib Sulaiman No. 7, Padang'],
];
$added = 0;
foreach ($customers as $c) {
    $exists = $db->query("SELECT id FROM customers WHERE email = ?", [$c[2]])->fetch();
    if (!$exists) {
        $db->execute(
            "INSERT INTO customers (customer_name, phone, email, address) VALUES (?,?,?,?)",
            $c
        );
        $added++;
    }
}
echo "      {$added} pelanggan baru ditambahkan.\n";

// ============================================================
// 3. Siapkan pool produk (dengan pelacak stok lokal)
// ============================================================
echo "\n[3/4] Menyiapkan produk...\n";
$allProducts = $db->query(
    "SELECT id, price, stock, product_type, service_status
     FROM products
     WHERE is_active = 1"
)->fetchAll();

$priceMap  = [];
$stockMap  = [];
$barangPool = [];
$jasaPool   = [];
foreach ($allProducts as $p) {
    $pid = (int) $p['id'];
    $priceMap[$pid] = (float) $p['price'];
    if (($p['product_type'] ?? 'barang') === 'barang') {
        if ((int) $p['stock'] > 0) {
            $stockMap[$pid]   = (int) $p['stock'];
            $barangPool[]     = $pid;
        }
    } elseif (($p['service_status'] ?? '') === 'tersedia') {
        $jasaPool[] = $pid;
    }
}
echo '      Barang: ' . count($barangPool) . ', Jasa tersedia: ' . count($jasaPool) . "\n";

$customerIds = array_map(
    static fn (array $r): int => (int) $r['id'],
    $db->query("SELECT id FROM customers")->fetchAll()
);
$namedCustomerIds = array_values(array_filter($customerIds, static fn (int $id): bool => $id !== 1));

// ============================================================
// 4. Bangun transaksi
// ============================================================
echo "\n[4/4] Membuat {$count} transaksi...\n";
$created = [];
$failures = 0;
// "Hari ini" harus mengikuti jam MySQL (Asia/Jakarta), bukan jam PHP, karena
// panel "Hari Ini" di dashboard memakai CURDATE() MySQL. Kalau memakai tanggal
// PHP (timezone berbeda) transaksi "hari ini" bisa jatuh di tanggal salah dan
// panel dashboard tampak kosong.
$nowRow   = $db->query("SELECT NOW() AS n, CURDATE() AS d, HOUR(NOW()) AS h")->fetch();
$today    = new DateTimeImmutable($nowRow['d']);
$todayNow = new DateTimeImmutable($nowRow['n']);
$currentHour = max(8, (int) $nowRow['h']);

/**
 * Buat satu transaksi pada waktu tertentu, lengkap dengan side-effect konsisten
 * (stok, kartu stok, pembayaran, audit log) lewat TransactionService::create().
 * Hasilnya di-push ke $created untuk di-backdate & renumber belakangan.
 */
$makeTx = static function (DateTimeImmutable $dt) use (
    $rand, $jasaPool, $barangPool, &$stockMap, $priceMap,
    $namedCustomerIds, $tx, &$created, &$failures
): void {
    // --- pilih item ---
    $items = [];
    if ($jasaPool && $rand(1, 100) <= 70) {
        $nJasa = $rand(1, 2);
        for ($k = 0; $k < $nJasa; $k++) {
            $items[] = ['product_id' => $jasaPool[$rand(0, count($jasaPool) - 1)], 'quantity' => 1];
        }
    }
    $nBarang = $rand(1, 4);
    $picked  = [];
    for ($k = 0; $k < $nBarang && $barangPool; $k++) {
        $pid = null;
        for ($try = 0; $try < 25; $try++) {
            $cand = $barangPool[$rand(0, count($barangPool) - 1)];
            if (($stockMap[$cand] ?? 0) > 0 && !in_array($cand, $picked, true)) {
                $pid = $cand;
                break;
            }
        }
        if ($pid === null) {
            continue;
        }
        $qty  = $rand(1, min(6, $stockMap[$pid]));
        $picked[] = $pid;
        $items[]  = ['product_id' => $pid, 'quantity' => $qty];
        $stockMap[$pid] -= $qty; // pelacak lokal agar tidak melebihi stok saat create()
    }

    if (!$items) {
        $failures++;
        return;
    }

    // --- total (dihitung sendiri agar keputusan bayar bisa dibuat sebelum create) ---
    $total = 0.0;
    foreach ($items as $it) {
        $total += $priceMap[$it['product_id']] * $it['quantity'];
    }

    // --- metode bayar ---
    $roll = $rand(1, 100);
    if ($roll <= 55)      { $method = 'cash'; }
    elseif ($roll <= 75)  { $method = 'qris'; }
    elseif ($roll <= 90)  { $method = 'transfer'; }
    else                  { $method = 'ewallet'; }

    $provider = null;
    $ref      = null;
    if ($method === 'qris') {
        $provider = 'QRIS';
        $ref      = 'QR-' . random_int(100000, 999999);
    } elseif ($method === 'transfer') {
        $provider = $rand(0, 1) ? 'BCA' : 'Mandiri';
        $ref      = 'TRF-' . random_int(100000, 999999);
    } elseif ($method === 'ewallet') {
        $provider = $rand(0, 1) ? 'GoPay' : 'OVO';
        $ref      = 'EW-' . random_int(100000, 999999);
    }

    // --- status bayar: ~15% bayar sebagian (memamerkan fitur partial/outstanding) ---
    $isPartial = $total > 0 && $rand(1, 100) <= 15;
    $paid = $total;
    if ($isPartial) {
        $paid = (float) floor($total * ($rand(30, 70) / 100));
        if ($paid <= 0) {
            $paid = 1000.0;
        }
    }

    $payments = [[
        'payment_method'    => $method,
        'amount'            => $paid,
        'provider'          => $provider,
        'payment_reference' => $ref,
    ]];

    // --- kasir & pelanggan ---
    $r = $rand(1, 100);
    $cashierId = $r <= 70 ? 3 : ($r <= 90 ? 2 : 1);
    $customerId = ($rand(1, 100) <= 60 || !$namedCustomerIds)
        ? 1
        : $namedCustomerIds[$rand(0, count($namedCustomerIds) - 1)];

    try {
        $result = $tx->create([
            'items'       => $items,
            'payments'    => $payments,
            'customer_id' => $customerId,
        ], $cashierId);

        $created[] = [
            'id'      => (int) $result['invoice']['id'],
            'dt'      => $dt,
            'partial' => $isPartial,
        ];
    } catch (\Throwable $e) {
        $failures++;
        fwrite(STDERR, '      Gagal transaksi ' . $dt->format('Y-m-d H:i') . ": {$e->getMessage()}\n");
    }
};

// --- transaksi backdate (mayoritas, tersebar $days hari ke belakang) ---
for ($i = 1; $i <= $count; $i++) {
    $dayOffset = $rand(0, $days - 1);
    $dt = $today->modify("-{$dayOffset} days")->setTime($rand(8, 20), $rand(0, 59), $rand(0, 59));
    $makeTx($dt);
    if ($i % 20 === 0) {
        echo "      ...{$i}/{$count}\n";
    }
}

// --- transaksi "hari ini" (tanggal sebenarnya) agar panel dashboard terisi ---
// Dipisah dari backdate supaya jumlahnya pasti dan tidak bergantung pada
// kapan skrip dijalankan (rolled-past-midnight membuat "today" bergeser).
for ($i = 1; $i <= $todayCount; $i++) {
    $hour = $rand(8, max(8, $currentHour));
    $dt = $today->setTime($hour, $rand(0, 59), $rand(0, 59));
    $makeTx($dt);
}

echo '      Selesai: ' . count($created) . ' transaksi dibuat' .
    ($failures ? ", {$failures} gagal" : '') . ".\n";

// ============================================================
// 5. Backdate + renumber nomor invoice
// ============================================================
echo "\n[5] Backdate & renumber nomor invoice...\n";

// Fase 1: nomor sementara unik agar tidak bentrok unique constraint.
foreach ($created as $row) {
    $db->execute("UPDATE invoices SET invoice_number = CONCAT('TMP-', id) WHERE id = ?", [$row['id']]);
}

// Fase 2: urut kronologis, nomor mengikuti tanggal.
usort($created, static fn (array $a, array $b): int => $a['dt'] <=> $b['dt']);
$seqByDate = [];
foreach ($created as $row) {
    $d    = $row['dt']->format('Y-m-d');
    $ymd  = $row['dt']->format('Ymd');
    $seqByDate[$d] = ($seqByDate[$d] ?? 0) + 1;
    $num  = sprintf('INV-%s-%04d', $ymd, $seqByDate[$d]);
    $dtS  = $row['dt']->format('Y-m-d H:i:s');

    $db->execute("UPDATE invoices SET invoice_number = ?, created_at = ? WHERE id = ?", [$num, $dtS, $row['id']]);
    $db->execute("UPDATE payments SET payment_time = ? WHERE invoice_id = ?", [$dtS, $row['id']]);
    $db->execute("UPDATE stock_movements SET created_at = ? WHERE invoice_id = ?", [$dtS, $row['id']]);

    if ($row['partial']) {
        $due = $row['dt']->modify('+7 days')->format('Y-m-d H:i:s');
        $db->execute("UPDATE invoices SET due_date = ? WHERE id = ?", [$due, $row['id']]);
        // Invoice sebagian yang jatuh temponya sudah lewat ditandai overdue
        // agar fitur "overdue" ikut terlihat di laporan.
        if ($row['dt']->modify('+7 days') < $todayNow) {
            $db->execute("UPDATE invoices SET payment_status = 'overdue' WHERE id = ?", [$row['id']]);
        }
    }
}

// Fase 3: samakan counter harian dengan nomor yang dipakai.
foreach ($seqByDate as $d => $max) {
    $db->execute(
        "INSERT INTO invoice_sequences (sequence_date, last_number) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE last_number = VALUES(last_number)",
        [$d, $max]
    );
}
echo '      Renumber selesai untuk ' . count($seqByDate) . " hari.\n";

// ============================================================
// Ringkasan
// ============================================================
echo "\n=== Ringkasan ===\n";
foreach ($db->query("SELECT payment_status, COUNT(*) AS n FROM invoices GROUP BY payment_status ORDER BY n DESC")->fetchAll() as $row) {
    echo "  status {$row['payment_status']}: {$row['n']}\n";
}
foreach ($db->query("SELECT payment_method, COUNT(*) AS n FROM invoices GROUP BY payment_method ORDER BY n DESC")->fetchAll() as $row) {
    echo "  metode {$row['payment_method']}: {$row['n']}\n";
}
$totals = $db->query("SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS revenue, MIN(created_at) AS first, MAX(created_at) AS last FROM invoices")->fetch();
echo "  total invoice : {$totals['n']}\n";
echo "  pendapatan    : Rp " . number_format((float) $totals['revenue'], 0, ',', '.') . "\n";
echo "  rentang       : {$totals['first']} s/d {$totals['last']}\n";
$pending = $db->query("SELECT COUNT(*) AS n FROM invoices WHERE sync_status IN ('pending','failed')")->fetch();
echo "  menunggu sync Odoo: {$pending['n']}\n";

echo "\nLangkah berikutnya: jalankan sinkronisasi Odoo\n";
echo "  php cli/sync_odoo.php   (ulangi sampai 0 pending)\n";
