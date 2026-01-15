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
                    :touch-content-blur="false"
                    class-name="chat-quick-emoji-wrapper scrollbar-hidden">
                    <li v-for="(item, index) in emojiQuickItems" :key="index" @click="onEmojiQuick(item)">
                        <Imgs :title="item.name" :alt="item.name" :src="item.src"/>
                    </li>
                </Scrollbar>
            </EPopover>
        </div>

        <!-- 工具栏 -->
        <div class="chat-input-toolbar">
            <EPopover
                ref="toolbarRef"
                v-model="selectedText"
                :visibleArrow="false"
                transition=""
                placement="top-start"
                popperClass="chat-input-toolbar-popover">
                <div slot="reference"></div>
                <ul class="chat-input-toolbar-menu">
                    <li
                        v-for="(item, index) in tools"
                        :key="index"
                        :data-label="item.label"
                        :data-type="item.type"
                        v-touchmouse="onMenu">
                        <i class="taskfont" v-html="item.icon"></i>
                    </li>
                </ul>
            </EPopover>
        </div>

        <div ref="inputWrapper" class="chat-input-wrapper">
            <!-- 回复、修改 -->
            <div v-if="quoteData" class="chat-quote">
                <div v-if="quoteUpdate" class="quote-label">{{$L('编辑消息')}}</div>
                <UserAvatar v-else :userid="quoteData.userid" :userResult="onQuoteUserResult" :show-icon="false" :show-name="true"/>
                <div class="quote-desc no-dark-content">{{$A.getMsgSimpleDesc(quoteData)}}</div>
                <i class="taskfont" v-touchclick="onTouchClick" data-action="cancel-quote">&#xe6e5;</i>
            </div>

            <!-- 输入框 -->
            <div
                ref="editor"
                class="no-dark-content user-select-auto"
                @click.stop="onClickEditor"
                @paste="handlePaste"></div>

            <!-- 工具栏占位 -->
            <div class="chat-space">
                <input class="space-input" @focus="onSpaceInputFocus"/>
            </div>

            <!-- 工具栏 -->
            <ul class="chat-toolbar" @click.stop>
                <!-- 桌面端表情（漂浮） -->
                <li>
                    <EPopover
                        ref="emoji"
                        v-if="!emojiBottom"
                        v-model="showEmoji"
                        :visibleArrow="false"
                        placement="top"
                        popperClass="chat-input-emoji-popover">
                        <ETooltip slot="reference" ref="emojiTip" :disabled="$isEEUIApp || windowTouch || showEmoji" placement="top" :enterable="false" :content="$L('表情')">
                            <i class="taskfont">&#xe7ad;</i>
                        </ETooltip>
                        <ChatEmoji v-if="showEmoji" @on-select="onSelectEmoji" :searchKey="emojiQuickKey"/>
                    </EPopover>
                    <ETooltip v-else ref="emojiTip" :disabled="$isEEUIApp || windowTouch || showEmoji" placement="top" :enterable="false" :content="$L('表情')">
                        <i class="taskfont" @click="showEmoji=!showEmoji">&#xe7ad;</i>
                    </ETooltip>
                </li>

                <!-- @ # -->
                <li>
                    <ETooltip placement="top" :disabled="$isEEUIApp || windowTouch" :enterable="false" :content="$L('选择成员')">
                        <i class="taskfont" @click="onToolbar('user')">&#xe78f;</i>
                    </ETooltip>
                </li>
                <li>
                    <ETooltip placement="top" :disabled="$isEEUIApp || windowTouch" :enterable="false" :content="$L('选择任务')">
                        <i class="taskfont" @click="onToolbar('task')">&#xe7d6;</i>
                    </ETooltip>
                </li>

                <!-- 加号更多 -->
                <li>
                    <EPopover
                        ref="more"
                        v-model="showMore"
                        :visibleArrow="false"
                        placement="top"
                        popperClass="chat-input-more-popover">
                        <ETooltip slot="reference" ref="moreTip" :disabled="$isEEUIApp || windowTouch || showMore" placement="top" :enterable="false" :content="$L('展开')">
                            <i class="taskfont">&#xe790;</i>
                        </ETooltip>
                        <template v-if="!isAiBot">
                            <div v-if="maybePhotoShow" class="chat-input-popover-item maybe-photo" @click="onToolbar('maybe-photo')">
                                <span :style="{maxWidth: maybePhotoStyle.width}">{{$L('可能要发的照片')}}:</span>
                                <div class="photo-preview" :style="maybePhotoStyle"></div>
                            </div>
                            <div v-if="recordReady" class="chat-input-popover-item" @click="onToolbar('meeting')">
                                <i class="taskfont">&#xe7c1;</i>
                                <em>{{$L('新会议')}}</em>
                            </div>
                            <div v-if="canCall" class="chat-input-popover-item" @click="onToolbar('call')">
                                <i class="taskfont">&#xe7ba;</i>
                                <em>{{$L('拨打电话')}}</em>
                            </div>
                            <div class="chat-input-popover-item" @click="onToolbar('image')">
                                <i class="taskfont">&#xe7bc;</i>
                                <em>{{$L('发送图片')}}</em>
                            </div>
                            <div class="chat-input-popover-item" @click="onToolbar('file')">
                                <i class="taskfont">&#xe7c0;</i>
                                <em>{{$L('上传文件')}}</em>
                            </div>
                            <div v-if="canAnon" class="chat-input-popover-item" @click="onToolbar('anon')">
                                <i class="taskfont">&#xe690;</i>
                                <em>{{$L('匿名消息')}}</em>
                            </div>
                            <div v-if="dialogData.type == 'group'" class="chat-input-popover-item" @click="onToolbar('word-chain')">
                                <i class="taskfont">&#xe80a;</i>
                                <em>{{$L('发起接龙')}}</em>
                            </div>
                            <div v-if="dialogData.type == 'group'" class="chat-input-popover-item" @click="onToolbar('vote')">
                                <i class="taskfont">&#xe7fd;</i>
                                <em>{{$L('发起投票')}}</em>
                            </div>
                        </template>
                        <template v-else>
                            <div class="chat-input-popover-item" @click="onToolbar('file')">
                                <i class="taskfont">&#xe7c0;</i>
                                <em>{{$L('上传文件')}}</em>
                            </div>
                        </template>
                        <div v-if="dialogId > 0" class="chat-input-popover-item" @click="onToolbar('ai')">
                            <i class="taskfont">&#xe8a1;</i>
                            <em>{{$L('AI 生成')}}</em>
                        </div>
                        <div ref="moreFull" class="chat-input-popover-item" @click="onToolbar('full')">
                            <i class="taskfont">&#xe6a7;</i>
                            <em>{{$L('全屏输入')}}</em>
                        </div>
                    </EPopover>
                </li>

                <!-- 发送按钮 -->
                <li
                    ref="chatSend"
                    class="chat-send"
                    :class="sendClass"
                    v-touchmouse="clickSend"
                    v-longpress="onShowMenu">
                    <EPopover
                        ref="menu"
                        v-model="showMenu"
                        :visibleArrow="false"
                        trigger="manual"
                        placement="top"
                        popperClass="chat-input-more-popover">
                        <ETooltip slot="reference" ref="sendTip" placement="top" :disabled="$isEEUIApp || windowTouch || showMenu" :enterable="false" :content="$L(sendContent)">
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
                            <em>{{$L('无声发送')}}</em>
                        </div>
                        <div class="chat-input-popover-item" @click="onSend('md')">
                            <i class="taskfont">&#xe647;</i>
                            <em>{{$L('MD 格式发送')}}</em>
                        </div>
                        <div class="chat-input-popover-item" @click="onSend('normal')">
                            <i class="taskfont">&#xe71b;</i>
                            <em>{{$L('普通格式发送')}}</em>
                        </div>
                    </EPopover>
                </li>
            </ul>

            <!-- 录音效果 -->
            <div class="chat-record" :class="recordClassName">
                <div @click="stopRecord(false, true)" class="record-convert">
                    <i class="taskfont">&#xe628;</i>
                </div>
                <div class="record-recwave">
                    <div ref="recwave"></div>
                </div>
                <div @click="stopRecord(true)" class="record-remove">
                    <i class="taskfont">&#xe787;</i>
                    <i class="taskfont">&#xe702;</i>
                </div>
            </div>

            <!-- 覆盖层 -->
            <div class="chat-cover" @click.stop="onClickCover"></div>
        </div>

        <!-- 移动端表情（底部） -->
        <ChatEmoji
            v-if="emojiBottom && showEmoji"
            @on-select="onSelectEmoji"
            @on-delete="onEmojiDelete"
            :searchKey="emojiQuickKey"
            showEmojiDelete/>

        <!-- 录音浮窗 -->
        <transition name="fade">
            <div
                v-if="recordShow"
                v-transfer-dom
                :data-transfer="true"
                class="chat-input-record-transfer"
                :class="recordClassName"
                :style="recordStyle"
                @click="stopRecord">
                <div v-if="recordDuration > 0" class="record-duration">{{recordFormatDuration}}</div>
                <div v-else class="record-loading"><Loading type="pure"/></div>
                <div class="record-cancel" @click.stop="stopRecord(true)">{{$L(recordFormatTip)}}</div>
            </div>
        </transition>

        <!-- 录音转文字 -->
        <transition name="fade">
            <div
                v-if="recordConvertIng"
                v-transfer-dom
                :data-transfer="true"
                class="chat-input-convert-transfer"
                :style="recordConvertStyle">
                <div class="convert-box">
                    <div class="convert-body">
                        <div class="convert-content">
                            <div v-if="recordConvertSetting" class="convert-setting">
                                <i class="taskfont" :class="{active: !!recordConvertTranslate}" @click="convertSetting($event)">&#xe795;</i>
                            </div>
                            <div class="convert-input">
                                <Input
                                    type="textarea"
                                    class="convert-result no-dark-content"
                                    v-model="recordConvertResult"
                                    :rows="1"
                                    :autosize="{minRows: 1, maxRows: 5}"
                                    :placeholder="recordConvertStatus === 0 ? '...' : ''"
                                    :disabled="recordConvertStatus !== 1"
                                    @on-focus="recordConvertFocus=true"
                                    @on-blur="recordConvertFocus=false"/>
                            </div>
                        </div>
                    </div>
                    <ul class="convert-footer" :style="recordConvertFooterStyle">
                        <li v-touchclick="onTouchClick" data-action="record-convert-cancel">
                            <i class="taskfont">&#xe637;</i>
                            <span>{{$L('取消')}}</span>
                        </li>
                        <li v-touchclick="onTouchClick" data-action="record-convert-voice">
                            <i class="taskfont voice">&#xe793;</i>
                            <span>{{$L('发送原语音')}}</span>
                        </li>
                        <li v-touchclick="onTouchClick" data-action="record-convert-result">
                            <i v-if="recordConvertStatus === 0" class="send"><Loading/></i>
                            <i v-else-if="recordConvertStatus === 2" class="taskfont error">&#xe665;</i>
                            <i v-else class="taskfont send">&#xe684;</i>
                        </li>
                    </ul>
                </div>
            </div>
        </transition>

        <!-- 全屏输入 -->
        <Modal
            v-model="fullInput"
            :mask-closable="false"
            :beforeClose="onFullBeforeClose"
            class-name="chat-input-full-input"
            footer-hide
            fullscreen>
            <div class="chat-input-box" :style="chatInputBoxStyle">
                <!-- 输入区域 -->
                <div class="chat-input-wrapper">
                    <div ref="editorFull" class="no-dark-content"></div>
                </div>
                <!-- 工具栏 -->
                <ul class="chat-input-menu" :class="{activation: fullSelected}">
                    <li
                        v-for="(item, index) in tools"
                        :key="index"
                        :data-label="item.label"
                        :data-type="item.type"
                        v-touchmouse="onMenu">
                        <i class="taskfont" v-html="item.icon"></i>
                    </li>
                </ul>
            </div>
            <i slot="close" class="taskfont">&#xe6ab;</i>
        </Modal>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import Quill from 'quill-hi';
import {Delta} from "quill-hi/core";
import "quill-mention-hi";
import "./selection-plugin";
import ChatEmoji from "./emoji";
import touchmouse from "../../../../directives/touchmouse";
import touchclick from "../../../../directives/touchclick";
import TransferDom from "../../../../directives/transfer-dom";
import clickoutside from "../../../../directives/clickoutside";
import longpress from "../../../../directives/longpress";
import {inputLoadAdd, inputLoadIsLast, inputLoadRemove} from "./one";
import {languageList, languageName} from "../../../../language";
import {isMarkdownFormat, MarkdownConver} from "../../../../utils/markdown";
import {cutText, extractPlainText} from "../../../../utils/text";
import {MESSAGE_AI_SYSTEM_PROMPT, withLanguagePreferencePrompt} from "../../../../utils/ai";
import emitter from "../../../../store/events";
import historyMixin from "./history";

const globalRangeIndexs = {};

export default {
    name: 'ChatInput',
    components: {ChatEmoji},
    directives: {touchmouse, touchclick, TransferDom, clickoutside, longpress},
    mixins: [historyMixin],
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
        emojiBottom: {
            type: Boolean,
            default: false
        },
        sendMenu: {
            type: Boolean,
            default: true
        },
        simpleMode: {
            type: Boolean,
            default: false
        },
        options: {
            type: Object,
            default: () => ({})
        },
        toolbar: {
            type: Array,
            default: () => {
                return ['bold', 'strike', 'italic', 'underline', 'blockquote', 'link', {'list': 'ordered'}, {'list': 'bullet'}, {'list': 'check'}]
            },
        },
        maxlength: {
            type: Number
        },
        defaultMenuOrientation: {
            type: String,
            default: "top"
        },
        replyMsgAutoMention: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            quill: null,
            isFocus: false,
            rangeIndex: 0,
            rangeLength: 0,
            _content: '',
            _options: {},

            mentionMode: '',

            maybePhotoShow: false,
            maybePhotoData: {},
            maybePhotoStyle: {},

            userList: null,
            userCache: null,
            taskList: null,
            taskSearchList: {},
            fileList: {},
            reportList: {},
            taskSearchKey: '',

            showMenu: false,
            showMore: false,
            showEmoji: false,

            emojiQuickShow: false,
            emojiQuickKey: '',
            emojiQuickItems: [],

            recordReady: false,
            recordRec: null,
            recordBlob: null,
            recordWave: null,
            recordInter: null,
            recordState: "stop",
            recordDuration: 0,
            recordIndex: window.modalTransferIndex,

            recordConvertIng: false,
            recordConvertFocus: false,
            recordConvertSetting: false,    // 是否显示转换设置
            recordConvertStatus: 0,         // 0: 转换中, 1: 转换成功, 2: 转换失败
            recordConvertResult: '',        // 转换结果
            recordConvertTranslate: '',     // 转换结果翻译语言

            touchStart: {},
            touchFocus: false,
            touchLimitX: false,
            touchLimitY: false,

            pasteClean: true,

            changeLoad: 0,

            isSpecVersion: this.checkIOSVersion(),

            emojiTimer: null,
            scrollTimer: null,
            textTimer: null,
            fileTimer: null,
            reportTimer: null,
            taskSearchTimer: null,
            moreTimer: null,
            selectTimer: null,
            selectRange: null,
            selectedText: false,

            fullInput: false,
            fullQuill: null,
            fullSelected: false,
            fullSelection: null,

            tools: [
                {
                    label: 'bold',
                    type: '',
                    icon: '&#xe891;',
                },
                {
                    label: 'strike',
                    type: '',
                    icon: '&#xe892;',
                },
                {
                    label: 'italic',
                    type: '',
                    icon: '&#xe896;',
                },
                {
                    label: 'underline',
                    type: '',
                    icon: '&#xe88e;',
                },
                {
                    label: 'blockquote',
                    type: '',
                    icon: '&#xe88d;',
                },
                {
                    label: 'link',
                    type: '',
                    icon: '&#xe885;',
                },
                {
                    label: 'list',
                    type: 'ordered',
                    icon: '&#xe886;',
                },
                {
                    label: 'list',
                    type: 'bullet',
                    icon: '&#xe894;',
                },
                {
                    label: 'list',
                    type: 'unchecked',
                    icon: '&#xe88c;',
                },
            ],

            iOSDevices: $A.isIos(),
        };
    },
    created() {
        inputLoadAdd(this._uid)
    },
    mounted() {
        this.init();
        this.refreshHistoryContext();
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
        if (this.$isEEUIApp) {
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
        inputLoadRemove(this._uid)
        if (this.quill) {
            this.quill.getModule("mention")?.hideMentionList();
            this.quill = null
        }
        if (this.recordRec) {
            this.recordRec = null
        }
        if (this.recordConvertIng) {
            this.recordConvertIng = false
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

            'cacheKeyboard',
            'keyboardShow',
            'keyboardHeight',
            'isModKey',
            'safeAreaSize',
            'viewportHeight',
        ]),

        ...mapGetters(['getDialogDraft', 'getDialogQuote']),

        isEnterSend({cacheKeyboard}) {
            if (this.$isEEUIApp) {
                return cacheKeyboard.send_button_app === 'enter';
            } else {
                return cacheKeyboard.send_button_desktop === 'enter';
            }
        },

        isAiBot({dialogData}) {
            if (!dialogData.bot || dialogData.type !== 'user') {
                return false
            }
            return /^ai-(.*?)@bot\.system/.test(dialogData.email)
        },

        canCall() {
            return this.dialogData.type === 'user' && !this.dialogData.bot && this.$isEEUIApp
        },

        canAnon() {
            return this.dialogData.type === 'user' && !this.dialogData.bot
        },

        recordShow() {
            const {recordState} = this;
            return ['ready', 'ing'].includes(recordState)
        },

        recordStyle() {
            const {windowScrollY, recordIndex} = this;
            const style = {
                zIndex: recordIndex,
            }
            if (windowScrollY > 0) {
                style.marginTop = (windowScrollY / 2) + 'px'
            }
            return style
        },

        recordConvertStyle() {
            const {recordIndex} = this;
            return {
                zIndex: recordIndex,
            }
        },

        recordConvertFooterStyle() {
            const {recordConvertFocus, keyboardShow, keyboardHeight} = this;
            return (recordConvertFocus && keyboardShow && keyboardHeight > 120 && $A.isIos()) ? {
                alignItems: 'flex-start',
                transform: 'translateY(12px)'
            } : {}
        },

        boxClass() {
            const array = [];
            if (this.recordShow) {
                if (this.recordState === 'ing' && this.recordDuration > 0) {
                    array.push('record-progress');
                } else {
                    array.push('record-ready');
                }
            }
            if (this.simpleMode) {
                array.push('simple-mode');
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
            if ($A.filterInvalidLine(this.value)) {
                return 'sender';
            }
            if (this.recordReady) {
                return 'recorder'
            }
            return ''
        },

        sendContent() {
            this.tempHiddenSendTip();
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

        recordClassName({touchLimitX, touchLimitY}) {
            if (touchLimitY) {
                return 'cancel'
            } else if (touchLimitX) {
                return 'convert'
            }
            return ''
        },

        recordFormatTip({touchLimitX, touchLimitY}) {
            if (touchLimitY) {
                return '松开取消'
            } else if (touchLimitX) {
                return '转文字'
            }
            return '向上滑动取消'
        },

        dialogData() {
            return this.dialogId > 0 ? (this.cacheDialogs.find(({id}) => id == this.dialogId) || {}) : {};
        },

        draftId() {
            return this.dialogId || `t_${this.taskId}`
        },

        draftData() {
            return this.getDialogDraft(this.draftId)?.content || ''
        },

        quoteData() {
            return this.getDialogQuote(this.dialogId)?.content || null
        },

        quoteUpdate() {
            return this.getDialogQuote(this.dialogId)?.type === 'update'
        },

        chatInputBoxStyle({iOSDevices, fullInput, keyboardShow, viewportHeight, safeAreaSize}) {
            const style = {}
            if (iOSDevices && fullInput && keyboardShow && viewportHeight > 0 && $A.isIos()) {
                style.height = Math.max(100, viewportHeight - 70 - safeAreaSize.top) + 'px'
            } else {
                style.paddingBottom = `${safeAreaSize.bottom}px`
            }
            return style
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
            if (!this.simpleMode) {
                this.$store.dispatch("saveDialogDraft", {id: this.draftId, content: val})
            }
        },

        // Watch disabled change
        disabled(val) {
            this.quill?.enable(!val)
        },

        // Reset lists
        dialogId() {
            this.selectRange = null;
            this.userList = null;
            this.userCache = null;
            this.taskList = null;
            this.taskSearchList = {};
            this.fileList = {};
            this.reportList = {};
            this.loadInputDraft()
            this.refreshHistoryContext();
        },
        taskId() {
            this.selectRange = null;
            this.userList = null;
            this.userCache = null;
            this.taskList = null;
            this.taskSearchList = {};
            this.fileList = {};
            this.reportList = {};
            this.loadInputDraft()
            this.refreshHistoryContext();
        },

        draftData() {
            if (this.isFocus) {
                return
            }
            this.loadInputDraft()
        },

        quoteData() {
            this.quoteChanged = true
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
            this.maybePhotoShow = false
            if (val) {
                this.showMenu = false;
                // this.showMore = false;
                this.showEmoji = false;
                this.emojiQuickShow = false;
                //
                if (this.isAiBot) {
                    return
                }
                $A.eeuiAppGetLatestPhoto().then(({thumbnail, original}) => {
                    const size = Math.min(120, Math.max(100, this.$refs.moreFull.clientWidth));
                    this.maybePhotoStyle = {
                        width: size + 'px',
                        height: size + 'px',
                        backgroundImage: `url(${thumbnail.base64})`,
                    }
                    this.maybePhotoData = {thumbnail, original}
                    this.maybePhotoShow = true
                    this.$nextTick(() => {
                        this.$refs.more?.updatePopper()
                    })
                }).catch(_ => {
                    // 获取图片失败
                })
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
                    this.rangeLength = range ? range.length : 0
                }
            } else {
                this.rangeLength = 0;
                if (this.rangeIndex > 0) {
                    this.quill.setSelection(this.rangeIndex)
                }
            }
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

        recordShow(show) {
            if (show) {
                this.recordIndex = ++window.modalTransferIndex
            }
        },

        recordConvertIng(show) {
            if (show) {
                this.recordIndex = ++window.modalTransferIndex
            } else {
                this.recordConvertSetting = false
            }
        },

        fullInput(val) {
            this.quill?.enable(!val)
        },

        windowScrollY(val) {
            if (this.fullInput && val > 0) {
                window.scrollTo(0, 0)
            }
        },

        keyboardShow(val) {
            if (!val && this.isFocus) {
                this.isFocus = false
                this.quill?.blur()
            }
        },

        selectRange(range) {
            if (range?.index) {
                globalRangeIndexs[this.draftId] = range.index
            }
        },
    },
    methods: {
        init() {
            // Options
            this._options = Object.assign({
                theme: 'bubble',
                bubbleTooltipTop: true,
                formats: ['bold', 'strike', 'italic', 'underline', 'blockquote', 'list', 'link', 'image', 'mention'],
                readOnly: false,
                placeholder: this.placeholder,
                modules: {
                    toolbar: false,
                    keyboard: this.simpleMode ? {} : {
                        bindings: {
                            'enter-short': {
                                key: "Enter",
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
                                key: "Enter",
                                shiftKey: false,
                                handler: _ => {
                                    if (this.isEnterSend) {
                                        this.onSend();
                                        return false;
                                    }
                                    return true;
                                }
                            },
                            'esc': {
                                key: "Escape",
                                shiftKey: false,
                                handler: _ => {
                                    if (this.emojiQuickShow) {
                                        this.emojiQuickShow = false;
                                        return false;
                                    }
                                    return true;
                                }
                            },
                            'history-up': {
                                key: 38,
                                handler: range => this.navigateHistory('up', range)
                            },
                            'history-down': {
                                key: 40,
                                handler: range => this.navigateHistory('down', range)
                            }
                        }
                    },
                    selectionPlugin: {
                        onTextSelected: (selectedText) => {
                            if (this.$isEEUIApp || this.windowTouch) {
                                return
                            }
                            this.selectedText = !!selectedText.trim()
                        },
                        onSelectionCleared: () => {
                            this.selectedText = false
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
                if (!this.inputActivated()) {
                    return;
                }
                if (range) {
                    this.selectRange = range
                } else if (this.selectRange && document.activeElement && /(ql-editor|ql-clipboard)/.test(document.activeElement.className)) {
                    // 修复iOS光标会超出的问题
                    this.selectTimer && clearTimeout(this.selectTimer)
                    this.selectTimer = setTimeout(_ => {
                        this.quill.setSelection(this.selectRange.index, this.selectRange.length)
                    }, 100)
                    return
                }
                this.isFocus = !!range;
            })

            // Update model if text changes
            this.quill.on('text-change', _ => {
                if (this.isFocus) {
                    const {index} = this.quill.getSelection();
                    if (this.quill.getText(index - 1, 1) === "\r") {
                        this.quill.insertText(index, "\n");
                        this.quill.deleteText(index - 1, 1);
                        return;
                    }
                }
                if (this.textTimer) {
                    clearTimeout(this.textTimer)
                } else {
                    this.changeLoad++
                }
                this.textTimer = setTimeout(_ => {
                    this.textTimer = null
                    this.changeLoad--
                    if (this.maxlength > 0 && this.quill.getLength() > this.maxlength) {
                        this.quill.deleteText(this.maxlength, this.quill.getLength());
                    }
                    const html = this.$refs.editor.firstChild.innerHTML;
                    this.updateEmojiQuick(html)
                    this._content = html
                    this.$emit('input', this._content)
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

            // 专门处理mention的matcher - 同时处理span.mention和a.mention
            this.quill.clipboard.addMatcher(['span.mention', 'a.mention'], (node, delta) => {
                if (!this.pasteClean) {
                    return delta
                }
                const mention = this.extractMentionData(node)
                if (mention === null) {
                    return delta
                }

                return new Delta([{
                    insert: {mention}
                }]);
            })

            // Link handler
            const toolbar = this.quill.getModule('toolbar')
            if (toolbar?.handlers?.link) {
                toolbar.addHandler('link', (value) => {
                    if (value) {
                        $A.modalInput({
                            title: "插入链接",
                            placeholder: "请输入完整的链接地址",
                            onOk: (link) => {
                                if (!link) {
                                    return false;
                                }
                                this.quill.format('link', link);
                            }
                        })
                    } else {
                        this.quill.format('link', false);
                    }
                });
            }

            // Set enterkeyhint
            this.$nextTick(_ => {
                if (this.$isEEUIApp && this.cacheKeyboard.send_button_app === 'enter') {
                    this.quill.root.setAttribute('enterkeyhint', 'send')
                }
            })

            // Ready event
            this.$emit('on-ready', this.quill)

            // Load recorder
            if (!this.disabledRecord) {
                const i18nLang = /^zh/.test(languageName) ? "zh-CN" : "en-US";
                $A.loadScriptS([
                    'js/recorder/recorder.mp3.min.js',
                    'js/recorder/lib.fft.js',
                    'js/recorder/frequency.histogram.view.js',
                    `js/recorder/i18n/${i18nLang}.js`,
                ]).then(_ => {
                    if (typeof window.Recorder !== 'function') {
                        return;
                    }
                    window.Recorder.i18n.lang = i18nLang
                    this.recordRec = window.Recorder({
                        type: "mp3",
                        bitRate: 64,
                        sampleRate: 32000,
                        audioTrackSet: {
                            noiseSuppression: true,
                            echoCancellation: true,
                        },
                        disableEnvInFix: false,
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
                    if (window.systemInfo.debug !== "yes") {
                        window.Recorder.CLog = function () { }
                    }
                });
            }
        },

        quillMention() {
            return {
                allowedChars: /^\S*$/,
                mentionDenotationChars: ["@", "#", "~", "%", "/"],
                defaultMenuOrientation: this.defaultMenuOrientation,
                isolateCharacter: true,
                positioningStrategy: 'fixed',
                dataAttributes: ['tip'],
                renderItem: (data) => {
                    if (data.disabled === true) {
                        return `<div class="mention-item-disabled">${data.value}</div>`;
                    }
                    const nameHtml = `<div class="mention-item-name" title="${data.value}">${data.value}</div>`;
                    const tipHtml = data.tip ? `<div class="mention-item-tip" title="${data.tip}">${data.tip}</div>` : '';
                    if (data.id === 0) {
                        return `<div class="mention-item-at">@</div>${nameHtml}${tipHtml}`;
                    }
                    if (data.avatar) {
                        const botHtml = data.bot ? `<div class="taskfont mention-item-bot">&#xe68c;</div>` : ''
                        return `<div class="mention-item-img${data.online ? ' online' : ''}"><img src="${data.avatar}"/><em></em></div>${botHtml}${nameHtml}${tipHtml}`;
                    }
                    return `${nameHtml}${tipHtml}`;
                },
                renderLoading: () => {
                    return "Loading...";
                },
                onSelect: function (item, insertItem) {
                    if (item.denotationChar === "/" && item.tip) {
                        const mentionCharPos = this.mentionCharPos;
                        const cursorPos = this.cursorPos;
                        if (typeof mentionCharPos === 'number' && typeof cursorPos === 'number' && cursorPos >= mentionCharPos) {
                            this.quill.deleteText(mentionCharPos, cursorPos - mentionCharPos, Quill.sources.USER);
                            this.quill.setSelection(mentionCharPos, 0, Quill.sources.USER);
                        }
                        if (["@", "#", "~", "%"].includes(item.tip)) {
                            this.openMenu(item.tip);
                        } else {
                            const insertText = item.tip.endsWith(' ') ? item.tip : `${item.tip} `;
                            const insertAt = typeof mentionCharPos === 'number' ? mentionCharPos : (this.quill.getSelection(true)?.index || 0);
                            this.quill.insertText(insertAt, insertText, Quill.sources.USER);
                            this.quill.setSelection(insertAt + insertText.length, 0, Quill.sources.USER);
                        }
                        return;
                    }
                    insertItem(item);
                },
                source: (searchTerm, renderList, mentionChar) => {
                    const mentionMap = {
                        '@': 'user-mention',
                        '#': 'task-mention',
                        '~': 'file-mention',
                        '%': 'report-mention',
                        '/': 'slash-mention'
                    };
                    const mentionName = mentionMap[mentionChar] || 'file-mention';
                    const containers = document.getElementsByClassName("ql-mention-list-container");
                    for (let i = 0; i < containers.length; i++) {
                        containers[i].classList.remove(...Object.values(mentionMap));
                        containers[i].classList.add(mentionName);
                        containers[i].style.zIndex = window.modalTransferIndex + 1000;
                    }
                    let mentionSourceCache = null;
                    this.getMentionSource(mentionChar, searchTerm, array => {
                        const values = [];
                        array.some(item => {
                            let list = item.list;
                            if (searchTerm) {
                                list = list.filter(({id, value, key}) => {
                                    if (/^\d+$/.test(searchTerm) && id && id == searchTerm) {
                                        return true;
                                    }
                                    return $A.strExists(key || value, searchTerm)
                                });
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

        extractMentionData(node) {
            let denotationChar = node.getAttribute('data-denotation-char');
            let dataId = node.getAttribute('data-id') || node.getAttribute('href');
            let dataValue = node.getAttribute('data-value');

            if (!denotationChar || !dataValue) {
                const textContent = node.textContent || node.innerText || '';
                const match = textContent.match(/^([@#~%])(.*)$/);
                if (match) {
                    denotationChar = denotationChar || match[1];
                    dataValue = dataValue || match[2];
                }
            }

            if (!denotationChar || !dataId || !dataValue) {
                return null
            }

            return {
                denotationChar: denotationChar,
                id: dataId,
                value: dataValue
            };
        },

        updateEmojiQuick(text) {
            if (!this.isFocus || !text) {
                this.emojiQuickShow = false
                return
            }
            this.emojiTimer && clearTimeout(this.emojiTimer)
            this.emojiTimer = setTimeout(_ => {
                this.emojiTimer = null
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
                    const baseUrl = $A.mainUrl("images/emoticon")
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

        inputActivated() {
            return !this.fullInput && inputLoadIsLast(this._uid)
        },

        getEditor() {
            return this.fullInput ? this.fullQuill : this.quill
        },

        getText() {
            if (this.quill) {
                return `${this.quill.getText()}`.replace(/^\s+|\s+$/g, "")
            }
            return "";
        },

        insertText(text) {
            if (this.quill) {
                const {index} = this.quill.getSelection(true);
                this.quill.insertText(index, text)
            }
        },

        setText(value) {
            if (this.quill) {
                this.quill.setText(value)
            }
        },

        setContent(value) {
            if (this.quill) {
                this.quill.setContents(this.quill.clipboard.convert({html: value}))
            }
        },

        setPasteMode(bool) {
            this.pasteClean = bool
        },

        loadInputDraft() {
            if (this.simpleMode || !this.draftData) {
                this.$emit('input', '')
                return
            }
            this.pasteClean = false
            this.$emit('input', this.draftData)
            this.$nextTick(_ => this.pasteClean = true)
        },

        onClickEditor() {
            this.clearSearchKey()
            this.updateEmojiQuick(this.value)
            !this.isFocus && this.focus()
            inputLoadAdd(this._uid)
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
                const quill = this.getEditor();
                if (quill) {
                    if (!this.selectRange?.index) {
                        const length = quill.getLength();
                        quill.setSelection(Math.min(globalRangeIndexs[this.draftId] || length, length));
                    }
                    quill.focus()
                }
            })
        },

        blur() {
            this.$nextTick(() => {
                this.getEditor()?.blur()
            })
        },

        clickSend(action, event) {
            if (this.loading) {
                return;
            }
            switch (action) {
                case 'down':
                    this.touchFocus = this.quill?.hasFocus();
                    this.touchLimitX = false;
                    this.touchLimitY = false;
                    this.touchStart = event.type === "touchstart" ? event.touches[0] : event;
                    if ((event.button === undefined || event.button === 0) && this.startRecord()) {
                        return;
                    }
                    if (event.button === 2){
                        this.onShowMenu()
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
                    if (this.stopRecord(this.touchLimitY, this.touchLimitX)) {
                        return;
                    }
                    if (this.touchLimitY || this.touchLimitX) {
                        return; // 移动了 X、Y 轴
                    }
                    this.onSend()
                    break;

                case 'click':
                    if (this.showMenu) {
                        this.tempHiddenSendTip()
                        this.showMenu = false;
                    }
                    if (this.touchFocus) {
                        this.quill.blur();
                        this.quill.focus();
                    }
                    break;
            }
        },

        onShowMenu() {
            if (this.sendClass === 'recorder' || !this.sendMenu) {
                return;
            }
            this.showMenu = true;
        },

        onSend(type = 'auto') {
            this.emojiTimer && clearTimeout(this.emojiTimer)
            this.emojiQuickShow = false;
            //
            setTimeout(_ => {
                if ($A.filterInvalidLine(this.value) === '') {
                    return
                }
                this.hidePopover('send')
                this.rangeIndex = 0
                this.clearSearchKey()
                //
                if (type === 'auto') {
                    type = isMarkdownFormat(this.value) ? 'md' : ''
                }
                if (type === 'normal') {
                    type = ''
                }
                const content = this.value;
                this.persistInputHistory(content);
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
                                if (this.recordState == "stop") {
                                    this.recordRec.close();
                                } else {
                                    this.recordRec.start()
                                }
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

        stopRecord(isCancel, isConvert = false) {
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
                            this.recordDuration = duration;
                            if (isConvert === true) {
                                this.blur();
                                this.convertRecord();
                            } else {
                                this.uploadRecord();
                            }
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

        onTouchClick(e, el) {
            let action = el.getAttribute('data-action')
            if (action === "children") {
                action = e.target?.getAttribute('data-action')
            }
            switch (action) {
                case "cancel-quote":
                    this.cancelQuote()
                    break;

                case "record-convert-cancel":
                    this.recordConvertIng = false
                    break;

                case "record-convert-voice":
                    this.convertSend('voice')
                    break;

                case "record-convert-result":
                    this.convertSend('result')
                    break;
            }
        },

        convertRecord() {
            if (this.recordBlob === null) {
                this.recordConvertIng = false
                return;
            }
            this.recordConvertResult = ''
            this.recordConvertStatus = 0
            this.recordConvertIng = true
            //
            const reader = new FileReader();
            reader.onloadend = () => {
                this.$store.dispatch("call", {
                    url: 'dialog/msg/convertrecord',
                    data: {
                        dialog_id: this.dialogId,
                        base64: reader.result,
                        duration: this.recordDuration,
                        translate: this.recordConvertTranslate
                    },
                    method: 'post',
                }).then(({data}) => {
                    if (data) {
                        this.recordConvertStatus = 1
                        this.recordConvertResult = data
                        this.recordConvertSetting = true
                    } else {
                        this.recordConvertStatus = 2
                        this.recordConvertResult = this.$L('转文字失败')
                    }
                }).catch(({msg}) => {
                    this.recordConvertStatus = 2
                    this.recordConvertResult = msg
                });
            };
            reader.readAsDataURL(this.recordBlob);
        },

        async convertSetting(event) {
            if (this.recordConvertStatus !== 1) {
                $A.messageWarning("请稍后再试...")
                return;
            }
            await this.$nextTick()
            const list = Object.keys(languageList).map(item => ({
                label: languageList[item],
                value: item
            }))
            list.unshift(...[
                {label: this.$L('选择翻译结果'), value: '', disabled: true},
                {label: this.$L('不翻译结果'), value: '', divided: true},
            ])
            this.$store.commit('menu/operation', {
                event,
                list,
                active: this.recordConvertTranslate,
                language: false,
                onUpdate: (language) => {
                    this.recordConvertTranslate = language
                    this.convertRecord()
                }
            })
        },

        convertSend(type) {
            if (!this.recordConvertIng) {
                return;
            }
            if (type === 'voice') {
                this.uploadRecord();
                this.recordConvertIng = false
            } else {
                if (this.recordConvertStatus === 1) {
                    this.$emit('on-send', this.recordConvertResult)
                    this.recordConvertIng = false
                } else if (this.recordConvertStatus === 2) {
                    this.convertRecord()
                }
            }
        },

        uploadRecord() {
            if (this.recordBlob === null) {
                return;
            }
            const reader = new FileReader();
            reader.onloadend = () => {
                this.$emit('on-record', {
                    type: this.recordBlob.type,
                    base64: reader.result,
                    duration: this.recordDuration,
                })
            };
            reader.readAsDataURL(this.recordBlob);
        },

        onEmojiQuick(item) {
            if (item.type === 'online') {
                this.$emit('on-send', `<img src="${item.src}"/>`)
            } else {
                this.$emit('on-send', `<img class="emoticon" data-asset="${item.asset}" data-name="${item.name}" src="${item.src}"/>`)
            }
            this.$emit('input', "")
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
                this.rangeLength = 0;
                this.quill.setSelection(this.rangeIndex, 0, 'silent');
                if (this.windowLandscape && !this.isModKey) {
                    this.showEmoji = false;
                }
            } else if (item.type === 'emoticon') {
                this.$emit('on-send', `<img class="emoticon" data-asset="${item.asset}" data-name="${item.name}" src="${item.src}"/>`)
                if (item.asset === "emosearch") {
                    this.$emit('input', "")
                }
                if (this.windowLandscape && !this.isModKey) {
                    this.showEmoji = false;
                }
            }
        },

        onEmojiDelete() {
            if (!this.quill) {
                return;
            }
            const savedRange = this.quill.selection?.savedRange || this.quill.getSelection();
            if (savedRange && typeof savedRange.index === 'number') {
                this.rangeIndex = savedRange.index;
                this.rangeLength = savedRange.length || 0;
            }
            if (this.rangeLength > 0) {
                this.quill.deleteText(this.rangeIndex, this.rangeLength);
                this.rangeLength = 0;
            } else if (this.rangeIndex > 0) {
                const deleteLength = this.getPreviousGraphemeLength(this.rangeIndex);
                if (deleteLength > 0) {
                    this.quill.deleteText(this.rangeIndex - deleteLength, deleteLength);
                    this.rangeIndex -= deleteLength;
                }
            }
            this.quill.setSelection(this.rangeIndex, 0, 'silent');
        },

        getPreviousGraphemeLength(index) {
            if (!this.quill || index <= 0) {
                return 0;
            }
            const textBeforeCursor = this.quill.getText(0, index);
            if (!textBeforeCursor) {
                return 0;
            }
            if (typeof Intl !== 'undefined' && typeof Intl.Segmenter === 'function') {
                if (!this.graphemeSegmenter) {
                    this.graphemeSegmenter = new Intl.Segmenter(undefined, {granularity: 'grapheme'});
                }
                let lastSegment;
                for (const segment of this.graphemeSegmenter.segment(textBeforeCursor)) {
                    lastSegment = segment;
                }
                if (lastSegment && lastSegment.segment) {
                    return lastSegment.segment.length;
                }
            }
            const fallbackWindow = Math.min(index, 8);
            const fallbackText = this.quill.getText(index - fallbackWindow, fallbackWindow);
            if (!fallbackText) {
                return 0;
            }
            const fallbackGraphemes = Array.from(fallbackText);
            const lastGrapheme = fallbackGraphemes.pop();
            return lastGrapheme ? lastGrapheme.length : 0;
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

                case 'ai':
                    this.onMessageAI();
                    break;

                case 'maybe-photo':
                    this.$emit('on-file', {
                        type: 'photo',
                        msg: {
                            type: 'img',
                            filename: this.maybePhotoData.original.name,
                            path: this.maybePhotoData.original.path,
                            width: this.maybePhotoData.original.width,
                            height: this.maybePhotoData.original.height,
                            thumb: this.maybePhotoData.thumbnail.base64,
                        }
                    })
                    break;

                case 'meeting':
                    emitter.emit('addMeeting', {
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

        onMessageAI() {
            if (this.disabled) {
                return;
            }
            if (!this.dialogId) {
                $A.messageWarning('当前未选择会话');
                return;
            }
            emitter.emit('openAIAssistant', {
                sessionKey: 'chat-message',
                title: this.$L('AI 消息助手'),
                placeholder: this.$L('请简要描述消息的主题、语气或要点，AI 将生成完整消息'),
                onBeforeSend: this.handleMessageAIBeforeSend,
                onApply: this.handleMessageAIApply,
            });
        },

        handleMessageAIBeforeSend(context = []) {
            const prepared = [
                ['system', withLanguagePreferencePrompt(MESSAGE_AI_SYSTEM_PROMPT)]
            ];
            let assistantContext = this.buildMessageAssistantContext();
            if (assistantContext) {
                if ($A.getObject(context, [0,0]) === 'human') {
                    assistantContext += "\n----\n请根据以上信息，结合以下用户输入的内容生成消息：++++";
                }
                prepared.push(['human', assistantContext]);
            }
            if (context.length > 0) {
                prepared.push(...context);
            }
            return prepared;
        },

        handleMessageAIApply({rawOutput}) {
            if (!rawOutput) {
                $A.messageWarning('AI 未生成内容');
                return;
            }
            const html = MarkdownConver(rawOutput);
            this.$emit('input', html);
            this.$nextTick(() => this.focus());
        },

        buildMessageAssistantContext() {
            const sections = [];
            const infoLines = [];
            if (this.dialogData?.name) {
                infoLines.push(`名称：${cutText(this.dialogData.name, 60)}`);
            }
            if (this.dialogData?.type) {
                const typeMap = {group: this.$L('群聊'), user: this.$L('单聊')};
                infoLines.push(`类型：${typeMap[this.dialogData.type] || this.dialogData.type}`);
            }
            if (this.dialogData?.group_type) {
                infoLines.push(`分类：${cutText(this.dialogData.group_type, 60)}`);
            }
            if (infoLines.length) {
                sections.push('## 会话信息');
                sections.push(...infoLines);
            }

            const memberNames = this.collectDialogMemberNames();
            if (memberNames.length) {
                sections.push('## 会话成员');
                sections.push(memberNames.join('，'));
            }

            const recentMessages = this.collectRecentMessages();
            if (recentMessages.length) {
                sections.push('## 最近消息');
                recentMessages.forEach(({sender, summary}) => {
                    if (summary) {
                        const name = sender || this.$L('成员');
                        sections.push(`- ${name}：${summary}`);
                    }
                });
            }

            if (this.quoteData) {
                const quoteSummary = this.getMessageSummaryText(this.quoteData);
                if (quoteSummary) {
                    sections.push('## 引用消息');
                    const quoteUser = this.resolveUserNickname(this.quoteData.userid);
                    sections.push(quoteUser ? `${quoteUser}：${quoteSummary}` : quoteSummary);
                }
            }

            const draftText = extractPlainText(this.value, 500);
            if (draftText) {
                sections.push('## 当前草稿');
                sections.push(draftText);
            }

            return sections.join('\n');
        },

        collectDialogMemberNames(limit = 10) {
            if (!this.dialogId) {
                return [];
            }
            const result = [];
            const seen = new Set();
            const pushName = (name) => {
                const clean = cutText((name || '').trim(), 30);
                if (!clean || seen.has(clean)) {
                    return;
                }
                seen.add(clean);
                result.push(clean);
            };

            if (this.dialogData?.dialog_user) {
                pushName(this.dialogData.dialog_user.nickname || this.dialogData.dialog_user.name);
            }

            const messages = this.dialogMsgs.filter(item => item.dialog_id == this.dialogId);
            for (let i = messages.length - 1; i >= 0 && result.length < limit; i--) {
                pushName(this.resolveUserNickname(messages[i].userid));
            }

            const currentUserId = this.$store?.state?.userInfo?.userid;
            if (currentUserId) {
                pushName(this.resolveUserNickname(currentUserId));
            }

            return result.slice(0, limit);
        },

        collectRecentMessages(limit = 15) {
            if (!this.dialogId) {
                return [];
            }
            const messages = this.dialogMsgs.filter(item => item.dialog_id == this.dialogId);
            if (messages.length === 0) {
                return [];
            }
            const sorted = messages.slice().sort((a, b) => a.id - b.id);
            const result = [];
            for (let i = sorted.length - 1; i >= 0 && result.length < limit; i--) {
                const msg = sorted[i];
                const summary = this.getMessageSummaryText(msg);
                if (!summary) {
                    continue;
                }
                result.unshift({
                    sender: this.resolveUserNickname(msg.userid) || this.$L('成员'),
                    summary,
                });
            }
            return result;
        },

        getMessageSummaryText(message) {
            if (!message) {
                return '';
            }
            try {
                const preview = $A.getMsgSimpleDesc(message);
                return extractPlainText(preview || '', 300);
            } catch (error) {
                return '';
            }
        },

        resolveUserNickname(userid) {
            if (!userid) {
                return '';
            }
            const currentUser = this.$store?.state?.userInfo;
            if (currentUser && currentUser.userid == userid) {
                return currentUser.nickname || currentUser.username || currentUser.name || '';
            }
            const cached = this.cacheUserBasic.find(user => user.userid == userid);
            if (cached) {
                return cached.nickname || cached.name || cached.username || '';
            }
            if (this.dialogData?.dialog_user && this.dialogData.dialog_user.userid == userid) {
                return this.dialogData.dialog_user.nickname || this.dialogData.dialog_user.name || '';
            }
            return '';
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
                            toolbar: false,
                            selectionPlugin: {
                                onTextSelected: (selectedText) => {
                                    this.fullSelected = !!selectedText.trim()
                                },
                                onSelectionCleared: () => {
                                    this.fullSelected = false
                                }
                            },
                            mention: this.quillMention()
                        }
                    }, this.options))
                    this.fullQuill.on('selection-change', range => {
                        if (range) {
                            this.fullSelection = range
                        } else if (this.fullSelection && document.activeElement && /(ql-editor|ql-clipboard)/.test(document.activeElement.className)) {
                            // 修复iOS光标会超出的问题
                            this.selectTimer && clearTimeout(this.selectTimer)
                            this.selectTimer = setTimeout(_ => {
                                this.fullQuill.setSelection(this.fullSelection.index, this.fullSelection.length)
                            }, 100)
                        }
                    })
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

        onMenu(action, _, el) {
            if (action !== 'up') {
                return;
            }
            const quill = this.getEditor();
            const {length} = quill.getSelection(true);
            if (length === 0) {
                $A.messageWarning("请选择文字后再操作")
                return
            }
            const label = el.getAttribute('data-label');
            switch (label) {
                case 'bold':
                    quill.format('bold', !quill.getFormat().bold);
                    break;
                case 'strike':
                    quill.format('strike', !quill.getFormat().strike);
                    break;
                case 'italic':
                    quill.format('italic', !quill.getFormat().italic);
                    break;
                case 'underline':
                    quill.format('underline', !quill.getFormat().underline);
                    break;
                case 'blockquote':
                    quill.format('blockquote', !quill.getFormat().blockquote);
                    break;
                case 'link':
                    if (quill.getFormat().link) {
                        quill.format('link', false);
                        return
                    }
                    $A.modalInput({
                        title: "插入链接",
                        placeholder: "请输入完整的链接地址",
                        onOk: (link) => {
                            if (!link) {
                                return false;
                            }
                            quill.format('link', link);
                        }
                    })
                    break;
                case 'list':
                    const type = el.getAttribute('data-type') || '';
                    quill.format('list', quill.getFormat().list === type ? false : type);
                    break;
            }
        },

        setQuote(id, type = 'reply') {
            if (this.dialogId <= 0) {
                return
            }
            const content = this.dialogMsgs.find(item => item.id == id && item.dialog_id == this.dialogId)
            if (!content) {
                this.$store.dispatch("removeDialogQuote", this.dialogId);
                return
            }
            this.$store.dispatch("saveDialogQuote", {
                id: this.dialogId,
                type: type === 'update' ? 'update' : 'reply',
                content
            });
        },

        cancelQuote() {
            if (this.quoteUpdate) {
                // 取消修改
                this.$emit('input', '')
            } else if (this.quoteData) {
                // 取消回复
                const {firstChild} = this.$refs.editor;
                if (firstChild && firstChild.querySelectorAll('img').length === 0) {
                    const mentions = firstChild.querySelectorAll("span.mention");
                    if (mentions.length === 1) {
                        const element = mentions[0];
                        if (element.getAttribute("data-id") == this.quoteData.userid) {
                            const parent = element.parentNode;
                            parent.normalize();
                            const nodes = Array.from(parent.childNodes).filter(node => {
                                return node.nodeType !== Node.TEXT_NODE || !/^\s*$/.test(node.textContent);
                            })
                            if (nodes.length === 1) {
                                element.remove();
                            }
                        }
                    }
                    if (!firstChild.innerText.replace(/\s/g, '')) {
                        this.$emit('input', '')
                    }
                }
            }
            this.setQuote(0)
        },

        onQuoteUserResult(userData) {
            if (!this.quoteChanged) {
                return
            }
            this.quoteChanged = false
            // 基本判断
            if (
                this.dialogData.type !== 'group' ||         // 非群聊
                this.quoteUpdate ||                         // 修改消息
                !this.quoteData ||                          // 无引用消息
                !this.replyMsgAutoMention ||                // 不自动@
                this.userId === userData.userid ||          // 自己
                this.quoteData.userid !== userData.userid   // 不同人
            ) {
                return
            }
            // 判断是否已经@过
            if (new RegExp(`<span[^>]+?class="mention"[^>]+?data-id="${userData.userid}"[^>]*?>`).test(this.$refs.editor.firstChild?.innerHTML)) {
                return
            }
            // 添加@
            this.addMention({
                denotationChar: "@",
                id: userData.userid,
                value: userData.nickname,
            })
        },

        onSpaceInputFocus() {
            if (this.selectRange) {
                // 修复Android光标会超出的问题
                this.quill?.setSelection(this.selectRange.index, this.selectRange.length)
            }
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
            if (!this.inputActivated()) {
                return;
            }
            const {index} = this.quill.getSelection(true);
            this.quill.insertEmbed(index, "mention", data, Quill.sources.USER);
            this.quill.insertText(index + 1, " ", Quill.sources.USER);
            this.quill.setSelection(index + 2, Quill.sources.USER);
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
                            // 群外成员 排序 -> 前5名为最近联系的人
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
                                        list: [{id: 0, value: this.$L('所有人.All'), tip: ''}]
                                    })
                                }
                                this.userCache.push(...[{
                                    label: [{id: 0, value: this.$L('群内成员'), className: "sticky-top", disabled: true}],
                                    list,
                                }, {
                                    label: [{id: 0, value: this.$L('群外成员'), className: "sticky-top", disabled: true}],
                                    list: moreUser,
                                }])
                            } else {
                                if (list.length > 2) {
                                    this.userCache.push(...[{
                                        label: null,
                                        list: [{id: 0, value: this.$L('所有人.All'), tip: ''}]
                                    }, {
                                        label: [{id: 0, value: this.$L('群成员'), className: "sticky-top", disabled: true}],
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
                                    people: data.length,
                                    people_user: data.filter(item => !item.bot).length,
                                    people_bot: data.filter(item => item.bot).length,
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
                                        key: `${item.nickname} ${item.email} ${item.pinyin}`
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
                                        key: `${item.nickname} ${item.email} ${item.pinyin}`
                                    })
                                }
                            })
                        }
                        atCallback(array)
                    }
                    break;

                case "#": // #任务
                    this.mentionMode = "task-mention";
                    const searchKey = (searchTerm || '').trim();
                    this.taskSearchKey = searchKey;
                    const buildOtherTasks = (list) => {
                        const baseLists = Array.isArray(list) ? list : [];
                        if (!searchKey) {
                            return baseLists;
                        }
                        const searchTasks = Array.isArray(this.taskSearchList[searchKey]) ? this.taskSearchList[searchKey] : [];
                        if (searchTasks.length === 0) {
                            return baseLists;
                        }
                        const existingIds = new Set();
                        baseLists.forEach(group => {
                            (group.list || []).forEach(item => existingIds.add(item.id));
                        });
                        const otherTasks = [];
                        searchTasks.forEach(task => {
                            if (!existingIds.has(task.id)) {
                                existingIds.add(task.id);
                                otherTasks.push({
                                    id: task.id,
                                    value: task.name,
                                    tip: task.complete_at ? this.$L('已完成') : null,
                                });
                            }
                        });
                        if (otherTasks.length === 0) {
                            return baseLists;
                        }
                        return [
                            ...baseLists,
                            {
                                label: [{id: 0, value: this.$L('其他任务'), className: "sticky-top", disabled: true}],
                                list: otherTasks,
                            }
                        ];
                    };
                    const renderTaskList = (list) => resultCallback(buildOtherTasks(list || []))
                    if (this.taskList !== null) {
                        renderTaskList(this.taskList)
                    } else {
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
                                    label: [{id: 0, value: this.$L('项目任务'), className: "sticky-top", disabled: true}],
                                    list,
                                })
                            }
                            // 待完成任务
                            const { overdue, today, todo } = this.$store.getters.dashboardTask;
                            const combinedTasks = [...overdue, ...today, ...todo];
                            let allTask = this.$store.getters.transforTasks(combinedTasks);
                            if (allTask.length > 0) {
                                allTask = [...allTask].sort((a, b) => {
                                    return $A.sortDay(a.end_at || "2099-12-31 23:59:59", b.end_at || "2099-12-31 23:59:59");
                                }).slice(0, 100)
                                this.taskList.push({
                                    label: [{id: 0, value: this.$L('我的待完成任务'), className: "sticky-top", disabled: true}],
                                    list: allTask.map(item => {
                                        return {
                                            id: item.id,
                                            value: item.name
                                        }
                                    }),
                                })
                            }
                            // 我协助的任务
                            let assistTask = this.$store.getters.assistTask || [];
                            if (assistTask.length > 0) {
                                assistTask = [...assistTask].sort((a, b) => {
                                    return $A.sortDay(a.end_at || "2099-12-31 23:59:59", b.end_at || "2099-12-31 23:59:59");
                                }).slice(0, 100)
                                this.taskList.push({
                                    label: [{id: 0, value: this.$L('我协助的任务'), className: "sticky-top", disabled: true}],
                                    list: assistTask.map(item => {
                                        return {
                                            id: item.id,
                                            value: item.name
                                        }
                                    }),
                                })
                            }
                            renderTaskList(this.taskList)
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
                                    return $A.sortDay(b.complete_at || "2099-12-31 23:59:59", a.complete_at || "2099-12-31 23:59:59")
                                })
                                if (tasks.length > 0) {
                                    taskCallback(tasks)
                                } else {
                                    taskCallback([])
                                }
                            }).catch(_ => {
                                taskCallback([])
                            })
                        } else {
                            taskCallback([])
                        }
                    }
                    if (searchKey) {
                        if (Array.isArray(this.taskSearchList[searchKey])) {
                            return;
                        }
                        this.taskSearchTimer && clearTimeout(this.taskSearchTimer)
                        this.taskSearchTimer = setTimeout(async _ => {
                            if (this.taskSearchKey !== searchKey) {
                                return;
                            }
                            const data = (await this.$store.dispatch("call", {
                                url: 'project/task/lists',
                                data: {
                                    keys: {
                                        name: searchKey,
                                    },
                                    parent_id: -1,
                                    scope: 'all_project',
                                    pagesize: 50,
                                },
                            }).catch(_ => {}))?.data;
                            if (this.taskSearchKey !== searchKey) {
                                return;
                            }
                            const tasks = $A.getObject(data, 'data') || [];
                            this.taskSearchList[searchKey] = tasks.map(item => ({
                                id: item.id,
                                name: item.name,
                                complete_at: item.complete_at,
                            }));
                            renderTaskList(this.taskList)
                        }, 300)
                    }
                    break;

                case "~": // ~文件
                    this.mentionMode = "file-mention";
                    if ($A.isArray(this.fileList[searchTerm])) {
                        resultCallback(this.fileList[searchTerm])
                        return;
                    }
                    this.fileTimer && clearTimeout(this.fileTimer)
                    this.fileTimer = setTimeout(async _ => {
                        const lists = [];
                        const data = (await this.$store.dispatch("searchFiles", searchTerm).catch(_ => {}))?.data;
                        if (data) {
                            lists.push({
                                label: [{id: 0, value: this.$L('文件分享查看'), className: "sticky-top", disabled: true}],
                                list: data.filter(item => item.type !== "folder").map(item => {
                                    return {
                                        id: item.id,
                                        value: item.ext ? `${item.name}.${item.ext}` : item.name
                                    }
                                })
                            })
                            this.fileList[searchTerm] = lists
                        }
                        resultCallback(lists)
                    }, 300)
                    break;

                case "%": // %报告
                    this.mentionMode = "report-mention";
                    if ($A.isArray(this.reportList[searchTerm])) {
                        resultCallback(this.reportList[searchTerm])
                        return;
                    }
                    this.reportTimer && clearTimeout(this.reportTimer)
                    this.reportTimer = setTimeout(async _ => {
                        const lists = [];
                        let wait = 2;
                        const myData = (await this.$store.dispatch("call", {
                            url: 'report/my',
                            data: {
                                keys: {
                                    key: searchTerm,
                                },
                            },
                        }).catch(_ => {}))?.data;
                        if (myData) {
                            lists.push({
                                label: [{id: 0, value: this.$L('我的报告'), className: "sticky-top", disabled: true}],
                                list: myData.data.map(item => {
                                    return {
                                        id: item.id,
                                        value: item.title
                                    }
                                })
                            })
                            wait--
                        }
                        const receiveData = (await this.$store.dispatch("call", {
                            url: 'report/receive',
                            data: {
                                keys: {
                                    key: searchTerm,
                                },
                            },
                        }).catch(_ => {}))?.data;
                        if (receiveData) {
                            lists.push({
                                label: [{id: 0, value: this.$L('收到的报告'), className: "sticky-top", disabled: true}],
                                list: receiveData.data.map(item => {
                                    return {
                                        id: item.id,
                                        value: item.title
                                    }
                                })
                            })
                            wait--
                        }
                        if (wait === 0) {
                            this.reportList[searchTerm] = lists
                        }
                        resultCallback(lists)
                    }, 300)
                    break;

                case "/": // /快捷菜单
                    this.mentionMode = "slash-mention";
                    const allowBotCommands = this.isSlashAtLineStart();
                    const isBotDialog = this.dialogData.type === 'user' && this.dialogData.bot;
                    const isOwnBot = isBotDialog && this.dialogData.bot == this.userId;
                    const isBotManager = isBotDialog && this.dialogData.email === 'bot-manager@bot.system';
                    const showBotCommands = allowBotCommands && (isOwnBot || isBotManager);
                    const baseLabel = showBotCommands ? [{id: 0, value: this.$L('快捷菜单'), className: "sticky-top", disabled: true}] : null;
                    const slashLists = [{
                        label: baseLabel,
                        list: [
                            {
                                id: 'mention',
                                value: this.$L('提及'),
                                tip: '@',
                            },
                            {
                                id: 'task',
                                value: this.$L('任务'),
                                tip: '#',
                            },
                            {
                                id: 'file',
                                value: this.$L('文件'),
                                tip: '~',
                            },
                            {
                                id: 'report',
                                value: this.$L('工作报告'),
                                tip: '%',
                            },
                        ]
                    }];
                    if (showBotCommands) {
                        const commandList = [];
                        if (isBotManager) {
                            commandList.push(
                                {
                                    id: 'list',
                                    value: this.$L('我的机器人'),
                                    tip: '/list',
                                },
                                {
                                    id: 'newbot',
                                    value: this.$L('新建机器人'),
                                    tip: '/newbot',
                                }
                            );
                        }
                        commandList.push(
                            {
                                id: 'help',
                                value: this.$L('帮助指令'),
                                tip: '/help',
                            },
                            {
                                id: 'api',
                                value: this.$L('API接口文档'),
                                tip: '/api',
                            },
                            {
                                id: 'info',
                                value: this.$L('机器人信息'),
                                tip: '/info',
                            },
                            {
                                id: 'setname',
                                value: this.$L('设置名称'),
                                tip: '/setname',
                            },
                            {
                                id: 'deletebot',
                                value: this.$L('删除机器人'),
                                tip: '/deletebot',
                            },
                            {
                                id: 'token',
                                value: this.$L('机器人Token'),
                                tip: '/token',
                            },
                            {
                                id: 'revoke',
                                value: this.$L('更新Token'),
                                tip: '/revoke',
                            },
                            {
                                id: 'clearday',
                                value: this.$L('设置保留消息时间'),
                                tip: '/clearday',
                            },
                            {
                                id: 'webhook',
                                value: this.$L('设置Webhook'),
                                tip: '/webhook',
                            },
                            {
                                id: 'dialog',
                                value: this.$L('对话列表'),
                                tip: '/dialog',
                            }
                        );
                        slashLists.push({
                            label: [{id: 0, value: this.$L('机器人命令'), className: "sticky-top", disabled: true}],
                            list: commandList,
                        });
                    }
                    resultCallback(slashLists)
                    break;

                default:
                    resultCallback([])
                    break;
            }
        },

        isSlashAtLineStart() {
            const editor = this.getEditor();
            const mention = editor?.getModule("mention");
            const mentionCharPos = mention?.mentionCharPos;
            if (!editor || typeof mentionCharPos !== 'number') {
                return false;
            }
            const textLength = Math.max(0, editor.getLength() - 1);
            if (textLength > 100) {
                return false;
            }
            const prefixText = editor.getText(0, mentionCharPos) || '';
            const lastBreak = Math.max(prefixText.lastIndexOf("\n"), prefixText.lastIndexOf("\r"));
            if (lastBreak >= 0) {
                return false;
            }
            return prefixText.trim().length === 0;
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
                                    bot: 2,
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
                                    bot: !!item.bot,
                                    key: `${item.nickname} ${item.email} ${item.pinyin}`
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
            }
        },

        updateTools() {
            if (this.showEmoji) {
                this.$refs.emoji?.updatePopper()
            }
            if (this.showMore) {
                this.$refs.more?.updatePopper()
            }
            if (this.showMenu) {
                this.$refs.menu?.updatePopper()
            }
            const mention = this.quill?.getModule("mention")
            if (mention.isOpen) {
                mention.setMentionContainerPosition()
            }
        },

        tempHiddenSendTip() {
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
        },
    }
}
</script>
