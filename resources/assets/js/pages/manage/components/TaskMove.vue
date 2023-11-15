<template>
    <div class="task-add">
        <Cascader
            v-model="cascader"
            :data="cascaderData"
            :clearable="false"
            :placeholder="$L('请选择项目')"
            :load-data="cascaderLoadData"
            @on-input-change="cascaderInputChange"
            @on-visible-change="cascaderShow=!cascaderShow"
            filterable/>
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

export default {
    name: "TaskMove",
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
            cascader: [],
            cascaderShow: false,
            cascaderData: [],
            cascaderValue: '',
            cascaderLoading: 0,
            cascaderAlready: [],

            loadIng: 0,
            beforeClose: [],
        }
    },

    async mounted() {
        this.initCascaderData();
    },

    beforeDestroy() {
        this.beforeClose.some(func => {
            typeof func === "function" && func()
        })
        this.beforeClose = [];
    },

    computed: {
        ...mapState(['cacheProjects', 'cacheColumns']),
    },

    watch: {
        task: {
            handler: function (val) {
                this.cascader = [val.project_id, val.column_id];
            },
            deep: true,
            immediate: true
        },
    },

    methods: {
        /**
         * 初始化级联数据
         */
        initCascaderData() {
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

        cascaderInputChange(key) {
            this.cascaderValue = key || "";
            //
            if (this.cascaderAlready[this.cascaderValue] === true) {
                return;
            }
            this.cascaderAlready[this.cascaderValue] = true;
            //
            setTimeout(() => {
                this.cascaderLoading++;
            }, 1000)
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.cascaderValue,
                },
                getcolumn: 'yes'
            }).then(() => {
                this.cascaderLoading--;
                this.initCascaderData();
            }).catch(() => {
                this.cascaderLoading--;
            });
        },


        async onConfirm() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: "project/task/move",
                data: {
                    task_id: this.task.id,
                    project_id: this.cascader[0],
                    column_id: this.cascader[1],
                }
            }).then(({msg}) => {
                this.loadIng--;
                this.$store.dispatch("saveTask", {
                    id: this.task.id,
                    project_id: this.cascader[0],
                    column_id: this.cascader[1],
                });
                $A.messageSuccess(msg);
                this.close()
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError(msg);
            })
        },

        close() {
            this.$emit("input", !this.value)
        },
    }
}
</script>
