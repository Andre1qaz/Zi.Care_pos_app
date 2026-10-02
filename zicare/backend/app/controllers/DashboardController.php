<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class DashboardController extends BaseController
{
    public function indexAction()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $stats = $this->di->getShared('dashboardService')->getStats();
            return ResponseHelper::success($stats);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function index()
    {
        return $this->indexAction();
    }
}