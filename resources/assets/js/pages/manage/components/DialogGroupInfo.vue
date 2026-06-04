<template>
    <div class="dialog-group-info">
        <div class="dialog-group-head">
            <div class="group-info-title">{{$L('群名')}}</div>
            <div class="group-info-value">
                <div class="quick-edit">
                    <div class="quick-text" :title="dialogData.name">{{dialogData.name}}</div>
                    <Icon
                        v-if="isOwnerOrDeputy || (dialogData.group_type === 'all' && userIsAdmin)"
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
                        <UserAvatar :userid="item.userid" :size="32" showName>
                            <template v-if="item.userid === dialogData.owner_id" #name-prefix>
                                <div class="user-tag">{{ $L("群主") }}</div>
                            </template>
                            <template v-else-if="(dialogData.deputy_ids || []).includes(item.userid)" #name-prefix>
                                <div class="deputy-tag">{{ $L('群管理员') }}</div>
                            </template>
                        </UserAvatar>
                        <div v-if="canKickMember(item)" class="user-exit" @click.stop="onExit(item)"><Icon type="md-exit"/></div>
                    </li>
                    <li class="label">
                        <span>{{$L(`群成员 (${userList.length}人)`)}}</span>
                    </li>
                </template>
                <li v-for="item in userList" @click="openUser(item.userid)">
                    <UserAvatar :userid="item.userid" :size="32" showName>
                        <template v-if="item.userid === dialogData.owner_id" #name-prefix>
                            <div class="user-tag">{{ $L("群主") }}</div>
                        </template>
                        <template v-else-if="(dialogData.deputy_ids || []).includes(item.userid)" #name-prefix>
                            <div class="deputy-tag">{{ $L('群管理员') }}</div>
                        </template>
                    </UserAvatar>
                    <div
                        v-if="canManageDeputy && !isPrimaryOwner(item) && !isDeputy(item)"
                        class="user-deputy-add"
                        :title="$L('任命群管理员')"
                        @click.stop="addDeputy(item)"><Icon type="md-add"/></div>
                    <div
                        v-if="canManageDeputy && isDeputy(item)"
                        class="user-deputy-del"
                        :title="$L('罢免群管理员')"
                        @click.stop="delDeputy(item)"><Icon type="md-remove"/></div>
                    <div v-if="canKickMember(item)" class="user-exit" @click.stop="onExit(item)"><Icon type="md-exit"/></div>
                </li>
            </ul>
        </div>

        <div v-if="operableAdd" class="group-info-button">
            <Button v-if="isOwnerOrDeputy || dialogData.owner_id == 0" @click="openAdd" type="primary" icon="md-add">{{ $L("添加成员") }}</Button>
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
            const deputyIds = dialogData.deputy_ids || [];
            const rank = uid => {
                if (uid === dialogData.owner_id) return 0;
                if (deputyIds.includes(uid)) return 1;
                return 2;
            };
            return list.sort((a, b) => {
                const ra = rank(a.userid), rb = rank(b.userid);
                if (ra !== rb) return ra - rb;
                return $A.sortDay(a.created_at, b.created_at);
            })
        },

        botList({allList}) {
            return allList.filter(item => item.bot)
        },


        userList({allList}) {
            return allList.filter(item => !item.bot)
        },

        canManageDeputy() {
            // Only the primary owner can manage deputies
            return this.dialogData?.owner_id === this.userId;
        },

        isOwnerOrDeputy() {
            if (!this.dialogData) return false;
            if (this.dialogData.owner_id === this.userId) return true;
            return (this.dialogData.deputy_ids || []).includes(this.userId);
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
            return [0, this.userId].includes(owner_id) || this.isOwnerOrDeputy
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

        isPrimaryOwner(item) {
            return item.userid === this.dialogData.owner_id;
        },

        isDeputy(item) {
            return (this.dialogData.deputy_ids || []).includes(item.userid);
        },

        canKickMember(item) {
            if (!this.dialogData) return false;
            if (item.userid === this.userId) return false; // can't kick self via this button
            const ownerId = this.dialogData.owner_id;
            const deputyIds = this.dialogData.deputy_ids || [];
            const isPrimary = ownerId === this.userId;
            const isDeputy = deputyIds.includes(this.userId);
            if (!isPrimary && !isDeputy) return false; // not a manager
            if (isPrimary) return item.userid !== ownerId; // primary can kick anyone except self
            // deputy: can't kick primary or other deputies
            return item.userid !== ownerId && !deputyIds.includes(item.userid);
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

        addDeputy(item) {
            $A.modalConfirm({
                language: false,
                title: this.$L('任命群管理员'),
                content: this.$L('确定将 (*) 任命为群管理员吗？', item.nickname || item.email),
                onOk: () => {
                    this.$store.dispatch('call', {
                        url: 'dialog/group/adddeputy',
                        data: {
                            dialog_id: this.dialogData.id,
                            userid: item.userid,
                        },
                        method: 'post',
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.getDialogUser();
                    }).catch(({msg}) => {
                        $A.messageError(msg);
                    });
                },
            });
        },

        delDeputy(item) {
            $A.modalConfirm({
                title: '罢免群管理员',
                content: '确定要罢免该群管理员吗？',
                // title/content auto-translated by modalConfig
                onOk: () => {
                    this.$store.dispatch('call', {
                        url: 'dialog/group/deldeputy',
                        data: {
                            dialog_id: this.dialogData.id,
                            userid: item.userid,
                        },
                        method: 'post',
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.getDialogUser();
                    }).catch(({msg}) => {
                        $A.messageError(msg);
                    });
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
