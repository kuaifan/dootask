<template>
    <div>
        <p>{{$L(msg.title)}}</p>
        <p>&nbsp;</p>

        <p v-for="item in msg.list">
            <span class="mention task" :data-id="item.id">#{{item.name}}</span>
        </p>
    </div>
</template>

<script>
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
