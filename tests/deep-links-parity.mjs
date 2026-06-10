/**
 * 深链目录一致性校验：
 *   resources/assets/js/components/AIAssistant/deep-links.js 的可执行 id 集合
 *   必须与 resources/ai-kb/_meta/page-links.yaml 的语义 id 集合完全一致。
 *
 * 用法：node tests/deep-links-parity.mjs   （无依赖，纯正则解析）
 * 不一致时打印差异并以非 0 退出，可接入 CI。
 */
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');

// page-links.yaml：links: 下两空格缩进的一级 key
const yaml = readFileSync(resolve(root, 'resources/ai-kb/_meta/page-links.yaml'), 'utf8');
const yamlIds = new Set();
let inLinks = false;
for (const line of yaml.split('\n')) {
    if (/^links:\s*$/.test(line)) { inLinks = true; continue; }
    if (inLinks && /^\S/.test(line)) { inLinks = false; }
    const m = inLinks && line.match(/^ {2}([a-z_]+):\s*$/);
    if (m) yamlIds.add(m[1]);
}

// deep-links.js：LINKS 对象内四空格缩进、值为 { ... } 的 key
const js = readFileSync(resolve(root, 'resources/assets/js/components/AIAssistant/deep-links.js'), 'utf8');
const jsIds = new Set();
for (const m of js.matchAll(/^ {4}([a-z_]+):\s*\{/gm)) {
    jsIds.add(m[1]);
}

const onlyYaml = [...yamlIds].filter(id => !jsIds.has(id));
const onlyJs = [...jsIds].filter(id => !yamlIds.has(id));

if (onlyYaml.length || onlyJs.length) {
    console.error('✗ 深链目录不一致：');
    if (onlyYaml.length) console.error('  仅在 page-links.yaml：', onlyYaml.join(', '));
    if (onlyJs.length) console.error('  仅在 deep-links.js：', onlyJs.join(', '));
    process.exit(1);
}

console.log(`✓ 深链目录一致，共 ${yamlIds.size} 个 id`);
