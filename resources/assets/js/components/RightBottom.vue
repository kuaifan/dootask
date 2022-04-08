<template>
    <div class="common-right-bottom">
        <div v-if="showSSO" class="common-right-bottom-link" @click="useSSOLogin">
            <Icon type="ios-globe-outline"/>
            {{ $L('使用 SSO 登录') }}
        </div>
        <template v-if="showDown">
            <a v-if="downloadUrl" class="common-right-bottom-link" :href="downloadUrl" target="_blank">
                <Icon type="md-download"/>
                {{ $L('客户端下载') }}
            </a>
            <div v-else-if="updateVersion && $Electron" class="common-right-bottom-link" @click="updateShow=true">
                <Icon type="md-download"/>
                {{ $L('更新客户端') }}
            </div>
        </template>
        <Modal
            v-model="updateShow"
            :closable="false"
            :mask-closable="false"
            class-name="common-right-bottom-notification">
            <div slot="header">
                <div class="notification-head">
                    <div class="notification-title">{{$L('发现新版本')}}</div>
                    <Tag color="volcano">v{{systemVersion}} -&gt; v{{updateVersion}}</Tag>
                </div>
                <div v-if="$Platform === 'mac'" class="notification-tip">{{$L('离最新版本只有一步之遥了！重新启动应用即可完成更新。')}}</div>
            </div>
            <MarkdownPreview class="notification-body overlay-y" :initialValue="updateNote"/>
            <div slot="footer" class="adaption">
                <Button type="default" @click="updateShow=false">{{$L('稍后')}}</Button>
                <Button type="primary" :loading="updateIng" @click="updateQuitAndInstall">{{$L($Platform === 'mac' ? '重新启动' : '立即升级')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import MarkdownPreview from "./MDEditor/components/preview";
import axios from "axios";
import {mapState} from "vuex";
import {Store} from "le5le-store";

export default {
    name: 'RightBottom',
    components: {MarkdownPreview},
    data() {
        return {
            loadIng: 0,
            subscribe: null,

            apiVersion: '',
            systemVersion: window.systemInfo.version,

            updateVersion: '',
            updateNote: '',
            updateShow: false,
            updateIng: false,

            downloadUrl: '',
        }
    },

    mounted() {
        this.checkVersion()
        //
        if (this.$Electron) {
            this.subscribe = Store.subscribe('updateNotification', _ => {
                this.updateShow = true
            })
            this.$Electron.registerMsgListener('updateDownloaded', info => {
                this.updateVersion = info.version;
                this.updateNote = info.releaseNotes || this.$L('没有更新描述。');
                this.updateShow = true;
            })
        }
    },

    beforeDestroy() {
        if (this.subscribe) {
            this.subscribe.unsubscribe();
            this.subscribe = null;
        }
    },

    computed: {
        ...mapState([
            'isDesktop',
        ]),

        showSSO() {
            return this.$Electron && ['login'].includes(this.$route.name)
        },

        showDown() {
            return this.isDesktop && ['login', 'index', 'manage-dashboard'].includes(this.$route.name)
        }
    },

    methods: {
        checkVersion() {
            axios.get($A.apiUrl('../version')).then(({status, data}) => {
                if (status === 200) {
                    this.apiVersion = data.version || ''
                    if (this.$Electron) {
                        // 客户端提示更新
                        this.$Electron.sendMessage('updateCheckAndDownload', {
                            apiVersion: this.apiVersion
                        })
                    } else {
                        // 网页端提示下载
                        this.getDownloadUrl(data.publish)
                    }
                }
            }).catch(_ => {

            })
            //
            this.__checkVersion && clearTimeout(this.__checkVersion)
            this.__checkVersion = setTimeout(this.checkVersion, 600 * 1000)
        },

        getDownloadUrl(publish) {
            if (!$A.isJson(publish)) {
                return;
            }
            //
            switch (publish.provider) {
                case 'generic':
                    this.downloadUrl = `${publish.url}/${this.apiVersion}`
                    break;

                case 'github':
                    let key = "cacheAppdown::" + this.apiVersion
                    let cache = $A.getStorageJson(key);
                    let timeout = 600;
                    if (cache.time && cache.time + timeout > Math.round(new Date().getTime() / 1000)) {
                        this.downloadUrl = cache.data.html_url;
                        return;
                    }
                    //
                    if (this.loadIng > 0) {
                        return;
                    }
                    this.loadIng++;
                    axios.get(`https://api.github.com/repos/${publish.owner}/${publish.repo}/releases`).then(({status, data}) => {
                        this.loadIng--;
                        if (status === 200 && $A.isArray(data)) {
                            cache.time = Math.round(new Date().getTime() / 1000)
                            cache.data = data.find(({tag_name}) => this.compareVersion(this.tagVersion(tag_name), this.apiVersion) === 0) || {}
                            $A.setStorage(key, cache);
                            this.downloadUrl = cache.data.html_url;
                        }
                    }).catch(() => {
                        this.loadIng--;
                    });
                    break;
            }
        },

        updateQuitAndInstall() {
            this.updateIng = true
            setTimeout(() => {
                this.$Electron.sendMessage('updateQuitAndInstall')
            }, 301)
        },

        useSSOLogin() {
            Store.set('useSSOLogin', true);
        },

        tagVersion(tag) {
            return tag ? $A.leftDelete(tag.toLowerCase(), "v") : ''
        },

        compareVersion(version1, version2) {
            let pA = 0, pB = 0;

            // 版本号完全相同
            if (version1 === version2) {
                return 0
            }

            // 寻找当前区间的版本号
            const findDigit = (str, start) => {
                let i = start;
                while (str[i] !== '.' && i < str.length) {
                    i++;
                }
                return i;
            }

            while (pA < version1.length && pB < version2.length) {
                const nextA = findDigit(version1, pA);
                const nextB = findDigit(version2, pB);
                const numA = +version1.substr(pA, nextA - pA);
                const numB = +version2.substr(pB, nextB - pB);
                if (numA !== numB) {
                    return numA > numB ? 1 : -1;
                }
                pA = nextA + 1;
                pB = nextB + 1;
            }

            // 若arrayA仍有小版本号
            while (pA < version1.length) {
                const nextA = findDigit(version1, pA);
                const numA = +version1.substr(pA, nextA - pA);
                if (numA > 0) {
                    return 1;
                }
                pA = nextA + 1;
            }

            // 若arrayB仍有小版本号
            while (pB < version2.length) {
                const nextB = findDigit(version2, pB);
                const numB = +version2.substr(pB, nextB - pB);
                if (numB > 0) {
                    return -1;
                }
                pB = nextB + 1;
            }

            // 版本号完全相同
            return 0;
        },
    }
};
</script>
