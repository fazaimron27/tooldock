<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Models\AuditLog;
use Modules\Core\App\Models\User;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of audit logs.
     *
     * Supports filtering by user, event type, model type, and date range.
     */
    public function index(DatatableQueryService $datatableService, Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::with(['user', 'auditable']);

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('event') && $request->event) {
            $query->where('event', $request->event);
        }

        if ($request->has('auditable_type') && $request->auditable_type) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $defaultPerPage = 20;

        $auditLogs = $datatableService->build(
            $query,
            [
                'searchFields' => ['auditable_type', 'url', 'ip_address'],
                'allowedSorts' => ['created_at', 'event', 'auditable_type'],
                'defaultSort' => 'created_at',
                'defaultDirection' => 'desc',
                'allowedPerPage' => [10, 20, 30, 50],
                'defaultPerPage' => $defaultPerPage,
            ]
        );

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        $cacheTtl = (int) settings('model_types_cache_ttl', 3600);
        $modelTypes = Cache::remember('auditlog.model_types', $cacheTtl, function () {
            return AuditLog::select('auditable_type')
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type')
                ->map(function ($type) {
                    return [
                        'value' => $type,
                        'label' => class_basename($type),
                    ];
                })
                ->toArray();
        });

        return Inertia::render('Modules::AuditLog/Index', [
            'auditLogs' => $auditLogs,
            'users' => $users,
            'modelTypes' => $modelTypes,
            'defaultPerPage' => $defaultPerPage,
            'filters' => [
                'user_id' => $request->user_id,
                'event' => $request->event,
                'auditable_type' => $request->auditable_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): Response
    {
        $this->authorize('view', $auditLog);

        $auditLog->load(['user', 'auditable']);

        return Inertia::render('Modules::AuditLog/Show', [
            'auditLog' => $auditLog,
        ]);
    }

    /**
     * Export audit logs as CSV.
     *
     * Applies the same filters as the index method and processes
     * results in chunks for memory efficiency.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::with(['user']);

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('event') && $request->event) {
            $query->where('event', $request->event);
        }

        if ($request->has('auditable_type') && $request->auditable_type) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'User',
                'Event',
                'Model Type',
                'Model ID',
                'Old Values',
                'New Values',
                'URL',
                'IP Address',
                'User Agent',
                'Created At',
            ]);

            $chunkSize = (int) settings('export_chunk_size', 500);
            $query->orderBy('created_at', 'desc')->chunk($chunkSize, function ($auditLogs) use ($file) {
                foreach ($auditLogs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->user?->name ?? 'System',
                        $log->event,
                        class_basename($log->auditable_type),
                        $log->auditable_id,
                        json_encode($log->old_values),
                        json_encode($log->new_values),
                        $log->url ?? '',
                        $log->ip_address ?? '',
                        $log->user_agent ?? '',
                        $log->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
