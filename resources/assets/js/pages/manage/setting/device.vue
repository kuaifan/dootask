<template>
    <div class="setting-device">
        <ul>
            <li v-if="loadIng > 0 && devices.length === 0" class="loading">
                <Loading/>
            </li>
            <template v-else>
                <li v-for="device in devices" :key="device.id">
                    <div class="icon">
                        <span :class="getIcon(device.detail)"></span>
                    </div>
                    <div class="info">
                        <div class="title">
                            <span class="name">{{ getName(device.detail) }}</span>
                            <span class="device">{{ getOs(device.detail) }}</span>
                        </div>
                        <div class="time">
                            <EPopover placement="bottom-start" trigger="click">
                                <div class="setting-device-popover">
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
            </template>
        </ul>
    </div>
</template>

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
                if (typeof this.$parent.updateDeviceCount === "function") {
                    this.$parent.updateDeviceCount(this.devices.length);
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.devices = []
            }).finally(() => {
                this.loadIng--;
            })
        },

        getIcon({app_type, app_name}) {
            if (/ios/i.test(app_type)) {
                if (/ipad/i.test(app_name)) {
                    return 'tablet';
                }
                if (/iphone/i.test(app_name)) {
                    return 'phone';
                }
                return 'apple';
            }
            else if (/android/i.test(app_type)) {
                if (/(tablet|phablet)/i.test(app_name)) {
                    return 'tablet';
                }
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

        getName({app_brand, app_model, device_name, app_type, app_name, browser}) {
            const array = [];
            if (/web/i.test(app_type)) {
                array.push(...[browser, this.$L("浏览器")]);
            } else if (device_name) {
                return device_name
            } else if (app_brand) {
                array.push(...[app_brand, app_model])
            } else {
                array.push(...[app_name || app_type, this.$L("客户端")])
            }
            return array.join(' ');
        },

        getOs({app_os, os}) {
            return app_os || os;
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
