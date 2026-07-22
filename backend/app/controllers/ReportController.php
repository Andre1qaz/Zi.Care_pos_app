<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;

class ReportController extends BaseController
{
    public function dailySales()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $date = $this->request->getQuery('date') ?? date('Y-m-d');
            $report = $this->di->getShared('reportService')->dailySales($date);
            return ResponseHelper::success($report);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function monthlySales()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $year = (int) ($this->request->getQuery('year') ?? date('Y'));
            $month = (int) ($this->request->getQuery('month') ?? date('m'));
            $report = $this->di->getShared('reportService')->monthlySales($year, $month);
            return ResponseHelper::success($report);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function productSales()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $dateFrom = $this->request->getQuery('date_from') ?? date('Y-m-01');
            $dateTo = $this->request->getQuery('date_to') ?? date('Y-m-d');
            $report = $this->di->getShared('reportService')->productSales($dateFrom, $dateTo);
            return ResponseHelper::success($report);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function payments()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $dateFrom = $this->request->getQuery('date_from') ?? date('Y-m-01');
            $dateTo = $this->request->getQuery('date_to') ?? date('Y-m-d');
            $report = $this->di->getShared('reportService')->payments($dateFrom, $dateTo);
            return ResponseHelper::success($report);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function customerTransactions()
    {
        try {
            $this->requireRole(['administrator', 'manager']);
            $customerId = (int) $this->request->getQuery('customer_id');
            $dateFrom = $this->request->getQuery('date_from') ?? date('Y-m-01');
            $dateTo = $this->request->getQuery('date_to') ?? date('Y-m-d');
            $report = $this->di->getShared('reportService')->customerTransactions($customerId, $dateFrom, $dateTo);
            return ResponseHelper::success($report);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }
}
