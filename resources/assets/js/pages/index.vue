<template>
    <div v-if="needStartHome" class="page-index">
        <div class="page-warp">
            <div class="page-header">
                <div class="header-nav">
                    <div class="header-nav-box">
                        <div class="logo no-dark-mode"></div>
                    </div>
                    <div class="header-nav-box header-nav-boxs" v-if="windowWidth>780">
                        <div class="header-right-one">
                            <Dropdown trigger="click" @on-click="setLanguage">

                                <a
                                    href="javascript:void(0)"
                                    class="header-right-one-dropdown"Dootask
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
                        <div class="header-right-four">
                            <Dropdown trigger="click"  @on-click="setTheme">
                                <a
                                    href="javascript:void(0)"
                                    class="header-right-one-dropdown"
                                >
                                        {{$L('主题皮肤')}}
                                        <Icon type="ios-arrow-down"></Icon>
                                </a>
                                <DropdownMenu slot="list">
                                    <Dropdown-item v-for="(item, key) in themeList" :key="key" :name="item.value" :selected="themeMode === item.value">{{$L(item.name)}}</Dropdown-item>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                        <div class="header-right-two" @click="register">
                            {{ $L("注册账号") }}
                        </div>
                        <div class="header-right-three no-dark-mode" @click="login">
                            {{ $L("登录") }}
                        </div>
                    </div>
                    <div class="header-nav-box header-nav-boxs" v-else>

                        <Dropdown trigger="click">
                            <a href="javascript:void(0)">
                                <Icon type="md-menu" class="header-nav-more"/>
                            </a>
                            <DropdownMenu slot="list">
                                <DropdownItem @click.native="login">  {{ $L("登录") }}</DropdownItem>
                                <DropdownItem @click.native="register">  {{ $L("注册账号") }}</DropdownItem>
                                <Dropdown placement="right-start" @on-click="setLanguage">
                                    <DropdownItem>
                                        <Icon
                                            class="header-right-one-language no-dark-mode"
                                            type="md-globe"/>
                                        <a
                                            href="javascript:void(0)"
                                            class="header-right-one-dropdown"
                                        >
                                            {{ currentLanguage }}
                                        </a>
                                    </DropdownItem>
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
                                <Dropdown trigger="click" placement="right-end" @on-click="setTheme">
                                    <DropdownItem>
                                        <div class="login-setting-item">
                                            {{$L('主题皮肤')}}
                                            <Icon type="ios-arrow-forward"></Icon>
                                        </div>
                                    </DropdownItem>
                                    <DropdownMenu slot="list">
                                        <Dropdown-item v-for="(item, key) in themeList" :key="key" :name="item.value" :selected="themeMode === item.value">{{$L(item.name)}}</Dropdown-item>
                                    </DropdownMenu>

                                </Dropdown>
                            </DropdownMenu>
                        </Dropdown>
                    </div>

                </div>
                <div class="header-content">
                    <div class="header-title header-title-one">DooTask</div>
                    <div class="header-title">
                        {{ $L("轻量级任务管理工具") }}
                    </div>
                    <div class="header-tips">
                        {{
                            $L("DooTask是一款轻量级的开源在线项目任务管理工具，提供各类文档协作工具、在线思维导图、在线流程图、项目管理、任务分发、即时IM，文件管理等工具。")
                        }}
                    </div>
                    <div class="login-buttom no-dark-mode" @click="login">
                        {{ $L("登录") }}
                    </div>
                </div>
            </div>
            <div class="page-header-bottom">
                <div class="page-header-bottoms">
                    <img  :src="themeMode==='dark' ? 'images/index/pic_black.png':'images/index/pic.png'">
                </div>
            </div>
            <div class="page-main">
                <Row :class="windowWidth>1200 ? 'page-main-row':'page-main-rows'">
                    <Col :class="windowWidth>1200 ? 'page-main-img':'page-main-imgs'"  :xs="24" :sm="24" :xl="12">
                        <img :src="themeMode==='dark' ? 'images/index/pic1_black.png':'images/index/pic1.png'">
                    </Col>
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth>1200">
                        <img src="images/index/square.png">
                        <h3>{{$L('高效便捷的团队沟通工具')}}</h3>
                        <p>{{$L('针对项目和任务建立群组，工作问题可及时沟通，促进团队快速协作，提高团队工作效率。')}}</p>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-else>
                        <h3><img src="images/index/square.png">{{$L('高效便捷的团队沟通工具')}}</h3>
                        <p>{{$L('针对项目和任务建立群组，工作问题可及时沟通，促进团队快速协作，提高团队工作效率。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth>1200 ? 'page-main-row':'page-main-rows'">
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth>1200">
                        <img src="images/index/square.png">
                        <h3>{{$L('强大易用的协同创作云文档')}}</h3>
                        <p>{{$L('汇集文档、电子表格、思维笔记等多种在线工具，汇聚企业知识资源于一处，支持多人实时协同编辑，让团队协作更便捷。')}}</p>
                    </Col>

                    <Col :class="windowWidth>1200 ? 'page-main-img':'page-main-imgs'"  :xs="24" :sm="24" :xl="12">
                        <img  :src="themeMode==='dark' ? 'images/index/pic2_black.png':'images/index/pic2.png'">
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-if="windowWidth<=1200">
                        <h3><img src="images/index/square.png">{{$L('强大易用的协同创作云文档')}}</h3>
                        <p>{{$L('汇集文档、电子表格、思维笔记等多种在线工具，汇聚企业知识资源于一处，支持多人实时协同编辑，让团队协作更便捷。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth>1200 ? 'page-main-row':'page-main-rows'">
                    <Col :class="windowWidth>1200 ? 'page-main-img':'page-main-imgs'"  :xs="24" :sm="24" :xl="12">
                        <img  :src="themeMode==='dark' ? 'images/index/pic3_black.png':'images/index/pic3.png'">
                    </Col>
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth>1200">
                        <img src="images/index/square.png">
                        <h3>{{$L('便捷易用的项目管理模板')}}</h3>
                        <p>{{$L('模版满足多种团队协作场景，同时支持自定义模版，满足团队个性化场景管理需求，可直观的查看项目的进展情况，团队协作更方便。')}}</p>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-else>
                        <h3><img src="images/index/square.png">{{$L('便捷易用的项目管理模板')}}</h3>
                        <p>{{$L('模版满足多种团队协作场景，同时支持自定义模版，满足团队个性化场景管理需求，可直观的查看项目的进展情况，团队协作更方便。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth>1200 ? 'page-main-row':'page-main-rows'">
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth>1200">
                        <img src="images/index/square.png">
                        <h3>{{$L('清晰直观的任务日历')}}</h3>
                        <p>{{$L('通过灵活的任务日历，轻松安排每一天的日程，把任务拆解到每天，让工作目标更清晰，时间分配更合理。')}}</p>
                    </Col>

                    <Col :class="windowWidth>1200 ? 'page-main-img':'page-main-imgs'"  :xs="24" :sm="24" :xl="12">
                        <img  :src="themeMode==='dark' ? 'images/index/pic4_black.png':'images/index/pic4.png'">
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-if="windowWidth<=1200">
                        <h3><img src="images/index/square.png">{{$L('清晰直观的任务日历')}}</h3>
                        <p>{{$L('通过灵活的任务日历，轻松安排每一天的日程，把任务拆解到每天，让工作目标更清晰，时间分配更合理。')}}</p>
                    </Col>
                </Row>
            </div>
            <div class="page-footer">
                <div class="footer-service no-dark-mode">
                    <div class="footer-bg-box">
                        <div class="box-title">
                            {{ $L("开启您的 Dootask 团队协作") }}
                        </div>
                        <div class="buttom-box">
                            <div class="login-btn" @click="login">
                                {{ $L("立即登录") }}
                            </div>
                            <div class="contact-btn">{{ $L("联系我们") }}</div>
                        </div>
                    </div>
                </div>
                <div class="footer-opyright" v-if="this.homeFooter" v-html="this.homeFooter"></div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import RightBottom from "../components/RightBottom";
export default {
    components:{RightBottom},
    data() {
        return {
            needStartHome: false,
            homeFooter: '',

        };
    },
    computed: {
        ...mapState(['userId', 'windowWidth','themeMode', 'themeList',]),

        currentLanguage() {
            return this.languageList[this.languageType] || "Language";
        },
    },
    mounted() {
        this.getNeedStartHome();
    },

    methods: {
        setTheme(mode) {
            this.$store.dispatch("setTheme", mode)
        },

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
        getNeedStartHome() {
            this.$store.dispatch("call", {
                url: "system/get/starthome",
            }).then(({data}) => {
                this.homeFooter = data.home_footer;
                if (this.userId > 0) {
                    this.goForward({path: '/manage/dashboard'}, true);
                } else {
                    this.needStartHome = !!data.need_start;
                    if (this.needStartHome === false) {
                        this.goForward({path: '/login'}, true);
                    }
                }
            }).catch(() => {
                this.needStartHome = false;
            });
        },
    },
};
</script>
