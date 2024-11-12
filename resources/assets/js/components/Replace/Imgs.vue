<template>
    <img :src="processedSrc" :alt="alt">
</template>

<script>
import {publicImageResources} from "./utils";

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
            return publicImageResources(src)
        }
    },
}
</script>

