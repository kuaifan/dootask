<template>
    <div class="quick-edit" :class="[alwaysIcon ? 'quick-always' : '']">
        <div v-if="isEdit" v-clickoutside="onClickOut" class="quick-input">
            <TagInput
                v-if="isTag"
                ref="input"
                v-model="content"
                :disabled="isLoad"
                @on-keydown="onKeydown"
                @on-blur="onBlur"/>
            <Input
                v-else
                ref="input"
                v-model="content"
                :disabled="isLoad"
                @on-keydown="onKeydown"
                @on-blur="onBlur"/>
            <div v-if="isLoad" class="quick-loading"><Loading/></div>
        </div>
        <template v-else>
            <div class="quick-text"><slot></slot></div>
            <Icon class="quick-icon" type="ios-create-outline" @click.stop="onEdit"/>
        </template>
    </div>
</template>

<script>
import clickoutside from "../directives/clickoutside";

export default {
    name: 'QuickEdit',
    directives: {clickoutside},
    props: {
        value: {

        },
        autoEdit: {

        },
        isTag: {
            type: Boolean,
            default: false
        },
        alwaysIcon: {
            type: Boolean,
            default: false
        },
        clickOutSide: {
            type: Boolean,
            default: true
        },
    },

    data() {
        return {
            isLoad: false,
            isEdit: false,
            content: ''
        }
    },

    mounted() {
        if (this.autoEdit === true) {
            this.onEdit();
        }
    },

    watch: {
        autoEdit(val) {
            if (val === true) {
                setTimeout(this.onEdit, 0)
            }
        }
    },

    methods: {
        onEditChange(val) {
            this.isEdit = val;
            this.$emit("on-edit-change", val);
        },

        onEdit() {
            this.content = this.value;
            this.onEditChange(true);
            this.$nextTick(() => {
                this.$refs.input.focus({
                    cursor: 'all'
                });
            })
        },

        onKeydown(e) {
            if (e.keyCode === 13) {
                this.onEnter();
            } else if (e.keyCode === 27) {
                e.preventDefault()
                e.stopPropagation()
                this.isEdit = false;
                this.isLoad = false;
            }
        },

        onEnter() {
            if (this.content == this.value) {
                this.onEditChange(false);
                return;
            }
            if (this.isLoad) {
                return;
            }
            this.isLoad = true;
            this.$emit("input", this.content);
            this.$emit("on-update", this.content, () => {
                this.onEditChange(false);
                this.isLoad = false;
            })
        },

        onClickOut() {
            if (!this.clickOutSide) {
                return;
            }
            this.onEnter();
        },

        onBlur() {
            if (this.clickOutSide || !this.isEdit) {
                return;
            }
            this.onEnter();
        },
    }
}
</script>
