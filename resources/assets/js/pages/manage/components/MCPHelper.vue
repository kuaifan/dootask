<template>
    <Modal v-model="mcpHelperShow" :title="$L('桌面 MCP 服务器')" :mask-closable="false" width="700">
        <div class="mcp-helper-content">
            <Alert type="success" show-icon>
                {{ $L('MCP 服务器已启动成功！') }}
                <span slot="desc">
                    {{ $L('服务地址') }}: <code>http://localhost:22224/sse</code>
                </span>
            </Alert>

            <div class="mcp-section">
                <h3><span class="emoji-original">🔗</span> {{ $L('接入配置') }}</h3>
                <p>{{ $L('以接入 Claude 为例，在配置文件中添加以下配置') }}:</p>
                <div class="mcp-code-block">
                    <pre ref="mcpConfig">{{ mcpConfig }}</pre>
                    <Button size="small" class="mcp-copy-btn" @click="copyMcpConfig">{{ $L('复制配置') }}</Button>
                </div>
            </div>

            <div class="mcp-section">
                <h3><span class="emoji-original">💡</span> {{ $L('使用示例') }}</h3>
                <p>{{ $L('配置生效后，即可通过自然语言使用 MCP 服务') }}:</p>
                <ul class="mcp-examples">
                    <li>"{{ $L("查看我未完成的任务") }}"</li>
                    <li>"{{ $L("搜索包含'报告'的任务") }}"</li>
                    <li>"{{ $L("标记任务456为已完成") }}"</li>
                    <li>"{{ $L("在项目1中创建任务：完成用户手册") }}"</li>
                    <li>"{{ $L("把任务789的截止时间改为下周五") }}"</li>
                    <li>"{{ $L("我有哪些项目？") }}"</li>
                </ul>
            </div>
        </div>

        <div slot="footer" class="adaption">
            <Button type="default" @click="onCloseMcp">{{$L('关闭 MCP 服务器')}}</Button>
            <Button type="primary" @click="mcpHelperShow = false">{{ $L('我知道了') }}</Button>
        </div>
    </Modal>
</template>

<style lang="scss" scoped>
.mcp-helper-content {
    .mcp-section {
        margin-top: 20px;

        h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #333;
        }

        p {
            margin-bottom: 10px;
            color: #666;
            line-height: 1.6;
        }

        .mcp-code-block {
            position: relative;
            background: #f5f7fa;
            border: 1px solid #e4e7ed;
            border-radius: 4px;
            padding: 12px;
            margin: 12px 0;

            pre {
                margin: 0;
                font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
                font-size: 13px;
                line-height: 1.6;
                color: #333;
                overflow-x: auto;
            }

            .mcp-copy-btn {
                position: absolute;
                top: 8px;
                right: 8px;
            }
        }

        .mcp-hint {
            font-size: 13px;
            color: #999;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 4px;

            code {
                background: #f5f7fa;
                padding: 2px 6px;
                border-radius: 3px;
                font-size: 12px;
            }
        }

        .mcp-examples {
            margin: 12px 0;
            padding-left: 20px;

            li {
                margin: 8px 0;
                color: #666;
                line-height: 1.6;
                font-size: 14px;

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
        font-size: 13px;
        color: #e96900;
    }
}
</style>

<script>
import { mapState } from 'vuex';

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
            mcpConfig: `{
  "mcpServers": {
    "DooTask": {
      "url": "http://localhost:22224/sse"
    }
  }
}`
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
        }
    },
    methods: {
        copyMcpConfig() {
            this.copyText(this.$refs.mcpConfig.textContent);
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
