/**
 * ThemeSync - Syncs theme preference with backend
 *
 * This component must be rendered inside the Inertia component tree
 * (where usePage() works). It syncs the server-provided theme preference
 * to the ThemeProvider context and handles POSTing theme changes back
 * to the server.
 */
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

import { useTheme } from './ThemeProvider';

export function ThemeSync() {
  const { userPreferences } = usePage().props;
  const { theme, _setThemeFromServer } = useTheme();
  const hasInitialized = useRef(false);

  useEffect(() => {
    const serverTheme = userPreferences?.theme;

    if (serverTheme && ['light', 'dark', 'system'].includes(serverTheme)) {
      if (!hasInitialized.current || serverTheme !== theme) {
        _setThemeFromServer(serverTheme);
        hasInitialized.current = true;
      }
    } else {
      hasInitialized.current = true;
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [userPreferences?.theme]);

  useEffect(() => {
    const handleThemeChange = (event) => {
      const newTheme = event.detail;
      if (userPreferences) {
        router.post(
          route('preferences.update'),
          {
            key: 'core_theme',
            value: newTheme,
          },
          {
            preserveScroll: true,
            preserveState: true,
            only: [],
          }
        );
      }
    };

    window.addEventListener('theme-change', handleThemeChange);
    return () => window.removeEventListener('theme-change', handleThemeChange);
  }, [userPreferences]);

  return null;
}

export default ThemeSync;
