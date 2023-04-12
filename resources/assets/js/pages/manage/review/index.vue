<template>
    <div class="page-review">
        <PageTitle :title="$L('审批中心')"/>
        <div class="review-wrapper" ref="fileWrapper">
            <div class="review-head">
                <div class="review-nav">
                    <h1>{{$L('审批中心')}}</h1>
                </div>
            </div>
            <div class="review-main">
                <div class="review-main-left">
                    <div class="review-main-search">
                        <div>
                            <Dropdown @on-click="changeTime" trigger="click">
                                <a href="javascript:void(0)">
                                    {{ timeChose }}<Icon type="ios-arrow-down"></Icon>
                                </a>
                                <template #list>
                                    <DropdownMenu>
                                        <DropdownItem name="all">{{$L('所有时间')}}</DropdownItem>
                                        <DropdownItem name="24">{{$L('最近24小时')}}</DropdownItem>
                                        <DropdownItem name="7">{{$L('最近7天')}}</DropdownItem>
                                        <DropdownItem name="30">{{$L('最近30天')}}</DropdownItem>
                                        <DropdownItem name="customize">{{$L('自定义时间')}}</DropdownItem>
                                    </DropdownMenu>
                                </template>
                            </Dropdown>

                            <Dropdown @on-click="" trigger="click">
                                <a href="javascript:void(0)">
                                    {{ timeChose }}<Icon type="ios-arrow-down"></Icon>
                                </a>
                                <template #list>
                                    <DropdownMenu>
                                        <DropdownItem name="all">{{$L('全部状态')}}</DropdownItem>
                                        <DropdownItem name="0">{{$L('审批中')}}</DropdownItem>
                                        <DropdownItem name="1">{{$L('已通过')}}</DropdownItem>
                                        <DropdownItem name="-1">{{$L('已拒绝')}}</DropdownItem>
                                        <DropdownItem name="-2">{{$L('已撤回')}}</DropdownItem>
                                        <DropdownItem name="-3">{{$L('已删除')}}</DropdownItem>
                                    </DropdownMenu>
                                </template>
                            </Dropdown>
                        </div>
                        <Dropdown @on-click="" trigger="click">
                            <a href="javascript:void(0)">
                                <Icon type="ios-arrow-down" />
                            </a>
                            <template #list>
                                <DropdownMenu>
                                    <DropdownItem name="all">{{$L('最新发起优先')}}</DropdownItem>
                                    <DropdownItem name="24">{{$L('最早发起优先')}}</DropdownItem>
                                </DropdownMenu>
                            </template>
                        </Dropdown>

                    </div>
                    <div class="review-main-list">
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                        <list></list>
                    </div>
                </div>
                <div class="review-main-right">
                    <listDetails></listDetails>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import list from "./list";
import listDetails from "./details";
export default {
    components:{list,listDetails},
    name: "review",
    data(){
        return{
            timeChose:this.$L('所有时间'),
        }
    },
    methods:{
        changeTime(e){
            switch (e) {
                case 'all':
                    this.timeChose = this.$L('所有时间');
                    return;
                case '24':
                    this.timeChose = this.$L('最近24小时');
                    return;
                case '7':
                    this.timeChose = this.$L('最近7天');
                    return;
                case '30':
                    this.timeChose = this.$L('最近30天');
                    return;
                case 'customize':
                    this.timeChose = this.$L('自定义时间');
                    return;
            }
        },
    },
}
</script>

<style scoped>

</style>
