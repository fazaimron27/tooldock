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
      '@Modules': path.resolve(__dirname, 'Modules'),
      '@AuditLog': path.resolve(__dirname, 'Modules/AuditLog/resources/assets/js'),
      '@Categories': path.resolve(__dirname, 'Modules/Categories/resources/assets/js'),
      '@Core': path.resolve(__dirname, 'Modules/Core/resources/assets/js'),
      '@Groups': path.resolve(__dirname, 'Modules/Groups/resources/assets/js'),
      '@Media': path.resolve(__dirname, 'Modules/Media/resources/assets/js'),
      '@Settings': path.resolve(__dirname, 'Modules/Settings/resources/assets/js'),
      '@Signal': path.resolve(__dirname, 'Modules/Signal/resources/assets/js'),
      '@Vault': path.resolve(__dirname, 'Modules/Vault/resources/assets/js'),
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
         * Let Vite automatically split chunks based on usage patterns and dependencies.
         * This ensures proper module loading order and prevents circular dependency issues.
         * Vite will automatically create optimized vendor chunks for node_modules.
         */
        manualChunks: undefined,
      },
    },
    // Warn about chunks larger than 500 kB (Vite default)
    chunkSizeWarningLimit: 500,
  },
});
