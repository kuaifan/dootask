<template>
    <div :class="classArray">
        <div class="dialog-avatar">
            <UserAvatar :userid="source.userid" :tooltipDisabled="source.userid == userId" :size="30"/>
        </div>
        <DialogView
            ref="view"
            :msg-data="source"
            :dialog-type="dialogData.type"
            :hide-percentage="isMyDialog"
            :operate-visible="operateVisible"
            :operate-action="operateVisible && source.id === operateItem.id"
            @on-longpress="onLongpress"/>
    </div>
</template>

<script>

import {mapState} from "vuex";
import DialogView from "./DialogView";
import {Store} from "le5le-store";

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
        isMyDialog: {
            type: Boolean,
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

    mounted() {
        this.subscribe = Store.subscribe('dialogOperate', this.onOperate);
    },

    beforeDestroy() {
        this.subscribe.unsubscribe();
    },

    computed: {
        ...mapState(['userId']),

        classArray() {
            return {
                'dialog-item': true,
                'self': this.source.userid == this.userId,
            }
        }
    },

    methods: {
        onLongpress(e) {
            Store.set('dialogLongpress', e);
        },

        onOperate({id, action, value}) {
            if (id === this.source.id) {
                switch (action) {
                    case "withdraw":
                        this.$refs.view.withdraw()
                        break;

                    case "view":
                        this.$refs.view.viewFile()
                        break;

                    case "down":
                        this.$refs.view.downFile()
                        break;

                    case "emoji":
                        this.$refs.view.setEmoji(value)
                        break;
                }
            }
        },
    }
}
</script>
