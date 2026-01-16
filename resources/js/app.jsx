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

/**
 * Static map of module pages available at build time.
 */
const modulePages = import.meta.glob('../../Modules/*/resources/assets/js/Pages/**/*.jsx', {
  eager: false,
});

/**
 * Wrap a page loader with React.lazy for code splitting.
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

/**
 * Extract module pages manifest from Inertia's initial page props.
 */
function getServerModulePages() {
  try {
    const el = document.getElementById('app');
    if (!el?.dataset?.page) return [];
    const pageData = JSON.parse(el.dataset.page);
    return pageData?.props?.modulePages || [];
  } catch {
    return [];
  }
}

createInertiaApp({
  title: generateInertiaTitle,
  resolve: (name) => {
    if (name.startsWith('Modules::')) {
      const parts = name.replace('Modules::', '').split('/');
      const moduleName = parts[0];
      const pagePath = parts.slice(1).join('/');
      const pageIdentifier = `${moduleName}/${pagePath}`;
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

      const serverModulePages = getServerModulePages();
      const pageExistsOnServer = serverModulePages.includes(pageIdentifier);

      if (pageExistsOnServer) {
        console.info(
          `[Inertia] Module page "${name}" exists on server but not in build. ` +
            `This module was likely installed after the last build. Triggering full reload.`
        );
        window.location.reload();
        // Return a never-resolving promise while reload happens
        return lazy(() => new Promise(() => {}));
      }

      throw new Error(
        `Module page not found: ${name}\n` +
          `- Not in build-time glob (looked for: ${expectedPath}.jsx)\n` +
          `- Not in server manifest (modulePages: ${serverModulePages.length} pages)\n` +
          `If this module was just installed, try running 'npm run build'.`
      );
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
  prefetch: {
    enabled: true,
    delay: 200,
  },
});
