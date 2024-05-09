<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('签到设置') }}</h3>
                <div class="form-box">
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
                        <FormItem :label="$L('签到时间')" prop="time">
                            <TimePicker
                                v-model="formData.time"
                                type="timerange"
                                format="HH:mm"
                                :placeholder="$L('请选择签到时间')"/>
                            <Form @submit.native.prevent class="block-setting-advance">
                                <FormItem :label="$L('最早可提前')" prop="advance">
                                    <div class="input-number-box">
                                        <InputNumber v-model="formData.advance" :min="0" :step="1"/>
                                        <label>{{ $L('分钟') }}</label>
                                    </div>
                                </FormItem>
                                <FormItem :label="$L('最晚可延后')" prop="delay">
                                    <div class="input-number-box">
                                        <InputNumber v-model="formData.delay" :min="0" :step="1"/>
                                        <label>{{ $L('分钟') }}</label>
                                    </div>
                                </FormItem>
                                <div class="form-tip">{{$L('签到前后时间收到消息通知')}}</div>
                                <FormItem :label="$L('签到打卡提醒')" prop="remindin">
                                    <div class="input-number-box">
                                        <InputNumber v-model="formData.remindin" :min="0" :step="1"/>
                                        <label>{{ $L('分钟') }}</label>
                                    </div>
                                </FormItem>
                                <FormItem :label="$L('签到缺卡提醒')" prop="remindexceed">
                                    <div class="input-number-box">
                                        <InputNumber v-model="formData.remindexceed" :min="0" :step="1"/>
                                        <label>{{ $L('分钟') }}</label>
                                    </div>
                                </FormItem>
                                <div class="form-tip">{{$L('签到提醒对象：3天内有签到的成员（法定工作日）')}}</div>
                            </Form>
                        </FormItem>
                        <FormItem :label="$L('允许修改')" prop="edit">
                            <RadioGroup v-model="formData.edit">
                                <Radio label="open">{{ $L('允许') }}</Radio>
                                <Radio label="close">{{ $L('禁止') }}</Radio>
                            </RadioGroup>
                            <div class="form-tip">{{$L('允许成员自己修改MAC地址')}}</div>
                        </FormItem>
                        <FormItem :label="$L('签到方式')" prop="modes">
                            <CheckboxGroup v-model="formData.modes">
                                <Checkbox label="auto">{{$L('自动签到')}}</Checkbox>
                                <Checkbox label="manual">{{$L('手动签到')}}</Checkbox>
                                <Checkbox v-if="false" label="location">{{$L('定位签到')}}</Checkbox>
                            </CheckboxGroup>
                            <div v-if="formData.modes.includes('auto')" class="form-tip">{{$L('自动签到')}}: {{$L('详情看下文安装说明')}}</div>
                            <div v-if="formData.modes.includes('manual')" class="form-tip">{{$L('手动签到')}}: {{$L('通过在签到打卡机器人发送指令签到')}}</div>
                            <div v-if="formData.modes.includes('location')" class="form-tip">{{$L('定位签到')}}: {{$L('通过在签到打卡机器人发送位置签到')}}</div>
                        </FormItem>
                    </template>
                </div>
            </div>

            <template v-if="formData.open === 'open' && formData.modes.includes('auto')">
                <div class="block-setting-space"></div>
                <div class="block-setting-box">
                    <h3>{{ $L('自动签到') }}</h3>
                    <div class="form-box">
                        <FormItem :label="$L('安装说明')" prop="explain">
                            <p>1. {{ $L('自动签到延迟时长为±1分钟。') }}</p>
                            <p>2. {{ $L('设备连接上指定路由器（WiFi）后自动签到。') }}</p>
                            <p>3. {{ $L('仅支持Openwrt系统的路由器。') }}</p>
                            <p>4. {{ $L('关闭签到功能再开启需要重新安装。') }}</p>
                            <p>5. {{ $L('进入路由器终端执行以下命令即可完成安装') }}:</p>
                            <Input ref="cmd" @on-focus="clickCmd" style="margin-top:6px" type="textarea" readonly :value="formData.cmd"/>
                        </FormItem>
                    </div>
                </div>
            </template>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>

        <!--导出签到数据-->
        <CheckinExport v-model="exportShow"/>

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
import DrawerOverlay from "../../../../components/DrawerOverlay";
import TeamManagement from "../../components/TeamManagement";
import CheckinExport from "../../components/CheckinExport";
export default {
    name: "SystemCheckin",
    components: {CheckinExport, TeamManagement, DrawerOverlay},
    data() {
        return {
            loadIng: 0,

            formData: {
                open: '',
                edit: '',
                cmd: '',
                modes: [],
            },
            ruleData: {},

            allUserShow: false,
            exportShow: false,
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
            this.formData.cmd = '';
            this.$store.dispatch("call", {
                url: 'system/setting/checkin?type=' + (save ? 'save' : 'all'),
                data: this.formData,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.formData = data;
                try {
                    this.formData.cmd = atob(this.formData.cmd);
                } catch (error) {}
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },

        clickCmd() {
            this.$nextTick(_ => {
                this.$refs.cmd.focus({cursor:'all'});
            });
        },
    }
}
</script>
