<template>
    <div class="common-user-select" :class="{'select-border': border}">
        <ul @click="onSelect(true)" :style="warpStyle">
            <li v-for="userid in values">
                <UserAvatar :userid="userid" :size="avatarSize" :show-icon="avatarIcon" :show-name="avatarName" tooltip-disabled/>
            </li>
            <li v-if="addIcon || values.length === 0" class="add-icon" :style="addStyle" @click.stop="onSelect"></li>
        </ul>
        <Modal
            v-model="showModal"
            :mask-closable="false"
            class-name="common-user-select-modal"
            :title="localTitle"
            :fullscreen="windowWidth < 576">
            <div class="user-modal-search">
                <Input v-model="searchKey" :placeholder="localPlaceholder" clearable>
                    <div class="search-pre" slot="prefix">
                        <Loading v-if="loadIng > 0"/>
                        <Icon v-else type="ios-search" />
                    </div>
                </Input>
            </div>
            <Scrollbar class="user-modal-list">
                <ul>
                    <li
                        v-for="item in lists"
                        :class="{
                            selected: selects.includes(item.userid),
                            disabled: inUncancelable(item.userid) || isDisabled(item.userid)
                        }"
                        @click="selectUser(item)">
                        <Icon v-if="selects.includes(item.userid)" class="user-modal-icon" type="ios-checkmark-circle" />
                        <Icon v-else class="user-modal-icon" type="ios-radio-button-off" />
                        <UserAvatar class="user-modal-avatar" :userid="item.userid" show-name tooltip-disabled/>
                        <div class="user-modal-userid">ID: {{item.userid}}</div>
                    </li>
                </ul>
            </Scrollbar>
            <div v-if="multipleMax" class="user-modal-multiple">
                <Checkbox class="multiple-check" v-model="multipleCheck" @on-change="onMultipleChange" :disabled="lists.length === 0">{{$L(multipleCheck ? '取消全选' : '全选')}}</Checkbox>
                <div class="multiple-text">
                    <span>{{$L('最多只能选择' + multipleMax + '个')}}</span>
                    <em v-if="selects.length">({{$L(`已选${selects.length}个`)}})</em>
                </div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" :loading="submittIng > 0" @click="showModal=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="submittIng > 0" @click="onSubmit">{{$L('确定')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
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
        // 是否显示添加按钮（已选择为空时一定是true）
        addIcon: {
            type: Boolean,
            default: true
        },
        // 是否只有点击添加按钮才显示弹窗
        onlyAddIconClick: {
            type: Boolean,
            default: false
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

        // 提交前的回调
        beforeSubmit: Function
    },
    data() {
        return {
            loadIng: 0,
            submittIng: 0,

            values: [],
            lists: [],
            selects: [],

            showModal: false,

            multipleCheck: false,

            searchTimer: null,
            searchKey: null,
            searchHistory: [],
        }
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
        showModal(value) {
            if (value) {
                this.searchUser()
            } else {
                this.searchKey = ""
            }
        },
        searchKey() {
            this.searchUser()
        },
        'lists.length'() {
            this.calcMultiple()
        },
        'selects.length'() {
            this.calcMultiple()
        },
    },
    computed: {
        warpStyle() {
            if (!this.onlyAddIconClick) {
                return {
                    cursor: 'pointer'
                }
            }
        },
        addStyle() {
            return {
                width: this.avatarSize + 'px',
                height: this.avatarSize + 'px',
            }
        },
        localTitle() {
            if (this.title === undefined) {
                return this.$L('选择会员')
            } else {
                return this.title;
            }
        },
        localPlaceholder() {
            if (this.placeholder === undefined) {
                return this.$L('搜索会员')
            } else {
                return this.placeholder;
            }
        }
    },
    methods: {
        searchUser() {
            if (!this.showModal) {
                return
            }
            //
            let key = this.searchKey;
            const history = this.searchHistory.find(item => item.key == key);
            if (history) {
                this.lists = history.data
            }
            //
            if (this.searchTimer) {
                clearTimeout(this.searchTimer);
            }
            this.searchTimer = setTimeout(() => {
                if (this.searchKey != key) {
                    return;
                }
                setTimeout(() => {
                    this.loadIng++;
                }, 300)
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
                        take: 50
                    },
                }).then(({data}) => {
                    this.lists = data
                    //
                    const index = this.searchHistory.findIndex(item => item.key == key);
                    const tmpData = {key, data, time: $A.Time()};
                    if (index > -1) {
                        this.searchHistory.splice(index, 1, tmpData)
                    } else {
                        this.searchHistory.push(tmpData)
                    }
                }).catch(({msg}) => {
                    this.lists = []
                    $A.messageWarning(msg)
                }).finally(_ => {
                    this.loadIng--;
                });
            }, this.searchHistory.length > 0 ? 300 : 0)
        },

        onSelect(warp = false) {
            if (warp === true) {
                if (this.onlyAddIconClick) {
                    return
                }
            }
            this.selects = $A.cloneJSON(this.values)
            this.showModal = true
        },

        onSubmit() {
            const clone = $A.cloneJSON(this.values)
            this.values = $A.cloneJSON(this.selects)
            this.$emit('input', this.values)

            if (!this.beforeSubmit) {
                this.showModal = false
                return
            }
            const before = this.beforeSubmit();
            if (before && before.then) {
                this.submittIng++
                before.then(() => {
                    this.showModal = false
                }).catch(() => {
                    this.values = clone
                    this.$emit('input', this.values)
                }).finally(() => {
                    this.submittIng--
                })
            } else {
                this.showModal = false
            }
        },

        onMultipleChange(check) {
            if (check) {
                let optional = this.multipleMax - this.selects.length
                this.lists.some(item => {
                    if (this.inUncancelable(item.userid)) {
                        return false
                    }
                    if (this.isDisabled(item.userid)) {
                        return false
                    }
                    if (optional <= 0) {
                        $A.messageWarning("已超过最大选择数量")
                        return true
                    }
                    if (!this.selects.includes(item.userid)) {
                        this.selects.push(item.userid)
                        optional--
                    }
                })
                this.calcMultiple()
            } else {
                this.selects = $A.cloneJSON(this.uncancelable)
            }
        },

        calcMultiple() {
            this.$nextTick(() => {
                this.multipleCheck = this.lists.length > 0 && this.lists.filter(item => this.selects.includes(item.userid)).length === this.lists.length;
            })
        },

        selectUser(item) {
            if (this.selects.includes(item.userid)) {
                if (this.inUncancelable(item.userid)) {
                    return
                }
                this.selects = this.selects.filter(userid => userid != item.userid)
            } else {
                if (this.isDisabled(item.userid)) {
                    return
                }
                if (this.multipleMax && this.selects.length >= this.multipleMax) {
                    $A.messageWarning("已超过最大选择数量")
                    return
                }
                this.selects.push(item.userid)
            }
        },

        inUncancelable(value) {
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
    }
};
</script>
