<template>
    <div class="project-list">
        <PageTitle :title="$L('项目')"/>
        <div class="list-search">
            <div class="search-wrapper">
                <Input v-model="projectKeyValue" :placeholder="$L(loadProjects ? '更新中...' : '搜索项目')" clearable>
                    <div class="search-pre" slot="prefix">
                        <Loading v-if="loadProjects"/>
                        <Icon v-else type="ios-search" />
                    </div>
                </Input>
            </div>
        </div>
        <ul>
            <template v-if="projectLists.length === 0">
                <li v-if="projectKeyLoading > 0" class="loading"><Loading/></li>
                <li v-else class="nothing">
                    {{$L(projectKeyValue ? `没有任何与"${projectKeyValue}"相关的项目` : `没有任何项目`)}}
                </li>
            </template>
            <li
                v-for="(item, key) in projectLists"
                :key="key"
                @click="toggleRoute('project', {projectId: item.id})">
                <div class="project-item">
                    <div class="item-left">
                        <div class="project-h1">
                            <span>{{item.name}}</span>
                            <em v-if="item.task_my_num > 0">{{item.task_my_num}}</em>
                        </div>
                        <div class="project-h2">
                            {{item.desc}}
                        </div>
                    </div>
                    <div v-if="item.task_num > 0" class="item-right" @click.stop="modalPercent(item)">
                        <EProgress
                            type="circle"
                            color="#8bcf70"
                            :percentage="item.task_percent"
                            :status="item.task_percent >= 100 ? 'success' : ''"
                            :width="60"
                            :stroke-width="5"/>
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
                return data.filter(item => $A.strExists(`${item.name}||${item.desc}`, projectKeyValue));
            }
            /*return data.map(item => {
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
            });*/
            return data;
        },
    },

    watch: {
        projectKeyValue(val) {
            if (val == '') {
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
    },

    methods: {
        searchProject() {
            this.projectKeyLoading++;
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.projectKeyValue
                },
                hideLoad: true,
            }).finally(_ => {
                this.projectKeyLoading--;
            });
        },

        toggleRoute(path, params) {
            let location = {name: 'manage-' + path, params: params || {}};
            this.goForward(location);
        },

        modalPercent(item) {
            let content = `<p><strong>${this.$L('总进度')}</strong></p>`
            content += `<p>${this.$L('总数量')}: ${item.task_num}</p>`
            content += `<p>${this.$L('已完成')}: ${item.task_complete}</p>`
            content += `<p style="margin-top:12px"><strong>${this.$L('我的任务')}</strong></p>`
            content += `<p>${this.$L('总数量')}: ${item.task_my_num}</p>`
            content += `<p>${this.$L('已完成')}: ${item.task_my_complete}</p>`
            $A.modalInfo({
                language: false,
                title: this.$L(`${item.name} 项目进度`),
                content,
            });
        }
    }
}
</script>
