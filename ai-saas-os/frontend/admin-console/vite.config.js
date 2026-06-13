import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const rootDir = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');

function commandOutput(command, fallback = 'unknown') {
  try {
    return execSync(command, { cwd: rootDir, stdio: ['ignore', 'pipe', 'ignore'] }).toString().trim() || fallback;
  } catch {
    return fallback;
  }
}

function stableVersion() {
  const stableTagPath = path.join(rootDir, 'STABLE_TAG.md');
  if (!fs.existsSync(stableTagPath)) return 'unknown';
  const contents = fs.readFileSync(stableTagPath, 'utf8');
  return contents.match(/Current stable version:\s*(.+)/)?.[1]?.trim() || 'unknown';
}

export default defineConfig({
  base: '/console/',
  plugins: [react()],
  define: {
    __APP_VERSION__: JSON.stringify(process.env.VITE_APP_VERSION || stableVersion()),
    __GIT_COMMIT__: JSON.stringify(process.env.VITE_GIT_COMMIT || commandOutput('git rev-parse --short HEAD')),
    __BUILD_TIME__: JSON.stringify(process.env.VITE_BUILD_TIME || new Date().toISOString()),
  },
  build: {
    outDir: '../../public/console',
    emptyOutDir: true,
  },
});
