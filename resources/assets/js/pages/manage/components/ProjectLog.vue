<template>
    <div :class="['project-log', taskId == 0 ? 'is-drawer' : '']">
        <div class="log-title">{{$L('项目动态')}}</div>
        <ul class="logs-activity">
            <li v-for="items in lists">
                <div class="logs-date">{{logDate(items)}}</div>
                <div class="logs-section">
                    <Timeline>
                        <TimelineItem v-for="(item, index) in items.lists" :key="index">
                            <div slot="dot" class="logs-dot">
                                <UserAvatar :userid="item.userid" :size="18"/>
                            </div>
                            <div class="log-summary">
                                <span class="log-creator">{{item.user.nickname}}</span>
                                <span class="log-text">{{$L(item.detail)}}</span>
                                <span class="log-time">{{item.time.ymd}} {{item.time.segment}} {{item.time.hi}}</span></div>
                        </TimelineItem>
                    </Timeline>
                </div>
            </li>
            <li v-if="loadIng > 0" class="logs-loading"><Loading/></li>
            <li v-else-if="hasMorePages" class="logs-more" @click="getMore">{{$L('加载更多')}}</li>
            <li v-else-if="totalNum == 0" class="logs-none" @click="getLists(true)">{{$L('没有任何动态')}}</li>
        </ul>
    </div>
</template>

<script>
export default {
    name: "ProjectLog",
    props: {
        projectId: {
            type: Number,
            default: 0
        },
        taskId: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            loadIng: 0,

            lists: {},
            listPage: 1,
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
            this.lists = {};
            this.getLists(true);
        },
        taskId() {
            this.lists = {};
            this.getLists(true);
        },
    },

    methods: {
        logDate(items) {
            let md = $A.formatDate("m-d");
            return md == items.ymd ? (items.ymd + ' ' + this.$L('今天')) : items.key;
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
                    pagesize: this.pagesize,
                }
            }).then(({data}) => {
                this.loadIng--;
                if (resetLoad === true) {
                    this.lists = {};
                }
                data.data.forEach((item) => {
                    let time = item.time;
                    let key = time.ymd + " " + time.week;
                    if (typeof this.lists[key] !== "object") {
                        this.$set(this.lists, key, {
                            key: key,
                            ymd: time.ymd,
                            lists: [],
                        });
                    }
                    this.lists[key].lists.push(item);
                });
                this.hasMorePages = data.current_page < data.last_page;
                this.totalNum = data.total;
            }).catch(() => {
                this.loadIng--;
                this.lists = {};
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
    }
}
</script>
