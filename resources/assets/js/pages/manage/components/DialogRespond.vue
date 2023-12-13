<template>
    <div class="dialog-respond">
        <div class="respond-title"><em class="no-dark-content">{{respondData.symbol}}</em>{{$L('回应详情')}} ({{respondData.userids.length}})</div>

        <div class="respond-user">
            <ul>
                <li v-for="(userid, index) in respondData.userids" :key="index" @click="openUser(userid)">
                    <UserAvatar :userid="userid" :size="32" showName/>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "DialogRespond",
    props: {
        respondData: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },

    data() {
        return {
            openIng: false,
        }
    },

    methods: {
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
