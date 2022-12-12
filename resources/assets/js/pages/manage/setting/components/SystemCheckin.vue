<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('自动签到') }}</h3>
                <FormItem :label="$L('功能开启')" prop="open">
                    <RadioGroup v-model="formData.open">
                        <Radio label="open">{{ $L('开启') }}</Radio>
                        <Radio label="close">{{ $L('关闭') }}</Radio>
                    </RadioGroup>
                    <div class="export-data">
                        <p @click="allUserShow=true">{{$L('管理成员MAC地址')}}</p>
                        <p @click="exportShow=true">{{$L('导出签到数据')}}</p>
                    </div>
                </FormItem>
                <template v-if="formData.open === 'open'">
                    <FormItem :label="$L('允许修改')" prop="edit">
                        <RadioGroup v-model="formData.edit">
                            <Radio label="open">{{ $L('允许') }}</Radio>
                            <Radio label="close">{{ $L('禁止') }}</Radio>
                        </RadioGroup>
                        <div class="form-tip">{{$L('允许成员自己修改MAC地址')}}</div>
                    </FormItem>
                    <FormItem :label="$L('安装说明')" prop="explain">
                        <p>1. {{$L('签到延迟时长为±1分钟。')}}</p>
                        <p>2. {{$L('设备连接上指定路由器（WiFi）后自动签到。')}}</p>
                        <p>3. {{$L('仅支持Openwrt系统的路由器。')}}</p>
                        <p>4. {{$L('关闭签到功能再开启需要重新安装。')}}</p>
                        <p>5. {{$L('进入路由器终端执行以下命令即可完成安装：')}}</p>
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
                    <div class="form-tip">{{$L('每次最多选择导出20个成员')}}</div>
                </FormItem>
                <FormItem :label="$L('签到日期')">
                    <DatePicker
                        v-model="exportData.date"
                        type="daterange"
                        format="yyyy/MM/dd"
                        style="width:100%"
                        :placeholder="$L('请选择签到日期')"/>
                    <div class="form-tip page-setting-checkin-export-common">
                        {{$L('快捷选择')}}:
                        <em @click="exportData.date=dateShortcuts('prev')">{{$L('上个月')}}</em>
                        <em @click="exportData.date=dateShortcuts('this')">{{$L('这个月')}}</em>
                    </div>
                </FormItem>
                <FormItem :label="$L('签到班次')">
                    <TimePicker
                        v-model="exportData.time"
                        type="timerange"
                        format="HH:mm"
                        style="width:100%"
                        :placeholder="$L('请选择签到班次')"/>
                    <div class="form-tip page-setting-checkin-export-common">
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

        <!--查看所有团队-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="1380">
            <TeamManagement v-if="allUserShow" checkin-mac/>
        </DrawerOverlay>
    </div>
</template>

<script>
import UserInput from "../../../../components/UserInput";
import DrawerOverlay from "../../../../components/DrawerOverlay";
import TeamManagement from "../../components/TeamManagement";
export default {
    name: "SystemCheckin",
    components: {TeamManagement, DrawerOverlay, UserInput},
    data() {
        return {
            loadIng: 0,

            formData: {
                open: '',
                edit: '',
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

            allUserShow: false,

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
