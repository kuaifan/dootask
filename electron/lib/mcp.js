/**
 * DooTask MCP Server
 *
 * DooTask 的 Electron 客户端集成了 Model Context Protocol (MCP) 服务，
 * 允许 AI 助手(如 Claude)直接与 DooTask 任务进行交互。
 *
 * 提供的工具（共 25 个）:
 *
 * === 用户管理 ===
 * - get_users_basic   - 批量获取用户基础信息（1-50个），便于匹配负责人/协助人
 * - search_user       - 按关键词搜索用户，支持按项目/对话范围筛选，用于不知道用户ID时的查找
 *
 * === 任务管理 ===
 * - list_tasks        - 获取当前用户相关的任务列表（负责/协助/关注），支持按状态/项目/时间筛选
 * - get_task          - 获取单个任务的完整详细信息，比 list_tasks 返回更详细
 * - complete_task     - 快速标记任务完成
 * - create_task       - 在指定项目中创建新任务
 * - update_task       - 更新任务的任意属性，只需提供要修改的字段
 * - create_sub_task   - 为指定主任务创建子任务
 * - get_task_files    - 获取任务附件列表
 * - delete_task       - 删除或还原任务
 *
 * === 项目管理 ===
 * - list_projects     - 获取当前用户可访问的项目列表，支持按归档状态筛选、搜索
 * - get_project       - 获取项目的完整详细信息，比 list_projects 返回更详细
 * - create_project    - 创建新项目
 * - update_project    - 修改项目信息（名称、描述等）
 *
 * === 文件管理（个人文件系统） ===
 * - list_files        - 浏览个人文件系统，获取指定文件夹下的文件和子文件夹列表
 * - search_files      - 搜索用户文件系统中的文件（自己创建的和共享给自己的）
 * - get_file_detail   - 获取文件详情，支持通过文件ID或分享码访问，返回 content_url 可配合 WebFetch 读取
 *
 * === 工作报告 ===
 * - list_received_reports    - 获取我接收的汇报列表，支持按类型/状态/部门/时间筛选
 * - get_report_detail        - 获取汇报详情，支持通过报告ID或分享码访问，内容自动转为 Markdown
 * - generate_report_template - 基于任务完成情况自动生成汇报模板（已完成/未完成任务）
 * - create_report            - 创建并提交工作汇报
 * - list_my_reports          - 获取我发送的汇报列表，支持按类型/时间筛选
 * - mark_reports_read        - 批量标记汇报为已读或未读状态
 *
 * === 消息通知 ===
 * - send_message_to_user - 给指定用户发送私信
 * - get_message_list  - 两种模式：获取对话消息列表 或 按关键词搜索消息
 *
 * 配置方法:
 *  {
 *    "mcpServers": {
 *      "DooTask": {
 *        "url": "http://localhost:22224/mcp"
 *      }
 *    }
 *  }
 *
 * 使用示例:
 *
 * 任务管理：
 * - "查看我未完成的任务"
 * - "搜索包含'报告'的任务"
 * - "显示任务123的详细信息"
 * - "标记任务456为已完成"
 * - "在项目1中创建任务：完成用户手册，负责人100，协助人员101"
 * - "把任务789的截止时间改为下周五，并分配给用户200"
 *
 * 项目管理：
 * - "我有哪些项目？"
 * - "查看项目5的详情，包括所有列和成员"
 *
 * 文件管理：
 * - "查看我的文件列表"
 * - "搜索包含'设计稿'的文件"
 * - "显示文件123的详细信息"
 * - "帮我分析这个文档的内容"
 *
 * 工作报告：
 * - "查看未读的工作汇报"
 * - "生成本周周报"
 * - "查看我上周提交的日报"
 * - "把周报提交给用户100和200"
 * - "把所有未读报告标记为已读"
 */

let FastMCP = null;
const { z } = require('zod');
const loger = require("electron-log");
const TurndownService = require('turndown');
const { marked } = require('marked');

async function loadFastMCP() {
    if (FastMCP) {
        return FastMCP;
    }

    // Prefer require for environments that still support CJS entry, fall back to ESM import when exports block it.
    try {
        const maybeModule = require('fastmcp');
        FastMCP = maybeModule.FastMCP || maybeModule.default || maybeModule;
        return FastMCP;
    } catch (error) {
        if (error.code && !['ERR_PACKAGE_PATH_NOT_EXPORTED', 'ERR_REQUIRE_ESM'].includes(error.code)) {
            throw error;
        }
    }

    const moduleExports = await import('fastmcp');
    FastMCP = moduleExports.FastMCP || moduleExports.default || moduleExports;
    return FastMCP;
}

let mcpServer = null;

// 初始化 HTML 转 Markdown 工具
const turndownService = new TurndownService({
    headingStyle: 'atx',
    codeBlockStyle: 'fenced',
    bulletListMarker: '-',
    emDelimiter: '_',
    strongDelimiter: '**',
    linkStyle: 'inlined',
    preformattedCode: true,
});

// HTML 转 Markdown
function htmlToMarkdown(html) {
    if (!html) {
        return '';
    }
    if (typeof html !== 'string') {
        loger.warn(`HTML to Markdown: expected string, got ${typeof html}`);
        return '';
    }
    try {
        const markdown = turndownService.turndown(html);
        return markdown.trim();
    } catch (error) {
        loger.error(`HTML to Markdown conversion failed: ${error.message}`, { html: html.substring(0, 100) });
        // 返回清理后的纯文本作为降级方案
        return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    }
}

// Markdown 转 HTML
function markdownToHtml(markdown) {
    if (!markdown) {
        return '';
    }
    if (typeof markdown !== 'string') {
        loger.warn(`Markdown to HTML: expected string, got ${typeof markdown}`);
        return '';
    }
    try {
        // marked.parse 在某些版本可能返回 Promise,这里使用同步方法
        const html = marked.parse(markdown, { async: false });
        return html;
    } catch (error) {
        loger.error(`Markdown to HTML conversion failed: ${error.message}`, { markdown: markdown.substring(0, 100) });
        // 返回原始 markdown 作为降级方案,至少保留内容
        return markdown.replace(/\n/g, '<br>');
    }
}

class DooTaskMCP {
    constructor(mainWindow) {
        this.mainWindow = mainWindow;
        this.mcp = null;
        this.readyPromise = this.initMCP();
    }

    async initMCP() {
        const FastMCPClass = await loadFastMCP();

        this.mcp = new FastMCPClass({
            name: 'DooTask MCP Server',
            version: '1.0.0',
            description: 'DooTask 任务管理 MCP 接口',
        });

        this.setupTools();
    }

    /**
     * 调用接口
     */
    async request(method, path, data = {}) {
        try {
            // 通过主窗口执行前端代码来调用API
            if (!this.mainWindow || !this.mainWindow.webContents) {
                throw new Error('Main window not available, please open DooTask application first');
            }

            // 检查 webContents 是否 ready
            if (this.mainWindow.webContents.isLoading()) {
                loger.warn(`[MCP] WebContents is loading, waiting...`);
                await new Promise(resolve => {
                    this.mainWindow.webContents.once('did-finish-load', resolve);
                });
            }

            // 通过前端已有的API调用机制，添加超时处理
            const executePromise = this.mainWindow.webContents.executeJavaScript(`
                (async () => {
                    try {
                        // 检查API是否可用
                        if (typeof $A === 'undefined') {
                            return { error: 'API not available - $A is undefined' };
                        }
                        
                        if (typeof $A.apiCall !== 'function') {
                            return { error: 'API not available - $A.apiCall is not a function' };
                        }
                        
                        // 调用 API
                        const result = await $A.apiCall({
                            url: '${path}',
                            data: ${JSON.stringify(data)},
                            method: '${method.toLowerCase()}',
                        });
                        
                        try {
                            return { data: JSON.parse(JSON.stringify(result.data)) };
                        } catch (serError) {
                            return { error: 'Result contains non-serializable data' };
                        }
                    } catch (error) {
                        return { error: error.msg || error.message || String(error) || 'API request failed' };
                    }
                })()
            `);

            // 添加超时处理（30秒）
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Request timeout after 30 seconds')), 30000);
            });

            // 返回结果
            const result = await Promise.race([executePromise, timeoutPromise]);

            if (result && result.error) {
                throw new Error(result.error);
            }

            return result;
        } catch (error) {
            throw error;
        }
    }

    // 设置 MCP 工具
    setupTools() {
        // 用户管理：获取用户基础信息
        this.mcp.addTool({
            name: 'get_users_basic',
            description: '根据用户ID列表批量获取用户基础信息（昵称、邮箱、头像等）。适用于分配任务前确认成员身份，支持1-50个用户。',
            parameters: z.object({
                userids: z.array(z.number())
                    .min(1)
                    .max(50)
                    .describe('用户ID数组，至少1个，最多50个'),
            }),
            execute: async (params) => {
                const ids = params.userids;
                const requestData = {
                    userid: ids.length === 1 ? ids[0] : JSON.stringify(ids),
                };

                const result = await this.request('GET', 'users/basic', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const rawList = Array.isArray(result.data)
                    ? result.data
                    : (Array.isArray(result.data?.data) ? result.data.data : []);

                const users = rawList.map(user => ({
                    userid: user.userid,
                    nickname: user.nickname || '',
                    email: user.email || '',
                    avatar: user.avatar || '',
                    identity: user.identity || '',
                    department: user.department || '',
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            count: users.length,
                            users: users,
                        }, null, 2)
                    }]
                };
            }
        });

        // 用户管理：搜索用户
        this.mcp.addTool({
            name: 'search_user',
            description: '按关键词搜索用户（昵称、邮箱、拼音），支持按项目/对话范围筛选。与 get_users_basic 不同，此工具用于不知道具体用户ID时的查找场景。',
            parameters: z.object({
                keyword: z.string()
                    .min(1)
                    .describe('搜索关键词，支持昵称、邮箱、拼音等'),
                project_id: z.number()
                    .optional()
                    .describe('仅返回指定项目的成员'),
                dialog_id: z.number()
                    .optional()
                    .describe('仅返回指定对话的成员'),
                include_disabled: z.boolean()
                    .optional()
                    .describe('是否同时包含已离职/禁用用户'),
                include_bot: z.boolean()
                    .optional()
                    .describe('是否同时包含机器人账号'),
                with_department: z.boolean()
                    .optional()
                    .describe('是否返回部门信息'),
                page: z.number()
                    .optional()
                    .describe('页码，默认1'),
                pagesize: z.number()
                    .optional()
                    .describe('每页数量，默认20，最大100'),
            }),
            execute: async (params) => {
                const page = params.page && params.page > 0 ? params.page : 1;
                const pagesize = params.pagesize && params.pagesize > 0 ? Math.min(params.pagesize, 100) : 20;

                const keys = {
                    key: params.keyword,
                };

                if (params.project_id !== undefined) {
                    keys.project_id = params.project_id;
                }
                if (params.dialog_id !== undefined) {
                    keys.dialog_id = params.dialog_id;
                }
                if (params.include_disabled) {
                    keys.disable = 2;
                }
                if (params.include_bot) {
                    keys.bot = 2;
                }

                const requestData = {
                    page,
                    pagesize,
                    keys,
                };

                if (params.with_department) {
                    requestData.with_department = 1;
                }

                const result = await this.request('GET', 'users/search', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const data = result.data || {};
                let users = [];
                let total = 0;
                let perPage = pagesize;
                let currentPage = page;

                if (Array.isArray(data.data)) {
                    users = data.data;
                    total = data.total ?? users.length;
                    perPage = data.per_page ?? perPage;
                    currentPage = data.current_page ?? currentPage;
                } else if (Array.isArray(data)) {
                    users = data;
                    total = users.length;
                }

                const simplified = users.map(user => ({
                    userid: user.userid,
                    nickname: user.nickname || '',
                    email: user.email || '',
                    tags: user.tags || [],
                    department: user.department_info || user.department || '',
                    online: user.online ?? null,
                    identity: user.identity || '',
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            total,
                            page: currentPage,
                            pagesize: perPage,
                            users: simplified,
                        }, null, 2)
                    }]
                };
            }
        });

        // 获取任务列表
        this.mcp.addTool({
            name: 'list_tasks',
            description: '获取当前用户相关的任务列表（负责/协助/关注），支持按状态、项目、时间范围筛选和搜索。',
            parameters: z.object({
                status: z.enum(['all', 'completed', 'uncompleted'])
                    .optional()
                    .describe('任务状态: all(所有), completed(已完成), uncompleted(未完成)'),
                search: z.string()
                    .optional()
                    .describe('搜索关键词(可搜索任务ID、名称、描述)'),
                time: z.string()
                    .optional()
                    .describe('时间范围: today(今天), week(本周), month(本月), year(今年), 自定义时间范围,如：2025-12-12,2025-12-30'),
                project_id: z.number()
                    .optional()
                    .describe('项目ID,只获取指定项目的任务'),
                parent_id: z.number()
                    .optional()
                    .describe('主任务ID。大于0:获取该主任务的子任务; -1:仅获取主任务; 不传:所有任务'),
                page: z.number()
                    .optional()
                    .describe('页码,默认1'),
                pagesize: z.number()
                    .optional()
                    .describe('每页数量,默认20,最大100'),
            }),
            execute: async (params) => {
                const requestData = {
                    page: params.page || 1,
                    pagesize: params.pagesize || 20,
                };

                // 构建 keys 对象用于筛选
                const keys = {};
                if (params.search) {
                    keys.name = params.search;
                }
                if (params.status && params.status !== 'all') {
                    keys.status = params.status;
                }
                if (Object.keys(keys).length > 0) {
                    requestData.keys = keys;
                }

                // 其他筛选参数
                if (params.time !== undefined) {
                    requestData.time = params.time;
                }
                if (params.project_id !== undefined) {
                    requestData.project_id = params.project_id;
                }
                if (params.parent_id !== undefined) {
                    requestData.parent_id = params.parent_id;
                }

                const result = await this.request('GET', 'project/task/lists', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const tasks = result.data.data.map(task => ({
                    id: task.id,
                    name: task.name,
                    desc: task.desc || '无描述',
                    dialog_id: task.dialog_id,
                    status: task.complete_at ? '已完成' : '未完成',
                    complete_at: task.complete_at || '未完成',
                    end_at: task.end_at || '无截止时间',
                    project_id: task.project_id,
                    project_name: task.project_name || '',
                    column_name: task.column_name || '',
                    parent_id: task.parent_id,
                    owners: task.taskUser?.filter(u => u.owner === 1).map(u => ({
                        userid: u.userid,
                        username: u.username || u.nickname || `用户${u.userid}`
                    })) || [],
                    sub_num: task.sub_num || 0,
                    sub_complete: task.sub_complete || 0,
                    percent: task.percent || 0,
                    created_at: task.created_at,
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            total: result.data.total,
                            page: result.data.current_page,
                            pagesize: result.data.per_page,
                            tasks: tasks,
                        }, null, 2)
                    }]
                };
            }
        });

        // 获取任务详情
        this.mcp.addTool({
            name: 'get_task',
            description: '获取单个任务的完整详细信息，包括任务描述、完整内容（content）、负责人、协助人员、标签、时间等。比 list_tasks 返回更详细的信息。',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
            }),
            execute: async (params) => {
                const taskResult = await this.request('GET', 'project/task/one', {
                    task_id: params.task_id,
                });

                if (taskResult.error) {
                    throw new Error(taskResult.error);
                }

                const task = taskResult.data;

                // 获取任务完整内容
                let fullContent = task.desc || '无描述';
                try {
                    const contentResult = await this.request('GET', 'project/task/content', {
                        task_id: params.task_id,
                    });
                    if (contentResult && contentResult.data) {
                        if (typeof contentResult.data === 'object' && contentResult.data.content) {
                            fullContent = contentResult.data.content;
                        } else if (typeof contentResult.data === 'string') {
                            fullContent = contentResult.data;
                        }
                    }
                } catch (error) {
                    loger.warn(`Failed to get task content: ${error.message}`);
                }

                // 将 HTML 内容转换为 Markdown
                fullContent = htmlToMarkdown(fullContent);

                const taskDetail = {
                    id: task.id,
                    name: task.name,
                    desc: task.desc || '无描述',
                    dialog_id: task.dialog_id,
                    content: fullContent,
                    status: task.complete_at ? '已完成' : '未完成',
                    complete_at: task.complete_at || '未完成',
                    project_id: task.project_id,
                    project_name: task.project_name,
                    column_id: task.column_id,
                    column_name: task.column_name,
                    parent_id: task.parent_id,
                    start_at: task.start_at || '无开始时间',
                    end_at: task.end_at || '无截止时间',
                    flow_item_id: task.flow_item_id,
                    flow_item_name: task.flow_item_name,
                    visibility: task.visibility === 1 ? '公开' : '指定人员',
                    owners: task.taskUser?.filter(u => u.owner === 1).map(u => ({
                        userid: u.userid,
                        username: u.username || u.nickname || `用户${u.userid}`
                    })) || [],
                    assistants: task.taskUser?.filter(u => u.owner === 0).map(u => ({
                        userid: u.userid,
                        username: u.username || u.nickname || `用户${u.userid}`
                    })) || [],
                    tags: task.taskTag?.map(t => t.name) || [],
                    created_at: task.created_at,
                    updated_at: task.updated_at,
                };

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify(taskDetail, null, 2)
                    }]
                };
            }
        });

        // 标记任务完成
        this.mcp.addTool({
            name: 'complete_task',
            description: '快速标记任务完成（自动使用当前时间）。如需指定完成时间或取消完成，请使用 update_task。注意:主任务必须在所有子任务完成后才能标记完成。',
            parameters: z.object({
                task_id: z.number()
                    .describe('要标记完成的任务ID'),
            }),
            execute: async (params) => {
                const now = new Date().toISOString().slice(0, 19).replace('T', ' ');

                const result = await this.request('POST', 'project/task/update', {
                    task_id: params.task_id,
                    complete_at: now,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            message: '任务已标记为完成',
                            task_id: params.task_id,
                            complete_at: result.data.complete_at,
                        }, null, 2)
                    }]
                };
            }
        });

        // 创建任务
        this.mcp.addTool({
            name: 'create_task',
            description: '在指定项目中创建新任务。必需参数：项目ID、任务名称。可选：负责人、协助人、开始/结束时间、看板列等。',
            parameters: z.object({
                project_id: z.number()
                    .describe('项目ID'),
                name: z.string()
                    .describe('任务名称'),
                content: z.string()
                    .optional()
                    .describe('任务内容描述(Markdown 格式)'),
                owner: z.array(z.number())
                    .optional()
                    .describe('负责人用户ID数组'),
                assist: z.array(z.number())
                    .optional()
                    .describe('协助人员用户ID数组'),
                column_id: z.number()
                    .optional()
                    .describe('列ID(看板列)'),
                start_at: z.string()
                    .optional()
                    .describe('开始时间，格式: YYYY-MM-DD HH:mm:ss'),
                end_at: z.string()
                    .optional()
                    .describe('结束时间，格式: YYYY-MM-DD HH:mm:ss'),
            }),
            execute: async (params) => {
                const requestData = {
                    project_id: params.project_id,
                    name: params.name,
                };

                // 添加可选参数，将 Markdown 转换为 HTML
                if (params.content) requestData.content = markdownToHtml(params.content);
                if (params.owner) requestData.owner = params.owner;
                if (params.assist) requestData.assist = params.assist;
                if (params.column_id) requestData.column_id = params.column_id;
                if (params.start_at) requestData.start_at = params.start_at;
                if (params.end_at) requestData.end_at = params.end_at;

                const result = await this.request('POST', 'project/task/add', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const task = result.data;

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            message: '任务创建成功',
                            task: {
                                id: task.id,
                                name: task.name,
                                project_id: task.project_id,
                                column_id: task.column_id,
                                created_at: task.created_at,
                            }
                        }, null, 2)
                    }]
                };
            }
        });

        // 更新任务
        this.mcp.addTool({
            name: 'update_task',
            description: '更新任务的任意属性（名称、内容、负责人、协助人、时间、完成状态、看板列等）。只需提供要修改的字段。',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
                name: z.string()
                    .optional()
                    .describe('任务名称'),
                content: z.string()
                    .optional()
                    .describe('任务内容描述(Markdown 格式)'),
                owner: z.array(z.number())
                    .optional()
                    .describe('负责人用户ID数组'),
                assist: z.array(z.number())
                    .optional()
                    .describe('协助人员用户ID数组'),
                column_id: z.number()
                    .optional()
                    .describe('移动到指定列ID'),
                start_at: z.string()
                    .optional()
                    .describe('开始时间，格式: YYYY-MM-DD HH:mm:ss'),
                end_at: z.string()
                    .optional()
                    .describe('结束时间，格式: YYYY-MM-DD HH:mm:ss'),
                complete_at: z.union([z.string(), z.boolean()])
                    .optional()
                    .describe('完成时间。传时间字符串标记完成，传false标记未完成'),
            }),
            execute: async (params) => {
                const requestData = {
                    task_id: params.task_id,
                };

                // 添加要更新的字段，将 Markdown 转换为 HTML
                if (params.name !== undefined) requestData.name = params.name;
                if (params.content !== undefined) requestData.content = markdownToHtml(params.content);
                if (params.owner !== undefined) requestData.owner = params.owner;
                if (params.assist !== undefined) requestData.assist = params.assist;
                if (params.column_id !== undefined) requestData.column_id = params.column_id;
                if (params.start_at !== undefined) requestData.start_at = params.start_at;
                if (params.end_at !== undefined) requestData.end_at = params.end_at;
                if (params.complete_at !== undefined) requestData.complete_at = params.complete_at;

                const result = await this.request('POST', 'project/task/update', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const task = result.data;

                // 构建更新摘要
                const updates = [];
                if (params.name !== undefined) updates.push('名称');
                if (params.content !== undefined) updates.push('内容');
                if (params.owner !== undefined) updates.push('负责人');
                if (params.assist !== undefined) updates.push('协助人员');
                if (params.column_id !== undefined) updates.push('列');
                if (params.start_at !== undefined || params.end_at !== undefined) updates.push('时间');
                if (params.complete_at !== undefined) updates.push('完成状态');

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            message: `任务已更新: ${updates.join('、')}`,
                            task: {
                                id: task.id,
                                name: task.name,
                                status: task.complete_at ? '已完成' : '未完成',
                                complete_at: task.complete_at || '未完成',
                                updated_at: task.updated_at,
                            }
                        }, null, 2)
                    }]
                };
            }
        });

        // 创建子任务
        this.mcp.addTool({
            name: 'create_sub_task',
            description: '为指定主任务新增子任务，自动继承主任务所属项目与看板列配置。',
            parameters: z.object({
                task_id: z.number()
                    .describe('主任务ID'),
                name: z.string()
                    .min(1)
                    .describe('子任务名称'),
            }),
            execute: async (params) => {
                const result = await this.request('GET', 'project/task/addsub', {
                    task_id: params.task_id,
                    name: params.name,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const subTask = result.data || {};

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            sub_task: {
                                id: subTask.id,
                                name: subTask.name,
                                project_id: subTask.project_id,
                                parent_id: subTask.parent_id,
                                column_id: subTask.column_id,
                                start_at: subTask.start_at,
                                end_at: subTask.end_at,
                                created_at: subTask.created_at,
                            }
                        }, null, 2)
                    }]
                };
            }
        });

        // 获取任务附件
        this.mcp.addTool({
            name: 'get_task_files',
            description: '获取指定任务的附件列表，包含文件名称、大小、下载地址等信息。',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
            }),
            execute: async (params) => {
                const result = await this.request('GET', 'project/task/files', {
                    task_id: params.task_id,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const files = Array.isArray(result.data) ? result.data : [];

                const normalized = files.map(file => ({
                    id: file.id,
                    name: file.name,
                    ext: file.ext,
                    size: file.size,
                    url: file.path,
                    thumb: file.thumb,
                    userid: file.userid,
                    download_count: file.download,
                    created_at: file.created_at,
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            task_id: params.task_id,
                            files: normalized,
                        }, null, 2)
                    }]
                };
            }
        });

        // 删除或还原任务
        this.mcp.addTool({
            name: 'delete_task',
            description: '删除或还原任务。默认执行删除，可通过 action=recovery 将任务从回收站恢复。',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
                action: z.enum(['delete', 'recovery'])
                    .optional()
                    .describe('操作类型：delete(默认) 删除，recovery 还原'),
            }),
            execute: async (params) => {
                const action = params.action || 'delete';

                const result = await this.request('GET', 'project/task/remove', {
                    task_id: params.task_id,
                    type: action,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            action: action,
                            task_id: params.task_id,
                            data: result.data,
                        }, null, 2)
                    }]
                };
            }
        });

        // 获取项目列表
        this.mcp.addTool({
            name: 'list_projects',
            description: '获取当前用户可访问的项目列表，支持按归档状态筛选、搜索项目名称。',
            parameters: z.object({
                archived: z.enum(['no', 'yes', 'all'])
                    .optional()
                    .describe('归档状态: no(未归档), yes(已归档), all(全部)，默认no'),
                search: z.string()
                    .optional()
                    .describe('搜索关键词(可搜索项目名称)'),
                page: z.number()
                    .optional()
                    .describe('页码，默认1'),
                pagesize: z.number()
                    .optional()
                    .describe('每页数量，默认20'),
            }),
            execute: async (params) => {
                const requestData = {
                    archived: params.archived || 'no',
                    page: params.page || 1,
                    pagesize: params.pagesize || 20,
                };

                // 添加搜索参数
                if (params.search) {
                    requestData.keys = {
                        name: params.search
                    };
                }

                const result = await this.request('GET', 'project/lists', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const projects = result.data.data.map(project => ({
                    id: project.id,
                    name: project.name,
                    desc: project.desc || '无描述',
                    dialog_id: project.dialog_id,
                    archived_at: project.archived_at || '未归档',
                    owner_userid: project.owner_userid || 0,
                    created_at: project.created_at,
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            total: result.data.total,
                            page: result.data.current_page,
                            pagesize: result.data.per_page,
                            projects: projects,
                        }, null, 2)
                    }]
                };
            }
        });

        // 获取项目详情
        this.mcp.addTool({
            name: 'get_project',
            description: '获取指定项目的完整详细信息，包括项目描述、所有看板列、成员列表及权限等。比 list_projects 返回更详细的信息。',
            parameters: z.object({
                project_id: z.number()
                    .describe('项目ID'),
            }),
            execute: async (params) => {
                const result = await this.request('GET', 'project/one', {
                    project_id: params.project_id,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const project = result.data;

                const projectDetail = {
                    id: project.id,
                    name: project.name,
                    desc: project.desc || '无描述',
                    dialog_id: project.dialog_id,
                    archived_at: project.archived_at || '未归档',
                    owner_userid: project.owner_userid,
                    owner_username: project.owner_username,
                    columns: project.projectColumn?.map(col => ({
                        id: col.id,
                        name: col.name,
                        sort: col.sort,
                    })) || [],
                    members: project.projectUser?.map(user => ({
                        userid: user.userid,
                        username: user.username,
                        owner: user.owner === 1 ? '管理员' : '成员',
                    })) || [],
                    created_at: project.created_at,
                    updated_at: project.updated_at,
                };

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify(projectDetail, null, 2)
                    }]
                };
            }
        });

        // 创建项目
        this.mcp.addTool({
            name: 'create_project',
            description: '创建新项目，可选设置项目描述、初始化列及流程状态。',
            parameters: z.object({
                name: z.string()
                    .min(2)
                    .describe('项目名称，至少2个字符'),
                desc: z.string()
                    .optional()
                    .describe('项目描述'),
                columns: z.union([z.string(), z.array(z.string())])
                    .optional()
                    .describe('初始化列名称，字符串使用逗号分隔，也可直接传字符串数组'),
                flow: z.enum(['open', 'close'])
                    .optional()
                    .describe('是否开启流程，open/close，默认close'),
                personal: z.boolean()
                    .optional()
                    .describe('是否创建个人项目，仅支持创建一个个人项目'),
            }),
            execute: async (params) => {
                const requestData = {
                    name: params.name,
                };

                if (params.desc !== undefined) {
                    requestData.desc = params.desc;
                }
                if (params.columns !== undefined) {
                    requestData.columns = Array.isArray(params.columns) ? params.columns.join(',') : params.columns;
                }
                if (params.flow !== undefined) {
                    requestData.flow = params.flow;
                }
                if (params.personal !== undefined) {
                    requestData.personal = params.personal ? 1 : 0;
                }

                const result = await this.request('GET', 'project/add', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const project = result.data || {};

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            project: {
                                id: project.id,
                                name: project.name,
                                desc: project.desc || '',
                                columns: project.projectColumn || [],
                                created_at: project.created_at,
                            }
                        }, null, 2)
                    }]
                };
            }
        });

        // 更新项目
        this.mcp.addTool({
            name: 'update_project',
            description: '修改项目信息（名称、描述、归档策略等）。若未传 name 将自动沿用项目当前名称。',
            parameters: z.object({
                project_id: z.number()
                    .describe('项目ID'),
                name: z.string()
                    .optional()
                    .describe('项目名称'),
                desc: z.string()
                    .optional()
                    .describe('项目描述'),
                archive_method: z.string()
                    .optional()
                    .describe('归档方式'),
                archive_days: z.number()
                    .optional()
                    .describe('自动归档天数'),
            }),
            execute: async (params) => {
                const requestData = {
                    project_id: params.project_id,
                };

                if (params.name && params.name.trim().length > 0) {
                    requestData.name = params.name;
                } else {
                    const projectResult = await this.request('GET', 'project/one', {
                        project_id: params.project_id,
                    });

                    if (projectResult.error) {
                        throw new Error(projectResult.error);
                    }

                    const currentName = projectResult.data?.name;
                    if (!currentName) {
                        throw new Error('无法获取项目名称，请手动提供 name 参数');
                    }
                    requestData.name = currentName;
                }

                if (params.desc !== undefined) {
                    requestData.desc = params.desc;
                }
                if (params.archive_method !== undefined) {
                    requestData.archive_method = params.archive_method;
                }
                if (params.archive_days !== undefined) {
                    requestData.archive_days = params.archive_days;
                }

                const result = await this.request('GET', 'project/update', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const project = result.data || {};

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            project: {
                                id: project.id,
                                name: project.name,
                                desc: project.desc || '',
                                archived_at: project.archived_at || null,
                                archive_method: project.archive_method ?? requestData.archive_method ?? null,
                                archive_days: project.archive_days ?? requestData.archive_days ?? null,
                                updated_at: project.updated_at,
                            }
                        }, null, 2)
                    }]
                };
            }
        });

        // 发送消息给用户
        this.mcp.addTool({
            name: 'send_message_to_user',
            description: '给指定用户发送私信，可选择 Markdown 或 HTML 格式，并支持静默发送。',
            parameters: z.object({
                user_id: z.number()
                    .describe('接收方用户ID'),
                text: z.string()
                    .min(1)
                    .describe('消息内容'),
                text_type: z.enum(['md', 'html'])
                    .optional()
                    .describe('消息类型，默认md，可选md/html'),
                silence: z.boolean()
                    .optional()
                    .describe('是否静默发送（不触发提醒）'),
            }),
            execute: async (params) => {
                const dialogResult = await this.request('GET', 'dialog/open/user', {
                    userid: params.user_id,
                });

                if (dialogResult.error) {
                    throw new Error(dialogResult.error);
                }

                const dialogData = dialogResult.data || {};
                const dialogId = dialogData.id;

                if (!dialogId) {
                    throw new Error('未能获取会话ID，无法发送消息');
                }

                const payload = {
                    dialog_id: dialogId,
                    text: params.text,
                };

                if (params.text_type) {
                    payload.text_type = params.text_type;
                } else {
                    payload.text_type = 'md';
                }
                if (params.silence !== undefined) {
                    payload.silence = params.silence ? 'yes' : 'no';
                }

                const sendResult = await this.request('POST', 'dialog/msg/sendtext', payload);

                if (sendResult.error) {
                    throw new Error(sendResult.error);
                }

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            dialog_id: dialogId,
                            data: sendResult.data,
                        }, null, 2)
                    }]
                };
            }
        });

        // 获取消息列表或搜索消息
        this.mcp.addTool({
            name: 'get_message_list',
            description: '两种模式：1）获取指定对话的消息列表（需提供 dialog_id），支持按类型筛选、分页加载；2）按关键词搜索消息（提供 keyword），可在单个对话或全局搜索。',
            parameters: z.object({
                dialog_id: z.number()
                    .optional()
                    .describe('对话ID，获取消息列表时必填'),
                keyword: z.string()
                    .optional()
                    .describe('搜索关键词，提供时执行消息搜索'),
                msg_id: z.number()
                    .optional()
                    .describe('围绕某条消息加载相关内容'),
                position_id: z.number()
                    .optional()
                    .describe('以position_id为中心加载消息'),
                prev_id: z.number()
                    .optional()
                    .describe('获取此消息之前的历史'),
                next_id: z.number()
                    .optional()
                    .describe('获取此消息之后的新消息'),
                msg_type: z.enum(['tag', 'todo', 'link', 'text', 'image', 'file', 'record', 'meeting'])
                    .optional()
                    .describe('按消息类型筛选'),
                take: z.number()
                    .optional()
                    .describe('获取条数，列表模式最大100，搜索模式受接口限制'),
            }),
            execute: async (params) => {
                const keyword = params.keyword?.trim();

                if (keyword) {
                    const searchPayload = {
                        key: keyword,
                    };
                    if (params.dialog_id) {
                        searchPayload.dialog_id = params.dialog_id;
                    }
                    if (params.take && params.take > 0) {
                        const takeValue = params.take;
                        searchPayload.take = params.dialog_id
                            ? Math.min(takeValue, 200)
                            : Math.min(takeValue, 50);
                    }

                    const searchResult = await this.request('GET', 'dialog/msg/search', searchPayload);

                    if (searchResult.error) {
                        throw new Error(searchResult.error);
                    }

                    return {
                        content: [{
                            type: 'text',
                            text: JSON.stringify({
                                mode: params.dialog_id ? 'position_search' : 'global_search',
                                keyword: keyword,
                                dialog_id: params.dialog_id || null,
                                data: searchResult.data,
                            }, null, 2)
                        }]
                    };
                }

                if (!params.dialog_id) {
                    throw new Error('请提供 dialog_id 以获取消息列表，或提供 keyword 执行搜索');
                }

                const requestData = {
                    dialog_id: params.dialog_id,
                };

                if (params.msg_id !== undefined) requestData.msg_id = params.msg_id;
                if (params.position_id !== undefined) requestData.position_id = params.position_id;
                if (params.prev_id !== undefined) requestData.prev_id = params.prev_id;
                if (params.next_id !== undefined) requestData.next_id = params.next_id;
                if (params.msg_type !== undefined) requestData.msg_type = params.msg_type;
                if (params.take !== undefined) {
                    const takeValue = params.take > 0 ? params.take : 1;
                    requestData.take = Math.min(takeValue, 100);
                }

                const result = await this.request('GET', 'dialog/msg/list', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const data = result.data || {};
                const messages = Array.isArray(data.list) ? data.list : [];

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            dialog_id: params.dialog_id,
                            count: messages.length,
                            time: data.time,
                            dialog: data.dialog,
                            top: data.top,
                            todo: data.todo,
                            messages: messages,
                        }, null, 2)
                    }]
                };
            }
        });

        // 工作报告：获取我接收的汇报列表
        this.mcp.addTool({
            name: 'list_received_reports',
            description: '获取我接收的工作汇报列表，支持按类型、已读状态、部门、时间筛选和搜索。适用于管理者查看团队成员提交的工作汇报。',
            parameters: z.object({
                search: z.string()
                    .optional()
                    .describe('搜索关键词（可搜索标题、汇报人邮箱或用户ID）'),
                type: z.enum(['weekly', 'daily', 'all'])
                    .optional()
                    .describe('汇报类型: weekly(周报), daily(日报), all(全部)，默认all'),
                status: z.enum(['read', 'unread', 'all'])
                    .optional()
                    .describe('已读状态: read(已读), unread(未读), all(全部)，默认all'),
                department_id: z.number()
                    .optional()
                    .describe('部门ID，筛选指定部门的汇报'),
                created_at_start: z.string()
                    .optional()
                    .describe('开始时间，格式: YYYY-MM-DD'),
                created_at_end: z.string()
                    .optional()
                    .describe('结束时间，格式: YYYY-MM-DD'),
                page: z.number()
                    .optional()
                    .describe('页码，默认1'),
                pagesize: z.number()
                    .optional()
                    .describe('每页数量，默认20，最大50'),
            }),
            execute: async (params) => {
                const page = params.page && params.page > 0 ? params.page : 1;
                const pagesize = params.pagesize && params.pagesize > 0 ? Math.min(params.pagesize, 50) : 20;

                const keys = {};
                if (params.search) {
                    keys.key = params.search;
                }
                if (params.type && params.type !== 'all') {
                    keys.type = params.type;
                }
                if (params.status && params.status !== 'all') {
                    keys.status = params.status;
                }
                if (params.department_id !== undefined) {
                    keys.department_id = params.department_id;
                }
                if (params.created_at_start || params.created_at_end) {
                    const dateRange = [];
                    if (params.created_at_start) {
                        dateRange.push(new Date(params.created_at_start).getTime());
                    } else {
                        dateRange.push(0);
                    }
                    if (params.created_at_end) {
                        dateRange.push(new Date(params.created_at_end).getTime());
                    } else {
                        dateRange.push(0);
                    }
                    keys.created_at = dateRange;
                }

                const requestData = {
                    page,
                    pagesize,
                };
                if (Object.keys(keys).length > 0) {
                    requestData.keys = keys;
                }

                const result = await this.request('GET', 'report/receive', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const data = result.data || {};
                const reports = Array.isArray(data.data) ? data.data : [];

                const simplified = reports.map(report => {
                    const myReceive = Array.isArray(report.receives_user)
                        ? report.receives_user.find(u => u.pivot && u.pivot.userid)
                        : null;

                    return {
                        id: report.id,
                        title: report.title,
                        type: report.type === 'daily' ? '日报' : '周报',
                        sender_id: report.userid,
                        sender_name: report.user ? (report.user.nickname || report.user.email) : '',
                        is_read: myReceive && myReceive.pivot ? (myReceive.pivot.read === 1) : false,
                        receive_at: report.receive_at || report.created_at,
                        created_at: report.created_at,
                    };
                });

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            total: data.total || reports.length,
                            page: data.current_page || page,
                            pagesize: data.per_page || pagesize,
                            reports: simplified,
                        }, null, 2)
                    }]
                };
            }
        });

        // 工作报告：获取汇报详情
        this.mcp.addTool({
            name: 'get_report_detail',
            description: '获取指定工作汇报的详细信息，包括完整内容、汇报人、接收人列表、AI分析等。返回的 content 字段为 Markdown 格式。支持通过报告ID或分享码访问。',
            parameters: z.object({
                report_id: z.number()
                    .optional()
                    .describe('报告ID'),
                share_code: z.string()
                    .optional()
                    .describe('报告分享码'),
            }),
            execute: async (params) => {
                if (!params.report_id && !params.share_code) {
                    throw new Error('必须提供 report_id 或 share_code 参数之一');
                }

                const requestData = {};
                if (params.report_id) {
                    requestData.id = params.report_id;
                } else if (params.share_code) {
                    requestData.code = params.share_code;
                }

                const result = await this.request('GET', 'report/detail', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const report = result.data;

                // 将 HTML 内容转换为 Markdown
                const markdownContent = htmlToMarkdown(report.content || '');

                const reportDetail = {
                    id: report.id,
                    title: report.title,
                    type: report.type === 'daily' ? '日报' : '周报',
                    type_value: report.type_val || report.type,
                    content: markdownContent,
                    sender_id: report.userid,
                    sender_name: report.user ? (report.user.nickname || report.user.email) : '',
                    receivers: Array.isArray(report.receives_user)
                        ? report.receives_user.map(u => ({
                            userid: u.userid,
                            nickname: u.nickname || u.email,
                            is_read: u.pivot ? (u.pivot.read === 1) : false,
                        }))
                        : [],
                    ai_analysis: report.ai_analysis || null,
                    created_at: report.created_at,
                    updated_at: report.updated_at,
                };

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify(reportDetail, null, 2)
                    }]
                };
            }
        });

        // 工作报告：生成汇报模板
        this.mcp.addTool({
            name: 'generate_report_template',
            description: '基于用户的任务完成情况自动生成工作汇报模板，包括已完成工作、未完成工作等内容。支持生成当前周期或历史周期的汇报。返回的 content 字段为 Markdown 格式。',
            parameters: z.object({
                type: z.enum(['weekly', 'daily'])
                    .describe('汇报类型: weekly(周报), daily(日报)'),
                offset: z.number()
                    .optional()
                    .describe('时间偏移量，0表示当前周期，-1表示上一周期，-2表示上上周期，以此类推。默认0'),
            }),
            execute: async (params) => {
                const offset = params.offset !== undefined ? Math.abs(params.offset) : 0;

                const result = await this.request('GET', 'report/template', {
                    type: params.type,
                    offset: offset,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const template = result.data;

                // 将 HTML 内容转换为 Markdown
                const markdownContent = htmlToMarkdown(template.content || '');

                const templateData = {
                    sign: template.sign,
                    title: template.title,
                    content: markdownContent,
                    existing_report_id: template.id || null,
                    message: template.id
                        ? '该时间周期已有报告，如需修改请使用 update_report 或在界面中编辑'
                        : '模板已生成，可以直接使用或编辑 content 字段，然后使用 create_report 提交',
                };

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify(templateData, null, 2)
                    }]
                };
            }
        });

        // 工作报告：创建汇报
        this.mcp.addTool({
            name: 'create_report',
            description: '创建并提交工作汇报。通常先使用 generate_report_template 生成模板，然后使用此工具提交。',
            parameters: z.object({
                type: z.enum(['weekly', 'daily'])
                    .describe('汇报类型: weekly(周报), daily(日报)'),
                title: z.string()
                    .describe('报告标题'),
                content: z.string()
                    .describe('报告内容（Markdown 格式），通常从 generate_report_template 返回的 content 字段获取'),
                receive: z.array(z.number())
                    .optional()
                    .describe('接收人用户ID数组，不包含自己'),
                sign: z.string()
                    .optional()
                    .describe('唯一签名，从 generate_report_template 返回的 sign 字段获取'),
                offset: z.number()
                    .optional()
                    .describe('时间偏移量，应与生成模板时保持一致。默认0'),
            }),
            execute: async (params) => {
                const requestData = {
                    id: 0,
                    title: params.title,
                    type: params.type,
                    content: markdownToHtml(params.content),
                    offset: params.offset !== undefined ? Math.abs(params.offset) : 0,
                };

                if (params.receive && Array.isArray(params.receive)) {
                    requestData.receive = params.receive;
                }
                if (params.sign) {
                    requestData.sign = params.sign;
                }

                const result = await this.request('POST', 'report/store', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const report = result.data || {};

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            message: '工作汇报创建成功',
                            report: {
                                id: report.id,
                                title: report.title,
                                type: report.type === 'daily' ? '日报' : '周报',
                                created_at: report.created_at,
                            }
                        }, null, 2)
                    }]
                };
            }
        });

        // 工作报告：获取我发送的汇报列表
        this.mcp.addTool({
            name: 'list_my_reports',
            description: '获取我发送的工作汇报列表，支持按类型、时间筛选和搜索。适用于查看自己的历史汇报。',
            parameters: z.object({
                search: z.string()
                    .optional()
                    .describe('搜索关键词（可搜索标题）'),
                type: z.enum(['weekly', 'daily', 'all'])
                    .optional()
                    .describe('汇报类型: weekly(周报), daily(日报), all(全部)，默认all'),
                created_at_start: z.string()
                    .optional()
                    .describe('开始时间，格式: YYYY-MM-DD'),
                created_at_end: z.string()
                    .optional()
                    .describe('结束时间，格式: YYYY-MM-DD'),
                page: z.number()
                    .optional()
                    .describe('页码，默认1'),
                pagesize: z.number()
                    .optional()
                    .describe('每页数量，默认20，最大50'),
            }),
            execute: async (params) => {
                const page = params.page && params.page > 0 ? params.page : 1;
                const pagesize = params.pagesize && params.pagesize > 0 ? Math.min(params.pagesize, 50) : 20;

                const keys = {};
                if (params.search) {
                    keys.key = params.search;
                }
                if (params.type && params.type !== 'all') {
                    keys.type = params.type;
                }
                if (params.created_at_start || params.created_at_end) {
                    const dateRange = [];
                    if (params.created_at_start) {
                        dateRange.push(new Date(params.created_at_start).getTime());
                    } else {
                        dateRange.push(0);
                    }
                    if (params.created_at_end) {
                        dateRange.push(new Date(params.created_at_end).getTime());
                    } else {
                        dateRange.push(0);
                    }
                    keys.created_at = dateRange;
                }

                const requestData = {
                    page,
                    pagesize,
                };
                if (Object.keys(keys).length > 0) {
                    requestData.keys = keys;
                }

                const result = await this.request('GET', 'report/my', requestData);

                if (result.error) {
                    throw new Error(result.error);
                }

                const data = result.data || {};
                const reports = Array.isArray(data.data) ? data.data : [];

                const simplified = reports.map(report => ({
                    id: report.id,
                    title: report.title,
                    type: report.type === 'daily' ? '日报' : '周报',
                    receivers: Array.isArray(report.receives) ? report.receives : [],
                    receiver_count: Array.isArray(report.receives) ? report.receives.length : 0,
                    created_at: report.created_at,
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            total: data.total || reports.length,
                            page: data.current_page || page,
                            pagesize: data.per_page || pagesize,
                            reports: simplified,
                        }, null, 2)
                    }]
                };
            }
        });

        // 工作报告：标记已读/未读
        this.mcp.addTool({
            name: 'mark_reports_read',
            description: '批量标记工作汇报为已读或未读状态。支持单个或多个报告的状态管理。',
            parameters: z.object({
                report_ids: z.union([z.number(), z.array(z.number())])
                    .describe('报告ID或ID数组，最多100个'),
                action: z.enum(['read', 'unread'])
                    .optional()
                    .describe('操作类型: read(标记已读), unread(标记未读)，默认read'),
            }),
            execute: async (params) => {
                const action = params.action || 'read';
                const ids = Array.isArray(params.report_ids) ? params.report_ids : [params.report_ids];

                if (ids.length > 100) {
                    throw new Error('最多只能操作100条数据');
                }

                const result = await this.request('GET', 'report/mark', {
                    id: ids,
                    action: action,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            message: `已将 ${ids.length} 个报告标记为${action === 'read' ? '已读' : '未读'}`,
                            action: action,
                            affected_count: ids.length,
                            report_ids: ids,
                        }, null, 2)
                    }]
                };
            }
        });

        // 文件管理：获取文件列表
        this.mcp.addTool({
            name: 'list_files',
            description: '获取用户文件列表（个人文件系统），支持按父级文件夹筛选。pid=0或不传表示获取根目录，pid>0获取指定文件夹下的内容。可以浏览文件夹结构，查看所有文件和子文件夹。',
            parameters: z.object({
                pid: z.number()
                    .optional()
                    .describe('父级文件夹ID，0或不传表示根目录'),
            }),
            execute: async (params) => {
                const pid = params.pid !== undefined ? params.pid : 0;

                const result = await this.request('GET', 'file/lists', {
                    pid: pid,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const files = Array.isArray(result.data) ? result.data : [];

                const simplified = files.map(file => ({
                    id: file.id,
                    name: file.name,
                    type: file.type,
                    ext: file.ext || '',
                    size: file.size || 0,
                    pid: file.pid,
                    userid: file.userid,
                    created_id: file.created_id,
                    share: file.share ? true : false,
                    created_at: file.created_at,
                    updated_at: file.updated_at,
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            pid: pid,
                            total: simplified.length,
                            files: simplified,
                        }, null, 2)
                    }]
                };
            }
        });

        // 文件管理：搜索文件
        this.mcp.addTool({
            name: 'search_files',
            description: '按关键词搜索用户文件系统中的文件，支持搜索文件名称、文件ID或分享链接。搜索范围包括：自己创建的文件和共享给自己的文件。',
            parameters: z.object({
                keyword: z.string()
                    .min(1)
                    .describe('搜索关键词，支持文件名称、文件ID或分享链接'),
                take: z.number()
                    .optional()
                    .describe('返回数量，默认50，最大100'),
            }),
            execute: async (params) => {
                const take = params.take && params.take > 0 ? Math.min(params.take, 100) : 50;

                const result = await this.request('GET', 'file/search', {
                    key: params.keyword,
                    take: take,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const files = Array.isArray(result.data) ? result.data : [];

                const simplified = files.map(file => ({
                    id: file.id,
                    name: file.name,
                    type: file.type,
                    ext: file.ext || '',
                    size: file.size || 0,
                    pid: file.pid,
                    userid: file.userid,
                    created_id: file.created_id,
                    share: file.share ? true : false,
                    created_at: file.created_at,
                    updated_at: file.updated_at,
                }));

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            keyword: params.keyword,
                            total: simplified.length,
                            files: simplified,
                        }, null, 2)
                    }]
                };
            }
        });

        // 文件管理：获取文件详情
        this.mcp.addTool({
            name: 'get_file_detail',
            description: '获取指定文件的详细信息，包括类型、大小、共享状态、创建者等。支持通过文件ID或分享码访问。返回的 content_url 可以配合 WebFetch 工具读取文件内容进行分析。',
            parameters: z.object({
                file_id: z.union([z.number(), z.string()])
                    .describe('文件ID（数字）或分享码（字符串）'),
            }),
            execute: async (params) => {
                const result = await this.request('GET', 'file/one', {
                    id: params.file_id,
                    with_url: 'yes',
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const file = result.data;

                const fileDetail = {
                    id: file.id,
                    name: file.name,
                    type: file.type,
                    ext: file.ext || '',
                    size: file.size || 0,
                    pid: file.pid,
                    userid: file.userid,
                    created_id: file.created_id,
                    share: file.share ? true : false,
                    content_url: file.content_url || null,
                    created_at: file.created_at,
                    updated_at: file.updated_at,
                };

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify(fileDetail, null, 2)
                    }]
                };
            }
        });
    }

    // 启动 MCP 服务器
    async start(port = 22224) {
        try {
            await this.readyPromise;
            await this.mcp.start({
                transportType: 'httpStream',
                httpStream: {
                    port: port
                }
            });
        } catch (error) {
            throw error;
        }
    }

    // 停止服务器
    async stop() {
        await this.readyPromise;
        if (this.mcp && typeof this.mcp.stop === 'function') {
            await this.mcp.stop();
        }
    }
}

/**
 * 启动 MCP 服务器
 */
function startMCPServer(mainWindow, mcpPort) {
    if (mcpServer) {
        loger.info('MCP server already running');
        return;
    }

    mcpServer = new DooTaskMCP(mainWindow);
    mcpServer.start(mcpPort).then(() => {
        loger.info(`MCP Server started on http://localhost:${mcpPort}/mcp`);
        loger.info(`Legacy SSE endpoint also available at http://localhost:${mcpPort}/sse`);
    }).catch((error) => {
        loger.error('Failed to start MCP server:', error);
        mcpServer = null;
    });
}

/**
 * 停止 MCP 服务器
 */
function stopMCPServer() {
    if (!mcpServer) {
        loger.info('MCP server is not running');
        return;
    }

    mcpServer.stop().then(() => {
        loger.info('MCP server stopped');
    }).catch((error) => {
        loger.error('Failed to stop MCP server:', error);
    }).finally(() => {
        mcpServer = null;
    });
}

module.exports = {
    DooTaskMCP,
    startMCPServer,
    stopMCPServer,
};
