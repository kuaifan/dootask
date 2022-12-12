<template>
    <div class="setting-item submit">
        <Form ref="formData" label-width="auto" @submit.native.prevent>
            <Alert style="margin-bottom:18px">
                {{$L('设备连接上指定路由器（WiFi）后自动签到。')}}
            </Alert>
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
    </div>
</template>

<script>
export default {
    data() {
        return {
            loadIng: 0,

            formData: [],

            nullDatum: {
                'mac': '',
                'remark': '',
            },
        }
    },

    mounted() {
        this.initData();
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
    }
}
</script>
