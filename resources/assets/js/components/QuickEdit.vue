<template>
    <div class="quick-edit" :class="[alwaysIcon ? 'quick-always' : '']">
        <div v-if="isEdit" v-clickoutside="onEnter" class="quick-input">
            <TagInput v-if="isTag" ref="input" v-model="content" :disabled="isLoad" @on-enter="onEnter"/>
            <Input v-else ref="input" v-model="content" :disabled="isLoad" @on-enter="onEnter"/>
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
        isEdit(val) {
            this.$emit("on-edit-change", val);
        },
        autoEdit(val) {
            if (val === true) {
                setTimeout(this.onEdit, 0)
            }
        }
    },

    methods: {
        onEdit() {
            this.content = this.value;
            this.isEdit = true;
            this.$nextTick(() => {
                this.$refs.input.focus({
                    cursor: 'all'
                });
            })
        },

        onEnter() {
            if (this.content == this.value) {
                this.isEdit = false;
                return;
            }
            if (this.isLoad) {
                return;
            }
            this.isLoad = true;
            this.$emit("input", this.content);
            this.$emit("on-update", this.content, () => {
                this.isEdit = false;
                this.isLoad = false;
            })
        },
    }
}
</script>
