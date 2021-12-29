<template>
    <div>
        <div class="page-warp">
            <div class="page-header">
                <div class="header-nav">
                    <div class="header-nav-box">
                        <div class="logo"></div>
                    </div>
                    <div class="header-nav-box">
                        <div class="header-right-one">
                            <Icon class="header-right-one-language" type="md-globe" />
                            <Dropdown trigger="click" @on-click="setLanguage">
                                <a
                                    href="javascript:void(0)"
                                    class="header-right-one-dropdown"
                                >
                                    {{ currentLanguage }}
                                    <Icon type="ios-arrow-down"></Icon>
                                </a>
                                <DropdownMenu slot="list">
                                    <Dropdown-item
                                        v-for="(item, key) in languageList"
                                        :key="key"
                                        :name="key"
                                        :selected="getLanguage() === key"
                                        >{{ item }}
                                    </Dropdown-item>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                        <div class="header-right-two" @click="register">
                           {{$L('注册账号')}} 
                        </div>
                        <div class="header-right-three" @click="login">
                            {{$L('立即登录')}}
                        </div>
                    </div>
                </div>
                <div class="header-content">
                    <div class="header-title header-title-one">Dootask</div>
                    <div class="header-title">{{$L('轻量级任务管理工具')}}</div>
                    <div class="header-tips">
                        {{$L('DooTask是一款轻量级的开源在线项目任务管理工具，提供各类文档协作工具、在线思维导图、在线流程图、项目管理、任务分发、即时IM，文件管理等工具。')}}
                    </div>
                    <div class="login-buttom" @click="login">{{$L('登录')}}</div>
                </div>

                <div class="header-bg"></div>
                <div class="header-pic"></div>
            </div>
            <div class="page-main">
                <div class="main-box-one">
                    <div class="box-one-square"></div>
                    <div class="box-one-pic1"></div>
                    <div class="box-one-tips">
                        <div class="box-square"></div>
                        <div class="box-title">{{$L('高效便捷的团队沟通工具')}} </div>
                        <div class="box-tips">
                            {{$L('针对项目和任务建立群组，工作问题可及时沟通，促进团队快速协作，提高团队工作效率。')}}  
                        </div>
                    </div>
                </div>
                <div class="main-box-two">
                    <div class="box-two-tips">
                        <div class="box-square"></div>
                        <div class="box-title">{{$L('强大易用的协同创作云文档')}}</div>
                        <div class="box-tips">
                            {{$L('汇集文档、电子表格、思维笔记等多种在线工具，汇聚企业知识资源于一处，支持多人实时协同编辑，让团队协作更便捷。')}}
                        </div>
                    </div>
                    <div class="box-two-square"></div>
                    <div class="box-two-pic2"></div>
                </div>
                <div class="main-box-one">
                    <div class="box-one-square"></div>
                    <div class="box-one-pic3"></div>
                    <div class="box-one-tips">
                        <div class="box-square"></div>
                        <div class="box-title">{{$L('便捷易用的项目管理模板')}}</div>
                        <div class="box-tips">
                            {{$L('模版满足多种团队协作场景，同时支持自定义模版，满足团队个性化场景管理需求，可直观的查看项目的进展情况，团队协作更方便。')}}
                        </div>
                    </div>
                </div>
                <div class="main-box-two">
                    <div class="box-two-tips">
                        <div class="box-square"></div>
                        <div class="box-title">{{$L('清晰直观的任务日历')}}</div>
                        <div class="box-tips">
                            {{$L('通过灵活的任务日历，轻松安排每一天的日程，把任务拆解到每天，让工作目标更清晰，时间分配更合理。')}}
                        </div>
                    </div>
                    <div class="box-two-square"></div>
                    <div class="box-two-pic4"></div>
                </div>
            </div>
            <div class="page-footer">
                <div class="footer-service">
                    <div class="footer-bg-box">
                        <div class="box-title">{{$L('开启您的 Dootask 团队协作')}}</div>
                        <div class="buttom-box">
                            <div class="login-btn" @click="login">{{$L('立即登录')}}</div>
                            <div class="contact-btn">{{$L('联系我们')}}</div>
                        </div>
                    </div>
                </div>
                <div class="footer-opyright">
                    {{$L('桂公网安备 31011002000058号 桂ICP备12020087号-3')}}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {};
    },
    computed: {
        currentLanguage() {
            return this.languageList[this.languageType] || "Language";
        },
    },
    mounted() {
        // if (this.$store.state.userId > 0) {
        //     this.goForward({path: '/manage/dashboard'}, true);
        // } else {
        //     this.goForward({path: '/login'}, true);
        // }
    },
    methods: {
        login() {
            this.goForward(
                {
                    path: `/login`,
                },
                false
            );
        },
        register() {
            this.goForward(
                {
                    path: `/login`,
                    query: {
                        type: "reg",
                    },
                },
                false
            );
        },
    },
    deactivated() {
        // this.$destroy()
    },
};
</script>
