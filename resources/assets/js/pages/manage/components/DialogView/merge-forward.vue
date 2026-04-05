<template>
    <div class="content-merge-forward" @click="openDetail">
        <div class="merge-title">{{ mergeTitle }}</div>
        <div class="merge-list">
            <div v-for="(item, index) in displayList" :key="index" class="merge-item">
                <UserAvatar :userid="item.userid" :show-icon="false" :show-name="true" :size="14"/>
                <span class="item-colon">:</span>
                <span class="item-desc" v-html="$A.getMsgSimpleDesc(item)"></span>
            </div>
        </div>
        <div class="merge-footer">{{ $L('共(*)条消息', msg.count || 0) }}</div>
    </div>
</template>

<script>
export default {
    name: "MergeForwardMsg",
    props: {
        msg: {
            type: Object,
            default: () => ({})
        }
    },
    computed: {
        displayList() {
            return this.msg?.preview || [];
        },
        mergeTitle() {
            return $A.getMergeForwardTitle(this.msg);
        }
    },
    methods: {
        openDetail() {
            this.$emit("on-view-detail", this.msg);
        }
    }
}
</script>
