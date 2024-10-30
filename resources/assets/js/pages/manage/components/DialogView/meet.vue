<template>
    <div class="content-meeting no-dark-content">
        <ul class="dialog-meeting" :class="{'meeting-end':!!msg.end_at}">
            <li>
                <em>{{ $L('会议主题') }}</em>
                {{ msg.name }}
            </li>
            <li>
                <em>{{ $L('会议创建人') }}</em>
                <UserAvatar :userid="msg.userid" :show-icon="false" :show-name="true"/>
            </li>
            <li>
                <em>{{ $L('频道ID') }}</em>
                {{ channelID(msg.meetingid) }}
            </li>
            <li v-if="msg.end_at" class="meeting-operation">
                {{ $L('会议已结束') }}
            </li>
            <li v-else class="meeting-operation" @click="openMeeting">
                {{ $L('点击加入会议') }}
                <i class="taskfont">&#xe68b;</i>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        msg: Object,
    },
    methods: {
        openMeeting() {
            this.$emit('openMeeting');
        },
        channelID(meetingId) {
            return meetingId.replace(/^(.{3})(.{3})(.*)$/, '$1 $2 $3');
        },
    },
}
</script>
