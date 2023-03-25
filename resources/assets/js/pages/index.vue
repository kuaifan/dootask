<template>
    <div v-if="needStartHome" class="page-index">
        <PageTitle :title="appTitle"/>
        <div class="page-warp">
            <div class="page-header">
                <div class="header-nav">
                    <div class="header-nav-box">
                        <div class="logo no-dark-content"></div>
                    </div>
                    <div class="header-nav-box header-nav-boxs" v-if="windowWidth > 780">
                        <Button v-if="showItem.pro" class="header-right-pro no-dark-content" size="small" @click="onPro">{{$L('Pro版')}}</Button>
                        <template v-if="windowWidth >= 820">
                            <a v-if="showItem.github" class="header-right-github" :href="showItem.github" target="_blank"><Icon type="logo-github"/></a>
                            <div v-if="showItem.updateLog" class="header-right-uplog" @click="uplogShow=true">{{$L('更新日志')}}</div>
                        </template>

                        <div class="header-right-1">
                            <Dropdown trigger="click" @on-click="onLanguage">
                                <a href="javascript:void(0)" class="header-right-1-dropdown">
                                    {{ currentLanguage }}
                                    <Icon type="ios-arrow-down"></Icon>
                                </a>
                                <DropdownMenu slot="list">
                                    <DropdownItem
                                        v-for="(item, key) in languageList"
                                        :key="key"
                                        :name="key"
                                        :selected="languageType === key">{{ item }}</DropdownItem>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                        <div v-if="windowWidth >= 980" class="header-right-2">
                            <Dropdown trigger="click" @on-click="setTheme">
                                <a href="javascript:void(0)" class="header-right-2-dropdown">
                                    {{$L('主题皮肤')}}
                                    <Icon type="ios-arrow-down"></Icon>
                                </a>
                                <DropdownMenu slot="list">
                                    <DropdownItem
                                        v-for="(item, key) in themeList"
                                        :key="key"
                                        :name="item.value"
                                        :selected="themeMode === item.value">{{$L(item.name)}}</DropdownItem>
                                </DropdownMenu>
                            </Dropdown>
                        </div>
                        <div v-if="userId > 0" class="header-right-5 no-dark-content" @click="login">
                            <UserAvatar :userid="userId" :size="38"/>
                        </div>
                        <template v-else>
                            <div class="header-right-3" @click="register">{{ $L("注册帐号") }}</div>
                            <div class="header-right-4 no-dark-content" @click="login">{{ $L("登录") }}</div>
                        </template>
                    </div>
                    <div class="header-nav-box header-nav-boxs" v-else>
                        <Dropdown trigger="click">
                            <a href="javascript:void(0)">
                                <Icon type="md-menu" class="header-nav-more no-dark-content"/>
                            </a>
                            <DropdownMenu slot="list">
                                <DropdownItem v-if="userId > 0" @click.native="login">
                                    <UserAvatar :userid="userId" show-name :show-icon="false"/>
                                </DropdownItem>
                                <template v-else>
                                    <DropdownItem @click.native="login">{{ $L("登录") }}</DropdownItem>
                                    <DropdownItem @click.native="register">{{ $L("注册帐号") }}</DropdownItem>
                                </template>
                                <DropdownItem v-if="showItem.github" @click.native="windowOpen(showItem.github)">Github</DropdownItem>
                                <DropdownItem v-if="showItem.updateLog" @click.native="uplogShow=true">{{ $L("更新日志") }}</DropdownItem>
                                <Dropdown placement="right-start" @on-click="onLanguage" transfer>
                                    <DropdownItem>
                                        <div class="header-nav-dropdown-item">
                                            {{ currentLanguage }}
                                            <Icon type="ios-arrow-forward"></Icon>
                                        </div>
                                    </DropdownItem>
                                    <DropdownMenu slot="list">
                                        <DropdownItem
                                            v-for="(item, key) in languageList"
                                            :key="key"
                                            :name="key"
                                            :selected="languageType === key">{{ item }}</DropdownItem>
                                    </DropdownMenu>
                                </Dropdown>
                                <Dropdown trigger="click" placement="right-end" @on-click="setTheme" transfer>
                                    <DropdownItem>
                                        <div class="header-nav-dropdown-item">
                                            {{$L('主题皮肤')}}
                                            <Icon type="ios-arrow-forward"></Icon>
                                        </div>
                                    </DropdownItem>
                                    <DropdownMenu slot="list">
                                        <DropdownItem
                                            v-for="(item, key) in themeList"
                                            :key="key"
                                            :name="item.value"
                                            :selected="themeMode === item.value">{{$L(item.name)}}</DropdownItem>
                                    </DropdownMenu>
                                </Dropdown>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </div>
                <div class="header-content">
                    <div class="header-title header-title-one">{{appTitle}}</div>
                    <div class="header-title">
                        {{ $L("轻量级任务管理工具") }}
                    </div>
                    <div class="header-tips">
                        {{ $L(`${appTitle}是一款轻量级的开源在线项目任务管理工具，提供各类文档协作工具、在线思维导图、在线流程图、项目管理、任务分发、即时IM，文件管理等工具。`) }}
                    </div>
                    <div class="login-buttom no-dark-content" @click="login">
                        {{ $L("登录") }}
                    </div>
                </div>
            </div>
            <div class="page-header-bottom">
                <div class="page-header-bottoms">
                    <ImgView :src="themeIsDark ? 'images/index/dark/1.png':'images/index/light/1.png'"/>
                </div>
            </div>
            <div class="page-main">
                <Row :class="windowWidth > 1200 ? 'page-main-row':'page-main-rows'">
                    <Col :class="windowWidth > 1200 ? 'page-main-img':'page-main-imgs'" :xs="24" :sm="24" :xl="12">
                        <ImgView :src="themeIsDark ? 'images/index/dark/2.png':'images/index/light/2.png'"/>
                    </Col>
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth > 1200">
                        <ImgView src="images/index/square.png"/>
                        <h3>{{$L('高效便捷的团队沟通工具')}}</h3>
                        <p>{{$L('针对项目和任务建立群组，工作问题可及时沟通，促进团队快速协作，提高团队工作效率。')}}</p>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-else>
                        <h3><ImgView src="images/index/square.png"/>{{$L('高效便捷的团队沟通工具')}}</h3>
                        <p>{{$L('针对项目和任务建立群组，工作问题可及时沟通，促进团队快速协作，提高团队工作效率。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth > 1200 ? 'page-main-row':'page-main-rows'">
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth > 1200">
                        <ImgView src="images/index/square.png"/>
                        <h3>{{$L('强大易用的协同创作云文档')}}</h3>
                        <p>{{$L('汇集文档、电子表格、思维笔记等多种在线工具，汇聚企业知识资源于一处，支持多人实时协同编辑，让团队协作更便捷。')}}</p>
                    </Col>

                    <Col :class="windowWidth > 1200 ? 'page-main-img':'page-main-imgs'" :xs="24" :sm="24" :xl="12">
                        <ImgView :src="themeIsDark ? 'images/index/dark/3.png':'images/index/light/3.png'"/>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-if="windowWidth<=1200">
                        <h3><ImgView src="images/index/square.png"/>{{$L('强大易用的协同创作云文档')}}</h3>
                        <p>{{$L('汇集文档、电子表格、思维笔记等多种在线工具，汇聚企业知识资源于一处，支持多人实时协同编辑，让团队协作更便捷。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth > 1200 ? 'page-main-row':'page-main-rows'">
                    <Col :class="windowWidth > 1200 ? 'page-main-img':'page-main-imgs'" :xs="24" :sm="24" :xl="12">
                        <ImgView :src="themeIsDark ? 'images/index/dark/4.png':'images/index/light/4.png'"/>
                    </Col>
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth > 1200">
                        <ImgView src="images/index/square.png"/>
                        <h3>{{$L('便捷易用的项目管理模板')}}</h3>
                        <p>{{$L('模版满足多种团队协作场景，同时支持自定义模版，满足团队个性化场景管理需求，可直观的查看项目的进展情况，团队协作更方便。')}}</p>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-else>
                        <h3><ImgView src="images/index/square.png"/>{{$L('便捷易用的项目管理模板')}}</h3>
                        <p>{{$L('模版满足多种团队协作场景，同时支持自定义模版，满足团队个性化场景管理需求，可直观的查看项目的进展情况，团队协作更方便。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth > 1200 ? 'page-main-row':'page-main-rows'">
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth > 1200">
                        <ImgView src="images/index/square.png"/>
                        <h3>{{$L('清晰直观的任务日历')}}</h3>
                        <p>{{$L('通过灵活的任务日历，轻松安排每一天的日程，把任务拆解到每天，让工作目标更清晰，时间分配更合理。')}}</p>
                    </Col>

                    <Col :class="windowWidth > 1200 ? 'page-main-img':'page-main-imgs'" :xs="24" :sm="24" :xl="12">
                        <ImgView :src="themeIsDark ? 'images/index/dark/5.png':'images/index/light/5.png'"/>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-if="windowWidth <= 1200">
                        <h3><ImgView src="images/index/square.png"/>{{$L('清晰直观的任务日历')}}</h3>
                        <p>{{$L('通过灵活的任务日历，轻松安排每一天的日程，把任务拆解到每天，让工作目标更清晰，时间分配更合理。')}}</p>
                    </Col>
                </Row>

                <Row :class="windowWidth > 1200 ? 'page-main-row':'page-main-rows'">
                    <Col :class="windowWidth > 1200 ? 'page-main-img':'page-main-imgs'" :xs="24" :sm="24" :xl="12">
                        <ImgView :src="themeIsDark ? 'images/index/dark/6.png':'images/index/light/6.png'"/>
                    </Col>
                    <Col class="page-main-text" :xs="24" :sm="24" :xl="12" v-if="windowWidth > 1200">
                        <ImgView src="images/index/square.png"/>
                        <h3>{{$L('支持多平台应用')}}</h3>
                        <p>{{$L('多平台应用支持，打开客户端即可跟进项目任务进度， 同时让你在工作中每一个步骤都能拥有更高效愉悦的体验。')}}</p>
                    </Col>
                    <Col class="page-main-text page-main-texts" :xs="24" :sm="24" :xl="12" v-else>
                        <h3><ImgView src="images/index/square.png"/>{{$L('支持多平台应用')}}</h3>
                        <p>{{$L('多平台应用支持，打开客户端即可跟进项目任务进度， 同时让你在工作中每一个步骤都能拥有更高效愉悦的体验。')}}</p>
                    </Col>
                </Row>
            </div>
            <div class="page-footer">
                <div class="footer-service no-dark-content">
                    <div class="footer-bg-box">
                        <div class="box-title">{{ $L(`开启您的 ${appTitle} 团队协作`) }}</div>
                        <div class="buttom-box">
                            <div class="login-btn" @click="login">{{ $L("立即登录") }}</div>
                            <div class="reg-btn" @click="register">{{ $L("注册") }}</div>
                        </div>
                    </div>
                </div>
                <div class="footer-copyright" v-if="homeFooter" v-html="homeFooter"></div>
            </div>
        </div>

        <!--更新日志-->
        <UpdateLog v-model="uplogShow" :update-log="showItem.updateLog" :update-ver="showItem.updateVer"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {languageList, languageType, setLanguage} from "../language";
import UpdateLog from "./manage/components/UpdateLog";

export default {
    components: {UpdateLog},
    data() {
        return {
            languageList,
            languageType,

            showItem: {
                pro: false,
                github: '',
                updateLog: '',
                updateVer: ''
            },
            needStartHome: false,
            homeFooter: '',

            uplogShow: false,
        };
    },
    computed: {
        ...mapState(['themeMode', 'themeIsDark', 'themeList',]),

        isSoftware() {
            return this.$Electron || this.$isEEUiApp;
        },

        currentLanguage() {
            return languageList[languageType] || "Language";
        },

        appTitle() {
            return  window.systemInfo.title || "DooTask";
        },
    },

    mounted() {
        if (/^https*:/i.test(window.location.protocol)) {
            if (this.$router.mode === "hash") {
                if ($A.stringLength(window.location.pathname) > 2) {
                    window.location.href = `${window.location.origin}/#${window.location.pathname}${window.location.search}`
                }
            } else if (this.$router.mode === "history") {
                if ($A.strExists(window.location.href, "/#/")) {
                    window.location.href = window.location.href.replace("/#/", "/")
                }
            }
        }
    },

    activated() {
        this.getShowItem();
        this.getNeedStartHome();
    },

    methods: {
        onPro() {
            this.goForward({name: 'pro'});
        },

        setTheme(mode) {
            this.$store.dispatch("setTheme", mode)
        },

        login() {
            if (this.userId > 0) {
                this.goForward({name: 'manage-dashboard'}, true);
            } else {
                this.goForward({name: 'login'}, true);
            }
        },

        register() {
            this.goForward({name: 'login', query: {type: "reg"}});
        },

        windowOpen(url) {
            window.open(url)
        },

        getShowItem() {
            this.$store.dispatch("call", {
                url: "system/get/showitem",
                spinner: 1000
            }).then(({data}) => {
                this.showItem = data
            }).catch(_ => {
                this.showItem = {}
            });
        },

        getNeedStartHome() {
            if (this.isSoftware) {
                this.needStartHome = false;
                if (this.userId > 0) {
                    this.goForward({name: 'manage-dashboard'}, true);
                } else {
                    this.goForward({name: 'login'}, true);
                }
                return;
            }
            //
            this.$store.dispatch("showSpinner", 1000)
            this.$store.dispatch("needHome").then(({home_footer}) => {
                this.needStartHome = true;
                this.homeFooter = home_footer;
            }).catch(_ => {
                this.needStartHome = false;
                this.goNext();
            }).finally(_ => {
                this.$store.dispatch("hiddenSpinner")
            });
        },

        goNext() {
            if (this.userId > 0) {
                this.goForward({name: 'manage-dashboard'}, true);
            } else {
                this.goForward({name: 'login'}, true);
            }
        },

        onLanguage(l) {
            setLanguage(l)
        }
    },
};
</script>
