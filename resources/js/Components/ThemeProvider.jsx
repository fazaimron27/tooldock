/**
 * Theme provider component for managing application theme (light/dark/system)
 *
 * Persists theme preference in localStorage and applies it to the document root.
 * Server sync is handled separately by ThemeSync component which must be
 * rendered inside the Inertia component tree.
 */
import { createContext, useContext, useEffect, useState } from 'react';

const initialState = {
  theme: 'system',
  setTheme: () => null,
};

const ThemeProviderContext = createContext(initialState);

export function ThemeProvider({
  children,
  defaultTheme = 'system',
  storageKey = 'vite-ui-theme',
  ...props
}) {
  const [theme, setThemeState] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem(storageKey) || defaultTheme;
    }
    return defaultTheme;
  });

  useEffect(() => {
    const root = window.document.documentElement;

    root.classList.remove('light', 'dark');

    if (theme === 'system') {
      const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light';

      root.classList.add(systemTheme);
      return;
    }

    root.classList.add(theme);
  }, [theme]);

  const value = {
    theme,
    setTheme: (newTheme) => {
      localStorage.setItem(storageKey, newTheme);
      setThemeState(newTheme);
      // Dispatch event for ThemeSync to sync to backend
      window.dispatchEvent(new window.CustomEvent('theme-change', { detail: newTheme }));
    },
    // Internal method for ThemeSync to update without triggering save
    _setThemeFromServer: (newTheme) => {
      if (newTheme && ['light', 'dark', 'system'].includes(newTheme)) {
        localStorage.setItem(storageKey, newTheme);
        setThemeState(newTheme);
      }
    },
  };

  return (
    <ThemeProviderContext.Provider {...props} value={value}>
      {children}
    </ThemeProviderContext.Provider>
  );
}

export const useTheme = () => {
  const context = useContext(ThemeProviderContext);

  if (context === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }

  return context;
};
