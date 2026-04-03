<template>
    <div class="content-merge-forward" @click="openDetail">
        <div class="merge-title">{{ msg.title }}</div>
        <div class="merge-list">
            <div v-for="(item, index) in displayList" :key="index" class="merge-item">
                <UserAvatar :userid="item.userid" :show-icon="false" :show-name="true" :size="14"/>
                <span class="item-colon">:</span>
                <span class="item-desc" v-html="$A.getMsgSimpleDesc(item)"></span>
            </div>
        </div>
        <div class="merge-footer">{{ $L('共') }} {{ msg.count || msg.list.length }} {{ $L('条消息') }}</div>

        <!-- 详情弹窗 -->
        <Modal
            v-model="detailShow"
            :title="msg.title"
            class-name="merge-forward-detail-modal"
            :mask-closable="true"
            width="500"
            @click.native.stop>
            <Scrollbar class-name="merge-detail-scroller" :style="{maxHeight: '60vh'}">
                <div class="merge-detail-list">
                    <div v-for="(item, index) in msg.list" :key="index" class="merge-detail-item">
                        <UserAvatar :userid="item.userid" :size="28" :show-name="false"/>
                        <div class="detail-content">
                            <div class="detail-header">
                                <UserAvatar :userid="item.userid" :show-icon="false" :show-name="true" :size="0"/>
                                <span class="detail-time">{{ item.created_at }}</span>
                            </div>
                            <div class="detail-body">
                                <template v-if="item.type === 'text'">
                                    <pre v-html="$A.formatTextMsg(item.msg.text)"></pre>
                                </template>
                                <template v-else-if="item.type === 'file'">
                                    <span>[{{ $L('文件') }}] {{ item.msg.name }}</span>
                                </template>
                                <template v-else-if="item.type === 'merge-forward'">
                                    <span>[{{ $L('聊天记录') }}] {{ item.msg.title }}</span>
                                </template>
                                <template v-else>
                                    <span v-html="$A.getMsgSimpleDesc(item)"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </Scrollbar>
        </Modal>
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
    data() {
        return {
            detailShow: false
        }
    },
    computed: {
        displayList() {
            if (!this.msg || !this.msg.list) return [];
            return this.msg.list.slice(0, 4);
        }
    },
    methods: {
        openDetail() {
            this.detailShow = true;
        }
    }
}
</script>
