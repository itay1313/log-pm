import { defineConfig } from 'vite';

export default defineConfig({
  base: '',
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: 'src/main.js',
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
  },
});
