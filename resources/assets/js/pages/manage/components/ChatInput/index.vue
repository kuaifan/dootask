<template>
    <div class="chat-input-box" :class="boxClass" v-clickoutside="hidePopover">
        <!-- 快速表情 -->
        <div class="chat-input-quick-emoji">
            <EPopover
                ref="emojiQuickRef"
                v-model="emojiQuickShow"
                :visibleArrow="false"
                transition=""
                placement="top-end"
                popperClass="chat-quick-emoji-popover">
                <div slot="reference"></div>
                <Scrollbar
                    tag="ul"
                    ref="emojiWrapper"
                    :enable-x="true"
                    :enable-y="false"
                    class-name="chat-quick-emoji-wrapper scrollbar-hidden">
                    <li v-for="item in emojiQuickItems" @click="onEmojiQuick(item)">
                        <img :title="item.name" :alt="item.name" :src="item.src"/>
                    </li>
                </Scrollbar>
            </EPopover>
        </div>

        <div ref="inputWrapper" class="chat-input-wrapper" @click.stop="focus">
            <!-- 回复、修改 -->
            <div v-if="quoteData" class="chat-quote">
                <div v-if="quoteUpdate" class="quote-label">{{$L('编辑消息')}}</div>
                <UserAvatar v-else :userid="quoteData.userid" :show-icon="false" :show-name="true" :tooltip-disabled="true"/>
                <div class="quote-desc">{{$A.getMsgSimpleDesc(quoteData)}}</div>
                <i class="taskfont" @click.stop="cancelQuote">&#xe6e5;</i>
            </div>

            <!-- 输入框 -->
            <div
                ref="editor"
                class="no-dark-content"
                :style="editorStyle"
                @click.stop="onClickEditor"
                @paste="handlePaste"></div>

            <!-- 工具栏 -->
            <ul class="chat-toolbar" @click.stop>
                <!-- 桌面端表情（漂浮） -->
                <li>
                    <EPopover
                        v-if="!emojiBottom"
                        v-model="showEmoji"
                        :visibleArrow="false"
                        placement="top"
                        popperClass="chat-input-emoji-popover">
                        <ETooltip slot="reference" ref="emojiTip" :disabled="$isEEUiApp || windowTouch || showEmoji" placement="top" :content="$L('表情')">
                            <i class="taskfont">&#xe7ad;</i>
                        </ETooltip>
                        <ChatEmoji v-if="showEmoji" @on-select="onSelectEmoji" :searchKey="emojiQuickKey"/>
                    </EPopover>
                    <ETooltip v-else ref="emojiTip" :disabled="$isEEUiApp || windowTouch || showEmoji" placement="top" :content="$L('表情')">
                        <i class="taskfont" @click="showEmoji=!showEmoji">&#xe7ad;</i>
                    </ETooltip>
                </li>

                <!-- @ # -->
                <li>
                    <ETooltip placement="top" :disabled="$isEEUiApp || windowTouch" :content="$L('选择成员')">
                        <i class="taskfont" @click="onToolbar('user')">&#xe78f;</i>
                    </ETooltip>
                </li>
                <li>
                    <ETooltip placement="top" :disabled="$isEEUiApp || windowTouch" :content="$L('选择任务')">
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
                        <ETooltip slot="reference" ref="moreTip" :disabled="$isEEUiApp || windowTouch || showMore" placement="top" :content="$L('展开')">
                            <i class="taskfont">&#xe790;</i>
                        </ETooltip>
                        <div v-if="recordReady" class="chat-input-popover-item" @click="onToolbar('meeting')">
                            <i class="taskfont">&#xe7c1;</i>
                            {{$L('新会议')}}
                        </div>
                        <div v-if="canCall" class="chat-input-popover-item" @click="onToolbar('call')">
                            <i class="taskfont">&#xe7ba;</i>
                            {{$L('拨打电话')}}
                        </div>
                        <div class="chat-input-popover-item" @click="onToolbar('image')">
                            <i class="taskfont">&#xe7bc;</i>
                            {{$L('发送图片')}}
                        </div>
                        <div class="chat-input-popover-item" @click="onToolbar('file')">
                            <i class="taskfont">&#xe7c0;</i>
                            {{$L('上传文件')}}
                        </div>
                        <div v-if="canAnon" class="chat-input-popover-item" @click="onToolbar('anon')">
                            <i class="taskfont">&#xe690;</i>
                            {{$L('匿名消息')}}
                        </div>
                        <div v-if="dialogData.type == 'group'" class="chat-input-popover-item" @click="onToolbar('word-chain')">
                            <i class="taskfont">&#xe807;</i>
                            {{$L('接龙')}}
                        </div>
                        <div v-if="dialogData.type == 'group'" class="chat-input-popover-item" @click="onToolbar('vote')">
                            <i class="taskfont">&#xe806;</i>
                            {{$L('投票')}}
                        </div>
                        <div class="chat-input-popover-item" @click="onToolbar('full')">
                            <i class="taskfont">&#xe6a7;</i>
                            {{$L('全屏输入')}}
                        </div>
                    </EPopover>
                </li>

                <!-- 发送按钮 -->
                <li
                    ref="chatSend"
                    class="chat-send"
                    :class="sendClass"
                    v-touchmouse="clickSend"
                    v-longpress="{callback: longSend, delay: 300}">
                    <EPopover
                        v-model="showMenu"
                        :visibleArrow="false"
                        trigger="manual"
                        placement="top"
                        popperClass="chat-input-more-popover">
                        <ETooltip slot="reference" ref="sendTip" placement="top" :disabled="$isEEUiApp || windowTouch || showMenu" :content="$L(sendContent)">
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
                        <div class="chat-input-popover-item" @click="onSend('silence')">
                            <i class="taskfont">&#xe7d7;</i>
                            {{$L('无声发送')}}
                        </div>
                        <div class="chat-input-popover-item" @click="onSend('md')">
                            <i class="taskfont">&#xe647;</i>
                            {{$L('Markdown 格式发送')}}
                        </div>
                    </EPopover>
                </li>

                <!-- 录音效果 -->
                <li class="chat-record-recwave"><div ref="recwave"></div></li>
            </ul>

            <!-- 覆盖层 -->
            <div class="chat-cover" @click.stop="onClickCover"></div>
        </div>

        <!-- 移动端表情（底部） -->
        <ChatEmoji v-if="emojiBottom && showEmoji" @on-select="onSelectEmoji" :searchKey="emojiQuickKey"/>

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

        <Modal
            v-model="fullInput"
            :mask-closable="false"
            :beforeClose="onFullBeforeClose"
            class-name="chat-input-full-input"
            footer-hide
            fullscreen>
            <div class="chat-input-box">
                <div class="chat-input-wrapper">
                    <div ref="editorFull" class="no-dark-content"></div>
                </div>
            </div>
            <i slot="close" class="taskfont">&#xe6ab;</i>
        </Modal>
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
import longpress from "../../../../directives/longpress";
import {Store} from "le5le-store";

export default {
    name: 'ChatInput',
    components: {ChatEmoji},
    directives: {touchmouse, TransferDom, clickoutside, longpress},
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
        sendMenu: {
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
            isFocus: false,
            rangeIndex: 0,
            _content: '',
            _options: {},

            mentionMode: '',

            userList: null,
            userCache: null,
            taskList: null,
            fileList: {},

            showMenu: false,
            showMore: false,
            showEmoji: false,

            emojiQuickShow: false,
            emojiQuickKey: '',
            emojiQuickItems: [],

            observer: null,
            wrapperWidth: 0,
            wrapperHeight: 0,
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

            pasteClean: true,

            changeLoad: 0,

            isSpecVersion: this.checkIOSVersion(),

            emojiTimer: null,
            scrollTimer: null,
            selectTimer: null,
            textTimer: null,
            fileTimer: null,
            moreTimer: null,

            fullInput: false,
            fullQuill: null,
        };
    },
    mounted() {
        this.init();
        //
        this.observer = new ResizeObserver(entries => {
            entries.some(({target, contentRect}) => {
                if (target === this.$el) {
                    this.wrapperWidth = contentRect.width;
                    this.wrapperHeight = contentRect.height;
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
                if (type === 'recordAudio' && result === false) {
                    // Android 录音权限被拒绝了
                    this.stopRecord(true);
                }
            }
        }
        //
        $A.loadScript('js/emoticon.all.js')
    },
    beforeDestroy() {
        if (this.quill) {
            this.quill = null
        }
        if (this.recordRec) {
            this.recordRec = null
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
        ...mapState([
            'cacheProjects',
            'cacheTasks',
            'cacheUserBasic',

            'cacheDialogs',
            'dialogMsgs',
        ]),

        isEnterSend({enterSend}) {
            if (typeof enterSend === "boolean") {
                return enterSend;
            } else {
                return true;
            }
        },

        canCall() {
            return this.dialogData.type === 'user' && !this.dialogData.bot && this.$isEEUiApp
        },

        canAnon() {
            return this.dialogData.type === 'user' && !this.dialogData.bot
        },

        editorStyle() {
            const {wrapperWidth, editorHeight, value} = this;
            const style = {};
            if (value.length < 10) {
                style.height = '30px';
            }
            if (wrapperWidth > 0
                && editorHeight > 0
                && (wrapperWidth < 280 || editorHeight > 40)) {
                style.width = '100%';
            }
            return style;
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
            if (this.showMenu) {
                array.push('show-menu');
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

        sendContent() {
            const {sendTip} = this.$refs
            if (sendTip && sendTip.$refs.popper) {
                sendTip.$refs.popper.style.visibility = 'hidden'
                sendTip.showPopper = false
                setTimeout(_ => {
                    if (sendTip.$refs.popper) {
                        sendTip.$refs.popper.style.visibility = 'visible'
                    }
                }, 300)
            }
            return this.sendClass === 'recorder' ? '长按录音' : '发送'
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

        dialogData() {
            return this.dialogId > 0 ? (this.cacheDialogs.find(({id}) => id == this.dialogId) || {}) : {};
        },

        quoteUpdate() {
            return this.dialogData.extra_quote_type === 'update'
        },

        quoteData() {
            const {extra_quote_id} = this.dialogData;
            if (extra_quote_id) {
                return this.dialogMsgs.find(item => item.id === extra_quote_id)
            }
            return null;
        },

        separateSendButton() {
            return $A.jsonParse(window.localStorage.getItem("__keyboard:data__"))?.separate_send_button === 'open';
        },

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
            this.$store.dispatch("saveDialogDraft", {id: this.dialogId, extra_draft_content: val})
        },

        // Watch disabled change
        disabled(val) {
            this.quill?.enable(!val)
        },

        // Reset lists
        dialogId() {
            this.userList = null;
            this.userCache = null;
            this.taskList = null;
            this.fileList = {};
            this.loadInputDraft()
        },
        taskId() {
            this.userList = null;
            this.userCache = null;
            this.taskList = null;
            this.fileList = {};
            this.loadInputDraft()
        },

        'dialogData.extra_draft_content'() {
            if (this.isFocus) {
                return
            }
            this.loadInputDraft()
        },

        showMenu(val) {
            if (val) {
                // this.showMenu = false;
                this.showMore = false;
                this.showEmoji = false;
                this.emojiQuickShow = false;
            }
        },

        showMore(val) {
            if (val) {
                this.showMenu = false;
                // this.showMore = false;
                this.showEmoji = false;
                this.emojiQuickShow = false;
            }
        },

        showEmoji(val) {
            if (this.emojiBottom) {
                if (val) {
                    this.quill.enable(false)
                } else if (!this.disabled) {
                    this.quill.enable(true)
                }
            }
            if (val) {
                let text = this.value
                    .replace(/&nbsp;/g," ")
                    .replace(/<[^>]+>/g, "")
                if (text
                    && text.indexOf(" ") === -1
                    && text.length >= 1
                    && text.length <= 8) {
                    this.emojiQuickKey = text;
                } else {
                    this.emojiQuickKey = "";
                }
                //
                this.showMenu = false;
                this.showMore = false;
                // this.showEmoji = false;
                this.emojiQuickShow = false;
                if (this.quill) {
                    const range = this.quill.selection.savedRange;
                    this.rangeIndex = range ? range.index : 0
                }
            } else if (this.rangeIndex > 0) {
                this.quill.setSelection(this.rangeIndex)
            }
            this.$emit('on-emoji-visible-change', val)
        },

        emojiQuickShow(val) {
            if (val) {
                this.showMenu = false;
                this.showMore = false;
                this.showEmoji = false;
                // this.emojiQuickShow = false;
            }
        },

        isFocus(val) {
            if (this.scrollTimer) {
                clearInterval(this.scrollTimer);
            }
            if (val) {
                this.$emit('on-focus')
                this.hidePopover()
                if (this.isSpecVersion) {
                    // ios11.0-11.3 对scrollTop及scrolIntoView解释有bug
                    // 直接执行会导致输入框滚到底部被遮挡
                } else if (this.windowPortrait) {
                    this.scrollTimer = setInterval(() => {
                        if (this.quill?.hasFocus()) {
                            this.windowScrollY > 0 && $A.scrollIntoViewIfNeeded(this.$refs.editor);
                        } else {
                            clearInterval(this.scrollTimer);
                        }
                    }, 200);
                }
            } else {
                this.$emit('on-blur')
            }
        },

        recordState(state) {
            if (state === 'ing') {
                this.recordWave = window.Recorder.FrequencyHistogramView({
                    elem: this.$refs.recwave,
                    lineCount: 90,
                    position: 0,
                    minHeight: 1,
                    stripeEnable: false
                })
            } else {
                this.recordWave = null
                this.$refs.recwave.innerHTML = ""
            }
            this.$emit('on-record-state', state)
        },

        wrapperHeight(newVal, oldVal) {
            this.$emit('on-height-change', {newVal, oldVal})
        },

        fullInput(val) {
            this.quill?.enable(!val)
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
                                        if (this.separateSendButton) {
                                            const length = this.quill.getSelection(true).index;
                                            this.quill.insertText(length, "\r\n");
                                            return false;
                                        }
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
                                        if (this.separateSendButton) {
                                            const length = this.quill.getSelection(true).index;
                                            this.quill.insertText(length, "\r\n");
                                            return false;
                                        }
                                        this.onSend();
                                        return false;
                                    }
                                    return true;
                                }
                            },
                            'esc': {
                                key: 27,
                                shiftKey: false,
                                handler: _ => {
                                    if (this.emojiQuickShow) {
                                        this.emojiQuickShow = false;
                                        return false;
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    mention: this.quillMention()
                }
            }, this.options)

            // Instance
            this.quill = new Quill(this.$refs.editor, this._options)
            this.quill.enable(!this.disabled)

            // Set editor content
            if (this.value) {
                this.setContent(this.value)
            } else {
                this.loadInputDraft()
            }

            // Mark model as touched if editor lost focus
            this.quill.on('selection-change', range => {
                if (!range && document.activeElement) {
                    // 修复光标会超出的问题
                    if (['ql-editor', 'ql-clipboard'].includes(document.activeElement.className)) {
                        this.selectTimer && clearTimeout(this.selectTimer)
                        this.selectTimer = setTimeout(_ => {
                            this.quill.setSelection(document.activeElement.className === 'ql-editor' ? 0 : this.quill.getLength())
                        }, 100)
                        return
                    }
                }
                this.isFocus = !!range;
            })

            // Update model if text changes
            this.quill.on('text-change', _ => {
                this.changeLoad++
                this.textTimer && clearTimeout(this.textTimer)
                this.textTimer = setTimeout(_ => {
                    this.changeLoad--
                    if (this.maxlength > 0 && this.quill.getLength() > this.maxlength) {
                        this.quill.deleteText(this.maxlength, this.quill.getLength());
                    }
                    let html = this.$refs.editor.firstChild.innerHTML
                    html = html.replace(/^(<p>\s*<\/p>)+|(<p>\s*<\/p>)+$/gi, '')
                    html = html.replace(/^(<p><br\/*><\/p>)+|(<p><br\/*><\/p>)+$/gi, '')
                    this.updateEmojiQuick(html)
                    this._content = html
                    this.$emit('input', this._content)
                    this.$nextTick(_ => {
                        const range = this.quill.getSelection();
                        if (range) {
                            const endText = this.quill.getText(range.index);
                            /^\n\n$/.test(endText) && this.quill.deleteText(range.index, 1);
                        }
                    })
                }, 100)
            })

            // Clipboard Matcher (保留图片跟空格，清除其余所以样式)
            this.quill.clipboard.addMatcher(Node.ELEMENT_NODE, (node, delta) => {
                if (!this.pasteClean) {
                    return delta
                }
                delta.ops = delta.ops.map(op => {
                    const obj = {
                        insert: op.insert
                    };
                    try {
                        // 替换 mention 对象为纯文本
                        if (typeof obj.insert.mention === "object" && node.innerHTML) {
                            obj.insert = node.innerHTML.replace(/<[^>]+>/g, "")
                        }
                    } catch (e) { }
                    if (op.attributes) {
                        ['bold', 'strike', 'italic', 'underline', 'list', 'blockquote', 'link'].some(item => {
                            if (op.attributes[item]) {
                                if (typeof obj.attributes === "undefined") {
                                    obj.attributes = {}
                                }
                                obj.attributes[item] = op.attributes[item]
                            }
                        })
                    }
                    return obj
                })
                return delta
            })

            // Set enterkeyhint
            this.$nextTick(_ => {
                this.quill.root.addEventListener('keydown', e => {
                    if (e.key === '\r\r' && e.keyCode === 229) {
                        const length = this.quill.getSelection(true).index;
                        this.quill.insertText(length, "\r\n");
                    }
                });
                if (!this.separateSendButton) {
                    this.quill.root.setAttribute('enterkeyhint', 'send')
                }
            })

            // Ready event
            this.$emit('on-ready', this.quill)

            // Load recorder
            if (!this.disabledRecord) {
                $A.loadScriptS([
                    'js/recorder/recorder.mp3.min.js',
                    'js/recorder/lib.fft.js',
                    'js/recorder/frequency.histogram.view.js',
                ]).then(_ => {
                    if (typeof window.Recorder !== 'function') {
                        return;
                    }
                    this.recordRec = window.Recorder({
                        type: "mp3",
                        bitRate: 64,
                        sampleRate: 32000,
                        onProcess: (buffers, powerLevel, duration, sampleRate, newBufferIdx, asyncEnd) => {
                            this.recordWave?.input(buffers[buffers.length - 1], powerLevel, sampleRate);
                            this.recordDuration = duration;
                            if (duration >= 3 * 60 * 1000) {
                                // 最长录3分钟
                                this.stopRecord(false);
                            }
                        }
                    })
                    if (window.Recorder.Support()) {
                        this.recordReady = true;
                    }
                });
            }
        },

        quillMention() {
            return {
                allowedChars: /^\S*$/,
                mentionDenotationChars: ["@", "#", "~"],
                defaultMenuOrientation: this.defaultMenuOrientation,
                isolateCharacter: true,
                positioningStrategy: 'fixed',
                renderItem: (data) => {
                    if (data.disabled === true) {
                        return `<div class="mention-item-disabled">${data.value}</div>`;
                    }
                    if (data.id === 0) {
                        return `<div class="mention-item-at">@</div><div class="mention-item-name">${data.value}</div><div class="mention-item-tip">${data.tip}</div>`;
                    }
                    if (data.avatar) {
                        const botHtml = data.bot ? `<div class="taskfont mention-item-bot">&#xe68c;</div>` : ''
                        return `<div class="mention-item-img${data.online ? ' online' : ''}"><img src="${data.avatar}"/><em></em></div>${botHtml}<div class="mention-item-name">${data.value}</div>`;
                    }
                    if (data.tip) {
                        return `<div class="mention-item-name" title="${data.value}">${data.value}</div><div class="mention-item-tip">${data.tip}</div>`;
                    }
                    return `<div class="mention-item-name" title="${data.value}">${data.value}</div>`;
                },
                renderLoading: () => {
                    return "Loading...";
                },
                source: (searchTerm, renderList, mentionChar) => {
                    const mentionName = mentionChar == "@" ? 'user-mention' : (mentionChar == "#" ? 'task-mention' : 'file-mention');
                    const containers = document.getElementsByClassName("ql-mention-list-container");
                    for (let i = 0; i < containers.length; i++) {
                        containers[i].classList.remove("user-mention");
                        containers[i].classList.remove("task-mention");
                        containers[i].classList.remove("file-mention");
                        containers[i].classList.add(mentionName);
                        $A.scrollPreventThrough(containers[i]);
                    }
                    let mentionSourceCache = null;
                    this.getMentionSource(mentionChar, searchTerm, array => {
                        const values = [];
                        array.some(item => {
                            let list = item.list;
                            if (searchTerm) {
                                list = list.filter(({value}) => $A.strExists(value, searchTerm));
                            }
                            if (list.length > 0) {
                                item.label && values.push(...item.label)
                                values.push(...list)
                            }
                        })
                        if ($A.jsonStringify(values.map(({id}) => id)) !== mentionSourceCache) {
                            mentionSourceCache = $A.jsonStringify(values.map(({id}) => id))
                            renderList(values, searchTerm);
                        }
                    })
                }
            }
        },

        updateEmojiQuick(text) {
            if (!this.isFocus || !text) {
                this.emojiQuickShow = false
                return
            }
            this.emojiTimer && clearTimeout(this.emojiTimer)
            this.emojiTimer = setTimeout(_ => {
                if (/<img/i.test(text)) {
                    this.emojiQuickShow = false
                    return
                }
                text = text
                    .replace(/&nbsp;/g," ")
                    .replace(/<[^>]+>/g, "")
                if (text
                    && text.indexOf(" ") === -1
                    && text.length >= 1
                    && text.length <= 8
                    && $A.isArray(window.emoticonData)) {
                    // 显示快捷选择表情窗口
                    this.emojiQuickItems = [];
                    const baseUrl = $A.apiUrl("../images/emoticon")
                    window.emoticonData.some(data => {
                        let j = 0
                        data.list.some(item => {
                            const arr = [item.name]
                            if (item.key) {
                                arr.push(...(`${item.key}`).split(" "))
                            }
                            if (arr.includes(text)) {
                                this.emojiQuickItems.push(Object.assign(item, {
                                    type: `emoticon`,
                                    asset: `images/emoticon/${data.path}/${item.path}`,
                                    name: item.name,
                                    src: `${baseUrl}/${data.path}/${item.path}`
                                }))
                                if (++j >= 2) {
                                    return true
                                }
                            }
                        })
                        if (this.emojiQuickItems.length >= 20) {
                            return true
                        }
                    });
                    if (this.emojiQuickItems.length > 0) {
                        this.$refs.emojiWrapper.$el.style.maxWidth = `${Math.min(500, this.$refs.inputWrapper.clientWidth)}px`
                        this.$nextTick(_ => {
                            this.emojiQuickShow = true
                            this.$refs.emojiQuickRef.updatePopper()
                        })
                        return
                    }
                }
                this.emojiQuickShow = false
            }, 100)
        },

        getText() {
            if (this.quill) {
                return `${this.quill.getText()}`.replace(/^\s+|\s+$/g, "")
            }
            return "";
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

        setPasteMode(bool) {
            this.pasteClean = bool
        },

        loadInputDraft() {
            const {extra_draft_content} = this.dialogData;
            if (extra_draft_content) {
                this.pasteClean = false
                this.$emit('input', extra_draft_content)
                this.$nextTick(_ => this.pasteClean = true)
            } else {
                this.$emit('input', '')
            }
        },

        onClickEditor() {
            this.clearSearchKey()
            this.updateEmojiQuick(this.value)
        },

        clearSearchKey() {
            if (this.$parent.$options.name === 'DialogWrapper' && (this.$store.state.messengerSearchKey.dialog != '' || this.$store.state.messengerSearchKey.contacts != '')) {
                setTimeout(_ => {
                    this.$parent.onActive();
                }, 10)
            }
            this.$store.state.messengerSearchKey = {dialog: '', contacts: ''}
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
                    if ((event.button === undefined || event.button === 0) && this.startRecord()) {
                        return;
                    }
                    if (event.button === 2){
                        this.showMenu = true;
                    }
                    break;

                case 'move':
                    const touchMove = event.type === "touchmove" ? event.touches[0] : event;
                    this.touchLimitX = (this.touchStart.clientX - touchMove.clientX) / window.innerWidth > 0.1
                    this.touchLimitY = (this.touchStart.clientY - touchMove.clientY) / window.innerHeight > 0.1
                    break;

                case 'up':
                    if (this.showMenu) {
                        return;
                    }
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

        longSend() {
            if (this.sendClass === 'recorder' || !this.sendMenu) {
                return;
            }
            this.showMenu = true;
        },

        onSend(type) {
            setTimeout(_ => {
                this.hidePopover('send')
                this.rangeIndex = 0
                this.clearSearchKey()
                if (type) {
                    this.$emit('on-send', null, type)
                } else {
                    this.$emit('on-send')
                }
            }, this.changeLoad > 0 ? 100 : 0)
        },

        startRecord() {
            if (this.sendClass === 'recorder') {
                this.$store.dispatch("audioStop", true)
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

        hidePopover(action) {
            this.showMenu = false;
            this.showMore = false;
            if (action === 'send') {
                return
            }
            this.showEmoji = false;
            this.emojiQuickShow = false;
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

        onEmojiQuick(item) {
            if (item.type === 'online') {
                this.$emit('input', "")
                this.$emit('on-send', `<img src="${item.src}"/>`)
            } else {
                this.$emit('input', "")
                this.$emit('on-send', `<img class="emoticon" data-asset="${item.asset}" data-name="${item.name}" src="${item.src}"/>`)
            }
            this.emojiQuickShow = false
            this.focus()
        },

        onSelectEmoji(item) {
            if (!this.quill) {
                return;
            }
            if (item.type === 'emoji') {
                this.quill.insertText(this.rangeIndex, item.text);
                this.rangeIndex += item.text.length
                if (this.windowLandscape) {
                    this.showEmoji = false;
                }
            } else if (item.type === 'emoticon') {
                this.$emit('on-send', `<img class="emoticon" data-asset="${item.asset}" data-name="${item.name}" src="${item.src}"/>`)
                if (item.asset === "emosearch") {
                    this.$emit('input', "")
                }
                if (this.windowLandscape) {
                    this.showEmoji = false;
                }
            }
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

                case 'full':
                    this.onFullInput()
                    break;

                case 'image':
                case 'file':
                case 'call':
                case 'anon':
                    this.$emit('on-more', action)
                    break;

                case 'word-chain':
                    this.$store.state.dialogDroupWordChain = {
                        type: 'create',
                        dialog_id: this.dialogId
                    }
                    break;

                case 'vote':
                    this.$store.state.dialogGroupVote = {
                        type: 'create',
                        dialog_id: this.dialogId
                    }
                    break;

            }
        },

        onFullInput() {
            if (this.disabled) {
                return
            }
            this.fullInput = !this.fullInput;
            //
            if (this.fullInput) {
                this.$nextTick(_ => {
                    this.fullQuill = new Quill(this.$refs.editorFull, Object.assign({
                        theme: 'bubble',
                        readOnly: false,
                        placeholder: this.placeholder,
                        modules: {
                            toolbar: [
                                ['bold', 'strike', 'italic', 'underline', {'list': 'ordered'}, {'list': 'bullet'}, 'blockquote', 'code-block']
                            ],
                            mention: this.quillMention()
                        }
                    }, this.options))
                    this.fullQuill.enable(true)
                    this.$refs.editorFull.firstChild.innerHTML = this.$refs.editor.firstChild.innerHTML
                    this.$nextTick(_ => {
                        this.fullQuill.setSelection(this.fullQuill.getLength())
                        this.fullQuill.focus()
                    })
                })
            }
        },

        onFullBeforeClose() {
            return new Promise(resolve => {
                if (this.$refs.editorFull?.firstChild) {
                    this.$refs.editor.firstChild.innerHTML = this.$refs.editorFull.firstChild.innerHTML
                }
                resolve()
            })
        },

        onMoreVisibleChange(v) {
            this.showMore = v;
        },

        setQuote(id, type = 'reply') {
            this.dialogId > 0 && this.$store.dispatch("saveDialog", {
                id: this.dialogId,
                extra_quote_id: id,
                extra_quote_type: type === 'update' ? 'update' : 'reply'
            });
        },

        cancelQuote() {
            if (this.quoteUpdate) {
                this.$emit('input', '')
            }
            this.setQuote(0)
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

        addMention(data) {
            if (!this.quill) {
                return;
            }
            this.quill.getModule("mention").insertItem(data, true);
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

        getMentionSource(mentionChar, searchTerm, resultCallback) {
            switch (mentionChar) {
                case "@": // @成员
                    this.mentionMode = "user-mention";
                    const atCallback = (list) => {
                        this.getMoreUser(searchTerm, list.map(item => item.id)).then(moreUser => {
                            // 会话以外成员 排序 -> 前5名为最近联系的人
                            let cacheDialogs = this.cacheDialogs.filter((h, index) => h.type == "user" && h.bot == 0 && h.last_at)
                            cacheDialogs.sort((a, b) => a.last_at > b.last_at ? -1 : (a.last_at < b.last_at ? 1 : 0));
                            cacheDialogs = cacheDialogs.filter((h, index) => index < 5)
                            moreUser.forEach(user => {
                                user.last_at = "1990-01-01 00:00:00";
                                cacheDialogs.forEach(dialog => {
                                    if (dialog.dialog_user?.userid == user.id) {
                                        user.last_at = dialog.last_at;
                                    }
                                })
                            })
                            moreUser.sort((a, b) => a.last_at > b.last_at ? -1 : (a.last_at < b.last_at ? 1 : 0));
                            //
                            this.userList = list
                            this.userCache = [];
                            if (moreUser.length > 0) {
                                if (list.length > 2) {
                                    this.userCache.push({
                                        label: null,
                                        list: [{id: 0, value: this.$L('所有人'), tip: this.$L('仅提示会话内成员')}]
                                    })
                                }
                                this.userCache.push(...[{
                                    label: [{id: 0, value: this.$L('会话内成员'), disabled: true}],
                                    list,
                                }, {
                                    label: [{id: 0, value: this.$L('会话以外成员'), disabled: true}],
                                    list: moreUser,
                                }])
                            } else {
                                if (list.length > 2) {
                                    this.userCache.push(...[{
                                        label: null,
                                        list: [{id: 0, value: this.$L('所有人'), tip: this.$L('提示所有成员')}]
                                    }, {
                                        label: [{id: 0, value: this.$L('会话内成员'), disabled: true}],
                                        list,
                                    }])
                                } else {
                                    this.userCache.push({
                                        label: null,
                                        list
                                    })
                                }
                            }
                            resultCallback(this.userCache)
                        })
                    }
                    //
                    if (this.dialogData.people && $A.arrayLength(this.userList) !== this.dialogData.people) {
                        this.userList = null;
                        this.userCache = null;
                    }
                    if (this.userCache !== null) {
                        resultCallback(this.userCache)
                    }
                    if (this.userList !== null) {
                        atCallback(this.userList)
                        return;
                    }
                    //
                    const array = [];
                    if (this.dialogId > 0) {
                        // 根据会话ID获取成员
                        this.$store.dispatch("call", {
                            url: 'dialog/user',
                            data: {
                                dialog_id: this.dialogId,
                                getuser: 1
                            }
                        }).then(({data}) => {
                            if (this.cacheDialogs.find(({id}) => id == this.dialogId)) {
                                this.$store.dispatch("saveDialog", {
                                    id: this.dialogId,
                                    people: data.length
                                })
                            }
                            if (data.length > 0) {
                                array.push(...data.map(item => {
                                    return {
                                        id: item.userid,
                                        value: item.nickname,
                                        avatar: item.userimg,
                                        online: item.online,
                                        bot: item.bot,
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
                                const item = this.cacheUserBasic.find(({userid}) => userid == tmp.userid);
                                if (item) {
                                    array.push({
                                        id: item.userid,
                                        value: item.nickname,
                                        avatar: item.userimg,
                                        online: item.online,
                                        bot: item.bot,
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
                        resultCallback(this.taskList)
                        return;
                    }
                    const taskCallback = (list) => {
                        this.taskList = [];
                        // 项目任务
                        if (list.length > 0) {
                            list = list.map(item => {
                                return {
                                    id: item.id,
                                    value: item.name,
                                    tip: item.complete_at ? this.$L('已完成') : null,
                                }
                            }).splice(0, 100)
                            this.taskList.push({
                                label: [{id: 0, value: this.$L('项目任务'), disabled: true}],
                                list,
                            })
                        }
                        // 待完成任务
                        let dataA = this.$store.getters.transforTasks(this.$store.getters.dashboardTask['all']);
                        if (dataA.length > 0) {
                            dataA = dataA.sort((a, b) => {
                                return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                            }).splice(0, 100)
                            this.taskList.push({
                                label: [{id: 0, value: this.$L('我的待完成任务'), disabled: true}],
                                list: dataA.map(item => {
                                    return {
                                        id: item.id,
                                        value: item.name
                                    }
                                }),
                            })
                        }
                        // 我协助的任务
                        let dataB = this.$store.getters.assistTask;
                        if (dataB.length > 0) {
                            dataB = dataB.sort((a, b) => {
                                return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                            }).splice(0, 100)
                            this.taskList.push({
                                label: [{id: 0, value: this.$L('我协助的任务'), disabled: true}],
                                list: dataB.map(item => {
                                    return {
                                        id: item.id,
                                        value: item.name
                                    }
                                }),
                            })
                        }
                        resultCallback(this.taskList)
                    }
                    //
                    const projectId = this.getProjectId();
                    if (projectId > 0) {
                        this.$store.dispatch("getTaskForProject", projectId).then(_ => {
                            const tasks = this.cacheTasks.filter(task => {
                                if (task.archived_at) {
                                    return false;
                                }
                                return task.project_id == projectId
                                    && task.parent_id === 0
                                    && !task.archived_at
                            }).sort((a, b) => {
                                return $A.Date(b.complete_at || "2099-12-31 23:59:59") - $A.Date(a.complete_at || "2099-12-31 23:59:59")
                            })
                            if (tasks.length > 0) {
                                taskCallback(tasks)
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

                case "~": // ~文件
                    this.mentionMode = "file-mention";
                    if ($A.isArray(this.fileList[searchTerm])) {
                        resultCallback(this.fileList[searchTerm])
                        return;
                    }
                    this.fileTimer && clearTimeout(this.fileTimer)
                    this.fileTimer = setTimeout(_ => {
                        this.$store.dispatch("searchFiles", searchTerm).then(({data}) => {
                            this.fileList[searchTerm] = [{
                                label: [{id: 0, value: this.$L('文件分享查看'), disabled: true}],
                                list: data.filter(item => item.type !== "folder").map(item => {
                                    return {
                                        id: item.id,
                                        value: item.ext ? `${item.name}.${item.ext}` : item.name
                                    }
                                })
                            }];
                            resultCallback(this.fileList[searchTerm])
                        }).catch(() => {
                            resultCallback([])
                        })
                    }, 300)
                    break;

                default:
                    resultCallback([])
                    break;
            }
        },

        getMoreUser(key, existIds) {
            return new Promise(resolve => {
                const {owner_id, type} = this.dialogData
                const permission = type === 'group' && [0, this.userId].includes(owner_id)
                if (this.taskId > 0 || permission) {
                    this.moreTimer && clearTimeout(this.moreTimer)
                    this.moreTimer = setTimeout(_ => {
                        this.$store.dispatch("call", {
                            url: 'users/search',
                            data: {
                                keys: {
                                    key,
                                },
                                state: 1,
                                take: 30
                            },
                        }).then(({data}) => {
                            const moreUser = data.filter(item => !existIds.includes(item.userid))
                            resolve(moreUser.map(item => {
                                return {
                                    id: item.userid,
                                    value: item.nickname,
                                    avatar: item.userimg,
                                    online: !!item.online,
                                }
                            }))
                        }).catch(_ => {
                            resolve([])
                        });
                    }, this.userCache === null ? 0 : 600)
                } else {
                    resolve([])
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
            const files = Array.prototype.slice.call(e.clipboardData.files)
            const postFiles = files.filter(file => !$A.leftExists(file.type, 'image/'));
            if (postFiles.length > 0) {
                e.preventDefault()
                this.$emit('on-file', files)
            } else if (this.pasteRtf(e)) {
                e.preventDefault()
            }
        },

        pasteRtf(e) {
            if (e && e.clipboardData && e.clipboardData.items) {
                const imgHtml = (new DOMParser).parseFromString(e.clipboardData.getData("text/html") || "", "text/html").querySelector("img");
                if (!imgHtml) {
                    const array = [];
                    let image = null;
                    if (e.clipboardData.types && -1 != [].indexOf.call(e.clipboardData.types, "text/rtf") || e.clipboardData.getData("text/rtf")) {
                        image = e.clipboardData.items[0].getAsFile();
                        if (image) {
                            array.push(image)
                        }
                    } else {
                        for (let s = 0; s < e.clipboardData.items.length; s++) {
                            image = e.clipboardData.items[s].getAsFile()
                            if (image) {
                                array.push(image)
                            }
                        }
                    }
                    if (array.length > 0) {
                        array.forEach(image => {
                            const t = new FileReader;
                            t.onload = ({target}) => {
                                const length = this.quill.getSelection(true).index;
                                this.quill.insertEmbed(length, "image", target.result);
                                this.quill.setSelection(length + 1)
                            };
                            t.readAsDataURL(image)
                        })
                        return true
                    }
                }
            }
            return false
        },
    }
}
</script>
