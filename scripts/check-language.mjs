#!/usr/bin/env node
/**
 * 前端翻译文案校验脚本
 *
 * 递归扫描 resources/assets/js 下的 .js/.vue 文件，提取
 * $L('...') / $L("...") / this.$L(...) / $A.L(...) 的第一个字符串字面量参数，
 * 与 language/original-web.txt 的行集合（trim 后）比对：
 *   - 代码中有、txt 中没有 → 「缺失文案」，列出文案及所有出现位置，exit 1
 *   - txt 中有、代码中没有 → 「疑似未使用」，仅输出数量与前 20 条样例，不影响退出码
 *
 * 用法：node scripts/check-language.mjs
 * 零第三方依赖，要求 Node >= 20。
 */

import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, relative, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const SCAN_DIR = join(ROOT, "resources", "assets", "js");
const TXT_FILE = join(ROOT, "language", "original-web.txt");

// ---------- 工具函数 ----------

/** 递归收集 .js/.vue 文件 */
function collectFiles(dir, out = []) {
    for (const name of readdirSync(dir)) {
        const full = join(dir, name);
        const st = statSync(full);
        if (st.isDirectory()) {
            collectFiles(full, out);
        } else if (st.isFile() && /\.(js|vue)$/.test(name)) {
            out.push(full);
        }
    }
    return out;
}

/** 反转义字符串字面量中的常见转义序列 */
function unescapeLiteral(raw) {
    return raw.replace(/\\(.)/g, (_, ch) => {
        switch (ch) {
            case "n": return "\n";
            case "t": return "\t";
            case "r": return "\r";
            default: return ch; // \' \" \\ 以及其他都还原为字符本身
        }
    });
}

/**
 * 从 content 的 pos（指向开引号 ' 或 "）开始解析字符串字面量。
 * 返回 { raw, end }：raw 为引号内原始文本（未反转义），end 指向闭引号的下一位；
 * 未闭合返回 null。
 */
function parseStringLiteral(content, pos) {
    const quote = content[pos];
    let i = pos + 1;
    let raw = "";
    while (i < content.length) {
        const ch = content[i];
        if (ch === "\\") {
            if (i + 1 >= content.length) return null;
            raw += ch + content[i + 1];
            i += 2;
            continue;
        }
        if (ch === quote) {
            return { raw, end: i + 1 };
        }
        if (ch === "\n") return null; // 普通字符串字面量不允许裸换行
        raw += ch;
        i++;
    }
    return null;
}

/**
 * 提取单个文件中所有翻译调用的第一个字符串字面量参数。
 * 返回 [{ text, line }]。
 * 第一个参数不是普通字符串字面量（模板字符串、变量、函数调用等），
 * 或字面量后紧跟 + （拼接表达式）时跳过，不报错。
 */
function extractCalls(content) {
    const results = [];
    // $L( / this.$L( —— 都含 "$L("，要求 $ 前不是标识符字符或 $；$A.L( 单独匹配
    const callRe = /(?<![\w$])\$L\s*\(|\$A\.L\s*\(/g;
    let m;
    while ((m = callRe.exec(content)) !== null) {
        let i = m.index + m[0].length;
        // 跳过空白（含换行）
        while (i < content.length && /\s/.test(content[i])) i++;
        const ch = content[i];
        if (ch !== "'" && ch !== '"') continue; // 模板字符串、变量、其他表达式：跳过
        const lit = parseStringLiteral(content, i);
        if (!lit) continue;
        // 看字面量后第一个非空白字符：是 + 则为拼接表达式，跳过
        let j = lit.end;
        while (j < content.length && /\s/.test(content[j])) j++;
        if (content[j] === "+") continue;
        const text = unescapeLiteral(lit.raw).trim();
        if (!text) continue;
        const line = content.slice(0, m.index).split("\n").length;
        results.push({ text, line });
    }
    return results;
}

// ---------- 主流程 ----------

// 1. 读取 original-web.txt 行集合（trim 后，忽略空行）
const txtLines = new Set(
    readFileSync(TXT_FILE, "utf8")
        .split("\n")
        .map(l => l.trim())
        .filter(Boolean)
);

// 2. 扫描源码并提取
const files = collectFiles(SCAN_DIR);
/** Map<text, Array<"相对路径:行号">> */
const usages = new Map();
for (const file of files) {
    const rel = relative(ROOT, file);
    const content = readFileSync(file, "utf8");
    for (const { text, line } of extractCalls(content)) {
        if (!usages.has(text)) usages.set(text, []);
        usages.get(text).push(`${rel}:${line}`);
    }
}

// 3. 比对
const missing = []; // 代码有、txt 无
for (const [text, locations] of usages) {
    if (!txtLines.has(text)) {
        missing.push({ text, locations });
    }
}
const unused = [...txtLines].filter(l => !usages.has(l)); // txt 有、代码无

// 4. 输出
if (missing.length > 0) {
    console.log("== 缺失文案（代码中使用但 language/original-web.txt 中没有）==\n");
    for (const { text, locations } of missing) {
        console.log(`  「${text}」`);
        for (const loc of locations) {
            console.log(`      ${loc}`);
        }
    }
    console.log("");
}

console.log("== 汇总 ==");
console.log(`  扫描文件数:       ${files.length}`);
console.log(`  提取字面量数(去重): ${usages.size}`);
console.log(`  缺失数:           ${missing.length}`);
console.log(`  疑似未使用数:     ${unused.length}`);

if (unused.length > 0) {
    console.log("\n== 疑似未使用（txt 中有、代码中未发现，仅提示，前 20 条样例）==");
    for (const text of unused.slice(0, 20)) {
        console.log(`  ${text}`);
    }
    if (unused.length > 20) {
        console.log(`  ...（共 ${unused.length} 条）`);
    }
}

if (missing.length > 0) {
    console.log(`\n校验失败：存在 ${missing.length} 条缺失文案，请将原文追加到 language/original-web.txt`);
    process.exit(1);
}
console.log("\n校验通过：未发现缺失文案。");
