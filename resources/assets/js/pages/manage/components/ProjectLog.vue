<template>
    <div :class="['project-log', taskId == 0 ? 'is-drawer' : '']">
        <div class="log-title">{{$L('项目动态')}}</div>
        <ul class="logs-activity">
            <li v-for="itemA in lists">
                <div class="logs-date">{{logDate(itemA)}}</div>
                <div class="logs-section">
                    <Timeline>
                        <TimelineItem v-for="(itemB, index) in itemA.lists" :key="index">
                            <div slot="dot" class="logs-dot">
                                <UserAvatar v-if="itemB.userid" :userid="itemB.userid" :size="18" showName/>
                                <div v-else class="avatar-wrapper common-avatar">
                                    <EAvatar :size="18">A</EAvatar>
                                    <div class="avatar-name auto">{{$L('系统')}}</div>
                                </div>
                            </div>
                            <template v-for="log in itemB.lists">
                                <div class="log-summary">
                                    <ProjectLogDetail :render="logDetail" :item="log"/>
                                    <span v-if="operationList(log).length > 0" class="log-operation">
                                        <Button v-for="(op, oi) in operationList(log)" :key="oi" size="small" @click="onOperation(op)">{{op.button}}</Button>
                                    </span>
                                    <span class="log-time">{{log.time.ymd}} {{log.time.segment}} {{log.time.hi}}</span>
                                </div>
                                <div v-if="log.project_task" class="log-task">
                                    <em @click="openTask(log.project_task)">{{$L('关联任务')}}: {{log.project_task.name}}</em>
                                </div>
                            </template>
                        </TimelineItem>
                    </Timeline>
                </div>
            </li>
            <li v-if="loadIng > 0 && showLoad" class="logs-loading"><Loading/></li>
            <li v-else-if="hasMorePages" class="logs-more" @click="getMore">{{$L('加载更多')}}</li>
            <li v-else-if="totalNum == 0" class="logs-none" @click="getLists(true)">{{$L('没有任何动态')}}</li>
        </ul>
    </div>
</template>

<script>
import ProjectLogDetail from "./ProjectLogDetail";

export default {
    name: "ProjectLog",
    components: {ProjectLogDetail},
    props: {
        projectId: {
            type: Number,
            default: 0
        },
        taskId: {
            type: Number,
            default: 0
        },
        showLoad: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            loadIng: 0,

            lists: [],
            listPage: 1,
            listPageSize: 20,
            hasMorePages: false,
            totalNum: -1,
        }
    },

    mounted() {
        this.getLists(true);
    },

    computed: {

    },

    watch: {
        projectId() {
            this.lists = [];
            this.getLists(true);
        },
        taskId() {
            this.lists = [];
            this.getLists(true);
        },
        loadIng(num) {
            this.$emit("on-load-change", num > 0)
        }
    },

    methods: {
        logDate(itemA) {
            let md = $A.formatDate("m-d");
            return md == itemA.ymd ? (itemA.ymd + ' ' + this.$L('今天')) : itemA.key;
        },

        getLists(resetLoad) {
            if (resetLoad === true) {
                this.listPage = 1;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/log/lists',
                data: {
                    project_id: this.projectId,
                    task_id: this.taskId,
                    page: Math.max(this.listPage, 1),
                    pagesize: Math.max($A.runNum(this.listPageSize), 10),
                }
            }).then(({data}) => {
                this.loadIng--;
                if (resetLoad === true) {
                    this.lists = [];
                }
                data.data.some(log => {
                    let tmpTime = log.time;
                    let tmpKey = tmpTime.ymd + " " + tmpTime.week;
                    let itemA = this.lists.find(({key}) => key == tmpKey)
                    if (itemA) {
                        let itemB = itemA.lists.find(({userid}) => userid == log.userid)
                        if (itemB) {
                            itemB.lists.push(log)
                        } else {
                            itemA.lists.push({
                                userid: log.userid,
                                lists: [log]
                            })
                        }
                    } else {
                        this.lists.push({
                            key: tmpKey,
                            ymd: log.ymd,
                            lists: [{
                                userid: log.userid,
                                lists: [log]
                            }]
                        })
                    }
                })
                this.hasMorePages = data.current_page < data.last_page;
                this.totalNum = data.total;
            }).catch(() => {
                this.loadIng--;
                this.lists = [];
                this.hasMorePages = false;
                this.totalNum = 0;
            });
        },

        getMore() {
            if (!this.hasMorePages) {
                return;
            }
            this.hasMorePages = false;
            this.listPage++;
            this.getLists();
        },

        /**
         * 日志详情
         * @param h
         * @param detail
         * @param record
         * @returns {*[]}
         */
        logDetail(h, {detail, record}) {
            let vNode = [h('span', this.$L(detail))];
            if ($A.isJson(record)) {
                if ($A.isArray(record.change)) {
                    let [before, now] = record.change
                    vNode.push(h('span', ': '))
                    if (before && before != now) {
                        vNode.push(h('span', {class:'change-value'}, `${before || '-'}`))
                        vNode.push(h('span', ' => '))
                        vNode.push(h('span', {class:'change-value'}, `${now || '-'}`))
                    } else {
                        vNode.push(h('span', {class:'change-value'}, now || '-'))
                    }
                }
                if (record.userid) {
                    let userids = $A.isArray(record.userid) ? record.userid : [record.userid]
                    let userNode = [];
                    userids.some(userid => {
                        userNode.push(h('UserAvatar', {
                            props: {
                                size: 18,
                                userid
                            }
                        }))
                    })
                    if (userNode.length > 0) {
                        vNode.push(h('div', {
                            class: 'detail-user'
                        }, [
                            h('div', {
                                class: 'detail-user-wrap'
                            }, userNode)
                        ]))
                    }
                }
            }
            return h('span', {
                class: 'log-text'
            }, vNode)
        },

        /**
         * 可操作事件
         * @param id
         * @param record
         * @returns {*[]}
         */
        operationList({id, record}) {
            let list = [];
            if (!$A.isJson(record)) {
                return list
            }
            if (this.taskId > 0 && $A.isJson(record.flow)) {
                let name = $A.getMiddle(record.flow.flow_item_name, "|")
                if (name) {
                    list.push({
                        id,
                        button: '重置',
                        content: `确定重置为【${name}】吗？`,
                    })
                }
            }
            return list;
        },

        /**
         * 执行操作
         * @param item
         */
        onOperation(item) {
            $A.modalConfirm({
                content: item.content,
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'project/task/resetfromlog',
                        data: {
                            id: item.id
                        }
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                        this.$store.dispatch("saveTask", data);
                        this.getLists(true);
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        openTask(task) {
            this.$store.dispatch("openTask", task)
        }
    }
}
</script>
