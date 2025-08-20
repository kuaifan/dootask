<template>
    <div class="project-list">
        <PageTitle :title="$L('项目')"/>
        <div class="list-search">
            <div class="search-wrapper">
                <div class="search-pre">
                    <Loading v-if="loadProjects > 0"/>
                    <Icon v-else type="ios-search" />
                </div>
                <Form class="search-form" action="javascript:void(0)" @submit.native.prevent="$A.eeuiAppKeyboardHide">
                    <Input type="search" v-model="projectKeyValue" :placeholder="$L(loadProjects > 0 ? '更新中...' : '搜索')" clearable/>
                </Form>
            </div>
        </div>
        <Draggable
            :list="projectDraggableList"
            :animation="150"
            :disabled="!(isDragging && !projectKeyValue)"
            tag="ul"
            item-key="id"
            draggable="li:not(.pinned)"
            handle=".item-sort"
            @scroll.native="onScroll"
            @touchstart.native="onTouchStart"
            v-longpress="handleLongpress"
            @start="projectDragging = true"
            @end="onProjectSortEnd">
            <li
                v-for="item in projectDraggableList"
                :key="item.id"
                :data-id="item.id"
                :class="[{operate: item.id == operateItem.id && operateVisible}, item.top_at ? 'pinned' : '']"
                @pointerdown="handleOperation"
                @click="toggleRoute('project', {projectId: item.id})">
                <div class="project-item">
                    <div class="item-left">
                        <div class="project-h1">
                            <div class="project-name" v-html="transformEmojiToHtml(item.name)"></div>
                            <div v-if="item.top_at" class="icon-top"></div>
                            <div v-if="item.task_my_num - item.task_my_complete > 0" class="num">{{item.task_my_num - item.task_my_complete}}</div>
                        </div>
                        <div class="project-h2">
                            {{item.desc}}
                        </div>
                    </div>
                    <div v-if="item.task_num > 0" class="item-right" @click.stop="modalPercent(item)">
                        <iCircle
                            type="circle"
                            trail-color="rgba(132, 197, 106, 0.2)"
                            :trail-width="7"
                            :stroke-color="item.task_percent === 100 ? 'rgba(132, 197, 106, 0)' : '#84C56A'"
                            :stroke-width="7"
                            :percent="item.task_percent"
                            :size="44">
                            <Icon v-if="item.task_percent === 100" type="ios-checkmark"></Icon>
                            <span v-else class="percent-text">{{item.task_percent}}%</span>
                        </iCircle>
                    </div>
                    <div v-show="isDragging && !projectKeyValue && !item.top_at" class="item-sort" @click.stop="handleDragTip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="M3 18h18"/><path d="M3 6h18"/></svg>
                    </div>
                </div>
            </li>
            <template v-if="projectLists.length === 0">
                <li v-if="projectKeyLoading > 0" class="loading"><Loading/></li>
                <li v-else class="nothing">
                    {{$L(projectKeyValue ? `没有任何与"${projectKeyValue}"相关的结果` : `没有任何项目`)}}
                </li>
            </template>
        </Draggable>
        <div
            v-transfer-dom
            :data-transfer="true"
            class="operate-position"
            :style="operateStyles"
            v-show="operateVisible">
            <Dropdown
                trigger="custom"
                :placement="windowLandscape ? 'bottom' : 'top'"
                :visible="operateVisible"
                @on-clickoutside="operateVisible = false"
                transfer>
                <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                <DropdownMenu slot="list">
                    <DropdownItem @click.native="handleTopClick">
                        {{ $L(operateItem.top_at ? '取消置顶' : '置顶该项目') }}
                    </DropdownItem>
                    <DropdownItem @click.native="handleChatClick">
                        {{ $L('项目讨论') }}
                    </DropdownItem>
                    <DropdownItem v-if="!projectKeyValue && !operateItem.top_at" @click.native="isDragging=!isDragging">
                        {{ $L(isDragging ? '退出排序' : '调整排序') }}
                    </DropdownItem>
                </DropdownMenu>
            </Dropdown>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import Draggable from 'vuedraggable'
import longpress from "../../../directives/longpress";
import TransferDom from "../../../directives/transfer-dom";
import transformEmojiToHtml from "../../../utils/emoji";

export default {
    name: "ProjectList",
    components: {Draggable},
    directives: {longpress, TransferDom},
    data() {
        return {
            projectKeyValue: '',
            projectKeyLoading: 0,

            operateStyles: {},
            operateVisible: false,
            operateItem: {},

            isDragging: false,
            projectDraggableList: [],
            projectDragging: false,
        }
    },

    computed: {
        ...mapState(['cacheProjects', 'loadProjects', 'longpressData']),

        projectLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = $A.cloneJSON(cacheProjects).sort((a, b) => {
                // 置顶优先
                if (a.top_at !== b.top_at && (a.top_at || b.top_at)) {
                    return $A.sortDay(b.top_at, a.top_at);
                }
                // 自定义排序
                const as = typeof a.sort === 'number' ? a.sort : Number.MAX_SAFE_INTEGER;
                const bs = typeof b.sort === 'number' ? b.sort : Number.MAX_SAFE_INTEGER;
                if (as !== bs) return as - bs;
                // 兜底：按ID倒序
                return b.id - a.id;
            });
            if (projectKeyValue) {
                return data.filter(item => $A.strExists(`${item.name} ${item.desc}`, projectKeyValue));
            }
            return data;
        },
    },

    watch: {
        projectLists: {
            handler(val) {
                if (!this.projectDragging) {
                    this.projectDraggableList = $A.cloneJSON(val)
                }
            },
            immediate: true
        },
        projectKeyValue(val) {
            if (val == '') {
                return;
            }
            if ($A.loadVConsole(val)) {
                this.projectKeyValue = '';
                return;
            }
            this.projectKeyLoading++;
            setTimeout(() => {
                if (this.projectKeyValue == val) {
                    this.searchProject();
                }
                this.projectKeyLoading--;
            }, 600);
        },
        projectDragging(val) {
            if (val) {
                this.$el.parentElement.style.overflow = 'hidden'
            } else {
                this.$el.parentElement.style.overflow = null
            }
        },
    },

    methods: {
        transformEmojiToHtml,
        onProjectSortEnd() {
            const nonPinnedItems = this.projectDraggableList.filter(item => !item.top_at)
            this.$store.dispatch("call", {
                url: 'project/user/sort',
                data: {
                    list: nonPinnedItems.map(item => item.id)
                },
                method: 'post',
                spinner: 2000
            }).then(({msg}) => {
                nonPinnedItems.forEach((item, index) => {
                    this.$store.dispatch("saveProject", {id: item.id, sort: index})
                })
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                this.projectDraggableList = $A.cloneJSON(this.projectLists)
                $A.modalError(msg)
            }).finally(() => {
                this.projectDragging = false
            })
        },
        searchProject() {
            this.projectKeyLoading++;
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.projectKeyValue
                },
            }).finally(_ => {
                this.projectKeyLoading--;
            });
        },

        toggleRoute(path, params) {
            if (this.operateVisible) {
                return
            }
            this.goForward({name: 'manage-' + path, params: params || {}});
        },

        onTouchStart(e) {
            const focusedElement = document.activeElement;
            if (focusedElement) {
                focusedElement.blur();
            }
        },

        onScroll(e) {
            this.operateVisible = false
        },

        modalPercent(item) {
            if (this.operateVisible) {
                return
            }
            let content = `<p><strong>${this.$L('总进度')}</strong></p>`
            content += `<p>${this.$L('总数量')}: ${item.task_num}</p>`
            content += `<p>${this.$L('已完成')}: ${item.task_complete}</p>`
            content += `<p style="margin-top:12px"><strong>${this.$L('我的任务')}</strong></p>`
            content += `<p>${this.$L('总数量')}: ${item.task_my_num}</p>`
            content += `<p>${this.$L('已完成')}: ${item.task_my_complete}</p>`
            $A.modalInfo({
                language: false,
                title: `${item.name} ${this.$L('项目进度')}`,
                content,
            });
        },

        handleDragTip() {
            $A.modalAlert("请按住图标进行拖动排序")
        },

        handleLongpress(event) {
            if (event.target.classList.contains('item-sort')) {
                return; // 不处理排序手柄的长按事件
            }
            const {type, data, element} = this.longpressData;
            this.$store.commit("longpress/clear")
            //
            if (type !== 'projectList') {
                return
            }
            const projectItem = this.projectLists.find(item => item.id == data.projectId)
            if (!projectItem) {
                return
            }
            this.operateVisible = false;
            this.operateItem = $A.isJson(projectItem) ? projectItem : {};
            requestAnimationFrame(() => {
                const rect = element.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX}px`,
                    top: `${rect.top}px`,
                    height: `${rect.height}px`,
                }
                this.operateVisible = true;
            })
        },

        handleOperation({currentTarget}) {
            this.$store.commit("longpress/set", {
                type: 'projectList',
                data: {
                    projectId: $A.getAttr(currentTarget, 'data-id')
                },
                element: currentTarget
            })
        },

        handleTopClick() {
            this.$store.dispatch("call", {
                url: 'project/top',
                data: {
                    project_id: this.operateItem.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveProject", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        handleChatClick() {
            this.$store.dispatch("openDialog", this.operateItem.dialog_id).catch(({msg}) => {
                $A.modalError(msg || this.$L('打开会话失败'))
            })
        }
    }
}
</script>
