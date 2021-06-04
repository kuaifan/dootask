<template>
    <div class="message-view">

        <div v-if="msgData.type == 'text'" class="message-content" v-html="textMsg(msgData.msg.text)"></div>
        <div v-else class="message-content message-unknown">{{$L("未知的消息类型")}}</div>

        <div v-if="msgData.created_at" class="message-time">{{formatTime(msgData.created_at)}}</div>
        <div v-else class="message-time"><Loading/></div>

    </div>
</template>

<script>
export default {
    name: "MessageView",
    props: {
        msgData: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },

    methods: {
        formatTime(date) {
            let time = Math.round(new Date(date).getTime() / 1000),
                string = '';
            if ($A.formatDate('Ymd') === $A.formatDate('Ymd', time)) {
                string = $A.formatDate('H:i', time)
            } else if ($A.formatDate('Y') === $A.formatDate('Y', time)) {
                string = $A.formatDate('m-d', time)
            } else {
                string = $A.formatDate('Y-m-d', time)
            }
            return string || '';
        },

        textMsg(text) {
            if (!text) {
                return ""
            }
            text = text.trim().replace(/(\n\x20*){3,}/g, "<br/><br/>");
            text = text.trim().replace(/\n/g, "<br/>");
            return text;
        }
    }
}
</script>
