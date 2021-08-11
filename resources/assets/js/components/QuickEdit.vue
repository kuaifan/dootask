<template>
    <div class="quick-edit">
        <div v-if="isEdit" class="quick-input">
            <Input ref="input" v-model="content" :disabled="isLoad" @on-blur="onBlur" @on-enter="onEnter"/>
            <div v-if="isLoad" class="quick-loading"><Loading/></div>
        </div>
        <template v-else>
            <div class="quick-text"><slot></slot></div>
            <Icon class="quick-icon" type="ios-create-outline" @click.stop="onEdit"/>
        </template>
    </div>
</template>

<script>
export default {
    name: 'QuickEdit',
    props: {
        value: {

        },
        autoEdit: {

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
                this.onEdit();
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

        onBlur() {
            this.onEnter();
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
        }
    }
}
</script>
