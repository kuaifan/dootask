<template>
    <div class="meeting-wrap">
        <ul>
            <li v-if="localUser.uid">
                <MeetingPlayer :player="localUser" isLocal/>
            </li>
            <li v-for="user in remoteUsers">
                <MeetingPlayer :player="user"/>
            </li>
        </ul>
    </div>
</template>

<script>
import MeetingPlayer from "./MeetingPlayer.vue";

export default {
    name: "MeetingWrap",
    components: {MeetingPlayer},
    props: {

    },
    data() {
        return {

        }
    },
}
</script>
