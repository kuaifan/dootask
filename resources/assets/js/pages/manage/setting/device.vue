<template>
    <div class="setting-device">
        <ul>
            <li v-for="device in devices" :key="device.id">
                <div class="icon">
                    <span :class="getIcon(device.detail)"></span>
                </div>
                <div class="info">
                    <div class="title">
                        <span class="name">{{ getName(device.detail) }}</span>
                        <span class="device">{{ device.detail.os }}</span>
                    </div>
                    <div class="time">
                        <EPopover placement="bottom-start" trigger="click">
                            <div class="time-popover">
                                <p>{{$L('登录时间')}}: {{device.created_at}}</p>
                                <p>{{$L('更新时间')}}: {{device.updated_at}}</p>
                                <p>{{$L('过期时间')}}: {{device.expired_at}}</p>
                            </div>
                            <span slot="reference">{{ device.updated_at }}</span>
                        </EPopover>
                    </div>
                </div>
                <div>
                    <span v-if="device.is_current" class="current">{{$L('当前设备')}}</span>
                    <Button v-else @click="onLogout(device)">{{$L('退出登录')}}</Button>
                </div>
            </li>
        </ul>
    </div>
</template>

<style lang="scss" scoped>
.setting-device {
    > ul {
        display: flex;
        flex-direction: column;
        gap: 16px;

        > li {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 24px;
            border-radius: 12px;
            background: rgba(79, 89, 102, .04);

            .icon {
                align-self: flex-start;
                display: flex;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                height: 24px;

                > span {
                    width: 20px;
                    height: 20px;
                }
            }

            .info {
                flex: 1 1 auto;
                display: flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
                gap: 6px;

                .title {
                    width: 100%;
                    font-size: 16px;
                    line-height: 24px;
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    gap: 2px;
                    justify-content: flex-start;
                    color: #262626;

                    .name {
                        font-weight: 500;
                    }

                    .device {
                        &:before {
                            content: "（";
                        }

                        &:after {
                            content: "）";
                        }
                    }
                }

                .time {
                    width: 100%;
                    font-size: 14px;
                    line-height: 22px;
                    color: #8a939d;
                    cursor: pointer;
                }
            }

            .current {
                color: #595959;
            }

            .ivu-btn {
                background: #d9d9dd;
                border-color: #d9d9dd;
                color: #262626;
                box-shadow: none;
                height: 36px;
                padding: 0 12px;
                border-radius: 12px;
                &:hover {
                    background: rgba(217, 217, 221, 0.8);
                    border-color: rgba(217, 217, 221, 0.8);
                }
            }
        }
    }
}
.time-popover {
    > p {
        line-height: 26px;
    }
}
</style>

<script>
export default {
    name: 'SettingDevice',
    data() {
        return {
            loadIng: 0,
            devices: []
        };
    },

    mounted() {
        this.getDeviceList();
    },

    methods: {
        getDeviceList() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'users/device/list',
            }).then(({data}) => {
                this.devices = data.list
                if (typeof this.$parent.updateDeviceNum === "function") {
                    this.$parent.updateDeviceNum(this.devices.length);
                }
            }).catch(() => {
                this.devices = []
            }).finally(() => {
                this.loadIng--;
            })
        },

        getIcon({app_type}) {
            if (/ios/i.test(app_type)) {
                return 'apple';
            }
            else if (/android/i.test(app_type)) {
                return 'android';
            }
            else if (/mac/i.test(app_type)) {
                return 'macos';
            }
            else if (/win/i.test(app_type)) {
                return 'window';
            }
            return 'web';
        },

        getName({app_type, browser}) {
            if (/web/i.test(app_type)) {
                return browser + " " + this.$L("浏览器")
            }
            return app_type + " " + this.$L("客户端")
        },

        onLogout(device) {
            $A.modalConfirm({
                title: '退出登录',
                content: '是否在该设备上退出登录？',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'users/device/logout',
                            data: {
                                id: device.id
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            this.getDeviceList();
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                },
            });
        }
    }
};
</script>
