/**
 * DooTask MCP Server
 * 
 * DooTask 的 Electron 客户端集成了 Model Context Protocol (MCP) 服务，
 * 允许 AI 助手(如 Claude)直接与 DooTask 任务进行交互。
 * 
 * 提供的工具:
 * 1. list_tasks      - 获取任务列表，支持按状态/项目/主任务筛选、搜索、分页
 * 2. get_task        - 获取任务详情，包含负责人、协助人员、标签等完整信息
 * 3. complete_task   - 标记任务完成，自动记录完成时间
 * 4. uncomplete_task - 取消完成任务，将已完成任务改为未完成
 * 5. get_task_content - 获取任务的富文本描述内容
 * 
 * 配置方法:
 * 添加 DooTask MCP 服务器配置:
 *  {
 *    "mcpServers": {
 *      "DooTask": {
 *        "url": "http://localhost:22224/sse"
 *      }
 *    }
 *  }
 * 
 * 使用示例:
 * - "请帮我查看目前有哪些未完成的任务"
 * - "任务 123 的详细信息是什么?"
 * - "帮我把任务 789 标记为已完成"
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
                            method: '${method}'
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

                // 格式化返回数据，使其更易读
                const tasks = result.data.data.map(task => ({
                    id: task.id,
                    name: task.name,
                    status: task.complete_at ? '已完成' : '未完成',
                    complete_at: task.complete_at || '未完成',
                    project_id: task.project_id,
                    parent_id: task.parent_id,
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
            description: '获取指定任务的详细信息,包括任务描述、负责人、协助人员、标签、时间等完整信息。',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
            }),
            execute: async (params) => {
                const result = await this.request('GET', 'project/task/one', {
                    task_id: params.task_id,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                const task = result.data;

                // 格式化任务详情
                const taskDetail = {
                    id: task.id,
                    name: task.name,
                    desc: task.desc || '无描述',
                    status: task.complete_at ? '已完成' : '未完成',
                    complete_at: task.complete_at || '未完成',
                    project_id: task.project_id,
                    project_name: task.project_name,
                    column_id: task.column_id,
                    column_name: task.column_name,
                    parent_id: task.parent_id,
                    start_at: task.start_at,
                    end_at: task.end_at,
                    flow_item_id: task.flow_item_id,
                    flow_item_name: task.flow_item_name,
                    visibility: task.visibility === 1 ? '公开' : '指定人员',
                    owners: task.taskUser?.filter(u => u.owner === 1).map(u => u.userid) || [],
                    assistants: task.taskUser?.filter(u => u.owner === 0).map(u => u.userid) || [],
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
            description: '将指定任务标记为已完成。注意:主任务必须在所有子任务完成后才能标记完成。',
            parameters: z.object({
                task_id: z.number()
                    .describe('要标记完成的任务ID'),
            }),
            execute: async (params) => {
                // 使用当前时间标记完成
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

        // 4. 取消完成任务
        this.mcp.addTool({
            name: 'uncomplete_task',
            description: '将已完成的任务标记为未完成。',
            parameters: z.object({
                task_id: z.number()
                    .describe('要标记为未完成的任务ID'),
            }),
            execute: async (params) => {
                const result = await this.request('POST', 'project/task/update', {
                    task_id: params.task_id,
                    complete_at: false,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            success: true,
                            message: '任务已标记为未完成',
                            task_id: params.task_id,
                        }, null, 2)
                    }]
                };
            }
        });

        // 5. 获取任务内容详情
        this.mcp.addTool({
            name: 'get_task_content',
            description: '获取任务的详细内容描述(富文本内容)',
            parameters: z.object({
                task_id: z.number()
                    .describe('任务ID'),
            }),
            execute: async (params) => {
                const result = await this.request('GET', 'project/task/content', {
                    task_id: params.task_id,
                });

                if (result.error) {
                    throw new Error(result.error);
                }

                return {
                    content: [{
                        type: 'text',
                        text: JSON.stringify({
                            task_id: params.task_id,
                            content: result.data.content || '无内容',
                        }, null, 2)
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
