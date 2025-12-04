# TanStack Query Hooks

This directory contains hooks for using TanStack Query (React Query) in the application.

## When to Use TanStack Query vs Inertia.js

### Use Inertia.js for:
- Server-driven state management
- Form submissions that navigate to new pages
- Data that comes from Laravel controllers
- Full-page navigation and state synchronization

### Use TanStack Query for:
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

## Example Hooks

See `useExternalData.js` and `useCachedData.js` for example implementations.

## Best Practices

1. **Query Keys**: Use descriptive, hierarchical query keys (e.g., `['users', userId, 'posts']`)
2. **Stale Time**: Set appropriate `staleTime` based on how often data changes
3. **Error Handling**: Always handle loading and error states
4. **Cache Invalidation**: Invalidate related queries after mutations
5. **Optimistic Updates**: Use `onMutate` for optimistic UI updates when appropriate

