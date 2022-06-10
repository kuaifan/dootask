<template>
    <div class="minder-editor-container">
        <div class="quickbar">
            <ETooltip placement="top" effect="light">
                <div><i class="ft icon" :title="$L('缩放')">&#xE7B3;</i></div>
                <div slot="content" class="minder-editor-slider">
                    <Slider v-model="zoom" :min="10" :max="300"></Slider>
                </div>
            </ETooltip>
            <ETooltip v-if="readOnly!==true" placement="top" effect="light">
                <div><i class="ft icon" :title="$L('图形')">&#xE621;</i></div>
                <div slot="content">
                    <ul class="minder-editor-quickul mold">
                        <li @click="execCommand('template', 'default')"><span class="default"></span></li>
                        <li @click="execCommand('template', 'structure')"><span class="structure"></span></li>
                        <li @click="execCommand('template', 'filetree')"><span class="filetree"></span></li>
                        <li @click="execCommand('template', 'right')"><span class="right"></span></li>
                        <li @click="execCommand('template', 'fish-bone')"><span class="fish-bone"></span></li>
                        <li @click="execCommand('template', 'tianpan')"><span class="tianpan"></span></li>
                    </ul>
                </div>
            </ETooltip>
            <ETooltip v-if="readOnly!==true" placement="top" effect="light">
                <div><i class="ft icon" :title="$L('样式')">&#xE678;</i></div>
                <div slot="content">
                    <ul class="minder-editor-quickul">
                        <li @click="execCommand('theme', 'fresh-blue')">{{$L('天空蓝')}}</li>
                        <li @click="execCommand('theme', 'wire')">{{$L('线框')}}</li>
                        <li @click="execCommand('theme', 'fish')">{{$L('鱼骨图')}}</li>
                        <li @click="execCommand('theme', 'classic')">{{$L('脑图经典')}}</li>
                        <li @click="execCommand('theme', 'classic-compact')">{{$L('紧凑经典')}}</li>
                        <li @click="execCommand('theme', 'snow')">{{$L('温柔冷光')}}</li>
                        <li @click="execCommand('theme', 'snow-compact')">{{$L('紧凑冷光')}}</li>
                        <li @click="execCommand('theme', 'tianpan')">{{$L('经典天盘')}}</li>
                        <li @click="execCommand('theme', 'tianpan-compact')">{{$L('紧凑天盘')}}</li>
                    </ul>
                </div>
            </ETooltip>
            <ETooltip placement="top" effect="light">
                <div><i class="ft icon" :title="$L('折叠')">&#xE779;</i></div>
                <div slot="content">
                    <ul class="minder-editor-quickul">
                        <li @click="execCommand('ExpandToLevel', 1)">{{$L('展开到一级节点')}}</li>
                        <li @click="execCommand('ExpandToLevel', 2)">{{$L('展开到二级节点')}}</li>
                        <li @click="execCommand('ExpandToLevel', 3)">{{$L('展开到三级节点')}}</li>
                        <li @click="execCommand('ExpandToLevel', 4)">{{$L('展开到四级节点')}}</li>
                        <li @click="execCommand('ExpandToLevel', 5)">{{$L('展开到五级节点')}}</li>
                        <li @click="execCommand('ExpandToLevel', 99)">{{$L('展开全部节点')}}</li>
                    </ul>
                </div>
            </ETooltip>
            <ETooltip placement="top" :content="$L('居中')">
                <div @click="minder.execCommand('camera', minder.getRoot(), 600)"><i class="ft icon">&#xE61F;</i></div>
            </ETooltip>
            <ETooltip placement="top" :content="$L('移动')">
                <div @click="[minder.execCommand('Hand'),isHand=!isHand]"><i class="ft icon" :class="{active:isHand}">&#xE6CF;</i></div>
            </ETooltip>
        </div>
        <div :id="id"></div>
    </div>
</template>

<style lang="less" scoped>
    .basebtn {
        color: #fff;
        background-color: #409eff;
        display: inline-block;
        line-height: 1;
        white-space: nowrap;
        cursor: pointer;
        border: 1px solid #dcdfe6;
        -webkit-appearance: none;
        text-align: center;
        box-sizing: border-box;
        outline: none;
        margin: 0;
        transition: 0.1s;
        font-weight: 500;
        -moz-user-select: none;
        -webkit-user-select: none;
        -ms-user-select: none;
        padding: 8px 12px;
        font-size: 14px;
        border-radius: 4px;
    }

    .baseSelect {
        height: 32px;
        option {
            min-height: 1.5em;
        }
    }

    .quickbar {
        position: fixed;
        left: 20px;
        bottom: 20px;
        height: 34px;
        border-radius: 3px;
        box-shadow: 3px 3px 10px rgba(0,0,0,.2);
        background-color: #fff;
        color: #666;
        z-index: 10;
        display: flex;
        padding: 0 6px;
        align-items: center;
        i {
            font-size: 22px;
            width: 34px;
            height: 34px;
            line-height: 34px;
            display: block;
            text-align: center;
            transform: scale(1);
            cursor: pointer;
            padding: 0;
            color: #666666;
            font-style: normal;
            &:hover {
                color: #232323;
            }
            &.active {
                color: #0285d7;
            }
        }
    }
</style>
<style lang="less">
.minder-editor-quickul {
    li {
        list-style: none;
        cursor: pointer;
        padding: 8px 0;
        &:hover {
            color: #0285d7
        }
    }
    &.mold {
        span {
            display: block;
            width: 30px;
            background: url(data:image/png;base64,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) no-repeat;background-size: 300px 24px;
            height: 30px;
            &.default {
                background-position: 0 3px
            }
            &.structure {
                background-position: -30px 3px
            }
            &.filetree {
                background-position: -60px 3px
            }
            &.right {
                background-position: -90px 3px
            }
            &.fish-bone {
                background-position: -120px 3px
            }
            &.tianpan {
                background-position: -150px 3px
            }
        }
    }
}
.minder-editor-slider {
    width: 200px;
    margin: 0 6px;
}
</style>
<script>
    import {generateMixed} from 'vue-kityminder-ggg/src/utils/index.js';
    import 'vue-kityminder-ggg/examples/styles/minder.css';
    import JSPDF from 'jspdf';

    export default {
        name: 'mind-editor',
        props: {
            value: {
                type: Object,
                default: function () {
                    return {}
                }
            },
            AccessKey: {
                type: String,
                default: ''
            },
            SecretKey: {
                type: String,
                default: ''
            },
            Domain: {
                type: String,
                default: ''
            },
            scope: {
                type: String,
                default: ''
            },
            isImageUpload: {
                type: Boolean,
                default: true
            },
            saveShow: {
                type: Boolean,
                default: true
            },
            readOnly: {
                type: Boolean,
                default: false
            },
            id: {
                type: String,
                default: 'minder-component-' + generateMixed(12)
            },
        },
        data() {
            return {
                minder: null,
                isHand: false,
                bakValue: '',

                zoom: 100,
            };
        },
        methods: {
            execCommand(var1, var2) {
                if (this.readOnly === true) {
                    this.minder.enable();
                    this.$nextTick(() => {
                        this.minder.execCommand(var1, var2);
                        this.$nextTick(() => {
                            this.minder.disable();
                            if (this.isHand) {
                                this.minder.execCommand('Hand');
                            }
                        });
                    });
                } else {
                    this.minder.execCommand(var1, var2);
                }
            },
            exportHandle(n, filename) {
                filename = filename || (this.value.root.data.text || this.$L('无标题'));
                if (n === 0 || n === 'png') {
                    this.minder.exportData('png').then((content) => {
                        let element = document.createElement('a');
                        element.setAttribute('href', content);
                        element.setAttribute('download', filename);
                        element.style.display = 'none';
                        document.body.appendChild(element);
                        element.click();
                        document.body.removeChild(element);
                    });
                } else if (n === 1 || n === 'pdf') {
                    this.minder.exportData('png').then((content) => {
                        let doc = new JSPDF();
                        doc.addImage(content, 'PNG', 0, 0, 0, 0);
                        doc.save(`${filename}.pdf`);
                    });
                }
            },
            rendData() {
                this.$nextTick(() => {
                    setTimeout(() => {
                        if (this.minder !== null) {
                            if (this.bakValue == JSON.stringify(this.value)) {
                                return;
                            }
                            this.bakValue = JSON.stringify(this.value);
                            this.minder.importJson(this.value);
                            return;
                        }
                        window.__minderReadOnly = this.readOnly;
                        const Editor = require('./editor');
                        this.minder = window.editor = new Editor(document.getElementById(this.id)).minder;
                        this.bakValue = JSON.stringify(this.value);
                        this.minder.importJson(this.value);
                        if (this.readOnly === true) {
                            this.minder.disable();
                            this.minder.execCommand('Hand');
                            this.isHand = true;
                        }
                        this.$emit('minderHandle', this.minder);
                        this.minder.on('contentchange', e => {
                            const newJson = this.minder.exportJson();
                            if (this.bakValue == JSON.stringify(newJson)) {
                                return;
                            }
                            this.bakValue = JSON.stringify(newJson);
                            this.$emit('input', newJson);
                        });
                    }, 300)
                });
            }
        },
        watch: {
            value: {
                handler: function (newObj) {
                    if (typeof newObj !== "object" || newObj === null) {
                        newObj = {
                            root: newObj,
                            theme: "fresh-blue",
                            template: "default",
                        };
                    }
                    if (typeof newObj.root !== "object" || newObj.root === null || newObj.root.length == 0) {
                        newObj.root = {
                            data: {
                                id: generateMixed(12),
                                text: this.$L('默认节点'),
                            },
                            children: []
                        }
                    }
                    if (typeof newObj.theme !== "string") {
                        newObj.theme = "fresh-blue";
                    }
                    if (typeof newObj.template !== "string") {
                        newObj.template = "default";
                    }
                    this.rendData();
                },
                deep: true,
                immediate: true
            },
            zoom(val) {
                this.execCommand('Zoom', val)
            }
        }
    };
</script>
