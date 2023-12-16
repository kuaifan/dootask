<template>
    <div class="mobile-nav-box">
        <slot name="left">
            <div class="nav-back" @click="onBack"><i class="taskfont">&#xe676;</i></div>
        </slot>
        <div class="nav-title">{{title}}</div>
        <slot name="right"/>
    </div>
</template>

<style lang="scss" scoped>
.mobile-nav-box {
    display: flex;
    align-items: center;
    height: 44px;
    padding: 0 15px;
    background: #fff;
    position: relative;

    .nav-back {
        position: absolute;
        left: 0;
        top: 0;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 44px;
        > i {
            font-size: 22px;
            font-weight: 500;
            color: #333;
        }
    }

    .nav-title {
        flex: 1;
        font-size: 16px;
        font-weight: 500;
        color: #333;
        text-align: center;
    }
}
</style>

<script>
export default {
    name: "MobileNavTitle",
    props: {
        title: {
            default: ""
        },
        beforeBack: {
            type: Function,
        }
    },

    methods: {
        onBack() {
            if (typeof this.beforeBack === "function") {
                const before = this.beforeBack();
                if (before && before.then) {
                    before.then(() => {
                        this.goBack()
                    })
                }
                return
            }
            this.goBack()
        }
    },
};
</script>
