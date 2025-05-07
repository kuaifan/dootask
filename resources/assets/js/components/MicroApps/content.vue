<template>
    <div class="micro-app-content">
        <micro-app
            v-if="isOpen"
            :name="appName"
            :url="appUrl"
            :keep-alive="keepAlive"
            :data="appData"
            @created="$emit('created', $event)"
            @beforemount="$emit('beforemount', $event)"
            @mounted="$emit('mounted', $event)"
            @unmount="$emit('unmount', $event)"
            @error="$emit('error', $event)"/>
        <div v-if="isLoading" class="micro-app-loader">
            <Loading/>
        </div>
    </div>
</template>

<script>
export default {
    name: "MicroContent",
    props: {
        isOpen: {
            type: Boolean,
            required: true
        },
        appName: {
            type: String,
            required: true
        },
        appUrl: {
            type: String,
            required: true
        },
        keepAlive: {
            type: Boolean,
            default: true
        },
        isLoading: {
            type: Boolean,
            default: false
        },
        appData: {
            type: Object,
            required: true
        }
    }
}
</script>
