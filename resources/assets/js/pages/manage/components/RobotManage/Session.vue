<template>
    <Modal
        v-model="sessionShow"
        :title="$L('会话列表')"
        :mask-closable="false"
        :scrollable="true"
        >
        <div class="robot-session-search">
            <Input v-model="sessionSearch" :placeholder="$L('请输入会话名称')" clearable style="width: 320px;" />
            <Button type="primary" icon="ios-search" @click="onSearch" class="robot-session-search-button">{{$L('搜索')}}</Button>
        </div>
        <div v-if="sessionData.length > 0">
            <div v-for="(item, key) in sessionData" :key="key" class="robot-session-list">
                <div class="robot-session-item robot-session-item-id">
                    <div class="robot-session-item-label">{{$L('会话ID')}}：</div>
                    <div class="robot-session-item-value">{{item.id}}</div>
                </div>
                <div class="robot-session-item robot-session-item-name">
                    <div class="robot-session-item-label">{{$L('会话名称')}}：</div>
                    <div class="robot-session-item-value">{{item.name}}</div>
                </div>
            </div>
        </div>
        <div v-else class="nothing">
            {{$L('没有任何会话')}}
        </div>
        <div slot="footer" class="adaption">
            <Button type="primary" @click="handleClose">{{$L('关闭')}}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'RobotManageSession',

    props: {
        id: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            sessionShow: false,
            sessionLoading: 0,
            sessionData: {},
            sessionSearch: '',
        }
    },

    watch: {
        sessionShow(val) {
            if (!val) {
                this.handleClose();
            } else {
                this.initInfo();
            }
        }
    },

    methods: {
        // 初始化信息
        initInfo() {
            if (this.$props.id <= 0) return;
            this.sessionLoading++;
            this.$store.dispatch("call", {
                url: 'users/bot/session',
                data: {
                    id: this.$props.id,
                    name: this.sessionSearch,
                },
            }).then(({data}) => {
                this.sessionData = data;
            }).finally(_ => {
                this.sessionLoading--;
            })
        },

        // 关闭
        handleClose() {
            this.$emit('update:sessionShow', false);
            this.sessionLoading = 0;
            this.sessionData = {};
        },

        // 搜索
        onSearch() {
            this.initInfo();
        }
    }
}
</script>