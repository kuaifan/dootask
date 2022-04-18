<template>
    <div class="chat-emoji-wrapper">
        <ul class="chat-emoji-box overlay-y" :class="type === 'emoji' ? ['no-dark-mode', 'emoji'] : [type]">
            <li v-for="item in list" @click="onSelect(item)">
                <img v-if="item.type === 'emoticon'" :src="item.src" :title="item.name" :alt="item.name"/>
                <span v-else v-html="item.html" :title="item.name"></span>
            </li>
        </ul>
        <ul class="chat-emoji-menu">
            <li :class="{active: type === 'emoji'}" @click="type='emoji'">
                <span class="no-dark-mode">&#128512;</span>
            </li>
            <li v-for="item in emoticonList" :class="{active: type === 'emoticon' && emoticonPath == item.path}" @click="onEmoticon(item.path)">
                <img :title="item.name" :alt="item.name" :src="item.src"/>
            </li>
        </ul>
    </div>
</template>

<script>

export default {
    name: 'ChatEmoji',
    props: {

    },
    data() {
        return {
            type: 'emoji',
            emoticonPath: '',
        };
    },
    mounted() {

    },
    computed: {
        list() {
            if (this.type === 'emoji') {
                if (!$A.isArray(window.emojiData)) {
                    return [];
                }
                return window.emojiData.sort(function (a, b) {
                    return a.emoji_order - b.emoji_order;
                }).map(item => {
                    return {
                        type: 'emoji',
                        name: item.name,
                        html: item.code_decimal,
                    }
                })
            } else if (this.type === 'emoticon') {
                const data = this.emoticonList.find(({path}) => path === this.emoticonPath)
                if (data) {
                    return data.list;
                }
            }
            return [];
        },
        emoticonList() {
            if ($A.isArray(window.emoticonData)) {
                let baseUrl = $A.apiUrl("../images/emoticon")
                return window.emoticonData.map(data => {
                    data.src = `${baseUrl}/${data.path}/${data.icon}`
                    data.list = data.list.map(item => {
                        item.type = `emoticon`
                        item.asset = `images/emoticon/${data.path}/${item.path}`
                        item.src = `${baseUrl}/${data.path}/${item.path}`
                        return item
                    })
                    return data;
                });
            }
            return [];
        }
    },
    methods: {
        onEmoticon(path) {
            this.type = 'emoticon';
            this.emoticonPath = path;
        },

        onSelect(item) {
            this.$emit('on-select', item)
        }
    }
}
</script>
