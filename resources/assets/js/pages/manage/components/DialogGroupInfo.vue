<template>
    <div class="dialog-group-info">
        <div class="dialog-group-head">
            <div class="group-info-title">{{$L('群名')}}</div>
            <div class="group-info-value">
                <div class="quick-edit">
                    <div class="quick-text" :title="dialogData.name">{{dialogData.name}}</div>
                    <Icon
                        v-if="dialogData.owner_id == userId"
                        class="quick-icon"
                        type="ios-create-outline"
                        @click.stop="onEditName"/>
                </div>
            </div>
        </div>

        <div class="group-info-title">{{$L('群组 ID')}}</div>
        <div class="group-info-value">{{ dialogId }}</div>

        <div class="group-info-title">{{$L('群类型')}}</div>
        <div class="group-info-value">{{ $L(groupType) }}</div>

        <div class="group-info-search">
            <Input
                prefix="ios-search"
                v-model="searchKey"
                :placeholder="$L('搜索成员')"
                clearable/>
        </div>

        <div class="group-info-user">
            <ul>
                <li v-if="allList.length === 0" class="no">
                    <Loading v-if="loadIng > 0"/>
                    <span v-else>{{$L('没有符合条件的数据')}}</span>
                </li>
                <template v-else-if="botList.length > 0">
                    <li class="label">
                        <span>{{$L('群机器人')}}</span>
                    </li>
                    <li v-for="item in botList" @click="openUser(item.userid)">
                        <UserAvatar :userid="item.userid" :size="32" showName/>
                        <div v-if="item.userid === dialogData.owner_id" class="user-tag">{{ $L("群主") }}</div>
                        <div v-else-if="operableExit(item)" class="user-exit" @click.stop="onExit(item)"><Icon type="md-exit"/></div>
                    </li>
                    <li class="label">
                        <span>{{$L(`群成员 (${userList.length}人)`)}}</span>
                    </li>
                    <li v-for="item in userList" @click="openUser(item.userid)">
                        <UserAvatar :userid="item.userid" :size="32" showName/>
                        <div v-if="item.userid === dialogData.owner_id" class="user-tag">{{ $L("群主") }}</div>
                        <div v-else-if="operableExit(item)" class="user-exit" @click.stop="onExit(item)"><Icon type="md-exit"/></div>
                    </li>
                </template>
                <template v-else>
                    <li v-for="item in userList" @click="openUser(item.userid)">
                        <UserAvatar :userid="item.userid" :size="32" showName/>
                        <div v-if="item.userid === dialogData.owner_id" class="user-tag">{{ $L("群主") }}</div>
                        <div v-else-if="operableExit(item)" class="user-exit" @click.stop="onExit(item)"><Icon type="md-exit"/></div>
                    </li>
                </template>
            </ul>
        </div>

        <div v-if="operableAdd" class="group-info-button">
            <Button v-if="dialogData.owner_id == userId || dialogData.owner_id == 0" @click="openAdd" type="primary" icon="md-add">{{ $L("添加成员") }}</Button>
        </div>

        <!--添加成员-->
        <Modal
            v-model="addShow"
            :title="$L('添加群成员')"
            :mask-closable="false">
            <Form :model="addData" v-bind="formOptions" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('新增成员')">
                    <UserSelect v-model="addData.userids" :disabledChoice="addData.disabledChoice" :multiple-max="100" show-bot :title="$L('选择成员')"/>
                    <div v-if="dialogData.group_type === 'department'" class="form-tip">{{$L('此操作仅加入群成员并不会加入部门')}}</div>
                    <div v-else-if="dialogData.group_type === 'project'" class="form-tip">{{$L('此操作仅加入群成员并不会加入项目')}}</div>
                    <div v-else-if="dialogData.group_type === 'task'" class="form-tip">{{$L('此操作仅加入群成员并不会加入任务负责人')}}</div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="addLoad > 0" @click="onAdd">{{$L('确定添加')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import UserSelect from "../../../components/UserSelect.vue";

export default {
    name: "DialogGroupInfo",
    components: {UserSelect},
    props: {
        dialogId: {
            type: Number,
            default: 0
        },
    },

    data() {
        return {
            searchKey: '',

            loadIng: 0,

            dialogUser: [],

            addShow: false,
            addData: {},
            addLoad: 0,

            openIng: false,
        }
    },

    computed: {
        ...mapState(['cacheDialogs', 'cacheUserBasic', 'userIsAdmin', 'formOptions']),

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {};
        },

        groupType() {
            const {group_type} = this.dialogData
            if (group_type === 'department') return '部门群组'
            if (group_type === 'project') return '项目群组'
            if (group_type === 'task') return '任务群组'
            if (group_type === 'user') return '个人群组'
            if (group_type === 'all') return '全员群组'
            if (group_type === 'okr') return 'OKR群组'
            return '未知'
        },

        allList() {
            const {dialogUser, searchKey, cacheUserBasic, dialogData} = this;
            const list = dialogUser.map(item => {
                const userBasic = cacheUserBasic.find(basic => basic.userid == item.userid)
                if (userBasic) {
                    item.nickname = userBasic.nickname
                    item.email = userBasic.email
                }
                return item
            }).filter(item => {
                if (searchKey && item.nickname) {
                    if (!$A.strExists(item.nickname, searchKey) && !$A.strExists(item.email, searchKey)) {
                        return false;
                    }
                }
                return true;
            })
            return list.sort((a, b) => {
                if (a.userid === dialogData.owner_id || b.userid === dialogData.owner_id) {
                    return (a.userid === dialogData.owner_id ? 0 : 1) - (b.userid === dialogData.owner_id ? 0 : 1);
                }
                return $A.sortDay(a.created_at, b.created_at);
            })
        },

        botList({allList}) {
            return allList.filter(item => item.bot)
        },


        userList({allList}) {
            return allList.filter(item => !item.bot)
        },
    },

    watch: {
        dialogId: {
            handler() {
                this.getDialogUser();
            },
            immediate: true
        }
    },

    methods: {
        onEditName() {
            this.$emit("on-modify")
        },

        getDialogUser() {
            if (this.dialogId <= 0) {
                return
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'dialog/user',
                data: {
                    dialog_id: this.dialogId
                }
            }).then(({data}) => {
                this.dialogUser = data;
                this.$store.dispatch("saveDialog", {
                    id: this.dialogId,
                    people: data.length,
                    people_user: data.filter(item => !item.bot).length,
                    people_bot: data.filter(item => item.bot).length,
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        operableAdd() {
            const {owner_id, group_type} = this.dialogData
            if (group_type == 'all') {
                return this.userIsAdmin
            }
            return [0, this.userId].includes(owner_id)
        },

        openAdd() {
            this.addData = {
                dialog_id: this.dialogId,
                userids: [],
                disabledChoice: this.dialogUser.map(item => item.userid)
            };
            this.addShow = true;
        },

        onAdd() {
            this.addLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/group/adduser',
                data: this.addData
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.addShow = false;
                this.addData = {};
                this.getDialogUser();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.addLoad--;
            });
        },

        operableExit(item) {
            const {owner_id, group_type} = this.dialogData
            if (group_type == 'all') {
                return this.userIsAdmin
            }
            return owner_id == this.userId || item.inviter == this.userId
        },

        onExit(item) {
            let content = "你确定要退出群组吗？"
            let userids = [];
            if ($A.isJson(item) && item.userid != this.userId) {
                content = `你确定要将【${item.nickname}】移出群组吗？`
                userids = [item.userid];
            }
            $A.modalConfirm({
                content,
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/group/deluser',
                            data: {
                                dialog_id: this.dialogId,
                                userids,
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            if (userids.length > 0) {
                                this.getDialogUser();
                            } else {
                                this.$store.dispatch("forgetDialog", {id: this.dialogId});
                                this.$emit("on-close")
                            }
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        },

        openUser(userid) {
            if (this.openIng) {
                return
            }
            this.openIng = true
            this.$store.dispatch("openDialogUserid", userid).then(_ => {
                this.$emit("on-close")
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.openIng = false
            });
        }
    }
}
</script>
