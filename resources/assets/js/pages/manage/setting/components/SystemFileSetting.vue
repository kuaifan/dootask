<template>
    <div :class="{'setting-component-item': !embedded}">
        <div>
            <div class="block-setting-box">
                <h3>{{$L('权限设置')}}</h3>
                <div class="form-box">
                    <FormItem :label="$L('打包权限')" prop="permission_pack_type">
                        <RadioGroup v-model="formData.permission_pack_type">
                            <Radio label="all">{{ $L('允许所有人') }}</Radio>
                            <Radio label="admin">{{ $L('仅限管理员') }}</Radio>
                            <Radio label="appointAllow">{{ $L('指定允许') }}</Radio>
                            <Radio label="appointProhibit">{{ $L('指定禁止') }}</Radio>
                        </RadioGroup>
                        <div v-if="formData.permission_pack_type === 'all'" class="form-tip">{{$L('允许系统所有人员使用文件打包下载功能')}}</div>
                        <div v-else-if="formData.permission_pack_type === 'admin'" class="form-tip">{{$L('仅限管理员使用文件打包下载功能')}}</div>
                        <div v-else-if="formData.permission_pack_type === 'appointAllow'" class="form-tip">{{$L('指定允许的人员使用文件打包下载功能')}}</div>
                        <div v-else-if="formData.permission_pack_type === 'appointProhibit'" class="form-tip">{{$L('指定禁止的人员使用文件打包下载功能')}}</div>
                    </FormItem>
                    <FormItem v-if="['appointAllow', 'appointProhibit'].includes(formData.permission_pack_type)" :label="$L('指定人员')" prop="permission_pack_userid">
                        <UserSelect v-model="formData.permission_pack_userid" :multiple-max="200" avatar-name show-disable :title="$L('请选择指定人员')"/>
                        <div class="form-tip">{{$L('指定人员最多可选择200人')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{$L('WebDAV')}}</h3>
                <div class="form-box">
                    <Alert v-if="webDavStatus.path_conflicts > 0" type="error">
                        {{$L('发现(*)组同路径文件，处理后才能启用 WebDAV', webDavStatus.path_conflicts)}}
                        <Button slot="desc" @click="openWebDavConflicts" style="margin-top:8px;">{{$L('查看冲突文件')}}</Button>
                    </Alert>
                    <FormItem :label="$L('启用 WebDAV')">
                        <i-switch
                            v-model="formData.webdav_enabled"
                            true-value="open"
                            false-value="close"
                            :disabled="webDavStatus.path_conflicts > 0 && formData.webdav_enabled !== 'open'"/>
                    </FormItem>
                    <template v-if="formData.webdav_enabled === 'open'">
                        <FormItem :label="$L('允许范围')">
                            <RadioGroup v-model="formData.webdav_permission_type">
                                <Radio label="all">{{$L('所有人')}}</Radio>
                                <Radio label="appoint">{{$L('指定成员')}}</Radio>
                            </RadioGroup>
                        </FormItem>
                        <FormItem v-if="formData.webdav_permission_type === 'appoint'" :label="$L('指定人员')">
                            <UserSelect
                                v-model="formData.webdav_permission_userids"
                                :multiple-max="200"
                                avatar-name
                                show-disable
                                :title="$L('请选择指定人员')"/>
                        </FormItem>
                        <FormItem :label="$L('每人应用密码上限')">
                            <InputNumber v-model="formData.webdav_max_credentials" :min="1" :max="20"/>
                        </FormItem>
                        <FormItem :label="$L('默认有效期')">
                            <InputNumber v-model="formData.webdav_default_expire_days" :min="1" :max="365"/>
                            <span style="margin-left:8px">{{$L('[day_unit].天')}}</span>
                        </FormItem>
                        <FormItem :label="$L('最长有效期')">
                            <InputNumber v-model="formData.webdav_max_expire_days" :min="1" :max="3650"/>
                            <span style="margin-left:8px">{{$L('[day_unit].天')}}</span>
                        </FormItem>
                        <div class="form-tip">{{$L('用户可在文件页面右上角的更多菜单中管理 WebDAV 应用密码')}}</div>
                    </template>
                </div>
            </div>
        </div>
        <div v-if="!embedded" class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm">{{ $L('重置') }}</Button>
        </div>
        <Modal v-model="conflictShow" :title="$L('WebDAV 路径冲突')" width="800" class-name="webdav-conflict-modal" footer-hide>
            <Alert type="warning">
                {{$L('同一拥有者的同一目录中存在完整名称相同的文件，WebDAV 无法确定应访问哪一条。')}}
                <div slot="desc">{{$L('请让对应拥有者在文件页面保留其中一条，并重命名或删除其余文件，然后刷新检测。')}}</div>
            </Alert>
            <div class="webdav-conflict-actions">
                <Button icon="md-refresh" :loading="conflictLoading > 0" @click="loadWebDavConflicts(conflictPage)">{{$L('刷新检测')}}</Button>
            </div>
            <Table class="webdav-conflict-table" :columns="conflictColumns" :data="conflictList" :loading="conflictLoading > 0"/>
            <Page
                v-if="conflictTotal > conflictPageSize"
                :total="conflictTotal"
                :current="conflictPage"
                :page-size="conflictPageSize"
                show-total
                @on-change="loadWebDavConflicts"/>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import UserSelect from "../../../../components/UserSelect.vue";

export default {
    name: "SystemFileSetting",
    components: {UserSelect},
    props: {
        embedded: Boolean,
    },
    data() {
        return {
            loadIng: 0,
            webDavStatus: {},
            conflictShow: false,
            conflictLoading: 0,
            conflictList: [],
            conflictPage: 1,
            conflictPageSize: 20,
            conflictTotal: 0,
            conflictColumns: [
                {
                    title: this.$L('拥有者'),
                    minWidth: 120,
                    maxWidth: 190,
                    render: (h, {row}) => h('div', {class: 'webdav-conflict-owner'}, [
                        h('div', {attrs: {title: row.owner.nickname || '-'}}, row.owner.nickname || '-'),
                        h('AutoTip', {
                            class: 'webdav-conflict-owner-detail',
                        }, `${row.owner.email || '-'} (ID: ${row.owner.userid})`),
                    ]),
                },
                {
                    title: this.$L('冲突路径'),
                    minWidth: 200,
                    render: (h, {row}) => h('div', {class: 'webdav-conflict-path-row'}, [
                        h('AutoTip', {
                            class: 'webdav-conflict-path',
                        }, row.path),
                        h('Tooltip', {props: {content: this.$L('复制'), placement: 'top', transfer: true}}, [
                            h('button', {
                                class: 'webdav-conflict-path-copy',
                                attrs: {type: 'button'},
                                on: {click: () => this.copyText(row.path)},
                            }, [h('Icon', {props: {type: 'md-copy', size: 15}})]),
                        ]),
                    ]),
                },
                {
                    title: this.$L('冲突记录'),
                    minWidth: 180,
                    render: (h, {row}) => h('div', row.files.map(item => h('div', {class: 'webdav-conflict-record'}, [
                        h('AutoTip', {
                            class: 'webdav-conflict-record-name',
                        }, `${item.full_name} (ID: ${item.id})`),
                        h('span', {class: 'webdav-conflict-record-actions'}, [
                            h('Tooltip', {props: {content: this.$L('重命名'), placement: 'top', transfer: true}}, [
                                h('button', {
                                    class: 'webdav-conflict-icon-button',
                                    attrs: {type: 'button'},
                                    on: {click: () => this.confirmRenameConflictFile(row, item)},
                                }, [h('Icon', {props: {type: 'md-create', size: 15}})]),
                            ]),
                            item.can_open_location ? h('Tooltip', {props: {content: this.$L('打开位置'), placement: 'top', transfer: true}}, [
                                h('button', {
                                    class: 'webdav-conflict-icon-button',
                                    attrs: {type: 'button'},
                                    on: {click: () => this.openConflictFile(row, item)},
                                }, [h('Icon', {props: {type: 'md-folder-open', size: 15}})]),
                            ]) : null,
                        ]),
                    ]))),
                },
            ],
            formData: {

            },
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['userId']),
    },

    methods: {
        submitForm() {
            this.systemSetting(true);
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formDatum_bak);
        },

        openWebDavConflicts() {
            this.conflictShow = true;
            this.loadWebDavConflicts(1);
        },

        loadWebDavConflicts(page = 1) {
            this.conflictLoading++;
            this.$store.dispatch("call", {
                url: `file/dav/conflicts?page=${page}&pagesize=${this.conflictPageSize}`,
            }).then(({data}) => {
                this.conflictPage = data.current_page || 1;
                this.conflictTotal = data.total || 0;
                this.conflictList = data.data || [];
                this.webDavStatus.path_conflicts = this.conflictTotal;
            }).finally(_ => {
                this.conflictLoading--;
            });
        },

        confirmRenameConflictFile(row, item) {
            if (row.owner.userid === this.userId) {
                this.renameConflictFile(item);
                return;
            }
            $A.modalConfirm({
                language: false,
                title: this.$L('重命名其他成员的文件'),
                content: this.$L('你正在重命名(*)的私人文件，此操作会直接修改文件名称。', row.owner.nickname || row.owner.email || row.owner.userid),
                onOk: () => $A.modalInput({
                    title: this.$L('重命名'),
                    placeholder: this.$L('请输入新名称'),
                    value: item.full_name,
                    onOk: value => this.submitConflictRename(item, value),
                }, 300),
            });
        },

        renameConflictFile(item) {
            $A.modalInput({
                title: this.$L('重命名'),
                placeholder: this.$L('请输入新名称'),
                value: item.full_name,
                onOk: value => this.submitConflictRename(item, value),
            });
        },

        submitConflictRename(item, value) {
            const name = (value || '').trim();
            if (!name) {
                return this.$L('请输入新名称');
            }
            if (name === item.full_name) {
                return false;
            }
            return this.$store.dispatch("call", {
                url: 'file/dav/conflictrename',
                method: 'post',
                data: {id: item.id, name},
            }).then(({msg}) => {
                this.loadWebDavConflicts(this.conflictPage);
                return msg;
            }).catch(({msg}) => {
                return Promise.reject(msg);
            });
        },

        openConflictFile(row, item) {
            this.conflictShow = false;
            this.$store.dispatch('filePos', {
                folderId: item.location_parent_id || null,
                fileId: null,
                shakeId: item.id,
                board: item.location_board,
            });
        },

        systemSetting(save, silent = false) {
            this.loadIng++;
            const fileRequest = () => this.$store.dispatch("call", {
                url: 'system/setting/file?type=' + (save ? 'save' : 'all'),
                data: this.formData,
            });
            const webDavRequest = () => this.$store.dispatch("call", {
                url: 'file/dav/adminsetting?type=' + (save ? 'save' : 'all'),
                data: this.formData,
                method: save ? 'post' : 'get',
            });
            const webDavStatusRequest = () => this.$store.dispatch("call", {
                url: 'file/dav/adminstatus',
            });
            const request = save
                ? fileRequest().then(fileSetting => webDavRequest().then(webDavSetting => Promise.all([
                    Promise.resolve(fileSetting),
                    Promise.resolve(webDavSetting),
                    webDavStatusRequest(),
                ])))
                : Promise.all([fileRequest(), webDavRequest(), webDavStatusRequest()]);
            return request.then(([fileSetting, webDavSetting, webDavStatus]) => {
                if (save && !silent) {
                    $A.messageSuccess('修改成功');
                }
                this.formData = Object.assign({}, fileSetting.data, webDavSetting.data);
                this.webDavStatus = webDavStatus.data || {};
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save && !silent) {
                    $A.modalError(msg);
                }
                if (silent) {
                    return Promise.reject(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },
    }
}
</script>

<style lang="scss">
.webdav-conflict-modal .webdav-conflict-actions {
    display: flex;
    justify-content: flex-end;
    margin: 16px 0 12px;
}

.webdav-conflict-modal .webdav-conflict-table {
    margin-bottom: 16px;
}

.webdav-conflict-modal .webdav-conflict-owner {
    min-width: 0;
}

.webdav-conflict-modal .webdav-conflict-owner > div,
.webdav-conflict-modal .webdav-conflict-owner-detail,
.webdav-conflict-modal .webdav-conflict-path {
    display: block;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.webdav-conflict-modal .webdav-conflict-owner-detail {
    font-size: 12px;
}

.webdav-conflict-modal .webdav-conflict-path-row {
    display: flex;
    align-items: center;
    gap: 4px;
    min-width: 0;
}

.webdav-conflict-modal .webdav-conflict-path {
    flex: 1;
}

.webdav-conflict-modal .webdav-conflict-path-row > .ivu-tooltip {
    display: none;
}

.webdav-conflict-modal .webdav-conflict-path-row:hover > .ivu-tooltip,
.webdav-conflict-modal .webdav-conflict-path-row > .ivu-tooltip:focus-within {
    display: inline-block;
}

.webdav-conflict-modal .webdav-conflict-path-copy {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    padding: 0;
    color: #515a6e;
    background: transparent;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
}

.webdav-conflict-modal .webdav-conflict-path-copy:hover {
    color: #2d8cf0;
    background: #f0f5ff;
}

.webdav-conflict-modal .webdav-conflict-record {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    white-space: nowrap;
}

.webdav-conflict-modal .webdav-conflict-record-name {
    display: block;
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.webdav-conflict-modal .webdav-conflict-record-actions {
    display: flex;
    align-items: center;
    flex: none;
    gap: 4px;
    .ivu-tooltip,
    .ivu-tooltip-rel {
        height: 20px;
    }
}

.webdav-conflict-modal .webdav-conflict-icon-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    padding: 0;
    color: #515a6e;
    background: transparent;
    border: 0;
    border-radius: 4px;
    cursor: pointer;
}

.webdav-conflict-modal .webdav-conflict-icon-button:hover {
    color: #2d8cf0;
    background: #f0f5ff;
}

.webdav-conflict-modal .webdav-conflict-table th,
.webdav-conflict-modal .webdav-conflict-table td {
    white-space: nowrap;
}
</style>
