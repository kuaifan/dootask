<template>
    <div class="task-move">

        <Cascader
        v-model="cascader"
        :data="cascaderData"
        :clearable="false"
        :placeholder="$L('请选择项目')"
        :load-data="cascaderLoadData"
        @on-visible-change="cascaderShow=!cascaderShow"
        filterable/>

        <div class="task-move-content">
            <div class="task-move-content-old">
                <div class="task-move-title">{{ $L('移动前') }}</div>
                <div class="task-move-row">
                    <span class="label">{{$L('状态')}}:</span>
                    <div v-if="task.flow_item_name" class="flow">
                        <span :class="task.flow_item_status">{{task.flow_item_name}}</span>
                    </div>
                </div>
                <div class="task-move-row" :class="{'not-flex': windowPortrait}">
                    <span class="label">{{$L('负责人')}}:</span>
                    <UserSelect
                        v-model="ownerUserids"
                        class="item-content user"
                        :avatar-size="28"
                        :project-id="task.project_id"
                        :add-icon="false"
                        disabled/>
                </div>
                <div class="task-move-row" :class="{'not-flex': windowPortrait}">
                    <span class="label">{{$L('协助人')}}:</span>
                    <UserSelect
                        class="item-content user"
                        v-model="assistUserids"
                        :avatar-size="28"
                        :project-id="task.project_id"
                        :add-icon="false"
                        disabled/>
                </div>
            </div>
            <div class="task-move-content-new">
                <div class="task-move-title">{{ $L('移动后') }}</div>
                <div class="task-move-row">
                    <span class="label">{{$L('状态')}}:</span>
                    <TaskMenu
                        :ref="`taskMenu_${task.id}`"
                        :task="tasks"
                        :project-id="cascader[0]"
                        :color-show="false"
                        :operation-show="false"
                        :load-status="task.loading === true"
                        @on-update="onStatusUpdate"
                    />
                    <div v-if="updateData.flow.flow_item_name" class="flow">
                        <span :class="updateData.flow.flow_item_status" @click.stop="openMenu($event, tasks)">{{updateData.flow.flow_item_name}}</span>
                    </div>
                </div>
                <div class="task-move-row" :class="{'not-flex': windowPortrait}">
                    <span class="label">{{$L('负责人')}}:</span>
                    <div>
                        <UserSelect
                            class="item-content user"
                            v-model="updateData.owner_userids"
                            :multiple-max="10"
                            :avatar-size="28"
                            :project-id="cascader[0]"
                            :add-icon="false"/>
                    </div>
                </div>
                <div class="task-move-row" :class="{'not-flex': windowPortrait}">
                    <span class="label">{{$L('协助人')}}:</span>
                    <div>
                        <UserSelect
                            class="item-content user"
                            v-model="updateData.assist_userids"
                            :multiple-max="10"
                            :avatar-size="28"
                            :project-id="cascader[0]"
                            :add-icon="false"/>
                    </div>
                </div>
            </div>
        </div>

        <div class="ivu-modal-footer">
            <div class="adaption">
                <Button type="default" @click="close">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="onConfirm">{{$L('确定')}}</Button>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TaskMenu from "./TaskMenu";
import UserSelect from "../../../components/UserSelect.vue";

export default {
    name: "TaskMove",
    components: {
        TaskMenu,
        UserSelect,
    },
    props: {
        value: {
            type: Boolean,
            default: false
        },
        task: {
            type: Object,
            default: false
        },

    },
    data() {
        return {
            tasks: {},
            cascader: [],
            cascaderShow: false,
            cascaderData: [],
            cascaderValue: '',
            cascaderLoading: 0,
            cascaderAlready: [],

            loadIng: 0,

            flowItemId: 0,
            ownerUserids: [],
            assistUserids: [],
            updateData:{
                flow: {},
                owner_userids: [],
                assist_userids: []
            }

        }
    },

    async mounted() {
        this.initData();
    },

    computed: {
        ...mapState(['cacheProjects', 'cacheColumns']),
    },

    watch: {
        cascader(val){
            this.tasks.flow_item_id = this.flowItemId;
            if(val[0] != this.task.project_id){
                this.updateData.flow.flow_item_id = 0;
                this.updateData.flow.flow_item_name = '';
                this.updateData.flow.flow_item_status = '';
            }else{
                this.updateData.flow.flow_item_id = this.flowItemId;
                this.updateData.flow.flow_item_name = this.task.flow_item_name;
                this.updateData.flow.flow_item_status = this.task.flow_item_status;
            }
            //
            const projectUserIds = this.cacheProjects.find(project => project.id == val[0])?.project_user?.map(h=>{
                return h.userid
            }) || [];
            //
            this.updateData.owner_userids = (this.task.task_user || []).filter(h=>{
                return h.owner && projectUserIds.indexOf(h.userid) !== -1
            }).sort((a, b) => {
                return a.id - b.id;
            }).map(h=>{
                return h.userid
            });
            //
            this.updateData.assist_userids = (this.task.task_user || []).filter(h=>{
                return !h.owner && projectUserIds.indexOf(h.userid) !== -1
            }).sort((a, b) => {
                return a.id - b.id;
            }).map(h=>{
                return h.userid
            });
        },
    },

    methods: {
        /**
         * 初始化数据
         */
        initData() {
            this.flowItemId =  this.task.flow_item_id;
            this.cascader = [this.task.project_id, this.task.column_id];
            this.ownerUserids = (this.task.task_user || []).filter(h=>{
                return h.owner
            }).sort((a, b) => {
                return a.id - b.id;
            }).map(h=>{
                return h.userid
            });
            this.assistUserids = (this.task.task_user || []).filter(h=>{
                return !h.owner
            }).sort((a, b) => {
                return a.id - b.id;
            }).map(h=>{
                return h.userid
            });
            this.tasks = JSON.parse(JSON.stringify(this.task));
            //
            const data = $A.cloneJSON(this.cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            });
            this.cascaderData = data.map(project => {
                const children = this.cacheColumns.filter(({project_id}) => project_id == project.id).map(column => {
                    return {
                        value: column.id,
                        label: column.name
                    }
                });
                const data = {
                    value: project.id,
                    label: project.name,
                    children,
                };
                if (children.length == 0) {
                    data.loading = false;
                }
                return data
            });
        },

        cascaderLoadData(item, callback) {
            item.loading = true;
            this.$store.dispatch("getColumns", item.value).then((data) => {
                item.children = data.map(column => {
                    return {
                        value: column.id,
                        label: column.name
                    }
                });
                item.loading = false;
                callback();
            }).catch(() => {
                item.loading = false;
                callback();
            });
        },

        async onConfirm() {
            if (this.task.project_id == this.cascader[0] && this.task.column_id == this.cascader[1]) {
                $A.messageError("未变更移动项");
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: "project/task/move",
                data: {
                    task_id: this.task.id,
                    project_id: this.cascader[0],
                    column_id: this.cascader[1],
                    flow_item_id: this.updateData.flow.flow_item_id || 0,
                    complete_at: this.updateData.flow.complete_at || '',
                    owner: this.updateData.owner_userids,
                    assist: this.updateData.assist_userids,
                }
            }).then(({data,msg}) => {
                this.loadIng--;
                data.column_name = "";
                data.project_name = "";
                this.$store.dispatch("saveTask", data);
                $A.messageSuccess(msg);
                this.close()
            }).catch(({msg, ret}) => {
                this.loadIng--;
                if (ret == 102) {
                    $A.messageError("请选择移动后状态");
                } else {
                    $A.modalError(msg);
                }
            })
        },

        close() {
            this.$emit("input", !this.value)
        },

        openMenu(event, task) {
            const el = this.$refs[`taskMenu_${task.id}`];
            el && el.handleClick(event)
        },

        onStatusUpdate(val) {
            if (val.complete_at && !val.flow_item_id) {
                val.flow_item_name = this.$L('已完成');
            }
            this.tasks.flow_item_id = val.flow_item_id;
            this.updateData.flow = val
        }
    }
}
</script>
