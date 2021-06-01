<template>
    <div class="task-priority" :style="myStyle"><slot/></div>
</template>

<style lang="scss" scoped>
:global {
    .task-priority {
        display: inline-block;
        padding: 0 6px;
        border-radius: 3px;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>

<script>
export default {
    name: "TaskPriority",
    props: {
        color: {
            default: '#ffffff'
        },
        background: {
            default: '#7DBEEA'
        },
        backgroundColor: {
            default: '#7DBEEA'
        },
    },
    data() {
        return {

        }
    },
    computed: {
        myStyle() {
            const {color, background, backgroundColor} = this;
            return {
                color: color,
                backgroundColor: backgroundColor || background,
            }
        }
    },
}
</script>
