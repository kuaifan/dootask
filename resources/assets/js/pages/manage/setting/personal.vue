<template>
    <div class="setting-item submit">
        <Form ref="formDatum" :model="formDatum" :rules="ruleDatum" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('头像')" prop="userimg">
                <ImgUpload v-model="formDatum.userimg" :num="1"></ImgUpload>
                <span class="form-tip">{{$L('建议尺寸：%', '200x200')}}</span>
            </FormItem>
            <FormItem :label="$L('邮箱')">
                <Input v-model="userInfo.email" disabled></Input>
            </FormItem>
            <FormItem :label="$L('昵称')" prop="nickname">
                <Input v-model="formDatum.nickname" :maxlength="20"></Input>
            </FormItem>
            <FormItem :label="$L('职位/职称')" prop="profession">
                <Input v-model="formDatum.profession" :maxlength="20"></Input>
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

            formDatum: {
                userimg: '',
                nickname: '',
                profession: ''
            },

            ruleDatum: { },
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
            this.ruleDatum = {
                nickname: [
                    {required: true, message: this.$L('请输入昵称！'), trigger: 'change'},
                    {type: 'string', min: 2, message: this.$L('昵称长度至少2位！'), trigger: 'change'}
                ]
            };
        },

        initData() {
            this.$set(this.formDatum, 'userimg', this.userInfo.userimg);
            this.$set(this.formDatum, 'nickname', this.userInfo.nickname);
            this.$set(this.formDatum, 'profession', this.userInfo.profession);
            this.formDatum_bak = $A.cloneJSON(this.formDatum);
        },

        submitForm() {
            this.$refs.formDatum.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/editdata',
                        data: this.formDatum,
                    }).then((data, msg) => {
                        this.loadIng--;
                        $A.messageSuccess('修改成功');
                        this.$store.commit('getUserInfo');
                    }).catch((data, msg) => {
                        this.loadIng--;
                        $A.modalError(msg);
                    });
                }
            })
        },

        resetForm() {
            this.formDatum = $A.cloneJSON(this.formDatum_bak);
        }
    }
}
</script>
