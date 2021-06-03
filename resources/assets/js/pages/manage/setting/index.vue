<template>
    <div class="setting">
        <PageTitle>{{$L('设置')}}</PageTitle>
        <div class="setting-head">
            <div class="setting-titbox">
                <div class="setting-title">
                    <h1>{{$L('设置')}}</h1>
                </div>
            </div>
        </div>
        <div class="setting-box">
            <div class="setting-menu">
                <ul>
                    <li v-for="(item, key) in menu" :key="key" @click="toggleRoute(item.path)" :class="classNameRoute(item.path)">{{$L(item.name)}}</li>
                </ul>
            </div>
            <div class="setting-content">
                <div class="setting-content-title">{{$L(titleNameRoute)}}</div>
                <div class="setting-content-view"><router-view class="setting-router-view"></router-view></div>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .setting {
        display: flex;
        flex-direction: column;
        .setting-head {
            display: flex;
            align-items: flex-start;
            margin: 32px 32px 16px;
            border-bottom: 1px solid #F4F4F5;
            .setting-titbox {
                flex: 1;
                margin-bottom: 16px;
                .setting-title {
                    display: flex;
                    align-items: center;
                    > h1 {
                        color: #333333;
                        font-size: 28px;
                        font-weight: 600;
                    }
                }
            }
        }
    }
    .setting-box {
        flex: 1;
        height: 0;
        display: flex;
        margin-bottom: 16px;
        .setting-menu {
            width: 200px;
            flex-shrink: 0;
            border-right: 1px solid #F4F4F5;
            overflow: auto;
            > ul {
                padding: 12px 0 0 32px;
                > li {
                    cursor: pointer;
                    color: #6C7D8C;
                    list-style: none;
                    line-height: 42px;
                    padding: 0 20px;
                    margin: 5px 0;
                    position: relative;
                    &.active,
                    &:hover {
                        background-color: #F4F5F7;
                    }
                }
            }
        }
        .setting-content {
            flex: 1;
            overflow: auto;
            position: relative;
            display: flex;
            flex-direction: column;
            .setting-content-title {
                font-size: 20px;
                font-weight: 500;
                padding: 12px 32px;
            }
            .setting-content-view {
                flex: 1;
                position: relative;
                .setting-router-view {
                    padding: 24px 40px;
                }
            }
        }
    }
}
</style>
<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            curPath: this.$route.path,

            menu: [
                {
                    path: 'personal',
                    name: '个人设置'
                },
                {
                    path: 'password',
                    name: '密码设置'
                },
                {
                    path: 'system',
                    name: '系统设置'
                },
                {
                    path: 'priority',
                    name: '任务优先级'
                }
            ]
        }
    },
    mounted() {

    },
    computed: {
        ...mapState(['userInfo']),

        titleNameRoute() {
            const {curPath, menu} = this;
            let name = '';
            menu.some((item) => {
                if ($A.leftExists(curPath, '/manage/setting/' + item.path)) {
                    name = item.name;
                    return true;
                }
            })
            return name;
        }
    },
    watch: {
        '$route' (route) {
            this.curPath = route.path;
        }
    },
    methods: {
        toggleRoute(path) {
            this.goForward({path: '/manage/setting/' + path});
        },

        classNameRoute(path) {
            return {
                "active": $A.leftExists(this.curPath, '/manage/setting/' + path)
            };
        },
    }
}
</script>
