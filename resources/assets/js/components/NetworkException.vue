<template>
    <Modal
        v-model="show"
        :width="416"
        :closable="false"
        :footer-hide="true"
        class-name="common-network-exception">
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

<script>
import {mapState} from "vuex";

export default {
    name: 'NetworkException',
    data() {
        return {
            show: false,
        }
    },

    computed: {
        ...mapState([ 'ajaxNetworkException' ]),
    },

    watch: {
        ajaxNetworkException: {
            handler(v) {
                this.show = v;
            },
            immediate: true
        }
    }

};
</script>
