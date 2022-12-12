<template>
    <div class="setting-item submit">
        <Form ref="formData" label-width="auto" @submit.native.prevent>
            <Alert>
                {{$L('设备连接上指定路由器（WiFi）后自动签到。')}}
            </Alert>
            <div class="setting-checkin-button" @click="calendarShow=true">{{$L('查看我的签到数据')}}</div>
            <Row class="setting-template">
                <Col span="12">{{$L('设备MAC地址')}}</Col>
                <Col span="12">{{$L('备注')}}</Col>
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
            <Button type="default" icon="md-add" @click="addDatum">{{$L('添加设备')}}</Button>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
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
export default {
    components: {CheckinCalendar},

    data() {
        return {
            loadIng: 0,

            formData: [],

            nullDatum: {
                'mac': '',
                'remark': '',
            },

            calendarShow: false,
            calendarLoading: 0,
            calendarData: [],
        }
    },

    mounted() {
        this.initData();
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
                this.formData = data.length > 0 ? data : [$A.cloneJSON(this.nullDatum)];
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
                    //
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/checkin/save',
                        data: {list},
                        method: 'post',
                    }).then(({data}) => {
                        this.formData = data;
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

        changeMonth(ym) {
            setTimeout(_ => {
                this.calendarLoading++;
            }, 600)
            this.$store.dispatch("call", {
                url: 'users/checkin/list',
                data: {ym}
            }).then(({data}) => {
                if (this.$refs.calendar.ym() != ym) {
                    return;
                }
                this.calendarData = data;
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
