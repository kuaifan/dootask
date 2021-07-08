<template>
    <div :id="id" class="luckysheet-component">
        <Loading v-if="loadIng" class="luckysheet-loading"/>
    </div>
</template>

<style lang="scss" scoped>
    .luckysheet-component {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        .luckysheet-loading {
            width: 32px;
            height: 32px;
        }
    }
</style>
<script>
import XLSX from "xlsx";

export default {
    name: 'LuckySheet',
    props: {
        id: {
            type: String,
            default: () => {
                return  "luckysheet_" + Math.round(Math.random() * 10000);
            }
        },
        value: {
            type: [Object, Array],
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
            sheetLoaded: false,

            bakValue: '',
        }
    },
    mounted() {
        $A.loadScriptS([
            'js/luckysheet/plugins/css/pluginsCss.css',
            'js/luckysheet/plugins/plugins.css',
            'js/luckysheet/css/luckysheet.css',
            'js/luckysheet/assets/iconfont/iconfont.css',
            //
            'js/luckysheet/plugins/js/plugin.js',
            'js/luckysheet/luckysheet.umd.js',
        ], () => {
            this.loadIng = false;
            this.bakValue = JSON.stringify(this.value);
            this.loadSheet(this.value);
        });
    },
    watch: {
        value: {
            handler(value) {
                if (this.bakValue == JSON.stringify(value)) {
                    return;
                }
                this.bakValue = JSON.stringify(value);
                this.loadSheet(value);
            },
            deep: true
        }
    },
    methods: {
        loadSheet(value) {
            let lang = 'zh';
            switch (this.getLanguage()) {
                case 'CN':
                case 'TC':
                    lang = 'zh';
                    break;
                default:
                    lang = 'en';
                    break;
            }
            //
            this.sheetLoaded && window.luckysheet.destroy();
            this.sheetLoaded = true;
            let config = {
                container: this.id,
                showinfobar: false,
                plugins: [
                    'chart'
                ],
                lang: lang,
                loading: {
                    image: 'image://' + $A.originUrl('js/luckysheet/css/loading.gif')
                },
                data: value ? $A.cloneJSON(value) : [
                    {
                        "name": "Sheet1",
                    }
                ],
                hook:{
                    updated: () => {
                        this.updateData();
                    },
                    sheetActivate: () => {
                        this.$nextTick(this.updateData);
                    }
                },
            };
            if (this.readOnly) {
                config.showtoolbar = false;
                config.allowEdit = false;
                config.enableAddRow = false;
                config.enableAddBackTop = false;
                config.showsheetbarConfig = {
                    add: false
                };
                config.sheetRightClickConfig = {
                    delete: false, // 删除
                    copy: false, // 复制
                    rename: false, //重命名
                    color: false, //更改颜色
                    hide: false, //隐藏，取消隐藏
                    move: false, //向左移，向右移
                };
            }
            window.luckysheet.create(config);
        },

        updateData() {
            const value = window.luckysheet.getAllSheets();
            value.forEach((item) => {
                delete item.ch_width;
                delete item.rh_height;
                delete item.index;
            });
            this.bakValue = JSON.stringify(value);
            this.$emit('input', value);
        },

        chatatABC(n) {
            var orda = 'a'.charCodeAt(0);
            var ordz = 'z'.charCodeAt(0);
            var len = ordz - orda + 1;
            var s = "";
            while (n >= 0) {
                s = String.fromCharCode(n % len + orda) + s;
                n = Math.floor(n / len) - 1;
            }
            return s.toUpperCase();
        },

        exportExcel(bookName, bookType) {
            let allSheetData = window.luckysheet.getluckysheetfile();
            let SheetNames = [];
            let Sheets = {};
            allSheetData.forEach((sheetData) => {
                let downOriginData = sheetData.data;
                let arr = [];  // 所有的单元格数据组成的二维数组
                let bgConfig = {};
                let cellValue = null;
                // 获取单元格的背景色
                let setBackground = (row, col, bg) => {
                    var colA = this.chatatABC(col);
                    var key = colA + (row + 1);
                    bgConfig[key] = bg.replace(/\#?/, '');
                }
                // 获取二维数组
                for (let row = 0; row < downOriginData.length; row++) {
                    let arrRow = [];
                    for (let col = 0; col < downOriginData[row].length; col++) {
                        cellValue = downOriginData[row][col]
                        if (cellValue) {
                            // 处理单元格的背景颜色
                            if (cellValue.bg) {
                                setBackground(row, col, cellValue.bg)
                            }
                            if (cellValue.ct != null && cellValue.ct.t == 'd') {
                                //  d为时间格式  2019-01-01   或者2019-01-01 10:10:10
                                arrRow.push(new Date(cellValue.m.replace(/\-/g, '/'))) //兼容IE
                            } else if (cellValue.m && this.isPercentage(cellValue)) {
                                //百分比问题
                                arrRow.push(cellValue.m)
                            } else {
                                arrRow.push(cellValue.v)
                            }
                        }
                    }
                    arr.push(arrRow)
                }
                let opts = {
                    dateNF: 'm/d/yy h:mm',
                    cellDates: true,
                    cellStyles: true
                }
                let ws = XLSX.utils.aoa_to_sheet(arr, opts)
                //
                let reg = /[\u4e00-\u9fa5]/g;
                for (let key in ws) {
                    if (!ws.hasOwnProperty(key)) {
                        continue;
                    }
                    let item = ws[key]
                    if (item.t === 'd') {
                        if (item.w) {
                            //时间格式的设置
                            let arr = item.w.split(' ')
                            if (arr[1] && arr[1] == '0:00') {
                                ws[key].z = 'm/d/yy'
                            } else {
                                item.z = 'yyyy/m/d h:mm:ss'
                            }
                        }
                    } else if (item.t === 's') {
                        //百分比设置格式
                        if (item.v && !item.v.match(reg) && item.v.indexOf('%') > -1) {
                            item.t = 'n'
                            item.z = '0.00%'
                            item.v = Number.parseFloat(item.v) / 100
                        } else if (item.v && item.v.match(reg)) {
                            //含有中文的设置居中样式
                            item['s'] = {
                                alignment: {vertical: 'center', horizontal: 'center'}
                            }
                        }
                    }
                    // 设置单元格样式
                    if (bgConfig[key]) {
                        ws[key]['s'] = {
                            alignment: {vertical: 'center', horizontal: 'center'},
                            fill: {bgColor: {indexed: 32}, fgColor: {rgb: bgConfig[key]}},
                            border: {
                                top: {style: 'thin', color: {rgb: '999999'}},
                                bottom: {style: 'thin', color: {rgb: '999999'}},
                                left: {style: 'thin', color: {rgb: '999999'}},
                                right: {style: 'thin', color: {rgb: '999999'}}
                            }
                        }
                    }
                }
                // 内容
                SheetNames.push(sheetData.name)
                Sheets[sheetData.name] = Object.assign({}, ws);
                // 合并单元格配置
                let mergeConfig = sheetData.config.merge
                let mergeArr = [];
                if (JSON.stringify(mergeConfig) !== '{}') {
                    mergeArr = this.handleMergeData(mergeConfig)
                    Sheets[sheetData.name]['!merges'] = mergeArr
                }
            });
            //
            XLSX.writeFile({
                SheetNames: SheetNames,
                Sheets: Sheets
            }, bookName + "." + (bookType == 'xlml' ? 'xls' : bookType), {
                bookType: bookType || "xlsx"
            });
        },

        isPercentage(value) {
            return /%$/.test(value.m) && value.ct && value.ct.t === 'n'
        },

        handleMergeData(origin) {
            let result = []
            if (origin instanceof Object) {
                var r = "r",
                    c = "c",
                    cs = "cs",
                    rs = "rs";
                for (var key in origin) {
                    if (!origin.hasOwnProperty(key)) {
                        continue;
                    }
                    var startR = origin[key][r];
                    var endR = origin[key][r];
                    var startC = origin[key][c];
                    var endC = origin[key][c];

                    // 如果只占一行 为1 如果占两行 为2
                    if (origin[key][cs] > 0) {
                        endC = startC + (origin[key][cs] - 1);
                    }
                    if (origin[key][rs] > 0) {
                        endR = startR + (origin[key][rs] - 1);
                    }
                    // s为合并单元格的开始坐标  e为结束坐标
                    var obj = {s: {"r": startR, "c": startC}, e: {"r": endR, "c": endC}}
                    result.push(obj)
                }
            }
            return result
        }
    }
}
</script>
