import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { fileURLToPath } from 'url';
import { defineConfig, loadEnv } from 'vite';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, __dirname, 'VITE_');
  const devServerUrl = env.VITE_DEV_SERVER_URL ? new URL(env.VITE_DEV_SERVER_URL) : null;

  return {
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
      /**
       * Force a single copy of React across the dependency graph. Prevents
       * "Invalid hook call / dispatcher is null" when a transitive dependency
       * (e.g. alasql -> react-native-fs -> react-native) nests its own React.
       */
      dedupe: ['react', 'react-dom'],
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
        '@Treasury': path.resolve(__dirname, 'Modules/Treasury/resources/assets/js'),
        '@Routine': path.resolve(__dirname, 'Modules/Routine/resources/assets/js'),
        '@QuickDraw': path.resolve(__dirname, 'Modules/QuickDraw/resources/assets/js'),
        '@Folio': path.resolve(__dirname, 'Modules/Folio/resources/assets/js'),
        '@Hook': path.resolve(__dirname, 'Modules/Hook/resources/assets/js'),
        '@Bot': path.resolve(__dirname, 'Modules/Bot/resources/assets/js'),
        '@Nucleus': path.resolve(__dirname, 'Modules/Nucleus/resources/assets/js'),
      },
    },
    optimizeDeps: {
      exclude: ['@tldraw/assets'],
    },
    server: {
      ...(devServerUrl
        ? {
            origin: devServerUrl.origin,
            cors: true,
            hmr: {
              host: devServerUrl.hostname,
              protocol: devServerUrl.protocol === 'https:' ? 'wss' : 'ws',
              clientPort:
                Number(devServerUrl.port) || (devServerUrl.protocol === 'https:' ? 443 : 80),
            },
          }
        : {}),
      fs: {
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
  };
});
