<template>
    <div :class="classArray">
        <div v-if="isUnreadStart" class="dialog-unread-label">
            <em></em><span>{{$L('以下为新消息')}}</span><em></em>
        </div>
        <div v-if="source.type === 'tag'" class="dialog-tag" @click="onViewTag">
            <div class="tag-user"><UserAvatar :userid="source.userid" :show-name="true" :show-icon="false"/></div>
            {{$L(source.msg.action === 'remove' ? '取消标注' : '标注了')}}
            "{{$A.getMsgSimpleDesc(source.msg.data)}}"
        </div>
        <div v-else-if="source.type === 'top'" class="dialog-top" @click="onViewTag">
            <div class="tag-user"><UserAvatar :userid="source.userid" :show-name="true" :show-icon="false"/></div>
            {{$L(source.msg.action === 'remove' ? '取消置顶' : '置顶了')}}
            "{{$A.getMsgSimpleDesc(source.msg.data)}}"
        </div>
        <div v-else-if="source.type === 'todo'" class="dialog-todo" @click="onViewTodo">
            <div class="todo-user"><UserAvatar :userid="source.userid" :show-name="true" :show-icon="false"/></div>
            {{$L(source.msg.action === 'remove' ? '取消待办' : (source.msg.action === 'done' ? '完成' : '设待办'))}}
            "{{$A.getMsgSimpleDesc(source.msg.data)}}"
            <div v-if="formatTodoUser(source.msg.data).length > 0" class="todo-users">
                <span>{{$L('给')}}</span>
                <template v-for="(item, index) in formatTodoUser(source.msg.data)">
                    <div v-if="index < 3" class="todo-user"><UserAvatar :userid="item" :show-name="true" :show-icon="false"/></div>
                    <div v-else-if="index == 3" class="todo-user">+{{formatTodoUser(source.msg.data).length - 3}}</div>
                </template>
            </div>
        </div>
        <div v-else-if="source.type === 'notice'" class="dialog-notice">
            {{$L(source.msg.notice)}}
        </div>
        <template v-else>
            <div class="dialog-avatar">
                <UserAvatar
                    v-longpress="{callback: onMention, delay: 300}"
                    @open-dialog="onOpenDialog"
                    :userid="source.userid"
                    :size="30"/>
            </div>
            <DialogView
                :msg-data="source"
                :dialog-type="dialogData.type"
                :hide-percentage="hidePercentage"
                :hide-reply="hideReply"
                :hide-forward="hideForward"
                :operate-visible="operateVisible"
                :operate-action="operateVisible && source.id === operateItem.id"
                :pointer-mouse="pointerMouse"
                :is-right-msg="isRightMsg"
                @on-longpress="onLongpress"
                @on-view-reply="onViewReply"
                @on-view-text="onViewText"
                @on-view-file="onViewFile"
                @on-down-file="onDownFile"
                @on-reply-list="onReplyList"
                @on-error="onError"
                @on-emoji="onEmoji"
                @on-other="onOther"
                @on-show-emoji-user="onShowEmojiUser"/>
        </template>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogView from "./DialogView";
import longpress from "../../../directives/longpress";

export default {
    name: "DialogItem",
    components: {DialogView},
    directives: {longpress},
    props: {
        source: {
            type: Object,
            default() {
                return {}
            }
        },
        dialogData: {
            type: Object,
            default() {
                return {}
            }
        },
        operateVisible: {
            type: Boolean,
            default: false
        },
        operateItem: {
            type: Object,
            default() {
                return {}
            }
        },
        pointerMouse: {
            type: Boolean,
            default: false
        },
        simpleView: {
            type: Boolean,
            default: false
        },
        isMyDialog: {
            type: Boolean,
            default: false
        },
        msgId: {
            type: Number,
            default: 0
        },
        unreadOne: {
            type: Number,
            default: 0
        },
        scrollIng: {
            type: Number,
            default: 0
        },
        readEnabled: {
            type: Boolean,
            default: false
        },
    },

    computed: {
        ...mapState(['userId']),

        isRightMsg() {
            return this.source.userid == this.$store.state.userId
        },

        isReply() {
            return this.simpleView || this.msgId === this.source.id
        },

        isNoRead() {
            return this.isRightMsg || this.source.read_at
        },

        isUnreadStart() {
            return this.unreadOne === this.source.id
        },

        hidePercentage() {
            return this.simpleView || this.isMyDialog || this.isReply
        },

        hideReply() {
            return this.simpleView || this.msgId > 0
        },

        hideForward() {
            return this.simpleView || this.msgId > 0
        },

        classArray() {
            return {
                'dialog-item': true,
                'reply-item': this.isReply,
                'unread-start': this.isUnreadStart,
                'self': this.isRightMsg,
            }
        },
    },

    watch: {
        readEnabled() {
            this.msgRead();
        },
        windowActive() {
            this.msgRead();
        },
        scrollIng() {
            this.msgRead();
        },
    },

    methods: {
        msgRead() {
            if (this.isNoRead) {
                return;
            }
            if (!this.readEnabled) {
                return;
            }
            if (!this.windowActive) {
                return;
            }
            if (!this.$el?.parentNode.classList.contains('item-enter')) {
                return;
            }
            // 标记已读
            this.$store.dispatch("dialogMsgRead", this.source);
        },

        formatTodoUser(data) {
            if ($A.isJson(data)) {
                const {userids} = data
                if (userids) {
                    return userids.split(",")
                }
            }
            return []
        },

        onViewTag() {
            this.onViewReply({
                msg_id: this.source.id,
                reply_id: this.source.msg.data.id
            })
        },

        onViewTodo() {
            this.onViewReply({
                msg_id: this.source.id,
                reply_id: this.source.msg.data.id
            })
        },

        onOpenDialog(userid) {
            if (this.dialogData.type == 'group' || ![this.dialogData.dialog_user?.userid, this.userId].includes(userid)) {
                this.$store.dispatch("openDialogUserid", userid).then(_ => {
                    this.goForward({name: 'manage-messenger'})
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            }
        },

        onMention() {
            this.dispatch("on-mention", this.source)
        },

        onLongpress(e) {
            this.dispatch("on-longpress", e)
        },

        onViewReply(data) {
            this.dispatch("on-view-reply", data)
        },

        onViewText(e, el) {
            this.dispatch("on-view-text", e, el)
        },

        onViewFile(data) {
            this.dispatch("on-view-file", data)
        },

        onDownFile(data) {
            this.dispatch("on-down-file", data)
        },

        onReplyList(data) {
            this.dispatch("on-reply-list", data)
        },

        onError(data) {
            this.dispatch("on-error", data)
        },

        onEmoji(data) {
            this.dispatch("on-emoji", data)
        },

        onOther(data) {
            this.dispatch("on-other", data)
        },

        onShowEmojiUser(data) {
            this.dispatch("on-show-emoji-user", data)
        },

        dispatch(event, ...arg) {
            if (this.isReply) {
                this.$emit(event, ...arg)
                return
            }

            let parent = this.$parent
            let name = parent.$options.name

            while (parent && (!name || name !== 'virtual-list')) {
                parent = parent.$parent
                if (parent) {
                    name = parent.$options.name
                }
            }

            if (parent) {
                parent.$emit(event, ...arg)
            }
        }
    }
}
</script>
