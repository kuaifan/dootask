<template>
    <div class="setting-item submit">
        <Form ref="formDatum" label-width="auto" @submit.native.prevent>
            <Row class="setting-color">
                <Col span="12">{{$L('名称')}}</Col>
                <Col span="4">
                    <Tooltip :content="$L('数值越大级别越高')" max-width="auto" placement="top" transfer>
                        <Icon class="information" type="ios-information-circle-outline" /> {{$L('级别')}}
                    </Tooltip>
                </Col>
                <Col span="4">
                    <Tooltip :content="$L('任务完成时间')" max-width="auto" placement="top" transfer>
                        <Icon class="information" type="ios-information-circle-outline" /> {{$L('天数')}}
                    </Tooltip>
                </Col>
                <Col span="4">{{$L('颜色')}}</Col>
            </Row>
            <Row v-for="(item, key) in formDatum" :key="key" class="setting-color">
                <Col span="12">
                    <Input
                        v-model="item.name"
                        :maxlength="20"
                        :placeholder="$L('请输入名称')"
                        clearable
                        @on-clear="delDatum(key)"/>
                </Col>
                <Col span="4">
                    <Input v-model="item.priority" type="number"/>
                </Col>
                <Col span="4">
                    <Input v-model="item.days" type="number"/>
                </Col>
                <Col span="4">
                    <ColorPicker v-model="item.color" recommend transfer/>
                </Col>
            </Row>
            <Button type="default" icon="md-add" @click="addDatum">{{$L('添加优先级')}}</Button>
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

            formDatum: [],

            nullDatum: {
                'name': '',
                'priority': 1,
                'days': 1,
                'color': '#2D8CF0',
            }
        }
    },

    mounted() {
        this.systemSetting();
    },

    methods: {
        submitForm() {
            this.$refs.formDatum.validate((valid) => {
                if (valid) {
                    this.systemSetting(true);
                }
            })
        },

        resetForm() {
            this.formDatum = $A.cloneJSON(this.formDatum_bak);
        },

        addDatum() {
            this.formDatum.push($A.cloneJSON(this.nullDatum));
        },

        delDatum(key) {
            this.formDatum.splice(key, 1);
            if (this.formDatum.length === 0) {
                this.addDatum();
            }
        },

        systemSetting(save) {
            this.loadIng++;
            $A.apiAjax({
                url: 'system/priority?type=' + (save ? 'save' : 'get'),
                method: 'post',
                data: {
                    list: this.formDatum
                },
                complete: () => {
                    this.loadIng--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        this.$store.state.taskPriority = $A.cloneJSON(data);
                        this.formDatum = data;
                        if (this.formDatum.length === 0) {
                            this.addDatum();
                        }
                        this.formDatum_bak = $A.cloneJSON(this.formDatum);
                        if (save) {
                            $A.messageSuccess('修改成功');
                        }
                    } else {
                        if (save) {
                            $A.modalError(msg);
                        }
                    }
                }
            });
        }
    }
}
</script>
