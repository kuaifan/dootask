<template>
    <Modal
        v-model="infoShow"
        :title="$L('机器人信息')"
        :mask-closable="false"
        >
        <div>
            <div class="robot-info-item">
                <div class="robot-info-item-label">{{$L('机器人ID')}}：</div>
                <div class="robot-info-item-value">{{infoData.id}}</div>
            </div>
            <div class="robot-info-item">
                <div class="robot-info-item-label">{{$L('机器人名称')}}：</div>
                <div class="robot-info-item-value">{{infoData.name}}</div>
            </div>
            <div class="robot-info-item">
                <div class="robot-info-item-label">{{$L('清理时间')}}：</div>
                <div class="robot-info-item-value">{{infoData.clear_day + '天'}}</div>
            </div>
            <div class="robot-info-item">
                <div class="robot-info-item-label">{{$L('Token')}}：</div>
                <div class="robot-info-item-value">{{tokenFormat(infoData.token)}}</div>
                <div v-if="infoData.token" class="robot-info-item-action" @click="tokenCopy">
                    <Icon type="ios-copy" size="16" />
                </div>
            </div>
            <div class="robot-info-item">
                <div class="robot-info-item-label">{{$L('Webhook地址')}}：</div>
                <div class="robot-info-item-value">{{ infoData.webhook_url || '-' }} </div>
                <div v-if="infoData.webhook_url" class="robot-info-item-action" @click="webhookCopy">
                    <Icon type="ios-copy" size="16" />
                </div>
            </div>
        </div>
        <div slot="footer" class="adaption"></div>
    </Modal>
</template>

<script>
export default {
    name: 'RobotManageInfo',

    props: {
        id: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            infoShow: false,
            infoLoading: 0,
            infoData: {},
        }
    },

    watch: {
        infoShow(val) {
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
            this.infoLoading++;
            this.$store.dispatch("call", {
                url: 'users/bot/info',
                data: {
                    id: this.$props.id,
                },
            }).then(({data}) => {
                this.infoData = data;
            }).finally(_ => {
                this.infoLoading--;
            })
        },

        // 关闭
        handleClose() {
            this.$emit('update:infoShow', false);
            this.infoLoading = 0;
            this.infoData = {};
        },

        // Token只显示前10和后10位，中间用*代替
        tokenFormat(token) {
            if (!token) return '-';
            return token.slice(0, 10) + '****' + token.slice(-10);
        },

        // Token复制
        tokenCopy() {
            if (!this.infoData.token) {
                return;
            }
            this.copyText(this.infoData.token);
        },

        // Webhook复制
        webhookCopy() {
            if (!this.infoData.webhook_url) {
                return;
            }
            this.copyText(this.infoData.webhook_url);
        }
    }
}
</script>

<style lang="scss">
.robot-info-item {
    display: flex;
    margin-bottom: 10px;
    font-size: 14px;
    .robot-info-item-value {
        word-break: break-all;
    }
    .robot-info-item-action {
        margin-left: 8px;
    }
}
</style>