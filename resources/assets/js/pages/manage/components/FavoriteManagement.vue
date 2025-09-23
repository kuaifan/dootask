<template>
    <div class="favorite-management">
        <div class="management-title">
            {{$L('我的收藏')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
            </div>
        </div>
        <div class="search-container lr">
            <ul>
                <li>
                    <div class="search-label">
                        {{$L("收藏类型")}}
                    </div>
                    <div class="search-content">
                        <Select v-model="keys.type" :placeholder="$L('全部类型')">
                            <Option value="">{{$L('全部类型')}}</Option>
                            <Option value="task">{{$L('任务')}}</Option>
                            <Option value="project">{{$L('项目')}}</Option>
                            <Option value="file">{{$L('文件')}}</Option>
                        </Select>
                    </div>
                </li>
                <li>
                    <div class="search-label">
                        {{$L("名称")}}
                    </div>
                    <div class="search-content">
                        <Input v-model="keys.name" clearable :placeholder="$L('搜索收藏名称')"/>
                    </div>
                </li>
                <li class="search-button">
                    <SearchButton
                        :loading="loadIng > 0"
                        :filtering="keyIs"
                        placement="right"
                        @search="onSearch"
                        @refresh="getLists"
                        @cancelFilter="keyIs=false"/>
                </li>
            </ul>
        </div>
        <div class="table-page-box">
            <Table
                :columns="columns"
                :data="list"
                :loading="loadIng > 0"
                :no-data-text="$L(noText)"
                stripe/>
            <Page
                :total="total"
                :current="page"
                :page-size="pageSize"
                :disabled="loadIng > 0"
                :simple="windowPortrait"
                :page-size-opts="[10,20,30,50,100]"
                show-elevator
                show-sizer
                show-total
                @on-change="setPage"
                @on-page-size-change="setPageSize"/>
        </div>
    </div>
</template>

<script>
import SearchButton from "../../../components/SearchButton.vue";

export default {
    name: "FavoriteManagement",
    components: {SearchButton},
    data() {
        return {
            loadIng: 0,

            keys: {},
            keyIs: false,

            columns: [
                {
                    title: this.$L('类型'),
                    key: 'type',
                    width: 80,
                    render: (h, {row}) => {
                        const typeMap = {
                            'task': this.$L('任务'),
                            'project': this.$L('项目'),
                            'file': this.$L('文件')
                        };
                        const color = {
                            'task': 'primary',
                            'project': 'success',
                            'file': 'warning'
                        };
                        return h('Tag', {
                            props: {
                                color: color[row.type] || 'default'
                            }
                        }, typeMap[row.type] || row.type);
                    }
                },
                {
                    title: this.$L('名称'),
                    key: 'name',
                    minWidth: 150,
                    render: (h, {row}) => {
                        return h('div', {
                            class: 'favorite-name',
                            on: {
                                click: () => this.openFavorite(row)
                            }
                        }, [
                            h('AutoTip', row.name)
                        ]);
                    }
                },
                {
                    title: this.$L('所属项目'),
                    key: 'project_name',
                    minWidth: 120,
                    render: (h, {row}) => {
                        return row.project_name ? h('AutoTip', row.project_name) : h('span', '-');
                    }
                },
                {
                    title: this.$L('状态'),
                    minWidth: 80,
                    render: (h, {row}) => {
                        if (row.type === 'task') {
                            // 任务使用工作流状态显示
                            if (row.flow_item_name) {
                                return h('span', {
                                    class: `flow-name ${row.flow_item_status}`,
                                    style: this.$A.generateColorVarStyle(row.flow_item_color, [10], 'flow-item-custom-color')
                                }, row.flow_item_name);
                            } else {
                                // 没有工作流状态时的后备显示
                                if (row.complete_at) {
                                    return h('span', {
                                        class: 'favorite-status-tag favorite-status-success'
                                    }, this.$L('已完成'));
                                } else {
                                    return h('span', {
                                        class: 'favorite-status-tag favorite-status-processing'
                                    }, this.$L('进行中'));
                                }
                            }
                        } else if (row.type === 'project') {
                            if (row.archived_at) {
                                return h('span', {
                                    class: 'favorite-status-tag favorite-status-error'
                                }, this.$L('已归档'));
                            } else {
                                return h('span', {
                                    class: 'favorite-status-tag favorite-status-success'
                                }, this.$L('正常'));
                            }
                        }
                        return h('span', '-');
                    }
                },
                {
                    title: this.$L('收藏时间'),
                    key: 'favorited_at',
                    width: 168,
                },
                {
                    title: this.$L('操作'),
                    align: 'center',
                    width: 100,
                    render: (h, params) => {
                        const vNode = [
                            h('Poptip', {
                                props: {
                                    title: this.$L(`确定要取消收藏"${params.row.name}"吗？`),
                                    confirm: true,
                                    transfer: true,
                                    placement: 'left',
                                    okText: this.$L('确定'),
                                    cancelText: this.$L('取消'),
                                },
                                style: {
                                    fontSize: '13px',
                                    cursor: 'pointer',
                                    color: '#f00',
                                },
                                on: {
                                    'on-ok': () => {
                                        this.removeFavorite(params.row)
                                    }
                                },
                            }, this.$L('取消收藏'))
                        ];
                        return h('TableAction', {
                            props: {
                                column: params.column
                            }
                        }, vNode);
                    },
                }
            ],
            list: [],
            allData: [], // 存储所有数据用于筛选

            page: 1,
            pageSize: 20,
            total: 0,
            noText: ''
        }
    },
    mounted() {
        this.getLists();
    },
    watch: {
        keyIs(v) {
            if (!v) {
                this.keys = {}
                this.setPage(1)
            }
        }
    },
    methods: {
        onSearch() {
            this.page = 1;
            this.filterData();
        },

        getLists() {
            this.loadIng++;
            this.keyIs = $A.objImplode(this.keys) != "";
            
            this.$store.dispatch("call", {
                url: 'users/favorites',
                data: {
                    type: this.keys.type || '',
                    page: this.page,
                    pagesize: this.pageSize,
                },
            }).then(({data}) => {
                // 处理返回的数据，将三种类型合并到一个列表中
                this.allData = [];
                
                // 处理任务收藏
                if (data.data.tasks) {
                    data.data.tasks.forEach(task => {
                        this.allData.push({
                            id: task.id,
                            type: 'task',
                            name: task.name,
                            project_id: task.project_id,
                            project_name: task.project_name,
                            complete_at: task.complete_at,
                            flow_item_id: task.flow_item_id,
                            flow_item_name: task.flow_item_name,
                            flow_item_status: task.flow_item_status,
                            flow_item_color: task.flow_item_color,
                            favorited_at: task.favorited_at,
                        });
                    });
                }
                
                // 处理项目收藏
                if (data.data.projects) {
                    data.data.projects.forEach(project => {
                        this.allData.push({
                            id: project.id,
                            type: 'project',
                            name: project.name,
                            desc: project.desc,
                            archived_at: project.archived_at,
                            favorited_at: project.favorited_at,
                        });
                    });
                }
                
                // 处理文件收藏
                if (data.data.files) {
                    data.data.files.forEach(file => {
                        this.allData.push({
                            id: file.id,
                            type: 'file',
                            name: file.name,
                            ext: file.ext,
                            size: file.size,
                            pid: file.pid,
                            favorited_at: file.favorited_at,
                        });
                    });
                }
                
                this.total = data.total || this.allData.length;
                this.filterData();
                this.noText = '没有相关的收藏';
            }).catch(() => {
                this.noText = '数据加载失败';
            }).finally(_ => {
                this.loadIng--;
            })
        },

        filterData() {
            let filteredData = this.allData;
            
            // 按名称筛选
            if (this.keys.name) {
                filteredData = filteredData.filter(item => {
                    return item.name && item.name.toLowerCase().includes(this.keys.name.toLowerCase());
                });
            }
            
            this.list = filteredData;
        },

        setPage(page) {
            this.page = page;
            this.getLists();
        },

        setPageSize(pageSize) {
            this.page = 1;
            this.pageSize = pageSize;
            this.getLists();
        },

        openFavorite(item) {
            switch (item.type) {
                case 'task':
                    this.$store.dispatch("openTask", {id: item.id});
                    break;
                case 'project':
                    this.$router.push({
                        name: 'manage-project',
                        params: {projectId: item.id}
                    });
                    this.$emit('on-close');
                    break;
                case 'file':
                    this.$router.push({
                        name: 'manage-file', 
                        params: {
                            folderId: item.pid || 0, 
                            fileId: null, 
                            shakeId: item.id
                        }
                    });
                    this.$store.state.fileShakeId = item.id;
                    setTimeout(() => {
                        this.$store.state.fileShakeId = 0;
                    }, 600);
                    this.$emit('on-close');
                    break;
            }
        },

        removeFavorite(item) {
            this.$store.dispatch("toggleFavorite", {
                type: item.type,
                id: item.id
            }).then(() => {
                $A.messageSuccess('取消收藏成功');
                this.getLists();
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        }
    }
}
</script>
