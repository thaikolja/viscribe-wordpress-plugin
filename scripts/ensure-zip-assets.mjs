/*
 * @name:           AI Image Renamer
 * @wordpress       Uses AI to rename images during upload for SEO-friendly filenames.
 * @author          Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright       2025-2026 (C) Kolja Nolte
 * @see             https://docs.kolja-nolte.com/viscribe
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Released under the GNU General Public License v2 or later.
 * See: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AIR
 * @license GPL-2.0-or-later
 */


import AdmZip          from 'adm-zip';
import fs              from 'fs';
import path            from 'path';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const pluginDir = path.join(__dirname, '..');
const packageName = 'viscribe';
const zipPath = path.join(pluginDir, `${packageName}.zip`);
const tempZipPath = `${zipPath}.tmp`;

const zip = new AdmZip(zipPath);
const assetPaths = [
  'assets/js/index.js',
  'assets/js/index.asset.php',
  'assets/css/index.css',
  'assets/css/main.css',
];

for (const relativePath of assetPaths) {
  const filePath = path.join(pluginDir, relativePath);
  if (!fs.existsSync(filePath)) {
    continue;
  }

  const entryName = path.posix.join(packageName, relativePath.split(path.sep).join('/'));
  zip.addFile(entryName, fs.readFileSync(filePath));
}

zip.writeZip(tempZipPath);
fs.renameSync(tempZipPath, zipPath);
console.log('✓ Ensured built assets are included in viscribe.zip');

