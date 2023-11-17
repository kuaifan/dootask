<template>
    <div v-if="show" class="common-guide">
        <img :src="src" width="100%" />
        <div>
            <Button type="default" @click="show = false" size="large" >{{ $L('忍心拒绝') }}</Button>
        </div>
    </div>
</template>

<script>

export default {
    name: 'GuidePage',
    data() {
        return {
            show: false,
            src: '',
        }
    },

    mounted() {
        this.src = $.apiUrl('../images/guide.png');
    },

    watch: {
        '$route': {
            handler(route) {
                this.show = false;
                if (navigator.userAgent.indexOf("MicroMessenger") === -1) {
                    return;
                }
                //
                if (route.name == 'manage-project-invite') {
                    this.show = true;
                }
            },
            immediate: true
        },
    }
};
</script>
