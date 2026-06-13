#!/usr/bin/env node
/**
 * 事件总线注册表生成脚本
 *
 * 扫描前端代码中 mitt 事件总线（resources/assets/js/store/events.js 导出的 emitter）
 * 的 emit/on/off 调用，按事件名聚合生成 docs/events-map.md。
 *
 * 用法: node scripts/gen-events-map.mjs
 * 零第三方依赖（仅 node:fs / node:path）。
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// ===== 常量配置 =====
const ROOT_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
// 扫描范围（相对仓库根目录）
const SCAN_DIR = 'resources/assets/js';
// 输出文件（相对仓库根目录）
const OUTPUT_FILE = 'docs/events-map.md';
// 扫描的文件扩展名
const EXTENSIONS = new Set(['.js', '.vue']);
// ====================

// 匹配 emitter.emit( / emitter.on( / emitter.off(
// 负向断言排除 xxx.emitter.emit（如 Quill 的 this.quill.emitter，不是 mitt 总线）
const CALL_RE = /(?<![.\w])emitter\.(emit|on|off)\s*\(/g;
// 第一参数为字符串字面量：'xxx' 或 "xxx"
const LITERAL_RE = /^\s*(['"])((?:\\.|(?!\1).)*?)\1/;

/** 递归收集待扫描文件 */
function collectFiles(dir) {
    const result = [];
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            result.push(...collectFiles(full));
        } else if (entry.isFile() && EXTENSIONS.has(path.extname(entry.name))) {
            result.push(full);
        }
    }
    return result;
}

/** 由字符偏移计算行号（1-based） */
function lineOf(content, offset) {
    let line = 1;
    for (let i = 0; i < offset; i++) {
        if (content.charCodeAt(i) === 10) line++;
    }
    return line;
}

const scanRoot = path.join(ROOT_DIR, SCAN_DIR);
const files = collectFiles(scanRoot).sort();

// events: Map<eventName, {emit: loc[], on: loc[], off: loc[]}>
const events = new Map();
// 动态事件名（第一参数非字符串字面量）
const dynamics = [];
let totalCalls = 0;

for (const file of files) {
    const content = fs.readFileSync(file, 'utf8');
    const rel = path.relative(ROOT_DIR, file).split(path.sep).join('/');
    let m;
    CALL_RE.lastIndex = 0;
    while ((m = CALL_RE.exec(content)) !== null) {
        totalCalls++;
        const method = m[1];
        const argStart = m.index + m[0].length;
        const line = lineOf(content, m.index);
        const loc = `${rel}:${line}`;
        const rest = content.slice(argStart, argStart + 200);
        const lit = rest.match(LITERAL_RE);
        if (lit) {
            const name = lit[2];
            if (!events.has(name)) {
                events.set(name, { emit: [], on: [], off: [] });
            }
            events.get(name)[method].push(loc);
        } else {
            // 截取第一参数片段用于展示
            const snippet = rest.split(/[\n,)]/)[0].trim();
            dynamics.push({ method, loc, snippet });
        }
    }
}

const names = [...events.keys()].sort();

// 统计
const deadEmit = names.filter(n => events.get(n).emit.length > 0 && events.get(n).on.length === 0);
const deadOn = names.filter(n => events.get(n).on.length > 0 && events.get(n).emit.length === 0);

// ===== 生成 Markdown =====
const out = [];
out.push('# 前端事件总线注册表');
out.push('');
out.push('> **本文件由脚本自动生成，请勿手改。**');
out.push('>');
out.push('> - 生成命令: `node scripts/gen-events-map.mjs`');
out.push(`> - 扫描范围: \`${SCAN_DIR}\` 下所有 \`.js\` / \`.vue\` 文件（共 ${files.length} 个）`);
out.push('> - 事件总线: `resources/assets/js/store/events.js`（mitt 实例）');
out.push('> - 仅匹配裸 `emitter.emit/on/off(` 调用；`xxx.emitter.emit(`（如 Quill 内部 emitter）不属于本总线，已排除');
out.push('');
out.push(`共 **${names.length}** 个静态可解析事件，**${totalCalls}** 处 \`emitter.emit/on/off\` 调用。`);
out.push('');
out.push('## 事件清单');
out.push('');

for (const name of names) {
    const ev = events.get(name);
    out.push(`### \`${name}\``);
    out.push('');
    out.push(`- **emit（${ev.emit.length}）**${ev.emit.length ? '' : '：无（疑似死事件）'}`);
    for (const loc of ev.emit) out.push(`  - \`${loc}\``);
    out.push(`- **on（${ev.on.length}）**${ev.on.length ? '' : '：无（无人监听）'}`);
    for (const loc of ev.on) out.push(`  - \`${loc}\``);
    if (ev.off.length) {
        out.push(`- **off（${ev.off.length}）**`);
        for (const loc of ev.off) out.push(`  - \`${loc}\``);
    }
    out.push('');
}

out.push('## 动态事件名（无法静态解析）');
out.push('');
if (dynamics.length === 0) {
    out.push('无。');
} else {
    out.push('以下调用的第一参数不是字符串字面量，无法静态解析事件名：');
    out.push('');
    for (const d of dynamics) {
        out.push(`- \`${d.loc}\` — \`emitter.${d.method}(${d.snippet}...)\``);
    }
}
out.push('');
out.push('## 统计');
out.push('');
out.push(`- 事件总数（静态可解析）: **${names.length}**`);
out.push(`- 只 emit 无 on（疑似死事件）: **${deadEmit.length}**${deadEmit.length ? ` — ${deadEmit.map(n => `\`${n}\``).join('、')}` : ''}`);
out.push(`- 只 on 无 emit（无人发射）: **${deadOn.length}**${deadOn.length ? ` — ${deadOn.map(n => `\`${n}\``).join('、')}` : ''}`);
out.push(`- 动态事件名调用: **${dynamics.length}**`);
out.push('');

const outputPath = path.join(ROOT_DIR, OUTPUT_FILE);
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, out.join('\n'), 'utf8');

console.log(`[gen-events-map] 扫描 ${files.length} 个文件，${totalCalls} 处调用，${names.length} 个事件，${dynamics.length} 处动态事件名`);
console.log(`[gen-events-map] 已生成 ${OUTPUT_FILE}`);
console.log(`[gen-events-map] 只 emit 无 on: ${deadEmit.length ? deadEmit.join(', ') : '无'}`);
console.log(`[gen-events-map] 只 on 无 emit: ${deadOn.length ? deadOn.join(', ') : '无'}`);
