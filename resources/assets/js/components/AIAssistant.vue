<template>
    <Modal
        v-model="showModal"
        :title="$L('AI 助手')"
        :mask-closable="false"
        :closable="false"
        :width="shouldCreateNewSession ? '420px' : '600px'"
        class-name="ai-assistant-modal">
        <div class="ai-assistant-content">
            <div
                v-if="responses.length"
                ref="responseContainer"
                class="ai-assistant-output">
                <div
                    v-for="response in responses"
                    :key="response.id || response.localId"
                    class="ai-assistant-output-item">
                    <div class="ai-assistant-output-apply">
                        <template v-if="response.status === 'error'">
                            <span class="ai-assistant-output-error">{{ response.error || $L('发送失败') }}</span>
                        </template>
                        <template v-else-if="response.rawOutput">
                            <Button
                                type="primary"
                                size="small"
                                :loading="response.applyLoading"
                                class="ai-assistant-apply-btn"
                                @click="applyResponse(response)">
                                {{ applyButtonText || $L('应用此内容') }}
                            </Button>
                        </template>
                        <template v-else>
                            <Icon type="ios-sync" class="ai-assistant-output-icon icon-loading"/>
                            <span class="ai-assistant-output-status">{{ $L('生成中...') }}</span>
                        </template>
                    </div>
                    <div class="ai-assistant-output-meta">
                        <span class="ai-assistant-output-model">{{ response.modelLabel || response.model }}</span>
                    </div>
                    <div v-if="response.prompt" class="ai-assistant-output-question">{{ response.prompt }}</div>
                    <DialogMarkdown
                        v-if="response.rawOutput"
                        class="ai-assistant-output-markdown no-dark-content"
                        :text="response.displayOutput || response.rawOutput"/>
                    <div v-else class="ai-assistant-output-placeholder">
                        {{ response.status === 'error' ? (response.error || $L('发送失败')) : $L('等待 AI 回复...') }}
                    </div>
                </div>
            </div>
            <div class="ai-assistant-input">
                <Input
                    v-model="inputValue"
                    type="textarea"
                    :placeholder="inputPlaceholder || $L('请输入你的问题...')"
                    :rows="inputRows || 2"
                    :autosize="inputAutosize || {minRows:2, maxRows:6}"
                    :maxlength="inputMaxlength || 500" />
            </div>
        </div>
        <div slot="footer" class="ai-assistant-footer">
            <div class="ai-assistant-footer-models">
                <Select
                    v-model="inputModel"
                    :placeholder="$L('选择模型')"
                    :loading="modelsLoading"
                    :disabled="modelsLoading || modelGroups.length === 0"
                    :not-found-text="$L('暂无可用模型')"
                    transfer>
                    <OptionGroup
                        v-for="group in modelGroups"
                        :key="group.type"
                        :label="group.label">
                        <Option
                            v-for="option in group.options"
                            :key="option.id"
                            :value="option.id">
                            {{ option.label }}
                        </Option>
                    </OptionGroup>
                </Select>
            </div>
            <div class="ai-assistant-footer-btns">
                <Button type="text" @click="showModal=false">{{ $L('取消') }}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onSubmit">{{ submitButtonText || $L('确定') }}</Button>
            </div>
        </div>
    </Modal>
</template>

<script>
import emitter from "../store/events";
import {SSEClient} from "../utils";
import {AIBotMap, AIModelNames} from "../utils/ai";
import DialogMarkdown from "../pages/manage/components/DialogMarkdown.vue";

export default {
    name: 'AIAssistant',
    components: {DialogMarkdown},
    data() {
        return {
            // 弹窗状态
            showModal: false,
            closing: false,
            loadIng: 0,
            pendingAutoSubmit: false,
            autoSubmitTimer: null,
            applyButtonText: null,
            submitButtonText: null,

            // 输入配置
            inputValue: '',
            inputPlaceholder: null,
            inputRows: null,
            inputAutosize: null,
            inputMaxlength: null,

            // 回调钩子
            applyHook: null,
            beforeSendHook: null,
            renderHook: null,

            // 模型选择
            inputModel: '',
            modelGroups: [],
            modelMap: {},
            modelsFirstLoad: true,
            modelsLoading: false,
            modelCacheKey: 'aiAssistant.model',
            cachedModelId: '',

            // 响应渲染
            responses: [],
            responseSeed: 1,
            maxResponses: 50,
            contextWindowSize: 10,
            activeSSEClients: [],
        }
    },
    mounted() {
        emitter.on('openAIAssistant', this.onOpenAIAssistant);
        this.loadCachedModel();
    },
    beforeDestroy() {
        emitter.off('openAIAssistant', this.onOpenAIAssistant);
        this.clearActiveSSEClients();
        this.clearAutoSubmitTimer();
    },
    computed: {
        selectedModelOption({modelMap, inputModel}) {
            return modelMap[inputModel] || null;
        },
        shouldCreateNewSession() {
            return this.responses.length === 0;
        },
    },
    watch: {
        inputModel(value) {
            this.saveModelCache(value);
        },
    },
    methods: {
        /**
         * 打开助手弹窗并应用参数
         */
        onOpenAIAssistant(params) {
            if (!$A.isJson(params)) {
                params = {};
            }
            this.inputValue = params.value || '';
            this.inputPlaceholder = params.placeholder || null;
            this.inputRows = params.rows || null;
            this.inputAutosize = params.autosize || null;
            this.inputMaxlength = params.maxlength || null;
            this.applyHook = params.onApply || null;
            this.beforeSendHook = params.onBeforeSend || null;
            this.applyButtonText = params.applyButtonText || null;
            this.submitButtonText = params.submitButtonText || null;
            this.renderHook = params.onRender || null;
            this.pendingAutoSubmit = !!params.autoSubmit;
            //
            this.responses = [];
            this.showModal = true;
            this.fetchModelOptions();
            this.clearActiveSSEClients();
            this.clearAutoSubmitTimer();
            this.$nextTick(() => {
                this.scheduleAutoSubmit();
            });
        },

        /**
         * 读取缓存的模型ID
         */
        async loadCachedModel() {
            try {
                this.cachedModelId = await $A.IDBString(this.modelCacheKey) || '';
            } catch (e) {
                this.cachedModelId = '';
            }
        },

        /**
         * 持久化模型选择
         */
        saveModelCache(value) {
            if (!value) {
                return;
            }
            $A.IDBSave(this.modelCacheKey, value);
            this.cachedModelId = value;
        },

        /**
         * 拉取模型配置
         */
        async fetchModelOptions() {
            const needFetch = this.modelsFirstLoad
            if (needFetch) {
                this.modelsFirstLoad = false;
                this.modelsLoading = true;
            }
            try {
                const {data} = await this.$store.dispatch("call", {
                    url: 'assistant/models',
                });
                this.normalizeModelOptions(data);
            } catch (error) {
                if (this.modelGroups.length > 0) {
                    return;
                }
                $A.modalError({
                    content: error?.msg || '获取模型列表失败',
                    onOk: _ => {
                        this.showModal = false;
                    },
                });
            } finally {
                if (needFetch) {
                    this.modelsLoading = false;
                }
            }
        },

        /**
         * 解析模型列表
         */
        normalizeModelOptions(data) {
            const groups = [];
            const map = {};
            if ($A.isJson(data)) {
                Object.keys(data).forEach(key => {
                    const match = key.match(/^(.*?)_models$/);
                    if (!match) {
                        return;
                    }
                    const type = match[1];
                    const raw = data[key];
                    const list = raw ? AIModelNames(raw) : [];
                    if (!list.length) {
                        return;
                    }
                    const defaultModel = data[`${type}_model`] || '';
                    const label = AIBotMap[type] || type;
                    const options = list.slice(0, 5);
                    if (defaultModel) {
                        const defaultOption = list.find(option => option.value === defaultModel);
                        if (defaultOption && !options.some(option => option.value === defaultOption.value)) {
                            options.push(defaultOption);
                        }
                    }
                    const group = {
                        type,
                        label,
                        defaultModel,
                        options: options.map(option => {
                            const id = `${type}:${option.value}`;
                            const item = Object.assign({}, option, {
                                id,
                                type,
                            });
                            map[id] = item;
                            return item;
                        }),
                    };
                    groups.push(group);
                });
            }
            const order = Object.keys(AIBotMap);
            groups.sort((a, b) => {
                const indexA = order.indexOf(a.type);
                const indexB = order.indexOf(b.type);
                if (indexA === -1 && indexB === -1) {
                    return a.label.localeCompare(b.label);
                }
                if (indexA === -1) {
                    return 1;
                }
                if (indexB === -1) {
                    return -1;
                }
                return indexA - indexB;
            });
            this.modelGroups = groups;
            this.modelMap = map;
            this.ensureSelectedModel();
        },

        /**
         * 应用默认或缓存的模型
         */
        ensureSelectedModel() {
            if (this.inputModel && this.modelMap[this.inputModel]) {
                return;
            }
            if (this.cachedModelId && this.modelMap[this.cachedModelId]) {
                this.inputModel = this.cachedModelId;
                return;
            }
            for (const group of this.modelGroups) {
                if (group.defaultModel) {
                    const match = group.options.find(option => option.value === group.defaultModel);
                    if (match) {
                        this.inputModel = match.id;
                        return;
                    }
                }
            }
            const firstGroup = this.modelGroups.find(group => group.options.length > 0);
            if (firstGroup) {
                this.inputModel = firstGroup.options[0].id;
            } else {
                this.inputModel = '';
            }
        },

        /**
         * 发送提问并推入响应
         */
        async onSubmit() {
            if (this.loadIng > 0) {
                return;
            }
            const rawValue = this.inputValue || '';
            const modelOption = this.selectedModelOption;
            if (!modelOption) {
                $A.messageWarning('请选择模型');
                return;
            }

            this.loadIng++;
            let responseEntry = null;
            try {
                const baseContext = this.collectBaseContext(rawValue);
                const context = await this.buildPayloadData(baseContext);

                responseEntry = this.createResponseEntry({
                    modelOption,
                    prompt: rawValue,
                });
                this.scrollResponsesToBottom();

                const streamKey = await this.fetchStreamKey({
                    model_type: modelOption.type,
                    model_name: modelOption.value,
                    context,
                });

                this.inputValue = '';
                this.startStream(streamKey, responseEntry);
            } catch (error) {
                const msg = error?.msg || '发送失败';
                if (responseEntry) {
                    this.markResponseError(responseEntry, msg);
                }
                $A.modalError(msg);
            } finally {
                this.loadIng--;
            }
        },

        /**
         * 构建最终发送的数据
         */
        async buildPayloadData(context) {
            const baseContext = this.normalizeContextEntries(context);
            if (typeof this.beforeSendHook !== 'function') {
                return baseContext;
            }
            try {
                const clonedContext = baseContext.map(entry => entry.slice());
                const result = this.beforeSendHook(clonedContext);
                const resolved = result && typeof result.then === 'function'
                    ? await result
                    : result;
                const prepared = this.normalizeContextEntries(resolved);
                if (prepared.length) {
                    return prepared;
                }
            } catch (e) {
                console.warn('[AIAssistant] onBeforeSend error:', e);
            }
            return baseContext;
        },

        /**
         * 汇总当前会话的基础上下文
         */
        collectBaseContext(prompt) {
            const pushEntry = (context, role, value) => {
                if (typeof value === 'undefined' || value === null) {
                    return;
                }
                const content = String(value).trim();
                if (!content) {
                    return;
                }
                context.push([role, content]);
            };
            const context = [];
            const windowSize = Number(this.contextWindowSize) || 0;
            const recentResponses = windowSize > 0
                ? this.responses.slice(-windowSize)
                : this.responses;
            recentResponses.forEach(item => {
                if (item.prompt) {
                    pushEntry(context, 'human', item.prompt);
                }
                if (item.rawOutput) {
                    pushEntry(context, 'assistant', item.rawOutput);
                }
            });
            if (prompt && String(prompt).trim()) {
                pushEntry(context, 'human', prompt);
            }
            return context;
        },

        /**
         * 归一化上下文结构
         */
        normalizeContextEntries(context) {
            if (!Array.isArray(context)) {
                return [];
            }
            const normalized = [];
            context.forEach(entry => {
                if (!Array.isArray(entry) || entry.length < 2) {
                    return;
                }
                const [role, value] = entry;
                const roleName = typeof role === 'string' ? role.trim() : '';
                const content = typeof value === 'string'
                    ? value.trim()
                    : String(value ?? '').trim();
                if (!roleName || !content) {
                    return;
                }
                const last = normalized[normalized.length - 1];
                const canMergeWithLast = last
                    && last[0] === roleName
                    && typeof last[1] === 'string'
                    && last[1].slice(-4) === '++++';
                if (canMergeWithLast) {
                    const previousContent = last[1].slice(0, -4);
                    last[1] = previousContent ? `${previousContent}\n${content}` : content;
                    return;
                }
                normalized.push([roleName, content]);
            });
            return normalized;
        },

        /**
         * 请求 stream_key
         */
        async fetchStreamKey({model_type, model_name, context}) {
            const payload = {
                model_type,
                model_name,
                context: JSON.stringify(context || []),
            };
            const {data} = await this.$store.dispatch("call", {
                url: 'assistant/auth',
                method: 'post',
                data: payload,
            });
            const streamKey = data?.stream_key || '';
            if (!streamKey) {
                throw new Error('获取 stream_key 失败');
            }
            return streamKey;
        },

        /**
         * 启动 SSE 订阅
         */
        startStream(streamKey, responseEntry) {
            if (!streamKey) {
                throw new Error('获取 stream_key 失败');
            }
            this.clearActiveSSEClients();
            const sse = new SSEClient($A.mainUrl(`ai/invoke/stream/${streamKey}`));
            this.registerSSEClient(sse);
            sse.subscribe(['append', 'replace', 'done'], (type, event) => {
                switch (type) {
                    case 'append':
                    case 'replace':
                        this.handleStreamChunk(responseEntry, type, event);
                        break;
                    case 'done':
                        if (responseEntry && responseEntry.status !== 'error' && responseEntry.rawOutput) {
                            responseEntry.status = 'completed';
                        }
                        this.releaseSSEClient(sse);
                        break;
                }
            });
            return sse;
        },

        /**
         * 处理 SSE 片段
         */
        handleStreamChunk(responseEntry, type, event) {
            if (!responseEntry) {
                return;
            }
            const stickToBottom = this.shouldStickToBottom();
            const payload = this.parseStreamPayload(event);
            const chunk = this.resolveStreamContent(payload);
            if (type === 'replace') {
                responseEntry.rawOutput = chunk;
            } else {
                responseEntry.rawOutput += chunk;
            }
            this.updateResponseDisplayOutput(responseEntry);
            responseEntry.status = 'streaming';
            if (stickToBottom) {
                this.scrollResponsesToBottom();
            }
        },

        /**
         * 解析 SSE 数据
         */
        parseStreamPayload(event) {
            if (!event || !event.data) {
                return {};
            }
            try {
                return JSON.parse(event.data);
            } catch (e) {
                return {};
            }
        },

        /**
         * 获取 SSE 文本
         */
        resolveStreamContent(payload) {
            if (!payload || typeof payload !== 'object') {
                return '';
            }
            if (typeof payload.content === 'string') {
                return payload.content;
            }
            if (typeof payload.c === 'string') {
                return payload.c;
            }
            return '';
        },

        /**
         * 将 SSE 客户端加入活跃列表，方便后续清理
         */
        registerSSEClient(sse) {
            if (!sse) {
                return;
            }
            this.activeSSEClients.push(sse);
        },

        /**
         * 从活跃列表移除 SSE 客户端并执行注销
         */
        releaseSSEClient(sse) {
            const index = this.activeSSEClients.indexOf(sse);
            if (index > -1) {
                this.activeSSEClients.splice(index, 1);
            }
            sse.unsunscribe();
        },

        /**
         * 关闭所有活跃的 SSE 连接
         */
        clearActiveSSEClients() {
            this.activeSSEClients.forEach(sse => {
                try {
                    sse.unsunscribe();
                } catch (e) {
                }
            });
            this.activeSSEClients = [];
        },

        /**
         * 清除自动提交定时器
         */
        clearAutoSubmitTimer() {
            if (this.autoSubmitTimer) {
                clearTimeout(this.autoSubmitTimer);
                this.autoSubmitTimer = null;
            }
        },

        /**
         * 调度自动提交
         */
        scheduleAutoSubmit() {
            if (!this.pendingAutoSubmit) {
                return;
            }
            const attemptSubmit = () => {
                if (!this.pendingAutoSubmit) {
                    return;
                }
                if (this.canAutoSubmit()) {
                    this.pendingAutoSubmit = false;
                    this.clearAutoSubmitTimer();
                    this.onSubmit();
                    return;
                }
                this.autoSubmitTimer = setTimeout(attemptSubmit, 200);
            };
            this.clearAutoSubmitTimer();
            this.autoSubmitTimer = setTimeout(attemptSubmit, 0);
        },

        /**
         * 检查是否可以自动提交
         */
        canAutoSubmit() {
            return !this.modelsLoading
                && !!this.selectedModelOption
                && this.responses.length === 0
                && this.loadIng === 0;
        },

        /**
         * 新建响应卡片
         */
        createResponseEntry({modelOption, prompt}) {
            const entry = {
                localId: this.responseSeed++,
                id: null,
                model: modelOption.value,
                modelLabel: modelOption.label,
                type: modelOption.type,
                prompt: prompt.trim(),
                rawOutput: '',
                displayOutput: '',
                status: 'waiting',
                error: '',
                applyLoading: false,
            };
            this.responses.push(entry);
            if (this.responses.length > this.maxResponses) {
                this.responses.shift();
            }
            return entry;
        },

        /**
         * 标记响应失败
         */
        markResponseError(response, msg) {
            response.status = 'error';
            response.error = msg;
        },

        /**
         * 将AI内容应用到父组件
         */
        applyResponse(response) {
            if (!response || response.applyLoading) {
                return;
            }
            if (!response.rawOutput) {
                $A.messageWarning('暂无可用内容');
                return;
            }
            if (typeof this.applyHook !== 'function') {
                this.closeAssistant();
                return;
            }
            response.applyLoading = true;
            const payload = this.buildResponsePayload(response, true);
            try {
                const result = this.applyHook(payload);
                if (result && typeof result.then === 'function') {
                    result.then(() => {
                        this.closeAssistant();
                    }).catch(error => {
                        $A.modalError(error?.msg || '应用失败');
                    }).finally(() => {
                        response.applyLoading = false;
                    });
                } else {
                    this.closeAssistant();
                    response.applyLoading = false;
                }
            } catch (error) {
                response.applyLoading = false;
                $A.modalError(error?.msg || '应用错误');
            }
        },

        /**
         * 构造发送给外部回调的统一数据结构
         */
        buildResponsePayload(response, removeReasoning = false) {
            if (!response) {
                return {
                    model: '',
                    type: '',
                    prompt: '',
                    rawOutput: '',
                };
            }
            return {
                model: response.model,
                type: response.type,
                prompt: response.prompt,
                rawOutput: removeReasoning ? this.removeReasoningSections(response.rawOutput) : response.rawOutput,
            };
        },

        /**
         * 从回调中剔除 reasoning block
         */
        removeReasoningSections(text) {
            if (typeof text !== 'string') {
                return text;
            }
            return text.replace(/:::\s*reasoning[\s\S]*?:::/gi, '').trim();
        },

        /**
         * 根据 onRender 回调生成展示文本
         */
        updateResponseDisplayOutput(response) {
            if (!response) {
                return;
            }
            if (typeof this.renderHook !== 'function') {
                response.displayOutput = response.rawOutput;
                return;
            }
            try {
                const payload = this.buildResponsePayload(response);
                const result = this.renderHook(payload);
                if (result && typeof result.then === 'function') {
                    console.warn('[AIAssistant] onRender should be synchronous');
                    response.displayOutput = response.rawOutput;
                    return;
                }
                response.displayOutput = typeof result === 'string' ? result : response.rawOutput;
            } catch (e) {
                console.warn('[AIAssistant] onRender error:', e);
                response.displayOutput = response.rawOutput;
            }
        },

        /**
         * 关闭弹窗
         */
        closeAssistant() {
            if (this.closing) {
                return;
            }
            this.closing = true;
            this.pendingAutoSubmit = false;
            this.clearAutoSubmitTimer();
            this.clearActiveSSEClients();
            this.showModal = false;
            this.responses = [];
            setTimeout(() => {
                this.closing = false;
            }, 300);
        },

        /**
         * 滚动结果区域到底部
         */
        scrollResponsesToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.responseContainer;
                if (container && container.scrollHeight) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        /**
         * 判断是否需要保持滚动到底部
         */
        shouldStickToBottom(threshold = 20) {
            const container = this.$refs.responseContainer;
            if (!container) {
                return true;
            }
            const currentBottom = container.scrollTop + container.clientHeight;
            const distance = container.scrollHeight - currentBottom;
            if (Number.isNaN(distance)) {
                return true;
            }
            return distance <= threshold;
        },
    },
}
</script>

<style lang="scss">
.ai-assistant-modal {
    --apply-reasoning-before-bg: #e1e1e1;
    .ivu-modal {
        transition: max-width 0.3s ease;
        .ivu-modal-header {
            padding-left: 30px !important;
            padding-right: 30px !important;
        }
        .ivu-modal-body {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }
        .ivu-modal-footer {
            .ivu-btn {
                min-width: auto !important;
            }
        }
    }

    .ai-assistant-content {
        display: flex;
        flex-direction: column;
        gap: 16px;
        max-height: calc(var(--window-height) - var(--status-bar-height) - var(--navigation-bar-height) - 344px);
        @media (height <= 900px) {
            max-height: calc(var(--window-height) - var(--status-bar-height) - var(--navigation-bar-height) - 214px);
        }

        .ai-assistant-output {
            flex: 1;
            min-height: 0;
            padding: 12px;
            border-radius: 8px;
            background: #f8f9fb;
            border: 1px solid rgba(0, 0, 0, 0.04);
            overflow-y: auto;
        }

        .ai-assistant-output-item + .ai-assistant-output-item {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .ai-assistant-output-apply {
            position: sticky;
            top: 0;
            right: 0;
            z-index: 1;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            height: 24px;
            color: #999;
            gap: 4px;
        }

        .ai-assistant-output-icon {
            font-size: 16px;
            color: #52c41a;
        }

        .ai-assistant-apply-btn {
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ai-assistant-output-status {
            color: #52c41a;
        }

        .ai-assistant-output-error {
            color: #ff4d4f;
        }

        .ai-assistant-output-meta {
            display: flex;
            align-items: center;
            height: 24px;
            margin-top: -24px;
        }

        .ai-assistant-output-model {
            max-width: 50%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 12px;
            font-weight: 600;
            color: #2f54eb;
            background: rgba(47, 84, 235, 0.08);
            border-radius: 4px;
            padding: 2px 8px;
        }

        .ai-assistant-output-question {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 12px;
            color: #666;
            line-height: 1.4;
            margin-top: 8px;
        }

        .ai-assistant-output-placeholder {
            margin-top: 12px;
            font-size: 13px;
            color: #999;
            padding: 8px;
            border-radius: 6px;
            background: rgba(0, 0, 0, 0.02);
        }

        .ai-assistant-output-markdown {
            margin-top: 12px;
            font-size: 13px;

            .apply-reasoning {
                margin: 0 0 12px 0;
                padding: 0 0 0 13px;
                line-height: 26px;
                position: relative;

                &:before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    bottom: 0;
                    width: 2px;
                    background-color: var(--apply-reasoning-before-bg);
                }

                .reasoning-label {
                    margin-bottom: 4px;
                    opacity: 0.9;
                }

                .reasoning-content {
                    opacity: 0.5;
                    > p:last-child {
                        margin-bottom: 0;
                    }
                }
            }
        }
    }

    .ai-assistant-footer {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        .ai-assistant-footer-models {
            text-align: left;
            .ivu-select-disabled {
                .ivu-select-selection {
                    background-color: transparent;
                }
            }
            .ivu-select-selection {
                border: 0;
                box-shadow: none;
                .ivu-select-placeholder,
                .ivu-select-selected-value {
                    padding-left: 4px;
                }
            }
        }
        .ai-assistant-footer-btns {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
    }
}
body.dark-mode-reverse {
    .ai-assistant-modal {
        --apply-reasoning-before-bg: #4e4e56;
    }
}
</style>
