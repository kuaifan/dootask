<template>
    <div class="project-menu">
        <PageTitle :title="$L('项目')"/>
        <div class="list-search">
            <div class="search-wrapper">
                <Input v-model="projectKeyValue" type="text" :placeholder="$L(loadProjects ? '更新中...' : '搜索项目')" clearable>
                    <div class="search-pre" slot="prefix">
                        <Loading v-if="loadProjects"/>
                        <Icon v-else type="ios-search" />
                    </div>
                </Input>
            </div>
        </div>
        <div ref="menuProject" class="menu-project" >
            <ul v-if="projectLists.length > 0">
                <li
                    v-for="(item, key) in projectLists"
                    :ref="`project_${item.id}`"
                    :key="key"
                    :class="classNameProject(item)"
                    :data-id="item.id"
                    @click="toggleRoute('project', {projectId: item.id})"
                    v-longpress="handleLongpress">
                    <div class="project-h1">
                        <em @click.stop="toggleOpenMenu(item.id)"></em>
                        <div class="title">{{item.name}}</div>
                        <div v-if="item.top_at" class="icon-top"></div>
                        <div v-if="item.task_my_num - item.task_my_complete > 0" class="num">{{item.task_my_num - item.task_my_complete}}</div>
                    </div>
                    <div class="project-h2">
                        <p>
                            <em>{{$L('我的')}}:</em>
                            <span>{{item.task_my_complete}}/{{item.task_my_num}}</span>
                            <Progress :percent="item.task_my_percent" :stroke-width="6" />
                        </p>
                        <p>
                            <em>{{$L('全部')}}:</em>
                            <span>{{item.task_complete}}/{{item.task_num}}</span>
                            <Progress :percent="item.task_percent" :stroke-width="6" />
                        </p>
                    </div>
                </li>
                <li v-if="projectKeyLoading > 0" class="loading"><Loading/></li>
            </ul>
            <div v-else>
                {{$L(projectKeyValue ? `没有与"${projectKeyValue}"相关的项目` : `没有任何项目`)}}
            </div>
        </div>

        <div class="operate-position" :style="operateStyles" v-show="operateVisible">
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
                </DropdownMenu>
            </Dropdown>
        </div>

    </div>
</template>

<script>
import {mapState} from "vuex";
import longpress from "../../../directives/longpress";

export default {
    name: "ProjectLists",
    directives: {longpress},
    props: {
        projectId: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            openMenu: {},

            projectKeyValue: '',
            projectKeyLoading: 0,
            projectSearchShow: false,

            operateStyles: {},
            operateVisible: false,
            operateItem: {},
        }
    },

    computed: {
        ...mapState(['cacheProjects', 'loadProjects']),

        routeName() {
            return this.$route.name
        },

        projectLists() {
            const {projectKeyValue, cacheProjects} = this;
            const data = $A.cloneJSON(cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            });
            if (projectKeyValue) {
                return data.filter(item => $A.strExists(`${item.name} ${item.desc}`, projectKeyValue));
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

        'cacheProjects.length': {
            handler() {
                this.$nextTick(_ => {
                    const menuProject = this.$refs.menuProject
                    const lastEl = $A.last($A.getObject(menuProject, 'children.0.children'))
                    if (lastEl) {
                        const lastRect = lastEl.getBoundingClientRect()
                        const menuRect = menuProject.getBoundingClientRect()
                        if (lastRect.top > menuRect.top + menuRect.height) {
                            this.projectSearchShow = true
                            return
                        }
                    }
                    this.projectSearchShow = false
                })
            },
            immediate: true
        },
    },

    methods: {

        handleLongpress(event, el) {
            const projectId = $A.getAttr(el, 'data-id')
            const projectItem = this.projectLists.find(item => item.id == projectId)
            if (!projectItem) {
                return
            }
            this.operateVisible = false;
            this.operateItem = $A.isJson(projectItem) ? projectItem : {};
            this.$nextTick(() => {
                const projectRect = el.getBoundingClientRect();
                const wrapRect = this.$refs.menuProject.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX - wrapRect.left}px`,
                    top: `${projectRect.top + this.windowScrollY}px`,
                    height: projectRect.height + 'px',
                }
                this.operateVisible = true;
            })
        },

        classNameProject(item) {
            return {
                "active": this.routeName === 'manage-project' && (this.projectId || this.$route.params.projectId) == item.id,
                "open-menu": this.openMenu[item.id] === true,
                "operate": item.id == this.operateItem.id && this.operateVisible
            };
        },

        toggleOpenMenu(id) {
            this.$set(this.openMenu, id, !this.openMenu[id])
        },

        async toggleRoute(path, params) {
            this.showMobileMenu = false;
            let location = {name: 'manage-' + path, params: params || {}};
            let fileFolderId = await $A.IDBInt("fileFolderId");
            if (path === 'file' && fileFolderId > 0) {
                location.params.folderId = fileFolderId
            }
            this.goForward(location);
        },

        searchProject() {
            setTimeout(() => {
                this.projectKeyLoading++;
            }, 1000)
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.projectKeyValue
                }
            }).finally(_ => {
                this.projectKeyLoading--;
            });
        },

        handleTopClick() {
            this.$store.dispatch("call", {
                url: 'project/top',
                data: {
                    project_id: this.operateItem.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveProject", data);
                this.$nextTick(() => {
                    const active = this.$refs.menuProject.querySelector(".active")
                    if (active) {
                        $A.scrollIntoViewIfNeeded(active);
                    }
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        }

    }
}
</script>
