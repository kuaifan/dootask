<template>
    <div class="chat-input-wrapper" :class="modeClass">
        <div ref="editor"></div>
        <div class="chat-input-toolbar">
            <slot name="toolbarBefore"/>

            <EPopover
                v-model="showEmoji"
                :visibleArrow="false"
                popperClass="chat-input-emoji-popover">
                <ETooltip slot="reference" :disabled="showEmoji" placement="top" :content="$L('表情')">
                    <i class="taskfont" @click="onToolbar('emoji')">&#xe7ad;</i>
                </ETooltip>
                <ChatEmoji @on-select="onSelectEmoji"/>
            </EPopover>

            <ETooltip placement="top" :content="$L('选择会员')">
                <i class="taskfont" @click="onToolbar('user')">&#xe78f;</i>
            </ETooltip>
            <ETooltip placement="top" :content="$L('选择任务')">
                <i class="taskfont" @click="onToolbar('task')">&#xe7d6;</i>
            </ETooltip>

            <EPopover
                v-model="showMore"
                :visibleArrow="false"
                popperClass="chat-input-more-popover">
                <ETooltip slot="reference" :disabled="showMore" placement="top" :content="$L('展开')">
                    <i class="taskfont">&#xe790;</i>
                </ETooltip>
                <div class="chat-input-popover-item" @click="onToolbar('image')">
                    <i class="taskfont">&#xe64a;</i>
                    {{$L('图片')}}
                </div>
                <div class="chat-input-popover-item" @click="onToolbar('file')">
                    <i class="taskfont">&#xe786;</i>
                    {{$L('文件')}}
                </div>
            </EPopover>

            <div class="toolbar-spacing"></div>

            <Loading v-if="loading"/>
            <ETooltip v-else placement="top" :content="$L('发送')"><Icon :class="[value ? '' : 'disabled']" type="md-send" @click="send"/></ETooltip>

            <slot name="toolbarAfter"/>
        </div>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";

import Quill from 'quill';
import "quill-mention";
import ChatEmoji from "./emoji";

export default {
    name: 'ChatInput',
    components: {ChatEmoji},
    props: {
        dialogId: {
            type: Number,
            default: 0
        },
        taskId: {
            type: Number,
            default: 0
        },
        value: {
            type: [String, Number],
            default: ''
        },
        placeholder: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        },
        loading: {
            type: Boolean,
            default: false
        },
        enterSend: {
            type: Boolean,
            default: true
        },
        options: {
            type: Object,
            default: () => ({})
        },
        maxlength: {
            type: Number
        },
        defaultMenuOrientation: {
            type: String,
            default: "top"
        },
    },
    data() {
        return {
            quill: null,
            _content: '',
            _options: {},

            modeClass: '',

            userList: null,
            taskList: null,

            showMore: false,
            showEmoji: false,
        };
    },
    mounted() {
        this.init();
    },
    beforeDestroy() {
        this.quill = null
        delete this.quill
    },
    computed: {
        ...mapState(['cacheProjects', 'cacheTasks', 'cacheUserBasic', 'userId']),

        ...mapGetters(['dashboardTask', 'transforTasks']),
    },
    watch: {
        // Watch content change
        value(newVal) {
            if (this.quill) {
                if (newVal && newVal !== this._content) {
                    this._content = newVal
                    this.quill.pasteHTML(newVal)
                } else if(!newVal) {
                    this.quill.setText('')
                }
            }
        },

        // Watch disabled change
        disabled(newVal) {
            if (this.quill) {
                this.quill.enable(!newVal)
            }
        },

        // Reset lists
        dialogId() {
            this.userList = null;
            this.taskList = null;
        },
        taskId() {
            this.userList = null;
            this.taskList = null;
        },
    },
    methods: {
        init() {
            // Options
            this._options = Object.assign({
                theme: null,
                readOnly: false,
                placeholder: this.placeholder,
                modules: {
                    keyboard: {
                        bindings: {
                            'short enter': {
                                key: 13,
                                shortKey: true,
                                handler: _ => {
                                    if (!this.enterSend) {
                                        this.send();
                                        return false;
                                    }
                                    return true;
                                }
                            },
                            'enter': {
                                key: 13,
                                shiftKey: false,
                                handler: _ => {
                                    if (this.enterSend) {
                                        this.send();
                                        return false;
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    mention: {
                        allowedChars: /^\S*$/,
                        mentionDenotationChars: ["@", "#"],
                        defaultMenuOrientation: this.defaultMenuOrientation,
                        isolateCharacter: true,
                        renderItem: (data) => {
                            if (data.disabled === true) {
                                return `<div class="mention-item-disabled">${data.value}</div>`;
                            }
                            if (data.id === 0) {
                                return `<div class="mention-item-at">@</div><div class="mention-item-name">${data.value}</div><div class="mention-item-tip">${this.$L('提示所有成员')}</div>`;
                            }
                            if (data.avatar) {
                                return `<div class="mention-item-img${data.online ? ' online' : ''}"><img src="${data.avatar}"/><em></em></div><div class="mention-item-name">${data.value}</div>`;
                            }
                            return `<div class="mention-item-name" title="${data.value}">${data.value}</div>`;
                        },
                        renderLoading: () => {
                            return "Loading...";
                        },
                        source: (searchTerm, renderList, mentionChar) => {
                            this.getSource(mentionChar).then(array => {
                                let values = [];
                                array.some(item => {
                                    let list = item.list;
                                    if (searchTerm && !item.ignoreSearch) {
                                        list = list.filter(({value}) => $A.strExists(value, searchTerm));
                                    }
                                    if (list.length > 0 || item.ignoreSearch) {
                                        item.label && values.push(...item.label)
                                        list.length > 0 && values.push(...list)
                                    }
                                })
                                renderList(values, searchTerm);
                            })
                        }
                    }
                }
            }, this.options)

            // Instance
            this.quill = new Quill(this.$refs.editor, this._options)
            this.quill.enable(false)

            // Set editor content
            if (this.value) {
                this.quill.pasteHTML(this.value)
            }

            // Disabled editor
            if (!this.disabled) {
                this.quill.enable(true)
            }

            // Mark model as touched if editor lost focus
            this.quill.on('selection-change', range => {
                if (!range) {
                    this.$emit('on-blur', this.quill)
                } else {
                    this.$emit('on-focus', this.quill)
                }
            })

            // Update model if text changes
            this.quill.on('text-change', _ => {
                if (this.maxlength > 0 && this.quill.getLength() > this.maxlength) {
                    this.quill.deleteText(this.maxlength, this.quill.getLength());
                }
                let html = this.$refs.editor.children[0].innerHTML
                const quill = this.quill
                const text = this.quill.getText()
                if (/^(\<p\>\<br\>\<\/p\>)+$/.test(html)) html = ''
                this._content = html
                this.$emit('input', this._content)
                this.$emit('on-change', { html, text, quill })
            })

            // Emit ready event
            this.$emit('on-ready', this.quill)
        },

        focus() {
            this.$nextTick(() => {
                this.quill && this.quill.focus()
            })
        },

        blur() {
            this.$nextTick(() => {
                this.quill && this.quill.blur()
            })
        },

        send() {
            this.$emit('on-send')
        },

        onSelectEmoji(item) {
            if (!this.quill) {
                return;
            }
            if (item.type === 'emoji') {
                let element = document.createElement('span');
                element.innerHTML = item.html;
                this.quill.insertText(this.quill.getSelection(true).index, element.innerHTML);
                element = null;
            } else if (item.type === 'emoticon') {
                this.$emit('on-send', `<img class="emoticon" data-asset="${item.asset}" data-name="${item.name}" src="${item.src}"/>`)
            }
            this.showEmoji = false;
        },

        onToolbar(action) {
            switch (action) {
                case 'user':
                    this.openMenu("@");
                    break;

                case 'task':
                    this.openMenu("#");
                    break;

                case 'image':
                case 'file':
                    this.$emit('on-more', action)
                    break;
            }
        },

        onMoreVisibleChange(v) {
            this.showMore = v;
        },

        openMenu(char) {
            if (!this.quill) {
                return;
            }
            if (this.value.length === 0 || this.value.endsWith("<p><br></p>")) {
                this.quill.getModule("mention").openMenu(char);
            } else {
                let str = this.value.replace(/<[^>]+>/g,"");
                if (str.length === 0 || str.endsWith(" ")) {
                    this.quill.getModule("mention").openMenu(char);
                } else {
                    this.quill.getModule("mention").openMenu(` ${char}`);
                }
            }
        },

        getProjectId() {
            let object = null;
            if (this.dialogId > 0) {
                object = this.cacheProjects.find(({dialog_id}) => dialog_id == this.dialogId);
                if (object) {
                    return object.id;
                }
                object = this.cacheTasks.find(({dialog_id}) => dialog_id == this.dialogId);
                if (object) {
                    return object.project_id;
                }
            } else if (this.taskId > 0) {
                object = this.cacheTasks.find(({id}) => id == this.taskId);
                if (object) {
                    return object.project_id;
                }
            }
            return 0;
        },

        getSource(mentionChar) {
            return new Promise(resolve => {
                switch (mentionChar) {
                    case "@": // @成员
                        this.modeClass = "user-mention";
                        if (this.userList !== null) {
                            resolve(this.userList)
                            return;
                        }
                        const atCallback = (list) => {
                            if (list.length > 2) {
                                this.userList = [{
                                    ignoreSearch: true,
                                    label: null,
                                    list: [{id: 0, value: this.$L('所有人')}]
                                }, {
                                    ignoreSearch: false,
                                    label: [{id: 0, value: this.$L('会话内成员'), disabled: true}],
                                    list,
                                }]
                            } else {
                                this.userList = [{
                                    ignoreSearch: false,
                                    label: null,
                                    list
                                }]
                            }
                            resolve(this.userList)
                        }
                        let array = [];
                        if (this.dialogId > 0) {
                            // 根据会话ID获取成员
                            this.$store.dispatch("call", {
                                url: 'dialog/group/user',
                                data: {
                                    dialog_id: this.dialogId,
                                    getuser: 1
                                }
                            }).then(({data}) => {
                                if (data.length > 0) {
                                    array.push(...data.map(item => {
                                        return {
                                            id: item.userid,
                                            value: item.nickname,
                                            avatar: item.userimg,
                                            online: item.online,
                                        }
                                    }))
                                }
                                atCallback(array)
                            }).catch(_ => {
                                atCallback(array)
                            });
                        } else if (this.taskId > 0) {
                            // 根据任务ID获取成员
                            const task = this.cacheTasks.find(({id}) => id == this.taskId)
                            if (task && $A.isArray(task.task_user)) {
                                task.task_user.some(tmp => {
                                    let item = this.cacheUserBasic.find(({userid}) => userid == tmp.userid);
                                    if (item) {
                                        array.push({
                                            id: item.userid,
                                            value: item.nickname,
                                            avatar: item.userimg,
                                            online: item.online,
                                        })
                                    }
                                })
                            }
                            atCallback(array)
                        }
                        break;

                    case "#": // #任务
                        this.modeClass = "task-mention";
                        if (this.taskList !== null) {
                            resolve(this.taskList)
                            return;
                        }
                        const taskCallback = (list) => {
                            this.taskList = [];
                            // 项目任务
                            if (list.length > 0) {
                                list = list.map(item => {
                                    return {
                                        id: item.id,
                                        value: item.name
                                    }
                                })
                                this.taskList.push({
                                    ignoreSearch: false,
                                    label: [{id: 0, value: this.$L('项目未完成任务'), disabled: true}],
                                    list,
                                })
                            }
                            // 待完成任务
                            let data = this.transforTasks(this.dashboardTask['all']);
                            if (data.length > 0) {
                                data = data.sort((a, b) => {
                                    return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                                })
                                this.taskList.push({
                                    ignoreSearch: false,
                                    label: [{id: 0, value: this.$L('我的待完成任务'), disabled: true}],
                                    list: data.map(item => {
                                        return {
                                            id: item.id,
                                            value: item.name
                                        }
                                    }),
                                })
                            }
                            resolve(this.taskList)
                        }
                        //
                        const projectId = this.getProjectId();
                        if (projectId > 0) {
                            this.$store.dispatch("getTaskForProject", projectId).then(_ => {
                                let tasks = this.cacheTasks.filter(task => {
                                    if (task.archived_at) {
                                        return false;
                                    }
                                    return task.project_id == projectId
                                        && task.parent_id === 0
                                        && !task.archived_at
                                        && !task.complete_at
                                })
                                if (tasks.length > 0) {
                                    taskCallback(tasks);
                                } else {
                                    taskCallback([])
                                }
                            }).catch(_ => {
                                taskCallback([])
                            })
                            return;
                        }
                        taskCallback([])
                        break;

                    default:
                        resolve([])
                        break;
                }
            })
        },
    }
}
</script>
