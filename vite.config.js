import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig } from 'vite';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
  plugins: [
    laravel({
      input: 'resources/js/app.jsx',
      refresh: [
        'resources/views/**',
        'Modules/*/resources/views/**',
        'Modules/*/resources/assets/js/**',
      ],
    }),
    react(),
    {
      /**
       * Custom plugin to handle module page resolution for Inertia.js.
       * Module pages use virtual path syntax (Modules::Blog/Pages/Index) which
       * are resolved at runtime by Inertia, not during Vite build.
       */
      name: 'module-page-resolver',
      enforce: 'pre',
      configureServer(server) {
        /**
         * Intercept dev server requests for module pages to prevent 404 errors.
         * Return empty JS module to satisfy browser MIME type requirements.
         */
        server.middlewares.use((req, res, next) => {
          if (req.url && req.url.includes('Modules::')) {
            res.statusCode = 200;
            res.setHeader('Content-Type', 'application/javascript');
            res.end('// Module page handled by Inertia resolver');
            return;
          }
          next();
        });
      },
      resolveId(id) {
        /**
         * Mark module page imports as external to exclude from Vite bundle.
         * These are resolved dynamically by Inertia's page resolver.
         */
        if (id.includes('Modules::')) {
          return { id: id, external: true };
        }
        return null;
      },
      handleHotUpdate({ file }) {
        /**
         * Skip HMR for virtual module paths to prevent unnecessary reloads.
         */
        if (file.includes('Modules::')) {
          return [];
        }
        return null;
      },
    },
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      '@modules': path.resolve(__dirname, 'Modules'),
    },
  },
  server: {
    fs: {
      /**
       * Allow Vite to access files outside project root for module support.
       */
      allow: ['..'],
    },
  },
  build: {
    rollupOptions: {
      output: {
        /**
         * Split vendor dependencies into separate chunks for better caching and loading performance.
         * Each major library or group of related libraries gets its own chunk.
         */
        manualChunks: (id) => {
          if (id.includes('node_modules')) {
            if (id.includes('react-hook-form')) {
              return 'react-hook-form-vendor';
            }

            if (
              id.includes('/react/') ||
              id.includes('/react-dom/') ||
              id.includes('/scheduler/')
            ) {
              return 'react-vendor';
            }

            if (id.includes('@inertiajs')) {
              return 'inertia-vendor';
            }

            if (id.includes('framer-motion')) {
              return 'framer-motion-vendor';
            }

            if (id.includes('@tanstack/')) {
              if (id.includes('react-query') || id.includes('query-core')) {
                return 'react-query-vendor';
              }
              if (id.includes('react-table') || id.includes('table-core')) {
                return 'react-table-vendor';
              }
              return 'tanstack-vendor';
            }

            if (id.includes('@radix-ui')) {
              return 'radix-ui-vendor';
            }

            if (id.includes('lucide-react')) {
              return 'icons-vendor';
            }

            if (id.includes('recharts')) {
              return 'charts-vendor';
            }

            if (id.includes('sonner')) {
              return 'toast-vendor';
            }

            if (id.includes('date-fns') || id.includes('react-day-picker')) {
              return 'date-vendor';
            }

            if (id.includes('zustand')) {
              return 'state-vendor';
            }

            if (id.includes('next-themes')) {
              return 'theme-vendor';
            }

            if (
              id.includes('clsx') ||
              id.includes('tailwind-merge') ||
              id.includes('class-variance-authority') ||
              id.includes('use-debounce')
            ) {
              return 'utils-vendor';
            }

            if (id.includes('zod') || id.includes('@hookform')) {
              return 'validation-vendor';
            }

            if (id.includes('axios')) {
              return 'http-vendor';
            }

            if (id.includes('@headlessui')) {
              return 'headlessui-vendor';
            }

            if (id.includes('@fontsource')) {
              return 'fonts-vendor';
            }

            return 'vendor';
          }
        },
      },
    },
    chunkSizeWarningLimit: 1000,
  },
});
