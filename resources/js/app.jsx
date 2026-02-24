import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Suspense, lazy } from 'react';
import { createRoot } from 'react-dom/client';

import '../css/app.css';
import { GlobalSpinner, setNavigationState } from './Components/AppSpinner';
import { ThemeProvider } from './Components/ThemeProvider';
import DashboardLayout from './Layouts/DashboardLayout';
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
 * Page patterns that should NOT use the DashboardLayout.
 * These pages either have their own layout or should be displayed standalone.
 *
 * Pattern syntax:
 * - 'PageName'       - Exact match for a page
 * - 'Folder/*'       - Matches all direct children (e.g., 'Auth/Login')
 * - 'Folder/ **'     - Matches all descendants recursively
 * - 'Page?'          - Single character wildcard
 * - 'Modules::X/Y'   - Matches specific module page
 * - Module wildcards work too (e.g., match Auth in any module)
 *
 * Categories:
 * 1. Landing/Public pages - Use their own layout (LandingLayout)
 * 2. Authentication pages - Use AuthLayout
 */

const pagesWithoutLayout = ['Welcome', 'Auth/**', 'Modules::*/Auth/**', 'Modules::Folio/Print'];

/**
 * Convert a glob-like pattern to a regex.
 * Supports:
 * - '*'  - matches any characters except '/'
 * - '**' - matches any characters including '/' (recursive)
 * - '?'  - matches exactly one character
 *
 * @param {string} pattern - Glob pattern to convert
 * @returns {RegExp} Compiled regex
 */
function patternToRegex(pattern) {
  const GLOBSTAR_PLACEHOLDER = '\u0000GLOBSTAR\u0000';

  const regexStr = pattern
    // Escape regex special chars (except * and ?)
    .replace(/[.+^${}()|[\]\\]/g, '\\$&')
    // Handle ** first (must come before single *)
    .replace(/\*\*/g, GLOBSTAR_PLACEHOLDER)
    // Handle single * (matches anything except /)
    .replace(/\*/g, '[^/]*')
    // Handle ? (matches single char except /)
    .replace(/\?/g, '[^/]')
    // Restore ** as .* (matches anything including /)
    .replace(new RegExp(GLOBSTAR_PLACEHOLDER, 'g'), '.*');

  return new RegExp(`^${regexStr}$`);
}

/**
 * Check if a page name matches a pattern.
 * @param {string} pageName - The page name to check
 * @param {string} pattern - Glob-like pattern
 * @returns {boolean} True if matches
 */
function matchesPattern(pageName, pattern) {
  // Fast path: exact match (no wildcards)
  if (!pattern.includes('*') && !pattern.includes('?')) {
    return pageName === pattern;
  }

  return patternToRegex(pattern).test(pageName);
}

/**
 * Check if a page should use the dashboard layout.
 * @param {string} pageName - The Inertia page name
 * @returns {boolean} True if page should use DashboardLayout
 */
function shouldUseDashboardLayout(pageName) {
  return !pagesWithoutLayout.some((pattern) => matchesPattern(pageName, pattern));
}

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
    let LazyComponent;

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

        LazyComponent = lazyPage(moduleLoader);
      } else {
        const serverModulePages = getServerModulePages();
        const pageExistsOnServer = serverModulePages.includes(pageIdentifier);

        if (pageExistsOnServer) {
          console.info(
            `[Inertia] Module page "${name}" exists on server but not in build. ` +
              `This module was likely installed after the last build. Triggering full reload.`
          );
          window.location.reload();
          return lazy(() => new Promise(() => {}));
        }

        throw new Error(
          `Module page not found: ${name}\n` +
            `- Not in build-time glob (looked for: ${expectedPath}.jsx)\n` +
            `- Not in server manifest (modulePages: ${serverModulePages.length} pages)\n` +
            `If this module was just installed, try running 'npm run build'.`
        );
      }
    } else {
      const pageLoader = resolvePageComponent(
        `./Pages/${name}.jsx`,
        import.meta.glob('./Pages/**/*.jsx')
      );
      LazyComponent = lazyPage(() => pageLoader);
    }

    // Add persistent layout for dashboard pages
    if (shouldUseDashboardLayout(name)) {
      LazyComponent.layout = (page) => <DashboardLayout>{page}</DashboardLayout>;
    }

    return LazyComponent;
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
