<template>
    <div class="project-list">
        <div class="project-head">
            <div class="project-titbox">
                <div class="project-title">✔️ Daily Task</div>
                <div class="project-subtitle">Click + New To create new list and wait for project manager card Don't Create a card by yourself to manage a good colaboration.</div>
            </div>
            <div class="project-icobox">
                <ul class="project-icons">
                    <li class="project-icon">
                        <Icon type="ios-search-outline" />
                    </li>
                    <li class="project-icon" @click="$store.commit('toggleProjectChatShow');">
                        <Icon type="ios-chatbubbles-outline" />
                        <Badge :count="999"></Badge>
                    </li>
                    <li class="project-avatar online">
                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                    </li>
                </ul>
                <div class="project-switch">
                    <div class="project-switch-button">
                        <div class="project-switch-img active">
                            <img src="../../../../statics/images/project-panel-blue.svg">
                        </div>
                        <div class="project-switch-img">
                            <img src="../../../../statics/images/project-menu-gray.svg">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="project-column">
            <ul>
                <li>
                    <div class="column-head">
                        <div class="column-head-title">Next Up</div>
                        <div class="column-head-num">3</div>
                    </div>
                    <ul>
                        <li>
                            <div class="task-head">
                                <div class="task-title">Maxxis Tyres</div>
                                <Icon type="ios-more" />
                            </div>
                            <div class="task-desc">These project will need a new brand Identity where they will get recognise.</div>
                            <div class="task-tags">
                                <Tag color="red">red</Tag>
                                <Tag color="volcano">volcano</Tag>
                            </div>
                            <div class="task-member">
                                <ul>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li>
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                </ul>
                                <div class="task-icon">5<Icon type="ios-link-outline" /></div>
                                <div class="task-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="25" :stroke-width="6" />
                                <div class="task-time today"><Icon type="ios-time-outline" />Mar 30</div>
                            </div>
                        </li>
                        <li>
                            <div class="task-head">
                                <div class="task-title">Maxxis Tyres</div>
                                <Icon type="ios-more" />
                            </div>
                            <div class="task-desc">These project will need a new brand Identity where they will get recognise.</div>
                            <div class="task-member">
                                <ul>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li>
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                </ul>
                                <div class="task-icon">5<Icon type="ios-link-outline" /></div>
                                <div class="task-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="25" :stroke-width="6" />
                                <div class="task-time"><Icon type="ios-time-outline" />Mar 30</div>
                            </div>
                        </li>
                    </ul>
                    <div class="column-add">
                        <Icon type="md-add" />
                    </div>
                </li>
                <li>
                    <div class="column-head">
                        <div class="column-head-title">Hi Progress</div>
                        <div class="column-head-num">3</div>
                    </div>
                    <ul>
                        <li>
                            <div class="task-head">
                                <div class="task-title">Maxxis Tyres</div>
                                <Icon type="ios-more" />
                            </div>
                            <div class="task-desc">These project will need a new brand Identity where they will get recognise.</div>
                            <div class="task-tags">
                                <Tag color="magenta">magenta</Tag>
                                <Tag color="red">red</Tag>
                                <Tag color="volcano">volcano</Tag>
                            </div>
                            <div class="task-member">
                                <ul>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li>
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                </ul>
                                <div class="task-icon">5<Icon type="ios-link-outline" /></div>
                                <div class="task-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="25" :stroke-width="6" />
                                <div class="task-time"><Icon type="ios-time-outline" />Mar 30</div>
                            </div>
                        </li>
                        <li>
                            <div class="task-head">
                                <div class="task-title">Maxxis Tyres</div>
                                <Icon type="ios-more" />
                            </div>
                            <div class="task-desc">These project will need a new brand Identity where they will get recognise.</div>
                            <div class="task-tags">
                                <Tag color="magenta">magenta</Tag>
                                <Tag color="red">red</Tag>
                                <Tag color="volcano">volcano</Tag>
                            </div>
                            <div class="task-member">
                                <ul>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li>
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                </ul>
                                <div class="task-icon">5<Icon type="ios-link-outline" /></div>
                                <div class="task-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="25" :stroke-width="6" />
                                <div class="task-time tomorrow"><Icon type="ios-time-outline" />Mar 30</div>
                            </div>
                        </li>
                    </ul>
                    <div class="column-add">
                        <Icon type="md-add" />
                    </div>
                </li>
                <li>
                    <div class="column-head">
                        <div class="column-head-title">Complete</div>
                        <div class="column-head-num">3</div>
                    </div>
                    <ul>
                        <li>
                            <div class="task-head">
                                <div class="task-title">Maxxis Tyres</div>
                                <Icon type="ios-more" />
                            </div>
                            <div class="task-desc">These project will need a new brand Identity where they will get recognise.</div>
                            <div class="task-member">
                                <ul>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li>
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                </ul>
                                <div class="task-icon">5<Icon type="ios-link-outline" /></div>
                                <div class="task-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="25" :stroke-width="6" />
                                <div class="task-time"><Icon type="ios-time-outline" />Mar 30</div>
                            </div>
                        </li>
                        <li>
                            <div class="task-head">
                                <div class="task-title">Maxxis Tyres</div>
                                <Icon type="ios-more" />
                            </div>
                            <div class="task-desc">These project will need a new brand Identity where they will get recognise.</div>
                            <div class="task-tags">
                                <Tag color="primary">primary</Tag>
                                <Tag color="success">success</Tag>
                                <Tag color="error">error</Tag>
                                <Tag color="warning">warning</Tag>
                                <Tag color="magenta">magenta</Tag>
                                <Tag color="red">red</Tag>
                                <Tag color="volcano">volcano</Tag>
                            </div>
                            <div class="task-member">
                                <ul>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li class="online">
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                    <li>
                                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                                    </li>
                                </ul>
                                <div class="task-icon">5<Icon type="ios-link-outline" /></div>
                                <div class="task-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="25" :stroke-width="6" />
                                <div class="task-time"><Icon type="ios-time-outline" />Mar 30</div>
                            </div>
                        </li>
                    </ul>
                    <div class="column-add">
                        <Icon type="md-add" />
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project-list {
        display: flex;
        flex-direction: column;
        .project-head {
            display: flex;
            align-items: flex-start;
            margin: 32px 32px 16px;
            .project-titbox {
                flex: 1;
                margin-bottom: 16px;
                .project-title {
                    color: #333333;
                    font-size: 28px;
                    font-weight: 600;
                }
                .project-subtitle {
                    color: #999999;
                    margin-top: 18px;
                    line-height: 24px;
                }
            }
            .project-icobox {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
                margin-top: 2px;
                margin-left: 80px;
                .project-icons {
                    display: flex;
                    align-items: center;
                    flex-shrink: 0;
                    > li {
                        list-style: none;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 36px;
                        height: 36px;
                        position: relative;
                        margin-left: 16px;
                        &.project-icon {
                            border-radius: 50%;
                            background-color: #F2F3F5;
                            .ivu-icon {
                                font-size: 20px;
                            }
                            .ivu-badge {
                                position: absolute;
                                top: -6px;
                                left: 20px;
                                transform: scale(0.8);
                            }
                        }
                        &.project-avatar {
                            .ivu-avatar {
                                width: 36px;
                                height: 36px;
                            }
                            &:before {
                                content: "";
                                position: absolute;
                                right: 0;
                                bottom: 0;
                                width: 9px;
                                height: 9px;
                                border-radius: 50%;
                                background-color: #ff0000;
                                border: 1px solid #ffffff;
                                z-index: 1;
                            }
                            &.online {
                                &:before {
                                    background-color: #509E76;
                                }
                            }
                        }
                    }
                }
                .project-switch {
                    display: flex;
                    justify-content: flex-end;
                    .project-switch-button {
                        display: flex;
                        align-items: center;
                        background-color: #ffffff;
                        border-radius: 6px;
                        .project-switch-img {
                            width: 32px;
                            height: 30px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            border-radius: 6px;
                            > img {
                                width: 16px;
                                height: 16px;
                            }
                            &.active {
                                border: 1px solid #2d8cf0;
                                background-color: #e6f7ff;
                            }
                        }
                    }
                }
            }
        }
        .project-column {
            display: flex;
            height: 100%;
            overflow-x: auto;
            > ul {
                display: inline-flex;
                justify-content: space-between;
                align-items: center;
                > li {
                    flex-shrink: 0;
                    list-style: none;
                    width: 260px;
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                    &:first-child {
                        margin-left: 22px;
                    }
                    &:last-child {
                        margin-right: 22px;
                    }
                    .column-head {
                        display: flex;
                        align-items: center;
                        padding: 6px 10px;
                        margin: 0 10px;
                        background-color: #F2F3F5;
                        border-radius: 4px;
                        .column-head-title {
                            flex: 1;
                            font-size: 16px;
                            font-weight: 600;
                        }
                        .column-head-num {
                            font-size: 12px;
                            padding: 2px 7px;
                            line-height: 16px;
                            border-radius: 3px;
                            color: #ffffff;
                            background-color: #1C1D1E;
                        }
                    }
                    > ul {
                        flex: 1;
                        height: 0;
                        overflow: auto;
                        > li {
                            list-style: none;
                            margin: 0 10px 16px;
                            background-color: #ffffff;
                            border-radius: 12px;
                            padding: 12px;
                            transition: box-shadow 0.3s;
                            &:hover {
                                box-shadow: 0 0 10px #e6ecfa;
                            }
                            &:first-child {
                                margin-top: 16px;
                            }
                            .task-head {
                                display: flex;
                                align-items: center;
                                .task-title {
                                    flex: 1;
                                    font-weight: 600;
                                }
                                .ivu-icon {
                                    font-size: 22px;
                                    color: #666666;
                                }
                            }
                            .task-desc {
                                color: #999999;
                                margin-top: 10px;
                                line-height: 18px;
                            }
                            .task-tags {
                                margin-top: 10px;
                                .ivu-tag {

                                }
                            }
                            .task-member {
                                margin-top: 10px;
                                display: flex;
                                align-items: center;
                                > ul {
                                    flex: 1;
                                    width: 0;
                                    display: flex;
                                    align-items: center;
                                    margin-right: 12px;
                                    > li {
                                        list-style: none;
                                        margin-left: -5px;
                                        &:first-child {
                                            margin-left: 0;
                                        }
                                        .ivu-avatar {
                                            width: 28px;
                                            height: 28px;
                                            border: 2px solid #ffffff;
                                        }
                                    }
                                }
                                .task-icon {
                                    margin-left: 6px;
                                    font-size: 12px;
                                    .ivu-icon {
                                        margin-left: 1px;
                                        font-size: 14px;
                                    }
                                }
                            }
                            .task-progress {
                                margin-top: 10px;
                                display: flex;
                                align-items: center;
                                .task-time {
                                    flex-shrink: 0;
                                    background-color: #EAEDF2;
                                    padding: 1px 4px;
                                    font-size: 12px;
                                    font-weight: 500;
                                    border-radius: 3px;
                                    display: flex;
                                    align-items: center;
                                    &.today {
                                        color: #ffffff;
                                        background-color: #ed4014;
                                    }
                                    &.tomorrow {
                                        color: #ffffff;
                                        background-color: #ff9900;
                                    }
                                    .ivu-icon {
                                        margin-right: 2px;
                                        font-size: 14px;
                                    }
                                }
                            }
                        }
                    }
                    .column-add {
                        border-radius: 6px;
                        border: 2px dashed #F1f1f1;
                        padding: 5px 12px;
                        text-align: center;
                        margin: 0 10px 18px;
                        .ivu-icon {
                            color: #cccccc;
                            font-size: 22px;
                        }
                    }
                }
            }
        }
    }
}
</style>

<script>
export default {
    name: "ProjectList",
    data() {
        return {

        }
    },
    mounted() {

    },
}
</script>
