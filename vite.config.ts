import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
  },
  plugins: [react()],
  build: {
    cssCodeSplit: false,
    emptyOutDir: true,
    lib: {
      entry: resolve(__dirname, 'frontend/src/main.tsx'),
      formats: ['iife'],
      fileName: () => 'chat.js',
      name: 'CraftSkillChat',
    },
    outDir: resolve(__dirname, 'src/web/assets/chat/dist'),
    rollupOptions: {
      output: {
        banner:
          'var process = globalThis.process || (globalThis.process = { env: { NODE_ENV: "production" } });',
        assetFileNames: (assetInfo) =>
          assetInfo.names.some((name) => name.endsWith('.css')) ? 'chat.css' : '[name][extname]',
      },
    },
  },
});
