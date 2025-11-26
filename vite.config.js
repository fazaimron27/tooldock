import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
  plugins: [
    laravel({
      input: "resources/js/app.jsx",
      refresh: [
        "resources/views/**",
        "Modules/*/resources/views/**",
        "Modules/*/resources/assets/js/**",
      ],
    }),
    react(),
  ],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./resources/js"),
      "@modules": path.resolve(__dirname, "Modules"),
    },
  },
});
