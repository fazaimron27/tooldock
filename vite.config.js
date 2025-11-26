import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import { defineConfig } from 'vite';

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
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      '@modules': path.resolve(__dirname, 'Modules'),
    },
  },
});
