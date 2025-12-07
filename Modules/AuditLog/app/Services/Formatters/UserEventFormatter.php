<?php

namespace Modules\AuditLog\App\Services\Formatters;

use Carbon\Carbon;

/**
 * Formatter for user-related events.
 *
 * Handles: email_verified, email_changed, account_deleted
 */
class UserEventFormatter extends AuditLogFormatter
{
    /**
     * Format the diff based on event type.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  string|null  $event
     * @return array<string>
     */
    public function format(array $oldValues, array $newValues, ?string $event = null): array
    {
        if (! $event) {
            return [];
        }

        return match ($event) {
            'email_verified' => $this->formatEmailVerifiedDiff($newValues),
            'email_changed' => $this->formatEmailChangedDiff($oldValues, $newValues),
            'account_deleted' => $this->formatAccountDeletedDiff($oldValues),
            default => [],
        };
    }

    /**
     * Format diff for email verified events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatEmailVerifiedDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $verifiedAt = $newValues['verified_at'] ?? null;

        if ($email) {
            $changes[] = "Email {$email} verified";
        } else {
            $changes[] = 'Email verified';
        }

        if ($verifiedAt) {
            try {
                $date = Carbon::parse($verifiedAt);
                $changes[] = "Verification time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for email changed events.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatEmailChangedDiff(array $oldValues, array $newValues): array
    {
        $changes = [];
        $oldEmail = $oldValues['email'] ?? null;
        $newEmail = $newValues['email'] ?? null;
        $changedAt = $newValues['changed_at'] ?? null;

        if ($oldEmail && $newEmail) {
            $changes[] = "Email changed from {$oldEmail} to {$newEmail}";
        } elseif ($newEmail) {
            $changes[] = "Email set to {$newEmail}";
        } elseif ($oldEmail) {
            $changes[] = "Email {$oldEmail} removed";
        } else {
            $changes[] = 'Email changed';
        }

        if ($changedAt) {
            try {
                $date = Carbon::parse($changedAt);
                $changes[] = "Change time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for account deleted events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatAccountDeletedDiff(array $oldValues): array
    {
        $changes = [];
        $email = $oldValues['email'] ?? null;
        $name = $oldValues['name'] ?? null;
        $deletedAt = $oldValues['deleted_at'] ?? null;

        if ($email && $name) {
            $changes[] = "Account deleted for user {$name} ({$email})";
        } elseif ($email) {
            $changes[] = "Account deleted for user {$email}";
        } elseif ($name) {
            $changes[] = "Account deleted for user {$name}";
        } else {
            $changes[] = 'Account deleted';
        }

        if ($deletedAt) {
            try {
                $date = Carbon::parse($deletedAt);
                $changes[] = "Deletion time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }
}
