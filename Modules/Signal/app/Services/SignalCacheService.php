<?php

/**
 * Signal Cache Service
 *
 * Provides caching functionality for the Signal notification module.
 * Handles caching of frequently accessed notification data.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Services;

use App\Services\Cache\CacheService;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Core\Models\User;

/**
 * Class SignalCacheService
 *
 * Manages caching for notification-related data including unread counts
 * and recent notifications. Uses Redis tags for efficient cache invalidation.
 */
class SignalCacheService
{
    /** @var string Cache tag for Signal module entries */
    private const CACHE_TAG = 'signal';

    /** @var int TTL for unread count cache (5 minutes) */
    private const UNREAD_COUNT_TTL = 300;

    /** @var int TTL for recent notifications cache (2 minutes) */
    private const RECENT_TTL = 120;

    /** @var int Default limit for recent notifications */
    public const DEFAULT_RECENT_LIMIT = 5;

    /**
     * @param  CacheService  $cacheService  The application cache service
     */
    public function __construct(
        private CacheService $cacheService
    ) {}

    /**
     * Get the unread notification count for a user (cached).
     *
     * @param  User|Authenticatable  $user  The user to get count for
     * @return int The unread count
     */
    public function getUnreadCount(Authenticatable $user): int
    {
        $key = $this->getUserCacheKey($user, 'unread_count');

        return $this->cacheService->remember(
            $key,
            self::UNREAD_COUNT_TTL,
            fn () => $user->unreadNotifications()->count(),
            $this->getUserTags($user),
            'SignalCacheService'
        );
    }

    /**
     * Get recent notifications for a user (cached).
     *
     * @param  User|Authenticatable  $user  The user to get notifications for
     * @param  int|null  $limit  Number of notifications to fetch
     * @return array<int, array<string, mixed>> Array of formatted notifications
     */
    public function getRecentNotifications(Authenticatable $user, ?int $limit = null): array
    {
        $limit = $limit ?? self::DEFAULT_RECENT_LIMIT;
        $key = $this->getUserCacheKey($user, "recent_{$limit}");

        return $this->cacheService->remember(
            $key,
            self::RECENT_TTL,
            fn () => $user->notifications()
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($notification) => $this->formatNotification($notification))
                ->toArray(),
            $this->getUserTags($user),
            'SignalCacheService'
        );
    }

    /**
     * Invalidate all caches for a specific user.
     *
     * @param  Authenticatable  $user  The user whose cache to invalidate
     * @return void
     */
    public function invalidateUserCache(Authenticatable $user): void
    {
        $userTag = $this->getUserTag($user);
        $this->cacheService->flush($userTag, 'SignalCacheService');
    }

    /**
     * Invalidate all signal caches (all users).
     *
     * @return void
     */
    public function invalidateAll(): void
    {
        $this->cacheService->flush(self::CACHE_TAG, 'SignalCacheService');
    }

    /**
     * Generate a user-specific cache key.
     *
     * @param  Authenticatable  $user  The user
     * @param  string  $suffix  Key suffix
     * @return string Cache key
     */
    private function getUserCacheKey(Authenticatable $user, string $suffix): string
    {
        return "signal:user:{$user->getAuthIdentifier()}:{$suffix}";
    }

    /**
     * Get tag for a specific user's cache.
     *
     * @param  Authenticatable  $user  The user
     * @return string User-specific tag
     */
    private function getUserTag(Authenticatable $user): string
    {
        return "signal:user:{$user->getAuthIdentifier()}";
    }

    /**
     * Get tags for a user's cached data.
     *
     * @param  Authenticatable  $user  The user
     * @return array<int, string> Tags array
     */
    private function getUserTags(Authenticatable $user): array
    {
        return [self::CACHE_TAG, $this->getUserTag($user)];
    }

    /**
     * Format a notification for caching/API response.
     *
     * @param  \Illuminate\Notifications\DatabaseNotification  $notification
     * @return array<string, mixed> Formatted notification data
     */
    private function formatNotification($notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? 'info',
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'] ?? '',
            'action_url' => $data['action_url'] ?? null,
            'module_source' => $data['module_source'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
            'created_at_human' => $notification->created_at->diffForHumans(),
        ];
    }
}
