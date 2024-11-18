<template>
    <div class="setting-item">
        <Form ref="formData" label-width="auto" @submit.native.prevent>
            <Divider orientation="left" style="margin-top:0">{{ $L('签到记录') }}</Divider>
            <div v-if="latelyLoad > 0" class="setting-checkin-load">
                <Loading/>
            </div>
            <Timeline v-else class="setting-checkin-lately">
                <TimelineItem
                    v-for="(item, key) in latelyData"
                    :key="key"
                    :color="item.section.length > 0 ? 'blue' :'#F29D38'">
                    <Icon :type="item.section.length > 0 ? 'md-checkmark-circle' : 'md-close-circle'" slot="dot"></Icon>
                    <p class="time">{{ item.date }}</p>
                    <p class="content" v-html="item.section.length > 0 ? latelySection(item.section) : $L('未签到')"></p>
                </TimelineItem>
            </Timeline>
            <div class="setting-checkin-button" @click="calendarShow=true">{{ $L('查看更多签到数据') }}</div>

            <Divider orientation="left">{{ $L('签到设置') }}</Divider>
            <div class="setting-checkin-row">
                <Tabs v-model="checkinTabs" style="margin: 0;">
                    <TabPane :label="$L('人脸签到')" name="face">
                        <Row class="setting-template">
                            <Col span="24">{{ $L('人脸图片') }}</Col>
                        </Row>
                        <Row class="setting-template">
                            <Col span="24">
                                <ImgUpload v-model="faceimgs" :num="1" :width="512" :height="512" whcut="cover"/>
                                <div class="form-tip">{{ $L('建议尺寸：500x500') }}</div>
                            </Col>
                        </Row>
                    </TabPane>
                    <TabPane :label="$L('WiFi签到')" name="mac">
                        <Alert type="success">
                            {{ $L('设备连接上指定路由器（WiFi）后自动签到。') }}
                        </Alert>
                        <Row class="setting-template">
                            <Col span="12">{{ $L('设备MAC地址') }}</Col>
                            <Col span="12">{{ $L('备注') }}</Col>
                        </Row>
                        <Row v-for="(item, key) in formData" :key="key" class="setting-template">
                            <Col span="12">
                                <Input
                                    v-model="item.mac"
                                    :maxlength="20"
                                    :placeholder="$L('请输入设备MAC地址')"
                                    clearable
                                    @on-clear="delDatum(key)"/>
                            </Col>
                            <Col span="12">
                                <Input v-model="item.remark" :maxlength="100" :placeholder="$L('备注')"/>
                            </Col>
                        </Row>
                        <Button type="default" icon="md-add" @click="addDatum">{{ $L('添加设备') }}</Button>
                    </TabPane>
                </Tabs>
            </div>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>

        <Modal
            v-model="calendarShow"
            :title="$L('签到数据')"
            footer-hide
            :mask-closable="false">
            <CheckinCalendar ref="calendar" :loadIng="calendarLoading > 0" :checkin="calendarData" @changeMonth="changeMonth"/>
        </Modal>
    </div>
</template>

<script>
import CheckinCalendar from "../components/CheckinCalendar";
import ImgUpload from "../../../components/ImgUpload";
export default {
    name: "ManageCheckin",
    components: {CheckinCalendar, ImgUpload},

    data() {
        return {
            loadIng: 0,

            formData: [],
            faceimgs: [],

            nullDatum: {
                'mac': '',
                'remark': '',
            },
            checkinTabs: "face",

            latelyLoad: 0,
            latelyData: [],

            calendarShow: false,
            calendarLoading: 0,
            calendarData: [],
        }
    },

    mounted() {
        this.initData();
        this.getLately();
    },

    watch: {
        calendarShow(val) {
            if (val) {
                this.$nextTick(_ => {
                    this.changeMonth(this.$refs.calendar.ym());
                })
            }
        }
    },

    methods: {
        initData() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'users/checkin/get',
            }).then(({data}) => {
                this.formData = data.list.length > 0 ? data.list : [$A.cloneJSON(this.nullDatum)];
                this.faceimgs = data.faceimg
                this.formData_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    const list = this.formData
                        .filter(item => /^[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}:[A-Fa-f\d]{2}$/.test(item.mac.trim()))
                        .map(item => {
                            return {
                                mac: item.mac.trim(),
                                remark: item.remark.trim()
                            }
                        });
                    const faceimg = $A.arrayLength(this.faceimgs) > 0 ? this.faceimgs[0].url : ''
                    //
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/checkin/save',
                        data: {
                            type: this.checkinTabs,
                            list,
                            faceimg,
                        },
                        method: 'post',
                    }).then(({data}) => {
                        this.formData = data.list;
                        this.faceimgs = data.faceimg
                        this.formData_bak = $A.cloneJSON(this.formData);
                        $A.messageSuccess('修改成功');
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
                        this.loadIng--;
                    });
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        },

        addDatum() {
            this.formData.push($A.cloneJSON(this.nullDatum));
        },

        delDatum(key) {
            this.formData.splice(key, 1);
            if (this.formData.length === 0) {
                this.addDatum();
            }
        },

        getLately() {
            this.latelyLoad++
            this.$store.dispatch("call", {
                url: 'users/checkin/list',
                data: {
                    ym: $A.daytz().format("YYYY-MM"),
                    before: 1
                }
            }).then(({data}) => {
                this.latelyFormat(data)
            }).finally(_ => {
                this.latelyLoad--
            })
        },

        latelyFormat(data) {
            this.latelyData = [];
            for (let i = 0; i < 5; i++) {
                const ymd = $A.daytz().subtract(i, 'day').format("YYYY-MM-DD")
                const item = data.find(({date}) => date == ymd) || {date: ymd, section: []}
                this.latelyData.push(item)
            }
        },

        latelySection(section) {
            return section.map(item => {
                return `${item[0]} - ${item[1] || 'None'}`
            }).join('<br/>')
        },

        changeMonth(ym) {
            setTimeout(_ => {
                this.calendarLoading++;
            }, 600)
            this.$store.dispatch("call", {
                url: 'users/checkin/list',
                data: {
                    ym,
                    before: 1
                }
            }).then(({data}) => {
                if (this.$refs.calendar.ym() != ym) {
                    return;
                }
                this.calendarData = data;
                //
                if (ym == $A.daytz().format("YYYY-MM")) {
                    this.latelyFormat(data)
                }
            }).catch(({msg}) => {
                this.calendarData = [];
                $A.modalError(msg);
            }).finally(_ => {
                this.calendarLoading--;
            })
        }
    }
}
</script>
