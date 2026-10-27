import test from 'node:test';
import assert from 'node:assert/strict';
import { unreleasedNotes } from '../../../../scripts/release-check.mjs';

// The release gate (scripts/release-check.mjs, run by release.yml and npm run release:zip) stops a release while
// notes wait under "## Unreleased".

test('notes under "## Unreleased" are found, an empty section or none is not', () => {
    assert.equal(unreleasedNotes('# Changelog\n\n## Unreleased\n\n- Fixed: a thing.\n\n## 2.2.0 (2026-10-22)\n\n- Old.\n'), '- Fixed: a thing.');
    assert.equal(unreleasedNotes('# Changelog\n\n## Unreleased\n\n## 2.2.0 (2026-10-22)\n\n- Old.\n'), '');
    assert.equal(unreleasedNotes('# Changelog\n\n## 2.2.0 (2026-10-22)\n\n- Old.\n'), '');
    assert.equal(unreleasedNotes('# Changelog\n\n## Unreleased\n\n- Last section.\n'), '- Last section.');
});
