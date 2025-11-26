import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from './Components/ThemeProvider';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Lazy load module pages - only download when actually needed
const modulePages = import.meta.glob('../../Modules/*/resources/assets/js/Pages/**/*.jsx');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Check if this is a module page (starts with "Modules::")
        if (name.startsWith('Modules::')) {
            // Extract module and page path (e.g., "Modules::Blog/Index" -> ["Blog", "Index"])
            const parts = name.replace('Modules::', '').split('/');
            const moduleName = parts[0];
            const pagePath = parts.slice(1).join('/');

            // Build the expected path pattern (without extension, as glob includes it)
            // Format: ../../Modules/{ModuleName}/resources/assets/js/Pages/{PagePath}
            const expectedPath = `../../Modules/${moduleName}/resources/assets/js/Pages/${pagePath}`;

            // Find matching key - glob keys include .jsx extension
            const modulePageKey = Object.keys(modulePages).find((key) => {
                // Remove .jsx extension from key for comparison
                const keyWithoutExt = key.replace(/\.jsx$/, '');
                return keyWithoutExt === expectedPath || key === `${expectedPath}.jsx`;
            });

            if (modulePageKey && modulePages[modulePageKey]) {
                // Lazy load: modulePages[modulePageKey] is a function that returns a Promise
                return modulePages[modulePageKey]().then((module) => {
                    return typeof module === 'object' && 'default' in module ? module.default : module;
                });
            }

            throw new Error(`Module page not found: ${name} (looked for: ${expectedPath}.jsx)`);
        }

        // Standard resolution for non-module pages
        return resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        );
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
