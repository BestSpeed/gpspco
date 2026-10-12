#!/usr/bin/env node
// Syntax lint: token_get_all(TOKEN_PARSE) surfaces ParseError on bad syntax.
// (php-wasm cannot fork, so `php -l` via passthru is not available.)
const fs = require('fs');
const path = require('path');
const { PhpNode } = require('php-wasm/PhpNode');

const pluginDir = process.argv[2];
if (!pluginDir) {
  console.error('usage: node lint.js /path/to/mojrian-club');
  process.exit(2);
}

function walk(dir, base = dir) {
  const out = [];
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) out.push(...walk(p, base));
    else if (p.endsWith('.php')) out.push({ fs: p, mem: p.slice(base.length).split(path.sep).join('/') });
  }
  return out;
}

(async () => {
  const files = walk(pluginDir);
  const php = new PhpNode();
  let output = '';
  php.addEventListener('output', (e) => { output += (Array.isArray(e.detail) ? e.detail.join('') : e.detail); });
  php.addEventListener('error', (e) => { output += (Array.isArray(e.detail) ? e.detail.join('') : e.detail); });
  await php.binary;

  await php.mkdir('/lint');
  for (const f of files) {
    const target = '/lint/' + f.mem;
    const dir = target.slice(0, target.lastIndexOf('/'));
    try { await php.mkdir(dir); } catch (e) { /* exists */ }
    await php.writeFile(target, fs.readFileSync(f.fs));
  }

  output = '';
  await php.run(`<?php
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/lint'));
$bad = 0; $n = 0;
foreach ($rii as $f) {
  if ($f->isDir() || substr($f->getFilename(), -4) !== '.php') continue;
  $n++;
  $rel = substr($f->getPathname(), 6);
  try {
    token_get_all(file_get_contents($f->getPathname()), TOKEN_PARSE);
    echo "OK   $rel\\n";
  } catch (ParseError $e) {
    $bad++;
    echo "FAIL $rel :: " . $e->getMessage() . "\\n";
  } catch (Throwable $e) {
    $bad++;
    echo "FAIL $rel :: " . $e->getMessage() . "\\n";
  }
}
echo "lint: " . ($n - $bad) . "/$n ok\\n";
`);

  console.log(output);
  process.exit(output.includes('lint: ' + files.length + '/' + files.length + ' ok') ? 0 : 1);
})().catch((e) => {
  console.error(e);
  process.exit(1);
});
