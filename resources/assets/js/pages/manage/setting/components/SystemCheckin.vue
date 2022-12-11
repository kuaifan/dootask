<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('WIFI签到') }}</h3>
                <FormItem :label="$L('功能开启')" prop="wifi">
                    <RadioGroup v-model="formData.wifi">
                        <Radio label="open">{{ $L('开启') }}</Radio>
                        <Radio label="close">{{ $L('关闭') }}</Radio>
                    </RadioGroup>
                    <div class="export-data" @click="exportShow=true">{{$L('导出签到数据')}}</div>
                </FormItem>
                <template v-if="formData.wifi === 'open'">
                    <FormItem :label="$L('功能说明')" prop="explain">
                        <p>1. {{$L('此功能仅支持手机客户端使用。')}}</p>
                        <p>2. {{$L('手机连接上指定路由器WIFI后自动签到。')}}{{$L('（注：理论上不限制连接方式）')}}</p>
                        <p>3. {{$L('签到延迟时长为±1分钟。')}}</p>
                    </FormItem>
                    <FormItem :label="$L('安装说明')" prop="install">
                        <p>1. {{$L('此功能仅支持Openwrt系统的路由器。')}}</p>
                        <p>2. {{$L('关闭签到功能再开启需要重新安装。')}}</p>
                        <p>3. {{$L('进入路由器终端执行以下命令即可完成安装：')}}</p>
                        <Input ref="cmd" @on-focus="clickCmd" style="margin-top:6px" type="textarea" readonly :value="formData.cmd"/>
                    </FormItem>
                </template>
            </div>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>

        <!--导出签到数据-->
        <Modal
            v-model="exportShow"
            :title="$L('导出签到数据')"
            :mask-closable="false">
            <Form ref="export" :model="exportData" label-width="auto" @submit.native.prevent>
                <FormItem :label="$L('导出成员')">
                    <UserInput v-model="exportData.userid" :multiple-max="20" :placeholder="$L('请选择成员')"/>
                </FormItem>
                <FormItem :label="$L('签到日期')">
                    <DatePicker
                        v-model="exportData.date"
                        type="daterange"
                        format="yyyy/MM/dd"
                        style="width:100%"
                        :placeholder="$L('请选择签到日期')"/>
                    <div class="page-setting-checkin-export-common">
                        {{$L('快捷选择')}}:
                        <em @click="exportData.date=dateShortcuts('prev')">上个月</em>
                        <em @click="exportData.date=dateShortcuts('this')">这个月</em>
                    </div>
                </FormItem>
                <FormItem :label="$L('签到时间')">
                    <TimePicker
                        v-model="exportData.time"
                        type="timerange"
                        format="HH:mm"
                        style="width:100%"
                        :placeholder="$L('请选择签到时间')"/>
                    <div class="page-setting-checkin-export-common">
                        {{$L('快捷选择')}}:
                        <em @click="exportData.time=['8:30', '18:00']">8:30-18:00</em>
                        <em @click="exportData.time=['9:00', '18:00']">9:00-18:00</em>
                        <em @click="exportData.time=['9:30', '18:00']">9:30-18:30</em>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="exportShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="exportLoadIng > 0" @click="onExport">{{$L('导出')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import UserInput from "../../../../components/UserInput";
export default {
    name: "SystemCheckin",
    components: {UserInput},
    data() {
        return {
            loadIng: 0,

            formData: {
                wifi: '',
                cmd: '',
            },
            ruleData: {},

            dateOptions: {
                shortcuts: [
                    {
                        text: this.$L('上个月'),
                        value() {
                            return [$A.getData('上个月', true), this.lastSecond($A.getData('上个月结束', true))];
                        }
                    },
                    {
                        text: this.$L('这个月'),
                        value() {
                            return [$A.getData('本周', true), this.lastSecond($A.getData('本月结束', true))];
                        }
                    }
                ]
            },

            exportShow: false,
            exportLoadIng: 0,
            exportData: {
                userid: [],
                date: [],
                time: [],
            },
        }
    },

    mounted() {
        this.systemSetting();
    },

    methods: {
        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    this.systemSetting(true);
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formDatum_bak);
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/setting/checkin?type=' + (save ? 'save' : 'all'),
                data: this.formData,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.formData = data;
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },

        dateShortcuts(act) {
            const lastSecond = (e) => {
                return $A.Date($A.formatDate("Y-m-d 23:59:29", Math.round(e / 1000)))
            };
            if (act === 'prev') {
                return [$A.getData('上个月', true), lastSecond($A.getData('上个月结束', true))];
            } else if (act === 'this') {
                return [$A.getData('本月', true), lastSecond($A.getData('本月结束', true))]
            }
        },

        clickCmd() {
            this.$nextTick(_ => {
                this.$refs.cmd.focus({cursor:'all'});
            });
        },

        onExport() {
            if (this.exportLoadIng > 0) {
                return;
            }
            this.exportLoadIng++;
            this.$store.dispatch("call", {
                url: 'system/checkin/export',
                data: this.exportData,
            }).then(({data}) => {
                this.exportShow = false;
                this.$store.dispatch('downUrl', {
                    url: data.url
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.exportLoadIng--;
            });
        }
    }
}
</script>
