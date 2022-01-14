<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('头像')" prop="userimg">
                <ImgUpload v-model="formData.userimg" :num="1"></ImgUpload>
                <span class="form-tip">{{$L('建议尺寸：200x200')}}</span>
            </FormItem>
            <FormItem :label="$L('邮箱')">
                <Input v-model="userInfo.email" disabled></Input>
            </FormItem>
            <FormItem :label="$L('昵称')" prop="nickname">
                <Input v-model="formData.nickname" :maxlength="20"></Input>
            </FormItem>
            <FormItem :label="$L('职位/职称')" prop="profession">
                <Input v-model="formData.profession" :maxlength="20"></Input>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
import ImgUpload from "../../../components/ImgUpload";
import {mapState} from "vuex";
export default {
    components: {ImgUpload},
    data() {
        return {
            loadIng: 0,

            formData: {
                userimg: '',
                nickname: '',
                profession: ''
            },

            ruleData: { },
        }
    },
    mounted() {
        this.initData();
    },
    computed: {
        ...mapState(['userInfo']),
    },
    watch: {
        userInfo() {
            this.initData();
        }
    },
    methods: {
        initLanguage() {
            this.ruleData = {
                nickname: [
                    {required: true, message: this.$L('请输入昵称！'), trigger: 'change'},
                    {type: 'string', min: 2, message: this.$L('昵称长度至少2位！'), trigger: 'change'}
                ]
            };
        },

        initData() {
            if (!$A.strExists(this.userInfo.userimg, '/avatar/default_')) {
                this.$set(this.formData, 'userimg', this.userInfo.userimg);
            }
            this.$set(this.formData, 'nickname', this.userInfo.nickname);
            this.$set(this.formData, 'profession', this.userInfo.profession);
            this.formData_bak = $A.cloneJSON(this.formData);
        },

        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    let data = $A.cloneJSON(this.formData);
                    if ($A.count(data.userimg) == 0) data.userimg = "";
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/editdata',
                        data,
                    }).then(() => {
                        $A.messageSuccess('修改成功');
                        this.loadIng--;
                        this.$store.dispatch('getUserInfo').catch(() => {});
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                        this.loadIng--;
                    });
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        }
    }
}
</script>
