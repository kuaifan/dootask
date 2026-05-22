const test = require('node:test');
const assert = require('node:assert');
const { parseFilename, buildReleaseIndex } = require('./release-index');

test('parseFilename: win exe x64', () => {
    assert.deepStrictEqual(parseFilename('DooTask-v1.7.56-win-x64.exe'), { platform: 'win', arch: 'x64' });
});

test('parseFilename: mac dmg arm64', () => {
    assert.deepStrictEqual(parseFilename('DooTask-v1.7.56-mac-arm64.dmg'), { platform: 'mac', arch: 'arm64' });
});

test('parseFilename: android apk has null arch', () => {
    assert.deepStrictEqual(parseFilename('app-release.apk'), { platform: 'android', arch: null });
});

test('parseFilename: ignores yml/blockmap/zip', () => {
    assert.strictEqual(parseFilename('latest.yml'), null);
    assert.strictEqual(parseFilename('DooTask-v1.7.56-win-x64.exe.blockmap'), null);
    assert.strictEqual(parseFilename('DooTask-v1.7.56-mac-arm64.zip'), null);
});

test('buildReleaseIndex: groups by platform/arch, .zip never overwrites .dmg', () => {
    const index = buildReleaseIndex([
        'DooTask-v1.7.56-mac-arm64.dmg',
        'DooTask-v1.7.56-mac-arm64.zip',
        'DooTask-v1.7.56-win-x64.exe',
        'latest.yml',
        'app-release.apk',
    ]);
    assert.deepStrictEqual(index, {
        mac: { arm64: 'DooTask-v1.7.56-mac-arm64.dmg' },
        win: { x64: 'DooTask-v1.7.56-win-x64.exe' },
        android: { default: 'app-release.apk' },
    });
});
