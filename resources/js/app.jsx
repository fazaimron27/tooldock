import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Suspense, lazy } from 'react';
import { createRoot } from 'react-dom/client';

import '../css/app.css';
import { GlobalSpinner, setNavigationState } from './Components/AppSpinner';
import { ThemeProvider } from './Components/ThemeProvider';
import { QueryProvider } from './Providers/QueryProvider';
import { generateInertiaTitle } from './Utils/inertiaTitle';
import './bootstrap';

const modulePages = import.meta.glob('../../Modules/*/resources/assets/js/Pages/**/*.jsx', {
  eager: false,
});

/**
 * Wraps a page loader with React.lazy for better code splitting and Suspense support.
 * This enables React to create separate chunks and provides Suspense boundaries.
 */
function lazyPage(loader) {
  return lazy(() =>
    loader().then((module) => {
      if (typeof module === 'function') {
        return { default: module };
      }
      if (typeof module === 'object' && module !== null) {
        if ('default' in module) {
          return module;
        }
        return { default: module };
      }
      throw new Error(`Invalid module export type: ${typeof module}`);
    })
  );
}

createInertiaApp({
  title: generateInertiaTitle,
  resolve: (name) => {
    if (name.startsWith('Modules::')) {
      const parts = name.replace('Modules::', '').split('/');
      const moduleName = parts[0];
      const pagePath = parts.slice(1).join('/');
      const expectedPath = `../../Modules/${moduleName}/resources/assets/js/Pages/${pagePath}`;

      const modulePageKey = Object.keys(modulePages).find((key) => {
        const normalizedKey = key.replace(/\.jsx$/, '');
        return normalizedKey === expectedPath || key === `${expectedPath}.jsx`;
      });

      if (modulePageKey && modulePages[modulePageKey]) {
        const moduleLoader = modulePages[modulePageKey];
        if (typeof moduleLoader !== 'function') {
          throw new Error(`Module loader for ${modulePageKey} is not a function`);
        }

        return lazyPage(moduleLoader);
      }

      throw new Error(`Module page not found: ${name} (looked for: ${expectedPath}.jsx)`);
    }

    const pageLoader = resolvePageComponent(
      `./Pages/${name}.jsx`,
      import.meta.glob('./Pages/**/*.jsx')
    );
    return lazyPage(() => pageLoader);
  },
  setup({ el, App, props }) {
    const root = createRoot(el);

    root.render(
      <QueryProvider>
        <ThemeProvider defaultTheme="system" storageKey="vite-ui-theme">
          <GlobalSpinner />
          <Suspense fallback={null}>
            <App {...props} />
          </Suspense>
        </ThemeProvider>
      </QueryProvider>
    );
  },
  progress: {
    showSpinner: false,
    delay: 0,
    includeCSS: false,
    /**
     * Fallback navigation state handlers.
     * Primary navigation state is managed by NavigationSpinner via router events.
     * These callbacks serve as a safety net for edge cases.
     */
    onStart: () => {
      setNavigationState(true);
    },
    onFinish: () => {
      setNavigationState(false);
    },
    onError: () => {
      setNavigationState(false);
    },
  },
  /**
   * Prefetch configuration for Inertia.js link prefetching.
   * Enabled with 200ms delay to improve UX while avoiding excessive parallel requests.
   * Links are prefetched after 200ms of hover, balancing performance and user experience.
   */
  prefetch: {
    enabled: true,
    delay: 200,
  },
});
