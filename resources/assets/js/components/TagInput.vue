<template>
    <div class="common-tag-input" @paste="pasteText($event)" @click="clickWrap">
        <div class="tags-item" v-for="(text, index) in disSource">
            <span class="tags-content" @click.stop="">{{text}}</span><span class="tags-del" @click.stop="delTag(index)">&times;</span>
        </div>
        <textarea ref="myTextarea" class="tags-input" :style="{ minWidth: minWidth + 'px' }" :placeholder="tis || placeholder"
                  v-model="content" @keydown.enter="downEnter($event)" @keyup="addTag($event, content)"
                  @blur="addTag(false, content)" @keydown.delete="delTag(false)" :disabled="disabled" :readonly="readonly"></textarea>
        <span ref="myPlaceholder" v-if="showPlaceholder || tis !== ''" class="tags-placeholder">{{tis || placeholder}}</span>
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
            }
        },
        methods: {
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
            clickWrap() {
                this.$refs.myTextarea.focus();
            },
            downEnter(e) {
                e.preventDefault();
            },
            addTag(e, content) {
                if (e.keyCode === 13 || e === false) {
                    if (content.trim() != '' && this.disSource.indexOf(content.trim()) === -1) {
                        this.disSource.push(content.trim());
                    }
                    this.content = '';
                } else {
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
                }
            },
            delTag(index) {
                if (index === false) {
                    if (this.content !== '') {
                        return;
                    }
                    index = this.disSource.length - 1;
                }
                this.disSource.splice(index, 1)
            }
        }
    }
</script>
