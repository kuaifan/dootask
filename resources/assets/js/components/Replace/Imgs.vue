<template>
    <img :src="processedSrc" :alt="alt">
</template>

<script>
import {convertLocalResourcePath} from "./utils";

export default {
    props: {
        src: {
            type: String,
            required: true
        },
        alt: {
            type: String,
            default: ''
        }
    },
    computed: {
        processedSrc({src}) {
            return convertLocalResourcePath(src)
        }
    },
}
</script>

