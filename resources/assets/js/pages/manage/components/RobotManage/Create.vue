<template>
    <Modal
        v-model="createShow"
        :title="$L('创建机器人')"
        :mask-closable="false">
        <Form ref="addProject" :model="createData" @submit.native.prevent>
            <FormItem prop="name" :label="$L('机器人名称')">
                <Input type="text" v-model="createData.name" :placeholder="$L('请输入机器人名称')"></Input>
            </FormItem>
        </Form>
        <div slot="footer" class="adaption">
            <Button type="default" @click="createShow=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="createLoading > 0" @click="onSaveCreate">{{$L('创建')}}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'RobotManageCreate',
    data() {
        return {
            createShow: false,
            createLoading: 0,
            createData: {
                name: '',
            },
        }
    },

    watch: {
        createShow(val) {
            if (!val) {
                this.$emit('update:createShow', false);
            } else {
                this.createLoading--;
                this.createData = {
                    name: '',
                };
            }
        }
    },

    methods: {
        // 创建机器人
        onSaveCreate() {
            this.createLoading++;
            this.$store.dispatch("call", {
                url: 'users/bot/add',
                data: this.createData,
            }).then(({msg, data}) => {
                this.$emit('success', data);
                this.createShow = false;
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.createLoading--;
            });
        },
    }
}
</script>