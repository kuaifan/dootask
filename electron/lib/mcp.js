/**
 * DooTask MCP Server
 * 
 * DooTask 的 Electron 客户端集成了 Model Context Protocol (MCP) 服务，
 * 允许 AI 助手(如 Claude)直接与 DooTask 任务进行交互。
 * 
 * 提供的工具（共 7 个）:
 * 
 * === 任务管理 ===
 * 1. list_tasks       - 获取任务列表，支持按状态/项目/主任务筛选、搜索、分页
 * 2. get_task         - 获取任务详情，包含完整内容、负责人、协助人员、标签等所有信息
 * 3. complete_task    - 快速标记任务完成
 * 4. create_task      - 创建新任务
 * 5. update_task      - 更新任务，支持修改名称、内容、负责人、时间、状态等所有属性
 * 
 * === 项目管理 ===
 * 6. list_projects    - 获取项目列表，支持按归档状态筛选、搜索
 * 7. get_project      - 获取项目详情，包含列（看板列）、成员等完整信息
 * 
 * 配置方法:
 *  {
 *    "mcpServers": {
 *      "DooTask": {
 *        "url": "http://localhost:22224/sse"
 *      }
 *    }
 *  }
 * 
 * 使用示例:
 * - "查看我未完成的任务"
 * - "搜索包含'报告'的任务"
 * - "显示任务123的详细信息"
 * - "标记任务456为已完成"
 * - "在项目1中创建任务：完成用户手册，负责人100，协助人员101"
 * - "把任务789的截止时间改为下周五，并分配给用户200"
 * - "取消任务234的完成状态"
 * - "我有哪些项目？"
 * - "查看项目5的详情，包括所有列和成员"
 */

const { FastMCP } = require('fastmcp');
const { z } = require('zod');
const loger = require("electron-log");

let mcpServer = null;

class DooTaskMCP {
    constructor(mainWindow) {
        this.mainWindow = mainWindow;
        this.mcp = new FastMCP({
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
        // 1. 获取任务列表
        this.mcp.addTool({
            name: 'list_tasks',
            description: '获取任务列表。可以按状态筛选(已完成/未完成)、搜索任务名称、按时间范围筛选等。',
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

        // 2. 获取任务详情
        this.mcp.addTool({
            name: 'get_task',
            description: '获取指定任务的详细信息,包括任务描述、完整内容、负责人、协助人员、标签、时间等所有信息。',
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

        // 3. 标记任务完成
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

        // 4. 创建任务
        this.mcp.addTool({
            name: 'create_task',
            description: '创建新任务。可以指定任务名称、内容、负责人、时间等信息。',
            parameters: z.object({
                project_id: z.number()
                    .describe('项目ID'),
                name: z.string()
                    .describe('任务名称'),
                content: z.string()
                    .optional()
                    .describe('任务内容描述(支持富文本)'),
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

                // 添加可选参数
                if (params.content) requestData.content = params.content;
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

        // 5. 更新任务（完整版）
        this.mcp.addTool({
            name: 'update_task',
            description: '更新任务信息。可以修改任务名称、内容、负责人、时间、状态等所有属性。',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
                name: z.string()
                    .optional()
                    .describe('任务名称'),
                content: z.string()
                    .optional()
                    .describe('任务内容描述'),
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

                // 添加要更新的字段
                if (params.name !== undefined) requestData.name = params.name;
                if (params.content !== undefined) requestData.content = params.content;
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

        // 6. 获取项目列表
        this.mcp.addTool({
            name: 'list_projects',
            description: '获取项目列表。可以按归档状态筛选、搜索项目名称等。',
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

        // 7. 获取项目详情
        this.mcp.addTool({
            name: 'get_project',
            description: '获取指定项目的详细信息，包括项目的列（看板列）、成员等完整信息。',
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
    }

    // 启动 MCP 服务器
    async start(port = 22224) {
        try {
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
        loger.info(`DooTask MCP Server started on http://localhost:${mcpPort}/sse`);
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
