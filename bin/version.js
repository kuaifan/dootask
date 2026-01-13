const fs = require('fs');
const path = require("path");
const exec = require('child_process').exec;
let ProxyAgent = null;
try {
    ProxyAgent = require("undici").ProxyAgent;
} catch (error) {
    ProxyAgent = null;
}
const packageFile = path.resolve(process.cwd(), "package.json");
const changeFile = path.resolve(process.cwd(), "CHANGELOG.md");

const verOffset = 6394; // 版本号偏移量
const codeOffset = 34;  // 代码版本号偏移量

const envFilePath = path.resolve(process.cwd(), ".env");
const defaultAiSystemPrompt = "你是一位软件发布日志编辑专家。请产出 Markdown 更新日志，面向普通用户，以通俗友好的简体中文描述更新带来的直接好处，避免技术术语。所有章节标题必须以 `### ` 开头并保持英文 Title Case（例如 `### Features`、`### Bug Fixes`、`### Performance`、`### Documentation` 等）。每个章节内的条目按用户价值和影响范围排序，将更重要、影响更广的更新放在前面。";
const defaultOpenAiEndpoint = "https://api.openai.com/v1/chat/completions";

function loadEnvFile(filePath) {
    if (!fs.existsSync(filePath)) {
        return;
    }
    const content = fs.readFileSync(filePath, "utf8");
    content.split(/\r?\n/).forEach(rawLine => {
        const line = rawLine.trim();
        if (!line || line.startsWith("#")) {
            return;
        }
        const equalsIndex = line.indexOf("=");
        if (equalsIndex === -1) {
            return;
        }
        let key = line.slice(0, equalsIndex).trim();
        if (key.startsWith("export ")) {
            key = key.slice(7).trim();
        }
        let value = line.slice(equalsIndex + 1).trim();
        if (!value) {
            value = "";
        }
        if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
            value = value.slice(1, -1);
        } else {
            const commentIndex = value.indexOf(" #");
            if (commentIndex !== -1) {
                value = value.slice(0, commentIndex).trim();
            }
        }
        if (process.env[key] === undefined) {
            process.env[key] = value;
        }
    });
}


loadEnvFile(envFilePath);

function resolveApiEndpoint(candidate) {
    const source = (candidate || "").trim();
    if (!source) {
        return defaultOpenAiEndpoint;
    }
    if (/\/chat\/completions(\?|$)/.test(source)) {
        return source;
    }
    const normalized = source.replace(/\/+$/, "");
    if (/\/v\d+$/i.test(normalized)) {
        return `${normalized}/chat/completions`;
    }
    return `${normalized}/v1/chat/completions`;
}

function loadSocksProxyAgent(proxyUrl) {
    try {
        const { SocksProxyAgent } = require('socks-proxy-agent');
        return new SocksProxyAgent(proxyUrl);
    } catch (error) {
        if (error && error.code === 'MODULE_NOT_FOUND') {
            console.warn("检测到 SOCKS 代理，但未安装 socks-proxy-agent，请运行 `npm install --save-dev socks-proxy-agent` 后重试。");
        } else {
            console.warn(`无法初始化 SOCKS 代理: ${error?.message || error}`);
        }
        return null;
    }
}

function createProxyDispatcher(proxyUrl) {
    if (!proxyUrl) {
        return null;
    }
    let parsedProtocol = '';
    try {
        parsedProtocol = new URL(proxyUrl).protocol.replace(':', '').toLowerCase();
    } catch (error) {
        console.warn(`代理地址无效 (${proxyUrl}): ${error.message}`);
        return null;
    }
    if (parsedProtocol.startsWith('socks')) {
        return loadSocksProxyAgent(proxyUrl);
    }
    if (!ProxyAgent) {
        console.warn('未找到 undici.ProxyAgent，无法启用 HTTP 代理。');
        return null;
    }
    try {
        return new ProxyAgent(proxyUrl);
    } catch (error) {
        console.warn(`无法初始化代理 (${proxyUrl}): ${error.message}`);
        return null;
    }
}

function buildDefaultUserPrompt(version, changelogSection) {
    return [
        "你是一位软件发布日志编辑专家。",
        "下面是一段通过 git 提交记录自动生成的更新日志文本。",
        "",
        "请将其整理为一份「面向普通用户、简洁概览风格」的 changelog，保持 Markdown 格式，包含以下结构：",
        "",
        `## [${version}]`,
        "",
        "### Features",
        "",
        "- ...",
        "",
        "### Bug Fixes",
        "",
        "- ...",
        "",
        "### Performance",
        "",
        "- ...",
        "",
        "**要求：**",
        "1. 删除技术性或重复的细节，合并相似项。",
        "2. 语句自然简洁，用简体中文描述。",
        "3. 使用贴近日常的词汇，突出更新对普通用户的直接价值，避免开发或管理术语（如\"refactor\"、\"merge branch\"、\"commit lint\"）。",
        "4. 小节标题必须以 `### ` 开头并保持英文 Title Case（例如 `### Features`、`### Bug Fixes`、`### Performance`、`### Documentation`、`### Security`、`### Miscellaneous` 等），不得翻译成中文。",
        "5. 每个小节内的条目按用户价值和影响范围排序，将更重要、影响更广的更新放在前面。",
        "6. 若某个小节没有内容，请省略整段小节（包括标题）。",
        "7. 输出仅为 Markdown changelog 内容，不加其他解释。",
        "",
        "以下是原始日志：",
        "```markdown",
        changelogSection,
        "```"
    ].join("\n");
}

function runExec(command) {
    return new Promise((resolve, reject) => {
        exec(command, { maxBuffer: 1024 * 1024 * 10 }, (err, stdout, stderr) => {
            if (err) {
                reject(err);
                return;
            }
            resolve(stdout.toString());
        });
    });
}

function removeDuplicateLines(log) {
    const logs = log.split(/(\n## \[.*?\])/);
    return logs.map(str => {
        const array = [];
        const items = str.split("\n");
        items.forEach(item => {
            if (/^-/.test(item)) {
                if (array.indexOf(item) === -1) {
                    array.push(item);
                }
            } else {
                array.push(item);
            }
        });
        return array.join("\n");
    }).join('');
}

function findSectionBounds(content, version) {
    const heading = `## [${version}]`;
    const start = content.indexOf(heading);
    if (start === -1) {
        return null;
    }
    const nextHeadingIndex = content.indexOf("\n## [", start + heading.length);
    const end = nextHeadingIndex === -1 ? content.length : nextHeadingIndex;
    return { start, end };
}

function trimCliffOutput(rawOutput, version) {
    const markerIndex = rawOutput.indexOf("## [");
    if (markerIndex === -1) {
        return "";
    }
    return rawOutput
        .slice(markerIndex)
        .replace("## [Unreleased]", `## [${version}]`)
        .trim();
}

function buildAiHeaders(apiUrl, apiKey) {
    const headers = { "Content-Type": "application/json" };
    const customHeader = process.env.CHANGELOG_AI_AUTH_HEADER;
    if (customHeader) {
        const separatorIndex = customHeader.indexOf(":");
        if (separatorIndex !== -1) {
            const headerName = customHeader.slice(0, separatorIndex).trim();
            const headerValue = customHeader.slice(separatorIndex + 1).trim();
            if (headerName && headerValue) {
                headers[headerName] = headerValue;
            }
        }
        return headers;
    }
    if (apiUrl.includes("openai.azure.com")) {
        headers["api-key"] = apiKey;
    } else {
        headers.Authorization = `Bearer ${apiKey}`;
    }
    return headers;
}

async function enhanceWithAI(version, changelogSection) {
    const apiKey = (process.env.OPENAI_API_KEY || "").trim();
    if (!apiKey) {
        console.warn("未设置 OPENAI_API_KEY，跳过 AI 发布日志整理。");
        return changelogSection;
    }
    const proxyUrl = (process.env.OPENAI_PROXY_URL || "").trim();
    const explicitApiUrl = process.env.CHANGELOG_AI_URL || process.env.OPENAI_API_URL;
    const apiUrl = resolveApiEndpoint(explicitApiUrl);
    const dispatcher = createProxyDispatcher(proxyUrl);
    const model = process.env.CHANGELOG_AI_MODEL || process.env.OPENAI_API_MODEL || "gpt-4o-mini";
    const systemPrompt = process.env.CHANGELOG_AI_SYSTEM_PROMPT || defaultAiSystemPrompt;
    const userPrompt = process.env.CHANGELOG_AI_PROMPT || buildDefaultUserPrompt(version, changelogSection);

    try {
        const requestInit = {
            method: "POST",
            headers: buildAiHeaders(apiUrl, apiKey),
            body: JSON.stringify({
                model,
                messages: [
                    { role: "system", content: systemPrompt },
                    { role: "user", content: userPrompt }
                ],
            })
        };
        if (dispatcher) {
            requestInit.dispatcher = dispatcher;
        }
        const response = await fetch(apiUrl, requestInit);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`AI request failed: ${errorText}`);
        }
        const data = await response.json();
        const aiText = data?.choices?.[0]?.message?.content?.trim();
        if (!aiText) {
            throw new Error("AI response did not contain content.");
        }
        return aiText
            .replace(/^\s*```markdown\s*/i, "")
            .replace(/\s*```\s*$/i, "")
            .trim();
    } catch (error) {
        console.warn("AI summarization failed, falling back to original section:", error.message);
        return changelogSection;
    }
}

async function generateLatestSection(version) {
    const rawOutput = await runExec('docker run -t --rm -v "$(pwd)":/app/ orhunp/git-cliff:1.3.0 --unreleased');
    const section = trimCliffOutput(rawOutput, version);
    if (!section.trim() || section.trim() === `## [${version}]`) {
        return "";
    }
    return section;
}

function insertChangelogSection(existing, section, version) {
    const trimmedSection = section.trim();
    if (!trimmedSection) {
        return existing;
    }
    const bounds = findSectionBounds(existing, version);
    if (bounds) {
        return `${existing.slice(0, bounds.start)}${trimmedSection}\n\n${existing.slice(bounds.end).replace(/^(\n)+/, "")}`;
    }
    const insertIndex = existing.indexOf("\n## [");
    if (insertIndex === -1) {
        return `${existing.trimEnd()}\n\n${trimmedSection}\n`;
    }
    const head = existing.slice(0, insertIndex).trimEnd();
    const tail = existing.slice(insertIndex).replace(/^(\n)+/, "");
    return `${head}\n\n${trimmedSection}\n\n${tail}`;
}

async function main() {
    try {
        const verCountRaw = await runExec("git rev-list --count HEAD");
        const codeCountRaw = await runExec("git tag --merged pro -l 'v*' | wc -l");
        const verCount = verCountRaw.trim();
        const codeCount = codeCountRaw.trim();

        const num = verOffset + parseInt(verCount, 10);
        if (Number.isNaN(num) || Math.floor(num % 100) < 0) {
            throw new Error(`get version error ${verCount}`);
        }
        const version = `${Math.floor(num / 10000)}.${Math.floor((num % 10000) / 100)}.${Math.floor(num % 100)}`;
        const codeVersion = codeOffset + parseInt(codeCount, 10);

        let packageContent = fs.readFileSync(packageFile, "utf8");
        packageContent = packageContent.replace(/"version":\s*"(.*?)"/, `"version": "${version}"`);
        packageContent = packageContent.replace(/"codeVerson":(.*?)(,|$)/, `"codeVerson": ${codeVersion}$2`);
        fs.writeFileSync(packageFile, packageContent, "utf8");

        console.log("New version: " + version);
        console.log("New code verson: " + codeVersion);

        if (!fs.existsSync(changeFile)) {
            throw new Error("Change file does not exist");
        }

        const latestSection = await generateLatestSection(version);
        if (!latestSection) {
            console.log("No new changelog entries detected.");
            return;
        }

        const aiSection = await enhanceWithAI(version, latestSection);

        const changelogContent = fs.readFileSync(changeFile, "utf8");
        const mergedContent = insertChangelogSection(changelogContent, aiSection, version);
        const dedupedContent = removeDuplicateLines(mergedContent);

        fs.writeFileSync(changeFile, dedupedContent.trimEnd() + "\n", "utf8");
        console.log("Log file updated: CHANGELOG.md");
    } catch (error) {
        console.error(error);
        process.exitCode = 1;
    }
}

main();
