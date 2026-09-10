const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const {patchSource} = require('./patch-mac-keychain');

const source = `
async function createKeychain(keychainFile, certPaths, cscPasswords) {
    const keychainPassword = "random-keychain-password";
    return await importCerts(keychainFile, certPaths, cscPasswords);
}
async function importCerts(keychainFile, paths, keyPasswords) {
    for (let i = 0; i < paths.length; i++) {
        const password = keyPasswords[i] ?? "";
        await exec("/usr/bin/security", ["import", paths[i], "-k", keychainFile, "-P", password]);
        await exec("/usr/bin/security", ["set-key-partition-list", "-S", "apple-tool:,apple:", "-s", "-k", password, keychainFile]);
    }
}
`;

for (const version of ['26.8.1', '26.15.3']) {
    test(`patch is idempotent for ${version}`, () => {
        const patched = patchSource(source, version);
        assert.notEqual(patched, source);
        assert.equal(patchSource(patched, version), patched);
    });
}

for (const passwords of [[''], ['certificate-password'], ['distribution-password', 'installer-password']]) {
    test(`keeps certificate passwords separate: ${passwords.length} certificate(s), empty=${passwords[0] === ''}`, async () => {
        const calls = [];
        const context = vm.createContext({exec: async (command, args) => calls.push({command, args})});
        vm.runInContext(patchSource(source, '26.15.3'), context);
        await context.createKeychain('test.keychain', passwords.map((_, i) => `cert-${i}.p12`), passwords);
        assert.equal(calls.length, passwords.length * 2);
        for (let i = 0; i < passwords.length; i++) {
            assert.equal(calls[i * 2].args.at(-1), passwords[i]);
            assert.equal(calls[i * 2 + 1].args.at(-2), 'random-keychain-password');
        }
    });
}

test('rejects unknown versions and changed or partially patched sources', () => {
    assert.throws(() => patchSource(source, '27.0.0'), /Unsupported/);
    assert.throws(() => patchSource(source.replace('return await importCerts', 'return importCerts'), '26.15.3'), /Unexpected/);
    assert.throws(() => patchSource(source + source, '26.15.3'), /Unexpected/);
    assert.throws(() => patchSource(source.replace('cscPasswords);', 'cscPasswords, keychainPassword);'), '26.15.3'), /Unexpected/);
});
