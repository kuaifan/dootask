<template>
    <Modal class-name="word-chain-wrapper"
        v-model="show"
        :mask-closable="false"
        :title="wordChain.type == 'create' ? $L('发起接龙') : $L('接龙结果')"
        :closable="!isFullscreen"
        :fullscreen="isFullscreen"
        :footer-hide="isFullscreen">
        <!-- 顶部 -->
        <template #header>
            <div v-if="isFullscreen" class="chain-modal-header">
                <div class="chain-modal-close" @click="show = false">
                    {{ $L('取消') }}
                </div>
                <div class="chain-modal-title">
                    {{ wordChain.type == 'create' ? $L('发起接龙') : $L('接龙结果') }}
                </div>
                <div class="chain-modal-submit" :class="{'disabled': !isEdit}" @click="onSend" >
                    <div v-if="loadIng > 0" class="submit-loading"><Loading /></div>
                    {{$L('发送')}}
                </div>
            </div>
        </template>
        <template #close>
            <i class="ivu-icon ivu-icon-ios-close"></i>
        </template>
        <div ref="wordChainBodyRef" class="word-chain-body">
            <div class="source" v-if="wordChain.type == 'create'">
                {{$L('来自')}}
                <span>{{ dialog.name }}</span>
            </div>
            <div class="initiate">
                <span>{{ $L('由') }}</span>
                <UserAvatar :userid="createId" :size="22" :showName="true" tooltipDisabled/>
                <span> {{ $L('发起，参与接龙目前共'+num+'人') }}</span>
            </div>
            <div class="textarea">
                <Input ref="wordChainTextareaRef" v-model="value" type="textarea" :autosize="{minRows: 3,maxRows: 5}" :disabled="wordChain.type != 'create'" />
            </div>
            <ul ref="wordChainListRef">
                <li v-for="(item,index) in list" :key="index" v-if="item.type == 'case' && (wordChain.type == 'create' || item.text)">
                    <span>{{ $L('例') }}</span>
                    <Input v-model="item.text" :placeholder="$L('可填写接龙格式')" :disabled="wordChain.type != 'create'" />
                </li>
                <li v-for="(item,index) in list" :key="index" v-if="item.type != 'case'">
                    <span>{{index}}</span>
                    <Input v-model="item.text" :disabled="item.userid != userId"/>
                </li>
                <li class="add">
                    <i class="taskfont" @click="add">&#xe78c;</i>
                </li>
            </ul>
        </div>
        <div slot="footer">
            <Button type="default" @click="show=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="loadIng > 0" @click="onSend" :disabled="!isEdit">{{$L('发送')}}</Button>
        </div>
    </Modal>
</template>

<script>
import {mapState} from "vuex";
export default {
    name: 'WordChain',

    data() {
        return {
            show: false,

            createId: 0,
            value: "#接龙 \n",
            list: [],

            oldData: '',
            loadIng: 0,
        }
    },

    computed: {
        ...mapState(['wordChain', 'userInfo', 'dialogMsgs', 'cacheDialogs']),

        isFullscreen({ windowWidth }) {
            return windowWidth < 576;
        },

        num(){
            return this.list.filter(h=>h.type != 'case')?.length || 0;
        },

        allList(){
            let list = JSON.parse(JSON.stringify(this.wordChain.msgData?.msg?.list)) || [];
            this.dialogMsgs.filter(h=>{
                return h.type == "word-chain" && h.msg?.uuid == this.wordChain.msgData?.msg?.uuid
            }).forEach((h)=>{
                (h.msg.list || []).forEach(k=>{
                    if( k.type != 'case' && list.map(j=>j.id).indexOf(k.id) == -1 ){
                        list.push(k)
                    }
                })
            });
            return list;
        },

        isEdit(){
            return this.oldData != JSON.stringify(this.list)
        },

        dialog(){
            return this.cacheDialogs.find(h=>h.id == this.wordChain.dialog_id) || {}
        },
    },

    watch: {
        show(val){
            if(!val){
                this.value = "#接龙 \n";
                this.list = [];
            }else{
                if(this.wordChain.type == 'create'){
                    this.$nextTick(()=>{
                        this.$refs.wordChainTextareaRef.focus()
                    })
                }
            }
        },

        wordChain(data) {
            if(data.type == 'create' && data.dialog_id){
                this.show = true;
                this.createId = this.userId
                this.list.push({
                    id: Date.now(),
                    type: 'case',
                    userid: this.userId,
                    text: '',
                })
                this.list.push({
                    id: Date.now() + 1,
                    type: 'text',
                    userid: this.userId,
                    text: this.userInfo.nickname,
                })
            }
            if(data.type == 'participate' && data.dialog_id && data.msgData){
                this.show = true;
                this.createId = data.msgData.msg.userid
                this.value = data.msgData.msg.text
                this.list =  this.allList
                this.oldData = JSON.stringify(this.list)
            }
        }
    },

    methods: {
        add(){
            this.list.push({
                id: Date.now(),
                type: 'text',
                userid: this.userId,
                text: this.userInfo.nickname,
            })
            this.$nextTick(()=>{
                this.$refs.wordChainListRef.scrollTo(0, 99999);
            })
        },

        onSend() {
            if( !this.isEdit ){
                return;
            }
            const texts = this.list.map(h=> h.text);
            if( texts.length != [...new Set(texts)].length ){
                $A.modalConfirm({
                    content: '重复内容将不再计入接龙结果',
                    cancelText: '返回编辑',
                    okText: '继续发送',
                    onOk: () => {
                       this.send()
                    }
                })
                return;
            }
            this.send()
        },

        /**
         * 发送消息
         */
        send() {
            const list = [];
            this.list.forEach(h=>{
                if( list.map(h=> h.text).indexOf(h.text) == -1){
                    list.push(h);
                }
            });
            //
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/wordchain',
                method: 'post',
                data: {
                    dialog_id: this.wordChain.dialog_id,
                    text: this.value,
                    list: list,
                    uuid: this.wordChain.msgData?.msg?.uuid || ''
                }
            }).then(({data}) => {
                this.show = false;
                this.$store.dispatch("saveDialogMsg", data);
            }).catch(({msg}) => {
                if( msg.indexOf("System error") !== -1){
                    $A.modalInfo({
                        language: false,
                        title: '版本过低',
                        content: '服务器版本过低，请升级服务器。',
                    })
                    return;
                }
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
