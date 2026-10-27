import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// ARCHITECTURE.md, section 2: each layer depends only on the ones below it. The behaviour core (core/, behavior/,
// plugins/) imports no framework adapter (adapters/: Alpine, Livewire), so React and Vue adapters can use it
// without pulling in Livewire or Alpine code.

const js = fileURLToPath(new URL('../../js/', import.meta.url));
const CORE = ['core', 'behavior', 'plugins'];

test('the behaviour core imports no framework adapter', () => {
    const offenders = [];
    for (const folder of CORE) {
        for (const file of fs.readdirSync(path.join(js, folder)).filter((name) => name.endsWith('.js'))) {
            const source = fs.readFileSync(path.join(js, folder, file), 'utf8');
            for (const [, from] of source.matchAll(/^import[^'"]*['"]([^'"]+)['"]/gm)) {
                if (/(^|\/)adapters\//.test(from) || /^(alpinejs|livewire)/.test(from)) offenders.push(`${folder}/${file} → ${from}`);
            }
        }
    }

    assert.deepEqual(offenders, []);
});
