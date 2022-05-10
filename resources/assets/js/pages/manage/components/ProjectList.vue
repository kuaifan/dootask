<template>
    <div class="project-list">
        <PageTitle :title="$L('项目')"/>
        <div class="list-search">
            <div class="search-wrapper">
                <Input prefix="ios-search" v-model="projectKeyValue" :placeholder="$L('搜索...')" clearable />
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
                    <Progress :percent="item.task_percent" :stroke-width="6" />
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

    mounted() {

    },

    destroyed() {

    },

    computed: {
        ...mapState([
            'cacheProjects',
        ]),

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
            return data;
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
    }
}
</script>
