<template>
    <div class="common-network-exception">
        <template v-if="type==='alert'">
            <Alert v-if="show" type="error" show-icon closable>{{$L('网络连接失败，请检查网络设置。')}}</Alert>
        </template>
        <template v-else-if="type==='modal'">
            <Modal
                v-model="show"
                :width="416"
                :closable="false"
                :footer-hide="true"
                class-name="common-network-exception-modal">
                <div class="ivu-modal-confirm">
                    <div class="ivu-modal-confirm-head">
                        <div class="ivu-modal-confirm-head-icon ivu-modal-confirm-head-icon-error"><Icon type="ios-close-circle"/></div><div class="ivu-modal-confirm-head-title">{{$L('温馨提示')}}</div>
                    </div>
                    <div class="ivu-modal-confirm-body">
                        <div>{{$L('网络连接失败，请检查网络设置。')}}</div>
                    </div>
                    <div class="ivu-modal-confirm-footer">
                        <Button type="primary" @click="show = false">{{$L('确定')}}</Button>
                    </div>
                </div>
            </Modal>
        </template>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: 'NetworkException',
    props: {
        type: {
            type: String,
            default: 'modal'
        },
    },
    data() {
        return {
            show: false,
        }
    },

    beforeDestroy() {
        this.show = false
    },

    computed: {
        ...mapState(['ajaxNetworkException']),
    },

    watch: {
        ajaxNetworkException: {
            handler(v) {
                this.show = v;
                if (v) {
                    this.checkNetwork();
                }
            },
            immediate: true
        }
    },

    methods: {
        isNotServer() {
            let apiHome = $A.getDomain(window.systemInfo.apiUrl)
            return this.$isSoftware && (apiHome == "" || apiHome == "public")
        },

        checkNetwork() {
            this.__timer && clearTimeout(this.__timer);
            this.__timer = setTimeout(() => {
                if (!this.ajaxNetworkException) {
                    return; // 已经恢复
                }
                if (this.isNotServer()) {
                    return; // 没有配置服务器地址
                }
                this.$store.dispatch("call", {
                    url: "system/setting",
                }).finally(() => {
                    this.checkNetwork();
                });
            }, 3000);
        }
    }
}
</script>
