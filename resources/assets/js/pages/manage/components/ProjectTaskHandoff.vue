<template>
    <div class="project-task-handoff" :class="[records.length > 0 ? 'has-records' : '']">
        <div v-if="canAssign && showAssign" class="handoff-actions">
            <Button size="small" type="primary" icon="md-person-add" :loading="optionsLoading" @click="openAssign">{{$L('指派')}}</Button>
        </div>
        <div v-for="(item, index) in records" :key="item.id" class="handoff-record">
            <div v-if="index === 0 || date(item.created_at) !== date(records[index - 1].created_at)" class="handoff-date">{{date(item.created_at)}}</div>
            <UserAvatar v-if="item.userid" :userid="item.userid" :size="18" showName/>
            <div v-else class="handoff-system"><Icon type="ios-contact" :size="18"/> {{$L('系统')}}</div>
            <div class="handoff-detail">
                <div>{{sourceLabel(item)}}</div>
                <div v-if="ownersChanged(item)" class="handoff-people">
                    <template v-if="item.record.before">
                        <UserAvatar v-for="id in item.record.before.owners" :key="'b' + id" :userid="id" :size="18" showName/>
                        <span v-if="!item.record.before.owners.length">{{$L('无')}}</span>
                        <Icon type="ios-arrow-forward"/>
                    </template>
                    <UserAvatar v-for="id in item.record.after.owners" :key="'a' + id" :userid="id" :size="18" showName/>
                    <span v-if="!item.record.after.owners.length">{{$L('无')}}</span>
                </div>
                <div v-if="flowChanged(item)" class="handoff-change">
                    {{flowName(item.record.before.flow_item_name)}}
                    <Icon type="ios-arrow-forward"/>
                    {{flowName(item.record.after.flow_item_name)}}
                </div>
                <div v-for="(text, i) in stateChanges(item)" :key="i" class="handoff-change">{{text}}</div>
                <div v-if="item.record.note" class="handoff-note">{{item.record.note}}</div>
                <div class="handoff-time">{{time(item.created_at)}}</div>
            </div>
        </div>
        <div v-if="loading && showLoad" class="handoff-empty"><Loading/></div>
        <div v-else-if="error" class="handoff-empty">
            <span>{{error}}</span>
            <Button type="text" @click="load(true)">{{$L('重试')}}</Button>
        </div>
        <div v-else-if="!records.length && (!loading || !showLoad)" class="handoff-empty">{{$L('暂无流转记录')}}</div>
        <div v-if="hasMore && !loading" class="handoff-bottom">
            <Button type="text" @click="load(false)">{{$L('加载更多')}}</Button>
        </div>
        <Modal
            v-model="assignVisible"
            :title="$L('指派负责人')"
            width="460"
            :mask-closable="false"
            :closable="!saving">
            <Form v-if="options" label-position="top" class="handoff-form">
                <FormItem v-if="options.protected.length" :label="$L('保留负责人')">
                    <div class="handoff-people">
                        <UserAvatar v-for="id in options.protected" :key="id" :userid="id" :size="24" showName/>
                    </div>
                    <div class="handoff-help">{{$L('管理范围外，不可移除')}}</div>
                </FormItem>
                <FormItem :label="$L('负责人')">
                    <UserSelect
                        v-model="selected"
                        :users="selectableUsers"
                        :uncancelable="options.protected"
                        :multiple-max="10"
                        :disabled="saving"
                        :title="$L('选择任务负责人')"
                        avatar-name/>
                </FormItem>
                <FormItem :label="$L('指派留言')" :required="options.note_required">
                    <Input
                        v-model="note"
                        type="textarea"
                        :rows="4"
                        :maxlength="1000"
                        :disabled="saving"
                        :placeholder="$L(options.note_required ? '必填' : '选填')"/>
                </FormItem>
                <div v-for="group in changes" :key="group.label" class="handoff-summary">
                    <span>{{group.label}}:</span>
                    <UserAvatar v-for="id in group.ids" :key="id" :userid="id" :size="18" showName/>
                    <span v-if="!group.ids.length">{{$L('无')}}</span>
                </div>
            </Form>
            <div slot="footer">
                <Button :disabled="saving" @click="assignVisible = false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="saving" @click="submit">{{$L('确定')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import UserSelect from "../../../components/UserSelect.vue";

export default {
    name: 'ProjectTaskHandoff',
    components: {UserSelect},
    props: {
        task: {
            type: Object,
            required: true,
        },

        showLoad: {
            type: Boolean,
            default: true,
        },

        showAssign: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            records: [],
            loading: false,
            error: '',
            hasMore: false,
            canAssign: false,
            requestId: 0,

            optionsLoading: false,
            assignVisible: false,
            saving: false,
            options: null,
            selected: [],
            note: '',
        };
    },
    computed: {
        assignState() {
            return {visible: this.canAssign, loading: this.optionsLoading};
        },

        departmentIds() {
            return this.$store.state.departmentOwnerProjectViewEnabled
                ? (this.$store.state.cacheDepartmentOwnerIds || []).join(',') : 'all';
        },

        selectableUsers() {
            if (!this.options) {
                return [];
            }
            return this.options.users.filter(user => this.options.candidates.includes(user.userid)
                || this.options.owners.includes(user.userid));
        },

        targetOwners() {
            return [...new Set([...this.selected, ...(this.options?.protected || [])])].sort((a, b) => a - b);
        },

        changes() {
            const before = this.options?.owners || [];
            const after = this.targetOwners;
            return [
                {label: this.$L('保留'), ids: before.filter(id => after.includes(id))},
                {label: this.$L('新增'), ids: after.filter(id => !before.includes(id))},
                {label: this.$L('移除'), ids: before.filter(id => !after.includes(id))},
            ];
        },
    },
    watch: {
        assignState: {
            immediate: true,
            handler(state) {
                this.$emit('on-assign-state', state);
            },
        },

        'task.id'() {
            this.assignVisible = false;
            this.load(true, true);
        },

        'task.updated_at'() {
            this.load(true);
        },

        departmentIds() {
            this.assignVisible = false;
            this.load(true, true);
        },
    },

    mounted() {
        this.load(true);
    },

    beforeDestroy() {
        this.requestId++;
        this.$emit('on-assign-state', {visible: false, loading: false});
    },

    methods: {
        async call(method, data = {}) {
            return this.$store.dispatch('call', {
                url: `projecttaskhandoff/${method}`,
                method: method === 'assign' ? 'post' : 'get',
                data: {
                    task_id: this.task.id,
                    department_owner_ids: this.departmentIds,
                    ...data,
                },
            });
        },

        async load(reset = true, clear = false) {
            const requestId = ++this.requestId;
            this.loading = true;
            this.error = '';
            this.$emit('on-load-change', true);
            // 切换任务或权限范围时清空，刷新同一任务时保留内容直到响应返回。
            if (clear) {
                this.records = [];
                this.canAssign = false;
                this.hasMore = false;
            }
            try {
                const {data} = await this.call('lists', {
                    before_id: reset ? 0 : this.records[this.records.length - 1]?.id,
                });
                if (requestId !== this.requestId) {
                    return;
                }
                this.records = reset ? data.lists : this.records.concat(data.lists);
                this.hasMore = data.has_more;
                this.canAssign = data.can_assign;
            } catch ({msg}) {
                if (requestId === this.requestId) {
                    this.records = [];
                    this.canAssign = false;
                    this.hasMore = false;
                    this.error = msg || this.$L('加载失败');
                }
            } finally {
                if (requestId === this.requestId) {
                    this.loading = false;
                    this.$emit('on-load-change', false);
                }
            }
        },

        async openAssign() {
            const taskId = this.task.id;
            const departmentIds = this.departmentIds;
            this.optionsLoading = true;
            try {
                const {data} = await this.call('options');
                if (taskId !== this.task.id || departmentIds !== this.departmentIds || this._isDestroyed) {
                    return;
                }
                this.options = data;
                this.selected = [...data.owners];
                this.note = '';
                this.assignVisible = true;
            } catch ({msg}) {
                $A.modalError(msg);
            } finally {
                this.optionsLoading = false;
            }
        },

        async submit() {
            if (!this.options || this.saving) {
                return;
            }
            if (this.targetOwners.length > 10) {
                return $A.messageError('任务负责人最多不能超过10个');
            }
            if (JSON.stringify(this.targetOwners) === JSON.stringify(this.options.owners)) {
                return $A.messageError('负责人未发生变化');
            }
            if (this.options.note_required && !this.note.trim()) {
                return $A.messageError('请填写指派留言');
            }
            this.saving = true;
            try {
                const {data} = await this.call('assign', {
                    owners: this.targetOwners,
                    version: this.options.version,
                    note: this.note.trim(),
                });
                this.assignVisible = false;
                await this.$store.dispatch('saveTask', data);
                this.load(true);
                $A.messageSuccess('指派成功');
            } catch ({msg}) {
                $A.modalError(msg);
            } finally {
                this.saving = false;
            }
        },

        date(value) {
            return $A.dayjs(value).format('YYYY-MM-DD');
        },

        time(value) {
            return $A.dayjs(value).format('YYYY-MM-DD HH:mm');
        },

        ownersChanged(item) {
            return !item.record.before || JSON.stringify(item.record.before.owners) !== JSON.stringify(item.record.after.owners);
        },

        flowChanged(item) {
            return item.record.before && item.record.before.flow_item_name !== item.record.after.flow_item_name;
        },

        flowName(value) {
            return (value || '').split('|')[1] || this.$L('无');
        },

        stateChanges(item) {
            if (!item.record.before) {
                return [];
            }
            const {before, after} = item.record;
            const changes = [];
            if (!!before.complete_at !== !!after.complete_at) {
                changes.push(after.complete_at ? this.$L('标记已完成') : this.$L('标记未完成'));
            }
            if (!!before.archived_at !== !!after.archived_at) {
                changes.push(after.archived_at ? this.$L('任务归档') : this.$L('任务取消归档'));
            }
            return changes;
        },

        sourceLabel(item) {
            switch (item.source) {
                case 'create':
                    return this.$L('创建任务');
                case 'copy':
                    return this.$L('复制任务');
                case 'assign':
                    return this.$L('指派负责人');
                case 'flow':
                    return this.$L('状态流转');
                case 'move':
                    return this.$L('移动任务');
                case 'transfer':
                    return this.$L('移交任务身份');
                case 'member_exit':
                    return this.$L('退出项目');
                case 'recurring':
                    return this.$L('重复任务');
                case 'auto_archive':
                    return this.$L('任务自动归档');
                default:
                    return this.ownersChanged(item) ? this.$L('修改负责人') : this.$L('任务状态');
            }
        },
    },
};
</script>

<style lang="scss">
.project-task-handoff {
    position: relative;
    padding: 14px 10px 20px 24px;
    color: #606266;
    &.has-records {
        .handoff-actions {
            position: absolute;
            right: 10px;
            top: 14px;
            + .handoff-record {
                .handoff-date,
                .handoff-system,
                .avatar-wrapper {
                    max-width: 240px;
                }
            }
        }
        .handoff-empty,
        .handoff-bottom {
            text-align: left;
        }
    }
    .handoff-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        color: #a5a9ae;
        font-size: 13px;
        margin-bottom: 24px;
    }
    .handoff-record {
        margin-bottom: 24px;
    }
    .handoff-date {
        color: #a8aaad;
        margin-bottom: 15px;
        font-size: 14px;
    }
    .handoff-system {
        color: #a2a8b1;
        font-size: 13px;
    }
    .handoff-detail {
        padding: 10px 0 0 35px;
        font-size: 13px;
        line-height: 1.8;
        overflow-wrap: anywhere;
    }
    .handoff-change {
        margin-top: 5px;
    }
    .handoff-note {
        margin-top: 9px;
        padding-left: 10px;
        border-left: 2px solid #e5eadf;
        white-space: pre-wrap;
    }
    .handoff-time {
        color: #b5b8bd;
        font-size: 13px;
        margin-top: 5px;
    }
    .handoff-empty,
    .handoff-bottom {
        text-align: center;
        color: #a6a9af;
        padding: 12px 0;
    }
    .handoff-empty {
        overflow-wrap: anywhere;
    }
}
.handoff-people, .handoff-summary {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 5px;
}
.handoff-form {
    padding: 4px;
    .handoff-help {
        font-size: 13px;
        color: #a6a9af;
        margin-top: 8px;
    }
    .handoff-summary {
        font-size: 13px;
        margin-top: 9px;
    }
    .common-user-select > ul > li {
        padding-right: 8px;
    }
}
.task-detail.open-dialog .task-dialog .project-task-handoff {
    position: absolute;
    top: 40px;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1;
    background: #fff;
    overflow: auto;
}
</style>
