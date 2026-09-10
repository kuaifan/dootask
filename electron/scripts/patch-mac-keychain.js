const fs = require('node:fs');
const path = require('node:path');
const {createRequire} = require('node:module');

// Remove this workaround once the supported builder uses the keychain password.
const replacements = [
    ['return await importCerts(keychainFile, certPaths, cscPasswords);',
        'return await importCerts(keychainFile, certPaths, cscPasswords, keychainPassword);'],
    ['async function importCerts(keychainFile, paths, keyPasswords) {',
        'async function importCerts(keychainFile, paths, keyPasswords, keychainPassword) {'],
    ['["set-key-partition-list", "-S", "apple-tool:,apple:", "-s", "-k", password, keychainFile]',
        '["set-key-partition-list", "-S", "apple-tool:,apple:", "-s", "-k", keychainPassword, keychainFile]'],
];

function patchSource(source, version) {
    if (!['26.8.1', '26.15.3'].includes(version)) {
        throw new Error(`Unsupported app-builder-lib ${version}; review the macOS keychain workaround before building.`);
    }
    const count = (text) => source.split(text).length - 1;
    if (replacements.every(([before, after]) => count(before) === 0 && count(after) === 1)) {
        return source;
    }
    if (!replacements.every(([before, after]) => count(before) === 1 && count(after) === 0)) {
        throw new Error('Unexpected macCodeSign.js source; refusing to apply the keychain workaround.');
    }
    for (const [before, after] of replacements) {
        source = source.replace(before, after);
    }
    return source;
}

function patchInstalledBuilder() {
    // Resolve from electron-builder so a nested dependency is patched correctly.
    const builderRequire = createRequire(require.resolve('electron-builder/package.json'));
    const packageFile = builderRequire.resolve('app-builder-lib/package.json');
    const {version} = JSON.parse(fs.readFileSync(packageFile, 'utf8'));
    const file = path.join(path.dirname(packageFile), 'out/codeSign/macCodeSign.js');
    const source = fs.readFileSync(file, 'utf8');
    const patched = patchSource(source, version);
    if (patched !== source) {
        fs.writeFileSync(file, patched);
    }
    console.log(`macOS keychain workaround ready (app-builder-lib ${version}).`);
}

if (require.main === module) {
    patchInstalledBuilder();
}

module.exports = {patchSource};
