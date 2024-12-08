<template>
    <div class="robot-management">
        <div class="robot-management-search">
            <Input v-model="search" :placeholder="$L('请输入机器人名称')" clearable class="robot-management-search-input" />
            <Button type="primary" icon="ios-search" @click="onSearch">{{$L('搜索')}}</Button>
        </div>
        <div ref="list" class="robot-scrollbar" @scroll="handleScroll">
            <template v-if="list.length > 0">
                <div v-for="(item, key) in list" :key="key" class="robot-box">
                    <div class="robot-row">
                        <div class="robot-item robot-item-id">
                            <span class="robot-item-label">ID：</span>
                            <span class="robot-item-value">{{item.bot_id}}</span>
                        </div>
                        <Dropdown @on-click="handleDropdownClick($event, item)">
                            <a href="javascript:void(0)">
                                <Icon type="ios-more" size="20"></Icon>
                            </a>
                            <template #list>
                                <DropdownMenu>
                                    <DropdownItem name="view">{{ $L('查看机器人详情') }}</DropdownItem>
                                    <DropdownItem name="edit">{{ $L('编辑机器人') }}</DropdownItem>
                                    <DropdownItem name="delete">{{ $L('删除机器人') }}</DropdownItem>
                                    <DropdownItem name="session" divided>{{ $L('机器人会话') }}</DropdownItem>
                                    <DropdownItem name="token">{{ $L('重置机器人Token令牌') }}</DropdownItem>
                                </DropdownMenu>
                            </template>
                        </Dropdown>
                    </div>
                    <div class="robot-row">
                        <div class="robot-item">
                            <span class="robot-item-label">{{ $L('名称') }}：</span>
                            <span class="robot-item-value">{{item.name}}</span>
                        </div>
                    </div>
                    <div class="robot-row">
                        <div class="robot-item">
                            <span class="robot-item-label">{{ $L('Token') }}：</span>
                            <span class="robot-item-value">{{tokenFormat(item.token)}}</span>
                            <div v-if="item.token" class="robot-item-action" @click="tokenCopy(item)">
                                <Icon type="ios-copy" size="16" />
                            </div>
                        </div>
                    </div>
                    <div class="robot-row">
                        <div class="robot-item">
                            <div class="robot-item-label">{{ $L('Webhook地址') }}：</div>
                            <div class="robot-item-value">{{item.webhook_url || '-'}}</div>
                            <div v-if="item.webhook_url" class="robot-item-action" @click="webhookCopy(item)">
                                <Icon type="ios-copy" size="16" />
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <span v-else class="nothing">
                {{$L('没有任何机器人')}}
            </span>
        </div>

        <!-- 创建机器人 -->
        <RobotManageCreate 
            ref="create" 
            @update:createShow="createShow = $event" 
            @success="handleCreateSuccess"
        />

        <!-- 机器人信息 -->
        <RobotManageInfo 
            ref="info" 
            :id="infoId" 
            @update:infoShow="infoShow = $event"
        />

        <!-- 修改机器人 -->
        <RobotManageEdit 
            ref="edit" 
            :item="infoItem"
            @update:editShow="editShow = $event"
            @success="handleEditSuccess"
        />

        <!-- 删除机器人 -->
        <RobotManageDelete 
            ref="delete" 
            :id="infoId" 
            @update:deleteShow="deleteShow = $event"
            @success="handleDeleteSuccess"
        />

        <!-- 会话列表 -->
        <RobotManageSession 
            ref="session" 
            :id="infoId" 
            @update:sessionShow="sessionShow = $event"
        />

        <!-- API文档 -->
        <RobotManageApi 
            ref="api" 
            @update:apiShow="apiShow = $event"
        />
    </div>
</template>

<script>

import RobotManageCreate from './Create';
import RobotManageInfo from './Info';
import RobotManageDelete from './Delete';
import RobotManageEdit from './Edit';
import RobotManageSession from './Session';
import RobotManageApi from './Api';
export default {
    components: {
        RobotManageCreate, 
        RobotManageInfo, 
        RobotManageDelete,
        RobotManageEdit,
        RobotManageSession,
        RobotManageApi,
    },
    data() {
        return {
            loadIng: 0,
            list: [],
            search: '',
            infoId: 0,
            infoItem: {},
            // 创建机器人
            createShow: false,
            // 机器人信息
            infoShow: false,
            // 修改机器人
            editShow: false,
            // 删除机器人
            deleteShow: false,
            // 会话列表
            sessionShow: false,
            // 分页
            pagination: {
                page: 1,
                pageSize: 9,
                lastPage: 0,
            },
            // API文档
            apiShow: false,
        };
    },
    mounted() {
        this.initList()
    },
    watch: {
        createShow(val) {
            if (val) {
                this.$refs.create.createShow = val;
            } else {
                this.$emit('update:createShow', false);
            }
        },
        infoShow(val) {
            this.$refs.info.infoShow = val;
        },
        editShow(val) {
            this.$refs.edit.editShow = val;
        },
        deleteShow(val) {
            this.$refs.delete.deleteShow = val;
        },
        sessionShow(val) {
            this.$refs.session.sessionShow = val;
        },
        apiShow(val) {
            this.$refs.api.apiShow = val;
        }
    },
    methods: {
        // 初始化机器人列表
        // type: refresh, scroll
        initList(type = 'refresh') {
            if (type == 'refresh') {
                this.pagination.page = 1;
            } else if (type == 'scroll') {
                if (this.pagination.page >= this.pagination.lastPage) {
                    return;
                }
                this.pagination.page++;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'users/bot/list',
                data: {
                    search: this.search,
                    page: this.pagination.page,
                    pagesize: this.pagination.pageSize,
                },
            }).then(({data}) => {
                this.updateData(data, type);
                this.pagination.lastPage = data.last_page;
                this.pagination.page = data.current_page;
                this.pagination.pageSize = data.per_page;
            }).finally(_ => {
                this.loadIng--;
            });
        },

        // 下拉菜单点击事件
        handleDropdownClick(name, item) {
            switch (name) {
                case 'view':
                    this.handleView(item);
                    break;
                case 'edit':
                    this.handleEdit(item);
                    break;
                case 'delete':
                    this.handleDelete(item);
                    break;
                case 'session':
                    this.handleSession(item);
                    break;
                case 'token':
                    this.handleToken(item);
                    break;
            }
        },

        // 查看机器人
        handleView(item) {
            this.infoId = item.bot_id;
            this.infoShow = true;
        },

        // 编辑机器人
        handleEdit(item) {
            this.infoItem = item;
            this.editShow = true;
        },

        // 删除机器人
        handleDelete(item) {
            this.infoId = item.bot_id;
            this.deleteShow = true;
        },

        // 会话管理
        handleSession(item) {
            this.infoId = item.bot_id;
            this.sessionShow = true;
        },

        // 重置Token
        handleToken(item) {
            this.$store.dispatch("call", {
                url: 'users/bot/revoke',
                data: {
                    id: item.bot_id,
                },
            }).then(({msg, data}) => {
                $A.messageSuccess(msg);
                this.list.forEach(item => {
                    if (item.bot_id == data.id) {
                        item.token = data.token;
                    }
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        // 创建机器人成功
        handleCreateSuccess(data) {
            this.initList();
        },

        // 删除机器人成功
        handleDeleteSuccess() {
            this.list = this.list.filter(item => item.bot_id != this.infoId);
        },

        // 修改机器人成功
        handleEditSuccess(data) {
            this.list.forEach(item => {
                if (item.bot_id == data.id) {
                    item.name = data.name;
                    item.webhook_url = data.webhook_url;
                }
            });
        },

        // 复制Webhook
        webhookCopy(item) {
            if (!item.webhook_url) return;
            this.copyText(item.webhook_url);
        },

        // Token只显示前10和后10位，中间用*代替
        tokenFormat(token) {
            if (!token) return '-';
            return token.slice(0, 10) + '******' + token.slice(-10);
        },

        // Token复制
        tokenCopy(item) {
            if (!item.token) {
                return;
            }
            this.copyText(item.token);
        },

        // 搜索
        onSearch() {
            this.initList();
        },

        // 下拉加载
        handleScroll(e) {
            if (e.target.scrollTop + e.target.clientHeight >= e.target.scrollHeight) {
                this.initList('scroll');
            }
        },

        // 更新数据
        updateData(data, type) {
            type != 'scroll' ? (this.list = data.data) : data.data.map(h => {
                if (this.list.map(item => {
                    return item.bot_id
                }).indexOf(h.bot_id) == -1) {
                    this.list.push(h)
                }
            });
        },
        
    }
};
</script>