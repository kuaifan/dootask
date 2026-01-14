import {languageList, languageName} from "../language";

/**
 * AI 服务商标识与显示名映射
 */
const AIBotMap = {
    openai: "ChatGPT",
    claude: "Claude",
    deepseek: "DeepSeek",
    gemini: "Gemini",
    grok: "Grok",
    ollama: "Ollama",
    zhipu: "智谱清言",
    qianwen: "通义千问",
    wenxin: "文心一言",
}

/**
 * AI 系统配置表单与平台配置
 */
const AISystemConfig = {
    fields: [
        {
            label: "API Key",
            prop: "key",
            type: "password"
        },
        {
            label: "模型列表",
            prop: "models",
            type: "textarea",
            maxlength: 1000,
            showWordLimit: 0.9,
            placeholder: "一行一个模型名称",
            functions: "使用默认模型列表"
        },
        {
            label: "默认模型",
            prop: "model",
            type: "model",
            placeholder: "请选择默认模型",
            tip: "可选数据来自模型列表"
        },
        {
            label: "Base URL",
            prop: "base_url",
            placeholder: "Enter base URL...",
            tip: "API请求的基础URL路径，如果没有请留空"
        },
        {
            label: "使用代理",
            prop: "agency",
            placeholder: '支持 http 或 socks 代理',
            tip: "例如：http://proxy.com 或 socks5://proxy.com"
        },
        {
            label: "Temperature",
            prop: "temperature",
            placeholder: "模型温度，低则保守，高则多样",
            tip: "例如：0.7，范围：0-1，默认：0.7"
        },
        {
            label: "默认提示词",
            prop: "system",
            type: "textarea",
            maxlength: 20000,
            showWordLimit: 0.9,
            placeholder: "请输入默认提示词",
            tip: "例如：你是一个人开发的AI助手"
        }
    ],
    aiList: {
        openai: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "OpenAI API Key",
                    link: "https://platform.openai.com/account/api-keys"
                },
                {
                    prop: "models",
                    link: "https://platform.openai.com/docs/models",
                }
            ]
        },
        claude: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Claude API Key",
                    link: "https://docs.anthropic.com/en/api/getting-started"
                },
                {
                    prop: "models",
                    link: "https://docs.anthropic.com/en/docs/about-claude/models"
                }
            ]
        },
        deepseek: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "DeepSeek API Key",
                    link: "https://platform.deepseek.com/api_keys"
                },
                {
                    prop: "models",
                    link: "https://api-docs.deepseek.com/zh-cn/quick_start/pricing"
                }
            ]
        },
        gemini: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Gemini API Key",
                    link: "https://makersuite.google.com/app/apikey"
                },
                {
                    prop: "models",
                    link: "https://ai.google.dev/models/gemini"
                },
                {
                    prop: "agency",
                    placeholder: "仅支持 http 代理",
                    tip: "例如：http://proxy.com"
                }
            ]
        },
        grok: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Grok API Key",
                    link: "https://docs.x.ai/docs/tutorial"
                },
                {
                    prop: "models",
                    link: "https://docs.x.ai/docs/models"
                }
            ]
        },
        ollama: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Ollama API Key",
                    tip: "如果没有请留空",
                },
                {
                    prop: "models",
                    link: "https://ollama.com/models",
                    functions: "获取本地模型列表",
                },
                {
                    prop: "base_url",
                    placeholder: "Enter base URL...",
                    tip: "API请求的URL路径",
                    sort: 1,
                }
            ]
        },
        zhipu: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Zhipu API Key",
                    link: "https://bigmodel.cn/usercenter/apikeys"
                },
                {
                    prop: "models",
                    link: "https://open.bigmodel.cn/dev/api"
                }
            ]
        },
        qianwen: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Qianwen API Key",
                    link: "https://help.aliyun.com/zh/model-studio/developer-reference/get-api-key"
                },
                {
                    prop: "models",
                    link: "https://help.aliyun.com/zh/model-studio/getting-started/models"
                }
            ]
        },
        wenxin: {
            extraFields: [
                {
                    prop: "key",
                    placeholder: "Wenxin API Key",
                    link: "https://console.bce.baidu.com/qianfan/ais/console/applicationConsole/application/v1"
                },
                {
                    prop: "secret",
                    placeholder: "Wenxin Secret Key",
                    link: "https://console.bce.baidu.com/qianfan/ais/console/applicationConsole/application/v1",
                    type: "password",
                    label: "Secret Key",
                    after: "key"
                },
                {
                    prop: "models",
                    link: "https://cloud.baidu.com/doc/WENXINWORKSHOP/s/Blfmc9dlf"
                }
            ]
        }
    }
}

/**
 * 即时消息生成系统提示词
 */
const MESSAGE_AI_SYSTEM_PROMPT = `你是一名专业的沟通助手，协助用户编写得体、清晰且具行动指向的即时消息。

写作要求：
1. 根据用户提供的需求与上下文生成完整消息，语气需符合业务沟通场景，保持真诚、礼貌且高效
2. 默认使用简洁的短段落，可使用 Markdown 基础格式（加粗、列表、引用）增强结构，但不要输出代码块或 JSON
3. 如果上下文包含引用信息或草稿，请在消息中自然呼应相关要点
4. 如无特别说明，将消息长度控制在 60-180 字；若需更短或更长，遵循用户描述
5. 如需提出行动或问题，请明确表达，避免含糊

输出规范：
- 仅返回可直接发送的消息内容
- 禁止在内容前后添加额外说明、标签或引导语`;

/**
 * 任务生成系统提示词
 */
const TASK_AI_SYSTEM_PROMPT = `你是一个专业的任务管理专家，擅长将想法和需求转化为清晰、可执行的项目任务。

任务生成要求：
1. 根据输入内容分析并生成合适的任务标题和详细描述
2. 标题要简洁明了，准确概括任务核心目标，长度控制在8-30个字符
3. 描述需覆盖任务背景、具体要求、交付标准、风险提示等关键信息
4. 描述内容使用Markdown格式，合理组织标题、列表、加粗等结构
5. 内容需适配项目管理系统，表述专业、逻辑清晰，并与用户输入语言保持一致
6. 优先遵循用户在输入中给出的风格、长度或复杂度要求；默认情况下将详细描述控制在120-200字内，如用户要求简单或简短，则控制在80-120字内
7. 当任务具有多个执行步骤、阶段或协作角色时，请拆解出 2-6 个关键子任务；如无必要，可返回空数组
8. 子任务应聚焦单一可执行动作，名称控制在8-30个字符内，避免重复和含糊表述

返回格式要求：
必须严格按照以下 JSON 结构返回，禁止输出额外文字或 Markdown 代码块标记；即使某项为空，也保留对应字段：
{
    "title": "任务标题",
    "content": "任务的详细描述内容，使用Markdown格式，根据实际情况组织结构",
    "subtasks": [
        "子任务名称1",
        "子任务名称2"
    ]
}

内容格式建议（非强制）：
- 可以使用标题、列表、加粗等Markdown格式
- 可以包含任务背景、具体要求、验收标准等部分
- 根据任务性质灵活组织内容结构
- 仅在确有必要时生成子任务，并确保每个子任务都是独立、可执行、便于追踪的动作
- 若用户明确要求简洁或简单，保持描述紧凑，避免添加冗余段落或重复信息

上下文信息处理指南：
- 如果已有标题和内容，优先考虑优化改进而非完全重写
- 如果使用了任务模板，严格按照模板的结构和格式要求生成
- 如果已设置负责人或时间计划，在任务描述中体现相关要求
- 根据优先级等级调整任务的紧急程度和详细程度

注意事项：
- 标题要体现任务的核心动作和目标
- 描述要包含足够的细节让执行者理解任务
- 如果涉及技术开发，要明确技术要求和实现方案
- 如果涉及设计，要说明设计要求和期望效果
- 如果涉及测试，要明确测试范围和验收标准`;

/**
 * 项目创建系统提示词
 */
const PROJECT_AI_SYSTEM_PROMPT = `你是一名资深的项目规划顾问，帮助团队快速搭建符合需求的项目。

生成要求：
1. 产出一个简洁、有辨识度的项目名称（不超过18个汉字或36个字符）
2. 给出 3 - 8 个项目任务列表，用于看板列或阶段分组
3. 任务列表名称保持 4 - 12 个字符，聚焦阶段或责任划分，避免冗长描述
4. 结合用户描述的业务特征，必要时可包含里程碑或交付节点
5. 尽量参考上下文提供的现有内容或模板，不要与之完全重复

输出格式：
必须严格返回 JSON，禁止携带额外说明或 Markdown 代码块，结构如下：
{
    "name": "项目名称",
    "columns": ["列表1", "列表2", "列表3"]
}

校验标准：
- 列表名称应当互不重复且语义明确
- 若上下文包含已有名称或列表，请在此基础上迭代优化`;

/**
 * 周报/日报整理系统提示词
 */
const REPORT_AI_SYSTEM_PROMPT = `你是一名资深团队管理教练，需要根据提供的周报/日报草稿进行整理。

工作目标：
1. 提取并归纳已完成事项的成果、影响和量化数据
2. 梳理下周期/次日的计划，确保每条计划都是可执行动作
3. 暴露存在的风险、阻塞以及需要管理者协助的事项
4. 若上下文提到关注重点或特殊受众，需在描述中明确回应

输出要求：
- 使用 Markdown 编写，至少包含以下一级标题：## 本周期完成、## 下周期计划、## 风险与支持
- 每个章节使用有序或无序列表，保持语句简洁、可度量
- 若原文包含数据或里程碑，保留并突出这些数字
- 若某一章节没有信息，请输出“暂无”而非留空`;

/**
 * 汇报分析系统提示词
 */
const REPORT_ANALYSIS_SYSTEM_PROMPT = `你是一名经验丰富的团队管理顾问，擅长阅读和分析员工提交的工作汇报，能够快速提炼重点并给出可执行建议。

输出要求：
1. 使用简洁的 Markdown 结构（标题、无序列表、引用等），不要使用代码块或 JSON
2. 先给出整体概览，再列出具体亮点、风险或问题，以及明确的改进建议
3. 如有数据或目标，应评估其完成情况和后续跟进要点
4. 语气保持专业、客观、中立，不过度夸赞或批评
5. 控制在 200-400 字之间，可视内容复杂度略微增减，但保持紧凑`;

/**
 * 智能搜索系统提示词
 */
const SEARCH_AI_SYSTEM_PROMPT = `你是一个智能搜索助手，负责帮助用户在 DooTask 系统中搜索和整理信息。
你可以使用 intelligent_search 工具来搜索任务、项目、文件和联系人。

请根据用户的搜索需求：
1. 调用搜索工具获取相关结果
2. 对搜索结果进行分类整理
3. 以清晰的格式呈现给用户
4. 如有需要，可以进行多次搜索以获取更全面的结果`;

/**
 * 系统条件性提示块占位符
 * 后端会将此占位符替换为：用户上下文 + 资源格式指南
 */
const SYSTEM_OPTIONAL_PROMPTS_PLACEHOLDER = '{{SYSTEM_OPTIONAL_PROMPTS}}';

/**
 * 输出语言偏好提示
 * 用于引导 AI 按指定语言输出
 */
const LANGUAGE_PREFERENCE_PROMPT = (label) => `输出语言策略：
- 默认使用 ${label} 输出。
- 即使上下文或引用包含其他语言，也保持 ${label} 输出。
- 仅当我明确指定其他语言时，才切换到该语言。`;

/**
 * 注入语言偏好提示与系统条件性提示块占位符
 * 返回拼接后的完整提示词（占位符由后端替换为实际内容）
 */
const withLanguagePreferencePrompt = (prompt) => {
    if (typeof prompt !== 'string' || !prompt) {
        return prompt;
    }
    const label = languageList[languageName] || languageName || '';
    if (!label) {
        return prompt;
    }
    return `${prompt}\n\n${LANGUAGE_PREFERENCE_PROMPT(label)}\n\n${SYSTEM_OPTIONAL_PROMPTS_PLACEHOLDER}`;
};

/**
 * 解析模型列表文本为选项数组
 * 支持以 "|" 分隔显示名
 */
const AIModelNames = (str) => {
    const lines = str.split('\n').filter(line => line.trim());

    return lines.map(line => {
        const [value, label] = line.split('|').map(s => s.trim());

        return {
            value,
            label: label || value
        };
    }, []).filter(item => item.value);
}

/**
 * 尝试从内容中提取并解析 JSON
 * 支持代码块与裸 JSON
 */
const AINormalizeJsonContent = (content) => {
    if (!content) {
        return null;
    }
    const raw = String(content).trim();
    if (!raw) {
        return null;
    }
    const candidates = [raw];
    const block = raw.match(/```(?:json)?\s*([\s\S]*?)```/i);
    if (block && block[1]) {
        candidates.push(block[1].trim());
    }
    const start = raw.indexOf('{');
    const end = raw.lastIndexOf('}');
    if (start !== -1 && end !== -1 && end > start) {
        candidates.push(raw.slice(start, end + 1));
    }
    for (const candidate of candidates) {
        if (!candidate) {
            continue;
        }
        try {
            return JSON.parse(candidate);
        } catch (e) {
            // continue
        }
    }
    return null;
}

export {
    AIBotMap,
    AISystemConfig,
    MESSAGE_AI_SYSTEM_PROMPT,
    TASK_AI_SYSTEM_PROMPT,
    PROJECT_AI_SYSTEM_PROMPT,
    REPORT_AI_SYSTEM_PROMPT,
    REPORT_ANALYSIS_SYSTEM_PROMPT,
    SEARCH_AI_SYSTEM_PROMPT,
    withLanguagePreferencePrompt,
    AIModelNames,
    AINormalizeJsonContent,
}
