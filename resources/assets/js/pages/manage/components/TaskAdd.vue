<template>
    <div class="task-add">
        <Form class="task-add-form" label-position="top" @submit.native.prevent>
            <FormItem :label="$L('任务描述')">
                <Input
                    v-model="addData.name"
                    ref="input"
                    type="textarea"
                    :rows="1"
                    :autosize="{ minRows: 1, maxRows: 3 }"
                    :maxlength="255"
                    :placeholder="$L('必填')"
                    @on-keydown="onKeydown"/>
            </FormItem>
            <FormItem :label="$L('任务详情')">
                <TEditor
                    v-model="addData.content"
                    :plugins="taskPlugins"
                    :options="taskOptions"
                    :option-full="taskOptionFull"
                    :placeholder="$L('选填...')"/>
            </FormItem>
            <div class="advanced-option">
                <Button :class="{advanced: advanced}" @click="advanced=!advanced">{{$L('高级选项')}}</Button>
                <ul class="advanced-priority">
                    <li v-for="(item, key) in taskPriority" :key="key">
                        <ETooltip :content="item.name + ' (' + item.days + $L('天') + ')'">
                            <i
                                class="taskfont"
                                :style="{color:item.color}"
                                v-html="addData.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"
                                @click="choosePriority(item)"></i>
                        </ETooltip>
                    </li>
                </ul>
            </div>
        </Form>
        <Form v-if="advanced" class="task-add-advanced" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('任务列表')">
                <Select
                    v-model="addData.column_id"
                    :placeholder="$L('选择任务列表')"
                    :multipleMax="1"
                    multiple
                    filterable
                    transfer
                    allowCreate
                    transfer-class-name="task-add-advanced-transfer"
                    @on-create="columnCreate">
                    <div slot="drop-prepend" class="task-drop-prepend">{{$L('最多只能选择1项')}}</div>
                    <Option v-for="(item, key) in columnList" :value="item.id" :key="key">{{ item.name }}</Option>
                    <Option v-for="(item, key) in columnAdd" :value="item.id" :key="'_' + key">{{ item.name }}</Option>
                </Select>
            </FormItem>
            <FormItem :label="$L('计划时间')">
                <DatePicker
                    v-model="addData.times"
                    :options="timeOptions"
                    :editable="false"
                    :placeholder="$L('选择计划范围')"
                    format="yyyy-MM-dd HH:mm"
                    type="datetimerange"
                    @on-change="taskTimeChange(addData.times)"/>
            </FormItem>
            <FormItem :label="$L('任务负责人')">
                <UserInput
                    v-model="addData.owner"
                    :multiple-max="10"
                    :placeholder="$L('选择任务负责人')"
                    :project-id="projectId"/>
            </FormItem>
            <div class="subtasks">
                <div v-if="addData.subtasks.length > 0" class="sublist">
                    <Row>
                        <Col span="12">{{$L('任务描述')}}</Col>
                        <Col span="6">{{$L('计划时间')}}</Col>
                        <Col span="6">{{$L('负责人')}}</Col>
                    </Row>
                    <Row v-for="(item, key) in addData.subtasks" :key="key">
                        <Col span="12">
                            <Input
                                v-model="item.name"
                                :maxlength="255"
                                clearable
                                @on-clear="addData.subtasks.splice(key, 1)"/>
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
                                :placeholder="$L('选择负责人')"
                                :project-id="projectId"/>
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
    data() {
        return {
            addData: {
                name: "",
                content: "",
                owner: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                p_level: 0,
                p_name: '',
                p_color: '',
            },

            advanced: false,
            subName: '',
            columnAdd: [],

            taskPlugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak imagetools',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table directionality',
                'emoticons paste imagetools codesample',
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
    computed: {
        ...mapState(['userId', 'projectId', 'columns', 'taskPriority']),

        columnList() {
            return this.columns.filter(({project_id}) => project_id == this.projectId)
        },
    },
    methods: {
        initLanguage() {
            const lastSecond = (e) => {
                return $A.Date($A.formatDate("Y-m-d 23:59:29", Math.round(e / 1000)))
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
            if (!this.columnAdd.find(({id}) => id == val)) {
                this.columnAdd.push({
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
        onKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.$emit("on-add")
            }
        },
        addSubTask() {
            if (this.subName.trim() !== '') {
                this.addData.subtasks.push({
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
            this.$set(this.addData, 'times', $A.date2string([start, end]))
            this.$set(this.addData, 'p_level', item.priority)
            this.$set(this.addData, 'p_name', item.name)
            this.$set(this.addData, 'p_color', item.color)
        },
        defaultPriority() {
            if (this.taskPriority.length === 0) {
                return;
            }
            if (this.addData.p_name) {
                return;
            }
            this.choosePriority(this.taskPriority[0]);
        },
        setData(data) {
            this.addData = Object.assign({}, this.addData, data);
        },
        onAdd(callback, again) {
            if (!this.addData.name) {
                $A.messageError("任务描述不能为空");
                callback(false)
                return;
            }
            this.$store.dispatch("taskAdd", Object.assign(this.addData, {
                project_id: this.projectId
            })).then(({msg}) => {
                $A.messageSuccess(msg);
                if (again === true) {
                    this.addData = Object.assign({}, this.addData, {
                        name: "",
                        content: "",
                        subtasks: [],
                    });
                } else {
                    this.addData = {
                        name: "",
                        content: "",
                        owner: 0,
                        column_id: 0,
                        times: [],
                        subtasks: [],
                        p_level: 0,
                        p_name: '',
                        p_color: '',
                    };
                }
                callback(true)
            }).catch(({msg}) => {
                $A.modalError(msg);
                callback(false)
            });
        }
    }
}
</script>
