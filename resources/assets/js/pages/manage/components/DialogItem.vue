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
            <div class="no-dark-content">
                <div v-if="source.msg.action === 'done' && todoDoneDisplayList(source.msg.data).length > 0" class="todo-users">
                    <div
                        v-for="(item, index) in todoDoneDisplayList(source.msg.data)"
                        :key="`todo-done-${item.type}-${item.value}-${index}`"
                        class="todo-user">
                        <UserAvatar v-if="item.type === 'user'" :userid="item.value" :show-name="true" :show-icon="false"/>
                        <span v-else>{{item.value}}</span>
                    </div>
                </div>
                <div v-else class="todo-user"><UserAvatar :userid="source.userid" :show-name="true" :show-icon="false"/></div>
                {{$L(source.msg.action === 'remove' ? '取消待办' : (source.msg.action === 'done' ? '完成' : '设待办'))}}
                "{{$A.getMsgSimpleDesc(source.msg.data)}}"
                <div v-if="source.msg.action === 'add' && formatTodoUser(source.msg.data).length > 0" class="todo-users">
                    <span>{{$L('给')}}</span>
                    <template v-for="(item, index) in formatTodoUser(source.msg.data)">
                        <div v-if="index < 3" class="todo-user"><UserAvatar :userid="item" :show-name="true" :show-icon="false"/></div>
                        <div v-else-if="index == 3" class="todo-user">+{{formatTodoUser(source.msg.data).length - 3}}</div>
                    </template>
                </div>
            </div>
        </div>
        <div v-else-if="source.type === 'notice'" class="dialog-notice">
            {{source.msg.source === 'api' ? source.msg.notice : $L(source.msg.notice)}}
        </div>
        <template v-else>
            <div v-if="multiSelectMode && isSelectableMsg" class="dialog-multi-check" @click.stop="onMultiSelectToggle">
                <Icon :type="isSelected ? 'ios-checkmark-circle' : 'ios-radio-button-off'" :class="{checked: isSelected}"/>
            </div>
            <div
                class="dialog-avatar"
                @pointerdown="handleOperation">
                <!-- AI 助手头像 -->
                <div v-if="source.userid === -1" class="ai-assistant-avatar">
                    <svg class="no-dark-content" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                        <path d="M385.80516777 713.87417358c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404756l-48.91927648-123.9413531c-18.40341303-46.75969229-55.77360888-84.0359932-102.53330118-102.53330117l-123.94135309-48.91927649c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.8257541s7.79328205-24.13100586 19.62404757-28.82575407l123.94135309-48.91927649c46.75969229-18.40341303 84.0359932-55.77360888 102.53330118-102.53330119l48.91927648-123.94135308c4.69474822-11.83076552 16.05603892-19.62404757 28.8257541-19.62404757s24.13100586 7.79328205 28.82575408 19.62404757l48.91927648 123.94135308c18.40341303 46.75969229 55.77360888 84.0359932 102.53330118 102.53330119l123.94135309 48.91927649c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575407 0 12.76971517-7.79328205 24.13100586-19.62404757 28.8257541l-123.94135309 48.91927649c-46.75969229 18.40341303-84.0359932 55.77360888-102.53330118 102.53330117l-48.91927648 123.9413531c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575408 19.62404756zM177.45224165 390.12433614l50.89107073 20.0935224c62.62794129 24.69437565 112.67395736 74.74039171 137.368333 137.36833299l20.09352239 50.89107073 20.0935224-50.89107073c24.69437565-62.62794129 74.74039171-112.67395736 137.368333-137.36833299l50.89107072-20.0935224-50.89107073-20.09352239c-62.62794129-24.69437565-112.67395736-74.74039171-137.36833299-137.36833301l-20.09352239-50.89107074-20.0935224 50.89107074c-24.69437565 62.62794129-74.74039171 112.67395736-137.368333 137.36833301l-50.89107073 20.09352239zM771.33789183 957.62550131c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404758l-26.6661699-67.6043744c-8.63833672-21.87752672-26.10280012-39.34199011-47.98032684-47.98032684l-67.60437441-26.6661699c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.82575409s7.79328205-24.13100586 19.62404757-28.82575409l67.60437441-26.6661699c21.87752672-8.63833672 39.34199011-26.10280012 47.98032684-47.98032685l26.6661699-67.6043744c4.69474822-11.83076552 16.05603892-19.62404757 28.82575409-19.62404757s24.13100586 7.79328205 28.82575409 19.62404757l26.66616991 67.6043744c8.63833672 21.87752672 26.10280012 39.34199011 47.98032684 47.98032685l67.6043744 26.6661699c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575409s-7.79328205 24.13100586-19.62404757 28.82575409l-67.6043744 26.6661699c-21.87752672 8.63833672-39.34199011 26.10280012-47.98032684 47.98032684l-26.66616991 67.6043744c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575409 19.62404758z m-75.58544639-190.70067281c33.61439727 14.83540438 60.75004201 41.87715415 75.49155143 75.49155143 14.83540438-33.61439727 41.87715415-60.75004201 75.49155142-75.49155143-33.61439727-14.83540438-60.75004201-41.87715415-75.49155142-75.49155143-14.74150942 33.61439727-41.87715415 60.75004201-75.49155143 75.49155143z"/>
                    </svg>
                </div>
                <UserAvatar v-else :userid="source.userid" :size="30" click-open-detail/>
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
                @on-view-reply="onViewReply"
                @on-view-text="onViewText"
                @on-view-file="onViewFile"
                @on-down-file="onDownFile"
                @on-reply-list="onReplyList"
                @on-error="onError"
                @on-emoji="onEmoji"
                @on-other="onOther"
                @on-show-emoji-user="onShowEmojiUser"
                @on-merge-forward-detail="onMergeForwardDetail"/>
        </template>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogView from "./DialogView";

export default {
    name: "DialogItem",
    components: {DialogView},
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
        multiSelectMode: {
            type: Boolean,
            default: false
        },
        selectedMsgIdsSet: {
            type: Set,
            default: () => new Set()
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
            return this.unreadOne === this.source.id && this.source.id > 0
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

        isSelected() {
            return this.multiSelectMode && this.selectedMsgIdsSet.has(this.source.id);
        },

        isSelectableMsg() {
            return !['tag', 'top', 'todo', 'notice', 'word-chain', 'vote', 'template'].includes(this.source.type);
        },

        classArray() {
            return {
                'dialog-item': true,
                'reply-item': this.isReply,
                'unread-start': this.isUnreadStart,
                'self': this.isRightMsg,
                'multi-select-mode': this.multiSelectMode,
                'multi-selected': this.isSelected,
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

        formatTodoDoneUser(data) {
            if ($A.isJson(data) && $A.isArray(data.done_userids)) {
                return data.done_userids
            }
            return []
        },

        todoDoneDisplayList(data) {
            const userIds = this.formatTodoDoneUser(data)
            if (userIds.length === 0) {
                return []
            }
            const list = userIds.slice(0, 3).map(userid => ({
                type: 'user',
                value: userid,
            }))
            if (userIds.length > 3) {
                list.push({
                    type: 'extra',
                    value: `+${userIds.length - 3}`,
                })
            }
            return list
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

        handleOperation({currentTarget}) {
            this.$store.commit("longpress/set", {
                type: 'mention',
                data: this.source,
                element: currentTarget
            })
        },

        onMultiSelectToggle() {
            this.dispatch("on-multi-select-toggle", this.source.id)
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

        onMergeForwardDetail(data) {
            this.dispatch("on-merge-forward-detail", data)
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
