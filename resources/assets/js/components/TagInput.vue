<template>
    <div class="common-tag-input" :class="{focus:isFocus}" @paste="pasteText($event)" @click="focus">
        <div class="tags-item" v-for="(text, index) in disSource">
            <span class="tags-content" @click.stop="">{{text}}</span><span class="tags-del" @click.stop="delTag(index)">&times;</span>
        </div>
        <textarea
            ref="myTextarea"
            class="tags-input"
            v-model="content"
            :style="{ minWidth: minWidth + 'px' }"
            :placeholder="tis || placeholderText"
            @keydown.enter="downEnter($event)"
            @keydown.delete="delTag(false)"
            @keyup="onKeyup"
            @focus="onFocus"
            @blur="onBlur"
            :disabled="disabled"
            :readonly="readonly"/>
        <span ref="myPlaceholder" v-if="showPlaceholder || tis !== ''" class="tags-placeholder">{{tis || placeholderText}}</span>
    </div>
</template>

<script>
    export default {
        name: 'TagInput',
        props: {
            value: {
                default: ''
            },
            cut: {
                default: ','
            },
            disabled: {
                type: Boolean,
                default: false
            },
            readonly: {
                type: Boolean,
                default: false
            },
            placeholder: {
                default: ''
            },
            max: {
                default: 0
            },
        },
        data() {
            let disSource = [];
            this.value.split(",").forEach((item) => {
                if (item) {
                    disSource.push(item)
                }
            });
            return {
                minWidth: 80,

                tis: '',
                tisTimeout: null,

                showPlaceholder: true,

                content: '',

                disSource: disSource,

                isFocus: false
            }
        },
        mounted() {
            this.wayMinWidth();
        },
        watch: {
            placeholder() {
                this.wayMinWidth();
            },
            value (val) {
                let disSource = [];
                if ($A.count(val) > 0) {
                    val.split(",").forEach((item) => {
                        if (item) {
                            disSource.push(item)
                        }
                    });
                }
                this.disSource = disSource;
            },
            disSource(val) {
                let temp = '';
                val.forEach((item) => {
                    if (temp != '') {
                        temp += this.cut;
                    }
                    temp += item;
                });
                this.$emit('input', temp);
                this.$emit('on-change');
            }
        },
        computed: {
            placeholderText() {
                if (this.disSource.length > 0) {
                    return ""
                }
                return this.placeholder
            }
        },
        methods: {
            focus(option) {
                const $el = this.$refs.myTextarea;
                $el.focus(option);
                const { cursor } = option || {};
                if (cursor) {
                    const len = $el.value.length;
                    switch (cursor) {
                        case 'start':
                            $el.setSelectionRange(0, 0);
                            break;
                        case 'end':
                            $el.setSelectionRange(len, len);
                            break;
                        default:
                            $el.setSelectionRange(0, len);
                    }
                }
            },
            wayMinWidth() {
                this.showPlaceholder = true;
                this.$nextTick(() => {
                    if (this.$refs.myPlaceholder) {
                        this.minWidth = Math.max(this.minWidth, this.$refs.myPlaceholder.offsetWidth);
                    }
                    setTimeout(() => {
                        try {
                            this.minWidth = Math.max(this.minWidth, this.$refs.myPlaceholder.offsetWidth);
                            this.showPlaceholder = false;
                        }catch (e) { }
                        if (!$A(this.$refs.myPlaceholder).is(":visible")) {
                            this.wayMinWidth();
                        }
                    }, 500);
                });
            },
            pasteText(e) {
                e.preventDefault();
                let content = (e.clipboardData || window.clipboardData).getData('text');
                this.addTag(false, content)
            },
            downEnter(e) {
                e.preventDefault();
            },
            onFocus(e) {
                this.isFocus = true;
                this.$emit("on-focus", e)
            },
            onBlur(e) {
                this.isFocus = false;
                this.addTag(false, this.content)
                this.$emit("on-blur", e)
            },
            onKeyup(e) {
                this.addTag(e, this.content);
                //
                this.$emit("on-keyup", e)
                if (e.keyCode === 13) {
                    this.$nextTick(() => {
                        this.$emit("on-enter", e)
                    })
                }
            },
            addTag(e, content) {
                if (e === false || e.keyCode === 13) {
                    if (content.trim() != '' && this.disSource.indexOf(content.trim()) === -1) {
                        this.disSource.push(content.trim());
                    }
                    this.content = '';
                    return;
                }
                if (this.max > 0 && this.disSource.length >= this.max) {
                    this.content = '';
                    this.tis = '最多只能添加' + this.max + '个';
                    clearInterval(this.tisTimeout);
                    this.tisTimeout = setTimeout(() => { this.tis = ''; }, 2000);
                    return;
                }
                let temp = content.trim();
                let cutPos = temp.length - this.cut.length;
                if (temp != '' && temp.substring(cutPos) === this.cut) {
                    temp = temp.substring(0, cutPos);
                    if (temp.trim() != '' && this.disSource.indexOf(temp.trim()) === -1) {
                        this.disSource.push(temp.trim());
                    }
                    this.content = '';
                }
            },
            delTag(index) {
                if (index === false) {
                    if (this.content !== '') {
                        return;
                    }
                    index = this.disSource.length - 1;
                }
                this.disSource.splice(index, 1);
                this.focus();
            }
        }
    }
</script>
