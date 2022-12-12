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

        clickCmd() {
            this.$nextTick(_ => {
                this.$refs.cmd.focus({cursor:'all'});
            });
        },
    }
}
</script>
