<template>
    <div class="page-404">
        <div class="flex-center position-ref full-height">
            <div class="code">404</div>
            <div class="message">Not Found</div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.page-404 {
    background-color: #fff;
    color: #636b6f;
    font-weight: 400;
    height: 100vh;
    margin: 0;

    .full-height {
        height: 100vh;
    }

    .flex-center {
        align-items: center;
        display: flex;
        justify-content: center;
    }

    .position-ref {
        position: relative;
    }

    .code {
        border-right: 2px solid;
        font-size: 26px;
        padding: 0 15px 0 15px;
        text-align: center;
    }

    .message {
        font-size: 18px;
        padding: 10px;
        text-align: center;
    }
}
</style>
