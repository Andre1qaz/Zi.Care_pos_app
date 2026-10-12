<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class DashboardController extends BaseController
{
    public function index()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $range = $this->request->getQuery('range', 'string', 'today');
            $stats = $this->di->getShared('dashboardService')->getStats($range);
            return ResponseHelper::success($stats);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}