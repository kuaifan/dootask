<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth" @submit.native.prevent>
            <FormItem :label="$L('头像')" prop="userimg">
                <ImgUpload v-model="formData.userimg" :num="1" :width="512" :height="512" :whcut="1"></ImgUpload>
                <span class="form-tip">{{$L('建议尺寸：200x200')}}</span>
            </FormItem>
            <FormItem :label="$L('邮箱')" prop="email">
                <Input v-model="userInfo.email" disabled></Input>
            </FormItem>
            <FormItem :label="$L('电话')" prop="tel">
                <Input v-model="formData.tel" :maxlength="20" :placeholder="$L('请输入联系电话')"></Input>
            </FormItem>
            <FormItem :label="$L('昵称')" prop="nickname">
                <Input v-model="formData.nickname" :maxlength="20" :placeholder="$L('请输入昵称')"></Input>
            </FormItem>
            <FormItem :label="$L('职位/职称')" prop="profession">
                <Input v-model="formData.profession" :maxlength="20" :placeholder="$L('请输入职位/职称')"></Input>
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
                email: '',
                tel: '',
                nickname: '',
                profession: ''
            },

            ruleData: {
                email: [
                    {required: true, message: this.$L('请输入邮箱地址！'), trigger: 'change'},
                ],
                tel: [
                    {required: true, message: this.$L('请输入联系电话！'), trigger: 'change'},
                    {type: 'string', min: 6, message: this.$L('电话长度至少6位！'), trigger: 'change'}
                ],
                nickname: [
                    {required: true, message: this.$L('请输入昵称！'), trigger: 'change'},
                    {type: 'string', min: 2, message: this.$L('昵称长度至少2位！'), trigger: 'change'}
                ]
            },
        }
    },
    mounted() {
        this.initData();
    },
    computed: {
        ...mapState(['userInfo', 'formLabelPosition', 'formLabelWidth']),
    },
    watch: {
        userInfo() {
            this.initData();
        }
    },
    methods: {
        initData() {
            this.$set(this.formData, 'userimg', $A.strExists(this.userInfo.userimg, '/avatar') ? '' : this.userInfo.userimg);
            this.$set(this.formData, 'email', this.userInfo.email);
            this.$set(this.formData, 'tel', this.userInfo.tel);
            this.$set(this.formData, 'nickname', typeof this.userInfo.nickname_original !== "undefined" ? this.userInfo.nickname_original : this.userInfo.nickname);
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
                        this.$store.dispatch('getUserInfo').catch(() => {});
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
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
