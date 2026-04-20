import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
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
        assetFileNames: (assetInfo) =>
          assetInfo.names.includes('style.css') ? 'chat.css' : '[name][extname]',
      },
    },
  },
});
