<template>
    <div>
        <p>{{$L(msg.desc)}}</p>
        <p>&nbsp;</p>

        <p v-for="item in msg.list">
            <span class="mention task" :data-id="item.id">#{{item.name}}</span>
        </p>
    </div>
</template>

<script lang="ts">
export default {
    props: {
        msg: Object,
    },
    data() {
        return {};
    },
    computed: {},
    methods: {},
}
</script>
