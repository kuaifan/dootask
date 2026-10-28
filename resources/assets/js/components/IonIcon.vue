<script>
/**
 * Ionicons（新版）SVG 图标
 *
 * 用法：
 * import {lockClosedOutline} from "ionicons/icons";
 * <IonIcon :icon="lockClosedOutline"/>
 *
 * 按需引入 ionicons/icons 导出的 data URI，内联渲染为 SVG（不使用 <ion-icon> 组件，避免运行时从 CDN 加载）
 * 尺寸跟随 font-size，颜色跟随 color
 */
export default {
    name: 'IonIcon',
    functional: true,
    props: {
        icon: {
            type: String,
            required: true
        },
    },
    render(h, {props, data}) {
        const {icon} = props
        return h('i', Object.assign({}, data, {
            staticClass: ['ion-icon', data.staticClass].filter(Boolean).join(' '),
            domProps: {
                innerHTML: icon.slice(icon.indexOf(',') + 1)
            },
        }))
    }
}
</script>
