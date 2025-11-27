import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

import '../css/app.css';
import { ThemeProvider } from './Components/ThemeProvider';
import './bootstrap';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Lazy-loads module pages to reduce initial bundle size
// Pages are only loaded when navigated to, improving performance
const modulePages = import.meta.glob('../../Modules/*/resources/assets/js/Pages/**/*.jsx', {
  eager: false,
});

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => {
    // Resolves module pages using "Modules::ModuleName/PagePath" syntax
    // Converts module notation to file system paths and handles lazy loading
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

        return moduleLoader()
          .then((module) => {
            if (typeof module === 'function') {
              return module;
            }
            if (typeof module === 'object' && module !== null) {
              if ('default' in module) {
                return module.default;
              }
              return module;
            }
            throw new Error(`Module ${name} exported an invalid component type: ${typeof module}`);
          })
          .catch((error) => {
            throw new Error(`Failed to load module ${name}: ${error.message}`);
          });
      }

      throw new Error(`Module page not found: ${name} (looked for: ${expectedPath}.jsx)`);
    }

    return resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx'));
  },
  setup({ el, App, props }) {
    const root = createRoot(el);

    root.render(
      <ThemeProvider defaultTheme="system" storageKey="vite-ui-theme">
        <App {...props} />
      </ThemeProvider>
    );
  },
  progress: {
    color: '#4B5563',
  },
});
