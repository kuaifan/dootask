<template>
    <div class="dialog-complaint-info">
        <div class="group-complaint-title">{{ $L('匿名举报') }}</div>
        <div class="group-complaint-warp">
            <div class="group-complaint-title underline required">{{ $L('请选择举报类型') }}:</div>
            <div class="group-complaint-list">
                <List>
                    <ListItem v-for="(item, index) in typeList" :key="index" :class="{ 'active': typeId == item.id }">
                        <div class="text" @click="onSelectType(item)">{{ $L(item.label) }}</div>
                        <RadioGroup v-model="typeId">
                            <Radio :label="item.id" :model-value="typeId">&nbsp;</Radio>
                        </RadioGroup>
                    </ListItem>
                </List>
            </div>
            <!--  -->
            <div class="group-complaint-title required">{{ $L('请输入举报原因') }}:</div>
            <div class="group-complaint-reason">
                <Input v-model="reason" type="textarea" maxlength="500" :autosize="{ minRows: 4, maxRows: 8 }"
                    :placeholder="$L('请输入填写详细的举报原因，以使我们更好的帮助你解决问题')" />
            </div>
            <div class="group-complaint-img">
                <ImgUpload v-model="imgs" :num="5" :width="512" :height="512" :whcut="1"></ImgUpload>
            </div>
        </div>
        <!--  -->
        <div class="group-info-button">
            <Button @click="onSubmit" type="primary" icon="md-add">{{ $L("提交") }}</Button>
        </div>
    </div>
</template>

<script>
import ImgUpload from "../../../components/ImgUpload";

export default {
    name: "DialogComplaint",
    components: { ImgUpload },
    props: {
        dialogId: {
            type: Number,
            default: 0
        },
    },

    data() {
        return {
            typeList: [
                { id: 10, label: "诈骗诱导转账" },
                { id: 20, label: "引流下载其他APP付费" },
                { id: 30, label: "敲诈勒索" },
                { id: 40, label: "照片与本人不一致" },
                { id: 50, label: "色情低俗" },
                { id: 60, label: "频繁广告骚扰" },
                { id: 70, label: "其他问题" }
            ],
            typeId: 0,
            reason: '',
            imgs: [],
        }
    },
    methods: {
        onSelectType(item) {
            if (this.typeId == item.id) {
                this.typeId = 0;
            } else {
                this.typeId = item.id;
            }
        },

        onSubmit() {
            if (!this.typeId) {
                return $A.modalError("请选择举报类型");
            }
            if (!this.reason) {
                return $A.modalError("请填写举报原因");
            }
            //
            this.$store.dispatch("call", {
                url: 'complaint/submit',
                data: {
                    dialog_id: this.dialogId,
                    reason: this.reason,
                    type: this.typeId,
                    imgs: this.imgs
                }
            }).then(({ data }) => {
                $A.modalSuccess("举报成功");
                this.$emit("on-close")
            }).catch(({ msg }) => {
                $A.modalError(msg);
            });
        }
    }
}
</script>
