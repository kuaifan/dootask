<template>
    <Modal
        v-model="showModal"
        :title="$L('AI 助手')"
        :mask-closable="false"
        :closable="false"
        :styles="{
            width: '90%',
            maxWidth: '420px'
        }"
        class-name="ai-assistant-modal">
        <div class="ai-assistant-content">
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

export default {
    name: 'AIAssistant',
    props: {
        defaultPlaceholder: {
            type: String,
            default: '',
        },
        defaultInputRows: {
            type: Number,
            default: 1,
        },
        defaultInputAutosize: {
            type: Object,
            default: () => ({minRows:1, maxRows:6}),
        },
        defaultInputMaxlength: {
            type: Number,
            default: 500,
        },
    },
    data() {
        return {
            showModal: false,
            loadIng: 0,
            
            inputValue: '',
            inputModel: '',
            modelGroups: [],
            modelMap: {},
            modelsLoading: false,
            inputPlaceholder: this.defaultPlaceholder,
            inputRows: this.defaultInputRows,
            inputAutosize: this.defaultInputAutosize,
            inputMaxlength: this.defaultInputMaxlength,
            inputOnOk: null,
        }
    },
    mounted() {
        emitter.on('openAIAssistant', this.onOpenAIAssistant);
        this.onOpenAIAssistant({})
        this.fetchModelOptions();
    },
    beforeDestroy() {
        emitter.off('openAIAssistant', this.onOpenAIAssistant);
    },
    computed: {
        ...mapState([
            'cacheDialogs',
        ]),
        selectedModelOption({modelMap, inputModel}) {
            return modelMap[inputModel] || null;
        },
    },
    methods: {
        onOpenAIAssistant(params) {
            if ($A.isJson(params)) {
                this.inputValue = params.value || '';
                this.inputPlaceholder = params.placeholder || this.defaultPlaceholder || this.$L('请输入你的问题...');
                this.inputRows = params.rows || this.defaultInputRows;
                this.inputAutosize = params.autosize || this.defaultInputAutosize;
                this.inputMaxlength = params.maxlength || this.defaultInputMaxlength;
                this.inputOnOk = params.onOk || null;
            }
            this.showModal = true;
        },

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

        ensureSelectedModel() {
            if (this.inputModel && this.modelMap[this.inputModel]) {
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

        async onSubmit() {
            if (this.loadIng > 0) {
                return;
            }
            const content = (this.inputValue || '').trim();
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
            try {
                const {dialogId, userid} = await this.ensureAiDialog(modelOption.type);
                await this.createAiSession(dialogId);
                const message = await this.sendAiMessage(dialogId, this.formatPlainText(this.inputValue), modelOption.value);
                if (typeof this.inputOnOk === 'function') {
                    this.inputOnOk({
                        dialogId,
                        userid,
                        model: modelOption.value,
                        type: modelOption.type,
                        content: this.inputValue,
                        message,
                    });
                }
                this.showModal = false;
                this.inputValue = '';
            } catch (error) {
                const msg = error?.msg || error?.message || error || this.$L('发送失败');
                $A.modalError(msg);
            } finally {
                this.loadIng--;
            }
        },

        getAiEmail(type) {
            return `ai-${type}@bot.system`;
        },

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

        sleep(ms = 150) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },

        async ensureAiDialog(type) {
            let dialog = this.findAiDialog({type});
            if (dialog) {
                await this.$store.dispatch("openDialog", dialog.id);
                return {
                    dialogId: dialog.id,
                    userid: dialog.dialog_user?.userid || dialog.userid || 0,
                };
            }
            const {data} = await this.$store.dispatch("call", {
                url: 'users/search/ai',
                data: {type},
            });
            const userid = data?.userid;
            if (!userid) {
                throw new Error(this.$L('未找到AI机器人'));
            }
            await this.$store.dispatch("openDialogUserid", userid);
            const retries = 5;
            for (let i = 0; i < retries; i++) {
                dialog = this.findAiDialog({type, userid});
                if (dialog) {
                    break;
                }
                await this.sleep();
            }
            if (!dialog) {
                throw new Error(this.$L('AI对话打开失败'));
            }
            return {
                dialogId: dialog.id,
                userid,
            };
        },

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

        async sendAiMessage(dialogId, text, model) {
            const {data} = await this.$store.dispatch("call", {
                url: 'dialog/msg/sendtext',
                method: 'post',
                data: {
                    dialog_id: dialogId,
                    text,
                    model_name: model,
                },
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

        formatPlainText(text) {
            const escaped = `${text}`
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/\n/g, '<br/>');
            return `<p>${escaped}</p>`;
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
</style>
