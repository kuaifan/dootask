<template>
    <li
        :id="'view_' + dialogMsg.id"
        :class="{self:dialogMsg.userid === userId, 'history-tip': topId === dialogMsg.id}"
        @mouseover="listHover(true)"
        @mouseleave="listHover(false)">
        <em v-if="topId === dialogMsg.id" class="history-text">{{$L('历史消息')}}</em>
        <div class="dialog-avatar">
            <UserAvatar :userid="dialogMsg.userid" :tooltip-disabled="dialogMsg.userid === userId" :size="30"/>
        </div>
        <DialogView :msg-data="dialogMsg" :dialog-type="dialogData.type"/>
        <div class="dialog-action" v-show="showAction">
            <Tooltip v-if="parseInt(dialogMsg.userid) === parseInt(userId)" :content="$L('撤回')" :placement="msgIndex === 0 ? 'bottom' : 'top'">
                <Button type="text" icon="md-undo" @click="messageWithdraw"/>
            </Tooltip>
        </div>
    </li>
</template>

<script>
import {mapState} from "vuex";
import DialogView from "./DialogView";

export default {
    name: "DialogList",
    components: {DialogView},
    props: {
        dialogMsg: {
            type: Object,
            default: {}
        },
        topId: {
            type: Number,
            default: 0
        },
        dialogData: {
            type: Object,
            default: {}
        },
        msgIndex: {
            type: Number,
            default: -1,
        }
    },

    data() {
        return {
            showAction: false
        }
    },

    computed: {
        ...mapState([
            'userId',
        ]),
    },

    watch: {
    },

    methods: {
        listHover(act) {
            this.showAction = act === true;
        },
        messageWithdraw(){
            this.$store.dispatch("call", {
                url: 'dialog/msg/withdraw',
                data: {
                    msg_id: this.dialogMsg.id
                },
                method: 'get',
            }).then(({data, msg}) => {
                // data 结果数据
                $A.messageSuccess("消息已撤回");
                this.$store.dispatch("getDialogMsgs", this.dialogData.id);
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            });
        }
    }
}
</script>
