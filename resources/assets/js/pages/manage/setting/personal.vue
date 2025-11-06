<template>
    <div class="setting-item submit">
        <Form
            ref="formData"
            :model="formData"
            :rules="ruleData"
            v-bind="formOptions"
            @submit.native.prevent>
            <FormItem :label="$L('头像')" prop="userimg">
                <ImgUpload v-model="formData.userimg" :num="1" :width="512" :height="512" whcut="cover"/>
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
            <FormItem :label="$L('生日')" prop="birthday">
                <DatePicker
                    v-model="formData.birthday"
                    type="date"
                    format="yyyy-MM-dd"
                    value-format="yyyy-MM-dd"
                    :placeholder="$L('请选择生日')"
                    confirm
                    transfer/>
            </FormItem>
            <FormItem :label="$L('地址')" prop="address">
                <Input v-model="formData.address" :maxlength="100" :placeholder="$L('请输入地址')"></Input>
            </FormItem>
            <FormItem :label="$L('个人简介')" prop="introduction">
                <Input
                    v-model="formData.introduction"
                    type="textarea"
                    :rows="2"
                    :autosize="{ minRows: 2, maxRows: 8 }"
                    :maxlength="500"
                    :placeholder="$L('请输入个人简介')"></Input>
            </FormItem>
            <FormItem :label="$L('个性标签')">
                <div class="user-tags-preview">
                    <template v-if="displayTags.length">
                        <div
                            v-for="tag in displayTags"
                            :key="tag.id"
                            class="tag-pill" 
                            :class="{'is-recognized': tag.recognized}"
                            @click="openTagModal">
                            {{tag.name}}
                            <span v-if="tag.recognition_total > 0">{{tag.recognition_total}}</span>
                        </div>
                    </template>
                    <span v-else class="tags-empty">{{$L('暂无个性标签')}}</span>
                    <span v-if="personalTagTotal > displayTags.length" class="tags-total">{{$L('共(*)个', personalTagTotal)}}</span>
                    <Button type="text" size="small" class="manage-button" @click.stop="openTagModal">
                        <Icon type="md-create" />
                        {{$L('管理')}}
                    </Button>
                </div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
        <UserTagsModal
            v-if="userInfo.userid"
            v-model="tagModalVisible"
            :userid="userInfo.userid"
            @updated="onTagsUpdated"/>
    </div>
</template>

<script>
import ImgUpload from "../../../components/ImgUpload";
import UserTagsModal from "../components/UserTagsModal.vue";
import {mapState} from "vuex";
export default {
    components: {ImgUpload, UserTagsModal},
    data() {
        return {
            loadIng: 0,

            formData: {
                // 基本信息
                userimg: '',
                email: '',
                tel: '',
                nickname: '',
                profession: '',
                // 拓展信息 生日、地址、个人简介
                birthday: '',
                address: '',
                introduction: ''
            },

            extraInfo: {},

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

            tagModalVisible: false,
            personalTags: [],
            personalTagTotal: 0,
        }
    },
    mounted() {
        this.initData();
        this.loadUserExtra();
    },
    computed: {
        ...mapState(['userInfo', 'formOptions']),

        displayTags() {
            return this.personalTags;
        }
    },
    watch: {
        userInfo() {
            this.initData();
            this.loadUserExtra();
        }
    },
    methods: {
        initData() {
            const extra = this.extraInfo || {};
            this.$set(this.formData, 'userimg', $A.strExists(this.userInfo.userimg, '/avatar') ? '' : this.userInfo.userimg);
            this.$set(this.formData, 'email', this.userInfo.email);
            this.$set(this.formData, 'tel', this.userInfo.tel);
            this.$set(this.formData, 'nickname', typeof this.userInfo.nickname_original !== "undefined" ? this.userInfo.nickname_original : this.userInfo.nickname);
            this.$set(this.formData, 'profession', this.userInfo.profession);
            this.$set(this.formData, 'birthday', extra.birthday || '');
            this.$set(this.formData, 'address', extra.address || '');
            this.$set(this.formData, 'introduction', extra.introduction || '');
            this.formData_bak = $A.cloneJSON(this.formData);
            this.syncPersonalTags();
        },

        loadUserExtra(force = false) {
            const userid = this.userInfo?.userid;
            if (!userid) {
                this.applyExtraInfo({});
                return;
            }
            const payload = force ? {userid, force: true} : userid;
            this.$store.dispatch("getUserExtra", payload)
                .then((data) => {
                    if ($A.isJson(data)) {
                        this.applyExtraInfo(data);
                    }
                })
                .catch(() => {
                    if (!this.extraInfo || Object.keys(this.extraInfo).length === 0) {
                        this.applyExtraInfo({});
                    }
                });
        },

        applyExtraInfo(extra) {
            const info = $A.isJson(extra) ? extra : {};
            this.extraInfo = info;
            this.$set(this.formData, 'birthday', info.birthday || '');
            this.$set(this.formData, 'address', info.address || '');
            this.$set(this.formData, 'introduction', info.introduction || '');
            this.syncPersonalTags();
            this.formData_bak = $A.cloneJSON(this.formData);
        },

        syncPersonalTags() {
            const extra = this.extraInfo || {};
            const tags = Array.isArray(extra.personal_tags) ? extra.personal_tags : [];
            this.personalTags = tags.slice(0, 10);
            this.personalTagTotal = typeof extra.personal_tags_total === 'number'
                ? extra.personal_tags_total
                : this.personalTags.length;
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
                        const userid = this.userInfo?.userid;
                        const extraPayload = {
                            birthday: data.birthday || '',
                            address: data.address || '',
                            introduction: data.introduction || ''
                        };
                        if (userid) {
                            this.$store.dispatch('saveUserExtra', {
                                userid,
                                data: extraPayload
                            });
                        }
                        this.applyExtraInfo(Object.assign({}, this.extraInfo, extraPayload));
                        this.$store.dispatch('getUserInfo')
                            .catch(() => {})
                            .finally(() => {
                                this.loadUserExtra(true);
                            });
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
        },

        openTagModal() {
            if (!this.userInfo.userid) {
                return;
            }
            this.tagModalVisible = true;
        },

        onTagsUpdated({top, total}) {
            this.personalTags = Array.isArray(top) ? top : [];
            this.personalTagTotal = typeof total === 'number' ? total : this.personalTags.length;
            this.extraInfo = Object.assign({}, this.extraInfo, {
                personal_tags: this.personalTags,
                personal_tags_total: this.personalTagTotal
            });
        }
    }
}
</script>

<style lang="scss" scoped>
.user-tags-preview {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    min-height: 32px;

    .tag-pill {
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 13px;
        user-select: none;
        background-color: #f5f5f5;
        color: #606266;
        line-height: 14px;
        height: 26px;
        max-width: 160px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        &.is-recognized {
            color: #67c23a;
        }
        span {
            padding-left: 8px;
            position: relative;
            &:before {
                content: '';
                position: absolute;
                left: 2px;
                top: 50%;
                transform: translateY(-50%);
                width: 2px;
                height: 2px;
                border-radius: 50%;
                background-color: currentColor;
            }
        }
    }

    .tags-empty {
        color: #909399;
    }

    .tags-total {
        color: #909399;
        font-size: 12px;
    }

    .manage-button {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
}
</style>
