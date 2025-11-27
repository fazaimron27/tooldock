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
      // Prevents Vite from trying to resolve module page paths as actual files
      // Module pages are handled by Inertia's resolver in app.jsx, not Vite
      name: 'module-page-resolver',
      enforce: 'pre',
      configureServer(server) {
        // Intercepts browser requests for module pages and returns empty JS
        // This satisfies browser MIME type checks without Vite processing the path
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
        // Marks module page IDs as external to prevent Vite from bundling them
        if (id.includes('Modules::')) {
          return { id: id, external: true };
        }
        return null;
      },
      handleHotUpdate({ file }) {
        // Prevents HMR from reloading module pages with virtual path syntax
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
      allow: ['..'],
    },
  },
});
