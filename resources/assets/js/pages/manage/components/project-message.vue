<template>
    <div class="project-message">
        <div class="group-member">
            <div class="member-head">
                <div class="member-title">Member<span>(25)</span></div>
                <div class="member-view-all">View All</div>
            </div>
            <ul class="member-list">
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
            </ul>
        </div>
        <div class="group-chat">
            <div class="group-title">Group Chat</div>
        </div>
        <div class="group-input">

        </div>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project-message {
        display: flex;
        flex-direction: column;
        padding: 36px 24px;
        .group-member {
            padding: 0 8px;
            .member-head {
                display: flex;
                align-items: center;
                .member-title {
                    flex: 1;
                    font-size: 18px;
                    font-weight: 600;
                    > span {
                        padding-left: 6px;
                        color: #4E60CC;
                    }
                }
                .member-view-all {
                    color: #999;
                }
            }
            .member-list {
                display: flex;
                align-items: center;
                margin-top: 14px;
                overflow: auto;
                > li {
                    position: relative;
                    list-style: none;
                    margin-right: 13px;
                    .ivu-avatar {
                        width: 38px;
                        height: 38px;
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
        .group-chat {
            flex: 1;
            padding: 0 8px;
            margin-top: 24px;
            .group-title {
                font-size: 18px;
                font-weight: 600;
            }
        }
        .group-input {

        }
    }
}
</style>

<script>
export default {
    name: "ProjectMessage",
    data() {
        return {

        }
    },
    mounted() {

    },
}
</script>
