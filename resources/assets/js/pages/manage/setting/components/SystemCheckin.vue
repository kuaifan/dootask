<template>
    <div class="setting-component-item">
        <Form
            ref="formData"
            :model="formData"
            :rules="ruleData"
            v-bind="formOptions"
            @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('签到设置') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('功能开启')" prop="open">
                        <RadioGroup v-model="formData.open">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                        <div class="export-data">
                            <p @click="allUserShow=true">{{$L('会员签到设置')}}</p>
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
                            <RadioGroup v-model="formData.face_upload">
                                <Radio label="open">{{ $L('允许') }}</Radio>
                                <Radio label="close">{{ $L('禁止') }}</Radio>
                            </RadioGroup>
                            <div class="form-tip">{{$L('允许成员自己上传人脸图片')}}</div>
                            <RadioGroup v-model="formData.edit">
                                <Radio label="open">{{ $L('允许') }}</Radio>
                                <Radio label="close">{{ $L('禁止') }}</Radio>
                            </RadioGroup>
                            <div class="form-tip">{{$L('允许成员自己修改MAC地址')}} ({{$L('WiFi签到')}})</div>
                        </FormItem>
                        <FormItem :label="$L('签到方式')" prop="modes">
                            <CheckboxGroup v-model="formData.modes">
                                <Checkbox label="face">{{$L('人脸签到')}}</Checkbox>
                                <Checkbox label="auto">{{$L('WiFi签到')}}</Checkbox>
                                <Checkbox label="locat">{{$L('定位签到')}}</Checkbox>
                                <Checkbox label="manual">{{$L('手动签到')}}</Checkbox>
                            </CheckboxGroup>
                            <div v-if="formData.modes.includes('face')" class="form-tip">{{$L('人脸签到')}}: {{$L('通过人脸识别机签到')}}</div>
                            <div v-if="formData.modes.includes('auto')" class="form-tip">{{$L('WiFi签到')}}: {{$L('详情看下文安装说明')}}</div>
                            <div v-if="formData.modes.includes('locat')" class="form-tip">{{$L('定位签到')}}: {{$L('通过在签到打卡机器人发送位置签到')}} ({{$L('仅支持移动端App')}})</div>
                            <div v-if="formData.modes.includes('manual')" class="form-tip">{{$L('手动签到')}}: {{$L('通过在签到打卡机器人发送指令签到')}}</div>
                        </FormItem>
                    </template>
                </div>
            </div>

            <template v-if="formData.open === 'open'">
                <template v-if="formData.modes.includes('face')">
                    <div class="block-setting-space"></div>
                    <div class="block-setting-box">
                        <h3>{{ $L('人脸签到') }}</h3>
                        <div class="form-box">
                            <FormItem :label="$L('签到备注')" prop="face_remark">
                                <Input :maxlength="30" v-model="formData.face_remark"/>
                            </FormItem>
                            <FormItem :label="$L('重复打卡提醒')" prop="face_retip">
                                <RadioGroup v-model="formData.face_retip">
                                    <Radio label="open">{{ $L('开启') }}</Radio>
                                    <Radio label="close">{{ $L('关闭') }}</Radio>
                                </RadioGroup>
                            </FormItem>
                        </div>
                    </div>
                </template>
                <template v-if="formData.modes.includes('auto')">
                    <div class="block-setting-space"></div>
                    <div class="block-setting-box">
                        <h3>{{ $L('WiFi签到') }}</h3>
                        <div class="form-box">
                            <FormItem :label="$L('安装说明')" prop="explain">
                                <p>1. {{ $L('WiFi签到延迟时长为±1分钟。') }}</p>
                                <p>2. {{ $L('设备连接上指定路由器（WiFi）后自动签到。') }}</p>
                                <p>3. {{ $L('仅支持Openwrt系统的路由器。') }}</p>
                                <p>4. {{ $L('关闭签到功能再开启需要重新安装。') }}</p>
                                <p>5. {{ $L('进入路由器终端执行以下命令即可完成安装') }}:</p>
                                <Input ref="cmd" @on-focus="clickCmd" style="margin-top:6px" type="textarea" readonly :value="formData.cmd"/>
                            </FormItem>
                        </div>
                    </div>
                </template>
                <template v-if="formData.modes.includes('locat')">
                    <div class="block-setting-space"></div>
                    <div class="block-setting-box">
                        <h3>{{ $L('定位签到') }}</h3>
                        <div class="form-box">
                            <FormItem :label="$L('签到备注')" prop="locat_remark">
                                <Input :maxlength="30" v-model="formData.locat_remark"/>
                            </FormItem>
                            <FormItem :label="$L('百度地图AK')" prop="locat_bd_lbs_key">
                                <Input :maxlength="100" v-model="formData.locat_bd_lbs_key"/>
                                <div class="form-tip">{{$L('获取AK流程')}}: <a href="https://lbs.baidu.com/faq/search?id=299&title=677" target="_blank">https://lbs.baidu.com/faq/search?id=299&title=677</a></div>
                            </FormItem>
                            <FormItem :label="$L('允许签到位置')" prop="locat_bd_allow_point">
                                <ETooltip v-if="formData.locat_bd_lbs_point.lng" :content="$L('点击修改')">
                                    <a href="javascript:void(0)" @click="openBdSelect">
                                        {{ $L(`经度：${formData.locat_bd_lbs_point.lng}，纬度：${formData.locat_bd_lbs_point.lat}，半径：${formData.locat_bd_lbs_point.radius}米`) }}
                                    </a>
                                </ETooltip>
                                <a v-else href="javascript:void(0)" @click="openBdSelect">{{$L('点击设置')}}</a>
                            </FormItem>
                        </div>
                    </div>
                </template>
                <template v-if="formData.modes.includes('manual')">
                    <div class="block-setting-space"></div>
                    <div class="block-setting-box">
                        <h3>{{ $L('手动签到') }}</h3>
                        <div class="form-box">
                            <FormItem :label="$L('签到备注')" prop="manual_remark">
                                <Input :maxlength="30" v-model="formData.manual_remark"/>
                            </FormItem>
                        </div>
                    </div>
                </template>
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
            <TeamManagement v-if="allUserShow" checkin-mode/>
        </DrawerOverlay>

        <!--百度选择签到位置-->
        <Modal
            v-model="bdSelectShow"
            :title="$L('允许签到位置')"
            :mask-closable="false"
            width="800">
            <div>
                <div v-if="bdSelectPoint.radius" class="bd-select-point-tip">{{ $L(`签到半径${bdSelectPoint.radius}米`) }}</div>
                <div v-else class="bd-select-point-tip">{{ $L('请点击地图选择签到位置') }}</div>
                <IFrame v-if="bdSelectShow" class="bd-select-point-iframe" :src="bdSelectUrl" @on-message="onBdMessage"/>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="bdSelectShow=false">{{$L('关闭')}}</Button>
                <Button type="primary" @click="onBdSelect">{{$L('确定')}}</Button>
            </div>
        </Modal>

    </div>
</template>
<style lang="scss" scoped>
.bd-select-point-tip {
    font-size: 16px;
    text-align: center;
    margin-bottom: 12px;
    margin-top: -12px;
}
.bd-select-point-iframe {
    width: 100%;
    height: 500px;
    border: 0;
    border-radius: 12px;
}
</style>
<script>
import DrawerOverlay from "../../../../components/DrawerOverlay";
import TeamManagement from "../../components/TeamManagement";
import CheckinExport from "../../components/CheckinExport";
import {mapState} from "vuex";
import IFrame from "../../components/IFrame.vue";
export default {
    name: "SystemCheckin",
    components: {IFrame, CheckinExport, TeamManagement, DrawerOverlay},
    data() {
        return {
            loadIng: 0,

            formData: {
                open: '',
                edit: '',
                cmd: '',
                modes: [],
                face_upload: '',
                face_remark: '',
                face_retip: '',
                manual_remark: '',
                locat_remark: '',
                locat_bd_lbs_point: {},
            },
            ruleData: {},

            allUserShow: false,
            exportShow: false,

            bdSelectShow: false,
            bdSelectPoint: {},
            bdSelectUrl: '',
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['formOptions']),
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

        openBdSelect() {
            if (!this.formData.locat_bd_lbs_key) {
                $A.messageError('请先填写百度地图AK');
                return;
            }
            const url = $A.urlAddParams($A.mainUrl('tools/map/select.html'), {
                key: this.formData.locat_bd_lbs_key,
                point: this.formData.locat_bd_lbs_point.lng + ',' + this.formData.locat_bd_lbs_point.lat,
                radius: this.formData.locat_bd_lbs_point.radius,
            })
            this.$store.dispatch('userUrl', url).then(newUrl => {
                this.bdSelectUrl = newUrl;
                this.bdSelectPoint = this.formData.locat_bd_lbs_point;
                this.bdSelectShow = true;
            });
        },

        onBdMessage(data) {
            if (data.action !== 'bd_lbs_select_point') {
                return;
            }
            this.bdSelectPoint = {
                lng: data.longitude,
                lat: data.latitude,
                radius: data.radius,
            }
        },

        onBdSelect() {
            this.formData.locat_bd_lbs_point = this.bdSelectPoint;
            this.bdSelectShow = false;
        },
    }
}
</script>
