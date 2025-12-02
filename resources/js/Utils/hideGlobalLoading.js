/**
 * Hide global loading spinner once React has mounted
 * Checks if navigation loading is active to prevent double spinners
 */
export function hideGlobalLoading() {
  const globalLoading = document.getElementById('global-loading');
  if (!globalLoading) {
    return;
  }

  let isNavigationLoading = false;
  try {
    isNavigationLoading = window.sessionStorage.getItem('inertia:is-loading') === 'true';
  } catch {
    return;
  }

  if (isNavigationLoading) {
    globalLoading.classList.add('hidden');
  } else {
    window.setTimeout(() => {
      globalLoading.classList.add('hidden');
    }, 100);
  }
}
