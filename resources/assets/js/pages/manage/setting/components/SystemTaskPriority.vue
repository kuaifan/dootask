<template>
    <div :class="{'setting-component-item': !embedded, 'system-task-priority': true}">
        <div>
            <Row class="setting-color color-label-box">
                <Col span="2">{{$L('默认')}}</Col>
                <Col span="9">{{$L('名称')}}</Col>
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
                <Col span="1" class="setting-row-action"></Col>
            </Row>
            <RadioGroup v-model="defaultIndex">
                <Row v-for="(item, key) in formDatum" :key="key" class="setting-color">
                    <Col span="2" class="priority-default-col">
                        <Radio :label="key"><span></span></Radio>
                    </Col>
                    <Col span="9">
                        <Input
                            v-model="item.name"
                            :maxlength="20"
                            :placeholder="$L('请输入名称')"/>
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
                    <Col span="1" class="setting-row-action">
                        <Tooltip :content="formDatum.length > 1 ? $L('删除') : $L('至少保留一项')" placement="top" transfer>
                            <Button
                                type="text"
                                icon="ios-trash-outline"
                                :disabled="formDatum.length <= 1"
                                @click="delDatum(key)"/>
                        </Tooltip>
                    </Col>
                </Row>
            </RadioGroup>
            <div class="setting-add-action">
                <Button type="default" icon="md-add" @click="addDatum">{{$L('添加优先级')}}</Button>
            </div>
        </div>
        <div v-if="!embedded" class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: 'SystemTaskPriority',
    props: {
        embedded: Boolean,
    },
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
            this.systemSetting(true);
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
            if (this.formDatum.length <= 1) {
                return;
            }
            $A.modalConfirm({
                title: '确认删除',
                content: '确定要删除该优先级吗？',
                onOk: () => {
                    const deletingDefault = key === this.defaultIndex;
                    this.formDatum.splice(key, 1);
                    if (deletingDefault) {
                        this.defaultIndex = 0;
                    } else if (key < this.defaultIndex) {
                        this.defaultIndex--;
                    }
                    this.applyDefaultIndex();
                },
            });
        },

        applyDefaultIndex() {
            this.formDatum.forEach((item, idx) => {
                item.is_default = idx === this.defaultIndex ? 1 : 0;
            })
        },

        systemSetting(save, silent = false) {
            this.loadIng++;
            this.applyDefaultIndex();
            return this.$store.dispatch("call", {
                url: 'system/priority?type=' + (save ? 'save' : 'get'),
                method: 'post',
                data: {
                    list: this.formDatum
                },
            }).then(({data}) => {
                if (save && !silent) {
                    $A.messageSuccess('修改成功');
                }
                this.$store.state.taskPriority = $A.cloneJSON(data);
            }).catch(({msg}) => {
                if (save && !silent) {
                    $A.modalError(msg);
                }
                if (silent) {
                    return Promise.reject(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
