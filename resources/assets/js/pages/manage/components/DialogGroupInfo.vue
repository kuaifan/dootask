<template>
    <div class="dialog-group-info">
        <div class="group-info-title">{{$L('群名')}}</div>
        <div class="group-info-value">
            <QuickEdit :value="dialogData.name" :disabled="dialogData.owner_id != userId" @on-update="updateName">{{dialogData.name}}</QuickEdit>
        </div>

        <div class="group-info-title">{{$L('群类型')}}</div>
        <div class="group-info-value">{{ $L(groupType) }}</div>

        <div class="group-info-search">
            <Input
                prefix="ios-search"
                v-model="searchKey"
                :placeholder="$L('搜索')"
                clearable/>
        </div>

        <div class="group-info-user">
            <ul>
                <li v-for="(item, index) in userList" :key="index">
                    <UserAvatar :userid="item.userid" :size="32" :user-result="userResult" showName tooltipDisabled/>
                    <div v-if="item.userid === dialogData.owner_id" class="user-tag">{{ $L("群主") }}</div>
                    <Icon v-else-if="dialogData.owner_id == userId" class="user-exit" type="md-exit" @click="onExit(item)"/>
                </li>
                <li v-if="userList.length === 0" class="no">
                    <Loading v-if="loadIng > 0"/>
                    <span v-else>{{$L('没有符合条件的数据')}}</span>
                </li>
            </ul>
        </div>

        <div v-if="dialogData.owner_id == userId" class="group-info-button">
            <Button @click="openAdd" type="primary">{{ $L("添加成员") }}</Button>
            <Button @click="onDisband" type="error" ghost>{{ $L("解散群组") }}</Button>
        </div>
        <div v-else class="group-info-button">
            <Button @click="onExit" type="error" ghost>{{ $L("退出群组") }}</Button>
        </div>

        <!--添加成员-->
        <Modal
            v-model="addShow"
            :title="$L('添加群成员')"
            :mask-closable="false">
            <Form :model="addData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('新增成员')">
                    <UserInput v-model="addData.userids" :disabledChoice="addData.disabledChoice" :multiple-max="100" :placeholder="$L('选择项目成员')"/>
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
import UserInput from "../../../components/UserInput";

export default {
    name: "DialogGroupInfo",
    components: {UserInput},
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
        }
    },

    computed: {
        ...mapState(['cacheDialogs', 'userId']),

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {};
        },

        groupType() {
            const {group_type} = this.dialogData
            if (group_type === 'project') return '项目群组'
            if (group_type === 'task') return '任务群组'
            if (group_type === 'user') return '个人群组'
            return '未知'
        },

        userList() {
            const {dialogUser, searchKey, dialogData} = this;
            const list = dialogUser.filter(item => {
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
                return $A.Date(a.created_at) - $A.Date(b.created_at);
            })
        }
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
        updateName(val, cb) {
            if (!val) {
                cb()
                return;
            }
            this.$store.dispatch("call", {
                url: 'dialog/group/edit',
                data: {
                    dialog_id: this.dialogId,
                    chat_name: val
                }
            }).then(({data}) => {
                this.$store.dispatch("saveDialog", data);
                cb()
            }).catch(({msg}) => {
                $A.modalError(msg);
                cb()
            });
        },

        getDialogUser() {
            if (this.dialogId <= 0) {
                return
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'dialog/group/user',
                data: {
                    dialog_id: this.dialogId
                }
            }).then(({data}) => {
                this.dialogUser = data;
                this.$store.dispatch("saveDialog", {
                    id: this.dialogId,
                    people: data.length
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        userResult(user) {
            let index = this.dialogUser.findIndex(({userid}) => userid === user.userid);
            if (index > -1) {
                this.dialogUser.splice(index, 1, Object.assign(user, {
                    id: this.dialogUser[index].id,
                    created_at: this.dialogUser[index].created_at
                }))
            }
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

        onExit(item) {
            let content = "你确定要退出群组吗？"
            let userids = [];
            if ($A.isJson(item)) {
                content = `你确定要将【${item.nickname}】移出群组吗？`
                userids = [item.userid];
            }
            $A.modalConfirm({
                content,
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'dialog/group/deluser',
                        data: {
                            dialog_id: this.dialogId,
                            userids,
                        }
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                        if (userids.length > 0) {
                            this.getDialogUser();
                        } else {
                            this.$store.dispatch("forgetDialog", this.dialogId);
                            this.goForward({name: 'manage-messenger'});
                        }
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                    }).finally(_ => {
                        this.$Modal.remove();
                    });
                },
            });
        },

        onDisband() {
            $A.modalConfirm({
                content: `你确定要解散【${this.dialogData.name}】群组吗？`,
                loading: true,
                okText: '解散',
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'dialog/group/disband',
                        data: {
                            dialog_id: this.dialogId,
                        }
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$store.dispatch("forgetDialog", this.dialogId);
                        this.goForward({name: 'manage-messenger'});
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                    }).finally(_ => {
                        this.$Modal.remove();
                    });
                },
            });
        },
    }
}
</script>
