<template>
    <Modal
        v-model="show"
        :title="$L('批量导入用户')"
        :mask-closable="false"
        :width="(preview || result) ? 900 : 520">
        <div class="import-user-modal">
            <div class="import-tip">
                {{$L('请按模板填写后上传，列顺序：邮箱、昵称、初始密码、职位(选填)；单次最多导入500条。')}}
            </div>
            <div class="import-actions">
                <Button type="default" icon="md-download" @click="onDownloadTemplate">{{$L('下载模板')}}</Button>
                <Upload
                    name="file"
                    ref="upload"
                    :action="previewUrl"
                    :headers="headers"
                    :format="['xls','xlsx','csv']"
                    :show-upload-list="false"
                    :before-upload="handleBeforeUpload"
                    :on-success="handlePreviewSuccess"
                    :on-error="handleError"
                    :on-format-error="handleFormatError">
                    <Button type="primary" icon="md-cloud-upload" :loading="uploading">{{$L(preview ? '重新选择文件' : '上传文件')}}</Button>
                </Upload>
            </div>
            <!-- 预览（点击确定后才真正导入） -->
            <div v-if="preview && !result" class="import-preview">
                <Alert :type="preview.valid > 0 ? 'success' : 'error'" show-icon>
                    {{$L('共(*)条 · 可导入(*)条 · 错误(*)条', preview.total, preview.valid, preview.invalid)}}
                </Alert>
                <Table
                    :columns="previewColumns"
                    :data="preview.rows"
                    :row-class-name="rowClassName"
                    size="small"
                    max-height="320"
                    @on-selection-change="onSelectionChange"/>
                <!-- 勾选行后批量设置部门（错误行不可勾选） -->
                <div class="import-setdept">
                    <span class="import-batch-label">{{$L('所属部门')}}</span>
                    <Select
                        v-model="setDepartmentIds"
                        multiple
                        :disabled="selectedRows.length === 0"
                        :multiple-max="10"
                        :multiple-max-before="onMultipleMaxBefore"
                        :placeholder="$L('选择部门')"
                        class="import-setdept-select">
                        <Option
                            v-for="(item, index) in departmentList"
                            :value="item.id"
                            :key="index"
                            :label="item.chains.join(' - ')">
                            <div :class="`department-level-name level-${item.level - 1}`">{{ item.name }}</div>
                        </Option>
                    </Select>
                    <Button :type="selectedRows.length === 0 ? 'default' : 'primary'" :disabled="selectedRows.length === 0" @click="onApplyDepartment">
                        {{$L('设置部门到选中(*)项', selectedRows.length)}}
                    </Button>
                </div>
                <!-- 勾选行后批量设置邮箱认证状态（错误行不可勾选） -->
                <div class="import-setverity">
                    <span class="import-batch-label">{{$L('邮箱认证')}}</span>
                    <Button :type="selectedRows.length === 0 ? 'default' : 'primary'" :disabled="selectedRows.length === 0" @click="onApplyVerity(1)">
                        {{$L('标记选中(*)项为已认证', selectedRows.length)}}
                    </Button>
                    <Button :type="selectedRows.length === 0 ? 'default' : 'primary'" :disabled="selectedRows.length === 0" @click="onApplyVerity(0)">
                        {{$L('标记选中(*)项为未认证', selectedRows.length)}}
                    </Button>
                </div>
                <div class="import-option">
                    <Checkbox v-model="changepass">{{$L('员工首次登录需修改密码')}}</Checkbox>
                </div>
            </div>

            <!-- 导入结果 -->
            <div v-if="result" class="import-result">
                <Alert :type="result.failed.length ? 'warning' : 'success'" show-icon>
                    {{$L('导入结果：共(*)条，成功(*)条，失败(*)条', result.total, result.success, result.failed.length)}}
                </Alert>
                <Table
                    v-if="result.failed.length"
                    :columns="failedColumns"
                    :data="result.failed"
                    size="small"
                    max-height="260"/>
            </div>
        </div>
        <div slot="footer">
            <Button type="default" @click="show=false">{{$L('关闭')}}</Button>
            <Button
                v-if="preview && !result"
                type="primary"
                :loading="importing"
                :disabled="preview.valid === 0"
                @click="onConfirmImport">
                {{$L('确定导入(*)条', preview.valid)}}
            </Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'ImportUserModal',
    props: {
        value: {
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
            show: false,
            uploading: false,
            importing: false,
            changepass: true,
            setDepartmentIds: [],
            selectedRows: [],
            preview: null,
            result: null,
            previewUrl: $A.apiUrl('users/import/preview'),
            previewColumns: [
                {type: 'selection', width: 50, align: 'center'},
                {title: this.$L('行号'), key: 'line', width: 64, align: 'center'},
                {
                    title: this.$L('邮箱'),
                    minWidth: 150,
                    render: (h, {row}) => {
                        // 列渲染发生在 Table 的上下文，scoped 样式不生效，故图标颜色/布局内联（与会员列表 $primary-color 一致）
                        const arr = [h('AutoTip', {style: {minWidth: '50px'}}, row.email || '-')];
                        if (row.email_verity && row.status === 'ok') {
                            arr.push(h('Icon', {
                                props: {type: 'md-mail'},
                                attrs: {title: this.$L('已邮箱认证')},
                                style: {color: '#84C56A', marginLeft: '6px', fontSize: '16px', flexShrink: 0},
                            }));
                        }
                        return h('div', {style: {display: 'flex', alignItems: 'center'}}, arr);
                    }
                },
                {title: this.$L('昵称'), width: 90, render: (h, {row}) => h('AutoTip', row.nickname || '-')},
                {
                    title: this.$L('初始密码'),
                    width: 110,
                    render: (h, {row}) => {
                        return h('AutoTip', {
                            class: 'pwd-cell',
                            attrs: {title: this.$L('点击查看明文')},
                            on: {'on-click': () => this.$set(row, '_showPwd', !row._showPwd)}
                        }, row._showPwd ? (row.password || '') : '••••••');
                    }
                },
                {title: this.$L('职位'), width: 90, render: (h, {row}) => h('AutoTip', row.profession || '-')},
                {
                    title: this.$L('部门'),
                    minWidth: 120,
                    render: (h, {row}) => h('AutoTip', this.departmentNames(row.department)),
                },
                {
                    title: this.$L('状态'),
                    width: 80,
                    align: 'center',
                    render: (h, {row}) => {
                        const ok = row.status === 'ok';
                        return h('Tag', {props: {color: ok ? 'success' : 'error'}}, ok ? this.$L('可导入') : this.$L('错误'));
                    }
                },
                {
                    title: this.$L('原因'),
                    minWidth: 120,
                    render: (h, {row}) => h('AutoTip', row.reason ? row.reason : '-'),
                },
            ],
            failedColumns: [
                {title: this.$L('行号'), key: 'line', width: 80},
                {title: this.$L('邮箱'), minWidth: 160, render: (h, {row}) => h('AutoTip', row.email || '-')},
                {title: this.$L('失败原因'), minWidth: 140, render: (h, {row}) => h('AutoTip', row.reason || '-')},
            ],
        }
    },
    computed: {
        // userToken 由 mixins/state.js（通过 Vue.mixin 全局注册）提供
        headers() {
            return {token: this.userToken};
        },
        departmentNameMap() {
            return this.departmentList.reduce((acc, item) => {
                acc[item.id] = item.name;
                return acc;
            }, {});
        }
    },
    watch: {
        value(val) {
            this.show = val;
            if (val) {
                this.resetState();
            }
        },
        show(val) {
            this.$emit('input', val);
        },
        // 选择子部门时自动补选其所有上级部门（与单个创建/编辑用户行为一致）
        setDepartmentIds: {
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
                        this.setDepartmentIds = merged;
                    }
                }
            },
            deep: true
        }
    },
    methods: {
        resetState() {
            this.uploading = false;
            this.importing = false;
            this.changepass = true;
            this.setDepartmentIds = [];
            this.selectedRows = [];
            this.preview = null;
            this.result = null;
        },
        rowClassName(row) {
            return row.status === 'ok' ? '' : 'import-row-error';
        },
        departmentNames(ids) {
            if (!Array.isArray(ids) || ids.length === 0) {
                return '-';
            }
            const map = this.departmentNameMap;
            const names = ids.map(id => map[id]).filter(Boolean);
            return names.length ? names.join(', ') : '-';
        },
        onDownloadTemplate() {
            window.open($A.apiUrl('users/import/template?token=' + this.userToken));
        },
        handleBeforeUpload() {
            this.uploading = true;
            this.preview = null;
            this.result = null;
            this.selectedRows = [];
            this.setDepartmentIds = [];
            return true;
        },
        handlePreviewSuccess(res) {
            this.uploading = false;
            if (res && res.ret === 1) {
                const data = res.data;
                (data.rows || []).forEach(row => {
                    this.$set(row, 'department', []);     // 逐行部门，默认空
                    this.$set(row, 'email_verity', row.email_verity ? 1 : 0); // 逐行邮箱认证，默认已认证
                    if (row.status !== 'ok') {
                        this.$set(row, '_disabled', true); // 错误行不可勾选
                    }
                });
                this.preview = data;
            } else {
                // language:false：服务端返回的 msg 已本地化，不再二次翻译
                $A.modalError({content: (res && res.msg) || '解析失败', language: false});
            }
        },
        onSelectionChange(selection) {
            this.selectedRows = selection;
        },
        onApplyDepartment() {
            if (this.selectedRows.length === 0) {
                return;
            }
            const ids = [...this.setDepartmentIds];
            // on-selection-change 传出的是深拷贝行，按唯一 line 匹配回 preview.rows 的原始对象再写入
            const selectedLines = new Set(this.selectedRows.map(row => row.line));
            (this.preview && this.preview.rows ? this.preview.rows : []).forEach(row => {
                if (selectedLines.has(row.line)) {
                    this.$set(row, 'department', ids);
                }
            });
        },
        onApplyVerity(verity) {
            if (this.selectedRows.length === 0) {
                return;
            }
            // 与 onApplyDepartment 一致：按唯一 line 匹配回 preview.rows 的原始对象再写入
            const selectedLines = new Set(this.selectedRows.map(row => row.line));
            (this.preview && this.preview.rows ? this.preview.rows : []).forEach(row => {
                if (selectedLines.has(row.line)) {
                    this.$set(row, 'email_verity', verity ? 1 : 0);
                }
            });
        },
        onConfirmImport() {
            if (!this.preview || this.preview.valid === 0) {
                return;
            }
            const rows = this.preview.rows
                .filter(row => row.status === 'ok')
                .map(({line, email, nickname, password, profession, department, email_verity}) => ({
                    line,
                    email,
                    nickname,
                    password,
                    profession: profession || '',
                    department: Array.isArray(department) ? department : [],
                    email_verity: email_verity ? 1 : 0,
                }));
            this.importing = true;
            this.$store.dispatch("call", {
                url: 'users/import',
                data: {rows, changepass: this.changepass ? 1 : 0},
                method: 'post',
            }).then(({data}) => {
                this.importing = false;
                if (data.success > 0) {
                    this.$emit('imported'); // 仅在确有账号被创建时才刷新列表
                }
                if (data.failed && data.failed.length) {
                    this.result = data;
                } else {
                    this.show = false;
                    $A.modalSuccess({content: this.$L('成功导入(*)条', data.success), language: false});
                }
            }).catch(({msg}) => {
                this.importing = false;
                $A.modalError(msg);
            });
        },
        handleError() {
            this.uploading = false;
            $A.modalError('解析失败');
        },
        handleFormatError() {
            this.uploading = false;
            $A.modalWarning('仅支持 xls/xlsx/csv 文件');
        },
        onMultipleMaxBefore(num) {
            $A.messageError(this.$L('最多选择(*)个部门', num));
            return false;
        }
    }
}
</script>

<style lang="scss" scoped>
.import-user-modal {
    .import-tip { color: #808695; margin-bottom: 12px; }
    .import-actions { display: flex; gap: 12px; align-items: center; }
    .import-option { margin-top: 12px; }
    .import-batch-label {
        flex-shrink: 0;
        min-width: 64px;
        color: #515a6e;
    }
    .import-setdept {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        .import-setdept-select {
            width: auto;
        }
    }
    .import-setverity {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
    }
    .import-preview { margin-top: 16px; }
    .import-result { margin-top: 16px; }

    ::v-deep .ivu-table-cell {
        white-space: nowrap;
    }

    ::v-deep .pwd-cell {
        cursor: pointer;
        letter-spacing: 1px;
        user-select: none;
        &:hover { color: #2d8cf0; }
    }
    ::v-deep .import-row-error td {
        background-color: #fff2f0;
    }
}
</style>
