#!/usr/bin/env node
/**
 * 前端国际化质量门禁与迁移报告。
 *
 * 阻断：字面量登记、Vue 模板硬编码、占位符、翻译数据、前端生成文件。
 * 报告：动态翻译键、(**) 迁移、可参数化文案、疑似未使用文案。
 */

import { existsSync, readFileSync, readdirSync, statSync } from "node:fs";
import { createRequire } from "node:module";
import { dirname, extname, join, relative, sep } from "node:path";
import { fileURLToPath } from "node:url";

const require = createRequire(import.meta.url);
const babelParser = require("@babel/parser");
const babelTraverse = require("@babel/traverse").default;
const vueTemplateCompiler = require("vue-template-compiler");

const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const FRONTEND_DIR = join(ROOT, "resources", "assets", "js");
const ORIGINAL_WEB_FILE = join(ROOT, "language", "original-web.txt");
const TRANSLATE_FILE = join(ROOT, "language", "translate.json");
const PUBLIC_WEB_DIR = join(ROOT, "public", "language", "web");
const LANGUAGE_FIELDS = ["key", "zh", "zh-CHT", "en", "ko", "ja", "de", "fr", "id", "ru"];
const EXCLUDED_TEMPLATE_FILES = new Set([
    "resources/assets/js/pages/manage/components/MCPHelper.vue",
]);
const ACTUAL_SOURCE_ROOTS = [
    "app",
    "bootstrap/app.php",
    "bootstrap/providers.php",
    "config",
    "database",
    "electron",
    "resources/assets/js",
    "resources/assets/sass",
    "resources/mobile/src",
    "resources/mobile/scripts",
    "resources/views",
    "routes",
    "bin",
];
const ACTUAL_SOURCE_EXTENSIONS = new Set([
    ".cjs", ".css", ".htm", ".html", ".js", ".json", ".jsx", ".less", ".mjs",
    ".php", ".sass", ".scss", ".sh", ".ts", ".tsx", ".vue", ".xml", ".yaml", ".yml",
]);
const EXCLUDED_SOURCE_DIRECTORIES = new Set([
    ".git", "node_modules", "vendor", "ai-kb", "coverage", "dist", "build", "platforms", "plugins",
]);
const BABEL_PLUGINS = [
    "classProperties",
    "classPrivateProperties",
    "classPrivateMethods",
    "decorators-legacy",
    "dynamicImport",
    "jsx",
    "nullishCoalescingOperator",
    "objectRestSpread",
    "optionalChaining",
    "topLevelAwait",
];
const PARAMETER_RE = /\(%[TM]\d+\)/g;
const RAW_PARAMETER_RE = /\(\*{1,2}\)/g;
const PARAMETER_TEST_RE = /\(%[TM]\d+\)/;
const RAW_PARAMETER_TEST_RE = /\(\*{1,2}\)/;
const NAMED_PARAMETER_RE = /\{[A-Za-z_][A-Za-z0-9_.-]*\}/g;
const CHINESE_RE = /[\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff]/;
const CONTEXT_KEY_RE = /^\[([a-z][a-z0-9_]*)]\.([\s\S]+)$/;
const CONTEXT_KEY_PREFIX_RE = /^\[[^\]]+]\./;

function normalizePath(file) {
    return file.split(sep).join("/");
}

function lineAt(content, offset) {
    return content.slice(0, offset).split("\n").length;
}

function shortenText(text, maxLength = 120) {
    const compact = text.replace(/\s+/g, " ").trim();
    return compact.length <= maxLength ? compact : `${compact.slice(0, maxLength - 1)}…`;
}

function collectFiles(path, predicate, out = []) {
    if (!existsSync(path)) return out;
    const stat = statSync(path);
    if (stat.isFile()) {
        if (predicate(path)) out.push(path);
        return out;
    }
    for (const entry of readdirSync(path, { withFileTypes: true })) {
        if (entry.isDirectory() && EXCLUDED_SOURCE_DIRECTORIES.has(entry.name)) continue;
        const full = join(path, entry.name);
        if (entry.isDirectory()) {
            collectFiles(full, predicate, out);
        } else if (entry.isFile() && predicate(full)) {
            out.push(full);
        }
    }
    return out;
}

function readOriginalLines(file) {
    return readFileSync(file, "utf8")
        .split(/\r?\n/)
        .map((text, index) => ({ text: text.trim(), line: index + 1 }))
        .filter(item => item.text !== "");
}

function normalizeParameterKey(key) {
    return key.replace(/\(%T\d+\)/g, "(*)").replace(/\(%M\d+\)/g, "(**)");
}

function contextSourceKey(key) {
    return key.match(CONTEXT_KEY_RE)?.[2] || null;
}

function placeholders(value) {
    return [...value.matchAll(PARAMETER_RE)].map(match => match[0]);
}

function samePlaceholderSet(expected, actual) {
    return [...expected].sort().join("\u0000") === [...actual].sort().join("\u0000");
}

function addMapLocation(map, key, location) {
    if (!map.has(key)) map.set(key, []);
    const locations = map.get(key);
    if (!locations.includes(location)) locations.push(location);
}
function uniqueNamedParameters(items) {
    const seen = new Set();
    return items.filter(item => {
        const key = `${item.location}\u0000${item.named}\u0000${item.text}`;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}

function isTranslationCall(node) {
    const callee = node.callee;
    if (callee?.type === "Identifier" && callee.name === "$L") return true;
    if (callee?.type !== "MemberExpression" && callee?.type !== "OptionalMemberExpression") return false;
    const propertyName = callee.computed ? callee.property?.value : callee.property?.name;
    if (propertyName === "$L") return true;
    return propertyName === "L" && callee.object?.type === "Identifier" && callee.object.name === "$A";
}

function staticStringValue(node) {
    if (node?.type === "StringLiteral") return node.value.trim();
    if (node?.type === "TemplateLiteral" && node.expressions.length === 0) {
        return (node.quasis[0]?.value?.cooked ?? node.quasis[0]?.value?.raw ?? "").trim();
    }
    return null;
}

function translationLiteralValue(node) {
    return node?.type === "StringLiteral" ? node.value.trim() : null;
}

function containsStaticString(node) {
    if (!node) return false;
    if (node.type === "StringLiteral") return true;
    if (node.type === "TemplateLiteral") return node.quasis.some(part => (part.value.cooked ?? part.value.raw ?? "") !== "");
    if (node.type === "BinaryExpression" && node.operator === "+") {
        return containsStaticString(node.left) || containsStaticString(node.right);
    }
    return false;
}

function classifyDynamicArgument(node) {
    if (node?.type === "TemplateLiteral") return "模板字符串";
    if (node?.type === "BinaryExpression" && node.operator === "+" && containsStaticString(node)) return "字符串拼接";
    return null;
}

function appendStringShapes(left, right) {
    const values = [];
    for (const leftValue of left) {
        for (const rightValue of right) {
            values.push(leftValue + rightValue);
            if (values.length >= 32) return values;
        }
    }
    return values;
}

function normalizeShapePunctuation(value) {
    return value
        .replace(/"\(\*\)"/g, "(*)")
        .replace(/"\(\*\)/g, "(*)")
        .replace(/\(\*\)"/g, "(*)");
}

function normalizeParameterizedShape(value) {
    return normalizeShapePunctuation(compactParameterized(value));
}

function parameterizedFixedPartInfo(value) {
    const normalized = normalizeParameterizedShape(value);
    if (!normalized.includes("(*)") && !normalized.includes("(**)")) return false;
    const parts = normalized.split(/\(\*{1,2}\)/);
    if (parts.length < 2) return false;
    const fixedParts = parts.map(part => part.trim()).filter(Boolean);
    const meaningfulLength = [...fixedParts.join("").matchAll(/[A-Za-z0-9\u3400-\u4dbf\u4e00-\u9fff\uf900-\ufaff]/g)].length;
    return { parts, fixedParts, meaningfulLength };
}

function isCredibleParameterizedShape(shape) {
    const info = parameterizedFixedPartInfo(shape);
    if (!info || info.fixedParts.length === 0) return false;
    if (info.fixedParts.length >= 2) {
        return info.meaningfulLength >= 4 || (info.parts.length > 2 && info.fixedParts.length >= 3 && info.meaningfulLength >= 3);
    }
    return info.meaningfulLength >= 4;
}

function recordParameterizedShape(result, shape, location) {
    if (!isCredibleParameterizedShape(shape)) return;
    result.parameterizedShapes.push({ shape, location });
}

function expressionStringShapes(node, scope, seen = new Set(), allowUnknown = false) {
    if (!node) return allowUnknown ? ["(*)"] : [];
    if (node.type === "StringLiteral") return [node.value.trim()];
    if (node.type === "NumericLiteral" || node.type === "BooleanLiteral") return [String(node.value)];
    if (node.type === "TemplateLiteral") {
        let values = [""];
        for (let index = 0; index < node.quasis.length; index++) {
            values = values.map(value => value + (node.quasis[index].value.cooked ?? node.quasis[index].value.raw ?? ""));
            if (index >= node.expressions.length) continue;
            const expressionValues = expressionStringShapes(node.expressions[index], scope, seen, true);
            values = appendStringShapes(values, expressionValues);
        }
        return values.map(value => value.trim());
    }
    if (node.type === "BinaryExpression" && node.operator === "+") {
        return appendStringShapes(
            expressionStringShapes(node.left, scope, seen, true),
            expressionStringShapes(node.right, scope, seen, true),
        );
    }
    if (node.type === "ConditionalExpression") {
        return [
            ...expressionStringShapes(node.consequent, scope, seen, allowUnknown),
            ...expressionStringShapes(node.alternate, scope, seen, allowUnknown),
        ].slice(0, 32);
    }
    if (node.type === "LogicalExpression") {
        return [
            ...expressionStringShapes(node.left, scope, seen, allowUnknown),
            ...expressionStringShapes(node.right, scope, seen, allowUnknown),
        ].slice(0, 32);
    }
    if (node.type === "Identifier") {
        const binding = scope?.getBinding(node.name);
        const bindingNode = binding?.path?.node;
        const init = bindingNode?.type === "VariableDeclarator" ? bindingNode.init : null;
        if (init && !seen.has(bindingNode)) {
            const nextSeen = new Set(seen);
            nextSeen.add(bindingNode);
            const values = expressionStringShapes(init, binding.path.scope, nextSeen, allowUnknown);
            for (const violation of binding.constantViolations || []) {
                const right = violation.node?.type === "AssignmentExpression" ? violation.node.right : null;
                if (right) values.push(...expressionStringShapes(right, violation.scope, nextSeen, allowUnknown));
            }
            const unique = [...new Set(values)];
            if (unique.length) return unique.slice(0, 32);
            return allowUnknown ? ["(*)"] : [];
        }
    }
    return allowUnknown ? ["(*)"] : [];
}

function templateShape(node) {
    if (node?.type !== "TemplateLiteral" || node.expressions.length === 0) return null;
    let result = "";
    for (let index = 0; index < node.quasis.length; index++) {
        result += node.quasis[index].value.cooked ?? node.quasis[index].value.raw ?? "";
        if (index < node.expressions.length) result += "(*)";
    }
    return result.trim();
}

function parseJavaScript(content, rel, baseLine, result) {
    let ast;
    try {
        ast = babelParser.parse(content, {
            sourceType: "unambiguous",
            allowReturnOutsideFunction: true,
            plugins: BABEL_PLUGINS,
        });
    } catch (error) {
        result.parserWarnings.push(`${rel}:${baseLine} JavaScript 解析失败，已跳过 AST 报告：${error.message}`);
        return;
    }

    const locationOf = node => `${rel}:${baseLine + (node.loc?.start?.line || 1) - 1}`;
    babelTraverse(ast, {
        CallExpression(path) {
            const node = path.node;
            if (!isTranslationCall(node) || node.arguments.length === 0) return;
            const argument = node.arguments[0];
            const location = locationOf(node);
            const literal = translationLiteralValue(argument);
            if (literal) {
                addMapLocation(result.literalUsages, literal, location);
                for (const named of literal.match(NAMED_PARAMETER_RE) || []) {
                    result.namedParameters.push({ text: literal, named, location });
                }
            }
            const staticValue = staticStringValue(argument);
            if (staticValue) {
                for (const named of staticValue.match(NAMED_PARAMETER_RE) || []) {
                    result.namedParameters.push({ text: staticValue, named, location });
                }
            }
            if (literal) {
                if (literal.includes("(*)") || literal.includes("(**)")) {
                    let rendered = [literal];
                    for (const argumentValue of node.arguments.slice(1)) {
                        const values = expressionStringShapes(argumentValue, path.scope);
                        rendered = rendered.flatMap(value => values.map(item => {
                            const marker = value.includes("(**)") ? "(**)" : "(*)";
                            return value.replace(marker, item);
                        })).slice(0, 32);
                    }
                    for (const shapeValue of rendered) {
                        recordParameterizedShape(result, shapeValue, location);
                    }
                }
                return;
            }
            const category = classifyDynamicArgument(argument);
            if (category) {
                result.dynamicCalls.push({ category, location, expression: shortenText(content.slice(argument.start, argument.end)) });
            }
        },
        BinaryExpression(path) {
            if (path.node.operator !== "+" || !containsStaticString(path.node)) return;
            for (const shape of expressionStringShapes(path.node, path.scope)) {
                recordParameterizedShape(result, shape, locationOf(path.node));
            }
        },
        StringLiteral(path) {
            const value = path.node.value.trim();
            if (value) addMapLocation(result.sourceStrings, value, locationOf(path.node));
        },
        TemplateLiteral(path) {
            const literal = staticStringValue(path.node);
            if (literal) addMapLocation(result.sourceStrings, literal, locationOf(path.node));
            const shape = templateShape(path.node);
            if (shape) result.templateShapes.push({ shape, location: locationOf(path.node) });
            if (path.node.expressions.length) {
                for (const value of expressionStringShapes(path.node, path.scope)) {
                    recordParameterizedShape(result, value, locationOf(path.node));
                }
            }
        },
    });
}

function skipQuoted(content, start, quote) {
    let index = start + 1;
    while (index < content.length) {
        if (content[index] === "\\") {
            index += 2;
        } else if (content[index] === quote) {
            return index + 1;
        } else {
            index++;
        }
    }
    return content.length;
}

function firstArgumentSource(content, openParen) {
    let index = openParen + 1;
    while (/\s/.test(content[index] || "")) index++;
    const start = index;
    const stack = [];
    while (index < content.length) {
        const char = content[index];
        if (char === "'" || char === '"' || char === "`") {
            index = skipQuoted(content, index, char);
            continue;
        }
        if (char === "/" && content[index + 1] === "/") {
            const newline = content.indexOf("\n", index + 2);
            index = newline === -1 ? content.length : newline + 1;
            continue;
        }
        if (char === "/" && content[index + 1] === "*") {
            const close = content.indexOf("*/", index + 2);
            index = close === -1 ? content.length : close + 2;
            continue;
        }
        if (char === "(" || char === "[" || char === "{") {
            stack.push(char);
        } else if (char === ")") {
            if (stack.length === 0) return content.slice(start, index).trim();
            stack.pop();
        } else if (char === "]" || char === "}") {
            stack.pop();
        } else if (char === "," && stack.length === 0) {
            return content.slice(start, index).trim();
        }
        index++;
    }
    return null;
}

function scanVueTemplateCalls(blockContent, rel, baseLine, result) {
    const content = blockContent.replace(/<!--[\s\S]*?-->/g, match => match.replace(/[^\n]/g, " "));
    const callRe = /(?<![\w$])\$L\s*\(|\$A\.L\s*\(/g;
    let match;
    while ((match = callRe.exec(content)) !== null) {
        const openParen = content.indexOf("(", match.index);
        const source = firstArgumentSource(content, openParen);
        if (!source) continue;
        let argument;
        try {
            argument = babelParser.parseExpression(source, { plugins: BABEL_PLUGINS });
        } catch {
            continue;
        }
        const location = `${rel}:${baseLine + lineAt(content, match.index) - 1}`;
        const literal = translationLiteralValue(argument);
        if (literal) {
            addMapLocation(result.literalUsages, literal, location);
            addMapLocation(result.sourceStrings, literal, location);
            for (const named of literal.match(NAMED_PARAMETER_RE) || []) {
                result.namedParameters.push({ text: literal, named, location });
            }
        }
        const staticValue = staticStringValue(argument);
        if (staticValue) {
            addMapLocation(result.sourceStrings, staticValue, location);
            for (const named of staticValue.match(NAMED_PARAMETER_RE) || []) {
                result.namedParameters.push({ text: staticValue, named, location });
            }
        }
        if (literal) {
            continue;
        }
        const category = classifyDynamicArgument(argument);
        if (category) result.dynamicCalls.push({ category, location, expression: shortenText(source) });
        const shape = templateShape(argument);
        if (shape) result.templateShapes.push({ shape, location });
        if (argument.type === "TemplateLiteral" || argument.type === "BinaryExpression" || argument.type === "ConditionalExpression") {
            for (const value of expressionStringShapes(argument, null)) {
                recordParameterizedShape(result, value, location);
            }
        }
    }
}

function scanPhpStrings(content, rel, result) {
    let index = 0;
    const readQuoted = (start, quote) => {
        let cursor = start + 1;
        let value = "";
        while (cursor < content.length) {
            if (content[cursor] === "\\") {
                if (cursor + 1 < content.length) {
                    value += content[cursor + 1];
                    cursor += 2;
                } else {
                    cursor++;
                }
                continue;
            }
            if (content[cursor] === quote) return { end: cursor + 1, value };
            value += content[cursor++];
        }
        return { end: content.length, value };
    };
    const skipSpaceAndComments = start => {
        let cursor = start;
        while (cursor < content.length) {
            if (/\s/.test(content[cursor])) {
                cursor++;
                continue;
            }
            if (content.startsWith("//", cursor) || content[cursor] === "#") {
                const newline = content.indexOf("\n", cursor + 1);
                return newline === -1 ? content.length : newline + 1;
            }
            if (content.startsWith("/*", cursor)) {
                const close = content.indexOf("*/", cursor + 2);
                cursor = close === -1 ? content.length : close + 2;
                continue;
            }
            break;
        }
        return cursor;
    };
    const skipOperand = start => {
        let cursor = start;
        const stack = [];
        while (cursor < content.length) {
            if (content[cursor] === "'" || content[cursor] === '"') {
                cursor = readQuoted(cursor, content[cursor]).end;
                continue;
            }
            if (content.startsWith("//", cursor) || content[cursor] === "#") {
                const newline = content.indexOf("\n", cursor + 1);
                cursor = newline === -1 ? content.length : newline + 1;
                continue;
            }
            if (content.startsWith("/*", cursor)) {
                const close = content.indexOf("*/", cursor + 2);
                cursor = close === -1 ? content.length : close + 2;
                continue;
            }
            if (content[cursor] === "(" || content[cursor] === "[" || content[cursor] === "{") {
                stack.push(content[cursor]);
            } else if (content[cursor] === ")" || content[cursor] === "]" || content[cursor] === "}") {
                if (stack.length === 0) return cursor;
                stack.pop();
            } else if (stack.length === 0 && (content[cursor] === "." || content[cursor] === ";" || content[cursor] === ",")) {
                return cursor;
            }
            cursor++;
        }
        return cursor;
    };
    while (index < content.length) {
        if (content.startsWith("//", index) || content[index] === "#") {
            const newline = content.indexOf("\n", index + 1);
            index = newline === -1 ? content.length : newline + 1;
            continue;
        }
        if (content.startsWith("/*", index)) {
            const close = content.indexOf("*/", index + 2);
            index = close === -1 ? content.length : close + 2;
            continue;
        }
        if (content[index] !== "'" && content[index] !== '"') {
            index++;
            continue;
        }
        const start = index;
        const quote = content[index];
        const token = readQuoted(index, quote);
        index = token.end;
        const location = `${rel}:${lineAt(content, start)}`;
        const interpolation = quote === '"' && /(?:\{\$[^}]+\}|\$\{[^}]+\}|\$[A-Za-z_][A-Za-z0-9_]*)/.test(token.value);
        if (!interpolation) {
            const value = token.value.trim();
            if (value) addMapLocation(result.sourceStrings, value, location);
        }
        let shape = interpolation
            ? token.value.replace(/\$[A-Za-z_][A-Za-z0-9_]*|\$\{[^}]+\}|\{\$[^}]+\}/g, "(*)").trim()
            : token.value.trim();
        let cursor = skipSpaceAndComments(index);
        let hasConcat = false;
        while (content[cursor] === ".") {
            hasConcat = true;
            cursor = skipSpaceAndComments(cursor + 1);
            if (content[cursor] === "'" || content[cursor] === '"') {
                const next = readQuoted(cursor, content[cursor]);
                const nextInterpolation = content[cursor] === '"' && /(?:\{\$[^}]+\}|\$\{[^}]+\}|\$[A-Za-z_][A-Za-z0-9_]*)/.test(next.value);
                shape += nextInterpolation
                    ? next.value.replace(/\$[A-Za-z_][A-Za-z0-9_]*|\$\{[^}]+\}|\{\$[^}]+\}/g, "(*)").trim()
                    : next.value.trim();
                cursor = skipSpaceAndComments(next.end);
            } else {
                shape += "(*)";
                cursor = skipSpaceAndComments(skipOperand(cursor));
            }
        }
        if (shape.includes("(*)") && (interpolation || hasConcat)) {
            recordParameterizedShape(result, shape, location);
        }
    }
}

function extractTemplateTexts(content) {
    let block;
    try {
        block = vueTemplateCompiler.parseComponent(content, { deindent: false }).template;
    } catch {
        return [];
    }
    if (!block) return [];

    let ast;
    try {
        ast = vueTemplateCompiler.compile(block.content, { outputSourceRange: true }).ast;
    } catch {
        return [];
    }
    if (!ast) return [];

    const results = [];
    const visibleTextInfo = (raw) => {
        let text = "";
        let firstChineseIndex = -1;
        let index = 0;
        while (index < raw.length) {
            if (raw.startsWith("{{", index)) {
                const close = raw.indexOf("}}", index + 2);
                if (close === -1) break;
                index = close + 2;
                continue;
            }
            const char = raw[index];
            text += char;
            if (firstChineseIndex === -1 && CHINESE_RE.test(char)) firstChineseIndex = index;
            index++;
        }
        text = text.replace(/\s+/g, " ").trim();
        return text && firstChineseIndex !== -1 ? { text, firstChineseIndex } : null;
    };
    const visited = new Set();
    const visit = (node) => {
        if (!node || visited.has(node)) return;
        visited.add(node);
        if (node.type === 2 || node.type === 3) {
            const info = visibleTextInfo(node.text || "");
            if (info) {
                const nodeStart = typeof node.start === "number" ? node.start : 0;
                results.push({ text: shortenText(info.text), offset: (block.start || 0) + nodeStart + info.firstChineseIndex });
            }
        }
        for (const child of node.children || []) visit(child);
        for (const condition of node.ifConditions || []) visit(condition.block);
        for (const slot of Object.values(node.scopedSlots || {})) visit(slot);
    };
    visit(ast);
    return results;
}

function scanFrontendSources() {
    const files = collectFiles(FRONTEND_DIR, file => /\.(js|vue)$/.test(file));
    const result = {
        files,
        literalUsages: new Map(),
        sourceStrings: new Map(),
        namedParameters: [],
        dynamicCalls: [],
        templateShapes: [],
        parameterizedShapes: [],
        templateTexts: [],
        parserWarnings: [],
    };

    for (const file of files) {
        const rel = normalizePath(relative(ROOT, file));
        const content = readFileSync(file, "utf8");
        if (file.endsWith(".js")) {
            parseJavaScript(content, rel, 1, result);
            continue;
        }

        let descriptor;
        try {
            descriptor = vueTemplateCompiler.parseComponent(content, { deindent: false });
        } catch (error) {
            result.parserWarnings.push(`${rel}:1 Vue SFC 解析失败：${error.message}`);
            continue;
        }
        if (descriptor.script) {
            const scriptOffset = content.indexOf(descriptor.script.content);
            parseJavaScript(descriptor.script.content, rel, lineAt(content, Math.max(scriptOffset, 0)), result);
        }
        if (descriptor.template) {
            const templateOffset = content.indexOf(descriptor.template.content);
            scanVueTemplateCalls(
                descriptor.template.content,
                rel,
                lineAt(content, Math.max(templateOffset, 0)),
                result,
            );
        }
        if (!EXCLUDED_TEMPLATE_FILES.has(rel)) {
            for (const item of extractTemplateTexts(content)) {
                result.templateTexts.push({ rel, line: lineAt(content, item.offset), text: item.text });
            }
        }
    }
    const backendFiles = ACTUAL_SOURCE_ROOTS.flatMap(root => collectFiles(
        join(ROOT, root), file => extname(file).toLowerCase() === ".php",
    ));
    for (const file of backendFiles) {
        const rel = normalizePath(relative(ROOT, file));
        scanPhpStrings(readFileSync(file, "utf8"), rel, result);
    }
    return result;
}

function stripSourceComments(content, file) {
    const extension = extname(file).toLowerCase();
    const supportsHashComments = extension === ".php" || extension === ".sh" || extension === ".yaml" || extension === ".yml";
    let result = "";
    let index = 0;
    let quote = null;
    const blank = text => text.replace(/[^\r\n]/g, " ");

    while (index < content.length) {
        const char = content[index];
        if (quote) {
            result += char;
            if (char === "\\") {
                result += content[index + 1] || "";
                index += 2;
                continue;
            }
            if (char === quote) quote = null;
            index++;
            continue;
        }
        if (char === "'" || char === "\"" || char === "`") {
            quote = char;
            result += char;
            index++;
            continue;
        }
        if (content.startsWith("<!--", index)) {
            const close = content.indexOf("-->", index + 4);
            const end = close === -1 ? content.length : close + 3;
            result += blank(content.slice(index, end));
            index = end;
            continue;
        }
        if (content.startsWith("//", index)) {
            const newline = content.slice(index + 2).search(/\r?\n/);
            const end = newline === -1 ? content.length : index + 2 + newline;
            result += blank(content.slice(index, end));
            index = end;
            continue;
        }
        if (content.startsWith("/*", index)) {
            const close = content.indexOf("*/", index + 2);
            const end = close === -1 ? content.length : close + 2;
            result += blank(content.slice(index, end));
            index = end;
            continue;
        }
        if (supportsHashComments && char === "#") {
            const newline = content.slice(index + 1).search(/\r?\n/);
            const end = newline === -1 ? content.length : index + 1 + newline;
            result += blank(content.slice(index, end));
            index = end;
            continue;
        }
        result += char;
        index++;
    }
    return result;
}

function buildPatternMatcher(patterns, resolveIndex = index => index) {
    const nodes = [{ next: new Map(), fail: 0, outputs: [] }];
    patterns.forEach((pattern, patternIndex) => {
        let nodeIndex = 0;
        for (const char of pattern) {
            if (!nodes[nodeIndex].next.has(char)) {
                nodes[nodeIndex].next.set(char, nodes.length);
                nodes.push({ next: new Map(), fail: 0, outputs: [] });
            }
            nodeIndex = nodes[nodeIndex].next.get(char);
        }
        nodes[nodeIndex].outputs.push(resolveIndex(patternIndex));
    });

    const queue = [];
    for (const child of nodes[0].next.values()) queue.push(child);
    for (let cursor = 0; cursor < queue.length; cursor++) {
        const current = queue[cursor];
        for (const [char, child] of nodes[current].next) {
            queue.push(child);
            let fallback = nodes[current].fail;
            while (fallback !== 0 && !nodes[fallback].next.has(char)) fallback = nodes[fallback].fail;
            if (nodes[fallback].next.has(char)) fallback = nodes[fallback].next.get(char);
            nodes[child].fail = fallback;
            nodes[child].outputs.push(...nodes[fallback].outputs);
        }
    }
    return (content, found) => {
        let nodeIndex = 0;
        for (const char of content) {
            while (nodeIndex !== 0 && !nodes[nodeIndex].next.has(char)) nodeIndex = nodes[nodeIndex].fail;
            if (nodes[nodeIndex].next.has(char)) nodeIndex = nodes[nodeIndex].next.get(char);
            for (const patternIndex of nodes[nodeIndex].outputs) found.add(patternIndex);
        }
    };
}

function parameterizedShapeMatches(key, shape) {
    if (!isCredibleParameterizedShape(shape)) return false;
    const normalizedKey = normalizeParameterizedShape(key);
    const normalizedShape = normalizeParameterizedShape(shape);
    const keyParts = normalizedKey.split(/\(\*{1,2}\)/);
    const shapeParts = normalizedShape.split(/\(\*{1,2}\)/);
    if (keyParts.length === shapeParts.length && keyParts.every((part, index) => part === shapeParts[index])) return true;
    const boundedCaptures = match => match && match.slice(1).every(item => item.length > 0 && item.length <= 200);
    if (keyParts.length === shapeParts.length) {
        const match = normalizedShape.match(parameterizedRegExp(key));
        if (boundedCaptures(match)) return true;
    }
    if (!/[0-9]/.test(key) || shapeParts.filter(part => part.trim()).length < 2) return false;
    const numericMatch = normalizedKey.match(parameterizedRegExp(shape));
    return boundedCaptures(numericMatch) && numericMatch.slice(1).some(item => /[0-9]/.test(item));
}

function findUnusedKeys(keys, decodedSourceStrings, parameterizedShapes = []) {
    const sourceFiles = ACTUAL_SOURCE_ROOTS.flatMap(root => collectFiles(
        join(ROOT, root),
        file => ACTUAL_SOURCE_EXTENSIONS.has(extname(file).toLowerCase()),
    ));
    const found = new Set();
    const keyIndexes = new Map(keys.map((key, index) => [key, index]));
    const parameterizedKeys = keys.filter(key => key.includes("(*)") || key.includes("(**)"));
    const parameterizedKeySet = new Set(parameterizedKeys);
    const plainKeys = keys.filter(key => !parameterizedKeySet.has(key));
    const plainMatcher = buildPatternMatcher(plainKeys, index => keyIndexes.get(plainKeys[index]));
    const parameterizedMatcher = buildPatternMatcher(parameterizedKeys, index => keyIndexes.get(parameterizedKeys[index]));
    for (const file of sourceFiles) {
        const content = readFileSync(file, "utf8");
        plainMatcher(content, found);
        parameterizedMatcher(stripSourceComments(content, file), found);
    }
    const parameterizedUsages = new Map();
    const recordParameterizedUsage = (key, locations) => {
        if (!parameterizedUsages.has(key)) parameterizedUsages.set(key, []);
        for (const location of locations || []) {
            const entries = parameterizedUsages.get(key);
            if (!entries.includes(location)) entries.push(location);
        }
    };
    for (const value of decodedSourceStrings.keys()) {
        const index = keyIndexes.get(value);
        if (index !== undefined) {
            found.add(index);
            if (parameterizedKeySet.has(value)) recordParameterizedUsage(value, decodedSourceStrings.get(value));
        }
    }
    for (const key of parameterizedKeys) {
        const keyIndex = keyIndexes.get(key);
        if (found.has(keyIndex)) continue;
        if (isCredibleParameterizedShape(key)) {
            const regex = parameterizedRegExp(key);
            for (const [value, locations] of decodedSourceStrings) {
                if (RAW_PARAMETER_TEST_RE.test(value)) continue;
                const match = compactParameterized(value).match(regex);
                if (match && match.slice(1).every(item => item.length > 0 && item.length <= 200)) {
                    found.add(keyIndex);
                    recordParameterizedUsage(key, locations);
                    break;
                }
            }
            if (found.has(keyIndex)) continue;
        }
        for (const item of parameterizedShapes) {
            if (!parameterizedShapeMatches(key, item.shape)) continue;
            found.add(keyIndex);
            recordParameterizedUsage(key, [item.location]);
        }
    }
    return {
        sourceFileCount: new Set(sourceFiles).size,
        unused: keys.filter((key, index) => !found.has(index)),
        parameterizedUsages,
    };
}

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

function compactParameterized(value) {
    return value.replace(/\s+/g, "");
}

function parameterizedRegExp(key) {
    const parts = compactParameterized(key).split(/\(\*{1,2}\)/);
    return new RegExp(`^${parts.map(escapeRegExp).join("(.+?)")}$`);
}

function findDoubleStarTodos(originalKeys, templateShapes) {
    return originalKeys.filter(key => key.includes("(**)")).map(key => {
        const regex = parameterizedRegExp(key);
        const sources = templateShapes
            .filter(item => regex.test(compactParameterized(item.shape)))
            .map(item => item.location);
        return { key, sources: [...new Set(sources)] };
    });
}

function findSpecificCandidates(originalKeys, sourceStrings) {
    const genericKeys = originalKeys.filter(key => key.includes("(*)") && !key.includes("(**)"));
    const candidates = [];
    for (const concrete of originalKeys) {
        if (concrete.includes("(*)") || concrete.includes("(**)")) continue;
        const sources = sourceStrings.get(concrete);
        if (!sources?.length) continue;
        const matches = genericKeys.filter(generic => {
            const fixedLength = [...generic.replaceAll("(*)", "")].length;
            if (fixedLength < 5) return false;
            const match = compactParameterized(concrete).match(parameterizedRegExp(generic));
            return match && match.slice(1).every(value => value.length <= 20);
        });
        if (matches.length === 1) candidates.push({ concrete, generic: matches[0], sources });
    }
    return candidates;
}

function parseGeneratedWebFile(field) {
    const rel = `public/language/web/${field}.js`;
    const file = join(PUBLIC_WEB_DIR, `${field}.js`);
    if (!existsSync(file)) return { rel, error: "文件不存在" };
    const content = readFileSync(file, "utf8");
    const match = content.match(/window\.LANGUAGE_DATA\["([^"]+)"\]=(\[[\s\S]*\])\s*;?\s*$/);
    if (!match || match[1] !== field) return { rel, error: "文件格式无法解析或字段名不匹配" };
    try {
        const data = JSON.parse(match[2]);
        return Array.isArray(data) ? { rel, data } : { rel, error: "数据不是数组" };
    } catch (error) {
        return { rel, error: `JSON 解析失败：${error.message}` };
    }
}

function validateGeneratedFiles(originalKeys, translationsByNormalizedKey, translationRows, options = {}) {
    const errors = [];
    const parsed = new Map();
    for (const field of LANGUAGE_FIELDS) {
        const result = parseGeneratedWebFile(field);
        if (result.error) {
            errors.push(`${result.rel}：${result.error}`);
        } else {
            parsed.set(field, result);
        }
    }
    if (parsed.size !== LANGUAGE_FIELDS.length) return { errors, rawPublicKeys: [] };

    const expectedRows = originalKeys
        .map(key => translationsByNormalizedKey.get(key))
        .filter(Boolean)
        .sort((left, right) => {
            const weight = item => Buffer.byteLength(item.key, "utf8") + (PARAMETER_TEST_RE.test(item.key) ? 0 : 10_000_000_000);
            const difference = weight(right) - weight(left);
            return difference || Buffer.compare(Buffer.from(left.key), Buffer.from(right.key));
        });
    const expectedLength = expectedRows.length;
    const lengthGroups = new Map();
    for (const [field, result] of parsed) {
        if (result.data.length !== expectedLength) {
            const key = result.data.length;
            if (!lengthGroups.has(key)) lengthGroups.set(key, []);
            lengthGroups.get(key).push(field);
        }
    }
    for (const [actualLength, fields] of lengthGroups) {
        const scope = fields.length === LANGUAGE_FIELDS.length
            ? `public/language/web：${fields.length} 个文件`
            : fields.map(field => `public/language/web/${field}.js`).join("、");
        errors.push(`${scope} 数组长度 ${actualLength}，预期 ${expectedLength}`);
    }

    const publicKeys = parsed.get("key").data;
    const duplicateKeys = new Map();
    publicKeys.forEach((key, index) => {
        if (!duplicateKeys.has(key)) duplicateKeys.set(key, []);
        duplicateKeys.get(key).push(index);
    });
    for (const [key, indexes] of duplicateKeys) {
        if (indexes.length > 1) errors.push(`public/language/web/key.js：重复 key「${key}」，索引 ${indexes.join(", ")}`);
    }

    if (options.skipIndexComparison) {
        errors.push("public/language/web：上游 original-web.txt 或 translate.json 已存在缺失/重复，已跳过逐索引内容比较以避免级联噪声");
        return {
            errors,
            rawPublicKeys: publicKeys
                .map((key, index) => ({ key, index }))
                .filter(item => RAW_PARAMETER_TEST_RE.test(item.key)),
        };
    }

    const comparisonLength = Math.min(publicKeys.length, expectedRows.length);
    for (let index = 0; index < comparisonLength; index++) {
        const expected = expectedRows[index];
        if (publicKeys[index] !== expected.key) {
            errors.push(`public/language/web/key.js[${index}]：key 为「${publicKeys[index]}」，预期「${expected.key}」`);
            continue;
        }
        for (const field of LANGUAGE_FIELDS) {
            const actual = parsed.get(field).data[index];
            if (actual !== expected[field]) {
                errors.push(`public/language/web/${field}.js[${index}]：内容与 translate.json 的「${expected.key}」不一致`);
            }
        }
    }

    const expectedKeySet = new Set(expectedRows.map(item => item.key));
    for (const [index, key] of publicKeys.entries()) {
        if (!translationRows.some(item => item.key === key)) {
            errors.push(`public/language/web/key.js[${index}]：key「${key}」不在 translate.json`);
        } else if (!expectedKeySet.has(key)) {
            errors.push(`public/language/web/key.js[${index}]：key「${key}」不属于前端 original-web.txt`);
        }
    }
    return {
        errors,
        rawPublicKeys: publicKeys
            .map((key, index) => ({ key, index }))
            .filter(item => RAW_PARAMETER_TEST_RE.test(item.key)),
    };
}

const originalLineItems = readOriginalLines(ORIGINAL_WEB_FILE);
const originalKeys = originalLineItems.map(item => item.text);
const originalLocations = new Map();
for (const item of originalLineItems) {
    if (!originalLocations.has(item.text)) originalLocations.set(item.text, []);
    originalLocations.get(item.text).push(item.line);
}
const originalDuplicates = [...originalLocations]
    .filter(([, lines]) => lines.length > 1)
    .map(([key, lines]) => ({ key, lines }));
const originalKeySet = new Set(originalKeys);
const originalContextErrors = originalLineItems.flatMap(item => {
    if (!CONTEXT_KEY_PREFIX_RE.test(item.text)) return [];
    const source = contextSourceKey(item.text);
    if (!source) return [`language/original-web.txt:${item.line}：上下文翻译键格式错误「${item.text}」`];
    return source.trim() === "" ? [`language/original-web.txt:${item.line}：上下文翻译键原文不能为空`] : [];
});

let translationRows = [];
const translationFormatErrors = [];
try {
    const parsed = JSON.parse(readFileSync(TRANSLATE_FILE, "utf8"));
    if (!Array.isArray(parsed)) {
        translationFormatErrors.push("language/translate.json：根数据必须是数组");
    } else {
        translationRows = parsed;
    }
} catch (error) {
    translationFormatErrors.push(`language/translate.json：JSON 解析失败：${error.message}`);
}

const rawTranslateKeys = [];
const translationParameterErrors = [];
const normalizedTranslations = new Map();
for (const [index, row] of translationRows.entries()) {
    const location = `language/translate.json[index ${index}]`;
    if (!row || typeof row !== "object" || Array.isArray(row)) {
        translationFormatErrors.push(`${location}：条目必须是对象`);
        continue;
    }
    for (const field of LANGUAGE_FIELDS) {
        if (typeof row[field] !== "string") translationFormatErrors.push(`${location}.${field}：必须是字符串`);
    }
    if (typeof row.key !== "string") continue;
    if (CONTEXT_KEY_PREFIX_RE.test(row.key)) {
        const source = contextSourceKey(row.key);
        if (!source) {
            translationFormatErrors.push(`${location}.key：上下文翻译键格式错误「${row.key}」`);
        } else if (row.zh !== source) {
            translationFormatErrors.push(`${location}.zh：上下文翻译键必须填写原文「${source}」`);
        }
    }
    if (RAW_PARAMETER_TEST_RE.test(row.key)) rawTranslateKeys.push({ key: row.key, location });
    const keyParameters = placeholders(row.key);
    if (row.key.replace(PARAMETER_RE, "").includes("(%")) {
        translationParameterErrors.push(`${location}.key：存在非法参数占位符「${row.key}」`);
    }
    PARAMETER_RE.lastIndex = 0;
    const numbers = keyParameters.map(parameter => Number(parameter.match(/\d+/)[0]));
    if (numbers.some((number, parameterIndex) => number !== parameterIndex + 1)) {
        translationParameterErrors.push(`${location}.key：参数编号必须按出现顺序从 1 连续递增「${row.key}」`);
    }
    for (const field of LANGUAGE_FIELDS.slice(1)) {
        if (typeof row[field] !== "string" || row[field] === "") continue;
        const fieldParameters = placeholders(row[field]);
        if (!samePlaceholderSet(keyParameters, fieldParameters)) {
            translationParameterErrors.push(
                `${location}.${field}：参数占位符与 key 不一致；key=[${keyParameters.join(", ")}]，value=[${fieldParameters.join(", ")}]`,
            );
        }
    }
    const normalized = normalizeParameterKey(row.key);
    if (!normalizedTranslations.has(normalized)) normalizedTranslations.set(normalized, []);
    normalizedTranslations.get(normalized).push({ index, row });
}
const normalizedDuplicates = [...normalizedTranslations]
    .filter(([, rows]) => rows.length > 1)
    .map(([key, rows]) => ({ key, rows }));
const translationsByNormalizedKey = new Map(
    [...normalizedTranslations].map(([key, rows]) => [key, rows[0].row]),
);

const frontend = scanFrontendSources();
const missingLiterals = [...frontend.literalUsages]
    .filter(([key]) => !originalKeySet.has(key))
    .map(([key, locations]) => ({ key, locations }));
const namedOriginalKeys = originalLineItems.flatMap(item =>
    (item.text.match(NAMED_PARAMETER_RE) || []).map(named => ({
        text: item.text,
        named,
        location: `language/original-web.txt:${item.line}`,
    })),
);
const namedParameters = uniqueNamedParameters([...frontend.namedParameters, ...namedOriginalKeys]);
const missingFrontendTranslations = originalKeys
    .filter(key => !translationsByNormalizedKey.has(key))
    .map(key => ({ key, line: originalLocations.get(key)[0] }));
const skipGeneratedIndexComparison = originalDuplicates.length > 0
    || originalContextErrors.length > 0
    || normalizedDuplicates.length > 0
    || missingFrontendTranslations.length > 0
    || translationFormatErrors.length > 0
    || translationParameterErrors.length > 0;
const generated = validateGeneratedFiles(originalKeys, translationsByNormalizedKey, translationRows, {
    skipIndexComparison: skipGeneratedIndexComparison,
});
const unusedReport = findUnusedKeys(originalKeys, frontend.sourceStrings, frontend.parameterizedShapes);
const doubleStarTodos = findDoubleStarTodos(originalKeys, frontend.templateShapes);
const specificCandidates = findSpecificCandidates(originalKeys, frontend.sourceStrings);

const blockerCounts = {
    "代码字面量未登记": missingLiterals.length,
    "Vue 模板中文硬编码": frontend.templateTexts.length,
    "命名变量占位符": namedParameters.length,
    "original-web.txt 重复键": originalDuplicates.length,
    "上下文翻译键格式错误": originalContextErrors.length,
    "translate.json raw (*)/(**) key": rawTranslateKeys.length,
    "translate.json 结构错误": translationFormatErrors.length,
    "translate.json 参数错误": translationParameterErrors.length,
    "translate.json 规范化重复": normalizedDuplicates.length,
    "前端键缺少 translate.json 翻译": missingFrontendTranslations.length,
    "public/language/web 结构或内容错误": generated.errors.length,
    "public key raw (*)/(**)": generated.rawPublicKeys.length,
};

console.log("== 阻断错误 ==");
for (const [label, count] of Object.entries(blockerCounts)) console.log(`  ${label}: ${count}`);

if (missingLiterals.length) {
    console.log("\n-- 代码字面量未登记 --");
    for (const item of missingLiterals) {
        console.log(`  「${item.key}」`);
        for (const location of item.locations) console.log(`    ${location}`);
    }
}
if (frontend.templateTexts.length) {
    console.log("\n-- Vue 模板中文硬编码 --");
    for (const item of frontend.templateTexts) console.log(`  ${item.rel}:${item.line} 「${item.text}」`);
}
if (namedParameters.length) {
    console.log("\n-- 命名变量占位符（请改用 (*)）--");
    for (const item of namedParameters) console.log(`  ${item.location} ${item.named} 「${item.text}」`);
}
if (originalDuplicates.length) {
    console.log("\n-- original-web.txt 重复键 --");
    for (const item of originalDuplicates) console.log(`  行 ${item.lines.join(", ")} 「${item.key}」`);
}
if (originalContextErrors.length) {
    console.log("\n-- 上下文翻译键格式错误 --");
    for (const error of originalContextErrors) console.log(`  ${error}`);
}
if (rawTranslateKeys.length) {
    console.log("\n-- translate.json raw key（请改用 (%Tn)/(%Mn)）--");
    for (const item of rawTranslateKeys) console.log(`  ${item.location} 「${item.key}」`);
}
for (const section of [translationFormatErrors, translationParameterErrors]) {
    if (section.length) {
        console.log("\n-- translate.json 数据错误 --");
        for (const error of section) console.log(`  ${error}`);
    }
}
if (normalizedDuplicates.length) {
    console.log("\n-- translate.json 规范化重复 --");
    for (const item of normalizedDuplicates) {
        console.log(`  「${item.key}」：${item.rows.map(row => `index ${row.index}「${row.row.key}」`).join("；")}`);
    }
}
if (missingFrontendTranslations.length) {
    console.log("\n-- 前端键缺少 translate.json 翻译 --");
    for (const item of missingFrontendTranslations) console.log(`  language/original-web.txt:${item.line} 「${item.key}」`);
}
if (generated.errors.length) {
    console.log("\n-- public/language/web 结构或内容错误 --");
    for (const error of generated.errors) console.log(`  ${error}`);
}
if (generated.rawPublicKeys.length) {
    console.log("\n-- public key raw (*)/(**) --");
    for (const item of generated.rawPublicKeys) console.log(`  public/language/web/key.js[index ${item.index}] 「${item.key}」`);
}

console.log("\n== 迁移待办（仅报告，不阻断）==");
const dynamicCounts = new Map([["模板字符串", 0], ["字符串拼接", 0]]);
for (const item of frontend.dynamicCalls) dynamicCounts.set(item.category, dynamicCounts.get(item.category) + 1);
console.log(`  动态翻译键/模板字符串: ${dynamicCounts.get("模板字符串")}`);
console.log(`  动态翻译键/字符串拼接: ${dynamicCounts.get("字符串拼接")}`);
console.log(`  仍使用 (**) 的前端键: ${doubleStarTodos.length}`);
console.log(`  可明显改用现有 (*) 键: ${specificCandidates.length}`);
if (frontend.dynamicCalls.length) {
    console.log("\n-- 动态翻译键 --");
    for (const category of ["模板字符串", "字符串拼接"]) {
        const items = frontend.dynamicCalls.filter(item => item.category === category);
        if (!items.length) continue;
        console.log(`  [${category}]`);
        for (const item of items) console.log(`    ${item.location} ${item.expression}`);
    }
}
if (doubleStarTodos.length) {
    console.log("\n-- (**) 前端键 --");
    for (const item of doubleStarTodos) {
        console.log(`  「${item.key}」`);
        if (item.sources.length) {
            for (const location of item.sources) console.log(`    ${location}`);
        } else {
            console.log("    来源：未在直接模板字符串中定位（可能经变量间接传入）");
        }
    }
}
if (specificCandidates.length) {
    console.log("\n-- 可明显参数化的具体文案 --");
    for (const item of specificCandidates) {
        console.log(`  「${item.concrete}」 => 「${item.generic}」`);
        for (const location of item.sources) console.log(`    ${location}`);
    }
}

console.log("\n== 疑似未使用（仅报告，不阻断）==");
console.log(`  实际源码扫描文件数: ${unusedReport.sourceFileCount}`);
console.log(`  参数化形状确认使用数: ${unusedReport.parameterizedUsages.size}`);
console.log(`  疑似未使用数: ${unusedReport.unused.length}`);
for (const key of unusedReport.unused.slice(0, 20)) console.log(`  「${key}」`);
if (unusedReport.unused.length > 20) console.log(`  ...（共 ${unusedReport.unused.length} 条）`);
if (unusedReport.parameterizedUsages.size) {
    console.log("\n-- 参数化键使用证据（固定片段/参数槽位完整匹配） --");
    for (const [key, locations] of unusedReport.parameterizedUsages) {
        console.log(`  「${key}」`);
        for (const location of locations.slice(0, 8)) console.log(`    ${location}`);
        if (locations.length > 8) console.log(`    ...（共 ${locations.length} 个来源）`);
    }
}

if (frontend.parserWarnings.length) {
    console.log("\n== 检测器提示（不阻断）==");
    for (const warning of frontend.parserWarnings) console.log(`  ${warning}`);
}

console.log("\n== 汇总 ==");
console.log(`  前端扫描文件数: ${frontend.files.length}`);
console.log(`  前端字面量键数（去重）: ${frontend.literalUsages.size}`);
console.log(`  阻断错误总数: ${Object.values(blockerCounts).reduce((sum, count) => sum + count, 0)}`);
console.log(`  迁移待办总数: ${frontend.dynamicCalls.length + doubleStarTodos.length + specificCandidates.length}`);
console.log(`  疑似未使用数: ${unusedReport.unused.length}`);

if (Object.values(blockerCounts).some(count => count > 0)) {
    console.log("\n校验失败：存在阻断错误。");
    process.exit(1);
}
console.log("\n校验通过：阻断错误为 0；迁移待办与疑似未使用仅作报告。");
