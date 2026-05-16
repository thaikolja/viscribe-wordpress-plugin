#!/usr/bin/env node

import fs              from 'fs';
import path            from 'path';
import {fileURLToPath} from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const pluginDir = path.join(__dirname, '..');
const isProduction = process.env.NODE_ENV === 'production';

// ── Generate index.asset.php ──

const packageJson = JSON.parse(
    fs.readFileSync(path.join(pluginDir, 'package.json'), 'utf8')
);

const assetFileContent = `<?php
return array(
	'dependencies' => array(),
	'version'      => '${packageJson.version}',
);`;

const outputDir = path.join(pluginDir, 'assets', 'js');
const outputFile = path.join(outputDir, 'index.asset.php');

if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, {recursive: true});
}

fs.writeFileSync(outputFile, assetFileContent, 'utf8');
console.log('✓ Generated index.asset.php');

// ── Minify CSS for production ──

if (isProduction) {
  const cssSource = path.join(pluginDir, 'assets', 'css', 'styles.css');
  const cssOutput = path.join(pluginDir, 'assets', 'css', 'styles.min.css');

  if (fs.existsSync(cssSource)) {
    let css = fs.readFileSync(cssSource, 'utf8');

    css = css
        .replace(/\/\*[\s\S]*?\*\//g, '')
        .replace(/\s*([{}:;,])\s*/g, '$1')
        .replace(/\s+/g, ' ')
        .trim();

    fs.writeFileSync(cssOutput, css, 'utf8');
    console.log(`✓ Minified styles.css → styles.min.css (${Buffer.byteLength(css, 'utf8')} B)`);
  } else {
    console.warn('  Warning: assets/css/styles.css not found, skipping CSS minification');
  }
}
