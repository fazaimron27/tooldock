<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Data\DatatableQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\AuditLog\App\Models\AuditLog;
use Modules\Core\App\Models\User;

class AuditLogController extends Controller
{
    /**
     * Display a paginated listing of audit logs.
     *
     * Supports filtering by user, system (user vs system actions), event type, model type, and date range.
     */
    public function index(DatatableQueryService $datatableService, Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::with(['user.avatar']);
        $this->applyFilters($query, $request);

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

        $this->loadAuditableRelationshipsBatch($auditLogs->items());

        $users = User::select('id', 'name', 'email')->orderBy('name')->get();

        /**
         * Get event types dynamically from filtered query (excluding event filter itself).
         * This ensures event types reflect what's available in the current filtered results.
         *
         * TODO: Consider adding caching with dynamic cache keys if performance becomes an issue.
         * Cache key should include all filter parameters (except event) to maintain accuracy.
         * Suggested TTL: 5-15 minutes for balance between performance and freshness.
         */
        $eventTypesQuery = AuditLog::query();
        $this->applyFiltersForOptions($eventTypesQuery, $request, excludeEvent: true);
        $eventTypes = $eventTypesQuery
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->map(function ($event) {
                return [
                    'value' => $event,
                    'label' => ucfirst($event),
                ];
            })
            ->toArray();

        /**
         * Get model types dynamically from filtered query (excluding auditable_type filter itself).
         * This ensures model types reflect what's available in the current filtered results.
         *
         * TODO: Consider adding caching with dynamic cache keys if performance becomes an issue.
         * Cache key should include all filter parameters (except auditable_type) to maintain accuracy.
         * Suggested TTL: 5-15 minutes for balance between performance and freshness.
         */
        $modelTypesQuery = AuditLog::query();
        $this->applyFiltersForOptions($modelTypesQuery, $request, excludeAuditableType: true);
        $modelTypes = $modelTypesQuery
            ->select('auditable_type')
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

        return Inertia::render('Modules::AuditLog/Index', [
            'auditLogs' => $auditLogs,
            'users' => $users,
            'modelTypes' => $modelTypes,
            'eventTypes' => $eventTypes,
            'defaultPerPage' => $defaultPerPage,
            'filters' => [
                'user_id' => $request->input('user_id'),
                'system' => $request->input('system'),
                'event' => $request->input('event'),
                'auditable_type' => $request->input('auditable_type'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ],
        ]);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): Response
    {
        $this->authorize('view', $auditLog);

        $auditLog->load(['user.avatar']);
        $this->loadAuditableRelationshipsBatch([$auditLog]);

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

        $query = AuditLog::with(['user.avatar']);
        $this->applyFilters($query, $request);

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
            ], ',', '"', '\\');

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
                    ], ',', '"', '\\');
                }
            });

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Efficiently load auditable relationships in batches, grouped by type.
     *
     * This method optimizes relationship loading by:
     * 1. Grouping audit logs by auditable_type
     * 2. Validating each type once (with caching)
     * 3. Batch loading all models of the same type in a single query
     * 4. Mapping relationships back to audit logs
     *
     * This reduces N+1 queries to just a few queries per unique model type.
     *
     * @param  array<int, AuditLog>  $auditLogs  Array of audit log instances
     * @return void
     */
    private function loadAuditableRelationshipsBatch(array $auditLogs): void
    {
        if (empty($auditLogs)) {
            return;
        }

        $groupedByType = [];
        $typeValidityCache = [];

        foreach ($auditLogs as $auditLog) {
            if (! ($auditLog instanceof AuditLog) || ! $auditLog->auditable_type) {
                $auditLog->setRelation('auditable', null);

                continue;
            }

            $type = $auditLog->auditable_type;

            if (! isset($typeValidityCache[$type])) {
                $typeValidityCache[$type] = AuditLog::isAuditableTypeValid($type);
            }

            if (! $typeValidityCache[$type]) {
                $auditLog->setRelation('auditable', null);

                continue;
            }

            if (! isset($groupedByType[$type])) {
                $groupedByType[$type] = [];
            }

            $id = $auditLog->auditable_id;
            if (! isset($groupedByType[$type][$id])) {
                $groupedByType[$type][$id] = [];
            }

            $groupedByType[$type][$id][] = $auditLog;
        }

        foreach ($groupedByType as $type => $auditLogsByType) {
            try {
                $modelInstance = new $type;
                $primaryKey = $modelInstance->getKeyName();
                $ids = array_keys($auditLogsByType);
                $models = $type::whereIn($primaryKey, $ids)->get()->keyBy($primaryKey);

                foreach ($auditLogsByType as $id => $auditLogsForId) {
                    $model = $models->get($id);

                    foreach ($auditLogsForId as $auditLog) {
                        $auditLog->setRelation('auditable', $model);
                    }
                }
            } catch (\Exception $e) {
                foreach ($auditLogsByType as $auditLogsForId) {
                    foreach ($auditLogsForId as $auditLog) {
                        $auditLog->setRelation('auditable', null);
                    }
                }
            }
        }
    }

    /**
     * Apply filters to the audit log query.
     *
     * Handles filtering by user, system (user vs system actions), event type,
     * model type, and date range. Ensures user_id and system filters don't conflict.
     * Validates date range to ensure start_date is before or equal to end_date.
     * Validates user_id exists in database before applying filter.
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @return void
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        $this->applyFiltersForOptions($query, $request);
    }

    /**
     * Apply filters to the audit log query for getting filter options.
     *
     * This method is used to get dynamic filter options (event types, model types)
     * based on the current filters. It can exclude specific filters to get
     * all available options for that filter type.
     *
     * @param  Builder  $query
     * @param  Request  $request
     * @param  bool  $excludeEvent  If true, excludes the event filter
     * @param  bool  $excludeAuditableType  If true, excludes the auditable_type filter
     * @return void
     */
    private function applyFiltersForOptions(
        Builder $query,
        Request $request,
        bool $excludeEvent = false,
        bool $excludeAuditableType = false
    ): void {
        $query->when($request->filled('user_id'), function ($q) use ($request) {
            $userId = $request->input('user_id');
            if (User::where('id', $userId)->exists()) {
                $q->where('user_id', $userId);
            }
        });

        $query->when(
            ! $request->filled('user_id') && $request->filled('system'),
            function ($q) use ($request) {
                $systemValue = $request->input('system');
                if (in_array($systemValue, ['user', 'system'], true)) {
                    if ($systemValue === 'system') {
                        $q->whereNull('user_id');
                    } else {
                        $q->whereNotNull('user_id');
                    }
                }
            }
        );

        if (! $excludeEvent) {
            $query->when($request->filled('event'), function ($q) use ($request) {
                $q->where('event', $request->input('event'));
            });
        }

        if (! $excludeAuditableType) {
            $query->when($request->filled('auditable_type'), function ($q) use ($request) {
                $q->where('auditable_type', $request->input('auditable_type'));
            });
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate && $startDate > $endDate) {
            $query->whereDate('created_at', '>=', $startDate);
        } else {
            $query->when($startDate, function ($q) use ($startDate) {
                $q->whereDate('created_at', '>=', $startDate);
            });

            $query->when($endDate, function ($q) use ($endDate) {
                $q->whereDate('created_at', '<=', $endDate);
            });
        }
    }
}
