<template>
    <div class="setting-item submit">
        <Row class="approve-row" :gutter="8">
            <Col :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 24 }" :xs="{ span: 24 }" >
                <div class="approve-col-box approve-col-add">
                    <Icon type="md-add" />
                </div>

            </Col>
            <Col :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 24 }" :xs="{ span: 24 }" >
                <div class="approve-col-box approve-col-for">
                    <p>{{$L('流程名称：')}}<span>{{$L('加班')}}</span></p>

                    <Divider style="margin: 12px 0;"/>
                    <div class="approve-button-box">
                        <p>{{$L('是否启用：')}} <i-switch v-model="value"  /></p>
                        <p>
                            <Icon type="md-create" />
                            <Icon type="md-trash" />
                        </p>

                    </div>
                </div>
            </Col>
            <Col :xxl="{ span: 6 }" :xl="{ span: 8 }" :lg="{ span: 12 }" :sm="{ span: 24 }" :xs="{ span: 24 }" >
                <div class="approve-col-box approve-col-for">
                    <p>{{$L('流程名称：')}}<span>{{$L('加班')}}</span></p>

                    <Divider style="margin: 12px 0;"/>
                    <div class="approve-button-box">
                        <p>{{$L('是否启用：')}} <i-switch v-model="value"  /></p>
                        <p>
                            <Icon type="md-create" />
                            <Icon type="md-trash" />
                        </p>

                    </div>
                </div>
            </Col>
        </Row>
    </div>
</template>

<script>
export default {
    name: "approve",
    data(){
        return{
            value:true,
            loadIng:0,
        }
    },
}
</script>

<style scoped>

</style>
