<template>
    <div class="manage-box">
        <div class="manage-box-main">
            <div class="manage-box-body">
                <div class="manage-box-body-content">
                    <keep-alive>
                        <router-view class="manage-box-view"></router-view>
                    </keep-alive>
                </div>
            </div>
        </div>
    </div>
</template>


<style lang="scss" scoped>
:global {
    .manage-box {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        .manage-box-main {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            .manage-box-body {
                width: 100%;
                flex: 1;
                position: relative;
                overflow: auto;
                display: flex;
                flex-direction: column;
                .manage-box-body-content {
                    flex: 1;
                    position: relative;
                    .manage-box-view {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        overflow: auto;
                    }
                }
            }
        }
    }
}
</style>

<script>
export default {
    data() {
        return {
        }
    },
    mounted() {

    },
}
</script>
