const { PhpNode } = require('php-wasm/PhpNode');
(async () => {
  const php = new PhpNode();
  let out = '';
  php.addEventListener('output', (e) => { out += (Array.isArray(e.detail) ? e.detail.join('') : e.detail); });
  await php.binary;
  await php.run(`<?php
$fns = get_defined_functions();
$all = array_merge($fns['internal'], $fns['user']);
sort($all);
file_put_contents('/php_builtins.txt', implode("\\n", $all));
$groups = get_defined_constants(true);
$allc = array();
foreach ($groups as $g) { foreach ($g as $name => $v) { $allc[] = $name; } }
sort($allc);
file_put_contents('/php_constants.txt', implode("\\n", $allc));
echo "done " . count($all) . " fns " . count($allc) . " consts";
`);
  console.log(out);
  const fs = require('fs');
  fs.writeFileSync(__dirname + '/php_builtins.txt', await php.readFile('/php_builtins.txt'));
  fs.writeFileSync(__dirname + '/php_constants.txt', await php.readFile('/php_constants.txt'));
})().catch(e => { console.error(e); process.exit(1); });
