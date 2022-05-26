<template>
    <div
        v-if="isReady"
        class="dialog-wrapper"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true, $event)"
        @dragleave.prevent="chatDragOver(false, $event)">
        <slot name="head">
            <div class="dialog-nav" :class="{completed:$A.dialogCompleted(dialogData)}">
                <div class="dialog-back" @click="goBack">
                    <i class="taskfont">&#xe72d;</i>
                    <div v-if="msgUnreadOnly" class="back-num">{{msgUnreadOnly}}</div>
                </div>

                <div class="dialog-block">
                    <div class="dialog-avatar">
                        <template v-if="dialogData.type=='group'">
                            <i v-if="dialogData.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                            <i v-else-if="dialogData.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                            <Icon v-else class="icon-avatar" type="ios-people" />
                        </template>
                        <div v-else-if="dialogData.dialog_user" class="user-avatar">
                            <UserAvatar :online.sync="dialogData.online_state" :userid="dialogData.dialog_user.userid" :size="42"/>
                        </div>
                        <Icon v-else class="icon-avatar" type="md-person" />
                    </div>
                    <div class="dialog-title">
                        <div class="main-title">
                            <template v-for="tag in $A.dialogTags(dialogData)" v-if="tag.color != 'success'">
                                <Tag :color="tag.color" :fade="false">{{$L(tag.text)}}</Tag>
                            </template>
                            <h2>{{dialogData.name}}</h2>
                            <em v-if="peopleNum > 0">({{peopleNum}})</em>
                            <label v-if="dialogData.top_at" class="top-text">{{$L('置顶')}}</label>
                        </div>
                        <template v-if="dialogData.type === 'group'">
                            <div v-if="dialogData.group_type === 'project'" class="sub-title pointer" @click="openProject">
                                {{$L('项目聊天室')}} {{$L('打开项目管理')}}
                            </div>
                            <div v-else-if="dialogData.group_type === 'task'" class="sub-title pointer" @click="openTask">
                                {{$L('任务聊天室')}} {{$L('查看任务详情')}}
                            </div>
                        </template>
                        <div v-else-if="dialogData.type === 'user'" :class="['sub-title', dialogData.online_state === true ? 'online' : 'offline']">
                            {{$L(dialogData.online_state === true ? '在线' : dialogData.online_state)}}
                        </div>
                    </div>
                </div>

                <template v-if="dialogData.type === 'group'">
                    <ETooltip
                        v-if="dialogData.group_type === 'user'"
                        placement="top"
                        :disabled="!$isDesktop"
                        :openDelay="600"
                        :content="$L('群设置')">
                        <i class="taskfont dialog-create" @click="groupInfoShow = true">&#xe6e9;</i>
                    </ETooltip>
                </template>
                <ETooltip v-else-if="dialogData.type === 'user'" placement="top" :disabled="!$isDesktop" :content="$L('创建群组')">
                    <i class="taskfont dialog-create" @click="openCreateGroup">&#xe646;</i>
                </ETooltip>
            </div>
        </slot>
        <DynamicScroller
            ref="scroller"
            :items="allMsgs"
            :min-item-size="58"
            @onScroll="onScroll"
            class="dialog-scroller overlay-y">
            <template #before>
                <template v-if="allMsgs.length === 0">
                    <div v-if="dialogData.loading > 0" class="dialog-item loading"><Loading/></div>
                    <div v-else class="dialog-item nothing">{{$L('暂无消息')}}</div>
                </template>
                <div v-else-if="dialogData.hasMorePages" class="dialog-item history" @click="loadNextPage">{{$L('加载历史消息')}}</div>
            </template>
            <template v-slot="{ item, index, active }">
                <DynamicScrollerItem
                    :item="item"
                    :active="active"
                    :size-dependencies="[item.msg]"
                    :data-index="index"
                    :data-active="active"
                    :class="{
                        'dialog-item': true,
                        'self': item.userid == userId,
                        'history-tip': topId == item.id
                    }"
                    @click.native="">
                    <em v-if="topId == item.id" class="history-text">{{$L('历史消息')}}</em>
                    <div class="dialog-avatar">
                        <UserAvatar :userid="item.userid" :tooltipDisabled="item.userid == userId" :size="30"/>
                    </div>
                    <DialogView :msg-data="item" :dialog-type="dialogData.type" :hidden-percentage="dialogData.dialog_user && dialogData.dialog_user.userid == userId"/>
                </DynamicScrollerItem>
            </template>
        </DynamicScroller>
        <div :class="['dialog-footer', msgNew > 0 && allMsgs.length > 0 ? 'newmsg' : '']" @click="onActive">
            <div class="dialog-newmsg" @click="onToBottom">{{$L('有' + msgNew + '条新消息')}}</div>
            <div class="dialog-input">
                <slot name="inputBefore"/>
                <ChatInput
                    ref="input"
                    :dialog-id="dialogId"
                    v-model="msgText"
                    :maxlength="20000"
                    @on-focus="onEventFocus"
                    @on-blur="onEventBlur"
                    @on-more="onEventMore"
                    @on-file="sendFileMsg"
                    @on-send="sendMsg"
                    :placeholder="$L('输入消息...')"/>
                <slot name="inputAfter"/>
            </div>
            <DialogUpload
                ref="chatUpload"
                class="chat-upload"
                :dialog-id="dialogId"
                @on-progress="chatFile('progress', $event)"
                @on-success="chatFile('success', $event)"
                @on-error="chatFile('error', $event)"/>
        </div>
        <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
            <div class="drag-text">{{$L('拖动到这里发送')}}</div>
        </div>

        <!--拖动发送提示-->
        <Modal
            v-model="pasteShow"
            :title="$L(pasteTitle)"
            :cancel-text="$L('取消')"
            :ok-text="$L('发送')"
            :enter-ok="true"
            @on-ok="pasteSend">
            <ul class="dialog-wrapper-paste" :class="pasteWrapperClass">
                <li v-for="item in pasteItem">
                    <img v-if="item.type == 'image'" :src="item.result"/>
                    <div v-else>{{$L('文件')}}: {{item.name}} ({{$A.bytesToSize(item.size)}})</div>
                </li>
            </ul>
        </Modal>

        <!--创建群组-->
        <Modal
            v-model="createGroupShow"
            :title="$L('创建群组')"
            :mask-closable="false">
            <Form :model="createGroupData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('群成员')">
                    <UserInput v-model="createGroupData.userids" :uncancelable="createGroupData.uncancelable" :multiple-max="100" :placeholder="$L('选择项目成员')"/>
                </FormItem>
                <FormItem prop="chat_name" :label="$L('群名称')">
                    <Input v-model="createGroupData.chat_name" :placeholder="$L('输入群名称（选填）')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="createGroupShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="createGroupLoad > 0" @click="onCreateGroup">{{$L('创建')}}</Button>
            </div>
        </Modal>

        <!--群设置-->
        <DrawerOverlay
            v-model="groupInfoShow"
            placement="right"
            :size="380">
            <DialogGroupInfo v-if="groupInfoShow" :dialogId="dialogId"/>
        </DrawerOverlay>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogView from "./DialogView";
import DialogUpload from "./DialogUpload";
import UserInput from "../../../components/UserInput";
import DrawerOverlay from "../../../components/DrawerOverlay";
import DialogGroupInfo from "./DialogGroupInfo";
import ChatInput from "./ChatInput";

import { DynamicScroller, DynamicScrollerItem } from 'vue-virtual-scroller-hi'
import 'vue-virtual-scroller-hi/dist/vue-virtual-scroller.css'

export default {
    name: "DialogWrapper",
    components: {
        DynamicScroller,
        DynamicScrollerItem,
        ChatInput,
        DialogGroupInfo,
        DrawerOverlay,
        UserInput,
        DialogUpload,
        DialogView
    },

    props: {
        dialogId: {
            type: Number,
            default: 0
        },
    },

    data() {
        return {
            msgText: '',
            msgNew: 0,
            topId: 0,

            allMsgs: [],
            tempMsgs: [],

            pasteShow: false,
            pasteFile: [],
            pasteItem: [],

            createGroupShow: false,
            createGroupData: {},
            createGroupLoad: 0,

            dialogDrag: false,
            groupInfoShow: false,
        }
    },

    mounted() {

    },

    beforeDestroy() {

    },

    computed: {
        ...mapState([
            'userId',
            'cacheDialogs',
            'dialogMsgs',
            'wsOpenNum',
        ]),

        isReady() {
            return this.dialogId > 0 && this.dialogData.id > 0
        },

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {};
        },

        dialogMsgList() {
            if (!this.isReady) {
                return [];
            }
            return $A.cloneJSON(this.dialogMsgs.filter(({dialog_id}) => {
                return dialog_id == this.dialogId;
            })).sort((a, b) => {
                return a.id - b.id;
            });
        },

        tempMsgList() {
            if (!this.isReady) {
                return [];
            }
            return $A.cloneJSON(this.tempMsgs.filter(({dialog_id}) => {
                return dialog_id == this.dialogId;
            }));
        },

        allMsgList() {
            const {dialogMsgList, tempMsgList} = this;
            if (tempMsgList.length > 0) {
                dialogMsgList.push(...tempMsgList);
            }
            return dialogMsgList;
        },

        peopleNum() {
            return this.dialogData.type === 'group' ? $A.runNum(this.dialogData.people) : 0;
        },

        pasteTitle() {
            const {pasteItem} = this;
            let hasImage = pasteItem.find(({type}) => type == 'image')
            let hasFile = pasteItem.find(({type}) => type != 'image')
            if (hasImage && hasFile) {
                return '发送文件/图片'
            } else if (hasImage) {
                return '发送图片'
            }
            return '发送文件'
        },

        pasteWrapperClass() {
            if (this.pasteItem.find(({type}) => type !== 'image')) {
                return ['multiple'];
            }
            return [];
        },

        msgUnreadOnly() {
            let num = 0;
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogUnread(dialog);
            })
            if (num <= 0) {
                return '';
            }
            if (num > 99) {
                num = "99+"
            }
            return String(num);
        },
    },

    watch: {
        '$route': {
            handler (route) {
                if ($A.isJson(window.__sendDialogMsg) && window.__sendDialogMsg.time > $A.Time()) {
                    const {msgFile, msgText} = window.__sendDialogMsg;
                    window.__sendDialogMsg = null;
                    this.$nextTick(() => {
                        if ($A.isArray(msgFile) && msgFile.length > 0) {
                            this.sendFileMsg(msgFile);
                        } else if (msgText) {
                            this.sendMsg(msgText);
                        }
                    });
                }
                if (route.query && route.query._) {
                    let query = $A.cloneJSON(route.query);
                    delete query._;
                    this.goForward({query}, true);
                }
            },
            immediate: true
        },

        dialogId: {
            handler(id) {
                if (id) {
                    this.msgNew = 0;
                    this.topId = -1;
                    let cacheTimer = null;
                    if (this.allMsgList.length > 0) {
                        cacheTimer = setTimeout(_ => {
                            this.allMsgs = this.allMsgList;
                            this.onToBottom();
                        }, 1);
                    }
                    const startTime = new Date().getTime();
                    this.$store.dispatch("getDialogMsgs", id).then(_ => {
                        cacheTimer && clearTimeout(cacheTimer);
                        setTimeout(this.onToBottom, Math.max(0, 100 - (new Date().getTime() - startTime)));
                    }).catch(_ => {});
                }
            },
            immediate: true
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.$store.dispatch("getDialogMsgs", this.dialogId).catch(_ => {});
        },

        allMsgList(newList, oldList) {
            const {scrollE} = this.scrollInfo();
            this.allMsgs = newList;
            this.$nextTick(_ => {
                if (scrollE > 10 && oldList.length > 0) {
                    const lastId = oldList[oldList.length - 1].id
                    const tmpList = newList.filter(item => item.id && item.id > lastId)
                    this.msgNew += tmpList.length
                } else {
                    this.onToBottom();
                }
            })
        },
    },

    methods: {
        sendMsg(text) {
            let msgText;
            if (typeof text === "string" && text) {
                msgText = text;
            } else {
                msgText = this.msgText;
                this.msgText = '';
            }
            if (msgText == '') {
                this.$refs.input.focus();
                return;
            }
            msgText = msgText.replace(/<\/span> <\/p>$/, "</span></p>")
            //
            this.onToBottom();
            this.onActive();
            //
            let tempId = $A.randomString(16);
            this.tempMsgs.push({
                id: tempId,
                dialog_id: this.dialogData.id,
                type: 'text',
                userid: this.userId,
                msg: {
                    text: msgText,
                },
            });
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendtext',
                data: {
                    dialog_id: this.dialogId,
                    text: msgText,
                },
                method: 'post'
            }).then(({data}) => {
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
                this.sendSuccess(data);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
            });
        },

        sendFileMsg(row) {
            const files = $A.isArray(row) ? row : [row];
            if (files.length > 0) {
                this.pasteFile = [];
                this.pasteItem = [];
                files.some(file => {
                    let reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = ({target}) => {
                        this.pasteFile.push(file)
                        this.pasteItem.push({
                            type: $A.getMiddle(file.type, null, '/'),
                            name: file.name,
                            size: file.size,
                            result: target.result
                        })
                        this.pasteShow = true
                    }
                });
            }
        },

        chatPasteDrag(e, type) {
            this.dialogDrag = false;
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            const postFiles = Array.prototype.slice.call(files);
            if (postFiles.length > 0) {
                e.preventDefault();
                this.sendFileMsg(postFiles);
            }
        },

        chatDragOver(show, e) {
            let random = (this.__dialogDrag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialogDrag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                if (e.dataTransfer.effectAllowed === 'move') {
                    return;
                }
                this.dialogDrag = true;
            }
        },

        pasteSend() {
            this.pasteFile.some(file => {
                this.$refs.chatUpload.upload(file)
            });
        },

        chatFile(type, file) {
            switch (type) {
                case 'progress':
                    this.onToBottom();
                    this.onActive();
                    //
                    this.tempMsgs.push({
                        id: file.tempId,
                        dialog_id: this.dialogData.id,
                        type: 'loading',
                        userid: this.userId,
                        msg: { },
                    });
                    break;

                case 'error':
                    this.tempMsgs = this.tempMsgs.filter(({id}) => id != file.tempId)
                    break;

                case 'success':
                    this.tempMsgs = this.tempMsgs.filter(({id}) => id != file.tempId)
                    this.sendSuccess(file.data)
                    break;
            }
        },

        sendSuccess(data) {
            if ($A.isArray(data)) {
                data.some(item => {
                    this.sendSuccess(item)
                })
                return;
            }
            this.$store.dispatch("saveDialogMsg", data);
            this.$store.dispatch("increaseTaskMsgNum", this.dialogId);
            this.$store.dispatch("updateDialogLastMsg", data);
            this.onActive();
        },

        onEventFocus(e) {
            this.$emit("on-focus", e)
        },

        onEventBlur(e) {
            this.$emit("on-blur", e)
        },

        onEventMore(e) {
            if (['image', 'file'].includes(e)) {
                this.$refs.chatUpload.handleClick()
            }
        },

        onActive() {
            this.$emit("on-active");
        },

        onToBottom() {
            this.msgNew = 0;
            if (this.isReady) {
                this.$refs.scroller.scrollToBottom();
            }
        },

        openProject() {
            if (!this.dialogData.group_info) {
                return;
            }
            this.goForward({name: 'manage-project', params: {projectId:this.dialogData.group_info.id}});
        },

        openTask() {
            if (!this.dialogData.group_info) {
                return;
            }
            this.$store.dispatch("openTask", this.dialogData.group_info.id);
        },

        loadNextPage() {
            let tmpId = this.allMsgs[0].id;
            this.$store.dispatch('getDialogMoreMsgs', this.dialogId).then(() => {
                this.$nextTick(() => {
                    this.topId = tmpId;
                    const index = this.allMsgs.findIndex(({id}) => id == tmpId);
                    if (index > -1) {
                        this.$refs.scroller.scrollToItem(index);
                    }
                });
            }).catch(() => {})
        },

        openCreateGroup() {
            this.createGroupData = {
                userids: this.dialogData.dialog_user ? [this.userId, this.dialogData.dialog_user.userid] : [this.userId],
                uncancelable: [this.userId]
            };
            this.createGroupShow = true;
        },

        onCreateGroup() {
            this.createGroupLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/group/add',
                data: this.createGroupData
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.createGroupShow = false;
                this.createGroupData = {};
                this.$store.dispatch("saveDialog", data);
                this.goForward({name: 'manage-messenger', params: {dialogId: data.id}});
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.createGroupLoad--;
            });
        },

        scrollInfo() {
            if (!this.isReady) {
                return {
                    scale: 0,       //已滚动比例
                    scrollY: 0,     //滚动的距离
                    scrollE: 0,     //与底部距离
                }
            }
            const scrollerView = this.$refs.scroller.$el;
            let wInnerH = scrollerView.clientHeight;
            let wScrollY = scrollerView.scrollTop;
            let bScrollH = scrollerView.scrollHeight;
            this.scrollY = wScrollY;
            return {
                scale: wScrollY / (bScrollH - wInnerH),
                scrollY: wScrollY,
                scrollE: bScrollH - wInnerH - wScrollY,
            }
        },

        onScroll() {
            this.__onScroll && clearTimeout(this.__onScroll);
            this.__onScroll = setTimeout(_ => {
                const {scrollE} = this.scrollInfo();
                if (scrollE <= 10) {
                    this.msgNew = 0;
                }
            }, 100)
        },
    }
}
</script>
