<template>
    <div class="td-action" :style="tdStyle" :data-width="width" :data-height="height">
        <div
            ref="action"
            class="td-action-container"
            :class="{'td-action-menu':menu.length > 0}"
            @mouseenter="handleIn"
            v-resize="onResize">
            <slot></slot>
            <ETooltip
                v-for="(item, key) in menu"
                placement="top"
                :key="key"
                :disabled="!item.title"
                :content="item.title"
                :enterable="false"
                :open-delay="600">
                <EDropdown
                    v-if="item.children && item.children.length > 0"
                    size="medium"
                    trigger="click"
                    class="menu-dropdown"
                    @command="onClick">
                    <i
                        v-if="isAliIcon(item.icon)"
                        class="taskfont menu-icon"
                        v-html="item.icon"
                        :style="item.style || {}"/>
                    <Icon
                        v-else
                        class="menu-icon"
                        :type="item.icon"
                        :style="item.style || {}"/>
                    <EDropdownMenu slot="dropdown">
                        <EDropdownItem
                            v-for="(d, k) in item.children"
                            :key="k"
                            :command="d.action"
                            :divided="!!d.divided"
                            :style="d.style || {}">
                            <div>{{d.title}}</div>
                        </EDropdownItem>
                    </EDropdownMenu>
                </EDropdown>
                <i
                    v-else-if="isAliIcon(item.icon)"
                    class="taskfont menu-icon"
                    v-html="item.icon"
                    :style="item.style || {}"
                    @click="onClick(item.action)"/>
                <Icon
                    v-else
                    class="menu-icon"
                    :type="item.icon"
                    :style="item.style || {}"
                    @click="onClick(item.action)"/>
            </ETooltip>
        </div>
    </div>
</template>

<script>
import VueResizeObserver from "vue-resize-observer";
import Vue from 'vue'
Vue.use(VueResizeObserver);

    export default {
        name: 'TableAction',
        props: {
            column: {
                type: Object,
                default: () => {
                    return {};
                }
            },
            autoWidth: {
                type: Boolean,
                default: true
            },
            minWidth: {
                type: Number,
                default: 80
            },
            align: {
                type: String,
                default: ''
            },
            menu: {
                type: Array,
                default: () => {
                    return [];
                }
            },
        },
        data() {
            return {
                width: 0,
                height: 0,
            }
        },
        mounted() {
            this.onUpdate();
        },
        activated() {
            this.onUpdate();
        },
        beforeUpdate() {
            this.onUpdate();
        },
        computed: {
            tdStyle() {
                const style = {};
                const {align} = this;
                switch (align.toLowerCase()) {
                    case 'left':
                        style.justifyContent = 'flex-start';
                        break;
                    case 'center':
                        style.justifyContent = 'center';
                        break;
                    case 'right':
                        style.justifyContent = 'flex-end';
                        break;
                }
                return style;
            }
        },
        methods: {
            isAliIcon(icon) {
                return $A.leftExists(icon, '&#')
            },
            handleIn() {
                if (this.$refs.action.offsetWidth != this.width) {
                    this.onUpdate();
                }
            },
            onUpdate() {
                this.onResize({
                    width: this.$refs.action.offsetWidth,
                    height: this.$refs.action.offsetHeight,
                })
            },
            onResize({ width, height }) {
                if (!this.autoWidth) {
                    return;
                }
                $A(".ivu-table-column-" + this.column.__id).each((index, el) => {
                    let action = $A(el).find(".td-action-container")
                    if (action.length > 0) {
                        width = Math.max(width, action[0].offsetWidth)
                        height = Math.max(height, action[0].offsetHeight)
                    }
                });
                this.width = width;
                this.height = height;
                let newWidth = Math.max(this.minWidth, this.width + 26);
                if (this.column.minWidth) {
                    newWidth = Math.max(this.column.minWidth, newWidth);
                }
                if (this.column.maxWidth) {
                    newWidth = Math.min(this.column.maxWidth, newWidth);
                }
                if (newWidth != this.column.width) {
                    this.$nextTick(() => {
                        this.$set(this.column, 'width', newWidth);
                    })
                }
            },
            onClick(action) {
                this.$emit("action", action)
            }
        }
    }
</script>
