<template>
    <div class="task-detail">
        <div class="task-info">
            <div class="head">
                <Icon class="radio" type="md-radio-button-off"/>
                <div class="nav">
                    <p>项目名称</p>
                    <p>列表名称</p>
                    <p>2222</p>
                </div>
                <Icon class="menu" type="ios-more"/>
            </div>
            <div class="title">
                <Input
                    v-model="projectTask.name"
                    type="textarea"
                    :rows="1"
                    :autosize="{ minRows: 1, maxRows: 8 }"
                    :maxlength="255"/>
            </div>
            <div class="desc">
                <TEditor
                    v-model="content"
                    :plugins="taskPlugins"
                    :options="taskOptions"
                    :option-full="taskOptionFull"
                    :placeholder="$L('详细描述...')"
                    inline></TEditor>
            </div>
            <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                <FormItem>
                    <div class="item-label" slot="label">
                        <i class="iconfont">&#xe6ec;</i>
                        {{$L('优先级')}}
                    </div>
                    <ul class="item-content">
                        <li>紧急且重要</li>
                    </ul>
                </FormItem>
                <FormItem>
                    <div class="item-label" slot="label">
                        <i class="iconfont">&#xe6e4;</i>
                        {{$L('负责人')}}
                    </div>
                    <ul class="item-content">
                        <li><UserAvatar :userid="1"/></li>
                    </ul>
                </FormItem>
                <FormItem>
                    <div class="item-label" slot="label">
                        <i class="iconfont">&#xe6e8;</i>
                        {{$L('截止时间')}}
                    </div>
                    <ul class="item-content">
                        <li>2020/10/11 10:00</li>
                    </ul>
                </FormItem>
                <FormItem>
                    <div class="item-label" slot="label">
                        <i class="iconfont">&#xe6e6;</i>
                        {{$L('附件')}}
                    </div>
                    <ul class="item-content file">
                        <li>文件11</li>
                        <li>文件22</li>
                        <li>文件33</li>
                        <li>
                            <div class="add-button">
                                <i class="iconfont">&#xe6f2;</i>{{$L('添加附件')}}
                            </div>
                        </li>
                    </ul>
                </FormItem>
                <FormItem>
                    <div class="item-label" slot="label">
                        <i class="iconfont">&#xe6f0;</i>
                        {{$L('子任务')}}
                    </div>
                    <ul class="item-content subtask">
                        <li>文件11</li>
                        <li>文件22</li>
                        <li>文件33</li>
                        <li>
                            <div class="add-button">
                                <i class="iconfont">&#xe6f2;</i>{{$L('添加子任务')}}
                            </div>
                        </li>
                    </ul>
                </FormItem>
            </Form>
            <div class="add">
                <div class="add-button">
                    <i class="iconfont">&#xe6f2;</i>{{$L('添加模块')}}
                </div>
            </div>
        </div>
        <div class="task-dialog">

        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TEditor from "../../../components/TEditor";

export default {
    name: "TaskDetail",
    components: {TEditor},
    data() {
        return {
            content: '随着互联网的发展,生活智能化越来越普及,各类智能产品逐渐出现到人们面前,在体验的过程中,其实里面有 很多细节需要深挖和思考。很多产品细节的背后都是为了提升用户操作效率、兼容用户使用场景、满足用户情感 表达,以最终达到对用户体验的提升。作为智能产品的设计师只有充分了解市面上的智能产品,才能设计出更好',

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
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img,pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
            taskOptionFull: {
                menubar: 'file edit view',
                forced_root_block : false,
                remove_trailing_brs: false,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img,pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
        }
    },
    computed: {
        ...mapState(['userId', 'projectTask']),
    },
}
</script>
