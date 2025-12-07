<?php

namespace Modules\AuditLog\App\Services\Formatters;

use Carbon\Carbon;

/**
 * Formatter for authentication-related events.
 *
 * Handles: registered, login, logout, password_reset, password_changed, password_reset_requested
 */
class AuthenticationEventFormatter extends AuditLogFormatter
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
            'registered' => $this->formatRegisteredDiff($newValues),
            'login' => $this->formatLoginDiff($newValues),
            'logout' => $this->formatLogoutDiff($oldValues),
            'password_reset' => $this->formatPasswordResetDiff($newValues),
            'password_changed' => $this->formatPasswordChangedDiff($newValues),
            'password_reset_requested' => $this->formatPasswordResetRequestedDiff($newValues),
            default => [],
        };
    }

    /**
     * Format diff for registered events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatRegisteredDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $name = $newValues['name'] ?? null;

        if ($email && $name) {
            $changes[] = "User {$name} ({$email}) registered";
        } elseif ($email) {
            $changes[] = "User {$email} registered";
        } elseif ($name) {
            $changes[] = "User {$name} registered";
        } else {
            $changes[] = 'New user registered';
        }

        return $changes;
    }

    /**
     * Format diff for login events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatLoginDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $loggedInAt = $newValues['logged_in_at'] ?? null;

        if ($email) {
            $changes[] = "User {$email} logged in";
        } else {
            $changes[] = 'User logged in';
        }

        if ($loggedInAt) {
            try {
                $date = Carbon::parse($loggedInAt);
                $changes[] = "Login time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for logout events.
     *
     * @param  array<string, mixed>  $oldValues
     * @return array<string>
     */
    protected function formatLogoutDiff(array $oldValues): array
    {
        $changes = [];
        $email = $oldValues['email'] ?? null;
        $loggedOutAt = $oldValues['logged_out_at'] ?? null;

        if ($email) {
            $changes[] = "User {$email} logged out";
        } else {
            $changes[] = 'User logged out';
        }

        if ($loggedOutAt) {
            try {
                $date = Carbon::parse($loggedOutAt);
                $changes[] = "Logout time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for password reset events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatPasswordResetDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $resetAt = $newValues['reset_at'] ?? null;

        if ($email) {
            $changes[] = "Password reset for user {$email}";
        } else {
            $changes[] = 'Password reset';
        }

        if ($resetAt) {
            try {
                $date = Carbon::parse($resetAt);
                $changes[] = "Reset time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }

    /**
     * Format diff for password changed events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatPasswordChangedDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $changedAt = $newValues['changed_at'] ?? null;

        if ($email) {
            $changes[] = "Password changed for user {$email}";
        } else {
            $changes[] = 'Password changed';
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
     * Format diff for password reset requested events.
     *
     * @param  array<string, mixed>  $newValues
     * @return array<string>
     */
    protected function formatPasswordResetRequestedDiff(array $newValues): array
    {
        $changes = [];
        $email = $newValues['email'] ?? null;
        $requestedAt = $newValues['requested_at'] ?? null;

        if ($email) {
            $changes[] = "Password reset requested for {$email}";
        } else {
            $changes[] = 'Password reset requested';
        }

        if ($requestedAt) {
            try {
                $date = Carbon::parse($requestedAt);
                $changes[] = "Request time: {$date->format('F j, Y \a\t g:i A')}";
            } catch (\Exception) {
                // Ignore date parsing errors
            }
        }

        return $changes;
    }
}
