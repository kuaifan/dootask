<template>
    <div class="chat-input-box" :class="boxClass" v-clickoutside="hidePopover">
        <div class="chat-input-wrapper" @click.stop="focus">
            <!-- 回复 -->
            <div v-if="replyData" class="chat-reply">
                <UserAvatar :userid="replyData.userid" :show-icon="false" :show-name="true" :tooltip-disabled="true"/>
                <div class="reply-desc">{{formatMsgDesc(replyData)}}</div>
                <i class="taskfont" @click.stop="onCancelReply">&#xe6e5;</i>
            </div>

            <!-- 输入框 -->
            <div
                ref="editor"
                class="no-dark-content"
                :style="editorStyle"
                @click.stop=""
                @paste="handlePaste"></div>

            <!-- 工具栏 -->
            <ul class="chat-toolbar" @click.stop="">
                <!-- 桌面端表情（漂浮） -->
                <li>
                    <EPopover
                        v-if="!emojiBottom"
                        v-model="showEmoji"
                        :visibleArrow="false"
                        placement="top"
                        popperClass="chat-input-emoji-popover">
                        <ETooltip slot="reference" ref="emojiTip" :disabled="windowSmall || showEmoji" placement="top" :content="$L('表情')">
                            <i class="taskfont">&#xe7ad;</i>
                        </ETooltip>
                        <ChatEmoji @on-select="onSelectEmoji"/>
                    </EPopover>
                    <ETooltip v-else ref="emojiTip" :disabled="windowSmall || showEmoji" placement="top" :content="$L('表情')">
                        <i class="taskfont" @click="showEmoji=!showEmoji">&#xe7ad;</i>
                    </ETooltip>
                </li>

                <!-- @ # -->
                <li>
                    <ETooltip placement="top" :disabled="windowSmall" :content="$L('选择会员')">
                        <i class="taskfont" @click="onToolbar('user')">&#xe78f;</i>
                    </ETooltip>
                </li>
                <li>
                    <ETooltip placement="top" :disabled="windowSmall" :content="$L('选择任务')">
                        <i class="taskfont" @click="onToolbar('task')">&#xe7d6;</i>
                    </ETooltip>
                </li>

                <!-- 图片文件 -->
                <li>
                    <EPopover
                        v-model="showMore"
                        :visibleArrow="false"
                        placement="top"
                        popperClass="chat-input-more-popover">
                        <ETooltip slot="reference" ref="moreTip" :disabled="windowSmall || showMore" placement="top" :content="$L('展开')">
                            <i class="taskfont">&#xe790;</i>
                        </ETooltip>
                        <div v-if="recordReady" class="chat-input-popover-item" @click="onToolbar('meeting')">
                            <i class="taskfont">&#xe7c1;</i>
                            {{$L('新会议')}}
                        </div>
                        <div class="chat-input-popover-item" @click="onToolbar('image')">
                            <i class="taskfont">&#xe7bc;</i>
                            {{$L('发送图片')}}
                        </div>
                        <div class="chat-input-popover-item" @click="onToolbar('file')">
                            <i class="taskfont">&#xe7c0;</i>
                            {{$L('上传文件')}}
                        </div>
                    </EPopover>
                </li>

                <!-- 发送按钮 -->
                <li class="chat-send" :class="sendClass" v-touchmouse="clickSend">
                    <ETooltip placement="top" :disabled="windowSmall" :content="$L('发送')">
                        <div v-if="loading">
                            <div class="chat-load">
                                <Loading/>
                            </div>
                        </div>
                        <div v-else>
                            <transition name="mobile-send">
                                <i v-if="sendClass === 'recorder'" class="taskfont">&#xe609;</i>
                            </transition>
                            <transition name="mobile-send">
                                <i v-if="sendClass !== 'recorder'" class="taskfont">&#xe606;</i>
                            </transition>
                        </div>
                    </ETooltip>
                </li>

                <!-- 录音效果 -->
                <li v-if="recordReady" class="chat-record-recwave">
                    <div ref="recwave"></div>
                </li>
            </ul>

            <!-- 覆盖层 -->
            <div class="chat-cover" @click.stop="onClickCover"></div>
        </div>

        <!-- 移动端表情（底部） -->
        <ChatEmoji v-if="emojiBottom && showEmoji" @on-select="onSelectEmoji"/>

        <!-- 录音浮窗 -->
        <transition name="fade">
            <div
                v-if="['ready', 'ing'].includes(recordState)"
                v-transfer-dom
                :data-transfer="true"
                class="chat-input-record-transfer"
                :class="{cancel: touchLimitY}"
                :style="recordTransferStyle"
                @click="stopRecord">
                <div v-if="recordDuration > 0" class="record-duration">{{recordFormatDuration}}</div>
                <div v-else class="record-loading"><Loading/></div>
                <div class="record-cancel" @click.stop="stopRecord(true)">{{$L(touchLimitY ? '松开取消' : '向上滑动取消')}}</div>
            </div>
        </transition>
    </div>
</template>

<script>
import {mapState} from "vuex";
import Quill from 'quill';
import "quill-mention-hi";
import ChatEmoji from "./emoji";
import touchmouse from "../../../../directives/touchmouse";
import TransferDom from "../../../../directives/transfer-dom";
import clickoutside from "../../../../directives/clickoutside";
import {Store} from "le5le-store";
import {scrollPreventThrough, msgSimpleDesc} from "../../../../functions/utils";

export default {
    name: 'ChatInput',
    components: {ChatEmoji},
    directives: {touchmouse, TransferDom, clickoutside},
    props: {
        value: {
            type: [String, Number],
            default: ''
        },
        dialogId: {
            type: Number,
            default: 0
        },
        taskId: {
            type: Number,
            default: 0
        },
        placeholder: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        },
        disabledRecord: {
            type: Boolean,
            default: false
        },
        loading: {
            type: Boolean,
            default: false
        },
        enterSend: {
            type: [String, Boolean],
            default: null
        },
        emojiBottom: {
            type: Boolean,
            default: false
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
        replyId: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            quill: null,
            isFocus: false,
            rangeIndex: 0,
            _content: '',
            _options: {},

            mentionMode: '',

            userList: null,
            taskList: null,

            showMore: false,
            showEmoji: false,

            observer: null,
            wrapperWidth: 0,
            editorHeight: 0,

            recordReady: false,
            recordRec: null,
            recordBlob: null,
            recordWave: null,
            recordInter: null,
            recordState: "stop",
            recordDuration: 0,

            touchStart: {},
            touchLimitX: false,
            touchLimitY: false,

            isSpecVersion: this.checkIOSVersion(),
        };
    },
    mounted() {
        this.init();
        //
        this.observer = new ResizeObserver(entries => {
            entries.some(({target, contentRect}) => {
                if (target === this.$el) {
                    this.wrapperWidth = contentRect.width;
                } else if (target === this.$refs.editor) {
                    this.editorHeight = contentRect.height;
                }
            })
        });
        this.observer.observe(this.$el);
        this.observer.observe(this.$refs.editor);
        //
        this.recordInter = setInterval(_ => {
            if (this.recordState === 'ing') {
                // 录音中，但录音时长不增加则取消录音
                if (this.__recordDuration && this.__recordDuration === this.recordDuration) {
                    this.__recordDuration = null;
                    this.stopRecord(true);
                    $A.messageWarning("录音失败，请重试")
                } else {
                    this.__recordDuration = this.recordDuration;
                }
            }
        }, 1000)
        //
        if (this.$isEEUiApp) {
            window.__onPermissionRequest = (type, result) => {
                console.log(type, result);
                if (type === 'recordAudio' && result === false) {
                    // Android 录音权限被拒绝了
                    this.stopRecord(true);
                }
            }
        }
    },
    beforeDestroy() {
        if (this.quill) {
            this.quill = null
        }
        if (this.observer) {
            this.observer.disconnect()
            this.observer = null
        }
        if (this.recordInter) {
            clearInterval(this.recordInter)
        }
    },
    computed: {
        ...mapState(['dialogInputCache', 'cacheProjects', 'cacheTasks', 'cacheUserBasic', 'dialogMsgs']),

        isEnterSend() {
            if (typeof this.enterSend === "boolean") {
                return this.enterSend;
            } else {
                return this.$store.state.windowLarge
            }
        },

        editorStyle() {
            const {wrapperWidth, editorHeight} = this;
            if (wrapperWidth > 0
                && editorHeight > 0
                && (wrapperWidth < 280 || editorHeight > 40)) {
                return {
                    width: '100%'
                };
            } else {
                return {};
            }
        },

        recordTransferStyle() {
            const {windowScrollY} = this;
            return windowScrollY > 0 ? {
                marginTop: (windowScrollY / 2) + 'px'
            } : null
        },

        boxClass() {
            const array = [];
            if (['ready', 'ing'].includes(this.recordState)) {
                if (this.recordState === 'ing' && this.recordDuration > 0) {
                    array.push('record-progress');
                } else {
                    array.push('record-ready');
                }
            }
            if (this.showMore) {
                array.push('show-more');
            }
            if (this.showEmoji) {
                array.push('show-emoji');
            }
            if (this.mentionMode) {
                array.push(this.mentionMode);
            }
            return array
        },

        sendClass() {
            if (this.value) {
                return 'sender';
            }
            if (this.recordReady) {
                return 'recorder'
            }
            return ''
        },

        recordFormatDuration() {
            const {recordDuration} = this;
            let minute = Math.floor(recordDuration / 60000),
                seconds = Math.floor(recordDuration / 1000) % 60,
                millisecond = ("00" + recordDuration % 1000).substr(-2)
            if (minute < 10) minute = `0${minute}`
            if (seconds < 10) seconds = `0${seconds}`
            return `${minute}:${seconds}″${millisecond}`
        },

        replyData() {
            const {replyId} = this;
            if (replyId > 0) {
                return this.dialogMsgs.find(item => item.id === replyId)
            }
            return null;
        }
    },
    watch: {
        // Watch content change
        value(val) {
            if (this.quill) {
                if (val && val !== this._content) {
                    this._content = val
                    this.setContent(val)
                } else if(!val) {
                    this.quill.setText('')
                }
            }
            this.setInputCache(val)
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
            this.$emit('input', this.getInputCache())
        },
        taskId() {
            this.userList = null;
            this.taskList = null;
            this.$emit('input', this.getInputCache())
        },

        showEmoji(val) {
            if (val) {
                this.showMore = false;
                if (this.quill) {
                    const range = this.quill.selection.savedRange;
                    this.rangeIndex = range ? range.index : 0
                }
            }
            if (!val && this.$refs.emojiTip) {
                this.$refs.emojiTip.updatePopper()
            }
            this.$emit('on-emoji-visible-change', val)
        },

        showMore(val) {
            if (val) {
                this.showEmoji = false;
            }
            if (!val && this.$refs.moreTip) {
                this.$refs.moreTip.updatePopper()
            }
            this.$emit('on-more-visible-change', val)
        },

        isFocus(val) {
            if (this.timerScroll) {
                clearInterval(this.timerScroll);
            }
            if (val) {
                this.$emit('on-focus')
                this.hidePopover()
                if (this.isSpecVersion) {
                    // ios11.0-11.3 对scrollTop及scrolIntoView解释有bug
                    // 直接执行会导致输入框滚到底部被遮挡
                } else if (this.windowSmall) {
                    this.timerScroll = setInterval(() => {
                        if (this.quill?.hasFocus()) {
                            this.windowScrollY > 0 && $A.scrollIntoViewIfNeeded(this.$refs.editor);
                        } else {
                            clearInterval(this.timerScroll);
                        }
                    }, 200);
                }
            } else {
                this.$emit('on-blur')
            }
        },

        recordState(state) {
            this.$emit('on-record-state', state)
        },

        dialogInputCache() {
            this.$emit('input', this.getInputCache())
        }
    },
    methods: {
        init() {
            // Options
            this._options = Object.assign({
                theme: 'bubble',
                readOnly: false,
                placeholder: this.placeholder,
                modules: {
                    toolbar: [
                        ['bold', 'strike', 'italic', 'underline', {'list': 'ordered'}, {'list': 'bullet'}, 'blockquote', 'code-block']
                    ],
                    keyboard: {
                        bindings: {
                            'short enter': {
                                key: 13,
                                shortKey: true,
                                handler: _ => {
                                    if (!this.isEnterSend) {
                                        this.onSend();
                                        return false;
                                    }
                                    return true;
                                }
                            },
                            'enter': {
                                key: 13,
                                shiftKey: false,
                                handler: _ => {
                                    if (this.isEnterSend) {
                                        this.onSend();
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
                        positioningStrategy: 'fixed',
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
                            const mentionName = mentionChar == "@" ? 'user-mention' : 'task-mention';
                            const containers = document.getElementsByClassName("ql-mention-list-container");
                            for (let i = 0; i < containers.length; i++) {
                                containers[i].classList.remove("user-mention");
                                containers[i].classList.remove("task-mention");
                                containers[i].classList.add(mentionName);
                                scrollPreventThrough(containers[i]);
                            }
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
                this.setContent(this.value)
            } else {
                this.$emit('input', this.getInputCache())
            }

            // Disabled editor
            if (!this.disabled) {
                this.quill.enable(true)
            }

            // Mark model as touched if editor lost focus
            this.quill.on('selection-change', range => {
                if (!range) {
                    // 修复光标会超出的问题
                    if (this.quill.hasFocus()) {
                        this.quill.setSelection(0)
                        return
                    }
                    if (document.activeElement && document.activeElement.className === 'ql-clipboard') {
                        this.quill.setSelection(this.quill.getLength())
                        return
                    }
                }
                this.isFocus = !!range;
            })

            // Update model if text changes
            this.quill.on('text-change', _ => {
                if (this.maxlength > 0 && this.quill.getLength() > this.maxlength) {
                    this.quill.deleteText(this.maxlength, this.quill.getLength());
                }
                let html = this.$refs.editor.children[0].innerHTML
                html = html.replace(/^(<p>\s*<\/p>)+|(<p>\s*<\/p>)+$/gi, '')
                html = html.replace(/^(<p><br\/*><\/p>)+|(<p><br\/*><\/p>)+$/gi, '')
                this._content = html
                this.$emit('input', this._content)
                this.$nextTick(_ => {
                    const range = this.quill.getSelection();
                    if (range) {
                        const endText = this.quill.getText(range.index);
                        /\n\n/.test(endText) && this.quill.deleteText(range.index, 1);
                    }
                })
            })

            // Clipboard Matcher (保留图片跟空格，清除其余所以样式)
            this.quill.clipboard.addMatcher(Node.ELEMENT_NODE, (node, delta) => {
                delta.ops = delta.ops.map(op => {
                    const obj = {
                        attributes: {},
                        insert: op.insert
                    };
                    if (op.attributes) {
                        ['bold', 'strike', 'italic', 'underline', 'list', 'blockquote', 'link'].some(item => {
                            if (op.attributes[item]) {
                                obj.attributes[item] = op.attributes[item]
                            }
                        })
                    }
                    return obj
                })
                return delta
            })

            // Ready event
            this.$emit('on-ready', this.quill)

            // Load recorder
            if (!this.disabledRecord) {
                $A.loadScriptS([
                    'js/recorder/recorder.mp3.min.js',
                    'js/recorder/lib.fft.js',
                    'js/recorder/frequency.histogram.view.js',
                ], (e) => {
                    if (e !== null || typeof window.Recorder !== 'function') {
                        return;
                    }
                    this.recordRec = window.Recorder({
                        type: "mp3",
                        bitRate: 32,
                        sampleRate: 16000,
                        onProcess: (buffers, powerLevel, duration, sampleRate, newBufferIdx, asyncEnd) => {
                            this.recordWave.input(buffers[buffers.length - 1], powerLevel, sampleRate);
                            this.recordDuration = duration;
                            if (duration >= 3 * 60 * 1000) {
                                // 最长录3分钟
                                this.stopRecord(false);
                            }
                        }
                    })
                    if (window.Recorder.Support()) {
                        this.recordReady = true;
                        this.$nextTick(_ => {
                            this.recordWave = window.Recorder.FrequencyHistogramView({
                                elem: this.$refs.recwave,
                                lineCount: 90,
                                position: 0,
                                minHeight: 1,
                                stripeEnable: false
                            })
                        })
                    }
                });
            }
        },

        setText(value) {
            if (this.quill) {
                this.quill.setText(value)
            }
        },

        setContent(value) {
            if (this.quill) {
                this.quill.setContents(this.quill.clipboard.convert(value))
            }
        },

        getInputCache() {
            const key = this.dialogId || this.taskId;
            const item = this.dialogInputCache.find(item => item.key == key);
            return item ? item.cache : '';
        },

        setInputCache(cache) {
            const key = this.dialogId || this.taskId;
            const index = this.dialogInputCache.findIndex(item => item.key == key);
            const data = {key, cache}
            if (index > -1) {
                this.$store.state.dialogInputCache.splice(index, 1, data)
            } else {
                this.$store.state.dialogInputCache.push(data)
            }
            this.__setInputCache && clearTimeout(this.__setInputCache);
            this.__setInputCache = setTimeout(_ => {
                $A.setStorage("cacheDialogInput", this.$store.state.dialogInputCache);
            }, 600)
        },

        focus() {
            this.$nextTick(() => {
                if (this.quill) {
                    this.quill.setSelection(this.quill.getLength())
                    this.quill.focus()
                }
            })
        },

        blur() {
            this.$nextTick(() => {
                this.quill && this.quill.blur()
            })
        },

        clickSend(action, event) {
            if (this.loading) {
                return;
            }
            switch (action) {
                case 'down':
                    this.touchLimitX = false;
                    this.touchLimitY = false;
                    this.touchStart = event.type === "touchstart" ? event.touches[0] : event;
                    if (this.startRecord()) {
                        return;
                    }
                    break;

                case 'move':
                    const touchMove = event.type === "touchmove" ? event.touches[0] : event;
                    this.touchLimitX = (this.touchStart.clientX - touchMove.clientX) / window.innerWidth > 0.1
                    this.touchLimitY = (this.touchStart.clientY - touchMove.clientY) / window.innerHeight > 0.1
                    break;

                case 'up':
                    if (this.stopRecord(this.touchLimitY)) {
                        return;
                    }
                    if (this.touchLimitY || this.touchLimitX) {
                        return; // 移动了 X、Y 轴
                    }
                    this.onSend()
                    break;
            }
        },

        onSend() {
            this.rangeIndex = 0
            this.$emit('on-send')
        },

        startRecord() {
            if (this.sendClass === 'recorder') {
                this.recordDuration = 0;
                this.recordState = "ready";
                this.$nextTick(_ => {
                    this.recordRec.open(_ => {
                        if (this.recordState === "ready") {
                            this.recordState = "ing"
                            this.recordBlob = null
                            setTimeout(_ => {
                                this.recordRec.start()
                            }, 300)
                        } else {
                            this.recordRec.close();
                        }
                    }, (msg) => {
                        this.recordState = "stop";
                        $A.messageError(msg || '打开录音失败')
                    });
                })
                return true;
            } else {
                return false;
            }
        },

        stopRecord(isCancel) {
            switch (this.recordState) {
                case "ing":
                    this.recordState = "stop";
                    this.recordRec.stop((blob, duration) => {
                        this.recordRec.close();
                        if (isCancel === true) {
                            return;
                        }
                        if (duration < 600) {
                            $A.messageWarning("说话时间太短") // 小于 600ms 不发送
                        } else {
                            this.recordBlob = blob;
                            this.uploadRecord(duration);
                        }
                    }, (msg) => {
                        this.recordRec.close();
                        $A.messageError(msg || "录音失败");
                    });
                    return true;

                case "ready":
                    this.recordState = "stop";
                    return true;

                default:
                    this.recordState = "stop";
                    return false;
            }
        },

        hidePopover() {
            this.showEmoji = false;
            this.showMore = false;
        },

        onClickCover() {
            this.hidePopover();
            this.$nextTick(_ => {
                this.quill?.focus()
            })
        },

        uploadRecord(duration) {
            if (this.recordBlob === null) {
                return;
            }
            const reader = new FileReader();
            reader.onloadend = () => {
                this.$emit('on-record', {
                    type: this.recordBlob.type,
                    base64: reader.result,
                    duration,
                })
            };
            reader.readAsDataURL(this.recordBlob);
        },

        onSelectEmoji(item) {
            if (!this.quill) {
                return;
            }
            if (item.type === 'emoji') {
                let element = document.createElement('span');
                element.innerHTML = item.html;
                this.quill.insertText(this.rangeIndex, element.innerHTML);
                this.rangeIndex += element.innerHTML.length
                element = null;
                if (this.windowLarge) {
                    this.showEmoji = false;
                    this.quill.setSelection(this.rangeIndex)
                }
            } else if (item.type === 'emoticon') {
                this.$emit('on-send', `<img class="emoticon" data-asset="${item.asset}" data-name="${item.name}" src="${item.src}"/>`)
                this.showEmoji = false;
            }
        },

        onCancelReply() {
            this.$emit('on-cancel-reply')
        },

        onToolbar(action) {
            this.hidePopover();
            switch (action) {
                case 'user':
                    this.openMenu("@");
                    break;

                case 'task':
                    this.openMenu("#");
                    break;

                case 'meeting':
                    Store.set('addMeeting', {
                        type: 'create',
                        dialog_id: this.dialogId,
                        userids: [this.userId],
                    });
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
                        this.mentionMode = "user-mention";
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
                                url: 'dialog/user',
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
                        this.mentionMode = "task-mention";
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
                            let data = this.$store.getters.transforTasks(this.$store.getters.dashboardTask['all']);
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

        checkIOSVersion() {
            let ua = window && window.navigator && window.navigator.userAgent;
            let match = ua.match(/OS ((\d+_?){2,3})\s/i);
            let IOSVersion = match ? match[1].replace(/_/g, ".") : "unknown";
            const iosVsn = IOSVersion.split(".");
            return +iosVsn[0] == 11 && +iosVsn[1] >= 0 && +iosVsn[1] < 3;
        },

        handlePaste(e) {
            const {files} = e.clipboardData;
            const postFiles = Array.prototype.slice.call(files).filter(file => !$A.leftExists(file.type, 'image/'));
            if (postFiles.length > 0) {
                e.preventDefault()
                this.$emit('on-file', postFiles)
            }
        },

        formatMsgDesc(data) {
            return msgSimpleDesc(data);
        },
    }
}
</script>
