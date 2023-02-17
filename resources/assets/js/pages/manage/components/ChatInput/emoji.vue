<template>
    <div class="chat-emoji-wrapper">
        <div class="chat-emoji-box">
            <div v-if="type === 'emosearch'" class="chat-emoji-emosearch">
                <Input clearable v-model="emosearchKey" :placeholder="$L('搜索表情')">
                    <Icon :type="emosearchLoad ? 'ios-loading' : 'ios-search'" :class="{'icon-loading': emosearchLoad}" slot="prefix" />
                </Input>
            </div>
            <ul class="scrollbar-overlay" :class="[type, 'no-dark-content']">
                <li v-for="item in list" @click="onSelect($event, item)">
                    <img v-if="item.type === 'emoticon'" :src="item.src" :title="item.name" :alt="item.name"/>
                    <span v-else v-html="item.html" :title="item.name"></span>
                </li>
            </ul>
        </div>
        <ul class="chat-emoji-menu">
            <li :class="{active: type === 'emosearch'}" @click="type='emosearch'">
                <i class="taskfont">&#xe6f8;</i>
            </li>
            <li :class="{active: type === 'emoji'}" @click="type='emoji'">
                <span class="no-dark-content">&#128512;</span>
            </li>
            <li v-for="item in emoticonData" :class="{active: type === 'emoticon' && emoticonPath == item.path}" @click="onEmoticon(item.path)">
                <img :title="item.name" :alt="item.name" :src="item.src"/>
            </li>
        </ul>
    </div>
</template>

<script>
import { jsonp } from 'vue-jsonp'

export default {
    name: 'ChatEmoji',
    props: {
        searchKey: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            type: 'emoji',
            emoticonPath: '',

            emosearchKey: '',
            emosearchCache: null,
            emosearchLoad: false,
            emosearchTimer: null,
            emosearchList: [],

            emojiData: [],
            emoticonData: [],
        };
    },
    mounted() {
        this.initData()
    },
    watch: {
        type() {
            this.onEmosearch()
        },
        emosearchKey() {
            this.onEmosearch()
        },
        searchKey: {
            handler(val) {
                this.emosearchKey = val;
            },
            immediate: true
        }
    },
    computed: {
        list() {
            if (this.type === 'emoji') {
                return this.emojiData
            } else if (this.type === 'emosearch') {
                return this.emosearchList
            } else if (this.type === 'emoticon') {
                const data = this.emoticonData.find(({path}) => path === this.emoticonPath)
                if (data) {
                    return data.list;
                }
            }
            return [];
        }
    },
    methods: {
        initData() {
            $A.loadScriptS([
                'js/emoji.all.js',
                'js/emoticon.all.js',
            ], _ => {
                const baseUrl = $A.apiUrl("../images/emoticon")
                if ($A.isArray(window.emojiData)) {
                    this.emojiData = window.emojiData.sort(function (a, b) {
                        return a.emoji_order - b.emoji_order;
                    }).map(item => {
                        return {
                            type: 'emoji',
                            name: item.name,
                            html: item.code_decimal,
                        }
                    })
                }
                if ($A.isArray(window.emoticonData)) {
                    this.emoticonData = window.emoticonData.map(data => {
                        return Object.assign(data, {
                            src: `${baseUrl}/${data.path}/${data.icon}`,
                            list: data.list.map(item => {
                                return Object.assign(item, {
                                    type: `emoticon`,
                                    asset: `images/emoticon/${data.path}/${item.path}`,
                                    src: `${baseUrl}/${data.path}/${item.path}`
                                })
                            })
                        });
                    })
                }
            })
        },

        onEmosearch() {
            if (this.type !== 'emosearch' || this.emosearchCache === this.emosearchKey) {
                return
            }
            this.emosearchCache = this.emosearchKey;
            //
            this.emosearchLoad = true;
            this.emosearchTimer && clearTimeout(this.emosearchTimer)
            this.emosearchTimer = setTimeout(_ => {
                jsonp('https://pic.sogou.com/napi/wap/pic', {
                    query: this.emosearchKey + ' 表情'
                }).then(data => {
                    this.emosearchList = []
                    if (data.status === 0) {
                        const items = data.data.items
                        if (items.length > 0) {
                            this.emosearchList = items.map(item => {
                                return {
                                    type: 'emoticon',
                                    asset: 'emosearch',
                                    name: item.title,
                                    src: item.thumbUrl,
                                    height: item.thumbHeight,
                                    width: item.thumbWidth,
                                }
                            })
                        }
                    }
                    if (this.emosearchList.length === 0) {
                        $A.noticeWarning("没有搜索到任何表情")
                    }
                }).catch(_ => {
                    this.emosearchList = []
                    $A.noticeWarning("搜索结果为空")
                }).finally(_ => {
                    this.emosearchLoad = false;
                })
            }, 300)
        },

        onEmoticon(path) {
            this.type = 'emoticon';
            this.emoticonPath = path;
        },

        onSelect(event, item) {
            if (item.type === 'emoji') {
                this.$emit('on-select', {
                    type: 'emoji',
                    text: event.target.innerText
                })
            } else {
                this.$emit('on-select', item)
            }
        }
    }
}
</script>
