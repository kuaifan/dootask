<template>
    <div class="manage-box">
        <div class="manage-box-menu">
            <div class="manage-box-title">
                <div class="manage-box-logo"></div>
                <span>Doo Task</span>
            </div>
            <ul>
                <li>
                    <Icon type="md-speedometer" />
                    <div class="menu-title">Dashboard</div>
                </li>
                <li>
                    <Icon type="md-cog" />
                    <div class="menu-title">Setting</div>
                </li>
                <li>
                    <Icon type="md-calendar" />
                    <div class="menu-title">Calendar</div>
                </li>
                <li class="menu-project">
                    <ul>
                        <li class="action">✔️ Daily Task</li>
                        <li>✈️ Meetings Summary</li>
                        <li>🛰 Resources</li>
                        <li>💺 Availibity</li>
                        <li>🍒 Brainstroaming</li>
                    </ul>
                </li>
            </ul>
            <Button class="manage-box-new" type="primary" icon="md-add">New Project</Button>
        </div>
        <div class="manage-box-main">
            <div class="manage-box-body">
                <div class="manage-box-body-content">
                    <keep-alive>
                        <router-view class="manage-box-view"></router-view>
                    </keep-alive>
                </div>
            </div>
        </div>
    </div>
</template>


<style lang="scss" scoped>
:global {
    .manage-box {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        .manage-box-menu {
            flex-grow: 0;
            flex-shrink: 0;
            width: 255px;
            height: 100%;
            background: #F4F5F7;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: all 0.2s;
            .manage-box-title {
                display: flex;
                align-items: center;
                flex-shrink: 0;
                width: 86%;
                padding: 6px;
                margin-top: 27px;
                border-radius: 8px;
                background-color: #ffffff;
                .manage-box-logo {
                    width: 36px;
                    height: 36px;
                    background: url("../../statics/images/logo.svg") no-repeat center center;
                    background-size: contain;
                }
                > span {
                    padding-left: 12px;
                    font-size: 16px;
                    font-weight: 600;
                }
            }
            > ul {
                flex: 1;
                width: 100%;
                margin-top: 16px;
                overflow: auto;
                > li {
                    display: flex;
                    align-items: center;
                    height: 38px;
                    color: #6C7D8C;
                    cursor: pointer;
                    position: relative;
                    width: 80%;
                    max-width: 100%;
                    margin: 8px auto;
                    padding: 0 4%;
                    border-radius: 4px;
                    > i {
                        opacity: 0.3;
                        font-size: 22px;
                        margin-right: 10px;
                        margin-top: -1px;
                    }
                    .menu-title {
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    &.menu-project {
                        height: auto;
                        padding: 14px 0 0;
                        > ul {
                            width: 100%;
                            > li {
                                position: relative;
                                list-style: none;
                                padding: 0 8px 0 30px;
                                height: 38px;
                                line-height: 38px;
                                margin: 4px auto;
                                overflow: hidden;
                                text-overflow: ellipsis;
                                white-space: nowrap;
                                border-radius: 4px;
                                color: #333333;
                                &:before {
                                    content: "";
                                    position: absolute;
                                    top: 50%;
                                    left: 8px;
                                    transform: translateY(-50%);
                                    width: 12px;
                                    height: 12px;
                                    background: url("data:image/svg+xml;base64,PHN2ZyB0PSIxNjIyMzkwODExNTQxIiBjbGFzcz0iaWNvbiIgdmlld0JveD0iMCAwIDEwMjQgMTAyNCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHAtaWQ9IjI0OTk3IiB3aWR0aD0iNDgiIGhlaWdodD0iNDgiPjxwYXRoIGQ9Ik0zNjYuMTgyNCAxMDguMjM2OEw4MTIuMDMyIDQyOC4wMzJhMTAyLjQgMTAyLjQgMCAwIDEgMCAxNjYuNTAyNEwzNjYuMTgyNCA5MTQuMzI5NmExMDIuNCAxMDIuNCAwIDAgMS0xNjIuMDk5Mi04My4yNTEyVjE5MS40ODhhMTAyLjQgMTAyLjQgMCAwIDEgMTYyLjA5OTItODMuMjUxMnoiIHAtaWQ9IjI0OTk4IiBmaWxsPSIjOTk5OTk5Ij48L3BhdGg+PC9zdmc+") no-repeat center center;
                                    background-size: contain;
                                }
                                &.action {
                                    background-color: #ffffff;
                                }
                            }
                        }
                    }
                    &.action {
                        background-color: #ffffff;
                    }
                }
            }
            .manage-box-new {
                width: 80%;
                height: 38px;
                margin-top: 16px;
                margin-bottom: 20px;
            }
        }
        .manage-box-main {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: white;
            .manage-box-body {
                width: 100%;
                flex: 1;
                position: relative;
                overflow: auto;
                display: flex;
                flex-direction: column;
                .manage-box-body-content {
                    flex: 1;
                    position: relative;
                    .manage-box-view {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        overflow: auto;
                    }
                }
            }
        }
    }
}
</style>

<script>
export default {
    data() {
        return {
        }
    },
    mounted() {
        $A.getUserInfo(true);
    },
}
</script>
