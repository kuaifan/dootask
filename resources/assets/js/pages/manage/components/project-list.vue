<template>
    <div class="project-list">
        <div class="project-head">
            <div class="project-titbox">
                <div class="project-title">✔️ Daily Task</div>
                <div class="project-subtitle">Click + New To create new list and wait for project manager card Don't Create a card by yourself to manage a good colaboration.</div>
            </div>
            <div class="project-icobox">
                <ul class="project-icons">
                    <li class="project-avatar online">
                        <Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" />
                    </li>
                    <li class="project-icon" @click="addShow=true">
                        <Icon type="md-add" />
                    </li>
                    <li class="project-icon">
                        <Tooltip theme="light" :always="searchText!=''" transfer>
                            <Icon type="ios-search" />
                            <div slot="content">
                                <Input v-model="searchText" placeholder="Search task..." clearable autofocus/>
                            </div>
                        </Tooltip>
                    </li>
                    <li class="project-icon" :class="{'active':$store.state.projectChatShow}" @click="$store.commit('toggleProjectChatShow')">
                        <Icon type="ios-chatbubbles" />
                        <Badge :count="999"></Badge>
                    </li>
                    <li class="project-icon">
                        <Icon type="ios-more" />
                    </li>
                </ul>
                <div class="project-switch">
                    <div class="project-switch-button" @click="$store.commit('toggleProjectListPanel')">
                        <template v-if="$store.state.projectListPanel">
                            <div class="project-switch-img active"><img src="../../../../statics/images/project-panel-blue.svg"></div>
                            <div class="project-switch-img"><img src="../../../../statics/images/project-menu-gray.svg"></div>
                        </template>
                        <template v-else>
                            <div class="project-switch-img"><img src="../../../../statics/images/project-panel-gray.svg"></div>
                            <div class="project-switch-img active"><img src="../../../../statics/images/project-menu-blue.svg"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="$store.state.projectListPanel" class="project-column">
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
        <div v-else class="project-table">
            <div class="project-table-head">
                <Row class="project-row">
                    <Col span="12"># Task name</Col>
                    <Col span="3">Task Column</Col>
                    <Col span="3">Priority</Col>
                    <Col span="3">Member</Col>
                    <Col span="3">Expiration</Col>
                </Row>
            </div>
            <div class="project-table-body">
                <Row class="project-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont">&#xe689;</i>
                        <div class="row-h1">My task</div>
                        <div class="row-num">(5)</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Unimportance</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                        <div class="item-icon">6<i class="iconfont">&#xe648;</i></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
            </div>
            <div class="project-table-body">
                <Row class="project-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont">&#xe689;</i>
                        <div class="row-h1">Undone</div>
                        <div class="row-num">(5)</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Unimportance</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Unimportance</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon type="md-radio-button-off" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
            </div>
            <div class="project-table-body">
                <Row class="project-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont">&#xe689;</i>
                        <div class="row-h1">Completed</div>
                        <div class="row-num">(5)</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon class="completed" type="md-checkmark-circle" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Hi Progress</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon class="completed" type="md-checkmark-circle" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Hi Progress</Col>
                    <Col span="3"><TaskPriority>Unimportance</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon class="completed" type="md-checkmark-circle" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Hi Progress</Col>
                    <Col span="3"><TaskPriority>Important</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
                <Row class="project-row">
                    <Col span="12" class="row-item">
                        <Icon class="completed" type="md-checkmark-circle" />
                        <div class="item-title">Maxxis Tyres</div>
                        <div class="item-icon">5<Icon type="ios-link-outline" /></div>
                        <div class="item-icon">5<Icon type="ios-chatbubbles-outline" /></div>
                    </Col>
                    <Col span="3">Next Up</Col>
                    <Col span="3"><TaskPriority>Unimportance</TaskPriority></Col>
                    <Col span="3" class="row-member">
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
                    </Col>
                    <Col span="3">6 June, 2021</Col>
                </Row>
            </div>
        </div>

        <!--新建项目-->
        <Modal
            v-model="addShow"
            :title="$L('添加任务')"
            :styles="{
                width: '90%',
                maxWidth: '640px'
            }"
            :mask-closable="false"
            class-name="simple-modal">
            <TaskAdd v-model="addData"/>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="taskLoad > 0" @click="">{{$L('添加')}}</Button>
            </div>
        </Modal>
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
                        border-radius: 50%;
                        position: relative;
                        margin-left: 16px;
                        cursor: pointer;
                        transition: all 0.3s;
                        &:hover {
                            box-shadow: 0 0 6px #cccccc;
                        }
                        &.project-icon {
                            border-radius: 50%;
                            background-color: #F2F3F5;
                            .ivu-icon {
                                font-size: 20px;
                                width: 36px;
                                height: 36px;
                                line-height: 36px;
                            }
                            .ivu-badge {
                                position: absolute;
                                top: -6px;
                                left: 20px;
                                transform: scale(0.8);
                            }
                            &.active {
                                color: #ffffff;
                                background-color: #2d8cf0;
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
                                    overflow: auto;
                                    margin-right: 24px;
                                    > li {
                                        list-style: none;
                                        margin-left: -6px;
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
        .project-table {
            height: 100%;
            overflow-y: auto;
            .project-row {
                background-color: #ffffff;
                border-bottom: 1px solid #F4F4F5;
                > div {
                    display: flex;
                    align-items: center;
                    padding: 8px 12px;
                    border-right: 1px solid #F4F4F5;
                    &:first-child {
                        padding-left: 32px;
                    }
                    &:last-child {
                        border-right: 0;
                    }
                }
            }
            .project-table-head,
            .project-table-body {
                margin: 0 32px 12px;
                border-radius: 5px;
                border: 1px solid #F4F4F5;
                border-bottom: 0;
                overflow: hidden;
            }
            .project-table-head {
                .project-row {
                    > div {
                        color: #888888;
                        font-size: 13px;
                        font-weight: 500;
                    }
                }
            }
            .project-table-body {
                &:hover {
                    box-shadow: 0 0 10px #e6ecfa;
                }
                .project-row {
                    > div {
                        padding: 10px 12px;
                        &.row-title {
                            font-size: 14px;
                            font-weight: 500;
                            color: #333333;
                            padding-left: 14px;
                            .iconfont {
                                font-size: 12px;
                            }
                            .row-h1 {
                                padding-left: 18px;
                            }
                            .row-num {
                                color: #999999;
                                padding-left: 6px;
                            }
                        }
                        &.row-item {
                            padding-left: 20px;
                            .ivu-icon {
                                font-size: 16px;
                                color: #dddddd;
                                &.completed {
                                    color: #87d068;
                                }
                            }
                            .item-title {
                                padding: 0 22px 0 10px;
                            }
                            .item-icon {
                                font-size: 12px;
                                margin-right: 8px;
                                color: #777777;
                                .ivu-icon,
                                .iconfont {
                                    margin-left: 1px;
                                    font-size: 14px;
                                    color: #666666;
                                }
                                .iconfont {
                                    color: #999999;
                                }
                            }
                        }
                        &.row-member {
                            > ul {
                                display: flex;
                                align-items: center;
                                overflow: auto;
                                margin-left: -4px;
                                > li {
                                    list-style: none;
                                    margin-left: -6px;
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
                        }
                    }
                }
            }
        }
    }
}
</style>

<script>
import TaskPriority from "./task-priority";
import TaskAdd from "./task-add";
export default {
    name: "ProjectList",
    components: {TaskAdd, TaskPriority},
    data() {
        return {
            searchText: '',

            addShow: false,
            addData: {
                type: 'task',
                owner: 1,
                column_id: 5,
                times: [],
            },
            taskLoad: false,
        }
    },
    mounted() {

    }
}
</script>
