<template>
    <AssistantModal
        v-model="showModal"
        :displayMode="displayMode"
        :shouldCreateNewSession="shouldCreateNewSession">
        <div slot="header" class="ai-assistant-header">
            <div class="ai-assistant-header-title">
                <i class="taskfont">&#xe8a1;</i>
                <span>{{ modalTitle || $L('AI 助手') }}</span>
            </div>
            <div class="ai-assistant-header-actions">
                <div v-if="sessionEnabled && (responses.length > 0 || hasSessionHistory)" class="ai-assistant-header-btn" :title="$L('新建会话')" @click="createNewSession()">
                    <i class="taskfont">&#xe6f2;</i>
                </div>
                <Dropdown
                    v-if="sessionEnabled && hasSessionHistory"
                    trigger="click"
                    placement="bottom-end"
                    :transfer="true">
                    <div class="ai-assistant-header-btn" :title="$L('历史会话')">
                        <i class="taskfont">&#xe6e8;</i>
                    </div>
                    <DropdownMenu slot="list" class="ai-assistant-history-menu">
                        <DropdownItem
                            v-for="session in currentSessionList"
                            :key="session.id"
                            :class="{'active': session.id === currentSessionId}"
                            @click.native="loadSession(session.id)">
                            <div class="history-item">
                                <div class="history-item-content">
                                    <span class="history-item-title">{{ session.title }}</span>
                                    <span class="history-item-time">{{ formatSessionTime(session.updatedAt) }}</span>
                                </div>
                                <div class="history-item-delete" @click.stop="deleteSession(session.id)">
                                    <i class="taskfont">&#xe6e5;</i>
                                </div>
                            </div>
                        </DropdownItem>
                        <DropdownItem divided @click.native="clearSessionHistory">
                            <div class="history-clear">
                                {{ $L('清空历史记录') }}
                            </div>
                        </DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </div>
        <div class="ai-assistant-content">
            <div
                v-if="responses.length"
                ref="responseContainer"
                class="ai-assistant-output">
                <div
                    v-for="response in responses"
                    :key="response.localId"
                    class="ai-assistant-output-item">
                    <div class="ai-assistant-output-apply">
                        <template v-if="response.status === 'error'">
                            <span class="ai-assistant-output-error">{{ response.error || $L('发送失败') }}</span>
                        </template>
                        <template v-else-if="response.rawOutput">
                            <Button
                                v-if="showApplyButton"
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
                            <span class="ai-assistant-output-status">{{ loadingText || $L('生成中...') }}</span>
                        </template>
                    </div>
                    <div class="ai-assistant-output-meta">
                        <span class="ai-assistant-output-model">{{ response.modelLabel || response.model }}</span>
                    </div>
                    <div v-if="response.prompt" class="ai-assistant-output-question">{{ response.prompt }}</div>
                    <DialogMarkdown
                        v-if="response.rawOutput"
                        class="ai-assistant-output-markdown no-dark-content"
                        :text="response.displayOutput || response.rawOutput"
                        :before-navigate="() => { showModal = false }"/>
                    <div v-else class="ai-assistant-output-placeholder">
                        {{ response.status === 'error' ? (response.error || $L('发送失败')) : $L('等待 AI 回复...') }}
                    </div>
                </div>
            </div>
            <div v-else-if="displayMode === 'chat'" class="ai-assistant-welcome" @click="onFocus">
                <div class="ai-assistant-welcome-icon">
                    <i class="taskfont">&#xe8a1;</i>
                </div>
                <div class="ai-assistant-welcome-title">
                    欢迎使用 AI 助手
                </div>
                <div class="ai-assistant-welcome-swiper">
                    <!-- Swiper 容器 -->
                </div>
            </div>
            <div class="ai-assistant-input">
                <Input
                    v-model="inputValue"
                    ref="inputRef"
                    type="textarea"
                    :placeholder="inputPlaceholder || $L('请输入你的问题...')"
                    :rows="inputRows || 1"
                    :autosize="inputAutosize || {minRows:1, maxRows:6}"
                    :maxlength="inputMaxlength || 500"
                    @on-keydown="onInputKeydown"
                    @compositionstart.native="isComposing = true"
                    @compositionend.native="isComposing = false" />
                <div class="ai-assistant-footer">
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
                        <Button v-if="submitButtonText" type="primary" shape="circle" icon="md-arrow-up" :loading="loadIng > 0" @click="onSubmit">{{ submitButtonText }}</Button>
                        <Button v-else type="primary" shape="circle" icon="md-arrow-up" :loading="loadIng > 0" @click="onSubmit"></Button>
                    </div>
                </div>
            </div>
        </div>
    </AssistantModal>
</template>

<script>
import Vue from "vue";
import emitter from "../../store/events";
import {SSEClient} from "../../utils";
import {AIBotMap, AIModelNames} from "../../utils/ai";
import DialogMarkdown from "../../pages/manage/components/DialogMarkdown.vue";
import FloatButton from "./float-button.vue";
import AssistantModal from "./modal.vue";

export default {
    name: 'AIAssistant',
    components: {AssistantModal, DialogMarkdown},
    floatButtonInstance: null,
    data() {
        return {
            // 弹窗状态
            displayMode: 'modal',
            showModal: false,
            closing: false,
            loadIng: 0,
            pendingAutoSubmit: false,
            autoSubmitTimer: null,
            modalTitle: null,
            applyButtonText: null,
            submitButtonText: null,
            showApplyButton: true,
            loadingText: null,

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

            // 输入法组合状态
            isComposing: false,

            // 响应渲染
            responses: [],
            responseSeed: 1,
            maxResponses: 50,
            contextWindowSize: 10,
            activeSSEClients: [],

            // 会话管理
            sessionEnabled: false,
            sessionStore: {},
            currentSessionKey: 'default',
            currentSessionId: null,
            sessionCacheKey: 'aiAssistant.sessions',
            maxSessionsPerKey: 20,
        }
    },
    mounted() {
        emitter.on('openAIAssistant', this.onOpenAIAssistant);
        this.loadCachedModel();
        this.loadSessionStore();
        this.mountFloatButton();
    },
    beforeDestroy() {
        emitter.off('openAIAssistant', this.onOpenAIAssistant);
        this.clearActiveSSEClients();
        this.clearAutoSubmitTimer();
        this.unmountFloatButton();
    },
    computed: {
        selectedModelOption({modelMap, inputModel}) {
            return modelMap[inputModel] || null;
        },
        shouldCreateNewSession() {
            return this.responses.length === 0;
        },
        currentSessionList() {
            return this.sessionStore[this.currentSessionKey] || [];
        },
        hasSessionHistory() {
            return this.currentSessionList.length > 0;
        },
    },
    watch: {
        inputModel(value) {
            this.saveModelCache(value);
        },
    },
    methods: {
        /**
         * 获取输入框焦点事件
         */
        onFocus() {
            this.$refs.inputRef?.focus();
        },

        /**
         * 挂载浮动按钮到 body
         */
        mountFloatButton() {
            const FloatButtonCtor = Vue.extend(FloatButton);
            this.$options.floatButtonInstance = new FloatButtonCtor({
                parent: this,
            });
            this.$options.floatButtonInstance.$mount();
            document.body.appendChild(this.$options.floatButtonInstance.$el);
        },

        /**
         * 卸载浮动按钮
         */
        unmountFloatButton() {
            if (this.$options.floatButtonInstance) {
                this.$options.floatButtonInstance.$destroy();
                if (this.$options.floatButtonInstance.$el && this.$options.floatButtonInstance.$el.parentNode) {
                    this.$options.floatButtonInstance.$el.parentNode.removeChild(this.$options.floatButtonInstance.$el);
                }
                this.$options.floatButtonInstance = null;
            }
        },

        /**
         * 打开助手弹窗并应用参数
         */
        onOpenAIAssistant(params) {
            if (!$A.isJson(params)) {
                params = {};
            }
            
            const newDisplayMode = params.displayMode === 'chat' ? 'chat' : 'modal';
            let timeout = 0;
            if (this.showModal && this.displayMode === 'chat' && newDisplayMode === 'modal') {
                this.showModal = false;
                timeout = 50;
            }

            setTimeout(() => {
                this.doOpenAssistant(params, newDisplayMode);
            }, timeout);
        },

        /**
         * 实际执行打开助手的逻辑
         */
        doOpenAssistant(params, displayMode) {
            // 应用参数
            this.displayMode = displayMode;
            this.inputValue = params.value || '';
            this.inputPlaceholder = params.placeholder || null;
            this.inputRows = params.rows || null;
            this.inputAutosize = params.autosize || null;
            this.inputMaxlength = params.maxlength || null;
            this.applyHook = params.onApply || null;
            this.beforeSendHook = params.onBeforeSend || null;
            this.modalTitle = params.title || null;
            this.applyButtonText = params.applyButtonText || null;
            this.submitButtonText = params.submitButtonText || null;
            this.showApplyButton = params.showApplyButton !== false;
            this.loadingText = params.loadingText || null;
            this.renderHook = params.onRender || null;
            this.pendingAutoSubmit = !!params.autoSubmit;

            // 会话管理
            this.initSession(params.sessionKey, params.resumeSession);

            this.showModal = true;
            this.fetchModelOptions();
            this.clearActiveSSEClients();
            this.clearAutoSubmitTimer();
            this.$nextTick(() => {
                this.scheduleAutoSubmit();
                this.scrollResponsesToBottom();
                this.onFocus();
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
         * 输入框键盘事件：回车发送，Shift+回车换行
         * 注意：输入法组合输入时（如中文候选字）不发送
         */
        onInputKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey && !this.isComposing) {
                e.preventDefault();
                this.onSubmit();
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
                        // 响应完成后保存会话
                        this.saveCurrentSession();
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

        // ==================== 会话管理方法 ====================

        /**
         * 加载持久化的会话数据
         */
        async loadSessionStore() {
            try {
                const stored = await $A.IDBString(this.sessionCacheKey);
                if (stored) {
                    this.sessionStore = JSON.parse(stored);
                }
            } catch (e) {
                this.sessionStore = {};
            }
        },

        /**
         * 持久化会话数据
         */
        saveSessionStore() {
            try {
                $A.IDBSave(this.sessionCacheKey, JSON.stringify(this.sessionStore));
            } catch (e) {
                console.warn('[AIAssistant] Failed to save session store:', e);
            }
        },

        /**
         * 生成会话 ID
         */
        generateSessionId() {
            return `session-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        },

        /**
         * 根据首次输入生成会话标题
         */
        generateSessionTitle(responses) {
            if (!responses || responses.length === 0) {
                return this.$L('新会话');
            }
            const firstPrompt = responses.find(r => r.prompt)?.prompt || '';
            if (!firstPrompt) {
                return this.$L('新会话');
            }
            // 截取前20个字符作为标题
            const title = firstPrompt.trim().substring(0, 20);
            return title.length < firstPrompt.trim().length ? `${title}...` : title;
        },

        /**
         * 获取指定场景的会话列表
         */
        getSessionList(sessionKey) {
            return this.sessionStore[sessionKey] || [];
        },

        /**
         * 初始化会话
         * @param {string} sessionKey - 会话场景标识，不传则不启用会话管理
         * @param {boolean|number} resumeSession - 恢复上次会话：true 总是恢复，数字表示秒数阈值（上次会话在该时间内则恢复）
         */
        initSession(sessionKey, resumeSession = false) {
            // 保存当前会话
            if (this.responses.length > 0) {
                this.saveCurrentSession();
            }

            this.sessionEnabled = !!sessionKey;

            if (this.sessionEnabled) {
                this.currentSessionKey = sessionKey;

                if (resumeSession) {
                    const sessions = this.getSessionList(sessionKey);
                    if (sessions.length > 0) {
                        const lastSession = sessions[0];
                        // 如果是数字，检查时间阈值
                        if (typeof resumeSession === 'number') {
                            const elapsed = (Date.now() - lastSession.updatedAt) / 1000;
                            if (elapsed > resumeSession) {
                                // 超过阈值，创建新会话
                                this.currentSessionId = this.generateSessionId();
                                this.responses = [];
                                return;
                            }
                        }
                        this.currentSessionId = lastSession.id;
                        this.responses = JSON.parse(JSON.stringify(lastSession.responses));
                        this.syncResponseSeed();
                        return;
                    }
                }

                this.currentSessionId = this.generateSessionId();
                this.responses = [];
            } else {
                this.currentSessionKey = 'default';
                this.currentSessionId = null;
                this.responses = [];
            }
        },

        /**
         * 创建新会话
         */
        createNewSession(autoSaveCurrent = true) {
            // 保存当前会话
            if (autoSaveCurrent && this.responses.length > 0) {
                this.saveCurrentSession();
            }
            // 创建新会话
            this.currentSessionId = this.generateSessionId();
            this.responses = [];
        },

        /**
         * 保存当前会话到存储
         */
        saveCurrentSession() {
            if (!this.sessionEnabled || !this.currentSessionId || this.responses.length === 0) {
                return;
            }

            const sessionKey = this.currentSessionKey;
            if (!this.sessionStore[sessionKey]) {
                this.$set(this.sessionStore, sessionKey, []);
            }

            const sessions = this.sessionStore[sessionKey];
            const existingIndex = sessions.findIndex(s => s.id === this.currentSessionId);
            const sessionData = {
                id: this.currentSessionId,
                title: this.generateSessionTitle(this.responses),
                responses: JSON.parse(JSON.stringify(this.responses)),
                createdAt: existingIndex > -1 ? sessions[existingIndex].createdAt : Date.now(),
                updatedAt: Date.now(),
            };

            if (existingIndex > -1) {
                sessions[existingIndex] = sessionData;
            } else {
                sessions.unshift(sessionData);
            }

            // 限制每个场景的会话数量
            if (sessions.length > this.maxSessionsPerKey) {
                sessions.splice(this.maxSessionsPerKey);
            }

            this.saveSessionStore();
        },

        /**
         * 加载指定会话
         */
        loadSession(sessionId) {
            const sessions = this.getSessionList(this.currentSessionKey);
            const session = sessions.find(s => s.id === sessionId);
            if (session) {
                // 先保存当前会话
                if (this.currentSessionId !== sessionId && this.responses.length > 0) {
                    this.saveCurrentSession();
                }
                this.currentSessionId = session.id;
                this.responses = JSON.parse(JSON.stringify(session.responses));
                this.syncResponseSeed();
                this.scrollResponsesToBottom();
            }
        },

        /**
         * 同步 responseSeed 以避免与已有响应 localId 冲突
         */
        syncResponseSeed() {
            if (this.responses.length === 0) {
                return;
            }
            const maxLocalId = this.responses.reduce((max, r) => {
                return Math.max(max, r.localId || 0);
            }, 0);
            if (maxLocalId >= this.responseSeed) {
                this.responseSeed = maxLocalId + 1;
            }
        },

        /**
         * 删除指定会话
         */
        deleteSession(sessionId) {
            const sessions = this.getSessionList(this.currentSessionKey);
            const index = sessions.findIndex(s => s.id === sessionId);
            if (index > -1) {
                sessions.splice(index, 1);
                this.saveSessionStore();
                // 如果删除的是当前会话，创建新会话
                if (this.currentSessionId === sessionId) {
                    this.createNewSession(false);
                }
            }
        },

        /**
         * 清空当前场景的所有历史会话
         */
        clearSessionHistory() {
            $A.modalConfirm({
                title: this.$L('清空历史会话'),
                content: this.$L('确定要清空当前场景的所有历史会话吗？'),
                onOk: () => {
                    this.$set(this.sessionStore, this.currentSessionKey, []);
                    this.saveSessionStore();
                    this.createNewSession(false);
                }
            });
        },

        /**
         * 格式化会话时间显示
         */
        formatSessionTime(timestamp) {
            const now = $A.daytz();
            const time = $A.dayjs(timestamp);
            if (now.format('YYYY-MM-DD') === time.format('YYYY-MM-DD')) {
                return this.$L('今天') + ' ' + time.format('HH:mm');
            }
            if (now.subtract(1, 'day').format('YYYY-MM-DD') === time.format('YYYY-MM-DD')) {
                return this.$L('昨天') + ' ' + time.format('HH:mm');
            }
            if (now.year() === time.year()) {
                return time.format('MM-DD HH:mm');
            }
            return time.format('YYYY-MM-DD HH:mm');
        },
    },
}
</script>

<style lang="scss">

.ai-assistant-header {
    display: flex;
    align-items: center;
    margin: -11px 24px -10px 0;
    height: 38px;

    .ai-assistant-header-title {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        color: #303133;
        padding-right: 12px;
        gap: 8px;

        > i {
            font-size: 18px;
        }

        > span {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 18px;
            font-weight: 500;
        }
    }
    .ai-assistant-header-actions {
        display: flex;
        align-items: center;
        gap: 4px;

        .ai-assistant-header-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            &:hover {
                background-color: rgba(0, 0, 0, 0.06);
            }
            > i {
                font-size: 18px;
            }
        }
    }
}

.ai-assistant-content {
    display: flex;
    flex-direction: column;
    max-height: calc(var(--window-height) - var(--status-bar-height) - var(--navigation-bar-height) - 266px);
    @media (height <= 900px) {
        max-height: calc(var(--window-height) - var(--status-bar-height) - var(--navigation-bar-height) - 136px);
    }

    .ai-assistant-welcome,
    .ai-assistant-output {
        flex: 1;
        min-height: 0;
        padding: 12px 24px;
        margin-bottom: 12px;
        border-radius: 0;
        background: #f8f9fb;
        border: 0;
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
        height: 26px;
        color: #999;
        gap: 4px;
    }

    .ai-assistant-output-icon {
        font-size: 16px;
        color: #52c41a;
    }

    .ai-assistant-apply-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        border-radius: 4px;
        height: 26px;
        padding: 0 8px;
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

.ai-assistant-input {
    padding: 4px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;

    .ivu-input {
        background-color: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        padding: 0 8px;
        resize: none;
        &:hover {
            border-color: transparent;
        }
    }

    .ivu-select-selection {
        background-color: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        padding: 0 0 0 8px;
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
                padding-left: 0;
                opacity: 0.8;
            }
        }
    }
    .ai-assistant-footer-btns {
        flex: 1;
        display: flex;
        justify-content: flex-end;
    }
}

.ai-assistant-history-menu {
    min-width: 240px;
    max-width: 300px;
    max-height: 320px;
    overflow-y: auto;

    .ivu-dropdown-item {
        &.active {
            background-color: rgba(45, 140, 240, 0.1);
        }
        &:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }
    }

    .history-item {
        display: flex;
        align-items: center;
        gap: 8px;

        .history-item-content {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 2px;

            .history-item-title {
                font-size: 13px;
                color: #303133;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .history-item-time {
                font-size: 11px;
                color: #909399;
            }
        }

        .history-item-delete {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            opacity: 0;
            transition: opacity 0.2s, background-color 0.2s;
            cursor: pointer;

            &:hover {
                background-color: rgba(0, 0, 0, 0.08);
            }

            > i {
                font-size: 12px;
                color: #909399;
            }
        }

        &:hover .history-item-delete {
            opacity: 1;
        }
    }

    .history-clear {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #F56C6C;
    }
}

.ai-assistant-chat {
    position: fixed;
    right: 24px;
    bottom: 24px;
    width: 460px;
    height: 80vh;
    min-width: 380px;
    max-width: 600px;
    max-height: 640px;
    background-color: #ffffff;
    box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.12);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;

    .ai-assistant-close {
        position: absolute;
        top: 6px;
        right: 10px;
        z-index: 1;
        font-size: 38px;
        color: #999;
        cursor: pointer;
        transition: all 0.2s;
        &:hover {
            color: #444;
            transform: rotate(-90deg);
        }
    }

    .ai-assistant-drag-handle {
        cursor: move;
        user-select: none;
    }

    .ai-assistant-header {
        margin: 6px 48px 6px 16px;

        .ai-assistant-header-title {
            > span {
                font-size: 17px;
            }
        }
    }

    .ai-assistant-content {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;

        .ai-assistant-welcome {
            .ai-assistant-welcome-icon {
                margin-top: 12px;
                i {
                    font-size: 24px;
                }
            }
            .ai-assistant-welcome-title {
                margin-top: 12px;
            }
            .ai-assistant-welcome-swiper {
                margin-top: 24px;
            }
        }
    }

    .ai-assistant-input {
        padding: 4px 12px 12px;
    }
}

.ai-assistant-modal {
    --apply-reasoning-before-bg: #e1e1e1;
    .ivu-modal {
        transition: width 0.3s, max-width 0.3s;
        .ivu-modal-header {
            border-bottom: none !important;
        }
        .ivu-modal-body {
            padding: 0 !important;
        }
    }
}

body.dark-mode-reverse {
    .ai-assistant-modal {
        --apply-reasoning-before-bg: #4e4e56;
    }
}
</style>
