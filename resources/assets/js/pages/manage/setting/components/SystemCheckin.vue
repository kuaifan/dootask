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
                                        <InputNumber v-model="formData.advance" :min="0" :step="1" @on-change="onAdvanceBlur"/>
                                        <label>{{ $L('分钟') }}</label>
                                    </div>
                                    <div v-if="earliestCheckinTime" class="form-tip">{{ earliestCheckinTime }}</div>
                                </FormItem>
                                <FormItem :label="$L('最晚可延后')" prop="delay">
                                    <div class="input-number-box">
                                        <InputNumber v-model="formData.delay" :min="0" :step="1" @on-change="onDelayBlur"/>
                                        <label>{{ $L('分钟') }}</label>
                                    </div>
                                    <div v-if="latestCheckinTime" class="form-tip">{{ latestCheckinTime }}</div>
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
                            <FormItem :label="$L('地图类型')" prop="locat_map_type">
                                <RadioGroup v-model="formData.locat_map_type">
                                    <Radio label="baidu">{{ $L('百度地图') }}</Radio>
                                    <Radio label="amap">{{ $L('高德地图') }}</Radio>
                                    <Radio label="tencent">{{ $L('腾讯地图') }}</Radio>
                                </RadioGroup>
                                <div class="form-tip">{{$L('仅支持移动端App')}}</div>
                            </FormItem>

                            <!-- 百度地图配置 -->
                            <template v-if="formData.locat_map_type === 'baidu'">
                                <FormItem :label="$L('百度地图AK')" prop="locat_bd_lbs_key">
                                    <Input :maxlength="100" v-model="formData.locat_bd_lbs_key"/>
                                    <div class="form-tip">{{$L('获取AK流程')}}: <a href="https://lbs.baidu.com/faq/search?id=299&title=677" target="_blank">https://lbs.baidu.com/faq/search?id=299&title=677</a></div>
                                </FormItem>
                                <FormItem :label="$L('允许签到位置')" prop="locat_bd_lbs_point">
                                    <template v-if="formData.locat_bd_lbs_point.lng">
                                        <div class="form-tip">
                                            <a href="javascript:void(0)" @click="openMapSelect">
                                                {{ $L(`经度：${formData.locat_bd_lbs_point.lng}，纬度：${formData.locat_bd_lbs_point.lat}，半径：${formData.locat_bd_lbs_point.radius}米`) }}
                                            </a>
                                        </div>
                                        <div class="form-tip" @click="openMapSelect">{{$L('点击修改允许签到位置')}}</div>
                                    </template>
                                    <a v-else href="javascript:void(0)" @click="openMapSelect">{{$L('点击设置')}}</a>
                                </FormItem>
                            </template>

                            <!-- 高德地图配置 -->
                            <template v-if="formData.locat_map_type === 'amap'">
                                <FormItem :label="$L('高德地图Key')" prop="locat_amap_key">
                                    <Input :maxlength="100" v-model="formData.locat_amap_key"/>
                                    <div class="form-tip">{{$L('获取Key流程')}}: <a href="https://lbs.amap.com/api/javascript-api/guide/abc/prepare" target="_blank">https://lbs.amap.com/api/javascript-api/guide/abc/prepare</a></div>
                                </FormItem>
                                <FormItem :label="$L('允许签到位置')" prop="locat_amap_point">
                                    <template v-if="formData.locat_amap_point.lng">
                                        <div class="form-tip">
                                            <a href="javascript:void(0)" @click="openMapSelect">
                                                {{ $L(`经度：${formData.locat_amap_point.lng}，纬度：${formData.locat_amap_point.lat}，半径：${formData.locat_amap_point.radius}米`) }}
                                            </a>
                                        </div>
                                        <div class="form-tip" @click="openMapSelect">{{$L('点击修改允许签到位置')}}</div>
                                    </template>
                                    <a v-else href="javascript:void(0)" @click="openMapSelect">{{$L('点击设置')}}</a>
                                </FormItem>
                            </template>

                            <!-- 腾讯地图配置 -->
                            <template v-if="formData.locat_map_type === 'tencent'">
                                <FormItem :label="$L('腾讯地图Key')" prop="locat_tencent_key">
                                    <Input :maxlength="100" v-model="formData.locat_tencent_key"/>
                                    <div class="form-tip">{{$L('获取Key流程')}}: <a href="https://lbs.qq.com/dev/console/application/mine" target="_blank">https://lbs.qq.com/dev/console/application/mine</a></div>
                                </FormItem>
                                <FormItem :label="$L('允许签到位置')" prop="locat_tencent_point">
                                    <template v-if="formData.locat_tencent_point.lng">
                                        <div class="form-tip">
                                            <a href="javascript:void(0)" @click="openMapSelect">
                                                {{ $L(`经度：${formData.locat_tencent_point.lng}，纬度：${formData.locat_tencent_point.lat}，半径：${formData.locat_tencent_point.radius}米`) }}
                                            </a>
                                        </div>
                                        <div class="form-tip" @click="openMapSelect">{{$L('点击修改允许签到位置')}}</div>
                                    </template>
                                    <a v-else href="javascript:void(0)" @click="openMapSelect">{{$L('点击设置')}}</a>
                                </FormItem>
                            </template>
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

        <!--查看管理团队-->
        <DrawerOverlay
            v-model="allUserShow"
            placement="right"
            :size="1380">
            <TeamManagement v-if="allUserShow" checkin-mode/>
        </DrawerOverlay>

        <!--地图选择签到位置-->
        <Modal
            v-model="mapSelectShow"
            :title="$L('允许签到位置')"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: '1000px'
            }">
            <div>
                <div class="map-select-container">
                    <div class="map-select-iframe-container">
                        <IFrame v-if="mapSelectShow" ref="mapSelectIframe" class="map-select-point-iframe" :src="mapSelectUrl" @on-message="onMapMessage"/>
                    </div>
                    <div class="map-radius-control">
                        <div class="radius-control-header">
                            <h4>{{ $L('签到半径设置') }}</h4>
                        </div>
                        <div class="radius-control-body">
                            <Input :value="mapSelectPoint.radius" @on-change="onRadiusChange" @on-blur="onRadiusBlur">
                                <span slot="prepend">{{ $L('半径') }}</span>
                                <span slot="append">{{ $L('米') }}</span>
                            </Input>
                            <div class="location-info">
                                <div class="info-item">
                                    <span class="info-label">{{ $L('经度') }}：</span>
                                    <span class="info-value">{{ mapSelectPoint.lng || '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">{{ $L('纬度') }}：</span>
                                    <span class="info-value">{{ mapSelectPoint.lat || '-' }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">{{ $L('半径') }}：</span>
                                    <span class="info-value">{{ mapSelectPoint.radius || '-' }} {{ $L('米') }}</span>
                                </div>
                            </div>
                            <div class="radius-control-tip">
                                <template v-if="formData.locat_map_type === 'baidu'">
                                    {{ $L('点击地图选择中心位置，拖拽圆形边缘调整半径，或在上方输入框直接设置半径值') }}
                                </template>
                                <template v-else>
                                    {{ $L('点击地图选择中心位置，在上方输入框中设置签到半径值') }}
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="mapSelectShow=false">{{$L('关闭')}}</Button>
                <Button type="primary" @click="onMapSelect">{{$L('确定')}}</Button>
            </div>
        </Modal>

    </div>
</template>
<style lang="scss" scoped>
.map-select-container {
    display: flex;
    gap: 20px;
    height: 500px;
    @media (width < 768px) {
        flex-direction: column;
        height: 700px;
        .map-radius-control {
            width: 100%;
            border-left: 0;
            padding-left: 0;
        }
    }
}
.map-select-iframe-container {
    flex: 1;
}
.map-select-point-iframe {
    width: 100%;
    height: 100%;
    border: 0;
    border-radius: 12px;
}
.map-radius-control {
    width: 280px;
    border-left: 1px solid #e8e8e8;
    padding-left: 20px;
    display: flex;
    flex-direction: column;
}
.radius-control-header {
    margin-bottom: 15px;
}
.radius-control-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}
.radius-control-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.location-info {
    margin: 15px 0;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 6px;
}
.info-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}
.info-item:last-child {
    margin-bottom: 0;
}
.info-label {
    color: #666;
}
.info-value {
    color: #333;
    font-weight: 500;
}
.radius-control-tip {
    background: #f0f8ff;
    padding: 12px;
    border-radius: 6px;
    border-left: 3px solid #007cff;
    font-size: 13px;
    color: #333;
    line-height: 1.4;
    margin-top: auto;
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
                locat_map_type: 'baidu', // 地图类型
                locat_bd_lbs_key: '', // 百度地图AK
                locat_bd_lbs_point: {}, // 百度地图允许签到位置
                locat_amap_key: '', // 高德地图Key
                locat_amap_point: {}, // 高德地图允许签到位置
                locat_tencent_key: '', // 腾讯地图Key
                locat_tencent_point: {}, // 腾讯地图允许签到位置
            },
            ruleData: {},

            allUserShow: false,
            exportShow: false,

            mapSelectShow: false,
            mapSelectPoint: {},
            mapSelectUrl: '',
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['formOptions']),

        earliestCheckinTime() {
            const times = this.formData.time;
            if (!times || times.length < 1 || !times[0]) return '';
            const advance = parseInt(this.formData.advance) || 0;
            if (advance <= 0) return '';

            const startMinutes = this.timeToMinutes(times[0]);
            let earliestMinutes = startMinutes - advance;

            // 处理跨天（负数表示前一天）
            let prefix = '';
            if (earliestMinutes < 0) {
                earliestMinutes += 24 * 60;
                prefix = '(' +  this.$L('前日') + ') ';
            }

            const hours = Math.floor(earliestMinutes / 60);
            const mins = earliestMinutes % 60;
            return prefix + String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
        },

        latestCheckinTime() {
            const times = this.formData.time;
            if (!times || times.length < 2 || !times[1]) return '';
            const delay = parseInt(this.formData.delay) || 0;
            if (delay <= 0) return '';

            const endMinutes = this.timeToMinutes(times[1]);
            let latestMinutes = endMinutes + delay;

            // 处理跨天（超过24小时表示次日）
            let prefix = '';
            if (latestMinutes >= 24 * 60) {
                latestMinutes -= 24 * 60;
                prefix = '(' +  this.$L('次日') + ') ';
            }

            const hours = Math.floor(latestMinutes / 60);
            const mins = latestMinutes % 60;
            return prefix + String(hours).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
        },
    },

    methods: {
        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    this.systemSetting(true);
                }
            })
        },

        timeToMinutes(timeStr) {
            if (!timeStr) return 0;
            const parts = timeStr.split(':');
            return parseInt(parts[0]) * 60 + parseInt(parts[1]);
        },

        getMaxAllowed() {
            const times = this.formData.time;
            if (!times || times.length < 2) return null;
            const startMinutes = this.timeToMinutes(times[0]);
            const endMinutes = this.timeToMinutes(times[1]);
            let shiftDuration = endMinutes - startMinutes;
            if (shiftDuration <= 0) shiftDuration += 24 * 60;
            return 24 * 60 - shiftDuration;
        },

        onAdvanceBlur() {
            const maxAllowed = this.getMaxAllowed();
            if (maxAllowed === null) return;

            const delay = parseInt(this.formData.delay) || 0;
            const maxAdvance = maxAllowed - delay - 1;

            if (maxAdvance < 0) {
                this.formData.advance = 0;
            } else if (this.formData.advance > maxAdvance) {
                this.formData.advance = maxAdvance;
            }
        },

        onDelayBlur() {
            const maxAllowed = this.getMaxAllowed();
            if (maxAllowed === null) return;

            const advance = parseInt(this.formData.advance) || 0;
            const maxDelay = maxAllowed - advance - 1;

            if (maxDelay < 0) {
                this.formData.delay = 0;
            } else if (this.formData.delay > maxDelay) {
                this.formData.delay = maxDelay;
            }
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

        openMapSelect() {
            const mapType = this.formData.locat_map_type;
            let mapKey = '';
            let currentPoint = {};

            // 根据地图类型获取对应的key和point
            switch (mapType) {
                case 'baidu':
                    mapKey = this.formData.locat_bd_lbs_key;
                    currentPoint = this.formData.locat_bd_lbs_point;
                    if (!mapKey) {
                        $A.messageError('请先填写百度地图AK');
                        return;
                    }
                    break;
                case 'amap':
                    mapKey = this.formData.locat_amap_key;
                    currentPoint = this.formData.locat_amap_point;
                    if (!mapKey) {
                        $A.messageError('请先填写高德地图Key');
                        return;
                    }
                    break;
                case 'tencent':
                    mapKey = this.formData.locat_tencent_key;
                    currentPoint = this.formData.locat_tencent_point;
                    if (!mapKey) {
                        $A.messageError('请先填写腾讯地图Key');
                        return;
                    }
                    break;
                default:
                    $A.messageError('请选择地图类型');
                    return;
            }

            const selectPage = `select_${mapType}.html`;
            const url = $A.urlAddParams($A.mainUrl(`tools/map/${selectPage}`), {
                key: mapKey,
                point: currentPoint.lng + ',' + currentPoint.lat,
                radius: currentPoint.radius,
            })

            this.$store.dispatch('userUrl', url).then(newUrl => {
                this.mapSelectUrl = newUrl;
                this.mapSelectPoint = currentPoint;
                this.mapSelectShow = true;
            });
        },

        onMapMessage(data) {
            const expectedAction = `${this.formData.locat_map_type}_lbs_select_point`;
            if (data.action !== expectedAction) {
                return;
            }
            this.mapSelectPoint = {
                lng: parseFloat(data.longitude),
                lat: parseFloat(data.latitude),
                radius: parseInt(data.radius),
            }
        },

        onRadiusChange({target}) {
            const value = parseInt(target.value);
            if (value && value >= 50 && value <= 5000) {
                this.mapSelectPoint.radius = value;
                const iframe = this.$refs.mapSelectIframe;
                iframe?.postMessage({
                    action: 'update_radius',
                    radius: value
                })
            }
        },

        onRadiusBlur({target}) {
            target.value = this.mapSelectPoint.radius;
        },

        onMapSelect() {
            const mapType = this.formData.locat_map_type;
            switch (mapType) {
                case 'baidu':
                    this.formData.locat_bd_lbs_point = this.mapSelectPoint;
                    break;
                case 'amap':
                    this.formData.locat_amap_point = this.mapSelectPoint;
                    break;
                case 'tencent':
                    this.formData.locat_tencent_point = this.mapSelectPoint;
                    break;
            }
            this.mapSelectShow = false;
        },
    }
}
</script>
