<template>
    <li :data-id="column.id" class="column-item">
        <div
            :class="['column-head', column.color ? 'custom-color' : '']"
            :style="column.color ? {backgroundColor: column.color} : {}">
            <div class="column-head-title">
                <AutoTip v-html="transformEmojiToHtml(column.name)"></AutoTip>
                <em>({{projectPanel.panelTask(column.tasks).length}})</em>
            </div>
            <div class="column-head-icon">
                <div v-if="loading" class="loading"><Loading /></div>
                <EDropdown
                    v-else-if="!readonly"
                    trigger="click"
                    size="medium"
                    @visible-change="onMenuVisible"
                    @command="$emit('on-command', $event)">
                    <Icon type="ios-more" />
                    <EDropdownMenu slot="dropdown" class="project-panel-more-dropdown-menu">
                        <li v-if="menuMounted" class="project-panel-more-dropdown-warp">
                            <ul>
                                <EDropdownItem command="title">
                                    <div class="item">
                                        <Icon type="md-create" />{{$L('修改')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem command="archive_completed">
                                    <div class="item">
                                        <Icon type="ios-filing" />{{$L('归档')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem command="remove">
                                    <div class="item">
                                        <Icon type="md-trash" />{{$L('删除')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem v-for="(c, k) in $store.state.columnColorList" :key="k" :divided="k==0" :command="c">
                                    <div class="item">
                                        <i class="taskfont" :style="{color:c.color||'#ddd'}" v-html="c.color == column.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                    </div>
                                </EDropdownItem>
                            </ul>
                        </li>
                    </EDropdownMenu>
                </EDropdown>
                <Icon v-if="!readonly" class="last" type="md-add" @click="$emit('on-top-show', true)" />
            </div>
        </div>
        <Scrollbar
            class="column-task"
            class-name="task-scrollbar"
            @on-scroll="handleTaskScroll">
            <div v-if="!readonly && topShow" class="task-item additem">
                <TaskAddSimple
                    :column-id="column.id"
                    :project-id="projectId"
                    :add-top="true"
                    @on-close="$emit('on-top-show', false)"
                    @on-priority="$emit('on-priority', $event)"
                    auto-active/>
            </div>
            <Draggable
                :list="column.tasks"
                :animation="150"
                :disabled="dragDisabled"
                class="task-list"
                draggable=".task-draggable"
                filter=".complete"
                group="task"
                @add="onTaskAdd"
                @sort="$emit('on-sort')"
                @remove="$emit('on-sort')">
                <div
                    v-for="item in column.tasks"
                    :key="`${column.id}_${item.id}`"
                    :data-id="item.id"
                    :class="['task-item task-draggable', item.complete_at ? 'complete' : '', projectPanel.taskIsHidden(item) ? 'hidden' : '']"
                    :style="$A.generateColorVarStyle(item.flow_item_color, [10], 'flow-item-custom-color', taskItemStyle(item))"
                    @click="projectPanel.openTask(item)">
                    <template v-if="taskItemVisible(item)">
                        <div :class="['task-head', item.desc ? 'has-desc' : '']">
                            <div class="task-title">
                                <!--工作流状态-->
                                <span v-if="item.flow_item_name" :class="item.flow_item_status" @click.stop="openMenu($event, item)">{{item.flow_item_name}}</span>
                                <!--任务描述-->
                                <pre>{{item.name}}</pre>
                            </div>
                            <div class="task-menu" @click.stop="">
                                <TaskMenu v-if="!readonly" :ref="`taskMenu_${item.id}`" :task="item" icon="ios-more"/>
                            </div>
                        </div>
                        <template v-if="!item.complete_at">
                            <div v-if="item.desc" class="task-desc"><pre v-html="item.desc"></pre></div>
                            <TaskTag v-if="item.task_tag.length > 0" class="task-tags" :tags="item.task_tag"/>
                            <div class="task-users">
                                <ul>
                                    <li v-for="(user, keyu) in ownerUser(item.task_user)" :key="keyu">
                                        <UserAvatar :userid="user.userid" size="32" :borderWidth="2" :borderColor="item.color"/>
                                    </li>
                                    <li v-if="ownerUser(item.task_user).length === 0" class="no-owner">
                                        <Button type="primary" size="small" ghost @click.stop="projectPanel.openTask(item, true)">{{$L('领取任务')}}</Button>
                                    </li>
                                </ul>
                                <div v-if="item.file_num > 0" class="task-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                                <div v-if="item.msg_num > 0" class="task-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <div v-if="item.sub_num > 0" class="task-sub-num">{{item.sub_complete}}/{{item.sub_num}}</div>
                                <Progress :percent="item.percent" :stroke-width="6" />
                                <ETooltip
                                    v-if="item.end_at"
                                    :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                    :disabled="$isEEUIApp || windowTouch"
                                    :open-delay="600"
                                    :content="item.end_at">
                                    <div v-if="!item.complete_at"><i class="taskfont">&#xe71d;</i>{{ expiresFormat(item.end_at) }}</div>
                                </ETooltip>
                            </div>
                            <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                        </template>
                    </template>
                </div>
                <div v-if="!readonly" class="task-item additem">
                    <TaskAddSimple
                        :column-id="column.id"
                        :project-id="projectId"
                        @on-priority="$emit('on-priority', $event)"/>
                </div>
            </Draggable>
        </Scrollbar>
    </li>
</template>

<script>
import Draggable from 'vuedraggable'
import TaskAddSimple from "./TaskAddSimple";
import TaskMenu from "./TaskMenu";
import TaskTag from "./ProjectTaskTag/tags.vue";
import transformEmojiToHtml from "../../../utils/emoji";
import {updateTaskVisibilitys} from "../../../utils/taskVisibility";

/**
 * 项目看板的一列，独立成组件，使任务可见状态等列内变化只重渲染本列，不牵动整个面板
 * 筛选、搜索、打开任务等面板级逻辑通过注入的 projectPanel 读取
 */
export default {
    name: "ProjectColumn",
    components: {
        Draggable,
        TaskAddSimple,
        TaskMenu,
        TaskTag,
    },
    inject: ['projectPanel'],
    props: {
        column: {
            type: Object,
            required: true
        },
        projectId: {
            type: Number,
            default: 0
        },
        readonly: {
            type: Boolean,
            default: false
        },
        dragDisabled: {
            type: Boolean,
            default: false
        },
        loading: {
            type: Boolean,
            default: false
        },
        topShow: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            visible: false,         // 列是否在看板可视区内
            taskVisibilitys: {},    // 列内任务的可见状态与高度
            menuMounted: false,     // 菜单首次展开时才渲染菜单项
        }
    },
    methods: {
        transformEmojiToHtml,

        onMenuVisible(visible) {
            if (visible) {
                this.menuMounted = true
            }
        },

        openMenu(event, task) {
            const el = this.$refs[`taskMenu_${task.id}`];
            if (el) {
                el[0].handleClick(event)
            }
        },

        // 从其他列拖入的任务落在可视位置，直接标记可见，避免先显示空白占位
        onTaskAdd({item}) {
            const dataId = item?.getAttribute('data-id');
            if (dataId) {
                this.$set(this.taskVisibilitys, dataId, {visible: true, height: 0});
            }
        },

        ownerUser(list) {
            return list.filter(({owner}) => owner == 1).sort((a, b) => {
                return a.id - b.id;
            });
        },

        expiresFormat(date) {
            return $A.countDownFormat(this.projectPanel.nowTime, date)
        },

        taskItemVisible({id}) {
            return this.visible && this.taskVisibilitys[id]?.visible
        },

        taskItemStyle({id, complete_at, color}) {
            const style = {}
            if (color) {
                style.backgroundColor = color;
            }
            if (!this.taskItemVisible({id})) {
                style.height = (this.taskVisibilitys[id]?.height || (complete_at ? 46 :146)) + 'px';
            }
            return style;
        },

        /**
         * 检查列是否在看板可视区内，可见时再更新列内任务的可见状态
         * @param mainRect 看板容器的边界
         */
        checkVisibility(mainRect) {
            const columnRect = this.$el.getBoundingClientRect();
            const visible = (
                columnRect.left < mainRect.right &&
                columnRect.right > mainRect.left &&
                columnRect.top < mainRect.bottom &&
                columnRect.bottom > mainRect.top
            )
            if (visible) {
                this.handleTaskScroll({target: this.$el.querySelector('.task-scrollbar')})
            }
            this.visible = visible
        },

        handleTaskScroll({target}) {
            return updateTaskVisibilitys(this, target, this.taskVisibilitys)
        },
    }
}
</script>
