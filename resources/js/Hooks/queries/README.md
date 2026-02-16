# TanStack Query Hooks

This directory contains hooks for using TanStack Query (React Query) in the application.

## When to Use TanStack Query vs Inertia.js

### Use Inertia.js for

- Server-driven state management
- Form submissions that navigate to new pages
- Data that comes from Laravel controllers
- Full-page navigation and state synchronization

### Use TanStack Query for

- External API calls (third-party services, weather APIs, etc.)
- Real-time data that needs polling
- Client-side caching of data
- Optimistic updates for non-Inertia operations
- Data fetching that doesn't fit Inertia's server-driven model

## Base Hooks

### `useApiQuery`

Base hook for GET requests to external APIs.

```javascript
import { useApiQuery } from '@/Hooks/queries/useApiQuery';

function MyComponent() {
  const { data, isLoading, error } = useApiQuery(
    ['external-data'],
    '/api/external-endpoint',
    {
      staleTime: 5 * 60 * 1000, // 5 minutes
    }
  );

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error: {error.message}</div>;

  return <div>{JSON.stringify(data)}</div>;
}
```

### `useApiMutation`

Base hook for POST/PUT/PATCH/DELETE requests to external APIs.

```javascript
import { useApiMutation } from '@/Hooks/queries/useApiMutation';

function MyComponent() {
  const mutation = useApiMutation(
    '/api/external-endpoint',
    'post',
    {
      invalidateQueries: [['external-data']],
      onSuccess: (data) => {
        console.log('Success!', data);
      },
    }
  );

  const handleSubmit = () => {
    mutation.mutate({ name: 'John', email: 'john@example.com' });
  };

  return (
    <button onClick={handleSubmit} disabled={mutation.isPending}>
      {mutation.isPending ? 'Submitting...' : 'Submit'}
    </button>
  );
}
```

## Real Implementation: Notification Hooks

The Signal module provides notification queries that demonstrate TanStack Query usage:

```javascript
import {
  useUnreadCount,
  useRecentNotifications,
  useMarkAsRead,
  useMarkAllAsRead,
} from '@Signal/Hooks/useNotificationQueries';

function NotificationBell() {
  // Query: Auto-refetches every 30s, caches result
  const { data, isLoading } = useUnreadCount();
  
  // Mutation: Invalidates cache on success
  const markAllRead = useMarkAllAsRead();
  
  return (
    <div>
      <span>Unread: {data?.count ?? 0}</span>
      <button 
        onClick={() => markAllRead.mutate()}
        disabled={markAllRead.isPending}
      >
        Mark All Read
      </button>
    </div>
  );
}
```

See `Modules/Signal/resources/assets/js/Hooks/useNotificationQueries.js` for full implementation.

## Other Available Hooks

### User Search Hooks

Shared hooks for searching users across components. Cached results mean instant display for repeated searches.

```javascript
import { useUserSearch, useUserById } from '@/Hooks/queries/useUserSearch';

function UserSelect() {
  const [search, setSearch] = useState('');
  const [debouncedSearch] = useDebounce(search, 300);
  
  // Search users - cached results are shared across components
  const { data, isLoading } = useUserSearch(debouncedSearch, {
    enabled: open, // Only fetch when dropdown is open
    limit: 20,
  });
  
  // Fetch specific user by ID
  const { data: userData } = useUserById(userId, {
    enabled: !!userId,
  });
  
  return <div>...</div>;
}
```

Used by: `UserCombobox`, `MemberSelect`

### Vault Hooks

Polling hooks for vault lock status and TOTP codes.

```javascript
import { useVaultLockStatus, useTOTPCode } from '@Vault/Hooks/useVaultQueries';

// Lock status polling (10s interval, auto-pauses when tab hidden)
const { data } = useVaultLockStatus({
  enabled: vaultLockEnabled,
});

// TOTP code polling (5s interval)
const { data: totpData } = useTOTPCode(vault.id, {
  enabled: !!vault.totp_secret,
});
const totpCode = totpData?.code;
```

## Best Practices

1. **Query Keys**: Use descriptive, hierarchical query keys (e.g., `['users', userId, 'posts']`)
2. **Stale Time**: Set appropriate `staleTime` based on how often data changes
3. **Error Handling**: Always handle loading and error states
4. **Cache Invalidation**: Invalidate related queries after mutations
5. **Optimistic Updates**: Use `onMutate` for optimistic UI updates when appropriate
6. **Enabled Option**: Use `enabled: false` to defer fetching until needed (e.g., dropdown open)
