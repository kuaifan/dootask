<template>
    <svg viewBox="25 25 50 50" class="common-loading"><circle cx="50" cy="50" r="20" fill="none" stroke-width="5" stroke-miterlimit="10" class="common-path"></circle></svg>
</template>

<style lang="scss" scoped>
:global {
    .common-loading {
        transform-origin: center center;
        width: 30px;
        height: 30px;
        max-width: 100%;
        max-height: 100%;
        margin: auto;
        overflow: hidden;
        &:local {
            animation: rotate 2s linear infinite;
        }

        .common-path {
            stroke-dasharray: 1, 200;
            stroke-dashoffset: 0;
            stroke-linecap: round;
            &:local {
                animation: dash 1.5s ease-in-out infinite, color 6s ease-in-out infinite;
            }
        }
    }
    @keyframes rotate {
        to {
            -webkit-transform: rotate(1turn);
            transform: rotate(1turn)
        }
    }
    @keyframes dash {
        0% {
            stroke-dasharray: 1, 200;
            stroke-dashoffset: 0
        }

        50% {
            stroke-dasharray: 89, 200;
            stroke-dashoffset: -35
        }

        to {
            stroke-dasharray: 89, 200;
            stroke-dashoffset: -124
        }
    }
    @keyframes color {
        0%, to {
            stroke: #d62d20
        }

        40% {
            stroke: #0057e7
        }

        66% {
            stroke: #008744
        }

        80%, 90% {
            stroke: #ffa700
        }
    }
}
</style>
<script>
    export default {
        name: 'Loading',
    }
</script>
