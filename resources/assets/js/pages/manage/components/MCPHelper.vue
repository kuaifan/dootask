<template>
    <Modal v-model="mcpHelperShow" :title="t('桌面 MCP 服务器', 'Desktop MCP Server')" :mask-closable="false" width="700">
        <div class="mcp-helper-content">
            <Alert type="success" show-icon>
                {{ t('MCP 服务器已启动成功！', 'MCP Server started successfully!') }}
                <span slot="desc">
                    {{ t('服务地址', 'Server URL') }}: <code>{{ mcpServerUrl }}</code>
                </span>
            </Alert>

            <div class="mcp-section">
                <h3><span class="emoji-original">🔗</span> {{ t('接入配置', 'Configuration') }}</h3>
                <p>{{ t('选择你的 AI 工具，复制对应配置', 'Choose your AI tool and copy the configuration') }}:</p>
                <Tabs v-model="configTab" class="mcp-config-tabs">
                    <TabPane label="Claude Code" name="claude-code">
                        <template v-if="configTab === 'claude-code'">
                            <p class="mcp-config-hint">{{ t('在终端运行以下命令', 'Run the following command in terminal') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configClaudeCode">claude mcp add --transport http DooTask {{ mcpServerUrl }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configClaudeCode')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Codex" name="codex">
                        <template v-if="configTab === 'codex'">
                            <p class="mcp-config-hint">{{ t('编辑 TOML 配置文件', 'Edit TOML config file') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configCodex">{{ configCodex }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configCodex')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Cursor" name="cursor">
                        <template v-if="configTab === 'cursor'">
                            <p class="mcp-config-hint">{{ t('编辑配置文件', 'Edit config file') }}: <code>~/.cursor/mcp.json</code></p>
                            <div class="mcp-code-block">
                                <pre ref="configCursor">{{ configCursor }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configCursor')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="VS Code" name="vscode">
                        <template v-if="configTab === 'vscode'">
                            <p class="mcp-config-hint">{{ t('编辑配置文件', 'Edit config file') }}: <code>settings.json</code></p>
                            <div class="mcp-code-block">
                                <pre ref="configVSCode">{{ configVSCode }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configVSCode')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Claude Desktop" name="claude-desktop">
                        <template v-if="configTab === 'claude-desktop'">
                            <div class="mcp-config-hint">
                                <p>{{ t('编辑配置文件', 'Edit config file') }}:</p>
                                <p><code class="mcp-path">macOS: ~/Library/Application Support/Claude/claude_desktop_config.json</code></p>
                                <p><code class="mcp-path">Windows: %APPDATA%\Claude\claude_desktop_config.json</code></p>
                            </div>
                            <div class="mcp-code-block">
                                <pre ref="configClaudeDesktop">{{ configClaudeDesktop }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configClaudeDesktop')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Windsurf" name="windsurf">
                        <template v-if="configTab === 'windsurf'">
                            <p class="mcp-config-hint">{{ t('编辑 MCP 配置文件', 'Edit MCP config file') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configWindsurf">{{ configWindsurf }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configWindsurf')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Kiro" name="kiro">
                        <template v-if="configTab === 'kiro'">
                            <p class="mcp-config-hint">{{ t('通过 Kiro > MCP Servers > Add 添加，或编辑配置文件', 'Add via Kiro > MCP Servers > Add, or edit config file') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configKiro">{{ configKiro }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configKiro')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Trae" name="trae">
                        <template v-if="configTab === 'trae'">
                            <p class="mcp-config-hint">{{ t('手动添加 JSON 配置', 'Manually add JSON configuration') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configTrae">{{ configTrae }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configTrae')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Antigravity" name="antigravity">
                        <template v-if="configTab === 'antigravity'">
                            <p class="mcp-config-hint">{{ t('编辑 MCP 配置文件', 'Edit MCP config file') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configAntigravity">{{ configAntigravity }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configAntigravity')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane label="Opencode" name="opencode">
                        <template v-if="configTab === 'opencode'">
                            <p class="mcp-config-hint">{{ t('编辑配置文件中的 mcp 字段', 'Edit the mcp field in config file') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configOpencode">{{ configOpencode }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configOpencode')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                    <TabPane :label="t('其他', 'Other')" name="other">
                        <template v-if="configTab === 'other'">
                            <p class="mcp-config-hint">{{ t('对于其他支持 MCP 的工具，只需在配置中添加以下服务地址即可', 'For other MCP-compatible tools, simply add the following server URL to your configuration') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configOther">{{ mcpServerUrl }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configOther')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                            <p class="mcp-config-hint mcp-config-note">{{ t('通用 JSON 配置格式', 'Generic JSON configuration format') }}:</p>
                            <div class="mcp-code-block">
                                <pre ref="configOtherJson">{{ configOtherJson }}</pre>
                                <Button size="small" class="mcp-copy-btn" @click="copyConfig('configOtherJson')">{{ t('复制', 'Copy') }}</Button>
                            </div>
                        </template>
                    </TabPane>
                </Tabs>
            </div>

            <div class="mcp-section">
                <h3><span class="emoji-original">💡</span> {{ t('使用示例', 'Usage Examples') }}</h3>
                <p>{{ t('配置生效后，即可通过自然语言与 AI 对话操作 DooTask', 'After configuration, you can interact with DooTask through natural language') }}:</p>

                <div class="mcp-category">
                    <h4>{{ t('任务管理', 'Task Management') }}</h4>
                    <ul class="mcp-examples">
                        <li>"{{ t('我今天有哪些任务？', 'What tasks do I have today?') }}"</li>
                        <li>"{{ t('本周还有多少未完成的任务？', 'How many uncompleted tasks do I have this week?') }}"</li>
                        <li>"{{ t('帮我把任务"修复登录bug"标记完成', 'Mark the task "Fix login bug" as completed') }}"</li>
                        <li>"{{ t('创建一个任务：设计用户中心页面', 'Create a task: Design user center page') }}"</li>
                        <li>"{{ t('给任务添加子任务：编写单元测试', 'Add a subtask: Write unit tests') }}"</li>
                        <li>"{{ t('把任务截止时间改为下周五', 'Change the task deadline to next Friday') }}"</li>
                    </ul>
                </div>

                <div class="mcp-category">
                    <h4>{{ t('项目查询', 'Project Query') }}</h4>
                    <ul class="mcp-examples">
                        <li>"{{ t('我参与了哪些项目？', 'What projects am I involved in?') }}"</li>
                        <li>"{{ t('电商项目目前进展如何？', 'How is the e-commerce project progressing?') }}"</li>
                        <li>"{{ t('项目里还有多少未完成任务？', 'How many uncompleted tasks are in the project?') }}"</li>
                        <li>"{{ t('项目成员有哪些人？', 'Who are the project members?') }}"</li>
                    </ul>
                </div>

                <div class="mcp-category">
                    <h4>{{ t('工作汇报', 'Work Reports') }}</h4>
                    <ul class="mcp-examples">
                        <li>"{{ t('帮我生成今天的日报', 'Generate my daily report for today') }}"</li>
                        <li>"{{ t('帮我写本周周报', 'Write my weekly report') }}"</li>
                        <li>"{{ t('我上周提交过周报吗？', 'Did I submit a weekly report last week?') }}"</li>
                        <li>"{{ t('张三上个月的周报情况怎么样？', "How was Zhang San's weekly reports last month?") }}"</li>
                    </ul>
                </div>

                <div class="mcp-category">
                    <h4>{{ t('团队协作', 'Team Collaboration') }}</h4>
                    <ul class="mcp-examples">
                        <li>"{{ t('发消息给张三：明天会议改到下午3点', 'Send a message to Zhang San: Tomorrow\'s meeting is rescheduled to 3 PM') }}"</li>
                        <li>"{{ t('搜索关于"接口设计"的聊天记录', 'Search chat history about "API design"') }}"</li>
                        <li>"{{ t('帮我找一下李四的联系方式', 'Help me find Li Si\'s contact info') }}"</li>
                    </ul>
                </div>

                <div class="mcp-category">
                    <h4>{{ t('文件查找', 'File Search') }}</h4>
                    <ul class="mcp-examples">
                        <li>"{{ t('帮我找一下需求文档', 'Help me find the requirements document') }}"</li>
                        <li>"{{ t('我的文件列表有哪些？', 'What files do I have?') }}"</li>
                        <li>"{{ t('这个任务有哪些附件？', 'What attachments does this task have?') }}"</li>
                    </ul>
                </div>
            </div>
        </div>

        <div slot="footer" class="adaption">
            <Button type="default" @click="onCloseMcp">{{ t('关闭 MCP 服务器', 'Stop MCP Server') }}</Button>
            <Button type="primary" @click="mcpHelperShow = false">{{ t('我知道了', 'Got it') }}</Button>
        </div>
    </Modal>
</template>

<style lang="scss" scoped>
.mcp-helper-content {
    .mcp-section {
        margin-top: 20px;

        h3 {
            font-weight: 600;
            margin-bottom: 12px;
            color: #333;
        }

        p {
            margin-bottom: 10px;
            color: #666;
            line-height: 1.6;
        }

        .mcp-config-tabs {
            margin-top: 12px;

            .mcp-config-hint {
                margin: 8px 0;
                color: #666;

                code {
                    background: #f0f0f0;
                    padding: 2px 6px;
                    border-radius: 3px;
                }

                .mcp-path {
                    display: block;
                    margin-top: 4px;
                    color: #999;
                }

                &.mcp-config-note {
                    margin-top: 16px;
                    padding-top: 12px;
                    border-top: 1px dashed #e4e7ed;
                }
            }
        }

        .mcp-code-block {
            position: relative;
            background: #f5f7fa;
            border: 1px solid #e4e7ed;
            border-radius: 4px;
            padding: 12px;
            padding-right: 70px;
            margin: 8px 0;

            code, pre {
                font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
                font-size: 13px;
            }

            pre {
                margin: 0;
                line-height: 1.5;
                color: #333;
                overflow-x: auto;
                white-space: pre-wrap;
                word-break: break-all;
            }

            .mcp-copy-btn {
                position: absolute;
                top: 8px;
                right: 8px;
            }
        }

        .mcp-category {
            margin-top: 16px;

            &:first-child {
                margin-top: 12px;
            }

            h4 {
                font-weight: 600;
                color: #515a6e;
                margin-bottom: 8px;
                padding-left: 8px;
                border-left: 3px solid #2d8cf0;
            }
        }

        .mcp-examples {
            margin: 0;
            padding-left: 20px;

            li {
                margin: 6px 0;
                color: #666;
                line-height: 1.6;

                &:before {
                    content: '•';
                    color: #2d8cf0;
                    font-weight: bold;
                    display: inline-block;
                    width: 1em;
                    margin-left: -1em;
                }
            }
        }
    }

    code {
        background: #f5f7fa;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
        color: #e96900;
    }
}
</style>

<script>
import { mapState } from 'vuex';
import { languageName } from "../../../language";

export default {
    name: "MCPHelper",
    props: {
        value: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            configTab: 'claude-code',
            mcpServerUrl: 'http://localhost:22224/mcp'
        }
    },
    computed: {
        ...mapState(['mcpServerStatus']),

        mcpHelperShow: {
            get() {
                return this.value;
            },
            set(value) {
                this.$emit('input', value);
            }
        },

        // Cursor: ~/.cursor/mcp.json
        configCursor() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        url: this.mcpServerUrl
                    }
                }
            }, null, 2);
        },

        // VS Code: settings.json
        configVSCode() {
            return JSON.stringify({
                mcp: {
                    servers: {
                        DooTask: {
                            type: "http",
                            url: this.mcpServerUrl
                        }
                    }
                }
            }, null, 2);
        },

        // Windsurf: uses serverUrl
        configWindsurf() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        serverUrl: this.mcpServerUrl
                    }
                }
            }, null, 2);
        },

        // Claude Desktop: claude_desktop_config.json
        configClaudeDesktop() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        type: "streamable-http",
                        url: this.mcpServerUrl
                    }
                }
            }, null, 2);
        },

        // OpenAI Codex: TOML format
        configCodex() {
            return `[mcp_servers.DooTask]\nurl = "${this.mcpServerUrl}"`;
        },

        // Kiro: JSON format
        configKiro() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        type: "streamable-http",
                        url: this.mcpServerUrl
                    }
                }
            }, null, 2);
        },

        // Trae: JSON format
        configTrae() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        url: this.mcpServerUrl
                    }
                }
            }, null, 2);
        },

        // Google Antigravity: uses serverUrl
        configAntigravity() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        serverUrl: this.mcpServerUrl
                    }
                }
            }, null, 2);
        },

        // Opencode: uses mcp field
        configOpencode() {
            return JSON.stringify({
                mcp: {
                    DooTask: {
                        type: "remote",
                        url: this.mcpServerUrl,
                        enabled: true
                    }
                }
            }, null, 2);
        },

        // Other: generic JSON format
        configOtherJson() {
            return JSON.stringify({
                mcpServers: {
                    DooTask: {
                        url: this.mcpServerUrl
                    }
                }
            }, null, 2);
        }
    },
    methods: {
        t(zh, en) {
            return languageName.includes('zh') ? zh : en;
        },

        copyConfig(refName) {
            const el = this.$refs[refName];
            if (el) {
                this.copyText(el.textContent);
            }
        },

        onCloseMcp() {
            if (this.mcpServerStatus.running === 'running') {
                this.$store.dispatch('toggleMcpServer');
            }
            this.mcpHelperShow = false;
        }
    }
}
</script>
