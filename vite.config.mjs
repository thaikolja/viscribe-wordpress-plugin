import {defineConfig}  from 'vite';
import path            from 'path';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const isProduction = process.env.NODE_ENV === 'production';
const suffix = isProduction ? '.min' : '';

export default defineConfig({
  build:   {
    outDir:        'assets',
    emptyOutDir:   false,
    sourcemap:     true,
    minify:        isProduction,
    rollupOptions: {
      input:  {
        main: path.resolve(__dirname, 'assets/js/main.js'),
      },
      output: {
        entryFileNames: `js/scripts${suffix}.js`,
        chunkFileNames: `js/[name]${suffix}.js`,
      },
    },
  },
  server:  {
    port:  3000,
    host:  true,
    watch: {
      ignored: ['**/vendor/**', '**/node_modules/**'],
    },
  },
});
