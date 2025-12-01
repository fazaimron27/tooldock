<?php

namespace Modules\AuditLog\App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\AuditLog\App\Jobs\CreateAuditLogJob;

/**
 * Trait for syncing relationships with audit logging.
 *
 * This trait provides a reusable method for syncing many-to-many relationships
 * while automatically creating audit log entries when relationships change.
 *
 * Usage:
 * ```php
 * use SyncsRelationshipsWithAuditLog;
 *
 * private function syncUserRoles(User $user, array $newRoleIds): void
 * {
 *     $this->syncRelationshipWithAuditLog(
 *         model: $user,
 *         relationshipName: 'roles',
 *         newIds: $newRoleIds,
 *         relatedModelClass: \Spatie\Permission\Models\Role::class,
 *         relationshipDisplayName: 'roles'
 *     );
 * }
 * ```
 */
trait SyncsRelationshipsWithAuditLog
{
    /**
     * Sync a many-to-many relationship and log changes to audit log.
     *
     * Captures the current relationship state, syncs the relationship,
     * and creates an audit log entry if the relationship changed.
     * Uses Spatie Permission's specific sync methods when available,
     * otherwise falls back to generic relationship sync.
     *
     * @param  Model  $model  The model whose relationship is being synced
     * @param  string  $relationshipName  The name of the relationship method (e.g., 'roles', 'permissions')
     * @param  array<int|string>  $newIds  Array of IDs to sync
     * @param  string  $relatedModelClass  Fully qualified class name of the related model
     * @param  string  $relationshipDisplayName  Display name for the relationship in audit log (e.g., 'roles', 'permissions')
     * @return void
     */
    protected function syncRelationshipWithAuditLog(
        Model $model,
        string $relationshipName,
        array $newIds,
        string $relatedModelClass,
        string $relationshipDisplayName
    ): void {
        $relationship = $model->{$relationshipName};
        $oldIds = $relationship->pluck('id')->sort()->values()->toArray();
        $oldNames = $relationship->pluck('name')->sort()->values()->toArray();

        $newIds = array_map('intval', $newIds);
        sort($newIds);
        $newIds = array_values($newIds);

        $syncMethod = 'sync'.ucfirst($relationshipName);
        if (method_exists($model, $syncMethod)) {
            $model->{$syncMethod}($newIds);
        } else {
            $model->{$relationshipName}()->sync($newIds);
        }

        $model->load($relationshipName);

        if ($oldIds !== $newIds) {
            $newNames = $relatedModelClass::whereIn('id', $newIds)
                ->pluck('name')
                ->sort()
                ->values()
                ->toArray();

            CreateAuditLogJob::dispatch(
                event: 'updated',
                model: $model,
                oldValues: [
                    $relationshipDisplayName => $oldNames,
                    $relationshipDisplayName.'_ids' => $oldIds,
                ],
                newValues: [
                    $relationshipDisplayName => $newNames,
                    $relationshipDisplayName.'_ids' => $newIds,
                ],
                userId: Auth::id(),
                url: request()?->url(),
                ipAddress: request()?->ip(),
                userAgent: request()?->userAgent()
            );
        }
    }
}
