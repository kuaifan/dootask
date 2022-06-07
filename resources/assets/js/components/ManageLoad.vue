<template>
    <div v-if="[-1, 0].includes(userState)" class="app-view-loading">
        <div v-if="userState === -1" class="load-error" @click="onClick">
            {{$L('加载失败，点击重试')}}
        </div>
        <div v-else>
            <div>PAGE LOADING</div>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'ManageLoad',
        props: {
            userState: {
                type: Number,
            },
        },
        methods: {
            onClick() {
                this.$emit('on-click')
            }
        }
    }
</script>
