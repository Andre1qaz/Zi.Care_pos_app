<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\ResponseHelper;
use App\Services\AuditLogService;

class AuditLogController extends BaseController
{
    private ?AuditLogService $auditLogService = null;

    private function getService(): AuditLogService
    {
        if ($this->auditLogService === null) {
            $this->auditLogService = new AuditLogService();
        }
        return $this->auditLogService;
    }

    public function indexAction()
    {
        try {
            $this->requireRole(['administrator', 'manager']);

            $page = (int) $this->request->getQuery('page', null, 1);
            $limit = (int) $this->request->getQuery('limit', null, 50);
            
            $filters = [];
            if ($this->request->getQuery('user_id')) {
                $filters['user_id'] = $this->request->getQuery('user_id');
            }
            if ($this->request->getQuery('entity_type')) {
                $filters['entity_type'] = $this->request->getQuery('entity_type');
            }
            if ($this->request->getQuery('activity')) {
                $filters['activity'] = $this->request->getQuery('activity');
            }
            if ($this->request->getQuery('start_date')) {
                $filters['start_date'] = $this->request->getQuery('start_date');
            }
            if ($this->request->getQuery('end_date')) {
                $filters['end_date'] = $this->request->getQuery('end_date');
            }

            $result = $this->getService()->getLogs($page, $limit, $filters);

            return ResponseHelper::success($result);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function showAction(int $id)
    {
        try {
            $this->requireRole(['administrator', 'manager']);

            $log = $this->getService()->getLogById($id);

            if (!$log) {
                return ResponseHelper::error('Audit log not found', 404);
            }

            return ResponseHelper::success($log);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function statsAction()
    {
        try {
            $this->requireRole(['administrator', 'manager']);

            $stats = $this->getService()->getStats();

            return ResponseHelper::success($stats);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    // =========================================================================
    // ALIAS METHOD UNTUK MENANGGULANGI ROUTER YANG MEMANGGIL TANPA AKHIRAN "Action"
    // =========================================================================
    
    public function index()
    {
        return $this->indexAction();
    }

    public function stats()
    {
        return $this->statsAction();
    }

    public function show(int $id)
    {
        return $this->showAction($id);
    }
}