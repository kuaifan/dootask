<template>
    <div class="task-priority" :style="myStyle"><slot/></div>
</template>

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
