<template>
    <div class="setting-component-item system-task-priority">
        <Form ref="formDatum" label-width="auto" @submit.native.prevent>
            <Row class="setting-color color-label-box">
                <Col span="2">{{$L('默认')}}</Col>
                <Col span="10">{{$L('名称')}}</Col>
                <Col span="4">
                    <ETooltip :content="$L('数值越小级别越高')" max-width="auto" placement="top" transfer>
                        <div><Icon class="information" type="ios-information-circle-outline" /> {{$L('级别')}}</div>
                    </ETooltip>
                </Col>
                <Col span="4">
                    <ETooltip :content="$L('任务完成时间')" max-width="auto" placement="top" transfer>
                        <div><Icon class="information" type="ios-information-circle-outline" /> {{$L('天数')}}</div>
                    </ETooltip>
                </Col>
                <Col span="4">{{$L('颜色')}}</Col>
            </Row>
            <RadioGroup v-model="defaultIndex">
                <Row v-for="(item, key) in formDatum" :key="key" class="setting-color">
                    <Col span="2" class="priority-default-col">
                        <Radio :label="key"><span></span></Radio>
                    </Col>
                    <Col span="10">
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
            </RadioGroup>
            <div class="priority-add-action">
                <Button type="default" icon="md-add" @click="addDatum">{{$L('添加优先级')}}</Button>
            </div>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: 'SystemTaskPriority',
    data() {
        return {
            loadIng: 0,

            formDatum: [],
            defaultIndex: 0,

            nullDatum: {
                'name': '',
                'priority': 1,
                'days': 1,
                'color': '#84C56A',
                'is_default': 0,
            }
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['taskPriority']),
    },

    watch: {
        taskPriority: {
            handler(data) {
                this.formDatum = $A.cloneJSON(data);
                const idx = this.formDatum.findIndex(item => $A.runNum(item.is_default) === 1 || item.is_default === true || item.default);
                this.defaultIndex = idx > -1 ? idx : 0;
                this.applyDefaultIndex();
                if (this.formDatum.length === 0) {
                    this.addDatum();
                }
            },
            immediate: true,
        },
        defaultIndex() {
            this.applyDefaultIndex();
        }
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
            this.formDatum = $A.cloneJSON(this.taskPriority);
            const idx = this.formDatum.findIndex(item => $A.runNum(item.is_default) === 1 || item.is_default === true || item.default);
            this.defaultIndex = idx > -1 ? idx : 0;
            this.applyDefaultIndex();
        },

        addDatum() {
            this.formDatum.push($A.cloneJSON(this.nullDatum));
            if (this.formDatum.length === 1) {
                this.defaultIndex = 0;
                this.applyDefaultIndex();
            }
        },

        delDatum(key) {
            this.formDatum.splice(key, 1);
            if (this.formDatum.length === 0) {
                this.addDatum();
                return;
            }
            if (this.defaultIndex >= this.formDatum.length) {
                this.defaultIndex = 0;
            }
            this.applyDefaultIndex();
        },

        applyDefaultIndex() {
            this.formDatum.forEach((item, idx) => {
                item.is_default = idx === this.defaultIndex ? 1 : 0;
            })
        },

        systemSetting(save) {
            this.loadIng++;
            this.applyDefaultIndex();
            this.$store.dispatch("call", {
                url: 'system/priority?type=' + (save ? 'save' : 'get'),
                method: 'post',
                data: {
                    list: this.formDatum
                },
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.$store.state.taskPriority = $A.cloneJSON(data);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
