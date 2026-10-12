#!/usr/bin/env node
// Smoke runner: mounts the plugin + stubs into php-wasm MEMFS and runs tests.php.
const fs = require('fs');
const path = require('path');
const { PhpNode } = require('php-wasm/PhpNode');

const pluginDir = process.argv[2];
if (!pluginDir) {
  console.error('usage: node smoke.js /path/to/mojrian-club');
  process.exit(2);
}

function walk(dir, base = dir) {
  const out = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) out.push(...walk(p, base));
    else out.push({ fs: p, mem: p.slice(base.length).split(path.sep).join('/') });
  }
  return out;
}

async function mkdirp(php, dir) {
  const parts = dir.split('/').filter(Boolean);
  let cur = '';
  for (const p of parts) {
    cur += '/' + p;
    try { await php.mkdir(cur); } catch (e) { /* exists */ }
  }
}

(async () => {
  const php = new PhpNode();
  let seen = false;
  php.addEventListener('output', (e) => { seen = true; process.stdout.write((Array.isArray(e.detail) ? e.detail.join('') : e.detail)); });
  php.addEventListener('error', (e) => { seen = true; process.stderr.write((Array.isArray(e.detail) ? e.detail.join('') : e.detail)); });
  await php.binary;

  await mkdirp(php, '/plugin');
  await mkdirp(php, '/stubs');

  for (const f of walk(pluginDir)) {
    const target = '/plugin/' + f.mem;
    await mkdirp(php, target.slice(0, target.lastIndexOf('/')));
    await php.writeFile(target, fs.readFileSync(f.fs));
  }

  const stubRoot = path.join(__dirname, 'stubs');
  for (const f of walk(stubRoot)) {
    const target = '/stubs/' + f.mem;
    await mkdirp(php, target.slice(0, target.lastIndexOf('/')));
    await php.writeFile(target, fs.readFileSync(f.fs));
  }

  // Mount stubs/wp-core at ABSPATH (/tmp/wp/) so WP include paths resolve.
  const coreRoot = path.join(__dirname, 'stubs', 'wp-core');
  for (const f of walk(coreRoot)) {
    const target = '/tmp/wp/' + f.mem;
    await mkdirp(php, target.slice(0, target.lastIndexOf('/')));
    await php.writeFile(target, fs.readFileSync(f.fs));
  }

  await php.writeFile('/tests.php', fs.readFileSync(path.join(__dirname, 'tests.php')));

  const res = await php.run('<?php require "/tests.php";');
  const log = (Array.isArray(res) ? res.join('') : String(res));
  if (!seen) process.stdout.write(log);
  process.exit(log.includes('RESULT: OK') ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
