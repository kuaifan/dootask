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
            {"key": "多种支持服务让企业无后顾之忧，7x24 线上支持、在线工单、中英文邮件支持、上门支持","zh": "","zh-CHT": "多種支持服務讓企業無後顧之憂，7x24 線上支持、在線工單、中英文郵件支持、上門支持","en": "A variety of support services give the enterprise without worries.","ko": "다양한 지원 서비스는 근로자가없는 기업에 제공합니다.","ja": "さまざまなサポートサービスは、心配することなく企業を提供します。","de": "Eine Vielzahl von Unterstützungsdiensten erteilen das Unternehmen ohne Sorgen.","fr": "Une variété de services de soutien donnent à l'entreprise sans soucis.","id": "Berbagai layanan dukungan memberikan perusahaan tanpa pekerja."},
            {"key": "无需本地环境准备，按需购买帐户，专业团队提供运维保障服务，两周一次的版本迭代","zh": "","zh-CHT": "無需本地環境準備，按需購買帳戶，專業團隊提供運維保障服務，兩週一次的版本迭代","en": "No need to prepare a local environment, buy accounts on demand, professional team provides operation and maintenance security services, and iterations for two weeks once a week","ko": "현지 환경을 준비하고, 주문시 계정을 구매, 프로 프랙틱 팀 제공 및 유지 보수 보안 서비스 및 2 주 동안의 설계가 onces onces onces의 설계가 필요하지 않습니다.","ja": "ローカル環境を準備したり、アカウントをオンデマンドで購入したり、プロのチームが運用とメンテナンスのセキュリティサービスを提供したり、週に1回の反復を提供する必要はありません。","de": "Es ist nicht erforderlich, ein lokales Umfeld vorzubereiten, Konten auf Demand zu kaufen, professionelles Team für Betriebs- und Wartungssicherheitsdienste sowie zwei Wochen einmal pro Woche Iterationen","fr": "Pas besoin de préparer un environnement local, d'acheter des comptes à la demande, l'équipe professionnelle fournit des services de sécurité et de maintenance et des itérations pendant deux semaines une fois par semaine","id": "Tidak perlu menyiapkan lingkungan lokal, membeli akun sesuai permintaan, tim propraktik menyediakan dan layanan keamanan pemeliharaan, dan desain selama dua minggu di atasnya"},
            {"key": "资深客户成功顾问对企业进行调研、沟通需求、制定个性化的解决方案，帮助企业落地","zh": "","zh-CHT": "資深客戶成功顧問對企業進行調研、溝通需求、制定個性化的解決方案，幫助企業落地","en": "Senior customer successful consultants investigate, communicate needs, and formulate personalized solutions to help enterprises land","ko": "선임 고객 성공적인 컨설턴트는 기업 토지를 돕기 위해 조사, 요구를 조사하고 개인화 된 솔루션을 공식화합니다.","ja": "シニア顧客の成功コンサルタントは、企業の土地を支援するためにパーソナライズされたソリューションを調査、コミュニケーション、および策定します","de": "Senior Customer -erfolgreiche Berater untersuchen, kommunizieren Bedürfnisse und formulieren personalisierte Lösungen, um Unternehmen beim Land zu helfen","fr": "Les consultants à succès des clients seniors enquêtent, communiquent les besoins et formulent des solutions personnalisées pour aider les entreprises à terrer","id": "Pelanggan Senior Konsultan sukses menyelidiki, berkomunikasi kebutuhan, dan merumuskan solusi yang dipersonalisasi untuk membantu perusahaan mendarat"},
            {"key": "企业隔离的云服务器环境，高可用性，网络及应用层完整隔离，数据高度自主可控","zh": "","zh-CHT": "企業隔離的雲服務器環境，高可用性，網絡及應用層完整隔離，數據高度自主可控","en": "Enterprise isolation cloud server environment, high availability, network and application layer complete isolation, data high and controllable","ko": "엔터프라이즈 격리 클라우드 서버 환경, 고 가용성, 네트워크 및 애플리케이션 계층 완전한 격리, 데이터 높음 및 제어 화","ja": "エンタープライズ分離クラウドサーバー環境、高可用性、ネットワークおよびアプリケーションレイヤーの完全な分離、データが高く、制御可能なデータ","de": "Enterprise Isolation Cloud Server -Umgebung, hohe Verfügbarkeit, Netzwerk- und Anwendungsschicht vollständige Isolation, Daten hoch und kontrollierbar","fr": "Environnement du serveur cloud d'isolement d'entreprise, haute disponibilité, réseau de réseau et de couche d'application Isolement complet, données élevées et contrôlables","id": "Lingkungan server cloud isolasi perusahaan, ketersediaan tinggi, jaringan dan lapisan aplikasi isolasi lengkap, data tinggi dan pengendalian"},
            {"key": "根据需求定制培训内容，为不同角色给出专属培训方案，线上线下培训渠道全覆盖","zh": "","zh-CHT": "根據需求定制培訓內容，為不同角色給出專屬培訓方案，線上線下培訓渠道全覆蓋","en": "Customize the training content according to the needs, give the exclusive training scheme for different roles, and the online and offline training channels are fully covered","ko": "The The The The The Needs, 다른 역할에 대한 독점 교육 계획을 제공하며 온라인 및 사무실 교육 채널이 완전히 커버됩니다.","ja": "ニーズに応じてトレーニングコンテンツをカスタマイズし、さまざまな役割の排他的なトレーニングスキームを提供すると、オンラインおよびオフラインのトレーニングチャネルが完全にカバーされています","de": "Passen Sie die Schulungsinhalte entsprechend den Anforderungen an, geben Sie das exklusive Schulungsschema für verschiedene Rollen an, und die Online- und Offline -Trainingskanäle sind vollständig abgedeckt","fr": "Personnalisez le contenu de formation en fonction des besoins, donnez le schéma de formation exclusif pour différents rôles, et les canaux de formation en ligne et hors ligne sont entièrement couverts","id": "CustMize the the the the the the the Needs, Berikan skema pelatihan eksklusif untuk peran yang berbeda, dan saluran pelatihan online dan kantor tertutup sepenuhnya"},
            {"key": "DooTask 是新一代团队协作平台，您可以根据您团队的需求，选择合适的产品功能。","zh": "","zh-CHT": "DooTask 是新一代團隊協作平台，您可以根據您團隊的需求，選擇合適的產品功能。","en": "Dootask is a new generation of team collaboration platform. You can choose the appropriate product function according to the needs of your team.","ko": "Dootask는 새로운 세대의 팀 협업 플랫폼입니다. 팀의 필요한 것들에 대한 적절한 양육 재미를 선택할 수 있습니다.","ja": "Dootaskは、新世代のチームコラボレーションプラットフォームです。チームのニーズに応じて適切な製品機能を選択できます。","de": "Dootask ist eine neue Generation von Team -Kollaborationsplattform. Sie können die entsprechende Produktfunktion entsprechend Ihrem Team auswählen.","fr": "Dootask est une nouvelle génération de plate-forme de collaboration d'équipe. Vous pouvez choisir la fonction de produit appropriée en fonction des besoins de votre équipe.","id": "Dootask adalah generasi baru platform kolaborasi tim. Anda dapat memamerkan kesenangan yang menyenangkan untuk kebutuhan tim Anda."},
            {"key": "基于 Docker 的容器化部署，支持高可用集群，快速弹性扩展，数据高度自主可控","zh": "","zh-CHT": "基於 Docker 的容器化部署，支持高可用集群，快速彈性擴展，數據高度自主可控","en": "Based on Docker's containerization deployment, support high available clusters, rapid elastic expansion, data highly controlled and controllable","ko": "Docker의 컨테이너화 배치를 기반으로, 높은 가용 클러스터, 빠른 탄성 확장, 데이터가 고도로 제어되고 제어되는 지원","ja": "Dockerのコンテナ化の展開に基づいて、利用可能な高いクラスター、急速な弾性拡張、高度に制御され、制御可能なデータをサポートします","de": "Basierend auf der Bereitstellung von Docker's Containerisierung, Unterstützung von hohen verfügbaren Clustern, schnelle elastische Expansion, Daten hoch kontrolliert und kontrollierbar","fr": "Sur la base du déploiement de la conteneurisation de Docker, prends en charge les clusters élevés disponibles, l'expansion élastique rapide, les données hautement contrôlées et contrôlables","id": "Berdasarkan penyebaran kontainerisasi Docker, mendukung kluster tinggi yang tersedia, ekspansi elastis cepat, data sangat terkontrol dan dikendalikan"},
            {"key": "多重方式保证数据不丢失，高可用故障转移，异地容灾备份，99.99%可用性保证","zh": "","zh-CHT": "多重方式保證數據不丟失，高可用故障轉移，異地容災備份，99.99%可用性保證","en": "Multiple ways to ensure that the data is not lost, high availability can be transferred, disasters are backup, 99.99%availability guarantee guarantee","ko": "데이터가 길지 않도록하는 여러 가지 방법, 고 가용성을 전송할 수 있고, 재해는 백업되며, 99.99%가용성 보증인 보증인","ja": "データが失われないようにする複数の方法、高可用性を転送し、災害はバックアップ、99.99％の可用性保証保証","de": "Mehrere Möglichkeiten, um sicherzustellen, dass die Daten nicht verloren gehen, kann eine hohe Verfügbarkeit übertragen werden, Katastrophen Backups, 99,99%Verfügbarkeitsgarantie Garantie -Garantie","fr": "Plusieurs façons de s'assurer que les données ne sont pas perdues, la haute disponibilité peut être transférée, les catastrophes sont une sauvegarde, une garantie de garantie de disponibilité de 99,99%","id": "Berbagai cara untuk memastikan bahwa data tidak panjang, ketersediaan tinggi dapat ditransfer, bencana adalah cadangan, 99,99%ketersediaan jaminan jaminan"},
            {"key": "多重方式保证帐户安全，远程会话控制，设备绑定，安全日志以及手势密码","zh": "","zh-CHT": "多重方式保證帳戶安全，遠程會話控制，設備綁定，安全日誌以及手勢密碼","en": "Multiple ways to ensure account security, remote session control, device binding, security logs and gesture passwords","ko": "계정 보안, 원격 세션 제어, 장치 바인딩, 보안 로그 및 제스처 암호를 보장하는 여러 가지 방법","ja": "アカウントセキュリティ、リモートセッション制御、デバイスバインディング、セキュリティログ、ジェスチャーパスワードを確保する複数の方法","de": "Mehrere Möglichkeiten, um die Sicherheitssicherheit, die Steuerung der Remotesitzung, die Gerätebindung, die Sicherheitsprotokolle und die Gestenkennwörter sicherzustellen","fr": "Plusieurs façons d'assurer la sécurité du compte, le contrôle de session à distance, la liaison des périphériques, les journaux de sécurité et les mots de passe gestuels","id": "Berbagai cara untuk memastikan keamanan akun, kontrol sesi jarak jauh, pengikatan perangkat, log keamanan dan kata sandi gerakan"},
            {"key": "多重方式保证数据不泄漏，基于 TLS 的数据加密传输，DDOS 防御和入侵检测","zh": "","zh-CHT": "多重方式保證帳戶安全，遠程會話控制，設備綁定，安全日誌以及手勢密碼","en": "Multi -way guarantees the data is not leaked. TLS -based data encryption transmission, DDOS defense and intrusion detection","ko": "다중 웨이 Guarane 데이터가 유출되지 않습니다. TLS 기반 데이터 암호화 전송, DDOS 방어 및 침입 탐지","ja": "アカウントセキュリティ、リモートセッション制御、デバイスバインディング、セキュリティログ、ジェスチャーパスワードを確保する複数の方法","de": "Multi -Way -Garantien, dass die Daten nicht durchgesickert sind.","fr": "Garantie multi-voies Les données ne sont pas divulguées. Transmission de chiffrement des données basée sur TLS, défense DDOS et détection d'intrusion","id": "Multi -Way Guaranees Data tidak bocor. Transmisi enkripsi data berbasis TLS, DDOS Defense dan Detection Intrusion"},
            {"key": "从现在开始，DooTask 为世界各地的团队提供支持，探索适合您的选项。","zh": "","zh-CHT": "從現在開始，DooTask 為世界各地的團隊提供支持，探索適合您的選項。","en": "From now on, Dootask has provided support for teams around the world and explores the options suitable for you.","ko": "이제부터 Dootask는 전 세계 팀을 위해 지원을 제공했으며 귀하에게 적합한 옵션을 탐색합니다.","ja": "これから、Dootaskは世界中のチームにサポートを提供し、あなたに適したオプションを探ります。","de": "Von nun an hat Dootask Teams auf der ganzen Welt unterstützt und die für Sie geeigneten Optionen untersucht.","fr": "À partir de maintenant, Dootask a fourni un soutien aux équipes du monde entier et explore les options qui vous conviennent.","id": "Mulai sekarang, Dootask telah memberikan yang disediakan untuk tim di seluruh dunia dan mengeksplorasi opsi yang cocok untuk Anda."},
            {"key": "如有任何问题，欢迎使用邮箱与我们联系。","zh": "","zh-CHT": "如有任何問題，歡迎使用郵箱與我們聯繫。","en": "If you have any questions, please use your mailbox to contact us.","ko": "궁금한 점이 있으면 사서함을 사용하여 문의하십시오.","ja": "ご質問がある場合は、メールボックスを使用してお問い合わせください。","de": "Wenn Sie Fragen haben, verwenden Sie bitte Ihre Mailbox, um uns zu kontaktieren.","fr": "Si vous avez des questions, veuillez utiliser votre boîte aux lettres pour nous contacter.","id": "Jika Anda memiliki pertanyaan, silakan gunakan kotak surat Anda untuk menghubungi kami."},
            {"key": "帐号：(*)、密码：(*)","zh": "","zh-CHT": "帳號：(*)、密碼：(*)","en": "Account password:(*)","ko": "계정 비밀번호: (*)","ja": "アカウントパスワード：（*）","de": "Konto Passwort:(*)","fr": "mot de passe du compte:(*)","id": "kata sandi akun: (*)"},
            {"key": "允许隐藏或定制产品名","zh": "","zh-CHT": "允許隱藏或定制產品名","en": "Allow hidden or customized product name","ko": "숨겨져 있거나 맞춤형 제품 이름을 허용하십시오","ja": "非表示またはカスタマイズされた製品名を許可します","de": "Ermöglichen Sie den versteckten oder angepassten Produktnamen","fr": "Autoriser le nom du produit caché ou personnalisé","id": "Izinkan nama produk tersembunyi atau disesuaikan"},
            {"key": "多种部署方式随心选择","zh": "","zh-CHT": "多種部署方式隨心選擇","en": "Choose a variety of deployment methods at will","ko": "마음대로 다양한 배포 방법을 선택하십시오","ja": "さまざまな展開方法を自由に選択してください","de": "Wählen Sie nach Belieben eine Vielzahl von Bereitstellungsmethoden","fr": "Choisissez une variété de méthodes de déploiement à volonté","id": "Pilih berbagai metode penempatan sesuka hati"},
            {"key": "多重安全策略保护数据","zh": "","zh-CHT": "多重安全策略保護數據","en": "Multiple security strategy protection data","ko": "다중 보안 전략 보호 데이터","ja": "複数のセキュリティ戦略保護データ","de": "Mehrere Sicherheitsstrategieschutzdaten","fr": "Données de protection de la stratégie de sécurité multiples","id": "Data Perlindungan Strategi Keamanan Berganda"},
            {"key": "拥有最新版本所有功能","zh": "","zh-CHT": "擁有最新版本所有功能","en": "Have all functions of the latest version","ko": "최신 버전의 모든 기능이 있습니다","ja": "最新バージョンのすべての機能を持っています","de": "Haben Sie alle Funktionen der neuesten Version","fr": "Avoir toutes les fonctions de la dernière version","id": "Memiliki semua fungsi versi terbaru"},
            {"key": "根据您的需求量身定制","zh": "","zh-CHT": "根據您的需求量身定制","en": "Customized according to your needs","ko": "사용자 정의하십시오","ja": "ニーズに応じてカスタマイズされています","de": "Angepasst nach Ihren Bedürfnissen","fr": "Personnalisé en fonction de vos besoins","id": "Menyesuaikan"},
            {"key": "完善的服务支持体系","zh": "","zh-CHT": "完善的服務支持體系","en": "Perfect service support system","ko": "완벽한 서비스 지원 시스템","ja": "完璧なサービスサポートシステム","de": "Perfektes Service -Support -System","fr": "Système de support de service parfait","id": "Sistem Dukungan Layanan yang Sempurna"},
            {"key": "专属客户成功经理","zh": "","zh-CHT": "專屬客戶成功經理","en": "Customer success manager","ko": "고객 성공 관리자","ja": "カスタマーサクセスマネージャー","de": "Kundenerfolgsmanager","fr": "Client Success Manager","id": "Manajer Sukses Pelanggan"},
            {"key": "二次开发咨询服务","zh": "","zh-CHT": "二次開發諮詢服務","en": "Secondary development consulting service","ko": "2 차 개발 컨설팅 서비스","ja": "二次開発コンサルティングサービス","de": "Sekundärentwicklungsberatungsdienst","fr": "Service de conseil en développement secondaire","id": "Layanan Konsultasi Pengembangan Sekunder"},
            {"key": "免费提供一次内训","zh": "","zh-CHT": "免費提供一次內訓","en": "Provide internal training for free","ko": "인터넷을 무료로 제공하십시오","ja": "無料で内部トレーニングを提供します","de": "Bieten Sie kostenlos interne Schulungen an","fr": "Fournir une formation interne gratuitement","id": "Menyediakan internet secara gratis"},
            {"key": "选择适合您的版本","zh": "","zh-CHT": "選擇適合您的版本","en": "Choose the version that suits you","ko": "자신에게 맞는 버전을 선택하십시오","ja": "あなたに合ったバージョンを選択してください","de": "Wählen Sie die Version, die zu Ihnen passt","fr": "Choisissez la version qui vous convient","id": "Pilih versi yang cocok untuk Anda"},
            {"key": "1:1客户成功顾问","zh": "","zh-CHT": "1:1客戶成功顧問","en": "1: 1 Customer successful consultant","ko": "1 : 1 고객 성공적인 컨설턴트","ja": "1：1の顧客成功コンサルタント","de": "1: 1 Kunde erfolgreicher Berater","fr": "1: 1 consultant réussi du client","id": "1: 1 Pelanggan Konsultan Sukses"},
            {"key": "一对一客户顾问","zh": "","zh-CHT": "一對一客戶顧問","en": "One -to -one customer consultant","ko": "하나의 고객 컨설턴트","ja": "1人から1人の顧客コンサルタント","de": "Ein -zu -Ein -Kundenberater","fr": "Un consultant client à un à un","id": "Satu -untuk -satu konsultan pelanggan"},
            {"key": "中英文邮件支持","zh": "","zh-CHT": "中英文郵件支持","en": "Chinese and English email support","ko": "중국어 및 영어 이메일 지원","ja": "中国語と英語の電子メールサポート","de": "Chinesische und englische E -Mail -Support","fr": "Assistance par e-mail chinois et anglais","id": "Dukungan Email Cina dan Inggris"},
            {"key": "全面的支持服务","zh": "","zh-CHT": "全面的支持服務","en": "Comprehensive support service","ko": "포괄적 인 지원 서비스","ja": "包括的なサポートサービス","de": "Umfassender Support -Service","fr": "Service de soutien complet","id": "Layanan Dukungan Komprehensif"},
            {"key": "完善的培训体系","zh": "","zh-CHT": "完善的培訓體系","en": "Comprehensive training system","ko": "포괄적 인 Trining Systemm","ja": "包括的なトレーニングシステム","de": "Umfassendes Trainingssystem","fr": "Système de formation complet","id": "Sistem Trining Komprehensif"},
            {"key": "支持私有化部署","zh": "","zh-CHT": "支持私有化部署","en": "Support privatization deployment","ko": "개인 배포를 지원합니다","ja": "民営化の展開をサポートします","de": "Bereitstellung von Privatisierungen unterstützen","fr": "Soutenir le déploiement de la privatisation","id": "Mendukung penyebaran privatif"},
            {"key": "iOS/Android客户端","zh": "","zh-CHT": "iOS/Android客戶端","en": "IOS/Android client","ko": "iOS/Android 클라이언트","ja": "iOS/Androidクライアント","de": "iOS/Android -Client","fr": "Client iOS / Android","id": "Klien iOS/Android"},
            {"key": "（Issues/社群）","zh": "","zh-CHT": "（Issues/社群）","en": "(ISSUES/Community)","ko": "(문제/커뮤니티)","ja": "（問題/コミュニティ）","de": "(Themen/Community)","fr": "(Problèmes / communauté)","id": "(Masalah/Komunitas)"},
            {"key": "在线咨询支持","zh": "","zh-CHT": "在線諮詢支持","en": "Online consultation support","ko": "온라인 상담 지원","ja": "オンライン相談サポート","de": "Online -Beratungsunterstützung","fr": "Assistance de consultation en ligne","id": "Dukungan Konsultasi Online"},
            {"key": "明星客户案例","zh": "","zh-CHT": "明星客戶案例","en": "Star customer case","ko": "스타 고객 사례","ja": "スターの顧客ケース","de": "Sternkundenfall","fr": "Case client vedette","id": "Kasus Pelanggan Bintang"},
            {"key": "电话咨询支持","zh": "","zh-CHT": "電話諮詢支持","en": "Telephone consultation support","ko": "전화 상담 지원","ja": "電話相談サポート","de": "Telefonberatungsunterstützung","fr": "Assistance de consultation téléphonique","id": "Dukungan Konsultasi Telepon"},
            {"key": "绑定自有域名","zh": "","zh-CHT": "綁定自有域名","en": "Bind your own domain name","ko": "자신의 도메인 이름을 바인딩하십시오","ja": "独自のドメイン名をバインドします","de": "Binden Sie Ihren eigenen Domainnamen","fr": "Lisez votre propre nom de domaine","id": "Ikat nama domain Anda sendiri"},
            {"key": "选择适合你的","zh": "","zh-CHT": "選擇適合你的","en": "Choose suitable for you","ko": "당신에게 적합한 선택을 선택하십시오","ja": "あなたに適した選択を選択してください","de": "Wählen Sie für Sie geeignet","fr": "Choisissez adapté pour vous","id": "Pilih yang cocok untuk Anda"},
            {"key": "高可用性保证","zh": "","zh-CHT": "高可用性保證","en": "High availability guarantee","ko": "고 가용성 보증","ja": "高可用性保証","de": "Hohe Verfügbarkeitsgarantie","fr": "Garantie de haute disponibilité","id": "Jaminan ketersediaan tinggi"},
            {"key": "邮箱地址：(*)","zh": "","zh-CHT": "郵箱地址：(*)","en": "Email address:(*)","ko": "이메일 주소: (*)","ja": "電子メールアドレス：（*）","de": "E-Mail-Addresse:(*)","fr": "adresse e-mail:(*)","id": "alamat email: (*)"},
            {"key": "Mac/PC客户端","zh": "","zh-CHT": "Mac/PC客戶端","en": "Mac/PC client","ko": "Mac/PC 클라이언트","ja": "Mac/PCクライアント","de": "MAC/PC -Client","fr": "Client Mac / PC","id": "Klien Mac/PC"},
            {"key": "本地服务器","zh": "","zh-CHT": "本地服務器","en": "Local server","ko": "로컬 서버","ja": "ローカルサーバー","de": "Lokaler Server","fr": "Serveur local","id": "Server lokal"},
            {"key": "周报/日报","zh": "","zh-CHT": "週報/日報","en": "Weekly/Daily","ko": "주간/매일","ja": "毎週/毎日","de": "Wöchentlich/täglich","fr": "Hebdomadaire / quotidien","id": "Mingguan/harian"},
            {"key": "上门支持","zh": "","zh-CHT": "上門支持","en": "Support","ko": "지원하다","ja": "サポート","de": "Unterstützung","fr": "Soutien","id": "Mendukung"},
            {"key": "二次开发","zh": "","zh-CHT": "二次開發","en": "Secondary development","ko": "이차 개발","ja": "二次発達","de": "Sekundärentwicklung","fr": "Développement secondaire","id": "Pengembangan Sekunder"},
            {"key": "产品培训","zh": "","zh-CHT": "產品培訓","en": "Product training","ko": "제품 교육","ja": "製品トレーニング","de": "Produktschulungen","fr": "Essai de production","id": "Pelatihan produk"},
            {"key": "任务动态","zh": "","zh-CHT": "任務動態","en": "Mission dynamic","ko": "미션 다이나믹","ja": "ミッションダイナミック","de": "Missionsdynamik","fr": "Dynamique de mission","id": "Dinamika Misi"},
            {"key": "任务类型","zh": "","zh-CHT": "任務類型","en": "Task type","ko": "작업 유형","ja": "タスクタイプ","de": "Aufgabentyp","fr": "Type de tâche","id": "Jenis tugas"},
            {"key": "创建群聊","zh": "","zh-CHT": "創建群聊","en": "Create group chat","ko": "그룹 채팅을 만듭니다","ja": "グループチャットを作成します","de": "Gruppenchat erstellen","fr": "Créer un chat de groupe","id": "Buat obrolan grup"},
            {"key": "导出任务","zh": "","zh-CHT": "導出任務","en": "Export task","ko": "수출 작업","ja": "エクスポートタスク","de": "Exportaufgabe","fr": "Tâche d'exportation","id": "Tugas Ekspor"},
            {"key": "帐户安全","zh": "","zh-CHT": "帳戶安全","en": "Account security","ko": "계정 보안","ja": "アカウントのセキュリティ","de": "Konto Sicherheit","fr": "Sécurité du compte","id": "Keamanan Akun"},
            {"key": "应用支持","zh": "","zh-CHT": "應用支持","en": "Applied support","ko": "응용 지원","ja": "応用サポート","de": "Angewandte Unterstützung","fr": "Assistance appliquée","id": "Dukungan Terapan"},
            {"key": "数据加密","zh": "","zh-CHT": "數據加密","en": "Data encryption","ko": "데이터 암호화","ja": "データ暗号化","de": "Datenverschlüsselung","fr": "cryptage de données","id": "Enkripsi data"},
            {"key": "文件搜索","zh": "","zh-CHT": "文件搜索","en": "File search","ko": "파일 검색","ja": "ファイル検索","de": "Dateisuche","fr": "Recherche de fichier","id": "Pencarian file"},
            {"key": "文件管理","zh": "","zh-CHT": "文件管理","en": "File management","ko": "파일 관리","ja": "ファイル管理","de": "Dokumentenverwaltung","fr": "Gestion de fichiers","id": "Manajemen file"},
            {"key": "服务支持","zh": "","zh-CHT": "服務支持","en": "Service support","ko": "서비스 지원","ja": "サービスサポート","de": "Service-Unterstützung","fr": "service après vente","id": "Dukungan Layanan"},
            {"key": "自助支持","zh": "","zh-CHT": "自助支持","en": "Self -support","ko": "자기 지원","ja": "自己サポート","de": "Selbsthilfe","fr": "Auto-support","id": "Mendukung diri sendiri"},
            {"key": "返回首页","zh": "","zh-CHT": "返回首頁","en": "Return homepage","ko": "홈페이지를 반환하십시오","ja": "ホームページを返します","de": "Homepage zurückkehren","fr": "Page d'accueil de retour","id": "Kembalikan beranda"},
            {"key": "项目搜索","zh": "","zh-CHT": "項目搜索","en": "Project search","ko": "프로젝트 검색","ja": "プロジェクト検索","de": "Projektsuche","fr": "Recherche de projet","id": "Pencarian Proyek"},
            {"key": "项目管理","zh": "","zh-CHT": "項目管理","en": "Project management","ko": "프로젝트 관리","ja": "プロジェクト管理","de": "Projektmanagement","fr": "gestion de projet","id": "Manajemen proyek"},
            {"key": "项目群聊","zh": "","zh-CHT": "項目群聊","en": "Project group chat","ko": "프로젝트 그룹 채팅","ja": "プロジェクトグループチャット","de": "Projektgruppe Chat","fr": "Chat de groupe de projet","id": "Obrolan grup proyek"},
            {"key": "体验DEMO","zh": "","zh-CHT": "體驗DEMO","en": "Experience DEMO","ko": "데모를 경험하십시오","ja": "デモを体験してください","de": "Erleben Sie Demo","fr": "Découvrez la démo","id": "Pengalaman demo"},
            {"key": "公有云","zh": "","zh-CHT": "公有云","en": "Public cloud","ko": "퍼블릭 클라우드","ja": "パブリッククラウド","de": "Öffentliche Wolke","fr": "Nuage public","id": "Cloud publik"},
            {"key": "国际化","zh": "","zh-CHT": "國際化","en": "Globalization","ko": "세계화","ja": "グローバリゼーション","de": "Globalisierung","fr": "mondialisation","id": "globalisasi"},
            {"key": "定制版","zh": "","zh-CHT": "定製版","en": "Custom Edition","ko": "커스텀 에디션","ja": "カスタムエディション","de": "Benutzerdefinierte Ausgabe","fr": "Édition personnalisée","id": "Edisi Kustom"},
            {"key": "无限制","zh": "","zh-CHT": "無限制","en": "Unlimited","ko": "제한 없는","ja": "無制限","de": "Unbegrenzt","fr": "Illimité","id": "Tak terbatas"},
            {"key": "甘特图","zh": "","zh-CHT": "甘特圖","en": "Gantt chart","ko": "간트 차트","ja": "ガントチャート","de": "Gantt-Diagramm","fr": "diagramme de Gantt","id": "Bagan Gant"},
            {"key": "社区版","zh": "","zh-CHT": "社區版","en": "Community","ko": "지역 사회","ja": "コミュニティ","de": "Gemeinschaft","fr": "Communauté","id": "Masyarakat"},
            {"key": "社区版","zh": "","zh-CHT": "社區版","en": "Community","ko": "지역 사회","ja": "コミュニティ","de": "Gemeinschaft","fr": "Communauté","id": "Masyarakat"},
            {"key": "私有云","zh": "","zh-CHT": "私有云","en": "Private Cloud","ko": "프라이빗 클라우드","ja": "プライベートクラウド","de": "Private Wolke","fr": "Nuage privé","id": "Cloud pribadi"},
            {"key": "自定义","zh": "","zh-CHT": "自定義","en": "Customize","ko": "사용자 정의하십시오","ja": "カスタマイズ","de": "anpassen","fr": "Personnaliser","id": "Menyesuaikan"},
            {"key": "Pro版","zh": "","zh-CHT": "Pro版","en": "PRO version","ko": "프로 버전","ja": "プロバージョン","de": "Pro-Version","fr": "Version pro","id": "Versi Pro"},
            {"key": "人数","zh": "","zh-CHT": "人數","en": "Number of people","ko": "사람들의 수","ja": "人々の数","de": "Anzahl der Personen","fr": "Nombre de personnes","id": "Jumlah orang"},
            {"key": "价格","zh": "","zh-CHT": "價格","en": "Price","ko": "가격","ja": "価格","de": "Preis","fr": "prix","id": "harga"},
            {"key": "推荐","zh": "","zh-CHT": "推薦","en": "Recommend","ko": "추천","ja": "お勧め","de": "empfehlen","fr": "recommander","id": "Direkomendasikan"},
            {"key": "日程","zh": "","zh-CHT": "日程","en": "Schedule","ko": "일정","ja": "スケジュール","de": "Zeitplan","fr": "calendrier","id": "jadwal"},
            {"key": "概述","zh": "","zh-CHT": "概述","en": "Overview","ko": "개요","ja": "概要","de": "Überblick","fr": "Aperçu","id": "Ringkasan"},
            {"key": "授权方式", "zh": "", "zh-CHT": "授權方式", "en": "Authorization mode", "ko": "인증 방식", "ja": "授権方法です", "de": "Nicht autorisiert.", "fr": "Modalités de mandat", "id": "Cara memberdayakan"},
            {"key": "普通版", "zh": "", "zh-CHT": "普通版", "en": "Ordinary edition", "ko": "일반판", "ja": "通常版です", "de": "Normale ausgabe.", "fr": "La version normale", "id": "Versi biasa"},
            {"key": "功能较少可能会停更", "zh": "", "zh-CHT": "功能較少可能會停更", "en": "Simple function may stop more", "ko": "기능이 간단해서 더 이상 중단되지 않을 수도 있습니다", "ja": "機能はシンプルですが停止するかもしれません", "de": "Einfacher funktionen sind nicht mehr notwendig", "fr": "Fonctionnalité simple peut s’arrêter plus", "id": "Fungsi sederhana mungkin akan berhenti lebih"},
            {"key": "无须授权", "zh": "", "zh-CHT": "無須授權", "en": "Without authorization", "ko": "인증 필요 없음", "ja": "許可は不要です", "de": "Dafür ist keine genehmigung nötig.", "fr": "Aucune autorisation requise", "id": "Tidak perlu perintah."},
            {"key": "Pro免费版", "zh": "", "zh-CHT": "Pro免費版", "en": "Pro Free Edition", "ko": "Pro 무료버전", "ja": "プロ無料版です", "de": "Pro publikum kostenlos.", "fr": "Version gratuite Pro", "id": "Versi Pro gratis"},
            {"key": "(*)人", "zh": "", "zh-CHT": "(*)人", "en": "(*) people", "ko": "(*)명", "ja": "(*)人です", "de": "Drei leute.", "fr": "((*))", "id": "(*) orang"},
            {"key": "Pro订阅版", "zh": "", "zh-CHT": "Pro訂閱版", "en": "Pro subscription", "ko": "Pro 구독판", "ja": "プロ購読版です", "de": "Pro abonnement", "fr": "Version abonnement Pro", "id": "Edisi Pro"}
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
