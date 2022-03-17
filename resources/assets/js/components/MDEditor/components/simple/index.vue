<template lang="html">
    <div
        :class="`markdown ${fullscreen ? 'fullscreen' : ''} ${bordered ? 'border' : ''}`"
        ref="markdown"
        :style="{ width: width + (typeof width === 'string'?'':'px'), height: height + (typeof height === 'string'?'':'px') }"
    >
        <!-- 头部工具栏 -->
        <ul class="markdown-toolbars" v-show="!preview">
            <li>
                <slot name="title"/>
            </li>
            <li v-if="tools.strong" :name="$L('粗体')">
                <span @click="insertStrong" class="iconfont icon-bold"></span>
            </li>
            <li v-if="tools.italic" :name="$L('斜体')">
                <span @click="insertItalic" class="iconfont icon-italic"></span>
            </li>
            <li v-if="tools.overline" :name="$L('删除线')">
                <span
                    @click="insertOverline"
                    class="iconfont icon-overline"
                ></span>
            </li>
            <li v-if="tools.overline" :name="$L('下划线')">
                <span
                    @click="insertUnderline"
                    class="iconfont icon-under-line"
                ></span>
            </li>
            <li v-if="tools.h1" :name="$L('标题1')">
                <span style="font-size: 16px;" @click="insertTitle(1)">h1</span>
            </li>
            <li v-if="tools.h2" :name="$L('标题2')">
                <span style="font-size: 16px;" @click="insertTitle(2)">h2</span>
            </li>
            <li v-if="tools.h3" :name="$L('标题3')">
                <span style="font-size: 16px;" @click="insertTitle(3)">h3</span>
            </li>
            <li v-if="tools.h4" :name="$L('标题4')">
                <span style="font-size: 16px;" @click="insertTitle(4)">h4</span>
            </li>
            <li v-if="tools.h5" :name="$L('标题5')">
                <span style="font-size: 16px;" @click="insertTitle(5)">h5</span>
            </li>
            <li v-if="tools.h6" :name="$L('标题6')">
                <span style="font-size: 16px;" @click="insertTitle(6)">h6</span>
            </li>
            <li v-if="tools.hr" :name="$L('分割线')">
                <span
                    @click="insertLine"
                    class="iconfont icon-horizontal"
                ></span>
            </li>
            <li v-if="tools.quote" :name="$L('引用')">
                <span
                    style="font-size: 16px;"
                    @click="insertQuote"
                    class="iconfont icon-quote"
                ></span>
            </li>
            <li v-if="tools.ul" :name="$L('无序列表')">
                <span @click="insertUl" class="iconfont icon-ul"></span>
            </li>
            <li v-if="tools.ol" :name="$L('有序列表')">
                <span @click="insertOl" class="iconfont icon-ol"></span>
            </li>
            <li v-if="tools.code" :name="$L('代码块')">
                <span @click="insertCode" class="iconfont icon-code"></span>
            </li>
            <li v-if="tools.notChecked" :name="$L('未完成列表')">
                <span
                    @click="insertNotFinished"
                    class="iconfont icon-checked-false"
                ></span>
            </li>
            <li v-if="tools.checked" :name="$L('已完成列表')">
                <span
                    @click="insertFinished"
                    class="iconfont icon-checked"
                ></span>
            </li>
            <li v-if="tools.link" :name="$L('链接')">
                <span @click="insertLink" class="iconfont icon-link"></span>
            </li>
            <li v-if="tools.image" :name="$L('图片')">
                <span @click="insertImage" class="iconfont icon-img"></span>
            </li>
            <li v-if="tools.uploadImage" :name="$L('本地图片')">
                <span @click="chooseImage" class="iconfont icon-upload-img"></span>
            </li>
            <li v-if="tools.custom_image" :name="$L('图片空间')">
                <span @click="custom_insertImage" class="iconfont icon-img"></span>
            </li>
            <li v-if="tools.custom_uploadImage" :name="$L('上传图片')">
                <span @click="custom_chooseImage" class="iconfont icon-upload-img"></span>
            </li>
            <li v-if="tools.custom_uploadFile" :name="$L('上传文件')">
                <span @click="custom_chooseFile" class="iconfont icon-upload-img"></span>
            </li>
            <li v-if="tools.table" :name="$L('表格')">
                <span @click="insertTable" class="iconfont icon-table"></span>
            </li>
            <li v-if="tools.theme" class="shift-theme" :name="$L('代码块主题')">
                <div>
                    <span
                        class="iconfont icon-theme"
                        @click="themeSlideDown = !themeSlideDown"
                    ></span>
                    <ul
                        :class="{ active: themeSlideDown }"
                        @mouseleave="themeSlideDown = false"
                    >
                        <li @click="setThemes('light')">Light</li>
                        <li @click="setThemes('dark')">VS Code</li>
                        <li @click="setThemes('oneDark')">Atom OneDark</li>
                        <li @click="setThemes('gitHub')">GitHub</li>
                    </ul>
                </div>
            </li>
            <li :name="$L('导入文件')" class="import-file" v-show="tools.importmd">
                <span class="iconfont icon-daoru" @click="importFile"></span>
                <input
                    type="file"
                    @change="importFile($event)"
                    accept="text/markdown"
                />
            </li>
            <li :name="$L('保存到本地')" v-show="tools.exportmd">
                <span class="iconfont icon-download" @click="exportFile"></span>
            </li>
            <li v-if="tools.split && split" :name="$L('全屏编辑')">
                <span @click="split = false" class="iconfont icon-md"></span>
            </li>
            <li v-if="tools.split && !split" :name="$L('分屏显示')">
                <span @click="split = true" class="iconfont icon-group"></span>
            </li>
            <li v-if="tools.preview" :name="$L('预览')">
                <span
                    @click="preview = true"
                    class="iconfont icon-preview"
                ></span>
            </li>
            <li v-if="tools.clear" :name="$L('清空')" @click="value = ''">
                <span class="iconfont icon-clear"></span>
            </li>
            <li v-if="tools.save" :name="$L('保存')" @click="handleSave">
                <span class="iconfont icon-save"></span>
            </li>
            <li :name="$L(scrolling ? '同步滚动:开' : '同步滚动:关')">
                <span
                    @click="scrolling = !scrolling"
                    v-show="scrolling"
                    class="iconfont icon-on"
                ></span>
                <span
                    @click="scrolling = !scrolling"
                    v-show="!scrolling"
                    class="iconfont icon-off"
                ></span>
            </li>
            <li class="empty"></li>
            <li v-if="tools.fullscreen && !fullscreen" :name="$L('全屏')">
                <span
                    @click="fullscreen = !fullscreen"
                    class="iconfont icon-fullscreen"
                ></span>
            </li>
            <li v-if="tools.fullscreen && fullscreen" :name="$L('退出全屏')">
                <span
                    @click="fullscreen = !fullscreen"
                    class="iconfont icon-quite"
                ></span>
            </li>
        </ul>
        <!-- 关闭预览按钮 -->
        <div
            class="close-preview"
            v-show="preview && !isPreview"
            @click="preview = false"
        >
            <span class="iconfont icon-close"></span>
        </div>
        <!-- 编辑器 -->
        <div
            class="markdown-content"
            :style="{ background: preview ? '#fff' : '' }"
        >
            <div
                v-show="!preview"
                class="markdown-editor"
                ref="markdownEditor"
                @scroll="markdownScroll"
                @mouseenter="mousescrollSide('markdown')"
            >
                <ul
                    class="index"
                    ref="index"
                    :style="{
                        height: textareaHeight ? `${textareaHeight}px` : '100%'
                    }"
                >
                    <li v-for="index in indexLenth" :key="index">
                        {{ index }}
                    </li>
                </ul>
                <textarea
                    v-model="currentValue"
                    ref="textarea"
                    @keydown.tab="tab"
                    @keyup.enter="enter"
                    @keyup.delete="onDelete"
                    @mouseenter="mousescrollSide('left')"
                    :style="{
                        height: textareaHeight ? `${textareaHeight}px` : '100%'
                    }"
                ></textarea>
            </div>
            <div
                v-show="preview ? preview : split"
                :class="`markdown-preview ${'markdown-theme-' + themeName}`"
                ref="preview"
                @scroll="previewScroll"
                @mouseenter="mousescrollSide('right')"
            >
                <div v-html="html" ref="previewInner"></div>
            </div>
        </div>
        <!--    预览图片-->
        <div :class="['preview-img', previewImgModal ? 'active' : '']">
            <span
                class="close icon-close iconfont"
                @click="previewImgModal = false"
            ></span>
            <img :src="previewImgSrc" :class="[previewImgMode]" alt=""/>
        </div>
    </div>
</template>

<script>
    import markdown from './simple';

    export default markdown;
</script>

<style scoped lang="less">
    @import "../../assets/font/iconfont.css";
    @import "../../assets/css/theme";
    @import "../../assets/css/light";
    @import "../../assets/css/dark";
    @import "../../assets/css/one-dark";
    @import "../../assets/css/github";
    @import "../../assets/css/index";
</style>
