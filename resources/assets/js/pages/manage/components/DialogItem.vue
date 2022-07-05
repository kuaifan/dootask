<template>
    <div :class="classArray">
        <div v-if="source.type === 'tag'" class="dialog-tag" @click="onViewTag">
            <div class="tag-user"><UserAvatar :userid="source.userid" :tooltipDisabled="source.userid == userId" :show-name="true" :show-icon="false"/></div>
            {{$L(source.msg.action === 'remove' ? '取消标注' : '标注了')}}
            "{{$A.getMsgSimpleDesc(source.msg.data)}}"
        </div>
        <div v-else-if="source.type === 'notice'" class="dialog-notice">
            {{source.msg.notice}}
        </div>
        <template v-else>
            <div class="dialog-avatar">
                <UserAvatar :userid="source.userid" :tooltipDisabled="source.userid == userId" :size="30"/>
            </div>
            <DialogView
                :msg-data="source"
                :dialog-type="dialogData.type"
                :hide-percentage="hidePercentage"
                :hide-reply="hideReply"
                :operate-visible="operateVisible"
                :operate-action="operateVisible && source.id === operateItem.id"
                @on-longpress="onLongpress"
                @on-view-reply="onViewReply"
                @on-view-text="onViewText"
                @on-view-file="onViewFile"
                @on-down-file="onDownFile"
                @on-reply-list="onReplyList"
                @on-emoji="onEmoji"/>
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
        hidePercentage: {
            type: Boolean,
            default: false
        },
        hideReply: {
            type: Boolean,
            default: false
        },
        isReply: {
            type: Boolean,  // 是否回复对象
            default: false
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
    },

    data() {
        return {
            subscribe: null,
        }
    },

    computed: {
        ...mapState(['userId']),

        classArray() {
            return {
                'dialog-item': true,
                'self': !this.isReply && this.source.userid == this.userId,
            }
        }
    },

    watch: {
        source: {
            handler() {
                this.msgRead();
            },
            immediate: true,
        },
        windowActive(active) {
            if (active) {
                this.msgRead();
            }
        }
    },

    methods: {
        msgRead() {
            if (!this.windowActive) {
                return;
            }
            this.$store.dispatch("dialogMsgRead", this.source);
        },

        onViewTag() {
            this.onViewReply({
                msg_id: this.source.id,
                reply_id: this.source.msg.data.id
            })
        },

        onLongpress(e) {
            this.dispatch("on-longpress", e)
        },

        onViewReply(data) {
            this.dispatch("on-view-reply", data)
        },

        onViewText(el) {
            this.dispatch("on-view-text", el)
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

        onEmoji(data) {
            this.dispatch("on-emoji", data)
        },

        dispatch(event, arg) {
            if (this.isReply) {
                this.$emit(event, arg)
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
                parent.$emit(event, arg)
            }
        }
    }
}
</script>
