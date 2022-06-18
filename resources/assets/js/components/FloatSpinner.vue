<template>
    <transition name="fade">
        <div v-if="floatSpinnerLoad > 0" class="common-float-spinner">
            <Loading class="common-float-circular"></Loading>
        </div>
    </transition>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: 'FloatSpinner',
    computed: {
        ...mapState(['floatSpinnerLoad'])
    }
}
</script>
