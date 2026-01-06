<template>
    <Modal
        v-model="visible"
        :title="$L('编辑用户信息')"
        :mask-closable="false"
        width="560">
        <Form :model="formData" v-bind="formOptions" @submit.native.prevent>
            <Alert type="warning" style="margin-bottom:18px">
                {{ $L(`正在编辑帐号【ID:${userData.userid}, ${userData.nickname}】的信息。`) }}
            </Alert>

            <FormItem :label="$L('昵称')">
                <Input
                    v-model="formData.nickname"
                    :maxlength="20"
                    :placeholder="$L('请输入昵称')"/>
            </FormItem>
            <FormItem :label="$L('电话')">
                <Input
                    v-model="formData.tel"
                    :placeholder="$L('请输入电话号码')"/>
            </FormItem>
            <FormItem :label="$L('职位')">
                <Input
                    v-model="formData.profession"
                    :maxlength="20"
                    :placeholder="$L('请输入职位/职称')"/>
            </FormItem>
            <FormItem :label="$L('邮箱')">
                <Input
                    v-model="formData.email"
                    :placeholder="$L('请输入邮箱地址')"
                    :disabled="isLdapUser"/>
                <div v-if="isLdapUser" class="form-tip">
                    {{ $L('LDAP 用户禁止修改邮箱') }}
                </div>
            </FormItem>

            <FormItem :label="$L('新密码')">
                <Input
                    v-model="formData.password"
                    type="password"
                    password
                    :placeholder="$L('留空则不修改密码')"/>
            </FormItem>

            <FormItem :label="$L('所属部门')">
                <Select
                    v-model="formData.department"
                    multiple
                    :multiple-max="10"
                    :multiple-max-before="onMultipleMaxBefore"
                    :placeholder="$L('留空为默认部门')">
                    <Option
                        v-for="(item, index) in departmentList"
                        :value="item.id"
                        :key="index"
                        :label="item.chains.join(' - ')">
                        <div :class="`department-level-name level-${item.level - 1}`">{{ item.name }}</div>
                    </Option>
                </Select>
            </FormItem>

            <FormItem :label="$L('个人简介')">
                <Input
                    v-model="formData.introduction"
                    type="textarea"
                    :rows="2"
                    :autosize="{ minRows: 2, maxRows: 6 }"
                    :maxlength="500"
                    :placeholder="$L('请输入个人简介')"/>
            </FormItem>
            <FormItem :label="$L('个性标签')">
                <div class="user-tags-preview">
                    <template v-if="personalTags.length">
                        <div
                            v-for="tag in personalTags"
                            :key="tag.id"
                            class="tag-pill"
                            :class="{'is-recognized': tag.recognized}"
                            @click="openTagModal">
                            {{ tag.name }}
                            <span v-if="tag.recognition_total > 0">{{ tag.recognition_total }}</span>
                        </div>
                    </template>
                    <span v-else class="tags-empty">{{ $L('暂无个性标签') }}</span>
                    <span v-if="personalTagTotal > personalTags.length" class="tags-total">{{ $L('共(*)个', personalTagTotal) }}</span>
                    <Button type="text" size="small" class="manage-button" @click.stop="openTagModal">
                        <Icon type="md-create"/>
                        {{ $L('管理') }}
                    </Button>
                </div>
            </FormItem>

            <template v-if="checkinMode">
                <FormItem :label="$L('人脸图片')" class="checkin-field">
                    <ImgUpload v-model="formData.faceimg" :num="1" :width="512" :height="512" whcut="cover"/>
                    <div class="form-tip">{{ $L('建议尺寸：500x500') }}</div>
                </FormItem>

                <FormItem :label="$L('MAC地址')" class="checkin-field">
                    <Row class="checkin-mac-header">
                        <Col span="11">{{ $L('设备MAC地址') }}</Col>
                        <Col span="11">{{ $L('备注') }}</Col>
                        <Col span="2"></Col>
                    </Row>
                    <Row
                        v-for="(item, key) in formData.checkin_macs"
                        :key="key"
                        class="checkin-mac-item">
                        <Col span="11">
                            <Input
                                v-model="item.mac"
                                :maxlength="20"
                                :placeholder="$L('请输入设备MAC地址')"/>
                        </Col>
                        <Col span="11">
                            <Input v-model="item.remark" :maxlength="100" :placeholder="$L('备注')"/>
                        </Col>
                        <Col span="2" class="checkin-mac-del">
                            <Icon type="md-close" @click="delCheckinMac(key)"/>
                        </Col>
                    </Row>
                    <Button type="default" icon="md-add" @click="addCheckinMac">
                        {{ $L('添加设备') }}
                    </Button>
                </FormItem>
            </template>
        </Form>

        <UserTagsModal
            v-if="userData.userid"
            v-model="tagModalVisible"
            :userid="userData.userid"
            @updated="onTagsUpdated"/>

        <div slot="footer" class="adaption">
            <Button type="default" @click="visible = false">{{ $L('取消') }}</Button>
            <Button type="primary" :loading="loading" @click="handleSave">{{ $L('保存') }}</Button>
        </div>
    </Modal>
</template>

<script>
import ImgUpload from "../../../components/ImgUpload.vue";
import UserTagsModal from "./UserTagsModal.vue";
import {mapState} from "vuex";

export default {
    name: "UserEditModal",
    components: {ImgUpload, UserTagsModal},
    props: {
        value: {
            type: Boolean,
            default: false
        },
        userData: {
            type: Object,
            default: () => ({})
        },
        checkinMode: {
            type: Boolean,
            default: false
        },
        departmentList: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            visible: this.value,
            loading: false,
            formData: {
                nickname: '',
                tel: '',
                profession: '',
                email: '',
                password: '',
                department: [],
                introduction: '',
                faceimg: [],
                checkin_macs: []
            },
            extraInfo: {},
            tagModalVisible: false,
            personalTags: [],
            personalTagTotal: 0
        };
    },
    computed: {
        ...mapState(['formOptions']),

        isLdapUser() {
            return this.userData.identity && this.userData.identity.includes('ldap');
        }
    },
    watch: {
        value(v) {
            this.visible = v;
            if (v) {
                this.initFormData();
            }
        },
        visible(v) {
            this.$emit('input', v);
        },
        'formData.department': {
            handler(value, oldValue = []) {
                if (!Array.isArray(value) || value.length === 0 || this.departmentList.length === 0) {
                    return;
                }
                const previous = Array.isArray(oldValue) ? new Set(oldValue) : new Set();
                const selected = new Set(value);
                const hasNewSelection = Array.from(selected).some(id => !previous.has(id));
                if (!hasNewSelection) {
                    return;
                }
                const departmentMap = this.departmentList.reduce((acc, item) => {
                    acc[item.id] = item;
                    return acc;
                }, {});
                const needAdd = new Set();
                value.forEach((id) => {
                    let cursor = departmentMap[id];
                    while (cursor && cursor.parent_id && cursor.parent_id > 0) {
                        if (!selected.has(cursor.parent_id)) {
                            needAdd.add(cursor.parent_id);
                        }
                        cursor = departmentMap[cursor.parent_id];
                    }
                });
                if (needAdd.size > 0) {
                    const merged = Array.from(new Set([...value, ...needAdd])).sort((a, b) => a - b);
                    if (merged.length !== value.length || merged.some((id, index) => id !== value[index])) {
                        this.$set(this.formData, 'department', merged);
                    }
                }
            },
            deep: true
        }
    },
    methods: {
        initFormData() {
            const {nickname_original, tel, profession, email, department, checkin_face, checkin_macs} = this.userData;
            this.formData = {
                nickname: nickname_original || '',
                tel: tel || '',
                profession: profession || '',
                email: email || '',
                password: '',
                department: Array.isArray(department)
                    ? department.map(id => parseInt(id))
                    : [],
                introduction: '',
                faceimg: checkin_face ? [{url: checkin_face}] : [],
                checkin_macs: Array.isArray(checkin_macs) && checkin_macs.length > 0
                    ? $A.cloneJSON(checkin_macs)
                    : [{mac: '', remark: ''}]
            };
            this.extraInfo = {};
            this.personalTags = [];
            this.personalTagTotal = 0;
            this.loadUserExtra();
        },

        loadUserExtra() {
            const userid = this.userData?.userid;
            if (!userid) {
                return;
            }
            this.$store.dispatch("getUserExtra", userid)
                .then((data) => {
                    if ($A.isJson(data)) {
                        this.extraInfo = data;
                        this.formData.introduction = data.introduction || '';
                        this.syncPersonalTags();
                    }
                })
                .catch(() => {});
        },

        syncPersonalTags() {
            const extra = this.extraInfo || {};
            const tags = Array.isArray(extra.personal_tags) ? extra.personal_tags : [];
            this.personalTags = tags.slice(0, 10);
            this.personalTagTotal = typeof extra.personal_tags_total === 'number'
                ? extra.personal_tags_total
                : this.personalTags.length;
        },

        openTagModal() {
            if (!this.userData.userid) {
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
        },

        onMultipleMaxBefore(num) {
            $A.messageError(`最多选择${num}个部门`);
            return false;
        },

        addCheckinMac() {
            this.formData.checkin_macs.push({mac: '', remark: ''});
        },

        delCheckinMac(index) {
            this.formData.checkin_macs.splice(index, 1);
            if (this.formData.checkin_macs.length === 0) {
                this.addCheckinMac();
            }
        },

        async handleSave() {
            this.loading = true;
            try {
                await this.saveBasicInfo();
                await this.saveExtraInfo();
                if (this.checkinMode) {
                    await this.saveCheckinInfo();
                }
                $A.messageSuccess(this.$L('保存成功'));
                this.visible = false;
                this.$emit('updated');
            } catch (error) {
                $A.modalError(error.msg || this.$L('保存失败'));
            } finally {
                this.loading = false;
            }
        },

        saveExtraInfo() {
            const userid = this.userData?.userid;
            if (!userid) {
                return Promise.resolve();
            }
            const oldIntroduction = this.extraInfo?.introduction || '';
            if (this.formData.introduction === oldIntroduction) {
                return Promise.resolve();
            }
            return this.$store.dispatch('saveUserExtra', {
                userid,
                data: {
                    introduction: this.formData.introduction || ''
                }
            });
        },

        saveBasicInfo() {
            return new Promise((resolve, reject) => {
                const data = {
                    userid: this.userData.userid,
                    department: this.formData.department,
                    type: 'department'
                };
                if (this.formData.nickname !== (this.userData.nickname_original || '')) {
                    data.nickname = this.formData.nickname;
                }
                if (this.formData.tel !== (this.userData.tel || '')) {
                    data.tel = this.formData.tel;
                }
                if (this.formData.profession !== (this.userData.profession || '')) {
                    data.profession = this.formData.profession;
                }
                if (this.formData.email !== this.userData.email) {
                    data.email = this.formData.email;
                }
                if (this.formData.password) {
                    data.password = this.formData.password;
                }
                this.$store.dispatch("call", {
                    url: 'users/operation',
                    data
                }).then(resolve).catch(reject);
            });
        },

        saveCheckinInfo() {
            const promises = [];
            const newFaceUrl = $A.arrayLength(this.formData.faceimg) > 0
                ? this.formData.faceimg[0].url
                : '';
            const oldFaceUrl = this.userData.checkin_face || '';
            if (newFaceUrl !== oldFaceUrl) {
                promises.push(
                    this.$store.dispatch("call", {
                        url: 'users/operation',
                        data: {
                            userid: this.userData.userid,
                            type: 'checkin_face',
                            checkin_face: newFaceUrl
                        }
                    })
                );
            }
            const validMacs = this.formData.checkin_macs.filter(item => item.mac && item.mac.trim());
            promises.push(
                this.$store.dispatch("call", {
                    url: 'users/operation',
                    data: {
                        userid: this.userData.userid,
                        type: 'checkin_macs',
                        checkin_macs: validMacs
                    }
                })
            );
            return Promise.all(promises);
        }
    }
};
</script>

<style lang="scss">
.checkin-field {
    .ivu-form-item-label {
        color: #f90;
        font-weight: 500;
    }
}
</style>
<style lang="scss" scoped>
.checkin-mac-header {
    margin-bottom: 8px;
    font-weight: 500;
    color: #606266;
}

.checkin-mac-item {
    margin-bottom: 8px;

    .ivu-col {
        padding-right: 8px;

        &:last-child {
            padding-right: 0;
        }
    }
}

.checkin-mac-del {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #f00;

    &:hover {
        opacity: 0.8;
    }
}

.form-tip {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

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
