<template>
    <div class="project-list">
        <PageTitle :title="$L('项目')"/>
        <div class="list-search">
            <div class="search-wrapper">
                <Input v-model="projectKeyValue" :placeholder="$L(loadProjects || projectKeyLoading ? '读取中...' : '搜索...')" clearable>
                    <div class="search-pre" slot="prefix">
                        <Loading v-if="loadProjects || projectKeyLoading"/>
                        <Icon v-else type="ios-search" />
                    </div>
                </Input>
            </div>
        </div>
        <ul>
            <li
                v-for="(item, key) in projectLists"
                :key="key"
                @click="toggleRoute('project', {projectId: item.id})">
                <div class="project-h1">
                    {{item.name}}
                </div>
                <div class="project-h2">
                    {{item.desc}}
                </div>
                <div class="project-percent">
                    <Progress :percent="item.task_my_percent" :stroke-width="5" hide-info />
                    <div class="percent-info" @click.stop="modalPercent(item)">{{item.task_my_complete}}<em>/{{item.task_my_num}}</em></div>
                </div>
                <div class="project-footer">
                    <div class="footer-percent" @click.stop="modalPercent(item)">{{item.task_complete}}<em>/{{item.task_num}}</em></div>
                    <div class="footer-user">
                        <UserAvatar v-for="(uid, ukey) in item.user_simple" :key="ukey" :userid="uid" :size="26" :borderWitdh="2"/>
                        <div v-if="item.user_count > 3" class="footer-user-more">{{item.user_count > 99 ? '99+' : `${item.user_count}+`}}</div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "ProjectList",
    data() {
        return {
            projectKeyValue: '',
            projectKeyAlready: {},
            projectKeyLoading: 0,
        }
    },

    computed: {
        ...mapState(['cacheProjects', 'loadProjects']),

        projectLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = $A.cloneJSON(cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            });
            if (projectKeyValue) {
                return data.filter(({name}) => name.toLowerCase().indexOf(projectKeyValue.toLowerCase()) > -1);
            }
            return data.map(item => {
                if (!$A.isArray(item.user_simple)) {
                    const arr = (item.user_simple || "").split("|");
                    if (arr.length > 1) {
                        item.user_count = arr[0];
                        item.user_simple = arr[1].split(",");
                    } else {
                        item.user_count = 0;
                        item.user_simple = [];
                    }
                }
                return item;
            });
        },
    },

    watch: {
        projectKeyValue(val) {
            if (val == '') {
                return;
            }
            setTimeout(() => {
                if (this.projectKeyValue == val) {
                    this.searchProject();
                }
            }, 600);
        },
    },

    methods: {
        searchProject() {
            if (this.projectKeyAlready[this.projectKeyValue] === true) {
                return;
            }
            this.projectKeyAlready[this.projectKeyValue] = true;
            //
            setTimeout(() => {
                this.projectKeyLoading++;
            }, 1000)
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.projectKeyValue
                }
            }).then(() => {
                this.projectKeyLoading--;
            }).catch(() => {
                this.projectKeyLoading--;
            });
        },

        toggleRoute(path, params) {
            let location = {name: 'manage-' + path, params: params || {}};
            this.goForward(location);
        },

        modalPercent(item) {
            $A.modalInfo({
                title: `${item.name} 项目进度`,
                content: `总进度：${item.task_complete}/${item.task_num}<br/>我的任务：${item.task_my_complete}/${item.task_my_num}`
            });
        }
    }
}
</script>
