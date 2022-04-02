<template>
    <div class="setting-component-item">
        <Form ref="formDatum" label-width="auto" @submit.native.prevent>
            <Row class="setting-template">
                <Col span="8">{{$L('名称')}}</Col>
                <Col span="16">{{$L('项目模板')}}</Col>
            </Row>
            <Row v-for="(item, key) in formDatum" :key="key" class="setting-template">
                <Col span="8">
                    <Input
                        v-model="item.name"
                        :maxlength="20"
                        :placeholder="$L('请输入名称')"
                        clearable
                        @on-clear="delDatum(key)"/>
                </Col>
                <Col span="16">
                    <TagInput v-model="item.columns"/>
                </Col>
            </Row>
            <Button type="default" icon="md-add" @click="addDatum">{{$L('添加模板')}}</Button>
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
    name: 'SystemColumnTemplate',
    data() {
        return {
            loadIng: 0,

            formDatum: [],

            nullDatum: {
                'name': '',
                'columns': '',
            }
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['columnTemplate']),
    },

    watch: {
        columnTemplate: {
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
            this.formDatum = $A.cloneJSON(this.columnTemplate);
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
                url: 'system/column/template?type=' + (save ? 'save' : 'get'),
                method: 'post',
                data: {
                    list: this.formDatum
                },
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.loadIng--;
                this.$store.state.columnTemplate = $A.cloneJSON(data).map(item => {
                    if ($A.isArray(item.columns)) {
                        item.columns = item.columns.join(",")
                    }
                    return item;
                });
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
