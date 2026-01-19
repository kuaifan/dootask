<template>
    <AssistantModal
        v-model="showModal"
        :displayMode="displayMode"
        :shouldCreateNewSession="shouldCreateNewSession"
        :zIndex="topZIndex">
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
                    :transfer="true"
                    :z-index="topZIndex + 1">
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
                                    <div class="history-item-title">{{ session.title }}</div>
                                    <div class="history-item-delete" @click.stop="deleteSession(session.id)">
                                        <i class="taskfont">&#xe6e5;</i>
                                    </div>
                                </div>
                                <span class="history-item-time">{{ formatSessionTime(session.updatedAt) }}</span>
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
                            <span class="ai-assistant-output-error">{{ $L('发送失败') }}</span>
                        </template>
                        <template v-else-if="response.rawOutput && response.status !== 'streaming'">
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
                        <template v-else-if="!response.rawOutput || response.status === 'streaming'">
                            <Icon type="ios-loading" class="ai-assistant-output-icon icon-loading"/>
                            <span v-if="loadingText && !response.rawOutput" class="ai-assistant-output-status">{{ loadingText }}</span>
                        </template>
                    </div>
                    <div class="ai-assistant-output-meta">
                        <span class="ai-assistant-output-model">{{ response.modelLabel || response.model }}</span>
                    </div>
                    <!-- 问题区域：正常显示或编辑模式 -->
                    <div v-if="response.prompt" class="ai-assistant-output-question-wrap">
                        <!-- 编辑模式 -->
                        <div v-if="editingIndex === responses.indexOf(response)" class="ai-assistant-question-editor">
                            <Input
                                v-model="editingValue"
                                ref="editInputRef"
                                type="textarea"
                                :autosize="{minRows: 1, maxRows: 6}"
                                :maxlength="inputMaxlength || 500"
                                @on-keydown="onEditKeydown"
                                @compositionstart.native="isComposing = true"
                                @compositionend.native="isComposing = false" />
                            <div class="ai-assistant-question-editor-btns">
                                <Button size="small" @click="cancelEditQuestion">{{ $L('取消') }}</Button>
                                <Button type="primary" size="small" :loading="loadIng > 0" @click="submitEditedQuestion">{{ $L('发送') }}</Button>
                            </div>
                        </div>
                        <!-- 正常显示模式 -->
                        <div v-else class="ai-assistant-output-question">
                            <span class="ai-assistant-output-question-text">{{ response.prompt }}</span>
                            <span class="ai-assistant-output-question-edit" :title="$L('编辑问题')" @click="startEditQuestion(responses.indexOf(response))">
                                <svg viewBox="0 0 20 20" fill="currentColor"><path d="M11.331 3.568a3.61 3.61 0 0 1 4.973.128l.128.135a3.61 3.61 0 0 1 0 4.838l-.128.135-6.292 6.29c-.324.324-.558.561-.79.752l-.235.177q-.309.21-.65.36l-.23.093c-.181.066-.369.114-.585.159l-.765.135-2.394.399c-.142.024-.294.05-.422.06-.1.007-.233.01-.378-.026l-.149-.049a1.1 1.1 0 0 1-.522-.474l-.046-.094a1.1 1.1 0 0 1-.074-.526c.01-.129.035-.28.06-.423l.398-2.394.134-.764a4 4 0 0 1 .16-.586l.093-.23q.15-.342.36-.65l.176-.235c.19-.232.429-.466.752-.79l6.291-6.292zm-5.485 7.36c-.35.35-.533.535-.66.688l-.11.147a2.7 2.7 0 0 0-.24.433l-.062.155c-.04.11-.072.225-.106.394l-.127.717-.398 2.393-.001.002h.003l2.393-.399.717-.126c.169-.034.284-.065.395-.105l.153-.062q.228-.1.433-.241l.148-.11c.153-.126.338-.31.687-.66l4.988-4.988-3.226-3.226zm9.517-6.291a2.28 2.28 0 0 0-3.053-.157l-.173.157-.364.363L15 8.226l.363-.363.157-.174a2.28 2.28 0 0 0 0-2.878z"/></svg>
                            </span>
                        </div>
                    </div>
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
                    <svg class="no-dark-content" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                        <path d="M385.80516777 713.87417358c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404756l-48.91927648-123.9413531c-18.40341303-46.75969229-55.77360888-84.0359932-102.53330118-102.53330117l-123.94135309-48.91927649c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.8257541s7.79328205-24.13100586 19.62404757-28.82575407l123.94135309-48.91927649c46.75969229-18.40341303 84.0359932-55.77360888 102.53330118-102.53330119l48.91927648-123.94135308c4.69474822-11.83076552 16.05603892-19.62404757 28.8257541-19.62404757s24.13100586 7.79328205 28.82575408 19.62404757l48.91927648 123.94135308c18.40341303 46.75969229 55.77360888 84.0359932 102.53330118 102.53330119l123.94135309 48.91927649c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575407 0 12.76971517-7.79328205 24.13100586-19.62404757 28.8257541l-123.94135309 48.91927649c-46.75969229 18.40341303-84.0359932 55.77360888-102.53330118 102.53330117l-48.91927648 123.9413531c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575408 19.62404756zM177.45224165 390.12433614l50.89107073 20.0935224c62.62794129 24.69437565 112.67395736 74.74039171 137.368333 137.36833299l20.09352239 50.89107073 20.0935224-50.89107073c24.69437565-62.62794129 74.74039171-112.67395736 137.368333-137.36833299l50.89107072-20.0935224-50.89107073-20.09352239c-62.62794129-24.69437565-112.67395736-74.74039171-137.36833299-137.36833301l-20.09352239-50.89107074-20.0935224 50.89107074c-24.69437565 62.62794129-74.74039171 112.67395736-137.368333 137.36833301l-50.89107073 20.09352239zM771.33789183 957.62550131c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404758l-26.6661699-67.6043744c-8.63833672-21.87752672-26.10280012-39.34199011-47.98032684-47.98032684l-67.60437441-26.6661699c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.82575409s7.79328205-24.13100586 19.62404757-28.82575409l67.60437441-26.6661699c21.87752672-8.63833672 39.34199011-26.10280012 47.98032684-47.98032685l26.6661699-67.6043744c4.69474822-11.83076552 16.05603892-19.62404757 28.82575409-19.62404757s24.13100586 7.79328205 28.82575409 19.62404757l26.66616991 67.6043744c8.63833672 21.87752672 26.10280012 39.34199011 47.98032684 47.98032685l67.6043744 26.6661699c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575409s-7.79328205 24.13100586-19.62404757 28.82575409l-67.6043744 26.6661699c-21.87752672 8.63833672-39.34199011 26.10280012-47.98032684 47.98032684l-26.66616991 67.6043744c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575409 19.62404758z m-75.58544639-190.70067281c33.61439727 14.83540438 60.75004201 41.87715415 75.49155143 75.49155143 14.83540438-33.61439727 41.87715415-60.75004201 75.49155142-75.49155143-33.61439727-14.83540438-60.75004201-41.87715415-75.49155142-75.49155143-14.74150942 33.61439727-41.87715415 60.75004201-75.49155143 75.49155143z"/>
                    </svg>
                </div>
                <div class="ai-assistant-welcome-title">
                    {{ $L('欢迎使用 AI 助手') }}
                </div>
                <div class="ai-assistant-welcome-prompts">
                    <div
                        v-for="(prompt, index) in displayWelcomePrompts"
                        :key="index"
                        class="ai-assistant-prompt-card"
                        @click="onPromptClick(prompt)">
                        <span v-if="prompt.svg" class="ai-assistant-prompt-icon no-dark-content" v-html="prompt.svg"></span>
                        <span>{{ prompt.text }}</span>
                    </div>
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
                            transfer
                            :z-index="topZIndex + 1">
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
import {debounce} from "lodash";
import emitter from "../../store/events";
import {SSEClient} from "../../utils";
import {AIBotMap, AIModelNames} from "../../utils/ai";
import DialogMarkdown from "../../pages/manage/components/DialogMarkdown.vue";
import FloatButton from "./float-button.vue";
import AssistantModal from "./modal.vue";
import {getWelcomePrompts} from "./welcome-prompts";

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
            sessionStore: [],
            currentSessionKey: 'default',
            currentSessionId: null,
            currentSceneKey: null,
            sessionCacheKeyPrefix: 'aiAssistant.sessions',
            maxSessionsPerKey: 20,
            sessionStoreLoaded: false,

            // 欢迎提示词（防抖更新，避免场景切换时连续刷新导致闪屏）
            displayWelcomePrompts: [],

            // 编辑历史问题
            editingIndex: -1,        // 正在编辑的响应索引，-1 表示不在编辑模式
            editingValue: '',        // 编辑中的文本内容

            // 输入历史（上下键切换）
            inputHistoryList: [],    // 历史输入列表
            inputHistoryIndex: 0,    // 当前历史索引
            inputHistoryCurrent: '', // 切换前的当前输入
            inputHistoryCacheKey: 'aiAssistant.inputHistory',
            inputHistoryLimit: 50,

            // 动态 z-index（确保始终在最顶层）
            topZIndex: (window.modalTransferIndex || 1000) + 1000,
            zIndexTimer: null,
        }
    },
    created() {
        // 创建防抖函数（每个实例独立）
        this.refreshWelcomePromptsDebounced = debounce(() => {
            this.displayWelcomePrompts = getWelcomePrompts(this.$store, this.$route?.params || {});
        }, 100);
    },
    mounted() {
        emitter.on('openAIAssistant', this.onOpenAIAssistant);
        this.loadCachedModel();
        this.loadInputHistory();
        this.mountFloatButton();
        this.startZIndexTimer(20000);
    },
    beforeDestroy() {
        emitter.off('openAIAssistant', this.onOpenAIAssistant);
        this.clearActiveSSEClients();
        this.clearAutoSubmitTimer();
        this.unmountFloatButton();
        this.refreshWelcomePromptsDebounced?.cancel();
        this.stopZIndexTimer();
    },
    computed: {
        selectedModelOption({modelMap, inputModel}) {
            return modelMap[inputModel] || null;
        },
        shouldCreateNewSession() {
            return this.responses.length === 0;
        },
        currentSessionList() {
            return this.sessionStore || [];
        },
        hasSessionHistory() {
            return this.currentSessionList.length > 0;
        },
        // 提示词数据源（用于触发 watch，实际渲染用 displayWelcomePrompts）
        welcomePromptsKey() {
            // 返回影响提示词的关键数据，用于判断是否需要刷新
            const routeName = this.$store.state.routeName;
            const dialogId = this.$store.state.dialogId;
            const projectId = this.$store.getters.projectData?.id;
            const taskId = this.$store.state.taskId;
            const dialogModalShow = this.$store.state.dialogModalShow;
            return `${routeName}|${dialogId}|${projectId}|${taskId}|${dialogModalShow}`;
        },
    },
    watch: {
        inputModel(value) {
            this.saveModelCache(value);
        },
        welcomePromptsKey: {
            handler() {
                this.refreshWelcomePromptsDebounced?.();
            },
            immediate: true,
        },
        showModal(value) {
            if (value) {
                // 弹窗打开时：5 秒刷新 z-index
                this.startZIndexTimer(5000);
            } else {
                // 弹窗关闭时：20 秒刷新 z-index，并通知操作模块
                this.startZIndexTimer(20000);
                emitter.emit('aiAssistantClosed');
            }
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
         * 点击快捷提示，填入输入框
         */
        onPromptClick(prompt) {
            if (!prompt || !prompt.text) {
                return;
            }
            this.inputValue = prompt.text;
            this.$nextTick(() => {
                this.onFocus();
            });
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
        async doOpenAssistant(params, displayMode) {
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
            await this.initSession(params.sessionKey, params.sceneKey, params.resumeSession);

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
         * 输入框键盘事件：回车发送，Shift+回车换行，上下键切换历史
         * 注意：输入法组合输入时（如中文候选字）不发送
         */
        onInputKeydown(e) {
            if (this.isComposing) {
                return;
            }
            if (!e.shiftKey) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.onSubmit();
                    return;
                }
                if (e.key === 'Escape' && this.displayMode === 'chat') {
                    this.showModal = false;
                    return;
                }
            }
            // 上下键切换历史输入
            if (e.key === 'ArrowUp') {
                if (!this.navigateInputHistory('up')) {
                    e.preventDefault();
                }
            } else if (e.key === 'ArrowDown') {
                if (!this.navigateInputHistory('down')) {
                    e.preventDefault();
                }
            }
        },

        /**
         * 发送提问并推入响应
         */
        async onSubmit() {
            if (this.loadIng > 0) {
                return;
            }
            const prompt = (this.inputValue || '').trim();
            if (!prompt) {
                return;
            }
            const success = await this._doSendQuestion(prompt);
            if (success) {
                this.inputValue = '';
            }
        },

        /**
         * 执行发送问题的核心逻辑
         * @param {string} prompt - 要发送的问题
         * @returns {Promise<boolean>} - 是否发送成功
         * @private
         */
        async _doSendQuestion(prompt) {
            const modelOption = this.selectedModelOption;
            if (!modelOption) {
                $A.messageWarning('请选择模型');
                return false;
            }

            this.loadIng++;
            let responseEntry = null;
            try {
                const baseContext = this.collectBaseContext(prompt);
                const context = await this.buildPayloadData(baseContext);

                responseEntry = this.createResponseEntry({
                    modelOption,
                    prompt,
                });
                this.scrollResponsesToBottom();

                const streamKey = await this.fetchStreamKey({
                    model_type: modelOption.type,
                    model_name: modelOption.value,
                    context,
                });

                this.persistInputHistory(prompt);
                this.startStream(streamKey, responseEntry);
                return true;
            } catch (error) {
                const msg = error?.msg || '发送失败';
                if (responseEntry) {
                    this.markResponseError(responseEntry, msg);
                }
                $A.modalError(msg);
                return false;
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
                        // 检查 done 事件是否携带错误信息
                        const donePayload = this.parseStreamPayload(event);
                        if (donePayload && donePayload.error) {
                            this.markResponseError(responseEntry, donePayload.error);
                        } else if (responseEntry && responseEntry.status !== 'error') {
                            responseEntry.status = 'completed';
                        }
                        this.releaseSSEClient(sse);
                        // 响应完成后保存会话
                        this.saveCurrentSession();
                        break;
                }
            }, () => {
                // SSE 连接失败（重试次数用完）时的回调
                if (responseEntry && responseEntry.status === 'streaming') {
                    responseEntry.status = 'completed';
                }
                this.releaseSSEClient(sse);
                this.saveCurrentSession();
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
            this.resetInputHistoryNavigation();
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
         * 获取指定场景的缓存 key
         */
        getSessionCacheKey(sessionKey) {
            return `${this.sessionCacheKeyPrefix}_${sessionKey || 'default'}`;
        },

        /**
         * 加载指定场景的会话数据
         */
        async loadSessionStore(sessionKey) {
            const cacheKey = this.getSessionCacheKey(sessionKey);
            try {
                const stored = await $A.IDBString(cacheKey);
                if (stored) {
                    this.sessionStore = JSON.parse(stored);
                    if (!Array.isArray(this.sessionStore)) {
                        this.sessionStore = [];
                    }
                } else {
                    this.sessionStore = [];
                }
            } catch (e) {
                this.sessionStore = [];
            }
            this.sessionStoreLoaded = true;
        },

        /**
         * 持久化当前场景的会话数据
         */
        saveSessionStore() {
            const cacheKey = this.getSessionCacheKey(this.currentSessionKey);
            try {
                $A.IDBSave(cacheKey, JSON.stringify(this.sessionStore));
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
         * 获取当前场景的会话列表
         */
        getSessionList() {
            return this.sessionStore || [];
        },

        /**
         * 初始化会话
         * @param {string} sessionKey - 会话场景标识，不传则不启用会话管理
         * @param {string} sceneKey - 场景标识，用于判断是否恢复会话
         * @param {number} resumeTimeout - 恢复超时时间（秒），默认1天
         */
        async initSession(sessionKey, sceneKey = null, resumeTimeout = 86400) {
            // 保存当前会话
            if (this.responses.length > 0) {
                this.saveCurrentSession();
            }

            this.sessionEnabled = !!sessionKey;
            this.currentSceneKey = sceneKey;

            if (this.sessionEnabled) {
                // 如果切换到不同的场景，需要加载新场景的数据
                if (this.currentSessionKey !== sessionKey || !this.sessionStoreLoaded) {
                    this.currentSessionKey = sessionKey;
                    await this.loadSessionStore(sessionKey);
                }

                // 如果传入了 sceneKey，从历史中查找相同场景的最新会话
                if (sceneKey) {
                    const sessions = this.getSessionList();
                    // 找到相同场景标识的最新一条记录
                    const matchedSession = sessions.find(s => s.sceneKey === sceneKey);
                    if (matchedSession) {
                        const elapsed = (Date.now() - matchedSession.updatedAt) / 1000;
                        // 在超时时间内则恢复
                        if (elapsed <= resumeTimeout) {
                            this.currentSessionId = matchedSession.id;
                            this.responses = JSON.parse(JSON.stringify(matchedSession.responses));
                            this.syncResponseSeed();
                            return;
                        }
                    }
                }

                // 无匹配会话、超时或无 sceneKey，创建新会话
                this.currentSessionId = this.generateSessionId();
                this.responses = [];
            } else {
                this.currentSessionKey = 'default';
                this.currentSessionId = null;
                this.currentSceneKey = null;
                this.responses = [];
                this.sessionStoreLoaded = false;
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

            // 确保 sessionStore 是数组
            if (!Array.isArray(this.sessionStore)) {
                this.sessionStore = [];
            }

            const existingIndex = this.sessionStore.findIndex(s => s.id === this.currentSessionId);
            const sessionData = {
                id: this.currentSessionId,
                title: this.generateSessionTitle(this.responses),
                responses: JSON.parse(JSON.stringify(this.responses)),
                sceneKey: this.currentSceneKey,
                createdAt: existingIndex > -1 ? this.sessionStore[existingIndex].createdAt : Date.now(),
                updatedAt: Date.now(),
            };

            if (existingIndex > -1) {
                this.sessionStore.splice(existingIndex, 1, sessionData);
            } else {
                this.sessionStore.unshift(sessionData);
            }

            // 限制每个场景的会话数量
            if (this.sessionStore.length > this.maxSessionsPerKey) {
                this.sessionStore.splice(this.maxSessionsPerKey);
            }

            this.saveSessionStore();
        },

        /**
         * 加载指定会话
         */
        loadSession(sessionId) {
            const sessions = this.getSessionList();
            const session = sessions.find(s => s.id === sessionId);
            if (session) {
                // 先保存当前会话
                if (this.currentSessionId !== sessionId && this.responses.length > 0) {
                    this.saveCurrentSession();
                }
                this.currentSessionId = session.id;
                this.currentSceneKey = session.sceneKey || null;
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
            const index = this.sessionStore.findIndex(s => s.id === sessionId);
            if (index > -1) {
                this.sessionStore.splice(index, 1);
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
                    this.sessionStore = [];
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

        // ==================== 输入历史（上下键切换）====================

        /**
         * 加载输入历史
         */
        async loadInputHistory() {
            try {
                const history = await $A.IDBValue(this.inputHistoryCacheKey);
                if (Array.isArray(history)) {
                    this.inputHistoryList = history;
                } else {
                    this.inputHistoryList = [];
                }
            } catch (e) {
                this.inputHistoryList = [];
            }
            this.inputHistoryIndex = this.inputHistoryList.length;
            this.inputHistoryCurrent = '';
        },

        /**
         * 保存输入到历史
         */
        persistInputHistory(content) {
            const trimmed = (content || '').trim();
            if (!trimmed) {
                return;
            }
            const history = Array.isArray(this.inputHistoryList) ? [...this.inputHistoryList] : [];
            // 如果和最后一条相同，不重复添加
            if (history[history.length - 1] === trimmed) {
                this.inputHistoryIndex = history.length;
                this.inputHistoryCurrent = '';
                return;
            }
            // 如果已存在，移动到末尾
            const existIndex = history.indexOf(trimmed);
            if (existIndex !== -1) {
                history.splice(existIndex, 1);
            }
            history.push(trimmed);
            // 限制最大条数
            if (history.length > this.inputHistoryLimit) {
                history.splice(0, history.length - this.inputHistoryLimit);
            }
            this.inputHistoryList = history;
            this.inputHistoryIndex = history.length;
            this.inputHistoryCurrent = '';
            $A.IDBSet(this.inputHistoryCacheKey, history).catch(() => {});
        },

        /**
         * 重置历史导航状态
         */
        resetInputHistoryNavigation() {
            this.inputHistoryIndex = this.inputHistoryList.length;
            this.inputHistoryCurrent = '';
        },

        /**
         * 导航输入历史
         * @param {string} direction - 'up' 或 'down'
         * @returns {boolean} - 是否允许默认行为
         */
        navigateInputHistory(direction) {
            if (!this.inputHistoryList.length) {
                return true;
            }
            const textarea = this.$refs.inputRef?.$el?.querySelector('textarea');
            if (!textarea) {
                return true;
            }
            const cursorPos = textarea.selectionStart;
            const cursorEnd = textarea.selectionEnd;
            const value = this.inputValue || '';
            // 如果有选中文本，允许默认行为
            if (cursorPos !== cursorEnd) {
                return true;
            }
            if (direction === 'up') {
                // 只有光标在第一行时才切换历史
                const beforeCursor = value.substring(0, cursorPos);
                if (beforeCursor.includes('\n')) {
                    return true;
                }
                // 保存当前输入
                if (this.inputHistoryIndex === this.inputHistoryList.length) {
                    this.inputHistoryCurrent = value;
                }
                if (this.inputHistoryIndex > 0) {
                    this.inputHistoryIndex--;
                    this.inputValue = this.inputHistoryList[this.inputHistoryIndex] || '';
                    this.$nextTick(() => {
                        const ta = this.$refs.inputRef?.$el?.querySelector('textarea');
                        ta?.setSelectionRange(0, 0);
                    });
                    return false;
                }
            } else if (direction === 'down') {
                // 只有光标在最后一行时才切换历史
                const afterCursor = value.substring(cursorPos);
                if (afterCursor.includes('\n')) {
                    return true;
                }
                if (this.inputHistoryIndex >= this.inputHistoryList.length) {
                    return true;
                }
                if (this.inputHistoryIndex < this.inputHistoryList.length - 1) {
                    this.inputHistoryIndex++;
                    this.inputValue = this.inputHistoryList[this.inputHistoryIndex] || '';
                } else {
                    this.inputHistoryIndex = this.inputHistoryList.length;
                    this.inputValue = this.inputHistoryCurrent || '';
                }
                this.$nextTick(() => {
                    const ta = this.$refs.inputRef?.$el?.querySelector('textarea');
                    if (ta) {
                        const len = (this.inputValue || '').length;
                        ta.setSelectionRange(len, len);
                    }
                });
                return false;
            }
            return true;
        },

        // ==================== 编辑历史问题 ====================

        /**
         * 开始编辑历史问题
         */
        startEditQuestion(index) {
            if (index < 0 || index >= this.responses.length) {
                return;
            }
            if (this.loadIng > 0) {
                return;
            }
            this.editingIndex = index;
            this.editingValue = this.responses[index].prompt || '';
            this.$nextTick(() => {
                // ref 在 v-for 中会变成数组
                const inputRef = this.$refs.editInputRef;
                const input = Array.isArray(inputRef) ? inputRef[0] : inputRef;
                if (input && typeof input.focus === 'function') {
                    input.focus();
                }
            });
        },

        /**
         * 取消编辑
         */
        cancelEditQuestion() {
            this.editingIndex = -1;
            this.editingValue = '';
        },

        /**
         * 编辑器键盘事件
         */
        onEditKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.cancelEditQuestion();
            } else if (e.key === 'Enter' && !e.shiftKey && !this.isComposing) {
                e.preventDefault();
                this.submitEditedQuestion();
            }
        },

        /**
         * 提交编辑后的问题
         */
        async submitEditedQuestion() {
            if (this.editingIndex < 0 || this.loadIng > 0) {
                return;
            }
            const newPrompt = (this.editingValue || '').trim();
            if (!newPrompt) {
                $A.messageWarning('请输入问题');
                return;
            }

            // 删除从编辑位置开始的所有响应
            this.responses.splice(this.editingIndex);

            // 重置编辑状态
            this.editingIndex = -1;
            this.editingValue = '';

            // 发送新问题
            await this._doSendQuestion(newPrompt);
        },

        // ==================== z-index 管理 ====================

        /**
         * 更新 z-index 确保在最顶层
         */
        updateTopZIndex() {
            this.topZIndex = (window.modalTransferIndex || 1000) + 1000;
        },

        /**
         * 启动 z-index 刷新定时器
         * @param {number} interval - 刷新间隔（毫秒）
         */
        startZIndexTimer(interval) {
            this.stopZIndexTimer();
            this.updateTopZIndex();
            this.zIndexTimer = setInterval(() => {
                this.updateTopZIndex();
            }, interval);
        },

        /**
         * 停止 z-index 刷新定时器
         */
        stopZIndexTimer() {
            if (this.zIndexTimer) {
                clearInterval(this.zIndexTimer);
                this.zIndexTimer = null;
            }
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
        gap: 6px;

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

    .ai-assistant-output-question-wrap {
        margin-top: 8px;
    }

    .ai-assistant-output-question {
        display: flex;
        align-items: flex-start;
        gap: 4px;
        font-size: 12px;
        color: #666;
        line-height: 1.4;

        .ai-assistant-output-question-text {
            flex: 1;
            min-width: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .ai-assistant-output-question-edit {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            color: #777;
            border-radius: 4px;
            margin-top: -2px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s, color 0.2s, background-color 0.2s;

            svg {
                width: 14px;
                height: 14px;
            }

            &:hover {
                color: #444;
                background-color: rgba(0, 0, 0, 0.06);
            }
        }

        &:hover {
            .ai-assistant-output-question-edit {
                opacity: 1;
            }
        }
    }

    .ai-assistant-question-editor {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 8px;
        background: #fff;
        border: 1px solid #e8e8e8;
        border-radius: 13px;

        .ivu-input {
            color: #333;
            background-color: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 0 2px;
            resize: none;
            font-size: 12px;

            &:hover,
            &:focus {
                border-color: transparent;
                box-shadow: none;
            }
        }

        .ai-assistant-question-editor-btns {
            display: flex;
            justify-content: flex-end;
            gap: 8px;

            .ivu-btn {
                height: 26px;
                font-size: 12px;
                padding: 0 9px;
                border-radius: 13px;
            }
        }
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
        color: #333333;
        background-color: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        padding: 0 8px;
        resize: none;
        &:hover,
        &:focus {
            border-color: transparent;
            box-shadow: none;
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
    width: 260px;
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
        flex-direction: column;
        gap: 2px;

        .history-item-content {
            flex: 1;
            min-width: 0;
            display: flex;
            gap: 8px;
            line-height: 20px;
            align-items: center;

            .history-item-title {
                flex: 1;
                min-width: 0;
                font-size: 13px;
                color: #303133;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .history-item-delete {
                flex-shrink: 0;
                display: none;
                align-items: center;
                justify-content: center;
                width: 20px;
                height: 20px;
                border-radius: 4px;
                margin-right: -2px;
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
        }

        .history-item-time {
            font-size: 11px;
            color: #909399;
        }

        &:hover {
            .history-item-content {
                .history-item-delete {
                    display: flex;
                }
            }
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
    width: 460px;
    height: 600px;
    min-width: 380px;
    max-width: 800px;
    min-height: 400px;
    max-height: 900px;
    background-color: #ffffff;
    box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.12);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;

    // 调整大小控制点基础样式
    .ai-assistant-resize-handle {
        position: absolute;
        z-index: 10;
    }

    // 四边控制点
    .ai-assistant-resize-n {
        top: 0;
        left: 8px;
        right: 8px;
        height: 6px;
        cursor: n-resize;
    }
    .ai-assistant-resize-s {
        bottom: 0;
        left: 8px;
        right: 8px;
        height: 6px;
        cursor: s-resize;
    }
    .ai-assistant-resize-e {
        top: 8px;
        right: 0;
        bottom: 8px;
        width: 6px;
        cursor: e-resize;
    }
    .ai-assistant-resize-w {
        top: 8px;
        left: 0;
        bottom: 8px;
        width: 6px;
        cursor: w-resize;
    }

    // 四角控制点
    .ai-assistant-resize-ne {
        top: 0;
        right: 0;
        width: 12px;
        height: 12px;
        cursor: ne-resize;
    }
    .ai-assistant-resize-nw {
        top: 0;
        left: 0;
        width: 12px;
        height: 12px;
        cursor: nw-resize;
    }
    .ai-assistant-resize-se {
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        cursor: se-resize;
    }
    .ai-assistant-resize-sw {
        bottom: 0;
        left: 0;
        width: 12px;
        height: 12px;
        cursor: sw-resize;
    }

    .ai-assistant-fullscreen {
        position: absolute;
        top: 11px;
        right: 48px;
        z-index: 1;
        width: 28px;
        height: 28px;
        padding: 4px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        svg {
            width: 100%;
            height: 100%;
            stroke: #777;
            transition: stroke 0.2s;
        }
        &:hover {
            background-color: rgba(0, 0, 0, 0.06);
            svg {
                stroke: #444;
            }
        }
    }

    .ai-assistant-close {
        position: absolute;
        top: 6px;
        right: 10px;
        z-index: 1;
        font-size: 38px;
        color: #999;
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 50%;
        overflow: hidden;
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
        margin: 6px 82px 6px 16px;

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
            display: flex;
            flex-direction: column;
            align-items: center;

            .ai-assistant-welcome-icon {
                flex-shrink: 0;
                margin-top: auto;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 52px;
                height: 52px;
                border-radius: 50%;
                background: #8bcf70;
                margin-bottom: 24px;

                svg {
                    width: 28px;
                    height: 28px;
                    fill: #fff;
                }
            }

            .ai-assistant-welcome-title {
                font-size: 16px;
                margin-bottom: 24px;
                font-weight: 500;
                color: #303133;
            }

            .ai-assistant-welcome-prompts {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px;
                max-width: 100%;
                padding: 0 8px;
                margin-bottom: auto;
            }

            .ai-assistant-prompt-card {
                min-width: 0;
                overflow: hidden;
                display: flex;
                align-items: center;
                gap: 8px;
                background: #fff;
                border: 1px solid #e8e8e8;
                border-radius: 8px;
                color: #303133;
                cursor: pointer;
                transition: all 0.2s;
                padding: 8px 12px;
                font-size: 13px;

                &:hover {
                    border-color: #8bcf70;
                    box-shadow: 0 2px 8px rgba(139, 207, 112, 0.15);
                }

                &:active {
                    transform: scale(0.98);
                }

                .ai-assistant-prompt-icon {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 16px;
                    height: 16px;
                    flex-shrink: 0;

                    svg {
                        width: 100%;
                        height: 100%;
                        stroke: #8bcf70;
                    }
                }

                > span:last-child {
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
            }
        }
    }

    .ai-assistant-input {
        padding: 4px 12px 12px;
    }

    // 全屏状态
    &.is-fullscreen {
        top: 12px;
        left: 12px;
        right: 12px;
        bottom: 12px;
        width: auto;
        height: auto;
        max-width: none;
        max-height: none;

        .ai-assistant-drag-handle {
            cursor: default;
        }
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
    .ai-assistant-content {
        max-height: calc(var(--window-height) - var(--status-bar-height) - var(--navigation-bar-height) - 266px);
        @media (height <= 900px) {
            max-height: calc(var(--window-height) - var(--status-bar-height) - var(--navigation-bar-height) - 136px);
        }
    }
}

body.dark-mode-reverse {
    .ai-assistant-content {
        .ai-assistant-welcome,
        .ai-assistant-output {
            background-color: #f5f5f5;
        }

        .ai-assistant-prompt-card {
            background: #fff;
            border-color: #d9d9d9;

            &:hover {
                background: rgba(102, 126, 234, 0.06);
            }
        }
    }
    .ai-assistant-chat {
        background-color: #e9e9e9;
        box-shadow: none;
    }
    .ai-assistant-modal {
        --apply-reasoning-before-bg: #4e4e56;
    }
}
</style>
