<template>
    <div class="content-location no-dark-content">
        <div class="location-title">{{msg.title}}</div>
        <div v-if="msg.address" class="location-address">{{msg.address}}</div>
        <div class="location-preview" :style="imageStyle(msg)"></div>
    </div>
</template>
<script>
export default {
    props: {
        msg: Object,
    },
    methods: {
        imageStyle({width, height, thumb}) {
            if (!thumb) {
                return {};
            }
            const style = {
                backgroundImage: `url(${thumb})`,
            }
            if (width && height) {
                const scale = $A.scaleToScale(width, height, 600);
                style.backgroundSize = `${scale.width}px ${scale.height}px`;
            }
            return style;
        }
    }
}
</script>
