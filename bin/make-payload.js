#!/usr/bin/env node
/*!
 * make-payload.js
 *
 * Generate a base64url(JSON) payload for the FormPrefill plugin.
 *
 * Usage examples:
 *   node bin/make-payload.js --vars project=K8s --fields subject="Hello {{project}}" --fields message="Line1\nLine2"
 *   node bin/make-payload.js --out payload.txt --fields message="@message.txt"
 *
 * Notes:
 * - Use @file to read value from a file.
 * - Newlines: you can pass literal \n in the CLI and it will be converted to real newlines.
 */

'use strict';

const fs = require('fs');

function parseArgs(argv) {
  const out = { vars: {}, fields: {}, outFile: null };
  let i = 0;
  while (i < argv.length) {
    const a = argv[i];
    if (a === '--out') {
      out.outFile = argv[i + 1] || null;
      i += 2;
      continue;
    }
    if (a === '--vars') {
      const kv = argv[i + 1] || '';
      const { k, v } = parseKV(kv);
      out.vars[k] = v;
      i += 2;
      continue;
    }
    if (a === '--fields') {
      const kv = argv[i + 1] || '';
      const { k, v } = parseKV(kv);
      out.fields[k] = v;
      i += 2;
      continue;
    }
    i += 1;
  }
  return out;
}

function parseKV(s) {
  const idx = s.indexOf('=');
  if (idx <= 0) throw new Error(`Invalid key=value: ${s}`);
  const k = s.slice(0, idx);
  let v = s.slice(idx + 1);

  // @file support
  if (v.startsWith('@')) {
    v = fs.readFileSync(v.slice(1), 'utf8');
  }

  // Convert literal \n sequences to newlines
  v = v.replace(/\\n/g, '\n');
  return { k, v };
}

function encodePayloadBase64Url(obj) {
  const json = JSON.stringify(obj);
  const b64 = Buffer.from(json, 'utf8').toString('base64');
  return b64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

function main() {
  const args = parseArgs(process.argv.slice(2));
  const payload = { vars: args.vars, fields: args.fields };
  const enc = encodePayloadBase64Url(payload);

  if (args.outFile) {
    fs.writeFileSync(args.outFile, enc + '\n', 'utf8');
  } else {
    process.stdout.write(enc + '\n');
  }
}

main();
