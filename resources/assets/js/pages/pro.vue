<template>
    <div class="page-plans no-dark-content">
        <PageTitle :title="appTitle"></PageTitle>

        <div class="top-bg"></div>

        <div class="top-menu">
            <div class="header">
                <div class="z-row">
                    <div class="header-col-sub">
                        <div @click="goHome" class="logo"></div>
                    </div>
                    <div class="z-1">
                        <dl>
                            <dd>
                                <div class="right-info" @click="goHome">{{$L('返回首页')}}</div>
                                <div v-if="showItem.updateLog" class="right-info" @click="uplogShow=true">{{$L('更新日志')}}</div>
                                <a class="right-info item-center" target="_blank" href="https://github.com/kuaifan/dootask">
                                    <Icon class="right-icon" type="logo-github"/>
                                </a>
                                <Dropdown class="right-info" trigger="click" @on-click="onLanguage">
                                    <div class="item-center">
                                        <Icon class="right-icon" type="md-globe"/>
                                        <Icon type="md-arrow-dropdown"/>
                                    </div>
                                    <Dropdown-menu slot="list">
                                        <Dropdown-item v-for="(item, key) in languageList" :key="key" :name="key" :selected="languageType === key">{{item}}</Dropdown-item>
                                    </Dropdown-menu>
                                </Dropdown>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="banner">
            <div class="banner-title">
                {{$L('选择适合您的版本')}}
            </div>
            <div class="banner-desc">
                {{$L('DooTask 是新一代团队协作平台，您可以根据您团队的需求，选择合适的产品功能。')}} <br>
                {{$L('从现在开始，DooTask 为世界各地的团队提供支持，探索适合您的选项。')}}
            </div>
            <div class="plans-table">
                <div class="plans-table-bd plans-table-info">
                    <div class="plans-table-item first">
                        <div class="plans-table-info-th"></div>
                        <div class="plans-table-info-price"><em>{{$L('价格')}}</em></div>
                        <div class="plans-table-info-desc"><em>{{$L('概述')}}</em></div>
                        <div class="plans-table-info-desc"><em>{{$L('人数')}}</em></div>
                        <div class="plans-table-info-desc"><em>{{$L('授权方式')}}</em></div>
                        <div class="plans-table-info-btn"></div>
                    </div>
                    <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}">
                        <div class="plans-table-info-th">{{$L('普通版')}}</div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/free.png"/>
                            <div class="currency"><em>0</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('功能较少可能会停更')}}</div>
                        <div class="plans-table-info-desc">{{$L('无限制')}}</div>
                        <div class="plans-table-info-desc">{{$L('无须授权')}}</div>
                        <div class="plans-table-info-btn">
                            <div class="plans-info-btns">
                                <a href="https://github.com/kuaifan/dootask/tree/v0.13.0" class="github" target="_blank"><Icon type="logo-github"/></a>
                            </div>
                        </div>
                    </div>
                    <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}">
                        <div class="plans-table-info-th">{{$L('Pro免费版')}}</div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/free.png"/>
                            <div class="currency"><em>0</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('拥有最新版本所有功能')}}</div>
                        <div class="plans-table-info-desc">{{$L('3人')}}</div>
                        <div class="plans-table-info-desc">License</div>
                        <div class="plans-table-info-btn">
                            <div class="plans-info-btns">
                                <a href="https://github.com/kuaifan/dootask/tree/pro" class="github" target="_blank"><Icon type="logo-github"/></a>
                            </div>
                        </div>
                    </div>
                    <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}">
                        <div class="plans-table-info-th">{{$L('Pro订阅版')}} <span>{{$L('推荐')}}</span></div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/pro.png"/>
                            <div class="currency"><em>18800</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('拥有最新版本所有功能')}}</div>
                        <div class="plans-table-info-desc">{{$L('无限制')}}</div>
                        <div class="plans-table-info-desc">License</div>
                        <div class="plans-table-info-btn">
                            <div class="plans-info-btns">
                                <a href="https://github.com/kuaifan/dootask/tree/pro" class="github" target="_blank"><Icon type="logo-github"/></a>
                                <ETooltip :content="$L('帐号：admin、密码：123456')">
                                    <a href="https://www.dootask.com" class="btn mini" target="_blank">{{$L('体验DEMO')}}</a>
                                </ETooltip>
                            </div>
                        </div>
                    </div>
                    <div @mouseenter="active=4" class="plans-table-item" :class="{active:active==4}">
                        <div class="plans-table-info-th">{{$L('定制版')}}</div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/custom.png"/>
                            <div class="currency"><em class="custom">{{$L('自定义')}}</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('根据您的需求量身定制')}}</div>
                        <div class="plans-table-info-desc">{{$L('无限制')}}</div>
                        <div class="plans-table-info-desc">License</div>
                        <div class="plans-table-info-btn">
                            <a href="javascript:void(0)" class="btn btn-contact" @click="contactShow=true">{{$L('联系我们')}}</a>
                        </div>
                    </div>
                </div>
                <div class="plans-accordion-head" :class="{'plans-accordion-close':!body1}" @click="body1=!body1">
                    <div class="first"><span>{{$L('应用支持')}}</span></div>
                    <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}"></div>
                    <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}"></div>
                    <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}"></div>
                    <div @mouseenter="active=4" class="plans-table-item" :class="{active:active==4}"></div>
                    <span><Icon type="ios-arrow-down" /></span>
                </div>
                <div v-if="body1" class="plans-accordion-body">
                    <div class="plans-table-bd plans-table-app">
                        <div class="plans-table-item first">
                            <div v-for="item in functions" class="plans-table-td">{{$L(item.label)}}</div>
                        </div>
                        <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}">
                            <div v-for="item in functions" class="plans-table-td">
                                <Icon v-if="item.supports[0]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                        </div>
                        <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}">
                            <div v-for="item in functions" class="plans-table-td">
                                <Icon v-if="item.supports[1]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                        </div>
                        <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}">
                            <div v-for="item in functions" class="plans-table-td">
                                <Icon v-if="item.supports[2]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                        </div>
                        <div @mouseenter="active=4" class="plans-table-item" :class="{active:active==4}">
                            <div v-for="item in functions" class="plans-table-td">
                                <Icon v-if="item.supports[3]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="plans-accordion-head" :class="{'plans-accordion-close':!body2}" @click="body2=!body2">
                    <div class="first"><span>{{$L('服务支持')}}</span></div>
                    <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}"></div>
                    <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}"></div>
                    <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}"></div>
                    <div @mouseenter="active=4" class="plans-table-item" :class="{active:active==4}"></div>
                    <span><Icon type="ios-arrow-down" /></span>
                </div>
                <div v-if="body2" class="plans-accordion-body">
                    <div class="plans-table-bd plans-table-app plans-table-service">
                        <div class="plans-table-item first">
                            <div v-for="item in services" class="plans-table-td">
                                {{$L(item.label)}}
                                <span v-if="item.sublabel">{{$L(item.sublabel)}}</span>
                            </div>
                            <div class="plans-table-info-btn"></div>
                        </div>
                        <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}">
                            <div v-for="item in services" class="plans-table-td">
                                <Icon v-if="item.supports[0]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                            <div class="plans-table-info-btn">
                                <div class="plans-info-btns">
                                    <a href="https://github.com/kuaifan/dootask/tree/v0.13.0" class="github" target="_blank"><Icon type="logo-github"/></a>
                                </div>
                            </div>
                        </div>
                        <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}">
                            <div v-for="item in services" class="plans-table-td">
                                <Icon v-if="item.supports[1]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                            <div class="plans-table-info-btn">
                                <div class="plans-info-btns">
                                    <a href="https://github.com/kuaifan/dootask/tree/pro" class="github" target="_blank"><Icon type="logo-github"/></a>
                                </div>
                            </div>
                        </div>
                        <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}">
                            <div v-for="item in services" class="plans-table-td">
                                <Icon v-if="item.supports[2]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                            <div class="plans-table-info-btn">
                                <div class="plans-info-btns">
                                    <a href="https://github.com/kuaifan/dootask/tree/pro" class="github" target="_blank"><Icon type="logo-github"/></a>
                                    <ETooltip :content="$L('帐号：admin、密码：123456')">
                                        <a href="https://www.dootask.com" class="btn mini" target="_blank">{{$L('体验DEMO')}}</a>
                                    </ETooltip>
                                </div>
                            </div>
                        </div>
                        <div @mouseenter="active=4" class="plans-table-item" :class="{active:active==4}">
                            <div v-for="item in services" class="plans-table-td">
                                <Icon v-if="item.supports[3]" type="md-checkmark" />
                                <span v-else> - </span>
                            </div>
                            <div class="plans-table-info-btn">
                                <a href="javascript:void(0)" class="btn btn-contact" @click="contactShow=true">{{$L('联系我们')}}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <div class="fluid-info fluid-info-1">
                <div class="fluid-info-item">
                    <div class="info-title">
                        {{$L('多种部署方式随心选择')}}
                    </div>
                    <div class="info-function">
                        <div class="func-item">
                            <div class="image">
                                <ImgView src="images/pro/1.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('公有云')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('无需本地环境准备，按需购买帐户，专业团队提供运维保障服务，两周一次的版本迭代')}}
                                </div>
                            </div>
                        </div>
                        <div class="func-item">
                            <div class="image">
                                <ImgView src="images/pro/2.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('私有云')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('企业隔离的云服务器环境，高可用性，网络及应用层完整隔离，数据高度自主可控')}}
                                </div>
                            </div>
                        </div>
                        <div class="func-item">
                            <div class="image image-80">
                                <ImgView src="images/pro/3.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('本地服务器')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('基于 Docker 的容器化部署，支持高可用集群，快速弹性扩展，数据高度自主可控')}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fluid-info">
                <div class="fluid-info-item">
                    <div class="info-title">
                        {{$L('完善的服务支持体系')}}
                    </div>
                    <div class="info-function">
                        <div class="func-item">
                            <div class="image">
                                <ImgView src="images/pro/4.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('1:1客户成功顾问')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('资深客户成功顾问对企业进行调研、沟通需求、制定个性化的解决方案，帮助企业落地')}}
                                </div>
                            </div>
                        </div>
                        <div class="func-item">
                            <div class="image image-80">
                                <ImgView src="images/pro/5.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('完善的培训体系')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('根据需求定制培训内容，为不同角色给出专属培训方案，线上线下培训渠道全覆盖')}}
                                </div>
                            </div>
                        </div>
                        <div class="func-item">
                            <div class="image">
                                <ImgView src="images/pro/6.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('全面的支持服务')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('多种支持服务让企业无后顾之忧，7x24 线上支持、在线工单、中英文邮件支持、上门支持')}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="fluid-info fluid-info-3">
                <div class="fluid-info-item">
                    <div class="info-title">
                        {{$L('多重安全策略保护数据')}}
                    </div>
                    <div class="info-function">
                        <div class="func-item">
                            <div class="image">
                                <ImgView src="images/pro/7.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('高可用性保证')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('多重方式保证数据不丢失，高可用故障转移，异地容灾备份，99.99\%可用性保证')}}
                                </div>
                            </div>
                        </div>
                        <div class="func-item">
                            <div class="image image-80">
                                <ImgView src="images/pro/8.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('数据加密')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('多重方式保证数据不泄漏，基于 TLS 的数据加密传输，DDOS 防御和入侵检测')}}
                                </div>
                            </div>
                        </div>
                        <div class="func-item">
                            <div class="image image-50">
                                <ImgView src="images/pro/9.svg"/>
                            </div>
                            <div class="func-desc">
                                <div class="desc-title">
                                    {{$L('帐户安全')}}
                                </div>
                                <div class="desc-text">
                                    {{$L('多重方式保证帐户安全，远程会话控制，设备绑定，安全日志以及手势密码')}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="systemConfig.home_footer" class="contact-footer"><span v-html="systemConfig.home_footer"></span></div>

        <!--联系我们-->
        <Modal
            v-model="contactShow"
            :title="$L('联系我们')"
            width="430">
            <p>{{$L('如有任何问题，欢迎使用邮箱与我们联系。')}}</p>
            <p>{{$L('邮箱地址：aipaw@live.cn')}}</p>
            <div slot="footer" class="adaption">
                <Button type="primary" @click="contactShow=false">{{$L('确定')}}</Button>
            </div>
        </Modal>

        <!--更新日志-->
        <UpdateLog v-model="uplogShow" :update-log="showItem.updateLog" :update-ver="showItem.updateVer"/>
    </div>
</template>

<style lang="scss" scoped>
.page-plans {
    height: 100%;
    overflow: auto;
    background-color: #ffffff;

    > div {
        min-width: 1120px;
    }

    .top-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 640px;
        padding-top: 192px;
        z-index: 0;
        background: url("../images/pro/banner-bg.png") center top no-repeat;
        background-size: 100% 100%;
    }

    .top-menu {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        z-index: 2;
        .header {
            height: 50px;
            max-width: 1200px;
            margin: 18px auto 0;
            .z-row {
                color: #fff;
                height: 50px;
                position: relative;
                z-index: 2;
                margin: 0 auto;
                .header-col-sub {
                    padding: 0 20px;
                    .logo {
                        width: 143px;
                        height: 36px;
                        background: url("../images/logo-index.svg") no-repeat center center;
                        background-size: contain;
                        cursor: pointer;
                    }
                }
                .z-1 {
                    dl {
                        position: absolute;
                        right: 20px;
                        top: 0;
                        font-size: 14px;
                        dd {
                            line-height: 32px;
                            color: #fff;
                            cursor: pointer;
                            margin-right: 1px;
                            display: flex;
                            align-items: center;
                            .right-info {
                                display: inline-block;
                                cursor: pointer;
                                margin-left: 24px;
                                color: #ffffff;
                                .right-icon {
                                    font-size: 26px;
                                    vertical-align: middle;
                                }
                            }
                            .item-center {
                                display: flex;
                                align-items: center;
                            }
                        }
                    }
                }
            }
        }
    }

    .banner {
        position: relative;
        z-index: 1;
        padding-top: 192px;
        .banner-title {
            font-size: 50px;
            text-align: center;
            padding: 0 10px;
            color: #fff;
        }
        .banner-desc {
            font-size: 18px;
            color: #fff;
            text-align: center;
            padding: 0 25px;
            max-width: 940px;
            margin-left: auto;
            margin-right: auto;
            margin-top: 40px;
            line-height: 38px;
        }

        .plans-table {
            min-width: 900px;
            max-width: 1120px;
            margin: 110px auto 100px;
            box-shadow: 0 10px 30px rgba(172, 184, 207, 0.3);
            em {
                font-style: normal;
                font-size: 14px;
                color: #666666;
            }
            .plans-table-bd {
                background-color: #fff;
                display: flex;
                .plans-table-item {
                    flex: 1;
                    border-left: 1px solid #eee;
                    position: relative;
                    z-index: 1;
                    & > div {
                        transition: background 0.3s;
                        border-bottom: 1px solid #eee;
                        &:first-child,
                        &:last-child {
                            border-bottom: none;
                        }
                    }
                    &:first-child {
                        flex: none;
                        width: 25.7%;
                        border-left: none;
                    }
                    &::before {
                        content: "";
                        position: absolute;
                        width: 100%;
                        height: 100%;
                        left: 0;
                        top: 0;
                        background: transparent;
                        border-radius: 0;
                        z-index: -2;
                        transform: scaleY(1);
                        transition: all 0.3s;
                    }
                    &.active {
                        position: relative;
                        border-left-color: transparent;
                        & > div {
                            border-color: transparent !important;
                            background: transparent;
                        }
                        &::before {
                            z-index: -1;
                            border-radius: 2px;
                            background: #fff;
                            transform: scaleY(1.05);
                            box-shadow: 0 10px 30px rgba(172, 184, 207, 0.3);
                        }
                        & + .plans-table-item {
                            border-left-color: transparent;
                        }
                    }
                }
            }
            .plans-table-app {
                .plans-table-td {
                    height: 60px;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    text-align: center;
                    padding: 0 12px;
                    &:first-child {
                        border-bottom: 1px solid #eee !important;
                    }
                    > span {
                        font-family:-apple-system, Arial, sans-serif;
                    }
                }
                .plans-table-item {
                    .plans-table-td {
                        position: relative;
                        i {
                            color: #22d7bb;
                            font-size: 20px;
                        }
                        & > .info {
                            position: absolute;
                            font-size: 12px;
                            color: #888;
                            top: 50%;
                            left: 50%;
                            transform: translate(30%, -50%);
                        }
                    }
                    .plans-table-info-btn {
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        height: 100px;
                    }
                    &.first {
                        .plans-table-td {
                            font-size: 14px;
                            color: #666;
                            i {
                                width: 34px;
                                font-size: 20px;
                                text-align: center;
                                transform: translateX(-5px);
                            }
                            &:nth-child(1) {
                                i {
                                    color: #ff7747;
                                }
                            }
                            &:nth-child(2) {
                                i {
                                    color: #f669a7;
                                }
                            }
                            &:nth-child(3) {
                                i {
                                    color: #ffa415;
                                }
                            }
                            &:nth-child(4) {
                                i {
                                    color: #2dbcff;
                                }
                            }
                            &:nth-child(5) {
                                i {
                                    color: #66c060;
                                }
                            }
                            &:nth-child(6) {
                                i {
                                    color: #99d75a;
                                }
                            }
                            &:nth-child(7) {
                                i {
                                    color: #4e8af9;
                                }
                            }
                            &:nth-child(8) {
                                i {
                                    color: #ff5b57;
                                }
                            }
                            &.plans-table-app-okr {
                                position: relative;
                                &::after {
                                    content: "(OKR)";
                                    position: absolute;
                                    top: 50%;
                                    left: 50%;
                                    transform: translate(90%, -50%);
                                }
                            }
                        }
                    }
                }
            }
            .plans-table-info-flex {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }
            .plans-table-info {
                .plans-table-info-th {
                    height: 70px;
                    line-height: 26px;
                    background-color: #eef2f8;
                    font-size: 16px;
                    color: #485778;
                    text-align: center;
                    font-weight: 600;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    flex-wrap: wrap;
                    align-content: center;
                    span {
                        height: 18px;
                        line-height: 18px;
                        font-size: 14px;
                        padding: 0 8px;
                        background-color: #fa3d3f;
                        border-radius: 2px;
                        color: #fff;
                        font-weight: normal;
                        margin-left: 7px;
                    }
                }
                .plans-table-info-price {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    height: 265px;
                    .plans-version {
                        margin-bottom: 30px;
                    }
                    .currency {
                        height: 35px;
                        position: relative;
                        margin-bottom: 18px;
                        &::before {
                            content: "￥";
                            color: #485778;
                            position: absolute;
                            font-size: 18px;
                            left: 0;
                            top: 0;
                            transform: translate(-110%, -20%);
                        }
                        > em {
                            font-size: 36px;
                            font-weight: 900;
                            display: inline-block;
                            margin-top: -10px;
                            height: 56px;
                            line-height: 56px;
                            &.custom {
                                font-size: 24px;
                                font-weight: 500;
                            }
                        }
                    }
                }
                .plans-table-info-desc {
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: center;
                    height: 70px;
                    font-size: 14px;
                    color: #aaaaaa;
                    text-align: center;
                    padding: 0 12px;
                }
            }
            .plans-table-info-btn {
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                height: 115px;
                .plans-info-btns {
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    .btn {
                        padding: 14px 36px;
                        &.mini {
                            margin-left: 8px;
                            padding: 14px 18px;
                        }
                    }
                    .github {
                        & > i {
                            font-size: 32px;
                        }
                    }
                }
                .btn {
                    display: inline-block;
                    color: #fff;
                    background-color: #348FE4;
                    border-color: #348FE4;
                    padding: 14px 34px;
                    font-size: 14px;
                    line-height: 14px;
                    border-radius: 30px;
                    outline: none;
                    &.btn-contact {
                        background-color: #6BC853;
                        border-color: #6BC853;
                    }
                }
            }
            .plans-accordion-head {
                height: 60px;
                line-height: 60px;
                background-color: #eef2f8;
                position: relative;
                z-index: 2;
                display: flex;
                cursor: pointer;
                & > div {
                    width: 25.7%;
                    flex: 1;
                    &.first {
                        width: 25.7%;
                        flex: none;
                        & > span {
                            font-weight: 600;
                            color: #333333;
                            font-size: 14px;
                            padding-left: 30px;
                        }
                    }
                }
                & > span {
                    position: absolute;
                    top: 0;
                    right: 30px;
                    line-height: 60px;
                    height: 60px;
                    transition: transform 0.3s;
                    i {
                        font-size: 20px;
                        color: #aaa;
                    }
                }
                &.plans-accordion-close {
                    & > span {
                        transform: rotate(90deg);
                    }
                }
            }
        }
    }

    .container-fluid {
        margin-left: auto;
        margin-right: auto;
        .fluid-info {
            min-width: 900px;
            &.fluid-info-1 {
                border-bottom: 1px solid #dddddd;
            }
            &.fluid-info-3 {
                background: url("../images/pro/bg-04.jpg");
                background-size: 100% 100%;
            }
            .fluid-info-item {
                max-width: 1120px;
                margin: 0 auto;
                height: 780px;
                padding: 130px 0;
                .info-title {
                    text-align: center;
                    font-size: 42px;
                    color: #333333;
                    margin-bottom: 110px;
                }
                .info-function {
                    .func-item {
                        float: left;
                        width: 33%;
                        text-align: center;
                        padding: 0 40px;
                        .image {
                            height: 215px;
                            margin: 0 auto 40px;
                            img {
                                width: 63%;
                            }
                            &.image-80 {
                                img {
                                    width: 78%;
                                }
                            }
                            &.image-50 {
                                img {
                                    width: 50%;
                                }
                            }
                        }
                        .func-desc {
                            .desc-title {
                                font-size: 16px;
                                color: #333333;
                                margin-bottom: 27px;
                                font-weight: 600;
                            }
                            .desc-text {
                                color: #888888;
                                line-height: 24px;
                            }
                        }
                    }
                }
            }
        }
    }

    .contact-footer {
        margin: 20px 0;
        text-align: center;
        color: #333;

        a, span {
            color: #333;
            margin-left: 10px;
        }
    }
}
</style>
<script>
import {languageList, languageType, setLanguage, addLanguage} from "../language";
import UpdateLog from "./manage/components/UpdateLog";

export default {
    components: {UpdateLog},
    data() {
        return {
            languageList,
            languageType,

            active: 3,

            body1: true,
            body2: true,

            contactShow: false,
            systemConfig: {},

            showItem: {
                pro: false,
                github: '',
                updateLog: '',
                updateVer: ''
            },

            uplogShow: false,

            functions: [
                {label: '项目管理', supports: [1,1,1,1]},
                {label: '文件管理', supports: [1,1,1,1]},
                {label: '团队管理', supports: [1,1,1,1]},
                {label: '即时聊天', supports: [1,1,1,1]},
                {label: '子任务', supports: [1,1,1,1]},
                {label: '国际化', supports: [1,1,1,1]},
                {label: '甘特图', supports: [1,1,1,1]},
                {label: '任务动态', supports: [1,1,1,1]},
                {label: '导出任务', supports: [1,1,1,1]},
                {label: '日程', supports: [1,1,1,1]},
                {label: '周报/日报', supports: [1,1,1,1]},
                {label: '创建群聊', supports: [1,1,1,1]},
                {label: '项目群聊', supports: [1,1,1,1]},
                {label: '项目搜索', supports: [1,1,1,1]},
                {label: '任务类型', supports: [1,1,1,1]},
                {label: '文件搜索', supports: [1,1,1,1]},
                {label: '二维码登录', supports: [0,1,1,1]},
                {label: '聊天机器人', supports: [0,1,1,1]},
                {label: '消息免打扰', supports: [0,1,1,1]},
                {label: '消息标注', supports: [0,1,1,1]},
                {label: '发送语音消息', supports: [0,1,1,1]},
                {label: '会议功能', supports: [0,1,1,1]},
                {label: '部门功能', supports: [0,1,1,1]},
                {label: '签到功能', supports: [0,1,1,1]},
                {label: 'LDAP登录', supports: [0,1,1,1]},
                {label: '临时帐号', supports: [0,1,1,1]},
                {label: '匿名消息', supports: [0,1,1,1]},
                {label: '回复消息', supports: [0,1,1,1]},
                {label: '表情回复', supports: [0,1,1,1]},
                {label: '搜索消息', supports: [0,1,1,1]},
                {label: '任务重复周期', supports: [0,1,1,1]},
                {label: 'Mac/PC客户端', supports: [0,1,1,1]},
                {label: 'iOS/Android客户端', supports: [0,1,1,1]},
            ],
            services: [
                {label: '自助支持', sublabel:'（Issues/社群）', supports: [1,1,1,1]},
                {label: '支持私有化部署', supports: [1,1,1,1]},
                {label: '绑定自有域名', supports: [1,1,1,1]},
                {label: '二次开发', supports: [1,1,1,1]},
                {label: '二次开发咨询服务', supports: [0,0,1,1]},
                {label: '允许隐藏或定制产品名', supports: [0,0,1,1]},
                {label: '在线咨询支持', supports: [0,0,1,1]},
                {label: '电话咨询支持', supports: [0,0,1,1]},
                {label: '中英文邮件支持', supports: [0,0,1,1]},
                {label: '一对一客户顾问', supports: [0,0,1,1]},
                {label: '产品培训', supports: [0,0,1,1]},
                {label: '上门支持', supports: [0,0,1,1]},
                {label: '专属客户成功经理', supports: [0,0,1,1]},
                {label: '免费提供一次内训', supports: [0,0,1,1]},
                {label: '明星客户案例', supports: [0,0,1,1]},
            ]
        }
    },

    created() {
        addLanguage([
            {"key": "多种支持服务让企业无后顾之忧，7x24 线上支持、在线工单、中英文邮件支持、上门支持","zh": "","zh-CHT": "多種支持服務讓企業無後顧之憂，7x24 線上支持、在線工單、中英文郵件支持、上門支持","en": "A variety of support services let the enterprise no worries, 7x24 online support, online work order, English and Chinese email support, door-to-door support","ko": "다양한 지원 서비스, 7x24 온라인 지원, 온라인 업무 주문, 중영문 이메일 지원, 방문 지원 등 기업이 근심걱정이 없도록 합니다","ja": "7x24オンラインサポート、オンラインマニュアル、中国語と英語のメールサポート、訪問サポートなど、様々なサポートサービスがあります。","de": "Eine reihe Von unterstützungsdiensten hat dieses unternehmen unerreichbar gemacht: online-unterstützung auf der 7x24 online-seite, online-unterstützung, chinesische und englische mails sowie unterstützung zu hause","fr": "Une variété de services de soutien pour les entreprises sans soucis, 7x24 support en ligne, ordres de travail en ligne, chinois et anglais soutien par courrier électronique, soutien à la porte","id": "Berbagai layanan dukungan telah membantu usaha mereka"},
            {"key": "无需本地环境准备，按需购买帐户，专业团队提供运维保障服务，两周一次的版本迭代","zh": "","zh-CHT": "無需本地環境準備，按需購買帳戶，專業團隊提供運維保障服務，兩週一次的版本迭代","en": "No need for local environment preparation, purchase accounts on demand, professional team to provide operation and maintenance services, biweekly version iteration","ko": "지역 환경에 대한 준비 없이 주문형 계정을 구입하며 전문 팀에 의해 운영 관리 서비스가 제공되며 2주에 한 번씩 버전을 반복합니다","ja": "ローカル環境の準備は必要ありません、オンデマンドでアカウントを購入、専門チームによる運用・保守保証サービス、2週間に1回のバージョン反復","de": "Fachgruppen liefern personensicherungsdienste, ohne dass die örtlichen gegebenheiten berücksichtigt werden müssen, und liefern zweiwöchige versionen Von isps","fr": "Aucune préparation de l’environnement local, achat de compte à la demande, service de garantie o&e par une équipe spécialisée, itération de la version bi-hebdomadaire","id": "Tak perlu persiapan lingkungan lokal, membeli rekening yang diperlukan, dan tim profesional menyediakan layanan pengamanan transportasi, dan iterasi versi dua minggu"},
            {"key": "资深客户成功顾问对企业进行调研、沟通需求、制定个性化的解决方案，帮助企业落地","zh": "","zh-CHT": "資深客戶成功顧問對企業進行調研、溝通需求、制定個性化的解決方案，幫助企業落地","en": "Senior customer success consultant to the enterprise research, communication needs, develop personalized solutions, to help the enterprise landing","ko": "베테랑 고객 성공 고문은 기업에 대해 조사 연구를 진행하고, 요구를 소통하며, 개성화된 해결 방안을 제정하여 기업의 정착을 돕습니다","ja": "ベテランクライアント成功コンサルタントは、企業のニーズを調査し、コミュニケーションし、箇性化された解決案を作成して、企業の定着を支援します。","de": "Erfahrene berater berater recherchieren, kommunizieren und individuelle lösungen erarbeiten, die das unternehmen zum scheitern bringen","fr": "Le conseiller senior de succès de client effectue des recherches sur les entreprises, communique les besoins, élabore des solutions personnalisées, aide les entreprises à atterrir","id": "Konsultan senior yang berhasil melakukan riset, kebutuhan komunikasi, dan resolusi pribadi untuk membantu bisnis ini mendarat"},
            {"key": "企业隔离的云服务器环境，高可用性，网络及应用层完整隔离，数据高度自主可控","zh": "","zh-CHT": "企業隔離的雲服務器環境，高可用性，網絡及應用層完整隔離，數據高度自主可控","en": "Enterprise isolated cloud server environment, high availability, complete isolation of network and application layer, highly autonomous and controllable data","ko": "기업에서 격리된 클라우드 서버 환경은 가용성이 높아 네트워크와 애플리케이션 계층이 완전히 격리되고 데이터가 고도로 자율적이고 제어가 가능하다","ja": "企業から分離されたクラウドサーバー環境、高可用性、ネットワークおよびアプリケーション層の完全な分離、データの高度な自律制御が可能です。","de": "Cloud-server-umfeld zur unternehmensquaranting, hohe verfügbarkeit, vollständige cyber-isolation und vorhersehbare daten","fr": "Environnement de serveur cloud isolé pour les entreprises, haute disponibilité, isolation complète des couches réseau et application, haute autonomie et contrôle des données","id": "Lingkungan server awan yang terisolasi dari bisnis, sangat tersedia, jaringan dan lapisan aplikasi aman, dan data sangat otonom"},
            {"key": "根据需求定制培训内容，为不同角色给出专属培训方案，线上线下培训渠道全覆盖","zh": "","zh-CHT": "根據需求定製培訓內容，爲不同角色給出專屬培訓方案，線上線下培訓渠道全覆蓋","en": "Training content is customized according to the needs, and exclusive training programs are provided for different roles, with full coverage of online and offline training channels","ko": "수요에 따라 훈련내용을 맞춤화하고 부동한 역할을 위해 전속적인 교육방안을 내놓으며 온라인과 오프라인의 교육경로를 전면적으로 커버한다","ja": "ニーズに合わせてトレーニング内容をカスタマイズし、それぞれのキャラクターに合ったトレーニングプランを提供しています。オンラインとオフラインのトレーニングをすべてカバーしています。","de": "Weicht man Von den anforderungen ab, werden spezielle ausbildungsprogramme für die unterschiedlichen rollen bereitgestellt und über alle online-kanäle bereitgestellt","fr": "Personnaliser le contenu de la formation en fonction de la demande, donner des programmes de formation exclusifs pour différents rôles, couverture complète des canaux de formation en ligne et hors ligne","id": "Program pelatihan eksklusif untuk berbagai peran diberikan, sesuai dengan kebutuhan, dan saluran pelatihan via telepon yang disesuaikan"},
            {"key": "DooTask 是新一代团队协作平台，您可以根据您团队的需求，选择合适的产品功能。","zh": "","zh-CHT": "DooTask 是新一代團隊協作平臺，您可以根據您團隊的需求，選擇合適的產品功能。","en": "DooTask is a next-generation team collaboration platform that allows you to select the right product features for your team's needs.","ko": "'두태스크'는 팀의 필요에 따라 제품에 맞는 기능을 선택할 수 있는 차세대 팀워크 플랫폼입니다.","ja": "DooTaskは次世代のチームワークプラットフォームです。チームのニーズに応じて、適切な製品機能を選ぶことができます。","de": "Die dingsda ist die härtere arbeitsplatte, mit der sie die produktfunktion nach den bedürfnissen ihrer gruppe auswählen können.","fr": "DooTask est une plateforme de collaboration d’équipe de nouvelle génération. En fonction des besoins de votre équipe, vous pouvez choisir les fonctionnalités de produit appropriées.","id": "Penugasan adalah platform kerjasama tim generasi baru. Anda dapat memilih fitur produk yang sesuai sesuai kebutuhan tim anda."},
            {"key": "基于 Docker 的容器化部署，支持高可用集群，快速弹性扩展，数据高度自主可控","zh": "","zh-CHT": "基於 Docker 的容器化部署，支持高可用集羣，快速彈性擴展，數據高度自主可控","en": "Containerized deployment based on Docker supports high availability cluster, fast and elastic expansion, and highly autonomous and controllable data","ko": "Docker 기반의 컨테이너화 배포를 통해 고가용성 클러스터, 빠른 탄력성, 높은 자율적 데이터 제어가 가능하다","ja": "Dockerベースのコンテナ化配置、高利用可能なクラスタのサポート、迅速で柔軟な拡張、高度に自律的なデータ制御が可能です。","de": "Basierend auf dockers containern ist es möglich, starke agglomerationen zu bilden, die eine hohe elastizität und kontrollierbare daten ermöglichen","fr": "Déploiement container basé sur Docker, prise en charge de clusters hautement disponibles, expansion rapide et élastique, données hautement autonomes et contrôlables","id": "Penyebaran penahanan berbasis Docker, mendukung cluster yang sangat tersedia, perpanjangan cepat lentur, dan data sangat otonom"},
            {"key": "多重方式保证数据不丢失，高可用故障转移，异地容灾备份，99.99%可用性保证","zh": "","zh-CHT": "多重方式保證數據不丟失，高可用故障轉移，異地容災備份，99.99%可用性保證","en": "Multiple modes guarantee data loss, high availability failover, remote disaster recovery backup, and 99.99% availability","ko": "다중방식으로 데이타가 분실되지 않도록 담보하고 고가용성 고장전이, 타지역재해허용백업하여 99.99%의 가용성을 보장한다","ja": "多重の方式はデータが失わないことを保証して、高利用可能な故障の転移、異地に災害を許容するバックアップ、99.99%利用可能性の保証です","de": "Mehrere wege, um sicherzustellen, dass keine daten verloren gehen, störungen werden hoch gelagert, eine externe erdbebensicherung: 999%","fr": "Plusieurs façons de garantir que les données ne sont pas perdues, failover haute disponibilité, sauvegarde hors site Dr, garantie de disponibilité à 99,99%","id": "Beberapa cara memastikan data tidak hilang, transfer macet yang tinggi, cadangan bencana jarak jauh, dijamin 99,99%"},
            {"key": "多重方式保证帐户安全，远程会话控制，设备绑定，安全日志以及手势密码","zh": "","zh-CHT": "多重方式保證帳戶安全，遠程會話控制，設備綁定，安全日誌以及手勢密碼","en": "Multiple ways to ensure account security, remote session control, device binding, security logging, and gesture passwords","ko": "여러 가지 방법으로 계정 보안, 원격 세션 제어, 장치 바인딩, 보안 로그 및 제스처 비밀번호를 보장합니다","ja": "復数の方法でアカウントの安全を保証して、遠隔のセッションの制御、装置のバインディング、安全なログとジェスチャーの暗号化です","de": "Sie verschiedene systeme, um das konto sicherer zu machen, die sitzungskontrolle aus der ferne zu sichern, das programm zu programmieren, das sicherheitsprotokoll und das gesten passwort","fr": "Plusieurs façons de garantir la sécurité du compte, le contrôle de session à distance, la liaison des appareils, les journaux de sécurité ainsi que les mots de passe gestuels","id": "Beberapa cara untuk menjamin keamanan akun, kontrol sesi jarak jauh, pengikat perangkat, log keamanan, dan password gerak"},
            {"key": "多重方式保证数据不泄漏，基于 TLS 的数据加密传输，DDOS 防御和入侵检测","zh": "","zh-CHT": "多重方式保證數據不泄漏，基於 TLS 的數據加密傳輸，DDOS 防禦和入侵檢測","en": "Multiple methods to ensure data leakage, TLS based data encryption transmission, DDOS defense and intrusion detection","ko": "다중 보안 데이터 유출 방지, tls 기반 암호화 전송, ddos 방어 및 침입 탐지","ja": "データ漏洩防止、TLSによるデータの暗号化、DDOSの防御、侵入検知を複数で行います。","de": "Dies stellt sicher, dass die daten nicht kompromittiert sind, und es gibt einige lücken bei den TLS, verschlüsselte übertragungen, die verteidigung für DDOS und die erkennung Von lücken","fr": "Plusieurs façons de garantir aucune fuite de données, transmission cryptée des données basée sur TLS, protection contre les DDOS et détection d’intrusion","id": "Beberapa cara memastikan data tidak bocor, transmisi data terenkripsi berdasarkan TLS, DDOS pertahanan dan deteksi penyusup"},
            {"key": "从现在开始，DooTask 为世界各地的团队提供支持，探索适合您的选项。","zh": "","zh-CHT": "從現在開始，DooTask 爲世界各地的團隊提供支持，探索適合您的選項。","en": "From now on, DooTask supports teams around the world to explore options that are right for you.","ko": "지금부터 dootask 에서는 전세계의 팀들을 지원하면서 어떤 옵션을 선택하실 수 있는지 알아보십시오.","ja": "これからDooTaskは世界中のチームをサポートし、あなたに合った選択肢を模索していきます。","de": "Von jetzt an steht die härteste welle jedem team auf der ganzen welt zur verfügung und sonnt auch mal die ganz speziellen einstellungen für sie.","fr": "Désormais, DooTask offre une assistance aux équipes du monde entier. Explorez les options qui vous conviennent.","id": "Mulai sekarang, tugasmu tugaskan tugaskan tim di seluruh dunia, temukan berbagai pilihan yang cocok untukmu."},
            {"key": "如有任何问题，欢迎使用邮箱与我们联系。","zh": "","zh-CHT": "如有任何問題，歡迎使用郵箱與我們聯繫。","en": "If you have any questions, please feel free to contact us by email.","ko": "만약 문제가 있는 경우에, 환영 메일함으로 저희에게 연락한다.","ja": "何か問題があれば、メールボックスを使って私達に連絡することを歓迎します。","de": "Bei weiteren fragen können wir uns mit dem briefkasten melden.","fr": "Toute question, bienvenue à nous contacter à l’aide de l’email.","id": "Jika ada pertanyaan, silakan hubungi kami lewat email."},
            {"key": "允许隐藏或定制产品名","zh": "","zh-CHT": "允許隱藏或定製產品名","en": "Allows you to hide or customize product names","ko": "제품 이름을 숨기거나 사용자 정의할 수 있습니다","ja": "製品名を隠すこともカスタマイズすることもできます","de": "Ein system erlaubt es, anonym Oder individuell festzulegen","fr": "Permet de cacher ou personnaliser les noms de produits","id": "Memungkinkan untuk menyembunyikan atau menyesuaikan nama produk"},
            {"key": "多种部署方式随心选择","zh": "","zh-CHT": "多種部署方式隨心選擇","en": "Multiple deployment modes are optional","ko": "여러가지 포치방식을 마음대로 선택한다","ja": "様々な配置方法を選択できます","de": "Verschiedene dislozierungsmodalitäten. Individuelle einsätze","fr": "Plusieurs modes de déploiement au choix","id": "Dalam berbagai cara"},
            {"key": "多重安全策略保护数据","zh": "","zh-CHT": "多重安全策略保護數據","en": "Multiple security policies protect data","ko": "여러 보안 정책으로 데이터를 보호합니다","ja": "複数のセキュリティポリシーでデータを保護します","de": "Verschiedene sicherheitsstrategien schützen daten","fr": "Plusieurs politiques de sécurité protègent les données","id": "Strategi keamanan ganda untuk melindungi data"},
            {"key": "拥有最新版本所有功能","zh": "","zh-CHT": "擁有最新版本所有功能","en": "Has all the features of the latest version","ko": "최신 버전의 모든 기능을 갖추고 있다","ja": "最新バージョンのすべての機能を備えています","de": "Mit allen neusten funktionen","fr": "Avec toutes les fonctionnalités de la dernière version","id": "Dengan versi terbaru dari semua fitur"},
            {"key": "根据您的需求量身定制","zh": "","zh-CHT": "根據您的需求量身定製","en": "Tailored to your needs","ko": "당신의 요구에 맞추어 만들다","ja": "あなたのニーズに合わせてカスタマイズします","de": "Ihren bedürfnissen anpassen","fr": "Adapté à vos besoins","id": "Disesuaikan dengan kebutuhan anda"},
            {"key": "完善的服务支持体系","zh": "","zh-CHT": "完善的服務支持體系","en": "Perfect service support system","ko": "완벽한 서비스 지원 체계","ja": "充実したサービスサポート体制です","de": "Bevor sie ihren dienst fortsetzen","fr": "Système de support de service parfait","id": "Layanan pendukung yang sempurna"},
            {"key": "专属客户成功经理","zh": "","zh-CHT": "專屬客戶成功經理","en": "Dedicated customer success manager","ko": "전속 고객 성공 관리자","ja": "専属カスタマーサクセスマネージャーです","de": "Hat einen erfolgreichen kunden","fr": "Gestionnaire exclusif de la réussite client","id": "Manajer sukses untuk pelanggan"},
            {"key": "二次开发咨询服务","zh": "","zh-CHT": "二次開發諮詢服務","en": "Secondary development consulting services","ko": "재개발 자문역","ja": "二次開発コンサルティングサービスです","de": "Die wirtschaftsberatung in reo","fr": "Services de consultation en développement secondaire","id": "Konsultan pengembangan kedua"},
            {"key": "免费提供一次内训","zh": "","zh-CHT": "免費提供一次內訓","en": "Provide a free internal training","ko": "내신을 1회 무료로 제공한다","ja": "内訓を1回無料で提供します","de": "Kostenlos ein fachstudium anbieten","fr": "Une formation interne offerte gratuitement","id": "Dapat satu pelatihan gratis"},
            {"key": "选择适合您的版本","zh": "","zh-CHT": "選擇適合您的版本","en": "Choose the version that works for you","ko": "당신에게 맞는 버전을 선택하십시오","ja": "あなたに合ったバージョンを選びます。","de": "Wählen sie ihre version aus","fr": "Choisissez la version qui vous convient","id": "Pilih versi yang cocok untukmu"},
            {"key": "1:1客户成功顾问","zh": "","zh-CHT": "1:1客戶成功顧問","en": "1:1 Customer success consultant","ko": "1:1 고객 성공 컨설턴트","ja": "1:1カスタマーサクセスアドバイザーです","de": "1:1, ein erfolgreicher berater","fr": "1:1 conseiller de réussite client","id": "1:1, penasihat sukses pelanggan"},
            {"key": "一对一客户顾问","zh": "","zh-CHT": "一對一客戶顧問","en": "One-to-one client consultant","ko": "일대일 고객상담사","ja": "1対1のコンサルタントです","de": "Ein persönlicher berater.","fr": "Conseiller client one to one","id": "Konsultan pelanggan satu lawan satu"},
            {"key": "中英文邮件支持","zh": "","zh-CHT": "中英文郵件支持","en": "English and Chinese email support","ko": "중국어 영문 이메일 지원","ja": "英語と中国語のメールのサポートです","de": "Unterstützung durch chinesische mails","fr": "Support par email en anglais et chinois","id": "Dukungan surat inggris"},
            {"key": "全面的支持服务","zh": "","zh-CHT": "全面的支持服務","en": "Full support services","ko": "전폭적인 지원 서비스","ja": "全面的なサポートサービスです","de": "Ich will eine komplette unterstützung","fr": "Services de support complets","id": "Layanan dukungan penuh"},
            {"key": "完善的培训体系","zh": "","zh-CHT": "完善的培訓體系","en": "Perfect training system","ko": "완벽한 훈련 시스템","ja": "充実した教育システムです","de": "Aber die weiterbildung ist gut","fr": "Un système de formation complet","id": "Sistem pelatihan yang sempurna"},
            {"key": "支持私有化部署","zh": "","zh-CHT": "支持私有化部署","en": "Support privatization deployment","ko": "민영화 배치 지지","ja": "民営化を支援します","de": "Unterstützung für privatisierung","fr": "Soutien au déploiement privatisé","id": "Penyebaran privatisasi didukung"},
            {"key": "iOS\/Android客户端","zh": "","zh-CHT": "IOS\/Android客戶端","en": "IOS\/Android client","ko": "Ios\/안드로이드 클라이언트","ja": "IOS\/Androidクライアントです","de": "Ein Android ist auf den markt gegangen","fr": "Client iOS\/Android","id": "Pelanggan iOS\/Android"},
            {"key": "（Issues\/社群）","zh": "","zh-CHT": "（Issues\/社羣）","en": "(Issues\/ Community)","ko": "(issues\/커뮤니티)","ja": "(Issues\/コミュニティです)","de": "Eskips (herzen)","fr": "(Issues\/ communauté)","id": "(Issues\/ social)"},
            {"key": "在线咨询支持","zh": "","zh-CHT": "在線諮詢支持","en": "Online consultation support","ko": "온라인 컨설팅 지원","ja": "オンライン相談支援です","de": "Unterstützung für online-beratung","fr": "Soutien de consultation en ligne","id": "Dukungan konseling online"},
            {"key": "明星客户案例","zh": "","zh-CHT": "明星客戶案例","en": "Star customer case","ko": "스타 고객 사례","ja": "スター顧客事例です","de": "Der fall der star-klienten","fr": "Cas clients star","id": "Kasus klien bintang"},
            {"key": "电话咨询支持","zh": "","zh-CHT": "電話諮詢支持","en": "Telephone consultation support","ko": "전화 상담 지원","ja": "お電話サポートです。","de": "Telefonberatung, verstärkung.","fr": "Soutien de consultation téléphonique","id": "Dukungan konsultasi telepon"},
            {"key": "绑定自有域名","zh": "","zh-CHT": "綁定自有域名","en": "Bind own domain name","ko": "자체 도메인 바인딩","ja": "ドメイン名を付けています","de": "Einen main-namen habe ich auch","fr": "Domaine propre lié","id": "Terikat punya nama domain"},
            {"key": "选择适合你的","zh": "","zh-CHT": "選擇適合你的","en": "Choose what works for you","ko": "네게 맞는 것을 골라라","ja": "あなたに合ったものを選びます","de": "Wählen sie, was ihnen passt","fr": "Choisissez ce qui vous convient","id": "Pilih apa yang cocok untukmu"},
            {"key": "高可用性保证","zh": "","zh-CHT": "高可用性保證","en": "High availability assurance","ko": "고가용성 보장","ja": "高可用性保証です","de": "Hohe verfügbarkeit garantiert","fr": "Garantie de haute disponibilité","id": "Jaminan ketersediaan yang tinggi"},
            {"key": "Mac\/PC客户端","zh": "","zh-CHT": "Mac\/PC客戶端","en": "Mac\/PC client","ko": "Mac\/pc 클라이언트","ja": "Mac\/PCクライアントです","de": "Mac\/PC sind am apparat","fr": "Client pour Mac\/PC","id": "Klien Mac\/PC"},
            {"key": "本地服务器","zh": "","zh-CHT": "本地服務器","en": "Local server","ko": "로컬 서버","ja": "ローカルサーバーです","de": "Dem lokalen server.","fr": "Un serveur local","id": "Server lokal"},
            {"key": "周报\/日报","zh": "","zh-CHT": "週報\/日報","en": "Weekly\/daily newspaper","ko": "주간 신문","ja": "週報\/日刊紙です","de": "Aber mein leben verändert sich","fr": "Rapport hebdomadaire\/quotidien","id": "Mingguan\/harian"},
            {"key": "上门支持","zh": "","zh-CHT": "上門支持","en": "Door-to-door support","ko": "방문하여 지원하다.","ja": "訪問支援です","de": "Türunterstützung","fr": "Soutien à domicile","id": "Gerbang untuk mendukung"},
            {"key": "二次开发","zh": "","zh-CHT": "二次開發","en": "Secondary development","ko": "이차 개발","ja": "二次開発です","de": "Eine zweite entwicklung.","fr": "Le développement secondaire","id": "Pengembangan kedua"},
            {"key": "产品培训","zh": "","zh-CHT": "產品培訓","en": "Product training","ko": "제품 교육","ja": "製品トレーニングです","de": "1. Weiterbildung","fr": "Formation aux produits","id": "Pelatihan produk"},
            {"key": "任务动态","zh": "","zh-CHT": "任務動態","en": "Task dynamics","ko": "작업 동향","ja": "ミッション・ダイナミクスです","de": "Missionsdynamik.","fr": "Dynamique des tâches","id": "Dinamika tugas"},
            {"key": "任务类型","zh": "","zh-CHT": "任務類型","en": "Task type","ko": "작업 형식","ja": "タスクタイプです","de": "Typ des auftrags?","fr": "Types de missions","id": "Jenis tugas"},
            {"key": "创建群聊","zh": "","zh-CHT": "創建羣聊","en": "Create a group chat","ko": "그룹 채팅 만들기","ja": "グループチャットを作成します","de": "Gruppengespräch kreieren.","fr": "Créer un chat de groupe","id": "Buat grup chat"},
            {"key": "帐户安全","zh": "","zh-CHT": "帳戶安全","en": "Account security","ko": "계정 보안","ja": "口座は安全です","de": "Sichere konten.","fr": "Sécurité du compte","id": "Keamanan akun"},
            {"key": "应用支持","zh": "","zh-CHT": "應用支持","en": "Application support","ko": "지원 적용","ja": "アプリケーションサポートです","de": "Unterstützung durch anwendung.","fr": "Soutien à l’application","id": "Menerapkan dukungan"},
            {"key": "数据加密","zh": "","zh-CHT": "數據加密","en": "Data encryption","ko": "데이터 암호화","ja": "データ暗号化です","de": "Datenverschlüsselung","fr": "Cryptage des données","id": "Enkripsi data"},
            {"key": "文件搜索","zh": "","zh-CHT": "文件搜索","en": "File search","ko": "파일 검색","ja": "ファイル検索です","de": "Suche nach dateien","fr": "Recherche de documents","id": "Pencarian berkas"},
            {"key": "文件管理","zh": "","zh-CHT": "文件管理","en": "Document management","ko": "파일 관리","ja": "ファイル管理です","de": "Halte die dokumentation auf.","fr": "Gestion des documents","id": "Manajemen berkascomment"},
            {"key": "服务支持","zh": "","zh-CHT": "服務支持","en": "Service support","ko": "서비스 지원","ja": "サービスサポートです","de": "Service-support.","fr": "Service et support","id": "Dukungan layanan"},
            {"key": "自助支持","zh": "","zh-CHT": "自助支持","en": "Self-help support","ko": "셀프 지원","ja": "セルフサポートです","de": "Unterstützung. Unterstützen.","fr": "Assistance en auto-assistance","id": "Dukungan diri"},
            {"key": "返回首页","zh": "","zh-CHT": "返回首頁","en": "Return to home page","ko": "홈 페이지로 돌아가기","ja": "トップページに戻ります","de": "Mach die titelseite auf.","fr": "Retour à l’accueil","id": "Kembali ke rumah"},
            {"key": "项目搜索","zh": "","zh-CHT": "項目搜索","en": "Item search","ko": "항목 검색","ja": "項目検索です","de": "Suche nach projekten.","fr": "Recherche de projets","id": "Pencarian proyek"},
            {"key": "项目管理","zh": "","zh-CHT": "項目管理","en": "Project management","ko": "프로젝트 관리","ja": "プロジェクトマネジメントです","de": "Projektmanagement. - projektmanagement?","fr": "Gestion du projet","id": "Manajemen proyek"},
            {"key": "项目群聊","zh": "","zh-CHT": "項目羣聊","en": "Project group chat","ko": "프로젝트 그룹 채팅","ja": "グループトークです","de": "Kleine projektgruppe.","fr": "Projet chat de groupe","id": "Obrolan kelompok proyek"},
            {"key": "体验DEMO","zh": "","zh-CHT": "體驗DEMO","en": "DEMO","ko": "데모 체험","ja": "デモを体験します","de": "DEMO","fr": "DEMO","id": "DEMO"},
            {"key": "公有云","zh": "","zh-CHT": "公有云","en": "Public cloud","ko": "퍼블릭 클라우드","ja": "パブリッククラウドです","de": "Flying corp!","fr": "Cloud public disponible","id": "Publik tahu."},
            {"key": "国际化","zh": "","zh-CHT": "國際化","en": "Internationalization","ko": "국제화","ja": "国際化です","de": "International.","fr": "Internationalisation","id": "Kosmopolitan"},
            {"key": "定制版","zh": "","zh-CHT": "定製版","en": "Custom version","ko": "주문 제작판","ja": "カスタマイズ版です","de": "Etwas maßgeschneidert?","fr": "Édition sur mesure","id": "Edisi khusus"},
            {"key": "甘特图","zh": "","zh-CHT": "甘特圖","en": "Gantt chart","ko": "간터투","ja": "ガントチャートです","de": "La gant.","fr": "Gantt en chiffres","id": "Gant."},
            {"key": "社区版","zh": "","zh-CHT": "社區版","en": "Community edition","ko": "커뮤니티 버전","ja": "コミュニティ版です","de": "In der gemeinde.","fr": "La version communautaire","id": "Halaman masyarakat"},
            {"key": "私有云","zh": "","zh-CHT": "私有云","en": "Private cloud","ko": "개인 클라우드","ja": "プライベートクラウドです","de": "Private wolke.","fr": "Un cloud privé","id": "Awan pribadi"},
            {"key": "自定义","zh": "","zh-CHT": "自定義","en": "Customize","ko": "사용자 정의","ja": "カスタムです","de": "Selbstdefiniert.","fr": "Personnalisé et personnalisé","id": "Khusus"},
            {"key": "人数","zh": "","zh-CHT": "人數","en": "Number of people","ko": "수가","ja": "人数です","de": "Eine anzahl.","fr": "Le nombre de","id": "Jumlah"},
            {"key": "价格","zh": "","zh-CHT": "價格","en": "Price","ko": "가격","ja": "値段です","de": "Dem preis?","fr": "Prix","id": "Harga"},
            {"key": "推荐","zh": "","zh-CHT": "推薦","en": "Recommend","ko": "추천","ja": "推薦します","de": "Eine empfehlung?","fr": "Recommandée","id": "Merekomendasikan"},
            {"key": "日程","zh": "","zh-CHT": "日程","en": "Schedule","ko": "일정","ja": "スケジュールです","de": "Plan?","fr": "Le calendrier","id": "Jadwal"},
            {"key": "概述","zh": "","zh-CHT": "概述","en": "Overview","ko": "요약","ja": "概説します","de": "Erkläre es.","fr": "Résumé","id": "Ikhtisar"},
            {"key": "授权方式","zh": "","zh-CHT": "授權方式","en": "Authorization mode","ko": "인증 방식","ja": "授権方法です","de": "Nicht autorisiert.","fr": "Modalités de mandat","id": "Cara memberdayakan"},
            {"key": "普通版","zh": "","zh-CHT": "普通版","en": "Ordinary edition","ko": "일반판","ja": "通常版です","de": "Normale ausgabe.","fr": "La version normale","id": "Versi biasa"},
            {"key": "功能较少可能会停更","zh": "","zh-CHT": "功能較少可能會停更","en": "Less function may stop","ko": "기능이 부족하면 더 늦을 수도 있습니다","ja": "機能が少ないため停止することもあります","de": "Eine unmenge an funktionen ist wahrscheinlich nicht mehr moglich","fr": "Moins de fonctions peut s’arrêter plus","id": "Sedikit fungsi mungkin akan berhenti lebih"},
            {"key": "无须授权","zh": "","zh-CHT": "無須授權","en": "Without authorization","ko": "인증 필요 없음","ja": "許可は不要です","de": "Dafür ist keine genehmigung nötig.","fr": "Aucune autorisation requise","id": "Tidak perlu perintah."},
            {"key": "Pro免费版","zh": "","zh-CHT": "Pro免費版","en": "Pro Free Edition","ko": "Pro 무료버전","ja": "プロ無料版です","de": "Pro publikum kostenlos.","fr": "Version gratuite Pro","id": "Versi Pro gratis"},
            {"key": "即时聊天","zh": "","zh-CHT": "即時聊天","en": "Instant chat","ko": "인스턴트 채팅","ja": "インスタントチャットです","de": "In echtzeit.","fr": "Chat en direct","id": "Ngobrol langsung"},
            {"key": "Pro订阅版","zh": "","zh-CHT": "Pro訂閱版","en": "Pro subscription","ko": "Pro 구독판","ja": "プロ購読版です","de": "Pro abonnement","fr": "Version abonnement Pro","id": "Edisi Pro"},
            {"key": "二维码登录","zh": "","zh-CHT": "二維碼登錄","en": "Qr code login","ko": "Qr 코드 등록","ja": "Qrコード登録です","de": "Computer mit code zwei","fr": "Connexion qr code","id": "Kode qr masuk"},
            {"key": "聊天机器人","zh": "","zh-CHT": "聊天機器人","en": "Chatbot","ko": "채팅 로봇","ja": "チャットボットです","de": "Die roboter.","fr": "Le chatbot","id": "Ngobrol robot"},
            {"key": "消息标注","zh": "","zh-CHT": "消息標註","en": "Message annotation","ko": "메시지 레이블","ja": "メッセージ表記です","de": "Die nachricht markiert.","fr": "Étiquetage des messages","id": "Label pesan"},
            {"key": "发送语音消息","zh": "","zh-CHT": "發送語音消息","en": "Send voice message","ko": "음성 메시지 보내기","ja": "音声メッセージを送ります","de": "Senden sie eine nachricht.","fr": "Envoyer un message vocal","id": "Kirim pesan suara"},
            {"key": "部门功能","zh": "","zh-CHT": "部門功能","en": "Departmental function","ko": "부서 기능","ja": "部署機能です","de": "Funktion ab abteilung","fr": "Fonction du département","id": "Fungsi departemen"},
            {"key": "LDAP登录","zh": "","zh-CHT": "LDAP登錄","en": "LDAP login","ko": "Ldap 로그인","ja": "LDAPログインです","de": "LDAP loggt sich ein","fr": "Connexion avec LDAP","id": "Log masuk LDAP"},
            {"key": "表情回复","zh": "","zh-CHT": "表情回覆","en": "Expression response","ko": "이모티콘 답장","ja": "スタンプレスです","de": "Emoticon-antworten antworten.","fr": "Emoji répondre à","id": "Balas ekspresi"},
            {"key": "任务重复周期","zh": "","zh-CHT": "任務重複週期","en": "Task repetition cycle","ko": "작업 반복 주기","ja": "繰り返しのサイクルです","de": "Mission dupliziert sich.","fr": "Cycle de répétition des tâches","id": "Siklus tugas berulang"},
            {"key": "帐号：(*)、密码：(*)","zh": "","zh-CHT": "帳號：(*)、密碼：(*)","en": "Account number: (*), Password: (*)","ko": "계정 번호:(*), 비밀번호:(*)","ja": "アカウント番号:(*)、パスワード:(*)です。","de": "Kontonummer (*), passwort (*)","fr": "Numéro de compte :(*), mot de passe :(*)","id": "Akun :(*), kata sandi: *"},
            {"key": "邮箱地址：(*)","zh": "","zh-CHT": "郵箱地址：(*)","en": "Email Address: (*)","ko": "전자우편 주소:(*)","ja": "メールアドレス:(*)です。","de": "Posteingang :(*)","fr": "Adresse e-mail :(*)","id": "Alamat surat :(*)"},
            {"key": "(*)人","zh": "","zh-CHT": "(*)人","en": "(*) people","ko": "(*)","ja": "(*)人です","de": "(*) menschen","fr": "(*)","id": "(*) orang"}
        ])
    },

    mounted() {
        this.getSetting();
        this.getShowItem();
    },

    computed: {
        appTitle() {
            return `${this.$L('选择适合你的')} ${window.systemInfo.title || "DooTask"}`;
        },
    },

    methods: {
        onLanguage(l) {
            setLanguage(l)
        },

        goHome() {
            this.goForward({name: 'index', query: {action: 'index'}});
        },

        getSetting() {
            this.$store.dispatch("call", {
                url: 'system/setting',
            }).then(({data}) => {
                this.systemConfig = data;
            })
        },

        getShowItem() {
            this.$store.dispatch("call", {
                url: "system/get/showitem",
            }).then(({data}) => {
                this.showItem = data
            }).catch(_ => {
                this.showItem = {}
            });
        },
    }
}
</script>
