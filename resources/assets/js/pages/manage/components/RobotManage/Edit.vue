<template>
    <Modal
        v-model="editShow"
        :title="$L('修改机器人')"
        :mask-closable="false">
        <Form ref="addProject" :model="editData" @submit.native.prevent>
            <FormItem prop="name" :label="$L('机器人名称')">
                <Input type="text" v-model="editData.name" :placeholder="$L('请输入机器人名称')"></Input>
            </FormItem>
            <FormItem prop="clear_day" :label="$L('清除天数')">
                <InputNumber v-model="editData.clear_day" :min="1" :max="999" :placeholder="$L('请输入清除天数')"></InputNumber>
            </FormItem>
            <FormItem prop="webhook_url" :label="$L('Webhook地址')">
                <Input type="textarea" v-model="editData.webhook_url" :autosize="{minRows: 2,maxRows: 5}" :placeholder="$L('请输入Webhook地址')"></Input>
            </FormItem>
        </Form>
        <div slot="footer" class="adaption">
            <Button type="default" @click="editShow=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="editLoading > 0" @click="onSaveEdit">{{$L('确定')}}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'RobotManageEdit',

    props: {
        item: {},
    },
    data() {
        return {
            editShow: false,
            editLoading: 0,
            editData: {
                id: 0,
                name: '',
                clear_day: 0,
                webhook_url: '',
            },
        }
    },

    watch: {
        editShow(val) {
            if (!val) {
                this.$emit('update:editShow', false);
            } else {
                this.editLoading = 0;
                this.editData = {
                    id: this.item.bot_id,
                    name: this.item.name,
                    clear_day: this.item.clear_day,
                    webhook_url: this.item.webhook_url,
                }
            }
        }
    },

    methods: {
        // 修改机器人
        onSaveEdit() {
            this.editLoading++;
            this.$store.dispatch("call", {
                url: 'users/bot/edit',
                data: this.editData,
            }).then(({msg, data}) => {
                this.$emit('success', data);
                this.editShow = false;
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.editLoading--;
            });
        },
    }
}
</script>