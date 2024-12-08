<template>
    <Modal
        v-model="deleteShow"
        :title="$L('删除机器人')"
        :mask-closable="false"
        >
        <div>{{ deleteText }}</div>
        <div slot="footer" class="adaption">
            <Button type="default" @click="handleClose">{{ $L('取消') }}</Button>
            <Button type="primary" :loading="deleteLoading > 0" @click="handleDeleteOk">{{ $L('确定') }}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'RobotManageDelete',

    props: {
        id: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            deleteText: '',
            deleteShow: false,
            deleteLoading: 0,
            deleteData: {},
        }
    },

    watch: {
        deleteShow(val) {
            if (!val) {
                this.handleClose();
            } else {
                console.log(this.$props.id)
                this.deleteText = this.$L('你确定要删除机器人【ID：' + this.$props.id + '】吗？');
            }
        }
    },

    methods: {
        // 关闭
        handleClose() {
            this.$emit('update:deleteShow', false);
            this.deleteLoading = 0;
        },

        // 确定删除
        handleDeleteOk() {
            if (this.$props.id <= 0) return;
            this.deleteLoading++;
            this.$store.dispatch("call", {
                url: 'users/bot/delete',
                data: {
                    id: this.$props.id,
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.$emit('success');
                this.deleteShow = false;
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.deleteLoading--;
            });
        }
    }
}
</script>

<style lang="scss">
.robot-info-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-size: 14px;
}
</style>