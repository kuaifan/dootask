#!/usr/bin/env node
// 计算并写入新版本号到 package.json（version + codeVerson），算法对齐 bin/version.js。
// 不生成 CHANGELOG（在技能流程内撰写），只输出版本号与 changelog 的提交区间。
//
// 项目根相对脚本自身定位（脚本固定在 <root>/.claude/skills/dootask-release/scripts/），与调用时的 cwd 无关。
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT = path.resolve(__dirname, '../../../..');
const pkgFile = path.join(ROOT, 'package.json');
const verOffset = 6394; // 版本号偏移量（与 bin/version.js 一致）
const codeOffset = 35;  // 代码版本号偏移量

function git(cmd) {
    return execSync(cmd, { cwd: ROOT, maxBuffer: 1024 * 1024 * 10 }).toString().trim();
}

const verCount = parseInt(git('git rev-list --count HEAD'), 10);
const codeCount = parseInt(git("git tag --merged pro -l 'v*' | wc -l"), 10);
const num = verOffset + verCount;
if (Number.isNaN(num)) {
    console.error(`版本计算失败：rev-list count=${verCount}`);
    process.exit(1);
}
const version = `${Math.floor(num / 10000)}.${Math.floor((num % 10000) / 100)}.${Math.floor(num % 100)}`;
const codeVersion = codeOffset + codeCount;

let pkg = fs.readFileSync(pkgFile, 'utf8');
const prevVersion = (pkg.match(/"version":\s*"(.*?)"/) || [])[1] || '';
pkg = pkg.replace(/"version":\s*"(.*?)"/, `"version": "${version}"`);
pkg = pkg.replace(/"codeVerson":(.*?)(,|$)/, `"codeVerson": ${codeVersion}$2`);
fs.writeFileSync(pkgFile, pkg, 'utf8');

// 上一个 release 提交作为 changelog 区间下界
let prevReleaseCommit = '';
try {
    prevReleaseCommit = git("git log --grep='^release: v' -n 1 --pretty=format:%H");
} catch (e) { /* ignore */ }

console.log(JSON.stringify({
    version,
    codeVersion,
    prevVersion,
    prevReleaseCommit,
    changelogRange: prevReleaseCommit ? `${prevReleaseCommit}..HEAD` : '(未找到上一个 release 提交，需人工确定区间)',
}, null, 2));
