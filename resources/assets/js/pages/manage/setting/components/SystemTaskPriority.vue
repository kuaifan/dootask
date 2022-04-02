<template>
    <div class="setting-component-item">
        <Form ref="formDatum" label-width="auto" @submit.native.prevent>
            <Row class="setting-color">
                <Col span="12">{{$L('名称')}}</Col>
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
import {mapState} from "vuex";

export default {
    name: 'SystemTaskPriority',
    data() {
        return {
            loadIng: 0,

            formDatum: [],

            nullDatum: {
                'name': '',
                'priority': 1,
                'days': 1,
                'color': '#8bcf70',
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
                if (this.formDatum.length === 0) {
                    this.addDatum();
                }
            },
            immediate: true,
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
                this.loadIng--;
                this.$store.state.taskPriority = $A.cloneJSON(data);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
                this.loadIng--;
            });
        }
    }
}
</script>
