<template>
    <div>
        <p v-for="(item, index) in items" :key="index" :style="item.style">{{ item.content }}</p>
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
    computed: {
        items({msg}) {
            const {content} = msg;
            if ($A.isArray(content)) {
                return content.map(item => this.formatContent(item))
            } else {
                return [this.formatContent(content)];
            }
        },
    },
    methods: {
        formatContent(item) {
            if ($A.isJson(item)) {
                return {
                    content: item.language === false || this.msg.source === 'api' ? item.content : this.$L(item.content),
                    style: item.style || {},
                };
            }
            return {
                content: this.$L(item),
                style: {},
            };
        },
    },
}
</script>
