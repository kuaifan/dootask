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
                        <div class="plans-table-info-btn"></div>
                    </div>
                    <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}">
                        <div class="plans-table-info-th">{{$L('社区版')}}</div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/free.png"/>
                            <div class="currency"><em>0</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('相比Pro更新周期长些')}}</div>
                        <div class="plans-table-info-desc">{{$L('无限制')}}</div>
                        <div class="plans-table-info-btn">
                            <div class="plans-info-btns">
                                <a href="https://github.com/kuaifan/dootask" class="github" target="_blank"><Icon type="logo-github"/></a>
                            </div>
                        </div>
                    </div>
                    <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}">
                        <div class="plans-table-info-th">{{$L('Pro版')}} <span>{{$L('推荐')}}</span></div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/pro.png"/>
                            <div class="currency"><em>18800</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('拥有最新版本所有功能')}}</div>
                        <div class="plans-table-info-desc">{{$L('无限制')}}</div>
                        <div class="plans-table-info-btn">
                            <Tooltip :content="$L('帐号：admin、密码：123456')" transfer>
                                <a href="https://www.dootask.com" class="btn" target="_blank">{{$L('体验DEMO')}}</a>
                            </Tooltip>
                        </div>
                    </div>
                    <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}">
                        <div class="plans-table-info-th">{{$L('定制版')}}</div>
                        <div class="plans-table-info-price">
                            <ImgView class="plans-version" src="images/pro/custom.png"/>
                            <div class="currency"><em class="custom">{{$L('自定义')}}</em></div>
                        </div>
                        <div class="plans-table-info-desc">{{$L('根据您的需求量身定制')}}</div>
                        <div class="plans-table-info-desc">{{$L('无限制')}}</div>
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
                    <span><Icon type="ios-arrow-down" /></span>
                </div>
                <div v-if="body1" class="plans-accordion-body">
                    <div class="plans-table-bd plans-table-app">
                        <div class="plans-table-item first">
                            <div class="plans-table-td">{{$L('项目管理')}}</div>
                            <div class="plans-table-td">{{$L('文件管理')}}</div>
                            <div class="plans-table-td">{{$L('团队管理')}}</div>
                            <div class="plans-table-td">{{$L('即时聊天')}}</div>
                            <div class="plans-table-td">{{$L('子任务')}}</div>
                            <div class="plans-table-td">{{$L('国际化')}}</div>
                            <div class="plans-table-td">{{$L('甘特图')}}</div>
                            <div class="plans-table-td">{{$L('任务动态')}}</div>
                            <div class="plans-table-td">{{$L('导出任务')}}</div>
                            <div class="plans-table-td">{{$L('日程')}}</div>
                            <div class="plans-table-td">{{$L('周报/日报')}}</div>
                            <div class="plans-table-td">{{$L('创建群聊')}}</div>
                            <div class="plans-table-td">{{$L('项目群聊')}}</div>
                            <div class="plans-table-td">{{$L('项目搜索')}}</div>
                            <div class="plans-table-td">{{$L('任务类型')}}</div>
                            <div class="plans-table-td">{{$L('文件搜索')}}</div>
                            <div class="plans-table-td">{{$L('Mac/PC客户端')}}</div>
                            <div class="plans-table-td">{{$L('iOS/Android客户端')}}</div>
                        </div>
                        <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}">
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                        </div>
                        <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}">
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                        </div>
                        <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}">
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                        </div>
                    </div>
                </div>
                <div class="plans-accordion-head" :class="{'plans-accordion-close':!body2}" @click="body2=!body2">
                    <div class="first"><span>{{$L('服务支持')}}</span></div>
                    <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}"></div>
                    <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}"></div>
                    <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}"></div>
                    <span><Icon type="ios-arrow-down" /></span>
                </div>
                <div v-if="body2" class="plans-accordion-body">
                    <div class="plans-table-bd plans-table-app plans-table-service">
                        <div class="plans-table-item first">
                            <div class="plans-table-td">{{$L('自助支持')}} <span>{{$L('（Issues/社群）')}}</span></div>
                            <div class="plans-table-td">{{$L('支持私有化部署')}}</div>
                            <div class="plans-table-td">{{$L('绑定自有域名')}}</div>
                            <div class="plans-table-td">{{$L('二次开发')}}</div>
                            <div class="plans-table-td">{{$L('二次开发咨询服务')}}</div>
                            <div class="plans-table-td">{{$L('允许隐藏或定制产品名')}}</div>
                            <div class="plans-table-td">{{$L('在线咨询支持')}}</div>
                            <div class="plans-table-td">{{$L('电话咨询支持')}}</div>
                            <div class="plans-table-td">{{$L('中英文邮件支持')}}</div>
                            <div class="plans-table-td">{{$L('一对一客户顾问')}}</div>
                            <div class="plans-table-td">{{$L('产品培训')}}</div>
                            <div class="plans-table-td">{{$L('上门支持')}}</div>
                            <div class="plans-table-td">{{$L('专属客户成功经理')}}</div>
                            <div class="plans-table-td">{{$L('免费提供一次内训')}}</div>
                            <div class="plans-table-td">{{$L('明星客户案例')}}</div>
                            <div class="plans-table-info-btn"></div>
                        </div>
                        <div @mouseenter="active=1" class="plans-table-item" :class="{active:active==1}">
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-info-btn">
                                <div class="plans-info-btns">
                                    <a href="https://github.com/kuaifan/dootask" class="github" target="_blank"><Icon type="logo-github"/></a>
                                </div>
                            </div>
                        </div>
                        <div @mouseenter="active=2" class="plans-table-item" :class="{active:active==2}">
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-td"><span> - </span></div>
                            <div class="plans-table-info-btn">
                                <Tooltip :content="$L('帐号：admin、密码：123456')" transfer>
                                    <a href="https://www.dootask.com" class="btn" target="_blank">{{$L('体验DEMO')}}</a>
                                </Tooltip>
                            </div>
                        </div>
                        <div @mouseenter="active=3" class="plans-table-item" :class="{active:active==3}">
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
                            <div class="plans-table-td"><Icon type="md-checkmark" /></div>
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
                        width: 27.7%;
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
                    background-color: #eef2f8;
                    font-size: 16px;
                    color: #485778;
                    line-height: 70px;
                    text-align: center;
                    font-weight: 600;
                    display: flex;
                    justify-content: center;
                    align-items: center;
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
                    padding: 14px 54px;
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
                    width: 27.7%;
                    flex: 1;
                    &.first {
                        width: 27.7%;
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

            active: 2,

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

            uplogShow: false
        }
    },

    created() {
        addLanguage([
            {"CN":"多种支持服务让企业无后顾之忧，7x24 线上支持、在线工单、中英文邮件支持、上门支持","EN":"A variety of support services give the enterprise without worries.","TC":"多種支持服務讓企業無後顧之憂，7x24 線上支持、在線工單、中英文郵件支持、上門支持","KO":"다양한 지원 서비스는 근로자가없는 기업에 제공합니다.","JA":"さまざまなサポートサービスは、心配することなく企業を提供します。"},
            {"CN":"无需本地环境准备，按需购买帐户，专业团队提供运维保障服务，两周一次的版本迭代","EN":"No need to prepare a local environment, buy accounts on demand, professional team provides operation and maintenance security services, and iterations for two weeks once a week","TC":"無需本地環境準備，按需購買帳戶，專業團隊提供運維保障服務，兩週一次的版本迭代","KO":"현지 환경을 준비하고, 주문시 계정을 구매, 프로 프랙틱 팀 제공 및 유지 보수 보안 서비스 및 2 주 동안의 설계가 onces onces onces의 설계가 필요하지 않습니다.","JA":"ローカル環境を準備したり、アカウントをオンデマンドで購入したり、プロのチームが運用とメンテナンスのセキュリティサービスを提供したり、週に1回の反復を提供する必要はありません。"},
            {"CN":"资深客户成功顾问对企业进行调研、沟通需求、制定个性化的解决方案，帮助企业落地","EN":"Senior customer successful consultants investigate, communicate needs, and formulate personalized solutions to help enterprises land","TC":"資深客戶成功顧問對企業進行調研、溝通需求、制定個性化的解決方案，幫助企業落地","KO":"선임 고객 성공적인 컨설턴트는 기업 토지를 돕기 위해 조사, 요구를 조사하고 개인화 된 솔루션을 공식화합니다.","JA":"シニア顧客の成功コンサルタントは、企業の土地を支援するためにパーソナライズされたソリューションを調査、コミュニケーション、および策定します"},
            {"CN":"企业隔离的云服务器环境，高可用性，网络及应用层完整隔离，数据高度自主可控","EN":"Enterprise isolation cloud server environment, high availability, network and application layer complete isolation, data high and controllable","TC":"企業隔離的雲服務器環境，高可用性，網絡及應用層完整隔離，數據高度自主可控","KO":"엔터프라이즈 격리 클라우드 서버 환경, 고 가용성, 네트워크 및 애플리케이션 계층 완전한 격리, 데이터 높음 및 제어 화","JA":"エンタープライズ分離クラウドサーバー環境、高可用性、ネットワークおよびアプリケーションレイヤーの完全な分離、データが高く、制御可能なデータ"},
            {"CN":"根据需求定制培训内容，为不同角色给出专属培训方案，线上线下培训渠道全覆盖","EN":"Customize the training content according to the needs, give the exclusive training scheme for different roles, and the online and offline training channels are fully covered","TC":"根據需求定制培訓內容，為不同角色給出專屬培訓方案，線上線下培訓渠道全覆蓋","KO":"The The The The The Needs, 다른 역할에 대한 독점 교육 계획을 제공하며 온라인 및 사무실 교육 채널이 완전히 커버됩니다.","JA":"ニーズに応じてトレーニングコンテンツをカスタマイズし、さまざまな役割の排他的なトレーニングスキームを提供すると、オンラインおよびオフラインのトレーニングチャネルが完全にカバーされています"},
            {"CN":"DooTask 是新一代团队协作平台，您可以根据您团队的需求，选择合适的产品功能。","EN":"Dootask is a new generation of team collaboration platform. You can choose the appropriate product function according to the needs of your team.","TC":"DooTask 是新一代團隊協作平台，您可以根據您團隊的需求，選擇合適的產品功能。","KO":"Dootask는 새로운 세대의 팀 협업 플랫폼입니다. 팀의 필요한 것들에 대한 적절한 양육 재미를 선택할 수 있습니다.","JA":"Dootaskは、新世代のチームコラボレーションプラットフォームです。チームのニーズに応じて適切な製品機能を選択できます。"},
            {"CN":"基于 Docker 的容器化部署，支持高可用集群，快速弹性扩展，数据高度自主可控","EN":"Based on Docker's containerization deployment, support high available clusters, rapid elastic expansion, data highly controlled and controllable","TC":"基於 Docker 的容器化部署，支持高可用集群，快速彈性擴展，數據高度自主可控","KO":"Docker의 컨테이너화 배치를 기반으로, 높은 가용 클러스터, 빠른 탄성 확장, 데이터가 고도로 제어되고 제어되는 지원","JA":"Dockerのコンテナ化の展開に基づいて、利用可能な高いクラスター、急速な弾性拡張、高度に制御され、制御可能なデータをサポートします"},
            {"CN":"多重方式保证数据不丢失，高可用故障转移，异地容灾备份，99.99%可用性保证","EN":"Multiple ways to ensure that the data is not lost, high availability can be transferred, disasters are backup, 99.99%availability guarantee guarantee","TC":"多重方式保證數據不丟失，高可用故障轉移，異地容災備份，99.99%可用性保證","KO":"데이터가 길지 않도록하는 여러 가지 방법, 고 가용성을 전송할 수 있고, 재해는 백업되며, 99.99%가용성 보증인 보증인","JA":"データが失われないようにする複数の方法、高可用性を転送し、災害はバックアップ、99.99％の可用性保証保証"},
            {"CN":"多重方式保证帐户安全，远程会话控制，设备绑定，安全日志以及手势密码","EN":"Multiple ways to ensure account security, remote session control, device binding, security logs and gesture passwords","TC":"多重方式保證帳戶安全，遠程會話控制，設備綁定，安全日誌以及手勢密碼","KO":"계정 보안, 원격 세션 제어, 장치 바인딩, 보안 로그 및 제스처 암호를 보장하는 여러 가지 방법","JA":"アカウントセキュリティ、リモートセッション制御、デバイスバインディング、セキュリティログ、ジェスチャーパスワードを確保する複数の方法"},
            {"CN":"多重方式保证数据不泄漏，基于 TLS 的数据加密传输，DDOS 防御和入侵检测","EN":"Multi -way guarantees the data is not leaked. TLS -based data encryption transmission, DDOS defense and intrusion detection","TC":"多重方式保證數據不洩漏，基於 TLS 的數據加密傳輸，DDOS 防禦和入侵檢測","KO":"다중 웨이 Guarane 데이터가 유출되지 않습니다. TLS 기반 데이터 암호화 전송, DDOS 방어 및 침입 탐지","JA":"マルチウェイは、データが漏れないことを保証します。TLSベースのデータ暗号化伝送、DDOS防御、侵入検出"},
            {"CN":"从现在开始，DooTask 为世界各地的团队提供支持，探索适合您的选项。","EN":"From now on, Dootask has provided support for teams around the world and explores the options suitable for you.","TC":"從現在開始，DooTask 為世界各地的團隊提供支持，探索適合您的選項。","KO":"이제부터 Dootask는 전 세계 팀을 위해 지원을 제공했으며 귀하에게 적합한 옵션을 탐색합니다.","JA":"これから、Dootaskは世界中のチームにサポートを提供し、あなたに適したオプションを探ります。"},
            {"CN":"如有任何问题，欢迎使用邮箱与我们联系。","EN":"If you have any questions, please use your mailbox to contact us.","TC":"如有任何問題，歡迎使用郵箱與我們聯繫。","KO":"궁금한 점이 있으면 사서함을 사용하여 문의하십시오.","JA":"ご質問がある場合は、メールボックスを使用してお問い合わせください。"},
            {"CN":"帐号：admin、密码：123456","EN":"Account number: admin, password: 123456","TC":"帳號：admin、密碼：123456","KO":"계정 번호 : 관리자, 비밀번호 : 123456","JA":"アカウント番号：管理者、パスワード：123456"},
            {"CN":"允许隐藏或定制产品名","EN":"Allow hidden or customized product name","TC":"允許隱藏或定制產品名","KO":"숨겨져 있거나 맞춤형 제품 이름을 허용하십시오","JA":"非表示またはカスタマイズされた製品名を許可します"},
            {"CN":"多种部署方式随心选择","EN":"Choose a variety of deployment methods at will","TC":"多種部署方式隨心選擇","KO":"마음대로 다양한 배포 방법을 선택하십시오","JA":"さまざまな展開方法を自由に選択してください"},
            {"CN":"多重安全策略保护数据","EN":"Multiple security strategy protection data","TC":"多重安全策略保護數據","KO":"다중 보안 전략 보호 데이터","JA":"複数のセキュリティ戦略保護データ"},
            {"CN":"拥有最新版本所有功能","EN":"Have all functions of the latest version","TC":"擁有最新版本所有功能","KO":"최신 버전의 모든 기능이 있습니다","JA":"最新バージョンのすべての機能を持っています"},
            {"CN":"根据您的需求量身定制","EN":"Customized according to your needs","TC":"根據您的需求量身定制","KO":"사용자 정의하십시오","JA":"ニーズに応じてカスタマイズされています"},
            {"CN":"完善的服务支持体系","EN":"Perfect service support system","TC":"完善的服務支持體系","KO":"완벽한 서비스 지원 시스템","JA":"完璧なサービスサポートシステム"},
            {"CN":"专属客户成功经理","EN":"Customer success manager","TC":"專屬客戶成功經理","KO":"고객 성공 관리자","JA":"カスタマーサクセスマネージャー"},
            {"CN":"二次开发咨询服务","EN":"Secondary development consulting service","TC":"二次開發諮詢服務","KO":"2 차 개발 컨설팅 서비스","JA":"二次開発コンサルティングサービス"},
            {"CN":"免费提供一次内训","EN":"Provide internal training for free","TC":"免費提供一次內訓","KO":"인터넷을 무료로 제공하십시오","JA":"無料で内部トレーニングを提供します"},
            {"CN":"选择适合您的版本","EN":"Choose the version that suits you","TC":"選擇適合您的版本","KO":"자신에게 맞는 버전을 선택하십시오","JA":"あなたに合ったバージョンを選択してください"},
            {"CN":"1:1客户成功顾问","EN":"1: 1 Customer successful consultant","TC":"1:1客戶成功顧問","KO":"1 : 1 고객 성공적인 컨설턴트","JA":"1：1の顧客成功コンサルタント"},
            {"CN":"一对一客户顾问","EN":"One -to -one customer consultant","TC":"一對一客戶顧問","KO":"하나의 고객 컨설턴트","JA":"1人から1人の顧客コンサルタント"},
            {"CN":"中英文邮件支持","EN":"Chinese and English email support","TC":"中英文郵件支持","KO":"중국어 및 영어 이메일 지원","JA":"中国語と英語の電子メールサポート"},
            {"CN":"全面的支持服务","EN":"Comprehensive support service","TC":"全面的支持服務","KO":"포괄적 인 지원 서비스","JA":"包括的なサポートサービス"},
            {"CN":"完善的培训体系","EN":"Comprehensive training system","TC":"完善的培訓體系","KO":"포괄적 인 Trining Systemm","JA":"包括的なトレーニングシステム"},
            {"CN":"支持私有化部署","EN":"Support privatization deployment","TC":"支持私有化部署","KO":"개인 배포를 지원합니다","JA":"民営化の展開をサポートします"},
            {"CN":"iOS/Android客户端","EN":"IOS/Android client","TC":"iOS/Android客戶端","KO":"iOS/Android 클라이언트","JA":"iOS/Androidクライアント"},
            {"CN":"（Issues/社群）","EN":"(ISSUES/Community)","TC":"（Issues/社群）","KO":"(문제/커뮤니티)","JA":"（問題/コミュニティ）"},
            {"CN":"在线咨询支持","EN":"Online consultation support","TC":"在線諮詢支持","KO":"온라인 상담 지원","JA":"オンライン相談サポート"},
            {"CN":"明星客户案例","EN":"Star customer case","TC":"明星客戶案例","KO":"스타 고객 사례","JA":"スターの顧客ケース"},
            {"CN":"电话咨询支持","EN":"Telephone consultation support","TC":"電話諮詢支持","KO":"전화 상담 지원","JA":"電話相談サポート"},
            {"CN":"绑定自有域名","EN":"Bind your own domain name","TC":"綁定自有域名","KO":"자신의 도메인 이름을 바인딩하십시오","JA":"独自のドメイン名をバインドします"},
            {"CN":"选择适合你的","EN":"Choose suitable for you","TC":"選擇適合你的","KO":"당신에게 적합한 선택을 선택하십시오","JA":"あなたに適した選択を選択してください"},
            {"CN":"高可用性保证","EN":"High availability guarantee","TC":"高可用性保證","KO":"고 가용성 보증","JA":"高可用性保証"},
            {"CN":"邮箱地址：*","EN":"Email address:*","TC":"郵箱地址：*","KO":"이메일 주소:*","JA":"電子メールアドレス：*"},
            {"CN":"Mac/PC客户端","EN":"Mac/PC client","TC":"Mac/PC客戶端","KO":"Mac/PC 클라이언트","JA":"Mac/PCクライアント"},
            {"CN":"本地服务器","EN":"Local server","TC":"本地服務器","KO":"로컬 서버","JA":"ローカルサーバー"},
            {"CN":"周报/日报","EN":"Weekly/Daily","TC":"週報/日報","KO":"주간/매일","JA":"毎週/毎日"},
            {"CN":"上门支持","EN":"Support","TC":"上門支持","KO":"지원하다","JA":"サポート"},
            {"CN":"二次开发","EN":"Secondary development","TC":"二次開發","KO":"이차 개발","JA":"二次発達"},
            {"CN":"产品培训","EN":"Product training","TC":"產品培訓","KO":"제품 교육","JA":"製品トレーニング"},
            {"CN":"任务动态","EN":"Mission dynamic","TC":"任務動態","KO":"미션 다이나믹","JA":"ミッションダイナミック"},
            {"CN":"任务类型","EN":"Task type","TC":"任務類型","KO":"작업 유형","JA":"タスクタイプ"},
            {"CN":"创建群聊","EN":"Create group chat","TC":"創建群聊","KO":"그룹 채팅을 만듭니다","JA":"グループチャットを作成します"},
            {"CN":"导出任务","EN":"Export task","TC":"導出任務","KO":"수출 작업","JA":"エクスポートタスク"},
            {"CN":"帐户安全","EN":"Account security","TC":"帳戶安全","KO":"계정 보안","JA":"アカウントのセキュリティ"},
            {"CN":"应用支持","EN":"Applied support","TC":"應用支持","KO":"응용 지원","JA":"応用サポート"},
            {"CN":"数据加密","EN":"Data encryption","TC":"數據加密","KO":"데이터 암호화","JA":"データ暗号化"},
            {"CN":"文件搜索","EN":"File search","TC":"文件搜索","KO":"파일 검색","JA":"ファイル検索"},
            {"CN":"文件管理","EN":"File management","TC":"文件管理","KO":"파일 관리","JA":"ファイル管理"},
            {"CN":"服务支持","EN":"Service support","TC":"服務支持","KO":"서비스 지원","JA":"サービスサポート"},
            {"CN":"自助支持","EN":"Self -support","TC":"自助支持","KO":"자기 지원","JA":"自己サポート"},
            {"CN":"返回首页","EN":"Return homepage","TC":"返回首頁","KO":"홈페이지를 반환하십시오","JA":"ホームページを返します"},
            {"CN":"项目搜索","EN":"Project search","TC":"項目搜索","KO":"프로젝트 검색","JA":"プロジェクト検索"},
            {"CN":"项目管理","EN":"Project management","TC":"項目管理","KO":"프로젝트 관리","JA":"プロジェクト管理"},
            {"CN":"项目群聊","EN":"Project group chat","TC":"項目群聊","KO":"프로젝트 그룹 채팅","JA":"プロジェクトグループチャット"},
            {"CN":"体验DEMO","EN":"Experience DEMO","TC":"體驗DEMO","KO":"데모를 경험하십시오","JA":"デモを体験してください"},
            {"CN":"公有云","EN":"Public cloud","TC":"公有云","KO":"퍼블릭 클라우드","JA":"パブリッククラウド"},
            {"CN":"国际化","EN":"Globalization","TC":"國際化","KO":"세계화","JA":"グローバリゼーション"},
            {"CN":"定制版","EN":"Custom Edition","TC":"定製版","KO":"커스텀 에디션","JA":"カスタムエディション"},
            {"CN":"无限制","EN":"Unlimited","TC":"無限制","KO":"제한 없는","JA":"無制限"},
            {"CN":"甘特图","EN":"Gantt chart","TC":"甘特圖","KO":"간트 차트","JA":"ガントチャート"},
            {"CN":"社区版","EN":"Community","TC":"社區版","KO":"지역 사회","JA":"コミュニティ"},
            {"CN":"社区版","EN":"Compared to the PRO update cycle, longer","TC":"社區版","KO":"Pro 업데이트주기와 비교하여 더 길다","JA":"コミュニティ"},
            {"CN":"私有云","EN":"Private Cloud","TC":"私有云","KO":"프라이빗 클라우드","JA":"プライベートクラウド"},
            {"CN":"自定义","EN":"Customize","TC":"自定義","KO":"사용자 정의하십시오","JA":"カスタマイズ"},
            {"CN":"Pro版","EN":"PRO version","TC":"Pro版","KO":"프로 버전","JA":"プロバージョン"},
            {"CN":"人数","EN":"Number of people","TC":"人數","KO":"사람들의 수","JA":"人々の数"},
            {"CN":"价格","EN":"Price","TC":"價格","KO":"가격","JA":"価格"},
            {"CN":"推荐","EN":"Recommend","TC":"推薦","KO":"추천","JA":"お勧め"},
            {"CN":"日程","EN":"Schedule","TC":"日程","KO":"일정","JA":"スケジュール"},
            {"CN":"概述","EN":"Overview","TC":"概述","KO":"개요","JA":"概要"}
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
