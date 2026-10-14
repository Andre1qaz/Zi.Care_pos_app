<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use Phalcon\Di\Di;

class ProductService
{
    private ProductRepository $repository;
    private StockMovementRepository $stockMovementRepository;

    public function __construct()
    {
        $di = Di::getDefault();
        $this->repository = $di->getShared('productRepository');
        $this->stockMovementRepository = $di->getShared('stockMovementRepository');
    }

    public function list(array $filters, int $page, int $limit): array
    {
        return $this->repository->findAll($filters, $page, $limit);
    }

    public function get(int $id): array
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        return $product->toArray();
    }

    public function create(array $data, ?int $userId = null): array
    {
        $errors = $this->validate($data);
        if ($errors) {
            throw new ValidationException($errors);
        }

        if ($this->repository->findByCode($data['product_code'])) {
            throw new ValidationException(['product_code' => 'Product code already exists']);
        }

        $product = new Product();
        $product->assign($data);
        
        // Ensure services have stock = 0
        if ($product->product_type === 'jasa') {
            $product->stock = 0;
        }
        
        $product->save();

        // Catat saldo awal sebagai pergerakan stok agar kartu stok lengkap.
        if (($product->product_type ?? 'barang') === 'barang' && (int) $product->stock !== 0) {
            $this->stockMovementRepository->record([
                'product_id'    => (int) $product->id,
                'movement_type' => 'opname',
                'quantity'      => (int) $product->stock,
                'stock_before'  => 0,
                'stock_after'   => (int) $product->stock,
                'reason'        => 'Stok awal produk',
                'reference'     => 'OPENING',
                'user_id'       => $userId,
            ]);
        }

        return $product->toArray();
    }

    public function update(int $id, array $data, ?int $userId = null): array
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $beforeStock = (int) $product->stock;

        $product->assign($data);
        
        // Ensure services have stock = 0
        if ($product->product_type === 'jasa') {
            $product->stock = 0;
        }
        
        $product->save();

        // Jika stok diubah lewat form edit, catat sebagai penyesuaian agar
        // kartu stok tetap akurat (bukan perubahan diam-diam).
        $afterStock = (int) $product->stock;
        if (($product->product_type ?? 'barang') === 'barang' && $afterStock !== $beforeStock) {
            $this->stockMovementRepository->record([
                'product_id'    => (int) $product->id,
                'movement_type' => 'adjustment',
                'quantity'      => $afterStock - $beforeStock,
                'stock_before'  => $beforeStock,
                'stock_after'   => $afterStock,
                'reason'        => 'Perubahan stok via edit produk',
                'user_id'       => $userId,
            ]);
        }

        return $product->toArray();
    }

    public function delete(int $id): void
    {
        $product = $this->repository->findById($id);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }
        $product->is_active = 0;
        $product->save();
    }

    /**
     * Kartu stok: detail produk + ringkasan + daftar pergerakan.
     */
    public function stockMovements(int $productId, array $filters, int $page, int $limit): array
    {
        $product = $this->repository->findById($productId);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        $result = $this->stockMovementRepository->findByProduct($productId, $filters, $page, $limit);

        return [
            'product' => $product->toArray(),
            'summary' => $this->stockMovementRepository->summary($productId),
            'items'   => $result['items'],
            'total'   => $result['total'],
        ];
    }

    /**
     * Penyesuaian stok (stok opname / koreksi manual).
     *
     * Terima salah satu dari:
     *  - new_stock : stok fisik hasil hitung (mode opname, nilainya absolut)
     *  - delta     : selisih (+/-) untuk koreksi cepat
     */
    public function adjustStock(int $productId, array $data, ?int $userId = null): array
    {
        $product = $this->repository->findById($productId);
        if (!$product) {
            throw new NotFoundException('Product not found');
        }

        if (($product->product_type ?? 'barang') !== 'barang') {
            throw new ValidationException(['product' => 'Hanya produk barang yang memiliki stok']);
        }

        $reason = trim((string) ($data['reason'] ?? ''));
        if ($reason === '') {
            throw new ValidationException(['reason' => 'Alasan penyesuaian wajib diisi']);
        }

        $before = (int) $product->stock;
        $hasNewStock = array_key_exists('new_stock', $data) && $data['new_stock'] !== null && $data['new_stock'] !== '';
        $hasDelta    = array_key_exists('delta', $data) && $data['delta'] !== null && $data['delta'] !== '';

        if ($hasNewStock) {
            $newStock = (int) $data['new_stock'];
            if ($newStock < 0) {
                throw new ValidationException(['new_stock' => 'Stok tidak boleh negatif']);
            }
            $movementType = 'opname';
        } elseif ($hasDelta) {
            $delta = (int) $data['delta'];
            if ($delta === 0) {
                throw new ValidationException(['delta' => 'Perubahan stok tidak boleh 0']);
            }
            $newStock = $before + $delta;
            if ($newStock < 0) {
                throw new ValidationException(['delta' => 'Stok akhir tidak boleh negatif']);
            }
            $movementType = 'adjustment';
        } else {
            throw new ValidationException(['new_stock' => 'Isi stok fisik baru atau selisih penyesuaian']);
        }

        $delta = $newStock - $before;
        if ($delta === 0) {
            throw new ValidationException(['new_stock' => 'Stok baru sama dengan stok saat ini']);
        }

        $db = Di::getDefault()->getShared('db');
        $db->begin();

        try {
            $this->repository->setStock($productId, $newStock);
            $this->stockMovementRepository->record([
                'product_id'    => $productId,
                'movement_type' => $movementType,
                'quantity'      => $delta,
                'stock_before'  => $before,
                'stock_after'   => $newStock,
                'reason'        => $reason,
                'reference'     => $data['reference'] ?? null,
                'user_id'       => $userId,
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            throw $e;
        }

        return [
            'product'  => $this->repository->findById($productId)->toArray(),
            'movement' => [
                'type'         => $movementType,
                'quantity'     => $delta,
                'stock_before' => $before,
                'stock_after'  => $newStock,
            ],
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['product_code'])) {
            $errors['product_code'] = 'Product code is required';
        }
        if (empty($data['product_name'])) {
            $errors['product_name'] = 'Product name is required';
        }
        if (!isset($data['price']) || $data['price'] < 0) {
            $errors['price'] = 'Valid price is required';
        }

        $productType = $data['product_type'] ?? 'barang';

        if ($productType === 'barang') {
            if (!isset($data['stock']) || $data['stock'] < 0) {
                $errors['stock'] = 'Valid stock is required for barang';
            }
        } elseif ($productType === 'jasa') {
            // Services must have stock = 0
            if (isset($data['stock']) && $data['stock'] != 0) {
                $errors['stock'] = 'Stock must be 0 for jasa (services)';
            }
            if (!isset($data['service_status']) || !in_array($data['service_status'], ['tersedia', 'tidak_tersedia'])) {
                $errors['service_status'] = 'Valid service status is required for jasa';
            }
        }

        return $errors;
    }
}
