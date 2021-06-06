<template>
    <div class="dashboard">
        <PageTitle>{{$L('仪表板')}}</PageTitle>
        <div class="nopage">
            <div class="nopage-icon"><Icon type="ios-speedometer" /></div>
            <div class="nopage-text">{{$L('仪表板')}}</div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .nopage {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        .nopage-icon {
            background-color: #f4f5f7;
            padding: 20px;
            border-radius: 50%;
            .ivu-icon {
                color: #d1d8dd;
                font-size: 46px;
            }
        }
        .nopage-text {
            margin-top: 16px;
            color: #bec6cc;
            background-color: #f4f5f7;
            padding: 4px 15px;
            border-radius: 14px;
        }
    }
}
</style>
<script>
export default {
    data() {
        return {}
    },
    mounted() {

    },
}
</script>
