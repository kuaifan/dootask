<template>
    <div class="dialog-respond">
        <div class="respond-title"><em>{{respondData.symbol}}</em>{{$L('回应详情')}} ({{respondData.userids.length}})</div>

        <div class="respond-user">
            <ul>
                <li v-for="(userid, index) in respondData.userids" :key="index">
                    <UserAvatar :userid="userid" :size="32" showName tooltipDisabled/>
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
}
</script>
