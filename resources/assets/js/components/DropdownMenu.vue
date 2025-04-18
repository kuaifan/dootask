<template>
    <EDropdown
        ref="dropdown"
        trigger="click"
        class="general-operation-dropdown"
        placement="bottom"
        :size="size"
        :style="styles"
        @command="onCommand"
        @visible-change="visibleChange">
        <div ref="icon" class="general-operation-icon"></div>
        <EDropdownMenu ref="dropdownMenu" slot="dropdown" class="general-operation-more-dropdown menu-dropdown">
            <li class="general-operation-more-warp" :class="[size]">
                <ul :style="ulStyle">
                    <EDropdownItem
                        v-for="(item, key) in list"
                        :key="key"
                        :class="item.className"
                        :command="item.value"
                        :divided="!!item.divided"
                        :disabled="(active === item.value && !activeClick) || !!item.disabled">
                        <div class="item-box">
                            <div class="item">{{item.label}}</div>
                            <div v-if="tickShow" class="tick">
                                <i v-if="active === item.value && !item.disabled" class="taskfont">&#xe684;</i>
                            </div>
                        </div>
                    </EDropdownItem>
                </ul>
            </li>
        </EDropdownMenu>
    </EDropdown>
</template>
<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            visible: false,

            list: [],           // 数据列表: [{label: '', value: ''}]
            active: '',         // 当前选中的值
            size: 'small',      // 下拉框大小
            activeClick: false, // 当前选中的值是否可以被点击
            onUpdate: null,     // 选中后的回调函数
            scrollHide: true,   // 滚动立即隐藏
            tickShow: true,     // 是否显示打勾
            maxHeight: 0,       // 滚动区域最大高度

            element: null,
            target: null,
            styles: {},
        }
    },

    beforeDestroy() {
        if (this.target) {
            this.target.removeEventListener('scroll', this.handlerEventListeners);
        }
    },

    computed: {
        ...mapState(['menuOperation']),

        ulStyle({maxHeight}) {
            return maxHeight > 0 ? {maxHeight: `${maxHeight}px`} : {};
        }
    },

    watch: {
        menuOperation(data) {
            if (data.event && data.list) {
                if (this.$refs.dropdown.visible && this.element === data.event.target) {
                    this.hide();
                    return;
                }
                const eventRect = data.event.target.getBoundingClientRect();
                this.styles = {
                    left: `${eventRect.left}px`,
                    top: `${eventRect.top}px`,
                    width: `${eventRect.width}px`,
                    height: `${eventRect.height}px`,
                }
                this.list = data.list;
                this.active = data.active && this.list.find(item => item.value === data.active) ? data.active : '';
                this.size = ['small', 'medium', 'large'].includes(data.size) ? data.size : 'small';
                this.activeClick = typeof data.activeClick === "boolean" ? data.activeClick : false;
                this.onUpdate = typeof data.onUpdate === "function" ? data.onUpdate : null;
                this.scrollHide = typeof data.scrollHide === "boolean" ? data.scrollHide : true;
                this.tickShow = typeof data.tickShow === "boolean" ? data.tickShow : true;
                this.maxHeight = typeof data.maxHeight === "number" ? data.maxHeight : 0;
                //
                this.$refs.icon.focus();
                this.show();
                this.updatePopper();
                this.setupEventListeners(data.event)
            } else {
                this.hide()
            }
        }
    },

    methods: {
        show() {
            this.$refs.dropdown.show()
        },

        hide() {
            this.$refs.dropdown.hide()
        },

        onCommand(value) {
            this.hide();
            if (typeof this.onUpdate === "function") {
                this.onUpdate(value);
            }
        },

        visibleChange(visible) {
            this.visible = visible;
        },

        updatePopper() {
            setTimeout(() => {
                this.$refs.dropdownMenu.updatePopper();
            }, 0);
        },

        setupEventListeners(event) {
            this.element = event.target;
            let target = this.getScrollParent(this.element);
            if (target === window.document.body || target === window.document.documentElement) {
                target = window;
            }
            if (this.target) {
                if (this.target === target) {
                    return;
                }
                this.target.removeEventListener('scroll', this.handlerEventListeners);
            }
            this.target = target;
            this.target.addEventListener('scroll', this.handlerEventListeners);
        },

        handlerEventListeners(e) {
            if (!this.visible || !this.element) {
                return
            }
            if (this.scrollHide) {
                this.hide();
                return;
            }
            const scrollRect = e.target.getBoundingClientRect();
            const eventRect = this.element.getBoundingClientRect();
            if (eventRect.top < scrollRect.top || eventRect.top > scrollRect.top + scrollRect.height) {
                this.hide();
                return;
            }
            this.styles = {
                left: `${eventRect.left}px`,
                top: `${eventRect.top}px`,
                width: `${eventRect.width}px`,
                height: `${eventRect.height}px`,
            };
            this.updatePopper();
        },

        getScrollParent(element) {
            const parent = element.parentNode;
            if (!parent) {
                return element;
            }
            if (parent === window.document) {
                if (window.document.body.scrollTop || window.document.body.scrollLeft) {
                    return window.document.body;
                } else {
                    return window.document.documentElement;
                }
            }
            if (
                ['scroll', 'auto'].indexOf(this.getStyleComputedProperty(parent, 'overflow')) !== -1 ||
                ['scroll', 'auto'].indexOf(this.getStyleComputedProperty(parent, 'overflow-x')) !== -1 ||
                ['scroll', 'auto'].indexOf(this.getStyleComputedProperty(parent, 'overflow-y')) !== -1
            ) {
                return parent;
            }
            return this.getScrollParent(element.parentNode);
        },

        getStyleComputedProperty(element, property) {
            const css = window.getComputedStyle(element, null);
            return css[property];
        }
    }
}
</script>
