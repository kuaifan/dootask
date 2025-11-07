<template>
    <Modal
        v-model="showModal"
        :title="$L('AI 助手')"
        :mask-closable="false"
        :closable="false"
        :styles="{
            width: '90%',
            maxWidth: shouldCreateNewSession ? '420px' : '600px',
        }"
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
                    <div class="ai-assistant-output-meta">
                        <div class="ai-assistant-output-meta-left">
                            <span class="ai-assistant-output-model">{{ response.modelLabel || response.model }}</span>
                        </div>
                        <div class="ai-assistant-output-meta-right">
                            <template v-if="response.status === 'error'">
                                <span class="ai-assistant-output-error">{{ response.error || $L('发送失败') }}</span>
                            </template>
                            <template v-else-if="response.text">
                                <Button
                                    type="primary"
                                    size="small"
                                    :loading="response.applyLoading"
                                    class="ai-assistant-apply-btn"
                                    @click="applyResponse(response)">
                                    {{ $L('应用此内容') }}
                                </Button>
                            </template>
                            <template v-else>
                                <Icon type="ios-sync" class="ai-assistant-output-icon ai-spin"/>
                                <span class="ai-assistant-output-status">{{ $L('生成中...') }}</span>
                            </template>
                        </div>
                    </div>
                    <div v-if="response.prompt" class="ai-assistant-output-question">{{ response.prompt }}</div>
                    <DialogMarkdown
                        v-if="response.text"
                        class="ai-assistant-output-markdown no-dark-content"
                        :text="response.text"/>
                    <div v-else class="ai-assistant-output-placeholder">
                        {{ response.status === 'error' ? (response.error || $L('发送失败')) : $L('等待 AI 回复...') }}
                    </div>
                </div>
            </div>
            <div class="ai-assistant-input">
                <Input 
                    v-model="inputValue" 
                    type="textarea" 
                    :placeholder="inputPlaceholder" 
                    :rows="inputRows" 
                    :autosize="inputAutosize" 
                    :maxlength="inputMaxlength" />
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
                        :label="$L(group.label)">
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
                <Button type="primary" :loading="loadIng > 0" @click="onSubmit">{{ $L('确定') }}</Button>
            </div>
        </div>
    </Modal>
</template>

<script>
import {mapState} from "vuex";
import emitter from "../store/events";
import {AIBotList, AIModelNames} from "../utils/ai";
import DialogMarkdown from "../pages/manage/components/DialogMarkdown.vue";

export default {
    name: 'AIAssistant',
    components: {DialogMarkdown},
    props: {
        defaultPlaceholder: {
            type: String,
            default: '',
        },
        defaultInputRows: {
            type: Number,
            default: 2,
        },
        defaultInputAutosize: {
            type: Object,
            default: () => ({minRows:2, maxRows:6}),
        },
        defaultInputMaxlength: {
            type: Number,
            default: 500,
        },
    },
    data() {
        return {
            // 弹窗状态
            showModal: false,
            closing: false,
            loadIng: 0,

            // 输入配置
            inputValue: '',
            inputPlaceholder: this.defaultPlaceholder,
            inputRows: this.defaultInputRows,
            inputAutosize: this.defaultInputAutosize,
            inputMaxlength: this.defaultInputMaxlength,
            inputOnOk: null,
            inputOnBeforeSend: null,

            // 模型选择
            inputModel: '',
            modelGroups: [],
            modelMap: {},
            modelsLoading: false,
            modelCacheKey: 'aiAssistant.model',
            cachedModelId: '',

            // 响应渲染
            responses: [],
            pendingResponses: [],
            responseSeed: 1,
            maxResponses: 5,
        }
    },
    mounted() {
        emitter.on('openAIAssistant', this.onOpenAIAssistant);
        emitter.on('streamMsgData', this.onStreamMsgData);
        this.initModelCache();
    },
    beforeDestroy() {
        emitter.off('openAIAssistant', this.onOpenAIAssistant);
        emitter.off('streamMsgData', this.onStreamMsgData);
    },
    computed: {
        ...mapState([
            'cacheDialogs',
        ]),
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
            if ($A.isJson(params)) {
                this.inputValue = params.value || '';
                this.inputPlaceholder = params.placeholder || this.defaultPlaceholder || this.$L('请输入你的问题...');
                this.inputRows = params.rows || this.defaultInputRows;
                this.inputAutosize = params.autosize || this.defaultInputAutosize;
                this.inputMaxlength = params.maxlength || this.defaultInputMaxlength;
                this.inputOnOk = params.onOk || null;
                this.inputOnBeforeSend = params.onBeforeSend || null;
            }
            this.responses = [];
            this.pendingResponses = [];
            this.showModal = true;
        },

        /**
         * 初始化模型缓存与下拉数据
         */
        async initModelCache() {
            await this.loadCachedModel();
            this.fetchModelOptions();
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
            this.modelsLoading = true;
            try {
                const {data} = await this.$store.dispatch("call", {
                    url: 'system/setting/aibot_models',
                });
                this.normalizeModelOptions(data);
            } catch (error) {
                const msg = error?.msg || error?.message || error || this.$L('获取模型列表失败');
                $A.modalError(msg);
            } finally {
                this.modelsLoading = false;
            }
        },

        /**
         * 解析模型列表
         */
        normalizeModelOptions(data) {
            const groups = [];
            const map = {};
            const labelMap = AIBotList.reduce((acc, bot) => {
                acc[bot.value] = bot.label;
                return acc;
            }, {});
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
                    const label = labelMap[type] || type;
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
            const order = AIBotList.map(bot => bot.value);
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
            const content = rawValue.trim();
            if (!content) {
                $A.messageWarning(this.$L('请输入你的问题'));
                return;
            }
            const modelOption = this.selectedModelOption;
            if (!modelOption) {
                $A.messageWarning(this.$L('请选择模型'));
                return;
            }

            this.loadIng++;
            let responseEntry = null;
            try {
                const {dialogId, userid} = await this.ensureAiDialog(modelOption.type);
                if (this.shouldCreateNewSession) {
                    await this.createAiSession(dialogId);
                }
                responseEntry = this.createResponseEntry({
                    modelOption,
                    dialogId,
                    prompt: rawValue,
                });
                this.scrollResponsesToBottom();
                const message = await this.sendAiMessage(dialogId, rawValue, modelOption.value);
                if (responseEntry) {
                    responseEntry.userid = userid;
                    responseEntry.message = message;
                    responseEntry.messageId = message?.id || 0;
                }
                this.inputValue = '';
            } catch (error) {
                const msg = error?.msg || error?.message || error || this.$L('发送失败');
                if (responseEntry) {
                    this.markResponseError(responseEntry, msg);
                }
                $A.modalError(msg);
            } finally {
                this.loadIng--;
            }
        },

        /**
         * 生成AI机器人邮箱
         */
        getAiEmail(type) {
            return `ai-${type}@bot.system`;
        },

        /**
         * 在缓存会话里查找AI
         */
        findAiDialog({type, userid}) {
            const email = this.getAiEmail(type);
            return this.cacheDialogs.find(dialog => {
                if (!dialog) {
                    return false;
                }
                if (userid && dialog.dialog_user && dialog.dialog_user.userid === userid) {
                    return true;
                }
                if (dialog.dialog_user && dialog.dialog_user.email === email) {
                    return true;
                }
                if (dialog.email === email) {
                    return true;
                }
                return false;
            });
        },

        /**
         * 确保能够打开AI会话
         */
        async ensureAiDialog(type) {
            let dialog = this.findAiDialog({type});
            let userid = dialog?.dialog_user?.userid || dialog?.userid || 0;
            if (dialog) {
                return {
                    dialogId: dialog.id,
                    userid,
                };
            }
            const {data} = await this.$store.dispatch("call", {
                url: 'users/search/ai',
                data: {type},
            });
            userid = data?.userid;
            if (!userid) {
                throw new Error(this.$L('未找到AI机器人'));
            }
            const dialogResult = await this.$store.dispatch("call", {
                url: 'dialog/open/user',
                data: {userid},
                method: 'get',
            });
            dialog = dialogResult?.data || null;
            if (dialog) {
                this.$store.dispatch("saveDialog", dialog);
            }
            if (!dialog) {
                throw new Error(this.$L('AI对话打开失败'));
            }
            return {
                dialogId: dialog.id,
                userid,
            };
        },

        /**
         * 创建新的AI会话session
         */
        async createAiSession(dialogId) {
            if (!dialogId) {
                return;
            }
            await this.$store.dispatch("call", {
                url: 'dialog/session/create',
                data: {dialog_id: dialogId},
            });
            await this.$store.dispatch("clearDialogMsgs", {
                id: dialogId,
            });
        },

        /**
         * 发送文本消息
         */
        async sendAiMessage(dialogId, text, model) {
            const {data} = await this.$store.dispatch("call", {
                url: 'dialog/msg/sendtext',
                method: 'post',
                data: await this.buildPayloadData({
                    dialog_id: dialogId,
                    text,
                    model_name: model,
                }),
            });
            if (data) {
                this.$store.dispatch("saveDialogMsg", data);
                this.$store.dispatch("increaseTaskMsgNum", {id: data.dialog_id});
                if (data.reply_id) {
                    this.$store.dispatch("increaseMsgReplyNum", {id: data.reply_id});
                }
                this.$store.dispatch("updateDialogLastMsg", data);
            }
            return data;
        },

        /**
         * 构建最终发送的数据
         */
        async buildPayloadData(data) {
            if (typeof this.inputOnBeforeSend !== 'function') {
                return data;
            }
            try {
                const result = this.inputOnBeforeSend(data);
                if (result && typeof result.then === 'function') {
                    const resolved = await result;
                    if ($A.isJson(resolved)) {
                        return resolved;
                    }
                } else if ($A.isJson(result)) {
                    return result;
                }
            } catch (e) {
                console.warn('[AIAssistant] onBeforeSend error:', e);
            }
            return data;
        },

        /**
         * 新建响应卡片
         */
        createResponseEntry({modelOption, dialogId, prompt}) {
            const entry = {
                localId: this.responseSeed++,
                id: null,
                dialogId,
                model: modelOption.value,
                modelLabel: modelOption.label,
                type: modelOption.type,
                prompt: prompt.trim(),
                text: '',
                status: 'waiting',
                error: '',
                userid: 0,
                message: null,
                messageId: 0,
                applyLoading: false,
            };
            this.responses.push(entry);
            this.pendingResponses.push(entry);
            if (this.responses.length > this.maxResponses) {
                const removed = this.responses.shift();
                this.pendingResponses = this.pendingResponses.filter(item => item !== removed);
            }
            return entry;
        },

        /**
         * 处理流式输出
         */
        onStreamMsgData(data) {
            if (!data || !data.id) {
                return;
            }
            let response = this.responses.find(item => item.id === data.id);
            if (!response && data.reply_id) {
                response = this.responses.find(item => item.messageId === data.reply_id);
            }
            if (!response) {
                const index = this.pendingResponses.findIndex(item => {
                    if (data.reply_id && item.messageId) {
                        return item.messageId === data.reply_id;
                    }
                    if (data.dialog_id && item.dialogId) {
                        return item.dialogId === data.dialog_id;
                    }
                    return true;
                });
                if (index === -1) {
                    return;
                }
                response = this.pendingResponses.splice(index, 1)[0];
                response.id = data.id;
            }
            const chunk = typeof data.text === 'string' ? data.text : '';
            if (data.type === 'replace') {
                response.text = chunk;
                response.status = 'completed';
            } else {
                response.text += chunk;
                response.status = 'streaming';
            }
            this.scrollResponsesToBottom();
        },

        /**
         * 标记响应失败
         */
        markResponseError(response, msg) {
            response.status = 'error';
            response.error = msg;
            this.pendingResponses = this.pendingResponses.filter(item => item !== response);
        },

        /**
         * 将AI内容应用到父组件
         */
        applyResponse(response) {
            if (!response || response.applyLoading) {
                return;
            }
            if (!response.text) {
                $A.messageWarning(this.$L('暂无可用内容'));
                return;
            }
            if (typeof this.inputOnOk !== 'function') {
                this.closeAssistant();
                return;
            }
            response.applyLoading = true;
            const payload = {
                dialogId: response.dialogId,
                userid: response.userid,
                model: response.model,
                type: response.type,
                content: response.prompt,
                message: response.message,
                aiContent: response.text,
            };
            try {
                const result = this.inputOnOk(payload);
                if (result && typeof result.then === 'function') {
                    result.then(() => {
                        this.closeAssistant();
                    }).catch(error => {
                        const msg = error?.msg || error?.message || error || this.$L('应用失败');
                        $A.modalError(msg);
                    }).finally(() => {
                        response.applyLoading = false;
                    });
                } else {
                    this.closeAssistant();
                    response.applyLoading = false;
                }
            } catch (error) {
                response.applyLoading = false;
                const msg = error?.msg || error?.message || error || this.$L('应用失败');
                $A.modalError(msg);
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
            this.showModal = false;
            this.responses = [];
            this.pendingResponses = [];
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
    },
}
</script>

<style lang="scss">
.ai-assistant-modal {
    .ivu-modal {
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
        max-height: calc(100vh - 344px);
        @media (height <= 900px) {
            max-height: calc(100vh - 214px);
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

        .ai-assistant-output-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .ai-assistant-output-meta-left {
            flex: 1;
            min-width: 0;
        }

        .ai-assistant-output-model {
            display: inline-flex;
            align-items: center;
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

        .ai-assistant-output-meta-right {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: #999;
            gap: 4px;
        }

        .ai-assistant-output-icon {
            font-size: 16px;
            color: #2f54eb;
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
        }
    }

    .ai-assistant-footer {
        display: flex;
        justify-content: between;
        gap: 12px;
        .ai-assistant-footer-models {
            text-align: left;
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

@keyframes ai-assistant-spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.ai-spin {
    animation: ai-assistant-spin 1s linear infinite;
}
</style>
