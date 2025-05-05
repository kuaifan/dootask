<template>
    <ul class="ivu-modal-wrap-ul">
        <li>
            <div class="modal-item-img">
                <div class="apply-icon no-dark-content meeting"></div>
            </div>
            <div class="modal-item-info">
                <div class="modal-item-name">
                    <h4>{{ $L('新会议') }}</h4>
                </div>
                <p class="modal-item-desc" @click="openDetail(meetingDescs.add)"> {{ meetingDescs.add }} </p>
                <div class="modal-item-btns">
                    <Button @click="onMeeting('createMeeting')">{{ $L('新建会议') }}</Button>
                </div>
            </div>
        </li>
        <li>
            <div class="modal-item-img">
                <div class="apply-icon no-dark-content meeting-join"></div>
            </div>
            <div class="modal-item-info">
                <div class="modal-item-name">
                    <h4>{{ $L('加入会议') }}</h4>
                </div>
                <p class="modal-item-desc" @click="openDetail(meetingDescs.join)">{{ meetingDescs.join }}</p>
                <div class="modal-item-btns">
                    <Button @click="onMeeting('joinMeeting')">{{ $L('加入会议') }}</Button>
                </div>
            </div>
        </li>
    </ul>
</template>

<script>
export default {
    name: "SystemMeetingNav",
    data() {
        return {
            meetingDescs: {
                add: this.$L('创建一个全新的会议视频会议，与会者可以在实时中进行面对面的视听交流。') + this.$L('通过视频会议平台，参与者可以分享屏幕、共享文档，并与其他与会人员进行讨论和协。'),
                join: this.$L('加入视频会议，参与已经创建的会议，在会议过程中与其他参会人员进行远程实时视听交流和协作。'),
            },
        }
    },

    methods: {
        openDetail(desc){
            this.$emit("openDetail", desc);
        },
        onMeeting(name) {
            this.$emit("onMeeting", name);
        },
    }
}
</script>
