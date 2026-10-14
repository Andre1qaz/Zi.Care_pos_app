<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

/**
 * Integrasi eksternal (Odoo Accounting).
 * Menyediakan pemeriksaan status koneksi ke Odoo tanpa membuat data baru.
 */
class IntegrationController extends BaseController
{
    /**
     * GET /api/v1/integration/odoo/status
     * Hanya administrator & manager.
     */
    public function odooStatus()
    {
        try {
            $this->requireRole(['administrator', 'manager']);

            $status = $this->di->getShared('odooSyncService')->testConnection();

            return ResponseHelper::success($status);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
