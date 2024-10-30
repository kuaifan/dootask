<template>
    <div class="content-text content-word-chain no-dark-content">
        <pre v-html="$A.formatTextMsg(msg.text, userId)"></pre>
        <ul :class="{'expand': unfoldWordChainData.indexOf(msgId) !== -1 }">
            <li v-for="(item) in (msg.list || []).filter(h=>h.type == 'case')">
                {{ $L('例') }} {{ item.text }}
            </li>
            <li v-for="(item, index) in (msg.list || []).filter(h=>h.type != 'case' && h.text)">
                <span class="expand" v-if="index == 2 && msg.list.length > 4" @click="unfoldWordChain">
                    ...{{ $L('展开') }}...
                </span>
                <span :class="{'shrink': index >= 2 && msg.list.length > 4 } ">
                    {{ index + 1 }}. {{ item.text }}
                </span>
            </li>
            <li @click="onWordChain" class="participate">
                {{ $L('参与接龙') }}
                <i class="taskfont">&#xe703;</i>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    props: {
        msg: Object,
        msgId: Number,
        unfoldWordChainData: Array,
    },
    methods: {
        unfoldWordChain() {
            this.$emit('unfoldWordChain');
        },
        onWordChain() {
            this.$emit('onWordChain');
        },
    },
}
</script>
