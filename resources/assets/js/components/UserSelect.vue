<template>
    <div class="common-user-select" :class="warpClass">
        <ul v-if="!module">
            <template v-for="userid in values">
                <li v-if="userid" :key="userid" @click="onSelection">
                    <UserAvatar :userid="userid" :size="avatarSize" :show-icon="avatarIcon" :show-name="avatarName"/>
                </li>
            </template>
            <li v-if="addIcon || values.length === 0" class="add-icon" :style="addStyle" @click="onSelection"></li>
        </ul>

        <Modal
            v-model="showModal"
            class-name="common-user-select-modal"
            :mask-closable="false"
            :closable="!isFullscreen"
            :fullscreen="isFullscreen"
            :footer-hide="isFullscreen"
            width="640">

            <!-- 顶部 -->
            <template #header>
                <div v-if="isFullscreen" class="user-modal-header">
                    <div class="user-modal-close" @click="showModal=false">{{ $L('关闭') }}</div>
                    <div class="user-modal-title">
                        <span ref="headerTitle" @click="onClickTitle">{{ localTitle }}</span>
                    </div>
                    <div ref="headerSubmit" class="user-modal-submit" @click="onSubmit">
                        <div v-if="submittIng > 0" class="submit-loading">
                            <Loading/>
                        </div>
                        {{ $L('确定') }}
                        <template v-if="selects.length > 0">
                            ({{ selects.length }}<span v-if="multipleMax">/{{ multipleMax }}</span>)
                        </template>
                    </div>
                </div>
                <div v-else class="ivu-modal-header-inner">{{ localTitle }}</div>
            </template>
            <template #close>
                <i class="ivu-icon ivu-icon-ios-close"></i>
            </template>

            <!-- 搜索 -->
            <div class="user-modal-search">
                <Scrollbar ref="selected" class="search-selected" v-if="selects.length > 0" enable-x :enable-y="false">
                    <ul>
                        <li v-for="item in formatSelect(selects)" :key="item.userid" :data-id="item.userid" @click.stop="onRemoveItem(item.userid)">
                            <template v-if="item.type=='group'">
                                <EAvatar v-if="item.avatar" class="img-avatar" :src="item.avatar" :size="32"></EAvatar>
                                <i v-else-if="item.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                <i v-else-if="item.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="item.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <i v-else-if="item.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people"/>
                            </template>
                            <UserAvatar v-else :userid="item.userid"/>
                        </li>
                    </ul>
                </Scrollbar>
                <div class="search-input">
                    <div class="search-pre">
                        <Loading v-if="loadIng > 0"/>
                        <Icon v-else type="ios-search"/>
                    </div>
                    <Form class="search-form" action="javascript:void(0)" @submit.native.prevent="$A.eeuiAppKeyboardHide">
                        <Input
                            type="search"
                            v-model="searchKey"
                            :placeholder="localPlaceholder"
                            @on-keydown="onKeydown"
                            @on-keyup="onKeyup"
                            clearable/>
                    </Form>
                </div>
            </div>

            <!-- 切换 -->
            <ul v-if="isWhole" class="user-modal-switch">
                <li
                    v-for="item in switchItems" :key="item.key"
                    :class="{active:switchActive===item.key}"
                    @click="switchActive=item.key">{{ $L(item.label) }}
                </li>
            </ul>

            <!-- 列表 -->
            <Scrollbar v-if="lists.length > 0" class="user-modal-list">
                <!-- 项目 -->
                <ul v-if="switchActive == 'project'" class="user-modal-project">
                    <li
                        v-for="item in lists"
                        :key="item.id"
                        :class="selectClass(item.userid_list)"
                        @click="onSelectMultiple(item.userid_list)">
                        <Icon class="user-modal-icon" :type="selectIcon(item.userid_list)"/>
                        <div class="user-modal-avatar">
                            <i class="taskfont icon-avatar">&#xe6f9;</i>
                            <div class="project-name">
                                <div class="label">{{ item.name }}</div>
                                <div class="subtitle">
                                    {{ item.userid_list.length }} {{ $L('项目成员') }}
                                    <em class="all">{{ $L('已全选') }}</em>
                                    <em class="some">{{ $L('已选部分') }}</em>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
                <!-- 会员、会话 -->
                <template v-else>
                    <ul v-if="showSelectAll || switchActive=='contact'" class="sticky-top">
                        <li :class="selectClass('all')" class="select-view">
                            <div v-if="showSelectAll" @click="onSelectAll" class="user-modal-label">
                                <Icon class="user-modal-icon" :type="selectIcon('all')"/>
                                <span>{{ $L('全选') }}</span>
                            </div>
                            <div v-if="switchActive=='contact'" class="user-modal-view">
                                <RadioGroup v-model="contactViewMode" type="button" button-style="solid">
                                    <Radio label="list">{{ $L('列表视图') }}</Radio>
                                    <Radio label="department">{{ $L('部门视图') }}</Radio>
                                </RadioGroup>
                            </div>
                        </li>
                    </ul>
                    <template v-for="items in convertTwoList(lists)">
                        <ul v-if="items.name !== null" :key="`${items.id}-sticky`" class="sticky-top">
                            <li :class="selectClass(items.userid_list)">
                                <div @click="onSelectMultiple(items.userid_list)" class="user-modal-label">
                                    <Icon class="user-modal-icon" :type="selectIcon(items.userid_list)"/>
                                    <span>{{ items.name }}</span>
                                </div>
                                <div class="user-modal-view">{{ items.list.length }} {{ $L('部门成员') }}</div>
                            </li>
                        </ul>
                        <ul :key="`${items.id}-list`">
                            <li
                                v-for="item in items.list"
                                :key="item.userid"
                                :class="{
                                    selected: selects.includes(item.userid),
                                    disabled: isNoChoice(item.userid),
                                }"
                                @click="onSelectItem(item)">
                                <Icon v-if="selects.includes(item.userid)" class="user-modal-icon" type="ios-checkmark-circle"/>
                                <Icon v-else-if="isNoChoice(item.userid)" class="user-modal-icon" type="ios-remove-circle-outline"/>
                                <Icon v-else class="user-modal-icon" type="ios-radio-button-off"/>
                                <div v-if="item.type=='group'" class="user-modal-avatar">
                                    <EAvatar v-if="item.avatar" class="img-avatar" :src="item.avatar" :size="40"></EAvatar>
                                    <i v-else-if="item.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                    <i v-else-if="item.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                    <i v-else-if="item.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                    <i v-else-if="item.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                    <Icon v-else class="icon-avatar" type="ios-people"/>
                                    <div class="avatar-name">
                                        <span>{{ item.name }}</span>
                                    </div>
                                </div>
                                <UserAvatar v-else class="user-modal-avatar" :userid="item.userid" :size="40" show-name/>
                            </li>
                        </ul>
                    </template>
                </template>
            </Scrollbar>
            <!-- 空 -->
            <div v-else class="user-modal-empty">
                <Loading v-if="waitIng > 0"/>
                <template v-else>
                    <div class="empty-icon">
                        <Icon type="ios-cafe-outline"/>
                    </div>
                    <div class="empty-text">{{ $L('暂无结果') }}</div>
                </template>
            </div>

            <!-- 底部 -->
            <template #footer>
                <Button type="primary" :loading="submittIng > 0" @click="onSubmit">
                    {{ $L('确定') }}
                    <template v-if="selects.length > 0">
                        ({{ selects.length }}<span v-if="multipleMax">/{{ multipleMax }}</span>)
                    </template>
                </Button>
            </template>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: 'UserSelect',
    props: {
        value: {
            type: [String, Number, Array],
            default: () => {
                return [];
            }
        },
        // 不允许取消的列表
        uncancelable: {
            type: Array,
            default: () => {
                return [];
            }
        },
        // 禁止选择的列表
        disabledChoice: {
            type: Array,
            default: () => {
                return [];
            }
        },

        // 指定项目ID
        projectId: {
            type: Number,
            default: 0
        },
        // 指定非项目ID
        noProjectId: {
            type: Number,
            default: 0
        },
        // 指定会话ID
        dialogId: {
            type: Number,
            default: 0
        },

        // 是否显示机器人
        showBot: {
            type: Boolean,
            default: false
        },
        // 是否显示禁用的
        showDisable: {
            type: Boolean,
            default: false
        },
        // 最大选择数量
        multipleMax: {
            type: Number,
        },

        // 头像大小
        avatarSize: {
            type: Number,
            default: 28
        },
        // 是否显示头像
        avatarIcon: {
            type: Boolean,
            default: true
        },
        // 是否显示名称
        avatarName: {
            type: Boolean,
            default: false
        },
        // 是否显示添加按钮（已选择为空时强制true）
        addIcon: {
            type: Boolean,
            default: true
        },
        // 显示边框
        border: {
            type: Boolean,
            default: false
        },

        // 弹窗标题
        title: {
            type: String,
        },
        // 搜索提示
        placeholder: {
            type: String,
        },

        // 显示全选项
        showSelectAll: {
            type: Boolean,
            default: true
        },
        // 显示所有会话（会话返回格式：d:{会话ID}，建议配合 module=true 一起使用）
        showDialog: {
            type: Boolean,
            default: false
        },
        // 模块化（通过 api 方法调用）
        module: {
            type: Boolean,
            default: false
        },

        // 是否禁用（禁止打开选择）
        disabled: {
            type: Boolean,
            default: false
        },

        // 仅显示群组
        onlyGroup: {
            type: Boolean,
            default: false
        },

        // 提交前的回调
        beforeSubmit: Function
    },
    data() {
        return {
            switchItems: [
                {key: 'recent', label: '最近'},
                {key: 'contact', label: '通讯录'},
                {key: 'project', label: '项目成员'},
            ],
            switchActive: 'recent',
            contactViewMode: 'list',

            loadIng: 0,             // 搜索框等待效果
            waitIng: 0,             // 页面等待效果
            submittIng: 0,          // 提交按钮等待效果
            backspaceDelete: false, // 是否按删除键删除

            values: [],
            selects: [],
            callback: null,
            closeCallback: null,

            recents: [],
            contacts: [],
            projects: [],

            showModal: false,

            searchKey: null,
            searchCache: [],
        }
    },
    async mounted() {
        this.contactViewMode = await $A.IDBString("userSelectContactViewMode", this.contactViewMode)
    },
    watch: {
        value: {
            handler(value) {
                if (typeof value === 'number') {
                    this.$emit('input', value > 0 ? [value] : [])
                } else if (typeof value === 'string') {
                    value = value.indexOf(',') > -1 ? value.split(',') : [value]
                    this.$emit('input', value.map(item => $A.runNum(item)).filter(item => item > 0))
                }
                this.values = value
            },
            immediate: true
        },

        isWhole: {
            handler(value) {
                if (value || this.onlyGroup) {
                    this.switchActive = 'recent'
                } else {
                    this.switchActive = 'contact'
                }
            },
            immediate: true
        },

        showModal(v) {
            if (v) {
                this.searchBefore()
                this.upTitleWidth()
            } else {
                this.searchKey = ""
                this.closeCallback && this.closeCallback()
            }
            this.$emit("on-show-change", v)
            //
            $A.eeuiAppSetScrollDisabled(v && this.windowPortrait)
        },

        searchKey() {
            this.searchBefore()
        },

        switchActive() {
            this.searchBefore()
        },

        contactViewMode(value) {
            $A.IDBSet("userSelectContactViewMode", value)
        },

        isFullscreen(value) {
            if (value) {
                this.upTitleWidth()
            }
        },

        'selects.length'() {
            this.upTitleWidth()
        }
    },
    computed: {
        ...mapState([
            'cacheDialogs',
        ]),

        isFullscreen({windowWidth}) {
            return windowWidth < 576
        },

        isWhole({projectId, noProjectId, dialogId, onlyGroup}) {
            return projectId === 0 && noProjectId === 0 && dialogId === 0 && !onlyGroup
        },

        lists({switchActive, searchKey, recents, contacts, projects}) {
            switch (switchActive) {
                case 'recent':
                    if (searchKey) {
                        return recents.filter(item => $A.strExists(`${item.name} ${item.email} ${item.pinyin}`, searchKey))
                    }
                    return recents

                case 'contact':
                    return contacts

                case 'project':
                    return projects
            }
            return []
        },

        isSelectAll({lists, selects}) {
            return lists.length > 0 && lists.filter(item => selects.includes(item.userid)).length === lists.length;
        },

        warpClass() {
            return {
                'select-module': this.module,
                'select-border': this.border,
                'select-whole': this.isWhole,
            }
        },

        addStyle({avatarSize}) {
            return {
                width: avatarSize + 'px',
                height: avatarSize + 'px',
            }
        },

        localTitle({title}) {
            if (title === undefined) {
                return this.$L('选择会员')
            } else {
                return title;
            }
        },

        localPlaceholder({placeholder}) {
            if (placeholder === undefined) {
                return this.$L('搜索')
            } else {
                return placeholder;
            }
        }
    },
    methods: {
        upTitleWidth() {
            if (!this.isFullscreen) {
                return
            }
            this.$nextTick(() => {
                const headerTitle = this.$refs.headerTitle;
                const headerSubmit = this.$refs.headerSubmit;
                if (headerTitle && headerSubmit) {
                    headerTitle.style.width = (this.windowWidth - headerSubmit.clientWidth * 2) + 'px';
                }
            })
        },

        isUncancelable(value) {
            if (this.uncancelable.length === 0) {
                return false;
            }
            return this.uncancelable.includes(value);
        },

        isDisabled(userid) {
            if (this.disabledChoice.length === 0) {
                return false;
            }
            return this.disabledChoice.includes(userid)
        },

        isNoChoice(userid) {
            return this.isUncancelable(userid) || this.isDisabled(userid)
        },

        formatSelect(list) {
            return list.map(userid => {
                if ($A.leftExists(userid, 'd:')) {
                    return this.recents.find(item => item.userid === userid)
                }
                return {
                    type: 'user',
                    userid,
                }
            })
        },

        convertTwoList(lists) {
            if (this.switchActive === 'contact' && this.contactViewMode === 'department') {
                const departmentMap = new Map()
                const noDepartmentMembers = []
                // 部门视图
                lists.forEach(item => {
                    if (item.department_info && item.department_info.length > 0) {
                        // 用户可能属于多个部门
                        item.department_info.forEach(dept => {
                            if (!departmentMap.has(dept.id)) {
                                departmentMap.set(dept.id, {
                                    id: dept.id,
                                    name: dept.name,
                                    list: [],
                                })
                            }
                            departmentMap.get(dept.id).list.push(item)
                        })
                    } else {
                        // 没有部门信息的成员
                        noDepartmentMembers.push(item)
                    }
                })
                // 处理默认部门（未分组）
                if (noDepartmentMembers.length > 0) {
                    departmentMap.set(0, {
                        id: 0,
                        name: this.$L('默认部门'),
                        list: noDepartmentMembers,
                    })
                }
                return Array.from(departmentMap.values()).map(item => ({...item, userid_list: item.list.map(item => item.userid)}))
            }
            return [
                {
                    id: 0,
                    name: null,
                    list: lists,
                },
            ]
        },

        selectIcon(value) {
            if (value === 'all') {
                return this.isSelectAll ? 'ios-checkmark-circle' : 'ios-radio-button-off';
            }
            if ($A.isArray(value) && value.length > 0) {
                const len = value.filter(value => this.selects.includes(value)).length
                if (len === value.length) {
                    return 'ios-checkmark-circle';
                }
                if (len > 0) {
                    return 'ios-remove-circle';
                }
            }
            return 'ios-radio-button-off';
        },

        selectClass(value) {
            switch (this.selectIcon(value)) {
                case 'ios-checkmark-circle':
                    return 'selected';
                case 'ios-remove-circle':
                    return 'somed';
            }
            return '';
        },

        searchBefore() {
            if (!this.showModal) {
                return
            }
            if (this.switchActive === 'recent') {
                this.searchRecent()
            } else if (this.switchActive === 'contact') {
                this.searchContact()
            } else if (this.switchActive === 'project') {
                this.searchProject()
            }
        },

        searchRecent() {
            this.recents = this.cacheDialogs.filter(dialog => {
                if (this.onlyGroup && dialog.type != 'group') {
                    return false
                }
                if (dialog.name === undefined || dialog.dialog_delete === 1) {
                    return false
                }
                if (!this.showBot && dialog.bot) {
                    return false
                }
                return this.showDialog || dialog.type === 'user'
            }).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.sortDay(b.top_at, a.top_at);
                }
                if (a.todo_num > 0 || b.todo_num > 0) {
                    return $A.sortFloat(b.todo_num, a.todo_num);
                }
                return $A.sortDay(b.last_at, a.last_at);
            }).map(({id, name, pinyin, email, type, group_type, avatar, dialog_user}) => {
                return {
                    name,
                    pinyin,
                    email,
                    type,
                    group_type,
                    avatar,
                    userid: type === 'user' ? dialog_user.userid : `d:${id}`,
                }
            });
        },

        searchContact() {
            const key = this.searchKey;
            const cache = this.searchCache.find(item => item.type === 'contact' && item.key == key);
            if (cache) {
                this.contacts = cache.data
                if (!cache.more) {
                    return
                }
            }
            //
            this.waitIng++
            setTimeout(() => {
                if (this.searchKey != key) {
                    this.waitIng--
                    return;
                }
                setTimeout(() => {
                    this.loadIng++
                }, 300)
                this.searchRequest(key, 1, () => {
                    this.loadIng--;
                    this.waitIng--;
                })
            }, this.searchCache.length > 0 ? 300 : 0)
        },

        searchRequest(key, page, cb) {
            this.$store.dispatch("call", {
                url: 'users/search',
                data: {
                    keys: {
                        key,
                        project_id: this.projectId,
                        no_project_id: this.noProjectId,
                        dialog_id: this.dialogId,
                        bot: this.showBot && key ? 2 : 0,
                        disable: this.showDisable && key ? 2 : 0,
                    },
                    page,
                    pagesize: 100,
                    with_department: 1,
                },
            }).then(({data}) => {
                if (this.searchKey != key) {
                    cb()
                    return
                }
                const items = data.data.map(item => Object.assign(item, {type: 'user'}))
                if (data.current_page > 1) {
                    items.unshift(...this.contacts)
                }
                this.contacts = items
                //
                const index = this.searchCache.findIndex(item => item.type === 'contact' && item.key == key);
                const tmpData = {type: 'contact', key, data: items, time: $A.dayjs().unix(), more: data.current_page < data.last_page};
                if (index > -1) {
                    this.searchCache.splice(index, 1, tmpData)
                } else {
                    this.searchCache.push(tmpData)
                }
                //
                if (!tmpData.more) {
                    cb()
                    return;
                }
                if (data.current_page % 5 === 0) {
                    $A.modalConfirm({
                        content: "数据已超过" + data.to + "条，是否继续加载？",
                        onOk: () => {
                            this.searchRequest(key, data.current_page + 1, cb)
                        },
                        onCancel: cb
                    });
                } else {
                    this.searchRequest(key, data.current_page + 1, cb)

                }
            }).catch(({msg}) => {
                if (page === 1) {
                    this.contacts = []
                }
                $A.messageWarning(msg)
                cb()
            });
        },

        searchProject() {
            const key = this.searchKey;
            const cache = this.searchCache.find(item => item.type === 'project' && item.key == key);
            if (cache) {
                this.projects = cache.data
                if (!cache.more) {
                    return
                }
            }
            //
            this.waitIng++
            setTimeout(() => {
                if (this.searchKey != key) {
                    this.waitIng--
                    return;
                }
                setTimeout(() => {
                    this.loadIng++
                }, 300)
                this.$store.dispatch("call", {
                    url: 'project/lists',
                    data: {
                        type: 'team',
                        keys: {
                            name: key,
                        },
                        getuserid: 'yes',
                        getstatistics: 'no'
                    },
                }).then(({data}) => {
                    if (this.searchKey != key) {
                        return
                    }
                    //
                    const items = data.data.map(item => Object.assign(item, {type: 'project'}))
                    this.projects = items
                    //
                    const index = this.searchCache.findIndex(item => item.type === 'project' && item.key == key);
                    const tmpData = {type: 'project', key, data: items, time: $A.dayjs().unix(), more: false};
                    if (index > -1) {
                        this.searchCache.splice(index, 1, tmpData)
                    } else {
                        this.searchCache.push(tmpData)
                    }
                }).catch(({msg}) => {
                    this.projects = []
                    $A.messageWarning(msg)
                }).finally(_ => {
                    this.loadIng--;
                    this.waitIng--;
                });
            }, this.searchCache.length > 0 ? 300 : 0)
        },

        onSelection(callback = null, closeCallback = null) {
            if (this.disabled) {
                return
            }
            this.$nextTick(_ => {
                this.selects = $A.cloneJSON(this.values)
                this.callback = typeof callback === 'function' ? callback : null
                this.closeCallback = typeof closeCallback === 'function' ? closeCallback : null
                this.showModal = true
            })
        },

        onSelectItem({userid}) {
            if (this.selects.includes(userid)) {
                if (this.isUncancelable(userid)) {
                    return
                }
                this.selects = this.selects.filter(value => value != userid)
            } else {
                if (this.isDisabled(userid)) {
                    return
                }
                if (this.multipleMax && this.selects.length >= this.multipleMax) {
                    if (this.multipleMax > 1) {
                        $A.messageWarning("已超过最大选择数量")
                        return
                    }
                    this.selects = []
                }
                this.selects.push(userid)
                // 滚动到选中的位置
                this.$nextTick(() => {
                    $A.scrollIntoViewIfNeeded(this.$refs.selected.querySelector(`li[data-id="${userid}"]`), true)
                })
            }
        },

        onSelectMultiple(userid_list) {
            switch (this.selectIcon(userid_list)) {
                case 'ios-checkmark-circle':
                    // 去除
                    const removeList = userid_list.filter(userid => !this.isUncancelable(userid))
                    if (removeList.length != userid_list.length) {
                        $A.messageWarning("部分成员禁止取消")
                    }
                    this.selects = this.selects.filter(userid => !removeList.includes(userid))
                    break;
                default:
                    // 添加
                    const addList = userid_list.filter(userid => !this.isDisabled(userid))
                    if (addList.length != userid_list.length) {
                        $A.messageWarning("部分成员禁止选择")
                    }
                    this.selects = this.selects.concat(addList.filter(userid => !this.selects.includes(userid)))
                    // 超过最大数量
                    if (this.multipleMax && this.selects.length > this.multipleMax) {
                        $A.messageWarning("已超过最大选择数量")
                        this.selects = this.selects.slice(0, this.multipleMax)
                    }
                    break;
            }
        },

        onSelectAll() {
            if (this.isSelectAll) {
                this.selects = $A.cloneJSON(this.uncancelable)
                return
            }
            this.lists.some(item => {
                if (this.isDisabled(item.userid)) {
                    return false
                }
                if (this.multipleMax && this.selects.length >= this.multipleMax) {
                    $A.messageWarning("已超过最大选择数量")
                    return true
                }
                if (!this.selects.includes(item.userid)) {
                    this.selects.push(item.userid)
                }
            })
        },

        onRemoveItem(userid) {
            if (this.isUncancelable(userid)) {
                return
            }
            this.selects = this.selects.filter(value => value != userid)
        },

        onClickTitle() {
            const $content = this.$refs.headerTitle;
            const range = document.createRange();
            range.setStart($content, 0);
            range.setEnd($content, $content.childNodes.length || 0);
            const rangeWidth = range.getBoundingClientRect().width;
            if (Math.floor(rangeWidth) > Math.floor($content.offsetWidth)) {
                $A.modalInfo({
                    title: this.$L("全标题"),
                    content: this.localTitle,
                    language: false,
                })
            }
        },

        onSubmit() {
            if (this.submittIng > 0) {
                return
            }
            const clone = $A.cloneJSON(this.values)
            this.values = $A.cloneJSON(this.selects)
            this.$emit('input', this.values)
            this.$emit('on-submit', this.values)

            const beforeSubmit = this.callback || this.beforeSubmit;
            if (!beforeSubmit) {
                this.hide()
                return
            }
            const before = beforeSubmit(this.values);
            if (before && before.then) {
                this.submittIng++
                before.then(() => {
                    this.hide()
                }).catch(() => {
                    this.values = clone
                    this.$emit('input', this.values)
                }).finally(() => {
                    this.submittIng--
                })
            } else {
                this.hide()
            }
        },

        onKeydown(event) {
            if (event.isComposing || event.key === 'Process') {
                return;
            }
            // 按下删除键时，判断是否符合删除条件
            this.backspaceDelete = event.key === 'Backspace' && !this.searchKey && this.selects.length > 0;
        },

        onKeyup(event) {
            if (event.isComposing || event.key === 'Process') {
                return;
            }
            if (event.key === 'Backspace' && this.backspaceDelete) {
                // 从最后一个元素开始向前遍历，找到第一个不是不可取消的元素
                for (let i = this.selects.length - 1; i >= 0; i--) {
                    const userid = this.selects[i];
                    if (!this.isUncancelable(userid)) {
                        this.onRemoveItem(userid);
                        break; // 找到并移除后立即退出循环
                    }
                }
            }
        },

        show() {
            this.onSelection()
        },

        hide() {
            this.showModal = false
        },
    }
};
</script>
