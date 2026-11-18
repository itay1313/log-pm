import { defineConfig } from 'vite';

export default defineConfig({
  base: '',
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: 'src/main.js',
        // The web brand book at /brand: its own shell, no map, no Lenis.
        brand: 'src/brand/main.js',
      },
    },
  },
  worker: {
    format: 'es',
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
  },
});
