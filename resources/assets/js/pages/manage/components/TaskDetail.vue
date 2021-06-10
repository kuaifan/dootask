<template>
    <div class="task-detail">
        {{projectTask}}
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "TaskDetail",

    data() {
        return {

        }
    },
    computed: {
        ...mapState(['userId', 'projectTask']),
    },
}
</script>
