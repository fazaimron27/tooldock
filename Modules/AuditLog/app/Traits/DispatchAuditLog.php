<?php

namespace Modules\AuditLog\App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\App\Enums\AuditLogEvent;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;

/**
 * Trait for dispatching audit log jobs from controllers.
 *
 * This trait provides a reusable method for controllers to dispatch audit log jobs
 * with automatic extraction of user ID, URL, IP address, and user agent from the request.
 *
 * Usage:
 * ```php
 * use DispatchAuditLog;
 *
 * public function update(Request $request)
 * {
 *     $user = $request->user();
 *     // ... update logic ...
 *
 *     $this->dispatchAuditLog(
 *         event: AuditLogEvent::UPDATED,
 *         model: $user,
 *         oldValues: $oldValues,
 *         newValues: $newValues,
 *         tags: 'profile,update'
 *     );
 * }
 * ```
 */
trait DispatchAuditLog
{
    /**
     * Dispatch an audit log job with automatic request context extraction.
     *
     * Helper method to reduce duplication when dispatching audit log jobs from controllers.
     * Automatically extracts user ID, URL, IP address, and user agent from the request.
     *
     * When called from a console context (e.g., Artisan commands), the request will be null
     * and all request-related fields (URL, IP address, user agent) will be set to null.
     * The user ID can still be explicitly provided via the $userId parameter.
     *
     * @param  string  $event  The audit log event type (use AuditLogEvent constants)
     * @param  Model  $model  The model being audited
     * @param  array|null  $oldValues  Old values (for updates/deletes)
     * @param  array|null  $newValues  New values (for creates/updates)
     * @param  string|null  $tags  Optional tags (comma-separated string)
     * @param  Request|null  $request  Optional request instance (defaults to current request via request() helper).
     *                                 In console context, this will be null and request-related fields will be null.
     * @param  string|null  $userId  Optional user ID (defaults to authenticated user via Auth::id()).
     *                               If null and no authenticated user exists, user_id will be null in the audit log.
     * @return void
     */
    protected function dispatchAuditLog(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $tags = null,
        ?Request $request = null,
        ?string $userId = null
    ): void {
        $request = $request ?? request();
        $userId = $userId ?? (Auth::id() ? (string) Auth::id() : null);

        CreateAuditLogJob::dispatch(
            event: $event,
            model: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            userId: $userId,
            url: $request?->url(),
            ipAddress: $request?->ip(),
            userAgent: $request?->userAgent(),
            tags: $tags
        );
    }
}
