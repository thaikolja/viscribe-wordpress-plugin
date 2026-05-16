#!/usr/bin/env node

import AdmZip          from 'adm-zip';
import fs              from 'fs';
import path            from 'path';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const pluginDir = path.join(__dirname, '..');
const packageName = 'viscribe';
const zipFileName = `${packageName}.zip`;
const zipPath = path.join(pluginDir, zipFileName);

const DEV_VENDOR_PACKAGES = new Set([
  'dealerdirect',
  'kint-php',
  'phpcsstandards',
  'phpstan',
  'squizlabs',
]);

function shouldExcludeFromVendor(entryPath) {
  const parts = entryPath.split('/');

  if (parts.length >= 2 && DEV_VENDOR_PACKAGES.has(parts[1])) {
    return true;
  }

  const basename = path.basename(entryPath);

  if (/^\.(git|DS_Store|distignore|babelrc|eslintignore|eslintrc\.json)$/i.test(basename)) {
    return true;
  }

  if (/^(build-phar\.sh|phpcs\.xml\.dist|phpunit\.xml|phpstan\.neon|phpstan\.baseline\.neon)$/i.test(basename)) {
    return true;
  }

  if (/^changelog$/i.test(basename) && !path.extname(basename)) {
    return true;
  }

  if (/^readme\.(md|rst|txt)$/i.test(basename)) {
    return true;
  }

  if (/\/docs(\/|$)/i.test(entryPath) || /\/tests?(\/|$)/i.test(entryPath)) {
    return true;
  }

  if (/\/bin\//i.test(entryPath)) {
    return true;
  }

  return false;
}

const ENTRIES = [
  'viscribe.php',
  'uninstall.php',
  'readme.txt',
  'LICENSE',
  'composer.json',
  'composer.lock',
  'includes',
  'assets/js/scripts.min.js',
  'assets/js/scripts.min.js.map',
  'assets/js/index.asset.php',
  'assets/css/styles.min.css',
  'assets/icons',
  'views',
  'languages',
  'vendor',
];

console.log('Creating viscribe.zip...');
const zip = new AdmZip();

for (const entry of ENTRIES) {
  const absPath = path.join(pluginDir, entry);
  if (!fs.existsSync(absPath)) {
    console.warn(`  Warning: ${entry} not found, skipping`);
    continue;
  }

  const stat = fs.statSync(absPath);

  if (stat.isDirectory()) {
    const files = walkSync(absPath);
    let addedCount = 0;
    for (const file of files) {
      const relative = path.relative(pluginDir, file);
      const zipName = path.posix.join(packageName, relative.split(path.sep).join('/'));

      if (relative.startsWith('vendor') && shouldExcludeFromVendor(relative)) {
        continue;
      }

      zip.addFile(zipName, fs.readFileSync(file));
      addedCount++;
    }
    console.log(`  Added ${entry}/ (${addedCount} files)`);
  } else {
    const zipName = path.posix.join(packageName, entry.split(path.sep).join('/'));
    zip.addFile(zipName, fs.readFileSync(absPath));
    console.log(`  Added ${entry}`);
  }
}

zip.writeZip(zipPath);
console.log(`✓ ${zipFileName} created at ${zipPath}`);

function walkSync(dir) {
  const results = [];
  const list = fs.readdirSync(dir);
  for (const item of list) {
    if (item === '.DS_Store') continue;
    const filePath = path.join(dir, item);
    const stat = fs.statSync(filePath);
    if (stat.isDirectory()) {
      results.push(...walkSync(filePath));
    } else {
      results.push(filePath);
    }
  }
  return results;
}
