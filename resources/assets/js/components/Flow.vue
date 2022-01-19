<template>
    <div class="flow-content">
        <iframe ref="myFlow" class="flow-iframe" :src="url"></iframe>
        <div v-if="loadIng" class="flow-loading"><Loading/></div>
        <div v-if="readOnly && zoom > 0" class="zoom-box" :class="{'zoom-ing': zoomIng}">
            <div class="zoom-svg">
                <svg t="1600613502044" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1161" width="18" height="18"><path d="M598.646154 401.723077H279.630769c-15.753846 0-27.569231 11.815385-27.569231 31.507692 0 15.753846 11.815385 27.569231 31.507693 27.569231h319.015384c15.753846 0 27.569231-11.815385 27.569231-31.507692 0-15.753846-15.753846-27.569231-31.507692-27.569231z" fill="#666666" p-id="1162"></path><path d="M921.6 850.707692l-204.8-196.923077c47.261538-59.076923 78.769231-137.846154 78.769231-220.553846 0-196.923077-157.538462-354.461538-354.461539-354.461538s-354.461538 157.538462-354.461538 354.461538 157.538462 354.461538 354.461538 354.461539c90.584615 0 173.292308-35.446154 236.307693-90.584616l204.8 196.923077c3.938462 3.938462 11.815385 7.876923 19.692307 7.876923s15.753846-3.938462 19.692308-7.876923c11.815385-15.753846 11.815385-35.446154 0-43.323077z m-484.430769-126.030769c-161.476923 0-295.384615-133.907692-295.384616-295.384615S275.692308 133.907692 437.169231 133.907692s295.384615 133.907692 295.384615 295.384616-129.969231 295.384615-295.384615 295.384615z" fill="#666666" p-id="1163"></path></svg>
                <svg t="1600613514136" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="1403" width="18" height="18"><path d="M929.476923 854.646154l-212.676923-200.861539c47.261538-59.076923 78.769231-137.846154 78.769231-220.553846 0-196.923077-157.538462-354.461538-354.461539-354.461538s-354.461538 157.538462-354.461538 354.461538 157.538462 354.461538 354.461538 354.461539c90.584615 0 173.292308-35.446154 236.307693-90.584616l212.676923 200.861539c3.938462 3.938462 11.815385 7.876923 19.692307 7.876923s15.753846-3.938462 19.692308-7.876923c11.815385-11.815385 11.815385-31.507692 0-43.323077z m-488.369231-126.030769c-161.476923 0-295.384615-133.907692-295.384615-295.384616s133.907692-295.384615 295.384615-295.384615 295.384615 133.907692 295.384616 295.384615-133.907692 295.384615-295.384616 295.384616z" fill="#666666" p-id="1404"></path><path d="M598.646154 401.723077h-129.969231V271.753846c0-15.753846-11.815385-31.507692-31.507692-31.507692s-31.507692 11.815385-31.507693 31.507692v129.969231H279.630769c-15.753846 0-31.507692 11.815385-31.507692 31.507692s11.815385 31.507692 31.507692 31.507693h129.969231V590.769231c0 15.753846 11.815385 31.507692 31.507692 31.507692s31.507692-11.815385 31.507693-31.507692v-129.969231h129.96923c15.753846 0 31.507692-11.815385 31.507693-31.507692s-15.753846-27.569231-35.446154-27.569231z" fill="#666666" p-id="1405"></path></svg>
            </div>
            <Slider class="zoom-slider" v-model="zoom" :min="1" :max="300" :tip-format="formatZoom" @on-change="zoomIng=false" @on-input="zoomIng=true"></Slider>
        </div>
    </div>
</template>

<style lang="scss" scoped>
    .flow-content {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        .flow-iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 0 0;
            border: 0;
            float: none;
            margin: -1px 0 0;
            max-width: none;
            outline: 0;
            padding: 0;
        }
        .flow-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .zoom-box {
            position: absolute;
            left: 20px;
            bottom: 20px;
            height: 34px;
            max-width: 50%;
            border-radius: 3px;
            box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
            background-color: #fff;
            color: #666;
            z-index: 10;
            padding: 0 6px;
            .zoom-svg {
                height: 34px;
                display: flex;
                align-items: center;
                .icon {
                    margin: 0 6px;
                }
            }
            .zoom-slider {
                display: none;
                padding: 0 10px;
                width: 300px;
                max-width: 100%;
            }
            &:hover,
            &.zoom-ing {
                .zoom-svg {
                    display: none;
                }
                .zoom-slider {
                    display: inline-block;
                }
            }
        }
    }
</style>
<script>
    import JSPDF from "jspdf";

    export default {
        name: "Flow",
        props: {
            value: {
                type: Object,
                default: function () {
                    return {}
                }
            },
            readOnly: {
                type: Boolean,
                default: false
            },
        },
        data() {
            return {
                loadIng: true,

                url: null,

                zoom: -1,
                zoomIng: false,

                bakData: '',
            }
        },
        created() {
            let language = 'en';
            switch (this.getLanguage()) {
                case 'CN':
                case 'TC':
                    language = 'zh'
                    break;
            }
            let route = this.readOnly ? 'viewer' : 'index';
            let theme = $A.dark.isDarkEnabled() ? 'dark' : 'light'
            this.url = $A.originUrl('js/grapheditor/' + route + '.html?lang=' + language + '&theme=' + theme);
        },
        mounted() {
            window.addEventListener('message', this.handleMessage)
        },
        beforeDestroy() {
            window.removeEventListener('message', this.handleMessage)
        },
        watch: {
            value: {
                handler(val) {
                    if (this.bakData == $A.jsonStringify(val)) {
                        return;
                    }
                    this.bakData = $A.jsonStringify(val);
                    this.updateContent();
                },
                deep: true
            },
            zoom(val) {
                this.$refs.myFlow.contentWindow.postMessage({
                    act: 'zoom',
                    params: {
                        zoom: val / 100
                    }
                }, '*')
            }
        },
        methods: {
            formatZoom(val) {
                return val + '%';
            },

            updateContent() {
                this.zoom = Math.max(1, (typeof this.value.scale === "number" ? this.value.scale : 1) * 100)
                this.$refs.myFlow.contentWindow.postMessage({
                    act: 'setXml',
                    params: Object.assign(this.value, typeof this.value.xml === "undefined" ? {
                        xml: this.value.content
                    } : {})
                }, '*')
            },

            handleMessage (event) {
                const data = event.data;
                switch (data.act) {
                    case 'ready':
                        this.loadIng = false;
                        this.updateContent();
                        break

                    case 'change':
                        this.bakData = $A.jsonStringify(data.params);
                        this.$emit('input', data.params);
                        break

                    case 'save':
                        this.$emit('saveData');
                        break

                    case 'imageContent':
                        let pdf = new JSPDF({
                            format: [data.params.width, data.params.height]
                        });
                        pdf.addImage(data.params.content, 'PNG', 0, 0, 0, 0);
                        pdf.save(`${data.params.name}.pdf`);
                        break
                }
            },

            exportPNG(name, scale = 10) {
                this.$refs.myFlow.contentWindow.postMessage({
                    act: 'exportPNG',
                    params: {
                        name: name || this.$L('无标题'),
                        scale: scale,
                        type: 'png',
                    }
                }, '*')
            },

            exportPDF(name, scale = 10) {
                this.$refs.myFlow.contentWindow.postMessage({
                    act: 'exportPNG',
                    params: {
                        name: name || this.$L('无标题'),
                        scale: scale,
                        type: 'imageContent',
                    }
                }, '*')
            }
        },
    }
</script>
