<?php

/**
 * Signal Controller
 *
 * HTTP controller for managing user notifications in the Signal module.
 * Handles all CRUD operations for notifications including listing,
 * viewing, marking as read, and deletion.
 *
 * @author     Tool Dock Team
 * @license    MIT
 */

namespace Modules\Signal\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Signal\Http\Requests\DeleteNotificationRequest;
use Modules\Signal\Http\Requests\MarkNotificationAsReadRequest;
use Modules\Signal\Services\SignalCacheService;

/**
 * Class SignalController
 *
 * Manages notification lifecycle operations for authenticated users.
 * All methods enforce ownership - users can only access their own notifications.
 * Supports both JSON API responses and Inertia page rendering.
 *
 * @see \Modules\Signal\Services\SignalCacheService
 * @see \Modules\Signal\Policies\NotificationPolicy
 */
class SignalController extends Controller
{
    /**
     * SignalController constructor.
     *
     * Injects the cache service for optimized notification retrieval.
     *
     * @param  SignalCacheService  $cacheService  Service for caching notification data
     * @return void
     */
    public function __construct(
        private SignalCacheService $cacheService
    ) {}

    /**
     * Display paginated list of user's notifications.
     *
     * Renders the notifications inbox page with pagination.
     * Supports filtering by read/unread status via query parameter.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return Response Inertia response with notification data
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user lacks viewAny permission
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DatabaseNotification::class);

        $user = $request->user();

        $query = $user->notifications();

        if ($request->has('filter') && $request->filter === 'unread') {
            $query = $user->unreadNotifications();
        }

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(fn ($notification) => $this->formatNotification($notification))
            ->withQueryString();

        $counts = [
            'all' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
        ];

        return Inertia::render('Modules::Signal/Index', [
            'notifications' => $notifications,
            'filter' => $request->filter ?? 'all',
            'counts' => $counts,
        ]);
    }

    /**
     * Display a single notification's details.
     *
     * Renders the notification detail page with navigation context.
     * Automatically marks the notification as read when viewed.
     * Provides prev/next navigation links for sequential browsing.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  DatabaseNotification  $notification  The notification to display
     * @return Response Inertia response with notification detail
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException If user cannot view notification
     */
    public function show(Request $request, DatabaseNotification $notification): Response
    {
        $this->authorize('view', $notification);

        $user = $request->user();

        if (! $notification->read_at) {
            $notification->markAsRead();
            $this->cacheService->invalidateUserCache($user);
        }

        $allNotificationIds = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->pluck('id')
            ->toArray();

        $currentIndex = array_search($notification->id, $allNotificationIds);
        $prevId = $currentIndex > 0 ? $allNotificationIds[$currentIndex - 1] : null;
        $nextId = $currentIndex < count($allNotificationIds) - 1 ? $allNotificationIds[$currentIndex + 1] : null;

        return Inertia::render('Modules::Signal/Show', [
            'notification' => $this->formatNotification($notification),
            'navigation' => [
                'prev' => $prevId,
                'next' => $nextId,
                'current' => $currentIndex + 1,
                'total' => count($allNotificationIds),
            ],
        ]);
    }

    /**
     * Get the count of unread notifications.
     *
     * Returns cached unread count for performance.
     * Used by the notification bell component for badge display.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return JsonResponse JSON response with unread count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->cacheService->getUnreadCount($request->user());

        return response()->json([
            'count' => $count,
        ]);
    }

    /**
     * Get recent notifications for the dropdown menu.
     *
     * Returns cached list of recent notifications for quick access.
     * Typically limited to 5 most recent notifications.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return JsonResponse JSON response with recent notifications
     */
    public function recent(Request $request): JsonResponse
    {
        $notifications = $this->cacheService->getRecentNotifications($request->user());

        return response()->json([
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a specific notification as read.
     *
     * Updates the read_at timestamp and invalidates cache.
     * Supports both JSON and redirect responses based on request type.
     *
     * @param  MarkNotificationAsReadRequest  $request  Validated request
     * @param  DatabaseNotification  $notification  The notification to mark
     * @return JsonResponse|RedirectResponse Response based on request type
     */
    public function markAsRead(MarkNotificationAsReadRequest $request, DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        $notification->markAsRead();
        $this->cacheService->invalidateUserCache($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all of the user's notifications as read.
     *
     * Bulk operation to mark all unread notifications as read.
     * Invalidates cache after update for accurate counts.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return JsonResponse|RedirectResponse Response based on request type
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        $this->cacheService->invalidateUserCache($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a specific notification.
     *
     * Permanently removes the notification from the database.
     * Authorization is handled by DeleteNotificationRequest.
     *
     * @param  DeleteNotificationRequest  $request  Validated request with authorization
     * @param  DatabaseNotification  $notification  The notification to delete
     * @return JsonResponse|RedirectResponse Response based on request type
     */
    public function destroy(DeleteNotificationRequest $request, DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        $notification->delete();
        $this->cacheService->invalidateUserCache($request->user());

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification deleted.');
    }

    /**
     * Bulk delete selected notifications.
     *
     * Deletes multiple notifications in a single operation.
     * Only deletes notifications owned by the current user.
     *
     * @param  Request  $request  The incoming HTTP request with notification IDs
     * @return JsonResponse|RedirectResponse Response with deleted count
     */
    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid'],
        ]);

        $user = $request->user();
        $deleted = DatabaseNotification::whereIn('id', $request->ids)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->delete();

        $this->cacheService->invalidateUserCache($user);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'deleted' => $deleted]);
        }

        return back()->with('success', "{$deleted} notification(s) deleted.");
    }

    /**
     * Bulk mark selected notifications as read.
     *
     * Marks multiple notifications as read in a single operation.
     * Only affects unread notifications owned by the current user.
     *
     * @param  Request  $request  The incoming HTTP request with notification IDs
     * @return JsonResponse|RedirectResponse Response with updated count
     */
    public function bulkMarkAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'uuid'],
        ]);

        $user = $request->user();
        $updated = DatabaseNotification::whereIn('id', $request->ids)
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->cacheService->invalidateUserCache($user);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'updated' => $updated]);
        }

        return back()->with('success', "{$updated} notification(s) marked as read.");
    }

    /**
     * Format a notification for frontend consumption.
     *
     * Transforms the database notification into a standardized array
     * structure suitable for API responses and Inertia rendering.
     *
     * @param  DatabaseNotification  $notification  The notification to format
     * @return array<string, mixed> Formatted notification data array
     */
    private function formatNotification(DatabaseNotification $notification): array
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
