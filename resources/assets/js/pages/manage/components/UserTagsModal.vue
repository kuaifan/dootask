<template>
    <ModalAlive
        v-model="visible"
        class-name="user-tags-manage-modal"
        :title="$L('个性标签管理')"
        :mask-closable="false"
        :footer-hide="true"
        width="520"
        :closable="true">
        <div class="tag-modal-container">
            <div class="tag-modal-form">
                <Input
                    v-model="newTagName"
                    :maxlength="20"
                    :disabled="pending.add"
                    :placeholder="$L('请输入个性标签')"
                    @on-enter="handleAdd">
                    <Button
                        slot="append"
                        type="primary"
                        :loading="pending.add"
                        @click="handleAdd">{{$L('添加')}}</Button>
                </Input>
            </div>
            <div class="tag-modal-body">
                <div v-if="loading > 0 && tags.length === 0" class="tag-loading">
                    <Loading />
                </div>
                <div v-else-if="tags.length === 0" class="tag-empty">
                    <Icon type="ios-pricetags-outline" size="32" />
                    <p>{{$L('还没有个性标签，快来添加吧~')}}</p>
                </div>
                <ul v-else class="tag-list">
                    <li
                        v-for="tag in tags"
                        :key="tag.id"
                        class="tag-item"
                        :class="{'is-editing': editId === tag.id}">
                        <div class="tag-item-main">
                            <div class="tag-name" v-if="editId !== tag.id">
                                <div class="tag-pill" :class="{'is-recognized': tag.recognized}">{{tag.name}}</div>
                            </div>
                            <div class="tag-name edit" v-else>
                                <Input
                                    ref="editInput"
                                    size="small"
                                    v-model="editName"
                                    :maxlength="20"
                                    :disabled="isPending(tag.id, 'edit')"
                                    @on-enter="confirmEdit(tag)"/>
                            </div>
                            <div class="tag-actions">
                                <Button
                                    type="text"
                                    size="small"
                                    class="recognize-btn"
                                    :loading="isPending(tag.id, 'recognize')"
                                    @click="toggleRecognize(tag)">
                                    <Icon type="md-thumbs-up" />
                                    <span v-if="tag.recognition_total > 0">{{tag.recognition_total}}</span>
                                    <span class="recognize-text">{{$L('认可')}}</span>
                                </Button>
                                <template v-if="editId === tag.id">
                                    <Button
                                        type="primary"
                                        size="small"
                                        :loading="isPending(tag.id, 'edit')"
                                        @click="confirmEdit(tag)">{{$L('保存')}}</Button>
                                    <Button
                                        type="text"
                                        size="small"
                                        @click="cancelEdit">{{$L('取消')}}</Button>
                                </template>
                                <template v-else>
                                    <Button
                                        v-if="tag.can_edit"
                                        type="text"
                                        size="small"
                                        @click="startEdit(tag)">{{$L('编辑')}}</Button>
                                    <Button
                                        v-if="tag.can_delete"
                                        type="text"
                                        size="small"
                                        :loading="isPending(tag.id, 'delete')"
                                        @click="confirmDelete(tag)">{{$L('删除')}}</Button>
                                </template>
                            </div>
                        </div>
                        <div class="tag-meta-info" v-if="tag.created_by_name">
                            <span>{{$L('由 (*) 创建', tag.created_by_name)}}</span>
                        </div>
                    </li>
                </ul>
            </div>
            <div v-if="total > 0" class="tag-modal-footer">
                <span>{{$L('当前共 (*) 个标签', total)}}</span>
            </div>
        </div>
    </ModalAlive>
</template>

<script>
export default {
    name: 'UserTagsModal',
    props: {
        value: {
            type: Boolean,
            default: false
        },
        userid: {
            type: Number,
            required: true
        }
    },
    data() {
        return {
            visible: this.value,
            loading: 0,
            tags: [],
            newTagName: '',
            editId: null,
            editName: '',
            pending: {
                add: false,
                tagId: null,
                type: ''
            }
        };
    },
    computed: {
        userId() {
            return this.$store.state.userId;
        },
        total() {
            return this.tags.length;
        }
    },
    watch: {
        value(v) {
            this.visible = v;
            if (v) {
                this.openModal();
            }
        },
        visible(v) {
            this.$emit('input', v);
            if (!v) {
                this.resetInlineState();
            }
        },
        userid() {
            if (this.visible) {
                this.loadTags();
            }
        }
    },
    methods: {
        openModal() {
            this.resetInlineState();
            this.loadTags();
        },
        resetInlineState() {
            this.newTagName = '';
            this.editId = null;
            this.editName = '';
            this.pending = {
                add: false,
                tagId: null,
                type: ''
            };
        },
        setPending(type, tagId = null) {
            if (type === 'add') {
                this.pending.add = true;
            } else {
                this.pending.tagId = tagId;
                this.pending.type = type;
            }
        },
        clearPending(type) {
            if (type === 'add') {
                this.pending.add = false;
            } else if (this.pending.type === type) {
                this.pending.tagId = null;
                this.pending.type = '';
            }
        },
        isPending(tagId, type) {
            return this.pending.tagId === tagId && this.pending.type === type;
        },
        loadTags() {
            if (!this.userid) {
                return;
            }
            this.loading++;
            this.$store.dispatch('call', {
                url: 'users/tags/lists',
                data: {userid: this.userid},
            }).then(({data}) => {
                this.applyTagData(data);
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('加载失败'));
            }).finally(() => {
                this.loading--;
            });
        },
        applyTagData(data) {
            const list = Array.isArray(data?.list) ? data.list : [];
            this.tags = list;
            const top = Array.isArray(data?.top) ? data.top : list.slice(0, 10);
            const total = typeof data?.total === 'number' ? data.total : list.length;
            this.emitUpdated({list, top, total});
        },
        emitUpdated(payload) {
            this.$emit('updated', payload);
            this.$store.dispatch('saveUserExtra', {
                userid: this.userid,
                data: {
                    personal_tags: Array.isArray(payload?.top) ? payload.top : [],
                    personal_tags_total: typeof payload?.total === 'number'
                        ? payload.total
                        : (Array.isArray(payload?.top) ? payload.top.length : 0)
                }
            });
        },
        handleAdd() {
            const name = this.newTagName.trim();
            if (!name) {
                $A.messageError(this.$L('请输入个性标签'));
                return;
            }
            if (name.length > 20) {
                $A.messageError(this.$L('标签名称最多只能设置20个字'));
                return;
            }
            if (this.pending.add) {
                return;
            }
            this.setPending('add');
            this.$store.dispatch('call', {
                url: 'users/tags/add',
                method: 'post',
                data: {userid: this.userid, name},
            }).then(({data, msg}) => {
                this.applyTagData(data);
                this.newTagName = '';
                if (msg) {
                    $A.messageSuccess(msg);
                }
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('添加失败'));
            }).finally(() => {
                this.clearPending('add');
            });
        },
        startEdit(tag) {
            this.editId = tag.id;
            this.editName = tag.name;
            this.$nextTick(() => {
                const input = this.$refs.editInput;
                if (input && input.focus) {
                    input.focus();
                } else if (Array.isArray(input) && input.length > 0 && input[0].focus) {
                    input[0].focus();
                }
            });
        },
        cancelEdit() {
            this.editId = null;
            this.editName = '';
        },
        confirmEdit(tag) {
            const name = this.editName.trim();
            if (!name) {
                $A.messageError(this.$L('请输入个性标签'));
                return;
            }
            if (name.length > 20) {
                $A.messageError(this.$L('标签名称最多只能设置20个字'));
                return;
            }
            if (name === tag.name) {
                this.cancelEdit();
                return;
            }
            if (this.isPending(tag.id, 'edit')) {
                return;
            }
            this.setPending('edit', tag.id);
            this.$store.dispatch('call', {
                url: 'users/tags/update',
                method: 'post',
                data: {tag_id: tag.id, name},
            }).then(({data, msg}) => {
                this.applyTagData(data);
                this.cancelEdit();
                if (msg) {
                    $A.messageSuccess(msg);
                }
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('保存失败'));
            }).finally(() => {
                this.clearPending('edit');
            });
        },
        confirmDelete(tag) {
            if (this.isPending(tag.id, 'delete')) {
                return;
            }
            $A.modalConfirm({
                title: this.$L('删除标签'),
                content: this.$L('确定要删除该标签吗？'),
                onOk: () => {
                    this.deleteTag(tag);
                }
            });
        },
        deleteTag(tag) {
            this.setPending('delete', tag.id);
            this.$store.dispatch('call', {
                url: 'users/tags/delete',
                method: 'post',
                data: {tag_id: tag.id},
            }).then(({data, msg}) => {
                this.applyTagData(data);
                if (msg) {
                    $A.messageSuccess(msg);
                }
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('删除失败'));
            }).finally(() => {
                this.clearPending('delete');
            });
        },
        toggleRecognize(tag) {
            if (this.isPending(tag.id, 'recognize')) {
                return;
            }
            this.setPending('recognize', tag.id);
            this.$store.dispatch('call', {
                url: 'users/tags/recognize',
                method: 'post',
                data: {tag_id: tag.id},
            }).then(({data, msg}) => {
                this.applyTagData(data);
                if (msg) {
                    $A.messageSuccess(msg);
                }
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('操作失败'));
            }).finally(() => {
                this.clearPending('recognize');
            });
        }
    }
};
</script>

<style lang="scss" scoped>
.user-tags-manage-modal {
    .tag-modal-container {
        padding-bottom: 20px;
    }
    .tag-modal-form {
        margin-bottom: 16px;
    }
    .tag-modal-body {
        max-height: 360px;
        overflow-y: auto;
        margin-bottom: 16px;
    }
    .tag-loading {
        display: flex;
        justify-content: center;
        padding: 40px 0;
    }
    .tag-empty {
        text-align: center;
        padding: 36px 0 32px;
        color: #909399;
        p {
            margin-top: 8px;
        }
    }
    .tag-list {
        list-style: none;
        margin: 0;
        padding: 0;
        .tag-item {
            border: 1px solid var(--divider-color, #ebeef5);
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 12px;
            &:last-child {
                margin-bottom: 0;
            }
            &.is-editing {
                background-color: rgba(64, 158, 255, 0.08);
            }
            .tag-item-main {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
            }
            .tag-name {
                flex: 1;
                display: flex;
                align-items: center;
                &.edit {
                    max-width: 220px;
                }
            }
            .tag-pill {
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
            }
            .tag-actions {
                display: flex;
                align-items: center;
                gap: 4px;
                .recognize-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    .ivu-icon {
                        transform: translateY(-1px);
                    }
                    .recognize-text {
                        padding-left: 4px;
                        font-size: 12px;
                    }
                }
            }
            .tag-meta-info {
                margin-top: 6px;
                font-size: 12px;
                color: #a0a3a6;
            }
        }
    }
    .tag-modal-footer {
        color: #909399;
        font-size: 12px;
    }
}
</style>
