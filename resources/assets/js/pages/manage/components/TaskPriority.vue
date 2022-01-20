<template>
    <div class="task-priority" :style="myStyle"><slot/></div>
</template>

<script>
import {mapState} from "vuex";

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
        ...mapState(['themeIsDark']),

        myStyle() {
            const {color, background, backgroundColor, themeIsDark} = this;
            if (themeIsDark) {
                return {
                    color: backgroundColor || background,
                    borderColor: backgroundColor || background,
                    backgroundColor: 'transparent',
                }
            }
            return {
                color: color,
                borderColor: backgroundColor || background,
                backgroundColor: backgroundColor || background,
            }
        }
    },
}
</script>
