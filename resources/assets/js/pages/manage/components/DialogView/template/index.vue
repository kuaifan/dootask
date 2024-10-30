<template>
    <div :class="`content-template no-dark-content ${msg.type}`" @click="viewText">
        <component :is="currentTemplate" :msg="msg"/>
    </div>
</template>

<script>
import BotHello from "./bot-hello.vue";
import BotHelp from "./bot-help.vue";
import BotList from "./bot-list.vue";
import BotInfo from "./bot-info.vue";
import BotNewbot from "./bot-newbot.vue";
import BotSetname from "./bot-setname.vue";
import BotDeletebot from "./bot-deletebot.vue";
import BotToken from "./bot-token.vue";
import BotRevoke from "./bot-revoke.vue";
import BotWebhook from "./bot-webhook.vue";
import BotClearday from "./bot-clearday.vue";
import BotDialog from "./bot-dialog.vue";
import BotApi from "./bot-api.vue";

import ApproveReviewer from "./approve-reviewer.vue";
import ApproveNotifier from "./approve-notifier.vue";
import ApproveCommentNotifier from "./approve-comment-notifier.vue";
import ApproveSubmitter from "./approve-submitter.vue";

import TaskList from "./task-list.vue";
import FileDownload from "./file-download.vue";

import Content from "./content.vue";
import Other from "./other.vue";

export default {
    props: {
        msg: Object,
    },
    data() {
        return {};
    },
    computed: {
        currentTemplate() {
            switch (this.msg.type) {
                case '/hello':
                    return BotHello;
                case '/help':
                    return BotHelp;
                case '/list':
                    return BotList;
                case '/info':
                    return BotInfo;
                case '/newbot':
                    return BotNewbot;
                case '/setname':
                    return BotSetname;
                case '/deletebot':
                    return BotDeletebot;
                case '/token':
                    return BotToken;
                case '/revoke':
                    return BotRevoke;
                case '/webhook':
                    return BotWebhook;
                case '/clearday':
                    return BotClearday;
                case '/dialog':
                    return BotDialog;
                case '/api':
                    return BotApi;

                case 'approve_reviewer':
                    return ApproveReviewer;
                case 'approve_notifier':
                    return ApproveNotifier;
                case 'approve_comment_notifier':
                    return ApproveCommentNotifier;
                case 'approve_submitter':
                    return ApproveSubmitter;

                case 'task_list':
                    return TaskList;
                case 'file_download':
                    return FileDownload;

                case 'content':
                    return Content;
                default:
                    return Other;
            }
        },
    },
    methods: {
        viewText(e) {
            this.$emit('viewText', e);
        },
    },
}
</script>
