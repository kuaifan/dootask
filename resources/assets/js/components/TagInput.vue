<template>
    <div class="common-tag-input" :class="{focus:isFocus}" @paste="pasteText($event)" @click="focus">
        <Draggable
            :list="disSource"
            :animation="150"
            tag="ul"
            draggable=".column-item"
        >
            <div class="tags-item column-item" v-for="(text, index) in disSource" :key="text">
                <span class="tags-content" @click.stop="edit(disSource,index)">{{text}}</span><span class="tags-del" @click.stop="delTag(index)">&times;</span>
            </div>
        </Draggable>
        <textarea
            ref="myTextarea"
            class="tags-input"
            v-model="content"
            :style="{ minWidth: minWidth + 'px' }"
            :placeholder="tis || placeholderText"
            :enterkeyhint="enterkeyhint"
            @keydown.enter="downEnter($event)"
            @keydown.delete="onBackspace($event)"
            @keyup="onKeyup"
            @focus="onFocus"
            @blur="onBlur"
            :disabled="disabled"
            :readonly="readonly"/>
        <span ref="myPlaceholder" v-if="showPlaceholder || tis !== ''" class="tags-placeholder">{{tis || placeholderText}}</span>
    </div>
</template>

<script>
    import Draggable from 'vuedraggable'
    export default {
        name: 'TagInput',
        components: {Draggable},
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
            enterkeyhint: {
                type: String,
                default: ''
            },
        },
        data() {
            return {
                minWidth: 80,

                tis: '',
                tisTimeout: null,

                showPlaceholder: true,

                content: '',

                disSource: this.parseValue(this.value),

                isFocus: false,

                editShow: false,
                editData:{
                    index:0,
                    disSource:[],
                    name:""
                },
                addRule: {
                    name: [
                        { required: true, message: this.$L('请填写名称！'), trigger: 'change' },
                    ]
                },
            }
        },
        mounted() {
            this.wayMinWidth();
        },
        watch: {
            placeholder() {
                this.wayMinWidth();
            },
            value(val) {
                this.disSource = this.parseValue(val);
            },
            disSource(val) {
                this.$emit('input', val.join(this.joinChar()));
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
            normalizedCuts() {
                const raw = Array.isArray(this.cut) ? this.cut : [this.cut];
                return raw.filter(c => typeof c === 'string' && c.length > 0);
            },
            cutPattern() {
                const cuts = this.normalizedCuts();
                if (cuts.length === 0) {
                    return null;
                }
                const escaped = cuts.map(c => c.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
                return new RegExp(escaped.join('|'), 'g');
            },
            joinChar() {
                return this.normalizedCuts()[0] || ',';
            },
            parseValue(val) {
                const list = [];
                if (typeof val !== 'string' || val === '') {
                    return list;
                }
                val.split(this.joinChar()).forEach(item => {
                    const value = item.trim();
                    if (value && list.indexOf(value) === -1) {
                        list.push(value);
                    }
                });
                return list;
            },
            splitByCuts(str) {
                const pattern = this.cutPattern();
                return pattern ? str.split(pattern) : [str];
            },
            showTis(msg) {
                this.tis = msg;
                clearTimeout(this.tisTimeout);
                this.tisTimeout = setTimeout(() => { this.tis = ''; }, 2000);
            },
            edit(disSource,index){
                this.editData.disSource = disSource
                this.editData.index = index
                this.editData.name = disSource[index] + ''
                $A.modalInput({
                    title: `编辑`,
                    placeholder: `请输入名称`,
                    okText: "确定",
                    value: disSource[index] + '',
                    onOk: (desc) => {
                        const trimmed = (desc || '').trim()
                        if (!trimmed) {
                            return `请输入名称`
                        }
                        const exists = this.disSource.indexOf(trimmed)
                        if (exists !== -1 && exists !== this.editData.index) {
                            return `该标签已存在`
                        }
                        this.editData.name = trimmed
                        this.editData.disSource[this.editData.index] = trimmed
                        this.$set(this.disSource, this.editData.index, trimmed)
                        return false
                    },
                });
            },
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
                if (!content) {
                    return;
                }
                for (const item of this.splitByCuts(content)) {
                    const value = item.trim();
                    if (!value) continue;
                    if (this.addTag(false, value) === false) break;
                }
            },
            downEnter(e) {
                if (e.isComposing || e.key === 'Process' || e.keyCode === 229) {
                    return;
                }
                e.preventDefault();
                this.addTag(e, this.content);
                this.$nextTick(() => {
                    this.$emit("on-enter", e)
                })
            },
            onBackspace(e) {
                if (e.isComposing || e.key === 'Process' || e.keyCode === 229) {
                    return;
                }
                this.delTag(false);
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
                if (e.keyCode !== 13) {
                    this.addTag(e, this.content);
                }
                this.$emit("on-keyup", e)
            },
            addTag(e, content) {
                const isForce = e === false || e.keyCode === 13;
                let value;
                if (isForce) {
                    value = content.trim();
                } else {
                    if (content === '') return true;
                    let matchedCut = null;
                    for (const c of this.normalizedCuts()) {
                        if (c.length <= content.length && content.substring(content.length - c.length) === c) {
                            matchedCut = c;
                            break;
                        }
                    }
                    if (matchedCut === null) return true;
                    value = content.substring(0, content.length - matchedCut.length).trim();
                }
                if (value === '') {
                    this.content = '';
                    return true;
                }
                if (this.max > 0 && this.disSource.length >= this.max) {
                    this.content = '';
                    this.showTis(this.$L('最多只能添加(*)个', this.max));
                    return false;
                }
                if (this.disSource.indexOf(value) !== -1) {
                    this.content = '';
                    this.showTis(this.$L('该标签已存在'));
                    return true;
                }
                this.disSource.push(value);
                this.content = '';
                return true;
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
