<template>
    <div :class="classArray">
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
            @on-reply-list="onReplyList"
            @on-emoji="onEmoji"/>
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

    methods: {
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
