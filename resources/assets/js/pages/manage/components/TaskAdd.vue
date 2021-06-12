<template>
    <div class="task-add">
        <Form class="task-add-form" label-position="top" @submit.native.prevent>
            <FormItem :label="$L('任务描述')">
                <Input
                    v-model="value.name"
                    type="textarea"
                    :rows="1"
                    :autosize="{ minRows: 1, maxRows: 3 }"
                    :maxlength="255"
                    :placeholder="$L('必填')"></Input>
            </FormItem>
            <FormItem :label="$L('任务详情')">
                <TEditor
                    v-model="value.content"
                    :plugins="taskPlugins"
                    :options="taskOptions"
                    :option-full="taskOptionFull"
                    :placeholder="$L('选填...')"></TEditor>
            </FormItem>
            <div class="advanced-option">
                <Button :class="{advanced: advanced}" @click="advanced=!advanced">{{$L('高级选项')}}</Button>
                <ul class="advanced-priority">
                    <li v-for="(item, key) in taskPriority" :key="key">
                        <ETooltip :content="item.name + ' (' + item.days + $L('天') + ')'">
                            <i
                                class="iconfont"
                                :style="{color:item.color}"
                                v-html="value.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"
                                @click="choosePriority(item)"></i>
                        </ETooltip>
                    </li>
                </ul>
            </div>
        </Form>
        <Form v-if="advanced" class="task-add-advanced" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('任务列表')">
                <Select
                    v-model="value.column_id"
                    :placeholder="$L('选择任务列表')"
                    :multipleMax="1"
                    multiple
                    filterable
                    transfer
                    allowCreate
                    transfer-class-name="task-add-advanced-transfer"
                    @on-create="columnCreate">
                    <div slot="drop-prepend" class="task-drop-prepend">{{$L('最多只能选择1项')}}</div>
                    <Option v-for="(item, key) in columns" :value="item.id" :key="key">{{ item.name }}</Option>
                </Select>
            </FormItem>
            <FormItem :label="$L('计划时间')">
                <DatePicker
                    v-model="value.times"
                    :options="timeOptions"
                    :editable="false"
                    :placeholder="$L('选择计划范围')"
                    format="yyyy-MM-dd HH:mm"
                    type="datetimerange"
                    @on-change="taskTimeChange(value.times)"/>
            </FormItem>
            <FormItem :label="$L('任务负责人')">
                <UserInput v-model="value.owner" :multiple-max="1" :placeholder="$L('选择任务负责人')"/>
            </FormItem>
            <div class="subtasks">
                <div v-if="value.subtasks.length > 0" class="sublist">
                    <Row>
                        <Col span="12">{{$L('任务描述')}}</Col>
                        <Col span="6">{{$L('计划时间')}}</Col>
                        <Col span="6">{{$L('负责人')}}</Col>
                    </Row>
                    <Row v-for="(item, key) in value.subtasks" :key="key">
                        <Col span="12">
                            <Input
                                v-model="item.name"
                                :maxlength="255"
                                clearable
                                @on-clear="value.subtasks.splice(key, 1)"/>
                        </Col>
                        <Col span="6">
                            <DatePicker
                                v-model="item.times"
                                :options="timeOptions"
                                :editable="false"
                                :placeholder="$L('选择时间')"
                                format="yyyy-MM-dd HH:mm"
                                type="datetimerange"
                                @on-change="taskTimeChange(item.times)"/>
                        </Col>
                        <Col span="6">
                            <UserInput
                                v-model="item.owner"
                                :multiple-max="1"
                                :placeholder="$L('选择负责人')"/>
                        </Col>
                    </Row>
                </div>
                <Input
                    type="text"
                    v-model="subName"
                    :class="['enter-input', subName == '' ? 'empty' : '']"
                    @on-enter="addSubTask"
                    :placeholder="$L('+ 输入子任务，回车添加子任务')"/>
            </div>
        </Form>
    </div>
</template>

<script>
import TEditor from "../../../components/TEditor";
import {mapState} from "vuex";
import UserInput from "../../../components/UserInput";

export default {
    name: "TaskAdd",
    components: {UserInput, TEditor},
    props: {
        value: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },
    data() {
        return {
            advanced: false,
            columns: [],
            subName: '',

            taskPlugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak imagetools',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table contextmenu directionality',
                'emoticons paste textcolor colorpicker imagetools codesample',
                'autoresize'
            ],
            taskOptions: {
                statusbar: false,
                menubar: false,
                forced_root_block : false,
                remove_trailing_brs: false,
                autoresize_bottom_margin: 2,
                min_height: 200,
                max_height: 380,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
            taskOptionFull: {
                menubar: 'file edit view',
                forced_root_block : false,
                remove_trailing_brs: false,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },

            timeOptions: {
                shortcuts: []
            },
        }
    },
    mounted() {
        this.$store.commit('getTaskPriority', () => {
            if (!this.value.p_name && this.taskPriority.length > 0) {
                this.choosePriority(this.taskPriority[0])
            }
        })
    },
    computed: {
        ...mapState(['userId', 'projectDetail', 'taskPriority']),
    },
    watch: {
        projectDetail(detail) {
            this.columns = $A.cloneJSON(detail.project_column);
        }
    },
    methods: {
        initLanguage() {
            const lastSecond = (e) => {
                return new Date($A.formatDate("Y-m-d 23:59:29", Math.round(e / 1000)))
            };
            this.timeOptions = {
                shortcuts: [{
                    text: this.$L('今天'),
                    value() {
                        return [new Date(), lastSecond(new Date().getTime())];
                    }
                }, {
                    text: this.$L('明天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 1);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }, {
                    text: this.$L('本周'),
                    value() {
                        return [$A.getData('今天', true), lastSecond($A.getData('本周结束2', true))];
                    }
                }, {
                    text: this.$L('本月'),
                    value() {
                        return [$A.getData('今天', true), lastSecond($A.getData('本月结束', true))];
                    }
                }, {
                    text: this.$L('3天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 3);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }, {
                    text: this.$L('5天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 5);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }, {
                    text: this.$L('7天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 7);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }]
            };
        },
        columnCreate(val) {
            if (!this.columns.find(({id}) => id == val)) {
                this.columns.push({
                    id: val,
                    name: val
                });
            }
        },
        taskTimeChange(times) {
            let tempc = $A.date2string(times, "Y-m-d H:i");
            if (tempc[0] && tempc[1]) {
                if ($A.rightExists(tempc[0], '00:00') && $A.rightExists(tempc[1], '00:00')) {
                    this.$set(times, 1, tempc[1].replace("00:00", "23:59"));
                }
            }
        },
        addSubTask() {
            if (this.subName.trim() !== '') {
                this.value.subtasks.push({
                    name: this.subName.trim(),
                    times: [],
                    owner: this.userId
                });
                this.subName = '';
            }
        },
        choosePriority(item) {
            let start = new Date();
            let end = new Date(new Date().setDate(start.getDate() + $A.runNum(item.days)));
            this.$set(this.value, 'times', $A.date2string([start, end]))
            this.$set(this.value, 'p_level', item.priority)
            this.$set(this.value, 'p_name', item.name)
            this.$set(this.value, 'p_color', item.color)
        }
    }
}
</script>
