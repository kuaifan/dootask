<template>
    <Form ref="forwardForm" :model="value" label-width="auto" @submit.native.prevent>
        <FormItem prop="dialogids" :label="$L('最近聊天')">
            <Select
                v-model="value.dialogids"
                :placeholder="$L('选择转发最近聊天')"
                :multiple-max="20"
                multiple
                filterable
                class="dialog-wrapper-dialogids"
                transfer-class-name="dialog-wrapper-forward">
                <div slot="drop-prepend" class="forward-drop-prepend">{{$L('最多只能选择20个')}}</div>
                <Option
                    v-for="(dialog, key) in dialogList"
                    :value="dialog.id"
                    :key="key"
                    :key-value="dialog.name"
                    :label="dialog.name">
                    <div class="forward-option">
                        <div class="forward-avatar">
                            <template v-if="dialog.type=='group'">
                                <i v-if="dialog.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                <i v-else-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="26"/></div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                        </div>
                        <div class="forward-name">{{ dialog.name }}</div>
                    </div>
                </Option>
            </Select>
        </FormItem>
        <FormItem prop="userids" :label="`(${$L('或')}) ${$L('指定成员')}`">
            <UserSelect v-model="value.userids" :multiple-max="20" :avatar-size="24" :title="$L('选择转发指定成员')" border/>
        </FormItem>
    </Form>
</template>

<script>

import {mapState} from "vuex";
import UserSelect from "../../../components/UserSelect.vue";

export default {
    name: "DialogSelect",
    components: {UserSelect},
    props: {
        value: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },

    computed: {
        ...mapState([
            'cacheDialogs',
        ]),

        dialogList() {
            return this.cacheDialogs.filter(dialog => {
                return !(dialog.name === undefined || dialog.dialog_delete === 1);
            }).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                if (a.todo_num > 0 || b.todo_num > 0) {
                    return b.todo_num - a.todo_num;
                }
                return $A.Date(b.last_at) - $A.Date(a.last_at);
            });
        },
    }
}
</script>
