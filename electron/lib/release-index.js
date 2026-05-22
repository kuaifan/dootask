// 仅这些扩展名进入下载索引（排除 .zip：mac 自动更新增量包，非下载按钮目标）
const DOWNLOAD_EXTS = ['.dmg', '.exe', '.msi', '.appimage', '.deb', '.rpm', '.apk', '.pkg'];

/**
 * 从文件名解析 platform/arch（与官网 storage.ts 规则保持一致）
 * @returns {{platform: string, arch: string|null}|null}
 */
function parseFilename(filename) {
    const lower = filename.toLowerCase();
    if (lower.endsWith('.apk')) {
        return { platform: 'android', arch: null };
    }
    if (!DOWNLOAD_EXTS.some((ext) => lower.endsWith(ext))) {
        return null;
    }
    let platform = null;
    if (/-mac-/i.test(filename) || lower.endsWith('.dmg') || lower.endsWith('.pkg')) {
        platform = 'mac';
    } else if (/-win-/i.test(filename) || /-win\./i.test(filename) || lower.endsWith('.msi')) {
        platform = 'win';
    } else if (/-linux-/i.test(filename) || lower.endsWith('.appimage') || lower.endsWith('.deb') || lower.endsWith('.rpm')) {
        platform = 'linux';
    }
    if (!platform) return null;
    let arch = null;
    if (/-arm64[.-]/i.test(filename)) arch = 'arm64';
    else if (/-x64[.-]/i.test(filename)) arch = 'x64';
    return { platform, arch };
}

/**
 * 生成下载索引：{ "<platform>": { "<arch|default>": filename } }
 */
function buildReleaseIndex(filenames) {
    const index = {};
    for (const filename of filenames) {
        const parsed = parseFilename(filename);
        if (!parsed) continue;
        const archKey = parsed.arch || 'default';
        index[parsed.platform] = index[parsed.platform] || {};
        index[parsed.platform][archKey] = filename;
    }
    return index;
}

module.exports = { parseFilename, buildReleaseIndex, DOWNLOAD_EXTS };
