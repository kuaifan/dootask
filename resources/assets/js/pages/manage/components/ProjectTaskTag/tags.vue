<template>
    <ul class="tags-box">
        <li
        v-for="(item, index) in items"
        :key="index"
        :style="item.style">{{item.name}}</li>
        <slot name="end"/>
    </ul>
</template>

<script>
import {colorUtils} from "./utils";

export default {
    name: "TaskTag",
    props: {
        tags: {
            default: ''
        },
        defaultColor: {
            type: String,
            default: '#84C56A'
        }
    },
    computed: {
        items({tags, defaultColor}) {
            if (!tags) return [];

            const items = $A.isArray(tags) ? tags : [tags];
            if (!items.length) return [];

            // 预先生成默认配色方案
            const defaultColors = colorUtils.generateColorScheme(null, defaultColor);

            return items.map((item, index) => {
                if (!item) return null;

                let backgroundColor;
                let name;

                if (typeof item === 'string') {
                    name = item;
                    backgroundColor = defaultColors[index % defaultColors.length];
                } else {
                    name = item.name;
                    if (!name) return null;

                    const colors = item.color ?
                        colorUtils.generateColorScheme(item.color, defaultColor) :
                        defaultColors;
                    backgroundColor = colors[index % colors.length];
                }

                return {
                    name,
                    style: {
                        backgroundColor,
                        color: colorUtils.isColorDark(backgroundColor) ? '#ffffff' : '#000000'
                    }
                }
            }).filter(Boolean);
        }
    }
}
</script>

<style lang="scss" scoped>
.tags-box {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 0;
    margin: 0;
    list-style: none;

    li {
        display: inline-flex;
        align-items: center;
        height: 24px;
        padding: 0 10px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1;
        user-select: none;

        // 文字过长时处理
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;

        // 鼠标样式
        cursor: default;
    }
}
</style>
