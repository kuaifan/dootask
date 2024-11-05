<template>
    <div class="chat-emoji-wrapper">
        <div class="chat-emoji-box">
            <div v-if="type === 'emosearch'" class="chat-emoji-emosearch">
                <Input clearable v-model="emosearchKey" :placeholder="$L('搜索表情')">
                    <Icon :type="emosearchLoad ? 'ios-loading' : 'ios-search'" :class="{'icon-loading': emosearchLoad}" slot="prefix" />
                </Input>
            </div>
            <div v-else-if="type === 'emoji'" class="chat-emoji-nav">
                <div
                    v-for="item in emojiNavList"
                    :key="item.type"
                    :class="{[`i-${item.name}`]: true, active: item.type === emojiNavActive}"
                    @click="emojiNavActive=item.type"
                    v-html="item.content"></div>
            </div>
            <Scrollbar>
                <ul :class="[type, 'no-dark-content']">
                    <li v-for="item in list" @click="onSelect($event, item)">
                        <img v-if="item.type === 'emoticon'" :src="item.src" :title="item.name" :alt="item.name"/>
                        <span v-else v-html="item.html" :title="item.name"></span>
                    </li>
                </ul>
            </Scrollbar>
        </div>
        <ul v-if="!onlyEmoji" ref="chatEmojiMenuRef" class="chat-emoji-menu" :style="chatEmojiMenuStyle" @scroll="onHandleScroll">
            <li v-if="showEmojiMenuScrollLeftBtn" @click="onEmojiMenuScroll('left')" class="left-btn"><i class="taskfont">&#xe72d;</i></li>
            <li :class="{active: type === 'emosearch'}" @click="type='emosearch'">
                <i class="taskfont">&#xe6f8;</i>
            </li>
            <li :class="{active: type === 'emoji'}" @click="type='emoji'">
                <span class="no-dark-content">&#128512;</span>
            </li>
            <li v-for="item in emoticonData" :class="{active: type === 'emoticon' && emoticonPath == item.path}" @click="onEmoticon(item.path)">
                <img :title="item.name" :alt="item.name" :src="item.src"/>
            </li>
            <li v-if="showEmojiMenuScrollRightBtn" @click="onEmojiMenuScroll('right')" class="right-btn"><i class="taskfont">&#xe733;</i></li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'ChatEmoji',
    props: {
        searchKey: {
            type: String,
            default: ''
        },
        onlyEmoji: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            type: 'emoji',

            emojiNavActive: 'p',
            emojiNavList: [
                {'type': 'p', 'name': 'people', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 256C0 114.6 114.6 0 256 0C397.4 0 512 114.6 512 256C512 397.4 397.4 512 256 512C114.6 512 0 397.4 0 256zM256 432C332.1 432 396.2 382 415.2 314.1C419.1 300.4 407.8 288 393.6 288H118.4C104.2 288 92.92 300.4 96.76 314.1C115.8 382 179.9 432 256 432V432zM176.4 160C158.7 160 144.4 174.3 144.4 192C144.4 209.7 158.7 224 176.4 224C194 224 208.4 209.7 208.4 192C208.4 174.3 194 160 176.4 160zM336.4 224C354 224 368.4 209.7 368.4 192C368.4 174.3 354 160 336.4 160C318.7 160 304.4 174.3 304.4 192C304.4 209.7 318.7 224 336.4 224z" /></svg>'},
                {'type': 'n', 'name': 'nature', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M332.7 19.85C334.6 8.395 344.5 0 356.1 0C363.6 0 370.6 3.52 375.1 9.502L392 32H444.1C456.8 32 469.1 37.06 478.1 46.06L496 64H552C565.3 64 576 74.75 576 88V112C576 156.2 540.2 192 496 192H426.7L421.6 222.5L309.6 158.5L332.7 19.85zM448 64C439.2 64 432 71.16 432 80C432 88.84 439.2 96 448 96C456.8 96 464 88.84 464 80C464 71.16 456.8 64 448 64zM416 256.1V480C416 497.7 401.7 512 384 512H352C334.3 512 320 497.7 320 480V364.8C295.1 377.1 268.8 384 240 384C211.2 384 184 377.1 160 364.8V480C160 497.7 145.7 512 128 512H96C78.33 512 64 497.7 64 480V249.8C35.23 238.9 12.64 214.5 4.836 183.3L.9558 167.8C-3.331 150.6 7.094 133.2 24.24 128.1C41.38 124.7 58.76 135.1 63.05 152.2L66.93 167.8C70.49 182 83.29 191.1 97.97 191.1H303.8L416 256.1z" /></svg>\n'},
                {'type': 'd', 'name': 'food', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M481.9 270.1C490.9 279.1 496 291.3 496 304C496 316.7 490.9 328.9 481.9 337.9C472.9 346.9 460.7 352 448 352H64C51.27 352 39.06 346.9 30.06 337.9C21.06 328.9 16 316.7 16 304C16 291.3 21.06 279.1 30.06 270.1C39.06 261.1 51.27 256 64 256H448C460.7 256 472.9 261.1 481.9 270.1zM475.3 388.7C478.3 391.7 480 395.8 480 400V416C480 432.1 473.3 449.3 461.3 461.3C449.3 473.3 432.1 480 416 480H96C79.03 480 62.75 473.3 50.75 461.3C38.74 449.3 32 432.1 32 416V400C32 395.8 33.69 391.7 36.69 388.7C39.69 385.7 43.76 384 48 384H464C468.2 384 472.3 385.7 475.3 388.7zM50.39 220.8C45.93 218.6 42.03 215.5 38.97 211.6C35.91 207.7 33.79 203.2 32.75 198.4C31.71 193.5 31.8 188.5 32.99 183.7C54.98 97.02 146.5 32 256 32C365.5 32 457 97.02 479 183.7C480.2 188.5 480.3 193.5 479.2 198.4C478.2 203.2 476.1 207.7 473 211.6C469.1 215.5 466.1 218.6 461.6 220.8C457.2 222.9 452.3 224 447.3 224H64.67C59.73 224 54.84 222.9 50.39 220.8zM372.7 116.7C369.7 119.7 368 123.8 368 128C368 131.2 368.9 134.3 370.7 136.9C372.5 139.5 374.1 141.6 377.9 142.8C380.8 143.1 384 144.3 387.1 143.7C390.2 143.1 393.1 141.6 395.3 139.3C397.6 137.1 399.1 134.2 399.7 131.1C400.3 128 399.1 124.8 398.8 121.9C397.6 118.1 395.5 116.5 392.9 114.7C390.3 112.9 387.2 111.1 384 111.1C379.8 111.1 375.7 113.7 372.7 116.7V116.7zM244.7 84.69C241.7 87.69 240 91.76 240 96C240 99.16 240.9 102.3 242.7 104.9C244.5 107.5 246.1 109.6 249.9 110.8C252.8 111.1 256 112.3 259.1 111.7C262.2 111.1 265.1 109.6 267.3 107.3C269.6 105.1 271.1 102.2 271.7 99.12C272.3 96.02 271.1 92.8 270.8 89.88C269.6 86.95 267.5 84.45 264.9 82.7C262.3 80.94 259.2 79.1 256 79.1C251.8 79.1 247.7 81.69 244.7 84.69V84.69zM116.7 116.7C113.7 119.7 112 123.8 112 128C112 131.2 112.9 134.3 114.7 136.9C116.5 139.5 118.1 141.6 121.9 142.8C124.8 143.1 128 144.3 131.1 143.7C134.2 143.1 137.1 141.6 139.3 139.3C141.6 137.1 143.1 134.2 143.7 131.1C144.3 128 143.1 124.8 142.8 121.9C141.6 118.1 139.5 116.5 136.9 114.7C134.3 112.9 131.2 111.1 128 111.1C123.8 111.1 119.7 113.7 116.7 116.7L116.7 116.7z" /></svg>'},
                {'type': 's', 'name': 'symbols', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M500.3 7.251C507.7 13.33 512 22.41 512 31.1V175.1C512 202.5 483.3 223.1 447.1 223.1C412.7 223.1 383.1 202.5 383.1 175.1C383.1 149.5 412.7 127.1 447.1 127.1V71.03L351.1 90.23V207.1C351.1 234.5 323.3 255.1 287.1 255.1C252.7 255.1 223.1 234.5 223.1 207.1C223.1 181.5 252.7 159.1 287.1 159.1V63.1C287.1 48.74 298.8 35.61 313.7 32.62L473.7 .6198C483.1-1.261 492.9 1.173 500.3 7.251H500.3zM74.66 303.1L86.5 286.2C92.43 277.3 102.4 271.1 113.1 271.1H174.9C185.6 271.1 195.6 277.3 201.5 286.2L213.3 303.1H239.1C266.5 303.1 287.1 325.5 287.1 351.1V463.1C287.1 490.5 266.5 511.1 239.1 511.1H47.1C21.49 511.1-.0019 490.5-.0019 463.1V351.1C-.0019 325.5 21.49 303.1 47.1 303.1H74.66zM143.1 359.1C117.5 359.1 95.1 381.5 95.1 407.1C95.1 434.5 117.5 455.1 143.1 455.1C170.5 455.1 191.1 434.5 191.1 407.1C191.1 381.5 170.5 359.1 143.1 359.1zM440.3 367.1H496C502.7 367.1 508.6 372.1 510.1 378.4C513.3 384.6 511.6 391.7 506.5 396L378.5 508C372.9 512.1 364.6 513.3 358.6 508.9C352.6 504.6 350.3 496.6 353.3 489.7L391.7 399.1H336C329.3 399.1 323.4 395.9 321 389.6C318.7 383.4 320.4 376.3 325.5 371.1L453.5 259.1C459.1 255 467.4 254.7 473.4 259.1C479.4 263.4 481.6 271.4 478.7 278.3L440.3 367.1zM116.7 219.1L19.85 119.2C-8.112 90.26-6.614 42.31 24.85 15.34C51.82-8.137 93.26-3.642 118.2 21.83L128.2 32.32L137.7 21.83C162.7-3.642 203.6-8.137 231.6 15.34C262.6 42.31 264.1 90.26 236.1 119.2L139.7 219.1C133.2 225.6 122.7 225.6 116.7 219.1H116.7z" /></svg>'},
                {'type': 'a', 'name': 'activity', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M16.17 337.5c0 44.98 7.565 83.54 13.98 107.9C35.22 464.3 50.46 496 174.9 496c9.566 0 19.59-.4707 29.84-1.271L17.33 307.3C16.53 317.6 16.17 327.7 16.17 337.5zM495.8 174.5c0-44.98-7.565-83.53-13.98-107.9c-4.688-17.54-18.34-31.23-36.04-35.95C435.5 27.91 392.9 16 337 16c-9.564 0-19.59 .4707-29.84 1.271l187.5 187.5C495.5 194.4 495.8 184.3 495.8 174.5zM26.77 248.8l236.3 236.3c142-36.1 203.9-150.4 222.2-221.1L248.9 26.87C106.9 62.96 45.07 177.2 26.77 248.8zM256 335.1c0 9.141-7.474 16-16 16c-4.094 0-8.188-1.564-11.31-4.689L164.7 283.3C161.6 280.2 160 276.1 160 271.1c0-8.529 6.865-16 16-16c4.095 0 8.189 1.562 11.31 4.688l64.01 64C254.4 327.8 256 331.9 256 335.1zM304 287.1c0 9.141-7.474 16-16 16c-4.094 0-8.188-1.564-11.31-4.689L212.7 235.3C209.6 232.2 208 228.1 208 223.1c0-9.141 7.473-16 16-16c4.094 0 8.188 1.562 11.31 4.688l64.01 64.01C302.5 279.8 304 283.9 304 287.1zM256 175.1c0-9.141 7.473-16 16-16c4.094 0 8.188 1.562 11.31 4.688l64.01 64.01c3.125 3.125 4.688 7.219 4.688 11.31c0 9.133-7.468 16-16 16c-4.094 0-8.189-1.562-11.31-4.688l-64.01-64.01C257.6 184.2 256 180.1 256 175.1z" /></svg>'},
                {'type': 't', 'name': 'travel', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M39.61 196.8L74.8 96.29C88.27 57.78 124.6 32 165.4 32H346.6C387.4 32 423.7 57.78 437.2 96.29L472.4 196.8C495.6 206.4 512 229.3 512 256V448C512 465.7 497.7 480 480 480H448C430.3 480 416 465.7 416 448V400H96V448C96 465.7 81.67 480 64 480H32C14.33 480 0 465.7 0 448V256C0 229.3 16.36 206.4 39.61 196.8V196.8zM109.1 192H402.9L376.8 117.4C372.3 104.6 360.2 96 346.6 96H165.4C151.8 96 139.7 104.6 135.2 117.4L109.1 192zM96 256C78.33 256 64 270.3 64 288C64 305.7 78.33 320 96 320C113.7 320 128 305.7 128 288C128 270.3 113.7 256 96 256zM416 320C433.7 320 448 305.7 448 288C448 270.3 433.7 256 416 256C398.3 256 384 270.3 384 288C384 305.7 398.3 320 416 320z" /></svg>'},
                {'type': 'o', 'name': 'objects', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M112.1 454.3c0 6.297 1.816 12.44 5.284 17.69l17.14 25.69c5.25 7.875 17.17 14.28 26.64 14.28h61.67c9.438 0 21.36-6.401 26.61-14.28l17.08-25.68c2.938-4.438 5.348-12.37 5.348-17.7L272 415.1h-160L112.1 454.3zM191.4 .0132C89.44 .3257 16 82.97 16 175.1c0 44.38 16.44 84.84 43.56 115.8c16.53 18.84 42.34 58.23 52.22 91.45c.0313 .25 .0938 .5166 .125 .7823h160.2c.0313-.2656 .0938-.5166 .125-.7823c9.875-33.22 35.69-72.61 52.22-91.45C351.6 260.8 368 220.4 368 175.1C368 78.61 288.9-.2837 191.4 .0132zM192 96.01c-44.13 0-80 35.89-80 79.1C112 184.8 104.8 192 96 192S80 184.8 80 176c0-61.76 50.25-111.1 112-111.1c8.844 0 16 7.159 16 16S200.8 96.01 192 96.01z" /></svg>'},
                {'type': 'f', 'name': 'flags', 'content': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M64 496C64 504.8 56.75 512 48 512h-32C7.25 512 0 504.8 0 496V32c0-17.75 14.25-32 32-32s32 14.25 32 32V496zM476.3 0c-6.365 0-13.01 1.35-19.34 4.233c-45.69 20.86-79.56 27.94-107.8 27.94c-59.96 0-94.81-31.86-163.9-31.87C160.9 .3055 131.6 4.867 96 15.75v350.5c32-9.984 59.87-14.1 84.85-14.1c73.63 0 124.9 31.78 198.6 31.78c31.91 0 68.02-5.971 111.1-23.09C504.1 355.9 512 344.4 512 332.1V30.73C512 11.1 495.3 0 476.3 0z" /></svg>'}
            ],
            emoticonPath: '',

            emosearchKey: '',
            emosearchCache: null,
            emosearchLoad: false,
            emosearchTimer: null,
            emosearchList: [],

            emojiData: [],
            emoticonData: [],

            emojiMenuScrollLeft: 0,
        };
    },
    mounted() {
        this.initData()
        if(!this.$store?.state.windowPortrait){
            this.onMonitorWheel()
        }
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
                return this.emojiData.filter(item => item.category === this.emojiNavActive)
            } else if (this.type === 'emosearch') {
                return this.emosearchList
            } else if (this.type === 'emoticon') {
                const data = this.emoticonData.find(({path}) => path === this.emoticonPath)
                if (data) {
                    return data.list;
                }
            }
            return [];
        },
        chatEmojiMenuStyle() {
            return {
                paddingLeft: this.showEmojiMenuScrollLeftBtn ? '34px' : 0,
                paddingRight: this.showEmojiMenuScrollRightBtn ? '34px' : 0,
            }
        },
        showEmojiMenuScrollLeftBtn(){
            return this.emojiMenuScrollLeft > 34
        },
        showEmojiMenuScrollRightBtn(){
            const container = this.$refs['chatEmojiMenuRef'];
            const liWidth = container?.querySelector('li')?.offsetWidth || 48;
            return this.emojiMenuScrollLeft < this.emoticonData.length * liWidth - (this.$store.state.windowPortrait ? 34 : 0)
        }
    },
    methods: {
        initData() {
            $A.loadScriptS([
                'js/emoji.all.js',
                'js/emoticon.all.js',
            ]).then(_ => {
                const baseUrl = $A.mainUrl("images/emoticon")
                if ($A.isArray(window.emojiData)) {
                    this.emojiData = window.emojiData.sort(function (a, b) {
                        return a.emoji_order - b.emoji_order;
                    }).map(item => {
                        return {
                            type: 'emoji',
                            name: item.name,
                            category: item.category,
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
                this.$store.dispatch("call", {
                    url: 'dialog/sticker/search',
                    data: {
                        key: this.emosearchKey,
                    },
                }).then(({data}) => {
                    this.emosearchList = []
                    const items = data.list
                    if (items.length > 0) {
                        this.emosearchList = items.map(item => {
                            return {
                                type: 'emoticon',
                                asset: 'emosearch',
                                name: item.name,
                                src: item.src,
                                height: item.height,
                                width: item.width,
                            }
                        })
                    }
                    if (this.emosearchList.length === 0) {
                        $A.messageWarning("没有搜索到任何表情")
                    }
                }).catch(_ => {
                    this.emosearchList = []
                    $A.messageWarning("搜索结果为空")
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
        },

        onMonitorWheel() {
            const container = this.$refs['chatEmojiMenuRef'];
            container?.addEventListener("wheel", (event) =>{
                event.preventDefault();
                container.scrollLeft = container.scrollLeft + event.deltaY;
            });
        },

        onEmojiMenuScroll(type) {
            const container = this.$refs['chatEmojiMenuRef'];
            const containerWidth = container.offsetWidth - 68
            const scrollLeft = type == 'right' ? container.scrollLeft + containerWidth : container.scrollLeft - containerWidth
            container.scrollTo({ left: scrollLeft, behavior: "smooth" })
        },

        onHandleScroll(event) {
            this.emojiMenuScrollLeft = event.target.scrollLeft;
        }
    }
}
</script>
